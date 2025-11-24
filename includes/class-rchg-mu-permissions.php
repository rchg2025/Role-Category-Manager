<?php
/**
 * Class xử lý phân quyền
 */

if (!defined('ABSPATH')) {
    exit;
}

class RCHG_MU_Permissions {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Lọc query bài viết dựa trên phân quyền (frontend)
        add_action('pre_get_posts', array($this, 'filter_posts_by_permission'));
        
        // Ẩn categories không có quyền xem trong menu (frontend)
        add_filter('get_terms', array($this, 'filter_categories'), 10, 3);
        
        // Kiểm tra quyền xem bài viết đơn lẻ
        add_action('template_redirect', array($this, 'check_single_post_permission'));

        // ENFORCE edit/create permissions in wp-admin
        if (is_admin()) {
            // Hide disallowed categories in editor & quick edit
            add_filter('get_terms', array($this, 'filter_admin_categories'), 11, 3);

            // Block editing posts outside UI based on categories
            add_filter('map_meta_cap', array($this, 'enforce_post_caps'), 10, 4);

            // Block Add New when user has no create permission
            add_filter('user_has_cap', array($this, 'enforce_user_caps'), 10, 4);
        }

        // Default user-specific permissions on user creation (copy from role)
        add_action('user_register', array($this, 'assign_defaults_on_user_register'));        
    }
    
    /**
     * Lấy danh sách categories mà user có quyền xem
     */
    public function get_allowed_categories_for_user($user_id = null, $permission = 'view') {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        // Nếu user không đăng nhập hoặc là admin, cho phép xem tất cả
        if (!$user_id || user_can($user_id, 'manage_options')) {
            return 'all';
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            return array();
        }
        
        $roles = $user->roles;
        if (empty($roles)) {
            return array();
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'rchg_mu_permissions';

        $column = 'can_view';
        if ($permission === 'edit') $column = 'can_edit';
        if ($permission === 'create') $column = 'can_create';

        $allowed_categories = array();

        // Ưu tiên cấu hình theo user
        $user_cats = $wpdb->get_col($wpdb->prepare(
            "SELECT category_id FROM {$table_name} WHERE permission_type = 'user' AND user_id = %d AND {$column} = 1",
            $user_id
        ));

        if (!empty($user_cats)) {
            $allowed_categories = array_map('intval', $user_cats);
        } else {
            // Nếu không có theo user, lấy theo role
            foreach ($roles as $role) {
                $categories = $wpdb->get_col($wpdb->prepare(
                    "SELECT category_id FROM {$table_name} WHERE permission_type = 'role' AND role_name = %s AND {$column} = 1",
                    $role
                ));
                if (!empty($categories)) {
                    $allowed_categories = array_merge($allowed_categories, array_map('intval', $categories));
                }
            }
        }

        // Nếu không có cấu hình nào, mặc định không hạn chế (tránh khóa nhầm)
        if ($permission === 'view' && empty($allowed_categories)) {
            return 'all';
        }

        return array_unique($allowed_categories);
    }
    
    /**
     * Lọc bài viết theo phân quyền
     */
    public function filter_posts_by_permission($query) {
        // Chỉ áp dụng cho query chính, không phải admin
        if (is_admin() || !$query->is_main_query()) {
            return;
        }
        
        $allowed_categories = $this->get_allowed_categories_for_user();
        
        // Nếu cho phép xem tất cả, không làm gì
        if ($allowed_categories === 'all') {
            return;
        }
        
        // Nếu không có category nào được phép xem
        if (empty($allowed_categories)) {
            $query->set('post__in', array(0)); // Không hiển thị bài viết nào
            return;
        }
        
        // Lọc theo categories được phép
        $tax_query = $query->get('tax_query');
        if (!is_array($tax_query)) {
            $tax_query = array();
        }
        
        $tax_query[] = array(
            'taxonomy' => 'category',
            'field' => 'term_id',
            'terms' => $allowed_categories,
            'operator' => 'IN'
        );
        
        $query->set('tax_query', $tax_query);
    }
    
    /**
     * Lọc danh sách categories
     */
    public function filter_categories($terms, $taxonomies, $args) {
        // Chỉ áp dụng cho category taxonomy
        if (!in_array('category', $taxonomies) || is_admin()) {
            return $terms;
        }
        
        $allowed_categories = $this->get_allowed_categories_for_user(null, 'view');
        
        // Nếu cho phép xem tất cả
        if ($allowed_categories === 'all') {
            return $terms;
        }
        
        // Nếu không có category nào được phép
        if (empty($allowed_categories)) {
            return array();
        }
        
        // Lọc các categories
        $filtered_terms = array();
        foreach ($terms as $term) {
            if (in_array($term->term_id, $allowed_categories)) {
                $filtered_terms[] = $term;
            }
        }
        
        return $filtered_terms;
    }

    /**
     * Ẩn categories trong wp-admin (editor, quick edit) theo quyền edit/create
     */
    public function filter_admin_categories($terms, $taxonomies, $args) {
        if (!is_admin() || empty($terms) || !in_array('category', (array)$taxonomies, true)) {
            return $terms;
        }
        // Chỉ áp dụng ở màn soạn bài hoặc quick edit
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen && $screen->post_type && $screen->base && in_array($screen->base, array('post','edit'))) {
            $allowed = $this->get_allowed_categories_for_user(null, 'create');
            if ($allowed === 'all') {
                return $terms;
            }
            $allowed = is_array($allowed) ? $allowed : array();
            $filtered = array();
            foreach ($terms as $t) {
                if (in_array((int)$t->term_id, $allowed, true)) {
                    $filtered[] = $t;
                }
            }
            return $filtered;
        }
        return $terms;
    }
    
    /**
     * Kiểm tra quyền xem bài viết đơn lẻ
     */
    public function check_single_post_permission() {
        if (!is_single()) {
            return;
        }
        
        $post_id = get_the_ID();
        $categories = get_the_category($post_id);
        
        if (empty($categories)) {
            return;
        }
        
    $allowed_categories = $this->get_allowed_categories_for_user(null, 'view');
        
        // Nếu cho phép xem tất cả
        if ($allowed_categories === 'all') {
            return;
        }
        
        // Kiểm tra xem bài viết có thuộc category được phép không
        $has_permission = false;
        foreach ($categories as $category) {
            if (in_array($category->term_id, $allowed_categories)) {
                $has_permission = true;
                break;
            }
        }
        
        // Nếu không có quyền, chuyển về trang 404
        if (!$has_permission) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            get_template_part(404);
            exit();
        }
    }
    
    /**
     * Kiểm tra user có quyền xem category không
     */
    public function user_can_view_category($category_id, $user_id = null) {
        $allowed_categories = $this->get_allowed_categories_for_user($user_id, 'view');
        
        if ($allowed_categories === 'all') {
            return true;
        }
        
        return in_array($category_id, $allowed_categories);
    }

    /**
     * Enforce edit permissions on specific posts (edit/delete/publish)
     */
    public function enforce_post_caps($caps, $cap, $user_id, $args) {
        $checked_caps = array('edit_post', 'delete_post', 'publish_post');
        if (!in_array($cap, $checked_caps, true)) {
            return $caps;
        }
        $post_id = isset($args[0]) ? (int)$args[0] : 0;
        if (!$post_id) {
            return $caps;
        }
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'post') {
            return $caps;
        }

        // Bài viết thuộc các category nào?
        $cats = wp_get_post_categories($post_id);
        if (empty($cats)) {
            return $caps; // Không ràng buộc nếu chưa có category
        }
        $allowed = $this->get_allowed_categories_for_user($user_id, 'edit');
        if ($allowed === 'all') {
            return $caps;
        }
        $allowed = is_array($allowed) ? $allowed : array();
        $can = false;
        foreach ($cats as $cid) {
            if (in_array((int)$cid, $allowed, true)) { $can = true; break; }
        }
        if (!$can) {
            return array('do_not_allow');
        }
        return $caps;
    }

    /**
     * Enforce create permission by denying edit_posts if user has no creatable categories
     */
    public function enforce_user_caps($allcaps, $caps, $args, $user) {
        $requested = isset($args[0]) ? $args[0] : '';
        if ($requested !== 'edit_posts') {
            return $allcaps;
        }
        // Chỉ áp dụng cho post type bài viết chính
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $post_type = $screen && !empty($screen->post_type) ? $screen->post_type : 'post';
        if ($post_type !== 'post') {
            return $allcaps;
        }
        $allowed = $this->get_allowed_categories_for_user($user->ID, 'create');
        if ($allowed !== 'all' && empty($allowed)) {
            $allcaps['edit_posts'] = false;
        }
        return $allcaps;
    }

    /**
     * Khi tạo user mới: copy phân quyền theo Role sang user để tiện chỉnh sửa
     */
    public function assign_defaults_on_user_register($user_id) {
        $user = get_userdata($user_id);
        if (!$user) return;
        global $wpdb; $table = $wpdb->prefix . 'rchg_mu_permissions';
        // Nếu user đã có cấu hình thì bỏ qua
        $exists = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(1) FROM {$table} WHERE permission_type='user' AND user_id=%d", $user_id));
        if ($exists > 0) return;
        if (empty($user->roles)) return;
        // Lấy theo role
        $rows = $wpdb->get_results(
            "SELECT role_name, category_id, can_view, can_edit, can_create FROM {$table} WHERE permission_type='role'",
            ARRAY_A
        );
        if (empty($rows)) return;
        foreach ($rows as $r) {
            if (!in_array($r['role_name'], (array)$user->roles, true)) continue;
            $wpdb->insert($table, array(
                'permission_type' => 'user',
                'role_name' => null,
                'user_id' => $user_id,
                'category_id' => (int)$r['category_id'],
                'can_view' => (int)$r['can_view'],
                'can_edit' => (int)$r['can_edit'],
                'can_create' => (int)$r['can_create']
            ), array('%s','%s','%d','%d','%d','%d','%d'));
        }
    }
}

