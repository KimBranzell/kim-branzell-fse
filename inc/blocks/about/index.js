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
	const ServerSideRender = wp.serverSideRender;

	const speak = ( wp.a11y && wp.a11y.speak )
		? wp.a11y.speak.bind( wp.a11y )
		: function () {};

	const SIZE_OPTIONS = [
		{ label: __( 'Small (1 col)', 'kim-branzell' ), value: 'small' },
		{ label: __( 'Medium (2 cols)', 'kim-branzell' ), value: 'medium' },
		{ label: __( 'Large (3 cols)', 'kim-branzell' ), value: 'large' },
	];

	try {
		registerBlockType( 'kim-branzell/about', {
			edit: function AboutEdit( { attributes, setAttributes, isSelected } ) {
				const { portrait, eyebrow, items } = attributes;
				const blockProps = useBlockProps();
				const hasPortrait = portrait?.url;

				const onSelectPortrait = ( media ) => {
					setAttributes( {
						portrait: { id: media.id, url: media.url, alt: media.alt || media.title || '' },
					} );
					speak( __( 'Portrait updated.', 'kim-branzell' ) );
				};

				const onRemovePortrait = () => {
					if ( window.confirm( __( 'Remove the portrait?', 'kim-branzell' ) ) ) {
						setAttributes( { portrait: null } );
						speak( __( 'Portrait removed.', 'kim-branzell' ) );
					}
				};

				const updateItem = ( index, field, value ) => {
					const newItems = [ ...items ];
					newItems[ index ] = { ...newItems[ index ], [ field ]: value };
					setAttributes( { items: newItems } );
				};

				const addItem = () => {
					setAttributes( {
						items: [ ...items, {
							id: 'item-' + Date.now(),
							title: __( 'New Item', 'kim-branzell' ),
							content: __( 'Add your content here.', 'kim-branzell' ),
							size: 'medium',
						} ],
					} );
					speak( __( 'New bento item added.', 'kim-branzell' ) );
				};

				const removeItem = ( index ) => {
					if ( items.length <= 1 ) return;
					setAttributes( { items: items.filter( ( _, i ) => i !== index ) } );
					speak( __( 'Bento item removed.', 'kim-branzell' ) );
				};

				const moveItem = ( index, direction ) => {
					const newItems = [ ...items ];
					const targetIndex = index + direction;
					if ( targetIndex < 0 || targetIndex >= newItems.length ) return;
					[ newItems[ index ], newItems[ targetIndex ] ] = [ newItems[ targetIndex ], newItems[ index ] ];
					setAttributes( { items: newItems } );
				};

				// ─── Inline edit fields ───
				function renderEditFields() {
					return el(
						'div',
						{ className: 'block-edit-form' },
						el( 'div', { className: 'block-edit-form__section' },
							el( 'p', { className: 'block-edit-form__section-label' }, __( 'Section Info', 'kim-branzell' ) ),
							el( TextControl, {
								label: __( 'Section Label', 'kim-branzell' ),
								help: __( 'e.g. "02 About"', 'kim-branzell' ),
								value: eyebrow.sectionLabel,
								onChange: ( val ) =>
									setAttributes( { eyebrow: { ...eyebrow, sectionLabel: val } } ),
							} )
						),
						el( 'div', { className: 'block-edit-form__section', style: { marginBottom: 0 } },
							el( 'p', { className: 'block-edit-form__section-label' }, __( 'Quick Tip', 'kim-branzell' ) ),
							el( 'p', { style: { fontSize: '0.8125rem', color: '#757575', margin: 0 } },
								__( 'Edit the portrait image and bento boxes in the sidebar.', 'kim-branzell' )
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
								el( ServerSideRender, { block: 'kim-branzell/about', attributes: attributes } )
							),
							renderEditFields()
					  )
					: el( ServerSideRender, { block: 'kim-branzell/about', attributes: attributes } );

				return el(
					Fragment,
					null,
					// ─── SIDEBAR ───
					el(
						InspectorControls,
						null,
						el( PanelBody, { title: __( 'Portrait', 'kim-branzell' ), initialOpen: true },
							hasPortrait
								? el( Fragment, null,
										el( 'img', {
											src: portrait.url, alt: portrait.alt || '',
											style: { width: '100%', height: 'auto', maxHeight: '200px', objectFit: 'cover', borderRadius: '8px' },
										} ),
										el( 'div', { style: { display: 'flex', gap: '0.5rem', margin: '0.5rem 0', flexWrap: 'wrap' } },
											el( MediaUploadCheck, null,
												el( MediaUpload, {
													onSelect: onSelectPortrait, allowedTypes: [ 'image' ], value: portrait.id,
													render: ( { open } ) => el( Button, { onClick: open, variant: 'secondary', isSmall: true }, __( 'Replace', 'kim-branzell' ) ),
												} )
											),
											el( Button, { onClick: onRemovePortrait, variant: 'tertiary', isDestructive: true, isSmall: true }, __( 'Remove', 'kim-branzell' ) )
										),
										el( TextControl, {
											label: __( 'Alt text', 'kim-branzell' ),
											value: portrait.alt || '',
											onChange: ( val ) => setAttributes( { portrait: { ...portrait, alt: val } } ),
										} )
								  )
								: el( MediaPlaceholder, {
										labels: { title: __( 'Portrait', 'kim-branzell' ), instructions: __( 'Upload or select a portrait photo.', 'kim-branzell' ) },
										onSelect: onSelectPortrait, accept: 'image/*', allowedTypes: [ 'image' ],
								  } )
						),
						el( PanelBody, { title: __( 'Bento Items', 'kim-branzell' ), initialOpen: false },
							el( 'p', { style: { fontSize: '0.75rem', color: '#757575', marginBottom: '0.75rem' } },
								__( 'Configure the bento boxes. Use arrows to reorder.', 'kim-branzell' ) ),
							...items.map( ( item, index ) =>
								el( PanelBody, {
									key: item.id || index,
									title: item.title || __( 'Item ', 'kim-branzell' ) + ( index + 1 ),
									initialOpen: false,
								},
									el( 'div', { style: { display: 'flex', gap: '0.25rem', marginBottom: '0.75rem', flexWrap: 'wrap', alignItems: 'center' } },
										el( Button, { onClick: () => moveItem( index, -1 ), variant: 'secondary', isSmall: true, disabled: index === 0, 'aria-label': __( 'Move up', 'kim-branzell' ) }, '\u2191' ),
										el( Button, { onClick: () => moveItem( index, 1 ), variant: 'secondary', isSmall: true, disabled: index === items.length - 1, 'aria-label': __( 'Move down', 'kim-branzell' ) }, '\u2193' ),
										items.length > 1 && el( Button, { onClick: () => removeItem( index ), variant: 'tertiary', isDestructive: true, isSmall: true }, __( 'Remove', 'kim-branzell' ) )
									),
									el( TextControl, { label: __( 'Title', 'kim-branzell' ), value: item.title, onChange: ( val ) => updateItem( index, 'title', val ) } ),
									el( TextControl, { label: __( 'Content', 'kim-branzell' ), value: item.content, onChange: ( val ) => updateItem( index, 'content', val ) } ),
									el( SelectControl, { label: __( 'Size', 'kim-branzell' ), value: item.size, options: SIZE_OPTIONS, onChange: ( val ) => updateItem( index, 'size', val ) } )
								)
							),
							el( Button, { onClick: addItem, variant: 'secondary', isSmall: true, style: { marginTop: '0.5rem' } }, __( 'Add Bento Item', 'kim-branzell' ) )
						)
					),
					// ─── CANVAS ───
					el( 'div', blockProps, canvasContent )
				);
			},
			save: () => null,
		} );
	} catch ( err ) {
		console.error( 'About block registration failed:', err );
	}
} );
