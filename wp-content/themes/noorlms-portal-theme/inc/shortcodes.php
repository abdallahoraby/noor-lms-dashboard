<?php

// Register Shortcode for Student Registration Form
function custom_student_registration_form() {
    if (is_user_logged_in()) {
        return '<p>You are already registered and logged in.</p>';
    }

    ob_start(); ?>
    <form id="student-registration-form" action="<?php //echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post">
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
    </form>


    <script>
        jQuery(document).ready(function($) {
            $('.submit_registration').on('click', function(e){
                e.preventDefault();
                let userFirstName = $('.userFirstName').val();
                let userLastName = $('.userLastName').val();
                let userGender = $('.userGender:checked').val();
                let userName = $('.userName').val();
                let userEmail = $('.userEmail').val();
                let userPassword = $('.userPassword').val();
                let userConfirmPassword = $('.userConfirmPassword').val();
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
                    },
                    success: function(response) {
                        if (response.success) {
                            $('.ajax-response').html(response.data.message);
                            setTimeout(function() {
                                window.location.reload();
                            }, 3000);
                        } else {
                            $('.ajax-response').html(response.data.message);
                        }
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
    if (is_user_logged_in()) {
        return '<p>You are already logged in.</p>';
    }

    ob_start(); ?>
    <form id="login-form" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post">
        <p>
            <input type="text" name="username" placeholder="Username or Email" required>
        </p>
        <p>
            <input type="password" name="password" placeholder="Password" required>
        </p>
        <p>
            <input type="submit" name="submit_login" value="Login">
        </p>
    </form>
    <?php
    return ob_get_clean();
}

// Add the login form shortcode
add_shortcode('custom_login_form', 'custom_login_form');
