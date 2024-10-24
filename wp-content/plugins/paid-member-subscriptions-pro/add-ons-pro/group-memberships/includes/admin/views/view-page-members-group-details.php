<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

$group_owner_id = ( ! empty( $_GET['group_owner'] ) ? (int)$_GET['group_owner'] : 0 );

$group_name = pms_get_member_subscription_meta( $group_owner_id, 'pms_group_name', true );
?>


<div class="wrap pms-group-wrap cozmoslabs-wrap" id="group-details-wrap">

    <h1></h1>
    <!-- WordPress Notices are added after the h1 tag -->

    <div class="cozmoslabs-page-header">
        <div class="cozmoslabs-section-title">
            <h3 class="cozmoslabs-page-title"><?php esc_html_e( 'Group Details', 'paid-member-subscriptions' ); ?></h3>
        </div>
    </div>

    <div class="pms-admin-notice <?php echo count( pms_success()->get_messages() ) > 0 ? 'updated' : ''; ?>"
        style="<?php echo count( pms_success()->get_messages() ) > 0 ? 'display:block' : ''; ?>">
        <?php if( count( pms_success()->get_messages() ) > 0 ) : $messages = pms_success()->get_messages(); ?>
            <p>
                <?php echo esc_html( $messages[0] ); ?>
            </p>
        <?php else : ?>
            <p></p>
        <?php endif; ?>
    </div>

    <div class="pms-group-holder">
        <div class="pms-group-info cozmoslabs-form-subsection-wrapper" id="cozmoslabs-subsection-group-info">
            <h3 class="cozmoslabs-subsection-title"> <?php echo esc_html( $group_name ); ?> </h3>

            <?php if( $group_description = pms_get_member_subscription_meta( $group_owner_id, 'pms_group_description', true ) ) : ?>
                <div class="pms-group-description">
                    <p class="cozmoslabs-description"><?php echo esc_html( $group_description ); ?></p>

                    <div class="cozmoslabs-form-field-wrapper">
                        <textarea name="pms_group_description" rows="2"><?php echo esc_textarea( $group_description ); ?></textarea>
                    </div>
                </div>
            <?php endif; ?>

            <div class="cozmoslabs-form-field-wrapper">
                <?php
                    $group_info_table = new PMS_IN_Group_Info_List_Table();
                    $group_info_table->prepare_items();
                    $group_info_table->display();
                ?>
            </div>

            <input type="hidden" id="pms-owner-id" value="<?php echo !empty( $_GET['group_owner'] ) ? esc_attr( absint( $_GET['group_owner'] ) ) : ''; ?>">
        </div>

        <div class="pms-group-members-list cozmoslabs-form-subsection-wrapper" id="cozmoslabs-subsection-member-list">
            <h3 class="cozmoslabs-subsection-title"> <?php esc_html_e( 'Members List', 'paid-member-subscriptions' ); ?> </h3>

            <div class="cozmoslabs-form-field-wrapper">
            <form method="POST">
                <?php
                    $members_list_table = new PMS_IN_Group_Members_List_Table();
                    $members_list_table->prepare_items();
                    $members_list_table->display();
                ?>
            </form>
            </div>
        </div>

        <?php if( current_user_can( 'manage_options' ) ) : ?>
            <div class="pms-group-invite-members cozmoslabs-form-subsection-wrapper" id="cozmoslabs-form-subsection-invite-members">
                <h3 class="cozmoslabs-subsection-title"> <?php esc_html_e( 'Add New Members', 'paid-member-subscriptions' ); ?> </h3>

                <div class="cozmoslabs-form-field-wrapper">
                    <?php
                        $group_info_table = new PMS_IN_Invite_Members_Table();
                        $group_info_table->prepare_items();
                        $group_info_table->display();
                    ?>
                </div>
            </div>

                <?php 
                $users = pms_in_gm_get_group_subscriptions( $group_owner_id );
                
                if( !empty( $users ) ) : ?>
                    <div class="pms-group-change-owner cozmoslabs-form-subsection-wrapper">
                        <h3 class="cozmoslabs-subsection-title"> <?php esc_html_e( 'Change Group Owner', 'paid-member-subscriptions' ); ?> </h3>

                        <form method="POST">
                            <?php wp_nonce_field( 'pms_change_group_owner_form_nonce', 'pmstkn' ); ?>

                            <div class="cozmoslabs-form-field-wrapper">
                                <label class="cozmoslabs-form-field-label" for="non-group-member-users"><?php esc_html_e( 'New Owner', 'paid-member-subscriptions' ); ?></label>
                                <select name="pms_new_group_owner" id="non-group-member-users" class="widefat pms-chosen" data-placeholder="<?php esc_html_e( 'Select new owner', 'paid-member-subscriptions' ); ?>" >
                                    <option value=""></option>

                                    <?php
                                        foreach ( $users as $user ) {

                                            if( $user == $group_owner_id )
                                                continue;

                                            $member_user_id = pms_in_gm_get_member_subscription_user_id( $user );

                                            echo '<option value="'. esc_attr( $user ) .'">';

                                                $name = pms_in_gm_get_user_name( $member_user_id, true );

                                                if( !empty( $name ) )
                                                    echo esc_html( $name ) . ' (';

                                            echo esc_html( pms_in_gm_get_email_by_user_id( $member_user_id ) );

                                                if( !empty( $name ) )
                                                    echo ')';

                                            echo '</option>';
                                        }
                                    ?>

                                </select>

                                <input type="submit" class="button button-secondary" value="<?php esc_html_e( 'Change owner', 'paid-member-subscriptions' ); ?>" />
                            </div>
                        </form>

                    </div>

                <?php endif; ?>

        <?php endif; ?>

    </div>
</div>
