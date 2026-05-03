<?php
/**
 * AQM_Shortcode – rendert das Qualitäts-Widget.
 *
 * Shortcodes:
 *   [aqm_quality_meta]              – kanonisch (Plugin)
 *   [themisdb_v3_quality_meta]      – Alias (rückwärtskompatibel, nur wenn noch nicht belegt)
 *
 * Filter-Hooks:
 *   aqm_score_row_html   (string $html, string $key, array $data)  – einzelne Zeile anpassen
 *   aqm_widget_html      (string $html, int $post_id)              – gesamtes Widget anpassen
 *   aqm_widget_title     (string $title, int $post_id)             – Titeltext
 *   aqm_show_widget      (bool $show, int $post_id)                – Widget ganz unterdrücken
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AQM_Shortcode {

    public static function register(): void {
        add_shortcode( 'aqm_quality_meta', array( __CLASS__, 'render' ) );

        // Rückwärtskompatibilität: alten Shortcode nur registrieren, wenn er frei ist.
        if ( ! shortcode_exists( 'themisdb_v3_quality_meta' ) ) {
            add_shortcode( 'themisdb_v3_quality_meta', array( __CLASS__, 'render' ) );
        }
    }

    // -------------------------------------------------------------------------
    // Haupt-Rendering
    // -------------------------------------------------------------------------

    public static function render( array $atts = array() ): string {
        $post_id = get_the_ID();
        if ( ! $post_id ) {
            return '';
        }

        if ( ! (bool) apply_filters( 'aqm_show_widget', true, $post_id ) ) {
            return '';
        }

        $criteria  = AQM_Config::criteria();
        $ai_scores = AQM_Meta::get_ai_scores( $post_id );
        $uf_data   = AQM_Meta::get_uf_data( $post_id );
        $uf_count  = $uf_data['count'];

        // Widget nur anzeigen, wenn mindestens ein KI-Score vorhanden.
        $has_score = false;
        foreach ( $ai_scores as $s ) {
            if ( $s > 0 ) { $has_score = true; break; }
        }
        if ( ! $has_score ) {
            return '';
        }

        $rest_url = esc_url( rest_url( AQM_Config::rest_namespace() . '/feedback' ) );
        $nonce    = wp_create_nonce( 'aqm_feedback_' . $post_id );
        $summary  = esc_html( (string) get_post_meta( $post_id, AQM_Config::summary_meta(), true ) );

        $config = wp_json_encode( array(
            'postId'  => $post_id,
            'restUrl' => $rest_url,
            'nonce'   => $nonce,
        ) );

        $title = (string) apply_filters(
            'aqm_widget_title',
            __( 'KI-Bewertung & Leserfeedback', 'aqm' ),
            $post_id
        );

        // Zeilen für alle Kriterien rendern.
        $rows_html = '';
        foreach ( $criteria as $key => $meta ) {
            $ai_val  = $ai_scores[ $key ];
            $uf_val  = $uf_data['scores'][ $key ] ?? 0;
            $display = AQM_Config::combined_score( $ai_val, $uf_val, $uf_count );
            $label   = esc_html( $meta['label'] ?? $key );

            $row = sprintf(
                '<div class="tv3-qm-row aqm-row" data-criterion="%1$s">
  <span class="tv3-qm-label">%2$s<span class="tv3-qm-ai-hint"> · %3$d</span></span>
  <span class="tv3-qm-bars">
    <span class="tv3-qm-bar-wrap"><span class="tv3-qm-bar" style="width:%3$d%%"></span></span>
    <input type="range" class="tv3-qm-slider" min="0" max="100"
           value="%3$d" data-ai="%4$d" data-key="%1$s">
  </span>
  <span class="tv3-qm-val">%3$d</span>
</div>',
                esc_attr( $key ),
                $label,
                $display,
                $ai_val
            );

            $rows_html .= (string) apply_filters(
                'aqm_score_row_html',
                $row,
                $key,
                array(
                    'ai'      => $ai_val,
                    'uf'      => $uf_val,
                    'display' => $display,
                    'count'   => $uf_count,
                )
            );
        }

        // Summary-Block.
        $summary_html = $summary
            ? sprintf( '<p class="tv3-qm-summary aqm-summary">%s</p>', $summary )
            : '';

        $feedback_hint = sprintf(
            /* translators: %d = Anzahl Stimmen */
            _n(
                '<span class="tv3-qm-feedback-info aqm-feedback-info">%d Leserstimme einfließen.</span>',
                '<span class="tv3-qm-feedback-info aqm-feedback-info">%d Leserstimmen einfließen.</span>',
                $uf_count,
                'aqm'
            ),
            $uf_count
        );

        $html = sprintf(
            '<div class="tv3-quality-meta aqm-widget" data-aqm=\'%1$s\'>
  <h4 class="tv3-qm-title aqm-title">%2$s</h4>
  %3$s%4$s%5$s
  <div class="tv3-qm-reset-wrap aqm-reset-wrap">
    <button class="tv3-qm-reset aqm-reset" type="button">%6$s</button>
    <span class="tv3-qm-toast aqm-toast"></span>
  </div>
</div>',
            esc_attr( $config ),
            esc_html( $title ),
            $rows_html,
            $summary_html,
            $uf_count > 0 ? $feedback_hint : '',
            esc_html__( 'Bewertung zurücksetzen', 'aqm' )
        );

        return (string) apply_filters( 'aqm_widget_html', $html, $post_id );
    }

    // -------------------------------------------------------------------------
    // Hilfsfunktion für externe Nutzung im Theme
    // -------------------------------------------------------------------------

    /**
     * Gibt das Widget-HTML zurück, ohne Shortcode-Kontext.
     * Nutzung im Theme: echo AQM_Shortcode::get_widget_html();
     */
    public static function get_widget_html( int $post_id = 0 ): string {
        if ( ! $post_id ) {
            $post_id = get_the_ID();
        }
        if ( ! $post_id ) {
            return '';
        }
        global $post;
        $prev = $post;
        $post = get_post( $post_id );  // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        setup_postdata( $post );
        $html = self::render();
        $post = $prev;                  // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        wp_reset_postdata();
        return $html;
    }
}
