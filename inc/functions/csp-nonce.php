<?php
/**
 * CSP Nonce — per-request nonce for Content-Security-Policy.
 *
 * Generates a single cryptographically random nonce per HTTP request,
 * exposes it to the CSP header, and injects it into WordPress-generated
 * inline <style> and <script> tags so strict style-src/script-src
 * policies work without 'unsafe-inline'.
 *
 * @since 0.2.0
 */

/**
 * Retrieve (and cache) the CSP nonce for the current request.
 *
 * @return string 32-character hex nonce.
 */
function kb_get_csp_nonce(): string {
	static $nonce = null;

	if ( null === $nonce ) {
		$nonce = bin2hex( random_bytes( 16 ) );
	}

	return $nonce;
}

/**
 * Inject the CSP nonce into a <script> tag's HTML.
 *
 * @param string $tag    The <script> tag HTML.
 * @param string $handle The script handle.
 * @return string Modified HTML.
 */
function kb_script_loader_nonce( string $tag, string $handle ): string {
	$nonce = kb_get_csp_nonce();

	return str_replace(
		'<script ',
		'<script nonce="' . esc_attr( $nonce ) . '" ',
		$tag
	);
}

/**
 * Inject nonces into all <style> tags in a buffer of HTML.
 *
 * Called by PHP's output buffer callback — processes the entire
 * page once at the end of the request.
 *
 * @param string $html Raw HTML buffer.
 * @return string Modified HTML.
 */
function kb_inject_style_nonces( string $html ): string {
	// Skip non-HTML responses (AJAX JSON, REST API, etc.).
	if ( ! str_contains( $html, '<style' ) ) {
		return $html;
	}

	$nonce = kb_get_csp_nonce();

	return (string) preg_replace(
		'/<style\b/',
		'<style nonce="' . esc_attr( $nonce ) . '"',
		$html
	);
}

// ─────────────────────────────────────────────────────────────
// Hooks — front-end and AJAX only (skip admin)
// ─────────────────────────────────────────────────────────────

if ( ! is_admin() || wp_doing_ajax() ) {
	// Scripts: script_loader_tag still fires for both inline and external scripts.
	add_filter( 'script_loader_tag', 'kb_script_loader_nonce', 10, 2 );

	// Styles: WP 7.0 bypasses style_loader_tag for inline <style> tags,
	// generating them via WP_HTML_Tag_Processor with no filter. The only
	// reliable approach is to buffer the entire page and inject nonces
	// into every <style> tag at the end of the request.
	add_action( 'template_redirect', function () {
		ob_start( 'kb_inject_style_nonces' );
	} );
}
