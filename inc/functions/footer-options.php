<?php
/**
 * Footer Options — ACF Options page + fields + accessors.
 *
 * Registers a "Site Settings → Footer" options sub-page
 * so the admin can manage footer content without touching code.
 *
 * @package Kim_Branzell
 */

// ─────────────────────────────────────────────────────────────
// 1. Options Page Registration
// ─────────────────────────────────────────────────────────────

/**
 * Register the ACF options pages and field groups.
 */
function kb_register_footer_options(): void {
	if ( ! function_exists( 'acf_add_options_page' ) ) {
		return;
	}

	// Parent page.
	$parent = acf_add_options_page(
		array(
			'page_title' => __( 'Webbplatsinställningar', 'kim-branzell' ),
			'menu_slug'  => 'kb-site-settings',
			'capability' => 'manage_options',
			'redirect'   => false,
			'icon_url'   => 'dashicons-admin-settings',
		)
	);

	// Footer sub-page.
	acf_add_options_sub_page(
		array(
			'page_title'  => __( 'Sidfot', 'kim-branzell' ),
			'menu_slug'   => 'kb-footer-settings',
			'parent_slug' => $parent['menu_slug'],
			'capability'  => 'manage_options',
		)
	);

	// Field group.
	acf_add_local_field_group(
		array(
			'key'                   => 'group_kb_footer_settings',
			'title'                 => __( 'Sidfotsinnehåll', 'kim-branzell' ),
			'fields'                => kb_footer_fields(),
			'location'              => array(
				array(
					array(
						'param'    => 'options_page',
						'operator' => '==',
						'value'    => 'kb-footer-settings',
					),
				),
			),
			'menu_order'            => 0,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'field',
			'hide_on_screen'        => '',
			'active'                => true,
			'description'           => '',
			'show_in_rest'          => 0,
		)
	);
}
add_action( 'acf/init', 'kb_register_footer_options' );

/**
 * Return the ACF field definitions for the footer options page.
 *
 * @return array
 */
function kb_footer_fields(): array {
	return array(
		// ── Tab: Brand ────────────────────────────────────
		array(
			'key'   => 'field_kb_footer_tab_brand',
			'label' => __( 'Varumärke', 'kim-branzell' ),
			'name'  => '',
			'type'  => 'tab',
		),
		array(
			'key'           => 'field_kb_footer_tagline',
			'label'         => __( 'Varumärkes-text', 'kim-branzell' ),
			'name'          => 'footer_tagline',
			'type'          => 'textarea',
			'default_value' => get_option( 'blogdescription' ),
			'rows'          => 3,
			'new_lines'     => 'br',
			'instructions'  => __( 'Kort varumärkestext — visas stort i sidfoten. Tillåtna taggar: a, br, em, strong, span.', 'kim-branzell' ),
		),
		// ── Tab: Kontakt ──────────────────────────────────
		array(
			'key'   => 'field_kb_footer_tab_contact',
			'label' => __( 'Kontakt', 'kim-branzell' ),
			'name'  => '',
			'type'  => 'tab',
		),
		array(
			'key'          => 'field_kb_footer_contact_email',
			'label'        => __( 'E-post', 'kim-branzell' ),
			'name'         => 'footer_contact_email',
			'type'         => 'email',
			'placeholder'  => 'hej@kimbranzell.kim',
			'instructions' => __( 'Ange en e-postadress som visas som mailto:-länk.', 'kim-branzell' ),
		),
		array(
			'key'    => 'field_kb_footer_contact_phone',
			'label'  => __( 'Telefon (valfritt)', 'kim-branzell' ),
			'name'   => 'footer_contact_phone',
			'type'   => 'text',
			'instructions' => __( 'Valfritt telefonnummer. Visas som tel:-länk.', 'kim-branzell' ),
		),
		array(
			'key'    => 'field_kb_footer_location',
			'label'  => __( 'Ort / plats', 'kim-branzell' ),
			'name'   => 'footer_location',
			'type'   => 'text',
			'placeholder' => __( 't.ex. Malmö, Sverige', 'kim-branzell' ),
			'instructions' => __( 'Valfri plats-text.', 'kim-branzell' ),
		),
		// ── Tab: Sociala länkar ───────────────────────────
		array(
			'key'   => 'field_kb_footer_tab_social',
			'label' => __( 'Sociala medier', 'kim-branzell' ),
			'name'  => '',
			'type'  => 'tab',
		),
		array(
			'key'          => 'field_kb_footer_social_links',
			'label'        => __( 'Sociala länkar', 'kim-branzell' ),
			'name'         => 'footer_social_links',
			'type'         => 'repeater',
			'layout'       => 'table',
			'button_label' => __( 'Lägg till social länk', 'kim-branzell' ),
			'sub_fields'   => array(
				array(
					'key'     => 'field_kb_footer_social_platform',
					'label'   => __( 'Plattform', 'kim-branzell' ),
					'name'    => 'platform',
					'type'    => 'select',
					'choices' => array(
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
					),
				),
				array(
					'key'          => 'field_kb_footer_social_url',
					'label'        => __( 'URL', 'kim-branzell' ),
					'name'         => 'url',
					'type'         => 'url',
					'instructions' => __( 'Fullständig URL till din profil.', 'kim-branzell' ),
				),
			),
		),
		// ── Tab: Nyhetsbrev ───────────────────────────────
		array(
			'key'   => 'field_kb_footer_tab_newsletter',
			'label' => __( 'Nyhetsbrev', 'kim-branzell' ),
			'name'  => '',
			'type'  => 'tab',
		),
		array(
			'key'          => 'field_kb_footer_show_newsletter',
			'label'        => __( 'Visa nyhetsbrev', 'kim-branzell' ),
			'name'         => 'footer_show_newsletter',
			'type'         => 'true_false',
			'default_value' => 0,
			'message'      => __( 'Visa en nyhetsbrevssektion i sidfoten', 'kim-branzell' ),
			'instructions' => __( 'Aktivera när formuläret är klart. Förhandsgranska sidan för att se resultatet.', 'kim-branzell' ),
		),
		array(
			'key'               => 'field_kb_footer_newsletter_heading',
			'label'             => __( 'Rubrik', 'kim-branzell' ),
			'name'              => 'footer_newsletter_heading',
			'type'              => 'text',
			'default_value'     => __( 'Nyhetsbrev', 'kim-branzell' ),
			'conditional_logic' => array(
				array(
					array(
						'field'    => 'field_kb_footer_show_newsletter',
						'operator' => '==',
						'value'    => '1',
					),
				),
			),
		),
		array(
			'key'               => 'field_kb_footer_newsletter_text',
			'label'             => __( 'Beskrivning', 'kim-branzell' ),
			'name'              => 'footer_newsletter_text',
			'type'              => 'textarea',
			'rows'              => 2,
			'conditional_logic' => array(
				array(
					array(
						'field'    => 'field_kb_footer_show_newsletter',
						'operator' => '==',
						'value'    => '1',
					),
				),
			),
		),
		array(
			'key'               => 'field_kb_footer_newsletter_embed',
			'label'             => __( 'Inbäddning (shortcode/HTML)', 'kim-branzell' ),
			'name'              => 'footer_newsletter_embed',
			'type'              => 'textarea',
			'rows'              => 4,
			'new_lines'         => '',
			'instructions'      => __( 'Klistra in brnzl-forms-shortcode (t.ex. [brnzl_form id="1"]) eller formulär-HTML när nyhetsbrevsformuläret är klart. Fältet är avsett för administratören — endast användare med manage_options kan redigera denna sida.', 'kim-branzell' ),
			'conditional_logic' => array(
				array(
					array(
						'field'    => 'field_kb_footer_show_newsletter',
						'operator' => '==',
						'value'    => '1',
					),
				),
			),
		),
		// ── Tab: Botten (copyright, legal, etc.) ───────────
		array(
			'key'   => 'field_kb_footer_tab_bottom',
			'label' => __( 'Bottenrad', 'kim-branzell' ),
			'name'  => '',
			'type'  => 'tab',
		),
		array(
			'key'          => 'field_kb_footer_copyright_override',
			'label'        => __( 'Copyright-override', 'kim-branzell' ),
			'name'         => 'footer_copyright_override',
			'type'         => 'text',
			'placeholder'  => sprintf( '© %d %s', (int) current_time( 'Y' ), get_bloginfo( 'name' ) ),
			'instructions' => __( 'Lämna tom för att automatiskt visa "© ÅR Webbplatsnamn".', 'kim-branzell' ),
		),
		array(
			'key'          => 'field_kb_footer_show_back_to_top',
			'label'        => __( 'Visa "Till toppen"-länk', 'kim-branzell' ),
			'name'         => 'footer_show_back_to_top',
			'type'         => 'true_false',
			'default_value' => 1,
			'message'      => __( 'Visa en "Till toppen"-länk i bottenraden', 'kim-branzell' ),
		),
		array(
			'key'          => 'field_kb_footer_colophon',
			'label'        => __( 'Kolofon (valfritt)', 'kim-branzell' ),
			'name'         => 'footer_colophon',
			'type'         => 'textarea',
			'rows'         => 2,
			'new_lines'    => 'br',
			'instructions' => __( 'Valfri rad längst ner, t.ex. "Byggd med WordPress av Kim Branzell." Tillåtna taggar: a, br, em, strong, span.', 'kim-branzell' ),
		),
		array(
			'key'          => 'field_kb_footer_legal_links',
			'label'        => __( 'Juridiska länkar', 'kim-branzell' ),
			'name'         => 'footer_legal_links',
			'type'         => 'repeater',
			'layout'       => 'table',
			'button_label' => __( 'Lägg till juridisk länk', 'kim-branzell' ),
			'max'          => 6,
			'sub_fields'   => array(
				array(
					'key'         => 'field_kb_footer_legal_label',
					'label'       => __( 'Etikett', 'kim-branzell' ),
					'name'        => 'label',
					'type'        => 'text',
					'placeholder' => __( 't.ex. Integritetspolicy', 'kim-branzell' ),
				),
				array(
					'key'  => 'field_kb_footer_legal_url',
					'label' => __( 'URL', 'kim-branzell' ),
					'name'  => 'url',
					'type'  => 'url',
				),
			),
		),
	);
}

// ─────────────────────────────────────────────────────────────
// 2. Data Accessors
// ─────────────────────────────────────────────────────────────

/**
 * Retrieve a single footer option with caching.
 *
 * @param string $key     The ACF field name (without 'field_kb_footer_' prefix).
 * @param mixed  $default Fallback value if not set.
 * @return mixed
 */
function kb_footer_option( string $key, mixed $default = '' ): mixed {
	static $cache = array();

	if ( array_key_exists( $key, $cache ) ) {
		return $cache[ $key ];
	}

	if ( ! function_exists( 'get_field' ) ) {
		$cache[ $key ] = $default;
		return $default;
	}

	$value = get_field( $key, 'option' );

	if ( null === $value || false === $value ) {
		$cache[ $key ] = $default;
		return $default;
	}

	$cache[ $key ] = $value;
	return $value;
}

/**
 * Return social links as an array of [ 'platform' => …, 'url' => … ].
 *
 * @return list<array{platform: string, url: string}>
 */
function kb_get_footer_social(): array {
	$rows = kb_footer_option( 'footer_social_links', array() );

	if ( ! is_array( $rows ) || empty( $rows ) ) {
		return array();
	}

	$social = array();
	foreach ( $rows as $row ) {
		$platform = $row['platform'] ?? '';
		$url      = $row['url'] ?? '';
		if ( '' !== $platform && '' !== $url ) {
			$social[] = array(
				'platform' => $platform,
				'url'      => $url,
			);
		}
	}

	return $social;
}

/**
 * Return legal links as an array of [ 'label' => …, 'url' => … ].
 *
 * @return list<array{label: string, url: string}>
 */
function kb_get_footer_legal_links(): array {
	$rows = kb_footer_option( 'footer_legal_links', array() );

	if ( ! is_array( $rows ) || empty( $rows ) ) {
		return array();
	}

	$links = array();
	foreach ( $rows as $row ) {
		$label = $row['label'] ?? '';
		$url   = $row['url'] ?? '';
		if ( '' !== $label && '' !== $url ) {
			$links[] = array(
				'label' => $label,
				'url'   => $url,
			);
		}
	}

	return $links;
}
