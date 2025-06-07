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
    <div class="bwp-customer-info-display card--cart">
        <div class="section-header">
            <div class="header-content">
                <h2>Your Information</h2>
                <a href="<?php echo esc_url(wc_get_page_permalink('cart')); ?>" class="edit-link">Edit</a>
            </div>
        </div>
        <div class="info-grid">
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
                <span class="info-value"><?php echo esc_html($billing_first_name . ' ' . $billing_last_name); ?></span>
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
                <span class="info-value"><?php echo esc_html($billing_thai_id); ?></span>
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
                <span class="info-value"><?php echo esc_html($billing_email); ?></span>
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
            // $fields['billing']['billing_country']['default'] = 'TH';
            // $fields['billing']['billing_address_1']['default'] = '-';
            // $fields['billing']['billing_city']['default'] = '-';
            // $fields['billing']['billing_state']['default'] = 'Bangkok';
            // $fields['billing']['billing_postcode']['default'] = '10110';
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
    
    // Remove WooCommerce coupon form but keep coupon functionality
    remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10);
    add_filter('woocommerce_coupons_enabled', '__return_true');
    
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
    if (WC()->session) {
        $customer_data = WC()->session->get('bwp_customer_data');
        
        if ($customer_data) {
            // Save standard billing fields
            $order->set_billing_first_name($customer_data['first_name']);
            $order->set_billing_last_name($customer_data['last_name']);
            $order->set_billing_email($customer_data['email']);
            $order->set_billing_phone($customer_data['phone']);
            
            // Save custom fields
            if (isset($customer_data['thai_id'])) {
                $order->update_meta_data('_billing_thai_id', $customer_data['thai_id']);
            }
            if (isset($customer_data['hotel_name'])) {
                $order->update_meta_data('_billing_hotel_name', $customer_data['hotel_name']);
            }
            if (isset($customer_data['room'])) {
                $order->update_meta_data('_billing_room', $customer_data['room']);
            }
            if (!empty($customer_data['special_requests'])) {
                $order->update_meta_data('_billing_special_requests', $customer_data['special_requests']);
            }
            
            // Save default billing fields
            // $order->set_billing_country('TH');
            // $order->set_billing_address_1('-');
            // $order->set_billing_city('-');
            // $order->set_billing_state('Bangkok');
            // $order->set_billing_postcode('10110');
        }
    }
    
    // Update order items with correct pricing
    foreach ($order->get_items() as $item) {
        $product = $item->get_product();
        if (!$product) continue;
        
        $product_id = $product->get_id();
        
        // Get cart item data
        $cart = WC()->cart;
        if (!$cart) continue;
        
        foreach ($cart->get_cart() as $cart_item) {
            if ($cart_item['product_id'] == $product_id) {
                // Get quantities
                $adults = isset($cart_item['bwp_adults']) ? intval($cart_item['bwp_adults']) : 1;
                $children = isset($cart_item['bwp_children']) ? intval($cart_item['bwp_children']) : 0;
                
                // Get base price
                $base_price = $product->get_price();
                
                // Calculate additional costs
                $total_price = $base_price;
                
                // Add adult costs
                if ($adults >= 2) {
                    $adult_tiers = get_field('adult_price_tiers', $product_id);
                    if ($adult_tiers) {
                        foreach ($adult_tiers as $tier) {
                            if (isset($tier['number_of_adults']) && intval($tier['number_of_adults']) == $adults) {
                                if (isset($tier['additional_price']) && is_numeric($tier['additional_price'])) {
                                    $total_price += floatval($tier['additional_price']);
                                    break;
                                }
                            }
                        }
                    }
                }
                
                // Add child costs
                if ($children >= 1) {
                    $child_tiers = get_field('child_price_tiers', $product_id);
                    if ($child_tiers) {
                        foreach ($child_tiers as $tier) {
                            if (isset($tier['number_of_children']) && intval($tier['number_of_children']) == $children) {
                                if (isset($tier['additional_price']) && is_numeric($tier['additional_price'])) {
                                    $total_price += floatval($tier['additional_price']);
                                    break;
                                }
                            }
                        }
                    }
                }
                
                // Add departure location costs if any
                if (isset($cart_item['bwp_departure_location'])) {
                    $departure_group = get_field('departure', $product_id);
                    if ($departure_group) {
                        $location = $cart_item['bwp_departure_location'];
                        $price_key = $location . '_additional_price';
                        if (isset($departure_group[$price_key]) && is_numeric($departure_group[$price_key])) {
                            $total_price += floatval($departure_group[$price_key]);
                        }
                    }
                }
                
                // Get any cart item discounts
                $cart_discount = 0;
                if (isset($cart_item['line_subtotal']) && isset($cart_item['line_total'])) {
                    $cart_discount = $cart_item['line_subtotal'] - $cart_item['line_total'];
                }
                
                // Update item totals
                $item->set_subtotal($total_price); // Original price before discount
                if ($cart_discount > 0) {
                    // Apply the same discount percentage to our calculated total
                    $discount_percentage = $cart_discount / $cart_item['line_subtotal'];
                    $discount_amount = $total_price * $discount_percentage;
                    $item->set_total($total_price - $discount_amount); // Price after discount
                } else {
                    $item->set_total($total_price);
                }
                
                // Save guest counts as item meta - using non-hidden keys for display
                $item->add_meta_data('Adults', $adults, true);
                $item->add_meta_data('Children', $children, true);
                if (isset($cart_item['bwp_departure_location'])) {
                    $item->add_meta_data('Departure From', ucfirst($cart_item['bwp_departure_location']), true);
                }
                
                // Save booking date
                if (isset($cart_item['bwp_booking_date'])) {
                    $item->add_meta_data('Booking Date', $cart_item['bwp_booking_date'], true);
                }
                
                break; // Found matching item, no need to continue loop
            }
        }
    }
    
    // Recalculate order totals
    $order->calculate_totals();
    
    return $order;
}
add_filter('woocommerce_checkout_create_order', 'bwp_save_billing_details_to_order');
