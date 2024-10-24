<?php
/**
 * Paid Member Subscriptions - Recurring Payments for PayPal Standard
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

Class PMS_IN_PayPal_Standard_Recurring_Payments {

    public function __construct() {

        // Load only if the free version doesn't have this functionality
        if( file_exists( PMS_PLUGIN_DIR_PATH . 'includes/gateways/paypal_standard/functions-paypal-standard-recurring.php' ) )
            return;

        // Define global constants
        define( 'PMS_IN_PPSRP_VERSION', '1.2.8' );
        define( 'PMS_IN_PPSRP_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
        define( 'PMS_IN_PPSRP_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );

        // Include dependencies
        $this->include_dependencies();

    }


    /*
     * Function to include the files needed
     *
     */
    public function include_dependencies() {

        /*
         * Settings Admin Page
         */
        if( file_exists( PMS_IN_PPSRP_PLUGIN_DIR_PATH . 'includes/admin/functions-admin-pages.php' ) )
            include_once PMS_IN_PPSRP_PLUGIN_DIR_PATH . 'includes/admin/functions-admin-pages.php';

        /*
         * PayPal Functions
         */
        if( file_exists( PMS_IN_PPSRP_PLUGIN_DIR_PATH . 'includes/functions-paypal.php' ) )
            include_once PMS_IN_PPSRP_PLUGIN_DIR_PATH . 'includes/functions-paypal.php';


    }

}

// Let's get the party started
new PMS_IN_PayPal_Standard_Recurring_Payments;
