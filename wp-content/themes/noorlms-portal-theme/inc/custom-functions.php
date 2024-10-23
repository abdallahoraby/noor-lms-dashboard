<?php



    function get_user_enrolled_courses( $user_id ) {

        global $wpdb;
        $item_ids = [];

        $user_id = get_current_user_id();

        if ( ! $user_id ) {
            return false;
        }

        $filter = ! empty( $request['course_filter'] ) ? $request['course_filter'] : false;
        $where  = $wpdb->prepare( 'user_id=%d AND item_type=%s', $user_id, 'lp_course' ); // phpcs:ignore

        if ( $filter ) {
            if ( $filter === 'in-progress' ) {
                $where .= $wpdb->prepare( ' AND status=%s AND graduation=%s', 'enrolled', 'in-progress' );
            } elseif ( in_array( $filter, array( 'passed', 'failed' ) ) ) { // is "passed" or "failed"
                $where .= $wpdb->prepare( ' AND status=%s AND graduation=%s', 'finished', $filter );
            }
        }

        $query = "SELECT item_id FROM {$wpdb->prefix}learnpress_user_items WHERE {$where}";

        $item_ids = $wpdb->get_col( $query );

        return $item_ids;

    }

