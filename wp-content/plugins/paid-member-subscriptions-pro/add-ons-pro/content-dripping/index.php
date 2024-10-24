<?php
/**
 * Paid Member Subscriptions - Content Dripping
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

if( ! defined( 'ABSPATH' ) )
    exit;

if( ! defined( 'PMS_VERSION' ) )
    return;

Class PMS_IN_Content_Dripper {

    public function __construct() {

        $this->define_constants();

        $this->load_dependencies();

        $this->init();

    }

    private function init() {

        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_back_end_scripts' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_front_end_scripts' ) );

    }

    private function define_constants() {

        define( 'PMS_IN_CONTENT_DRIPPING_VERSION', '1.0.7' );
        define( 'PMS_IN_CONTENT_DRIPPING_DIR_PATH', plugin_dir_path( __FILE__ ) );
        define( 'PMS_IN_CONTENT_DRIPPING_DIR_URL', plugin_dir_url( __FILE__ ) );

    }

    private function load_dependencies() {

        if( file_exists( PMS_IN_CONTENT_DRIPPING_DIR_PATH . 'includes/class-admin-content-dripping.php' ) )
            include PMS_IN_CONTENT_DRIPPING_DIR_PATH . 'includes/class-admin-content-dripping.php';

        if( file_exists( PMS_IN_CONTENT_DRIPPING_DIR_PATH . 'includes/class-meta-box-content-dripping-details.php' ) )
            include PMS_IN_CONTENT_DRIPPING_DIR_PATH . 'includes/class-meta-box-content-dripping-details.php';

        if( file_exists( PMS_IN_CONTENT_DRIPPING_DIR_PATH . 'includes/class-meta-box-content-dripping-rules.php' ) )
            include PMS_IN_CONTENT_DRIPPING_DIR_PATH . 'includes/class-meta-box-content-dripping-rules.php';

        if( file_exists( PMS_IN_CONTENT_DRIPPING_DIR_PATH . 'includes/functions-content-filtering.php' ) )
            include PMS_IN_CONTENT_DRIPPING_DIR_PATH . 'includes/functions-content-filtering.php';

        if( file_exists( PMS_IN_CONTENT_DRIPPING_DIR_PATH . 'includes/class-shortcodes.php' ) )
            include PMS_IN_CONTENT_DRIPPING_DIR_PATH . 'includes/class-shortcodes.php';

    }


    /**
     * Enqueue back end scripts and styles
     *
     */
    public function enqueue_back_end_scripts() {

        wp_enqueue_script( 'pms-content-dripping-script', PMS_IN_CONTENT_DRIPPING_DIR_URL . 'assets/js/back-end.js', array( 'jquery', 'jquery-ui-core' ), PMS_VERSION );
        wp_enqueue_style( 'pms-content-dripping-style', PMS_IN_CONTENT_DRIPPING_DIR_URL . 'assets/css/back-end.css', array(), PMS_VERSION );

    }

    /**
     * Enqueue front-end scripts and styles
     *
     */
    public function enqueue_front_end_scripts() {}

}

// Let's get this party started
new PMS_IN_Content_Dripper;
