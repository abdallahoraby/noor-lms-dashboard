<?php

/*
 * an AJAX action in WordPress that dynamically loads a template part with arguments
 */

function load_template_part_via_ajax() {
    // Check if the AJAX request has sent arguments
    if ( isset($_POST['template_name']) ) {
        $template_name = sanitize_text_field($_POST['template_name']);
        $args = $_POST['args']; // You can further sanitize or validate this if necessary

        // Use locate_template to find the template and pass arguments
        ob_start();
        get_template_part('template-parts/template-' . $template_name, null, $args);
        $output = ob_get_clean();


        // Return the template part output as the AJAX response
        wp_send_json_success( $output );
    } else {
        wp_send_json_error( 'Invalid request' );
    }


}
add_action( 'wp_ajax_load_template_part', 'load_template_part_via_ajax' );
add_action( 'wp_ajax_nopriv_load_template_part', 'load_template_part_via_ajax' );


/*
 * an AJAX action in WordPress that handle users registration
 */

function handle_registration() {
    // Check the nonce for security
    check_ajax_referer('ajax-register-nonce', 'security');

    // Sanitize and validate the user inputs
    wp_enqueue_script('lottie-script');
    $lottie_src = get_stylesheet_directory_uri(). '/assets/lotties/check-mark.json';


    $userFirstName = sanitize_text_field($_POST['userFirstName']);
    $userLastName = sanitize_text_field($_POST['userLastName']);
    $userGender = sanitize_text_field($_POST['userGender']);
    $userName = sanitize_text_field($_POST['userName']);
    $userEmail = sanitize_text_field($_POST['userEmail']);
    $userPassword = sanitize_text_field($_POST['userPassword']);
    $userConfirmPassword = sanitize_text_field($_POST['userConfirmPassword']);


    // Form validation
    if (empty($userFirstName)) {
        echo json_encode(array('loggedin' => false, 'message' => __(' Please enter first name')));
    } elseif ( !is_email($userEmail) || empty($userEmail) ){
        echo json_encode(array('loggedin' => false, 'message' => __(' Please enter valid email ')));
    } elseif ( empty($userPassword) || empty($userConfirmPassword) ) {
        echo json_encode(array('loggedin' => false, 'message' => __('Passwords is empty or do not match.')));
    } elseif ($userPassword !== $userConfirmPassword) {
        echo json_encode(array('loggedin' => false, 'message' => __('Passwords do not match.')));
    } else {

        if ( email_exists($userEmail)) {
            echo json_encode(array('loggedin' => false, 'message' => __('Email already exists.')));
        } elseif (username_exists($userName)) {
            echo json_encode(array('loggedin' => false, 'message' => __('Username already exists.')));
        } else {
            // Register the user
            $user_id = wp_create_user($userName, $userPassword, $userEmail);
            if (!is_wp_error($user_id)) {
                // Assign "student" role
                wp_update_user(
                    array(
                        'ID' => $user_id,
                        'role' => 'student',
                        'first_name' => $userFirstName,
                        'last_name' => $userLastName,
                        'gender' => $userGender,
                    )
                );

                // Auto-login after registration
//                wp_send_json_success(array(
//                    'success' => true,
//                    'message' => '<div class="success-login"> <lottie-player src="'.$lottie_src.'"  background="transparent"  speed="1"  style="width: 70px; height: 70px;"  loop autoplay></lottie-player> You are now successfully registered. Please login </div>'
//                ));

                // Get the username and password from the AJAX request
                $info = array();
                $info['user_login'] = $userName;
                $info['user_password'] = $userPassword;
                $info['remember'] = true;


                // Attempt to sign the user in
                $user_signon = wp_signon($info, false);

                if (is_wp_error($user_signon)) {
                    // Return an error if the login fails
                    echo json_encode(array('loggedin' => false, 'message' => __('Wrong username or password.')));
                } else {
                    // Return success if login is successful
                    echo json_encode(array('loggedin' => true, 'message' => __('Login successful.')));
                }

            } else {
                $error_string = $user_id->get_error_message();
                echo json_encode(array('loggedin' => false, 'message' => __($error_string)));
            }
        }
    }

    wp_die();

}
add_action( 'wp_ajax_handle_registration', 'handle_registration' );
add_action( 'wp_ajax_nopriv_handle_registration', 'handle_registration' );


/*
 * an AJAX action in WordPress that handle users login
 */

function handle_login() {
    // Check the nonce for security
    check_ajax_referer('ajax-login-nonce', 'security');
    $username = sanitize_text_field($_POST['username']);
    $password = sanitize_text_field($_POST['password']);

    // Get the username and password from the AJAX request
    $info = array();
    $info['user_login'] = $username;
    $info['user_password'] = $password;
    $info['remember'] = true;


    // Attempt to sign the user in
    $user_signon = wp_signon($info, false);


    if (is_wp_error($user_signon)) {
        // Return an error if the login fails
        echo json_encode(array('loggedin' => false, 'message' => __('Wrong username or password.')));
    } else {
        // Return success if login is successful
        echo json_encode(array('loggedin' => true, 'message' => __('Login successful.')));
    }

    wp_die(); // Required to terminate the request


    wp_die();


}
add_action( 'wp_ajax_handle_login', 'handle_login' );
add_action( 'wp_ajax_nopriv_handle_login', 'handle_login' );


// Handle AJAX logout request
function ajax_logout() {
    // Check the nonce for security
    check_ajax_referer('ajax-logout-nonce', 'security');

    // Log the user out
    wp_logout();

    // Return success response
    //echo json_encode(array('loggedout' => true));

    wp_die(); // Required to terminate immediately and properly
}
add_action('wp_ajax_ajaxlogout', 'ajax_logout'); // For logged-in users