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

/**
 * Set payment methods as active tab
 */
function bwp_set_payment_methods_active($classes, $endpoint) {
    if (!is_wc_endpoint_url()) {
        if ($endpoint === 'payment-methods') {
            $classes[] = 'is-active';
        } elseif ($endpoint === 'dashboard') {
            $key = array_search('is-active', $classes);
            if ($key !== false) {
                unset($classes[$key]);
            }
        }
    }
    return $classes;
}
add_filter('woocommerce_account_menu_item_classes', 'bwp_set_payment_methods_active', 10, 2);

/**
 * Reorder menu items to show payment methods first
 */
function bwp_reorder_account_menu($menu_items) {
    // Move payment methods to the top
    $payment_methods = $menu_items['payment-methods'];
    unset($menu_items['payment-methods']);
    
    return array_merge(
        ['payment-methods' => $payment_methods],
        $menu_items
    );
}
add_filter('woocommerce_account_menu_items', 'bwp_reorder_account_menu');
