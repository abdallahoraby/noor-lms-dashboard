<?php

if( !class_exists( 'PMS_Shortcodes' ) )
    return false;

Class PMS_IN_CD_Shortcodes extends PMS_Shortcodes {

    /*
     * Initialise the shortcodes
     */
    public static function init() {

        add_shortcode( 'pms-cd-contents-table', __CLASS__ . '::content_dripping_set_contents_table' );

    }


    /*
     * The callback for the content dripping set table of contents
     *
     */
    public static function content_dripping_set_contents_table( $atts ) {

        $atts = wp_parse_args( $atts,
            array(
                'id' => 0
            )
        );

        // Exit if no id was provided
        if( $atts['id'] === 0 )
            return '';

        // Get content set
        $content_set_post_object = get_post( $atts['id'] );

        // Exit if the post is not a
        if( $content_set_post_object->post_type !== 'pms-content-dripping' || $content_set_post_object->post_status !== 'active' )
            return '';

        $rules                  = get_post_meta( $atts['id'], 'pms-content-dripping-rules', true );
        $subscription_plan_id   = get_post_meta( $atts['id'], 'pms_content_dripping_set_subscription_plan', true );

        if( empty( $rules ) || empty( $subscription_plan_id ) )
            return '';

        // Get member
        $member = pms_get_member( get_current_user_id() );

        // Start output
        $output = '<ul>';

        $items_output = '';

        foreach( $rules as $rule ) {

            if( empty( $rule['content'] ) )
                continue;

            // Check to see if the content in this rule is available for member
            $is_content_available = ( current_user_can( 'manage_options' ) ? true : pms_in_cd_is_rule_content_available( $member, $subscription_plan_id, $rule ) );

            foreach( $rule['content'] as $object_id ) {

                $title = '';
                $url   = '';

                // Get the title and url for the contents
                if( $rule['type'] === 'pms_list_of_posts' ) {
                    $title  = get_the_title( $object_id );
                    $url    = get_post_permalink( $object_id );
                } else {
                    $term   = get_term( $object_id, $rule['type'] );
                    $title  = $term->name;
                    $url    = get_term_link( $term, $rule['type'] );
                }

                $item_output = '<li class="' . ( $is_content_available ? 'pms-content-available' : 'pms-content-unavailable' ) . '">';

                    if( $is_content_available )
                        $item_output .= apply_filters( 'pms_cd_contents_table_item_available', '<a href="' . $url . '">' . $title . '</a>', $title, $url );
                    else
                        $item_output .= apply_filters( 'pms_cd_contents_table_item_unavailable', '<span>' . $title . '</span>', $title, $url );

                $item_output .= '</li>';

                // Option to apply filters on each item
                $items_output .= apply_filters( 'pms_cd_contents_table_item', $item_output, $rule, $object_id, $title, $url );

            }

        }

        $output .= apply_filters( 'pms_cd_contents_table_items', $items_output ) . '</ul>';

        // Return the output
        return apply_filters( 'pms_cd_contents_table_output', $output );

    }

}
add_action( 'init', array( 'PMS_IN_CD_Shortcodes', 'init' ) );