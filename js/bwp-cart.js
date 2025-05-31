jQuery(document).ready(function($) {
    // Handle quantity button clicks
    $('.quantity-btn').on('click', function() {
        const button = $(this);
        const type = button.data('type');
        const itemKey = button.data('item-key');
        const action = button.hasClass('plus') ? 'increase' : 'decrease';
        
        // Get current quantity
        const currentQty = parseInt(button.siblings('.quantity').text());
        
        // Get min/max limits
        const min = parseInt(button.data('min')) || 0;
        const max = parseInt(button.data('max')) || 999;
        
        // Check if we can proceed with the action
        if (action === 'increase' && currentQty >= max) {
            console.log('Maximum quantity reached');
            return;
        }
        if (action === 'decrease' && currentQty <= min) {
            console.log('Minimum quantity reached');
            return;
        }
        
        // Debug info
        console.log('Button clicked:', {
            type: type,
            itemKey: itemKey,
            action: action,
            nonce: bwp_ajax.nonce,
            ajaxUrl: bwp_ajax.ajax_url
        });
        
        // Disable buttons during update
        button.closest('.guest-type').find('.quantity-btn').prop('disabled', true);
        
        $.ajax({
            url: bwp_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'bwp_update_guest_quantity',
                nonce: bwp_ajax.nonce,
                type: type,
                item_key: itemKey,
                action_type: action
            },
            success: function(response) {
                console.log('AJAX success response:', response);
                if (response.success) {
                    // Update quantity display
                    button.siblings('.quantity').text(response.data.new_value);
                    
                    // Update item price
                    const itemPriceElement = $('.total-price[data-item-key="' + itemKey + '"]');
                    if (itemPriceElement.length) {
                        itemPriceElement.html(response.data.total_price);
                        console.log('Updated price element:', {
                            itemKey: itemKey,
                            newPrice: response.data.total_price
                        });
                    } else {
                        console.error('Price element not found for item key:', itemKey);
                    }
                    
                    // Update order summary
                    if (response.data.cart_totals) {
                        // Update order totals
                        $('.order-totals .subtotal-amount').html(response.data.cart_totals.subtotal);
                        
                        // Update discount if present
                        const discountRow = $('.order-totals .discount');
                        if (response.data.cart_totals.discount) {
                            if (discountRow.length === 0) {
                                // Add discount row if it doesn't exist
                                $('.order-totals .subtotal').after(
                                    '<div class="discount">'
                                    + '<span class="label">Discount</span>'
                                    + '<span class="discount-amount">-' + response.data.cart_totals.discount + '</span>'
                                    + '</div>'
                                );
                            } else {
                                discountRow.find('.discount-amount').html('-' + response.data.cart_totals.discount);
                            }
                            discountRow.show();
                        } else {
                            discountRow.hide();
                        }
                        
                        // Update total
                        $('.order-totals .total-amount').html(response.data.cart_totals.total);
                        
                        // Log for debugging
                        console.log('Updated cart totals:', response.data.cart_totals);
                    }
                    
                    // Refresh the page if needed
                    if (response.data.refresh) {
                        location.reload();
                        return;
                    }
                } else {
                    console.error('Error response:', response);
                    alert(response.data || 'Error updating quantity. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                alert('Error updating quantity. Please try again.');
            },
            complete: function() {
                // Re-enable buttons after update
                button.closest('.guest-type').find('.quantity-btn').prop('disabled', false);
            }
        });
    });
    
    // Form validation
    $('.bwp-customer-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        
        // Disable submit button
        $submitBtn.prop('disabled', true);
        
        $.ajax({
            url: bwp_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'bwp_save_customer_info',
                nonce: bwp_ajax.nonce,
                first_name: $('#first_name').val(),
                last_name: $('#last_name').val(),
                thai_id: $('#thai_id').val(),
                email: $('#email').val(),
                phone: $('#phone').val(),
                hotel_name: $('#hotel_name').val(),
                room: $('#room').val(),
                special_requests: $('#special_requests').val()
            },
            success: function(response) {
                if (response.success) {
                    // Redirect to next step
                    window.location.href = response.data.redirect;
                } else {
                    alert('Error: ' + response.data);
                    $submitBtn.prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                alert('Error saving customer information. Please try again.');
                $submitBtn.prop('disabled', false);
            }
        });
    });
});
