<?php
/**
 * Project Index block – template markup.
 *
 * Variables passed from render.php:
 * @var string $eyebrow              Escaped eyebrow label.
 * @var string $heading              Escaped section heading.
 * @var string $heading_id           Unique id for aria-labelledby.
 * @var bool   $show_count           Whether to render the total count.
 * @var string $count_label          Zero-padded "(NN)" count label.
 * @var bool   $show_column_labels   Whether to render column header buttons.
 * @var array  $column_labels        Assoc array of translatable column headers.
 * @var string $rows_html            Escaped <li><a> rows markup.
 */
?>
<section class="project-index" aria-labelledby="<?php echo esc_attr( $heading_id ); ?>">
	<div class="project-index__inner">
		<div class="project-index__header">
			<div class="project-index__header-text">
				<p class="project-index__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
				<h2 class="project-index__heading" id="<?php echo esc_attr( $heading_id ); ?>"><?php echo esc_html( $heading ); ?></h2>
			</div>
			<?php if ( $show_count ) : ?>
			<span class="project-index__count"><?php echo esc_html( $count_label ); ?></span>
			<?php endif; ?>
		</div>
		<?php if ( $show_column_labels ) : ?>
		<div class="project-index__header-row">
			<div class="project-index__header-cell project-index__header-cell--title">
				<button type="button" class="project-index__sort-btn" data-sort-key="title" aria-sort="none">
					<?php echo esc_html( $column_labels['title'] ); ?>
					<span class="project-index__sort-indicator" aria-hidden="true"></span>
				</button>
			</div>
			<div class="project-index__header-cell project-index__header-cell--sector">
				<button type="button" class="project-index__sort-btn" data-sort-key="sector" aria-sort="none">
					<?php echo esc_html( $column_labels['sector'] ); ?>
					<span class="project-index__sort-indicator" aria-hidden="true"></span>
				</button>
			</div>
			<div class="project-index__header-cell project-index__header-cell--tags">
				<button type="button" class="project-index__sort-btn" data-sort-key="tags" aria-sort="none">
					<?php echo esc_html( $column_labels['tags'] ); ?>
					<span class="project-index__sort-indicator" aria-hidden="true"></span>
				</button>
			</div>
			<div class="project-index__header-cell project-index__header-cell--year">
				<button type="button" class="project-index__sort-btn" data-sort-key="year" aria-sort="none">
					<?php echo esc_html( $column_labels['year'] ); ?>
					<span class="project-index__sort-indicator" aria-hidden="true"></span>
				</button>
			</div>
		</div>
		<?php endif; ?>
		<ul class="project-index__list" role="list">
			<?php echo $rows_html; ?>
		</ul>
		<div class="project-index__sort-announcer screen-reader-text" aria-live="polite" aria-atomic="true"></div>
	</div>
</section>