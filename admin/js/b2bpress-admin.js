/**
 * B2BPress 后台脚本
 */
(function($) {
    'use strict';

    /**
     * 表格管理页面
     */
    function initTablesList() {
        // 删除表格
        $('.b2bpress-delete-table').on('click', function(e) {
            e.preventDefault();
            
            var tableId = $(this).data('id');
            
            if (confirm(b2bpressAdmin.i18n.confirm_delete)) {
                $.ajax({
                    url: b2bpressAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'b2bpress_delete_table',
                        nonce: b2bpressAdmin.nonce,
                        table_id: tableId
                    },
                    success: function(response) {
                        if (response.success) {
                            // 刷新页面
                            location.reload();
                        } else {
                            alert(response.data || '删除表格时发生错误');
                        }
                    },
                    error: function() {
                        alert('删除表格时发生错误');
                    }
                });
            }
        });
        
        // 复制短代码
        $('.b2bpress-copy-shortcode').on('click', function() {
            var $button = $(this);
            var shortcode = $button.data('shortcode');
            
            var $temp = $('<input>');
            $('body').append($temp);
            $temp.val(shortcode).select();
            document.execCommand('copy');
            $temp.remove();
            
            var originalText = $button.text();
            $button.text('已复制');
            
            setTimeout(function() {
                $button.text(originalText);
            }, 2000);
        });
    }
    
    /**
     * 表格编辑页面
     */
    function initTableEdit() {
        // 已在表格编辑页面模板中实现
    }
    
    /**
     * 表格新建页面
     */
    function initTableNew() {
        // 已在表格新建页面模板中实现
    }
    
    /**
     * 设置页面
     */
    function initSettings() {
        // 暂无特殊脚本
    }
    
    /**
     * 向导页面
     */
    function initWizard() {
        // 已在向导页面模板中实现
    }
    
    /**
     * 初始化
     */
    $(document).ready(function() {
        // 获取当前页面
        var currentPage = window.location.href;
        
        // 根据当前页面初始化相应功能
        if (currentPage.indexOf('page=b2bpress-tables') !== -1) {
            if (currentPage.indexOf('action=edit') !== -1) {
                initTableEdit();
            } else if (currentPage.indexOf('action=new') !== -1) {
                initTableNew();
            } else {
                initTablesList();
            }
        } else if (currentPage.indexOf('page=b2bpress-settings') !== -1) {
            initSettings();
        } else if (currentPage.indexOf('page=b2bpress-wizard') !== -1) {
            initWizard();
        }
        
        // 刷新表格缓存
        $('.b2bpress-refresh-cache').on('click', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var tableId = $button.data('id');
            var originalText = $button.text();
            
            $button.prop('disabled', true);
            $button.text('刷新中...');
            
            // 确保使用管理员nonce
            var nonce = b2bpressAdmin.nonce;
            
            console.log('刷新缓存请求:', {
                tableId: tableId,
                nonce: nonce.substr(0, 5) + '...' // 只显示nonce的前5个字符，出于安全考虑
            });
            
            $.ajax({
                url: b2bpressAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'b2bpress_refresh_cache',
                    nonce: nonce,
                    table_id: tableId
                },
                success: function(response) {
                    console.log('刷新缓存响应:', response);
                    
                    if (response.success) {
                        $button.text('已刷新');
                        
                        setTimeout(function() {
                            $button.prop('disabled', false);
                            $button.text(originalText);
                        }, 2000);
                    } else {
                        $button.prop('disabled', false);
                        $button.text(originalText);
                        alert(response.data || '刷新缓存时发生错误');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX错误:', status, error);
                    console.error('响应文本:', xhr.responseText);
                    
                    $button.prop('disabled', false);
                    $button.text(originalText);
                    alert('刷新缓存时发生错误: ' + error);
                }
            });
        });
    });
})(jQuery); 