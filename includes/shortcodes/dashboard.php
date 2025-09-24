<?php
if (!defined('ABSPATH')) exit;

function bbdc_render_volunteer_dashboard_shortcode() {
    static $has_run = false;
    if ($has_run) {
        return '';
    }
    $has_run = true;

    if (!is_user_logged_in()) {
        $login_url = home_url('/volunteer-area/');
        return '<p>Please log in to view the dashboard. <a href="' . esc_url($login_url) . '">Login here</a>.</p>';
    }

    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    $user_roles = (array) $current_user->roles;
    
    $is_admin = in_array('administrator', $user_roles) || in_array('bbdc_admin', $user_roles);
    
    global $wpdb;
    $commercial_dept_id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}bbdc_departments WHERE name = 'Commercial Department'");
    $is_commercial = $commercial_dept_id ? $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}bbdc_board_members WHERE user_id = %d AND department_id = %d", $user_id, $commercial_dept_id)) : false;
    
    $is_blood_response = in_array('blood_response', $user_roles);
    
    $profile_pic = get_user_meta($user_id, 'profile_pic_url', true) ?: get_avatar_url($user_id);
    $blood_group = get_user_meta($user_id, 'blood_group', true);
    $session = get_user_meta($user_id, 'training_session', true);
    
    $menus_to_render = [];

    if ($is_admin) {
        $menus_to_render['Admin Menu'] = [
            ['title' => 'Dashboard', 'icon' => 'fa-tachometer-alt', 'url' => admin_url('/')],
            ['title' => 'Donors', 'icon' => 'fa-users', 'url' => admin_url('admin.php?page=bbdc-donor-tracking')],
            ['title' => 'Volunteers', 'icon' => 'fa-user-friends', 'url' => admin_url('admin.php?page=bbdc-volunteers')],
            ['title' => 'Events', 'icon' => 'fa-calendar-alt', 'url' => admin_url('admin.php?page=bbdc-events')],
            ['title' => 'Accounts', 'icon' => 'fa-wallet', 'url' => admin_url('admin.php?page=bbdc-accounts')],
            ['title' => 'Patients', 'icon' => 'fa-procedures', 'url' => admin_url('admin.php?page=bbdc-registered-patients')],
        ];
    } else {
        $main_menu = [];
        $main_menu[] = ['title' => 'Donation Form', 'icon' => 'fa-tint', 'url' => home_url('/donation-form/')];
        $main_menu[] = ['title' => 'Donors', 'icon' => 'fa-search', 'url' => home_url('/donors/')];
        if ($is_commercial) $main_menu[] = ['title' => 'Accounts', 'icon' => 'fa-wallet', 'url' => home_url('/accounts/')];
        if ($is_blood_response) $main_menu[] = ['title' => 'Attendance', 'icon' => 'fa-hand-pointer', 'url' => home_url('/attendance/')];
        $menus_to_render['Main Menu'] = $main_menu;
    }
    
    $menus_to_render['General Menu'] = [
        ['title' => 'My Profile', 'icon' => 'fa-user-circle', 'url' => home_url('/my-profile/')],
        ['title' => 'My Activity', 'icon' => 'fa-history', 'url' => home_url('/my-profile/')],
        ['title' => 'Management Board', 'icon' => 'fa-sitemap', 'url' => home_url('/board/')],
        ['title' => 'Logout', 'icon' => 'fa-sign-out-alt', 'url' => wp_logout_url(home_url('/'))],
    ];

    ob_start();
    ?>
    <div class="bbdc-dashboard">
        <div class="bbdc-profile-header">
            <div class="bbdc-profile-header-content">
                <img src="<?php echo esc_url($profile_pic); ?>" alt="Profile Picture">
                <div class="bbdc-profile-header-info">
                    <h2>Hello, <?php echo esc_html($current_user->display_name); ?></h2>
                    <p>
                        <?php if ($blood_group) echo 'Blood Group: ' . esc_html($blood_group); ?>
                        <?php if ($blood_group && $session) echo ' | '; ?>
                        <?php if ($session) echo 'Session: ' . esc_html($session); ?>
                    </p>
                </div>
            </div>
        </div>
        <?php foreach ($menus_to_render as $title => $items): if (!empty($items)): ?>
            <div class="bbdc-menu-card">
                <h3><?php echo esc_html($title); ?></h3>
                <div class="bbdc-menu-grid">
                    <?php foreach($items as $item): ?>
                        <div class="bbdc-menu-item">
                            <a href="<?php echo esc_url($item['url']); ?>">
                                <div class="menu-icon"><i class="fas <?php echo esc_attr($item['icon']); ?>"></i></div>
                                <div class="menu-title"><?php echo esc_html($item['title']); ?></div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('bbdc_volunteer_dashboard', 'bbdc_render_volunteer_dashboard_shortcode');