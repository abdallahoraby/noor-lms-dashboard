<?php
// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) )
    return;

/**
 * Add Pay What You Want options to Subsciption Plan Details metabox
 *
 * @param int $subscription_plan_id
 */
function pms_in_pwyw_add_subscription_plan_settings_fields( $subscription_plan_id ){

    $pay_what_you_want = get_post_meta( $subscription_plan_id, 'pms_subscription_plan_pay_what_you_want', true );
    $min_price = get_post_meta( $subscription_plan_id, 'pms_subscription_plan_min_price', true );
    $max_price = get_post_meta( $subscription_plan_id, 'pms_subscription_plan_max_price', true );
    $pay_what_you_want_text = ( metadata_exists( 'post', $subscription_plan_id, 'pms_subscription_plan_pay_what_you_want_label' ) ) ?  get_post_meta( $subscription_plan_id, 'pms_subscription_plan_pay_what_you_want_label', true ) : __( 'Pay What You Want', 'paid-member-subscriptions' );

    ?>

    <!-- Pay What You Want -->
	<div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">

	    <label for="pms-subscription-plan-pay-what-you-want" class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'Pay What You Want', 'paid-member-subscriptions' ); ?></label>

        <div class="cozmoslabs-toggle-container">
            <input type="checkbox" id="pms-subscription-plan-pay-what-you-want" name="pms_subscription_plan_pay_what_you_want" value="1" <?php if( ! empty( $pay_what_you_want ) ) checked($pay_what_you_want, '1' ); ?><?php echo esc_html( $pay_what_you_want ); ?>" />
            <label class="cozmoslabs-toggle-track" for="pms-subscription-plan-pay-what-you-want"></label>
        </div>
        <div class="cozmoslabs-toggle-description">
            <label for="pms-subscription-plan-pay-what-you-want" class="cozmoslabs-description"><?php esc_html_e( 'Enable Pay What You Want Pricing?', 'paid-member-subscriptions' ); ?></label>
        </div>
        <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Enabling this will allow users to set their own price when purchasing this subscription. This will override the subscription price set above, which will be used as the recommended price.', 'paid-member-subscriptions' ); ?></p>



        <div class="pms-meta-box-field-wrapper-pwyw">

            <div class="cozmoslabs-form-field-wrapper">
                <label for="pms-subscription-plan-min-price" class="cozmoslabs-form-field-label"> <?php esc_html_e( 'Minimum price', 'paid-member-subscriptions' ); ?></label>

                <input type="text" id="pms-subscription-plan-min-price" name="pms_subscription_plan_min_price" class="small" value="<?php echo esc_attr( $min_price ); ?>" />

                <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e( 'Enter the minimum price allowed for this subscription plan. Leaving it empty will set the minimum price equal to the subscription price.', 'paid-member-subscriptions' ); ?></p>
            </div>

            <div class="cozmoslabs-form-field-wrapper">
                <label for="pms-subscription-plan-max-price" class="cozmoslabs-form-field-label"> <?php esc_html_e( 'Maximum price', 'paid-member-subscriptions' ); ?></label>

                <input type="text" id="pms-subscription-plan-max-price" name="pms_subscription_plan_max_price" class="small" value="<?php echo esc_attr( $max_price ); ?>" />

                <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e( 'Enter the maximum price allowed for this subscription plan. Leaving it empty will imply no maximum price is set.', 'paid-member-subscriptions' ); ?></p>
            </div>

            <div class="cozmoslabs-form-field-wrapper">
                <label for="pms-subscription-plan-pay-what-you-want-label" class="cozmoslabs-form-field-label"> <?php esc_html_e( 'Label', 'paid-member-subscriptions' ); ?></label>

                <input type="text" id="pms-subscription-plan-pay-what-you-want-label" name="pms_subscription_plan_pay_what_you_want_label" value="<?php echo esc_attr( $pay_what_you_want_text ); ?>" />

                <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e( 'Text that will be displayed on the front-end, after the subscription plan name and before the price input.', 'paid-member-subscriptions' ); ?></p>
            </div>

        </div>

    </div>

<?php
}
add_action('pms_view_meta_box_subscription_details_price_bottom', 'pms_in_pwyw_add_subscription_plan_settings_fields');


/**
 * Save the Pay What You Want settings from Subscription Plan Details metabox
 *
 * @param int $subscription_plan_id
 */
function pms_in_pwyw_save_subscription_plan_settings_fields( $subscription_plan_id ){

    if( empty( $_POST['post_ID'] ) )
        return;

    if( $subscription_plan_id != $_POST['post_ID'] )
        return;

    if( isset( $_POST['pms_subscription_plan_pay_what_you_want'] ) )
        update_post_meta($subscription_plan_id, 'pms_subscription_plan_pay_what_you_want', sanitize_text_field($_POST['pms_subscription_plan_pay_what_you_want']));
    else
        update_post_meta($subscription_plan_id, 'pms_subscription_plan_pay_what_you_want', '0');

    if( isset( $_POST['pms_subscription_plan_min_price'] ) )
        update_post_meta( $subscription_plan_id, 'pms_subscription_plan_min_price', sanitize_text_field( $_POST['pms_subscription_plan_min_price'] ) );

    if( isset( $_POST['pms_subscription_plan_max_price'] ) )
        update_post_meta( $subscription_plan_id, 'pms_subscription_plan_max_price', sanitize_text_field( $_POST['pms_subscription_plan_max_price'] ) );

    if( isset( $_POST['pms_subscription_plan_pay_what_you_want_label'] ) )
        update_post_meta( $subscription_plan_id, 'pms_subscription_plan_pay_what_you_want_label', sanitize_text_field( $_POST['pms_subscription_plan_pay_what_you_want_label'] ) );

}
add_action('pms_save_meta_box_pms-subscription', 'pms_in_pwyw_save_subscription_plan_settings_fields');