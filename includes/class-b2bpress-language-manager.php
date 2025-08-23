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
        // 加载文本域
        add_action('plugins_loaded', array($this, 'load_textdomain'));

        // 当语言切换时，重新加载文本域，确保 .mo 随当前 locale 生效
        add_action('change_locale', array($this, 'reload_textdomain_on_locale_change'));

        // 仅依赖 .po/.mo
    }

    /**
     * 加载文本域
     */
    public function load_textdomain() {
        load_plugin_textdomain('b2bpress', false, dirname(B2BPRESS_PLUGIN_BASENAME) . '/languages');
    }

    /**
     * 语言切换时重新加载文本域
     */
    public function reload_textdomain_on_locale_change() {
        unload_textdomain('b2bpress');
        $this->load_textdomain();
    }

    /**
     * 获取站点/请求语言
     * 
     * @return string 站点语言代码
     */
    public function get_site_language() {
        // 使用 WP 的语言判定，以覆盖 REST/AJAX 等场景
        $site_language = function_exists('determine_locale') ? determine_locale() : get_locale();
        
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
        // 始终使用 WP 提供的用户语言（与后台用户语言保持一致）
        if (function_exists('get_user_locale')) {
            return get_user_locale();
        }
        return get_locale();
    }

    /**
     * 设置用户语言偏好
     * 
     * @param int $user_id 用户ID
     * @param string $language 语言代码
     * @return bool 是否成功设置
     */
    public function set_user_language($user_id, $language) {
        // 不再保存插件内的独立语言偏好，统一使用 WP 用户语言
        return false;
    }

    /**
     * 获取适当的语言
     * 
     * @param bool $is_frontend 是否是前端请求
     * @return string 语言代码
     */
    public function get_appropriate_language($is_frontend = false) {
        // 前端：使用 WP 判定语言（通常为站点语言）
        if ($is_frontend) {
            return $this->get_site_language();
        }
        // 后端：使用用户语言
        return $this->get_user_language();
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

    // 仅依赖标准翻译文件（.po/.mo），不再提供自定义映射或用户级独立设置
} 