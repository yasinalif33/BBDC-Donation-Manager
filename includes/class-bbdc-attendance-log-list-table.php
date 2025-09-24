<?php
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class BBDC_Attendance_Log_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct(['singular' => 'Attendance Log', 'plural' => 'Attendance Logs', 'ajax' => false]);
    }

    public function get_columns() {
        return [
            'display_name'    => 'Volunteer Name',
            'attendance_time' => 'Attendance Time',
            'activity_description' => 'Activity Report',
        ];
    }

    protected function extra_tablenav($which) {
        if ($which == 'top') {
            $s_date_from = isset($_REQUEST['s_date_from']) ? sanitize_text_field($_REQUEST['s_date_from']) : '';
            $s_date_to = isset($_REQUEST['s_date_to']) ? sanitize_text_field($_REQUEST['s_date_to']) : '';
            $s_user_id = isset($_REQUEST['s_user_id']) ? intval($_REQUEST['s_user_id']) : 0;

            $users = get_users(['role__in' => ['blood_response', 'bbdc_admin', 'administrator']]);
            ?>
            <div class="alignleft actions">
                <select name="s_user_id">
                    <option value="">All Volunteers</option>
                    <?php foreach ($users as $user) : ?>
                        <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($s_user_id, $user->ID); ?>>
                            <?php echo esc_html($user->display_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" class="bbdc-datepicker" name="s_date_from" placeholder="Date From" value="<?php echo esc_attr($s_date_from); ?>">
                <input type="text" class="bbdc-datepicker" name="s_date_to" placeholder="Date To" value="<?php echo esc_attr($s_date_to); ?>">
                <input type="submit" name="filter_action" class="button" value="Filter">
                <a href="<?php echo admin_url('admin.php?page=bbdc-attendance-log'); ?>" class="button">Clear</a>
            </div>
            <div class="alignleft actions">
                 <a href="<?php echo esc_url(add_query_arg(['action' => 'export_attendance_csv'] + $_GET)); ?>" class="button button-primary">Export to CSV</a>
            </div>
            <?php
        }
    }

    public function prepare_items() {
        global $wpdb;
        $this->_column_headers = [$this->get_columns(), [], []];
        
        $sql = "SELECT al.*, u.display_name 
                FROM {$wpdb->prefix}bbdc_attendance_log al
                JOIN {$wpdb->prefix}users u ON al.user_id = u.ID";

        $where = [];
        if (!empty($_REQUEST['s_date_from'])) $where[] = $wpdb->prepare("DATE(al.attendance_time) >= %s", sanitize_text_field($_REQUEST['s_date_from']));
        if (!empty($_REQUEST['s_date_to'])) $where[] = $wpdb->prepare("DATE(al.attendance_time) <= %s", sanitize_text_field($_REQUEST['s_date_to']));
        if (!empty($_REQUEST['s_user_id'])) $where[] = $wpdb->prepare("al.user_id = %d", intval($_REQUEST['s_user_id']));
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " ORDER BY al.attendance_time DESC";
        
        $this->items = $wpdb->get_results($sql, ARRAY_A);
    }
    
    public function column_default($item, $column_name) {
        return esc_html($item[$column_name]);
    }

    public function column_attendance_time($item) {
        return date("F j, Y, g:i a", strtotime($item['attendance_time']));
    }
    
    public function column_activity_description($item) {
        return !empty($item['activity_description']) ? nl2br(esc_html($item['activity_description'])) : 'N/A';
    }
}