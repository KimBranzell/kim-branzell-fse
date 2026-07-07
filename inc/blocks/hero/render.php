<?php
/**
 * Hero block – render callback.
 *
 * Attributes are defined in block.json.
 */

function kb_render_block_hero( array $attributes, string $content = '' ) : string {
	$enabled = (bool) ( $attributes['enabled'] ?? true );

	if ( ! $enabled ) {
		return '';
	}

	$title = esc_html( $attributes['title'] ?? 'Hello World' );
	$intro = esc_html( $attributes['intro'] ?? '' );
	$count = (int) ( $attributes['count'] ?? 5 );
	$size  = esc_attr( $attributes['size'] ?? 'medium' );
	$ctas  = $attributes['ctas'] ?? array();

	// Image: stored as { id, url, alt } object from MediaPlaceholder.
	$image = $attributes['image'] ?? null;

	if ( is_array( $image ) && ! empty( $image['id'] ) ) {
		$image_html = wp_get_attachment_image(
			(int) $image['id'],
			'full',
			false,
			array( 'class' => 'hero__image' )
		);
	} elseif ( is_array( $image ) && ! empty( $image['url'] ) ) {
		// Fallback for external images (no attachment ID).
		$alt = ! empty( $image['alt'] ) ? esc_attr( $image['alt'] ) : '';
		$image_html = sprintf(
			'<img src="%s" alt="%s" class="hero__image">',
			esc_url( $image['url'] ),
			$alt
		);
	} else {
		$image_html = '';
	}

	// Build CTAs HTML.
	$ctas_html = '';
	foreach ( $ctas as $cta ) {
		$cta_text = esc_html( $cta['text'] ?? __( 'Learn more', 'kim-branzell' ) );
		$cta_url  = esc_url( $cta['url'] ?? '#' );
		$ctas_html .= sprintf(
			'<a href="%s" class="hero__cta-button">%s</a>',
			$cta_url,
			$cta_text
		);
	}

	ob_start();
	include __DIR__ . '/template.php';
	return ob_get_clean();
}
