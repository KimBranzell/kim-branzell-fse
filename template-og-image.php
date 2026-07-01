<?php
/**
 * Template: OG Image Generator.
 *
 * Outputs a 1200x630 PNG Open Graph image.
 * Called directly via query var: ?og_image=1&post_id=N[&v=N]
 *
 * No header/footer HTML — image only.
 *
 * @package Kim_Branzell
 */

// Bootstrap WordPress (minimal)
require_once ABSPATH . 'wp-load.php';

if ( ! isset( $_GET['og_image'] ) || $_GET['og_image'] !== '1' ) {
	status_header( 404 );
	exit;
}

$post_id = isset( $_GET['post_id'] ) ? (int) $_GET['post_id'] : 0;

if ( ! $post_id ) {
	status_header( 400 );
	echo 'Missing post_id';
	exit;
}

// Allow CORS for og:image (some scrapers fetch directly)
header( 'Access-Control-Allow-Origin: *' );
header( 'Content-Type: image/png' );
header( 'Cache-Control: public, max-age=86400, s-maxage=604800' );

// Include required files
require_once get_theme_file_path( 'inc/functions/user-portrait.php' );
require_once get_theme_file_path( 'inc/functions/og-image.php' );

// Check if cached file exists
$version = isset( $_GET['v'] ) ? (int) $_GET['v'] : kb_og_get_version( $post_id );
$cached  = kb_og_cache_path( $post_id, $version );

if ( file_exists( $cached ) ) {
	$fp = fopen( $cached, 'rb' );
	if ( $fp ) {
		fpassthru( $fp );
		fclose( $fp );
		exit;
	}
}

// Render and output
$result = kb_og_render( $post_id );

if ( ! file_exists( $result['path'] ) ) {
	status_header( 500 );
	echo 'Failed to generate image';
	exit;
}

$fp = fopen( $result['path'], 'rb' );
if ( $fp ) {
	fpassthru( $fp );
	fclose( $fp );
} else {
	status_header( 500 );
	echo 'Failed to read image';
	exit;
}
