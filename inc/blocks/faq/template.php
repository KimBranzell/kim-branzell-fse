<?php
/**
 * FAQ Section block – template markup.
 *
 * @package Kim_Branzell
 */
?>
<div class="kb-faq-section">
	<div class="kb-faq-section__inner">
		<div class="kb-faq-section__left">
			<h2 class="kb-faq-section__title">
				<?php echo $section_title; ?>
			</h2>
			<?php if ( $section_subtitle ) : ?>
				<p class="kb-faq-section__subtitle"><?php echo $section_subtitle; ?></p>
			<?php endif; ?>
			<?php echo $cta_html; ?>
		</div>
		<div class="kb-faq-section__right">
			<div class="kb-faq-list">
				<?php echo $faq_items_html; ?>
			</div>
		</div>
	</div>
</div>
