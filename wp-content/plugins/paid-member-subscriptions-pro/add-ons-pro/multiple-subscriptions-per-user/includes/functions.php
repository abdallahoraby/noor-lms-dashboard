<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;


/**
 * Returns the output for the add new subscription members subpage
 *
 * @param string $output
 *
 * @return string
 *
 */
function pms_in_msu_output_add_new_subscription_subpage( $output = '' ) {

    if( empty( $_GET['member_id'] ) )
        return $output;

    if( empty( $_GET['subpage'] ) || $_GET['subpage'] != 'add_subscription' )
        return $output;


    ob_start();

    if( file_exists( PMS_PLUGIN_DIR_PATH . 'includes/admin/views/view-page-members-add-new-edit-subscription.php' ) )
        include PMS_PLUGIN_DIR_PATH . 'includes/admin/views/view-page-members-add-new-edit-subscription.php';

    $output = ob_get_contents();

    ob_clean();

    return $output;

}
add_filter( 'pms_submenu_page_members_output', 'pms_in_msu_output_add_new_subscription_subpage' );


/*
 * Add new button for subscription plans allows you to add top level subscription plan
 *
 */
function pms_in_msu_add_subscription_plan_action( $action ) {
    return 'allow';
}
add_filter( 'pms_action_add_new_subscription_plan', 'pms_in_msu_add_subscription_plan_action' );


/*
 * Add the "Add New Subscription" button on members add/edit list table
 *
 */
function pms_in_msu_member_subscription_list_table_add_new_button( $which, $member, $existing_subscriptions ) {

    if( $which == 'bottom' ) {

        // NOTE: This function returns the number of groups. For Inactive plans, it counts it as a group only if the plan is not single, so it has some child plans, part of a tier
        // When it counts active plans it doesn't care about this. Weird
        $subscription_groups_count = pms_in_get_subscription_plan_groups_count();

        
        // NOTE: If there are inactive plans that are single and the user is subscribed to them, we need to remove these from the member subscriptions count
        // in order for this check to be valid. Else, the group count from above might say 5 and the user could be subscribed to 5 subs that are inactive and the button would
        // not show
        
        $member_subscriptions_count = 0;

        if( !empty( $member->subscriptions ) ){
            $member_subscriptions_count = count( $member->subscriptions );

            foreach( $member->subscriptions as $subscription ){
                $plan = pms_get_subscription_plan( $subscription['subscription_plan_id'] );

                if( !$plan->is_active() )
                    $member_subscriptions_count = $member_subscriptions_count - 1;
            }
        }

        if( ( $subscription_groups_count > 1 && $member_subscriptions_count < $subscription_groups_count ) ) {
            echo '<a href="' . esc_url( add_query_arg( array( 'page' => 'pms-members-page', 'subpage' => 'add_subscription', 'member_id' => $member->user_id ), admin_url( 'admin.php' ) ) ) . '" class="button-primary">' . esc_html__( 'Add New Subscription', 'paid-member-subscriptions' ) . '</a>';
        }

        echo '<input id="pms-subscription-groups-count" type="hidden" value="' . esc_attr( $subscription_groups_count ) . '" />';

    }

}
add_action( 'pms_member_subscription_list_table_extra_tablenav', 'pms_in_msu_member_subscription_list_table_add_new_button', 10, 3 );

function pms_in_msu_nav_menu_extra_fields( $item ) {
    if( empty( $item->type ) )
        return;

    if( strpos( $item->type, 'pms_') === false || $item->type == 'pms_logout' )
        return;

    if( !( pms_in_get_subscription_plan_groups_count() > 1 ) )
        return;

    $selected_subscription = get_post_meta( $item->ID, '_pms_msu_nav_menu_subscription', true );
    ?>

    <div class="pms-options">
        <p class="description"><?php esc_html_e( 'Subscription plan', 'paid-member-subscriptions' ); ?></p>

        <label class="pms-menu-item-msu-subscription-label" for="pms-menu-li-<?php echo esc_attr( $item->ID ); ?>">

            <select id="pms-msu-subscription" name="pms-msu-subscription-<?php echo esc_attr( $item->ID ); ?>" class="widefat code edit-menu-item-url">
                <option value="-1"><?php esc_html_e( 'Choose...', 'paid-member-subscriptions' ) ?></option>

                <?php
                foreach( pms_get_subscription_plans_list() as $plan_id => $plan_title )
                    echo '<option value="' . esc_attr( $plan_id ) . '"' . selected( $selected_subscription, $plan_id, false ) . '>' . esc_html( $plan_title ) . ' (ID: ' . esc_attr( $plan_id ) . ')' . '</option>';
                ?>
            </select>

        </label>
    </div>

    <?php
}
add_action( 'pms_nav_menu_extra_fields_top', 'pms_in_msu_nav_menu_extra_fields' );

function pms_in_msu_nav_menu_save( $menu_id, $menu_item_db_id ) {

    if( !empty( $_REQUEST['pms-msu-subscription-' . $menu_item_db_id] ) )
        update_post_meta( $menu_item_db_id, '_pms_msu_nav_menu_subscription', sanitize_text_field( $_REQUEST['pms-msu-subscription-' . $menu_item_db_id] ) );
    else
        delete_post_meta( $menu_item_db_id, '_pms_msu_nav_menu_subscription' );

}
add_action( 'wp_update_nav_menu_item', 'pms_in_msu_nav_menu_save', 10, 2 );


/*
 * Return the number of subscription plan groups
 *
 */
 function pms_in_get_subscription_plan_groups_count() {

     $active_parent_plans = get_posts( array( 'post_type' => 'pms-subscription', 'numberposts' => -1, 'post_parent' => 0, 'post_status' => 'any', 'meta_key' => 'pms_subscription_plan_status', 'meta_value' => 'active' ) );

     $active_tiers = count( $active_parent_plans );

     //get inactive parent plans
     $inactive_parent_plans = get_posts( array( 'post_type' => 'pms-subscription', 'numberposts' => -1, 'post_parent' => 0, 'post_status' => 'any', 'meta_key' => 'pms_subscription_plan_status', 'meta_value' => 'inactive' ) );

     foreach ( $inactive_parent_plans as $plan ) {
         $tier = pms_get_subscription_plans_group( $plan->ID, true );

         if ( !empty( $tier ) && isset( $tier[0]->id ) )
             $active_tiers = $active_tiers + 1;
     }


     return $active_tiers;

 }

/*
* Return the active of subscription plan ids
*
*/
 function pms_in_get_active_subscription_plan_ids(){

     $active_parent_plans = get_posts( array( 'post_type' => 'pms-subscription', 'numberposts' => -1, 'post_parent' => 0, 'post_status' => 'any', 'meta_key' => 'pms_subscription_plan_status', 'meta_value' => 'active' ) );
     $active_plan_ids = array();

     foreach ( $active_parent_plans as $plan ){
         $active_plan_ids[] = $plan->ID;
     }

     return $active_plan_ids;
 }