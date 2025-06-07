<?php
/**
 * BWP Thank You Page Functionality
 * 
 * Implements custom thank you page functionality for Booking WP
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Enqueue styles for thank you page
 */
function bwp_thankyou_enqueue_styles() {
    wp_enqueue_style('bwp-thankyou-styles', plugins_url('css/bwp-thankyou.css', __FILE__));
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');
}
add_action('wp_enqueue_scripts', 'bwp_thankyou_enqueue_styles');

/**
 * Display customer information in thank you page
 */
function bwp_thankyou_customer_info_shortcode() {
    ob_start();
    
    // Get the order
    $order_id = absint(get_query_var('order-received'));
    if (!$order_id) return;
    
    $order = wc_get_order($order_id);
    if (!$order) return;
    
    // Get customer info from order meta
    $billing_first_name = $order->get_meta('_bwp_first_name');
    $billing_last_name = $order->get_meta('_bwp_last_name');
    $billing_thai_id = $order->get_meta('_bwp_thai_id');
    $billing_email = $order->get_meta('_bwp_email');
    $billing_phone = $order->get_meta('_bwp_phone');
    
    ?>
    <div class="bwp-customer-info-display card--cart">
        <div class="section-header">
            <h2>Your Information</h2>
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
add_shortcode('bwp_thankyou_customer_info', 'bwp_thankyou_customer_info_shortcode');

/**
 * Display booking information in thank you page
 */
function bwp_thankyou_bookings_shortcode() {
    ob_start();
    
    // Get the order
    $order_id = absint(get_query_var('order-received'));
    if (!$order_id) return;
    
    $order = wc_get_order($order_id);
    if (!$order) return;
    
    // Get order details
    $order_number = $order->get_order_number();
    $order_date = $order->get_date_created()->format('M d, Y');
    $order_time = $order->get_date_created()->format('H:i A.');
    ?>
    <div class="bwp-your-bookings card--cart">
        <h2>Your Bookings</h2>
        
        <div class="booking-header">
            <div class="booking-info">
                <div class="info-group">
                    <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <g clip-path="url(#clip0_2020_13572)">
                    <path d="M29.3327 13.333V7.99967C29.3327 6.51967 28.1327 5.33301 26.666 5.33301H5.33268C3.86602 5.33301 2.67935 6.51967 2.67935 7.99967V13.333C4.14602 13.333 5.33268 14.533 5.33268 15.9997C5.33268 17.4663 4.14602 18.6663 2.66602 18.6663V23.9997C2.66602 25.4663 3.86602 26.6663 5.33268 26.6663H26.666C28.1327 26.6663 29.3327 25.4663 29.3327 23.9997V18.6663C27.866 18.6663 26.666 17.4663 26.666 15.9997C26.666 14.533 27.866 13.333 29.3327 13.333ZM17.3327 23.333H14.666V20.6663H17.3327V23.333ZM17.3327 17.333H14.666V14.6663H17.3327V17.333ZM17.3327 11.333H14.666V8.66634H17.3327V11.333Z" fill="var(--be-03)"/>
                    </g>
                    <defs>
                    <clipPath id="clip0_2020_13572">
                    <rect width="32" height="32" fill="white"/>
                    </clipPath>
                    </defs>
                    </svg>
                    <div class="info-text">
                        <span class="label">Booking Number</span>
                        <span class="value"><?php echo esc_html($order_number); ?> <i class="fas fa-copy"></i></span>
                    </div>
                </div>
                
                <div class="info-group">               
                <svg width="33" height="32" viewBox="0 0 33 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                <g clip-path="url(#clip0_2039_33682)">
                <path d="M25.8333 5.33366H24.5V2.66699H21.8333V5.33366H11.1667V2.66699H8.5V5.33366H7.16667C5.68667 5.33366 4.51333 6.53366 4.51333 8.00033L4.5 26.667C4.5 28.1337 5.68667 29.3337 7.16667 29.3337H25.8333C27.3 29.3337 28.5 28.1337 28.5 26.667V8.00033C28.5 6.53366 27.3 5.33366 25.8333 5.33366ZM25.8333 26.667H7.16667V13.3337H25.8333V26.667ZM12.5 18.667H9.83333V16.0003H12.5V18.667ZM17.8333 18.667H15.1667V16.0003H17.8333V18.667ZM23.1667 18.667H20.5V16.0003H23.1667V18.667ZM12.5 24.0003H9.83333V21.3337H12.5V24.0003ZM17.8333 24.0003H15.1667V21.3337H17.8333V24.0003ZM23.1667 24.0003H20.5V21.3337H23.1667V24.0003Z" fill="var(--be-03)"/>
                </g>
                <defs>
                <clipPath id="clip0_2039_33682">
                <rect width="32" height="32" fill="white" transform="translate(0.5)"/>
                </clipPath>
                </defs>
                </svg>
                    <div class="info-text">
                        <span class="label">Payment Confirmed</span>
                        <span class="value"><?php echo esc_html($order_date . ' ' . $order_time); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="booking-items">
        <?php
        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();
            if (!$product) continue;
            
            // Get booking meta data
            $booking_date = $item->get_meta('Booking Date');
            $adults = $item->get_meta('Adults');
            $children = $item->get_meta('Children');
            
            // Set defaults if empty
            $adults = !empty($adults) ? $adults : 0;
            $children = !empty($children) ? $children : 0;
            ?>
            <div class="booking-item">
                <div class="item-image">
                    <?php echo $product->get_image('thumbnail'); ?>
                </div>
                <div class="item-details">
                    <h4><?php echo esc_html($product->get_name()); ?></h4>
                    <div class="booking-meta">
                        <?php if ($booking_date): ?>
                            <div class="date">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <g clip-path="url(#clip0_2020_11699)">
                                    <path d="M19 4H18V2H16V4H8V2H6V4H5C3.89 4 3.01 4.9 3.01 6L3 20C3 21.1 3.89 22 5 22H19C20.1 22 21 21.1 21 20V6C21 4.9 20.1 4 19 4ZM19 20H5V10H19V20ZM9 14H7V12H9V14ZM13 14H11V12H13V14ZM17 14H15V12H17V14ZM9 18H7V16H9V18ZM13 18H11V16H13V18ZM17 18H15V16H17V18Z" fill="var(--be-03)"/>
                                    </g>
                                    <defs>
                                    <clipPath id="clip0_2020_11699">
                                    <rect width="24" height="24" fill="white"/>
                                    </clipPath>
                                    </defs>
                                </svg>
                                <?php echo esc_html($booking_date); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="guests">
                            <div class="guest-type adults">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <g clip-path="url(#clip0_2020_11706)">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M16.6699 13.1299C18.0399 14.0599 18.9999 15.3199 18.9999 16.9999V19.9999H22.9999V16.9999C22.9999 14.8199 19.4299 13.5299 16.6699 13.1299Z" fill="var(--be-03)"/>
                                    <path d="M9 12C11.2091 12 13 10.2091 13 8C13 5.79086 11.2091 4 9 4C6.79086 4 5 5.79086 5 8C5 10.2091 6.79086 12 9 12Z" fill="var(--be-03)"/>
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M14.9999 12C17.2099 12 18.9999 10.21 18.9999 8C18.9999 5.79 17.2099 4 14.9999 4C14.5299 4 14.0899 4.1 13.6699 4.24C14.4999 5.27 14.9999 6.58 14.9999 8C14.9999 9.42 14.4999 10.73 13.6699 11.76C14.0899 11.9 14.5299 12 14.9999 12Z" fill="var(--be-03)"/>
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M9 13C6.33 13 1 14.34 1 17V20H17V17C17 14.34 11.67 13 9 13Z" fill="var(--be-03)"/>
                                    </g>
                                    <defs>
                                    <clipPath id="clip0_2020_11706">
                                    <rect width="24" height="24" fill="white"/>
                                    </clipPath>
                                    </defs>
                                    </svg>
                                <span class="guest-count"><?php echo esc_html($adults); ?></span>
                            </div>
                            <?php if ($children && intval($children) > 0): ?>
                            <div class="guest-type children">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <g clip-path="url(#clip0_2020_11752)">
                                    <path d="M13 2V10H21C21 5.58 17.42 2 13 2ZM19.32 15.89C20.37 14.54 21 12.84 21 11H6.44L5.49 9H2V11H4.22C4.22 11 6.11 15.07 6.34 15.42C5.24 16.01 4.5 17.17 4.5 18.5C4.5 20.43 6.07 22 8 22C9.76 22 11.22 20.7 11.46 19H13.54C13.78 20.7 15.24 22 17 22C18.93 22 20.5 20.43 20.5 18.5C20.5 17.46 20.04 16.53 19.32 15.89ZM8 20C7.17 20 6.5 19.33 6.5 18.5C6.5 17.67 7.17 17 8 17C8.83 17 9.5 17.67 9.5 18.5C9.5 19.33 8.83 20 8 20ZM17 20C16.17 20 15.5 19.33 15.5 18.5C15.5 17.67 16.17 17 17 17C17.83 17 18.5 17.67 18.5 18.5C18.5 19.33 17.83 20 17 20Z" fill="var(--be-03)"/>
                                    </g>
                                    <defs>
                                    <clipPath id="clip0_2020_11752">
                                    <rect width="24" height="24" fill="white"/>
                                    </clipPath>
                                    </defs>
                                </svg>
                                <span class="guest-count"><?php echo esc_html($children); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="item-price">
                    <?php echo wc_price($item->get_total()); ?>
                </div>
            </div>
            <?php
        }
        ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('bwp_thankyou_bookings', 'bwp_thankyou_bookings_shortcode');

/**
 * Display order totals summary in thank you page
 */
function bwp_thankyou_totals_shortcode() {
    ob_start();
    
    // Get the order
    $order_id = absint(get_query_var('order-received'));
    if (!$order_id) return;
    
    $order = wc_get_order($order_id);
    if (!$order) return;
    
    // Get order totals
    $subtotal = $order->get_subtotal();
    $discount = $order->get_total_discount();
    $total = $order->get_total();
    
    // Get payment method info
    $payment_method = $order->get_payment_method();
    $payment_method_title = $order->get_payment_method_title();
    $last4 = $order->get_meta('_payment_card_last4');
    $exp_month = $order->get_meta('_payment_card_expiry_month');
    $exp_year = $order->get_meta('_payment_card_expiry_year');
    ?>
    <div class="price-summary card--cart">
        <h2>Price Summary</h2>
        
        <div class="paid-by">
            <span class="label">
            <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M5.33268 26.6663C4.59935 26.6663 3.97179 26.4055 3.45002 25.8837C2.92735 25.361 2.66602 24.733 2.66602 23.9997V7.99967C2.66602 7.26634 2.92735 6.63879 3.45002 6.11701C3.97179 5.59434 4.59935 5.33301 5.33268 5.33301H26.666C27.3993 5.33301 28.0273 5.59434 28.55 6.11701C29.0718 6.63879 29.3327 7.26634 29.3327 7.99967V23.9997C29.3327 24.733 29.0718 25.361 28.55 25.8837C28.0273 26.4055 27.3993 26.6663 26.666 26.6663H5.33268ZM5.33268 10.6663V15.9997H26.666V10.6663H5.33268Z" fill="var(--be-03)"/>
            </svg>
            Payment Method</span>
            <div class="payment-info">
                <?php echo esc_html($payment_method_title); ?>
            </div>
        </div>
        
        <div class="order-totals">
            <div class="subtotal">
                <span class="label">Subtotal</span>
                <span class="subtotal-amount"><?php echo wc_price($subtotal); ?></span>
            </div>
            <?php if ($discount > 0) : ?>
            <div class="discount">
                <div class="label-group">
                    <span class="label">Discount</span>
                    <?php 
                    $coupon_codes = $order->get_coupon_codes();
                    if (!empty($coupon_codes)) :
                        $coupon_code = $coupon_codes[0];
                    ?>
                    <div class="coupon-badge">
                        <?php echo esc_html($coupon_code); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <span class="discount-amount">-<?php echo wc_price($discount); ?></span>
            </div>
            <?php endif; ?>
            <div class="total">
                <div class="label-group">
                    <span class="label">Total</span>
                    <p class="taxes-note">Included taxes & fees</p>
                </div>
                <span class="total-amount"><?php echo wc_price($total); ?></span>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('bwp_thankyou_totals', 'bwp_thankyou_totals_shortcode');
