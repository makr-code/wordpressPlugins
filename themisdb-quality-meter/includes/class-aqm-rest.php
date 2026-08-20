<?php
/**
 * AQM_Rest – REST-Endpunkte für:
 *   GET  /aqm/v1/feedback?post_id=N   → Session-Feedback lesen
 *   POST /aqm/v1/feedback             → User-Feedback speichern (anonym, session-basiert)
 *   POST /aqm/v1/scores               → KI-Scores schreiben (edit_posts erforderlich)
 *
 * Rückwärtskompatibilität: themisdb/v1/quality-feedback wird als Alias mitregistriert.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AQM_Rest {

    public static function register_routes(): void {
        $ns = AQM_Config::rest_namespace();

        // ── User-Feedback ────────────────────────────────────────────────────
        register_rest_route( $ns, '/feedback', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( __CLASS__, 'get_feedback' ),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'post_id' => array( 'required' => true, 'type' => 'integer', 'minimum' => 1 ),
                ),
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( __CLASS__, 'save_feedback' ),
                'permission_callback' => '__return_true',
                'args'                => self::feedback_args(),
            ),
        ) );

        // ── KI-Scores schreiben (Import-Pipeline / externe KI-Tools) ─────────
        register_rest_route( $ns, '/scores', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( __CLASS__, 'save_scores' ),
            'permission_callback' => static function () {
                return current_user_can( 'edit_posts' );
            },
            'args'                => self::score_args(),
        ) );

        // ── Rückwärtskompatibilität: themisdb/v1/quality-feedback ─────────────
        if ( $ns !== 'themisdb/v1' ) {
            register_rest_route( 'themisdb/v1', '/quality-feedback', array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( __CLASS__, 'get_feedback' ),
                    'permission_callback' => '__return_true',
                    'args'                => array(
                        'post_id' => array( 'required' => true, 'type' => 'integer', 'minimum' => 1 ),
                    ),
                ),
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( __CLASS__, 'save_feedback' ),
                    'permission_callback' => '__return_true',
                    'args'                => self::feedback_args(),
                ),
            ) );
        }
    }

    // -------------------------------------------------------------------------
    // GET /feedback
    // -------------------------------------------------------------------------

    public static function get_feedback( WP_REST_Request $req ): WP_REST_Response {
        self::ensure_session();
        $post_id = (int) $req->get_param( 'post_id' );
        $key     = 'aqm_qf_' . $post_id;
        return new WP_REST_Response( array( 'feedback' => $_SESSION[ $key ] ?? null ), 200 );
    }

    // -------------------------------------------------------------------------
    // POST /feedback
    // -------------------------------------------------------------------------

    public static function save_feedback( WP_REST_Request $req ): WP_REST_Response {
        self::ensure_session();
        $post_id = (int) $req->get_param( 'post_id' );
        $key     = 'aqm_qf_' . $post_id;
        $counted = 'aqm_counted_' . $post_id;

        $already_counted = ! empty( $_SESSION[ $counted ] );
        $stored          = (array) ( $_SESSION[ $key ] ?? array() );
        $new_values      = array();

        foreach ( array_keys( AQM_Config::criteria() ) as $criterion ) {
            $val = $req->get_param( $criterion );
            if ( null !== $val ) {
                $safe                   = max( 0, min( 100, (int) $val ) );
                $new_values[ $criterion ] = $safe;
                $stored[ $criterion ]     = $safe;
            }
        }
        $_SESSION[ $key ] = $stored;

        // Gleitenden Durchschnitt in Post-Meta persistieren.
        if ( ! empty( $new_values ) && get_post( $post_id ) ) {
            $old_count = (int) get_post_meta( $post_id, AQM_Config::uf_count_meta(), true );
            $new_count = $already_counted ? max( 1, $old_count ) : $old_count + 1;

            foreach ( $new_values as $criterion => $val ) {
                $meta_key = AQM_Config::uf_meta( $criterion );
                $old_avg  = (int) get_post_meta( $post_id, $meta_key, true );

                if ( $already_counted && $old_count >= 1 ) {
                    // Korrektur: alten Session-Beitrag herausrechnen.
                    $old_session = isset( $stored[ $criterion ] ) ? (int) $stored[ $criterion ] : $val;
                    $new_avg = (int) round( ( $old_avg * $old_count - $old_session + $val ) / $old_count );
                } else {
                    $new_avg = $new_count > 1
                        ? (int) round( ( $old_avg * $old_count + $val ) / $new_count )
                        : $val;
                }
                update_post_meta( $post_id, $meta_key, max( 0, min( 100, $new_avg ) ) );
            }
            update_post_meta( $post_id, AQM_Config::uf_count_meta(), $new_count );
            $_SESSION[ $counted ] = true;
        }

        return new WP_REST_Response( array( 'saved' => true, 'feedback' => $stored ), 200 );
    }

    // -------------------------------------------------------------------------
    // POST /scores  (Import-Pipeline / externe KI)
    // -------------------------------------------------------------------------

    /**
     * Schreibt KI-Scores für einen Post.
     * Payload: { post_id, scores: { quality: 85, impact: 72, … }, summary: "…", ai_model: "gemma4" }
     */
    public static function save_scores( WP_REST_Request $req ): WP_REST_Response {
        $post_id = (int) $req->get_param( 'post_id' );
        if ( ! get_post( $post_id ) ) {
            return new WP_REST_Response( array( 'error' => 'post_not_found' ), 404 );
        }

        $scores = $req->get_param( 'scores' );
        if ( is_array( $scores ) ) {
            foreach ( array_keys( AQM_Config::criteria() ) as $key ) {
                if ( isset( $scores[ $key ] ) && is_numeric( $scores[ $key ] ) ) {
                    $val = max( 0, min( 100, (int) $scores[ $key ] ) );
                    update_post_meta( $post_id, AQM_Config::ai_meta( $key ), (string) $val );
                }
            }
        }

        $summary = $req->get_param( 'summary' );
        if ( is_string( $summary ) ) {
            update_post_meta( $post_id, AQM_Config::summary_meta(), sanitize_text_field( $summary ) );
        }

        $model = $req->get_param( 'ai_model' );
        if ( is_string( $model ) ) {
            $model = trim( $model );
            update_post_meta( $post_id, AQM_Config::ai_coauthors_meta(), sanitize_text_field( $model ) );
            if ( function_exists( 'themisdb_v3_assign_ai_coauthors_to_post' ) ) {
                themisdb_v3_assign_ai_coauthors_to_post( $post_id, $model );
            }
        }

        return new WP_REST_Response( array( 'saved' => true, 'post_id' => $post_id ), 200 );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private static function ensure_session(): void {
        if ( session_status() === PHP_SESSION_NONE ) {
            session_start();
        }
    }

    private static function feedback_args(): array {
        $args = array(
            'post_id' => array( 'required' => true, 'type' => 'integer', 'minimum' => 1 ),
        );
        foreach ( array_keys( AQM_Config::criteria() ) as $key ) {
            $args[ $key ] = array(
                'required' => false,
                'type'     => 'integer',
                'minimum'  => 0,
                'maximum'  => 100,
            );
        }
        return $args;
    }

    private static function score_args(): array {
        return array(
            'post_id'  => array( 'required' => true, 'type' => 'integer', 'minimum' => 1 ),
            'scores'   => array( 'required' => false, 'type' => 'object' ),
            'summary'  => array( 'required' => false, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
            'ai_model' => array( 'required' => false, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
        );
    }
}
