<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;


/**
 * Function that adds default restriction messages for bbPress forums and topics under PMS Settings -> Content Restriction messages
 *
 * @param array $options PMS settings options
 *
 */
function pms_in_bbp_settings_page_add_default_restricted_messages( $options ) {

    echo '<div class="cozmoslabs-form-subsection-wrapper" id="cozmoslabs-restriction-bbpress">';
        echo '<h4 class="cozmoslabs-subsection-title">' . esc_html__( 'bbPress Restriction Messages', 'paid-member-subscriptions' ) . '</h4>';

        // Forum Messages for logged-out users
        echo '<div class="cozmoslabs-form-field-wrapper cozmoslabs-wysiwyg-wrapper cozmoslabs-wysiwyg-indented">';

            echo '<label class="cozmoslabs-form-field-label">' . esc_html__( 'Forum Messages for logged-out users', 'paid-member-subscriptions' ) . '</label>';
            wp_editor( pms_get_restriction_content_message( 'logged_out_forum' ), 'messages_logged_out_forum', array( 'textarea_name' => 'pms_content_restriction_settings[logged_out_forum]', 'editor_height' => 180 ) );

        echo '</div>';

        // Forum Messages for logged-in non-member users
        echo '<div class="cozmoslabs-form-field-wrapper cozmoslabs-wysiwyg-wrapper cozmoslabs-wysiwyg-indented">';

            echo '<label class="cozmoslabs-form-field-label">' . esc_html__( 'Forum Messages for logged-in non-member users', 'paid-member-subscriptions' ) . '</label>';
            wp_editor( pms_get_restriction_content_message( 'non_members_forum' ), 'messages_non_members_forum', array( 'textarea_name' => 'pms_content_restriction_settings[non_members_forum]', 'editor_height' => 180 ) );

        echo '</div>';

        // Topic Messages for logged-out users
        echo '<div class="cozmoslabs-form-field-wrapper cozmoslabs-wysiwyg-wrapper cozmoslabs-wysiwyg-indented">';

            echo '<label class="cozmoslabs-form-field-label">' . esc_html__( 'Topic Messages for logged-out users', 'paid-member-subscriptions' ) . '</label>';
            wp_editor( pms_get_restriction_content_message( 'logged_out_topic' ), 'messages_logged_out_topic', array( 'textarea_name' => 'pms_content_restriction_settings[logged_out_topic]', 'editor_height' => 180 ) );

        echo '</div>';

        // Topic Messages for logged-in non-member users
        echo '<div class="cozmoslabs-form-field-wrapper cozmoslabs-wysiwyg-wrapper cozmoslabs-wysiwyg-indented">';

            echo '<label class="cozmoslabs-form-field-label">' . esc_html__( 'Topic Messages for logged-in non-member users', 'paid-member-subscriptions' ) . '</label>';
            wp_editor( pms_get_restriction_content_message( 'non_members_topic' ), 'messages_non_members_topic', array( 'textarea_name' => 'pms_content_restriction_settings[non_members_topic]', 'editor_height' => 180 ) );

        echo '</div>';
    echo '</div>';

}
add_action('pms-settings-page_tab_content_restriction_restrict_messages_bottom', 'pms_in_bbp_settings_page_add_default_restricted_messages');
