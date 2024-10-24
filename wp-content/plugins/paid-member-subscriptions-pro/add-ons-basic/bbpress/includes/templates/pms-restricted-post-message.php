<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

$reply_id = bbp_get_reply_id();
$topic_id = bbp_get_topic_id();
$forum_id = bbp_get_forum_id();

if( ! empty( $forum_id ) )
	$post_id = $forum_id;

if( ! empty( $reply_id ) || ! empty( $topic_id ) )
	$post_id = $topic_id;

if( ! empty( $post_id ) )
	echo pms_get_restricted_post_message( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped