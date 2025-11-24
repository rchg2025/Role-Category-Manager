<?php
/**
 * Test script - Chạy: php test-plugin.php
 */

// Load WordPress
define('WP_USE_THEMES', false);
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php');

echo "=== ROLE CATEGORY MANAGER - TEST ===\n\n";

// 1. Kiểm tra constants
echo "1. CONSTANTS:\n";
echo "   RCHG_MU_VERSION: " . (defined('RCHG_MU_VERSION') ? RCHG_MU_VERSION : 'NOT DEFINED') . "\n";
echo "   RCHG_MU_PLUGIN_DIR: " . (defined('RCHG_MU_PLUGIN_DIR') ? RCHG_MU_PLUGIN_DIR : 'NOT DEFINED') . "\n\n";

// 2. Kiểm tra classes
echo "2. CLASSES:\n";
$classes = ['Role_Category_Manager_MU', 'RCHG_MU_Admin', 'RCHG_MU_Permissions', 'RCHG_MU_Frontend'];
foreach ($classes as $class) {
    echo "   " . ($class_exists($class) ? "✓" : "✗") . " {$class}\n";
}
echo "\n";

// 3. Kiểm tra plugin active
echo "3. PLUGIN STATUS:\n";
$plugin = 'role-category-manager/role-category-manager.php';
echo "   Active: " . (is_plugin_active($plugin) ? "YES" : "NO") . "\n\n";

// 4. Kiểm tra database table
echo "4. DATABASE:\n";
global $wpdb;
$table = $wpdb->prefix . 'rchg_mu_permissions';
$exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table;
echo "   Table {$table}: " . ($exists ? "EXISTS" : "NOT EXISTS") . "\n\n";

// 5. Kiểm tra hooks
echo "5. HOOKS:\n";
global $wp_filter;
$has_admin_menu = isset($wp_filter['admin_menu']) && !empty($wp_filter['admin_menu']->callbacks);
echo "   admin_menu hook: " . ($has_admin_menu ? "REGISTERED" : "NOT REGISTERED") . "\n";

// 6. Kiểm tra user có quyền
echo "\n6. USER PERMISSIONS:\n";
$current_user = wp_get_current_user();
echo "   Current user: " . $current_user->user_login . "\n";
echo "   Has manage_options: " . (current_user_can('manage_options') ? "YES" : "NO") . "\n";

echo "\n=== END TEST ===\n";
