<?php
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class BBDC_Volunteers_List_Table extends WP_List_Table {

    private $status;

    public function __construct($status = 'approved') {
        $this->status = $status;
        parent::__construct([
            'singular' => 'Volunteer',
            'plural'   => 'Volunteers',
            'ajax'     => false
        ]);
    }

    public function get_columns() {
        $columns = [
            'cb'       => '<input type="checkbox" />',
            'username' => 'Username',
            'name'     => 'Name',
            'mobile'   => 'Mobile',
            'email'    => 'Email',
            'role'     => 'Role',
        ];
        if ($this->status === 'pending') {
            unset($columns['role']);
        }
        return $columns;
    }

    protected function column_cb($item) {
        return sprintf('<input type="checkbox" name="user[]" value="%s" />', $item->ID);
    }

    protected function column_username($item) {
        $edit_link = esc_url(get_edit_user_link($item->ID));
        $actions = [];

        if ($this->status === 'pending') {
            $actions['approve'] = sprintf('<a href="#" class="approve-volunteer" data-id="%d">Approve</a>', $item->ID);
            $actions['reject'] = sprintf('<a href="#" class="reject-volunteer" data-id="%d" style="color:#a00;">Reject</a>', $item->ID);
        }
        
        $actions['edit'] = '<a href="' . $edit_link . '">View/Edit Profile</a>';

        $output = sprintf('<strong><a class="row-title" href="%s">%s</a></strong>', $edit_link, esc_html($item->user_login));
        $output .= $this->row_actions($actions);

        return $output;
    }
    
    protected function column_name($item) { 
        return esc_html($item->display_name); 
    }

    protected function column_email($item) { 
        return esc_html($item->user_email); 
    }

    protected function column_mobile($item) { 
        return get_user_meta($item->ID, 'bbdc_mobile_number', true); 
    }

    protected function column_role($item) {
        if ($this->status !== 'approved') {
            return !empty($item->roles) ? implode(', ', array_map('ucfirst', $item->roles)) : 'N/A';
        }

        $all_roles = ['volunteer' => 'Volunteer', 'blood_response' => 'Blood Response', 'bbdc_admin' => 'BBDC Admin'];
        $user_roles = (array) $item->roles;
        
        if (in_array('administrator', $user_roles)) {
            return 'Administrator';
        }

        ob_start();
        ?>
        <div class="role-management-cell" data-user-id="<?php echo $item->ID; ?>">
            <?php foreach ($all_roles as $role_key => $role_name) : ?>
                <label style="display: block; margin-bottom: 5px;">
                    <input type="checkbox" name="volunteer_roles[]" value="<?php echo esc_attr($role_key); ?>" <?php checked(in_array($role_key, $user_roles)); ?>>
                    <?php echo esc_html($role_name); ?>
                </label>
            <?php endforeach; ?>
            <button class="button button-secondary save-volunteer-role" style="margin-top: 10px;">Save Roles</button>
            <span class="spinner" style="float: none; vertical-align: middle;"></span>
        </div>
        <?php
        return ob_get_clean();
    }
    
    protected function extra_tablenav($which) {
        if ($which == 'top') {
            $s_mobile = isset($_REQUEST['s_mobile']) ? sanitize_text_field($_REQUEST['s_mobile']) : '';
            $s_date_from = isset($_REQUEST['s_date_from']) ? sanitize_text_field($_REQUEST['s_date_from']) : '';
            $s_date_to = isset($_REQUEST['s_date_to']) ? sanitize_text_field($_REQUEST['s_date_to']) : '';
            ?>
            <div class="alignleft actions">
                <input type="text" name="s_mobile" placeholder="Filter by Mobile" value="<?php echo esc_attr($s_mobile); ?>">
                <input type="text" class="bbdc-datepicker" name="s_date_from" placeholder="Registered From" value="<?php echo esc_attr($s_date_from); ?>" autocomplete="off">
                <input type="text" class="bbdc-datepicker" name="s_date_to" placeholder="Registered To" value="<?php echo esc_attr($s_date_to); ?>" autocomplete="off">
                <input type="submit" name="filter_action" class="button" value="Filter">
                <a href="<?php echo admin_url('admin.php?page=' . $_REQUEST['page']); ?>" class="button">Clear</a>
            </div>
            <div class="alignleft actions">
                <a href="<?php echo esc_url(add_query_arg(['action' => 'export_csv_volunteers', 'status' => $this->status] + $_GET)); ?>" class="button button-primary">Export to CSV</a>
            </div>
            <?php
        }
    }

    public function prepare_items() {
        $this->_column_headers = [$this->get_columns(), [], []];
        $per_page = 20; 
        $current_page = $this->get_pagenum();
        
        $roles_to_include = ['volunteer', 'bbdc_admin', 'blood_response'];
        if ($this->status === 'approved') {
            $roles_to_include[] = 'administrator';
        }
        
        $args = [
            'role__in' => $roles_to_include, 
            'orderby' => 'user_registered', 
            'order' => 'DESC',
            'number'   => $per_page, 
            'offset' => ($current_page - 1) * $per_page,
        ];
        
        if ($this->status === 'pending') {
            $args['meta_query'] = [['key' => 'bbdc_approval_status', 'value' => 'pending']];
        } else if ($this->status === 'approved') {
            $args['meta_query'] = [
                'relation' => 'OR',
                ['key' => 'bbdc_approval_status', 'value' => 'approved'],
                ['key' => 'bbdc_approval_status', 'compare' => 'NOT EXISTS'],
            ];
        }
        
        if (!empty($_REQUEST['s_mobile'])) { 
            if (!isset($args['meta_query']['relation'])) { $args['meta_query'] = ['relation' => 'AND']; }
            $args['meta_query'][] = ['key' => 'bbdc_mobile_number', 'value' => sanitize_text_field($_REQUEST['s_mobile']), 'compare' => 'LIKE']; 
        }
        
        if (!empty($_REQUEST['s_date_from']) || !empty($_REQUEST['s_date_to'])) {
            $args['date_query'] = ['inclusive' => true];
            if(!empty($_REQUEST['s_date_from'])) $args['date_query']['after'] = sanitize_text_field($_REQUEST['s_date_from']);
            if(!empty($_REQUEST['s_date_to'])) $args['date_query']['before'] = sanitize_text_field($_REQUEST['s_date_to']);
        }
        
        $total_items_query = new WP_User_Query($args);
        $total_items = $total_items_query->get_total();

        $this->set_pagination_args(['total_items' => $total_items, 'per_page' => $per_page]);
        
        $user_query = new WP_User_Query($args);
        $this->items = $user_query->get_results();
    }
}