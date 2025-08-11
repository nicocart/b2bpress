<?php
/**
 * 表格管理页面模板
 */
// 如果直接访问则退出
if (!defined('ABSPATH')) {
    exit;
}

// 获取当前操作
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
$table_id = isset($_GET['id']) ? absint($_GET['id']) : 0;

// 处理不同的操作
if ($action === 'edit' && $table_id > 0) {
    // 编辑表格
    require_once B2BPRESS_PLUGIN_DIR . 'admin/partials/tables-edit.php';
} elseif ($action === 'new') {
    // 新建表格
    require_once B2BPRESS_PLUGIN_DIR . 'admin/partials/tables-new.php';
} else {
    // 表格列表
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
        <a href="<?php echo admin_url('admin.php?page=b2bpress-tables&action=new'); ?>" class="page-title-action">
            <?php _e('添加表格', 'b2bpress'); ?>
        </a>
        <button type="button" class="page-title-action b2bpress-refresh-cache" data-id="0">
            <?php _e('刷新所有缓存', 'b2bpress'); ?>
        </button>
        <hr class="wp-header-end">
        
        <div class="b2bpress-tables-wrapper">
            <?php
            // 获取表格列表
            $args = array(
                'post_type' => 'b2bpress_table',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'orderby' => 'date',
                'order' => 'DESC',
            );
            
            $query = new WP_Query($args);
            
            if ($query->have_posts()) :
            ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" class="manage-column column-title column-primary">
                            <?php _e('标题', 'b2bpress'); ?>
                        </th>
                        <th scope="col" class="manage-column column-category">
                            <?php _e('分类', 'b2bpress'); ?>
                        </th>
                        <th scope="col" class="manage-column column-style">
                            <?php _e('样式', 'b2bpress'); ?>
                        </th>
                        <th scope="col" class="manage-column column-shortcode">
                            <?php _e('短代码', 'b2bpress'); ?>
                        </th>
                        <th scope="col" class="manage-column column-date">
                            <?php _e('日期', 'b2bpress'); ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $table_generator = new B2BPress_Table_Generator();
                    
                    while ($query->have_posts()) :
                        $query->the_post();
                        $table_id = get_the_ID();
                        $settings = $table_generator->get_table_settings($table_id);
                        $category_id = isset($settings['category']) ? $settings['category'] : 0;
                        $category = get_term($category_id, 'product_cat');
                        $category_name = $category ? $category->name : '';
                        $style = isset($settings['style']) ? $settings['style'] : 'default';
                        $style_labels = array(
                            'default' => __('默认', 'b2bpress'),
                            'striped' => __('条纹', 'b2bpress'),
                            'card' => __('卡片', 'b2bpress'),
                        );
                        $style_label = isset($style_labels[$style]) ? $style_labels[$style] : $style;
                    ?>
                    <tr>
                        <td class="title column-title column-primary">
                            <strong>
                                <a href="<?php echo admin_url('admin.php?page=b2bpress-tables&action=edit&id=' . $table_id); ?>" class="row-title">
                                    <?php echo esc_html(get_the_title()); ?>
                                </a>
                            </strong>
                            <div class="row-actions">
                                <span class="edit">
                                    <a href="<?php echo admin_url('admin.php?page=b2bpress-tables&action=edit&id=' . $table_id); ?>">
                                        <?php _e('编辑', 'b2bpress'); ?>
                                    </a> |
                                </span>
                                <span class="refresh">
                                    <a href="#" class="b2bpress-refresh-cache" data-id="<?php echo esc_attr($table_id); ?>">
                                        <?php _e('刷新缓存', 'b2bpress'); ?>
                                    </a> |
                                </span>
                                <span class="trash">
                                    <a href="#" class="b2bpress-delete-table" data-id="<?php echo esc_attr($table_id); ?>">
                                        <?php _e('删除', 'b2bpress'); ?>
                                    </a>
                                </span>
                            </div>
                        </td>
                        <td class="category column-category">
                            <?php echo esc_html($category_name); ?>
                        </td>
                        <td class="style column-style">
                            <?php echo esc_html($style_label); ?>
                        </td>
                        <td class="shortcode column-shortcode">
                            <code>[b2bpress_table id="<?php echo esc_attr($table_id); ?>"]</code>
                            <button type="button" class="button button-small b2bpress-copy-shortcode" data-shortcode="[b2bpress_table id=&quot;<?php echo esc_attr($table_id); ?>&quot;]">
                                <?php _e('复制', 'b2bpress'); ?>
                            </button>
                        </td>
                        <td class="date column-date">
                            <?php echo get_the_date(); ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th scope="col" class="manage-column column-title column-primary">
                            <?php _e('标题', 'b2bpress'); ?>
                        </th>
                        <th scope="col" class="manage-column column-category">
                            <?php _e('分类', 'b2bpress'); ?>
                        </th>
                        <th scope="col" class="manage-column column-style">
                            <?php _e('样式', 'b2bpress'); ?>
                        </th>
                        <th scope="col" class="manage-column column-shortcode">
                            <?php _e('短代码', 'b2bpress'); ?>
                        </th>
                        <th scope="col" class="manage-column column-date">
                            <?php _e('日期', 'b2bpress'); ?>
                        </th>
                    </tr>
                </tfoot>
            </table>
            <?php else : ?>
            <div class="b2bpress-no-tables">
                <p><?php _e('暂无表格。', 'b2bpress'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=b2bpress-tables&action=new'); ?>" class="button button-primary">
                    <?php _e('创建第一个表格', 'b2bpress'); ?>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
?> 