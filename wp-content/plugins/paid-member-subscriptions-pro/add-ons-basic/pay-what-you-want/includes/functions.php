<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) )
    return;

/**
 * Display Name Your Price input instead of fixed subscription price on front-end
 */
function pms_in_pwyw_subscription_plan_name_your_price_html( $price_output, $subscription_plan ){

    if( ! is_object( $subscription_plan ) )
        return '';

    // check if Pay What You Want Pricing is enabled for this subscription plan
    if( pms_in_pwyw_pricing_enabled( $subscription_plan->id ) ) {

        $pay_what_you_want_text = get_post_meta( $subscription_plan->id, 'pms_subscription_plan_pay_what_you_want_label', true );

        $amount = isset( $_POST['subscription_price_'.$subscription_plan->id] ) ? sanitize_text_field( $_POST['subscription_price_'.$subscription_plan->id] ) : $subscription_plan->price;

        $price = '<input name="subscription_price_'.$subscription_plan->id.'" type="text" class="small pms_pwyw_pricing" value="'.$amount.'">';

        $currency_symbol = ' (' . pms_get_currency_symbol( pms_get_active_currency() ) . ') ';

        if ( function_exists( 'pms_get_currency_position' ) )
            $price_output = ( pms_get_currency_position() == 'after' ? $price . $currency_symbol : $currency_symbol . $price );
        else {
            $settings = get_option( 'pms_settings' );

            $price_output = ( !isset( $settings['payments']['currency_position'] ) || ( isset( $settings['payments']['currency_position'] ) &&
                $settings['payments']['currency_position'] == 'after' ) ? $price . $currency_symbol : $currency_symbol . $price );
        }

        $pay_what_you_want_price_output = '<span class="pms-divider"> - </span> ' . $pay_what_you_want_text .  $price_output;

        return $pay_what_you_want_price_output;

    }

    return $price_output;

}
add_filter('pms_subscription_plan_output_price', 'pms_in_pwyw_subscription_plan_name_your_price_html', 10, 2);


/**
 * Validate Pay What You Want price entered by the user
 *
 */
function pms_in_pwyw_validate_price( $request = array() ){

    if( empty( $request ) )
        $request = $_POST;

    // Get subscription plan
    if( !empty($request['subscription_plans']) ) {

        $subscription_plan = pms_get_subscription_plan((int)$request['subscription_plans']);

        // Check if selected plan has Pay What You Want Pricing enabled
        if ( pms_in_pwyw_pricing_enabled( $subscription_plan->id ) ) {

            $price = $request['subscription_price_'.$subscription_plan->id];

            // Price can't be empty
            if ( empty( $price ) && ( $price !== '0' ) ) {
                pms_errors()->add( 'subscription_plans', __( 'Please enter a price for the selected subscription plan.', 'paid-member-subscriptions' ) );
                return;
            }

            //Make sure price is numeric and greater or equal to zero
            if ( !is_numeric( $price ) || ( (float)($price) < 0 ) ) {
                pms_errors()->add( 'subscription_plans', __( 'Please enter a numeric price, greater than zero.', 'paid-member-subscriptions' ) );
                return;
            }

            // Get min and max price values, if defined
            $min_price = (float) get_post_meta( $subscription_plan->id, 'pms_subscription_plan_min_price', true );
            $max_price = (float) get_post_meta( $subscription_plan->id, 'pms_subscription_plan_max_price', true );

            // Make sure the price set is between the min and max price allowed
            $currency_symbol = pms_get_currency_symbol( pms_get_active_currency() );

            if ( !empty($min_price) ) {

                if ( !empty($max_price) ) {

                    if ( ( (float)$price < $min_price ) || ( (float)$price > $max_price ) ) {
                        pms_errors()->add( 'subscription_plans', sprintf( __( 'Please enter a price between %1$s and %2$s.', 'paid-member-subscriptions' ), $currency_symbol . $min_price, $currency_symbol . $max_price) );
                        return;
                    }

                }
                else {
                    if ( (float)$price < $min_price ) {
                        pms_errors()->add('subscription_plans', sprintf(__('Please enter a price greater than or equal to %s.', 'paid-member-subscriptions'), $currency_symbol . $min_price ));
                        return;
                    }
                }

            }

            else {
                if ( !empty($max_price) && (float)$price > $max_price ){
                    pms_errors()->add('subscription_plans', sprintf(__('Please enter a price less than or equal to %s.', 'paid-member-subscriptions'), $currency_symbol . $max_price ));
                    return;
                }
            }

        }

    }
}
add_action( 'pms_register_form_validation', 'pms_in_pwyw_validate_price' );
add_action( 'pms_new_subscription_form_validation', 'pms_in_pwyw_validate_price' );
add_action( 'pms_upgrade_subscription_form_validation', 'pms_in_pwyw_validate_price' );
add_action( 'pms_renew_subscription_form_validation', 'pms_in_pwyw_validate_price' );
add_action( 'pms_change_subscription_form_validation', 'pms_in_pwyw_validate_price' );
add_action( 'pms_process_checkout_validations', 'pms_in_pwyw_validate_price' ); // used by Profile Builder form to display subscription plan field errors


/**
 * Function that sets subscription "billing_amount" based on the value entered by the user (Pay What You Want pricing)
 * This is done only for recurring subscriptions that support "plugin_scheduled_payments" (Stripe and PayPal Express Checkout - with Reference Transactions activated)
 *
 * @param array $subcription_data
 * @param array $checkout_data
 * @return array
 *
 */
function pms_in_pwyw_modify_subscription_data_billing_amount( $subscription_data , $checkout_data ){

    if ( isset( $subscription_data['subscription_plan_id'] ) && isset( $_POST[ 'subscription_price_'.$subscription_data['subscription_plan_id' ] ] ) && ( !empty( $subscription_data['billing_amount'] ) || intval( $subscription_data['billing_amount'] ) == 0 ) ) {

        $price = sanitize_text_field( $_POST['subscription_price_'.$subscription_data['subscription_plan_id'] ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated

        $subscription_data['billing_amount'] = $price;
    }

    return $subscription_data;

}
add_filter( 'pms_process_checkout_subscription_data', 'pms_in_pwyw_modify_subscription_data_billing_amount', 10, 2 );


/**
 * Function that modifies subscription price sent to the payment gateway based on the value entered by the user (Pay What You Want pricing)
 *
 * @param array $payment_gateway_data
 * @param array $payments_settings
 * @return array
 */
function pms_in_pwyw_modify_payment_data_price( $payment_gateway_data ) {

    if ( isset( $payment_gateway_data['subscription_data']['subscription_plan_id'] ) && isset( $_POST[ 'subscription_price_'.$payment_gateway_data['subscription_data']['subscription_plan_id' ] ] ) ) {

        // Take into account sign-up fees
        $price = sanitize_text_field( $_POST['subscription_price_'.$payment_gateway_data['subscription_data']['subscription_plan_id'] ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated

        if( !empty( $payment_gateway_data['form_location'] ) && in_array( $payment_gateway_data['form_location'], apply_filters( 'pms_checkout_signup_fee_form_locations', array( 'register', 'new_subscription', 'retry_payment', 'register_email_confirmation', 'change_subscription', 'wppb_register' ) ) ) && !empty( $payment_gateway_data['user_data'] ) && !empty( $payment_gateway_data['user_data']['subscription'] ) && !empty( $payment_gateway_data['user_data']['subscription']->sign_up_fee ) )
            $price = $price + $payment_gateway_data['user_data']['subscription']->sign_up_fee;

        $payment_gateway_data['sign_up_amount'] = $payment_gateway_data['amount'] = $price;

        if ( class_exists( 'PMS_Payment' ) ) {

            $payment = pms_get_payment( $payment_gateway_data['payment_id'] );

            $data = array(
                'amount' => $payment_gateway_data['sign_up_amount'],
                'status' => ( $payment_gateway_data['sign_up_amount'] == 0 ? 'completed' : $payment->status )
            );

            $payment->update( $data );
        }

    }

    return $payment_gateway_data;

}
add_filter( 'pms_register_payment_data', 'pms_in_pwyw_modify_payment_data_price', 10 );

function pms_in_pwyw_process_checkout_validation_payment_gateway() {

    if( ! empty( $_POST['pay_gate'] ) )
        return;

    if ( empty( $_POST['subscription_plans'] ) )
        return;

    $payment_gateway_errors = pms_errors()->get_error_message( 'payment_gateway' );

    if( empty( $payment_gateway_errors ) )
        return;

    $subscription_plan = pms_get_subscription_plan((int)$_POST['subscription_plans']);

    // Check if PWYW pricing is enabled
    if ( pms_in_pwyw_pricing_enabled( $subscription_plan->id ) )
        return;

    $price = !empty( $_POST['subscription_price_'.$subscription_plan->id] ) ? sanitize_text_field( $_POST['subscription_price_'.$subscription_plan->id] ) : 0;

    if ( $price != 0 )
        return;

    $min_price = (float) get_post_meta( $subscription_plan->id, 'pms_subscription_plan_min_price', true );

    if ( $price >= $min_price )
        pms_errors()->remove( 'payment_gateway' );

}
add_action( 'pms_process_checkout_validations', 'pms_in_pwyw_process_checkout_validation_payment_gateway' );

function pms_in_pwyw_pricing_enabled( $plan_id ){

    $enabled = get_post_meta( $plan_id, 'pms_subscription_plan_pay_what_you_want', true );

    if( !empty( $enabled ) && $enabled == '1' )
        return true;

    return false;

}

function pms_in_pwyw_modify_checkout_payment_amount( $amount, $subscription ){

    if ( isset( $subscription->subscription_plan_id ) && !empty( $_POST['subscription_price_' . $subscription->subscription_plan_id ] ) ) {

        $amount = (int)$_POST['subscription_price_'. $subscription->subscription_plan_id ] + $amount; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated

    }

    return $amount;

}
add_filter( 'pms_checkout_payment_amount', 'pms_in_pwyw_modify_checkout_payment_amount', 20, 2 );
