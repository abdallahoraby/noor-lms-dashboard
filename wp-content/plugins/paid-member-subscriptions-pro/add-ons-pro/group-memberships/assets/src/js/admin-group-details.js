jQuery(document).ready(function($){

    let groupName            = jQuery( '.group-info td.group_name' )
    let groupSeats           = jQuery( '.group-info td.seats' )
    let groupDescription     = jQuery( '.pms-group-info .pms-group-description' )
    let groupActions         = jQuery( '.group-info td.actions' )
    let messagesHolder       = jQuery( '.pms-group-wrap .pms-admin-notice' )

    jQuery(document).on( 'click', '.group-info .row-actions #edit', function(event){
        event.preventDefault()

        jQuery( 'span', groupName ).hide()
        jQuery( 'input', groupName ).show()

        jQuery( 'span', groupSeats ).hide()
        jQuery( 'input', groupSeats ).show()

        jQuery( 'p', groupDescription ).hide()
        jQuery( 'textarea', groupDescription ).show()

        jQuery( '#edit', groupActions ).hide()
        jQuery( '#save', groupActions ).css( 'display', 'inline-block' )

    })

    jQuery(document).on( 'click', '.group-info .row-actions #save', function(event){
        event.preventDefault()

        jQuery(this).pms_addSpinner( 200 )

        let data = {}
            data.action            = 'pms_edit_group_details'
            data.security          = pms_gm.edit_group_details_nonce
            data.group_name        = jQuery( 'input', groupName ).val()
            data.group_description = jQuery( 'textarea', groupDescription ).val()
            data.seats             = jQuery( 'input', groupSeats ).val()
            data.owner_id          = jQuery( '.pms-group-info #pms-owner-id' ).val()

        $.post( pms_gm.ajax_url, data, function( response ){

            response = JSON.parse( response )

            if( response.message ){
                if( response.status == 'error' )
                    messagesHolder.addClass( 'error' )
                else
                    messagesHolder.addClass( 'updated' )

                jQuery( 'p', messagesHolder ).text( response.message )

                jQuery(this).pms_removeSpinner( 200 )

                messagesHolder.show()

                if( response.status == 'success' ){
                    jQuery( 'span', groupName ).text( jQuery( 'input', groupName ).val() )
                    jQuery( '.pms-group-info h3').text( jQuery( 'input', groupName ).val() )

                    jQuery( 'span', groupName ).show()
                    jQuery( 'input', groupName ).hide()

                    jQuery( 'span', groupSeats ).text( jQuery( 'input', groupSeats ).val() )

                    jQuery( 'span', groupSeats ).show()
                    jQuery( 'input', groupSeats ).hide()

                    jQuery( 'p', groupDescription ).text( jQuery( 'textarea', groupDescription ).val() )

                    jQuery( 'p', groupDescription ).show()
                    jQuery( 'textarea', groupDescription ).hide()

                    jQuery( '#save', groupActions ).hide()
                    jQuery( '#edit', groupActions ).css( 'display', 'inline-block' )
                }

            }
        })

    })

    jQuery(document).on( 'click', '.group-members .members-list-row-actions #resend', function(event){
        event.preventDefault()

        jQuery(this).pms_addSpinner( 200 )

        let data = {}
            data.action          = 'pms_resend_invitation'
            data.security        = pms_gm.resend_group_invitation_nonce
            data.reference       = jQuery(this).data('reference')
            data.subscription_id = jQuery(this).data('subscription')

        $.post( pms_gm.ajax_url, data, function( response ){
            response = JSON.parse( response )

            if( response.message ){
                if( response.status == 'error' )
                    messagesHolder.addClass( 'error' )
                else
                    messagesHolder.addClass( 'updated' )

                jQuery( 'p', messagesHolder ).text( response.message )

                jQuery(this).pms_removeSpinner( 200 )

                messagesHolder.show()
            }
        })
    })

    jQuery(document).on( 'click', '.members-list-row-actions input[type="submit"]', function(event){

        if( !confirm( pms_gm.remove_user_message ) )
            return false;

    })

    /**
     * Adds a spinner after the element
     */
    $.fn.pms_addSpinner = function( animation_speed ) {

        if( typeof animation_speed == 'undefined' )
            animation_speed = 100

        $this = $(this)

        if( $this.siblings('.spinner').length == 0 )
            $this.after('<div class="spinner"></div>')

        $spinner = $this.siblings('.spinner')
        $spinner.css('visibility', 'visible').animate({opacity: 1}, animation_speed )

    };


    /**
     * Removes the spinners next to the element
     */
    $.fn.pms_removeSpinner = function( animation_speed ) {

        if( typeof animation_speed == 'undefined' )
            animation_speed = 100

        if( $this.siblings('.spinner').length > 0 ) {

            $spinner = $this.siblings('.spinner')
            $spinner.animate({opacity: 0}, animation_speed )

            setTimeout( function() {
                $spinner.remove()
            }, animation_speed )

        }

    }

})
