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
    <div class="bwp-your-bookings">
        <h2>Your Bookings</h2>
        
        <div class="booking-header">
            <div class="booking-info">
                <div class="info-group">
                    <i class="fas fa-ticket"></i>
                    <div class="info-text">
                        <span class="label">Booking Number</span>
                        <span class="value"><?php echo esc_html($order_number); ?> <i class="fas fa-copy"></i></span>
                    </div>
                </div>
                
                <div class="info-group">
                    <i class="fas fa-calendar-check"></i>
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
                                <i class="fas fa-calendar"></i>
                                <?php echo esc_html($booking_date); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="guests">
                            <div class="guest-type adults">
                                <i class="fas fa-user"></i>
                                <span class="guest-count"><?php echo esc_html($adults); ?>x</span>
                                <span class="guest-label">Adults</span>
                            </div>
                            <?php if ($children && intval($children) > 0): ?>
                            <div class="guest-type children">
                                <i class="fas fa-child"></i>
                                <span class="guest-count"><?php echo esc_html($children); ?>x</span>
                                <span class="guest-label">Children</span>
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
