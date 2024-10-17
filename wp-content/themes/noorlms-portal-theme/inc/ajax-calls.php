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
        wp_send_json_error(array(
            'message' => '<p class="error"> Please enter first name </p>'
        ));
    } elseif ( !is_email($userEmail) || empty($userEmail) ){
        wp_send_json_error(array(
            'message' => '<p class="error"> Please enter valid email </p>'
        ));
    } elseif ( empty($userPassword) || empty($userConfirmPassword) ) {
        wp_send_json_error(array(
            'message' => '<p class="error">Passwords is empty or do not match.</p>'
        ));
    } elseif ($userPassword !== $userConfirmPassword) {
        wp_send_json_error(array(
            'message' => '<p class="error">Passwords do not match.</p>'
        ));
    } else {

        if ( email_exists($userEmail)) {
            wp_send_json_error(array(
                'message' => '<p class="error">Email already exists.</p>'
            ));
        } elseif (username_exists($userName)) {
            wp_send_json_error(array(
                'message' => '<p class="error">Username already exists.</p>'
            ));
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
                wp_send_json_success(array(
                    'success' => true,
                    'message' => '<div class="success-login"> <lottie-player src="'.$lottie_src.'"  background="transparent"  speed="1"  style="width: 70px; height: 70px;"  loop autoplay></lottie-player> You are now successfully registered. Please login </div>'
                ));
            } else {
                $error_string = $user_id->get_error_message();
                wp_send_json_error(array(
                    'message' => '<p class="error"> '.$error_string.' <br> Please try again.</p>'
                ));
            }
        }

        wp_die();
    }


}
add_action( 'wp_ajax_handle_registration', 'handle_registration' );
add_action( 'wp_ajax_nopriv_handle_registration', 'handle_registration' );
