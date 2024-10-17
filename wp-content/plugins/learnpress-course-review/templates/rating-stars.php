<?php
/**
 * Template for displaying rating stars.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/addons/course-review/rating-stars.php.
 *
 * @author  ThimPress
 * @package LearnPress/Course-Review/Templates
 * version  3.0.7
 */

// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! isset( $rated ) ) {
	return;
}

$percent = min( 100, ( round( (int) $rated * 2 ) / 2 ) * 20 );
$title   = sprintf( __( '%s out of 5 stars', 'learnpress-course-review' ), round( (int) $rated, 2 ) );
wp_enqueue_style( 'course-review' );
?>
<div class="review-stars-rated" title="<?php echo esc_attr( $title ); ?>">
	<?php
	for ( $i = 1; $i <= 5; $i ++ ) {
		$p = ( $i * 20 );
		$r = max( $p <= $percent ? 100 : ( $percent - ( $i - 1 ) * 20 ) * 5, 0 );
		?>
		<div class="review-star">
			<span class="far">
  				<svg width="17px" height="16px" viewBox="0 0 17 16" xmlns="http://www.w3.org/2000/svg">
					<g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
						<g fill="#FFB606" fill-rule="nonzero">
							<path d="M8.5,0 L10.9285714,6.15384615 L17,6.15384615 L11.5357143,9.84615385 L13.9642857,16 L8.5,12.3076923 L3.03571429,16 L5.46428571,9.84615385 L0,6.15384615 L6.07142857,6.15384615 L8.5,0 Z M8.46921775,3.53848077 L7.09419569,7.21637091 L3.96923077,7.21637091 L6.96923077,9.20675852 L5.63589261,12.5384808 L8.46921775,10.5710529 L11.3025689,12.5384808 L9.96921341,9.20675852 L12.9692308,7.21637091 L9.84423981,7.21637091 L8.46921775,3.53848077 Z"></path>
						</g>
					</g>
				</svg>
 			</span>
			<span class="fas" style="width:<?php echo $r; ?>%;">
 				<svg xmlns="http://www.w3.org/2000/svg" width="17px" height="16px" viewBox="0 0 17 16">
					<g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
						<g fill="#FFB606" fill-rule="nonzero">
							<polygon points="8.5 12.3076923 3.03571429 16 5.46428571 9.84615385 0 6.15384615 6.07142857 6.15384615 8.5 0 10.9285714 6.15384615 17 6.15384615 11.5357143 9.84615385 13.9642857 16"></polygon>
						</g>
					</g>
				</svg>
			</span>
		</div>
	<?php } ?>
</div>
