<?php
/**
 * Intro block – render callback.
 *
 * Attributes are defined in block.json.
 */

function kb_render_block_intro( array $attributes, string $content = '' ) : string {
	$eyebrow    = $attributes['eyebrow'] ?? array(
		'sectionLabel' => '01 Introduction',
		'year'          => gmdate( 'Y' ),
	);
	$headline   = esc_html( $attributes['headline'] ?? '' );
	$services   = $attributes['services'] ?? array();

	// Image handling.
	$image = $attributes['image'] ?? null;
	if ( is_array( $image ) && ! empty( $image['id'] ) ) {
		$image_html = wp_get_attachment_image(
			(int) $image['id'],
			'full',
			false,
			array( 'class' => 'intro__image' )
		);
	} elseif ( is_array( $image ) && ! empty( $image['url'] ) ) {
		$alt = ! empty( $image['alt'] ) ? esc_attr( $image['alt'] ) : '';
		$image_html = sprintf(
			'<img src="%s" alt="%s" class="intro__image">',
			esc_url( $image['url'] ),
			$alt
		);
	} else {
		$image_html = '';
	}

	// Build services HTML — accordion layout.
	$services_html = '';
	foreach ( $services as $index => $service ) {
		$title       = esc_html( $service['title'] ?? '' );
		$description = esc_html( $service['description'] ?? '' );
		$link        = esc_url( $service['link'] ?? '#' );
		$inverted    = ! empty( $service['inverted'] );
		$card_class  = $inverted ? 'intro__card intro__card--inverted' : 'intro__card';

		// Unique IDs to avoid collisions when multiple Intro blocks exist.
		$panel_id  = wp_unique_id( 'intro-card-panel-' );
		$toggle_id = wp_unique_id( 'intro-card-toggle-' );
		$number    = sprintf( '%02d', $index + 1 );

		// All panels are rendered visible server-side for progressive enhancement.
		// JavaScript collapses panels 2–N on init and adds .js-intro-accordion.
		// The first panel is pre-marked expanded for the no-JS fallback.
		$is_expanded = ( 0 === $index ) ? 'true' : 'false';

		$services_html .= sprintf(
			'<div class="%1$s" data-expanded="%2$s" role="listitem">
				<button
					class="intro__card-toggle"
					aria-expanded="%2$s"
					aria-controls="%3$s"
					id="%4$s"
				>
					<span class="intro__card-number">%5$s</span>
					<h3 class="intro__card-title h5">%6$s</h3>
					<span class="intro__card-icon" aria-hidden="true">
						<span class="intro__card-icon-plus"></span>
						<span class="intro__card-icon-minus"></span>
					</span>
				</button>
				<div
					class="intro__card-panel"
					id="%3$s"
					role="region"
					aria-labelledby="%4$s"
				>
					<div class="intro__card-panel-inner">
						<p class="intro__card-description">%7$s</p>
						<a href="%8$s" class="intro__card-link" aria-label="%9$s">%10$s</a>
					</div>
				</div>
			</div>',
			$card_class,
			$is_expanded,
			esc_attr( $panel_id ),
			esc_attr( $toggle_id ),
			$number,
			$title,
			$description,
			$link,
			/* translators: %s: Service title */
			sprintf( esc_attr__( 'View details about %s', 'kim-branzell' ), $title ),
			esc_html__( 'View details', 'kim-branzell' )
		);
	}

	ob_start();
	include __DIR__ . '/template.php';
	return ob_get_clean();
}
