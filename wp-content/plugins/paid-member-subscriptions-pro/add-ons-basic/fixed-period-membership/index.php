<?php
/**
 * Paid Member Subscriptions - Fixed Period Membership
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

Class PMS_IN_Member_Subscriptions_Fixed_Period {

    /**
     * Constructor
     *
     */
    public function __construct() {

        define( 'PMS_IN_MSFP_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
        define( 'PMS_IN_MSFP_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );

        $this->load_dependencies();
        $this->init();

    }

    private function init() {

        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

        add_action( 'admin_init', array( $this, 'msfp_migration' ) );

    }


    /**
     * Load needed files
     *
     */
    private function load_dependencies() {

        // Admin page
        if( file_exists( PMS_IN_MSFP_PLUGIN_DIR_PATH . 'includes/admin/functions-admin-pages.php' ) )
            include PMS_IN_MSFP_PLUGIN_DIR_PATH . 'includes/admin/functions-admin-pages.php';

        // Display functions
        if( file_exists( PMS_IN_MSFP_PLUGIN_DIR_PATH . 'includes/functions.php' ) )
            include PMS_IN_MSFP_PLUGIN_DIR_PATH . 'includes/functions.php';

    }


    /**
     * Enqueue admin scripts
     *
     */
    public function enqueue_admin_scripts( $hook ) {

        if( get_post_type() == 'pms-subscription' ) {

            wp_enqueue_script( 'jquery-ui-datepicker' );
            wp_enqueue_style('jquery-style', PMS_PLUGIN_DIR_URL . 'assets/css/admin/jquery-ui.min.css', array(), PMS_VERSION );

            global $wp_scripts;

            // Try to detect if chosen has already been loaded
            $found_chosen = false;

            foreach( $wp_scripts as $wp_script ) {
                if( !empty( $wp_script['src'] ) && strpos($wp_script['src'], 'chosen') !== false )
                    $found_chosen = true;
            }

            if( !$found_chosen ) {
                wp_enqueue_script( 'pms-chosen', PMS_PLUGIN_DIR_URL . 'assets/libs/chosen/chosen.jquery.min.js', array( 'jquery' ), PMS_VERSION );
                wp_enqueue_style( 'pms-chosen', PMS_PLUGIN_DIR_URL . 'assets/libs/chosen/chosen.css', array(), PMS_VERSION );
            }

        }

    }

    /**
     * Migrate old Fixed Period Memberships to new version
     *
     */
    public function msfp_migration( $hook ){

        $option = get_option( 'pms_msfp_migration', array() );

        if( empty( $option ) ){

            $subscription_plans = pms_get_subscription_plans();

            foreach( $subscription_plans as $subscription_plan ){

                if( isset( $subscription_plan->type ) && $subscription_plan->type == 'fixed-period' ){

                    update_post_meta( $subscription_plan->id, 'pms_subscription_plan_type', 'regular' );
                    update_post_meta( $subscription_plan->id, 'pms_subscription_plan_fixed_membership', 'on' );

                }
            }

            add_option( 'pms_msfp_migration', 'msfp_migration' );

        }
    }

}

// Let's get this party started
new PMS_IN_Member_Subscriptions_Fixed_Period;
