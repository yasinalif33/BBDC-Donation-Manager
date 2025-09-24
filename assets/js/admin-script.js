jQuery(document).ready(function($) {
    function handleAjaxAction(button, action, confirmationMessage) {
        if (confirmationMessage && !confirm(confirmationMessage)) return;
        const row = button.closest('tr');
        const id = button.data('id');
        const spinner = $('<span class="spinner is-active"></span>');
        button.parent().append(spinner).find('.button').prop('disabled', true);
        $.post(bbdc_ajax_object.ajax_url, { action: action, nonce: bbdc_ajax_object.nonce, id: id }, function(response) {
            spinner.remove();
            button.parent().find('.button').prop('disabled', false);
            if (response.success) {
                row.css('background-color', '#d4edda').fadeOut(1000, function() { $(this).remove(); });
                if(action === 'bbdc_approve_volunteer') {
                    alert(response.data);
                }
            } else { alert('Error: ' + response.data); }
        });
    }

    $('.wp-list-table').on('click', '.approve-donation', function(e) { e.preventDefault(); handleAjaxAction($(this), 'bbdc_approve_donation', 'Are you sure you want to approve this?'); });
    $('.wp-list-table').on('click', '.reject-donation, .delete-history', function(e) { e.preventDefault(); handleAjaxAction($(this), 'bbdc_reject_donation', 'Are you sure? This cannot be undone.'); });
    $('.wp-list-table').on('click', '.delete-donor', function(e) { e.preventDefault(); handleAjaxAction($(this), 'bbdc_delete_donor', 'Are you sure you want to delete this donor PERMANENTLY? All their history will also be removed.'); });
    $('.wp-list-table').on('click', '.approve-volunteer', function(e) { e.preventDefault(); handleAjaxAction($(this), 'bbdc_approve_volunteer', 'Are you sure you want to approve this volunteer?'); });
    $('.wp-list-table').on('click', '.reject-volunteer', function(e) { e.preventDefault(); handleAjaxAction($(this), 'bbdc_reject_volunteer', 'Are you sure you want to delete this user permanently?'); });
    $('.wp-list-table').on('click', '.delete-patient', function(e) { e.preventDefault(); handleAjaxAction($(this), 'bbdc_delete_patient', 'Are you sure you want to delete this patient record?'); });

    $('.wp-list-table').on('click', '.send-greeting-sms', function(e) {
        e.preventDefault();
        const button = $(this); const mobile = button.data('mobile'); const name = button.data('name'); const originalText = button.text();
        button.text('Sending...').prop('disabled', true);
        $.post(bbdc_ajax_object.ajax_url, { action: 'bbdc_send_greeting_sms', nonce: bbdc_ajax_object.nonce, mobile: mobile, name: name }, function(response) {
            alert(response.success ? response.data : 'Error: ' + response.data);
            button.text(originalText).prop('disabled', false);
        });
    });

    $('.wp-list-table').on('click', '.save-volunteer-role', function(e) {
        e.preventDefault();
        const button = $(this);
        const cell = button.closest('.role-management-cell');
        const spinner = cell.find('.spinner');
        const userId = cell.data('user-id');
        
        const newRoles = cell.find('input[name="volunteer_roles[]"]:checked').map(function() {
            return this.value;
        }).get();

        spinner.addClass('is-active');
        button.prop('disabled', true);
        
        $.post(bbdc_ajax_object.ajax_url, { 
            action: 'bbdc_change_volunteer_role', 
            nonce: bbdc_ajax_object.nonce, 
            user_id: userId, 
            new_roles: newRoles
        }, function(response) {
            spinner.removeClass('is-active');
            button.prop('disabled', false);
            if (response.success) {
                button.text('Saved!');
                setTimeout(function() { button.text('Save Roles'); }, 2000);
            } else { 
                alert('Error: ' + response.data); 
            }
        });
    });

    $('.bbdc-datepicker').datepicker({ dateFormat: "yy-mm-dd" });
    
    $('body').append(`
        <div id="patient-details-modal" style="display:none;">
            <div class="modal-content">
                <span class="close-button">&times;</span>
                <h2>Patient Details</h2>
                <div id="modal-body-content"></div>
            </div>
        </div>
    `);

    const modal = $('#patient-details-modal');
    const modalBody = $('#modal-body-content');

    $('.wp-list-table').on('click', '.view-patient-details', function(e) {
        e.preventDefault();
        const patientId = $(this).data('id');

        modalBody.html('<p>Loading...</p>');
        modal.show();

        $.post(bbdc_ajax_object.ajax_url, {
            action: 'bbdc_get_patient_details',
            nonce: bbdc_ajax_object.nonce,
            id: patientId
        }, function(response) {
            if (response.success) {
                const data = response.data;
                let detailsHtml = `
                    <div class="patient-details-grid">
                        <div class="patient-photo">
                            ${data.image_url ? `<img src="${data.image_url}" alt="${data.patient_name}" />` : '<span class="no-photo">No Photo</span>'}
                        </div>
                        <div class="patient-info">
                            <table class="wp-list-table widefat striped">
                                <tbody>
                                    <tr><td><strong>নাম</strong></td><td>${data.patient_name || ''}</td></tr>
                                    <tr><td><strong>মোবাইল</strong></td><td>${data.mobile_number || ''}</td></tr>
                                    <tr><td><strong>বাবার নাম</strong></td><td>${data.father_name || ''}</td></tr>
                                    <tr><td><strong>মায়ের নাম</strong></td><td>${data.mother_name || ''}</td></tr>
                                    <tr><td><strong>বয়স</strong></td><td>${data.age || ''}</td></tr>
                                    <tr><td><strong>ঠিকানা</strong></td><td>${data.address || ''}</td></tr>
                                    <tr><td><strong>পেশা</strong></td><td>${data.occupation || ''}</td></tr>
                                    <tr><td><strong>অবিভাবকের পেশা</strong></td><td>${data.guardian_occupation || ''}</td></tr>
                                    <tr><td><strong>রোগ</strong></td><td>${data.disease || ''}</td></tr>
                                    <tr><td><strong>রক্তের গ্রুপ</strong></td><td>${data.blood_group || ''}</td></tr>
                                    <tr><td><strong>মাসিক রক্তের প্রয়োজন</strong></td><td>${data.monthly_blood_need || ''} ব্যাগ</td></tr>
                                    <tr><td><strong>অন্যান্য তথ্য</strong></td><td>${data.other_info || ''}</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
                modalBody.html(detailsHtml);
            } else {
                modalBody.html(`<p>Error: ${response.data.message}</p>`);
            }
        });
    });

    modal.on('click', '.close-button', function() {
        modal.hide();
    });

    $(window).on('click', function(event) {
        if ($(event.target).is(modal)) {
            modal.hide();
        }
    });

function updateDonorsFilterLink() {
        const baseUrl = window.location.href.split('?')[0];
        const params = new URLSearchParams();
        
        const pageParam = new URLSearchParams(window.location.search).get('page');
        if (pageParam) {
            params.append('page', pageParam);
        }

        const searchQuery = $('input[name="s"]').val();
        const bloodGroup = $('select[name="blood_group"]').val();
        
        if (searchQuery) params.append('s', searchQuery);
        if (bloodGroup) params.append('blood_group', bloodGroup);

        $('#bbdc-donors-filter-link').attr('href', baseUrl + '?' + params.toString());
    }
    
    if ($('#bbdc-donors-filter-link').length) {
        updateDonorsFilterLink();
        $('.actions input, .actions select').on('change keyup', updateDonorsFilterLink);
    }
});