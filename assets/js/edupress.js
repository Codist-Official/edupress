if(typeof $j === 'undefined'){
    var $j = jQuery;
}
if(typeof preventDefault === 'undefined'){
    function preventDefault(e) {
        e.preventDefault();
        e.stopPropagation();
    }
}
if(typeof clog === 'undefined'){
    function clog(x){
        console.log(x);
    }
}

if ( typeof showEduPressLoading === 'undefined' ){
    function showEduPressLoading(){
        // $j("body").append("<div class='edupress-loading' id='edupress-loading'><div class='lds-ripple'> <div></div> <div></div> </div></div>");
        // $j('body').append("<div class='edupress-loading' id='edupress-loading'><div class='loader'></div></div>");
        $j('body').append("<div class='edupress-loading' id='edupress-loading'><div class='lds-hourglass'></div></div>");
    }
}

if ( typeof hideEduPressLoading === 'undefined' ){
    function hideEduPressLoading(){
        $j(".edupress-loading").remove();
    }
}

if ( typeof showEduPressStatus === 'undefined' ){
    function showEduPressStatus( status = 'success', timeout = 2000 ){
        let statusImg =  "<img alt='"+status+"' src='"+edupress.img_dir_url + status + '.png'+"' class='status-img "+status+"'>";
        let html = "<div class='edupress-status-wrap' id='edupress-status-wrap'><div class='edupress-status'>"+statusImg+"</div></div>";
        $j("body").append(html);
        if( timeout > 0 ){
            setTimeout( hideEduPressStatus, timeout );
        }
    }
}
if ( typeof  hideEduPressStatus === 'undefined' ){
    function hideEduPressStatus(){
        jQuery('.edupress-status-wrap').remove();
    }
}

if ( typeof showEduPressPopup === 'undefined' ){
    function showEduPressPopup( content = '', title = '' ){
        var html = `<div class="edupress-popup-overlay">
                                <div class='edupress-popup-wrap' id='edupress-popup-wrap'>
                                    <a href='javascript:void(0)' class='close-popup' id='edupress-popup-close'>x</a>
                                    <div class='edupress-popup'>
                                        <div class="title">${title}</div>
                                        <div class='content'>${content}</div>
                                    </div>
                                </div>
                            </div>`;
        $j("body").append(html);
    }
}

if ( typeof hideEdupressPopup === 'undefined' ){
    function hideEduPressPopup(){
        $j(".edupress-popup-overlay").remove();
    }
}

jQuery(document).ready(function(){

    // table sorter
    $j('.tablesorter').tablesorter();

    // popup close
    $j(document).on('click', '.close-popup', function (){
        hideEduPressPopup();
    })

    // EduPress ajax form submission
    $j(document).on('submit','.edupress-ajax', function(e){
        preventDefault(e);

        let data = $j(this).serialize();
        let isEditPost = $j(this).hasClass('edupress-edit-post-form');
        let ele = $j(this);

        let beforeSendCallback = $j(this).find(":input[name='before_send_callback']").val();
        let successCallback = $j(this).find(":input[name='success_callback']").val();
        let errorCallback = $j(this).find(":input[name='error_callback']").val();

        // check if ajax_action equals to saveEduPressAdminSettingsForm
        let refreshPage = $j(this).find(":input[name='ajax_action']").val() === 'saveEduPressAdminSettingsForm';

        $j.ajax({
            url: edupress.ajax_url,
            data: data,
            dataType: 'json',
            method: 'POST',
            beforeSend: function (){
                ele.closest("input[type=submit],button[type=submit]").prop('disabled', true);
                if ( typeof window[beforeSendCallback] !== 'undefined' ){
                    let r = window[beforeSendCallback]( { data:data }, ele  );
                    if ( !r ) return false;
                }
                showEduPressLoading();
            },
            success: function ( res, xhr, ele ){
                hideEduPressLoading();
                clog(res);
                if( res.status > 0 ){
                    if ( typeof window[successCallback] === 'undefined' ){
                        hideEduPressPopup();
                        showEduPressStatus( 'success' );
                        if( refreshPage ){
                            window.location.reload();
                        }
                        if ( isEditPost ){
                            // Updating corresponding row
                            $j("tr[data-id='"+res.payload.post_id+"']").addClass('updated');
                            $j("tr[data-id='"+res.payload.post_id+"'] td").each( function(){
                                let k = $j(this).data('key');
                                if( typeof k !== 'undefined' && typeof  res.payload[k] !== 'undefined' ){
                                    $j(this).text(res.payload[k]);
                                }
                            })
                        }
                    } else {
                        window[successCallback]( res, xhr );
                    }
                } else {
                    if ( typeof window[errorCallback] === 'undefined' ){
                        showEduPressStatus('error');
                        hideEduPressPopup();
                    } else {
                        window[errorCallback]( res, xhr, ele );
                    }
                }
            },
            error: function (){

            }
        })

    })

    // clicking ajax link
    $j(document).on('click', '.edupress-ajax-link', function (e){
        preventDefault(e);

        let beforeSendCallback = $j(this).data('before_send_callback');
        let successCallback = $j(this).data('success_callback');
        let errorCallback = $j(this).data('error_callback');

        clog(`BeforeSend: ${beforeSendCallback} Success: ${successCallback} Error: ${errorCallback}`);

        let ele = $j(this);

        let data = `action=edupress_admin_ajax&_wpnonce=${edupress.wpnonce}`;

        let dataAttr = $j(this).data();
        $j.each( dataAttr, function( k, v ){
            data += `&${k}=${v}`;
        })

        $j.ajax({
            url: edupress.ajax_url,
            data: data,
            dataType: 'JSON',
            method: 'POST',
            beforeSend: function (){
                clog(data);
                if( typeof window[beforeSendCallback] !== 'undefined' && typeof window[beforeSendCallback] === 'function' ){
                    let r = window[beforeSendCallback]( { data: data }, ele );
                    if( !r ) return false;
                } else {
                    showEduPressLoading();
                }
            },
            success: function (res, xhr ){
                clog(res);
                if( typeof window[successCallback] !== 'undefined' && typeof window[successCallback] === 'function' ){
                    window[successCallback](res, xhr, ele);
                } else {
                    hideEduPressLoading();
                    if( res.status === 1 ){
                        showEduPressStatus( 'success' );
                    }
                }
            },
            error: function (){
                if ( typeof window[errorCallback] !== 'undefined' && typeof window[errorCallback] === 'function' ){
                    window[errorCallback]();
                } else {
                    hideEduPressLoading();
                    showEduPressStatus('error');
                }
            }
        })


    })

    // clicking ajax form
    $j(document).on('submit', '.edupress-ajax-form', function (e){
        preventDefault(e);

        let beforeSendCallback = $j(this).find(":input[name='before_send_callback']").val();
        let successCallback = $j(this).find(":input[name='success_callback']").val();
        let errorCallback = $j(this).find(":input[name='error_callback']").val();

        clog(`BeforeSend: ${beforeSendCallback} Success: ${successCallback} Error: ${errorCallback}`);

        let ele = $j(this);
        let data = $j(this).serialize();
        let dataAttr = $j(this).data();
        $j.each( dataAttr, function( k, v ){
            data += `&${k}=${v}`;
        })

        $j.ajax({
            url: edupress.ajax_url,
            data: data,
            dataType: 'JSON',
            method: 'POST',
            beforeSend: function (){
                clog(data);
                if( typeof window[beforeSendCallback] !== 'undefined' ){
                    let r = window[beforeSendCallback]( { data: data}, ele );
                    if( !r ) return false;
                } else {
                    showEduPressLoading();
                }
            },
            success: function (res, xhr ){
                clog(res);
                if( typeof window[successCallback] !== 'undefined' ){
                    window[successCallback](res, xhr, ele);
                } else {
                    hideEduPressLoading();
                    if( res.status === 1 ){
                        hideEduPressPopup();
                        showEduPressStatus( 'success' );
                    }
                }
            },
            error: function (){
                if ( typeof window[errorCallback] !== 'undefined' && typeof window[errorCallback] === 'function' ){
                    window[errorCallback]();
                } else {
                    hideEduPressLoading();
                    showEduPressStatus('error');
                }
            },
            fail: function( xhr, status, err ){
                if (xhr.status === 500) {
                    console.error('Server error 500:', jqXHR.responseText);
                    alert('Internal Server Error (500). Please try again later.');
                } else {
                    console.error('Error:', textStatus, errorThrown);
                    alert('An error occurred: ' + textStatus);
                }
            }
        })
    })

    function updateShiftOptions(branchId){

        branchId = parseInt(branchId);

        // Checking if shift is active or not
        if( edupress.shift_active == 1 ) {

            // Removing existing options
            $j(":input[name=shift_id] option").each(function(){
                let v = $j(this).attr('value');
                if( v !== '' ) $j(this).remove();
            })

            clog(edupress.shifts);

            // Adding new options
            for( i in edupress.shifts ){
                if( parseInt(edupress.shifts[i]['branch_id']) !== branchId ) continue;
                let selected = parseInt(getUrlParameter('shift_id')) === parseInt(edupress.shifts[i]['id']) ? " selected='selected' " : '';
                $j(":input[name=shift_id]").append(`<option value='${edupress.shifts[i]['id']}' ${selected}>${edupress.shifts[i]['title']}</option>`);
            }

        } else if (edupress.class_active == 1) {
            // Updating class
            // Removing existing options
            $j(":input[name=class_id] option").each(function(){
                let v = $j(this).attr('value');
                if( v !== '' ) $j(this).remove();
            })

            // Adding new options
            for( i in edupress.classes ){
                if( parseInt(edupress.classes[i]['branch_id']) !== branchId ) continue;
                let selected = parseInt(getUrlParameter('class_id')) === parseInt(edupress.classes[i]['id']) ? " selected='selected" : '';
                $j(":input[name=class_id]").append(`<option value='${edupress.classes[i]['id']}' ${selected}>${edupress.classes[i]['title']}</option>`);
            }
        }
    }

    function updateClassOptions(shiftId){

        shiftId = parseInt(shiftId);

        // Removing existing options
        $j(":input[name='class_id'] option").each(function(){
            let v = $j(this).attr('value');
            if( v !== '' ) $j(this).remove();
        })

        // Adding new options
        if(edupress.class_active == 1) {
            for( i in edupress.classes ){
                // if( parseInt(edupress.classes[i]['shift_id']) !== shiftId ) continue;
                let classId = parseInt(getUrlParameter('class_id'));
                let selected = classId === parseInt(edupress.classes[i]['id']) ? " selected='selected' " : "";
                $j(":input[name='class_id']").append(`<option value='${edupress.classes[i]['id']}' ${selected}>${edupress.classes[i]['title']}</option>`);
            }
        }
    }

    function updateSectionOptions(classId){

        classId = parseInt(classId);

        // Removing existing options
        $j(":input[name='section_id'] option").each(function(){
            let v = $j(this).attr('value');
            if( v !== '' ) $j(this).remove();
        })

        // Adding new options
        if(edupress.section_active == 1) {
            for( i in edupress.sections ){
                if( parseInt(edupress.sections[i]['class_id']) !== classId ) continue;
                let selected = parseInt(edupress.sections[i]['id']) === parseInt(getUrlParameter('section_id')) ? " selected='selected' " : '';
                $j(":input[name='section_id']").append(`<option value='${edupress.sections[i]['id']}' ${selected}>${edupress.sections[i]['title']}</option>`);
            }
        }
    }

    // Updating shift if active
    $j(document).on('change',':input[name=branch_id]',function (){
        updateShiftOptions(parseInt($j(this).val()));
    })

    // Update class if shift is changed
    $j(document).on('change', ":input[name=shift_id]", function(){
        updateClassOptions(parseInt($j(this).val()));
    })

    // Update sections if class is changed
    $j(document).on('change', ":input[name=class_id]", function(){
        updateSectionOptions(parseInt($j(this).val()));
    })

    let paramBranchId = getUrlParameter('branch_id');
    let paramShiftId = getUrlParameter('shift_id');
    let paramClassId = getUrlParameter('class_id');

    if(paramBranchId > 0){
        if(edupress.shift_active == 1){
            updateShiftOptions(paramBranchId);
        } else {
            updateClassOptions(paramBranchId);
        }
    }
    if(paramShiftId > 0){
        updateClassOptions(paramShiftId);
    }
    if(paramClassId > 0){
        updateSectionOptions(paramClassId);
    }

    // Updating branch if branch is 1 only when dom content loaded done 
    let curBranchId = $j(":input[name='branch_id']").val();
    if( curBranchId == '' && edupress.default_branch_id > 0){
        $j(":input[name='branch_id']").val(edupress.default_branch_id);
        if(edupress.shift_active == 1){
            updateShiftOptions(edupress.default_branch_id);
        } else {
            updateClassOptions(edupress.default_branch_id);
            updateSectionOptions(edupress.default_branch_id);
        }
    }


    // Ajax link for edit post
    $j(document).on('click', '.edupress-edit-post', function (e){
        preventDefault(e);

        let beforeSendCallback = $j(this).data('before-send-callback');
        let successCallback = $j(this).data('success-callback');
        let errorCallback = $j(this).data('error-callback');

        let postId = $j(this).data('id');
        let postType = $j(this).data('post-type');
        let title = 'Update ' + postType.replace(/_/g, ' ');
        let data = `action=edupress_admin_ajax&ajax_action=getPostEditForm&post_id=${postId}&post_type=${postType}&_wpnonce=${edupress.wpnonce}`;

        $j.ajax({
            url: edupress.ajax_url,
            data: data,
            dataType: 'json',
            method: 'POST',
            beforeSend: function (){
                clog(data);
                if( typeof window[beforeSendCallback] !== 'undefined' ){
                    let r = window[beforeSendCallback](data);
                    if ( r === false ) return false;
                }
                showEduPressLoading();
            },
            success: function ( res, xhr ){
                clog(data);
                hideEduPressLoading();
                if( typeof window[successCallback] !== 'undefined' ){
                    window[successCallback]( res, xhr );
                } else {
                    showEduPressPopup( res.data, title );
                }
            },
            error: function (){
                clog(data);
                hideEduPressLoading();
                if( typeof window[errorCallback] !== 'undefined' ){
                    window[errorCallback]( res, xhr );
                } else {
                    showEduPressPopup( res.data, title );
                }
            }
        })
    })

    const updateBaseUrl = () => {
        let search = '#base_url#';
        $j('a').each(function(){
            let href = $j(this).attr('href');
            if( href.indexOf(search) !== -1){
                href = href.replace(search, edupress.page_url);
                $j(this).attr('href', href);
            }
        })
    }

    updateBaseUrl();

    // Toggle form under current parent
    $j(document).on('click', '.toggleForm', function(e){
        preventDefault(e);
        $j(this).parents('div').find('form:eq(0)').toggle();
        let curText = $j(this).text();
        if( curText === '[Show]'){
            $j(this).text('[Hide]');
        } else {
            $j(this).text('[Show]');
        }
    })

    // Bulk user CSV Upload
    $j(document).on('click', '.showFormToUploadCsvToAddUsers', function(e){

        $j('.bulk_users_csv_trigger').click();

    })

    // On change csv bulk user upload
    $j(document).on('change', '.bulk_users_csv_trigger', function (e){
        var files_data = $j(this).prop('files');
        var form_data = new FormData();
        $j.each(files_data, function(i, file){
            form_data.append('files[]', file);
        });
        form_data.append('action', 'edupress_admin_ajax');
        form_data.append('ajax_action', 'bulkUsersCsvUpload');
        form_data.append('_wpnonce', edupress.wpnonce );

        $j.ajax({
            url: edupress.ajax_url,
            type: 'post',
            data: form_data,
            contentType: false,
            processData: false,
            dataType:'JSON',
            beforeSend: function(){
                console.log(form_data);
                showEduPressLoading();
            },
            success: function(response){
                hideEduPressLoading();
                if(response.status === 1){
                    let success = typeof  response.success !== 'undefined' ? response.success : 0;
                    let error = typeof  response.error !== 'undefined' ? response.error : 0;
                    showEduPressPopup( `${success} people inserted, ${error} failed!` );
                    setTimeout( function(){
                        hideEduPressPopup();
                        // window.location.reload();
                    }, 3000 );
                }
            }
        });

    })

    $j(document).on( 'focus', ":input[name='user_search']", function(e){
        $j(this).select();
    })

    // Publish new post
    $j(document).on('click', '.edupress-publish-post', function (e){

        preventDefault(e);
        let postType = $j(this).data('post-type');
        let data = `action=edupress_admin_ajax&ajax_action=getPostPublishForm&post_type=${postType}&_wpnonce=${edupress.wpnonce}`;

        $j.ajax({
            url: edupress.ajax_url,
            data: data,
            dataType: 'json',
            method: 'POST',
            beforeSend: function(){
                clog(data);
                showEduPressLoading();
            },
            success: function ( res ){
                hideEduPressLoading();
                if(res.status === 1){
                    showEduPressPopup(res.data);
                    triggerSearchUser();
                } else {
                    showEduPressStatus('error');
                }
            },
            error: function (){}
        })
    })



    // Attendance bulk status change
    $j(document).on('change', '.attendance-bulk-status', function (){
        let v = $j(this).val();
        $j(this).parents('form').find(':input[name="status[]"]').val(v);
    })

    // Generate attendance api key
    $j(document).on('click touch', '.getApiKey', function(e){
        preventDefault(e);
        let token = $j("input[name='attendance_token']").val();
        let data = `action=edupress_admin_ajax&ajax_action=getApiKey&&_wpnonce=${edupress.wpnonce}&token=${token}`;
        $j.ajax({
            url: edupress.ajax_url,
            data: data,
            method: 'POST',
            dataType: 'JSON',
            beforeSend: function(){
                clog(data);
                if(!confirm("Are you sure to generate a new API Key?")){
                    return false;
                }
                showEduPressLoading();
            },
            success: function(res){
                hideEduPressLoading();
                clog(res);
                if(res.status === 1){
                    $j("input[name='attendance_api_key']").val(res.api_key);
                }
            },
            error: function(xhr, r){

            }
        })
    })

    // Generate attendance ids for all users
    $j(document).on('click', '.generate_attendance_ids', function(e){
        preventDefault(e);
        let d = `action=edupress_admin_ajax&ajax_action=generateAttendanceIdForUsers&_wpnonce=${edupress.wpnonce}`;
        $j.ajax({
            url:edupress.ajax_url,
            data: d,
            dataType: 'json',
            method: 'POST',
            beforeSend: function(){
                clog(d);
                showEduPressLoading();
            },
            success: function(r){
                clog(r);
                hideEduPressLoading();
                if(r.status === 1){
                    showEduPressStatus('success');
                }
            }
        })
    })

    // Delete Post
    $j(document).on('click', '.edupress-delete-post', function (e){
        preventDefault(e);

        let countTotal = $j('.edupress-delete-post').length;
        let thisEle = $j(this);
        let id = $j(this).data('id');
        let postType = $j(this).data('post-type');
        let data = `action=edupress_admin_ajax&ajax_action=deletePost&post_id=${id}&post_type=${postType}&_wpnonce=${edupress.wpnonce}`;

        $j.ajax({
            url: edupress.ajax_url,
            data: data,
            dataType: 'json',
            method: 'POST',
            beforeSend: function (){
                clog(data);
                if(countTotal === 1){
                    alert("Sorry! You cannot delete this item.");
                    return false;
                }
                if( !confirm('Are you sure to delete?') ){
                    return false;
                }
                showEduPressLoading();
            },
            success: function ( res ){
                hideEduPressLoading();
                if( res.status === 1 ){
                    // delete
                    thisEle.closest('tr').remove();
                } else {
                    showEduPressStatus('error');
                }
            },
            error: function (){}
        })

    })

    // Bulk select to delete
    $j(document).on('click touch', '.edupress-bulk-select-all', function (){

        let s = $j('.edupress-bulk-select-item');
        s.prop('checked', $j(this).prop('checked') );
        s.each(function (){
            if( $j(this).prop('checked') ){
                $j(this).closest('tr').addClass('highlighted');
            } else {
                $j(this).closest('tr').removeClass('highlighted');
            }
        })
    })

    // highlight when a value is unchecked
    $j(document).on('click touch change', '.edupress-bulk-select-item', function(){
        if( $j(this).prop('checked') ){
            $j(this).closest('tr').addClass('highlighted');
        } else {
            $j(this).closest('tr').removeClass('highlighted');
        }
    })

    // Bulk delete
    $j(document).on('click touch', '.edupress-bulk-delete', function (){
        let sel = $j(`:input[name='edupress-bulk-delete-post[]']:checked`);
        let countPost = sel.length;
        if( !countPost ) {
            alert("Select an item at least!");
            return false;
        }
        let data = `action=edupress_admin_ajax&ajax_action=bulkDeletePost&_wpnonce=${edupress.wpnonce}`;
        let postType;
        sel.each(function (){
            let postId = $j(this).data('id');
            data += `&post_id[]=${postId}`;
            postType = $j(this).data('post-type');
        })
        data += `&post_type=${postType}`;

        $j.ajax({
            url: edupress.ajax_url,
            data: data,
            dataType: 'json',
            method: 'POST',
            beforeSend: function (){
                clog(data);
                if(!confirm("Are you sure to delete?")){
                    return false;
                }
                showEduPressLoading();
            },
            success: function ( res ){
                clog(res);
                hideEduPressLoading();
                if( res.status === 1 ){
                    res.posts.forEach( (v, k) => {
                        $j(`tr[data-id='${v}']`).slideUp();
                    })
                }
            },
            error: function (){}
        })
    })

    // Delete publish bulk user
    $j(document).on('click', '.publish-bulk-user-delete', function (){
        let c = $j('.publish-bulk-user-delete').length;
        if ( c === 1 ) {
            alert("You cannot delete all rows!");
            return false;
        }
        $j(this).closest('tr').remove();
    })

    // Duplicate publish bulk user row
    $j(document).on('click', '.publish-bulk-user-copy', function (){
        let currentRow = $j(this).closest('tr');
        let newRow = currentRow.clone();
        $j(currentRow).after(newRow);
    })

    // Modify user
    $j(document).on('click touch', '.edupress-modify-user', function (e){
        preventDefault(e);

        let action = $j(this).data('action');
        let ajaxAction = action === 'edit' ? 'showUserEditForm' : 'deleteUser'
        let userId = $j(this).data('user-id');
        let ele = $j(this);

        let data = `action=edupress_admin_ajax&ajax_action=${ajaxAction}&user_id=${userId}&_wpnonce=${edupress.wpnonce}`;

        $j.ajax({
            url: edupress.ajax_url,
            data: data,
            dataType: 'json',
            method: 'POST',
            beforeSend: function (){
                clog(data);
                if( action === 'delete' && !confirm("Are you sure to delete?")){
                    return false;
                }
                showEduPressLoading();
            },
            success: function( res ){
                hideEduPressLoading();
                if ( !res.status ){
                    showEduPressStatus('error' );
                } else {
                    if ( action === 'delete' ){
                        ele.closest('tr').remove();
                    } else {
                        showEduPressPopup( res.data, 'Update User' );
                    }
                }
            },
            error: function(){}
        })
    })


    // result absent status change
    $j(document).on('change', '.edupress-result-absent-status', function (){
        let v = parseInt($j(this).val());
        if( v === 1 ){
            $j(this).parents('td').find(":input[type='number']").val(0);
            $j(this).parents('td').find(":input[type='number']").prop('readonly', true);
        } else {
            $j(this).parents('td').find(":input[type='number']").prop('readonly', false);
            $j(this).parents('td').find(":input[type='number']").val('');
            $j(this).parents('td').find(":input[type='number']").focus();
        }
    })

    // Result edit form validation
    $j(document).on('change', `.edupress-result-form thead tr:first input[type='number']`, function (){
        let maxVal = $j(this).val();
        let markHead = $j(this).data('mark-head');
        $j(":input[name='"+markHead+"[]']").each(function(){
          $j(this).attr('max', maxVal );
        })
    })

    $j(document).on('change', ".edupress-result-form table tbody tr :input[type='number']", function (){
        $j(".edupress-result-form table tr").each( function (){
            let total = 0;
            $j(this).find('input[type="number"]').each(function() {
                total += parseInt($j(this).val()) || 0;
            });
            $j(this).find('.edupress-value-container').text(total);
        });

    })

    // SMS Sent to
    $j(document).on('change', ":input[name='send_to']", function (){
        let v = $j(this).val();
        let sel = $j(".form-row.branch_id,.form-row.shift_id,.form-row.class_id,.form-row.section_id,.form-row.role,.form-row.status");

        if( v === 'users' ){
            sel.show();
        } else {
            sel.hide();
        }
    })

    // count mobile numbers
    $j(document).on( 'change keyup kewdown', ":input[name=mobile_numbers]", function(){
        var text = $j(this).val();
        var nonEmptyLines = text.split('\n').filter(function(line) {
            return line.trim() !== '' ;
        });
        $j('.total_numbers').text( nonEmptyLines.length );
    });

    // Sms count and pricing
    $j(document).on('change keyup keydown', ":input[name=sms_text]", function(){
        var textLength = $j(this).val().length;
        var smsCount = Math.ceil(textLength / 159);
        $j('.sms_len').text(textLength);
        $j('.sms_count').text(smsCount);
        var totalNumbers = parseInt($j('.total_numbers').text());
        var totalCost = edupress.sms_rate * totalNumbers * smsCount;
        $j('.sms_rate').text(edupress.sms_rate);
        $j('.sms_cost').text(totalCost);
    });

    // sms compose user select
    $j(document).on( 'change', '.sms-compose-user-select', function (){
        let isChecked = $j(this).is(':checked');
        $j(this).parents('li').find(":input[type=checkbox]").prop('checked', isChecked);
    })

    // Grade table row copy
    $j(document).on( 'click touch', '.copy-grade-table-row', function(){
        let currentRow = $j(this).closest('li');
        let lastValue = currentRow.find(":input[name='range_end[]']").val();
        let newValue = parseInt(lastValue) + 1 || 0;
        let newRow = currentRow.clone();
        newRow.find(":input").val("");
        newRow.find(":input[name='range_start[]']").val(newValue);
        currentRow.after(newRow);
    })
    // Grade table row remove
    $j(document).on( 'click touch', '.remove-grade-table-row', function(){
        let countRows = $j(this).parents('ul').find('li').length;
        if( countRows === 1) {
            alert("You cannot delete all rows!");
            return false;
        }
        if( confirm("Are you sure to delete?") ){
            $j(this).closest('li').remove();
        }
    })

    // Duplicate transaction row
    $j(document).on('click', '.copy-transaction-row', function(){
        let currentRow = $j(this).closest('li');
        let newRow = currentRow.clone();
        newRow.find(":input[name='fee_type[]'],:input[name='fee_amount[]']").val('');
        newRow.find(":input[name='fee_due[]']").val(0);
        currentRow.after(newRow);
    })

    // Remove transaction row
    $j(document).on('click', '.remove-transaction-row', function(){
        let currentRow = $j(this).closest('li');
        let countRows = $j(this).parents('ul').find('li').length;
        if( countRows === 1 ){
            alert("Sorry! You cannot delete all rows.");
            return false
        } else {
            currentRow.remove();
            calculateGrossAmount();
            calculateNetAmount();
        }
    })

    // change in fee_amount
    $j(document).on('change keyup keydown', '.edupress-publish-transaction .fee_amount, .edupress-edit-transaction .fee_amount', function(){
        calculateGrossAmount();
    })

    // Adjust discount
    $j(document).on('change keyup keydown', ".discount_type,.discount_amount,:input[name='fee_amount[]']",function(e){
        calculateGrossAmount();
        calculateNetAmount();
    })

    const calculateGrossAmount = () => {
        let totalAmount = 0;
        $j(".edupress-publish-transaction .fee_amount, .edupress-edit-transaction .fee_amount").each(function(){
            let amt = parseFloat($j(this).val());
            if(amt == '' || isNaN(amt)) amt = 0;
            totalAmount += amt;
        })
        if(isNaN(totalAmount)) totalAmount = 0;
        $j("input[name=amount]").val(totalAmount);
        $j(".gross_amount").val(totalAmount);
    }

    const calculateNetAmount = () => {

        let grossAmount = parseFloat($j('.gross_amount').val());
        if(isNaN(grossAmount)) grossAmount = 0;
        let discountType = $j(':input[name=discount_type]').val();
        let discountAmount = parseFloat($j(".discount_amount").val());
        if(isNaN(discountAmount) || discountAmount == '') discountAmount = 0;
        let discount = 0;
        let netAmount = grossAmount;

        if(discountAmount > 0){
            if ( discountType === 'percentage' ){
                $j(".discount_amount").attr('max', 100);
                discount = grossAmount * discountAmount / 100;
                console.log('discount', discount);
                netAmount = grossAmount - discount;
            } else if( discountType === 'fixed' ){
                netAmount = parseFloat(grossAmount - discountAmount);
            }
        }

        if(isNaN(netAmount) || netAmount < 0) netAmount = 0;
        netAmount = netAmount.toFixed(2);
        $j('.t_amount').val(netAmount);

    }

    // Autocomplete for filter
    if( $j('.edupress-filter-list .t_user').length > 0 ){

        $j('.edupress-filter-list :input.t_user').autocomplete({
            minLength: 3,
            source: function( request, response ){
                let branchId = $j(":input[name=branch_id]").val();
                $j.ajax({
                    url: edupress.ajax_url,
                    data: {
                        'action': 'edupress_admin_ajax',
                        'ajax_action' : 'getTransactionUserDetails',
                        'term' : request.term,
                        '_wpnonce': edupress.wpnonce,
                        'branch_id': branchId,
                    },
                    dataType: 'json',
                    method: 'POST',
                    beforeSend:function(){
                        if(branchId === ''){
                            alert("Please select a branch first!");
                            return false;
                        }
                    },
                    success: function( r ){
                        response(r);
                    },
                    error: function(){}
                })
            },
            select: function( e, ui ){
                let userId = ui.item.key;
                $j(this).parents('form').find('.t_user_id').val(userId);
            },
            change: function(){},
            close: function (){},
            open: function(){}
        })
    }


    // Bulk SMS send
    $j(document).on('click touch', '.result-bulk-sms-send', function(){

        let sel = $j(":input[name='student_id[]']");
        let studentIds = [];

        sel.each(function(){
            if( $j(this).prop('checked') ){
                studentIds.push($j(this).val());
            }
        })

        let data = {
            action: 'edupress_admin_ajax',
            ajax_action: 'sendResultSmsToBulkUsers',
            _wpnonce: edupress.wpnonce,
            student_ids: studentIds,
            sms_data: edupress.sms_data,
        }

        $j.ajax({
            url: edupress.ajax_url,
            data: data,
            dataType: 'json',
            method: 'POST',
            beforeSend: function(){
                clog(data);
                if( studentIds.length === 0){
                    alert("You must select at least one student.");
                    return false;
                }
                if( !confirm("Are you sure to select SMS?")){
                    return false;
                }
                showEduPressLoading();
            },
            success: function (res){
                clog(res);
                hideEduPressLoading();
                showEduPressStatus( res.status ? 'success' : 'error' );
                $j('.edupress-bulk-select-all, .edupress-bulk-select-item').prop('checked', false);
            }
        })
    })

    // Result bulk print
    $j(document).on('click touch', '.result-bulk-print', function(e){

        let method = $j(this).data('rank-method');
        let sel = $j(":input[name='student_id[]']");
        let termId = $j(this).data('term-id');
        let startDate = $j(this).data('start-date');
        let endDate = $j(this).data('end-date');
        let studentIds = [];
        let ele = $j(this);

        sel.each(function(){
            if( $j(this).prop('checked') ){
                studentIds.push($j(this).val());
            }
        })

        let data = {
            action: 'edupress_admin_ajax',
            ajax_action: 'printBulkResult',
            _wpnonce: edupress.wpnonce,
            student_ids: studentIds,
            method: method,
            subject_order: edupress.subject_order,
            term_id : termId,
            start_date: startDate,
            end_date: endDate,
            data: edupress.result_data,
        }

        $j.ajax({
            url: edupress.ajax_url,
            data: data,
            dataType: 'json',
            method: 'POST',
            beforeSend: function(){
                clog(data);
                if( studentIds.length === 0){
                    alert("You must select at least one student.");
                    return false;
                }
                if( !confirm("Are you sure to print results?")){
                    return false;
                }
                showEduPressLoading();
            },
            success: function (res){
                clog(res);
                $j('.edupress-bulk-select-all').click();
                hideEduPressLoading();
                printContent(res.data);
            }
        })
    })

    // Result disabling row if unregistered selected
    $j(document).on('change', `:input[name='unregistered[]']`, function (e){
        let unregistered = parseInt($j(this).val());
        let curEle = $j(this);
        if( unregistered === 1 ){
            $j(this).parents('tr').find(":input").each(function (){
                if( !$j(this).hasClass('edupress-result-unregistered-status')){

                    // Making absent
                    if( $j(this).hasClass('edupress-result-absent-status') ){
                        $j(this).val(1);
                    }

                    // Making result 0
                    if( $j(this).attr('type') !== 'hidden'){
                        $j(this).val(0);
                    }

                    // Making all fields disabled except registered status
                    $j(this).prop( 'readonly', true );
                }

            })

            // Updating total amount
            $j(this).parents('tr').find('.edupress-value-container').text(0);

        } else {

            $j(this).parents('tr').find(":input").each(function (){
                if( !$j(this).hasClass('edupress-result-unregistered-status')){

                    // Making absent false
                    if( $j(this).hasClass('edupress-result-absent-status') ){
                        $j(this).val(0);
                    }

                    // Making all fields value 0
                    if( $j(this).attr('type') !== 'hidden'){
                        $j(this).val(0);
                    }

                    // Updating all fields except status
                    $j(this).prop( 'readonly', false );
                }

            })
        }
    })

    // Mobile menu update
    $j(document).on('change', '#mobileMenu', function(){
        window.location.href = $j(this).val();
    })

    // Print Individual result
    $j(document).on('click', '.printIndividualResult', function(e){
        let userId = $j(this).data('user-id');
        let termId = $j(this).data('term-id');
        let startDate = $j(this).data('start-date');
        let endDate = $j(this).data('end-date');
        let method = $j(this).data('rank-method');
        let data = {
            '_wpnonce' : edupress.wpnonce,
            'action' : 'edupress_admin_ajax',
            'ajax_action' : 'printIndividualResult',
            'user_id' :  userId,
            'rank_method' : method,
            'term_id': termId,
            'start_date': startDate,
            'end_date': endDate,
            'subject_order': edupress.subject_order,
            'class_data' : edupress.class_data,
            'data' : edupress.result_data[userId],
        };
        $j.ajax({
            url: edupress.ajax_url,
            data: data,
            dataType: 'JSON',
            method: 'POST',
            beforeSend: function(){
                clog(data);
                showEduPressLoading();
            },
            success: function(res, xhr ){
                clog(res);
                hideEduPressLoading();
                printContent(res.data);
            }
        })
    })

    // Bulk user update
    $j(document).on('click','.edupress-bulk-update-users',function(e){
        let sel = $j(".edupress-bulk-select-item:checked");
        let users = [];
        $j(".edupress-bulk-select-item:checked").each(function (){
            users.push($j(this).val());
        })
        let data = `action=edupress_admin_ajax&ajax_action=showBulkUserUpdateScreen&_wpnonce=${edupress.wpnonce}&users=${users.join(',')}`;
        $j.ajax({
            url: edupress.ajax_url,
            data: data,
            dataType: 'json',
            method: 'POST',
            beforeSend: function (){
                if(sel.length === 0){
                    alert('Please select an item first!');
                    return false;
                }
                clog(data);
                showEduPressLoading();
            },
            success: function(res){
                hideEduPressLoading();
                clog(res);
                if(res.status === 1){
                    showEduPressPopup(res.data);
                }
            }
        })
    })

    // Delete SMS Logs
    $j(document).on('click', '#delete_sms_logs', function(e){
        preventDefault(e);
        let start = $j('#sms_delete_start').val();
        let end = $j('#sms_delete_end').val();
        if( start === '' || end === '' ){
            alert('Please select both dates!');
            return false;
        }
        let data = `action=edupress_admin_ajax&ajax_action=deleteSmsLogs&start_date=${start}&end_date=${end}&_wpnonce=${edupress.wpnonce}`;
        $j.ajax({
            url: edupress.ajax_url,
            data: data,
            dataType: 'json',
            method: 'POST',
            beforeSend: function(){
                clog(data);
                showEduPressLoading();
            },
            success: function(r){
                clog(r);
                hideEduPressLoading();
            }
        })
    })

    // Send Attendance Summary report
    $j(document).on('click', '.sms-attendance-summary', function(e){
        let users = $j(":input[name='user_id[]']:checked").map(function() {
            return this.value;
        }).get();
        let users_st = users.join(',');
        // let data = `action=edupress_admin_ajax&ajax_action=smsAttendanceSummaryReport&_wpnonce=${edupress.wpnonce}&users=${users_st}&attendance_data=` + JSON.stringify(edupress.summary_json);
        let data = {
            action: 'edupress_admin_ajax',
            ajax_action: 'smsAttendanceSummaryReport',
            _wpnonce: edupress.wpnonce,
            users: users_st,
            attendance_data: edupress.summary_json,
        };

        $j.ajax({
            url: edupress.ajax_url,
            data: data,
            dataType: 'JSON',
            method: 'POST',
            beforeSend: function(){
                clog(data);
                if( users.length === 0 ) {
                    alert('Please select users first!');
                    return false;
                }
                if(!confirm('Are you sure to send SMS?')) return false;
                showEduPressLoading();
            },
            success: function(r){
                clog(r);
                hideEduPressLoading();
                if(r.status === 1){
                    showEduPressStatus('success');
                }
            }
        })
    })

    // Monthly day selector
    $j(document).on('change', '.monthly_day_selector', function(e){
        let dayname = $j(this).data('dayname');
        let month = $j(this).data('month');
        let year = $j(this).data('year');

        let isChecked = $j(this).is(":checked");
        $j(`:input[name='changeDayStatus'][data-dayname='${dayname}'][data-month='${month}'][data-year='${year}']`).click();
    })

    // Calendar month selector
    $j(document).on('change', '#calendarMonthSelector', function(e){
        let v = $j(this).val();
        if( v.trim() === '' ) return false;
        $j('.calendar-month').hide();
        $j(`.calendar-month[data-my='${v}'],.cal-save-btn`).show();

    })

    // Showing Delete Data option
    $j(document).on('click touch', '.process_delete_data', function(e){
        preventDefault(e);
        let types = $j(":input[name='delete_data_types[]']:checked").map(function(){
            return $j(this).val();
        }).get().join(',');
        let startDate = $j("input[name='delete_start_date']").val();
        let endDate = $j("input[name='delete_end_date']").val();
        let data = `action=${edupress.ajax_action}&ajax_action=processDeleteData&data_types=${types}&_wpnonce=${edupress.wpnonce}&start_date=${startDate}&end_date=${endDate}`;
        $j.ajax({
            url: edupress.ajax_url,
            data: data,
            dataType: 'JSON',
            method: 'POST',
            beforeSend: function(){
                clog(data);
                if(types.length === 0 || (startDate === '' && endDate === '') ) {
                    alert("Please select data types and one of the dates!");
                    return false;
                }
                showEduPressLoading();
            },
            success: function(r){
                hideEduPressLoading();
                clog(r);
                if(r.status === 1){
                    $j('.value-wrap .delete_data_stats').html(r.data);
                    $j('.delete_confirm_btn').show();
                }
            }
        })
    })

    // Confirming deleting data
    $j(document).on('click touch', '.delete_confirm_btn', function(e){
        preventDefault(e);
        let types = $j(":input[name='confirm_post_types[]']:checked").map(function(){
            return $j(this).val();
        }).get().join(',');
        let startDate = $j("input[name='delete_start_date']").val();
        let endDate = $j("input[name='delete_end_date']").val();
        let data = `action=${edupress.ajax_action}&ajax_action=confirmDeleteData&data_types=${types}&_wpnonce=${edupress.wpnonce}&start_date=${startDate}&end_date=${endDate}`;
        $j.ajax({
            url: edupress.ajax_url,
            data: data,
            dataType: 'JSON',
            method: 'POST',
            beforeSend: function(){
                clog(data);
                if(types.length === 0 || (startDate === '' && endDate === '') ) {
                    alert("Please select at least one item to confirm deletion!");
                    return false;
                }
                showEduPressLoading();
            },
            success: function(r){
                hideEduPressLoading();
                clog(r);
            }
        })
    })

    let draggedItem = null;
    let dragSelector = "table.draggable[data-post-type='subject'] tbody tr";

    // Drag Drop to Save Order
    $j(document).on('dragstart', dragSelector, function(e){
        draggedItem = $j(this);
        $j(this).addClass('dragging');
        setTimeout( () => $j(this).hide(), 0 );
    });

    // Dragend
    $j(document).on('dragend', dragSelector, function(e){
        draggedItem = null;
        $j(this).removeClass('dragging').show();
    });

    // Dragover
    $j(document).on('dragover', dragSelector, function(e){
        e.preventDefault();
    })

    // Dragover
    $j(document).on('drop', dragSelector, function(e){
        if(draggedItem){
            $j(this).before(draggedItem);
            saveNewDragOrder();
        }
    });

    // register attendance device
    $j(document).on('click', '.register_attendance_device', function(e){
        preventDefault(e);

        let device_name = $j("#attendance_device_name").val();
        let device_count = parseInt($j("#attendance_device_count").val());
        if(device_name === ''){
            alert("Please select device name!");
            return false;
        }
        if( isNaN(device_count) || device_count < 1 ){
            alert("Please select at least one device!");
            return false;
        }
        let data = `device_name=${device_name}&device_count=${device_count}&action=edupress_admin_ajax&ajax_action=registerDevice&_wpnonce=${edupress.wpnonce}`;
        $j.ajax({
            url: edupress.ajax_url,
            data: data,
            dataType: 'json',
            method: 'POST',
            beforeSend: function(){
                clog(data);
                showEduPressLoading();
            },
            success: function(res){
                clog(res);
                hideEduPressLoading();
                showEduPressStatus('success');
            }
        })
    });

    let saveNewDragOrder = () => {
        let order = [];
        let postType = null;
        $j(dragSelector).each(function(e){
            order.push($j(this).data('post-id'));
            if(postType === null){
                postType = $j(this).data('post-type');
            }
        })

        let data = `action=edupress_admin_ajax&ajax_action=savePostOrder&post_type=${postType}&order=${order}&_wpnonce=${edupress.wpnonce}`;
        $j.ajax({
            url: edupress.ajax_url,
            data: data,
            dataType: 'JSON',
            method: 'POST',
            beforeSend: function(){
                clog(data);
                showEduPressLoading();
            },
            success: function(r){
                hideEduPressLoading();
                clog(r);
            }
        })
    }

    // Insert National holidays 
    $j(document).on('click', '.get_default_holidays', function(e){
        preventDefault(e);
        let holidays = $j(this).data('holidays');
        $j("#attendance_national_holidays").val(holidays);
    })

    // decheck sms or print when transaction type outflow 
    $j(document).on('change', ":input[name='is_inflow']", function(e){
        let isInflow = $j(this).val() == '1' ? true : false;
        if(isInflow){
            $j("[name='extra_actions[]']").prop('checked', true);
        } else {
            $j("[name='extra_actions[]']").prop('checked', false);
        }
    })


})
///// jQuery Ends //////

function deleteCalendarBeforeSendCallback(){
    if(!confirm("Are you sure to delete?")) return false;
    showEduPressLoading();
    return true;

}
function getValueByKey(jsonData, key) {
    var pairs = jsonData.split("&");
    for (var i = 0; i < pairs.length; i++) {
        var pair = pairs[i].split("=");
        if (pair[0] === key) {
            return pair[1];
        }
    }
    return null; // Return null if key is not found
}

function examSuccessCallback( res ){
    if( res.status !== 0 ){
        hideEduPressPopup();
        if( res.payload.ajax_action === 'publishPost' ){
            showExamResultForm( res.post_id );
        } else {
            showEduPressStatus('success');
        }

    } else {
        hideEduPressPopup();
        showEduPressStatus('error');
    }
}


// trigger transaction search user
function triggerSearchUser(){
    let sel = $j(".edupress-publish-attendance .a_user, .edupress-publish-transaction :input.user_search, .edupress-edit-transaction :input.user_search");
    if ( sel.length > 0 ){
        sel.autocomplete({
            minLength: 2,
            source: function( request, response ){
                let branchId = sel.parents('form').find(":input[name='branch_id']").val();
                $j.ajax({
                    url: edupress.ajax_url,
                    data: {
                        'branch_id': branchId,
                        'action': 'edupress_admin_ajax',
                        'ajax_action' : 'getTransactionUserDetails',
                        'term' : request.term,
                        '_wpnonce': edupress.wpnonce,
                    },
                    dataType: 'json',
                    method: 'POST',
                    beforeSend:function(){
                        if( branchId === '' ){
                            alert('Please select a branch first!');
                            return false;
                        }
                    },
                    success: function( r ){
                        response(r);
                    },
                    error: function(){}
                })
            },
            select: function( e, ui ){
                let userId = ui.item.key;
                $j(this).parents('form').find('.t_user_id').val(userId);
                $j(this).parents('form').find('.user_id').val(userId);
                $j(this).parents('form').find('.shift_id').val(ui.item.shift_id);
                $j(this).parents('form').find('.class_id').val(ui.item.class_id);
                $j(this).parents('form').find('.section_id').val(ui.item.section_id);
                $j('.transaction-user-details').html(ui.item.details);
            },
            change: function(){},
            close: function (){},
            open: function(){}
        })
    }
}

function showExamResultForm( exam_id, callback ){
    let data = `action=edupress_admin_ajax&ajax_action=getExamResultForm&_wpnonce=${edupress.wpnonce}&post_id=${exam_id}`;
    $j.ajax({
        url: edupress.ajax_url,
        data: data,
        dataType: 'json',
        method: 'POST',
        beforeSend: function (){
            clog(data);
            showEduPressLoading();
        },
        success: function ( res ){
            clog(res);
            hideEduPressLoading();
            showEduPressPopup( res.data );

        }
    })
}

function bulkUserBeforeSendCallback( data ){
    let d = data.data;
    let role = getValueByKey(d, 'role');
    let shift_id = getValueByKey(d, 'shift_id');
    let class_id = getValueByKey(d, 'class_id');
    let section_id = getValueByKey(d, 'section_id');

    if ( role === 'student' ){
        if ( shift_id === '' || class_id === '' || section_id === '' ){
            alert('You must select all fields!');
            return false;
        }
    }
    return true;
}

function bulkUserSuccessCallback( res ){
    if( res.status === 1 ){
        hideEduPressLoading();
        showEduPressPopup( res.data );
    }
}

function publishBulkUserBeforeSendCallback( data, ele ){
    showEduPressLoading();

    $j('.publish-bulk-user-status').html(`<img alt="edupress" src="${edupress.img_dir_url}loading.gif" style="height: 24px; width: auto;">`);
    ele.closest('form').find("input[type='submit']").prop('disabled', 'disabled');
    return true;
}

function publishBulkUserSuccessCallback( res ){
    hideEduPressLoading();
    res.payload.forEach( (v , k) => {
        if( v.user_id > 0 ) {
            $j(`.publish-bulk-user-status[data-row-id=${k}]`).html(`<img alt="edupress" title="${v.user_status}" src="${edupress.img_dir_url}success.png" style="height: 24px; width: auto;">`);
        } else {
            $j(`.publish-bulk-user-status[data-row-id=${k}]`).html(`<img alt="edupress" title="${v.user_status}" src="${edupress.img_dir_url}error.png" style="height: 24px; width: auto;">`);
        }
    })
}

// result success callback
function resultSuccessCallback( res ){
    hideEduPressLoading();
    if ( res.status === 1){
        showEduPressPopup(res.data);
    } else {
        showEduPressStatus('error');
    }
}

function smsSuccessCallback( res ){
    hideEduPressLoading();
    showEduPressPopup( res.data );
}

// grade table before send callback
function grade_tableBeforeSendCallback( data, ele ){
    let res = true;
    $j("ul.grade-table li").each(function(){
        let rs = $j(this).find("input[name='range_start[]']");
        let re = $j(this).find("input[name='range_end[]']");
        if( parseFloat(rs.val()) > parseFloat(re.val()) ){
            re.focus();
            alert("Invalid range value!");
            res = false;
        }
    })

    return res;

}

if ( typeof  smsGetCurBal === 'undefined' ){
    function smsGetCurBal(){
        let data = `action=edupress_admin_ajax&ajax_action=getSmsCurrentBalance&_wpnonce=${edupress.wpnonce}`;
        $j.ajax({
            url: edupress.ajax_url,
            data: data,
            dataType: 'json',
            method: 'POST',
            beforeSend: function(){},
            success: function( res ){
                if(res.status == 1){
                    $j(".sms-current-balance").text(res.data);
                }
            },
            error: function(){}
        })
    }
}

function smsComposeBeforeSendCallback(){
    return confirm('Are you sure to send SMS?' );
}
function smsComposeSuccessCallback(){
    hideEduPressPopup();
    showEduPressStatus('success');
    smsGetCurBal();
}

function getUrlParameter(name) {
    name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
    var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
    var results = regex.exec(location.search);
    return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
}

function smsUserResultSuccessCallback(res, xhr, ele ){
    hideEduPressLoading();
    let img = res.status === 1 ? 'success.png' : 'error.png';
    img = edupress.img_dir_url + img;

    ele.after(`<img class="edupress-icon size-1x" src='${img}'>`);

}

function getLoginFormSuccessCallback( res ){
    hideEduPressLoading();
    if(res.status === 1){
        showEduPressPopup(res.data);
    }
}

function loginSuccessCallback( res ){
    hideEduPressPopup();
    showEduPressStatus('success', 3000);
    setTimeout( function(){
        location.reload();
    }, 2000);
}
function loginErrorCallback( res ){
    alert(res.data);
    return false;
}

function showProfileUpdateFormCallback( res ){
    hideEduPressLoading();
    showEduPressPopup(res.data);
    return false;
}

function editUserBeforeSendCallback(){
    let up = $j(":input[name=user_pass]").val();
    let cup = $j(":input[name=confirm_user_pass]").val();
    if ( up !== '' ){
        if( up.length < 7 ){
            alert("Minimum password length is 7!");
            return false;
        }
        if( up !== cup  ){

            alert("Password and Confirm Paswword don't match!");
            return false;

        }
    }
    return true;
}

function editCalendarSuccessCallback( res ){
    hideEduPressLoading();
    showEduPressPopup(res.data);
}

function showUserProfileSuccessCallback( res ){
    if(res.status === 1){
        hideEduPressLoading();
        showEduPressPopup(res.data);
    }
}

function showPopupOnCallback( res ){
    if(res.status === 1){
        hideEduPressLoading();
        showEduPressPopup(res.data);
    }
}

function insertAttendanceUsersInPopup( res ){
    hideEduPressLoading();
    $j(".attendance-users-wrap").html(res.data);
}

function confirmBeforeSendCallback(){
    hideEduPressLoading();
    return confirm('Are you sure to proceed on?');
}

function printIdCardBeforeSend(){
    let printType = $j("[name='print_type']").val();
    if( printType === 'students_roll_wise' ){
        let roll = $j("[name='roll']").val();
        if( roll === '' ){
            alert('Please enter roll!');
            return false;
        }
    } else if( printType === 'class_wise' ){
        let classId = $j("[name='class_id']").val();
        if( classId === '' ){
            alert('Please select class!');
            return false;
        }
    }
    return true;
}

function printIdCardAfterSuccess(data){
    const pdfUrl = data.data; // adjust if your key is different

    // Create a hidden link and click it to trigger download
    const a = document.createElement('a');
    a.href = pdfUrl;
    a.download = ''; // optional: set a filename here
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);

}

function printDataOnCallback(data = ''){
    // open a new window, insert data and print it 
    const win = window.open('', '_blank');
    win.document.open();
    typeof data === 'string' ? win.document.write(data) : win.document.write(data.data);
    win.document.close();
    
    win.onload = () => {
        win.focus();
        win.print();
    }
}

function transactionSuccessCallback(data){
    if(data.status == 1 && data.print == 1){
        printDataOnCallback(data.data);
    }
    hideEduPressLoading();
    hideEduPressPopup();
    showEduPressStatus( data.status == 1 ? 'success': 'error');
}

function printUserListAfterSuccess(data){
    data = data.data.trim();
    if(data != ''){
        printDataOnCallback(data);
    }
}