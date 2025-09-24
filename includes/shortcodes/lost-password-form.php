<?php
if (!defined('ABSPATH')) exit;

/**
 * Renders the lost password form via a shortcode.
 */
function bbdc_render_lost_password_form_shortcode() {
    if (is_admin()) {
        return '<div style="border: 1px solid #ccc; padding: 10px; text-align: center; background: #f1f1f1;">
                    <strong>BBDC Lost Password Form</strong>
                    <p><small>This form is only visible to logged-out users on the website.</small></p>
                </div>';
    }

    if (is_user_logged_in()) {
        wp_redirect(home_url('/volunteer-dashboard/'));
        exit;
    }

    ob_start();
    
    if (isset($_GET['status'])) {
        if ($_GET['status'] === 'success') {
            echo '<p class="bbdc-success">If a matching account was found, a password reset link has been sent to your email address.</p>';
        } else {
            $error_message = 'An error occurred. Please try again.';
            if ($_GET['status'] === 'nonce_error') {
                 $error_message = 'Security check failed. Please refresh the page and try again.';
            }
            echo '<p class="bbdc-error">' . $error_message . '</p>';
        }
    }
    ?>
    <div class="bbdc-form bbdc-auth-wrapper">
        <h3>Reset Password</h3>
        <p>Please enter your username or email address. You will receive a link to create a new password via email.</p>
        <form id="bbdc-lost-password-form" method="post" action="">
            <?php wp_nonce_field('bbdc_lost_password_nonce', 'bbdc_nonce'); ?>
            <p>
                <label for="user_login">Username or Email Address</label>
                <input type="text" name="user_login" id="user_login" class="input" required>
            </p>
            <p>
                <input type="submit" name="submit_lost_password" class="button" value="Get New Password">
            </p>
        </form>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('bbdc_lost_password_form', 'bbdc_render_lost_password_form_shortcode');

/**
 * Handles the submission of the lost password form.
 * Hooked to 'template_redirect' to ensure it only runs on the frontend.
 */
function bbdc_handle_lost_password_submission() {
    if (isset($_POST['submit_lost_password'])) {
        
        if (!isset($_POST['bbdc_nonce']) || !wp_verify_nonce($_POST['bbdc_nonce'], 'bbdc_lost_password_nonce')) {
             $current_url = strtok($_SERVER['REQUEST_URI'], '?');
             wp_redirect(add_query_arg('status', 'nonce_error', $current_url));
             exit;
        }
        
        $user_login = sanitize_text_field($_POST['user_login']);
        $result = retrieve_password($user_login);
        
        $status = is_wp_error($result) ? 'error' : 'success';
        
        $current_url = strtok($_SERVER['REQUEST_URI'], '?');
        wp_redirect(add_query_arg('status', $status, $current_url));
        exit;
    }
}
add_action('template_redirect', 'bbdc_handle_lost_password_submission');