<?php
/**
 * Paid Member Subscriptions - PayPal Pro and PayPal Express
 * License: GPL2
 *
 * == Copyright ==
 * Copyright 2016 Cozmoslabs (www.cozmoslabs.com)
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

Class PMS_IN_PayPal_Express_Pro {

    /**
     * Constructor
     *
     */
    public function __construct() {

        define( 'PMS_IN_PP_VERSION', '1.4.5' );
        define( 'PMS_IN_PP_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
        define( 'PMS_IN_PP_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );

        // Include dependencies
        $this->include_dependencies();

        // Initialize the plugin
        $this->init();

    }

    /*
     * Initialize the plugin
     *
     * */
    public function init(){

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_front_end_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
    }

    /*
     * Function to include the files needed
     *
     */
    public function include_dependencies() {

        /*
         * Settings Admin Page
         */
        if( file_exists( PMS_IN_PP_PLUGIN_DIR_PATH . 'includes/admin/functions-admin-pages.php' ) )
            include PMS_IN_PP_PLUGIN_DIR_PATH . 'includes/admin/functions-admin-pages.php';

        /*
         * PayPal Express Checkout and PayPal Pro
         */
        if( file_exists( PMS_IN_PP_PLUGIN_DIR_PATH . 'includes/functions.php' ) )
            include PMS_IN_PP_PLUGIN_DIR_PATH . 'includes/functions.php';

        if( file_exists( PMS_IN_PP_PLUGIN_DIR_PATH . 'includes/class-payment-gateway-paypal-express-legacy.php' ) )
            include PMS_IN_PP_PLUGIN_DIR_PATH . 'includes/class-payment-gateway-paypal-express-legacy.php';

        if( file_exists( PMS_IN_PP_PLUGIN_DIR_PATH . 'includes/class-payment-gateway-paypal-express.php' ) )
            include PMS_IN_PP_PLUGIN_DIR_PATH . 'includes/class-payment-gateway-paypal-express.php';

        if( file_exists( PMS_IN_PP_PLUGIN_DIR_PATH . 'includes/class-payment-gateway-paypal-pro.php' ) )
            include PMS_IN_PP_PLUGIN_DIR_PATH . 'includes/class-payment-gateway-paypal-pro.php';

        /*
         * Compatibility files with PB
         */
        if( file_exists( PMS_IN_PP_PLUGIN_DIR_PATH . 'extend/functions-pb-redirect.php' ) )
            include PMS_IN_PP_PLUGIN_DIR_PATH . 'extend/functions-pb-redirect.php';

    }


    /**
     * Enqueue front-end scripts and styles
     *
     */
    public function enqueue_front_end_scripts() {

        wp_enqueue_style( 'pms-paypal-express-pro-style', PMS_IN_PP_PLUGIN_DIR_URL . 'assets/css/pms-paypal-express-pro.css', array(), PMS_IN_PP_VERSION );

    }


    /**
     * Enqueue admin scripts
     *
     */
    public function enqueue_admin_scripts( $hook ) {

        if( $hook != 'paid-member-subscriptions_page_pms-settings-page' )
            return;

        wp_enqueue_script( 'pms-paypal-express-pro-admin-script', PMS_IN_PP_PLUGIN_DIR_URL . 'assets/js/admin.js', array('jquery'), PMS_IN_PP_VERSION );

    }

}

// Let's get the party started
new PMS_IN_PayPal_Express_Pro;
