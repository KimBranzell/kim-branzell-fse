<?php
/**
 * Footer render helper.
 *
 * Builds fully-escaped footer HTML (mirrors the block render.php convention:
 * all escaping happens here at construction time; the template echoes
 * pre-escaped strings). Contains the platform→SVG map for social icons
 * (inline SVG, 24×24, fill="currentColor", aria-hidden).
 *
 * The orphaned sprite kb_add_svg_defs() in filters.php is intentionally
 * NOT reused — its defs use inconsistent coordinate spaces (RSS ~500k‑scale,
 * others 0–512) and nothing references them. All footer icons here are
 * self-contained inline SVGs with a uniform 24×24 viewBox, matching the
 * established pattern from project-index/render.php and blog-posts/render.php.
 *
 * @package Kim_Branzell
 */

// ─────────────────────────────────────────────────────────────
// 1. Social platform SVG icons
// ─────────────────────────────────────────────────────────────

/**
 * Return an inline SVG icon for a social platform.
 *
 * Paths sourced from Simple Icons (https://simpleicons.org),
 * all 24×24 viewBox, fill="currentColor".
 *
 * @param string $platform Platform slug (linkedin, instagram, github, x, …).
 * @return string Inline SVG or empty string for unknown platforms.
 */
function kb_footer_social_svg( string $platform ): string {
	$paths = array(
		'linkedin'  => 'M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z',
		'instagram' => 'M7.0301.084c-1.2768.0602-2.1487.264-2.911.5634-.7888.3075-1.4575.72-2.1228 1.3877-.6652.6677-1.075 1.3368-1.3802 2.127-.2954.7638-.4956 1.6365-.552 2.914-.0564 1.2775-.0689 1.6882-.0626 4.947.0062 3.2586.0206 3.6671.0825 4.9473.061 1.2765.264 2.1482.5635 2.9107.308.7889.72 1.4573 1.388 2.1228.6679.6655 1.3365 1.0743 2.1285 1.38.7632.295 1.6361.4961 2.9134.552 1.2773.056 1.6884.069 4.9462.0627 3.2578-.0062 3.668-.0207 4.9478-.0814 1.28-.0607 2.147-.2652 2.9098-.5633.7889-.3086 1.4578-.72 2.1228-1.3881.665-.6682 1.0745-1.3378 1.3795-2.1284.2957-.7632.4966-1.636.552-2.9124.056-1.2809.0692-1.6898.063-4.948-.0063-3.2583-.021-3.6668-.0817-4.9465-.0607-1.2797-.264-2.1487-.5633-2.9117-.3084-.7889-.72-1.4568-1.3876-2.1228C21.2982 1.33 20.628.9208 19.8378.6165 19.074.321 18.2017.1197 16.9244.0645 15.6471.0093 15.236-.005 11.977.0014 8.718.0076 8.31.0215 7.0301.0839m.1402 21.6932c-1.17-.0509-1.8053-.2453-2.2287-.408-.5606-.216-.96-.4771-1.3819-.895-.422-.4178-.6811-.8186-.9-1.378-.1644-.4234-.3624-1.058-.4171-2.228-.0595-1.2645-.072-1.6442-.079-4.848-.007-3.2037.0053-3.583.0607-4.848.05-1.169.2456-1.805.408-2.2282.216-.5613.4762-.96.895-1.3816.4188-.4217.8184-.6814 1.3783-.9003.423-.1651 1.0575-.3614 2.227-.4171 1.2655-.06 1.6447-.072 4.848-.079 3.2033-.007 3.5835.005 4.8495.0608 1.169.0508 1.8053.2445 2.228.408.5608.216.96.4754 1.3816.895.4217.4194.6816.8176.9005 1.3787.1653.4217.3617 1.056.4169 2.2263.0602 1.2655.0739 1.645.0796 4.848.0058 3.203-.0055 3.5834-.061 4.848-.051 1.17-.245 1.8055-.408 2.2294-.216.5604-.4763.96-.8954 1.3814-.419.4215-.8181.6811-1.3783.9-.4224.1649-1.0577.3617-2.2262.4174-1.2656.0595-1.6448.072-4.8493.079-3.2045.007-3.5825-.006-4.848-.0608M16.953 5.5864A1.44 1.44 0 1 0 18.39 4.144a1.44 1.44 0 0 0-1.437 1.4424M5.8385 12.012c.0067 3.4032 2.7706 6.1557 6.173 6.1493 3.4026-.0065 6.157-2.7701 6.1506-6.1733-.0065-3.4032-2.771-6.1565-6.174-6.1498-3.403.0067-6.156 2.771-6.1496 6.1738M8 12.0077a4 4 0 1 1 4.008 3.9921A3.9996 3.9996 0 0 1 8 12.0077',
		'github'    => 'M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12',
		'x'         => 'M14.234 10.162 22.977 0h-2.072l-7.591 8.824L7.251 0H.258l9.168 13.343L.258 24H2.33l8.016-9.318L16.749 24h6.993zm-2.837 3.299-.929-1.329L3.076 1.56h3.182l5.965 8.532.929 1.329 7.754 11.09h-3.182z',
		'youtube'   => 'M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z',
		'threads'   => 'M18.263 11.097c-.03-3.486-1.92-5.586-5.111-5.586-2.13 0-3.922.963-4.863 2.499l2.062 1.438c.535-.843 1.272-1.543 2.628-1.543 1.528 0 2.318.85 2.544 2.431a15 15 0 0 0-2.236-.173c-4.125 0-6.068 1.867-6.068 4.336s1.943 3.99 4.804 3.99c3.139 0 5.013-2.115 5.781-4.735.798.361 1.348 1.204 1.348 2.47 0 3.387-3.907 5.232-7.22 5.232-4.885 0-8.077-3.207-8.077-8.424 0-6.392 4.223-10.487 9.9-10.487 3.808 0 5.69 1.671 6.97 3.914l2.108-1.475C21.44 2.078 18.331 0 13.663 0 6.227 0 1.168 5.277 1.168 12.934c0 7 4.953 11.066 10.856 11.066 4.878 0 9.809-2.846 9.809-7.716 0-2.545-1.46-4.231-3.569-5.187m-6.33 4.855c-1.077 0-2.026-.512-2.026-1.453 0-1.483 1.822-1.934 3.606-1.934.678 0 1.34.045 1.927.173-.422 1.927-1.671 3.215-3.508 3.214Z',
		'facebook'  => 'M9.101 23.691v-7.98H6.627v-3.667h2.474v-1.58c0-4.085 1.848-5.978 5.858-5.978.401 0 .955.042 1.468.103a8.68 8.68 0 0 1 1.141.195v3.325a8.623 8.623 0 0 0-.653-.036 26.805 26.805 0 0 0-.733-.009c-.707 0-1.259.096-1.675.309a1.686 1.686 0 0 0-.679.622c-.258.42-.374.995-.374 1.752v1.297h3.919l-.386 2.103-.287 1.564h-3.246v8.245C19.396 23.238 24 18.179 24 12.044c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.628 3.874 10.35 9.101 11.647Z',
		'mastodon'  => 'M23.268 5.313c-.35-2.578-2.617-4.61-5.304-5.004C17.51.242 15.792 0 11.813 0h-.03c-3.98 0-4.835.242-5.288.309C3.882.692 1.496 2.518.917 5.127.64 6.412.61 7.837.661 9.143c.074 1.874.088 3.745.26 5.611.118 1.24.325 2.47.62 3.68.55 2.237 2.777 4.098 4.96 4.857 2.336.792 4.849.923 7.256.38.265-.061.527-.132.786-.213.585-.184 1.27-.39 1.774-.753a.057.057 0 0 0 .023-.043v-1.809a.052.052 0 0 0-.02-.041.053.053 0 0 0-.046-.01 20.282 20.282 0 0 1-4.709.545c-2.73 0-3.463-1.284-3.674-1.818a5.593 5.593 0 0 1-.319-1.433.053.053 0 0 1 .066-.054c1.517.363 3.072.546 4.632.546.376 0 .75 0 1.125-.01 1.57-.044 3.224-.124 4.768-.422.038-.008.077-.015.11-.024 2.435-.464 4.753-1.92 4.989-5.604.008-.145.03-1.52.03-1.67.002-.512.167-3.63-.024-5.545zm-3.748 9.195h-2.561V8.29c0-1.309-.55-1.976-1.67-1.976-1.23 0-1.846.79-1.846 2.35v3.403h-2.546V8.663c0-1.56-.617-2.35-1.848-2.35-1.112 0-1.668.668-1.67 1.977v6.218H4.822V8.102c0-1.31.337-2.35 1.011-3.12.696-.77 1.608-1.164 2.74-1.164 1.311 0 2.302.5 2.962 1.498l.638 1.06.638-1.06c.66-.999 1.65-1.498 2.96-1.498 1.13 0 2.043.395 2.74 1.164.675.77 1.012 1.81 1.012 3.12z',
		'bluesky'   => 'M5.202 2.857C7.954 4.922 10.913 9.11 12 11.358c1.087-2.247 4.046-6.436 6.798-8.501C20.783 1.366 24 .213 24 3.883c0 .732-.42 6.156-.667 7.037-.856 3.061-3.978 3.842-6.755 3.37 4.854.826 6.089 3.562 3.422 6.299-5.065 5.196-7.28-1.304-7.847-2.97-.104-.305-.152-.448-.153-.327 0-.121-.05.022-.153.327-.568 1.666-2.782 8.166-7.847 2.97-2.667-2.737-1.432-5.473 3.422-6.3-2.777.473-5.899-.308-6.755-3.369C.42 10.04 0 4.615 0 3.883c0-3.67 3.217-2.517 5.202-1.026',
		'rss'       => 'M19.199 24C19.199 13.467 10.533 4.8 0 4.8V0c13.165 0 24 10.835 24 24h-4.801zM3.291 17.415c1.814 0 3.293 1.479 3.293 3.295 0 1.813-1.485 3.29-3.301 3.29C1.47 24 0 22.526 0 20.71s1.475-3.294 3.291-3.295zM15.909 24h-4.665c0-6.169-5.075-11.245-11.244-11.245V8.09c8.727 0 15.909 7.184 15.909 15.91z',
	);

	$path = $paths[ $platform ] ?? '';
	if ( '' === $path ) {
		return '';
	}

	return sprintf(
		'<svg class="site-footer__social-icon" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="%s"/></svg>',
		$path // path strings are pure SVG path data, safe to use directly.
	);
}

// ─────────────────────────────────────────────────────────────
// 2. Social platform labels (for aria-label)
// ─────────────────────────────────────────────────────────────

/**
 * Return a human-readable label for a social platform.
 *
 * @param string $platform Platform slug.
 * @return string Label or the slug as fallback.
 */
function kb_footer_social_label( string $platform ): string {
	$labels = array(
		'linkedin'  => 'LinkedIn',
		'instagram' => 'Instagram',
		'github'    => 'GitHub',
		'x'         => 'X (Twitter)',
		'youtube'   => 'YouTube',
		'threads'   => 'Threads',
		'facebook'  => 'Facebook',
		'mastodon'  => 'Mastodon',
		'bluesky'   => 'Bluesky',
		'rss'       => 'RSS',
	);

	return $labels[ $platform ] ?? $platform;
}

// ─────────────────────────────────────────────────────────────
// 3. Back-to-top SVG icon
// ─────────────────────────────────────────────────────────────

/**
 * Return the back-to-top arrow SVG.
 *
 * @return string Inline SVG.
 */
function kb_footer_back_to_top_svg(): string {
	return '<svg class="site-footer__back-to-top-icon" aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg"><path d="m18 15-6-6-6 6"/></svg>';
}

// ─────────────────────────────────────────────────────────────
// 4. Main render function
// ─────────────────────────────────────────────────────────────

/**
 * Build the complete footer HTML — fully pre-escaped.
 *
 * @return string Pre-escaped HTML.
 */
function kb_render_footer(): string {
	// ─── Fetch all options ─────────────────────────────────────
	$tagline_raw       = kb_footer_option( 'footer_tagline', get_option( 'blogdescription' ) );
	$contact_email     = kb_footer_option( 'footer_contact_email', '' );
	$contact_phone     = kb_footer_option( 'footer_contact_phone', '' );
	$location          = kb_footer_option( 'footer_location', '' );
	$social_links      = kb_get_footer_social();
	$show_newsletter   = (bool) kb_footer_option( 'footer_show_newsletter', false );
	$newsletter_heading = kb_footer_option( 'footer_newsletter_heading', __( 'Nyhetsbrev', 'kim-branzell' ) );
	$newsletter_text   = kb_footer_option( 'footer_newsletter_text', '' );
	$newsletter_embed  = kb_footer_option( 'footer_newsletter_embed', '' );
	$colophon_raw      = kb_footer_option( 'footer_colophon', '' );
	$legal_links       = kb_get_footer_legal_links();
	$copyright_override = kb_footer_option( 'footer_copyright_override', '' );
	$show_back_to_top  = (bool) kb_footer_option( 'footer_show_back_to_top', true );

	// ─── Site identity ────────────────────────────────────────
	$site_name     = esc_html( get_bloginfo( 'name' ) );
	$home_url      = esc_url( home_url( '/' ) );
	$home_host     = wp_parse_url( home_url(), PHP_URL_HOST );

	// ─── Brand markup ─────────────────────────────────────────
	$tagline_html = '';
	if ( '' !== $tagline_raw ) {
		$tagline_html = '<p class="site-footer__tagline">' . wp_kses_post( $tagline_raw ) . '</p>';
	}

	// Logo SVG (reuse the site logo path from header.php).
	$logo_svg = '<svg class="site-footer__logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 910 882" aria-hidden="true" focusable="false"><path fill="currentColor" d="M6.01 867.64c0-.61 9-16.99 20.06-36.43 11.26-19.44 33.36-58.94 49.53-87.59s47.89-85.54 70.81-126.27 54.44-96.8 69.99-124.84c37.04-66.1 58.73-104.99 157.99-280.99 45.43-80.43 89.02-157.99 97-172.32L485.92 13h84.52c46.46 0 84.11.61 83.7 1.64-2.87 4.5-65.28 114.81-96.6 170.27-20.06 35.4-46.25 81.45-57.92 102.33-11.87 20.87-36.43 64.67-54.64 97.21-31.72 56.48-90.66 160.86-128.32 227.16-9.62 16.99-46.25 81.66-81.45 143.87l-63.85 112.97H88.68c-45.43 0-82.68-.41-82.68-.82zm276.28-.41c0-2.25 79-144.07 202.4-363.05C619.56 264.53 637.56 233.02 640.84 232.4c2.05-.41 5.12 2.87 8.8 8.8 7.37 12.89 30.08 36.43 45.02 47.07 15.55 11.05 33.77 20.26 48.71 24.56 6.34 1.84 11.67 3.89 11.67 4.5 0 1.23-30.9 55.87-76.13 134.05l-30.29 52.6 38.07 67.13c45.23 79.4 111.53 196.06 144.28 253.36 12.89 22.51 23.74 41.54 23.94 42.57.41.82-36.43 1.23-81.66 1.02l-82.47-.61-61.6-107.85c-33.77-59.35-61.8-108.06-62.42-107.85-.61 0-27.22 47.07-59.35 104.37-31.93 57.51-59.35 106.21-60.99 108.47-2.66 3.68-6.14 3.89-83.5 3.89-44.41 0-80.63-.61-80.63-1.23zm507.54-643.42c-54.64-9.82-90.87-63.03-79.61-117.06 11.46-54.64 66.92-90.05 119.52-76.33 28.65 7.57 49.53 24.56 62.42 50.75 15.35 31.31 15.35 59.35 0 90.66-18.83 38.47-60.17 59.35-102.33 51.98"/></svg>';

	$brand_html = sprintf(
		'<div class="site-footer__brand">%s%s%s</div>',
		// Brand link: logo + site name.
		sprintf(
			'<a class="site-footer__brand-link" href="%s">%s<span>%s</span></a>',
			$home_url,
			$logo_svg,
			$site_name
		),
		$tagline_html,
		// Social links — omitted if no items.
		kb_render_footer_social_list( $social_links )
	);

	// ─── Nav markup ───────────────────────────────────────────
	$nav_html    = '';
	$menu_output = wp_nav_menu(
		array(
			'theme_location' => 'footer_menu',
			'depth'          => 1,
			'container'      => false,
			'menu_class'     => 'site-footer__nav-list',
			'fallback_cb'    => '__return_empty_string',
			'echo'           => false,
		)
	);

	if ( is_string( $menu_output ) && '' !== trim( $menu_output ) ) {
		$nav_html = sprintf(
			'<nav class="site-footer__nav" aria-label="%s"><h3 class="site-footer__heading">%s</h3>%s</nav>',
			esc_attr__( 'Footer navigering', 'kim-branzell' ),
			esc_html__( 'Navigering', 'kim-branzell' ),
			$menu_output
		);
	}

	// ─── Contact markup ──────────────────────────────────────
	$contact_items = '';
	if ( '' !== $contact_email ) {
		$mailto         = 'mailto:' . $contact_email;
		$contact_items .= sprintf(
			'<li><a class="site-footer__contact-link" href="%s">%s</a></li>',
			esc_url( $mailto, array( 'mailto' ) ),
			esc_html( $contact_email )
		);
	}
	if ( '' !== $contact_phone ) {
		$digits         = preg_replace( '/[^+\d]/', '', $contact_phone );
		$tel            = 'tel:' . $digits;
		$contact_items .= sprintf(
			'<li><a class="site-footer__contact-link" href="%s">%s</a></li>',
			esc_url( $tel, array( 'tel' ) ),
			esc_html( $contact_phone )
		);
	}
	if ( '' !== $location ) {
		$contact_items .= sprintf(
			'<li><span class="site-footer__location">%s</span></li>',
			esc_html( $location )
		);
	}

	$contact_html = '';
	if ( '' !== $contact_items ) {
		$contact_html = sprintf(
			'<div class="site-footer__contact"><h3 class="site-footer__heading">%s</h3><ul class="site-footer__contact-list" role="list">%s</ul></div>',
			esc_html__( 'Kontakt', 'kim-branzell' ),
			$contact_items
		);
	}

	// ─── Newsletter markup (conditional) ─────────────────────
	$newsletter_html = '';
	if ( $show_newsletter ) {
		$newsletter_body = '';
		if ( '' !== $newsletter_text ) {
			$newsletter_body .= '<p class="site-footer__newsletter-text">' . wp_kses_post( $newsletter_text ) . '</p>';
		}
		if ( '' !== $newsletter_embed ) {
			// Admin-trusted shortcode/HTML (only manage_options users can edit this field).
			$newsletter_body .= do_shortcode( $newsletter_embed );
		} else {
			// Stub — "Kommer snart".
			$newsletter_body .= '<p class="site-footer__newsletter-stub">' . esc_html__( 'Kommer snart', 'kim-branzell' ) . '</p>';
		}
		$newsletter_html = sprintf(
			'<div class="site-footer__newsletter"><h3 class="site-footer__heading">%s</h3>%s</div>',
			esc_html( $newsletter_heading ),
			$newsletter_body
		);
	}

	// ─── Merge contact + newsletter into one column ──────────
	// If both are empty, hide the column.
	if ( '' !== $contact_html || '' !== $newsletter_html ) {
		$contact_column    = $contact_html . $newsletter_html;
		$contact_column_html = sprintf(
			'<div class="site-footer__contact-column">%s</div>',
			$contact_column
		);
	} else {
		$contact_column_html = '';
	}

	// ─── Upper tier ───────────────────────────────────────────
	$top_html = '';
	if ( '' !== $brand_html || '' !== $nav_html || '' !== $contact_column_html ) {
		$top_html = sprintf(
			'<div class="site-footer__top">%s%s%s</div>',
			$brand_html,
			$nav_html,
			$contact_column_html
		);
	}

	// ─── Legal links ──────────────────────────────────────────
	$legal_html = '';
	if ( ! empty( $legal_links ) ) {
		$items = '';
		foreach ( $legal_links as $link ) {
			$link_url   = $link['url'];
			$link_label = esc_html( $link['label'] );
			$link_host  = wp_parse_url( $link_url, PHP_URL_HOST );

			// Internal link = same host → no target/rel.
			if ( $link_host === $home_host || '' === $link_host ) {
				$items .= sprintf(
					'<li><a href="%s">%s</a></li>',
					esc_url( $link_url ),
					$link_label
				);
			} else {
				$items .= sprintf(
					'<li><a href="%s" target="_blank" rel="noopener external">%s</a></li>',
					esc_url( $link_url, array( 'http', 'https' ) ),
					$link_label
				);
			}
		}
		$legal_html = '<ul class="site-footer__legal" role="list">' . $items . '</ul>';
	}

	// ─── Colophon ─────────────────────────────────────────────
	$colophon_html = '';
	if ( '' !== $colophon_raw ) {
		$colophon_html = '<p class="site-footer__colophon">' . wp_kses_post( $colophon_raw ) . '</p>';
	}

	// ─── Copyright ────────────────────────────────────────────
	if ( '' !== $copyright_override ) {
		$copyright_text = esc_html( $copyright_override );
	} else {
		/* translators: %1$d: current year, %2$s: site name. */
		$copyright_text = sprintf(
			esc_html__( '© %1$d %2$s', 'kim-branzell' ),
			(int) current_time( 'Y' ),
			$site_name
		);
	}
	$copyright_html = '<p class="site-footer__copyright">' . $copyright_text . '</p>';

	// ─── Back-to-top ──────────────────────────────────────────
	$back_to_top_html = '';
	if ( $show_back_to_top ) {
		$back_to_top_html = sprintf(
			'<a class="site-footer__back-to-top" href="#main-content">%s<span>%s</span></a>',
			kb_footer_back_to_top_svg(),
			esc_html__( 'Till toppen', 'kim-branzell' )
		);
	}

	// ─── Theme switcher (copied verbatim from current footer.php) ─
	$theme_switcher_html = '
		<div class="theme-toggle">
		  <div class="theme-switcher" role="radiogroup" aria-label="' . esc_attr__( 'Tema', 'kim-branzell' ) . '">
		    <div class="theme-switcher__option" role="radio" aria-checked="false" tabindex="-1" data-value="light" aria-label="' . esc_attr__( 'Ljust tema', 'kim-branzell' ) . '" aria-setsize="3" aria-posinset="1">
		      <svg class="theme-switcher__icon" aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
		        <circle cx="12" cy="12" r="5"/>
		        <line x1="12" y1="1" x2="12" y2="3"/>
		        <line x1="12" y1="21" x2="12" y2="23"/>
		        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
		        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
		        <line x1="1" y1="12" x2="3" y2="12"/>
		        <line x1="21" y1="12" x2="23" y2="12"/>
		        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
		        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
		      </svg>
		      <span class="theme-switcher__label">' . esc_html__( 'Ljust', 'kim-branzell' ) . '</span>
		    </div>
		    <div class="theme-switcher__option" role="radio" aria-checked="true" tabindex="0" data-value="auto" aria-label="' . esc_attr__( 'System', 'kim-branzell' ) . '" aria-setsize="3" aria-posinset="2">
		      <svg class="theme-switcher__icon" aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
		        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
		        <line x1="8" y1="21" x2="16" y2="21"/>
		        <line x1="12" y1="17" x2="12" y2="21"/>
		      </svg>
		      <span class="theme-switcher__label">' . esc_html__( 'System', 'kim-branzell' ) . '</span>
		    </div>
		    <div class="theme-switcher__option" role="radio" aria-checked="false" tabindex="-1" data-value="dark" aria-label="' . esc_attr__( 'Mörkt', 'kim-branzell' ) . '" aria-setsize="3" aria-posinset="3">
		      <svg class="theme-switcher__icon" aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
		        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
		      </svg>
		      <span class="theme-switcher__label">' . esc_html__( 'Mörkt', 'kim-branzell' ) . '</span>
		    </div>
		  </div>
		</div>';

	// ─── Bottom tier ──────────────────────────────────────────
	// Group legal links + colophon together.
	$bottom_legal_group = $legal_html . $colophon_html;

	$bottom_html = sprintf(
		'<div class="site-footer__bottom">%s<div class="site-footer__bottom-group">%s</div><div class="site-footer__utilities">%s%s</div></div>',
		$copyright_html,
		$bottom_legal_group,
		$back_to_top_html,
		$theme_switcher_html
	);

	// ─── Assemble full footer ─────────────────────────────────
	return sprintf(
		'<footer class="site-footer" aria-labelledby="site-footer-heading"><h2 id="site-footer-heading" class="screen-reader-text">%s</h2><div class="site-footer__inner">%s%s</div></footer>',
		esc_html__( 'Sidfot', 'kim-branzell' ),
		$top_html,
		$bottom_html
	);
}

/**
 * Build the social-links list HTML.
 *
 * @param array $links [ [ 'platform'=>…, 'url'=>… ], … ].
 * @return string HTML or empty string if no links.
 */
function kb_render_footer_social_list( array $links ): string {
	if ( empty( $links ) ) {
		return '';
	}

	$items = '';
	foreach ( $links as $link ) {
		$platform = $link['platform'];
		$url      = $link['url'];
		$label    = kb_footer_social_label( $platform );
		$svg      = kb_footer_social_svg( $platform );

		/* translators: %s: platform name (e.g. LinkedIn). */
		$aria_label = sprintf(
			esc_attr__( '%s — öppnas i nytt fönster', 'kim-branzell' ),
			$label
		);

		$items .= sprintf(
			'<li><a class="site-footer__social-link" href="%s" target="_blank" rel="noopener external" aria-label="%s">%s</a></li>',
			esc_url( $url, array( 'http', 'https' ) ),
			$aria_label,
			$svg
		);
	}

	return '<ul class="site-footer__social" role="list">' . $items . '</ul>';
}
