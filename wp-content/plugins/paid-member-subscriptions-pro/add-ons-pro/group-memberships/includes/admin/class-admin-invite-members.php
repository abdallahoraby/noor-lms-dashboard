<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// WP_List_Table is not loaded automatically in the plugins section
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


Class PMS_IN_Invite_Members_Table extends WP_List_Table {

    private $group_owner_id;
    private $member_subscription;

    /*
     * Constructor function
     *
     */
    public function __construct() {

        global $pagenow, $wp_importers, $hook_suffix, $plugin_page, $typenow, $taxnow;
        $page_hook = get_plugin_page_hook($plugin_page, $plugin_page);

        parent::__construct( array(
            'singular'  => 'invite-member',
            'plural'    => 'invite-members',
            'ajax'      => false,

            // Screen is a must!
            'screen'    => $page_hook
        ));

        $this->group_owner_id = ( ! empty( $_GET['group_owner'] ) ? (int)$_GET['group_owner'] : 0 );
        $this->member_subscription = pms_get_member_subscription( $this->group_owner_id );
    }


    /**
     * Overwrites the parent class.
     * Define the columns for the members
     *
     * @return array
     *
     */
    public function get_columns() {

        $column_title = array(
            'invite_new_members'   => __( 'Invite Members via Email', 'paid-member-subscriptions' ),
            'add_existing_users'   => __( 'Add Existing Users', 'paid-member-subscriptions' ),
        );


        return $column_title;

    }


    /**
     * Returns the table data
     *
     * @return array
     *
     */
    public function get_table_data() {

        $data = array();

        $data[] = array(
            'invite_new_members'   => __( 'Invite Members via Email', 'paid-member-subscriptions' ),
            'add_existing_users'   => __( 'Add Existing Users', 'paid-member-subscriptions' ),
        );

        return $data;

    }


    /**
     * Populates the items for the table
     *
     */
    public function prepare_items() {

        $columns = $this->get_columns();

        $data = $this->get_table_data();

        $this->_column_headers = array( $columns );
        $this->items = $data;

    }


    /**
     * Return data that will be displayed in the Invite New Members section
     *
     * @param array $item           - data for the current row
     *
     * @return string
     *
     */
    public function column_invite_new_members( $item ) {


        $output = '';
        ob_start();

        include_once 'views/view-admin-invite-new-members.php';

        $output .= ob_get_clean();

        return $output;

    }


    /**
     * Return data that will be displayed in the Add New Members section
     *
     * @param array $item           - data for the current row
     *
     * @return string
     *
     */
    public function column_add_existing_users( $item ) {

        if( apply_filters( 'pms_gm_admin_enable_add_existing_users', true ) === false )
            return;

        $output = '';
        ob_start();

        include_once 'views/view-admin-add-existing-users.php';

        $output .= ob_get_clean();

        return $output;

    }



}
