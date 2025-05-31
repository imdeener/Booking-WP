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

// Your Booking Section
function bwp_your_booking_shortcode() {
    ob_start();
    ?>
    <div class="bwp-booking-section">
        <div class="section-header">
            <h2>Your Booking</h2>
            <a href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>">All Tours</a>
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
                            <?php echo $product->get_image('thumbnail'); ?>
                        </div>
                        <div class="booking-details">
                            <h3><?php echo $product->get_name(); ?></h3>
                            <div class="booking-meta">
                                <?php if ($booking_date): ?>
                                    <div class="date"><i class="fas fa-calendar"></i> <?php echo esc_html($booking_date); ?></div>
                                <?php endif; ?>
                                
                                <div class="guests">
                                    <div class="adults">
                                        <i class="fas fa-user"></i> <?php echo $adults; ?> Adults
                                    </div>
                                    <?php if ($children > 0): ?>
                                    <div class="children">
                                        <i class="fas fa-child"></i> <?php echo $children; ?> Children
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="booking-price">
                                <div class="total-price">
                                    <?php echo wc_price($total_price); ?>
                                </div>
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
    // Get current user data
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    
    // Get saved billing data
    $billing_first_name = get_user_meta($user_id, 'billing_first_name', true);
    $billing_last_name = get_user_meta($user_id, 'billing_last_name', true);
    $billing_thai_id = get_user_meta($user_id, 'billing_thai_id', true);
    $billing_email = get_user_meta($user_id, 'billing_email', true);
    $billing_phone = get_user_meta($user_id, 'billing_phone', true);
    ?>
    <div class="bwp-customer-information">
    <div class="section-header"><h2>Your Information</h2></div>
        <form class="bwp-customer-form">
            <?php wp_nonce_field('bwp_save_customer_info', 'bwp_nonce'); ?>
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" 
                           value="<?php echo esc_attr($billing_first_name); ?>" required>
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" 
                           value="<?php echo esc_attr($billing_last_name); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="thai_id">Thai ID or Passport Number *</label>
                <input type="text" id="thai_id" name="thai_id" 
                       value="<?php echo esc_attr($billing_thai_id); ?>" required>
            </div>
            
            <div class="form-row">
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" 
                       value="<?php echo esc_attr($billing_email); ?>" required>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number *</label>
                <input type="tel" id="phone" name="phone" 
                       value="<?php echo esc_attr($billing_phone); ?>" required>
            </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="hotel_name">Hotel Name *</label>
                    <input type="text" id="hotel_name" name="hotel_name" required>
                </div>
                <div class="form-group">
                    <label for="room">Room *</label>
                    <input type="text" id="room" name="room" required>
                </div>
            </div>

            <div class="form-group">
                <label for="special_requests">Special Requests</label>
                <textarea id="special_requests" name="special_requests"></textarea>
            </div>

            <?php wp_nonce_field('bwp_customer_info_nonce', 'bwp_nonce'); ?>
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
        <div class="section-header"><h2>Order Summary</h2></div>

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
                        <h4><?php echo $product->get_name(); ?></h4>
                        <div class="item-meta">
                            <?php
                            if (isset($cart_item['booking_date'])) {
                                echo '<span class="date">' . esc_html($cart_item['booking_date']) . '</span>';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="item-price">
                        <div class="price-breakdown">
                            <?php
                            $base_price = floatval($product->get_price('edit'));
                            $adults = isset($cart_item['bwp_adults']) ? intval($cart_item['bwp_adults']) : 1;
                            $children = isset($cart_item['bwp_children']) ? intval($cart_item['bwp_children']) : 0;
                            $departure_location = isset($cart_item['bwp_departure_location']) ? $cart_item['bwp_departure_location'] : '';
                            
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
                            
                            // Display only total price
                            ?>
                            <div class="total-price"><?php echo wc_price($base_price + $additional_adult_cost + $additional_child_cost + $departure_cost); ?></div>
                        </div>
                    </div>
                </div>
                <?php
            }
        }
        ?>

        <div class="order-totals">
            <?php
            $cart_total = 0;
            foreach ($cart->get_cart() as $cart_item) {
                $product = $cart_item['data'];
                $base_price = floatval($product->get_price('edit'));
                $adults = isset($cart_item['bwp_adults']) ? intval($cart_item['bwp_adults']) : 1;
                $children = isset($cart_item['bwp_children']) ? intval($cart_item['bwp_children']) : 0;
                $departure_location = isset($cart_item['bwp_departure_location']) ? $cart_item['bwp_departure_location'] : '';
                
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
                
                $cart_total += $base_price + $additional_adult_cost + $additional_child_cost + $departure_cost;
            }
            ?>
            <div class="subtotal">
                <span>Subtotal</span>
                <span class="amount"><?php echo wc_price($cart_total); ?></span>
            </div>
            <?php if (WC()->cart->get_discount_total()) : ?>
            <div class="discount">
                <span>Discount</span>
                <span class="amount">-<?php echo wc_price(WC()->cart->get_discount_total()); ?></span>
            </div>
            <?php endif; ?>
            <div class="total">
                <span>Total</span>
                <span class="amount"><?php echo wc_price($cart_total - WC()->cart->get_discount_total()); ?></span>
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
    wp_enqueue_script('bwp-cart-scripts', plugins_url('js/bwp-cart.js', __FILE__), array('jquery'), '1.0.0', true);
    
    // Localize the script with new data
    wp_localize_script('bwp-cart-scripts', 'bwp_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('bwp_customer_info_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'bwp_cart_enqueue_styles');

// AJAX handler for saving customer information
function bwp_save_customer_info() {
    // Verify nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bwp_customer_info_nonce')) {
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

    // Get current user or create a new customer
    $user_id = get_current_user_id();
    if (!$user_id) {
        // Create new customer if email doesn't exist
        $user_email = $customer_data['email'];
        if (!email_exists($user_email)) {
            $user_id = wc_create_new_customer(
                $user_email,
                $user_email, // username same as email
                wp_generate_password() // random password
            );
        } else {
            $user = get_user_by('email', $user_email);
            $user_id = $user->ID;
        }
    }

    if (is_wp_error($user_id)) {
        wp_send_json_error('Could not create/update customer');
        return;
    }

    // Update WooCommerce billing details
    update_user_meta($user_id, 'billing_first_name', $customer_data['first_name']);
    update_user_meta($user_id, 'billing_last_name', $customer_data['last_name']);
    update_user_meta($user_id, 'billing_email', $customer_data['email']);
    update_user_meta($user_id, 'billing_phone', $customer_data['phone']);
    
    // Save additional fields as both user meta and billing meta
    update_user_meta($user_id, 'billing_thai_id', $customer_data['thai_id']);
    update_user_meta($user_id, 'billing_hotel_name', $customer_data['hotel_name']);
    update_user_meta($user_id, 'billing_room', $customer_data['room']);
    
    // Also save as custom fields for compatibility
    update_user_meta($user_id, 'thai_id', $customer_data['thai_id']);
    update_user_meta($user_id, 'hotel_name', $customer_data['hotel_name']);
    update_user_meta($user_id, 'room_number', $customer_data['room']);


    // Store data in WooCommerce session for order creation
    WC()->session->set('bwp_customer_data', $customer_data);

    // Redirect to WooCommerce checkout page
    $next_step_url = wc_get_checkout_url();
    
    wp_send_json_success(array(
        'message' => 'Customer information saved successfully',
        'redirect' => $next_step_url
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
    $custom_fields = array(
        'billing_thai_id' => array(
            'type' => 'text',
            'label' => 'Thai ID/Passport Number',
            'required' => true,
            'class' => array('form-row-wide'),
            'clear' => true,
            'priority' => 25
        ),
        'billing_hotel_name' => array(
            'type' => 'text',
            'label' => 'Hotel Name',
            'required' => true,
            'class' => array('form-row-wide'),
            'clear' => true,
            'priority' => 35
        ),
        'billing_room' => array(
            'type' => 'text',
            'label' => 'Room Number',
            'required' => true,
            'class' => array('form-row-wide'),
            'clear' => true,
            'priority' => 36
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
