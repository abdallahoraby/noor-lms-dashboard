<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) )
    return;


/**
 * Add tab for Invoices under PMS Settings page
 *
 * @param array $pms_tabs The PMS Settings tabs
 *
 * @return array
 *
 */
function pms_in_inv_add_invoices_tab( $pms_tabs ) {

    $pms_tabs['invoices'] = __( 'Invoices', 'paid-member-subscriptions' );

    return $pms_tabs;
}
add_filter( 'pms-settings-page_tabs', 'pms_in_inv_add_invoices_tab' );


/**
 * Add content for Invoices tab
 *
 * @param string $output     Tab content
 * @param string $active_tab Current active tab
 * @param array $options     The PMS settings options
 *
 */
function pms_in_inv_add_invoices_tab_content( $output, $active_tab, $options ) {

    if ( $active_tab == 'invoices' ) {
        ob_start();

        include_once 'views/view-settings-tab-invoices.php';

        $output = ob_get_clean();
    }

    return $output;
}
add_action( 'pms_settings_tab_content', 'pms_in_inv_add_invoices_tab_content', 20, 3 );

add_action( 'pms_register_tab_settings', 'pms_in_inv_register_settings' );
function pms_in_inv_register_settings() {
    register_setting( 'pms_invoices_settings', 'pms_invoices_settings', 'pms_in_inv_sanitize_settings' );
}

/**
 * Sanitize PMS Invoices settings
 *
 * @param array $options The PMS settings options
 *
 * @return array
 *
 */
function pms_in_inv_sanitize_settings( $options ) {

    if( !isset( $_REQUEST['option_page'] ) )
        return $options;

    $option_page = sanitize_text_field( $_REQUEST['option_page'] );

    if ( $option_page != 'pms_invoices_settings' ) return $options;

    // Invoice Details
    if( empty( $options['company_details'] ) ) {
        add_settings_error('general', 'invoices_company_details', __('Company Details are required in order to create invoices.', 'paid-member-subscriptions'), 'error');
    }

    if( isset( $options['company_details'] ) ) {
        $options['company_details'] = wp_kses_post( $options['company_details'] );
    }

    if( isset( $options['notes'] ) ) {
        $options['notes'] =  wp_kses_post( $options['notes'] );
    }

    // Invoice Settings
    if( isset( $options['title'] ) ) {

        if ( !empty( $options['title'] ) ) {
            $options['title'] = sanitize_text_field( $options['title'] );
        } else {
            $options['title'] = __( 'Invoice', 'paid-member-subscriptions' );
        }

    }

    if( isset( $options['format'] ) ) {

        // {{number}} tag is required in invoice format
        if ( strpos( $options['format'], '{{number}}' ) === false ) {
            add_settings_error('general', 'invoices_format', __('The {{number}} tag is required under Format.', 'paid-member-subscriptions'), 'error');
            $options['format'] = '{{number}}';
        } else {
            $options['format'] = sanitize_text_field($options['format']);
        }

    }

    // Remove Reset Invoice Counter
    if( isset( $options['reset_invoice_counter'] ) ) {
        if( isset( $_POST['pms_inv_invoice_number'] ) )
            update_option( 'pms_inv_invoice_number', (int)$_POST['pms_inv_invoice_number'] );

        unset( $options['reset_invoice_counter'] );
    }

    if( isset( $options['next_invoice_number'] ) ) {
        $options['next_invoice_number'] =  (int)$options['next_invoice_number'];
    }

    if( isset( $options['reset_yearly'] ) ) {
        $options['reset_yearly'] =  (int)$options['reset_yearly'];
    }

    return $options;

}
add_filter( 'pms_sanitize_settings', 'pms_in_inv_sanitize_settings' );

/**
 * Add merge tags for Invoice URL and link
 *
 * @param string $tags     Array with merge tags from plugin
 */
add_filter( 'pms_merge_tags', 'pms_in_inv_add_merge_tags' );
function pms_in_inv_add_merge_tags( $tags ){

    $tags[] = 'invoice_url';
    $tags[] = 'invoice_link';

    return $tags;

}

/**
 * {{invoice_url}} merge tag
 */

add_filter( 'pms_merge_tag_invoice_url', 'pms_in_inv_invoice_url_merge_tag', 10, 4 );
function pms_in_inv_invoice_url_merge_tag( $value, $user_info, $subscription_id, $payment_id ){

    if( empty( $payment_id ) )
        return;

    $payment = pms_get_payment( $payment_id );

    // Get the Invoice access key for this payment
    $payment_key = pms_in_inv_get_url_access_key( $payment );

    $url = add_query_arg(
        array(
            'pms-action' => 'generate_invoice_pdf',
            'payment_id' => $payment->id,
            'pmskey'     => $payment_key,
        ), home_url()
    );

    return $url;

}


/**
 * {{invoice_link}} merge tag
 */
add_filter( 'pms_merge_tag_invoice_link', 'pms_in_inv_invoice_link_merge_tag', 10, 4 );
function pms_in_inv_invoice_link_merge_tag( $value, $user_info, $subscription_id, $payment_id ){

    if( empty( $payment_id ) )
        return;

    $payment = pms_get_payment( $payment_id );

    // Get the Invoice access key for this payment
    $payment_key = pms_in_inv_get_url_access_key( $payment );

    $url = add_query_arg(
        array(
            'pms-action' => 'generate_invoice_pdf',
            'payment_id' => $payment->id,
            'pmskey'     => $payment_key,
        ), home_url()
    );

    return sprintf( '<a href="%s" target="_blank">%s</a>', $url, $url );

}

/**
 * Retrieves (and generates) an Invoices access key that is mailed to an user
 *
 * @param string $payment     Payment object
 */
function pms_in_inv_get_url_access_key( $payment ){

    if( empty( $payment->id ) )
        return '';

    $key = pms_get_payment_meta( $payment->id, 'pms_invoice_access_key', true );

    // If empty, generate a key
    if( empty( $key ) ){

        $data = $payment->id . $payment->user_id . get_site_url() . time();
        $key  = hash_hmac( 'sha256' , $data, $payment->user_id . time() );

        pms_add_payment_meta( $payment->id, 'pms_invoice_access_key', $key );

    }

    return $key;

}
