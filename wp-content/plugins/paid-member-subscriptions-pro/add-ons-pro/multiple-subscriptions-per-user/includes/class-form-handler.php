<?php
/*
 * Extends PMS Form Handler class
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;


Class PMS_IN_MSU_Form_Handler extends PMS_Form_Handler {

    /*
     * Hook data processing methods on init
     *
     */
    public static function init() {

        parent::init();

        remove_action( 'init', array( get_parent_class(), 'new_subscription_form' ) );
        add_action( 'init', array( __CLASS__, 'new_subscription_form' ) );

    }

    /*
     * Validates when a member subscribes to a new plan
     *
     */
    public static function new_subscription_form() {

        // Verify nonce
        if( !isset( $_REQUEST['pmstkn'] ) || !wp_verify_nonce( sanitize_text_field( $_REQUEST['pmstkn'] ), 'pms_new_subscription_form_nonce' ) )
            return;

        if( !self::validate_subscription_plans() )
            return;


        // Extra validations
        do_action( 'pms_new_subscription_form_validation', $_POST );

        // Stop if there are errors
        if ( count( pms_errors()->get_error_codes() ) > 0 )
            return;

        // Hook useful for saving extra information
        do_action( 'pms_new_subscription_form_extra', get_current_user_id() );

        // Process checkout
        self::process_checkout();

    }

}
PMS_IN_MSU_Form_Handler::init();