<?php
/**
 * Plugin Name: Role Category Manager
 * Plugin URI: https://github.com/rchg2025/Role-Category-Manager
 * Description: Quản lý quyền xem các chuyên mục (categories) dựa trên vai trò (role) của người dùng. Plugin này cho phép ẩn các bài viết không thuộc chuyên mục được phép và ngăn chặn truy cập trực tiếp vào bài viết không có quyền.
 * Version: 1.0.0
 * Author: rchg2025
 * Text Domain: role-category-manager
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Ngăn chặn truy cập trực tiếp
if (!defined('ABSPATH')) {
    exit;
}

// Định nghĩa các hằng số
define('RCM_VERSION', '1.0.0');
define('RCM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RCM_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Class Role_Category_Manager
 * Quản lý plugin chính
 */
class Role_Category_Manager {
    
    /**
     * Instance duy nhất của class
     */
    private static $instance = null;
    
    /**
     * Tên bảng trong database
     */
    private $table_name;
    
    /**
     * Lấy instance của class (Singleton pattern)
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'role_category_permissions';
        
        // Hooks khi kích hoạt/vô hiệu hóa plugin
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Khởi tạo các hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('pre_get_posts', array($this, 'filter_posts_by_category'));
        add_action('template_redirect', array($this, 'check_single_post_access'));
        
        // AJAX handlers
        add_action('wp_ajax_rcm_save_permissions', array($this, 'ajax_save_permissions'));
        add_action('wp_ajax_rcm_get_permissions', array($this, 'ajax_get_permissions'));
    }
    
    /**
     * Kích hoạt plugin - tạo bảng database
     */
    public function activate() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            role_name varchar(50) NOT NULL,
            category_id bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY role_category (role_name, category_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Thêm phiên bản plugin vào options
        add_option('rcm_version', RCM_VERSION);
    }
    
    /**
     * Vô hiệu hóa plugin
     */
    public function deactivate() {
        // Có thể thêm logic cleanup nếu cần
    }
    
    /**
     * Thêm menu vào admin
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Role Category Manager', 'role-category-manager'),
            __('Category Permissions', 'role-category-manager'),
            'manage_options',
            'role-category-manager',
            array($this, 'render_admin_page'),
            'dashicons-admin-network',
            30
        );
    }
    
    /**
     * Tải CSS và JS cho admin
     */
    public function enqueue_admin_assets($hook) {
        // Chỉ tải trên trang của plugin
        if ('toplevel_page_role-category-manager' !== $hook) {
            return;
        }
        
        wp_enqueue_style(
            'rcm-admin-style',
            RCM_PLUGIN_URL . 'assets/css/admin-style.css',
            array(),
            RCM_VERSION
        );
        
        wp_enqueue_script(
            'rcm-admin-script',
            RCM_PLUGIN_URL . 'assets/js/admin-script.js',
            array('jquery'),
            RCM_VERSION,
            true
        );
        
        // Localize script cho AJAX
        wp_localize_script('rcm-admin-script', 'rcmAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rcm_nonce'),
            'strings' => array(
                'saved' => __('Đã lưu thành công!', 'role-category-manager'),
                'error' => __('Có lỗi xảy ra. Vui lòng thử lại.', 'role-category-manager'),
                'loading' => __('Đang tải...', 'role-category-manager'),
                'unsaved' => __('Bạn có thay đổi chưa được lưu. Bạn có chắc muốn rời khỏi trang?', 'role-category-manager'),
            )
        ));
    }
    
    /**
     * Render trang admin
     */
    public function render_admin_page() {
        // Kiểm tra quyền
        if (!current_user_can('manage_options')) {
            wp_die(__('Bạn không có quyền truy cập trang này.', 'role-category-manager'));
        }
        
        // Lấy danh sách roles
        global $wp_roles;
        $all_roles = $wp_roles->roles;
        
        // Lấy danh sách categories
        $categories = get_categories(array(
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        
        // Include template
        include RCM_PLUGIN_DIR . 'templates/admin-page.php';
    }
    
    /**
     * Lấy quyền đã lưu cho một role
     */
    public function get_role_permissions($role_name) {
        global $wpdb;
        
        $results = $wpdb->get_col($wpdb->prepare(
            "SELECT category_id FROM {$this->table_name} WHERE role_name = %s",
            $role_name
        ));
        
        return array_map('intval', $results);
    }
    
    /**
     * Lưu quyền cho một role
     */
    public function save_role_permissions($role_name, $category_ids) {
        global $wpdb;
        
        // Xóa quyền cũ
        $wpdb->delete(
            $this->table_name,
            array('role_name' => $role_name),
            array('%s')
        );
        
        // Thêm quyền mới
        if (!empty($category_ids)) {
            foreach ($category_ids as $category_id) {
                $wpdb->insert(
                    $this->table_name,
                    array(
                        'role_name' => $role_name,
                        'category_id' => intval($category_id)
                    ),
                    array('%s', '%d')
                );
            }
        }
        
        return true;
    }
    
    /**
     * AJAX: Lưu permissions
     */
    public function ajax_save_permissions() {
        check_ajax_referer('rcm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $role_name = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : '';
        $categories = isset($_POST['categories']) ? array_map('intval', $_POST['categories']) : array();
        
        if (empty($role_name)) {
            wp_send_json_error(array('message' => 'Invalid role'));
            return;
        }
        
        $result = $this->save_role_permissions($role_name, $categories);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Permissions saved successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to save permissions'));
        }
    }
    
    /**
     * AJAX: Lấy permissions
     */
    public function ajax_get_permissions() {
        check_ajax_referer('rcm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $role_name = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : '';
        
        if (empty($role_name)) {
            wp_send_json_error(array('message' => 'Invalid role'));
            return;
        }
        
        $permissions = $this->get_role_permissions($role_name);
        
        wp_send_json_success(array('categories' => $permissions));
    }
    
    /**
     * Lọc posts theo category permissions
     */
    public function filter_posts_by_category($query) {
        // Chỉ áp dụng cho main query và không phải admin
        if (is_admin() || !$query->is_main_query()) {
            return;
        }
        
        // Chỉ áp dụng cho các query liên quan đến posts
        if (!$query->is_home() && !$query->is_archive() && !$query->is_search()) {
            return;
        }
        
        // Admin và editor có quyền xem tất cả
        if (current_user_can('manage_options') || current_user_can('edit_others_posts')) {
            return;
        }
        
        // Lấy categories được phép của user hiện tại
        $allowed_categories = $this->get_allowed_categories_for_current_user();
        
        // Nếu trả về 'all', không áp dụng lọc (cho guest users)
        if (in_array('all', $allowed_categories)) {
            return;
        }
        
        // Nếu không có category nào được phép, ẩn tất cả posts
        if (empty($allowed_categories)) {
            $query->set('post__in', array(0));
            return;
        }
        
        // Lọc theo categories được phép
        $tax_query = $query->get('tax_query') ?: array();
        $tax_query[] = array(
            'taxonomy' => 'category',
            'field' => 'term_id',
            'terms' => $allowed_categories,
            'operator' => 'IN'
        );
        $query->set('tax_query', $tax_query);
    }
    
    /**
     * Kiểm tra quyền truy cập single post
     */
    public function check_single_post_access() {
        // Chỉ áp dụng cho single post
        if (!is_single()) {
            return;
        }
        
        // Admin và editor có quyền xem tất cả
        if (current_user_can('manage_options') || current_user_can('edit_others_posts')) {
            return;
        }
        
        global $post;
        
        if (!$post) {
            return;
        }
        
        // Lấy categories của post
        $post_categories = wp_get_post_categories($post->ID);
        
        if (empty($post_categories)) {
            return;
        }
        
        // Lấy categories được phép của user
        $allowed_categories = $this->get_allowed_categories_for_current_user();
        
        // Nếu trả về 'all', cho phép truy cập (cho guest users)
        if (in_array('all', $allowed_categories)) {
            return;
        }
        
        // Kiểm tra xem post có thuộc category được phép không
        $has_permission = false;
        foreach ($post_categories as $cat_id) {
            if (in_array($cat_id, $allowed_categories)) {
                $has_permission = true;
                break;
            }
        }
        
        // Nếu không có quyền, chuyển hướng về trang chủ
        if (!$has_permission) {
            // Filter cho phép tùy chỉnh URL chuyển hướng
            $redirect_url = apply_filters('rcm_access_denied_redirect', home_url(), $post->ID);
            
            // Action hook trước khi chuyển hướng
            do_action('rcm_access_denied', $post->ID, get_current_user_id());
            
            wp_redirect($redirect_url);
            exit;
        }
    }
    
    /**
     * Lấy danh sách categories được phép cho user hiện tại
     */
    private function get_allowed_categories_for_current_user() {
        // Nếu user chưa đăng nhập, cho phép xem tất cả (có thể thay đổi theo nhu cầu)
        if (!is_user_logged_in()) {
            // Filter cho phép tùy chỉnh hành vi với user chưa đăng nhập
            $allow_all_for_guests = apply_filters('rcm_allow_all_categories_for_guests', true);
            
            if ($allow_all_for_guests) {
                // Trả về mảng rỗng để không áp dụng lọc
                return array('all');
            } else {
                // Trả về mảng rỗng để ẩn tất cả
                return array();
            }
        }
        
        $user = wp_get_current_user();
        $user_roles = $user->roles;
        
        $allowed_categories = array();
        
        // Lấy tất cả categories được phép từ tất cả roles của user
        foreach ($user_roles as $role) {
            $role_categories = $this->get_role_permissions($role);
            $allowed_categories = array_merge($allowed_categories, $role_categories);
        }
        
        // Loại bỏ duplicate
        $allowed_categories = array_unique($allowed_categories);
        
        return $allowed_categories;
    }
}

// Khởi tạo plugin
function role_category_manager_init() {
    return Role_Category_Manager::get_instance();
}

// Chạy plugin
role_category_manager_init();
