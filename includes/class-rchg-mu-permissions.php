<?php
if (!defined('ABSPATH')) exit;

class RCHG_MU_Permissions {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Frontend & Admin filtering
        add_filter('pre_get_posts', array($this, 'filter_posts_by_permission'), 10);
        add_action('template_redirect', array($this, 'check_single_post_permission'));
        add_filter('get_terms', array($this, 'filter_categories'), 10, 3);
        
        // Deep permission controls
        add_filter('map_meta_cap', array($this, 'filter_post_capabilities'), 10, 4);
        add_filter('user_has_cap', array($this, 'filter_create_post_cap'), 10, 3);
        
        // Auto-assign on user registration
        add_action('user_register', array($this, 'assign_default_permissions'));
    }
    
    /**
     * Get user permissions for a category
     */
    public function get_user_permission($user_id, $category_id, $permission = 'can_view') {
        global $wpdb;
        
        $user = get_userdata($user_id);
        if (!$user) return false;
        
        // Validate permission column name (whitelist only)
        $allowed_permissions = array('can_view', 'can_edit', 'can_create');
        if (!in_array($permission, $allowed_permissions, true)) {
            return false;
        }
        
        // Escape column name safely (already validated against whitelist)
        $safe_permission = esc_sql($permission);
        
        // Check user-specific permission first
        $user_perm = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "SELECT $safe_permission FROM {$wpdb->prefix}rchg_mu_permissions WHERE permission_type = 'user' AND user_id = %d AND category_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $user_id,
                $category_id
            )
        );
        
        if ($user_perm !== null) {
            return (bool) $user_perm;
        }
        
        // Fallback to role permission
        foreach ($user->roles as $role) {
            $role_perm = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                    "SELECT $safe_permission FROM {$wpdb->prefix}rchg_mu_permissions WHERE permission_type = 'role' AND role_name = %s AND category_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    $role,
                    $category_id
                )
            );
            
            if ($role_perm !== null) {
                return (bool) $role_perm;
            }
        }
        
        // Default: allow view, deny edit/create
        return ($permission === 'can_view');
    }
    
    /**
     * Get all categories user has permission for
     */
    public function get_user_categories($user_id, $permission = 'can_view') {
        // Remove filter temporarily to prevent infinite loop
        remove_filter('get_terms', array($this, 'filter_categories'), 10);
        
        $categories = get_categories(array('hide_empty' => false));
        $allowed = array();
        
        foreach ($categories as $cat) {
            if ($this->get_user_permission($user_id, $cat->term_id, $permission)) {
                $allowed[] = $cat->term_id;
            }
        }
        
        // Re-add filter
        add_filter('get_terms', array($this, 'filter_categories'), 10, 3);
        
        return $allowed;
    }
    
    /**
     * Filter posts in queries
     */
    public function filter_posts_by_permission($query) {
        if (is_admin() || !$query->is_main_query()) return;
        if (current_user_can('manage_options')) return;
        
        $user_id = get_current_user_id();
        if (!$user_id) return;
        
        $allowed_cats = $this->get_user_categories($user_id, 'can_view');
        
        if (!empty($allowed_cats)) {
            $query->set('category__in', $allowed_cats);
        } else {
            $query->set('category__in', array(0)); // No posts
        }
    }
    
    /**
     * Check permission for single post
     */
    public function check_single_post_permission() {
        if (!is_single()) return;
        if (current_user_can('manage_options')) return;
        
        $user_id = get_current_user_id();
        if (!$user_id) return;
        
        $post_id = get_the_ID();
        $categories = wp_get_post_categories($post_id);
        
        $has_permission = false;
        foreach ($categories as $cat_id) {
            if ($this->get_user_permission($user_id, $cat_id, 'can_view')) {
                $has_permission = true;
                break;
            }
        }
        
        if (!$has_permission) {
            wp_die(esc_html__('Bạn không có quyền xem bài viết này.', 'role-category-manager'));
        }
    }
    
    /**
     * Filter categories in dropdowns
     */
    public function filter_categories($terms, $taxonomies, $args) {
        if (!in_array('category', (array) $taxonomies)) return $terms;
        if (current_user_can('manage_options')) return $terms;
        
        $user_id = get_current_user_id();
        if (!$user_id) return $terms;
        
        // In admin: filter by create permission
        // In frontend: filter by view permission
        $permission = is_admin() ? 'can_create' : 'can_view';
        
        // Get allowed categories directly from database to avoid infinite loop
        global $wpdb;
        $user = get_userdata($user_id);
        if (!$user) return array();
        
        // Validate permission column
        $allowed_permissions = array('can_view', 'can_edit', 'can_create');
        if (!in_array($permission, $allowed_permissions, true)) {
            return $terms;
        }
        $safe_permission = esc_sql($permission);
        
        // Get user-specific permissions
        $user_cats = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "SELECT category_id FROM {$wpdb->prefix}rchg_mu_permissions WHERE permission_type = 'user' AND user_id = %d AND $safe_permission = 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $user_id
            )
        );
        
        // Get role permissions
        $role_cats = array();
        if (!empty($user->roles)) {
            $roles_placeholder = implode(',', array_fill(0, count($user->roles), '%s'));
            $role_cats = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                    "SELECT category_id FROM {$wpdb->prefix}rchg_mu_permissions WHERE permission_type = 'role' AND role_name IN ($roles_placeholder) AND $safe_permission = 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
                    ...$user->roles
                )
            );
        }
        
        $allowed_cats = array_unique(array_merge($user_cats, $role_cats));
        
        if (empty($allowed_cats)) {
            return is_array($terms) ? array() : array();
        }
        
        // Filter terms
        $filtered = array();
        foreach ($terms as $term) {
            if (in_array($term->term_id, $allowed_cats)) {
                $filtered[] = $term;
            }
        }
        
        return $filtered;
    }
    
    /**
     * Filter edit/delete post capabilities
     */
    public function filter_post_capabilities($caps, $cap, $user_id, $args) {
        if (!in_array($cap, array('edit_post', 'delete_post'))) return $caps;
        if (current_user_can('manage_options')) return $caps;
        
        $post_id = isset($args[0]) ? $args[0] : 0;
        if (!$post_id) return $caps;
        
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'post') return $caps;
        
        $categories = wp_get_post_categories($post_id);
        
        $has_permission = false;
        foreach ($categories as $cat_id) {
            if ($this->get_user_permission($user_id, $cat_id, 'can_edit')) {
                $has_permission = true;
                break;
            }
        }
        
        if (!$has_permission) {
            $caps[] = 'do_not_allow';
        }
        
        return $caps;
    }
    
    /**
     * Filter create post capability
     */
    public function filter_create_post_cap($allcaps, $caps, $args) {
        if (!isset($args[0]) || !in_array($args[0], array('edit_posts', 'create_posts'))) {
            return $allcaps;
        }
        
        if (current_user_can('manage_options')) return $allcaps;
        
        $user_id = get_current_user_id();
        if (!$user_id) return $allcaps;
        
        // Check if user has at least one category with create permission
        $allowed_cats = $this->get_user_categories($user_id, 'can_create');
        
        if (empty($allowed_cats)) {
            $allcaps['edit_posts'] = false;
            $allcaps['create_posts'] = false;
        }
        
        return $allcaps;
    }
    
    /**
     * Assign default permissions when user is created
     */
    public function assign_default_permissions($user_id) {
        $user = get_userdata($user_id);
        if (!$user || empty($user->roles)) return;
        
        global $wpdb;
        
        // Get role permissions
        $role = $user->roles[0];
        $role_perms = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}rchg_mu_permissions WHERE permission_type = 'role' AND role_name = %s",
                $role
            ),
            ARRAY_A
        );
        
        // Clone to user
        if (is_array($role_perms)) {
            foreach ($role_perms as $perm) {
                $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                    "{$wpdb->prefix}rchg_mu_permissions",
                    array(
                        'permission_type' => 'user',
                        'user_id' => $user_id,
                        'category_id' => intval($perm['category_id']),
                        'can_view' => intval($perm['can_view']),
                        'can_edit' => intval($perm['can_edit']),
                        'can_create' => intval($perm['can_create']),
                    )
                );
            }
        }
    }
}
