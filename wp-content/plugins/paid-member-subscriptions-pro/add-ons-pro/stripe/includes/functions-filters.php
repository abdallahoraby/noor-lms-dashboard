<?php
// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

/**
 * Add Stripe to the payment gateways array
 *
 * @param array $payment_gateways
 *
 */
function pms_in_payment_gateways_stripe( $payment_gateways = array() ) {

    $payment_gateways['stripe'] = array(
        'display_name_user'  => __( 'Credit / Debit Card', 'paid-member-subscriptions' ),
        'display_name_admin' => 'Stripe',
        'class_name'         => 'PMS_IN_Payment_Gateway_Stripe',
        'description'        =>  __( 'Payments using credit cards directly on your website through Stripe API.', 'paid-member-subscriptions' )
    );

    if( version_compare( PMS_VERSION, '1.9.3', '>=' ) ) {
        $payment_gateways['stripe_intents'] = array(
            'display_name_user'  => __( 'Credit / Debit Card', 'paid-member-subscriptions' ),
            'display_name_admin' => 'Stripe (Payment Intents)',
            'class_name'         => 'PMS_IN_Payment_Gateway_Stripe_Payment_Intents',
            'description'        =>  __( 'Payments using credit cards directly on your website through Stripe API.', 'paid-member-subscriptions' )
        );
    }

    return $payment_gateways;

}
add_filter( 'pms_payment_gateways', 'pms_in_payment_gateways_stripe' );

function pms_in_admin_payment_gateways_stripe( $payment_gateways ){

    $pms_payments_settings        = get_option( 'pms_payments_settings', array() );
    $disabled_base_stripe_gateway = true;

    if( !empty( $pms_payments_settings ) && !empty( $pms_payments_settings['active_pay_gates'] ) ){

        if( in_array( 'stripe', $pms_payments_settings['active_pay_gates'] ) ){

            $disabled_base_stripe_gateway = false;
        }

    }

    if( $disabled_base_stripe_gateway && isset( $payment_gateways['stripe'] ) )
        unset( $payment_gateways['stripe'] );

    return $payment_gateways;

}
add_filter( 'pms_admin_display_payment_gateways', 'pms_in_admin_payment_gateways_stripe', 20, 2 );

/**
 * Hooks to 'pms_confirm_cancel_subscription' from PMS to change the default value provided
 * Makes an api call to Stripe to cancel the subscription, if is successful returns true,
 * but if not returns an array with 'error'
 *
 * @param bool $confirmation
 * @param int $user_id
 * @param int $subscription_plan_id
 *
 * @return mixed    - bool true if successful, array if not
 *
 */
function pms_in_stripe_confirm_cancel_subscription( $confirmation, $user_id, $subscription_plan_id ) {

    // Get payment_profile_id
    $payment_profile_id = pms_member_get_payment_profile_id( $user_id, $subscription_plan_id );

    // Continue only if the profile id is a PayPal one
    if( !pms_in_is_stripe_payment_profile_id($payment_profile_id) )
        return $confirmation;

    // Instantiate the payment gateway with data
    $payment_data = array(
        'user_data' => array(
            'user_id'       => $user_id,
            'subscription'  => pms_get_subscription_plan( $subscription_plan_id )
        )
    );

    $stripe_gate = pms_get_payment_gateway( 'stripe', $payment_data );

    // Cancel the subscription and return the value
    $confirmation = $stripe_gate->cancel_subscription( $payment_profile_id );

    if( !$confirmation )
        $confirmation = array( 'error' => __( 'Something went wrong.', 'paid-member-subscriptions' ) );

    return $confirmation;

}
add_filter( 'pms_confirm_cancel_subscription', 'pms_in_stripe_confirm_cancel_subscription', 10, 3 );

// When the Stripe Payment Intents API is active and the plugin tries to charge an user
// through the regular Charges API, switch the charge to the Payment Intents implementation
add_filter( 'pms_get_payment_gateway_class_name', 'pms_in_stripe_filter_payment_gateway', 20, 3 );
function pms_in_stripe_filter_payment_gateway( $class, $gateway_slug, $payment_data ){
    $active_stripe_gateway = pms_in_get_active_stripe_gateway();

    if( empty( $active_stripe_gateway ) )
        return $class;
    else if( $active_stripe_gateway == 'stripe_intents' && $gateway_slug == 'stripe' )
        return 'PMS_IN_Payment_Gateway_Stripe_Payment_Intents';

    return $class;
}

// Update the payment gateway slug in the payment data when processing a regular Stripe payment
// through the Payment Intents API
add_filter( 'pms_cron_process_member_subscriptions_payment_data', 'pms_in_stripe_filter_member_subscriptions_payment_data', 20, 2 );
function pms_in_stripe_filter_member_subscriptions_payment_data( $data, $subscription ){
    $active_stripe_gateway = pms_in_get_active_stripe_gateway();

    if( empty( $active_stripe_gateway ) )
        return $data;
    else if( $active_stripe_gateway == 'stripe_intents' && $subscription->payment_gateway == 'stripe' )
        $data['payment_gateway'] = $active_stripe_gateway;

    return $data;
}

// Show success message after successfull authentication
add_filter( 'pms_account_shortcode_content', 'pms_in_stripe_payment_authentication_success_message', 20, 2 );
add_filter( 'pms_member_account_not_logged_in', 'pms_in_stripe_payment_authentication_success_message', 20, 2 );
function pms_in_stripe_payment_authentication_success_message( $content, $args ){
    if( isset( $_GET['pms-action'], $_GET['success'] ) && $_GET['pms-action'] == 'authenticate_stripe_payment' ){
        ob_start(); ?>

            <div class="pms_success-messages-wrapper">
                <p>
                    <span class="pms-notice-title"><?php esc_html_e('SUCCESS! ', 'paid-member-subscriptions')?></span>
                    <?php esc_html_e( 'Payment authenticated successfully.', 'paid-member-subscriptions' ); ?>
                </p>
            </div>

        <?php
        $message = ob_get_clean();

        return $message . $content;
    }

    return $content;
}

add_filter( 'pms_cron_process_member_subscriptions_subscription_data', 'pms_in_stripe_handle_subscription_expiration', 20, 3 );
function pms_in_stripe_handle_subscription_expiration( $subscription_data, $response, $payment ){
    if( $subscription_data['status'] == 'expired'
        && $payment->payment_gateway == 'stripe_intents'
        && pms_in_get_active_stripe_gateway() == 'stripe_intents'
        && pms_get_payment_meta( $payment->id, 'authentication', true ) == 'yes' ){

        unset( $subscription_data['billing_duration'] );
        unset( $subscription_data['billing_duration_unit'] );
        unset( $subscription_data['billing_next_payment'] );

        $subscription_data['status'] = 'pending';
    }

    return $subscription_data;
}


