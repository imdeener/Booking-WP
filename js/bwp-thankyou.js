jQuery(function($) {
    // Copy booking number functionality
    $('.booking-number-copy').on('click', function() {
        const $bookingNumber = $(this).closest('.info-text').find('.value').clone()    // Create a copy of the element
            .children()    // Get all the children
            .remove()      // Remove all the children
            .end()        // Go back to selected element
            .text()       // Get the text content
            .trim();      // Remove whitespace

        // Create temporary textarea to copy text
        const $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val($bookingNumber).select();
        
        try {
            // Execute copy command
            document.execCommand('copy');
            
            // Show success message
            const $icon = $(this);
            const $originalClass = $icon.attr('class');
            
            $icon.removeClass('fa-copy').addClass('fa-check text-success');
            
            // Reset icon after 2 seconds
            setTimeout(function() {
                $icon.removeClass('fa-check text-success').addClass('fa-copy');
            }, 2000);
            
        } catch (err) {
            console.error('Failed to copy text:', err);
        }
        
        // Remove temporary textarea
        $temp.remove();
    });
});
