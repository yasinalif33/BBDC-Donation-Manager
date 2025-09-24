<?php
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class BBDC_Events_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => 'Event',
            'plural'   => 'Events',
            'ajax'     => false
        ]);
    }

    public function get_columns() {
        return [
            'cb'             => '<input type="checkbox" />',
            'campaign_name'  => 'Event Name',
            'event_category' => 'Category',
            'campaign_date'  => 'Date',
            'venue'          => 'Venue',
            'volunteers'     => 'Attendees'
        ];
    }

    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bbdc_campaigns';
        $per_page = 20;

        $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns()];

        $this->items = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY campaign_date DESC", ARRAY_A);
        
        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");
        $this->set_pagination_args(['total_items' => $total_items, 'per_page' => $per_page]);
    }
    
    public function column_default($item, $column_name) {
        return $item[$column_name] ?? '—';
    }

    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="event[]" value="%s" />', $item['id']);
    }

    public function column_campaign_name($item) {
        $link = sprintf('?page=bbdc-events&action=manage&event_id=%s', $item['id']);
        $delete_url = wp_nonce_url(
            admin_url('admin-post.php?action=bbdc_delete_event&event_id=' . $item['id']),
            'bbdc_delete_event_nonce'
        );

        $actions = [
            'manage' => sprintf('<a href="%s">Manage</a>', $link),
            'delete' => sprintf('<a href="%s" style="color:#a00;" onclick="return confirm(\'Are you sure you want to delete this event and all its data?\')">Delete</a>', $delete_url)
        ];
        return sprintf('<strong><a class="row-title" href="%s">%s</a></strong> %s', $link, $item['campaign_name'], $this->row_actions($actions));
    }
    
    protected function column_volunteers($item) {
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(volunteer_user_id) FROM {$wpdb->prefix}bbdc_campaign_attendance WHERE campaign_id = %d",
            $item['id']
        ));
        return $count;
    }
}