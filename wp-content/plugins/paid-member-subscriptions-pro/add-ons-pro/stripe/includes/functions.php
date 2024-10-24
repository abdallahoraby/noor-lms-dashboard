<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

/*
 * Return the Stripe API credentials
 *
 */
if( !function_exists( 'pms_in_get_stripe_api_credentials' ) ) {

    function pms_in_get_stripe_api_credentials() {

        if ( defined( 'PMS_VERSION' ) && version_compare( PMS_VERSION, '1.7.8' ) == -1 ) {
            $pms_settings = get_option( 'pms_settings', array() );
            $pms_settings = ( !empty( $pms_settings['payments']['gateways']['stripe'] ) ? $pms_settings['payments']['gateways']['stripe'] : '' );
        } else {
            $pms_settings = get_option( 'pms_payments_settings', array() );
            $pms_settings = ( !empty( $pms_settings['gateways']['stripe'] ) ? $pms_settings['gateways']['stripe'] : '' );
        }

        if( empty( $pms_settings ) )
            return false;

        if( pms_is_payment_test_mode() )
            $sandbox_prefix = 'test_';
        else
            $sandbox_prefix = '';

        $api_credentials = array(
            'secret_key'      => $pms_settings[$sandbox_prefix . 'api_secret_key'],
            'publishable_key' => $pms_settings[$sandbox_prefix . 'api_publishable_key']
        );

        $api_credentials = array_map( 'trim', $api_credentials );

        if( count( array_filter($api_credentials) ) == count($api_credentials) )
            return $api_credentials;
        else
            return false;

    }

}


/*
 * Checks whether the value of the payment profile id matches the subscription ids
 * in Stripe
 *
 * @param string $payment_profile_id
 *
 */
function pms_in_is_stripe_payment_profile_id( $payment_profile_id ) {

    if( empty( $payment_profile_id ) )
        return false;
        
    if( strpos( $payment_profile_id, 'sub_' ) !== false )
        return true;
    else
        return false;

}

function pms_in_get_payment_by_transaction_id( $intent_id ){

    global $wpdb;

    $result = $wpdb->get_row( "SELECT id FROM {$wpdb->prefix}pms_payments WHERE transaction_id = '{$intent_id}'", ARRAY_A );

    if( ! is_null( $result ) )
        $result = new PMS_Payment( $result['id'] );

    return $result;

}

function pms_in_get_active_stripe_gateway(){

    $settings = get_option( 'pms_payments_settings', array() );

    if( !isset( $settings['active_pay_gates'] ) )
        return false;

    $active_gateway = false;

    foreach( $settings['active_pay_gates'] as $gateway_slug ){
        if( strpos( $gateway_slug, 'stripe' ) !== false )
            $active_gateway = $gateway_slug;
    }

    return $active_gateway;

}

function pms_in_stripe_check_filter_from_class_exists( $hook, $className, $methodName ){
    global $wp_filter;

    if( !isset( $wp_filter[$hook] ) )
        return false;

    foreach( $wp_filter[$hook] as $priority => $realhook ){

        foreach( $realhook as $hook_k => $hook_v ){

            if( is_array( $hook_v['function'] ) ){

                if( isset( $hook_v['function'][0], $hook_v['function'][1] ) && get_class( $hook_v['function'][0] ) == $className && $hook_v['function'][1] == $methodName ) {

                    return true;

                }
            }

        }

    }

    return false;
}

function pms_in_stripe_get_generated_errors(){

    $generated_errors = array();
    $error_obj        = pms_errors();

    if( !empty( $error_obj->errors ) ){
        foreach( $error_obj->errors as $key => $error ){

            if( !empty( $error[0] ) )
                $generated_errors[] = array(
                    'target'  => $key,
                    'message' => $error[0]
                );

        }
    }

    return $generated_errors;

}

/**
 * Function that search in multidimensional arrays
 * Copied from MultiStep Forms add-on
 */
function pms_in_wppb_msf_get_field_options( $needle, $haystack, $type = 'meta-name' ) {

    foreach( $haystack as $item ) {
        if( is_array( $item ) && isset( $item[$type] ) && $item[$type] == $needle ) {
            return $item;
        }
    }

    return false;

}

function pms_in_stripe_validate_checkout(){

    if( empty( $_POST['form_type'] ) )
        return;

    // If the user is not logged in, the data from the register form needs to be validated
    if( !is_user_logged_in() ){

        // Validate PMS Register form
        if( $_POST['form_type'] == 'pms' ){

            // This also validates PWYW
            if( !PMS_Form_Handler::validate_register_form() ){
                $errors = pms_in_stripe_get_generated_errors();

                echo json_encode( array(
                    'success' => false,
                    'data'    => $errors,
                ) );
                die();
            }

            // Validate subscription plans
            if( !PMS_Form_Handler::validate_subscription_plans() || !PMS_Form_Handler::validate_subscription_plans_member_eligibility() ){
                $errors = pms_in_stripe_get_generated_errors();

                echo json_encode( array(
                    'success' => false,
                    'data'   => $errors,
                ) );
                die();
            }

        // Validate WPPB Register form
        } else if( $_POST['form_type'] == 'wppb' && !empty( $_POST['wppb_fields' ] ) ){

            $wppb_errors = pms_in_stripe_validate_wppb_form_fields();

            // Validate PMS fields
            PMS_Form_Handler::validate_subscription_plans();
            PMS_Form_Handler::validate_subscription_plans_member_eligibility();

            $pms_errors  = pms_in_stripe_get_generated_errors();

            if( !empty( $wppb_errors ) || !empty( $pms_errors ) ){
                echo json_encode( array(
                    'success'     => false,
                    'data'        => '',
                    'wppb_errors' => $wppb_errors,
                    'pms_errors'  => $pms_errors,
                ) );
                die();
            }

        } else if( $_POST['form_type'] == 'pms_email_confirmation' && !empty( $_POST['pms_user_id'] ) ){

            // Validate Billing Fields
            do_action( 'pms_register_form_validation' );

            $errors = pms_in_stripe_get_generated_errors();

            if( !empty( $errors ) ){
                echo json_encode( array(
                    'success' => false,
                    'data'    => $errors,
                ) );
                die();
            }

        }

    } else {

        if( $_POST['form_type'] == 'pms_new_subscription' ){

            // We only validate the subscription plans if MSPU is active since the user can have multiple plans
            if( !class_exists( 'PMS_IN_MSU_Form_Handler' ) )
                PMS_Form_Handler::validate_new_subscription_form();
                
            PMS_Form_Handler::validate_subscription_plans();
            PMS_Form_Handler::validate_subscription_plans_member_eligibility();

        } else if( $_POST['form_type'] == 'pms_upgrade_subscription' ){

            PMS_Form_Handler::validate_upgrade_subscription_form();

        } else if( $_POST['form_type'] == 'pms_change_subscription' ){

            PMS_Form_Handler::validate_change_subscription_form();

        } else if( $_POST['form_type'] == 'pms_renew_subscription' ){

            PMS_Form_Handler::validate_renew_subscription_form();

        } else if( $_POST['form_type'] == 'pms_confirm_retry_payment_subscription' ){

            PMS_Form_Handler::validate_retry_payment_form();

        }

        // Validate Billing Fields & others
        do_action( 'pms_process_checkout_validations' );

        $errors = pms_in_stripe_get_generated_errors();

        if( !empty( $errors ) ){
            echo json_encode( array(
                'success'    => false,
                'pms_errors' => $errors,
            ) );
            die();
        }

    }

}