<?php
/**
 * Project Index block – template markup.
 *
 * Variables passed from render.php:
 * @var string $eyebrow       Escaped eyebrow label.
 * @var string $heading       Escaped section heading.
 * @var string $heading_id    Unique id for aria-labelledby.
 * @var bool   $show_count    Whether to render the total count.
 * @var string $count_label   Zero-padded "(NN)" count label.
 * @var string $rows_html     Escaped <li><a> rows markup.
 */
?>
<section class="project-index" aria-labelledby="<?php echo esc_attr( $heading_id ); ?>">
	<div class="project-index__inner">
		<div class="project-index__header">
			<div class="project-index__header-text">
				<p class="project-index__eyebrow"><?php echo $eyebrow; ?></p>
				<h2 class="project-index__heading" id="<?php echo esc_attr( $heading_id ); ?>"><?php echo $heading; ?></h2>
			</div>
			<?php if ( $show_count ) : ?>
			<span class="project-index__count"><?php echo $count_label; ?></span>
			<?php endif; ?>
		</div>
		<ul class="project-index__list" role="list">
			<?php echo $rows_html; ?>
		</ul>
	</div>
</section>