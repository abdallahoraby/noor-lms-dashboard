<?php
/**
 * Paid Member Subscriptions - Pay What You Want
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

class PMS_IN_Pay_What_You_Want {

    /**
     * Constructor
     *
     */
    public function __construct() {

        //define constants
        define( 'PMS_IN_PWYW_VERSION', '1.1.0' );
        define( 'PMS_IN_PWYW_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
        define( 'PMS_IN_PWYW_PLUGIN_DIR_URL',  plugin_dir_url( __FILE__ ) );

        $this->include_files();
        $this->init();

    }


    private function init() {

        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ), 20 );
        add_action( 'wp_footer', array( $this, 'enqueue_front_end_scripts' ) );

    }


    /**
     * Include add-on files
     *
     */
    private function include_files() {

        if ( file_exists( PMS_IN_PWYW_PLUGIN_DIR_PATH . 'includes/functions-admin.php' ) )
            include_once( PMS_IN_PWYW_PLUGIN_DIR_PATH . 'includes/functions-admin.php' );

        if ( file_exists( PMS_IN_PWYW_PLUGIN_DIR_PATH . 'includes/functions.php' ) )
            include_once( PMS_IN_PWYW_PLUGIN_DIR_PATH . 'includes/functions.php' );

    }

    /**
     * Enqueue admin scripts
     *
     */
    public function enqueue_admin_scripts( $hook ) {

        if( get_post_type() == 'pms-subscription' ) {

           wp_enqueue_style('pms-pwyw-admin-style', PMS_IN_PWYW_PLUGIN_DIR_URL . 'assets/css/admin.css' );
           wp_enqueue_script( 'pms-pwyw-admin-script', PMS_IN_PWYW_PLUGIN_DIR_URL . 'assets/js/back-end.js', array('jquery') );

        }

    }

    /**
     * Enqueue front-end scripts
     *
     */
    public function enqueue_front_end_scripts( $hook ) {
    
        if( !pms_should_load_scripts() )
            return;
            
        wp_enqueue_script( 'pms-pwyw-script', PMS_IN_PWYW_PLUGIN_DIR_URL . 'assets/js/front-end.js', array('jquery') );

    }

}

// let's get the party started
new PMS_IN_Pay_What_You_Want();
