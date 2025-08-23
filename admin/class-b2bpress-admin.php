<?php
/**
 * B2BPress 管理员类
 * 
 * 处理插件的后台管理功能
 */
class B2BPress_Admin {
    /**
     * 构造函数
     */
    public function __construct() {
        // 确保缓存类已加载
        require_once B2BPRESS_PLUGIN_DIR . 'includes/class-b2bpress-cache.php';
        
        $this->init_hooks();
    }

    /**
     * 初始化钩子
     */
    private function init_hooks() {
        // 添加管理菜单
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // 注册设置
        add_action('admin_init', array($this, 'register_settings'));
        
        // 加载管理脚本和样式
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // 添加设置页面的帮助选项卡
        add_action('admin_head', array($this, 'add_help_tabs'));
        
        // 添加设置页面的屏幕选项
        add_filter('screen_options_show_screen', array($this, 'add_screen_options'), 10, 2);
    }

    /**
     * 添加管理菜单
     */
    public function add_admin_menu() {
        // 添加主菜单
        add_menu_page(
            __('B2BPress', 'b2bpress'),
            __('B2BPress', 'b2bpress'),
            'manage_b2bpress',
            'b2bpress',
            array($this, 'display_dashboard_page'),
            'dashicons-store',
            56
        );
        
        // 添加子菜单
        add_submenu_page(
            'b2bpress',
            __('仪表盘', 'b2bpress'),
            __('仪表盘', 'b2bpress'),
            'manage_b2bpress',
            'b2bpress',
            array($this, 'display_dashboard_page')
        );
        
        // 添加表格管理页面
        add_submenu_page(
            'b2bpress',
            __('表格管理', 'b2bpress'),
            __('表格管理', 'b2bpress'),
            'manage_b2bpress',
            'b2bpress-tables',
            array($this, 'display_tables_page')
        );
        
        // 添加设置页面
        add_submenu_page(
            'b2bpress',
            __('设置', 'b2bpress'),
            __('设置', 'b2bpress'),
            'manage_b2bpress',
            'b2bpress-settings',
            array($this, 'display_settings_page')
        );
        
        // 添加关于页面
        add_submenu_page(
            'b2bpress',
            __('关于', 'b2bpress'),
            __('关于', 'b2bpress'),
            'manage_b2bpress',
            'b2bpress-about',
            array($this, 'display_about_page')
        );
    }

    /**
     * 显示仪表盘页面
     */
    public function display_dashboard_page() {
        require_once B2BPRESS_PLUGIN_DIR . 'admin/partials/dashboard.php';
    }

    /**
     * 显示表格管理页面
     */
    public function display_tables_page() {
        require_once B2BPRESS_PLUGIN_DIR . 'admin/partials/tables.php';
    }

    /**
     * 显示设置页面
     */
    public function display_settings_page() {
        require_once B2BPRESS_PLUGIN_DIR . 'admin/partials/settings.php';
    }

    /**
     * 显示关于页面
     */
    public function display_about_page() {
        require_once B2BPRESS_PLUGIN_DIR . 'admin/partials/about.php';
    }

    /**
     * 注册设置
     */
    public function register_settings() {
        // 注册设置组
        register_setting('b2bpress_settings', 'b2bpress_options', array($this, 'sanitize_settings'));
        
        // 添加设置部分
        add_settings_section(
            'b2bpress_wc_lite_section',
            __('WooCommerce 精简模式', 'b2bpress'),
            array($this, 'wc_lite_section_callback'),
            'b2bpress_settings'
        );
        
        // 添加设置字段
        add_settings_field(
            'disable_cart',
            __('禁用购物车', 'b2bpress'),
            array($this, 'checkbox_field_callback'),
            'b2bpress_settings',
            'b2bpress_wc_lite_section',
            array(
                'label_for' => 'disable_cart',
                'description' => __('禁用WooCommerce购物车功能', 'b2bpress'),
            )
        );
        
        add_settings_field(
            'disable_checkout',
            __('禁用结账', 'b2bpress'),
            array($this, 'checkbox_field_callback'),
            'b2bpress_settings',
            'b2bpress_wc_lite_section',
            array(
                'label_for' => 'disable_checkout',
                'description' => __('禁用WooCommerce结账功能', 'b2bpress'),
            )
        );
        
        add_settings_field(
            'disable_coupons',
            __('禁用优惠券', 'b2bpress'),
            array($this, 'checkbox_field_callback'),
            'b2bpress_settings',
            'b2bpress_wc_lite_section',
            array(
                'label_for' => 'disable_coupons',
                'description' => __('禁用WooCommerce优惠券功能', 'b2bpress'),
            )
        );
        
        add_settings_field(
            'disable_inventory',
            __('禁用库存', 'b2bpress'),
            array($this, 'checkbox_field_callback'),
            'b2bpress_settings',
            'b2bpress_wc_lite_section',
            array(
                'label_for' => 'disable_inventory',
                'description' => __('禁用WooCommerce库存管理功能', 'b2bpress'),
            )
        );
        
        add_settings_field(
            'disable_prices',
            __('禁用价格', 'b2bpress'),
            array($this, 'checkbox_field_callback'),
            'b2bpress_settings',
            'b2bpress_wc_lite_section',
            array(
                'label_for' => 'disable_prices',
                'description' => __('隐藏产品价格', 'b2bpress'),
            )
        );
        
        add_settings_field(
            'disable_marketing',
            __('禁用营销', 'b2bpress'),
            array($this, 'checkbox_field_callback'),
            'b2bpress_settings',
            'b2bpress_wc_lite_section',
            array(
                'label_for' => 'disable_marketing',
                'description' => __('禁用WooCommerce营销功能', 'b2bpress'),
            )
        );
        
        add_settings_field(
            'disable_css_js',
            __('禁用前端CSS/JS', 'b2bpress'),
            array($this, 'checkbox_field_callback'),
            'b2bpress_settings',
            'b2bpress_wc_lite_section',
            array(
                'label_for' => 'disable_css_js',
                'description' => __('禁用WooCommerce前端CSS和JavaScript', 'b2bpress'),
            )
        );
        
        // 表格设置部分
        add_settings_section(
            'b2bpress_table_section',
            __('表格设置', 'b2bpress'),
            array($this, 'table_section_callback'),
            'b2bpress_settings'
        );
        
        add_settings_field(
            'default_table_style',
            __('默认表格样式', 'b2bpress'),
            array($this, 'select_field_callback'),
            'b2bpress_settings',
            'b2bpress_table_section',
            array(
                'label_for' => 'default_table_style',
                'description' => __('选择默认表格样式', 'b2bpress'),
                'options' => array(
                    'inherit' => __('继承主题', 'b2bpress'),
                    'shadcn' => __('ShadCN/UI 风格', 'b2bpress'),
                    'clean' => __('干净（无边框）', 'b2bpress'),
                    'bordered' => __('描边表格', 'b2bpress'),
                    'compact' => __('紧凑密集', 'b2bpress'),
                ),
            )
        );
        
        add_settings_field(
            'show_product_images',
            __('显示产品图片', 'b2bpress'),
            array($this, 'checkbox_field_callback'),
            'b2bpress_settings',
            'b2bpress_table_section',
            array(
                'label_for' => 'show_product_images',
                'description' => __('在前端表格中显示产品图片列', 'b2bpress'),
            )
        );
        
        add_settings_field(
            'default_per_page',
            __('默认每页显示', 'b2bpress'),
            array($this, 'number_field_callback'),
            'b2bpress_settings',
            'b2bpress_table_section',
            array(
                'label_for' => 'default_per_page',
                'description' => __('设置默认每页显示的产品数量', 'b2bpress'),
                'min' => 5,
                'max' => 100,
            )
        );
        
        add_settings_field(
            'login_required',
            __('需要登录', 'b2bpress'),
            array($this, 'checkbox_field_callback'),
            'b2bpress_settings',
            'b2bpress_table_section',
            array(
                'label_for' => 'login_required',
                'description' => __('仅登录用户可以查看表格', 'b2bpress'),
            )
        );
        
        add_settings_field(
            'global_table_css',
            __('全局表格CSS', 'b2bpress'),
            array($this, 'textarea_field_callback'),
            'b2bpress_settings',
            'b2bpress_table_section',
            array(
                'label_for' => 'global_table_css',
                'description' => __('为所有表格应用的全局CSS样式', 'b2bpress'),
            )
        );
    }

    /**
     * WooCommerce精简模式部分回调
     */
    public function wc_lite_section_callback() {
        echo '<p>' . __('配置WooCommerce精简模式设置，禁用不需要的功能。', 'b2bpress') . '</p>';
    }

    /**
     * 表格设置部分回调
     */
    public function table_section_callback() {
        echo '<p>' . __('配置产品表格的默认设置。', 'b2bpress') . '</p>';
    }

    /**
     * 复选框字段回调
     *
     * @param array $args 字段参数
     */
    public function checkbox_field_callback($args) {
        $options = get_option('b2bpress_options', array());
        $value = isset($options[$args['label_for']]) ? $options[$args['label_for']] : 0;
        ?>
        <input type="hidden" name="b2bpress_options[<?php echo esc_attr($args['label_for']); ?>]" value="0">
        <input type="checkbox" id="<?php echo esc_attr($args['label_for']); ?>" 
               name="b2bpress_options[<?php echo esc_attr($args['label_for']); ?>]" 
               value="1" <?php checked('1', (string) $value); ?>>
        <label for="<?php echo esc_attr($args['label_for']); ?>"><?php echo esc_html($args['description']); ?></label>
        <?php
    }

    /**
     * 选择字段回调
     *
     * @param array $args 字段参数
     */
    public function select_field_callback($args) {
        $options = get_option('b2bpress_options', array());
        $value = isset($options[$args['label_for']]) ? $options[$args['label_for']] : (isset($args['options']['inherit']) ? 'inherit' : '');
        ?>
        <select id="<?php echo esc_attr($args['label_for']); ?>" 
                name="b2bpress_options[<?php echo esc_attr($args['label_for']); ?>]">
            <?php foreach ($args['options'] as $key => $label) : ?>
                <option value="<?php echo esc_attr($key); ?>" <?php selected($key, $value); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php
    }

    /**
     * 数字字段回调
     *
     * @param array $args 字段参数
     */
    public function number_field_callback($args) {
        $options = get_option('b2bpress_options', array());
        $value = isset($options[$args['label_for']]) ? $options[$args['label_for']] : 20;
        ?>
        <input type="number" id="<?php echo esc_attr($args['label_for']); ?>" 
               name="b2bpress_options[<?php echo esc_attr($args['label_for']); ?>]" 
               value="<?php echo esc_attr($value); ?>"
               min="<?php echo esc_attr($args['min']); ?>" 
               max="<?php echo esc_attr($args['max']); ?>">
        <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php
    }
    
    /**
     * 文本区域字段回调
     *
     * @param array $args 字段参数
     */
    public function textarea_field_callback($args) {
        $options = get_option('b2bpress_options', array());
        $value = isset($options[$args['label_for']]) ? $options[$args['label_for']] : '';
        ?>
        <textarea id="<?php echo esc_attr($args['label_for']); ?>" 
                  name="b2bpress_options[<?php echo esc_attr($args['label_for']); ?>]" 
                  rows="5" 
                  class="large-text code"
                  placeholder="color: #333; font-size: 14px;"><?php echo esc_textarea($value); ?></textarea>
        <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php
    }

    /**
     * 加载管理脚本和样式
     *
     * @param string $hook_suffix 当前页面的钩子后缀
     */
    public function enqueue_scripts($hook_suffix) {
        // 只在插件页面加载
        if (strpos($hook_suffix, 'b2bpress') === false) {
            return;
        }
        
        // 注册和加载样式
        wp_register_style(
            'b2bpress-admin',
            B2BPRESS_PLUGIN_URL . 'admin/css/b2bpress-admin.css',
            array(),
            B2BPRESS_VERSION
        );
        wp_enqueue_style('b2bpress-admin');
        
        // 注册和加载脚本
        wp_register_script(
            'b2bpress-admin',
            B2BPRESS_PLUGIN_URL . 'admin/js/b2bpress-admin.js',
            array('jquery', 'jquery-ui-sortable'),
            B2BPRESS_VERSION,
            true
        );
        
        // 本地化脚本
        wp_localize_script('b2bpress-admin', 'b2bpressAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('b2bpress-admin-nonce'),
            'i18n' => array(
                'confirm_delete' => __('确定要删除此表格吗？此操作无法撤销。', 'b2bpress'),
                'saving' => __('保存中...', 'b2bpress'),
                'saved' => __('已保存', 'b2bpress'),
                'error' => __('发生错误', 'b2bpress'),
                'error_delete_table' => __('删除表格时发生错误', 'b2bpress'),
                'copied' => __('已复制', 'b2bpress'),
                'refreshing' => __('刷新中...', 'b2bpress'),
                'refreshed' => __('已刷新', 'b2bpress'),
                'error_refresh_cache' => __('刷新缓存时发生错误', 'b2bpress'),
            ),
        ));
        
        wp_enqueue_script('b2bpress-admin');
    }

    /**
     * 添加帮助选项卡
     */
    public function add_help_tabs() {
        $screen = get_current_screen();
        
        // 只在插件页面添加
        if (!$screen || strpos($screen->id, 'b2bpress') === false) {
            return;
        }
        
        // 添加帮助选项卡
        $screen->add_help_tab(array(
            'id' => 'b2bpress-help-overview',
            'title' => __('概述', 'b2bpress'),
            'content' => '<p>' . __('B2BPress 是一个基于 WooCommerce 的 B2B 电子商务解决方案，专为批发和 B2B 业务设计。', 'b2bpress') . '</p>',
        ));
        
        $screen->add_help_tab(array(
            'id' => 'b2bpress-help-tables',
            'title' => __('表格管理', 'b2bpress'),
            'content' => '<p>' . __('使用表格管理功能创建和管理产品表格。您可以选择产品分类、属性和显示样式。', 'b2bpress') . '</p>',
        ));
        
        // 设置帮助侧边栏
        $docs_url = esc_url('https://example.com/docs');
        $support_url = esc_url('https://example.com/support');
        $screen->set_help_sidebar(
            '<p><strong>' . esc_html__('更多信息:', 'b2bpress') . '</strong></p>' .
            '<p><a href="' . $docs_url . '" target="_blank" rel="noopener noreferrer">' . esc_html__('文档', 'b2bpress') . '</a></p>' .
            '<p><a href="' . $support_url . '" target="_blank" rel="noopener noreferrer">' . esc_html__('支持', 'b2bpress') . '</a></p>'
        );
    }

    /**
     * 添加屏幕选项
     *
     * @param bool $show_screen 是否显示屏幕选项
     * @param WP_Screen $screen 当前屏幕对象
     * @return bool
     */
    public function add_screen_options($show_screen, $screen) {
        if (strpos($screen->id, 'b2bpress-tables') !== false) {
            add_screen_option('per_page', array(
                'label' => __('每页表格数', 'b2bpress'),
                'default' => 10,
                'option' => 'b2bpress_tables_per_page',
            ));
        }
        
        return $show_screen;
    }
    
    /**
     * 设置数据清理
     *
     * @param array $input 输入数据
     * @return array 清理后的数据
     */
    public function sanitize_settings($input) {
        // 旧设置用于对比是否需要清缓存
        $old_options = get_option('b2bpress_options', array());

        $sanitized = $old_options; // 基于已有值，避免首次保存把未出现字段全部视为选中

        // 布尔选项
        $boolean_keys = array(
            'disable_cart', 'disable_checkout', 'disable_coupons', 'disable_inventory',
            'disable_prices', 'disable_marketing', 'disable_css_js', 'show_product_images', 'login_required'
        );
        foreach ($boolean_keys as $key) {
            if (isset($input[$key])) {
                $sanitized[$key] = (int) (bool) $input[$key];
            } else {
                // 若表单未提交该键，则保持旧值（避免首次保存误勾选）
                if (!isset($sanitized[$key])) {
                    $sanitized[$key] = 0;
                }
            }
        }

        // 默认表格样式（白名单）
        $style = isset($input['default_table_style']) ? sanitize_text_field($input['default_table_style']) : (isset($old_options['default_table_style']) ? $old_options['default_table_style'] : 'inherit');
        $allowed_styles = array('inherit', 'shadcn', 'clean', 'bordered', 'compact');
        $sanitized['default_table_style'] = in_array($style, $allowed_styles, true) ? $style : 'inherit';

        // 默认每页数量（范围限制）
        $per_page = isset($input['default_per_page']) ? absint($input['default_per_page']) : (isset($old_options['default_per_page']) ? absint($old_options['default_per_page']) : 20);
        if ($per_page < 5) { $per_page = 5; }
        if ($per_page > 100) { $per_page = 100; }
        $sanitized['default_per_page'] = $per_page;

        // 全局表格CSS（文本域清洗）
        if (isset($input['global_table_css'])) {
            $css = sanitize_textarea_field($input['global_table_css']);
            // 进一步去除可能的注入符号（与前端输出的保护配合）
            $css = preg_replace('/[{}<>]/', '', (string) $css);
            $sanitized['global_table_css'] = $css;
        } else if (!isset($sanitized['global_table_css'])) {
            $sanitized['global_table_css'] = '';
        }

        // 判断是否需要清理表格缓存
        $table_settings_changed = false;
        foreach (array('default_table_style', 'show_product_images') as $key) {
            $old = isset($old_options[$key]) ? $old_options[$key] : null;
            if ($old !== $sanitized[$key]) {
                $table_settings_changed = true;
                break;
            }
        }

        if ($table_settings_changed) {
            // 清除所有渲染缓存，确保样式立即生效
            $cache = new B2BPress_Cache();
            $cache->delete_by_prefix('b2bpress_rendered_table_');
            $cache->delete_group('b2bpress_table');
            if (method_exists($cache, 'bump_last_changed')) { $cache->bump_last_changed(); }
            // 设置提示
            add_settings_error(
                'b2bpress_settings',
                'cache_cleared',
                __('表格设置已更改，所有表格缓存已清除。', 'b2bpress'),
                'updated'
            );
        }

        return $sanitized;
    }
    
    /**
     * 清除所有表格缓存
     */
    private function clear_all_table_cache() {
        $cache = new B2BPress_Cache();
        $cache->refresh_all_cache();
        
        // 添加设置页面通知
        add_settings_error(
            'b2bpress_settings',
            'cache_cleared',
            __('表格设置已更改，所有表格缓存已清除。', 'b2bpress'),
            'updated'
        );
    }
} 