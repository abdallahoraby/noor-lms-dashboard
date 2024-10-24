<?php

if ( !class_exists('PMS_Custom_Post_Type') )
    return;

Class PMS_IN_Content_Dripping_Custom_Post_Type extends PMS_Custom_Post_Type {

    /*
     * Initialise post type functionality
     *
     */
    public function init() {

        add_action( 'init', array( $this, 'process_data' ) );

        // Register custom statuses for the sets
        add_action( 'init', array( $this, 'register_custom_statuses' ) );

        // Custom row actions
        add_filter( 'page_row_actions', array( $this, 'remove_post_row_actions' ), 10, 2 );
        add_action( 'page_row_actions', array( $this, 'add_post_row_actions' ), 11, 2 );

        // Remove "Move to Trash" bulk action
        add_filter('bulk_actions-edit-' . $this->post_type, array($this, 'remove_bulk_actions'));

        // Custom table columns
        add_filter( 'manage_' . $this->post_type . '_posts_columns', array( __CLASS__, 'manage_posts_columns' ) );
        add_action( 'manage_' . $this->post_type . '_posts_custom_column', array( __CLASS__, 'manage_posts_custom_column' ), 10, 2 );

        // Set custom updated messages
        add_filter( 'post_updated_messages', array( $this, 'set_custom_messages' ) );

        add_action( 'enqueue_admin_scripts', array( $this, 'enqueue_admin_scripts' ) );

    }


    /*
     * Validate data and execute pms-actions
     *
     */
    public function process_data() {

        // Check nonce
        if( empty( $_REQUEST['_wpnonce'] ) || !wp_verify_nonce( sanitize_text_field( $_REQUEST['_wpnonce'] ), 'pms_content_dripping_set_nonce' ) )
            return;

        // Activate content dripping set
        if( !empty( $_REQUEST['pms-action'] ) && $_REQUEST['pms-action'] == 'activate_content_dripping_set' && !empty( $_REQUEST['post_id'] ) ) {

            $post_id = (int)$_REQUEST['post_id'];

            if( $post_id == 0 )
                return;

            update_post_meta( $post_id, 'pms_content_dripping_set_status', 'active' );

            $post_data = array(
                'ID'            => $post_id,
                'post_status'   => 'active',
            );
            wp_update_post( $post_data );

        }

        // Deactivate content dripping set
        if( !empty( $_REQUEST['pms-action'] ) && $_REQUEST['pms-action'] == 'deactivate_content_dripping_set' && !empty( $_REQUEST['post_id'] ) ) {

            $post_id = (int)$_REQUEST['post_id'];

            if( $post_id == 0 )
                return;

            update_post_meta( $post_id, 'pms_content_dripping_set_status', 'inactive' );

            $post_data = array(
                'ID'            => $post_id,
                'post_status'   => 'inactive',
            );
            wp_update_post( $post_data );

        }

        // Delete content dripping set
        if( !empty( $_REQUEST['pms-action'] ) && $_REQUEST['pms-action'] == 'delete_content_dripping_set' && !empty( $_REQUEST['post_id'] ) ) {

            $post_id = (int)$_REQUEST['post_id'];

            if( $post_id == 0 )
                return;

            wp_delete_post( $post_id, true );

        }

    }


    /*
     * Register custom statuses
     *
     */
    public function register_custom_statuses() {

        register_post_status( 'active', array(
            'label'                     => _x( 'Active', 'Active status for content dripping set.', 'paid-member-subscriptions' ),
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop( 'Active <span class="count">(%s)</span>', 'Active <span class="count">(%s)</span>', 'paid-member-subscriptions' )
        ));
        register_post_status( 'inactive', array(
            'label'                     => _x( 'Inactive', 'Inactive status for content dripping set.', 'paid-member-subscriptions' ),
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop( 'Inactive <span class="count">(%s)</span>', 'Inactive <span class="count">(%s)</span>', 'paid-member-subscriptions' )
        ));

    }


    /*
     * Method that removes all row actions besides the edit one
     *
     */
    public function remove_post_row_actions( $actions, $post ) {

        if( $post->post_type != $this->post_type )
            return $actions;

        if( empty( $actions ) )
            return $actions;

        foreach( $actions as $key => $action ) {
            if( $key != 'edit' ) {
                unset( $actions[ $key ] );
            }
        }

        return $actions;
    }


    /*
     * Method that adds new actions
     *
     */
    public function add_post_row_actions( $actions, $post ) {

        if( $post->post_type != $this->post_type )
            return $actions;

        if( empty( $actions ) )
            return $actions;


        /*
         * Add the option to activate and deactivate a subscription plan
         */

        if( $post->post_status == 'active' )
            $activate_deactivate = '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms-action' => 'deactivate_content_dripping_set', 'post_id' => $post->ID ) ), 'pms_content_dripping_set_nonce' ) ) . '">' . __( 'Deactivate', 'paid-member-subscriptions' ) . '</a>';
        else
            $activate_deactivate = '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms-action' => 'activate_content_dripping_set', 'post_id' => $post->ID ) ), 'pms_content_dripping_set_nonce' ) ) . '">' . __( 'Activate', 'paid-member-subscriptions' ) . '</a>';

        $actions['change_status'] = $activate_deactivate;

        /*
         * Add the option to delete a subscription plan
         */
        $delete = '<span class="trash"><a onclick="return confirm( \'' . __( "Are you sure you want to delete this Content Dripping Set?", "paid-member-subscriptions" ) . ' \' )" href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms-action' => 'delete_content_dripping_set', 'post_id' => $post->ID, 'deleted' => 1 ) ), 'pms_content_dripping_set_nonce' ) ) . '">' . __( 'Delete', 'paid-member-subscriptions' ) . '</a></span>';

        $actions['delete'] = $delete;

        // Return actions
        return $actions;

    }


    /*
     * Remove bulk actions
     *
     */
    public function remove_bulk_actions( $actions ) {

        return array();

    }


    /*
     * Method that adds new columns on the content dripping sets listing
     *
     */
    public static function manage_posts_columns( $columns ) {

        // New columns for the content dripping set
        $new_columns = array_merge( $columns, array(
            'subscription_plan' => __( 'Subscription Plan', 'paid-member-subscriptions' ),
            'shortcode'         => __( 'Contents Table Shortcode', 'paid-member-subscriptions' ),
            'status'            => __( 'Status', 'paid-member-subscriptions' )
        ));

        // Remove the date
        unset( $new_columns['date'] );

        return $new_columns;

    }


    /*
     * Method to display values for each new column
     *
     */
    public static function manage_posts_custom_column( $column, $post_id ) {

        // Get post meta
        $post_meta = pms_get_post_meta( $post_id );

        // Information shown in the subscription plan column
        if( $column == 'subscription_plan' ) {

            $subscription_plan_id = ( !empty( $post_meta['pms_content_dripping_set_subscription_plan'] ) ? $post_meta['pms_content_dripping_set_subscription_plan'] : null );

            if( !is_null( $subscription_plan_id ) ){
                $subscription_plan    = pms_get_subscription_plan( $subscription_plan_id );

                if( $subscription_plan->is_valid() )
                    echo '<span>' . esc_html( $subscription_plan->name ) . '</span>';

            } else
                echo '<span>-</span>';

        }

        // Information in the contents table shortcode
        if( $column == 'shortcode' ) {

            global $post;

            echo '<input type="text" readonly class="pms-dp-contents-table-shortcode" value="[pms-cd-contents-table ' . htmlspecialchars( 'id="' . $post->ID . '"', ENT_QUOTES, 'UTF-8') . ']" />'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        }

        // Information shown in the status column
        if( $column == 'status' ) {

            $status = !empty( $post_meta['pms_content_dripping_set_status'] ) ? $post_meta['pms_content_dripping_set_status'] : 'inactive';

            $subscription_plan_status_dot = apply_filters( 'pms_list_table_subscription_plans_show_status_dot', '<span class="pms-status-dot ' . esc_attr( $status ) . '"></span>' );

            if( $status == 'active' )
                echo $subscription_plan_status_dot . '<span>' . esc_html__( 'Active', 'paid-member-subscriptions' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            else
                echo $subscription_plan_status_dot . '<span>' . esc_html__( 'Inactive', 'paid-member-subscriptions' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }

    }


    /*
     * Method that set custom updated messages
     *
     */
    function set_custom_messages( $messages ) {

        global $post;

        $messages[$this->post_type] = array(
            0  => 	'',
            1  => 	__( 'Content Drip Set updated.', 'paid-member-subscriptions' ),
            2  => 	__( 'Custom field updated.', 'paid-member-subscriptions' ),
            3  => 	__( 'Custom field deleted.', 'paid-member-subscriptions' ),
            4  => 	__( 'Content Drip Set updated.', 'paid-member-subscriptions' ),
            5  => 	isset( $_GET['revision'] ) ? sprintf( __( 'Content Drip Set restored to revision from %s', 'paid-member-subscriptions' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
            6  => 	__( 'Content Drip Set saved.', 'paid-member-subscriptions' ),
            7  => 	__( 'Content Drip Set saved.', 'paid-member-subscriptions' ),
            8  => 	__( 'Content Drip Set submitted.', 'paid-member-subscriptions' ),
            9  => 	sprintf( __( 'Content Drip Set scheduled for: <strong>%1$s</strong>.', 'paid-member-subscriptions' ), date_i18n( __( 'M j, Y @ G:i', 'paid-member-subscriptions' ), strtotime( $post->post_date ) ) ),
            10 =>	__( 'Content Drip Set draft updated.', 'paid-member-subscriptions' ),
        );

        return $messages;

    }



    /*
     * Enqueue admin scripts that are needed only on this post type
     *
     */
    public function enqueue_admin_scripts() {

        // Return if the post types doesn't match
        global $post;

        if( !$post || $post->post_type != $this->post_type )
            return;

        global $wp_scripts;

        // Try to detect if chosen has already been loaded
        $found_chosen = false;

        foreach( $wp_scripts as $wp_script ) {
            if( !empty( $wp_script['src'] ) && strpos($wp_script['src'], 'chosen') !== false )
                $found_chosen = true;
        }

        if( !$found_chosen ) {
            wp_enqueue_script( 'pms-chosen', PMS_IN_CONTENT_DRIPPING_DIR_URL . 'assets/libs/chosen/chosen.jquery.min.js', array( 'jquery' ), PMS_VERSION );
            wp_enqueue_style( 'pms-chosen', PMS_IN_CONTENT_DRIPPING_DIR_URL . 'assets/libs/chosen/chosen.css', array(), PMS_VERSION );
        }

    }

}


$args = array(
    'show_ui' => true,
    'show_in_menu' => 'paid-member-subscriptions',
    'query_var' => true,
    'capability_type' => 'post',
    'menu_position' => null,
    'supports' => array('title'),
    'hierarchical' => true
);

$pms_cpt_content_dripping = new PMS_IN_Content_Dripping_Custom_Post_Type( 'pms-content-dripping', __( 'Content Drip Set', 'paid-member-subscriptions' ), __( 'Content Drip Sets', 'paid-member-subscriptions' ), $args );
$pms_cpt_content_dripping->init();
