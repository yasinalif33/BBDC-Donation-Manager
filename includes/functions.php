<?php
if (!defined('ABSPATH')) exit;

//======================================================================
// HELPER FUNCTIONS
//======================================================================

if (!function_exists('bbdc_get_all_fcm_tokens_with_user_ids')) {
    function bbdc_get_all_fcm_tokens_with_user_ids() {
        global $wpdb;
        return $wpdb->get_results("SELECT user_id, fcm_token FROM {$wpdb->prefix}bbdc_fcm_tokens WHERE user_id > 0");
    }
}

if (!function_exists('bbdc_send_fcm_v1_notification')) {
    function bbdc_send_fcm_v1_notification($device_token, $title, $body, $data_payload = []) {
        $project_id = get_option('bbdc_firebase_project_id');
        $key_file_path = get_option('bbdc_fcm_service_account_path');

        if (empty($project_id) || empty($key_file_path) || !file_exists($key_file_path)) {
            return false;
        }
        
        $autoloader = BBDC_DM_PATH . 'vendor/autoload.php';
        if (!file_exists($autoloader)) {
            return false;
        }
        require_once $autoloader;

        try {
            $client = new Google\Client();
            $client->setAuthConfig($key_file_path);
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
            $access_token_info = $client->fetchAccessTokenWithAssertion();
            
            if (!isset($access_token_info['access_token'])) {
                return false;
            }
            $access_token = $access_token_info['access_token'];
            $fcm_api_url = "https://fcm.googleapis.com/v1/projects/{$project_id}/messages:send";

            $message_payload = [
                'message' => [
                    'token' => $device_token,
                    'notification' => ['title' => $title, 'body'  => $body],
                    'data' => (object) array_map('strval', $data_payload),
                    'android' => ['priority' => 'high', 'notification' => ['channel_id' => 'high_importance_channel']]
                ]
            ];

            $response = wp_remote_post($fcm_api_url, [
                'headers' => ['Authorization' => 'Bearer ' . $access_token, 'Content-Type'  => 'application/json'],
                'body'    => json_encode($message_payload),
                'timeout' => 30,
            ]);

            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
                return false;
            }

            $body_data = json_decode(wp_remote_retrieve_body($response), true);
            if (isset($body_data['error'])) {
                 return false;
            }
            
            return true;

        } catch (Exception $e) {
            error_log('BBDC FCM Exception: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('bbdc_can_manage_accounts')) {
    function bbdc_can_manage_accounts() {
        if (current_user_can('manage_options')) {
            return true;
        }
        global $wpdb;
        $user_id = get_current_user_id();
        $department_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}bbdc_departments WHERE LOWER(name) = %s",
            strtolower('Commercial Department')
        ));
        if (!$department_id) {
            return false;
        }
        $is_member = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}bbdc_board_members WHERE user_id = %d AND department_id = %d",
            $user_id,
            $department_id
        ));
        return !is_null($is_member);
    }
}

//======================================================================
// SETUP & ENQUEUE SCRIPTS
//======================================================================

if (!function_exists('bbdc_dm_enqueue_assets')) {
    function bbdc_dm_enqueue_assets($hook) {
        $is_bbdc_page = is_admin() && isset($_GET['page']) && strpos($_GET['page'], 'bbdc-') === 0;
        if (is_admin() && ($hook === 'index.php' || $is_bbdc_page)) {
            wp_enqueue_style('bbdc-admin-style', BBDC_DM_URL . 'assets/css/admin-style.css', [], BBDC_DM_VERSION);
        }
        if ($is_bbdc_page) {
            wp_enqueue_script('jquery-ui-datepicker');
            wp_enqueue_style('jquery-ui-style', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css');
            wp_enqueue_script('bbdc-admin-script', BBDC_DM_URL . 'assets/js/admin-script.js', ['jquery', 'jquery-ui-datepicker'], BBDC_DM_VERSION, true);
            wp_localize_script('bbdc-admin-script', 'bbdc_ajax_object', ['ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('bbdc_admin_nonce')]);
        }
        if (!is_admin()) {
            wp_enqueue_style('bbdc-frontend-style', BBDC_DM_URL . 'assets/css/frontend-style.css', [], BBDC_DM_VERSION);
            wp_enqueue_script('bbdc-frontend-script', BBDC_DM_URL . 'assets/js/frontend-script.js', ['jquery'], BBDC_DM_VERSION, true);
        }
    }
}

//======================================================================
// AUTOMATIC NOTIFICATIONS & WP INTEGRATIONS
//======================================================================

function bbdc_add_dashboard_widgets() {
    wp_add_dashboard_widget('bbdc_donation_summary_widget', 'BBDC Donation Summary', 'bbdc_render_dashboard_widget');
}

function bbdc_render_dashboard_widget() {
    global $wpdb;
    $donors_count = (int) $wpdb->get_var("SELECT COUNT(id) FROM {$wpdb->prefix}bbdc_donors");
    $approved_donations = (int) $wpdb->get_var("SELECT COUNT(id) FROM {$wpdb->prefix}bbdc_donation_history WHERE status = 'approved'");
    $pending_donations = (int) $wpdb->get_var("SELECT COUNT(id) FROM {$wpdb->prefix}bbdc_donation_history WHERE status = 'pending'");
    ?>
    <div class="main"><div class="bbdc-widget-row"><div class="bbdc-widget-item"><strong>Total Donors:</strong> <span><?php echo $donors_count; ?></span></div>
    <div class="bbdc-widget-item"><strong>Total Donations (Approved):</strong> <span><?php echo $approved_donations; ?></span></div>
    <div class="bbdc-widget-item"><strong>Pending Submissions:</strong> <span><?php echo $pending_donations; ?></span></div></div>
    <p><a href="<?php echo admin_url('admin.php?page=bbdc-donor-tracking'); ?>" class="button button-primary">View All Donors</a></p></div><?php
}

function bbdc_set_pending_status_on_registration($user_id) {
    $user = new WP_User($user_id);
    $user->set_role('volunteer');
    update_user_meta($user_id, 'bbdc_approval_status', 'pending');
}
add_action('user_register', 'bbdc_set_pending_status_on_registration', 10, 1);


function bbdc_send_new_volunteer_notification($user_id) {
    $user = get_userdata($user_id);
    if (!$user) return;
    $user_name = $user->display_name;
    $admins = get_users(['role__in' => ['administrator', 'bbdc_admin']]);
    $admin_ids = wp_list_pluck($admins, 'ID');
    if (empty($admin_ids)) return;
    
    $title = "New Volunteer Registration";
    $body = "$user_name has registered as a volunteer. Please review the application.";
    
    $data_payload = ['type' => 'pending_volunteer'];
    
    bbdc_send_notification_to_users($admin_ids, $title, $body, $data_payload);
}
add_action('user_register', 'bbdc_send_new_volunteer_notification', 10, 1);

function bbdc_handle_donation_approval_notifications($history_id) {
    global $wpdb;
    $donation = $wpdb->get_row($wpdb->prepare("SELECT donor_name, blood_group FROM {$wpdb->prefix}bbdc_donation_history WHERE id = %d", $history_id));

    if (!$donation) return;

    $all_users = get_users(['role__in' => ['administrator', 'bbdc_admin', 'volunteer']]);
    $recipient_ids = [];
    foreach ($all_users as $user) {
        if (get_user_meta($user->ID, 'bbdc_approval_status', true) !== 'pending') {
            $recipient_ids[] = $user->ID;
        }
    }

    if (empty($recipient_ids)) return;

    $title = "Successful Donation!";
    $body = "A donation of {$donation->blood_group} blood was successfully arranged for donor: {$donation->donor_name}. Thank you!";
    
    bbdc_send_notification_to_users($recipient_ids, $title, $body);
}

function bbdc_send_approval_notification_to_submitter($history_id) {
    global $wpdb;
    $donation = $wpdb->get_row($wpdb->prepare(
        "SELECT donor_name, submitted_by_user_id FROM {$wpdb->prefix}bbdc_donation_history WHERE id = %d",
        $history_id
    ));

    if (empty($donation) || empty($donation->submitted_by_user_id)) {
        return;
    }

    $submitter_id = (int) $donation->submitted_by_user_id;
    $donor_name = $donation->donor_name;

    $title = "Donation Approved";
    $body = "The donation record you submitted for '$donor_name' has been approved. Thank you for your contribution!";
    
    bbdc_send_notification_to_users([$submitter_id], $title, $body);
}

function bbdc_send_new_pending_donation_notification($history_id) {
    global $wpdb;
    $donation = $wpdb->get_row($wpdb->prepare("SELECT donor_name FROM {$wpdb->prefix}bbdc_donation_history WHERE id = %d", $history_id));
    if (!$donation) return;

    $admin_ids = $wpdb->get_col(
        "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = '{$wpdb->prefix}capabilities' AND (meta_value LIKE '%\"administrator\"%' OR meta_value LIKE '%\"bbdc_admin\"%')"
    );
    
    if (empty($admin_ids)) return;

    $title = "New Donation for Approval";
    $body = "A new donation record for donor '{$donation->donor_name}' has been submitted for your approval.";
    
    $data_payload = ['type' => 'pending_donation'];
    
    bbdc_send_notification_to_users($admin_ids, $title, $body, $data_payload);
}

function bbdc_send_notification_to_users($user_ids, $title, $body, $data_payload = []) {
    global $wpdb;
    if (empty($user_ids)) return;

    $user_ids = array_unique(array_map('intval', $user_ids));
    $user_ids_placeholder = implode(',', $user_ids);
    
    $tokens = $wpdb->get_col("SELECT fcm_token FROM {$wpdb->prefix}bbdc_fcm_tokens WHERE user_id IN ($user_ids_placeholder)");
    
    if (empty($tokens)) {
        error_log("BBDC Debug: No FCM tokens found for user IDs: " . $user_ids_placeholder);
        return;
    }
    
    foreach ($user_ids as $user_id) {
         $wpdb->insert($wpdb->prefix . 'bbdc_notifications', [
            'user_id' => $user_id, 
            'title' => $title, 
            'body' => $body, 
            'data_payload' => json_encode($data_payload)
        ]);
    }
    
    foreach ($tokens as $token) {
        bbdc_send_fcm_v1_notification($token, $title, $body, $data_payload);
    }
}

if (!function_exists('bbdc_add_profile_menu_item')) {
    function bbdc_add_profile_menu_item($items, $args) {
        if (isset($args->theme_location) && $args->theme_location == 'primary' && is_user_logged_in()) {
            if (current_user_can('access_bbdc_plugin')) {
                $items .= '<li class="menu-item"><a href="' . esc_url(home_url('/my-profile/')) . '">Profile</a></li>';
                $items .= '<li class="menu-item"><a href="' . wp_logout_url(home_url()) . '">Logout</a></li>';
            }
        }
        return $items;
    }
}

if (!function_exists('bbdc_restrict_volunteer_backend_access')) {
    function bbdc_restrict_volunteer_backend_access() {
        if (is_admin() && !defined('DOING_AJAX') && current_user_can('access_bbdc_plugin') && !current_user_can('manage_options')) {
            wp_redirect(home_url('/my-profile/'));
            exit;
        }
    }
}
add_action('admin_init', 'bbdc_restrict_volunteer_backend_access');

if (!function_exists('bbdc_display_custom_user_profile_fields')) {
    function bbdc_display_custom_user_profile_fields($user) {
        if (!current_user_can('edit_user', $user->ID)) return;
        
        $meta = get_user_meta($user->ID);
        $get_meta = function($key) use ($meta) { return $meta[$key][0] ?? ''; };
        ?>
        <h2>BBDC Volunteer Information</h2>
        <table class="form-table">
            </table>

        <?php if (current_user_can('promote_users')) : ?>
            <h2>BBDC Roles</h2>
            <table class="form-table">
                <tr>
                    <th><label>Assign Roles</label></th>
                    <td>
                        <?php
                        $bbdc_roles = ['bbdc_accountant' => 'BBDC Accountant', 'bbdc_event_manager' => 'BBDC Event Manager'];
                        foreach ($bbdc_roles as $role_key => $role_name) {
                            ?>
                            <label style="display: block; margin-bottom: 5px;">
                                <input type="checkbox" name="bbdc_roles[]" value="<?php echo esc_attr($role_key); ?>" <?php checked(in_array($role_key, (array) $user->roles)); ?>>
                                <?php echo esc_html($role_name); ?>
                            </label>
                            <?php
                        }
                        ?>
                        <p class="description">Assign additional responsibilities. The base 'Volunteer' role will be kept.</p>
                    </td>
                </tr>
            </table>
        <?php endif; ?>
        <?php
    }
}
add_action('show_user_profile', 'bbdc_display_custom_user_profile_fields');
add_action('edit_user_profile', 'bbdc_display_custom_user_profile_fields');

if (!function_exists('bbdc_save_custom_user_profile_fields')) {
    function bbdc_save_custom_user_profile_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) return false;
        
        // Save standard meta fields (unchanged)
        $fields_to_save = ['bbdc_mobile_number', 'father_name', 'mother_name', 'blood_group', 'birth_date', 'present_address', 'permanent_address', 'nid_number'];
        foreach ($fields_to_save as $field) {
            if (isset($_POST[$field])) {
                $value = in_array($field, ['present_address', 'permanent_address']) ? sanitize_textarea_field($_POST[$field]) : sanitize_text_field($_POST[$field]);
                update_user_meta($user_id, $field, $value);
            }
        }

        if (current_user_can('promote_users') && isset($_POST['bbdc_roles'])) {
            $user = get_user_by('id', $user_id);
            $bbdc_roles_to_manage = ['bbdc_accountant', 'bbdc_event_manager'];
            $submitted_roles = array_map('sanitize_key', $_POST['bbdc_roles']);

            foreach ($bbdc_roles_to_manage as $role_key) {
                if (in_array($role_key, $submitted_roles)) {
                    $user->add_role($role_key); // Add role if checked
                } else {
                    $user->remove_role($role_key); // Remove role if unchecked
                }
            }
        }
    }
}
add_action('personal_options_update', 'bbdc_save_custom_user_profile_fields');
add_action('edit_user_profile_update', 'bbdc_save_custom_user_profile_fields');

//======================================================================
// AJAX HANDLERS
//======================================================================

add_action('wp_ajax_bbdc_approve_donation', function() {
    check_ajax_referer('bbdc_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied.');
    }

    $history_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if (!$history_id) {
        wp_send_json_error('Invalid ID.');
    }

    global $wpdb;
    $history_table = $wpdb->prefix . 'bbdc_donation_history';
    $donors_table = $wpdb->prefix . 'bbdc_donors';

    $donation = $wpdb->get_row($wpdb->prepare("SELECT * FROM $history_table WHERE id = %d", $history_id));
    if (!$donation) {
        wp_send_json_error('Pending record not found.');
    }

    $form_data = json_decode($donation->form_data, true);
    $donor_mobile = $donation->donor_mobile;

    $wpdb->update($history_table, ['status' => 'approved'], ['id' => $history_id]);

    if ($donation->is_bbdc_donation == 1) {
        if (empty($donor_mobile) || !is_numeric($donor_mobile) || strlen($donor_mobile) < 11) {
            $wpdb->update($history_table, ['status' => 'pending'], ['id' => $history_id]);
            wp_send_json_error('Approval Failed: Record is missing a valid mobile number.');
        }

        $existing_donor = $wpdb->get_row($wpdb->prepare("SELECT id, total_donations FROM $donors_table WHERE mobile_number = %s", $donor_mobile));
        
        $data = [
            'donor_name'         => sanitize_text_field($form_data['donor_name']),
            'blood_group'        => sanitize_text_field($form_data['blood_group']),
            'mobile_number'      => $donor_mobile,
            'last_donation_date' => sanitize_text_field($form_data['donation_date']) ?: null,
            'age'                => !empty($form_data['age']) ? intval($form_data['age']) : null,
            'donor_location'     => isset($form_data['donor_location']) ? sanitize_text_field($form_data['donor_location']) : null,
        ];

        if ($existing_donor) {
            $data['total_donations'] = $existing_donor->total_donations + 1;
            $result = $wpdb->update($donors_table, $data, ['id' => $existing_donor->id]);
        } else {
            $data['total_donations'] = 1;
            $result = $wpdb->insert($donors_table, $data);
        }

        if ($result === false) {
            $wpdb->update($history_table, ['status' => 'pending'], ['id' => $history_id]);
            wp_send_json_error('DB Error: Could not update/insert donor record.');
        }
    }
    
    if (function_exists('bbdc_send_approval_notification_to_submitter')) {
        bbdc_send_approval_notification_to_submitter($history_id);
    }
    
    wp_send_json_success('Donation approved successfully.');
});

add_action('wp_ajax_bbdc_reject_donation', function() { check_ajax_referer('bbdc_admin_nonce', 'nonce'); if (!current_user_can('manage_options')) { wp_send_json_error('Permission denied.'); } $history_id = isset($_POST['id']) ? intval($_POST['id']) : 0; if (!$history_id) { wp_send_json_error('Invalid ID.'); } global $wpdb; $wpdb->delete($wpdb->prefix . 'bbdc_donation_history', ['id' => $history_id]); wp_send_json_success('Entry removed.'); });
add_action('wp_ajax_bbdc_delete_history', function() { check_ajax_referer('bbdc_admin_nonce', 'nonce'); if (!current_user_can('manage_options')) { wp_send_json_error('Permission denied.'); } $history_id = isset($_POST['id']) ? intval($_POST['id']) : 0; if (!$history_id) { wp_send_json_error('Invalid ID.'); } global $wpdb; $wpdb->delete($wpdb->prefix . 'bbdc_donation_history', ['id' => $history_id]); wp_send_json_success('Entry removed.'); });
add_action('wp_ajax_bbdc_delete_donor', function() { check_ajax_referer('bbdc_admin_nonce', 'nonce'); if (!current_user_can('manage_options')) { wp_send_json_error('Permission denied.'); } $donor_id = isset($_POST['id']) ? intval($_POST['id']) : 0; if (!$donor_id) { wp_send_json_error('Invalid Donor ID.'); } global $wpdb; $donors_table = $wpdb->prefix . 'bbdc_donors'; $history_table = $wpdb->prefix . 'bbdc_donation_history'; $donor_mobile = $wpdb->get_var($wpdb->prepare("SELECT mobile_number FROM $donors_table WHERE id = %d", $donor_id)); if ($donor_mobile) { $wpdb->delete($history_table, ['donor_mobile' => $donor_mobile]); } $wpdb->delete($donors_table, ['id' => $donor_id]); wp_send_json_success('Donor and all related history have been deleted.'); });
add_action('wp_ajax_bbdc_send_greeting_sms', function() { check_ajax_referer('bbdc_admin_nonce', 'nonce'); if (!current_user_can('manage_options')) { wp_send_json_error('Permission denied.'); } $mobile = sanitize_text_field($_POST['mobile']); $name = sanitize_text_field($_POST['name']); if(function_exists('bbdc_send_sms')) { bbdc_send_sms($mobile, $name); } wp_send_json_success('SMS sent successfully.'); });
add_action('wp_ajax_bbdc_approve_volunteer', function() { check_ajax_referer('bbdc_admin_nonce', 'nonce'); if (!current_user_can('manage_options')) { wp_send_json_error('Permission denied.'); } $user_id = isset($_POST['id']) ? intval($_POST['id']) : 0; if (!$user_id) { wp_send_json_error('Invalid User ID.'); } update_user_meta($user_id, 'bbdc_approval_status', 'approved'); wp_send_json_success('Volunteer approved successfully.'); });
add_action('wp_ajax_bbdc_reject_volunteer', function() { check_ajax_referer('bbdc_admin_nonce', 'nonce'); if (!current_user_can('manage_options')) { wp_send_json_error('Permission denied.'); } $user_id = isset($_POST['id']) ? intval($_POST['id']) : 0; if (!$user_id) { wp_send_json_error('Invalid User ID.'); } require_once(ABSPATH.'wp-admin/includes/user.php'); wp_delete_user($user_id); wp_send_json_success('Volunteer rejected and deleted.'); });
add_action('wp_ajax_bbdc_change_volunteer_role', function() {
    check_ajax_referer('bbdc_admin_nonce', 'nonce');
    if (!current_user_can('promote_users')) {
        wp_send_json_error('You do not have permission to change user roles.');
    }
    
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

    $new_roles = isset($_POST['new_roles']) && is_array($_POST['new_roles']) ? array_map('sanitize_key', $_POST['new_roles']) : [];

    if (!$user_id) {
        wp_send_json_error('Invalid user ID provided.');
    }

    $user = get_user_by('id', $user_id);
    if (!$user) {
        wp_send_json_error('User not found.');
    }

    $user->set_role(''); 
    
    $allowed_roles = ['volunteer', 'bbdc_admin', 'blood_response'];
    foreach ($new_roles as $role) {
        if (in_array($role, $allowed_roles)) {
            $user->add_role($role);
        }
    }
    if (empty($user->roles)) {
        $user->add_role('volunteer');
    }

    wp_send_json_success('Roles updated successfully.');
});
add_action('wp_ajax_bbdc_delete_patient', function() { check_ajax_referer('bbdc_admin_nonce', 'nonce'); if (!current_user_can('manage_options')) { wp_send_json_error('Permission denied.'); } $patient_id = isset($_POST['id']) ? intval($_POST['id']) : 0; if (!$patient_id) { wp_send_json_error('Invalid Patient ID.'); } global $wpdb; $wpdb->delete($wpdb->prefix . 'bbdc_registered_patients', ['id' => $patient_id]); wp_send_json_success('Patient record deleted.'); });
add_action('wp_ajax_bbdc_get_patient_details', function() { check_ajax_referer('bbdc_admin_nonce', 'nonce'); if (!current_user_can('manage_options')) { wp_send_json_error(['message' => 'Permission Denied.']); } $patient_id = isset($_POST['id']) ? intval($_POST['id']) : 0; if (!$patient_id) { wp_send_json_error(['message' => 'Invalid Patient ID.']); } global $wpdb; $patient_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}bbdc_registered_patients WHERE id = %d", $patient_id), ARRAY_A); if ($patient_data) { wp_send_json_success($patient_data); } else { wp_send_json_error(['message' => 'Patient not found.']); } });


//======================================================================
// ADMIN POST HANDLERS
//======================================================================

add_action('admin_post_bbdc_create_event', function() { if (!current_user_can('manage_options') || !check_admin_referer('bbdc_create_event_nonce')) { wp_die('Permission denied.'); } global $wpdb; $wpdb->insert("{$wpdb->prefix}bbdc_campaigns", ['campaign_name' => sanitize_text_field($_POST['event_name']), 'event_category' => sanitize_text_field($_POST['event_category']), 'campaign_date' => sanitize_text_field($_POST['event_date']), 'venue' => sanitize_textarea_field($_POST['venue'])]); wp_redirect(admin_url('admin.php?page=bbdc-events&message=created')); exit; });
add_action('admin_post_bbdc_save_attendance', function() { if (!current_user_can('manage_options') || !check_admin_referer('bbdc_manage_event_nonce_save')) { wp_die('Permission denied.'); } global $wpdb; $event_id = intval($_POST['event_id']); $attendees = isset($_POST['attendees']) ? array_map('intval', $_POST['attendees']) : []; $guests_raw = isset($_POST['guests']) ? sanitize_textarea_field($_POST['guests']) : ''; $guests = array_filter(array_map('trim', explode("\n", $guests_raw))); $wpdb->delete($wpdb->prefix . 'bbdc_campaign_attendance', ['campaign_id' => $event_id]); $wpdb->delete($wpdb->prefix . 'bbdc_campaign_guests', ['campaign_id' => $event_id]); foreach ($attendees as $user_id) { $wpdb->insert($wpdb->prefix . 'bbdc_campaign_attendance', ['campaign_id' => $event_id, 'volunteer_user_id' => $user_id]); } foreach ($guests as $guest_name) { $wpdb->insert($wpdb->prefix . 'bbdc_campaign_guests', ['campaign_id' => $event_id, 'guest_name' => $guest_name]); } wp_redirect(wp_get_referer()); exit; });
add_action('admin_post_bbdc_delete_event', function() { if (!current_user_can('manage_options') || !check_admin_referer('bbdc_delete_event_nonce')) { wp_die('Permission denied.'); } global $wpdb; $event_id = intval($_GET['event_id']); $wpdb->delete($wpdb->prefix . 'bbdc_campaigns', ['id' => $event_id]); $wpdb->delete($wpdb->prefix . 'bbdc_campaign_logistics', ['campaign_id' => $event_id]); $wpdb->delete($wpdb->prefix . 'bbdc_campaign_attendance', ['campaign_id' => $event_id]); $wpdb->delete($wpdb->prefix . 'bbdc_campaign_guests', ['campaign_id' => $event_id]); wp_redirect(admin_url('admin.php?page=bbdc-events&message=deleted')); exit; });
add_action('admin_post_bbdc_add_income', function() { if (!bbdc_can_manage_accounts() || !check_admin_referer('bbdc_accounts_nonce')) { wp_die('Permission Denied.'); } global $wpdb; $wpdb->insert($wpdb->prefix . 'bbdc_accounts_transactions', ['transaction_type' => 'income', 'source' => sanitize_text_field($_POST['income_source']), 'amount' => floatval($_POST['income_amount']), 'remarks' => sanitize_textarea_field($_POST['remarks']), 'transaction_date' => sanitize_text_field($_POST['transaction_date']), 'entered_by_user_id' => get_current_user_id()]); wp_redirect(admin_url('admin.php?page=bbdc-accounts&message=income_added')); exit; });
add_action('admin_post_bbdc_add_expense', function() { if (!bbdc_can_manage_accounts() || !check_admin_referer('bbdc_accounts_nonce')) { wp_die('Permission Denied.'); } if (empty($_FILES['expense_memo']['name'])) { wp_redirect(admin_url('admin.php?page=bbdc-accounts&message=memo_required')); exit; } global $wpdb; $memo_url = ''; require_once(ABSPATH . 'wp-admin/includes/file.php'); $movefile = wp_handle_upload($_FILES['expense_memo'], ['test_form' => false]); if ($movefile && !isset($movefile['error'])) { $memo_url = $movefile['url']; } else { wp_redirect(admin_url('admin.php?page=bbdc-accounts&message=upload_error')); exit; } $wpdb->insert($wpdb->prefix . 'bbdc_accounts_transactions', ['transaction_type' => 'expense', 'source' => sanitize_text_field($_POST['expense_source']), 'amount' => floatval($_POST['expense_amount']), 'memo_url' => $memo_url, 'remarks' => sanitize_textarea_field($_POST['remarks']), 'transaction_date' => sanitize_text_field($_POST['transaction_date']), 'entered_by_user_id' => get_current_user_id()]); wp_redirect(admin_url('admin.php?page=bbdc-accounts&message=expense_added')); exit; });
add_action('admin_post_bbdc_send_admin_notification', 'bbdc_handle_send_notification_form');

function bbdc_handle_send_notification_form() {
    if (!current_user_can('manage_options') || !check_admin_referer('bbdc_send_notification_nonce')) {
        wp_die('Permission denied.');
    }
    $title = sanitize_text_field($_POST['notification_title']);
    $body = sanitize_textarea_field($_POST['notification_body']);
    $screen = !empty($_POST['notification_screen']) ? sanitize_text_field($_POST['notification_screen']) : '/';
    $redirect_url = admin_url('admin.php?page=bbdc-settings');
    
    $tokens_with_users = bbdc_get_all_fcm_tokens_with_user_ids();
    if (empty($tokens_with_users)) {
        wp_redirect(add_query_arg(['notification_error' => 'true', 'error_message' => urlencode('No registered devices found.')], $redirect_url));
        exit;
    }
    
    $user_ids = array_unique(wp_list_pluck($tokens_with_users, 'user_id'));
    $data_payload = ['screen' => $screen, 'click_action' => 'FLUTTER_NOTIFICATION_CLICK'];
    
    $success_count = bbdc_send_notification_to_users($user_ids, $title, $body, $data_payload);

    wp_redirect(add_query_arg(['notification_sent' => 'true', 'count' => $success_count], $redirect_url));
    exit;
}

//======================================================================
// SCHEDULED TASKS (CRON JOBS) FOR NOTIFICATIONS
//======================================================================

/**
 * Schedules the daily attendance reminder cron jobs.
 */
function bbdc_schedule_attendance_reminders() {
    if (!wp_next_scheduled('bbdc_evening_attendance_reminder')) {
        wp_schedule_event(strtotime('today 18:00:00'), 'daily', 'bbdc_evening_attendance_reminder');
    }
    if (!wp_next_scheduled('bbdc_night_attendance_reminder')) {
        wp_schedule_event(strtotime('today 23:00:00'), 'daily', 'bbdc_night_attendance_reminder');
    }
}

/**
 * Clears the scheduled cron jobs on plugin deactivation.
 */
function bbdc_clear_scheduled_reminders() {
    wp_clear_scheduled_hook('bbdc_evening_attendance_reminder');
    wp_clear_scheduled_hook('bbdc_night_attendance_reminder');
}

/**
 * Executes the logic for sending the evening attendance reminder.
 */
function bbdc_send_evening_attendance_reminder() {
    sleep(rand(0, 3600)); 
    
    $title = "Daily Attendance Reminder";
    $body = "Don't forget to mark your attendance for today. Your contribution is valuable to us!";
    
    bbdc_send_attendance_reminder_to_pending_users($title, $body);
}
add_action('bbdc_evening_attendance_reminder', 'bbdc_send_evening_attendance_reminder');

/**
 * Executes the logic for sending the final night attendance reminder.
 */
function bbdc_send_night_attendance_reminder() {
    sleep(rand(0, 3599));

    $title = "Final Attendance Reminder!";
    $body = "This is the final reminder to mark your attendance for today before the time runs out.";

    bbdc_send_attendance_reminder_to_pending_users($title, $body);
}
add_action('bbdc_night_attendance_reminder', 'bbdc_send_night_attendance_reminder');

/**
 * Helper function to find and notify users who haven't marked attendance.
 */
function bbdc_send_attendance_reminder_to_pending_users($title, $body) {
    global $wpdb;
    
    $users = get_users(['role__in' => ['blood_response', 'bbdc_admin', 'administrator']]);
    if (empty($users)) {
        return;
    }

    $all_user_ids = wp_list_pluck($users, 'ID');
    
    $today_start = date('Y-m-d 00:00:00', strtotime(current_time('mysql')));
    $ids_who_attended = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT user_id FROM {$wpdb->prefix}bbdc_attendance_log WHERE attendance_time >= %s",
        $today_start
    ));
    $ids_who_attended = array_map('intval', $ids_who_attended);

    $ids_to_notify = array_diff($all_user_ids, $ids_who_attended);

    if (!empty($ids_to_notify)) {
        bbdc_send_notification_to_users($ids_to_notify, $title, $body);
    }
}

/**
 * Disables the default WordPress registration page and redirects to the custom form.
 */
function bbdc_disable_default_registration() {
    // We are on the login page
    if (in_array($GLOBALS['pagenow'], ['wp-login.php', 'wp-register.php'])) {
        // We are on the registration action
        if (isset($_GET['action']) && $_GET['action'] === 'register') {
            // Redirect to our custom registration/login page
            wp_redirect(home_url('/volunteer-area/'));
            exit;
        }
    }
}
add_action('init', 'bbdc_disable_default_registration');

/**
 * Finds patient entries with broken image URLs and attempts to fix them.
 */
function bbdc_fix_patient_image_urls($batch = 1, $per_batch = 5) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'bbdc_registered_patients';
    $updated_count = 0;
    $failed_count = 0;
    $offset = ($batch - 1) * $per_batch;

    $broken_patients = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, image_url FROM {$table_name} WHERE image_url LIKE '%%/scaled_%%-scaled.jpg' LIMIT %d OFFSET %d",
            $per_batch,
            $offset
        )
    );

    if (empty($broken_patients)) {
        return ['complete' => true, 'updated' => 0, 'failed' => 0];
    }

    foreach ($broken_patients as $patient) {
        $broken_url = $patient->image_url;
        $file_name_with_prefix = basename($broken_url);
        $directory_url = dirname($broken_url);
        $found_correct_url = false;

        preg_match('/^scaled_(.*)-scaled\.jpg$/', $file_name_with_prefix, $matches);
        if (empty($matches[1])) {
            $failed_count++;
            continue;
        }
        $core_file_name = $matches[1];

        $potential_file_names = [
            $core_file_name . '-scaled.jpg', $core_file_name . '.jpg',
            $core_file_name . '-scaled.png', $core_file_name . '.png',
            $core_file_name . '-scaled.jpeg', $core_file_name . '.jpeg',
        ];

        foreach ($potential_file_names as $new_file_name) {
            $potential_url = $directory_url . '/' . $new_file_name;
            $response = wp_remote_head($potential_url, ['timeout' => 5]);
            
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $wpdb->update(
                    $table_name,
                    ['image_url' => $potential_url],
                    ['id' => $patient->id]
                );
                $updated_count++;
                $found_correct_url = true;
                break;
            }
        }

        if (!$found_correct_url) {
            $failed_count++;
        }
    }

    return [
        'complete' => false,
        'updated' => $updated_count,
        'failed' => $failed_count,
        'processed' => count($broken_patients)
    ];
}

if (!function_exists('bbdc_get_user_note_permission')) {
    function bbdc_get_user_note_permission($note_id, $user_id) {
        global $wpdb;
        $notes_table = $wpdb->prefix . 'bbdc_notes';
        $permissions_table = $wpdb->prefix . 'bbdc_note_permissions';

        // 1. Check if the user is the owner
        $owner_id = $wpdb->get_var($wpdb->prepare("SELECT owner_user_id FROM $notes_table WHERE id = %d", $note_id));
        if ($owner_id && $owner_id == $user_id) {
            return 'owner';
        }

        // 2. Check if the user has been granted permission
        $permission = $wpdb->get_var($wpdb->prepare(
            "SELECT permission_level FROM $permissions_table WHERE note_id = %d AND user_id = %d",
            $note_id, $user_id
        ));
        
        return $permission; // Will be 'view', 'edit', or null
    }
}

if (!function_exists('bbdc_send_ssl_sms')) {
    /**
     * Sends a single SMS using SSL Wireless API.
     *
     * @param string $mobile_number The recipient's mobile number.
     * @param string $message The SMS text body.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    function bbdc_send_ssl_sms($mobile_number, $message) {
        $api_token = get_option('bbdc_sms_api_token');
        $sid = get_option('bbdc_sms_sid');

        if (empty($api_token) || empty($sid)) {
            return new WP_Error('config_error', 'SMS API Token or SID is not configured in settings.');
        }

        $formatted_mobile = sanitize_text_field($mobile_number);
        if (substr($formatted_mobile, 0, 2) === '01') {
            $formatted_mobile = '8801' . substr($formatted_mobile, 2);
        }

        $api_url = 'https://api.sslwireless.com/api/v3/send-sms';
        $csms_id = uniqid('bbdc_');

        $payload = [
            'api_token' => $api_token,
            'sid' => $sid,
            'msisdn' => $formatted_mobile,
            'sms' => $message,
            'csms_id' => $csms_id,
        ];
        
        $request_url = add_query_arg($payload, $api_url);
        
        $response = wp_remote_get($request_url, [
            'timeout'   => 30,
        ]);
        
        if (is_wp_error($response)) {
            error_log('BBDC SMS WP_Error: ' . $response->get_error_message());
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['status_code']) && $body['status_code'] == 200 && $body['status'] === 'SUCCESS') {
            return true;
        } else {
            error_log('BBDC SMS Gateway Error: ' . ($body['error_message'] ?? 'Unknown Error. Response: ' . wp_remote_retrieve_body($response)));
            return new WP_Error('gateway_error', $body['error_message'] ?? 'Unknown SMS gateway error.');
        }
    }
}