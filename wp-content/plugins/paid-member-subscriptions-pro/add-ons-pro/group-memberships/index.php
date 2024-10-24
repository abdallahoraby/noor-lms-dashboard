<?php
/**
 * Paid Member Subscriptions - Group Memberships Add-on
 * License: GPL2
 *
 * == Copyright ==
 * Copyright 2019 Cozmoslabs (www.cozmoslabs.com)
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

Class PMS_IN_Group_Memberships_Base {

    public function __construct(){

        define( 'PMS_IN_GM_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
        define( 'PMS_IN_GM_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );

        if( version_compare( PMS_VERSION, '1.9.2', '>=' ) ){
            $this->load_dependencies();
            $this->init();
        } else {
            $message = __( 'Your version of Paid Member Subscriptions is not compatible with the Group Memberships add-on. Please update Paid member subscriptions to the latest version.', 'paid-member-subscriptions' );

            $pms_notifications_instance = PMS_Plugin_Notifications::get_instance();

            if( !$pms_notifications_instance->is_plugin_page() ) {
                $message .= sprintf(__(' %1$sDismiss%2$s', 'paid-member-subscriptions'), "<a class='dismiss-right' href='" . esc_url( wp_nonce_url( add_query_arg( 'pms_group_memberships_core_version_message_dismiss_notification', '0' ), 'pms_general_notice_dismiss' ) ) . "'>", "</a>");
                $pms_force_show = false;
            }
            else{
                $pms_force_show = true;
            }

            new PMS_Add_General_Notices( 'pms_group_memberships_core_version_message',
                $message,
                'error',
                '',
                '',
                $pms_force_show );
        }

    }

    private function init(){

        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

        add_action( 'wp_footer', array( $this, 'frontend_scripts' ) );

    }


    /**
     * Load needed files
     *
     */
    private function load_dependencies(){

        if( is_admin() ){
            if( file_exists( PMS_IN_GM_PLUGIN_DIR_PATH . 'includes/admin/class-admin-group-info-list-table.php' ) )
                include PMS_IN_GM_PLUGIN_DIR_PATH . 'includes/admin/class-admin-group-info-list-table.php';

            if( file_exists( PMS_IN_GM_PLUGIN_DIR_PATH . 'includes/admin/class-admin-invite-members.php' ) )
                include PMS_IN_GM_PLUGIN_DIR_PATH . 'includes/admin/class-admin-invite-members.php';

            if( file_exists( PMS_IN_GM_PLUGIN_DIR_PATH . 'includes/admin/class-admin-group-members-list-table.php' ) )
                include PMS_IN_GM_PLUGIN_DIR_PATH . 'includes/admin/class-admin-group-members-list-table.php';

            if( file_exists( PMS_IN_GM_PLUGIN_DIR_PATH . 'includes/admin/class-admin-group-memberships.php' ) )
                include PMS_IN_GM_PLUGIN_DIR_PATH . 'includes/admin/class-admin-group-memberships.php';
        }

        if( file_exists( PMS_IN_GM_PLUGIN_DIR_PATH . 'includes/functions.php' ) )
            include PMS_IN_GM_PLUGIN_DIR_PATH . 'includes/functions.php';

        if( file_exists( PMS_IN_GM_PLUGIN_DIR_PATH . 'includes/class-emails.php' ) )
            include PMS_IN_GM_PLUGIN_DIR_PATH . 'includes/class-emails.php';

        if( file_exists( PMS_IN_GM_PLUGIN_DIR_PATH . 'includes/class-group-memberships.php' ) )
            include PMS_IN_GM_PLUGIN_DIR_PATH . 'includes/class-group-memberships.php';

    }


    /**
     * Enqueue admin scripts
     *
     */
    public function admin_scripts( $hook ){

        if( get_post_type() == 'pms-subscription' )
            wp_enqueue_script( 'pms-gm-admin-script', PMS_IN_GM_PLUGIN_DIR_URL . 'assets/js/admin.js', array( 'jquery' ), PMS_VERSION );

        // Plugin name can be translated so the hook differs in that case, we can work around this
        $parent_menu_slug = sanitize_title( __( 'Paid Member Subscriptions', 'paid-member-subscriptions' ) );

        if( $hook == $parent_menu_slug . '_page_pms-members-page' ){
            wp_enqueue_style( 'pms-gm-style-back-end', PMS_IN_GM_PLUGIN_DIR_URL . 'assets/css/style-back-end.css', array(), PMS_VERSION );
            wp_enqueue_script( 'pms-gm-admin-group-details', PMS_IN_GM_PLUGIN_DIR_URL . 'assets/js/admin-group-details.js', array( 'jquery' ), PMS_VERSION );

            wp_localize_script( 'pms-gm-admin-group-details', 'pms_gm', array(
                'ajax_url'                      => admin_url( 'admin-ajax.php' ),
                'edit_group_details_nonce'      => wp_create_nonce( 'pms_gm_admin_edit_group_details_nonce' ),
                'resend_group_invitation_nonce' => wp_create_nonce( 'pms_group_subscription_resend_invitation' ),
                'remove_user_message'           => esc_html__( 'Are you sure you want to remove this member ?', 'paid-member-subscriptions' )
            ));
        }

    }

    public function frontend_scripts(){
    
        if( !pms_should_load_scripts() )
            return;

        wp_enqueue_style( 'pms-group-memberships-style-front', PMS_IN_GM_PLUGIN_DIR_URL . 'assets/css/style-front-end.css' );

        wp_enqueue_script( 'pms-frontend-group-memberships-js', PMS_IN_GM_PLUGIN_DIR_URL . 'assets/js/front-end.js', array( 'jquery' ), PMS_VERSION );

        if( get_query_var( 'tab' ) == 'manage-group' ){
            wp_enqueue_script( 'pms-gm-group-dashboard', PMS_IN_GM_PLUGIN_DIR_URL . 'assets/js/frontend-group-dashboard.js', array( 'jquery' ), PMS_VERSION );

            wp_localize_script( 'pms-gm-group-dashboard', 'pms_gm', array(
                'ajax_url'                      => admin_url( 'admin-ajax.php' ),
                'remove_group_member_nonce'     => wp_create_nonce( 'pms_group_subscription_member_remove' ),
                'resend_group_invitation_nonce' => wp_create_nonce( 'pms_group_subscription_resend_invitation' ),
                'remove_user_message'           => esc_html__( 'Are you sure you want to remove this member ?', 'paid-member-subscriptions' ),
            ) );
        }

    }

}

new PMS_IN_Group_Memberships_Base;
