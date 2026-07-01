<?php

require_once get_theme_file_path( 'inc/functions/cleanup.php' );

define( 'KB_VERSION', '0.1.1' );
define( 'KB_DIST_PATH', get_theme_file_path( 'assets/dist' ) );
define( 'KB_DIST_URI', get_theme_file_uri( 'assets/dist' ) );

add_action( 'after_setup_theme', 'kb_after_setup_theme' );
add_action( 'enqueue_block_assets', 'kb_enqueue_frontend_assets' );
add_action( 'enqueue_block_assets', 'kb_enqueue_mobilemenu_assets');
add_action( 'enqueue_block_editor_assets', 'kb_enqueue_editor_assets' );
add_filter( 'should_load_separate_core_block_assets', '__return_true' );

function kb_after_setup_theme() : void {
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'title-tag' );
	add_editor_style( 'assets/dist/css/editor.css' );

	register_nav_menus( array(
			'primary' => __( 'Primary Menu', 'text_domain' ),
			'footer_menu'  => __( 'Footer Menu', 'text_domain' ),
	) );

}

/**
 * Filter the document title for search results.
 *
 * Provides screen-reader context: "Search: <query> — <site name>"
 * instead of just the site name alone.
 *
 * @param array $parts The document title parts.
 * @return array Modified title parts.
 */
function kb_search_document_title( array $parts ) : array {
	if ( is_search() ) {
		$parts['title'] = sprintf(
			/* translators: %s: Search query. */
			_x( 'Search: %s', 'document title', 'kim-branzell' ),
			get_search_query( false )
		);
	}
	return $parts;
}
add_filter( 'document_title_parts', 'kb_search_document_title' );

// Unregister block-library styles to prevent loading on the frontend.
add_action( 'wp_enqueue_scripts', function() {
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-theme' );
} );

// Unregister block-supports styles to prevent loading on the frontend.
add_action( 'enqueue_block_assets', function() {
	wp_dequeue_style( 'wp-block-supports' );
} );

require_once get_theme_file_path( 'inc/functions/enqueues.php' );
require_once get_theme_file_path( 'inc/functions/filters.php' );
require_once get_theme_file_path( 'inc/functions/site-menu.php' );
require_once get_theme_file_path( 'inc/functions/cpt.php' );
require_once get_theme_file_path( 'inc/blocks/blocks.php' );
require_once get_theme_file_path( 'inc/functions/user-portrait.php' );
require_once get_theme_file_path( 'inc/functions/og-image.php' );
require_once get_theme_file_path( 'inc/functions/ogpreview-rest.php' );

function kb_get_manifest() : array {
	static $manifest = null;

	if ( null !== $manifest ) {
		return $manifest;
	}

	$manifest_path = KB_DIST_PATH . '/manifest.json';

	if ( ! file_exists( $manifest_path ) ) {
		return $manifest = array();
	}

	$data = json_decode( (string) file_get_contents( $manifest_path ), true );

	return $manifest = is_array( $data ) ? $data : array();
}

function kb_asset_version( string $relative_path ) : string {
	$file = KB_DIST_PATH . '/' . ltrim( $relative_path, '/' );
	return file_exists( $file ) ? (string) filemtime( $file ) : KB_VERSION;
}

// ─────────────────────────────────────────────────────────────
// OG Image Query Var + Template Redirect
// ─────────────────────────────────────────────────────────────

/**
 * Register the og_image query var.
 *
 * @param array $vars Query vars.
 * @return array
 */
function kb_register_og_image_query_var( array $vars ) : array {
	$vars[] = 'og_image';
	return $vars;
}
add_filter( 'query_vars', 'kb_register_og_image_query_var' );

/**
 * Redirect to the OG image template when ?og_image=1.
 */
function kb_og_image_template_redirect() {
	global $wp_query;

	if ( ! isset( $wp_query->query_vars['og_image'] ) || $wp_query->query_vars['og_image'] !== '1' ) {
		return;
	}

	// Don't show if post_id is missing
	if ( ! isset( $_GET['post_id'] ) || empty( $_GET['post_id'] ) ) {
		status_header( 400 );
		echo 'Missing post_id';
		exit;
	}

	// Serve template-og-image.php
	$template = locate_template( 'template-og-image.php' );
	if ( $template ) {
		// Prevent WP from loading header/footer
		remove_all_actions( 'wp_head' );
		remove_all_actions( 'wp_footer' );
		status_header( 200 );
		include $template;
		exit;
	}
}
add_action( 'template_redirect', 'kb_og_image_template_redirect', 0 );

// ─────────────────────────────────────────────────────────────
// Rank Math OG Image Integration
// ─────────────────────────────────────────────────────────────

/**
 * Override Rank Math Facebook OG image.
 *
 * @param string $url     Existing image URL.
 * @param Post   $post    Post object.
 * @return string
 */
function kb_rankmath_facebook_og_image( $url, $post ) {
	if ( ! $post || ! $post instanceof WP_Post ) {
		return $url;
	}
	$version = kb_og_get_version( $post->ID );
	return home_url( "/?og_image=1&post_id={$post->ID}&v={$version}" );
}
add_filter( 'rank_math/facebook/image', 'kb_rankmath_facebook_og_image', 10, 2 );

/**
 * Override Rank Math Twitter OG image.
 *
 * @param string $url     Existing image URL.
 * @param Post   $post    Post object.
 * @return string
 */
function kb_rankmath_twitter_og_image( $url, $post ) {
	if ( ! $post || ! $post instanceof WP_Post ) {
		return $url;
	}
	$version = kb_og_get_version( $post->ID );
	return home_url( "/?og_image=1&post_id={$post->ID}&v={$version}" );
}
add_filter( 'rank_math/twitter/image', 'kb_rankmath_twitter_og_image', 10, 2 );
