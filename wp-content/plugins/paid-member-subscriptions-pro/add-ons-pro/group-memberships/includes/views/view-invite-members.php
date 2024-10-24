<h3>
    <?php esc_html_e( 'Invite Members', 'paid-member-subscriptions' ); ?>
</h3>

<?php if( count( pms_success()->get_messages( 'invite_members' ) ) > 0 ) : $messages = pms_success()->get_messages( 'invite_members' ); ?>
    <div class="pms_success-messages-wrapper">
        <p>
            <span class="pms-notice-title"><?php esc_html_e('SUCCESS! ', 'paid-member-subscriptions')?></span>
            <?php echo esc_html( $messages[0] ); ?>
        </p>
    </div>
<?php endif; ?>

<?php pms_display_field_errors( pms_errors()->get_error_messages( 'invite_members' ) ); ?>

<?php if( $subscription->status == 'pending' ) : ?>

    <?php esc_html_e( 'Your subscription is pending. After the payment is confirmed, you will be able to invite members.', 'paid-member-subscriptions' ); ?>

<?php elseif( !pms_is_member_of_plan( $subscription->subscription_plan_id ) ) : ?>

    <?php esc_html_e( 'Your subscription is expired. In order to invite more members, please renew.', 'paid-member-subscriptions' ); ?>

<?php elseif( !$this->members_can_be_invited( $subscription ) ) : ?>

    <?php esc_html_e( 'You have reached the maximum amount of users that you can invite.', 'paid-member-subscriptions' ); ?>

<?php else : $available_seats = pms_in_gm_get_total_seats( $subscription ) - pms_in_gm_get_used_seats( $subscription->id ); ?>
    <p>
        <?php printf( esc_html__( 'You can invite up to %s more members.', 'paid-member-subscriptions' ), '<strong>'. esc_html( $available_seats ) .'</strong>' ); ?>
    </p>

    <form id="pms-invite-members" class="pms-form" method="POST">
        <?php wp_nonce_field( 'pms_invite_members_form_nonce', 'pmstkn' ); ?>

        <label for="pms_emails_to_invite">
            <?php esc_html_e( 'Email(s) to invite:', 'paid-member-subscriptions' ); ?>
        </label>

        <textarea id="pms_emails_to_invite" name="pms_emails_to_invite" rows="6"></textarea>

        <p class="description">
            <?php esc_html_e( 'Enter a comma separated list of emails or a different email on each line.', 'paid-member-subscriptions' ); ?>
        <p>

        <input type="hidden" name="pms_subscription_id" value="<?php echo esc_attr( $subscription->id ); ?>" />

        <input type="submit" value="<?php esc_html_e( 'Invite Members', 'paid-member-subscriptions' ); ?>" />
    </form>
<?php endif; ?>
