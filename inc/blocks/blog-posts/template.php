<?php
/**
 * Blog Posts block – template markup.
 *
 * Variables passed from render.php:
 *
 * @var string $eyebrow         Escaped eyebrow label.
 * @var string $heading         Escaped section heading.
 * @var string $heading_id      Unique id for aria-labelledby.
 * @var bool   $show_count      Whether to render the total count.
 * @var string $count_label     Zero-padded "(NN)" count label.
 * @var string $chips_html      Fully-escaped filter chip buttons, or empty string.
 * @var string $lead_html       Fully-escaped lead card markup, or empty string.
 * @var string $rows_html       Escaped <li><a> rows markup.
 * @var bool   $show_view_all  Whether to show the "View all posts" link.
 * @var string $view_all_url    Escaped URL for the posts archive.
 * @var string $view_all_label  Escaped label for the link.
 *
 * @package Kim_Branzell
 */

$view_all_arrow = '<svg class="blog-posts__view-all-icon" aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>';

?>

<section class="blog-posts" aria-labelledby="<?php echo esc_attr( $heading_id ); ?>">
	<div class="blog-posts__inner">
		<div class="blog-posts__header">
			<div class="blog-posts__header-text">
				<p class="blog-posts__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
				<h2 class="blog-posts__heading" id="<?php echo esc_attr( $heading_id ); ?>"><?php echo esc_html( $heading ); ?></h2>
			</div>
			<?php if ( $show_count ) : ?>
			<span class="blog-posts__count"><?php echo esc_html( $count_label ); ?></span>
			<?php endif; ?>
		</div>

		<?php if ( ! empty( $chips_html ) ) : ?>
		<div class="blog-posts__filter" role="group" aria-label="<?php echo esc_attr__( 'Filter posts by category', 'kim-branzell' ); ?>">
			<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built pre-escaped in render.php. ?>
			<?php echo $chips_html; ?>
		</div>
		<?php endif; ?>

		<?php if ( ! empty( $lead_html ) ) : ?>
		<div class="blog-posts__lead">
			<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built pre-escaped in render.php. ?>
			<?php echo $lead_html; ?>
		</div>
		<?php endif; ?>

		<?php if ( ! empty( $rows_html ) ) : ?>
		<ul class="blog-posts__list" role="list">
			<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built pre-escaped in render.php. ?>
			<?php echo $rows_html; ?>
		</ul>
		<?php endif; ?>

		<p class="blog-posts__empty" aria-hidden="true"><?php esc_html_e( 'No posts in this category.', 'kim-branzell' ); ?></p>

		<?php if ( $show_view_all ) : ?>
		<div class="blog-posts__view-all">
			<a href="<?php echo esc_url( $view_all_url ); ?>" class="blog-posts__view-all-link">
				<?php echo esc_html( $view_all_label ); ?>
				<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG built inline with no user input. ?>
				<?php echo $view_all_arrow; ?>
			</a>
		</div>
		<?php endif; ?>

		<div class="blog-posts__filter-announcer screen-reader-text" aria-live="polite" aria-atomic="true"></div>
	</div>
</section>
