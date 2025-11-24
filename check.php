<?php
/**
 * Simple check - Mở file này trong browser: http://localhost:8881/wp-content/plugins/role-category-manager/check.php
 */

// Include WordPress
$wp_load_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
if (!file_exists($wp_load_path)) {
    die('Cannot find wp-load.php at: ' . $wp_load_path);
}
require_once($wp_load_path);

if (!current_user_can('manage_options')) {
    die('Bạn cần đăng nhập với tài khoản admin!');
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Plugin Check</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .check { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .ok { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { color: blue; }
        h2 { color: #333; border-bottom: 2px solid #0073aa; padding-bottom: 10px; }
        pre { background: #f9f9f9; padding: 10px; border-left: 3px solid #0073aa; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 Role Category Manager - Plugin Check</h1>
    
    <div class="check">
        <h2>1. Constants</h2>
        <?php
        $constants = ['RCHG_MU_VERSION', 'RCHG_MU_PLUGIN_DIR', 'RCHG_MU_PLUGIN_URL', 'RCHG_MU_PLUGIN_BASENAME'];
        foreach ($constants as $const) {
            if (defined($const)) {
                echo "<p class='ok'>✓ {$const}: " . constant($const) . "</p>";
            } else {
                echo "<p class='error'>✗ {$const}: NOT DEFINED</p>";
            }
        }
        ?>
    </div>
    
    <div class="check">
        <h2>2. Classes</h2>
        <?php
        $classes = ['Role_Category_Manager_MU', 'RCHG_MU_Admin', 'RCHG_MU_Permissions', 'RCHG_MU_Frontend'];
        foreach ($classes as $class) {
            if (class_exists($class)) {
                echo "<p class='ok'>✓ {$class}</p>";
            } else {
                echo "<p class='error'>✗ {$class} - NOT FOUND</p>";
            }
        }
        ?>
    </div>
    
    <div class="check">
        <h2>3. Plugin Status</h2>
        <?php
        $plugin = 'role-category-manager/role-category-manager.php';
        $is_active = is_plugin_active($plugin);
        if ($is_active) {
            echo "<p class='ok'>✓ Plugin is ACTIVE</p>";
        } else {
            echo "<p class='error'>✗ Plugin is NOT ACTIVE</p>";
        }
        ?>
    </div>
    
    <div class="check">
        <h2>4. Database Table</h2>
        <?php
        global $wpdb;
        $table = $wpdb->prefix . 'rchg_mu_permissions';
        $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table;
        if ($exists) {
            $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
            echo "<p class='ok'>✓ Table {$table} exists</p>";
            echo "<p class='info'>Records: {$count}</p>";
        } else {
            echo "<p class='error'>✗ Table {$table} NOT EXISTS</p>";
        }
        ?>
    </div>
    
    <div class="check">
        <h2>5. Files Check</h2>
        <?php
        $plugin_dir = WP_PLUGIN_DIR . '/role-category-manager/';
        $files = [
            'role-category-manager.php',
            'includes/class-rchg-mu-admin.php',
            'includes/class-rchg-mu-permissions.php',
            'includes/class-rchg-mu-frontend.php',
            'assets/css/admin-style.css',
            'assets/js/admin-script.js'
        ];
        
        foreach ($files as $file) {
            $path = $plugin_dir . $file;
            if (file_exists($path)) {
                echo "<p class='ok'>✓ {$file}</p>";
            } else {
                echo "<p class='error'>✗ {$file} - NOT FOUND</p>";
            }
        }
        ?>
    </div>
    
    <div class="check">
        <h2>6. Admin Menu</h2>
        <?php
        global $menu;
        $found = false;
        if (!empty($menu)) {
            foreach ($menu as $item) {
                if (isset($item[2]) && $item[2] === 'rchg-mu-permissions') {
                    echo "<p class='ok'>✓ Menu 'Phân Quyền' found at position: " . $item[2] . "</p>";
                    echo "<pre>" . print_r($item, true) . "</pre>";
                    $found = true;
                    break;
                }
            }
        }
        
        if (!$found) {
            echo "<p class='error'>✗ Menu 'Phân Quyền' NOT FOUND</p>";
            echo "<p class='info'>Available menus:</p>";
            echo "<pre>";
            if (!empty($menu)) {
                foreach ($menu as $item) {
                    if (!empty($item[0])) {
                        echo "- " . strip_tags($item[0]) . " ({$item[2]})\n";
                    }
                }
            } else {
                echo "No menus found!";
            }
            echo "</pre>";
        }
        ?>
    </div>
    
    <div class="check">
        <h2>7. Hooks Debug</h2>
        <?php
        global $wp_filter;
        $hooks_to_check = ['admin_menu', 'plugins_loaded'];
        foreach ($hooks_to_check as $hook) {
            if (isset($wp_filter[$hook])) {
                echo "<p class='ok'>✓ Hook '{$hook}' is registered</p>";
                echo "<details><summary>Show callbacks</summary><pre>";
                foreach ($wp_filter[$hook]->callbacks as $priority => $callbacks) {
                    foreach ($callbacks as $callback) {
                        $func = is_array($callback['function']) ? get_class($callback['function'][0]) . '::' . $callback['function'][1] : $callback['function'];
                        echo "Priority {$priority}: {$func}\n";
                    }
                }
                echo "</pre></details>";
            } else {
                echo "<p class='error'>✗ Hook '{$hook}' NOT registered</p>";
            }
        }
        ?>
    </div>
    
    <div class="check">
        <h2>8. Quick Actions</h2>
        <p>
            <a href="<?php echo admin_url('plugins.php'); ?>" class="button">→ Go to Plugins Page</a>
            <a href="<?php echo admin_url('admin.php?page=rchg-mu-permissions'); ?>" class="button">→ Try Open Phân Quyền Page</a>
        </p>
    </div>
    
</body>
</html>
