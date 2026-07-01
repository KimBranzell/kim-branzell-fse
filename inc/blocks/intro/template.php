<?php
/**
 * Intro block – template.
 *
 * Variables passed from render.php:
 * @var array  $eyebrow       { sectionLabel, year }
 * @var string $headline
 * @var string $image_html     Full <img> output.
 * @var string $services_html <li> items HTML.
 */
?>
<div class="intro">
	<p class="intro__eyebrow">
		<span class="intro__eyebrow-section"><?php echo esc_html( $eyebrow['sectionLabel'] ); ?></span>
		<span class="intro__eyebrow-site"><?php bloginfo( 'name' ); ?></span>
		<time class="intro__eyebrow-year" datetime="<?php echo esc_attr( $eyebrow['year'] ); ?>"><?php echo esc_html( $eyebrow['year'] ); ?></time>
	</p>

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
				<ul class="intro__cards">
					<?php echo $services_html; ?>
				</ul>
			</section>
		</div>
	</div>
</div>
