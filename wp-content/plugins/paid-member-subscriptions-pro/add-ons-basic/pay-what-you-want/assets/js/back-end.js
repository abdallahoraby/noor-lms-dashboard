jQuery( function($) {

    $(document).ready( function() {

        if ( $('#pms-subscription-plan-pay-what-you-want').is(':checked') ) {

            $('.pms-meta-box-field-wrapper-pwyw').show();

        }


        $('#pms-subscription-plan-pay-what-you-want').click(function() {

            if ( $(this).is(':checked')) {
                $('.pms-meta-box-field-wrapper-pwyw').show();
            }
            else {
                $('.pms-meta-box-field-wrapper-pwyw').hide();
            }
        });

    });

});

