<?php
/**
 * Project Index block – render callback.
 *
 * Renders an editorial index list of published projects, each
 * row linking to its single-project permalink. Total published
 * project count is rendered as `(NN)` independent of postsToShow.
 *
 * @param array  $attributes Block attributes.
 * @param string $content    Block content (empty).
 *
 * @package Kim_Branzell
 */
function kb_render_block_project_index( array $attributes, string $content = '' ): string {
	if ( ! (bool) ( $attributes['enabled'] ?? true ) ) {
		return '';
	}

	$eyebrow       = esc_html( $attributes['eyebrow'] ?? __( 'Recent engagements', 'kim-branzell' ) );
	$heading       = esc_html( $attributes['heading'] ?? __( 'Project index', 'kim-branzell' ) );
	$heading_id    = wp_unique_id( 'project-index-heading-' );
	$show_count    = (bool) ( $attributes['showCount'] ?? true );
	$posts_to_show = (int) ( $attributes['postsToShow'] ?? 0 );

	$projects = get_posts(
		array(
			'post_type'      => 'project',
			'post_status'    => 'publish',
			'posts_per_page' => $posts_to_show > 0 ? $posts_to_show : -1,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			),
			'no_found_rows'  => true,
		)
	);

	if ( empty( $projects ) ) {
		return '';
	}

	$count_obj   = wp_count_posts( 'project' );
	$count_label = $count_obj ? sprintf( '(%02d)', (int) $count_obj->publish ) : '(00)';

	$arrow_svg = '<svg class="project-index__arrow" aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg"><path d="M7 17 17 7"/><path d="M7 7h10v10"/></svg>';

	// Build row list items — one per published project.
	$rows_html = '';
	foreach ( $projects as $project ) {
		$title  = esc_html( get_the_title( $project ) );
		$sector = esc_html( get_post_meta( $project->ID, 'kb_project_sector', true ) );
		$tags   = esc_html( get_post_meta( $project->ID, 'kb_project_tags', true ) );
		$year   = absint( get_post_meta( $project->ID, 'kb_project_year', true ) );
		if ( 0 === $year ) {
			$year = (int) get_the_date( 'Y', $project );
		}
		$permalink = esc_url( get_permalink( $project ) );

		$rows_html .= sprintf(
			'<li class="project-index__item"><a class="project-index__link" href="%1$s">' .
				'<span class="project-index__title">%2$s</span>' .
				'<span class="project-index__sector">%3$s</span>' .
				'<span class="project-index__tags">%4$s</span>' .
				'<span class="project-index__year">%5$s%6$s</span>' .
			'</a></li>',
			$permalink,
			$title,
			$sector,
			$tags,
			esc_html( (string) $year ),
			$arrow_svg
		);
	}

	ob_start();
	include __DIR__ . '/template.php';
	return ob_get_clean();
}
