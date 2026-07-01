wp.domReady( () => {
	const { registerBlockType } = wp.blocks;
	const {
		useBlockProps,
		MediaPlaceholder,
		MediaUpload,
		MediaUploadCheck,
		InspectorControls,
	} = wp.blockEditor;
	const { PanelBody, TextControl, SelectControl, ToggleControl, Button, Icon } =
		wp.components;
	const { __ } = wp.i18n;
	const el = wp.element.createElement;
	const Fragment = wp.element.Fragment;

	const speak = ( wp.a11y && wp.a11y.speak )
		? wp.a11y.speak.bind( wp.a11y )
		: function () {};

	try {
		registerBlockType( 'kim-branzell/hero', {
			edit: function HeroEdit( { attributes, setAttributes } ) {
				const { title, count, enabled, image, size } = attributes;

				const blockProps = useBlockProps( {
					className: 'hero-editor hero-editor--size-' + size,
				} );

				const hasImage = image?.url;
				const titleDisplay = title || __( 'Add a title', 'kim-branzell' );
				const titleEmptyClass = title ? '' : ' hero-editor__preview-title--empty';

				const onSelectImage = ( media ) => {
					setAttributes( {
						image: {
							id: media.id,
							url: media.url,
							alt: media.alt || media.title || '',
						},
					} );
					speak( __( 'Hero image updated.', 'kim-branzell' ) );
				};

				const onRemoveImage = () => {
					setAttributes( { image: null } );
					speak( __( 'Hero image removed.', 'kim-branzell' ) );
				};

				const onConfirmRemove = () => {
					if ( window.confirm( __( 'Remove the hero image?', 'kim-branzell' ) ) ) {
						onRemoveImage();
					}
				};

				const previewClass = 'hero-editor__preview' +
					( hasImage ? ' hero-editor__preview--has-image' : '' );

				return el(
					Fragment,
					null,
					// ─── SIDEBAR: Inspector Controls ───
					el(
						InspectorControls,
						null,
						// Panel 1: Content
						el(
							PanelBody,
							{ title: __( 'Content', 'kim-branzell' ), initialOpen: true },
							el( TextControl, {
								label: __( 'Title', 'kim-branzell' ),
								value: title,
								onChange: ( val ) => setAttributes( { title: val } ),
							} ),
							el( TextControl, {
								label: __( 'Count', 'kim-branzell' ),
								type: 'number',
								value: count,
								help: __( 'Number displayed prominently in the hero.', 'kim-branzell' ),
								onChange: ( val ) => setAttributes( { count: parseInt( val, 10 ) || 0 } ),
							} ),
							el( SelectControl, {
								label: __( 'Size', 'kim-branzell' ),
								value: size,
								help: __( 'Controls the hero height.', 'kim-branzell' ),
								options: [
									{ label: __( 'Small', 'kim-branzell' ), value: 'small' },
									{ label: __( 'Medium', 'kim-branzell' ), value: 'medium' },
									{ label: __( 'Large', 'kim-branzell' ), value: 'large' },
								],
								onChange: ( val ) => setAttributes( { size: val } ),
							} )
						),
						// Panel 2: Image
						el(
							PanelBody,
							{ title: __( 'Image', 'kim-branzell' ), initialOpen: true },
							hasImage
								? el(
										Fragment,
										null,
										el(
											'div',
											{ style: { marginBottom: '1rem' } },
											el( 'img', {
												src: image.url,
												alt: image.alt || '',
												style: {
													width: '100%',
													height: 'auto',
													maxHeight: '120px',
													objectFit: 'cover',
													borderRadius: '8px',
												},
											} )
										),
										el(
											'div',
											{ style: { display: 'flex', gap: '0.5rem', marginBottom: '1rem' } },
											el(
												MediaUploadCheck,
												null,
												el( MediaUpload, {
													onSelect: onSelectImage,
													allowedTypes: [ 'image' ],
													value: image.id,
													render: ( { open } ) =>
														el( Button, { onClick: open, variant: 'secondary', isSmall: true },
															__( 'Replace Image', 'kim-branzell' )
														),
												} )
											),
											el( Button, {
												onClick: onConfirmRemove,
												variant: 'tertiary',
												isDestructive: true,
												isSmall: true,
											}, __( 'Remove', 'kim-branzell' ) )
										),
										el( TextControl, {
											label: __( 'Alternative Text', 'kim-branzell' ),
											help: __( 'Describe the image for screen readers. Leave empty if purely decorative.', 'kim-branzell' ),
											value: image.alt || '',
											onChange: ( val ) =>
												setAttributes( { image: { ...image, alt: val } } ),
										} )
								  )
								: el(
										MediaPlaceholder,
										{
											icon: 'format-image',
											labels: {
												title: __( 'Hero Image', 'kim-branzell' ),
												instructions: __( 'Upload or select a background image for the hero section.', 'kim-branzell' ),
											},
											onSelect: onSelectImage,
											accept: 'image/*',
											allowedTypes: [ 'image' ],
										}
								  )
						),
						// Panel 3: Visibility
						el(
							PanelBody,
							{ title: __( 'Visibility', 'kim-branzell' ), initialOpen: false },
							el( ToggleControl, {
								label: __( 'Show Hero Section', 'kim-branzell' ),
								checked: enabled,
								help: enabled
									? __( 'Hero is visible on the front end.', 'kim-branzell' )
									: __( 'Hero is hidden on the front end.', 'kim-branzell' ),
								onChange: ( val ) => setAttributes( { enabled: val } ),
							} )
						)
					),
					// ─── PREVIEW AREA ───
					el(
						'div',
						blockProps,
						// Richer preview: hero card with background, title, count badge
						el(
							'div',
							{ className: previewClass },
							hasImage && el( 'div', { className: 'hero-editor__preview-overlay' } ),
							el(
								'div',
								{ className: 'hero-editor__preview-content' },
								el(
									'h2',
									{ className: 'hero-editor__preview-title' + titleEmptyClass },
									titleDisplay
								),
								el(
									'div',
									{ className: 'hero-editor__preview-count' },
									String( count || 0 )
								)
							)
						),
						// Disabled badge
						! enabled && el(
							'div',
							{ className: 'hero-editor__disabled-badge' },
							el( 'span', { className: 'dashicons dashicons-hidden', 'aria-hidden': 'true' } ),
							__( 'Hero is hidden on the front end', 'kim-branzell' )
						)
					)
				);
			},
			save: () => null,
		} );
	} catch ( err ) {
		console.error( 'Hero block registration failed:', err );
	}
} );
