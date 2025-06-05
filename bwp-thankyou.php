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
