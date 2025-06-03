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
    
    // Try to get data from session first
    $customer_data = WC()->session ? WC()->session->get('bwp_customer_data') : null;
    
    if ($customer_data) {
        $billing_first_name = $customer_data['first_name'];
        $billing_last_name = $customer_data['last_name'];
        $billing_email = $customer_data['email'];
        $billing_phone = $customer_data['phone'];
        $billing_thai_id = $customer_data['thai_id'];
    } else {
        // Fallback to customer object if session data not available
        $billing_first_name = WC()->customer ? WC()->customer->get_billing_first_name() : '';
        $billing_last_name = WC()->customer ? WC()->customer->get_billing_last_name() : '';
        $billing_email = WC()->customer ? WC()->customer->get_billing_email() : '';
        $billing_phone = WC()->customer ? WC()->customer->get_billing_phone() : '';
        $billing_thai_id = WC()->customer ? WC()->customer->get_meta('billing_thai_id') : '';
    }
    
    // Debug
    error_log('BWP Debug - Customer Info Display:');
    error_log('Session Data: ' . print_r($customer_data, true));
    error_log('First Name: ' . $billing_first_name);
    error_log('Last Name: ' . $billing_last_name);
    error_log('Thai ID: ' . $billing_thai_id);
    
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

/**
 * Prefill checkout fields from session data
 */
function bwp_prefill_checkout_fields($fields) {
    // Get customer data from session
    if (WC()->session) {
        $customer_data = WC()->session->get('bwp_customer_data');
        
        // Debug session data
        error_log('BWP Debug - Session Data:');
        error_log(print_r($customer_data, true));
        
        // Also try to get individual fields from session
        $thai_id = WC()->session->get('billing_thai_id');
        $hotel_name = WC()->session->get('billing_hotel_name');
        $room = WC()->session->get('billing_room');
        $special_requests = WC()->session->get('billing_special_requests');
        
        error_log('BWP Debug - Individual Fields:');
        error_log('Thai ID: ' . $thai_id);
        error_log('Hotel: ' . $hotel_name);
        error_log('Room: ' . $room);
        error_log('Special Requests: ' . $special_requests);
        
        if ($customer_data) {
            // Prefill standard billing fields
            if (!empty($customer_data['first_name'])) {
                $fields['billing']['billing_first_name']['default'] = $customer_data['first_name'];
            }
            if (!empty($customer_data['last_name'])) {
                $fields['billing']['billing_last_name']['default'] = $customer_data['last_name'];
            }
            if (!empty($customer_data['email'])) {
                $fields['billing']['billing_email']['default'] = $customer_data['email'];
            }
            if (!empty($customer_data['phone'])) {
                $fields['billing']['billing_phone']['default'] = $customer_data['phone'];
            }
            
            // Prefill custom fields
            if (!empty($customer_data['thai_id'])) {
                $fields['billing']['billing_thai_id']['default'] = $customer_data['thai_id'];
            }
            if (!empty($customer_data['hotel_name'])) {
                $fields['billing']['billing_hotel_name']['default'] = $customer_data['hotel_name'];
            }
            if (!empty($customer_data['room'])) {
                $fields['billing']['billing_room']['default'] = $customer_data['room'];
            }
            if (!empty($customer_data['special_requests'])) {
                $fields['billing']['billing_special_requests']['default'] = $customer_data['special_requests'];
            }
            
            // Set required default fields
            $fields['billing']['billing_country']['default'] = 'TH';
            $fields['billing']['billing_address_1']['default'] = '-';
            $fields['billing']['billing_city']['default'] = '-';
            $fields['billing']['billing_state']['default'] = 'Bangkok';
            $fields['billing']['billing_postcode']['default'] = '10110';
        }
    }
    
    return $fields;
}
add_filter('woocommerce_checkout_fields', 'bwp_prefill_checkout_fields');
