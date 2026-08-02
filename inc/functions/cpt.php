<?php
/**
 * FAQ Custom Post Type registration.
 *
 * @package Kim_Branzell
 */

/**
 * Register FAQ custom post type.
 */
function kb_register_faq_cpt() {
	$labels = array(
		'name'                  => _x( 'FAQs', 'Post Type General Name', 'kim-branzell' ),
		'singular_name'         => _x( 'FAQ', 'Post Type Singular Name', 'kim-branzell' ),
		'menu_name'             => __( 'FAQs', 'kim-branzell' ),
		'name_admin_bar'        => __( 'FAQ', 'kim-branzell' ),
		'archives'              => __( 'FAQ Archives', 'kim-branzell' ),
		'attributes'            => __( 'FAQ Attributes', 'kim-branzell' ),
		'parent_item_colon'     => __( 'Parent FAQ:', 'kim-branzell' ),
		'all_items'             => __( 'All FAQs', 'kim-branzell' ),
		'add_new_item'          => __( 'Add New FAQ', 'kim-branzell' ),
		'add_new'               => __( 'Add New', 'kim-branzell' ),
		'new_item'              => __( 'New FAQ', 'kim-branzell' ),
		'edit_item'             => __( 'Edit FAQ', 'kim-branzell' ),
		'update_item'           => __( 'Update FAQ', 'kim-branzell' ),
		'view_item'             => __( 'View FAQ', 'kim-branzell' ),
		'view_items'            => __( 'View FAQs', 'kim-branzell' ),
		'search_items'          => __( 'Search FAQ', 'kim-branzell' ),
		'not_found'             => __( 'Not found', 'kim-branzell' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'kim-branzell' ),
		'featured_image'        => __( 'Featured Image', 'kim-branzell' ),
		'set_featured_image'    => __( 'Set featured image', 'kim-branzell' ),
		'remove_featured_image' => __( 'Remove featured image', 'kim-branzell' ),
		'use_featured_image'    => __( 'Use as featured image', 'kim-branzell' ),
		'insert_into_item'      => __( 'Insert into FAQ', 'kim-branzell' ),
		'uploaded_to_this_item' => __( 'Uploaded to this FAQ', 'kim-branzell' ),
		'items_list'            => __( 'Items list', 'kim-branzell' ),
		'items_list_navigation' => __( 'Items list navigation', 'kim-branzell' ),
		'filter_items_list'     => __( 'Filter items list', 'kim-branzell' ),
	);
	$args   = array(
		'label'               => __( 'FAQ', 'kim-branzell' ),
		'description'         => __( 'Frequently Asked Questions', 'kim-branzell' ),
		'labels'              => $labels,
		'supports'            => array( 'title', 'editor', 'revisions', 'page-attributes' ),
		'hierarchical'        => false,
		'public'              => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'menu_position'       => 25,
		'menu_icon'           => 'dashicons-editor-help',
		'show_in_admin_bar'   => true,
		'show_in_nav_menus'   => true,
		'can_export'          => true,
		'has_archive'         => false,
		'exclude_from_search' => true,
		'publicly_queryable'  => false,
		'capability_type'     => 'post',
		'show_in_rest'        => true,
		'rewrite'             => false,
	);
	register_post_type( 'faq', $args );
}
add_action( 'init', 'kb_register_faq_cpt' );

/**
 * Add custom meta field for FAQ order.
 *
 * @param string $post_type The post type name.
 */
function kb_faq_add_meta_boxes( $post_type ) {
	if ( 'faq' === $post_type ) {
		add_meta_box(
			'kb_faq_meta',
			__( 'FAQ Settings', 'kim-branzell' ),
			'kb_faq_meta_box_callback',
			'faq',
			'side',
			'default'
		);
	}
}
add_action( 'add_meta_boxes', 'kb_faq_add_meta_boxes' );

/**
 * FAQ meta box callback.
 *
 * @param WP_Post $post The post object.
 */
function kb_faq_meta_box_callback( WP_Post $post ) {
	wp_nonce_field( 'kb_faq_meta_save', 'kb_faq_meta_nonce' );

	$order = get_post_meta( $post->ID, 'kb_faq_order', true );
	?>
	<p>
		<label for="kb_faq_order"><?php esc_html_e( 'Display Order', 'kim-branzell' ); ?></label>
		<input
			type="number"
			id="kb_faq_order"
			name="kb_faq_order"
			value="<?php echo esc_attr( $order ?? '' ); ?>"
			class="widefat"
			placeholder="0"
		/>
		<span class="description"><?php esc_html_e( 'Lower numbers appear first. Default: 0', 'kim-branzell' ); ?></span>
	</p>
	<?php
}

/**
 * Save FAQ meta data.
 *
 * @param int $post_id The post ID.
 */
function kb_faq_save_meta( $post_id ) {
	if ( ! isset( $_POST['kb_faq_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['kb_faq_meta_nonce'] ), 'kb_faq_meta_save' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['kb_faq_order'] ) ) {
		update_post_meta( $post_id, 'kb_faq_order', absint( $_POST['kb_faq_order'] ) );
	}
}
add_action( 'save_post_faq', 'kb_faq_save_meta' );

// ─────────────────────────────────────────────────────────────
// ACF Field Groups
// ─────────────────────────────────────────────────────────────

/**
 * Register ACF field groups programmatically.
 * User portrait for OG images.
 */
function kb_register_acf_field_groups() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group(
		array(
			'key'                   => 'group_kb_user_portrait',
			'title'                 => __( 'Profile Portrait', 'kim-branzell' ),
			'fields'                => array(
				array(
					'key'           => 'field_kb_profile_image',
					'label'         => __( 'Profile Photo', 'kim-branzell' ),
					'name'          => 'kb_profile_image',
					'type'          => 'image',
					'return_format' => 'array',
					'preview_size'  => 'medium',
					'library'       => 'all',
					'mime_types'    => 'jpg,jpeg,png,webp,avif',
					'instructions'  => __( 'Profile photo for OG social preview images. Leave empty to use your Gravatar.', 'kim-branzell' ),
				),
			),
			'location'              => array(
				array(
					array(
						'param'    => 'user_form',
						'operator' => '==',
						'value'    => 'all',
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
add_action( 'acf/init', 'kb_register_acf_field_groups' );
