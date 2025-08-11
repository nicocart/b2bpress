<?php
/**
 * B2BPress API类
 * 
 * 用于提供REST API
 */
class B2BPress_API {
    /**
     * API命名空间
     *
     * @var string
     */
    private $namespace = 'b2bpress/v1';
    
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
        // 注册REST API路由
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * 注册REST API路由
     */
    public function register_routes() {
        // 注册表格列表路由
        register_rest_route($this->namespace, '/tables', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_tables'),
            'permission_callback' => array($this, 'get_items_permissions_check'),
        ));
        
        // 注册表格详情路由
        register_rest_route($this->namespace, '/tables/(?P<id>\d+)', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_table'),
            'permission_callback' => array($this, 'get_item_permissions_check'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ),
            ),
        ));
        
        // 注册创建表格路由
        register_rest_route($this->namespace, '/tables', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'create_table'),
            'permission_callback' => array($this, 'create_item_permissions_check'),
            'args' => array(
                'title' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'category' => array(
                    'required' => true,
                    'type' => 'integer',
                ),
                'style' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'default' => 'default',
                ),
                'columns' => array(
                    'required' => true,
                    'type' => 'array',
                ),
            ),
        ));
        
        // 注册更新表格路由
        register_rest_route($this->namespace, '/tables/(?P<id>\d+)', array(
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => array($this, 'update_table'),
            'permission_callback' => array($this, 'update_item_permissions_check'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ),
                'title' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'category' => array(
                    'type' => 'integer',
                ),
                'style' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'columns' => array(
                    'type' => 'array',
                ),
            ),
        ));
        
        // 注册删除表格路由
        register_rest_route($this->namespace, '/tables/(?P<id>\d+)', array(
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => array($this, 'delete_table'),
            'permission_callback' => array($this, 'delete_item_permissions_check'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ),
            ),
        ));
        
        // 注册表格数据路由（统一前端数据来源）
        register_rest_route($this->namespace, '/tables/(?P<id>\d+)/data', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_table_data'),
            'permission_callback' => array($this, 'get_table_data_permissions_check'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ),
                'page' => array(
                    'type' => 'integer',
                    'default' => 1,
                    'minimum' => 1,
                ),
                'per_page' => array(
                    'type' => 'integer',
                    'default' => 20,
                    'minimum' => 1,
                    'maximum' => 200,
                ),
                'search' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'default' => '',
                ),
                'category' => array(
                    'type' => 'integer',
                    'default' => 0,
                ),
                'language' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'default' => '',
                ),
            ),
        ));
    }
    
    /**
     * 检查获取表格列表的权限
     *
     * @param WP_REST_Request $request 请求对象
     * @return bool|WP_Error
     */
    public function get_items_permissions_check($request) {
        // 若未启用“需要登录”，允许匿名读取表格列表
        $options = get_option('b2bpress_options', array());
        if (empty($options['login_required'])) {
            return true;
        }

        $permissions = new B2BPress_Permissions();
        if ($permissions->can_view_b2bpress_tables()) {
            return true;
        }

        return new WP_Error('rest_forbidden', __('您没有查看表格的权限', 'b2bpress'), array('status' => 403));
    }
    
    /**
     * 检查获取表格详情的权限
     *
     * @param WP_REST_Request $request 请求对象
     * @return bool|WP_Error
     */
    public function get_item_permissions_check($request) {
        // 若未启用“需要登录”，允许匿名读取表格详情
        $options = get_option('b2bpress_options', array());
        if (empty($options['login_required'])) {
            return true;
        }

        $permissions = new B2BPress_Permissions();
        if ($permissions->can_view_b2bpress_tables()) {
            return true;
        }

        return new WP_Error('rest_forbidden', __('您没有查看表格的权限', 'b2bpress'), array('status' => 403));
    }

    /**
     * 检查获取表格数据的权限（与可见性策略对齐）
     *
     * 若 login_required=false 列表数据可匿名读取；否则要求登录
     *
     * @param WP_REST_Request $request 请求对象
     * @return bool|WP_Error
     */
    public function get_table_data_permissions_check($request) {
        $table_id = absint($request->get_param('id'));
        $post = get_post($table_id);
        if (!$post || $post->post_type !== 'b2bpress_table') {
            return new WP_Error('rest_not_found', __('表格不存在', 'b2bpress'), array('status' => 404));
        }

        // 读取表格设置
        $table_generator = new B2BPress_Table_Generator();
        $settings = $table_generator->get_table_settings($table_id);
        $login_required = isset($settings['login_required']) ? (bool)$settings['login_required'] : false;

        if ($login_required && !is_user_logged_in()) {
            return new WP_Error('rest_forbidden', __('需要登录才能查看该表格数据', 'b2bpress'), array('status' => 401));
        }

        return true;
    }
    
    /**
     * 检查创建表格的权限
     *
     * @param WP_REST_Request $request 请求对象
     * @return bool|WP_Error
     */
    public function create_item_permissions_check($request) {
        // 检查用户是否有管理B2BPress的权限
        $permissions = new B2BPress_Permissions();
        if (!$permissions->can_manage_b2bpress()) {
            return new WP_Error('rest_forbidden', __('您没有创建表格的权限', 'b2bpress'), array('status' => 403));
        }
        
        return true;
    }
    
    /**
     * 检查更新表格的权限
     *
     * @param WP_REST_Request $request 请求对象
     * @return bool|WP_Error
     */
    public function update_item_permissions_check($request) {
        // 检查用户是否有管理B2BPress的权限
        $permissions = new B2BPress_Permissions();
        if (!$permissions->can_manage_b2bpress()) {
            return new WP_Error('rest_forbidden', __('您没有更新表格的权限', 'b2bpress'), array('status' => 403));
        }
        
        return true;
    }
    
    /**
     * 检查删除表格的权限
     *
     * @param WP_REST_Request $request 请求对象
     * @return bool|WP_Error
     */
    public function delete_item_permissions_check($request) {
        // 检查用户是否有管理B2BPress的权限
        $permissions = new B2BPress_Permissions();
        if (!$permissions->can_manage_b2bpress()) {
            return new WP_Error('rest_forbidden', __('您没有删除表格的权限', 'b2bpress'), array('status' => 403));
        }
        
        return true;
    }
    
    /**
     * 获取表格列表
     *
     * @param WP_REST_Request $request 请求对象
     * @return WP_REST_Response|WP_Error
     */
    public function get_tables($request) {
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
            $table_generator = new B2BPress_Table_Generator();
            $settings = $table_generator->get_table_settings($post->ID);
            
            $tables[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'category' => isset($settings['category']) ? $settings['category'] : 0,
                'style' => isset($settings['style']) ? $settings['style'] : 'default',
                'date' => $post->post_date,
            );
        }
        
        // 返回响应
        return rest_ensure_response($tables);
    }
    
    /**
     * 获取表格详情
     *
     * @param WP_REST_Request $request 请求对象
     * @return WP_REST_Response|WP_Error
     */
    public function get_table($request) {
        // 获取表格ID
        $table_id = $request->get_param('id');
        
        // 检查表格是否存在
        $post = get_post($table_id);
        if (!$post || $post->post_type !== 'b2bpress_table') {
            return new WP_Error('rest_not_found', __('表格不存在', 'b2bpress'), array('status' => 404));
        }
        
        // 获取表格设置和列
        $table_generator = new B2BPress_Table_Generator();
        $settings = $table_generator->get_table_settings($table_id);
        $columns = $table_generator->get_table_columns($table_id);
        
        // 准备数据
        $table = array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'category' => isset($settings['category']) ? $settings['category'] : 0,
            'style' => isset($settings['style']) ? $settings['style'] : 'default',
            'columns' => $columns,
            'date' => $post->post_date,
        );
        
        // 返回响应
        return rest_ensure_response($table);
    }
    
    /**
     * 创建表格
     *
     * @param WP_REST_Request $request 请求对象
     * @return WP_REST_Response|WP_Error
     */
    public function create_table($request) {
        // 获取参数
        $title = $request->get_param('title');
        $category = $request->get_param('category');
        $style = $request->get_param('style');
        $columns = $request->get_param('columns');
        
        // 验证分类
        $term = get_term($category, 'product_cat');
        if (!$term) {
            return new WP_Error('rest_invalid_param', __('无效的产品分类', 'b2bpress'), array('status' => 400));
        }
        
        // 验证列
        if (empty($columns)) {
            return new WP_Error('rest_invalid_param', __('请选择至少一个列', 'b2bpress'), array('status' => 400));
        }
        
        // 创建表格
        $table_id = wp_insert_post(array(
            'post_title' => $title,
            'post_type' => 'b2bpress_table',
            'post_status' => 'publish',
        ));
        
        if (is_wp_error($table_id)) {
            return $table_id;
        }
        
        // 保存表格设置
        $settings = array(
            'title' => $title,
            'category' => $category,
            'style' => $style,
            'per_page' => 20,
        );
        
        update_post_meta($table_id, '_b2bpress_table_settings', $settings);
        
        // 保存表格列
        update_post_meta($table_id, '_b2bpress_table_columns', $columns);
        
        // 获取表格详情
        $table_generator = new B2BPress_Table_Generator();
        $settings = $table_generator->get_table_settings($table_id);
        $columns = $table_generator->get_table_columns($table_id);
        
        // 准备数据
        $table = array(
            'id' => $table_id,
            'title' => $title,
            'category' => $category,
            'style' => $style,
            'columns' => $columns,
            'date' => get_the_date('', $table_id),
        );
        
        // 返回响应
        return rest_ensure_response($table);
    }
    
    /**
     * 更新表格
     *
     * @param WP_REST_Request $request 请求对象
     * @return WP_REST_Response|WP_Error
     */
    public function update_table($request) {
        // 获取表格ID
        $table_id = $request->get_param('id');
        
        // 检查表格是否存在
        $post = get_post($table_id);
        if (!$post || $post->post_type !== 'b2bpress_table') {
            return new WP_Error('rest_not_found', __('表格不存在', 'b2bpress'), array('status' => 404));
        }
        
        // 获取表格设置
        $table_generator = new B2BPress_Table_Generator();
        $settings = $table_generator->get_table_settings($table_id);
        
        // 获取参数
        $title = $request->get_param('title');
        $category = $request->get_param('category');
        $style = $request->get_param('style');
        $columns = $request->get_param('columns');
        
        // 更新标题
        if (!empty($title)) {
            wp_update_post(array(
                'ID' => $table_id,
                'post_title' => $title,
            ));
            
            $settings['title'] = $title;
        }
        
        // 更新分类
        if (!empty($category)) {
            // 验证分类
            $term = get_term($category, 'product_cat');
            if (!$term) {
                return new WP_Error('rest_invalid_param', __('无效的产品分类', 'b2bpress'), array('status' => 400));
            }
            
            $settings['category'] = $category;
        }
        
        // 更新样式
        if (!empty($style)) {
            $settings['style'] = $style;
        }
        
        // 更新设置
        update_post_meta($table_id, '_b2bpress_table_settings', $settings);
        
        // 更新列
        if (!empty($columns)) {
            // 验证列
            if (empty($columns)) {
                return new WP_Error('rest_invalid_param', __('请选择至少一个列', 'b2bpress'), array('status' => 400));
            }
            
            update_post_meta($table_id, '_b2bpress_table_columns', $columns);
        }
        
        // 获取更新后的表格详情
        $settings = $table_generator->get_table_settings($table_id);
        $columns = $table_generator->get_table_columns($table_id);
        
        // 准备数据
        $table = array(
            'id' => $table_id,
            'title' => $post->post_title,
            'category' => isset($settings['category']) ? $settings['category'] : 0,
            'style' => isset($settings['style']) ? $settings['style'] : 'default',
            'columns' => $columns,
            'date' => $post->post_date,
        );
        
        // 返回响应
        return rest_ensure_response($table);
    }
    
    /**
     * 删除表格
     *
     * @param WP_REST_Request $request 请求对象
     * @return WP_REST_Response|WP_Error
     */
    public function delete_table($request) {
        // 获取表格ID
        $table_id = $request->get_param('id');
        
        // 检查表格是否存在
        $post = get_post($table_id);
        if (!$post || $post->post_type !== 'b2bpress_table') {
            return new WP_Error('rest_not_found', __('表格不存在', 'b2bpress'), array('status' => 404));
        }
        
        // 删除表格
        $result = wp_delete_post($table_id, true);
        
        if (!$result) {
            return new WP_Error('rest_cannot_delete', __('无法删除表格', 'b2bpress'), array('status' => 500));
        }
        
        // 返回响应
        return rest_ensure_response(array(
            'deleted' => true,
            'previous' => array(
                'id' => $post->ID,
                'title' => $post->post_title,
            ),
        ));
    }
    
    /**
     * 获取表格数据
     *
     * @param WP_REST_Request $request 请求对象
     * @return WP_REST_Response|WP_Error
     */
    public function get_table_data($request) {
        // 获取参数
        $table_id = $request->get_param('id');
        $page = $request->get_param('page');
        $per_page = $request->get_param('per_page');
        $search = $request->get_param('search');
        $category = (int) $request->get_param('category');
        $language = $request->get_param('language');
        
        // 检查表格是否存在
        $post = get_post($table_id);
        if (!$post || $post->post_type !== 'b2bpress_table') {
            return new WP_Error('rest_not_found', __('表格不存在', 'b2bpress'), array('status' => 404));
        }
        
        // 获取表格数据
        $table_generator = new B2BPress_Table_Generator();
        $settings = $table_generator->get_table_settings($table_id);

        // 临时应用语言（如果提供）
        if (!empty($language)) {
            switch_to_locale($language);
        }

        // 优先使用传入的 category（允许临时分类视图），否则使用表格设置
        $effective_category = $category > 0 ? $category : (isset($settings['category']) ? (int)$settings['category'] : 0);

        // 计算 ETag/Last-Modified
        $etag = $this->compute_etag($table_id, $page, $per_page, (string)$search, $effective_category);
        $last_modified_gmt = $this->compute_last_modified_gmt($table_id);

        // 条件请求命中则返回304
        $if_none_match = $request->get_header('if-none-match');
        $if_modified_since = $request->get_header('if-modified-since');
        if (!empty($if_none_match) && trim($if_none_match, '"') === $etag) {
            $response = new WP_REST_Response(null, 304);
            $this->apply_cache_headers($response, $etag, $last_modified_gmt);
            if (!empty($language)) { restore_previous_locale(); }
            return $response;
        }
        if (!empty($if_modified_since)) {
            $since = strtotime($if_modified_since);
            if ($since !== false && gmdate('D, d M Y H:i:s', $since) . ' GMT' >= $last_modified_gmt) {
                $response = new WP_REST_Response(null, 304);
                $this->apply_cache_headers($response, $etag, $last_modified_gmt);
                if (!empty($language)) { restore_previous_locale(); }
                return $response;
            }
        }

        // 获取表格数据
        $data = $this->get_data($table_id, $page, $per_page, $search, $effective_category);

        // 输出响应并附带缓存头
        $response = rest_ensure_response($data);
        $this->apply_cache_headers($response, $etag, $last_modified_gmt);

        if (!empty($language)) {
            restore_previous_locale();
        }

        return $response;
    }
    
    /**
     * 获取表格数据
     *
     * @param int $table_id 表格ID
     * @param int $page 页码
     * @param int $per_page 每页数量
     * @param string $search 搜索关键字
     * @param int $category 分类ID
     * @return array 表格数据
     */
    private function get_data($table_id, $page, $per_page, $search, $category) {
        // 创建表格生成器实例
        $table_generator = new B2BPress_Table_Generator();
        
        // 获取表格列
        $columns = $table_generator->get_table_columns($table_id);
        
        // 查询产品
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
        );
        
        // 添加分类过滤
        if ($category > 0) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $category,
                ),
            );
        }
        
        // 添加搜索过滤
        if (!empty($search)) {
            $args['s'] = $search;
        }
        
        // 执行查询
        $query = new WP_Query($args);
        
        // 准备数据
        $products = array();
        foreach ($query->posts as $post) {
            $product = wc_get_product($post->ID);
            if (!$product) {
                continue;
            }
            
            // 基本产品数据
            $product_data = array(
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'permalink' => get_permalink($product->get_id()),
                'sku' => $product->get_sku(),
            );
            
            // 添加列数据
            foreach ($columns as $column) {
                $product_data[$column['key']] = $this->get_column_value($product, $column);
            }
            
            $products[] = $product_data;
        }
        
        // 准备分页数据
        $pagination = array(
            'current_page' => $page,
            'per_page' => $per_page,
            'total_pages' => $query->max_num_pages,
            'total_items' => $query->found_posts,
        );
        
        // 从全局或表格设置确定是否显示图片
        $global_options = get_option('b2bpress_options', array());
        $show_images = isset($global_options['show_product_images']) ? (bool)$global_options['show_product_images'] : false;
        $settings = $table_generator->get_table_settings($table_id);
        if (isset($settings['show_images'])) {
            $show_images = (bool)$settings['show_images'];
        }

        // 若禁用价格，则在API层也输出占位
        $options = get_option('b2bpress_options', array());
        if (isset($options['disable_prices']) && $options['disable_prices']) {
            foreach ($products as &$p) {
                // 遍历列找出 price 类型并替换为占位
                foreach ($columns as $col) {
                    if ($col['type'] === 'price') {
                        $p[$col['key']] = is_user_logged_in() ? __('面议', 'b2bpress') : __('登录后可见', 'b2bpress');
                    }
                }
            }
            unset($p);
        }

        // 准备返回数据
        $data = array(
            'products' => $products,
            'pagination' => $pagination,
            'columns' => $columns,
            'show_images' => $show_images,
        );
        
        return $data;
    }

    /**
     * 计算 ETag
     */
    private function compute_etag($table_id, $page, $per_page, $search, $category) {
        $last_changed = get_option('b2bpress_last_changed', '0');
        $post = get_post($table_id);
        $modified = $post ? strtotime($post->post_modified_gmt) : 0;
        $token = implode('|', array($table_id, (int)$page, (int)$per_page, (string)$search, (int)$category, (string)$last_changed, (int)$modified));
        return md5($token);
    }

    /**
     * 计算 Last-Modified (GMT 格式)
     */
    private function compute_last_modified_gmt($table_id) {
        $last_changed = get_option('b2bpress_last_changed', '0');
        $post = get_post($table_id);
        $modified_ts = $post ? max(strtotime($post->post_modified_gmt), (int)$last_changed) : (int)$last_changed;
        if (!$modified_ts) {
            $modified_ts = time();
        }
        return gmdate('D, d M Y H:i:s', $modified_ts) . ' GMT';
    }

    /**
     * 为响应添加缓存头
     */
    private function apply_cache_headers($response, $etag, $last_modified_gmt) {
        if ($response instanceof WP_REST_Response) {
            $response->header('Cache-Control', 'public, max-age=300, must-revalidate');
            $response->header('ETag', '"' . $etag . '"');
            $response->header('Last-Modified', $last_modified_gmt);
        }
    }
    
    /**
     * 获取列值
     *
     * @param WC_Product $product 产品对象
     * @param array $column 列信息
     * @return string 列值
     */
    private function get_column_value($product, $column) {
        $value = '';
        
        switch ($column['type']) {
            case 'attribute':
                $value = $this->get_attribute_value($product, $column['key']);
                break;
                
            case 'taxonomy':
                $value = $this->get_taxonomy_value($product, $column['key']);
                break;
                
            case 'meta':
                $value = $this->get_meta_value($product, $column['key']);
                break;
                
            case 'sku':
                $value = $product->get_sku();
                break;
                
            case 'price':
                $value = $product->get_price_html();
                break;
                
            case 'stock':
                $value = $product->get_stock_status();
                $value = $this->format_stock_status($value);
                break;
                
            default:
                $value = apply_filters('b2bpress_column_value_' . $column['key'], '', $product, $column);
                break;
        }
        
        return $value;
    }
    
    /**
     * 获取属性值
     *
     * @param WC_Product $product 产品对象
     * @param string $attribute_key 属性键
     * @return string 属性值
     */
    private function get_attribute_value($product, $attribute_key) {
        $attribute_key = str_replace('pa_', '', $attribute_key);
        $attribute_key = 'pa_' . $attribute_key;
        
        $attributes = $product->get_attributes();
        if (isset($attributes[$attribute_key])) {
            $attribute = $attributes[$attribute_key];
            if ($attribute->is_taxonomy()) {
                $terms = wp_get_post_terms($product->get_id(), $attribute_key, array('fields' => 'names'));
                return implode(', ', $terms);
            } else {
                return $attribute->get_options();
            }
        }
        
        return '';
    }
    
    /**
     * 获取分类值
     *
     * @param WC_Product $product 产品对象
     * @param string $taxonomy 分类法
     * @return string 分类值
     */
    private function get_taxonomy_value($product, $taxonomy) {
        $terms = wp_get_post_terms($product->get_id(), $taxonomy, array('fields' => 'names'));
        return implode(', ', $terms);
    }
    
    /**
     * 获取元数据值
     *
     * @param WC_Product $product 产品对象
     * @param string $meta_key 元数据键
     * @return string 元数据值
     */
    private function get_meta_value($product, $meta_key) {
        return $product->get_meta($meta_key, true);
    }
    
    /**
     * 格式化库存状态
     *
     * @param string $status 库存状态
     * @return string 格式化后的库存状态
     */
    private function format_stock_status($status) {
        switch ($status) {
            case 'instock':
                return __('有库存', 'b2bpress');
                
            case 'outofstock':
                return __('缺货', 'b2bpress');
                
            case 'onbackorder':
                return __('可预订', 'b2bpress');
                
            default:
                return $status;
        }
    }
} 