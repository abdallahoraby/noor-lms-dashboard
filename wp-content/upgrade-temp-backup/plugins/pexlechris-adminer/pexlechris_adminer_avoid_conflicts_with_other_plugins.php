<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}


if( !defined('PEXLECHRIS_ADMINER_SLUG') ){
	if( is_multisite() ){
		define('PEXLECHRIS_ADMINER_SLUG', 'multisite-adminer');
	}else{
		define('PEXLECHRIS_ADMINER_SLUG', 'wp-adminer');
	}
}

if( !function_exists('pexlechris_is_current_url_the_wp_adminer_url') ){
	function pexlechris_is_current_url_the_wp_adminer_url(){
		$REQUEST_URI = parse_url( esc_url($_SERVER["REQUEST_URI"]), PHP_URL_PATH);
		$REQUEST_URI = untrailingslashit($REQUEST_URI);
		if( pexlechris_adminer_ends_with($REQUEST_URI, untrailingslashit(PEXLECHRIS_ADMINER_SLUG)) ){
			return true;
		}else{
			return false;
		}
	}
}

if( !function_exists('pexlechris_adminer_ends_with') ) {
	function pexlechris_adminer_ends_with( $haystack, $needle ) {
		$length = strlen( $needle );
		if( !$length ) {
			return true;
		}
		return substr( $haystack, -$length ) === $needle;
	}
}

if( pexlechris_is_current_url_the_wp_adminer_url() ){
	add_filter( 'option_active_plugins', function( $plugins ){
		return ['pexlechris-adminer/pexlechris-adminer.php'];
	} );
}