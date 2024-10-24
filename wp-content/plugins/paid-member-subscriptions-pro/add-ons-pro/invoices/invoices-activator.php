<?php

/**
 * The code that runs during plugin activation.
 */

if( !function_exists( 'pms_in_inv_set_cron_jobs' ) ){

    function pms_in_inv_set_cron_jobs( $addon ) {

		if( $addon == 'pms-add-on-invoices/index.php' ){

			if( ! wp_next_scheduled( 'pms_inv_cron_job_reset_yearly' ) )
				wp_schedule_event( time(), 'hourly', 'pms_inv_cron_job_reset_yearly' );
				
		}

	}
    add_action( 'pms_add_ons_activate', 'pms_in_inv_set_cron_jobs', 10, 1);

}

if( !function_exists( 'pms_in_inv_unset_cron_jobs' ) ){

    function pms_in_inv_unset_cron_jobs( $addon ) {

		if( $addon == 'pms-add-on-invoices/index.php' )
			wp_clear_scheduled_hook( 'pms_inv_cron_job_reset_yearly' );

	}
    add_action( 'pms_add_ons_deactivate','pms_in_inv_unset_cron_jobs', 10, 1);

}
