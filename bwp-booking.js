jQuery(document).ready(function ($) {
    console.log('bwp-booking.js started and jQuery ready'); // DEBUG LINE

    // Helper function to format numbers according to WooCommerce settings
    function bwp_format_price_number(number, decimals, dec_point, thousands_sep) {
        number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
        var n = !isFinite(+number) ? 0 : +number,
            prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
            sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
            dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
            s = '',
            toFixedFix = function (n, prec) {
                var k = Math.pow(10, prec);
                return '' + Math.round(n * k) / k;
            };
        // Fix for IE parseFloat(0.55).toFixed(0) = 0;
        s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
        if (s[0].length > 3) {
            s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
        }
        if ((s[1] || '').length < prec) {
            s[1] = s[1] || '';
            s[1] += new Array(prec - s[1].length + 1).join('0');
        }
        return s.join(dec);
    }

    function calculate_price() {
        var base_price = parseFloat($('#bwp_base_price').val());
        var tiered_prices_json = $('#bwp_tiered_prices').val();
        var tiered_prices = { adults: {}, children: {} };

        if (tiered_prices_json) {
            try {
                tiered_prices = JSON.parse(tiered_prices_json);
                // Ensure nested objects exist
                tiered_prices.adults = tiered_prices.adults || {};
                tiered_prices.children = tiered_prices.children || {};
            } catch (e) {
                console.error("Error parsing tiered prices JSON:", e);
            }
        }

        var adults = parseInt($('select[name="bwp_adults"]').val());
        var children = parseInt($('select[name="bwp_children"]').val());
        var selected_departure_location = $('input[name="bwp_departure_location"]:checked').val(); // Get selected departure location from radio

        // Get departure location prices from hidden input
        var departure_prices_json = $('#bwp_departure_location_prices_data').val();
        var departure_prices = {};
        if (departure_prices_json) {
            try {
                departure_prices = JSON.parse(departure_prices_json);
            } catch (e) {
                console.error("Error parsing departure location prices JSON:", e);
                departure_prices = {};
            }
        }

        if (isNaN(adults) || adults < 1) {
            adults = 1;
        }
        if (isNaN(children) || children < 0) {
            children = 0;
        }

        var total_price = base_price;
        var additional_adult_price = 0;
        var additional_child_price = 0;
        var additional_departure_price = 0;

        // Calculate additional price for selected departure location
        if (selected_departure_location && typeof departure_prices[selected_departure_location] !== 'undefined') {
            additional_departure_price = parseFloat(departure_prices[selected_departure_location]);
            if (!isNaN(additional_departure_price)) {
                total_price += additional_departure_price;
            }
        }

        // Calculate additional price for adults based on tiers
        if (adults >= 2 && tiered_prices.adults && typeof tiered_prices.adults[adults] !== 'undefined') {
            additional_adult_price = parseFloat(tiered_prices.adults[adults]);
            if (!isNaN(additional_adult_price)) {
                total_price += additional_adult_price;
            }
        }
        // Note: If adults selected is > max defined tier (e.g. 5),
        // this logic currently adds no further adult price beyond the base.
        // You might want to adjust this (e.g. use price for max tier, or prevent selection).

        // Calculate additional price for children based on tiers
        if (children >= 1 && tiered_prices.children && typeof tiered_prices.children[children] !== 'undefined') {
            additional_child_price = parseFloat(tiered_prices.children[children]);
            if (!isNaN(additional_child_price)) {
                total_price += additional_child_price;
            }
        }
        // Similar note for children selected > max defined tier.

        // Display the price
        // Make sure your theme's WooCommerce price display is compatible or adjust this selector
        var formatted_price_number = total_price.toFixed(bwp_booking_params.decimals || 2); // Default to 2 decimals

        if (typeof bwp_booking_params !== 'undefined') {
            formatted_price_number = bwp_format_price_number(
                total_price,
                bwp_booking_params.decimals,
                bwp_booking_params.decimal_separator,
                bwp_booking_params.thousand_separator
            );
        }

        var currency_symbol_html = '<span class="woocommerce-Price-currencySymbol">' + bwp_booking_params.currency_symbol + '</span>';
        var bdi_content_html = '';

        switch (bwp_booking_params.currency_pos) {
            case 'left':
                bdi_content_html = currency_symbol_html + formatted_price_number;
                break;
            case 'right':
                bdi_content_html = formatted_price_number + currency_symbol_html;
                break;
            case 'left_space':
                bdi_content_html = currency_symbol_html + '&nbsp;' + formatted_price_number;
                break;
            case 'right_space':
                bdi_content_html = formatted_price_number + '&nbsp;' + currency_symbol_html;
                break;
            default: // Fallback if something is wrong
                bdi_content_html = currency_symbol_html + formatted_price_number;
        }

        // Attempt to update the main WooCommerce price display, targeting Oxygen Builder structure
        // and preserving <del> tag if present by updating only the <bdi> inside <ins> or the main price span.
        var $priceTargetBdi = null;
        var $oxygenPriceContainer = $('div.oxy-product-price');

        if ($oxygenPriceContainer.length) {
            var $priceParagraph = $oxygenPriceContainer.find('p.price');
            if ($priceParagraph.length) {
                var $salePriceInsBdi = $priceParagraph.find('ins span.woocommerce-Price-amount.amount bdi');
                if ($salePriceInsBdi.length) {
                    $priceTargetBdi = $salePriceInsBdi;
                } else {
                    // Try to find the bdi in a non-sale price structure
                    var $regularPriceBdi = $priceParagraph.find('span.woocommerce-Price-amount.amount bdi');
                    if ($regularPriceBdi.length) {
                        // Ensure we get the first one that's not inside a <del> tag, if multiple exist.
                        if ($priceParagraph.find('del span.woocommerce-Price-amount.amount bdi').is($regularPriceBdi)) {
                            // If the only bdi found is inside a del, this means there's no separate current price bdi.
                            // This case might require replacing the whole p.price or creating an <ins>
                            // For now, if only <del> exists, we won't update to avoid complex DOM manipulation.
                            // A better solution for this edge case might be to replace p.price content.
                            // However, if a <del> exists, an <ins> *should* also exist for a sale.
                            // If only <del> exists and no <ins>, it implies the product is not purchasable at that price.
                            // Let's assume if we find a bdi not in ins, it's the one to update.
                            $priceTargetBdi = $regularPriceBdi.first(); // Get the first one
                        } else {
                            $priceTargetBdi = $regularPriceBdi.not($priceParagraph.find('del span.woocommerce-Price-amount.amount bdi')).first();
                        }

                        // If after filtering, we still don't have a target, it might be a simple price without <del> or <ins>
                        if (!$priceTargetBdi || !$priceTargetBdi.length) {
                            $priceTargetBdi = $priceParagraph.find('span.woocommerce-Price-amount.amount:first bdi');
                        }
                    }
                }
            }
        }

        if ($priceTargetBdi && $priceTargetBdi.length) {
            $priceTargetBdi.html(bdi_content_html);
        } else {
            // Fallback: If specific BDI targeting fails, try replacing the content of p.price (might lose <del>)
            // This is the simpler previous behavior if precise targeting fails.
            var $fallbackPriceP = $('div.oxy-product-price p.price');
            if ($fallbackPriceP.length) {
                var price_html_full_span = '<span class="woocommerce-Price-amount amount"><bdi>' + bdi_content_html + '</bdi></span>';
                // If there's an <ins>, put it there.
                var $ins = $fallbackPriceP.find('ins');
                if ($ins.length) {
                    $ins.html(price_html_full_span);
                } else {
                    $fallbackPriceP.html(price_html_full_span);
                }
            }
            // console.log('BWP Booking: Could not find specific BDI to update price. Used fallback or did nothing.');
        }

        // Optionally, update the main product price display if your theme uses a standard class.
        // This might need adjustment based on your theme's structure.
        // $('.product_meta .price').html(price_html); // Example, might not work for all themes
        // $('.summary .price > .woocommerce-Price-amount').html(price_html); // Another common selector
    }

    // Initial calculation
    calculate_price();

    // Handle the Child button click
    $('.bwp-add-child-btn').on('click', function(e) {
        e.preventDefault();
        // Show the children dropdown
        $('.bwp-children-field').removeClass('hidden');
        // Hide the button
        $(this).hide();
    });
    
    // Style the departure radio buttons
    $('.bwp-departure-radio-option').on('click', function() {
        // Remove selected class from all options
        $('.bwp-departure-radio-option').removeClass('selected');
        // Add selected class to clicked option
        $(this).addClass('selected');
        // Check the radio button
        $(this).find('input[type="radio"]').prop('checked', true).trigger('change');
    });
    
    // Recalculate on change
    $('select[name="bwp_adults"], select[name="bwp_children"], input[name="bwp_departure_location"]').on('change', function () {
        calculate_price();
    });

    // Litepicker Initialization
    // Corrected ID to match the actual input field ID generated by woocommerce_form_field
    var dateRangeDisplayInput = document.getElementById('bwp_date_range_display');
    var startDateHiddenInput = document.getElementById('bwp_start_date_hidden');
    var endDateHiddenInput = document.getElementById('bwp_end_date_hidden');

    if (dateRangeDisplayInput && startDateHiddenInput && endDateHiddenInput && typeof Litepicker !== 'undefined') {
        const picker = new Litepicker({
            element: dateRangeDisplayInput,
            singleMode: true, // Changed to single mode
            autoApply: true,
            minDate: new Date(), // Set minDate to today
            format: 'YYYY-MM-DD',
            // separator: ' - ', // Not needed for singleMode
            numberOfMonths: 1, // Show one month for single date selection
            // numberOfColumns: 2, // Not needed for single month
            lang: 'en-US', // Explicitly set language, can be changed
            // tooltipText for nights is not relevant for singleMode
            setup: (pickerInstance) => {
                pickerInstance.on('selected', (date) => { // singleMode provides one date argument
                    if (date) {
                        // Explicitly set the display input's value
                        dateRangeDisplayInput.value = date.format('YYYY-MM-DD');

                        // Populate both hidden fields with the same date for backend consistency
                        startDateHiddenInput.value = date.format('YYYY-MM-DD');
                        endDateHiddenInput.value = date.format('YYYY-MM-DD');

                        // Trigger change on hidden inputs
                        $(startDateHiddenInput).trigger('change');
                        $(endDateHiddenInput).trigger('change');
                    } else {
                        // If selection results in no date
                        dateRangeDisplayInput.value = '';
                        startDateHiddenInput.value = '';
                        endDateHiddenInput.value = '';
                        $(startDateHiddenInput).trigger('change');
                        $(endDateHiddenInput).trigger('change');
                    }
                    // console.log('Litepicker selected: Display - ' + dateRangeDisplayInput.value + ', StartHidden - ' + startDateHiddenInput.value + ', EndHidden - ' + endDateHiddenInput.value);
                    // calculate_price(); // Trigger price calculation if dates affect price
                });

                pickerInstance.on('clear:selection', () => {
                    dateRangeDisplayInput.value = '';
                    startDateHiddenInput.value = '';
                    endDateHiddenInput.value = '';
                    $(startDateHiddenInput).trigger('change');
                    $(endDateHiddenInput).trigger('change');
                    // console.log('Litepicker cleared. Display: ' + dateRangeDisplayInput.value);
                    // calculate_price(); // Trigger price calculation if dates affect price
                });
            }
        });
    } else {
        if (typeof Litepicker === 'undefined' && dateRangeDisplayInput) {
            console.error('BWP Booking: Litepicker is not loaded or not a function. Ensure it is enqueued correctly and bwp-booking-js depends on it.');
        } else if (!dateRangeDisplayInput) {
            // console.log('BWP Booking: Date range display input not found.');
        }
    }

    // The currency symbol is now passed via bwp_booking_params from wp_localize_script.
    // No need for the old logic to find it in the DOM.
});