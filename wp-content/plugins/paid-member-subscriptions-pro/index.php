<?php
/*
Plugin Name: Paid Member Subscriptions Pro
Plugin URI: https://cozmoslabs.com/
Description: Unlock the full potential of Paid Member Subscriptions and grow your membership revenue with recurring payments, different payment gateways, group memberships, invoices, taxes and more.
Version: 1.6.4
Author: Cozmoslabs
Author URI: https://cozmoslabs.com/
Text Domain: paid-member-subscriptions
Requires Plugins: paid-member-subscriptions
License: GPL2


== Copyright ==
Copyright 2017 Cozmoslabs (www.cozmoslabs.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

//this class might be present in PMS Basic, Pro and Elite as well
if( !class_exists('PMS_Handle_Included_Addons') ){
    class PMS_Handle_Included_Addons{

        function __construct(){
            //disable old addons and create database entries for add-ons status
            add_action( 'plugins_loaded', array( $this, 'disable_old_add_ons' ), 12 );

            //activate an add-on when you press the button from the add-ons page
            add_action( 'pms_add_ons_activate', array( $this, 'pms_activate_add_ons' ) );
            //deactivate an add-on when you press the button from the add-ons page
            add_action( 'pms_add_ons_deactivate', array( $this, 'pms_deactivate_add_ons' ) );
            //show the button in the add-ons page with the correct action
            add_filter( 'pms_add_on_is_active', array( $this, 'pms_check_add_ons_activation' ) , 10, 2 );

            add_action( 'admin_notices', array( $this, 'pms_main_plugin_notice' ) );
            add_action( 'network_admin_notices', array( $this, 'pms_main_plugin_notice' ) );

            //include add-on files that contain activation hooks even when add-ons are deactivated
            $this->include_mandatory_addon_files();

            //include the addons from the main plugin if they are activated
            add_action( 'plugins_loaded', array( $this, 'include_addons' ) );

        }


        /**
         * Add a notice if the Paid Member Subscriptions main plugin is active and right version
         */
        function pms_main_plugin_notice(){
            $pms_installation_status = $this->install_activate_pms();
            if ( $pms_installation_status !== 'no_action_requested' ){
                if ( $pms_installation_status === 'plugin_activated' ) {
                    echo '<div class="notice updated is-dismissible "><p>' . esc_html__('Plugin activated.', 'paid-member-subscriptions') . '</p></div>';
                }
                if ( $pms_installation_status === 'error_activating' ) {
                    echo '<div class="notice notice-error is-dismissible "><p>' . wp_kses( sprintf( __('Could not install. Try again from <a href="%s" >Plugins Dashboard.</a>', 'paid-member-subscriptions'), admin_url('plugins.php') ), array('a' => array( 'href' => array() ) ) ) . '</p></div>';
                }
            }

            if( !defined( 'PMS_VERSION' ) ){
                if ( $pms_installation_status === 'no_action_requested') {
                    echo '<div class="notice notice-info is-dismissible"><p>';
                    echo '<strong>' . esc_html( PAID_MEMBER_SUBSCRIPTIONS ) . '</strong></p><p>';
                    printf( esc_html__( 'Please install and activate the Paid Member Subscriptions plugin', 'paid-member-subscriptions' ) );
                    echo '</p>';
                    echo '<p><a href="' . esc_url( add_query_arg( array( 'action' => 'pms_install_pms_plugin', 'nonce' => wp_create_nonce( 'pms_install_pms_plugin' ) ) ) ) . '" type="button" class="button-primary">' . esc_html__( 'Install & Activate', 'paid-member-subscriptions' ) . '</a></p>';
                    echo '</div>';
                }
            }
            else{
                if( version_compare( PMS_VERSION, '2.5.0', '<' ) ){
                    echo '<div class="notice notice-info is-dismissible"><p>';
                    echo esc_html( sprintf(__('Please update the Paid Member Subscriptions plugin to version 2.5.0 at least for %s to work properly', 'paid-member-subscriptions'), PAID_MEMBER_SUBSCRIPTIONS ) );
                    echo '</p></div>';
                }
            }
        }

        /**
         * Function that determines if an add-on is active or not
         * @param $bool
         * @param $slug
         * @return mixed
         */
        function pms_check_add_ons_activation( $bool, $slug ){
            $pms_add_ons_settings = get_option( 'pms_add_ons_settings', array() );
            if( !empty( $pms_add_ons_settings[$slug] ) )
                $bool = $pms_add_ons_settings[$slug];

            return $bool;
        }

        /**
         * Function that activates a PMS add-on
         */
        function pms_activate_add_ons( $slug ){
            $this->pms_activate_or_deactivate_add_on( $slug, true );
        }

        /**
         * Function that deactivates a PMS add-on
         */

        function pms_deactivate_add_ons( $slug ){
            $this->pms_activate_or_deactivate_add_on( $slug, false );
        }


        /**
         * Function used to activate or deactivate a PMS add-on
         */
        function pms_activate_or_deactivate_add_on( $slug, $action ){
            $pms_add_ons_settings = get_option( 'pms_add_ons_settings', array() );
            $pms_add_ons_settings[$slug] = $action;
            update_option( 'pms_add_ons_settings', $pms_add_ons_settings );
        }


        /**
         * Check if an addon was active as a slug before it was programmatically deactivated by us
         * On the plugin updates, where we transitioned add-ons we save the status in an option 'pms_old_add_ons_status'
         * @param $slug
         * @return false
         */
        function was_addon_active_as_plugin( $slug ){
            $old_add_ons_status = get_option( 'pms_old_add_ons_status' );
            if( isset( $old_add_ons_status[$slug] ) )
                return $old_add_ons_status[$slug];
            else
                return false;
        }

        /**
         * Function that returns the slugs of old addons that were plugins
         * @return string[]
         */
        function get_old_addons_slug_list(){
            $old_addon_list = array(
                'pms-add-on-bbpress/index.php',
                'pms-add-on-content-dripping/index.php',
                'pms-add-on-discount-codes/index.php',
                'pms-add-on-email-reminders/index.php',
                'pms-add-on-member-subscription-fixed-period/index.php',
                'pms-add-on-global-content-restriction/index.php',
                'pms-add-on-group-memberships/index.php',
                'pms-add-on-invoices/index.php',
                //'pms-add-on-labels-edit/index.php', // this is handled in the free version as it is a free addon and people might not install the paid versions
                'pms-add-on-multiple-subscriptions-per-user/index.php',
                'pms-add-on-navigation-menu-filtering/index.php',
                'pms-add-on-pay-what-you-want/index.php',
                'pms-add-on-paypal-express-pro/index.php',
                'pms-add-on-paypal-standard-recurring-payments/index.php',
                'pms-add-on-stripe/index.php',
                'pms-add-on-tax/index.php',
            );

            return $old_addon_list;
        }


        /**
         * Deactivate the old addons as plugins
         */
        function disable_old_add_ons(){

            //if it's triggered in the frontend we need this include
            if( !function_exists('is_plugin_active') )
                include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

            $old_addons_list = $this->get_old_addons_slug_list();
            $deactivated_addons = 0;

            $old_add_ons_status = get_option( 'pms_old_add_ons_status', array() );

            foreach( $old_addons_list as $addon_slug ){
                if( is_plugin_active($addon_slug) ){

                    if( !isset( $old_add_ons_status[$addon_slug] ) )//construct here the old add-ons status just once
                        $old_add_ons_status[$addon_slug] = true;

                    if( is_multisite() ){
                        if( is_plugin_active_for_network($addon_slug) )
                            deactivate_plugins($addon_slug, true);
                        else
                            deactivate_plugins($addon_slug, true, false);
                    }
                    else {
                        deactivate_plugins($addon_slug, true);
                    }
                    $deactivated_addons++;
                }
                else{
                    if( !isset( $old_add_ons_status[$addon_slug] ) )
                        $old_add_ons_status[$addon_slug] = false;
                }
            }
            if ( isset( $_GET['activate'] ) && $deactivated_addons === 1 ){
                add_action( 'load-plugins.php',
                    function(){
                        add_action( 'in_admin_header',
                            function(){
                                add_filter( 'gettext', array( $this, 'disable_old_add_ons_notice' ), 99, 3 );
                            }
                        );
                    }
                );
            } elseif ( isset( $_GET['activate-multi'] ) && $deactivated_addons !== 0 ){
                add_action( 'admin_notices', array( $this, 'disable_old_add_ons_notice_multi' ) );
            }


            if( !empty( $old_add_ons_status ) ){
                $old_add_ons_option = get_option( 'pms_old_add_ons_status', array() );
                if( empty( $old_add_ons_option ) )
                    update_option( 'pms_old_add_ons_status', $old_add_ons_status );//this should not change

                $add_ons_settings = get_option( 'pms_add_ons_settings', array() );
                if( empty( $add_ons_settings ) ) {
                    update_option('pms_add_ons_settings', $old_add_ons_status);//this should be set just once
                }
            }
        }

        /**
         * Modify the output of the notification when trying to activate an old addon
         * @param $translated_text
         * @param $untranslated_text
         * @param $domain
         * @return mixed|string
         */
        function disable_old_add_ons_notice( $translated_text, $untranslated_text, $domain )
        {
            $old = array(
                "Plugin activated."
            );

            $new = "This Paid Member Subscriptions add-on has been migrated to the main plugin and is no longer used. You can delete it.";

            if ( in_array( $untranslated_text, $old, true ) )
            {
                $translated_text = $new;
                remove_filter( current_filter(), __FUNCTION__, 99 );
            }
            return $translated_text;
        }

        /**
         * Modify the output of the notification when trying to activate an old addon
         */
        function disable_old_add_ons_notice_multi() {
            ?>
            <div id="message" class="updated notice is-dismissible">
                <p><?php esc_html_e( 'This Paid Member Subscriptions add-on has been migrated to the main plugin and is no longer used. You can delete it.', 'paid-member-subscriptions' ); ?></p>
            </div>
            <?php
        }


        /**
         * Function that includes the add-ons from the main plugin
         */
        function include_addons(){

            $add_ons_settings = get_option( 'pms_add_ons_settings', array() );

            if( !empty( $add_ons_settings ) ){
                foreach( $add_ons_settings as $add_on_slug => $add_on_enabled ){
                    if( $add_on_enabled ){

                        //include here the basic addons
                        if( $add_on_slug === 'pms-add-on-bbpress/index.php' && file_exists(plugin_dir_path( __FILE__ ) . '/add-ons-basic/bbpress/index.php') )
                            require_once plugin_dir_path(__FILE__) . '/add-ons-basic/bbpress/index.php';
                        if( $add_on_slug === 'pms-add-on-discount-codes/index.php' && file_exists(plugin_dir_path( __FILE__ ) . '/add-ons-basic/discount-codes/index.php') && !file_exists( WP_PLUGIN_DIR . '/paid-member-subscriptions/includes/features/discount-codes/index.php' ) )
                            require_once plugin_dir_path(__FILE__) . '/add-ons-basic/discount-codes/index.php';
                        if( $add_on_slug === 'pms-add-on-email-reminders/index.php' && file_exists(plugin_dir_path( __FILE__ ) . '/add-ons-basic/email-reminders/index.php') )
                            require_once plugin_dir_path(__FILE__) . '/add-ons-basic/email-reminders/index.php';
                        if( $add_on_slug === 'pms-add-on-member-subscription-fixed-period/index.php' && file_exists(plugin_dir_path( __FILE__ ) . '/add-ons-basic/fixed-period-membership/index.php') )
                            require_once plugin_dir_path(__FILE__) . '/add-ons-basic/fixed-period-membership/index.php';
                        if( $add_on_slug === 'pms-add-on-global-content-restriction/index.php' && file_exists(plugin_dir_path( __FILE__ ) . '/add-ons-basic/global-content-restriction/index.php') )
                            require_once plugin_dir_path(__FILE__) . '/add-ons-basic/global-content-restriction/index.php';
                        if( $add_on_slug === 'pms-add-on-navigation-menu-filtering/index.php' && file_exists(plugin_dir_path( __FILE__ ) . '/add-ons-basic/navigation-menu-filtering/index.php') )
                            require_once plugin_dir_path(__FILE__) . '/add-ons-basic/navigation-menu-filtering/index.php';
                        if( $add_on_slug === 'pms-add-on-pay-what-you-want/index.php' && file_exists(plugin_dir_path( __FILE__ ) . '/add-ons-basic/pay-what-you-want/index.php') )
                            require_once plugin_dir_path(__FILE__) . '/add-ons-basic/pay-what-you-want/index.php';
                        if( $add_on_slug === 'pms-add-on-learndash/index.php' && file_exists(plugin_dir_path( __FILE__ ) . '/add-ons-basic/learndash/index.php') )
                            require_once plugin_dir_path(__FILE__) . '/add-ons-basic/learndash/index.php';

                        //include here the PRO addons
                        if( $add_on_slug === 'pms-add-on-content-dripping/index.php' && file_exists(plugin_dir_path( __FILE__ ) . '/add-ons-pro/content-dripping/index.php') )
                            require_once plugin_dir_path(__FILE__) . '/add-ons-pro/content-dripping/index.php';
                        if( $add_on_slug === 'pms-add-on-group-memberships/index.php' && file_exists(plugin_dir_path( __FILE__ ) . '/add-ons-pro/group-memberships/index.php') )
                            require_once plugin_dir_path(__FILE__) . '/add-ons-pro/group-memberships/index.php';
                        if( $add_on_slug === 'pms-add-on-invoices/index.php' && file_exists(plugin_dir_path( __FILE__ ) . '/add-ons-pro/invoices/index.php') )
                            require_once plugin_dir_path(__FILE__) . '/add-ons-pro/invoices/index.php';
                        if( $add_on_slug === 'pms-add-on-multiple-subscriptions-per-user/index.php' && file_exists(plugin_dir_path( __FILE__ ) . '/add-ons-pro/multiple-subscriptions-per-user/index.php') )
                            require_once plugin_dir_path(__FILE__) . '/add-ons-pro/multiple-subscriptions-per-user/index.php';
                        if( $add_on_slug === 'pms-add-on-paypal-express-pro/index.php' && file_exists(plugin_dir_path( __FILE__ ) . '/add-ons-pro/paypal-express-pro/index.php') )
                            require_once plugin_dir_path(__FILE__) . '/add-ons-pro/paypal-express-pro/index.php';
                        if( $add_on_slug === 'pms-add-on-paypal-standard-recurring-payments/index.php' && file_exists( plugin_dir_path( __FILE__ ) . '/add-ons-pro/paypal-standard-recurring-payments/index.php' ) && !file_exists( WP_PLUGIN_DIR . '/paid-member-subscriptions/includes/gateways/paypal_standard/functions-paypal-standard-recurring.php' ) )
                            require_once plugin_dir_path(__FILE__) . '/add-ons-pro/paypal-standard-recurring-payments/index.php';
                        if( $add_on_slug === 'pms-add-on-stripe/index.php' && file_exists(plugin_dir_path( __FILE__ ) . '/add-ons-pro/stripe/index.php') )
                            require_once plugin_dir_path(__FILE__) . '/add-ons-pro/stripe/index.php';
                        if( $add_on_slug === 'pms-add-on-tax/index.php' && file_exists(plugin_dir_path( __FILE__ ) . '/add-ons-pro/tax/index.php') )
                            require_once plugin_dir_path(__FILE__) . '/add-ons-pro/tax/index.php';
                        if( $add_on_slug === 'pms-add-on-pro-rate/index.php' && file_exists(plugin_dir_path( __FILE__ ) . '/add-ons-pro/pro-rate/index.php') )
                            require_once plugin_dir_path(__FILE__) . '/add-ons-pro/pro-rate/index.php';
                        if( $add_on_slug === 'pms-add-on-files-restriction/index.php' && file_exists( plugin_dir_path( __FILE__ ) . '/add-ons-pro/files-restriction/index.php' ) )
                            require_once plugin_dir_path(__FILE__) . '/add-ons-pro/files-restriction/index.php';
    
                    }
                }
            }

        }


        /**
         * Include add-on files that contain activation hooks even when add-ons are deactivated
         *
         * Necessary in order to perform actions during the operation of activation or deactivation of that add-on
         */
        function include_mandatory_addon_files(){

            if( file_exists(plugin_dir_path( __FILE__ ) . '/add-ons-basic/email-reminders/email-reminders-activator.php') )
                require_once plugin_dir_path(__FILE__) . '/add-ons-basic/email-reminders/email-reminders-activator.php';

            if( file_exists(plugin_dir_path( __FILE__ ) . '/add-ons-basic/fixed-period-membership/fixed-period-membership-activator.php') )
                require_once plugin_dir_path(__FILE__) . '/add-ons-basic/fixed-period-membership/fixed-period-membership-activator.php';

            if ( file_exists( plugin_dir_path( __FILE__ ) . '/add-ons-pro/invoices/invoices-activator.php' ) )
                require_once plugin_dir_path( __FILE__ ) . '/add-ons-pro/invoices/invoices-activator.php';

            if ( file_exists( plugin_dir_path( __FILE__ ) . '/add-ons-pro/paypal-standard-recurring-payments/paypal-standard-recurring-payments-activator.php' ) )
                require_once plugin_dir_path( __FILE__ ) . '/add-ons-pro/paypal-standard-recurring-payments/paypal-standard-recurring-payments-activator.php';

            if ( file_exists( plugin_dir_path( __FILE__ ) . '/add-ons-pro/group-memberships/group-memberships-activator.php' ) )
                require_once plugin_dir_path( __FILE__ ) . '/add-ons-pro/group-memberships/group-memberships-activator.php';

            if ( file_exists( plugin_dir_path( __FILE__ ) . '/add-ons-pro/tax/tax-activator.php' ) )
                require_once plugin_dir_path( __FILE__ ) . '/add-ons-pro/tax/tax-activator.php';

            if( file_exists(plugin_dir_path( __FILE__ ) . '/add-ons-pro/files-restriction/files-restriction-activator.php') )     
                require_once plugin_dir_path(__FILE__) . '/add-ons-pro/files-restriction/files-restriction-activator.php';

            if ( file_exists( plugin_dir_path( __FILE__ ) . '/add-ons-pro/functions.php' ) )
                require_once plugin_dir_path( __FILE__ ) . '/add-ons-pro/functions.php';

        }

        /**
         * If action and nonce are set, attempt installing and activating PMS Free
         *
         * @return string 'no_action_requested' || 'error_activating' || 'plugin_activated'
         */
        public function install_activate_pms(){
            if ( isset( $_REQUEST['pms_install_pms_plugin_success'] ) && $_REQUEST['pms_install_pms_plugin_success'] === 'true' ){
                return 'plugin_activated';
            }

            if (
                isset( $_REQUEST['action'] ) && !empty($_REQUEST['nonce']) && $_REQUEST['action'] === 'pms_install_pms_plugin' &&
                !isset( $_REQUEST['pms_install_pms_plugin_success']) &&
                current_user_can( 'manage_options' ) &&
                wp_verify_nonce( sanitize_text_field( $_REQUEST['nonce'] ), 'pms_install_pms_plugin' )
            ) {
                $plugin_slug = 'paid-member-subscriptions/index.php';

                $installed = true;
                if ( !$this->is_plugin_installed( $plugin_slug ) ){
                    $plugin_zip = 'https://downloads.wordpress.org/plugin/paid-member-subscriptions.zip';
                    $installed = $this->install_plugin($plugin_zip);
                }

                if ( !is_wp_error( $installed ) && $installed ) {
                    $activate = activate_plugin( $plugin_slug );

                    if ( is_null( $activate ) ) {
                        wp_safe_redirect( add_query_arg( 'pms_install_pms_plugin_success', 'true' ) );
                        return 'plugin_activated';
                    }
                }

                return 'error_activating';
            }

            return 'no_action_requested';
        }

        /**
         * Check if plugin is installed
         *
         * @param $plugin_slug
         * @return bool
         */
        public function is_plugin_installed( $plugin_slug ) {
            if ( !function_exists( 'get_plugins' ) ) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            $all_plugins = get_plugins();

            if ( !empty( $all_plugins[ $plugin_slug ] ) ) {
                return true;
            }

            return false;
        }

        /**
         * Install plugin by providing downloadable zip address
         *
         * @param $plugin_zip
         * @return array|bool|WP_Error
         */
        public function install_plugin( $plugin_zip ) {
            include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
            wp_cache_flush();
            $upgrader  = new Plugin_Upgrader();

            // do not output any messages
            $upgrader->skin = new Automatic_Upgrader_Skin();

            $installed = $upgrader->install( $plugin_zip );
            return $installed;
        }

    }

    //initialize the handle of the included addons
    $pms_add_ons_handler = new PMS_Handle_Included_Addons();
}

if( !defined( 'PAID_MEMBER_SUBSCRIPTIONS' ) )
    define( 'PAID_MEMBER_SUBSCRIPTIONS', 'Paid Member Subscriptions Pro' );

register_activation_hook(__FILE__, 'pms_pro_activate');
function pms_pro_activate( $network_wide ) {
    if( !function_exists('is_plugin_active') )
        include_once( ABSPATH . '/wp-admin/includes/plugin.php' );

    if( is_plugin_active('paid-member-subscriptions-basic/index.php') || is_plugin_active('paid-member-subscriptions-agency/index.php') || is_plugin_active('paid-member-subscriptions-unlimited/index.php') ){
        set_transient( 'pms_deactivate_pro', true );
    }
}


add_action('admin_notices', 'pms_pro_admin_notice');
function pms_pro_admin_notice(){
    $pms_deactivate_pro = get_transient( 'pms_deactivate_pro' );
    if( $pms_deactivate_pro ){

        $other_plugin_name = '';
        if( is_plugin_active('paid-member-subscriptions-basic/index.php') )
            $other_plugin_name = 'Paid Member Subscriptions - Basic';
        else if( is_plugin_active('paid-member-subscriptions-agency/index.php') )
            $other_plugin_name = 'Paid Member Subscriptions - Agency';
        else if( is_plugin_active('paid-member-subscriptions-unlimited/index.php') )
            $other_plugin_name = 'Paid Member Subscriptions - Unlimited';
        ?>
        <div class="error">
            <p>
                <?php
                /* translators: %s is the plugin version name */
                echo wp_kses_post( sprintf( __( '%s is also activated. You need to deactivate it before activating this version of the plugin.', 'paid-member-subscriptions'), $other_plugin_name ) );
                ?>
            </p>
        </div>
        <?php
        delete_transient( 'pms_deactivate_pro' );
    }
}

add_action( 'admin_init', 'pms_pro_plugin_deactivate' );
function pms_pro_plugin_deactivate() {

    $pms_deactivate_pro = get_transient( 'pms_deactivate_pro' );
    if( $pms_deactivate_pro ){
        deactivate_plugins( plugin_basename( __FILE__ ) );
    }
    unset($_GET['activate']);
}

function pms_pro_add_plugin_action_links( $links ) {

    if ( current_user_can( 'manage_options' ) ) {

        $addons_url = sprintf( '<a href="%1$s">%2$s</a>', menu_page_url( 'pms-addons-page', false ), esc_html( __( 'Add-ons', 'paid-member-subscriptions' ) ) );

        array_unshift( $links, $addons_url );

    }

    return $links;

}
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'pms_pro_add_plugin_action_links' );

add_filter( 'pms_update_check_licence_api_params', 'pms_pro_add_extra_data_to_licence_check_requests' );
function pms_pro_add_extra_data_to_licence_check_requests( $data ){

    if( empty( $data ) || pms_is_payment_test_mode() )
        return $data;

    global $wpdb;

    $result = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(*) as payments_count FROM {$wpdb->prefix}pms_payments WHERE `status` = %s", 'completed' ), 'ARRAY_A' );

    if( !empty( $result['payments_count'] ) )
        $data['payments_count'] = $result['payments_count'];

    $result = $wpdb->get_row( $wpdb->prepare( "SELECT SUM(amount) as total FROM {$wpdb->prefix}pms_payments WHERE `status` = %s", 'completed' ), 'ARRAY_A' );

    if( !empty( $result['total'] ) )
        $data['payments_revenue'] = round( $result['total'] );

    $result = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(DISTINCT user_id) as unique_customers FROM {$wpdb->prefix}pms_payments WHERE `status` = %s", 'completed' ), 'ARRAY_A' );

    $data['unique_customers'] = $result['unique_customers'];

    $data['active_currency'] = pms_get_active_currency();

    return $data;

}
