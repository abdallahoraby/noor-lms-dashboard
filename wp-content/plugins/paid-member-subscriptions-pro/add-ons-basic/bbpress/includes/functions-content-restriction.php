<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;


/**
 * Registers the PMS bbPress templates stack
 *
 * @return string
 *
 */
function pms_in_bbp_get_template_directory() {

    return PMS_IN_BBPRESS_PLUGIN_DIR_PATH . 'includes/templates';

}
add_filter( 'bbp_template_stack', 'pms_in_bbp_get_template_directory' );


/**
 * If the topic is restricted and its restriction method is to show the topic, but hide the replies,
 * this will add a filter on the "bbp_show_lead_topic" to return true
 *
 */
function pms_in_bbp_show_lead_topic_true() {

	// Return if we're not on a topic
	if( ! is_singular( 'topic' ) )
		return;

	$topic_id = bbp_get_topic_id();
	$forum_id = bbp_get_forum_id();

	// Return if the topic or parent forum isn't restricted
	if( ! pms_is_post_restricted( $topic_id ) && ! pms_is_post_restricted( $forum_id ) )
		return;


	$topic_restriction_mode = get_post_meta( $topic_id, 'pms-bbpress-topic-restriction-mode', true );

	if( empty( $topic_restriction_mode ) || $topic_restriction_mode == 'forum_default' ) {

		$topic_restriction_mode = get_post_meta( $forum_id, 'pms-bbpress-topic-restriction-mode', true );

	}

	// If the topic restriction mode is set to show the topic
	// Overwrite the show lead topic to return true
	if( $topic_restriction_mode == 'show_topic' )
		add_filter( 'bbp_show_lead_topic', '__return_true' );

}
add_action( 'wp', 'pms_in_bbp_show_lead_topic_true' );


/**
 * Replace the default bbPress templates for Forums that are restricted
 *
 */
function pms_in_bbp_restrict_template_parts_forum( $templates, $slug, $name ) {

	$forum_id = bbp_get_forum_id();

	if( empty( $forum_id ) )
		return $templates;

	if( ! pms_is_post_restricted( $forum_id ) )
		return $templates;

	// Get the topic restriction mode
	$topic_restriction_mode = get_post_meta( $forum_id, 'pms-bbpress-topic-restriction-mode', true );

	// Check if current template is for the single forum content
	$key = array_search( 'content-single-forum.php', $templates );

	if( false !== $key && $topic_restriction_mode == 'hide_topic' )
	    $templates[$key] = 'pms-restricted-post-message.php';

	// Check if current template is for the form new topic
	$key = array_search( 'form-topic.php', $templates );

	if( false !== $key )
		$templates[$key] = 'pms-empty-template.php';


    return $templates;

}
add_filter( 'bbp_get_template_part' , 'pms_in_bbp_restrict_template_parts_forum', 10, 3 );


/**
 * Replace the default bbPress templates for Topics that are restricted
 *
 */
function pms_in_bbp_restrict_template_parts_topic( $templates, $slug, $name ) {

	$topic_id = bbp_get_topic_id();
	$forum_id = bbp_get_forum_id();

	if( empty( $topic_id ) || empty( $forum_id ) )
		return $templates;

	if( ! pms_is_post_restricted( $topic_id ) && ! pms_is_post_restricted( $forum_id ) )
		return $templates;

	$topic_restriction_mode = get_post_meta( $topic_id, 'pms-bbpress-topic-restriction-mode', true );

	if( empty( $topic_restriction_mode ) || $topic_restriction_mode == 'forum_default' ) {

		$topic_restriction_mode = get_post_meta( $forum_id, 'pms-bbpress-topic-restriction-mode', true );

	}

	/**
	 * Hide the entire topic and replies
	 *
	 */
	if( $topic_restriction_mode == 'hide_topic' ) {

		$key = array_search( 'content-single-topic.php' , $templates );

		if( false !== $key )
			$templates[$key] = 'pms-restricted-post-message.php';

	}


	/**
	 * Show the topic content, but hide the replies
	 *
	 */
	if( $topic_restriction_mode == 'show_topic' ) {

		// Overwrite the replies with an empty template
		$key = array_search( 'loop-replies.php' , $templates );

		if( false !== $key )
			$templates[$key] = 'pms-empty-template.php';

		// Overwrite the replies pagination with an empty template
		$key = array_search( 'pagination-replies.php' , $templates );

		if( false !== $key )
			$templates[$key] = 'pms-empty-template.php';

		// Overwrite the reply form with the restricted post message
		$key = array_search( 'form-reply.php', $templates );

		if( false !== $key )
			$templates[$key] = 'pms-restricted-post-message.php';

	}

	return $templates;

}
add_filter( 'bbp_get_template_part' , 'pms_in_bbp_restrict_template_parts_topic', 10, 3 );


/**
 * Replace the default bbPress templates for Replies that are restricted
 *
 */
function pms_in_bbp_restrict_template_parts_reply( $templates, $slug, $name ) {

	$reply_id = bbp_get_reply_id();
	$topic_id = bbp_get_topic_id();
	$forum_id = bbp_get_forum_id();

	if( empty( $reply_id ) || empty( $topic_id ) || empty( $forum_id ) )
		return $templates;

	if( ! pms_is_post_restricted( $topic_id ) && ! pms_is_post_restricted( $forum_id ) )
		return $templates;

	// Check if current template is for the single reply content
	$key = array_search( 'content-single-reply.php', $templates );

	if( false !== $key )
	    $templates[$key] = 'pms-restricted-post-message.php';

	return $templates;

}
add_filter( 'bbp_get_template_part' , 'pms_in_bbp_restrict_template_parts_reply', 10, 3 );


/**
 * Singular replies take the redirect settings from the parent topic, thus if the
 * parent topic is restricted we change the reply_id with the parent topic_id
 *
 *
 * @param int $post_id
 *
 * @return int
 *
 */
function pms_in_bbp_restricted_post_redirect_post_id_reply( $post_id = 0 ) {

	if( empty( $post_id ) )
		return $post_id;

	if( ! is_singular( 'reply' ) )
		return $post_id;

	$topic_id = bbp_get_topic_id();
	$forum_id = bbp_get_forum_id();

	if( ! pms_is_post_restricted( $topic_id ) && ! pms_is_post_restricted( $forum_id ) )
		return $post_id;

	return $topic_id;

}
add_action( 'pms_restricted_post_redirect_post_id', 'pms_in_bbp_restricted_post_redirect_post_id_reply' );


/**
 * Function that sets the default message for bbPress forums and topics used under PMS Settings Page -> Content Restriction messages
 *
 * @param string $message The message to return
 * @param string $type The type of message
 * @param array  $settings The PMS settings array
 *
 * @return string
 *
 */
function pms_in_bbp_set_default_restricted_messages( $message, $type, $settings ) {

	if( ! in_array( $type, array( 'logged_out_forum', 'non_members_forum', 'logged_out_topic', 'non_members_topic' ) ) )
		return $message;

    $message = isset( $settings[$type] ) ? $settings[$type] : __( 'You do not have access to this content.', 'paid-member-subscriptions' );

    return wp_kses_post( $message );

}
add_filter( 'pms_get_restriction_content_message_default', 'pms_in_bbp_set_default_restricted_messages', 10, 3 );


/**
 * Function that sets the message type inside "pms_get_restricted_post_message" function
 *
 * @param string $type
 *
 * @return string
 *
 */
function pms_in_bbp_set_restricted_messages_types( $type = '', $post_id = 0 ) {

	if( empty( $post_id ) )
		return $type;

	$post_type = get_post_type( $post_id );

	if( ! in_array( $post_type, array( 'forum', 'topic', 'reply' ) ) )
		return $type;


	// Check to see if the post has custom messages enabled
	$custom_message_enabled = get_post_meta( $post_id, 'pms-content-restrict-messages-enabled', true );

	// If it does we don't need to change the message type
	if( ! empty( $custom_message_enabled ) )
		return $type;

	// Forum message type
	if( $post_type == 'forum' ) {

		if( ! is_user_logged_in() )
			$type = 'logged_out_forum';
		else
			$type = 'non_members_forum';

	}

	// Topic message type (applies also to replies)
	if( in_array( $post_type, array( 'topic', 'reply' ) ) ) {

		if( ! is_user_logged_in() )
			$type = 'logged_out_topic';
		else
			$type = 'non_members_topic';

	}

    return $type;

}
add_filter( 'pms_get_restricted_post_message_type', 'pms_in_bbp_set_restricted_messages_types', 10, 2 );
