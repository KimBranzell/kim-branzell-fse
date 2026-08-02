document.addEventListener(
	'DOMContentLoaded',
	function () {
		const sections = document.querySelectorAll( '.blog-posts' );

		if ( ! sections.length ) {
			return;
		}

		sections.forEach(
			function ( section ) {
				const filterGroup = section.querySelector( '.blog-posts__filter' );
				const leadZone    = section.querySelector( '.blog-posts__lead' );
				const list        = section.querySelector( '.blog-posts__list' );
				const emptyMsg    = section.querySelector( '.blog-posts__empty' );
				const announcer   = section.querySelector( '.blog-posts__filter-announcer' );

				if ( ! filterGroup ) {
					return;
				}

				// Collect items from both zones (lead cards + list rows).
				const items = section.querySelectorAll( '.blog-posts__card, .blog-posts__item' );

				if ( ! items.length ) {
					return;
				}

				/**
				 * Announce a message to the live region for screen readers.
				 *
				 * Uses wp.a11y.speak if available (WordPress core), otherwise
				 * falls back to setting textContent on the announcer element.
				 *
				 * @param {string} msg Message to announce.
				 */
				function announce( msg ) {
					if ( window.wp && window.wp.a11y && typeof window.wp.a11y.speak === 'function' ) {
						window.wp.a11y.speak( msg );
					} else if ( announcer ) {
						// Trigger re-read by clearing first.
						announcer.textContent = '';
						requestAnimationFrame(
							function () {
								announcer.textContent = msg;
							}
						);
					}
				}

				/**
				 * Filter both lead cards and list rows by a category slug, or show all.
				 *
				 * @param {string} filterSlug Category slug, or 'all'.
				 * @returns {number} Count of visible items after filtering.
				 */
				function applyFilter( filterSlug ) {
					let visibleCount    = 0;
					let leadVisibleCount = 0;

					items.forEach(
						function ( item ) {
							const catData  = item.getAttribute( 'data-category' ) || '';
							const catSlugs = catData.split( /\s+/ ).filter( Boolean );

							if ( filterSlug === 'all' || catSlugs.indexOf( filterSlug ) !== -1 ) {
								item.style.display = '';
								visibleCount++;
								if ( item.classList.contains( 'blog-posts__card' ) ) {
									leadVisibleCount++;
								}
							} else {
								item.style.display = 'none';
							}
						}
					);

					// Hide the entire lead zone when no cards match.
					if ( leadZone ) {
						leadZone.style.display = leadVisibleCount > 0 ? '' : 'none';
					}

					if ( emptyMsg ) {
						emptyMsg.setAttribute( 'aria-hidden', visibleCount > 0 ? 'true' : 'false' );
					}

					return visibleCount;
				}

				// ─── Delegated click handler on the filter group ───
				filterGroup.addEventListener(
					'click',
					function ( e ) {
						const chip = e.target.closest( '.blog-posts__filter-chip' );

						if ( ! chip ) {
							return;
						}

						const filterSlug = chip.getAttribute( 'data-filter' );

						if ( chip.getAttribute( 'aria-pressed' ) === 'true' ) {
							return; // Already active.
						}

						// Update all chips.
						const allChips = filterGroup.querySelectorAll( '.blog-posts__filter-chip' );
						allChips.forEach(
							function ( c ) {
								c.setAttribute( 'aria-pressed', 'false' );
							}
						);

						chip.setAttribute( 'aria-pressed', 'true' );
						const visibleCount = applyFilter( filterSlug );

						// Announce the filter change.
						const label = chip.textContent.trim();
						announce( label + ': ' + visibleCount + ' ' + ( filterSlug === 'all' ? 'total posts' : 'posts' ) );
					}
				);
			}
		);
	}
);
