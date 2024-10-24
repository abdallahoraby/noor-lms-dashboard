<?php
/**
 * Paid Member Subscriptions - bbPress Add-on
 * License: GPL2
 *
 * == Copyright ==
 * Copyright 2017 Cozmoslabs (www.cozmoslabs.com)
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


Class PMS_IN_bbPress {

    /**
     * Constructor
     *
     */
    public function __construct() {

        define( 'PMS_IN_BBPRESS_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
        define( 'PMS_IN_BBPRESS_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );

        $this->load_dependencies();
        $this->init();

    }

    /**
     * Initialise plugin components
     *
     */
    private function init() {

    }

    /**
     * Load needed files
     *
     */
    private function load_dependencies() {

    	// Admin pages
    	if( file_exists( PMS_IN_BBPRESS_PLUGIN_DIR_PATH . 'includes/admin/functions-admin-pages.php' ) )
            include PMS_IN_BBPRESS_PLUGIN_DIR_PATH . 'includes/admin/functions-admin-pages.php';

        // Meta-boxes
        if( file_exists( PMS_IN_BBPRESS_PLUGIN_DIR_PATH . 'includes/admin/meta-boxes/functions-meta-box-content-restriction.php' ) )
            include PMS_IN_BBPRESS_PLUGIN_DIR_PATH . 'includes/admin/meta-boxes/functions-meta-box-content-restriction.php';

        // Content restriction
        if( file_exists( PMS_IN_BBPRESS_PLUGIN_DIR_PATH . 'includes/functions-content-restriction.php' ) )
            include PMS_IN_BBPRESS_PLUGIN_DIR_PATH . 'includes/functions-content-restriction.php';

    }

}

// Let's get this party started
function pms_in_bbp_init() {

	if( class_exists( 'bbPress' ) )
		new PMS_IN_bbPress;

	else
        add_action( 'admin_notices', 'pms_in_bbp_admin_notice' );

}
add_action( 'plugins_loaded', 'pms_in_bbp_init', 11 );


/**
 * Admin notice if bbPress plugin is not active
 *
 */
function pms_in_bbp_admin_notice() {

    echo '<div class="notice notice-error is-dismissible">';
        echo '<p>' . esc_html__( 'bbPress needs to be installed and activated for Paid Member Subscriptions - bbPress Add-on to work as expected!', 'paid-member-subscriptions' ) . '</p>';
    echo '</div>';

}
