<?php


    /*
     * Filters the content based on the Content Dripping Sets added by the admin
     *
     */
    function pms_in_cd_filter_content( $content, $post = null ) {

        // Get globals
        global $user_ID, $pms_show_content;

        if( is_null( $post ) )
            global $post;

        // If content has already been filtered return the content
        if( current_user_can( 'manage_options' ) )
            return $content;

        // If by now the content is restricted, obey
        if( $pms_show_content === false )
            return $content;

        // For the moment show the content
        $show_content = true;


        // Get member
        $member = pms_get_member( $user_ID );

        // Get active content dripping sets
        $cd_sets = get_posts( array( 'post_type' => 'pms-content-dripping', 'numberposts' => -1, 'post_status' => 'active' ) );

        if( !empty( $cd_sets ) ) {

            foreach( $cd_sets as $cd_set ) {

                // Get the subscription plan id attached to this
                $subscription_plan_id = get_post_meta( $cd_set->ID, 'pms_content_dripping_set_subscription_plan', true );

                // Get all rules for this content set
                $rules = get_post_meta( $cd_set->ID, 'pms-content-dripping-rules', true );

                $found = false;

                // Go through each rule and check if the current post has been set for the rule
                if( !empty( $rules ) ) {
                    foreach( $rules as $rule ) {
                        if( pms_in_cd_is_post_in_rule( $post, $rule ) ) {
                            $found = $rule;
                            break;
                        }
                    }
                }

                // If the current post has been set check to see if the user is a member
                // and if he has an active subscription
                if( $found ) {

                    if( pms_in_cd_is_rule_content_available( $member, $subscription_plan_id, $found ) === false ) {
                        $show_content = false;
                    } else {
                        $show_content = true;
                        break;
                    }

                }

            }

        }

        // Show the restriction message if the content is not accessible
        if( !$show_content ) {

            $pms_show_content = false;

            if( is_user_logged_in() )
                $message_type = 'non_members';
            else
                $message_type = 'logged_out';

            $message = pms_process_restriction_content_message( $message_type, $user_ID, $post->ID );
            return do_shortcode( apply_filters( 'pms_restriction_message_' . $message_type, $message, $content, $post, $user_ID ) );

        // Else show the content
        } else
            return $content;

    }
    add_filter( 'the_content', 'pms_in_cd_filter_content', 1001 );
    add_filter( 'pms_post_restricted_check', 'pms_in_cd_filter_content', 20, 2 );


    /*
     * Checks to see whether the post is present in the content dripping rule
     *
     * @param object $post
     * @param array $rule
     *
     * @return bool
     *
     */
    function pms_in_cd_is_post_in_rule( $post, $rule ) {

        if( !is_object( $post ) )
            return false;

        // Check post type
        if( $post->post_type !== $rule['post_type'] )
            return false;

        // Check if rule is based on a list of posts
        if( $rule['type'] == 'pms_list_of_posts' ) {

            if( in_array( $post->ID, $rule['content'] ) )
                return true;
            else
                return false;

        }

        // Check if rule is based on taxonomies and check to see if the post
        // has terms from the taxonomy attached to id
        if( $rule['type'] != 'pms_list_of_posts' ) {

            // Get post terms
            $post_terms_ids = array();
            $post_terms     = wp_get_post_terms( $post->ID, $rule['type'] );

            foreach( $post_terms as $post_term )
                $post_terms_ids[] = $post_term->term_id;

            // Check to see if ids from the rule are present in the post terms
            $common_term_ids = array_intersect( $rule['content'], $post_terms_ids );

            if( !empty( $common_term_ids ) )
                return true;
            else
                return false;
        }

        return true;

    }


    /**
     * Validates whether the content within the rule is available for the member or not
     *
     * @param object $member
     * @param int $subscription_plan_id     - the subscription plan id for which the rule applies to
     * @param array $rule
     *
     * @return bool
     *
     */
    function pms_in_cd_is_rule_content_available( $member, $subscription_plan_id, $rule ) {

        // If the user is not a member do not show the content
        if( ! $member->is_member() )
            return false;

        // Check to see if the member has an active subscription matching the subscription
        // of this content dripping set
        $member_subscriptions = $member->get_subscription( $subscription_plan_id );
        if( empty( $member_subscriptions ) || ! in_array( $member_subscriptions['status'] , array( 'active', 'canceled' ) ) )
            return false;

        // Check to see if the time of the rule
        if( strtotime( $member_subscriptions['start_date'] . '+' . $rule['delay'] . ' ' . $rule['delay_unit'] ) > time() )
            return false;


        return true;

    }
