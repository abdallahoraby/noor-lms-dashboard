<?php
/**
 * Paid Member Subscriptions - Stripe Payment Gateway
 * License: GPL2
 *
 * == Copyright ==
 * Copyright 2015 Cozmoslabs (www.cozmoslabs.com)
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

Class PMS_IN_Stripe {

    /**
     * Constructor
     *
     */
    public function __construct() {

        define( 'PMS_IN_STRIPE_VERSION', '1.4.9' );
        define( 'PMS_IN_STRIPE_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
        define( 'PMS_IN_STRIPE_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );

        $this->load_dependencies();
        $this->init();

    }

    private function init() {

        add_action( 'wp_footer', array( $this, 'enqueue_front_end_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

        // Enable auth email by default on the first run
        if( get_option( 'pms_stripe_first_activation', false ) === false ){

            update_option( 'pms_stripe_first_activation', time() );

            $email_settings = get_option( 'pms_emails_settings', array() );

            $email_settings['stripe_authentication_is_enabled'] = 'yes';

            update_option( 'pms_emails_settings', $email_settings );
        }
    }


    /**
     * Load needed files
     *
     */
    private function load_dependencies() {

        // Stripe Library
        if( file_exists( PMS_IN_STRIPE_PLUGIN_DIR_PATH . 'libs/stripe/init.php' ) )
            include PMS_IN_STRIPE_PLUGIN_DIR_PATH . 'libs/stripe/init.php';

        // Admin page
        if( file_exists( PMS_IN_STRIPE_PLUGIN_DIR_PATH . 'includes/admin/functions-admin-pages.php' ) )
            include PMS_IN_STRIPE_PLUGIN_DIR_PATH . 'includes/admin/functions-admin-pages.php';

        // Gateway class and gateway functions
        if( file_exists( PMS_IN_STRIPE_PLUGIN_DIR_PATH . 'includes/functions.php' ) )
            include PMS_IN_STRIPE_PLUGIN_DIR_PATH . 'includes/functions.php';

        if( file_exists( PMS_IN_STRIPE_PLUGIN_DIR_PATH . 'includes/functions-actions.php' ) )
            include PMS_IN_STRIPE_PLUGIN_DIR_PATH . 'includes/functions-actions.php';

        if( file_exists( PMS_IN_STRIPE_PLUGIN_DIR_PATH . 'includes/functions-filters.php' ) )
            include PMS_IN_STRIPE_PLUGIN_DIR_PATH . 'includes/functions-filters.php';

        if( file_exists( PMS_IN_STRIPE_PLUGIN_DIR_PATH . 'includes/class-payment-gateway-stripe-legacy.php' ) )
            include PMS_IN_STRIPE_PLUGIN_DIR_PATH . 'includes/class-payment-gateway-stripe-legacy.php';

        if( file_exists( PMS_IN_STRIPE_PLUGIN_DIR_PATH . 'includes/class-payment-gateway-stripe.php' ) )
            include PMS_IN_STRIPE_PLUGIN_DIR_PATH . 'includes/class-payment-gateway-stripe.php';

        if( file_exists( PMS_IN_STRIPE_PLUGIN_DIR_PATH . 'includes/class-payment-gateway-stripe-payment-intents.php' ) )
            include PMS_IN_STRIPE_PLUGIN_DIR_PATH . 'includes/class-payment-gateway-stripe-payment-intents.php';

        if( file_exists( PMS_IN_STRIPE_PLUGIN_DIR_PATH . 'includes/class-emails.php' ) )
            include PMS_IN_STRIPE_PLUGIN_DIR_PATH . 'includes/class-emails.php';

        //Compatibility files with PB
        if( file_exists( PMS_IN_STRIPE_PLUGIN_DIR_PATH . 'extend/functions-pb-redirect.php' ) )
            include PMS_IN_STRIPE_PLUGIN_DIR_PATH . 'extend/functions-pb-redirect.php';

    }

    /**
     * Enqueue front-end scripts and styles
     *
     */
    public function enqueue_front_end_scripts() {
    
        if( !pms_should_load_scripts() )
            return;

        $active_gateways = pms_get_active_payment_gateways();

        if( !in_array( 'stripe_intents', $active_gateways ) )
            return;
            
        wp_enqueue_script( 'pms-stripe-js', 'https://js.stripe.com/v3/', array( 'jquery' ) );

        wp_enqueue_style( 'pms-stripe-style', PMS_IN_STRIPE_PLUGIN_DIR_URL . 'assets/css/pms-stripe.css', array(), PMS_IN_STRIPE_VERSION );

        $pms_stripe_script_vars = array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'empty_credit_card_message' => __( 'Please enter a credit card number.', 'paid-member-subscriptions' ), 'invalid_card_details_error' => __( 'Your card details do not seem to be valid.', 'paid-member-subscriptions' ) );

        wp_enqueue_script( 'pms-stripe-script', PMS_IN_STRIPE_PLUGIN_DIR_URL . 'assets/js/front-end.js', array('jquery'), PMS_IN_STRIPE_VERSION );

        wp_localize_script( 'pms-stripe-script', 'pms', $pms_stripe_script_vars );

        wp_localize_script( 'pms-stripe-script', 'pms_elements_styling', apply_filters( 'pms_stripe_elements_styling', array( 'base' => array(), 'invalid' => array() ) ) );

    }

    public function enqueue_admin_scripts( $hook ) {

        if( $hook != 'paid-member-subscriptions_page_pms-settings-page' )
            return;

        wp_enqueue_script( 'pms-stripe-admin-script', PMS_IN_STRIPE_PLUGIN_DIR_URL . 'assets/js/admin-settings-payments.js', array('jquery'), PMS_IN_STRIPE_VERSION );

    }

}

// Let's get this party started
new PMS_IN_Stripe;
