<?php
if( !function_exists( 'pms_get_member' ) )
    return;

class PMS_IN_Nav_Menu_Filtering{

    function __construct(){
        // switch the admin walker
        add_filter( 'wp_edit_nav_menu_walker', array( $this, 'change_nav_menu_walker' ) );

        // add extra fields in menu items on the hook we define in the walker class
        add_action( 'wp_nav_menu_item_custom_fields', array( $this, 'extra_fields' ), 9, 4);

        // save the extra fields
        add_action( 'wp_update_nav_menu_item', array( $this, 'update_menu' ), 10, 2);

        // exclude items from frontend
        if ( ! is_admin() )
            add_filter( 'wp_get_nav_menu_items', array( $this, 'hide_menu_elements' ) );

        // enqueue needed resources
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

        // add metabox with custom elements to the Appearance -> Menus page
        add_action( 'admin_head-nav-menus.php', array( $this, 'add_metabox' ) );

        // change type for our custom elements
        add_filter( 'wp_setup_nav_menu_item', array( $this, 'change_items_type' ) );

        // setup URLs for the new menu items
        add_filter( 'wp_setup_nav_menu_item', array( $this, 'setup_items_url' ), 99 );

        // hide custom items from front-end
        if ( !is_admin() )
            add_filter( 'wp_get_nav_menu_items', array( $this, 'hide_items' ) );
    }

    /*
     * Change the default walker class for the menu
     *
     * @param $walker the filtered walker class
     * @return string new walker
     *
     */
    function change_nav_menu_walker( $walker ){
        global $wp_version;
        if ( version_compare( $wp_version, "5.4", "<" ) ) {
            $walker = 'PMS_Walker_Nav_Menu';
        }

        return $walker;
    }

    /*
     * @param $hook the current admin page
     *
     */
    function enqueue_scripts( $hook ){

        if ( 'nav-menus.php' != $hook ) {
            return;
        }

        wp_enqueue_script( 'pms_nav_menu_script', PMS_IN_NMF_PLUGIN_URL . 'assets/js/admin/pms-nav-menu-filtering.js' );
        wp_enqueue_style( 'pms_nav_menu_script', PMS_IN_NMF_PLUGIN_URL . 'assets/css/pms-nav-menu-filtering.css' );
    }

    /*
     * Function that ads extra fields on the hook we added in the walker class
     *
     */
    function extra_fields( $item_id, $item, $depth, $args ) {
        $lilo                     = get_post_meta( $item->ID, '_pms_menu_lilo', true );
        $saved_subscription_plans = explode( ',', get_post_meta( $item->ID, '_pms_content_restrict_subscription_plan', true ) );
        ?>

        <input type="hidden" name="pms-menu-filtering" value="<?php echo esc_attr( wp_create_nonce('pms-menu-filtering') ); ?>"/>

        <?php do_action( 'pms_nav_menu_extra_fields_top', $item ); ?>

        <?php if ( $item->type == 'pms_logout' ) : ?>
            <?php $logout_redirect_page = get_post_meta( $item->ID, '_pms_nmf_logout_redirect', true ); ?>

            <div class="pms-options">
                <p class="description"><?php esc_html_e( 'Logout redirect URL', 'paid-member-subscriptions' ); ?></p>

                <label class="pms-menu-item-url-label" for="pms-menu-li-<?php echo esc_attr( $item->ID ); ?>">

                    <select id="pms-nmf-logout-page" name="pms-nmf-logout-page-<?php echo esc_attr( $item->ID ); ?>" class="widefat code edit-menu-item-url">
                        <option value="-1"><?php esc_html_e( 'Choose...', 'paid-member-subscriptions' ) ?></option>

                        <?php
                        foreach( get_pages() as $page )
                            echo '<option value="' . esc_attr( $page->ID ) . '"' . selected( $logout_redirect_page, $page->ID, false ) . '>' . esc_html( $page->post_title ) . ' (ID: ' . esc_attr( $page->ID ) . ')' . '</option>';
                        ?>
                    </select>

                </label>
            </div>
        <?php endif; ?>

        <div class="pms-options">
            <p class="description"><?php esc_html_e("Display To", 'paid-member-subscriptions'); ?></p>

            <label class="pms-menu-item-radio-label" for="pms-menu-li-<?php echo esc_attr( $item->ID ); ?>">
                <input type="radio" name="pms-menu-lilo-<?php echo esc_attr( $item->ID ); ?>" class="pms-lilo" id="pms-menu-li-<?php echo esc_attr( $item->ID ); ?>" <?php checked( 'loggedin', $lilo ); ?> value="loggedin"/>
                <?php esc_html_e('Logged In Users', 'paid-member-subscriptions'); ?>
            </label>

            <label class="pms-menu-item-radio-label" for="pms-menu-lo-<?php echo esc_attr( $item->ID ); ?>">
                <input type="radio" name="pms-menu-lilo-<?php echo esc_attr( $item->ID ); ?>" class="pms-lilo" id="pms-menu-lo-<?php echo esc_attr( $item->ID ); ?>" <?php checked( 'loggedout', $lilo ); ?> value="loggedout"/>
                <?php esc_html_e('Logged Out Users', 'paid-member-subscriptions'); ?>
            </label>


            <label class="pms-menu-item-radio-label" for="pms-menu-lilo-<?php echo esc_attr( $item->ID ); ?>">
                <input type="radio" name="pms-menu-lilo-<?php echo esc_attr( $item->ID ); ?>" class="pms-lilo" id="pms-menu-lilo-<?php echo esc_attr( $item->ID ); ?>" <?php checked( '', $lilo ); ?> value=""/>
                <?php esc_html_e('Everyone', 'paid-member-subscriptions'); ?>
            </label>

            <label class="pms-menu-item-radio-label" for="pms-menu-non-members-<?php echo esc_attr( $item->ID ); ?>">
                <input type="radio" name="pms-menu-lilo-<?php echo esc_attr( $item->ID ); ?>" class="pms-lilo" id="pms-menu-non-members-<?php echo esc_attr( $item->ID ); ?>" <?php checked( 'non-members', $lilo ); ?> value="non-members"/>
                <?php esc_html_e('Non-Members', 'paid-member-subscriptions'); ?>
            </label>

            <label class="pms-menu-item-radio-label" for="pms-menu-group-owner-<?php echo esc_attr( $item->ID ); ?>">
                <input type="radio" name="pms-menu-lilo-<?php echo esc_attr( $item->ID ); ?>" class="pms-lilo" id="pms-menu-group-owner-<?php echo esc_attr( $item->ID ); ?>" <?php checked( 'group-owner', $lilo ); ?> value="group-owner"/>
                <?php esc_html_e('Group Owner', 'paid-member-subscriptions'); ?>
            </label>

        </div>

        <?php
        $subscription_plans = pms_get_subscription_plans();

        if( !empty( $subscription_plans ) ) :
        ?>
            <div class="pms-subscription-plans">
                <p class="description"><?php esc_html_e("Limit logged in users to Subscriptions", 'paid-member-subscriptions'); ?></p>

                <?php foreach( $subscription_plans as $subscription_plan ) : ?>
                    <label for="pms-content-restrict-subscription-plan-<?php echo esc_attr( $subscription_plan->id ); ?>-<?php echo esc_attr( $item->ID ); ?>" class="pms-menu-item-checkbox-label">
                        <input type="checkbox" <?php if( $lilo != 'loggedin' ) echo 'disabled'; ?> value="<?php echo esc_attr( $subscription_plan->id ); ?>" <?php if( in_array( $subscription_plan->id, $saved_subscription_plans ) ) echo 'checked="checked"'; ?> name="pms-content-restrict-subscription-plan_<?php echo esc_attr( $item->ID ); ?>[]" id="pms-content-restrict-subscription-plan-<?php echo esc_attr( $subscription_plan->id ); ?>-<?php echo esc_attr( $item->ID ); ?>">
                        <?php echo esc_html( $subscription_plan->name ); ?>
                    </label>
                <?php endforeach; ?>

            </div>
        <?php endif;

        do_action( 'pms_nav_menu_extra_fields_bottom', $item );
    }

    /**
     * Save the values on the menu
     */
    function update_menu( $menu_id, $menu_item_db_id ){

        // verify this came from our screen and with proper authorization.
        if( !isset( $_POST['pms-menu-filtering'] ) || !wp_verify_nonce( sanitize_text_field( $_POST['pms-menu-filtering'] ), 'pms-menu-filtering' ) )
            return;

        if( !empty( $_POST['pms-menu-lilo-'.$menu_item_db_id] ) )
            update_post_meta( $menu_item_db_id, '_pms_menu_lilo', sanitize_text_field( $_POST['pms-menu-lilo-'.$menu_item_db_id] ) );
        else
            delete_post_meta( $menu_item_db_id, '_pms_menu_lilo' );


        if( !empty( $_REQUEST['pms-content-restrict-subscription-plan_'.$menu_item_db_id] ) && is_array( $_REQUEST['pms-content-restrict-subscription-plan_'.$menu_item_db_id] ) ) {

            $subscription_plan_ids = array_map( 'absint', $_REQUEST['pms-content-restrict-subscription-plan_'.$menu_item_db_id] );

            update_post_meta( $menu_item_db_id, '_pms_content_restrict_subscription_plan', implode( ',', $subscription_plan_ids ) );

        } else
            delete_post_meta( $menu_item_db_id, '_pms_content_restrict_subscription_plan' );

        if( !empty( $_REQUEST['pms-nmf-logout-page-' . $menu_item_db_id] ) )
            update_post_meta( $menu_item_db_id, '_pms_nmf_logout_redirect', sanitize_text_field( $_REQUEST['pms-nmf-logout-page-' . $menu_item_db_id] ) );
        else
            delete_post_meta( $menu_item_db_id, '_pms_nmf_logout_redirect' );
    }

    /**
     * Function that hides the elements on the frontend
     * @param $items the filtered item objects in the menu
     */
    function hide_menu_elements( $items ){
        $hide_children_of = array();

        // Iterate over the items to search and destroy
        foreach ( $items as $key => $item ) {

            $visible = true;

            // hide any item that is the child of a hidden item
            if( in_array( $item->menu_item_parent, $hide_children_of ) ){
                $visible = false;
                $hide_children_of[] = $item->ID; // for nested menus
            }

            // check any item that has NMR roles set
            if( $visible ){

                $lilo_option        = get_post_meta( $item->ID, '_pms_menu_lilo', true );
                $subscription_plans = get_post_meta( $item->ID, '_pms_content_restrict_subscription_plan', true );

                // check all logged in, all logged out, or role
                if ( current_user_can( 'manage_options' ) && apply_filters( 'pms_nmf_show_for_admin', true ) )
                    $visible = true;
                elseif( empty( $lilo_option ) || $lilo_option == '' ){
                    $visible = true;
                }
                elseif( $lilo_option == 'loggedout' ){
                    if( is_user_logged_in() )
                        $visible = false;
                }
                elseif( $lilo_option == 'loggedin' ) {
                    if( !is_user_logged_in() ){
                        $visible = false;
                    }
                    else{
                        if( !empty( $subscription_plans ) ){

                            $subscription_plans = explode( ',', $subscription_plans );

                            if ( !pms_is_member_of_plan( $subscription_plans ) )
                                $visible = false;

                        }

                    }
                }
                elseif( $lilo_option == 'non-members' ){
                    if( !is_user_logged_in() ) {
                        $visible = false;
                    }
                    else{
                        if( pms_is_member( get_current_user_id() ) ){
                            $visible = false;
                        }
                    }
                }
                elseif( $lilo_option == 'group-owner' ){
                    if( !is_user_logged_in() || !function_exists('pms_in_gm_is_group_owner' ) ){
                        $visible = false;
                    }
                    else{
                        $visible = false;
                        $user_id = get_current_user_id();
                        $member = pms_get_member( $user_id );
                        if( !empty( $member->subscriptions ) ){
                            foreach( $member->subscriptions as $subscription ) {
                                $plan = pms_get_subscription_plan( $subscription['subscription_plan_id'] );
                                if( $plan->type == 'group' && pms_in_gm_is_group_owner( $subscription['id'] ) ){
                                    $visible = true;
                                }
                            }
                        }
                    }
                }

            }

            // add filter to work with plugins that don't use traditional roles
            $visible = apply_filters( 'nav_menu_roles_item_visibility', $visible, $item );

            // unset non-visible item
            if ( ! $visible ) {
                $hide_children_of[] = $item->ID; // store ID of item
                unset( $items[$key] ) ;
            }

        }

        return $items;
    }

    function get_metabox_items() {
        return array(
            'pms_retry'   => __( 'Retry Payment', 'paid-member-subscriptions' ),
            'pms_abandon' => __( 'Abandon Subscription', 'paid-member-subscriptions' ),
            'pms_cancel'  => __( 'Cancel Subscription', 'paid-member-subscriptions' ),
            'pms_renew'   => __( 'Renew Subscription', 'paid-member-subscriptions' ),
            'pms_upgrade' => __( 'Upgrade Subscription', 'paid-member-subscriptions' ),
            'pms_logout'  => __( 'Logout', 'paid-member-subscriptions' ),
        );
    }

    function add_metabox() {
        add_meta_box(
            'pms-nmf-box',
            'Paid Member Subscriptions',
            array( $this, 'metabox_content' ),
            'nav-menus',
            'side',
            'low'
        );
    }

    function metabox_content() {
        global $nav_menu_selected_id;

        include_once PMS_IN_NMF_PLUGIN_DIR . '/includes/class-pms-nmf-item.php';

        $elements = array();

        foreach( $this->get_metabox_items() as $value => $title ) {
            $elements[$value]            = new PMS_IN_NMF_Item();
            $elements[$value]->object_id = esc_attr( $value );
            $elements[$value]->title     = esc_attr( $title );
            $elements[$value]->url       = '';
            $elements[$value]->object    = esc_attr( $value );
            $elements[$value]->type      = esc_attr( $value );
        }

        $walker = new Walker_Nav_Menu_Checklist( array() );
        ?>
    	<div id="pms-nmf-links" class="pms-nmf-links-div">
    		<div id="tabs-panel-pms-nmf-links-all" class="tabs-panel tabs-panel-view-all tabs-panel-active">
    			<ul id="pms-nmf-links-checklist" class="list:pms-nmf-links categorychecklist form-no-clear">
    				<?php echo walk_nav_menu_tree( array_map( 'wp_setup_nav_menu_item', $elements ), 0, (object) array( 'walker' => $walker ) ); ?>
    			</ul>
    		</div>

    		<p class="button-controls">
    			<span class="add-to-menu">
    				<input type="submit"<?php disabled( $nav_menu_selected_id, 0 ); ?> class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e( 'Add to Menu', 'paid-member-subscriptions' ); ?>" name="add-pms-nmf-links-menu-item" id="submit-pms-nmf-links" />
    				<span class="spinner"></span>
    			</span>
    		</p>
    	</div>

        <?php
    }

    function change_items_type( $item ) {

        if( isset( $item->object, $item->url ) && in_array( $item->type, array_keys( $this->get_metabox_items() ) ) ) {
            $item->type_label = 'Paid Member Subscriptions';
            $item->object     = 'custom';
        }

        return $item;
    }

    function setup_items_url( $item ) {
        if( !pms_get_page( 'account' ) )
            return $item;

        global $pagenow;

        if( $pagenow == 'nav-menus.php' )
            return $item;

        if( !isset( $item->type ) )
            return $item;

        if( !in_array( $item->type, array_keys( $this->get_metabox_items() ) ) )
            return $item;

        if ( $item->type == 'pms_logout' ){
            $redirect = get_post_meta( $item->ID, '_pms_nmf_logout_redirect', true );

            $item->url = wp_logout_url( get_permalink( $redirect ) );
        } else {
            $action       = str_replace( 'pms_', '', $item->type );
            $url_function = 'pms_get_' . $action . '_url';

            if( !function_exists( $url_function ) )
                return $item;

            // compatibility with Multiple Subscriptions Per User
            $plan_id = get_post_meta( $item->ID, '_pms_msu_nav_menu_subscription', true );

            if( empty( $plan_id ) || $plan_id == '-1' )
                $plan_id = '';

            $item->url = $url_function( $plan_id );
        }

        return $item;
    }

    function hide_items( $items ) {

        foreach( $items as $key => $item ) {
            if( !in_array( $item->type, array_keys( $this->get_metabox_items() ) ) )
                continue;

            if( empty( $item->url ) )
                unset( $items[$key] );
        }

        return $items;
    }

}

new PMS_IN_Nav_Menu_Filtering();
