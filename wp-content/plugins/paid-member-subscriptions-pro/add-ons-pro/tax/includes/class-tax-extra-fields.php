<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

Class PMS_IN_Tax_Extra_Fields {

    public function init(){

        // register sections
        add_filter( 'pms_extra_form_sections',             array( $this, 'register_form_section' ), 60, 2 );

        // register fields
        add_filter( 'pms_extra_form_fields',               array( $this, 'register_extra_form_fields' ), 60, 2 );

        // output  custom VAT number field
        add_action( 'pms_output_form_field_inner_text_vat-number', array( $this, 'output_form_field_inner_text_vat_number' ), 20, 2 );

        // validate fields
        add_action( 'pms_process_checkout_validations',    array( $this, 'validate_extra_form_fields') );
        add_action( 'pms_register_form_validation',        array( $this, 'validate_extra_form_fields') );
        add_action( 'pms_edit_profile_form_validation',    array( $this, 'validate_extra_form_fields') );

        // save fields
        add_action( 'pms_register_payment',                array( $this, 'save_extra_form_fields' ), 10, 1 );
        add_action( 'pms_edit_profile_form_update_user',   array( $this, 'save_extra_form_fields' ), 10, 1 );
        add_action( 'pms_payment_method_updated',          array( $this, 'save_extra_form_fields' ), 10, 1 );

        // validate vat number through AJAX
        add_action( 'wp_ajax_pms_tax_validate_vat',        array( $this, 'ajax_validate_vat' ) );
        add_action( 'wp_ajax_nopriv_pms_tax_validate_vat', array( $this, 'ajax_validate_vat' ) );

        // Add extra fields to payment when completed
        add_action( 'pms_payment_insert',                  array( $this, 'copy_billing_fields' ), 100, 2 );
        add_action( 'pms_payment_update',                  array( $this, 'copy_billing_fields' ), 100, 2 );

    }

    public function register_form_section( $sections = array(), $form_location = '' ){

        // Add a new field section that will contain the "Billing Details" fields
        $sections[] = array(
            'name'    => 'billing_details',
            'element' => 'ul',
            'class'	  => 'pms-section-billing-details pms-billing-details'
        );

        return $sections;

    }

    public function register_extra_form_fields( $fields = array(), $form_location = '' ){

        $tax_fields      = $this->get_tax_extra_fields();
        $tax_fields_keys = array_keys( $tax_fields );

        $new_fields = array();

        foreach( $fields as $field_key => $field ) {

            // Add each field again
            $new_fields[$field_key] = $field;

            // If the field is also in the tax fields, merge the values
            if( in_array( $field_key, $tax_fields_keys ) )
                $new_fields[$field_key] = array_merge( $field, $tax_fields[$field_key] );

        }

        $new_fields_keys = array_keys( $new_fields );

        // If there are other tax fields add them at the end
        foreach( $tax_fields as $tax_field_key => $tax_field ) {

            if( ! in_array( $tax_field_key, $new_fields_keys ) )
                $new_fields[$tax_field_key] = $tax_field;

        }

        return $new_fields;

    }

    public function validate_extra_form_fields(){

        /**
         * For checkout purposes, if the subscription is free, we don't need to validate the tax
         * fields, as they are not needed
         *
         */
        if( current_filter() !== 'pms_edit_profile_form_validation' ) {

            if( empty( $_POST['pay_gate'] ) )
                return;

        }

        /**
         * If a full discount is applied, meaning users won't need to pay, we don't need to validate the tax
         * fields, as they are not needed
         *
         */
        if ( current_filter() == 'pms_process_checkout_validations' ) {

            if ( !empty($_POST['discount_code']) ) {

                $code = sanitize_text_field( $_POST['discount_code'] );

                // Make sure the code is added by our Discount Codes add-on
                if ( function_exists('pms_in_get_discount_by_code') && pms_in_get_discount_by_code($code) ) {

                    // User checked auto-renew checkbox on checkout
                    $user_checked_auto_renew = (!empty($_POST['recurring']) ? true : false);

                    // Get selected subscription plan id
                    $subscription_plan_id = (!empty($_POST['subscription_plans']) ? (int)$_POST['subscription_plans'] : 0);

                    // Check if is full discount applied
                    $is_full_discount = pms_in_dc_check_is_full_discount($code, $subscription_plan_id, $user_checked_auto_renew);

                    if ( $is_full_discount )
                        return;

                }
            }
        }

        /**
         * Validate the billing fields for the tax
         *
         */
        $tax_fields = $this->get_tax_extra_fields();

        foreach ( $tax_fields as $field ) {

            if ( empty( $field['required'] ) )
                continue;

            if ( empty( $_POST[$field['name']] ) ){

                $existing_error = pms_errors()->get_error_messages( $field['name'] );

                if( empty( $existing_error ) )
                    pms_errors()->add( $field['name'], __( 'This field is required.', 'paid-member-subscriptions' ) );

            }

        }

        // Validate VAT Number if enabled and present
        if( pms_in_tax_eu_vat_enabled() && !empty( $_POST['pms_billing_country'] ) && !empty( $_POST['pms_vat_number'] ) )
            $this->validate_vat_number( sanitize_text_field( $_POST['pms_billing_country'] ), sanitize_text_field( $_POST['pms_vat_number'] ) );


    }

    public function save_extra_form_fields( $data ){

        $hook 		= current_action();
        $user_id 	= 0;
        $payment_id = 0;

        switch ( $hook ) {

            case 'pms_register_payment' :
                // $data stores payment gateway data
                $user_id 	= $data['user_id'];
                $payment_id = !empty( $data['payment_id'] ) ? $data['payment_id'] : 0;
                break;

            case 'pms_edit_profile_form_update_user' :
                // $data stores user information
                $user_id = $data;
                break;

            case 'pms_payment_method_updated' :
                // $data stores user information
                $user_id = $data->user_id;
                break;

        }


        if( ! empty( $user_id ) ) {

            $tax_fields = $this->get_tax_extra_fields();

            // Update payment meta and user meta
            foreach ( $tax_fields as $field ) {

                // Exclude fields without a name
                if( empty( $field['name'] ) )
                    continue;

                if( empty( $_POST[ $field['name'] ] ) )
                    continue;

                // Save billing details in usermeta
                update_user_meta( $user_id, $field['name'], sanitize_text_field( $_POST[$field['name']] ) );

                // Save details in payment meta
                if( function_exists( 'pms_add_payment_meta' ) && ( ! empty( $payment_id ) ) )
                    pms_add_payment_meta( $payment_id, $field['name'], sanitize_text_field( $_POST[$field['name']] ), true );

            }

        }

    }

    public function get_tax_extra_fields(){

        $tax_fields = array();
        $user_id    = 0;
        $user_meta  = array();
        $settings   = get_option( 'pms_tax_settings', array() );

        if( is_user_logged_in() )
            $user_id = pms_get_current_user_id();

        if( ! empty( $user_id ) ) {
            $user      = get_userdata( $user_id );
            $user_meta = get_user_meta( $user_id );
        }

        $tax_fields['pms_billing_details_heading'] = array(
            'section'         => 'billing_details',
            'type'            => 'heading',
            'default'         => '<h3>' . __( 'Billing Details', 'paid-member-subscriptions' ) . '</h3>',
            'element_wrapper' => 'li',
        );

        $tax_fields['pms_billing_address'] = array(
            'section'         => 'billing_details',
            'type'            => 'text',
            'name'            => 'pms_billing_address',
            'default'         => '',
            'value'           => ( isset( $_POST['pms_billing_address'] ) ? $_POST['pms_billing_address'] : ( !(empty($user_meta['pms_billing_address'])) ? $user_meta['pms_billing_address'][0] : '') ), /* phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized */ /* escaped on output */
            'label'           => __( 'Billing Address', 'paid-member-subscriptions' ),
            'description'     => '',
            'element_wrapper' => 'li',
            'required'        => 1,
            'wrapper_class'   => 'pms-billing-address',
        );

        $tax_fields['pms_billing_city'] = array(
            'section'         => 'billing_details',
            'type'            => 'text',
            'name'            => 'pms_billing_city',
            'default'         => '',
            'value'           => ( isset( $_POST['pms_billing_city'] ) ? $_POST['pms_billing_city'] : ( !(empty($user_meta['pms_billing_city'])) ? $user_meta['pms_billing_city'][0] : '') ), /* phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized */ /* escaped on output */
            'label'           => __( 'Billing City', 'paid-member-subscriptions' ),
            'description'     => '',
            'element_wrapper' => 'li',
            'wrapper_class'   => 'pms-billing-city',
        );

        $tax_fields['pms_billing_zip'] = array(
            'section'         => 'billing_details',
            'type'            => 'text',
            'name'            => 'pms_billing_zip',
            'default'         => '',
            'value'           => ( isset( $_POST['pms_billing_zip']) ? $_POST['pms_billing_zip'] : ( !(empty($user_meta['pms_billing_zip'])) ? $user_meta['pms_billing_zip'][0] : '') ), /* phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized */ /* escaped on output */
            'label'           => __( 'Billing Zip / Postal Code', 'paid-member-subscriptions' ),
            'description'     => '',
            'element_wrapper' => 'li',
            'wrapper_class'   => 'pms-billing-zip',
        );

        $tax_fields['pms_billing_country'] = array(
            'section'         => 'billing_details',
            'type'            => 'select',
            'name'            => 'pms_billing_country',
            'default'         => !empty( $settings['default-billing-country'] ) ? $settings['default-billing-country'] : '',
            'value'           => ( isset( $_POST['pms_billing_country'] ) ? $_POST['pms_billing_country'] : ( !(empty($user_meta['pms_billing_country'])) ? $user_meta['pms_billing_country'][0] : '') ), /* phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized */ /* escaped on output */
            'label'           => __( 'Billing Country', 'paid-member-subscriptions' ),
            'options'         => function_exists( 'pms_get_countries' ) ? pms_get_countries() : array(),
            'description'     => '',
            'element_wrapper' => 'li',
            'required'        => 1,
            'wrapper_class'   => 'pms-billing-country',
        );

        $tax_fields['pms_billing_state'] = array(
            'section'         => 'billing_details',
            'type'            => 'select_state',
            'name'            => 'pms_billing_state',
            'default'         => '',
            'value'           => ( isset( $_POST['pms_billing_state'] ) ? $_POST['pms_billing_state'] : ( !(empty($user_meta['pms_billing_state'])) ? $user_meta['pms_billing_state'][0] : '') ), /* phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized */ /* escaped on output */
            'label'           => __( 'Billing State / Province', 'paid-member-subscriptions' ),
            'description'     => '',
            'element_wrapper' => 'li',
            'wrapper_class'   => 'pms-billing-state',
        );

        if( pms_in_tax_eu_vat_enabled() ){
            $tax_fields['pms_vat_number'] = array(
                'section'         => 'billing_details',
                'type'            => 'text_vat-number',
                'name'            => 'pms_vat_number',
                'default'         => '',
                'value'           => ( isset( $_POST['pms_vat_number'] ) ? $_POST['pms_vat_number'] : ( !(empty($user_meta['pms_vat_number'])) ? $user_meta['pms_vat_number'][0] : '') ), /* phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized */ /* escaped on output */
                'label'           => __( 'EU VAT Number', 'paid-member-subscriptions' ),
                'description'     => '',
                'element_wrapper' => 'li',
                'wrapper_class'   => 'pms-vat-number',
            );
        }

        /**
         * Filter the tax fields
         *
         * @param array $tax_fields
         *
         */
        return apply_filters( 'pms_get_tax_extra_fields', $tax_fields );

    }

    public function output_form_field_inner_text_vat_number( $field = array() ) {

        if( $field['type'] != 'text_vat-number' )
            return;

        if( empty( $field['name'] ) )
            return;

        // Set value
        $value = ( !empty( $field['value'] ) ? $field['value'] : ( !empty( $field['default'] ) ? $field['default'] : '' ) );

        $extra_attributes = '';

        // Re-validate VAT Number if already supplied
        if( !empty( $value ) && is_user_logged_in() ){
            $billing_country = get_user_meta( pms_get_current_user_id(), 'pms_billing_country', true );

            if( !empty( $billing_country ) ){
                $response = $this->validate_vat_number( $billing_country, $value );

                if( isset( $response['valid'] ) && $response['valid'] == 'true' ){
                    $extra_attributes .= 'data-vat-valid="'.esc_attr( true ).'"';
                }

            }            
        }

        // Field output
        $output = '<input type="text" id="' . esc_attr( $field['name'] ) . '" name="' . esc_attr( $field['name'] ) . '" value="' . esc_attr( $value ) . '" '. $extra_attributes .'/>';

        echo $output; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

    }

    public function ajax_validate_vat(){

        if( empty( $_POST['vatNumber'] ) || empty( $_POST['vatCountry'] ) )
            die();

        $minimum_characters_array = pms_in_tax_get_vat_numbers_minimum_characters();
        $minimum_characters       = !empty( $minimum_characters_array[ sanitize_text_field( $_POST['vatCountry'] ) ] ) ? $minimum_characters_array[ sanitize_text_field( $_POST['vatCountry' ] ) ] : $minimum_characters_array['default'];

        if( strlen( sanitize_text_field( $_POST['vatNumber'] ) ) < $minimum_characters )
            die();

        $response = $this->validate_vat_number( sanitize_text_field( $_POST['vatCountry'] ), sanitize_text_field( $_POST['vatNumber'] ) );

        if( isset( $response['valid'] ) && $response['valid'] == 'true' ){
            echo json_encode( array( 'status' => 'valid' ) );

            die();
        }

        echo json_encode( array( 'status' => 'invalid' ) );
        die();

    }

    public function validate_vat_number( $countryCode, $vatNumber ){

        if( $transient = get_transient( 'pms_tax_' . $countryCode . '_' . $vatNumber ) )
            return $transient;

        if( !class_exists( 'SoapClient' ) ){

            include_once( PMS_IN_TAX_PLUGIN_DIR_PATH . '/libs/nusoap/nusoap.php' );

            $client = new PMS\nusoap_client( 'https://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl', 'wsdl' );

            if( function_exists( 'curl_exec' ) ) $client->setUseCurl( true );

            if( !$client->getError() ){

                try {
                    $response = $client->call( 'checkVat', array(
                        'countryCode' => $countryCode,
                        'vatNumber'   => $this->parse_vat_number( $vatNumber, $countryCode ),
                    ) );

                    if( isset( $response['valid'] ) ){

                        // Only relevant data
                        $data = array(
                            'countryCode' => $response['countryCode'],
                            'vatNumber'   => $response['vatNumber'],
                            'requestDate' => $response['requestDate'],
                            'valid'       => $response['valid'],
                        );

                        // Save response for a day
                        set_transient( 'pms_tax_' . $countryCode . '_' . $vatNumber, $data, DAY_IN_SECONDS );

                        return $data;

                    }

                } catch (Exception $e){

                    return false;

                }

                return false;

            }

        } else {

            try {

                $client = new SoapClient("https://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl",array(
                    'exceptions' => true,
                ));

            } catch ( SoapFault $e ) {

                $client = null;

            }

            if( !is_null( $client ) ){

                try {

                    $response = $client->checkVat( array(
                        'countryCode' => $countryCode,
                        'vatNumber'   => $this->parse_vat_number( $vatNumber, $countryCode ),
                    ) );

                    if( $response->valid === true ){

                        // Only relevant data
                        $data = array(
                            'countryCode' => $response->countryCode,
                            'vatNumber'   => $response->vatNumber,
                            'requestDate' => $response->requestDate,
                            'valid'       => $response->valid,
                        );

                        // Save response for a day
                        set_transient( 'pms_tax_' . $countryCode . '_' . $vatNumber, $data, DAY_IN_SECONDS );

                        return $data;

                    }

                } catch (Exception $e){

                    return false;

                }

            }

        }

        // @TODO: register some debug data to the payment since the VAT number is not mandatory, the checkout needs to continue
        return false;

    }

    private function parse_vat_number( $vat_number, $country ){

        // remove extra characters
        $vat_number = strtoupper( str_replace( array(' ', '-', '_', '.'), '', $vat_number ) );

        // remove first 2 characters if they match a country code
        if( in_array( substr( $vat_number, 0, 2 ), array_merge( pms_in_tax_get_eu_vat_countries_slugs(), array( 'EL', 'GB' ) ) ) )
            $vat_number = substr( $vat_number, 2 );

        // for Belgium numbers, if they have 9 characters, prepend a 0
        if( $country == 'BE' && strlen( $vat_number ) == 9 )
            $vat_number = '0' . $vat_number;

        return $vat_number;

    }

    public function copy_billing_fields( $payment_id, $data ){

        if( empty( $payment_id) )
            return;

        if( empty( $data ) || !isset( $data['status'] ) )
            return;

        if( $data['status'] != 'completed' )
            return;

        $payment = pms_get_payment( $payment_id );

        if( is_null( $payment ) )
            return;

        $fields                  = $this->get_tax_extra_fields();
        $fields_user_meta_values = array();

        foreach( $fields as $field ){

            if( empty( $field['name'] ) )
                continue;

            $existing_meta = pms_get_payment_meta( $payment_id, $field['name'], true );

            if( !empty( $existing_meta ) )
                continue;

            $fields_user_meta_values[$field['name']] = get_user_meta( $payment->user_id, $field['name'], true );

        }

        $fields_user_meta_values = array_filter( $fields_user_meta_values );

        if( !empty( $fields_user_meta_values ) ) {

            foreach( $fields_user_meta_values as $name => $value )
                pms_add_payment_meta( $payment_id, $name, $value, true );

        }

    }

}

$pms_tax_extra_fields = new PMS_IN_Tax_Extra_Fields;
$pms_tax_extra_fields->init();
