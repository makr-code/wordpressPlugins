( function () {
	'use strict';

	function ready( fn ) {
		if ( document.readyState === 'loading' ) {
			document.addEventListener( 'DOMContentLoaded', fn );
			return;
		}
		fn();
	}

	ready( function () {
		var cfg = window.themisdbV3OrderOverlay || {};
		var overlay = document.getElementById( 'themisdb-order-overlay' );
		var openButtons = document.querySelectorAll( '[data-themisdb-open-order-overlay]' );

		if ( ! overlay || ! openButtons.length ) {
			return;
		}

		var body = document.body;
		var closeTargets = overlay.querySelectorAll( '[data-themisdb-close-order-overlay]' );

		function openOverlay() {
			overlay.hidden = false;
			overlay.setAttribute( 'aria-hidden', 'false' );
			body.classList.add( 'themisdb-order-overlay-open' );
		}

		function closeOverlay() {
			overlay.hidden = true;
			overlay.setAttribute( 'aria-hidden', 'true' );
			body.classList.remove( 'themisdb-order-overlay-open' );
		}

		openButtons.forEach( function ( button ) {
			button.setAttribute( 'aria-label', cfg.openLabel || 'Neuen Auftrag oeffnen' );
			button.addEventListener( 'click', function ( event ) {
				event.preventDefault();
				openOverlay();
			} );
		} );

		closeTargets.forEach( function ( el ) {
			el.addEventListener( 'click', function () {
				closeOverlay();
			} );
		} );

		document.addEventListener( 'keydown', function ( event ) {
			if ( event.key === 'Escape' && ! overlay.hidden ) {
				closeOverlay();
			}
		} );
	} );
}() );