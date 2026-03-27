<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB - Hybrid Database System                                   ║
╠═════════════════════════════════════════════════════════════════════╣
  File:            class-shortcodes.php                               ║
  Version:         0.0.2                                              ║
  Last Modified:   2026-03-09 04:08:18                                ║
  Author:          unknown                                            ║
╠═════════════════════════════════════════════════════════════════════╣
  Quality Metrics:                                                    ║
    • Maturity Level:  🟢 PRODUCTION-READY                             ║
    • Quality Score:   100.0/100                                      ║
    • Total Lines:     109                                            ║
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
 * Shortcodes Class
 * 
 * Handles shortcode registration and processing
 */

if (!defined('ABSPATH')) {
    exit;
}

class ThemisDB_Formula_Shortcodes {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Register shortcodes
        add_shortcode('themisdb_formula', array($this, 'render_formula_shortcode'));
        add_shortcode('formula', array($this, 'render_formula_shortcode'));
        add_shortcode('latex', array($this, 'render_formula_shortcode'));
        add_shortcode('math', array($this, 'render_formula_shortcode'));
    }

    /**
     * Build normalized shortcode data and pass it through a shared hook pipeline.
     */
    private function prepare_shortcode_context($shortcode_tag, $raw_atts, $default_atts, $payload = array()) {
        $atts = shortcode_atts($default_atts, (array) $raw_atts, $shortcode_tag);
        $atts = apply_filters($shortcode_tag . '_shortcode_atts', $atts, (array) $raw_atts);

        if (!is_array($payload)) {
            $payload = array();
        }

        return array($atts, $payload);
    }

    /**
     * Allow themes to fully override plugin HTML for a shortcode.
     */
    private function resolve_shortcode_html_override($shortcode_tag, $payload, $atts) {
        $html = apply_filters($shortcode_tag . '_shortcode_html', null, $payload, $atts);
        return (null !== $html) ? (string) $html : null;
    }

    /**
     * Final pass for post-processing plugin HTML.
     */
    private function finalize_shortcode_html($shortcode_tag, $html, $payload, $atts) {
        return apply_filters($shortcode_tag . '_shortcode_html_output', (string) $html, $payload, $atts);
    }
    
    /**
     * Render formula shortcode
     * 
     * @param array $atts Shortcode attributes
     * @param string $content Shortcode content
     * @return string Rendered HTML
     */
    public function render_formula_shortcode($atts, $content = null) {
        list($atts, $payload) = $this->prepare_shortcode_context('themisdb_formula', $atts, array(
            'display' => 'block', // 'block' or 'inline'
            'class' => '',
        ));
        
        if (empty($content)) {
            return '';
        }
        
        // Sanitize content
        $content = trim($content);
        
        // Determine display mode
        $is_block = ($atts['display'] === 'block');
        $delimiter = $is_block ? '$$' : '$';
        
        // Add delimiters if not already present
        if (!$this->starts_with($content, '$')) {
            $content = $delimiter . $content . $delimiter;
        }

        $payload = array_merge($payload, array(
            'display' => $atts['display'],
            'class' => $atts['class'],
            'formula' => $content,
            'is_block' => $is_block,
        ));
        $payload = apply_filters('themisdb_formula_shortcode_payload', $payload, $atts);
        $override_html = $this->resolve_shortcode_html_override('themisdb_formula', $payload, $atts);
        if (null !== $override_html) {
            return $override_html;
        }
        
        // Build CSS classes
        $classes = array('themisdb-formula');
        if ($is_block) {
            $classes[] = 'themisdb-formula-block';
        } else {
            $classes[] = 'themisdb-formula-inline';
        }
        
        if (!empty($atts['class'])) {
            $classes[] = sanitize_html_class($atts['class']);
        }
        
        $class_attr = implode(' ', $classes);
        
        // Return wrapped content
        if ($is_block) {
            $html = '<div class="' . esc_attr($class_attr) . '">' . esc_html($content) . '</div>';
        } else {
            $html = '<span class="' . esc_attr($class_attr) . '">' . esc_html($content) . '</span>';
        }

        return $this->finalize_shortcode_html('themisdb_formula', $html, $payload, $atts);
    }
    
    /**
     * Check if string starts with substring
     * 
     * @param string $haystack The string to search in
     * @param string $needle The substring to search for
     * @return bool
     */
    private function starts_with($haystack, $needle) {
        return strpos($haystack, $needle) === 0;
    }
}
