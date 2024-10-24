<div id="add-existing-users" >

    <?php

    $members_can_be_invited = (pms_in_gm_get_used_seats( $this->member_subscription->id ) >= pms_in_gm_get_total_seats( $this->member_subscription )) ? false : true;

    if( !$members_can_be_invited ) :
        esc_html_e( 'You have reached the maximum amount of users that you can invite.', 'paid-member-subscriptions' );
    else :

        $available_seats = pms_in_gm_get_total_seats($this->member_subscription) - pms_in_gm_get_used_seats($this->member_subscription->id);
    ?>

        <p class="invite-info cozmoslabs-description">
            <?php printf( esc_html__( 'You can add up to %s more members.', 'paid-member-subscriptions' ), '<strong>'. esc_html( $available_seats ) .'</strong>' ); ?>
        </p>

        <form id="pms-admin-add_users" class="pms-form" method="POST">

            <?php wp_nonce_field( 'pms_invite_members_form_nonce', 'pmstkn' ); ?>

            <div class="add-users-box cozmoslabs-form-field-wrapper">
                <label for="pms_emails_to_invite" class="cozmoslabs-form-field-label"><?php esc_html_e( 'User(s) to add as members of your Group Subscription:', 'paid-member-subscriptions' ); ?></label>
                <select name="pms_emails_to_invite[]" id="non-group-member-users" class="widefat pms-chosen" multiple data-placeholder="<?php esc_html_e( 'Select users', 'paid-member-subscriptions' ); ?>" >

                    <?php

                    $multiple_subscription_addon_active = apply_filters( 'pms_add_on_is_active', false, 'pms-add-on-multiple-subscriptions-per-user/index.php' );

                    if ( $multiple_subscription_addon_active )
                        $non_member_users = pms_in_gm_get_non_group_member_users( $this->group_owner_id );
                    else 
                        $non_member_users = pms_get_users_non_members();

                    if( !empty( $non_member_users ) ) {
                        foreach ( $non_member_users as $user ) {
                            $user_id = is_object( $user ) ? $user->ID : $user['id'];
                            $user_subscriptions = pms_get_member_subscriptions( array( 'user_id' => $user_id ) );
                            $already_subscribed = false;

                            foreach ( $user_subscriptions as $user_sub ) {
                                if ( $user_sub->subscription_plan_id == $this->member_subscription->subscription_plan_id )
                                    $already_subscribed = true;
                            }

                            if ( !$already_subscribed ) {
                                echo '<option value="' . esc_attr( pms_in_gm_get_email_by_user_id( $user_id )) . '">';

                                $name = pms_in_gm_get_user_name( $user_id, true );

                                if ( !empty( $name ) )
                                    echo esc_html( $name ) . ' (';

                                echo esc_html( pms_in_gm_get_email_by_user_id( $user_id ) );

                                if ( !empty( $name ) )
                                    echo ')';

                                echo '</option>';
                            }
                        }
                    }
                    ?>

                </select>

                <p class="cozmoslabs-description">
                    <?php esc_html_e( 'Click in the box above to select the users you want to add.', 'paid-member-subscriptions' ); ?>
                </p>

            </div>


            <input type="hidden" name="pms_subscription_id" value="<?php echo esc_attr( $this->member_subscription->id ); ?>" />
            <input type="submit" class="button button-secondary" value="<?php esc_html_e( 'Add Members', 'paid-member-subscriptions' ); ?>" />

        </form>

    <?php endif; ?>

</div>
