<?php
/**
 * Admin page template
 * 
 * @package Role_Category_Manager
 */

// Ngăn chặn truy cập trực tiếp
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap rcm-wrap">
    <h1 class="rcm-title">
        <span class="dashicons dashicons-admin-network"></span>
        <?php echo esc_html__('Quản Lý Quyền Xem Chuyên Mục', 'role-category-manager'); ?>
    </h1>
    
    <div class="rcm-description">
        <p><?php echo esc_html__('Cấu hình quyền xem các chuyên mục cho từng vai trò người dùng. Người dùng chỉ có thể xem các bài viết thuộc chuyên mục được phép.', 'role-category-manager'); ?></p>
    </div>
    
    <div class="rcm-container">
        <div class="rcm-sidebar">
            <h2><?php echo esc_html__('Vai Trò', 'role-category-manager'); ?></h2>
            <ul class="rcm-roles-list">
                <?php foreach ($all_roles as $role_slug => $role_data) : ?>
                    <li>
                        <button 
                            class="rcm-role-button" 
                            data-role="<?php echo esc_attr($role_slug); ?>"
                            <?php echo ($role_slug === 'administrator') ? 'disabled' : ''; ?>
                        >
                            <span class="rcm-role-name"><?php echo esc_html($role_data['name']); ?></span>
                            <span class="rcm-role-slug"><?php echo esc_html($role_slug); ?></span>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>
            
            <?php if (isset($all_roles['administrator'])) : ?>
                <div class="rcm-info-box">
                    <span class="dashicons dashicons-info"></span>
                    <p><?php echo esc_html__('Administrator luôn có quyền xem tất cả chuyên mục.', 'role-category-manager'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="rcm-main-content">
            <div class="rcm-content-header">
                <h2>
                    <?php echo esc_html__('Chuyên Mục Được Phép', 'role-category-manager'); ?>
                    <span id="rcm-selected-role"></span>
                </h2>
                <button id="rcm-save-btn" class="button button-primary" disabled>
                    <span class="dashicons dashicons-saved"></span>
                    <?php echo esc_html__('Lưu Thay Đổi', 'role-category-manager'); ?>
                </button>
            </div>
            
            <div id="rcm-loading" class="rcm-loading">
                <span class="spinner is-active"></span>
                <p><?php echo esc_html__('Đang tải...', 'role-category-manager'); ?></p>
            </div>
            
            <div id="rcm-select-role" class="rcm-select-prompt">
                <span class="dashicons dashicons-arrow-left-alt"></span>
                <p><?php echo esc_html__('Vui lòng chọn một vai trò từ danh sách bên trái', 'role-category-manager'); ?></p>
            </div>
            
            <div id="rcm-categories-container" style="display: none;">
                <div class="rcm-select-all">
                    <label>
                        <input type="checkbox" id="rcm-select-all-categories">
                        <strong><?php echo esc_html__('Chọn Tất Cả', 'role-category-manager'); ?></strong>
                    </label>
                </div>
                
                <div class="rcm-categories-grid">
                    <?php foreach ($categories as $category) : ?>
                        <div class="rcm-category-item">
                            <label>
                                <input 
                                    type="checkbox" 
                                    class="rcm-category-checkbox" 
                                    name="categories[]" 
                                    value="<?php echo esc_attr($category->term_id); ?>"
                                >
                                <span class="rcm-category-name">
                                    <?php echo esc_html($category->name); ?>
                                    <span class="rcm-category-count">(<?php echo esc_html($category->count); ?>)</span>
                                </span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($categories)) : ?>
                        <div class="rcm-empty-state">
                            <span class="dashicons dashicons-category"></span>
                            <p><?php echo esc_html__('Chưa có chuyên mục nào. Vui lòng tạo chuyên mục trước.', 'role-category-manager'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div id="rcm-message" class="rcm-message" style="display: none;"></div>
        </div>
    </div>
</div>
