<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

Class PMS_IN_Tax {

    public $tax_rate  = 0;
    public $valid_vat = false;

    public function __construct(){

        // apply tax to payment data
        add_filter( 'pms_register_payment_data', array( $this, 'apply_tax_to_payment_data' ), 60, 2 );

        // apply tax to subscription data
        add_filter( 'pms_process_checkout_subscription_data', array( $this, 'apply_tax_to_subscription_data' ), 60, 2 );

        // apply tax to custom hook
        add_filter( 'pms_tax_apply_to_amount', array( $this, 'apply_tax_to_amount' ), 60, 2 );

        // apply tax to recurring payment after free trial
        add_action( 'pms_cron_after_processing_member_subscription', array( $this, 'apply_tax_breakdown_to_recurring_payment_after_free_trial' ), 40, 2 );

        // front-end price breakdown
        $this->init_price_breakdown();

        // Display tax breakdown on Invoice
        add_filter( 'pms_inv_invoice_template',     array( $this, 'replace_invoice_template' ) );
        add_filter( 'pms_inv_invoice_template_tax', array( $this, 'tax_invoice_template' ), 10, 2 );

        // Display a message in the front-end forms indicating that taxes will be applied
        add_action( 'plugins_loaded', array( $this, 'load_frontend_message' ) );

    }

    /**
     * Apply tax to payment data sent to the payment gateway
     */
    public function apply_tax_to_payment_data( $payment_data, $payments_settings ){

        if( empty( $_POST['pms_billing_country'] ) )
            return $payment_data;

        // check if the subscription is tax exempt
        $tax_exempt = get_post_meta( $payment_data['subscription_data']['subscription_plan_id'], 'pms_subscription_plan_tax_exempt', true );

        if( !empty( $tax_exempt ) && $tax_exempt == 1 )
            return $payment_data;

        $this->tax_rate = $this->determine_tax_rate();

        if( $this->tax_rate == 0 )
            return $payment_data;

        $tax_settings = get_option( 'pms_tax_settings', array() );

        if( empty( $tax_settings ) )
            return $payment_data;

        $payment = pms_get_payment( isset($payment_data['payment_id']) ? $payment_data['payment_id'] : 0 );

        // Check if Tax Rate is already added
        $tax_rate_exists = pms_get_payment_meta( $payment->id, 'pms_tax_rate', true );

        if( !empty( $tax_rate_exists ) )
            return $payment_data;

        if( $payment_data['sign_up_amount'] == '0' ){

            // even if sign_up_amount is 0, save the tax details on the payment so that future recurring payments can use them
            if( !empty( $payment->id ) ){
                pms_add_payment_meta( $payment->id, 'pms_tax_rate', $this->tax_rate );
                pms_add_payment_meta( $payment->id, 'pms_tax_country', sanitize_text_field( $_POST['pms_billing_country'] ) );

                if( !empty( $_POST['pms_billing_state'] ) )
                    pms_add_payment_meta( $payment->id, 'pms_tax_state', sanitize_text_field( $_POST['pms_billing_state'] ) );

                if( !empty( $_POST['pms_billing_city'] ) )
                    pms_add_payment_meta( $payment->id, 'pms_tax_city', sanitize_text_field( $_POST['pms_billing_city'] ) );

                if ( pms_in_tax_eu_vat_enabled() )
                    pms_add_payment_meta( $payment->id, 'pms_tax_valid_vat_number', $this->valid_vat === true ? 'true' : 'false' );
            }

            return $payment_data;

        }

        //$price_with_tax = $this->calculate_tax_rate( $payment_data['amount'] );

        $payment_data['sign_up_amount'] = $this->calculate_tax_rate( $payment_data['sign_up_amount'] );
        $payment_data['amount']         = $this->calculate_tax_rate( $payment_data['amount'] );

        if( $payment_data['sign_up_amount'] == null && !empty( $payment_data['amount'] ) )
            $payment_data['sign_up_amount'] = $payment_data['amount'] ;

        if( !is_null( $payment_data['sign_up_amount'] ) ){

            if( !empty( $payment->id ) ){
                $payment->update( array( 'amount' => $payment_data['sign_up_amount'] ) );

                pms_add_payment_meta( $payment->id, 'pms_tax_rate', $this->tax_rate );
                pms_add_payment_meta( $payment->id, 'pms_tax_country', sanitize_text_field( $_POST['pms_billing_country'] ) );

                if( !empty( $_POST['pms_billing_state'] ) )
                    pms_add_payment_meta( $payment->id, 'pms_tax_state', sanitize_text_field( $_POST['pms_billing_state'] ) );

                if( !empty( $_POST['pms_billing_city'] ) )
                    pms_add_payment_meta( $payment->id, 'pms_tax_city', sanitize_text_field( $_POST['pms_billing_city'] ) );

                if ( pms_in_tax_eu_vat_enabled() )
                    pms_add_payment_meta( $payment->id, 'pms_tax_valid_vat_number', $this->valid_vat === true ? 'true' : 'false' );

            }

        }

        return $payment_data;

    }

    /**
     * Apply tax to a random given amount. Data is sourced from $_POST
     *
     * Hooked to: 'pms_tax_apply_to_amount'
     *
     * @param  int  $amount                 Amount to be paid after Pay What You Want pricing, Sign-Up Fees and Discount codes were applied
     * @param  int  $subscription_plan_id   Subscription Plan ID
     *
     * @return int                          Amount with tax applied
     */
    public function apply_tax_to_amount( $amount, $subscription_plan_id ){

        if( empty( $amount ) )
            return $amount;

        if( empty( $_POST['pms_billing_country'] ) )
            return $amount;

        // check if the subscription is tax exempt
        $tax_exempt = get_post_meta( $subscription_plan_id, 'pms_subscription_plan_tax_exempt', true );

        if( !empty( $tax_exempt ) && $tax_exempt == 1 )
            return $amount;

        $this->tax_rate = $this->determine_tax_rate();

        if( $this->tax_rate == 0 )
            return $amount;

        $tax_settings = get_option( 'pms_tax_settings', array() );

        if( empty( $tax_settings ) )
            return $amount;

        return $this->calculate_tax_rate( $amount );

    }

    /**
     * Apply tax to subscription data
     */
    public function apply_tax_to_subscription_data( $subscription_data = array(), $checkout_data = array() ){

        if( empty( $subscription_data ) )
            return array();

        // if( empty( $checkout_data['is_recurring' ] ) )
        //     return $subscription_data;

        if( !empty( $subscription_data['payment_gateway'] ) && !empty( $subscription_data['billing_amount'] ) )
            $subscription_data['billing_amount'] = $this->calculate_tax_rate( $subscription_data['billing_amount'] );

        return $subscription_data;

    }

    public function apply_tax_breakdown_to_recurring_payment_after_free_trial( $subscription, $payment ){

        if( empty( $subscription->subscription_plan_id ) || empty( $payment->id ) )
            return;

        $payment_tax_rate = pms_get_payment_meta( $payment->id, 'pms_tax_rate', true );

        if( !empty( $payment_tax_rate ) )
            return;

        $tax_settings = get_option( 'pms_tax_settings', array() );

        if( empty( $tax_settings ) )
            return;

        // check if the subscription is tax exempt
        $tax_exempt = get_post_meta( $subscription->subscription_plan_id, 'pms_subscription_plan_tax_exempt', true );

        if( !empty( $tax_exempt ) && $tax_exempt == 1 )
            return;

        $country = pms_get_payment_meta( $payment->id, 'pms_billing_country', true );
        $state   = pms_get_payment_meta( $payment->id, 'pms_billing_state', true );
        $city    = pms_get_payment_meta( $payment->id, 'pms_billing_city', true );

        $this->tax_rate = $this->determine_tax_rate( $country, $state, $city );

        if( $this->tax_rate == 0 )
            return;

        pms_add_payment_meta( $payment->id, 'pms_tax_rate', $this->tax_rate );
        pms_add_payment_meta( $payment->id, 'pms_tax_country', $country );

        if( !empty( $state ) )
            pms_add_payment_meta( $payment->id, 'pms_tax_state', $state );

        if( !empty( $city ) )
            pms_add_payment_meta( $payment->id, 'pms_tax_city', $city );

        if ( pms_in_tax_eu_vat_enabled() ){

            $vat_number = pms_get_payment_meta( $payment->id, 'pms_vat_number', true );

            if( !empty( $vat_number ) ){
                $pms_tax_extra_fields = new PMS_IN_Tax_Extra_Fields();

                $vat_validation = $pms_tax_extra_fields->validate_vat_number( $country, $vat_number );

                if( isset( $vat_validation['valid'] ) && $vat_validation['valid'] == 'true' ){
                    pms_add_payment_meta( $payment->id, 'pms_tax_valid_vat_number', 'true' );
                }
            }
        }

    }

    /**
     * Adds all the necessary hooks in order for the front-end price breakdown to appear
     */
    public function init_price_breakdown(){

        add_action( 'pms_register_form_bottom',             array( $this, 'load_frontend_price_breakdown' ), 60 );
        add_action( 'pms_new_subscription_form_bottom',     array( $this, 'load_frontend_price_breakdown' ), 60 );
        add_action( 'pms_upgrade_subscription_form_bottom', array( $this, 'load_frontend_price_breakdown' ), 60 );
        add_action( 'pms_renew_subscription_form_bottom',   array( $this, 'load_frontend_price_breakdown' ), 60 );
        add_action( 'pms_change_subscription_form_bottom',  array( $this, 'load_frontend_price_breakdown' ), 60 );
        add_action( 'pms_retry_payment_form_bottom',        array( $this, 'load_frontend_price_breakdown' ), 60 );
        add_action( 'wppb_after_form_fields',               array( $this, 'wppb_load_frontend_price_breakdown' ), 60 );

    }

    public function load_frontend_price_breakdown(){

        include_once 'views/view-price-breakdown.php';

    }

    public function wppb_load_frontend_price_breakdown( $content ){

        ob_start();

        include_once 'views/view-price-breakdown.php';

        $price_breakdown = ob_get_clean();

        return $content . $price_breakdown;

    }

    /**
     * Determine if tax applies and process payment amount
     */
    public function calculate_tax_rate( $amount ){

        $this->tax_rate = $this->determine_tax_rate();

        if( empty( $amount ) || pms_in_tax_prices_include_tax() === true || $this->tax_rate == 0 )
            return $amount;

        // don't add tax if VAT number is valid
        if( !empty( $_POST['pms_vat_number'] ) && !empty( $_POST['pms_billing_country'] ) ){
            $pms_tax_extra_fields = new PMS_IN_Tax_Extra_Fields();

            $vat_validation = $pms_tax_extra_fields->validate_vat_number( sanitize_text_field( $_POST['pms_billing_country'] ), sanitize_text_field( $_POST['pms_vat_number'] ) );

            if( isset( $vat_validation['valid'] ) && $vat_validation['valid'] == 'true' ){
                $this->valid_vat = true;

                // add it if the customer is from the same country as the merchant
                if( $_POST['pms_billing_country'] != pms_in_tax_get_merchant_country() )
                    return $amount;

            }
        }

        return round( $amount * ( 1 + $this->tax_rate / 100 ), 2 );

    }

    /**
     * Determines the tax rate from the current flow or returns an already determined rate
     */
    public function determine_tax_rate( $country = '', $state = '', $city = '', $unprocessed = false ){

        if( !empty( $this->tax_rate ) )
            return $this->tax_rate;

        if( empty( $country ) && isset( $_POST['pms_billing_country'] ) )
            $country = sanitize_text_field( $_POST['pms_billing_country'] );

        if( empty( $state ) && isset( $_POST['pms_billing_state'] ) )
            $state = sanitize_text_field( $_POST['pms_billing_state'] );

        if( empty( $city ) && isset( $_POST['pms_billing_city'] ) )
            $city = sanitize_text_field( $_POST['pms_billing_city'] );

        // search using all the completed data
        $data = array(
            'tax_country' => $country,
            'tax_state'   => !empty( $state ) ? $state : '*',
            'tax_city'    => !empty( $city ) ? $city : '*',
        );

        $current_tax_rate = pms_in_tax_get_rates( $data );

        // remove city and search again
        if( empty( $current_tax_rate ) && !empty( $city ) ){
            $data['tax_city'] = '*';

            $current_tax_rate = pms_in_tax_get_rates( $data );
        }

        // remove state and search again
        if( empty( $current_tax_rate ) && !empty( $state ) ){
            $data['tax_state'] = '*';

            $current_tax_rate = pms_in_tax_get_rates( $data );
        }

        // if EU VAT is enabled, get the default VAT rate from the plugin if not found in the database
        if( pms_in_tax_eu_vat_enabled() && empty( $current_tax_rate ) ){
            $default_eu_vat = pms_in_tax_get_eu_vat_countries();

            if( isset( $default_eu_vat[ $country ] ) && isset( $default_eu_vat[ $country ]['rate'] ) )
                $current_tax_rate = array( array( 'tax_rate' => $default_eu_vat[ $country ]['rate'], 'tax_name' => __('VAT', 'paid-member-subscriptions') ) );
        }

        // if we have a tax rate process it, if not, use the default rate
        if( !empty( $current_tax_rate ) && isset( $current_tax_rate[0] ) && $unprocessed === true )
            $current_tax_rate = $current_tax_rate[0];
        elseif( !empty( $current_tax_rate ) && isset( $current_tax_rate[0] ) && !empty( $current_tax_rate[0]['tax_rate'] ) )
            $current_tax_rate = $current_tax_rate[0]['tax_rate'];
        else
            $current_tax_rate = pms_in_tax_get_default_rate();

        return $current_tax_rate;

    }

    /**
     * Change Invoice template slug
     */
    public function replace_invoice_template(){
        return 'tax';
    }

    /**
     * Invoice template with VAT/TAX and VAT ID
     */
    public function tax_invoice_template( $pdf_invoice, $payment ){

        /**
         * General settings
         *
         */
    	$defaults = array(
    	    'format'          => '{{number}}',
            'company_details' => __('Please fill your company details in Paid Member Subscriptions -> Settings -> Invoices', 'paid-member-subscriptions'),
            'title'           => __('Invoice', 'paid-member-subscriptions'),
            'logo'            => '',
            'font'            => 'dejavusans',
        );
        $settings = wp_parse_args( get_option( 'pms_invoices_settings', array() ), $defaults );

        /**
         * Subscription plan
         *
         */
        $subscription_plan = pms_get_subscription_plan( $payment->subscription_id );

    	/**
         * Invoice data
         *
         */
        $invoice_number          = pms_in_inv_parse_payment_invoice_tags( ( ! empty( $settings['format'] ) ? $settings['format'] : '{{number}}' ), $payment );
        $invoice_billing_details = pms_in_inv_get_invoice_billing_details( $payment->id );

        /**
         * Payment gateway
         *
         */
        $payment_gateways = pms_get_payment_gateways();
        $payment_gateway  = ( ! empty( $payment_gateways[$payment->payment_gateway]['display_name_admin'] ) ? $payment_gateways[$payment->payment_gateway]['display_name_admin'] : '' );

        $payment_statuses = pms_get_payment_statuses();

        /**
         * Provider company details
         *
         */

         // Retrieve saved company details from the payment
         $payment_company_details = pms_get_payment_meta( $payment->id, 'pms_billing_settings_company_details', true );

         if( apply_filters( 'pms_inv_use_payment_company_details', true, $payment ) )
             $company_details = !empty( $payment_company_details ) ? wpautop( $payment_company_details ) : ( !empty( $settings['company_details'] ) ? wpautop( $settings['company_details'] ) : '' );
         else
             $company_details = ( ! empty( $settings['company_details'] ) ? wpautop( $settings['company_details'] ) : '' );

        /**
         * Client billing details
         *
         */
        $billing_details = '';

        if( ! empty( $invoice_billing_details ) ) {

            // Company
            if( ! empty( $invoice_billing_details['pms_billing_company'] ) ) {

                $billing_details .= $invoice_billing_details['pms_billing_company'];

                if( apply_filters( 'pms_inv_always_show_first_last_name', false ) ){
                    $billing_details .= ( ! empty( $invoice_billing_details['pms_billing_first_name'] ) ? $invoice_billing_details['pms_billing_first_name'] : '' ) . ' ';
                    $billing_details .= ( ! empty( $invoice_billing_details['pms_billing_last_name'] ) ? $invoice_billing_details['pms_billing_last_name'] : '' );
                }

            // First name and last name
            } else {

                $billing_details .= ( ! empty( $invoice_billing_details['pms_billing_first_name'] ) ? $invoice_billing_details['pms_billing_first_name'] : '' ) . ' ';
                $billing_details .= ( ! empty( $invoice_billing_details['pms_billing_last_name'] ) ? $invoice_billing_details['pms_billing_last_name'] : '' );

            }

            // Complete address string
            $billing_address = '';

            // Address
            if( ! empty( $invoice_billing_details['pms_billing_address'] ) ) {

                $billing_address .= $invoice_billing_details['pms_billing_address'];

            }

            // Zip code
            if( ! empty( $invoice_billing_details['pms_billing_zip'] ) ) {

                $billing_address .= ', ' . $invoice_billing_details['pms_billing_zip'];

            }

            // City
            if( ! empty( $invoice_billing_details['pms_billing_city'] ) ) {

                $billing_address .= ', ' . $invoice_billing_details['pms_billing_city'];

            }

            // Complete billing address string with new line
            $billing_details .= PHP_EOL . PHP_EOL . $billing_address;

            // Billing country
            if( ! empty( $invoice_billing_details['pms_billing_country'] ) ) {

                $countries = pms_get_countries();

                if( ! empty( $countries[$invoice_billing_details['pms_billing_country']] ) )
                    $billing_details .= PHP_EOL . PHP_EOL . $countries[$invoice_billing_details['pms_billing_country']];

            }

            $vat_number = pms_get_payment_meta( $payment->id, 'pms_vat_number', true );

            if( !empty( $vat_number ) ){

                $billing_details .= PHP_EOL . PHP_EOL . __( 'VAT Number: ', 'paid-member-subscriptions' ) . $vat_number;

            }

            // Email
            $billing_details .= PHP_EOL . PHP_EOL . ( ! empty( $invoice_billing_details['pms_billing_email'] ) ? __( 'E-mail: ', 'paid-member-subscriptions' ) . $invoice_billing_details['pms_billing_email'] : '' );

            // Autop it
            $billing_details = wpautop( $billing_details );

        }


        /**
         * Start building the PDF invoice
         *
         */
        $font = apply_filters( 'pms_inv_invoice_template_tax_font_family', $settings['font'] );

        $pdf_invoice->SetMargins( 8, 8, 8 );
        $pdf_invoice->SetX( 8 );

        $pdf_invoice->AddPage();


       	// Page title
        $pdf_invoice->SetFont( $font, '', 22 );
        $pdf_invoice->Cell( 0, 0, ( ! empty( $settings['title'] ) ? pms_in_inv_parse_payment_invoice_tags( $settings['title'], $payment ) : __( 'Invoice', 'paid-member-subscriptions' ) ), 0, 2, 'L', false );

        //Logo
        $logo = get_attached_file( $settings['logo'] );


        if( !empty( $logo ) ){
            $pdf_invoice->Image( $logo, 10, 10, 40, 40, '', '', 'T', false, 300, 'R', false, false, 0, true, false, false);
            $pdf_invoice->Ln( 7 );
        }

        $pdf_invoice->Ln( 7 );

        // Set default font
        $pdf_invoice->SetFont( $font, '', 10 );

        // Invoice number
        $pdf_invoice->Cell( 0, 6, sprintf( __( 'Invoice number: %s', 'paid-member-subscriptions' ), $invoice_number ), 0, 2, 'L', false );

        // Payment ID
        $pdf_invoice->Cell( 0, 6, sprintf( __( 'Payment ID: %s', 'paid-member-subscriptions' ), $payment->id ), 0, 2, 'L', false );

        // Payment date
        $pdf_invoice->Cell( 0, 6, sprintf( __( 'Payment date: %s', 'paid-member-subscriptions' ), date( 'Y-m-d', strtotime( $payment->date ) ) ), 0, 2, 'L', false );

        // Payment status
        $pdf_invoice->Cell( 0, 6, sprintf( __( 'Payment status: %s', 'paid-member-subscriptions' ), $payment_statuses[ $payment->status ] ), 0, 2, 'L', false );

        // Payment gateway
        if( ! empty( $payment_gateway ) )
            $pdf_invoice->Cell( 0, 6, sprintf( __( 'Payment gateway: %s', 'paid-member-subscriptions' ), $payment_gateway ), 0, 2, 'L', false );

        $pdf_invoice->Ln( 10 );

        // Add a line
        $pdf_invoice->Line( 8, $pdf_invoice->getY(), $pdf_invoice->getPageWidth() - 8, $pdf_invoice->getY() );

        $pdf_invoice->Ln( 6 );

        // Set font for the headings
        $pdf_invoice->SetFont( $font, 'B', 12 );

        // Set Provided By Heading
        $provider_heading_y = $pdf_invoice->getY();
        $pdf_invoice->Cell( ( $pdf_invoice->getPageWidth() - 16 ) / 2, 12, __( 'Provided by:', 'paid-member-subscriptions' ), 0, 2, 'L', false );

        // Set Provided To Heading
        $pdf_invoice->SetXY( $pdf_invoice->getPageWidth() / 2, $provider_heading_y );
        $pdf_invoice->Cell( ( $pdf_invoice->getPageWidth() - 16 ) / 2, 12, __( 'Provided to:', 'paid-member-subscriptions' ), 0, 2, 'R', false );

        // Reset X
        $pdf_invoice->SetX( 8 );

        // Set font
        $pdf_invoice->SetFont( $font, '', 10 );

        // Add Provided By details
        $pdf_invoice->writeHTMLCell( ( $pdf_invoice->getPageWidth() - 16 ) / 2 , 50, '', '', $company_details );

        // Add Provided To details
        $pdf_invoice->writeHTMLCell( ( $pdf_invoice->getPageWidth() - 16 ) / 2 , 50, '', '', $billing_details, 0, 2, false, true, 'R' );

        // Add a line and some space
        $pdf_invoice->Line( 8, $pdf_invoice->getY(), $pdf_invoice->getPageWidth() - 8, $pdf_invoice->getY() );
        $pdf_invoice->Ln( 10 );

        /**
         * The subscription heading
         *
         */
        $subscription_heading_y = $pdf_invoice->getY();

        // Set default font
        $pdf_invoice->SetFont( $font, 'B', 10 );

        // Reset position
        $pdf_invoice->SetXY( 8, $subscription_heading_y );

        // Payment subscription plan name heading
        $pdf_invoice->Cell( 0, 6, __( 'Subscription Plan', 'paid-member-subscriptions' ), 0, 2, 'L', false );

        // Reset position to
        $pdf_invoice->SetXY( ( $pdf_invoice->getPageWidth() - $pdf_invoice->getPageWidth() / 4 - 16 ), $subscription_heading_y );

        // Payment amount
        $pdf_invoice->Cell( 0, 6, sprintf( __( 'Amount (%s)', 'paid-member-subscriptions' ), pms_get_active_currency() ), 0, 2, 'R', false );


        /**
         * The subscription name and payment amount
         *
         */

        $tax_breakdown = pms_in_tax_determine_tax_breakdown( $payment->id );

        // Set default font
        $pdf_invoice->SetFont( $font, '', 10 );

        // Add a line
        $pdf_invoice->Ln( 1 );
        $pdf_invoice->Line( 8, $pdf_invoice->getY(), $pdf_invoice->getPageWidth() - 8, $pdf_invoice->getY() );
        $pdf_invoice->Ln( 1 );

        // Reset position
        $subscription_y = $pdf_invoice->getY();
        $pdf_invoice->SetXY( 8, $subscription_y );

        // Payment subscription plan name heading
        $pdf_invoice->Cell( 0, 6, $subscription_plan->name, 0, 2, 'L', false );

        // Reset position to
        $pdf_invoice->SetXY( ( $pdf_invoice->getPageWidth() - $pdf_invoice->getPageWidth() / 4 - 16 ), $subscription_y );

        // Payment amount
        if( !empty( $tax_breakdown ) && isset( $tax_breakdown['subtotal'] ) )
            $pdf_invoice->writeHTMLCell( 0, 6, '', '', pms_format_price( $tax_breakdown['subtotal'] ), 0, 2, 0, false, 'R' );
        else
            $pdf_invoice->writeHTMLCell( 0, 6, '', '', pms_format_price( $payment->amount ), 0, 2, 0, false, 'R' );

        // Add a line
        $pdf_invoice->Ln( 1 );
        $pdf_invoice->Line( 8, $pdf_invoice->getY(), $pdf_invoice->getPageWidth() - 8, $pdf_invoice->getY() );
        $pdf_invoice->Ln( 1 );

        // Tax Amount
        if( !empty( $tax_breakdown ) && isset( $tax_breakdown['rate'] ) ){
            $pdf_invoice->writeHTMLCell( 0, 6, '', '', sprintf( __( '%s%% %s: %s', 'paid-member-subscriptions' ), $tax_breakdown['rate'], $this->get_tax_name( $payment->id ), pms_format_price( $tax_breakdown['amount'] ) ), 0, 2, 0, true, 'R' );
            $pdf_invoice->Ln( 0 );
        } else if ( apply_filters( 'pms_tax_show_zero_tax_rate_on_invoice', true ) ) {
            $pdf_invoice->writeHTMLCell( 0, 6, '', '', sprintf( __( '%s%% %s: %s', 'paid-member-subscriptions' ), 0, $this->get_tax_name( $payment->id ), pms_format_price( 0 ) ), 0, 2, 0, true, 'R' );
            $pdf_invoice->Ln( 0 );
        }

        // Total
        $pdf_invoice->writeHTMLCell( 0, 6, '', '', sprintf( __( 'Total (%s): %s', 'paid-member-subscriptions' ), pms_get_active_currency(), pms_format_price( $payment->amount ) ), 0, 0, 0, true, 'R' );

    }

    /**
     * Given a payment id, determines the name of the tax applied to that payment
     */
    public function get_tax_name( $payment_id ){

        if( empty( $payment_id ) )
	        return __('TAX/VAT', 'paid-member-subscriptions');

        $tax_country = pms_get_payment_meta( $payment_id, 'pms_tax_country', true );
        $tax_city    = pms_get_payment_meta( $payment_id, 'pms_tax_city', true );
        $tax_state   = pms_get_payment_meta( $payment_id, 'pms_tax_state', true );

        $tax = $this->determine_tax_rate( $tax_country, $tax_state, $tax_city, true );

        if( !empty( $tax['tax_name'] ) )
            return $tax['tax_name'];

	    return __('TAX/VAT', 'paid-member-subscriptions');

    }

    public function load_frontend_message(){

        add_filter( 'pms_output_subscription_plans', array( $this, 'display_frontend_message' ), 26 );

    }

    public function display_frontend_message( $output ){

        $output .= '<div class="pms-tax-notice"><p>'. __( '* Taxes might be applied at the end of the checkout.', 'paid-member-subscriptions' ) .'</p></div>';

        return $output;

    }

}

global $pms_tax;
$pms_tax = new PMS_IN_Tax;
