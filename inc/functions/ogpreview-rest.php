<?php
/**
 * OG Preview REST API + Admin Preview Meta Box.
 *
 * REST endpoint: GET/POST /wp-json/kb/v1/ogpreview/{post_id}
 * Admin preview panel in block editor sidebar.
 *
 * @package Kim_Branzell
 */

/**
 * Register REST routes.
 */
function kb_og_preview_register_rest() {
	register_rest_route( 'kb/v1', '/ogpreview/(?P<post_id>\d+)', array(
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'           => 'kb_og_preview_get',
			'permission_callback' => 'kb_og_preview_permission',
			'args'                => array(
				'post_id' => array(
					'validate_callback' => function( $param ) {
						return is_numeric( $param );
					},
				),
			),
		),
		array(
			'methods'             => WP_REST_Server::EDITABLE,
			'callback'           => 'kb_og_preview_regenerate',
			'permission_callback' => 'kb_og_preview_permission',
			'args'                => array(
				'post_id' => array(
					'validate_callback' => function( $param ) {
						return is_numeric( $param );
					},
				),
			),
		),
	) );
}
add_action( 'rest_api_init', 'kb_og_preview_register_rest' );

/**
 * Check admin permission.
 *
 * @param WP_REST_Request $request Request object.
 * @return bool
 */
function kb_og_preview_permission( $request ) {
	return current_user_can( 'manage_options' );
}

/**
 * GET handler — return OG preview data.
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response|WP_Error
 */
function kb_og_preview_get( $request ) {
	$post_id = (int) $request->get_param( 'post_id' );
	$post    = get_post( $post_id );

	if ( ! $post ) {
		return new WP_Error( 'not_found', 'Post not found', array( 'status' => 404 ) );
	}

	$version     = kb_og_get_version( $post_id );
	$version_url = isset( $_GET['v'] ) ? (int) $_GET['v'] : $version;
	$image_url   = home_url( "/?og_image=1&post_id={$post_id}&v={$version_url}" );
	$author_id   = (int) $post->post_author;
	$portrait    = kb_get_portrait_data( $author_id );

	// Check if image is already cached
	$cache_path  = kb_og_cache_path( $post_id, $version_url );
	$is_cached   = file_exists( $cache_path );

	return new WP_REST_Response( array(
		'post_id'         => $post_id,
		'post_type'       => $post->post_type,
		'image_url'       => $image_url,
		'version'         => $version_url,
		'is_cached'       => $is_cached,
		'portrait_source' => $portrait['source'],
		'portrait_url'    => $portrait['url'],
	), 200 );
}

/**
 * POST handler — regenerate OG image (bump version + async render).
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response|WP_Error
 */
function kb_og_preview_regenerate( $request ) {
	$post_id = (int) $request->get_param( 'post_id' );
	$post    = get_post( $post_id );

	if ( ! $post ) {
		return new WP_Error( 'not_found', 'Post not found', array( 'status' => 404 ) );
	}

	$new_version = kb_og_bump_version( $post_id );
	$image_url   = home_url( "/?og_image=1&post_id={$post_id}&v={$new_version}" );

	return new WP_REST_Response( array(
		'post_id'    => $post_id,
		'image_url'  => $image_url,
		'version'    => $new_version,
		'message'    => 'OG image regenerating in background.',
	), 200 );
}

// ─────────────────────────────────────────────────────────────
// Admin Preview Meta Box
// ─────────────────────────────────────────────────────────────

/**
 * Add OG Preview meta box to post edit screens.
 */
function kb_og_preview_add_meta_box() {
	$screens = apply_filters( 'kb_og_post_types', array( 'post', 'page' ) );
	foreach ( $screens as $screen ) {
		add_meta_box(
			'kb_og_preview',
			__( 'Social Preview', 'kim-branzell' ),
			'kb_og_preview_meta_box_callback',
			$screen,
			'side',
			'high'
		);
	}
}
add_action( 'add_meta_boxes', 'kb_og_preview_add_meta_box' );

/**
 * Render the OG Preview meta box.
 *
 * @param WP_Post $post Post object.
 */
function kb_og_preview_meta_box_callback( $post ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	wp_nonce_field( 'kb_og_preview_nonce', 'kb_og_preview_nonce_field' );

	$post_id   = $post->ID;
	$version   = kb_og_get_version( $post_id );
	$image_url = home_url( "/?og_image=1&post_id={$post_id}&v={$version}" );
	$author_id = (int) $post->post_author;
	$portrait  = kb_get_portrait_data( $author_id );

	?>
	<div id="kb-ogpreview-root">
		<div class="kb-ogpreview-loading" style="text-align:center;padding:20px 0;">
			<span class="spinner is-active" style="float:none;margin:0 auto;"></span>
		</div>
	</div>

	<script>
	window.KB_OG_PREVIEW = {
		postId: <?php echo (int) $post_id; ?>,
		restBase: '<?php echo rest_url( 'kb/v1' ); ?>',
		nonce: '<?php echo wp_create_nonce( 'wp_rest' ); ?>',
		initialVersion: <?php echo (int) $version; ?>,
		initialImageUrl: '<?php echo esc_url( $image_url ); ?>',
		portraitUrl: <?php echo $portrait['url'] ? json_encode( $portrait['url'] ) : 'null'; ?>,
		portraitSource: <?php echo json_encode( $portrait['source'] ); ?>,
		postType: '<?php echo esc_js( $post->post_type ); ?>'
	};
	</script>

	<style>
	#kb-ogpreview-root {
		display: flex;
		flex-direction: column;
		gap: 10px;
	}

	.kb-og-image-wrap {
		position: relative;
		border: 1px solid #ddd;
		border-radius: 4px;
		overflow: hidden;
		background: #f0f0f0;
	}

	.kb-og-image-wrap img {
		display: block;
		width: 100%;
		height: auto;
	}

	.kb-og-badge {
		display: inline-flex;
		align-items: center;
		padding: 3px 8px;
		background: #0c6f67;
		color: #fff;
		font-size: 11px;
		font-weight: 600;
		border-radius: 3px;
		vertical-align: middle;
	}

	.kb-og-badge--cached {
		background: #2e7d32;
	}

	.kb-og-badge--uncached {
		background: #ef6c00;
	}

	.kb-og-meta {
		display: flex;
		align-items: center;
		justify-content: space-between;
		font-size: 12px;
		color: #666;
	}

	.kb-og-source {
		display: flex;
		align-items: center;
		gap: 6px;
		font-size: 11px;
		color: #888;
	}

	.kb-og-actions {
		display: flex;
		gap: 8px;
		margin-top: 4px;
	}

	.kb-og-actions .button {
		flex: 1;
	}

	.kb-og-error {
		padding: 12px;
		background: #fef2f2;
		border: 1px solid #fca5a5;
		border-radius: 4px;
		color: #dc2626;
		font-size: 12px;
	}

	.kb-og-initials {
		display: flex;
		align-items: center;
		justify-content: center;
		width: 100%;
		height: 165px;
		background: #e5eff1;
		color: #0c6f67;
		font-size: 48px;
		font-weight: 700;
		font-family: 'Atkinson Hyperlegible Next', sans-serif;
	}
	</style>
	<?php
}

/**
 * Enqueue OG preview admin assets.
 *
 * @param string $hook_suffix Admin page hook.
 */
function kb_og_preview_admin_enqueue( $hook_suffix ) {
	if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	wp_enqueue_script(
		'kb-ogpreview',
		get_theme_file_uri( 'assets/dist/js/ogpreview.js' ),
		array( 'wp-api', 'wp-element', 'wp-components', 'wp-i18n' ),
		defined( 'KB_VERSION' ) ? KB_VERSION : '1.0',
		true
	);
}
add_action( 'admin_enqueue_scripts', 'kb_og_preview_admin_enqueue' );
