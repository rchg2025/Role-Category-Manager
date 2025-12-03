<?php
if (!defined('ABSPATH')) exit;

class RCHG_MU_Admin {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // AJAX handlers
        add_action('wp_ajax_rchg_mu_save_permissions', array($this, 'ajax_save_permissions'));
        add_action('wp_ajax_rchg_mu_get_permissions', array($this, 'ajax_get_permissions'));
        add_action('wp_ajax_rchg_mu_search_users', array($this, 'ajax_search_users'));
        add_action('wp_ajax_rchg_mu_export_permissions', array($this, 'ajax_export_permissions'));
        add_action('wp_ajax_rchg_mu_import_permissions', array($this, 'ajax_import_permissions'));
        add_action('wp_ajax_rchg_mu_reset_user_permissions', array($this, 'ajax_reset_user_permissions'));
    }
    
    public function add_menu() {
        add_menu_page(
            'Phân Quyền Chuyên Mục',
            'Phân Quyền',
            'manage_options',
            'rchg-permissions',
            array($this, 'render_page'),
            'dashicons-admin-network',
            30
        );
    }
    
    public function enqueue_assets($hook) {
        if ('toplevel_page_rchg-permissions' !== $hook) return;
        
        wp_enqueue_style('rchg-admin', RCHG_MU_PLUGIN_URL . 'assets/css/admin-style.css', array(), RCHG_MU_VERSION);
        wp_enqueue_script('rchg-admin', RCHG_MU_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery'), RCHG_MU_VERSION, true);
        
        wp_localize_script('rchg-admin', 'rchgData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rchg_mu_nonce')
        ));
    }
    
    public function render_page() {
        if (!current_user_can('manage_options')) return;
        
        $roles = wp_roles()->roles;
        $categories = get_categories(array('hide_empty' => false));
        ?>
        <div class="wrap rchg-wrap">
            <h1><?php esc_html_e('Phân Quyền Chuyên Mục', 'role-category-manager'); ?></h1>
            
            <div class="rchg-tabs">
                <button class="rchg-tab active" data-tab="role"><?php esc_html_e('Theo Vai Trò', 'role-category-manager'); ?></button>
                <button class="rchg-tab" data-tab="user"><?php esc_html_e('Theo User', 'role-category-manager'); ?></button>
            </div>
            
            <!-- Tab: Role Permissions -->
            <div class="rchg-tab-content active" id="tab-role">
                <div class="rchg-select-box">
                    <label><?php esc_html_e('Chọn vai trò:', 'role-category-manager'); ?></label>
                    <select id="rchg-role-select">
                        <option value=""><?php esc_html_e('-- Chọn vai trò --', 'role-category-manager'); ?></option>
                        <?php foreach ($roles as $role_key => $role_data): ?>
                            <option value="<?php echo esc_attr($role_key); ?>">
                                <?php echo esc_html(translate_user_role($role_data['name'])); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="rchg-load-role" class="button"><?php esc_html_e('Tải Phân Quyền', 'role-category-manager'); ?></button>
                </div>
                
                <div id="rchg-role-permissions" style="display:none;">
                    <h3><?php esc_html_e('Phân quyền cho:', 'role-category-manager'); ?> <span id="rchg-role-name"></span></h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Chuyên mục', 'role-category-manager'); ?></th>
                                <th style="width:100px;"><?php esc_html_e('👁️ Xem', 'role-category-manager'); ?></th>
                                <th style="width:100px;"><?php esc_html_e('✏️ Sửa', 'role-category-manager'); ?></th>
                                <th style="width:100px;"><?php esc_html_e('➕ Tạo', 'role-category-manager'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="rchg-role-categories">
                            <?php foreach ($categories as $cat): ?>
                                <tr data-cat-id="<?php echo esc_attr($cat->term_id); ?>">
                                    <td><?php echo esc_html($cat->name); ?></td>
                                    <td><input type="checkbox" class="perm-view" value="1"></td>
                                    <td><input type="checkbox" class="perm-edit" value="1"></td>
                                    <td><input type="checkbox" class="perm-create" value="1"></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p class="submit">
                        <button type="button" id="rchg-save-role" class="button button-primary"><?php esc_html_e('Lưu Phân Quyền', 'role-category-manager'); ?></button>
                        <button type="button" id="rchg-export-role" class="button"><?php esc_html_e('Export JSON', 'role-category-manager'); ?></button>
                    </p>
                </div>
            </div>
            
            <!-- Tab: User Permissions -->
            <div class="rchg-tab-content" id="tab-user">
                <div class="rchg-select-box">
                    <label><?php esc_html_e('Tìm user:', 'role-category-manager'); ?></label>
                    <input type="text" id="rchg-user-search" placeholder="<?php esc_attr_e('Nhập tên hoặc email...', 'role-category-manager'); ?>" autocomplete="off">
                    <div id="rchg-user-results"></div>
                </div>
                
                <div id="rchg-user-permissions" style="display:none;">
                    <h3><?php esc_html_e('Phân quyền cho user:', 'role-category-manager'); ?> <span id="rchg-user-name"></span></h3>
                    <input type="hidden" id="rchg-selected-user-id">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Chuyên mục', 'role-category-manager'); ?></th>
                                <th style="width:100px;"><?php esc_html_e('👁️ Xem', 'role-category-manager'); ?></th>
                                <th style="width:100px;"><?php esc_html_e('✏️ Sửa', 'role-category-manager'); ?></th>
                                <th style="width:100px;"><?php esc_html_e('➕ Tạo', 'role-category-manager'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="rchg-user-categories">
                            <?php foreach ($categories as $cat): ?>
                                <tr data-cat-id="<?php echo esc_attr($cat->term_id); ?>">
                                    <td><?php echo esc_html($cat->name); ?></td>
                                    <td><input type="checkbox" class="perm-view" value="1"></td>
                                    <td><input type="checkbox" class="perm-edit" value="1"></td>
                                    <td><input type="checkbox" class="perm-create" value="1"></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p class="submit">
                        <button type="button" id="rchg-save-user" class="button button-primary"><?php esc_html_e('Lưu Phân Quyền', 'role-category-manager'); ?></button>
                        <button type="button" id="rchg-export-user" class="button"><?php esc_html_e('Export JSON', 'role-category-manager'); ?></button>
                        <button type="button" id="rchg-reset-user" class="button button-secondary"><?php esc_html_e('Reset User', 'role-category-manager'); ?></button>
                    </p>
                </div>
            </div>
            
            <!-- Tools Panel -->
            <div class="rchg-tools">
                <h3><?php esc_html_e('Công cụ', 'role-category-manager'); ?></h3>
                <p>
                    <button type="button" id="rchg-export-all" class="button"><?php esc_html_e('Export toàn bộ', 'role-category-manager'); ?></button>
                    <button type="button" id="rchg-import-json" class="button"><?php esc_html_e('Import từ file', 'role-category-manager'); ?></button>
                    <input type="file" id="rchg-import-file" accept=".json" style="display:none;">
                </p>
            </div>
        </div>
        <?php
    }
    
    // AJAX: Save permissions
    public function ajax_save_permissions() {
        check_ajax_referer('rchg_mu_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Không có quyền'));
        }
        
        global $wpdb;
        
        $type = isset($_POST['type']) ? sanitize_text_field(wp_unslash($_POST['type'])) : '';
        $role_name = isset($_POST['role_name']) ? sanitize_text_field(wp_unslash($_POST['role_name'])) : '';
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $permissions = isset($_POST['permissions']) ? wp_unslash($_POST['permissions']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
        
        // Delete existing permissions
        if ($type === 'role' && $role_name) {
            $wpdb->delete("{$wpdb->prefix}rchg_mu_permissions", array('permission_type' => 'role', 'role_name' => $role_name)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        } elseif ($type === 'user' && $user_id) {
            $wpdb->delete("{$wpdb->prefix}rchg_mu_permissions", array('permission_type' => 'user', 'user_id' => $user_id)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        }
        
        // Insert new permissions
        if (is_array($permissions)) {
            foreach ($permissions as $perm) {
                if (!is_array($perm)) continue;
                
                $data = array(
                    'permission_type' => sanitize_text_field($type),
                    'category_id' => isset($perm['category_id']) ? intval($perm['category_id']) : 0,
                    'can_view' => isset($perm['can_view']) ? 1 : 0,
                    'can_edit' => isset($perm['can_edit']) ? 1 : 0,
                    'can_create' => isset($perm['can_create']) ? 1 : 0,
                );
                
                if ($type === 'role' && $role_name) {
                    $data['role_name'] = sanitize_text_field($role_name);
                    $data['user_id'] = null;
                } elseif ($type === 'user' && $user_id) {
                    $data['user_id'] = intval($user_id);
                    $data['role_name'] = null;
                } else {
                    continue;
                }
                
                $wpdb->insert("{$wpdb->prefix}rchg_mu_permissions", $data); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            }
        }
        
        wp_send_json_success(array('message' => 'Đã lưu phân quyền'));
    }
    
    // AJAX: Get permissions
    public function ajax_get_permissions() {
        check_ajax_referer('rchg_mu_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Không có quyền'));
        }
        
        global $wpdb;
        
        $type = isset($_POST['type']) ? sanitize_text_field(wp_unslash($_POST['type'])) : '';
        $role_name = isset($_POST['role_name']) ? sanitize_text_field(wp_unslash($_POST['role_name'])) : '';
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if ($type === 'role' && $role_name) {
            $results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}rchg_mu_permissions WHERE permission_type = %s AND role_name = %s",
                    'role',
                    $role_name
                ),
                ARRAY_A
            );
        } elseif ($type === 'user' && $user_id) {
            $results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}rchg_mu_permissions WHERE permission_type = %s AND user_id = %d",
                    'user',
                    $user_id
                ),
                ARRAY_A
            );
        } else {
            $results = array();
        }
        
        wp_send_json_success(array('permissions' => $results));
    }
    
    // AJAX: Search users
    public function ajax_search_users() {
        check_ajax_referer('rchg_mu_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Không có quyền'));
        }
        
        $search = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
        
        $users = get_users(array(
            'search' => '*' . $search . '*',
            'search_columns' => array('user_login', 'user_email', 'display_name'),
            'number' => 10,
        ));
        
        $results = array();
        foreach ($users as $user) {
            $results[] = array(
                'id' => $user->ID,
                'name' => $user->display_name,
                'email' => $user->user_email,
                'login' => $user->user_login,
            );
        }
        
        wp_send_json_success(array('users' => $results));
    }
    
    // AJAX: Export permissions
    public function ajax_export_permissions() {
        check_ajax_referer('rchg_mu_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Không có quyền'));
        }
        
        global $wpdb;
        
        $type = isset($_POST['type']) ? sanitize_text_field(wp_unslash($_POST['type'])) : 'all';
        
        if ($type === 'all') {
            $data = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rchg_mu_permissions", ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        } else {
            $data = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                $wpdb->prepare("SELECT * FROM {$wpdb->prefix}rchg_mu_permissions WHERE permission_type = %s", $type),
                ARRAY_A
            );
        }
        
        wp_send_json_success(array('data' => $data));
    }
    
    // AJAX: Import permissions
    public function ajax_import_permissions() {
        check_ajax_referer('rchg_mu_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Không có quyền'));
        }
        
        $json_data = isset($_POST['json_data']) ? wp_unslash($_POST['json_data']) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
        $data = json_decode($json_data, true);
        
        if (!is_array($data)) {
            wp_send_json_error(array('message' => 'Dữ liệu JSON không hợp lệ'));
        }
        
        global $wpdb;
        
        $imported = 0;
        foreach ($data as $row) {
            $wpdb->replace("{$wpdb->prefix}rchg_mu_permissions", array( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                'permission_type' => sanitize_text_field($row['permission_type']),
                'role_name' => isset($row['role_name']) ? sanitize_text_field($row['role_name']) : '',
                'user_id' => isset($row['user_id']) ? intval($row['user_id']) : 0,
                'category_id' => intval($row['category_id']),
                'can_view' => intval($row['can_view']),
                'can_edit' => intval($row['can_edit']),
                'can_create' => intval($row['can_create']),
            ));
            $imported++;
        }
        
        wp_send_json_success(array('message' => sprintf('Đã import %d bản ghi', $imported)));
    }
    
    // AJAX: Reset user permissions
    public function ajax_reset_user_permissions() {
        check_ajax_referer('rchg_mu_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Không có quyền'));
        }
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        global $wpdb;
        
        $deleted = $wpdb->delete("{$wpdb->prefix}rchg_mu_permissions", array('permission_type' => 'user', 'user_id' => $user_id)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        
        wp_send_json_success(array('message' => sprintf('Đã xóa %d phân quyền của user', $deleted)));
    }
}
