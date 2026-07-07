wp.domReady( () => {
	const { registerBlockType } = wp.blocks;
	const {
		useBlockProps,
		MediaPlaceholder,
		MediaUpload,
		MediaUploadCheck,
		InspectorControls,
	} = wp.blockEditor;
	const { PanelBody, TextControl, ToggleControl, Button } =
		wp.components;
	const { __ } = wp.i18n;
	const el = wp.element.createElement;
	const Fragment = wp.element.Fragment;
	const ServerSideRender = wp.serverSideRender;

	const speak = ( wp.a11y && wp.a11y.speak )
		? wp.a11y.speak.bind( wp.a11y )
		: function () {};

	try {
		registerBlockType( 'kim-branzell/intro', {
			edit: function IntroEdit( { attributes, setAttributes, isSelected } ) {
				const { eyebrow, headline, image, services } = attributes;
				const blockProps = useBlockProps();
				const hasImage = image?.url;

				const onSelectImage = ( media ) => {
					setAttributes( {
						image: { id: media.id, url: media.url, alt: media.alt || media.title || '' },
					} );
					speak( __( 'Image updated.', 'kim-branzell' ) );
				};

				const onRemoveImage = () => {
					if ( window.confirm( __( 'Remove the image?', 'kim-branzell' ) ) ) {
						setAttributes( { image: null } );
						speak( __( 'Image removed.', 'kim-branzell' ) );
					}
				};

				const updateService = ( index, field, value ) => {
					const newServices = [ ...services ];
					newServices[ index ] = { ...newServices[ index ], [ field ]: value };
					setAttributes( { services: newServices } );
				};

				const hasCardContent = ( svc, idx ) => {
					const defaults = [ 'Solar Energy', 'Hydro Energy', 'Geothermal Energy', 'Wind Energy' ];
					return svc.title && svc.title !== ( defaults[ idx ] || '' );
				};

				// ─── Inline edit fields ───
				function renderEditFields() {
					return el(
						'div',
						{ className: 'block-edit-form' },
						el( 'div', { className: 'block-edit-form__section' },
							el( 'p', { className: 'block-edit-form__section-label' }, __( 'Section Info', 'kim-branzell' ) ),
							el( 'div', { className: 'block-edit-form__row' },
								el( TextControl, {
									label: __( 'Section Label', 'kim-branzell' ),
									help: __( 'e.g. "01 Introduction"', 'kim-branzell' ),
									value: eyebrow.sectionLabel,
									onChange: ( val ) =>
										setAttributes( { eyebrow: { ...eyebrow, sectionLabel: val } } ),
								} ),
								el( TextControl, {
									label: __( 'Year', 'kim-branzell' ),
									value: eyebrow.year,
									onChange: ( val ) =>
										setAttributes( { eyebrow: { ...eyebrow, year: val } } ),
								} )
							)
						),
						el( 'div', { className: 'block-edit-form__section' },
							el( 'p', { className: 'block-edit-form__section-label' }, __( 'Headline', 'kim-branzell' ) ),
							el( TextControl, {
								label: __( 'Headline', 'kim-branzell' ),
								help: __( 'Main headline displayed below the eyebrow.', 'kim-branzell' ),
								value: headline,
								onChange: ( val ) => setAttributes( { headline: val } ),
							} )
						)
					);
				}

				// ─── Canvas content ───
				const canvasContent = isSelected
					? el(
							Fragment,
							null,
							el( 'div', { className: 'block-edit-form__preview' },
								el( ServerSideRender, { block: 'kim-branzell/intro', attributes: attributes } )
							),
							renderEditFields()
					  )
					: el( ServerSideRender, { block: 'kim-branzell/intro', attributes: attributes } );

				return el(
					Fragment,
					null,
					// ─── SIDEBAR: image + service cards ───
					el(
						InspectorControls,
						null,
						el(
							PanelBody,
							{ title: __( 'Image', 'kim-branzell' ), initialOpen: true },
							hasImage
								? el(
										Fragment,
										null,
										el( 'img', {
											src: image.url, alt: image.alt || '',
											style: { width: '100%', height: 'auto', maxHeight: '120px', objectFit: 'cover', borderRadius: '8px' },
										} ),
										el( 'div', { style: { display: 'flex', gap: '0.5rem', margin: '0.5rem 0' } },
											el( MediaUploadCheck, null,
												el( MediaUpload, {
													onSelect: onSelectImage, allowedTypes: [ 'image' ], value: image.id,
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
										labels: { title: __( 'Feature Image', 'kim-branzell' ), instructions: __( 'Upload or select an image.', 'kim-branzell' ) },
										onSelect: onSelectImage, accept: 'image/*', allowedTypes: [ 'image' ],
								  } )
						),
						el(
							PanelBody,
							{ title: __( 'Service Cards', 'kim-branzell' ), initialOpen: false },
							el( 'p', { style: { fontSize: '0.75rem', color: '#757575', marginBottom: '0.75rem' } },
								__( 'Configure up to four service cards shown in a 2x2 grid.', 'kim-branzell' ) ),
							...services.map( ( service, index ) =>
								el( PanelBody, {
									title: ( service.title && hasCardContent( service, index ) )
										? service.title
										: __( 'Card ', 'kim-branzell' ) + ( index + 1 ),
									initialOpen: false,
								},
									el( TextControl, { label: __( 'Title', 'kim-branzell' ), value: service.title, onChange: ( val ) => updateService( index, 'title', val ) } ),
									el( TextControl, { label: __( 'Description', 'kim-branzell' ), value: service.description, onChange: ( val ) => updateService( index, 'description', val ) } ),
									el( TextControl, { label: __( 'Link', 'kim-branzell' ), value: service.link, onChange: ( val ) => updateService( index, 'link', val ) } ),
									el( ToggleControl, { label: __( 'Inverted (dark background)', 'kim-branzell' ), checked: service.inverted, onChange: ( val ) => updateService( index, 'inverted', val ) } )
								)
							)
						)
					),
					// ─── CANVAS ───
					el( 'div', blockProps, canvasContent )
				);
			},
			save: () => null,
		} );
	} catch ( err ) {
		console.error( 'Intro block registration failed:', err );
	}
} );
