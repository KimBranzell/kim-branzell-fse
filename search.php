<?php
/**
 * The template for displaying search results pages.
 *
 * @package Kim_Branzell
 */

get_header();
?>

<main id="main-content">
	<div class="main__inner">
		<?php if ( have_posts() ) : ?>

			<header class="search-header">
				<h1 class="search-header__title">
					<?php
					printf(
						/* translators: %s: Search query. */
						esc_html__( 'Search results for: %s', 'kim-branzell' ),
						'<span class="search-header__query">' . esc_html( get_search_query( false ) ) . '</span>'
					);
					?>
				</h1>
				<div role="status" class="search-header__status">
					<p class="search-header__count">
						<?php
						$count = (int) $wp_query->found_posts;
						printf(
							/* translators: %s: Number of search results. */
							esc_html( _n( '%s result found', '%s results found', $count, 'kim-branzell' ) ),
							'<span class="search-header__count-number">' . number_format_i18n( $count ) . '</span>'
						);
						?>
					</p>
				</div>
			</header>

			<div class="search-results">
				<?php while ( have_posts() ) : the_post(); ?>

					<article id="post-<?php the_ID(); ?>" <?php post_class( 'search-result' ); ?>>
						<h2 class="search-result__title">
							<a href="<?php the_permalink(); ?>">
								<?php the_title(); ?>
							</a>
						</h2>

						<div class="search-result__excerpt">
							<?php the_excerpt(); ?>
						</div>

						<div class="search-result__meta">
							<span class="search-result__type">
								<?php
								$post_type_obj = get_post_type_object( get_post_type() );
								echo esc_html( $post_type_obj ? $post_type_obj->labels->singular_name : get_post_type() );
								?>
							</span>
							<?php if ( get_the_date() ) : ?>
								<time class="search-result__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
									<?php echo esc_html( get_the_date() ); ?>
								</time>
							<?php endif; ?>
						</div>
					</article>

				<?php endwhile; ?>
			</div>

			<?php
			the_posts_pagination(
				array(
					'mid_size'           => 2,
					'prev_text'          => __( 'Previous', 'kim-branzell' ),
					'next_text'          => __( 'Next', 'kim-branzell' ),
					'screen_reader_text' => __( 'Search results navigation', 'kim-branzell' ),
				)
			);
			?>

		<?php else : ?>

			<header class="search-header">
				<h1 class="search-header__title">
					<?php
					printf(
						/* translators: %s: Search query. */
						esc_html__( 'No results for: %s', 'kim-branzell' ),
						'<span class="search-header__query">' . esc_html( get_search_query( false ) ) . '</span>'
					);
					?>
				</h1>
				<div role="status" class="search-header__status">
					<p class="search-header__count">
						<?php esc_html_e( 'No results found', 'kim-branzell' ); ?>
					</p>
				</div>
			</header>

			<div class="search-no-results">
				<p class="search-no-results__message">
					<?php esc_html_e( 'Sorry, no pages or posts matched your search. Please try again with different keywords.', 'kim-branzell' ); ?>
				</p>
				<?php get_search_form(); ?>
			</div>

		<?php endif; ?>
	</div>
</main>

<?php
get_footer();
