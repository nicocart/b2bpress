<?php
/**
 * B2BPress 核心类
 * 
 * 处理插件的主要功能和组件加载
 */
class B2BPress_Core {
    /**
     * 语言管理器实例
     *
     * @var B2BPress_Language_Manager
     */
    private $language_manager;
    
    /**
     * 构造函数
     */
    public function __construct() {
        $this->init_components();
        $this->register_hooks();
    }

    /**
     * 初始化组件
     */
    private function init_components() {
        // 初始化语言管理器
        $this->language_manager = new B2BPress_Language_Manager();
        
        // 加载WooCommerce精简模式组件
        require_once B2BPRESS_PLUGIN_DIR . 'includes/woocommerce/class-b2bpress-wc-lite.php';
        new B2BPress_WC_Lite();
        
        // 加载表格生成器组件
        require_once B2BPRESS_PLUGIN_DIR . 'includes/tables/class-b2bpress-table-generator.php';
        new B2BPress_Table_Generator();
        
        // 加载缓存管理组件
        require_once B2BPRESS_PLUGIN_DIR . 'includes/class-b2bpress-cache.php';
        new B2BPress_Cache();
        
        // 加载权限管理组件
        require_once B2BPRESS_PLUGIN_DIR . 'includes/class-b2bpress-permissions.php';
        new B2BPress_Permissions();
        
        // 隐私导出/擦除组件
        require_once B2BPRESS_PLUGIN_DIR . 'includes/class-b2bpress-privacy.php';
        new B2BPress_Privacy();

        // 加载API组件
        require_once B2BPRESS_PLUGIN_DIR . 'includes/api/class-b2bpress-api.php';
        new B2BPress_API();
        
        // Elementor集成
        if (did_action('elementor/loaded')) {
            require_once B2BPRESS_PLUGIN_DIR . 'includes/elementor/class-b2bpress-elementor.php';
            new B2BPress_Elementor();
        }
    }

    /**
     * 注册钩子
     */
    private function register_hooks() {
        // 注册短代码
        add_action('init', array($this, 'register_shortcodes'));
        
        // 添加设置链接
        add_filter('plugin_action_links_' . B2BPRESS_PLUGIN_BASENAME, array($this, 'plugin_action_links'));
        
        // 注册自定义能力
        add_action('admin_init', array($this, 'register_capabilities'));
        
        // 注册自定义文章类型
        add_action('init', array($this, 'register_post_types'));
        
        // 添加与缓存插件的兼容性
        $this->add_cache_compatibility();
    }
    
    /**
     * 添加与缓存插件的兼容性
     */
    private function add_cache_compatibility() {
        // WP Rocket 兼容性
        if (defined('WP_ROCKET_VERSION')) {
            // 在WP Rocket清除缓存后重新生成表格缓存
            add_action('after_rocket_clean_domain', array($this, 'regenerate_tables_cache'));
            
            // 在WP Rocket预加载缓存时预先生成表格缓存
            add_action('before_rocket_clean_domain', array($this, 'regenerate_tables_cache'));
        }
        
        // WP Fastest Cache 兼容性
        if (class_exists('WpFastestCache')) {
            add_action('wpfc_delete_cache', array($this, 'regenerate_tables_cache'));
        }
        
        // W3 Total Cache 兼容性
        if (defined('W3TC')) {
            add_action('w3tc_flush_all', array($this, 'regenerate_tables_cache'));
        }
        
        // WP Super Cache 兼容性
        if (function_exists('wp_cache_clear_cache')) {
            add_action('wp_cache_cleared', array($this, 'regenerate_tables_cache'));
        }
    }
    
    /**
     * 重新生成所有表格缓存
     */
    public function regenerate_tables_cache() {
        // 确保表格生成器类已加载
        if (!class_exists('B2BPress_Table_Generator')) {
            require_once B2BPRESS_PLUGIN_DIR . 'includes/tables/class-b2bpress-table-generator.php';
        }
        
        // 创建表格生成器实例
        $table_generator = new B2BPress_Table_Generator();
        
        // 调用刷新所有表格缓存的方法
        $table_generator->refresh_all_table_cache();
    }

    /**
     * 注册短代码
     */
    public function register_shortcodes() {
        add_shortcode('b2bpress_table', array($this, 'table_shortcode'));
    }

    /**
     * 表格短代码处理函数
     *
     * @param array $atts 短代码属性
     * @return string 短代码输出
     */
    public function table_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'style' => 'default',
            'category' => '',
            'per_page' => 20,
            'show_images' => false,
        ), $atts, 'b2bpress_table');
        
        // 获取表格生成器实例
        $table_generator = new B2BPress_Table_Generator();
        
        // 返回表格HTML
        return $table_generator->render_table($atts);
    }

    /**
     * 添加插件操作链接
     *
     * @param array $links 现有链接
     * @return array 修改后的链接
     */
    public function plugin_action_links($links) {
        $plugin_links = array(
            '<a href="' . admin_url('admin.php?page=b2bpress-settings') . '">' . __('设置', 'b2bpress') . '</a>',
            '<a href="' . admin_url('admin.php?page=b2bpress-tables') . '">' . __('表格', 'b2bpress') . '</a>',
        );
        return array_merge($plugin_links, $links);
    }

    /**
     * 注册自定义能力
     */
    public function register_capabilities() {
        // 获取管理员角色
        $admin = get_role('administrator');
        
        // 添加自定义能力
        if ($admin) {
            $admin->add_cap('manage_b2bpress');
        }
    }
    
    /**
     * 注册自定义文章类型
     */
    public function register_post_types() {
        // 注册表格自定义文章类型
        register_post_type('b2bpress_table', array(
            'labels' => array(
                'name'               => __('表格', 'b2bpress'),
                'singular_name'      => __('表格', 'b2bpress'),
                'menu_name'          => __('表格', 'b2bpress'),
                'add_new'            => __('添加新表格', 'b2bpress'),
                'add_new_item'       => __('添加新表格', 'b2bpress'),
                'edit_item'          => __('编辑表格', 'b2bpress'),
                'new_item'           => __('新表格', 'b2bpress'),
                'view_item'          => __('查看表格', 'b2bpress'),
                'search_items'       => __('搜索表格', 'b2bpress'),
                'not_found'          => __('未找到表格', 'b2bpress'),
                'not_found_in_trash' => __('回收站中未找到表格', 'b2bpress'),
            ),
            'public'              => false,
            'show_ui'             => false,
            'show_in_menu'        => false,
            'show_in_nav_menus'   => false,
            'show_in_admin_bar'   => false,
            'show_in_rest'        => false,
            'publicly_queryable'  => false,
            'exclude_from_search' => true,
            'has_archive'         => false,
            'query_var'           => false,
            'can_export'          => true,
            'rewrite'             => false,
            'capability_type'     => 'post',
            'supports'            => array('title'),
        ));
    }

    /**
     * 获取语言管理器实例
     *
     * @return B2BPress_Language_Manager
     */
    public function get_language_manager() {
        return $this->language_manager;
    }
} 