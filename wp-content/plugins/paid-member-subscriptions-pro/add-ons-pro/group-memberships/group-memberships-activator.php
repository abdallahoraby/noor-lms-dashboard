<?php

/**
 * The code that runs during plugin activation.
 */

if( !function_exists( 'pms_in_gm_first_installation_setup' ) ){

    function pms_in_gm_first_installation_setup( $addon ) {

        if( $addon == 'pms-add-on-group-memberships/index.php' ) {
            if( get_option( 'pms_gm_first_activation', false ) === false ){

                update_option( 'pms_gm_first_activation', time() );

                $email_settings = get_option( 'pms_emails_settings', array() );

                $email_settings['invite_is_enabled'] = 'yes';

                update_option( 'pms_emails_settings', $email_settings );

            }
        }

	}
    add_action( 'pms_add_ons_activate', 'pms_in_gm_first_installation_setup', 10, 1);

}
