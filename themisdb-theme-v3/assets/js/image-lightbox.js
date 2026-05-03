/**
 * ThemisDB v3 - Image Lightbox
 * Opens article images in a keyboard-accessible overlay.
 */
( function () {
    'use strict';

    document.addEventListener( 'DOMContentLoaded', function () {
        var imageNodes = Array.from( document.querySelectorAll( '.tv3-rich-post-content img' ) ).filter( isEligibleImage );

        if ( ! imageNodes.length ) {
            return;
        }

        var state = {
            images: imageNodes,
            index: 0,
            scale: 1,
            previousFocus: null,
        };

        var modal = buildModal();
        document.body.appendChild( modal.root );

        imageNodes.forEach( function ( img, idx ) {
            img.classList.add( 'tv3-lightbox-trigger' );
            img.setAttribute( 'tabindex', '0' );
            img.setAttribute( 'role', 'button' );
            img.setAttribute( 'aria-label', 'Bild in Grossansicht oeffnen' );

            img.addEventListener( 'click', function ( event ) {
                event.preventDefault();
                openAtIndex( idx );
            } );

            img.addEventListener( 'keydown', function ( event ) {
                if ( event.key === 'Enter' || event.key === ' ' ) {
                    event.preventDefault();
                    openAtIndex( idx );
                }
            } );
        } );

        modal.backdrop.addEventListener( 'click', close );
        modal.close.addEventListener( 'click', close );
        modal.prev.addEventListener( 'click', function ( event ) {
            event.stopPropagation();
            showRelative( -1 );
        } );
        modal.next.addEventListener( 'click', function ( event ) {
            event.stopPropagation();
            showRelative( 1 );
        } );

        document.addEventListener( 'keydown', function ( event ) {
            if ( ! modal.root.classList.contains( 'is-open' ) ) {
                return;
            }
            if ( event.key === 'Escape' ) {
                close();
                return;
            }
            if ( event.key === 'ArrowLeft' ) {
                showRelative( -1 );
                return;
            }
            if ( event.key === 'ArrowRight' ) {
                showRelative( 1 );
                return;
            }
            if ( event.key === '+' || event.key === '=' ) {
                event.preventDefault();
                setScale( state.scale + 0.25 );
                return;
            }
            if ( event.key === '-' ) {
                event.preventDefault();
                setScale( state.scale - 0.25 );
            }
        } );

        modal.image.addEventListener( 'wheel', function ( event ) {
            if ( ! modal.root.classList.contains( 'is-open' ) ) {
                return;
            }
            event.preventDefault();
            var step = event.deltaY < 0 ? 0.2 : -0.2;
            setScale( state.scale + step );
        }, { passive: false } );

        modal.image.addEventListener( 'dblclick', function ( event ) {
            event.preventDefault();
            if ( state.scale > 1.1 ) {
                setScale( 1 );
            } else {
                setScale( 2 );
            }
        } );

        var lastTapAt = 0;
        modal.image.addEventListener( 'touchend', function () {
            var now = Date.now();
            if ( now - lastTapAt < 280 ) {
                if ( state.scale > 1.1 ) {
                    setScale( 1 );
                } else {
                    setScale( 2 );
                }
            }
            lastTapAt = now;
        } );

        function openAtIndex( idx ) {
            state.previousFocus = document.activeElement;
            state.index = idx;
            renderCurrent();
            modal.root.classList.add( 'is-open' );
            modal.root.setAttribute( 'aria-hidden', 'false' );
            document.body.classList.add( 'tv3-lightbox-open' );
            modal.close.focus();
        }

        function close() {
            modal.root.classList.remove( 'is-open' );
            modal.root.setAttribute( 'aria-hidden', 'true' );
            document.body.classList.remove( 'tv3-lightbox-open' );
            setScale( 1 );
            if ( state.previousFocus && typeof state.previousFocus.focus === 'function' ) {
                state.previousFocus.focus();
            }
        }

        function showRelative( delta ) {
            state.index = ( state.index + delta + state.images.length ) % state.images.length;
            renderCurrent();
        }

        function renderCurrent() {
            var current = state.images[ state.index ];
            var src = current.getAttribute( 'data-tv3-full' ) || current.currentSrc || current.src;
            var alt = ( current.getAttribute( 'alt' ) || '' ).trim();
            var caption = alt || current.getAttribute( 'title' ) || '';

            modal.image.src = src;
            modal.image.alt = alt || 'Bildansicht';
            modal.caption.textContent = caption;
            modal.counter.textContent = ( state.index + 1 ) + ' / ' + state.images.length;
            setScale( 1 );
        }

        function setScale( nextScale ) {
            var clamped = Math.max( 1, Math.min( 4, nextScale ) );
            state.scale = Math.round( clamped * 100 ) / 100;
            modal.image.style.transform = 'scale(' + state.scale + ')';
            modal.image.style.cursor = state.scale > 1 ? 'zoom-out' : 'zoom-in';
        }
    } );

    function isEligibleImage( img ) {
        if ( ! img || ! img.src ) {
            return false;
        }

        if ( img.closest( '.tv3-media-list, .tv3-media-item, .wp-block-audio' ) ) {
            return false;
        }

        if ( img.classList.contains( 'emoji' ) || img.classList.contains( 'wp-smiley' ) ) {
            return false;
        }

        var className = String( img.className || '' ).toLowerCase();
        if ( /(?:icon|logo|avatar|emoji|smiley)/.test( className ) ) {
            return false;
        }

        var widthAttr = parseInt( img.getAttribute( 'width' ) || '0', 10 );
        var heightAttr = parseInt( img.getAttribute( 'height' ) || '0', 10 );
        if ( widthAttr > 0 && heightAttr > 0 && Math.max( widthAttr, heightAttr ) <= 140 ) {
            return false;
        }

        var anchor = img.closest( 'a' );
        if ( anchor ) {
            var href = String( anchor.getAttribute( 'href' ) || '' );
            var imageLink = /\.(png|jpe?g|webp|gif|avif|svg)(\?.*)?$/i.test( href );
            if ( href && ! imageLink && href !== img.src ) {
                return false;
            }
        }

        return true;
    }

    function buildModal() {
        var root = document.createElement( 'div' );
        root.className = 'tv3-lightbox';
        root.setAttribute( 'aria-hidden', 'true' );

        var backdrop = document.createElement( 'div' );
        backdrop.className = 'tv3-lightbox__backdrop';

        var dialog = document.createElement( 'div' );
        dialog.className = 'tv3-lightbox__dialog';
        dialog.setAttribute( 'role', 'dialog' );
        dialog.setAttribute( 'aria-modal', 'true' );

        var close = document.createElement( 'button' );
        close.className = 'tv3-lightbox__close';
        close.type = 'button';
        close.setAttribute( 'aria-label', 'Lightbox schliessen' );
        close.textContent = 'x';

        var prev = document.createElement( 'button' );
        prev.className = 'tv3-lightbox__nav tv3-lightbox__nav--prev';
        prev.type = 'button';
        prev.setAttribute( 'aria-label', 'Vorheriges Bild' );
        prev.textContent = '<';

        var next = document.createElement( 'button' );
        next.className = 'tv3-lightbox__nav tv3-lightbox__nav--next';
        next.type = 'button';
        next.setAttribute( 'aria-label', 'Naechstes Bild' );
        next.textContent = '>';

        var image = document.createElement( 'img' );
        image.className = 'tv3-lightbox__image';
        image.loading = 'eager';
        image.decoding = 'sync';

        var footer = document.createElement( 'div' );
        footer.className = 'tv3-lightbox__footer';

        var caption = document.createElement( 'div' );
        caption.className = 'tv3-lightbox__caption';

        var counter = document.createElement( 'div' );
        counter.className = 'tv3-lightbox__counter';

        var zoomHint = document.createElement( 'div' );
        zoomHint.className = 'tv3-lightbox__zoom-hint';
        zoomHint.textContent = 'Mausrad oder Doppelklick zum Zoomen';

        footer.appendChild( caption );
        footer.appendChild( counter );
        footer.appendChild( zoomHint );

        dialog.appendChild( close );
        dialog.appendChild( prev );
        dialog.appendChild( image );
        dialog.appendChild( next );
        dialog.appendChild( footer );

        root.appendChild( backdrop );
        root.appendChild( dialog );

        return {
            root: root,
            backdrop: backdrop,
            dialog: dialog,
            close: close,
            prev: prev,
            next: next,
            image: image,
            caption: caption,
            counter: counter,
            zoomHint: zoomHint,
        };
    }
} )();
