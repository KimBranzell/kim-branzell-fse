wp.domReady( () => {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, ToggleControl, TextControl, SelectControl } = wp.components;
	const { __ } = wp.i18n;
	const el = wp.element.createElement;
	const Fragment = wp.element.Fragment;
	const ServerSideRender = wp.serverSideRender;

	// Shown when the server render returns an empty string so the canvas
	// isn't blank while there are no published posts yet.
	const EmptyResponsePlaceholder = () =>
		el(
			'p',
			{ className: 'blog-posts__empty-notice' },
			__( 'No published posts yet. Add posts under Posts → Add New.', 'kim-branzell' )
		);

	try {
		registerBlockType( 'kim-branzell/blog-posts', {
			edit: function BlogPostsEdit( { attributes, setAttributes, isSelected } ) {
				const {
					eyebrow,
					heading,
					postsToShow,
					showCount,
					showDate,
					showReadingTime,
					showCategory,
					enabled,
					leadCount,
					leadImageSize,
					showExcerpt,
					showThumbnail,
					showAuthor,
					showAuthorAvatar,
					showComments,
					showReadMore,
					showViewAll,
					excerptLength,
				} = attributes;
				const blockProps = useBlockProps();

				// ─── Helper to render inline edit fields ───
				function renderEditFields() {
					return el(
						'div',
						{ className: 'block-edit-form' },
						el(
							'div',
							{ className: 'block-edit-form__section' },
							el( 'p', { className: 'block-edit-form__section-label' }, __( 'Header', 'kim-branzell' ) ),
							el( TextControl, {
								label: __( 'Eyebrow text', 'kim-branzell' ),
								value: eyebrow,
								onChange: ( val ) => setAttributes( { eyebrow: val } ),
							} ),
							el( TextControl, {
								label: __( 'Heading', 'kim-branzell' ),
								value: heading,
								onChange: ( val ) => setAttributes( { heading: val } ),
							} )
						)
					);
				}

				// ─── Canvas content ───
				const previewContent = ! enabled
					? el(
							'div',
							{ className: 'editor-disabled-badge' },
							el( 'span', { className: 'dashicons dashicons-hidden', 'aria-hidden': 'true' } ),
							__( 'Blog Posts is hidden on the front end.', 'kim-branzell' )
					  )
					: isSelected
						? el(
								Fragment,
								null,
								// Compact SSR preview
								el(
									'div',
									{ className: 'block-edit-form__preview' },
									el( ServerSideRender, {
										block: 'kim-branzell/blog-posts',
										attributes: attributes,
										EmptyResponsePlaceholder,
									} )
								),
								// Inline edit form
								renderEditFields()
						  )
						: el( ServerSideRender, {
								block: 'kim-branzell/blog-posts',
								attributes: attributes,
								EmptyResponsePlaceholder,
						  } );

				return el(
					Fragment,
					null,
					// ─── SIDEBAR: secondary controls only ───
					el(
						InspectorControls,
						null,
						el(
							PanelBody,
							{ title: __( 'Lead zone', 'kim-branzell' ), initialOpen: true },
							el( TextControl, {
								label: __( 'Lead card count', 'kim-branzell' ),
								type: 'number',
								min: 0,
								max: 10,
								value: leadCount,
								onChange: ( val ) =>
									setAttributes( { leadCount: parseInt( val, 10 ) || 0 } ),
							} ),
							el( SelectControl, {
								label: __( 'Lead image size', 'kim-branzell' ),
								value: leadImageSize,
								options: [
									{ label: __( 'Medium', 'kim-branzell' ), value: 'medium' },
									{ label: __( 'Large', 'kim-branzell' ), value: 'large' },
									{ label: __( 'Full', 'kim-branzell' ), value: 'full' },
									{ label: __( 'Wide optimized', 'kim-branzell' ), value: 'kb-blog-lead' },
								],
								onChange: ( val ) => setAttributes( { leadImageSize: val } ),
							} ),
							el( TextControl, {
								label: __( 'Excerpt length (words)', 'kim-branzell' ),
								type: 'number',
								min: 8,
								max: 80,
								value: excerptLength,
								onChange: ( val ) =>
									setAttributes( { excerptLength: parseInt( val, 10 ) || 28 } ),
							} ),
							el( ToggleControl, {
								label: __( 'Show excerpt', 'kim-branzell' ),
								checked: showExcerpt,
								onChange: ( val ) => setAttributes( { showExcerpt: val } ),
							} ),
							el( ToggleControl, {
								label: __( 'Show thumbnail', 'kim-branzell' ),
								checked: showThumbnail,
								onChange: ( val ) => setAttributes( { showThumbnail: val } ),
							} ),
							el( ToggleControl, {
								label: __( 'Show author', 'kim-branzell' ),
								checked: showAuthor,
								onChange: ( val ) => setAttributes( { showAuthor: val } ),
							} ),
							el( ToggleControl, {
								label: __( 'Show author avatar', 'kim-branzell' ),
								checked: showAuthorAvatar,
								onChange: ( val ) => setAttributes( { showAuthorAvatar: val } ),
							} ),
							el( ToggleControl, {
								label: __( 'Show read more', 'kim-branzell' ),
								checked: showReadMore,
								onChange: ( val ) => setAttributes( { showReadMore: val } ),
							} ),
							el( ToggleControl, {
								label: __( 'Show category', 'kim-branzell' ),
								checked: showCategory,
								onChange: ( val ) => setAttributes( { showCategory: val } ),
							} )
						),
						el(
							PanelBody,
							{ title: __( 'List zone', 'kim-branzell' ), initialOpen: false },
							el( ToggleControl, {
								label: __( 'Show date', 'kim-branzell' ),
								checked: showDate,
								onChange: ( val ) => setAttributes( { showDate: val } ),
							} ),
							el( ToggleControl, {
								label: __( 'Show reading time', 'kim-branzell' ),
								checked: showReadingTime,
								onChange: ( val ) => setAttributes( { showReadingTime: val } ),
							} ),
							el( ToggleControl, {
								label: __( 'Show comments', 'kim-branzell' ),
								checked: showComments,
								onChange: ( val ) => setAttributes( { showComments: val } ),
							} ),
							el( ToggleControl, {
								label: __( 'Show post count', 'kim-branzell' ),
								checked: showCount,
								onChange: ( val ) => setAttributes( { showCount: val } ),
							} ),
							el( ToggleControl, {
								label: __( 'Show view all link', 'kim-branzell' ),
								checked: showViewAll,
								onChange: ( val ) => setAttributes( { showViewAll: val } ),
							} )
						),
						el(
							PanelBody,
							{ title: __( 'Query', 'kim-branzell' ), initialOpen: false },
							el( TextControl, {
								label: __( 'Posts to show', 'kim-branzell' ),
								help: __( 'Set to 0 to show all published posts.', 'kim-branzell' ),
								type: 'number',
								min: 0,
								value: postsToShow,
								onChange: ( val ) =>
									setAttributes( { postsToShow: parseInt( val, 10 ) || 0 } ),
							} ),
							el( ToggleControl, {
								label: __( 'Show Blog Posts Section', 'kim-branzell' ),
								checked: enabled,
								onChange: ( val ) => setAttributes( { enabled: val } ),
							} )
						)
					),
					// ─── CANVAS ───
					el( 'div', blockProps, previewContent )
				);
			},
			save: () => null,
		} );
	} catch ( err ) {
		console.error( 'Blog Posts block registration failed:', err );
	}
} );
