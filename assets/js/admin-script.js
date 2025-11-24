/**
 * Role Category Manager - Admin JavaScript
 */

(function($) {
    'use strict';
    
    let currentRole = null;
    let originalPermissions = [];
    
    $(document).ready(function() {
        initRoleButtons();
        initSaveButton();
        initSelectAll();
        initCategoryCheckboxes();
    });
    
    /**
     * Khởi tạo các nút role
     */
    function initRoleButtons() {
        $('.rcm-role-button:not(:disabled)').on('click', function() {
            const role = $(this).data('role');
            selectRole(role, $(this));
        });
    }
    
    /**
     * Chọn một role
     */
    function selectRole(role, $button) {
        currentRole = role;
        
        // Cập nhật UI
        $('.rcm-role-button').removeClass('active');
        $button.addClass('active');
        
        // Hiển thị loading
        showLoading();
        
        // Ẩn select prompt
        $('#rcm-select-role').hide();
        
        // Hiển thị categories container
        $('#rcm-categories-container').show();
        
        // Cập nhật tên role đã chọn
        $('#rcm-selected-role').text($button.find('.rcm-role-name').text());
        
        // Load permissions cho role
        loadRolePermissions(role);
    }
    
    /**
     * Load permissions từ server
     */
    function loadRolePermissions(role) {
        $.ajax({
            url: rcmAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'rcm_get_permissions',
                nonce: rcmAjax.nonce,
                role: role
            },
            success: function(response) {
                hideLoading();
                
                if (response.success) {
                    const categories = response.data.categories || [];
                    originalPermissions = [...categories];
                    updateCategoryCheckboxes(categories);
                    $('#rcm-save-btn').prop('disabled', false);
                } else {
                    showMessage(response.data.message || rcmAjax.strings.error, 'error');
                }
            },
            error: function() {
                hideLoading();
                showMessage(rcmAjax.strings.error, 'error');
            }
        });
    }
    
    /**
     * Cập nhật checkboxes theo permissions
     */
    function updateCategoryCheckboxes(categories) {
        // Uncheck tất cả
        $('.rcm-category-checkbox').prop('checked', false);
        
        // Check các categories được phép
        categories.forEach(function(catId) {
            $('.rcm-category-checkbox[value="' + catId + '"]').prop('checked', true);
        });
        
        // Cập nhật select all
        updateSelectAllCheckbox();
    }
    
    /**
     * Khởi tạo nút save
     */
    function initSaveButton() {
        $('#rcm-save-btn').on('click', function() {
            if (!currentRole) {
                return;
            }
            
            const $button = $(this);
            const originalText = $button.html();
            
            // Disable button và hiển thị loading
            $button.prop('disabled', true).html(
                '<span class="spinner is-active" style="float: none; margin: 0 8px 0 0;"></span>' + 
                rcmAjax.strings.loading
            );
            
            // Lấy danh sách categories đã chọn
            const selectedCategories = [];
            $('.rcm-category-checkbox:checked').each(function() {
                selectedCategories.push($(this).val());
            });
            
            // Lưu vào server
            $.ajax({
                url: rcmAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'rcm_save_permissions',
                    nonce: rcmAjax.nonce,
                    role: currentRole,
                    categories: selectedCategories
                },
                success: function(response) {
                    if (response.success) {
                        originalPermissions = [...selectedCategories];
                        showMessage(rcmAjax.strings.saved, 'success');
                    } else {
                        showMessage(response.data.message || rcmAjax.strings.error, 'error');
                    }
                },
                error: function() {
                    showMessage(rcmAjax.strings.error, 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).html(originalText);
                }
            });
        });
    }
    
    /**
     * Khởi tạo select all checkbox
     */
    function initSelectAll() {
        $('#rcm-select-all-categories').on('change', function() {
            const isChecked = $(this).prop('checked');
            $('.rcm-category-checkbox').prop('checked', isChecked);
        });
    }
    
    /**
     * Khởi tạo category checkboxes
     */
    function initCategoryCheckboxes() {
        $('.rcm-category-checkbox').on('change', function() {
            updateSelectAllCheckbox();
        });
    }
    
    /**
     * Cập nhật trạng thái của select all checkbox
     */
    function updateSelectAllCheckbox() {
        const totalCheckboxes = $('.rcm-category-checkbox').length;
        const checkedCheckboxes = $('.rcm-category-checkbox:checked').length;
        
        $('#rcm-select-all-categories').prop('checked', totalCheckboxes > 0 && totalCheckboxes === checkedCheckboxes);
    }
    
    /**
     * Hiển thị loading
     */
    function showLoading() {
        $('#rcm-loading').addClass('active');
        $('#rcm-categories-container').hide();
    }
    
    /**
     * Ẩn loading
     */
    function hideLoading() {
        $('#rcm-loading').removeClass('active');
        $('#rcm-categories-container').show();
    }
    
    /**
     * Hiển thị thông báo
     */
    function showMessage(message, type) {
        const $messageDiv = $('#rcm-message');
        
        $messageDiv
            .removeClass('success error')
            .addClass(type)
            .html(message)
            .show();
        
        // Tự động ẩn sau 5 giây
        setTimeout(function() {
            $messageDiv.fadeOut();
        }, 5000);
        
        // Scroll to message
        $('html, body').animate({
            scrollTop: $messageDiv.offset().top - 100
        }, 300);
    }
    
    /**
     * Kiểm tra xem có thay đổi chưa
     */
    function hasChanges() {
        const currentCategories = [];
        $('.rcm-category-checkbox:checked').each(function() {
            currentCategories.push(parseInt($(this).val()));
        });
        
        if (currentCategories.length !== originalPermissions.length) {
            return true;
        }
        
        for (let i = 0; i < currentCategories.length; i++) {
            if (!originalPermissions.includes(currentCategories[i])) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Cảnh báo khi rời trang có thay đổi chưa lưu
     */
    $(window).on('beforeunload', function() {
        if (currentRole && hasChanges()) {
            return 'Bạn có thay đổi chưa được lưu. Bạn có chắc muốn rời khỏi trang?';
        }
    });
    
})(jQuery);
