jQuery(document).ready(function($){

    var subscription_plan_selector = 'input[name=subscription_plans]'

    var pms_checked_subscription = jQuery( subscription_plan_selector + '[type=radio]' ).length > 0 ? jQuery( subscription_plan_selector + '[type=radio]:checked' ) : jQuery( subscription_plan_selector + '[type=hidden]' )
    var pms_gm_purchase_message = jQuery( '.pms-gm-message__purchase' )
    var pms_gm_extra_fields = jQuery( '.pms-group-memberships-field' )
    var previousSeats = 0;

    seatsMessageDisplay()
    extraFieldsDisplay()

    jQuery( document ).on( 'click', subscription_plan_selector + '[type=radio]', function(e){

        if( jQuery(this).is(':checked') )
            pms_checked_subscription = jQuery(this)

        seatsMessageDisplay()
        extraFieldsDisplay()

    })

    function extraFieldsDisplay(){
        if( isGroupSubscription() ){
            pms_gm_extra_fields.show()
        } else {
            pms_gm_extra_fields.hide()
        }
    }

    function seatsMessageDisplay(){

        if( pms_gm_purchase_message.length == 0 )
            return

        if( isGroupSubscription() ){
            var text  = pms_gm_purchase_message.get(0).textContent
            var seats = pms_checked_subscription.data('seats') - 1

            var replace = '%s'

            if( previousSeats !== 0 )
                replace = previousSeats

            var newText = text.replace( replace, seats )

            previousSeats = seats

            pms_gm_purchase_message.text( newText ).show()
        } else
            pms_gm_purchase_message.hide()
            
    }

    function isGroupSubscription(){
        if( pms_checked_subscription.length >= 1 && typeof pms_checked_subscription.data('seats') !== 'undefined' )
            return true

        if( jQuery( '.pms-form, .wppb-register-user' ).hasClass( 'pms-gm-edit-details' ) )
            return true

        return false
    }

})
