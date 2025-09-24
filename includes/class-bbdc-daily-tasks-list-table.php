<?php
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class BBDC_Daily_Tasks_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct(['singular' => 'Daily Task', 'plural' => 'Daily Tasks', 'ajax' => false]);
    }

    public function get_columns() {
        return [
            'created_at' => 'Date',
            'display_name' => 'Submitted By',
            'task_description' => 'Task Description',
        ];
    }

    public function prepare_items() {
        global $wpdb;
        $this->_column_headers = [$this->get_columns(), [], []];
        
        $sql = "SELECT dt.*, u.display_name 
                FROM {$wpdb->prefix}bbdc_daily_tasks dt
                JOIN {$wpdb->prefix}users u ON dt.user_id = u.ID
                ORDER BY dt.created_at DESC";
        
        $this->items = $wpdb->get_results($sql, ARRAY_A);
    }
    
    public function column_default($item, $column_name) {
        return esc_html($item[$column_name]);
    }

    public function column_task_description($item) {
        return nl2br(esc_html($item['task_description']));
    }
}