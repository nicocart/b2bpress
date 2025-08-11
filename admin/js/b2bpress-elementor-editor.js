/**
 * B2BPress Elementor编辑器脚本
 */
(function($) {
    'use strict';

    /**
     * 初始化
     */
    $(window).on('elementor:init', function() {
        // 获取表格列表
        var tables = b2bpressElementor.tables || [];
        
        // 如果没有表格，添加创建表格按钮
        if (tables.length === 0) {
            elementor.hooks.addFilter('elementor/editor/template-library/template/action-button', function(viewClass, model) {
                if (model.get('widgetType') === 'b2bpress_table') {
                    viewClass.prototype.ui.insertButton.text(b2bpressElementor.i18n.create_table);
                    viewClass.prototype.ui.insertButton.on('click', function() {
                        window.open(b2bpressAdmin.adminUrl + 'admin.php?page=b2bpress-tables&action=new', '_blank');
                    });
                }
                
                return viewClass;
            });
        }
    });
})(jQuery); 