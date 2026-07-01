<?php
/**
 * Hero block – template.
 *
 * Variables passed from render.php:
 * @var string $title
 * @var int    $count
 * @var string $size
 * @var string $image_html  Full <img> or wp_get_attachment_image() output.
 */
?>
<div class="hero hero--<?php echo $size; ?>">
	<div class="hero__inner">
		<!-- <div class="hero__eyebrow">
			<?php if ( $image_html ) : ?>
				<div class="hero__image-wrapper">
					<?php echo wp_kses_post( $image_html ); ?>
				</div>
			<?php endif; ?>
			<div class="hero__name-title">
				<span class="name">Kim Branzell</span>
				<span class="title">Backend Developer</span>
			</div>
		</div> -->
		<div class="hero__text-container">
			<h2><?php echo $title; ?></h2>
			<div class="hero__lower-text-container">
				<p> I build resilient digital ecosystems that are thoughtful, fast, and welcoming to everyone. My approach to software engineering prioritizes technical precision, robust accessibility, and a minimal carbon footprint. I also have a soft spot for sustainable solutions and good coffee. </p>
				<div class="hero__cta-buttons">
					<a href="#">View projects</a>
					<a href="#">Read manifesto</a>
				</div>
			</div>
		</div>
	</div>
</div>



