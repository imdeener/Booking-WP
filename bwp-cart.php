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
    <div class="bwp-booking-section card--cart">
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
 <?php echo esc_html($booking_date); ?></div>
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
                                        <div class="quantity-controls">
                                            <button type="button" class="quantity-btn minus" data-type="adults" data-item-key="<?php echo esc_attr($cart_item_key); ?>" data-min="1">&minus;</button>
                                            <span class="quantity"><?php echo $adults; ?></span>
                                            <button type="button" class="quantity-btn plus" data-type="adults" data-item-key="<?php echo esc_attr($cart_item_key); ?>" data-max="<?php echo esc_attr($max_adults); ?>">&plus;</button>
                                        </div>
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
                                        <div class="quantity-controls">
                                            <button type="button" class="quantity-btn minus" data-type="children" data-item-key="<?php echo esc_attr($cart_item_key); ?>" data-min="0">&minus;</button>
                                            <span class="quantity"><?php echo $children; ?></span>
                                            <button type="button" class="quantity-btn plus" data-type="children" data-item-key="<?php echo esc_attr($cart_item_key); ?>" data-max="<?php echo esc_attr($max_children); ?>">&plus;</button>
                                        </div>
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
    <div class="bwp-customer-information card--cart">
    <div class="section-header">
        <h2>Your Information</h2>
        <?php if (!is_user_logged_in()): ?>
            <a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>" class="btn btn--primary btn--xs btn--quaternary">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12ZM12 14C9.33 14 4 15.34 4 18V19C4 19.55 4.45 20 5 20H19C19.55 20 20 19.55 20 19V18C20 15.34 14.67 14 12 14Z" fill="currentColor"/>
                </svg>
                Sign In to Your Account
            </a>
        <?php endif; ?>
    </div>
    <form class="bwp-customer-form">
            <?php wp_nonce_field('bwp_save_customer_info', 'bwp_nonce'); ?>
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">
                    <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <g clip-path="url(#clip0_2039_36846)">
                    <path d="M13.9993 14.0003C16.5777 14.0003 18.666 11.912 18.666 9.33366C18.666 6.75533 16.5777 4.66699 13.9993 4.66699C11.421 4.66699 9.33268 6.75533 9.33268 9.33366C9.33268 11.912 11.421 14.0003 13.9993 14.0003ZM13.9993 16.3337C10.8843 16.3337 4.66602 17.897 4.66602 21.0003V22.167C4.66602 22.8087 5.19102 23.3337 5.83268 23.3337H22.166C22.8077 23.3337 23.3327 22.8087 23.3327 22.167V21.0003C23.3327 17.897 17.1143 16.3337 13.9993 16.3337Z" fill="currentColor"/>
                    </g>
                    <defs>
                    <clipPath id="clip0_2039_36846">
                    <rect width="28" height="28" fill="white"/>
                    </clipPath>
                    </defs>
                    </svg>
                    First Name *</label>
                    <input type="text" id="first_name" name="first_name" 
                           value="<?php echo esc_attr($billing_first_name); ?>" 
                           pattern="[A-Za-z ]{2,}" 
                           title="Please enter at least 2 letters. Numbers and special characters are not allowed."
                           placeholder="Enter your first name"
                           required>
                </div>
                <div class="form-group">
                    <label for="last_name"><svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <g clip-path="url(#clip0_2039_36846)">
                    <path d="M13.9993 14.0003C16.5777 14.0003 18.666 11.912 18.666 9.33366C18.666 6.75533 16.5777 4.66699 13.9993 4.66699C11.421 4.66699 9.33268 6.75533 9.33268 9.33366C9.33268 11.912 11.421 14.0003 13.9993 14.0003ZM13.9993 16.3337C10.8843 16.3337 4.66602 17.897 4.66602 21.0003V22.167C4.66602 22.8087 5.19102 23.3337 5.83268 23.3337H22.166C22.8077 23.3337 23.3327 22.8087 23.3327 22.167V21.0003C23.3327 17.897 17.1143 16.3337 13.9993 16.3337Z" fill="currentColor"/>
                    </g>
                    <defs>
                    <clipPath id="clip0_2039_36846">
                    <rect width="28" height="28" fill="white"/>
                    </clipPath>
                    </defs>
                    </svg>Last Name *</label>
                    <input type="text" id="last_name" name="last_name" 
                           value="<?php echo esc_attr($billing_last_name); ?>" 
                           pattern="[A-Za-z ]{2,}" 
                           title="Please enter at least 2 letters. Numbers and special characters are not allowed."
                           placeholder="Enter your last name"
                           required>
                </div>
            </div>

            <div class="form-group">
                <label for="thai_id"><svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
<g clip-path="url(#clip0_2039_36853)">
<path d="M22.1667 3.5H5.83333C4.55 3.5 3.5 4.55 3.5 5.83333V22.1667C3.5 23.45 4.55 24.5 5.83333 24.5H22.1667C23.45 24.5 24.5 23.45 24.5 22.1667V5.83333C24.5 4.55 23.45 3.5 22.1667 3.5ZM14 7C16.2517 7 18.0833 8.83167 18.0833 11.0833C18.0833 13.335 16.2517 15.1667 14 15.1667C11.7483 15.1667 9.91667 13.335 9.91667 11.0833C9.91667 8.83167 11.7483 7 14 7ZM22.1667 22.1667H5.83333V21.8983C5.83333 21.175 6.16 20.4983 6.72 20.055C8.715 18.4567 11.2467 17.5 14 17.5C16.7533 17.5 19.285 18.4567 21.28 20.055C21.84 20.4983 22.1667 21.1867 22.1667 21.8983V22.1667Z" fill="currentColor"/>
</g>
<defs>
<clipPath id="clip0_2039_36853">
<rect width="28" height="28" fill="white"/>
</clipPath>
</defs>
</svg>Thai ID or Passport Number *</label>
                <input type="text" id="thai_id" name="thai_id" 
                       value="<?php echo esc_attr($billing_thai_id); ?>" 
                       pattern="[0-9A-Za-z]{8,}" 
                       title="Please enter a valid Thai ID (13 digits) or Passport number (at least 8 characters)"
                       placeholder="Enter Thai ID or Passport number"
                       required>
            </div>
            
            <div class="form-row">
            <div class="form-group">
                <label for="email"><svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
<g clip-path="url(#clip0_2039_36864)">
<path d="M23.334 4.66699H4.66732C3.38398 4.66699 2.34565 5.71699 2.34565 7.00033L2.33398 21.0003C2.33398 22.2837 3.38398 23.3337 4.66732 23.3337H23.334C24.6173 23.3337 25.6673 22.2837 25.6673 21.0003V7.00033C25.6673 5.71699 24.6173 4.66699 23.334 4.66699ZM22.8673 9.62533L14.619 14.782C14.2457 15.0153 13.7557 15.0153 13.3823 14.782L5.13398 9.62533C4.84232 9.43866 4.66732 9.12366 4.66732 8.78533C4.66732 8.00366 5.51898 7.53699 6.18398 7.94533L14.0007 12.8337L21.8173 7.94533C22.4823 7.53699 23.334 8.00366 23.334 8.78533C23.334 9.12366 23.159 9.43866 22.8673 9.62533Z" fill="currentColor"/>
</g>
<defs>
<clipPath id="clip0_2039_36864">
<rect width="28" height="28" fill="white"/>
</clipPath>
</defs>
</svg>Email Address *</label>
                <input type="email" id="email" name="email" 
                       value="<?php echo esc_attr($billing_email); ?>" 
                       pattern="[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}" 
                       title="Please enter a valid email address"
                       placeholder="Enter your email address"
                       required>
            </div>

            <div class="form-group">
                <label for="phone"><svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
<g clip-path="url(#clip0_2039_36872)">
<path d="M22.4355 17.8034L19.4722 17.4651C18.7605 17.3834 18.0605 17.6284 17.5589 18.1301L15.4122 20.2767C12.1105 18.5967 9.40387 15.9017 7.72387 12.5884L9.8822 10.4301C10.3839 9.92839 10.6289 9.22839 10.5472 8.51672L10.2089 5.57672C10.0689 4.39839 9.0772 3.51172 7.8872 3.51172H5.86887C4.55054 3.51172 3.45387 4.60839 3.53554 5.92672C4.15387 15.8901 12.1222 23.8467 22.0739 24.4651C23.3922 24.5467 24.4889 23.4501 24.4889 22.1317V20.1134C24.5005 18.9351 23.6139 17.9434 22.4355 17.8034Z" fill="currentColor"/>
</g>
<defs>
<clipPath id="clip0_2039_36872">
<rect width="28" height="28" fill="white"/>
</clipPath>
</defs>
</svg>Phone Number *</label>
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
                    <label for="hotel_name">
<svg width="24" height="28" viewBox="0 0 24 28" fill="none" xmlns="http://www.w3.org/2000/svg">
<g clip-path="url(#clip0_2116_2421)">
<path d="M7 15.1663C8.66 15.1663 10 13.603 10 11.6663C10 9.72967 8.66 8.16634 7 8.16634C5.34 8.16634 4 9.72967 4 11.6663C4 13.603 5.34 15.1663 7 15.1663ZM19 8.16634H11V16.333H3V5.83301H1V23.333H3V19.833H21V23.333H23V12.833C23 10.2547 21.21 8.16634 19 8.16634Z" fill="currentColor"/>
</g>
<defs>
<clipPath id="clip0_2116_2421">
<rect width="24" height="28" fill="white"/>
</clipPath>
</defs>
</svg>
Hotel Name *</label>
                    <input type="text" id="hotel_name" name="hotel_name" 
                           value="<?php echo esc_attr($billing_hotel_name); ?>" 
                           pattern=".{3,}" 
                           title="Please enter hotel name (at least 3 characters)"
                           placeholder="Enter hotel name"
                           required>
                </div>
                <div class="form-group">
                    <label for="room">
<svg width="29" height="28" viewBox="0 0 29 28" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M16.8333 7V24.5H4V22.1667H6.33333V3.5H16.8333V4.66667H22.6667V22.1667H25V24.5H20.3333V7H16.8333ZM12.1667 12.8333V15.1667H14.5V12.8333H12.1667Z" fill="currentColor"/>
</svg>
Room *</label>
                    <input type="text" id="room" name="room" 
                           value="<?php echo esc_attr($billing_room); ?>" 
                           pattern="[A-Za-z0-9 \-]+" 
                           title="Please enter room number/name (letters, numbers, spaces and hyphens only)"
                           placeholder="Enter room number"
                           required>
                </div>
            </div>

            <div class="form-group">
                <label for="special_requests">
<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M20.4167 21.5833V23.9167C20.4167 24.0722 20.475 24.2083 20.5917 24.325C20.7083 24.4417 20.8444 24.5 21 24.5C21.1556 24.5 21.2917 24.4417 21.4083 24.325C21.525 24.2083 21.5833 24.0722 21.5833 23.9167V21.5833H23.9167C24.0722 21.5833 24.2083 21.525 24.325 21.4083C24.4417 21.2917 24.5 21.1556 24.5 21C24.5 20.8444 24.4417 20.7083 24.325 20.5917C24.2083 20.475 24.0722 20.4167 23.9167 20.4167H21.5833V18.0833C21.5833 17.9278 21.525 17.7917 21.4083 17.675C21.2917 17.5583 21.1556 17.5 21 17.5C20.8444 17.5 20.7083 17.5583 20.5917 17.675C20.475 17.7917 20.4167 17.9278 20.4167 18.0833V20.4167H18.0833C17.9278 20.4167 17.7917 20.475 17.675 20.5917C17.5583 20.7083 17.5 20.8444 17.5 21C17.5 21.1556 17.5583 21.2917 17.675 21.4083C17.7917 21.525 17.9278 21.5833 18.0833 21.5833H20.4167ZM21 26.8333C19.3861 26.8333 18.0106 26.2648 16.8735 25.1277C15.7356 23.9898 15.1667 22.6139 15.1667 21C15.1667 19.3861 15.7356 18.0102 16.8735 16.8723C18.0106 15.7352 19.3861 15.1667 21 15.1667C22.6139 15.1667 23.9898 15.7352 25.1277 16.8723C26.2648 18.0102 26.8333 19.3861 26.8333 21C26.8333 22.6139 26.2648 23.9898 25.1277 25.1277C23.9898 26.2648 22.6139 26.8333 21 26.8333ZM9.33333 10.5H18.6667C18.9972 10.5 19.2741 10.388 19.4973 10.164C19.7213 9.94078 19.8333 9.66389 19.8333 9.33333C19.8333 9.00278 19.7213 8.7255 19.4973 8.5015C19.2741 8.27828 18.9972 8.16667 18.6667 8.16667H9.33333C9.00278 8.16667 8.7255 8.27828 8.5015 8.5015C8.27828 8.7255 8.16667 9.00278 8.16667 9.33333C8.16667 9.66389 8.27828 9.94078 8.5015 10.164C8.7255 10.388 9.00278 10.5 9.33333 10.5ZM13.6208 24.5H5.83333C5.19167 24.5 4.64217 24.2717 4.18483 23.8152C3.72828 23.3578 3.5 22.8083 3.5 22.1667V5.83333C3.5 5.19167 3.72828 4.64217 4.18483 4.18483C4.64217 3.72828 5.19167 3.5 5.83333 3.5H22.1667C22.8083 3.5 23.3578 3.72828 23.8152 4.18483C24.2717 4.64217 24.5 5.19167 24.5 5.83333V13.65C23.9361 13.3778 23.3676 13.1736 22.7943 13.0375C22.2203 12.9014 21.6222 12.8333 21 12.8333C20.7861 12.8333 20.587 12.838 20.4027 12.8473C20.2176 12.8574 20.0278 12.8819 19.8333 12.9208C19.6583 12.8819 19.4639 12.8574 19.25 12.8473C19.0361 12.838 18.8417 12.8333 18.6667 12.8333H9.33333C9.00278 12.8333 8.7255 12.9449 8.5015 13.1682C8.27828 13.3922 8.16667 13.6694 8.16667 14C8.16667 14.3306 8.27828 14.6074 8.5015 14.8307C8.7255 15.0547 9.00278 15.1667 9.33333 15.1667H15.3125C14.9625 15.4972 14.6463 15.8569 14.364 16.2458C14.0824 16.6347 13.8347 17.0528 13.6208 17.5H9.33333C9.00278 17.5 8.7255 17.6116 8.5015 17.8348C8.27828 18.0588 8.16667 18.3361 8.16667 18.6667C8.16667 18.9972 8.27828 19.2741 8.5015 19.4973C8.7255 19.7213 9.00278 19.8333 9.33333 19.8333H12.9208C12.8819 20.0278 12.8574 20.2176 12.8473 20.4027C12.838 20.587 12.8333 20.7861 12.8333 21C12.8333 21.6417 12.8917 22.2398 13.0083 22.7943C13.125 23.3481 13.3292 23.9167 13.6208 24.5Z" fill="currentColor"/>
</svg>
Special Requests</label>
                <textarea id="special_requests" name="special_requests" 
                           maxlength="500"
                           placeholder="Enter any special requests or requirements"><?php echo esc_textarea($billing_special_requests); ?></textarea>
                <small class="form-text text-muted">Maximum 500 characters</small>
            </div>

            <?php wp_nonce_field('bwp_save_customer_info', 'bwp_nonce'); ?>
            <button type="button" id="continue-btn" class="submit-button btn btn--primary btn--s">Continue</button>
        </form>
    </div>

    <!-- Privacy Policy Modal -->
    <div id="privacy-modal" class="privacy-modal" style="display: none;">
        <div class="privacy-modal-content">
            <div class="privacy-modal-header">
                <h3>
                    <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9.99935 36.6663C9.08268 36.6663 8.29824 36.3402 7.64602 35.688C6.99268 35.0347 6.66602 34.2497 6.66602 33.333V6.66634C6.66602 5.74967 6.99268 4.96467 7.64602 4.31134C8.29824 3.65912 9.08268 3.33301 9.99935 3.33301H21.9577C22.4021 3.33301 22.826 3.41634 23.2293 3.58301C23.6316 3.74967 23.9855 3.98579 24.291 4.29134L32.3743 12.3747C32.6799 12.6802 32.916 13.0341 33.0827 13.4363C33.2493 13.8397 33.3327 14.2636 33.3327 14.708V33.333C33.3327 34.2497 33.0066 35.0347 32.3543 35.688C31.701 36.3402 30.916 36.6663 29.9993 36.6663H9.99935ZM21.666 13.333C21.666 13.8052 21.826 14.2008 22.146 14.5197C22.4649 14.8397 22.8605 14.9997 23.3327 14.9997H29.9993L21.666 6.66634V13.333Z" fill="var(--be-03)"/>
                    </svg>
                    <?php 
                    $privacy_page = get_post(3);
                    echo $privacy_page ? esc_html($privacy_page->post_title) : 'Privacy Policy';
                    ?>
                </h3>
                <span class="privacy-modal-close">&times;</span>
            </div>
            <div class="privacy-modal-body">
                <?php
                if ($privacy_page) {
                    echo apply_filters('the_content', $privacy_page->post_content);
                } else {
                    echo '<p>Privacy policy content not found.</p>';
                }
                ?>
            </div>
            <div class="privacy-modal-footer">
                <div class="privacy-checkbox">
                    <input type="checkbox" id="accept-privacy" required>
                    <label for="accept-privacy">I have read and agree to the Terms of Use and Conditions and Privacy Policy.</label>
                </div>
                <div class="privacy-buttons">
                    <button type="button" class="btn-cancel" id="privacy-cancel">Cancel</button>
                    <button type="button" class="btn-accept" id="privacy-accept" disabled>Accept & Continue</button>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('msc_customer_information_form', 'bwp_customer_information_shortcode');

// Order Summary Section
function bwp_order_summary_shortcode() {
    ob_start();
    ?>
    <div class="bwp-order-summary card--cart">
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
 <?php echo esc_html($cart_item['bwp_start_date']); ?></div>
                            <?php endif; ?>
                            
                            <div class="guests" data-item-key="<?php echo esc_attr($cart_item_key); ?>">
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
                                    <span class="guest-count" data-item-key="<?php echo esc_attr($cart_item_key); ?>" data-type="adults"><?php echo isset($cart_item['bwp_adults']) ? $cart_item['bwp_adults'] : 0; ?></span>
                                </div>
                                <?php if (isset($cart_item['bwp_children']) && intval($cart_item['bwp_children']) > 0): ?>
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
                                    <span class="guest-count" data-item-key="<?php echo esc_attr($cart_item_key); ?>" data-type="children"><?php echo $cart_item['bwp_children']; ?></span>
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
add_shortcode('msc_order_totals_summary', 'bwp_order_summary_shortcode');

// Enqueue necessary styles
function bwp_cart_enqueue_styles() {
    wp_enqueue_style('bwp-cart-styles', plugins_url('css/bwp-cart.css', __FILE__));
    wp_enqueue_script('bwp-cart-script', plugins_url('js/bwp-cart.js', __FILE__), array('jquery'), '', true);
    wp_enqueue_script('bwp-privacy-modal', plugins_url('js/bwp-privacy-modal.js', __FILE__), array('jquery'), '', true);
    
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
