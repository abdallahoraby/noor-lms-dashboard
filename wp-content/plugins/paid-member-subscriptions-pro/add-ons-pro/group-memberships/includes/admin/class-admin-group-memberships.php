<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

Class PMS_IN_Admin_Group_Memberships {

	public function __construct(){

		// Figure out how to add the Group subscription type to the Subscription Plans interface
		add_action( 'admin_init',                                 array( $this, 'hook_subscription_plan_type_change' ) );

		// Add the admin field to specify the available seats
		add_action( 'pms_view_meta_box_subscription_details_top', array( $this, 'add_subscription_plan_seats_field' ) );

		// Save the extra fields from the Subscription Plan Details meta-box on post save
		add_action( 'pms_save_meta_box_pms-subscription',         array( $this, 'save_subscription_plan_settings_fields' ) );

		// Allow admins to define new subscription plan tiers
		add_filter( 'pms_action_add_new_subscription_plan',       array( $this, 'members_list_allow_add_new_action' ) );

		// Display custom 'group' column in the Members List
		add_filter( 'pms_members_list_table_columns',             array( $this, 'members_list_add_group_column' ) );

		// Populate the group column
		add_filter( 'pms_members_list_table_entry_data',          array( $this, 'members_list_add_group_column_data' ), 20, 2 );

		// Add Filter by Group select
		add_action( 'pms_members_list_extra_table_nav',           array( $this, 'members_list_add_group_filter' ) );

		// Filter Members by Group
		add_filter( 'pms_get_members_args',                       array( $this, 'members_list_filter_members_by_group' ) );

		// Remove Views count when filtering by Groups
		add_filter( 'pms_members_list_table_get_views',           array( $this, 'members_list_remove_views' ) );

		// Add `Edit Owner` custom row action
		add_filter( 'pms_members_list_username_actions',          array( $this, 'members_list_add_edit_owner_action' ), 20, 2 );

		// Filter page output to add the Group Details and Edit Page
		add_filter( 'pms_submenu_page_members_output',            array( $this, 'members_list_output' ) );

		// Edit Group Details
		add_action( 'wp_ajax_pms_edit_group_details',             array( $this, 'members_list_edit_group_details' ) );

        // Determine subscription type
        add_action( 'wp_ajax_determine_subscription_type',         array( $this, 'ajax_determine_subscription_type' ) );

        // Add Group Name and Description Fields
        add_action( 'pms_admin_new_subscription_after_form_fields', array( $this, 'add_group_fields' ) );

		// Validate fields
        add_filter( 'pms_submenu_page_members_validate_subscription_data', array( $this, 'validate_group_fields' ), 20, 2 );

		// Remove Members functionality
		add_action( 'admin_init',                                 array( $this, 'members_list_remove_members' ) );

		// Change Group Owner
		add_action( 'admin_init',                                 array( $this, 'change_group_owner' ) );

		// Errors when saving subscription plans
		add_action( 'pre_post_update', 				  			  array( $this, 'validate_group_subscription_plan_save' ), 20, 3 );
		add_action( 'pms_cpt_admin_notice_messages', 			  array( $this, 'validate_group_subscription_plan_messages' ) );

		// Add Mange Group action on the Edit Member page
		add_filter( 'pms_member_subscription_list_table_column_actions',               array( $this, 'add_member_subscription_list_manage_group_action' ), 20, 2 );

		// Add extra information for Owner and Child subscriptions on the Edit Subscriptions page
		add_action( 'pms_view_edit_subscription_after_member_data',                    array( $this, 'show_group_information_on_edit_subscription_page' ) );

		// Disable editing of child subscriptions
		add_action( 'pms_view_edit_add_new_subscription_disable_subscription_editing', array( $this, 'disable_admin_child_subscriptions_editing' ) );

		// On the Edit Member page, the Subscriptions List table will display information about the Owner subscriptions instead of the child subscription
		add_filter( 'pms_member_subscription_list_table_data',                         array( $this, 'replace_child_subscription_data_with_parent' ), 20, 2 );

	}

	public function hook_subscription_plan_type_change(){

	    /**
	     * If the Fixed Period Membership add-on is active, hook into its subscription types, else add our own Select field
	     */
	    if( function_exists( 'pms_msfp_add_subscription_plan_settings_fields' ) )
	    	add_filter( 'pms_subscription_plan_types', array( $this, 'add_subscription_plan_type_filter' ) );
	    else
	    	add_action( 'pms_view_meta_box_subscription_details_top', array( $this, 'add_subscription_plan_type' ) );

	}

	// If not already present, generate the Subscription Plan Type Select field
	public function add_subscription_plan_type( $subscription_plan_id ){

		$subscription_type = get_post_meta( $subscription_plan_id, 'pms_subscription_plan_type', true );

		$types = array(
			'regular' => esc_html__( 'Regular', 'paid-member-subscriptions' ),
			'group'   => esc_html__( 'Group', 'paid-member-subscriptions' )
		);

		$types = apply_filters( 'pms_subscription_plan_types', $types );
		?>

		<!-- Subscription Plan Type -->
		<div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper">
		    <label for="pms-subscription-plan-type" class="pms-meta-box-field-label cozmoslabs-form-field-label">
				<?php esc_html_e( 'Subscription Type', 'paid-member-subscriptions' ); ?>
			</label>

		    <select id="pms-subscription-plan-type" name="pms_subscription_plan_type">

				<?php foreach( $types as $slug => $label ) : ?>
					<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $subscription_type, $slug ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>

		    </select>
		    <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e( 'Please select the type for this subscription plan.', 'paid-member-subscriptions' ); ?></p>
		</div>

		<?php

	}

	// Add the necessary type to the existing select
	public function add_subscription_plan_type_filter( $types ){

		$types['group'] = esc_html__( 'Group', 'paid-member-subscriptions' );

		return $types;

	}

	public function add_subscription_plan_seats_field( $subscription_plan_id ){

		$seats = get_post_meta( $subscription_plan_id, 'pms_subscription_plan_seats', true );

		// calculate min
		$min_seats = 2;

		if( isset( $_GET['pms-action'], $_GET['plan_id'] ) && $_GET['pms-action'] == 'add_upgrade' ){

			$min_seats = get_post_meta( absint( $_GET['plan_id'] ), 'pms_subscription_plan_seats', true );

		} elseif ( isset( $_GET['action'], $_GET['post'] ) && $_GET['action'] == 'edit' ){

			$group = pms_get_subscription_plans_group( absint( $_GET['post'] ) );

			if( !empty( $group ) && count( $group ) > 1 ){

				$downgrade_key = '';

				foreach( $group as $key => $plan ){
					if( $plan->id == $_GET['post'] )
						$downgrade_key = $key + 1;
				}

				if( $downgrade_key != count( $group ) )
					$min_seats = get_post_meta( $group[$downgrade_key]->id, 'pms_subscription_plan_seats', true );

			}

		}
		?>

		<div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper">

			<label for="pms-subscription-plan-seats" class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'Seats', 'paid-member-subscriptions' ); ?></label>

			<input type="number" name="pms_subscription_plan_seats" id="pms-subscription-plan-seats" min="<?php echo !empty( $min_seats ) ? esc_attr( $min_seats ) : 2; ?>" value="<?php echo esc_attr( $seats ); ?>" />

			<p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e( 'The number of additional members, including the owner, that can be added to the subscription.', 'paid-member-subscriptions' ); ?></p>

		</div>
	<?php
	}

	public function save_subscription_plan_settings_fields( $subscription_plan_id ){

		if( empty( $_POST['post_ID'] ) || $subscription_plan_id != $_POST['post_ID'] )
	        return;

	    if( isset( $_POST['pms_subscription_plan_type'] ) )
	        update_post_meta( $subscription_plan_id, 'pms_subscription_plan_type', sanitize_text_field( $_POST['pms_subscription_plan_type'] ) );

	    if( isset( $_POST['pms_subscription_plan_seats'] ) )
	        update_post_meta( $subscription_plan_id, 'pms_subscription_plan_seats', sanitize_text_field( $_POST['pms_subscription_plan_seats'] ) );

	}

	public function members_list_add_group_column( $columns ){
		$subscriptions = $columns['subscriptions'];

		unset( $columns['subscriptions'] );

		$columns['group']         = __( 'Group', 'paid-member-subscriptions' );
		$columns['subscriptions'] = $subscriptions;

		return $columns;
	}

	public function members_list_add_group_column_data( $data, $member ){

		if( empty( $member->subscriptions ) )
			return $data;

		//determine id
		$subscription_id = '';
		foreach( $member->subscriptions as $subscription ){
			$plan = pms_get_subscription_plan( $subscription['subscription_plan_id'] );

			if( $plan->type != 'group' )
				continue;

			$subscription_id = $subscription['id'];
			break;
		}

		if( empty( $subscription_id ) )
			return $data;

		if( pms_in_gm_is_group_owner( $subscription_id ) )
			$owner_subscription_id = $subscription_id;
		else
			$owner_subscription_id = pms_get_member_subscription_meta( $subscription_id, 'pms_group_subscription_owner', true );

		if( empty( $owner_subscription_id ) )
			return $data;

		$group_name = pms_in_gm_get_group_name( $subscription_id );

		if( empty( $group_name ) )
			$group_name = 'Undefined';

		$data['group'] = sprintf( '<a href="%s">%s</a>', add_query_arg( array( 'subpage' => 'group_details', 'group_owner' => $owner_subscription_id ), admin_url( 'admin.php?page=pms-members-page' ) ), $group_name );

		return $data;

	}

	public function members_list_allow_add_new_action( $action ){
	    return 'allow';
	}

	public function members_list_add_group_filter(){

		$groups = $this->get_all_active_group_names();

		if( empty( $groups ) )
			return;

		echo '<div>';
            echo '<select name="pms-filter-group" id="pms-filter-group">';
                echo '<option value="">' . esc_html__( 'Group...', 'paid-member-subscriptions' ) . '</option>';

                foreach( $groups as $group )
                    echo '<option value="' . esc_attr( $group['member_subscription_id'] ) . '" ' . ( !empty( $_GET['pms-filter-group'] ) ? selected( $group['member_subscription_id'], sanitize_text_field( $_GET['pms-filter-group'] ), false ) : '' ) . '>' . esc_html( $group['meta_value'] ) . '</option>';
            echo '</select>';
		echo '</div>';

	}

	public function members_list_filter_members_by_group( $args ){

		if( !is_admin() || !isset( $_GET['pms-filter-group'] ) || empty( $_GET['pms-filter-group'] ) )
			return $args;

		$args['group_owner'] = sanitize_text_field( $_GET['pms-filter-group'] );

		return $args;

	}

	public function members_list_remove_views( $views ){
		if( !empty( $_GET['pms-filter-group'] ) )
			return array();

		return $views;
	}

	public function members_list_add_edit_owner_action( $actions, $item ){
		if( empty( $item['subscriptions'][0] ) )
			return $actions;

		$plan = pms_get_subscription_plan( $item['subscriptions'][0]->subscription_plan_id );

		if( $plan->type != 'group' )
			return $actions;

		$owner_subscription_id = pms_get_member_subscription_meta( $item['subscriptions'][0]->id, 'pms_group_subscription_owner', true );

		if( empty( $owner_subscription_id ) )
			return $actions;

		$owner_subscription = pms_get_member_subscription( $owner_subscription_id );

		if( empty( $owner_subscription ) )
			return $actions;

		$actions['group_owner_edit'] = '<a href="' . add_query_arg( array( 'subpage' => 'edit_member', 'member_id' => $owner_subscription->user_id ) ) . '">' . __( 'Edit Owner', 'paid-member-subscriptions' ) . '</a>';

		return $actions;
	}

	public function members_list_output( $content ){

		if( isset( $_GET['subpage'] ) && $_GET['subpage'] == 'group_details' && !empty( $_GET['group_owner'] ) )
			include_once 'views/view-page-members-group-details.php';
		else
			return $content;

	}

	public function members_list_edit_group_details(){
		check_ajax_referer( 'pms_gm_admin_edit_group_details_nonce', 'security' );

		if( empty( $_POST['owner_id'] ) )
			$this->ajax_response( 'error', esc_html__( 'Something went wrong.', 'paid-member-subscriptions' ) );

		if( empty( $_POST['group_name'] ) || empty( $_POST['seats'] ) || empty( $_POST['owner_id'] ) )
			die();

		$group_name      = sanitize_text_field( $_POST['group_name'] );
		$group_seats     = absint( $_POST['seats'] );
		$subscription_id = absint( $_POST['owner_id'] );

		$subscription = pms_get_member_subscription( $subscription_id );

		//Validate
		if( empty( $subscription->id ) )
			pms_errors()->add( 'subscription', esc_html__( 'Invalid subscriptions.', 'paid-member-subscriptions' ) );

		if( empty( $group_name ) )
			pms_errors()->add( 'group_name', esc_html__( 'Group name cannot be empty.', 'paid-member-subscriptions' ) );

		if( empty( $group_seats ) )
			pms_errors()->add( 'group_seats', esc_html__( 'Group seats cannot be empty.', 'paid-member-subscriptions' ) );

		if( !is_numeric( $group_seats ) )
			pms_errors()->add( 'group_seats', esc_html__( 'Group seats needs to be a number', 'paid-member-subscriptions' ) );

		if( $group_seats < pms_in_gm_get_used_seats( $subscription_id ) )
			pms_errors()->add( 'group_seats', esc_html__( 'Available seats needs to be equal or bigger than used seats.', 'paid-member-subscriptions' ) );

		if ( count( pms_errors()->get_error_codes() ) > 0 ){
			$errors = pms_errors()->get_error_messages();
			$this->ajax_response( 'error', $errors[0] );
		}

		pms_update_member_subscription_meta( $subscription->id, 'pms_group_name', $group_name );

		pms_update_member_subscription_meta( $subscription->id, 'pms_group_seats', $group_seats );

		if( !empty( $_POST['group_description'] ) )
			pms_update_member_subscription_meta( $subscription->id, 'pms_group_description', sanitize_text_field( $_POST['group_description'] ) );

		$this->ajax_response( 'success', esc_html__( 'Group subscription details edited successfully !', 'paid-member-subscriptions' ) );
	}

	public function members_list_remove_members(){

        if( ! current_user_can( 'manage_options' ) )
            return;

		if( !isset( $_REQUEST['pmstkn'] ) || !wp_verify_nonce( sanitize_text_field( $_REQUEST['pmstkn'] ), 'pms_remove_members_form_nonce' ) )
			return;

		if( empty( $_POST['pms_reference'] ) || empty( $_POST['pms_subscription_id'] ) )
			return;

		$reference          = sanitize_text_field( $_POST['pms_reference'] );
		$subscription_id    = sanitize_text_field( $_POST['pms_subscription_id'] );
		$owner_subscription = pms_get_member_subscription( absint( $subscription_id ) );

		$user = get_user_by( 'email', $reference );

		if( !empty( $user->ID ) ){
			$member_subscription = pms_get_member_subscriptions( array( 'user_id' => $user->ID ) );

			if( !empty( $member_subscription[0] ) ){
				$member_subscription_id = $member_subscription[0]->id;
				$member_subscription[0]->remove();

				pms_delete_member_subscription_meta( $owner_subscription->id, 'pms_group_subscription_member', (int)$member_subscription_id );

				$meta_id = pms_in_gm_get_meta_id_by_value( $owner_subscription->id, $reference );

				pms_delete_member_subscription_meta( $owner_subscription->id, 'pms_gm_invited_emails_' . $meta_id );

				pms_success()->add( 'remove_member', esc_html__( 'Member removed successfully !', 'paid-member-subscriptions' ) );


			} else {
				$meta_id = pms_in_gm_get_meta_id_by_value( $owner_subscription->id, $reference );

				pms_delete_member_subscription_meta( $owner_subscription->id, 'pms_gm_invited_emails_' . $meta_id );

				if( pms_delete_member_subscription_meta( $owner_subscription->id, 'pms_gm_invited_emails', $reference ) )
					pms_success()->add( 'remove_member', esc_html__( 'Member removed successfully !', 'paid-member-subscriptions' ) );
			}
		} else {
			$meta_id = pms_in_gm_get_meta_id_by_value( $owner_subscription->id, $reference );

			pms_delete_member_subscription_meta( $owner_subscription->id, 'pms_gm_invited_emails_' . $meta_id );

			if( pms_delete_member_subscription_meta( $owner_subscription->id, 'pms_gm_invited_emails', $reference ) )
				pms_success()->add( 'remove_member', esc_html__( 'Member removed successfully !', 'paid-member-subscriptions' ) );
		}

	}

	public function change_group_owner(){

        if( ! current_user_can( 'manage_options' ) )
            return;

		if( !isset( $_REQUEST['pmstkn'] ) || !wp_verify_nonce( sanitize_text_field( $_REQUEST['pmstkn'] ), 'pms_change_group_owner_form_nonce' ) )
			return;

		if( empty( $_REQUEST['group_owner'] ) || empty( $_REQUEST['pms_new_group_owner'] ) )
			return;

		// subscription ids
		$group_owner     = sanitize_text_field( $_REQUEST['group_owner'] );
		$new_group_owner = sanitize_text_field( $_REQUEST['pms_new_group_owner'] );
		$group_name      = pms_get_member_subscription_meta( $group_owner, 'pms_group_name', true );

		// make the old owner a group member
		pms_update_member_subscription_meta( $group_owner, 'pms_group_subscription_owner', $new_group_owner );
		pms_delete_member_subscription_meta( $group_owner, 'pms_group_name' );
		pms_add_member_subscription_meta( $new_group_owner, 'pms_group_subscription_member', $group_owner );

		$old_owner_member_subscription = pms_get_member_subscription( $group_owner );
		delete_user_meta( $old_owner_member_subscription->user_id, 'pms_group_subscription_owner' );

		// assign new ownership
		pms_delete_member_subscription_meta( $new_group_owner, 'pms_group_subscription_owner' );
		pms_delete_member_subscription_meta( $group_owner, 'pms_group_subscription_member', $new_group_owner );
		pms_update_member_subscription_meta( $new_group_owner, 'pms_group_name', $group_name );

		$new_owner_member_subscription = pms_get_member_subscription( $new_group_owner );
		add_user_meta( $new_owner_member_subscription->user_id, 'pms_group_subscription_owner', 1 );

		$group_members = pms_get_member_subscription_meta( $group_owner, 'pms_group_subscription_member' );

		if( !empty( $group_members ) ){
			foreach( $group_members as $member ){
				pms_add_member_subscription_meta( $new_group_owner, 'pms_group_subscription_member', $member );
				pms_delete_member_subscription_meta( $member, 'pms_group_subscription_owner' );
				pms_update_member_subscription_meta( $member, 'pms_group_subscription_owner', $new_group_owner );
			}

			pms_delete_member_subscription_meta( $group_owner, 'pms_group_subscription_member' );
		}

		$redirect_url = add_query_arg( 'group_owner', $new_group_owner, remove_query_arg( 'group_owner' ) );

		wp_redirect( $redirect_url );
		die();

	}

    public function ajax_determine_subscription_type() {

        if( !isset( $_POST['subscription_plan_id'] ) )
            die();

        $subscription_plan_id = (int)sanitize_text_field( $_POST['subscription_plan_id'] );

        if( ! empty( $subscription_plan_id ) ) {
            $subscription_plan = pms_get_subscription_plan($subscription_plan_id);

            if ($subscription_plan->type == 'group')
                echo 'group';
            else echo 'regular';

        }

        wp_die();

    }

    public function add_group_fields() {

        ?>

        <div class="pms-meta-box-field-wrapper pms-group-memberships-field cozmoslabs-form-field-wrapper">
            <label class="pms-meta-box-field-label cozmoslabs-form-field-label" for="pms_group_name"><?php echo esc_html__( 'Group Name *', 'paid-member-subscriptions' ); ?></label>
            <input class="pms-subscription-field" id="pms_group_name" name="group_name" type="text" value="<?php echo ( ! empty( $form_data['group_name'] ) ? esc_attr( $form_data['group_name'] ) : '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized  ?>" />

        </div>

        <div class="pms-meta-box-field-wrapper pms-group-memberships-field cozmoslabs-form-field-wrapper">
            <label class="pms-meta-box-field-label cozmoslabs-form-field-label" for="pms_group_description"><?php echo esc_html__( 'Group Description', 'paid-member-subscriptions' ); ?></label>
            <textarea class="pms-subscription-field"  id="pms_group_description" name="group_description" rows="2"><?php echo ( ! empty( $form_data['group_description'] ) ? esc_textarea(  $form_data['group_description'] ) : ''); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized  ?></textarea>
        </div>

        <?php

    }

	public function validate_group_fields( $errors, $request_data ) {

		if( !isset( $_GET['subpage'] ) || $_GET['subpage'] != 'add_subscription' )
			return $errors;

		if( empty( $errors ) )
			$errors = array();

		if( empty( $request_data['subscription_plan_id'] ) )
			return $errors;

		$subscription_plan = pms_get_subscription_plan( absint( $request_data['subscription_plan_id'] ) );

		if( !isset( $subscription_plan->type ) || $subscription_plan->type != 'group' )
			return $errors;

		if( empty( $request_data['group_name'] ) )
			$errors[] = array( 'error' => __( 'Please enter a group name.', 'paid-member-subscriptions' ) );
		else {
			$existing_group = pms_in_gm_get_meta_by_value( 'pms_group_name', $request_data['group_name'] );

			if( $existing_group !== false )
				$errors[] = array( 'error' => __( 'The group name you chose is already registered. Please enter another one.', 'paid-member-subscriptions' ) );
		}

		return $errors;

	}

	public function validate_group_subscription_plan_save( $post_id, $data ){

		if( get_post_type( $post_id ) != 'pms-subscription' )
			return;

		$error = $this->get_group_subscription_plan_error();

		if ( $error !== false ) {

			if( isset( $_GET['pms-action'] ) && ( $_GET['pms-action'] == 'move_up_subscription_plan' || $_GET['pms-action'] == 'move_down_subscription_plan' ) )
				$redirect = admin_url( 'edit.php?post_type=pms-subscription' );
			else
				$redirect = get_edit_post_link( $post_id, 'redirect' );

			wp_safe_redirect( add_query_arg( 'pms-subscription-error', $error, $redirect ) );
			exit;
		}

	}

	public function validate_group_subscription_plan_messages( $messages ){

		$messages = array(
			0 => __( 'Group subscriptions can only be added as upgrades to regular plans.', 'paid-member-subscriptions' ),
			1 => __( 'Regular plans cannot be added as upgrades to Group subscription plans.', 'paid-member-subscriptions' ),
			2 => __( 'You need to define the number of seats for this Group Subscription.', 'paid-member-subscriptions' ),
			3 => __( 'Group subscriptions cannot be downgrades to regular plans.', 'paid-member-subscriptions' )
		);

		return $messages;

	}

	public function add_member_subscription_list_manage_group_action( $output, $item ){

		if( empty( $item['subscription_id'] ) || isset( $item['custom'] ) )
			return $output;

		if( pms_in_gm_is_group_owner( $item['subscription_id'] ) )
			$output .= '<a href="'. esc_url( add_query_arg( array( 'page' => 'pms-members-page', 'subpage' => 'group_details', 'group_owner' => $item['subscription_id'] ), 'admin.php' ) ) .'" class="button button-secondary">' . esc_html__( 'Manage Group','paid-member-subscriptions' ) . '</a>';

		return $output;

	}

	public function show_group_information_on_edit_subscription_page( $user_id ){

		if( empty( $_GET['subscription_id'] ) )
			return;

		$subscription = pms_get_member_subscription( absint( $_GET['subscription_id'] ) );

		if( empty( $subscription->subscription_plan_id ) )
			return;

		$plan = pms_get_subscription_plan( $subscription->subscription_plan_id );

		if( empty( $plan->type ) || $plan->type != 'group' )
			return;

		$owner_subscription_id = $subscription->id;

		ob_start(); ?>

		<div class="pms-group-edit-subscription">

			<?php if( pms_in_gm_is_group_owner( $subscription->id ) ) : ?>
                <p class="cozmoslabs-description"><?php esc_html_e( 'This is the subscription of a Group Owner. By editing this subscription, all child subscriptions will be modified as well.','paid-member-subscriptions' ); ?> </p>
			<?php else : ?>
				<?php $owner_subscription_id = pms_get_member_subscription_meta( $subscription->id, 'pms_group_subscription_owner', true ); ?>

                <p class="cozmoslabs-description"><?php echo wp_kses_post( sprintf( __( 'This is a child subscription that is linked to a Group Owner. You cannot edit this subscription.<br>%sClick here%s to go to the owner\'s subscription in order to make changes.', 'paid-member-subscriptions' ), '<a href="'.esc_url( add_query_arg( array( 'page' => 'pms-members-page', 'subpage' => 'edit_subscription', 'subscription_id' => $owner_subscription_id ), 'admin.php' ) ).'">', '</a>' ) ); ?></p>
			<?php endif; ?>

			<?php echo '<a href="'. esc_url( add_query_arg( array( 'page' => 'pms-members-page', 'subpage' => 'group_details', 'group_owner' => $owner_subscription_id ), 'admin.php' ) ) .'" class="button button-secondary">' . esc_html__( 'Group Dashboard','paid-member-subscriptions' ) . '</a>'; ?>

		</div>

		<?php
		return ob_get_contents();

	}

	public function disable_admin_child_subscriptions_editing( $disable ){

		if( empty( $_GET['subscription_id'] ) )
			return $disable;

		$subscription = pms_get_member_subscription( absint( $_GET['subscription_id'] ) );

		if( empty( $subscription->subscription_plan_id ) )
			return $disable;

		$plan = pms_get_subscription_plan( $subscription->subscription_plan_id );

		if( empty( $plan->type ) || $plan->type != 'group' )
			return $disable;

		if( pms_in_gm_is_group_owner( $subscription->id ) )
			return $disable;

		return true;

	}

	public function replace_child_subscription_data_with_parent( $row_data, $member_subscription ){

		if( empty( $member_subscription->subscription_plan_id ) )
			return $row_data;

		$plan = pms_get_subscription_plan( absint( $member_subscription->subscription_plan_id ) );

		if( empty( $plan->type ) || $plan->type != 'group' )
			return $row_data;

		if( !pms_in_gm_is_group_owner( $member_subscription->id ) ){

			$owner_subscription_id = pms_get_member_subscription_meta( $member_subscription->id, 'pms_group_subscription_owner', true );

			$owner_subscription = pms_get_member_subscription( $owner_subscription_id );

			$row_data['expiration_date']          = pms_sanitize_date( $owner_subscription->expiration_date );
			$row_data['next_payment_date']        = pms_sanitize_date( $owner_subscription->billing_next_payment );
			$row_data['status']                   = pms_sanitize_date( $owner_subscription->status );
			$row_data['auto_renewal']             = $owner_subscription->is_auto_renewing();
			$row_data['active_trial']             = !empty( $owner_subscription->trial_end ) && strtotime( $owner_subscription->trial_end ) > time() ? true : false;
			$row_data['custom']                   = true;

		}

		return $row_data;

	}

	private function get_group_subscription_plan_error(){

		$error = false;

		if( isset( $_POST['pms_subscription_plan_type'] ) && $_POST['pms_subscription_plan_type'] == 'group' ){

			if( !isset( $_POST['pms_subscription_plan_seats'] ) || empty( $_POST['pms_subscription_plan_seats'] ) )
				$error = 2;

			// Check that Group Subscriptions are added as upgrades to other subs
			if( isset( $_POST['ID'] ) ){

				$upgrades = pms_get_subscription_plan_upgrades( (int)$_POST['ID'] );

				if( !empty( $upgrades ) ){
					foreach( $upgrades as $upgrade ){

						if( $upgrade->id != $_POST['ID'] && $upgrade->type == 'regular' ){
							$error = 0;
							break;
						}

					}
				}

				if( $error === 0 ){
					// If this is the only Group subscription from this tier, this error should not be triggered
					$tier_subs = pms_get_subscription_plans_group( (int)$_POST['ID'] );

					if( !empty( $tier_subs ) ){
						$error = false;

						foreach( $tier_subs as $sub ){

							if( $sub->id != $_POST['ID'] && $sub->type == 'group' ){
								$error = 0;
								break;
							}

						}
					}
				}

			}

		}

		// When adding regular plans as upgrades we need to check that downgrades dont have a group subscription
		if( isset( $_POST['pms_subscription_plan_type'] ) && ( ( isset( $_POST['pms-action'] ) && $_POST['pms-action'] == 'add_upgrade' ) || ( isset( $_POST['action'] ) && $_POST['action'] == 'editpost' ) ) && $_POST['pms_subscription_plan_type'] == 'regular' ){

			$parent_id = isset( $_POST['pms-subscription-plan-id'] ) ? (int)$_POST['pms-subscription-plan-id'] : (int)$_POST['ID'];

			$plans = pms_get_subscription_plans_group( $parent_id );

			if( count( $plans ) > 1 ){
				// Find key of plan we add the upgrade to
				$upgrade_key = null;

				foreach( $plans as $key => $plan ){
					if( $plan->id == $parent_id ){
						$upgrade_key = $key;
						break;
					}
				}

				if( $upgrade_key !== null ){

					$downgrades = array_slice( $plans, $upgrade_key );

					foreach( $downgrades as $downgrade ){
						if( $downgrade->id != $_POST['ID'] && $downgrade->type == 'group' ){
							$error = 1;
							break;
						}
					}

				}
			} else if( isset( $_POST['pms-action'] ) && $_POST['pms-action'] == 'add_upgrade' ){
				// we're here so there's only one plan in this group, if it's a group plan, return error
				if( $plans[0]->type == 'group' )
					$error = 1;
			}

		}

		// Check when moving a subscription plan up
		if( isset( $_GET['post_id'], $_GET['pms-action'] ) && $_GET['pms-action'] == 'move_up_subscription_plan' ){

			$current_plan = pms_get_subscription_plan( (int)$_GET['post_id'] );

			if( isset( $current_plan->id ) && $current_plan->type == 'regular' ){

				$current_post = get_post( $current_plan->id );

				if( $current_post->post_parent != 0 ){

					$parent_plan = pms_get_subscription_plan( $current_post->post_parent );

					if( isset( $parent_plan->id ) && $parent_plan->type == 'group' )
						$error = 1;

				}

			}
		}

		if( isset( $_GET['post_id'], $_GET['pms-action'] ) && $_GET['pms-action'] == 'move_down_subscription_plan' ){

			$current_plan = pms_get_subscription_plan( (int)$_GET['post_id'] );

			if( isset( $current_plan->id ) && $current_plan->type == 'group' ){

				$children_posts = get_posts( array( 'post_type' => 'pms-subscription', 'post_status' => 'any', 'numberposts' => 1, 'post_parent' => $current_plan->id ) );

				if( !empty( $children_posts ) ){

					$child_plan = pms_get_subscription_plan( $children_posts[0]->ID );

					if( isset( $child_plan->id ) && $child_plan->type == 'regular' )
						$error = 3;

				}

			}
		}

		return $error;

	}

	private function ajax_response( $type, $message ){
		echo json_encode( array( 'status' => $type, 'message' => $message ) );
		die();
	}

	private function get_all_active_group_names(){

		global $wpdb;

		$result = $wpdb->get_results( $wpdb->prepare( "SELECT subscription_meta.meta_id, subscription_meta.member_subscription_id, subscription_meta.meta_value FROM {$wpdb->prefix}pms_member_subscriptionmeta as subscription_meta INNER JOIN {$wpdb->prefix}pms_member_subscriptions as subscriptions ON subscription_meta.member_subscription_id = subscriptions.id WHERE subscription_meta.meta_key = %s AND subscription_meta.meta_value != '' AND subscriptions.status != 'abandoned'", 'pms_group_name' ), 'ARRAY_A' );

		if( !empty( $result ) )
			return $result;

		return false;

	}

}

$pms_group_memberships_admin = new PMS_IN_Admin_Group_Memberships;
