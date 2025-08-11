<?php
/**
 * B2BPress WooCommerce精简模式类
 * 
 * 用于禁用不需要的WooCommerce功能
 */
class B2BPress_WC_Lite {
    /**
     * 构造函数
     */
    public function __construct() {
        $this->init_hooks();
        $this->check_hpos_compatibility();
    }

    /**
     * 初始化钩子
     */
    private function init_hooks() {
        // 获取设置
        $options = get_option('b2bpress_options', array());
        
        // 禁用购物车
        if (isset($options['disable_cart']) && $options['disable_cart']) {
            add_filter('woocommerce_is_cart', '__return_false');
            add_filter('woocommerce_is_checkout', '__return_false');
            add_action('template_redirect', array($this, 'disable_cart_page'));
            add_filter('woocommerce_add_to_cart_validation', '__return_false');
            remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart');
            remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
        }
        
        // 禁用结账
        if (isset($options['disable_checkout']) && $options['disable_checkout']) {
            add_action('template_redirect', array($this, 'disable_checkout_page'));
            add_filter('woocommerce_checkout_registration_enabled', '__return_false');
            add_filter('woocommerce_checkout_registration_required', '__return_false');
        }
        
        // 禁用优惠券
        if (isset($options['disable_coupons']) && $options['disable_coupons']) {
            add_filter('woocommerce_coupons_enabled', '__return_false');
            remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10);
        }
        
        // 禁用库存
        if (isset($options['disable_inventory']) && $options['disable_inventory']) {
            add_filter('woocommerce_product_get_stock_status', array($this, 'override_stock_status'), 10, 2);
            add_filter('woocommerce_product_get_stock_quantity', '__return_null');
            add_filter('woocommerce_product_get_manage_stock', '__return_false');
        }
        
        // 禁用价格并输出占位
        if (isset($options['disable_prices']) && $options['disable_prices']) {
            remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10);
            remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);

            $placeholder_cb = function($price, $product = null) {
                if (is_user_logged_in()) {
                    return __('面议', 'b2bpress');
                }
                return __('登录后可见', 'b2bpress');
            };

            add_filter('woocommerce_get_price_html', $placeholder_cb, 10, 2);
            add_filter('woocommerce_variable_sale_price_html', $placeholder_cb, 10, 2);
            add_filter('woocommerce_variable_price_html', $placeholder_cb, 10, 2);
            add_filter('woocommerce_get_variation_price_html', $placeholder_cb, 10, 2);
        }
        
        // 禁用营销
        if (isset($options['disable_marketing']) && $options['disable_marketing']) {
            add_filter('woocommerce_marketing_menu_items', '__return_empty_array');
            add_action('admin_menu', array($this, 'remove_marketing_menu'), 999);
        }

        // 从导航菜单中移除购物车与结账页
        add_filter('wp_nav_menu_objects', array($this, 'remove_cart_checkout_from_menu'), 10, 2);
    }

    /**
     * 禁用购物车页面
     */
    public function disable_cart_page() {
        if (is_cart()) {
            // 隐藏购物车端点：跳转到商店或首页
            $redirect = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');
            wp_redirect($redirect);
            exit;
        }
    }

    /**
     * 禁用结账页面
     */
    public function disable_checkout_page() {
        if (is_checkout()) {
            $redirect = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');
            wp_redirect($redirect);
            exit;
        }
    }

    /**
     * 覆盖库存状态
     *
     * @param string $status 库存状态
     * @param WC_Product $product 产品对象
     * @return string
     */
    public function override_stock_status($status, $product) {
        return 'instock';
    }

    /**
     * 移除营销菜单
     */
    public function remove_marketing_menu() {
        remove_submenu_page('woocommerce', 'wc-admin&path=/marketing');
        // 同时隐藏购物车/结账菜单入口（如果存在）
        remove_submenu_page('woocommerce', 'wc-admin&path=/checkout');
        remove_submenu_page('woocommerce', 'wc-admin&path=/cart');
    }

    /**
     * 从前端导航菜单中移除购物车与结账页面
     *
     * @param array $items
     * @param object $args
     * @return array
     */
    public function remove_cart_checkout_from_menu($items, $args) {
        $options = get_option('b2bpress_options', array());
        $remove = (isset($options['disable_cart']) && $options['disable_cart']) || (isset($options['disable_checkout']) && $options['disable_checkout']);
        if (!$remove) {
            return $items;
        }

        if (!function_exists('wc_get_page_id')) {
            return $items;
        }

        $cart_id = wc_get_page_id('cart');
        $checkout_id = wc_get_page_id('checkout');

        $filtered = array();
        foreach ($items as $item) {
            if ((int)$item->object_id === (int)$cart_id || (int)$item->object_id === (int)$checkout_id) {
                continue;
            }
            $filtered[] = $item;
        }
        return $filtered;
    }

    /**
     * 检查HPOS兼容性
     */
    public function check_hpos_compatibility() {
        // 检查是否使用HPOS
        add_action('admin_init', array($this, 'check_hpos_status'));
    }

    /**
     * 检查HPOS状态
     */
    public function check_hpos_status() {
        // 只在管理员页面检查
        if (!is_admin() || !current_user_can('manage_woocommerce')) {
            return;
        }
        
        // 检查WooCommerce版本
        if (!function_exists('WC') || version_compare(WC()->version, '8.2', '<')) {
            return;
        }
        
        // 检查是否启用了HPOS
        $is_hpos_enabled = get_option('woocommerce_custom_orders_table_enabled', 'no') === 'yes';
        
        // 如果未启用HPOS，显示通知
        if (!$is_hpos_enabled) {
            add_action('admin_notices', array($this, 'hpos_notice'));
        }
    }

    /**
     * HPOS通知
     */
    public function hpos_notice() {
        ?>
        <div class="notice notice-warning">
            <p>
                <?php 
                echo sprintf(
                    __('B2BPress 建议启用 WooCommerce 高性能订单存储 (HPOS)。自 WooCommerce 8.2 起，HPOS 已默认启用。<a href="%s">点击此处启用</a>', 'b2bpress'),
                    admin_url('admin.php?page=wc-settings&tab=advanced&section=features')
                ); 
                ?>
            </p>
        </div>
        <?php
    }
} 