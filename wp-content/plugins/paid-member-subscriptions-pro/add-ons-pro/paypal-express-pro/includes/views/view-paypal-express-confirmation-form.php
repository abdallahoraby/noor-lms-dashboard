<?php
/*
 * Confirmation form HTML output to display to the user to confirm his payment
 *
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

?>

<?php
    global $pms_checkout_details;
    $payment           = pms_get_payment( $pms_checkout_details['payment_data']['payment_id'] );
    $subscription_plan = pms_get_subscription_plan( $payment->subscription_id );
    $is_recurring      = ( !empty( $pms_checkout_details['BILLINGAGREEMENTACCEPTEDSTATUS'] ) && $pms_checkout_details['BILLINGAGREEMENTACCEPTEDSTATUS'] == 1 ? true : false );
    $is_discounted     = ( !empty( $pms_checkout_details['payment_data']['sign_up_amount'] ) && !is_null( $pms_checkout_details['payment_data']['sign_up_amount'] ) ? true : false );
    $pms_settings      = get_option( 'pms_payments_settings' );
    $currency          = apply_filters( 'pms_paypal_express_confirmation_form_currency', $pms_settings['currency'], $payment );
    $currency_symbol   = pms_get_currency_symbol( $currency );

    // the $one_time_payment variable describes a non-recurring subscription that has trial
    $one_time_payment   = false;
    $has_trial          = ( $subscription_plan->has_trial() && in_array( $pms_checkout_details['payment_data']['form_location'], array( 'register', 'new_subscription', 'retry_payment', 'upgrade_subscription', 'register_email_confirmation', 'change_subscription' ) ) );
    $has_sign_up_fee    = ( $subscription_plan->has_sign_up_fee() && in_array( $pms_checkout_details['payment_data']['form_location'], apply_filters( 'pms_checkout_signup_fee_form_locations', array( 'register', 'new_subscription', 'retry_payment', 'register_email_confirmation', 'change_subscription', 'wppb_register' ) ) ) );
    $member             = pms_get_member( $payment->user_id );

    $used_trial = get_option( 'pms_used_trial_' . $subscription_plan->id, false );
    if( !empty( $used_trial ) && in_array( $member->email, $used_trial ) )
        $has_trial = false;

    $has_trial = apply_filters( 'pms_paypal_express_confirmation_form_has_trial', $has_trial, $payment, $subscription_plan, $pms_checkout_details['payment_data'] );
    
    // The three variables refer to the price to be paid in each of the subscription phases
    // $first_payment corresponds to the initial amount, $second_payment to the trial amount, $third_payment to the base amount to be paid
    $first_payment      = ( $has_trial || $has_sign_up_fee ) ? '0' : $pms_checkout_details['AMT'];
    $second_payment     = $has_trial ? '0' : '';
    $third_payment      = apply_filters( 'pms_paypal_express_confirmation_form_third_payment', isset( $pms_checkout_details['L_PAYMENTREQUEST_0_AMT0'] ) ? $pms_checkout_details['L_PAYMENTREQUEST_0_AMT0'] : $pms_checkout_details['AMT'], $pms_checkout_details );

    // Check the case of a one time payment with trial
    if( $is_recurring && $has_trial ){

        $set_express_checkout_custom = get_transient( 'pms_set_express_checkout_custom_' . $payment->id );

        if( $set_express_checkout_custom )
            $one_time_payment = $set_express_checkout_custom['one_time_payment'];
        else
            $one_time_payment = false;

        $is_recurring = !$one_time_payment;

    }

    if( $one_time_payment ){

        if( !empty( $pms_checkout_details['payment_data']['sign_up_amount'] ) )
            $second_payment = strval( $pms_checkout_details['payment_data']['sign_up_amount'] );
        else if( $has_sign_up_fee )
            $second_payment = ( empty( $pms_checkout_details['payment_data']['sign_up_amount'] ) && function_exists( 'pms_in_get_discount_by_code' ) && !empty( $payment->discount_code )  ) ? '0' : $pms_checkout_details['AMT'];

        if( empty( $second_payment ) && !empty( $this->checkout_details['payment_data']['billing_amount'] ) )
            $third_payment = $this->checkout_details['payment_data']['billing_amount'];

        $first_payment = '';

    }
    else if( $has_trial ){
        $first_payment  = !empty( $pms_checkout_details['payment_data']['sign_up_amount'] ) ? strval( $pms_checkout_details['payment_data']['sign_up_amount'] ) : ( $has_sign_up_fee ? $this->checkout_details['AMT'] : '0' );
        $second_payment = isset( $pms_checkout_details['L_PAYMENTREQUEST_0_AMT0'] ) ? $pms_checkout_details['L_PAYMENTREQUEST_0_AMT0'] : $pms_checkout_details['AMT'];

        if( empty( (int)$second_payment ) && !empty( $this->checkout_details['payment_data']['billing_amount'] ) ){
            $third_payment = $this->checkout_details['payment_data']['billing_amount'];
            $second_payment = '0';
        }

    }
    else if( $has_sign_up_fee ){
        $first_payment = !empty( $pms_checkout_details['payment_data']['sign_up_amount'] ) ? strval( $pms_checkout_details['payment_data']['sign_up_amount'] ) : $pms_checkout_details['AMT'];
    }

    if( function_exists( 'pms_in_get_discount_by_code' ) ){
        $discount = pms_in_get_discount_by_code( $payment->discount_code );

        if( empty( $this->checkout_details['payment_data']['sign_up_amount'] ) && $has_trial && !empty( $payment->discount_code ) && !$one_time_payment && $discount != false && !$discount->recurring_payments ){
            $first_payment  = '0';
            $second_payment = $pms_checkout_details['AMT'];
            $third_payment  = $pms_checkout_details['L_PAYMENTREQUEST_0_AMT0'];
        }

    }

    if( !$is_recurring && !$one_time_payment ){
        $first_payment = '';
        $second_payment = '';
    }

    // Check that no duplicate payment amounts will be displayed
    $same_first_payment  = ( floatval( $first_payment ) == floatval( $third_payment ) && !$has_trial && !$subscription_plan->is_fixed_period_membership() );
    $same_second_payment = ( floatval( $second_payment ) == floatval( $third_payment ) || floatval( $first_payment ) == floatval( $second_payment ) );

    // Filter the duration and duration unit of a free trial that is displayed
    $display_trial_duration      = apply_filters( 'pms_paypal_express_confirmation_form_display_trial_duration', $subscription_plan->trial_duration, $payment, $subscription_plan, $pms_checkout_details['payment_data'] );
    $display_trial_duration_unit = apply_filters( 'pms_paypal_express_confirmation_form_display_trial_duration_unit', $subscription_plan->trial_duration_unit, $payment, $subscription_plan, $pms_checkout_details['payment_data'] );

    $extra_classes = apply_filters( 'pms_add_extra_form_classes', '' , 'ppe_confirm_payment_container' );
?>

<div id="pms_ppe_confirm_payment" class="pms-form <?php echo esc_attr( $extra_classes ) ?>">

    <?php do_action( 'pms_ppe_content_before_confirmation_table', $subscription_plan, $payment ); ?>

    <h3><?php echo esc_html( apply_filters( 'pms_ppe_confirm_payment_heading', __( 'Payment confirmation', 'paid-member-subscriptions' ) ) ); ?></h3>
    <table id="pms-confirm-payment">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Subscription', 'paid-member-subscriptions' ); ?></th>
                <th><?php esc_html_e( 'Price', 'paid-member-subscriptions' ); ?></th>
                <th><?php esc_html_e( 'Recurring', 'paid-member-subscriptions' ); ?></th>
            </tr>
        </thead>

        <tbody>
            <tr>
                <td><?php echo esc_html( $subscription_plan->name ); ?></td>
                <td>
                    <?php

                        if( $has_trial )
                            $trial_expiration_date = strtotime( date( 'Y-m-d 00:00:00', strtotime( 'now + ' . $display_trial_duration . ' ' . $display_trial_duration_unit ) ) );

                        // Used for handling the payment amount displayed for a recurring Fixed Period Membership if the user didn't subscribe exactly on the expiration date
                        if( $subscription_plan->is_fixed_period_membership() ){
                            $expiration_date = strtotime( date( 'Y-m-d 00:00:00', strtotime( $subscription_plan->get_expiration_date() ) ) );
                            if( $is_recurring && $expiration_date != strtotime( date( 'Y-m-d 00:00:00', strtotime( 'today + 1 year' ) ) ) ){
                                if( !( $has_trial && $trial_expiration_date >= $expiration_date ) )
                                    $fixed_period_days_difference = true;
                            }
                        }

                        // Handle the case of duplicate payment amounts for recurring subscriptions
                        if( $same_second_payment ){
                            if( $is_recurring ){
                                // This can be allowed only when the corresponding periods differ (like in the case of Fixed Period Memberships if the user didn't subscribe exactly on the expiration date)
                                if( $subscription_plan->is_fixed_period_membership() && ( ( $has_trial && $trial_expiration_date < $expiration_date ) || ( isset( $fixed_period_days_difference ) ) ) ){
                                    $same_second_payment = false;
                                }
                            }
                            else{
                                $same_second_payment = false;
                            }
                        }

                        echo ( $first_payment != '' && !$same_first_payment ) ? '<div>' . esc_html( $currency_symbol ) . esc_html( $first_payment ) . '</div>' : '';
                        echo ( $second_payment != '' && !$same_second_payment ) ? '<div>' . esc_html( $currency_symbol ) . esc_html( $second_payment ) . '</div>' : '';
                        echo '<div>' . esc_html( $currency_symbol ) . esc_html( $third_payment ) . '</div>';

                    ?>
                </td>
                <td>
                    <?php
                    if( $has_trial ){
                        if( $is_recurring || !$subscription_plan->is_fixed_period_membership() || ( $trial_expiration_date < $expiration_date ) ){

                            if( $subscription_plan->is_fixed_period_membership() && $trial_expiration_date >= $expiration_date ){
                                $trial_days_left = ( strtotime( date( 'Y-m-d 00:00:00', strtotime( $subscription_plan->get_expiration_date() ) ) ) - strtotime( date( 'Y-m-d 00:00:00', strtotime( 'now' ) ) ) ) / 86400;
                                echo '<div>' . sprintf( esc_html( _n( 'For first %1$d %2$s', 'For first %1$d %2$ss', esc_html( $trial_days_left ), 'paid-member-subscriptions' ) ), esc_html( $trial_days_left ), 'day' ) . '</div>';
                            } else
                                echo '<div>' . sprintf( esc_html( _n( 'For first %1$d %2$s', 'For first %1$d %2$ss', esc_html( $display_trial_duration ), 'paid-member-subscriptions' ) ), esc_html( $display_trial_duration ), esc_html( $display_trial_duration_unit ) ) . '</div>';

                        }
                    }

                    if( $is_recurring ){

                        if( $subscription_plan->is_fixed_period_membership() ){

                            if( ( $has_trial && $trial_expiration_date < $expiration_date ) || ( isset( $fixed_period_days_difference ) ) )
                                echo '<div>' . sprintf( esc_html__( 'Until %s', 'paid-member-subscriptions' ), esc_html( date_i18n( get_option( 'date_format' ), strtotime( $subscription_plan->get_expiration_date() ) ) ) ) . '</div>';
                            else
                                echo ( !$same_second_payment && !$same_first_payment ) ? '<div>' . esc_html__( 'For the first year', 'paid-member-subscriptions' ) . '</div>' : '';

                        } else
                            echo ( !$same_second_payment && !$same_first_payment ) ? '<div>' . sprintf( esc_html( _n( 'For %1$d %2$s', 'For %1$d %2$ss', esc_html( $subscription_plan->duration ), 'paid-member-subscriptions' ) ), esc_html( $subscription_plan->duration ), esc_html( $subscription_plan->duration_unit ) ) . '</div>' : '';

                    }

                    if( $is_recurring ){

                        if( $subscription_plan->is_fixed_period_membership() )
                            echo '<div>' . sprintf( esc_html__( 'Once every year', 'paid-member-subscriptions' ) ) . '</div>';
                        else
                            echo '<div>' . sprintf( esc_html( _n( 'Once every %1$d %2$s', 'Once every %1$d %2$ss', esc_html( $subscription_plan->duration ), 'paid-member-subscriptions' ) ), esc_html( $subscription_plan->duration ), esc_html( $subscription_plan->duration_unit ) ) . '</div>';
                        

                    } else {

                        if( $has_trial ){

                            if( $subscription_plan->is_fixed_period_membership() )
                                echo '<div>' . sprintf( esc_html__( 'Until %s', 'paid-member-subscriptions' ), esc_html( date_i18n( get_option( 'date_format' ), strtotime( $subscription_plan->get_expiration_date() ) ) ) ) . '</div>';
                            else
                                echo '<div>' . sprintf( esc_html( _n( 'For %1$d %2$s', 'For %1$d %2$ss', esc_html( $subscription_plan->duration ), 'paid-member-subscriptions' ) ), esc_html( $subscription_plan->duration ), esc_html( $subscription_plan->duration_unit ) ) . '</div>';

                        } else
                            echo '-';

                    }

                    ?>
                </td>
            </tr>
        </tbody>

    </table>

    <form id="pms-paypal-express-confirmation-form" action="<?php echo esc_url( remove_query_arg( array( 'token', 'PayerID' ), pms_get_current_page_url() ) ) ?>" method="POST">

        <?php do_action( 'pms_ppe_confirm_form_top', $pms_checkout_details, $payment ); ?>

        <input type="hidden" name="pmstkn" value="<?php echo esc_attr( wp_create_nonce( 'pms_payment_process_confirmation' ) ) ?>" />

        <input type="hidden" name="pms_token" value="<?php echo ( isset($pms_checkout_details['TOKEN']) ? esc_attr( $pms_checkout_details['TOKEN'] ) : '' ); ?>" />
        <?php if( $is_recurring ): ?>
            <input type="hidden" name="pms_is_recurring" value="1" />
        <?php endif; ?>

        <input type="submit" value="<?php echo esc_html( apply_filters( 'pms_ppe_confirm_payment_button_value', __( 'Confirm payment', 'paid-member-subscriptions' ) ) ); ?>" />

        <?php do_action( 'pms_ppe_confirm_form_bottom', $pms_checkout_details, $payment ); ?>

    </form>

    <?php do_action( 'pms_ppe_content_after_confirmation_table', $subscription_plan, $payment ); ?>

</div>
