<?php
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class BBDC_Donor_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => 'Donor',
            'plural'   => 'Donors',
            'ajax'     => false
        ]);
    }

    public function get_columns() {
        return [
            'cb'                 => '<input type="checkbox" />',
            'donor_name'         => 'Donor Name',
            'mobile_number'      => 'Mobile',
            'blood_group'        => 'Blood Group',
            'donor_location'     => 'Location',
            'last_donation_date' => 'Last Donation Date',
            'availability'       => 'Availability',
            'send_greeting'      => 'Send Greeting'
        ];
    }

    protected function extra_tablenav($which) {
        if ($which === 'top') {
            $search_term = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
            $blood_group_filter = isset($_REQUEST['blood_group_filter']) ? sanitize_text_field($_REQUEST['blood_group_filter']) : '';
            ?>
            <form method="get">
                <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>">
                <div class="alignleft actions">
                    <input type="search" name="s" placeholder="Search by Name or Mobile" value="<?php echo esc_attr($search_term); ?>">
                    <select name="blood_group_filter">
                        <option value="">All Blood Groups</option>
                        <?php
                        $groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                        foreach ($groups as $group) {
                            printf('<option value="%s" %s>%s</option>', esc_attr($group), selected($blood_group_filter, $group, false), esc_html($group));
                        }
                        ?>
                    </select>
                    <input type="submit" class="button" value="Filter Donors">
                    <a href="<?php echo admin_url('admin.php?page=' . esc_attr($_REQUEST['page'])); ?>" class="button">Clear</a>
                </div>
            </form>
            <div class="alignleft actions">
                <a href="<?php echo esc_url(add_query_arg(['action' => 'export_csv_donors'] + $_GET)); ?>" class="button button-primary">Export to CSV</a>
            </div>
            <?php
        }
    }

    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bbdc_donors';
        $per_page = 20;

        $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns()];

        $sql = "SELECT * FROM {$table_name}";
        $where = [];
        $params = [];

        if (!empty($_REQUEST['s'])) {
            $search_term = '%' . $wpdb->esc_like(sanitize_text_field($_REQUEST['s'])) . '%';
            $where[] = "(donor_name LIKE %s OR mobile_number LIKE %s)";
            $params[] = $search_term;
            $params[] = $search_term;
        }

        if (!empty($_REQUEST['blood_group_filter'])) {
            $where[] = "blood_group = %s";
            $params[] = sanitize_text_field($_REQUEST['blood_group_filter']);
        }

        $where_clause = !empty($where) ? " WHERE " . implode(' AND ', $where) : "";

        $total_items_query = "SELECT COUNT(id) FROM {$table_name}" . $where_clause;
        $total_items = $wpdb->get_var($wpdb->prepare($total_items_query, $params));

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page
        ]);

        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;

        $final_query = $sql . $where_clause . " ORDER BY last_donation_date DESC, id DESC LIMIT %d OFFSET %d";
        $final_params = array_merge($params, [$per_page, $offset]);

        $this->items = $wpdb->get_results($wpdb->prepare($final_query, $final_params), ARRAY_A);
    }

    public function column_default($item, $column_name) {
        return esc_html($item[$column_name] ?? 'N/A');
    }

    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="donor[]" value="%s" />', esc_attr($item['id']));
    }

    public function column_donor_name($item) {
        $link = sprintf('?page=%s&action=edit&donor_id=%s', esc_attr($_REQUEST['page']), esc_attr($item['id']));
        $actions = [
            'edit'   => sprintf('<a href="%s">Edit Profile</a>', esc_url($link)),
            'delete' => sprintf('<a href="#" class="delete-donor" data-id="%s" style="color:#a00;">Delete</a>', esc_attr($item['id']))
        ];
        return sprintf('<strong><a href="%s">%s</a></strong> %s', esc_url($link), esc_html($item['donor_name']), $this->row_actions($actions));
    }

    public function column_availability($item) {
        $last_date = $item['last_donation_date'];
        if (!$last_date) return '<span class="availability-status status-green">Ready</span>';
        try {
            $diff = (new DateTime())->diff(new DateTime($last_date))->days;
            if ($diff < 90) return '<span class="availability-status status-red">Not Available</span>';
            if ($diff < 120) return '<span class="availability-status status-orange">Emergency Only</span>';
            return '<span class="availability-status status-green">Ready to Donate</span>';
        } catch (Exception $e) {
            return 'Invalid Date';
        }
    }

    public function column_send_greeting($item) {
        return sprintf(
            '<button class="button button-secondary send-greeting-sms" data-mobile="%s" data-name="%s">Send SMS</button>',
            esc_attr($item['mobile_number']),
            esc_attr($item['donor_name'])
        );
    }

    public function get_sortable_columns() {
        return [
            'donor_name'         => ['donor_name', true],
            'blood_group'        => ['blood_group', false],
            'last_donation_date' => ['last_donation_date', true]
        ];
    }
}
