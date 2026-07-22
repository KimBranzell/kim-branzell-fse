wp.domReady( () => {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, TextControl, ToggleControl } = wp.components;
	const { __ } = wp.i18n;
	const el = wp.element.createElement;
	const Fragment = wp.element.Fragment;
	const ServerSideRender = wp.serverSideRender;

	// wp.a11y is not a declared script dependency, so guard the speak call.
	const speak = ( wp.a11y && wp.a11y.speak )
		? wp.a11y.speak.bind( wp.a11y )
		: function () {};

	// Shown when the server render returns an empty string so the canvas
	// isn't blank while there are no published projects yet.
	const EmptyResponsePlaceholder = () =>
		el(
			'p',
			{ className: 'project-index__empty-notice' },
			__( 'No published projects yet. Add projects under Projects → Add New.', 'kim-branzell' )
		);

	try {
		registerBlockType( 'kim-branzell/project-index', {
			edit: function ProjectIndexEdit( { attributes, setAttributes, isSelected } ) {
				const { eyebrow, heading, postsToShow, showCount, enabled } = attributes;
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
							__( 'Project Index is hidden on the front end.', 'kim-branzell' )
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
										block: 'kim-branzell/project-index',
										attributes: attributes,
										EmptyResponsePlaceholder,
									} )
								),
								// Inline edit form
								renderEditFields()
						  )
						: el( ServerSideRender, {
								block: 'kim-branzell/project-index',
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
							{ title: __( 'Settings', 'kim-branzell' ), initialOpen: true },
							el( ToggleControl, {
								label: __( 'Show project count', 'kim-branzell' ),
								checked: showCount,
								onChange: ( val ) => setAttributes( { showCount: val } ),
							} ),
							el( TextControl, {
								label: __( 'Projects to show', 'kim-branzell' ),
								help: __( 'Set to 0 to show all published projects.', 'kim-branzell' ),
								type: 'number',
								min: 0,
								value: postsToShow,
								onChange: ( val ) =>
									setAttributes( { postsToShow: parseInt( val, 10 ) || 0 } ),
							} ),
							el( ToggleControl, {
								label: __( 'Show Project Index Section', 'kim-branzell' ),
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
		console.error( 'Project Index block registration failed:', err );
	}
} );