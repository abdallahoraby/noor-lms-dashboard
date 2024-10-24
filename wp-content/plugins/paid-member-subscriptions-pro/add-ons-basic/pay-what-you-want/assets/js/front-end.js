jQuery( function($) {

    $(document).ready( function() {
        //var $pms_form

        // Check subscription plan checkbox when clicking the corresponding price input
        $(document).on("click",".pms-subscription-plan-price input[type='text']", function(){

            $(this).closest('div.pms-subscription-plan').find('input[name="subscription_plans"]').prop('checked', true).trigger('click')

        })

        $('.pms_pwyw_pricing').keyup( function() {

            if( typeof $pms_form == 'undefined' )
                $pms_form = $(this).closest('form')

            let price = $(this).val()

            if ( $.pms_plan_has_signup_fee() )
                price = +price + +$pms_checked_subscription.data( 'sign_up_fee' )

            if ( price == 0 )
                hide_payment_fields( $pms_form )
            else
                show_payment_fields( $pms_form )

            $pms_checked_subscription.data( 'price', $(this).val() )
            $pms_checked_subscription.attr( 'data-price', $(this).val() )

            $(this).closest('div.pms-subscription-plan').find('input[name="subscription_plans"]').trigger('click')

        })

        if ( $('.pms_pwyw_pricing', $pms_checked_subscription.parent()) && $('.pms_pwyw_pricing', $pms_checked_subscription.parent()).val() == '0' ) {

            if( typeof $pms_form == 'undefined' )
                $pms_form = $('.pms_pwyw_pricing').closest('form')

            hide_payment_fields( $pms_form )

        }

        $('.pms-subscription-plan input[type="radio"][name="subscription_plans"]').click(function(){

            if( typeof $pms_form == 'undefined' )
                $pms_form = $(this).closest('form')

            var pwyw_price = $( 'input[name="subscription_price_' + $(this).val() + '"]' ).prop('value')

            //If subscription is not free and discount code field is not empty
            if ( typeof pwyw_price == 'undefined' && $(this).attr( "data-price" ) > 0  )
                show_payment_fields( $pms_form )
            else if ( pwyw_price == '0' )
                hide_payment_fields( $pms_form )
            else if ( pwyw_price > 0 )
                show_payment_fields( $pms_form )
            else if( typeof pwyw_price == 'undefined' ){

                if ( $.pms_plan_is_prorated( $(this) ) && $.pms_checkout_is_recurring( $(this) ) ){
                    show_payment_fields( $pms_form )
                    return
                }

                if ( $(this).data('price') == 0 && !$.pms_plan_has_signup_fee( $(this) ) && !$.pms_plan_has_trial( $(this) ) )
                    hide_payment_fields( $pms_form )

            }

        })
    })

    /**
     * Clones and caches the wrappers for the payment gateways and the credit card / billing information
     * It replaces these wrappers with empy spans that represent the wrappers
     *
     */
     function hide_payment_fields( $form ) {

         if( typeof $form.pms_paygates_wrapper == 'undefined' )
             $form.pms_paygates_wrapper = $form.find('#pms-paygates-wrapper').clone();

         $form.find('#pms-paygates-wrapper').replaceWith('<span id="pms-paygates-wrapper">');

         $form.find('.pms-credit-card-information').hide()

         if( typeof $form.pms_billing_details == 'undefined' ){

             if( typeof PMS_ChosenStrings !== 'undefined' && $.fn.chosen != undefined ){

                 $form.find('#pms_billing_country').chosen('destroy')
                 $form.find('#pms_billing_state').chosen('destroy')

             }

             $form.pms_billing_details = $form.find('.pms-billing-details').clone();

         }

         $form.find('.pms-billing-details').replaceWith('<span class="pms-billing-details">');

     }


     /**
      * It replaces the placeholder spans, that represent the payment gateway and the credit card
      * and billing information, with the cached wrappers that contain the actual fields
      *
      */
     function show_payment_fields( $form ) {

         if( typeof $form.pms_paygates_wrapper != 'undefined' )
             $form.find('#pms-paygates-wrapper').replaceWith( $form.pms_paygates_wrapper );

         if( typeof $pms_checked_paygate != 'undefined' && $pms_checked_paygate.data('type') == 'credit_card' )
             $form.find('.pms-credit-card-information').show()

         if( typeof $form.pms_billing_details != 'undefined' ){

             $form.find('.pms-billing-details').replaceWith( $form.pms_billing_details )

             if( typeof PMS_ChosenStrings !== 'undefined' && $.fn.chosen != undefined ){

                 $form.find('#pms_billing_country').chosen( PMS_ChosenStrings )

                 if( $('#pms_billing_state option').length > 0 )
                     $form.find('#pms_billing_state').chosen( PMS_ChosenStrings )

             }

         }


     }

});
