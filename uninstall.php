<?php
if (!defined('WP_UNINSTALL_PLUGIN')) exit;
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bbdc_donors");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bbdc_donation_history");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bbdc_referrers");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bbdc_registered_patients");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bbdc_campaign_guests");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bbdc_accounts_transactions");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bbdc_daily_tasks");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bbdc_attendance_log");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bbdc_notes");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bbdc_note_permissions");
delete_option('bbdc_sms_api_key');
delete_option('bbdc_sms_sender_id');
delete_option('bbdc_sms_template');
remove_role('volunteer');
remove_role('bbdc_admin');
remove_role('blood_response');