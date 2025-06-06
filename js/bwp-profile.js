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
});
