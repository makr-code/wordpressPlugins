<?php
/**
 * AQM_Config – zentrale Konfiguration, alles per Filter überschreibbar.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AQM_Config {

    // -------------------------------------------------------------------------
    // Meta-Prefix
    // -------------------------------------------------------------------------

    /**
     * Präfix für alle Post-Meta-Keys.
     * Default: 'tv3_' (Rückwärtskompatibilität zu themisdb-theme-v3).
     * Überschreiben: add_filter('aqm_meta_prefix', fn() => 'mysite_');
     */
    public static function prefix(): string {
        static $cache = null;
        if ( $cache === null ) {
            $cache = (string) apply_filters( 'aqm_meta_prefix', 'tv3_' );
        }
        return $cache;
    }

    // -------------------------------------------------------------------------
    // Kriterien-Definition
    // -------------------------------------------------------------------------

    /**
     * Gibt die Bewertungs-Kriterien zurück.
     *
     * Format:
     *   'key' => ['label' => 'Anzeige-Label']
     *
     * Der Key bestimmt die Meta-Key-Ableitung:
     *   AI-Score:   {prefix}{key}_score   z.B. tv3_quality_score
     *   User-Avg:   {prefix}uf_{key}      z.B. tv3_uf_quality
     *
     * Erweiterungsbeispiel (Podcast-Audio):
     *   add_filter('aqm_criteria', function($c) {
     *       $c['audio_quality'] = ['label' => 'Audioqualität'];
     *       return $c;
     *   });
     */
    public static function criteria(): array {
        $defaults = array(
            'quality'     => array( 'label' => __( 'Qualität',   'aqm' ) ),
            'impact'      => array( 'label' => __( 'Impact',     'aqm' ) ),
            'readability' => array( 'label' => __( 'Lesbarkeit', 'aqm' ) ),
            'fidelity'    => array( 'label' => __( 'Treue',      'aqm' ) ),
        );
        return (array) apply_filters( 'aqm_criteria', $defaults );
    }

    // -------------------------------------------------------------------------
    // Abgeleitete Meta-Keys
    // -------------------------------------------------------------------------

    /** AI-Score-Meta-Key für ein Kriterium, z.B. tv3_quality_score. */
    public static function ai_meta( string $key ): string {
        return self::prefix() . $key . '_score';
    }

    /** User-Feedback-Meta-Key für ein Kriterium, z.B. tv3_uf_quality. */
    public static function uf_meta( string $key ): string {
        return self::prefix() . 'uf_' . $key;
    }

    /** Gesamtzähler User-Stimmen, z.B. tv3_uf_count. */
    public static function uf_count_meta(): string {
        return self::prefix() . 'uf_count';
    }

    /** Freitext-Zusammenfassung der KI-Bewertung, z.B. tv3_quality_summary. */
    public static function summary_meta(): string {
        return self::prefix() . 'quality_summary';
    }

    /** KI-Modellnamen (co-authors), z.B. tv3_ai_coauthors. */
    public static function ai_coauthors_meta(): string {
        return self::prefix() . 'ai_coauthors';
    }

    // -------------------------------------------------------------------------
    // REST-Namespace
    // -------------------------------------------------------------------------

    /**
     * REST-API-Namespace.
     * Default: 'aqm/v1'
     * Rückwärtskompatibel: themisdb/v1 wird als Alias registriert.
     */
    public static function rest_namespace(): string {
        return (string) apply_filters( 'aqm_rest_namespace', 'aqm/v1' );
    }

    // -------------------------------------------------------------------------
    // Score-Kombination
    // -------------------------------------------------------------------------

    /**
     * Kombiniert KI- und User-Score.
     *
     * Unter $threshold Stimmen gilt 100 % KI.
     * Ab $threshold: KI * (1 - user_weight) + User * user_weight.
     *
     * Überschreibbar:
     *   add_filter('aqm_blend_threshold', fn() => 5);
     *   add_filter('aqm_user_weight',     fn() => 0.3);
     */
    public static function combined_score( int $ai, int $user_avg, int $count ): int {
        $threshold   = max( 1, (int) apply_filters( 'aqm_blend_threshold', 3 ) );
        $user_weight = min( 1.0, max( 0.0, (float) apply_filters( 'aqm_user_weight', 0.4 ) ) );

        if ( $count >= $threshold && $user_avg > 0 && $ai > 0 ) {
            return (int) round( $ai * ( 1.0 - $user_weight ) + $user_avg * $user_weight );
        }
        return $ai;
    }

    // -------------------------------------------------------------------------
    // Post-Types
    // -------------------------------------------------------------------------

    /** Post-Types auf denen Meta registriert wird. */
    public static function post_types(): array {
        return (array) apply_filters( 'aqm_post_types', array( 'post', 'page' ) );
    }
}
