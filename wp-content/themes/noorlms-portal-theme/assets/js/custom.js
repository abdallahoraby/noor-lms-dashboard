/* This is your custom Javascript */


toastr.options = {
    "closeButton": true,
    "debug": false,
    "newestOnTop": true,
    "progressBar": true,
    "positionClass": "toast-top-right",
    "preventDuplicates": true,
    "onclick": null,
    "showDuration": "300",
    "hideDuration": "2000",
    "timeOut": "5000",
    "extendedTimeOut": "1000",
    "showEasing": "swing",
    "hideEasing": "linear",
    "showMethod": "fadeIn",
    "hideMethod": "fadeOut"
}

function loadTemplate(e, btn_data){
    e.preventDefault();
    btn_data.addClass('is-active');
    $('nav .u-list li').not(this).removeClass('is-active');
    $('.ajax-loader-wrapper').show();
    let templateName = btn_data.data('template-name'); // The name of the template to load

    //let templateName = 'template-part';
    let args = { key1: 'value1', key2: 'value2' }; // Arguments to pass to the template

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
}

jQuery(document).ready(function($) {
    // Trigger the AJAX call when needed (e.g., on button click)
    $('.load-template-part').on('click', function(e) {
        let btn_data = $(this);
        loadTemplate(e, btn_data);
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


    // get quiz score on submit
    jQuery('div#learn-press-quiz-app button.lp-button.modal-button-ok').on('click', function (){



        jQuery.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'get_quiz_score',
            },
            success: function(response) {

            }
        });


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
        let practice_date = $('#practice_date').val();
        let practice_minutes = $('input[name="practice_minutes"]:checked').val();

        if( !practice_date ){
            toastr.warning("Please enter practice date");
            return false;
        }

        if(!practice_minutes){
            toastr.warning("Please select practice time");
            return false;
        }

        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'save_practice',
                practice_date: practice_date,
                practice_minutes: practice_minutes
            },
            success: function(response) {
                if (response.success) {
                    toastr.success("Practice Saved Successfully");
                    $('#practice_modal').modal('hide');
                }
            }
        });
    });


    $('.assigned-tasks.practice-btn').on('click', function(e) {

        $('html,body').animate({
                scrollTop: $("#practice-section").offset().top},
            'slow');

    });

    $('.load-template-courses').on('click', function(e) {
        e.preventDefault();
        $('.ajax-loader-wrapper').show();
        $.ajax({
            url: ajax_object.ajax_url, // AJAX URL from wp_localize_script
            type: 'POST',
            data: {
                action: 'load_template_part', // The AJAX action we defined in PHP
                template_name: 'courses',
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

    // Trigger the AJAX call when needed (e.g., on button click)
    $('.load-template-part').on('click', function(e) {
        let btn_data = $(this);
        loadTemplate(e, btn_data);
    });

    $('.open-practice-modal').on('click', function(e) {
       e.preventDefault();
        const practice_modal = document.getElementById('practice_modal');
        const practice_modal_element = new bootstrap.Modal(practice_modal, {
            backdrop: true,
            keyboard: true,
            focus: true
        });
        practice_modal_element.show();
    });

    $('.close-modal-btn').on('click', function(e) {
        e.preventDefault();
        $('#practice_modal').modal('hide');
    });



}); // Ajax Ready