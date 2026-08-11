wp.domReady( () => {
	const { registerBlockType } = wp.blocks;
	const {
		useBlockProps,
		MediaPlaceholder,
		MediaUpload,
		MediaUploadCheck,
		InspectorControls,
	} = wp.blockEditor;
	const {
		PanelBody,
		TextControl,
		TextareaControl,
		SelectControl,
		ToggleControl,
		RangeControl,
		Button,
	} = wp.components;
	const { __ } = wp.i18n;
	const el = wp.element.createElement;
	const Fragment = wp.element.Fragment;
	const ServerSideRender = wp.serverSideRender;

	const speak = ( wp.a11y && wp.a11y.speak )
		? wp.a11y.speak.bind( wp.a11y )
		: function () {};

	const BG_OPTIONS = [
		{ label: __( 'Coral — buttons', 'kim-branzell' ),  value: 'buttons' },
		{ label: __( 'Cream — main', 'kim-branzell' ),      value: 'main' },
		{ label: __( 'Tan — alternative', 'kim-branzell' ), value: 'alternative' },
		{ label: __( 'Brown — accent', 'kim-branzell' ),    value: 'accent' },
	];

	try {
		registerBlockType( 'kim-branzell/testimonial', {
			edit: function TestimonialEdit( { attributes, setAttributes, isSelected } ) {
				const { enabled, background, autoplay, interval, testimonials } = attributes;
				const blockProps = useBlockProps();

				const updateTestimonial = ( index, field, value ) => {
					const newTestimonials = [ ...testimonials ];
					newTestimonials[ index ] = { ...newTestimonials[ index ], [ field ]: value };
					setAttributes( { testimonials: newTestimonials } );
				};

				const updatePortrait = ( index, media ) => {
					const portraitData = media
						? { id: media.id, url: media.url, alt: media.alt || media.title || '' }
						: null;
					updateTestimonial( index, 'portrait', portraitData );
				};

				const addTestimonial = () => {
					if ( testimonials.length >= 6 ) {
						return;
					}
					setAttributes( {
						testimonials: [ ...testimonials, {
							id: 'testimonial-' + Date.now(),
							quote: '',
							authorName: '',
							authorRole: '',
							portrait: null,
						} ],
					} );
					speak( __( 'Testimonial added.', 'kim-branzell' ) );
				};

				const removeTestimonial = ( index ) => {
					if ( testimonials.length <= 1 ) {
						return;
					}
					setAttributes( { testimonials: testimonials.filter( ( _, i ) => i !== index ) } );
					speak( __( 'Testimonial removed.', 'kim-branzell' ) );
				};

				const moveTestimonial = ( index, direction ) => {
					const targetIndex = index + direction;
					if ( targetIndex < 0 || targetIndex >= testimonials.length ) {
						return;
					}
					const newTestimonials = [ ...testimonials ];
					[ newTestimonials[ index ], newTestimonials[ targetIndex ] ] =
						[ newTestimonials[ targetIndex ], newTestimonials[ index ] ];
					setAttributes( { testimonials: newTestimonials } );
				};

				// ─── Inline edit fields (shown when block is selected) ───
				function renderEditFields() {
					return el(
						'div',
						{ className: 'block-edit-form' },
						el( 'div', { className: 'block-edit-form__section' },
							el( 'p', { className: 'block-edit-form__section-label' }, __( 'Quick Tip', 'kim-branzell' ) ),
							el( 'p', { style: { fontSize: '0.8125rem', color: '#757575', margin: 0 } },
								__( 'Configure the background, autoplay, and testimonials in the sidebar.', 'kim-branzell' )
							)
						)
					);
				}

				// ─── Canvas content ───
				const canvasContent = isSelected
					? el(
							Fragment,
							null,
							el( 'div', { className: 'block-edit-form__preview' },
								el( ServerSideRender, { block: 'kim-branzell/testimonial', attributes: attributes } )
							),
							renderEditFields()
					  )
					: el( ServerSideRender, { block: 'kim-branzell/testimonial', attributes: attributes } );

				return el(
					Fragment,
					null,
					// ─── SIDEBAR ───
					el(
						InspectorControls,
						null,
						// Appearance panel
						el(
							PanelBody,
							{ title: __( 'Appearance', 'kim-branzell' ), initialOpen: true },
							el( SelectControl, {
								label: __( 'Background', 'kim-branzell' ),
								value: background,
								options: BG_OPTIONS,
								onChange: ( val ) => setAttributes( { background: val } ),
							} )
						),
						// Behavior panel
						el(
							PanelBody,
							{ title: __( 'Behavior', 'kim-branzell' ), initialOpen: false },
							el( ToggleControl, {
								label: __( 'Autoplay slides', 'kim-branzell' ),
								help: __( 'Autoplay pauses on hover, focus, and when the visitor’s system requests reduced motion.', 'kim-branzell' ),
								checked: autoplay,
								onChange: ( val ) => setAttributes( { autoplay: val } ),
							} ),
							autoplay && el( RangeControl, {
								label: __( 'Seconds per slide', 'kim-branzell' ),
								value: Math.round( interval / 1000 ),
								onChange: ( val ) => setAttributes( { interval: val * 1000 } ),
								min: 6,
								max: 30,
								step: 1,
								help: __( 'Minimum 6 seconds per slide for accessibility.', 'kim-branzell' ),
							} )
						),
						// Testimonials repeater panel
						el(
							PanelBody,
							{ title: __( 'Testimonials', 'kim-branzell' ), initialOpen: true },
							el( 'p', { style: { fontSize: '0.75rem', color: '#757575', marginBottom: '0.75rem' } },
								__( 'Add, reorder, or remove testimonials. Max 6.', 'kim-branzell' ) ),
							...testimonials.map( ( testimonial, index ) =>
								el( PanelBody, {
									key: testimonial.id || index,
									title: testimonial.authorName || ( __( 'Testimonial ', 'kim-branzell' ) + ( index + 1 ) ),
									initialOpen: false,
								},
									// Reorder / remove bar
									el( 'div', { style: { display: 'flex', gap: '0.25rem', marginBottom: '0.75rem', flexWrap: 'wrap', alignItems: 'center' } },
										el( Button, {
											onClick: () => moveTestimonial( index, -1 ),
											variant: 'secondary',
											isSmall: true,
											disabled: index === 0,
											'aria-label': __( 'Move up', 'kim-branzell' ),
										}, '\u2191' ),
										el( Button, {
											onClick: () => moveTestimonial( index, 1 ),
											variant: 'secondary',
											isSmall: true,
											disabled: index === testimonials.length - 1,
											'aria-label': __( 'Move down', 'kim-branzell' ),
										}, '\u2193' ),
										testimonials.length > 1 && el( Button, {
											onClick: () => removeTestimonial( index ),
											variant: 'tertiary',
											isDestructive: true,
											isSmall: true,
										}, __( 'Remove', 'kim-branzell' ) )
									),
									// Quote
									el( TextareaControl, {
										label: __( 'Quote', 'kim-branzell' ),
										value: testimonial.quote,
										onChange: ( val ) => updateTestimonial( index, 'quote', val ),
									} ),
									// Author name
									el( TextControl, {
										label: __( 'Author name', 'kim-branzell' ),
										value: testimonial.authorName,
										onChange: ( val ) => updateTestimonial( index, 'authorName', val ),
									} ),
									// Author role
									el( TextControl, {
										label: __( 'Author role', 'kim-branzell' ),
										value: testimonial.authorRole,
										onChange: ( val ) => updateTestimonial( index, 'authorRole', val ),
									} ),
									// Portrait uploader
									el( 'div', { style: { marginTop: '0.75rem' } },
										el( 'p', { style: { fontSize: '0.75rem', marginBottom: '0.5rem' } },
											__( 'Portrait (optional)', 'kim-branzell' ) ),
										testimonial.portrait && testimonial.portrait.url
											? el( Fragment, null,
													el( 'img', {
														src: testimonial.portrait.url,
														alt: testimonial.portrait.alt || '',
														style: { width: '100%', height: 'auto', maxHeight: '120px', objectFit: 'cover', borderRadius: '8px' },
													} ),
													el( 'div', { style: { display: 'flex', gap: '0.5rem', margin: '0.5rem 0' } },
														el( MediaUploadCheck, null,
															el( MediaUpload, {
																onSelect: ( media ) => updatePortrait( index, media ),
																allowedTypes: [ 'image' ],
																render: ( { open } ) => el( Button, {
																	onClick: open,
																	variant: 'secondary',
																	isSmall: true,
																}, __( 'Replace', 'kim-branzell' ) ),
															} )
														),
														el( Button, {
															onClick: () => updatePortrait( index, null ),
															variant: 'tertiary',
															isDestructive: true,
															isSmall: true,
														}, __( 'Remove', 'kim-branzell' ) )
													)
											  )
											: el( MediaPlaceholder, {
													labels: {
														title: __( 'Portrait', 'kim-branzell' ),
														instructions: __( 'Upload a portrait photo.', 'kim-branzell' ),
													},
													onSelect: ( media ) => updatePortrait( index, media ),
													accept: 'image/*',
													allowedTypes: [ 'image' ],
													disableMediaButtons: false,
											  } )
									)
								)
							),
							testimonials.length < 6 && el( Button, {
								onClick: addTestimonial,
								variant: 'secondary',
								isSmall: true,
								style: { marginTop: '0.5rem' },
							}, __( 'Add Testimonial', 'kim-branzell' ) )
						)
					),
					// ─── CANVAS ───
					el( 'div', blockProps, canvasContent )
				);
			},
			save: () => null,
		} );
	} catch ( err ) {
		console.error( 'Testimonial block registration failed:', err );
	}
} );
