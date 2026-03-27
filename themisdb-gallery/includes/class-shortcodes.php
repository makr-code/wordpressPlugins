<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB - Hybrid Database System                                   ║
╠═════════════════════════════════════════════════════════════════════╣
  File:            class-shortcodes.php                               ║
  Version:         0.0.2                                              ║
  Last Modified:   2026-03-09 04:08:19                                ║
  Author:          unknown                                            ║
╠═════════════════════════════════════════════════════════════════════╣
  Quality Metrics:                                                    ║
    • Maturity Level:  🟢 PRODUCTION-READY                             ║
    • Quality Score:   100.0/100                                      ║
    • Total Lines:     216                                            ║
    • Open Issues:     TODOs: 0, Stubs: 0                             ║
╠═════════════════════════════════════════════════════════════════════╣
  Revision History:                                                   ║
    • 2a1fb0423  2026-03-03  Merge branch 'develop' into copilot/audit-src-module-docu... ║
    • 9d3ecaa0e  2026-02-28  Add ThemisDB Wiki Integration plugin with documentation i... ║
╠═════════════════════════════════════════════════════════════════════╣
  Status: ✅ Production Ready                                          ║
╚═════════════════════════════════════════════════════════════════════╝
 */


/**
 * Shortcodes Handler
 * 
 * Handles all plugin shortcodes
 */

if (!defined('ABSPATH')) {
    exit;
}

class ThemisDB_Gallery_Shortcodes {
    
    public function __construct() {
        add_shortcode('themisdb_gallery', array($this, 'gallery_shortcode'));
        add_shortcode('themisdb_image_search', array($this, 'image_search_shortcode'));
        add_shortcode('themisdb_image_attribution', array($this, 'attribution_shortcode'));
    }
    
    /**
     * Gallery shortcode
     * Displays a gallery of images from search or specific IDs
     * 
     * Usage:
     * [themisdb_gallery ids="1,2,3"]
     * [themisdb_gallery search="nature" provider="unsplash" columns="3"]
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function gallery_shortcode($atts) {
        $raw_atts = is_array($atts) ? $atts : array();

        $atts = shortcode_atts(array(
            'ids' => '',
            'search' => '',
            'provider' => get_option('themisdb_gallery_default_provider', 'unsplash'),
            'columns' => 3,
            'limit' => 12,
            'show_attribution' => 'yes',
        ), $raw_atts, 'themisdb_gallery');

        $atts = apply_filters('themisdb_gallery_shortcode_atts', $atts, $raw_atts);

        $items = array();
        $mode = 'empty';

        // Build a normalized payload that themes can reuse for custom markup.
        if (!empty($atts['ids'])) {
            $mode = 'ids';
            $ids = array_map('intval', explode(',', $atts['ids']));
            foreach ($ids as $attachment_id) {
                if ($attachment_id <= 0) {
                    continue;
                }

                $image_url = wp_get_attachment_image_url($attachment_id, 'medium');
                $image_full = wp_get_attachment_image_url($attachment_id, 'full');
                if (empty($image_url) || empty($image_full)) {
                    continue;
                }

                $title = get_the_title($attachment_id);
                $attribution = '';
                if ($atts['show_attribution'] === 'yes') {
                    $attribution = ThemisDB_Gallery_Media_Handler::get_attribution_html($attachment_id);
                }

                $items[] = array(
                    'type' => 'attachment',
                    'id' => $attachment_id,
                    'title' => (string) $title,
                    'thumb' => (string) $image_url,
                    'url' => (string) $image_full,
                    'attribution' => (string) $attribution,
                );
            }
        } elseif (!empty($atts['search'])) {
            $mode = 'search';
            $results = ThemisDB_Gallery_Image_API::search_images(
                $atts['search'],
                $atts['provider'],
                1,
                intval($atts['limit'])
            );

            if (!is_wp_error($results) && !empty($results)) {
                foreach ($results as $image) {
                    $attribution = '';
                    if ($atts['show_attribution'] === 'yes') {
                        $attribution = ThemisDB_Gallery_Media_Handler::generate_attribution_text($image);
                    }

                    $items[] = array(
                        'type' => 'search',
                        'title' => isset($image['title']) ? (string) $image['title'] : '',
                        'thumb' => isset($image['thumb']) ? (string) $image['thumb'] : '',
                        'url' => isset($image['url']) ? (string) $image['url'] : '',
                        'attribution' => (string) $attribution,
                    );
                }
            }
        }

        $payload = array(
            'mode' => $mode,
            'items' => $items,
        );

        $payload = apply_filters('themisdb_gallery_shortcode_payload', $payload, $atts);

        // Allow themes to fully own markup while plugin keeps data logic.
        $custom_html = apply_filters('themisdb_gallery_shortcode_html', null, $payload, $atts);
        if (null !== $custom_html) {
            return (string) $custom_html;
        }
        
        $html = '<div class="themisdb-gallery themisdb-gallery-columns-' . esc_attr($atts['columns']) . '">';

        if (!empty($payload['items']) && is_array($payload['items'])) {
            foreach ($payload['items'] as $item) {
                $html .= $this->render_payload_item($item, $atts['show_attribution']);
            }
        } elseif ($mode === 'search') {
            $html .= '<p>' . __('Keine Bilder gefunden.', 'themisdb-gallery') . '</p>';
        }
        
        $html .= '</div>';

        return apply_filters('themisdb_gallery_shortcode_html_output', $html, $payload, $atts);
    }

    private function render_payload_item($item, $show_attribution) {
        if (!is_array($item)) {
            return '';
        }

        $thumb = isset($item['thumb']) ? (string) $item['thumb'] : '';
        $url = isset($item['url']) ? (string) $item['url'] : '';
        $title = isset($item['title']) ? (string) $item['title'] : '';
        $attribution = isset($item['attribution']) ? (string) $item['attribution'] : '';

        if ('' === $thumb || '' === $url) {
            return '';
        }

        $html = '<div class="themisdb-gallery-item">';
        $html .= '<a href="' . esc_url($url) . '" data-lightbox="themisdb-gallery">';
        $html .= '<img src="' . esc_url($thumb) . '" alt="' . esc_attr($title) . '" />';
        $html .= '</a>';

        if ($show_attribution === 'yes' && !empty($attribution)) {
            $html .= '<div class="themisdb-gallery-attribution">' . $attribution . '</div>';
        }

        $html .= '</div>';

        return $html;
    }
    
    /**
     * Render gallery item for attachment
     * 
     * @param int $attachment_id Attachment ID
     * @param string $show_attribution Whether to show attribution
     * @return string HTML
     */
    private function render_gallery_item($attachment_id, $show_attribution) {
        $image_url = wp_get_attachment_image_url($attachment_id, 'medium');
        $image_full = wp_get_attachment_image_url($attachment_id, 'full');
        $title = get_the_title($attachment_id);
        
        $html = '<div class="themisdb-gallery-item">';
        $html .= '<a href="' . esc_url($image_full) . '" data-lightbox="themisdb-gallery">';
        $html .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($title) . '" />';
        $html .= '</a>';
        
        if ($show_attribution === 'yes') {
            $attribution = ThemisDB_Gallery_Media_Handler::get_attribution_html($attachment_id);
            if (!empty($attribution)) {
                $html .= '<div class="themisdb-gallery-attribution">' . $attribution . '</div>';
            }
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render search result item
     * 
     * @param array $image Image data
     * @param string $show_attribution Whether to show attribution
     * @return string HTML
     */
    private function render_search_result_item($image, $show_attribution) {
        $html = '<div class="themisdb-gallery-item">';
        $html .= '<a href="' . esc_url($image['url']) . '" data-lightbox="themisdb-gallery">';
        $html .= '<img src="' . esc_url($image['thumb']) . '" alt="' . esc_attr($image['title']) . '" />';
        $html .= '</a>';
        
        if ($show_attribution === 'yes') {
            $attribution = ThemisDB_Gallery_Media_Handler::generate_attribution_text($image);
            if (!empty($attribution)) {
                $html .= '<div class="themisdb-gallery-attribution">' . $attribution . '</div>';
            }
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Image search shortcode
     * Displays a search interface for finding images
     * 
     * Usage: [themisdb_image_search]
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function image_search_shortcode($atts) {
        $atts = shortcode_atts(array(
            'placeholder' => __('Suche nach Bildern...', 'themisdb-gallery'),
            'button_text' => __('Suchen', 'themisdb-gallery'),
        ), $atts, 'themisdb_image_search');
        
        ob_start();
        ?>
        <div class="themisdb-image-search-widget">
            <form class="themisdb-search-form" onsubmit="return false;">
                <div class="themisdb-search-controls">
                    <input type="text" 
                           class="themisdb-search-input" 
                           placeholder="<?php echo esc_attr($atts['placeholder']); ?>" 
                           id="themisdb-frontend-search-input" />
                    <select class="themisdb-provider-select" id="themisdb-frontend-provider">
                        <option value="all"><?php _e('Alle Anbieter', 'themisdb-gallery'); ?></option>
                        <option value="unsplash">Unsplash</option>
                        <option value="pexels">Pexels</option>
                        <option value="pixabay">Pixabay</option>
                    </select>
                    <button type="submit" class="themisdb-search-button"><?php echo esc_html($atts['button_text']); ?></button>
                </div>
            </form>
            <div class="themisdb-search-results" id="themisdb-frontend-results"></div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Attribution shortcode
     * Displays attribution for a specific attachment
     * 
     * Usage: [themisdb_image_attribution id="123"]
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function attribution_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts, 'themisdb_image_attribution');
        
        $attachment_id = intval($atts['id']);
        
        if (empty($attachment_id)) {
            return '';
        }
        
        $attribution = ThemisDB_Gallery_Media_Handler::get_attribution_html($attachment_id);
        
        if (empty($attribution)) {
            return '';
        }
        
        return '<div class="themisdb-image-attribution">' . $attribution . '</div>';
    }
}
