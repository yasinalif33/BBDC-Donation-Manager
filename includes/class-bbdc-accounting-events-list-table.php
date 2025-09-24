<?php
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class BBDC_Accounting_Events_List_Table extends WP_List_Table {
    public function get_columns() {
        return [
            'event_name' => 'Event Name',
            'date_range' => 'Date Range',
            'managers'   => 'Managers'
        ];
    }

    public function prepare_items() {
        global $wpdb;
        $this->_column_headers = [$this->get_columns(), [], []];
        $this->items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bbdc_accounting_events ORDER BY start_date DESC", ARRAY_A);
    }

    public function column_default($item, $column_name) {
        return esc_html($item[$column_name] ?? '');
    }

    public function column_event_name($item) {
        $manage_url = add_query_arg([
            'page'   => 'bbdc-accounting-events',
            'action' => 'manage',
            'event_id' => $item['id']
        ], admin_url('admin.php'));

        $actions = [
            'manage' => sprintf('<a href="%s">Manage Users</a>', esc_url($manage_url))
        ];
        return sprintf('<strong>%s</strong> %s', esc_html($item['event_name']), $this->row_actions($actions));
    }

    public function column_date_range($item) {
        $start = date('d M, Y', strtotime($item['start_date']));
        $end = date('d M, Y', strtotime($item['end_date']));
        return "$start - $end";
    }

    public function column_managers($item) {
        global $wpdb;
        $user_ids = $wpdb->get_col($wpdb->prepare("SELECT user_id FROM {$wpdb->prefix}bbdc_accounting_event_permissions WHERE event_id = %d", $item['id']));
        if (empty($user_ids)) {
            return 'None';
        }
        $user_names = [];
        foreach($user_ids as $user_id) {
            $user = get_userdata($user_id);
            if($user) $user_names[] = $user->display_name;
        }
        return implode(', ', $user_names);
    }
}