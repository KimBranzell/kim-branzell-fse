<?php
/**
 * The template for displaying search forms.
 *
 * @package Kim_Branzell
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'Site search', 'kim-branzell' ); ?>">
	<label for="search-field" class="search-form__label screen-reader-text">
		<?php esc_html_e( 'Search for:', 'kim-branzell' ); ?>
	</label>
	<input
		type="search"
		id="search-field"
		class="search-form__input"
		placeholder="<?php esc_attr_e( 'Search …', 'kim-branzell' ); ?>"
		value="<?php echo get_search_query(); ?>"
		name="s"
		autocomplete="search"
	/>
	<button type="submit" class="search-form__submit">
		<span class="search-form__submit-text"><?php esc_html_e( 'Search', 'kim-branzell' ); ?></span>
		<svg class="search-form__submit-icon" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
			<circle cx="11" cy="11" r="8"/>
			<line x1="21" y1="21" x2="16.65" y2="16.65"/>
		</svg>
	</button>
</form>
