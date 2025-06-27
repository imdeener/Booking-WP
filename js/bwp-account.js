jQuery(function($) {
    // Only on the main account page
    const path = window.location.pathname;
    if (path.endsWith('/my-account/') || path.endsWith('/my-account')) {
        // Find and click the payment methods link
        $('.woocommerce-MyAccount-navigation-link--payment-methods a').trigger('click');
        
        // Add active class to payment methods tab
        $('.woocommerce-MyAccount-navigation-link--dashboard').removeClass('is-active');
        $('.woocommerce-MyAccount-navigation-link--payment-methods').addClass('is-active');
    }
});
