<?php
if (!defined('ABSPATH')) exit;

class BBDC_DM_Frontend_Form {

    public function __construct() {
        add_shortcode('bbdc_donation_form', [$this, 'render_donation_form']);
        add_shortcode('bbdc_auth_form', [$this, 'render_auth_form']);
        add_shortcode('bbdc_volunteer_profile', [$this, 'render_volunteer_profile']);
        add_shortcode('bbdc_patient_form', [$this, 'render_patient_form']);
        add_shortcode('bbdc_app_landing_page', [$this, 'render_app_landing_page']);
        add_action('init', [$this, 'handle_all_submissions']);
    }

    public function handle_all_submissions() {
        if (isset($_POST['submit_donation'])) $this->handle_donation_submission();
        if (isset($_POST['submit_registration'])) $this->handle_registration_submission();
        if (isset($_POST['submit_login'])) $this->handle_login_submission();
        if (isset($_POST['update_volunteer_profile'])) $this->handle_profile_update();
        if (isset($_POST['submit_patient_data'])) $this->handle_patient_submission();
    }
    
    public function render_donation_form() {
        if (!is_user_logged_in()) {
            wp_redirect(home_url('/volunteer-area/'));
            exit;
        }
        $user = wp_get_current_user();
        $is_admin = current_user_can('manage_options');
        $approval_status = get_user_meta($user->ID, 'bbdc_approval_status', true);

        if (!$is_admin && $approval_status !== 'approved') {
            return '<p class="bbdc-message">Your volunteer account is currently pending approval. You will be able to submit records once an administrator approves your account.</p>';
        }
        
        ob_start();

        if (isset($_GET['submission'])) {
            if ($_GET['submission'] == 'success') echo '<p class="bbdc-success">Thank you! Your donation record has been submitted and is pending review.</p>';
            if ($_GET['submission'] == 'error') echo '<p class="bbdc-error">An error occurred. Please try again.</p>';
        }

        global $wpdb;
        $manual_referrers_query = $wpdb->get_results("SELECT referrer_name FROM {$wpdb->prefix}bbdc_referrers ORDER BY referrer_name ASC");
        $manual_referrers = wp_list_pluck($manual_referrers_query, 'referrer_name');
        $volunteer_users = get_users(['role__in' => ['volunteer', 'bbdc_admin', 'administrator'], 'fields' => ['display_name']]);
        $volunteer_referrers = wp_list_pluck($volunteer_users, 'display_name');
        $all_referrers = array_unique(array_merge($manual_referrers, $volunteer_referrers));
        sort($all_referrers);

        ?>
        <form id="bbdc-donation-form" class="bbdc-form" method="post" action="">
            <input type="hidden" name="action" value="bbdc_submit_donation">
            <?php wp_nonce_field('bbdc_donation_nonce', 'bbdc_nonce'); ?>
            <p><label for="donor_name">Donor Name *</label><input type="text" id="donor_name" name="donor_name" required></p>
            <p><label for="donor_mobile">Mobile Number * (11 digits)</label><input type="text" id="donor_mobile" name="donor_mobile" required pattern="[0-9]{11}" maxlength="11"></p>
            <p><label for="blood_group">Blood Group *</label><select id="blood_group" name="blood_group" required><option value="">Select</option><option value="A+">A+</option><option value="A-">A-</option><option value="B+">B+</option><option value="B-">B-</option><option value="AB+">AB+</option><option value="AB-">AB-</option><option value="O+">O+</option><option value="O-">O-</option></select></p>
            <p><label for="donation_date">Donation Date *</label><input type="date" id="donation_date" name="donation_date" required></p>
            <p><label for="donor_location">Donor Location *</label><input type="text" id="donor_location" name="donor_location" required></p>
            <p><label for="age">Age (Optional)</label><input type="number" id="age" name="age"></p>
            <p><label for="patient_problem">Patient's Problem (Optional)</label><textarea id="patient_problem" name="patient_problem"></textarea></p>
            <p><label for="patient_contact">Patient's Contact (Optional)</label><input type="text" id="patient_contact" name="patient_contact" pattern="[0-9]{11}" maxlength="11"></p>
            <p><label><input type="checkbox" name="is_bbdc_donation" value="1" checked> This donation is for BBDC.</label></p>
            <div id="referrer-field">
                <label for="referrer_name">Referrer *</label>
                <select name="referrer_name" required>
                    <option value="">Select Referrer</option>
                    <?php foreach ($all_referrers as $referrer_name) : ?>
                        <option value="<?php echo esc_attr($referrer_name); ?>"><?php echo esc_html($referrer_name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <p><input type="submit" name="submit_donation" value="Submit Record"></p>
        </form>
        <?php
        return ob_get_clean();
    }

    public function handle_donation_submission() {
        if (!isset($_POST['submit_donation']) || !isset($_POST['bbdc_nonce']) || !wp_verify_nonce($_POST['bbdc_nonce'], 'bbdc_donation_nonce')) return;
        
        global $wpdb;
        $history_table = $wpdb->prefix . 'bbdc_donation_history'; 
        
        $form_data_raw = [];
        foreach ($_POST as $key => $value) {
            if ($key !== 'bbdc_nonce' && $key !== '_wp_http_referer' && $key !== 'action' && $key !== 'submit_donation') {
                $form_data_raw[$key] = sanitize_text_field($value);
            }
        }
        
        $history_data = [
            'donor_name' => sanitize_text_field($_POST['donor_name']), 
            'donor_mobile' => sanitize_text_field($_POST['donor_mobile']), 
            'blood_group' => sanitize_text_field($_POST['blood_group']),
            'donation_date' => sanitize_text_field($_POST['donation_date']), 
            'is_bbdc_donation' => !empty($_POST['is_bbdc_donation']) ? 1 : 0, 
            'referrer_name' => !empty($_POST['is_bbdc_donation']) ? sanitize_text_field($_POST['referrer_name']) : null, 
            'status' => 'pending', 
            'form_data' => json_encode($form_data_raw),
            'submitted_by_user_id' => get_current_user_id(),
        ];
        
        $result = $wpdb->insert($history_table, $history_data);

        if ($result) {
            $new_history_id = $wpdb->insert_id;
            bbdc_send_new_pending_donation_notification($new_history_id);
        }
        
        wp_redirect(add_query_arg('submission', $result ? 'success' : 'error', wp_get_referer())); 
        exit;
    }

    public function render_auth_form() {
        if (is_user_logged_in()) {
            wp_redirect(home_url('/my-profile/'));
            exit;
        }
        ob_start();
        $site_key = get_option('bbdc_recaptcha_site_key');
        ?>
        <div class="bbdc-auth-wrapper">
            <div class="bbdc-auth-tabs"><button class="tab-link active" data-tab="login">Login</button><button class="tab-link" data-tab="register">Register</button></div>
            <div id="bbdc-login" class="bbdc-tab-content active">
                <?php if (isset($_GET['login_error'])) echo '<p class="bbdc-error">Login failed. Please check your credentials.</p>'; ?>
                <form id="bbdc-login-form" class="bbdc-form" method="post" action="">
                    <input type="hidden" name="action" value="bbdc_login_user">
                    <?php wp_nonce_field('bbdc_login_nonce', 'bbdc_nonce'); ?>
                    <p><label for="bbdc_log">Username or Email *</label><input type="text" id="bbdc_log" name="log" required></p>
                    <p><label for="bbdc_pwd">Password *</label><input type="password" id="bbdc_pwd" name="pwd" required></p>
                    <p><label><input name="rememberme" type="checkbox" id="rememberme" value="forever"> Remember Me</label></p>
                    <p><input type="submit" name="submit_login" value="Login"></p>
                    <p class="bbdc-lost-password"><a href="<?php echo esc_url(home_url('/reset-password/')); ?>">Lost your password?</a></p>
                </form>
            </div>
            <div id="bbdc-register" class="bbdc-tab-content">
                <?php 
                if (isset($_GET['reg_error'])) {
                    $error_messages = ['empty_fields' => 'Please fill in all required fields.', 'password_mismatch' => 'The passwords do not match.', 'email_exists' => 'This email is already registered.', 'username_exists' => 'This username is already taken.', 'mobile_exists' => 'This mobile number is already registered.', 'consent_required' => 'You must agree to the terms to register.', 'upload_failed' => 'File upload failed. Please try again.', 'recaptcha_failed' => 'reCAPTCHA verification failed. Please try again.'];
                    echo '<p class="bbdc-error">' . ($error_messages[sanitize_key($_GET['reg_error'])] ?? 'An unknown error occurred.') . '</p>';
                }
                if (isset($_GET['registration']) && $_GET['registration'] === 'success') echo '<p class="bbdc-success">Registration successful! Your account is now pending review.</p>';
                ?>
                <form id="bbdc-registration-form" class="bbdc-form" method="post" action="" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="bbdc_register_volunteer">
                    <?php wp_nonce_field('bbdc_auth_nonce', 'bbdc_nonce'); ?>
                    <p class="bbdc-honeypot" style="display:none !important;" aria-hidden="true"><label for="bbdc_website">Website</label><input type="text" name="bbdc_website" id="bbdc_website" autocomplete="off" tabindex="-1"></p>
                    <p><label for="bbdc_full_name">Full Name *</label><input type="text" id="bbdc_full_name" name="bbdc_full_name" required></p>
                    <div class="form-row"><p class="form-half"><label for="bbdc_father_name">Father’s Name *</label><input type="text" id="bbdc_father_name" name="bbdc_father_name" required></p><p class="form-half"><label for="bbdc_mother_name">Mother’s Name *</label><input type="text" id="bbdc_mother_name" name="bbdc_mother_name" required></p></div>
                    <div class="form-row"><p class="form-half"><label for="bbdc_birth_date">Birth Date *</label><input type="date" id="bbdc_birth_date" name="bbdc_birth_date" required></p><p class="form-half"><label for="bbdc_blood_group">Blood Group *</label><select id="bbdc_blood_group" name="bbdc_blood_group" required><option value="">Select</option><option value="A+">A+</option><option value="A-">A-</option><option value="B+">B+</option><option value="B-">B-</option><option value="AB+">AB+</option><option value="AB-">AB-</option><option value="O+">O+</option><option value="O-">O-</option></select></p></div>
                    <p><label for="bbdc_mobile">Phone Number *</label><input type="text" id="bbdc_mobile" name="bbdc_mobile" required pattern="[0-9]{11}" maxlength="11"></p>
                    <div class="form-row"><p class="form-half"><label for="bbdc_alt_phone">Alternative Phone Number *</label><input type="text" id="bbdc_alt_phone" name="bbdc_alt_phone" required pattern="[0-9]{11}" maxlength="11"></p><p class="form-half"><label for="bbdc_guardian_phone">Phone Number (Guardian) *</label><input type="text" id="bbdc_guardian_phone" name="bbdc_guardian_phone" required pattern="[0-9]{11}" maxlength="11"></p></div>
                    <p><label for="bbdc_email">Email *</label><input type="email" id="bbdc_email" name="bbdc_email" required></p>
                    <p><label for="bbdc_fb_link">Facebook Profile Link *</label><input type="url" id="bbdc_fb_link" name="bbdc_fb_link" required></p>
                    <p><label for="bbdc_present_address">Present Address *</label><textarea id="bbdc_present_address" name="bbdc_present_address" required></textarea></p>
                    <p><label for="bbdc_permanent_address">Permanent Address *</label><textarea id="bbdc_permanent_address" name="bbdc_permanent_address" required></textarea></p>
                    <p><label for="bbdc_education">Running/Last Educational Institution *</label><input type="text" id="bbdc_education" name="bbdc_education" required></p>
                    <div class="form-row">
                        <p class="form-half"><label for="bbdc_class_dept">Class/Department *</label><input type="text" id="bbdc_class_dept" name="bbdc_class_dept" required></p>
                        <p class="form-half"><label for="bbdc_semester_year">Semester/Year *</label><input type="text" id="bbdc_semester_year" name="bbdc_semester_year" required></p>
                    </div>
                    <div class="form-row">
                        <p class="form-half"><label for="bbdc_joining_date">Joining Date *</label><input type="date" id="bbdc_joining_date" name="bbdc_joining_date" required></p>
                        <p class="form-half"><label for="bbdc_training">Session *</label><input type="text" id="bbdc_training" name="bbdc_training" required></p>
                    </div>
                    <p><label for="bbdc_skills">Special Abilities/Skills *</label><textarea id="bbdc_skills" name="bbdc_skills" required></textarea></p>
                    <p><label for="bbdc_referer">Introducer/Referer Name *</label><input type="text" id="bbdc_referer" name="bbdc_referer" required></p>
                    <p><label for="bbdc_nid_no">NID / Birth Certificate No *</label><input type="text" id="bbdc_nid_no" name="bbdc_nid_no" required></p>
                    <p>
                        <label for="bbdc_document_type">Document Type *</label>
                        <select id="bbdc_document_type" name="bbdc_document_type" required>
                            <option value="">-- Select Document Type --</option>
                            <option value="nid">NID Card</option>
                            <option value="birth_certificate">Birth Certificate</option>
                        </select>
                    </p>
                    <p>
                        <label for="bbdc_photo">Your Photo *</label>
                        <input type="file" id="bbdc_photo" name="bbdc_photo" required accept="image/jpeg,image/png">
                    </p>
                    <div id="bbdc-nid-fields" class="form-row" style="display: none;">
                        <p class="form-half">
                            <label for="bbdc_nid_front">NID (Front Side) *</label>
                            <input type="file" id="bbdc_nid_front" name="bbdc_nid_front" accept="image/jpeg,image/png,application/pdf">
                        </p>
                        <p class="form-half">
                            <label for="bbdc_nid_back">NID (Back Side) *</label>
                            <input type="file" id="bbdc_nid_back" name="bbdc_nid_back" accept="image/jpeg,image/png,application/pdf">
                        </p>
                    </div>
                    <div id="bbdc-birth-cert-field" style="display: none;">
                        <p>
                            <label for="bbdc_birth_cert_scan">Scan Copy of Birth Certificate *</label>
                            <input type="file" id="bbdc_birth_cert_scan" name="bbdc_birth_cert_scan" accept="image/jpeg,image/png,application/pdf">
                        </p>
                    </div>
                    <hr>
                    <p><label for="bbdc_username">Username *</label><input type="text" id="bbdc_username" name="bbdc_username" required></p>
                    <p><label for="bbdc_password">Password *</label><input type="password" id="bbdc_password" name="bbdc_password" required></p>
                    <p><label for="bbdc_password_confirm">Confirm Password *</label><input type="password" id="bbdc_password_confirm" name="bbdc_password_confirm" required></p>
                    <p class="consent-field">
                        <label for="bbdc_consent">
                            <input type="checkbox" id="bbdc_consent" name="bbdc_consent" required> আমি সম্মতি প্রদান করছি *
                        </label>
                        <small class="consent-text">আমার প্রদানকৃত সকল তথ্য সঠিক এবং আমি বিবিডিসি'র সকল নিয়ম কানুন মানতে বাধ্য থাকিব।</small>
                    </p>
                    <?php if (!empty($site_key)): ?>
                        <p class="recaptcha-field">
                            <div class="g-recaptcha" data-sitekey="<?php echo esc_attr($site_key); ?>"></div>
                        </p>
                    <?php endif; ?>
                    <p><input type="submit" name="submit_registration" value="Register"></p>
                </form>
            </div>
        </div>
        <?php if (!empty($site_key)): ?>
            <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        <?php endif; ?>
        <?php
        return ob_get_clean();
    }
    
    public function handle_login_submission() {
        if (!isset($_POST['submit_login']) || !isset($_POST['bbdc_nonce']) || !wp_verify_nonce($_POST['bbdc_nonce'], 'bbdc_login_nonce')) return;
        $creds = ['user_login' => sanitize_user($_POST['log']), 'user_password' => $_POST['pwd'], 'remember' => isset($_POST['rememberme'])];
        $user = wp_signon($creds, false);
        if (is_wp_error($user)) {
            wp_redirect(add_query_arg('login_error', 'failed', wp_get_referer())); exit;
        } else {
            wp_redirect(home_url('/my-profile/')); exit;
        }
    }

    public function handle_registration_submission() {
        if (!isset($_POST['submit_registration']) || !isset($_POST['bbdc_nonce']) || !wp_verify_nonce($_POST['bbdc_nonce'], 'bbdc_auth_nonce')) return;
        
        $secret_key = get_option('bbdc_recaptcha_secret_key');
        $redirect_url = strtok($_SERVER['REQUEST_URI'], '?');

        if (!empty($secret_key)) {
            if (empty($_POST['g-recaptcha-response'])) { wp_redirect(add_query_arg('reg_error', 'recaptcha_failed', $redirect_url)); exit; }
            $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', ['body' => ['secret' => $secret_key, 'response' => $_POST['g-recaptcha-response'], 'remoteip' => $_SERVER['REMOTE_ADDR'],]]);
            if (is_wp_error($response)) { wp_redirect(add_query_arg('reg_error', 'recaptcha_failed', $redirect_url)); exit; }
            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (!$body['success']) { wp_redirect(add_query_arg('reg_error', 'recaptcha_failed', $redirect_url)); exit; }
        }
        
        if (!empty($_POST['bbdc_website'])) { wp_die('Spam submission detected.'); exit; }

$required_fields = ['bbdc_full_name', 'bbdc_father_name', 'bbdc_mother_name', 'bbdc_birth_date', 'bbdc_blood_group', 'bbdc_mobile', 'bbdc_alt_phone', 'bbdc_guardian_phone', 'bbdc_email', 'bbdc_fb_link', 'bbdc_present_address', 'bbdc_permanent_address', 'bbdc_education', 'bbdc_class_dept', 'bbdc_semester_year', 'bbdc_training', 'bbdc_joining_date', 'bbdc_skills', 'bbdc_referer', 'bbdc_nid_no', 'bbdc_username', 'bbdc_password', 'bbdc_password_confirm', 'bbdc_document_type'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) { wp_redirect(add_query_arg('reg_error', 'empty_fields', $redirect_url)); exit; }
        }
        if (empty($_POST['bbdc_consent'])) { wp_redirect(add_query_arg('reg_error', 'consent_required', $redirect_url)); exit; }
        if ($_POST['bbdc_password'] !== $_POST['bbdc_password_confirm']) { wp_redirect(add_query_arg('reg_error', 'password_mismatch', $redirect_url)); exit; }

        $email = sanitize_email($_POST['bbdc_email']);
        $username = sanitize_user($_POST['bbdc_username']);
        $mobile = sanitize_text_field($_POST['bbdc_mobile']);
        if (username_exists($username)) { wp_redirect(add_query_arg('reg_error', 'username_exists', $redirect_url)); exit; }
        if (email_exists($email)) { wp_redirect(add_query_arg('reg_error', 'email_exists', $redirect_url)); exit; }
        if (!empty(get_users(['meta_key' => 'bbdc_mobile_number', 'meta_value' => $mobile]))) { wp_redirect(add_query_arg('reg_error', 'mobile_exists', $redirect_url)); exit; }

        if (!function_exists('bbdc_dm_handle_file_upload')) {
            function bbdc_dm_handle_file_upload($file) {
                if (!function_exists('wp_handle_upload')) require_once(ABSPATH . 'wp-admin/includes/file.php');
                $uploadedfile = $file;
                $upload_overrides = ['test_form' => false];
                $movefile = wp_handle_upload($uploadedfile, $upload_overrides);
                return $movefile && !isset($movefile['error']) ? $movefile['url'] : '';
            }
        }

        $profile_pic_url = bbdc_dm_handle_file_upload($_FILES['bbdc_photo']);
        $doc_type = sanitize_text_field($_POST['bbdc_document_type']);
        $doc_front_url = '';
        $doc_back_url = '';

        if ($doc_type === 'nid') {
            if (empty($_FILES['bbdc_nid_front']['name']) || empty($_FILES['bbdc_nid_back']['name'])) { wp_redirect(add_query_arg('reg_error', 'empty_files', $redirect_url)); exit; }
            $doc_front_url = bbdc_dm_handle_file_upload($_FILES['bbdc_nid_front']);
            $doc_back_url = bbdc_dm_handle_file_upload($_FILES['bbdc_nid_back']);
        } elseif ($doc_type === 'birth_certificate') {
            if (empty($_FILES['bbdc_birth_cert_scan']['name'])) { wp_redirect(add_query_arg('reg_error', 'empty_files', $redirect_url)); exit; }
            $doc_front_url = bbdc_dm_handle_file_upload($_FILES['bbdc_birth_cert_scan']);
        }
        if (empty($profile_pic_url) || empty($doc_front_url)) { wp_redirect(add_query_arg('reg_error', 'upload_failed', $redirect_url)); exit; }

        $full_name = sanitize_text_field($_POST['bbdc_full_name']);
        $name_parts = explode(' ', $full_name, 2);
        $userdata = ['user_login' => $username, 'user_email' => $email, 'user_pass'  => $_POST['bbdc_password'], 'first_name' => $name_parts[0], 'last_name'  => $name_parts[1] ?? '', 'display_name' => $full_name, 'role' => 'volunteer'];
        $user_id = wp_insert_user($userdata);
        if (is_wp_error($user_id)) { wp_redirect(add_query_arg('reg_error', 'unknown', $redirect_url)); exit; }

        update_user_meta($user_id, 'billing_phone', $mobile);
        update_user_meta($user_id, 'bbdc_approval_status', 'pending');
        update_user_meta($user_id, 'bbdc_mobile_number', $mobile);
        
        $meta_keys = [
            'father_name', 'mother_name', 'birth_date', 'blood_group', 'alt_phone', 
            'guardian_phone', 'fb_link', 'present_address', 'permanent_address', 
            'education', 'class_dept', 'semester_year', 'training_session', 
            'skills', 'referer', 'nid_number', 'joining_date'
        ];

        $post_key_map = [
            'training_session' => 'bbdc_training',
            'nid_number' => 'bbdc_nid_no',
            'joining_date' => 'bbdc_joining_date',
            'referer' => 'bbdc_referer',
            'mobile' => 'bbdc_mobile',
        ];

        foreach ($meta_keys as $meta_key) {
            $post_key = isset($post_key_map[$meta_key]) ? $post_key_map[$meta_key] : 'bbdc_' . $meta_key;
            if (isset($_POST[$post_key])) {
                $value = in_array($meta_key, ['present_address', 'permanent_address', 'skills']) 
                    ? sanitize_textarea_field($_POST[$post_key]) 
                    : sanitize_text_field($_POST[$post_key]);
                update_user_meta($user_id, $meta_key, $value);
            }
        }
        
        update_user_meta($user_id, 'profile_pic_url', esc_url_raw($profile_pic_url));
        update_user_meta($user_id, 'document_type', $doc_type);
        update_user_meta($user_id, 'nid_front_url', esc_url_raw($doc_front_url));
        update_user_meta($user_id, 'nid_back_url', esc_url_raw($doc_back_url));
        
        wp_redirect(add_query_arg('registration', 'success', $redirect_url));
        exit;
    }

    public function render_volunteer_profile() {
        if (!is_user_logged_in()) { wp_redirect(home_url('/volunteer-area/')); exit; }
        $user = wp_get_current_user();
        $user_id = $user->ID;
        $is_admin = current_user_can('manage_options');
        $status = get_user_meta($user_id, 'bbdc_approval_status', true);
        if (!$is_admin && $status !== 'approved') {
             return '<p class="bbdc-message">You do not have permission to view this page until your account is approved.</p>';
        }
        
        $meta = get_user_meta($user_id);
        $get_meta = function($key) use ($meta) { return $meta[$key][0] ?? ''; };
        
        ob_start();
        if (isset($_GET['profile_updated'])) echo '<p class="bbdc-success">Profile updated successfully!</p>';
        if (isset($_GET['profile_error'])) echo '<p class="bbdc-error">An error occurred during file upload. Please try again.</p>';
        ?>
        <div class="bbdc-form bbdc-profile-wrapper">
            <h2>My Profile</h2>
            <?php if ($status === 'pending' && !$is_admin): ?><p class="bbdc-message">Your account is pending approval.</p><?php endif; ?>
            <form method="post" enctype="multipart/form-data" action="">
                <?php wp_nonce_field('bbdc_update_profile_nonce', 'bbdc_nonce'); ?>
                
                <div class="profile-pic-area">
                    <img src="<?php echo esc_url($get_meta('profile_pic_url') ?: get_avatar_url($user_id)); ?>" alt="Profile Picture" class="profile-pic-preview">
                    <p><label for="bbdc_photo">Profile Picture</label><input type="file" name="bbdc_photo" id="bbdc_photo"></p>
                </div>

                <p><label>Full Name</label><input type="text" name="display_name" value="<?php echo esc_attr($user->display_name); ?>"></p>
                <p><label>Father’s Name</label><input type="text" name="father_name" value="<?php echo esc_attr($get_meta('father_name')); ?>" <?php if(!$is_admin) echo 'disabled'; ?>></p>
                <p><label>Mother’s Name</label><input type="text" name="mother_name" value="<?php echo esc_attr($get_meta('mother_name')); ?>" <?php if(!$is_admin) echo 'disabled'; ?>></p>
                <p><label>Birth Date</label><input type="date" name="birth_date" value="<?php echo esc_attr($get_meta('birth_date')); ?>" <?php if(!$is_admin) echo 'disabled'; ?>></p>
                <p><label>Blood Group</label><input type="text" name="blood_group" value="<?php echo esc_attr($get_meta('blood_group')); ?>" disabled></p>
                <p><label>Phone Number</label><input type="text" name="bbdc_mobile_number" value="<?php echo esc_attr($get_meta('bbdc_mobile_number')); ?>" disabled></p>
                <p><label>Alternative Phone Number</label><input type="text" name="alt_phone" value="<?php echo esc_attr($get_meta('alt_phone')); ?>"></p>
                <p><label>Phone Number (Guardian)</label><input type="text" name="guardian_phone" value="<?php echo esc_attr($get_meta('guardian_phone')); ?>"></p>
                <p><label>Email</label><input type="email" name="email" value="<?php echo esc_attr($user->user_email); ?>"></p>
                <p><label>Facebook Profile Link</label><input type="url" name="fb_link" value="<?php echo esc_attr($get_meta('fb_link')); ?>"></p>
                <p><label>Present Address</label><textarea name="present_address" rows="3"><?php echo esc_textarea($get_meta('present_address')); ?></textarea></p>
                <p><label>Permanent Address</label><textarea name="permanent_address" rows="3" <?php if(!$is_admin) echo 'disabled'; ?>><?php echo esc_textarea($get_meta('permanent_address')); ?></textarea></p>
                <p><label>Running/Last Educational Institution</label><input type="text" name="education" value="<?php echo esc_attr($get_meta('education')); ?>"></p>
                <p><label>Class/Department</label><input type="text" name="class_dept" value="<?php echo esc_attr($get_meta('class_dept')); ?>"></p>
                <p><label>Semester/Year</label><input type="text" name="semester_year" value="<?php echo esc_attr($get_meta('semester_year')); ?>"></p>
                <p><label>Training Session</label><input type="text" name="training_session" value="<?php echo esc_attr($get_meta('training_session')); ?>" <?php if(!$is_admin) echo 'disabled'; ?>></p>
                <p><label>Special Abilities/Skills</label><textarea name="skills" rows="3"><?php echo esc_textarea($get_meta('skills')); ?></textarea></p>
                <p><label>Introducer/Referer Name</label><input type="text" name="referer" value="<?php echo esc_attr($get_meta('referer')); ?>" <?php if(!$is_admin) echo 'disabled'; ?>></p>
                <p><label>NID/Birth Certificate No</label><input type="text" name="nid_number" value="<?php echo esc_attr($get_meta('nid_number')); ?>" <?php if(!$is_admin) echo 'disabled'; ?>></p>
                
                <div class="nid-pic-area">
                    <?php 
                    $doc_type = $get_meta('document_type');
                    if ($doc_type === 'nid'): ?>
                        <p>Current NID (Front): <?php if($get_meta('nid_front_url')): ?><a href="<?php echo esc_url($get_meta('nid_front_url')); ?>" target="_blank">View File</a><?php else: ?>Not Uploaded<?php endif; ?></p>
                        <p><label>Update NID (Front)</label><input type="file" name="nid_front"></p>
                        <p>Current NID (Back): <?php if($get_meta('nid_back_url')): ?><a href="<?php echo esc_url($get_meta('nid_back_url')); ?>" target="_blank">View File</a><?php else: ?>Not Uploaded<?php endif; ?></p>
                        <p><label>Update NID (Back)</label><input type="file" name="nid_back"></p>
                    <?php elseif($doc_type === 'birth_certificate'): ?>
                        <p>Current Birth Certificate: <?php if($get_meta('nid_front_url')): ?><a href="<?php echo esc_url($get_meta('nid_front_url')); ?>" target="_blank">View File</a><?php else: ?>Not Uploaded<?php endif; ?></p>
                        <p><label>Update Birth Certificate</label><input type="file" name="birth_cert_scan"></p>
                    <?php endif; ?>
                </div>

                <p><input type="submit" name="update_volunteer_profile" value="Update Profile"></p>
            </form>
        </div>
        <?php
        $output = ob_get_clean();
        
        if ($status === 'approved' || $is_admin) {
             global $wpdb;
        }
        return $output;
    }
    
    public function handle_profile_update() {
        if (!isset($_POST['update_volunteer_profile']) || !isset($_POST['bbdc_nonce']) || !wp_verify_nonce($_POST['bbdc_nonce'], 'bbdc_update_profile_nonce') || !is_user_logged_in()) return;
        
        $user_id = get_current_user_id();
        $is_admin = current_user_can('manage_options');

        $core_data = ['ID' => $user_id];
        if(isset($_POST['display_name'])) {
             $full_name = sanitize_text_field($_POST['display_name']);
             $name_parts = explode(' ', $full_name, 2);
             $core_data['first_name'] = $name_parts[0] ?? '';
             $core_data['last_name'] = $name_parts[1] ?? '';
             $core_data['display_name'] = $full_name;
        }
        if($is_admin && isset($_POST['email'])) { $core_data['user_email'] = sanitize_email($_POST['email']); }
        if(count($core_data) > 1) wp_update_user($core_data);

        $updatable_meta = ['alt_phone', 'guardian_phone', 'fb_link', 'present_address', 'education', 'class_dept', 'semester_year', 'skills'];
        if ($is_admin) {
            $updatable_meta = array_merge($updatable_meta, ['father_name','mother_name','birth_date','permanent_address', 'training_session', 'referer', 'nid_number', 'joining_date']);
        }

        foreach ($updatable_meta as $key) { 
            if (isset($_POST[$key])) { 
                $value = in_array($key, ['present_address', 'skills']) ? sanitize_textarea_field($_POST[$key]) : sanitize_text_field($_POST[$key]);
                update_user_meta($user_id, $key, $value);
            } 
        }

        if (!empty($_FILES['bbdc_photo']['name'])) $this->upload_user_file($user_id, 'bbdc_photo', 'profile_pic_url');
        if ($is_admin) {
            if(!empty($_FILES['nid_front']['name'])) $this->upload_user_file($user_id, 'nid_front', 'nid_front_url');
            if(!empty($_FILES['nid_back']['name'])) $this->upload_user_file($user_id, 'nid_back', 'nid_back_url');
            if(!empty($_FILES['birth_cert_scan']['name'])) $this->upload_user_file($user_id, 'birth_cert_scan', 'nid_front_url'); 
        }
        
        wp_redirect(add_query_arg('profile_updated', 'true', remove_query_arg('profile_error', wp_get_referer()))); exit;
    }

    private function upload_user_file($user_id, $file_key, $meta_key) {
        if (!function_exists('wp_handle_upload')) { require_once(ABSPATH . 'wp-admin/includes/file.php'); }
        $movefile = wp_handle_upload($_FILES[$file_key], ['test_form' => false]);
        if ($movefile && !isset($movefile['error'])) {
            update_user_meta($user_id, $meta_key, $movefile['url']);
        } else {
            wp_redirect(add_query_arg('profile_error', 'upload_failed', remove_query_arg('profile_updated', wp_get_referer()))); exit;
        }
    }
    
    public function render_patient_form() {
        ob_start();

        if (isset($_GET['submission'])) {
            if ($_GET['submission'] == 'success') echo '<p class="bbdc-success">ধন্যবাদ! রোগীর তথ্য সফলভাবে জমা হয়েছে।</p>';
            if ($_GET['submission'] == 'error') echo '<p class="bbdc-error">একটি ত্রুটি ঘটেছে। অনুগ্রহ করে আবার চেষ্টা করুন।</p>';
            if ($_GET['submission'] == 'file_error') echo '<p class="bbdc-error">ফাইল আপলোড করার সময় একটি সমস্যা হয়েছে।</p>';
        }

        ?>
        <form id="bbdc-patient-form" class="bbdc-form" method="post" action="" enctype="multipart/form-data">
            <input type="hidden" name="action" value="bbdc_submit_patient">
            <?php wp_nonce_field('bbdc_patient_nonce', 'bbdc_nonce'); ?>
            
            <p><label for="patient_name">নাম *</label><input type="text" id="patient_name" name="patient_name" required></p>
            <p><label for="father_name">বাবার নাম</label><input type="text" id="father_name" name="father_name"></p>
            <p><label for="mother_name">মায়ের নাম</label><input type="text" id="mother_name" name="mother_name"></p>
            <p><label for="age">বয়স *</label><input type="number" id="age" name="age" required></p>
            <p><label for="address">ঠিকানা *</label><textarea id="address" name="address" required></textarea></p>
            <p><label for="occupation">পেশা</label><input type="text" id="occupation" name="occupation"></p>
            <p><label for="guardian_occupation">অবিভাবকের পেশা</label><input type="text" id="guardian_occupation" name="guardian_occupation"></p>
            
            <p>
                <label for="disease">রোগ *</label>
                <select id="disease" name="disease" required>
                    <option value="থ্যালাসেমিয়া">থ্যালাসেমিয়া</option>
                    <option value="কিডনি ডায়ালাইসিস">কিডনি ডায়ালাইসিস</option>
                    <option value="ক্যান্সার">ক্যান্সার</option>
                </select>
            </p>

            <p>
                <label for="blood_group">রক্তের গ্রুপ *</label>
                <select id="blood_group" name="blood_group" required>
                    <option value="">নির্বাচন করুন</option>
                    <option value="A+">A+</option><option value="A-">A-</option>
                    <option value="B+">B+</option><option value="B-">B-</option>
                    <option value="AB+">AB+</option><option value="AB-">AB-</option>
                    <option value="O+">O+</option><option value="O-">O-</option>
                </select>
            </p>
            <p><label for="monthly_blood_need">মাসে কত ব্যাগ রক্তের প্রয়োজন *</label><input type="number" id="monthly_blood_need" name="monthly_blood_need" required></p>
            <p><label for="mobile_number">মোবাইল নাম্বার *</label><input type="text" id="mobile_number" name="mobile_number" required pattern="[0-9]{11}" maxlength="11"></p>
            <p><label for="other_info">অন্যান্য</label><textarea id="other_info" name="other_info"></textarea></p>
            <p><label for="patient_image">ছবি</label><input type="file" id="patient_image" name="patient_image" accept="image/jpeg,image/png"></p>

            <p><input type="submit" name="submit_patient_data" value="তথ্য জমা দিন"></p>
        </form>
        <?php
        return ob_get_clean();
    }

    /**
     * Handles the submission of the patient form.
     */
    public function handle_patient_submission() {
        if (!isset($_POST['submit_patient_data']) || !isset($_POST['bbdc_nonce']) || !wp_verify_nonce($_POST['bbdc_nonce'], 'bbdc_patient_nonce')) return;
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'bbdc_registered_patients';

        $image_url = '';
        if (!empty($_FILES['patient_image']['name'])) {
            if (!function_exists('wp_handle_upload')) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
            }
            $uploadedfile = $_FILES['patient_image'];
            $upload_overrides = ['test_form' => false];
            $movefile = wp_handle_upload($uploadedfile, $upload_overrides);

            if ($movefile && !isset($movefile['error'])) {
                $image_url = $movefile['url'];
            } else {
                wp_redirect(add_query_arg('submission', 'file_error', wp_get_referer()));
                exit;
            }
        }

        $data = [
            'patient_name'         => sanitize_text_field($_POST['patient_name']),
            'father_name'          => sanitize_text_field($_POST['father_name']),
            'mother_name'          => sanitize_text_field($_POST['mother_name']),
            'age'                  => intval($_POST['age']),
            'address'              => sanitize_textarea_field($_POST['address']),
            'occupation'           => sanitize_text_field($_POST['occupation']),
            'guardian_occupation'  => sanitize_text_field($_POST['guardian_occupation']),
            'disease'              => sanitize_text_field($_POST['disease']),
            'blood_group'          => sanitize_text_field($_POST['blood_group']),
            'monthly_blood_need'   => intval($_POST['monthly_blood_need']),
            'mobile_number'        => sanitize_text_field($_POST['mobile_number']),
            'other_info'           => sanitize_textarea_field($_POST['other_info']),
            'submitted_by_user_id' => is_user_logged_in() ? get_current_user_id() : null,
            'image_url'            => $image_url,
        ];

        $result = $wpdb->insert($table_name, $data);

        wp_redirect(add_query_arg('submission', $result ? 'success' : 'error', wp_get_referer()));
        exit;
    }
    
public function render_app_landing_page() {
        ob_start();
        ?>
        <style>
            /* The CSS code remains largely the same, with minor tweaks */
            :root {
                --primary-color: #1e2459;
                --accent-color: #cb2227;
                --text-color: #343a40;
                --light-text-color: #f8f9fa;
                --bg-color: #ffffff;
                --light-bg-color: #f9faff;
                --border-radius: 16px;
                --shadow: 0 12px 35px rgba(30, 36, 89, 0.1);
            }
            .bbdc-app-page {
                font-family: 'Hind Siliguri', 'Poppins', sans-serif;
                line-height: 1.8;
                color: var(--text-color);
            }
            .bbdc-app-page .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
            .bbdc-app-page .section { padding: 100px 0; text-align: center; border-bottom: 1px solid #eef; }
            .bbdc-app-page .section:last-of-type { border-bottom: none; }
            .bbdc-app-page .section-light { background-color: var(--light-bg-color); }
            .bbdc-app-page .section h2 { font-size: 2.8rem; margin-bottom: 20px; color: var(--primary-color); font-weight: 700; }
            .bbdc-app-page .section .subtitle { font-size: 1.1rem; color: #6c757d; max-width: 700px; margin: 0 auto 60px auto; }
            .bbdc-app-page .app-showcase-image { max-width: 100%; width: 300px; margin: 0 auto 40px auto; border-radius: var(--border-radius); box-shadow: var(--shadow); }
            .bbdc-app-page .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; text-align: left; }
            .bbdc-app-page .feature-card { background: var(--bg-color); padding: 30px; border-radius: var(--border-radius); box-shadow: var(--shadow); transition: transform 0.3s ease; }
            .bbdc-app-page .feature-card:hover { transform: translateY(-10px); }
            .bbdc-app-page .feature-icon { font-size: 2.5rem; width: 70px; height: 70px; line-height: 70px; text-align: center; border-radius: 50%; color: #fff; background: linear-gradient(45deg, var(--accent-color), #ff6b6b); margin-bottom: 20px; }
            .bbdc-app-page .feature-card h3 { font-size: 1.5rem; margin-bottom: 10px; color: var(--primary-color); }
            .bbdc-app-page .centered-content { max-width: 800px; margin: 0 auto; }
            .bbdc-app-page .accordion-item { background: var(--bg-color); margin-bottom: 10px; border-radius: var(--border-radius); box-shadow: 0 5px 15px rgba(0,0,0,0.05); overflow: hidden; text-align: left; }
            .bbdc-app-page .accordion-header { width: 100%; background: var(--bg-color); border: none; padding: 20px; font-size: 1.2rem; font-weight: 600; color: var(--primary-color); cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
            .bbdc-app-page .accordion-header::after { content: '\f078'; font-family: 'Font Awesome 6 Free'; font-weight: 900; transition: transform 0.3s ease; }
            .bbdc-app-page .accordion-header.active::after { transform: rotate(180deg); }
            .bbdc-app-page .accordion-content { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; padding: 0 20px; }
            .bbdc-app-page .accordion-content-inner { padding: 20px 0; border-top: 1px solid #eee; }
            .bbdc-app-page .changelog-tag { display: inline-block; padding: 3px 8px; border-radius: 5px; font-size: 0.8rem; font-weight: 600; color: #fff; margin-right: 8px; }
            .bbdc-app-page .tag-new { background-color: #28a745; }
            .bbdc-app-page .download-buttons .btn { display: inline-block; background-color: var(--accent-color); color: var(--light-text-color) !important; padding: 14px 28px; margin: 5px; border-radius: 50px; text-decoration: none; font-weight: 600; transition: all 0.3s ease; box-shadow: 0 5px 15px rgba(203, 34, 39, 0.3); }
            .bbdc-app-page .download-buttons .btn:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(203, 34, 39, 0.4); color: #fff !important; }
            .bbdc-app-page .download-buttons .btn.disabled { background-color: #aaa; box-shadow: none; transform: none; cursor: not-allowed; }
            .bbdc-app-page .download-buttons .btn i { margin-right: 10px; }
        </style>

        <div class="bbdc-app-page">
            <main>
                <section id="hero" class="section section-light">
                    <div class="container">
                        <h2>BBDC Companion</h2>
                        <p class="subtitle">আপনার রক্তদান ব্যবস্থাপনার বিশ্বস্ত সঙ্গী। বিবিডিসির সম্মানিত স্বেচ্ছাসেবকদের জন্য অফিশিয়াল অ্যাপ। ডোনার ম্যানেজমেন্ট, ইভেন্ট পরিচালনা ও নিজেদের কার্যক্রমের হিসাব রাখুন এক জায়গাতেই।</p>
                        <img src="https://bbdc.org.bd/wp-content/uploads/2025/08/app_image.jpg" alt="BBDC Companion App Screenshot" class="app-showcase-image">
                        <div class="download-buttons">
                            <a href="https://bbdc.org.bd/wp-content/app/android/app-release.apk" class="btn"><i class="fab fa-android"></i> Download for Android</a>
                            <a href="#" class="btn disabled"><i class="fab fa-apple"></i> Coming Soon for iOS</a>
                        </div>
                    </div>
                </section>

                <section id="features-list" class="section">
                    <div class="container">
                        <h2>অ্যাপের ফিচারসমূহ</h2>
                        <p class="subtitle">স্বেচ্ছাসেবকদের কাজ সহজ করতে এবং রক্তদাতাদের সাথে সংযোগ স্থাপন করতে আমরা সেরা ফিচারগুলো যুক্ত করেছি।</p>
                        <div class="features-grid">
                            <div class="feature-card">
                                <div class="feature-icon"><i class="fas fa-search"></i></div>
                                <h3>ডোনার ম্যানেজমেন্ট</h3>
                                <p>স্বেচ্ছাসেবকরা সহজেই নতুন রক্তদানের তথ্য জমা দিতে এবং রক্তের গ্রুপ বা নাম দিয়ে ডোনারদের তালিকা দেখতে পারবেন।</p>
                            </div>
                            <div class="feature-card">
                                <div class="feature-icon"><i class="fas fa-tasks"></i></div>
                                <h3>দৈনিক টাস্ক ও অ্যাক্টিভিটি</h3>
                                <p>প্রত্যেক ব্যবহারকারী তার নিজের ডোনেশন, রেফারেল এবং দৈনিক ভালো কাজের সম্পূর্ণ হিসাব ও লগ দেখতে পারবেন।</p>
                            </div>
                            <div class="feature-card">
                                <div class="feature-icon"><i class="fas fa-sitemap"></i></div>
                                <h3>সংগঠনের তথ্য</h3>
                                <p>সংগঠনের ম্যানেজমেন্ট বোর্ডের তালিকা, প্রোফাইল, সর্বশেষ নোটিশ ও আইনি কাগজপত্র সম্পর্কে অবগত থাকুন।</p>
                            </div>
                        </div>
                    </div>
                </section>
                
                <section id="changelog" class="section section-light">
                    <div class="container centered-content">
                        <h2>চেঞ্জলগ</h2>
                        <p class="subtitle">অ্যাপের প্রতিটি ভার্সনে আমরা নতুন কী যুক্ত করেছি তার তালিকা।</p>
                        <div class="changelog">
                             <div class="accordion-item">
                                <button class="accordion-header active">
                                    <span>Version 1.0.0 <small>(সর্বশেষ)</small></span>
                                    <span><?php echo date("F d, Y"); ?></span>
                                </button>
                                <div class="accordion-content" style="max-height: 500px;">
                                    <div class="accordion-content-inner">
                                       <ul>
                                           <li><span class="changelog-tag tag-new">New</span> BBDC Companion অ্যাপের প্রথম রিলিজ!</li>
                                           <li><span class="changelog-tag tag-new">New</span> ভলান্টিয়ারদের জন্য ডোনেশন ফর্ম এবং ডোনার তালিকা।</li>
                                           <li><span class="changelog-tag tag-new">New</span> প্রোফাইল, ব্যক্তিগত কার্যকলাপ এবং দৈনিক টাস্ক লগ করার সুবিধা।</li>
                                           <li><span class="changelog-tag tag-new">New</span> ম্যানেজমেন্ট বোর্ড, নোটিশ এবং অফিস লোকেশন দেখার ব্যবস্থা।</li>
                                       </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

            </main>
        </div>

        <script>
            if (document.querySelector('.bbdc-app-page')) {
                const accordionHeaders = document.querySelectorAll('.bbdc-app-page .accordion-header');
                accordionHeaders.forEach(header => {
                    header.addEventListener('click', () => {
                        const content = header.nextElementSibling;
                        header.classList.toggle('active');
                        // Close other open accordions
                        accordionHeaders.forEach(otherHeader => {
                            if (otherHeader !== header) {
                                otherHeader.classList.remove('active');
                                otherHeader.nextElementSibling.style.maxHeight = null;
                            }
                        });
                        // Open the clicked one
                        if (content.style.maxHeight) {
                            content.style.maxHeight = null;
                        } else {
                            content.style.maxHeight = content.scrollHeight + "px";
                        }
                    });
                });
            }
        </script>
        <?php
        return ob_get_clean();
    }
}