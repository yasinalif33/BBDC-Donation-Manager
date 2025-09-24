<?php
/**
* Plugin Name: BBDC Donation Manager
* Description: A custom plugin to manage blood donation records, volunteers, and more for BBDC.
* Version: 5.2.3
* Author: BBDC
* Author URI: https://bbdc.org.bd/
* License: GPL v2 or later
* Text Domain: bbdc-dm
*/

if (!defined('WPINC')) die;

define('BBDC_DM_VERSION', '5.2.0');
define('BBDC_DM_PATH', plugin_dir_path(__FILE__));
define('BBDC_DM_URL', plugin_dir_url(__FILE__));

register_activation_hook(__FILE__, function() {
require_once BBDC_DM_PATH . 'includes/class-bbdc-activator.php';
BBDC_DM_Activator::activate();
});

    if (function_exists('bbdc_schedule_attendance_reminders')) {
        bbdc_schedule_attendance_reminders();
    }
    
register_deactivation_hook(__FILE__, function() {
require_once BBDC_DM_PATH . 'includes/class-bbdc-deactivator.php';
BBDC_DM_Deactivator::deactivate();
});

    if (function_exists('bbdc_clear_scheduled_reminders')) {
        bbdc_clear_scheduled_reminders();
    }

require_once BBDC_DM_PATH . 'includes/functions.php';
require_once BBDC_DM_PATH . 'includes/class-bbdc-admin-menu.php';
require_once BBDC_DM_PATH . 'includes/class-bbdc-frontend-form.php';
require_once BBDC_DM_PATH . 'includes/class-bbdc-rest-api.php';
require_once BBDC_DM_PATH . 'includes/class-bbdc-patient-list-table.php';
require_once BBDC_DM_PATH . 'includes/class-bbdc-accounts-list-table.php';
require_once BBDC_DM_PATH . 'includes/class-bbdc-daily-tasks-list-table.php';
require_once BBDC_DM_PATH . 'includes/class-bbdc-attendance-log-list-table.php';
require_once BBDC_DM_PATH . 'includes/shortcodes/dashboard.php';
require_once BBDC_DM_PATH . 'includes/shortcodes/lost-password-form.php';
require_once BBDC_DM_PATH . 'includes/class-bbdc-accounting-events-list-table.php';


if (class_exists('BBDC_DM_Admin_Menu')) new BBDC_DM_Admin_Menu();
if (class_exists('BBDC_DM_Frontend_Form')) new BBDC_DM_Frontend_Form();
if (class_exists('BBDC_DM_Rest_Api')) new BBDC_DM_Rest_Api();

add_action('admin_enqueue_scripts', 'bbdc_dm_enqueue_assets');
add_action('wp_enqueue_scripts', 'bbdc_dm_enqueue_assets');
add_action('wp_dashboard_setup', 'bbdc_add_dashboard_widgets');
add_action('user_register', 'bbdc_set_pending_status_on_registration', 10, 1);
add_filter('wp_nav_menu_items', 'bbdc_add_profile_menu_item', 10, 2);

function bbdc_dm_update_db_check() {
    $current_plugin_version = '5.2.3';
    $installed_db_version = get_option('bbdc_dm_db_version');

    if ($installed_db_version != $current_plugin_version) {
        require_once BBDC_DM_PATH . 'includes/class-bbdc-activator.php';
        BBDC_DM_Activator::activate();
        update_option('bbdc_dm_db_version', $current_plugin_version);
    }
}
add_action('plugins_loaded', 'bbdc_dm_update_db_check');