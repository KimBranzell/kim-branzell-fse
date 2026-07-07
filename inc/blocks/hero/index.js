wp.domReady( () => {
	const { registerBlockType } = wp.blocks;
	const {
		useBlockProps,
		MediaPlaceholder,
		MediaUpload,
		MediaUploadCheck,
		InspectorControls,
	} = wp.blockEditor;
	const { PanelBody, TextControl, TextareaControl, SelectControl, ToggleControl, Button } =
		wp.components;
	const { __, sprintf } = wp.i18n;
	const el = wp.element.createElement;
	const Fragment = wp.element.Fragment;
	const ServerSideRender = wp.serverSideRender;

	const speak = ( wp.a11y && wp.a11y.speak )
		? wp.a11y.speak.bind( wp.a11y )
		: function () {};

	function updateCta( ctas, index, field, value ) {
		const newCtas = [ ...ctas ];
		if ( ! newCtas[ index ] ) {
			newCtas[ index ] = { text: '', url: '#' };
		}
		newCtas[ index ] = { ...newCtas[ index ], [ field ]: value };
		return newCtas;
	}

	try {
		registerBlockType( 'kim-branzell/hero', {
			edit: function HeroEdit( { attributes, setAttributes, isSelected } ) {
				const { title, intro, ctas, count, enabled, image, size } = attributes;
				const blockProps = useBlockProps();
				const hasImage = image?.url;

				const onSelectImage = ( media ) => {
					setAttributes( {
						image: { id: media.id, url: media.url, alt: media.alt || media.title || '' },
					} );
					speak( __( 'Hero image updated.', 'kim-branzell' ) );
				};

				const onRemoveImage = () => {
					if ( window.confirm( __( 'Remove the hero image?', 'kim-branzell' ) ) ) {
						setAttributes( { image: null } );
						speak( __( 'Hero image removed.', 'kim-branzell' ) );
					}
				};

				// ─── Helper to render inline edit fields ───
				function renderEditFields() {
					return el(
						'div',
						{ className: 'block-edit-form' },
						el( 'div', { className: 'block-edit-form__section' },
							el( 'p', { className: 'block-edit-form__section-label' }, __( 'Heading', 'kim-branzell' ) ),
							el( TextControl, {
								label: __( 'Title', 'kim-branzell' ),
								value: title,
								onChange: ( val ) => setAttributes( { title: val } ),
							} )
						),
						el( 'div', { className: 'block-edit-form__section' },
							el( 'p', { className: 'block-edit-form__section-label' }, __( 'Intro Text', 'kim-branzell' ) ),
							el( TextareaControl, {
								label: __( 'Intro', 'kim-branzell' ),
								help: __( 'The paragraph displayed below the title.', 'kim-branzell' ),
								value: intro,
								onChange: ( val ) => setAttributes( { intro: val } ),
							} )
						),
						el( 'div', { className: 'block-edit-form__section' },
							el( 'p', { className: 'block-edit-form__section-label' }, __( 'Call-to-Action Buttons', 'kim-branzell' ) ),
							[ 0, 1 ].map( ( i ) =>
								el(
									'div',
									{ key: i, className: 'block-edit-form__row', style: { marginBottom: '0.75rem' } },
									el( TextControl, {
										label: sprintf( __( 'Button %d Text', 'kim-branzell' ), i + 1 ),
										value: ( ctas[ i ] && ctas[ i ].text ) || '',
										onChange: ( val ) =>
											setAttributes( { ctas: updateCta( ctas, i, 'text', val ) } ),
									} ),
									el( TextControl, {
										label: sprintf( __( 'Button %d URL', 'kim-branzell' ), i + 1 ),
										value: ( ctas[ i ] && ctas[ i ].url ) || '#',
										onChange: ( val ) =>
											setAttributes( { ctas: updateCta( ctas, i, 'url', val ) } ),
									} )
								)
							)
						)
					);
				}

				// ─── Canvas content ───
				const previewContent = ! enabled
					? el(
							'div',
							{ className: 'editor-disabled-badge' },
							el( 'span', { className: 'dashicons dashicons-hidden', 'aria-hidden': 'true' } ),
							__( 'Hero is hidden on the front end.', 'kim-branzell' )
					  )
					: isSelected
						? el(
								Fragment,
								null,
								// Compact SSR preview
								el(
									'div',
									{ className: 'block-edit-form__preview' },
									el( ServerSideRender, { block: 'kim-branzell/hero', attributes: attributes } )
								),
								// Inline edit form
								renderEditFields()
						  )
						: el( ServerSideRender, { block: 'kim-branzell/hero', attributes: attributes } );

				return el(
					Fragment,
					null,
					// ─── SIDEBAR: secondary controls only ───
					el(
						InspectorControls,
						null,
						el(
							PanelBody,
							{ title: __( 'Settings', 'kim-branzell' ), initialOpen: true },
							el( SelectControl, {
								label: __( 'Size', 'kim-branzell' ),
								value: size,
								options: [
									{ label: __( 'Small', 'kim-branzell' ), value: 'small' },
									{ label: __( 'Medium', 'kim-branzell' ), value: 'medium' },
									{ label: __( 'Large', 'kim-branzell' ), value: 'large' },
								],
								onChange: ( val ) => setAttributes( { size: val } ),
							} ),
							el( ToggleControl, {
								label: __( 'Show Hero Section', 'kim-branzell' ),
								checked: enabled,
								onChange: ( val ) => setAttributes( { enabled: val } ),
							} )
						),
						el(
							PanelBody,
							{ title: __( 'Image', 'kim-branzell' ), initialOpen: false },
							hasImage
								? el(
										Fragment,
										null,
										el( 'img', {
											src: image.url,
											alt: image.alt || '',
											style: { width: '100%', height: 'auto', maxHeight: '120px', objectFit: 'cover', borderRadius: '8px' },
										} ),
										el( 'div', { style: { display: 'flex', gap: '0.5rem', margin: '0.5rem 0' } },
											el( MediaUploadCheck, null,
												el( MediaUpload, {
													onSelect: onSelectImage,
													allowedTypes: [ 'image' ],
													value: image.id,
													render: ( { open } ) => el( Button, { onClick: open, variant: 'secondary', isSmall: true }, __( 'Replace', 'kim-branzell' ) ),
												} )
											),
											el( Button, { onClick: onRemoveImage, variant: 'tertiary', isDestructive: true, isSmall: true }, __( 'Remove', 'kim-branzell' ) )
										),
										el( TextControl, {
											label: __( 'Alt text', 'kim-branzell' ),
											value: image.alt || '',
											onChange: ( val ) => setAttributes( { image: { ...image, alt: val } } ),
										} )
								  )
								: el( MediaPlaceholder, {
										icon: 'format-image',
										labels: { title: __( 'Hero Image', 'kim-branzell' ), instructions: __( 'Upload or select a background image.', 'kim-branzell' ) },
										onSelect: onSelectImage,
										accept: 'image/*',
										allowedTypes: [ 'image' ],
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
		console.error( 'Hero block registration failed:', err );
	}
} );
