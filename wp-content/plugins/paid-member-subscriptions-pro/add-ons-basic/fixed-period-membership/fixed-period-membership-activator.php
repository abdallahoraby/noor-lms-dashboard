<?php

/**
 * The code that runs during plugin activation.
 */

if( !function_exists( 'pms_in_msfp_install' ) ){

    function pms_in_msfp_install( $addon ) {

        if( $addon == 'pms-add-on-member-subscription-fixed-period/index.php' ){

            $option = get_option( 'pms_msfp_migration', array() );

            if( empty( $option ) ){

                $subscription_plans = pms_get_subscription_plans();

                foreach( $subscription_plans as $subscription_plan ){

                    if( isset( $subscription_plan->type ) && $subscription_plan->type == 'fixed-period' ){

                        update_post_meta( $subscription_plan->id, 'pms_subscription_plan_type', 'regular' );
                        update_post_meta( $subscription_plan->id, 'pms_subscription_plan_fixed_membership', 'on' );

                    }
                }

                add_option( 'pms_msfp_migration', 'msfp_migration' );

            }

        }

    }
    add_action( 'pms_add_ons_activate', 'pms_in_msfp_install', 10, 1);

}

if( !function_exists( 'pms_in_msfp_uninstall' ) ){

    function pms_in_msfp_uninstall( $addon ) {

        if( $addon == 'pms-add-on-member-subscription-fixed-period/index.php' ){

            $subscription_plans = pms_get_subscription_plans();

            foreach( $subscription_plans as $subscription_plan ){

                $fixed_period_membership = get_post_meta( $subscription_plan->id, 'pms_subscription_plan_fixed_membership' );
                if( !empty( $fixed_period_membership[0] ) && $fixed_period_membership[0] == 'on' )
                    update_post_meta( $subscription_plan->id, 'pms_subscription_plan_fixed_membership', '' );

            }
        }

    }
    add_action( 'pms_add_ons_deactivate','pms_in_msfp_uninstall', 10, 1);

}