<?php
/**
 * AQM_Meta – registriert Post-Meta dynamisch für alle konfigurierten Kriterien.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AQM_Meta {

    public static function register(): void {
        $string_field = array(
            'type'              => 'string',
            'single'            => true,
            'show_in_rest'      => true,
            'default'           => '',
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback'     => static function () {
                return current_user_can( 'edit_posts' );
            },
        );

        // Schreibgeschützt aus Frontend-Sicht; nur Import-Pipeline schreibt.
        $uf_field = array(
            'type'              => 'integer',
            'single'            => true,
            'show_in_rest'      => false,
            'default'           => 0,
            'sanitize_callback' => 'absint',
            'auth_callback'     => '__return_true',
        );

        foreach ( AQM_Config::post_types() as $pt ) {
            // Pro Kriterium: KI-Score + User-Feedback-Durchschnitt
            foreach ( array_keys( AQM_Config::criteria() ) as $key ) {
                register_post_meta( $pt, AQM_Config::ai_meta( $key ), $string_field );
                register_post_meta( $pt, AQM_Config::uf_meta( $key ), $uf_field );
            }

            // Gemeinsame Felder
            register_post_meta( $pt, AQM_Config::uf_count_meta(),   $uf_field );
            register_post_meta( $pt, AQM_Config::summary_meta(),    $string_field );
            register_post_meta( $pt, AQM_Config::ai_coauthors_meta(), $string_field );
        }
    }

    // -------------------------------------------------------------------------
    // Lesehilfen
    // -------------------------------------------------------------------------

    /**
     * Liest alle KI-Scores eines Posts als assoziatives Array.
     *
     * @param int $post_id
     * @return array<string, int>  key => score (0 wenn nicht gesetzt)
     */
    public static function get_ai_scores( int $post_id ): array {
        $scores = array();
        foreach ( array_keys( AQM_Config::criteria() ) as $key ) {
            $raw = get_post_meta( $post_id, AQM_Config::ai_meta( $key ), true );
            $scores[ $key ] = '' !== $raw && is_numeric( $raw )
                ? max( 0, min( 100, (int) $raw ) )
                : 0;
        }
        return $scores;
    }

    /**
     * Liest alle User-Feedback-Durchschnitte eines Posts.
     *
     * @param int $post_id
     * @return array{scores: array<string, int>, count: int}
     */
    public static function get_uf_data( int $post_id ): array {
        $scores = array();
        foreach ( array_keys( AQM_Config::criteria() ) as $key ) {
            $scores[ $key ] = (int) get_post_meta( $post_id, AQM_Config::uf_meta( $key ), true );
        }
        return array(
            'scores' => $scores,
            'count'  => (int) get_post_meta( $post_id, AQM_Config::uf_count_meta(), true ),
        );
    }

    /**
     * Öffentliche Hilfsfunktion: kombinierte Scores für ein Post.
     * Kann vom Theme via aqm_get_scores($post_id) aufgerufen werden.
     *
     * @return array{key: array{ai: int, user: int, combined: int, count: int}}
     */
    public static function get_combined_scores( int $post_id ): array {
        $ai_scores = self::get_ai_scores( $post_id );
        $uf_data   = self::get_uf_data( $post_id );
        $count     = $uf_data['count'];
        $result    = array();

        foreach ( array_keys( AQM_Config::criteria() ) as $key ) {
            $ai   = $ai_scores[ $key ];
            $user = $uf_data['scores'][ $key ] ?? 0;
            $result[ $key ] = array(
                'ai'       => $ai,
                'user'     => $user,
                'combined' => AQM_Config::combined_score( $ai, $user, $count ),
                'count'    => $count,
            );
        }
        return $result;
    }
}

/**
 * Theme-API: aqm_get_scores( $post_id )
 * Gibt kombinierte Scores zurück, nutzbar in Card-Rendering etc.
 */
function aqm_get_scores( int $post_id ): array {
    return AQM_Meta::get_combined_scores( $post_id );
}
