<?php

// Remove old functions for adding/saving custom fields as ACF now handles this.
// function bwp_add_product_custom_fields() { ... }
// remove_action('woocommerce_product_options_pricing', 'bwp_add_product_custom_fields');
// function bwp_save_product_custom_fields($post_id) { ... }
// remove_action('woocommerce_process_product_meta', 'bwp_save_product_custom_fields');

/**
 * Display fields on the product page
 */
function bwp_display_booking_fields()
{
    global $product;

    if ($product && ($product->is_type('simple') || $product->is_type('variable'))) { // Ensure it's a bookable product type if needed

        // Prepare data for JS and for dropdown options
        $js_tiered_prices = array(
            'adults' => array(),
            'children' => array(),
            // 'departures' will be populated if we decide to add it to $js_tiered_prices directly
        );
        $acf_adult_numbers = array();
        $acf_child_numbers = array();

        // For Departure Locations - New structure with fixed fields
        // Get dynamic labels from ACF field labels
        $phuket_display_label = 'Phuket'; // Default fallback
        $khaolak_display_label = 'Khaolak'; // Default fallback

        if (function_exists('get_field_object')) {
            // Note: $product->get_id() is available in this function context
            $departure_group_field_object = get_field_object('departure', $product->get_id());
            if ($departure_group_field_object && isset($departure_group_field_object['sub_fields'])) {
                foreach ($departure_group_field_object['sub_fields'] as $sub_field) {
                    if ($sub_field['name'] === 'phuket_additional_price' && !empty($sub_field['label'])) {
                        $phuket_display_label = $sub_field['label'];
                    } elseif ($sub_field['name'] === 'khaolak_additional_price' && !empty($sub_field['label'])) {
                        $khaolak_display_label = $sub_field['label'];
                    }
                }
            }
        }

        $departure_options_for_frontend = array(
            'phuket'  => esc_html($phuket_display_label), // Use 'phuket' as the value/key
            'khaolak' => esc_html($khaolak_display_label), // Use 'khaolak' as the value/key
        );
        $default_departure_location = 'phuket'; // As per user request

        $phuket_price = 0;
        $khaolak_price = 0; // These are the final prices to be used by JS

        if (function_exists('get_field')) {
            $departure_group = get_field('departure', $product->get_id()); // Get the parent group

            if ($departure_group) {
                $phuket_price_val = isset($departure_group['phuket_additional_price']) ? $departure_group['phuket_additional_price'] : null;
                $khaolak_price_val = isset($departure_group['khaolak_additional_price']) ? $departure_group['khaolak_additional_price'] : null;

                $phuket_price = is_numeric($phuket_price_val) ? floatval($phuket_price_val) : 0;
                $khaolak_price = is_numeric($khaolak_price_val) ? floatval($khaolak_price_val) : 0;
            }
            // If $departure_group is not found, $phuket_price and $khaolak_price remain 0.

            // Get Adult Price Tiers from ACF (for adults dropdown and JS)
            $adult_tiers_data = get_field('adult_price_tiers', $product->get_id());
            if ($adult_tiers_data) {
                foreach ($adult_tiers_data as $tier) {
                    $num_adults = isset($tier['number_of_adults']) ? intval($tier['number_of_adults']) : 0;
                    $additional_price = isset($tier['additional_price']) ? floatval($tier['additional_price']) : 0;
                    if ($num_adults >= 2) { // ACF field for adults starts from 2
                        $js_tiered_prices['adults'][$num_adults] = $additional_price;
                        $acf_adult_numbers[] = $num_adults;
                    }
                }
            }

            $child_tiers_data = get_field('child_price_tiers', $product->get_id());
            if ($child_tiers_data) {
                foreach ($child_tiers_data as $tier) {
                    $num_children = isset($tier['number_of_children']) ? intval($tier['number_of_children']) : 0;
                    $additional_price = isset($tier['additional_price']) ? floatval($tier['additional_price']) : 0;
                    if ($num_children >= 1) { // ACF field for children starts from 1
                        $js_tiered_prices['children'][$num_children] = $additional_price;
                        $acf_child_numbers[] = $num_children;
                    }
                }
            }
            // Data for JS, using the new fixed field prices
            $departure_prices_for_js = array(
                'phuket'  => $phuket_price,
                'khaolak' => $khaolak_price,
            );
        } else {
            // Fallback if get_field doesn't exist, though unlikely if ACF is active
            $departure_prices_for_js = array(
                'phuket'  => 0,
                'khaolak' => 0,
            );
        }


        // --- Generate Dropdown Options ---
        // Adults
        $adult_options = array();
        $unique_adult_numbers = array_unique(array_merge(array(1), $acf_adult_numbers)); // Always include 1 adult
        sort($unique_adult_numbers, SORT_NUMERIC);
        foreach ($unique_adult_numbers as $num) {
            if ($num < 1) continue;
            $adult_options[$num] = sprintf(_n('%s Adult', '%s Adults', $num, 'woocommerce'), $num);
        }
        if (empty($adult_options)) { // Fallback if no tiers and 1 wasn't added (should not happen)
            $adult_options[1] = sprintf(_n('%s Adult', '%s Adults', 1, 'woocommerce'), 1);
        }

        // Children
        $child_options = array();
        $unique_child_numbers = array_unique(array_merge(array(0), $acf_child_numbers)); // Always include 0 children
        sort($unique_child_numbers, SORT_NUMERIC);
        foreach ($unique_child_numbers as $num) {
            if ($num < 0) continue;
            if ($num == 0) {
                $child_options[0] = __('0 Children', 'woocommerce');
            } else {
                $child_options[$num] = sprintf(_n('%s Child', '%s Children', $num, 'woocommerce'), $num);
            }
        }
        if (empty($child_options)) { // Fallback
            $child_options[0] = __('0 Children', 'woocommerce');
        }

        // --- Display Fields ---
        echo '<div class="bwp-booking-fields">';

        woocommerce_form_field(
            'bwp_adults',
            array(
                'type'        => 'select',
                'class'       => array('form-row-wide', 'bwp-adults-field'),
                'label'       => __('Adults', 'woocommerce'),
                'required'    => true,
                'options'     => $adult_options,
                'default'     => 1,
            ),
            1 // Default value
        );

        woocommerce_form_field(
            'bwp_children',
            array(
                'type'        => 'select',
                'class'       => array('form-row-wide', 'bwp-children-field'),
                'label'       => __('Children', 'woocommerce'),
                'required'    => false,
                'options'     => $child_options,
                'default'     => 0,
            ),
            0 // Default value
        );

        // Departure Location Dropdown - Now with fixed options
        // $departure_options_for_frontend is already defined above
        // $default_departure_location is also defined above as 'phuket'
        if (!empty($departure_options_for_frontend)) {
            echo '<div class="form-row form-row-wide bwp-departure-field validate-required" id="bwp_departure_location_radio_field" data-priority="">';
            echo '<label class="required_field">' . esc_html__('Departure From', 'woocommerce') . '&nbsp;<span class="required" aria-hidden="true">*</span></label>';
            echo '<div class="woocommerce-input-wrapper bwp-departure-radio-wrapper">';
            $first_option = true; // To check the default one
            foreach ($departure_options_for_frontend as $value => $label) {
                // $default_departure_location is 'phuket'
                // The $value will be 'phuket' or 'khaolak'
                $is_checked = ($default_departure_location === $value);

                echo '<span class="bwp-departure-radio-option">'; // Wrapper for styling each radio option
                echo '<input type="radio" class="input-radio bwp_departure_location_radio" value="' . esc_attr($value) . '" name="bwp_departure_location" id="bwp_departure_location_' . esc_attr($value) . '" ' . checked($is_checked, true, false) . '>';
                echo '<label for="bwp_departure_location_' . esc_attr($value) . '" class="radio"> ' . esc_html($label) . '</label>'; // Added a space before label for better default styling
                echo '</span>';
            }
            echo '</div>'; // end .woocommerce-input-wrapper
            echo '</div>'; // end .form-row
        }

        echo '<h4 class="bwp-section-title">' . esc_html__('Select Booking Dates', 'woocommerce') . '</h4>';

        // This is the visible input field for Litepicker
        woocommerce_form_field(
            'bwp_date_range_display', // Name for the display field, not directly used by backend for dates
            array(
                'type'        => 'text',
                'class'       => array('form-row-wide', 'bwp-date-range-display-field'),
                'label'       => __('Booking Dates', 'woocommerce'), // Single label for the range
                'required'    => true, // The hidden fields will be validated by JS population
                'placeholder' => __('Select date range', 'woocommerce'),
                'custom_attributes' => array('readonly' => 'readonly', 'autocomplete' => 'off'), // Re-add readonly
            ),
            '' // Default value
        );

        // Hidden fields to store actual start and end dates for backend processing
        // Their 'name' attributes match what the backend PHP already expects.
        echo '<input type="hidden" name="bwp_start_date" id="bwp_start_date_hidden">';
        echo '<input type="hidden" name="bwp_end_date" id="bwp_end_date_hidden">';

        echo '</div>';

        // Hidden fields for JS
        echo '<input type="hidden" id="bwp_base_price" value="' . esc_attr($product->get_price()) . '">';
        echo '<input type="hidden" id="bwp_tiered_prices" value="' . esc_attr(json_encode($js_tiered_prices)) . '">'; // For adults and children
        if (!empty($departure_prices_for_js)) {
            echo '<input type="hidden" id="bwp_departure_location_prices_data" value="' . esc_attr(json_encode($departure_prices_for_js)) . '">';
        }
        // The div #bwp_calculated_price is no longer needed as price will update main display
    }
}
add_action('woocommerce_before_add_to_cart_button', 'bwp_display_booking_fields', 10);

/**
 * Enqueue script for dynamic price calculation and localize script
 */
function bwp_enqueue_scripts()
{
    if (is_product()) {
        // Enqueue Litepicker from CDN
        wp_enqueue_style('litepicker-css', 'https://cdn.jsdelivr.net/npm/litepicker/dist/css/litepicker.css');
        wp_enqueue_script('litepicker-js', 'https://cdn.jsdelivr.net/npm/litepicker/dist/litepicker.js', array(), '1.1.2', true); // Added version for Litepicker

        // Ensure bwp-booking-js depends on litepicker-js.
        wp_enqueue_script('bwp-booking-js', get_stylesheet_directory_uri() . '/bwp-booking.js', array('jquery', 'litepicker-js'), '1.0.2', true); // Version bump & dep update
        wp_localize_script('bwp-booking-js', 'bwp_booking_params', array(
            'currency_symbol'    => get_woocommerce_currency_symbol(),
            'ajax_url'           => admin_url('admin-ajax.php'), // If needed for future AJAX operations
            'currency_pos'       => get_option('woocommerce_currency_pos'), // e.g. 'left', 'right', 'left_space', 'right_space'
            'thousand_separator' => wc_get_price_thousand_separator(),
            'decimal_separator'  => wc_get_price_decimal_separator(),
            'decimals'           => wc_get_price_decimals(),
        ));
    }
}
add_action('wp_enqueue_scripts', 'bwp_enqueue_scripts');

/**
 * Add custom data to cart item
 */
function bwp_add_cart_item_data($cart_item_data, $product_id, $variation_id)
{
    if (isset($_POST['bwp_adults'])) {
        $cart_item_data['bwp_adults'] = intval(sanitize_text_field($_POST['bwp_adults']));
    }
    if (isset($_POST['bwp_children'])) {
        $cart_item_data['bwp_children'] = intval(sanitize_text_field($_POST['bwp_children']));
    }
    if (isset($_POST['bwp_departure_location']) && !empty($_POST['bwp_departure_location'])) {
        $cart_item_data['bwp_departure_location'] = sanitize_text_field($_POST['bwp_departure_location']);
    }
    if (isset($_POST['bwp_start_date']) && !empty($_POST['bwp_start_date'])) {
        $cart_item_data['bwp_start_date'] = sanitize_text_field($_POST['bwp_start_date']);
    }
    if (isset($_POST['bwp_end_date']) && !empty($_POST['bwp_end_date'])) {
        $cart_item_data['bwp_end_date'] = sanitize_text_field($_POST['bwp_end_date']);
    }
    // Store original price if needed for display or complex calculations later
    // $product = wc_get_product( $product_id );
    // $cart_item_data['bwp_base_price'] = $product->get_price();
    return $cart_item_data;
}
add_filter('woocommerce_add_cart_item_data', 'bwp_add_cart_item_data', 10, 3);

/**
 * Calculate and set the price for the cart item
 */
function bwp_calculate_cart_item_price($cart_object)
{
    if (is_admin() && ! defined('DOING_AJAX')) {
        return;
    }

    foreach ($cart_object->get_cart() as $cart_item_key => $cart_item) {
        if (isset($cart_item['bwp_adults'])) {
            $product_id = $cart_item['product_id']; // Get product ID
            $product_obj = $cart_item['data']; // Product object
            // $base_price = floatval(get_post_meta($product_id, '_price', true)); // Or $product_obj->get_price('edit') to get unmodified price
            // It's often better to get the price that WooCommerce itself considers the base before our modifications
            $original_product_for_price = wc_get_product($product_id);
            $base_price = floatval($original_product_for_price->get_price('edit'));


            $adults = intval($cart_item['bwp_adults']);
            $children = isset($cart_item['bwp_children']) ? intval($cart_item['bwp_children']) : 0;

            $new_price = $base_price;
            $additional_adult_price = 0;
            $additional_child_price = 0;
            $additional_departure_price = 0;

            if (function_exists('get_field')) { // No longer need have_rows for departure here
                // Calculate additional price for departure location
                if (isset($cart_item['bwp_departure_location']) && !empty($cart_item['bwp_departure_location'])) {
                    $selected_location_value = $cart_item['bwp_departure_location'];
                    $price_val = 0;
                    $departure_group = get_field('departure', $product_id); // Get the parent group

                    if ($departure_group) {
                        if ($selected_location_value === 'phuket') {
                            $price_val = isset($departure_group['phuket_additional_price']) ? $departure_group['phuket_additional_price'] : 0;
                        } elseif ($selected_location_value === 'khaolak') {
                            $price_val = isset($departure_group['khaolak_additional_price']) ? $departure_group['khaolak_additional_price'] : 0;
                        }
                    }

                    if (is_numeric($price_val)) {
                        $additional_departure_price = floatval($price_val);
                    }
                }
                $new_price += $additional_departure_price;

                // Calculate additional price for adults from ACF
                if ($adults >= 2) { // Base price is for 1 adult
                    $adult_tiers = get_field('adult_price_tiers', $product_id);
                    if ($adult_tiers) {
                        foreach ($adult_tiers as $tier) {
                            if (isset($tier['number_of_adults']) && intval($tier['number_of_adults']) == $adults) {
                                if (isset($tier['additional_price']) && is_numeric($tier['additional_price'])) {
                                    $additional_adult_price = floatval($tier['additional_price']);
                                    break; // Found the matching tier
                                }
                            }
                        }
                    }
                }
                $new_price += $additional_adult_price;

                // Calculate additional price for children from ACF
                if ($children >= 1) {
                    $child_tiers = get_field('child_price_tiers', $product_id);
                    if ($child_tiers) {
                        foreach ($child_tiers as $tier) {
                            if (isset($tier['number_of_children']) && intval($tier['number_of_children']) == $children) {
                                if (isset($tier['additional_price']) && is_numeric($tier['additional_price'])) {
                                    $additional_child_price = floatval($tier['additional_price']);
                                    break; // Found the matching tier
                                }
                            }
                        }
                    }
                }
                $new_price += $additional_child_price;
            }
            $product_obj->set_price($new_price);
        }
    }
}
add_action('woocommerce_before_calculate_totals', 'bwp_calculate_cart_item_price', 20, 1);


/**
 * Display booking data in cart and checkout
 */
function bwp_display_cart_item_booking_data($item_data, $cart_item)
{
    if (isset($cart_item['bwp_adults'])) {
        $item_data[] = array(
            'key'     => __('Adults', 'woocommerce'),
            'value'   => $cart_item['bwp_adults'],
            'display' => '',
        );
    }
    if (isset($cart_item['bwp_children']) && $cart_item['bwp_children'] > 0) {
        $item_data[] = array(
            'key'     => __('Children', 'woocommerce'),
            'value'   => $cart_item['bwp_children'],
            'display' => '',
        );
    }

    if (isset($cart_item['bwp_departure_location']) && !empty($cart_item['bwp_departure_location'])) {
        $selected_location_value = $cart_item['bwp_departure_location'];
        $location_label_display = '';
        $product_id = $cart_item['product_id']; // Ensure product_id is available

        if (function_exists('get_field_object')) {
            $departure_group_field_object = get_field_object('departure', $product_id);
            if ($departure_group_field_object && isset($departure_group_field_object['sub_fields'])) {
                $target_sub_field_name = ($selected_location_value === 'phuket') ? 'phuket_additional_price' : 'khaolak_additional_price';
                foreach ($departure_group_field_object['sub_fields'] as $sub_field) {
                    if ($sub_field['name'] === $target_sub_field_name && !empty($sub_field['label'])) {
                        $location_label_display = esc_html($sub_field['label']);
                        break;
                    }
                }
            }
        }
        // Fallback if dynamic label not found
        if (empty($location_label_display)) {
            if ($selected_location_value === 'phuket') {
                $location_label_display = 'Phuket'; // Default non-translatable fallback
            } elseif ($selected_location_value === 'khaolak') {
                $location_label_display = 'Khaolak'; // Default non-translatable fallback
            }
        }

        if (!empty($location_label_display)) {
            $item_data[] = array(
                'key'     => __('Departure From', 'woocommerce'),
                'value'   => $location_label_display,
                'display' => '',
            );
        }
    }

    // Display Booking Date
    if (!empty($cart_item['bwp_start_date'])) { // In single mode, bwp_start_date is the selected date
        $item_data[] = array(
            'key'     => __('Booking Date', 'woocommerce'), // Ensure singular label
            'value'   => esc_html($cart_item['bwp_start_date']), // Display only the single date
            'display' => '',
        );
    }

    return $item_data;
}
add_filter('woocommerce_get_item_data', 'bwp_display_cart_item_booking_data', 10, 2);

/**
 * Add booking data to order item meta
 */
function bwp_add_order_item_meta($item, $cart_item_key, $values, $order)
{
    if (isset($values['bwp_adults'])) {
        $item->add_meta_data(__('Adults', 'woocommerce'), $values['bwp_adults']);
    }
    if (isset($values['bwp_children']) && $values['bwp_children'] > 0) {
        $item->add_meta_data(__('Children', 'woocommerce'), $values['bwp_children']);
    }

    if (isset($values['bwp_departure_location']) && !empty($values['bwp_departure_location'])) {
        $selected_location_value = $values['bwp_departure_location'];
        $location_label_display = '';
        $product_id = $item->get_product_id(); // Get product_id from the order item

        if (function_exists('get_field_object')) {
            $departure_group_field_object = get_field_object('departure', $product_id);
            if ($departure_group_field_object && isset($departure_group_field_object['sub_fields'])) {
                $target_sub_field_name = ($selected_location_value === 'phuket') ? 'phuket_additional_price' : 'khaolak_additional_price';
                foreach ($departure_group_field_object['sub_fields'] as $sub_field) {
                    if ($sub_field['name'] === $target_sub_field_name && !empty($sub_field['label'])) {
                        $location_label_display = esc_html($sub_field['label']);
                        break;
                    }
                }
            }
        }
        // Fallback if dynamic label not found
        if (empty($location_label_display)) {
            if ($selected_location_value === 'phuket') {
                $location_label_display = 'Phuket'; // Default non-translatable fallback
            } elseif ($selected_location_value === 'khaolak') {
                $location_label_display = 'Khaolak'; // Default non-translatable fallback
            }
        }

        if (!empty($location_label_display)) {
            $item->add_meta_data(__('Departure From', 'woocommerce'), $location_label_display);
        }
    }

    // Add Booking Date to order item meta
    if (!empty($values['bwp_start_date'])) { // In single mode, bwp_start_date is the selected date
        $item->add_meta_data(
            __('Booking Date', 'woocommerce'), // Changed to singular
            esc_html($values['bwp_start_date'])
        );
    }
}
add_action('woocommerce_checkout_create_order_line_item', 'bwp_add_order_item_meta', 10, 4);

// Note: WooCommerce usually displays item meta automatically on order details pages (admin and customer)
// if added correctly using $item->add_meta_data().
// If specific formatting is needed, additional hooks like 'woocommerce_order_item_meta_start' or
// 'woocommerce_order_item_get_formatted_meta_data' can be used.