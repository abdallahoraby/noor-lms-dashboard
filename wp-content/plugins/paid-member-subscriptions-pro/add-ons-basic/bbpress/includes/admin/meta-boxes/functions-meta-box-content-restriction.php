<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;


/**
 * Remove the default meta-box from PMS
 *
 */
remove_action( 'add_meta_boxes', 'pms_init_meta_boxes_bbpress' );


/**
 * Initialize the restrict content metaboxes on bbPress forums and topics
 *
 */
function pms_in_bbp_initialize_content_restrict_metabox() {

	// bbPress post types
	$post_types = array( 'forum', 'topic' );

	foreach( $post_types as $post_type ) {

		$pms_meta_box_content_restriction = new PMS_Meta_Box_Content_Restriction( 'pms_post_content_restriction', __( 'Content Restriction', 'paid-member-subscriptions' ), $post_type, 'normal' );
		$pms_meta_box_content_restriction->init();

	}

}
add_action( 'init', 'pms_in_bbp_initialize_content_restrict_metabox', 12 );


/**
 * Adds option content restriction option for forums and topisc to be able to restrict topics
 *
 * Topic content restriction types:
 * 1) by hidding the entire topic and it's replies
 * 2) by showing the topic, but hidding just the replies
 *
 * @param int $post_id
 *
 */
function pms_in_bbp_add_topic_content_restriction_type( $post_id = 0 ) {

	if( empty( $post_id ) )
		return;

	if( ! in_array( get_post_type( $post_id ), array( 'forum', 'topic' ) ) )
		return;

	include_once 'views/view-content-restriction-topic-restriction-type.php';

}
add_action( 'pms_view_meta_box_content_restrict_display_options', 'pms_in_bbp_add_topic_content_restriction_type' );


/**
 * Saves the meta for the topic content restriction type
 *
 * @param int 	  $post_id
 * @param WP_Post $post
 *
 */
function pms_in_bbp_save_topic_content_restriction_type( $post_id, $post ) {

	// Verify nonce
    if( empty( $_POST['pmstkn'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['pmstkn'] ), 'pms_meta_box_single_content_restriction_nonce' ) )
        return;

    update_post_meta( $post_id, 'pms-bbpress-topic-restriction-mode', ( ! empty( $_POST['pms-bbpress-topic-restriction-mode'] ) ? sanitize_text_field( $_POST['pms-bbpress-topic-restriction-mode'] ) : '' ) );

}
add_action( 'pms_save_meta_box_forum', 'pms_in_bbp_save_topic_content_restriction_type', 10, 2 );
add_action( 'pms_save_meta_box_topic', 'pms_in_bbp_save_topic_content_restriction_type', 10, 2 );