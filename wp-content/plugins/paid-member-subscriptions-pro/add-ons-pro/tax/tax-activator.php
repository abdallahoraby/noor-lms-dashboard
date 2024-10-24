<?php

/**
 * The code that runs during plugin activation.
 */

if( !function_exists( 'pms_in_tax_install' ) ){

    function pms_in_tax_install( $addon ) {

        if( $addon == 'pms-add-on-tax/index.php' ){

            global $wpdb;

            //Handle multi-site installation
            if ( function_exists('is_multisite') && is_multisite() && $network_activate ) {

                $blogs_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );

                foreach ( $blogs_ids as $blogs_id ) {

                    switch_to_blog( $blogs_id );

                    // Create needed table
                    pms_in_tax_create_tables();

                    restore_current_blog();
                }
            }
            // Handle single site installation
            else {

                // Create needed table
                pms_in_tax_create_tables();

            }

        }

	}
    add_action( 'pms_add_ons_activate', 'pms_in_tax_install', 10, 1);

}

if( !function_exists( 'pms_in_tax_create_tables' ) ){

    function pms_in_tax_create_tables(){

        global $wpdb;

        $table_name = $wpdb->prefix . 'pms_tax_rates';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        tax_country varchar(191) NOT NULL DEFAULT '',
        tax_state varchar(191) NOT NULL DEFAULT '',
        tax_city varchar(191) NOT NULL DEFAULT '',
        tax_rate varchar(191) NOT NULL DEFAULT '',
        tax_name varchar(191) NOT NULL DEFAULT 'TAX',
        PRIMARY KEY  (id),
        KEY tax_country (tax_country),
        KEY tax_state (tax_state),
        KEY tax_city (tax_city),
        KEY tax_rate (tax_rate),
        KEY tax_name (tax_name)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );

    }
}