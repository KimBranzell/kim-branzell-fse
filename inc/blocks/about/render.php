<?php
/**
 * About block – render callback.
 *
 * Attributes are defined in block.json.
 */

function kb_render_block_about( array $attributes, string $content = '' ) : string {
	$eyebrow = $attributes['eyebrow'] ?? array(
		'sectionLabel' => '02 About',
		'year'          => '',
	);
	$items   = $attributes['items'] ?? array();

	// Portrait handling.
	$portrait = $attributes['portrait'] ?? null;
	if ( is_array( $portrait ) && ! empty( $portrait['id'] ) ) {
		$portrait_html = wp_get_attachment_image(
			(int) $portrait['id'],
			'full',
			false,
			array( 'class' => 'about__portrait' )
		);
	} elseif ( is_array( $portrait ) && ! empty( $portrait['url'] ) ) {
		$alt = ! empty( $portrait['alt'] ) ? esc_attr( $portrait['alt'] ) : '';
		$portrait_html = sprintf(
			'<img src="%s" alt="%s" class="about__portrait">',
			esc_url( $portrait['url'] ),
			$alt
		);
	} else {
		$portrait_html = '';
	}

	// Build bento items HTML.
	$items_html = '';
	foreach ( $items as $item ) {
		$id      = esc_attr( $item['id'] ?? '' );
		$title   = esc_html( $item['title'] ?? '' );
		$content = trim( $item['content'] ?? '' );
		$size    = esc_attr( $item['size'] ?? 'medium' );

		$content_html = '';
		if ( '' !== $content ) {
			$content_html = '<div class="about__bento-item-content">' . wp_kses_post( wpautop( $content ) ) . '</div>';
		}

		$items_html .= sprintf(
			'<div class="about__bento-item about__bento-item--%s" data-item-id="%s">
				<div class="about__bento-item-header">
					<h3 class="about__bento-item-title">%s</h3>
				</div>
				%s
			</div>',
			$size,
			$id,
			$title,
			$content_html
		);
	}

	ob_start();
	include __DIR__ . '/template.php';
	return ob_get_clean();
}
