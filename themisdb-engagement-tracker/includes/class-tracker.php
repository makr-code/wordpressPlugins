<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * REST endpoint and JS enqueueing for engagement event tracking.
 */
class TDET_Tracker {

    const META_VIEWS       = '_themisdb_views';
    const META_READS       = '_themisdb_read_completions';
    const META_PLAYS       = '_themisdb_podcast_plays';
    const META_COMPLETIONS = '_themisdb_podcast_completions';

    const VALID_EVENTS = [ 'view', 'read', 'podcast_play', 'podcast_complete' ];

    private static ?self $instance = null;

    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'rest_api_init',     [ $this, 'register_routes' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_tracker_js' ] );
    }

    // -------------------------------------------------------------------------
    // REST route
    // -------------------------------------------------------------------------

    public function register_routes(): void {
        register_rest_route(
            'themisdb/v1',
            '/track',
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'handle_track' ],
                'permission_callback' => '__return_true', // aggregated counters only; no auth required
                'args'                => [
                    'post_id' => [
                        'required'          => true,
                        'validate_callback' => fn( $v ) => is_numeric( $v ) && (int) $v > 0,
                        'sanitize_callback' => 'absint',
                    ],
                    'event' => [
                        'required'          => true,
                        'validate_callback' => fn( $v ) => in_array( $v, self::VALID_EVENTS, true ),
                        'sanitize_callback' => 'sanitize_key',
                    ],
                ],
            ]
        );
    }

    public function handle_track( WP_REST_Request $request ): WP_REST_Response {
        $post_id = $request->get_param( 'post_id' );
        $event   = $request->get_param( 'event' );

        if ( ! get_post( $post_id ) ) {
            return new WP_REST_Response( [ 'error' => 'invalid_post' ], 404 );
        }

        $meta_map = [
            'view'             => self::META_VIEWS,
            'read'             => self::META_READS,
            'podcast_play'     => self::META_PLAYS,
            'podcast_complete' => self::META_COMPLETIONS,
        ];

        $meta_key = $meta_map[ $event ] ?? null;
        if ( ! $meta_key ) {
            return new WP_REST_Response( [ 'error' => 'unknown_event' ], 400 );
        }

        $current = (int) get_post_meta( $post_id, $meta_key, true );
        update_post_meta( $post_id, $meta_key, $current + 1 );

        // Recalculate and persist engagement bonus after every event.
        $bonus = TDET_Score::calculate_bonus( $post_id );
        update_post_meta( $post_id, '_themisdb_engagement_score', $bonus );

        return new WP_REST_Response( [ 'ok' => true, 'bonus' => $bonus ], 200 );
    }

    // -------------------------------------------------------------------------
    // Frontend JS
    // -------------------------------------------------------------------------

    public function enqueue_tracker_js(): void {
        if ( ! is_singular() ) {
            return;
        }

        $post_id = get_the_ID();

        wp_enqueue_script(
            'tdet-tracker',
            TDET_URL . 'assets/js/tracker.js',
            [],
            TDET_VERSION,
            true
        );

        wp_localize_script(
            'tdet-tracker',
            'tdetConfig',
            [
                'restUrl'    => rest_url( 'themisdb/v1/track' ),
                'nonce'      => wp_create_nonce( 'wp_rest' ),
                'postId'     => (int) $post_id,
                'hasPodcast' => ! empty( get_post_meta( $post_id, 'audio_url', true ) ),
            ]
        );
    }
}
