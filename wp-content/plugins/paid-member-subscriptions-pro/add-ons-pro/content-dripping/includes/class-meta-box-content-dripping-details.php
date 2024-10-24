<?php

if( !class_exists( 'PMS_Meta_Box' ) )
    return;

Class PMS_IN_Meta_Box_Content_Dripping_Details extends PMS_Meta_Box {

    public $post_meta = array();

    /*
     * Initialise all needed components
     *
     */
    public function init() {

        add_action( 'pms_output_content_meta_box_' . $this->post_type . '_' . $this->id, array( $this, 'output' ) );
        add_action( 'pms_save_meta_box_' . $this->post_type, array( $this, 'save_data' ) );

    }


    /*
     * Outputs the content of the meta-box
     *
     */
    public function output( $post ) {

        // Get post meta and set some defaults to empty
        $this->post_meta = $this->get_post_meta( $post, array( 'pms_content_dripping_set_subscription_plan' => '', 'pms_content_dripping_set_status' => '' ) );

        include 'views/view-meta-box-content-dripping-details.php';

    }


    /*
     * Save data of the meta-box
     *
     */
    public function save_data( $post_id ) {

        // Check nonce
        // It's the same nonce used for the content rules meta box
        if( !isset( $_POST['pmstkn'] ) || !wp_verify_nonce( sanitize_text_field( $_POST['pmstkn'] ), 'pms_content_dripping_rules' ) )
            return;

        // Save the subscription plan
        if( !empty( $_POST['pms_content_dripping_set_subscription_plan'] ) )
            update_post_meta( $post_id, 'pms_content_dripping_set_subscription_plan', trim( sanitize_text_field( $_POST['pms_content_dripping_set_subscription_plan'] ) ) );

        // Save the status of the set
        if( !empty( $_POST['pms_content_dripping_set_status'] ) ) {

            $status = trim( sanitize_text_field( $_POST['pms_content_dripping_set_status'] ) );

            update_post_meta( $post_id, 'pms_content_dripping_set_status', $status );

            if ( ! wp_is_post_revision( $post_id ) ){

                // unhook this function so it doesn't loop infinitely
                remove_action('pms_save_meta_box_' . $this->post_type, array( $this, 'save_data' ));

                // Change the post status as the content dripping status
                $post = array(
                    'ID'           => $post_id,
                    'post_status'   => $status,
                );
                wp_update_post( $post );

                // re-hook this function
                add_action('pms_save_meta_box_' . $this->post_type, array( $this, 'save_data' ) );

            }

        }

    }

}

$pms_meta_box_content_dripping_details = new PMS_IN_Meta_Box_Content_Dripping_Details( 'pms_content_dripping_details', __( 'Content Drip Set Details', 'paid-member-subscriptions' ), 'pms-content-dripping', 'normal' );
$pms_meta_box_content_dripping_details->init();