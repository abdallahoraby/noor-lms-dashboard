<?php

// Register Shortcode for Student Registration Form
function custom_student_registration_form() {

    ob_start(); ?>
    <form id="student-registration-form" action="<?php //echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post">
        <div class="ajax-form-loader">
            <div class="loader"></div>
        </div>
        <p class="first-last-names">
            <input type="text" class="userFirstName" name="userFirstName" placeholder="First Name" value="" required>
            <input type="text" class="userLastName" name="userLastName" placeholder="Last Name" value="">
        </p>
        <p>
            <input type="text" class="userName" name="username" placeholder="Username" value="" required>
        </p>
        <p>
            <input type="email" class="userEmail" name="email" placeholder="Email" value="" required>
        </p>
        <p class="gender-select">
            <label for="gender_male">
                <input type="radio" id="gender_male" class="userGender" name="gender" value="male" required checked> Male
            </label>

            <label for="gender_female">
                <input type="radio" id="gender_female" class="userGender" name="gender" value="female" required> Female
            </label>
        </p>
        <p>
            <input type="password" class="userPassword" name="password" placeholder="Password" value="<?php if (isset($_POST['password'])) echo esc_attr($_POST['password']); ?>" required>
        </p>
        <p>
            <input type="password" class="userConfirmPassword" name="password_confirmation" placeholder="Confirm Password" value="<?php if (isset($_POST['password_confirmation'])) echo esc_attr($_POST['password_confirmation']); ?>" required>
        </p>
        <p>
            <input type="submit" name="submit_registration" class="submit_registration" value="Register">
        </p>

        <div class="ajax-response"></div>
        <?php wp_nonce_field('ajax-register-nonce', 'register-security'); ?>
    </form>


    <script>
        jQuery(document).ready(function($) {
            $('.submit_registration').on('click', function(e){
                e.preventDefault();

                // Get the current URL
                let url = new URL(window.location.href);

                let userFirstName = $('.userFirstName').val();
                let userLastName = $('.userLastName').val();
                let userGender = $('.userGender:checked').val();
                let userName = $('.userName').val();
                let userEmail = $('.userEmail').val();
                let userPassword = $('.userPassword').val();
                let userConfirmPassword = $('.userConfirmPassword').val();
                let security = $('#register-security').val();

                // Get all query parameters
                let params = new URLSearchParams(url.search);

                // Get a specific parameter value
                let subscription_plan = params.get("subscription_plan");
                if(!subscription_plan){
                    subscription_plan = '';
                }


                // validate empty inputs
                if(!userFirstName){
                    $('.ajax-response').html('<p class="error">please enter your first name</p>');
                    return false;
                }

                if(!userName){
                    $('.ajax-response').html('<p class="error">please enter username</p>');
                    return false;
                }

                if(!userEmail){
                    $('.ajax-response').html('<p class="error">please enter your email</p>');
                    return false;
                }

                if(!userPassword){
                    $('.ajax-response').html('<p class="error">please enter your password</p>');
                    return false;
                }

                if(!userConfirmPassword){
                    $('.ajax-response').html('<p class="error">please confirm your password</p>');
                    return false;
                }

                $('.ajax-form-loader').addClass('active');

                $.ajax({
                    url: ajax_object.ajax_url, // AJAX URL from wp_localize_script
                    type: 'POST',
                    data: {
                        'action' : 'handle_registration',
                        'userFirstName' : userFirstName,
                        'userLastName' : userLastName,
                        'userGender' : userGender,
                        'userName' : userName,
                        'userEmail' : userEmail,
                        'userPassword' : userPassword,
                        'userConfirmPassword' : userConfirmPassword,
                        'security' : security,
                        'subscription_plan' : subscription_plan
                    },
                    success: function(response) {
                        $('.submit_registration').prop('disabled', true);
                        $('.ajax-form-loader').removeClass('active');
                        response = JSON.parse(response);
                        if (response.loggedin === true) {
                            // do form submission
                            $('.ajax-response').html('<p class="success">Registration is successful, redirecting to dashboard...</p>');
                            if( response.subscription_plan ){
                                // redirect user to subscription page
                                document.location.href = '<?php echo home_url(); ?>/register-membership/?subscription_plan='+ response.subscription_plan +'&single_plan=yes'; // Redirect to subscription page
                            } else {
                                document.location.href = '<?php echo home_url(); ?>'; // Redirect to Dashboard page, after successful login
                            }
                        } else {
                            $('.submit_registration').prop('disabled', false);
                            $('.ajax-response').html('<p class="error">' + response.message + '</p>');
                        }
                        // if (response.success) {
                        //     $('.ajax-response').html(response.data.message);
                        //     setTimeout(function() {
                        //         window.location.reload();
                        //     }, 3000);
                        // } else {
                        //     $('.submit_registration').prop('disabled', false);
                        //     $('.ajax-response').html(response.data.message);
                        // }
                    },
                    error: function() {
                        console.log('[Error]');
                    }
                });
            });
        });
    </script>

    <?php
    return ob_get_clean();
}

// Add the registration form shortcode
add_shortcode('student_registration_form', 'custom_student_registration_form');


// Register Shortcode for Login Form
function custom_login_form() {

    ob_start(); ?>
    <form id="login-form" action="<?php //echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post">
        <div class="ajax-form-loader">
            <div class="loader"></div>
        </div>
        <p>
            <input type="text" name="username" class="username" placeholder="Username or Email" required>
        </p>
        <p>
            <input type="password" name="password" class="password" placeholder="Password" required>
        </p>
        <p>
            <input type="submit" name="submit_login" class="submit_login" value="Login">
        </p>

        <div class="ajax-login-response"></div>
        <?php wp_nonce_field('ajax-login-nonce', 'login-security'); ?>
    </form>

    <script>
        jQuery(document).ready(function($) {
            $('.submit_login').on('click', function(e){
                e.preventDefault();
                let username = $('.username').val();
                let password = $('.password').val();
                let security = $('#login-security').val();

                // Get the current URL
                let url = new URL(window.location.href);

                // Get all query parameters
                let params = new URLSearchParams(url.search);

                // Get a specific parameter value
                let subscription_plan = params.get("subscription_plan");
                if(!subscription_plan){
                    subscription_plan = '';
                }

                if( !username ){
                    $('.ajax-login-response').html('<p class="error">Please enter your username or email.</p>');
                    return false;
                }

                if( !password ){
                    $('.ajax-login-response').html('<p class="error">Please enter your password.</p>');
                    return false;
                }

                $('.ajax-form-loader').addClass('active');

                $.ajax({
                    url: ajax_object.ajax_url, // AJAX URL from wp_localize_script
                    type: 'POST',
                    data: {
                        'action' : 'handle_login',
                        'username' : username,
                        'password' : password,
                        'security': security,
                        'subscription_plan': subscription_plan
                    },
                    success: function(response) {
                        $('.ajax-form-loader').removeClass('active');
                        $('.submit_login').prop('disabled', true);
                        response = JSON.parse(response);
                        if (response.loggedin === true) {
                            // do form submission
                            $('.ajax-login-response').html('<p class="success">Login successful, redirecting...</p>');
                            if( response.subscription_plan ){
                                // redirect user to subscription page
                                document.location.href = '<?php echo home_url(); ?>/register-membership/?subscription_plan='+ response.subscription_plan +'&single_plan=yes'; // Redirect to subscription page
                            } else {
                                document.location.href = '<?php echo home_url(); ?>'; // Redirect to Dashboard page, after successful login
                            }
                        } else {
                            $('.submit_login').prop('disabled', false);
                            $('.ajax-login-response').html('<p class="error">' + response.message + '</p>');
                        }
                    },
                    error: function() {
                        $('.submit_login').prop('disabled', false);
                        console.log('[Error]');
                    }
                });
            });
        });
    </script>

    <?php
    return ob_get_clean();
}

// Add the login form shortcode
add_shortcode('custom_login_form', 'custom_login_form');


// Shortcode to display a logout button with AJAX
function ajax_logout_button_shortcode() {
    ob_start(); ?>

    <a href="<?php echo wp_logout_url(); ?>" title="Logout" id="ajax-logout-btn">
        <i class="fa fa-power-off"></i> Logout
    </a>

    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#ajax-logout-btn').on('click', function(e) {
                e.preventDefault();
                $('.ajax-loader-wrapper').show();
                $.ajax({
                    type: 'POST',
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    data: {
                        action: 'ajaxlogout',
                        security: '<?php echo wp_create_nonce("ajax-logout-nonce"); ?>'
                    },
                    success: function(response) {
                        $('.ajax-loader-wrapper').hide();
                        //response = JSON.parse(response);
                        //if (response.loggedout == true) {
                            window.location.href = '<?php echo home_url('/login-register'); ?>'; // Change the URL to the desired redirect page
                        //} else {
                            //alert('Logout failed!');
                        //}
                    }
                });
            });
        });
    </script>

    <?php
    return ob_get_clean();
}
add_shortcode('ajax_logout_button', 'ajax_logout_button_shortcode');
