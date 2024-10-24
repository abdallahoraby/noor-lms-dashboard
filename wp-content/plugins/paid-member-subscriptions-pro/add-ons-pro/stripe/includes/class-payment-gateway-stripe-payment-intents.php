<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Account;
use Stripe\PaymentMethod;
use Stripe\PaymentIntent;

Class PMS_IN_Payment_Gateway_Stripe_Payment_Intents extends PMS_IN_Payment_Gateway_Stripe_Legacy {

    /**
     * The features supported by the payment gateway
     *
     * @access public
     * @var array
     *
     */
    public $supports;

    private $customer_id = '';

    /**
     * Initialisation
     *
     */
    public function init() {

        parent::init();

        $this->supports = array(
            'plugin_scheduled_payments',
            'recurring_payments',
            'subscription_sign_up_fee',
            'subscription_free_trial',
            'change_subscription_payment_method_admin',
            'update_payment_method',
        );

        // don't add any hooks if the gateway is not active
        if( !in_array( 'stripe_intents', pms_get_active_payment_gateways() ) )
            return;

        // Add the needed sections for the checkout forms
        add_filter( 'pms_extra_form_sections', array( __CLASS__, 'register_form_sections' ), 25, 2 );

        // Add the needed form fields for the checkout forms
        add_filter( 'pms_extra_form_fields',   array( __CLASS__, 'register_form_fields' ), 25, 2 );

        // Set Stripe token obtained with Stripe JS
        $this->stripe_token = ( !empty( $_POST['stripe_token'] ) ? sanitize_text_field( $_POST['stripe_token'] ) : '' );

        // Set API secret key
        $api_credentials  = pms_in_get_stripe_api_credentials();
        $this->secret_key = ( !empty( $api_credentials['secret_key'] ) ? $api_credentials['secret_key'] : '' );

        // Set discount
        if( !empty( $_POST['discount_code'] ) )
            $this->discount = pms_in_get_discount_by_code( sanitize_text_field( $_POST['discount_code'] ) );

        if( empty( $this->payment_id ) && isset( $_POST['payment_id'] ) )
            $this->payment_id = (int)$_POST['payment_id'];

        if( empty( $this->form_location ) && isset( $_POST['form_location'] ) )
            $this->form_location = sanitize_text_field( $_POST['form_location'] );

        add_filter( 'pms_payment_logs_system_error_messages', array( $this, 'payment_logs_system_error_messages' ), 10, 2 );

        if( !pms_in_stripe_check_filter_from_class_exists( 'pms_get_output_payment_gateways', get_class($this), 'field_ajax_nonces' ) )
            add_filter( 'pms_get_output_payment_gateways',        array( $this, 'field_ajax_nonces' ), 10, 2 );

        add_filter( 'pms_member_account_not_logged_in',             array( $this, 'authentication_page' ) );
        add_filter( 'pms_account_shortcode_content',                array( $this, 'authentication_page' ) );

        // Profile Builder
        add_filter( 'wppb_edit_profile_user_not_logged_in_message', array( $this, 'authentication_page' ) );
        add_filter( 'wppb_edit_profile_form_content',               array( $this, 'authentication_page' ) );


        // In case of a failed payment, replace the default Profile Builder success message
        add_action( 'wppb_save_form_field',          array( $this, 'wppb_success_message_wrappers' ) );

        // Don't let users use the same card for multiple trials using the same subscription plan
        add_action( 'pms_checkout_has_trial', array( $this, 'disable_trial_if_duplicate_card' ) );

        // add Stripe publishable keys into the form
        if( !pms_in_stripe_check_filter_from_class_exists( 'pms_get_output_payment_gateways', get_class($this), 'field_publishable_key' ) )
            add_filter( 'pms_get_output_payment_gateways', array( $this, 'field_publishable_key' ), 10, 2 );

        // Add PK to Update Payment method form
        if( !pms_in_stripe_check_filter_from_class_exists( 'pms_update_payment_method_form_bottom', get_class($this), 'update_payment_form_field_publishable_key' ) )
            add_action( 'pms_update_payment_method_form_bottom', array( $this, 'update_payment_form_field_publishable_key' ) );

        // Add Update Payment Method ajax request nonce to form
        if( !pms_in_stripe_check_filter_from_class_exists( 'pms_update_payment_method_form_bottom', get_class($this), 'field_update_payment_method_nonce' ) )
            add_action( 'pms_update_payment_method_form_bottom', array( $this, 'field_update_payment_method_nonce' ), 20 );

        // Process Update Payment Method request
        if( !pms_in_stripe_check_filter_from_class_exists( 'pms_update_payment_method_stripe_intents', get_class($this), 'update_customer_payment_method' ) )
            add_action( 'pms_update_payment_method_stripe_intents', array( $this, 'update_customer_payment_method' ) );

    }


    /**
     * Register the Credit Card and Billing Details sections
     *
     * @param array  $sections
     * @param string $form_location
     *
     */
    public static function register_form_sections( $sections = array(), $form_location = '' ) {

        if( ! in_array( $form_location, array( 'register', 'new_subscription', 'upgrade_subscription', 'renew_subscription', 'retry_payment', 'change_subscription', 'update_payment_method_stripe_intents' ) ) )
            return $sections;

        // Add the credit card details if it does not exist
        if( empty( $sections['credit_card_information'] ) ) {

            $sections['credit_card_information'] = array(
                'name'    => 'credit_card_information',
                'element' => 'ul',
                'id'      => 'pms-credit-card-information',
                'class'   => 'pms-credit-card-information pms-section-credit-card-information'
            );

        }

        return $sections;

    }


    /**
     * Register the Credit Card and Billing Fields to the checkout forms
     *
     * @param array $fields
     *
     * @return array
     *
     */
    public static function register_form_fields( $fields = array(), $form_location = '' ) {

        if( ! in_array( $form_location, array( 'register', 'new_subscription', 'upgrade_subscription', 'renew_subscription', 'retry_payment', 'change_subscription', 'update_payment_method_stripe_intents' ) ) )
            return $fields;


        /**
         * Add the Credit Card fields
         *
         */
        $fields['pms_credit_card_heading'] = array(
            'section'         => 'credit_card_information',
            'type'            => 'heading',
            'default'         => '<h4>' . __( 'Credit / Debit Card Information', 'paid-member-subscriptions' ) . '</h4>',
            'element_wrapper' => 'li',
        );

        $fields['pms_credit_card_wrapper'] = array(
            'section' => 'credit_card_information',
            'type'    => 'empty',
            'id'      => 'pms-stripe-credit-card-details'
        );

        return $fields;

    }

    public function confirm_payment_intent() {
        if( empty( $this->secret_key ) )
            return false;

        if( empty( $this->stripe_token ) )
            return false;

        // Set API key
        Stripe::setApiKey( $this->secret_key );
        Stripe::setApiVersion( '2019-08-14');

        $payment = pms_get_payment( $this->payment_id );

        try {

            $payment->log_data( 'stripe_intent_attempted_confirmation' );

            $intent = \Stripe\PaymentIntent::retrieve( $this->stripe_token );
            $intent->confirm();

            if( $intent->status == 'succeeded' ){
                $payment->log_data( 'stripe_intent_confirmed' );
                $payment->update( array( 'transaction_id' => $intent->id, 'status' => 'completed' ) );

                pms_delete_payment_meta( $this->payment_id, 'authentication' );

                $this->handle_subscription();
            }

        } catch( Exception $e ) {

            //log errors
            $this->log_error_data( $e );

            //fail payment
            $payment->update( array( 'status' => 'failed' ) );

            //redirect user to error page
            $this->error_redirect();
        }

        $this->payment_response( $intent );
    }

    /**
     * Create the customer and save the customer's card id in Stripe and also save their ids as metadata
     * for the provided subscription as the payment method metadata needed for future payments
     *
     * @param int $member_subscription_id
     *
     * @return bool
     *
     */
    public function register_automatic_billing_info( $member_subscription_id = 0 ) {

        if( empty( $this->secret_key ) )
            return false;

        if( empty( $member_subscription_id ) )
            return false;

        // Set API key
        Stripe::setApiKey( $this->secret_key );
        Stripe::setApiVersion( '2019-08-14');

        // Verify API key
        try {

            Account::retrieve();

        } catch( Exception $e ) {

            return false;

        }


        /**
         * Grab the Stripe customer, if it doesn't exist create it and return it
         *
         */
        if( false === ( $customer = apply_filters( 'pms_stripe_get_customer', $this->get_customer( $this->user_id ), $this->user_id ) ) )
            $customer = $this->create_customer();

        $this->customer_id = $customer->id;

        /**
         * Save the customers id and payment method for future uses
         *
         */
        if( !empty( $this->customer_id ) && !empty( $this->stripe_token ) ) {

            try {
                //if we receive a Setup Intent ID an error happened, log it
                if( strpos( $this->stripe_token, 'seti_' ) !== false ){

                    //retrieve error from setup intent
                    $setup_intent = \Stripe\SetupIntent::retrieve( $this->stripe_token );

                    if( !empty( $setup_intent['last_setup_error'] ) ) {
                        $data       = array();
                        $error      = $setup_intent['last_setup_error'];

                        $data['data'] = array(
                            'code'              => !empty( $error['code'] ) ? $error['code'] : '',
                            'message'           => !empty( $error['message'] ) ? $error['message'] : '',
                            'doc_url'           => !empty( $error['doc_url'] ) ? $error['doc_url'] : '',
                            'payment_intent_id' => $this->stripe_token,
                        );

                        $error_code = !empty( $error['decline_code'] ) ? $error['decline_code'] : '';

                        $data['message'] = !empty( $error['message'] ) ? $error['message'] : '';
                        $data['desc']    = 'stripe response';

                        $payment = pms_get_payment( $this->payment_id );

                        $payment->log_data( 'payment_failed', $data, $error_code );
                        $payment->update( array( 'status' => 'failed' ) );

                        return false;
                    }
                }

                $payment_method = PaymentMethod::retrieve( $this->stripe_token );

                //check if this payment method is already attached to the customer, if not, add it
                $customer_payment_methods = \Stripe\PaymentMethod::all( array( 'customer' => $this->customer_id, 'type' => 'card' ) );
                $customer                 = \Stripe\Customer::retrieve( $this->customer_id );
                $attach_card              = true;

                if( !empty( $customer_payment_methods['data'] ) ){
                    foreach( $customer_payment_methods['data'] as $existing_payment_method ){
                        if( $payment_method->card->fingerprint == $existing_payment_method->card->fingerprint ){
                            $attach_card = false;
                            break;
                        }
                    }
                }

                // Add the card if the Customer does not have it already or does not have a Default Payment Method
                if( $attach_card || empty( $customer->invoice_settings->default_payment_method ) ) {

                    $payment_method->attach( ['customer' => $this->customer_id ] );

                    \Stripe\Customer::update(
                        $this->customer_id,
                        array(
                            'invoice_settings' => array(
                                'default_payment_method' => $this->stripe_token,
                            )
                        )
                    );

                } else {

                    // If we don't add the new card, make sure we use and save the existing one
                    if( !isset( $_POST['stripe_ajax_payment_intent_nonce'] ) )
                        $this->stripe_token = $customer->invoice_settings->default_payment_method;

                }

                // If subscription had a trial, save card fingerprint
                $member_subscription = pms_get_member_subscription( $member_subscription_id );

                if( !empty( $member_subscription->subscription_plan_id ) ){

                    $subscription_plan = pms_get_subscription_plan( $member_subscription->subscription_plan_id );

                    // if there's a signup fee, we attempt a charge right now, if that charge fails, we don't need to save the fingerprint so try to do this later
                    if( !empty( $subscription_plan->trial_duration ) && empty( $subscription_plan->sign_up_fee ) ){

                        $plan_fingerprints = get_option( 'pms_used_trial_cards_' . $subscription_plan->id, false );

                        if( $plan_fingerprints == false )
                            $plan_fingerprints = array( $payment_method->card->fingerprint );
                        else
                            $plan_fingerprints[] = $payment_method->card->fingerprint;

                        update_option( 'pms_used_trial_cards_' . $subscription_plan->id, $plan_fingerprints, false );

                    }

                }

                if( !empty( $payment_method ) ){

                    if( !empty( $payment_method->card ) ){

                        if( !empty( $payment_method->card->last4 ) )
                            pms_update_member_subscription_meta( $member_subscription_id, 'pms_payment_method_number', $payment_method->card->last4 );

                        if( !empty( $payment_method->card->brand ) )
                            pms_update_member_subscription_meta( $member_subscription_id, 'pms_payment_method_type', $payment_method->card->brand );

                        if( !empty( $payment_method->card->exp_month ) )
                            pms_update_member_subscription_meta( $member_subscription_id, 'pms_payment_method_expiration_month', $payment_method->card->exp_month );

                        if( !empty( $payment_method->card->exp_year ) )
                            pms_update_member_subscription_meta( $member_subscription_id, 'pms_payment_method_expiration_year', $payment_method->card->exp_year );
                    }

                }

            } catch( Exception $e ) {

                $this->log_error_data( $e );

                $payment = pms_get_payment( $this->payment_id );
                $payment->update( array( 'status' => 'failed' ) );

                return false;

            }

            // Save the customer and card to the subscription
            if( function_exists( 'pms_update_member_subscription_meta' ) ) {

                pms_update_member_subscription_meta( $member_subscription_id, '_stripe_customer_id', $this->customer_id );
                pms_update_member_subscription_meta( $member_subscription_id, '_stripe_card_id', $this->stripe_token );

            }

            return true;

        }

        return false;

    }

    public function process_payment( $payment_id = 0, $subscription_id = 0 ) {

        if( empty( $this->secret_key ) )
            return false;

        if( $payment_id != 0 )
            $this->payment_id = $payment_id;

        $payment = pms_get_payment( $this->payment_id );

        // Set subscription plan
        if( empty( $this->subscription_plan ) )
            $this->subscription_plan = pms_get_subscription_plan( $payment->subscription_id );

        $form_location = PMS_Form_Handler::get_request_form_location();

        // If a payment intent id is coming from front-end, a successful payment should've already happened
        if( isset( $_POST['payment_intent'] ) ){

            // Need to update the payment and add relevant info
            $payment->log_data( 'stripe_intent_created' );

            $intent = PaymentIntent::retrieve( sanitize_text_field( $_POST['payment_intent'] ) );

            // Add transaction ID to payment
            $payment->update( array( 'transaction_id' => $intent->id ) );

            // Set PaymentMethod as default
            if( !empty( $intent->customer ) ){

                \Stripe\Customer::update(
                    $intent->customer,
                    array(
                        'invoice_settings' => array( 'default_payment_method' => $intent->payment_method )
                    )
                );

            }

            if( !empty( $intent->status ) && $intent->status == 'succeeded' ){

                // Complete Payment
                $payment->log_data( 'stripe_intent_confirmed' );
                $payment->update( array( 'status' => 'completed' ) );

                $metadata = apply_filters( 'pms_stripe_transaction_metadata', array(
                    'payment_id'           => $this->payment_id,
                    'request_location'     => $form_location,
                    'subscription_id'      => $subscription_id,
                    'subscription_plan_id' => $this->subscription_plan->id,
                    'home_url'             => home_url(),
                ), $payment, $form_location );

                PaymentIntent::update( $intent->id, array( 'metadata' => $metadata ) );

                // If subscription had a trial, save card fingerprint
                $this->save_trial_card( $subscription_id );

                // Save card expiration info
                if( !empty( $intent->payment_method ) ){
                    $payment_method = PaymentMethod::retrieve( $intent->payment_method );

                    if( !empty( $payment_method->card ) ){

                        if( !empty( $payment_method->card->last4 ) )
                            pms_update_member_subscription_meta( $subscription_id, 'pms_payment_method_number', $payment_method->card->last4 );

                        if( !empty( $payment_method->card->brand ) )
                            pms_update_member_subscription_meta( $subscription_id, 'pms_payment_method_type', $payment_method->card->brand );

                        if( !empty( $payment_method->card->exp_month ) )
                            pms_update_member_subscription_meta( $subscription_id, 'pms_payment_method_expiration_month', $payment_method->card->exp_month );

                        if( !empty( $payment_method->card->exp_year ) )
                            pms_update_member_subscription_meta( $subscription_id, 'pms_payment_method_expiration_year', $payment_method->card->exp_year );
                    }

                }

                return true;

            }

            return true;

        }

        // Get the customer and card id from the database
        if( ! empty( $subscription_id ) ) {
            $this->customer_id  = pms_get_member_subscription_meta( $subscription_id, '_stripe_customer_id', true );
            $this->stripe_token = pms_get_member_subscription_meta( $subscription_id, '_stripe_card_id', true );
        }

        if( empty( $this->stripe_token ) )
            return false;

        // set API key
        $secret_key = $this->secret_key;
        $secret_key = apply_filters( 'pms_stripe_intents_process_payment_secret_key', $this->secret_key, $payment_id, $subscription_id );

        Stripe::setApiKey( $secret_key );
        Stripe::setApiVersion( '2019-08-14');

        //if form location is empty, the request is from plugin scheduled payments
        if ( empty( $form_location ) )
            $form_location = 'psp';

        $customer = \Stripe\Customer::retrieve( $this->customer_id );

        // @NOTE:
        // If the payment method we have on file is different than the payment method saved as default on the Customer
        // we need to use the one from the Customer
        //
        // This is because of an inconsistency on how we handle cards, where on renew/upgrade a new card is requested and
        // a new PaymentMethod is generated, but if it's the same as an existing card on the Customer, it doesn't get
        // added to the Customer so it can't be used for payments but we were saving it as the Card ID on the website
        //
        // This is now fixed and we save the correct card even if we don't add it to the Customer (in the future, we shouldn't
        // request the card details all the time, only when the user wants to update the card on file)
        //
        // We also want to start using the saved token for payments instead of the default payment method found on the Customer
        //
        // So this simply updates the card on the website if we notice that what we have on file is different than the Customers
        // default payment method
        if( !empty( $customer->invoice_settings->default_payment_method ) && $customer->invoice_settings->default_payment_method != $this->stripe_token && !empty( $subscription_id ) ){
            $this->stripe_token = $customer->invoice_settings->default_payment_method;

            pms_update_member_subscription_meta( $subscription_id, '_stripe_card_id', $this->stripe_token );
        }

        if( !empty( $payment->amount ) ) {
            // create payment intent
            try {
                $metadata = apply_filters( 'pms_stripe_transaction_metadata', array(
                    'payment_id'           => $this->payment_id,
                    'request_location'     => $form_location,
                    'subscription_id'      => $subscription_id,
                    'subscription_plan_id' => $this->subscription_plan->id,
                    'home_url'             => home_url(),
                ), $payment, $form_location );

                $args = apply_filters( 'pms_stripe_process_payment_args', array(
                    'payment_method'      => $this->stripe_token,
                    'customer'            => $this->customer_id,
                    'amount'              => $payment->amount,
                    'currency'            => $this->currency,
                    'confirmation_method' => 'manual',
                    'confirm'             => true,
                    'description'         => $this->subscription_plan->name,
                    'off_session'         => true,
                    'metadata'            => $metadata,
                ));

                $args['amount'] = $this->process_amount( $args['amount'], $args['currency'] );

                $intent = PaymentIntent::create( $args );

                $payment->log_data( 'stripe_intent_created' );

                //add transaction ID to payment
                $payment->update( array( 'transaction_id' => $intent->id ) );

                if( $intent->status == 'succeeded' ){
                    $payment->log_data( 'stripe_intent_confirmed' );
                    $payment->update( array( 'status' => 'completed' ) );

                    $payment_method = PaymentMethod::retrieve( $this->stripe_token );
                    $used_cards = get_option( 'pms_used_trial_cards_' . $payment->subscription_id, false );

                    if( in_array( $form_location, array( 'register', 'new_subscription', 'retry_payment', 'register_email_confirmation', 'wppb_register' ) ) && !empty( $payment_method->card->fingerprint ) && !empty( $used_cards ) && in_array( $payment_method->card->fingerprint, $used_cards ) && function_exists( 'pms_add_member_subscription_log' ) )
                        pms_add_member_subscription_log( $subscription_id, 'subscription_trial_period_already_used' );

                    // If subscription had a trial, save card fingerprint
                    $this->save_trial_card( $subscription_id );

                    return true;
                }

                // if( $intent->status == 'requires_action' && isset( $intent->next_action ) && $intent->next_action->type == 'redirect_to_url' )
                //     do_action( 'pms_stripe_send_authentication_email', $payment->user_id, $this->generate_auth_url( $intent, $payment ), $payment->id );

            } catch( Exception $e ) {

                $this->log_error_data( $e );

                $trace = $e->getTrace();

                if ( !empty( $trace[0]['args'][0] ) ) {
                    $error_obj = json_decode( $trace[0]['args'][0] );

                    if( isset( $error_obj->error->code ) && $error_obj->error->code == 'authentication_required' ){
                        pms_add_payment_meta( $payment->id, 'authentication', 'yes' );
                        do_action( 'pms_stripe_send_authentication_email', $payment->user_id, $this->generate_auth_url( $error_obj->error->payment_intent, $payment ), $payment->id );
                    }
                    else
                        $payment->update( array( 'status' => 'failed' ) );

                } else
                    $payment->update( array( 'status' => 'failed' ) );

                return false;

            }
        }

        // if( wp_doing_ajax() )
        //     $this->payment_response( $intent );

        //if we get here, the payment has failed
        return false;
    }

    //AJAX callback used when the Payment Authentication fails (front-end, when the user is on session)
    public function failed_payment_authentication(){
        if( empty( $this->payment_id ) || empty( $_POST['error'] ) )
            return;

        $payment = pms_get_payment( $this->payment_id );

        if ( method_exists( $payment, 'log_data' ) ){

            $data       = array();
            $error      = sanitize_text_field( $_POST['error'] );

            $data['data'] = array(
                'code'              => !empty( $error['code'] ) ? $error['code'] : '',
                'message'           => !empty( $error['message'] ) ? $error['message'] : '',
                'doc_url'           => !empty( $error['doc_url'] ) ? $error['doc_url'] : '',
                'payment_intent_id' => !empty( $error['payment_intent']['id'] ) ? $error['payment_intent']['id'] : '',
            );

            $error_code = !empty( $error['code'] ) ? $error['code'] : '';

            $data['message'] = !empty( $error['message'] ) ? $error['message'] : '';
            $data['desc']    = 'stripe response';

            $payment->log_data( 'payment_failed', $data, $error_code );

        }

        //fail payment
        $payment->update( array( 'status' => 'failed' ) );

        //redirect user to error page
        $this->error_redirect();

    }

    protected function create_customer() {

        // Set API key
        Stripe::setApiKey( $this->secret_key );
        Stripe::setApiVersion( '2019-08-14');

        try {

            $customer = Customer::create( array(
                'email'       => $this->user_email,
                'description' => !empty( $this->user_id ) ? 'User ID: ' . $this->user_id : '',
                'name'        => !empty( $this->user_id ) ? $this->get_user_name( $this->user_id ) : '',
                'address'     => $this->get_billing_details(),
                'metadata'    => !empty( $this->user_id ) ? array( 'user_id' => $this->user_id ) : array(),
            ));

            // Save Stripe customer ID
            if( !empty( $this->user_id ) )
                update_user_meta( $this->user_id, 'pms_stripe_customer_id', $customer->id );

            return $customer;

        } catch( Exception $e ) {

            $this->log_error_data( $e );

            return false;

        }

    }

    protected function process_amount( $amount, $currency = '' ) {

        $zero_decimal_currencies = array(
            'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'
        );

        if( empty( $currency ) )
            $currency = $this->currency;

        if ( !in_array( $currency, $zero_decimal_currencies ) )
            $amount = $amount * 100;

        return round( $amount );

    }

    protected function payment_response( $intent ) {

        if ( $intent->status == 'requires_action' && $intent->next_action->type == 'use_stripe_sdk' ) {

            echo json_encode(array(
                'requires_action'              => true,
                'payment_intent_client_secret' => $intent->client_secret,
                'payment_id'                   => $this->payment_id,
                'form_location'                => PMS_Form_Handler::get_request_form_location()
            ));

        } else if ( $intent->status == 'succeeded' ) {

            echo json_encode(array(
                'success'      => true,
                'redirect_url' => $this->get_success_redirect_url()
            ));

        } else {

            http_response_code(500);
            echo json_encode(array('error' => 'Invalid PaymentIntent status'));

        }

        die();
    }

    protected function error_redirect(){
        // since this is an AJAX request we can't use pms_get_current_page_url() to determine the current page URL
        // so we send the URL with the AJAX request and fallback to the page assigned in Settings
        if( !empty( $_POST['current_page'] ) )
            $current_page = esc_url_raw( $_POST['current_page'] );

        //@TODO: Log this case / add a notice
        if( empty( $current_page ) )
            die();

        $current_page = remove_query_arg( array( 'pms-action', 'pms-intent-id' ), $current_page );

        $pms_is_register = 0;

        if( in_array( $this->form_location, array( 'register', 'register_email_confirmation' ) ) )
            $pms_is_register = 1;

        if( $this->form_location == 'payment_authentication_form' ){
            if( is_user_logged_in() )
                $pms_is_register = 0;
            else
                $pms_is_register = 1;

            $current_page = add_query_arg( array( 'pms_stripe_authentication' => true ), $current_page );
        }

        $data['error']        = true;
        $data['redirect_url'] = add_query_arg( array( 'pms_payment_error' => '1', 'pms_is_register' => $pms_is_register, 'pms_payment_id' => $this->payment_id ), $current_page );

        echo json_encode( $data );
        die();
    }

    public function log_error_data( $exception ) {
        if ( empty( $exception ) ) return;

        $payment = new PMS_Payment( $this->payment_id );

        if ( !method_exists( $payment, 'log_data' ) )
            return;

        $trace = $exception->getTrace();

        //If there's no error code in the exception, use a generic one
        $error_code = 'card_declined';

        $data = array();

        if ( !empty( $trace[0]['args'][0] ) ) {
            $error_obj = json_decode( $trace[0]['args'][0] );

            if( isset( $error_obj->error->payment_intent->id ) ){
                $intent_id = $error_obj->error->payment_intent->id;

                $payment->update( array( 'transaction_id' => $error_obj->error->payment_intent->id ) );
            }

            // generate data array
            if( isset( $error_obj->error ) ){
                $data['data'] = array(
                    'charge_id'         => !empty( $error_obj->error->charge ) ? $error_obj->error->charge : '',
                    'code'              => !empty( $error_obj->error->code ) ? $error_obj->error->code : '',
                    'decline_code'      => !empty( $error_obj->error->decline_code ) ? $error_obj->error->decline_code : '',
                    'doc_url'           => !empty( $error_obj->error->doc_url ) ? $error_obj->error->doc_url : '',
                    'payment_intent_id' => !empty( $error_obj->error->payment_intent->id ) ? $error_obj->error->payment_intent->id : '',
                );
            }

            if ( !empty( $error_obj->error->decline_code ) )
                $error_code = $error_obj->error->decline_code;
            else if ( !empty( $error_obj->error->code ) )
                $error_code = $error_obj->error->code;
        }

        $data['message'] = $exception->getMessage();
        $data['desc']    = 'stripe response';

        $payment->log_data( 'payment_failed', $data, $error_code );
    }

    protected function get_success_redirect_url(){
        if( !is_user_logged_in() ){
            $register_success_url = pms_get_register_success_url();

            if( !empty( $register_success_url ) )
                $current_page = $register_success_url;
            else if( !empty( $_POST['current_page'] ) )
                $current_page = esc_url_raw( $_POST['current_page'] );

            if( $this->form_location == 'payment_authentication_form' ) {
                $current_page = remove_query_arg( 'pms-intent-id', $current_page );
                $current_page = add_query_arg( 'success', 'true', $current_page );
            }

            return isset( $current_page ) ? $current_page : '';
        } else {
            $account_page = pms_get_page( 'account', true );

            if( !empty( $account_page ) )
                $base_url = $account_page;
            else
                $base_url = remove_query_arg( array( 'pms-action', 'subscription_id', 'subscription_plan', 'pmstkn' ), $account_page );

            return add_query_arg( array( 'pmsscscd' => base64_encode( 'subscription_plans' ), 'pms_gateway_payment_action' => base64_encode( $this->form_location ), 'pms_gateway_payment_id' => base64_encode( $this->payment_id ) ), $base_url );
        }

        return '';
    }

    /**
     * Adds extra system Payment Logs error messages that are related to
     * the Payment Intents Stripe implementation
     *
     * @param  string  $message    error message
     * @param  array   $log        array with data about the current error
     * @return string  $message    error message
     */
    public function payment_logs_system_error_messages( $message, $log ) {

        if ( empty( $log['type'] ) )
            return $message;

        $kses_args = array(
            'strong' => array()
        );

        switch( $log['type'] ) {
            case 'stripe_intent_created':
                $message = __( 'Payment Intent created.', 'paid-member-subscriptions' );
                break;
            case 'stripe_intent_attempted_confirmation':
                $message = __( 'Attempting to confirm Payment Intent.', 'paid-member-subscriptions' );
                break;
            case 'stripe_intent_confirmed':
                $message = __( 'Payment Intent confirmed successfully.', 'paid-member-subscriptions' );
                break;
            case 'stripe_authentication_sent':
                $message = __( '3D Secure authentication required. An email with the confirmation link was sent to the user.', 'paid-member-subscriptions' );
                break;
            case 'stripe_authentication_succeeded':
                $message = __( '3D Secure authentication is successful.', 'paid-member-subscriptions' );
                break;
            case 'stripe_authentication_failed':
                $message = __( '3D Secure authentication has failed.', 'paid-member-subscriptions' );
                break;
            case 'stripe_authentication_link_not_clicked':
                $message = __( 'The user did not click on the confirmation link that was sent.', 'paid-member-subscriptions' );
                break;
            case 'stripe_returned_for_authentication':
                $message = __( 'User returned to the website for authentication.', 'paid-member-subscriptions' );
                break;
        }

        return wp_kses( $message, $kses_args );

    }

    protected function handle_subscription(){

        $is_recurring = PMS_Form_Handler::checkout_is_recurring();
        $payment      = pms_get_payment( $this->payment_id );

        //Get the subscription from the database
        $subscription = pms_get_member_subscriptions( array( 'user_id' => $payment->user_id, 'subscription_plan_id' => $payment->subscription_id ) );
        $subscription = $subscription[0];

        $subscription_plan = pms_get_subscription_plan( $payment->subscription_id );

        if( in_array( $this->form_location, array( 'renew_subscription', 'upgrade_subscription' ) ) ) {

            //error redirect ?
            if( empty( $_POST['subscription_plans'] ) )
                die();

            $plan_id = (int)$_POST['subscription_plans'];

            //generate subscription data
            $subscription_data = PMS_Form_Handler::get_subscription_data( $payment->user_id, pms_get_subscription_plan( $plan_id ), $this->form_location, $this->supports( 'plugin_scheduled_payments' ), 'stripe_intents' );

            if( empty( $subscription_data['start_date'] ) )
                $subscription_data['start_date'] = date('Y-m-d H:i:s');
        }

        $subscription_data['status'] = 'active';

        // Handle each subscription by the form location
        switch( $this->form_location ) {

            case 'register':
            // new subscription
            case 'new_subscription':
            // register form E-mail Confirmation compatibility
            case 'register_email_confirmation':
            // retry payment
            case 'retry_payment':

                $subscription->update( $subscription_data );
                break;

            // payment auth form
            case 'payment_authentication_form':
            case 'renew_subscription':

                if( strtotime( $subscription->expiration_date ) < time() || ( !$subscription_plan->is_fixed_period_membership() && $subscription_plan->duration === 0 ) )
                    $expiration_date = $subscription_plan->get_expiration_date();
                else{
                    if( $subscription_plan->is_fixed_period_membership() ){
                        $expiration_date = date( 'Y-m-d 23:59:59', strtotime( $subscription->expiration_date . '+ 1 year' ) );
                    }
                    else{
                        $expiration_date = date( 'Y-m-d 23:59:59', strtotime( $subscription->expiration_date . '+' . $subscription_plan->duration . ' ' . $subscription_plan->duration_unit ) );
                    }
                }

                if( $is_recurring ) {
                    $subscription_data['billing_next_payment'] = $expiration_date;
                    $subscription_data['expiration_date']      = '';
                } else
                    $subscription_data['expiration_date']      = $expiration_date;

                $subscription->update( $subscription_data );

                break;

            // upgrading the subscription
            case 'upgrade_subscription':

                //get existing customer details
                $customer_id  = pms_get_member_subscription_meta( $subscription->id, '_stripe_customer_id', true );
                $stripe_token = pms_get_member_subscription_meta( $subscription->id, '_stripe_card_id', true );

                //remove subscription from above and insert the new one
                $subscription->remove();

                $new_subscription = new PMS_Member_Subscription();
                $new_subscription->insert( $subscription_data );

                //add them to the current subscription
                pms_update_member_subscription_meta( $new_subscription->id, '_stripe_customer_id', $customer_id );
                pms_update_member_subscription_meta( $new_subscription->id, '_stripe_card_id', $stripe_token );

                break;

            default:
                break;

        }
    }

    protected function intent_confirmation_failed( $intent ){
        $payment = pms_get_payment( (int)$intent->metadata->payment_id );

        //handle payment
        $payment->log_data( 'stripe_authentication_failed' );

        if( !empty( $intent->last_payment_error ) )
            $payment->log_data( 'payment_failed', $this->parse_intent_last_error( $intent ), $intent->last_payment_error->code );

        $payment->update( array( 'status' => 'failed' ) );

        //handle subscription
        $subscription_data = array(
            'status'                => 'expired',
            'billing_duration'      => '',
            'billing_duration_unit' => '',
            'billing_next_payment'  => NULL,
        );

        $subscription = pms_get_member_subscriptions( array( 'user_id' => $payment->user_id, 'subscription_plan_id' => $payment->subscription_id ) );
        $subscription = $subscription[0];

        $subscription->update( $subscription_data );
    }

    protected function parse_intent_last_error( $intent ){
        if( empty( $intent->last_payment_error ) )
            return array();

        $error = array();

        $error['data'] = array(
            'payment_intent_id' => !empty( $intent->id ) ? $intent->id : '',
            'doc_url'           => !empty( $intent->last_payment_error->doc_url ) ? $intent->last_payment_error->doc_url : '',
            'code'              => !empty( $intent->last_payment_error->code ) ? $intent->last_payment_error->code : '',
        );

        $error['message'] = !empty( $intent->last_payment_error->message ) ? $intent->last_payment_error->message : '';
        $error['desc']    = 'stripe response';

        return $error;
    }

    //authentication stuff
    protected function generate_auth_url( $intent, $payment ){
        $account_page = pms_get_page( 'account', true );

        //@TODO: add a notice in this case (use an option)
        if( empty( $account_page ) )
            return '';

        $url = add_query_arg( array(
            'pms-action'    => 'authenticate_stripe_payment',
            'pms-intent-id' => $intent->id
        ), $account_page );

        return $url;
    }

    public function authentication_page( $content ) {
        if( empty( $_GET['pms-action'] ) || $_GET['pms-action'] != 'authenticate_stripe_payment' || empty( $_GET['pms-intent-id'] ) )
            return $content;

        if( empty( $this->secret_key ) )
            return $content;

        Stripe::setApiKey( $this->secret_key );
        Stripe::setApiVersion( '2019-08-14');

        $intent = \Stripe\PaymentIntent::retrieve( sanitize_text_field( $_GET['pms-intent-id'] ) );

        if( $intent->status == 'succeeded' )
            return $content;

        $payment = pms_in_get_payment_by_transaction_id( sanitize_text_field( $_GET['pms-intent-id'] ) );

        if( empty( $payment ) )
            return $content;

        global $wpdb;

        $payment_logs = $wpdb->get_var( "SELECT logs FROM {$wpdb->prefix}pms_payments WHERE id LIKE $payment->id" );
        $payment_logs = json_decode( $payment_logs );

        $add_log = true;

        if( is_array( $payment_logs ) ){
            foreach( array_reverse( $payment_logs ) as $log ){
                if( $log->type == 'stripe_returned_for_authentication' ){
                    $add_log = false;
                    break;
                }
            }
        }

        if( $add_log )
            $payment->log_data( 'stripe_returned_for_authentication' );

        ob_start();

        include 'views/view-payment-authentication-form.php';

        $content = ob_get_clean();

        return $content;
    }

    public function reauthenticate_intent(){
        if( empty( $this->secret_key ) || empty( $this->stripe_token ) )
            return false;

        if( empty( $_POST['intent_id'] ) )
            return false;

        // Set API key
        Stripe::setApiKey( $this->secret_key );
        Stripe::setApiVersion( '2019-08-14');

        $payment   = pms_get_payment( $this->payment_id );
        $intent_id = sanitize_text_field( $_POST['intent_id'] );

        try {

            //$payment->log_data( 'stripe_intent_attempted_confirmation' );

            $intent = \Stripe\PaymentIntent::retrieve( $intent_id );

            $metadata = apply_filters( 'pms_stripe_transaction_metadata', array(
                'payment_id'           => $this->payment_id,
                'request_location'     => $this->form_location,
                'subscription_plan_id' => $payment->subscription_id,
                'home_url'             => home_url(),
            ), $payment, $this->form_location );

            //create another intent using information from the older one
            $args = array(
                'payment_method'      => $this->stripe_token,
                'customer'            => $intent->customer,
                'amount'              => $intent->amount,
                'currency'            => $intent->currency,
                'confirmation_method' => 'manual',
                'confirm'             => true,
                'description'         => $intent->description,
                'setup_future_usage'  => 'off_session',
                'metadata'            => $metadata,
            );

            $new_intent = PaymentIntent::create( $args );

            if( $new_intent->status == 'succeeded' ){
                $payment->update( array( 'status' => 'completed', 'transaction_id' => $new_intent->id ) );
                $payment->log_data( 'stripe_intent_confirmed' );

                pms_delete_payment_meta( $payment_id, 'authentication' );
            }

        } catch( Exception $e ) {

            //log errors
            $this->log_error_data( $e );

            //fail payment
            $payment->update( array( 'status' => 'failed' ) );

            //redirect user to error page
            $this->error_redirect();
        }

        if( wp_doing_ajax() )
            $this->payment_response( $new_intent );

        return true;
    }

    /**
     * Add Payment Intent nonce to form
     *
     * @param  string   $output
     * @param  array    $pms_settings
     * @return string
     */
    public function field_ajax_nonces( $output, $pms_settings ) {

        $output .= '<input type="hidden" id="pms-stripe-ajax-payment-intent-nonce" name="stripe_ajax_payment_intent_nonce" value="'. esc_attr( wp_create_nonce( 'pms_create_payment_intent' ) ) .'"/>';

        return $output;

    }

    /**
     * Add Update Payment Method nonce to form
     *
     * @param  string   $output
     * @param  array    $pms_settings
     * @return string
     */
    public function field_update_payment_method_nonce() {

        echo '<input type="hidden" id="pms-stripe-ajax-update-payment-method-nonce" name="stripe_ajax_update_payment_method_nonce" value="'. esc_attr( wp_create_nonce( 'pms_update_payment_method' ) ) .'"/>';

    }

    /**
     * Remove success message wrappers from profile builder register form and add
     * payment failed hook
     *
     * @return void
     */
    public function wppb_success_message_wrappers() {

        $payment_id = $this->is_failed_payment_request();

        if( $payment_id !== false ){
            $this->payment_id = $payment_id;

            add_filter( 'wppb_form_message_tpl_start',   '__return_empty_string' );
            add_filter( 'wppb_form_message_tpl_end',     '__return_empty_string' );
            add_filter( 'wppb_register_success_message', array( $this, 'wppb_handle_failed_payment' ) );
        }

    }

    /**
     * Checks if the current $_POST data matches an user with a failed payment and returns the ID of that payment
     *
     * @return boolean
     */
    private function is_failed_payment_request(){
        if( !isset( $_POST['stripe_token'] ) )
            return false;

        if( isset( $_POST['username'] ) ){
            $user  = sanitize_user( $_POST['username'] );
            $field = 'login';
        } else if( isset( $_POST['email'] ) ){
            $user  = sanitize_email( $_POST['email'] );
            $field = 'email';
        } else
            return false;

        $user = get_user_by( $field, $user );

        if( $user === false )
            return false;

        $payments = pms_get_payments( array( 'user_id' => $user->ID, 'status' => 'failed' ) );


        if( !empty( $payments ) && !empty( $payments[0]->id ) )
            return $payments[0]->id;

        return false;
    }

    /**
     * Display payment failed error message
     *
     * Hook: wppb_register_success_message
     *
     * @param  string   $content
     * @return function pms_in_stripe_error_message
     */
    public function wppb_handle_failed_payment( $content ){

        return pms_in_stripe_error_message( $content, 1, $this->payment_id );

    }

    /**
     * Create a Payment Intent and return the client secret for use in front-end
     *
     * Hook: wp_ajax_pms_create_payment_intent, wp_ajax_nopriv_pms_create_payment_intent
     *
     * @return json
     */
    public function create_payment_intent(){

        if( empty( $this->secret_key ) )
            die();

        if( !isset( $_POST['subscription_plans'] ) )
            die();

        if( is_user_logged_in() ){
            $user  = get_userdata( get_current_user_id() );
            $email = $user->user_email;
        } else
            $email = isset( $_POST['user_email'] ) ? sanitize_email( $_POST['user_email'] ) : ( isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '' );

        if( empty( $email ) )
            die();

        $this->user_email = $email;

        // Verify validity of Subscription Plan
        $subscription_plan = pms_get_subscription_plan( absint( $_POST['subscription_plans'] ) );

        if( !isset( $subscription_plan->id ) )
            die();

        // need to take into account PayWhatYouWant, Discounts and Taxes
        $amount = apply_filters( 'pms_stripe_create_payment_intent_amount', $subscription_plan->price, $subscription_plan );

        // Check PWYW pricing
        if( function_exists( 'pms_in_pwyw_pricing_enabled' ) && pms_in_pwyw_pricing_enabled( $subscription_plan->id ) ){

            if( !empty( $_POST['subscription_price_' . $subscription_plan->id ] ) )
                $amount = (int)$_POST['subscription_price_' . $subscription_plan->id ];

        }

        global $pms_prorate;

        if( is_user_logged_in() && class_exists( 'PMS_IN_ProRate' ) && isset( $pms_prorate ) ){
            $amount = $pms_prorate->get_stripe_intents_prorated_amount( $amount, $subscription_plan->id );
        }

        // Add sign-up fee if necessary
        if( !empty( $subscription_plan->sign_up_fee ) && apply_filters( 'pms_stripe_create_payment_intent_apply_sign_up_fee', true, $subscription_plan ) ){

            $form_location = PMS_Form_Handler::get_request_form_location( 'pmstkn_original' );

            if( in_array( $form_location, apply_filters( 'pms_checkout_signup_fee_form_locations', array( 'register', 'new_subscription', 'retry_payment', 'register_email_confirmation', 'change_subscription', 'wppb_register' ) ) ) )
                $amount = $amount + $subscription_plan->sign_up_fee;

       }

        // Apply discount code if present
        if( function_exists( 'pms_in_calculate_discounted_amount' ) && !empty( $_POST['discount_code' ] ) ){

            $discount_code = pms_in_get_discount_by_code( sanitize_text_field( $_POST['discount_code'] ) );

            $amount = pms_in_calculate_discounted_amount( $amount, $discount_code );

        }

        // Apply taxes if they are enabled
        if( function_exists( 'pms_in_tax_enabled' ) && pms_in_tax_enabled() ){
            $amount = apply_filters( 'pms_tax_apply_to_amount', $amount, $subscription_plan->id );
        }

        // Set API key
        Stripe::setApiKey( $this->secret_key );
        Stripe::setApiVersion( '2019-08-14');

        // Grab existing Customer if logged-in
        if( is_user_logged_in() )
            $customer = $this->get_customer( get_current_user_id() );

        if( !isset( $customer ) || !isset( $customer->id ) )
            $customer = $this->create_customer();

        $args = apply_filters( 'pms_stripe_create_payment_intent_args', array(
            'amount'             => $amount,
            'currency'           => $this->currency,
            'customer'           => $customer->id,
            //'confirmation_method' => 'manual',
            //'confirm'             => true,
            'description'        => $subscription_plan->name,
            //'off_session'         => true,
            'setup_future_usage' => 'off_session',
            'metadata'           => array(
                'subscription_plan_id' => $subscription_plan->id,
                'home_url'             => home_url(),
            ),
        ), $subscription_plan );

        $args['amount'] = $this->process_amount( $args['amount'], $args['currency'] );

        try {

            $intent = PaymentIntent::create( $args );

        } catch( Exception $e ){

            die();

        }

        echo json_encode( array(
            'success'       => true,
            'intent_secret' => $intent->client_secret
        ) );
        die();

    }

    /**
     * Create a Setup Intent and return the client secret for use in front-end
     *
     * Hook: wp_ajax_pms_create_setup_intent, wp_ajax_nopriv_pms_create_setup_intent
     *
     * @return json
     */
    public function create_setup_intent(){

        if( empty( $this->secret_key ) )
            die();

        // Set API key
        Stripe::setApiKey( $this->secret_key );
        Stripe::setApiVersion( '2019-08-14');

        try {

            $intent = \Stripe\SetupIntent::create();

        } catch( Exception $e ) {

            die();

        }

        echo json_encode( array(
            'success'       => true,
            'intent_secret' => $intent->client_secret
        ) );

        die();

    }

    /**
     * Create a Setup Intent for an Update Payment Method call
     *
     * Hook: wp_ajax_pms_update_payment_method, wp_ajax_nopriv_pms_update_payment_method
     *
     * @return json
     */
    public function create_update_payment_method_setup_intent(){

        if( empty( $this->secret_key ) )
            die();

        // Set API key
        Stripe::setApiKey( $this->secret_key );
        Stripe::setApiVersion( '2019-08-14');

        try {

            $intent = \Stripe\SetupIntent::create();

        } catch( Exception $e ) {

            die();

        }

        echo json_encode( array(
            'success'       => true,
            'intent_secret' => $intent->client_secret
        ) );

        die();

    }

    /**
     * Updates a customers payment method for a subscription based on the data received
     */
    public function update_customer_payment_method( $member_subscription ){

        if( ! isset( $_REQUEST['pmstkn'] ) || ! wp_verify_nonce( sanitize_text_field( $_REQUEST['pmstkn'] ), 'pms_update_payment_method' ) )
            return false;

        if( empty( $this->secret_key ) )
            return false;

        if( empty( $this->stripe_token ) )
            return false;

        // Set API key
        Stripe::setApiKey( $this->secret_key );
        Stripe::setApiVersion( '2019-08-14');

        if( empty( $member_subscription ) || empty( $_REQUEST['stripe_token'] ) )
            return false;

        $customer = $this->get_customer( $member_subscription->user_id );

        if( empty( $customer )  )
            return false;

        $success_message = false;

        try {

            $payment_method = PaymentMethod::retrieve( $this->stripe_token );

            //check if this payment method is already attached to the customer, if not, add it
            $customer_payment_methods = \Stripe\PaymentMethod::all( array( 'customer' => $customer->id, 'type' => 'card' ) );
            $attach_card              = true;

            if( !empty( $customer_payment_methods['data'] ) ){
                foreach( $customer_payment_methods['data'] as $existing_payment_method ){
                    if( $payment_method->id == $existing_payment_method->id ){
                        $attach_card = false;
                        break;
                    }
                }
            }

            if( $attach_card )
                $payment_method->attach( ['customer' => $customer->id ] );

            \Stripe\Customer::update(
                $customer->id,
                array(
                    'invoice_settings' => array(
                        'default_payment_method' => $this->stripe_token,
                    )
                )
            );

            pms_update_member_subscription_meta( $member_subscription->id, '_stripe_card_id', $this->stripe_token );

            // Update saved credit card details
            if( !empty( $payment_method->card ) ){

                if( !empty( $payment_method->card->last4 ) )
                    pms_update_member_subscription_meta( $member_subscription->id, 'pms_payment_method_number', $payment_method->card->last4 );

                if( !empty( $payment_method->card->brand ) )
                    pms_update_member_subscription_meta( $member_subscription->id, 'pms_payment_method_type', $payment_method->card->brand );

                if( !empty( $payment_method->card->exp_month ) )
                    pms_update_member_subscription_meta( $member_subscription->id, 'pms_payment_method_expiration_month', $payment_method->card->exp_month );

                if( !empty( $payment_method->card->exp_year ) )
                    pms_update_member_subscription_meta( $member_subscription->id, 'pms_payment_method_expiration_year', $payment_method->card->exp_year );
            }

            pms_add_member_subscription_log( $member_subscription->id, 'subscription_payment_method_updated' );

            $success_message = true;

            do_action( 'pms_payment_method_updated', $member_subscription );

        } catch( Exception $e ) {

            // use pms-errors to write something
            pms_errors()->add( 'update_payment_method', __( 'Something went wrong, please try again.', 'paid-member-subscriptions' ) );

        }

        if( $success_message ){
            $redirect_url = remove_query_arg( array( 'pms-action', 'subscription_plan', 'subscription_id', 'pmstkn' ), pms_get_current_page_url() );

            $redirect_url = add_query_arg( array(
                'pmsscscd'  => base64_encode('update_payment_method'),
                'pmsscsmsg' => base64_encode( __( 'Payment method updated successfully.', 'paid-member-subscriptions' ) ),
            ), $redirect_url );


            wp_redirect( esc_url_raw( $redirect_url ) );
            exit;
        }


    }

    /**
     * Save current payment method fingerprint so it can't be used to access a trial for the
     * given subscription's subscription plan
     *
     * @param  int   $subscription_id   Subscription ID
     * @return void
     */
    public function save_trial_card( $subscription_id ){

        $member_subscription = pms_get_member_subscription( $subscription_id );

        if( !empty( $member_subscription->subscription_plan_id ) ){

            $subscription_plan = pms_get_subscription_plan( $member_subscription->subscription_plan_id );

            if( !empty( $subscription_plan->trial_duration ) ){

                $plan_fingerprints = get_option( 'pms_used_trial_cards_' . $subscription_plan->id, false );
                $payment_method    = PaymentMethod::retrieve( $this->stripe_token );

                if( $plan_fingerprints == false )
                    $plan_fingerprints = array( $payment_method->card->fingerprint );
                else
                    $plan_fingerprints[] = $payment_method->card->fingerprint;

                update_option( 'pms_used_trial_cards_' . $subscription_plan->id, $plan_fingerprints, false );

            }

        }

    }

    /**
     * Determines if trial is valid for the current request subscription plans and payment method
     *
     * Hook: pms_checkout_has_trial
     *
     * @param  boolean
     * @return boolean
     */
    public function disable_trial_if_duplicate_card( $has_trial ){

        if( $has_trial == false || apply_filters( 'pms_disable_trial_if_duplicate_card', false ) )
            return $has_trial;

        // Disable when payments are in test mode
        if( pms_is_payment_test_mode() )
            return $has_trial;

        // Skip if token is not for a payment method
        if( empty( $_POST['stripe_token'] ) || empty( $_POST['subscription_plans'] ) || strpos( sanitize_text_field( $_POST['stripe_token'] ), 'pm_' ) === false )
            return $has_trial;

        $plan = pms_get_subscription_plan( absint( $_POST['subscription_plans'] ) );

        if( empty( $plan->id ) )
            return $has_trial;

        if( empty( $this->secret_key ) )
            return $has_trial;

        // Set API key
        Stripe::setApiKey( $this->secret_key );
        Stripe::setApiVersion( '2019-08-14');

        $payment_method = PaymentMethod::retrieve( $this->stripe_token );

        if( empty( $payment_method->card->fingerprint ) )
            return $has_trial;

        $used_cards = get_option( 'pms_used_trial_cards_' . $plan->id, false );

        if( empty( $used_cards ) )
            return $has_trial;

        if( in_array( $payment_method->card->fingerprint, $used_cards ) )
            return false;

        return $has_trial;

    }

    /**
     * Add Publishable Key to the Update Payment Method form
     */
    public function update_payment_form_field_publishable_key(){

        $this->field_publishable_key( '', get_option( 'pms_payments_settings' ), false );

    }
}
