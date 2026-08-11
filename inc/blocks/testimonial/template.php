<?php
/**
 * Testimonial block – template markup.
 *
 * Variables passed from render.php:
 *
 * @var string $slides_html      Fully-escaped <li> slides markup.
 * @var string $dots_html        Fully-escaped tablist dot buttons.
 * @var string $prev_button      Fully-escaped previous button HTML.
 * @var string $next_button      Fully-escaped next button HTML.
 * @var string $heading_id       Unique id for aria-labelledby on the section.
 * @var string $viewport_id      Unique id for the carousel viewport region.
 * @var string $announcer_id     Unique id for the live-region element.
 * @var string $background       Sanitised background slug (main|alternative|accent|buttons).
 * @var string $section_label    Posed section label ('Testimonials').
 * @var bool   $autoplay         Whether autoplay is enabled.
 * @var int    $interval         Autoplay interval in milliseconds.
 * @var int    $total            Number of testimonials in this block.
 *
 * @package Kim_Branzell
 */

?>
<section
	class="testimonial testimonial--<?php echo esc_attr( $background ); ?>"
	aria-labelledby="<?php echo esc_attr( $heading_id ); ?>"
	data-autoplay="<?php echo $autoplay ? 'true' : 'false'; ?>"
	data-interval="<?php echo esc_attr( (string) $interval ); ?>"
	data-total="<?php echo esc_attr( (string) $total ); ?>"
>
	<div class="testimonial__inner">

		<h2 class="sr-only" id="<?php echo esc_attr( $heading_id ); ?>">
			<?php echo esc_html( $section_label ); ?>
		</h2>

		<div
			class="testimonial__viewport"
			id="<?php echo esc_attr( $viewport_id ); ?>"
			role="region"
			aria-roledescription="<?php echo esc_attr( __( 'carousel', 'kim-branzell' ) ); ?>"
			aria-label="<?php echo esc_attr( $section_label ); ?>"
			tabindex="0"
		>
			<ul class="testimonial__track" role="list">
				<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built pre-escaped in render.php. ?>
				<?php echo $slides_html; ?>
			</ul>
		</div>

		<div class="testimonial__controls">
			<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built pre-escaped in render.php. ?>
			<?php echo $prev_button; ?>

			<div
				class="testimonial__dots"
				role="tablist"
				aria-label="<?php echo esc_attr( $section_label ); ?>"
			>
				<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built pre-escaped in render.php. ?>
				<?php echo $dots_html; ?>
			</div>

			<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built pre-escaped in render.php. ?>
			<?php echo $next_button; ?>
		</div>

		<div
			class="testimonial__announcer screen-reader-text"
			id="<?php echo esc_attr( $announcer_id ); ?>"
			aria-live="polite"
			aria-atomic="true"
		></div>

	</div>
</section>
