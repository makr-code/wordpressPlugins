<?php
/**
 * AQM_Assets – enqueued JS + CSS des Quality-Meter-Plugins.
 *
 * Das Script wird nur geladen wenn:
 *   a) is_singular() AND der Post hat mindestens einen KI-Score, ODER
 *   b) do_action('aqm_force_enqueue') wurde im Theme aufgerufen.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AQM_Assets {

    private static bool $enqueued = false;

    public static function enqueue(): void {
        // Früh-Ausstieg: nur im Frontend, nur auf Einzelseiten.
        if ( ! is_singular() ) {
            return;
        }

        $post_id = get_the_ID();
        if ( ! $post_id ) {
            return;
        }

        // Hat dieser Post mindestens einen KI-Score?
        if ( ! self::post_has_score( $post_id ) ) {
            // Hook für Themes, um das Laden trotzdem zu erzwingen.
            if ( ! self::$enqueued ) {
                add_action( 'aqm_force_enqueue', array( __CLASS__, 'do_enqueue' ) );
            }
            return;
        }

        self::do_enqueue();
    }

    public static function do_enqueue(): void {
        if ( self::$enqueued ) {
            return;
        }
        self::$enqueued = true;

        wp_enqueue_style(
            'aqm-base',
            AQM_ASSETS . 'css/quality-meter-base.css',
            array(),
            AQM_VERSION
        );

        wp_enqueue_script(
            'aqm-feedback',
            AQM_ASSETS . 'js/quality-feedback.js',
            array(),       // kein jQuery-Dependency nötig
            AQM_VERSION,
            true           // im Footer laden
        );
    }

    // -------------------------------------------------------------------------
    // Hilfsfunktion
    // -------------------------------------------------------------------------

    private static function post_has_score( int $post_id ): bool {
        foreach ( array_keys( AQM_Config::criteria() ) as $key ) {
            $val = get_post_meta( $post_id, AQM_Config::ai_meta( $key ), true );
            if ( '' !== $val && is_numeric( $val ) && (int) $val > 0 ) {
                return true;
            }
        }
        return false;
    }
}
