<?php
/**
 * Plugin Name: ThemisDB Benchmark Visualizer
 * Plugin URI: https://github.com/makr-code/wordpressPlugins
 * Update URI: https://github.com/makr-code/wordpressPlugins
 * Description: Plugin for ThemisDB.
 * Version: 1.5.0
 * Author: makr-code
 * Author URI: https://github.com/makr-code/wordpressPlugins
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: themisdb-benchmark-visualizer
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB - Hybrid Database System                                   ║
╠═════════════════════════════════════════════════════════════════════╣
  File:            themisdb-benchmark-visualizer.php                  ║
  Version:         1.0.1                                              ║
  Last Modified:   2026-03-09 04:08:16                                ║
  Author:          makr-code                                            ║
╠═════════════════════════════════════════════════════════════════════╣
  Quality Metrics:                                                    ║
    • Maturity Level:  🟢 PRODUCTION-READY                             ║
    • Quality Score:   100.0/100                                      ║
    • Total Lines:     825                                            ║
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
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('THEMISDB_BV_VERSION', '1.5.0');
define('THEMISDB_BV_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('THEMISDB_BV_PLUGIN_URL', plugin_dir_url(__FILE__));
define('THEMISDB_BV_PLUGIN_FILE', __FILE__);
define('THEMISDB_BV_GITHUB_REPO', 'makr-code/wordpressPlugins');
define('THEMISDB_BV_GITHUB_PATH', 'themisdb-benchmark-visualizer');

// Load updater class
$themisdb_updater_local = THEMISDB_BV_PLUGIN_DIR . 'includes/class-themisdb-plugin-updater.php';
$themisdb_updater_shared = dirname(THEMISDB_BV_PLUGIN_DIR) . '/includes/class-themisdb-plugin-updater.php';

if (file_exists($themisdb_updater_local)) {
    require_once $themisdb_updater_local;
} elseif (file_exists($themisdb_updater_shared)) {
    require_once $themisdb_updater_shared;
}

// Initialize automatic updates
if (class_exists('ThemisDB_Plugin_Updater')) {
    new ThemisDB_Plugin_Updater(
        THEMISDB_BV_PLUGIN_FILE,
        'themisdb-benchmark-visualizer',
        THEMISDB_BV_VERSION
    );
}

/**
 * Plugin Name: ThemisDB Benchmark Visualizer
 * Main Plugin Class
 */
class ThemisDB_Benchmark_Visualizer {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Register activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Initialize plugin
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // Register shortcode
        add_shortcode('themisdb_benchmark_visualizer', array($this, 'render_visualizer'));
        
        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // Plugin action links
        add_filter('plugin_action_links_' . plugin_basename(THEMISDB_BV_PLUGIN_FILE), array($this, 'add_action_links'));
        
        // AJAX endpoints
        add_action('wp_ajax_themisdb_bv_get_data', array($this, 'ajax_get_data'));
        add_action('wp_ajax_nopriv_themisdb_bv_get_data', array($this, 'ajax_get_data'));
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Set default options
        $defaults = array(
            'data_source' => 'catalog',
            'catalog_path' => '',
            'github_data_url' => 'https://raw.githubusercontent.com/makr-code/wordpressPlugins/main/benchmarks/benchmark_results/',
            'default_comparison_dbs' => 'postgresql,mongodb',
            'chart_theme' => 'light',
            'auto_update_interval' => 86400, // 24 hours
            'default_metric' => 'latency',
            'default_category' => $this->get_default_category_key(),
            'default_chart_type' => 'bar',
            'default_top_n' => '30',
            'default_stats_mode' => 'toolbar',
            'require_comparable_tasks' => '1',
            'category_keyword_map' => wp_json_encode($this->default_category_keyword_map(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        );
        
        foreach ($defaults as $key => $value) {
            if (get_option('themisdb_bv_' . $key) === false) {
                add_option('themisdb_bv_' . $key, $value);
            }
        }
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up transients
        delete_transient('themisdb_bv_cached_data');
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('themisdb-benchmark-visualizer', false, dirname(plugin_basename(__FILE__)) . '/languages');

        $stored_default_category = get_option('themisdb_bv_default_category', null);
        if (null === $stored_default_category || 'all' === $stored_default_category || '' === $stored_default_category) {
            update_option('themisdb_bv_default_category', $this->get_default_category_key());
        }
    }
    
    /**
     * Enqueue assets
     */
    public function enqueue_assets() {
        global $post;

        $has_shortcode_in_content = is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'themisdb_benchmark_visualizer');
        $is_front_page_render = is_front_page() || is_page_template('front-page.php') || (is_page() && (int) get_option('page_on_front') === (int) get_queried_object_id());

        // Load assets when the shortcode is embedded in page content or rendered on the active front-page template.
        if (!$has_shortcode_in_content && !$is_front_page_render) {
            return;
        }

        $this->enqueue_frontend_assets();
    }

    private function enqueue_frontend_assets() {
        wp_enqueue_script(
            'themisdb-bv-chart',
            THEMISDB_BV_PLUGIN_URL . 'assets/vendor/chart.umd.min.js',
            array(),
            '4.4.0',
            true
        );

        $should_enqueue_frontend_style = apply_filters(
            'themisdb_benchmark_visualizer_enqueue_frontend_style',
            true
        );

        if ($should_enqueue_frontend_style) {
            wp_enqueue_style(
                'themisdb-bv-style',
                THEMISDB_BV_PLUGIN_URL . 'assets/css/benchmark-visualizer.css',
                array(),
                THEMISDB_BV_VERSION
            );
        }
        
        // Plugin JS
        wp_enqueue_script(
            'themisdb-bv-script',
            THEMISDB_BV_PLUGIN_URL . 'assets/js/benchmark-visualizer.js',
            array('themisdb-bv-chart'),
            THEMISDB_BV_VERSION,
            true
        );
        
    }

    private function get_frontend_config($atts) {
        return array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('themisdb_bv_nonce'),
            'plugin_url' => THEMISDB_BV_PLUGIN_URL,
            'settings' => array(
                'chart_theme' => get_option('themisdb_bv_chart_theme', 'light'),
                'default_metric' => $atts['metric'],
                'default_category' => $atts['category'],
                'default_chart_type' => $atts['chart_type'],
                'default_top_n' => $this->sanitize_top_n_option(get_option('themisdb_bv_default_top_n', '30')),
                'category_labels' => $this->get_category_display_labels(),
            ),
            'compare' => $atts['compare'],
            'demo' => get_option('themisdb_bv_data_source', 'catalog') === 'demo',
            'labels' => array(
                'loading' => __('Messdaten werden geladen ...', 'themisdb-benchmark-visualizer'),
                'empty' => __('Keine passenden Messdaten vorhanden.', 'themisdb-benchmark-visualizer'),
                'error' => __('Messdaten konnten nicht geladen werden. Bitte erneut versuchen.', 'themisdb-benchmark-visualizer'),
                'single' => __('Einzel-Engine-Messung, kein Datenbankvergleich.', 'themisdb-benchmark-visualizer'),
                'disjoint' => __('Keine gemeinsamen Vergleichsmessungen fuer die sichtbaren Engines.', 'themisdb-benchmark-visualizer'),
                'excluded' => __('Nicht vergleichbare Testaufgaben ausgeblendet: %s.', 'themisdb-benchmark-visualizer'),
                'comparison' => __('Vergleich mit uebereinstimmenden Protokollfeldern und erreichtem Recall-Ziel. Quellenangaben sind zu pruefen; keine externe Zertifizierung.', 'themisdb-benchmark-visualizer'),
                'unverified' => __('Referenz-/Altdaten: Vergleichsprotokoll fehlt oder ist unvollstaendig. Kein freigegebener Datenbankvergleich.', 'themisdb-benchmark-visualizer'),
                'protocol' => __('Vergleichsprotokoll', 'themisdb-benchmark-visualizer'),
                'demo' => __('DEMO: Synthetische Testdaten fuer erfundene Datenbanken. Keine realen Messungen und kein Leistungsnachweis.', 'themisdb-benchmark-visualizer'),
                'automatic' => __('Automatisch', 'themisdb-benchmark-visualizer'),
                'factor' => __('Leistungsfaktor zu', 'themisdb-benchmark-visualizer'),
                'factor_note' => __('Faktor: 1 = Referenz, groesser als 1 = besser. Nur bei passenden Vergleichsprotokollen oder Demo; fehlende Werte und Nullwerte ergeben keinen Faktor.', 'themisdb-benchmark-visualizer'),
                'log_note' => __('Logarithmische Skala: Nullwerte werden nur im Diagramm ausgelassen; Tabelle und CSV behalten die Originalwerte.', 'themisdb-benchmark-visualizer'),
                'radar_linear' => __('Radar unterstuetzt hier nur die lineare Skala; die logarithmische Auswahl gilt wieder beim Wechsel zu einem kartesischen Diagramm.', 'themisdb-benchmark-visualizer'),
                'chart_error' => __('Das Diagramm konnte nicht erzeugt werden. Die Messwerte stehen in der Tabelle bereit.', 'themisdb-benchmark-visualizer'),
                'metrics' => array('latency' => __('Latenz', 'themisdb-benchmark-visualizer'), 'throughput' => __('Durchsatz', 'themisdb-benchmark-visualizer'), 'memory' => __('Speicherbelegung', 'themisdb-benchmark-visualizer')),
                'all_engines' => __('Alle Engines', 'themisdb-benchmark-visualizer'),
                'tasks' => __('Testaufgaben', 'themisdb-benchmark-visualizer'),
                'task' => __('Testaufgabe', 'themisdb-benchmark-visualizer'),
                'variants' => __('Quellmessungen', 'themisdb-benchmark-visualizer'),
                'measured' => __('Gemessene Aufgaben', 'themisdb-benchmark-visualizer'),
                'mean' => __('Mittelwert der sichtbaren Aufgaben', 'themisdb-benchmark-visualizer'),
                'best' => __('Bestwert', 'themisdb-benchmark-visualizer'),
                'missing' => __('Nicht gemessen', 'themisdb-benchmark-visualizer'),
                'lower' => __('Kleinere Werte sind besser.', 'themisdb-benchmark-visualizer'),
                'higher' => __('Groessere Werte sind besser.', 'themisdb-benchmark-visualizer'),
            ),
        );
    }
    
    /**
     * Render benchmark visualizer
     */
    public function render_visualizer($atts) {
        $this->enqueue_frontend_assets();
		$raw_atts = (array) $atts;
		$normalize_bool = static function ($value, $default = false) {
			if (is_bool($value)) {
				return $value;
			}
			if (is_int($value)) {
				return (bool) $value;
			}
			if (is_string($value)) {
				$parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
				if (null !== $parsed) {
					return $parsed;
				}
			}
			return $default;
		};
		$default_category = $this->get_default_category_key();

		$atts = shortcode_atts(array(
            'category' => $default_category,
            'compare' => get_option('themisdb_bv_default_comparison_dbs', 'postgresql,mongodb'),
            'metric' => get_option('themisdb_bv_default_metric', 'latency'),
            'chart_type' => get_option('themisdb_bv_default_chart_type', 'bar'),
            'aspect_ratio' => get_option('themisdb_bv_default_aspect_ratio', '4:3'),
            'show_header' => 'false',
            'show_controls' => 'true',
            'show_chart' => 'true',
            'show_overlay' => 'false',
            'show_mermaid' => 'false',
            'show_nav' => 'true',
            'show_stats' => 'false',
            'stats_mode' => get_option('themisdb_bv_default_stats_mode', 'toolbar'),
            'show_table' => 'false',
        ), $raw_atts, 'themisdb_benchmark_visualizer');

		$atts = apply_filters('themisdb_benchmark_visualizer_shortcode_atts', $atts, $raw_atts);
        $atts['category'] = $this->normalize_category_key($atts['category']);
        $atts['metric'] = in_array($atts['metric'], array('latency', 'throughput', 'memory'), true) ? $atts['metric'] : 'latency';
        $atts['chart_type'] = $this->sanitize_chart_type_option($atts['chart_type']);

		$atts['show_header'] = $normalize_bool($atts['show_header'] ?? false, false);
		$atts['show_controls'] = $normalize_bool($atts['show_controls'] ?? false, false);
		$atts['show_chart'] = $normalize_bool($atts['show_chart'] ?? true, true);
		$atts['show_overlay'] = $normalize_bool($atts['show_overlay'] ?? false, false);
		$atts['show_mermaid'] = $normalize_bool($atts['show_mermaid'] ?? false, false);
		$atts['show_nav'] = $normalize_bool($atts['show_nav'] ?? false, false);
		$atts['show_stats'] = $normalize_bool($atts['show_stats'] ?? false, false);
		$atts['stats_mode'] = isset($atts['stats_mode']) ? strtolower(trim((string) $atts['stats_mode'])) : strtolower(trim((string) get_option('themisdb_bv_default_stats_mode', 'toolbar')));
		if (!in_array($atts['stats_mode'], array('toolbar', 'cards', 'hidden'), true)) {
		    $atts['stats_mode'] = 'toolbar';
		}
		$atts['show_table'] = $normalize_bool($atts['show_table'] ?? false, false);
		$atts['aspect_ratio'] = isset($atts['aspect_ratio']) ? sanitize_text_field($atts['aspect_ratio']) : '4:3';
		$chart_ratio_css = preg_replace('/\s*:\s*/', ' / ', $atts['aspect_ratio']);
		if (!preg_match('/^(\d+(?:\.\d+)?)\s*\/\s*(\d+(?:\.\d+)?)$/', $chart_ratio_css, $ratio_parts) || (float) $ratio_parts[1] <= 0 || (float) $ratio_parts[2] <= 0) {
			$chart_ratio_css = '4 / 3';
		}
        $atts['aspect_ratio'] = $chart_ratio_css;

		$payload = array(
			'category' => isset($atts['category']) ? sanitize_key($atts['category']) : $default_category,
			'compare' => isset($atts['compare']) ? sanitize_text_field($atts['compare']) : '',
			'metric' => isset($atts['metric']) ? sanitize_key($atts['metric']) : 'latency',
			'chart_type' => isset($atts['chart_type']) ? sanitize_key($atts['chart_type']) : 'bar',
			'aspect_ratio' => $atts['aspect_ratio'],
			'chart_theme' => get_option('themisdb_bv_chart_theme', 'light'),
			'show_header' => (bool) $atts['show_header'],
			'show_controls' => (bool) $atts['show_controls'],
			'show_chart' => (bool) $atts['show_chart'],
			'show_overlay' => (bool) $atts['show_overlay'],
			'show_mermaid' => (bool) $atts['show_mermaid'],
			'show_nav' => (bool) $atts['show_nav'],
			'show_stats' => (bool) $atts['show_stats'],
			'stats_mode' => $atts['stats_mode'],
			'show_table' => (bool) $atts['show_table'],
		);

		$payload = apply_filters('themisdb_benchmark_visualizer_shortcode_payload', $payload, $atts);
		$custom_html = apply_filters('themisdb_benchmark_visualizer_shortcode_html', null, $payload, $atts);
		if (null !== $custom_html) {
			return (string) $custom_html;
		}

        ob_start();
        include THEMISDB_BV_PLUGIN_DIR . 'templates/visualizer.php';
        $html = ob_get_clean();
        // Block templates can apply wpautop after shortcode rendering.
        $html = preg_replace('/>\s+</', '><', trim($html));

        return apply_filters('themisdb_benchmark_visualizer_shortcode_html_output', $html, $payload, $atts);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('Benchmark Visualizer Settings', 'themisdb-benchmark-visualizer'),
            __('Benchmark Visualizer', 'themisdb-benchmark-visualizer'),
            'manage_options',
            'themisdb-bv-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('themisdb_bv_settings', 'themisdb_bv_data_source');
        register_setting('themisdb_bv_settings', 'themisdb_bv_catalog_path');
        register_setting('themisdb_bv_settings', 'themisdb_bv_github_data_url');
        register_setting('themisdb_bv_settings', 'themisdb_bv_default_comparison_dbs');
        register_setting('themisdb_bv_settings', 'themisdb_bv_chart_theme');
        register_setting('themisdb_bv_settings', 'themisdb_bv_auto_update_interval');
        register_setting('themisdb_bv_settings', 'themisdb_bv_default_metric');
        register_setting(
            'themisdb_bv_settings',
            'themisdb_bv_default_category',
            array('sanitize_callback' => 'sanitize_key')
        );
        register_setting(
            'themisdb_bv_settings',
            'themisdb_bv_default_chart_type',
            array('sanitize_callback' => array($this, 'sanitize_chart_type_option'))
        );
        register_setting(
            'themisdb_bv_settings',
            'themisdb_bv_default_top_n',
            array('sanitize_callback' => array($this, 'sanitize_top_n_option'))
        );
        register_setting(
            'themisdb_bv_settings',
            'themisdb_bv_default_stats_mode',
            array('sanitize_callback' => array($this, 'sanitize_stats_mode_option'))
        );
        register_setting(
            'themisdb_bv_settings',
            'themisdb_bv_require_comparable_tasks',
            array('sanitize_callback' => array($this, 'sanitize_bool_option'))
        );
        register_setting(
            'themisdb_bv_settings',
            'themisdb_bv_category_keyword_map',
            array('sanitize_callback' => array($this, 'sanitize_category_keyword_map_option'))
        );
    }

    public function sanitize_bool_option($value) {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        $text = strtolower(trim((string) $value));
        return in_array($text, array('1', 'true', 'yes', 'on'), true) ? '1' : '0';
    }

    public function sanitize_stats_mode_option($value) {
        $normalized = strtolower(trim((string) $value));
        return in_array($normalized, array('toolbar', 'cards', 'hidden'), true) ? $normalized : 'toolbar';
    }

    public function sanitize_category_keyword_map_option($value) {
        $default = wp_json_encode($this->default_category_keyword_map(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $raw = trim((string) $value);
        if ($raw === '') {
            return $default;
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            add_settings_error(
                'themisdb_bv_settings',
                'themisdb_bv_category_keyword_map_invalid',
                __('Category Keyword Mapping must be valid JSON.', 'themisdb-benchmark-visualizer'),
                'error'
            );
            return $default;
        }

        $normalized = array();
        foreach ($decoded as $category => $keywords) {
            $category_key = sanitize_key((string) $category);
            if ($category_key === '' || !is_array($keywords)) {
                continue;
            }
            $items = array();
            foreach ($keywords as $keyword) {
                $token = strtolower(trim((string) $keyword));
                if ($token !== '') {
                    $items[] = $token;
                }
            }
            if (!empty($items)) {
                $normalized[$category_key] = array_values(array_unique($items));
            }
        }
        if (empty($normalized)) {
            add_settings_error(
                'themisdb_bv_settings',
                'themisdb_bv_category_keyword_map_empty',
                __('Category Keyword Mapping JSON must contain at least one non-empty category array.', 'themisdb-benchmark-visualizer'),
                'error'
            );
            return $default;
        }
        return wp_json_encode($normalized, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function get_chart_type_labels() {
        return array(
            'bar' => __('Balken (automatisch)', 'themisdb-benchmark-visualizer'),
            'bar-horizontal' => __('Balken horizontal', 'themisdb-benchmark-visualizer'),
            'bar-vertical' => __('Saeulen vertikal', 'themisdb-benchmark-visualizer'),
            'bar-isometric' => __('Saeulen isometrisch (2.5D)', 'themisdb-benchmark-visualizer'),
            'line' => __('Linie', 'themisdb-benchmark-visualizer'),
            'line-dot' => __('Linie mit Punkten', 'themisdb-benchmark-visualizer'),
            'stepped' => __('Stufenlinie', 'themisdb-benchmark-visualizer'),
            'area' => __('Flaeche', 'themisdb-benchmark-visualizer'),
            'radar' => __('Radar', 'themisdb-benchmark-visualizer'),
        );
    }

    public function sanitize_chart_type_option($value) {
        $allowed = array_keys($this->get_chart_type_labels());
        $candidate = sanitize_key((string) $value);
        return in_array($candidate, $allowed, true) ? $candidate : 'bar';
    }

    public function sanitize_top_n_option($value) {
        $text = strtolower(trim((string) $value));
        if ($text === 'all') {
            return 'all';
        }
        $numeric = (int) $text;
        if ($numeric <= 0) {
            return '30';
        }
        return (string) min(200, $numeric);
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        include THEMISDB_BV_PLUGIN_DIR . 'templates/admin-settings.php';
    }
    
    /**
     * Add plugin action links
     */
    public function add_action_links($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=themisdb-bv-settings') . '">' . __('Settings', 'themisdb-benchmark-visualizer') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * AJAX handler to get benchmark data
     */
    public function ajax_get_data() {
        check_ajax_referer('themisdb_bv_nonce', 'nonce');
        
        $category = isset($_POST['category']) && is_string($_POST['category']) ? $this->normalize_category_key(wp_unslash($_POST['category'])) : 'all';
        $metric = isset($_POST['metric']) && is_string($_POST['metric']) ? sanitize_key($_POST['metric']) : 'latency';
        if (!in_array($metric, array('latency', 'throughput', 'memory'), true) || ($category !== 'all' && !array_key_exists($category, $this->get_category_display_labels()))) {
            wp_send_json_error(array('message' => __('Ungueltige Benchmark-Filter.', 'themisdb-benchmark-visualizer')), 400);
        }
        
        $source = get_option('themisdb_bv_data_source', 'catalog');
        $directory = $source === 'local' ? $this->find_benchmark_directory() : false;
        $files = $directory ? $this->get_benchmark_files($directory, $category) : array_filter(array($this->find_catalog_path()));
        $versions = array();
        foreach ($files as $file) {
            if (is_file($file)) {
                $versions[] = array($file, filemtime($file), filesize($file));
            }
        }
        $cache_key = 'themisdb_bv_data_' . md5(wp_json_encode(array(THEMISDB_BV_VERSION, $category, $metric, $source, get_option('themisdb_bv_catalog_path', ''), $this->get_category_keyword_map(), $this->require_comparable_tasks(), $versions)));
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            wp_send_json_success($cached_data);
            return;
        }
        
        // Load data
        $data = $this->load_benchmark_data($category, $metric);
        if (is_wp_error($data)) {
            wp_send_json_error(array('message' => $data->get_error_message()), 503);
        }
        
        // Cache data
        $cache_duration = max(60, min(DAY_IN_SECONDS * 7, (int) get_option('themisdb_bv_auto_update_interval', 86400)));
        set_transient($cache_key, $data, $cache_duration);
        
        wp_send_json_success($data);
    }
    
    /**
     * Load benchmark data
     */
    private function load_benchmark_data($category = 'all', $metric = 'latency') {
        $data_source = get_option('themisdb_bv_data_source', 'catalog');
        $normalized_category = $this->normalize_category_key($category);

        if ($data_source === 'catalog') {
            return $this->load_catalog_benchmark_data($normalized_category, $metric);
        }
        if ($data_source === 'local') {
            return $this->load_local_benchmark_data($normalized_category, $metric);
        }
        if ($data_source === 'github') {
            return $this->load_github_benchmark_data($normalized_category, $metric);
        }
        if ($data_source === 'demo') {
            require_once THEMISDB_BV_PLUGIN_DIR . 'includes/demo-data.php';
            $data = $this->parse_catalog_records(themisdb_bv_demo_records(), $metric, $normalized_category, true);
            $data['metric'] = $metric;
            $data['category'] = $normalized_category;
            $data['source'] = array('type' => 'demo', 'file' => 'synthetic-demo-v1');
            return $data;
        }
        return new WP_Error('invalid_source', __('Ungueltige Benchmark-Datenquelle. Plugin-Einstellungen pruefen.', 'themisdb-benchmark-visualizer'));
    }

    private function normalize_category_key($category) {
        $raw = sanitize_key((string) $category);
        $aliases = array(
            'aql_query' => 'advanced',
            'query' => 'advanced',
            'transactions' => 'transaction',
            'graphs' => 'graph_traversal',
            'vectors' => 'vector_search',
        );
        return isset($aliases[$raw]) ? $aliases[$raw] : ($raw ?: 'all');
    }

    private function find_catalog_path() {
        $configured_path = trim((string) get_option('themisdb_bv_catalog_path', ''));
        if ($configured_path !== '') {
            if (is_file($configured_path)) {
                return $configured_path;
            }
            return false;
        }

        $possible_paths = array(
            THEMISDB_BV_PLUGIN_DIR . '../source-links/benchmarks/benchmark_results/benchmark_catalog.json',
            THEMISDB_BV_PLUGIN_DIR . '../../source-links/benchmarks/benchmark_results/benchmark_catalog.json',
            THEMISDB_BV_PLUGIN_DIR . '../../../source-links/benchmarks/benchmark_results/benchmark_catalog.json',
        );

        if (isset($_SERVER['DOCUMENT_ROOT'])) {
            $doc_root = untrailingslashit($_SERVER['DOCUMENT_ROOT']);
            $possible_paths[] = $doc_root . '/source-links/benchmarks/benchmark_results/benchmark_catalog.json';
            $possible_paths[] = dirname($doc_root) . '/source-links/benchmarks/benchmark_results/benchmark_catalog.json';
        }

        $env_path = getenv('THEMISDB_BENCHMARK_CATALOG');
        if ($env_path) {
            $possible_paths[] = $env_path;
        }

        foreach ($possible_paths as $path) {
            if (is_string($path) && is_file($path)) {
                return $path;
            }
        }
        return false;
    }

    private function load_catalog_benchmark_data($category, $metric) {
        $catalog_path = $this->find_catalog_path();
        if (!$catalog_path || !is_readable($catalog_path)) {
            return new WP_Error('missing_catalog', __('Benchmark-Katalog nicht gefunden oder nicht lesbar. Datenquelle in den Plugin-Einstellungen pruefen.', 'themisdb-benchmark-visualizer'));
        }

        $payload = json_decode((string) file_get_contents($catalog_path), true);
        if (!is_array($payload) || !isset($payload['records']) || !is_array($payload['records'])) {
            return new WP_Error('invalid_catalog', __('Benchmark-Katalog enthaelt keine gueltige records-Liste.', 'themisdb-benchmark-visualizer'));
        }

        $parsed_data = $this->parse_catalog_records($payload['records'], $metric, $category);
        if (is_wp_error($parsed_data)) {
            return $parsed_data;
        }
        $description = array('title' => $category, 'unit' => $metric === 'memory' ? 'MiB' : ($metric === 'latency' ? 'ms' : 'ops/s'));
        return array(
            'labels' => $parsed_data['labels'],
            'datasets' => $parsed_data['datasets'],
            'metric' => $metric,
            'category' => $category,
            'summary' => $parsed_data['summary'],
            'description' => $description,
            'source' => array('type' => 'catalog', 'file' => basename($catalog_path), 'modified' => gmdate('c', filemtime($catalog_path))),
        );
    }

    private function is_higher_better($metric) {
        return sanitize_key((string) $metric) === 'throughput';
    }

    private function default_category_keyword_map() {
        return array(
            'vector_search' => array('vector', 'embedding', 'hnsw', 'simd_distance', 'knn'),
            'graph_traversal' => array('graph', 'pagerank', 'traversal', 'bfs', 'dfs'),
            'transaction' => array('tpcc', 'transaction', 'ycsb', 'mvcc', 'lock'),
            'compression' => array('compression', 'zstd', 'gorilla', 'codec'),
            'advanced' => array('aql', 'changefeed', 'async', 'batch', 'querymix', 'query'),
            'content' => array('content', 'blob', 'branch', 'document', 'index'),
            'gpu' => array('gpu', 'cuda', 'rocm', 'vulkan'),
        );
    }

    private function get_category_keyword_map() {
        $default_map = $this->default_category_keyword_map();
        $raw = trim((string) get_option('themisdb_bv_category_keyword_map', ''));
        if ($raw === '') {
            return $default_map;
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return $default_map;
        }
        $normalized = array();
        foreach ($decoded as $category => $keywords) {
            $category_key = sanitize_key((string) $category);
            if ($category_key === '') {
                continue;
            }
            if (!is_array($keywords)) {
                continue;
            }
            $items = array();
            foreach ($keywords as $keyword) {
                $value = trim((string) $keyword);
                if ($value !== '') {
                    $items[] = strtolower($value);
                }
            }
            if (!empty($items)) {
                $normalized[$category_key] = array_values(array_unique($items));
            }
        }
        return !empty($normalized) ? $normalized : $default_map;
    }

    private function require_comparable_tasks() {
        $value = get_option('themisdb_bv_require_comparable_tasks', '1');
        if (is_bool($value)) {
            return $value;
        }
        return in_array(strtolower((string) $value), array('1', 'true', 'yes', 'on'), true);
    }

    private function get_default_category_key() {
        $configured = sanitize_key((string) get_option('themisdb_bv_default_category', ''));
        if ($configured !== '' && $configured !== 'all') {
            return $configured;
        }

        foreach (array_keys($this->get_category_keyword_map()) as $category_key) {
            if ($category_key !== 'all') {
                return $category_key;
            }
        }

        return 'vector_search';
    }

    private function get_category_display_labels() {
        $labels = array();
        foreach ($this->get_category_keyword_map() as $key => $keywords) {
            $text = ucwords(str_replace(array('_', '-'), ' ', (string) $key));
            $labels[$key] = $text;
        }
        return $labels;
    }

    private function normalize_benchmark_label($name) {
        $label = preg_replace('/^BM_/', '', trim((string) $name));
        $label = preg_replace('/\s+/', ' ', str_replace('_', ' ', $label));
        return trim((string) $label);
    }

    private function infer_category_from_text($text) {
        $value = strtolower((string) $text);
        $mapping = $this->get_category_keyword_map();

        foreach ($mapping as $category => $needles) {
            foreach ($needles as $needle) {
                if (strpos($value, $needle) !== false) {
                    return $category;
                }
            }
        }
        return 'all';
    }

    private function infer_workload_for_row($row) {
        $raw_workload = isset($row['workload']) && is_string($row['workload']) ? $this->normalize_category_key($row['workload']) : '';
        if ($raw_workload !== '' && $raw_workload !== 'all') {
            return $raw_workload;
        }

        $name = (string) ($row['benchmark_name'] ?? '');
        $source = isset($row['source_file']) && is_string($row['source_file']) ? $row['source_file'] : '';
        $notes = isset($row['notes']) && is_string($row['notes']) ? $row['notes'] : '';
        $inferred = $this->infer_category_from_text($name . ' ' . $source . ' ' . $notes);
        return sanitize_key((string) $inferred ?: 'all');
    }

    private function publication_errors($publication, $metric) {
        if (!is_array($publication)) {
            return array('publication metadata missing');
        }
        static $contract = null;
        if ($contract === null) {
            $contract = json_decode(file_get_contents(THEMISDB_BV_PLUGIN_DIR . 'includes/benchmark-publication-contract.json'), true);
        }
        $errors = array();
        foreach ($contract['fields'] as $name => $rule) {
            $value = $publication[$name] ?? null;
            $valid = $rule['type'] === 'string' ? is_string($value) && trim($value) !== '' :
                ($rule['type'] === 'integer' ? is_int($value) : (is_int($value) || is_float($value)) && is_finite((float) $value));
            if (!$valid) {
                $errors[] = $name;
                continue;
            }
            if ((isset($rule['enum']) && !in_array($value, $rule['enum'], true)) ||
                (isset($rule['minimum']) && $value < $rule['minimum']) ||
                (isset($rule['maximum']) && $value > $rule['maximum']) ||
                (isset($rule['pattern']) && !preg_match('~' . $rule['pattern'] . '~', $value))) {
                $errors[] = $name;
            }
        }
        if ($errors) {
            return $errors;
        }
        $statistics = array('latency' => array('mean', 'p50', 'p95', 'p99'), 'throughput' => array('qps'), 'memory' => array('peak'));
        if (!in_array($publication['statistic'], $statistics[$metric] ?? array(), true)) {
            $errors[] = 'statistic does not match metric';
        }
        if ($publication['recall_achieved'] < $publication['recall_target']) {
            $errors[] = 'recall below target';
        }
        if ($publication['top_k'] > $publication['vectors']) {
            $errors[] = 'top_k exceeds vector count';
        }
        return $errors;
    }

    private function parse_catalog_records($records, $metric, $category, $demo = false) {
        $metric = sanitize_key((string) $metric);
        $higher_is_better = $this->is_higher_better($metric);
        $label_values = array();
        $label_sources = array();
        $engines = array();
        $task_engine_counts = array();
        $task_protocols = array();
        $task_verified = array();
        $unverified_records = 0;
        $contract = json_decode(file_get_contents(THEMISDB_BV_PLUGIN_DIR . 'includes/benchmark-publication-contract.json'), true);

        foreach ($records as $row) {
            if (!is_array($row) || !isset($row['benchmark_name'], $row['engine'], $row['metric_name']) ||
                !is_string($row['benchmark_name']) || !is_string($row['engine']) || !is_string($row['metric_name']) ||
                (isset($row['source_file']) && !is_string($row['source_file'])) ||
                !empty($row['error_occurred']) || ($row['run_type'] ?? '') === 'aggregate') {
                continue;
            }

            $row_metric = sanitize_key((string) ($row['metric_name'] ?? ''));
            if ($row_metric !== $metric) {
                continue;
            }

            $row_category = $this->infer_workload_for_row($row);
            if ($category !== 'all' && $row_category !== $category) {
                continue;
            }

            $label = $this->normalize_benchmark_label((string) ($row['benchmark_name'] ?? 'benchmark'));
            $engine = trim($row['engine']);
            $value = $this->normalize_metric_value($row['metric_value'] ?? null, $row['metric_unit'] ?? '', $metric);
            if ($label === '' || $value === null || $engine === '') {
                continue;
            }
            $context = array();
            foreach (array('dataset_size', 'environment', 'threads') as $key) {
                if (isset($row[$key]) && is_scalar($row[$key]) && (string) $row[$key] !== '' && (string) $row[$key] !== 'n/a' && (string) $row[$key] !== '0') {
                    $context[] = $key . '=' . (string) $row[$key];
                }
            }
            if ($context) {
                $label .= ' [' . implode(', ', $context) . ']';
            }
            $publication = $row['publication'] ?? null;
            $issues = $this->publication_errors($publication, $metric);
            $verified = !$issues && $row_category === 'vector_search';
            if ($verified) {
                $identity = array();
                foreach ($contract['comparison_fields'] as $field) {
                    $identity[$field] = $publication[$field];
                }
                // A run identifies one measurement block, not an average of percentiles.
                $identity['run_id'] = $publication['run_id'];
                $identity['recall_target'] = (float) $identity['recall_target'];
                $label .= ' [' . $publication['statistic'] . '; protocol=' . hash('sha256', wp_json_encode($identity)) . ']';
            } elseif (!$demo) {
                ++$unverified_records;
                $label .= ' [reference: ' . $engine . '; ' . basename(str_replace('\\', '/', (string) ($row['source_file'] ?? 'unknown'))) . ']';
                if ($publication !== null) {
                    $label .= ' [' . hash('sha256', wp_json_encode($publication)) . ']';
                }
            }
            if (isset($label_values[$label][$engine]) && $verified) {
                return new WP_Error('duplicate_measurement', __('Doppelter Messwert fuer denselben Protokoll-Lauf und dieselbe Engine. Perzentile und Laufstatistiken duerfen nicht gemittelt werden.', 'themisdb-benchmark-visualizer'));
            }
            $task_verified[$label] = $verified;
            $task_protocols[$label][$engine] = $verified ? $publication : array(
                'issues' => $issues ?: array('workload is not vector_search'),
                'publication' => $publication,
            );

            if (!isset($label_values[$label])) {
                $label_values[$label] = array();
                $label_sources[$label] = array();
            }
            if (!isset($label_values[$label][$engine])) {
                $label_values[$label][$engine] = array();
            }
            $label_values[$label][$engine][] = $value;
            $task_engine_counts[$label] = count($label_values[$label]);
            $source_name = trim((string) ($row['benchmark_name'] ?? ''));
            if (isset($row['source_file']) && is_string($row['source_file']) && $row['source_file'] !== '') {
                $source_name .= ' | ' . basename(str_replace('\\', '/', $row['source_file']));
            }
            if ($source_name !== '' && !in_array($source_name, $label_sources[$label], true)) {
                $label_sources[$label][] = $source_name;
            }
            if (!in_array($engine, $engines, true)) {
                $engines[] = $engine;
            }
        }

        if (empty($label_values) || empty($engines)) {
            return array('labels' => array(), 'datasets' => array(), 'summary' => array());
        }

        $comparable_labels = array();
        foreach ($label_values as $label => $engine_map) {
            if (count($engine_map) >= 2 && $task_verified[$label]) {
                $comparable_labels[] = $label;
            }
        }
        $enforce_comparable = $this->require_comparable_tasks();
        $eligible_labels = (!$demo && $enforce_comparable && !empty($comparable_labels)) ? $comparable_labels : array_keys($label_values);
        $excluded_non_comparable = max(0, count($label_values) - count($eligible_labels));

        $label_scores = array();
        foreach ($label_values as $label => $engine_map) {
            if (!in_array($label, $eligible_labels, true)) {
                continue;
            }
            if (isset($engine_map['ThemisDB']) && !empty($engine_map['ThemisDB'])) {
                $values = $engine_map['ThemisDB'];
                $label_scores[$label] = array_sum($values) / count($values);
                continue;
            }
            $fallback = array();
            foreach ($engine_map as $values) {
                foreach ($values as $value) {
                    $fallback[] = $value;
                }
            }
            if (!empty($fallback)) {
                $label_scores[$label] = array_sum($fallback) / count($fallback);
            }
        }

        if (empty($label_scores)) {
            return array('labels' => array(), 'datasets' => array(), 'summary' => array());
        }

        $labels = array_keys($label_scores);
        usort($labels, function ($left, $right) use ($label_scores, $higher_is_better) {
            $lv = (float) ($label_scores[$left] ?? 0.0);
            $rv = (float) ($label_scores[$right] ?? 0.0);
            if ($lv === $rv) {
                return strcmp((string) $left, (string) $right);
            }
            if ($higher_is_better) {
                return ($rv <=> $lv);
            }
            return ($lv <=> $rv);
        });

        $datasets = array();
        foreach ($engines as $engine) {
            $data = array();
            foreach ($labels as $label) {
                $points = $label_values[$label][$engine] ?? array();
                $data[] = empty($points) ? null : array_sum($points) / count($points);
            }
            $datasets[] = array(
                'label' => $engine,
                'data' => $data,
                'backgroundColor' => $engine === 'ThemisDB' ? 'rgba(46, 164, 79, 0.8)' : 'rgba(80, 120, 200, 0.55)',
                'borderColor' => $engine === 'ThemisDB' ? 'rgba(46, 164, 79, 1)' : 'rgba(80, 120, 200, 0.95)',
                'borderWidth' => 2,
                'pointRadius' => 4,
                'pointHoverRadius' => 6,
            );
        }

        $summary_values = array_values($label_scores);
        $min_value = min($summary_values);
        $max_value = max($summary_values);
        $avg_value = array_sum($summary_values) / count($summary_values);
        $summary = array(
            'total_benchmarks' => count($label_scores),
            'total_tasks_raw' => count($label_values),
            'task_count' => count($label_scores),
            'comparable_task_count' => count($comparable_labels),
            'excluded_non_comparable_tasks' => $excluded_non_comparable,
            'displayed_benchmarks' => count($labels),
            'avg_time' => $avg_value,
            'fastest' => $min_value,
            'slowest' => $max_value,
            'min_value' => $min_value,
            'max_value' => $max_value,
            'best_value' => $higher_is_better ? $max_value : $min_value,
            'worst_value' => $higher_is_better ? $min_value : $max_value,
            'files_parsed' => 0,
            'metric' => $metric,
            'higher_is_better' => $higher_is_better,
            'task_sources' => $label_sources,
            'task_engine_counts' => $task_engine_counts,
            'task_comparison_verified' => $task_verified,
            'task_protocols' => $task_protocols,
            'unverified_records' => $unverified_records,
            'synthetic' => $demo,
            'comparison_mode' => $enforce_comparable ? (!empty($comparable_labels) ? 'cross_engine' : 'single_engine_fallback') : 'all_tasks',
            'enforce_comparable_tasks' => $enforce_comparable,
        );

        return array(
            'labels' => array_map('strval', $labels),
            'datasets' => $datasets,
            'summary' => $summary,
        );
    }
    
    
    /**
     * Load local benchmark data
     */
    private function load_local_benchmark_data($category, $metric) {
        // Find the benchmark results directory
        $benchmark_dir = $this->find_benchmark_directory();
        
        if (!$benchmark_dir || !is_dir($benchmark_dir)) {
            return new WP_Error('missing_benchmarks', __('Kein lokales Benchmark-Verzeichnis gefunden.', 'themisdb-benchmark-visualizer'));
        }
        
        // Get available benchmark files
        $benchmark_files = $this->get_benchmark_files($benchmark_dir, $category);
        
        if (empty($benchmark_files)) {
            return new WP_Error('missing_benchmarks', __('Keine Benchmark-Dateien fuer diese Kategorie gefunden.', 'themisdb-benchmark-visualizer'));
        }
        
        // Parse benchmark data
        $parsed_data = $this->parse_benchmark_files($benchmark_files, $metric);
        if (is_wp_error($parsed_data)) {
            return $parsed_data;
        }
        
        $description = array('title' => $category, 'unit' => $metric === 'memory' ? 'MiB' : ($metric === 'latency' ? 'ms' : 'ops/s'));
        
        return array(
            'labels' => $parsed_data['labels'],
            'datasets' => $parsed_data['datasets'],
            'metric' => $metric,
            'category' => $category,
            'summary' => $parsed_data['summary'],
            'description' => $description,
            'source' => array('type' => 'local', 'file' => basename($benchmark_dir)),
        );
    }
    
    /**
     * Find benchmark directory
     */
    private function find_benchmark_directory() {
        // Try multiple possible locations
        $possible_paths = array();
        $possible_paths[] = THEMISDB_BV_PLUGIN_DIR . '../source-links/benchmarks/benchmark_results';
        
        // Relative to plugin directory (common WordPress installations)
        $possible_paths[] = THEMISDB_BV_PLUGIN_DIR . '../../benchmarks/benchmark_results';
        $possible_paths[] = THEMISDB_BV_PLUGIN_DIR . '../../../benchmarks/benchmark_results';
        
        // Try to find from document root
        if (isset($_SERVER['DOCUMENT_ROOT'])) {
            $doc_root = $_SERVER['DOCUMENT_ROOT'];
            $possible_paths[] = $doc_root . '/benchmarks/benchmark_results';
            $possible_paths[] = dirname($doc_root) . '/benchmarks/benchmark_results';
        }
        
        // Check if environment variable is set
        if (getenv('THEMISDB_BENCHMARK_PATH')) {
            $possible_paths[] = getenv('THEMISDB_BENCHMARK_PATH');
        }
        
        // Try each path and find the latest dated directory
        foreach ($possible_paths as $base_path) {
            if (!is_dir($base_path)) {
                continue;
            }
            if (glob($base_path . '/bench_*.json')) {
                return $base_path;
            }
            
            // Find latest dated directory (format: YYYYMMDD_HHMMSS)
            $dirs = glob($base_path . '/202*', GLOB_ONLYDIR);
            if (!empty($dirs)) {
                rsort($dirs); // Sort descending to get latest first
                return $dirs[0];
            }
        }
        
        return false;
    }
    
    /**
     * Get benchmark files based on category
     */
    private function get_benchmark_files($benchmark_dir, $category) {
        $files = array();
        
        // Map categories to file patterns with metadata
        $category_map = array(
            'all' => array(
                'bench_comprehensive.json',
                'bench_core_performance.json',
                'bench_graph_traversal.json',
                'bench_advanced_patterns.json',
                'bench_compression.json',
                'bench_encryption.json',
                'bench_mvcc.json',
                'bench_image_analysis.json',
            ),
            'vector_search' => array(
                'bench_comprehensive.json',
                'bench_gnn_embeddings.json',
            ),
            'graph_traversal' => array(
                'bench_graph_traversal.json',
                'bench_pagerank.json',
            ),
            'encryption' => array(
                'bench_encryption.json',
                'bench_hsm_provider.json',
            ),
            'compression' => array(
                'bench_compression.json',
            ),
            'transaction' => array(
                'bench_mvcc.json',
                'bench_lock_contention.json',
            ),
            'image_analysis' => array(
                'bench_image_analysis.json',
                'bench_image_analysis_latency.json',
            ),
            'advanced' => array(
                'bench_advanced_patterns.json',
                'bench_hybrid_aql_sugar.json',
                'bench_changefeed_throughput.json',
                'bench_hotspots_micro.json',
            ),
            'gpu' => array(
                'bench_gpu_backends.json',
            ),
            'content' => array(
                'bench_content_versioning.json',
                'bench_index_rebuild.json',
            ),
        );
        
        $patterns = isset($category_map[$category]) ? $category_map[$category] : $category_map['all'];
        
        foreach ($patterns as $pattern) {
            $file_path = $benchmark_dir . '/' . $pattern;
            if (file_exists($file_path)) {
                $files[] = $file_path;
            }
        }
        
        return $files;
    }
    
    /**
     * Parse benchmark JSON files
     */
    private function parse_benchmark_files($files, $metric) {
        $labels = array();
        $data_points = array();
        $higher_is_better = $this->is_higher_better($metric);
        $label_values = array();
        $label_sources = array();
        $summary_stats = array(
            'total_benchmarks' => 0,
            'avg_time' => 0,
            'fastest' => null,
            'slowest' => null,
            'min_value' => null,
            'max_value' => null,
            'files_parsed' => 0,
        );
        
        foreach ($files as $file) {
            if (!is_readable($file)) {
                return new WP_Error('unreadable_benchmark', __('Eine Benchmark-Datei ist nicht lesbar.', 'themisdb-benchmark-visualizer'));
            }
            $content = file_get_contents($file);
            $json = json_decode($content, true);
            
            if (!is_array($json) || !isset($json['benchmarks']) || !is_array($json['benchmarks'])) {
                return new WP_Error('invalid_benchmark', __('Eine Benchmark-Datei enthaelt ungueltiges JSON oder keine Messungen.', 'themisdb-benchmark-visualizer'));
            }
            
            $summary_stats['files_parsed']++;
            
            foreach ($json['benchmarks'] as $bench) {
                if (!is_array($bench) || empty($bench['name']) || !is_string($bench['name']) || !empty($bench['error_occurred']) || (isset($bench['run_type']) && $bench['run_type'] === 'aggregate')) {
                    continue;
                }
                $name = $this->normalize_benchmark_label($bench['name']);
                
                // Extract metric value
                $value = $this->extract_metric_value($bench, $metric);
                
                if ($value !== null) {
                    if (!isset($label_values[$name])) {
                        $label_values[$name] = array();
                        $label_sources[$name] = array();
                    }
                    $label_values[$name][] = $value;
                    $source_name = trim($bench['name']) . ' | ' . basename($file);
                    if ($source_name !== '' && !in_array($source_name, $label_sources[$name], true)) {
                        $label_sources[$name][] = $source_name;
                    }
                    
                    // Track min and max for all metrics
                    if ($summary_stats['min_value'] === null || $value < $summary_stats['min_value']) {
                        $summary_stats['min_value'] = $value;
                    }
                    if ($summary_stats['max_value'] === null || $value > $summary_stats['max_value']) {
                        $summary_stats['max_value'] = $value;
                    }
                    
                }
            }
        }

        foreach ($label_values as $label => $values) {
            $labels[] = $label;
            $data_points[] = array_sum($values) / count($values);
        }
        $summary_stats['total_benchmarks'] = count($data_points);
        $summary_stats['task_count'] = count($data_points);
        
        if (!empty($data_points)) {
            $summary_stats['avg_time'] = array_sum($data_points) / count($data_points);
            $summary_stats['fastest'] = min($data_points);
            $summary_stats['slowest'] = max($data_points);
            $summary_stats['best_value'] = $higher_is_better ? max($data_points) : min($data_points);
            $summary_stats['worst_value'] = $higher_is_better ? min($data_points) : max($data_points);
            $summary_stats['higher_is_better'] = $higher_is_better;
            $summary_stats['metric'] = sanitize_key((string) $metric);
            $summary_stats['task_sources'] = $label_sources;
        }
        
        $summary_stats['displayed_benchmarks'] = count($data_points);
        $summary_stats['unverified_records'] = count($data_points);
        
        // Build datasets
        $datasets = array(
            array(
                'label' => 'ThemisDB',
                'data' => $data_points,
                'backgroundColor' => 'rgba(46, 164, 79, 0.8)',
                'borderColor' => 'rgba(46, 164, 79, 1)',
                'borderWidth' => 2,
                'pointRadius' => 4,
                'pointHoverRadius' => 6,
            ),
        );
        
        return array(
            'labels' => $labels,
            'datasets' => $datasets,
            'summary' => $summary_stats,
        );
    }
    
    /**
     * Extract metric value from benchmark data
     */
    private function extract_metric_value($bench, $metric) {
        switch ($metric) {
            case 'latency':
                // Convert time to milliseconds
                $time = $bench['real_time'] ?? $bench['cpu_time'] ?? null;
                $unit = isset($bench['time_unit']) ? $bench['time_unit'] : 'ms';
                
                return $this->normalize_metric_value($time, $unit, 'latency');
                
            case 'throughput':
                return $this->normalize_metric_value($bench['items_per_second'] ?? null, 'ops/s', 'throughput');
                
            case 'memory':
                // Memory metrics might not be available in all benchmarks
                return $this->normalize_metric_value($bench['memory_usage'] ?? $bench['bytes_used'] ?? null, 'bytes', 'memory');
                
            default:
                return null;
        }

    }

    private function normalize_metric_value($value, $unit, $metric) {
        if (!is_numeric($value) || !is_finite((float) $value) || (float) $value < 0 || !is_string($unit) || !is_string($metric)) {
            return null;
        }
        $units = array(
            'latency' => array('ns' => 0.000001, 'us' => 0.001, 'ms' => 1, 's' => 1000),
            'throughput' => array('ops/s' => 1, 'ops/sec' => 1, 'items/s' => 1, 'items_per_second' => 1),
            'memory' => array('bytes' => 1 / 1048576, 'B' => 1 / 1048576, 'KiB' => 1 / 1024, 'MiB' => 1, 'MB' => 1000000 / 1048576, 'GB' => 1000000000 / 1048576, 'GiB' => 1024),
        );
        $normalized = isset($units[$metric][$unit]) ? (float) $value * $units[$metric][$unit] : null;
        return $normalized !== null && is_finite($normalized) ? $normalized : null;
    }
    
    /**
     * Load GitHub benchmark data
     */
    private function load_github_benchmark_data($category, $metric) {
        return new WP_Error('unsupported_source', __('Die GitHub-Datenquelle ist nicht implementiert. Bitte einen lokalen Katalog oder lokale Messdateien konfigurieren.', 'themisdb-benchmark-visualizer'));
    }
}

// Initialize plugin
function themisdb_benchmark_visualizer_init() {
    return ThemisDB_Benchmark_Visualizer::get_instance();
}

add_action('plugins_loaded', 'themisdb_benchmark_visualizer_init');
