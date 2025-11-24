<?php
/**
 * Class quản lý Admin Panel
 */

if (!defined('ABSPATH')) {
    exit;
}

class RCHG_MU_Admin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Thêm menu vào admin
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Enqueue scripts và styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Xử lý AJAX
        add_action('wp_ajax_rchg_mu_save_permissions', array($this, 'save_permissions'));
        add_action('wp_ajax_rchg_mu_get_permissions', array($this, 'get_permissions'));
        add_action('wp_ajax_rchg_mu_search_users', array($this, 'search_users'));
    }
    
    /**
     * Thêm menu vào admin panel
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Phân Quyền Chuyên Mục', 'role-category-manager'),
            __('Phân Quyền', 'role-category-manager'),
            'manage_options',
            'rchg-mu-permissions',
            array($this, 'render_admin_page'),
            'dashicons-admin-users',
            30
        );
    }
    
    /**
     * Enqueue CSS và JS cho admin
     */
    public function enqueue_admin_assets($hook) {
        if ('toplevel_page_rchg-mu-permissions' !== $hook) {
            return;
        }
        
        wp_enqueue_style(
            'rchg-mu-admin-style',
            RCHG_MU_PLUGIN_URL . 'assets/css/admin-style.css',
            array(),
            RCHG_MU_VERSION
        );
        
        wp_enqueue_script(
            'rchg-mu-admin-script',
            RCHG_MU_PLUGIN_URL . 'assets/js/admin-script.js',
            array('jquery'),
            RCHG_MU_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('rchg-mu-admin-script', 'rchgMuAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rchg_mu_nonce'),
            'saving' => __('Đang lưu...', 'role-category-manager'),
            'saved' => __('Đã lưu thành công!', 'role-category-manager'),
            'error' => __('Có lỗi xảy ra!', 'role-category-manager')
        ));
    }
    
    /**
     * Render trang admin
     */
    public function render_admin_page() {
        global $wp_roles;
        
        // Lấy tất cả roles
        $roles = $wp_roles->get_names();
        
        // Lấy tất cả categories
        $categories = get_categories(array(
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        
        ?>
        <div class="rchg_wrap">
            <div class="rchg_header">
                <h1><?php echo esc_html__('Phân Quyền Xem Chuyên Mục', 'role-category-manager'); ?></h1>
                <p class="rchg_description">
                    <?php echo esc_html__('Quản lý quyền xem các chuyên mục bài viết theo từng vai trò người dùng', 'role-category-manager'); ?>
                </p>
            </div>
            
            <div class="rchg_mu_container">
                <div class="rchg_mu_card">
                    <div class="rchg_mu_card_header">
                        <h2><?php echo esc_html__('Cấu Hình Phân Quyền', 'role-category-manager'); ?></h2>
                    </div>
                    
                    <div class="rchg_mu_card_body">
                        <form id="rchg_mu_permissions_form">
                            <?php wp_nonce_field('rchg_mu_save_permissions', 'rchg_mu_nonce'); ?>
                            
                            <div class="rchg_mu_form_group">
                                <label for="rchg_mu_role_select" class="rchg_mu_label">
                                    <?php echo esc_html__('Chọn Vai Trò', 'role-category-manager'); ?>
                                </label>
                                <select id="rchg_mu_role_select" class="rchg_mu_select" name="role">
                                    <option value=""><?php echo esc_html__('-- Chọn vai trò --', 'role-category-manager'); ?></option>
                                    <?php foreach ($roles as $role_key => $role_name) : ?>
                                        <option value="<?php echo esc_attr($role_key); ?>">
                                            <?php echo esc_html($role_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div id="rchg_mu_categories_container" class="rchg_mu_categories_container" style="display: none;">
                                <h3 class="rchg_subtitle">
                                    <?php echo esc_html__('Chọn các chuyên mục được phép xem', 'role-category-manager'); ?>
                                </h3>
                                
                                <div class="rchg_categories_grid">
                                    <?php foreach ($categories as $category) : ?>
                                        <div class="rchg_category_item">
                                            <label class="rchg_checkbox_label">
                                                <input 
                                                    type="checkbox" 
                                                    name="categories[]" 
                                                    value="<?php echo esc_attr($category->term_id); ?>"
                                                    class="rchg_checkbox"
                                                    data-category-id="<?php echo esc_attr($category->term_id); ?>"
                                                >
                                                <span class="rchg_checkbox_text">
                                                    <?php echo esc_html($category->name); ?>
                                                    <small>(<?php echo esc_html($category->count); ?> bài viết)</small>
                                                </span>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="rchg_form_actions">
                                    <button type="button" id="rchg_mu_select_all" class="rchg_button rchg_button_secondary">
                                        <?php echo esc_html__('Chọn tất cả', 'role-category-manager'); ?>
                                    </button>
                                    <button type="button" id="rchg_mu_deselect_all" class="rchg_button rchg_button_secondary">
                                        <?php echo esc_html__('Bỏ chọn tất cả', 'role-category-manager'); ?>
                                    </button>
                                    <button type="submit" class="rchg_button rchg_button_primary">
                                        <?php echo esc_html__('Lưu Phân Quyền', 'role-category-manager'); ?>
                                    </button>
                                </div>
                            </div>
                            
                            <div id="rchg_mu_message" class="rchg_mu_message" style="display: none;"></div>
                        </form>
                    </div>
                </div>
                
                <div class="rchg_card">
                    <div class="rchg_card_header">
                        <h2><?php echo esc_html__('Hướng Dẫn Sử Dụng', 'role-category-manager'); ?></h2>
                    </div>
                    <div class="rchg_card_body">
                        <ol class="rchg_instructions">
                            <li><?php echo esc_html__('Chọn vai trò người dùng từ danh sách', 'role-category-manager'); ?></li>
                            <li><?php echo esc_html__('Đánh dấu các chuyên mục mà vai trò đó được phép xem', 'role-category-manager'); ?></li>
                            <li><?php echo esc_html__('Nhấn "Lưu Phân Quyền" để áp dụng thay đổi', 'role-category-manager'); ?></li>
                            <li><?php echo esc_html__('Người dùng thuộc vai trò đó sẽ chỉ thấy các bài viết trong chuyên mục được chọn', 'role-category-manager'); ?></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Lưu phân quyền (AJAX)
     */
    public function save_permissions() {
        check_ajax_referer('rchg_mu_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Bạn không có quyền thực hiện thao tác này', 'role-category-manager')));
        }
        
        $role = sanitize_text_field($_POST['role']);
        $categories = isset($_POST['categories']) ? array_map('intval', $_POST['categories']) : array();
        
        if (empty($role)) {
            wp_send_json_error(array('message' => __('Vui lòng chọn vai trò', 'role-category-manager')));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'rchg_mu_permissions';
        
        // Xóa các phân quyền cũ của role này
        $wpdb->delete($table_name, array('role_name' => $role));
        
        // Thêm phân quyền mới
        if (!empty($categories)) {
            foreach ($categories as $category_id) {
                $wpdb->insert(
                    $table_name,
                    array(
                        'role_name' => $role,
                        'category_id' => $category_id,
                        'can_view' => 1
                    ),
                    array('%s', '%d', '%d')
                );
            }
        }
        
        wp_send_json_success(array('message' => __('Đã lưu phân quyền thành công!', 'role-category-manager')));
    }
    
    /**
     * Lấy phân quyền (AJAX)
     */
    public function get_permissions() {
        check_ajax_referer('rchg_mu_nonce', 'nonce');
        
        $role = sanitize_text_field($_POST['role']);
        
        if (empty($role)) {
            wp_send_json_error(array('message' => __('Vui lòng chọn vai trò', 'role-category-manager')));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'rchg_mu_permissions';
        
        $categories = $wpdb->get_col($wpdb->prepare(
            "SELECT category_id FROM {$table_name} WHERE role_name = %s AND can_view = 1",
            $role
        ));
        
        wp_send_json_success(array('categories' => array_map('intval', $categories)));
    }
}




