Mở file này trong trình duyệt: http://localhost:8881/wp-content/plugins/role-category-manager/info.php
<?php
/**
 * Thông tin plugin đơn giản
 */

// Tìm wp-load.php
$possible_paths = [
    __DIR__ . '/../../../../wp-load.php',
    __DIR__ . '/../../../wp-load.php', 
    dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php',
];

$wp_loaded = false;
foreach ($possible_paths as $path) {
    if (file_exists($path)) {
        require_once($path);
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) {
    echo "<h1>Không tìm thấy WordPress</h1>";
    echo "<p>Đường dẫn thử:</p><ul>";
    foreach ($possible_paths as $p) {
        echo "<li>{$p} - " . (file_exists($p) ? 'Có' : 'Không có') . "</li>";
    }
    echo "</ul>";
    die();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Plugin Info</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .ok { color: green; }
        .error { color: red; }
        h2 { color: #0073aa; }
    </style>
</head>
<body>
    <h1>Role Category Manager - Status</h1>
    
    <div class="box">
        <h2>Plugin Active?</h2>
        <p class="<?php echo is_plugin_active('role-category-manager/role-category-manager.php') ? 'ok' : 'error'; ?>">
            <?php 
            if (is_plugin_active('role-category-manager/role-category-manager.php')) {
                echo "✓ Plugin đã ACTIVE";
            } else {
                echo "✗ Plugin CHƯA ACTIVE - Hãy vào Plugins và bấm Activate!";
            }
            ?>
        </p>
    </div>
    
    <div class="box">
        <h2>Classes Loaded?</h2>
        <?php
        $classes = [
            'Role_Category_Manager_MU',
            'RCHG_MU_Admin', 
            'RCHG_MU_Permissions',
            'RCHG_MU_Frontend'
        ];
        foreach ($classes as $class) {
            $exists = class_exists($class);
            echo "<p class='" . ($exists ? 'ok' : 'error') . "'>";
            echo ($exists ? '✓' : '✗') . " {$class}";
            echo "</p>";
        }
        ?>
    </div>
    
    <div class="box">
        <h2>Constants Defined?</h2>
        <?php
        $constants = ['RCHG_MU_VERSION', 'RCHG_MU_PLUGIN_DIR'];
        foreach ($constants as $const) {
            $defined = defined($const);
            echo "<p class='" . ($defined ? 'ok' : 'error') . "'>";
            echo ($defined ? '✓' : '✗') . " {$const}";
            if ($defined) echo " = " . constant($const);
            echo "</p>";
        }
        ?>
    </div>
    
    <div class="box">
        <h2>Database Table?</h2>
        <?php
        global $wpdb;
        $table = $wpdb->prefix . 'rchg_mu_permissions';
        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) === $table;
        echo "<p class='" . ($exists ? 'ok' : 'error') . "'>";
        echo ($exists ? '✓' : '✗') . " Table: {$table}";
        echo "</p>";
        ?>
    </div>
    
    <div class="box">
        <h2>Admin Menu?</h2>
        <?php
        global $menu;
        $found = false;
        if (is_array($menu)) {
            foreach ($menu as $item) {
                if (isset($item[2]) && $item[2] === 'rchg-mu-permissions') {
                    echo "<p class='ok'>✓ Menu 'Phân Quyền' đã được đăng ký</p>";
                    $found = true;
                    break;
                }
            }
        }
        if (!$found) {
            echo "<p class='error'>✗ Menu chưa được đăng ký</p>";
            echo "<p><small>Lưu ý: Menu chỉ hiển thị khi ở trang admin và user có quyền manage_options</small></p>";
        }
        ?>
    </div>
    
    <div class="box">
        <h2>Actions</h2>
        <p>
            <a href="<?php echo admin_url('plugins.php'); ?>" style="padding: 10px 15px; background: #0073aa; color: white; text-decoration: none; border-radius: 3px; display: inline-block;">
                → Đến trang Plugins
            </a>
        </p>
        <p>
            <a href="<?php echo admin_url('admin.php?page=rchg-mu-permissions'); ?>" style="padding: 10px 15px; background: #0073aa; color: white; text-decoration: none; border-radius: 3px; display: inline-block;">
                → Mở trang Phân Quyền
            </a>
        </p>
    </div>
    
    <div class="box">
        <h2>Hướng dẫn</h2>
        <ol>
            <li>Nếu plugin chưa active → Vào <a href="<?php echo admin_url('plugins.php'); ?>">Plugins</a> và bấm Activate</li>
            <li>Nếu đã active nhưng không thấy menu → Kiểm tra user có quyền 'manage_options' không</li>
            <li>Refresh cache trình duyệt (Ctrl+Shift+R)</li>
            <li>Kiểm tra sidebar bên trái WordPress Admin</li>
        </ol>
    </div>
    
</body>
</html>
