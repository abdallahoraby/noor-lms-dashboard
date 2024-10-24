<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

// Given a subscription object, returns the number of total seats available
function pms_in_gm_get_total_seats( $subscription ){
    if( empty( $subscription->id ) )
        return 0;

    $subscription_seats = pms_get_member_subscription_meta( $subscription->id, 'pms_group_seats', true );

    if( !empty( $subscription_seats ) )
        return $subscription_seats;

    return get_post_meta( $subscription->subscription_plan_id, 'pms_subscription_plan_seats', true );
}

// Returns the total number of used seats
function pms_in_gm_get_used_seats( $subscription_id ){
    if( empty( $subscription_id ) )
        return false;

    $members = pms_in_gm_get_group_subscriptions( $subscription_id );

    $registered = count( $members ) + 1;

    $invited = pms_get_member_subscription_meta( $subscription_id, 'pms_gm_invited_emails' );

    return count( $invited ) + $registered;
}

// Return meta_id based on value and subscription id
function pms_in_gm_get_meta_id_by_value( $subscription_id, $value ){
    global $wpdb;

    $result = $wpdb->get_var( $wpdb->prepare( "SELECT meta_id FROM {$wpdb->prefix}pms_member_subscriptionmeta WHERE member_subscription_id = %d AND meta_value = %s", $subscription_id, $value ) );

    if( !empty( $result ) )
        return (int)$result;

    return false;
}

// Return meta_id based on key and subscription id
function pms_in_gm_get_meta_id_by_key( $subscription_id, $key ){
    global $wpdb;

    $result = $wpdb->get_var( $wpdb->prepare( "SELECT meta_id FROM {$wpdb->prefix}pms_member_subscriptionmeta WHERE member_subscription_id = %d AND meta_key = %s", $subscription_id, $key ) );

    if( !empty( $result ) )
        return (int)$result;

    return false;
}

// Given a meta key, returns all the existing values
function pms_in_gm_get_all_meta_values_by_key( $meta_key ){
    global $wpdb;

    $result = $wpdb->get_results( $wpdb->prepare( "SELECT meta_id, meta_value FROM {$wpdb->prefix}pms_member_subscriptionmeta WHERE meta_key = %s AND meta_value != ''", $meta_key ), 'ARRAY_A' );

    if( !empty( $result ) )
        return $result;

    return false;
}

// Return all values with the given meta key and value
function pms_in_gm_get_meta_by_value( $meta_key, $meta_value ){
    global $wpdb;

    $result = $wpdb->get_results( $wpdb->prepare( "SELECT member_subscription_id, meta_key, meta_value FROM {$wpdb->prefix}pms_member_subscriptionmeta WHERE meta_key = %s AND meta_value = %s", $meta_key, $meta_value ), 'ARRAY_A' );

    if( !empty( $result ) )
        return $result;

    return false;
}

// Get subscription user id based on subscription id
function pms_in_gm_get_member_subscription_user_id( $subscription_id ){
    global $wpdb;

    $result = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM {$wpdb->prefix}pms_member_subscriptions WHERE id = %d", $subscription_id ) );

    if( !empty( $result ) )
        return (int)$result;

    return false;
}

// Returns name if available, if not it defaults to the user email
function pms_in_gm_get_user_name( $user_id, $only_name = false ){
    if( empty( $user_id ) )
        return '';

    $first_name = get_user_meta( $user_id, 'first_name', true );

    if( !empty( $first_name ) ){
        $last_name = get_user_meta( $user_id, 'last_name', true );

        if( !empty( $last_name ) )
            return $first_name . ' ' . $last_name;
    }

    if( !$only_name ){
        $userdata = get_userdata( $user_id );
        return $userdata->user_email;
    }

    return '';
}

// Get user email based on id
function pms_in_gm_get_email_by_user_id( $user_id ){
    if( empty( $user_id ) )
        return;

    $userdata = get_userdata( $user_id );

    return $userdata->user_email;
}

// Given an owner subscription ID, return group members
function pms_in_gm_get_group_subscriptions( $subscription_id ){
    return pms_get_member_subscription_meta( $subscription_id, 'pms_group_subscription_member' );
}

// Get Dashboard URL
function pms_in_gm_get_dashboard_url(){
    if( $account_page = pms_get_page( 'account', true ) )
        return pms_account_get_tab_url( 'manage-group', $account_page );

    return false;
}

// Returns the Group Name based on Subscription ID
function pms_in_gm_get_group_name( $subscription_id ){
    if( pms_in_gm_is_group_owner( $subscription_id ) )
        $owner_subscription_id = $subscription_id;
    else
        $owner_subscription_id = pms_get_member_subscription_meta( $subscription_id, 'pms_group_subscription_owner', true );

    if( empty( $owner_subscription_id ) )
        return '';

    $owner_subscription = pms_get_member_subscription( $owner_subscription_id );

    if( !empty( $owner_subscription ) )
        return pms_get_member_subscription_meta( $owner_subscription->id, 'pms_group_name', true );

    return '';
}

// Determines if the given subscription id is a group owner
function pms_in_gm_is_group_owner( $subscription_id ){
    if( empty( $subscription_id ) )
        return false;

    $subscription = pms_get_member_subscription( $subscription_id );

    if( !isset( $subscription->subscription_plan_id ) )
        return false;

    $plan = pms_get_subscription_plan( $subscription->subscription_plan_id );

    if( $plan->type != 'group' )
        return false;

    $owner_subscription_id = pms_get_member_subscription_meta( $subscription_id, 'pms_group_subscription_owner' );

    if( empty( $owner_subscription_id ) )
        return true;

    return false;
}

// Returns an array with all members from a group (owner, registered, invited)
function pms_in_gm_get_group_members( $subscription_id ){
    //owner
    $owner[] = $subscription_id;

    //members
    $group_subscriptions = pms_in_gm_get_group_subscriptions( $subscription_id );

    //invited
    $invited_users = pms_get_member_subscription_meta( $subscription_id, 'pms_gm_invited_emails' );

    $members_list = array_merge( $owner, $group_subscriptions, $invited_users );

    return $members_list;
}

// Returns an array with existing users that are not members of the Group Subscription
function pms_in_gm_get_non_group_member_users( $group_owner_id ) {
    $members_list = pms_in_gm_get_group_members( $group_owner_id );
    $all_users = get_users();
    $members_id_list = array();

    foreach ( $members_list as $sub_id ) {
        $members_id_list[] = pms_in_gm_get_member_subscription_user_id( $sub_id );
    }

    if ( !empty( $members_id_list )) {
        $non_member_users = array();

        foreach ( $all_users as $user ) {
            if ( !in_array( $user->ID, $members_id_list ) ) {
                $non_member_users[] = $user->data;
            }
        }

    }
    else $non_member_users = $all_users;

    return $non_member_users;
}

// Gets the Group Name of the currently logged in user
function pms_in_get_current_user_group_name(){
    // use subscription id from the url query if available
    if( !empty( $_GET['subscription_id'] ) ) {
        $subscription = pms_get_member_subscription( (int)$_GET['subscription_id'] );

        if( !empty( $subscription->id ) )
            return pms_get_member_subscription_meta( $subscription->id, 'pms_group_name', true );
    }

    $user_id = get_current_user_id();

    if( $user_id == 0 )
        return false;

    $member          = pms_get_member( $user_id );
    $subscription_id = 0;

    foreach( $member->subscriptions as $subscription ){
        $plan = pms_get_subscription_plan( $subscription['subscription_plan_id'] );

        if( $plan->type != 'group' )
            continue;

        $subscription_id = $subscription['id'];
    }

    if( $subscription_id == 0 )
        return false;

    return pms_in_gm_get_group_name( $subscription_id );
}

add_filter( 'pms_subscription_logs_system_error_messages', 'pms_in_gm_add_subscription_log_messages', 20, 2 );
function pms_in_gm_add_subscription_log_messages( $message, $log ){
    if( empty( $log ) )
        return $message;

    switch ( $log['type'] ) {
        case 'group_user_subscription_added':
            $message = __( 'Subscription activated by group subscription invitation.', 'paid-member-subscriptions' );
            break;
        case 'group_user_accepted_invite':
            $message = __( 'User accepted group subscription invitation and registered. Subscription activated.', 'paid-member-subscriptions' );
            break;
    }

    return $message;

}

add_action( 'admin_init', 'pms_in_gm_admin_notices', 9 );
function pms_in_gm_admin_notices(){
    if( !pms_get_page( 'register' ) ) {

        $message = esc_html__( 'Please select the [pms-register] page under Settings -> General -> Membership Pages in order for Group Subscription invitations to work.', 'paid-member-subscriptions' );
    
        if( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'pms-settings-page' ) {
    
            new PMS_Add_General_Notices( 'pms_gm_register_page_pms_pages',
                $message,
                'notice-warning');
    
        } else {
    
            new PMS_Add_General_Notices( 'pms_gm_register_page',
                sprintf( $message . esc_html__( ' %1$sDismiss%2$s', 'paid-member-subscriptions' ), "<a href='" . esc_url( wp_nonce_url( add_query_arg( 'pms_gm_register_page_dismiss_notification', '0' ), 'pms_general_notice_dismiss' ) ) . "'>", "</a>" ),
                'notice-warning');
    
        }
    
    }
    
    if( !pms_get_page( 'account' ) ) {
    
        $message = esc_html__( 'Please select the [pms-account] page under Settings -> General -> Membership Pages in order for Group Owners to be able to invite members and manage their group.', 'paid-member-subscriptions' );
    
        if( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'pms-settings-page' ) {
    
            new PMS_Add_General_Notices( 'pms_gm_account_page_pms_pages',
                $message,
                'notice-warning');
    
        } else {
    
            new PMS_Add_General_Notices( 'pms_gm_account_page',
                sprintf( $message . esc_html__( ' %1$sDismiss%2$s', 'paid-member-subscriptions' ), "<a href='" . esc_url( wp_nonce_url( add_query_arg( 'pms_gm_account_page_dismiss_notification', '0' ), 'pms_general_notice_dismiss' ) ) . "'>", "</a>" ),
                'notice-warning');
    
        }
    
    }    
}
