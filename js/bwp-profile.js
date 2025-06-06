jQuery(function($) {
    // Handle profile image change button click
    $('.change-image-btn').on('click', function(e) {
        e.preventDefault();
        $('#profile_image_upload').trigger('click');
    });

    // Handle file input change
    $('#profile_image_upload').on('change', function(e) {
        if (!e.target.files.length) return;

        const file = e.target.files[0];
        if (!file.type.startsWith('image/')) {
            alert('Please select an image file');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'bwp_upload_profile_image');
        formData.append('nonce', bwpProfile.nonce);
        formData.append('profile_image', file);

        $.ajax({
            url: bwpProfile.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('.profile-image').addClass('uploading');
            },
            success: function(response) {
                if (response.success && response.data.url) {
                    $('.profile-image img').attr('src', response.data.url);
                } else {
                    alert('Failed to upload image. Please try again.');
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            },
            complete: function() {
                $('.profile-image').removeClass('uploading');
            }
        });
    });

    // Handle edit profile button click
    $('#edit-profile-btn').on('click', function() {
        $('#profile-display').hide();
        $('#profile-form').show();
        $(this).hide();
    });

    // Handle cancel button click
    $('.cancel-edit-btn').on('click', function() {
        $('#profile-form').hide();
        $('#profile-display').show();
        $('#edit-profile-btn').show();
    });

    // Handle form submission
    $('#profile-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'bwp_update_profile');
        formData.append('nonce', $('#bwp_profile_nonce').val());

        $.ajax({
            url: bwpProfile.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('.save-profile-btn').prop('disabled', true).css('opacity', '0.7');
            },
            success: function(response) {
                if (response.success) {
                    // Update display values
                    $('#profile-display .info-value').eq(0).text(formData.get('first_name') + ' ' + formData.get('last_name'));
                    $('#profile-display .info-value').eq(1).text(formData.get('thai_id'));
                    $('#profile-display .info-value').eq(2).text(formData.get('email'));
                    $('#profile-display .info-value').eq(3).text(formData.get('phone'));
                    
                    // Switch back to display mode
                    $('#profile-form').hide();
                    $('#profile-display').show();
                    $('#edit-profile-btn').show();
                } else {
                    alert('Failed to update profile. Please try again.');
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            },
            complete: function() {
                $('.save-profile-btn').prop('disabled', false).css('opacity', '1');
            }
        });
    });
});
