<?php

/**
 * Add/Remove .htaccess rules when the File Restriction Add-On is activated or deactivated
 *
 */

if( !function_exists( 'pms_file_restriction_rewrite_htaccess_rules' ) ){
     function pms_file_restriction_rewrite_htaccess_rules( $slug ) {

        if ( $slug !== 'pms-add-on-files-restriction/index.php' )
            return;

         if ( !defined( 'PMS_IN_FILESRESTRICTION_PLUGIN_DIR_PATH' ) ) {
             define( 'PMS_IN_FILESRESTRICTION_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
         }

        // we need access to pms_file_restriction_insert_htaccess_rule() function, for the .htaccess rules to update correctly on Add-On activation/deactivation
        require_once( PMS_IN_FILESRESTRICTION_PLUGIN_DIR_PATH . '/includes/files-restriction-admin.php' );

         $file_restriction_class = new PMS_Files_Restriction();
         $web_server = $file_restriction_class->pms_get_web_server();

         if ( $web_server === 'apache' ) {
             // reload the .htaccess file to update the File Restriction rules
             flush_rewrite_rules();
         }
         elseif ( $web_server === 'nginx' ) {
             // regenerate the File Restriction nginx config file
             $file_restriction_class->pms_file_restriction_rewrite_nginx_rules();

         }

    }
    add_action( 'pms_add_ons_activate', 'pms_file_restriction_rewrite_htaccess_rules', 10, 1);
    add_action( 'pms_add_ons_deactivate','pms_file_restriction_rewrite_htaccess_rules', 10, 1);
}


if( !function_exists( 'pms_file_restriction_activation_check' ) ){
    function pms_file_restriction_activation_check( $slug ) {

        if ( $slug !== 'pms-add-on-files-restriction/index.php' )
            return;

        $already_activated = get_option( 'pms_files_restriction_addon_already_activated', 'not_found' );

        if( $already_activated !== 'yes' ) {
            update_option( 'pms_files_restriction_addon_already_activated', 'yes' );
        }

    }

    add_action( 'pms_add_ons_activate',  'pms_file_restriction_activation_check', 10, 1 );
}

/**
 * Notify the user that Nginx web-server needs to be restarted
 *
 * - this admin notice is displayed when the File Restriction rules have been changed (activate/deactivate Add-On or File Restriction settings update)
 *
 */
     function pms_file_restriction_nginx_restart_notification() {

        // initiate the plugin notifications class
        $notifications = PMS_Plugin_Notifications::get_instance();

        // this must be unique
        $notification_id = 'pms_file_restriction_nginx_restart';

        // add notification text
        $notification_message = '<p style="font-size: 15px; margin-top:4px;">' . sprintf( __( 'The Nginx web server needs to be restarted for the new File Restriction rules to take effect. %1$sLearn more%2$s.', 'paid-member-subscriptions' ), '<a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/add-ons/files-restriction/#Nginx" target="_blank">', '</a>', '<br>' ) . '</p>';

        // set the add-on icon
        $ul_icon_url = ( file_exists( esc_url(PMS_PLUGIN_DIR_PATH . 'assets/images/addons/pms-add-on-pro-files-restriction-logo.png' ) ) ) ? esc_url(PMS_PLUGIN_DIR_URL . 'assets/images/addons/pms-add-on-pro-files-restriction-logo.png' ) : '';
        $ul_icon = ( !empty( $ul_icon_url ) ) ? '<img src="'. $ul_icon_url .'" width="64" height="64" style="float: left; margin: 15px 12px 15px 0; max-width: 100px;" alt="Paid Member Subscription PRO - Files Restriction">' : '';

        // create the notification content
        $message = $ul_icon;
        $message .= '<h3 style="margin-bottom: 0;">Paid Member Subscription PRO - Files Restriction </h3>';
        $message .= $notification_message;
        $message .= '<p><a href="' . add_query_arg( array( 'pms_dismiss_admin_notification' => $notification_id ) ) . '" type="button" class="notice-dismiss"><span class="screen-reader-text">' . __( 'Dismiss this notice.', 'paid-member-subscriptions' ) . '</span></a></p>';

        // add the notification  (we need to add the "notice is-dismissible" classes for the dismiss button to be correctly positioned)
        $notifications->add_notification( $notification_id, $message, 'pms-notice error notice is-dismissible', false );
    }

    // display this notification only if Nginx is in use (we don't need this for Apache)
    if ( pms_get_web_server() === 'nginx' ){

        $already_activated = get_option( 'pms_files_restriction_addon_already_activated', 'not_found' );

        if( $already_activated === 'yes' ) {

            add_action( 'admin_init','pms_file_restriction_nginx_restart_notification');
        }
    }



/**
 * Detect web_server in use
 *
 */
 function pms_get_web_server() {

    if ( isset( $_SERVER['SERVER_SOFTWARE'] ) ) {

        $server_software = strtolower( sanitize_text_field( $_SERVER['SERVER_SOFTWARE'] ) );

        if ( strpos( $server_software, 'apache' ) !== false ) {
            return 'apache';
        } elseif ( strpos( $server_software, 'nginx' ) !== false ) {
            return 'nginx';
        } else {
            return 'unknown';
        }

    }

    return 'unknown';
}
