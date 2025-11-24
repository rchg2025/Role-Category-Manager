/**
 * Role Category Manager MU - Admin JavaScript v2.0
 * Hỗ trợ phân quyền theo Role và User
 */

jQuery(document).ready(function($) {
    
    var searchTimeout = null;
    
    // ===== TAB: ROLE =====
    
    // Khi chọn vai trò
    $('#rchg_mu_role_select').on('change', function() {
        var roleName = $(this).val();
        
        if (roleName) {
            $('#rchg_mu_categories_section').slideDown();
            loadPermissions('role', roleName);
        } else {
            $('#rchg_mu_categories_section').slideUp();
            resetForm();
        }
    });
    
    // Submit form role
    $('#rchg_mu_role_form').on('submit', function(e) {
        e.preventDefault();
        savePermissions($(this), $('#rchg_mu_message'));
    });
    
    // ===== TAB: USER =====
    
    // Search user
    $('#rchg_mu_user_search').on('keyup', function() {
        var searchTerm = $(this).val().trim();
        var $results = $('#rchg_mu_user_results');
        
            console.log('[DEBUG] Search term:', searchTerm);
        
        clearTimeout(searchTimeout);
        
        if (searchTerm.length < 2) {
            $results.hide().empty();
            return;
        }
        
        searchTimeout = setTimeout(function() {
            searchUsers(searchTerm);
        }, 300);
    });
    
    // Click user result
    $(document).on('click', '.rchg_mu_user_result_item', function() {
        var userId = $(this).data('user-id');
        var userName = $(this).data('user-name');
        var userEmail = $(this).data('user-email');
        var userRole = $(this).data('user-role');
        
        selectUser(userId, userName, userEmail, userRole);
    });
    
    // Clear selected user
    $('#rchg_mu_clear_user').on('click', function() {
        clearSelectedUser();
    });
    
    // Submit form user
    $('#rchg_mu_user_form').on('submit', function(e) {
        e.preventDefault();
        savePermissions($(this), $('#rchg_mu_user_message'));
    });
    
    // ===== SHARED FUNCTIONS =====
    
    // Select/Deselect all categories
    $(document).on('click', '#rchg_mu_select_all_cats, .rchg_mu_select_all_cats', function() {
        $('.rchg_mu_category_checkbox').prop('checked', true);
    });
    
    $(document).on('click', '#rchg_mu_deselect_all_cats, .rchg_mu_deselect_all_cats', function() {
        $('.rchg_mu_category_checkbox').prop('checked', false);
    });
    
    // Search users function
    function searchUsers(searchTerm) {
        var $results = $('#rchg_mu_user_results');
        
        $.ajax({
            url: rchgMuAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'rchg_mu_search_users',
                nonce: rchgMuAjax.nonce,
                search: searchTerm
            },
            beforeSend: function() {
                $results.html('<div class="rchg_mu_searching">' + rchgMuAjax.searching + '</div>').show();
            },
            success: function(response) {
                    console.log('[DEBUG] Search response:', response);
                
                if (response.success && response.data.users && response.data.users.length > 0) {
                    var html = '';
                    $.each(response.data.users, function(index, user) {
                        html += '<div class="rchg_mu_user_result_item" ' +
                                'data-user-id="' + user.id + '" ' +
                                'data-user-name="' + user.name + '" ' +
                                'data-user-email="' + user.email + '" ' +
                                'data-user-role="' + user.role + '">' +
                                '<strong>' + user.name + '</strong>' +
                                '<span>' + user.email + '</span>' +
                                '<small>' + user.role + '</small>' +
                                '</div>';
                    });
                    $results.html(html).show();
                } else {
                    $results.html('<div class="rchg_mu_no_results">' + rchgMuAjax.no_results + '</div>').show();
                }
            },
            error: function() {
                $results.html('<div class="rchg_mu_no_results">' + rchgMuAjax.error + '</div>').show();
            }
        });
    }
    
    // Select user
    function selectUser(userId, userName, userEmail, userRole) {
            console.log('[DEBUG] Select user:', userId, userName);
        
        $('#rchg_mu_selected_user_id').val(userId);
        $('#rchg_mu_user_name').text(userName);
        $('#rchg_mu_user_email').text(userEmail);
        $('#rchg_mu_user_role').text(userRole);
        
        $('#rchg_mu_user_search').val('');
        $('#rchg_mu_user_results').hide().empty();
        $('#rchg_mu_selected_user').slideDown();
        $('#rchg_mu_user_categories_section').slideDown();
    $('#rchg_mu_btn_reset').prop('disabled', false);
        
            console.log('[DEBUG] Categories section should show now');
        
        loadPermissions('user', null, userId);
    }
    
    // Clear selected user
    function clearSelectedUser() {
        $('#rchg_mu_selected_user_id').val('');
        $('#rchg_mu_selected_user').slideUp();
        $('#rchg_mu_user_categories_section').slideUp();
        $('#rchg_mu_btn_reset').prop('disabled', true);
        resetForm();
    }
    
    // Load permissions
    function loadPermissions(type, roleName, userId) {
        var data = {
            action: 'rchg_mu_get_permissions',
            nonce: rchgMuAjax.nonce,
            permission_type: type
        };
        
        if (type === 'role') {
            data.role_name = roleName;
        } else {
            data.user_id = userId;
        }
        
        $.ajax({
            url: rchgMuAjax.ajaxurl,
            type: 'POST',
            data: data,
            beforeSend: function() {
                resetForm();
            },
            success: function(response) {
                if (response.success && response.data.permissions && response.data.permissions.length > 0) {
                    var hasView = false, hasEdit = false, hasCreate = false;
                    
                    $.each(response.data.permissions, function(index, perm) {
                        $('.rchg_mu_category_checkbox[value="' + perm.category_id + '"]').prop('checked', true);
                        
                        // Set permission checkboxes based on first permission (they should all be the same)
                        if (index === 0) {
                            hasView = parseInt(perm.can_view) === 1;
                            hasEdit = parseInt(perm.can_edit) === 1;
                            hasCreate = parseInt(perm.can_create) === 1;
                        }
                    });
                    
                    $('input[name="can_view"]').prop('checked', hasView);
                    $('input[name="can_edit"]').prop('checked', hasEdit);
                    $('input[name="can_create"]').prop('checked', hasCreate);
                }
            }
        });
    }
    
    // Save permissions
    function savePermissions($form, $messageBox) {
        var $button = $form.find('button[type="submit"]');
        var formData = $form.serialize();
        formData += '&action=rchg_mu_save_permissions&nonce=' + rchgMuAjax.nonce;
        
        $.ajax({
            url: rchgMuAjax.ajaxurl,
            type: 'POST',
            data: formData,
            beforeSend: function() {
                $button.prop('disabled', true).find('.dashicons').removeClass('dashicons-saved').addClass('dashicons-update');
                $messageBox.removeClass('notice-success notice-error').hide();
            },
            success: function(response) {
                if (response.success) {
                    $messageBox.addClass('notice notice-success').html('<p>' + response.data.message + '</p>').slideDown();
                    
                    setTimeout(function() {
                        $messageBox.slideUp();
                    }, 3000);
                } else {
                    $messageBox.addClass('notice notice-error').html('<p>' + (response.data.message || rchgMuAjax.error) + '</p>').slideDown();
                }
            },
            error: function() {
                $messageBox.addClass('notice notice-error').html('<p>' + rchgMuAjax.error + '</p>').slideDown();
            },
            complete: function() {
                $button.prop('disabled', false).find('.dashicons').removeClass('dashicons-update').addClass('dashicons-saved');
            }
        });
    }
    
    // Reset form
    function resetForm() {
        $('.rchg_mu_category_checkbox').prop('checked', false);
        $('input[name="can_view"]').prop('checked', true);
        $('input[name="can_edit"]').prop('checked', false);
        $('input[name="can_create"]').prop('checked', false);
    }
    
    // Hide results when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#rchg_mu_user_search, #rchg_mu_user_results').length) {
            $('#rchg_mu_user_results').hide();
        }
    });
    
    // ===== IMPORT / EXPORT / RESET =====
    function downloadJSON(filename, dataObj) {
        var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(dataObj, null, 2));
        var dl = document.createElement('a');
        dl.setAttribute('href', dataStr);
        dl.setAttribute('download', filename);
        document.body.appendChild(dl);
        dl.click();
        dl.remove();
    }
    
    function exportPermissions(scope) {
        $.post(rchgMuAjax.ajaxurl, {
            action: 'rchg_mu_export_permissions',
            nonce: rchgMuAjax.nonce,
            scope: scope || ''
        }).done(function(resp){
            if (resp.success) {
                downloadJSON('permissions-' + (scope || 'all') + '.json', resp.data.data);
            } else {
                alert(resp.data && resp.data.message ? resp.data.message : rchgMuAjax.error);
            }
        }).fail(function(){ alert(rchgMuAjax.error); });
    }
    
    function importPermissionsFromFile(inputEl) {
        var file = inputEl.files[0];
        if (!file) return;
        var reader = new FileReader();
        reader.onload = function(e){
            var content = e.target.result;
            $.post(rchgMuAjax.ajaxurl, {
                action: 'rchg_mu_import_permissions',
                nonce: rchgMuAjax.nonce,
                json: content
            }).done(function(resp){
                if (resp.success) {
                    alert(rchgMuAjax.import_success);
                } else {
                    alert(resp.data && resp.data.message ? resp.data.message : rchgMuAjax.error);
                }
            }).fail(function(){ alert(rchgMuAjax.error); });
        };
        reader.readAsText(file);
        inputEl.value = '';
    }
    
    // Inline tools (User tab)
    $('#rchg_mu_btn_export').on('click', function(){ exportPermissions('user'); });
    $('#rchg_mu_import_file').on('change', function(){ importPermissionsFromFile(this); });
    $('#rchg_mu_btn_reset').on('click', function(){
        var userId = $('#rchg_mu_selected_user_id').val();
        if (!userId) return;
        $.post(rchgMuAjax.ajaxurl, {
            action: 'rchg_mu_reset_user_permissions',
            nonce: rchgMuAjax.nonce,
            user_id: userId
        }).done(function(resp){
            if (resp.success) {
                alert(rchgMuAjax.reset_success);
                loadPermissions('user', null, parseInt(userId,10));
            } else {
                alert(resp.data && resp.data.message ? resp.data.message : rchgMuAjax.error);
            }
        }).fail(function(){ alert(rchgMuAjax.error); });
    });
    
    // Bottom tools
    $('#rchg_mu_tools_export').on('click', function(){ exportPermissions(''); });
    $('#rchg_mu_tools_import_file').on('change', function(){ importPermissionsFromFile(this); });
});
