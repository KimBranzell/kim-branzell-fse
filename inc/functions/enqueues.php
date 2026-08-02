<?php
function kb_enqueue_frontend_assets(): void {
	$manifest = kb_get_manifest();
	$entry    = $manifest['assets/src/js/app.js'] ?? null;

	if ( ! $entry ) {
		return;
	}

	kb_enqueue_entry_scripts( $entry, 'kb-frontend' );
	kb_enqueue_entry_styles( $entry, 'kb-frontend' );
}

function kb_enqueue_mobilemenu_assets(): void {
	$manifest = kb_get_manifest();
	$entry    = $manifest['assets/src/js/mobilemenu.js'] ?? null;

	if ( ! $entry ) {
		return;
	}

	kb_enqueue_entry_scripts( $entry, 'kb-mobilemenu' );
}

function kb_enqueue_editor_assets(): void {
	$manifest = kb_get_manifest();
	$entry    = $manifest['assets/src/js/editor.js'] ?? null;

	if ( ! $entry ) {
		return;
	}

	kb_enqueue_entry_styles( $entry, 'kb-editor' );
	kb_enqueue_entry_scripts( $entry, 'kb-editor' );
}

function kb_enqueue_entry_styles( array $entry, string $handle_prefix ): void {
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

function kb_enqueue_entry_scripts( array $entry, string $handle_prefix ): void {
	$js_files = array();

	if ( ! empty( $entry['js'] ) && is_array( $entry['js'] ) ) {
		$js_files = $entry['js'];
	} elseif ( ! empty( $entry['file'] ) ) {
		$js_files = array( $entry['file'] );
	}

	if ( empty( $js_files ) ) {
		return;
	}

	foreach ( $js_files as $index => $js_file ) {
		wp_enqueue_script(
			$handle_prefix . '-script-' . $index,
			KB_DIST_URI . '/' . $js_file,
			array(),
			kb_asset_version( $js_file ),
			true
		);
	}
}
