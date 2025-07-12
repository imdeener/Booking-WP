jQuery(document).ready(function ($) {
    // Function to format price according to WooCommerce settings
    function formatPrice(price) {
        price = parseFloat(price);

        // Format the number with correct decimal places
        let formatted = price.toFixed(bwp_booking_params.decimals);

        // Add thousand separators
        if (bwp_booking_params.thousand_separator) {
            formatted = formatted.replace(/\B(?=(\d{3})+(?!\d))/g, bwp_booking_params.thousand_separator);
        }

        // Replace decimal separator if needed
        if (bwp_booking_params.decimal_separator !== '.') {
            formatted = formatted.replace('.', bwp_booking_params.decimal_separator);
        }

        // Add currency symbol in correct position
        switch (bwp_booking_params.currency_pos) {
            case 'left':
                return bwp_booking_params.currency_symbol + formatted;
            case 'right':
                return formatted + bwp_booking_params.currency_symbol;
            case 'left_space':
                return bwp_booking_params.currency_symbol + ' ' + formatted;
            case 'right_space':
                return formatted + ' ' + bwp_booking_params.currency_symbol;
            default:
                return formatted;
        }
    }

    // Calculate and update price when on product page
    if ($('.bwp-booking-fields').length > 0) {
        function calculate_price() {
            const base_price = parseFloat($('#bwp_base_price').val()) || 0;
            const tiered_prices = JSON.parse($('#bwp_tiered_prices').val() || '{}');
            const departure_prices = JSON.parse($('#bwp_departure_location_prices_data').val() || '{}');

            let total_price = base_price;

            // Get selected number of adults and children
            const adults = parseInt($('#bwp_adults').val()) || 1;
            const children = parseInt($('#bwp_children').val()) || 0;

            // Add tiered prices for adults if applicable
            if (adults >= 2 && tiered_prices.adults && tiered_prices.adults[adults]) {
                total_price += parseFloat(tiered_prices.adults[adults]);
            }

            // Add tiered prices for children if applicable
            if (children > 0 && tiered_prices.children && tiered_prices.children[children]) {
                total_price += parseFloat(tiered_prices.children[children]);
            }

            // Add departure location price if applicable
            const selected_departure = $('input[name="bwp_departure_location"]:checked').val();
            if (selected_departure && departure_prices[selected_departure]) {
                total_price += parseFloat(departure_prices[selected_departure]);
            }

            // Update WooCommerce price display
            $('.product p.price .amount').html(formatPrice(total_price));
            $('.product .single_variation_wrap .woocommerce-variation-price .amount').html(formatPrice(total_price));
        }

        // Attach event listeners to form elements when on product page
        $('#bwp_adults, #bwp_children').on('change', calculate_price);
        $('input[name="bwp_departure_location"]').on('change', calculate_price);

        // Initial calculation
        calculate_price();
    }
});
