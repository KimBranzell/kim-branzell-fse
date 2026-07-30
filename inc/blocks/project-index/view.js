/**
 * Project Index — client-side column sorting.
 *
 * Reads data-sort-* attributes from <li> items, reorders DOM nodes,
 * and manages aria-sort on header <button> elements.
 */

( function () {
	const list = document.querySelector( '.project-index__list' );
	if ( ! list ) {
		return;
	}

	const headerRow = document.querySelector( '.project-index__header-row' );
	if ( ! headerRow ) {
		return;
	}

	const buttons = headerRow.querySelectorAll( '.project-index__sort-btn' );
	const announcer = document.querySelector( '.project-index__sort-announcer' );

	/**
	 * Compare function: numeric for "year", locale-compare for text columns.
	 *
	 * @param {HTMLElement} a
	 * @param {HTMLElement} b
	 * @param {string}      key   data-sort-* key.
	 * @param {boolean}     asc   True for ascending.
	 * @return {number}
	 */
	function compare( a, b, key, asc ) {
		const rawA = a.getAttribute( 'data-sort-' + key ) || '';
		const rawB = b.getAttribute( 'data-sort-' + key ) || '';

		let result;
		if ( key === 'year' ) {
			const numA = parseInt( rawA, 10 ) || 0;
			const numB = parseInt( rawB, 10 ) || 0;
			result = numA - numB;
		} else {
			result = rawA.localeCompare( rawB, undefined, { sensitivity: 'base' } );
		}

		return asc ? result : -result;
	}

	/**
	 * Sort items, reattach to list, update aria-sort.
	 *
	 * @param {string}  key      data-sort-* key.
	 * @param {boolean} asc      True for ascending.
	 * @param {string}  label    Human-readable column name (for announcement).
	 */
	function sort( key, asc, label ) {
		const items = Array.from( list.querySelectorAll( '.project-index__item' ) );

		items.sort( function ( a, b ) {
			return compare( a, b, key, asc );
		} );

		// Reorder DOM — appendChild on an existing element moves it.
		items.forEach( function ( item ) {
			list.appendChild( item );
		} );

		// Update aria-sort on all buttons.
		buttons.forEach( function ( btn ) {
			btn.setAttribute(
				'aria-sort',
				btn.getAttribute( 'data-sort-key' ) === key
					? ( asc ? 'ascending' : 'descending' )
					: 'none'
			);
		} );

		// Announce to screen readers via live region.
		if ( announcer ) {
			const direction = asc ? 'stigande' : 'fallande';

			// Clear first so rapid re-announcements are not dropped by AT.
			announcer.textContent = '';
			requestAnimationFrame( function () {
				announcer.textContent = label + ' — sorterat ' + direction;
			} );
		}
	}

	// Bind click handlers on sort buttons.
	buttons.forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			const key = btn.getAttribute( 'data-sort-key' );
			const currentSort = btn.getAttribute( 'aria-sort' );
			const label = ( btn.textContent || '' ).replace( /\s+/g, ' ' ).trim() || key;

			if ( currentSort === 'ascending' ) {
				sort( key, false, label );
			} else {
				sort( key, true, label );
			}
		} );
	} );
} )();
