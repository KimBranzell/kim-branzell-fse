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
	$count = (int) ( $attributes['count'] ?? 5 );
	$size  = esc_attr( $attributes['size'] ?? 'medium' );

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

	ob_start();
	include __DIR__ . '/template.php';
	return ob_get_clean();
}
