<?php
/**
 * B2BPress表格小部件类
 */
class B2BPress_Table_Widget extends \Elementor\Widget_Base {
    /**
     * 获取小部件名称
     *
     * @return string 小部件名称
     */
    public function get_name() {
        return 'b2bpress_table';
    }
    
    /**
     * 获取小部件标题
     *
     * @return string 小部件标题
     */
    public function get_title() {
        return __('B2B产品表格', 'b2bpress');
    }
    
    /**
     * 获取小部件图标
     *
     * @return string 小部件图标
     */
    public function get_icon() {
        return 'eicon-table';
    }
    
    /**
     * 获取小部件分类
     *
     * @return array 小部件分类
     */
    public function get_categories() {
        return ['b2bpress'];
    }
    
    /**
     * 获取小部件关键字
     *
     * @return array 小部件关键字
     */
    public function get_keywords() {
        return ['b2bpress', 'table', 'product', 'b2b', 'woocommerce'];
    }
    
    /**
     * 注册小部件控件
     */
    protected function _register_controls() {
        // 内容部分
        $this->start_controls_section(
            'section_content',
            [
                'label' => __('表格设置', 'b2bpress'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );
        
        // 表格选择控件
        $this->add_control(
            'table_id',
            [
                'label' => __('选择表格', 'b2bpress'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $this->get_tables(),
                'default' => '0',
                'description' => __('选择要显示的表格', 'b2bpress'),
            ]
        );
        
        // 创建表格按钮
        $this->add_control(
            'create_table',
            [
                'type' => \Elementor\Controls_Manager::RAW_HTML,
                'raw' => '<a href="' . admin_url('admin.php?page=b2bpress-tables&action=new') . '" target="_blank" class="elementor-button elementor-button-default">' . __('创建新表格', 'b2bpress') . '</a>',
                'condition' => [
                    'table_id' => '0',
                ],
            ]
        );
        
        // 分类选择控件（用于临时表格）
        $this->add_control(
            'category',
            [
                'label' => __('产品分类', 'b2bpress'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $this->get_product_categories(),
                'default' => '',
                'condition' => [
                    'table_id' => '0',
                ],
                'description' => __('如果未选择表格，可以选择产品分类创建临时表格', 'b2bpress'),
            ]
        );
        
        // 每页显示数量控件
        $this->add_control(
            'per_page',
            [
                'label' => __('每页显示', 'b2bpress'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'min' => 5,
                'max' => 100,
                'step' => 5,
                'default' => 20,
            ]
        );

        // 表格样式（与全局设置一致）
        $this->add_control(
            'style',
            [
                'label' => __('表格样式', 'b2bpress'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'inherit' => __('继承主题', 'b2bpress'),
                    'shadcn' => __('ShadCN/UI 风格', 'b2bpress'),
                    'clean' => __('干净（无边框）', 'b2bpress'),
                    'bordered' => __('描边表格', 'b2bpress'),
                    'compact' => __('紧凑密集', 'b2bpress'),
                ],
                'default' => 'inherit',
            ]
        );

        // 是否显示图片（覆盖全局）
        $this->add_control(
            'show_images',
            [
                'label' => __('显示产品图片', 'b2bpress'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('显示', 'b2bpress'),
                'label_off' => __('隐藏', 'b2bpress'),
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        // 删除重复“风格”控件，避免覆盖样式

        // 是否显示图片
        $this->add_control(
            'show_images',
            [
                'label' => __('显示图片', 'b2bpress'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('是', 'b2bpress'),
                'label_off' => __('否', 'b2bpress'),
                'return_value' => 'yes',
                'default' => '',
                'description' => __('是否在表格中显示产品缩略图', 'b2bpress'),
            ]
        );
        
        $this->end_controls_section();
        
        // 样式部分
        $this->start_controls_section(
            'section_style',
            [
                'label' => __('表格样式', 'b2bpress'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        // 表头样式控件
        $this->add_control(
            'header_background_color',
            [
                'label' => __('表头背景色', 'b2bpress'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .b2bpress-table thead th' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'header_text_color',
            [
                'label' => __('表头文字颜色', 'b2bpress'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .b2bpress-table thead th' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        // 表格行样式控件
        $this->add_control(
            'row_background_color',
            [
                'label' => __('行背景色', 'b2bpress'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .b2bpress-table tbody tr' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'row_text_color',
            [
                'label' => __('行文字颜色', 'b2bpress'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .b2bpress-table tbody tr' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        // 条纹行样式控件
        $this->add_control(
            'striped_background_color',
            [
                'label' => __('条纹行背景色', 'b2bpress'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .b2bpress-table-striped tbody tr:nth-child(odd)' => 'background-color: {{VALUE}}',
                ],
                'condition' => [
                    'style' => 'striped',
                ],
            ]
        );
        
        // 边框样式控件
        $this->add_control(
            'border_color',
            [
                'label' => __('边框颜色', 'b2bpress'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .b2bpress-table' => 'border-color: {{VALUE}}',
                    '{{WRAPPER}} .b2bpress-table th, {{WRAPPER}} .b2bpress-table td' => 'border-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // 分页样式部分
        $this->start_controls_section(
            'section_pagination_style',
            [
                'label' => __('分页样式', 'b2bpress'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        // 分页按钮样式控件
        $this->add_control(
            'pagination_button_color',
            [
                'label' => __('按钮颜色', 'b2bpress'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .b2bpress-table-pagination-links a' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'pagination_button_background_color',
            [
                'label' => __('按钮背景色', 'b2bpress'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .b2bpress-table-pagination-links a' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'pagination_active_button_color',
            [
                'label' => __('当前页按钮颜色', 'b2bpress'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .b2bpress-table-pagination-links .current' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'pagination_active_button_background_color',
            [
                'label' => __('当前页按钮背景色', 'b2bpress'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .b2bpress-table-pagination-links .current' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->end_controls_section();
    }
    
    /**
     * 渲染小部件输出
     */
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // 获取表格ID
        $table_id = $settings['table_id'];
        
        // 如果没有表格ID但有分类，则使用分类
        if ($table_id === '0' && !empty($settings['category'])) {
            $category = $settings['category'];
        } else {
            $category = '';
        }
        
        // 获取表格样式（默认 inherit）
        $style = isset($settings['style']) && !empty($settings['style']) ? $settings['style'] : 'inherit';
        
        // 获取每页显示数量
        $per_page = $settings['per_page'];
        
        // 获取是否显示图片（优先控件，其次全局设置）
        $global_options = get_option('b2bpress_options', array());
        if (isset($settings['show_images']) && $settings['show_images'] === 'yes') {
            $show_images = 'true';
        } else {
            $show_images = isset($global_options['show_product_images']) && $global_options['show_product_images'] ? 'true' : 'false';
        }
        
        // 生成短代码
        $shortcode = '[b2bpress_table';
        
        if ($table_id !== '0') {
            $shortcode .= ' id="' . esc_attr($table_id) . '"';
        }
        
        if (!empty($category)) {
            $shortcode .= ' category="' . esc_attr($category) . '"';
        }
        
        if (!empty($per_page)) {
            $shortcode .= ' per_page="' . esc_attr($per_page) . '"';
        }
        
        if (!empty($style)) {
            $shortcode .= ' style="' . esc_attr($style) . '"';
        }

        $shortcode .= ' show_images="' . esc_attr($show_images) . '"';
        
        $shortcode .= ']';
        
        // 输出短代码
        echo do_shortcode($shortcode);
    }
    
    /**
     * 渲染纯内容输出
     */
    protected function content_template() {
        ?>
        <div class="elementor-b2bpress-table-placeholder">
            <div class="elementor-b2bpress-table-placeholder-title">
                <?php echo __('B2B产品表格', 'b2bpress'); ?>
            </div>
            <div class="elementor-b2bpress-table-placeholder-description">
                <?php echo __('此处将显示产品表格，在前端查看实际效果', 'b2bpress'); ?>
            </div>
        </div>
        <?php
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
        $tables = array(
            '0' => __('选择表格或使用分类', 'b2bpress'),
        );
        
        foreach ($query->posts as $post) {
            $tables[$post->ID] = $post->post_title;
        }
        
        return $tables;
    }
    
    /**
     * 获取产品分类列表
     *
     * @return array 产品分类列表
     */
    private function get_product_categories() {
        // 获取产品分类
        $terms = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
        ));
        
        // 准备数据
        $categories = array(
            '' => __('选择分类', 'b2bpress'),
        );
        
        if (!is_wp_error($terms) && !empty($terms)) {
            foreach ($terms as $term) {
                $categories[$term->slug] = $term->name;
            }
        }
        
        return $categories;
    }
} 