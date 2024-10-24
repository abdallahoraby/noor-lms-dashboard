jQuery( document ).ready(function(){


    /** Show/Hide LearnDash Fields from Subscription Plan settings */
    if ( jQuery('#pms-subscription-learndash').is(':checked') ) {
        jQuery('.pms-meta-box-field-wrapper-learndash').show();
    }
    else {
        jQuery('.pms-meta-box-field-wrapper-learndash').hide();
    }

    jQuery('#pms-subscription-learndash').click(function() {

        if ( jQuery(this).is(':checked')) {
            jQuery('.pms-meta-box-field-wrapper-learndash').show();
        }
        else {
            jQuery('.pms-meta-box-field-wrapper-learndash').hide();
        }
    });


    /** LearnDash Button URL - Copy Button functionality */
    jQuery('.pms_learndash-url__copy').click(function (e) {
        e.preventDefault();

        let inputId = jQuery(this).data('id');
        let inputValue = jQuery('#' + inputId).val();

        navigator.clipboard.writeText(inputValue);

        jQuery(this).text('Copied!');

        let clickTarget = jQuery(this);

        setTimeout(function () {
            clickTarget.text('Copy');
        }, 2500);
    });

});