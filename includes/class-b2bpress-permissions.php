<?php
/**
 * B2BPress 权限管理类
 * 
 * 用于管理插件权限
 */
class B2BPress_Permissions {
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
        // 注册激活钩子
        add_action('b2bpress_activated', array($this, 'add_capabilities'));
        
        // 注册停用钩子
        add_action('b2bpress_deactivated', array($this, 'remove_capabilities'));
        
        // 过滤表格可见性
        add_filter('b2bpress_table_visible', array($this, 'filter_table_visibility'), 10, 2);
        
        // 过滤列可见性
        add_filter('b2bpress_column_visible', array($this, 'filter_column_visibility'), 10, 3);
    }
    
    /**
     * 添加自定义能力
     */
    public function add_capabilities() {
        // 获取管理员角色
        $admin = get_role('administrator');
        
        // 添加自定义能力
        if ($admin) {
            $admin->add_cap('manage_b2bpress');
            $admin->add_cap('view_b2bpress_tables');
        }
        
        // 获取店铺管理员角色
        $shop_manager = get_role('shop_manager');
        
        // 添加自定义能力
        if ($shop_manager) {
            $shop_manager->add_cap('manage_b2bpress');
            $shop_manager->add_cap('view_b2bpress_tables');
        }
    }
    
    /**
     * 移除自定义能力
     */
    public function remove_capabilities() {
        // 获取所有角色
        $roles = wp_roles();
        
        // 移除所有角色的自定义能力
        foreach ($roles->role_objects as $role) {
            $role->remove_cap('manage_b2bpress');
            $role->remove_cap('view_b2bpress_tables');
        }
    }
    
    /**
     * 过滤表格可见性
     *
     * @param bool $visible 是否可见
     * @param array $args 表格参数
     * @return bool
     */
    public function filter_table_visibility($visible, $args) {
        // 获取设置
        $options = get_option('b2bpress_options', array());
        
        // 如果需要登录且用户未登录
        if (isset($options['login_required']) && $options['login_required'] && !is_user_logged_in()) {
            return false;
        }
        
        // 检查表格设置中的可见性
        if (isset($args['visibility']) && $args['visibility'] === 'roles') {
            // 如果用户未登录，不可见
            if (!is_user_logged_in()) {
                return false;
            }
            
            // 获取当前用户
            $user = wp_get_current_user();
            
            // 获取允许的角色
            $allowed_roles = isset($args['allowed_roles']) ? $args['allowed_roles'] : array();
            
            // 检查用户角色
            $has_role = false;
            foreach ($user->roles as $role) {
                if (in_array($role, $allowed_roles)) {
                    $has_role = true;
                    break;
                }
            }
            
            // 如果用户没有允许的角色，不可见
            if (!$has_role) {
                return false;
            }
        }
        
        return $visible;
    }
    
    /**
     * 过滤列可见性
     *
     * @param bool $visible 是否可见
     * @param array $column 列信息
     * @param array $args 表格参数
     * @return bool
     */
    public function filter_column_visibility($visible, $column, $args) {
        // 检查列设置中的可见性
        if (isset($column['visibility']) && $column['visibility'] === 'roles') {
            // 如果用户未登录，不可见
            if (!is_user_logged_in()) {
                return false;
            }
            
            // 获取当前用户
            $user = wp_get_current_user();
            
            // 获取允许的角色
            $allowed_roles = isset($column['allowed_roles']) ? $column['allowed_roles'] : array();
            
            // 检查用户角色
            $has_role = false;
            foreach ($user->roles as $role) {
                if (in_array($role, $allowed_roles)) {
                    $has_role = true;
                    break;
                }
            }
            
            // 如果用户没有允许的角色，不可见
            if (!$has_role) {
                return false;
            }
        }
        
        return $visible;
    }
    
    /**
     * 检查用户是否有管理B2BPress的权限
     *
     * @param int $user_id 用户ID
     * @return bool
     */
    public function can_manage_b2bpress($user_id = null) {
        // 如果没有指定用户ID，使用当前用户
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        // 检查用户是否有管理B2BPress的权限
        return user_can($user_id, 'manage_b2bpress');
    }
    
    /**
     * 检查用户是否可以查看B2BPress表格
     *
     * @param int $user_id 用户ID
     * @return bool
     */
    public function can_view_b2bpress_tables($user_id = null) {
        // 如果没有指定用户ID，使用当前用户
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        // 检查用户是否可以查看B2BPress表格
        return user_can($user_id, 'view_b2bpress_tables');
    }
} 