/**
 * ThemisDB v3 – Navigation & UI Enhancements (Vanilla JS, no jQuery)
 *
 * Handles:
 * - Mobile menu toggle (aria-label improvements)
 * - Announcement bar dismiss (sessionStorage)
 * - Scroll-aware header (add class `is-scrolled` with box-shadow)
 * - Code copy buttons
 * - Active nav link highlighting
 * - Card hover effects (CSS class-based)
 *
 * @package ThemisDB_V3
 */

( function () {
	'use strict';

	/* ----------------------------------------------------------
	   DOMContentLoaded entry point
	   ---------------------------------------------------------- */
	document.addEventListener( 'DOMContentLoaded', function () {

		initMobileNav();
		initAnnouncementBar();
		initScrollHeader();
		initCodeCopyButtons();
		initDataCopyButtons();
		initActiveNavLinks();
		initCardHoverEffects();

	} );

	/* ----------------------------------------------------------
	   Mobile Navigation Toggle
	   Enhances the WordPress block navigation's responsive toggle
	   ---------------------------------------------------------- */
	function initMobileNav() {
		var navToggle = document.querySelector( '.wp-block-navigation__responsive-container-open' );
		var navClose  = document.querySelector( '.wp-block-navigation__responsive-container-close' );
		var navMenu   = document.querySelector( '.wp-block-navigation__responsive-container' );

		if ( navToggle ) {
			navToggle.setAttribute( 'aria-label', 'Open navigation menu' );
			navToggle.setAttribute( 'aria-expanded', 'false' );
		}
		if ( navClose ) {
			navClose.setAttribute( 'aria-label', 'Close navigation menu' );
		}

		if ( navToggle && navMenu ) {
			navToggle.addEventListener( 'click', function () {
				var expanded = navToggle.getAttribute( 'aria-expanded' ) === 'true';
				navToggle.setAttribute( 'aria-expanded', String( ! expanded ) );
			} );
		}

		// Close mobile menu on Escape key
		document.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Escape' && navMenu ) {
				if ( navMenu.classList.contains( 'is-menu-open' ) && navClose ) {
					navClose.click();
					if ( navToggle ) navToggle.focus();
				}
			}
		} );
	}

	/* ----------------------------------------------------------
	   Announcement Bar – dismiss and persist via sessionStorage
	   ---------------------------------------------------------- */
	function initAnnouncementBar() {
		var bar = document.querySelector( '.tv3-announcement-bar, .themis-announcement-bar' );
		if ( ! bar ) return;

		var key = 'tv3-announcement-dismissed';
		if ( sessionStorage.getItem( key ) ) {
			bar.style.display = 'none';
			return;
		}

		// Add close button if not already present
		if ( ! bar.querySelector( '.tv3-announcement-close' ) ) {
			var btn = document.createElement( 'button' );
			btn.className      = 'tv3-announcement-close';
			btn.setAttribute( 'aria-label', 'Dismiss announcement' );
			btn.textContent    = '×';
			btn.style.cssText  =
				'position:absolute;right:1rem;top:50%;transform:translateY(-50%);' +
				'background:none;border:none;color:rgba(255,255,255,0.7);font-size:1.25rem;' +
				'cursor:pointer;padding:0 0.5rem;line-height:1;';
			bar.style.position = 'relative';
			bar.appendChild( btn );

			btn.addEventListener( 'click', function () {
				bar.style.transition = 'opacity 0.3s, max-height 0.35s';
				bar.style.opacity    = '0';
				bar.style.maxHeight  = '0';
				bar.style.overflow   = 'hidden';
				setTimeout( function () { bar.style.display = 'none'; }, 360 );
				sessionStorage.setItem( key, '1' );
			} );
		}
	}

	/* ----------------------------------------------------------
	   Scroll-aware Header: add class `is-scrolled` on scroll
	   ---------------------------------------------------------- */
	function initScrollHeader() {
		var header = document.querySelector( '.site-header, .tv3-header' );
		if ( ! header ) return;

		var THRESHOLD = 8;

		function update() {
			if ( window.scrollY > THRESHOLD ) {
				header.classList.add( 'is-scrolled' );
			} else {
				header.classList.remove( 'is-scrolled' );
			}
		}

		window.addEventListener( 'scroll', update, { passive: true } );
		update(); // run immediately on load
	}

	/* ----------------------------------------------------------
	   Code Copy Buttons
	   Injects a "Copy" button above all code blocks
	   ---------------------------------------------------------- */
	function initCodeCopyButtons() {
		var codeBlocks = document.querySelectorAll(
			'pre code, .wp-block-code code, .tv3-code-block pre'
		);

		codeBlocks.forEach( function ( codeEl ) {
			var pre = codeEl.closest( 'pre' );
			if ( ! pre ) return;

			// Skip if copy button already exists
			if ( pre.querySelector( '.tv3-code-copy' ) ) return;

			var wrapper = pre.parentElement;

			// If pre is not already inside a tv3-code-block wrapper, create one
			if ( wrapper && ! wrapper.classList.contains( 'tv3-code-block' ) ) {
				var container = document.createElement( 'div' );
				container.className = 'tv3-code-block';
				pre.parentNode.insertBefore( container, pre );
				container.appendChild( pre );
			}

			// Create copy button
			var copyBtn = document.createElement( 'button' );
			copyBtn.className   = 'tv3-code-copy';
			copyBtn.textContent = 'Copy';
			copyBtn.setAttribute( 'aria-label', 'Copy code to clipboard' );
			copyBtn.style.cssText =
				'position:absolute;top:0.625rem;right:0.625rem;' +
				'background:none;border:1px solid rgba(255,255,255,0.2);' +
				'color:rgba(255,255,255,0.5);font-size:0.75rem;' +
				'padding:0.2rem 0.6rem;border-radius:4px;cursor:pointer;' +
				'font-family:inherit;transition:all 0.2s;';

			// Make parent relative for positioning
			pre.style.position = 'relative';
			pre.appendChild( copyBtn );

			copyBtn.addEventListener( 'click', function () {
				var text = codeEl.textContent || '';
				var applySuccess = function () {
					copyBtn.textContent = '✓ Copied!';
					copyBtn.style.color = '#50e6ff';
					setTimeout( function () {
						copyBtn.textContent = 'Copy';
						copyBtn.style.color = 'rgba(255,255,255,0.5)';
					}, 2000 );
				};
				var fallbackCopy = function () {
					var ta = document.createElement( 'textarea' );
					ta.value = text;
					ta.style.position = 'absolute';
					ta.style.left = '-9999px';
					document.body.appendChild( ta );
					ta.select();
					try { document.execCommand( 'copy' ); } catch ( e ) {}
					document.body.removeChild( ta );
					applySuccess();
				};
				if ( navigator.clipboard && navigator.clipboard.writeText ) {
					navigator.clipboard.writeText( text )
						.then( applySuccess )
						.catch( fallbackCopy );
				} else {
					fallbackCopy();
				}
			} );
		} );
	}

	/* ----------------------------------------------------------
	   Data-Attribute Copy Buttons
	   Handles buttons with data-copy-text (static string) or
	   data-copy-selector (CSS selector whose textContent to copy).
	   Replaces inline onclick handlers to comply with CSP.
	   ---------------------------------------------------------- */
	function initDataCopyButtons() {
		var buttons = document.querySelectorAll( '[data-copy-text], [data-copy-selector]' );

		buttons.forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var text = '';

				if ( btn.hasAttribute( 'data-copy-text' ) ) {
					text = btn.getAttribute( 'data-copy-text' );
				} else {
					var selector = btn.getAttribute( 'data-copy-selector' );
					var target   = selector ? document.querySelector( selector ) : null;
					if ( ! target ) return;
					text = target.textContent || '';
				}

				var originalText  = btn.textContent;
				var originalColor = btn.style.color;

				if ( navigator.clipboard && navigator.clipboard.writeText ) {
					navigator.clipboard.writeText( text ).then( function () {
						btn.textContent  = '✓';
						btn.style.color  = '#50e6ff';
						setTimeout( function () {
							btn.textContent = originalText;
							btn.style.color = originalColor;
						}, 2000 );
					} ).catch( function () {
						// Fallback for browsers without Clipboard API or permission issues
						var ta        = document.createElement( 'textarea' );
						ta.value      = text;
						ta.style.cssText = 'position:absolute;left:-9999px;top:-9999px;';
						document.body.appendChild( ta );
						ta.select();
						try { document.execCommand( 'copy' ); } catch ( e ) {}
						document.body.removeChild( ta );
						btn.textContent = '✓';
						setTimeout( function () {
							btn.textContent = originalText;
							btn.style.color = originalColor;
						}, 2000 );
					} );
				} else {
					// Fallback for browsers without Clipboard API
					var ta        = document.createElement( 'textarea' );
					ta.value      = text;
					ta.style.cssText = 'position:absolute;left:-9999px;top:-9999px;';
					document.body.appendChild( ta );
					ta.select();
					try { document.execCommand( 'copy' ); } catch ( e ) {}
					document.body.removeChild( ta );
					btn.textContent = '✓';
					setTimeout( function () { btn.textContent = originalText; }, 2000 );
				}
			} );
		} );
	}

	/* ----------------------------------------------------------
	   Active Nav Link Highlighting
	   Adds `is-active` class to navigation links matching the current URL
	   ---------------------------------------------------------- */
	function initActiveNavLinks() {
		var currentUrl = window.location.pathname;
		var navLinks   = document.querySelectorAll(
			'.wp-block-navigation-item__content, .tv3-header a'
		);

		navLinks.forEach( function ( link ) {
			var href = link.getAttribute( 'href' );
			if ( ! href ) return;

			try {
				var linkPath = new URL( href, window.location.origin ).pathname;
				if (
					linkPath === currentUrl ||
					( linkPath !== '/' && currentUrl.indexOf( linkPath ) === 0 )
				) {
					link.classList.add( 'is-active' );
					link.setAttribute( 'aria-current', 'page' );
					link.style.color = '#50e6ff';
				}
			} catch ( e ) {
				// Invalid URL – skip
			}
		} );
	}

	/* ----------------------------------------------------------
	   Card Hover Effects (CSS class-based, not inline styles)
	   Enhances product and feature cards with top-border animation
	   ---------------------------------------------------------- */
	function initCardHoverEffects() {
		var cards = document.querySelectorAll(
			'.tv3-product-card, .tv3-card, .themis-product-card'
		);

		cards.forEach( function ( card ) {
			// Find any existing top border element
			var topBorder = card.querySelector( '.tv3-card-top-border' );

			card.addEventListener( 'mouseenter', function () {
				card.classList.add( 'is-hovered' );
				if ( topBorder ) {
					topBorder.style.transform = 'scaleX(1)';
				}
			} );

			card.addEventListener( 'mouseleave', function () {
				card.classList.remove( 'is-hovered' );
				if ( topBorder ) {
					topBorder.style.transform = 'scaleX(0)';
				}
			} );
		} );
	}

} )();
