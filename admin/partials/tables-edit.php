<?php
/**
 * 表格编辑页面模板
 */
// 如果直接访问则退出
if (!defined('ABSPATH')) {
    exit;
}

// 获取表格ID
$table_id = isset($_GET['id']) ? absint($_GET['id']) : 0;

// 检查表格是否存在
$post = get_post($table_id);
if (!$post || $post->post_type !== 'b2bpress_table') {
    wp_die(__('表格不存在', 'b2bpress'));
}

// 获取表格设置
$table_generator = new B2BPress_Table_Generator();
$settings = $table_generator->get_table_settings($table_id);
$columns = $table_generator->get_table_columns($table_id);

// 获取表格标题
$title = $post->post_title;

// 获取表格分类
$category_id = isset($settings['category']) ? $settings['category'] : 0;

// 获取产品分类列表
$categories = get_terms(array(
    'taxonomy' => 'product_cat',
    'hide_empty' => false,
));

// 获取自定义样式
$custom_styles = isset($settings['custom_styles']) ? $settings['custom_styles'] : '';
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('编辑表格', 'b2bpress'); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=b2bpress-tables'); ?>" class="page-title-action">
        <?php _e('返回列表', 'b2bpress'); ?>
    </a>
    <hr class="wp-header-end">
    
    <div class="b2bpress-tables-edit-wrapper">
        <form id="b2bpress-table-edit-form" method="post">
            <input type="hidden" name="table_id" value="<?php echo esc_attr($table_id); ?>">
            <?php wp_nonce_field('b2bpress-table-edit', 'b2bpress-table-edit-nonce'); ?>
            
            <div class="b2bpress-tables-edit-main">
                <div class="postbox">
                    <div class="postbox-header">
                        <h2><?php _e('表格设置', 'b2bpress'); ?></h2>
                    </div>
                    <div class="inside">
                        <div class="b2bpress-tables-edit-field">
                            <label for="b2bpress-table-title"><?php _e('表格标题', 'b2bpress'); ?></label>
                            <input type="text" id="b2bpress-table-title" name="title" value="<?php echo esc_attr($title); ?>" required>
                        </div>
                        
                        <div class="b2bpress-tables-edit-field">
                            <label for="b2bpress-table-category"><?php _e('产品分类', 'b2bpress'); ?></label>
                            <select id="b2bpress-table-category" name="category">
                                <?php
                                foreach ($categories as $cat) {
                                    echo '<option value="' . esc_attr($cat->term_id) . '" ' . selected($category_id, $cat->term_id, false) . '>' . esc_html($cat->name) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="postbox">
                    <div class="postbox-header">
                        <h2><?php _e('表格列', 'b2bpress'); ?></h2>
                    </div>
                    <div class="inside">
                        <p><?php _e('选择要在表格中显示的列。拖动列可以重新排序。', 'b2bpress'); ?></p>
                        <p><?php _e('如需重新加载分类下的所有属性，请点击"加载属性"按钮。', 'b2bpress'); ?></p>
                        
                        <div class="b2bpress-tables-edit-field">
                            <button type="button" id="b2bpress-load-attributes" class="button">
                                <?php _e('加载属性', 'b2bpress'); ?>
                            </button>
                            <span id="b2bpress-load-attributes-spinner" class="spinner"></span>
                        </div>
                        
                        <div id="b2bpress-table-columns-wrapper">
                            <div id="b2bpress-table-columns-available" class="b2bpress-table-columns-container">
                                <h3><?php _e('可用列', 'b2bpress'); ?></h3>
                                <ul id="b2bpress-table-columns-available-list" class="b2bpress-table-columns-list">
                                    <?php
                                    // 只显示默认的产品数据列，属性列通过"加载属性"按钮获取
                                    $other_columns = array(
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
                                    
                                    foreach ($other_columns as $other_column) {
                                        $is_selected = false;
                                        
                                        foreach ($columns as $column) {
                                            if ($column['key'] === $other_column['key']) {
                                                $is_selected = true;
                                                break;
                                            }
                                        }
                                        
                                        if (!$is_selected) {
                                            echo '<li data-key="' . esc_attr($other_column['key']) . '" data-label="' . esc_attr($other_column['label']) . '" data-type="' . esc_attr($other_column['type']) . '">';
                                            echo '<span class="b2bpress-table-column-label">' . esc_html($other_column['label']) . '</span>';
                                            echo '<span class="b2bpress-table-column-type">' . __('产品数据', 'b2bpress') . '</span>';
                                            echo '<button type="button" class="button button-small b2bpress-table-column-add">' . __('添加', 'b2bpress') . '</button>';
                                            echo '</li>';
                                        }
                                    }
                                    
                                    // 添加提示信息
                                    if (empty($category_id)) {
                                        echo '<li class="b2bpress-table-column-notice">';
                                        echo '<p>' . __('请选择产品分类并点击"加载属性"按钮来获取该分类下的产品属性。', 'b2bpress') . '</p>';
                                        echo '</li>';
                                    } else {
                                        echo '<li class="b2bpress-table-column-notice">';
                                        echo '<p>' . __('点击"加载属性"按钮来获取该分类下的产品属性。', 'b2bpress') . '</p>';
                                        echo '</li>';
                                    }
                                    ?>
                                </ul>
                            </div>
                            
                            <div id="b2bpress-table-columns-selected" class="b2bpress-table-columns-container">
                                <h3><?php _e('已选列', 'b2bpress'); ?></h3>
                                <ul id="b2bpress-table-columns-selected-list" class="b2bpress-table-columns-list">
                                    <?php
                                    foreach ($columns as $column) {
                                        echo '<li data-key="' . esc_attr($column['key']) . '" data-label="' . esc_attr($column['label']) . '" data-type="' . esc_attr($column['type']) . '">';
                                        echo '<span class="b2bpress-table-column-label">' . esc_html($column['label']) . '</span>';
                                        echo '<span class="b2bpress-table-column-type">';
                                        
                                        if ($column['type'] === 'attribute') {
                                            echo __('属性', 'b2bpress');
                                        } else {
                                            echo __('产品数据', 'b2bpress');
                                        }
                                        
                                        echo '</span>';
                                        echo '<button type="button" class="button button-small b2bpress-table-column-remove">' . __('移除', 'b2bpress') . '</button>';
                                        echo '</li>';
                                    }
                                    ?>
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
                                    <input type="submit" name="save" id="publish" class="button button-primary button-large" value="<?php _e('更新', 'b2bpress'); ?>">
                                </div>
                                <div class="clear"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="postbox">
                    <div class="postbox-header">
                        <h2><?php _e('短代码', 'b2bpress'); ?></h2>
                    </div>
                    <div class="inside">
                        <p><?php _e('使用以下短代码在任何页面或文章中显示此表格：', 'b2bpress'); ?></p>
                        <code>[b2bpress_table id="<?php echo esc_attr($table_id); ?>"]</code>
                        <button type="button" class="button button-small b2bpress-copy-shortcode" data-shortcode="[b2bpress_table id=&quot;<?php echo esc_attr($table_id); ?>&quot;]">
                            <?php _e('复制', 'b2bpress'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="postbox">
                    <div class="postbox-header">
                        <h2><?php _e('预览', 'b2bpress'); ?></h2>
                    </div>
                    <div class="inside">
                        <a href="<?php echo add_query_arg(array('b2bpress_table_preview' => $table_id), home_url()); ?>" target="_blank" class="button">
                            <?php _e('预览表格', 'b2bpress'); ?>
                        </a>
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
    $('#b2bpress-load-attributes').on('click', function() {
        var $button = $(this);
        var $spinner = $('#b2bpress-load-attributes-spinner');
        var category = $('#b2bpress-table-category').val();
        
        if (!category) {
            alert('<?php _e('请先选择产品分类', 'b2bpress'); ?>');
            return;
        }
        
        $button.prop('disabled', true);
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
                $button.prop('disabled', false);
                $spinner.css('visibility', 'hidden');
                
                if (response.success) {
                    // 保存当前已选列
                    var selectedColumns = [];
                    $('#b2bpress-table-columns-selected-list li').each(function() {
                        selectedColumns.push({
                            key: $(this).data('key'),
                            label: $(this).data('label'),
                            type: $(this).data('type')
                        });
                    });
                    
                    // 清除现有可用列
                    $('#b2bpress-table-columns-available-list').empty();
                    
                    // 添加默认列
                    var defaultColumns = [
                        {key: 'sku', label: '<?php _e('SKU', 'b2bpress'); ?>', type: 'sku'},
                        {key: 'price', label: '<?php _e('价格', 'b2bpress'); ?>', type: 'price'},
                        {key: 'stock', label: '<?php _e('库存状态', 'b2bpress'); ?>', type: 'stock'}
                    ];
                    
                    $.each(defaultColumns, function(index, column) {
                        // 检查该列是否已被选中
                        var isSelected = false;
                        $.each(selectedColumns, function(i, selectedColumn) {
                            if (selectedColumn.key === column.key) {
                                isSelected = true;
                                return false;
                            }
                        });
                        
                        if (!isSelected) {
                            var $item = $('<li></li>')
                                .attr('data-key', column.key)
                                .attr('data-label', column.label)
                                .attr('data-type', column.type);
                            
                            $item.append(
                                $('<span></span>')
                                    .addClass('b2bpress-table-column-label')
                                    .text(column.label)
                            );
                            
                            $item.append(
                                $('<span></span>')
                                    .addClass('b2bpress-table-column-type')
                                    .text('<?php _e('产品数据', 'b2bpress'); ?>')
                            );
                            
                            $item.append(
                                $('<button></button>')
                                    .attr('type', 'button')
                                    .addClass('button button-small b2bpress-table-column-add')
                                    .text('<?php _e('添加', 'b2bpress'); ?>')
                            );
                            
                            $('#b2bpress-table-columns-available-list').append($item);
                        }
                    });
                    
                    // 添加属性列
                    if (response.data && response.data.length > 0) {
                        $.each(response.data, function(index, attribute) {
                            // 检查该属性是否已被选中
                            var isSelected = false;
                            $.each(selectedColumns, function(i, selectedColumn) {
                                if (selectedColumn.key === attribute.key) {
                                    isSelected = true;
                                    return false;
                                }
                            });
                            
                            if (!isSelected) {
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
                            }
                        });
                        
                        // 显示成功消息
                        $('<div class="notice notice-success is-dismissible"><p><?php _e('属性加载成功！请选择需要显示的属性。', 'b2bpress'); ?></p></div>')
                            .insertAfter('#b2bpress-load-attributes')
                            .delay(3000)
                            .fadeOut(function() {
                                $(this).remove();
                            });
                    } else {
                        // 显示无属性消息
                        $('<div class="notice notice-warning is-dismissible"><p><?php _e('未找到属性。请确保该分类下的产品有设置属性。', 'b2bpress'); ?></p></div>')
                            .insertAfter('#b2bpress-load-attributes')
                            .delay(3000)
                            .fadeOut(function() {
                                $(this).remove();
                            });
                    }
                } else {
                    // 显示错误消息
                    var errorMsg = response.data || '<?php _e('加载属性时发生错误', 'b2bpress'); ?>';
                    $('<div class="notice notice-error is-dismissible"><p>' + errorMsg + '</p></div>')
                        .insertAfter('#b2bpress-load-attributes')
                        .delay(5000)
                        .fadeOut(function() {
                            $(this).remove();
                        });
                }
            },
            error: function(xhr, status, error) {
                $button.prop('disabled', false);
                $spinner.css('visibility', 'hidden');
                
                // 显示错误消息
                var errorMsg = '<?php _e('加载属性时发生错误', 'b2bpress'); ?>';
                if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMsg += ': ' + xhr.responseJSON.data;
                } else if (error) {
                    errorMsg += ': ' + error;
                }
                
                $('<div class="notice notice-error is-dismissible"><p>' + errorMsg + '</p></div>')
                    .insertAfter('#b2bpress-load-attributes')
                    .delay(5000)
                    .fadeOut(function() {
                        $(this).remove();
                    });
            }
        });
    });
    
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
    
    // 初始化列数据
    updateColumnsData();
    
    // 表单提交
    $('#b2bpress-table-edit-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitButton = $('#publish');
        var $spinner = $submitButton.prev('.spinner');
        
        $submitButton.prop('disabled', true);
        $spinner.css('visibility', 'visible');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'b2bpress_save_table',
                nonce: b2bpressAdmin.nonce,
                table_id: $form.find('input[name="table_id"]').val(),
                title: $form.find('input[name="title"]').val(),
                category: $form.find('select[name="category"]').val(),
                columns: getColumns()
            },
            success: function(response) {
                if (response.success) {
                    $submitButton.prop('disabled', false);
                    $spinner.css('visibility', 'hidden');
                    
                    alert('<?php _e('表格已保存', 'b2bpress'); ?>');
                } else {
                    $submitButton.prop('disabled', false);
                    $spinner.css('visibility', 'hidden');
                    
                    alert(response.data || '<?php _e('保存表格时发生错误', 'b2bpress'); ?>');
                }
            },
            error: function() {
                $submitButton.prop('disabled', false);
                $spinner.css('visibility', 'hidden');
                
                alert('<?php _e('保存表格时发生错误', 'b2bpress'); ?>');
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
    
    // 复制短代码
    $('.b2bpress-copy-shortcode').on('click', function() {
        var $button = $(this);
        var shortcode = $button.data('shortcode');
        
        var $temp = $('<input>');
        $('body').append($temp);
        $temp.val(shortcode).select();
        document.execCommand('copy');
        $temp.remove();
        
        var originalText = $button.text();
        $button.text('<?php _e('已复制', 'b2bpress'); ?>');
        
        setTimeout(function() {
            $button.text(originalText);
        }, 2000);
    });
});
</script> 