<?php

/**
 * The code that runs during plugin activation.
 */

if( !function_exists( 'pms_in_ppsrp_install' ) ){

    function pms_in_ppsrp_install( $addon ) {

        if( $addon == 'pms-add-on-paypal-standard-recurring-payments/index.php' ){
            $pms_settings = get_option( 'pms_payments_settings', array() );

            if( empty( $pms_settings ) ) return;

            $pms_settings['recurring'] = 1;
            update_option('pms_payments_settings', $pms_settings);
        }

	}
    add_action( 'pms_add_ons_activate', 'pms_in_ppsrp_install', 10, 1);

}

if( !function_exists( 'pms_in_ppsrp_uninstall' ) ){

    function pms_in_ppsrp_uninstall( $addon ) {

        if( $addon == 'pms-add-on-paypal-standard-recurring-payments/index.php' ){
            $pms_settings = get_option( 'pms_payments_settings', array() );

            if( empty($pms_settings) ) return;

            if( isset($pms_settings['recurring']) && count($pms_settings['active_pay_gates']) == 1 && $pms_settings['active_pay_gates'][0] == 'paypal_standard' ) {
                unset($pms_settings['recurring']);
                update_option( 'pms_payments_settings', $pms_settings );
            }
        }

	}
    add_action( 'pms_add_ons_deactivate','pms_in_ppsrp_uninstall', 10, 1);

}
