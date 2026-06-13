<?php
function kb_enqueue_frontend_assets() : void {
	$manifest = kb_get_manifest();
	$entry = $manifest['assets/src/js/app.js'] ?? null;

	if ( ! $entry ) {
		return;
	}

	kb_enqueue_entry_styles( $entry, 'kb-app' );

	wp_enqueue_script(
		'kb-app',
		KB_DIST_URI . '/' . $entry['file'],
		array(),
		kb_asset_version( $entry['file'] ),
		true
	);
}

function kb_enqueue_editor_assets() : void {
	$manifest = kb_get_manifest();
	$entry = $manifest['assets/src/js/editor.js'] ?? null;

	if ( ! $entry ) {
		return;
	}

	kb_enqueue_entry_styles( $entry, 'kb-editor' );

	wp_enqueue_script(
		'kb-editor',
		KB_DIST_URI . '/' . $entry['file'],
		array( 'wp-dom-ready' ),
		kb_asset_version( $entry['file'] ),
		true
	);
}

function kb_enqueue_entry_styles( array $entry, string $handle_prefix ) : void {
	if ( empty( $entry['css'] ) || ! is_array( $entry['css'] ) ) {
		return;
	}

	foreach ( $entry['css'] as $index => $css_file ) {
		wp_enqueue_style(
			$handle_prefix . '-style-' . $index,
			KB_DIST_URI . '/' . $css_file,
			array(),
			kb_asset_version( $css_file )
		);
	}
}