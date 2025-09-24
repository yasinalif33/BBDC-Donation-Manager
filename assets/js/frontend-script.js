jQuery(document).ready(function($) {
    // Logic for the donation form's conditional referrer field
    const donationForm = $("#bbdc-donation-form");
    if (donationForm.length) {
        const isBbdcCheckbox = $('input[name="is_bbdc_donation"]');
        const referrerField = $("#referrer-field");
        const toggleReferrerField = function() {
            if (isBbdcCheckbox.is(":checked")) {
                referrerField.slideDown();
                referrerField.find("select").prop("required", true);
            } else {
                referrerField.slideUp();
                referrerField.find("select").prop("required", false);
            }
        };
        toggleReferrerField();
        isBbdcCheckbox.on("change", toggleReferrerField);
    }

    // Logic for the Login/Register tabs
    const authWrapper = $(".bbdc-auth-wrapper");
    if (authWrapper.length) {
        authWrapper.on("click", ".tab-link", function(e) {
            e.preventDefault();
            const tabId = $(this).data("tab");
            authWrapper.find(".tab-link").removeClass("active");
            $(this).addClass("active");
            authWrapper.find(".bbdc-tab-content").removeClass("active");
            $("#bbdc-" + tabId).addClass("active");
        });
    }

    // **FIXED**: Logic for the "My Activity" tabs on the profile page
    const activityWrapper = $('.bbdc-activity-wrapper');
    if (activityWrapper.length) {
        activityWrapper.on('click', '.tab-link', function(e) {
            e.preventDefault();
            const tabId = $(this).data('tab');

            // Handle active classes for tabs
            activityWrapper.find('.tab-link').removeClass('active');
            $(this).addClass('active');

            // Handle active classes for content panes
            activityWrapper.find('.bbdc-tab-content').removeClass('active');
            activityWrapper.find('#bbdc-' + tabId).addClass('active');
        });
    }

    // English-only validation for all relevant forms
    const allForms = $("#bbdc-donation-form, #bbdc-registration-form, .bbdc-profile-wrapper");
    if (allForms.length) {
        allForms.on("submit", function(e) {
            let hasError = false;
            const englishOnlyFields = $(this).find("#donor_name, #donor_location, #bbdc_username, #first_name, #last_name, #father_name, #mother_name");
            const englishRegex = /^[a-zA-Z0-9\s.,'-]*$/;
            englishOnlyFields.each(function() {
                const field = $(this);
                const label = field.closest("p").find("label").text().replace(" *", "");
                if (field.val() && !englishRegex.test(field.val())) {
                    alert(label + " must contain English letters and numbers only.");
                    hasError = true;
                    return false; // break the loop
                }
            });
            if (hasError) e.preventDefault();
        });
    }
    
    const docTypeSelector = $('#bbdc_document_type');
    const nidFields = $('#bbdc-nid-fields');
    const birthCertField = $('#bbdc-birth-cert-field');

    function toggleDocumentFields() {
        const selectedType = docTypeSelector.val();
        if (selectedType === 'nid') {
            nidFields.slideDown();
            birthCertField.slideUp();
            nidFields.find('input').prop('required', true);
            birthCertField.find('input').prop('required', false);
        } else if (selectedType === 'birth_certificate') {
            nidFields.slideUp();
            birthCertField.slideDown();
            nidFields.find('input').prop('required', false);
            birthCertField.find('input').prop('required', true);
        } else {
            nidFields.slideUp();
            birthCertField.slideUp();
            nidFields.find('input').prop('required', false);
            birthCertField.find('input').prop('required', false);
        }
    }

    if (docTypeSelector.length) {
        toggleDocumentFields();
        docTypeSelector.on('change', toggleDocumentFields);
    }
});