jQuery(function($) {
    console.log('Privacy modal script loaded');
    
    // Privacy modal functionality
    const $modal = $('#privacy-modal');
    const $continueBtn = $('#continue-btn');
    const $closeBtn = $('.privacy-modal-close');
    const $cancelBtn = $('#privacy-cancel');
    const $acceptBtn = $('#privacy-accept');
    const $checkbox = $('#accept-privacy');
    const $customerForm = $('.bwp-customer-form');

    console.log('Elements found:', {
        modal: $modal.length,
        continueBtn: $continueBtn.length,
        closeBtn: $closeBtn.length,
        cancelBtn: $cancelBtn.length,
        acceptBtn: $acceptBtn.length,
        checkbox: $checkbox.length,
        customerForm: $customerForm.length
    });

    // Show modal when Continue button is clicked
    $continueBtn.on('click', function(e) {
        console.log('Continue button clicked!');
        e.preventDefault();
        
        // First validate the form
        const form = $customerForm[0];
        if (!form.checkValidity()) {
            // Show browser validation messages
            if (form.reportValidity) {
                form.reportValidity();
            }
            return;
        }

        // Show the privacy modal
        $modal.show();
        $('body').addClass('modal-open');
        
        // Focus on the modal for accessibility
        $modal.focus();
    });

    // Close modal functions
    function closeModal() {
        $modal.hide();
        $('body').removeClass('modal-open');
        $checkbox.prop('checked', false);
        $acceptBtn.prop('disabled', true);
    }

    // Close modal when clicking X
    $closeBtn.on('click', closeModal);

    // Close modal when clicking Cancel
    $cancelBtn.on('click', closeModal);

    // Close modal when clicking outside
    $modal.on('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    // Close modal with Escape key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $modal.is(':visible')) {
            closeModal();
        }
    });

    // Enable/disable Accept button based on checkbox
    $checkbox.on('change', function() {
        $acceptBtn.prop('disabled', !this.checked);
    });

    // Handle Accept & Continue
    $acceptBtn.on('click', function() {
        if ($checkbox.is(':checked')) {
            // Close modal
            closeModal();
            
            // Submit the customer form
            $customerForm.trigger('submit');
        }
    });

    // Prevent body scroll when modal is open
    $modal.on('show', function() {
        $('body').css('overflow', 'hidden');
    });

    $modal.on('hide', function() {
        $('body').css('overflow', '');
    });

    // Add modal-open class management
    $('body').on('DOMNodeInserted', function() {
        if ($modal.is(':visible')) {
            $('body').addClass('modal-open');
        }
    });

    // Add CSS to prevent body scroll when modal is open
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            body.modal-open {
                overflow: hidden !important;
            }
            .privacy-modal {
                overflow-y: auto;
            }
        `)
        .appendTo('head');
});
