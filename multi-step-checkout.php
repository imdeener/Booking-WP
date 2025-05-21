<?php
/**
 * Multi-step Checkout for WooCommerce
 *
 * @package WooCommerce
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check if WooCommerce is active
if (!function_exists('WC')) {
    return;
}

// Override checkout fields
add_filter('woocommerce_checkout_fields', 'custom_override_checkout_fields');
function custom_override_checkout_fields($fields) {
    return $fields;
}

// Add custom checkout steps
add_action('woocommerce_before_checkout_form', 'add_custom_checkout_steps', 5);
function add_custom_checkout_steps() {
    if (!function_exists('wc_get_checkout_url')) {
        return;
    }
    ?>
    <div id="checkout-steps-wrap" class="checkout-steps-wrapper">
        <div class="checkout-steps">
            <div class="step-indicator step-1 active">
                <span class="step-number">1</span>
                <span class="step-title">Order Information</span>
            </div>
            <div class="step-indicator step-2">
                <span class="step-number">2</span>
                <span class="step-title">Payment Information</span>
            </div>
            <div class="step-indicator step-3">
                <span class="step-number">3</span>
                <span class="step-title">Complete Your Order</span>
            </div>
        </div>
    </div>
    <?php
}

// Customize checkout fields
add_filter('woocommerce_checkout_fields', 'customize_checkout_fields');
function customize_checkout_fields($fields) {
    // Billing fields
    $fields['billing']['billing_first_name']['label'] = 'First Name';
    $fields['billing']['billing_last_name']['label'] = 'Last Name';
    $fields['billing']['billing_country']['label'] = 'Country / Region (Optional)';
    $fields['billing']['billing_state']['label'] = 'State / County (Optional)';
    $fields['billing']['billing_phone']['label'] = 'Phone (Optional)';
    $fields['billing']['billing_email']['label'] = 'Email Address';

    // Add custom field for additional information
    $fields['order']['order_comments'] = array(
        'type' => 'textarea',
        'label' => 'Additional Information',
        'placeholder' => 'Notes about your order, e.g. special notes for pickup.',
        'required' => false
    );

    return $fields;
}

// Add custom sections to checkout
add_action('woocommerce_checkout_before_customer_details', 'custom_checkout_sections');
function custom_checkout_sections() {
    if (!function_exists('woocommerce_form_field')) {
        return;
    }
    ?>
    <div id="checkout-steps-wrap">
        <!-- Step 1: Order Information -->
        <div class="checkout-step-content step-1 active">
            <div class="billing-info-section">
                <h3>Order Information</h3>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <?php woocommerce_form_field('billing_first_name', array(
                            'type' => 'text',
                            'class' => array('form-control'),
                            'label' => 'First Name',
                            'required' => true
                        )); ?>
                    </div>
                    <div class="form-group col-md-6">
                        <?php woocommerce_form_field('billing_last_name', array(
                            'type' => 'text',
                            'class' => array('form-control'),
                            'label' => 'Last Name',
                            'required' => true
                        )); ?>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <?php woocommerce_form_field('billing_country', array(
                            'type' => 'country',
                            'class' => array('form-control'),
                            'label' => 'Country / Region (Optional)',
                            'required' => false
                        )); ?>
                    </div>
                    <div class="form-group col-md-6">
                        <?php woocommerce_form_field('billing_state', array(
                            'type' => 'state',
                            'class' => array('form-control'),
                            'label' => 'State / County (Optional)',
                            'required' => false
                        )); ?>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <?php woocommerce_form_field('billing_phone', array(
                            'type' => 'tel',
                            'class' => array('form-control'),
                            'label' => 'Phone (Optional)',
                            'required' => false
                        )); ?>
                    </div>
                    <div class="form-group col-md-6">
                        <?php woocommerce_form_field('billing_email', array(
                            'type' => 'email',
                            'class' => array('form-control'),
                            'label' => 'Email Address',
                            'required' => true
                        )); ?>
                    </div>
                </div>
                <!-- Additional Information Section -->
                <div class="additional-info-section">
                    <h4>Additional Information</h4>
                    <div class="form-row">
                        <div class="form-group col-md-12">
                            <?php woocommerce_form_field('order_comments', array(
                                'type' => 'textarea',
                                'class' => array('form-control'),
                                'label' => 'Notes about your order',
                                'placeholder' => 'Notes about your order, e.g. special notes for pickup.',
                                'required' => false
                            )); ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="step-buttons">
                <button type="button" class="btn next-step" data-step="2">Continue to Payment</button>
            </div>
        </div>

        <!-- Step 2: Payment Information -->
        <div class="checkout-step-content step-2">
            <div class="payment-info-section">
                <h3>Payment Information</h3>
                <div id="payment" class="woocommerce-checkout-payment">
                    <?php do_action('woocommerce_checkout_payment'); ?>
                </div>
                <div class="coupon-section">
                    <h4>Have a coupon?</h4>
                    <?php if (wc_coupons_enabled()) { ?>
                        <div class="coupon-form">
                            <input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="Coupon code" />
                            <button type="button" class="button" name="apply_coupon" value="Apply coupon">Apply coupon</button>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <div class="step-buttons">
                <button type="button" class="btn prev-step" data-step="1">Back</button>
                <button type="button" class="btn next-step" data-step="3">Review Order</button>
            </div>
        </div>

        <!-- Step 3: Complete Your Order -->
        <div class="checkout-step-content step-3">
            <div class="order-review-section">
                <h3>Review Your Order</h3>
                <div class="order-review">
                    <?php do_action('woocommerce_checkout_order_review'); ?>
                </div>
            </div>
            <div class="step-buttons">
                <button type="button" class="btn prev-step" data-step="2">Back</button>
                <button type="submit" class="button alt" name="woocommerce_checkout_place_order" id="place_order" value="Place order" data-value="Place order">Place order</button>
            </div>
        </div>
    </div>
    <?php
}

// Add custom CSS
add_action('wp_head', 'add_multistep_checkout_styles');
function add_multistep_checkout_styles() {
    if (!function_exists('is_checkout') || !is_checkout()) return;
    ?>
    <style type="text/css">
        /* Checkout Steps Wrapper */
        .checkout-steps-wrapper {
            margin-bottom: 40px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        /* Steps Navigation */
        .checkout-steps {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            border-bottom: 1px solid #eee;
        }

        .step-indicator {
            flex: 1;
            text-align: center;
            padding: 0 15px;
            position: relative;
        }

        .step-indicator:not(:last-child):after {
            content: '';
            position: absolute;
            top: 50%;
            right: -50%;
            width: 100%;
            height: 2px;
            background: #ddd;
            transform: translateY(-50%);
            z-index: 1;
        }

        .step-number {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #fff;
            border: 2px solid #ddd;
            color: #666;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 8px;
            position: relative;
            z-index: 2;
            transition: all 0.3s ease;
        }

        .step-title {
            font-size: 14px;
            color: #666;
            margin-top: 8px;
            transition: all 0.3s ease;
        }

        /* Active Step */
        .step-indicator.active .step-number {
            border-color: #2196F3;
            background: #2196F3;
            color: #fff;
        }

        .step-indicator.active .step-title {
            color: #2196F3;
            font-weight: 600;
        }

        /* Completed Step */
        .step-indicator.completed .step-number {
            border-color: #4CAF50;
            background: #4CAF50;
            color: #fff;
        }

        .step-indicator.completed:after {
            background: #4CAF50;
        }

        /* Step Content */
        .checkout-step-content {
            display: none;
            padding: 30px;
            background: #fff;
            border-radius: 0 0 8px 8px;
        }

        .checkout-step-content.active {
            display: block;
        }

        /* Form Styling */
        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin-right: -15px;
            margin-left: -15px;
            margin-bottom: 20px;
        }

        .form-group {
            padding: 0 15px;
            margin-bottom: 20px;
        }

        .form-control {
            display: block;
            width: 100%;
            padding: 8px 12px;
            font-size: 14px;
            line-height: 1.5;
            color: #495057;
            background-color: #fff;
            border: 1px solid #ced4da;
            border-radius: 4px;
            transition: border-color 0.15s ease-in-out;
        }

        .form-control:focus {
            border-color: #2196F3;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(33,150,243,0.25);
        }

        .form-control.error {
            border-color: #dc3545;
        }

        /* Button Styling */
        .step-buttons {
            display: flex;
            justify-content: space-between;
            padding: 20px 30px;
            border-top: 1px solid #eee;
        }

        .button {
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .prev-step {
            background: #f5f5f5;
            color: #666;
        }

        .next-step {
            background: #2196F3;
            color: #fff;
        }

        .button:hover {
            opacity: 0.9;
        }

        /* Section Headers */
        h3 {
            color: #333;
            font-size: 20px;
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f5f5f5;
        }

        h4 {
            color: #666;
            font-size: 16px;
            margin-bottom: 20px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .form-group {
                flex: 0 0 100%;
                max-width: 100%;
            }

            .step-title {
                display: none;
            }

            .checkout-steps {
                padding: 15px;
            }

            .checkout-step-content {
                padding: 20px;
            }
        }
    </style>
    <?php
}
            align-items: center;
            justify-content: center;
            margin-right: 8px;
            font-size: 14px;
        }
        .step.active .step-number {
            background: #1e88e5;
            border-color: #1e88e5;
            color: #fff;
        }
        .order-info-section {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .billing-details-section,
        .additional-info-section {
            margin-bottom: 30px;
        }
        h3 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
        }
        h4 {
            font-size: 18px;
            margin-bottom: 15px;
            color: #555;
        }
        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px;
        }
        .form-group {
            padding: 0 10px;
            margin-bottom: 15px;
        }
        .col-md-6 {
            flex: 0 0 50%;
            max-width: 50%;
        }
        .col-md-12 {
            flex: 0 0 100%;
            max-width: 100%;
        }
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-control:focus {
            border-color: #1e88e5;
            outline: none;
            box-shadow: 0 0 0 2px rgba(30,136,229,0.1);
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-size: 14px;
        }
        .step-buttons {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
        }
        .button {
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .next-step {
            background: #1e88e5;
            color: #fff;
            border: none;
        }
        .prev-step {
            background: #f5f5f5;
            border: 1px solid #ddd;
            color: #555;
        }
        .next-step:hover {
            background: #1976d2;
        }
        .prev-step:hover {
            background: #eee;
        }
        .required {
            color: #e53935;
        }
        .woocommerce-error {
            background: #ffebee;
            border-left: 4px solid #e53935;
            padding: 10px 15px;
            margin-bottom: 20px;
            color: #c62828;
            border-radius: 4px;
        }
    </style>
    <?php
}

// Add custom JavaScript
add_action('wp_footer', 'add_multistep_checkout_scripts');
function add_multistep_checkout_scripts() {
    if (!function_exists('is_checkout') || !is_checkout()) return;
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        let currentStep = 1;
        const totalSteps = 3;

        // Update step indicators
        function updateStepIndicators() {
            $('.step-indicator').removeClass('active completed');
            $('.step-indicator.step-' + currentStep).addClass('active');
            for (let i = 1; i < currentStep; i++) {
                $('.step-indicator.step-' + i).addClass('completed');
            }

            // Update button visibility
            if (currentStep === 1) {
                $('.prev-step').hide();
            } else {
                $('.prev-step').show();
            }

            if (currentStep === totalSteps) {
                $('.next-step').hide();
                $('#place_order').show();
            } else {
                $('.next-step').show();
                $('#place_order').hide();
            }
        }

        // Show step content
        function showStep(step) {
            $('.checkout-step-content').hide();
            $('.checkout-step-content.step-' + step).show();
            currentStep = step;
            updateStepIndicators();

            // Scroll to top of steps
            $('html, body').animate({
                scrollTop: $('#checkout-steps-wrap').offset().top - 50
            }, 500);
        }

        // Validate step fields
        function validateStep(step) {
            let isValid = true;
            const requiredFields = $('.checkout-step-content.step-' + step)
                .find('[required]');

            requiredFields.each(function() {
                if (!$(this).val()) {
                    isValid = false;
                    $(this).addClass('error');
                } else {
                    $(this).removeClass('error');
                }
            });

            if (!isValid) {
                alert('Please fill in all required fields before proceeding.');
            }

            return isValid;
        }

        // Handle next step button clicks
        $('.next-step').click(function() {
            if (currentStep < totalSteps && validateStep(currentStep)) {
                showStep(currentStep + 1);
            }
        });

        // Handle previous step button clicks
        $('.prev-step').click(function() {
            if (currentStep > 1) {
                showStep(currentStep - 1);
            }
        });

        // Handle coupon code application
        $('button[name="apply_coupon"]').click(function(e) {
            e.preventDefault();
            const coupon = $('#coupon_code').val();
            if (coupon) {
                $('form.checkout_coupon').submit();
            }
        });

        // Initialize first step
        showStep(1);

        // Handle form submission
        $('form.checkout').on('submit', function(e) {
            if (!validateStep(currentStep)) {
                e.preventDefault();
                return false;
            }
        });
    });
    </script>
    <?php
}

// Modify order review section
add_filter('woocommerce_order_review_heading', 'custom_order_review_heading');
function custom_order_review_heading() {
    return 'Booking Summary';
}

// Add refund notice
add_action('woocommerce_review_order_before_submit', 'add_refund_notice');
function add_refund_notice() {
    $refund_date = date('j M Y', strtotime('+7 days'));
    ?>
    <div class="refund-notice">
        <h4>Get a full refund before <?php echo $refund_date; ?></h4>
        <p>Cancel before <?php echo $refund_date; ?> to receive a full refund. Cancellations are quick and can be done online.</p>
    </div>
    <?php
}
