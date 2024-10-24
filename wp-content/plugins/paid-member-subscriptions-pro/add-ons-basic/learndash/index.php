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

class PMS_IN_LearnDash {

    /**
     * Constructor
     *
     */
    public function __construct() {

        define( 'PMS_IN_LEARNDASH_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
        define( 'PMS_IN_LEARNDASH_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );

        $this->include_files();
        $this->init();

    }


    private function init() {

        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_learndash_admin_scripts' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'pms_enqueue_lerndash_scripts_and_styles' ) );

    }


    /**
     * Include add-on files
     *
     */
    private function include_files() {

        if ( file_exists( PMS_IN_LEARNDASH_PLUGIN_DIR_PATH . '/includes/functions-content-restriction.php' ) )
            include_once PMS_IN_LEARNDASH_PLUGIN_DIR_PATH . '/includes/functions-content-restriction.php';

        if ( file_exists( PMS_IN_LEARNDASH_PLUGIN_DIR_PATH . '/includes/class-pms-learndash-courses.php' ) )
            include_once PMS_IN_LEARNDASH_PLUGIN_DIR_PATH . '/includes/class-pms-learndash-courses.php';

    }

    /**
     * Enqueue admin scripts
     *
     */
    public function enqueue_learndash_admin_scripts( $hook ) {

        if( !get_post_type() == 'pms-subscription' )
            return;

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

        if ( file_exists( PMS_IN_LEARNDASH_PLUGIN_DIR_PATH . '/assets/js/pms-learndash-courses-backend.js' ) )
            wp_enqueue_script( 'pms-learndash-courses-backend-js', PMS_IN_LEARNDASH_PLUGIN_DIR_URL . '/assets/js/pms-learndash-courses-backend.js', array( 'jquery' ), PMS_VERSION );

    }


    /**
     * Enqueue frontend scripts and styles
     *
     */
    function pms_enqueue_lerndash_scripts_and_styles() {

        $pms_account_page_id = pms_get_page( 'account' );
        $current_page_id = get_the_ID();

        if ( $pms_account_page_id == $current_page_id ) {

            if ( file_exists( PMS_IN_LEARNDASH_PLUGIN_DIR_PATH . '/assets/css/pms-learndash-courses-style.css' ) )
                wp_enqueue_style( 'pms-learndash-courses-style', PMS_IN_LEARNDASH_PLUGIN_DIR_URL . '/assets/css/pms-learndash-courses-style.css', array(), PMS_VERSION );

            if ( file_exists( PMS_IN_LEARNDASH_PLUGIN_DIR_PATH . '/assets/js/pms-learndash-courses-frontend.js' ) )
                wp_enqueue_script( 'pms-learndash-courses-frontend-js', PMS_IN_LEARNDASH_PLUGIN_DIR_URL . '/assets/js/pms-learndash-courses-frontend.js', array( 'jquery' ), PMS_VERSION );

        }

    }

}


/**
 * Initiate LearnDash Addon or display an admin notice if LearnDash plugin is not active
 *
 */
function pms_in_lerndash_init() {

    if( is_plugin_active( 'sfwd-lms/sfwd_lms.php' ) )
        new PMS_IN_LearnDash;
    else
        add_action( 'admin_notices', 'pms_in_learndash_admin_notice' );

}
add_action( 'plugins_loaded', 'pms_in_lerndash_init', 11 );


/**
 * Admin notice if LearnDash plugin is not active
 *
 */
function pms_in_learndash_admin_notice() {

    echo '<div class="notice notice-error is-dismissible">';
    echo '<p>' . wp_kses_post( sprintf( __( '%s needs to be installed and activated for the %s to work as expected!', 'paid-member-subscriptions' ), '<strong>LearnDash</strong>', '<strong>Paid Member Subscriptions - LearnDash Add-on</strong>' ) ) . '</p>';
    echo '</div>';

}
