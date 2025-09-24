<?php
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class BBDC_Accounts_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => 'Transaction',
            'plural'   => 'Transactions',
            'ajax'     => false
        ]);
    }

    public function get_columns() {
        return [
            'transaction_date' => 'Date',
            'source'           => 'Source / Purpose',
            'amount'           => 'Amount',
            'entered_by'       => 'Entered By'
        ];
    }

    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bbdc_accounts_transactions';
        $per_page = 20;

        $this->_column_headers = [$this->get_columns(), [], []];
        
        $sql = "SELECT t.*, u.display_name as entered_by 
                FROM {$table_name} t
                LEFT JOIN {$wpdb->prefix}users u ON t.entered_by_user_id = u.ID
                ORDER BY t.transaction_date DESC";
        
        $total_items = $wpdb->get_var(str_replace('SELECT t.*, u.display_name as entered_by', 'SELECT COUNT(t.id)', $sql));

        $this->set_pagination_args(['total_items' => $total_items, 'per_page' => $per_page]);

        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;
        
        $sql .= " LIMIT {$per_page} OFFSET {$offset}";
        
        $this->items = $wpdb->get_results($sql, ARRAY_A);
    }
    
    public function column_default($item, $column_name) {
        return esc_html($item[$column_name] ?? '');
    }

    public function column_amount($item) {
        $amount = number_format($item['amount'], 2);
        if ($item['transaction_type'] === 'income') {
            return "<strong style='color: green;'>+ " . $amount . "</strong>";
        } else {
            $memo_link = $item['memo_url'] ? ' <a href="'.esc_url($item['memo_url']).'" target="_blank">(View Memo)</a>' : '';
            return "<strong style='color: red;'>- " . $amount . "</strong>" . $memo_link;
        }
    }
}