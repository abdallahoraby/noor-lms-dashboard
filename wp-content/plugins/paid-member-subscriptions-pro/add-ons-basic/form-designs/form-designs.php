<?php

if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Generate the Form Design Selector to PMS -> General Settings
 *
 */
function pms_render_forms_design_selector() {

    $form_designs_data = pms_get_form_designs_data();

    wp_enqueue_script( 'jquery-ui-dialog' );

    $output = '<div id="pms-forms-design-browser">';

    foreach ( $form_designs_data as $form_design ) {

        if ($form_design['status'] == 'active') {
            $status = ' active';
            $title = '<strong>Active: </strong> ' . $form_design['name'];
        } else {
            $status = '';
            $title = $form_design['name'];
        }

        if ( $form_design['id'] != 'form-style-default' )
            $preview_button = '<div class="pms-forms-design-preview button-secondary" id="'. $form_design['id'] .'-info">Preview</div>';
        else $preview_button = '';

        $output .= '
                <div class="pms-forms-design'. $status .'" id="'. $form_design['id'] .'">
                    <label for="pms-fd-option-' . $form_design['id'] . '">
                        <input type="radio" id="pms-fd-option-' . $form_design['id'] . '" value="' . $form_design['id'] . '" ' . (( $form_design['status'] === 'active' ) ? 'checked="checked"' : '') . ' name="pms_general_settings[forms_design]">
                        ' . $form_design['name'] . '

                       <div class="pms-forms-design-screenshot">
                          <img src="' . $form_design['images']['main'] . '" alt="Form Design">
                          '. $preview_button .'
                       </div>
                   </label>
                </div>
        ';

        $img_count = 0;
        $image_list = '';
        foreach ( $form_design['images'] as $image ) {
            $img_count++;
            $active_img = ( $img_count == 1 ) ? ' active' : '';
            $image_list .= '<img class="pms-forms-design-preview-image'. $active_img .'" src="'. $image .'">';
        }

        if ( $img_count > 1 ) {
            $previous_button = '<div class="pms-slideshow-button pms-forms-design-sildeshow-previous disabled" data-theme-id="'. $form_design['id'] .'" data-slideshow-direction="previous"> < </div>';
            $next_button = '<div class="pms-slideshow-button pms-forms-design-sildeshow-next" data-theme-id="'. $form_design['id'] .'" data-slideshow-direction="next"> > </div>';
            $justify_content = 'space-between';
        }
        else {
            $previous_button = $next_button = '';
            $justify_content = 'center';
        }

        $output .= '<div id="pms-modal-'. $form_design['id'] .'" class="pms-forms-design-modal" title="'. $form_design['name'] .'">
                        <div class="pms-forms-design-modal-slideshow" style="justify-content: '. $justify_content .'">
                            '. $previous_button .'
                            <div class="pms-forms-design-modal-images">
                                '. $image_list .'
                            </div>
                            '. $next_button .'
                        </div>
                    </div>';

    }

    $output .= '</div>';

    if ( defined( 'WPPB_PAID_PLUGIN_DIR' ) && file_exists( WPPB_PAID_PLUGIN_DIR.'/features/form-designs/form-designs.php' ) ){
        $pb_generalSettings = get_option( 'wppb_general_settings' );
        $pb_styles = array(
            'form-style-default' => 'Default Style',
            'form-style-1' => 'Sublime',
            'form-style-2' => 'Greenery',
            'form-style-3' => 'Slim'
        );

        if ( !isset( $pb_generalSettings['formsDesign'] ) )
            $pb_active_form_style = 'Default Style';
        else $pb_active_form_style = $pb_styles[$pb_generalSettings['formsDesign']];

        $output .= '<p class="cozmoslabs-description">'. sprintf( esc_html__( 'For a consistent design on your website, it is best to set the same Form Style for both %1$sPaid Member Subscriptions%2$s and %1$sProfile Builder%2$s plugins.', 'paid-member-subscriptions' ),'<strong>', '</strong>' ) .'</p>';
        $output .= '<p class="cozmoslabs-description">'. sprintf( esc_html__( 'The currently active Form Style for Profile Builder forms is:  %1$s %3$s %2$s.', 'paid-member-subscriptions' ),'<strong>', '</strong>', $pb_active_form_style ) .'</p>';
    }

    return $output;
}


/**
 * Get Form Designs Data
 *
 */
function pms_get_form_designs_data() {
    $active_design = pms_get_active_form_design();

    $form_designs = array(
        array(
            'id' => 'form-style-default',
            'name' => 'Default Style',
            'status' => $active_design == 'form-style-default' ? 'active' : '',
            'images' => array(
                'main' => PMS_PLUGIN_DIR_URL.'assets/images/pms-fd-style-default.jpg',
            ),
        ),
        array(
            'id' => 'form-style-1',
            'name' => 'Sublime',
            'status' => $active_design == 'form-style-1' ? 'active' : '',
            'images' => array(
                'main' => PMS_PLUGIN_DIR_URL.'assets/images/pms-fd-style1-slide1.jpg',
                'slide1' => PMS_PLUGIN_DIR_URL.'assets/images/pms-fd-style1-slide2.jpg',
            ),
        ),
        array(
            'id' => 'form-style-2',
            'name' => 'Greenery',
            'status' => $active_design == 'form-style-2' ? 'active' : '',
            'images' => array(
                'main' => PMS_PLUGIN_DIR_URL.'assets/images/pms-fd-style2-slide1.jpg',
                'slide1' => PMS_PLUGIN_DIR_URL.'assets/images/pms-fd-style2-slide2.jpg',
            ),
        ),
        array(
            'id' => 'form-style-3',
            'name' => 'Slim',
            'status' => $active_design == 'form-style-3' ? 'active' : '',
            'images' => array(
                'main' => PMS_PLUGIN_DIR_URL.'assets/images/pms-fd-style3-slide1.jpg',
                'slide1' => PMS_PLUGIN_DIR_URL.'assets/images/pms-fd-style3-slide2.jpg',
            ),
        )
    );

    return $form_designs;
}


/**
 * Get the Form Designs active Style
 *
 */
function pms_get_active_form_design() {
    $wppb_generalSettings = get_option( 'pms_general_settings' );

    if ( empty( $wppb_generalSettings['forms_design'] ) || $wppb_generalSettings['forms_design'] == 'form_style_default')
        $active_design = 'form-style-default';
    else $active_design = $wppb_generalSettings['forms_design'];

    return $active_design;
}


/**
 * Add Form Design classes
 *
 */
function pms_add_form_design_classes( $existing_classes, $location ) {

    $active_design = pms_get_active_form_design();

    // we don't need the extra classes for the Default Style
    if ( $active_design == 'form-style-default' )
        return $existing_classes;

    $existing_classes .= ' pms-form-design-wrapper pms-'. $active_design;
    return $existing_classes;
}
add_filter( 'pms_add_extra_form_classes', 'pms_add_form_design_classes', 10, 2 );


/**
 * Add Form Design wrapper
 *
 */
function pms_add_form_design_wrapper( $output ) {
    $active_design = pms_get_active_form_design();

    // we don't need the wrapper for the Default Style
    if ( $active_design == 'form-style-default'  )
        return $output;

    $edited_output = '<div class="pms-form-design-wrapper pms-'. $active_design .'">';
    $edited_output .= $output;
    $edited_output .= '</div>';

    return $edited_output;
}
add_filter( 'pms_account_shortcode_content', 'pms_add_form_design_wrapper', 10 );


/**
 * Load Form Design Feature Scripts and Styles
 *
 */
function pms_enqueue_form_design_styles() {
    $active_design = pms_get_active_form_design();

    if ( $active_design == 'form-style-default' )
        return;

    $file_path = plugin_dir_url( __FILE__ ) . 'css/pms-fd-'. $active_design .'.css';

    wp_register_style( 'pms_form_designs_style', $file_path, array(),PMS_VERSION );
    wp_enqueue_style( 'pms_form_designs_style' );

    wp_enqueue_style( 'pms-style-front-end', PMS_PLUGIN_DIR_URL . 'assets/css/style-front-end.css', array(), PMS_VERSION );
}
add_action('wp_enqueue_scripts' , 'pms_enqueue_form_design_styles');
add_action('elementor/editor/after_enqueue_styles' , 'pms_enqueue_form_design_styles');
add_action( 'enqueue_block_editor_assets', 'pms_enqueue_form_design_styles' );

function pms_enqueue_form_design_scripts() {
    $active_design = pms_get_active_form_design();

    if ( $active_design == 'form-style-default' )
        return;

    $file = 'pms-fd-front-end.js';

    $file_path = plugin_dir_url( __FILE__ ) . 'js/'.$file;

    wp_enqueue_script( 'pms_form_designs_script', $file_path, array( 'jquery' ), PMS_VERSION );

}
add_action('wp_enqueue_scripts' , 'pms_enqueue_form_design_scripts');
add_action('elementor/editor/after_enqueue_scripts' , 'pms_enqueue_form_design_scripts');


/**
 * Remove PB Form Designs Feature wrapper class and extra title
 *
 */
function pms_clear_ec_payment_form_pb_styling( $form_content ) {

    $pb_fd_wrappers = array('wppb-form-style-1-wrapper', 'wppb-form-style-2-wrapper', 'wppb-form-style-3-wrapper');

    foreach ( $pb_fd_wrappers as $wrapper ) {
        if ( strpos( $form_content, $wrapper ) !== false ) {
            $form_content = str_replace( 'id="'. $wrapper .'"', 'style="width: 100%; max-width: 1170px;"', $form_content );

            $pattern = '/<h2\s+class="wppb-form-title">.*?<\/h2>/i';
            $form_content = preg_replace($pattern, '', $form_content);
            break;
        }
    }

    return $form_content;
}
add_filter( 'wppb_register_activate_user_error_message2', 'pms_clear_ec_payment_form_pb_styling', 150 );


// Output heading for Subscription Plans field
add_action( 'pms_register_form_subscription_plans_field_before', 'pms_fd_output_subscription_plans_field_heading' );
add_action( 'pms_new_subscription_form_subscription_plans_field_before', 'pms_fd_output_subscription_plans_field_heading' );
function pms_fd_output_subscription_plans_field_heading( $atts ){

    $active_design = pms_get_active_form_design();

    if ( $active_design == 'form-style-default' )
        return;

    echo '<h3 class="pms-subscriptions-list-title">'. esc_html__( 'Select Your Subscription Plan', 'paid-member-subscriptions' ) .'</h3>';

}

add_action( 'admin_init', 'pms_fd_add_notification_for_incompatible_free_plugin_version' );
function pms_fd_add_notification_for_incompatible_free_plugin_version(){

    if( defined( 'PMS_VERSION' ) && version_compare( PMS_VERSION, '2.12.7', '>' ) )
        return; 

    $active_design = pms_get_active_form_design();

    if ( $active_design == 'form-style-default' )
        return;

    $notification_id = 'pms_fd_free_version_incompatibility';

    $message .= '<p style="margin-top: 16px;">' . wp_kses_post( 'Your core <strong>Paid Member Subscriptions</strong> version is incompatible with the current <strong>Form Design</strong> that you have selected. <br>Please update <strong>Paid Member Subscriptions</strong> to at least version <strong>2.12.8</strong> to ensure maximum compatibility.' ) . '</p>';
    $message .= '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms_dismiss_admin_notification' => $notification_id ) ), 'pms_plugin_notice_dismiss' ) ) . '" type="button" class="notice-dismiss"><span class="screen-reader-text">' . esc_html__( 'Dismiss this notice.', 'paid-member-subscriptions' ) . '</span></a>';

    pms_add_plugin_notification( $notification_id, $message, 'pms-notice pms-narrow notice notice-warning', false, array() );

}