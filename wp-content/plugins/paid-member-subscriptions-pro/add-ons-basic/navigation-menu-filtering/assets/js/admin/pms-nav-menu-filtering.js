jQuery( function(){

    jQuery(document).on( 'click', '.pms-lilo', function(){
        if( jQuery(this).val() == 'loggedin' ){
            jQuery(this).closest('.pms-options').next('.pms-subscription-plans').find('input').attr('disabled', false);
        }
        else{
            jQuery(this).closest('.pms-options').next('.pms-subscription-plans').find('input').attr('disabled', true);
        }
    });

});