<?php
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class BBDC_Fee_Tracking_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => 'Fee Payment',
            'plural'   => 'Fee Payments',
            'ajax'     => false
        ]);
    }

    public function get_columns() {
        return [
            'display_name' => 'Volunteer Name',
            'order_id'     => 'Order ID',
            'payment_for'  => 'Payment For',
            'amount'       => 'Amount',
            'status'       => 'Status',
            'order_date'   => 'Payment Date'
        ];
    }

    protected function extra_tablenav($which) {
        if ($which == 'top') {
            $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            $current_year = date('Y');
            $selected_user = isset($_GET['filter_by_volunteer']) ? intval($_GET['filter_by_volunteer']) : 0;
            $selected_month = isset($_GET['filter_by_month']) ? sanitize_text_field($_GET['filter_by_month']) : '';
            $selected_year = isset($_GET['filter_by_year']) ? intval($_GET['filter_by_year']) : 0;
            
            $volunteers = get_users(['role__in' => ['volunteer', 'bbdc_admin', 'administrator']]);
            
            echo '<div class="alignleft actions">';
            
            // Volunteer Filter
            echo '<select name="filter_by_volunteer"><option value="">All Volunteers</option>';
            foreach ($volunteers as $user) {
                printf('<option value="%d" %s>%s</option>', $user->ID, selected($selected_user, $user->ID, false), esc_html($user->display_name));
            }
            echo '</select>';

            // Month Filter
            echo '<select name="filter_by_month"><option value="">All Months</option>';
            foreach ($months as $month) {
                printf('<option value="%s" %s>%s</option>', $month, selected($selected_month, $month, false), $month);
            }
            echo '</select>';

            // Year Filter
            echo '<select name="filter_by_year"><option value="">All Years</option>';
            for ($i = $current_year; $i >= $current_year - 5; $i--) {
                printf('<option value="%d" %s>%d</option>', $i, selected($selected_year, $i, false), $i);
            }
            echo '</select>';

            submit_button('Filter', 'button', 'filter_action', false);
            echo '</div>';
        }
    }

    public function prepare_items() {
        global $wpdb;
        $this->_column_headers = [$this->get_columns(), [], []];
        
        $product_ids = [17190, 17198, 17199, 17200, 17201];
        $product_ids_placeholder = implode(',', $product_ids);

        $sql = "
            SELECT 
                p.ID as order_id, 
                p.post_date as order_date, 
                pm_customer.meta_value as customer_id,
                pm_total.meta_value as amount,
                pm_month.meta_value as fee_month,
                pm_year.meta_value as fee_year,
                u.display_name,
                p.post_status as status
            FROM {$wpdb->prefix}posts as p
            JOIN {$wpdb->prefix}postmeta as pm_customer ON p.ID = pm_customer.post_id AND pm_customer.meta_key = '_customer_user'
            JOIN {$wpdb->prefix}woocommerce_order_items as oi ON p.ID = oi.order_id
            JOIN {$wpdb->prefix}woocommerce_order_itemmeta as oim ON oi.order_item_id = oim.order_item_id AND oim.meta_key = '_product_id' AND oim.meta_value IN ($product_ids_placeholder)
            LEFT JOIN {$wpdb->prefix}users as u ON pm_customer.meta_value = u.ID
            LEFT JOIN {$wpdb->prefix}postmeta as pm_total ON p.ID = pm_total.post_id AND pm_total.meta_key = '_order_total'
            LEFT JOIN {$wpdb->prefix}postmeta as pm_month ON p.ID = pm_month.post_id AND pm_month.meta_key = '_bbdc_fee_month'
            LEFT JOIN {$wpdb->prefix}postmeta as pm_year ON p.ID = pm_year.post_id AND pm_year.meta_key = '_bbdc_fee_year'
            WHERE p.post_type = 'shop_order'
        ";

        // Apply filters
        if (!empty($_GET['filter_by_volunteer'])) {
            $sql .= $wpdb->prepare(" AND pm_customer.meta_value = %d", intval($_GET['filter_by_volunteer']));
        }
        if (!empty($_GET['filter_by_month'])) {
            $sql .= $wpdb->prepare(" AND pm_month.meta_value = %s", sanitize_text_field($_GET['filter_by_month']));
        }
        if (!empty($_GET['filter_by_year'])) {
            $sql .= $wpdb->prepare(" AND pm_year.meta_value = %d", intval($_GET['filter_by_year']));
        }

        $sql .= " GROUP BY p.ID ORDER BY p.post_date DESC";

        $this->items = $wpdb->get_results($sql, ARRAY_A);
    }
    
    public function column_default($item, $column_name) {
        return $item[$column_name] ?? '';
    }

    public function column_payment_for($item) {
        return ($item['fee_month'] && $item['fee_year']) ? esc_html($item['fee_month'] . ', ' . $item['fee_year']) : 'N/A';
    }

    public function column_status($item) {
        return wc_get_order_status_name(str_replace('wc-', '', $item['status']));
    }
}