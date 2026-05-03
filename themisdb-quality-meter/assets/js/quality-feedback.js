/**
 * Quality Feedback – Interaktive Bewertungs-Slider
 *
 * Unterstützt:
 *   [data-aqm='…']   – Plugin (aqm/v1)
 *   [data-qf='…']    – Theme-Legacy (themisdb/v1) – rückwärtskompatibel
 *
 * Slider-Anzahl ist beliebig skalierbar: jedes .tv3-qm-slider[data-key] Element
 * wird automatisch erkannt – kein Hardcoding der Kriterien im JS.
 */
( function () {
    'use strict';

    // ── Toast ────────────────────────────────────────────────────────────────

    /** Zeigt einen kurzen Toast; bevorzugt im Widget, sonst am Body. */
    function showToast( widget, msg ) {
        var toast = widget.querySelector( '.tv3-qm-toast, .aqm-toast' );
        if ( ! toast ) {
            toast = document.createElement( 'div' );
            toast.className = 'tv3-qm-toast aqm-toast';
            document.body.appendChild( toast );
        }
        toast.textContent = msg;
        toast.classList.add( 'tv3-qm-toast--visible' );
        clearTimeout( toast._aqmTimer );
        toast._aqmTimer = setTimeout( function () {
            toast.classList.remove( 'tv3-qm-toast--visible' );
        }, 2200 );
    }

    // ── Balken-Update ────────────────────────────────────────────────────────

    /** Berechnet den kombinierten Farbton (0–120°, rot → grün). */
    function hslColor( val ) {
        return 'hsl(' + Math.round( val * 1.2 ) + ',60%,42%)';
    }

    /**
     * Aktualisiert Balken und Zahlenwert einer Zeile.
     * Zeigt KI-Wert solange kein User-Feedback, danach Blend-Vorschau.
     */
    function updateBar( row, aiVal, userVal, hasUserFb ) {
        var combined = hasUserFb ? Math.round( aiVal * 0.6 + userVal * 0.4 ) : aiVal;
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

    // ── REST-Persistenz ───────────────────────────────────────────────────────

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
            /* Stille Fehler – kein UI-Absturz, Toast genügt. */
        } );
    }

    // ── Reset ────────────────────────────────────────────────────────────────

    function resetFeedback( widget, config ) {
        var sliders = widget.querySelectorAll( '.tv3-qm-slider' );

        // Slider auf KI-Wert zurücksetzen
        sliders.forEach( function ( slider ) {
            var aiVal   = parseInt( slider.getAttribute( 'data-ai' ), 10 );
            slider.value = aiVal;
            slider.removeAttribute( 'data-active' );
            var row = slider.closest( '.tv3-qm-row, .aqm-row' );
            if ( row ) {
                updateBar( row, aiVal, aiVal, false );
            }
        } );

        // Reset-Payload: alle Kriterien auf KI-Wert setzen
        var payload = {};
        sliders.forEach( function ( slider ) {
            var key   = slider.getAttribute( 'data-key' );
            var aiVal = parseInt( slider.getAttribute( 'data-ai' ), 10 );
            if ( key ) {
                payload[ key ] = aiVal;
            }
        } );

        saveFeedback( config, payload, function () {
            // Legacy-Theme: Feedback-Hinweis zurücksetzen
            var note = widget.querySelector( '.tv3-qm-feedback-note' );
            if ( note ) {
                note.className = 'tv3-qm-feedback-note';
                note.innerHTML = 'Schieberegler: Ihre Bewertung';
            }
            showToast( widget, 'Bewertung zurückgesetzt.' );
        } );
    }

    // ── Widget-Initialisierung ────────────────────────────────────────────────

    function initWidget( widget ) {
        // Plugin-Attribut (data-aqm) hat Vorrang; Fallback auf Theme-Legacy (data-qf)
        var raw = widget.getAttribute( 'data-aqm' ) || widget.getAttribute( 'data-qf' );
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
                var row     = slider.closest( '.tv3-qm-row, .aqm-row' );
                if ( row ) {
                    updateBar( row, aiVal, userVal, true );
                }
            } );

            // Debounced Speichern nach Loslassen (400 ms)
            slider.addEventListener( 'change', function () {
                slider.setAttribute( 'data-active', '1' );
                clearTimeout( debounceTimer );
                debounceTimer = setTimeout( function () {
                    var payload = {};
                    widget.querySelectorAll( '.tv3-qm-slider' ).forEach( function ( s ) {
                        var key = s.getAttribute( 'data-key' );
                        if ( key ) {
                            payload[ key ] = parseInt( s.value, 10 );
                        }
                    } );

                    saveFeedback( config, payload, function () {
                        // Legacy-Theme: Feedback-Hinweis aktualisieren
                        var note = widget.querySelector( '.tv3-qm-feedback-note' );
                        if ( note ) {
                            note.className = 'tv3-qm-feedback-note tv3-qm-feedback-active';
                            note.innerHTML = 'Ihre Bewertung ist eingeflossen '
                                + '<button class="tv3-qm-reset aqm-reset" type="button" aria-label="Bewertung zurücksetzen">↺</button>';
                            var innerBtn = note.querySelector( '.tv3-qm-reset' );
                            if ( innerBtn ) {
                                innerBtn.addEventListener( 'click', function () {
                                    resetFeedback( widget, config );
                                } );
                            }
                        }
                        showToast( widget, 'Bewertung gespeichert ✓' );
                    } );
                }, 400 );
            } );
        } );

        // ── Reset-Button ────────────────────────────────────────────────────
        widget.querySelectorAll( '.tv3-qm-reset, .aqm-reset' ).forEach( function ( btn ) {
            btn.addEventListener( 'click', function () {
                resetFeedback( widget, config );
            } );
        } );
    }

    // ── Bootstrap ────────────────────────────────────────────────────────────

    function boot() {
        // Plugin-Wrapper (data-aqm) + Theme-Legacy (data-qf)
        document.querySelectorAll( '[data-aqm], .tv3-quality-meta[data-qf]' )
            .forEach( initWidget );
    }

    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', boot );
    } else {
        boot();
    }
} )();
