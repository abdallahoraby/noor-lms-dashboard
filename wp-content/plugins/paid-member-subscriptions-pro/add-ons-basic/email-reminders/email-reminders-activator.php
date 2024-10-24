<?php

/**
 * The code that runs during plugin activation.
 */

if( !function_exists( 'pms_in_er_add_cron_job' ) ){

    function pms_in_er_add_cron_job( $addon ){

        if( $addon == 'pms-add-on-email-reminders/index.php' ){

            if ( ! wp_next_scheduled( 'pms_send_email_reminders_hourly' ) )
                wp_schedule_event( time(), 'hourly', 'pms_send_email_reminders_hourly', array( 'hourly' ) );

            if ( ! wp_next_scheduled( 'pms_send_email_reminders_daily' ) )
                wp_schedule_event( time(), 'daily', 'pms_send_email_reminders_daily', array( 'daily' ) );

        }

    }
    add_action( 'pms_add_ons_activate', 'pms_in_er_add_cron_job', 10, 1);

}

if( !function_exists( 'pms_in_er_clear_cron_job' ) ){

    function pms_in_er_clear_cron_job( $addon ){

        if( $addon == 'pms-add-on-email-reminders/index.php' ){

            wp_clear_scheduled_hook( 'pms_send_email_reminders_hourly', array( 'hourly' ) );
            wp_clear_scheduled_hook( 'pms_send_email_reminders_daily', array( 'daily' ) );
        
        }

    }
    add_action( 'pms_add_ons_deactivate','pms_in_er_clear_cron_job', 10, 1);

}
