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
 * Check if required customer data exists before checkout
 */
function bwp_check_required_customer_data() {
    if (!WC()->session) return false;
    
    $customer_data = WC()->session->get('bwp_customer_data');
    if (!$customer_data) return false;
    
    $required_fields = array('first_name', 'last_name', 'email', 'phone', 'thai_id');
    
    foreach ($required_fields as $field) {
        if (empty($customer_data[$field])) {
            return false;
        }
    }
    
    return true;
}

/**
 * Redirect to cart if required data is missing
 */
function bwp_check_checkout_requirements() {
    if (!is_checkout()) return;
    
    // Skip check for order-received page
    if (isset($_GET['key']) && strpos($_SERVER['REQUEST_URI'], 'order-received') !== false) {
        return;
    }
    
    if (!bwp_check_required_customer_data()) {
        wp_redirect(wc_get_cart_url());
        exit;
    }
}
add_action('template_redirect', 'bwp_check_checkout_requirements');

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

/**
 * Remove everything except payment section and use custom price calculation
 */
function bwp_show_only_payment() {
    // Remove customer information display
    remove_action('woocommerce_checkout_before_customer_details', 'bwp_display_customer_info_shortcode');
    
    // Remove coupon form
    remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10);
    
    // Remove customer details div
    add_filter('woocommerce_checkout_fields', function($fields) {
        return [];
    }, 99);
    
    // Remove order review
    remove_action('woocommerce_checkout_order_review', 'woocommerce_order_review', 10);
    
    // Remove additional information
    remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10);
    remove_action('woocommerce_checkout_before_order_review_heading', 'woocommerce_checkout_payment', 20);
    
    // Keep only payment section
    add_action('woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20);
    
    // Use custom price calculation from cart
    add_filter('woocommerce_before_calculate_totals', 'bwp_use_custom_price_calculation', 99);
    
    // Remove notices and headings
    add_action('template_redirect', function() {
        if (is_checkout()) {
            // Only clear notices if not on order-received page
            if (strpos($_SERVER['REQUEST_URI'], 'order-received') === false) {
                WC()->session->set('wc_notices', null);
            }
            
            // Remove order review heading
            add_filter('woocommerce_order_review_heading', '__return_empty_string');
            
            // Remove terms and conditions
            // remove_action('woocommerce_checkout_terms_and_conditions', 'wc_checkout_privacy_policy_text', 20);
            // remove_action('woocommerce_checkout_terms_and_conditions', 'wc_terms_and_conditions_page_content', 30);
        }
    });
    
    // Save customer data to order meta
    add_action('woocommerce_checkout_create_order', function($order) {
        if (WC()->session && ($customer_data = WC()->session->get('bwp_customer_data'))) {
            foreach ($customer_data as $key => $value) {
                $order->update_meta_data('_bwp_' . $key, $value);
            }
        }
    });
    
    // Prevent notices from showing
    add_filter('woocommerce_notice_types', function($notice_types) {
        if (is_checkout()) {
            return [];
        }
        return $notice_types;
    });
}
add_action('init', 'bwp_show_only_payment');

/**
 * Use custom price calculation from cart
 */
function bwp_use_custom_price_calculation($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    // Prevent recalculation if already done
    static $has_run = false;
    if ($has_run) {
        return;
    }
    $has_run = true;
    
    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        if (isset($cart_item['base_price'])) {
            // Use the original base price to prevent accumulation
            $cart_item['data']->set_price($cart_item['base_price']);
        }
    }
}

/**
 * Save billing details from session to order
 */
function bwp_save_billing_details_to_order($order) {
    // Get customer data from session
    $customer_data = WC()->session->get('bwp_customer_data');
    
    if ($customer_data) {
        // Map session data to order billing fields
        $billing_fields = array(
            'first_name' => isset($customer_data['first_name']) ? $customer_data['first_name'] : '',
            'last_name' => isset($customer_data['last_name']) ? $customer_data['last_name'] : '',
            'email' => isset($customer_data['email']) ? $customer_data['email'] : '',
            'phone' => isset($customer_data['phone']) ? $customer_data['phone'] : '',
        );
        
        // Set billing data
        foreach ($billing_fields as $key => $value) {
            $method = 'set_billing_' . $key;
            $order->$method($value);
        }
        
        // Set custom fields
        if (isset($customer_data['thai_id'])) {
            $order->update_meta_data('_billing_thai_id', $customer_data['thai_id']);
        }
        
        // Set hotel information
        if (isset($customer_data['hotel_name'])) {
            $order->update_meta_data('_billing_hotel_name', $customer_data['hotel_name']);
        }
        if (isset($customer_data['room'])) {
            $order->update_meta_data('_billing_room', $customer_data['room']);
        }
        if (isset($customer_data['special_requests'])) {
            $order->update_meta_data('_billing_special_requests', $customer_data['special_requests']);
        }
        
        // Save the order
        $order->save();
    }
    
    return $order;
}
add_filter('woocommerce_checkout_create_order', 'bwp_save_billing_details_to_order');
