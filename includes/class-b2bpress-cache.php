<?php
/**
 * B2BPress 缓存类
 * 
 * 用于管理表格数据缓存
 */
class B2BPress_Cache {
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
        // 添加AJAX刷新缓存
        add_action('wp_ajax_b2bpress_refresh_cache', array($this, 'ajax_refresh_cache'));
        
        // 添加定时任务
        add_action('b2bpress_refresh_cache', array($this, 'refresh_all_cache'));
        
        // 注册激活钩子
        add_action('b2bpress_activated', array($this, 'schedule_cache_refresh'));
        
        // 注册停用钩子
        add_action('b2bpress_deactivated', array($this, 'unschedule_cache_refresh'));
    }
    
    /**
     * 获取缓存值
     *
     * @param string $key 缓存键
     * @return mixed 缓存值或false
     */
    public function get($key) {
        $this->log_debug('尝试获取缓存: ' . $key);
        
        try {
            // 尝试从对象缓存获取
            $value = wp_cache_get($key, 'b2bpress');
            if ($value !== false) {
                $this->log_debug('从对象缓存获取成功: ' . $key);
                return $value;
            }
            
            // 尝试从瞬态缓存获取
            $transient_key = 'b2bpress_' . $key;
            $this->log_debug('从瞬态缓存获取: ' . $transient_key);
            $value = get_transient($transient_key);
            if ($value !== false) {
                // 同时存入对象缓存
                $this->log_debug('从瞬态缓存获取成功，同时存入对象缓存: ' . $key);
                wp_cache_set($key, $value, 'b2bpress');
                $this->index_object_cache_key($key);
                return $value;
            }
            
            $this->log_debug('缓存未命中: ' . $key);
            return false;
        } catch (Exception $e) {
            $this->log_debug('获取缓存过程中发生异常', $e->getMessage());
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('B2BPress获取缓存错误: ' . $e->getMessage() . ' [堆栈跟踪: ' . $e->getTraceAsString() . ']');
            }
            return false;
        }
    }
    
    /**
     * 设置缓存值
     *
     * @param string $key 缓存键
     * @param mixed $value 缓存值
     * @param int $expiration 过期时间（秒）
     * @return bool 是否成功
     */
    public function set($key, $value, $expiration = 3600) {
        $this->log_debug('尝试设置缓存: ' . $key . ', 过期时间: ' . $expiration . '秒');
        
        try {
            // 存入对象缓存
            $object_cache_result = wp_cache_set($key, $value, 'b2bpress', $expiration);
            if ($object_cache_result === false) {
                $this->log_debug('存入对象缓存失败: ' . $key);
            } else {
                $this->log_debug('存入对象缓存成功: ' . $key);
                $this->index_object_cache_key($key);
            }
            
            // 存入瞬态缓存
            $transient_key = 'b2bpress_' . $key;
            $this->log_debug('存入瞬态缓存: ' . $transient_key);
            $transient_result = set_transient($transient_key, $value, $expiration);
            
            if ($transient_result === false) {
                $this->log_debug('存入瞬态缓存失败: ' . $transient_key);
                
                // 如果瞬态缓存设置失败，检查是否是因为数据太大
                if (is_string($value) && strlen($value) > 1000000) {
                    $this->log_debug('缓存数据可能太大，尝试压缩');
                    
                    // 尝试压缩数据
                    $compressed_value = gzcompress($value);
                    if ($compressed_value !== false) {
                        $this->log_debug('数据压缩成功，原始大小: ' . strlen($value) . '，压缩后: ' . strlen($compressed_value));
                        $transient_result = set_transient($transient_key . '_compressed', $compressed_value, $expiration);
                        
                        if ($transient_result) {
                            $this->log_debug('压缩数据存入瞬态缓存成功');
                            return true;
                        } else {
                            $this->log_debug('压缩数据存入瞬态缓存失败');
                        }
                    } else {
                        $this->log_debug('数据压缩失败');
                    }
                }
            } else {
                $this->log_debug('存入瞬态缓存成功: ' . $transient_key);
            }
            
            return $transient_result;
        } catch (Exception $e) {
            $this->log_debug('设置缓存过程中发生异常', $e->getMessage());
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('B2BPress设置缓存错误: ' . $e->getMessage() . ' [堆栈跟踪: ' . $e->getTraceAsString() . ']');
            }
            return false;
        }
    }
    
    /**
     * 删除缓存
     *
     * @param string $key 缓存键
     * @return bool 是否成功
     */
    public function delete($key) {
        $this->log_debug('尝试删除缓存: ' . $key);
        
        try {
            // 删除对象缓存
            $object_cache_result = wp_cache_delete($key, 'b2bpress');
            if ($object_cache_result === false) {
                $this->log_debug('删除对象缓存失败: ' . $key);
            } else {
                $this->log_debug('删除对象缓存成功: ' . $key);
                // 同步更新索引
                $index_key = 'b2bpress_object_cache_keys_index';
                $keys = wp_cache_get($index_key, 'b2bpress');
                if (is_array($keys) && isset($keys[$key])) {
                    unset($keys[$key]);
                    wp_cache_set($index_key, $keys, 'b2bpress');
                }
            }
            
            // 删除瞬态缓存
            $transient_key = 'b2bpress_' . $key;
            $this->log_debug('删除瞬态缓存: ' . $transient_key);
            $transient_result = delete_transient($transient_key);
            
            // 同时尝试删除可能存在的压缩版本
            $compressed_transient_key = $transient_key . '_compressed';
            $this->log_debug('删除压缩瞬态缓存: ' . $compressed_transient_key);
            $compressed_result = delete_transient($compressed_transient_key);
            
            if ($transient_result === false && $compressed_result === false) {
                $this->log_debug('删除瞬态缓存失败: ' . $transient_key);
            } else {
                $this->log_debug('删除瞬态缓存成功: ' . $transient_key);
            }
            
            $result_any = $transient_result || $compressed_result || $object_cache_result;
            if ($result_any) {
                $this->bump_last_changed();
            }
            return $result_any;
        } catch (Exception $e) {
            $this->log_debug('删除缓存过程中发生异常', $e->getMessage());
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('B2BPress删除缓存错误: ' . $e->getMessage() . ' [堆栈跟踪: ' . $e->getTraceAsString() . ']');
            }
            return false;
        }
    }
    
    /**
     * 删除指定前缀的缓存
     *
     * @param string $prefix 缓存键前缀
     * @return void
     */
    public function delete_by_prefix($prefix) {
        global $wpdb;
        
        $this->log_debug('开始删除前缀为 ' . $prefix . ' 的缓存');
        
        try {
            // 仅在后台、定时任务或 WP-CLI 中执行，避免前台大范围删除瞬态
            if (!is_admin() && !(defined('DOING_CRON') && DOING_CRON) && !(defined('WP_CLI') && WP_CLI)) {
                $this->log_debug('跳过删除：非后台/非CRON/非WP-CLI环境');
                return;
            }
            // 删除瞬态缓存（仅删除与b2bpress相关的前缀）
            $query = $wpdb->prepare(
                "DELETE FROM $wpdb->options WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_b2bpress_' . $prefix . '%',
                '_transient_timeout_b2bpress_' . $prefix . '%'
            );
            
            // 避免在日志中直出完整SQL
            $this->log_debug('执行前缀删除瞬态缓存（语句已屏蔽），前缀: ' . $prefix);
            $result = $wpdb->query($query);
            
            if ($result === false) {
                $this->log_debug('删除瞬态缓存失败: ' . $wpdb->last_error);
                throw new Exception('删除瞬态缓存失败: ' . $wpdb->last_error);
            }
            
            $this->log_debug('删除瞬态缓存成功，影响行数: ' . $result);

            // 精确删除对象缓存键：通过维护一个索引列表来定位并删除
            $this->delete_object_cache_keys_by_prefix($prefix);
            
            $this->log_debug('前缀为 ' . $prefix . ' 的缓存删除完成');
            $this->bump_last_changed();
        } catch (Exception $e) {
            $this->log_debug('删除前缀缓存过程中发生异常', $e->getMessage());
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('B2BPress删除前缀缓存错误: ' . $e->getMessage() . ' [堆栈跟踪: ' . $e->getTraceAsString() . ']');
            }
            throw $e;
        }
    }
    
    /**
     * 删除指定组的缓存
     *
     * @param string $group 缓存组
     * @return void
     */
    public function delete_group($group) {
        global $wpdb;
        
        $this->log_debug('开始删除组 ' . $group . ' 的缓存');
        
        try {
            // 仅在后台、定时任务或 WP-CLI 中执行
            if (!is_admin() && !(defined('DOING_CRON') && DOING_CRON) && !(defined('WP_CLI') && WP_CLI)) {
                $this->log_debug('跳过删除：非后台/非CRON/非WP-CLI环境');
                return;
            }
            // 删除瞬态缓存
            $query = $wpdb->prepare(
                "DELETE FROM $wpdb->options WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_b2bpress_' . $group . '%',
                '_transient_timeout_b2bpress_' . $group . '%'
            );
            
            // 避免在日志中直出完整SQL
            $this->log_debug('执行组删除瞬态缓存（语句已屏蔽），组: ' . $group);
            $result = $wpdb->query($query);
            
            if ($result === false) {
                $this->log_debug('删除瞬态缓存失败: ' . $wpdb->last_error);
                throw new Exception('删除瞬态缓存失败: ' . $wpdb->last_error);
            }
            
            $this->log_debug('删除瞬态缓存成功，影响行数: ' . $result);
            
            // 精确删除对象缓存键：通过索引列表删除该组相关的键
            $this->delete_object_cache_keys_by_prefix($group);
            
            $this->log_debug('组 ' . $group . ' 的缓存删除完成');
            $this->bump_last_changed();
        } catch (Exception $e) {
            $this->log_debug('删除组缓存过程中发生异常', $e->getMessage());
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('B2BPress删除组缓存错误: ' . $e->getMessage() . ' [堆栈跟踪: ' . $e->getTraceAsString() . ']');
            }
            throw $e;
        }
    }

    /**
     * 维护对象缓存键索引，便于精确删除
     *
     * @param string $key
     * @return void
     */
    private function index_object_cache_key($key) {
        $index_key = 'b2bpress_object_cache_keys_index';
        $keys = wp_cache_get($index_key, 'b2bpress');
        if (!is_array($keys)) {
            $keys = array();
        }
        if (!isset($keys[$key])) {
            $keys[$key] = true;
            wp_cache_set($index_key, $keys, 'b2bpress');
        }
    }

    /**
     * 根据前缀删除对象缓存键
     *
     * @param string $prefix
     * @return void
     */
    private function delete_object_cache_keys_by_prefix($prefix) {
        $index_key = 'b2bpress_object_cache_keys_index';
        $keys = wp_cache_get($index_key, 'b2bpress');
        if (!is_array($keys) || empty($keys)) {
            return;
        }
        $updated = false;
        foreach (array_keys($keys) as $key) {
            if (strpos($key, $prefix) === 0) {
                wp_cache_delete($key, 'b2bpress');
                unset($keys[$key]);
                $updated = true;
            }
        }
        if ($updated) {
            wp_cache_set($index_key, $keys, 'b2bpress');
        }
    }

    /**
     * bump last_changed，用于REST ETag/Last-Modified
     */
    public function bump_last_changed() {
        try {
            update_option('b2bpress_last_changed', (string) time(), false);
        } catch (Exception $e) {
            // 忽略
        }
    }
    
    /**
     * 刷新所有缓存
     */
    public function refresh_all_cache() {
        // 删除所有表格缓存
        $this->delete_group('b2bpress_table');
        $this->delete_by_prefix('b2bpress_rendered_table_');
        
        // 重新生成所有表格缓存
        if (class_exists('B2BPress_Table_Generator')) {
            try {
                $table_generator = new B2BPress_Table_Generator();
                $table_generator->refresh_all_table_cache();
            } catch (Exception $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('B2BPress刷新所有缓存时重新生成表格缓存失败: ' . $e->getMessage());
                }
            }
        }
    }
    
    /**
     * 安排缓存刷新
     */
    public function schedule_cache_refresh() {
        // 如果定时任务不存在，则添加
        if (!wp_next_scheduled('b2bpress_refresh_cache')) {
            wp_schedule_event(time(), 'daily', 'b2bpress_refresh_cache');
        }
    }
    
    /**
     * 取消缓存刷新
     */
    public function unschedule_cache_refresh() {
        // 取消定时任务
        $timestamp = wp_next_scheduled('b2bpress_refresh_cache');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'b2bpress_refresh_cache');
        }
    }

    /**
     * 记录调试信息
     *
     * @param string $message 错误消息
     * @param mixed $data 额外数据
     * @return void
     */
    private function log_debug($message, $data = null) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            if ($data !== null) {
                error_log('B2BPress缓存调试: ' . $message . ' - ' . (is_string($data) ? $data : print_r($data, true)));
            } else {
                error_log('B2BPress缓存调试: ' . $message);
            }
        }
    }

    /**
     * AJAX刷新缓存
     */
    public function ajax_refresh_cache() {
        // 记录开始刷新缓存
        $this->log_debug('开始刷新缓存');
        
        try {
            // 获取表格ID
            $table_id = isset($_POST['table_id']) ? absint($_POST['table_id']) : 0;
            $this->log_debug('请求刷新表格ID: ' . $table_id);
            
            // 检查nonce - 同时支持前端和后台的nonce
            $is_admin_request = false;
            
            // 尝试验证管理员nonce
            if (isset($_POST['nonce']) && check_ajax_referer('b2bpress-admin-nonce', 'nonce', false)) {
                $is_admin_request = true;
                $this->log_debug('管理员nonce验证成功');
            } 
            // 尝试验证前端nonce
            elseif (isset($_POST['nonce']) && check_ajax_referer('b2bpress-public-nonce', 'nonce', false)) {
                $this->log_debug('前端nonce验证成功');
            } 
            // 两种nonce都验证失败
            else {
                // 不在日志中输出任何 nonce 内容，统一提示
                $this->log_debug('Nonce验证失败');
                wp_send_json_error(__('安全验证失败，请刷新页面后重试', 'b2bpress'));
                return;
            }
            
            // 检查权限
            if (!current_user_can('manage_b2bpress')) {
                $this->log_debug('权限检查失败，当前用户角色: ' . implode(', ', wp_get_current_user()->roles));
                wp_send_json_error(__('权限不足', 'b2bpress'));
                return;
            }
            
            if ($table_id > 0) {
                // 刷新指定表格的缓存
                $this->log_debug('开始刷新指定表格缓存');
                
                try {
                    $this->delete_by_prefix('b2bpress_table_' . $table_id);
                } catch (Exception $e) {
                    $this->log_debug('删除表格缓存前缀失败', $e->getMessage());
                    throw new Exception('删除表格缓存前缀失败: ' . $e->getMessage());
                }
                
                try {
                    $this->delete_by_prefix('b2bpress_rendered_table_' . $table_id);
                } catch (Exception $e) {
                    $this->log_debug('删除渲染表格缓存前缀失败', $e->getMessage());
                    throw new Exception('删除渲染表格缓存前缀失败: ' . $e->getMessage());
                }
                
                // 如果表格生成器类存在，重新生成缓存
                if (!class_exists('B2BPress_Table_Generator')) {
                    $this->log_debug('尝试加载表格生成器类');
                    require_once B2BPRESS_PLUGIN_DIR . 'includes/tables/class-b2bpress-table-generator.php';
                }
                
                if (class_exists('B2BPress_Table_Generator')) {
                    try {
                        $this->log_debug('实例化表格生成器');
                        $table_generator = new B2BPress_Table_Generator();
                        $this->log_debug('开始生成表格缓存');
                        $table_generator->generate_table_cache($table_id);
                        $this->log_debug('表格缓存生成完成');
                    } catch (Exception $e) {
                        $this->log_debug('生成表格缓存失败', $e->getMessage());
                        throw new Exception('生成表格缓存失败: ' . $e->getMessage());
                    }
                } else {
                    $this->log_debug('表格生成器类不存在');
                    throw new Exception('表格生成器类不存在');
                }
                
                // 返回成功响应
                $this->log_debug('表格缓存刷新成功');
                wp_send_json_success(array(
                    'message' => __('表格缓存已刷新', 'b2bpress'),
                ));
            } else {
                // 刷新所有表格缓存
                $this->log_debug('开始刷新所有表格缓存');
                
                try {
                    $this->refresh_all_cache();
                    $this->log_debug('所有表格缓存刷新成功');
                    
                    // 返回成功响应
                    wp_send_json_success(array(
                        'message' => __('所有表格缓存已刷新', 'b2bpress'),
                    ));
                } catch (Exception $e) {
                    $this->log_debug('刷新所有缓存失败', $e->getMessage());
                    throw new Exception('刷新所有缓存失败: ' . $e->getMessage());
                }
            }
        } catch (Exception $e) {
            $this->log_debug('刷新缓存时发生异常', $e->getMessage());
            $error_message = __('刷新缓存时发生错误: ', 'b2bpress') . $e->getMessage();
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $error_message .= ' [堆栈跟踪: ' . $e->getTraceAsString() . ']';
            }
            wp_send_json_error($error_message);
        }
    }
} 