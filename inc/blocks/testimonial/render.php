<?php
/**
 * Testimonial block – render callback.
 *
 * @param array  $attributes Block attributes.
 * @param string $_content   Block content (unused).
 *
 * @package Kim_Branzell
 */
function kb_render_block_testimonial( array $attributes, string $_content = '' ): string {
	if ( ! (bool) ( $attributes['enabled'] ?? true ) ) {
		return '';
	}

	$testimonials = $attributes['testimonials'] ?? array();

	if ( empty( $testimonials ) || ! is_array( $testimonials ) ) {
		return '';
	}

	$total = count( $testimonials );

	// Sanitise background enum.
	$background = $attributes['background'] ?? 'buttons';
	if ( ! in_array( $background, array( 'main', 'alternative', 'accent', 'buttons' ), true ) ) {
		$background = 'buttons';
	}

	// Clamp autoplay interval to 6–30 s.
	$autoplay = (bool) ( $attributes['autoplay'] ?? false );
	$interval = max( 6000, min( (int) ( $attributes['interval'] ?? 6000 ), 30000 ) );

	// Unique IDs for this block instance.
	$heading_id    = wp_unique_id( 'testimonial-heading-' );
	$viewport_id   = wp_unique_id( 'testimonial-viewport-' );
	$announcer_id  = wp_unique_id( 'testimonial-announcer-' );
	$section_label = __( 'Testimonials', 'kim-branzell' );

	// ─── Inline SVG icons ──────────────────────────────────────

	$prev_icon = '<svg class="testimonial__nav-icon" aria-hidden="true" focusable="false" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg"><path d="M15 18l-6-6 6-6"/></svg>';

	$next_icon = '<svg class="testimonial__nav-icon" aria-hidden="true" focusable="false" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg"><path d="M9 18l6-6-6-6"/></svg>';

	// ─── Build slides ───────────────────────────────────────────

	$slides_html = '';

	foreach ( $testimonials as $index => $testimonial ) {
		$slide_id    = wp_unique_id( 'testimonial-slide-' );
		$slide_index = $index + 1;

		$quote      = esc_html( $testimonial['quote'] ?? '' );
		$author     = esc_html( $testimonial['authorName'] ?? '' );
		$role       = esc_html( $testimonial['authorRole'] ?? '' );
		$is_first   = ( 0 === $index );
		$aria_label = sprintf(
			/* translators: 1: Current slide number, 2: Total slides */
			__( 'Slide %1$d of %2$d', 'kim-branzell' ),
			$slide_index,
			$total
		);

		// Optional portrait.
		$portrait      = $testimonial['portrait'] ?? null;
		$portrait_html = '';

		if ( is_array( $portrait ) && ! empty( $portrait['id'] ) ) {
			$portrait_html = wp_get_attachment_image(
				(int) $portrait['id'],
				array( 96, 96 ),
				false,
				array(
					'class'   => 'testimonial__avatar',
					'loading' => 'lazy',
				)
			);
		} elseif ( is_array( $portrait ) && ! empty( $portrait['url'] ) ) {
			$alt           = ! empty( $portrait['alt'] ) ? esc_attr( $portrait['alt'] ) : '';
			$portrait_html = sprintf(
				'<img src="%s" alt="%s" class="testimonial__avatar" loading="lazy" width="96" height="96">',
				esc_url( $portrait['url'] ),
				$alt
			);
		}

		$avatar_block = '';
		if ( '' !== $portrait_html ) {
			$avatar_block = sprintf(
				'<figure class="testimonial__avatar-wrap">%s</figure>',
				$portrait_html
			);
		}

		// Attribution block — only rendered when name or role is present.
		$attribution_block = '';
		if ( '' !== $author || '' !== $role ) {
			$author_html = '';
			if ( '' !== $author ) {
				$author_html = sprintf(
					'<span class="testimonial__author">%s</span>',
					$author
				);
			}

			$role_html = '';
			if ( '' !== $role ) {
				$role_html = sprintf(
					'<span class="testimonial__role">%s</span>',
					$role
				);
			}

			$attribution_block = sprintf(
				'<figcaption class="testimonial__attribution">%s<div class="testimonial__attribution-text">%s%s</div></figcaption>',
				$avatar_block,
				$author_html,
				$role_html
			);
		}

		$slides_html .= sprintf(
			'<li
				class="testimonial__slide%1$s"
				role="group"
				aria-roledescription="%2$s"
				aria-label="%3$s"
				aria-hidden="%4$s"
				id="%5$s"
			>
				<span class="testimonial__quote-mark" aria-hidden="true">%6$s</span>
				<div class="testimonial__container">
					<blockquote class="testimonial__quote">
						<p>%7$s</p>
					</blockquote>
					<hr class="testimonial__divider" aria-hidden="true">
					%8$s
				</div>
			</li>',
			$is_first ? '' : ' testimonial__slide--hidden',                                 // 1: hidden class on non-first slides
			esc_attr__( 'slide', 'kim-branzell' ),                                          // 2: aria-roledescription
			esc_attr( $aria_label ),                                                        // 3: aria-label
			$is_first ? 'false' : 'true',                                                   // 4: aria-hidden
			esc_attr( $slide_id ),                                                          // 5: id
			'"',                                                                            // 6: decorative double-quote glyph
			$quote,                                                                         // 7: quote text (esc_html'd)
			$attribution_block                                                              // 8: figcaption
		);
	}

	// ─── Build dots (tablist) ────────────────────────────────────

	$dots_html = '';

	foreach ( $testimonials as $index => $testimonial ) {
		$dot_index = $index + 1;
		$selected  = ( 0 === $index ) ? 'true' : 'false';
		$dot_id    = wp_unique_id( 'testimonial-dot-' );
		$dot_label = sprintf(
			/* translators: 1: Slide number, 2: Total slides */
			__( 'Go to testimonial %1$d of %2$d', 'kim-branzell' ),
			$dot_index,
			$total
		);

		$dots_html .= sprintf(
			'<button
				class="testimonial__dot"
				role="tab"
				id="%1$s"
				aria-selected="%2$s"
				aria-label="%3$s"
				data-index="%4$d"
				type="button"
			></button>',
			esc_attr( $dot_id ),       // 1
			$selected,                 // 2
			esc_attr( $dot_label ),    // 3
			$dot_index                 // 4
		);
	}

	// ─── Prev / Next buttons ────────────────────────────────────

	$prev_button = sprintf(
		'<button class="testimonial__prev" type="button" aria-label="%1$s">%2$s</button>',
		esc_attr__( 'Previous testimonial', 'kim-branzell' ),
		$prev_icon
	);

	$next_button = sprintf(
		'<button class="testimonial__next" type="button" aria-label="%1$s">%2$s</button>',
		esc_attr__( 'Next testimonial', 'kim-branzell' ),
		$next_icon
	);

	// ─── Render via template ────────────────────────────────────

	ob_start();
	include __DIR__ . '/template.php';
	return ob_get_clean();
}
