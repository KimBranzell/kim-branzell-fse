<?php
/**
 * Intro block – template.
 *
 * Variables passed from render.php:
 *
 * @var array  $eyebrow       { sectionLabel, year }
 * @var string $headline
 * @var string $image_html     Full <img> output.
 * @var string $services_html Service accordion items HTML.
 */
?>
<div class="intro">

	<div class="intro__content">
		<h2 class="intro__headline"><?php echo $headline; ?></h2>

		<div class="intro__grid">
			<?php if ( $image_html ) : ?>
			<figure class="intro__image-column">
				<div class="intro__image-wrapper">
					<?php echo wp_kses_post( $image_html ); ?>
				</div>
			</figure>
			<?php endif; ?>

			<section class="intro__cards-column" aria-label="Services">
				<div class="intro__cards" role="list" aria-multiselectable="false">
					<?php echo $services_html; ?>
				</div>
			</section>
		</div>
	</div>
</div>
