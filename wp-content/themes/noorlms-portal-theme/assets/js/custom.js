/* This is your custom Javascript */


jQuery(document).ready(function($) {
    // Trigger the AJAX call when needed (e.g., on button click)
    $('.load-template-part').on('click', function(e) {

        e.preventDefault();
        $(this).addClass('is-active');
        $('nav .u-list li').not(this).removeClass('is-active');
        $('.ajax-loader-wrapper').show();
        let templateName = $(this).data('template-name'); // The name of the template to load

        //var templateName = 'template-part';
        var args = { key1: 'value1', key2: 'value2' }; // Arguments to pass to the template

        // Send the AJAX request
        $.ajax({
            url: ajax_object.ajax_url, // AJAX URL from wp_localize_script
            type: 'POST',
            data: {
                action: 'load_template_part', // The AJAX action we defined in PHP
                template_name: templateName,
                args: args,
                security: ajax_object.nonce // Security nonce
            },
            success: function(response) {
                if (response.success) {
                    // Append the loaded template part to the desired container
                    $('.template-container').html(response.data);
                } else {
                    $('.template-container').html('Try reloading the page.');
                    console.log('[Error loading template part]');
                }
            },
            error: function() {
                console.log('[Error loading template part]');
            },
            complete: function() {
                // Hide loader when the AJAX call is complete
                $('.ajax-loader-wrapper').hide();
            }
        });
    });


    const signUpButton = document.getElementById('signUp');
    const signInButton = document.getElementById('signIn');
    const container = document.getElementById('login-container');

    if( signUpButton ){
        signUpButton.addEventListener('click', () => {
            container.classList.add("right-panel-active");
        });
    }

    if( signInButton ){
        signInButton.addEventListener('click', () => {
            container.classList.remove("right-panel-active");
        });
    }

    // auto renew subscription input
    jQuery('.pms-subscription-plan-auto-renew input').trigger('click');
    jQuery('.pms-subscription-plan-auto-renew label').hide();
    jQuery('.pms-subscription-plan-auto-renew').append('<p class="text-center"> Your subscription will be renewed automatically.  </p>');


    jQuery('form#pms-cancel-subscription-form [name="pms_redirect_back"]').on('click', function (e){
       e.preventDefault();
       // load subscription template
        jQuery('[data-template-name="membership"]').trigger('click');

    });

}); // Document Ready


jQuery(document).on('ajaxComplete', function() {
    // Place your jQuery code here to run after the AJAX content is loaded
    console.log('[AJAX call completed, and jQuery is ready.]');

    // show avatar upload options
    $('a.edit-avatar').on('click',function(){
        $('.avatar-container').toggleClass('show-edit');
    });

    $('#save-practice').on('click', function() {
        var practice_datetime = $('#practice_datetime').val();
        var number_of_practices = $('#number_of_practices').val();

        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'save_practice',
                practice_datetime: practice_datetime,
                number_of_practices: number_of_practices
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                    // toastr.success('Your next practice is scheduled!');
                }
            }
        });
    });


}); // Ajax Ready