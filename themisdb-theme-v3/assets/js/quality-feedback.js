/**
 * Quality Feedback – Interaktive Bewertungs-Slider
 *
 * Ermöglicht dem Benutzer, die KI-Qualitätsbewertung per Slider
 * anzupassen. Bewertungen werden session-basiert via REST API gespeichert.
 * Der angezeigte Wert ist der Durchschnitt aus KI- und Benutzer-Bewertung.
 */
( function () {
    'use strict';

    /** Zeigt einen kurzen Toast-Hinweis. */
    function showToast( msg ) {
        var toast = document.querySelector( '.tv3-qm-toast' );
        if ( ! toast ) {
            toast = document.createElement( 'div' );
            toast.className = 'tv3-qm-toast';
            document.body.appendChild( toast );
        }
        toast.textContent = msg;
        toast.classList.add( 'tv3-qm-toast--visible' );
        clearTimeout( toast._timer );
        toast._timer = setTimeout( function () {
            toast.classList.remove( 'tv3-qm-toast--visible' );
        }, 2200 );
    }

    /** Berechnet den kombinierten Farbton (0–120°, rot→grün). */
    function hslColor( val ) {
        return 'hsl(' + Math.round( val * 1.2 ) + ',60%,42%)';
    }

    /** Aktualisiert den Balken und die Zahl einer Zeile. */
    function updateBar( row, aiVal, userVal, hasUserFb ) {
        var combined = hasUserFb ? Math.round( ( aiVal + userVal ) / 2 ) : aiVal;
        var bar      = row.querySelector( '.tv3-qm-bar' );
        var valSpan  = row.querySelector( '.tv3-qm-val' );
        if ( bar ) {
            bar.style.width      = combined + '%';
            bar.style.background = hslColor( combined );
        }
        if ( valSpan ) {
            valSpan.textContent = combined;
        }
    }

    /** Persistiert ein Feedback-Objekt via REST. */
    function saveFeedback( config, payload, onSuccess ) {
        var body = JSON.stringify( Object.assign( { post_id: config.postId }, payload ) );
        fetch( config.restUrl, {
            method:  'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce':   config.nonce,
            },
            body: body,
        } )
        .then( function ( r ) { return r.ok ? r.json() : Promise.reject( r.status ); } )
        .then( function ( data ) {
            if ( typeof onSuccess === 'function' ) {
                onSuccess( data );
            }
        } )
        .catch( function () {
            showToast( 'Bewertung konnte nicht gespeichert werden.' );
        } );
    }

    /** Setzt alle Slider eines Blocks auf den KI-Wert zurück. */
    function resetFeedback( widget, config ) {
        var sliders = widget.querySelectorAll( '.tv3-qm-slider' );
        sliders.forEach( function ( slider ) {
            var aiVal = parseInt( slider.getAttribute( 'data-ai' ), 10 );
            slider.value = aiVal;
            slider.removeAttribute( 'data-active' );
            var row = slider.closest( '.tv3-qm-row' );
            if ( row ) {
                updateBar( row, aiVal, aiVal, false );
            }
        } );

        // Reset-Payload: alle Kriterien auf KI-Wert setzen
        var payload = {};
        sliders.forEach( function ( slider ) {
            var key   = slider.getAttribute( 'data-key' );
            var aiVal = parseInt( slider.getAttribute( 'data-ai' ), 10 );
            payload[ key ] = aiVal;
        } );

        saveFeedback( config, payload, function () {
            // Feedback-Hinweis zurück auf "neutral"
            var note = widget.querySelector( '.tv3-qm-feedback-note' );
            if ( note ) {
                note.className  = 'tv3-qm-feedback-note';
                note.innerHTML  = 'Schieberegler: Ihre Bewertung';
            }
            showToast( 'Bewertung zurückgesetzt.' );
        } );
    }

    /** Initialisiert einen einzelnen Quality-Meta-Block. */
    function initWidget( widget ) {
        var raw = widget.getAttribute( 'data-qf' );
        if ( ! raw ) { return; }

        var config;
        try { config = JSON.parse( raw ); } catch ( e ) { return; }
        if ( ! config.postId || ! config.restUrl ) { return; }

        var debounceTimer = null;

        // ── Slider-Events ──────────────────────────────────────────────────
        widget.querySelectorAll( '.tv3-qm-slider' ).forEach( function ( slider ) {
            var aiVal = parseInt( slider.getAttribute( 'data-ai' ), 10 );

            // Live-Preview während des Ziehens
            slider.addEventListener( 'input', function () {
                var userVal = parseInt( slider.value, 10 );
                var row     = slider.closest( '.tv3-qm-row' );
                if ( row ) {
                    updateBar( row, aiVal, userVal, true );
                }
            } );

            // Speichern nach Loslassen (debounced 400 ms)
            slider.addEventListener( 'change', function () {
                slider.setAttribute( 'data-active', '1' );
                clearTimeout( debounceTimer );
                debounceTimer = setTimeout( function () {
                    // Alle aktuellen Slider-Werte sammeln
                    var payload = {};
                    widget.querySelectorAll( '.tv3-qm-slider' ).forEach( function ( s ) {
                        payload[ s.getAttribute( 'data-key' ) ] = parseInt( s.value, 10 );
                    } );

                    saveFeedback( config, payload, function () {
                        // Feedback-Hinweis aktualisieren
                        var note = widget.querySelector( '.tv3-qm-feedback-note' );
                        if ( note ) {
                            note.className = 'tv3-qm-feedback-note tv3-qm-feedback-active';
                            note.innerHTML = 'Ihre Bewertung ist eingeflossen '
                                + '<button class="tv3-qm-reset" type="button" aria-label="Bewertung zurücksetzen">↺</button>';
                            // Reset-Button neu verbinden
                            var resetBtn = note.querySelector( '.tv3-qm-reset' );
                            if ( resetBtn ) {
                                resetBtn.addEventListener( 'click', function () {
                                    resetFeedback( widget, config );
                                } );
                            }
                        }
                        showToast( 'Bewertung gespeichert ✓' );
                    } );
                }, 400 );
            } );
        } );

        // ── Reset-Button (initial, falls Session-Daten vorhanden) ──────────
        var initialReset = widget.querySelector( '.tv3-qm-reset' );
        if ( initialReset ) {
            initialReset.addEventListener( 'click', function () {
                resetFeedback( widget, config );
            } );
        }
    }

    // ── Bootstrap ────────────────────────────────────────────────────────────
    function boot() {
        document.querySelectorAll( '.tv3-quality-meta[data-qf]' ).forEach( initWidget );
    }

    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', boot );
    } else {
        boot();
    }
} )();
