<?php
/**
 * 新建表格页面模板
 */
// 如果直接访问则退出
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('新建表格', 'b2bpress'); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=b2bpress-tables'); ?>" class="page-title-action">
        <?php _e('返回列表', 'b2bpress'); ?>
    </a>
    <hr class="wp-header-end">
    
    <div class="b2bpress-tables-edit-wrapper">
        <form id="b2bpress-table-new-form" method="post">
            <input type="hidden" name="table_id" value="0">
            <?php wp_nonce_field('b2bpress-table-new', 'b2bpress-table-new-nonce'); ?>
            
            <div class="b2bpress-tables-edit-main">
                <div class="postbox">
                    <div class="postbox-header">
                        <h2><?php _e('表格设置', 'b2bpress'); ?></h2>
                    </div>
                    <div class="inside">
                        <div class="b2bpress-tables-edit-field">
                            <label for="b2bpress-table-title"><?php _e('表格标题', 'b2bpress'); ?></label>
                            <input type="text" id="b2bpress-table-title" name="title" value="" required>
                        </div>
                        
                        <div class="b2bpress-tables-edit-field">
                            <label for="b2bpress-table-category"><?php _e('产品分类', 'b2bpress'); ?></label>
                            <select id="b2bpress-table-category" name="category" required>
                                <option value=""><?php _e('选择分类', 'b2bpress'); ?></option>
                                <?php
                                $terms = get_terms(array(
                                    'taxonomy' => 'product_cat',
                                    'hide_empty' => false,
                                ));
                                
                                if (!is_wp_error($terms) && !empty($terms)) {
                                    foreach ($terms as $term) {
                                        echo '<option value="' . esc_attr($term->term_id) . '">' . esc_html($term->name) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                            <span id="b2bpress-category-loading-spinner" class="spinner"></span>
                            <p class="description"><?php _e('选择分类后将自动加载该分类下的产品属性', 'b2bpress'); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="postbox">
                    <div class="postbox-header">
                        <h2><?php _e('表格列', 'b2bpress'); ?></h2>
                    </div>
                    <div class="inside">
                        <p><?php _e('选择要在表格中显示的列。拖动列可以重新排序。', 'b2bpress'); ?></p>
                        
                        <div id="b2bpress-table-columns-wrapper">
                            <div id="b2bpress-table-columns-available" class="b2bpress-table-columns-container">
                                <h3><?php _e('可用列', 'b2bpress'); ?></h3>
                                <ul id="b2bpress-table-columns-available-list" class="b2bpress-table-columns-list">
                                    <?php
                                    // 添加默认列
                                    $default_columns = array(
                                        array(
                                            'key' => 'sku',
                                            'label' => __('SKU', 'b2bpress'),
                                            'type' => 'sku',
                                        ),
                                        array(
                                            'key' => 'price',
                                            'label' => __('价格', 'b2bpress'),
                                            'type' => 'price',
                                        ),
                                        array(
                                            'key' => 'stock',
                                            'label' => __('库存状态', 'b2bpress'),
                                            'type' => 'stock',
                                        ),
                                    );
                                    
                                    foreach ($default_columns as $column) {
                                        echo '<li data-key="' . esc_attr($column['key']) . '" data-label="' . esc_attr($column['label']) . '" data-type="' . esc_attr($column['type']) . '">';
                                        echo '<span class="b2bpress-table-column-label">' . esc_html($column['label']) . '</span>';
                                        echo '<span class="b2bpress-table-column-type">' . __('产品数据', 'b2bpress') . '</span>';
                                        echo '<button type="button" class="button button-small b2bpress-table-column-add">' . __('添加', 'b2bpress') . '</button>';
                                        echo '</li>';
                                    }
                                    ?>
                                </ul>
                            </div>
                            
                            <div id="b2bpress-table-columns-selected" class="b2bpress-table-columns-container">
                                <h3><?php _e('已选列', 'b2bpress'); ?></h3>
                                <ul id="b2bpress-table-columns-selected-list" class="b2bpress-table-columns-list">
                                </ul>
                            </div>
                        </div>
                        
                        <div id="b2bpress-table-columns-data"></div>
                    </div>
                </div>
            </div>
            
            <div class="b2bpress-tables-edit-sidebar">
                <div class="postbox">
                    <div class="postbox-header">
                        <h2><?php _e('保存', 'b2bpress'); ?></h2>
                    </div>
                    <div class="inside">
                        <div class="submitbox">
                            <div id="major-publishing-actions">
                                <div id="publishing-action">
                                    <span class="spinner"></span>
                                    <input type="submit" name="save" id="publish" class="button button-primary button-large" value="<?php _e('创建表格', 'b2bpress'); ?>">
                                </div>
                                <div class="clear"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="postbox">
                    <div class="postbox-header">
                        <h2><?php _e('使用说明', 'b2bpress'); ?></h2>
                    </div>
                    <div class="inside">
                        <p><?php _e('创建表格后，您可以使用短代码在任何页面或文章中显示表格。', 'b2bpress'); ?></p>
                        <p><?php _e('您还可以在Elementor页面构建器中使用B2BPress表格小部件。', 'b2bpress'); ?></p>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // 初始化排序
    $('#b2bpress-table-columns-selected-list').sortable({
        placeholder: 'b2bpress-table-column-placeholder',
        update: function() {
            updateColumnsData();
        }
    });
    
    // 加载属性
    $('#b2bpress-table-category').on('change', function() {
        var category = $(this).val();
        
        if (!category) {
            return;
        }
        
        loadAttributes(category);
    });
    
    // 加载属性函数
    function loadAttributes(category) {
        var $spinner = $('#b2bpress-category-loading-spinner');
        
        $spinner.css('visibility', 'visible');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'b2bpress_get_category_attributes',
                nonce: b2bpressAdmin.nonce,
                category: category
            },
            success: function(response) {
                $spinner.css('visibility', 'hidden');
                
                if (response.success) {
                    // 清除现有属性
                    $('#b2bpress-table-columns-available-list li[data-type="attribute"]').remove();
                    
                    // 添加新属性
                    if (response.data && response.data.length > 0) {
                        $.each(response.data, function(index, attribute) {
                            var $item = $('<li></li>')
                                .attr('data-key', attribute.key)
                                .attr('data-label', attribute.label)
                                .attr('data-type', 'attribute');
                            
                            $item.append(
                                $('<span></span>')
                                    .addClass('b2bpress-table-column-label')
                                    .text(attribute.label)
                            );
                            
                            // 区分全局属性和自定义属性
                            var attrType = attribute.key.indexOf('custom_') === 0 ? 
                                '<?php _e('自定义属性', 'b2bpress'); ?>' : 
                                '<?php _e('全局属性', 'b2bpress'); ?>';
                            
                            $item.append(
                                $('<span></span>')
                                    .addClass('b2bpress-table-column-type')
                                    .text(attrType)
                            );
                            
                            $item.append(
                                $('<button></button>')
                                    .attr('type', 'button')
                                    .addClass('button button-small b2bpress-table-column-add')
                                    .text('<?php _e('添加', 'b2bpress'); ?>')
                            );
                            
                            $('#b2bpress-table-columns-available-list').append($item);
                        });
                        
                        // 显示成功消息
                        $('<div class="notice notice-success is-dismissible"><p><?php _e('属性加载成功！请选择需要显示的属性。', 'b2bpress'); ?></p></div>')
                            .insertAfter('#b2bpress-table-category')
                            .delay(3000)
                            .fadeOut(function() {
                                $(this).remove();
                            });
                    } else {
                        // 显示无属性消息
                        $('<div class="notice notice-warning is-dismissible"><p><?php _e('未找到属性。请确保该分类下的产品有设置属性。', 'b2bpress'); ?></p></div>')
                            .insertAfter('#b2bpress-table-category')
                            .delay(3000)
                            .fadeOut(function() {
                                $(this).remove();
                            });
                    }
                } else {
                    // 显示错误消息
                    var errorMsg = response.data || '<?php _e('加载属性时发生错误', 'b2bpress'); ?>';
                    $('<div class="notice notice-error is-dismissible"><p>' + errorMsg + '</p></div>')
                        .insertAfter('#b2bpress-table-category')
                        .delay(5000)
                        .fadeOut(function() {
                            $(this).remove();
                        });
                }
            },
            error: function(xhr, status, error) {
                $spinner.css('visibility', 'hidden');
                
                // 显示错误消息
                var errorMsg = '<?php _e('加载属性时发生错误', 'b2bpress'); ?>';
                if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMsg += ': ' + xhr.responseJSON.data;
                } else if (error) {
                    errorMsg += ': ' + error;
                }
                
                $('<div class="notice notice-error is-dismissible"><p>' + errorMsg + '</p></div>')
                    .insertAfter('#b2bpress-table-category')
                    .delay(5000)
                    .fadeOut(function() {
                        $(this).remove();
                    });
            }
        });
    }
    
    // 添加列
    $(document).on('click', '.b2bpress-table-column-add', function() {
        var $item = $(this).closest('li');
        var key = $item.data('key');
        var label = $item.data('label');
        var type = $item.data('type');
        
        var $newItem = $item.clone();
        $newItem.find('.b2bpress-table-column-add').removeClass('b2bpress-table-column-add').addClass('b2bpress-table-column-remove').text('<?php _e('移除', 'b2bpress'); ?>');
        
        $('#b2bpress-table-columns-selected-list').append($newItem);
        $item.remove();
        
        updateColumnsData();
    });
    
    // 移除列
    $(document).on('click', '.b2bpress-table-column-remove', function() {
        var $item = $(this).closest('li');
        var key = $item.data('key');
        var label = $item.data('label');
        var type = $item.data('type');
        
        var $newItem = $item.clone();
        $newItem.find('.b2bpress-table-column-remove').removeClass('b2bpress-table-column-remove').addClass('b2bpress-table-column-add').text('<?php _e('添加', 'b2bpress'); ?>');
        
        $('#b2bpress-table-columns-available-list').append($newItem);
        $item.remove();
        
        updateColumnsData();
    });
    
    // 更新列数据
    function updateColumnsData() {
        var columns = [];
        
        $('#b2bpress-table-columns-selected-list li').each(function() {
            var $item = $(this);
            
            columns.push({
                key: $item.data('key'),
                label: $item.data('label'),
                type: $item.data('type')
            });
        });
        
        $('#b2bpress-table-columns-data').html('');
        
        $.each(columns, function(index, column) {
            $('#b2bpress-table-columns-data').append(
                '<input type="hidden" name="columns[' + index + '][key]" value="' + column.key + '">' +
                '<input type="hidden" name="columns[' + index + '][label]" value="' + column.label + '">' +
                '<input type="hidden" name="columns[' + index + '][type]" value="' + column.type + '">'
            );
        });
    }
    
    // 表单提交
    $('#b2bpress-table-new-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitButton = $('#publish');
        var $spinner = $submitButton.prev('.spinner');
        
        // 检查是否有选择列
        if ($('#b2bpress-table-columns-selected-list li').length === 0) {
            alert('<?php _e('请至少选择一个列', 'b2bpress'); ?>');
            return;
        }
        
        $submitButton.prop('disabled', true);
        $spinner.css('visibility', 'visible');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'b2bpress_save_table',
                nonce: b2bpressAdmin.nonce,
                table_id: 0,
                title: $form.find('input[name="title"]').val(),
                category: $form.find('select[name="category"]').val(),
                columns: getColumns()
            },
            success: function(response) {
                if (response.success) {
                    // 重定向到编辑页面
                    window.location.href = '<?php echo admin_url('admin.php?page=b2bpress-tables&action=edit&id='); ?>' + response.data.table_id;
                } else {
                    $submitButton.prop('disabled', false);
                    $spinner.css('visibility', 'hidden');
                    
                    alert(response.data || '<?php _e('创建表格时发生错误', 'b2bpress'); ?>');
                }
            },
            error: function() {
                $submitButton.prop('disabled', false);
                $spinner.css('visibility', 'hidden');
                
                alert('<?php _e('创建表格时发生错误', 'b2bpress'); ?>');
            }
        });
    });
    
    // 获取列数据
    function getColumns() {
        var columns = [];
        
        $('#b2bpress-table-columns-selected-list li').each(function() {
            var $item = $(this);
            
            columns.push({
                key: $item.data('key'),
                label: $item.data('label'),
                type: $item.data('type')
            });
        });
        
        return columns;
    }
});
</script> 