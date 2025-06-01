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
            <h2>Your Information</h2>
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
            // Get list of available payment gateways
            $available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
            
            if (!empty($available_gateways)) {
                ?>
                <div id="payment" class="woocommerce-checkout-payment">
                    <ul class="wc_payment_methods payment_methods methods">
                        <?php
                        foreach ($available_gateways as $gateway) {
                            wc_get_template('checkout/payment-method.php', array(
                                'gateway' => $gateway
                            ));
                        }
                        ?>
                    </ul>
                </div>
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
function bwp_checkout_enqueue_styles() {
    wp_enqueue_style('bwp-checkout-styles', plugins_url('css/bwp-checkout.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'bwp_checkout_enqueue_styles');
