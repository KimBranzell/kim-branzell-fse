<?php

require_once get_theme_file_path( 'inc/functions/cleanup.php' );

define( 'KB_VERSION', '0.1.0' );
define( 'KB_DIST_PATH', get_theme_file_path( 'assets/dist' ) );
define( 'KB_DIST_URI', get_theme_file_uri( 'assets/dist' ) );

add_action( 'after_setup_theme', 'kb_after_setup_theme' );
add_action( 'enqueue_block_assets', 'kb_enqueue_frontend_assets' );
add_action( 'enqueue_block_editor_assets', 'kb_enqueue_editor_assets' );
add_filter( 'should_load_separate_core_block_assets', '__return_true' );

function kb_after_setup_theme() : void {
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'responsive-embeds' );
	add_editor_style( 'assets/dist/css/editor.css' );
}

require_once get_theme_file_path( 'inc/functions/enqueues.php' );
require_once get_theme_file_path( 'inc/functions/filters.php' );

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
