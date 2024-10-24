<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// WP_List_Table is not loaded automatically in the plugins section
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


Class PMS_IN_Group_Info_List_Table extends WP_List_Table {

    private $group_owner_id;

    /*
     * Constructor function
     *
     */
    public function __construct() {

        global $pagenow, $wp_importers, $hook_suffix, $plugin_page, $typenow, $taxnow;
        $page_hook = get_plugin_page_hook($plugin_page, $plugin_page);

        parent::__construct( array(
            'singular'  => 'group-info',
            'plural'    => 'group-info',
            'ajax'      => false,

            // Screen is a must!
            'screen'    => $page_hook
        ));

        $this->group_owner_id = ( ! empty( $_GET['group_owner'] ) ? (int)$_GET['group_owner'] : 0 );
    }


    /**
     * Overwrites the parent class.
     * Define the columns for the members
     *
     * @return array
     *
     */
    public function get_columns() {

        $columns = array(
            'group_name' => esc_html__( 'Name', 'paid-member-subscriptions' ),
            'members'    => esc_html__( 'Members', 'paid-member-subscriptions' ),
            'seats'      => esc_html__( 'Seats', 'paid-member-subscriptions' ),
            'start_date' => esc_html__( 'Start Date', 'paid-member-subscriptions' ),
        );

        $subscription = pms_get_member_subscription( $this->group_owner_id );

        if( !empty( $subscription->billing_next_payment ) )
            $columns['next_payment'] = esc_html__( 'Next Payment', 'paid-member-subscriptions' );

        $columns['actions'] = esc_html__( 'Actions', 'paid-member-subscriptions' );

        return $columns;

    }


    /**
     * Overwrites the parent class.
     * Define which columns to hide
     *
     * @return array
     *
     */
    public function get_hidden_columns() {

        return array();

    }


    /**
     * Overwrites the parent class.
     * Define which columns are sortable
     *
     * @return array
     *
     */
    public function get_sortable_columns() {

        return array();

    }

    /**
     * Returns the table data
     *
     * @return array
     *
     */
    public function get_table_data() {

        $data = array();

        $subscription = pms_get_member_subscription( $this->group_owner_id );

        $data[] = array(
            'group_name'   => pms_get_member_subscription_meta( $subscription->id, 'pms_group_name', true ),
            'members'      => pms_in_gm_get_used_seats( $subscription->id ),
            'seats'        => pms_in_gm_get_total_seats( $subscription ),
            'start_date'   => ucfirst( date_i18n( 'F d, Y', strtotime( $subscription->start_date ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) ),
            'next_payment' => $this->generate_billing_info( $subscription->billing_next_payment, $subscription->billing_amount ),
            'actions'      => ''
        );

        return $data;

    }


    /*
     * Populates the items for the table
     *
     */
    public function prepare_items() {

        $columns = $this->get_columns();
        $hidden_columns = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $data = $this->get_table_data();

        $this->_column_headers = array( $columns, $hidden_columns, $sortable );
        $this->items = $data;

    }


    /**
     * Return data that will be displayed in each column
     *
     * @param array $item           - data for the current row
     * @param string $column_name   - name of the current column
     *
     * @return string
     *
     */
    public function column_default( $item, $column_name ) {

        if( !isset( $item[ $column_name ] ) )
            return false;

        return $item[ $column_name ];

    }


    /**
     * Return data that will be displayed in the group name column
     *
     * @param array $item           - data for the current row
     *
     * @return string
     *
     */
    public function column_group_name( $item ) {

        $output = '<span>' . $item['group_name'] . '</span>';

        $output .= '<input type="text" name="pms_group_name" value="'.$item['group_name'].'" >';

        return $output;

    }

    /**
     * Return data that will be displayed in the group name column
     *
     * @param array $item           - data for the current row
     *
     * @return string
     *
     */
    public function column_seats( $item ) {

        $output = '<span>' . $item['seats'] . '</span>';

        $output .= '<input type="number" name="pms_seats" value="'.$item['seats'].'" >';

        return $output;

    }

    /**
     * Return data that will be displayed in the actions column
     *
     * @param array $item           - data for the current row
     *
     * @return string
     *
     */
    public function column_actions( $item ) {

        $output = '<div class="row-actions">';

            $output .= '<a href="" id="edit" class="button button-secondary">' . __( 'Edit', 'paid-member-subscriptions' ) . '</a>';

            $output .= '<a href="" id="save" class="button button-secondary">' . __( 'Save', 'paid-member-subscriptions' ) . '</a>';

        $output .= '</div>';

        return $output;

    }

    private function generate_billing_info( $date, $amount ){
        $currency_symbol = pms_get_currency_symbol( pms_get_active_currency() );
        $date = ucfirst( date_i18n( 'F d, Y', strtotime( $date ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) );

        return sprintf( esc_html__( '%d%s on %s', 'paid-member-subscriptions' ), $amount, $currency_symbol, $date );
    }

}
