<?php
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class BBDC_History_List_Table extends WP_List_Table {

    private $filter_type;

    public function __construct($filter_type = 'pending') {
        $this->filter_type = $filter_type;
        parent::__construct([
            'singular' => 'History',
            'plural'   => 'Histories',
            'ajax'     => false
        ]);
    }

    public function get_columns() {
        $columns = [
            'cb'            => '<input type="checkbox" />',
            'donor_name'    => 'Donor Name',
            'donor_mobile'  => 'Mobile',
            'blood_group'   => 'Blood Group',
            'donation_date' => 'Donation Date',
            'referrer_name' => 'Referrer',
            'submitted_by'  => 'Submitted By',
            'actions'       => 'Actions'
        ];
        if ($this->filter_type === 'others') {
            unset($columns['referrer_name']);
        }
        return $columns;
    }

protected function extra_tablenav($which) {
    if ($which == 'top') {
        global $wpdb;
        $s_mobile      = isset($_REQUEST['s_mobile']) ? sanitize_text_field($_REQUEST['s_mobile']) : '';
        $s_blood_group = isset($_REQUEST['s_blood_group']) ? sanitize_text_field($_REQUEST['s_blood_group']) : '';
        $s_date_from   = isset($_REQUEST['s_date_from']) ? sanitize_text_field($_REQUEST['s_date_from']) : '';
        $s_date_to     = isset($_REQUEST['s_date_to']) ? sanitize_text_field($_REQUEST['s_date_to']) : '';
        $s_referrer    = isset($_REQUEST['s_referrer']) ? sanitize_text_field($_REQUEST['s_referrer']) : '';
        ?>
        <form method="get">
            <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>">
            <input type="hidden" name="type" value="<?php echo esc_attr($this->filter_type); ?>">

            <div class="alignleft actions">
                <input type="text" name="s_mobile" placeholder="Filter by Mobile" value="<?php echo esc_attr($s_mobile); ?>">
                <select name="s_blood_group">
                    <option value="">All Blood Groups</option>
                    <option value="A+" <?php selected($s_blood_group, 'A+'); ?>>A+</option>
                    <option value="A-" <?php selected($s_blood_group, 'A-'); ?>>A-</option>
                    <option value="B+" <?php selected($s_blood_group, 'B+'); ?>>B+</option>
                    <option value="B-" <?php selected($s_blood_group, 'B-'); ?>>B-</option>
                    <option value="AB+" <?php selected($s_blood_group, 'AB+'); ?>>AB+</option>
                    <option value="AB-" <?php selected($s_blood_group, 'AB-'); ?>>AB-</option>
                    <option value="O+" <?php selected($s_blood_group, 'O+'); ?>>O+</option>
                    <option value="O-" <?php selected($s_blood_group, 'O-'); ?>>O-</option>
                </select>

                <?php if ($this->filter_type !== 'others'):
                    $referrers = $wpdb->get_results("SELECT DISTINCT referrer_name FROM {$wpdb->prefix}bbdc_donation_history WHERE referrer_name IS NOT NULL AND referrer_name != '' ORDER BY referrer_name ASC");
                ?>
                <select name="s_referrer">
                    <option value="">All Referrers</option>
                    <?php foreach ($referrers as $ref): ?>
                        <option value="<?php echo esc_attr($ref->referrer_name); ?>" <?php selected($s_referrer, $ref->referrer_name); ?>><?php echo esc_html($ref->referrer_name); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>

                <input type="text" class="bbdc-datepicker" name="s_date_from" placeholder="Date From" value="<?php echo esc_attr($s_date_from); ?>" autocomplete="off">
                <input type="text" class="bbdc-datepicker" name="s_date_to" placeholder="Date To" value="<?php echo esc_attr($s_date_to); ?>" autocomplete="off">

                <input type="submit" class="button" value="Filter">
                <a href="<?php echo admin_url('admin.php?page=' . $_REQUEST['page']); ?>" class="button">Clear</a>
            </div>
        </form>

        <div class="alignleft actions">
            <a href="<?php echo esc_url(add_query_arg(['action' => 'export_csv_history', 'type' => $this->filter_type] + $_GET)); ?>" class="button button-primary">Export to CSV</a>
        </div>
        <?php
    }
}

    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bbdc_donation_history';
        $per_page = 20;
        
        $this->_column_headers = [$this->get_columns(), [], []];
    
        $sql_select = "SELECT h.*, u.display_name as submitted_by";
        $sql_from = "FROM {$table_name} h LEFT JOIN {$wpdb->prefix}users u ON h.submitted_by_user_id = u.ID";
        
        $where = [];
        $params = [];
    
        if ($this->filter_type == 'pending') {
            $where[] = "h.status = %s";
            $params[] = 'pending';
        } elseif ($this->filter_type == 'bbdc') {
            $where[] = "h.status = %s AND h.is_bbdc_donation = 1";
            $params[] = 'approved';
        } elseif ($this->filter_type == 'others') {
            $where[] = "h.status = %s AND h.is_bbdc_donation = 0";
            $params[] = 'approved';
        }
    
        if (!empty($_REQUEST['s_mobile'])) {
            $where[] = "h.donor_mobile LIKE %s";
            $params[] = '%' . $wpdb->esc_like(sanitize_text_field($_REQUEST['s_mobile'])) . '%';
        }
        if (!empty($_REQUEST['s_blood_group'])) {
            $where[] = "h.blood_group = %s";
            $params[] = sanitize_text_field($_REQUEST['s_blood_group']);
        }
        if ($this->filter_type !== 'others' && !empty($_REQUEST['s_referrer'])) {
            $where[] = "h.referrer_name = %s";
            $params[] = sanitize_text_field($_REQUEST['s_referrer']);
        }
        if (!empty($_REQUEST['s_date_from'])) {
            $where[] = "h.donation_date >= %s";
            $params[] = sanitize_text_field($_REQUEST['s_date_from']);
        }
        if (!empty($_REQUEST['s_date_to'])) {
            $where[] = "h.donation_date <= %s";
            $params[] = sanitize_text_field($_REQUEST['s_date_to']);
        }
    
        $where_clause = !empty($where) ? " WHERE " . implode(' AND ', $where) : "";
    
        $total_items_sql = "SELECT COUNT(h.id) " . $sql_from . $where_clause;
        $total_items = $wpdb->get_var($wpdb->prepare($total_items_sql, $params));
        
        $this->set_pagination_args(['total_items' => $total_items, 'per_page' => $per_page]);
    
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;
        
        $sql = $sql_select . " " . $sql_from . $where_clause . " ORDER BY h.id DESC LIMIT %d OFFSET %d";
        
        $final_params = array_merge($params, [$per_page, $offset]);
    
        $this->items = $wpdb->get_results($wpdb->prepare($sql, $final_params), ARRAY_A);
    }

    public function column_default($item, $column_name) { 
        return esc_html($item[$column_name] ?? ''); 
    }

    public function column_cb($item) { 
        return sprintf('<input type="checkbox" name="history_id[]" value="%s" />', $item['id']); 
    }

    public function column_donor_name($item) {
        global $wpdb;
        $donor_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}bbdc_donors WHERE mobile_number = %s", $item['donor_mobile']));
        if ($donor_id) {
            $link = sprintf('?page=bbdc-donor-tracking&action=edit&donor_id=%s', $donor_id);
            return sprintf('<strong><a href="%s">%s</a></strong>', esc_url($link), esc_html($item['donor_name']));
        }
        return sprintf('<strong>%s</strong>', esc_html($item['donor_name']));
    }

    public function column_submitted_by($item) {
        return !empty($item['submitted_by']) ? esc_html($item['submitted_by']) : 'N/A';
    }

    public function column_actions($item) {
        if ($item['status'] === 'pending') {
            $approve_button = sprintf('<a href="#" class="button button-primary approve-donation" data-id="%s">Approve</a>', $item['id']);
            $reject_button = sprintf('<a href="#" class="button button-secondary reject-donation" data-id="%s" style="margin-left:5px;">Reject</a>', $item['id']);
            return $approve_button . ' ' . $reject_button;
        } elseif ($item['status'] === 'approved') {
            $delete_button = sprintf('<a href="#" class="button button-secondary delete-history" data-id="%s">Delete</a>', $item['id']);
            return $delete_button;
        }
        return '—';
    }
}