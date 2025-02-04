<?php
    $subscription_statuses = pms_get_member_subscription_statuses();
    $user_id               = pms_get_current_user_id();
?>

<table class="pms-account-subscription-details-table">
    <tbody>

        <?php do_action( 'pms_subscriptions_table_before_rows', $subscription ); ?>

        <!-- Subscription plan -->
        <tr class="pms-account-subscription-details-table__plan">
            <td><?php esc_html_e( 'Subscription Plan', 'paid-member-subscriptions' ); ?></td>
            <td><?php echo esc_html( ! empty( $subscription_plan->name ) ? $subscription_plan->name : '' ); ?></td>
        </tr>

        <!-- Subscription status -->
        <tr class="pms-account-subscription-details-table__status">
            <td><?php esc_html_e( 'Status', 'paid-member-subscriptions' ); ?></td>
            <td class="status-<?php echo esc_html( $subscription->status ) ?>">
                <?php echo ( ! empty( $subscription_statuses[$subscription->status] ) ? esc_html( $subscription_statuses[$subscription->status] ) : '' ); ?>
                <?php echo ( $subscription->is_trial_period() ? ' (' . esc_html__( 'Trial', 'paid-member-subscriptions' ) . ')' : '' ); ?>
            </td>
        </tr>

        <!-- Group Name -->
        <?php if( $subscription_plan->type == 'group' && $group_name = pms_in_gm_get_group_name( $subscription->id ) ) : ?>
            <tr>
                <td><?php esc_html_e( 'Group Name', 'paid-member-subscriptions' ); ?></td>
                <td><?php echo esc_html( $group_name ); ?></td>
            </tr>
        <?php endif; ?>

        <!-- Available Member seats -->
        <?php if( pms_in_gm_is_group_owner( $subscription->id ) ) : ?>
            <tr>
                <td><?php esc_html_e( 'Members', 'paid-member-subscriptions' ); ?></td>
                <td>
                    <?php
                        echo wp_kses_post( sprintf( esc_html__( '%d out of %d', 'paid-member-subscriptions' ), pms_in_gm_get_used_seats( $subscription->id ), pms_in_gm_get_total_seats( $subscription ) ) );

                        $url = pms_in_gm_get_dashboard_url();

                        if( class_exists( 'PMS_IN_Multiple_Subscriptions_Per_User' ) )
                            $url = add_query_arg( 'subscription_id', $subscription->id, $url );

                        if( !empty( $url ) && apply_filters( 'pms_gm_display_manage_group_link', true, $subscription->id ) )
                            printf( ' (<a href="%s">%s</a>)', esc_url( $url ), esc_html__( 'Manage group', 'paid-member-subscriptions' ) );
                    ?>
                </td>
            </tr>
        <?php endif; ?>

        <!-- Subscription start date -->
        <tr class="pms-account-subscription-details-table__start-date">
            <td><?php esc_html_e( 'Start Date', 'paid-member-subscriptions' ); ?></td>
            <td><?php echo ( ! empty( $subscription->start_date ) ? esc_html( ucfirst( date_i18n( get_option('date_format'), strtotime( $subscription->start_date ) ) ) ) : '' ); ?></td>
        </tr>

        <!-- Subscription expiration date -->
        <?php if( empty( $subscription->billing_next_payment ) ) : ?>
            <tr class="pms-account-subscription-details-table__expiration-date">
                <td><?php esc_html_e( 'Expiration Date', 'paid-member-subscriptions' ); ?></td>
                <td><?php echo ( ! empty( $subscription->expiration_date ) ? esc_html( ucfirst( date_i18n( get_option('date_format'), strtotime( $subscription->expiration_date ) ) ) ) : esc_html__( 'Unlimited', 'paid-member-subscriptions' ) ); ?></td>
            </tr>
        <?php endif; ?>

        <!-- Subscription Trial End Date -->
        <?php if( $subscription->is_trial_period() ): ?>
            <tr class="pms-account-subscription-details-table__trial">
                <td><?php esc_html_e( 'Trial End Date', 'paid-member-subscriptions' ); ?></td>
                <td><?php echo esc_html( ucfirst( date_i18n( get_option('date_format'), strtotime( $subscription->trial_end ) ) ) ); ?></td>
            </tr>
        <?php endif; ?>

        <!-- Subscription next payment -->
        <?php if( ! empty( $subscription->billing_next_payment ) && $subscription->status == 'active' ): ?>
        <tr>
            <td><?php esc_html_e( 'Next Payment', 'paid-member-subscriptions' ); ?></td>
            <td><?php echo wp_kses_post( sprintf( _x( '%s on %s', '[amount] on [date]', 'paid-member-subscriptions' ), pms_format_price( $subscription->billing_amount, pms_get_active_currency() ), ucfirst( date_i18n( get_option('date_format'), strtotime( $subscription->billing_next_payment ) ) ) ) ); ?></td>
        </tr>
        <?php endif; ?>

        <?php if( pms_in_gm_is_group_owner( $subscription->id ) ) : ?>
        <!-- Payment Method -->
        <?php 
            if( $subscription->is_auto_renewing() && pms_payment_gateways_support( array( $subscription->payment_gateway ), 'update_payment_method' ) ) : 
            
            $payment_method_data = pms_get_member_subscription_payment_method_details( $subscription->id );
        ?>
            <tr>
                <td><?php esc_html_e( 'Payment Method', 'paid-member-subscriptions' ); ?></td>
                <td>
                    <div class="pms-account-subscription-details-table__payment-method">

                        <?php if( !empty( $payment_method_data ) ) : ?>
                            <div class="pms-account-subscription-details-table__payment-method__wrap">
                                <span class="pms-account-subscription-details-table__payment-method__brand">
                                    <?php 
                                    $assets_url = esc_url( PMS_PLUGIN_DIR_URL ) . 'assets/images/card-icons/';

                                    if( !empty( $payment_method_data['pms_payment_method_type'] ) ) 
                                        echo file_get_contents( $assets_url . $payment_method_data['pms_payment_method_type'] . '.svg' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                    ?>
                                </span>

                                <span class="pms-account-subscription-details-table__payment-method__number">
                                    <?php echo !empty( $payment_method_data['pms_payment_method_number'] ) ? '&bull;&bull;&bull;&bull; ' . esc_html( $payment_method_data['pms_payment_method_number'] ) : '' ?>
                                </span>
                                
                                <span class="pms-account-subscription-details-table__payment-method__expiration">
                                    <?php esc_html_e( 'Expires:', 'paid-member-subscriptions' ) ?>
                                    <?php echo !empty( $payment_method_data['pms_payment_method_expiration_month'] ) ? esc_html( $payment_method_data['pms_payment_method_expiration_month'] ) . ' /' : '' ?>
                                    <?php echo !empty( $payment_method_data['pms_payment_method_expiration_year'] ) ? esc_html( $payment_method_data['pms_payment_method_expiration_year'] ) : '' ?>
                                </span>
                            </div>
                        <?php endif; ?>

                        <?php echo wp_kses_post( apply_filters( 'pms_output_subscription_plan_action_update_payment_method', '<a class="pms-account-subscription-action-link pms-account-subscription-action-link__update-payment-method" href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms-action' => 'update_payment_method', 'subscription_id' => $subscription->id  ), pms_get_current_page_url( true ) ), 'pms_update_payment_method', 'pmstkn' ) ) . '" title="'. __( 'Update the payment method attached to a recurring subscription.', 'paid-member-subscriptions' ) .'">' . __( 'Update', 'paid-member-subscriptions' ) . '</a>', $subscription_plan, $subscription->to_array(), $user_id ) ); ?>

                    </div>
                </td>
            </tr>
        <?php endif; ?>

        <!-- Subscription actions -->
            <tr class="pms-account-subscription-details-table__actions">
                <td><?php esc_html_e( 'Actions', 'paid-member-subscriptions' ); ?></td>
                <td>
                    <?php

                    if( $subscription->status != 'pending' && $subscription_plan->status != 'inactive' ){

                        // Show the Change action if any other subscription plan besides the current one exists
                        $plans           = function_exists( 'pms_get_subscription_plan_others' ) ? pms_get_subscription_plan_others( $user_id ) : pms_get_subscription_plans_list();
                        $plan_upgrades   = pms_get_subscription_plan_upgrades( $subscription_plan->id );
                        $plan_downgrades = pms_get_subscription_plan_downgrades( $subscription_plan->id );

                        // remove current plan
                        if( isset( $plans[$subscription->subscription_plan_id ] ) )
                            unset( $plans[$subscription->subscription_plan_id ] );

                        $payments_settings = get_option( 'pms_payments_settings' );

                        $change_action_name = __( 'Change', 'paid-member-subscriptions' );

                        if( !isset( $payments_settings['allow-downgrades'] ) && !isset( $payments_settings['allow-change'] ) )
                            $change_action_name = __( 'Upgrade', 'paid-member-subscriptions' );
                                                
                        // Display logic
                        $display_action = false;

                        if( ( !isset( $payments_settings['allow-downgrades'] ) && !isset( $payments_settings['allow-change'] ) ) && !empty( $plan_upgrades ) )
                            $display_action = true;
                        else if( ( !isset( $payments_settings['allow-downgrades'] ) && isset( $payments_settings['allow-change'] ) ) && ( !empty( $plans ) || !empty( $plan_upgrades ) ) )
                            $display_action = true;
                        else if( ( !isset( $payments_settings['allow-change'] ) && isset( $payments_settings['allow-downgrades'] ) ) && ( !empty( $plan_downgrades ) || !empty( $plan_upgrades ) ) )
                            $display_action = true;
                        else if( isset( $payments_settings['allow-change'] ) && ( !empty( $plans ) || !empty( $plan_upgrades ) ) )
                            $display_action = true;
                        else if( isset( $payments_settings['allow-downgrades'] ) && ( !empty( $plan_downgrades ) || !empty( $plan_upgrades ) ) )
                            $display_action = true;

                        if( $display_action === true )
                            echo wp_kses_post( apply_filters( 'pms_output_subscription_plan_action_change', '<a class="pms-account-subscription-action-link pms-account-subscription-action-link__change" href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms-action' => 'change_subscription', 'subscription_id' => $subscription->id, 'subscription_plan' => $subscription_plan->id ), pms_get_current_page_url( true ) ), 'pms_member_nonce', 'pmstkn' ) ) . '">' . $change_action_name . '</a>', $subscription_plan, $subscription->to_array(), $user_id ) );

                        // Number of days before expiration to show the renewal action
                        $renewal_display_time = apply_filters( 'pms_output_subscription_plan_action_renewal_time', 15 );

                        if( ( ! $subscription->is_auto_renewing() && strtotime( $subscription->expiration_date ) - time() < $renewal_display_time * DAY_IN_SECONDS ) || $subscription->status == 'canceled' )
                            echo wp_kses_post( apply_filters( 'pms_output_subscription_plan_action_renewal', '<a class="pms-account-subscription-action-link pms-account-subscription-action-link__renew" href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms-action' => 'renew_subscription', 'subscription_id' => $subscription->id, 'subscription_plan' => $subscription_plan->id ), pms_get_current_page_url( true ) ), 'pms_member_nonce', 'pmstkn' ) ) . '">' . __( 'Renew', 'paid-member-subscriptions' ) . '</a>', $subscription_plan, $subscription->to_array(), $user_id ) );

                        if( $subscription->status == 'active' && ( ( $subscription->is_auto_renewing() && pms_is_https() ) || ! $subscription->is_auto_renewing() ) )
                            echo wp_kses_post( apply_filters( 'pms_output_subscription_plan_action_cancel', '<a class="pms-account-subscription-action-link pms-account-subscription-action-link__cancel" href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms-action' => 'cancel_subscription', 'subscription_id' => $subscription->id  ), pms_get_current_page_url( true ) ), 'pms_member_nonce', 'pmstkn' ) ) . '">' . __( 'Cancel', 'paid-member-subscriptions' ) . '</a>', $subscription_plan, $subscription->to_array(), $user_id ) );

                    } else {

                        if( $subscription_plan->price > 0 )
                            echo wp_kses_post( apply_filters( 'pms_output_subscription_plan_pending_retry_payment', '<a class="pms-account-subscription-action-link pms-account-subscription-action-link__retry" href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms-action' => 'retry_payment_subscription', 'subscription_plan' => $subscription_plan->id  ) ), 'pms_member_nonce', 'pmstkn' ) ) . '">' . __( 'Retry payment', 'paid-member-subscriptions' ) . '</a>', $subscription_plan, $subscription->to_array() ) );

                    }

                    if( ( $subscription->is_auto_renewing() && pms_is_https() ) || ! $subscription->is_auto_renewing() )
                        echo wp_kses_post( apply_filters( 'pms_output_subscription_plan_action_abandon', '<a class="pms-account-subscription-action-link pms-account-subscription-action-link__abandon" href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms-action' => 'abandon_subscription', 'subscription_id' => $subscription->id  ), pms_get_current_page_url( true ) ), 'pms_member_nonce', 'pmstkn' ) ) . '">' . __( 'Abandon', 'paid-member-subscriptions' ) . '</a>', $subscription_plan, $subscription->to_array(), $user_id ) );

                    ?>
                </td>
            </tr>
        <?php endif; ?>

        <?php do_action( 'pms_subscriptions_table_after_rows', $subscription ); ?>

    </tbody>
</table>
