jQuery(document).ready(function($) {
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
