/**
 * B2BPress Elementor前端脚本
 */
(function($) {
    'use strict';

    /**
     * 初始化
     */
    $(document).ready(function() {
        // 初始化Elementor中的表格
        if (typeof elementorFrontend !== 'undefined') {
            elementorFrontend.hooks.addAction('frontend/element_ready/b2bpress_table.default', function($element) {
                // 初始化表格
                $element.find('.b2bpress-table-container').each(function() {
                    if (typeof B2BPressTable === 'function') {
                        new B2BPressTable($(this));
                    }
                });
            });
        }
    });
})(jQuery); 