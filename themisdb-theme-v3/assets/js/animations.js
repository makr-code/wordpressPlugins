/**
 * ThemisDB v3 – jQuery-Powered Animations & Interactive Components
 *
 * Requires:    jQuery (loaded by WordPress)
 * Optional:    jQuery UI (Tabs + Accordion) – loaded from WP core if available
 *
 * Features:
 * - Animated stat counters (.themis-v3-counter) via IntersectionObserver
 * - Scroll fade-in for .themis-v3-fade-in elements via IntersectionObserver
 * - Scroll slide-up for .themis-v3-slide-up elements via IntersectionObserver
 * - jQuery UI Tabs for .themis-v3-tabs elements
 * - jQuery accordion for .themis-v3-accordion elements
 * - Smooth scroll for anchor links
 * - Fallback tab switching when jQuery UI is unavailable
 *
 * @package ThemisDB_V3
 */

/* global jQuery, IntersectionObserver */

( function ( $ ) {
	'use strict';

	/* ================================================================
	   1. DOCUMENT READY
	   ================================================================ */
	$( document ).ready( function () {

		initCounters();
		initScrollAnimations();
		initTabs();
		initAccordions();
		initSmoothScroll();

	} );

	/* ================================================================
	   2. ANIMATED STAT COUNTERS
	   Counts up from 0 to data-target when element enters viewport.
	   Supports data-prefix and data-suffix attributes.
	   ================================================================ */
	function initCounters() {
		var counters = document.querySelectorAll( '.themis-v3-counter' );
		if ( ! counters.length ) return;

		var observer = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( entry ) {
				if ( ! entry.isIntersecting ) return;
				observer.unobserve( entry.target );
				animateCounter( entry.target );
			} );
		}, { threshold: 0.4 } );

		counters.forEach( function ( el ) {
			observer.observe( el );
		} );
	}

	function animateCounter( el ) {
		var target   = parseFloat( el.getAttribute( 'data-target' ) ) || 0;
		var suffix   = el.getAttribute( 'data-suffix' ) || '';
		var prefix   = el.getAttribute( 'data-prefix' ) || '';
		var duration = 1800; // ms
		var start    = null;
		var startVal = 0;

		function easeOutQuart( t ) {
			return 1 - Math.pow( 1 - t, 4 );
		}

		function step( timestamp ) {
			if ( ! start ) start = timestamp;
			var elapsed  = timestamp - start;
			var progress = Math.min( elapsed / duration, 1 );
			var eased    = easeOutQuart( progress );
			var current  = startVal + ( target - startVal ) * eased;

			// Format: use integer if target is integer, else 1 decimal
			var formatted;
			if ( target === Math.floor( target ) ) {
				formatted = Math.floor( current ).toLocaleString();
			} else {
				formatted = current.toFixed( 1 );
			}

			el.textContent = prefix + formatted + suffix;

			if ( progress < 1 ) {
				requestAnimationFrame( step );
			} else {
				el.textContent = prefix + ( Number.isInteger( target ) ? target.toLocaleString() : target.toFixed( 1 ) ) + suffix;
			}
		}

		requestAnimationFrame( step );
	}

	/* ================================================================
	   3. SCROLL FADE-IN & SLIDE-UP ANIMATIONS (IntersectionObserver)
	   ================================================================ */
	function initScrollAnimations() {
		if ( ! window.IntersectionObserver ) return;

		// Fade-in elements
		var fadeEls = document.querySelectorAll( '.themis-v3-fade-in' );
		if ( fadeEls.length ) {
			var fadeObserver = new IntersectionObserver( function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						entry.target.classList.add( 'in-view' );
						fadeObserver.unobserve( entry.target );
					}
				} );
			}, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' } );

			fadeEls.forEach( function ( el ) { fadeObserver.observe( el ); } );
		}

		// Slide-up elements
		var slideEls = document.querySelectorAll( '.themis-v3-slide-up' );
		if ( slideEls.length ) {
			var slideObserver = new IntersectionObserver( function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						entry.target.classList.add( 'in-view' );
						slideObserver.unobserve( entry.target );
					}
				} );
			}, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' } );

			slideEls.forEach( function ( el, index ) {
				// Staggered delay for sibling elements
				el.style.transitionDelay = ( index % 6 * 0.08 ) + 's';
				slideObserver.observe( el );
			} );
		}
	}

	/* ================================================================
	   4. JQUERY UI TABS
	   Enhances elements with class .themis-v3-tabs.
	   Falls back to manual tab switching when jQuery UI is unavailable.
	   ================================================================ */
	function initTabs() {
		var $tabContainers = $( '.themis-v3-tabs' );
		if ( ! $tabContainers.length ) return;

		if ( $.fn.tabs ) {
			// jQuery UI Tabs available
			$tabContainers.each( function () {
				$( this ).tabs( {
					activate: function ( event, ui ) {
						// Emit custom event for analytics etc.
						$( this ).trigger( 'tv3:tab-changed', {
							index: ui.newTab.index(),
							label: ui.newTab.text()
						} );
					}
				} );
			} );
		} else {
			// Fallback: manual tab implementation
			$tabContainers.each( function () {
				var $container = $( this );
				var $navLinks  = $container.find( '> ul > li > a' );
				var $panels    = $container.find( '.themis-v3-tab-panel' );

				// Show first panel
				$panels.hide();
				$panels.first().show();
				$navLinks.first().css( { color: '#0078d4', borderBottomColor: '#0078d4' } );

				$navLinks.on( 'click', function ( e ) {
					e.preventDefault();
					var targetId = $( this ).attr( 'href' );
					var $target  = $( targetId );

					if ( ! $target.length ) return;

					// Reset all tabs
					$navLinks.css( { color: '#6c7f96', borderBottomColor: 'transparent' } );
					$panels.hide();

					// Activate clicked tab
					$( this ).css( { color: '#0078d4', borderBottomColor: '#0078d4' } );
					$target.show();
				} );
			} );
		}
	}

	/* ================================================================
	   5. JQUERY ACCORDION
	   Enhances elements with class .themis-v3-accordion.
	   ================================================================ */
	function initAccordions() {
		var $accordions = $( '.themis-v3-accordion' );
		if ( ! $accordions.length ) return;

		if ( $.fn.accordion ) {
			$accordions.each( function () {
				$( this ).accordion( {
					heightStyle: 'content',
					animate:     250,
					collapsible: true
				} );
			} );
		} else {
			// Manual accordion fallback
			$accordions.each( function () {
				var $acc     = $( this );
				var $headers = $acc.find( '.ui-accordion-header' );
				var $panels  = $acc.find( '.ui-accordion-content' );

				$panels.not( ':first' ).hide();
				$headers.first().addClass( 'ui-state-active' );

				$headers.on( 'click', function () {
					var $h = $( this );
					var $p = $h.next( '.ui-accordion-content' );
					var isActive = $h.hasClass( 'ui-state-active' );

					$headers.removeClass( 'ui-state-active' );
					$panels.slideUp( 200 );

					if ( ! isActive ) {
						$h.addClass( 'ui-state-active' );
						$p.slideDown( 200 );
					}
				} );
			} );
		}
	}

	/* ================================================================
	   6. SMOOTH SCROLL FOR ANCHOR LINKS
	   Handles links with href="#section-id" for in-page navigation.
	   ================================================================ */
	function initSmoothScroll() {
		$( document ).on( 'click', 'a[href^="#"]', function ( e ) {
			var href   = $( this ).attr( 'href' );
			var target = $( href );

			// Only intercept if target exists and is not an empty hash
			if ( href === '#' || ! target.length ) return;

			e.preventDefault();

			var headerHeight = $( '.site-header, .tv3-header' ).outerHeight( true ) || 0;
			var offset       = target.offset().top - headerHeight - 16;

			$( 'html, body' ).animate( { scrollTop: offset }, {
				duration: 600,
				easing:   'swing',
				complete: function () {
					// Set focus for accessibility
					if ( ! target.is( ':focusable' ) ) {
						target.attr( 'tabindex', '-1' );
					}
					target.focus();
					// Update URL hash without triggering scroll
					if ( history.pushState ) {
						history.pushState( null, null, href );
					}
				}
			} );
		} );
	}

} )( jQuery );
