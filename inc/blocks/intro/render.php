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

	// Build services HTML.
	$services_html = '';
	foreach ( $services as $index => $service ) {
		$title       = esc_html( $service['title'] ?? '' );
		$description = esc_html( $service['description'] ?? '' );
		$link        = esc_url( $service['link'] ?? '#' );
		$inverted    = ! empty( $service['inverted'] );
		$card_class  = $inverted ? 'intro__card intro__card--inverted' : 'intro__card';

		$services_html .= sprintf(
			'<li class="%s">
				<h2 class="intro__card-title">%s</h2>
				<p class="intro__card-description">%s</p>
				<a href="%s" class="intro__card-link" aria-label="%s">View details</a>
			</li>',
			$card_class,
			$title,
			$description,
			$link,
			/* translators: %s: Service title */
			sprintf( esc_attr__( 'View details about %s', 'kim-branzell' ), $title )
		);
	}

	ob_start();
	include __DIR__ . '/template.php';
	return ob_get_clean();
}
