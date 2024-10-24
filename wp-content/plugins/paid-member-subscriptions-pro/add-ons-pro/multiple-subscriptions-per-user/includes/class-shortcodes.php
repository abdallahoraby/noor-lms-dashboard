<?php
/*
 * Extends PMS Shortcodes class
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;


Class PMS_IN_MSU_Shortcodes extends PMS_Shortcodes {

    /*
     * Hook methods on init
     *
     */
    public static function init() {

        remove_shortcode( 'pms-subscriptions' );
        add_shortcode( 'pms-subscriptions', __CLASS__ . '::subscriptions_form' );

    }

    /*
     * Overwrite new subscription form from parent class
     *
     */
    public static function subscriptions_form( $atts ) {

        $atts = shortcode_atts( array(
            'subscription_plans' => array(),
            'exclude'            => array(),
            'selected'           => ''
        ), $atts );


        /*
         * Sanitize attributes
         */
        if( ! empty( $atts['subscription_plans'] ) )
            $atts['subscription_plans'] = array_map( 'trim', explode(',', $atts['subscription_plans'] ) );

        if( ! empty( $atts['exclude'] ) )
            $atts['exclude'] = array_map( 'trim', explode( ',', $atts['exclude'] ) );

        // Start catching the contents of the new subscription form
        ob_start();

        if( is_user_logged_in() ) {

            $member = pms_get_member( pms_get_current_user_id() );

            // Exclude subscription
            if( $member->get_subscriptions_count() > 0 ) {
                foreach( $member->subscriptions as $member_subscription )
                    array_push( $atts['exclude'], $member_subscription['subscription_plan_id'] );
            }


            // Check to see if the member is subscribed to all subscription plans provided
            $array_dif = array_diff( $atts['subscription_plans'], $member->get_subscriptions_ids() );

            //  need to take into account if a provided subscription_plan is not assigned to the member but is a downgrade of one that is assigned
            if( !empty( $array_dif ) ){
                foreach( $array_dif as $key => $plan ){
                    $current_subscription = pms_get_current_subscription_from_tier( get_current_user_id(), $plan );

                    if( !empty( $current_subscription ) )
                        unset( $array_dif[$key] );
                }
            }

            // Display the form where the user can subscribe to new plans if he/she is not subscribed to
            // every subscription plan group/tree
            if( ( $member->get_subscriptions_count() >= 0 && count( array_intersect( $member->get_subscriptions_ids(), pms_in_get_active_subscription_plan_ids() ) ) < count( pms_in_get_active_subscription_plan_ids() ) ) && ( empty( $atts['subscription_plans'] ) || !empty( $array_dif ) ) ) {
                if( $member->get_subscriptions_count() > 0 ){
                    echo apply_filters( 'pms_mspu_show_account_before_buy_new_plans', do_shortcode( '[pms-account show_tabs="no"]' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                }
                // Don't load new subscription form if we're doing an action from [pms-account]
                if( !isset( $_GET['pms-action'] ) ){
                    echo '<h3 class="pms-mspu-form-heading">'. esc_html__( 'Select subscription plan', 'paid-member-subscriptions' ) .'</h3>';

                    include PMS_PLUGIN_DIR_PATH . 'includes/views/shortcodes/view-shortcode-new-subscription-form.php';
                }

            // If the user is subscribed to all possible plans display the pms-account so he/she can view hes/hers subscriptions
            } else {

                echo apply_filters( 'pms_subscriptions_form_already_a_member', do_shortcode( '[pms-account show_tabs="no"]' ), $atts, $member ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

            }

        } else {

            echo '<p class="pms-alert" >' . esc_html( apply_filters( 'pms_subscriptions_form_not_logged_in_message', __( 'Only registered users can see this information.', 'paid-member-subscriptions' ) ) ) . '</p>';

        }

        // Get the contents and clean the buffer
        $output = ob_get_contents();
        ob_end_clean();

        return apply_filters( 'pms_subscriptions_form_content', $output, $atts );

    }

}
add_action( 'init', array( 'PMS_IN_MSU_Shortcodes', 'init' ) );
