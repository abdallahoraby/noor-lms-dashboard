<div id="invite-new-members">

    <?php

    $members_can_be_invited = (pms_in_gm_get_used_seats( $this->member_subscription->id ) >= pms_in_gm_get_total_seats( $this->member_subscription )) ? false : true;

    if( !$members_can_be_invited ) :
        esc_html_e( 'You have reached the maximum amount of users that you can invite.', 'paid-member-subscriptions' );
    else :

        $available_seats = pms_in_gm_get_total_seats($this->member_subscription) - pms_in_gm_get_used_seats($this->member_subscription->id);
    ?>
        <p class="invite-info cozmoslabs-description">
            <?php printf( esc_html__( 'You can invite up to %s more members.', 'paid-member-subscriptions' ), '<strong>'. esc_html( $available_seats ) .'</strong>' ); ?>
        </p>

        <form id="pms-admin-invite-members" class="pms-form" method="POST">

            <?php wp_nonce_field( 'pms_invite_members_form_nonce', 'pmstkn' ); ?>

            <div class="invite-emails-box cozmoslabs-form-field-wrapper">
                <label for="pms_emails_to_invite" class="cozmoslabs-form-field-label">
                    <?php esc_html_e( 'Email(s) to invite:', 'paid-member-subscriptions' ); ?>
                </label>
                <textarea id="pms_emails_to_invite" name="pms_emails_to_invite"></textarea>

                <p class="cozmoslabs-description">
                    <?php esc_html_e( 'Enter a comma separated list or a different email on each line.', 'paid-member-subscriptions' ); ?>
                </p>
            </div>

            <input type="hidden" name="pms_subscription_id" value="<?php echo esc_attr( $this->member_subscription->id ); ?>" />
            <input type="submit" class="button button-secondary" value="<?php esc_html_e( 'Invite Members', 'paid-member-subscriptions' ); ?>" />

        </form>

    <?php endif; ?>

</div>
