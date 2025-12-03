jQuery(document).ready(function($) {
    'use strict';
    
    // Notification function
    function showNotification(message, type) {
        type = type || 'info'; // info, success, error, warning
        
        var iconMap = {
            'success': '✓',
            'error': '✗',
            'warning': '⚠',
            'info': 'ℹ'
        };
        
        var colorMap = {
            'success': '#10b981',
            'error': '#ef4444',
            'warning': '#f59e0b',
            'info': '#3b82f6'
        };
        
        // Remove existing notifications
        $('.rchg-notification').remove();
        
        var $notification = $('<div class="rchg-notification rchg-notification-' + type + '">')
            .html('<span class="rchg-notification-icon">' + iconMap[type] + '</span>' + 
                  '<span class="rchg-notification-message">' + message + '</span>' +
                  '<button class="rchg-notification-close">×</button>')
            .css({
                'position': 'fixed',
                'top': '32px',
                'right': '20px',
                'background': colorMap[type],
                'color': '#ffffff',
                'padding': '16px 20px',
                'border-radius': '8px',
                'box-shadow': '0 4px 12px rgba(0,0,0,0.15)',
                'z-index': '99999',
                'min-width': '300px',
                'max-width': '500px',
                'display': 'flex',
                'align-items': 'center',
                'gap': '12px',
                'animation': 'rchgSlideIn 0.3s ease-out',
                'font-size': '14px',
                'font-weight': '500'
            });
        
        $notification.find('.rchg-notification-icon').css({
            'font-size': '20px',
            'font-weight': 'bold'
        });
        
        $notification.find('.rchg-notification-message').css({
            'flex': '1'
        });
        
        $notification.find('.rchg-notification-close').css({
            'background': 'transparent',
            'border': 'none',
            'color': '#ffffff',
            'font-size': '24px',
            'cursor': 'pointer',
            'padding': '0',
            'width': '24px',
            'height': '24px',
            'line-height': '1',
            'opacity': '0.8'
        }).on('mouseenter', function() {
            $(this).css('opacity', '1');
        }).on('mouseleave', function() {
            $(this).css('opacity', '0.8');
        }).on('click', function() {
            $notification.fadeOut(200, function() {
                $(this).remove();
            });
        });
        
        // Add CSS animation
        if (!$('#rchg-notification-styles').length) {
            $('<style id="rchg-notification-styles">')
                .text('@keyframes rchgSlideIn { from { transform: translateX(400px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }')
                .appendTo('head');
        }
        
        $('body').append($notification);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            $notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Tab switching
    $('.rchg-tab').on('click', function() {
        var tab = $(this).data('tab');
        $('.rchg-tab').removeClass('active');
        $(this).addClass('active');
        $('.rchg-tab-content').removeClass('active');
        $('#tab-' + tab).addClass('active');
    });
    
    // Load role permissions
    $('#rchg-load-role').on('click', function() {
        var role = $('#rchg-role-select').val();
        if (!role) {
            showNotification('Vui lòng chọn vai trò', 'error');
            return;
        }
        
        var $btn = $(this);
        $btn.prop('disabled', true).text('Đang tải...');
        
        $.ajax({
            url: rchgData.ajaxurl,
            type: 'POST',
            data: {
                action: 'rchg_mu_get_permissions',
                nonce: rchgData.nonce,
                type: 'role',
                role_name: role
            },
            success: function(response) {
                if (response.success) {
                    $('#rchg-role-name').text($('#rchg-role-select option:selected').text());
                    $('#rchg-role-permissions').show();
                    
                    // Reset all checkboxes
                    $('#rchg-role-categories input[type="checkbox"]').prop('checked', false);
                    
                    // Set permissions
                    response.data.permissions.forEach(function(perm) {
                        var row = $('#rchg-role-categories tr[data-cat-id="' + perm.category_id + '"]');
                        if (perm.can_view == 1) row.find('.perm-view').prop('checked', true);
                        if (perm.can_edit == 1) row.find('.perm-edit').prop('checked', true);
                        if (perm.can_create == 1) row.find('.perm-create').prop('checked', true);
                    });
                    
                    showNotification('Đã tải phân quyền thành công!', 'success');
                } else {
                    showNotification('Lỗi: ' + (response.data.message || 'Không thể tải phân quyền'), 'error');
                }
            },
            error: function() {
                showNotification('Lỗi kết nối! Vui lòng thử lại.', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).text('Tải Phân Quyền');
            }
        });
    });
    
    // Save role permissions
    $('#rchg-save-role').on('click', function() {
        var role = $('#rchg-role-select').val();
        if (!role) {
            showNotification('Vui lòng chọn vai trò trước', 'error');
            return;
        }
        
        var permissions = [];
        $('#rchg-role-categories tr').each(function() {
            var catId = $(this).data('cat-id');
            var canView = $(this).find('.perm-view').is(':checked');
            var canEdit = $(this).find('.perm-edit').is(':checked');
            var canCreate = $(this).find('.perm-create').is(':checked');
            
            if (canView || canEdit || canCreate) {
                var perm = { category_id: catId };
                if (canView) perm.can_view = 1;
                if (canEdit) perm.can_edit = 1;
                if (canCreate) perm.can_create = 1;
                permissions.push(perm);
            }
        });
        
        var $btn = $(this);
        $btn.prop('disabled', true).html('<span class="rchg-loading"></span> Đang lưu...');
        
        $.ajax({
            url: rchgData.ajaxurl,
            type: 'POST',
            data: {
                action: 'rchg_mu_save_permissions',
                nonce: rchgData.nonce,
                type: 'role',
                role_name: role,
                permissions: permissions
            },
            success: function(response) {
                if (response.success) {
                    showNotification('✓ ' + response.data.message, 'success');
                } else {
                    showNotification('✗ Lỗi: ' + response.data.message, 'error');
                }
            },
            error: function() {
                showNotification('✗ Lỗi kết nối! Vui lòng thử lại.', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).text('Lưu Phân Quyền');
            }
        });
    });
    
    // User search autocomplete
    var searchTimer;
    $('#rchg-user-search').on('keyup', function() {
        clearTimeout(searchTimer);
        var search = $(this).val();
        
        if (search.length < 2) {
            $('#rchg-user-results').hide();
            return;
        }
        
        searchTimer = setTimeout(function() {
            $.ajax({
                url: rchgData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'rchg_mu_search_users',
                    nonce: rchgData.nonce,
                    search: search
                },
                success: function(response) {
                    if (response.success) {
                        var html = '';
                        response.data.users.forEach(function(user) {
                            html += '<div class="user-item" data-user-id="' + user.id + '">';
                            html += '<span class="user-name">' + user.name + '</span>';
                            html += '<span class="user-email">' + user.email + '</span>';
                            html += '</div>';
                        });
                        $('#rchg-user-results').html(html).show();
                    }
                }
            });
        }, 300);
    });
    
    // Select user from search results
    $(document).on('click', '#rchg-user-results .user-item', function() {
        var userId = $(this).data('user-id');
        var userName = $(this).find('.user-name').text();
        
        $('#rchg-selected-user-id').val(userId);
        $('#rchg-user-name').text(userName);
        $('#rchg-user-search').val(userName);
        $('#rchg-user-results').hide();
        
        showNotification('Đang tải phân quyền của ' + userName + '...', 'info');
        
        // Load user permissions
        $.ajax({
            url: rchgData.ajaxurl,
            type: 'POST',
            data: {
                action: 'rchg_mu_get_permissions',
                nonce: rchgData.nonce,
                type: 'user',
                user_id: userId
            },
            success: function(response) {
                if (response.success) {
                    $('#rchg-user-permissions').show();
                    
                    // Reset all checkboxes
                    $('#rchg-user-categories input[type="checkbox"]').prop('checked', false);
                    
                    // Set permissions
                    response.data.permissions.forEach(function(perm) {
                        var row = $('#rchg-user-categories tr[data-cat-id="' + perm.category_id + '"]');
                        if (perm.can_view == 1) row.find('.perm-view').prop('checked', true);
                        if (perm.can_edit == 1) row.find('.perm-edit').prop('checked', true);
                        if (perm.can_create == 1) row.find('.perm-create').prop('checked', true);
                    });
                    
                    showNotification('Đã tải phân quyền thành công!', 'success');
                } else {
                    showNotification('Lỗi: Không thể tải phân quyền', 'error');
                }
            },
            error: function() {
                showNotification('Lỗi kết nối! Vui lòng thử lại.', 'error');
            }
        });
    });
    
    // Save user permissions
    $('#rchg-save-user').on('click', function() {
        var userId = $('#rchg-selected-user-id').val();
        if (!userId) {
            showNotification('Vui lòng chọn user trước', 'error');
            return;
        }
        
        var permissions = [];
        $('#rchg-user-categories tr').each(function() {
            var catId = $(this).data('cat-id');
            var canView = $(this).find('.perm-view').is(':checked');
            var canEdit = $(this).find('.perm-edit').is(':checked');
            var canCreate = $(this).find('.perm-create').is(':checked');
            
            if (canView || canEdit || canCreate) {
                var perm = { category_id: catId };
                if (canView) perm.can_view = 1;
                if (canEdit) perm.can_edit = 1;
                if (canCreate) perm.can_create = 1;
                permissions.push(perm);
            }
        });
        
        var $btn = $(this);
        $btn.prop('disabled', true).html('<span class="rchg-loading"></span> Đang lưu...');
        
        $.ajax({
            url: rchgData.ajaxurl,
            type: 'POST',
            data: {
                action: 'rchg_mu_save_permissions',
                nonce: rchgData.nonce,
                type: 'user',
                user_id: userId,
                permissions: permissions
            },
            success: function(response) {
                if (response.success) {
                    showNotification('✓ ' + response.data.message, 'success');
                } else {
                    showNotification('✗ Lỗi: ' + response.data.message, 'error');
                }
            },
            error: function() {
                showNotification('✗ Lỗi kết nối! Vui lòng thử lại.', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).text('Lưu Phân Quyền');
            }
        });
    });
    
    // Export role permissions
    $('#rchg-export-role').on('click', function() {
        var role = $('#rchg-role-select').val();
        if (!role) {
            showNotification('Vui lòng chọn vai trò trước', 'warning');
            return;
        }
        
        var $btn = $(this);
        $btn.prop('disabled', true).text('Đang export...');
        
        $.ajax({
            url: rchgData.ajaxurl,
            type: 'POST',
            data: {
                action: 'rchg_mu_export_permissions',
                nonce: rchgData.nonce,
                type: 'role'
            },
            success: function(response) {
                if (response.success) {
                    downloadJSON(response.data.data, 'role-permissions-' + role + '.json');
                    showNotification('✓ Đã export phân quyền Role thành công!', 'success');
                } else {
                    showNotification('Lỗi export: ' + (response.data.message || 'Không xác định'), 'error');
                }
            },
            error: function() {
                showNotification('Lỗi kết nối! Không thể export.', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).text('Export JSON');
            }
        });
    });
    
    // Export user permissions
    $('#rchg-export-user').on('click', function() {
        var userId = $('#rchg-selected-user-id').val();
        if (!userId) {
            showNotification('Vui lòng chọn user trước', 'warning');
            return;
        }
        
        var $btn = $(this);
        $btn.prop('disabled', true).text('Đang export...');
        
        $.ajax({
            url: rchgData.ajaxurl,
            type: 'POST',
            data: {
                action: 'rchg_mu_export_permissions',
                nonce: rchgData.nonce,
                type: 'user'
            },
            success: function(response) {
                if (response.success) {
                    downloadJSON(response.data.data, 'user-permissions-' + userId + '.json');
                    showNotification('✓ Đã export phân quyền User thành công!', 'success');
                } else {
                    showNotification('Lỗi export: ' + (response.data.message || 'Không xác định'), 'error');
                }
            },
            error: function() {
                showNotification('Lỗi kết nối! Không thể export.', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).text('Export JSON');
            }
        });
    });
    
    // Export all permissions
    $('#rchg-export-all').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).text('Đang export...');
        
        $.ajax({
            url: rchgData.ajaxurl,
            type: 'POST',
            data: {
                action: 'rchg_mu_export_permissions',
                nonce: rchgData.nonce,
                type: 'all'
            },
            success: function(response) {
                if (response.success) {
                    downloadJSON(response.data.data, 'all-permissions.json');
                    showNotification('✓ Đã export toàn bộ phân quyền thành công!', 'success');
                } else {
                    showNotification('Lỗi export: ' + (response.data.message || 'Không xác định'), 'error');
                }
            },
            error: function() {
                showNotification('Lỗi kết nối! Không thể export.', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).text('Export toàn bộ');
            }
        });
    });
    
    // Import JSON
    $('#rchg-import-json').on('click', function() {
        $('#rchg-import-file').click();
    });
    
    $('#rchg-import-file').on('change', function(e) {
        var file = e.target.files[0];
        if (!file) return;
        
        if (file.type !== 'application/json') {
            showNotification('Vui lòng chọn file JSON!', 'error');
            return;
        }
        
        showNotification('Đang import dữ liệu...', 'info');
        
        var reader = new FileReader();
        reader.onload = function(event) {
            $.ajax({
                url: rchgData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'rchg_mu_import_permissions',
                    nonce: rchgData.nonce,
                    json_data: event.target.result
                },
                success: function(response) {
                    if (response.success) {
                        showNotification('✓ ' + response.data.message, 'success');
                    } else {
                        showNotification('✗ Lỗi: ' + response.data.message, 'error');
                    }
                },
                error: function() {
                    showNotification('✗ Lỗi kết nối! Import thất bại.', 'error');
                }
            });
        };
        reader.onerror = function() {
            showNotification('Lỗi đọc file! Vui lòng thử lại.', 'error');
        };
        reader.readAsText(file);
        
        // Reset input
        $(this).val('');
    });
    
    // Reset user permissions
    $('#rchg-reset-user').on('click', function() {
        var userId = $('#rchg-selected-user-id').val();
        if (!userId) {
            showNotification('Vui lòng chọn user trước', 'warning');
            return;
        }
        
        if (!confirm('Bạn có chắc muốn xóa tất cả phân quyền riêng của user này?\n\nUser sẽ quay về dùng phân quyền theo Role.')) {
            return;
        }
        
        var $btn = $(this);
        $btn.prop('disabled', true).text('Đang reset...');
        
        $.ajax({
            url: rchgData.ajaxurl,
            type: 'POST',
            data: {
                action: 'rchg_mu_reset_user_permissions',
                nonce: rchgData.nonce,
                user_id: userId
            },
            success: function(response) {
                if (response.success) {
                    showNotification('✓ ' + response.data.message, 'success');
                    $('#rchg-user-categories input[type="checkbox"]').prop('checked', false);
                } else {
                    showNotification('✗ Lỗi: ' + response.data.message, 'error');
                }
            },
            error: function() {
                showNotification('✗ Lỗi kết nối! Reset thất bại.', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).text('Reset User');
            }
        });
    });
    
    // Helper: Download JSON
    function downloadJSON(data, filename) {
        var json = JSON.stringify(data, null, 2);
        var blob = new Blob([json], { type: 'application/json' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
    
    // Close user results when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#rchg-user-search, #rchg-user-results').length) {
            $('#rchg-user-results').hide();
        }
    });
});
