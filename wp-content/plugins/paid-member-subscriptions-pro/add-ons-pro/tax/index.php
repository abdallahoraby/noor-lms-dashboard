<?php
/**
 * Paid Member Subscriptions - Tax & EU VAT Rates
 * License: GPL2
 *
 * == Copyright ==
 * Copyright 2018 Cozmoslabs (www.cozmoslabs.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

Class PMS_IN_Tax_Base {

    /**
     * Constructor
     *
     */
    public function __construct() {

        define( 'PMS_IN_TAX_VERSION', '1.1.7' );
        define( 'PMS_IN_TAX_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
        define( 'PMS_IN_TAX_PLUGIN_DIR_URL',  plugin_dir_url( __FILE__ ) );

        $this->load_dependencies();
        $this->init();

    }


    /**
     * Load needed files
     */
    private function load_dependencies(){

        if ( file_exists( PMS_IN_TAX_PLUGIN_DIR_PATH . '/includes/functions.php' ) )
            include_once( PMS_IN_TAX_PLUGIN_DIR_PATH . '/includes/functions.php' );

        if ( file_exists( PMS_IN_TAX_PLUGIN_DIR_PATH . '/includes/functions-admin.php' ) )
            include_once( PMS_IN_TAX_PLUGIN_DIR_PATH . '/includes/functions-admin.php' );

        if( pms_in_tax_enabled() === true ){
            if ( file_exists( PMS_IN_TAX_PLUGIN_DIR_PATH . '/includes/class-tax.php' ) )
                include_once( PMS_IN_TAX_PLUGIN_DIR_PATH . '/includes/class-tax.php' );

            if ( file_exists( PMS_IN_TAX_PLUGIN_DIR_PATH . '/includes/class-tax-extra-fields.php' ) )
                include_once( PMS_IN_TAX_PLUGIN_DIR_PATH . '/includes/class-tax-extra-fields.php' );
        }

    }


    private function init() {

        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_back_end_scripts' ) );

        if( pms_in_tax_enabled() === true )
            add_action( 'wp_footer', array( $this, 'frontend_scripts' ) );

    }

    public function enqueue_back_end_scripts( $hook ) {

        // Load only on PMS admin Settings page
        if ( $hook != 'paid-member-subscriptions_page_pms-settings-page' )
            return;

        wp_enqueue_script( 'pms-tax-script', PMS_IN_TAX_PLUGIN_DIR_URL . 'assets/js/back-end.js', array('jquery'), PMS_IN_TAX_VERSION );

        $js_data = array (
            'tax_nonce'            => wp_create_nonce('pms-tax'),
            'taxRateRemoveMessage' => __('Are you sure you want to delete this Tax Rate?', 'paid-member-subscriptions')
        );

        wp_localize_script( 'pms-tax-script', 'PMSTaxOptions', $js_data );

    }

    public function frontend_scripts(){
    
        if( !pms_should_load_scripts() )
            return;
            
        wp_enqueue_style( 'pms-tax-style-front', PMS_IN_TAX_PLUGIN_DIR_URL . 'assets/css/front-end.css' );

        wp_enqueue_script( 'pms-frontend-tax', PMS_IN_TAX_PLUGIN_DIR_URL . 'assets/js/front-end.js', array( 'jquery' ), PMS_IN_TAX_VERSION );

        wp_localize_script( 'pms-frontend-tax', 'PMSTaxOptions', $this->get_js_data() );

    }

    public function get_js_data() {

        $data     = array();
        $settings = get_option( 'pms_payments_settings' );

        $data['ajax_url']           = admin_url( 'admin-ajax.php' );
        $data['tax_rates']          = pms_in_tax_get_rates();
        $data['default_tax_rate']   = pms_in_tax_get_default_rate();
        $data['prices_include_tax'] = pms_in_tax_prices_include_tax() === true ? 'true' : 'false';
        $data['currency']           = pms_get_active_currency();
        $data['currency_symbol']    = pms_get_currency_symbol( $data['currency'] );
        $data['currency_position']  = pms_get_currency_position();
        $data['locale']             = str_replace( '_', '-', get_locale() );
        $data['price_trim_zeroes']  = isset( $settings['price-display-format'] ) && $settings['price-display-format'] == 'without_insignificant_zeroes' ? 'true' : 'false';
        $data['default_tax_name']   = __( 'TAX', 'paid-member-subscriptions' );

        $eu_vat_enabled        = pms_in_tax_eu_vat_enabled();
        $data['euvat_enabled'] = $eu_vat_enabled === true ? 'true' : 'false';

        if( $eu_vat_enabled === true ){
            $data['euvat_numbers_minimum_char']              = pms_in_tax_get_vat_numbers_minimum_characters();
            $data['euvat_country_rates']                     = pms_in_tax_get_eu_vat_countries();
            $data['euvat_number_valid_message']              = __( 'Validated successfully.', 'paid-member-subscriptions' );
            $data['euvat_number_valid_message_same_country'] = __( 'Validated successfully. VAT is applied because you are from the same country as the merchant.', 'paid-member-subscriptions' );
            $data['euvat_number_invalid_message']            = __( 'Provided VAT Number is invalid.', 'paid-member-subscriptions' );
            $data['euvat_number_short_message']              = __( 'Your VAT Number is too short.', 'paid-member-subscriptions' );
            $data['euvat_merchant_country']                  = pms_in_tax_get_merchant_country();
            $data['euvat_tax_name']                          = __( 'VAT', 'paid-member-subscriptions' );
        }

        return $data;

    }

}

//Instantiate the class
$pms_tax_base = new PMS_IN_Tax_Base();
