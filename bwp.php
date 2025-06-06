<?php
/**
 * Plugin Name: Booking WP
 * Description: Custom booking functionality for WooCommerce
 * Version: 1.0.0
 * Author: BWP Team
 * Text Domain: bwp
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('BWP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BWP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include files
require_once BWP_PLUGIN_DIR . 'bwp-profile.php';
require_once BWP_PLUGIN_DIR . 'bwp-booking.php';
require_once BWP_PLUGIN_DIR . 'bwp-cart.php';
require_once BWP_PLUGIN_DIR . 'bwp-checkout.php';
require_once BWP_PLUGIN_DIR . 'bwp-thankyou.php';
require_once BWP_PLUGIN_DIR . 'bwp-account.php';
