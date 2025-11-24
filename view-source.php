<?php
/**
 * View Page Source - Xem HTML của trang Phân Quyền
 * Mở: http://localhost:8881/wp-content/plugins/role-category-manager/view-source.php
 */

$wp_load_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
require_once($wp_load_path);

if (!current_user_can('manage_options')) {
    die('Cần quyền admin!');
}

// Get instance
if (class_exists('RCHG_MU_Admin')) {
    $admin = RCHG_MU_Admin::get_instance();
    
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
    echo "<title>View Source</title>";
    echo "<style>body{font-family:monospace;padding:20px;background:#f5f5f5;}";
    echo "pre{background:white;padding:15px;border:1px solid #ccc;overflow-x:auto;}</style>";
    echo "</head><body><h1>HTML Source của render_admin_page()</h1>";
    echo "<pre>";
    
    ob_start();
    $admin->render_admin_page();
    $html = ob_get_clean();
    
    echo htmlspecialchars($html);
    echo "</pre>";
    
    echo "<hr><h2>Kiểm tra:</h2><ul>";
    echo "<li>Có <code>id=\"rchg_mu_role_select\"</code>? " . (strpos($html, 'id="rchg_mu_role_select"') !== false ? '✓ CÓ' : '✗ KHÔNG') . "</li>";
    echo "<li>Có <code>id=\"rchg_mu_categories_container\"</code>? " . (strpos($html, 'id="rchg_mu_categories_container"') !== false ? '✓ CÓ' : '✗ KHÔNG') . "</li>";
    echo "<li>Có options trong select? " . (strpos($html, '<option value=') !== false ? '✓ CÓ' : '✗ KHÔNG') . "</li>";
    echo "<li>Có checkbox categories? " . (strpos($html, 'type="checkbox"') !== false ? '✓ CÓ' : '✗ KHÔNG') . "</li>";
    echo "</ul></body></html>";
} else {
    echo "Class RCHG_MU_Admin không tồn tại!";
}
