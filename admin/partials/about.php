<?php
/**
 * 关于页面模板
 */
// 如果直接访问则退出
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="b2bpress-about-wrapper">
        <div class="b2bpress-about-header">
            <div class="b2bpress-about-header-text">
                <h2><?php _e('B2BPress - 专业的B2B电子商务解决方案', 'b2bpress'); ?></h2>
                <p class="b2bpress-version"><?php echo sprintf(__('版本 %s', 'b2bpress'), B2BPRESS_VERSION); ?></p>
            </div>
        </div>
        
        <div class="b2bpress-about-section">
            <h3><?php _e('插件介绍', 'b2bpress'); ?></h3>
            <p><?php _e('B2BPress是一个基于WooCommerce的B2B电子商务解决方案，专为批发和B2B业务设计。它精简了所有与B2B无关的功能，提供了更加专注于B2B场景的用户体验。', 'b2bpress'); ?></p>
            <p><?php _e('通过B2BPress，您可以轻松创建产品表格，展示产品分类和属性，使您的客户能够更快地找到所需的产品。', 'b2bpress'); ?></p>
        </div>
        
        <div class="b2bpress-about-section">
            <h3><?php _e('核心功能', 'b2bpress'); ?></h3>
            <div class="b2bpress-features">
                <div class="b2bpress-feature">
                    <div class="b2bpress-feature-icon dashicons dashicons-table-row-after"></div>
                    <h4><?php _e('产品表格', 'b2bpress'); ?></h4>
                    <p><?php _e('创建自定义产品表格，展示产品分类和属性，支持多种表格样式。', 'b2bpress'); ?></p>
                </div>
                
                <div class="b2bpress-feature">
                    <div class="b2bpress-feature-icon dashicons dashicons-filter"></div>
                    <h4><?php _e('WooCommerce精简模式', 'b2bpress'); ?></h4>
                    <p><?php _e('禁用与B2B无关的功能，如购物车、结账、优惠券等，提供更专注的B2B体验。', 'b2bpress'); ?></p>
                </div>
                
                <div class="b2bpress-feature">
                    <div class="b2bpress-feature-icon dashicons dashicons-admin-appearance"></div>
                    <h4><?php _e('多种表格样式', 'b2bpress'); ?></h4>
                    <p><?php _e('提供多种表格样式，包括默认、条纹、卡片、现代和极简风格，满足不同的设计需求。', 'b2bpress'); ?></p>
                </div>
                
                <div class="b2bpress-feature">
                    <div class="b2bpress-feature-icon dashicons dashicons-smartphone"></div>
                    <h4><?php _e('响应式设计', 'b2bpress'); ?></h4>
                    <p><?php _e('所有表格都采用响应式设计，在各种设备上都能完美显示。', 'b2bpress'); ?></p>
                </div>
                
                <div class="b2bpress-feature">
                    <div class="b2bpress-feature-icon dashicons dashicons-welcome-widgets-menus"></div>
                    <h4><?php _e('Elementor集成', 'b2bpress'); ?></h4>
                    <p><?php _e('提供Elementor小部件，轻松在Elementor页面中添加产品表格。', 'b2bpress'); ?></p>
                </div>
                
                <div class="b2bpress-feature">
                    <div class="b2bpress-feature-icon dashicons dashicons-performance"></div>
                    <h4><?php _e('性能优化', 'b2bpress'); ?></h4>
                    <p><?php _e('使用缓存机制优化表格加载速度，提供更好的用户体验。', 'b2bpress'); ?></p>
                </div>
            </div>
        </div>
        
        <div class="b2bpress-about-section">
            <h3><?php _e('使用说明', 'b2bpress'); ?></h3>
            <p><?php _e('1. 创建表格：在"表格管理"页面创建新表格，选择产品分类和要显示的属性。', 'b2bpress'); ?></p>
            <p><?php _e('2. 使用短代码：在任何页面或文章中使用短代码 [b2bpress_table id="表格ID"] 显示表格。', 'b2bpress'); ?></p>
            <p><?php _e('3. Elementor集成：在Elementor页面构建器中使用B2BPress表格小部件。', 'b2bpress'); ?></p>
            <p><?php _e('4. 配置设置：在"设置"页面配置WooCommerce精简模式和表格默认设置。', 'b2bpress'); ?></p>
        </div>
        
        <div class="b2bpress-about-section">
            <h3><?php _e('版本历史', 'b2bpress'); ?></h3>
            <div class="b2bpress-changelog">
                <div class="b2bpress-changelog-item">
                    <h4>1.0.1 <span class="b2bpress-changelog-date">2025-08-11</span></h4>
                    <ul>
                        <li><?php _e('修复：移除重复的全局启用/停用钩子，避免激活逻辑执行两次', 'b2bpress'); ?></li>
                        <li><?php _e('安全：转义表格单元格输出并白名单HTML，降低XSS风险', 'b2bpress'); ?></li>
                        <li><?php _e('性能/稳定性：使用精确的前缀/分组缓存失效替代整站缓存清空', 'b2bpress'); ?></li>
                        <li><?php _e('用户体验：为表头与分页添加可访问性属性；预渲染输出更安全', 'b2bpress'); ?></li>
                    </ul>
                </div>
                <div class="b2bpress-changelog-item">
                    <h4>1.0.0 <span class="b2bpress-changelog-date">2025-08-11</span></h4>
                    <ul>
                        <li><?php _e('初始发布', 'b2bpress'); ?></li>
                        <li><?php _e('WooCommerce 精简模式', 'b2bpress'); ?></li>
                        <li><?php _e('产品表格生成器', 'b2bpress'); ?></li>
                        <li><?php _e('Elementor 小部件', 'b2bpress'); ?></li>
                        <li><?php _e('缓存与同步机制', 'b2bpress'); ?></li>
                        <li><?php _e('权限与可见性控制', 'b2bpress'); ?></li>
                        <li><?php _e('开发者钩子与 REST API', 'b2bpress'); ?></li>
                        <li><?php _e('HPOS 兼容声明', 'b2bpress'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="b2bpress-about-section">
            <h3><?php _e('团队信息', 'b2bpress'); ?></h3>
            <p><?php _e('B2BPress由一支专注于B2B电子商务解决方案的团队开发和维护。我们致力于为B2B企业提供简单、高效、专业的电子商务工具。', 'b2bpress'); ?></p>
            <p><?php _e('如果您有任何问题或建议，请随时联系我们：', 'b2bpress'); ?></p>
            <ul>
                <li><?php _e('官方网站：', 'b2bpress'); ?> <a href="https://expansing.cc/b2bpress" target="_blank">https://expansing.cc/b2bpress</a></li>
                <li><?php _e('支持邮箱：', 'b2bpress'); ?> <a href="mailto:support@expansing.cc">support@expansing.cc</a></li>
            </ul>
        </div>
    </div>
</div>

<style>
.b2bpress-about-wrapper {
    max-width: 1000px;
    margin: 20px 0;
}

.b2bpress-about-header {
    display: flex;
    align-items: center;
    margin-bottom: 30px;
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.b2bpress-about-header-text h2 {
    margin-top: 0;
    margin-bottom: 5px;
    font-size: 24px;
}

.b2bpress-version {
    color: #666;
    margin-top: 0;
    font-size: 14px;
}

.b2bpress-about-section {
    background: #fff;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.b2bpress-about-section h3 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
    font-size: 18px;
}

.b2bpress-features {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.b2bpress-feature {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.b2bpress-feature:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.b2bpress-feature-icon {
    font-size: 30px;
    color: #0073aa;
    margin-bottom: 10px;
}

.b2bpress-feature h4 {
    margin-top: 0;
    margin-bottom: 10px;
    font-size: 16px;
}

.b2bpress-feature p {
    margin-bottom: 0;
    color: #666;
}

.b2bpress-changelog-item {
    margin-bottom: 20px;
}

.b2bpress-changelog-item h4 {
    margin-top: 0;
    margin-bottom: 10px;
    font-size: 16px;
}

.b2bpress-changelog-date {
    color: #666;
    font-size: 14px;
    font-weight: normal;
    margin-left: 10px;
}

.b2bpress-changelog-item ul {
    margin-top: 0;
    margin-left: 20px;
}

.b2bpress-changelog-item li {
    margin-bottom: 5px;
}

@media screen and (max-width: 782px) {
    .b2bpress-features {
        grid-template-columns: 1fr;
    }
}
</style> 