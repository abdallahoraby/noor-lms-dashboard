<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

/**
 * Display a warning to the administrators if the API credentials are missing in the
 * register page
 *
 */
function pms_in_stripe_api_credentials_admin_warning() {

    if( !current_user_can( 'manage_options' ) )
        return;

    $are_active = array_intersect( array( 'stripe', 'stripe_intents' ), pms_get_active_payment_gateways() );

    if( pms_in_get_stripe_api_credentials() == false && !empty( $are_active ) ) {

        echo '<div class="pms-warning-message-wrapper">';
            echo '<p>' . wp_kses_post( sprintf( __( 'Your Stripe API settings are missing. In order to make payments you will need to add your API credentials %1$s here %2$s.', 'paid-member-subscriptions' ), '<a href="' . esc_url( admin_url( 'admin.php?page=pms-settings-page&nav_tab=payments#pms-settings-payment-gateways' ) ) .'" target="_blank">', '</a>' ) ) . '</p>';
            echo '<p><em>' . esc_html__( 'This message is visible only by Administrators.', 'paid-member-subscriptions' ) . '</em></p>';
        echo '</div>';

    }

}
add_action( 'pms_new_subscription_form_top', 'pms_in_stripe_api_credentials_admin_warning' );
add_action( 'pms_upgrade_subscription_form_top', 'pms_in_stripe_api_credentials_admin_warning' );
add_action( 'pms_renew_subscription_form_top', 'pms_in_stripe_api_credentials_admin_warning' );
add_action( 'pms_retry_payment_form_top', 'pms_in_stripe_api_credentials_admin_warning' );


/**
 * Cancel Stripe subscription before the user upgrades the subscription
 *
 */
function pms_in_stripe_cancel_subscription_before_upgrade( $member_subscription_id, $payment_data ) {

    $user_id = $payment_data['user_id'];

    // Get payment_profile_id
    $payment_profile_id = pms_member_get_payment_profile_id( $user_id, $member_subscription_id );

    // Continue only if the profile id is a PayPal one
    if( !pms_in_is_stripe_payment_profile_id($payment_profile_id) )
        return;

    // Instantiate the payment gateway with data
    $payment_data = array(
        'user_data' => array(
            'user_id'       => $user_id,
            'subscription'  => pms_get_subscription_plan( $member_subscription_id )
        )
    );

    $stripe_gate = pms_get_payment_gateway( 'stripe', $payment_data );

    // Cancel the subscription and return the value
    $confirmation = $stripe_gate->cancel_subscription( $payment_profile_id );

}
add_action( 'pms_stripe_before_upgrade_subscription', 'pms_in_stripe_cancel_subscription_before_upgrade', 10, 2 );

add_action( 'wp_ajax_pms_create_payment_intent', 'pms_in_stripe_create_payment_intent' );
add_action( 'wp_ajax_nopriv_pms_create_payment_intent', 'pms_in_stripe_create_payment_intent' );
function pms_in_stripe_create_payment_intent(){

    if( !check_ajax_referer( 'pms_create_payment_intent', 'pms_nonce' ) )
        die();

    if( !isset( $_POST['form_type'] ) )
        die();

    pms_in_stripe_validate_checkout();

    // Initialize gateway
    $gateway = new PMS_IN_Payment_Gateway_Stripe_Payment_Intents();
    $gateway->init();

    if( isset( $_POST['setup_intent'] ) && $_POST['setup_intent'] == true )
        $gateway->create_setup_intent();
    else
        $gateway->create_payment_intent();

    die();

}

add_action( 'wp_ajax_pms_confirm_payment_intent', 'pms_in_stripe_confirm_payment_intent' );
add_action( 'wp_ajax_nopriv_pms_confirm_payment_intent', 'pms_in_stripe_confirm_payment_intent' );
function pms_in_stripe_confirm_payment_intent(){
    $gateway = new PMS_IN_Payment_Gateway_Stripe_Payment_Intents();
    $gateway->init();

    $gateway->confirm_payment_intent();

    die();
}

add_action( 'wp_ajax_pms_failed_payment_authentication', 'pms_in_stripe_failed_payment_authentication' );
add_action( 'wp_ajax_nopriv_pms_failed_payment_authentication', 'pms_in_stripe_failed_payment_authentication' );
function pms_in_stripe_failed_payment_authentication(){
    $gateway = new PMS_IN_Payment_Gateway_Stripe_Payment_Intents();
    $gateway->init();

    $gateway->failed_payment_authentication();

    die();
}

add_action( 'wp_ajax_pms_reauthenticate_intent', 'pms_in_reauthenticate_intent' );
add_action( 'wp_ajax_nopriv_pms_reauthenticate_intent', 'pms_in_reauthenticate_intent' );
function pms_in_reauthenticate_intent(){
    $gateway = new PMS_IN_Payment_Gateway_Stripe_Payment_Intents();
    $gateway->init();

    $gateway->reauthenticate_intent();

    die();
}

function pms_in_stripe_validate_wppb_form_fields(){

    if( !isset( $_POST['wppb_fields'] ) )
        return '';

    // Load fields
    include_once( WPPB_PLUGIN_DIR .'/front-end/default-fields/default-fields.php' );
    if( function_exists( 'wppb_include_extra_fields_files' ) )
        wppb_include_extra_fields_files();

    // Load WPPB fields data
    $wppb_manage_fields = get_option( 'wppb_manage_fields', 'not_found' );

    $output_field_errors = array();

    foreach( $_POST['wppb_fields'] as $id => $value ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

        $field = array();

        // return field name from field class
        $field_name = explode( ' ', $value['class'] );
        $field_name = substr( $field_name[1], 5 );
        $field_name = esc_attr( $field_name );

        // return field title by removing required sign *
        if( isset( $value['title'] ) ) {
            $field['field-title'] = str_replace( '*', '', $value['title'] );
            $field['field-title'] = sanitize_text_field( $field['field-title'] );
        }

        // return the id of the field from the field li (wppb-form-element-XX)
        if( isset( $id ) ) {
            $field_id = intval( substr( $id, 18 ) );
        }

        // check for fields errors for woocommerce billing fields
        if( $field_name == 'woocommerce-customer-billing-address' ) {
            if( ( function_exists( 'wppb_woo_billing_fields_array' ) && function_exists( 'wppb_check_woo_individual_fields_val' ) ) || ( function_exists( 'wppb_in_woo_billing_fields_array' ) && function_exists( 'wppb_in_check_woo_individual_fields_val' ) ) ) {
                $field['field'] = 'WooCommerce Customer Billing Address';

                if( function_exists('wppb_woo_billing_fields_array') )
                    $billing_fields = wppb_woo_billing_fields_array();
                else if( function_exists('wppb_in_woo_billing_fields_array') )
                    $billing_fields = wppb_in_woo_billing_fields_array();

                if( ! empty( $_POST['billing_country'] ) && class_exists( 'WC_Countries' ) ) {
                    $WC_Countries_Obj = new WC_Countries();
                    $locale = $WC_Countries_Obj->get_country_locale();

                    if( isset( $locale[sanitize_text_field( $_POST['billing_country'] )]['state']['required'] ) && ( $locale[sanitize_text_field( $_POST['billing_country'] )]['state']['required'] == false ) ) {
                        if( is_array( $billing_fields ) && isset( $billing_fields['billing_state'] ) ) {
                            $billing_fields['billing_state']['required'] = 'No';
                        }
                    }
                }

                if( isset( $value['fields'] ) ) {
                    foreach( $value['fields'] as $key => $woo_field_label ) {
                        $key = sanitize_text_field( $key );

                        if( function_exists('wppb_check_woo_individual_fields_val') )
                            $woo_error_for_field = wppb_check_woo_individual_fields_val( '', $billing_fields[$key], $key, $_POST, isset( $_POST['form_type'] ) ? sanitize_text_field( $_POST['form_type'] ) : '' );
                        else if( function_exists('wppb_in_check_woo_individual_fields_val') )
                            $woo_error_for_field = wppb_in_check_woo_individual_fields_val( '', $billing_fields[$key], $key, $_POST, isset( $_POST['form_type'] ) ? sanitize_text_field( $_POST['form_type'] ) : '' );

                        if( ! empty( $woo_error_for_field ) ) {
                            $output_field_errors[$key]['field'] = $key;
                            $output_field_errors[$key]['error'] = '<span class="wppb-form-error">'. $woo_error_for_field .'</span>';
                            $output_field_errors[$key]['type'] = 'woocommerce';
                        }
                    }
                }
            }
        }

        // check for fields errors for woocommerce shipping fields
        if( $field_name == 'woocommerce-customer-shipping-address' ) {
            if( ( function_exists( 'wppb_woo_shipping_fields_array' ) && function_exists( 'wppb_check_woo_individual_fields_val' ) ) || ( function_exists( 'wppb_in_woo_shipping_fields_array' ) && function_exists( 'wppb_in_check_woo_individual_fields_val' ) ) ) {
                $field['field'] = 'WooCommerce Customer Shipping Address';

                if( function_exists('wppb_woo_shipping_fields_array') )
                    $shipping_fields = wppb_woo_shipping_fields_array();
                else if( function_exists('wppb_in_woo_shipping_fields_array') )
                    $shipping_fields = wppb_in_woo_shipping_fields_array();

                if( ! empty( $_POST['shipping_country'] ) && class_exists( 'WC_Countries' ) ) {
                    $WC_Countries_Obj = new WC_Countries();
                    $locale = $WC_Countries_Obj->get_country_locale();

                    if( isset( $locale[sanitize_text_field( $_POST['shipping_country'] )]['state']['required'] ) && ( $locale[ sanitize_text_field( $_POST['shipping_country'] ) ]['state']['required'] == false ) ) {
                        if( is_array( $shipping_fields ) && isset( $shipping_fields['shipping_state'] ) ) {
                            $shipping_fields['shipping_state']['required'] = 'No';
                        }
                    }
                }

                if( isset( $value['fields'] ) ) {
                    foreach( $value['fields'] as $key => $woo_field_label ) {
                        $key = sanitize_text_field( $key );

                        if( function_exists('wppb_check_woo_individual_fields_val') )
                            $woo_error_for_field = wppb_check_woo_individual_fields_val( '', $shipping_fields[$key], $key, $_POST, isset( $_POST['form_type'] ) ? sanitize_text_field( $_POST['form_type'] ) : '' );
                        else if( function_exists('wppb_in_check_woo_individual_fields_val') )
                            $woo_error_for_field = wppb_in_check_woo_individual_fields_val( '', $shipping_fields[$key], $key, $_POST, isset( $_POST['form_type'] ) ? sanitize_text_field( $_POST['form_type'] ) : '' );

                        if( ! empty( $woo_error_for_field ) ) {
                            $output_field_errors[$key]['field'] = $key;
                            $output_field_errors[$key]['error'] = '<span class="wppb-form-error">'. $woo_error_for_field .'</span>';
                            $output_field_errors[$key]['type'] = 'woocommerce';
                        }
                    }
                }
            }
        }

        // add repeater fields to fields array
        if( isset( $value['extra_groups_count'] ) ) {
            $wppb_manage_fields = apply_filters( 'wppb_form_fields', $wppb_manage_fields, array( 'context' => 'multi_step_forms', 'extra_groups_count' => esc_attr( $value['extra_groups_count'] ), 'global_request' => $_POST, 'form_type' => 'register' ) );
        }

        // search for fields in fields array by meta-name or id (if field does not have a mata-name)
        if( ! empty( $value['meta-name'] ) && $value['meta-name'] != 'passw1' && $value['meta-name'] != 'passw2' && pms_in_wppb_msf_get_field_options( $value['meta-name'], $wppb_manage_fields ) !== false ) {
            $field = pms_in_wppb_msf_get_field_options( $value['meta-name'], $wppb_manage_fields );
        } elseif( ! empty( $field_id ) && pms_in_wppb_msf_get_field_options( $field_id, $wppb_manage_fields, 'id' ) !== false
            && $field_name != 'woocommerce-customer-billing-address' && $field_name != 'woocommerce-customer-shipping-address' ) {

            //@TODO: DON'T FORGET TO BRING THIS FUNCTION TO STRIPE
            $field = pms_in_wppb_msf_get_field_options( $field_id, $wppb_manage_fields, 'id' );
        }


        // check for fields errors
        if( $field_name != 'woocommerce-customer-billing-address' && $field_name != 'woocommerce-customer-shipping-address' ) {
            $error_for_field = apply_filters( 'wppb_check_form_field_'. $field_name, '', $field, $_POST, 'register' );
        }

        // construct the array with fields errors
        if( ( ! empty( $value['meta-name'] ) || $field_name == 'subscription-plans' ) && ! empty( $error_for_field ) ) {
            $output_field_errors[esc_attr( $value['meta-name'] )]['field'] = $field_name;
            $output_field_errors[esc_attr( $value['meta-name'] )]['error'] = '<span class="wppb-form-error">'. wp_kses_post( $error_for_field ) .'</span>';
        }

    }

    $output_field_errors = apply_filters( 'wppb_output_field_errors_filter', $output_field_errors );

    return $output_field_errors;

}

// Interpret AJAX call for new subscription form
add_filter( 'pms_request_form_location', 'pms_in_stripe_filter_request_form_location', 20, 2 );
function pms_in_stripe_filter_request_form_location( $location, $request ){

    if( !wp_doing_ajax() )
        return $location;
    
    if( !isset( $request['form_type'] ) )
        return $location;
    
    if( isset( $request['pay_gate'] ) && $request['pay_gate'] == 'stripe_intents' && $request['form_type'] == 'pms_new_subscription' )
        $location = 'new_subscription';
        
    if( $request['form_type'] == 'wppb' && isset( $request['action'] ) && $request['action'] == 'pms_create_payment_intent' )
        $location = 'wppb_register';

    return $location;

}

/**
 * Remove reCaptcha validation before validating the register process, since a payment is involved, it is not absolutely necessary to verify it
 * 
 * This fixes the problem with the form not submitting after adding a valid credit card because 3D Secure verification takes longer to complete 
 * then the expiration time of the recaptcha token
 */
add_action( 'init', 'pms_in_stripe_disable_recaptcha_verification', 5 );
function pms_in_stripe_disable_recaptcha_verification(){

    if( isset( $_POST['pmstkn'] ) && wp_verify_nonce( sanitize_text_field( $_POST['pmstkn'] ), 'pms_register_form_nonce') ){
        
        if( isset( $_POST['pay_gate'] ) && $_POST['pay_gate'] == 'stripe_intents' )
            remove_action( 'pms_register_form_validation', 'pms_recaptcha_field_validate_forms' );

    }
    
    if( isset( $_POST['form_name'] ) && isset( $_POST[ 'register_' . sanitize_text_field( $_POST['form_name'] ) . '_nonce_field' ] ) && wp_verify_nonce( sanitize_text_field( $_POST[ 'register_' . sanitize_text_field( $_POST['form_name'] ) . '_nonce_field' ] ), 'wppb_verify_form_submission' ) ){

        if( isset( $_POST['pay_gate'] ) && $_POST['pay_gate'] == 'stripe_intents' )
            remove_action( 'wppb_check_form_field_recaptcha', 'wppb_check_recaptcha_value', 10 );

    }

}

add_action( 'wp_ajax_pms_update_payment_method', 'pms_in_stripe_update_payment_method' );
add_action( 'wp_ajax_nopriv_pms_update_payment_method', 'pms_in_stripe_update_payment_method' );
function pms_in_stripe_update_payment_method(){

    if( !check_ajax_referer( 'pms_update_payment_method', 'pms_nonce' ) )
        die();

    // Initialize gateway
    $gateway = new PMS_IN_Payment_Gateway_Stripe_Payment_Intents();
    $gateway->init();

    $gateway->create_update_payment_method_setup_intent();

    die();

}