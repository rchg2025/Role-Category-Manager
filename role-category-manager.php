<?php
/**
 * Plugin Name: RCHG Role Category Manager
 * Description: Phân quyền xem bài viết theo chuyên mục cho Role và User
 * Version: 2.0.1
 * Author: Rồng Con HG
 * Author URI: https://rongcon.net
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: role-category-manager
 */

if (!defined('ABSPATH')) exit;

define('RCHG_MU_VERSION', '2.0.1');
define('RCHG_MU_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RCHG_MU_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load classes
$rchg_mu_class_files = glob(RCHG_MU_PLUGIN_DIR . 'includes/*.php');
if (is_array($rchg_mu_class_files)) {
    foreach ($rchg_mu_class_files as $rchg_mu_file) {
        require_once $rchg_mu_file;
    }
}

// Activation hook
register_activation_hook(__FILE__, function() {
    global $wpdb;
    $table = $wpdb->prefix . 'rchg_mu_permissions';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        permission_type varchar(20) NOT NULL DEFAULT 'role',
        role_name varchar(100) DEFAULT NULL,
        user_id bigint(20) unsigned DEFAULT NULL,
        category_id bigint(20) unsigned NOT NULL,
        can_view tinyint(1) NOT NULL DEFAULT 1,
        can_edit tinyint(1) NOT NULL DEFAULT 0,
        can_create tinyint(1) NOT NULL DEFAULT 0,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY permission_type (permission_type),
        KEY role_name (role_name),
        KEY user_id (user_id),
        KEY category_id (category_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
});

// Initialize
add_action('plugins_loaded', function() {
    if (is_admin() && class_exists('RCHG_MU_Admin')) {
        RCHG_MU_Admin::get_instance();
    }
    if (class_exists('RCHG_MU_Permissions')) {
        RCHG_MU_Permissions::get_instance();
    }
    if (class_exists('RCHG_MU_Frontend')) {
        RCHG_MU_Frontend::get_instance();
    }
});
