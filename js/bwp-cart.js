jQuery(document).ready(function($) {
    let itemToDelete = null;

    // Delete confirmation modal functions
    function showDeleteModal(item) {
        itemToDelete = item;
        const modal = document.getElementById('deleteConfirmationModal');
        const itemDetails = document.getElementById('deleteItemDetails');
        
        if (!modal || !itemDetails) {
            console.error('Modal elements not found');
            return;
        }

        // Get item details
        const bookingItem = item.closest('.booking-item');
        if (!bookingItem) {
            console.error('Booking item not found');
            return;
        }

        const titleEl = bookingItem.querySelector('.product-title h3');
        const priceEl = bookingItem.querySelector('.booking-price .total-price .price');
        const imageEl = bookingItem.querySelector('.booking-image .booking-img');

        if (!titleEl || !priceEl || !imageEl) {
            console.error('Required elements not found');
            return;
        }

        const title = titleEl.textContent;
        const price = priceEl.textContent;
        const image = imageEl.src;
        
        // Update modal content
        itemDetails.innerHTML = `
            <div class="item">
                <img src="${image}" alt="${title}">
                <div class="item-info">
                    <div class="item-title">${title}</div>
                    <div class="item-price">${price}</div>
                </div>
            </div>
        `;
        
        modal.classList.add('show');
    }
    
    window.closeDeleteModal = function() {
        const modal = document.getElementById('deleteConfirmationModal');
        modal.classList.remove('show');
        itemToDelete = null;
    }
    
    window.confirmDelete = function() {
        if (itemToDelete) {
            window.location.href = itemToDelete.href;
        }
        closeDeleteModal();
    }
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
                    // Update quantity display in Your Booking section
                    button.siblings('.quantity').text(response.data.new_value);
                    
                    // Update quantity in Order Summary
                    if (type === 'children') {
                        const guestTypeChildren = $(`.guests[data-item-key="${itemKey}"] .guest-type.children`);
                        const newValue = parseInt(response.data.new_value);
                        
                        if (newValue > 0) {
                            // Show children section if not exists
                            if (guestTypeChildren.length === 0) {
                                const guestsContainer = $(`.guests[data-item-key="${itemKey}"]`);
                                guestsContainer.append(`
                                    <div class="guest-type children" data-item-key="${itemKey}">
                                        <i class="fas fa-child"></i>
                                        <span class="guest-count" data-item-key="${itemKey}" data-type="children">${newValue}</span>
                                        <span class="guest-label">Children</span>
                                    </div>
                                `);
                            } else {
                                // Update existing count
                                guestTypeChildren.find('.guest-count').text(newValue);
                            }
                        } else {
                            // Remove children section if exists
                            guestTypeChildren.remove();
                        }
                    } else {
                        // Update adults count
                        const summaryGuestCount = $(`.guest-count[data-item-key="${itemKey}"][data-type="${type}"]`);
                        if (summaryGuestCount.length) {
                            summaryGuestCount.text(response.data.new_value);
                        }
                    }
                    
                    // Update item price in Your Booking and Order Summary sections
                    const itemPriceElements = $('.total-price[data-item-key="' + itemKey + '"] .price, .summary-item .item-price .total-price[data-item-key="' + itemKey + '"]');
                    if (itemPriceElements.length) {
                        itemPriceElements.html(response.data.total_price);
                        console.log('Updated price elements:', {
                            itemKey: itemKey,
                            newPrice: response.data.total_price,
                            elements: itemPriceElements.length
                        });
                    } else {
                        console.error('Price elements not found for item key:', itemKey);
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
    
    // Handle coupon application
    $('.apply-coupon-btn').on('click', function() {
        const button = $(this);
        const couponInput = $('#coupon_code');
        const couponCode = couponInput.val().trim();
        const messageContainer = $('.coupon-message');
        const discountRow = $('.order-totals .discount');

        if (!couponCode) {
            messageContainer.removeClass('success').addClass('error').text('Please enter a coupon code');
            return;
        }

        // Check if a coupon is already applied
        if (discountRow.is(':visible')) {
            messageContainer.removeClass('success').addClass('error').text('A coupon is already applied. Please remove it first.');
            return;
        }

        // Disable button during request
        button.prop('disabled', true);
        messageContainer.removeClass('success error').text('Applying coupon...');

        $.ajax({
            url: bwp_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'bwp_apply_coupon',
                nonce: bwp_ajax.coupon_nonce,
                coupon_code: couponCode
            },
            success: function(response) {
                if (response.success) {
                    messageContainer.removeClass('error').addClass('success').text(response.data.message);
                    couponInput.val('');

                    // Update discount and total
                    const discountRow = $('.order-totals .discount');
                    if (response.data.discount > 0) {
                        if (discountRow.length === 0) {
                            $('.order-totals .subtotal').after(
                                '<div class="discount">' +
                                '<div class="label-group">' +
                                '<span class="label">Discount</span>' +
                                '<div class="coupon-badge">' +
                                couponCode +
                                '<button type="button" class="remove-coupon" data-coupon="' + couponCode + '">&times;</button>' +
                                '</div>' +
                                '</div>' +
                                '<span class="discount-amount">-' + response.data.discount_formatted + '</span>' +
                                '</div>'
                            );
                        } else {
                            discountRow.find('.discount-amount').html('-' + response.data.discount_formatted);
                        }
                        discountRow.show();
                        $('#coupon_code').val('').parent().hide();
                    } else {
                        discountRow.hide();
                    }

                    // Update total
                    $('.order-totals .total-amount').html(response.data.total_formatted);
                } else {
                    messageContainer.removeClass('success').addClass('error').text(response.data.message || 'Error applying coupon');
                }
            },
            error: function(xhr, status, error) {
                messageContainer.removeClass('success').addClass('error').text('Error applying coupon. Please try again.');
                console.error('Coupon AJAX error:', {status, error, response: xhr.responseText});
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });

    // Handle remove coupon
    $(document).on('click', '.remove-coupon', function() {
        const button = $(this);
        const couponCode = button.data('coupon');
        const messageContainer = $('.coupon-message');

        // Disable button during request
        button.prop('disabled', true);
        messageContainer.removeClass('success error').text('Removing coupon...');

        $.ajax({
            url: bwp_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'bwp_remove_coupon',
                nonce: bwp_ajax.coupon_nonce,
                coupon_code: couponCode
            },
            success: function(response) {
                if (response.success) {
                    messageContainer.removeClass('error').addClass('success').text(response.data.message);

                    // Hide discount row and show coupon input
                    $('.order-totals .discount').hide();
                    $('#coupon_code').val('').parent().show();

                    // Update subtotal and total
                    $('.order-totals .subtotal-amount').html(response.data.subtotal);
                    $('.order-totals .total-amount').html(response.data.total);
                } else {
                    messageContainer.removeClass('success').addClass('error').text(response.data.message || 'Error removing coupon');
                }
            },
            error: function(xhr, status, error) {
                messageContainer.removeClass('success').addClass('error').text('Error removing coupon. Please try again.');
                console.error('Remove coupon AJAX error:', {status, error, response: xhr.responseText});
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });

    // Handle remove item clicks
    $(document).on('click', '.remove-item a', function(e) {
        e.preventDefault();
        showDeleteModal(this);
    });
    
    // Form validation
    $('.bwp-customer-form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        submitBtn.prop('disabled', true);
        
        const formData = new FormData(form[0]);
        formData.append('action', 'bwp_save_customer_info');
        
        // Debug info
        console.log('Form submission:', {
            action: 'bwp_save_customer_info',
            nonce: formData.get('bwp_nonce')
        });
        
        $.ajax({
            url: bwp_ajax.ajax_url,
            type: 'POST',
            processData: false,
            contentType: false,
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Redirect to next step
                    window.location.href = response.data.redirect_url;
                } else {
                    submitBtn.prop('disabled', false);
                    alert(response.data || 'An error occurred. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                submitBtn.prop('disabled', false);
                alert('An error occurred. Please try again.');
                console.error('AJAX Error:', error);
            }
        });
    });
});
