<?php
/**
 * About block – template.
 *
 * Variables passed from render.php:
 * @var array  $eyebrow       { sectionLabel, year }
 * @var string $portrait_html  Full <img> output or empty string.
 * @var string $items_html    Bento item divs HTML.
 */
?>
<section class="about" aria-labelledby="about-heading">

	<div class="about__content">
		<div class="about__grid">
			<?php if ( $portrait_html ) : ?>
			<figure class="about__portrait-column">
				<div class="about__portrait-wrapper">
					<?php echo wp_kses_post( $portrait_html ); ?>
				</div>
			</figure>
			<?php endif; ?>

			<div class="about__bento-column">
				<h2 id="about-heading" class="about__section-heading sr-only"><?php esc_html_e( 'About', 'kim-branzell' ); ?></h2>
				<div class="about__bento-grid">
					<?php echo $items_html; ?>
				</div>
			</div>
		</div>
	</div>
</section>
