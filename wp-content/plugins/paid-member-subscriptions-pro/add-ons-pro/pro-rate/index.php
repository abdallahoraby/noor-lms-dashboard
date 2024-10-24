<?php
/**
 * Paid Member Subscriptions - Pro-rate
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

Class PMS_IN_ProRate_Base {

    /**
     * Constructor
     *
     */
    public function __construct() {

        define( 'PMS_IN_PRORATE_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
        define( 'PMS_IN_PRORATE_PLUGIN_DIR_URL',  plugin_dir_url( __FILE__ ) );

        $this->load_dependencies();

    }


    /**
     * Load needed files
     */
    private function load_dependencies(){

        if ( file_exists( PMS_IN_PRORATE_PLUGIN_DIR_PATH . '/includes/class-pro-rate.php' ) )
            include_once( PMS_IN_PRORATE_PLUGIN_DIR_PATH . '/includes/class-pro-rate.php' );

    }

}

//Instantiate the class
$pms_prorate_base = new PMS_IN_ProRate_Base();
