<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;


class PMS_LearnDash_Course_Access {

    public function __construct() {

        // Handle LearnDash Course List settings
        add_action( 'pms_view_meta_box_subscription_details_bottom', array( $this, 'add_learndash_settings' ) );
        add_action( 'pms_save_meta_box_pms-subscription', array( $this, 'save_learndash_settings'), 10, 2 );

        // Handle LearnDash Course access for Member Subscriptions
        add_action( 'pms_member_subscription_insert', array( $this, 'add_member_subscription_learndash_access_list_meta' ), 10, 2 );
        add_action( 'pms_member_subscription_update', array( $this, 'handle_member_subscription_update' ), 10, 3 );
        add_action( 'pms_member_subscription_before_metadata_delete', array( $this, 'remove_member_subscription_learndash_access_and_progress' ), 10, 2 );

        // Add LearnDash Tab for the Member Account Page
        add_filter( 'pms_member_account_tabs',array( $this, 'add_member_account_learndash_tab' ), 20, 2 );
        add_action( 'pms_member_account_before_learndash_tab',array( $this, 'add_member_account_learndash_tab_content' ), 20, 2 );
        add_action( 'ld_template_args_profile',array( $this, 'filter_member_learndash_courses' ), 20, 3 );

        // Redirect LearnDash Login to PMS Login
        if( apply_filters( 'pms_learndash_login_redirect', true ) )
            add_filter( 'learndash_login_url',array( $this, 'redirect_learndash_login_to_enroll' ) );

        // Add the "Take this Course" button for LearnDash Course
        add_filter( 'learndash_payment_button_closed',array( $this, 'add_learndash_payment_button' ), 10, 2 );

    }

    
    /**
     * Add the LearnDash fields to Subscription Plan Settings
     *
     */
    public function add_learndash_settings( $subscription_plan_id ) {

        if ( empty( $subscription_plan_id ) )
            return;

        $learndash_closed_courses = $this->get_learndash_course_list(  'closed' );
        $learndash_free_courses = $this->get_learndash_course_list(  'free' );
        $learndash_course_list = apply_filters( 'pms_learndash_course_list', array_merge( $learndash_closed_courses, $learndash_free_courses ), $subscription_plan_id );

        if ( empty( $learndash_course_list ) )
            return;

        $show_learndash_fields = get_post_meta( $subscription_plan_id, 'pms_subscription_plan_learndash', true );

        // LearnDash Fields toggle
        echo '<div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                  <label for="pms-subscription-learndash" class="pms-meta-box-field-label cozmoslabs-form-field-label">'. esc_html__( 'LearnDash', 'paid-member-subscriptions' ) .'</label>
                    
                  <div class="cozmoslabs-toggle-container">
                      <input type="checkbox" id="pms-subscription-learndash" name="pms_subscription_plan_learndash" value="yes" '.  ( esc_attr( $show_learndash_fields ) === 'yes' ? checked( $show_learndash_fields, 'yes', false ) : '' ) .' />
                      <label class="cozmoslabs-toggle-track" for="pms-subscription-learndash"></label>
                  </div>
                    
                  <div class="cozmoslabs-toggle-description">
                      <label for="pms-subscription-learndash" class="cozmoslabs-description">'. esc_html__( 'Enable LearnDash?', 'paid-member-subscriptions' ) .'</label>
                  </div>
            
                  <p class="cozmoslabs-description cozmoslabs-description-space-left">'. esc_html__( 'Enabling this option will allow LearnDash Courses to be associated with this Subscription Plan.', 'paid-member-subscriptions' ) .'</p>
              </div>';


        // LearnDash Fields wrapper
        echo '<div class="pms-meta-box-field-wrapper-learndash">';

        // LearnDash Course List Selector
        echo '<div class="cozmoslabs-form-field-wrapper">
                  <label for="pms-subscription-plan-learndash-courses" class="pms-meta-box-field-label cozmoslabs-form-field-label">' . esc_html__( 'LearnDash Course List', 'paid-member-subscriptions' ) . '</label>
                  <select id="pms-subscription-plan-learndash-courses" name="pms_subscription_plan_learndash_course_ids[]" class="pms-chosen" multiple="multiple">';

                        $selected_learndash_courses = apply_filters( 'pms_learndash_course_list_output', $this->get_subscription_plan_learndash_course_ids( $subscription_plan_id ), $subscription_plan_id);

                        foreach( $learndash_course_list as $course_id ) {
                            if ( is_array( $selected_learndash_courses ) && in_array( $course_id, $selected_learndash_courses ) )
                                $option_selected = ' selected="selected" ';
                            else $option_selected = '';

                            echo '<option value="' . esc_attr( $course_id ) . '" ' . esc_html( $option_selected ) . '>' . esc_html( get_the_title( $course_id ) ) . '</option>';
                        }

        echo '    </select>
                  <p class="cozmoslabs-description cozmoslabs-description-align-right">' . esc_html__( 'Select one or more LearnDash Courses to associate with this Subscription Plan.', 'paid-member-subscriptions' ) . '</p>
                  <p class="cozmoslabs-description cozmoslabs-description-space-left">'. esc_html__( 'NOTE: Only LearnDash Courses of type CLOSED or FREE will be available here.', 'paid-member-subscriptions' ) .'</p>
              </div>';

        $pms_general_settings = get_option( 'pms_general_settings', array() );

        if( !empty( $pms_general_settings['register_page'] ) ){
            $registration_url = get_permalink( $pms_general_settings['register_page'] );

            // LearnDash Course Button URL
            echo '<div class="cozmoslabs-form-field-wrapper">
                    <label class="cozmoslabs-form-field-label" for="pms-subscription-plan-learndash-url">' . esc_html__( 'LearnDash Button URL', 'paid-member-subscriptions' ) . '</label>
                    <input id="pms-subscription-plan-learndash-url" type="text" name="pms_subscription_plan_learndash_url" value="'. esc_url( $registration_url ) .'?subscription_plan='. esc_html( $subscription_plan_id ) .'&single_plan=yes" class="widefat" disabled="">
                    <a class="pms_learndash-url__copy button-secondary" data-id="pms-subscription-plan-learndash-url" href="" style="margin-left: 4px;">' . esc_html__( 'Copy', 'paid-member-subscriptions' ) . '</a>
                    <p class="cozmoslabs-description cozmoslabs-description-space-left">' . esc_html__( 'This URL can be used as the LearnDash Course Button URL, directing users to the PMS Registration page, where only the associated Subscription Plan is available.', 'paid-member-subscriptions' ) . '</p>
                 </div>';
        }

        echo '</div>'; // .pms-meta-box-field-wrapper-learndash

    }


    /**
     * Handle LearnDash Settings save
     *
     */
    public function save_learndash_settings( $subscription_plan_id, $post ) {

        if ( empty( $subscription_plan_id ) )
            return;

        if( isset( $_POST['pms_subscription_plan_learndash'] ) && $_POST['pms_subscription_plan_learndash'] === 'yes' ) {
            update_post_meta( $subscription_plan_id, 'pms_subscription_plan_learndash', sanitize_text_field( $_POST['pms_subscription_plan_learndash'] ) );

            if( isset( $_POST['pms_subscription_plan_learndash_course_ids'] ) ) {
                $learndash_course_ids = apply_filters( 'pms_learndash_course_list_selection_save', array_map( 'sanitize_text_field', $_POST['pms_subscription_plan_learndash_course_ids'] ), $subscription_plan_id);
                update_post_meta( $subscription_plan_id, 'pms_subscription_plan_learndash_course_ids', $learndash_course_ids );
            }
        }
        else {
            update_post_meta( $subscription_plan_id, 'pms_subscription_plan_learndash', 'no' );
            update_post_meta( $subscription_plan_id, 'pms_subscription_plan_learndash_course_ids', array() );
        }
    }


    /**
     * Check if LearnDash Settings are enabled for a specific Subscription Plan
     *
     */
    public function learndash_settings_enabled( $subscription_plan_id ) {
        $enabled = get_post_meta( $subscription_plan_id, 'pms_subscription_plan_learndash', true );

        if( !empty( $enabled ) && $enabled == 'yes' )
            return true;

        return false;
    }


    /**
     * Add LearnDash Course List to Member Subscription metadata
     *
     */
    public function add_member_subscription_learndash_access_list_meta( $subscription_id = 0, $subscription_data = array() ) {

        if( $subscription_id === 0 || !isset( $subscription_data['user_id'] ) || !isset( $subscription_data['subscription_plan_id'] ) )
            return;

        $user_id = $subscription_data['user_id'];
        $learndash_course_ids = apply_filters( 'pms_add_learndash_course_list_meta', $this->get_subscription_plan_learndash_course_ids( $subscription_data['subscription_plan_id'] ), $subscription_data['subscription_plan_id']);

        if ( !empty( $learndash_course_ids ) ) {
            pms_add_member_subscription_meta( $subscription_id, 'pms_learndash_course_ids', $learndash_course_ids, true );
            pms_add_member_subscription_meta( $subscription_id, 'pms_member_subscription_learndash_access', 'no', true );

            if ( isset( $subscription_data['status'] ) && $subscription_data['status'] === 'active' ) {

                foreach ( $learndash_course_ids as $course_id ) {
                    $this->grant_member_learndash_access( $user_id, $course_id );
                }

                pms_update_member_subscription_meta( $subscription_id, 'pms_member_subscription_learndash_access', 'yes');

            }
        }

    }


    /**
     * Handle LearnDash Course List for PMS Member Subscription
     *
     */
    public function handle_member_subscription_update( $subscription_id = 0, $new_data = array(), $old_data = array()  ) {

        if ( $subscription_id === 0 || !isset( $new_data['status'] ) || !isset( $old_data['status'] ) || !isset( $old_data['subscription_plan_id'] ) || !isset( $old_data['user_id'] ) )
            return;

        if ( isset( $new_data['subscription_plan_id'] ) && $new_data['subscription_plan_id'] !== $old_data['subscription_plan_id'] ) {
            $this->update_member_subscription_learndash_course_list( $old_data['user_id'], $subscription_id, $new_data['subscription_plan_id'], $old_data['subscription_plan_id'] );
        }
        elseif ( $new_data['status'] !== $old_data['status'] ) {
            $this->update_member_subscription_learndash_access( $subscription_id, $new_data['status'], $old_data['user_id'] );
        }

    }


    /**
     * Update access to LearnDash Course List for PMS-Member Subscription
     *
     */
    public function update_member_subscription_learndash_access( $subscription_id, $new_status, $user_id  ) {

        if ( empty( $subscription_id ) || empty( $new_status ) || empty( $user_id ) )
            return;

        $learndash_course_ids = apply_filters( 'pms_update_learndash_access', $this->get_member_subscription_learndash_course_ids( $subscription_id ), $subscription_id );

        if ( empty( $learndash_course_ids ) )
            return;

        if ( $new_status === 'active' ) {
            pms_update_member_subscription_meta( $subscription_id, 'pms_member_subscription_learndash_access', 'yes');

            foreach ( $learndash_course_ids as $course_id ) {
                $this->grant_member_learndash_access( $user_id, $course_id );
            }
        }
        else if ( $new_status !== 'canceled' ) {
            pms_update_member_subscription_meta( $subscription_id, 'pms_member_subscription_learndash_access', 'no');

            foreach ( $learndash_course_ids as $course_id ) {
                $this->remove_member_learndash_access( $user_id, $course_id );

                if ( $new_status === 'abandoned' )
                    $this->delete_member_learndash_progress( $user_id, $course_id );
            }
        }

    }


    /**
     * Update LearnDash Course List for PMS-Member Subscription
     * -> the list of LearnDash courses needs to be updated when a Subscription Plan is getting upgraded, downgraded or changed
     *
     */
    public function update_member_subscription_learndash_course_list( $user_id, $member_subscription_id, $new_subscription_plan_id, $old_subscription_plan_id  ) {

        $new_learndash_course_ids = $this->get_subscription_plan_learndash_course_ids( $new_subscription_plan_id );
        $old_learndash_course_ids = $this->get_subscription_plan_learndash_course_ids( $old_subscription_plan_id );

        // update PMS-Member Subscription meta that contains LearnDash Courses meta before removing/granting access
        pms_update_member_subscription_meta( $member_subscription_id, 'pms_learndash_course_ids', $new_learndash_course_ids );

        if ( !empty( $new_learndash_course_ids ) )
            pms_update_member_subscription_meta( $member_subscription_id, 'pms_member_subscription_learndash_access', 'yes');
        else
            pms_update_member_subscription_meta( $member_subscription_id, 'pms_member_subscription_learndash_access', 'no');

        // remove access to LearnDash Courses that are not attached to the new Subscription Plan
        foreach ( $old_learndash_course_ids as $course_id ) {

            if ( !in_array( $course_id, $new_learndash_course_ids ) )
                $this->remove_member_learndash_access( $user_id, $course_id );

        }

        // grant access to LearnDash Courses that are not attached to the old Subscription Plan
        foreach ( $new_learndash_course_ids as $course_id ) {

            if ( !in_array( $course_id, $old_learndash_course_ids ) )
                $this->grant_member_learndash_access( $user_id, $course_id );

        }
    }


    /**
     * Remove LearnDash access and progress when a Member Subscription is deleted
     *
     */
    public function remove_member_subscription_learndash_access_and_progress( $subscription_id = 0, $subscription_data = array() ) {

        if ( $subscription_id === 0 || !isset( $subscription_data['user_id'] ) )
            return;

        $user_id = $subscription_data['user_id'];
        $learndash_course_ids = apply_filters( 'pms_remove_learndash_access', $this->get_member_subscription_learndash_course_ids( $subscription_id ), $subscription_id );

        if ( !empty( $learndash_course_ids ) ) {
            foreach ( $learndash_course_ids as $course_id ) {
                $this->remove_member_learndash_access( $user_id, $course_id );
                $this->delete_member_learndash_progress( $user_id, $course_id );
            }
        }

    }


    /**
     * Return the LearnDash Course List of the selected type
     *
     */
    public function get_learndash_course_list( $learndash_course_type ) {
        $learndash_course_list = '';

        if ( function_exists('learndash_get_posts_by_price_type') )
            $learndash_course_list = learndash_get_posts_by_price_type( 'sfwd-courses', $learndash_course_type );

        return $learndash_course_list;
    }


    /**
     * Return the LearnDash Course IDs selected in the Subscription Plan Settings
     *
     */
    public function get_subscription_plan_learndash_course_ids( $subscription_plan_id ) {
        return get_post_meta( $subscription_plan_id, 'pms_subscription_plan_learndash_course_ids', true );
    }


    /**
     * Return the LearnDash Course IDs attached to the PMS-Member Subscription
     *
     */
    public function get_member_subscription_learndash_course_ids( $subscription_id ) {
        $learndash_course_ids = '';

        if ( function_exists('pms_get_member_subscription_meta') )
            $learndash_course_ids = pms_get_member_subscription_meta( $subscription_id, 'pms_learndash_course_ids', true );

        return $learndash_course_ids;
    }


    /**
     * Return all LearnDash Course IDs that the PMS-Member has access to
     *
     */
    public function get_member_learndash_course_ids( $user_id, $unique ) {
        $member_subscriptions = pms_get_member_subscriptions( array( 'user_id' => $user_id ) );

        $member_learndash_courses = array();
        foreach ( $member_subscriptions as $subscription ) {
            $subscription_learndash_courses = $this->get_member_subscription_learndash_course_ids( $subscription->id );

            $member_access = pms_get_member_subscription_meta( $subscription->id, 'pms_member_subscription_learndash_access', true );

            if ( $member_access === 'yes' && !empty( $subscription_learndash_courses ) && is_array( $subscription_learndash_courses ) )
                $member_learndash_courses = array_merge( $member_learndash_courses, $subscription_learndash_courses );
        }

        if ( $unique && !empty( $member_learndash_courses ) )
            $member_learndash_courses = array_unique( $member_learndash_courses );

        return $member_learndash_courses;
    }


    /**
     * Grant PMS-Member access to LearnDash Course
     *
     */
    public function grant_member_learndash_access( $user_id, $course_id ) {
        if ( function_exists( 'ld_update_course_access' )  )
            ld_update_course_access( $user_id, $course_id, false );
    }


    /**
     * Remove PMS-Member access to LearnDash Course
     *
     */
    public function remove_member_learndash_access( $user_id, $course_id ) {
        if ( !function_exists( 'ld_update_course_access' ) )
            return;

        $member_learndash_courses = $this->get_member_learndash_course_ids( $user_id, true );

        if ( !in_array( $course_id, $member_learndash_courses ) )
            ld_update_course_access( $user_id, $course_id, true );
    }


    /**
     * Delete PMS-Member progress for LearnDash Course
     *
     */
    public function delete_member_learndash_progress( $user_id, $course_id ) {

        if ( !function_exists( 'learndash_delete_course_progress' ) )
            return;

        $member_learndash_courses = $this->get_member_learndash_course_ids( $user_id, true );

        if ( !in_array( $course_id, $member_learndash_courses ) )
            learndash_delete_course_progress( $course_id, $user_id );

    }


    /**
     * Add the Member Account "LearnDash" Tab
     *
     */
    public function add_member_account_learndash_tab( $tabs, $args ) {
        $tabs['learndash'] = __( 'My Courses', 'paid-member-subscriptions' );

        return $tabs;
    }


    /**
     * Output the LearnDash Tab content
     *
     */
    public function add_member_account_learndash_tab_content( $active_tab, $member ) {
        if ( $active_tab !== 'learndash' )
            return;

        echo do_shortcode('[ld_profile show_header="no" show_search="no"]');
    }


    /**
     * Filter the LearnDash Course List displayed on PMS Account -> My Courses section
     *
     */
    public function filter_member_learndash_courses( $args, $filepath, $echo ) {

        /**
         * Controls what LearnDash Courses will be displayed on My Courses section from PMS Account Page
         *
         *  - TRUE  -> only the Courses that the user gained access through PMS Subscription will be displayed
         *  - FALSE -> all Courses that the user has access to will be displayed
         *
         * @param bool $pms_member_courses_only -> Defaults to TRUE.
         *
         */
        $pms_member_courses_only = apply_filters( 'pms_member_learndash_courses_only', true );

        if ( !$pms_member_courses_only )
            return $args;

        $pms_account_page_id = pms_get_page( 'account' );
        $current_page_id = get_the_ID();

        if ( $pms_account_page_id === $current_page_id ) {
            $member_learndash_courses = $this->get_member_learndash_course_ids( $args['user_id'], true );
            $args['user_courses'] = $member_learndash_courses;
        }

        return $args;
    }


    /**
     * Add the "Take this Course" button for redirecting the user to PMS Registration
     *
     *  -> the button will only be added if not present already
     *  -> the first Subscription Plan that is found associated with the Course will be displayed on the PMS Register from
     *  -> this works only for CLOSED type Courses
     *  -> teh button is displayed on LearnDash Course front-end page
     *
     */
    public function add_learndash_payment_button( $button, $data ) {

        // return if there is a custom_url set for the button or the Course is not of type CLOSED
        if ( !empty( $data['custom_button_url'] ) || $data['type'] !== 'closed' )
            return $button;

        $course_id = get_the_ID();
        $pms_general_settings = get_option( 'pms_general_settings', array() );
        $registration_url = isset( $pms_general_settings['register_page'] ) ? get_permalink( $pms_general_settings['register_page'] ) : false;
        $subscription_plans = pms_get_subscription_plans();

        /*
         * return if we do not have the necessary data:
         *  - an ID for the Course
         *  - PMS Register page selected in PMS "Membership Pages"
         *  - one or more active Subscription Plans
         * */
        if ( !$course_id || !$registration_url || empty( $subscription_plans ) )
            return $button;

        $subscription_plan_id = '';

        foreach ( $subscription_plans as $pms_plan ) {
            $learndash_settings_enabled = $this->learndash_settings_enabled( $pms_plan->id );

            // skip the rest of the code inside the loop if the LearnDash Settings are not enabled for the Subscription Plan
            if ( !$learndash_settings_enabled )
                continue;

            $subscription_plan_courses = $this->get_subscription_plan_learndash_course_ids( $pms_plan->id );

            if ( is_array( $subscription_plan_courses ) && !empty( $subscription_plan_courses ) && in_array( $course_id, $subscription_plan_courses ) ) {
                $subscription_plan_id = $pms_plan->id;
                break; // exit the loop when a Subscription Plan associated with the Course is found
            }
        }

        // add the button only if a Subscription Plan associated with the Course was found
        if ( !empty( $subscription_plan_id ) )
            $button = '<a class="btn-join learndash-button-closed" id="btn-join" href="'. esc_url( $registration_url ) .'?subscription_plan='. esc_html( $subscription_plan_id ) .'&single_plan=yes">'. __( 'Take this Course', 'paid-member-subscriptions' ) .'</a>';

        return $button;
    }


    /**
     * Redirect users to PMS Login page
     *
     * --> the redirect works only if the PMS Login page is set in Membership Pages settings
     *
     * - redirects the LearnDash "Login to Enroll" button (FREE type course)
     * - redirects the LearnDash "Login" link (RECURRING type course)
     *
     */
    public function redirect_learndash_login_to_enroll( $url ) {

        $pms_settings = get_option( 'pms_general_settings' );

        if ( isset( $pms_settings['login_page'] ) && $pms_settings['login_page'] != -1 )
            $url = get_permalink( $pms_settings['login_page'] );

        return $url;
    }

}
new PMS_LearnDash_Course_Access();