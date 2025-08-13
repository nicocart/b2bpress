<?php
/**
 * B2BPress 表格生成器类
 * 
 * 用于生成和显示产品表格
 */
class B2BPress_Table_Generator {
    /**
     * 表格ID
     *
     * @var int
     */
    private $table_id;
    
    /**
     * 表格设置
     *
     * @var array
     */
    private $settings;
    
    /**
     * 表格列
     *
     * @var array
     */
    private $columns;
    
    /**
     * 缓存实例
     *
     * @var B2BPress_Cache
     */
    private $cache;
    
    /**
     * 核心实例
     *
     * @var B2BPress_Core
     */
    private $core;
    
    /**
     * 构造函数
     */
    public function __construct() {
        $this->cache = new B2BPress_Cache();
        $this->init_hooks();
        
        // 获取核心实例
        $this->core = isset($GLOBALS['b2bpress_core']) ? $GLOBALS['b2bpress_core'] : null;
    }
    
    /**
     * 调度异步生成表格缓存
     */
    public function schedule_generate_table_cache($post_id, $post = null, $update = null) {
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }
        if (get_post_type($post_id) !== 'b2bpress_table') {
            return;
        }
        if (!wp_next_scheduled('b2bpress_generate_table_cache', array($post_id))) {
            wp_schedule_single_event(time() + 5, 'b2bpress_generate_table_cache', array($post_id));
        }
    }

    /**
     * WP-Cron 任务：生成表格缓存
     */
    public function generate_table_cache_job($post_id) {
        $this->generate_table_cache($post_id);
    }
    
    /**
     * 初始化钩子
     */
    private function init_hooks() {
        // 添加AJAX处理
        add_action('wp_ajax_b2bpress_get_table_data', array($this, 'ajax_get_table_data'));
        add_action('wp_ajax_nopriv_b2bpress_get_table_data', array($this, 'ajax_get_table_data'));
        
        // 添加AJAX保存表格
        add_action('wp_ajax_b2bpress_save_table', array($this, 'ajax_save_table'));
        
        // 添加AJAX删除表格
        add_action('wp_ajax_b2bpress_delete_table', array($this, 'ajax_delete_table'));
        
        // 添加AJAX获取分类属性
        add_action('wp_ajax_b2bpress_get_category_attributes', array($this, 'ajax_get_category_attributes'));
        
        // 监听产品变更
        add_action('save_post_product', array($this, 'invalidate_cache'), 10, 3);
        add_action('before_delete_post', array($this, 'invalidate_cache_on_delete'));
        add_action('woocommerce_update_product', array($this, 'invalidate_cache'));
        
        // 监听表格保存和更新（改为异步任务，避免阻塞）
        add_action('save_post_b2bpress_table', array($this, 'schedule_generate_table_cache'), 10, 3);
        
        // 监听缓存插件清除缓存的操作
        add_action('after_rocket_clean_domain', array($this, 'refresh_all_table_cache')); // WP Rocket
        add_action('wpfc_delete_cache', array($this, 'refresh_all_table_cache')); // WP Fastest Cache
        add_action('w3tc_flush_all', array($this, 'refresh_all_table_cache')); // W3 Total Cache

        // 后台任务：生成表格缓存
        add_action('b2bpress_generate_table_cache', array($this, 'generate_table_cache_job'), 10, 1);

        // 监听术语（分类与属性）变更，精准失效相关表格缓存
        add_action('created_term', array($this, 'on_term_changed'), 10, 3);
        add_action('edited_term', array($this, 'on_term_changed'), 10, 3);
        add_action('delete_term', array($this, 'on_term_changed'), 10, 3);
    }

    /**
     * 术语变更时使相关缓存失效
     *
     * @param int $term_id 术语ID
     * @param int $tt_id 术语taxonomy ID
     * @param string $taxonomy 分类法
     */
    public function on_term_changed($term_id, $tt_id = 0, $taxonomy = '') {
        try {
            // 仅处理产品分类与属性分类
            if ($taxonomy !== 'product_cat' && strpos($taxonomy, 'pa_') !== 0) {
                return;
            }
            // 尽量精细化：目前未建立表格到分类映射，先清理与表格相关的缓存前缀
            $this->cache->delete_group('b2bpress_table');
            $this->cache->delete_by_prefix('b2bpress_rendered_table_');
            $this->cache->delete_by_prefix('b2bpress_table_all_data_');
        } catch (Exception $e) {
            // 仅记录调试日志
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('B2BPress 术语变更缓存失效失败: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * 获取当前用户的语言偏好
     *
     * @param bool $is_frontend 是否是前端请求
     * @return string 语言代码
     */
    private function get_user_language($is_frontend = false) {
        if (isset($this->core) && method_exists($this->core, 'get_language_manager')) {
            $language_manager = $this->core->get_language_manager();
            return $language_manager->get_appropriate_language($is_frontend);
        }
        
        // 默认返回英文
        return 'en_US';
    }
    
    /**
     * 应用语言设置
     * 
     * @param bool $is_frontend 是否是前端请求
     */
    private function apply_language($is_frontend = false) {
        if (isset($this->core) && method_exists($this->core, 'get_language_manager')) {
            $language_manager = $this->core->get_language_manager();
            $language_manager->apply_language($is_frontend);
            return;
        }
        
        // 如果无法获取语言管理器，使用默认语言
        switch_to_locale('en_US');
    }
    
    /**
     * 恢复原始语言
     */
    private function restore_original_language() {
        if (isset($this->core) && method_exists($this->core, 'get_language_manager')) {
            $language_manager = $this->core->get_language_manager();
            $language_manager->restore_original_language();
            return;
        }
        
        // 如果无法获取语言管理器，使用WordPress函数
        restore_previous_locale();
    }
    
    /**
     * 渲染表格
     *
     * @param array $args 表格参数
     * @return string 表格HTML
     */
    public function render_table($args) {
        // 应用站点语言（前端表格）
        $this->apply_language(true);
        
        // 获取表格ID
        $this->table_id = absint($args['id']);
        
        // 如果没有表格ID但有分类，则使用分类创建临时表格
        if ($this->table_id === 0 && !empty($args['category'])) {
            $result = $this->render_category_table($args);
            // 恢复原始语言
            $this->restore_original_language();
            return $result;
        }
        
        // 如果没有有效的表格ID，返回错误消息
        if ($this->table_id === 0) {
            $result = '<p class="b2bpress-error">' . __('Invalid table ID', 'b2bpress') . '</p>';
            // 恢复原始语言
            $this->restore_original_language();
            return $result;
        }
        
        // 获取表格设置
        $this->settings = $this->get_table_settings($this->table_id);
        
        // 如果表格不存在，返回错误消息
        if (empty($this->settings)) {
            $result = '<p class="b2bpress-error">' . __('Table does not exist', 'b2bpress') . '</p>';
            // 恢复原始语言
            $this->restore_original_language();
            return $result;
        }
        
        // 合并短代码参数和表格设置
        $this->settings = wp_parse_args($args, $this->settings);
        
        // 获取表格列
        $this->columns = $this->get_table_columns($this->table_id);
        
        // 尝试从缓存获取预渲染的表格内容
        $cache_key = 'b2bpress_rendered_table_' . $this->table_id . '_' . md5(serialize($this->settings));
        $cached_content = $this->cache->get($cache_key);
        
        if ($cached_content !== false) {
            // 恢复原始语言
            $this->restore_original_language();
            return $cached_content;
        }
        
        // 如果没有缓存，生成表格内容并缓存
        $content = $this->generate_table_html();
        
        // 缓存表格内容（24小时）
        $this->cache->set($cache_key, $content, 86400);
        
        // 恢复原始语言
        $this->restore_original_language();
        
        return $content;
    }
    
    /**
     * 渲染分类表格
     *
     * @param array $args 表格参数
     * @return string 表格HTML
     */
    private function render_category_table($args) {
        // 获取分类ID
        $category_slug = sanitize_text_field($args['category']);
        $category = get_term_by('slug', $category_slug, 'product_cat');
        
        if (!$category) {
            return '<p class="b2bpress-error">' . __('Invalid product category', 'b2bpress') . '</p>';
        }
        
        // 创建临时表格设置
        $this->settings = array(
            'id' => 0,
            'title' => $category->name,
            'category' => $category->term_id,
            'style' => $args['style'],
            'per_page' => $args['per_page'],
            'show_images' => isset($args['show_images']) ? filter_var($args['show_images'], FILTER_VALIDATE_BOOLEAN) : false,
        );
        
        // 获取分类的所有属性
        $this->columns = $this->get_category_attributes($category->term_id);
        
        // 开始输出缓冲
        ob_start();
        
        // 触发表格前的操作
        do_action('b2bpress_before_table', $this->settings);
        
        // 渲染表格HTML
        $this->render_table_html();
        
        // 触发表格后的操作
        do_action('b2bpress_after_table', $this->settings);
        
        // 返回输出缓冲内容
        return ob_get_clean();
    }
    
    /**
     * 渲染表格HTML
     */
    private function render_table_html() {
        // 获取表格样式：优先使用当前设置（用于分类短码临时表格），否则回退到全局设置
        $style = isset($this->settings['style']) ? $this->settings['style'] : 'default';
        if ($style === 'default') {
            $global_options = get_option('b2bpress_options', array());
            $style = isset($global_options['default_table_style']) ? $global_options['default_table_style'] : 'inherit';
        }
        // 若当前样式为 inherit 而全局样式非 inherit，则使用全局样式
        if ($style === 'inherit') {
            $global_options = isset($global_options) ? $global_options : get_option('b2bpress_options', array());
            $global_style = isset($global_options['default_table_style']) ? $global_options['default_table_style'] : 'inherit';
            if ($global_style !== 'inherit') {
                $style = $global_style;
            }
        }
        $style = $this->normalize_style($style);
        
        // 表格容器类
        $table_class = 'b2bpress-table b2bpress-table-' . esc_attr($style);
        
        // 获取全局设置中的图片显示选项
        $global_options = get_option('b2bpress_options', array());
        $show_images = isset($global_options['show_product_images']) ? (bool)$global_options['show_product_images'] : false;
        
        // 更新设置
        $this->settings['show_images'] = $show_images;
        
        // 计算列数
        $column_count = count($this->columns) + 1; // 产品名称列 + 其他列
        if ($show_images) {
            $column_count++; // 如果显示图片，再加1列
        }
        
        // 获取用户语言偏好
        $user_language = $this->get_user_language(true);
        
        // 输出表格HTML（表头列添加 scope="col" 以提升可访问性）
        ?>
        <div class="b2bpress-table-wrapper">
            <table class="<?php echo esc_attr($table_class); ?>" data-per-page="<?php echo esc_attr($this->settings['per_page']); ?>">
                <thead>
                    <tr>
                        <?php $this->render_table_header(); ?>
                    </tr>
                </thead>
                <tbody class="b2bpress-table-body">
                    <tr>
                        <td colspan="<?php echo $column_count; ?>" class="b2bpress-loading">
                            <?php esc_html_e('Loading...', 'b2bpress'); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="b2bpress-table-pagination" role="navigation" aria-label="Table pagination">
            <div class="b2bpress-table-pagination-info" aria-live="polite"></div>
            <div class="b2bpress-table-pagination-links"></div>
        </div>
        
        <?php if (current_user_can('manage_b2bpress') && $this->table_id > 0) : ?>
            <div class="b2bpress-table-actions">
                <a href="<?php echo esc_url(admin_url('admin.php?page=b2bpress-tables&action=edit&id=' . $this->table_id)); ?>" class="button">
                    <?php esc_html_e('Edit Table', 'b2bpress'); ?>
                </a>
                <button type="button" class="button b2bpress-refresh-table" data-id="<?php echo esc_attr($this->table_id); ?>">
                    <?php esc_html_e('Refresh Cache', 'b2bpress'); ?>
                </button>
            </div>
        <?php endif; ?>
        <?php
    }
    
    /**
     * 渲染表格头部
     */
    private function render_table_header() {
        // 获取全局设置中的图片显示选项
        $global_options = get_option('b2bpress_options', array());
        $show_images = isset($global_options['show_product_images']) ? (bool)$global_options['show_product_images'] : false;
        
        // 更新设置
        $this->settings['show_images'] = $show_images;
        
        // 只有在设置为显示图片时才输出缩略图列
        if ($this->settings['show_images']) {
            echo '<th scope="col" class="b2bpress-column-thumbnail">' . esc_html__('Image', 'b2bpress') . '</th>';
        }
        
        // 输出产品名称列（允许扩展过滤列头）
        echo apply_filters(
            'b2bpress_table_header_cell',
            '<th scope="col" class="b2bpress-column-name">' . esc_html__('Product Name', 'b2bpress') . '</th>',
            array('key' => 'name', 'label' => __('Product Name', 'b2bpress'), 'type' => 'name'),
            $this->table_id
        );
        
        // 输出其他列
        foreach ($this->columns as $column) {
            $th_html = '<th scope="col" class="b2bpress-column-' . esc_attr($column['key']) . '">' . esc_html($column['label']) . '</th>';
            echo apply_filters('b2bpress_table_header_cell', $th_html, $column, $this->table_id);
        }
    }
    
    /**
     * AJAX获取表格数据
     */
    public function ajax_get_table_data() {
        // 检查nonce
        check_ajax_referer('b2bpress-public-nonce', 'nonce');
        
        // 获取参数
        $table_id = isset($_POST['table_id']) ? absint($_POST['table_id']) : 0;
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 20;
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $category = isset($_POST['category']) ? absint($_POST['category']) : 0;
        $language = isset($_POST['language']) ? sanitize_text_field($_POST['language']) : '';
        
        // 基于设置的访问控制
        $options = get_option('b2bpress_options', array());
        if (!empty($options['login_required']) && !is_user_logged_in()) {
            wp_send_json_error(__('需要登录才能查看表格数据', 'b2bpress'));
        }

        if ($table_id > 0) {
            $settings_for_acl = $this->get_table_settings($table_id);
            if (!empty($settings_for_acl['login_required']) && !is_user_logged_in()) {
                wp_send_json_error(__('需要登录才能查看该表格数据', 'b2bpress'));
            }
        }

        // 判断请求来源
        $is_frontend = true; // 默认假设是前端请求
        
        // 如果前端传递了语言参数，临时切换语言
        if (!empty($language)) {
            switch_to_locale($language);
        } else {
            // 否则应用适当的语言设置（前端优先用户语言）
            $this->apply_language($is_frontend);
        }
        
        // 获取表格数据
        $data = $this->get_table_data($table_id, $page, $per_page, $search, $category);
        
        // 恢复原始语言
        $this->restore_original_language();
        
        // 返回JSON响应
        wp_send_json_success($data);
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
    private function get_table_data($table_id, $page, $per_page, $search, $category) {
        // 生成缓存键
        $cache_key = 'b2bpress_table_' . $table_id . '_' . $category . '_' . $page . '_' . $per_page . '_' . md5($search);
        
        // 尝试从缓存获取数据
        $data = $this->cache->get($cache_key);
        if ($data !== false) {
            return $data;
        }
        
        // 获取全局设置中的图片显示选项
        $global_options = get_option('b2bpress_options', array());
        $show_images = isset($global_options['show_product_images']) ? (bool)$global_options['show_product_images'] : false;
        
        // 如果有表格ID，获取表格设置和列
        if ($table_id > 0) {
            $this->settings = $this->get_table_settings($table_id);
            $this->columns = $this->get_table_columns($table_id);
            $category = $this->settings['category'];
            // 如果表格设置中明确指定了是否显示图片，则使用表格设置；否则使用全局设置
            $this->settings['show_images'] = isset($this->settings['show_images']) ? (bool)$this->settings['show_images'] : $show_images;
        } else if ($category > 0) {
            // 如果没有表格ID但有分类ID，获取分类的所有属性
            $this->columns = $this->get_category_attributes($category);
            // 使用全局设置
            if (!isset($this->settings)) {
                $this->settings = array();
            }
            $this->settings['show_images'] = $show_images;
        } else {
            // 如果既没有表格ID也没有分类ID，返回错误
            return array(
                'error' => __('Invalid table ID or category ID', 'b2bpress'),
            );
        }
        
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
            
            // 只有在设置为显示图片时才添加缩略图
            if ($this->settings['show_images']) {
                $product_data['thumbnail'] = $this->get_product_thumbnail($product);
            }
            
            // 添加列数据（在服务端做安全处理，避免前端插入未转义HTML）
            foreach ($this->columns as $column) {
                $raw_value = $this->get_column_value($product, $column);
                $product_data[$column['key']] = $this->sanitize_cell_output($raw_value, $column);
            }
            
            $products[] = $product_data;
        }
        
        // 若禁用价格，则在AJAX层同样进行占位处理，防止价格泄露
        $global_options_for_price = get_option('b2bpress_options', array());
        if (isset($global_options_for_price['disable_prices']) && $global_options_for_price['disable_prices']) {
            foreach ($products as &$p) {
                foreach ($this->columns as $col) {
                    if ($col['type'] === 'price') {
                        $p[$col['key']] = is_user_logged_in() ? __('面议', 'b2bpress') : __('登录后可见', 'b2bpress');
                    }
                }
            }
            unset($p);
        }
        
        // 准备分页数据
        $pagination = array(
            'current_page' => $page,
            'per_page' => $per_page,
            'total_pages' => $query->max_num_pages,
            'total_items' => $query->found_posts,
        );
        
        // 准备返回数据
        $data = array(
            'products' => $products,
            'pagination' => $pagination,
            'columns' => $this->columns,
            'show_images' => $this->settings['show_images'],
        );
        
        // 缓存数据
        $this->cache->set($cache_key, $data, 3600); // 缓存1小时
        
        return $data;
    }
    
    /**
     * 获取产品缩略图
     *
     * @param WC_Product $product 产品对象
     * @return string 缩略图HTML
     */
    private function get_product_thumbnail($product) {
        $image_id = $product->get_image_id();
        if ($image_id) {
            $image = wp_get_attachment_image_src($image_id, 'thumbnail');
            if ($image) {
                return '<img src="' . esc_url($image[0]) . '" alt="' . esc_attr($product->get_name()) . '" />';
            }
        }
        
        return wc_placeholder_img('thumbnail');
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
                // 价格HTML允许有限HTML
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
     * 根据列类型安全输出单元格内容
     *
     * @param mixed $value 原始值
     * @param array $column 列配置
     * @return string 已转义的值
     */
    private function sanitize_cell_output($value, $column) {
        // 允许的HTML类型：价格、缩略图、外部过滤后已HTML
        $html_allowed_types = array('price');

        $sanitized = '';

        if (isset($column['key']) && $column['key'] === 'thumbnail') {
            $sanitized = wp_kses_post((string)$value);
        } elseif (isset($column['type']) && in_array($column['type'], $html_allowed_types, true)) {
            $sanitized = wp_kses_post((string)$value);
        } else {
            // 其他全部按纯文本处理
            $sanitized = esc_html((string)$value);
        }

        // 仅对属性列应用前后缀
        if (isset($column['type']) && $column['type'] === 'attribute') {
            // 保留空格：不用 sanitize_text_field，按文本输出并进行HTML转义，但不trim
            $prefix = isset($column['prefix']) ? htmlspecialchars((string)$column['prefix'], ENT_QUOTES, 'UTF-8') : '';
            $suffix = isset($column['suffix']) ? htmlspecialchars((string)$column['suffix'], ENT_QUOTES, 'UTF-8') : '';
            $sanitized = $prefix . $sanitized . $suffix;
        }

        return $sanitized;
    }
    
    /**
     * 获取属性值
     *
     * @param WC_Product $product 产品对象
     * @param string $attribute_key 属性键
     * @return string 属性值
     */
    private function get_attribute_value($product, $attribute_key) {
        // 处理自定义属性
        if (strpos($attribute_key, 'custom_') === 0) {
            $custom_attribute_name = str_replace('custom_', '', $attribute_key);
            $attributes = $product->get_attributes();
            
            foreach ($attributes as $attribute) {
                if (!$attribute->is_taxonomy() && sanitize_title($attribute->get_name()) === $custom_attribute_name) {
                    return implode(', ', $attribute->get_options());
                }
            }
            
            return '';
        }
        
        // 处理全局属性
        $attribute_key = str_replace('pa_', '', $attribute_key);
        $attribute_key = 'pa_' . $attribute_key;
        
        $attributes = $product->get_attributes();
        if (isset($attributes[$attribute_key])) {
            $attribute = $attributes[$attribute_key];
            if ($attribute->is_taxonomy()) {
                $terms = wp_get_post_terms($product->get_id(), $attribute_key, array('fields' => 'names'));
                return implode(', ', $terms);
            } else {
                return implode(', ', $attribute->get_options());
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
                return __('In Stock', 'b2bpress');
                
            case 'outofstock':
                return __('Out of Stock', 'b2bpress');
                
            case 'onbackorder':
                return __('On Backorder', 'b2bpress');
                
            default:
                return $status;
        }
    }
    
    /**
     * 获取表格设置
     *
     * @param int $table_id 表格ID
     * @return array 表格设置
     */
    public function get_table_settings($table_id) {
        $settings = get_post_meta($table_id, '_b2bpress_table_settings', true);
        if (empty($settings)) {
            return array();
        }
        
        return $settings;
    }
    
    /**
     * 获取表格列
     *
     * @param int $table_id 表格ID
     * @return array 表格列
     */
    public function get_table_columns($table_id) {
        $columns = get_post_meta($table_id, '_b2bpress_table_columns', true);
        if (empty($columns)) {
            return array();
        }
        
        return $columns;
    }
    
    /**
     * 获取分类属性
     *
     * @param int $category_id 分类ID
     * @return array 分类属性
     */
    public function get_category_attributes($category_id) {
        // 获取分类下的所有产品
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $category_id,
                ),
            ),
        );
        
        $query = new WP_Query($args);
        
        // 收集所有属性
        $attributes = array();
        foreach ($query->posts as $post) {
            $product = wc_get_product($post->ID);
            if (!$product) {
                continue;
            }
            
            $product_attributes = $product->get_attributes();
            foreach ($product_attributes as $attribute_key => $attribute) {
                if ($attribute->is_taxonomy()) {
                    $taxonomy = $attribute->get_taxonomy_object();
                    $attributes[$attribute_key] = array(
                        'key' => $attribute_key,
                        'label' => $taxonomy->attribute_label,
                        'type' => 'attribute',
                    );
                }
            }
        }
        
        // 添加默认列
        $default_columns = array(
            array(
                'key' => 'sku',
                'label' => __('SKU', 'b2bpress'),
                'type' => 'sku',
            ),
            array(
                'key' => 'stock',
                'label' => __('Stock Status', 'b2bpress'),
                'type' => 'stock',
            ),
        );
        
        return array_merge($default_columns, array_values($attributes));
    }
    
    /**
     * AJAX保存表格
     */
    public function ajax_save_table() {
        // 应用用户语言偏好
        $this->apply_language(); // 保存表格时，假设是后台请求
        
        // 检查nonce
        check_ajax_referer('b2bpress-admin-nonce', 'nonce');
        
        // 检查权限
        if (!current_user_can('manage_b2bpress')) {
            wp_send_json_error(__('Insufficient permissions', 'b2bpress'));
        }
        
        // 获取表格数据
        $table_id = isset($_POST['table_id']) ? absint($_POST['table_id']) : 0;
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $category = isset($_POST['category']) ? absint($_POST['category']) : 0;
        $columns = isset($_POST['columns']) ? $this->sanitize_columns($_POST['columns']) : array();
        
        // 验证数据
        if (empty($title)) {
            wp_send_json_error(__('Please enter a table title', 'b2bpress'));
        }
        
        if ($category === 0) {
            wp_send_json_error(__('Please select a product category', 'b2bpress'));
        }
        
        if (empty($columns)) {
            wp_send_json_error(__('Please select at least one column', 'b2bpress'));
        }
        
        // 准备表格设置
        $settings = array(
            'title' => $title,
            'category' => $category,
            'per_page' => 20,
        );
        
        // 如果是新表格，创建表格
        if ($table_id === 0) {
            $table_id = wp_insert_post(array(
                'post_title' => $title,
                'post_type' => 'b2bpress_table',
                'post_status' => 'publish',
            ));
        } else {
            // 更新表格标题
            wp_update_post(array(
                'ID' => $table_id,
                'post_title' => $title,
            ));
        }
        
        // 保存表格设置
        update_post_meta($table_id, '_b2bpress_table_settings', $settings);
        
        // 保存表格列
        update_post_meta($table_id, '_b2bpress_table_columns', $columns);
        
        // 清除缓存
        $this->invalidate_cache($table_id);
        
        // 恢复原始语言
        $this->restore_original_language();
        
        // 返回成功响应
        wp_send_json_success(array(
            'table_id' => $table_id,
            'message' => __('Table saved', 'b2bpress'),
        ));
    }
    
    /**
     * 清理列数据
     *
     * @param array $columns 列数据
     * @return array 清理后的列数据
     */
    private function sanitize_columns($columns) {
        $sanitized = array();
        
        if (!is_array($columns)) {
            return $sanitized;
        }
        
        foreach ($columns as $column) {
            if (!isset($column['key']) || !isset($column['label']) || !isset($column['type'])) {
                continue;
            }
            
            $item = array(
                'key' => sanitize_text_field($column['key']),
                'label' => sanitize_text_field($column['label']),
                'type' => sanitize_text_field($column['type']),
            );

            // 仅对属性列接受前后缀
            if (isset($column['type']) && $column['type'] === 'attribute') {
                if (isset($column['prefix'])) {
                    // 仅移除潜在控制字符，保留空格
                    $item['prefix'] = wp_kses_post((string) wp_unslash($column['prefix']));
                }
                if (isset($column['suffix'])) {
                    $item['suffix'] = wp_kses_post((string) wp_unslash($column['suffix']));
                }
            }

            $sanitized[] = $item;
        }
        
        return $sanitized;
    }
    
    /**
     * AJAX删除表格
     */
    public function ajax_delete_table() {
        // 应用用户语言偏好
        $this->apply_language(); // 删除表格时，假设是后台请求
        
        // 检查nonce
        check_ajax_referer('b2bpress-admin-nonce', 'nonce');
        
        // 检查权限
        if (!current_user_can('manage_b2bpress')) {
            wp_send_json_error(__('Insufficient permissions', 'b2bpress'));
        }
        
        // 获取表格ID
        $table_id = isset($_POST['table_id']) ? absint($_POST['table_id']) : 0;
        
        // 验证表格ID
        if ($table_id === 0) {
            wp_send_json_error(__('Invalid table ID', 'b2bpress'));
        }
        
        // 删除表格
        wp_delete_post($table_id, true);
        
        // 清除缓存
        $this->invalidate_cache($table_id);
        
        // 恢复原始语言
        $this->restore_original_language();
        
        // 返回成功响应
        wp_send_json_success(array(
            'message' => __('Table deleted', 'b2bpress'),
        ));
    }
    
    /**
     * AJAX获取分类属性
     */
    public function ajax_get_category_attributes() {
        // 应用用户语言偏好
        $this->apply_language(); // 获取分类属性时，假设是后台请求
        
        // 检查nonce
        check_ajax_referer('b2bpress-admin-nonce', 'nonce');
        
        // 检查权限
        if (!current_user_can('manage_b2bpress')) {
            wp_send_json_error(__('Insufficient permissions', 'b2bpress'));
        }
        
        // 获取分类ID
        $category_id = isset($_POST['category']) ? absint($_POST['category']) : 0;
        
        // 验证分类ID
        if ($category_id === 0) {
            wp_send_json_error(__('Invalid category ID', 'b2bpress'));
        }
        
        // 获取分类下的所有产品
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $category_id,
                ),
            ),
        );
        
        $query = new WP_Query($args);
        
        // 如果没有找到产品
        if ($query->post_count === 0) {
            wp_send_json_error(__('No products found in this category', 'b2bpress'));
            return;
        }
        
        // 收集所有属性
        $attributes = array();
        $global_attributes = array(); // 存储全局属性
        $custom_attributes = array(); // 存储自定义属性
        
        try {
            // 遍历所有产品
            foreach ($query->posts as $post) {
                $product = wc_get_product($post->ID);
                if (!$product) {
                    continue;
                }
                
                $product_attributes = $product->get_attributes();
                
                // 遍历产品属性
                foreach ($product_attributes as $attribute_key => $attribute) {
                    // 处理全局属性（分类法属性）
                    if ($attribute->is_taxonomy()) {
                        $taxonomy = $attribute->get_taxonomy_object();
                        if ($taxonomy) {
                            $global_attributes[$attribute_key] = array(
                                'key' => $attribute_key,
                                'label' => $taxonomy->attribute_label,
                                'type' => 'attribute',
                            );
                        }
                    } else {
                        // 处理自定义属性（本地属性）
                        $name = $attribute->get_name();
                        $key = 'custom_' . sanitize_title($name);
                        $custom_attributes[$key] = array(
                            'key' => $key,
                            'label' => $name,
                            'type' => 'attribute',
                        );
                    }
                }
            }
            
            // 合并全局和自定义属性，先显示全局属性，再显示自定义属性
            $attributes = array_merge(array_values($global_attributes), array_values($custom_attributes));
            
            // 如果没有找到任何属性
            if (empty($attributes)) {
                wp_send_json_error(__('No attributes found for products in this category', 'b2bpress'));
                return;
            }
            
            // 恢复原始语言
            $this->restore_original_language();
            
            // 返回属性列表
            wp_send_json_success($attributes);
            
        } catch (Exception $e) {
            // 恢复原始语言
            $this->restore_original_language();
            
            // 处理异常
            wp_send_json_error(__('Error getting attributes: ', 'b2bpress') . $e->getMessage());
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
                error_log('B2BPress表格生成器调试: ' . $message . ' - ' . (is_string($data) ? $data : print_r($data, true)));
            } else {
                error_log('B2BPress表格生成器调试: ' . $message);
            }
        }
    }

    /**
     * 生成表格缓存
     *
     * @param int $post_id 文章ID
     * @param WP_Post $post 文章对象
     * @param bool $update 是否为更新
     */
    public function generate_table_cache($post_id, $post = null, $update = null) {
        $this->log_debug('开始生成表格缓存，表格ID: ' . $post_id);
        
        try {
            // 如果不是表格，直接返回
            if (get_post_type($post_id) !== 'b2bpress_table') {
                $this->log_debug('不是表格类型，退出缓存生成');
                return;
            }
            
            // 如果是自动保存，直接返回
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                $this->log_debug('正在自动保存，退出缓存生成');
                return;
            }
            
            // 如果是修订版本，直接返回
            if (wp_is_post_revision($post_id)) {
                $this->log_debug('是修订版本，退出缓存生成');
                return;
            }
            
            // 清除该表格的所有缓存
            $this->log_debug('清除表格缓存');
            try {
                $this->invalidate_cache($post_id);
            } catch (Exception $e) {
                $this->log_debug('清除缓存失败', $e->getMessage());
                throw new Exception('清除缓存失败: ' . $e->getMessage());
            }
            
            // 获取全局设置
            $global_options = get_option('b2bpress_options', array());
            
            // 预生成表格缓存
            $args = array(
                'id' => $post_id,
                'style' => get_post_meta($post_id, '_b2bpress_table_style', true),
                'per_page' => get_post_meta($post_id, '_b2bpress_table_per_page', true),
                'show_images' => get_post_meta($post_id, '_b2bpress_table_show_images', true),
            );
            
            // 如果表格样式为默认，使用全局设置
            if ($args['style'] === 'default' && isset($global_options['default_table_style'])) {
                $args['style'] = $global_options['default_table_style'];
            }
            
            $this->log_debug('表格参数', $args);
            
            // 生成所有表格数据的缓存
            try {
                $this->log_debug('生成所有表格数据');
                $this->get_all_table_data($post_id);
            } catch (Exception $e) {
                $this->log_debug('生成所有表格数据失败', $e->getMessage());
                throw new Exception('生成所有表格数据失败: ' . $e->getMessage());
            }
            
            // 生成渲染后的表格HTML缓存
            try {
                $this->log_debug('设置表格属性');
                $this->table_id = $post_id;
                $this->settings = $this->get_table_settings($post_id);
                
                if (empty($this->settings)) {
                    $this->log_debug('表格设置为空');
                    throw new Exception('表格设置为空');
                }
                
                $this->columns = $this->get_table_columns($post_id);
                
                if (empty($this->columns)) {
                    $this->log_debug('表格列为空');
                    throw new Exception('表格列为空');
                }
                
                // 合并参数
                $this->settings = wp_parse_args($args, $this->settings);
                
                // 生成缓存键
                $cache_key = 'b2bpress_rendered_table_' . $post_id . '_' . md5(serialize($this->settings));
                $this->log_debug('缓存键: ' . $cache_key);
                
                // 生成表格HTML并缓存
                $this->log_debug('生成表格HTML');
                $content = $this->generate_table_html();
                
                if (empty($content)) {
                    $this->log_debug('生成的HTML内容为空');
                    throw new Exception('生成的HTML内容为空');
                }
                
                $this->log_debug('设置缓存');
                if (!$this->cache->set($cache_key, $content, 86400)) {
                    $this->log_debug('缓存设置失败');
                    throw new Exception('缓存设置失败');
                }
                
                $this->log_debug('表格缓存生成完成');
            } catch (Exception $e) {
                $this->log_debug('生成渲染后的表格HTML缓存失败', $e->getMessage());
                throw new Exception('生成渲染后的表格HTML缓存失败: ' . $e->getMessage());
            }
        } catch (Exception $e) {
            $this->log_debug('表格缓存生成过程中发生异常', $e->getMessage());
            // 在WP_DEBUG模式下，将错误信息写入日志
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('B2BPress表格缓存生成错误: ' . $e->getMessage() . ' [堆栈跟踪: ' . $e->getTraceAsString() . ']');
            }
            // 如果不是在钩子中调用的，则重新抛出异常
            if (func_num_args() == 1) {
                throw $e;
            }
        }
    }
    
    /**
     * 刷新所有表格缓存
     */
    public function refresh_all_table_cache() {
        $this->log_debug('开始刷新所有表格缓存');
        
        try {
            // 分页查询所有表格，避免 -1 大查询
            $paged = 1;
            $per_page = 50;
            $total_found = 0;
            
            $success_count = 0;
            $error_count = 0;
            $errors = array();
            
            do {
                $query = new WP_Query(array(
                    'post_type' => 'b2bpress_table',
                    'post_status' => 'publish',
                    'posts_per_page' => $per_page,
                    'paged' => $paged,
                    'fields' => 'ids',
                ));

                if (!$query->have_posts()) {
                    break;
                }

                $total_found += count($query->posts);
                foreach ($query->posts as $post_id) {
                    try {
                        $this->log_debug('开始处理表格ID: ' . $post_id);
                        $this->generate_table_cache($post_id);
                        $success_count++;
                    } catch (Exception $e) {
                        $this->log_debug('表格ID: ' . $post_id . ' 缓存生成失败', $e->getMessage());
                        $error_count++;
                        $errors[] = '表格ID: ' . $post_id . ' - ' . $e->getMessage();
                    }
                }

                $paged++;
                wp_reset_postdata();
            } while ($query->max_num_pages >= $paged - 1);

            $this->log_debug('分页查询到表格数量: ' . $total_found);
            $this->log_debug('所有表格缓存刷新完成，成功: ' . $success_count . ', 失败: ' . $error_count);

            if ($error_count > 0) {
                throw new Exception('部分表格缓存刷新失败: ' . implode('; ', $errors));
            }
        } catch (Exception $e) {
            $this->log_debug('刷新所有表格缓存过程中发生异常', $e->getMessage());
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('B2BPress刷新所有表格缓存错误: ' . $e->getMessage() . ' [堆栈跟踪: ' . $e->getTraceAsString() . ']');
            }
            throw $e;
        }
    }
    
    /**
     * 使缓存失效
     *
     * @param int $post_id 文章ID
     * @param WP_Post $post 文章对象（可选）
     * @param bool $update 是否为更新（可选）
     */
    public function invalidate_cache($post_id, $post = null, $update = null) {
        $this->log_debug('开始使缓存失效，ID: ' . $post_id);
        
        try {
            $post_type = get_post_type($post_id);
            $this->log_debug('文章类型: ' . $post_type);
            
            // 如果是产品，清除所有表格缓存
            if ($post_type === 'product') {
                $this->log_debug('是产品类型，清除所有表格缓存');
                
                try {
                    $this->cache->delete_group('b2bpress_table');
                } catch (Exception $e) {
                    $this->log_debug('删除表格组缓存失败', $e->getMessage());
                    throw new Exception('删除表格组缓存失败: ' . $e->getMessage());
                }
                
                try {
                    $this->cache->delete_by_prefix('b2bpress_rendered_table_');
                } catch (Exception $e) {
                    $this->log_debug('删除渲染表格缓存前缀失败', $e->getMessage());
                    throw new Exception('删除渲染表格缓存前缀失败: ' . $e->getMessage());
                }
                
                try {
                    $this->cache->delete_by_prefix('b2bpress_table_all_data_');
                } catch (Exception $e) {
                    $this->log_debug('删除表格所有数据缓存前缀失败', $e->getMessage());
                    throw new Exception('删除表格所有数据缓存前缀失败: ' . $e->getMessage());
                }
            }
            
            // 如果是表格，只清除该表格的缓存
            if ($post_type === 'b2bpress_table') {
                $this->log_debug('是表格类型，只清除该表格的缓存');
                
                try {
                    $this->cache->delete_by_prefix('b2bpress_table_' . $post_id);
                } catch (Exception $e) {
                    $this->log_debug('删除表格缓存前缀失败', $e->getMessage());
                    throw new Exception('删除表格缓存前缀失败: ' . $e->getMessage());
                }
                
                try {
                    $this->cache->delete_by_prefix('b2bpress_rendered_table_' . $post_id);
                } catch (Exception $e) {
                    $this->log_debug('删除渲染表格缓存前缀失败', $e->getMessage());
                    throw new Exception('删除渲染表格缓存前缀失败: ' . $e->getMessage());
                }
                
                try {
                    $this->cache->delete_by_prefix('b2bpress_table_all_data_' . $post_id);
                } catch (Exception $e) {
                    $this->log_debug('删除表格所有数据缓存前缀失败', $e->getMessage());
                    throw new Exception('删除表格所有数据缓存前缀失败: ' . $e->getMessage());
                }
            }
            
            $this->log_debug('缓存失效完成');
            if (method_exists($this->cache, 'bump_last_changed')) {
                $this->cache->bump_last_changed();
            }
        } catch (Exception $e) {
            $this->log_debug('使缓存失效过程中发生异常', $e->getMessage());
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('B2BPress使缓存失效错误: ' . $e->getMessage() . ' [堆栈跟踪: ' . $e->getTraceAsString() . ']');
            }
            throw $e;
        }
    }
    
    /**
     * 删除时使缓存失效
     *
     * @param int $post_id 文章ID
     */
    public function invalidate_cache_on_delete($post_id) {
        $this->invalidate_cache($post_id);
    }

    /**
     * 生成表格HTML内容
     *
     * @return string 表格HTML
     */
    private function generate_table_html() {
        // 开始输出缓冲
        ob_start();
        
        // 触发表格前的操作
        do_action('b2bpress_before_table', $this->settings);
        
        // 渲染表格HTML（包含完整数据，不是空的表格框架）
        $this->render_complete_table_html();
        
        // 触发表格后的操作
        do_action('b2bpress_after_table', $this->settings);
        
        // 获取并返回输出缓冲内容
        return ob_get_clean();
    }
    
    /**
     * 渲染完整的表格HTML（包含数据）
     */
    private function render_complete_table_html() {
        // 获取表格样式
        $style = $this->settings['style'];
        if ($style === 'default') {
            $global_options = get_option('b2bpress_options', array());
            $style = isset($global_options['default_table_style']) ? $global_options['default_table_style'] : 'inherit';
        }
        // 若当前样式为 inherit 而全局样式非 inherit，则使用全局样式
        if ($style === 'inherit') {
            $global_options = isset($global_options) ? $global_options : get_option('b2bpress_options', array());
            $global_style = isset($global_options['default_table_style']) ? $global_options['default_table_style'] : 'inherit';
            if ($global_style !== 'inherit') {
                $style = $global_style;
            }
        }
        $style = $this->normalize_style($style);
        
        // 表格容器类
        $table_class = 'b2bpress-table b2bpress-table-' . esc_attr($style) . ' b2bpress-table-prerendered';
        
        // 获取全局设置中的图片显示选项
        $global_options = get_option('b2bpress_options', array());
        $show_images = isset($global_options['show_product_images']) ? (bool)$global_options['show_product_images'] : false;
        
        // 只有在设置中未指定时才使用全局设置
        if (!isset($this->settings['show_images'])) {
            $this->settings['show_images'] = $show_images;
        }
        
        // 获取所有表格数据（分页聚合）
        $table_data = $this->get_all_table_data($this->table_id);
        
        // 输出表格HTML
        ?>
        <div class="b2bpress-table-wrapper">
            <table class="<?php echo esc_attr($table_class); ?>">
                <thead>
                    <tr>
                        <?php $this->render_table_header(); ?>
                    </tr>
                </thead>
                <tbody class="b2bpress-table-body">
                    <?php if (!empty($table_data['products'])): ?>
                        <?php foreach ($table_data['products'] as $product): ?>
                            <tr>
                                <?php if ($this->settings['show_images']): ?>
                                    <td class="b2bpress-column-thumbnail"><?php echo isset($product['thumbnail']) ? wp_kses_post($product['thumbnail']) : ''; ?></td>
                                <?php endif; ?>
                                <td class="b2bpress-column-name">
                                    <a href="<?php echo esc_url($product['permalink']); ?>"><?php echo esc_html($product['name']); ?></a>
                                </td>
                                <?php foreach ($this->columns as $column): ?>
                                    <?php
                                    $cell_value = isset($product[$column['key']]) ? $product[$column['key']] : '';
                                    $cell_html = '<td class="b2bpress-column-' . esc_attr($column['key']) . '">' . $this->sanitize_cell_output($cell_value, $column) . '</td>';
                                    echo apply_filters('b2bpress_table_cell', $cell_html, $cell_value, $column, $product, $this->table_id);
                                    ?>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?php echo count($this->columns) + 1 + ($this->settings['show_images'] ? 1 : 0); ?>" class="b2bpress-no-results">
                                <?php esc_html_e('No products found', 'b2bpress'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * 获取表格所有数据（不分页）
     *
     * @param int $table_id 表格ID
     * @return array 表格数据
     */
    private function get_all_table_data($table_id) {
        // 生成缓存键
        $cache_key = 'b2bpress_table_all_data_' . $table_id;
        
        // 尝试从缓存获取数据
        $data = $this->cache->get($cache_key);
        if ($data !== false) {
            return $data;
        }
        
        // 获取全局设置中的图片显示选项
        $global_options = get_option('b2bpress_options', array());
        $show_images = isset($global_options['show_product_images']) ? (bool)$global_options['show_product_images'] : false;
        
        // 如果有表格ID，获取表格设置和列
        if ($table_id > 0) {
            $settings = $this->get_table_settings($table_id);
            $columns = $this->get_table_columns($table_id);
            $category = $settings['category'];
            // 如果表格设置中明确指定了是否显示图片，则使用表格设置；否则使用全局设置
            $settings['show_images'] = isset($settings['show_images']) ? (bool)$settings['show_images'] : $show_images;
        } else {
            // 如果没有表格ID，返回错误
            return array(
                'error' => __('Invalid table ID', 'b2bpress'),
            );
        }
        
        // 分页聚合，避免 -1 大查询
        $products = array();
        $per_page_query = 100;
        $paged = 1;
        do {
            $args = array(
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => $per_page_query,
                'paged' => $paged,
                'fields' => 'ids',
            );
            if ($category > 0) {
                $args['tax_query'] = array(
                    array(
                        'taxonomy' => 'product_cat',
                        'field' => 'term_id',
                        'terms' => $category,
                    ),
                );
            }

            $query = new WP_Query($args);
            if (!$query->have_posts()) {
                break;
            }

            foreach ($query->posts as $product_id) {
                $product = wc_get_product($product_id);
                if (!$product) { continue; }
                $product_data = array(
                    'id' => $product_id,
                    'name' => $product->get_name(),
                    'permalink' => get_permalink($product_id),
                );
                if ($settings['show_images']) {
                    $product_data['thumbnail'] = $this->get_product_thumbnail($product);
                }
                foreach ($columns as $column) {
                    $product_data[$column['key']] = $this->get_product_attribute($product, $column);
                }
                $products[] = $product_data;
            }

            $paged++;
            wp_reset_postdata();
        } while ($query->max_num_pages >= $paged - 1);

        $data = array(
            'products' => $products,
            'columns' => $columns,
            'show_images' => $settings['show_images'],
        );

        $this->cache->set($cache_key, $data, 86400);

        return $data;
    }

    /**
     * 获取产品属性
     *
     * @param WC_Product $product 产品对象
     * @param array $column 列信息
     * @return string 属性值
     */
    private function get_product_attribute($product, $column) {
        return $this->get_column_value($product, $column);
    }

    /**
     * 规范化样式名称，兼容旧样式并映射到新样式集合
     *
     * @param string $style 原始样式
     * @return string 规范化后的样式
     */
    private function normalize_style($style) {
        $style = (string) $style;
        $map = array(
            'default' => 'default',
            'inherit' => 'inherit',
            'shadcn' => 'shadcn',
            'clean' => 'clean',
            'bordered' => 'bordered',
            'compact' => 'compact',
            // 兼容旧样式映射
            'classic' => 'bordered',
            'modern' => 'shadcn',
            'striped' => 'clean',
            'card' => 'bordered',
        );

        if (!isset($map[$style])) {
            return 'inherit';
        }
        return $map[$style];
    }
} 