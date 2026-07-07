wp.domReady( () => {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, TextControl, TextareaControl, Button, Placeholder, Flex, FlexBlock, FlexItem } = wp.components;
	const { __ } = wp.i18n;
	const el = wp.element.createElement;
	const Fragment = wp.element.Fragment;
	const useSelect = wp.data.useSelect;
	const ServerSideRender = wp.serverSideRender;

	const speak = ( wp.a11y && wp.a11y.speak )
		? wp.a11y.speak.bind( wp.a11y )
		: function () {};

	/* ──────────────────────────────────────────────
	 *  Fetch all FAQ posts for the picker.
	 * ────────────────────────────────────────────── */
	function useFaqPosts() {
		const posts = useSelect( ( select ) => {
			const query = {
				post_type: 'faq',
				per_page: -1,
				status: 'publish',
				orderby: 'menu_order',
				order: 'asc',
			};
			const records = select( 'core' ).getEntityRecords( 'postType', 'faq', query );
			return Array.isArray( records ) ? records : [];
		}, [] );
		return posts;
	}

	/* ──────────────────────────────────────────────
	 *  FAQ picker item component.
	 * ────────────────────────────────────────────── */
	function FaqPickerItem( { faq, isSelected, onToggle } ) {
		return el(
			Flex,
			{ key: faq.id, align: 'top' },
			el( FlexItem, null,
				el( 'button', {
					type: 'button',
					onClick: () => onToggle( faq ),
					className: 'kb-faq-picker__checkbox',
					'aria-pressed': isSelected,
				},
					isSelected
						? el( 'span', { className: 'dashicons dashicons-yes-alt', 'aria-hidden': 'true' } )
						: el( 'span', { className: 'dashicons dashicons-empty', 'aria-hidden': 'true' } )
				)
			),
			el( FlexBlock, null,
				el( 'div', { className: 'kb-faq-picker__item-content' },
					el( 'span', { className: 'kb-faq-picker__item-title' },
						faq.title?.raw || __( '(Untitled)', 'kim-branzell' )
					)
				)
			)
		);
	}

	/* ──────────────────────────────────────────────
	 *  FAQ Picker component (sidebar only).
	 * ────────────────────────────────────────────── */
	function FaqPicker( { selectedFaqs, onChange } ) {
		const posts = useFaqPosts();

		const toggleFaq = ( faq ) => {
			const isSelected = selectedFaqs.some( ( s ) => s.id === faq.id );
			if ( isSelected ) {
				onChange( selectedFaqs.filter( ( s ) => s.id !== faq.id ) );
				speak( __( 'FAQ removed.', 'kim-branzell' ) );
			} else {
				onChange( [ ...selectedFaqs, { id: faq.id, title: faq.title?.raw } ] );
				speak( __( 'FAQ added.', 'kim-branzell' ) );
			}
		};

		const moveFaq = ( index, direction ) => {
			const newFaqs = [ ...selectedFaqs ];
			const newIndex = index + direction;
			if ( newIndex < 0 || newIndex >= newFaqs.length ) return;
			[ newFaqs[ index ], newFaqs[ newIndex ] ] = [ newFaqs[ newIndex ], newFaqs[ index ] ];
			onChange( newFaqs );
		};

		if ( ! posts || posts.length === 0 ) {
			return el( Placeholder, {
				className: 'kb-faq-picker__empty',
				instructions: __( 'No FAQ posts found. Create some FAQs first.', 'kim-branzell' ),
			}, el( Button, { variant: 'primary', href: '/wp-admin/post-new.php?post_type=faq' }, __( 'Create FAQ', 'kim-branzell' ) ) );
		}

		const selectedCount = selectedFaqs.length;
		const selectedLabel = selectedCount === 1
			? __( '1 FAQ selected', 'kim-branzell' )
			: selectedCount + ' ' + __( 'FAQs selected', 'kim-branzell' );

		return el( Fragment, null,
			el( 'div', { className: 'kb-faq-picker' },
				el( 'p', { className: 'kb-faq-picker__label' }, __( 'Select FAQs to display:', 'kim-branzell' ) ),
				selectedFaqs.length > 0
					? el( 'div', { className: 'kb-faq-picker__selected' },
							el( 'summary', { className: 'kb-faq-picker__selected-title' }, selectedLabel ),
							el( 'ul', { className: 'kb-faq-picker__selected-list' },
								selectedFaqs.map( ( faq, index ) =>
									el( 'li', { key: faq.id, className: 'kb-faq-picker__selected-item' },
										el( 'span', { className: 'kb-faq-picker__order-buttons' },
											el( 'button', { type: 'button', className: 'dashicons dashicons-arrow-up-alt', onClick: () => moveFaq( index, -1 ), 'aria-label': __( 'Move up', 'kim-branzell' ), disabled: index === 0 } ),
											el( 'button', { type: 'button', className: 'dashicons dashicons-arrow-down-alt', onClick: () => moveFaq( index, 1 ), 'aria-label': __( 'Move down', 'kim-branzell' ), disabled: index === selectedFaqs.length - 1 } )
										),
										el( 'span', { className: 'kb-faq-picker__selected-title' }, faq.title || __( '(Untitled)', 'kim-branzell' ) ),
										el( Button, { isDestructive: true, isSmall: true, onClick: () => toggleFaq( { id: faq.id } ) }, __( 'Remove', 'kim-branzell' ) )
									)
								)
							)
					  )
					: null,
				el( 'div', { className: 'kb-faq-picker__available' },
					el( 'summary', { className: 'kb-faq-picker__available-title' }, __( 'Available FAQs', 'kim-branzell' ) ),
					el( 'ul', { className: 'kb-faq-picker__available-list' },
						posts.map( ( post ) => {
							const isSel = selectedFaqs.some( ( s ) => s.id === post.id );
							return el( FaqPickerItem, { key: post.id, faq: post, isSelected: isSel, onToggle: toggleFaq } );
						} )
					)
				)
			)
		);
	}

	/* ──────────────────────────────────────────────
	 *  Block registration.
	 * ────────────────────────────────────────────── */
	try {
		registerBlockType( 'kim-branzell/faq-section', {
			edit: function FaqSectionEdit( { attributes, setAttributes, isSelected } ) {
				const { sectionTitle, sectionSubtitle, selectedFaqs, ctaText, ctaButtonText, ctaButtonUrl } = attributes;
				const blockProps = useBlockProps();
				const hasFaqs = selectedFaqs.length > 0;

				const updateFaqs = ( newFaqs ) => setAttributes( { selectedFaqs: newFaqs } );

				// ─── Inline edit fields ───
				function renderEditFields() {
					return el( 'div', { className: 'block-edit-form' },
						el( 'div', { className: 'block-edit-form__section' },
							el( 'p', { className: 'block-edit-form__section-label' }, __( 'Section Header', 'kim-branzell' ) ),
							el( TextControl, {
								label: __( 'Section Title', 'kim-branzell' ),
								value: sectionTitle,
								onChange: ( val ) => setAttributes( { sectionTitle: val } ),
							} ),
							el( TextareaControl, {
								label: __( 'Section Subtitle (optional)', 'kim-branzell' ),
								help: __( 'Text that appears below the section title.', 'kim-branzell' ),
								value: sectionSubtitle,
								onChange: ( val ) => setAttributes( { sectionSubtitle: val } ),
							} )
						),
						el( 'div', { className: 'block-edit-form__section' },
							el( 'p', { className: 'block-edit-form__section-label' }, __( 'Contact CTA', 'kim-branzell' ) ),
							el( 'div', { className: 'block-edit-form__row' },
								el( TextControl, {
									label: __( 'CTA Text', 'kim-branzell' ),
									value: ctaText,
									onChange: ( val ) => setAttributes( { ctaText: val } ),
								} ),
								el( TextControl, {
									label: __( 'Button Text', 'kim-branzell' ),
									value: ctaButtonText,
									onChange: ( val ) => setAttributes( { ctaButtonText: val } ),
								} ),
								el( TextControl, {
									label: __( 'Button URL', 'kim-branzell' ),
									value: ctaButtonUrl,
									onChange: ( val ) => setAttributes( { ctaButtonUrl: val } ),
								} )
							)
						),
						! hasFaqs && el( 'div', { className: 'block-edit-form__section', style: { marginBottom: 0 } },
							el( 'p', { className: 'block-edit-form__section-label' }, __( 'FAQ Selection', 'kim-branzell' ) ),
							el( 'p', { style: { fontSize: '0.8125rem', color: '#757575', margin: 0 } },
								__( 'Select which FAQs to display using the sidebar panel.', 'kim-branzell' )
							)
						)
					);
				}

				// ─── Canvas content ───
				const previewContent = ! hasFaqs
					? el( 'div', { className: 'editor-empty-state' },
							el( 'span', { className: 'editor-empty-state__icon dashicons dashicons-editor-help' } ),
							el( 'p', { className: 'editor-empty-state__message' },
								__( 'No FAQs selected yet. Choose FAQs in the sidebar.', 'kim-branzell' )
							)
					  )
					: isSelected
						? el( Fragment, null,
								el( 'div', { className: 'block-edit-form__preview' },
									el( ServerSideRender, { block: 'kim-branzell/faq-section', attributes: attributes } )
								),
								renderEditFields()
						  )
						: el( ServerSideRender, { block: 'kim-branzell/faq-section', attributes: attributes } );

				return el( Fragment, null,
					// ─── SIDEBAR: FAQ picker only ───
					el( InspectorControls, null,
						el( PanelBody, { title: __( 'FAQ Items', 'kim-branzell' ), initialOpen: true },
							el( FaqPicker, { selectedFaqs, onChange: updateFaqs } )
						)
					),
					// ─── CANVAS ───
					el( 'div', blockProps, previewContent )
				);
			},
			save: () => null,
		} );
	} catch ( err ) {
		console.error( 'FAQ Section block registration failed:', err );
	}
} );
