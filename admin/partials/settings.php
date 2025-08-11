<?php
/**
 * 设置页面模板
 */
// 如果直接访问则退出
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('b2bpress_settings');
        do_settings_sections('b2bpress_settings');
        submit_button();
        ?>
    </form>
    
    <div class="b2bpress-settings-info">
        <div class="postbox">
            <div class="postbox-header">
                <h2><?php _e('WooCommerce 精简模式说明', 'b2bpress'); ?></h2>
            </div>
            <div class="inside">
                <p><?php _e('WooCommerce 精简模式可以禁用不需要的 WooCommerce 功能，使其更适合 B2B 业务。', 'b2bpress'); ?></p>
                <ul>
                    <li><?php _e('<strong>禁用购物车</strong>：禁用 WooCommerce 购物车功能，隐藏"添加到购物车"按钮。', 'b2bpress'); ?></li>
                    <li><?php _e('<strong>禁用结账</strong>：禁用 WooCommerce 结账功能，用户将无法完成购买流程。', 'b2bpress'); ?></li>
                    <li><?php _e('<strong>禁用优惠券</strong>：禁用 WooCommerce 优惠券功能，隐藏优惠券输入框。', 'b2bpress'); ?></li>
                    <li><?php _e('<strong>禁用库存</strong>：禁用 WooCommerce 库存管理功能，所有产品将显示为"有库存"。', 'b2bpress'); ?></li>
                    <li><?php _e('<strong>禁用价格</strong>：隐藏产品价格，适用于需要询价的 B2B 业务。', 'b2bpress'); ?></li>
                    <li><?php _e('<strong>禁用营销</strong>：禁用 WooCommerce 营销功能，隐藏相关菜单和功能。', 'b2bpress'); ?></li>
                    <li><?php _e('<strong>禁用前端 CSS/JS</strong>：禁用 WooCommerce 前端 CSS 和 JavaScript，减少页面加载时间。', 'b2bpress'); ?></li>
                </ul>
            </div>
        </div>
        
        <div class="postbox">
            <div class="postbox-header">
                <h2><?php _e('表格设置说明', 'b2bpress'); ?></h2>
            </div>
            <div class="inside">
                <p><?php _e('表格设置用于配置产品表格的默认行为。', 'b2bpress'); ?></p>
                <ul>
                    <li><?php _e('<strong>默认表格样式</strong>：设置表格的默认样式，可以在短代码中覆盖。', 'b2bpress'); ?></li>
                    <li><?php _e('<strong>显示产品图片</strong>：控制是否在前端表格中显示产品图片列。', 'b2bpress'); ?></li>
                    <li><?php _e('<strong>默认每页显示</strong>：设置表格默认每页显示的产品数量，可以在短代码中覆盖。', 'b2bpress'); ?></li>
                    <li><?php _e('<strong>需要登录</strong>：启用后，只有登录用户才能查看表格，未登录用户将被重定向到登录页面。', 'b2bpress'); ?></li>
                </ul>
                <p class="description"><?php _e('注意：更改表格样式或图片显示设置后，所有表格缓存将被自动清除，以确保新设置立即生效。', 'b2bpress'); ?></p>
            </div>
        </div>
    </div>
</div> 