<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;


/**
 * Add the LearnDash Lessons List display option to Content Restriction meta-box settings
 *
 */
function pms_ld_cr_lessons_list_option( $post_id ) {

    if ( function_exists('learndash_is_course_post') && !learndash_is_course_post( $post_id ) )
        return;

    $learndash_lessons_list_enabled = get_post_meta( $post_id, 'pms-content-restrict-ld-lessons-list-enabled', true );
    $content_restrict_type = get_post_meta( $post_id, 'pms-content-restrict-type', true );

    echo '<div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch ' . ($content_restrict_type == 'message' ? 'pms-enabled' : '') . '" id="pms-meta-box-field-learndash">
                <label class="pms-meta-box-field-label cozmoslabs-form-field-label" for="pms-content-restrict-ld-lessons-list-enabled">' . esc_html__('LearnDash Course Lessons', 'paid-member-subscriptions') . '</label>
                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" value="yes" ' . ( $learndash_lessons_list_enabled === "yes" ? 'checked="checked"' : '' ) . ' name="pms-content-restrict-ld-lessons-list-enabled" id="pms-content-restrict-ld-lessons-list-enabled">
                    <label class="cozmoslabs-toggle-track" for="pms-content-restrict-ld-lessons-list-enabled"></label>
                </div>
                <div class="cozmoslabs-toggle-description">                  
                    <label for="pms-content-restrict-ld-lessons-list-enabled" class="cozmoslabs-description">' . esc_html__('Enable if you wish to display the Lessons List for this Course.', 'paid-member-subscriptions') . '</label>
                </div>
                <p class="cozmoslabs-description cozmoslabs-description-space-left">' . esc_html__('By enabling this option a list of the Course Lessons will be displayed under the Content Restriction message! .', 'paid-member-subscriptions') . '</p>
            </div>';

}
add_action( 'pms_view_meta_box_content_restrict_display_options', 'pms_ld_cr_lessons_list_option' );


/**
 * Save the LearnDash Lessons List display option value from Content Restriction meta-box settings
 *
 */
function pms_ld_cr_update_lessons_list_option( $post_id, $post ) {

    // Verify nonce
    if( empty( $_POST['pmstkn'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['pmstkn'] ), 'pms_meta_box_single_content_restriction_nonce' ) )
        return;

    update_post_meta( $post_id, 'pms-content-restrict-ld-lessons-list-enabled', ( ! empty( $_POST['pms-content-restrict-ld-lessons-list-enabled'] ) ? sanitize_text_field( $_POST['pms-content-restrict-ld-lessons-list-enabled'] ) : 'no' ) );

}
add_action( 'pms_save_meta_box_sfwd-courses', 'pms_ld_cr_update_lessons_list_option', 10, 2 );


/**
 * Check to see if the LearnDash Course Lessons List should be displayed along the Restriction Message
 *
 */
function pms_ld_maybe_show_lessons_list( $message, $content, $post, $user_ID ){

    if ( empty( $post->ID ) || ( function_exists('learndash_is_course_post') && !learndash_is_course_post( $post->ID ) ) )
        return $message;

    $learndash_lessons_list_enabled = get_post_meta( $post->ID, 'pms-content-restrict-ld-lessons-list-enabled', true );

    if ( empty( $learndash_lessons_list_enabled ) )
        return $message;

    $lessons = function_exists('learndash_get_course_lessons_list_legacy') ? learndash_get_course_lessons_list_legacy( $post->ID ) : array();
    $lessons_list = array();

    foreach ( $lessons as $lesson) {
        $lessons_list[] = $lesson['post']->post_title;
    }

    $output = $message;

    if ( !empty( $lessons_list ) ) {
        $output .= '<p><strong>' . esc_html__('Course Lessons:', 'paid-member-subscriptions') . '</strong></p>';
        $output .= '<ul>';

        foreach ( $lessons_list as $lesson_name ) {
            $output .= '<li>' . $lesson_name . '</li>';
        }

        $output .= '</ul>';
    }

    return $output;

}
add_filter( 'pms_restriction_message_non_members', 'pms_ld_maybe_show_lessons_list', 30, 4 );
add_filter( 'pms_restriction_message_logged_out', 'pms_ld_maybe_show_lessons_list', 30, 4 );


/**
 * Clean the LearnDash post type slug before output
 * - displayed in Content Restriction Meta-box settings description
 */
function pms_ld_handle_cr_settings_description_cpt( $post_type ) {

    if ( substr( $post_type, 0, 5 ) === "sfwd-" ) {
        $post_type = substr( $post_type, 5 );

        if ( substr( $post_type, -1 ) === "s" )
            $post_type = substr( $post_type, 0, -1 );

    }

    return $post_type;
}
add_filter( 'pms_content_restrict_settings_description_cpt', 'pms_ld_handle_cr_settings_description_cpt', 20 );


/**
 * Hijack the content when restrictions are set on a LearnDash post
 * - pms_filter_content() found in -> includes/functions-content-filtering.php
 */
add_filter( 'learndash_content', 'pms_filter_content', 11 );