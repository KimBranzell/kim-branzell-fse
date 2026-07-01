/**
 * OG Preview Panel — Admin Block Editor Sidebar.
 *
 * Fetches OG preview data from the REST endpoint and renders
 * the preview UI. Handles regeneration.
 */

(function () {
	'use strict';

	if ( typeof window.KB_OG_PREVIEW === 'undefined' ) {
		console.error( '[KB OG Preview] KB_OG_PREVIEW not defined — is the meta box rendering?' );
		return;
	}

	const cfg = window.KB_OG_PREVIEW;
	const postId = cfg.postId;

	if ( ! postId || ! cfg.restBase ) {
		console.error( '[KB OG Preview] Missing config', cfg );
		return;
	}

	/**
	 * Build an absolute REST endpoint URL.
	 * WordPress rest_url() returns a path like '/wp-json/kb/v1'.
	 */
	function buildUrl( path, version ) {
		var base = cfg.restBase.replace( /\/$/, '' );
		var qs = version ? '?v=' + encodeURIComponent( version ) : '';
		// new URL() with a base resolves the path relative to current location
		var url = new URL( base + path + qs, window.location.href );
		return url.toString();
	}

	/**
	 * Fetch OG preview data with a 10s timeout.
	 */
	async function fetchPreview( version ) {
		var url = buildUrl( '/ogpreview/' + postId, version );
		var controller = null;
		if ( typeof AbortController !== 'undefined' ) {
			controller = new AbortController();
		}

		var timeout = setTimeout( function() {
			if ( controller ) controller.abort();
		}, 10000 );

		try {
			var res = await fetch( url, {
				method: 'GET',
				headers: {
					'X-WP-Nonce': cfg.nonce,
					'Content-Type': 'application/json',
					'X-Requested-With': 'XMLHttpRequest',
				},
				credentials: 'same-origin',
				signal: controller ? controller.signal : undefined,
			} );
			clearTimeout( timeout );

			if ( ! res.ok ) {
				throw new Error( 'HTTP ' + res.status + ': ' + res.statusText );
			}

			var data = await res.json();
			console.log( '[KB OG Preview] Data received:', data );
			return data;
		} catch ( err ) {
			clearTimeout( timeout );
			if ( err.name === 'AbortError' || err.message.indexOf( 'timed out' ) !== -1 ) {
				throw new Error( 'Request timed out after 10s — is the REST endpoint reachable?' );
			}
			throw err;
		}
	}

	/**
	 * Regenerate OG image (POST).
	 */
	async function regeneratePreview() {
		var url = buildUrl( '/ogpreview/' + postId );
		var controller = null;
		if ( typeof AbortController !== 'undefined' ) {
			controller = new AbortController();
		}

		var timeout = setTimeout( function() {
			if ( controller ) controller.abort();
		}, 10000 );

		var res = await fetch( url, {
			method: 'POST',
			headers: {
				'X-WP-Nonce': cfg.nonce,
				'Content-Type': 'application/json',
				'X-Requested-With': 'XMLHttpRequest',
			},
			credentials: 'same-origin',
			body: JSON.stringify( { post_id: postId } ),
			signal: controller ? controller.signal : undefined,
		} );

		clearTimeout( timeout );

		if ( ! res.ok ) {
			throw new Error( 'Failed to regenerate OG preview: ' + res.status );
		}

		return res.json();
	}

	/**
	 * Render the preview UI into the root element.
	 */
	function renderUI( container, data ) {
		const { image_url, version, is_cached, portrait_source, post_type } = data;

		const cacheClass = is_cached ? 'kb-og-badge--cached' : 'kb-og-badge--uncached';
		const cacheLabel  = is_cached ? 'Cached' : 'Generating…';
		const sourceLabel = portrait_source === 'acf'
			? 'Portrait: Custom upload'
			: portrait_source === 'gravatar'
			? 'Portrait: Gravatar'
			: 'Portrait: None';

		const sourceIcon = portrait_source === 'acf'
			? '✔'
			: portrait_source === 'gravatar'
			? '◐'
			: '✘';

		container.innerHTML = `
			<div class="kb-og-image-wrap">
				<img src="${encodeURI( image_url )}" alt="Open Graph preview" loading="lazy" />
			</div>
			<div class="kb-og-meta">
				<span class="kb-og-badge ${cacheClass}">${cacheLabel}</span>
				<span class="kb-og-badge">v${version}</span>
			</div>
			<div class="kb-og-source">
				<span>${sourceIcon}</span>
				<span>${sourceLabel}</span>
			</div>
			<div class="kb-og-actions">
				<button type="button" class="button kb-og-regenerate-btn" title="Regenerate OG image">
					<span class="dashicons dashicons-update" style="line-height:1.6;"></span>
					Regenerate
				</button>
				<a href="${encodeURI( image_url )}" target="_blank" rel="noopener" class="button">
					<span class="dashicons dashicons-external" style="line-height:1.6;"></span>
					Open
				</a>
			</div>
		`;

		// Attach regenerate handler
		const btn = container.querySelector( '.kb-og-regenerate-btn' );
		if ( btn ) {
			btn.addEventListener( 'click', handleRegenerate );
		}
	}

	/**
	 * Render loading spinner.
	 */
	function renderLoading( container ) {
		container.innerHTML = `
			<div style="text-align:center;padding:20px 0;">
				<span class="spinner is-active" style="float:none;margin:0 auto;display:block;width:20px;height:20px;"></span>
				<p style="font-size:12px;color:#666;margin:8px 0 0;">Loading preview…</p>
			</div>
		`;
	}

	/**
	 * Render error state.
	 */
	function renderError( container, message ) {
		container.innerHTML = `
			<div class="kb-og-error">
				<strong>Error:</strong> ${message}
			</div>
			<div class="kb-og-actions" style="margin-top:8px;">
				<button type="button" class="button kb-og-regenerate-btn">
					Try regenerating
				</button>
			</div>
		`;
		const btn = container.querySelector( '.kb-og-regenerate-btn' );
		if ( btn ) {
			btn.addEventListener( 'click', handleRegenerate );
		}
	}

	/**
	 * Handle regenerate button click.
	 */
	let isRegenerating = false;
	async function handleRegenerate() {
		if ( isRegenerating ) return;
		const container = document.getElementById( 'kb-ogpreview-root' );
		if ( ! container ) return;

		const btn = container.querySelector( '.kb-og-regenerate-btn' );
		if ( btn ) {
			btn.disabled = true;
			btn.textContent = 'Regenerating…';
		}
		isRegenerating = true;

		try {
			// Optimistically update version
			const data = await regeneratePreview();
			if ( data.image_url ) {
				// Update the global so re-fetches use the new URL
				window.KB_OG_PREVIEW.initialVersion = data.version;
				window.KB_OG_PREVIEW.initialImageUrl = data.image_url;

				// Re-fetch to get the full response with cached status
				const fresh = await fetchPreview( data.version );
				renderUI( container, fresh );
			}
		} catch ( err ) {
			console.error( '[KB OG Preview]', err );
			renderError( container, err.message || 'Unknown error' );
		} finally {
			isRegenerating = false;
		}
	}

	/**
	 * Boot — fetch initial data and render.
	 */
	async function boot() {
		const container = document.getElementById( 'kb-ogpreview-root' );
		if ( ! container ) return;

		renderLoading( container );

		try {
			const data = await fetchPreview();
			renderUI( container, data );
		} catch ( err ) {
			console.error( '[KB OG Preview]', err );
			renderError( container, err.message || 'Failed to load preview.' );
		}
	}

	// Wait for DOM ready
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
})();
