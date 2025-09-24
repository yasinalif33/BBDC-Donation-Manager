<?php
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class BBDC_Patient_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => 'Registered Patient',
            'plural'   => 'Registered Patients',
            'ajax'     => false
        ]);
    }

    public function get_columns() {
        return [
            'cb'                   => '<input type="checkbox" />',
            'patient_name'         => 'নাম',
            'mobile_number'        => 'মোবাইল',
            'disease'              => 'রোগ',
            'blood_group'          => 'রক্তের গ্রুপ',
            'monthly_blood_need'   => 'মাসিক রক্তের প্রয়োজন',
            'address'              => 'ঠিকানা',
        ];
    }
    
    protected function extra_tablenav($which) {
        if ($which == 'top') {
            $current_disease = isset($_GET['filter_by_disease']) ? sanitize_text_field($_GET['filter_by_disease']) : '';
            ?>
            <div class="alignleft actions">
                <select name="filter_by_disease">
                    <option value="">সকল রোগ</option>
                    <option value="থ্যালাসেমিয়া" <?php selected($current_disease, 'থ্যালাসেমিয়া'); ?>>থ্যালাসেমিয়া</option>
                    <option value="কিডনি ডায়ালাইসিস" <?php selected($current_disease, 'কিডনি ডায়ালাইসিস'); ?>>কিডনি ডায়ালাইসিস</option>
                    <option value="ক্যান্সার" <?php selected($current_disease, 'ক্যান্সার'); ?>>ক্যান্সার</option>
                </select>
                <input type="submit" name="filter_action" id="post-query-submit" class="button" value="Filter">
            </div>
            <div class="alignleft actions">
                <a href="<?php echo esc_url(add_query_arg(['action' => 'export_patients_csv'] + $_GET)); ?>" class="button button-primary">Export to CSV</a>
            </div>
            <?php
        }
    }

    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bbdc_registered_patients';
        $per_page = 20;

        $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns()];
        
        $sql = "SELECT * FROM {$table_name}";
        $where = [];

        if (!empty($_REQUEST['s'])) {
            $search = esc_sql($wpdb->esc_like(sanitize_text_field($_REQUEST['s'])));
            $where[] = $wpdb->prepare("(patient_name LIKE %s OR mobile_number LIKE %s)", "%{$search}%", "%{$search}%");
        }

        if (!empty($_REQUEST['filter_by_disease'])) {
            $disease = sanitize_text_field($_REQUEST['filter_by_disease']);
            $where[] = $wpdb->prepare("disease = %s", $disease);
        }
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        $total_items = $wpdb->get_var(str_replace('SELECT *', 'SELECT COUNT(id)', $sql));

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page
        ]);

        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;
        
        $sql .= " ORDER BY id DESC LIMIT {$per_page} OFFSET {$offset}";
        
        $this->items = $wpdb->get_results($sql, ARRAY_A);
    }
    
    public function column_default($item, $column_name) {
        return esc_html($item[$column_name] ?? 'N/A');
    }

    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="patient[]" value="%s" />', $item['id']);
    }

    public function column_patient_name($item) {
        $view_details_url = add_query_arg([
            'page' => $_REQUEST['page'],
            'action' => 'view_details',
            'patient_id' => $item['id']
        ], admin_url('admin.php'));

        $actions = [
            'view'   => sprintf('<a href="%s">View Details</a>', esc_url($view_details_url)),
            'delete' => sprintf('<a href="#" class="delete-patient" data-id="%d" style="color:#a00;">Delete</a>', $item['id'])
        ];

        return sprintf(
            '<strong><a class="row-title" href="%s">%s</a></strong> %s',
            esc_url($view_details_url),
            esc_html($item['patient_name']),
            $this->row_actions($actions)
        );
    }
    
    public function column_monthly_blood_need($item) {
        return esc_html($item['monthly_blood_need']) . ' ব্যাগ';
    }
}