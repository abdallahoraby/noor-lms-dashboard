<?php
/**
 * Requirements
 *
 * @package GamiPress\QSM\Requirements
 * @since 1.0.0
 */

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

/**
 * Add the score field to the requirement object
 *
 * @param $requirement
 * @param $requirement_id
 *
 * @return array
 */
function gamipress_qsm_requirement_object( $requirement, $requirement_id ) {

    if( isset( $requirement['trigger_type'] )
        && ( $requirement['trigger_type'] === 'gamipress_qsm_complete_quiz_score'
            || $requirement['trigger_type'] === 'gamipress_qsm_complete_specific_quiz_score'
            || $requirement['trigger_type'] === 'gamipress_qsm_complete_quiz_max_score'
            || $requirement['trigger_type'] === 'gamipress_qsm_complete_specific_quiz_max_score' ) ) {

        // Minimum/Maximum score
        $requirement['qsm_score'] = get_post_meta( $requirement_id, '_gamipress_qsm_score', true );

    }

    if( isset( $requirement['trigger_type'] )
    && ( $requirement['trigger_type'] === 'gamipress_qsm_complete_quiz_between_score'
        || $requirement['trigger_type'] === 'gamipress_qsm_complete_specifc_quiz_between_score' ) ) {

            // Between score
            $requirement['qsm_min_score'] = get_post_meta( $requirement_id, '_gamipress_qsm_min_score', true );
            $requirement['qsm_max_score'] = get_post_meta( $requirement_id, '_gamipress_qsm_max_score', true );
    
    }

    return $requirement;

}
add_filter( 'gamipress_requirement_object', 'gamipress_qsm_requirement_object', 10, 2 );

/**
 * Category field on requirements UI
 *
 * @param $requirement_id
 * @param $post_id
 */
function gamipress_qsm_requirement_ui_fields( $requirement_id, $post_id ) {

    $score = absint( get_post_meta( $requirement_id, '_gamipress_qsm_score', true ) );
    $min_score = get_post_meta( $requirement_id, 'gamipress_qsm_min_score', true );
    $max_score = get_post_meta( $requirement_id, 'gamipress_qsm_max_score', true );
    ?>

    <span class="qsm-quiz-score"><input type="text" value="<?php echo $score; ?>" size="3" maxlength="3" placeholder="points" /></span>
    <span class="qsm-quiz-min-score"><input type="text" value="<?php echo ( ! empty( $min_score ) ? absint( $min_score ) : '' ); ?>" size="3" maxlength="3" placeholder="Min" /></span>
    <span class="qsm-quiz-max-score"><input type="text" value="<?php echo ( ! empty( $max_score ) ? absint( $max_score ) : '' ); ?>" size="3" maxlength="3" placeholder="Max" /></span>

    <?php
}
add_action( 'gamipress_requirement_ui_html_after_achievement_post', 'gamipress_qsm_requirement_ui_fields', 10, 2 );

/**
 * Custom handler to save the score on requirements UI
 *
 * @param $requirement_id
 * @param $requirement
 */
function gamipress_qsm_ajax_update_requirement( $requirement_id, $requirement ) {
    
    if( isset( $requirement['trigger_type'] )
        && ( $requirement['trigger_type'] === 'gamipress_qsm_complete_quiz_score'
            || $requirement['trigger_type'] === 'gamipress_qsm_complete_specific_quiz_score'
            || $requirement['trigger_type'] === 'gamipress_qsm_complete_quiz_max_score'
            || $requirement['trigger_type'] === 'gamipress_qsm_complete_specific_quiz_max_score' ) ) {

        // Save the score field
        update_post_meta( $requirement_id, '_gamipress_qsm_score', $requirement['qsm_score'] );

    }

    if( isset( $requirement['trigger_type'] )
    && ( $requirement['trigger_type'] === 'gamipress_qsm_complete_quiz_between_score'
        || $requirement['trigger_type'] === 'gamipress_qsm_complete_specifc_quiz_between_score' ) ) {

            // Between score
            update_post_meta( $requirement_id, '_gamipress_qsm_min_score', $requirement['qsm_min_score'] );
            update_post_meta( $requirement_id, '_gamipress_qsm_max_score', $requirement['qsm_max_score'] );
    
    }


}
add_action( 'gamipress_ajax_update_requirement', 'gamipress_qsm_ajax_update_requirement', 10, 2 );