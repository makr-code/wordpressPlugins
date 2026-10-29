<?php
/**
 * Plugin Name: ThemisDB Global Network Map
 * Plugin URI: https://github.com/makr-code/wordpressPlugins
 * Description: WebGPU globe with GeoJSON continents and geolocated ThemisDB homecall telemetry.
 * Version: 0.3.0
 * Author: makr-code
 * Author URI: https://github.com/makr-code/wordpressPlugins
 * License: MIT
 * Text Domain: themisdb-global-network-map
 */

if (!defined('ABSPATH')) {
    exit;
}

define('THEMISDB_GNM_VERSION', '0.3.0');
define('THEMISDB_GNM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('THEMISDB_GNM_PLUGIN_URL', plugin_dir_url(__FILE__));

define('THEMISDB_GNM_MODE_AUTO', 'auto');
define('THEMISDB_GNM_MODE_PRODUCTION', 'production');
define('THEMISDB_GNM_MODE_DEMO', 'demo');

class ThemisDB_Global_Network_Map {
    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function activate() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $homecalls_table = $wpdb->prefix . 'themisdb_gnm_homecalls';
        $geo_cache_table = $wpdb->prefix . 'themisdb_gnm_geo_cache';
        $charset_collate = $wpdb->get_charset_collate();

        $homecalls_sql = "CREATE TABLE $homecalls_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            instance_id varchar(128) DEFAULT NULL,
            ip_address varchar(45) NOT NULL,
            network varchar(64) DEFAULT NULL,
            city varchar(120) DEFAULT NULL,
            region varchar(120) DEFAULT NULL,
            country varchar(4) DEFAULT NULL,
            lat decimal(9,6) DEFAULT NULL,
            lon decimal(9,6) DEFAULT NULL,
            instances int(11) unsigned NOT NULL DEFAULT 1,
            `load` float unsigned NOT NULL DEFAULT 0,
            status varchar(32) NOT NULL DEFAULT 'active',
            is_active tinyint(1) NOT NULL DEFAULT 1,
            source varchar(64) DEFAULT 'homecall',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_ip_address (ip_address),
            KEY idx_active_updated (is_active, updated_at),
            KEY idx_country (country),
            KEY idx_instance (instance_id)
        ) $charset_collate;";

        $geo_sql = "CREATE TABLE $geo_cache_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            ip_address varchar(45) NOT NULL,
            city varchar(120) DEFAULT NULL,
            region varchar(120) DEFAULT NULL,
            country varchar(4) DEFAULT NULL,
            lat decimal(9,6) DEFAULT NULL,
            lon decimal(9,6) DEFAULT NULL,
            geo_status varchar(32) NOT NULL DEFAULT 'pending',
            source varchar(64) DEFAULT 'cache',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY idx_ip_unique (ip_address),
            KEY idx_geo_status (geo_status),
            KEY idx_updated_at (updated_at)
        ) $charset_collate;";

        dbDelta($homecalls_sql);
        dbDelta($geo_sql);

        foreach (array($homecalls_table, $geo_cache_table) as $table) {
            if (!$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table)))) {
                error_log('ThemisDB Global Network Map: failed to create telemetry tables.');
                return;
            }
        }
        update_option('themisdb_gnm_db_version', THEMISDB_GNM_VERSION, false);
        add_option('themisdb_gnm_mode', THEMISDB_GNM_MODE_AUTO, '', 'no');
        add_option('themisdb_gnm_allow_demo_fallback', '1', '', 'no');
        add_option('themisdb_gnm_max_nodes', '250', '', 'no');
        add_option('themisdb_gnm_cache_ttl', '120', '', 'no');
    }

    public static function deactivate() {
        delete_transient('themisdb_gnm_production_nodes_250');
        delete_transient('themisdb_gnm_production_nodes_500');
        delete_transient('themisdb_gnm_production_nodes_1000');
    }

    private function __construct() {
        add_shortcode('themisdb_global_network_map', array($this, 'render_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('init', array($this, 'maybe_upgrade'));
        add_action('wp_ajax_themisdb_gnm_geojson', array($this, 'ajax_geojson'));
        add_action('wp_ajax_nopriv_themisdb_gnm_geojson', array($this, 'ajax_geojson'));
        add_action('rest_api_init', function () {
            register_rest_route('themisdb-gnm/v1', '/nodes', array(
                'methods' => 'GET',
                'permission_callback' => '__return_true',
                'callback' => array($this, 'rest_nodes'),
                'args' => array(
                    'mode' => array('default' => 'auto', 'type' => 'string', 'enum' => array('auto', 'production', 'demo')),
                    'max_nodes' => array('default' => 250, 'type' => 'integer', 'minimum' => 1, 'maximum' => 1000),
                ),
            ));
        });
    }

    public function maybe_upgrade() {
        if (get_option('themisdb_gnm_db_version') !== THEMISDB_GNM_VERSION) {
            self::activate();
        }
    }

    public function ajax_geojson() {
        $geojson_path = THEMISDB_GNM_PLUGIN_DIR . 'assets/data/world.geojson';

        if (!is_readable($geojson_path)) {
            status_header(404);
            wp_die('GeoJSON not found', 'GeoJSON not found', array('response' => 404));
        }

        $raw_geojson = file_get_contents($geojson_path);
        if ($raw_geojson === false) {
            status_header(500);
            wp_die('GeoJSON unreadable', 'GeoJSON unreadable', array('response' => 500));
        }

        $geojson = json_decode($raw_geojson, true);
        if (!is_array($geojson)) {
            status_header(500);
            wp_die('Invalid GeoJSON', 'Invalid GeoJSON', array('response' => 500));
        }

        $requested_feature = isset($_GET['feature']) ? sanitize_text_field(wp_unslash($_GET['feature'])) : '';
        if ($requested_feature !== '') {
            $needle = strtolower(trim($requested_feature));
            $filtered = array(
                'type' => 'FeatureCollection',
                'features' => array(),
            );

            foreach ($geojson['features'] as $feature) {
                if (!is_array($feature) || empty($feature['properties']) || !is_array($feature['properties'])) {
                    continue;
                }

                $property_names = array();
                if (!empty($feature['properties']['name'])) {
                    $property_names[] = strtolower((string) $feature['properties']['name']);
                }
                if (!empty($feature['properties']['ISO3166-1-Alpha-2'])) {
                    $property_names[] = strtolower((string) $feature['properties']['ISO3166-1-Alpha-2']);
                }
                if (!empty($feature['properties']['ISO3166-1-Alpha-3'])) {
                    $property_names[] = strtolower((string) $feature['properties']['ISO3166-1-Alpha-3']);
                }

                $matched = false;
                foreach ($property_names as $name) {
                    if ($name === $needle || strpos($name, $needle) !== false || strpos($needle, $name) !== false) {
                        $matched = true;
                        break;
                    }
                }

                if ($matched) {
                    $filtered['features'][] = $feature;
                }
            }

            if (empty($filtered['features'])) {
                $filtered['features'] = array();
            }

            wp_send_json($filtered);
        }

        wp_send_json($geojson);
    }

    public function enqueue_assets() {
        wp_register_style(
            'themisdb-global-network-map-style',
            THEMISDB_GNM_PLUGIN_URL . 'assets/css/themisdb-global-network-map.css',
            array(),
            THEMISDB_GNM_VERSION . '.' . filemtime(THEMISDB_GNM_PLUGIN_DIR . 'assets/css/themisdb-global-network-map.css')
        );
        wp_register_script(
            'themisdb-global-network-map-script',
            THEMISDB_GNM_PLUGIN_URL . 'assets/dist/network-map.js',
            array(),
            THEMISDB_GNM_VERSION . '.' . filemtime(THEMISDB_GNM_PLUGIN_DIR . 'assets/dist/network-map.js'),
            true
        );
        global $post;
        if (is_front_page() || (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'themisdb_global_network_map'))) {
            wp_enqueue_style('themisdb-global-network-map-style');
        }
    }

    public function render_shortcode($atts = array()) {
        $atts = shortcode_atts(array(
            'title' => '',
            'height' => 540,
            'show_legend' => true,
            'mode' => get_option('themisdb_gnm_mode', THEMISDB_GNM_MODE_AUTO),
            'max_nodes' => get_option('themisdb_gnm_max_nodes', 250),
            'theme' => 'ocean',
            'show_controls' => true,
        ), $atts, 'themisdb_global_network_map');

        $height = (int) $atts['height'];
        $height = max(360, min(760, $height));

        $atts['show_legend'] = filter_var($atts['show_legend'], FILTER_VALIDATE_BOOLEAN);
        $atts['show_controls'] = filter_var($atts['show_controls'], FILTER_VALIDATE_BOOLEAN);
        $atts['mode'] = $this->normalize_mode($atts['mode']);
        $atts['max_nodes'] = max(1, min(1000, (int) $atts['max_nodes']));

        if (!wp_script_is('themisdb-global-network-map-script', 'registered')) {
            $this->enqueue_assets();
        }

        wp_enqueue_script('themisdb-global-network-map-script');
        wp_enqueue_style('themisdb-global-network-map-style');

        $config = array(
            'geojsonUrl' => THEMISDB_GNM_PLUGIN_URL . 'assets/data/world.geojson',
            'nodesUrl' => add_query_arg(array('mode' => $atts['mode'], 'max_nodes' => $atts['max_nodes']), rest_url('themisdb-gnm/v1/nodes')),
            'theme' => in_array($atts['theme'], array('ocean', 'aurora', 'night'), true) ? $atts['theme'] : 'ocean',
            'refreshSeconds' => max(30, (int) get_option('themisdb_gnm_cache_ttl', 120)),
        );

        ob_start();
        include THEMISDB_GNM_PLUGIN_DIR . 'templates/map.php';
        // Keep wpautop from inserting paragraphs into shortcode controls in block content.
        return str_replace(array("\r", "\n"), '', ob_get_clean());
    }

    public function rest_nodes($request) {
        $mode = $this->normalize_mode($request['mode']);
        $limit = max(1, min(1000, (int) $request['max_nodes']));
        $nodes = $this->get_network_nodes($mode, $limit);
        if (is_wp_error($nodes)) {
            return $nodes;
        }
        $source = $mode === THEMISDB_GNM_MODE_DEMO ? 'demo' : 'production';
        if (!empty($nodes) && !empty($nodes[0]['is_demo'])) {
            $source = 'demo';
        }
        return rest_ensure_response(array(
            'nodes' => $nodes,
            'source' => $source,
            'generatedAt' => gmdate('c'),
            'maxNodes' => $limit,
        ));
    }

    private function database_error() {
        return new WP_Error(
            'themisdb_gnm_database_error',
            __('The network telemetry could not be read from the database.', 'themisdb-global-network-map'),
            array('status' => 503)
        );
    }

    public function get_network_nodes($mode = THEMISDB_GNM_MODE_AUTO, $max_nodes = 0) {
        $mode = $this->normalize_mode($mode);

        if (THEMISDB_GNM_MODE_DEMO === $mode) {
            return $this->get_demo_nodes($max_nodes);
        }

        $production_nodes = $this->get_production_nodes($max_nodes);
        if (is_wp_error($production_nodes)) {
            return $production_nodes;
        }

        if (!empty($production_nodes)) {
            return $production_nodes;
        }

        if (THEMISDB_GNM_MODE_PRODUCTION === $mode || !get_option('themisdb_gnm_allow_demo_fallback', true)) {
            return array();
        }

        return $this->get_demo_nodes($max_nodes);
    }

    private function get_production_nodes($max_nodes = 0) {
        $limit = max(1, min(1000, ((int) $max_nodes > 0 ? (int) $max_nodes : 250)));
        $cache_key = 'themisdb_gnm_v3_nodes_' . $limit;
        $cache_ttl = max(30, (int) get_option('themisdb_gnm_cache_ttl', 120));
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            return $cached;
        }

        global $wpdb;
        $homecalls_table = $wpdb->prefix . 'themisdb_gnm_homecalls';
        $homecalls_check = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($homecalls_table)));
        if ($wpdb->last_error) {
            return $this->database_error();
        }
        if (empty($homecalls_check)) {
            return new WP_Error('themisdb_gnm_missing_tables', __('The network map tables are missing. Reactivate the plugin to create them.', 'themisdb-global-network-map'), array('status' => 503));
        }

        $geo_cache_table = $wpdb->prefix . 'themisdb_gnm_geo_cache';
        $geo_cache_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($geo_cache_table)));
        if ($wpdb->last_error) {
            return $this->database_error();
        }
        $geo_fields = $geo_cache_exists ? ', g.lat AS geo_lat, g.lon AS geo_lon, g.city AS geo_city, g.region AS geo_region, g.country AS geo_country' : '';
        $geo_join = $geo_cache_exists ? " LEFT JOIN $geo_cache_table g ON g.ip_address = h.ip_address AND g.geo_status = 'resolved'" : '';
        $query = $wpdb->prepare(
            "SELECT h.* $geo_fields FROM $homecalls_table h $geo_join
             WHERE h.is_active = 1 AND NOT EXISTS (
                SELECT 1 FROM $homecalls_table newer
                WHERE ((h.instance_id IS NOT NULL AND h.instance_id <> '' AND newer.instance_id = h.instance_id)
                    OR ((h.instance_id IS NULL OR h.instance_id = '') AND (newer.instance_id IS NULL OR newer.instance_id = '') AND newer.ip_address = h.ip_address))
                AND (newer.updated_at > h.updated_at OR (newer.updated_at = h.updated_at AND newer.id > h.id))
             )
             ORDER BY h.updated_at DESC, h.id DESC LIMIT %d",
            $limit
        );
        $rows = $wpdb->get_results($query, ARRAY_A);
        if ($wpdb->last_error) {
            return $this->database_error();
        }

        if (empty($rows)) {
            return array();
        }

        $aggregated = array();

        foreach ($rows as $row) {
            $ip = isset($row['ip_address']) ? sanitize_text_field($row['ip_address']) : '';

            if (empty($ip) && empty($row['network'])) {
                continue;
            }

            $geo = $this->resolve_geo_for_row($row);

            $lat = $this->sanitize_coordinate($geo['lat'] ?? $row['lat'] ?? null, 'lat');
            $lon = $this->sanitize_coordinate($geo['lon'] ?? $row['lon'] ?? null, 'lon');
            if ($lat === null || $lon === null) {
                continue;
            }

            $network = !empty($row['network']) ? sanitize_text_field($row['network']) : '';
            $key = $lat . ':' . $lon . ':' . $network;
            if (!isset($aggregated[$key])) {
                $aggregated[$key] = array(
                    'network' => $network,
                    'city' => !empty($geo['city']) ? $geo['city'] : sanitize_text_field($row['city'] ?? ''),
                    'region' => !empty($geo['region']) ? $geo['region'] : sanitize_text_field($row['region'] ?? ''),
                    'country' => !empty($geo['country']) ? $geo['country'] : sanitize_text_field($row['country'] ?? ''),
                    'lat' => $lat,
                    'lon' => $lon,
                    'instances' => 0,
                    'load' => 0,
                    'status' => sanitize_key($row['status'] ?? 'active'),
                    'updated_at' => !empty($row['updated_at']) ? $row['updated_at'] : current_time('mysql'),
                );
            }

            $aggregated[$key]['instances'] += max(1, (int) ($row['instances'] ?? 1));
            $aggregated[$key]['load'] = max((float) $aggregated[$key]['load'], (float) ($row['load'] ?? 0));

            if (empty($aggregated[$key]['status']) || empty($row['status'])) {
                $aggregated[$key]['status'] = sanitize_key($row['status'] ?? $aggregated[$key]['status']);
            }

            if (empty($aggregated[$key]['updated_at']) || empty($row['updated_at'])) {
                $aggregated[$key]['updated_at'] = $row['updated_at'] ?? $aggregated[$key]['updated_at'];
            }
        }

        $nodes = array_values($aggregated);
        $nodes = array_map(array($this, 'normalize_node'), $nodes);
        $nodes = $this->filter_valid_nodes($nodes);

        set_transient($cache_key, $nodes, $cache_ttl);

        return $nodes;
    }

    private function resolve_geo_for_row($row) {
        $lat = $this->sanitize_coordinate($row['lat'] ?? null, 'lat');
        $lon = $this->sanitize_coordinate($row['lon'] ?? null, 'lon');
        if ($lat !== null && $lon !== null) {
            return array(
                'lat' => $lat,
                'lon' => $lon,
                'city' => sanitize_text_field($row['city'] ?? ''),
                'region' => sanitize_text_field($row['region'] ?? ''),
                'country' => sanitize_text_field($row['country'] ?? ''),
            );
        }

        $lat = $this->sanitize_coordinate($row['geo_lat'] ?? null, 'lat');
        $lon = $this->sanitize_coordinate($row['geo_lon'] ?? null, 'lon');
        return $lat !== null && $lon !== null ? array(
            'lat' => $lat,
            'lon' => $lon,
            'city' => sanitize_text_field($row['geo_city'] ?? ''),
            'region' => sanitize_text_field($row['geo_region'] ?? ''),
            'country' => sanitize_text_field($row['geo_country'] ?? ''),
        ) : array();
    }

    private function normalize_node($node) {
        if (!is_array($node)) {
            return array();
        }

        $lat = $this->sanitize_coordinate($node['lat'] ?? null, 'lat');
        $lon = $this->sanitize_coordinate($node['lon'] ?? null, 'lon');
        if ($lat === null || $lon === null) {
            return array();
        }

        return array(
            'network' => isset($node['network']) ? sanitize_text_field($node['network']) : '',
            'city' => sanitize_text_field($node['city'] ?? ''),
            'region' => sanitize_text_field($node['region'] ?? ''),
            'country' => sanitize_text_field($node['country'] ?? ''),
            'lat' => $lat,
            'lon' => $lon,
            'instances' => max(1, (int) ($node['instances'] ?? 1)),
            'load' => min(100, max(0, (float) ($node['load'] ?? 0))),
            'status' => sanitize_key($node['status'] ?? 'active'),
            'updated_at' => sanitize_text_field($node['updated_at'] ?? ''),
            'is_demo' => !empty($node['is_demo']),
        );
    }

    private function filter_valid_nodes($nodes) {
        return array_values(array_filter($nodes, function ($node) {
            $lat = $this->sanitize_coordinate($node['lat'] ?? null, 'lat');
            $lon = $this->sanitize_coordinate($node['lon'] ?? null, 'lon');
            $load = isset($node['load']) ? (float) $node['load'] : 0;
            $instances = isset($node['instances']) ? (int) $node['instances'] : 0;

            return $lat !== null && $lon !== null && $instances >= 0 && $load >= 0 && $load <= 100;
        }));
    }

    private function get_demo_nodes($max_nodes = 0) {
        $nodes = array(
            array('network' => '198.51.100.8/24', 'city' => 'Los Angeles', 'region' => 'Pacific Time', 'country' => 'US', 'lat' => 34.0522, 'lon' => -118.2437, 'instances' => 8, 'load' => 52, 'status' => 'active'),
            array('network' => '198.51.100.9/24', 'city' => 'New York', 'region' => 'Eastern Time', 'country' => 'US', 'lat' => 40.7128, 'lon' => -74.0060, 'instances' => 11, 'load' => 78, 'status' => 'active'),
            array('network' => '172.16.44.8/24', 'city' => 'São Paulo', 'region' => 'Brazil', 'country' => 'BR', 'lat' => -23.5505, 'lon' => -46.6333, 'instances' => 8, 'load' => 63, 'status' => 'active'),
            array('network' => '192.168.80.12/24', 'city' => 'London', 'region' => 'Greenwich Mean Time', 'country' => 'GB', 'lat' => 51.5074, 'lon' => -0.1278, 'instances' => 12, 'load' => 71, 'status' => 'active'),
            array('network' => '203.0.113.11/24', 'city' => 'Frankfurt', 'region' => 'Central European Time', 'country' => 'DE', 'lat' => 50.1109, 'lon' => 8.6821, 'instances' => 14, 'load' => 82, 'status' => 'warning'),
            array('network' => '10.20.10.7/24', 'city' => 'Dubai', 'region' => 'Gulf Standard Time', 'country' => 'AE', 'lat' => 25.2048, 'lon' => 55.2708, 'instances' => 7, 'load' => 68, 'status' => 'active'),
            array('network' => '192.0.2.14/24', 'city' => 'Singapore', 'region' => 'Singapore Standard Time', 'country' => 'SG', 'lat' => 1.3521, 'lon' => 103.8198, 'instances' => 9, 'load' => 66, 'status' => 'active'),
            array('network' => '10.10.4.17/24', 'city' => 'Tokyo', 'region' => 'Japan Standard Time', 'country' => 'JP', 'lat' => 35.6762, 'lon' => 139.6503, 'instances' => 10, 'load' => 74, 'status' => 'warning'),
            array('network' => '10.20.22.7/24', 'city' => 'Sydney', 'region' => 'Australia Eastern Time', 'country' => 'AU', 'lat' => -33.8688, 'lon' => 151.2093, 'instances' => 7, 'load' => 59, 'status' => 'active'),
        );

        $limit = max(0, (int) $max_nodes);
        if ($limit > 0) {
            $nodes = array_slice($nodes, 0, $limit);
        }

        return array_values(array_map(function ($node) {
            $node['is_demo'] = true;
            return $this->normalize_node($node);
        }, $nodes));
    }

    private function sanitize_coordinate($value, $kind) {
        if (!is_numeric($value)) {
            return null;
        }

        $float = (float) $value;
        if (!is_finite($float)) {
            return null;
        }

        if ('lat' === $kind && ($float < -90 || $float > 90)) {
            return null;
        }

        if ('lon' === $kind && ($float < -180 || $float > 180)) {
            return null;
        }

        return $float;
    }

    public function normalize_mode($mode) {
        $mode = strtolower((string) $mode);
        if (in_array($mode, array(THEMISDB_GNM_MODE_PRODUCTION, THEMISDB_GNM_MODE_DEMO), true)) {
            return $mode;
        }

        return THEMISDB_GNM_MODE_AUTO;
    }
}

register_activation_hook(__FILE__, array('ThemisDB_Global_Network_Map', 'activate'));
register_deactivation_hook(__FILE__, array('ThemisDB_Global_Network_Map', 'deactivate'));
ThemisDB_Global_Network_Map::get_instance();
