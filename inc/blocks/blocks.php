<?php
add_action(
	'init',
	__NAMESPACE__ . '\register_blocks'
);

function register_blocks() {

	// ──────────────────────────────────────────────
	// Hero block
	// ──────────────────────────────────────────────
	require_once __DIR__ . '/hero/render.php';

	wp_register_script(
		'kb-hero-editor',
		KB_DIST_URI . '/js/hero.js',
		array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-element', 'wp-server-side-render' ),
		kb_asset_version( 'js/hero.js' ),
		true
	);

	register_block_type_from_metadata(
		__DIR__ . '/hero/block.json',
		array(
			'render_callback' => 'kb_render_block_hero',
		)
	);

	// ──────────────────────────────────────────────
	// Intro block
	// ──────────────────────────────────────────────
	require_once __DIR__ . '/intro/render.php';

	wp_register_script(
		'kb-intro-editor',
		KB_DIST_URI . '/js/intro.js',
		array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-element', 'wp-server-side-render' ),
		kb_asset_version( 'js/intro.js' ),
		true
	);

	register_block_type_from_metadata(
		__DIR__ . '/intro/block.json',
		array(
			'render_callback' => 'kb_render_block_intro',
		)
	);

	// ──────────────────────────────────────────────
	// FAQ Section block
	// ──────────────────────────────────────────────
	require_once __DIR__ . '/faq/render.php';

	wp_register_script(
		'kb-faq-editor',
		KB_DIST_URI . '/js/faq.js',
		array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-element', 'wp-data', 'wp-server-side-render' ),
		kb_asset_version( 'js/faq.js' ),
		true
	);

	register_block_type_from_metadata(
		__DIR__ . '/faq/block.json',
		array(
			'render_callback' => 'kb_render_block_faq_section',
		)
	);

	// ──────────────────────────────────────────────
	// About Me block
	// ──────────────────────────────────────────────
	require_once __DIR__ . '/about/render.php';

	wp_register_script(
		'kb-about-editor',
		KB_DIST_URI . '/js/about.js',
		array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-element', 'wp-server-side-render' ),
		kb_asset_version( 'js/about.js' ),
		true
	);

	register_block_type_from_metadata(
		__DIR__ . '/about/block.json',
		array(
			'render_callback' => 'kb_render_block_about',
		)
	);

	// ──────────────────────────────────────────────
	// Project Index block
	// ──────────────────────────────────────────────
	require_once __DIR__ . '/project-index/render.php';

	wp_register_script(
		'kb-project-index-editor',
		KB_DIST_URI . '/js/project-index.js',
		array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-element', 'wp-server-side-render' ),
		kb_asset_version( 'js/project-index.js' ),
		true
	);

	wp_register_script(
		'kb-project-index-view',
		KB_DIST_URI . '/js/project-index-view.js',
		array(),
		kb_asset_version( 'js/project-index-view.js' ),
		true
	);

	register_block_type_from_metadata(
		__DIR__ . '/project-index/block.json',
		array(
			'render_callback' => 'kb_render_block_project_index',
		)
	);

	// ──────────────────────────────────────────────
	// Blog Posts block
	// ──────────────────────────────────────────────
	require_once __DIR__ . '/blog-posts/render.php';

	wp_register_script(
		'kb-blog-posts-editor',
		KB_DIST_URI . '/js/blog-posts.js',
		array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-element', 'wp-server-side-render' ),
		kb_asset_version( 'js/blog-posts.js' ),
		true
	);

	wp_register_script(
		'kb-blog-posts-view',
		KB_DIST_URI . '/js/blog-posts-view.js',
		array(),
		kb_asset_version( 'js/blog-posts-view.js' ),
		true
	);

	register_block_type_from_metadata(
		__DIR__ . '/blog-posts/block.json',
		array(
			'render_callback' => 'kb_render_block_blog_posts',
		)
	);

	// ──────────────────────────────────────────────
	// Testimonial block
	// ──────────────────────────────────────────────
	require_once __DIR__ . '/testimonial/render.php';

	wp_register_script(
		'kb-testimonial-editor',
		KB_DIST_URI . '/js/testimonial.js',
		array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-element', 'wp-server-side-render' ),
		kb_asset_version( 'js/testimonial.js' ),
		true
	);

	wp_register_script(
		'kb-testimonial-view',
		KB_DIST_URI . '/js/testimonial-view.js',
		array(),
		kb_asset_version( 'js/testimonial-view.js' ),
		true
	);

	register_block_type_from_metadata(
		__DIR__ . '/testimonial/block.json',
		array(
			'render_callback' => 'kb_render_block_testimonial',
		)
	);
}
