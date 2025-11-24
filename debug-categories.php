<?php
/**
 * Debug - Kiểm tra categories
 * Mở: http://localhost:8881/wp-content/plugins/role-category-manager/debug-categories.php
 */

$wp_load_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
require_once($wp_load_path);

if (!current_user_can('manage_options')) {
    die('Bạn cần đăng nhập với tài khoản admin!');
}

global $wp_roles;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Debug Categories & Roles</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h2 { color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #0073aa; color: white; }
        .ok { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>🔍 Debug: Categories & Roles</h1>
    
    <div class="box">
        <h2>1. WordPress Roles (Vai trò)</h2>
        <?php
        $roles = $wp_roles->get_names();
        if (!empty($roles)) {
            echo "<p class='ok'>✓ Có " . count($roles) . " roles</p>";
            echo "<table><tr><th>Role Key</th><th>Role Name</th></tr>";
            foreach ($roles as $role_key => $role_name) {
                echo "<tr><td><code>{$role_key}</code></td><td>{$role_name}</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='error'>✗ Không có roles nào!</p>";
        }
        ?>
    </div>
    
    <div class="box">
        <h2>2. Categories (Chuyên mục)</h2>
        <?php
        $categories = get_categories(array(
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        
        if (!empty($categories)) {
            echo "<p class='ok'>✓ Có " . count($categories) . " categories</p>";
            echo "<table><tr><th>ID</th><th>Name</th><th>Slug</th><th>Count</th></tr>";
            foreach ($categories as $cat) {
                echo "<tr>";
                echo "<td>{$cat->term_id}</td>";
                echo "<td>{$cat->name}</td>";
                echo "<td>{$cat->slug}</td>";
                echo "<td>{$cat->count}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='error'>✗ Không có categories nào!</p>";
            echo "<p><strong>Giải pháp:</strong> Vào Posts → Categories và tạo ít nhất 1 category</p>";
        }
        ?>
    </div>
    
    <div class="box">
        <h2>3. JavaScript Variables Check</h2>
        <p>Mở Developer Tools (F12) trong trang Phân Quyền và check:</p>
        <pre style="background: #f9f9f9; padding: 10px;">
// Chạy trong Console:
console.log(rchgMuAjax);
// Phải hiển thị object với: ajaxurl, nonce, saving, saved, error
        </pre>
    </div>
    
    <div class="box">
        <h2>4. Test tạo category nhanh</h2>
        <?php
        if (isset($_GET['create_test_cats'])) {
            $test_cats = ['Tin tức', 'Công nghệ', 'Thể thao', 'Giải trí'];
            foreach ($test_cats as $cat_name) {
                if (!term_exists($cat_name, 'category')) {
                    wp_insert_term($cat_name, 'category');
                }
            }
            echo "<p class='ok'>✓ Đã tạo categories test! <a href='?'>Refresh để xem</a></p>";
        } else {
            echo "<p><a href='?create_test_cats=1' style='padding: 10px 15px; background: #0073aa; color: white; text-decoration: none; border-radius: 3px; display: inline-block;'>→ Tạo 4 categories test</a></p>";
        }
        ?>
    </div>
    
    <div class="box">
        <h2>5. Check Form HTML</h2>
        <p>Trong trang Phân Quyền, mở DevTools và check:</p>
        <ul>
            <li>Có select với id="rchg_mu_role_select" không?</li>
            <li>Có div với id="rchg_mu_categories_container" không?</li>
            <li>Console có lỗi JavaScript không?</li>
        </ul>
    </div>
    
</body>
</html>
