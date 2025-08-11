<?php
/**
 * B2BPress 前端类
 * 
 * 处理插件的前端显示功能
 */
class B2BPress_Public {
    /**
     * 构造函数
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * 初始化钩子
     */
    private function init_hooks() {
        // 注册前端脚本和样式
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // 处理表格显示
        add_action('b2bpress_before_table', array($this, 'before_table'));
        add_action('b2bpress_after_table', array($this, 'after_table'));
        
        // 处理登录限制
        add_action('template_redirect', array($this, 'check_login_required'));
    }

    /**
     * 注册前端脚本和样式
     */
    public function enqueue_scripts() {
        // 获取设置
        $options = get_option('b2bpress_options', array());
        
        // 如果禁用了WooCommerce前端CSS/JS，则不加载WooCommerce样式
        if (isset($options['disable_css_js']) && $options['disable_css_js']) {
            add_filter('woocommerce_enqueue_styles', '__return_empty_array');
            add_action('wp_enqueue_scripts', array($this, 'dequeue_woocommerce_scripts'), 99);
        }
        
        // 注册和加载样式
        wp_register_style(
            'b2bpress-public',
            B2BPRESS_PLUGIN_URL . 'public/css/b2bpress-public.css',
            array(),
            B2BPRESS_VERSION
        );
        wp_enqueue_style('b2bpress-public');
        
        // 添加全局表格CSS
        if (isset($options['global_table_css']) && !empty($options['global_table_css'])) {
            wp_add_inline_style('b2bpress-public', '.b2bpress-table { ' . $options['global_table_css'] . ' }');
        }
        
        // 注册和加载脚本
        wp_register_script(
            'b2bpress-public',
            B2BPRESS_PLUGIN_URL . 'public/js/b2bpress-public.js',
            array('jquery'),
            B2BPRESS_VERSION,
            true
        );
        
        // 获取站点语言
        $site_language = determine_locale();
        
        // 尝试从核心实例获取站点语言
        if (isset($GLOBALS['b2bpress_core'])) {
            $core = $GLOBALS['b2bpress_core'];
            if (method_exists($core, 'get_language_manager')) {
                $language_manager = $core->get_language_manager();
                if ($language_manager) {
                    $site_language = $language_manager->get_site_language();
                }
            }
        }
        
        $default_locale = 'en_US'; // 默认英文
        
        // 本地化脚本
        wp_localize_script('b2bpress-public', 'b2bpressPublic', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('b2bpress-public-nonce'),
            'i18n' => array(
                'loading' => __('Loading...', 'b2bpress'),
                'no_results' => __('No results found', 'b2bpress'),
                'error' => __('An error occurred', 'b2bpress'),
                'showing' => __('Showing', 'b2bpress'),
                'to' => __('to', 'b2bpress'),
                'of' => __('of', 'b2bpress'),
                'items' => __('items', 'b2bpress'),
                'prev_page' => __('Previous page', 'b2bpress'),
                'next_page' => __('Next page', 'b2bpress'),
                'refresh_cache' => __('Refresh Cache', 'b2bpress'),
                'edit_table' => __('Edit Table', 'b2bpress'),
                'image' => __('Image', 'b2bpress'),
                'product_name' => __('Product Name', 'b2bpress'),
                'in_stock' => __('In Stock', 'b2bpress'),
                'out_of_stock' => __('Out of Stock', 'b2bpress'),
                'on_backorder' => __('On Backorder', 'b2bpress'),
            ),
            'locale' => $site_language,
            'default_locale' => $default_locale,
        ));
        
        wp_enqueue_script('b2bpress-public');
    }

    /**
     * 取消加载WooCommerce脚本
     */
    public function dequeue_woocommerce_scripts() {
        // 取消加载WooCommerce脚本
        wp_dequeue_script('woocommerce');
        wp_dequeue_script('wc-cart-fragments');
        wp_dequeue_script('wc-add-to-cart');
        
        // 取消加载其他WooCommerce相关脚本
        $scripts = array(
            'wc-add-to-cart-variation',
            'wc-single-product',
            'wc-checkout',
            'wc-cart',
            'wc-chosen',
            'wc-lost-password',
            'wc-password-strength-meter',
        );
        
        foreach ($scripts as $script) {
            wp_dequeue_script($script);
        }
    }

    /**
     * 表格前的操作
     *
     * @param array $args 表格参数
     */
    public function before_table($args) {
        // 获取站点语言
        $site_language = determine_locale();
        
        // 尝试从核心实例获取站点语言
        if (isset($GLOBALS['b2bpress_core'])) {
            $core = $GLOBALS['b2bpress_core'];
            if (method_exists($core, 'get_language_manager')) {
                $language_manager = $core->get_language_manager();
                if ($language_manager) {
                    $site_language = $language_manager->get_site_language();
                }
            }
        }
        
        // 输出表格前的HTML
        echo '<div class="b2bpress-table-container" data-id="' . esc_attr($args['id']) . '" data-language="' . esc_attr($site_language) . '">';
        
        // 添加搜索框
        echo '<div class="b2bpress-table-search">';
        echo '<input type="text" class="b2bpress-search-input" placeholder="' . esc_attr__('搜索产品...', 'b2bpress') . '">';
        echo '</div>';
        
        do_action('b2bpress_table_before_render', $args);
    }

    /**
     * 表格后的操作
     *
     * @param array $args 表格参数
     */
    public function after_table($args) {
        do_action('b2bpress_table_after_render', $args);
        
        // 输出表格后的HTML
        echo '</div>'; // .b2bpress-table-container
    }

    /**
     * 检查登录限制
     */
    public function check_login_required() {
        // 获取设置
        $options = get_option('b2bpress_options', array());
        
        // 如果需要登录且用户未登录
        if (isset($options['login_required']) && $options['login_required'] && !is_user_logged_in()) {
            // 检查是否包含表格短代码
            global $post;
            
            if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'b2bpress_table')) {
                // 重定向到登录页面
                wp_redirect(wp_login_url(get_permalink()));
                exit;
            }
        }
    }
} 