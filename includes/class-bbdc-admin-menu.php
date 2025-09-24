<?php
class BBDC_DM_Admin_Menu {
    public function __construct() {
        add_action('admin_menu', [$this, 'register_menus']);
        add_action('admin_init', [$this, 'handle_plugin_actions']);
    }

    public function register_menus() {
        add_menu_page('BBDC', 'BBDC', 'read', 'bbdc-donation-manager', [$this, 'render_page'], 'dashicons-heart', 25);
        add_submenu_page('bbdc-donation-manager', 'Dashboard', 'Dashboard', 'bbdc_view_dashboard', 'bbdc-donation-manager', [$this, 'render_page']);
        add_submenu_page('bbdc-donation-manager', 'Donor Tracking', 'Donor Tracking', 'bbdc_manage_donors', 'bbdc-donor-tracking', [$this, 'render_page']);
        add_submenu_page('bbdc-donation-manager', 'BBDC History', 'History (BBDC)', 'bbdc_manage_history', 'bbdc-history-bbdc', [$this, 'render_page']);
        add_submenu_page('bbdc-donation-manager', 'Other History', 'History (Others)', 'bbdc_manage_history', 'bbdc-history-others', [$this, 'render_page']);
        add_submenu_page('bbdc-donation-manager', 'Pending Donations', 'Pending Donations', 'bbdc_manage_history', 'bbdc-pending-donations', [$this, 'render_page']);
        add_submenu_page('bbdc-donation-manager', 'Registered Patients', 'Registered Patients', 'bbdc_manage_patients', 'bbdc-registered-patients', [$this, 'render_page']);
        add_submenu_page('bbdc-donation-manager', 'Accounts', 'Accounts', 'bbdc_manage_accounts', 'bbdc-accounts', [$this, 'render_page']);
        add_submenu_page('bbdc-donation-manager', 'Accounting Events', 'Accounting Events', 'bbdc_manage_accounts', 'bbdc-accounting-events', [$this, 'render_page']);
        add_submenu_page('bbdc-donation-manager', 'Daily Tasks', 'Daily Tasks', 'bbdc_manage_tasks', 'bbdc-daily-tasks', [$this, 'render_page']);
        add_submenu_page('bbdc-donation-manager', 'Attendance Log', 'Attendance Log', 'bbdc_manage_attendance', 'bbdc-attendance-log', [$this, 'render_page']);
        add_submenu_page('bbdc-donation-manager', 'Volunteers', 'Volunteers', 'bbdc_manage_volunteers', 'bbdc-volunteers', [$this, 'render_page']);
        add_submenu_page('bbdc-donation-manager', 'Pending Volunteers', 'Pending Volunteers', 'bbdc_manage_volunteers', 'bbdc-pending-volunteers', [$this, 'render_page']);
        add_submenu_page('bbdc-donation-manager', 'Events', 'Events', 'bbdc_manage_events', 'bbdc-events', [$this, 'render_page']);
        add_submenu_page('bbdc-events', 'Create Event', 'Create Event', 'bbdc_manage_events', 'bbdc-create-event', [$this, 'render_page']);
        add_submenu_page('bbdc-donation-manager', 'Settings', 'Settings', 'bbdc_manage_settings', 'bbdc-settings', [$this, 'render_page']);
    }

    public function render_page() {
        $current_page = isset($_GET['page']) ? $_GET['page'] : '';
        switch ($current_page) {
            case 'bbdc-donor-tracking': $this->render_donor_tracking_page(); break;
            case 'bbdc-history-bbdc': $this->render_history_page('bbdc'); break;
            case 'bbdc-history-others': $this->render_history_page('others'); break;
            case 'bbdc-pending-donations': $this->render_history_page('pending'); break;
            case 'bbdc-registered-patients': $this->render_patients_page(); break;
            case 'bbdc-accounts': $this->render_accounts_page(); break;
            case 'bbdc-accounting-events':
                if (isset($_GET['action']) && $_GET['action'] === 'manage' && !empty($_GET['event_id'])) {
                    $this->render_manage_accounting_event_page(intval($_GET['event_id']));
                } else {
                    $this->render_accounting_events_list_page();
                }
                break;
            case 'bbdc-daily-tasks': $this->render_daily_tasks_page(); break;
            case 'bbdc-attendance-log': $this->render_attendance_log_page(); break;
            case 'bbdc-volunteers': $this->render_volunteers_page('approved'); break;
            case 'bbdc-pending-volunteers': $this->render_volunteers_page('pending'); break;
            case 'bbdc-events': 
                if (isset($_GET['action']) && $_GET['action'] === 'manage' && !empty($_GET['event_id'])) {
                    $this->render_manage_event_page(intval($_GET['event_id']));
                } else {
                    $this->render_events_list_page();
                }
                break;
            case 'bbdc-create-event': $this->render_create_event_page(); break;
            case 'bbdc-settings': $this->render_settings_page(); break;
            default: echo '<div class="wrap"><h1>BBDC Donation Dashboard</h1><p>Overall statistics will be shown here.</p></div>'; break;
        }
    }

    private function render_donor_tracking_page() {
        if (isset($_GET['action']) && $_GET['action'] == 'edit' && !empty($_GET['donor_id'])) {
            $this->render_donor_profile_page(intval($_GET['donor_id']));
        } else {
            require_once BBDC_DM_PATH . 'includes/class-bbdc-donor-list-table.php';
            $list_table = new BBDC_Donor_List_Table();
            echo '<div class="wrap"><h2>Donor Tracking</h2>';
            $list_table->prepare_items();
            $list_table->display();
            echo '</div>';
        }
    }

    private function render_history_page($type) {
        require_once BBDC_DM_PATH . 'includes/class-bbdc-history-list-table.php';
        $list_table = new BBDC_History_List_Table($type);
        echo '<div class="wrap">';
        $total_items = $list_table->get_pagination_arg('total_items');
        $title = ($type == 'pending') ? "Pending Donations <span class='awaiting-mod'>{$total_items}</span>" : (($type == 'bbdc') ? "BBDC Donation History" : "Other Sources History");
        echo '<h2>' . $title . '</h2>';
        $list_table->prepare_items();
        $list_table->display();
        echo '</div>';
    }

    private function render_volunteers_page($status) {
        require_once BBDC_DM_PATH . 'includes/class-bbdc-volunteers-list-table.php';
        $list_table = new BBDC_Volunteers_List_Table($status);
        echo '<div class="wrap">';
        echo '<h2>' . ($status === 'approved' ? 'Approved Volunteers' : 'Pending Volunteers') . '</h2>';
        $list_table->prepare_items();
        $list_table->display();
        echo '</div>';
    }
    
    private function render_events_list_page() {
        require_once BBDC_DM_PATH . 'includes/class-bbdc-events-list-table.php';
        $list_table = new BBDC_Events_List_Table();
        echo '<div class="wrap"><h2>Events <a href="?page=bbdc-create-event" class="page-title-action">Create Event</a></h2>';
        $list_table->prepare_items();
        $list_table->display();
        echo '</div>';
    }

    private function render_create_event_page() {
        ?>
        <div class="wrap">
            <h1>Create New Event</h1>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field('bbdc_create_event_nonce'); ?>
                <input type="hidden" name="action" value="bbdc_create_event">
                <table class="form-table">
                    <tr><th><label for="event_name">Event Name *</label></th><td><input type="text" name="event_name" id="event_name" class="regular-text" required></td></tr>
                    <tr><th><label for="event_category">Event Category *</label></th>
                        <td>
                            <select name="event_category" id="event_category" required>
                                <option value="">Select Category</option>
                                <option value="Campaign">Campaign</option>
                                <option value="General Meeting">General Meeting</option>
                                <option value="Fundraising">Fundraising</option>
                            </select>
                        </td>
                    </tr>
                    <tr><th><label for="event_date">Date *</label></th><td><input type="date" name="event_date" id="event_date" required></td></tr>
                    <tr><th><label for="venue">Venue / Location</label></th><td><textarea name="venue" id="venue" class="large-text"></textarea></td></tr>
                </table>
                <?php submit_button('Create Event'); ?>
            </form>
        </div>
        <?php
    }
    
    private function render_patients_page() {
        if (isset($_GET['action']) && $_GET['action'] === 'view_details' && !empty($_GET['patient_id'])) {
            $patient_id = intval($_GET['patient_id']);
            $this->render_patient_detail_page($patient_id);
        } else {
            echo '<div class="wrap"><h2>Registered Patients</h2>';
            
            $list_table = new BBDC_Patient_List_Table();
            ?>
            <form method="get">
                <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
                <?php
                $list_table->prepare_items();
                $list_table->search_box('Search Patients', 'patient-search');
                $list_table->display();
                ?>
            </form>
            <?php
            echo '</div>';
        }
    }

    private function render_manage_event_page($event_id) {
        global $wpdb;
        $event = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}bbdc_campaigns WHERE id = %d", $event_id));
        if (!$event) { echo '<div class="wrap"><h2>Event not found.</h2></div>'; return; }
        
        $logistics = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}bbdc_campaign_logistics WHERE campaign_id = %d ORDER BY id DESC", $event_id));
        $attendees = $wpdb->get_col($wpdb->prepare("SELECT volunteer_user_id FROM {$wpdb->prefix}bbdc_campaign_attendance WHERE campaign_id = %d", $event_id));
        $guests = $wpdb->get_col($wpdb->prepare("SELECT guest_name FROM {$wpdb->prefix}bbdc_campaign_guests WHERE campaign_id = %d", $event_id));
        $all_volunteers = get_users(['role__in' => ['volunteer', 'bbdc_admin'], 'meta_key' => 'bbdc_approval_status', 'meta_value' => 'approved', 'orderby' => 'display_name']);
        ?>
        <div class="wrap">
            <h1>Manage Event: <?php echo esc_html($event->campaign_name); ?></h1>
            <p><strong>Category:</strong> <?php echo esc_html($event->event_category); ?> | <strong>Date:</strong> <?php echo esc_html(date("F j, Y", strtotime($event->campaign_date))); ?> | <strong>Venue:</strong> <?php echo esc_html($event->venue); ?></p>
            <hr>
            <div id="col-container">
                <div id="col-full" style="width:100%;">
                    <h2>Volunteer Attendance</h2>
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>"><input type="hidden" name="action" value="bbdc_save_attendance"><input type="hidden" name="event_id" value="<?php echo $event_id; ?>"><?php wp_nonce_field('bbdc_manage_event_nonce_save'); ?>
                        <p>Select volunteers who will attend:</p>
                        <select name="attendees[]" multiple style="width:100%; min-height: 200px;">
                        <?php foreach($all_volunteers as $volunteer): ?>
                            <option value="<?php echo $volunteer->ID; ?>" <?php selected(in_array($volunteer->ID, $attendees)); ?>><?php echo esc_html($volunteer->display_name); ?></option>
                        <?php endforeach; ?>
                        </select>
                        <h2 style="margin-top: 20px;">Guest Attendance</h2>
                        <p>Enter guest names, one per line.</p>
                        <textarea name="guests" rows="8" style="width: 100%;"><?php echo esc_textarea(implode("\n", $guests)); ?></textarea>
                        <?php submit_button('Save Attendance'); ?>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    public function handle_plugin_actions() {
        if (isset($_GET['action'])) {
            if ($_GET['action'] == 'export_csv_donors') $this->export_donors_to_csv();
            if ($_GET['action'] == 'export_csv_history') $this->export_history_to_csv();
            if ($_GET['action'] == 'export_csv_volunteers') $this->export_volunteers_to_csv();
            if ($_GET['action'] == 'export_patients_csv') $this->export_patients_to_csv();
            if ($_GET['action'] == 'export_attendance_csv') $this->export_attendance_to_csv();
            if ($_GET['action'] === 'delete_referrer' && isset($_GET['ref_id'], $_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_referrer_' . $_GET['ref_id'])) {
                if (current_user_can('manage_options')) {
                    global $wpdb; $wpdb->delete($wpdb->prefix . 'bbdc_referrers', ['id' => intval($_GET['ref_id'])]);
                    add_action('admin_notices', function() { echo '<div class="notice notice-success is-dismissible"><p>Referrer deleted.</p></div>'; });
                }
            }
        }
        if (isset($_POST['bbdc_action']) && $_POST['bbdc_action'] == 'reset_data' && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'bbdc_reset_nonce')) {
            if (current_user_can('manage_options')) {
                global $wpdb;
                $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}bbdc_donors");
                $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}bbdc_donation_history");
                $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}bbdc_referrers");
                $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}bbdc_campaigns");
                $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}bbdc_campaign_logistics");
                $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}bbdc_campaign_attendance");
                add_action('admin_notices', function() { echo '<div class="notice notice-success is-dismissible"><p>All plugin data has been reset.</p></div>'; });
            }
        }
        if (isset($_POST['submit_referrer']) && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'bbdc_add_referrer_nonce')) {
            $name = sanitize_text_field($_POST['referrer_name']);
            if (!empty($name) && current_user_can('manage_options')) {
                global $wpdb; $wpdb->insert($wpdb->prefix . 'bbdc_referrers', ['referrer_name' => $name], ['%s']);
                add_action('admin_notices', function() { echo '<div class="notice notice-success is-dismissible"><p>Referrer added!</p></div>'; });
            }
        }
        
        register_setting('bbdc_sms_settings_group', 'bbdc_sms_api_token');
        register_setting('bbdc_sms_settings_group', 'bbdc_sms_sid');
        register_setting('bbdc_sms_settings_group', 'bbdc_sms_template');
        add_settings_section('bbdc_sms_section', 'SMS Gateway Settings', null, 'bbdc-sms-settings');
        add_settings_field('bbdc_sms_api_token', 'API Token', [$this, 'api_token_callback'], 'bbdc-sms-settings', 'bbdc_sms_section');
        add_settings_field('bbdc_sms_sid', 'SID / Masking Name', [$this, 'sid_callback'], 'bbdc-sms-settings', 'bbdc_sms_section');
        add_settings_field('bbdc_sms_template', 'SMS Message Template', [$this, 'template_callback'], 'bbdc-sms-settings', 'bbdc_sms_section');
        
        register_setting('bbdc_notification_settings_group', 'bbdc_firebase_project_id', 'sanitize_text_field');
        register_setting('bbdc_notification_settings_group', 'bbdc_fcm_service_account_path', 'sanitize_text_field');
        register_setting('bbdc_app_version_settings_group', 'bbdc_min_app_version_name');
        register_setting('bbdc_app_version_settings_group', 'bbdc_min_app_version_code');
        register_setting('bbdc_maintenance_settings_group', 'bbdc_maintenance_mode_enabled');
        register_setting('bbdc_maintenance_settings_group', 'bbdc_maintenance_title');
        register_setting('bbdc_maintenance_settings_group', 'bbdc_maintenance_message');
        register_setting('bbdc_app_update_settings_group', 'bbdc_app_latest_version_code');
        register_setting('bbdc_app_update_settings_group', 'bbdc_app_apk_url');
        register_setting('bbdc_recaptcha_settings_group', 'bbdc_recaptcha_site_key');
        register_setting('bbdc_recaptcha_settings_group', 'bbdc_recaptcha_secret_key');
        register_setting('bbdc_payment_settings_group', 'bbdc_paystation_merchant_id');
        register_setting('bbdc_payment_settings_group', 'bbdc_paystation_password');
        add_settings_section('bbdc_fcm_section', 'Push Notification Settings (FCM v1)', null, 'bbdc-notification-settings');
        add_settings_field('bbdc_firebase_project_id', 'Firebase Project ID', [$this, 'fcm_project_id_callback'], 'bbdc-notification-settings', 'bbdc_fcm_section');
        add_settings_field('bbdc_fcm_service_account_path', 'Service Account JSON File Path', [$this, 'fcm_service_account_path_callback'], 'bbdc-notification-settings', 'bbdc_fcm_section');
        if (isset($_GET['action']) && $_GET['action'] == 'fix_patient_images' && current_user_can('manage_options')) {
            if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'bbdc_fix_images_nonce')) {
                
                $batch = isset($_GET['batch']) ? intval($_GET['batch']) : 1;
                $updated = isset($_GET['updated']) ? intval($_GET['updated']) : 0;
                $failed = isset($_GET['failed']) ? intval($_GET['failed']) : 0;

                $result = bbdc_fix_patient_image_urls($batch);

                $total_updated = $updated + $result['updated'];
                $total_failed = $failed + $result['failed'];

                if ($result['complete'] || $result['processed'] == 0) {
                    add_action('admin_notices', function() use ($total_updated, $total_failed) {
                        $message = "Image URL fixing process complete. Total Fixed: {$total_updated}. Total Failed: {$total_failed}.";
                        printf('<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html($message));
                    });
                } else {
                    $next_batch = $batch + 1;
                    $redirect_url = admin_url('admin.php?page=bbdc-settings&action=fix_patient_images&batch=' . $next_batch . '&updated=' . $total_updated . '&failed=' . $total_failed . '&_wpnonce=' . wp_create_nonce('bbdc_fix_images_nonce'));
                    
                    add_action('admin_notices', function() use ($batch, $total_updated, $total_failed) {
                        $message = "Processing batch {$batch}... URLs Updated so far: {$total_updated}, Failed so far: {$total_failed}. Please wait, the page will refresh automatically.";
                        printf('<div class="notice notice-info"><p>%s</p></div>', esc_html($message));
                    });
                    
                    echo "<script>window.location.href = '" . esc_url_raw($redirect_url) . "';</script>";
                }
            }
        }
    }

    public function api_token_callback() { echo "<input type='text' name='bbdc_sms_api_token' value='" . esc_attr(get_option('bbdc_sms_api_token')) . "' class='regular-text' />"; }
    public function sid_callback() { echo "<input type='text' name='bbdc_sms_sid' value='" . esc_attr(get_option('bbdc_sms_sid')) . "' class='regular-text' />"; }
    public function template_callback() {
        $value = esc_textarea(get_option('bbdc_sms_template', 'Thank you, {donor_name}, for your valuable blood donation! - BBDC'));
        echo "<textarea name='bbdc_sms_template' rows='5' class='large-text'>{$value}</textarea><p class='description'>Use {donor_name} to insert the donor's name.</p>";
    }
    public function fcm_project_id_callback() {
        echo "<input type='text' name='bbdc_firebase_project_id' value='" . esc_attr(get_option('bbdc_firebase_project_id')) . "' class='regular-text' />";
        echo "<p class='description'>Find this in your Firebase project settings.</p>";
    }
    public function fcm_service_account_path_callback() {
        echo "<input type='text' name='bbdc_fcm_service_account_path' value='" . esc_attr(get_option('bbdc_fcm_service_account_path')) . "' class='large-text' />";
        echo "<p class='description'><b>Security Warning:</b> Place this file outside the public_html folder. Example: <code>/home/your_user/keys/firebase-key.json</code></p>";
    }

    private function render_settings_page() {
        global $wpdb; $referrers = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bbdc_referrers ORDER BY referrer_name ASC");
        ?>
        <div class="wrap"><h1>BBDC Donation Manager Settings</h1><hr>
        
        <h2>Send Push Notification</h2>
        <?php if (isset($_GET['notification_sent'])): ?>
            <div class="notice notice-success is-dismissible">
                <p>Notification sent to <?php echo esc_html($_GET['count']); ?> devices.</p>
            </div>
        <?php elseif (isset($_GET['notification_error'])): ?>
            <div class="notice notice-error is-dismissible">
                <p>Failed to send notification. Error: <?php echo esc_html(urldecode($_GET['error_message'])); ?></p>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="bbdc_send_admin_notification">
            <?php wp_nonce_field('bbdc_send_notification_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="notification_title">Title</label></th>
                    <td><input name="notification_title" type="text" id="notification_title" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="notification_body">Message</label></th>
                    <td><textarea name="notification_body" id="notification_body" rows="5" class="large-text" required></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><label for="notification_url">Click URL (Optional)</label></th>
                    <td><input name="notification_url" type="url" id="notification_url" class="regular-text" placeholder="<?php echo esc_attr(home_url('/')); ?>"></td>
                </tr>
            </table>
            <?php submit_button('Send Notification to All Users'); ?>
        </form>
        <hr>

        <div id="col-container"><div id="col-left" style="width:48%; float:left; margin-right:2%;">
            <div class="col-wrap"><h2>Add New Referrer</h2><form method="post" action=""><?php wp_nonce_field('bbdc_add_referrer_nonce'); ?><div class="form-field"><label for="referrer_name">Name</label><input type="text" name="referrer_name" id="referrer_name" required style="width:100%;" /></div><?php submit_button('Add Referrer', 'primary', 'submit_referrer'); ?></form></div>
        </div><div id="col-right" style="width:48%; float:left;">
            <div class="col-wrap"><h2>Existing Referrers</h2><table class="wp-list-table widefat fixed striped"><thead><tr><th>Name</th><th style="width:20%;">Action</th></tr></thead><tbody>
            <?php if ($referrers): foreach ($referrers as $ref): $del_nonce = wp_create_nonce('delete_referrer_' . $ref->id); ?>
                <tr><td><?php echo esc_html($ref->referrer_name); ?></td><td><a href="?page=bbdc-settings&action=delete_referrer&ref_id=<?php echo $ref->id; ?>&_wpnonce=<?php echo $del_nonce; ?>" style="color:#a00;" onclick="return confirm('Are you sure?')">Delete</a></td></tr>
            <?php endforeach; else: echo '<tr><td colspan="2">No referrers found.</td></tr>'; endif; ?>
            </tbody></table></div>
        </div></div><div style="clear:both;"></div><hr>
        
        <h2>SMS Settings</h2><form method="post" action="options.php"><?php settings_fields('bbdc_sms_settings_group'); do_settings_sections('bbdc-sms-settings'); submit_button(); ?></form>
        
        <hr>
        <h2>PayStation Payment Gateway Settings</h2>
        <form method="post" action="options.php">
            <?php settings_fields('bbdc_payment_settings_group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="bbdc_paystation_merchant_id">Merchant ID</label></th>
                    <td><input type="text" id="bbdc_paystation_merchant_id" name="bbdc_paystation_merchant_id" value="<?php echo esc_attr(get_option('bbdc_paystation_merchant_id')); ?>" class="regular-text" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="bbdc_paystation_password">Password</label></th>
                    <td><input type="password" id="bbdc_paystation_password" name="bbdc_paystation_password" value="<?php echo esc_attr(get_option('bbdc_paystation_password')); ?>" class="regular-text" /></td>
                </tr>
            </table>
            <?php submit_button('Save Payment Settings'); ?>
        </form>
        
        <hr>
        <h2>Push Notification Settings (HTTP v1 API)</h2>
        <form method="post" action="options.php">
            <?php
                settings_fields('bbdc_notification_settings_group');
                do_settings_sections('bbdc-notification-settings');
                submit_button('Save Notification Settings');
            ?>
        </form>
        
        <h2>App Update Settings</h2>
            <p>Control the force update feature of the mobile app from here.</p>
            <form method="post" action="options.php">
                <?php settings_fields('bbdc_app_update_settings_group'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><label for="bbdc_app_latest_version_code">Latest App Version Code</label></th>
                        <td><input type="number" id="bbdc_app_latest_version_code" name="bbdc_app_latest_version_code" value="<?php echo esc_attr(get_option('bbdc_app_latest_version_code', '1')); ?>" class="regular-text" />
                        <p class="description">Enter an integer (e.g., 2, 3, 10). If this is higher than the user's installed app version, they will be forced to update.</p></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="bbdc_app_apk_url">Android App APK URL</label></th>
                        <td><input type="url" id="bbdc_app_apk_url" name="bbdc_app_apk_url" value="<?php echo esc_attr(get_option('bbdc_app_apk_url')); ?>" class="regular-text" />
                        <p class="description">Enter the full, direct download URL for the latest .apk file.</p></td>
                    </tr>
                </table>
                <?php submit_button('Save App Update Settings'); ?>
            </form>

        <hr>
            <h2>Maintenance Mode</h2>
            <p>Enable this to show a maintenance notice to all non-admin users when they open the app.</p>
            <form method="post" action="options.php">
                <?php settings_fields('bbdc_maintenance_settings_group'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Enable Maintenance Mode</th>
                        <td>
                            <input type="checkbox" name="bbdc_maintenance_mode_enabled" value="1" <?php checked(1, get_option('bbdc_maintenance_mode_enabled'), true); ?> />
                            <label for="bbdc_maintenance_mode_enabled">Show maintenance screen in the app</label>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Maintenance Title</th>
                        <td><input type="text" name="bbdc_maintenance_title" value="<?php echo esc_attr(get_option('bbdc_maintenance_title', 'Under Maintenance')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Maintenance Message</th>
                        <td><textarea name="bbdc_maintenance_message" rows="4" class="large-text"><?php echo esc_textarea(get_option('bbdc_maintenance_message', 'The app is currently undergoing maintenance. We will be back shortly. Thank you for your patience.')); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button('Save Maintenance Settings'); ?>
            </form>

            <hr>
            
            <hr>
            <h2>Google reCAPTCHA Settings (v2)</h2>
            <p>Add Google reCAPTCHA to the frontend registration form to prevent spam.</p>
            <form method="post" action="options.php">
                <?php settings_fields('bbdc_recaptcha_settings_group'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><label for="bbdc_recaptcha_site_key">Site Key</label></th>
                        <td><input type="text" id="bbdc_recaptcha_site_key" name="bbdc_recaptcha_site_key" value="<?php echo esc_attr(get_option('bbdc_recaptcha_site_key')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="bbdc_recaptcha_secret_key">Secret Key</label></th>
                        <td><input type="text" id="bbdc_recaptcha_secret_key" name="bbdc_recaptcha_secret_key" value="<?php echo esc_attr(get_option('bbdc_recaptcha_secret_key')); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button('Save reCAPTCHA Settings'); ?>
            </form>
            
            <hr>
            <h2>Tools</h2>
            <div class="bbdc-tools-section">
                <h3>Fix Broken Patient Image URLs</h3>
                <p>
                    If patient images are not showing up correctly in the app due to a previous bug, this tool will attempt to fix the URLs in the database.
                    <br><strong>Please back up your database before running this tool.</strong>
                </p>
                <?php
                $fix_url = wp_nonce_url(admin_url('admin.php?page=bbdc-settings&action=fix_patient_images'), 'bbdc_fix_images_nonce');
                ?>
                <a href="<?php echo esc_url($fix_url); ?>" class="button button-secondary">Run Image URL Fixer</a>
            </div>
            
        <hr><div class="bbdc-danger-zone"><h2>Danger Zone</h2><p><strong>WARNING:</strong> This action will permanently delete all donors, donation history, and referrer data.</p><form method="post" action=""><?php wp_nonce_field('bbdc_reset_nonce'); ?><input type="hidden" name="bbdc_action" value="reset_data"><?php submit_button('Reset All Plugin Data', 'delete', 'submit', false, ['onclick' => "return confirm('WARNING! This is irreversible. Are you absolutely sure?');"]); ?></form></div></div><?php
    }

    private function render_donor_profile_page($donor_id) {
        global $wpdb; $table = $wpdb->prefix . 'bbdc_donors'; 
        if (isset($_POST['update_donor_profile']) && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'bbdc_update_donor_nonce')) { 
            $data = [
                'donor_name' => sanitize_text_field($_POST['donor_name']),'blood_group' => sanitize_text_field($_POST['blood_group']),
                'age' => intval($_POST['age']), 'donor_location' => sanitize_text_field($_POST['donor_location']),
                'emergency_phone' => sanitize_text_field($_POST['emergency_phone']), 'present_address' => sanitize_textarea_field($_POST['present_address']),
                'permanent_address' => sanitize_textarea_field($_POST['permanent_address']), 'nid_birth_id' => sanitize_text_field($_POST['nid_birth_id']),
                'occupation' => sanitize_text_field($_POST['occupation']), 'date_of_birth' => sanitize_text_field($_POST['date_of_birth'])
            ]; 
            $wpdb->update($table, $data, ['id' => $donor_id]); 
            echo '<div class="notice notice-success is-dismissible"><p>Profile updated successfully!</p></div>'; 
        } 
        $donor = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $donor_id)); 
        if (!$donor) { echo '<div class="wrap"><h2>Error</h2><p>Donor not found.</p></div>'; return; } 
        ?> 
        <div class="wrap"><h1>Edit Donor Profile: <?php echo esc_html($donor->donor_name); ?></h1><form method="post" action=""><?php wp_nonce_field('bbdc_update_donor_nonce'); ?><table class="form-table">
            <tr><th><label>Name</label></th><td><input type="text" name="donor_name" value="<?php echo esc_attr($donor->donor_name); ?>" class="regular-text"></td></tr>
            <tr><th><label>Mobile</label></th><td><input type="text" value="<?php echo esc_attr($donor->donor_mobile); ?>" class="regular-text" readonly></td></tr>
            <tr><th><label>Blood Group</label></th><td><input type="text" name="blood_group" value="<?php echo esc_attr($donor->blood_group); ?>" class="regular-text"></td></tr>
            <tr><th><label>Age</label></th><td><input type="number" name="age" value="<?php echo esc_attr($donor->age); ?>" class="regular-text"></td></tr>
            <tr><th><label>Date of Birth</label></th><td><input type="date" name="date_of_birth" value="<?php echo esc_attr($donor->date_of_birth); ?>"></td></tr>
            <tr><th><label>Location</label></th><td><input type="text" name="donor_location" value="<?php echo esc_attr($donor->donor_location); ?>" class="regular-text"></td></tr>
            <tr><th><label>Occupation</label></th><td><input type="text" name="occupation" value="<?php echo esc_attr($donor->occupation); ?>" class="regular-text"></td></tr>
            <tr><th><label>Emergency Phone</label></th><td><input type="text" name="emergency_phone" value="<?php echo esc_attr($donor->emergency_phone); ?>" class="regular-text"></td></tr>
            <tr><th><label>NID/Birth ID</label></th><td><input type="text" name="nid_birth_id" value="<?php echo esc_attr($donor->nid_birth_id); ?>" class="regular-text"></td></tr>
            <tr><th><label>Present Address</label></th><td><textarea name="present_address" rows="3" class="large-text"><?php echo esc_textarea($donor->present_address); ?></textarea></td></tr>
            <tr><th><label>Permanent Address</label></th><td><textarea name="permanent_address" rows="3" class="large-text"><?php echo esc_textarea($donor->permanent_address); ?></textarea></td></tr>
        </table><?php submit_button('Update Profile', 'primary', 'update_donor_profile'); ?></form></div>
        <?php 
    }
    
    private function export_donors_to_csv() {
        if (!current_user_can('manage_options')) wp_die('Permission denied.');
        global $wpdb;
        $table_name = $wpdb->prefix . 'bbdc_donors';
        $sql = "SELECT * FROM {$table_name}";
        $where = [];
        if (!empty($_GET['s_mobile'])) $where[] = $wpdb->prepare("donor_mobile LIKE %s", '%' . $wpdb->esc_like(sanitize_text_field($_GET['s_mobile'])) . '%');
        if (!empty($_GET['s_blood_group'])) $where[] = $wpdb->prepare("blood_group = %s", sanitize_text_field($_GET['s_blood_group']));
        if (!empty($_GET['s_date_from'])) $where[] = $wpdb->prepare("last_donation_date >= %s", sanitize_text_field($_GET['s_date_from']));
        if (!empty($_GET['s_date_to'])) $where[] = $wpdb->prepare("last_donation_date <= %s", sanitize_text_field($_GET['s_date_to']));
        if (!empty($where)) $sql .= " WHERE " . implode(' AND ', $where);
        $items = $wpdb->get_results($sql, ARRAY_A);
        if (!$items) { wp_safe_redirect(add_query_arg('error', 'no-data', wp_get_referer())); exit; }
        $filename = 'bbdc-donors-' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $output = fopen('php://output', 'w');
        fputcsv($output, array_keys($items[0]));
        foreach ($items as $item) fputcsv($output, $item);
        fclose($output);
        exit;
    }
    
    private function export_history_to_csv() {
        if (!current_user_can('manage_options')) wp_die('Permission denied.');
        global $wpdb;
        $table_name = $wpdb->prefix . 'bbdc_donation_history';
        $sql = "SELECT * FROM {$table_name}";
        $where = [];
        $filter_type = isset($_GET['type']) ? sanitize_key($_GET['type']) : 'all';
        if ($filter_type == 'pending') $where[] = "status = 'pending'";
        if ($filter_type == 'bbdc') $where[] = "status = 'approved' AND is_bbdc_donation = 1";
        if ($filter_type == 'others') $where[] = "status = 'approved' AND is_bbdc_donation = 0";
        if (!empty($_GET['s_mobile'])) $where[] = $wpdb->prepare("donor_mobile LIKE %s", '%' . $wpdb->esc_like(sanitize_text_field($_GET['s_mobile'])) . '%');
        if (!empty($_GET['s_blood_group'])) $where[] = $wpdb->prepare("blood_group = %s", sanitize_text_field($_GET['s_blood_group']));
        if (!empty($_GET['s_referrer'])) $where[] = $wpdb->prepare("referrer_name = %s", sanitize_text_field($_GET['s_referrer']));
        if (!empty($_GET['s_date_from'])) $where[] = $wpdb->prepare("donation_date >= %s", sanitize_text_field($_GET['s_date_from']));
        if (!empty($_GET['s_date_to'])) $where[] = $wpdb->prepare("donation_date <= %s", sanitize_text_field($_GET['s_date_to']));
        if (!empty($where)) $sql .= " WHERE " . implode(' AND ', $where);
        $items = $wpdb->get_results($sql, ARRAY_A);
        if (!$items) { wp_safe_redirect(add_query_arg('error', 'no-data', wp_get_referer())); exit; }
        $filename = 'bbdc-history-'.$filter_type.'-' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $output = fopen('php://output', 'w');
        fputcsv($output, array_keys($items[0]));
        foreach ($items as $item) fputcsv($output, $item);
        fclose($output);
        exit;
    }

    private function export_volunteers_to_csv() {
        if (!current_user_can('manage_options')) wp_die('Permission denied.');
        $status = isset($_GET['status']) ? sanitize_key($_GET['status']) : 'approved';
        $args = ['role__in' => ['volunteer', 'bbdc_admin'], 'fields' => 'all_with_meta', 'meta_query' => ['relation' => 'AND', ['key' => 'bbdc_approval_status', 'value' => $status]]];
        if (!empty($_GET['s_mobile'])) $args['meta_query'][] = ['key' => 'bbdc_mobile_number', 'value' => sanitize_text_field($_GET['s_mobile']), 'compare' => 'LIKE'];
        if (!empty($_GET['s_date_from']) || !empty($_GET['s_date_to'])) {
            $args['date_query'] = ['inclusive' => true];
            if(!empty($_GET['s_date_from'])) $args['date_query']['after'] = sanitize_text_field($_GET['s_date_from']);
            if(!empty($_GET['s_date_to'])) $args['date_query']['before'] = sanitize_text_field($_GET['s_date_to']);
        }
        $user_query = new WP_User_Query($args);
        $users = $user_query->get_results();
        if (!$users) { wp_safe_redirect(add_query_arg('error', 'no-data', wp_get_referer())); exit; }
        $filename = 'bbdc-volunteers-'.$status.'-'.date('Y-m-d').'.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Username', 'Name', 'Email', 'Mobile', 'Registered Date', 'Status']);
        foreach ($users as $user) {
            fputcsv($output, [$user->ID, $user->user_login, $user->display_name, $user->user_email, get_user_meta($user->ID, 'bbdc_mobile_number', true), $user->user_registered, $status]);
        }
        fclose($output);
        exit;
    }
    
        private function export_patients_to_csv() {
        if (!current_user_can('manage_options')) {
            wp_die('Permission denied.');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'bbdc_registered_patients';
        
        $sql = "SELECT * FROM {$table_name}";
        $where = [];

        if (!empty($_GET['s'])) {
            $search = esc_sql($wpdb->esc_like(sanitize_text_field($_GET['s'])));
            $where[] = $wpdb->prepare("(patient_name LIKE %s OR mobile_number LIKE %s)", "%{$search}%", "%{$search}%");
        }
        
        if (!empty($_GET['filter_by_disease'])) {
            $disease = sanitize_text_field($_GET['filter_by_disease']);
            $where[] = $wpdb->prepare("disease = %s", $disease);
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $items = $wpdb->get_results($sql, ARRAY_A);

        if (!$items) {
            wp_safe_redirect(add_query_arg('error', 'no-data', wp_get_referer()));
            exit;
        }

        $filename = 'bbdc-registered-patients-' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        fputcsv($output, array_keys($items[0]));
        
        foreach ($items as $item) {
            fputcsv($output, $item);
        }
        
        fclose($output);
        exit;
    }
    
        private function render_patient_detail_page($patient_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bbdc_registered_patients';
        $patient = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $patient_id));

        if (!$patient) {
            echo '<div class="wrap"><h2>Error</h2><p>Patient not found.</p></div>';
            return;
        }

        $back_link = remove_query_arg(['action', 'patient_id']);
        ?>
        <div class="wrap">
            <h1>Patient Details: <?php echo esc_html($patient->patient_name); ?></h1>
            <a href="<?php echo esc_url($back_link); ?>" class="page-title-action">&larr; Back to List</a>

            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="postbox-container-1" class="postbox-container">
                        <div class="postbox">
                            <h2 class="hndle"><span>Patient Photo</span></h2>
                            <div class="inside">
                                <?php if (!empty($patient->image_url)) : ?>
                                    <img src="<?php echo esc_url($patient->image_url); ?>" style="width:100%; height:auto; border:1px solid #ddd; border-radius: 4px;" alt="<?php echo esc_attr($patient->patient_name); ?>" />
                                <?php else : ?>
                                    <p>No photo uploaded.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div id="postbox-container-2" class="postbox-container">
                        <div class="postbox">
                            <h2 class="hndle"><span>Information</span></h2>
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th>নাম</th>
                                        <td><?php echo esc_html($patient->patient_name); ?></td>
                                    </tr>
                                    <tr>
                                        <th>মোবাইল নাম্বার</th>
                                        <td><?php echo esc_html($patient->mobile_number); ?></td>
                                    </tr>
                                    <tr>
                                        <th>বাবার নাম</th>
                                        <td><?php echo esc_html($patient->father_name); ?></td>
                                    </tr>
                                     <tr>
                                        <th>মায়ের নাম</th>
                                        <td><?php echo esc_html($patient->mother_name); ?></td>
                                    </tr>
                                    <tr>
                                        <th>বয়স</th>
                                        <td><?php echo esc_html($patient->age); ?></td>
                                    </tr>
                                    <tr>
                                        <th>রক্তের গ্রুপ</th>
                                        <td><strong><?php echo esc_html($patient->blood_group); ?></strong></td>
                                    </tr>
                                    <tr>
                                        <th>রোগ</th>
                                        <td><?php echo esc_html($patient->disease); ?></td>
                                    </tr>
                                    <tr>
                                        <th>মাসে কত ব্যাগ রক্তের প্রয়োজন</th>
                                        <td><?php echo esc_html($patient->monthly_blood_need); ?> ব্যাগ</td>
                                    </tr>
                                    <tr>
                                        <th>ঠিকানা</th>
                                        <td><?php echo nl2br(esc_html($patient->address)); ?></td>
                                    </tr>
                                    <tr>
                                        <th>পেশা</th>
                                        <td><?php echo esc_html($patient->occupation); ?></td>
                                    </tr>
                                    <tr>
                                        <th>অবিভাবকের পেশা</th>
                                        <td><?php echo esc_html($patient->guardian_occupation); ?></td>
                                    </tr>
                                    <tr>
                                        <th>অন্যান্য তথ্য</th>
                                        <td><?php echo nl2br(esc_html($patient->other_info)); ?></td>
                                    </tr>
                                    <tr>
                                        <th>জমাদানের তারিখ</th>
                                        <td><?php echo esc_html(date("F j, Y, g:i a", strtotime($patient->created_at))); ?></td>
                                    </tr>
                                    <?php
                                    if (current_user_can('manage_options') && !empty($patient->submitted_by_user_id)) {
                                        $submitter = get_userdata($patient->submitted_by_user_id);
                                        if ($submitter) {
                                            $submitter_name = esc_html($submitter->display_name);
                                            $submitter_profile_link = esc_url(get_edit_user_link($submitter->ID));
                                            ?>
                                            <tr>
                                                <th>তথ্য জমা দিয়েছেন</th>
                                                <td><a href="<?php echo $submitter_profile_link; ?>"><?php echo $submitter_name; ?></a></td>
                                            </tr>
                                            <?php
                                        }
                                    }
                                    ?>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
        private function render_accounts_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'bbdc_accounts_transactions';

        $total_income = (float) $wpdb->get_var("SELECT SUM(amount) FROM $table WHERE transaction_type = 'income'");
        $total_expense = (float) $wpdb->get_var("SELECT SUM(amount) FROM $table WHERE transaction_type = 'expense'");
        $current_balance = $total_income - $total_expense;
        ?>
        <div class="wrap">
            <h1>Accounts Management</h1>
            <div id="dashboard-widgets-wrap">
                <div id="dashboard-widgets" class="metabox-holder">
                    <div class="postbox-container">
                        <div class="meta-box-sortables">
                            <div class="postbox">
                                <h2 class="hndle"><span>Summary</span></h2>
                                <div class="inside" style="display: flex; justify-content: space-around; padding: 20px; font-size: 1.5em;">
                                    <div><strong>Total Income:</strong> <span style="color: green;"><?php echo number_format($total_income, 2); ?></span></div>
                                    <div><strong>Total Expense:</strong> <span style="color: red;"><?php echo number_format($total_expense, 2); ?></span></div>
                                    <div><strong>Current Balance:</strong> <span style="color: blue;"><?php echo number_format($current_balance, 2); ?></span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            // Add Income Form:
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="form-wrap">
                <div class="form-field">
                    <label for="transaction_date">Date</label>
                    <input type="text" name="transaction_date" class="bbdc-datepicker" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-field">
                    <label for="remarks">Remarks (Optional)</label>
                    <textarea name="remarks" rows="3" style="width:100%;"></textarea>
                </div>
                <?php submit_button('Add Income'); ?>
            </form>
            
            // Add Expense Form:
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data" class="form-wrap">
                <div class="form-field">
                    <label for="transaction_date">Date</label>
                    <input type="text" name="transaction_date" class="bbdc-datepicker" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-field">
                    <label for="remarks">Remarks (Optional)</label>
                    <textarea name="remarks" rows="3" style="width:100%;"></textarea>
                </div>
                <?php submit_button('Add Expense'); ?>
            </form>
            
            <div style="clear:both;"></div>
            <hr>
            <h2>Transaction History</h2>
            <?php
            $list_table = new BBDC_Accounts_List_Table();
            $list_table->prepare_items();
            $list_table->display();
            ?>
        </div>
        <?php
    }
    
        private function render_daily_tasks_page() {
            echo '<div class="wrap"><h2>All Daily Tasks</h2>';
            $list_table = new BBDC_Daily_Tasks_List_Table();
            $list_table->prepare_items();
            $list_table->display();
            echo '</div>';
    }
    
        private function render_attendance_log_page() {
        echo '<div class="wrap"><h2>Attendance Log</h2>';
        $list_table = new BBDC_Attendance_Log_List_Table();
        $list_table->prepare_items();
        $list_table->display();
        echo '</div>';
    }
    
        private function export_attendance_to_csv() {
        if (!current_user_can('manage_options')) {
            wp_die('Permission denied.');
        }
        
        global $wpdb;
        $sql = "SELECT u.display_name as VolunteerName, al.attendance_time as AttendanceTime
                FROM {$wpdb->prefix}bbdc_attendance_log al
                JOIN {$wpdb->prefix}users u ON al.user_id = u.ID";

        $where = [];
        if (!empty($_GET['s_date_from'])) $where[] = $wpdb->prepare("DATE(al.attendance_time) >= %s", sanitize_text_field($_GET['s_date_from']));
        if (!empty($_GET['s_date_to'])) $where[] = $wpdb->prepare("DATE(al.attendance_time) <= %s", sanitize_text_field($_GET['s_date_to']));
        if (!empty($_GET['s_user_id'])) $where[] = $wpdb->prepare("al.user_id = %d", intval($_GET['s_user_id']));

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " ORDER BY al.attendance_time DESC";
        $items = $wpdb->get_results($sql, ARRAY_A);

        if (!$items) {
            wp_safe_redirect(add_query_arg('error', 'no-data', wp_get_referer()));
            exit;
        }

        $filename = 'bbdc-attendance-log-' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, array_keys($items[0]));
        foreach ($items as $item) {
            fputcsv($output, $item);
        }
        fclose($output);
        exit;
    }
    
        private function render_fee_tracking_page() {
        require_once BBDC_DM_PATH . 'includes/class-bbdc-fee-tracking-list-table.php';
        $list_table = new BBDC_Fee_Tracking_List_Table();
        echo '<div class="wrap"><h2>Monthly Fee Tracking</h2>';
        $list_table->prepare_items();
        $list_table->display();
        echo '</div>';
    }
    
    private function render_accounting_events_list_page() {
    $list_table = new BBDC_Accounting_Events_List_Table();
    echo '<div class="wrap"><h2>Accounting Events</h2>';
    $list_table->prepare_items();
    $list_table->display();
    echo '</div>';
}

    private function render_manage_accounting_event_page($event_id) {
        global $wpdb;
        $events_table = $wpdb->prefix . 'bbdc_accounting_events';
        $permissions_table = $wpdb->prefix . 'bbdc_accounting_event_permissions';
    
        if (isset($_POST['save_managers']) && check_admin_referer('save_event_managers_nonce')) {
            $managers = isset($_POST['managers']) ? array_map('intval', $_POST['managers']) : [];
            $wpdb->delete($permissions_table, ['event_id' => $event_id]);
            foreach ($managers as $user_id) {
                $wpdb->insert($permissions_table, ['event_id' => $event_id, 'user_id' => $user_id]);
            }
            echo '<div class="notice notice-success is-dismissible"><p>Managers updated successfully.</p></div>';
        }
    
        $event = $wpdb->get_row($wpdb->prepare("SELECT * FROM $events_table WHERE id = %d", $event_id));
        if (!$event) { echo '<div class="wrap"><h2>Event not found.</h2></div>'; return; }
    
        $all_volunteers = get_users(['role__in' => ['volunteer', 'bbdc_admin', 'administrator', 'bbdc_accountant']]);
        $assigned_managers = $wpdb->get_col($wpdb->prepare("SELECT user_id FROM $permissions_table WHERE event_id = %d", $event_id));
        ?>
        <div class="wrap">
            <h1>Manage Managers for: <?php echo esc_html($event->event_name); ?></h1>
            <p>Select the volunteers who can manage income and expenses for this specific event.</p>
            <form method="post">
                <?php wp_nonce_field('save_event_managers_nonce'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Assigned Managers</th>
                        <td>
                            <select name="managers[]" multiple="multiple" style="width:100%; min-height: 300px;">
                                <?php foreach ($all_volunteers as $volunteer) : ?>
                                    <option value="<?php echo esc_attr($volunteer->ID); ?>" <?php selected(in_array($volunteer->ID, $assigned_managers)); ?>>
                                        <?php echo esc_html($volunteer->display_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">Hold down the Ctrl (windows) or Command (Mac) button to select multiple options.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Save Managers'); ?>
            </form>
        </div>
        <?php
    }
}