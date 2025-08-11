/**
 * B2BPress 前端脚本
 */
(function($) {
    'use strict';

    /**
     * 表格类
     *
     * @param {Object} $container 表格容器
     */
    function B2BPressTable($container) {
        this.$container = $container;
        this.$table = $container.find('table');
        this.$tbody = this.$table.find('tbody');
        this.$searchInput = $container.find('.b2bpress-search-input');
        this.$paginationInfo = $container.find('.b2bpress-table-pagination-info');
        this.$paginationLinks = $container.find('.b2bpress-table-pagination-links');
        this.$tableWrapper = $container.find('.b2bpress-table-wrapper');
        
        this.tableId = $container.data('id');
        this.perPage = this.$table.data('per-page');
        this.currentPage = 1;
        this.searchQuery = '';
        this.category = $container.data('category') || 0;
        
        // 获取站点语言
        this.siteLanguage = $container.data('language') || b2bpressPublic.locale || b2bpressPublic.default_locale;
        
        // 检查表格是否已预渲染
        this.isPrerendered = this.$table.hasClass('b2bpress-table-prerendered');
        
        this.init();
    }
    
    /**
     * 初始化
     */
    B2BPressTable.prototype.init = function() {
        // 如果表格不是预渲染的，则需要通过AJAX加载数据
        if (!this.isPrerendered) {
            // 初始隐藏缩略图列，直到我们从服务器获取数据确认是否应该显示
            this.$table.find('.b2bpress-column-thumbnail').hide();
            
            // 加载表格数据
            this.loadTableData();
            
            // 绑定搜索事件
            var self = this;
            this.$searchInput.on('keyup', function() {
                self.searchQuery = $(this).val();
                self.currentPage = 1;
                self.loadTableData();
            });
        } else {
            // 对于预渲染表格，仅绑定搜索事件用于客户端过滤
            var self = this;
            this.$searchInput.on('keyup', function() {
                var searchTerm = $(this).val().toLowerCase();
                self.filterTableRows(searchTerm);
            });
            
            // 绑定刷新缓存按钮
            this.$container.find('.b2bpress-refresh-cache').on('click', function() {
                var tableId = $(this).data('table-id');
                self.refreshTableCache(tableId);
            });
        }
        
        // 绑定窗口大小改变事件
        $(window).on('resize', function() {
            self.adjustTableColumns();
        });
        
        // 初始调整列宽
        setTimeout(function() {
            self.adjustTableColumns();
        }, 100);
    };
    
    /**
     * 调整表格列宽
     */
    B2BPressTable.prototype.adjustTableColumns = function() {
        var self = this;
        // 确保表头和表体的列宽一致
        var $headers = this.$table.find('th');
        var $firstRow = this.$table.find('tbody tr:first-child td');
        
        if ($headers.length > 0 && $firstRow.length > 0) {
            // 设置表格布局为自动，以便获取自然宽度
            this.$table.css('table-layout', 'auto');
            
            // 计算并设置每列的宽度
            $headers.each(function(index) {
                var headerWidth = $(this).outerWidth();
                if ($firstRow.eq(index).length) {
                    var cellWidth = $firstRow.eq(index).outerWidth();
                    var maxWidth = Math.max(headerWidth, cellWidth);
                    $(this).css('width', maxWidth + 'px');
                    if ($firstRow.eq(index).length) {
                        $firstRow.eq(index).css('width', maxWidth + 'px');
                    }
                }
            });
            
            // 恢复表格布局
            setTimeout(function() {
                self.$table.css('table-layout', 'fixed');
            }, 100);
        }
    };
    
    /**
     * 客户端过滤表格行
     * 
     * @param {string} searchTerm 搜索词
     */
    B2BPressTable.prototype.filterTableRows = function(searchTerm) {
        if (!searchTerm) {
            // 如果搜索词为空，显示所有行
            this.$tbody.find('tr').show();
            return;
        }
        
        var $rows = this.$tbody.find('tr');
        var visibleCount = 0;
        
        $rows.each(function() {
            var rowText = $(this).text().toLowerCase();
            if (rowText.indexOf(searchTerm) > -1) {
                $(this).show();
                visibleCount++;
            } else {
                $(this).hide();
            }
        });
        
        // 如果没有匹配的行，显示"无结果"消息
        if (visibleCount === 0) {
            if (this.$tbody.find('.b2bpress-no-search-results').length === 0) {
                var colSpan = this.$table.find('th').length;
                this.$tbody.append('<tr class="b2bpress-no-search-results"><td colspan="' + colSpan + '">' + b2bpressPublic.i18n.no_results + '</td></tr>');
            }
            this.$tbody.find('.b2bpress-no-search-results').show();
        } else {
            this.$tbody.find('.b2bpress-no-search-results').hide();
        }
    };
    
    /**
     * 刷新表格缓存
     * 
     * @param {int} tableId 表格ID
     */
    B2BPressTable.prototype.refreshTableCache = function(tableId) {
        var self = this;
        
        // 显示加载状态
        var $button = this.$container.find('.b2bpress-refresh-cache');
        var originalText = $button.text();
        $button.text(b2bpressPublic.i18n.loading).prop('disabled', true);
        
        // 获取正确的nonce
        var nonce = b2bpressPublic.nonce;
        // 如果存在管理员nonce，则优先使用
        if (typeof b2bpressAdmin !== 'undefined' && b2bpressAdmin.nonce) {
            nonce = b2bpressAdmin.nonce;
        }
        
        // 发送AJAX请求
        $.ajax({
            url: b2bpressPublic.ajaxUrl,
            type: 'POST',
            data: {
                action: 'b2bpress_refresh_cache',
                nonce: nonce,
                table_id: tableId
            },
            success: function(response) {
                if (response.success) {
                    // 刷新页面以显示新缓存
                    location.reload();
                } else {
                    alert(response.data || b2bpressPublic.i18n.error);
                    $button.text(originalText).prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX错误:', status, error);
                alert(b2bpressPublic.i18n.error + ': ' + error);
                $button.text(originalText).prop('disabled', false);
            }
        });
    };
    
    /**
     * 加载表格数据
     */
    B2BPressTable.prototype.loadTableData = function() {
        // 如果表格是预渲染的，则不需要加载数据
        if (this.isPrerendered) {
            return;
        }
        
        var self = this;
        
        // 显示加载状态
        this.$tbody.html('<tr><td colspan="' + (this.$table.find('th').length) + '" class="b2bpress-loading">' + b2bpressPublic.i18n.loading + '</td></tr>');
        
        // 发送AJAX请求
        $.ajax({
            url: b2bpressPublic.ajaxUrl,
            type: 'POST',
            data: {
                action: 'b2bpress_get_table_data',
                nonce: b2bpressPublic.nonce,
                table_id: this.tableId,
                page: this.currentPage,
                per_page: this.perPage,
                search: this.searchQuery,
                category: this.category,
                language: this.siteLanguage
            },
            success: function(response) {
                if (response.success) {
                    self.renderTableData(response.data);
                } else {
                    self.$tbody.html('<tr><td colspan="' + (self.$table.find('th').length) + '" class="b2bpress-error">' + (response.data || b2bpressPublic.i18n.error) + '</td></tr>');
                }
            },
            error: function() {
                self.$tbody.html('<tr><td colspan="' + (self.$table.find('th').length) + '" class="b2bpress-error">' + b2bpressPublic.i18n.error + '</td></tr>');
            }
        });
    };
    
    /**
     * 渲染表格数据
     *
     * @param {Object} data 表格数据
     */
    B2BPressTable.prototype.renderTableData = function(data) {
        // 检查数据
        if (!data.products || data.products.length === 0) {
            this.$tbody.html('<tr><td colspan="' + (this.$table.find('th').length) + '" class="b2bpress-loading">' + b2bpressPublic.i18n.no_results + '</td></tr>');
            this.$paginationInfo.html('');
            this.$paginationLinks.html('');
            return;
        }
        
        // 渲染表格行
        var html = '';
        
        for (var i = 0; i < data.products.length; i++) {
            var product = data.products[i];
            
            html += '<tr>';
            // 只有在设置为显示图片时才渲染图片列
            if (data.show_images === true) {
                html += '<td class="b2bpress-column-thumbnail">' + product.thumbnail + '</td>';
            }
            html += '<td class="b2bpress-column-name"><a href="' + product.permalink + '">' + product.name + '</a></td>';
            
            // 渲染其他列
            for (var j = 0; j < data.columns.length; j++) {
                var column = data.columns[j];
                html += '<td class="b2bpress-column-' + column.key + '">' + (product[column.key] || '') + '</td>';
            }
            
            html += '</tr>';
        }
        
        this.$tbody.html(html);
        
        // 如果不显示图片，隐藏缩略图列
        if (data.show_images !== true) {
            this.$table.find('.b2bpress-column-thumbnail').hide();
        } else {
            this.$table.find('.b2bpress-column-thumbnail').show();
        }
        
        // 渲染分页信息
        var pagination = data.pagination;
        var totalItems = pagination.total_items;
        var start = (pagination.current_page - 1) * pagination.per_page + 1;
        var end = Math.min(pagination.current_page * pagination.per_page, totalItems);
        
        this.$paginationInfo.html(b2bpressPublic.i18n.showing + ' ' + start + ' ' + b2bpressPublic.i18n.to + ' ' + end + ' ' + b2bpressPublic.i18n.of + ' ' + totalItems + ' ' + b2bpressPublic.i18n.items);
        
        // 渲染分页链接
        this.renderPagination(pagination);
        
        // 调整表格列宽
        var self = this;
        setTimeout(function() {
            self.adjustTableColumns();
        }, 100);
    };
    
    /**
     * 渲染分页
     *
     * @param {Object} pagination 分页数据
     */
    B2BPressTable.prototype.renderPagination = function(pagination) {
        if (pagination.total_pages <= 1) {
            this.$paginationLinks.html('');
            return;
        }
        
        var html = '';
        var currentPage = pagination.current_page;
        var totalPages = pagination.total_pages;
        
        // 上一页
        if (currentPage > 1) {
            html += '<a href="#" class="b2bpress-pagination-prev" data-page="' + (currentPage - 1) + '">' + b2bpressPublic.i18n.prev_page + '</a>';
        }
        
        // 页码
        var startPage = Math.max(1, currentPage - 2);
        var endPage = Math.min(totalPages, currentPage + 2);
        
        if (startPage > 1) {
            html += '<a href="#" data-page="1">1</a>';
            if (startPage > 2) {
                html += '<span>...</span>';
            }
        }
        
        for (var i = startPage; i <= endPage; i++) {
            if (i === currentPage) {
                html += '<span class="current">' + i + '</span>';
            } else {
                html += '<a href="#" data-page="' + i + '">' + i + '</a>';
            }
        }
        
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                html += '<span>...</span>';
            }
            html += '<a href="#" data-page="' + totalPages + '">' + totalPages + '</a>';
        }
        
        // 下一页
        if (currentPage < totalPages) {
            html += '<a href="#" class="b2bpress-pagination-next" data-page="' + (currentPage + 1) + '">' + b2bpressPublic.i18n.next_page + '</a>';
        }
        
        this.$paginationLinks.html(html);
        
        // 绑定分页事件
        var self = this;
        this.$paginationLinks.find('a').on('click', function(e) {
            e.preventDefault();
            self.currentPage = $(this).data('page');
            self.loadTableData();
            
            // 滚动到表格顶部
            $('html, body').animate({
                scrollTop: self.$container.offset().top - 50
            }, 500);
        });
    };
    
    /**
     * 初始化
     */
    $(document).ready(function() {
        // 初始化所有表格
        $('.b2bpress-table-container').each(function() {
            new B2BPressTable($(this));
        });
    });
})(jQuery); 