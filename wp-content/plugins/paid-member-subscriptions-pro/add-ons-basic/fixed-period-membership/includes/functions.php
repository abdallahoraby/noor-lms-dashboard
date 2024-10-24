<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

/**
 * Output subscription plans excluding fixed period plans that are expired and cannot be renewed
 *
 * @param string $subscription_plan_output
 * @param PMS_Subscription_Plan $subscription_plan
 *
 * @return string
 *
 */
function pms_in_msfp_output_subscription_plans( $subscription_plan_output, $subscription_plan ){

    if( $subscription_plan->is_fixed_period_membership() && !$subscription_plan->fixed_period_renewal_allowed() && strtotime( $subscription_plan->get_expiration_date() ) < time() )
        return '';

    return $subscription_plan_output;

}
add_filter( 'pms_subscription_plan_output', 'pms_in_msfp_output_subscription_plans', 10, 2 );