<?php
/**
 * 仪表盘页面模板
 */
// 如果直接访问则退出
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="b2bpress-dashboard-wrapper">
        <div class="b2bpress-dashboard-header">
            <div class="b2bpress-dashboard-welcome">
                <h2><?php _e('欢迎使用 B2BPress', 'b2bpress'); ?></h2>
                <p><?php _e('B2BPress 是一个基于 WooCommerce 的 B2B 电子商务解决方案，专为批发和 B2B 业务设计。', 'b2bpress'); ?></p>
            </div>
        </div>
        
        <div class="b2bpress-dashboard-main">
            <div class="b2bpress-dashboard-column">
                <div class="b2bpress-dashboard-card">
                    <h3><?php _e('快速入门', 'b2bpress'); ?></h3>
                    <ul>
                        <li>
                            <a href="<?php echo admin_url('admin.php?page=b2bpress-wizard'); ?>">
                                <?php _e('运行设置向导', 'b2bpress'); ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo admin_url('admin.php?page=b2bpress-settings'); ?>">
                                <?php _e('配置插件设置', 'b2bpress'); ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo admin_url('admin.php?page=b2bpress-tables'); ?>">
                                <?php _e('创建产品表格', 'b2bpress'); ?>
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="b2bpress-dashboard-card">
                    <h3><?php _e('使用短代码', 'b2bpress'); ?></h3>
                    <p><?php _e('使用以下短代码在任何页面或文章中显示产品表格：', 'b2bpress'); ?></p>
                    <code>[b2bpress_table id="123" style="striped" per_page="20"]</code>
                    <p><?php _e('或者使用分类显示临时表格：', 'b2bpress'); ?></p>
                    <code>[b2bpress_table category="category-slug" style="striped" per_page="20"]</code>
                </div>
            </div>
            
            <div class="b2bpress-dashboard-column">
                <div class="b2bpress-dashboard-card">
                    <h3><?php _e('统计信息', 'b2bpress'); ?></h3>
                    <?php
                    // 获取表格数量
                    $tables_count = wp_count_posts('b2bpress_table');
                    $tables_count = isset($tables_count->publish) ? $tables_count->publish : 0;
                    
                    // 获取产品数量
                    $products_count = wp_count_posts('product')->publish;
                    
                    // 获取产品分类数量
                    $categories_count = wp_count_terms('product_cat', array('hide_empty' => false));
                    ?>
                    <ul class="b2bpress-dashboard-stats">
                        <li>
                            <span class="b2bpress-dashboard-stat-count"><?php echo esc_html($tables_count); ?></span>
                            <span class="b2bpress-dashboard-stat-label"><?php _e('表格', 'b2bpress'); ?></span>
                        </li>
                        <li>
                            <span class="b2bpress-dashboard-stat-count"><?php echo esc_html($products_count); ?></span>
                            <span class="b2bpress-dashboard-stat-label"><?php _e('产品', 'b2bpress'); ?></span>
                        </li>
                        <li>
                            <span class="b2bpress-dashboard-stat-count"><?php echo esc_html($categories_count); ?></span>
                            <span class="b2bpress-dashboard-stat-label"><?php _e('分类', 'b2bpress'); ?></span>
                        </li>
                    </ul>
                </div>
                
                <div class="b2bpress-dashboard-card">
                    <h3><?php _e('最近的表格', 'b2bpress'); ?></h3>
                    <?php
                    // 获取最近的表格
                    $recent_tables = get_posts(array(
                        'post_type' => 'b2bpress_table',
                        'post_status' => 'publish',
                        'posts_per_page' => 5,
                        'orderby' => 'date',
                        'order' => 'DESC',
                    ));
                    
                    if (!empty($recent_tables)) :
                    ?>
                    <ul class="b2bpress-dashboard-recent">
                        <?php foreach ($recent_tables as $table) : ?>
                        <li>
                            <a href="<?php echo admin_url('admin.php?page=b2bpress-tables&action=edit&id=' . $table->ID); ?>">
                                <?php echo esc_html($table->post_title); ?>
                            </a>
                            <span class="b2bpress-dashboard-date">
                                <?php echo date_i18n(get_option('date_format'), strtotime($table->post_date)); ?>
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else : ?>
                    <p><?php _e('暂无表格。', 'b2bpress'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=b2bpress-tables&action=new'); ?>" class="button button-primary">
                        <?php _e('创建第一个表格', 'b2bpress'); ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div> 