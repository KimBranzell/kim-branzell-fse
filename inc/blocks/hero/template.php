<?php
/**
 * Hero block – template.
 *
 * Variables passed from render.php:
 * @var string $title
 * @var string $intro
 * @var int    $count
 * @var string $size
 * @var string $image_html  Full <img> or wp_get_attachment_image() output.
 * @var string $ctas_html  Built CTA buttons HTML.
 */
?>
<div class="hero hero--<?php echo $size; ?>">
	<div class="hero__inner">
		<div class="image-holder"><?php if($image_html): ?><?php echo $image_html; ?><div class="image-gradient"></div><?php endif; ?></div>
		<div class="hero__text-container">
			<h2><?php echo $title; ?></h2>
			<div class="hero__lower-text-container">
				<?php if ( $intro ) : ?>
				<div class="hero__text">
					<p><?php echo $intro; ?></p>
				</div>
				<?php endif; ?>
				<?php if ( $ctas_html ) : ?>
				<div class="hero__cta-buttons">
					<?php echo $ctas_html; ?>
				</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
