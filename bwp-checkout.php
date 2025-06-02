<?php
/**
 * BWP Checkout Functionality
 * 
 * Implements custom checkout functionality for Booking WP
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Display customer information in read-only format
 */
function bwp_display_customer_info_shortcode() {
    ob_start();

    // Get current user data
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    
    // Get saved billing data
    $billing_first_name = get_user_meta($user_id, 'billing_first_name', true);
    $billing_last_name = get_user_meta($user_id, 'billing_last_name', true);
    $billing_thai_id = get_user_meta($user_id, 'billing_thai_id', true);
    $billing_email = get_user_meta($user_id, 'billing_email', true);
    $billing_phone = get_user_meta($user_id, 'billing_phone', true);
    ?>
    <div class="bwp-customer-info-display">
        <div class="section-header">
            <div class="header-content">
                <h2>Your Information</h2>
                <a href="<?php echo esc_url(wc_get_page_permalink('cart')); ?>" class="edit-link">Edit</a>
            </div>
        </div>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Name</span>
                <span class="info-value"><?php echo esc_html($billing_first_name . ' ' . $billing_last_name); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Thai ID/Passport</span>
                <span class="info-value"><?php echo esc_html($billing_thai_id); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Email</span>
                <span class="info-value"><?php echo esc_html($billing_email); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Phone</span>
                <span class="info-value"><?php echo esc_html($billing_phone); ?></span>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('msc_display_customer_info', 'bwp_display_customer_info_shortcode');

/**
 * Display WooCommerce payment methods
 */
function bwp_payment_methods_shortcode() {
    ob_start();
    ?>
    <div class="bwp-payment-methods">
        <div class="section-header">
            <h2>Your Payment</h2>
        </div>
        <?php 
        if (WC()->cart && !WC()->cart->is_empty()) {
            // Get customer data from session
            $customer_data = WC()->session->get('bwp_customer_data');
            
            // Get list of available payment gateways
            $available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
            
            if (!empty($available_gateways)) {
                ?>
                <form id="payment-form" class="checkout woocommerce-checkout">
                    <?php wp_nonce_field('woocommerce-process_checkout', 'woocommerce-process-checkout-nonce'); ?>
                    
                    <?php if ($customer_data) : ?>
                        <!-- Hidden fields for customer data -->
                        <input type="hidden" name="billing_first_name" value="<?php echo esc_attr($customer_data['first_name']); ?>">
                        <input type="hidden" name="billing_last_name" value="<?php echo esc_attr($customer_data['last_name']); ?>">
                        <input type="hidden" name="billing_email" value="<?php echo esc_attr($customer_data['email']); ?>">
                        <input type="hidden" name="billing_phone" value="<?php echo esc_attr($customer_data['phone']); ?>">
                        <input type="hidden" name="billing_thai_id" value="<?php echo esc_attr($customer_data['thai_id']); ?>">
                        <input type="hidden" name="billing_hotel_name" value="<?php echo esc_attr($customer_data['hotel_name']); ?>">
                        <input type="hidden" name="billing_room" value="<?php echo esc_attr($customer_data['room']); ?>">
                        <input type="hidden" name="billing_special_requests" value="<?php echo esc_attr($customer_data['special_requests']); ?>">
                        
                        <!-- Default values for required WooCommerce fields -->
                        <input type="hidden" name="billing_country" value="TH">
                        <input type="hidden" name="billing_address_1" value="-">
                        <input type="hidden" name="billing_city" value="-">
                        <input type="hidden" name="billing_state" value="Bangkok">
                        <input type="hidden" name="billing_postcode" value="10110">
                    <?php endif; ?>
                    
                    <div id="payment" class="woocommerce-checkout-payment">
                        <ul class="wc_payment_methods payment_methods methods">
                            <?php
                            foreach ($available_gateways as $gateway) {
                                ?>
                                <li class="wc_payment_method payment_method_<?php echo esc_attr($gateway->id); ?>">
                                    <input id="payment_method_<?php echo esc_attr($gateway->id); ?>"
                                           type="radio"
                                           class="input-radio"
                                           name="payment_method"
                                           value="<?php echo esc_attr($gateway->id); ?>"
                                           <?php checked($gateway->chosen, true); ?> />
                                    <label for="payment_method_<?php echo esc_attr($gateway->id); ?>">
                                        <?php echo $gateway->get_title(); ?>
                                    </label>
                                    <?php if ($gateway->has_fields() || $gateway->get_description()) : ?>
                                        <div class="payment_box payment_method_<?php echo esc_attr($gateway->id); ?>">
                                            <?php $gateway->payment_fields(); ?>
                                        </div>
                                    <?php endif; ?>
                                </li>
                                <?php
                            }
                            ?>
                        </ul>
                    </div>
                </form>
                <?php
            }
        }
        ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('msc_payment_methods', 'bwp_payment_methods_shortcode');

/**
 * Enqueue styles for checkout
 */
function bwp_checkout_enqueue_scripts() {
    // Enqueue WooCommerce styles and scripts
    wp_enqueue_style('woocommerce-general');
    wp_enqueue_style('woocommerce-layout');
    wp_enqueue_script('wc-checkout');
    wp_enqueue_script('wc-cart-fragments');
    wp_enqueue_script('wc-add-to-cart');
    wp_enqueue_script('jquery-blockui');
    
    // Enqueue our custom styles
    wp_enqueue_style('bwp-checkout-styles', plugins_url('css/bwp-checkout.css', __FILE__));
    
    // Localize script for AJAX
    wp_localize_script('wc-checkout', 'wc_checkout_params', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'wc_ajax_url' => WC_AJAX::get_endpoint('%%endpoint%%'),
        'update_order_review_nonce' => wp_create_nonce('update-order-review'),
        'apply_coupon_nonce' => wp_create_nonce('apply-coupon'),
        'remove_coupon_nonce' => wp_create_nonce('remove-coupon'),
        'option_guest_checkout' => get_option('woocommerce_enable_guest_checkout'),
        'checkout_url' => WC_AJAX::get_endpoint('checkout'),
        'is_checkout' => is_checkout() ? 1 : 0,
        'debug_mode' => defined('WP_DEBUG') && WP_DEBUG,
        'i18n_checkout_error' => esc_attr__('Error processing checkout. Please try again.', 'woocommerce'),
    ));
}
add_action('wp_enqueue_scripts', 'bwp_checkout_enqueue_scripts');
