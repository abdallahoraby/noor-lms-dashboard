<style type="text/css">

</style>
<?php
/*
 * HTML output for subscription plan content restrictions meta-box
 */
$nr_of_rules = get_post_meta( $subscription_plan->id, 'pms_nr_of_rules', true );
if( empty( $nr_of_rules ) || $nr_of_rules == 0 )
    $nr_of_rules = 1;

echo PMS_Meta_Box_Subscription_Content_Restriction::pms_output_content_restriction_row( $subscription_plan, $nr_of_rules ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped