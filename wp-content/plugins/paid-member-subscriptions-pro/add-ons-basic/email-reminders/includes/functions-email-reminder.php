<?php
/**
 * Functions for things related to email reminders
 */

/**
 * Returns all email reminders into an array of objects
 *
 * @param $only_active   - true to return only active email reminders, false to return all
 *
 * @param $trigger_unit   - used to filter email reminders which should be send by the hourly or the daily cron job
 *
 *  @return array
 */
function pms_in_get_email_reminders( $trigger_unit, $only_active = true ) {

    $post_status = ( $only_active == true ) ? 'active' : 'any';
    $email_reminders = get_posts( array('post_type' => 'pms-email-reminders', 'numberposts' => -1, 'post_status' => $post_status ) );

    $email_reminders_array = array();

    // return array of email reminder objects
    if ( !empty($email_reminders) ) {

        foreach ( $email_reminders as $reminder ) {

            $email_reminder = new PMS_IN_Email_Reminder( $reminder->ID );

            if( isset( $email_reminder->trigger_type ) && $email_reminder->trigger_type == 'instant' )
                continue;

            if ( ( $trigger_unit == 'hourly' ) && ( $email_reminder->trigger_unit == 'hour' ) )
                // return only the email reminders which should be sent by the hourly cron job
                $email_reminders_array[] = $email_reminder;

            if ( ( $trigger_unit != 'hourly' ) && ( $email_reminder->trigger_unit != 'hour' ) )
                // return only the email reminders who should be sent by the daily cron job
                $email_reminders_array[] = $email_reminder;

        }

    }

    return $email_reminders_array;
}

function pms_in_get_instant_email_reminders( $args = array() ){

    $post_status = ( isset( $args['only_active'] ) && $args['only_active'] == true ) ? 'active' : 'any';

    $default_args = array(
        'post_type'   => 'pms-email-reminders',
        'numberposts' => -1,
        'post_status' => $post_status,
        'meta_query' => array(
            array(
                'key'   => 'pms_email_reminder_trigger_type',
                'value' => 'instant',
            ),
        ),
    );

    $args = wp_parse_args( $args, $default_args );

    $email_reminders = get_posts( $args );

    $email_reminders_array = array();

    // return array of email reminder objects
    if ( !empty( $email_reminders ) ) {

        foreach ( $email_reminders as $reminder ) {

            $email_reminder = new PMS_IN_Email_Reminder( $reminder->ID );

            $email_reminders_array[] = $email_reminder;

        }

    }

    return $email_reminders_array;

}

/**
 * Function that returns the member subscriptions that match filters made from the
 * given trigger and trigger unit
 *
 * @param PMS_IN_Email_Reminder $email_reminder
 * @param string $trigger_unit
 *
 * @return array
 *
 */
function pms_in_er_get_member_subscriptions( $email_reminder, $trigger_unit ) {
    if( empty( $email_reminder->subscriptions ) )
        return;

    global $wpdb;

    // define subscription status to use in query
    $status = 'active';

    if ($email_reminder->event == 'after_member_abandons_signup')
        $status = 'pending';
    elseif ($email_reminder->event == 'after_subscription_expires')
        $status = 'expired';

    // define which column to use in the sql select based on email reminder event
    $column_name = "start_date";
    if ( ($email_reminder->event == 'before_subscription_expires') || ($email_reminder->event == 'after_subscription_expires') || ($email_reminder->event == 'before_subscription_renews_automatically') ) {
        $column_name = "expiration_date";
    }

    // used in defining the time intervals below
    $operator = ( ($email_reminder->event == 'before_subscription_expires') || ($email_reminder->event == 'before_subscription_renews_automatically') ) ? '+' : '-';

    // define time intervals
    $trigger_timestamp = strtotime( $operator . $email_reminder->trigger. ' ' . $email_reminder->trigger_unit);

    if ( $trigger_unit == 'hourly' ){
        // get 1 hour interval
        $begin = strtotime("-1 hour", $trigger_timestamp);
        $end = $trigger_timestamp;
    }
    else {
        // get begin and end of day interval
        $begin = strtotime("midnight", $trigger_timestamp);
        $end   = strtotime("tomorrow", $begin) - 1;
    }

    $begin_date = date("Y-m-d H:i:s", $begin);
    $end_date = date("Y-m-d H:i:s", $end);

    // create query string
    $query_string = "SELECT * ";
    $query_from = "FROM {$wpdb->prefix}pms_member_subscriptions member_subscriptions ";
    $query_join = "";
    $query_where = "WHERE member_subscriptions.status LIKE '" . $status. "'";

    // add inner join if email reminder event is 'since_last_login'
    if ( $email_reminder->event == 'since_last_login' ) {

        $query_join = "INNER JOIN {$wpdb->usermeta} usermeta ON member_subscriptions.user_id = usermeta.user_id ";
        $query_where .= " AND usermeta.meta_key = 'last_login' AND usermeta.meta_value BETWEEN '" . $begin_date . "' AND '" .$end_date . "' ";

    }
    else if ( $email_reminder->event == 'before_subscription_renews_automatically' )
        $query_where .= " AND member_subscriptions.billing_next_payment BETWEEN '" . $begin_date . "' AND '" . $end_date . "' ";
    else
        $query_where .= " AND member_subscriptions.{$column_name} BETWEEN '" . $begin_date . "' AND '" . $end_date . "' ";


    // add to query if member subscription is recurring
    if ( $email_reminder->event == 'before_subscription_renews_automatically' ) {

        $query_where .= " AND (
            ( member_subscriptions.payment_profile_id IS NOT NULL AND TRIM(member_subscriptions.payment_profile_id) <> '' ) OR
            ( member_subscriptions.expiration_date = '0000-00-00 00:00:00' )
        )";

    }

    // recurring subscriptions should not receive e-mails for before subscription expiration
    if ( $email_reminder->event == 'before_subscription_expires' ) {

        $query_where .= " AND member_subscriptions.payment_profile_id = '' ";

    }

    // get only results which have the subscription plans selected in the email reminder settings
    if ( strpos( $email_reminder->subscriptions, 'all_subscriptions' ) === false ) {

        $query_where .= " AND member_subscriptions.subscription_plan_id IN (" . $email_reminder->subscriptions . ") ";

    }

    // Concatenate the sections into the full query string
    $query_string .= $query_from . $query_join . $query_where;

    $results = $wpdb->get_results( $query_string, ARRAY_A );

    return $results;

}

/**
 * Function that sends the reminder emails
 *
 */
function pms_in_send_email_reminders( $result, $email_reminder ){

    $user_info = get_userdata( $result['user_id'] );

    // Set the reminder send to
    if( $email_reminder->send_to == 'user' ) {

        if( empty( $user_info->user_email ) )
            return;

        $reminder_send_to = $user_info->user_email;

    } else {

        $reminder_send_to = array();

        if( ! empty( $email_reminder->admin_emails ) ) {

            $admin_emails = array_map( 'trim', explode( ',', $email_reminder->admin_emails ) );

            foreach( $admin_emails as $key => $admin_email ) {

                if( ! is_email( $admin_email ) )
                    unset( $admin_emails[$key] );

            }

            $reminder_send_to = $admin_emails;

        }

    }

    // Grab subscription id
    $member_subscriptions = pms_get_member_subscriptions( array( 'user_id' => $result['user_id'], 'subscription_plan_id' => $result['subscription_plan_id'] ) );

    if( isset( $member_subscriptions[0] ) && !empty( $member_subscriptions[0]->id ) )
        $subscription_id = $member_subscriptions[0]->id;
    else
        $subscription_id = 0;

    if( !apply_filters( 'pms_er_send_email_reminder', true, $email_reminder, $subscription_id ) )
        return;

    // Set the reminder subject and content
    if ( class_exists( 'PMS_Merge_Tags' ) ) {

        $reminder_subject = PMS_Merge_Tags::process_merge_tags( $email_reminder->subject, $user_info, $subscription_id );
        $reminder_content = PMS_Merge_Tags::process_merge_tags( $email_reminder->content, $user_info, $subscription_id );

    } else {

        $reminder_subject = $email_reminder->subject;
        $reminder_content = $email_reminder->content;

    }

    // Format email message
    $reminder_content = wpautop( $reminder_content );
    $reminder_content = do_shortcode( $reminder_content );

    //we add this filter to enable html encoding
    add_filter( 'wp_mail_content_type', 'pms_in_er_email_content_type' );

    // Temporary change the from name and from email
    add_filter( 'wp_mail_from_name', 'pms_in_er_from_name', 20, 1 );
    add_filter( 'wp_mail_from', 'pms_in_er_from_email', 20, 1 );

    wp_mail( $reminder_send_to, $reminder_subject, $reminder_content );

    // Reset html encoding
    remove_filter( 'wp_mail_content_type', 'pms_in_er_email_content_type' );

    // Reset the from name and email
    remove_filter( 'wp_mail_from_name', 'pms_in_er_from_name', 20 );
    remove_filter( 'wp_mail_from', 'pms_in_er_from_email', 20 );

}

/*
 * Process email reminders
 *
 */
function pms_in_process_email_reminders( $trigger_unit = 'daily' ){

    $email_reminders_array = pms_in_get_email_reminders( $trigger_unit );

    // Check if we have any active email reminders
    if ( !empty( $email_reminders_array ) ) {

        foreach ( $email_reminders_array as $email_reminder ) {

            $results = pms_in_er_get_member_subscriptions( $email_reminder, $trigger_unit );

            // if we have any results from the query send the reminder emails
            if ( ! empty( $results ) ) {

                // for the 'since_last_login' event, send only one reminder email per user (even if he has multiple active subscriptions) and use the first subscription data in merge tags
                if ( $email_reminder->event == 'since_last_login' ){
                    $first_sub = $results[0];
                    $results   = array();
                    $results[] = $first_sub;
                }

                foreach ($results as $result) {

                    pms_in_send_email_reminders($result, $email_reminder);

                }

            }

        } // end foreach

    }

}
//add_action('init', 'pms_in_process_email_reminders');
add_action('pms_send_email_reminders_hourly', 'pms_in_process_email_reminders', 10, 1);
add_action('pms_send_email_reminders_daily', 'pms_in_process_email_reminders', 10, 1);

function pms_in_er_from_name( $site_name ) {
    $pms_settings = get_option( 'pms_emails_settings' );

    if ( !empty( $pms_settings['email-from-name'] ) )
        $site_name = $pms_settings['email-from-name'];
    else
        $site_name = get_bloginfo('name');

    return $site_name;
}

function pms_in_er_from_email() {
    $pms_settings = get_option( 'pms_emails_settings' );

    if ( ! empty( $pms_settings['email-from-email'] ) ) {

        if( is_email( $pms_settings['email-from-email'] ) )
            $sender_email = $pms_settings['email-from-email'];

    } else
        $sender_email = get_bloginfo( 'admin_email' );

    return $sender_email;
}

function pms_in_er_email_content_type() {

    return 'text/html';

}

/**
 * Instant Email Reminders functionality
 */
function pms_in_er_get_instant_reminders_triggers(){

    $triggers = array(
        'on_account_creation'         => __( 'Account Creation', 'paid-member-subscriptions' ),
        'on_subscription_activation'  => __( 'Subscription Active', 'paid-member-subscriptions' ),
        'on_subscription_expiration'  => __( 'Subscription Expired', 'paid-member-subscriptions' ),
        'on_subscription_cancelation' => __( 'Subscription Canceled', 'paid-member-subscriptions' ),
        'on_payment_completed'        => __( 'Payment Completed', 'paid-member-subscriptions' ),
        'on_payment_pending'          => __( 'Payment Pending', 'paid-member-subscriptions' ),
        'on_payment_failed'           => __( 'Payment Failed', 'paid-member-subscriptions' ),
    );

    return apply_filters( 'pms_er_get_instant_reminders_triggers', $triggers );
}

function pms_in_er_process_instant_reminders( $event, $event_data = array() ){

    if( empty( $event ) )
        return;

    $args = array(
        'meta_query' => array(
            array(
                'key'   => 'pms_email_reminder_event',
                'value' => $event,
            ),
        )
    );

    $email_reminders       = pms_in_get_instant_email_reminders( $args );
    $email_reminders_array = array();

    if( !empty( $email_reminders ) ){

        foreach ( $email_reminders as $email_reminder ) {

            if( $email_reminder->status != 'active' )
                continue;

            if( strpos( $email_reminder->subscriptions, 'all_subscriptions' ) !== false || strpos( $email_reminder->subscriptions, $event_data['subscription_plan_id'] ) !== false )
                $email_reminders_array[] = $email_reminder;

        }

    }

    if( !empty( $email_reminders_array ) ){

        foreach( $email_reminders_array as $email_reminder )
            pms_in_send_email_reminders( $event_data, $email_reminder );
        
    }

    return;

}

// On User Creation
add_action( 'pms_register_form_after_create_user', 'pms_in_er_send_instant_reminders_on_user_creation', 30 );
function pms_in_er_send_instant_reminders_on_user_creation( $userdata ){

    if( empty( $userdata['user_id'] ) || empty( $userdata['subscriptions'] ) || empty( $userdata['subscriptions'][0] ) )
        return;

    $event_data = array(
        'user_id'              => $userdata['user_id'],
        'subscription_plan_id' => absint( $userdata['subscriptions'][0] ),
    );

    pms_in_er_process_instant_reminders( 'on_account_creation', $event_data );

}


// WPPB Form
add_action( 'wppb_register_success', 'pms_in_er_wppb_send_instant_reminders_on_user_creation', 20, 3 );
function pms_in_er_wppb_send_instant_reminders_on_user_creation( $http_request, $form_name, $user_id ){

    if( empty( $user_id ) )
        return;

    if( empty( $http_request['subscription_plans'] ) ){

        // check if the request contains the subscription id
        if( !empty( $http_request['subscription_id'] ) ){
            $subscription = pms_get_member_subscription( absint( $http_request['subscription_id'] ) );

            if( !empty( $subscription->subscription_plan_id ) ){
                $plan_id = $subscription->subscription_plan_id;
            }
        }

    } else {
        $plan_id = $http_request['subscription_plans'];
    }

    if( empty( $plan_id ) )
        return;

    $event_data = array(
        'user_id'              => $user_id,
        'subscription_plan_id' => absint( $plan_id ),
    );

    pms_in_er_process_instant_reminders( 'on_account_creation', $event_data );

}

// WPPB Form with Email Confirmation
add_action( 'wppb_activate_user', 'pms_in_er_wppb_ec_send_instant_reminders_on_user_creation', 20, 3 );
function pms_in_er_wppb_ec_send_instant_reminders_on_user_creation( $user_id, $password, $meta ){
	
    if( empty( $user_id ) || empty( $meta['subscription_plans'] ) )
        return;

    $event_data = array(
        'user_id'              => $user_id,
        'subscription_plan_id' => absint( $meta['subscription_plans'] ),
    );

    pms_in_er_process_instant_reminders( 'on_account_creation', $event_data );

}

// On Subscription Update
add_action( 'pms_member_subscription_update', 'pms_in_er_send_instant_reminders_on_member_subbscription_update', 30, 3 );
function pms_in_er_send_instant_reminders_on_member_subbscription_update( $subscription_id, $new_data, $old_data ){

    if( empty( $old_data['user_id'] ) || empty( $old_data['subscription_plan_id'] ) )
        return;

    $event_data = array(
        'user_id'              => $old_data['user_id'],
        'subscription_plan_id' => $old_data['subscription_plan_id'],
    );

    if ( isset( $new_data['status'] ) && $new_data['status'] != $old_data['status'] && $new_data['status'] == 'active' ){
        
        pms_in_er_process_instant_reminders( 'on_subscription_activation', $event_data );
        
    }

    if ( isset( $new_data['status'] ) && $new_data['status'] != $old_data['status'] && $new_data['status'] == 'expired' ){
        
        pms_in_er_process_instant_reminders( 'on_subscription_expiration', $event_data );
        
    }

    if ( isset( $new_data['status'] ) && $new_data['status'] != $old_data['status'] && $new_data['status'] == 'canceled' ){
        
        pms_in_er_process_instant_reminders( 'on_subscription_cancelation', $event_data );
        
    }

}

// On Payment Update
add_action( 'pms_payment_update', 'pms_in_er_send_instant_reminders_on_payment_update', 20, 3 );
function pms_in_er_send_instant_reminders_on_payment_update( $payment_id, $new_data, $old_data ){

    if( empty( $old_data['user_id'] ) || empty( $old_data['subscription_id'] ) )
        return;

    $event_data = array(
        'user_id'              => $old_data['user_id'],
        'subscription_plan_id' => $old_data['subscription_id'],
    );

    if( isset( $new_data['status'] ) && $new_data['status'] != $old_data['status'] && $new_data['status'] == 'completed' ){
        
        pms_in_er_process_instant_reminders( 'on_payment_completed', $event_data );

    }

    if( isset( $new_data['status'] ) && $new_data['status'] != $old_data['status'] && $new_data['status'] == 'failed' ){
        
        pms_in_er_process_instant_reminders( 'on_payment_failed', $event_data );

    }

}

add_action( 'pms_payment_insert', 'pms_in_er_send_instant_reminders_on_payment_insert', 20, 2 );
function pms_in_er_send_instant_reminders_on_payment_insert( $payment_id, $data ){

    if( !isset( $data['payment_gateway'] ) || $data['payment_gateway'] != 'manual' )
        return;

    if( !isset( $data['status'] ) || $data['status'] != 'pending' )
        return;

    if( empty( $data['user_id'] ) || empty( $data['subscription_plan_id'] ) )
        return;

    $event_data = array(
        'user_id'              => $data['user_id'],
        'subscription_plan_id' => $data['subscription_plan_id'],
    );
    
    pms_in_er_process_instant_reminders( 'on_payment_pending', $event_data );

}