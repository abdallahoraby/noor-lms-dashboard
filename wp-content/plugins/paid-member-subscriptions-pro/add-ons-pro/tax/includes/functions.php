<?php

/**
 * Return tax rates filterable by an array of arguments
 *
 * @param array $args
 *
 * @return array
 *
 */
function pms_in_tax_get_rates( $args = array() ){

    global $wpdb;

    $defaults = array(
        'tax_country' => '',
        'tax_state'   => '',
        'tax_name'    => ''
    );

    $args = apply_filters( 'pms_get_tax_rates_args', wp_parse_args( $args, $defaults ), $args, $defaults );

    // Start query string
    $query_string = "SELECT * ";

    // Query string sections
    $query_from  = "FROM {$wpdb->prefix}pms_tax_rates pms_tax_rates ";
    $query_where = "WHERE 1=%d ";

    // Filter by Country code
    if( !empty( $args['tax_country'] ) ) {
        $country     = sanitize_text_field( $args['tax_country'] );
        $query_where = $query_where . " AND " . " pms_tax_rates.tax_country LIKE '{$country}'";
    }

    // Filter by State code
    if( !empty( $args['tax_state'] ) ) {
        $state       = sanitize_text_field( $args['tax_state'] );
        $query_where = $query_where . " AND " . " pms_tax_rates.tax_state LIKE '{$state}'";
    }

    // Filter by City code
    if( !empty( $args['tax_city'] ) ) {
        $city        = sanitize_text_field( $args['tax_city'] );
        $query_where = $query_where . " AND " . " pms_tax_rates.tax_city LIKE '{$city}'";
    }

    // Filter by Tax name
    if( !empty( $args['tax_name'] ) ) {
        $name        = sanitize_text_field( $args['tax_name'] );
        $query_where = $query_where . " AND " . " pms_tax_rates.tax_name LIKE '{$name}'";
    }

    // Concatenate query string
    $query_string .= $query_from . $query_where;

    // Get results
    $tax_rates = $wpdb->get_results( $wpdb->prepare( $query_string, 1 ), ARRAY_A );

    return $tax_rates;

}

/**
 * Return default tax rate saved in settings
 */
function pms_in_tax_get_default_rate(){

    $tax_settings = get_option( 'pms_tax_settings', array() );
    $default_rate = 0;

    if( !empty( $tax_settings['default_tax_rate'] ) )
        $default_rate = $tax_settings['default_tax_rate'];

    return $default_rate;

}

function pms_in_tax_prices_include_tax(){

    $settings = get_option( 'pms_tax_settings', array() );

    if( !empty( $settings ) && !empty( $settings['prices_include_tax'] ) && $settings['prices_include_tax'] == 'yes' )
        return true;

    return false;

}

function pms_in_tax_enabled(){

    $settings = get_option( 'pms_tax_settings', array() );

    if( !empty( $settings ) && !empty( $settings['enable_tax'] ) && $settings['enable_tax'] == 1 )
        return true;

    return false;

}

function pms_in_tax_eu_vat_enabled(){

    $settings = get_option( 'pms_tax_settings', array() );

    if( !empty( $settings ) && !empty( $settings['eu-vat-enable'] ) && $settings['eu-vat-enable'] == 1 )
        return true;

    return false;

}

function pms_in_tax_get_eu_vat_countries( $only_names = false ){

    $countries = array(
        'AT' => array( 'name' => __('Austria', 'paid-member-subscriptions'),        'rate' => 20, ),
        'BE' => array( 'name' => __('Belgium', 'paid-member-subscriptions'),        'rate' => 21, ),
        'BG' => array( 'name' => __('Bulgaria', 'paid-member-subscriptions'),       'rate' => 20, ),
        'CY' => array( 'name' => __('Cyprus', 'paid-member-subscriptions'),         'rate' => 19, ),
        'CZ' => array( 'name' => __('Czech Republic', 'paid-member-subscriptions'), 'rate' => 21, ),
        'DE' => array( 'name' => __('Germany', 'paid-member-subscriptions'),        'rate' => 19, ),
        'DK' => array( 'name' => __('Denmark', 'paid-member-subscriptions'),        'rate' => 25, ),
        'EE' => array( 'name' => __('Estonia', 'paid-member-subscriptions'),        'rate' => 22, ),
        'GR' => array( 'name' => __('Greece', 'paid-member-subscriptions'),         'rate' => 24, ),
        'ES' => array( 'name' => __('Spain', 'paid-member-subscriptions'),          'rate' => 21, ),
        'FI' => array( 'name' => __('Finland', 'paid-member-subscriptions'),        'rate' => 25.5, ),
        'FR' => array( 'name' => __('France', 'paid-member-subscriptions'),         'rate' => 20, ),
        'HR' => array( 'name' => __('Croatia', 'paid-member-subscriptions'),        'rate' => 25, ),
        // 'GB' => array( 'name' => __('United Kingdom', 'paid-member-subscriptions'), 'rate' => 20, ),
        'HU' => array( 'name' => __('Hungary', 'paid-member-subscriptions'),        'rate' => 27, ),
        'IE' => array( 'name' => __('Ireland', 'paid-member-subscriptions'),        'rate' => 23, ),
        'IT' => array( 'name' => __('Italy', 'paid-member-subscriptions'),          'rate' => 22, ),
        'LT' => array( 'name' => __('Lithuania', 'paid-member-subscriptions'),      'rate' => 21, ),
        'LU' => array( 'name' => __('Luxembourg', 'paid-member-subscriptions'),     'rate' => 17, ),
        'LV' => array( 'name' => __('Latvia', 'paid-member-subscriptions'),         'rate' => 21, ),
        'MT' => array( 'name' => __('Malta', 'paid-member-subscriptions'),          'rate' => 18, ),
        'NL' => array( 'name' => __('Netherlands', 'paid-member-subscriptions'),    'rate' => 21, ),
        // 'NO' => array( 'name' => __('Norway', 'paid-member-subscriptions'),         'rate' => 25, ),
        'PL' => array( 'name' => __('Poland', 'paid-member-subscriptions'),         'rate' => 23, ),
        'PT' => array( 'name' => __('Portugal', 'paid-member-subscriptions'),       'rate' => 23, ),
        'RO' => array( 'name' => __('Romania', 'paid-member-subscriptions'),        'rate' => 19, ),
        'SE' => array( 'name' => __('Sweden', 'paid-member-subscriptions'),         'rate' => 25, ),
        'CH' => array( 'name' => __('Switzerland', 'paid-member-subscriptions'),    'rate' => 8.1, ),
        'SI' => array( 'name' => __('Slovenia', 'paid-member-subscriptions'),       'rate' => 22, ),
        'SK' => array( 'name' => __('Slovakia', 'paid-member-subscriptions'),       'rate' => 20, )
    );


    if( $only_names === true ){
        foreach( $countries as $key => $value )
            $countries[$key] = $value['name'];
    }

    return $countries;

}

function pms_in_tax_get_eu_vat_countries_slugs(){

    $countries = pms_in_tax_get_eu_vat_countries();
    $slugs     = array();

    foreach( $countries as $key => $value )
        $slugs[] = $key;

    return $slugs;

}

function pms_in_tax_get_vat_numbers_minimum_characters(){

    // https://www.gov.uk/vat-eu-country-codes-vat-numbers-and-vat-in-other-languages
    return array(
        'RO'      => 2,
        'CZ'      => 8,
        'DK'      => 8,
        'FI'      => 8,
        'HU'      => 8,
        'MT'      => 8,
        'LU'      => 8,
        'SI'      => 8,
        'IE'      => 8,
        'PL'      => 10,
        'SK'      => 10,
        'HR'      => 11,
        'FR'      => 11,
        'MC'      => 11,
        'IT'      => 11,
        'LV'      => 11,
        'NL'      => 12,
        'SE'      => 12,
        'CH'      => 6,
        'default' => 9
    );

}

function pms_in_tax_get_merchant_country(){

    $settings = get_option( 'pms_tax_settings', array() );

    if( !empty( $settings ) && !empty( $settings['merchant-vat-country'] ) )
        return $settings['merchant-vat-country'];

    return false;

}

function pms_in_tax_determine_tax_breakdown( $payment_id ){

    if( empty( $payment_id ) )
        return;

    $payment = pms_get_payment( $payment_id );

    $recurring_types = array(
        'subscription_recurring_payment',
        'recurring_payment',
        'subscr_payment',
    );

    if( in_array( $payment->type, $recurring_types ) ){

        $payments = pms_get_payments( array( 'order' => 'ASC', 'user_id' => $payment->user_id, 'subscription_plan_id' => $payment->subscription_id ) );

        if( !empty( $payments[0] ) )
            $first_payment = $payments[0];

        $tax_rate   = pms_get_payment_meta( $first_payment->id, 'pms_tax_rate', true );

        if( empty( $tax_rate ) )
            return;

        $tax_amount = $payment->amount - ( $payment->amount / ( $tax_rate / 100 + 1 ) );
        $subtotal   = $payment->amount - $tax_amount;

    } else {

        $tax_rate = pms_get_payment_meta( $payment->id, 'pms_tax_rate', true );

        if( empty( $tax_rate ) )
            return array();

        if( pms_in_tax_prices_include_tax() === true ){

            $tax_amount = $payment->amount - ( $payment->amount / ( $tax_rate / 100 + 1 ) );
            $subtotal   = $payment->amount - $tax_amount;

        } else {

            if( pms_get_payment_meta( $payment->id, 'pms_tax_valid_vat_number', true ) == 'true' && pms_get_payment_meta( $payment->id, 'pms_billing_country', true ) != pms_in_tax_get_merchant_country() ){
                $tax_amount = 0;
                $tax_rate   = 0;
                $subtotal   = $payment->amount;
            } else {
                $subtotal = $payment->amount / ( 1 + $tax_rate / 100 );
                $tax_amount = $subtotal * ( $tax_rate / 100 );
            }

        }

    }

    $data = array(
        'rate'     => $tax_rate,
        'subtotal' => $subtotal,
        'amount'   => $tax_amount,
        'total'    => $payment->amount,
    );

    return apply_filters( 'pms_tax_determine_tax_breakdown', $data, $payment );

}

function pms_in_tax_add_tax_breakdown_to_payment_history_shortcode( $output, $payment ){

    if( empty( $payment->id ) )
        return $output;

    $tax = pms_in_tax_determine_tax_breakdown( $payment->id );

    if( empty( $tax ) )
        return $output;

    $subtotal   = sprintf( esc_html__( 'Subtotal: %s', 'paid-member-subscriptions' ), pms_format_price( $tax['subtotal'], pms_get_active_currency() ) );
    $tax_amount = ( $tax['rate'] != 0 ? $tax['rate'] . '% ' : '' ) . esc_html__( 'TAX/VAT: ', 'paid-member-subscriptions' ) . pms_format_price( $tax['amount'], pms_get_active_currency() );
    $total      = sprintf( esc_html__( 'Total: %s', 'paid-member-subscriptions' ), pms_format_price( $tax['total'], pms_get_active_currency() ) );

    echo "$subtotal\n$tax_amount\n$total"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

}
add_filter( 'pms_payment_history_amount_row_title', 'pms_in_tax_add_tax_breakdown_to_payment_history_shortcode', 20, 2 );
