<?php
/**
 * Blog Posts block – render callback.
 *
 * Renders a two-zone editorial index: lead cards (sticky + recent
 * with featured images, excerpts, author avatars) followed by
 * editorial rows (thumbnail, title, excerpt, meta, arrow).
 * Posts are filterable by category with client-side view.js.
 *
 * @param array  $attributes Block attributes.
 * @param string $_content   Block content (unused).
 *
 * @package Kim_Branzell
 */
function kb_render_block_blog_posts( array $attributes, string $_content = '' ): string {
	if ( ! (bool) ( $attributes['enabled'] ?? true ) ) {
		return '';
	}

	$eyebrow            = $attributes['eyebrow'] ?? __( 'From the journal', 'kim-branzell' );
	$heading            = $attributes['heading'] ?? __( 'Latest writing', 'kim-branzell' );
	$heading_id         = wp_unique_id( 'blog-posts-heading-' );
	$show_count         = (bool) ( $attributes['showCount'] ?? false );
	$show_date          = (bool) ( $attributes['showDate'] ?? true );
	$show_reading_time  = (bool) ( $attributes['showReadingTime'] ?? true );
	$show_category      = (bool) ( $attributes['showCategory'] ?? true );
	$posts_to_show      = (int) ( $attributes['postsToShow'] ?? 6 );
	$show_author        = (bool) ( $attributes['showAuthor'] ?? true );
	$show_author_avatar = (bool) ( $attributes['showAuthorAvatar'] ?? true );
	$show_comments      = (bool) ( $attributes['showComments'] ?? true );
	$show_excerpt       = (bool) ( $attributes['showExcerpt'] ?? true );
	$show_read_more     = (bool) ( $attributes['showReadMore'] ?? true );
	$show_thumbnail     = (bool) ( $attributes['showThumbnail'] ?? true );
	$show_view_all      = (bool) ( $attributes['showViewAll'] ?? true );
	$excerpt_length     = max( 8, min( (int) ( $attributes['excerptLength'] ?? 28 ), 80 ) );
	$lead_count         = max( 0, min( (int) ( $attributes['leadCount'] ?? 3 ), 10 ) );
	$lead_image_size    = (string) ( $attributes['leadImageSize'] ?? 'large' );

	$posts = get_posts(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => $posts_to_show > 0 ? $posts_to_show : -1,
			'orderby'             => 'date',
			'order'               => 'DESC',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => false,
			'has_password'        => false,
		)
	);

	if ( empty( $posts ) ) {
		return '';
	}

	$count_obj   = wp_count_posts( 'post' );
	$count_label = $count_obj ? sprintf( '(%02d)', (int) $count_obj->publish ) : '(00)';

	// ─── Avatar helper (avoids CSP-blocked Gravatar URLs) ─────
	$kb_blog_get_avatar = function ( int $user_id ) use ( $show_author_avatar ): array {
		if ( ! $show_author_avatar ) {
			return array( 'type' => 'none' );
		}

		$display_name = get_the_author_meta( 'display_name', $user_id );
		$initials     = mb_strtoupper( mb_substr( $display_name, 0, 2 ) );

		if ( function_exists( 'get_field' ) ) {
			$acf = get_field( 'kb_profile_image', 'user_' . $user_id );
			if ( is_array( $acf ) && ! empty( $acf['ID'] ) ) {
				// Same-origin ACF attachment — safe under strict CSP.
				return array(
					'type' => 'image',
					'html' => wp_get_attachment_image(
						(int) $acf['ID'],
						array( 64, 64 ),
						false,
						array(
							'class'   => 'blog-posts__avatar',
							'loading' => 'lazy',
						)
					),
				);
			}
		}

		// No ACF portrait — initials placeholder (safe; no Gravatar URL).
		return array(
			'type'     => 'initials',
			'initials' => esc_html( $initials ),
		);
	};

	// ─── Partition posts into lead and list zones ─────────────
	$raw_sticky = get_option( 'sticky_posts', array() );
	$sticky_ids = array_map( 'intval', $raw_sticky ? $raw_sticky : array() );
	$sticky_set = array_flip( $sticky_ids );

	$lead_posts = array();
	$list_posts = array();

	// First pass: collect sticky posts (in the order get_posts returned them).
	$non_sticky_buffer = array();
	foreach ( $posts as $post ) {
		if ( isset( $sticky_set[ $post->ID ] ) && count( $lead_posts ) < $lead_count ) {
			$lead_posts[] = $post;
		} else {
			$non_sticky_buffer[] = $post;
		}
	}

	// Fill remaining lead slots with recent non-sticky posts.
	$remaining = $lead_count - count( $lead_posts );
	if ( $remaining > 0 ) {
		$lead_posts        = array_merge( $lead_posts, array_slice( $non_sticky_buffer, 0, $remaining ) );
		$non_sticky_buffer = array_slice( $non_sticky_buffer, $remaining );
	}
	$list_posts = $non_sticky_buffer;

	$arrow_svg = '<svg class="blog-posts__link-icon" aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg"><path d="M7 17 17 7"/><path d="M7 7h10v10"/></svg>';

	// ─── Build categories chip data ────────────────────────────
	$distinct_categories = array(); // Map of slug => name.
	$post_category_map   = array(); // Map of post_id => array of slugs.

	foreach ( $posts as $post ) {
		$cats  = get_the_category( $post->ID );
		$slugs = array();
		foreach ( $cats as $cat ) {
			$slug                         = $cat->slug;
			$slugs[]                      = $slug;
			$distinct_categories[ $slug ] = $cat->name;
		}
		$post_category_map[ $post->ID ] = $slugs;
	}

	// Sort alphabetically by name.
	asort( $distinct_categories );

	// ─── Build chip HTML ───────────────────────────────────────
	$chips_html = '';
	if ( count( $distinct_categories ) > 1 ) {
		// "All" chip — always first.
		$chips_html .= '<button type="button" class="blog-posts__filter-chip" data-filter="all" aria-pressed="true">'
			. esc_html__( 'All', 'kim-branzell' )
			. '</button>';

		foreach ( $distinct_categories as $slug => $name ) {
			$chips_html .= sprintf(
				'<button type="button" class="blog-posts__filter-chip" data-filter="%s" aria-pressed="false">%s</button>',
				esc_attr( $slug ),
				esc_html( $name )
			);
		}
	}

	// ─── Read-more arrow SVG (for lead cards) ──────────────────
	$read_more_arrow = '<svg class="blog-posts__card-readmore-icon" aria-hidden="true" focusable="false" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>';

	// ─── Pinned badge SVG ──────────────────────────────────────
	$pinned_svg = '<svg class="blog-posts__pinned-icon" aria-hidden="true" focusable="false" width="12" height="12" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M16.5 2.25a.75.75 0 0 1 .75.75v5.25H21a.75.75 0 0 1 0 1.5h-5.25V21a.75.75 0 0 1-1.5 0V9.75H9a.75.75 0 0 1 0-1.5h5.25V3a.75.75 0 0 1 .75-.75Z" transform="rotate(45 12 12)"/></svg>';

	// ─── Build lead card markup ─────────────────────────────────
	$lead_html  = '';
	$lead_index = 0;

	foreach ( $lead_posts as $post ) {
		$title_raw   = get_the_title( $post );
		$permalink   = esc_url( get_permalink( $post ) );
		$cat_slugs   = $post_category_map[ $post->ID ] ?? array();
		$data_cats   = esc_attr( implode( ' ', $cat_slugs ) );
		$is_sticky   = isset( $sticky_set[ $post->ID ] );
		$author_id   = (int) $post->post_author;
		$author_name = esc_html( get_the_author_meta( 'display_name', $author_id ) );

		// ─── Image ──────────────────────────────────────────────
		$image_html = '';
		if ( $show_thumbnail && has_post_thumbnail( $post ) ) {
			$image_html = get_the_post_thumbnail(
				$post,
				$lead_image_size,
				array(
					'class'    => 'blog-posts__card-img',
					'loading'  => 'lazy',
					'decoding' => 'async',
				)
			);
		}

		if ( '' === $image_html ) {
			$image_html = '<div class="blog-posts__placeholder blog-posts__placeholder--lead" aria-hidden="true"><span>&#x26A1;</span></div>';
		}

		// ─── Excerpt ────────────────────────────────────────────
		$excerpt_html = '';
		if ( $show_excerpt ) {
			if ( ! empty( $post->post_excerpt ) ) {
				$excerpt_text = esc_html( wp_strip_all_tags( $post->post_excerpt ) );
			} else {
				$excerpt_text = esc_html( wp_trim_words( wp_strip_all_tags( $post->post_content ), $excerpt_length ) );
			}
			$excerpt_html = sprintf(
				'<p class="blog-posts__card-excerpt">%s</p>',
				$excerpt_text
			);
		}

		// ─── Category label ─────────────────────────────────────
		$cat_html = '';
		if ( $show_category && ! empty( $cat_slugs ) ) {
			$first_slug = $cat_slugs[0];
			$cat_name   = $distinct_categories[ $first_slug ] ?? '';
			if ( '' !== $cat_name ) {
				$cat_html = sprintf(
					'<p class="blog-posts__card-cat">%s</p>',
					esc_html( $cat_name )
				);
			}
		}

		// ─── Meta line ──────────────────────────────────────────
		$meta_parts = array();

		if ( $show_date ) {
			$meta_parts[] = sprintf(
				'<time class="blog-posts__meta-date" datetime="%s">%s</time>',
				esc_attr( get_the_date( 'c', $post ) ),
				esc_html( get_the_date( 'j M Y', $post ) )
			);
		}

		if ( $show_reading_time ) {
			$word_count   = str_word_count( wp_strip_all_tags( $post->post_content ) );
			$reading_time = max( 1, (int) round( $word_count / 200 ) );
			/* translators: %d: number of minutes to read. */
			$label        = sprintf( _n( '%d min read', '%d min read', $reading_time, 'kim-branzell' ), $reading_time );
			$meta_parts[] = sprintf(
				'<span class="blog-posts__meta-reading" aria-label="%s">%s</span>',
				esc_attr( $label ),
				esc_html( $label )
			);
		}

		if ( $show_comments ) {
			$comment_count = (int) get_comments_number( $post );
			if ( $comment_count > 0 ) {
				/* translators: %d: number of comments. */
				$label        = sprintf( _n( '%d comment', '%d comments', $comment_count, 'kim-branzell' ), $comment_count );
				$meta_parts[] = sprintf(
					'<span class="blog-posts__meta-comments" aria-label="%s">%s</span>',
					esc_attr( $label ),
					esc_html( $label )
				);
			}
		}

		$meta_html = '';
		if ( ! empty( $meta_parts ) ) {
			$sep       = ' <span class="blog-posts__meta-sep" aria-hidden="true">·</span> ';
			$meta_html = '<span class="blog-posts__card-meta">' . implode( $sep, $meta_parts ) . '</span>';
		}

		// ─── Author row ─────────────────────────────────────────
		$author_html = '';
		if ( $show_author ) {
			$avatar_info = $kb_blog_get_avatar( $author_id );

			$avatar_html = '';
			if ( 'image' === $avatar_info['type'] ) {
				$avatar_html = $avatar_info['html'];
			} elseif ( 'initials' === $avatar_info['type'] ) {
				$avatar_html = sprintf(
					'<span class="blog-posts__avatar blog-posts__avatar--initials" aria-hidden="true">%s</span>',
					$avatar_info['initials']
				);
			}

			$author_html = sprintf(
				'<div class="blog-posts__card-author">%s<span class="blog-posts__card-author-name">%s</span></div>',
				$avatar_html,
				$author_name
			);
		}

		// ─── Read more ──────────────────────────────────────────
		$read_more_html = '';
		if ( $show_read_more ) {
			$read_more_html = sprintf(
				'<span class="blog-posts__card-readmore">%s%s</span>',
				esc_html__( 'Read post', 'kim-branzell' ),
				$read_more_arrow
			);
		}

		// ─── Assemble card ──────────────────────────────────────
		$lead_html .= sprintf(
			'<article class="blog-posts__card" data-category="%1$s" style="--i: %2$d">' .
				'<a class="blog-posts__card-link" href="%3$s">' .
					'<div class="blog-posts__card-media">' .
			'%4$s' . // Image or placeholder.
			'%5$s' . // Pinned badge or empty.
			'</div>' .
			'<div class="blog-posts__card-body">' .
			'%6$s' . // Category.
			'<h3 class="blog-posts__card-title">%7$s</h3>' .
			'%8$s' . // Excerpt.
			'%9$s' . // Meta (date, reading, comments).
			'%10$s' . // Author row.
			'%11$s' . // Read more.
					'</div>' .
				'</a>' .
			'</article>',
			$data_cats,                                                       // %1
			$lead_index,                                                       // %2
			$permalink,                                                        // %3
			$image_html,                                                       // %4
			$is_sticky ? '<span class="blog-posts__pinned" aria-label="' . esc_attr__( 'Pinned post', 'kim-branzell' ) . '">' . $pinned_svg . '</span>' : '',  // %5
			$cat_html,                                                         // %6
			esc_html( $title_raw ),                                            // %7
			$excerpt_html,                                                     // %8
			$meta_html,                                                        // %9
			$author_html,                                                      // %10
			$read_more_html                                                    // %11
		);

		++$lead_index;
	}

	// ─── Build list row items ───────────────────────────────────
	$rows_html = '';
	$row_index = 0;

	foreach ( $list_posts as $post ) {
		$title_raw  = get_the_title( $post );
		$permalink  = esc_url( get_permalink( $post ) );
		$iso_date   = esc_attr( get_the_date( 'c', $post ) );
		$human_date = esc_html( get_the_date( 'j M Y', $post ) );
		$cat_slugs  = $post_category_map[ $post->ID ] ?? array();
		$data_cats  = esc_attr( implode( ' ', $cat_slugs ) );

		// Reading time: ceil(word_count / 200), minimum 1 minute.
		$word_count   = str_word_count( wp_strip_all_tags( $post->post_content ) );
		$reading_time = max( 1, (int) ceil( $word_count / 200 ) );

		// Category label — use first category name, or nothing.
		$category_name = '';
		if ( $show_category && ! empty( $cat_slugs ) ) {
			$first_slug    = $cat_slugs[0];
			$category_name = $distinct_categories[ $first_slug ] ?? '';
			$category_name = esc_html( $category_name );
		}

		// ─── Thumbnail ──────────────────────────────────────────
		$thumb_html = '';
		if ( $show_thumbnail && has_post_thumbnail( $post ) ) {
			$thumb_html = get_the_post_thumbnail(
				$post,
				'thumbnail',
				array(
					'class'    => 'blog-posts__thumb',
					'loading'  => 'lazy',
					'decoding' => 'async',
				)
			);
		}
		if ( '' === $thumb_html ) {
			// Empty spacer to keep grid alignment when no thumbnail.
			$thumb_html = '<span class="blog-posts__thumb blog-posts__thumb--empty" aria-hidden="true"></span>';
		}

		// ─── Row excerpt (shorter, one-line) ────────────────────
		$row_excerpt_html = '';
		if ( $show_excerpt ) {
			if ( ! empty( $post->post_excerpt ) ) {
				$row_excerpt_text = esc_html( wp_strip_all_tags( $post->post_excerpt ) );
			} else {
				$row_trim_length  = max( 8, min( (int) $excerpt_length, 20 ) );
				$row_excerpt_text = esc_html( wp_trim_words( wp_strip_all_tags( $post->post_content ), $row_trim_length ) );
			}
			$row_excerpt_html = sprintf(
				'<p class="blog-posts__excerpt">%s</p>',
				$row_excerpt_text
			);
		}

		// ─── Meta line parts ────────────────────────────────────
		$meta_parts = array();

		if ( $show_author ) {
			$meta_parts[] = sprintf(
				'<span class="blog-posts__meta-author">%s</span>',
				esc_html( get_the_author_meta( 'display_name', $post->post_author ) )
			);
		}

		if ( $show_category && '' !== $category_name ) {
			$meta_parts[] = sprintf(
				'<span class="blog-posts__category">%s</span>',
				$category_name
			);
		}

		if ( $show_reading_time ) {
			/* translators: %d: number of minutes to read. */
			$label        = sprintf( _n( '%d min read', '%d min read', $reading_time, 'kim-branzell' ), $reading_time );
			$meta_parts[] = sprintf(
				'<span class="blog-posts__reading-time" aria-label="%s">%s</span>',
				esc_attr( $label ),
				esc_html( $label )
			);
		}

		if ( $show_comments ) {
			$comment_count = (int) get_comments_number( $post );
			if ( $comment_count > 0 ) {
				/* translators: %d: number of comments. */
				$label        = sprintf( _n( '%d comment', '%d comments', $comment_count, 'kim-branzell' ), $comment_count );
				$meta_parts[] = sprintf(
					'<span class="blog-posts__meta-comments" aria-label="%s">%s</span>',
					esc_attr( $label ),
					esc_html( $label )
				);
			}
		}

		$meta_html = '';
		if ( ! empty( $meta_parts ) ) {
			$meta_html = '<span class="blog-posts__meta">' . implode( ' <span class="blog-posts__meta-sep" aria-hidden="true">·</span> ', $meta_parts ) . '</span>';
		}

		// ─── Date cell ──────────────────────────────────────────
		$date_html = '';
		if ( $show_date ) {
			$date_html = sprintf(
				'<time class="blog-posts__date" datetime="%s">%s</time>',
				$iso_date,
				$human_date
			);
		}

		// Wrap title + excerpt + meta into one column so the grid stays aligned.
		$title_block = sprintf(
			'<span class="blog-posts__title-wrap"><h3 class="blog-posts__title">%1$s</h3>%2$s%3$s</span>',
			esc_html( $title_raw ),
			$row_excerpt_html,
			$meta_html
		);

		// ─── Row (4 grid children: thumb | date | title-block | arrow) ──
		$rows_html .= sprintf(
			'<li class="blog-posts__item" data-category="%1$s" style="--i: %2$d">' .
				'<a class="blog-posts__link" href="%3$s">' .
				'%4$s' . // Thumbnail or spacer.
				'%5$s' . // Date.
				'%6$s' . // Title + excerpt + meta.
				'%7$s' . // Arrow.
			'</a></li>',
			$data_cats,
			$row_index,
			$permalink,
			$thumb_html,
			$date_html,
			$title_block,
			$arrow_svg
		);

		++$row_index;
	}

	// ─── View all posts link ────────────────────────────────────
	$posts_page_id = (int) get_option( 'page_for_posts' );
	$show_view_all = $show_view_all
		&& $posts_page_id > 0
		&& (int) $count_obj->publish > count( $posts );

	$view_all_url   = '';
	$view_all_label = '';
	if ( $show_view_all ) {
		$view_all_url   = esc_url( get_permalink( $posts_page_id ) );
		$view_all_label = esc_html__( 'View all posts', 'kim-branzell' );
	}

	ob_start();
	include __DIR__ . '/template.php';
	return ob_get_clean();
}
