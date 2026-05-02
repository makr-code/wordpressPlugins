<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin post-list column showing engagement signals and bonus score.
 */
class TDET_Admin {

    private static ?self $instance = null;

    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        foreach ( [ 'post', 'page' ] as $pt ) {
            add_filter( "manage_{$pt}s_columns",       [ $this, 'add_column' ] );
            add_action( "manage_{$pt}s_custom_column", [ $this, 'render_column' ], 10, 2 );
        }

        // Support custom post types registered after plugins_loaded.
        add_action( 'registered_post_type', [ $this, 'hook_custom_post_type' ], 10, 2 );

        add_action( 'admin_head', [ $this, 'column_style' ] );
    }

    public function hook_custom_post_type( string $post_type ): void {
        add_filter( "manage_{$post_type}_posts_columns",       [ $this, 'add_column' ] );
        add_action( "manage_{$post_type}_posts_custom_column", [ $this, 'render_column' ], 10, 2 );
    }

    public function add_column( array $columns ): array {
        $columns['tdet_engagement'] = __( 'Engagement', 'themisdb-engagement-tracker' );
        return $columns;
    }

    public function render_column( string $column, int $post_id ): void {
        if ( 'tdet_engagement' !== $column ) {
            return;
        }

        $s = TDET_Score::get_signals( $post_id );

        $title = sprintf(
            /* translators: 1: views, 2: reads, 3: plays, 4: completions */
            esc_attr__( 'Views: %1$d · Reads: %2$d · Plays: %3$d · Completions: %4$d', 'themisdb-engagement-tracker' ),
            $s['views'],
            $s['reads'],
            $s['plays'],
            $s['completions']
        );

        printf(
            '<span class="tdet-cell" title="%s">'
            . '<span class="tdet-sig">👁&nbsp;%d</span>'
            . '<span class="tdet-sig">📖&nbsp;%d</span>'
            . '<span class="tdet-sig">🎙&nbsp;%d</span>'
            . '<span class="tdet-sig">✅&nbsp;%d</span>'
            . '<strong class="tdet-bonus">+%d</strong>'
            . '</span>',
            $title,
            $s['views'],
            $s['reads'],
            $s['plays'],
            $s['completions'],
            $s['bonus']
        );
    }

    public function column_style(): void {
        echo '<style>
            .tdet-cell { display:flex; flex-wrap:wrap; gap:4px; align-items:center; font-size:12px; }
            .tdet-sig  { white-space:nowrap; color:#646970; }
            .tdet-bonus{ margin-left:4px; color:#2271b1; }
            .column-tdet_engagement { width:140px; }
        </style>';
    }
}
