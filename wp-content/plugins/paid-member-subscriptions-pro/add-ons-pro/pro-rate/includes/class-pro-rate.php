<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) )
    return;

Class PMS_IN_ProRate {
    
    public function __construct(){

        if( is_user_logged_in() ){

            // Front-end price output
            add_filter( 'pms_subscription_plan_output_price', array( $this, 'modify_price_output' ), 20, 3 );

            // Front-end duration output
            add_filter( 'pms_subscription_plan_output_duration', array( $this, 'modify_subscription_duration_output' ), 20, 3 );
            
            // Front-end trial output
            add_filter( 'pms_subscription_plan_output_trial', array( $this, 'modify_subscription_trial_output' ), 20, 3 );
            
            // Add notification about pro-rated discount for users
            add_action( 'pms_change_subscription_form_after_downgrade_group', array( $this, 'form_show_prorate_notification_with_change' ), 20, 4 );

            add_filter( 'pms_output_subscription_plans', array( $this, 'form_show_prorate_notification_without_change' ), 4, 7 );

            // Modify payment data before it's inserted into DB
            add_filter( 'pms_process_checkout_payment_data', array( $this, 'modify_payment_amount' ), 10, 2 );

            // PSP RECURRING
            add_filter( 'pms_process_checkout_subscription_data', array( $this, 'modify_checkout_subscription_data' ), 60, 2 );

            // Discount codes add-on
            add_filter( 'pms_dc_output_apply_discount_message_amount', array( $this, 'modify_discount_codes_amount'), 10 );

            add_filter( 'pms_dc_apply_discount_success_message', array( $this, 'modify_discount_codes_success_message'), 10, 7 );

            add_filter( 'pms_dc_success_message_plan_price', array( $this, 'modify_discount_codes_sucess_message_plan_price' ), 20, 3 );

            // Input data attributes
            add_filter( 'pms_get_subscription_plan_input_data_attrs', array( $this, 'modify_input_data_attributes_subscription_plan_price' ), 20, 3 );

            // Mark payment as user for proration
            add_action( 'pms_member_subscription_update', array( $this, 'mark_payment_as_prorated' ), 20, 3 );

            // Allow users to checkout without a gateway with pro-rated plans
            add_filter( 'pms_validate_payment_gateway_no_gateway_logged_in', array( $this, 'maybe_allow_checkout_without_gateway_with_non_free_plans' ), 20, 3 );

            // For PayPal gateways, we make use of the trial functionality to offer the free subscription time in case a recurring subscription needs to be setup
            add_filter( 'pms_checkout_has_trial', array( $this, 'maybe_enable_checkout_trial' ), 20, 6 );

            // Modify PayPal Standard recurring subscription args so pro-rated free time is taken into account
            add_filter( 'pms_paypal_standard_args', array( $this, 'apply_prorate_discount_to_paypal_standard_arguments' ), 20, 3 );

            /**
             * PayPal Express
             */
            // Modify initial request args
            add_filter( 'pms_paypal_express_request_args', array( $this, 'apply_prorate_discount_to_paypal_express_arguments' ), 20, 2 );
            
            // Modify confirmation form request args
            add_filter( 'pms_paypal_express_confirmation_request_args', array( $this, 'apply_prorate_discount_to_paypal_express_confirmation_arguments' ), 20, 2 );

            // Disable the used trial verification for the paypal express checkout
            add_filter( 'pms_paypal_express_checkout_verify_used_trial', array( $this, 'disable_paypal_express_checkout_used_trial_verification' ), 20, 4 );

            // Enable trial for confirmation form
            add_filter( 'pms_paypal_express_confirmation_form_has_trial', array( $this, 'enable_trial_for_paypal_express_confirmation_form' ), 20, 4 );

            // Change trial duration for confirmation form
            add_filter( 'pms_paypal_express_confirmation_form_display_trial_duration', array( $this, 'change_paypal_express_confirmation_form_trial_duration' ), 20, 4 );

            // Change trial duration unit for confirmation form
            add_filter( 'pms_paypal_express_confirmation_form_display_trial_duration_unit', array( $this, 'change_paypal_express_confirmation_form_trial_duration_unit' ), 20, 4 );

            // Change third payment parameter for the confirmation form
            add_filter( 'pms_paypal_express_confirmation_form_third_payment', array( $this, 'change_paypal_express_confirmation_form_third_payment' ), 20, 2 );

            // Add Subscription Logs messages for free time granted
            add_filter( 'pms_subscription_logs_system_error_messages', array( $this, 'add_subscription_logs_messages' ), 20, 2 );

            // Add Payment Logs for pro-rate discounts
            add_filter( 'pms_register_payment_data', array( $this, 'log_prorate_discounts_on_payments' ), 20, 2 );

            // Add Payment Logs messages
            add_filter( 'pms_payment_logs_system_error_messages', array( $this, 'payments_add_custom_log_system_error_messages' ), 20, 2 );

        }

        // Modify expiration date when subscription is upgrade/downgraded through PayPal
        add_filter( 'pms_paypal_subscr_payment_change_subscription_expiration_date', array( $this, 'modify_paypal_subscription_change_expiration_date' ), 20, 4 );
        add_filter( 'pms_paypal_express_payment_change_subscription_expiration_date', array( $this, 'modify_paypal_subscription_change_expiration_date' ), 20, 4 );

    }

    public function modify_price_output( $output, $subscription_plan, $form_location ){

        if( !in_array( $form_location, array( 'upgrade_subscription', 'downgrade_subscription' ) ) || !isset( $_GET['subscription_id'] ) || $subscription_plan->price == 0 )
            return $output;

        $prorated_discount_data = $this->get_prorated_subscription_discount( absint( $_GET['subscription_id'] ), $subscription_plan->id );

        if( $prorated_discount_data === false )
            return $output;

        // If discount amount is greater than 1 subscription period, the user needs to get 
        // a subscription period for free + extra time
        if( $prorated_discount_data['type'] == 'free_period' ){

            if( !isset( $prorated_discount_data['remaining_discount_extra_period_days'] ) || $prorated_discount_data['remaining_discount_extra_period_days'] == '0' || $subscription_plan->is_fixed_period_membership() )
                $prorated_price = '<span class="pms-subscription-plan-price-value">' . __( 'Free', 'paid-member-subscriptions' ) . '</span>';
            else 
                $prorated_price = sprintf( __( '%s days for free', 'paid-member-subscriptions' ), $prorated_discount_data['remaining_discount_extra_period_days'] + $prorated_discount_data['new_plan_period_length_in_days'] );

        } else {
            
            $prorated_price = pms_format_price( $this->process_amount( $subscription_plan->price - $prorated_discount_data['prorate_discount'] ), pms_get_active_currency(), array( 'before_price' => '<span class="pms-subscription-plan-price-value">', 'after_price' => '</span>', 'before_currency' => '<span class="pms-subscription-plan-currency">', 'after_currency' => '</span>' ) );

        }

        return '<span class="pms-divider"> - </span>' . $prorated_price . ' ';

    }

    public function modify_subscription_duration_output( $output, $subscription_plan, $form_location ){

        if( !in_array( $form_location, array( 'upgrade_subscription', 'downgrade_subscription' ) ) || !isset( $_GET['subscription_id'] ) || $subscription_plan->price == 0 )
            return $output;

        $prorated_discount_data = $this->get_prorated_subscription_discount( absint( $_GET['subscription_id'] ), $subscription_plan->id );

        if( isset( $prorated_discount_data['type'] ) && $prorated_discount_data['type'] === 'free_period' )
            $output = '';

        $recurring_period = '';

        if( $subscription_plan->recurring != 3 && !$subscription_plan->is_fixed_period_membership() ){

            $recurring_period = sprintf( __( 'then %s %s', 'paid-member-subscriptions' ), pms_format_price( $subscription_plan->price ), pms_get_output_subscription_plan_duration( $subscription_plan, 'custom' ) );

            $recurring_period = '<span class="pms-subscription-plan-price__recurring">'. $recurring_period .'</span>';

        } else if( $subscription_plan->is_fixed_period_membership() ){

            $recurring_period = pms_get_output_subscription_plan_duration( $subscription_plan, 'custom' );

            if( $subscription_plan->recurring != 3 && $subscription_plan->fixed_period_renewal_allowed() ){

                $recurring_period .= '<span class="pms-subscription-plan-price__recurring"> ' . sprintf( __( 'then %s every year', 'paid-member-subscriptions' ), pms_format_price( $subscription_plan->price ) ) . '</span>';

            }

        }

        return $output . ' ' . $recurring_period;
        
    }

    public function modify_subscription_trial_output( $output, $subscription_plan, $form_location ){

        if( !in_array( $form_location, array( 'upgrade_subscription', 'downgrade_subscription' ) ) || !isset( $_GET['subscription_id'] ) || $subscription_plan->price == 0 )
            return $output;

        $prorated_discount_data = $this->get_prorated_subscription_discount( absint( $_GET['subscription_id'] ), $subscription_plan->id );

        if( $prorated_discount_data === false )
            return $output;

        if( $prorated_discount_data['type'] == 'free_period' )
            $output = '';

        return $output;
        
    }

    public function form_show_prorate_notification_with_change( $current_subscription, $subscription_plan_upgrades, $subscription_plan_downgrades, $subscription_plan_others ){

        if( empty( $subscription_plan_others ) )
            return;

        if( !empty( $current_subscription->id ) && ( !empty( $subscription_plan_upgrades ) || !empty( $subscription_plan_downgrades ) ) ){

            $plans        = array_merge( $subscription_plan_upgrades, $subscription_plan_downgrades );
            $show_message = false;

            if( !empty( $plans ) ){
                foreach( $plans as $plan ){

                    $prorated_discount_data = $this->get_prorated_subscription_discount( $current_subscription->id, $plan->id );

                    if( !empty( $prorated_discount_data ) && !empty( $prorated_discount_data['prorate_discount'] ) ){
                        $show_message = true;
                        break;
                    }

                }
            }

            if( !empty( $show_message ) ) : ?>
                <div class="pms-prorate-frontend-message">
                    <?php printf( wp_kses_post( __( 'Subscription Upgrades and Downgrades are pro-rated. The prices above include a discount of %s.', 'paid-member-subscriptions' ) ), '<strong>' . esc_html( pms_format_price( $prorated_discount_data['prorate_discount'] ) ) . '</strong>' ); ?>
                </div>
            <?php endif;

        }

    }

    public function form_show_prorate_notification_without_change( $output, $include, $exclude_id_group, $member, $payments_settings, $subscription_plans, $form_location ){

        if( !isset( $_GET['subscription_plan'] ) || !isset( $_GET['subscription_id'] ) )
            return $output;

        $current_subscription_plan_id = absint( $_GET['subscription_plan'] );

        $subscription_plan_upgrades = pms_get_subscription_plan_upgrades( $current_subscription_plan_id );

        if( isset( $payments_settings['allow-downgrades'] ) && $payments_settings['allow-downgrades'] == '1' )
            $subscription_plan_downgrades = pms_get_subscription_plan_downgrades( $current_subscription_plan_id );
        else
            $subscription_plan_downgrades = array();

        if( !isset( $payments_settings['allow-change'] ) || $payments_settings['allow-change'] != '1' )
            $show_message = true;
        else if( isset( $payments_settings['allow-change'] ) && $payments_settings['allow-change'] == '1' ){

            // Grab only the Plan IDs from the Upgrades and Downgrades lists and then merge them
            $excluded = array_merge( array_map( function( $plan ) { return $plan->id; }, $subscription_plan_upgrades ), array_map( function( $plan ) { return $plan->id; }, $subscription_plan_downgrades ) );
            
            $member = pms_get_member( get_current_user_id() );

            // Exclude subscriptions that the user already has
            if( !empty( $member->subscriptions ) ){
                foreach( $member->subscriptions as $member_subscription ){

                    // Need to exclude the whole tier if a user is subscribed to another plan
                    $plans_from_tier = pms_get_subscription_plans_group( $member_subscription['subscription_plan_id'] );

                    foreach( $plans_from_tier as $plan ){
                        $excluded[] = $plan->id;
                    }

                }
            }

            $subscription_plan_others = pms_get_subscription_plans( true, array(), $excluded );

            if( empty( $subscription_plan_others ) )
                $show_message = true;

        }

        if( !empty( $show_message ) ) :

            if( !empty( $subscription_plan_upgrades ) && !empty( $subscription_plan_downgrades ) && $form_location == 'upgrade_subscription' )
                return $output;
            else if( empty( $subscription_plan_upgrades ) && !empty( $subscription_plan_downgrades ) && $form_location == 'upgrade_subscription' )
                return $output;
            else if ( !empty( $subscription_plan_upgrades ) && empty( $subscription_plan_downgrades ) && $form_location == 'downgrade_subscription' )
                return $output;

            $plans = array_merge( $subscription_plan_upgrades, $subscription_plan_downgrades );

            if( !empty( $plans ) ){
                foreach( $plans as $plan ){

                    $prorated_discount_data = $this->get_prorated_subscription_discount( absint( $_GET['subscription_id'] ), $plan->id );

                    if( empty( $prorated_discount_data ) || empty( $prorated_discount_data['prorate_discount'] ) ){
                        $show_message = false;
                        break;
                    }

                }
            }
            
            ob_start(); ?>
            
            <?php if( !empty( $show_message ) ) : ?>
                <div class="pms-prorate-frontend-message">
                    <?php printf( wp_kses_post( __( 'Subscription Upgrades and Downgrades are pro-rated. The prices above include a discount of %s.', 'paid-member-subscriptions' ) ), '<strong>' . esc_html( pms_format_price( $prorated_discount_data['prorate_discount'] ) ) . '</strong>' ); ?>
                </div>
            <?php endif; ?>

            <?php
                $message = ob_get_contents();
                ob_end_clean();

                return $output . $message;

        endif;

        return $output;

    }

    public function modify_payment_amount( $payment_data, $checkout_data ){

        if( !isset( $_REQUEST['subscription_id'] ) || !isset( $payment_data['subscription_plan_id'] ) || !isset( $checkout_data['form_location'] ) || !in_array( $checkout_data['form_location'], array( 'upgrade_subscription', 'downgrade_subscription' ) ) )
            return $payment_data;

        $prorated_discount_data = $this->get_prorated_subscription_discount( absint( $_REQUEST['subscription_id'] ), $payment_data['subscription_plan_id'] );

        if( $prorated_discount_data === false )
            return $payment_data;

        if( $prorated_discount_data['type'] == 'free_period' ){
            $payment_data['amount'] = 0;

            if( isset( $payment_data['sign_up_amount'] ) )
                $payment_data['sign_up_amount'] = 0;

        } else {
            $payment_data['amount'] = $this->process_amount( $payment_data['amount'] - $prorated_discount_data['prorate_discount'] );

            if( isset( $payment_data['sign_up_amount'] ) )
                $payment_data['sign_up_amount'] = $this->process_amount( $payment_data['sign_up_amount'] - $prorated_discount_data['prorate_discount'] );
        }

        return $payment_data;

    }

    public function modify_checkout_subscription_data( $subscription_data = array(), $checkout_data = array() ){

        if( empty( $subscription_data ) )
            return array();

        // Don't apply this for PayPal payments
        if( isset( $_POST['pay_gate'] ) && in_array( $_POST['pay_gate'], array( 'paypal_standard', 'paypal_express' ) ) )
            return $subscription_data;

        if( !isset( $_POST['pms_current_subscription'] ) || !isset( $checkout_data['form_location'] ) || !isset( $subscription_data['subscription_plan_id'] ) )
            return $subscription_data;

        if( !in_array( $checkout_data['form_location'], array( 'upgrade_subscription', 'downgrade_subscription' ) ) )
            return $subscription_data;

        $prorated_discount_data = $this->get_prorated_subscription_discount( absint( $_POST['pms_current_subscription'] ), $subscription_data['subscription_plan_id'], true );

        if( $prorated_discount_data === false )
            return $subscription_data;

        $subscription_plan = pms_get_subscription_plan( $subscription_data['subscription_plan_id'] );

        // Extend expiration date based on pro-rate data
        if( $prorated_discount_data['type'] == 'free_period' && !empty( $prorated_discount_data['remaining_discount_extra_period_days'] ) ){

            $expiration_date = new DateTime();
            $expiration_date->add( date_interval_create_from_date_string( $prorated_discount_data['remaining_discount_extra_period_days'] + $prorated_discount_data['new_plan_period_length_in_days'] . ' days' ) );

            if( !$subscription_plan->is_fixed_period_membership() ){

                $subscription_data['expiration_date'] = $expiration_date->format('Y-m-d H:i:s');

                // PSP recurring subscriptions only
                if( isset( $checkout_data['is_recurring'] ) && $checkout_data['is_recurring'] == true ){

                    $subscription_data['billing_next_payment'] = $expiration_date->format('Y-m-d H:i:s');

                    if( $_POST['pay_gate'] != 'manual' )
                        $subscription_data['expiration_date'] = '';
                
                // We're here because we need to apply extra subscription time to a non-recurring subscription
                } else {

                    $subscription_data['billing_next_payment']  = '';
                    $subscription_data['billing_amount']        = '';
                    $subscription_data['billing_duration']      = '';
                    $subscription_data['billing_duration_unit'] = '';
                    $subscription_data['payment_profile_id']    = '';

                }

            }

            pms_add_member_subscription_log( absint( $_POST['pms_current_subscription'] ), 'subscription_prorated', array( 'free_days' => $prorated_discount_data['remaining_discount_extra_period_days'] + $prorated_discount_data['new_plan_period_length_in_days'], 'fixed_period' => $subscription_plan->is_fixed_period_membership() ) );

        }

        return $subscription_data;

    }

    public function modify_discount_codes_amount( $amount ){

        if( !isset( $_POST['pms_current_subscription'] ) || !isset( $_POST['subscription'] ) )
            return $amount;

        $form_location = PMS_Form_Handler::get_request_form_location();       

        if( !in_array( $form_location, array( 'upgrade_subscription', 'downgrade_subscription' ) ) )
            return $amount;

        $prorated_discount_data = $this->get_prorated_subscription_discount( absint( $_POST['pms_current_subscription'] ), absint( $_POST['subscription'] ) );

        if( $prorated_discount_data === false )
            return $amount;

        if( $prorated_discount_data['type'] == 'free_period' )
            return 0;
        else
            return $this->process_amount( $amount - $prorated_discount_data['prorate_discount'] );

    }
    
    public function modify_discount_codes_success_message( $response, $code, $subscription, $pwyw_price, $is_recurring, $initial_payment, $recurring_payment ){

        if( !isset( $_POST['pms_current_subscription'] ) || !isset( $_POST['subscription'] ) )
            return $response;

        $form_location = PMS_Form_Handler::get_request_form_location();       

        if( !in_array( $form_location, array( 'upgrade_subscription', 'downgrade_subscription' ) ) )
            return $response;

        $prorated_discount_data = $this->get_prorated_subscription_discount( absint( $_POST['pms_current_subscription'] ), absint( $_POST['subscription'] ) );

        if( $prorated_discount_data === false )
            return $response;

        if( $prorated_discount_data['type'] == 'free_period' && $is_recurring ){

            $response = __( 'Discount successfully applied! ', 'paid-member-subscriptions' );
            $response .= sprintf( __( 'Amount to be charged after the free period is %1$s.', 'paid-member-subscriptions' ), pms_format_price( $recurring_payment, pms_get_active_currency() ) );

        }

        return $response;

    }

    public function modify_discount_codes_sucess_message_plan_price( $price, $subscription_plan, $form_location ){

        if( !isset( $_POST['pms_current_subscription'] ) || empty( $form_location ) || !in_array( $form_location, array( 'upgrade_subscription', 'downgrade_subscription' ) ) )
            return $price;

        $prorated_discount_data = $this->get_prorated_subscription_discount( absint( $_POST['pms_current_subscription'] ), absint( $subscription_plan->id ) );

        if( $prorated_discount_data === false )
            return $price;

        if( $prorated_discount_data['type'] == 'free_period' )
            return 0;
        else
            return $this->process_amount( $price - $prorated_discount_data['prorate_discount'] );

    }

    public function modify_input_data_attributes_subscription_plan_price( $subscription_plan_input_data_arr, $new_subscription_plan_id, $form_location ){

        if( !in_array( $form_location, array( 'upgrade_subscription', 'downgrade_subscription' ) ) )
            return $subscription_plan_input_data_arr;

        if( !isset( $_GET['subscription_id'] ) )
            return $subscription_plan_input_data_arr;

        $prorated_discount_data = $this->get_prorated_subscription_discount( absint( $_GET['subscription_id'] ), $new_subscription_plan_id );

        if( $prorated_discount_data != false ){

            if( $prorated_discount_data['type'] == 'free_period' ){
                $subscription_plan_input_data_arr['original_price']    = $subscription_plan_input_data_arr['price'];
                $subscription_plan_input_data_arr['price']             = 0;
                $subscription_plan_input_data_arr['prorated_discount'] = $prorated_discount_data['prorate_discount'];
            } else {
                $subscription_plan_input_data_arr['price']             = $this->process_amount( $subscription_plan_input_data_arr['price'] - $prorated_discount_data['prorate_discount'] );
                $subscription_plan_input_data_arr['prorated_discount'] = $prorated_discount_data['prorate_discount'];
            }
            
        }

        return $subscription_plan_input_data_arr;

    }

    public function get_stripe_intents_prorated_amount( $amount, $new_subscription_plan_id ){

        if( !isset( $_POST['form_type'] ) || $_POST['form_type'] != 'pms_change_subscription' || !isset( $_POST['pms_current_subscription'] ) )
            return $amount;

        $subscription = pms_get_member_subscription( absint( $_POST['pms_current_subscription'] ) );

        $current_context = pms_get_change_subscription_plan_context( $subscription->subscription_plan_id, $new_subscription_plan_id );

        if( $current_context == 'change' )
            return $amount;

        $prorated_discount_data = $this->get_prorated_subscription_discount( $subscription->id, $new_subscription_plan_id );
        
        if( $prorated_discount_data === false )
            return $amount;

        if( $prorated_discount_data['type'] == 'free_period' )
            return 0;
        else
            return $this->process_amount( $amount - $prorated_discount_data['prorate_discount'] );

    }

    public function mark_payment_as_prorated( $subscription_id, $data, $old_data ){

        if( empty( $data['subscription_plan_id'] ) )
            return;

        if( !isset( $data['status'] ) || !isset( $old_data['status'] ) || $data['status'] != 'active' || $old_data['status'] != $data['status'] )
            return;
        
        $payment_id = pms_get_member_subscription_meta( $subscription_id, 'pms_payment_prorated_' . $data['subscription_plan_id'], true );

        if( empty( $payment_id ) )
            return;

        $payment = pms_get_payment( (int)$payment_id );

        if( !empty( $payment->id ) && $payment->status == 'completed' ){
            pms_add_payment_meta( $payment->id, 'pms_payment_prorated', 1 );
            pms_delete_member_subscription_meta( $subscription_id, 'pms_payment_prorated_' . $data['subscription_plan_id'] );
        }

    }

    public function maybe_enable_checkout_trial( $has_trial, $user_data, $subscription_plan, $form_location, $pay_gate, $is_recurring ){

        if( !$is_recurring )
            return $has_trial;

        if( !isset( $_POST['pms_current_subscription'] ) || !in_array( $pay_gate, array( 'paypal_standard', 'paypal_express') )  || !in_array( $form_location, array( 'upgrade_subscription', 'downgrade_subscription' ) ) )
            return $has_trial;

        $prorated_discount_data = $this->get_prorated_subscription_discount( absint( $_POST['pms_current_subscription'] ), $subscription_plan->id );

        if( $prorated_discount_data === false )
            return $has_trial;
        
        if( $prorated_discount_data['type'] == 'free_period' )
            $has_trial = true;

        return $has_trial;

    }

    public function apply_prorate_discount_to_paypal_standard_arguments( $args, $gateway_object, $settings ){

        if( !isset( $_POST['pms_current_subscription'] ) || !isset( $gateway_object->form_location ) || !in_array( $gateway_object->form_location, array( 'upgrade_subscription', 'downgrade_subscription' ) ) || !isset( $gateway_object->subscription_plan ) )
            return $args;

        $prorated_discount_data = $this->get_prorated_subscription_discount( absint( $_POST['pms_current_subscription'] ), $gateway_object->subscription_plan->id );

        if( $prorated_discount_data === false )
            return $args;

        if( $prorated_discount_data['type'] == 'free_period' ){

            $subscription_plan = pms_get_subscription_plan( $gateway_object->subscription_plan->id );

            if( !$subscription_plan->is_fixed_period_membership() ){

                // @TODO: Needs some checking and converting because of the API limitations for a maximum of 90 D, x W, x M
                // In case sign-up fees are applied, empty them
                if( isset( $args['a1'] ) ){
                    $args['a1'] = 0;
                    $args['p1'] = $prorated_discount_data['remaining_discount_extra_period_days'] + $prorated_discount_data['new_plan_period_length_in_days'];
                    $args['t1'] = 'D';
                } elseif( isset( $args['p2'] ) ){
                    $args['p2'] = $prorated_discount_data['remaining_discount_extra_period_days'] + $prorated_discount_data['new_plan_period_length_in_days'];
                    $args['t2'] = 'D';
                }

            }

        }

        return $args;

    }

    public function apply_prorate_discount_to_paypal_express_arguments( $args, $checkout_data ){

        if( !isset( $_POST['pms_current_subscription'] ) || empty( $checkout_data['form_location'] ) || !in_array( $checkout_data['form_location'], array( 'upgrade_subscription', 'downgrade_subscription' ) ) )
            return $args;

        if( empty( $checkout_data['payment_id'] ) )
            return $args;

        $payment = pms_get_payment( absint( $checkout_data['payment_id'] ) );

        if( empty( $payment ) || empty( $payment->subscription_id ) )
            return $args;

        $prorated_discount_data = $this->get_prorated_subscription_discount( absint( $_POST['pms_current_subscription'] ), $payment->subscription_id );

        if( $prorated_discount_data === false )
            return $args;

        if( $prorated_discount_data['type'] == 'free_period' ){

        } else {

            if( isset( $args['PAYMENTREQUEST_0_AMT'] ) && !empty( $prorated_discount_data['prorate_discount'] ) && !empty( $payment->amount ) )
                $args['PAYMENTREQUEST_0_AMT'] = $payment->amount;

            if( isset( $args['L_BILLINGTYPE0'] ) && $args['L_BILLINGTYPE0'] == 'RecurringPayments' && isset( $args['L_PAYMENTREQUEST_0_DESC1'] ) )
                $args['L_PAYMENTREQUEST_0_DESC1'] = $args['L_PAYMENTREQUEST_0_DESC1'] . ' code + prorate discount';

        }

        return $args;

    }

    public function apply_prorate_discount_to_paypal_express_confirmation_arguments( $args, $payment_data ){

        if( empty( $payment_data['payment_id'] ) || empty( $payment_data['form_location'] ) || !in_array( $payment_data['form_location'], array( 'upgrade_subscription', 'downgrade_subscription' ) ) )
            return $args;

        $payment = pms_get_payment( absint( $payment_data['payment_id'] ) );

        if( empty( $payment->id ) || empty( $payment->member_subscription_id ) || empty( $payment->subscription_id ) )
            return $args;

        $subscription_plan = pms_get_subscription_plan( $payment->subscription_id );

        if( empty( $subscription_plan->id ) )
            return $args;
        
        $prorated_discount_data = $this->get_prorated_subscription_discount( $payment->member_subscription_id, $subscription_plan->id );

        if( $prorated_discount_data === false )
            return $args;

        if( $prorated_discount_data['type'] == 'free_period' ){

            if( !$subscription_plan->is_fixed_period_membership() ){
                $profile_start_date = new DateTime();
                $profile_start_date->add( date_interval_create_from_date_string( $prorated_discount_data['remaining_discount_extra_period_days'] + $prorated_discount_data['new_plan_period_length_in_days'] . ' days' ) );

                $args['PROFILESTARTDATE'] = $profile_start_date->format( 'Y-m-d\Tg:i:s' );
            }

            if( isset( $args['INITAMT'] ) )
                $args['INITAMT'] = 0;

        } else {

            // For PP Express Recurring, the checkout sets INITAMT = AMT by default and in our case this is set 
            // to the pro-rated price which needs to be updated for future payments
            $recurring_price = $subscription_plan->price;

            if( !empty( $payment->discount_code ) && function_exists( 'pms_in_get_discount_by_code' ) )
                $recurring_price = pms_in_calculate_discounted_amount( $recurring_price, pms_in_get_discount_by_code( $payment->discount_code ) );

            $tax_rate = pms_get_payment_meta( $payment->id, 'pms_tax_rate', true );

            if( !empty( $tax_rate ) )
                $recurring_price = $recurring_price + ( $recurring_price * ( $tax_rate / 100 ) );

            if( isset( $args['AMT'] ) && isset( $args['INITAMT'] ) && $args['AMT'] == $args['INITAMT'] )
                $args['AMT'] = $recurring_price;

        }

        return $args;

    }

    public function disable_paypal_express_checkout_used_trial_verification( $enabled, $subscription_plan, $payment, $form_location ){

        if( empty( $form_location ) || !in_array( $form_location, array( 'upgrade_subscription', 'downgrade_subscription' ) ) || empty( $subscription_plan ) || empty( $payment->member_subscription_id ) )
            return $enabled;

        $current_subscription = pms_get_member_subscription( $payment->member_subscription_id );

        if( empty( $current_subscription->id ) )
            return $enabled;

        $prorated_discount_data = $this->get_prorated_subscription_discount( $current_subscription->id, $subscription_plan->id );

        if( $prorated_discount_data === false )
            return $enabled;

        if( $prorated_discount_data['type'] == 'free_period' )
            $enabled = false;

        return $enabled;

    }

    public function modify_paypal_subscription_change_expiration_date( $expiration_date, $current_subscription, $new_subscription_plan_id, $post_data ){

        if( empty( $current_subscription->id ) || empty( $new_subscription_plan_id ) )
            return $expiration_date;

        $current_context = pms_get_change_subscription_plan_context( $current_subscription->subscription_plan_id, $new_subscription_plan_id );

        if( $current_context == 'change' )
            return $expiration_date;

        $prorated_discount_data = $this->get_prorated_subscription_discount( $current_subscription->id, $new_subscription_plan_id );
        
        if( $prorated_discount_data === false || !isset( $prorated_discount_data['remaining_discount_extra_period_days'] ) )
            return $expiration_date;

        $subscription_plan = pms_get_subscription_plan( $new_subscription_plan_id );

        pms_add_member_subscription_log( $current_subscription->id, 'subscription_prorated', array( 'free_days' => $prorated_discount_data['remaining_discount_extra_period_days'] + $prorated_discount_data['new_plan_period_length_in_days'], 'fixed_period' => $subscription_plan->is_fixed_period_membership() ) );
 
        // PayPal Standard
        if( isset( $post_data['subscr_date'] ) ){
            
            // We use the subscr_date from paypal to calculate the free time
            $expiration_date = new DateTime( $post_data['subscr_date'] );
            $expiration_date->add( date_interval_create_from_date_string( $prorated_discount_data['remaining_discount_extra_period_days'] + $prorated_discount_data['new_plan_period_length_in_days'] . ' days' ) );

            return $expiration_date->format( 'Y-m-d H:i:s' );

        // PayPal Express
        } else if( isset( $post_data['next_payment_date'] ) ){

            $expiration_date = new DateTime( $post_data['next_payment_date'] );

            return $expiration_date->format( 'Y-m-d H:i:s' );

        }

        return $expiration_date;

    }

    public function enable_trial_for_paypal_express_confirmation_form( $has_trial, $payment, $subscription_plan, $payment_data ){

        if( empty( $payment->id ) || empty( $payment->member_subscription_id ) || empty( $subscription_plan->id ) || empty( $payment_data['form_location'] ) || !in_array( $payment_data['form_location'], array( 'upgrade_subscription', 'downgrade_subscription' ) ) )
            return $has_trial;

        $prorated_discount_data = $this->get_prorated_subscription_discount( $payment->member_subscription_id, $subscription_plan->id );

        if( $prorated_discount_data === false )
            return $has_trial;

        if( $prorated_discount_data['type'] == 'free_period' )
            $has_trial = true;

        return $has_trial;

    }

    public function change_paypal_express_confirmation_form_trial_duration( $trial_duration, $payment, $subscription_plan, $payment_data ){

        if( empty( $payment->id ) || empty( $payment->member_subscription_id ) || empty( $subscription_plan->id ) || empty( $payment_data['form_location'] ) || !in_array( $payment_data['form_location'], array( 'upgrade_subscription', 'downgrade_subscription' ) ) )
            return $trial_duration;

        $prorated_discount_data = $this->get_prorated_subscription_discount( $payment->member_subscription_id, $subscription_plan->id );

        if( $prorated_discount_data === false )
            return $trial_duration;

        if( $prorated_discount_data['type'] == 'free_period' )
            $trial_duration = $prorated_discount_data['remaining_discount_extra_period_days'] + $prorated_discount_data['new_plan_period_length_in_days'];

        return $trial_duration;

    }

    public function change_paypal_express_confirmation_form_trial_duration_unit( $trial_duration_unit, $payment, $subscription_plan, $payment_data ){

        if( empty( $payment->id ) || empty( $payment->member_subscription_id ) || empty( $subscription_plan->id ) || empty( $payment_data['form_location'] ) || !in_array( $payment_data['form_location'], array( 'upgrade_subscription', 'downgrade_subscription' ) ) )
            return $trial_duration_unit;

        $prorated_discount_data = $this->get_prorated_subscription_discount( $payment->member_subscription_id, $subscription_plan->id );

        if( $prorated_discount_data === false )
            return $trial_duration_unit;

        if( $prorated_discount_data['type'] == 'free_period' )
            $trial_duration_unit = 'day';

        return $trial_duration_unit;

    }

    public function change_paypal_express_confirmation_form_third_payment( $third_payment, $pms_checkout_details ){

        if( !isset( $pms_checkout_details['BILLINGAGREEMENTACCEPTEDSTATUS'] ) || $pms_checkout_details['BILLINGAGREEMENTACCEPTEDSTATUS'] != 1 )
            return $third_payment;

        if( empty( $pms_checkout_details['payment_data']['payment_id'] ) || empty( $pms_checkout_details['payment_data']['form_location'] ) || !in_array( $pms_checkout_details['payment_data']['form_location'], array( 'upgrade_subscription', 'downgrade_subscription' ) ) )
            return $third_payment;

        $payment = pms_get_payment( absint( $pms_checkout_details['payment_data']['payment_id'] ) );

        if( empty( $payment->id ) || empty( $payment->member_subscription_id ) || empty( $payment->subscription_id ) )
            return $third_payment;

        $subscription_plan = pms_get_subscription_plan( $payment->subscription_id );

        if( empty( $subscription_plan->id ) )
            return $third_payment;
        
        $prorated_discount_data = $this->get_prorated_subscription_discount( $payment->member_subscription_id, $subscription_plan->id );

        if( $prorated_discount_data === false )
            return $third_payment;

        // @TODO: applies when it shouldn't. ONLY FOR RECURRING????
        if( $prorated_discount_data['type'] != 'free_period' ){

            $third_payment = $subscription_plan->price;

            if( !empty( $payment->discount_code ) && function_exists( 'pms_in_get_discount_by_code' ) ){
                $discount_code = pms_in_get_discount_by_code( $payment->discount_code );

                if( !empty( $discount_code->recurring_payments ) )
                    $third_payment = pms_in_calculate_discounted_amount( $third_payment, $discount_code );
            }

            $tax_rate = pms_get_payment_meta( $payment->id, 'pms_tax_rate', true );

            if( !empty( $tax_rate ) )
                $third_payment = $third_payment + ( $third_payment * ( $tax_rate / 100 ) );

        }

        return $third_payment;

    }

    public function maybe_allow_checkout_without_gateway_with_non_free_plans( $allow, $subscription_plan, $form_location ){

        if( !isset( $_POST['pms_current_subscription'] ) || empty( $form_location ) || !in_array( $form_location, array( 'upgrade_subscription', 'downgrade_subscription' ) ) || empty( $subscription_plan->id ) )
            return $allow;

        $prorated_discount_data = $this->get_prorated_subscription_discount( absint( $_POST['pms_current_subscription'] ), $subscription_plan->id );

        if( $prorated_discount_data === false )
            return $allow;

        if( $prorated_discount_data['type'] == 'free_period' )
            $allow = true;

        return $allow;

    }

    public function add_subscription_logs_messages( $message, $log ){

        if( empty( $log['type'] ) )
            return $message;

        switch ( $log['type'] ) {
            case 'subscription_prorated':
                if ( $log['data']['fixed_period'] == true )
                    $message = __( 'Pro-ration is enabled for this purchase. The user will receive the first fixed period form the subscription for free.', 'paid-member-subscriptions' );
                else
                    $message = sprintf( __( 'Pro-ration is enabled for this purchase. The user will receive %s days for free, based on his remaining subscription time.', 'paid-member-subscriptions' ), $log['data']['free_days'] );

                break;
        }

        return $message;

    }

    public function log_prorate_discounts_on_payments( $payment_gateway_data, $payment_settings ){

        if( !isset( $_REQUEST['subscription_id'] ) || !isset( $payment_gateway_data['subscription_plan_id'] ) || !isset( $payment_gateway_data['form_location'] ) )
            return $payment_gateway_data;

        if( !in_array( $payment_gateway_data['form_location'], array( 'upgrade_subscription', 'downgrade_subscription' ) ) || empty( $payment_gateway_data['payment_id'] ) )
            return $payment_gateway_data;

        $prorated_discount_data = $this->get_prorated_subscription_discount( absint( $_REQUEST['subscription_id'] ), $payment_gateway_data['subscription_plan_id'] );

        if( $prorated_discount_data === false )
            return $payment_gateway_data;

        if( $prorated_discount_data['type'] != 'free_period' ){

            $payment = pms_get_payment( $payment_gateway_data['payment_id'] );
            $payment->log_data( 'pms_payment_prorated', array( 'discount_applied' => $prorated_discount_data['prorate_discount'] ) );

        }

        return $payment_gateway_data;

    }

    public function payments_add_custom_log_system_error_messages( $message, $log ){

        if ( empty( $log['type'] ) )
            return $message;

        $kses_args = array(
            'strong' => array()
        );

        switch( $log['type'] ) {
            case 'pms_payment_prorated':
                $message = sprintf( __( 'A discount of %s was applied to this payment from the pro-rate functionality.', 'paid-member-subscriptions' ), pms_format_price( $log['data']['discount_applied'] ) );
                break;
        }

        return wp_kses( $message, $kses_args );

    }

    public function get_prorated_subscription_discount( $current_subscription_id, $new_subscription_plan_id, $tag_subscription = false ){
        
        // don't apply to pay what you want plans
        if( function_exists( 'pms_in_pwyw_pricing_enabled' ) && pms_in_pwyw_pricing_enabled( $new_subscription_plan_id ) )
            return false;

        $current_subscription = pms_get_member_subscription( $current_subscription_id );

        // Take into account only Active and Canceled subscriptions that aren't past the expiration date
        if( !in_array( $current_subscription->status, array( 'active', 'canceled' ) ) )
            return false;

        // If empty expiration date, try to use the billing date if available
        $expiration_date = $current_subscription->expiration_date;

        if( empty( $expiration_date ) )
            $expiration_date = $current_subscription->billing_next_payment;

        $expiration_date = new DateTime( $expiration_date );
        $now             = new DateTime();

        if( $now > $expiration_date )
            return false;

        $current_subscription_plan = pms_get_subscription_plan( $current_subscription->subscription_plan_id );

        $new_subscription_plan = pms_get_subscription_plan( $new_subscription_plan_id );

        // if new plan is free, don't do anything
        if( $new_subscription_plan->price == '0' )
            return false;

        // Determine the amount the user paid for the current subscription
        $payments = pms_get_payments( array( 'user_id' => $current_subscription->user_id, 'subscription_plan_id' => $current_subscription_plan->id, 'status' => 'completed', 'number' => 1 ) );

        if( !empty( $payments[0] ) ){
            $payment            = $payments[0];
            $initial_paid_price = $payment->amount;
        }

        // There will be some cases where we won't apply this if we can't calculate it accurately
        if( empty( $payment ) || empty( $initial_paid_price ) || $payment->status != 'completed' )
            return false;

        // Check if this payment was already prorated
        $already_prorated = pms_get_payment_meta( $payment->id, 'pms_payment_prorated', true );

        if( !empty( $already_prorated ) && $already_prorated == 1 )
            return false;
            
        $start_date   = new DateTime( date( 'Y-m-d', strtotime( $payment->date ) ) );
        $current_date = new DateTime( date( 'Y-m-d' ) );

        $days_passed = $current_date->diff( $start_date )->format( '%a' );

        $plan_length_in_days     = $this->get_subscription_plan_length_in_days( $current_subscription_plan );
        $new_plan_length_in_days = $this->get_subscription_plan_length_in_days( $new_subscription_plan );

        // If the action is made by the user in the same day, the prorate discount is equal to the previous price he paid
        if( $days_passed == '0' ){
            $prorate_discount = $initial_paid_price;
        } else {
            
            // @TODO: Unlimited need to be handled also, figure that out
            $price_per_day = $initial_paid_price / $plan_length_in_days;

            $prorate_discount = $initial_paid_price - ( $price_per_day * $days_passed );

        }

        $data = [
            'type'                             => $prorate_discount >= $new_subscription_plan->price ? 'free_period' : 'discounted_period',
            'prorate_discount'                 => $prorate_discount,
            'initial_paid_price'               => $initial_paid_price,
            'current_subscription_days_passed' => $days_passed,
            'new_plan_period_length_in_days'   => $new_plan_length_in_days
        ];

        if( $data['type'] == 'free_period' && $prorate_discount > $new_subscription_plan->price && $new_plan_length_in_days > 0 ){

            $price_per_day        = $new_subscription_plan->price / $new_plan_length_in_days;
            $discount_amount_left = $prorate_discount - $new_subscription_plan->price;

            $data['remaining_discount_extra_period_days'] = round( $discount_amount_left / $price_per_day );

        }

        if( $tag_subscription )
            pms_add_member_subscription_meta( $current_subscription_id, 'pms_payment_prorated_' . $new_subscription_plan_id, $payment->id );

        return $data;

    }

    public function get_subscription_plan_length_in_days( $subscription_plan ){

        if( !isset( $subscription_plan->id ) )
            return false;

        if( !$subscription_plan->is_fixed_period_membership() && $subscription_plan->duration_unit == 'day' )
            return $subscription_plan->duration;

        // generate dates based on duration and get the difference to the current date
        $plan_expiration_date = new DateTime( date( 'Y-m-d', strtotime( $subscription_plan->get_expiration_date() ) ) );
        $current_date         = new DateTime( date( 'Y-m-d' ) );

        return $plan_expiration_date->diff( $current_date )->format( '%a' );

    }

    public function get_last_completed_payment_for_subscription( $current_subscription_id ){

        $subscription_payments = pms_get_payments_by_subscription_id( $current_subscription_id );

        $target = '';

        if( !empty( $subscription_payments ) ){
            foreach( $subscription_payments as $item ){

                $payment = pms_get_payment( $item['payment_id'] );

                if( $payment->status == 'completed' && $payment->amount != 0 ){
                    $target = $payment->id;
                    break;
                }

            }
        }

        return $target;

    }

    /**
     * Stripe has a minimum charge amount for the settlement currency
     * When the currency is not supported by Stripe as a settlement currency, the paid amount needs to be at least the equivalent of 0.5$
     */
    public function process_amount( $amount ){

        $currency = pms_get_active_currency();
        
        // We'll keep track of the settlement currencies and make sure that at least the minimum amount for these currencies is satisfied
        $settlement_currencies = array(
            'USD' => 0.5,
            'AED' => 2,
            'AUD' => 0.5,
            'BGN' => 1,
            'BRL' => 0.5,
            'CAD' => 0.5,
            'CHF' => 0.5,
            'CZK' => 15,
            'DKK' => 2.5,
            'EUR' => 0.5,
            'GBP' => 0.3,
            'HKD' => 4,
            'HUF' => 175,
            'INR' => 0.5,
            'JPY' => 50,
            'MXN' => 10,
            'MYR' => 2,
            'NOK' => 3,
            'NZD' => 0.5,
            'PLN' => 2,
            'RON' => 2,
            'SEK' => 3,
            'SGD' => 0.5,
        );
        
        if( isset( $settlement_currencies[ $currency ] ) && $amount < $settlement_currencies[ $currency ] )
            $amount = $settlement_currencies[ $currency ];

        return round( $amount, 2 );

    }

}

global $pms_prorate;
$pms_prorate = new PMS_IN_ProRate;