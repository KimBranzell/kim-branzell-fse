wp.domReady( () => {
	const { registerBlockType } = wp.blocks;
	const {
		useBlockProps,
		MediaPlaceholder,
		MediaUpload,
		MediaUploadCheck,
		InspectorControls,
	} = wp.blockEditor;
	const { PanelBody, TextControl, SelectControl, Button } =
		wp.components;
	const { __ } = wp.i18n;
	const el = wp.element.createElement;
	const Fragment = wp.element.Fragment;

	const speak = ( wp.a11y && wp.a11y.speak )
		? wp.a11y.speak.bind( wp.a11y )
		: function () {};

	// Size options for bento items
	const SIZE_OPTIONS = [
		{ label: __( 'Small (1 column)', 'kim-branzell' ), value: 'small' },
		{ label: __( 'Medium (2 columns)', 'kim-branzell' ), value: 'medium' },
		{ label: __( 'Large (3 columns)', 'kim-branzell' ), value: 'large' },
	];

	try {
		registerBlockType( 'kim-branzell/about', {
			edit: function AboutEdit( { attributes, setAttributes } ) {
				const { portrait, eyebrow, items } = attributes;

				const blockProps = useBlockProps( {
					className: 'about-editor',
				} );

				const hasPortrait = portrait?.url;

				// Portrait handlers
				const onSelectPortrait = ( media ) => {
					setAttributes( {
						portrait: {
							id: media.id,
							url: media.url,
							alt: media.alt || media.title || '',
						},
					} );
					speak( __( 'Portrait updated.', 'kim-branzell' ) );
				};

				const onRemovePortrait = () => {
					if ( window.confirm( __( 'Remove the portrait?', 'kim-branzell' ) ) ) {
						setAttributes( { portrait: null } );
						speak( __( 'Portrait removed.', 'kim-branzell' ) );
					}
				};

				// Item update handlers
				const updateItem = ( index, field, value ) => {
					const newItems = [ ...items ];
					newItems[ index ] = { ...newItems[ index ], [ field ]: value };
					setAttributes( { items: newItems } );
				};

				const addItem = () => {
					const newItem = {
						id: 'item-' + Date.now(),
						title: __( 'New Item', 'kim-branzell' ),
						content: __( 'Add your content here.', 'kim-branzell' ),
						size: 'medium',
					};
					setAttributes( { items: [ ...items, newItem ] } );
					speak( __( 'New bento item added.', 'kim-branzell' ) );
				};

				const removeItem = ( index ) => {
					if ( items.length <= 1 ) {
						return; // Keep at least one item
					}
					const newItems = items.filter( ( _, i ) => i !== index );
					setAttributes( { items: newItems } );
					speak( __( 'Bento item removed.', 'kim-branzell' ) );
				};

				const moveItem = ( index, direction ) => {
					const newItems = [ ...items ];
					const targetIndex = index + direction;
					if ( targetIndex < 0 || targetIndex >= newItems.length ) {
						return; // Out of bounds
					}
					[ newItems[ index ], newItems[ targetIndex ] ] = [ newItems[ targetIndex ], newItems[ index ] ];
					setAttributes( { items: newItems } );
				};

				// Render a single bento item in the preview
				const renderItemPreview = ( item, index ) => {
					const sizeClass = 'about-editor__bento-item--' + item.size;
					return el(
						'div',
						{
							key: item.id || index,
							className: 'about-editor__bento-item ' + sizeClass,
						},
						el(
							'div',
							{ className: 'about-editor__bento-item-header' },
							el( 'h3', { className: 'about-editor__bento-item-title' }, item.title || __( 'Item Title', 'kim-branzell' ) )
						),
						el(
							'p',
							{ className: 'about-editor__bento-item-content' },
							item.content || __( 'Add your content here.', 'kim-branzell' )
						),
						el(
							'div',
							{ className: 'about-editor__bento-item-size-badge' },
							item.size
						)
					);
				};

				return el(
					Fragment,
					null,
					// ─── SIDEBAR: Inspector Controls ───
					el(
						InspectorControls,
						null,
						// Panel 1: Eyebrow
						el(
							PanelBody,
							{ title: __( 'Section Info', 'kim-branzell' ), initialOpen: true },
							el( TextControl, {
								label: __( 'Section Label', 'kim-branzell' ),
								help: __( 'Appears in the eyebrow bar (e.g., "02 About").', 'kim-branzell' ),
								value: eyebrow.sectionLabel,
								onChange: ( val ) =>
									setAttributes( { eyebrow: { ...eyebrow, sectionLabel: val } } ),
							} )
						),
						// Panel 2: Portrait
						el(
							PanelBody,
							{ title: __( 'Portrait', 'kim-branzell' ), initialOpen: true },
							hasPortrait
								? el(
										Fragment,
										null,
										el(
											'div',
											{ style: { marginBottom: '1rem' } },
											el( 'img', {
												src: portrait.url,
												alt: portrait.alt || '',
												style: {
													width: '100%',
													height: 'auto',
													maxHeight: '200px',
													objectFit: 'cover',
													borderRadius: '8px',
												},
											} )
										),
										el(
											'div',
											{ style: { display: 'flex', gap: '0.5rem', marginBottom: '1rem', flexWrap: 'wrap' } },
											el(
												MediaUploadCheck,
												null,
												el( MediaUpload, {
													onSelect: onSelectPortrait,
													allowedTypes: [ 'image' ],
													value: portrait.id,
													render: ( { open } ) =>
														el( Button, { onClick: open, variant: 'secondary', isSmall: true },
															__( 'Replace', 'kim-branzell' )
														),
												} )
											),
											el( Button, {
												onClick: onRemovePortrait,
												variant: 'tertiary',
												isDestructive: true,
												isSmall: true,
											}, __( 'Remove', 'kim-branzell' ) )
										),
										el( TextControl, {
											label: __( 'Alternative Text', 'kim-branzell' ),
											help: __( 'Describe the portrait for screen readers.', 'kim-branzell' ),
											value: portrait.alt || '',
											onChange: ( val ) =>
												setAttributes( { portrait: { ...portrait, alt: val } } ),
										} )
								  )
								: el(
										MediaPlaceholder,
										{
											labels: {
												title: __( 'Portrait', 'kim-branzell' ),
												instructions: __( 'Upload or select a portrait photo.', 'kim-branzell' ),
											},
											onSelect: onSelectPortrait,
											accept: 'image/*',
											allowedTypes: [ 'image' ],
										}
								  )
						),
						// Panel 3: Bento Items
						el(
							PanelBody,
							{ title: __( 'Bento Items', 'kim-branzell' ), initialOpen: true },
							el( 'p', {
								style: {
									fontSize: '0.75rem',
									color: '#757575',
									marginBottom: '0.75rem',
								},
							}, __( 'Configure the bento boxes. Click a box to edit, use arrows to reorder.', 'kim-branzell' ) ),
							...items.map( ( item, index ) =>
								el(
									PanelBody,
									{
										key: item.id || index,
										title: item.title || __( 'Item ', 'kim-branzell' ) + ( index + 1 ),
										initialOpen: index === 0,
									},
									el(
										'div',
										{ style: { display: 'flex', gap: '0.25rem', marginBottom: '0.75rem', flexWrap: 'wrap', alignItems: 'center' } },
										el( Button, {
											onClick: () => moveItem( index, -1 ),
											variant: 'secondary',
											isSmall: true,
											disabled: index === 0,
											'aria-label': __( 'Move up', 'kim-branzell' ),
										}, '↑' ),
										el( Button, {
											onClick: () => moveItem( index, 1 ),
											variant: 'secondary',
											isSmall: true,
											disabled: index === items.length - 1,
											'aria-label': __( 'Move down', 'kim-branzell' ),
										}, '↓' ),
										items.length > 1 && el( Button, {
											onClick: () => removeItem( index ),
											variant: 'tertiary',
											isDestructive: true,
											isSmall: true,
										}, __( 'Remove', 'kim-branzell' ) )
									),
									el( TextControl, {
										label: __( 'Title', 'kim-branzell' ),
										value: item.title,
										onChange: ( val ) => updateItem( index, 'title', val ),
									} ),
									el( TextControl, {
										label: __( 'Content', 'kim-branzell' ),
										value: item.content,
										onChange: ( val ) => updateItem( index, 'content', val ),
									} ),
									el( SelectControl, {
										label: __( 'Size', 'kim-branzell' ),
										value: item.size,
										options: SIZE_OPTIONS,
										onChange: ( val ) => updateItem( index, 'size', val ),
									} )
								)
							),
							el( Button, {
								onClick: addItem,
								variant: 'secondary',
								isSmall: true,
								style: { marginTop: '0.5rem' },
							}, __( 'Add Bento Item', 'kim-branzell' ) )
						)
					),
					// ─── PREVIEW AREA ───
					el(
						'div',
						blockProps,
						// Eyebrow bar
						el(
							'div',
							{ className: 'about-editor__eyebrow' },
							el( 'span', { className: 'about-editor__eyebrow-section' },
								eyebrow.sectionLabel || __( 'Section Label', 'kim-branzell' )
							),
							el( 'span', { className: 'about-editor__eyebrow-site' },
								'Site Name'
							),
							el( 'span', { className: 'about-editor__eyebrow-year' },
								eyebrow.year || ''
							)
						),
						// Main grid: portrait + bento boxes
						el(
							'div',
							{ className: 'about-editor__grid' },
							// Portrait column (left)
							el(
								'div',
								{ className: 'about-editor__portrait-column' },
								hasPortrait
									? el( 'img', {
											src: portrait.url,
											alt: portrait.alt || '',
											className: 'about-editor__portrait',
									  } )
									: el(
											'div',
											{ className: 'about-editor__portrait-placeholder' },
											__( 'Add portrait in sidebar', 'kim-branzell' )
									  )
							),
							// Bento grid column (right)
							el(
								'div',
								{ className: 'about-editor__bento-column' },
								el(
									'div',
									{ className: 'about-editor__bento-grid' },
									items.map( ( item, index ) => renderItemPreview( item, index ) )
								)
							)
						)
					)
				);
			},
			save: () => null,
		} );
	} catch ( err ) {
		console.error( 'About block registration failed:', err );
	}
} );
