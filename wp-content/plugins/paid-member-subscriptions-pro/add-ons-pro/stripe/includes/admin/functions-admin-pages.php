<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;


/**
 * Function that adds the HTML for Stripe in the payments tab from the Settings page
 *
 * @param array $options    - The saved option settings
 *
 */
function pms_in_add_settings_content_stripe( $options ) {

    // Stripe API fields
    $fields = array(
        'test_api_publishable_key' => array(
            'label' => __( 'Test Publishable Key', 'paid-member-subscriptions' )
        ),
        'test_api_secret_key' => array(
            'label' => __( 'Test Secret Key', 'paid-member-subscriptions' )
        ),
        'api_publishable_key' => array(
            'label' => __( 'Live Publishable Key', 'paid-member-subscriptions' )
        ),
        'api_secret_key' => array(
            'label' => __( 'Live Secret Key', 'paid-member-subscriptions' )
        )
    );

	if( in_array( 'stripe_intents', $options['active_pay_gates'] ) || in_array( 'stripe', $options['active_pay_gates'] ) ) :

		echo '<div class="cozmoslabs-form-subsection-wrapper" id="cozmoslabs-subsection-stripe-intents-configs">';

			echo '<h4 class="cozmoslabs-subsection-title" id="pms-stripe__gateway-settings">'
					. esc_html__( 'Stripe', 'paid-member-subscriptions' ) .
					'<a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/add-ons/stripe-payment-gateway/?utm_source=wpbackend&utm_medium=pms-documentation&utm_campaign=PMSDocs#Entering_your_Stripe_API_Credentials" target="_blank" data-code="f223" class="pms-docs-link dashicons dashicons-editor-help"></a>
				</h4>';

			if( in_array( 'stripe_intents', $options['active_pay_gates'] ) || in_array( 'stripe', $options['active_pay_gates'] ) ) :

				foreach( $fields as $field_slug => $field_options ) {
					echo '<div class="cozmoslabs-form-field-wrapper">';

                        echo '<label class="cozmoslabs-form-field-label" for="stripe-' . esc_attr( str_replace( '_', '-', $field_slug ) ) . '">' . esc_html( $field_options['label'] ) . '</label>';

                        echo '<input id="stripe-' . esc_attr( str_replace( '_', '-', $field_slug ) ) . '" type="text" name="pms_payments_settings[gateways][stripe][' . esc_attr( $field_slug ) . ']" value="' . ( isset( $options['gateways']['stripe'][$field_slug] ) ? esc_attr( $options['gateways']['stripe'][$field_slug] ) : '' ) . '" class="widefat" />';

                        if( isset( $field_options['desc'] ) )
                            echo '<p class="cozmoslabs-description cozmoslabs-description-space-left">' . esc_html( $field_options['desc'] ) . '</p>';

					echo '</div>';
				}

			endif;

			do_action( 'pms_settings_page_payment_gateway_stripe_extra_fields', $options );

		echo '</div>';

	endif;

}
add_action( 'pms-settings-page_payment_gateways_content', 'pms_in_add_settings_content_stripe', 9 );

function pms_in_stripe_sanitize_settings( $settings ){

    if( empty( $settings['gateways']['stripe'] ) )
        return $settings;

    // Test Keys
    if( !empty( $settings['gateways']['stripe']['test_api_publishable_key'] ) && strpos( $settings['gateways']['stripe']['test_api_publishable_key'], 'pk_test' ) === false ){
        $settings['gateways']['stripe']['test_api_publishable_key'] = '';
        add_settings_error( 'pms_payments_settings[gateways][stripe][test_api_publishable_key]', 'test-api-pk', __( 'The Test Publishable Key you entered is invalid. The key should start with `pk_test`.', 'paid-member-subscriptions' ) );
    }

    if( !empty( $settings['gateways']['stripe']['test_api_secret_key'] ) && strpos( $settings['gateways']['stripe']['test_api_secret_key'], 'sk_test' ) === false ){
        $settings['gateways']['stripe']['test_api_secret_key'] = '';
        add_settings_error( 'pms_payments_settings[gateways][stripe][test_api_secret_key]', 'test-api-sk', __( 'The Test Secret Key you entered is invalid. The key should start with `sk_test`.', 'paid-member-subscriptions' ) );
    }

    // Live Keys
    if( !empty( $settings['gateways']['stripe']['api_publishable_key'] ) && strpos( $settings['gateways']['stripe']['api_publishable_key'], 'pk_live' ) === false ){
        $settings['gateways']['stripe']['api_publishable_key'] = '';
        add_settings_error( 'pms_payments_settings[gateways][stripe][api_publishable_key]', 'live-api-pk', __( 'The Live Publishable Key you entered is invalid. The key should start with `pk_live`.', 'paid-member-subscriptions' ) );
    }

    if( !empty( $settings['gateways']['stripe']['api_secret_key'] ) && strpos( $settings['gateways']['stripe']['api_secret_key'], 'sk_live' ) === false ){
        $settings['gateways']['stripe']['api_secret_key'] = '';
        add_settings_error( 'pms_payments_settings[gateways][stripe][api_secret_key]', 'live-api-sk', __( 'The Live Secret Key you entered is invalid. The key should start with `sk_live`.', 'paid-member-subscriptions' ) );
    }

    return $settings;

}
add_filter( 'pms_sanitize_settings', 'pms_in_stripe_sanitize_settings' );
