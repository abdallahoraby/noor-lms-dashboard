<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;


/**
 * Add the extra fields needed in the Subscription Plan Details meta-box
 *
 * @param int $subscription_plan_id
 *
 */
function pms_in_msfp_add_subscription_plan_settings_fields( $subscription_plan_id ){

    $subscription_plan = pms_get_subscription_plan( $subscription_plan_id );

    ?>

    <!-- Fixed Membership -->
    <div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">

        <label for="pms-subscription-plan-fixed-membership" class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'Fixed Membership', 'paid-member-subscriptions' ); ?></label>

        <div class="cozmoslabs-toggle-container">
            <input type="checkbox" id="pms-subscription-plan-fixed-membership" name="pms_subscription_plan_fixed_membership" <?php checked( $subscription_plan->fixed_membership, 'on' ) ?>/>
            <label class="cozmoslabs-toggle-track" for="pms-subscription-plan-fixed-membership"></label>
        </div>

        <div class="cozmoslabs-toggle-description">
            <label for="pms-subscription-plan-fixed-membership" class="cozmoslabs-description"><?php esc_html_e( 'Check this box to enable fixed period memberships.', 'paid-member-subscriptions' ); ?></label>
        </div>

    </div>

    <!-- Expiration Date -->
    <div class="pms-meta-box-field-wrapper pms-subscription-plan-fixed-membership-field cozmoslabs-form-field-wrapper">

        <label for="pms-subscription-plan-expiration-date" class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'Expiration Date', 'paid-member-subscriptions' ); ?></label>

        <input id="pms-subscription-plan-expiration-date" type="text" class="datepicker" name="pms_subscription_plan_expiration_date" value="<?php echo esc_attr( $subscription_plan->fixed_expiration_date ) ?>"/>

        <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e( 'Set the Expiration Date. A subsequent date change will only affect new users.', 'paid-member-subscriptions' ); ?></p>

    </div>

    <!-- Allow plan to be renewed -->
    <div class="pms-meta-box-field-wrapper pms-subscription-plan-fixed-membership-field cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">

        <label for="pms-subscription-plan-allow-renew" class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'Allow plan to be renewed', 'paid-member-subscriptions' ); ?></label>

        <div class="cozmoslabs-toggle-container">
            <input type="checkbox" id="pms-subscription-plan-allow-renew" name="pms_subscription_plan_allow_renew" <?php checked( $subscription_plan->allow_renew, 'on' ) ?>/>
            <label class="cozmoslabs-toggle-track" for="pms-subscription-plan-allow-renew"></label>
        </div>

        <div class="cozmoslabs-toggle-description">
            <label for="pms-subscription-plan-allow-renew" class="cozmoslabs-description"><?php esc_html_e( 'Allow fixed period plan to be renewed each year.', 'paid-member-subscriptions' ); ?></label>
        </div>

    </div>

    <?php

}
add_action( 'pms_view_meta_box_subscription_details_description_bottom', 'pms_in_msfp_add_subscription_plan_settings_fields' );


/**
 * Save the extra fields from the Subscription Plan Details meta-box on post save
 *
 * @param int $subscription_plan_id
 *
 */
function pms_in_msfp_save_subscription_plan_settings_fields( $subscription_plan_id ) {

    if( empty( $_POST['post_ID'] ) )
        return;

    if( $subscription_plan_id != $_POST['post_ID'] )
        return;

    // Update subscription plan fixed membership meta
    if( isset( $_POST['pms_subscription_plan_fixed_membership'] ) )
        update_post_meta( $subscription_plan_id, 'pms_subscription_plan_fixed_membership', 'on' );
    else
        update_post_meta( $subscription_plan_id, 'pms_subscription_plan_fixed_membership', '' );

    // Update subscription plan expiration date
    if( isset( $_POST['pms_subscription_plan_expiration_date'] ) ) {

        $subscription_plan_expiration_date = sanitize_text_field( $_POST['pms_subscription_plan_expiration_date'] );

        if( isset( $_POST['pms_subscription_plan_fixed_membership'] ) && $_POST['pms_subscription_plan_fixed_membership'] == 'on' && empty( $subscription_plan_expiration_date ) ){

            add_settings_error( 'pms-plans-metabox', 'pms-plans-metabox-fxp-empty-date', 'Fixed expiration date cannot be empty.', 'error' );

            update_post_meta( $subscription_plan_id, 'pms_subscription_plan_fixed_membership', '' );

        } else if( strtotime( $subscription_plan_expiration_date ) > strtotime( 'now' ) ){

            $date = date( 'm/d/Y', strtotime( $subscription_plan_expiration_date ) );

            update_post_meta( $subscription_plan_id, 'pms_subscription_plan_expiration_date', $date );

        }
    }

    // Update subscription plan allow renew meta
    if( isset( $_POST['pms_subscription_plan_allow_renew'] ) )
        update_post_meta( $subscription_plan_id, 'pms_subscription_plan_allow_renew', 'on' );
    else
        update_post_meta( $subscription_plan_id, 'pms_subscription_plan_allow_renew', '' );

}
add_action( 'pms_save_meta_box_pms-subscription', 'pms_in_msfp_save_subscription_plan_settings_fields', 9 );