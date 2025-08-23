<?php
/**
 * B2BPress Elementor集成类
 * 
 * 用于提供Elementor小部件
 */
class B2BPress_Elementor {
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
        // 注册小部件
        add_action('elementor/widgets/widgets_registered', array($this, 'register_widgets'));
        
        // 注册小部件分类
        add_action('elementor/elements/categories_registered', array($this, 'register_widget_categories'));
        
        // 加载Elementor脚本和样式
        add_action('elementor/frontend/after_enqueue_styles', array($this, 'enqueue_styles'));
        add_action('elementor/frontend/after_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('elementor/editor/after_enqueue_scripts', array($this, 'enqueue_editor_scripts'));
    }
    
    /**
     * 注册小部件
     *
     * @param \Elementor\Widgets_Manager $widgets_manager Elementor小部件管理器
     */
    public function register_widgets($widgets_manager) {
        // 加载小部件类
        require_once B2BPRESS_PLUGIN_DIR . 'includes/elementor/widgets/class-b2bpress-table-widget.php';
        
        // 兼容新旧注册方式
        $widget = new B2BPress_Table_Widget();
        if (method_exists($widgets_manager, 'register')) {
            $widgets_manager->register($widget);
        } else {
            if (method_exists($widgets_manager, 'register_widget_type')) {
                $widgets_manager->register_widget_type($widget);
            }
        }
    }
    
    /**
     * 注册小部件分类
     *
     * @param \Elementor\Elements_Manager $elements_manager Elementor元素管理器
     */
    public function register_widget_categories($elements_manager) {
        // 添加B2BPress分类
        $elements_manager->add_category(
            'b2bpress',
            array(
                'title' => __('B2BPress', 'b2bpress'),
                'icon' => 'fa fa-plug',
            )
        );
    }
    
    /**
     * 加载前端样式
     */
    public function enqueue_styles() {
        // 注册和加载样式
        wp_register_style(
            'b2bpress-elementor',
            B2BPRESS_PLUGIN_URL . 'public/css/b2bpress-elementor.css',
            array(),
            B2BPRESS_VERSION
        );
        wp_enqueue_style('b2bpress-elementor');
    }
    
    /**
     * 加载前端脚本
     */
    public function enqueue_scripts() {
        // 注册和加载脚本
        wp_register_script(
            'b2bpress-elementor',
            B2BPRESS_PLUGIN_URL . 'public/js/b2bpress-elementor.js',
            array('jquery'),
            B2BPRESS_VERSION,
            true
        );
        wp_enqueue_script('b2bpress-elementor');
    }
    
    /**
     * 加载编辑器脚本
     */
    public function enqueue_editor_scripts() {
        // 注册和加载编辑器脚本
        wp_register_script(
            'b2bpress-elementor-editor',
            B2BPRESS_PLUGIN_URL . 'admin/js/b2bpress-elementor-editor.js',
            array('jquery'),
            B2BPRESS_VERSION,
            true
        );
        
        // 获取表格列表
        $tables = $this->get_tables();
        
        // 本地化脚本
        wp_localize_script('b2bpress-elementor-editor', 'b2bpressElementor', array(
            'tables' => $tables,
            'i18n' => array(
                'select_table' => __('选择表格', 'b2bpress'),
                'create_table' => __('创建表格', 'b2bpress'),
                'no_tables' => __('没有可用的表格', 'b2bpress'),
            ),
        ));
        
        wp_enqueue_script('b2bpress-elementor-editor');
    }
    
    /**
     * 获取表格列表
     *
     * @return array 表格列表
     */
    private function get_tables() {
        // 获取表格列表
        $args = array(
            'post_type' => 'b2bpress_table',
            'post_status' => 'publish',
            'posts_per_page' => -1,
        );
        
        $query = new WP_Query($args);
        
        // 准备数据
        $tables = array();
        foreach ($query->posts as $post) {
            $tables[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
            );
        }
        
        return $tables;
    }
} 