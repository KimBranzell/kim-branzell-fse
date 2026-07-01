wp.domReady( () => {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, TextControl, Button, Placeholder, Flex, FlexBlock, FlexItem } = wp.components;
	const { __ } = wp.i18n;
	const el = wp.element.createElement;
	const Fragment = wp.element.Fragment;
	const useSelect = wp.data.useSelect;

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
			el(
				FlexItem,
				null,
				el(
					'button',
					{
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
			el(
				FlexBlock,
				null,
				el(
					'div',
					{ className: 'kb-faq-picker__item-content' },
					el(
						'span',
						{ className: 'kb-faq-picker__item-title' },
						faq.title?.raw || __( '(Untitled)', 'kim-branzell' )
					)
				)
			)
		);
	}

	/* ──────────────────────────────────────────────
	 *  FAQ Picker component.
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
			if ( newIndex < 0 || newIndex >= newFaqs.length ) {
				return;
			}
			[ newFaqs[ index ], newFaqs[ newIndex ] ] = [ newFaqs[ newIndex ], newFaqs[ index ] ];
			onChange( newFaqs );
		};

		if ( ! posts || posts.length === 0 ) {
			return el(
				Placeholder,
				{
					className: 'kb-faq-picker__empty',
					instructions: __( 'No FAQ posts found. Create some FAQs first, then select them here.', 'kim-branzell' ),
				},
				el(
					Button,
					{
						variant: 'primary',
						href: '/wp-admin/post-new.php?post_type=faq',
					},
					__( 'Create FAQ', 'kim-branzell' )
				)
			);
		}

		const selectedCount = selectedFaqs.length;
		const selectedLabel = selectedCount === 1
			? __( '1 FAQ selected', 'kim-branzell' )
			: selectedCount + ' ' + __( 'FAQs selected', 'kim-branzell' );

		return el(
			Fragment,
			null,
			el(
				'div',
				{ className: 'kb-faq-picker' },
				el(
					'p',
					{ className: 'kb-faq-picker__label' },
					__( 'Select FAQs to display:', 'kim-branzell' )
				),
				selectedFaqs.length > 0
					? el(
							'div',
							{ className: 'kb-faq-picker__selected' },
							el(
								'summary',
								{ className: 'kb-faq-picker__selected-title' },
								selectedLabel
							),
							el(
								'ul',
								{ className: 'kb-faq-picker__selected-list' },
								selectedFaqs.map( ( faq, index ) =>
									el(
										'li',
										{ key: faq.id, className: 'kb-faq-picker__selected-item' },
										el(
											'span',
											{ className: 'kb-faq-picker__order-buttons' },
											el( 'button', {
												type: 'button',
												className: 'dashicons dashicons-arrow-up-alt',
												onClick: () => moveFaq( index, -1 ),
												'aria-label': __( 'Move up', 'kim-branzell' ),
												disabled: index === 0,
											} ),
											el( 'button', {
												type: 'button',
												className: 'dashicons dashicons-arrow-down-alt',
												onClick: () => moveFaq( index, 1 ),
												'aria-label': __( 'Move down', 'kim-branzell' ),
												disabled: index === selectedFaqs.length - 1,
											} )
										),
										el( 'span', { className: 'kb-faq-picker__selected-title' }, faq.title || __( '(Untitled)', 'kim-branzell' ) ),
										el(
											Button,
											{
												isDestructive: true,
												isSmall: true,
												onClick: () => toggleFaq( { id: faq.id } ),
											},
											__( 'Remove', 'kim-branzell' )
										)
									)
								)
							)
					  )
					: null,
				el(
					'div',
					{ className: 'kb-faq-picker__available' },
					el(
						'summary',
						{ className: 'kb-faq-picker__available-title' },
						__( 'Available FAQs', 'kim-branzell' )
					),
					el(
						'ul',
						{ className: 'kb-faq-picker__available-list' },
						posts.map( ( post ) => {
							const isSelected = selectedFaqs.some( ( s ) => s.id === post.id );
							return el( FaqPickerItem, {
								key: post.id,
								faq: post,
								isSelected,
								onToggle: toggleFaq,
							} );
						} )
					)
				)
			)
		);
	}

	/* ──────────────────────────────────────────────
	 *  Preview: render accordion item.
	 * ────────────────────────────────────────────── */
	function renderPreviewItem( faq, index, isFirst ) {
		const isExpanded = isFirst;
		const iconClass = 'faq-section-editor__item-icon' +
			( isExpanded ? ' faq-section-editor__item-icon--expanded' : '' );
		const iconLabel = isExpanded ? '⊖' : '⊕';

		return el(
			'div',
			{ key: faq.id, className: 'faq-section-editor__item' },
			el(
				'div',
				{ className: 'faq-section-editor__item-question' },
				el( 'span', { style: { flex: 1 } }, faq.title || __( '(Untitled)', 'kim-branzell' ) ),
				el(
					'span',
					{ className: iconClass, 'aria-hidden': 'true' },
					iconLabel
				)
			),
			isExpanded && el(
				'div',
				{ className: 'faq-section-editor__item-answer' },
				__( 'FAQ answer will appear here.', 'kim-branzell' )
			)
		);
	}

	/* ──────────────────────────────────────────────
	 *  Block registration.
	 * ────────────────────────────────────────────── */
	try {
		registerBlockType( 'kim-branzell/faq-section', {
			edit: function FaqSectionEdit( { attributes, setAttributes } ) {
				const {
					sectionTitle,
					sectionSubtitle,
					selectedFaqs,
					ctaText,
					ctaButtonText,
					ctaButtonUrl,
				} = attributes;

				const blockProps = useBlockProps( {
					className: 'faq-section-editor',
				} );

				const updateFaqs = ( newFaqs ) => {
					setAttributes( { selectedFaqs: newFaqs } );
				};

				const hasFaqs = selectedFaqs.length > 0;

				return el(
					Fragment,
					null,
					// ─── SIDEBAR: Inspector Controls ───
					el(
						InspectorControls,
						null,
						// Panel 1: Section Content
						el(
							PanelBody,
							{ title: __( 'Section Content', 'kim-branzell' ), initialOpen: true },
							el( TextControl, {
								label: __( 'Section Title', 'kim-branzell' ),
								value: sectionTitle,
								onChange: ( val ) => setAttributes( { sectionTitle: val } ),
							} ),
							el( TextControl, {
								label: __( 'Section Subtitle', 'kim-branzell' ),
								help: __( 'Optional text that appears below the section title.', 'kim-branzell' ),
								value: sectionSubtitle,
								onChange: ( val ) => setAttributes( { sectionSubtitle: val } ),
							} )
						),
						// Panel 2: FAQ Items
						el(
							PanelBody,
							{ title: __( 'FAQ Items', 'kim-branzell' ), initialOpen: true },
							el( FaqPicker, {
								selectedFaqs,
								onChange: updateFaqs,
							} )
						),
						// Panel 3: Contact CTA
						el(
							PanelBody,
							{ title: __( 'Contact CTA', 'kim-branzell' ), initialOpen: false },
							el( 'p', {
								style: {
									fontSize: '0.75rem',
									color: '#757575',
									marginBottom: '0.75rem',
								},
							}, __( 'A contact card shown in the left column prompting visitors to get in touch.', 'kim-branzell' ) ),
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
								help: __( 'Where the "Get in Touch" button links to.', 'kim-branzell' ),
								value: ctaButtonUrl,
								onChange: ( val ) => setAttributes( { ctaButtonUrl: val } ),
							} )
						)
					),
					// ─── PREVIEW AREA ───
					el(
						'div',
						blockProps,
						el(
							'div',
							{ className: 'faq-section-editor__preview' },
							// Left column
							el(
								'div',
								{ className: 'faq-section-editor__left' },
								el(
									'h2',
									{ className: 'faq-section-editor__title' },
									sectionTitle || __( 'Section Title', 'kim-branzell' )
								),
								sectionSubtitle && el(
									'p',
									{ className: 'faq-section-editor__subtitle' },
									sectionSubtitle
								),
								// CTA Card
								el(
									'div',
									{ className: 'faq-section-editor__cta' },
									el( 'p', null, ctaText || __( "Can't find your answer?", 'kim-branzell' ) ),
									el(
										'span',
										{ className: 'faq-section-editor__cta-button' },
										ctaButtonText || __( 'Get in Touch', 'kim-branzell' ),
										el( 'span', { style: { marginLeft: '0.375rem' }, 'aria-hidden': 'true' }, '→' )
									)
								)
							),
							// Right column
							el(
								'div',
								{ className: 'faq-section-editor__right' },
								hasFaqs
									? el(
											'div',
											{ className: 'faq-section-editor__list' },
											selectedFaqs.map( ( faq, index ) =>
												renderPreviewItem( faq, index, index === 0 )
											)
									  )
									: el(
											'div',
											{ className: 'faq-section-editor__empty' },
											el( 'span', { className: 'faq-section-editor__empty-icon dashicons dashicons-editor-help' } ),
											__( 'No FAQs selected. Add FAQs using the sidebar.', 'kim-branzell' )
									  )
							)
						)
					)
				);
			},
			save: () => null,
		} );
	} catch ( err ) {
		console.error( 'FAQ Section block registration failed:', err );
	}
} );
