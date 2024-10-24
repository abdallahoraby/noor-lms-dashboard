<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// WP_List_Table is not loaded automatically in the plugins section
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


/*
 * Extent WP default list table for our custom members section
 *
 */
Class PMS_IN_Group_Members_List_Table extends WP_List_Table {

    public $items_per_page;
    public $data;
    private $total_items;

    /*
     * Constructor function
     *
     */
    public function __construct() {

        global $pagenow, $wp_importers, $hook_suffix, $plugin_page, $typenow, $taxnow;
        $page_hook = get_plugin_page_hook($plugin_page, $plugin_page);

        parent::__construct( array(
            'singular'  => 'group-member',
            'plural'    => 'group-members',
            'ajax'      => false,

            // Screen is a must!
            'screen'    => $page_hook
        ));

        // Set items per page
        $items_per_page = get_user_meta( get_current_user_id(), 'pms_members_per_page', true );

        if( empty( $items_per_page ) )
            $items_per_page = 10;

        $this->items_per_page = $items_per_page;

        // Set data
        $this->set_table_data();
    }


    /*
     * Overwrites the parent class.
     * Define the columns for the members
     *
     * @return array
     *
     */
    public function get_columns() {

        $columns = array(
            'email'   => __( 'Email', 'paid-member-subscriptions' ),
            'name'    => __( 'Name', 'paid-member-subscriptions' ),
            'status'  => __( 'Status', 'paid-member-subscriptions' ),
            'actions' => __( 'Actions', 'paid-member-subscriptions' )
        );

        return $columns;

    }


    /*
     * Overwrites the parent class.
     * Define which columns to hide
     *
     * @return array
     *
     */
    public function get_hidden_columns() {

        return array();

    }


    /*
     * Overwrites the parent class.
     * Define which columns are sortable
     *
     * @return array
     *
     */
    public function get_sortable_columns() {

        return array();

    }


    /*
     * Returns the table data
     *
     * @return array
     *
     */
    public function get_table_data() {

        $group_owner = isset( $_REQUEST['group_owner'] ) ? absint( $_REQUEST['group_owner'] ) : '';

        $members_list = pms_in_gm_get_group_members( $group_owner );

        $data = array();

        if( !empty( $members_list ) ){

            foreach( $members_list as $member_reference ){

                if( is_numeric( $member_reference ) ){
                    $member_user_id = pms_in_gm_get_member_subscription_user_id( $member_reference );

                    $data[] = array(
                        'email'   => pms_in_gm_get_email_by_user_id( $member_user_id ),
                        'name'    => pms_in_gm_get_user_name( $member_user_id, true ),
                        'status'  => $member_reference == $group_owner ? esc_html__( 'Owner', 'paid-member-subscriptions' ) : esc_html__( 'Registered', 'paid-member-subscriptions' ),
                        'actions' => $member_reference
                    );

                } else {

                    $data[] = array(
                        'email'   => $member_reference,
                        'name'    => '',
                        'status'  => esc_html__( 'Invited', 'paid-member-subscriptions' ),
                        'actions' => $member_reference
                    );

                }

            }

        }

        $this->total_items = count( $data );

        $paged  = ( isset( $_GET['paged'] ) ? (int)$_GET['paged'] : 1 );
        $offset = ( $paged - 1 ) * $this->items_per_page;

        if( !empty( $offset ) )
            $data = array_slice( $data, $offset );

        array_splice( $data, $this->items_per_page );

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

        $this->set_pagination_args( array(
            'total_items' => $this->total_items,
            'per_page'    => $this->items_per_page
        ));

        $this->_column_headers = array( $columns, $hidden_columns, $sortable );
        $this->items = $data;

    }


    protected function display_tablenav( $which ) {

        echo '<div class="tablenav ' . esc_attr( $which ) . '">';

            $this->pagination( $which );

            echo '<br class="clear" />';
        echo '</div>';

    }

    /*
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


    /*
     * Return data that will be displayed in the actions column
     *
     * @param array $item   - data of the current row
     *
     * @return string
     *
     */
    public function column_actions( $item ) {

        if( empty( $item['email'] ) )
            return $item['actions'];

        $group_owner = isset( $_REQUEST['group_owner'] ) ? absint( $_REQUEST['group_owner'] ) : '';

        $output = '<div class="members-list-row-actions">';

        $user = get_user_by( 'email', $item['email'] );
        $subscription = '';

        if( $user ){
            $subscription = pms_get_member_subscriptions( array( 'user_id' => $user->ID ) );

            if( !empty( $subscription[0] ) ){
                $label = pms_in_gm_is_group_owner( $subscription[0]->id ) ? esc_html__( 'Edit Member', 'paid-member-subscriptions' ) : esc_html__( 'View Member', 'paid-member-subscriptions' );

                $output .= '<a href="' . add_query_arg( array( 'page' => 'pms-members-page', 'subpage' => 'edit_subscription', 'subscription_id' => $subscription[0]->id ), 'admin.php' ) . '" class="button button-secondary">' . $label . '</a>';
            }

        } else
            $output .= '<a id="resend" data-reference="'.$item['email'].'" data-subscription="'.esc_attr( $group_owner ).'" href="#" class="button button-secondary">'. esc_html__( 'Resend Invite', 'paid-member-subscriptions' ) .'</a>';

        if( !empty( $group_owner ) && ( empty( $subscription[0] ) || $subscription[0]->id != $group_owner ) )
            $output .= $this->get_remove_action( $item['email'], $group_owner );

        $output .= '</div>';

        return $output;

    }


    /*
     * Display if no items are found
     *
     */
    public function no_items() {

        echo esc_html__( 'No Members in this Group', 'paid-member-subscriptions' );

    }

    private function get_remove_action( $reference, $group_owner ){
        ob_start(); ?>

        <form method="POST">
            <input type="hidden" name="pms_reference" value="<?php echo esc_attr( $reference ); ?>">
            <input type="hidden" name="pms_subscription_id" value="<?php echo esc_attr( $group_owner ); ?>">
            <?php wp_nonce_field( 'pms_remove_members_form_nonce', 'pmstkn' ); ?>

            <input type="submit" class="button button-secondary" value="<?php esc_html_e( 'Remove', 'paid-member-subscriptions' ) ?>">
        </form>
        <?php

        return ob_get_clean();
    }
}
