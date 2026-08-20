<?php
/**
 * Hero context navigation (breadcrumb + section anchors).
 *
 * @package ThemisDB_Pulse
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'ThemisDB_V3_Hero_Context_Nav' ) ) {
    /**
     * Context navigation helper for under-hero bars.
     */
    class ThemisDB_V3_Hero_Context_Nav {
        /**
         * Build local in-page anchor links.
         *
         * Server side this is only a seed. The final list is created in JS
         * from section headings of the current page.
         *
         * @param int $limit Maximum number of links.
         * @return array<int,array<string,mixed>>
         */
        public static function get_local_context_links( $limit = 8 ) {
            $limit       = max( 1, min( 12, (int) $limit ) );
            $raw_links   = apply_filters( 'themisdb_v3_hero_context_links', array(), $limit );
            $safe_links  = array();
            $seen_anchor = array();

            if ( ! is_array( $raw_links ) ) {
                return $safe_links;
            }

            foreach ( $raw_links as $link ) {
                if ( ! is_array( $link ) ) {
                    continue;
                }

                $label = isset( $link['label'] ) ? trim( wp_strip_all_tags( (string) $link['label'] ) ) : '';
                $url   = isset( $link['url'] ) ? trim( (string) $link['url'] ) : '';
                if ( '' === $label || '' === $url || '#' !== substr( $url, 0, 1 ) ) {
                    continue;
                }

                $anchor = sanitize_title( substr( $url, 1 ) );
                if ( '' === $anchor || isset( $seen_anchor[ $anchor ] ) ) {
                    continue;
                }

                $seen_anchor[ $anchor ] = true;
                $safe_links[] = array(
                    'label'      => $label,
                    'url'        => '#' . $anchor,
                    'is_current' => ! empty( $link['is_current'] ),
                );

                if ( count( $safe_links ) >= $limit ) {
                    break;
                }
            }

            return $safe_links;
        }

        /**
         * Render context nav section.
         *
         * @param array<string,mixed> $atts Shortcode attributes.
         * @return string
         */
        public static function render_shortcode( $atts = array() ) {
            static $did_render = false;
            if ( $did_render ) {
                return '';
            }
            $did_render = true;

            $atts = shortcode_atts(
                array(
                    'limit' => 8,
                ),
                $atts,
                'themisdb_v3_hero_context_nav'
            );

            $local_links = self::get_local_context_links( (int) $atts['limit'] );

            $text_domain = defined( 'THEMISDB_PULSE_TEXT_DOMAIN' ) ? THEMISDB_PULSE_TEXT_DOMAIN : 'themisdb-pulse';

            $html  = '<div class="tv3-hero-context-nav" role="navigation" aria-label="' . esc_attr__( 'Kontextnavigation', $text_domain ) . '">';
            $html .= '<div class="tv3-hero-context-nav-inner">';

            $html .= '<nav class="tv3-hero-local-nav" aria-label="' . esc_attr__( 'Abschnitte auf dieser Seite', $text_domain ) . '" data-tv3-anchor-nav="true" data-tv3-anchor-limit="' . (int) $atts['limit'] . '">';
            $html .= '<ul class="tv3-hero-local-nav-list" data-tv3-anchor-list="true">';
            foreach ( $local_links as $link ) {
                $label = isset( $link['label'] ) ? (string) $link['label'] : '';
                $url   = isset( $link['url'] ) ? (string) $link['url'] : '';
                if ( '' === $label || '' === $url || '#' !== substr( $url, 0, 1 ) ) {
                    continue;
                }

                $item_class = ! empty( $link['is_current'] ) ? ' class="is-current"' : '';
                $aria       = ! empty( $link['is_current'] ) ? ' aria-current="location"' : '';

                $html .= '<li' . $item_class . '><a href="' . esc_attr( $url ) . '"' . $aria . ' data-tv3-anchor-link="true">' . esc_html( $label ) . '</a></li>';
            }
            $html .= '</ul></nav>';

            $html .= '</div>';
            $html .= '<div class="tv3-hero-context-progress" aria-hidden="true">';
            $html .= '<span class="tv3-hero-context-progress__bar" data-tv3-reading-progress-bar></span>';
            $html .= '<span class="tv3-hero-context-progress__label" data-tv3-reading-progress-label></span>';
            $html .= '</div>';
            $html .= '</div>';

            return $html;
        }
    }
}
