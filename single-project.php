<?php
/**
 * The template for displaying single project posts.
 *
 * @package Kim_Branzell
 */

get_header();
?>

<main id="main-content">
	<div class="main__inner">
		<?php while ( have_posts() ) : the_post(); ?>
			<?php
			$project_year   = absint( get_post_meta( get_the_ID(), 'kb_project_year', true ) );
			$project_year   = $project_year ? $project_year : (int) get_the_date( 'Y' );
			$project_sector = get_post_meta( get_the_ID(), 'kb_project_sector', true );
			$project_tags   = get_post_meta( get_the_ID(), 'kb_project_tags', true );
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'project-single' ); ?>>
				<header class="project-single__header">
					<h1 class="project-single__title"><?php echo esc_html( get_the_title() ); ?></h1>
					<dl class="project-single__meta">
						<div class="project-single__meta-item">
							<dt><?php esc_html_e( 'Year', 'kim-branzell' ); ?></dt>
							<dd><time datetime="<?php echo esc_attr( $project_year ); ?>"><?php echo esc_html( $project_year ); ?></time></dd>
						</div>
						<?php if ( ! empty( $project_sector ) ) : ?>
							<div class="project-single__meta-item">
								<dt><?php esc_html_e( 'Sector', 'kim-branzell' ); ?></dt>
								<dd><?php echo esc_html( $project_sector ); ?></dd>
							</div>
						<?php endif; ?>
						<?php if ( ! empty( $project_tags ) ) : ?>
							<div class="project-single__meta-item">
								<dt><?php esc_html_e( 'Tags', 'kim-branzell' ); ?></dt>
								<dd><?php echo esc_html( $project_tags ); ?></dd>
							</div>
						<?php endif; ?>
					</dl>
				</header>
				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="project-single__media">
						<?php the_post_thumbnail( 'large', array( 'class' => 'project-single__image' ) ); ?>
					</figure>
				<?php endif; ?>
				<div class="project-single__content">
					<?php the_content(); ?>
				</div>
				<footer class="project-single__footer">
					<?php
					the_post_navigation( array(
						'prev_text'          => '<span aria-hidden="true">←</span> %title',
						'next_text'          => '%title <span aria-hidden="true">→</span>',
						'screen_reader_text' => __( 'Project navigation', 'kim-branzell' ),
						'aria_label'         => __( 'Project navigation', 'kim-branzell' ),
					) );
					?>
				</footer>
			</article>
		<?php endwhile; ?>
	</div>
</main>
<?php
get_footer();
