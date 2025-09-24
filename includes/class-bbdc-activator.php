<?php
class BBDC_DM_Activator {
    
    public static function activate() {

    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // --- ROLE CREATION ---
    remove_role('volunteer');
    remove_role('bbdc_admin');

    add_role('volunteer', 'Volunteer', [
    'read' => true,
    'upload_files' => true
    ]);
    add_role('blood_response', 'Blood Response', ['read' => true, 'upload_files' => true]);
    add_role('bbdc_admin', 'BBDC Admin', [
    'read' => true,
    'manage_options' => true,
    'promote_users' => true,
    'upload_files' => true
    ]);

    $administrator_role = get_role('administrator');
    if($administrator_role) {
    $administrator_role->add_cap('upload_files');
    }

    // --- DATABASE TABLE CREATION ---
    // Donors Table
    $donors_table = $wpdb->prefix . 'bbdc_donors';
    $sql_donors = "CREATE TABLE $donors_table ( id BIGINT(20) NOT NULL AUTO_INCREMENT, donor_name VARCHAR(100) NOT NULL, mobile_number VARCHAR(15) NOT NULL, blood_group VARCHAR(5) NOT NULL, age INT(3), total_donations INT(5) DEFAULT 0, donor_location TEXT, last_donation_date DATE, emergency_phone VARCHAR(15), present_address TEXT, permanent_address TEXT, nid_birth_id VARCHAR(20), nid_upload_url VARCHAR(255), occupation VARCHAR(100), date_of_birth DATE, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id), UNIQUE KEY mobile_unique (mobile_number) ) $charset_collate;";
    dbDelta($sql_donors);

    // Donation History Table
    $history_table = $wpdb->prefix . 'bbdc_donation_history';
    $sql_history = "CREATE TABLE $history_table (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
            donor_name VARCHAR(100) NOT NULL,
            donor_mobile VARCHAR(15) NOT NULL,
            blood_group VARCHAR(5) NOT NULL,
            donation_date DATE NOT NULL,
            is_bbdc_donation BOOLEAN DEFAULT 1,
            referrer_name VARCHAR(100),
            status VARCHAR(20) DEFAULT 'pending',
            form_data TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            submitted_by_user_id BIGINT(20) UNSIGNED NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($sql_history);

    // Referrers Table
    $referrers_table = $wpdb->prefix . 'bbdc_referrers';
    $sql_referrers = "CREATE TABLE $referrers_table ( id BIGINT(20) NOT NULL AUTO_INCREMENT, referrer_name VARCHAR(100) NOT NULL, PRIMARY KEY (id), UNIQUE KEY name_unique (referrer_name) ) $charset_collate;";
    dbDelta($sql_referrers);

    // Events Table
    $events_table = $wpdb->prefix . 'bbdc_campaigns';
    $sql_events = "CREATE TABLE $events_table (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        campaign_name VARCHAR(255) NOT NULL,
        event_category VARCHAR(100),
        campaign_date DATE NOT NULL,
        venue TEXT,
        description TEXT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    dbDelta($sql_events);

    // Attendance Table
    $attendance_table = $wpdb->prefix . 'bbdc_campaign_attendance';
    $sql_attendance = "CREATE TABLE $attendance_table ( id BIGINT(20) NOT NULL AUTO_INCREMENT, campaign_id BIGINT(20) NOT NULL, volunteer_user_id BIGINT(20) UNSIGNED NOT NULL, PRIMARY KEY (id) ) $charset_collate;";
    dbDelta($sql_attendance);

    // FCM Tokens Table
    $fcm_table = $wpdb->prefix . 'bbdc_fcm_tokens';
    $sql_fcm = "CREATE TABLE $fcm_table ( id BIGINT(20) NOT NULL AUTO_INCREMENT, user_id BIGINT(20) UNSIGNED, fcm_token TEXT NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id), UNIQUE KEY token_unique (fcm_token(255)) ) $charset_collate;";
    dbDelta($sql_fcm);

    // Notifications Table
    $notifications_table = $wpdb->prefix . 'bbdc_notifications';
    $sql_notifications = "CREATE TABLE $notifications_table ( id BIGINT(20) NOT NULL AUTO_INCREMENT, user_id BIGINT(20) UNSIGNED NOT NULL, title TEXT NOT NULL, body TEXT NOT NULL, data_payload TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, read_at DATETIME NULL, PRIMARY KEY (id), KEY user_id (user_id) ) $charset_collate;";
    dbDelta($sql_notifications);

    // Notices Table
        $notices_table = $wpdb->prefix . 'bbdc_notices';
        $sql_notices = "CREATE TABLE $notices_table (
          id BIGINT(20) NOT NULL AUTO_INCREMENT,
          notice_subject TEXT NOT NULL,
          notice_content TEXT NOT NULL,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
          is_active TINYINT(1) DEFAULT 1,
          deadline DATETIME NULL,
          PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta($sql_notices);

    // Management Board Tables
    $departments_table = $wpdb->prefix . 'bbdc_departments';
    $sql_departments = "CREATE TABLE $departments_table ( id BIGINT(20) NOT NULL AUTO_INCREMENT, name VARCHAR(100) NOT NULL, sort_order INT(5) DEFAULT 0, PRIMARY KEY (id) ) $charset_collate;";
    dbDelta($sql_departments);

    $members_table = $wpdb->prefix . 'bbdc_board_members';
    $sql_members = "CREATE TABLE $members_table ( id BIGINT(20) NOT NULL AUTO_INCREMENT, user_id BIGINT(20) UNSIGNED NOT NULL, department_id BIGINT(20) NOT NULL, designation VARCHAR(100) NOT NULL, is_chief BOOLEAN DEFAULT 0, sort_order INT(5) DEFAULT 0, PRIMARY KEY (id) ) $charset_collate;";
    dbDelta($sql_members);
        
        $patients_table = $wpdb->prefix . 'bbdc_registered_patients';
        $sql_patients = "CREATE TABLE $patients_table (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            patient_name VARCHAR(100) NOT NULL,
            father_name VARCHAR(100),
            mother_name VARCHAR(100),
            age INT(3),
            address TEXT,
            occupation VARCHAR(100),
            guardian_occupation VARCHAR(100),
            disease VARCHAR(100),
            blood_group VARCHAR(5) NOT NULL,
            monthly_blood_need INT(3),
            mobile_number VARCHAR(15) NOT NULL,
            other_info TEXT,
            image_url VARCHAR(255),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            submitted_by_user_id BIGINT(20) UNSIGNED,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($sql_patients);
    
        $guests_table = $wpdb->prefix . 'bbdc_campaign_guests';
        $sql_guests = "CREATE TABLE $guests_table (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            campaign_id BIGINT(20) NOT NULL,
            guest_name VARCHAR(100) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY campaign_id (campaign_id)
        ) $charset_collate;";
        dbDelta($sql_guests);
    
        $accounts_table = $wpdb->prefix . 'bbdc_accounts_transactions';
        $sql_accounts = "CREATE TABLE $accounts_table (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            event_id BIGINT(20) NULL DEFAULT NULL,
            transaction_type VARCHAR(10) NOT NULL,
            source TEXT NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            memo_url VARCHAR(255) NULL,
            remarks TEXT NULL,
            transaction_date DATETIME NOT NULL,
            entered_by_user_id BIGINT(20) UNSIGNED NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_id (event_id)
        ) $charset_collate;";
        dbDelta($sql_accounts);
        
        $accounting_events_table = $wpdb->prefix . 'bbdc_accounting_events';
        $sql_accounting_events = "CREATE TABLE $accounting_events_table (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            event_name TEXT NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            created_by_user_id BIGINT(20) UNSIGNED NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($sql_accounting_events);
    
        $tasks_table = $wpdb->prefix . 'bbdc_daily_tasks';
        $sql_tasks = "CREATE TABLE $tasks_table (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            task_description TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id)
        ) $charset_collate;";
        dbDelta($sql_tasks);
    
        $attendance_table = $wpdb->prefix . 'bbdc_attendance_log';
        $sql_attendance = "CREATE TABLE $attendance_table (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            attendance_time DATETIME NOT NULL,
            activity_description TEXT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id)
        ) $charset_collate;";
        dbDelta($sql_attendance);
        
        // Notes Table
        if ($wpdb->get_var("SHOW TABLES LIKE '$notes_table'") === $notes_table) {
            if (!$wpdb->get_var("SHOW COLUMNS FROM `$notes_table` LIKE 'note_color'")) {
                $wpdb->query("ALTER TABLE `$notes_table` ADD `note_color` VARCHAR(20) NULL DEFAULT NULL AFTER `content`");
            }
            if (!$wpdb->get_var("SHOW COLUMNS FROM `$notes_table` LIKE 'note_type'")) {
                $wpdb->query("ALTER TABLE `$notes_table` ADD `note_type` VARCHAR(20) NOT NULL DEFAULT 'text' AFTER `owner_user_id`");
            }
        }
        $sql_notes = "CREATE TABLE $notes_table (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            owner_user_id BIGINT(20) UNSIGNED NOT NULL,
            title TEXT NOT NULL,
            content LONGTEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY owner_user_id (owner_user_id)
        ) $charset_collate;";
        dbDelta($sql_notes);
        
        // Note Permissions Table
        $permissions_table = $wpdb->prefix . 'bbdc_note_permissions';
        $sql_permissions = "CREATE TABLE $permissions_table (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            note_id BIGINT(20) NOT NULL,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            permission_level VARCHAR(10) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY note_user_unique (note_id, user_id)
        ) $charset_collate;";
        dbDelta($sql_permissions);
        
        // Assignments
        $event_permissions_table = $wpdb->prefix . 'bbdc_accounting_event_permissions';
        $sql_event_permissions = "CREATE TABLE $event_permissions_table (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            event_id BIGINT(20) NOT NULL,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY event_user_unique (event_id, user_id)
        ) $charset_collate;";
        dbDelta($sql_event_permissions);
        
        // --- ADD CUSTOM ROLES & CAPABILITIES ---
        self::add_custom_roles_and_caps();
    }
    
    /**
     * Adds custom roles and capabilities for the BBDC plugin.
     */
    public static function add_custom_roles_and_caps() {
        // Define all custom capabilities for the plugin
        $bbdc_caps = [
            'access_bbdc_plugin',
            'bbdc_view_dashboard',
            'bbdc_manage_donors',
            'bbdc_manage_history',
            'bbdc_manage_patients',
            'bbdc_manage_accounts',
            'bbdc_manage_fees',
            'bbdc_manage_tasks',
            'bbdc_manage_attendance',
            'bbdc_manage_volunteers',
            'bbdc_manage_events',
            'bbdc_manage_settings',
        ];
    
        // Give all capabilities to Administrator and BBDC Admin
        add_role(
            'bbdc_accountant',
            'BBDC Accountant',
            [
                'read' => true,
                'access_bbdc_plugin' => true,
                'bbdc_manage_accounts' => true,
                'bbdc_manage_fees' => true,
            ]
        );
    
       // Ensure the base 'volunteer' role has the basic access capability
        $volunteer_role = get_role('volunteer');
        if ($volunteer_role) {
            $volunteer_role->add_cap('read');
            $volunteer_role->add_cap('access_bbdc_plugin');
        }

        // Role: Accountant (Can manage Accounts and Fee Tracking)
        add_role('bbdc_accountant', 'BBDC Accountant', [
            'read' => true,
            'access_bbdc_plugin' => true,
            'bbdc_manage_accounts' => true,
            'bbdc_manage_fees' => true,
        ]);
        
        // Role: Event Manager (Can manage Events)
        add_role('bbdc_event_manager', 'BBDC Event Manager', [
            'read' => true,
            'access_bbdc_plugin' => true,
            'bbdc_manage_events' => true,
        ]);
    
        // Role: Volunteer Coordinator (Can manage Volunteers and Patients)
        add_role('bbdc_volunteer_coordinator', 'BBDC Volunteer Coordinator', [
            'read' => true,
            'access_bbdc_plugin' => true,
            'bbdc_manage_volunteers' => true,
            'bbdc_manage_patients' => true,
        ]);
    
        // Role: Senior Volunteer (Can do everything except Settings and managing other admins)
        add_role('bbdc_senior_volunteer', 'BBDC Senior Volunteer', [
            'read' => true,
            'access_bbdc_plugin' => true,
            'bbdc_view_dashboard' => true,
            'bbdc_manage_donors' => true,
            'bbdc_manage_history' => true,
            'bbdc_manage_patients' => true,
            'bbdc_manage_accounts' => true,
            'bbdc_manage_fees' => true,
            'bbdc_manage_tasks' => true,
            'bbdc_manage_attendance' => true,
            'bbdc_manage_volunteers' => true,
            'bbdc_manage_events' => true,
        ]);
    }
}