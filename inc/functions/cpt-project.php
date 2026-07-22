<?php
/**
 * Project custom post type registration.
 *
 * Registers the "project" CPT used by the Project Index block,
 * along with its year/sector/tags meta and the side meta box.
 *
 * @package Kim_Branzell
 */

/**
 * Register the Project custom post type.
 */
function kb_register_project_cpt() {
	$labels = array(
		'name'                  => _x( 'Projects', 'Post Type General Name', 'kim-branzell' ),
		'singular_name'         => _x( 'Project', 'Post Type Singular Name', 'kim-branzell' ),
		'menu_name'             => __( 'Projects', 'kim-branzell' ),
		'name_admin_bar'        => __( 'Project', 'kim-branzell' ),
		'archives'              => __( 'Project Archives', 'kim-branzell' ),
		'attributes'            => __( 'Project Attributes', 'kim-branzell' ),
		'parent_item_colon'     => __( 'Parent Project:', 'kim-branzell' ),
		'all_items'             => __( 'All Projects', 'kim-branzell' ),
		'add_new_item'          => __( 'Add New Project', 'kim-branzell' ),
		'add_new'               => __( 'Add New', 'kim-branzell' ),
		'new_item'              => __( 'New Project', 'kim-branzell' ),
		'edit_item'             => __( 'Edit Project', 'kim-branzell' ),
		'update_item'           => __( 'Update Project', 'kim-branzell' ),
		'view_item'             => __( 'View Project', 'kim-branzell' ),
		'view_items'            => __( 'View Projects', 'kim-branzell' ),
		'search_items'          => __( 'Search Project', 'kim-branzell' ),
		'not_found'             => __( 'Not found', 'kim-branzell' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'kim-branzell' ),
		'featured_image'        => __( 'Featured Image', 'kim-branzell' ),
		'set_featured_image'    => __( 'Set featured image', 'kim-branzell' ),
		'remove_featured_image' => __( 'Remove featured image', 'kim-branzell' ),
		'use_featured_image'    => __( 'Use as featured image', 'kim-branzell' ),
		'insert_into_item'      => __( 'Insert into Project', 'kim-branzell' ),
		'uploaded_to_this_item' => __( 'Uploaded to this Project', 'kim-branzell' ),
		'items_list'            => __( 'Items list', 'kim-branzell' ),
		'items_list_navigation' => __( 'Items list navigation', 'kim-branzell' ),
		'filter_items_list'     => __( 'Filter items list', 'kim-branzell' ),
	);
	$args   = array(
		'label'               => __( 'Project', 'kim-branzell' ),
		'description'         => __( 'Portfolio projects shown in the Project Index block.', 'kim-branzell' ),
		'labels'              => $labels,
		'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes', 'revisions' ),
		'hierarchical'        => false,
		'public'              => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'menu_position'       => 26,
		'menu_icon'           => 'dashicons-portfolio',
		'show_in_admin_bar'   => true,
		'show_in_nav_menus'   => true,
		'can_export'          => true,
		'has_archive'         => 'projects',
		'exclude_from_search' => false,
		'publicly_queryable'  => true,
		'capability_type'     => 'post',
		'show_in_rest'        => true,
		'rewrite'             => array( 'slug' => 'project' ),
	);
	register_post_type( 'project', $args );
}
add_action( 'init', 'kb_register_project_cpt' );

/**
 * Register Project post meta fields.
 *
 * Exposes kb_project_year (integer), kb_project_sector (string),
 * and kb_project_tags (string) to the REST API and the editor.
 */
function kb_register_project_meta() {
	register_post_meta(
		'project',
		'kb_project_year',
		array(
			'type'          => 'integer',
			'single'        => true,
			'default'       => 0,
			'show_in_rest'  => true,
			'auth_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);

	register_post_meta(
		'project',
		'kb_project_sector',
		array(
			'type'          => 'string',
			'single'        => true,
			'default'       => '',
			'show_in_rest'  => true,
			'auth_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);

	register_post_meta(
		'project',
		'kb_project_tags',
		array(
			'type'          => 'string',
			'single'        => true,
			'default'       => '',
			'show_in_rest'  => true,
			'auth_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);
}
add_action( 'init', 'kb_register_project_meta' );

/**
 * Add the Project Details meta box.
 *
 * @param string $post_type The current post type being edited.
 */
function kb_project_add_meta_boxes( $post_type ) {
	if ( 'project' === $post_type ) {
		add_meta_box(
			'kb_project_meta',
			__( 'Project Details', 'kim-branzell' ),
			'kb_project_meta_box_callback',
			'project',
			'side',
			'default'
		);
	}
}
add_action( 'add_meta_boxes', 'kb_project_add_meta_boxes' );

/**
 * Project meta box callback.
 *
 * Renders the year/sector/tags inputs plus the saving nonce.
 *
 * @param WP_Post $post The post being edited.
 */
function kb_project_meta_box_callback( WP_Post $post ) {
	wp_nonce_field( 'kb_project_meta_save', 'kb_project_meta_nonce' );

	$year   = get_post_meta( $post->ID, 'kb_project_year', true );
	$sector = get_post_meta( $post->ID, 'kb_project_sector', true );
	$tags   = get_post_meta( $post->ID, 'kb_project_tags', true );
	?>
	<p>
		<label for="kb_project_year"><?php esc_html_e( 'Year', 'kim-branzell' ); ?></label>
		<input
			type="number"
			id="kb_project_year"
			name="kb_project_year"
			min="1900"
			max="2100"
			value="<?php echo esc_attr( $year ?? '' ); ?>"
			class="widefat"
			placeholder="<?php esc_attr_e( 'e.g. 2025', 'kim-branzell' ); ?>"
		/>
	</p>
	<p>
		<label for="kb_project_sector"><?php esc_html_e( 'Sector', 'kim-branzell' ); ?></label>
		<input
			type="text"
			id="kb_project_sector"
			name="kb_project_sector"
			value="<?php echo esc_attr( $sector ?? '' ); ?>"
			class="widefat"
		/>
	</p>
	<p>
		<label for="kb_project_tags"><?php esc_html_e( 'Tags', 'kim-branzell' ); ?></label>
		<input
			type="text"
			id="kb_project_tags"
			name="kb_project_tags"
			value="<?php echo esc_attr( $tags ?? '' ); ?>"
			class="widefat"
		/>
		<span class="description"><?php esc_html_e( 'Comma-separated.', 'kim-branzell' ); ?></span>
	</p>
	<?php
}

/**
 * Save Project meta data.
 *
 * Runs on save_post_project after the nonce, autosave, and capability
 * guards pass. Year is absint; sector/tags are sanitized text fields
 * with wp_unslash applied to comply with WPCS.
 *
 * @param int $post_id The post being saved.
 */
function kb_project_save_meta( $post_id ) {
	if ( ! isset( $_POST['kb_project_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['kb_project_meta_nonce'] ), 'kb_project_meta_save' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['kb_project_year'] ) ) {
		update_post_meta( $post_id, 'kb_project_year', absint( $_POST['kb_project_year'] ) );
	}

	if ( isset( $_POST['kb_project_sector'] ) ) {
		update_post_meta( $post_id, 'kb_project_sector', sanitize_text_field( wp_unslash( $_POST['kb_project_sector'] ) ) );
	}

	if ( isset( $_POST['kb_project_tags'] ) ) {
		update_post_meta( $post_id, 'kb_project_tags', sanitize_text_field( wp_unslash( $_POST['kb_project_tags'] ) ) );
	}
}
add_action( 'save_post_project', 'kb_project_save_meta' );