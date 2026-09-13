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

            // DEPRECATED: Context navigation is now integrated into the breadcrumbs list
            // This function is kept for backward compatibility but returns empty string
            return '';
        }
    }
}
