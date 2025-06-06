<?php
/**
 * BWP Profile Functionality
 * 
 * Implements customer profile display functionality for Booking WP
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Add profile image field to user profile
 */
function bwp_add_profile_image_field($user) {
    $profile_image = get_user_meta($user->ID, 'bwp_profile_image', true);
    ?>
    <h3>Profile Image</h3>
    <table class="form-table">
        <tr>
            <th>
                <label for="bwp_profile_image">Profile Picture</label>
            </th>
            <td>
                <div class="bwp-profile-image-preview" style="margin-bottom: 10px;">
                    <?php if ($profile_image): ?>
                        <img src="<?php echo esc_url($profile_image); ?>" style="max-width: 150px; height: auto; border-radius: 50%;">
                    <?php endif; ?>
                </div>
                <input type="text" 
                       name="bwp_profile_image" 
                       id="bwp_profile_image" 
                       value="<?php echo esc_attr($profile_image); ?>" 
                       class="regular-text">
                <input type="button" 
                       class="button-secondary" 
                       value="Choose Image" 
                       id="bwp_upload_image_button">
                <?php if ($profile_image): ?>
                    <input type="button" 
                           class="button-secondary" 
                           value="Remove Image" 
                           id="bwp_remove_image_button">
                <?php endif; ?>
                <p class="description">Upload or choose your profile picture.</p>
            </td>
        </tr>
    </table>
    <script>
    jQuery(document).ready(function($) {
        var mediaUploader;
        
        $('#bwp_upload_image_button').on('click', function(e) {
            e.preventDefault();
            
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            
            mediaUploader = wp.media({
                title: 'Choose Profile Picture',
                button: {
                    text: 'Use this image'
                },
                multiple: false
            });
            
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#bwp_profile_image').val(attachment.url);
                $('.bwp-profile-image-preview').html('<img src="' + attachment.url + '" style="max-width: 150px; height: auto; border-radius: 50%;">');
                $('#bwp_remove_image_button').show();
            });
            
            mediaUploader.open();
        });
        
        $('#bwp_remove_image_button').on('click', function() {
            $('#bwp_profile_image').val('');
            $('.bwp-profile-image-preview').empty();
            $(this).hide();
        });
    });
    </script>
    <?php
}
add_action('show_user_profile', 'bwp_add_profile_image_field');
add_action('edit_user_profile', 'bwp_add_profile_image_field');

/**
 * Save profile image field
 */
function bwp_save_profile_image_field($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }
    
    if (isset($_POST['bwp_profile_image'])) {
        update_user_meta($user_id, 'bwp_profile_image', sanitize_text_field($_POST['bwp_profile_image']));
    }
}
add_action('personal_options_update', 'bwp_save_profile_image_field');
add_action('edit_user_profile_update', 'bwp_save_profile_image_field');

/**
 * Display customer profile information with avatar
 */
function bwp_customer_profile_shortcode() {
    ob_start();
    
    // Get current user
    $user_id = get_current_user_id();
    if (!$user_id) return '';
    
    // Get user data
    $user = get_userdata($user_id);
    $customer = new WC_Customer($user_id);
    
    // Get profile image
    $profile_image = get_user_meta($user_id, 'bwp_profile_image', true);
    $default_image = plugins_url('assets/images/default-avatar.png', __FILE__);
    
    // Get billing info
    $first_name = $customer->get_billing_first_name();
    $last_name = $customer->get_billing_last_name();
    $email = $customer->get_billing_email();
    $phone = $customer->get_billing_phone();
    $thai_id = $customer->get_meta('billing_thai_id');
    
    ?>
    <div class="bwp-customer-profile">
        <div class="profile-image-wrapper">
                <div class="profile-image">
                    <img src="<?php echo esc_url($profile_image ? $profile_image : $default_image); ?>" alt="Profile Image">
                    <?php if (is_account_page()): ?>
                    <button type="button" class="change-image-btn">
                        <i class="fas fa-camera"></i>
                        <span class="screen-reader-text">Change Profile Picture</span>
                    </button>
                    <input type="file" id="profile_image_upload" name="profile_image" accept="image/*" style="display: none;">
                    <?php endif; ?>
                </div>
            </div>
         
        <div class="profile-info">
        <div class="profile-header">
            <div class="profile-title">
                <h2>Your Information</h2>
                <?php if (is_account_page()): ?>
                <a href="<?php echo esc_url(wc_get_endpoint_url('edit-account', '', wc_get_page_permalink('myaccount'))); ?>" class="edit-profile">
                    <i class="fas fa-pencil-alt"></i>
                    <span>Edit Profile</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Name</span>
                    <span class="info-value"><?php echo esc_html($first_name . ' ' . $last_name); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Thai ID/Passport</span>
                    <span class="info-value"><?php echo esc_html($thai_id); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Email</span>
                    <span class="info-value"><?php echo esc_html($email); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Phone</span>
                    <span class="info-value"><?php echo esc_html($phone); ?></span>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('bwp_customer_profile', 'bwp_customer_profile_shortcode');

/**
 * Handle profile image upload via AJAX
 */
function bwp_handle_profile_image_upload() {
    check_ajax_referer('bwp_profile_image_nonce', 'nonce');
    
    if (!isset($_FILES['profile_image'])) {
        wp_send_json_error('No file uploaded');
    }
    
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('User not logged in');
    }
    
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    
    $attachment_id = media_handle_upload('profile_image', 0);
    
    if (is_wp_error($attachment_id)) {
        wp_send_json_error($attachment_id->get_error_message());
    }
    
    $image_url = wp_get_attachment_url($attachment_id);
    update_user_meta($user_id, 'bwp_profile_image', $image_url);
    
    wp_send_json_success([
        'url' => $image_url
    ]);
}
add_action('wp_ajax_bwp_upload_profile_image', 'bwp_handle_profile_image_upload');

/**
 * Enqueue profile scripts and styles
 */
function bwp_enqueue_profile_assets() {
    if (is_account_page()) {
        wp_enqueue_style('bwp-profile-styles', plugins_url('css/bwp-profile.css', __FILE__));
        wp_enqueue_script('bwp-profile-script', plugins_url('js/bwp-profile.js', __FILE__), ['jquery'], null, true);
        wp_localize_script('bwp-profile-script', 'bwpProfile', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bwp_profile_image_nonce')
        ]);
    }
}
add_action('wp_enqueue_scripts', 'bwp_enqueue_profile_assets');
