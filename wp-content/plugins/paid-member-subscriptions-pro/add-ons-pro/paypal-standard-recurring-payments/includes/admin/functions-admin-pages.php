<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

/**
 * Output the API username, API password and API signature for the PayPal business account
 *
 * @param array $options    - The settings option for Paid Member Subscriptions
 *
 */
if( !function_exists('pms_in_settings_gateway_paypal_extra_fields') ) {

    function pms_in_settings_gateway_paypal_extra_fields( $options ) {

        // PayPal API fields
        $fields = array(
            'api_username' => array(
                'label' => __( 'API Username', 'paid-member-subscriptions' ),
                'desc'  => __( 'API Username for Live site', 'paid-member-subscriptions' )
            ),
            'api_password' => array(
                'label' => __( 'API Password', 'paid-member-subscriptions' ),
                'desc'  => __( 'API Password for Live site', 'paid-member-subscriptions' )
            ),
            'api_signature' => array(
                'label' => __( 'API Signature', 'paid-member-subscriptions' ),
                'desc'  => __( 'API Signature for Live site', 'paid-member-subscriptions' )
            ),
            'test_api_username' => array(
                'label' => __( 'Test API Username', 'paid-member-subscriptions' ),
                'desc'  => __( 'API Username for Test/Sandbox site', 'paid-member-subscriptions' )
            ),
            'test_api_password' => array(
                'label' => __( 'Test API Password', 'paid-member-subscriptions' ),
                'desc'  => __( 'API Password for Test/Sandbox site', 'paid-member-subscriptions' )
            ),
            'test_api_signature' => array(
                'label' => __( 'Test API Signature', 'paid-member-subscriptions' ),
                'desc'  => __( 'API Signature for Test/Sandbox site', 'paid-member-subscriptions' )
            )
        );

        foreach( $fields as $field_slug => $field_details ) {
            echo '<div class="pms-form-field-wrapper">';

            echo '<label class="pms-form-field-label" for="paypal-' . esc_attr( str_replace('_', '-', $field_slug) ) . '">' . esc_html( $field_details['label'] ) . '</label>';

            echo '<input id="paypal-' . esc_attr( str_replace('_', '-', $field_slug) ) . '" type="password" name="pms_payments_settings[gateways][paypal][' . esc_attr( $field_slug ) . ']" value="' . ( isset($options['gateways']['paypal'][$field_slug]) ? esc_attr( $options['gateways']['paypal'][$field_slug] ) : '' ) . '" class="widefat" />';

            if( isset( $field_details['desc'] ) )
                echo '<p class="description">' . esc_html( $field_details['desc'] ) . '</p>';

            echo '</div>';
        }

    }
    add_action( 'pms_settings_page_payment_gateway_paypal_extra_fields', 'pms_in_settings_gateway_paypal_extra_fields' );
}
