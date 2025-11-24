<?php
/**
 * Class quản lý Admin Panel - Version 2.0
 * Hỗ trợ phân quyền theo Role và User
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
            'error' => __('Có lỗi xảy ra!', 'role-category-manager'),
            'searching' => __('Đang tìm...', 'role-category-manager'),
            'no_results' => __('Không tìm thấy user', 'role-category-manager')
        ));
    }
    
    /**
     * Render trang admin với tabs
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
        
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'role';
        
        ?>
        <div class="wrap rchg_mu_wrap">
            <h1 class="wp-heading-inline">
                <span class="dashicons dashicons-admin-users"></span>
                <?php echo esc_html__('Phân Quyền Xem Chuyên Mục', 'role-category-manager'); ?>
            </h1>
            
            <p class="description">
                <?php echo esc_html__('Quản lý quyền xem, sửa và tạo bài viết theo chuyên mục', 'role-category-manager'); ?>
            </p>
            
            <hr class="wp-header-end">
            
            <!-- Tabs Navigation -->
            <nav class="nav-tab-wrapper wp-clearfix">
                <a href="?page=rchg-mu-permissions&tab=role" 
                   class="nav-tab <?php echo $active_tab === 'role' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-groups"></span>
                    <?php _e('Theo Vai Trò', 'role-category-manager'); ?>
                </a>
                <a href="?page=rchg-mu-permissions&tab=user" 
                   class="nav-tab <?php echo $active_tab === 'user' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-admin-users"></span>
                    <?php _e('Theo User', 'role-category-manager'); ?>
                </a>
            </nav>
            
            <div class="rchg_mu_tab_content">
                <?php if ($active_tab === 'role') : ?>
                    <?php $this->render_role_tab($roles, $categories); ?>
                <?php else : ?>
                    <?php $this->render_user_tab($categories); ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render tab phân quyền theo Role
     */
    private function render_role_tab($roles, $categories) {
        ?>
        <div class="rchg_mu_tab_panel">
            <div class="rchg_mu_card">
                <div class="rchg_mu_card_body">
                    <form id="rchg_mu_role_form" class="rchg_mu_form">
                        <?php wp_nonce_field('rchg_mu_save_permissions', 'rchg_mu_nonce'); ?>
                        <input type="hidden" name="permission_type" value="role">
                        
                        <!-- Select Role -->
                        <div class="rchg_mu_form_row">
                            <div class="rchg_mu_form_group">
                                <label for="rchg_mu_role_select" class="rchg_mu_label">
                                    <span class="dashicons dashicons-groups"></span>
                                    <?php _e('Chọn Vai Trò', 'role-category-manager'); ?>
                                    <span class="required">*</span>
                                </label>
                                <select id="rchg_mu_role_select" name="role_name" class="rchg_mu_select" required>
                                    <option value=""><?php _e('-- Chọn vai trò --', 'role-category-manager'); ?></option>
                                    <?php foreach ($roles as $role_key => $role_name) : ?>
                                        <option value="<?php echo esc_attr($role_key); ?>">
                                            <?php echo esc_html($role_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">
                                    <?php _e('Chọn vai trò người dùng để phân quyền', 'role-category-manager'); ?>
                                </p>
                            </div>
                        </div>
                        
                        <!-- Categories Selection -->
                        <div id="rchg_mu_categories_section" class="rchg_mu_section" style="display: none;">
                            <h3 class="rchg_mu_section_title">
                                <span class="dashicons dashicons-category"></span>
                                <?php _e('Chọn Chuyên Mục', 'role-category-manager'); ?>
                            </h3>
                            
                            <?php $this->render_categories_grid($categories); ?>
                            
                            <!-- Permission Types -->
                            <div class="rchg_mu_permissions_types">
                                <h4><?php _e('Loại Quyền', 'role-category-manager'); ?></h4>
                                <div class="rchg_mu_checkboxes">
                                    <label class="rchg_mu_checkbox_label">
                                        <input type="checkbox" name="can_view" value="1" checked class="rchg_mu_permission_checkbox">
                                        <span class="dashicons dashicons-visibility"></span>
                                        <?php _e('Xem bài viết', 'role-category-manager'); ?>
                                    </label>
                                    <label class="rchg_mu_checkbox_label">
                                        <input type="checkbox" name="can_edit" value="1" class="rchg_mu_permission_checkbox">
                                        <span class="dashicons dashicons-edit"></span>
                                        <?php _e('Sửa bài viết', 'role-category-manager'); ?>
                                    </label>
                                    <label class="rchg_mu_checkbox_label">
                                        <input type="checkbox" name="can_create" value="1" class="rchg_mu_permission_checkbox">
                                        <span class="dashicons dashicons-plus-alt"></span>
                                        <?php _e('Tạo bài mới', 'role-category-manager'); ?>
                                    </label>
                                </div>
                                <p class="description">
                                    <?php _e('Chọn các quyền mà vai trò này có thể thực hiện', 'role-category-manager'); ?>
                                </p>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="rchg_mu_form_actions">
                                <button type="button" id="rchg_mu_select_all_cats" class="button">
                                    <?php _e('Chọn tất cả', 'role-category-manager'); ?>
                                </button>
                                <button type="button" id="rchg_mu_deselect_all_cats" class="button">
                                    <?php _e('Bỏ chọn tất cả', 'role-category-manager'); ?>
                                </button>
                                <button type="submit" class="button button-primary button-large">
                                    <span class="dashicons dashicons-saved"></span>
                                    <?php _e('Lưu Phân Quyền', 'role-category-manager'); ?>
                                </button>
                            </div>
                        </div>
                        
                        <div id="rchg_mu_message" class="rchg_mu_message" style="display: none;"></div>
                    </form>
                </div>
            </div>
            
            <?php $this->render_help_box(); ?>
        </div>
        <?php
    }
    
    /**
     * Render tab phân quyền theo User
     */
    private function render_user_tab($categories) {
        ?>
        <div class="rchg_mu_tab_panel">
            <div class="rchg_mu_card">
                <div class="rchg_mu_card_body">
                    <form id="rchg_mu_user_form" class="rchg_mu_form">
                        <?php wp_nonce_field('rchg_mu_save_permissions', 'rchg_mu_nonce'); ?>
                        <input type="hidden" name="permission_type" value="user">
                        <input type="hidden" id="rchg_mu_selected_user_id" name="user_id" value="">
                        
                        <!-- Search User -->
                        <div class="rchg_mu_form_row">
                            <div class="rchg_mu_form_group">
                                <label for="rchg_mu_user_search" class="rchg_mu_label">
                                    <span class="dashicons dashicons-search"></span>
                                    <?php _e('Tìm User', 'role-category-manager'); ?>
                                    <span class="required">*</span>
                                </label>
                                <input type="text" 
                                       id="rchg_mu_user_search" 
                                       class="rchg_mu_input" 
                                       placeholder="<?php esc_attr_e('Nhập tên hoặc email...', 'role-category-manager'); ?>"
                                       autocomplete="off">
                                <div id="rchg_mu_user_results" class="rchg_mu_search_results"></div>
                                <p class="description">
                                    <?php _e('Tìm kiếm user theo tên hoặc email', 'role-category-manager'); ?>
                                </p>
                            </div>
                        </div>
                        
                        <!-- Selected User Display -->
                        <div id="rchg_mu_selected_user" class="rchg_mu_selected_user" style="display: none;">
                            <div class="rchg_mu_user_card">
                                <span class="dashicons dashicons-admin-users"></span>
                                <div class="rchg_mu_user_info">
                                    <strong id="rchg_mu_user_name"></strong>
                                    <span id="rchg_mu_user_email"></span>
                                    <span id="rchg_mu_user_role"></span>
                                </div>
                                <button type="button" id="rchg_mu_clear_user" class="button button-small">
                                    <?php _e('Xóa', 'role-category-manager'); ?>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Categories Selection -->
                        <div id="rchg_mu_user_categories_section" class="rchg_mu_section" style="display: none;">
                            <h3 class="rchg_mu_section_title">
                                <span class="dashicons dashicons-category"></span>
                                <?php _e('Chọn Chuyên Mục', 'role-category-manager'); ?>
                            </h3>
                            
                            <?php $this->render_categories_grid($categories); ?>
                            
                            <!-- Permission Types -->
                            <div class="rchg_mu_permissions_types">
                                <h4><?php _e('Loại Quyền', 'role-category-manager'); ?></h4>
                                <div class="rchg_mu_checkboxes">
                                    <label class="rchg_mu_checkbox_label">
                                        <input type="checkbox" name="can_view" value="1" checked class="rchg_mu_permission_checkbox">
                                        <span class="dashicons dashicons-visibility"></span>
                                        <?php _e('Xem bài viết', 'role-category-manager'); ?>
                                    </label>
                                    <label class="rchg_mu_checkbox_label">
                                        <input type="checkbox" name="can_edit" value="1" class="rchg_mu_permission_checkbox">
                                        <span class="dashicons dashicons-edit"></span>
                                        <?php _e('Sửa bài viết', 'role-category-manager'); ?>
                                    </label>
                                    <label class="rchg_mu_checkbox_label">
                                        <input type="checkbox" name="can_create" value="1" class="rchg_mu_permission_checkbox">
                                        <span class="dashicons dashicons-plus-alt"></span>
                                        <?php _e('Tạo bài mới', 'role-category-manager'); ?>
                                    </label>
                                </div>
                                <p class="description">
                                    <?php _e('Chọn các quyền mà user này có thể thực hiện', 'role-category-manager'); ?>
                                </p>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="rchg_mu_form_actions">
                                <button type="button" class="button rchg_mu_select_all_cats">
                                    <?php _e('Chọn tất cả', 'role-category-manager'); ?>
                                </button>
                                <button type="button" class="button rchg_mu_deselect_all_cats">
                                    <?php _e('Bỏ chọn tất cả', 'role-category-manager'); ?>
                                </button>
                                <button type="submit" class="button button-primary button-large">
                                    <span class="dashicons dashicons-saved"></span>
                                    <?php _e('Lưu Phân Quyền', 'role-category-manager'); ?>
                                </button>
                            </div>
                        </div>
                        
                        <div id="rchg_mu_user_message" class="rchg_mu_message" style="display: none;"></div>
                    </form>
                </div>
            </div>
            
            <?php $this->render_help_box('user'); ?>
        </div>
        <?php
    }
    
    /**
     * Render categories grid
     */
    private function render_categories_grid($categories) {
        if (empty($categories)) {
            echo '<div class="notice notice-warning inline"><p>';
            _e('Không có chuyên mục nào. Vui lòng tạo chuyên mục trong Posts → Categories', 'role-category-manager');
            echo '</p></div>';
            return;
        }
        ?>
        <div class="rchg_mu_categories_grid">
            <?php foreach ($categories as $category) : ?>
                <div class="rchg_mu_category_item">
                    <label class="rchg_mu_checkbox_label">
                        <input type="checkbox" 
                               name="categories[]" 
                               value="<?php echo esc_attr($category->term_id); ?>"
                               class="rchg_mu_category_checkbox">
                        <span class="rchg_mu_checkbox_text">
                            <?php echo esc_html($category->name); ?>
                            <small class="count">(<?php echo esc_html($category->count); ?> bài)</small>
                        </span>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Render help box
     */
    private function render_help_box($type = 'role') {
        ?>
        <div class="rchg_mu_card rchg_mu_help_card">
            <div class="rchg_mu_card_header">
                <h3>
                    <span class="dashicons dashicons-info"></span>
                    <?php _e('Hướng Dẫn', 'role-category-manager'); ?>
                </h3>
            </div>
            <div class="rchg_mu_card_body">
                <?php if ($type === 'role') : ?>
                    <ol class="rchg_mu_help_list">
                        <li><?php _e('Chọn vai trò người dùng từ dropdown', 'role-category-manager'); ?></li>
                        <li><?php _e('Chọn các chuyên mục mà vai trò đó được phép truy cập', 'role-category-manager'); ?></li>
                        <li><?php _e('Chọn loại quyền: Xem, Sửa hoặc Tạo bài', 'role-category-manager'); ?></li>
                        <li><?php _e('Nhấn "Lưu Phân Quyền" để áp dụng', 'role-category-manager'); ?></li>
                    </ol>
                <?php else : ?>
                    <ol class="rchg_mu_help_list">
                        <li><?php _e('Tìm user bằng cách nhập tên hoặc email', 'role-category-manager'); ?></li>
                        <li><?php _e('Chọn user từ kết quả tìm kiếm', 'role-category-manager'); ?></li>
                        <li><?php _e('Chọn các chuyên mục mà user đó được phép truy cập', 'role-category-manager'); ?></li>
                        <li><?php _e('Chọn loại quyền: Xem, Sửa hoặc Tạo bài', 'role-category-manager'); ?></li>
                        <li><?php _e('Nhấn "Lưu Phân Quyền" để áp dụng', 'role-category-manager'); ?></li>
                    </ol>
                <?php endif; ?>
                
                <div class="rchg_mu_help_note">
                    <p>
                        <strong><?php _e('Lưu ý:', 'role-category-manager'); ?></strong>
                        <?php _e('Phân quyền theo User sẽ được ưu tiên hơn phân quyền theo Role.', 'role-category-manager'); ?>
                    </p>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX: Lưu phân quyền
     */
    public function save_permissions() {
        check_ajax_referer('rchg_mu_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Bạn không có quyền thực hiện thao tác này', 'role-category-manager')));
        }
        
        $permission_type = isset($_POST['permission_type']) ? sanitize_text_field($_POST['permission_type']) : 'role';
        $categories = isset($_POST['categories']) ? array_map('intval', $_POST['categories']) : array();
        $can_view = isset($_POST['can_view']) ? 1 : 0;
        $can_edit = isset($_POST['can_edit']) ? 1 : 0;
        $can_create = isset($_POST['can_create']) ? 1 : 0;
        
        if (empty($categories)) {
            wp_send_json_error(array('message' => __('Vui lòng chọn ít nhất một chuyên mục', 'role-category-manager')));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'rchg_mu_permissions';
        
        if ($permission_type === 'role') {
            $role_name = isset($_POST['role_name']) ? sanitize_text_field($_POST['role_name']) : '';
            
            if (empty($role_name)) {
                wp_send_json_error(array('message' => __('Vui lòng chọn vai trò', 'role-category-manager')));
            }
            
            // Xóa phân quyền cũ của role này
            $wpdb->delete(
                $table_name,
                array(
                    'permission_type' => 'role',
                    'role_name' => $role_name
                ),
                array('%s', '%s')
            );
            
            // Thêm phân quyền mới
            foreach ($categories as $category_id) {
                $wpdb->insert(
                    $table_name,
                    array(
                        'permission_type' => 'role',
                        'role_name' => $role_name,
                        'user_id' => null,
                        'category_id' => $category_id,
                        'can_view' => $can_view,
                        'can_edit' => $can_edit,
                        'can_create' => $can_create
                    ),
                    array('%s', '%s', '%d', '%d', '%d', '%d', '%d')
                );
            }
            
            wp_send_json_success(array(
                'message' => sprintf(
                    __('Đã lưu phân quyền cho vai trò %s với %d chuyên mục', 'role-category-manager'),
                    $role_name,
                    count($categories)
                )
            ));
            
        } else {
            // User permissions
            $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
            
            if (empty($user_id)) {
                wp_send_json_error(array('message' => __('Vui lòng chọn user', 'role-category-manager')));
            }
            
            $user = get_user_by('id', $user_id);
            if (!$user) {
                wp_send_json_error(array('message' => __('User không tồn tại', 'role-category-manager')));
            }
            
            // Xóa phân quyền cũ của user này
            $wpdb->delete(
                $table_name,
                array(
                    'permission_type' => 'user',
                    'user_id' => $user_id
                ),
                array('%s', '%d')
            );
            
            // Thêm phân quyền mới
            foreach ($categories as $category_id) {
                $wpdb->insert(
                    $table_name,
                    array(
                        'permission_type' => 'user',
                        'role_name' => null,
                        'user_id' => $user_id,
                        'category_id' => $category_id,
                        'can_view' => $can_view,
                        'can_edit' => $can_edit,
                        'can_create' => $can_create
                    ),
                    array('%s', '%s', '%d', '%d', '%d', '%d', '%d')
                );
            }
            
            wp_send_json_success(array(
                'message' => sprintf(
                    __('Đã lưu phân quyền cho user %s với %d chuyên mục', 'role-category-manager'),
                    $user->display_name,
                    count($categories)
                )
            ));
        }
    }
    
    /**
     * AJAX: Lấy phân quyền hiện tại
     */
    public function get_permissions() {
        check_ajax_referer('rchg_mu_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Bạn không có quyền thực hiện thao tác này', 'role-category-manager')));
        }
        
        $permission_type = isset($_POST['permission_type']) ? sanitize_text_field($_POST['permission_type']) : 'role';
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'rchg_mu_permissions';
        
        if ($permission_type === 'role') {
            $role_name = isset($_POST['role_name']) ? sanitize_text_field($_POST['role_name']) : '';
            
            if (empty($role_name)) {
                wp_send_json_error(array('message' => __('Vai trò không hợp lệ', 'role-category-manager')));
            }
            
            $permissions = $wpdb->get_results($wpdb->prepare(
                "SELECT category_id, can_view, can_edit, can_create 
                 FROM {$table_name} 
                 WHERE permission_type = 'role' AND role_name = %s",
                $role_name
            ), ARRAY_A);
            
        } else {
            $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
            
            if (empty($user_id)) {
                wp_send_json_error(array('message' => __('User không hợp lệ', 'role-category-manager')));
            }
            
            $permissions = $wpdb->get_results($wpdb->prepare(
                "SELECT category_id, can_view, can_edit, can_create 
                 FROM {$table_name} 
                 WHERE permission_type = 'user' AND user_id = %d",
                $user_id
            ), ARRAY_A);
        }
        
        wp_send_json_success(array('permissions' => $permissions));
    }
    
    /**
     * AJAX: Tìm kiếm user
     */
    public function search_users() {
        check_ajax_referer('rchg_mu_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Bạn không có quyền thực hiện thao tác này', 'role-category-manager')));
        }
        
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        
        if (strlen($search) < 2) {
            wp_send_json_error(array('message' => __('Vui lòng nhập ít nhất 2 ký tự', 'role-category-manager')));
        }
        
        $args = array(
            'search' => '*' . $search . '*',
            'search_columns' => array('user_login', 'user_email', 'display_name'),
            'number' => 10,
            'orderby' => 'display_name',
            'order' => 'ASC'
        );
        
        $user_query = new WP_User_Query($args);
        $users = $user_query->get_results();
        
        if (empty($users)) {
            wp_send_json_success(array('users' => array()));
        }
        
        $results = array();
        foreach ($users as $user) {
            $user_roles = $user->roles;
            $role_name = !empty($user_roles) ? $user_roles[0] : 'none';
            
            global $wp_roles;
            $all_roles = $wp_roles->get_names();
            $role_display = isset($all_roles[$role_name]) ? $all_roles[$role_name] : ucfirst($role_name);
            
            $results[] = array(
                'id' => $user->ID,
                'name' => $user->display_name,
                'email' => $user->user_email,
                'role' => $role_display
            );
        }
        
        wp_send_json_success(array('users' => $results));
    }
}

// Khởi tạo class
RCHG_MU_Admin::get_instance();
