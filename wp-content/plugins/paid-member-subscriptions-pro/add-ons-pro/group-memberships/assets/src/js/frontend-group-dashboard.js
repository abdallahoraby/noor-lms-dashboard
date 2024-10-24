import List from 'list.js'

jQuery(document).ready(function($){
    if ( jQuery('#pms-members-table .list').length !== 0 ) {

        var membersList = new List( 'pms-members-table', {
            valueNames: [ 'pms-members-list__email', 'pms-members-list__name', 'pms-members-list__status' ],
            page : 10,
            pagination : [{
                paginationClass : 'pms-gm-pagination'
            }],
            fuzzySearch: {
                location  : 0,
                threshold : 0.2,
            }
        })

        if( jQuery('.pms-gm-pagination li').length < 2 )
            jQuery('.pms-gm-pagination').hide()

    }

    jQuery(document).on( 'click', '.pms-members-list__actions .pms-remove', function(event){
        event.preventDefault()

        if( confirm( pms_gm.remove_user_message ) ){
            let data = {}
                data.action          = 'pms_remove_group_membership_member'
                data.security        = pms_gm.remove_group_member_nonce
                data.reference       = jQuery(this).data('reference')
                data.subscription_id = jQuery(this).data('subscription')

            let currentTarget = jQuery(this)

            $.post( pms_gm.ajax_url, data, function( response ){
                response = JSON.parse( response )

                if( response.message ){

                    if(response.message != 'pms_reload'){
                        jQuery( '.pms-members-table__messages' ).text( response.message ).show().addClass( response.status ).fadeOut( 5000 )
                    }

                    if( response.status == 'success' ) {

                        if(response.message == 'pms_reload'){
                            document.location.reload(true);
                        }
                        else{
                            //determine email since we can have an id as well
                            let email = jQuery( '.pms-members-list__email', currentTarget.parents('tr') ).html()

                            membersList.remove( 'pms-members-list__email', email )
                        }
                    }
                }
            })
        }

    })

    jQuery(document).on( 'click', '.pms-members-list__actions .pms-resend', function(event){
        event.preventDefault()

        let data = {}
            data.action          = 'pms_resend_invitation'
            data.security        = pms_gm.resend_group_invitation_nonce
            data.reference       = jQuery(this).data('reference')
            data.subscription_id = jQuery(this).data('subscription')

        $.post( pms_gm.ajax_url, data, function( response ){

            response = JSON.parse( response )

            if( response.message )
                jQuery( '.pms-members-table__messages' ).text( response.message ).show().addClass( response.status ).fadeOut( 5000 )

        })
    })
})
