<?php
/**
 * Email Reminders custom post type
 */

if ( class_exists('PMS_Custom_Post_Type') ) {

    class PMS_IN_Email_Reminders_Custom_Post_Type extends PMS_Custom_Post_Type {

        /*
         * Method to add the needed hooks
         *
         */
        public function init(){

            add_action( 'init', array( $this, 'process_data' ) );
            add_action( 'init', array( $this, 'register_custom_email_reminder_statuses' ) );

            add_filter('manage_' . $this->post_type . '_posts_columns', array(__CLASS__, 'manage_posts_columns'));
            add_action( 'manage_' . $this->post_type . '_posts_custom_column', array( __CLASS__, 'manage_posts_custom_column' ), 10, 2 );

            add_filter('page_row_actions', array($this, 'remove_post_row_actions'), 10, 2);
            add_action('page_row_actions', array($this, 'add_post_row_actions'), 11, 2);

            // Remove "Move to Trash" bulk action
            add_filter('bulk_actions-edit-' . $this->post_type, array($this, 'remove_bulk_actions'));

            // Add a delete button where the move to trash was
            add_action('post_submitbox_start', array($this, 'submitbox_add_delete_button'));

            // Change the default "Enter title here" text
            add_filter('enter_title_here', array($this, 'change_email_reminder_title_prompt_text'));

            // Set custom updated messages
            add_filter('post_updated_messages', array($this, 'set_custom_messages'));

            // Set custom bulk updated messages
            add_filter('bulk_post_updated_messages', array($this, 'set_bulk_custom_messages'), 10, 2);

        }


        /*
        * Method that validates data for the email reminder cpt
        *
        */
        public function process_data(){

            // Verify nonce before anything
            if( !isset( $_REQUEST['_wpnonce'] ) || !wp_verify_nonce( sanitize_text_field( $_REQUEST['_wpnonce'] ), 'pms_email_reminder_nonce' ) )
                return;

            // Activate Email Reminder
            if( isset( $_REQUEST['pms-action'] ) && $_REQUEST['pms-action'] == 'activate_email_reminder' && isset( $_REQUEST['post_id'] ) ) {
                PMS_IN_Email_Reminder::activate( absint( $_REQUEST['post_id'] ) );
            }

            // Deactivate Email Reminder
            if( isset( $_REQUEST['pms-action'] ) && $_REQUEST['pms-action'] == 'deactivate_email_reminder' && isset( $_REQUEST['post_id'] ) ) {
                PMS_IN_Email_Reminder::deactivate( absint( $_REQUEST['post_id'] ) );
            }

            // Delete Email Reminder
            if( isset( $_REQUEST['pms-action'] ) && $_REQUEST['pms-action'] == 'delete_email_reminder' && isset( $_REQUEST['post_id'] ) ) {
                PMS_IN_Email_Reminder::remove( absint( $_REQUEST['post_id'] ) );
            }

        }


        /*
        * Method that adds custom email reminders statuses (active, inactive)
        *
        */
        public function register_custom_email_reminder_statuses(){

            register_post_status( 'active', array(
                'label'                     => _x( 'Active', 'Active status', 'paid-member-subscriptions' ),
                'public'                    => true,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop( 'Active <span class="count">(%s)</span>', 'Active <span class="count">(%s)</span>', 'paid-member-subscriptions' )
            )  );

            register_post_status( 'inactive', array(
                'label'                     => _x( 'Inactive', 'Inactive status', 'paid-member-subscriptions' ),
                'public'                    => true,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop( 'Inactive <span class="count">(%s)</span>', 'Inactive <span class="count">(%s)</span>', 'paid-member-subscriptions' )
            )  );

        }


        /*
         * Method to add the needed columns in Email Reminders listing.
         *
         */
        public static function manage_posts_columns($columns) {

            // Add new columns for the email reminders listing
            $new_columns = array_merge($columns, array(
                'send_to'       => __( 'Send To', 'paid-member-subscriptions' ),
                'trigger'       => __( 'Trigger Event', 'paid-member-subscriptions' ),
                'subscriptions' => __( 'Subscriptions', 'paid-member-subscriptions' ),
                'status'        => __( 'Status', 'paid-member-subscriptions' )
            ));

            unset($new_columns['date']);

            return $new_columns;

        }


        /*
         * Method to display values for each Email Reminder column
         *
        */
        public static function manage_posts_custom_column( $column, $post_id ) {

            $email_reminder = new PMS_IN_Email_Reminder( $post_id );

            // Information shown in the "Send To" column
            if( $column == 'send_to' ) {

                if( $email_reminder->send_to == 'user' )
                    echo esc_html__( 'Members', 'paid-member-subscriptions' );
                else
                    echo esc_html__( 'Administrators', 'paid-member-subscriptions' );

            }

            // Information shown in the "Trigger Event" column
            if ( $column == 'trigger' ) {

                if( isset( $email_reminder->trigger_type ) && $email_reminder->trigger_type == 'instant' ){

                    $instant_triggers = pms_in_er_get_instant_reminders_triggers(); 

                    echo esc_html( sprintf( __( 'Instant on %s', 'paid-member-subscriptions' ), isset( $instant_triggers[ $email_reminder->event ] ) ? $instant_triggers[ $email_reminder->event ] : 'unknown' ) );
                } else {

                    echo esc_html( $email_reminder->trigger ) . ' ';

                    switch ( $email_reminder->trigger_unit ) {
    
                        case "hour" :
                            echo esc_html__( 'Hour(s)', 'paid-member-subscriptions' ) . ' ';
                            break;
                        case "day" :
                            echo esc_html__( 'Day(s)', 'paid-member-subscriptions' ) . ' ';
                            break;
                        case "week" :
                            echo esc_html__( 'Week(s)', 'paid-member-subscriptions' ) . ' ';
                            break;
                        case "month" :
                            echo esc_html__( 'Month(s)', 'paid-member-subscriptions' ) . ' ';
                            break;
                    }

                    switch ( $email_reminder->event ) {
    
                        case "after_member_signs_up" :
                            echo esc_html__( 'after Member Signs Up (subscription active)', 'paid-member-subscriptions' );
                            break;
                        case "after_member_abandons_signup" :
                            echo esc_html__( 'after Member Abandons Signup (subscription pending)', 'paid-member-subscriptions' );
                            break;
                        case "before_subscription_expires" :
                            echo esc_html__( 'before Subscription Expires', 'paid-member-subscriptions' );
                            break;
                        case "after_subscription_expires" :
                            echo esc_html__( 'after Subscription Expires', 'paid-member-subscriptions' );
                            break;
                        case "before_subscription_renews_automatically" :
                            echo esc_html__( 'before Subscription Renews Automatically', 'paid-member-subscriptions' );
                            break;
                        case "since_last_login" :
                            echo esc_html__( 'since Last Login', 'paid-member-subscriptions' );
                            break;
                    }

                }

            }

            //Information shown in the "Subscriptions" column
            if ( $column == 'subscriptions' ) {

                $subscriptions_array = explode(',', $email_reminder->subscriptions);

                $i = 0;
                foreach ($subscriptions_array as $subscription_id) {

                    if ($subscription_id == 'all_subscriptions') {
                        echo esc_html__( 'All Subscriptions', 'paid-member-subscriptions' );
                        break;
                    }

                    $i++;

                    $subscription_plan = pms_get_subscription_plan( $subscription_id );

                    echo esc_html( $subscription_plan->name );
                    echo ( count( $subscriptions_array ) > $i ) ? ', ' : '';
                }

            }

            // Information shown in the status column
            if( $column == 'status' ) {

                $email_reminder_status_dot = apply_filters( 'pms-list-table-show-status-dot', '<span class="pms-status-dot ' . esc_attr( $email_reminder->status ) . '"></span>' );

                if( $email_reminder->is_active() )
                    echo $email_reminder_status_dot . '<span>' . esc_html__( 'Active', 'paid-member-subscriptions' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                else
                    echo $email_reminder_status_dot . '<span>' . esc_html__( 'Inactive', 'paid-member-subscriptions' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }

        }


        /*
         * Method for removing the unnecessary row actions (e.g Quick edit, Trash).
         *
         */
        public function remove_post_row_actions($actions, $post) {

            if ($post->post_type != $this->post_type)
                return $actions;

            if (empty($actions))
                return $actions;

            foreach ($actions as $key => $action) {
                if ($key != 'edit') {
                    unset($actions[$key]);
                }
            }

            return $actions;
        }

        /*
         * Method for adding new row actions (e.g Activate/Deactivate , Delete).
         *
         */
        public function add_post_row_actions($actions, $post){

            if ($post->post_type != $this->post_type)
                return $actions;

            if (empty($actions))
                return $actions;

            /*
             * Add the option to activate and deactivate an Email Reminder
             */
            $email_reminder = new PMS_IN_Email_Reminder( $post );

            if( $email_reminder->is_active() )
                $activate_deactivate = '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms-action' => 'deactivate_email_reminder', 'post_id' => $post->ID ) ), 'pms_email_reminder_nonce' ) ) . '">' . esc_html__( 'Deactivate', 'paid-member-subscriptions' ) . '</a>';
            else
                $activate_deactivate = '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms-action' => 'activate_email_reminder', 'post_id' => $post->ID ) ), 'pms_email_reminder_nonce' ) ) . '">' . esc_html__( 'Activate', 'paid-member-subscriptions' ) . '</a>';

            $actions['change_status'] = $activate_deactivate;

            /*
             * Add the option to delete an email reminder
             */
            $delete = '<span class="trash"><a onclick="return confirm( \'' . esc_html__("Are you sure you want to delete this Email Reminder?", "paid-member-subscriptions") . ' \' )" href="' . esc_url(wp_nonce_url(add_query_arg(array('pms-action' => 'delete_email_reminder', 'post_id' => $post->ID, 'deleted' => 1)), 'pms_email_reminder_nonce')) . '">' . esc_html__('Delete', 'paid-member-subscriptions') . '</a></span>';

            $actions['delete'] = $delete;


            // Return actions
            return $actions;

        }

        /*
        * Remove "Move to Trash" bulk action
        *
        */
        public function remove_bulk_actions($actions){

            unset($actions['trash']);
            return $actions;

        }

        /*
        * Add a delete button where the move to trash was
        *
        */
        public function submitbox_add_delete_button(){

            global $post_type;
            global $post;

            if ($post_type != $this->post_type)
                return false;

            echo '<div id="pms-delete-action">';
            echo '<a class="submitdelete deletion" onclick="return confirm( \'' . esc_html__("Are you sure you want to delete this Email Reminder?", "paid-member-subscriptions") . ' \' )" href="' . esc_url(wp_nonce_url(add_query_arg(array('pms-action' => 'delete_email_reminder', 'post_id' => $post->ID, 'deleted' => 1), admin_url('edit.php?post_type=' . $this->post_type)), 'pms_email_reminder_nonce')) . '">' . esc_html__('Delete', 'paid-member-subscriptions') . '</a>';
            echo '</div>';

        }

        /*
        * Method to change the default title text "Enter title here"
        *
        */
        public function change_email_reminder_title_prompt_text($input){
            global $post_type;

            if ($post_type == $this->post_type) {
                return __('Enter Email Reminder name here', 'paid-member-subscriptions');
            }

            return $input;
        }

        /*
        * Method that set custom updated messages
        *
        */
        function set_custom_messages($messages){

            global $post;

            $messages['pms-email-reminders'] = array(
                0 => '',
                1 => __('Email Reminder updated.', 'paid-member-subscriptions'),
                2 => __('Custom field updated.', 'paid-member-subscriptions'),
                3 => __('Custom field deleted.', 'paid-member-subscriptions'),
                4 => __('Email Reminder updated.', 'paid-member-subscriptions'),
                5 => isset( $_GET['revision']) ? sprintf( __('Email Reminder restored to revision from %s', 'paid-member-subscriptions' ), wp_post_revision_title( absint( $_GET['revision'] ), false) ) : false,
                6 => __('Email Reminder saved.', 'paid-member-subscriptions'),
                7 => __('Email Reminder saved.', 'paid-member-subscriptions'),
                8 => __('Email Reminder submitted.', 'paid-member-subscriptions'),
                9 => sprintf(__('Email Reminder scheduled for: <strong>%1$s</strong>.', 'paid-member-subscriptions'), date_i18n( __( 'M j, Y @ G:i', 'paid-member-subscriptions' ), strtotime( $post->post_date ) ) ),
                10 => __('Email Reminder draft updated.', 'paid-member-subscriptions'),
            );

            // If there are validation errors do not display the above messages
            $error = get_transient('pms_er_metabox_validation_errors');
            if  ( !empty($error) ) // no validation errors
                return array();
            else
                return $messages;

        }

        /*
        * Method that set custom bulk updated messages
        *
        */
        public function set_bulk_custom_messages($bulk_messages, $bulk_counts){

            $bulk_messages['pms-email-reminders'] = array(
                'updated'   => _n('%s Email Reminder updated.', '%s Email Reminders updated.', $bulk_counts['updated'], 'paid-member-subscriptions'),
                'locked'    => _n('%s Email Reminder not updated, somebody is editing it.', '%s Email Reminders not updated, somebody is editing them.', $bulk_counts['locked'], 'paid-member-subscriptions'),
                'deleted'   => _n('%s Email Reminder permanently deleted.', '%s Email Reminders permanently deleted.', $bulk_counts['deleted'], 'paid-member-subscriptions'),
                'trashed'   => _n('%s Email Reminder moved to the Trash.', '%s Email Reminders moved to the Trash.', $bulk_counts['trashed'], 'paid-member-subscriptions'),
                'untrashed' => _n('%s Email Reminder restored from the Trash.', '%s Email Reminders restored from the Trash.', $bulk_counts['untrashed'], 'paid-member-subscriptions'),
            );

            return $bulk_messages;

        }


    } // end class PMS_Email_Reminder_Custom_Post_Type



    /*
     * Initialize the Email Reminders custom post type
     *
     */

    $args = array(
        'show_ui' => true,
        'show_in_menu' => 'paid-member-subscriptions',
        'query_var' => true,
        'capability_type' => 'post',
        'menu_position' => null,
        'supports' => array('title'),
        'hierarchical' => true
    );

    $pms_cpt_email_reminders = new PMS_IN_Email_Reminders_Custom_Post_Type( 'pms-email-reminders', __('Email Reminder', 'paid-member-subscriptions'), __('Email Reminders', 'paid-member-subscriptions'), $args);
    $pms_cpt_email_reminders->init();
}


