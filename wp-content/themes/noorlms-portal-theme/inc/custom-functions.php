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



    function get_courses_by_category_slug($category_slug) {
        $args = array(
            'post_type' => 'lp_course',
            'posts_per_page' => -1, // Get all courses (change number as needed)
            'tax_query' => array(
                array(
                    'taxonomy' => 'course_category', // LearnPress course category taxonomy
                    'field'    => 'slug',           // Match by slug
                    'terms'    => $category_slug,   // The category slug you want to match
                ),
            ),
        );

        $query = new WP_Query($args);

        // Check if courses are found
        if ($query->have_posts()) {
            $courses = array();
            while ($query->have_posts()) {
                $query->the_post();
                $courses[] = array(
                    'id'    => get_the_ID(),
                    'title' => get_the_title(),
                    'link'  => get_permalink(),
                );
            }
            wp_reset_postdata(); // Reset post data
            return $courses;
        }

        wp_reset_postdata(); // Reset post data if no courses found
        return array();
    }

