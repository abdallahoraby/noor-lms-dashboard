<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

Class PMS_IN_GM_Emails {

    public function __construct(){

        add_action( 'pms_gm_send_invitation_email', array( $this, 'send_invitation_email' ), 10, 3 );

        // Add the invite email on the settings page
        add_action( 'pms-settings-page_tab_emails_after_user_tab', array( $this, 'add_invite_email_settings' ) );

        // Add extra merge tags
        add_filter( 'pms_merge_tags', array( $this, 'add_extra_tags' ) );

        // Merge Tags handler functions
        add_filter( 'pms_merge_tag_owner_email',        array( $this, 'owner_email' ), 10, 2 );
        add_filter( 'pms_merge_tag_invited_user_email', array( $this, 'invited_user_email' ), 10, 2 );
        add_filter( 'pms_merge_tag_invite_link',        array( $this, 'invite_link' ), 10, 3 );
        add_filter( 'pms_merge_tag_invite_url',         array( $this, 'invite_url' ), 10, 3 );
        add_filter( 'pms_merge_tag_group_name',         array( $this, 'group_name' ), 10, 3 );

    }

    public function send_invitation_email( $email, $subscription, $invite_key ){

        if( apply_filters( 'pms_mail_stop_emails', false ) )
            return;

        $settings = get_option( 'pms_emails_settings', array() );

        if( !isset( $settings['invite_is_enabled'] ) )
            return;

        if( !empty( $settings['invite_sub_subject'] ) )
            $subject = $settings['invite_sub_subject'];
        else
            $subject = $this->get_default_email_subject();

        if( !empty( $settings['invite_sub'] ) )
            $content = $settings['invite_sub'];
        else
            $content = $this->get_default_email_content();

        $user = get_userdata( $subscription->user_id );

        $extra_info = array(
            'invited_email'   => $email,
            'owner_email'     => $user->user_email,
            'invite_key'      => $invite_key,
            'subscription_id' => $subscription->id,
        );

        // Filter Email we send to
        $email = apply_filters( 'pms_group_invitation_email_recipient', $email, $subscription->id );

        $subject = PMS_Merge_Tags::process_merge_tags( $subject, $extra_info, $subscription->id );
        $content = PMS_Merge_Tags::process_merge_tags( $content, $extra_info, $subscription->id );

        $content = wpautop( $content );
        $content = do_shortcode( $content );

        // Filter before sending
        $subject = apply_filters( 'pms_email_subject_user', $subject, 'invite', $extra_info, $subscription->subscription_plan_id, '', '' );
        $content = apply_filters( 'pms_email_content_user', $content, 'invite', $extra_info, $subscription->subscription_plan_id, '', '' );

        // Add filter to enable html encoding
        add_filter( 'wp_mail_content_type', array( 'PMS_Emails', 'pms_email_content_type' ) );

        // Temporary change the from name and from email
        add_filter( 'wp_mail_from_name', array( 'PMS_Emails', 'pms_email_website_name' ), 20, 1 );
        add_filter( 'wp_mail_from', array( 'PMS_Emails', 'pms_email_website_email' ), 20, 1 );

            wp_mail( $email, $subject, $content );

        // Reset html encoding
        remove_filter( 'wp_mail_content_type', array( 'PMS_Emails', 'pms_email_content_type' ) );

        // Reset the from name and email
        remove_filter( 'wp_mail_from_name', array( 'PMS_Emails', 'pms_email_website_name' ), 20 );
        remove_filter( 'wp_mail_from', array( 'PMS_Emails', 'pms_email_website_email' ), 20 );

    }

    public function add_invite_email_settings( $options ){
        ?>

        <div class="cozmoslabs-form-subsection-wrapper cozmoslabs-wysiwyg-container" id="cozmoslabs-user-group-invite-email">
            <div class="cozmoslabs-email-heading-wrap">
                <h3 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Group Membership Invite Email', 'paid-member-subscriptions') ?></h3>

                <div class="cozmoslabs-toggle-switch">
                    <div class="cozmoslabs-toggle-container">
                        <input type="checkbox" id="invite-is-enabled" name="pms_emails_settings[invite_is_enabled]" value="yes" <?php echo ( isset( $options['invite_is_enabled'] ) ? 'checked' : '' ); ?> />
                        <label class="cozmoslabs-toggle-track" for="invite-is-enabled"></label>
                    </div>
                </div>
            </div>

            <div class="cozmoslabs-form-field-wrapper">
                <label class="cozmoslabs-form-field-label" for="email-invite-sub-subject"><?php esc_html_e( 'Subject', 'paid-member-subscriptions' ) ?></label>
                <input type="text" id="email-invite-sub-subject" class="widefat" name="pms_emails_settings[invite_sub_subject]" value="<?php echo ( isset($options['invite_sub_subject']) ? esc_attr( $options['invite_sub_subject'] ) : esc_attr( $this->get_default_email_subject() ) ) ?>">
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-wysiwyg-wrapper">
                <?php wp_editor( ( isset($options['invite_sub']) ? $options['invite_sub'] : $this->get_default_email_content() ), 'emails-invite-sub', array( 'textarea_name' => 'pms_emails_settings[invite_sub]', 'editor_height' => 180 ) ); ?>

                <?php $available_merge_tags = apply_filters( 'pms_user_group_invite_email_available_tags', PMS_Merge_Tags::get_merge_tags()); ?>
                <div class="cozmoslabs-available-tags">
                    <h3 class="cozmoslabs-tags-list-heading"><?php esc_html_e( 'Available Tags', 'paid-member-subscriptions' ); ?></h3>

                    <div class="cozmoslabs-tags-list">
                        <?php foreach( $available_merge_tags as $available_merge_tag ):?>
                            <input readonly spellcheck="false" type="text" class="pms-tag input" value="{{<?php echo esc_attr( $available_merge_tag ); ?>}}">
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php
    }

    public function add_extra_tags( $available_tags ){
        $extra_tags = apply_filters( 'pms_gm_merge_tags', array( 'owner_email', 'invited_user_email', 'invite_link', 'invite_url', 'group_name' ) );

        return array_merge( $available_tags, $extra_tags );
    }

    public function owner_email( $value, $extra_info ){
        if( is_array( $extra_info ) && !empty( $extra_info['owner_email'] ) )
            return $extra_info['owner_email'];

        return;
    }

    public function invited_user_email( $value, $extra_info ){
        if( is_array( $extra_info ) && !empty( $extra_info['invited_email'] ) )
            return $extra_info['invited_email'];

        return;
    }

    public function invite_link( $value, $extra_info, $plan_id ){
        $url = $this->invite_url( $value, $extra_info, $plan_id );

        if( empty( $url ) )
            return;

        return sprintf( '<a href="%s" target="_blank">%s</a>', $url, $url );
    }

    public function invite_url( $value, $extra_info, $plan_id ){
        if( !is_array( $extra_info ) || empty( $extra_info['invited_email'] ) || empty( $extra_info['invite_key'] ) || empty( $extra_info['subscription_id'] ) )
            return;

        $register_page = pms_get_page( 'register', true );

        if( !$register_page )
            return;

        return apply_filters( 'pms_group_invite_url', add_query_arg( array(
            'email'           => urlencode( $extra_info['invited_email'] ),
            'subscription_id' => $extra_info['subscription_id'],
            'pms_key'         => $extra_info['invite_key'] ),
        $register_page ), $extra_info );
    }

    public function group_name( $value, $extra_info, $subscription_id ){
        if( isset( $subscription_id ) )
            return pms_in_gm_get_group_name( $subscription_id );

        return;
    }

    private function get_default_email_subject(){

        return esc_html__( 'You have been invited to join {{site_name}}', 'paid-member-subscriptions' );

    }

    private function get_default_email_content(){

        return __( '<p>Hello,</p> <p>{{owner_email}} has invited you to join {{site_name}}.</p> <p>Click on the following link in order to register: {{invite_link}}</p>', 'paid-member-subscriptions' );

    }

}

new PMS_IN_GM_Emails;
