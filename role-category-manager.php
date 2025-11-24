<?php
/**
 * Plugin Name: Role Category Manager
 * Plugin URI: https://rongcon.net
 * Description: Plugin phân quyền user để quản lý quyền xem, sửa, tạo các chuyên mục bài viết
 * Version: 1.0.0
 * Author: Rồng Con HG & Gemini 
 * Author URI: https://rongcon.net
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: role-category-manager
 * Domain Path: /languages
 */

// Ngăn chặn truy cập trực tiếp
if (!defined('ABSPATH')) {
    exit;
}

// Ngăn chặn load plugin nhiều lần - kiểm tra constant thay vì class
if (defined('RCHG_MU_VERSION')) {
    return;
}

// Định nghĩa các constants với prefix mới
define('RCHG_MU_VERSION', '1.0.0');
define('RCHG_MU_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RCHG_MU_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RCHG_MU_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include các file cần thiết với error handling
$required_files = array(
    'includes/class-rchg-mu-admin.php',
    'includes/class-rchg-mu-permissions.php',
    'includes/class-rchg-mu-frontend.php'
);

$missing_files = array();
foreach ($required_files as $file) {
    $file_path = RCHG_MU_PLUGIN_DIR . $file;
    if (file_exists($file_path)) {
        require_once $file_path;
    } else {
        $missing_files[] = $file;
        // Log error nếu file không tồn tại
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('RCHG_MU Error: Required file not found - ' . $file_path);
        }
    }
}

// Kiểm tra nếu có file bị thiếu, hiển thị admin notice
if (!empty($missing_files)) {
    add_action('admin_notices', function() use ($missing_files) {
        echo '<div class="error"><p><strong>Role Category Manager Error:</strong> Missing required files: ' . implode(', ', $missing_files) . '</p></div>';
    });
    return; // Không khởi tạo plugin nếu thiếu file
}

/**
 * Class chính của plugin
 */
class Role_Category_Manager_MU {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Khởi tạo plugin
        add_action('plugins_loaded', array($this, 'init'));
        
        // Activation hook
        register_activation_hook(__FILE__, array($this, 'activate'));
        
        // Deactivation hook
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Khởi tạo plugin
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('role-category-manager', false, dirname(RCHG_MU_PLUGIN_BASENAME) . '/languages');
        
        // Khởi tạo các class nếu tồn tại
        if (is_admin()) {
            if (class_exists('RCHG_MU_Admin')) {
                RCHG_MU_Admin::get_instance();
            } else {
                error_log('RCHG_MU Error: RCHG_MU_Admin class not found');
            }
        }
        
        if (class_exists('RCHG_MU_Permissions')) {
            RCHG_MU_Permissions::get_instance();
        } else {
            error_log('RCHG_MU Error: RCHG_MU_Permissions class not found');
        }
        
        if (class_exists('RCHG_MU_Frontend')) {
            RCHG_MU_Frontend::get_instance();
        } else {
            error_log('RCHG_MU Error: RCHG_MU_Frontend class not found');
        }
    }
    
    /**
     * Kích hoạt plugin
     */
    public function activate() {
        // Tạo bảng trong database nếu cần
        global $wpdb;
        $table_name = $wpdb->prefix . 'rchg_mu_permissions';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            permission_type varchar(20) NOT NULL DEFAULT 'role',
            role_name varchar(100) DEFAULT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            category_id bigint(20) unsigned NOT NULL,
            can_view tinyint(1) DEFAULT 1,
            can_edit tinyint(1) DEFAULT 0,
            can_create tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY permission_type (permission_type),
            KEY role_name (role_name),
            KEY user_id (user_id),
            KEY category_id (category_id)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Thêm option version
        add_option('rchg_mu_version', RCHG_MU_VERSION);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Hủy kích hoạt plugin
     */
    public function deactivate() {
        // Có thể thêm code cleanup ở đây nếu cần
    }
}

// Khởi tạo plugin
function rchg_mu_init() {
    return Role_Category_Manager_MU::get_instance();
}

// Chạy plugin
rchg_mu_init();
