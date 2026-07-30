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

	$eyebrow             = $attributes['eyebrow'] ?? __( 'Recent engagements', 'kim-branzell' );
	$heading             = $attributes['heading'] ?? __( 'Project index', 'kim-branzell' );
	$heading_id          = wp_unique_id( 'project-index-heading-' );
	$show_count          = (bool) ( $attributes['showCount'] ?? true );
	$show_column_labels  = (bool) ( $attributes['showColumnLabels'] ?? true );
	$posts_to_show       = (int) ( $attributes['postsToShow'] ?? 0 );
	$column_labels       = array(
		'title'  => __( 'Projekt', 'kim-branzell' ),
		'sector' => __( 'Sektor', 'kim-branzell' ),
		'tags'   => __( 'Typ av utveckling', 'kim-branzell' ),
		'year'   => __( 'År', 'kim-branzell' ),
	);

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

	$internal_svg = '<svg class="project-index__link-icon" aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg"><path d="M7 17 17 7"/><path d="M7 7h10v10"/></svg>';
	$external_svg = '<svg class="project-index__link-icon" aria-label="' . esc_attr__( 'external link', 'kim-branzell' ) . '" role="img" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>';

	// Build row list items — one per published project.
	$rows_html = '';
	foreach ( $projects as $project ) {
		$title_raw         = get_the_title( $project );
		$sector_raw        = get_post_meta( $project->ID, 'kb_project_sector', true );
		$tags_raw          = get_post_meta( $project->ID, 'kb_project_tags', true );
		$year              = absint( get_post_meta( $project->ID, 'kb_project_year', true ) );
		if ( 0 === $year ) {
			$year = (int) get_the_date( 'Y', $project );
		}
		$external_url_raw = trim( (string) get_post_meta( $project->ID, 'kb_project_external_url', true ) );
		$external_url     = esc_url( $external_url_raw );
		$permalink        = esc_url( get_permalink( $project ) );

		$is_external = ! empty( $external_url );
		$href        = $is_external ? $external_url : $permalink;
		$target_attr = $is_external ? ' target="_blank" rel="noopener noreferrer"' : '';
		$icon_html   = ' ' . ( $is_external ? $external_svg : $internal_svg );

		$rows_html .= sprintf(
			'<li class="project-index__item" data-sort-title="%1$s" data-sort-sector="%2$s" data-sort-tags="%3$s" data-sort-year="%4$d">' .
				'<a class="project-index__link" href="%5$s"%6$s>' .
				'<span class="project-index__title">%7$s</span>' .
				'<span class="project-index__sector">%8$s</span>' .
				'<span class="project-index__tags">%9$s</span>' .
				'<span class="project-index__year">%10$s%11$s</span>' .
			'</a></li>',
			esc_attr( $title_raw ),
			esc_attr( $sector_raw ),
			esc_attr( $tags_raw ),
			$year,
			$href,
			$target_attr,
			esc_html( $title_raw ),
			esc_html( $sector_raw ),
			esc_html( $tags_raw ),
			esc_html( (string) $year ),
			$icon_html
		);
	}

	ob_start();
	include __DIR__ . '/template.php';
	return ob_get_clean();
}
