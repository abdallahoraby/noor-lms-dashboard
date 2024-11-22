<?php
/**
 * Template for displaying course rate.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/addons/course-review/course-rate.php.
 *
 * @author  ThimPress
 * @package LearnPress/Course-Review/Templates
 * version  3.0.2
 */

// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! isset( $course_rate_res ) ) {
	return;
}

$rated = $course_rate_res['rated'] ?? 0;
$total = $course_rate_res['total'] ?? 0;
?>
<div class="course-rate">
	<div class="course-rate__summary">
		<div class="course-rate__summary-value"><?php echo esc_html( $rated ); ?></div>
		<div class="course-rate__summary-stars">
			<?php
			LP_Addon_Course_Review_Preload::$addon->get_template( 'rating-stars.php', [ 'rated' => $rated ] );
			?>
		</div>
		<div class="course-rate__summary-text">
			<?php printf( _n( '<span>%d</span> rating', '<span>%d</span> ratings', $total, 'learnpress-course-review' ), $total ); ?>
		</div>
	</div>
	<div class="course-rate__details">
		<?php
		foreach ( $course_rate_res['items'] as $item ) :
			?>
			<div class="course-rate__details-row">
				<span class="course-rate__details-row-star">
					<?php echo esc_html( $item['rated'] ); ?>
					<i class="fas" style="color: #ffb60a">
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-star-fill" viewBox="0 0 16 16">
					<path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.283.95l-3.523 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z"/>
				</svg>
					</i>
				</span>
				<div class="course-rate__details-row-value">
					<div class="rating-gray"></div>
					<div class="rating" style="width:<?php echo $item['percent']; ?>%;"
						title="<?php echo esc_attr( $item['percent'] ); ?>%">
					</div>
					<span class="rating-count"><?php echo $item['total']; ?></span>
				</div>
			</div>
			<?php
		endforeach;
		?>
	</div>
</div>
