document.addEventListener(
	'DOMContentLoaded',
	function () {
		var sections = document.querySelectorAll( '.testimonial' );

		if ( ! sections.length ) {
			return;
		}

		sections.forEach(
			function ( section ) {
				var viewport  = section.querySelector( '.testimonial__viewport' );
				var track     = section.querySelector( '.testimonial__track' );
				var slides    = section.querySelectorAll( '.testimonial__slide' );
				var dots      = section.querySelectorAll( '.testimonial__dot' );
				var prevBtn   = section.querySelector( '.testimonial__prev' );
				var nextBtn   = section.querySelector( '.testimonial__next' );
				var announcer = section.querySelector( '.testimonial__announcer' );

				if ( ! viewport || ! track || ! slides.length ) {
					return;
				}

				var total         = parseInt( section.getAttribute( 'data-total' ), 10 ) || slides.length;
				var autoplay      = section.getAttribute( 'data-autoplay' ) === 'true';
				var interval      = parseInt( section.getAttribute( 'data-interval' ), 10 ) || 6000;
				var currentIndex  = 0;
				var timerId       = null;
				var reducedMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

				/**
				 * Announce a message to the live region for screen readers.
				 *
				 * @param {string} msg Message to announce.
				 */
				function announce( msg ) {
					if ( ! announcer ) {
						return;
					}
					// Clearing first forces re-read.
					announcer.textContent = '';
					requestAnimationFrame(
						function () {
							announcer.textContent = msg;
						}
					);
				}

				/**
				 * Build the announcement string for the current slide.
				 */
				function buildAnnouncement( index ) {
					var slide      = slides[ index ];
					var authorEl   = slide.querySelector( '.testimonial__author' );
					var authorName = authorEl ? authorEl.textContent.trim() : '';
					var n          = index + 1;

					if ( authorName ) {
						return 'Slide ' + n + ' of ' + total + ': ' + authorName;
					}
					return 'Slide ' + n + ' of ' + total;
				}

				/**
				 * Activate a slide by index.
				 *
				 * @param {number}  index            Zero-based slide index.
				 * @param {boolean} [announce=true]  Whether to announce the change.
				 */
				function activate( index, announceChange ) {
					if ( announceChange === undefined ) {
						announceChange = true;
					}

					// Clamp.
					if ( index < 0 ) {
						index = total - 1;
					}
					if ( index >= total ) {
						index = 0;
					}

					currentIndex = index;

					// ── Update slides ──
					slides.forEach(
						function ( slide, i ) {
							var isActive = ( i === index );

							slide.setAttribute( 'aria-hidden', isActive ? 'false' : 'true' );

							if ( isActive ) {
								slide.classList.remove( 'testimonial__slide--hidden' );
								slide.removeAttribute( 'inert' );
							} else {
								slide.classList.add( 'testimonial__slide--hidden' );
								slide.setAttribute( 'inert', '' );
							}
						}
					);

					// ── Move track ──
					track.style.transform = 'translateX(-' + ( index * 100 ) + '%)';

					// ── Update dots ──
					dots.forEach(
						function ( dot, i ) {
							var isActive = ( i === index );
							dot.setAttribute( 'aria-selected', isActive ? 'true' : 'false' );
							dot.setAttribute( 'tabindex', isActive ? '0' : '-1' );
						}
					);

					if ( announceChange ) {
						announce( buildAnnouncement( currentIndex ) );
					}
				}

				// ─── Navigation ──────────────────────────────────

				function goNext() {
					activate( currentIndex + 1, true );
				}

				function goPrev() {
					activate( currentIndex - 1, true );
				}

				function goTo( index ) {
					activate( index, true );
				}

				// ─── Event handlers ──────────────────────────────

				if ( prevBtn ) {
					prevBtn.addEventListener( 'click', goPrev );
				}

				if ( nextBtn ) {
					nextBtn.addEventListener( 'click', goNext );
				}

				// Delegated dot clicks.
				var dotsContainer = section.querySelector( '.testimonial__dots' );
				if ( dotsContainer ) {
					dotsContainer.addEventListener(
						'click',
						function ( e ) {
							var dot = e.target.closest( '.testimonial__dot' );
							if ( ! dot ) {
								return;
							}
							var idx = Array.prototype.indexOf.call( dots, dot );
							if ( idx >= 0 ) {
								goTo( idx );
							}
						}
					);
				}

				// Keyboard navigation on the viewport.
				if ( viewport ) {
					viewport.addEventListener(
						'keydown',
						function ( e ) {
							var key = e.key;

							if ( key === 'ArrowRight' ) {
								e.preventDefault();
								goNext();
							} else if ( key === 'ArrowLeft' ) {
								e.preventDefault();
								goPrev();
							} else if ( key === 'Home' ) {
								e.preventDefault();
								goTo( 0 );
							} else if ( key === 'End' ) {
								e.preventDefault();
								goTo( total - 1 );
							}
						}
					);
				}

				// ─── Autoplay ────────────────────────────────────

				function startTimer() {
					if ( ! autoplay || reducedMotion ) {
						return;
					}
					stopTimer();
					timerId = setInterval( goNext, interval );
				}

				function stopTimer() {
					if ( timerId !== null ) {
						clearInterval( timerId );
						timerId = null;
					}
				}

				if ( autoplay && ! reducedMotion ) {
					// Pause on hover or focus.
					section.addEventListener( 'mouseenter', stopTimer );
					section.addEventListener( 'mouseleave', startTimer );
					section.addEventListener( 'focusin', stopTimer );
					section.addEventListener( 'focusout', startTimer );

					startTimer();
				}

				// Listen for OS reduced-motion changes at runtime.
				var motionQuery = window.matchMedia( '(prefers-reduced-motion: reduce)' );

				function handleMotionChange( e ) {
					reducedMotion = e.matches;
					if ( reducedMotion ) {
						stopTimer();
					} else if ( autoplay ) {
						startTimer();
					}
				}

				if ( motionQuery.addEventListener ) {
					motionQuery.addEventListener( 'change', handleMotionChange );
				} else if ( motionQuery.addListener ) {
					// Fallback for older browsers.
					motionQuery.addListener( handleMotionChange );
				}
			}
		);
	}
);
