<?php

if( class_exists('PMS_Meta_Box') ) {
    Class PMS_IN_Meta_Box_Subscription_Content_Restriction extends PMS_Meta_Box
    {


        /*
         * Method to hook the output and save data methods
         *
         */
        public function init()
        {

            // Hook the output method to the parent's class action for output instead of overwriting the
            // output_content method
            add_action('pms_output_content_meta_box_' . $this->post_type . '_' . $this->id, array($this, 'output'));

            // Hook the save_data method to the parent's class action for saving data instead of overwriting the
            // save_meta_box method
            add_action('pms_save_meta_box_' . $this->post_type, 'pms_in_gcr_save_metabox_content' );

            /* set ajax hooks */
            /* hook to add a new rule */
            add_action('wp_ajax_pms_add_new_restriction_rule', array($this, 'pms_add_new_restriction_rule'));
            /* hook to add a new taxonomy in the rule */
            add_action('wp_ajax_pms_add_new_taxonomy_in_rule', array($this, 'pms_add_new_taxonomy_in_rule'));
            /* hook to render the terms in a taxonomy */
            add_action('wp_ajax_pms_render_taxonomy', array($this, 'wp_ajax_pms_render_taxonomy'));

            /* actually restrict the content */
            add_filter( 'the_content', array($this, 'pms_gcr_filter_content'), 11);
            add_filter( 'pms_post_restricted_check', array($this, 'pms_gcr_filter_content'), 10, 2 );

        }

        /*
         * Method to output the HTML for this meta-box
         *
         */
        public function output($post)
        {
            $subscription_plan = pms_get_subscription_plan($post);
            include_once PMS_IN_GCR_PLUGIN_DIR . 'views/view-meta-box-subscription-content-restriction.php';
        }

        /**
         * Ajax function that ads a new restriction rule row
         */
        function pms_add_new_restriction_rule()
        {
            if (!empty($_POST['post_id']))
                $post_id = (int)$_POST['post_id'];
            else
                $post_id = 0;

            if (!empty($_POST['current_nr_of_rules']))
                $current_nr_of_rules = (int)$_POST['current_nr_of_rules'];
            else
                $current_nr_of_rules = NULL;

            $subscription_plan = get_post($post_id);
            $post = get_post($post_id);
            echo PMS_IN_Meta_Box_Subscription_Content_Restriction::pms_output_content_restriction_row($subscription_plan, 1, $post, $current_nr_of_rules); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            wp_die();
        }

        /**
         * Ajax function that generates a new taxonomy select for the rules post type
         */
        function pms_add_new_taxonomy_in_rule()
        {
            if (!empty($_POST['current_nr_of_rules']))
                $current_nr_of_rules = (int)$_POST['current_nr_of_rules'];
            else
                $current_nr_of_rules = NULL;

            if (!empty($_POST['current_post_type']))
                $selected_post_type = sanitize_text_field( $_POST['current_post_type'] );
            else
                $selected_post_type = 'post';

            $taxonomies_for_post_type = get_object_taxonomies($selected_post_type, 'objects');

            echo '<div class="pms-meta-box-field-wrapper">' . PMS_IN_Meta_Box_Subscription_Content_Restriction::pms_render_taxonomy_select($taxonomies_for_post_type, $current_nr_of_rules) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            wp_die();
        }

        /**
         * Ajax function that renders the terms in a taxonomy
         */
        function wp_ajax_pms_render_taxonomy()
        {
            if (!empty($_POST['taxonomy']))
                $taxonomy = sanitize_text_field( $_POST['taxonomy'] );
            else
                $taxonomy = '';

            if (!empty($_POST['post-type']))
                $post_type = sanitize_text_field( $_POST['post-type'] );
            else
                $post_type = '';

            if (!empty($_POST['post_id']))
                $post_id = (int)$_POST['post_id'];
            else
                $post_id = '';

            echo PMS_IN_Meta_Box_Subscription_Content_Restriction::pms_render_taxonomy_box($taxonomy, $post_type, $post_id); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

            wp_die();
        }

        /**
         * Function that generates the output for the subscription content restriction, for one row or multiple rows
         *
         * @param $subscription_plan Subscription object
         * @param $nr_of_rules int number of rules to render
         * @param null $post the post object
         * @param null $current_nr_of_rules int used when adding a new rule in ajax as the rule counter for the rule that is being added
         * @return string html output
         */
        static function pms_output_content_restriction_row($subscription_plan, $nr_of_rules, $post = NULL, $current_nr_of_rules = NULL)
        {
            /* set the post correctly */
            if ($post == NULL) {
                if (!empty($_GET['post']))
                    $post = get_post( (int)$_GET['post'] );
                else {
                    global $post;
                }
            }

            /* get registered post types for current site */
            $args = array(
                'public' => true
            );
            $post_types = get_post_types($args, 'objects');

            /* the output string */
            $output = '';

            for ($i = 1; $i <= $nr_of_rules; $i++) {

                $output .= '<div class="pms_content-rule">';

                /* when we are adding a new rule in ajax we need to know it's counter  */
                if ($current_nr_of_rules != NULL)
                    $current_counter = $current_nr_of_rules;
                else
                    $current_counter = $i;

                /* get post types and taxonomies for the current rule */
                $selected_post_type = get_post_meta($subscription_plan->id, 'pms_content_rule_post_type_' . $current_counter, true);
                $saved_taxonomies = get_post_meta($subscription_plan->id, 'pms_content_rule_taxonomies_' . $current_counter, true);
                /* get all the taxonomies that are attached to the post type  */
                $taxonomies_for_post_type = get_object_taxonomies($selected_post_type, 'objects');


                $output .= '<div class="pms_content-rule-inside" ' . (!empty($selected_post_type) ? 'data-taxonomy-lines="1"' : '') . '>';

                $output .= '<span class="pms_post-type-taxonomy-line"><!-- --></span>';

                $output .= '<div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper">';
                $output .= '<label for="pms_content_rule_post_type[]" class="pms-meta-box-field-label cozmoslabs-form-field-label">' . __('Post Type', 'paid-member-subscriptions') . '</label>';
                $output .= '<select name="pms_content_rule_post_type[]" class="pms-post-type-select">';
                $output .= '<option value=""> ' . __('Choose...', 'paid-member-subscriptions') . '</option>';
                foreach ($post_types as $post_slug => $post_type) {
                    $output .= '<option value="' . esc_attr( $post_slug ) . '" ' . selected($selected_post_type, $post_slug, false) . '>' . esc_attr( $post_type->name ) . '</option>';
                }
                $output .= '</select>';
                $output .= '<p class="cozmoslabs-description cozmoslabs-description-align-right">' . __('Choose a post type.', 'paid-member-subscriptions') . '</p>';
                $output .= '</div>';

                if (!empty($saved_taxonomies)) {
                    $saved_taxonomies_array = explode(',', $saved_taxonomies);
                    $j = 1;
                    foreach ($saved_taxonomies_array as $tax) {
                        $output .= '<div class="pms-meta-box-field-wrapper">';
                        $output .= PMS_IN_Meta_Box_Subscription_Content_Restriction::pms_render_taxonomy_select($taxonomies_for_post_type, $current_counter, $tax);
                        $output .= PMS_IN_Meta_Box_Subscription_Content_Restriction::pms_render_taxonomy_box($tax, $selected_post_type, $subscription_plan->id);
                        $output .= '</div>';
                        $j++;
                    }
                }

                if ($selected_post_type == '')
                    $disabled = ' disabled';
                else
                    $disabled = '';
                $output .= '<a href="#" class="pms-add-taxonomy' . $disabled . '" data-current-post-type="' . esc_attr( $selected_post_type ) . '" data-nr-of-rules="' . ((int)$current_counter) . '"><span class="pms_taxonomy-line"><!-- --></span><span class="dashicons dashicons-plus"></span>' . __('Add Taxonomy', 'paid-member-subscriptions') . '</a>';

                $output .= '</div>';
                $output .= '<div class="pms_-remove-rule"><a href="#" class="cozmoslabs-remove-item" title="' . __('Remove this rule', 'paid-member-subscriptions') . '"><span class="dashicons dashicons-no"></span></a></div>';
                $output .= '</div>';
            }

            $output .= '<div id="pms_add-new-rule-container"><a href="#" class="pms-add-rule" data-post-id="' . esc_attr( $post->ID ) . '" data-nr-of-rules="' . ((int)$current_counter + 1) . '"><span class="dashicons dashicons-plus"></span>' . __('Add Rule', 'paid-member-subscriptions') . '</a></div>';

            return $output;
        }

        /**
         * @param $taxonomies_for_post_type array of registered taxonomies for the post type
         * @param $current_rule_counter int the nr of the rule
         * @param null $current_taxonomy string the selected taxonomy if there is one
         * @return string
         */
        static function pms_render_taxonomy_select($taxonomies_for_post_type, $current_rule_counter, $current_taxonomy = null)
        {
            $output = '';

            $output .= '<label for="pms_content_rule_taxonomy_' . esc_attr( $current_rule_counter ) . '[]" class="pms-meta-box-field-label pms-field-label-taxonomy cozmoslabs-form-field-label"><span class="dashicons dashicons-editor-break"></span>' . __('Taxonomy', 'paid-member-subscriptions') . '</label>';
            $output .= '<select name="pms_content_rule_taxonomy_' . esc_attr( $current_rule_counter ) . '[]" class="pms-taxonomy-rule-change">';
            $output .= '<option value=""> ' . __('Choose...', 'paid-member-subscriptions') . '</option>';
            foreach ($taxonomies_for_post_type as $taxonomy_slug => $taxonomy) {
                $output .= '<option value="' . esc_attr( $taxonomy_slug ) . '" ' . selected($current_taxonomy, $taxonomy_slug, false) . '>' . esc_html( $taxonomy->labels->singular_name ) . '</option>';
            }
            $output .= '</select>';
            $output .= '<a href="#" class="pms-remove-taxonomy">' . __('Remove', 'paid-member-subscriptions') . '</a>';
            $output .= '<span class="pms_taxonomy-line"><!-- --></span>';

            return $output;
        }

        /**
         * @param $taxonomy the taxonomy for which we show the terms
         * @param $post_type the post type for which we saved the terms
         * @param $subscription_plan_id int the id of the subscription plan
         * @return string
         */
        static function pms_render_taxonomy_box($taxonomy, $post_type, $subscription_plan_id)
        {
            $terms = get_terms($taxonomy, array('hide_empty' => false));
            $selected_taxonomies = get_post_meta($subscription_plan_id, 'pms_content_rule_tax_terms_' . $post_type . '_' . $taxonomy, true);
            $selected_taxonomies = explode(',', $selected_taxonomies);

            if (!empty($terms) && !is_wp_error( $terms ) ) {
                $output = '<ul id="' . esc_attr( $taxonomy ) . 'checklist">';

                foreach ($terms as $term) {
                    $output .= '<li>';

                    $output .= '<div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">';

                    $output .= '<label class="selectit cozmoslabs-form-field-label" for="in-' . esc_attr( $term->taxonomy ) . '-' . esc_attr( $term->term_id ) . '">' . esc_html($term->name) . '</label>';

                    $output .= '<div class="cozmoslabs-toggle-container">';
                    $output .= '<input value="' . esc_attr( $term->term_id ) . '" type="checkbox" name="pms_content_rule_tax_terms[' . esc_attr( $post_type ) . '][' . esc_attr( $term->taxonomy ) . '][]" id="in-' . esc_attr( $term->taxonomy ) . '-' . esc_attr( $term->term_id ) . '"' . checked(in_array($term->term_id, $selected_taxonomies), true, false) . ' />';
                    $output .= '<label class="cozmoslabs-toggle-track" for="in-' . esc_attr( $term->taxonomy ) . '-' . esc_attr( $term->term_id ) . '"></label>';
                    $output .= '</div>';


                    $output .= '</div>';




                    $output .= '</li>';
                }

                $output .= '</ul>';
                return $output;
            }
        }

        /**
         * @param $content
         * @return mixed|void
         *
         * We check the current post if it is present in any rule of a Subscription Plan. If it is then we have two cases:
         * 1) if there is not user logged in than we don't show the content.
         * 2) if the user is logged in and is subscribed to that plan we show the content
         *
         */
        static function pms_gcr_filter_content( $content, $post = null )
        {
            global $user_ID, $pms_show_content;

            if( is_null( $post ) )
                global $post;

            if( empty( $post ) )
                return $content;

            // Show for administrators
            if( current_user_can( 'manage_options' ) )
                return $content;

            // If the $content has been explicitly filtered before ( which should be only by the main plugin ) return it, whatever it is
            if( $pms_show_content !== null )
                return $content;

            /* get all the Subscription Plans */
            $subscription_plans = pms_get_subscription_plans();

            /* if we have any then go through each of them, if not show the content */
            if (!empty($subscription_plans)) {

                $has_access = array();

                foreach ($subscription_plans as $subscription_plan) {
                    /* check the post against all the rules */
                    /* if no rules apply to this post then negate the result and show the content */
                    $has_rule = PMS_IN_Meta_Box_Subscription_Content_Restriction::pms_gcr_show_content($subscription_plan->id, $post);

                    /* if the content shouldn't be shown (a rule applies to it from a plan ) and we have a logged in user then let's see if he is subscribed to the plan */
                    if( $has_rule ) {

                        $has_access[$subscription_plan->id] = false;

                        if (is_user_logged_in()) {

                            $member = pms_get_member($user_ID);
                            $user_subscription_plans = $member->get_subscriptions();

                            if (!empty($user_subscription_plans)) {
                                foreach ($user_subscription_plans as $user_subscription_plan) {
                                    if ($user_subscription_plan['subscription_plan_id'] == $subscription_plan->id && ( $user_subscription_plan['status'] == 'active' || $user_subscription_plan['status'] == 'canceled' ) ) {
                                        $has_access[$subscription_plan->id] = true;
                                    }
                                }
                            }
                        }

                    }

                }


                if ( empty( $has_access ) || in_array( true, $has_access ) ) {
                    $pms_show_content = true;
                    return $content;
                } else {

                    $pms_show_content = false;

                    if( is_user_logged_in() ) {
                        $message = pms_process_restriction_content_message( 'non_members', $user_ID, $post->ID );
                        return do_shortcode( apply_filters( 'pms_restriction_message_non_members', $message, $content, $post, $user_ID ) );
                    }
                    else {
                        $message = pms_process_restriction_content_message( 'logged_out', $user_ID, $post->ID );
                        return do_shortcode( apply_filters( 'pms_restriction_message_logged_out', $message, $content, $post, $user_ID ) );
                    }
                }
            }


            return $content;
        }


        /**
         * @param $subscription_plan_id The id of Subscription Plan
         * @param $post - the current post object
         * @return bool wheather or not we show the content
         *
         * This function determines if the current post should display its content. We start by setting the boolean to false and if there is a rule in the
         * Subscription Plan that matches the post we set it to true. We use the boolean that is returned in different ways for logged in users and not logged in.
         * For not logged in user we get the negative of the returned value meaning if there is a rule that matches the post in a Subscription Plan then we don't show it.
         *
         */
        static function pms_gcr_show_content($subscription_plan_id, $post)
        {
            $show_content = false;

            $nr_of_rules = get_post_meta($subscription_plan_id, 'pms_nr_of_rules', 'true');
            if (!empty($nr_of_rules)) {
                if (!empty($nr_of_rules) && $nr_of_rules > 0) {
                    /* go through all the rules */
                    for ($k = 1; $k <= $nr_of_rules; $k++) {
                        /* first we check if the rules post type matches the current posts type */
                        $rules_post_type = get_post_meta($subscription_plan_id, 'pms_content_rule_post_type_' . $k, true);
                        $posts_post_type = get_post_type($post);
                        if ($rules_post_type == $posts_post_type) {
                            /* get the taxonomies for that rule */
                            $rule_taxonomies = get_post_meta($subscription_plan_id, 'pms_content_rule_taxonomies_' . $k, true);
                            /* if we have any taxonomies go forward */
                            if (!empty($rule_taxonomies)) {
                                $rule_taxonomies = explode(',', $rule_taxonomies);
                                /* get the taxonomies that have terms in them for the post */
                                $post_taxonomies = get_object_taxonomies($post);
                                if (!empty($post_taxonomies)) {
                                    foreach ($post_taxonomies as $key => $post_taxonomy) {
                                        $post_terms = wp_get_object_terms($post->ID, $post_taxonomy);
                                        if (empty($post_terms))
                                            unset($post_taxonomies[$key]);
                                    }
                                }
                                /* go through the rules */
                                foreach ($rule_taxonomies as $rule_taxonomy) {
                                    /* check if the post has any term attached to the taxonomy of the rule  */
                                    if (in_array($rule_taxonomy, $post_taxonomies)) {

                                        $rule_taxonomy_terms = get_post_meta($subscription_plan_id, 'pms_content_rule_tax_terms_' . $rules_post_type . '_' . $rule_taxonomy, true);
                                        if (!empty($rule_taxonomy_terms)) {
                                            $rule_taxonomy_terms = explode(',', $rule_taxonomy_terms);
                                            /* see what post terms we have */
                                            foreach ($rule_taxonomy_terms as $rule_taxonomy_term) {
                                                if (has_term($rule_taxonomy_term, $rule_taxonomy, $post)) {
                                                    $show_content = true;
                                                }
                                            }

                                        } else {
                                            $show_content = true;
                                        }
                                    }
                                }

                            } else {
                                $show_content = true;
                            }
                        }
                    }

                }
            }

            return $show_content;
        }

    }

    /* initialize the object */
    $pms_meta_box_subscription_content_restriction = new PMS_IN_Meta_Box_Subscription_Content_Restriction('pms_subscription_content_restriction', __('Global Content Restriction', 'paid-member-subscriptions'), 'pms-subscription', 'normal');
    $pms_meta_box_subscription_content_restriction->init();
}

/**
 * This was moved to a function so we can remove the filter easily
 *
 * @since 1.0.8
 */
function pms_in_gcr_save_metabox_content( $post_id ) {

    if( isset( $_POST['post_ID'] ) && $_POST['post_ID'] == $post_id ){
        /* delete rules first */
        $old_nr_of_rules = get_post_meta($post_id, 'pms_nr_of_rules', 'true');
        if (!empty($old_nr_of_rules)) {
            for ($k = 1; $k <= $old_nr_of_rules; $k++) {
                $old_post_type = get_post_meta($post_id, 'pms_content_rule_post_type_' . $k, true);
                delete_post_meta($post_id, 'pms_content_rule_post_type_' . $k);
                $old_tax = get_post_meta($post_id, 'pms_content_rule_taxonomies_' . $k, true);
                delete_post_meta($post_id, 'pms_content_rule_taxonomies_' . $k);
                $old_tax = explode(',', $old_tax);
                foreach ($old_tax as $tax) {
                    delete_post_meta($post_id, 'pms_content_rule_tax_terms_' . $old_post_type . '_' . $tax);
                }
            }
            update_post_meta( $post_id, 'pms_nr_of_rules', 1 );
        }


        /* save post types */
        if (!empty($_POST['pms_content_rule_post_type'])) {
            $nr_of_rules = count($_POST['pms_content_rule_post_type']);

            /* save the meta here */
            update_post_meta($post_id, 'pms_nr_of_rules', $nr_of_rules);
            foreach ($_POST['pms_content_rule_post_type'] as $key => $post_type) {//phpcs:ignore
                $key = sanitize_key($key);
                $post_type = sanitize_text_field($post_type);

                /* save post type rule */
                update_post_meta($post_id, 'pms_content_rule_post_type_' . ((int)$key + 1), $post_type);

                /* save taxonomies for each post type */
                if (!empty($_POST['pms_content_rule_taxonomy_' . ((int)$key + 1)])) {
                    /* save taxonomies for that rule */
                    update_post_meta($post_id, 'pms_content_rule_taxonomies_' . ((int)$key + 1), sanitize_text_field( implode(',', $_POST['pms_content_rule_taxonomy_' . ((int)$key + 1)]) ));//phpcs:ignore
                    /* save terms for the taxonomies for each taxonomy */
                    foreach ($_POST['pms_content_rule_taxonomy_' . ((int)$key + 1)] as $taxonomy) {//phpcs:ignore
                        $taxonomy = sanitize_text_field( $taxonomy );
                        if (!empty($_POST['pms_content_rule_tax_terms'][$post_type][$taxonomy])) {
                            /* save terms for the taxonomy for the post type */
                            update_post_meta($post_id, 'pms_content_rule_tax_terms_' . $post_type . '_' . $taxonomy, sanitize_text_field( implode(',', $_POST['pms_content_rule_tax_terms'][$post_type][$taxonomy]) ) );//phpcs:ignore
                        }
                    }
                }
            }
        }
    }

}
