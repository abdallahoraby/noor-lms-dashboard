<?php
// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

class PMS_IN_FilesRestriction{
    /**
     * Constructor
     *
     */
    public function __construct() {

        define( 'PMS_IN_FILESRESTRICTION_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
        define( 'PMS_IN_FILESRESTRICTION_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );

        $this->include_files();
        $this->init();

    }

    /**
     * Include add-on files
     *
     */
    private function include_files() {

        if ( file_exists( PMS_IN_FILESRESTRICTION_PLUGIN_DIR_PATH . '/includes/files-restriction-admin.php' ) )
            include_once PMS_IN_FILESRESTRICTION_PLUGIN_DIR_PATH . '/includes/files-restriction-admin.php';

    }

    private function init() {

        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_files_restriction_admin_scripts' ) );

    }

    public function enqueue_files_restriction_admin_scripts( $hook )
    {
        if( 'paid-member-subscriptions_page_pms-settings-page' != $hook )
            return;
        if ( file_exists( PMS_IN_FILESRESTRICTION_PLUGIN_DIR_PATH . '/assets/js/pms-restrict-files-backend.js' ) )
            wp_enqueue_script( 'pms-restrict-files-backend.js', PMS_IN_FILESRESTRICTION_PLUGIN_DIR_URL . '/assets/js/pms-restrict-files-backend.js', array( 'jquery' ), PMS_VERSION );

        global $wp_scripts;

        // Try to detect if chosen has already been loaded
        $found_chosen = false;

        foreach( $wp_scripts as $wp_script ) {
            if( !empty( $wp_script['src'] ) && strpos($wp_script['src'], 'chosen') !== false )
                $found_chosen = true;
        }

        if( !$found_chosen ) {
            wp_enqueue_script( 'pms-chosen', PMS_PLUGIN_DIR_URL . 'assets/libs/chosen/chosen.jquery.min.js', array( 'jquery' ), PMS_VERSION );
            wp_enqueue_style( 'pms-chosen', PMS_PLUGIN_DIR_URL . 'assets/libs/chosen/chosen.css', array(), PMS_VERSION );
        }
    }


}
new PMS_IN_FilesRestriction();