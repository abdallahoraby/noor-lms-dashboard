<?php
/**
 * Functions
 *
 * @package GamiPress\FluentCommunity\Functions
 * @since 1.0.0
 */

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

/**
 * Overrides GamiPress AJAX Helper for selecting posts
 *
 * @since 1.0.0
 */
function gamipress_fluentcommunity_ajax_get_posts() {

    global $wpdb;

    $results = array();

    // Pull back the search string
    $search = isset( $_REQUEST['q'] ) ? $wpdb->esc_like( $_REQUEST['q'] ) : '';

    if( isset( $_REQUEST['post_type'] ) && in_array( 'fluentcommunity_spaces', $_REQUEST['post_type'] ) ) {

        $spaces = \FluentCommunity\App\Functions\Utility::getSpaces();

        foreach ( $spaces as $space ) {

            // Results should meet same structure like posts
            $results[] = array(
                'ID' => $space['id'],
                'post_title' => $space['title'],
            );

        }

        // Return our results
        wp_send_json_success( $results );
        die;

    } else if( isset( $_REQUEST['post_type'] ) && in_array( 'fluentcommunity_courses', $_REQUEST['post_type'] ) ) {

        $courses = \FluentCommunity\App\Functions\Utility::getCourses();

        foreach ( $courses as $course ) {

            // Results should meet same structure like posts
            $results[] = array(
                'ID' => $course['id'],
                'post_title' => $course['title'],
            );

        }

        // Return our results
        wp_send_json_success( $results );
        die;

    }

}
add_action( 'wp_ajax_gamipress_get_posts', 'gamipress_fluentcommunity_ajax_get_posts', 5 );


// Get the space title
function gamipress_fluentcommunity_get_space_title( $space_id ) {

    if( absint( $space_id ) === 0 ) {
        return '';
    }

    $spaces = \FluentCommunity\App\Functions\Utility::getSpaces();

    foreach ( $spaces as $space ) {
        if ( absint( $space['id'] ) === absint( $space_id ) ) {
            return $space['title']; 
        }
    }

}

// Get the course title
function gamipress_fluentcommunity_get_course_title( $course_id ) {

    if( absint( $course_id ) === 0 ) {
        return '';
    }

    $courses = \FluentCommunity\App\Functions\Utility::getCourses();

    foreach ( $courses as $course ) {
        if ( absint( $course['id'] ) === absint( $course_id ) ) {
            return $course['title']; 
        }
    }

}