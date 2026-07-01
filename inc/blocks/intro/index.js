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

	const speak = ( wp.a11y && wp.a11y.speak )
		? wp.a11y.speak.bind( wp.a11y )
		: function () {};

	try {
		registerBlockType( 'kim-branzell/intro', {
			edit: function IntroEdit( { attributes, setAttributes } ) {
				const { eyebrow, headline, image, services } = attributes;

				const blockProps = useBlockProps( {
					className: 'intro-editor',
				} );

				const hasImage = image?.url;
				const headlineDisplay = headline || __( 'Add a headline', 'kim-branzell' );
				const headlineEmptyClass = headline ? '' : ' intro-editor__headline-preview--empty';

				const onSelectImage = ( media ) => {
					setAttributes( {
						image: {
							id: media.id,
							url: media.url,
							alt: media.alt || media.title || '',
						},
					} );
					speak( __( 'Image updated.', 'kim-branzell' ) );
				};

				const onRemoveImage = () => {
					setAttributes( { image: null } );
					speak( __( 'Image removed.', 'kim-branzell' ) );
				};

				const onConfirmRemove = () => {
					if ( window.confirm( __( 'Remove the image?', 'kim-branzell' ) ) ) {
						onRemoveImage();
					}
				};

				const updateService = ( index, field, value ) => {
					const newServices = [ ...services ];
					newServices[ index ] = { ...newServices[ index ], [ field ]: value };
					setAttributes( { services: newServices } );
				};

				// Check if a card has content beyond defaults
				const hasCardContent = ( svc, idx ) => {
					const defaults = [ 'Solar Energy', 'Hydro Energy', 'Geothermal Energy', 'Wind Energy' ];
					return svc.title && svc.title !== ( defaults[ idx ] || '' );
				};

				// Card preview for the editor layout
				const renderCardPreview = ( service, index ) => {
					const title = service.title || __( 'Card Title', 'kim-branzell' );
					const desc = service.description || __( 'Add a description for this service.', 'kim-branzell' );
					const empty = ! hasCardContent( service, index );
					const cardClass = 'intro-editor__card' +
						( service.inverted ? ' intro-editor__card--inverted' : '' ) +
						( empty ? ' intro-editor__card--empty' : '' );

					return el(
						'div',
						{ key: index, className: cardClass },
						el( 'h3', { className: 'intro-editor__card-title' }, title ),
						el( 'p', { className: 'intro-editor__card-desc' }, desc ),
						el( 'span', { className: 'intro-editor__card-link' }, 'View details' )
					);
				};

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
								label: __( 'Section Label', 'kim-branzell' ),
								help: __( 'Appears in the top eyebrow bar.', 'kim-branzell' ),
								value: eyebrow.sectionLabel,
								onChange: ( val ) =>
									setAttributes( { eyebrow: { ...eyebrow, sectionLabel: val } } ),
							} ),
							el( TextControl, {
								label: __( 'Year', 'kim-branzell' ),
								help: __( 'Displayed in the eyebrow bar.', 'kim-branzell' ),
								value: eyebrow.year,
								onChange: ( val ) =>
									setAttributes( { eyebrow: { ...eyebrow, year: val } } ),
							} ),
							el( TextControl, {
								label: __( 'Headline', 'kim-branzell' ),
								help: __( 'Main headline displayed below the eyebrow.', 'kim-branzell' ),
								value: headline,
								onChange: ( val ) => setAttributes( { headline: val } ),
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
											help: __( 'Describe the image for screen readers.', 'kim-branzell' ),
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
												title: __( 'Feature Image', 'kim-branzell' ),
												instructions: __( 'Upload or select an image for the intro section.', 'kim-branzell' ),
											},
											onSelect: onSelectImage,
											accept: 'image/*',
											allowedTypes: [ 'image' ],
										}
								  )
						),
						// Panel 3: Service Cards
						el(
							PanelBody,
							{ title: __( 'Service Cards', 'kim-branzell' ), initialOpen: true },
							el( 'p', {
								style: {
									fontSize: '0.75rem',
									color: '#757575',
									marginBottom: '0.75rem',
								},
							}, __( 'Configure up to four service cards shown in a 2×2 grid.', 'kim-branzell' ) ),
							...services.map( ( service, index ) =>
								el(
									PanelBody,
									{
										title: ( service.title && hasCardContent( service, index ) )
											? service.title
											: __( 'Card ', 'kim-branzell' ) + ( index + 1 ),
										initialOpen: index === 0,
									},
									el( TextControl, {
										label: __( 'Title', 'kim-branzell' ),
										value: service.title,
										onChange: ( val ) => updateService( index, 'title', val ),
									} ),
									el( TextControl, {
										label: __( 'Description', 'kim-branzell' ),
										value: service.description,
										onChange: ( val ) => updateService( index, 'description', val ),
									} ),
									el( TextControl, {
										label: __( 'Link', 'kim-branzell' ),
										value: service.link,
										onChange: ( val ) => updateService( index, 'link', val ),
									} ),
									el( ToggleControl, {
										label: __( 'Inverted (dark background)', 'kim-branzell' ),
										checked: service.inverted,
										onChange: ( val ) => updateService( index, 'inverted', val ),
									} )
								)
							)
						)
					),
					// ─── PREVIEW AREA ───
					el(
						'div',
						blockProps,
						// Eyebrow bar
						el(
							'div',
							{ className: 'intro-editor__eyebrow' },
							el( 'span', { className: 'intro-editor__eyebrow-section' },
								eyebrow.sectionLabel || __( 'Section Label', 'kim-branzell' )
							),
						el( 'span', { className: 'intro-editor__eyebrow-site' },
							'Site Name'
						),
							el( 'span', { className: 'intro-editor__eyebrow-year' },
								eyebrow.year || ''
							)
						),
						// Headline
						el(
							'h2',
							{ className: 'intro-editor__headline-preview' + headlineEmptyClass },
							headlineDisplay
						),
						// Two-column grid: image + cards
						el(
							'div',
							{ className: 'intro-editor__grid' },
							// Image column (left)
							el(
								'div',
								{ className: 'intro-editor__image-column' },
								hasImage
									? el( 'img', {
											src: image.url,
											alt: image.alt || '',
											className: 'intro-editor__image',
									  } )
									: el(
											'div',
											{ className: 'editor-placeholder-dotted' },
											__( 'Add a feature image in the sidebar', 'kim-branzell' )
									  )
							),
							// Cards column (right)
							el(
								'div',
								{ className: 'intro-editor__cards-column' },
								el(
									'div',
									{ className: 'intro-editor__cards-grid' },
									services.map( ( svc, i ) => renderCardPreview( svc, i ) )
								)
							)
						)
					)
				);
			},
			save: () => null,
		} );
	} catch ( err ) {
		console.error( 'Intro block registration failed:', err );
	}
} );
