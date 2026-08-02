<?php
/**
 * FAQ Section block – render callback.
 *
 * @param array  $attributes Block attributes.
 * @param string $content    Block content (empty).
 *
 * @package Kim_Branzell
 */
function kb_render_block_faq_section( array $attributes, string $content = '' ): string {
	$section_title    = esc_html( $attributes['sectionTitle'] ?? __( 'Answers to Most Common Questions', 'kim-branzell' ) );
	$section_subtitle = esc_html( $attributes['sectionSubtitle'] ?? '' );
	$selected_faqs    = $attributes['selectedFaqs'] ?? array();
	$cta_text         = esc_html( $attributes['ctaText'] ?? __( "Can't find your answer?", 'kim-branzell' ) );
	$cta_button_text  = esc_html( $attributes['ctaButtonText'] ?? __( 'Get in Touch', 'kim-branzell' ) );
	$cta_button_url   = esc_url( $attributes['ctaButtonUrl'] ?? '#' );

	// Return early if no FAQs selected.
	if ( empty( $selected_faqs ) ) {
		return '';
	}

	// Fetch the actual FAQ posts in the selected order.
	$faq_ids = array_column( $selected_faqs, 'id' );

	$faq_posts = get_posts(
		array(
			'post_type'        => 'faq',
			'post__in'         => $faq_ids,
			'posts_per_page'   => count( $faq_ids ),
			'orderby'          => 'post__in',
			'suppress_filters' => false,
		)
	);

	// If posts not found via post__in (might be empty), fallback to menu_order.
	if ( empty( $faq_posts ) ) {
		$faq_posts = get_posts(
			array(
				'post_type'      => 'faq',
				'posts_per_page' => -1,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
				'post_status'    => 'publish',
			)
		);

		// Filter to only selected IDs if we have them.
		if ( ! empty( $faq_ids ) ) {
			$faq_posts = array_filter(
				$faq_posts,
				fn( $post ) => in_array( (string) $post->ID, $faq_ids, true )
			);
			// Preserve the order from selected_faqs.
			$ordered_posts = array();
			foreach ( $faq_ids as $id ) {
				foreach ( $faq_posts as $post ) {
					if ( (string) $post->ID === (string) $id ) {
						$ordered_posts[] = $post;
						break;
					}
				}
			}
			$faq_posts = $ordered_posts;
		}
	}

	// Build FAQ items HTML.
	$faq_items_html = '';
	foreach ( $faq_posts as $index => $post ) {
		$post_id       = $post->ID;
		$question      = esc_html( $post->post_title );
		$answer        = wp_kses_post( apply_filters( 'the_content', $post->post_content ) );
		$is_expanded   = 0 === $index; // First item expanded by default.
		$expanded_attr = $is_expanded ? 'true' : 'false';

		$faq_items_html .= sprintf(
			'<div class="kb-faq-item"%s data-expanded="%s">
				<button
					class="kb-faq-item__question"
					aria-expanded="%s"
					aria-controls="kb-faq-answer-%d"
					id="kb-faq-question-%d"
				>
					<span class="kb-faq-item__question-text">%s</span>
					<span class="kb-faq-item__icon" aria-hidden="true">
						<span class="kb-faq-item__icon-plus"></span>
						<span class="kb-faq-item__icon-minus"></span>
					</span>
				</button>
				<div
					class="kb-faq-item__answer"
					id="kb-faq-answer-%d"
					role="region"
					aria-labelledby="kb-faq-question-%d"
					%s
				>
					<div class="kb-faq-item__answer-inner">
						%s
					</div>
				</div>
			</div>',
			$is_expanded ? ' aria-expanded="true"' : '',
			$is_expanded ? 'true' : 'false',
			$expanded_attr,
			$post_id,
			$post_id,
			$question,
			$post_id,
			$post_id,
			$is_expanded ? '' : 'hidden',
			$answer
		);
	}

	// Build CTA HTML.
	$cta_html = sprintf(
		'<div class="kb-faq-cta">
			<p class="kb-faq-cta__text">%s</p>
			<a href="%s" class="kb-faq-cta__button" aria-label="%s">
				<span>%s</span>
				<svg aria-hidden="true" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M3.33337 8H12.6667M12.6667 8L8.66671 4M12.6667 8L8.66671 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</a>
		</div>',
		$cta_text,
		$cta_button_url,
		esc_attr__( 'Get in touch for more help', 'kim-branzell' ),
		$cta_button_text
	);

	ob_start();
	include __DIR__ . '/template.php';
	return ob_get_clean();
}
