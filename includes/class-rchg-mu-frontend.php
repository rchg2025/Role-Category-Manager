<?php
/**
 * Class xử lý frontend
 */

if (!defined('ABSPATH')) {
    exit;
}

class RCHG_MU_Frontend {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Enqueue frontend styles nếu cần
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // Thêm shortcode hiển thị thông báo
        add_shortcode('rchg_mu_user_categories', array($this, 'display_user_categories_shortcode'));
    }
    
    /**
     * Enqueue CSS cho frontend
     */
    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            'rchg-mu-frontend-style',
            RCHG_MU_PLUGIN_URL . 'assets/css/frontend-style.css',
            array(),
            RCHG_MU_VERSION
        );
    }
    
    /**
     * Shortcode hiển thị các chuyên mục user có quyền xem
     */
    public function display_user_categories_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p class="rchg_notice">' . __('Vui lòng đăng nhập để xem nội dung.', 'role-category-manager') . '</p>';
        }
        
        $permissions = RCHG_MU_Permissions::get_instance();
        $allowed_categories = $permissions->get_allowed_categories_for_user();
        
        if ($allowed_categories === 'all') {
            return '<p class="rchg_notice rchg_notice_success">' . __('Bạn có quyền xem tất cả các chuyên mục.', 'role-category-manager') . '</p>';
        }
        
        if (empty($allowed_categories)) {
            return '<p class="rchg_notice rchg_notice_warning">' . __('Bạn chưa được cấp quyền xem bất kỳ chuyên mục nào.', 'role-category-manager') . '</p>';
        }
        
        $categories = get_categories(array(
            'include' => $allowed_categories,
            'hide_empty' => false
        ));
        
        if (empty($categories)) {
            return '<p class="rchg_notice rchg_notice_warning">' . __('Không tìm thấy chuyên mục nào.', 'role-category-manager') . '</p>';
        }
        
        $output = '<div class="rchg_user_categories">';
        $output .= '<h3 class="rchg_user_categories_title">' . __('Chuyên mục bạn có quyền xem:', 'role-category-manager') . '</h3>';
        $output .= '<ul class="rchg_user_categories_list">';
        
        foreach ($categories as $category) {
            $output .= '<li class="rchg_user_category_item">';
            $output .= '<a href="' . esc_url(get_category_link($category->term_id)) . '" class="rchg_user_category_link">';
            $output .= esc_html($category->name);
            $output .= ' <span class="rchg_user_category_count">(' . $category->count . ')</span>';
            $output .= '</a>';
            $output .= '</li>';
        }
        
        $output .= '</ul>';
        $output .= '</div>';
        
        return $output;
    }
}

