<?php
if (!defined('ABSPATH')) {
    exit;
}

class BBDC_DM_Rest_Api {
    protected $namespace = 'bbdc/v1';

    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

public function register_routes() {
        $permission_callback = [$this, 'validate_jwt_permission'];
        $admin_permission_callback = [$this, 'validate_admin_permission'];
        $attendance_permission = [$this, 'validate_attendance_permission'];
        $accounts_permission_callback = [$this, 'validate_accounts_permission'];

        // AUTH & USER
        register_rest_route($this->namespace, '/register', ['methods' => 'POST', 'callback' => [$this, 'handle_registration'], 'permission_callback' => '__return_true']);
        register_rest_route($this->namespace, '/user/me', ['methods' => 'GET', 'callback' => [$this, 'get_current_user_details'], 'permission_callback' => $permission_callback]);
        register_rest_route($this->namespace, '/users/lost-password', ['methods' => 'POST', 'callback' => [$this, 'handle_lost_password'], 'permission_callback' => '__return_true']);
        
        // APP INFO (Update & Maintenance)
        register_rest_route($this->namespace, '/app-info', ['methods' => 'GET', 'callback' => [$this, 'get_app_info'], 'permission_callback' => '__return_true']);

        // DONORS
        register_rest_route($this->namespace, '/donors', ['methods' => 'GET', 'callback' => [$this, 'get_donors_list'], 'permission_callback' => $permission_callback]);
        register_rest_route($this->namespace, '/donors/(?P<id>\d+)', [
            ['methods' => 'POST', 'callback' => [$this, 'update_donor_profile'], 'permission_callback' => $admin_permission_callback],
            ['methods' => 'DELETE', 'callback' => [$this, 'delete_donor'], 'permission_callback' => $admin_permission_callback]
        ]);
        register_rest_route($this->namespace, '/donors/(?P<id>\d+)/send-sms', ['methods' => 'POST', 'callback' => [$this, 'send_donor_sms'], 'permission_callback' => $admin_permission_callback]);

        // VOLUNTEERS
        register_rest_route($this->namespace, '/volunteers/(?P<status>approved|pending)', ['methods' => 'GET', 'callback' => [$this, 'get_volunteers_list'], 'permission_callback' => $admin_permission_callback]);
        register_rest_route($this->namespace, '/volunteers/all', ['methods' => 'GET', 'callback' => [$this, 'get_all_approved_volunteers'], 'permission_callback' => $permission_callback]);
        register_rest_route($this->namespace, '/volunteers/(?P<id>\d+)', [
            ['methods' => 'GET', 'callback' => [$this, 'get_volunteer_details'], 'permission_callback' => $permission_callback],
            ['methods' => 'POST', 'callback' => [$this, 'update_volunteer_profile'], 'permission_callback' => $permission_callback]
        ]);
        register_rest_route($this->namespace, '/volunteers/(?P<id>\d+)/approve', ['methods' => 'POST', 'callback' => [$this, 'approve_volunteer'], 'permission_callback' => $admin_permission_callback]);
        register_rest_route($this->namespace, '/volunteers/(?P<id>\d+)/reject', ['methods' => 'POST', 'callback' => [$this, 'reject_volunteer'], 'permission_callback' => $admin_permission_callback]);
        register_rest_route($this->namespace, '/volunteers/(?P<id>\d+)/set-role',['methods' => 'POST','callback' => [$this, 'set_volunteer_role'], 'permission_callback' => $admin_permission_callback]);

        // HISTORY & DONATIONS
        register_rest_route($this->namespace, '/history', ['methods' => 'GET', 'callback' => [$this, 'get_history_list'], 'permission_callback' => $admin_permission_callback]);
        register_rest_route($this->namespace, '/donation/submit', ['methods' => 'POST', 'callback' => [$this, 'handle_donation_submission'], 'permission_callback' => $permission_callback]);
        register_rest_route($this->namespace, '/donations/(?P<id>\d+)/approve', ['methods' => 'POST', 'callback' => [$this, 'approve_donation'], 'permission_callback' => $admin_permission_callback]);
        register_rest_route($this->namespace, '/donations/(?P<id>\d+)/reject', ['methods' => 'POST', 'callback' => [$this, 'reject_donation'], 'permission_callback' => $admin_permission_callback]);
        register_rest_route($this->namespace, '/donations/(?P<id>\d+)', ['methods' => 'DELETE', 'callback' => [$this, 'delete_history_record'], 'permission_callback' => $admin_permission_callback]);
        
        // REFERRERS
        register_rest_route($this->namespace, '/referrers', [
            ['methods' => 'GET', 'callback' => [$this, 'get_referrers_list'], 'permission_callback' => $permission_callback],
            ['methods' => 'POST', 'callback' => [$this, 'add_referrer'], 'permission_callback' => $admin_permission_callback]
        ]);
        register_rest_route($this->namespace, '/referrers/(?P<id>\d+)', ['methods' => 'DELETE', 'callback' => [$this, 'delete_referrer'], 'permission_callback' => $admin_permission_callback]);
        
        // MY ACTIVITY
        register_rest_route($this->namespace, '/user/me/activity/events', ['methods'  => 'GET', 'callback' => [$this, 'get_my_attended_events'], 'permission_callback' => $permission_callback]);
        register_rest_route($this->namespace, '/user/me/activity/donations', ['methods'  => 'GET', 'callback' => [$this, 'get_my_personal_donations'], 'permission_callback' => $permission_callback]);
        register_rest_route($this->namespace, '/user/me/activity/referrals', ['methods'  => 'GET', 'callback' => [$this, 'get_my_referred_donations'], 'permission_callback' => $permission_callback]);
        register_rest_route($this->namespace, '/user/me/activity/fees', ['methods' => 'GET', 'callback' => [$this, 'get_my_fee_history'], 'permission_callback' => $permission_callback]);
        
        // EVENTS
        register_rest_route($this->namespace, '/events', [['methods' => 'GET', 'callback' => [$this, 'get_events'], 'permission_callback' => $admin_permission_callback], ['methods' => 'POST', 'callback' => [$this, 'create_event'], 'permission_callback' => $admin_permission_callback]]);
        register_rest_route($this->namespace, '/events/(?P<id>\d+)', [
            ['methods' => 'GET', 'callback' => [$this, 'get_event_details'], 'permission_callback' => $admin_permission_callback],
            ['methods' => 'POST', 'callback' => [$this, 'update_event'], 'permission_callback' => $admin_permission_callback],
            ['methods' => 'DELETE', 'callback' => [$this, 'delete_event'], 'permission_callback' => $admin_permission_callback]
        ]);
        register_rest_route($this->namespace, '/events/(?P<id>\d+)/attendance', ['methods' => 'POST', 'callback' => [$this, 'save_event_attendance'], 'permission_callback' => $admin_permission_callback]);
        
        // NOTES
        register_rest_route($this->namespace, '/notes', [
            ['methods' => 'GET', 'callback' => [$this, 'get_notes'], 'permission_callback' => $permission_callback],
            ['methods' => 'POST', 'callback' => [$this, 'create_note'], 'permission_callback' => $permission_callback]
        ]);
        register_rest_route($this->namespace, '/notes/(?P<id>\d+)', [
            ['methods' => 'POST', 'callback' => [$this, 'update_note'], 'permission_callback' => $permission_callback],
            ['methods' => 'DELETE', 'callback' => [$this, 'delete_note'], 'permission_callback' => $permission_callback]
        ]);
        register_rest_route($this->namespace, '/notes/(?P<id>\d+)/shares', [
            ['methods' => 'GET', 'callback' => [$this, 'get_note_shares'], 'permission_callback' => $permission_callback],
            ['methods' => 'POST', 'callback' => [$this, 'update_note_shares'], 'permission_callback' => $permission_callback]
        ]);
        
        // PAYMENT GATEWAY
        register_rest_route($this->namespace, '/payment/initiate', [
            'methods' => 'POST',
            'callback' => [$this, 'initiate_payment'],
            'permission_callback' => $permission_callback
        ]);
        register_rest_route($this->namespace, '/payment/callback', [
            'methods' => 'GET',
            'callback' => [$this, 'handle_payment_callback'],
            'permission_callback' => '__return_true'
        ]);
        register_rest_route($this->namespace, '/payment/verify/(?P<order_id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'verify_payment_status'],
            'permission_callback' => $permission_callback
        ]);
        
        // NOTICES & NOTIFICATIONS
        register_rest_route($this->namespace, '/fcm-token', ['methods' => 'POST', 'callback' => [$this, 'save_fcm_token'], 'permission_callback' => $permission_callback]);
        register_rest_route($this->namespace, '/send-notification', ['methods' => 'POST', 'callback' => [$this, 'send_push_notification_api'], 'permission_callback' => $admin_permission_callback]);
        register_rest_route($this->namespace, '/notifications', ['methods' => 'GET', 'callback' => [$this, 'get_user_notifications'], 'permission_callback' => $permission_callback]);
        register_rest_route($this->namespace, '/notifications/unread-count', ['methods' => 'GET', 'callback' => [$this, 'get_unread_notifications_count'], 'permission_callback' => $permission_callback]);
        register_rest_route($this->namespace, '/notifications/mark-read', ['methods' => 'POST', 'callback' => [$this, 'mark_notifications_as_read'], 'permission_callback' => $permission_callback]);
        register_rest_route($this->namespace, '/notices/active', ['methods' => 'GET', 'callback' => [$this, 'get_active_notices'], 'permission_callback' => $permission_callback]);
        register_rest_route($this->namespace, '/notices/all', ['methods' => 'GET', 'callback' => [$this, 'get_all_notices'], 'permission_callback' => $admin_permission_callback]);
        register_rest_route($this->namespace, '/notices/update', ['methods' => 'POST', 'callback' => [$this, 'update_notice'], 'permission_callback' => $admin_permission_callback]);
        register_rest_route($this->namespace, '/notices/(?P<id>\d+)', ['methods' => 'DELETE', 'callback' => [$this, 'delete_notice'], 'permission_callback' => $admin_permission_callback]);
        
        // BOARD MANAGEMENT
        register_rest_route($this->namespace, '/board/departments', [['methods' => 'GET', 'callback' => [$this, 'get_board_departments'], 'permission_callback' => $permission_callback], ['methods' => 'POST', 'callback' => [$this, 'add_department'], 'permission_callback' => $admin_permission_callback]]);
        register_rest_route($this->namespace, '/board/departments/update-order', ['methods' => 'POST', 'callback' => [$this, 'update_department_order'], 'permission_callback' => $admin_permission_callback]);
        register_rest_route($this->namespace, '/board/departments/(?P<id>\d+)', [['methods' => 'POST', 'callback' => [$this, 'update_department'], 'permission_callback' => $admin_permission_callback], ['methods' => 'DELETE', 'callback' => [$this, 'delete_department'], 'permission_callback' => $admin_permission_callback]]);
        register_rest_route($this->namespace, '/board/members/(?P<department_id>\d+)', ['methods' => 'GET', 'callback' => [$this, 'get_members_by_department'], 'permission_callback' => $permission_callback]);
        register_rest_route($this->namespace, '/board/manage/members', ['methods' => 'GET', 'callback' => [$this, 'get_all_board_members_for_admin'], 'permission_callback' => $admin_permission_callback]);
        register_rest_route($this->namespace, '/board/manage/add-member', ['methods' => 'POST', 'callback' => [$this, 'add_board_member'], 'permission_callback' => $admin_permission_callback]);
        register_rest_route($this->namespace, '/board/manage/update-member/(?P<id>\d+)', ['methods' => 'POST', 'callback' => [$this, 'update_board_member'], 'permission_callback' => $admin_permission_callback]);
        register_rest_route($this->namespace, '/board/manage/remove-member/(?P<id>\d+)', ['methods' => 'DELETE', 'callback' => [$this, 'remove_board_member'], 'permission_callback' => $admin_permission_callback]);
        
        // PATIENTS
        register_rest_route($this->namespace, '/patients', ['methods' => 'GET', 'callback' => [$this, 'get_patients_list'], 'permission_callback' => $admin_permission_callback]);
        register_rest_route($this->namespace, '/patients/submit', ['methods' => 'POST', 'callback' => [$this, 'submit_patient_record'], 'permission_callback' => $admin_permission_callback]);
        register_rest_route($this->namespace, '/patients/(?P<id>\d+)', ['methods' => 'DELETE', 'callback' => [$this, 'delete_patient_record'], 'permission_callback' => $admin_permission_callback]);
        
        // ACCOUNTS
        $specific_accounts_permission = [$this, 'validate_specific_event_account_permission'];
        register_rest_route($this->namespace, '/accounts/summary', ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_accounts_summary'], 'permission_callback' => $specific_accounts_permission]);
        register_rest_route($this->namespace, '/accounts/transactions', ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_account_transactions'], 'permission_callback' => $specific_accounts_permission]);
        register_rest_route($this->namespace, '/accounts/income', ['methods' => 'POST', 'callback' => [$this, 'add_income_record'], 'permission_callback' => $specific_accounts_permission]);
        register_rest_route($this->namespace, '/accounts/expense', ['methods' => 'POST', 'callback' => [$this, 'add_expense_record'], 'permission_callback' => $specific_accounts_permission]);
        register_rest_route($this->namespace, '/accounts/transactions/(?P<id>\d+)', [['methods' => 'POST', 'callback' => [$this, 'update_transaction'], 'permission_callback' => $admin_permission_callback], ['methods' => 'DELETE', 'callback' => [$this, 'delete_transaction'], 'permission_callback' => $admin_permission_callback]]);
        
        // ACCOUNTING EVENTS
        register_rest_route($this->namespace, '/accounting-events', [
            ['methods' => 'GET', 'callback' => [$this, 'get_accounting_events'], 'permission_callback' => $accounts_permission_callback],
            ['methods' => 'POST', 'callback' => [$this, 'create_accounting_event'], 'permission_callback' => $accounts_permission_callback]]);
        
        // DAILY TASKS
        register_rest_route($this->namespace, '/daily-tasks/submit', ['methods' => 'POST', 'callback' => [$this, 'submit_daily_task'], 'permission_callback' => $permission_callback]);
        register_rest_route($this->namespace, '/daily-tasks/my-tasks', ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_my_daily_tasks'], 'permission_callback' => $permission_callback]);
        register_rest_route($this->namespace, '/daily-tasks/all-tasks', ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_all_daily_tasks'], 'permission_callback' => $admin_permission_callback]);
        
        // ATTENDANCE
        register_rest_route($this->namespace, '/attendance/status', ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_attendance_status'], 'permission_callback' => $attendance_permission]);
        register_rest_route($this->namespace, '/attendance/mark', ['methods' => 'POST', 'callback' => [$this, 'mark_attendance'], 'permission_callback' => $attendance_permission]);
        register_rest_route($this->namespace, '/attendance/all', ['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_all_attendance'], 'permission_callback' => $admin_permission_callback]);
    }
    
    public function validate_jwt_permission(WP_REST_Request $request) {
        $auth_header = $request->get_header('Authorization');
        if (!$auth_header) { return new WP_Error('jwt_auth_no_token', 'Authorization token not found.', ['status' => 403]); }
        
        list($token) = sscanf($auth_header, 'Bearer %s');
        if (!$token) { return new WP_Error('jwt_auth_bad_auth_header', 'Authorization header format is invalid.', ['status' => 403]); }
        
        if (!defined('JWT_AUTH_SECRET_KEY')) { return new WP_Error('jwt_auth_no_secret_key', 'JWT secret key is not defined.', ['status' => 500]); }
        
        try {
            if (!class_exists('\Firebase\JWT\JWT')) {
                $jwt_path = BBDC_DM_PATH . 'vendor/firebase/php-jwt/src/JWT.php';
                $key_path = BBDC_DM_PATH . 'vendor/firebase/php-jwt/src/Key.php';
                if (file_exists($jwt_path) && file_exists($key_path)) {
                    require_once $jwt_path;
                    require_once $key_path;
                } else {
                    return new WP_Error('jwt_auth_library_not_found', 'JWT library not found.', ['status' => 500]);
                }
            }
            $secret_key = JWT_AUTH_SECRET_KEY;
            $decoded_token = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($secret_key, 'HS256'));
            $user_id = $decoded_token->data->user->id;
            $user = get_user_by('id', $user_id);

            if (!$user) { return new WP_Error('jwt_auth_invalid_user', 'User from token not found.', ['status' => 403]); }
            
            wp_set_current_user($user_id);
            return true;

        } catch (Exception $e) {
            return new WP_Error('jwt_auth_invalid_token', $e->getMessage(), ['status' => 403]);
        }
    }

    public function validate_admin_permission(WP_REST_Request $request) {
        $is_valid_jwt = $this->validate_jwt_permission($request);
        if (is_wp_error($is_valid_jwt)) {
            return $is_valid_jwt;
        }
        if (!current_user_can('manage_options')) {
            return new WP_Error('rest_forbidden', 'You do not have permission to perform this action.', ['status' => 403]);
        }
        return true;
    }

    public function get_current_user_details(WP_REST_Request $request) {
        $user = wp_get_current_user();
        if (!$user->ID) {
            return new WP_Error('not_logged_in', 'User is not logged in.', ['status' => 401]);
        }
    
        $user_meta = get_user_meta($user->ID);
        $get_meta = function($key, $is_single = true) use ($user_meta) {
            return $user_meta[$key][0] ?? null;
        };
    
        $response = [
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'display_name' => $user->display_name,
            'roles' => $user->roles,
            'capabilities' => $user->allcaps,
            'profile_pic_url' => $get_meta('profile_pic_url'),
            'blood_group' => $get_meta('blood_group'),
            'training_session' => $get_meta('training_session'),
            'joining_date' => $get_meta('joining_date'),
            'approval_status' => $get_meta('bbdc_approval_status'),
        ];
        return new WP_REST_Response($response, 200);
    }
    
    public function handle_registration(WP_REST_Request $request) {
        $params = $request->get_json_params();
        
        $required_fields = [
            'full_name', 'father_name', 'mother_name', 'birth_date', 'blood_group', 
            'mobile', 'alt_phone', 'guardian_phone', 'email', 'fb_link', 
            'present_address', 'permanent_address', 'education', 'class_dept', 
            'semester_year', 'skills', 'referer', 'nid_no', 'joining_date', 'username', 'password', 'password_confirm',
            'document_type'
        ];
        foreach ($required_fields as $field) {
            if (empty($params[$field])) {
                return new WP_Error('empty_fields', 'Please fill in all required fields.', ['status' => 400]);
            }
        }
        if ($params['password'] !== $params['password_confirm']) {
            return new WP_Error('password_mismatch', 'The passwords do not match.', ['status' => 400]);
        }
        
        $doc_type = sanitize_text_field($params['document_type']);
        if (empty($params['profile_photo_base64'])) {
            return new WP_Error('empty_files', 'Profile Photo is required.', ['status' => 400]);
        }
        if ($doc_type === 'nid' && (empty($params['nid_front_base64']) || empty($params['nid_back_base64']))) {
            return new WP_Error('empty_files', 'Both sides of NID are required.', ['status' => 400]);
        }
        if ($doc_type === 'birth_certificate' && empty($params['nid_front_base64'])) {
            return new WP_Error('empty_files', 'Birth certificate scan is required.', ['status' => 400]);
        }

        $username = sanitize_user($params['username']);
        $email = sanitize_email($params['email']);
        $mobile = sanitize_text_field($params['mobile']);
        if (username_exists($username)) { return new WP_Error('username_exists', 'This username is already taken.', ['status' => 409]); }
        if (email_exists($email)) { return new WP_Error('email_exists', 'This email is already registered.', ['status' => 409]); }
        if (!empty(get_users(['meta_key' => 'bbdc_mobile_number', 'meta_value' => $mobile]))) { return new WP_Error('mobile_exists', 'This mobile number is already registered.', ['status' => 409]); }
        
        if (!function_exists('bbdc_dm_handle_base64_upload')) {
            function bbdc_dm_handle_base64_upload($base64_string, $file_name) {
                if (empty($base64_string) || empty($file_name)) return new WP_Error('upload_error', 'File data or name is empty.');
                $decoded_file = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64_string));
                $upload = wp_upload_bits($file_name, null, $decoded_file);
                if (!empty($upload['error'])) return new WP_Error('upload_error', $upload['error']);
                return $upload['url'];
            }
        }
        
        $profile_pic_url = bbdc_dm_handle_base64_upload($params['profile_photo_base64'], $params['profile_photo_name']);
        if (is_wp_error($profile_pic_url)) return $profile_pic_url;
        
        $doc_front_url = bbdc_dm_handle_base64_upload($params['nid_front_base64'], $params['nid_front_name']);
        if (is_wp_error($doc_front_url)) return $doc_front_url;
        
        $doc_back_url = null;
        if ($doc_type === 'nid') {
            $doc_back_url = bbdc_dm_handle_base64_upload($params['nid_back_base64'], $params['nid_back_name']);
            if (is_wp_error($doc_back_url)) return $doc_back_url;
        }

        $full_name = sanitize_text_field($params['full_name']);
        $name_parts = explode(' ', $full_name, 2);
        $userdata = [
            'user_login' => $username, 'user_email' => $email, 'user_pass' => $params['password'], 
            'first_name' => $name_parts[0], 'last_name' => $name_parts[1] ?? '', 'display_name' => $full_name, 'role' => 'volunteer'
        ];
        $user_id = wp_insert_user($userdata);
        if (is_wp_error($user_id)) return new WP_Error('registration_failed', $user_id->get_error_message(), ['status' => 500]);
        
        update_user_meta($user_id, 'billing_phone', $mobile);
        update_user_meta($user_id, 'bbdc_approval_status', 'pending');
        update_user_meta($user_id, 'bbdc_mobile_number', $mobile);
        
        $meta_fields = ['father_name', 'mother_name', 'birth_date', 'blood_group', 'alt_phone', 'guardian_phone', 'fb_link', 'present_address', 'permanent_address', 'education', 'class_dept', 'semester_year', 'training_session', 'skills', 'referer', 'nid_number' => 'nid_no'];
        foreach ($meta_fields as $meta_key => $param_key) {
            if (is_numeric($meta_key)) { $meta_key = $param_key; }
            if (isset($params[$param_key])) {
                $value = in_array($meta_key, ['present_address', 'permanent_address', 'skills']) ? sanitize_textarea_field($params[$param_key]) : sanitize_text_field($params[$param_key]);
                update_user_meta($user_id, $meta_key, $value);
            }
        }
        
        update_user_meta($user_id, 'profile_pic_url', esc_url_raw($profile_pic_url));
        update_user_meta($user_id, 'document_type', $doc_type);
        update_user_meta($user_id, 'nid_front_url', esc_url_raw($doc_front_url));
        if ($doc_back_url) update_user_meta($user_id, 'nid_back_url', esc_url_raw($doc_back_url));
        
        return new WP_REST_Response(['success' => true, 'message' => 'Registration successful! Your account is pending review.'], 201);
    }
    
    public function get_donors_list(WP_REST_Request $request) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bbdc_donors';
        
        $sql = "SELECT * FROM {$table_name}";
        $where = [];
        $params = [];
    
        if ($s = $request->get_param('s')) {
            $search_term = '%' . $wpdb->esc_like(sanitize_text_field($s)) . '%';
            $where[] = "(donor_name LIKE %s OR mobile_number LIKE %s)";
            $params[] = $search_term;
            $params[] = $search_term;
        }
        if ($blood_group = $request->get_param('blood_group')) { 
            $where[] = "blood_group = %s";
            $params[] = $blood_group;
        }
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        $sql .= " ORDER BY last_donation_date DESC";
        $results = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
    
        $sanitized_results = [];
        if (!empty($results)) {
            foreach ($results as $row) {
                $sanitized_row = [];
                foreach ($row as $key => $value) {
                    $sanitized_row[$key] = is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'UTF-8') : $value;
                }
                $sanitized_results[] = $sanitized_row;
            }
        }
        
        $user = wp_get_current_user();
        $is_admin = user_can($user, 'manage_options');
    
        if (!$is_admin) {
            foreach ($sanitized_results as &$donor) {
                unset($donor['mobile_number']);
            }
        }
        
        return new WP_REST_Response($sanitized_results, 200);
    }
    
    public function get_volunteers_list(WP_REST_Request $request) {
        $status = $request->get_param('status') ?? 'approved';
        $search_query = sanitize_text_field($request->get_param('s'));
    
        $args = [
            'orderby' => 'display_name',
            'order' => 'ASC',
        ];
    
        if (!empty($search_query)) {
            $args['search'] = '*' . esc_attr($search_query) . '*';
            $args['search_columns'] = ['user_login', 'user_email', 'display_name'];
        }
    
        $user_ids = [];
    
        if ($status === 'pending') {
            $args['role__in'] = ['volunteer', 'bbdc_admin', 'blood_response'];
            $args['meta_query'] = [['key' => 'bbdc_approval_status', 'value' => 'pending']];
            $user_query = new WP_User_Query($args);
            $users = $user_query->get_results();
        } else { // Approved
            // 1. Get approved volunteers/bbdc_admins/blood_response
            $approved_args = $args;
            $approved_args['role__in'] = ['volunteer', 'bbdc_admin', 'blood_response'];
            $approved_args['meta_query'] = [
                'relation' => 'AND',
                ['key' => 'bbdc_approval_status', 'value' => 'approved']
            ];
            $approved_volunteers_query = new WP_User_Query($approved_args);
            foreach ($approved_volunteers_query->get_results() as $user) {
                $user_ids[] = $user->ID;
            }
    
            // 2. Get administrators (they are always approved)
            $admin_args = $args;
            $admin_args['role'] = 'administrator';
            $admin_query = new WP_User_Query($admin_args);
            foreach ($admin_query->get_results() as $user) {
                $user_ids[] = $user->ID;
            }
    
            // Get unique user objects
            $unique_user_ids = array_unique($user_ids);
            if (empty($unique_user_ids)) {
                 $users = [];
            } else {
                 $users = get_users(['include' => $unique_user_ids, 'orderby' => 'display_name', 'order' => 'ASC']);
            }
        }
    
        $response_data = [];
        foreach ($users as $user) {
            $response_data[] = [
                'id' => $user->ID,
                'display_name' => $user->display_name,
                'roles' => $user->roles,
                'mobile' => get_user_meta($user->ID, 'bbdc_mobile_number', true),
                'profile_pic_url' => get_user_meta($user->ID, 'profile_pic_url', true),
                'training_session' => get_user_meta($user->ID, 'training_session', true),
                'fb_link' => get_user_meta($user->ID, 'fb_link', true),
            ];
        }
        
        return new WP_REST_Response($response_data, 200);
    }

    public function get_volunteer_details(WP_REST_Request $request) {
        $user_id = (int) $request['id'];
        $user_data = get_userdata($user_id);
        if (!$user_data) { return new WP_Error('not_found', 'Volunteer not found.', ['status' => 404]); }
        
        $meta = get_user_meta($user_id);
        $get_meta = function($key) use ($meta) { return $meta[$key][0] ?? ''; };
        
        $response = [
            'id' => $user_id, 
            'username' => $user_data->user_login,
            'display_name' => $user_data->display_name, 
            'email' => $user_data->user_email, 
            'roles' => $user_data->roles, 
            'mobile' => get_user_meta($user_id, 'bbdc_mobile_number', true), 
            'blood_group' => $get_meta('blood_group'), 
            'training_session' => $get_meta('training_session'), 
            'joining_date' => $get_meta('joining_date'), 
            'profile_pic_url' => $get_meta('profile_pic_url'), 
            'nid_front_url' => $get_meta('nid_front_url'), 
            'nid_back_url' => $get_meta('nid_back_url'), 
            'first_name' => $user_data->first_name, 
            'last_name' => $user_data->last_name, 
            'father_name' => $get_meta('father_name'), 
            'mother_name' => $get_meta('mother_name'), 
            'birth_date' => $get_meta('birth_date'), 
            'alt_phone' => $get_meta('alt_phone'), 
            'guardian_phone' => $get_meta('guardian_phone'), 
            'fb_link' => $get_meta('fb_link'), 
            'present_address' => $get_meta('present_address'), 
            'permanent_address' => $get_meta('permanent_address'), 
            'education' => $get_meta('education'), 
            'class_dept' => $get_meta('class_dept'), 
            'semester_year' => $get_meta('semester_year'), 
            'skills' => $get_meta('skills'), 
            'referer' => $get_meta('referer'), 
            'nid_number' => $get_meta('nid_number'),
        ];
        return new WP_REST_Response($response, 200);
    }
    
    public function set_volunteer_role(WP_REST_Request $request) {
        $user_id = (int) $request['id'];
        $params = $request->get_json_params();
        $new_roles = isset($params['roles']) && is_array($params['roles']) ? array_map('sanitize_key', $params['roles']) : [];

        if (empty($user_id)) {
            return new WP_Error('bad_request', 'Invalid user ID.', ['status' => 400]);
        }
        
        if (!current_user_can('promote_user', $user_id)) {
             return new WP_Error('rest_forbidden', 'You do not have permission to promote this user.', ['status' => 403]);
        }
        
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return new WP_Error('not_found', 'User not found.', ['status' => 404]);
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

        return new WP_REST_Response(['success' => true, 'message' => 'Volunteer roles updated successfully.'], 200);
    }
    
    public function send_push_notification_api(WP_REST_Request $request) {
        $params = $request->get_json_params();
        $title = sanitize_text_field($params['title']);
        $body = sanitize_textarea_field($params['body']);
        $screen = !empty($params['screen']) ? sanitize_text_field($params['screen']) : '/';
        
        $tokens_with_users = bbdc_get_all_fcm_tokens_with_user_ids();
        if (empty($tokens_with_users)) {
            return new WP_Error('no_devices', 'No registered devices found.', ['status' => 404]);
        }
        
        $success_count = 0;
        $processed_users = [];
        $data_payload = ['screen' => $screen, 'click_action' => 'FLUTTER_NOTIFICATION_CLICK'];
        
        global $wpdb;
        foreach ($tokens_with_users as $token_obj) {
            if (!in_array($token_obj->user_id, $processed_users)) {
                $wpdb->insert($wpdb->prefix . 'bbdc_notifications', [
                    'user_id' => $token_obj->user_id, 'title' => $title, 'body' => $body, 'data_payload' => json_encode($data_payload)
                ]);
                $processed_users[] = $token_obj->user_id;
            }

            $result = bbdc_send_fcm_v1_notification($token_obj->fcm_token, $title, $body, $data_payload);
            if ($result === true) {
                $success_count++;
            }
        }

        if ($success_count > 0) {
            return new WP_REST_Response(['success' => true, 'message' => "Notification sent to {$success_count} devices."], 200);
        } else {
            return new WP_Error('fcm_failed_all', 'Failed to send notification to any device.', ['status' => 500]);
        }
    }

    public function get_user_notifications(WP_REST_Request $request) {
        global $wpdb;
        $user_id = get_current_user_id();
        $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}bbdc_notifications WHERE user_id = %d ORDER BY created_at DESC LIMIT 100", $user_id));
        return new WP_REST_Response($results, 200);
    }

    public function get_unread_notifications_count(WP_REST_Request $request) {
        global $wpdb;
        $user_id = get_current_user_id();
        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM {$wpdb->prefix}bbdc_notifications WHERE user_id = %d AND read_at IS NULL", $user_id));
        return new WP_REST_Response(['count' => (int)$count], 200);
    }

    public function mark_notifications_as_read(WP_REST_Request $request) {
        global $wpdb;
        $user_id = get_current_user_id();
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}bbdc_notifications SET read_at = NOW() WHERE user_id = %d AND read_at IS NULL", $user_id));
        return new WP_REST_Response(['success' => true, 'message' => 'All notifications marked as read.'], 200);
    }
    
    public function get_active_notices(WP_REST_Request $request) {
        global $wpdb;
        $current_time = current_time('mysql');
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT id, notice_subject, notice_content, created_at, deadline FROM {$wpdb->prefix}bbdc_notices WHERE is_active = 1 AND (deadline IS NULL OR deadline > %s) ORDER BY created_at DESC",
            $current_time
        ));
        return new WP_REST_Response($results, 200);
    }

    public function get_all_notices(WP_REST_Request $request) {
        global $wpdb;
        $results = $wpdb->get_results("SELECT id, notice_subject, notice_content, created_at FROM {$wpdb->prefix}bbdc_notices ORDER BY created_at DESC");
        return new WP_REST_Response($results, 200);
    }

    public function update_notice(WP_REST_Request $request) {
        global $wpdb;
        $params = $request->get_json_params();
        $subject = sanitize_text_field($params['subject']);
        $content = sanitize_textarea_field($params['content']);

        if (empty($subject) || empty($content)) {
            return new WP_Error('empty_content', 'Subject and Content cannot be empty.', ['status' => 400]);
        }
        
        $data = [
            'notice_subject' => $subject,
            'notice_content' => $content,
            'is_active' => 1
        ];

        if (isset($params['deadline']) && !empty($params['deadline'])) {
            $data['deadline'] = sanitize_text_field($params['deadline']);
        }
        
        $result = $wpdb->insert($wpdb->prefix . 'bbdc_notices', $data);

        if ($result === false) {
            error_log('BBDC Notice Insert DB Error: ' . $wpdb->last_error);
            return new WP_Error('db_error', 'Could not save notice to the database. DB Error: ' . $wpdb->last_error, ['status' => 500]);
        }

        return new WP_REST_Response(['success' => true, 'message' => 'Notice published successfully.'], 201);
        
        $all_users = get_users(['role__in' => ['administrator', 'bbdc_admin', 'volunteer']]);
        $recipient_ids = [];
        foreach ($all_users as $user) {
            if (get_user_meta($user->ID, 'bbdc_approval_status', true) !== 'pending') {
                $recipient_ids[] = $user->ID;
            }
        }
        bbdc_send_notification_to_users($recipient_ids, $subject, "A new notice has been published by BBDC.");

        return new WP_REST_Response(['success' => true, 'message' => 'Notice published successfully.'], 201);
    }

    public function get_referrers_list(WP_REST_Request $request) {
        global $wpdb;
        $manual_referrers = $wpdb->get_results("SELECT id, referrer_name FROM {$wpdb->prefix}bbdc_referrers ORDER BY referrer_name ASC", ARRAY_A);

        $volunteer_args = [
            'role__in'   => ['volunteer', 'bbdc_admin', 'administrator'],
            'meta_key'   => 'bbdc_approval_status',
            'meta_value' => 'approved',
            'fields'     => ['ID', 'display_name']
        ];
        $volunteer_users = get_users($volunteer_args);
        
        $all_referrers = [];
        $unique_names = [];

        // Add manual referrers
        foreach($manual_referrers as $ref) {
            if (!in_array(strtolower($ref['referrer_name']), $unique_names)) {
                $all_referrers[] = [
                    'id' => (int)$ref['id'],
                    'referrer_name' => $ref['referrer_name']
                ];
                $unique_names[] = strtolower($ref['referrer_name']);
            }
        }
        
        // Add approved volunteers
        foreach ($volunteer_users as $user) {
            if (!in_array(strtolower($user->display_name), $unique_names)) {
                $all_referrers[] = [
                    'id' => (int)$user->ID,
                    'referrer_name' => $user->display_name
                ];
                $unique_names[] = strtolower($user->display_name);
            }
        }
        
        // Sort the combined list alphabetically by name
        usort($all_referrers, function($a, $b) {
            return strcasecmp($a['referrer_name'], $b['referrer_name']);
        });

        return new WP_REST_Response($all_referrers, 200);
    }

    public function add_referrer(WP_REST_Request $request) {
        global $wpdb;
        $params = $request->get_json_params();
        $name = sanitize_text_field($params['referrer_name']);
        if (empty($name)) {
            return new WP_Error('bad_request', 'Referrer name is required.', ['status' => 400]);
        }
        $result = $wpdb->insert($wpdb->prefix . 'bbdc_referrers', ['referrer_name' => $name]);
        if ($result === false) {
             return new WP_Error('db_error', 'Could not save the referrer.', ['status' => 500]);
        }
        return new WP_REST_Response(['success' => true, 'message' => 'Referrer added.'], 201);
    }

    public function delete_referrer(WP_REST_Request $request) {
        global $wpdb;
        $id = (int)$request['id'];
        $wpdb->delete($wpdb->prefix . 'bbdc_referrers', ['id' => $id]);
        return new WP_REST_Response(['success' => true, 'message' => 'Referrer deleted.'], 200);
    }

    public function handle_donation_submission(WP_REST_Request $request) {
        global $wpdb;
        $params = $request->get_json_params();
    
        if (empty($params['donor_mobile']) || !is_numeric($params['donor_mobile'])) {
            return new WP_Error('invalid_mobile', 'A valid mobile number is required.', ['status' => 400]);
        }
        
        $history_data = [
            'donor_name' => sanitize_text_field($params['donor_name']),  
            'donor_mobile' => sanitize_text_field($params['donor_mobile']),  
            'blood_group' => sanitize_text_field($params['blood_group']),
            'donation_date' => sanitize_text_field($params['donation_date']),
            
            'is_bbdc_donation' => !empty($params['is_bbdc_donation']) ? 1 : 0,
            
            'referrer_name' => !empty($params['is_bbdc_donation']) ? sanitize_text_field($params['referrer_name']) : null,  
            'status' => 'pending',  
            'form_data' => json_encode($params),
            'submitted_by_user_id' => get_current_user_id()
        ];
        
        $result = $wpdb->insert($wpdb->prefix . 'bbdc_donation_history', $history_data);
        
        if ($result) {
            return new WP_REST_Response(['success' => true, 'message' => 'Donation record submitted for review.'], 201);
        }
        return new WP_Error('db_error', 'Could not save donation record.', ['status' => 500]);
    }

    public function get_history_list(WP_REST_Request $request) {
        global $wpdb;
        $type = $request->get_param('type') ?? 'all';
        
        $sql_select = "SELECT h.*, u.display_name as submitted_by";
        $sql_from = " FROM {$wpdb->prefix}bbdc_donation_history h LEFT JOIN {$wpdb->prefix}users u ON h.submitted_by_user_id = u.ID";
        
        $where = [];
        $params = [];
    
        // Base filter for the page type
        if ($type == 'pending') {
            $where[] = "h.status = %s";
            $params[] = 'pending';
        } elseif ($type == 'bbdc') {
            $where[] = "h.status = %s AND h.is_bbdc_donation = 1";
            $params[] = 'approved';
        } elseif ($type == 'others') {
            $where[] = "h.status = %s AND h.is_bbdc_donation = 0";
            $params[] = 'approved';
        }
    
        // User-submitted filters from the app
        if ($mobile = $request->get_param('mobile')) {
            $where[] = "h.donor_mobile LIKE %s";
            $params[] = '%' . $wpdb->esc_like(sanitize_text_field($mobile)) . '%';
        }
        if ($blood_group = $request->get_param('bloodGroup')) {
            $where[] = "h.blood_group = %s";
            $params[] = sanitize_text_field($blood_group);
        }
        if ($type !== 'others' && ($referrer = $request->get_param('referrer'))) {
            $where[] = "h.referrer_name = %s";
            $params[] = sanitize_text_field($referrer);
        }
        if ($date_from = $request->get_param('dateFrom')) {
            $where[] = "h.donation_date >= %s";
            $params[] = sanitize_text_field($date_from);
        }
        if ($date_to = $request->get_param('dateTo')) {
            $where[] = "h.donation_date <= %s";
            $params[] = sanitize_text_field($date_to);
        }
    
        $where_clause = !empty($where) ? " WHERE " . implode(' AND ', $where) : "";
        
        $sql = $sql_select . $sql_from . $where_clause . " ORDER BY h.id DESC";
        
        $results = $wpdb->get_results($wpdb->prepare($sql, $params));
        
        if (!current_user_can('manage_options')) {
            foreach ($results as $key => $result) {
                unset($results[$key]->submitted_by);
            }
        }
    
        return new WP_REST_Response($results, 200);
    }

    public function get_all_approved_volunteers(WP_REST_Request $request) {
        $args = ['role__in' => ['volunteer', 'bbdc_admin', 'administrator'], 'meta_query' => [['key' => 'bbdc_approval_status', 'value' => 'approved']], 'orderby' => 'display_name', 'order' => 'ASC'];
        $users = get_users($args);
        $response = array_map(function($user) {
            return ['id' => $user->ID, 'display_name' => $user->display_name];
        }, $users);
        return new WP_REST_Response($response, 200);
    }

    public function update_donor_profile(WP_REST_Request $request) {
        global $wpdb;
        $donor_id = (int)$request['id'];
        $params = $request->get_json_params();
        $data = [];
        $allowed_fields = ['donor_name', 'blood_group', 'age', 'donor_location', 'emergency_phone', 'present_address', 'permanent_address', 'nid_birth_id', 'occupation', 'date_of_birth'];
        foreach ($allowed_fields as $field) {
            if (isset($params[$field])) {
                $data[$field] = sanitize_text_field($params[$field]);
            }
        }
        if (!empty($data)) {
            $wpdb->update($wpdb->prefix . 'bbdc_donors', $data, ['id' => $donor_id]);
        }
        return new WP_REST_Response(['success' => true, 'message' => 'Donor profile updated.'], 200);
    }

    public function send_donor_sms(WP_REST_Request $request) {
        $donor_id = (int)$request['id'];
        global $wpdb;
        $donor = $wpdb->get_row($wpdb->prepare("SELECT donor_name, mobile_number FROM {$wpdb->prefix}bbdc_donors WHERE id = %d", $donor_id));
    
        if (!$donor) {
            return new WP_Error('not_found', 'Donor not found.', ['status' => 404]);
        }
    
        $template = get_option('bbdc_sms_template', 'Thank you, {donor_name}, for your valuable blood donation! - BBDC');
        $message = str_replace('{donor_name}', $donor->donor_name, $template);
        
        $result = bbdc_send_ssl_sms($donor->mobile_number, $message);
    
        if (is_wp_error($result)) {
            return new WP_Error($result->get_error_code(), $result->get_error_message(), ['status' => 400]);
        }
    
        return new WP_REST_Response(['success' => true, 'message' => 'SMS sent successfully to ' . $donor->donor_name], 200);
    }
    
    public function approve_donation(WP_REST_Request $request) {
        global $wpdb;
        $history_id = (int)$request['id'];
        $history_table = $wpdb->prefix . 'bbdc_donation_history';
        $donors_table = $wpdb->prefix . 'bbdc_donors';

        $donation = $wpdb->get_row($wpdb->prepare("SELECT * FROM $history_table WHERE id = %d", $history_id));
        if (!$donation) {
            return new WP_Error('not_found', 'Pending donation record not found.', ['status' => 404]);
        }
        
        $form_data = json_decode($donation->form_data, true);
        $donor_mobile_from_history = $donation->donor_mobile;

        if (empty($donor_mobile_from_history) || !is_numeric($donor_mobile_from_history) || strlen($donor_mobile_from_history) < 11) {
            return new WP_Error('invalid_data', 'Approval Failed: Record is missing a valid 11-digit Mobile Number.', ['status' => 400]);
        }
        
        $existing_donor = $wpdb->get_row($wpdb->prepare("SELECT id, total_donations FROM $donors_table WHERE mobile_number = %s", $donor_mobile_from_history));
        $wpdb->update($history_table, ['status' => 'approved'], ['id' => $history_id]);
        
        $data = [
            'donor_name'         => sanitize_text_field($form_data['donor_name']),
            'blood_group'        => sanitize_text_field($form_data['blood_group']),
            'mobile_number'      => $donor_mobile_from_history,
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
            return new WP_Error('db_error', 'DB Error: Could not update/insert donor record.', ['status' => 500]);
        }
        
        bbdc_send_approval_notification_to_submitter($history_id);

        return new WP_REST_Response(['success' => true, 'message' => 'Donation approved and notifications sent.'], 200);
    }
    
    public function reject_donation(WP_REST_Request $request) {
        global $wpdb;
        $history_id = (int)$request['id'];
        $wpdb->delete($wpdb->prefix . 'bbdc_donation_history', ['id' => $history_id]);
        return new WP_REST_Response(['success' => true, 'message' => 'Donation record rejected and removed.'], 200);
    }

  public function approve_volunteer(WP_REST_Request $request) {
      $user_id = (int)$request['id'];
      update_user_meta($user_id, 'bbdc_approval_status', 'approved');
      return new WP_REST_Response(['success' => true, 'message' => 'Volunteer approved.'], 200);
  }

    public function reject_volunteer(WP_REST_Request $request) {
        require_once(ABSPATH.'wp-admin/includes/user.php');
        $user_id = (int)$request['id'];
        wp_delete_user($user_id);
        return new WP_REST_Response(['success' => true, 'message' => 'Volunteer rejected and deleted.'], 200);
    }

    public function get_my_attended_events(WP_REST_Request $request) {
        global $wpdb;
        $user_id = get_current_user_id();
        $category = $request->get_param('category');
    
        $sql = "SELECT c.id, c.campaign_name, c.campaign_date, c.venue, c.description 
                FROM {$wpdb->prefix}bbdc_campaigns c 
                JOIN {$wpdb->prefix}bbdc_campaign_attendance a ON c.id = a.campaign_id 
                WHERE a.volunteer_user_id = %d";
    
        if (!empty($category)) {
            $sql .= $wpdb->prepare(" AND c.event_category = %s", $category);
        }
        $sql .= " ORDER BY c.campaign_date DESC";
        $results = $wpdb->get_results($wpdb->prepare($sql, $user_id));
        return new WP_REST_Response($results, 200);
    }

    public function get_my_personal_donations(WP_REST_Request $request) {
        global $wpdb;
        $user_id = get_current_user_id();
        $mobile = get_user_meta($user_id, 'bbdc_mobile_number', true);
        if (empty($mobile)) { return new WP_REST_Response([], 200); }
        $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}bbdc_donation_history WHERE donor_mobile = %s AND status = 'approved' ORDER BY donation_date DESC", $mobile));
        return new WP_REST_Response($results, 200);
    }

    public function get_my_referred_donations(WP_REST_Request $request) {
        global $wpdb;
        $user = wp_get_current_user();
        $results = $wpdb->get_results($wpdb->prepare("SELECT donor_name, donor_mobile, donation_date FROM {$wpdb->prefix}bbdc_donation_history WHERE referrer_name = %s AND status = 'approved' ORDER BY donation_date DESC", $user->display_name));
        return new WP_REST_Response($results, 200);
    }

    public function get_events(WP_REST_Request $request) {
        global $wpdb;
        $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bbdc_campaigns ORDER BY campaign_date DESC");
        return new WP_REST_Response($results, 200);
    }

    public function create_event(WP_REST_Request $request) {
        global $wpdb;
        $params = $request->get_json_params();
        $result = $wpdb->insert($wpdb->prefix . 'bbdc_campaigns', [
            'campaign_name' => sanitize_text_field($params['event_name']),
            'event_category' => sanitize_text_field($params['event_category']),
            'campaign_date' => sanitize_text_field($params['event_date']),
            'venue' => sanitize_textarea_field($params['venue']),
            'description' => sanitize_textarea_field($params['description'])
        ]);
        if ($result === false) { return new WP_Error('db_error', 'Could not create event.', ['status' => 500]); }
        return new WP_REST_Response(['success' => true, 'message' => 'Event created.'], 201);
    }

    public function get_event_details(WP_REST_Request $request) {
        global $wpdb;
        $event_id = (int)$request['id'];
        $event = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}bbdc_campaigns WHERE id = %d", $event_id));
        if (!$event) { return new WP_Error('not_found', 'Event not found.', ['status' => 404]); }
        
        if (current_user_can('manage_options')) {
            $event->attendees = $wpdb->get_col($wpdb->prepare("SELECT volunteer_user_id FROM {$wpdb->prefix}bbdc_campaign_attendance WHERE campaign_id = %d", $event_id));
            $event->guests = $wpdb->get_col($wpdb->prepare("SELECT guest_name FROM {$wpdb->prefix}bbdc_campaign_guests WHERE campaign_id = %d ORDER BY guest_name ASC", $event_id));
        } else {
            $event->attendees = [];
            $event->guests = [];
        }
    
        return new WP_REST_Response($event, 200);
    }
    
    public function update_event(WP_REST_Request $request) {
    global $wpdb;
    $event_id = (int)$request['id'];
    $params = $request->get_json_params();

    $data_to_update = [];
    if (isset($params['event_name'])) $data_to_update['campaign_name'] = sanitize_text_field($params['event_name']);
    if (isset($params['event_category'])) $data_to_update['event_category'] = sanitize_text_field($params['event_category']);
    if (isset($params['event_date'])) $data_to_update['campaign_date'] = sanitize_text_field($params['event_date']);
    if (isset($params['venue'])) $data_to_update['venue'] = sanitize_textarea_field($params['venue']);
    if (isset($params['description'])) $data_to_update['description'] = sanitize_textarea_field($params['description']);

    if (empty($data_to_update)) {
        return new WP_Error('bad_request', 'No data provided for update.', ['status' => 400]);
    }

    $result = $wpdb->update($wpdb->prefix . 'bbdc_campaigns', $data_to_update, ['id' => $event_id]);

    if ($result === false) {
        return new WP_Error('db_error', 'Could not update event details.', ['status' => 500]);
    }
    return new WP_REST_Response(['success' => true, 'message' => 'Event details updated successfully.'], 200);
    }

    public function save_event_attendance(WP_REST_Request $request) {
        global $wpdb;
        $event_id = (int)$request['id'];
        $params = $request->get_json_params();
        $attendees = isset($params['attendees']) ? array_map('intval', $params['attendees']) : [];
        $guests = isset($params['guests']) ? array_map('sanitize_text_field', $params['guests']) : [];

        $wpdb->delete($wpdb->prefix . 'bbdc_campaign_attendance', ['campaign_id' => $event_id]);
        $wpdb->delete($wpdb->prefix . 'bbdc_campaign_guests', ['campaign_id' => $event_id]);
        
        foreach ($attendees as $user_id) {
            $wpdb->insert($wpdb->prefix . 'bbdc_campaign_attendance', ['campaign_id' => $event_id, 'volunteer_user_id' => $user_id]);
        }
        
        foreach ($guests as $guest_name) {
            if (!empty($guest_name)) {
                $wpdb->insert($wpdb->prefix . 'bbdc_campaign_guests', ['campaign_id' => $event_id, 'guest_name' => $guest_name]);
            }
        }
        
        return new WP_REST_Response(['success' => true, 'message' => 'Attendance saved.'], 200);
    }
    
    public function save_fcm_token(WP_REST_Request $request) {
        global $wpdb;
        $user_id = get_current_user_id();
        $params = $request->get_json_params();
        $token = sanitize_text_field($params['fcm_token']);
        if (empty($token)) { return new WP_Error('bad_request', 'FCM token is required.', ['status' => 400]); }

        $table = $wpdb->prefix . 'bbdc_fcm_tokens';
        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE fcm_token = %s", $token));
        if ($existing) {
            $wpdb->update($table, ['user_id' => $user_id, 'created_at' => current_time('mysql', 1)], ['id' => $existing]);
        } else {
            $wpdb->insert($table, ['user_id' => $user_id, 'fcm_token' => $token]);
        }
        return new WP_REST_Response(['success' => true, 'message' => 'Token saved.'], 200);
    }
    
    public function get_settings(WP_REST_Request $request) {
        $settings = [
            'bbdc_sms_api_token' => get_option('bbdc_sms_api_token', ''),
            'bbdc_sms_sid' => get_option('bbdc_sms_sid', ''),
            'bbdc_sms_template' => get_option('bbdc_sms_template', 'Thank you, {donor_name}, for your valuable blood donation! - BBDC'),
        ];
        return new WP_REST_Response($settings, 200);
    }
    
    public function update_settings(WP_REST_Request $request) {
        $params = $request->get_json_params();
        
        if (isset($params['bbdc_sms_api_token'])) {
            update_option('bbdc_sms_api_token', sanitize_text_field($params['bbdc_sms_api_token']));
        }
        if (isset($params['bbdc_sms_sid'])) {
            update_option('bbdc_sms_sid', sanitize_text_field($params['bbdc_sms_sid']));
        }
        if (isset($params['sms_template'])) { // Note: app sends 'sms_template' key for this one
            update_option('bbdc_sms_template', sanitize_textarea_field($params['sms_template']));
        }
        
        return new WP_REST_Response(['success' => true, 'message' => 'Settings updated.'], 200);
    }
    
    public function update_volunteer_profile(WP_REST_Request $request) {
        $user_id = (int) $request['id'];
        $current_user_id = get_current_user_id();
        $is_admin = current_user_can('manage_options');
    
        // Security Check: Only admins or the user themselves can edit the profile.
        if (!$is_admin && $current_user_id !== $user_id) {
            return new WP_Error('rest_forbidden', 'You do not have permission to update this profile.', ['status' => 403]);
        }
        
        $user_data = get_userdata($user_id);
        if (!$user_data) {
            return new WP_Error('not_found', 'Volunteer not found.', ['status' => 404]);
        }
        
        $params = $request->get_json_params();
        if (empty($params)) {
            return new WP_Error('no_data', 'No data provided for update.', ['status' => 400]);
        }
    
        // Fields a regular volunteer can edit for their OWN profile
        $volunteer_updatable_meta = [
            'alt_phone', 'guardian_phone', 'fb_link', 'present_address', 'education', 
            'class_dept', 'semester_year', 'skills', 'profile_pic_url',
            'joining_date'
        ];
        
        // Fields ONLY an admin can edit
        $admin_updatable_meta = [
            'father_name','mother_name','birth_date','blood_group',
            'permanent_address', 'training_session', 'referer', 'nid_number', 
            'nid_front_url', 'nid_back_url'
        ];
    
        // Core user fields (like name and email)
        $core_data = ['ID' => $user_id];
        if (isset($params['display_name'])) {
            $core_data['display_name'] = sanitize_text_field($params['display_name']);
        }
        if ($is_admin && isset($params['email'])) { 
            $core_data['user_email'] = sanitize_email($params['email']);
        }
        if (count($core_data) > 1) {
            wp_update_user($core_data);
        }
        
        // Determine which fields to update based on user role
        $fields_to_update = $volunteer_updatable_meta;
        if ($is_admin) {
            $fields_to_update = array_merge($volunteer_updatable_meta, $admin_updatable_meta);
        }
    
        // Update meta fields
        foreach ($fields_to_update as $meta_key) {
            if (isset($params[$meta_key])) {
                $value = in_array($meta_key, ['present_address', 'permanent_address', 'skills']) 
                    ? sanitize_textarea_field($params[$meta_key]) 
                    : sanitize_text_field($params[$meta_key]);
                update_user_meta($user_id, $meta_key, $value);
            }
        }
        
        return new WP_REST_Response(['success' => true, 'message' => 'Profile updated successfully.'], 200);
    }

    public function get_board_departments(WP_REST_Request $request) {
        global $wpdb;
        $results = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}bbdc_departments ORDER BY sort_order ASC, name ASC");
        return new WP_REST_Response($results, 200);
    }

    public function add_department(WP_REST_Request $request) {
        global $wpdb;
        $params = $request->get_json_params();
        $name = sanitize_text_field($params['name']);
        if (empty($name)) {
            return new WP_Error('bad_request', 'Department name is required.', ['status' => 400]);
        }
        $result = $wpdb->insert($wpdb->prefix . 'bbdc_departments', ['name' => $name]);
        if ($result === false) {
            return new WP_Error('db_error', 'Could not save the department to the database.', ['status' => 500]);
        }
        return new WP_REST_Response(['success' => true, 'message' => 'Department added.'], 201);
    }

    public function update_department(WP_REST_Request $request) {
        global $wpdb;
        $id = (int)$request['id'];
        $params = $request->get_json_params();
        $name = sanitize_text_field($params['name']);
        if (empty($name)) {
            return new WP_Error('bad_request', 'Department name is required.', ['status' => 400]);
        }
        $result = $wpdb->update($wpdb->prefix . 'bbdc_departments', ['name' => $name], ['id' => $id]);
        if ($result === false) {
            return new WP_Error('db_error', 'Could not update the department.', ['status' => 500]);
        }
        return new WP_REST_Response(['success' => true, 'message' => 'Department updated.'], 200);
    }

    public function delete_department(WP_REST_Request $request) {
        global $wpdb;
        $id = (int)$request['id'];
        $member_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM {$wpdb->prefix}bbdc_board_members WHERE department_id = %d", $id));
        if ($member_count > 0) {
            return new WP_Error('in_use', 'Cannot delete department. It has members assigned to it.', ['status' => 400]);
        }
        $wpdb->delete($wpdb->prefix . 'bbdc_departments', ['id' => $id]);
        return new WP_REST_Response(['success' => true, 'message' => 'Department deleted.'], 200);
    }

    public function get_members_by_department(WP_REST_Request $request) {
        global $wpdb;
        $department_id = (int)$request['department_id'];
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT bm.id, bm.user_id, u.display_name, bm.designation, bm.is_chief, 
                    um_bg.meta_value as blood_group, um_pic.meta_value as profile_pic_url, um_fb.meta_value as fb_link
             FROM {$wpdb->prefix}bbdc_board_members as bm
             JOIN {$wpdb->prefix}users as u ON bm.user_id = u.ID
             LEFT JOIN {$wpdb->prefix}usermeta as um_bg ON u.ID = um_bg.user_id AND um_bg.meta_key = 'blood_group'
             LEFT JOIN {$wpdb->prefix}usermeta as um_pic ON u.ID = um_pic.user_id AND um_pic.meta_key = 'profile_pic_url'
             LEFT JOIN {$wpdb->prefix}usermeta as um_fb ON u.ID = um_fb.user_id AND um_fb.meta_key = 'fb_link'
             WHERE bm.department_id = %d
             ORDER BY bm.is_chief DESC, bm.sort_order ASC, u.display_name ASC", $department_id
        ));
        return new WP_REST_Response($results, 200);
    }

    public function get_all_board_members_for_admin(WP_REST_Request $request) {
        global $wpdb;
        $results = $wpdb->get_results(
            "SELECT bm.id, bm.user_id, u.display_name, bm.designation, bm.is_chief, d.name as department_name
             FROM {$wpdb->prefix}bbdc_board_members as bm
             JOIN {$wpdb->prefix}users as u ON bm.user_id = u.ID
             JOIN {$wpdb->prefix}bbdc_departments as d ON bm.department_id = d.id
             ORDER BY d.name ASC, bm.is_chief DESC, u.display_name ASC"
        );
        return new WP_REST_Response($results, 200);
    }

    public function add_board_member(WP_REST_Request $request) {
        global $wpdb;
        $params = $request->get_json_params();
        $result = $wpdb->insert($wpdb->prefix . 'bbdc_board_members', [
            'user_id' => intval($params['user_id']),
            'department_id' => intval($params['department_id']),
            'designation' => sanitize_text_field($params['designation']),
            'is_chief' => intval($params['is_chief']),
        ]);
        if ($result === false) { return new WP_Error('db_error', 'Could not add board member.', ['status' => 500]); }
        return new WP_REST_Response(['success' => true, 'message' => 'Board member added.'], 201);
    }
    
    public function update_board_member(WP_REST_Request $request) {
        global $wpdb;
        $id = (int)$request['id'];
        $params = $request->get_json_params();
        $result = $wpdb->update($wpdb->prefix . 'bbdc_board_members', [
            'user_id' => intval($params['user_id']),
            'department_id' => intval($params['department_id']),
            'designation' => sanitize_text_field($params['designation']),
            'is_chief' => intval($params['is_chief']),
        ], ['id' => $id]);
        if ($result === false) { return new WP_Error('db_error', 'Could not update board member.', ['status' => 500]); }
        return new WP_REST_Response(['success' => true, 'message' => 'Board member updated.'], 200);
    }
    
    public function remove_board_member(WP_REST_Request $request) {
        global $wpdb;
        $id = (int)$request['id'];
        $wpdb->delete($wpdb->prefix . 'bbdc_board_members', ['id' => $id]);
        return new WP_REST_Response(['success' => true, 'message' => 'Board member removed.'], 200);
    }
    
    public function delete_donor(WP_REST_Request $request) {
        global $wpdb;
        $donor_id = (int)$request['id'];
        $donors_table = $wpdb->prefix . 'bbdc_donors';
        $history_table = $wpdb->prefix . 'bbdc_donation_history';

        // First, get the donor's mobile number to delete their history
        $donor_mobile = $wpdb->get_var($wpdb->prepare("SELECT mobile_number FROM $donors_table WHERE id = %d", $donor_id));

        if ($donor_mobile) {
            // Delete all history records associated with this mobile number
            $wpdb->delete($history_table, ['donor_mobile' => $donor_mobile]);
        }

        // Finally, delete the donor record itself
        $wpdb->delete($donors_table, ['id' => $donor_id]);
        
        return new WP_REST_Response(['success' => true, 'message' => 'Donor and all related history have been deleted.'], 200);
    }

    public function delete_history_record(WP_REST_Request $request) {
        global $wpdb;
        $history_id = (int)$request['id'];
        $history_table = $wpdb->prefix . 'bbdc_donation_history';

        $wpdb->delete($history_table, ['id' => $history_id]);
        
        return new WP_REST_Response(['success' => true, 'message' => 'Donation record deleted.'], 200);
    }
    
        /**
     * Get list of registered patients for the app.
     */
public function get_patients_list(WP_REST_Request $request) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bbdc_registered_patients';
        
        $search = sanitize_text_field($request->get_param('s'));
        $disease = sanitize_text_field($request->get_param('disease'));

        $sql = "SELECT p.*, u.display_name as submitter_name 
                FROM {$table_name} p 
                LEFT JOIN {$wpdb->prefix}users u ON p.submitted_by_user_id = u.ID";
        
        $where = [];
        $params = [];

        if (!empty($search)) {
            $where[] = "(p.patient_name LIKE %s OR p.mobile_number LIKE %s)";
            $params[] = '%' . $wpdb->esc_like($search) . '%';
            $params[] = '%' . $wpdb->esc_like($search) . '%';
        }

        if (!empty($disease)) {
            $where[] = "p.disease = %s";
            $params[] = $disease;
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " ORDER BY p.id DESC";
        
        $results = $wpdb->get_results($wpdb->prepare($sql, $params));
        
        return new WP_REST_Response($results, 200);
    }

    /**
     * Submit a new patient record from the app.
     */
    public function submit_patient_record(WP_REST_Request $request) {
        global $wpdb;
        $params = $request->get_json_params();
        $table_name = $wpdb->prefix . 'bbdc_registered_patients';

        if (empty($params['patient_name']) || empty($params['mobile_number']) || empty($params['blood_group']) || empty($params['disease'])) {
            return new WP_Error('bad_request', 'Name, Mobile, Blood Group and Disease are required fields.', ['status' => 400]);
        }
        
        $data = [
            'patient_name'        => sanitize_text_field($params['patient_name']),
            'father_name'         => isset($params['father_name']) ? sanitize_text_field($params['father_name']) : null,
            'mother_name'         => isset($params['mother_name']) ? sanitize_text_field($params['mother_name']) : null,
            'age'                 => isset($params['age']) && is_numeric($params['age']) ? intval($params['age']) : null,
            'address'             => isset($params['address']) ? sanitize_textarea_field($params['address']) : null,
            'occupation'          => isset($params['occupation']) ? sanitize_text_field($params['occupation']) : null,
            'guardian_occupation' => isset($params['guardian_occupation']) ? sanitize_text_field($params['guardian_occupation']) : null,
            'disease'             => sanitize_text_field($params['disease']),
            'blood_group'         => sanitize_text_field($params['blood_group']),
            'monthly_blood_need'  => isset($params['monthly_blood_need']) && is_numeric($params['monthly_blood_need']) ? intval($params['monthly_blood_need']) : null,
            'mobile_number'       => sanitize_text_field($params['mobile_number']),
            'other_info'          => isset($params['other_info']) ? sanitize_textarea_field($params['other_info']) : null,
            'image_url'           => isset($params['image_url']) ? esc_url_raw($params['image_url']) : null,
            'submitted_by_user_id' => get_current_user_id(),
        ];

        $result = $wpdb->insert($table_name, $data);

        if ($result) {
            return new WP_REST_Response(['success' => true, 'message' => 'Patient record submitted successfully.'], 201);
        }
        error_log('BBDC Patient Insert Error: ' . $wpdb->last_error);
        return new WP_Error('db_error', 'Could not save patient record.', ['status' => 500]);
    }

    /**
     * Delete a patient record (admin only).
     */
    public function delete_patient_record(WP_REST_Request $request) {
        global $wpdb;
        $patient_id = (int)$request['id'];
        $table = $wpdb->prefix . 'bbdc_registered_patients';
        
        $wpdb->delete($table, ['id' => $patient_id]);
        
        return new WP_REST_Response(['success' => true, 'message' => 'Patient record deleted.'], 200);
    }
    
    public function update_department_order(WP_REST_Request $request) {
    global $wpdb;
    $params = $request->get_json_params();
    $ordered_ids = isset($params['order']) ? array_map('intval', $params['order']) : [];

    if (empty($ordered_ids)) {
        return new WP_Error('bad_request', 'Ordered IDs are required.', ['status' => 400]);
    }

    $table = $wpdb->prefix . 'bbdc_departments';
    foreach ($ordered_ids as $index => $department_id) {
        $wpdb->update(
            $table,
            ['sort_order' => $index],
            ['id' => $department_id]
        );
    }

    return new WP_REST_Response(['success' => true, 'message' => 'Department order updated successfully.'], 200);
}

        public function delete_notice(WP_REST_Request $request) {
            global $wpdb;
            $notice_id = (int)$request['id'];
            $table = $wpdb->prefix . 'bbdc_notices';
    
            $result = $wpdb->delete($table, ['id' => $notice_id]);
    
            if ($result === false) {
                return new WP_Error('db_error', 'Could not delete notice from database.', ['status' => 500]);
            }
            if ($result === 0) {
                return new WP_Error('not_found', 'Notice with the given ID was not found.', ['status' => 404]);
            }
            
            return new WP_REST_Response(['success' => true, 'message' => 'Notice deleted successfully.'], 200);
        }

        public function validate_accounts_permission(WP_REST_Request $request) {
        $is_valid_jwt = $this->validate_jwt_permission($request);
        if (is_wp_error($is_valid_jwt)) {
            return $is_valid_jwt;
        }
        if (!function_exists('bbdc_can_manage_accounts') || !bbdc_can_manage_accounts()) {
            return new WP_Error('rest_forbidden', 'You do not have permission to manage accounts.', ['status' => 403]);
        }
        return true;
    }

        public function get_accounts_summary(WP_REST_Request $request) {
            global $wpdb;
            $table = $wpdb->prefix . 'bbdc_accounts_transactions';
            $event_id = $request->get_param('event_id');
        
            $where_clause = $event_id ? $wpdb->prepare("WHERE event_id = %d", $event_id) : "WHERE event_id IS NULL";
        
            $total_income = (float) $wpdb->get_var("SELECT SUM(amount) FROM $table WHERE transaction_type = 'income' AND " . ($event_id ? "event_id = %d" : "event_id IS NULL"), $event_id);
            $total_expense = (float) $wpdb->get_var("SELECT SUM(amount) FROM $table WHERE transaction_type = 'expense' AND " . ($event_id ? "event_id = %d" : "event_id IS NULL"), $event_id);
        
            return new WP_REST_Response([
                'total_income' => $total_income,
                'total_expense' => $total_expense,
                'current_balance' => $total_income - $total_expense,
            ], 200);
        }
        
        public function get_account_transactions(WP_REST_Request $request) {
            global $wpdb;
            $table = $wpdb->prefix . 'bbdc_accounts_transactions';
            $event_id = $request->get_param('event_id');
            $where_clause = $event_id ? $wpdb->prepare("WHERE t.event_id = %d", $event_id) : "WHERE t.event_id IS NULL";
        
            $results = $wpdb->get_results("
                SELECT t.*, u.display_name as entered_by 
                FROM {$table} t
                LEFT JOIN {$wpdb->prefix}users u ON t.entered_by_user_id = u.ID
                {$where_clause}
                ORDER BY t.transaction_date DESC, t.id DESC
                LIMIT 100
            ");
            return new WP_REST_Response($results, 200);
        }
        
        public function add_income_record(WP_REST_Request $request) {
            global $wpdb;
            $params = $request->get_json_params();
            $table = $wpdb->prefix . 'bbdc_accounts_transactions';
        
            $wpdb->insert($table, [
                'event_id' => isset($params['event_id']) ? intval($params['event_id']) : null,
                'transaction_type' => 'income',
                'source' => sanitize_text_field($params['source']),
                'amount' => floatval($params['amount']),
                'remarks' => isset($params['remarks']) ? sanitize_textarea_field($params['remarks']) : null,
                'transaction_date' => sanitize_text_field($params['transaction_date']),
                'entered_by_user_id' => get_current_user_id()
            ]);
        
            return new WP_REST_Response(['success' => true], 201);
        }
        
        public function add_expense_record(WP_REST_Request $request) {
            global $wpdb;
            $params = $request->get_json_params();
        
            if (empty($params['memo_url'])) {
                return new WP_Error('bad_request', 'Memo/Receipt is required for expenses.', ['status' => 400]);
            }
        
            $table = $wpdb->prefix . 'bbdc_accounts_transactions';
        
            $wpdb->insert($table, [
                'event_id' => isset($params['event_id']) ? intval($params['event_id']) : null,
                'transaction_type' => 'expense',
                'source' => sanitize_text_field($params['source']),
                'amount' => floatval($params['amount']),
                'memo_url' => esc_url_raw($params['memo_url']),
                'remarks' => isset($params['remarks']) ? sanitize_textarea_field($params['remarks']) : null,
                'transaction_date' => sanitize_text_field($params['transaction_date']),
                'entered_by_user_id' => get_current_user_id()
            ]);
        
            return new WP_REST_Response(['success' => true], 201);
        }
    
    public function submit_daily_task(WP_REST_Request $request) {
        global $wpdb;
        $params = $request->get_json_params();
        if (empty($params['description'])) {
            return new WP_Error('bad_request', 'Description is required.', ['status' => 400]);
        }
        
        $wpdb->insert($wpdb->prefix . 'bbdc_daily_tasks', [
            'user_id' => get_current_user_id(),
            'task_description' => sanitize_textarea_field($params['description']),
        ]);
        
        return new WP_REST_Response(['success' => true], 201);
    }

    public function get_my_daily_tasks(WP_REST_Request $request) {
        global $wpdb;
        $user_id = get_current_user_id();
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}bbdc_daily_tasks WHERE user_id = %d ORDER BY created_at DESC",
            $user_id
        ));
        return new WP_REST_Response($results, 200);
    }

    public function get_all_daily_tasks(WP_REST_Request $request) {
        global $wpdb;
        $results = $wpdb->get_results("
            SELECT dt.*, u.display_name 
            FROM {$wpdb->prefix}bbdc_daily_tasks dt
            JOIN {$wpdb->prefix}users u ON dt.user_id = u.ID
            ORDER BY dt.created_at DESC"
        );
        return new WP_REST_Response($results, 200);
    }
    
    public function validate_attendance_permission(WP_REST_Request $request) {
        $is_valid_jwt = $this->validate_jwt_permission($request);
        if (is_wp_error($is_valid_jwt)) return $is_valid_jwt;

        if (current_user_can('manage_options')) {
            return true;
        }

        $user = wp_get_current_user();
        if (!in_array('blood_response', $user->roles)) {
            return new WP_Error('rest_forbidden', 'Only users with the Blood Response or Admin role can access this.', ['status' => 403]);
        }
        return true;
    }

public function mark_attendance(WP_REST_Request $request) {
        $user_id = get_current_user_id();
        global $wpdb;
        $table = $wpdb->prefix . 'bbdc_attendance_log';
        $params = $request->get_json_params();
        
        $current_hour = (int) current_time('G');
        if ($current_hour < 18) {
            return new WP_Error('too_early', 'Attendance can only be marked after 6:00 PM.', ['status' => 400]);
        }

        $today_start = current_time('Y-m-d') . ' 00:00:00';
        $already_marked = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE user_id = %d AND attendance_time >= %s", $user_id, $today_start));
        if ($already_marked) {
            return new WP_Error('already_marked', 'You have already marked your attendance for today.', ['status' => 409]);
        }

        $description = isset($params['description']) ? sanitize_textarea_field($params['description']) : '';

        $data_to_insert = [
            'user_id' => $user_id, 
            'attendance_time' => current_time('mysql'),
            'activity_description' => $description
        ];
        
        $result = $wpdb->insert($table, $data_to_insert);
        
        if ($result === false) {
            error_log('BBDC Attendance Insert DB Error: ' . $wpdb->last_error);
            return new WP_Error('db_error', 'Could not save attendance to the database.', ['status' => 500]);
        }

        return new WP_REST_Response(['success' => true, 'message' => 'Attendance marked successfully!'], 201);
    }

    public function get_attendance_status(WP_REST_Request $request) {
        $user_id = get_current_user_id();
        global $wpdb;
        $today_start = current_time('Y-m-d') . ' 00:00:00';
        $record = $wpdb->get_row($wpdb->prepare("SELECT attendance_time FROM {$wpdb->prefix}bbdc_attendance_log WHERE user_id = %d AND attendance_time >= %s", $user_id, $today_start));
        
        if ($record) {
            return new WP_REST_Response(['status' => 'marked', 'time' => $record->attendance_time], 200);
        }
        return new WP_REST_Response(['status' => 'not_marked'], 200);
    }

    public function get_all_attendance(WP_REST_Request $request) {
        global $wpdb;
        $sql = "SELECT al.*, u.display_name FROM {$wpdb->prefix}bbdc_attendance_log al JOIN {$wpdb->prefix}users u ON al.user_id = u.ID";

        $where = [];
        if ($date_from = $request->get_param('date_from')) {
            $where[] = $wpdb->prepare("DATE(al.attendance_time) >= %s", sanitize_text_field($date_from));
        }
        if ($date_to = $request->get_param('date_to')) {
            $where[] = $wpdb->prepare("DATE(al.attendance_time) <= %s", sanitize_text_field($date_to));
        }
        if ($user_id = $request->get_param('user_id')) {
            $where[] = $wpdb->prepare("al.user_id = %d", intval($user_id));
        }
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " ORDER BY al.attendance_time DESC";
        $results = $wpdb->get_results($sql);
        return new WP_REST_Response($results, 200);
    }
    
    public function get_minimum_app_version(WP_REST_Request $request) {
        $min_version_name = get_option('bbdc_min_app_version_name', '1.0.0'); 
        $min_version_code = get_option('bbdc_min_app_version_code', '1'); 
        $apk_download_url = get_option('bbdc_apk_download_url', ''); 
        $maintenance_enabled = get_option('bbdc_maintenance_mode_enabled', 0);
        $maintenance_title = get_option('bbdc_maintenance_title', 'Under Maintenance');
        $maintenance_message = get_option('bbdc_maintenance_message', 'The app is temporarily unavailable. Please try again later.');

        return new WP_REST_Response([
            'min_version_name' => $min_version_name,
            'min_version_code' => (int)$min_version_code,
            'apk_url'          => $apk_download_url,
            'maintenance_mode_enabled' => (bool)$maintenance_enabled,
            'maintenance_title'        => $maintenance_title,
            'maintenance_message'      => $maintenance_message,
        ], 200);
    }
    
    public function update_transaction(WP_REST_Request $request) {
        global $wpdb;
        $id = (int)$request['id'];
        $params = $request->get_json_params();
        
        $data = [
            'source' => sanitize_text_field($params['source']),
            'amount' => floatval($params['amount']),
            'remarks' => sanitize_textarea_field($params['remarks']),
            'transaction_date' => sanitize_text_field($params['transaction_date']),
        ];

        $wpdb->update($wpdb->prefix . 'bbdc_accounts_transactions', $data, ['id' => $id]);
        return new WP_REST_Response(['success' => true, 'message' => 'Transaction updated.'], 200);
    }

    public function delete_transaction(WP_REST_Request $request) {
        global $wpdb;
        $id = (int)$request['id'];
        $wpdb->delete($wpdb->prefix . 'bbdc_accounts_transactions', ['id' => $id]);
        return new WP_REST_Response(['success' => true, 'message' => 'Transaction deleted.'], 200);
    }
    
        public function handle_lost_password(WP_REST_Request $request) {
        $params = $request->get_json_params();
        $user_login = isset($params['user_login']) ? sanitize_text_field($params['user_login']) : '';

        if (empty($user_login)) {
            return new WP_Error('empty_field', 'Please provide a username or email address.', ['status' => 400]);
        }
        
        $result = retrieve_password($user_login);

        if (is_wp_error($result)) {
            return new WP_REST_Response([
                'success' => true,
                'message' => 'If a matching account was found, an email has been sent to the associated address.'
            ], 200);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => 'If a matching account was found, an email has been sent to the associated address.'
        ], 200);
    }
    
    public function get_app_info(WP_REST_Request $request) {
        $response_data = [
            'latest_version_code'      => get_option('bbdc_app_latest_version_code', '1'),
            'apk_url'                  => get_option('bbdc_app_apk_url', ''),
            'maintenance_mode_enabled' => (bool) get_option('bbdc_maintenance_mode_enabled', false),
            'maintenance_title'        => get_option('bbdc_maintenance_title', 'Under Maintenance'),
            'maintenance_message'      => get_option('bbdc_maintenance_message', 'The app is currently undergoing maintenance. Please try again later.'),
        ];
        return new WP_REST_Response($response_data, 200);
    }
    
    public function check_if_user_is_admin(WP_REST_Request $request) {
        $user = wp_get_current_user();
        
        // --- Start Debugging ---
        if (!$user || !$user->ID) {
            error_log('BBDC DEBUG [Permission]: User not found or not logged in.');
            return false;
        }
        
        $user_roles_string = implode(', ', (array) $user->roles);
        error_log('BBDC DEBUG [Permission]: Checking user ID ' . $user->ID . ' with roles: [' . $user_roles_string . ']');
        // --- End Debugging ---

        $user_roles = (array) $user->roles;
        $is_admin = in_array('administrator', $user_roles) || in_array('bbdc_admin', $user_roles);
        
        error_log('BBDC DEBUG [Permission]: Permission check result: ' . ($is_admin ? 'GRANTED' : 'DENIED'));

        return $is_admin;
    }

    public function check_if_user_can_view_data(WP_REST_Request $request) {
        return is_user_logged_in();
    }
    
    // NOTES

    public function create_note(WP_REST_Request $request) {
        global $wpdb;
        $user_id = get_current_user_id();
        $params = $request->get_json_params();
        $title = sanitize_text_field($params['title']);
        $note_type = isset($params['note_type']) && $params['note_type'] === 'checklist' ? 'checklist' : 'text';
        $note_color = isset($params['note_color']) ? sanitize_hex_color($params['note_color']) : null;
        $content = $params['content'] ?? '';
    
        if ($note_type === 'checklist') {
            $content = base64_encode(wp_unslash($content));
        } else {
            $content = sanitize_textarea_field($content);
        }
    
        if (empty($title)) {
            return new WP_Error('bad_request', 'Title is required.', ['status' => 400]);
        }
    
        $table_name = $wpdb->prefix . 'bbdc_notes';
    
        $result = $wpdb->insert($table_name, [
            'owner_user_id' => $user_id,
            'title'         => $title,
            'content'       => $content,
            'created_at'    => current_time('mysql'),
            'updated_at'    => current_time('mysql'),
        ]);
    
        if ($result === false) {
            return new WP_Error('db_error', 'Could not create note (Step 1). DB Error: ' . $wpdb->last_error, ['status' => 500]);
        }
        
        $note_id = $wpdb->insert_id;
    
        if ($note_id > 0) {
            $wpdb->update(
                $table_name,
                [
                    'note_type'  => $note_type,
                    'note_color' => $note_color
                ],
                ['id' => $note_id]
            );
        }
        
        $new_note = $wpdb->get_row($wpdb->prepare("SELECT n.*, 'owner' as permission, u.display_name as owner_name FROM {$table_name} n LEFT JOIN {$wpdb->users} u ON n.owner_user_id = u.ID WHERE n.id = %d", $note_id));
        
        if ($new_note && $new_note->note_type === 'checklist') {
            $new_note->content = base64_decode($new_note->content);
        }
    
        return new WP_REST_Response($new_note, 201);
    }
    
    public function update_note(WP_REST_Request $request) {
        global $wpdb;
        $note_id = (int)$request['id'];
        $user_id = get_current_user_id();
        $permission = bbdc_get_user_note_permission($note_id, $user_id);
    
        if ($permission !== 'owner' && $permission !== 'edit') {
            return new WP_Error('rest_forbidden', 'You do not have permission to edit this note.', ['status' => 403]);
        }
    
        $params = $request->get_json_params();
        $data = [];
        if (isset($params['title'])) $data['title'] = sanitize_text_field($params['title']);
        if (isset($params['note_color'])) $data['note_color'] = sanitize_hex_color($params['note_color']);
        if (isset($params['note_type'])) $data['note_type'] = $params['note_type'] === 'checklist' ? 'checklist' : 'text';
    
        if (isset($params['content'])) {
            $note_type_to_check = $data['note_type'] ?? ($wpdb->get_var($wpdb->prepare("SELECT note_type FROM {$wpdb->prefix}bbdc_notes WHERE id = %d", $note_id)) ?: 'text');
            if ($note_type_to_check === 'checklist') {
                $data['content'] = base64_encode(wp_unslash($params['content']));
            } else {
                $data['content'] = sanitize_textarea_field($params['content']);
            }
        }
    
        if (!empty($data)) {
            $result = $wpdb->update($wpdb->prefix . 'bbdc_notes', $data, ['id' => $note_id]);
            if ($result === false) {
                 return new WP_Error('db_error', 'Could not update note. DB Error: ' . $wpdb->last_error, ['status' => 500]);
            }
        }
    
        return new WP_REST_Response(['success' => true, 'message' => 'Note updated.'], 200);
    }
    
    public function get_notes(WP_REST_Request $request) {
        global $wpdb;
        $user_id = get_current_user_id();
        $notes_table = $wpdb->prefix . 'bbdc_notes';
        $permissions_table = $wpdb->prefix . 'bbdc_note_permissions';
    
        $query = $wpdb->prepare(
            " (SELECT n.*, 'owner' as permission, u.display_name as owner_name FROM $notes_table n
               LEFT JOIN {$wpdb->users} u ON n.owner_user_id = u.ID
               WHERE n.owner_user_id = %d)
              UNION
              (SELECT n.*, np.permission_level as permission, u.display_name as owner_name FROM $notes_table n
               JOIN $permissions_table np ON n.id = np.note_id
               LEFT JOIN {$wpdb->users} u ON n.owner_user_id = u.ID
               WHERE np.user_id = %d)
              ORDER BY updated_at DESC",
            $user_id, $user_id
        );
    
        $results = $wpdb->get_results($query);
    
        foreach ($results as $note) {
            if ($note->note_type === 'checklist') {
                $note->content = base64_decode($note->content);
            }
        }
    
        return new WP_REST_Response($results, 200);
    }
    
    public function delete_note(WP_REST_Request $request) {
        global $wpdb;
        $note_id = (int)$request['id'];
        $user_id = get_current_user_id();
    
        // Only the owner can delete
        $permission = bbdc_get_user_note_permission($note_id, $user_id);
        if ($permission !== 'owner') {
            return new WP_Error('rest_forbidden', 'Only the owner can delete this note.', ['status' => 403]);
        }
    
        $wpdb->delete($wpdb->prefix . 'bbdc_notes', ['id' => $note_id]);
        $wpdb->delete($wpdb->prefix . 'bbdc_note_permissions', ['note_id' => $note_id]);
    
        return new WP_REST_Response(['success' => true, 'message' => 'Note deleted.'], 200);
    }
    
    public function get_note_shares(WP_REST_Request $request) {
        global $wpdb;
        $note_id = (int)$request['id'];
        $user_id = get_current_user_id();
    
        // Check if the current user has permission to view shares (must be owner)
        if (bbdc_get_user_note_permission($note_id, $user_id) !== 'owner') {
             return new WP_Error('rest_forbidden', 'Only the note owner can manage sharing.', ['status' => 403]);
        }
    
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT np.user_id, np.permission_level, u.display_name
             FROM {$wpdb->prefix}bbdc_note_permissions np
             JOIN {$wpdb->users} u ON np.user_id = u.ID
             WHERE np.note_id = %d",
            $note_id
        ));
    
        return new WP_REST_Response($results, 200);
    }
    
    public function update_note_shares(WP_REST_Request $request) {
        global $wpdb;
        $note_id = (int)$request['id'];
        $user_id = get_current_user_id();
        $params = $request->get_json_params();
        $shares = $params['shares'] ?? []; // Expecting an array of ['user_id' => X, 'permission_level' => 'view/edit']
    
        if (bbdc_get_user_note_permission($note_id, $user_id) !== 'owner') {
            return new WP_Error('rest_forbidden', 'Only the note owner can manage sharing.', ['status' => 403]);
        }
    
        // Clear existing permissions for this note
        $wpdb->delete($wpdb->prefix . 'bbdc_note_permissions', ['note_id' => $note_id]);
    
        // Insert new permissions
        foreach ($shares as $share) {
            $share_user_id = (int)$share['user_id'];
            $permission_level = in_array($share['permission_level'], ['view', 'edit']) ? $share['permission_level'] : 'view';
            
            // Don't allow sharing with the owner
            if ($share_user_id !== $user_id) {
                $wpdb->insert($wpdb->prefix . 'bbdc_note_permissions', [
                    'note_id' => $note_id,
                    'user_id' => $share_user_id,
                    'permission_level' => $permission_level
                ]);
            }
        }
    
        return new WP_REST_Response(['success' => true, 'message' => 'Sharing settings updated.'], 200);
    }
    
    public function delete_event(WP_REST_Request $request) {
    global $wpdb;
    $event_id = (int)$request['id'];

    if (empty($event_id)) {
        return new WP_Error('bad_request', 'Invalid Event ID.', ['status' => 400]);
    }

    $wpdb->delete($wpdb->prefix . 'bbdc_campaigns', ['id' => $event_id]);
    $wpdb->delete($wpdb->prefix . 'bbdc_campaign_attendance', ['campaign_id' => $event_id]);
    $wpdb->delete($wpdb->prefix . 'bbdc_campaign_guests', ['campaign_id' => $event_id]);

    return new WP_REST_Response(['success' => true, 'message' => 'Event and all related data deleted successfully.'], 200);
    }
    
    public function initiate_payment(WP_REST_Request $request) {
        if (!class_exists('WooCommerce')) {
            return new WP_Error('woocommerce_not_found', 'WooCommerce is not active.', ['status' => 500]);
        }
        
        $params = $request->get_json_params();
        $amount = isset($params['amount']) ? floatval($params['amount']) : 0;
        $fee_month = isset($params['fee_month']) ? sanitize_text_field($params['fee_month']) : '';
        $fee_year = isset($params['fee_year']) ? intval($params['fee_year']) : 0;
    
        if (empty($amount) || $amount <= 0 || empty($fee_month) || empty($fee_year)) {
            return new WP_Error('bad_request', 'A valid amount, fee month, and year are required.', ['status' => 400]);
        }
    
        $user = wp_get_current_user();
        
        $customer_phone = get_user_meta($user->ID, 'billing_phone', true);
        if (empty($customer_phone)) {
            $customer_phone = get_user_meta($user->ID, 'bbdc_mobile_number', true);
        }
        
        if (empty($user->display_name) || empty($customer_phone) || empty($user->user_email)) {
            return new WP_Error('missing_user_details', 'Your profile is incomplete. Please ensure your Name, Email, and Phone Number are set in your profile before making a payment.', ['status' => 400]);
        }
    
        $order = wc_create_order(['customer_id' => $user->ID]);
    
        try {
            $fee_name = 'Monthly Fee for ' . $fee_month . ', ' . $fee_year;
            $fee = new WC_Order_Item_Fee();
            $fee->set_name($fee_name);
            $fee->set_amount($amount);
            $fee->set_tax_class('');
            $fee->set_tax_status('none');
            $fee->set_total($amount);
            $order->add_item($fee);
        } catch (Exception $e) {
            return new WP_Error('fee_error', 'Could not add fee to order.', ['status' => 500]);
        }
        
        $order->set_billing_first_name($user->first_name);
        $order->set_billing_last_name($user->last_name);
        $order->set_billing_email($user->user_email);
        $order->set_billing_phone($customer_phone);
        
        $order->update_meta_data('_bbdc_fee_month', $fee_month);
        $order->update_meta_data('_bbdc_fee_year', $fee_year);
        
        $order->calculate_totals(); // This will now correctly calculate the total
        $order->update_status('pending', 'Order created via BBDC App for ' . $fee_month . ', ' . $fee_year);
        $order_id = $order->get_id();
    
        $merchant_id = get_option('bbdc_paystation_merchant_id');
        $password = get_option('bbdc_paystation_password');
    
        if (empty($merchant_id) || empty($password)) {
            return new WP_Error('config_error', 'PayStation credentials are not set.', ['status' => 500]);
        }
        
        $callback_url = home_url('/wp-json/' . $this->namespace . '/payment/callback');
    
        $payload = [
            'merchantId' => $merchant_id,
            'password' => $password,
            'invoice_number' => $order_id,
            'currency' => 'BDT',
            'payment_amount' => intval($order->get_total()),
            'cust_name' => $user->display_name,
            'cust_phone' => $customer_phone,
            'cust_email' => $user->user_email,
            'callback_url' => $callback_url,
            'reference' => 'BBDC Fee for ' . $fee_month . ', ' . $fee_year
        ];
    
        $response = wp_remote_post('https://api.paystation.com.bd/initiate-payment', ['method' => 'POST', 'body' => $payload, 'timeout' => 45]);
    
        if (is_wp_error($response)) {
            return new WP_Error('api_error', $response->get_error_message(), ['status' => 500]);
        }
    
        $body = json_decode(wp_remote_retrieve_body($response), true);
    
        if (isset($body['status_code']) && $body['status_code'] === '200' && $body['status'] === 'success') {
            $order->add_order_note('Payment initiated with PayStation. User redirected to checkout.');
            $order->save();
            return new WP_REST_Response(['payment_url' => $body['payment_url'], 'order_id' => $order_id], 200);
        } else {
            $order->update_status('failed', 'PayStation initiation failed: ' . ($body['message'] ?? 'Unknown Error'));
            return new WP_Error('gateway_error', $body['message'] ?? 'Failed to initiate payment.', ['status' => 400]);
        }
    }
        
    public function handle_payment_callback(WP_REST_Request $request) {
        $status = sanitize_text_field($request->get_param('status'));
        $invoice_number = (int)$request->get_param('invoice_number');
        $trx_id = sanitize_text_field($request->get_param('trx_id'));
        
        $success_url = 'https://bbdc.org.bd/payment-success/';
        $failure_url = 'https://bbdc.org.bd/payment-failure/';
    
        if (empty($invoice_number)) {
            wp_redirect($failure_url);
            exit;
        }
    
        $order = wc_get_order($invoice_number);
        if (!$order) {
            wp_redirect($failure_url);
            exit;
        }
    
        if ($status === 'Successful') {
            $order->payment_complete($trx_id);
            $order->add_order_note('Payment successful via PayStation. Transaction ID: ' . $trx_id);
            wp_redirect($success_url);
        } else {
            $order->update_status('failed', 'Payment failed or was cancelled by user.');
            wp_redirect($failure_url);
        }
        exit;
    }
    
    public function verify_payment_status(WP_REST_Request $request) {
        $order_id = (int)$request['order_id'];
        $order = wc_get_order($order_id);
    
        if (!$order || $order->get_customer_id() !== get_current_user_id()) {
            return new WP_Error('not_found', 'Order not found or permission denied.', ['status' => 404]);
        }
    
        return new WP_REST_Response(['status' => $order->get_status()], 200);
    }
    
    public function get_my_fee_history(WP_REST_Request $request) {
    if (!class_exists('WooCommerce')) {
        return new WP_Error('woocommerce_not_found', 'WooCommerce is not active.', ['status' => 500]);
    }
    $user_id = get_current_user_id();
    $product_ids = [17190, 17198, 17199, 17200, 17201];

    $orders = wc_get_orders([
        'customer_id' => $user_id,
        'status' => ['wc-completed', 'wc-processing'],
        'limit' => -1,
    ]);

    $fee_history = [];
    foreach ($orders as $order) {
        foreach ($order->get_items() as $item) {
            if (in_array($item->get_product_id(), $product_ids)) {
                $fee_history[] = [
                    'order_id' => $order->get_id(),
                    'amount' => $order->get_total(),
                    'date' => $order->get_date_paid()->date('Y-m-d H:i:s'),
                    'fee_month' => $order->get_meta('_bbdc_fee_month'),
                    'fee_year' => $order->get_meta('_bbdc_fee_year'),
                    'transaction_id' => $order->get_transaction_id(),
                ];
                break; 
            }
        }
    }
    return new WP_REST_Response($fee_history, 200);
    }
    
    public function get_accounting_events(WP_REST_Request $request) {
        global $wpdb;
        $user_id = get_current_user_id();
        $events_table = $wpdb->prefix . 'bbdc_accounting_events';
        $permissions_table = $wpdb->prefix . 'bbdc_accounting_event_permissions';
    
        // If user is an admin, show all events
        if (current_user_can('bbdc_manage_accounts')) {
            $results = $wpdb->get_results("SELECT * FROM $events_table ORDER BY start_date DESC");
        } else {
            // Otherwise, show only events they are assigned to
            $results = $wpdb->get_results($wpdb->prepare(
                "SELECT e.* FROM $events_table e JOIN $permissions_table p ON e.id = p.event_id WHERE p.user_id = %d ORDER BY e.start_date DESC",
                $user_id
            ));
        }
    
        return new WP_REST_Response($results, 200);
    }
    
    /**
     * Checks if a user can manage accounts for a specific event.
     * Access is granted if they have the global 'bbdc_manage_accounts' capability OR are explicitly assigned.
     */
    private function can_user_manage_event_accounts($user_id, $event_id) {
        if (user_can($user_id, 'bbdc_manage_accounts')) {
            return true;
        }
        if (empty($event_id)) {
            return false;
        }
    
        global $wpdb;
        $table = $wpdb->prefix . 'bbdc_accounting_event_permissions';
        $permission = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE user_id = %d AND event_id = %d",
            $user_id,
            $event_id
        ));
    
        return !is_null($permission);
    }
    
    /**
     * New REST API permission callback for event-specific accounting.
     */
    public function validate_specific_event_account_permission(WP_REST_Request $request) {
        $is_jwt_valid = $this->validate_jwt_permission($request);
        if (is_wp_error($is_jwt_valid)) {
            return $is_jwt_valid;
        }
    
        $user_id = get_current_user_id();
        $event_id = $request->get_param('event_id');
    
        // For general accounts (no event_id), check for the global capability
        if (empty($event_id)) {
            if (current_user_can('bbdc_manage_accounts')) {
                return true;
            } else {
                return new WP_Error('rest_forbidden', 'You do not have permission to manage general accounts.', ['status' => 403]);
            }
        }
    
        // For event-specific accounts, check per-event permission
        if ($this->can_user_manage_event_accounts($user_id, $event_id)) {
            return true;
        }
    
        return new WP_Error('rest_forbidden', 'You do not have permission to manage this event account.', ['status' => 403]);
    }
} // End of BBDC_DM_Rest_Api class