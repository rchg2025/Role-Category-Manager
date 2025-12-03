<?php
if (!defined('ABSPATH')) exit;

class RCHG_MU_Frontend {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_shortcode('rchg_mu_user_categories', array($this, 'render_user_categories'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }
    
    public function enqueue_assets() {
        if (has_shortcode(get_the_content(), 'rchg_mu_user_categories')) {
            wp_enqueue_style('rchg-frontend', RCHG_MU_PLUGIN_URL . 'assets/css/frontend-style.css', array(), RCHG_MU_VERSION);
        }
    }
    
    /**
     * Shortcode: Display user's allowed categories
     */
    public function render_user_categories($atts) {
        $atts = shortcode_atts(array(
            'permission' => 'can_view',
        ), $atts);
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            return '<p>' . esc_html__('Bạn cần đăng nhập để xem danh sách chuyên mục.', 'role-category-manager') . '</p>';
        }
        
        $permissions = RCHG_MU_Permissions::get_instance();
        $allowed_cats = $permissions->get_user_categories($user_id, $atts['permission']);
        
        if (empty($allowed_cats)) {
            return '<p>' . esc_html__('Bạn chưa được phân quyền cho bất kỳ chuyên mục nào.', 'role-category-manager') . '</p>';
        }
        
        $categories = get_categories(array(
            'include' => $allowed_cats,
            'hide_empty' => false,
        ));
        
        ob_start();
        ?>
        <div class="rchg-user-categories">
            <h3><?php esc_html_e('Chuyên mục của bạn:', 'role-category-manager'); ?></h3>
            <ul class="rchg-category-list">
                <?php foreach ($categories as $cat): ?>
                    <li>
                        <a href="<?php echo esc_url(get_category_link($cat->term_id)); ?>">
                            <?php echo esc_html($cat->name); ?>
                        </a>
                        <span class="count">(<?php echo intval($cat->count); ?>)</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }
}
