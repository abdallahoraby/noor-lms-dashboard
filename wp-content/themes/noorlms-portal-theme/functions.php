<?php
/**
 * @package BuddyBoss Child
 * The parent theme functions are located at /buddyboss-theme/inc/theme/functions.php
 * Add your own functions at the bottom of this file.
 */


/****************************** THEME SETUP ******************************/

/**
 * Sets up theme for translation
 *
 * @since BuddyBoss Child 1.0.0
 */
function buddyboss_theme_child_languages()
{
  /**
   * Makes child theme available for translation.
   * Translations can be added into the /languages/ directory.
   */

  // Translate text from the PARENT theme.
  load_theme_textdomain( 'buddyboss-theme', get_stylesheet_directory() . '/languages' );

  // Translate text from the CHILD theme only.
  // Change 'buddyboss-theme' instances in all child theme files to 'buddyboss-theme-child'.
  // load_theme_textdomain( 'buddyboss-theme-child', get_stylesheet_directory() . '/languages' );

}
add_action( 'after_setup_theme', 'buddyboss_theme_child_languages' );

/**
 * Enqueues scripts and styles for child theme front-end.
 *
 * @since Boss Child Theme  1.0.0
 */
function buddyboss_theme_child_scripts_styles()
{
  /**
   * Scripts and Styles loaded by the parent theme can be unloaded if needed
   * using wp_deregister_script or wp_deregister_style.
   *
   * See the WordPress Codex for more information about those functions:
   * http://codex.wordpress.org/Function_Reference/wp_deregister_script
   * http://codex.wordpress.org/Function_Reference/wp_deregister_style
   **/

  // Styles
  wp_enqueue_style( 'animate-css', get_stylesheet_directory_uri().'/assets/css/animate.min.css' );
  wp_enqueue_style( 'bootstrap-css', get_stylesheet_directory_uri().'/assets/css/bootstrap.min.css' );
  wp_enqueue_style( 'dataTables-css', get_stylesheet_directory_uri().'/assets/css/dataTables.dataTables.min.css' );
  wp_enqueue_style( 'fontawesome-css', get_stylesheet_directory_uri().'/assets/css/fontawesome.min.css' );
  wp_enqueue_style( 'normalize-css', get_stylesheet_directory_uri().'/assets/css/normalize.min.css' );
  wp_enqueue_style( 'splide-css', get_stylesheet_directory_uri().'/assets/css/splide.min.css' );
  wp_enqueue_style( 'styles-css', get_stylesheet_directory_uri().'/assets/css/styles.css' );
  wp_enqueue_style( 'buddyboss-child-css', get_stylesheet_directory_uri().'/assets/css/custom.css' );

  // Javascript
  wp_enqueue_script( 'jquery-js', get_stylesheet_directory_uri().'/assets/js/jquery.min.js' );
  wp_enqueue_script( 'bootstrap-js', get_stylesheet_directory_uri().'/assets/js/bootstrap.min.js' );
  wp_enqueue_script( 'dataTables-js', get_stylesheet_directory_uri().'/assets/js/dataTables.min.js' );
  wp_enqueue_script( 'splide-js', get_stylesheet_directory_uri().'/assets/js/splide.min.js' );
  wp_enqueue_script( 'jquery-ui-js', get_stylesheet_directory_uri().'/assets/js/jquery-ui.js' );
  wp_enqueue_script( 'scripts-js', get_stylesheet_directory_uri().'/assets/js/scripts.js', [],rand(1,100), true );
  wp_enqueue_script( 'buddyboss-child-js', get_stylesheet_directory_uri().'/assets/js/custom.js', [], rand(1,100), true );


    // Localize the script with the necessary AJAX URL and nonce
    wp_localize_script( 'buddyboss-child-js', 'ajax_object', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'ajax_nonce' ),
    ));

    wp_register_script(
        'lottie-script', // Handle for the script
        get_stylesheet_directory_uri() . '/assets/js/lottie-player.js', // Path to the script file
        array('jquery'), // Dependencies (optional)
        '1.0.0', // Version number (optional)
        true // Load script in the footer (optional, default is false to load in the head)
    );

}
add_action( 'wp_enqueue_scripts', 'buddyboss_theme_child_scripts_styles', 9999 );


/****************************** CUSTOM FUNCTIONS ******************************/

// Add your own custom functions here

// include required files
require_once get_stylesheet_directory() . '/inc/custom-functions.php';
require_once get_stylesheet_directory() . '/inc/ajax-calls.php';
require_once get_stylesheet_directory() . '/inc/shortcodes.php';


function add_body_class_for_student($classes) {

    $classes[] = 'sidebar-is-reduced';

    if(current_user_can( 'administrator' )):
        $classes[] = 'is-admin';
    endif;

    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        if ( in_array('student', (array) $user->roles) ) {
            $classes[] = 'student-user';
        }
    }

    if( is_login_page() ){
        $classes[] = 'is-login-page';
    }

    return $classes;
}
add_filter('body_class', 'add_body_class_for_student');

function override_login_template() {
    // do some check and call wp_redirect if its true or whatever you wanted to do
    $login_template = locate_template('template-login-register.php');

    if( $login_template ):
        load_template( $login_template );
    endif;
}
add_action( 'login_init', 'override_login_template' );


function redirect_non_logged_in_users() {
    // Check if the user is not logged in and is not on the login page
    if (!is_user_logged_in() && !is_page('login')) {
        // Redirect to the login page
        wp_redirect(wp_login_url());
        exit;
    }
}
// Hook the function to 'template_redirect'
add_action('template_redirect', 'redirect_non_logged_in_users');


function disable_dashboard_access() {
    // Check if the user is logged in, trying to access the admin, and does not have the 'administrator' role
    if (is_admin() && !current_user_can('administrator') && !(defined('DOING_AJAX') && DOING_AJAX)) {
        // Redirect non-admin users to the homepage
        wp_redirect(home_url());
        exit;
    }
}
add_action('admin_init', 'disable_dashboard_access');


// Function to create a custom role
function create_custom_role() {
    // Define the capabilities for the custom role
    $capabilities = [
        'read' => true, // Allows the user to read content
        'edit_posts' => true, // Allows the user to edit their own posts
        'delete_posts' => false, // Disallows the user from deleting their own posts
        'publish_posts' => false, // Disallows the user from publishing posts
        'upload_files' => true, // Allows the user to upload files
    ];

    // Add the custom role
    add_role('student', 'Student', $capabilities);
}

// Hook the function to run when WordPress initializes
add_action('init', 'create_custom_role');



// Handle Login Form Submission
function custom_login_handler() {
    if (isset($_POST['submit_login'])) {
        $username = sanitize_user($_POST['username']);
        $password = $_POST['password'];

        $creds = array(
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => true
        );

        $user = wp_signon($creds, false);
        if (is_wp_error($user)) {
            echo '<p class="error">Invalid username or password.</p>';
        } else {
            // do some check and call wp_redirect if its true or whatever you wanted to do
            $success_login_template = locate_template('template-success-login.php');

            if( $success_login_template ):
                load_template( $success_login_template );
            endif;

            exit;
        }
    }
}
add_action('init', 'custom_login_handler');

// Handle Student Registration Form Submission
//function custom_student_registration_handler() {
//
//
//    if (isset($_POST['submit_registration'])) {
//        $username = sanitize_user($_POST['username']);
//        $email = sanitize_email($_POST['email']);
//        $password = $_POST['password'];
//        $password_confirmation = $_POST['password_confirmation'];
//
//        // Form validation
//        if (username_exists($username)) {
//            echo '<p class="error">Username already exists.</p>';
//        } elseif (!is_email($email) || email_exists($email)) {
//            echo '<p class="error">Invalid email or email already exists.</p>';
//        } elseif ($password !== $password_confirmation) {
//            echo '<p class="error">Passwords do not match.</p>';
//        } else {
//            // Register the user
//            $user_id = wp_create_user($username, $password, $email);
//            if (!is_wp_error($user_id)) {
//                // Assign "student" role
//                wp_update_user(array('ID' => $user_id, 'role' => 'student'));
//
//                // Auto-login after registration
//                wp_set_current_user($user_id);
//                wp_set_auth_cookie($user_id);
//                wp_redirect(home_url());
//                exit;
//            } else {
//                echo '<p class="error">There was an error registering the user. Please try again.</p>';
//            }
//        }
//    }
//}
//add_action('init', 'custom_student_registration_handler');


function is_login_page() {
    return in_array($GLOBALS['pagenow'], array('wp-login.php', 'wp-register.php'));
}



function auto_redirect_after_logout(){
    wp_safe_redirect( home_url() );
    exit;
}
add_action('wp_logout','auto_redirect_after_logout');