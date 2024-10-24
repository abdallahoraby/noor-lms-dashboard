<?php

require_once( '../../../../../../wp-load.php' );
$files_restriction_add_on_active = apply_filters( 'pms_add_on_is_active', false, 'pms-add-on-files-restriction/index.php' );

    // get server request data
    $wp_root = isset( $_SERVER['DOCUMENT_ROOT'] ) ? sanitize_text_field( $_SERVER['DOCUMENT_ROOT'] ) : '';
    $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( $_SERVER['REQUEST_URI'] ) : '';
    $http_referer = isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( $_SERVER['HTTP_REFERER'] ) : '';

    // get the file path and url
    $file_path = $wp_root . $request_uri;
    $file_url = pms_get_current_page_url();

    // get the attachment data
    $attachment_id = attachment_url_to_postid( $file_url );
    $file_mime_type = mime_content_type( $file_path );
    $is_attachment_restricted = pms_is_post_restricted( $attachment_id );

    /**
     * Serve requested File when:
     *
     * - the request is made from WordPress Dashboard (media library thumbnails and single file view)
     * - there are no restrictions set for the attachment
     *
     */
    if ( strpos( $http_referer, '/wp-admin/' ) !== false || !$is_attachment_restricted || $files_restriction_add_on_active === false ) {
        pms_file_restriction_serve_file( $file_path, $file_mime_type );
    }

    // get the general Content Restriction settings
    $pms_content_restriction_settings = get_option( 'pms_content_restriction_settings', 'not_found' );

    // get the attachment restriction type
    $attachment_restriction_type = get_post_meta( $attachment_id, 'pms-content-restrict-type', true );
    $settings_restriction_type = $pms_content_restriction_settings['content_restrict_type'];

    // handle Settings Default type restriction
    if ( $attachment_restriction_type === 'default' )
        $attachment_restriction_type = $pms_content_restriction_settings['content_restrict_type'];

    // handle Message type restriction + template type restriction
    if ( $attachment_restriction_type === 'message' || $attachment_restriction_type === 'template' ) {
        if ( is_user_logged_in() ) {
            $message = pms_get_restriction_content_message( 'non_members', $attachment_id );
        } else {
            $message = pms_get_restriction_content_message( 'logged_out', $attachment_id );
        }

        // display the restriction message
        status_header(403);
        nocache_headers();
        die( wp_kses_post( $message ) );
    }

    // handle Redirect type restriction
    if ( $attachment_restriction_type === 'redirect' ) {
        $attachment_redirect_url_enabled = get_post_meta( $attachment_id, 'pms-content-restrict-custom-redirect-url-enabled', true );

        if ( is_user_logged_in() ) {
            if ( !pms_is_member( get_current_user_id() ) )
                $attachment_redirect_url = get_post_meta( $attachment_id, 'pms-content-restrict-custom-non-member-redirect-url', true );
            else
                $attachment_redirect_url = get_post_meta( $attachment_id, 'pms-content-restrict-custom-redirect-url', true );
        } else
            $attachment_redirect_url = get_post_meta( $attachment_id, 'pms-content-restrict-custom-redirect-url', true );

        $redirect_url = ( !empty($attachment_redirect_url_enabled ) && !empty( $attachment_redirect_url ) ? $attachment_redirect_url : '' );

        // if a custom redirect URL is not set for the attachment, get the default redirect URL from general Content Restriction settings
        if ( empty( $redirect_url ) ) {
            if ( is_user_logged_in() ) {
                $redirect_url = ( !empty( $pms_content_restriction_settings['content_restrict_non_member_redirect_url'] ) ? $pms_content_restriction_settings['content_restrict_non_member_redirect_url'] : '' );
            } else
                $redirect_url = ( !empty( $pms_content_restriction_settings['content_restrict_redirect_url'] ) ? $pms_content_restriction_settings['content_restrict_redirect_url'] : '' );

        }

        // if there is no redirect URL, serve the file
        if ( empty( $redirect_url ) )
            pms_file_restriction_serve_file( $file_path, $file_mime_type );

        // redirect the user to the specific URL
        nocache_headers();
        wp_redirect( apply_filters('pms_restricted_post_redirect_url', $redirect_url ) );
        exit;
    }


/**
 * Serve requested File
 *
 */
function pms_file_restriction_serve_file( $file_path, $file_mime_type ) {

    if ( file_exists( $file_path ) ) {

        header( 'Content-Type: ' . $file_mime_type );
        readfile( $file_path );
        exit;

    } else {

        status_header( 404 );
        nocache_headers();
        die( 'File not found.' );

    }

}