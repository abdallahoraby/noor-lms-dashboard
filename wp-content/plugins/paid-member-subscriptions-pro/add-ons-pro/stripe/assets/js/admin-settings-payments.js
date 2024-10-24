jQuery(document).ready(function() {

    jQuery('input[type="checkbox"][value="stripe"]').click( function() {

        if ( jQuery(this).is(':checked') )
            jQuery('input[type="checkbox"][value="stripe_intents"]').prop( 'checked', false )
            jQuery('input[type="checkbox"][value="stripe_connect"]').prop('checked', false)
            jQuery( '.pms-stripe-admin-warning' ).show()

    })

    jQuery('input[type="checkbox"][value="stripe_intents"]').click( function() {

        if ( jQuery(this).is(':checked') ){
            jQuery('input[type="checkbox"][value="stripe"]').prop( 'checked', false )
            jQuery('input[type="checkbox"][value="stripe_connect"]').prop('checked', false)
            jQuery( '.pms-stripe-admin-warning' ).hide()
        }

    })

    jQuery('input[type="checkbox"][value="stripe_connect"]').click( function() {

        if ( jQuery(this).is(':checked') ){
            jQuery('input[type="checkbox"][value="stripe"]').prop( 'checked', false )
            jQuery('input[type="checkbox"][value="stripe_intents"]').prop( 'checked', false )
            jQuery( '.pms-stripe-admin-warning' ).hide()
        }

    })

    jQuery('.pms-stripe-connect__disconnect-handler').click(function (e) {

        e.preventDefault()

        var pmsStripeDisconnectPrompt = prompt('Are you sure you want to disconnect this website from Stripe? Payments will not be processed anymore. \nPlease type DISCONNECT in order to remove the Stripe connection:')

        if ( pmsStripeDisconnectPrompt === "DISCONNECT" )
            window.location.replace(jQuery(e.target).attr("href"))
        else
            return false

    })


});
