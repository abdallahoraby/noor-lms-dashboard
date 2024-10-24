<?php
/**
 * Paid Member Subscriptions - Navigation Menu Filtering
 * License: GPL2
 */
/*  Copyright 2015 Cozmoslabs (www.cozmoslabs.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

    This add-on plugin is based on the "Nav Menu Roles" plugin: https://wordpress.org/plugins/nav-menu-roles/

*/

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) )
    exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) )
    return;

/*
* Define plugin path
*/
define( 'PMS_IN_NMF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PMS_IN_NMF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );


if( file_exists( PMS_IN_NMF_PLUGIN_DIR . 'includes/class-pms_walker_nav_menu.php' ) )
    include_once PMS_IN_NMF_PLUGIN_DIR . 'includes/class-pms_walker_nav_menu.php';

if( file_exists( PMS_IN_NMF_PLUGIN_DIR . 'includes/class-nav-menu-filtering.php' ) )
    include_once PMS_IN_NMF_PLUGIN_DIR . 'includes/class-nav-menu-filtering.php';

