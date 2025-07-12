<?php
/**
 * BWP Account functionality
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
 * Redirect non-logged in users to login page
 */
function bwp_redirect_to_login() {
    // Only on account pages
    if (!is_account_page()) {
        return;
    }

    // If not logged in, redirect to login page
    if (!is_user_logged_in()) {
        wp_safe_redirect(wc_get_page_permalink('myaccount') . 'login/');
        exit;
    }
}
add_action('template_redirect', 'bwp_redirect_to_login', 5);

/**
 * Replace dashboard content with payment methods
 */
function bwp_replace_dashboard_content() {
    // Only on main account page
    if (!is_account_page() || is_wc_endpoint_url()) {
        return;
    }

    // Remove default dashboard content
    remove_action('woocommerce_account_content', 'woocommerce_account_content');
    
    // Add payment methods content
    add_action('woocommerce_account_content', function() {
        if (function_exists('woocommerce_account_payment_methods')) {
            woocommerce_account_payment_methods();
        }
    });
}
add_action('template_redirect', 'bwp_replace_dashboard_content');
