<?php
/**
 * B2BPress 语言管理类
 * 
 * 处理插件的语言和本地化功能
 */
class B2BPress_Language_Manager {
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
        // 添加用户语言偏好设置
        add_action('show_user_profile', array($this, 'add_language_preference_field'));
        add_action('edit_user_profile', array($this, 'add_language_preference_field'));
        add_action('personal_options_update', array($this, 'save_language_preference_field'));
        add_action('edit_user_profile_update', array($this, 'save_language_preference_field'));
        
        // 加载文本域
        add_action('plugins_loaded', array($this, 'load_textdomain'));

        // 在未提供 .mo 文件时提供运行时英文翻译映射（兜底）
        add_action('plugins_loaded', array($this, 'init_runtime_en_translations'), 1);
    }

    /**
     * 加载文本域
     */
    public function load_textdomain() {
        load_plugin_textdomain('b2bpress', false, dirname(plugin_basename(B2BPRESS_PLUGIN_BASENAME)) . '/languages');
    }

    /**
     * 获取站点语言
     * 
     * @return string 站点语言代码
     */
    public function get_site_language() {
        // 获取WordPress站点语言
        $site_language = get_locale();
        
        // 如果没有设置站点语言，返回默认语言
        if (empty($site_language)) {
            return 'en_US';
        }
        
        return $site_language;
    }

    /**
     * 获取用户语言偏好
     * 
     * @param int $user_id 用户ID，默认为当前用户
     * @return string 语言代码
     */
    public function get_user_language($user_id = 0) {
        // 如果未提供用户ID，使用当前用户ID
        if ($user_id === 0 && is_user_logged_in()) {
            $user_id = get_current_user_id();
        }
        
        // 如果有用户ID，获取用户语言偏好
        if ($user_id > 0) {
            $user_language = get_user_meta($user_id, 'b2bpress_language', true);
            
            // 如果用户没有设置语言偏好，尝试获取用户的区域设置
            if (empty($user_language)) {
                $user_locale = get_user_locale($user_id);
                if (!empty($user_locale)) {
                    return $user_locale;
                }
            } else {
                return $user_language;
            }
        }
        
        // 如果无法获取用户语言偏好，返回默认语言
        return 'en_US';
    }

    /**
     * 设置用户语言偏好
     * 
     * @param int $user_id 用户ID
     * @param string $language 语言代码
     * @return bool 是否成功设置
     */
    public function set_user_language($user_id, $language) {
        return update_user_meta($user_id, 'b2bpress_language', $language);
    }

    /**
     * 获取适当的语言
     * 
     * @param bool $is_frontend 是否是前端请求
     * @return string 语言代码
     */
    public function get_appropriate_language($is_frontend = false) {
        // 前端优先使用已登录用户的语言偏好，否则回退站点语言
        if ($is_frontend) {
            if (is_user_logged_in()) {
                $user_lang = $this->get_user_language();
                if (!empty($user_lang)) {
                    return $user_lang;
                }
            }
            return $this->get_site_language();
        }
        
        // 后端优先使用用户语言，若无则回退站点语言
        $user_lang = $this->get_user_language();
        return !empty($user_lang) ? $user_lang : $this->get_site_language();
    }

    /**
     * 应用语言设置
     * 
     * @param bool $is_frontend 是否是前端请求
     */
    public function apply_language($is_frontend = false) {
        $language = $this->get_appropriate_language($is_frontend);
        
        // 如果有语言设置，临时切换语言
        if (!empty($language)) {
            switch_to_locale($language);
        }
    }

    /**
     * 恢复原始语言
     */
    public function restore_original_language() {
        restore_previous_locale();
    }

    /**
     * 运行时英文翻译（当源字符串为中文且缺少 en_US.mo 时的兜底方案）
     */
    public function init_runtime_en_translations() {
        // 仅在当前目标语言为 en_US 时启用
        $current = determine_locale();
        if ($current !== 'en_US') {
            return;
        }

        $map = $this->get_runtime_en_map();
        if (empty($map)) { return; }

        add_filter('gettext', function ($translated, $text, $domain) use ($map) {
            if ($domain !== 'b2bpress') { return $translated; }
            if (isset($map[$text])) { return $map[$text]; }
            return $translated;
        }, 10, 3);

        add_filter('ngettext', function ($translated, $single, $plural, $number, $domain) use ($map) {
            if ($domain !== 'b2bpress') { return $translated; }
            if (isset($map[$single])) { return $map[$single]; }
            return $translated;
        }, 10, 5);
    }

    /**
     * 常用中文=>英文映射（覆盖前后端常见文案）
     * @return array
     */
    private function get_runtime_en_map() {
        return array(
            'B2BPress' => 'B2BPress',
            '仪表盘' => 'Dashboard',
            '表格管理' => 'Tables',
            '设置' => 'Settings',
            '关于' => 'About',
            '默认表格样式' => 'Default Table Style',
            '显示产品图片' => 'Show Product Images',
            '默认每页显示' => 'Default Per Page',
            '需要登录' => 'Login Required',
            '全局表格CSS' => 'Global Table CSS',
            '加载属性' => 'Load Attributes',
            '添加' => 'Add',
            '移除' => 'Remove',
            '返回列表' => 'Back to list',
            '保存' => 'Save',
            '更新' => 'Update',
            '创建表格' => 'Create Table',
            '预览表格' => 'Preview Table',
            '短代码' => 'Shortcode',
            '标题' => 'Title',
            '分类' => 'Category',
            '样式' => 'Style',
            '日期' => 'Date',
            '编辑' => 'Edit',
            '删除' => 'Delete',
            '刷新缓存' => 'Refresh Cache',
            '复制' => 'Copy',
            '已复制' => 'Copied',
            '搜索产品...' => 'Search products...',
            '加载中...' => 'Loading...',
            'Loading...' => 'Loading...',
            '未找到结果' => 'No results found',
            'No results found' => 'No results found',
            '发生错误' => 'An error occurred',
            '上一页' => 'Previous page',
            '下一页' => 'Next page',
            '图片' => 'Image',
            '产品名称' => 'Product Name',
            '有库存' => 'In Stock',
            '缺货' => 'Out of Stock',
            '预订中' => 'On Backorder',
            'SKU' => 'SKU',
            '库存状态' => 'Stock Status',
            '属性' => 'Attribute',
            '产品数据' => 'Product Data',
            '价格' => 'Price',
            '需要登录才能查看表格数据' => 'Login required to view table data',
            '需要登录才能查看该表格数据' => 'Login required to view this table',
            '表格不存在' => 'Table does not exist',
            'Invalid product category' => 'Invalid product category',
            'Invalid table ID' => 'Invalid table ID',
            'No products found' => 'No products found',
            'Edit Table' => 'Edit Table',
            'Refresh Cache' => 'Refresh Cache',
            '确定要删除此表格吗？此操作无法撤销。' => 'Are you sure to delete this table? This action cannot be undone.',
        );
    }

    /**
     * 获取可用语言列表
     * 
     * @return array 语言列表，格式为 [语言代码 => 语言名称]
     */
    public function get_available_languages() {
        return array(
            'en_US' => __('英语', 'b2bpress'),
            'zh_CN' => __('简体中文', 'b2bpress'),
            'zh_TW' => __('繁体中文', 'b2bpress'),
            'ja' => __('日语', 'b2bpress'),
            'ko' => __('韩语', 'b2bpress'),
            'fr_FR' => __('法语', 'b2bpress'),
            'de_DE' => __('德语', 'b2bpress'),
            'es_ES' => __('西班牙语', 'b2bpress'),
            'it_IT' => __('意大利语', 'b2bpress'),
            'ru_RU' => __('俄语', 'b2bpress'),
            'pt_BR' => __('葡萄牙语(巴西)', 'b2bpress'),
            'pt_PT' => __('葡萄牙语(葡萄牙)', 'b2bpress'),
            'nl_NL' => __('荷兰语', 'b2bpress'),
            'pl_PL' => __('波兰语', 'b2bpress'),
            'tr_TR' => __('土耳其语', 'b2bpress'),
            'ar' => __('阿拉伯语', 'b2bpress'),
            'hi_IN' => __('印地语', 'b2bpress'),
            'bn_BD' => __('孟加拉语', 'b2bpress'),
        );
    }

    /**
     * 添加用户语言偏好设置字段
     */
    public function add_language_preference_field($user) {
        $current_language = get_user_meta($user->ID, 'b2bpress_language', true);
        $available_languages = $this->get_available_languages();
        ?>
        <h3><?php _e('B2BPress 语言偏好', 'b2bpress'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="b2bpress_language"><?php _e('首选语言', 'b2bpress'); ?></label></th>
                <td>
                    <select name="b2bpress_language" id="b2bpress_language">
                        <?php foreach ($available_languages as $code => $name) : ?>
                            <option value="<?php echo esc_attr($code); ?>" <?php selected($code, $current_language); ?>><?php echo esc_html($name); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e('选择您在 B2BPress 后台中使用的首选语言。前端表格将使用站点语言。', 'b2bpress'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * 保存用户语言偏好设置
     */
    public function save_language_preference_field($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }
        if (isset($_POST['b2bpress_language'])) {
            $language = sanitize_text_field(wp_unslash($_POST['b2bpress_language']));
            $this->set_user_language($user_id, $language);
        }
    }
} 