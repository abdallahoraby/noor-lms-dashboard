<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) )
    return;

/**
 * Add tab for Tax under PMS Settings page
 *
 * @param array $pms_tabs The PMS Settings tabs
 *
 * @return array
 *
 */
function pms_in_tax_add_tax_tab( $pms_tabs ) {

    $pms_tabs['tax'] = __( 'Tax', 'paid-member-subscriptions' );

    return $pms_tabs;
}
add_filter( 'pms-settings-page_tabs', 'pms_in_tax_add_tax_tab' );

/**
 * Add content for Tax tab
 *
 * @param string $output     Tab content
 * @param string $active_tab Current active tab
 * @param array $options The PMS Settings options
 *
 * @return string
 */
function pms_in_tax_add_tax_tab_content( $output, $active_tab, $options ) {

    if ( $active_tab == 'tax' ) {

        $tax_rates = pms_in_tax_get_rates();

        ob_start();

        include_once 'views/view-settings-tab-tax.php';

        $output = ob_get_clean();
    }

    return $output;
}
add_action( 'pms_settings_tab_content', 'pms_in_tax_add_tax_tab_content', 20, 3 );


/**
 *  Register Tax settings and its data
 */
function pms_in_tax_register_settings() {
    register_setting( 'pms_tax_settings', 'pms_tax_settings', 'pms_tax_sanitize_settings' );
}
add_action( 'pms_register_tab_settings', 'pms_in_tax_register_settings' );


/**
 * Process PMS Tax settings
 * Used to handle the Tax Rates CSV file upload and sanitize tax settings
 *
 * @param array $options The PMS settings options
 *
 * @return array
 *
 */
function pms_in_tax_process_settings( $options ) {

    if ( isset($_FILES['pms_tax_rates_csv']) && !empty($_FILES['pms_tax_rates_csv']) &&
         isset($_FILES['pms_tax_rates_csv']['tmp_name']) && !empty($_FILES['pms_tax_rates_csv']['tmp_name']) ) {

        // $_FILES['pms_tax_rates_csv']['tmp_name'] - contains the actual copy of the file on the server (name/location)
        // $_FILES['pms_tax_rates_csv']['name']     - contains the name of the file as uploaded from the computer

        $mapping = array(
            'country code' => 'tax_country',
            'Country Code' => 'tax_country',
            'state code'   => 'tax_state',
            'State Code'   => 'tax_state',
            'rate'         => 'tax_rate',
            'Rate'         => 'tax_rate',
            'Rate %'       => 'tax_rate',
            'tax name'     => 'tax_name',
            'Tax Name'     => 'tax_name',
            'City'         => 'tax_city',
            'city'         => 'tax_city',
        );

        $validations = array(
            'required' => array(
                'tax_country',
                'tax_state',
                'tax_rate',
                'tax_name',
            )
        );

        $tax_rates = pms_in_tax_parse_csv_file( $_FILES['pms_tax_rates_csv']['tmp_name'], $validations, $mapping ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

        pms_in_tax_import_rates($tax_rates);

    }

    return $options;

}
add_filter( 'pms_sanitize_settings', 'pms_in_tax_process_settings' );

/**
 * Parses a CSV file and returns an associative array
 */

function pms_in_tax_parse_csv_file($filepath, $validations=array(), $mappings=array()) {
    $assoc = $headers = array();
    $col_count = 0;
    $row = 1;

    if (($handle = fopen($filepath, 'r')) !== false) {
        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            if($row === 1) {
                foreach($data as $i => $header) {
                    if (!empty($header)) {
                        if (isset($mappings[$header])) {
                            $headers[$i] = $mappings[$header];
                        } else {
                            $headers[$i] = $header;
                        }
                    }
                }

                //Make sure the CSV contains all the required column names (headers)
                foreach($validations['required'] as $required_header) {
                    if(!in_array($required_header,$headers)) {
                        $key = array_search($required_header, $mappings);
                        add_settings_error('tax', 'tax_rates_csv', sprintf(__('Upload failed. Your CSV file must contain the column: %s. Please try again.', 'paid-member-subscriptions'), $key), 'error');
                        return array();
                    }
                }

                $col_count = count($headers);
            }
            else {
                if(!pms_in_tax_csv_row_is_blank($data)) {
                    $new_row = array();
                    for($i=0; $i < $col_count; $i++) {
                        $new_row[$headers[$i]] = $data[$i];
                    }
                    $assoc[] = $new_row;
                }
            }
            $row++;
        }
        fclose($handle);
    }

    return $assoc;
}


/**
 * Checks if a given CSV row is blank
 * @param $row
 * @return bool
 */
function pms_in_tax_csv_row_is_blank( $row ){

    foreach( $row as $i => $cell ) {
        if( !empty($cell) ) {
            return false;
        }
    }
    return true;
}

/**
 * Imports tax rates into the DB
 */
function pms_in_tax_import_rates( $tax_rates ){
    global $wpdb;

    if( !empty($tax_rates) && is_array($tax_rates) ) {

        foreach($tax_rates as $row) {

            $tax_rate_info = array(
                'tax_country' => sanitize_text_field($row['tax_country']),
                'tax_state'   => !empty( $row['tax_state'] ) ? sanitize_text_field( $row['tax_state'] ) : '*',
                'tax_city'    => !empty( $row['tax_city'] ) ? sanitize_text_field( $row['tax_city'] ) : '*',
                'tax_rate'    => (float)sanitize_text_field($row['tax_rate']),
                'tax_name'    => !empty(sanitize_text_field($row['tax_name'])) ? sanitize_text_field($row['tax_name']) : 'TAX',
            );

            if ( !empty($tax_rate_info['tax_country']) && !empty($tax_rate_info['tax_rate']) && is_float($tax_rate_info['tax_rate'])) {

                // Insert tax rates in the db
                $insert_result = $wpdb->insert($wpdb->prefix . 'pms_tax_rates', $tax_rate_info);
            }
        }
    }
}

/**
 * Function that removes a certain rate from the Tax Rates table
 */
function pms_in_tax_delete_row(){

    if( !empty( $_POST['pms_tax_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['pms_tax_nonce'] ), 'pms-tax' ) && !empty( $_POST['id'] ) ) {

        global $wpdb;

        $table_name = $wpdb->prefix . 'pms_tax_rates';

        $wpdb->delete( $table_name, array( 'id' => absint( $_POST['id'] ) ) );

        wp_send_json_success( array('message'=>__('This tax rate was successfully deleted', 'paid-member-subscriptions') ) );
    }

    wp_send_json_error();
}
add_action('wp_ajax_pms_remove_tax_rate', 'pms_in_tax_delete_row');

/**
 * AJAX callback for the Export Tax Rates functionality
 */
function pms_in_tax_export_rates(){

    check_ajax_referer( 'pms_tax_export_rates', 'pms-tax-nonce' );

    $headers_human_format = array(
        'tax_country' => 'Country Code',
        'tax_state'   => 'State Code',
        'tax_city'    => 'City',
        'tax_rate'    => 'Rate %',
        'tax_name'    => 'Tax Name',
    );

    $tax_rates = pms_in_tax_get_rates();
    $headers   = array();

    if( !empty( $tax_rates ) ) {

        foreach( $tax_rates as $key => $value )
            unset( $tax_rates[$key]['id'] );

        foreach( array_keys( $tax_rates[0] ) as $machine_headers )
            $headers[] = $headers_human_format[$machine_headers];

        pms_in_tax_generate_csv( $tax_rates, $headers );

    }

    die();

}
add_action('wp_ajax_pms_export_tax_rates', 'pms_in_tax_export_rates');

/**
 * Generates a .csv file given the data and sends it for donwloading
 */
function pms_in_tax_generate_csv( $rows, $headers ){

    $filename = 'tax-rates_' . date( 'd-m-y' ) . '.csv';

    // download file
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename={$filename}");

    $output = fopen('php://output','w');

    fputcsv( $output,$headers );

    foreach( $rows as $row )
      fputcsv( $output, (array)$row );

    fclose($output);
    exit;

}

/**
 * Function that removes ALL tax rates from the db
 */
function pms_in_tax_delete_all_rates(){

    $action = (isset($_REQUEST['pms-action']) ? sanitize_text_field( $_REQUEST['pms-action'] ) : '');

    if ($action === 'pms_clear_tax_rates') {
        check_admin_referer('pms_clear_tax_rates', 'pms-tax');

        // remove all tax rates from db
        global $wpdb;

        $table_name = $wpdb->prefix . 'pms_tax_rates';

        $wpdb->query(" TRUNCATE TABLE $table_name");

        $url = remove_query_arg( array( 'pms-action', 'pms-tax' ) );
        $url = add_query_arg( 'pms_taxes_cleared', 'true', $url );

        wp_safe_redirect( $url );
        exit;

    }

    if( isset( $_GET['pms_taxes_cleared'] ) && $_GET['pms_taxes_cleared'] == 'true' )
        add_settings_error('tax', 'delete_all_tax_rates', __('All tax rates were successfully deleted.', 'paid-member-subscriptions'), 'updated');

}
add_action('admin_init', 'pms_in_tax_delete_all_rates');

/**
 * Tax breakdown on the Amount column of the Payments list table
 */
function pms_in_tax_display_backend_tax_breakdown( $output, $item ){

    if( pms_in_tax_enabled() !== true )
        return $output;

    if( empty( $item['id'] ) || empty( $item['subscription'] ) )
        return $output;

    $tax = pms_in_tax_determine_tax_breakdown( $item['id'] );

    if( empty( $tax ) || ( !isset( $item['amount'] ) || $item['amount'] == '0' ) )
        return $output;

    ob_start(); ?>

        <span class="pms-has-bubble">

            <?php echo esc_html( pms_format_price( $item['amount'], pms_get_active_currency() ) ); ?>
            <?php if( !empty( $item['discount_code'] ) ) : ?>
                <span class="pms-discount-dot"> % </span>
            <?php endif; ?>

            <div class="pms-bubble">
                <?php if( !empty( $item['discount_code'] ) ) : ?>
                    <div>
                        <span class="alignleft"><?php esc_html_e( 'Discount code', 'paid-member-subscriptions' ); ?></span>
                        <span class="alignright"><?php echo esc_html( $item['discount_code'] ); ?></span>
                    </div><br>
                <?php endif; ?>

                <?php
                    $vat_number = pms_get_payment_meta( $item['id'], 'pms_vat_number', true );

                    if( pms_get_payment_meta( $item['id'], 'pms_tax_valid_vat_number', true ) == 'true' ) : ?>
                        <div>
                            <span class="alignleft"><?php esc_html_e( 'VAT Number', 'paid-member-subscriptions' ); ?></span>
                            <span class="alignright"><?php echo esc_html( $vat_number ); ?></span>
                        </div><br>
                <?php endif; ?>

                <div>
                    <span class="alignleft"><?php esc_html_e( 'Subtotal', 'paid-member-subscriptions' ); ?></span>
                    <span class="alignright"><?php echo esc_html( pms_format_price( $tax['subtotal'], pms_get_active_currency() ) ); ?></span>
                </div><br>

                <div>
                    <span class="alignleft"><?php echo esc_html( $tax['rate'] != 0 ? $tax['rate'] . '% ' : '' ) . esc_html__( 'TAX/VAT:', 'paid-member-subscriptions' ); ?></span>
                    <span class="alignright"><?php echo esc_html( pms_format_price( $tax['amount'], pms_get_active_currency() ) ); ?></span>
                </div><br>

                <div>
                    <span class="alignleft"><?php esc_html_e( 'Total', 'paid-member-subscriptions' ); ?></span>
                    <span class="alignright"><?php echo esc_html( pms_format_price( $tax['total'], pms_get_active_currency() ) ); ?></span>
                </div><br>
            </div>
        </span>

    <?php $output = ob_get_clean();

    return $output;

}
add_filter( 'pms_payments_list_table_column_amount', 'pms_in_tax_display_backend_tax_breakdown', 20, 2 );

/**
 * Display VAT Number when editing a payment in the back-end
 */
function pms_in_tax_add_vat_number_to_payment_screen( $payment ){

    if( !pms_in_tax_eu_vat_enabled() )
        return;

    if( isset( $payment ) && !empty( $payment->id ) ) {
        $vat_number        = pms_get_payment_meta( $payment->id, 'pms_vat_number', true );
        $vat_number_status = pms_get_payment_meta( $payment->id, 'pms_tax_valid_vat_number', true );
    } else {
        $vat_number        = isset( $_REQUEST['pms-vat-number'] ) ? sanitize_text_field( $_REQUEST['pms-vat-number'] ) : '';
        $vat_number_status = '';
    }

    ?>
    <div class="pms-form-field-vat-number cozmoslabs-form-field-wrapper">

        <label class="cozmoslabs-form-field-label" for="pms-vat-number"><?php esc_html_e( 'VAT Number', 'paid-member-subscriptions' ); ?></label>
        <input type="text" id="pms-vat-number" name="pms-vat-number" class="medium" value="<?php echo esc_attr( $vat_number ); ?>" />

        <?php if( !empty( $vat_number ) && !empty( $vat_number_status ) ) : ?>
            <span style="color:<?php echo $vat_number_status == 'true' ? 'green' : 'red' ?>">
                <?php echo $vat_number_status == 'true' ? esc_html__( 'Valid', 'paid-member-subscriptions' ) : esc_html__( 'Invalid', 'paid-member-subscriptions' ) ?>
            </span>
        <?php endif; ?>
    </div>
    <?php

}
add_action( 'pms_payment_edit_form_field', 'pms_in_tax_add_vat_number_to_payment_screen' );
add_action( 'pms_payment_add_new_form_field', 'pms_in_tax_add_vat_number_to_payment_screen' );

/**
 * Saves VAT Number when edited from the back-end
 * @return [type] [description]
 */
function pms_in_tax_save_vat_number(){

    // Verify correct nonce
    if( !isset( $_REQUEST['_wpnonce'] ) || !wp_verify_nonce( sanitize_text_field( $_REQUEST['_wpnonce'] ), 'pms_payment_nonce' ) )
        return;

    if( empty( $_POST['pms-vat-number'] ) || empty( $_REQUEST['pms-action'] ) || empty( $_REQUEST['payment_id'] ) )
        return;

    if( !in_array( $_REQUEST['pms-action'], array( 'edit_payment', 'add_payment' ) ) )
        return;

    $payment = pms_get_payment( absint( $_REQUEST['payment_id'] ) );

    if( empty( $payment->id ) )
        return;

    // we need to revalidate the VAT number upon saving
    $pms_tax_extra_fields = new PMS_IN_Tax_Extra_Fields();
    $vat_validation       = $pms_tax_extra_fields->validate_vat_number( pms_get_payment_meta( $payment->id, 'pms_billing_country', true ), sanitize_text_field( $_POST['pms-vat-number'] ) );

    pms_update_payment_meta( $payment->id, 'pms_tax_valid_vat_number', $vat_validation['valid'] == 'true' ? 'true' : 'false' );
    pms_update_payment_meta( $payment->id, 'pms_vat_number', sanitize_text_field( $_POST['pms-vat-number'] ) );

}
add_action( 'admin_init', 'pms_in_tax_save_vat_number' );

/**
 * Save VAT Number when a payment is manually added by an admin
 */
function pms_in_tax_add_vat_number( $payment ){

    if( empty( $payment->id ) || !isset( $_POST['pms-vat-number'] ) )
        return;

    $pms_tax_extra_fields = new PMS_IN_Tax_Extra_Fields();
    $vat_validation       = $pms_tax_extra_fields->validate_vat_number( pms_get_payment_meta( $payment->id, 'pms_billing_country', true ), sanitize_text_field( $_POST['pms-vat-number'] ) );

    pms_update_payment_meta( $payment->id, 'pms_tax_valid_vat_number', $vat_validation['valid'] == 'true' ? 'true' : 'false' );
    pms_update_payment_meta( $payment->id, 'pms_vat_number', sanitize_text_field( $_POST['pms-vat-number'] ) );

}
add_action( 'pms_manually_added_payment_success', 'pms_in_tax_add_vat_number' );

/**
 * Don't display this query arg in the URL
 */
function pms_in_tax_add_removable_arg($args) {
    array_push($args, 'pms_taxes_cleared');
    return $args;
}
add_filter('removable_query_args', 'pms_in_tax_add_removable_arg');

/**
 * Display option to make a subscription tax exempt
 *
 * @param int $subscription_plan_id
 */
function pms_in_tax_add_subscription_plan_settings_fields( $subscription_plan_id ){

    $tax_exempt = get_post_meta( $subscription_plan_id, 'pms_subscription_plan_tax_exempt', true );
    ?>

	<div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">

	    <label for="pms-subscription-plan-tax-exempt" class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'Tax Exempt', 'paid-member-subscriptions' ); ?></label>

        <div class="cozmoslabs-toggle-container">
            <input type="checkbox" id="pms-subscription-plan-tax-exempt" name="pms_subscription_plan_tax_exempt" value="1" <?php if( ! empty( $tax_exempt ) ) checked($tax_exempt, '1' ); ?><?php echo esc_html( $tax_exempt ); ?>" />
            <label class="cozmoslabs-toggle-track" for="pms-subscription-plan-tax-exempt"></label>
        </div>
        <div class="cozmoslabs-toggle-description">
            <label for="pms-subscription-plan-tax-exempt" class="cozmoslabs-description"><?php esc_html_e( 'By checking this option Tax will not be calculated for this Plan.', 'paid-member-subscriptions' ); ?></label>
        </div>

    </div>

<?php
}
add_action('pms_view_meta_box_subscription_details_free_trial_bottom', 'pms_in_tax_add_subscription_plan_settings_fields');


/**
 * Save the Tax Exempt option added by this add-on
 *
 * @param int $subscription_plan_id
 */
function pms_in_tax_save_subscription_plan_settings_fields( $subscription_plan_id ){

    if( empty( $_POST['post_ID'] ) )
        return;

    if( $subscription_plan_id != $_POST['post_ID'] )
        return;

    if( isset( $_POST['pms_subscription_plan_tax_exempt'] ) )
        update_post_meta( $subscription_plan_id, 'pms_subscription_plan_tax_exempt', sanitize_text_field( $_POST['pms_subscription_plan_tax_exempt'] ) );
    else
        update_post_meta( $subscription_plan_id, 'pms_subscription_plan_tax_exempt', 0 );

}
add_action('pms_save_meta_box_pms-subscription', 'pms_in_tax_save_subscription_plan_settings_fields');

function pms_in_tax_add_data_attributes( $data_attributes, $subscription_plan_id ){

    $tax_exempt = get_post_meta( $subscription_plan_id, 'pms_subscription_plan_tax_exempt', true );

    if( !empty( $tax_exempt ) && $tax_exempt == 1 )
        $data_attributes['tax-exempt'] = 1;

    return $data_attributes;

}
add_filter( 'pms_get_subscription_plan_input_data_attrs', 'pms_in_tax_add_data_attributes', 20, 2 );
