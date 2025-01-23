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
  wp_enqueue_style( 'toastr-css', get_stylesheet_directory_uri().'/assets/css/toastr.min.css' );
  wp_enqueue_style( 'styles-css', get_stylesheet_directory_uri().'/assets/css/styles.css' );
    wp_enqueue_style( 'buddyboss-child-css', get_stylesheet_directory_uri().'/assets/css/custom.css' );
    wp_enqueue_style( 'tablet-css', get_stylesheet_directory_uri().'/assets/css/tablet-styles.css' );
    wp_enqueue_style( 'mobile-css', get_stylesheet_directory_uri().'/assets/css/mobile-styles.css' );
    wp_enqueue_style( 'alertify-css', get_stylesheet_directory_uri().'/assets/css/alertify.min.css' );

  // Javascript
  wp_enqueue_script( 'jquery-js', get_stylesheet_directory_uri().'/assets/js/jquery.min.js' );
//  wp_enqueue_script( 'bootstrap-js', get_stylesheet_directory_uri().'/assets/js/bootstrap.min.js', rand(1,100) );
  wp_enqueue_script( 'bootstrap-bundle-js', get_stylesheet_directory_uri().'/assets/js/bootstrap.bundle.min.js', [], 5, true );
  wp_enqueue_script( 'dataTables-js', get_stylesheet_directory_uri().'/assets/js/dataTables.min.js' );
  wp_enqueue_script( 'splide-js', get_stylesheet_directory_uri().'/assets/js/splide.min.js' );
  wp_enqueue_script( 'jquery-ui-js', get_stylesheet_directory_uri().'/assets/js/jquery-ui.js' );
  wp_enqueue_script( 'jquery-toastr', get_stylesheet_directory_uri().'/assets/js/toastr.min.js' );
  wp_enqueue_script( 'jquery-alertify', get_stylesheet_directory_uri().'/assets/js/alertify.min.js' );
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

/**
 * Bulk inserts records into a table using WPDB.  All rows must contain the same keys.
 * Returns number of affected (inserted) rows.
 * @param $table string
 * @param $rows array
 * @return bool|int|mysqli_result|resource
 */
function wpdb_bulk_insert($table, $rows) {
    global $wpdb;

    // Extract column list from first row of data
    $columns = array_keys($rows[0]);
    asort($columns);
    $columnList = '`' . implode('`, `', $columns) . '`';

    // Start building SQL, initialise data and placeholder arrays
    $sql = "INSERT INTO `$table` ($columnList) VALUES\n";
    $placeholders = array();
    $data = array();

    // Build placeholders for each row, and add values to data array
    foreach ($rows as $row) {
        ksort($row);
        $rowPlaceholders = array();

        foreach ($row as $key => $value) {
            $data[] = $value;
            $rowPlaceholders[] = is_numeric($value) ? '%d' : '%s';
        }

        $placeholders[] = '(' . implode(', ', $rowPlaceholders) . ')';
    }

    // Stitch all rows together
    $sql .= implode(",\n", $placeholders);

    // Run the query.  Returns number of affected rows.
    return $wpdb->query($wpdb->prepare($sql, $data));
}


/**
 * a function to insert data into log table
 * @param $log_data array
 */
function addLog($table_name, $log_data ){
    // insert into custom log
    global $wpdb;
    $log_table = $wpdb->prefix . $table_name;
    $log_data = array( $log_data );
    wpdb_bulk_insert($log_table, $log_data);
}

function add_body_class_for_student($classes) {

    $classes[] = 'sidebar-is-reduced';

    if(current_user_can( 'administrator' )):
        $classes[] = 'is-admin';
    endif;

    if (is_user_logged_in() && !is_admin()):
        $classes[] = 'hide-admin-bar';
    endif;

    if( is_login_page() ):
        $classes[] = 'is-login-page';
    endif;

    return $classes;
}
add_filter('body_class', 'add_body_class_for_student');

function override_login_template() {

    // Check if the current page is the admin panel, if so, don't redirect
    if (is_admin()):
        return;
    endif;

    // do some check and call wp_redirect if its true or whatever you wanted to do
    $login_template = locate_template('template-login-register.php');

    if( $login_template ):
        load_template( $login_template );
    endif;
}
add_action( 'login_init', 'override_login_template' );


function redirect_non_logged_in_users() {

    // Check if the user is not logged in and is not on the login page

    // user is redirected from pricing page with a plan
    if( isset($_GET['subscription_plan']) && !is_user_logged_in() ):
        $url = wp_login_url() . '?subscription_plan='. $_GET['subscription_plan'];
        wp_safe_redirect( $url );
        exit();
    elseif (!is_user_logged_in() && !is_page('login') && !is_admin()):
        // Redirect to the login page
        wp_redirect(wp_login_url());
        exit;
    endif;
}
// Hook the function to 'template_redirect'
add_action('template_redirect', 'redirect_non_logged_in_users');


function disable_dashboard_access() {
    // Check if the user is logged in, trying to access the admin, and does not have the 'administrator' role
    if (!is_admin() && !current_user_can('administrator') && !(defined('DOING_AJAX') && DOING_AJAX)) {
        // Redirect non-admin users to the homepage
        wp_redirect(home_url());
        exit;
    }
}
add_action('admin_init', 'disable_dashboard_access');


function lms_practice_module_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'lms_practices';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        practice_date datetime NOT NULL,
        practice_minutes int NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function pms_cancel_subscription_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pms_cancel_subscription_log';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        cancel_datetime datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        cancel_reason TEXT NOT NULL DEFAULT '',
        cancel_comment TEXT NOT NULL DEFAULT '',
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/*
 * add_custom_roles
 * a function that adds custom roles
 */
function add_custom_roles(){
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

// Function to create a custom role
function custom_theme_initialization() {
    // Create the custom role when WordPress initializes
    add_custom_roles();
    // Create the custom practice module table when WordPress initializes
    lms_practice_module_create_table();
    // Create the custom table when WordPress initializes (for cancelled subscriptions)
    pms_cancel_subscription_create_table();
}

// Hook the function to run when WordPress initializes
add_action('init', 'custom_theme_initialization');



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



function add_custom_shortcode_button() {
    if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
        return;
    }

    if ( get_user_option( 'rich_editing' ) === 'true' ) {
        add_filter( 'mce_external_plugins', 'register_custom_button_script' );
        add_filter( 'mce_buttons', 'register_custom_button' );
    }
}

add_action( 'admin_head', 'add_custom_shortcode_button' );

function register_custom_button_script( $plugin_array ) {
    $plugin_array['custom_shortcode_button'] = get_stylesheet_directory_uri() . '/assets/js/custom-shortcode-button.js';
    return $plugin_array;
}

function register_custom_button( $buttons ) {
    array_push( $buttons, 'custom_shortcode_button' );
    return $buttons;
}


// function to cancel user subscription
function cancel_user_subscriptions($user_id) {


    $member_subscriptions = pms_get_member_subscriptions( array( 'user_id' => (int)$user_id ) );

    if( empty( $member_subscriptions ) )
        return;


    foreach( $member_subscriptions as $member_subscription ) {

        if( $member_subscription->status == 'active' ) {

            $member_subscription->update( array( 'status' => 'canceled' ) );
            do_action( 'pms_api_cancel_paypal_subscription', $member_subscription->payment_profile_id, $member_subscription->subscription_plan_id );
            apply_filters( 'pms_confirm_cancel_subscription', true, $user_id, $member_subscription->subscription_plan_id );
            pms_add_member_subscription_log( $member_subscription->id, 'subscription_canceled_user_deletion', array( 'who' => get_current_user_id() ) );

        }

    }

    return true;

}

/*
 * Override the template of the LearnPress plugin
 * To improve the speed as well as the quality of LearnPress ThimPress is going to have some necessary changes
 *  in coding from LP3 to LP4. Today, we will list out those changes to help our developers get updated to the new version.
 *  To override the template of the LearnPress plugin, you should add this code to the function.php file
 */
add_filter( 'learn-press/override-templates', function(){ return true; } );


/**
 * Added By MiWi
 */
function custom_format_buddypress_notifications( $action, $item_id, $secondary_item_id, $total_items, $format = 'string' ) 
{

}
add_filter( 'bp_notifications_get_notifications_for_user', 'custom_format_buddypress_notifications', 10, 5 );