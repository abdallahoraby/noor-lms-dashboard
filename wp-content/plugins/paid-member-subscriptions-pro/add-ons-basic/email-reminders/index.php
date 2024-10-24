<?php
/**
 * Paid Member Subscriptions - Email Reminders
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

if( ! defined( 'ABSPATH' ) )
    exit;

if( ! defined( 'PMS_VERSION' ) )
    return;

/* Define constants */
define( 'PMS_IN_ER_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'PMS_IN_ER_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );


/* Include needed files */
if ( file_exists( PMS_IN_ER_PLUGIN_DIR_PATH . 'includes/class-email-reminder.php' ) )
    include_once( PMS_IN_ER_PLUGIN_DIR_PATH . 'includes/class-email-reminder.php' );

if ( file_exists( PMS_IN_ER_PLUGIN_DIR_PATH . 'includes/class-admin-email-reminders.php' ) )
    include_once( PMS_IN_ER_PLUGIN_DIR_PATH . 'includes/class-admin-email-reminders.php' );

if ( file_exists( PMS_IN_ER_PLUGIN_DIR_PATH . 'includes/class-metabox-email-reminders-details.php' ) )
    include_once( PMS_IN_ER_PLUGIN_DIR_PATH . 'includes/class-metabox-email-reminders-details.php' );

if ( file_exists( PMS_IN_ER_PLUGIN_DIR_PATH . 'includes/functions-email-reminder.php' ) )
    include_once( PMS_IN_ER_PLUGIN_DIR_PATH . 'includes/functions-email-reminder.php' );


/* Adding Admin scripts */
function pms_in_er_add_admin_scripts(){

    // If the file exists where it should be, enqueue it
    if( file_exists( PMS_IN_ER_PLUGIN_DIR_PATH . 'includes/assets/js/cpt-email-reminders.js' ) )
        wp_enqueue_script( 'pms-email-reminders-js', PMS_IN_ER_PLUGIN_DIR_URL . 'includes/assets/js/cpt-email-reminders.js', array( 'jquery' ) );

    // add back-end css for Email Reminders cpt
    wp_enqueue_style( 'pms-er-style-back-end', PMS_IN_ER_PLUGIN_DIR_URL . 'includes/assets/css/style-back-end.css' );

}
add_action('pms_cpt_enqueue_admin_scripts_pms-email-reminders','pms_in_er_add_admin_scripts');
