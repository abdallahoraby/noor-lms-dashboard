<h3>
    <?php esc_html_e( 'Edit Group Details', 'paid-member-subscriptions'); ?>
</h3>

<form class="pms-form pms-gm-edit-details" method="POST">
    <?php
        wp_nonce_field( 'pms_gm_edit_group_details_nonce', 'pmstkn' );

        $group_name        = pms_get_member_subscription_meta( $subscription->id, 'pms_group_name', true );
        $group_description = pms_get_member_subscription_meta( $subscription->id, 'pms_group_description', true );
    ?>

    <ul class="pms-form-fields-wrapper">

        <?php $field_errors = pms_errors()->get_error_messages( 'group_name' ); ?>
        <li class="pms-field pms-group-name-field pms-group-memberships-field <?php echo ( !empty( $field_errors ) ? 'pms-field-error' : '' ); ?>">
            <label for="pms_group_name"><?php echo esc_html( apply_filters( 'pms_register_form_label_group_name', __( 'Group Name *', 'paid-member-subscriptions' ) ) ); ?></label>
            <input id="pms_group_name" name="group_name" type="text" value="<?php echo !empty( $group_name ) ? esc_attr( $group_name ) : ''; ?>" />

            <?php pms_display_field_errors( $field_errors ); ?>
        </li>

        <?php $field_errors = pms_errors()->get_error_messages( 'group_description' ); ?>
        <li class="pms-field pms-group-description-field pms-group-memberships-field <?php echo ( !empty( $field_errors ) ? 'pms-field-error' : '' ); ?>">
            <label for="pms_group_description"><?php echo esc_html( apply_filters( 'pms_register_form_label_group_name', __( 'Group Description', 'paid-member-subscriptions' ) ) ); ?></label>
            <textarea id="pms_group_description" name="group_description" rows="2"><?php echo !empty( $group_description ) ? esc_attr( trim( $group_description ) ) : ''; ?></textarea>

            <?php pms_display_field_errors( $field_errors ); ?>
        </li>

        <?php do_action( 'pms_group_details_form_bottom', $subscription ); ?>

        <input type="hidden" name="pms_subscription_id" value="<?php echo esc_attr( $subscription->id ); ?>" />

        <li>
            <input type="submit" value="<?php echo esc_html( apply_filters( 'pms_gm_edit_details_form_text', __( 'Edit Details', 'paid-member-subscriptions' ) ) ); ?>" />
        </li>
    </ul>
</form>
