<?php
/**
 * B2BPress 自动加载器类
 */
class B2BPress_Autoloader {
    /**
     * 类文件路径映射
     *
     * @var array
     */
    private static $class_map = array();

    /**
     * 初始化自动加载器
     */
    public static function init() {
        spl_autoload_register(array(__CLASS__, 'autoload'));
        self::generate_class_map();
    }

    /**
     * 自动加载类
     *
     * @param string $class_name 类名
     * @return bool
     */
    public static function autoload($class_name) {
        // 只处理我们的类前缀
        if (0 !== strpos($class_name, 'B2BPress_')) {
            return false;
        }

        // 检查类映射
        if (isset(self::$class_map[$class_name])) {
            $file = self::$class_map[$class_name];
            if (file_exists($file)) {
                require_once $file;
                return true;
            }
        }

        // 将类名转换为文件路径
        $file_name = 'class-' . str_replace('_', '-', strtolower($class_name)) . '.php';
        
        // 检查不同目录
        $directories = array(
            B2BPRESS_PLUGIN_DIR . 'includes/',
            B2BPRESS_PLUGIN_DIR . 'admin/',
            B2BPRESS_PLUGIN_DIR . 'public/',
            B2BPRESS_PLUGIN_DIR . 'includes/tables/',
            B2BPRESS_PLUGIN_DIR . 'includes/woocommerce/',
            B2BPRESS_PLUGIN_DIR . 'includes/elementor/',
            B2BPRESS_PLUGIN_DIR . 'includes/api/',
        );
        
        foreach ($directories as $directory) {
            $file = $directory . $file_name;
            if (file_exists($file)) {
                require_once $file;
                self::$class_map[$class_name] = $file;
                return true;
            }
        }
        
        return false;
    }

    /**
     * 生成类映射
     */
    private static function generate_class_map() {
        self::$class_map = array(
            'B2BPress_Core' => B2BPRESS_PLUGIN_DIR . 'includes/class-b2bpress-core.php',
            'B2BPress_Admin' => B2BPRESS_PLUGIN_DIR . 'admin/class-b2bpress-admin.php',
            'B2BPress_Public' => B2BPRESS_PLUGIN_DIR . 'public/class-b2bpress-public.php',
            // 其他类将在加载时添加到映射中
        );
    }
}

// 初始化自动加载器
B2BPress_Autoloader::init(); 