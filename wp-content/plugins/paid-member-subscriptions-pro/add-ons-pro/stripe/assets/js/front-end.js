jQuery( function( $ ) {

    if( !( jQuery('#stripe-pk').length > 0 ) )
        return false

    var stripe_pk = $( '#stripe-pk' ).val()

    //compatibility with PB conditional logic. if there are multiple subscription plans fields and the first one is hidden then it won't have a value attribute because of conditional logic
    if( typeof stripe_pk == 'undefined' || stripe_pk == '' )
        stripe_pk = $('#stripe-pk').attr('conditional-value')

    if( typeof stripe_pk == 'undefined' )
        return false

    var elementStyles = window.pms_elements_styling ? window.pms_elements_styling : { base: {} }

    var stripe   = Stripe( stripe_pk )
    var elements = stripe.elements()

    var card        = elements.create('card', { style: elementStyles, hidePostalCode : true } )
    var cardIsEmpty = true

    var payment_id    = 0
    var form_location = ''

    var subscription_plan_selector = 'input[name=subscription_plans]'
    var pms_checked_subscription   = jQuery( subscription_plan_selector + '[type=radio]' ).length > 0 ? jQuery( subscription_plan_selector + '[type=radio]:checked' ) : jQuery( subscription_plan_selector + '[type=hidden]' )

    // Show credit card details on the update payment method form
    if ( $( '#pms-update-payment-method-form #pms-stripe-credit-card-details' ).length > 0 ){
        $('.pms-credit-card-information').show()
        $('.pms-billing-details').show()
    }

    if( $('#pms-stripe-credit-card-details').length > 0 )
        card.mount('#pms-stripe-credit-card-details')

    // Paid Member Subscription submit buttons
    var payment_buttons  = 'input[name=pms_register], ';
        payment_buttons += 'input[name=pms_new_subscription], ';
        payment_buttons += 'input[name=pms_change_subscription], ';
        payment_buttons += 'input[name=pms_upgrade_subscription], ';
        payment_buttons += 'input[name=pms_renew_subscription], ';
        payment_buttons += 'input[name=pms_confirm_retry_payment_subscription], ';

    // Profile Builder submit buttons
    payment_buttons += '.wppb-register-user input[name=register]';

    //Regular Charges API
    $(document).on( 'click', payment_buttons, stripePaymentGatewayHandler )
    $(document).on( 'wppb_invisible_recaptcha_success', stripePaymentGatewayHandler )

    // Payment Intents
    //$(document).on( 'click', wppb_payment_buttons, stripeIntentsPaymentGatewayHandler )
    $(document).on( 'wppb_invisible_recaptcha_success', stripeIntentsPaymentGatewayHandler )

    $(document).on('submit', '.pms-form', function (e) {

        if( e.target && ( jQuery( e.target ).attr('id') == 'pms_recover_password_form' || jQuery( e.target ).attr('id') == 'pms_new_password_form' ) )
            return
        
        // Skip if the Go Back button was pressed
        if ( !e.originalEvent || !e.originalEvent.submitter || $(e.originalEvent.submitter).attr('name') != 'pms_redirect_back' ) {

            var target_button = jQuery('input[type="submit"], button[type="submit"]', jQuery(this)).not('#pms-apply-discount').not('input[name="pms_redirect_back"]')

            if ( $(e.originalEvent.submitter).attr('name') == 'pms_update_payment_method' )
                stripeIntentsUpdatePaymentMethod(e, target_button)
            else
                stripeIntentsPaymentGatewayHandler(e, target_button)

        }

    })

    $(document).on('submit', '.wppb-register-user', function (e) {

        if ( !jQuery('.wppb-recaptcha .wppb-recaptcha-element', jQuery(e.currentTarget)).hasClass('wppb-invisible-recaptcha') ) {

            var target_button = jQuery('input[type="submit"], button[type="submit"]', jQuery(this)).not('#pms-apply-discount').not('input[name="pms_redirect_back"]')

            stripeIntentsPaymentGatewayHandler(e, target_button)

        }

    })

    // Handle user payment reauthentication when he lands on the page
    $(document).ready( function() {

        handleCreditCardFields()

        if( $('#pms-auth-form').length == 0 )
            return

        if( form_location == '' )
            form_location = $('#pms-form-location').val()

        var data = $('#pms-auth-form').serializeArray().reduce(function(obj, item) {
            obj[item.name] = item.value;
            return obj;
        }, {});

        data.action = 'pms_reauthenticate_intent'

        $.post( pms.ajax_url, data, function( response ) {
            handleServerResponse( JSON.parse( response ), '' )
        });

        handleCreditCardFields()
    })

    // Comptibility with the other style of credit card fields from PayPal Pro
    $(document).on( 'click', 'input.pms_pay_gate', function(e) {
        handleCreditCardFields()
    })

    // Show credit card form error messages to the user as they happpen
    card.addEventListener( 'change', function( event ) {

        if( event.error ) {
            if( jQuery( '.pms-stripe-error-message' ).length > 0 )
                jQuery( '.pms-stripe-error-message' ).html( event.error.message )
            else
                jQuery( '<div class="pms-stripe-error-message">' + event.error.message + '</div>' ).insertAfter( '#pms-stripe-credit-card-details' )
        } else
            jQuery( '.pms-stripe-error-message' ).html( '' )

        // This is used to add an error that the credit card was not completed
        if( event.empty == false )
            cardIsEmpty = false

    })

    function stripePaymentGatewayHandler(e){

        if( $('input[type=hidden][name=pay_gate]').val() != 'stripe' && $('input[type=radio][name=pay_gate]:checked').val() != 'stripe' )
            return;

        if( $('input[type=hidden][name=pay_gate]').is(':disabled') || $('input[type=radio][name=pay_gate]:checked').is(':disabled') )
            return;

        e.preventDefault();

        // Disable the button
        $(this).attr( 'disabled', true );

        createToken( $(this) );
    }

    function stripeIntentsPaymentGatewayHandler( e, target_button = false ){

        if( $('input[type=hidden][name=pay_gate]').val() != 'stripe_intents' && $('input[type=radio][name=pay_gate]:checked').val() != 'stripe_intents' )
            return

        if( $('input[type=hidden][name=pay_gate]').is(':disabled') || $('input[type=radio][name=pay_gate]:checked').is(':disabled') )
            return

        e.preventDefault()

        removeErrors()

        var current_button = $(this)

        // Current submit button can't be determined from `this` context in case of the Invisible reCaptcha handler
        if( e.type == 'wppb_invisible_recaptcha_success' ){

            // target_button is supplied to the handler starting with version 3.5.0 of Profile Builder, we use this for backwards compatibility
            current_button = target_button == false ? jQuery( 'input[type="submit"]', jQuery( '.wppb-recaptcha-element' ).closest( 'form' ) ) : jQuery( target_button )

        } else if ( e.type == 'submit' ){

            if( target_button != false )
                current_button = jQuery( target_button )

        }

        var selected_plan = jQuery( subscription_plan_selector + '[type=radio]' ).length > 0 ? jQuery( subscription_plan_selector + '[type=radio]:checked' ) : jQuery( subscription_plan_selector + '[type=hidden]' )

        //Disable the button
        current_button.attr( 'disabled', true )

        // Add error if credit card was not completed
        if( cardIsEmpty === true ){
            addValidationErrors([{ target: 'credit_card', message: pms.empty_credit_card_message } ], current_button )
            return
        }

        // grab all data from the form
        var data = $(current_button).closest('form').serializeArray().reduce(function(obj, item) {
            obj[item.name] = item.value
            return obj
        }, {})

        // setup our custom AJAX action and add the current page URL
        data.action       = 'pms_create_payment_intent'
        data.current_page = window.location.href
        data.pms_nonce    = jQuery( '#pms-stripe-ajax-payment-intent-nonce' ).val()
        data.form_type    = jQuery( '.wppb-register-user .wppb-subscription-plans' ).length > 0 ? 'wppb' : jQuery( '.pms-ec-register-form' ).length > 0 ? 'pms_email_confirmation' : 'pms'

        // clear default PMS nonce and cache it in another key so default behaviour doesn't start to process this request
        data.pmstkn_original = data.form_type == 'pms' ? data.pmstkn : 'wppb_register'
        data.pmstkn          = ''

        // add WPPB fields metadata to request if necessary
        if( data.form_type == 'wppb' )
            data.wppb_fields = get_wppb_form_fields( current_button )

        // if user is logged in, set form type to current form
        if( jQuery( 'body' ).hasClass( 'logged-in' ) )
            data.form_type = jQuery('input[type="submit"], button[type="submit"]', jQuery(current_button).closest('form')).not('#pms-apply-discount').not('input[name="pms_redirect_back"]').attr('name')

        // This will be used to create a Setup Intent instead of a Payment Intent
        // in case of a trial subscription
        if( typeof selected_plan.data('trial') != 'undefined' && selected_plan.data('trial') == '1' )
            data.setup_intent = true
        // If a 100% discount code is used, initial amount will be 0
        else if( jQuery( 'input[name="discount_code"]' ).length > 0 && typeof selected_plan.data('price') != 'undefined' && selected_plan.data('price') == '0' )
            data.setup_intent = true
        // Pro-rated subscriptions
        else if ( $.pms_plan_is_prorated(selected_plan) && typeof selected_plan.data('price') != 'undefined' && selected_plan.data('price') == '0' )
            data.setup_intent = true

        // Recaptcha Compatibility
        // If reCaptcha field was not validated, don't send data to the server
        if( typeof data['g-recaptcha-response'] != 'undefined' && data['g-recaptcha-response'] == '' ){

            if( data.form_type == 'wppb' )
                addWPPBValidationErrors( { recaptcha : { field : 'recaptcha', error : '<span class="wppb-form-error">This field is required</span>' } }, current_button )
            else
                addValidationErrors( { 'recaptcha-register' : { target : 'recaptcha-register', message : 'Please complete the reCaptcha.' } }, current_button )

            stripeResetSubmitButton( current_button )

            return

        }

        // Make request to create PaymentIntent
        $.post( pms.ajax_url, data, function( response ) {

            if( response ){
                response = JSON.parse( response )

                if( response.success == true && response.intent_secret ){

                    // Handle card setup for a trial subscription
                    if( data.setup_intent && data.setup_intent === true ){

                        stripe.handleCardSetup( response.intent_secret, card, { payment_method_data : { billing_details : pms_stripe_get_billing_details() } } ).then(function(result) {
                            let token

                            if( result.error && result.error.decline_code && result.error.decline_code == 'live_mode_test_card' ){
                                let errors = [ { target : 'credit_card', message : result.error.message } ]

                                addValidationErrors( errors, current_button )
                            } else if( result.error && result.error.type && result.error.type == 'validation_error' )
                                stripeResetSubmitButton( current_button )
                            else {
                                if ( result.error && result.error.setup_intent )
                                    token = { id : result.error.setup_intent.id }
                                else if( result.setupIntent )
                                    token = { id : result.setupIntent.payment_method }
                                else
                                    token = ''

                                stripeTokenHandler( token, $(current_button).closest('form') )
                            }
                        })

                    // Take the payment if there's no trial
                    } else {

                        // Prompt the user when leaving the page once the payment request has started
                        var paymentRequestStarted = true

                        window.addEventListener('beforeunload', (event) => {
                            if ( paymentRequestStarted )
                                event.returnValue = 'Payment is processing, do not close the page'
                        })

                        stripe.confirmCardPayment( response.intent_secret, {
                            payment_method: {
                                card            : card,
                                billing_details : pms_stripe_get_billing_details()
                            },
                            setup_future_usage : 'off_session'
                        }).then(function(result) {

                            paymentRequestStarted = false

                            if( result.error){

                                addValidationErrors( [{  target: 'credit_card', message: result.error.message } ], current_button )

                            } else if( result.paymentIntent && result.paymentIntent.status == 'succeeded' ){

                                // Add PaymentIntent ID to form so we can verify that the payment is already processed
                                $form = $(current_button).closest('form')

                                $form.append( $('<input type="hidden" name="payment_intent" />').val( result.paymentIntent.id ) )

                                stripeTokenHandler( { id: result.paymentIntent.payment_method }, $form )

                            }

                        })

                    }

                // Error handling
                } else if( response.success == false ){

                    // Paid Member Subscription forms
                    if( response.data && ( data.form_type == 'pms' || data.form_type == 'pms_email_confirmation' ) ){
                        addValidationErrors( response.data, current_button )
                    // Profile Builder form
                    } else {

                        // Add PMS related errors (Billing Fields)
                        // These are added first because the form will scroll to the error and these
                        // are always placed at the end of the WPPB form
                        if( response.pms_errors.length > 0 )
                            addValidationErrors( response.pms_errors, current_button )

                        // Add WPPB related errors
                        if( typeof response.wppb_errors == 'object' )
                            addWPPBValidationErrors( response.wppb_errors, current_button )

                    }

                    jQuery(document).trigger( 'pms_checkout_validation_error', response, current_button )

                } else {
                    console.log( 'something unexpected happened' )
                }

            }

        })

    }

    function stripeIntentsUpdatePaymentMethod( e, target_button = false ){

        e.preventDefault()

        removeErrors()

        var current_button = $(this)

        if ( target_button != false )
            current_button = jQuery( target_button )

        //Disable the button
        current_button.attr('disabled', true)

        // Add error if credit card was not completed
        if (cardIsEmpty === true) {
            addValidationErrors([{ target: 'credit_card', message: pms.empty_credit_card_message }], current_button)
            return
        }

        data           = {}
        data.action    = 'pms_update_payment_method'
        data.pms_nonce = jQuery('#pms-stripe-ajax-update-payment-method-nonce').val()

        $.post(pms.ajax_url, data, function (response) {

            if (response) {
                response = JSON.parse(response)

                if ( response.success == true && response.intent_secret ){

                    stripe.handleCardSetup(response.intent_secret, card, { payment_method_data: { billing_details: pms_stripe_get_billing_details() } }).then(function (result) {
                        let token

                        if (result.error && result.error.decline_code && result.error.decline_code == 'live_mode_test_card') {
                            let errors = [{ target: 'credit_card', message: result.error.message }]

                            addValidationErrors(errors, current_button)
                        } else if (result.error && result.error.type && result.error.type == 'validation_error')
                            stripeResetSubmitButton(current_button)
                        else {
                            if (result.error && result.error.setup_intent)
                                token = { id: result.error.setup_intent.id }
                            else if (result.setupIntent)
                                token = { id: result.setupIntent.payment_method }
                            else
                                token = ''

                            stripeTokenHandler(token, $(current_button).closest('form'))
                        }

                    })
                }

            }

        })
        
    }

    /*
     * Stripe response handler
     *
     */
    function stripeTokenHandler( token, $form = null ) {

        if( $form === null )
            $form = $(payment_buttons).closest('form')

        $form.append( $('<input type="hidden" name="stripe_token" />').val( token.id ) )

        // We have to append a hidden input to the form to simulate that the submit
        // button has been clicked to have it to the $_POST
        var button_name = $form.find('input[type="submit"], button[type="submit"]').not('#pms-apply-discount').not('input[name="pms_redirect_back"]').attr('name')
        var button_value = $form.find('input[type="submit"], button[type="submit"]').not('#pms-apply-discount').not('input[name="pms_redirect_back"]').val()

        $form.append( $('<input type="hidden" />').val( button_value ).attr('name', button_name ) )

        $form.get(0).submit()

    }

    function createToken( payment_button ){
        stripe.createToken(card).then(function(result) {
            if( result.error )
                stripeResetSubmitButton( payment_button )
            else
                stripeTokenHandler( result.token )
        })
    }

    function stripeResetSubmitButton( target ) {

        setTimeout( function() {
            target.attr( 'disabled', false ).removeClass( 'pms-submit-disabled' ).val( target.data( 'original-value' ) ).blur()

            if ( $( target ).is('button') )
                $( target ).text( target.data('original-value') )

        }, 1 )

    }

    function handleServerResponse( response, payment_button ){
        //console.log( response )

        if( payment_id == 0 )
            payment_id = response.payment_id

        if( form_location == '' )
            form_location = response.form_location

        if( response.validation_errors )
            addValidationErrors( response.errors, payment_button )
        else if ( response.error ) {
            //console.log( 'error' )

            // If error, redirect to payment failed page
            if( response.redirect_url )
                window.location.replace( response.redirect_url )

        } else if ( response.requires_action ) {
            //console.log( 'requires auth ')

            // Use Stripe.js to handle required card action
            stripe.handleCardAction(
                response.payment_intent_client_secret
            ).then(function(result) {
                if ( result.error ) {
                    //console.log( '3D Secure confirmation failed' )

                    //send error data to server
                    var data                    = {}
                        data.action             = 'pms_failed_payment_authentication'
                        data.payment_id         = payment_id
                        data.form_location      = form_location
                        data.current_page       = window.location.href
                        data.error              = result.error
                        data.subscription_plans = pms_checked_subscription.val()
                        data.pms_recurring      = getAutoRenewalStatus()

                    $.post( pms.ajax_url, data, function( response ) {
                        handleServerResponse( JSON.parse( response ), payment_button );
                    });

                } else {
                    // console.log( 'payment intent needs to be confirmed again on the server' )
                    // console.log( result )

                    var data                    = {}
                        data.action             = 'pms_confirm_payment_intent'
                        data.stripe_token       = result.paymentIntent.id
                        data.payment_id         = payment_id
                        data.form_location      = form_location
                        data.current_page       = window.location.href
                        data.subscription_plans = pms_checked_subscription.val()
                        data.pms_recurring      = getAutoRenewalStatus()

                    $.post( pms.ajax_url, data, function( response ) {
                        handleServerResponse( JSON.parse( response ), payment_button );
                    });
                }
            });
        } else {
            //console.log( 'success' )

            //create a dummy form and submit it
            var redirect_url = ''

            if( !response.redirect_url )
                redirect_url = window.location.href
            else
                redirect_url = response.redirect_url

            var form = $('<form action="' + redirect_url + '" method="post">' +
                        '<input type="text" name="pms_register" value="1" /></form>')

            $('body').append(form)
                form.submit()
        }

    }

    function addValidationErrors( errors, payment_button ){
        var scrollLocation = '';

        $.each( errors, function(index, value){

            if( value.target == 'form_general' ){
                $.pms_add_general_error( value.message )

                scrollLocation = '.pms-form'
            } else if( value.target == 'subscription_plan' || value.target == 'subscription_plans' ){
                $.pms_add_subscription_plans_error( value.message )

                if( scrollLocation == '' )
                    scrollLocation = '.pms-field-subscriptions'
            } else if( value.target == 'credit_card' ){
                $.pms_stripe_add_credit_card_error( value.message )

                if( scrollLocation == '' )
                    scrollLocation = '#pms-paygates-wrapper'
            } else if( value.target == 'recaptcha-register'){

                $field_wrapper = jQuery( '#pms-recaptcha-register-wrapper', jQuery( payment_button ).closest( 'form' ) );

                error = '<p>' + value.message + '</p>';

                if( $field_wrapper.find('.pms_field-errors-wrapper').length > 0 )
                    $field_wrapper.find('.pms_field-errors-wrapper').html( error );
                else
                    $field_wrapper.append('<div class="pms_field-errors-wrapper pms-is-js">' + error + '</div>');


            } else {
                $.pms_add_field_error( value.message, value.target )

                if( scrollLocation == '' && value.target.indexOf('pms_billing') !== -1 )
                    scrollLocation = '.pms-billing-details'
                else
                    scrollLocation = '.pms-form'
            }

        })

        if( jQuery(payment_button).attr('name') == 'pms_update_payment_method' && scrollLocation == '#pms-paygates-wrapper' )
            scrollLocation = '#pms-credit-card-information';
            
        scrollTo( scrollLocation, payment_button )
    }

    function removeErrors(){
        jQuery('.pms_field-errors-wrapper').remove()

        if ( jQuery('.pms-stripe-error-message').length > 0 )
            jQuery('.pms-stripe-error-message').remove()

        if( jQuery( '.wppb-register-user' ).length > 0 ){

            jQuery( '.wppb-form-error' ).remove()

            jQuery( '.wppb-register-user .wppb-form-field' ).each( function(){

                jQuery(this).removeClass( 'wppb-field-error' )

            })

        }
    }

    function scrollTo( scrollLocation, payment_button ){
        var form = $(scrollLocation)[0]

        if( typeof form == 'undefined' ){
            stripeResetSubmitButton( payment_button )
            return
        }

        var coord  = form.getBoundingClientRect().top + window.pageYOffset
        var offset = -170

        window.scrollTo({
            top      : coord + offset,
            behavior : 'smooth'
        })

        stripeResetSubmitButton( payment_button )

    }

    function getAutoRenewalStatus(){
        if( jQuery( 'input[name="pms_default_recurring"]' ).val() == 2 )
            return 1

        if( pms_checked_subscription.data( 'recurring' ) == 2 )
            return 1

        if( jQuery( 'input[name="pms_recurring"]' ).is(':visible') && jQuery( 'input[name="pms_recurring"]' ).is(':checked') )
            return 1

        if( !jQuery( 'input[name="pms_recurring"]' ).is(':visible') && jQuery( 'input[name="intent_id"]' ).length > 0 )
            return 1

        return 0
    }

    function handleCreditCardFields(){

        if( jQuery( '.pms_pay_gate:checked' ).val() == 'paypal_pro' ){

            jQuery('#pms_card_number').attr( 'name', 'pms_card_number' )
            jQuery('#pms_card_cvv').attr( 'name', 'pms_card_cvv' )
            jQuery('#pms_card_exp_month').attr( 'name', 'pms_card_exp_month' )
            jQuery('#pms_card_exp_year').attr( 'name', 'pms_card_exp_year' )

            jQuery( '#pms-stripe-credit-card-details' ).hide()
            jQuery( '#pms-credit-card-information li:not(.pms-field-type-heading)' ).show()

        } else if( jQuery( '.pms_pay_gate:checked' ).val() == 'stripe_intents' || jQuery( '.pms_pay_gate:checked' ).val() == 'stripe' ){

            jQuery('#pms_card_number').removeAttr( 'name' )
            jQuery('#pms_card_cvv').removeAttr( 'name' )
            jQuery('#pms_card_exp_month').removeAttr( 'name' )
            jQuery('#pms_card_exp_year').removeAttr( 'name' )

            jQuery( '#pms-credit-card-information li:not(.pms-field-type-heading)' ).hide()
            jQuery( '#pms-stripe-credit-card-details' ).show()

        }

    }

    function pms_stripe_get_billing_details() {

        var data = {}

        var email = jQuery( '.pms-form input[name="user_email"], .wppb-user-forms input[name="email"]' ).val()

        if( typeof email != 'undefined' && email != '' )
            data.email = email.replace(/\s+/g, '') // remove any whitespace that might be present in the email

        var name = ''

        if( jQuery( '.pms-billing-details input[name="pms_billing_first_name"]' ).length > 0 )
            name = name + jQuery( '.pms-billing-details input[name="pms_billing_first_name"]' ).val() + ' '
        else if( jQuery( '.pms-form input[name="first_name"], .wppb-user-forms input[name="first_name"]' ).length > 0 )
            name = name + jQuery( '.pms-form input[name="first_name"], .wppb-user-forms input[name="first_name"]' ).val() + ' '

        if( jQuery( '.pms-billing-details input[name="pms_billing_last_name"]' ).length > 0 )
            name = name + jQuery( '.pms-billing-details input[name="pms_billing_last_name"]' ).val()
        else if( jQuery( '.pms-form input[name="last_name"], .wppb-user-forms input[name="last_name"]' ).length > 0 )
            name = name + jQuery( '.pms-form input[name="last_name"], .wppb-user-forms input[name="last_name"]' ).val()

        if( name.length > 1 )
            data.name = name

        if( jQuery( '.pms-billing-details ').length > 0 ){

            data.address = {
                city        : jQuery( '.pms-billing-details input[name="pms_billing_city"]' ).val(),
                country     : jQuery( '.pms-billing-details input[name="pms_billing_country"]' ).val(),
                line1       : jQuery( '.pms-billing-details input[name="pms_billing_address"]' ).val(),
                postal_code : jQuery( '.pms-billing-details input[name="pms_billing_zip"]' ).val(),
                state       : jQuery( '.pms-billing-details input[name="pms_billing_state"]' ).val()
            }

        }

        return data

    }

    $.pms_stripe_add_credit_card_error = function( error ) {

        if( error == '' || error == 'undefined' )
            return false

        $field_wrapper  = $('#pms-credit-card-information');

        error = '<p>' + error + '</p>'

        if( $field_wrapper.find('.pms_field-errors-wrapper').length > 0 )
            $field_wrapper.find('.pms_field-errors-wrapper').html( error )
        else
            $field_wrapper.append('<div class="pms_field-errors-wrapper pms-is-js">' + error + '</div>')

    };

    function get_wppb_form_fields( current_button ){

        var fields = {}

        // Taken from Multi Step Forms
        jQuery( 'li.wppb-form-field', jQuery(current_button).closest('form') ).each( function() {

            if( jQuery( this ).attr( 'class' ).indexOf( 'heading' ) == -1 && jQuery( this ).attr( 'class' ).indexOf( 'wppb_billing' ) == -1
                && jQuery( this ).attr( 'class' ).indexOf( 'wppb_shipping' ) == -1 && jQuery( this ).attr( 'class' ).indexOf( 'wppb-shipping' ) == -1 ) {

                var meta_name;

                if( jQuery( this ).hasClass( 'wppb-repeater' ) || jQuery( this ).parent().attr( 'data-wppb-rpf-set' ) == 'template' || jQuery( this ).hasClass( 'wppb-recaptcha' ) ) {
                    return true;
                }

                if( jQuery( this ).hasClass( 'wppb-send-credentials-checkbox' ) )
                    return true;

                /* exclude conditional required fields */
                if( jQuery( this ).find('[conditional-value]').length !== 0 ) {
                    return true;
                }

                fields[jQuery( this ).attr( 'id' )] = {};
                fields[jQuery( this ).attr( 'id' )]['class'] = jQuery( this ).attr( 'class' );

                if( jQuery( this ).hasClass( 'wppb-woocommerce-customer-billing-address' ) ) {
                    meta_name = 'woocommerce-customer-billing-address';
                } else if( jQuery( this ).hasClass( 'wppb-woocommerce-customer-shipping-address' ) ) {
                    meta_name = 'woocommerce-customer-shipping-address';

                    if ( !jQuery('.wppb-woocommerce-customer-billing-address #woo_different_shipping_address', jQuery(current_button).closest('form') ).is( ':checked' ) ) {
                        return true;
                    }
                } else {
                    meta_name = jQuery( this ).find( 'label' ).attr( 'for' );

                    //fields[jQuery( this ).attr( 'id' )]['required'] = jQuery( this ).find( 'label' ).find( 'span' ).attr( 'class' );
                    fields[jQuery( this ).attr( 'id' )]['title'] = jQuery( this ).find( 'label' ).first().text().trim();
                }

                fields[jQuery( this ).attr( 'id' )]['meta-name'] = meta_name;

                if( jQuery( this ).parent().parent().attr( 'data-wppb-rpf-meta-name' ) ) {
                    var repeater_group = jQuery( this ).parent().parent();

                    fields[jQuery( this ).attr( 'id' )]['extra_groups_count'] = jQuery( repeater_group ).find( '#' + jQuery( repeater_group ).attr( 'data-wppb-rpf-meta-name' ) + '_extra_groups_count' ).val();
                }

                if( jQuery( this ).hasClass( 'wppb-woocommerce-customer-billing-address' ) ) {
                    var woo_billing_fields_fields = {};

                    jQuery('ul.wppb-woo-billing-fields li.wppb-form-field', jQuery(current_button).closest('form') ).each( function() {
                        if( ! jQuery( this ).hasClass( 'wppb_billing_heading' ) ) {
                            woo_billing_fields_fields[jQuery( this ).find( 'label' ).attr( 'for' )] = jQuery( this ).find( 'label' ).text();
                        }
                    } );

                    fields[jQuery( this ).attr( 'id' )]['fields'] = woo_billing_fields_fields;
                }

                if( jQuery( this ).hasClass( 'wppb-woocommerce-customer-shipping-address' ) ) {
                    var woo_shipping_fields_fields = {};

                    jQuery('ul.wppb-woo-shipping-fields li.wppb-form-field', jQuery(current_button).closest('form') ).each( function() {
                        if( ! jQuery( this ).hasClass( 'wppb_shipping_heading' ) ) {
                            woo_shipping_fields_fields[jQuery( this ).find( 'label' ).attr( 'for' )] = jQuery( this ).find( 'label' ).text();
                        }
                    } );

                    fields[jQuery( this ).attr( 'id' )]['fields'] = woo_shipping_fields_fields;
                }
            }
        } )

        return fields

    }

    // Taken from MultiStep Forms code
    function addWPPBValidationErrors( errors, current_button ){

        let form   = $(current_button).closest('form')
        let scroll = false

        jQuery.each( errors, function( key, value ) {

            var meta_name

            if( value['type'] !== undefined && value['type'] == 'woocommerce' )
                meta_name = jQuery( form ).find( '.wppb_' +  value['field'] )
            else
                meta_name = jQuery( form).find( '.wppb-' +  value['field'] )

            if( meta_name.length > 1 ) {
                jQuery.each( meta_name, function( key2, value2 ) {
                    if( jQuery( value2, form ).find( 'label' ).attr( 'for' ) == key ) {
                        jQuery( jQuery( value2, form ) ).addClass( 'wppb-field-error' ).append( value['error'] )
                    }
                })
            } else {
                meta_name.addClass( 'wppb-field-error' ).append( value['error'] )
            }

            scroll = true

        })

        if( scroll )
            scrollTo( '.wppb-register-user', current_button )

    }

});
