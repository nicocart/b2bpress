<?php
// 如果不是通过 WordPress 卸载钩子调用，则退出
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// 删除插件选项
delete_option('b2bpress_options');
delete_option('b2bpress_last_changed');

// 清理瞬态（仅删除与 b2bpress 相关的键）
global $wpdb;
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
        '_transient_b2bpress_%',
        '_transient_timeout_b2bpress_%'
    )
);

// 清理对象缓存索引
if (function_exists('wp_cache_delete')) {
    wp_cache_delete('b2bpress_object_cache_keys_index', 'b2bpress');
}

// 可选：删除自定义文章类型数据（如果插件所有表格均应清理）
// $tables = get_posts(array('post_type' => 'b2bpress_table', 'numberposts' => -1, 'fields' => 'ids'));
// foreach ($tables as $post_id) { wp_delete_post($post_id, true); }


