<?php
/**
 * BWP Cart Functionality
 * 
 * Implements custom cart and checkout functionality for Booking WP
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check if WooCommerce is active
if (!function_exists('WC')) {
    return;
}

// Check if ACF is active
if (!function_exists('get_field')) {
    return;
}

// Your Booking Section
function bwp_your_booking_shortcode() {
    ob_start();
    ?>
    <div class="bwp-booking-section">
        <div class="section-header">
            <h2>Your Booking</h2>
            <a href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>">All Tours</a>
        </div>
        <!-- Delete Confirmation Modal -->
        <div id="deleteConfirmationModal" class="modal">
            <div class="modal-content">
                <div class="modal-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none">
                        <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z" fill="#E94E4E"/>
                    </svg>
                </div>
                <h2>Deleting <span id="deleteItemCount">1</span> Items</h2>
                <div id="deleteItemDetails" class="delete-item-details"></div>
                <div class="modal-actions">
                    <button class="cancel-btn" onclick="closeDeleteModal()">Cancel</button>
                    <button class="confirm-delete-btn" onclick="confirmDelete()">Confirm Delete</button>
                </div>
            </div>
        </div>

        <div class="booking-items">
            <?php
            $cart = WC()->cart;
            if ($cart && !$cart->is_empty()) {
                foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
                    $product = $cart_item['data'];
                    $product_id = $product->get_id();
                    $quantity = $cart_item['quantity'];
                    $adults = isset($cart_item['bwp_adults']) ? intval($cart_item['bwp_adults']) : 0;
                    $children = isset($cart_item['bwp_children']) ? intval($cart_item['bwp_children']) : 0;
                    
                    // Get base price
                    $original_product = wc_get_product($product_id);
                    $base_price = floatval($original_product->get_price('edit'));
                    
                    // Calculate additional costs
                    $additional_adult_price = 0;
                    $additional_child_price = 0;
                    $additional_departure_price = 0;
                    
                    if (function_exists('get_field')) {
                        // Get departure price
                        if (isset($cart_item['bwp_departure_location']) && !empty($cart_item['bwp_departure_location'])) {
                            $selected_location = $cart_item['bwp_departure_location'];
                            $departure_group = get_field('departure', $product_id);
                            
                            if ($departure_group) {
                                if ($selected_location === 'phuket') {
                                    $price_val = isset($departure_group['phuket_additional_price']) ? $departure_group['phuket_additional_price'] : 0;
                                } elseif ($selected_location === 'khaolak') {
                                    $price_val = isset($departure_group['khaolak_additional_price']) ? $departure_group['khaolak_additional_price'] : 0;
                                }
                                if (is_numeric($price_val)) {
                                    $additional_departure_price = floatval($price_val);
                                }
                            }
                        }
                        
                        // Get adult tier price
                        if ($adults >= 2) {
                            $adult_tiers = get_field('adult_price_tiers', $product_id);
                            if ($adult_tiers) {
                                foreach ($adult_tiers as $tier) {
                                    if (isset($tier['number_of_adults']) && intval($tier['number_of_adults']) == $adults) {
                                        if (isset($tier['additional_price']) && is_numeric($tier['additional_price'])) {
                                            $additional_adult_price = floatval($tier['additional_price']);
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                        
                        // Get child tier price
                        if ($children >= 1) {
                            $child_tiers = get_field('child_price_tiers', $product_id);
                            if ($child_tiers) {
                                foreach ($child_tiers as $tier) {
                                    if (isset($tier['number_of_children']) && intval($tier['number_of_children']) == $children) {
                                        if (isset($tier['additional_price']) && is_numeric($tier['additional_price'])) {
                                            $additional_child_price = floatval($tier['additional_price']);
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    
                    $total_price = $base_price + $additional_adult_price + $additional_child_price + $additional_departure_price;
                    
                    // Get booking details
                    $booking_date = isset($cart_item['bwp_start_date']) ? $cart_item['bwp_start_date'] : '';
                    $adults = isset($cart_item['bwp_adults']) ? $cart_item['bwp_adults'] : 0;
                    $children = isset($cart_item['bwp_children']) ? $cart_item['bwp_children'] : 0;
                    ?>
                    <div class="booking-item">
                        <div class="booking-image">
                            <?php 
                            $image_html = $product->get_image('thumbnail');
                            // Add class to img tag
                            $image_html = str_replace('<img', '<img class="booking-img"', $image_html);
                            echo $image_html;
                            ?>
                        </div>
                        <div class="booking-details">
                            <div class="product-title">
                                <h3 class="booking-title"><a href="<?php echo esc_url($product->get_permalink()); ?>"><?php echo get_the_title($product_id); ?></a></h3>
                            </div>
                            <div class="booking-meta">
                                <?php if ($booking_date): ?>
                                    <div class="date"><i class="fas fa-calendar"></i> <?php echo esc_html($booking_date); ?></div>
                                <?php endif; ?>
                                
                                <div class="guests">
                                    <?php
                                    // Get adult tiers
                                    $adult_tiers = get_field('adult_price_tiers', $product_id);
                                    $max_adults = 1; // Default min is 1
                                    if ($adult_tiers) {
                                        foreach ($adult_tiers as $tier) {
                                            $num_adults = isset($tier['number_of_adults']) ? intval($tier['number_of_adults']) : 0;
                                            if ($num_adults > $max_adults) {
                                                $max_adults = $num_adults;
                                            }
                                        }
                                    }
                                    ?>
                                    <div class="guest-type adults">
                                        <i class="fas fa-user"></i>
                                        <div class="quantity-controls">
                                            <button type="button" class="quantity-btn minus" data-type="adults" data-item-key="<?php echo esc_attr($cart_item_key); ?>" data-min="1">&minus;</button>
                                            <span class="quantity"><?php echo $adults; ?></span>
                                            <button type="button" class="quantity-btn plus" data-type="adults" data-item-key="<?php echo esc_attr($cart_item_key); ?>" data-max="<?php echo esc_attr($max_adults); ?>">&plus;</button>
                                        </div>
                                        <span class="guest-label">Adults</span>
                                    </div>
                                    <?php
                                    // Get child tiers
                                    $child_tiers = get_field('child_price_tiers', $product_id);
                                    $max_children = 0; // Default min is 0
                                    if ($child_tiers) {
                                        foreach ($child_tiers as $tier) {
                                            $num_children = isset($tier['number_of_children']) ? intval($tier['number_of_children']) : 0;
                                            if ($num_children > $max_children) {
                                                $max_children = $num_children;
                                            }
                                        }
                                    }
                                    ?>
                                    <div class="guest-type children">
                                        <i class="fas fa-child"></i>
                                        <div class="quantity-controls">
                                            <button type="button" class="quantity-btn minus" data-type="children" data-item-key="<?php echo esc_attr($cart_item_key); ?>" data-min="0">&minus;</button>
                                            <span class="quantity"><?php echo $children; ?></span>
                                            <button type="button" class="quantity-btn plus" data-type="children" data-item-key="<?php echo esc_attr($cart_item_key); ?>" data-max="<?php echo esc_attr($max_children); ?>">&plus;</button>
                                        </div>
                                        <span class="guest-label">Children</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="booking-price">
                            <div class="total-price" data-item-key="<?php echo esc_attr($cart_item_key); ?>">
                                <span class="price"><?php echo wc_price($total_price); ?></span>
                            </div>
                        </div>
                        <div class="remove-item">
                            <?php
                            echo apply_filters('woocommerce_cart_item_remove_link',
                                sprintf('<a href="%s" class="remove" title="%s">&times;</a>',
                                    esc_url(wc_get_cart_remove_url($cart_item_key)),
                                    esc_html__('Remove this item', 'woocommerce')
                                ),
                                $cart_item_key
                            );
                            ?>
                        </div>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
        
        <!-- Add-on Bundles Section -->
        <div class="add-on-bundles">
            <h3>Add-on bundles</h3>
            <p>Get more value when you bundle—cheaper than buying separately.</p>
            <!-- Add your bundle options here -->
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('msc_your_booking_summary', 'bwp_your_booking_shortcode');

// Your Information Section
function bwp_customer_information_shortcode() {
    ob_start();
    ?>
    <?php
    // Try to get data from session first
    $customer_data = WC()->session ? WC()->session->get('bwp_customer_data') : null;
    
    if ($customer_data) {
        $billing_first_name = $customer_data['first_name'];
        $billing_last_name = $customer_data['last_name'];
        $billing_email = $customer_data['email'];
        $billing_phone = $customer_data['phone'];
        $billing_thai_id = $customer_data['thai_id'];
        $billing_hotel_name = $customer_data['hotel_name'];
        $billing_room = $customer_data['room'];
        $billing_special_requests = $customer_data['special_requests'];
    } else {
        // Fallback to user meta if session data not available
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        
        $billing_first_name = get_user_meta($user_id, 'billing_first_name', true);
        $billing_last_name = get_user_meta($user_id, 'billing_last_name', true);
        $billing_thai_id = get_user_meta($user_id, 'billing_thai_id', true);
        $billing_email = get_user_meta($user_id, 'billing_email', true);
        $billing_phone = get_user_meta($user_id, 'billing_phone', true);
        $billing_hotel_name = get_user_meta($user_id, 'billing_hotel_name', true);
        $billing_room = get_user_meta($user_id, 'billing_room', true);
        $billing_special_requests = get_user_meta($user_id, 'billing_special_requests', true);
    }
    
    // Debug
    error_log('BWP Debug - Customer Info Form:');
    error_log('Session Data: ' . print_r($customer_data, true));
    error_log('First Name: ' . $billing_first_name);
    error_log('Last Name: ' . $billing_last_name);
    error_log('Thai ID: ' . $billing_thai_id);
    ?>
    <div class="bwp-customer-information">
    <div class="section-header"><h2>Your Information</h2></div>
    <form class="bwp-customer-form">
            <?php wp_nonce_field('bwp_save_customer_info', 'bwp_nonce'); ?>
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" 
                           value="<?php echo esc_attr($billing_first_name); ?>" 
                           pattern="[A-Za-z ]{2,}" 
                           title="Please enter at least 2 letters. Numbers and special characters are not allowed."
                           placeholder="Enter your first name"
                           required>
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" 
                           value="<?php echo esc_attr($billing_last_name); ?>" 
                           pattern="[A-Za-z ]{2,}" 
                           title="Please enter at least 2 letters. Numbers and special characters are not allowed."
                           placeholder="Enter your last name"
                           required>
                </div>
            </div>

            <div class="form-group">
                <label for="thai_id">Thai ID or Passport Number *</label>
                <input type="text" id="thai_id" name="thai_id" 
                       value="<?php echo esc_attr($billing_thai_id); ?>" 
                       pattern="[0-9A-Za-z]{8,}" 
                       title="Please enter a valid Thai ID (13 digits) or Passport number (at least 8 characters)"
                       placeholder="Enter Thai ID or Passport number"
                       required>
            </div>
            
            <div class="form-row">
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" 
                       value="<?php echo esc_attr($billing_email); ?>" 
                       pattern="[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}" 
                       title="Please enter a valid email address"
                       placeholder="Enter your email address"
                       required>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number *</label>
                <input type="tel" id="phone" name="phone" 
                       value="<?php echo esc_attr($billing_phone); ?>" 
                       pattern="[0-9+]{9,}" 
                       title="Please enter a valid phone number (at least 9 digits, can include + for country code)"
                       placeholder="Enter your phone number"
                       required>
            </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="hotel_name">Hotel Name *</label>
                    <input type="text" id="hotel_name" name="hotel_name" 
                           value="<?php echo esc_attr($billing_hotel_name); ?>" 
                           pattern=".{3,}" 
                           title="Please enter hotel name (at least 3 characters)"
                           placeholder="Enter hotel name"
                           required>
                </div>
                <div class="form-group">
                    <label for="room">Room *</label>
                    <input type="text" id="room" name="room" 
                           value="<?php echo esc_attr($billing_room); ?>" 
                           pattern="[A-Za-z0-9 \-]+" 
                           title="Please enter room number/name (letters, numbers, spaces and hyphens only)"
                           placeholder="Enter room number"
                           required>
                </div>
            </div>

            <div class="form-group">
                <label for="special_requests">Special Requests</label>
                <textarea id="special_requests" name="special_requests" 
                           maxlength="500"
                           placeholder="Enter any special requests or requirements"><?php echo esc_textarea($billing_special_requests); ?></textarea>
                <small class="form-text text-muted">Maximum 500 characters</small>
            </div>

            <?php wp_nonce_field('bwp_save_customer_info', 'bwp_nonce'); ?>
            <button type="submit" class="submit-button">Continue to Payment</button>
        </form>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('msc_customer_information_form', 'bwp_customer_information_shortcode');

// Order Summary Section
function bwp_order_summary_shortcode() {
    ob_start();
    ?>
    <div class="bwp-order-summary">
        <div class="section-header">
            <h2>Order Summary</h2>
            <div class="total-bookings">Total: <?php echo count(WC()->cart->get_cart()); ?> Bookings</div>
        </div>

        <?php
        $cart = WC()->cart;
        if ($cart && !$cart->is_empty()) {
            foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
                $product = $cart_item['data'];
                ?>
                <div class="summary-item">
                    <div class="item-image">
                        <?php echo $product->get_image('thumbnail'); ?>
                    </div>
                    <div class="item-details">
                        <h4><a href="<?php echo esc_url($product->get_permalink()); ?>"><?php echo $product->get_name(); ?></a></h4>
                        <div class="booking-meta">
                            <?php if (isset($cart_item['bwp_start_date'])): ?>
                                <div class="date"><i class="fas fa-calendar"></i> <?php echo esc_html($cart_item['bwp_start_date']); ?></div>
                            <?php endif; ?>
                            
                            <div class="guests" data-item-key="<?php echo esc_attr($cart_item_key); ?>">
                                <div class="guest-type adults">
                                    <i class="fas fa-user"></i>
                                    <span class="guest-count" data-item-key="<?php echo esc_attr($cart_item_key); ?>" data-type="adults"><?php echo isset($cart_item['bwp_adults']) ? $cart_item['bwp_adults'] : 0; ?></span>
                                    <span class="guest-label">Adults</span>
                                </div>
                                <?php if (isset($cart_item['bwp_children']) && intval($cart_item['bwp_children']) > 0): ?>
                                <div class="guest-type children">
                                    <i class="fas fa-child"></i>
                                    <span class="guest-count" data-item-key="<?php echo esc_attr($cart_item_key); ?>" data-type="children"><?php echo $cart_item['bwp_children']; ?></span>
                                    <span class="guest-label">Children</span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="item-price">
                        <div class="price-breakdown">
                            <?php
                            // Get base price and quantities
                            $base_price = floatval($product->get_price('edit'));
                            $adults = isset($cart_item['bwp_adults']) ? intval($cart_item['bwp_adults']) : 1;
                            $children = isset($cart_item['bwp_children']) ? intval($cart_item['bwp_children']) : 0;
                            $departure_location = isset($cart_item['bwp_departure_location']) ? $cart_item['bwp_departure_location'] : '';
                            
                            // Initialize total price
                            $total_price = $base_price;
                            
                            // Get adult tier price
                            $additional_adult_cost = 0;
                            if ($adults >= 2) {
                                $adult_tiers = get_field('adult_price_tiers', $product->get_id());
                                if ($adult_tiers) {
                                    foreach ($adult_tiers as $tier) {
                                        if (isset($tier['number_of_adults']) && intval($tier['number_of_adults']) == $adults) {
                                            if (isset($tier['additional_price']) && is_numeric($tier['additional_price'])) {
                                                $additional_adult_cost = floatval($tier['additional_price']);
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                            
                            // Get child tier price
                            $additional_child_cost = 0;
                            if ($children >= 1) {
                                $child_tiers = get_field('child_price_tiers', $product->get_id());
                                if ($child_tiers) {
                                    foreach ($child_tiers as $tier) {
                                        if (isset($tier['number_of_children']) && intval($tier['number_of_children']) == $children) {
                                            if (isset($tier['additional_price']) && is_numeric($tier['additional_price'])) {
                                                $additional_child_cost = floatval($tier['additional_price']);
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                            
                            // Get departure location price
                            $departure_cost = 0;
                            $departure_prices = get_field('departure_prices', $product->get_id());
                            if ($departure_location && is_array($departure_prices)) {
                                foreach ($departure_prices as $price) {
                                    if ($price['location'] === $departure_location) {
                                        $departure_cost = floatval($price['price']);
                                        break;
                                    }
                                }
                            }
                            
                            // Calculate total price for this item
                            $total_price += $additional_adult_cost + $additional_child_cost + $departure_cost;
                            
                            // Update cart item data
                            $cart->cart_contents[$cart_item_key]['line_total'] = $total_price;
                            $cart->cart_contents[$cart_item_key]['line_subtotal'] = $total_price;
                            $cart->cart_contents[$cart_item_key]['total_price'] = $total_price;
                            $cart->cart_contents[$cart_item_key]['data']->set_price($total_price);
                            ?>
                            <div class="total-price" data-item-key="<?php echo esc_attr($cart_item_key); ?>"><?php echo wc_price($total_price); ?></div>
                        </div>
                    </div>
                </div>
                <?php
            }
        }
        ?>

        <?php
        // Save cart data
        $cart->set_session();
        
        // Calculate totals from cart items
        $subtotal = 0;
        $total = 0;
        $discount = 0;
        
        // Calculate subtotal first
        foreach ($cart->get_cart() as $item) {
            $subtotal += $item['line_subtotal'];
        }
        
        // Calculate discount if coupon is applied
        if ($cart->has_discount()) {
            $coupons = $cart->get_applied_coupons();
            if (!empty($coupons)) {
                $coupon = new WC_Coupon($coupons[0]);
                if ($coupon && $coupon->is_valid() && $coupon->get_discount_type() === 'percent') {
                    $discount = ($subtotal * $coupon->get_amount()) / 100;
                }
            }
        }
        
        // Calculate final total
        $total = $subtotal - $discount;
        $has_coupon = !empty(WC()->cart->get_applied_coupons());
        ?>
        <div class="coupon-form">
            <div class="coupon-input-group" <?php echo $has_coupon ? 'style="display: none;"' : ''; ?>>
                <input type="text" id="coupon_code" class="input-text" placeholder="Enter coupon code" />
                <button type="button" class="apply-coupon-btn">Apply</button>
            </div>
            <div class="coupon-message"></div>
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
                    $coupons = WC()->cart->get_applied_coupons();
                    if (!empty($coupons)) :
                        $coupon_code = $coupons[0];
                    ?>
                    <div class="coupon-badge">
                        <?php echo esc_html($coupon_code); ?>
                        <button type="button" class="remove-coupon" data-coupon="<?php echo esc_attr($coupon_code); ?>">&times;</button>
                    </div>
                    <?php endif; ?>
                </div>
                <span class="discount-amount">-<?php echo wc_price($discount); ?></span>
            </div>
            <?php endif; ?>
            <div class="total">
                <span class="label">Total</span>
                <span class="total-amount"><?php echo wc_price($total); ?></span>
            </div>
        </div>

        <div class="refund-policy">
            <h4>Get a full refund before <?php echo date('j M Y', strtotime('+10 days')); ?></h4>
            <p>Cancel before <?php echo date('j M Y', strtotime('+10 days')); ?> to receive a full refund. Cancellations are quick and can be done online.</p>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('msc_order_totals_summary', 'bwp_order_summary_shortcode');

// Enqueue necessary styles
function bwp_cart_enqueue_styles() {
    wp_enqueue_style('bwp-cart-styles', plugins_url('css/bwp-cart.css', __FILE__));
    wp_enqueue_script('bwp-cart-script', plugins_url('js/bwp-cart.js', __FILE__), array('jquery'), '', true);
    
    wp_localize_script('bwp-cart-script', 'bwp_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('bwp_update_guest_quantity'),
        'customer_nonce' => wp_create_nonce('bwp_save_customer_info'),
        'coupon_nonce' => wp_create_nonce('bwp_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'bwp_cart_enqueue_styles');

// AJAX handler for saving customer information
function bwp_save_customer_info() {
    // Verify nonce for security
    if (!isset($_POST['bwp_nonce']) || !wp_verify_nonce($_POST['bwp_nonce'], 'bwp_save_customer_info')) {
        error_log('BWP Debug - Nonce verification failed:');
        error_log('Received nonce: ' . (isset($_POST['bwp_nonce']) ? $_POST['bwp_nonce'] : 'not set'));
        error_log('Action: ' . $_POST['action']);
        wp_send_json_error('Invalid security token');
        return;
    }

    $customer_data = array(
        'first_name' => sanitize_text_field($_POST['first_name']),
        'last_name' => sanitize_text_field($_POST['last_name']),
        'thai_id' => sanitize_text_field($_POST['thai_id']),
        'email' => sanitize_email($_POST['email']),
        'phone' => sanitize_text_field($_POST['phone']),
        'hotel_name' => sanitize_text_field($_POST['hotel_name']),
        'room' => sanitize_text_field($_POST['room']),
        'special_requests' => sanitize_textarea_field($_POST['special_requests'])
    );

    // Get current user ID if logged in
    $user_id = get_current_user_id();

    // If user is logged in, update their meta data
    if ($user_id) {
        // Update WooCommerce billing details
        update_user_meta($user_id, 'billing_first_name', $customer_data['first_name']);
        update_user_meta($user_id, 'billing_last_name', $customer_data['last_name']);
        update_user_meta($user_id, 'billing_email', $customer_data['email']);
        update_user_meta($user_id, 'billing_phone', $customer_data['phone']);
        
        // Save additional fields
        update_user_meta($user_id, 'billing_thai_id', $customer_data['thai_id']);
        update_user_meta($user_id, 'billing_hotel_name', $customer_data['hotel_name']);
        update_user_meta($user_id, 'billing_room', $customer_data['room']);
        update_user_meta($user_id, 'billing_special_requests', $customer_data['special_requests']);
    }

    // Add required WooCommerce billing fields to customer data
    $customer_data['billing_country'] = 'TH';
    $customer_data['billing_address_1'] = '-';
    $customer_data['billing_city'] = '-';
    $customer_data['billing_state'] = 'Bangkok';
    $customer_data['billing_postcode'] = '10110';

    // Store data in WooCommerce session for order creation
    WC()->session->set('bwp_customer_data', $customer_data);

    // Get WooCommerce customer object
    $customer = WC()->customer;
    if ($customer) {
        // Set billing fields
        $customer->set_billing_first_name($customer_data['first_name']);
        $customer->set_billing_last_name($customer_data['last_name']);
        $customer->set_billing_email($customer_data['email']);
        $customer->set_billing_phone($customer_data['phone']);
        $customer->set_billing_country($customer_data['billing_country']);
        $customer->set_billing_address_1($customer_data['billing_address_1']);
        $customer->set_billing_city($customer_data['billing_city']);
        $customer->set_billing_state($customer_data['billing_state']);
        $customer->set_billing_postcode($customer_data['billing_postcode']);

        // Set custom fields directly in customer session
        WC()->session->set('billing_thai_id', $customer_data['thai_id']);
        WC()->session->set('billing_hotel_name', $customer_data['hotel_name']);
        WC()->session->set('billing_room', $customer_data['room']);
        WC()->session->set('billing_special_requests', $customer_data['special_requests']);
        $customer->set_billing_postcode($customer_data['billing_postcode']);

        // Set additional fields
        $customer->add_meta_data('billing_thai_id', $customer_data['thai_id'], true);
        $customer->add_meta_data('billing_hotel_name', $customer_data['hotel_name'], true);
        $customer->add_meta_data('billing_room', $customer_data['room'], true);
        $customer->add_meta_data('billing_special_requests', $customer_data['special_requests'], true);

        // Save all changes
        $customer->save();
    }
    WC()->customer->save();

    // Redirect to WooCommerce checkout page
    $next_step_url = wc_get_checkout_url();
    
    wp_send_json_success(array(
        'message' => 'Customer information saved successfully',
        'redirect_url' => $next_step_url
    ));
}
add_action('wp_ajax_bwp_save_customer_info', 'bwp_save_customer_info');
add_action('wp_ajax_nopriv_bwp_save_customer_info', 'bwp_save_customer_info');

// Add customer data to order
function bwp_add_customer_data_to_order($order_id) {
    $customer_data = WC()->session->get('bwp_customer_data');
    
    if ($customer_data) {
        // Update order billing details
        update_post_meta($order_id, '_billing_first_name', $customer_data['first_name']);
        update_post_meta($order_id, '_billing_last_name', $customer_data['last_name']);
        update_post_meta($order_id, '_billing_email', $customer_data['email']);
        update_post_meta($order_id, '_billing_phone', $customer_data['phone']);
        update_post_meta($order_id, '_billing_thai_id', $customer_data['thai_id']);
        update_post_meta($order_id, '_billing_hotel_name', $customer_data['hotel_name']);
        update_post_meta($order_id, '_billing_room', $customer_data['room']);
        update_post_meta($order_id, '_billing_special_requests', $customer_data['special_requests']);
        
        // Also save as custom fields for compatibility
        update_post_meta($order_id, '_thai_id', $customer_data['thai_id']);
        update_post_meta($order_id, '_hotel_name', $customer_data['hotel_name']);
        update_post_meta($order_id, '_room_number', $customer_data['room']);
        update_post_meta($order_id, '_special_requests', $customer_data['special_requests']);
        
        // Clear the session data
        WC()->session->__unset('bwp_customer_data');
    }
}
add_action('woocommerce_checkout_update_order_meta', 'bwp_add_customer_data_to_order');

// Add custom billing fields
function bwp_add_billing_fields($fields) {
    // Get customer data from session
    $customer_data = WC()->session ? WC()->session->get('bwp_customer_data') : null;
    
    $custom_fields = array(
        'billing_thai_id' => array(
            'type' => 'text',
            'label' => 'Thai ID/Passport Number',
            'required' => true,
            'class' => array('form-row-wide'),
            'clear' => true,
            'priority' => 25,
            'default' => $customer_data ? $customer_data['thai_id'] : ''
        ),
        'billing_hotel_name' => array(
            'type' => 'text',
            'label' => 'Hotel Name',
            'required' => true,
            'class' => array('form-row-wide'),
            'clear' => true,
            'priority' => 35,
            'default' => $customer_data ? $customer_data['hotel_name'] : ''
        ),
        'billing_room' => array(
            'type' => 'text',
            'label' => 'Room Number',
            'required' => true,
            'class' => array('form-row-wide'),
            'clear' => true,
            'priority' => 36,
            'default' => $customer_data ? $customer_data['room'] : ''
        ),
        'billing_special_requests' => array(
            'type' => 'textarea',
            'label' => 'Special Requests',
            'required' => false,
            'class' => array('form-row-wide'),
            'clear' => true,
            'priority' => 37,
            'default' => $customer_data ? $customer_data['special_requests'] : ''
        )
    );
    
    return array_merge($fields, $custom_fields);
}
add_filter('woocommerce_billing_fields', 'bwp_add_billing_fields');

// Add custom admin order data
function bwp_admin_billing_fields($fields) {
    $fields['thai_id'] = array(
        'label' => 'Thai ID/Passport Number',
        'show' => true
    );
    $fields['hotel_name'] = array(
        'label' => 'Hotel Name',
        'show' => true
    );
    $fields['room'] = array(
        'label' => 'Room Number',
        'show' => true
    );
    $fields['special_requests'] = array(
        'label' => 'Special Requests',
        'show' => true
    );
    return $fields;
}
add_filter('woocommerce_admin_billing_fields', 'bwp_admin_billing_fields', 20);

// Add custom columns to orders list
function bwp_add_order_columns($columns) {
    $new_columns = array();
    
    foreach ($columns as $key => $column) {
        $new_columns[$key] = $column;
        if ($key === 'billing_address') {
            $new_columns['billing_thai_id'] = 'Thai ID/Passport';
            $new_columns['billing_hotel'] = 'Hotel & Room';
        }
    }
    
    return $new_columns;
}
add_filter('manage_edit-shop_order_columns', 'bwp_add_order_columns', 20);

// Display custom column content
function bwp_order_column_content($column) {
    global $post;
    $order = wc_get_order($post->ID);
    
    if ($column === 'billing_thai_id') {
        echo get_post_meta($post->ID, '_billing_thai_id', true);
    }
    
    if ($column === 'billing_hotel') {
        $hotel = get_post_meta($post->ID, '_billing_hotel_name', true);
        $room = get_post_meta($post->ID, '_billing_room', true);
        echo $hotel . ' - Room ' . $room;
    }
}
add_action('manage_shop_order_posts_custom_column', 'bwp_order_column_content');

// Add fields to order edit page
function bwp_add_order_meta_boxes() {
    add_meta_box(
        'bwp_order_fields',
        'Additional Booking Information',
        'bwp_order_fields_content',
        'shop_order',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'bwp_add_order_meta_boxes');

// Display fields in order meta box
function bwp_order_fields_content($post) {
    $order = wc_get_order($post->ID);
    $thai_id = get_post_meta($post->ID, '_billing_thai_id', true);
    $hotel_name = get_post_meta($post->ID, '_billing_hotel_name', true);
    $room = get_post_meta($post->ID, '_billing_room', true);
    ?>
    <div class="bwp-order-fields">
        <p>
            <label>Thai ID/Passport Number:</label>
            <input type="text" name="billing_thai_id" value="<?php echo esc_attr($thai_id); ?>" />
        </p>
        <p>
            <label>Hotel Name:</label>
            <input type="text" name="billing_hotel_name" value="<?php echo esc_attr($hotel_name); ?>" />
        </p>
        <p>
            <label>Room Number:</label>
            <input type="text" name="billing_room" value="<?php echo esc_attr($room); ?>" />
        </p>
    </div>
    <?php
}

// Save order meta box data
function bwp_save_order_meta_box($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    
    $fields = array('billing_thai_id', 'billing_hotel_name', 'billing_room');
    
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
        }
    }
}
add_action('save_post', 'bwp_save_order_meta_box');

// Add custom fields to user profile
function bwp_add_customer_meta_fields($user) {
    ?>
    <h3>Additional Customer Information</h3>
    <table class="form-table">
        <tr>
            <th><label for="billing_thai_id">Thai ID/Passport Number</label></th>
            <td>
                <input type="text" name="billing_thai_id" id="billing_thai_id" 
                       value="<?php echo esc_attr(get_user_meta($user->ID, 'billing_thai_id', true)); ?>" 
                       class="regular-text" />
                <p class="description">This ID will be used for all future bookings.</p>
            </td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'bwp_add_customer_meta_fields');
add_action('edit_user_profile', 'bwp_add_customer_meta_fields');

// Save custom user meta fields
function bwp_save_customer_meta_fields($user_id) {
    if (!current_user_can('edit_user', $user_id)) return;
    
    if (isset($_POST['billing_thai_id'])) {
        update_user_meta($user_id, 'billing_thai_id', sanitize_text_field($_POST['billing_thai_id']));
    }
}
add_action('personal_options_update', 'bwp_save_customer_meta_fields');
add_action('edit_user_profile_update', 'bwp_save_customer_meta_fields');

// AJAX handler for updating guest quantities
function bwp_update_guest_quantity() {
    check_ajax_referer('bwp_update_guest_quantity', 'nonce');
    
    if (!isset($_POST['item_key']) || !isset($_POST['type']) || !isset($_POST['action_type'])) {
        wp_send_json_error('Missing required parameters');
        return;
    }
    
    $cart_item_key = sanitize_text_field($_POST['item_key']);
    $type = sanitize_text_field($_POST['type']);
    $action = sanitize_text_field($_POST['action_type']);
    
    if (!in_array($type, ['adults', 'children'])) {
        wp_send_json_error('Invalid guest type');
        return;
    }
    
    if (!in_array($action, ['increase', 'decrease'])) {
        wp_send_json_error('Invalid action type');
        return;
    }
    
    $cart = WC()->cart;
    if (!$cart || !isset($cart->cart_contents[$cart_item_key])) {
        wp_send_json_error('Invalid cart item');
        return;
    }
    
    $cart_item = $cart->cart_contents[$cart_item_key];
    $current_value = isset($cart_item['bwp_' . $type]) ? intval($cart_item['bwp_' . $type]) : ($type === 'adults' ? 1 : 0);
    
    // Calculate new value
    if ($action === 'increase') {
        $new_value = $current_value + 1;
    } else {
        $new_value = max($current_value - 1, ($type === 'adults' ? 1 : 0));
    }
    
    // Update cart item meta
    $cart->cart_contents[$cart_item_key]['bwp_' . $type] = $new_value;
    $cart->set_session();
    
    // Calculate new total price
    $cart_item = WC()->cart->get_cart_item($cart_item_key);
    $product = wc_get_product($cart_item['product_id']);
    $base_price = floatval($product->get_price());
    
    // Get current adults and children counts from cart item
    $adults = isset($cart_item['bwp_adults']) ? intval($cart_item['bwp_adults']) : 1;
    $children = isset($cart_item['bwp_children']) ? intval($cart_item['bwp_children']) : 0;
    
    // Update the count based on which type was changed
    if ($type === 'adults') {
        $adults = $new_value;
    } else {
        $children = $new_value;
    }
    
    // Initialize costs
    $total_price = $base_price;
    $additional_adult_cost = 0;
    $additional_child_cost = 0;
    $additional_departure_cost = 0;
    
    // Get tiered prices
    $adult_tiers = get_field('adult_price_tiers', $product->get_id());
    $child_tiers = get_field('child_price_tiers', $product->get_id());
    
    // Calculate additional price for adults based on tiers
    if ($adults >= 2 && $adult_tiers) {
        foreach ($adult_tiers as $tier) {
            if (isset($tier['number_of_adults']) && $adults == intval($tier['number_of_adults'])) {
                if (isset($tier['additional_price']) && is_numeric($tier['additional_price'])) {
                    $additional_adult_cost = floatval($tier['additional_price']);
                    break;
                }
            }
        }
    }

    // Calculate additional price for children based on tiers
    if ($children >= 1 && $child_tiers) {
        foreach ($child_tiers as $tier) {
            if (isset($tier['number_of_children']) && $children == intval($tier['number_of_children'])) {
                if (isset($tier['additional_price']) && is_numeric($tier['additional_price'])) {
                    $additional_child_cost = floatval($tier['additional_price']);
                    break;
                }
            }
        }
    }

    // Get departure location price
    $departure_location = isset($cart_item['bwp_departure_location']) ? $cart_item['bwp_departure_location'] : '';
    if ($departure_location) {
        if ($departure_location === 'phuket') {
            $additional_departure_cost = floatval(get_field('phuket_price', $product->get_id()));
        } elseif ($departure_location === 'khaolak') {
            $additional_departure_cost = floatval(get_field('khaolak_price', $product->get_id()));
        }
    }
    
    // Store base price for reference
    $cart->cart_contents[$cart_item_key]['base_price'] = $base_price;

    // Calculate total price for this item
    $total_price += $additional_adult_cost + $additional_child_cost + $additional_departure_cost;

    // Update cart item data
    $cart->cart_contents[$cart_item_key]['line_total'] = $total_price;
    $cart->cart_contents[$cart_item_key]['line_subtotal'] = $total_price;
    $cart->cart_contents[$cart_item_key]['total_price'] = $total_price;
    $cart->cart_contents[$cart_item_key]['line_total'] = $total_price; // เก็บราคาเต็มไว้ใน line_total ด้วย เพราะจะคำนวณส่วนลดรวมทีเดียวตอนแสดงผล
    $cart->cart_contents[$cart_item_key]['data']->set_price($total_price);
    
    // Save cart data
    $cart->set_session();
    
    // Get order summary data
    $subtotal = 0;
    $total = 0;
    $discount_total = 0;
    
    // Calculate totals from cart items
    foreach ($cart->get_cart() as $item) {
        $subtotal += $item['line_subtotal'];
    }
    
    // ดึงข้อมูลคูปองและคำนวณส่วนลด
    $coupons = $cart->get_applied_coupons();
    if (!empty($coupons)) {
        $coupon_code = $coupons[0];
        $coupon = new WC_Coupon($coupon_code);
        if ($coupon && $coupon->is_valid() && $coupon->get_discount_type() === 'percent') {
            $discount_total = ($subtotal * $coupon->get_amount()) / 100;
            $total = $subtotal - $discount_total;
        }
    } else {
        $total = $subtotal;
    }
    
    // สร้าง response
    $cart_totals_response = array(
        'subtotal' => wc_price($subtotal),
        'total' => wc_price($total)
    );

    if ($discount_total > 0) {
        $cart_totals_response['discount'] = wc_price($discount_total);
    }

    wp_send_json_success(array(
        'new_value' => $new_value,
        'total_price' => wc_price($total_price),
        'cart_totals' => $cart_totals_response
    ));
}

add_action('wp_ajax_bwp_update_guest_quantity', 'bwp_update_guest_quantity');
add_action('wp_ajax_nopriv_bwp_update_guest_quantity', 'bwp_update_guest_quantity');

// AJAX handler for applying coupons
function bwp_apply_coupon() {
    check_ajax_referer('bwp_nonce', 'nonce');
    $coupon_code = sanitize_text_field($_POST['coupon_code']);
    if (empty($coupon_code)) {
        wp_send_json_error(array('message' => 'Please enter a coupon code'));
    }
    
    $cart = WC()->cart;
    if ($cart->has_discount()) {
        wp_send_json_error(array('message' => 'A coupon is already applied. Please remove it first.'));
    }

    // Get WooCommerce cart
    $cart = WC()->cart;

    // Remove all existing coupons first
    $cart->remove_coupons();

    // Try to apply the coupon
    $result = $cart->apply_coupon($coupon_code);

    // Get WooCommerce cart
    $cart = WC()->cart;
    $cart->remove_coupons();
    $result = $cart->apply_coupon($coupon_code);

    if ($result) {
        // Calculate totals from cart items
        $subtotal = 0;
        foreach ($cart->get_cart() as $item) {
            $subtotal += $item['line_subtotal'];
        }

        // Get coupon object
        $coupons = $cart->get_applied_coupons();
        $discount = 0;
        if (!empty($coupons)) {
            $coupon = new WC_Coupon($coupons[0]);
            if ($coupon && $coupon->is_valid() && $coupon->get_discount_type() === 'percent') {
                $discount = round(($subtotal * $coupon->get_amount()) / 100, 2);
            }
        }

        // Calculate final total
        $total = round($subtotal - $discount, 2);
        
        // Update cart totals
        $cart->calculate_totals();

        wp_send_json_success(array(
            'message' => 'Coupon applied successfully!',
            'discount' => $discount,
            'discount_formatted' => wc_price($discount),
            'total' => $total,
            'total_formatted' => wc_price($total)
        ));
    } else {
        wp_send_json_error(array('message' => 'Invalid coupon code. Please try again.'));
    }
}

add_action('wp_ajax_bwp_apply_coupon', 'bwp_apply_coupon');
add_action('wp_ajax_nopriv_bwp_apply_coupon', 'bwp_apply_coupon');

function bwp_remove_coupon() {
    check_ajax_referer('bwp_nonce', 'nonce');
    $coupon_code = sanitize_text_field($_POST['coupon_code']);

    if (empty($coupon_code)) {
        wp_send_json_error(array('message' => 'Invalid coupon code'));
    }

    $cart = WC()->cart;

    // Remove the coupon first
    $cart->remove_coupon($coupon_code);

    // Calculate totals after removing coupon
    $subtotal = 0;
    foreach ($cart->get_cart() as $cart_item_key => $item) {
        // Use base_price and recalculate total with additional costs
        $base_price = isset($item['base_price']) ? $item['base_price'] : $item['line_subtotal'];
        $adult_price = isset($item['adult_price']) ? $item['adult_price'] * $item['adult_quantity'] : 0;
        $child_price = isset($item['child_price']) ? $item['child_price'] * $item['child_quantity'] : 0;
        $departure_price = isset($item['departure_price']) ? $item['departure_price'] : 0;
        
        $total_price = $base_price + $adult_price + $child_price + $departure_price;
        
        // Update cart item with recalculated price
        $cart->cart_contents[$cart_item_key]['line_total'] = $total_price;
        $cart->cart_contents[$cart_item_key]['line_subtotal'] = $total_price;
        $cart->cart_contents[$cart_item_key]['total_price'] = $total_price;
        $cart->cart_contents[$cart_item_key]['data']->set_price($total_price);
    }
    
    // Save cart data
    $cart->set_session();

    // Calculate totals using WooCommerce
    $cart->calculate_totals();

    // Get updated totals after calculation
    $subtotal = 0;
    $total = 0;
    foreach ($cart->get_cart() as $item) {
        $subtotal += $item['line_subtotal'];
        $total += $item['line_total'];
    }

    wp_send_json_success(array(
        'message' => 'Coupon removed successfully!',
        'subtotal' => wc_price($subtotal),
        'total' => wc_price($total)
    ));
}

add_action('wp_ajax_bwp_remove_coupon', 'bwp_remove_coupon');
add_action('wp_ajax_nopriv_bwp_remove_coupon', 'bwp_remove_coupon');
