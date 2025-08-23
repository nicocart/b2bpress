<?php
/**
 * Plugin Name: B2BPress
 * Plugin URI: https://expansing.cc/b2bpress
 * Description: B2B eCommerce solution for WooCommerce, focused on B2B use cases and streamlined features.
 * Version: 1.2.0
 * Author: B2BPress Team
 * Author URI: https://expansing.cc
 * Text Domain: b2bpress
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * WC requires at least: 8.7
 * WC tested up to: 8.7
 */

// 如果直接访问则退出
if (!defined('ABSPATH')) {
    exit;
}

// 声明WooCommerce HPOS兼容性
add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// 定义插件常量
define('B2BPRESS_VERSION', '1.2.0');
define('B2BPRESS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('B2BPRESS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('B2BPRESS_PLUGIN_BASENAME', plugin_basename(__FILE__));

// 加载自动加载器
require_once B2BPRESS_PLUGIN_DIR . 'includes/class-b2bpress-autoloader.php';

/**
 * 插件主类
 */
final class B2BPress {
    /**
     * 单例实例
     *
     * @var B2BPress
     */
    private static $instance = null;
    
    /**
     * 依赖检查是否通过
     *
     * @var bool
     */
    private $requirements_met = false;

    /**
     * 获取单例实例
     *
     * @return B2BPress
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 构造函数
     */
    private function __construct() {
        $this->requirements_met = $this->check_requirements();
        
        // 只有当所有依赖都满足时才继续加载插件
        if ($this->requirements_met) {
            $this->includes();
            $this->init_hooks();
        }
    }

    /**
     * 检查插件依赖和要求
     * 
     * @return bool 是否满足所有要求
     */
    private function check_requirements() {
        // 检查PHP版本
        if (version_compare(PHP_VERSION, '8.0', '<')) {
            add_action('admin_notices', array($this, 'php_version_notice'));
            return false;
        }

        // 检查WordPress版本
        if (version_compare($GLOBALS['wp_version'], '6.5', '<')) {
            add_action('admin_notices', array($this, 'wp_version_notice'));
            return false;
        }

        // 检查WooCommerce是否激活
        // 使用更可靠的方法检查WooCommerce是否激活
        if (!$this->is_woocommerce_active()) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return false;
        }

        // 检查WooCommerce版本
        if (defined('WC_VERSION') && version_compare(WC_VERSION, '8.7', '<')) {
            add_action('admin_notices', array($this, 'woocommerce_version_notice'));
            return false;
        }
        
        return true;
    }
    
    /**
     * 检查WooCommerce是否激活
     * 
     * @return bool
     */
    private function is_woocommerce_active() {
        // 方法1: 检查类是否存在
        if (class_exists('WooCommerce')) {
            return true;
        }
        
        // 方法2: 检查插件是否在活动插件列表中
        if (!function_exists('is_plugin_active')) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        return is_plugin_active('woocommerce/woocommerce.php');
    }

    /**
     * PHP版本过低提示
     */
    public function php_version_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php esc_html_e('B2BPress 需要 PHP 8.0 或更高版本。请升级您的PHP版本。', 'b2bpress'); ?></p>
        </div>
        <?php
    }

    /**
     * WordPress版本过低提示
     */
    public function wp_version_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php esc_html_e('B2BPress 需要 WordPress 6.5 或更高版本。请升级您的WordPress。', 'b2bpress'); ?></p>
        </div>
        <?php
    }

    /**
     * WooCommerce未安装提示
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php 
                if (current_user_can('install_plugins')) {
                    $install_url = wp_nonce_url(
                        add_query_arg(
                            array(
                                'action' => 'install-plugin',
                                'plugin' => 'woocommerce',
                            ),
                            admin_url('update.php')
                        ),
                        'install-plugin_woocommerce'
                    );
                    printf(
                        esc_html__('B2BPress 需要 WooCommerce 插件。%1$s安装 WooCommerce%2$s 或 %3$s激活 WooCommerce%4$s。', 'b2bpress'),
                        '<a href="' . esc_url($install_url) . '">',
                        '</a>',
                        '<a href="' . esc_url(admin_url('plugins.php')) . '">',
                        '</a>'
                    );
                } else {
                    esc_html_e('B2BPress 需要 WooCommerce 插件。请联系网站管理员安装并激活WooCommerce。', 'b2bpress');
                }
            ?></p>
        </div>
        <?php
    }

    /**
     * WooCommerce版本过低提示
     */
    public function woocommerce_version_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php esc_html_e('B2BPress 需要 WooCommerce 8.7 或更高版本。请升级您的WooCommerce。', 'b2bpress'); ?></p>
        </div>
        <?php
    }

    /**
     * 包含必要的文件
     */
    private function includes() {
        // 语言管理类
        require_once B2BPRESS_PLUGIN_DIR . 'includes/class-b2bpress-language-manager.php';
        
        // 核心类
        require_once B2BPRESS_PLUGIN_DIR . 'includes/class-b2bpress-core.php';
        
        // 管理员类
        if (is_admin()) {
            require_once B2BPRESS_PLUGIN_DIR . 'admin/class-b2bpress-admin.php';
        }
        
        // 前端类
        require_once B2BPRESS_PLUGIN_DIR . 'public/class-b2bpress-public.php';

        // WooCommerce 精简（隐藏购物车/结账与价格占位）
        if (class_exists('WooCommerce')) {
            require_once B2BPRESS_PLUGIN_DIR . 'includes/woocommerce/class-b2bpress-wc-lite.php';
            new B2BPress_WC_Lite();
        }
    }

    /**
     * 初始化钩子
     */
    private function init_hooks() {
        // 激活和停用钩子
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // 初始化
        add_action('plugins_loaded', array($this, 'init'), 0);
    }

    /**
     * 插件激活
     */
    public function activate() {
        // 检查WooCommerce是否已安装并激活
        if (!$this->is_woocommerce_active()) {
            // 如果WooCommerce未激活，显示错误消息并中止激活
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(
                esc_html__('激活失败：B2BPress 需要 WooCommerce 插件。请先安装并激活 WooCommerce。', 'b2bpress'),
                esc_html__('插件依赖错误', 'b2bpress'),
                array('back_link' => true)
            );
        }
        
        // 创建必要的表格和设置
        do_action('b2bpress_activated');

        // 预生成所有表格相关缓存（保持与历史逻辑一致，但移入类内）
        try {
            if (!class_exists('B2BPress_Table_Generator')) {
                require_once B2BPRESS_PLUGIN_DIR . 'includes/tables/class-b2bpress-table-generator.php';
            }

            if (class_exists('B2BPress_Table_Generator')) {
                $table_generator = new B2BPress_Table_Generator();
                $table_generator->refresh_all_table_cache();
            }
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('B2BPress 激活后预生成缓存失败: ' . $e->getMessage());
            }
        }
    }

    /**
     * 插件停用
     */
    public function deactivate() {
        // 清理
        do_action('b2bpress_deactivated');
    }

    /**
     * 初始化插件
     */
    public function init() {
        // 如果依赖检查未通过，则不初始化插件
        if (!$this->requirements_met) {
            return;
        }
        
        // 加载文本域
        load_plugin_textdomain('b2bpress', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // 初始化核心
        $GLOBALS['b2bpress_core'] = new B2BPress_Core();
        
        // 初始化管理员
        if (is_admin()) {
            new B2BPress_Admin();
        }
        
        // 初始化前端
        new B2BPress_Public();
        
        do_action('b2bpress_init');
    }
    
    /**
     * 检查插件是否可以运行（所有依赖都满足）
     * 
     * @return bool
     */
    public function can_run() {
        return $this->requirements_met;
    }
}

/**
 * 返回插件主类的实例
 */
function B2BPress() {
    return B2BPress::instance();
}

// 保留类内的激活/停用钩子，移除全局函数注册以避免重复

// 启动插件
$GLOBALS['b2bpress'] = B2BPress(); 