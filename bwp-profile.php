<?php
/**
 * BWP Profile functionality
 *
 * @package BWP
 */

if (!defined('ABSPATH')) {
    exit;
}

// Ensure WooCommerce is active
if (!class_exists('WooCommerce')) {
    return;
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
                        <img src="<?php echo esc_url($profile_image); ?>" style="max-width: 150px; height: auto;">
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
 * Handle AJAX profile update
 */
function bwp_handle_profile_update() {
    check_ajax_referer('bwp_update_profile', 'nonce');
    
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('User not logged in');
    }
    
    $customer = new WC_Customer($user_id);
    
    // Update billing details
    $customer->set_billing_first_name(sanitize_text_field($_POST['first_name']));
    $customer->set_billing_last_name(sanitize_text_field($_POST['last_name']));
    $customer->set_billing_email(sanitize_email($_POST['email']));
    $customer->set_billing_phone(sanitize_text_field($_POST['phone']));
    $customer->update_meta_data('billing_thai_id', sanitize_text_field($_POST['thai_id']));
    
    $customer->save();
    
    wp_send_json_success();
}
add_action('wp_ajax_bwp_update_profile', 'bwp_handle_profile_update');

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
    $default_image = '/wp-content/uploads/2025/06/sbcf-default-avatar.webp';
    
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
        <div class="profile-title">
                    <h2>Your Information</h2>
                    <?php if (is_account_page()): ?>
                    <button type="button" class="edit-profile btn btn--primary btn--quaternary btn--s" id="edit-profile-btn">
                        <i class="fas fa-pencil-alt"></i>
                        <span>Edit</span>
                    </button>
                    <?php endif; ?>
                </div>
        <div class="profile-forms">
        <form class="info-grid" id="profile-form" style="display: none;">
                    <?php wp_nonce_field('bwp_update_profile', 'bwp_profile_nonce'); ?>
                    <div class="info-item">
                        <label class="info-label" for="first_name">
<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
<g clip-path="url(#clip0_2039_36846)">
<path d="M13.9993 14.0003C16.5777 14.0003 18.666 11.912 18.666 9.33366C18.666 6.75533 16.5777 4.66699 13.9993 4.66699C11.421 4.66699 9.33268 6.75533 9.33268 9.33366C9.33268 11.912 11.421 14.0003 13.9993 14.0003ZM13.9993 16.3337C10.8843 16.3337 4.66602 17.897 4.66602 21.0003V22.167C4.66602 22.8087 5.19102 23.3337 5.83268 23.3337H22.166C22.8077 23.3337 23.3327 22.8087 23.3327 22.167V21.0003C23.3327 17.897 17.1143 16.3337 13.9993 16.3337Z" fill="currentColor"/>
</g>
<defs>
<clipPath id="clip0_2039_36846">
<rect width="28" height="28" fill="white"/>
</clipPath>
</defs>
</svg>
First Name *</label>
                        <input type="text" id="first_name" name="first_name" 
                               value="<?php echo esc_attr($first_name); ?>" 
                               pattern="[A-Za-z ]{2,}" 
                               title="Please enter at least 2 letters. Numbers and special characters are not allowed."
                               placeholder="Enter your first name"
                               class="info-input"
                               required>
                    </div>
                    <div class="info-item">
                        <label class="info-label" for="last_name">
                        <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M13.9993 14.0003C16.5777 14.0003 18.666 11.912 18.666 9.33366C18.666 6.75533 16.5777 4.66699 13.9993 4.66699C11.421 4.66699 9.33268 6.75533 9.33268 9.33366C9.33268 11.912 11.421 14.0003 13.9993 14.0003ZM13.9993 16.3337C10.8843 16.3337 4.66602 17.897 4.66602 21.0003V22.167C4.66602 22.8087 5.19102 23.3337 5.83268 23.3337H22.166C22.8077 23.3337 23.3327 22.8087 23.3327 22.167V21.0003C23.3327 17.897 17.1143 16.3337 13.9993 16.3337Z" fill="currentColor"/>
</svg>
Last Name *</label>
                        <input type="text" id="last_name" name="last_name" 
                               value="<?php echo esc_attr($last_name); ?>" 
                               pattern="[A-Za-z ]{2,}" 
                               title="Please enter at least 2 letters. Numbers and special characters are not allowed."
                               placeholder="Enter your last name"
                               class="info-input"
                               required>
                    </div>
                    <div class="info-item">
                        <label class="info-label" for="thai_id">
<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
<g clip-path="url(#clip0_2039_36853)">
<path d="M22.1667 3.5H5.83333C4.55 3.5 3.5 4.55 3.5 5.83333V22.1667C3.5 23.45 4.55 24.5 5.83333 24.5H22.1667C23.45 24.5 24.5 23.45 24.5 22.1667V5.83333C24.5 4.55 23.45 3.5 22.1667 3.5ZM14 7C16.2517 7 18.0833 8.83167 18.0833 11.0833C18.0833 13.335 16.2517 15.1667 14 15.1667C11.7483 15.1667 9.91667 13.335 9.91667 11.0833C9.91667 8.83167 11.7483 7 14 7ZM22.1667 22.1667H5.83333V21.8983C5.83333 21.175 6.16 20.4983 6.72 20.055C8.715 18.4567 11.2467 17.5 14 17.5C16.7533 17.5 19.285 18.4567 21.28 20.055C21.84 20.4983 22.1667 21.1867 22.1667 21.8983V22.1667Z" fill="currentColor"/>
</g>
<defs>
<clipPath id="clip0_2039_36853">
<rect width="28" height="28" fill="white"/>
</clipPath>
</defs>
</svg>
Thai ID/Passport *</label>
                        <input type="text" id="thai_id" name="thai_id" 
                               value="<?php echo esc_attr($thai_id); ?>" 
                               pattern="[0-9A-Za-z]{8,}" 
                               title="Please enter a valid Thai ID (13 digits) or Passport number (at least 8 characters)"
                               placeholder="Enter Thai ID or Passport number"
                               class="info-input"
                               required>
                    </div>
                    <div class="info-item">
                        <label class="info-label" for="email">
<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
<g clip-path="url(#clip0_2039_36864)">
<path d="M23.334 4.66699H4.66732C3.38398 4.66699 2.34565 5.71699 2.34565 7.00033L2.33398 21.0003C2.33398 22.2837 3.38398 23.3337 4.66732 23.3337H23.334C24.6173 23.3337 25.6673 22.2837 25.6673 21.0003V7.00033C25.6673 5.71699 24.6173 4.66699 23.334 4.66699ZM22.8673 9.62533L14.619 14.782C14.2457 15.0153 13.7557 15.0153 13.3823 14.782L5.13398 9.62533C4.84232 9.43866 4.66732 9.12366 4.66732 8.78533C4.66732 8.00366 5.51898 7.53699 6.18398 7.94533L14.0007 12.8337L21.8173 7.94533C22.4823 7.53699 23.334 8.00366 23.334 8.78533C23.334 9.12366 23.159 9.43866 22.8673 9.62533Z" fill="currentColor"/>
</g>
<defs>
<clipPath id="clip0_2039_36864">
<rect width="28" height="28" fill="white"/>
</clipPath>
</defs>
</svg>
Email *</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo esc_attr($email); ?>" 
                               pattern="[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}" 
                               title="Please enter a valid email address"
                               placeholder="Enter your email address"
                               class="info-input"
                               required>
                    </div>
                    <div class="info-item">
                        <label class="info-label" for="phone">
<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
<g clip-path="url(#clip0_2039_36872)">
<path d="M22.4355 17.8034L19.4722 17.4651C18.7605 17.3834 18.0605 17.6284 17.5589 18.1301L15.4122 20.2767C12.1105 18.5967 9.40387 15.9017 7.72387 12.5884L9.8822 10.4301C10.3839 9.92839 10.6289 9.22839 10.5472 8.51672L10.2089 5.57672C10.0689 4.39839 9.0772 3.51172 7.8872 3.51172H5.86887C4.55054 3.51172 3.45387 4.60839 3.53554 5.92672C4.15387 15.8901 12.1222 23.8467 22.0739 24.4651C23.3922 24.5467 24.4889 23.4501 24.4889 22.1317V20.1134C24.5005 18.9351 23.6139 17.9434 22.4355 17.8034Z" fill="currentColor"/>
</g>
<defs>
<clipPath id="clip0_2039_36872">
<rect width="28" height="28" fill="white"/>
</clipPath>
</defs>
</svg>
Phone *</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo esc_attr($phone); ?>" 
                               pattern="[0-9+]{9,}" 
                               title="Please enter a valid phone number (at least 9 digits, can include + for country code)"
                               placeholder="Enter your phone number"
                               class="info-input"
                               required>
                    </div>
                    <div class="form-buttons">
                        <button type="submit" class="save-profile-btn btn btn--primary btn--s">
                            <i class="fas fa-check"></i> Save Changes
                        </button>
                        <button type="button" class="cancel-edit-btn btn btn--primary btn--s btn--quaternary">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
                <div class="info-grid" id="profile-display">
                    <div class="info-item">
                        <span class="info-label"><svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
<g clip-path="url(#clip0_2039_36846)">
<path d="M13.9993 14.0003C16.5777 14.0003 18.666 11.912 18.666 9.33366C18.666 6.75533 16.5777 4.66699 13.9993 4.66699C11.421 4.66699 9.33268 6.75533 9.33268 9.33366C9.33268 11.912 11.421 14.0003 13.9993 14.0003ZM13.9993 16.3337C10.8843 16.3337 4.66602 17.897 4.66602 21.0003V22.167C4.66602 22.8087 5.19102 23.3337 5.83268 23.3337H22.166C22.8077 23.3337 23.3327 22.8087 23.3327 22.167V21.0003C23.3327 17.897 17.1143 16.3337 13.9993 16.3337Z" fill="currentColor"/>
</g>
<defs>
<clipPath id="clip0_2039_36846">
<rect width="28" height="28" fill="white"/>
</clipPath>
</defs>
</svg>Name</span>
                        <span class="info-value"><?php echo esc_html($first_name . ' ' . $last_name); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
<g clip-path="url(#clip0_2039_36853)">
<path d="M22.1667 3.5H5.83333C4.55 3.5 3.5 4.55 3.5 5.83333V22.1667C3.5 23.45 4.55 24.5 5.83333 24.5H22.1667C23.45 24.5 24.5 23.45 24.5 22.1667V5.83333C24.5 4.55 23.45 3.5 22.1667 3.5ZM14 7C16.2517 7 18.0833 8.83167 18.0833 11.0833C18.0833 13.335 16.2517 15.1667 14 15.1667C11.7483 15.1667 9.91667 13.335 9.91667 11.0833C9.91667 8.83167 11.7483 7 14 7ZM22.1667 22.1667H5.83333V21.8983C5.83333 21.175 6.16 20.4983 6.72 20.055C8.715 18.4567 11.2467 17.5 14 17.5C16.7533 17.5 19.285 18.4567 21.28 20.055C21.84 20.4983 22.1667 21.1867 22.1667 21.8983V22.1667Z" fill="currentColor"/>
</g>
<defs>
<clipPath id="clip0_2039_36853">
<rect width="28" height="28" fill="white"/>
</clipPath>
</defs>
</svg>Thai ID/Passport</span>
                        <span class="info-value"><?php echo esc_html($thai_id); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
<g clip-path="url(#clip0_2039_36864)">
<path d="M23.334 4.66699H4.66732C3.38398 4.66699 2.34565 5.71699 2.34565 7.00033L2.33398 21.0003C2.33398 22.2837 3.38398 23.3337 4.66732 23.3337H23.334C24.6173 23.3337 25.6673 22.2837 25.6673 21.0003V7.00033C25.6673 5.71699 24.6173 4.66699 23.334 4.66699ZM22.8673 9.62533L14.619 14.782C14.2457 15.0153 13.7557 15.0153 13.3823 14.782L5.13398 9.62533C4.84232 9.43866 4.66732 9.12366 4.66732 8.78533C4.66732 8.00366 5.51898 7.53699 6.18398 7.94533L14.0007 12.8337L21.8173 7.94533C22.4823 7.53699 23.334 8.00366 23.334 8.78533C23.334 9.12366 23.159 9.43866 22.8673 9.62533Z" fill="currentColor"/>
</g>
<defs>
<clipPath id="clip0_2039_36864">
<rect width="28" height="28" fill="white"/>
</clipPath>
</defs>
</svg>Email</span>
                        <span class="info-value"><?php echo esc_html($email); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
<g clip-path="url(#clip0_2039_36872)">
<path d="M22.4355 17.8034L19.4722 17.4651C18.7605 17.3834 18.0605 17.6284 17.5589 18.1301L15.4122 20.2767C12.1105 18.5967 9.40387 15.9017 7.72387 12.5884L9.8822 10.4301C10.3839 9.92839 10.6289 9.22839 10.5472 8.51672L10.2089 5.57672C10.0689 4.39839 9.0772 3.51172 7.8872 3.51172H5.86887C4.55054 3.51172 3.45387 4.60839 3.53554 5.92672C4.15387 15.8901 12.1222 23.8467 22.0739 24.4651C23.3922 24.5467 24.4889 23.4501 24.4889 22.1317V20.1134C24.5005 18.9351 23.6139 17.9434 22.4355 17.8034Z" fill="currentColor"/>
</g>
<defs>
<clipPath id="clip0_2039_36872">
<rect width="28" height="28" fill="white"/>
</clipPath>
</defs>
</svg>Phone</span>
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
