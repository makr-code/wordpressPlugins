<?php
/**
 * Plugin Name: ThemisDB Global Network Map
 * Plugin URI: https://github.com/makr-code/wordpressPlugins
 * Description: Canvas-based world heatmap showing live ThemisDB runtime locations derived from WordPress DB homecalls and geocache entries.
 * Version: 0.2.0
 * Author: makr-code
 * Author URI: https://github.com/makr-code/wordpressPlugins
 * License: MIT
 * Text Domain: themisdb-global-network-map
 */

if (!defined('ABSPATH')) {
    exit;
}

define('THEMISDB_GNM_VERSION', '0.2.0');
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

        $homecalls_sql = "CREATE TABLE IF NOT EXISTS $homecalls_table (
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
            load float unsigned NOT NULL DEFAULT 0,
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

        $geo_sql = "CREATE TABLE IF NOT EXISTS $geo_cache_table (
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

        update_option('themisdb_gnm_db_version', THEMISDB_GNM_VERSION, false);
        add_option('themisdb_gnm_mode', THEMISDB_GNM_MODE_AUTO, '', 'no');
        add_option('themisdb_gnm_allow_demo_fallback', '1', '', 'no');
        add_option('themisdb_gnm_max_nodes', '250', '', 'no');
        add_option('themisdb_gnm_cache_ttl', '120', '', 'no');
    }

    public static function deactivate() {
        delete_option('themisdb_gnm_db_version');
        delete_option('themisdb_gnm_mode');
        delete_option('themisdb_gnm_allow_demo_fallback');
        delete_option('themisdb_gnm_max_nodes');
        delete_option('themisdb_gnm_cache_ttl');

        delete_transient('themisdb_gnm_production_nodes_250');
        delete_transient('themisdb_gnm_production_nodes_500');
        delete_transient('themisdb_gnm_production_nodes_1000');
        wp_cache_flush();
    }

    private function __construct() {
        add_shortcode('themisdb_global_network_map', array($this, 'render_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function enqueue_assets() {
        if (!is_admin()) {
            global $post;
            $has_shortcode = is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'themisdb_global_network_map');

            if (!$has_shortcode && !is_front_page()) {
                return;
            }

            wp_enqueue_style(
                'themisdb-global-network-map-style',
                THEMISDB_GNM_PLUGIN_URL . 'assets/css/themisdb-global-network-map.css',
                array(),
                THEMISDB_GNM_VERSION
            );

            wp_enqueue_script(
                'themisdb-global-network-map-script',
                THEMISDB_GNM_PLUGIN_URL . 'assets/js/themisdb-global-network-map.js',
                array(),
                THEMISDB_GNM_VERSION,
                true
            );

            $atts = array(
                'mode' => get_option('themisdb_gnm_mode', THEMISDB_GNM_MODE_AUTO),
                'max_nodes' => (int) get_option('themisdb_gnm_max_nodes', 250),
                'theme' => 'ocean',
                'show_controls' => true,
            );

            wp_localize_script('themisdb-global-network-map-script', 'themisdbGnmData', array(
                'nodes' => $this->get_network_nodes($atts['mode'], $atts['max_nodes']),
                'title' => '',
                'mode' => $this->normalize_mode($atts['mode']),
                'theme' => sanitize_key($atts['theme']),
                'showControls' => !empty($atts['show_controls']),
                'geojsonUrl' => THEMISDB_GNM_PLUGIN_URL . 'assets/data/world.geojson',
                'hasLocalGeojson' => file_exists(THEMISDB_GNM_PLUGIN_DIR . 'assets/data/world.geojson'),
                'allowDemoFallback' => (bool) get_option('themisdb_gnm_allow_demo_fallback', true),
            ));
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
        $atts['max_nodes'] = max(1, (int) $atts['max_nodes']);

        wp_enqueue_script('themisdb-global-network-map-script');
        wp_enqueue_style('themisdb-global-network-map-style');

        $this->maybe_enqueue_local_data_for_shortcode($atts);

        ob_start();
        include THEMISDB_GNM_PLUGIN_DIR . 'templates/map.php';
        return ob_get_clean();
    }

    private function maybe_enqueue_local_data_for_shortcode($atts) {
        $nodes = $this->get_network_nodes($atts['mode'], $atts['max_nodes']);

        if (!wp_script_is('themisdb-global-network-map-script', 'enqueued')) {
            return;
        }

        $data = array(
            'nodes' => $nodes,
            'title' => sanitize_text_field($atts['title']),
            'mode' => $this->normalize_mode($atts['mode']),
            'theme' => sanitize_key($atts['theme']),
            'showControls' => !empty($atts['show_controls']),
            'geojsonUrl' => THEMISDB_GNM_PLUGIN_URL . 'assets/data/world.geojson',
            'hasLocalGeojson' => file_exists(THEMISDB_GNM_PLUGIN_DIR . 'assets/data/world.geojson'),
            'allowDemoFallback' => (bool) get_option('themisdb_gnm_allow_demo_fallback', true),
        );

        wp_localize_script('themisdb-global-network-map-script', 'themisdbGnmData', $data);
    }

    public function get_network_nodes($mode = THEMISDB_GNM_MODE_AUTO, $max_nodes = 0) {
        $mode = $this->normalize_mode($mode);

        if (THEMISDB_GNM_MODE_DEMO === $mode) {
            return $this->get_demo_nodes($max_nodes);
        }

        $production_nodes = $this->get_production_nodes($max_nodes);

        if (!empty($production_nodes)) {
            return $production_nodes;
        }

        if (THEMISDB_GNM_MODE_PRODUCTION === $mode) {
            $allow_fallback = (bool) get_option('themisdb_gnm_allow_demo_fallback', true);
            if ($allow_fallback) {
                return $this->get_demo_nodes($max_nodes);
            }
            return array();
        }

        return $this->get_demo_nodes($max_nodes);
    }

    private function get_production_nodes($max_nodes = 0) {
        $cache_key = 'themisdb_gnm_production_nodes_' . (int) $max_nodes;
        $cache_ttl = max(30, (int) get_option('themisdb_gnm_cache_ttl', 120));
        $cached = get_transient($cache_key);
        if (is_array($cached) && !empty($cached)) {
            return $cached;
        }

        global $wpdb;
        $homecalls_table = $wpdb->prefix . 'themisdb_gnm_homecalls';
        $homecalls_table = esc_sql($homecalls_table);
        $homecalls_check = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->prefix . 'themisdb_gnm_homecalls'));
        if (empty($homecalls_check)) {
            return array();
        }

        $limit = max(1, ((int) $max_nodes > 0 ? (int) $max_nodes : 250));
        $query = $wpdb->prepare(
            "SELECT id, instance_id, ip_address, network, city, region, country, lat, lon, instances, load, status, is_active, source, updated_at, created_at FROM $homecalls_table WHERE is_active = 1 ORDER BY updated_at DESC, created_at DESC LIMIT %d",
            $limit
        );
        $rows = $wpdb->get_results($query, ARRAY_A);

        if (empty($rows)) {
            return array();
        }

        $aggregated = array();

        foreach ($rows as $row) {
            $row = array_map('stripslashes_deep', $row);
            $ip = isset($row['ip_address']) ? sanitize_text_field($row['ip_address']) : '';

            if (empty($ip) && empty($row['network'])) {
                continue;
            }

            $geo = $this->resolve_geo_for_ip($ip, $row);

            $lat = $this->sanitize_coordinate($geo['lat'] ?? $row['lat'] ?? null, 'lat');
            $lon = $this->sanitize_coordinate($geo['lon'] ?? $row['lon'] ?? null, 'lon');
            if ($lat === null || $lon === null) {
                continue;
            }

            $network = isset($row['network']) ? sanitize_text_field($row['network']) : ($ip ? $ip . '/32' : 'unknown');
            $key = $ip ? $ip : $network;
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

        if (!empty($nodes)) {
            set_transient($cache_key, $nodes, $cache_ttl);
        }

        return $nodes;
    }

    private function resolve_geo_for_ip($ip, $row = array()) {
        if (empty($ip) || !filter_var($ip, FILTER_VALIDATE_IP)) {
            return array();
        }

        global $wpdb;
        $geo_cache_table = $wpdb->prefix . 'themisdb_gnm_geo_cache';
        $geo_cache_table = esc_sql($geo_cache_table);
        $cache_query = $wpdb->prepare(
            "SELECT * FROM $geo_cache_table WHERE ip_address = %s ORDER BY updated_at DESC LIMIT 1",
            $ip
        );
        $cached = $wpdb->get_row($cache_query, ARRAY_A);

        if (!empty($cached) && !empty($cached['lat']) && !empty($cached['lon'])) {
            return array(
                'lat' => $this->sanitize_coordinate($cached['lat'], 'lat'),
                'lon' => $this->sanitize_coordinate($cached['lon'], 'lon'),
                'city' => sanitize_text_field($cached['city'] ?? ''),
                'region' => sanitize_text_field($cached['region'] ?? ''),
                'country' => sanitize_text_field($cached['country'] ?? ''),
            );
        }

        $lat = $this->sanitize_coordinate($row['lat'] ?? null, 'lat');
        $lon = $this->sanitize_coordinate($row['lon'] ?? null, 'lon');
        if ($lat !== null && $lon !== null) {
            $this->store_geo_cache($ip, array(
                'city' => sanitize_text_field($row['city'] ?? ''),
                'region' => sanitize_text_field($row['region'] ?? ''),
                'country' => sanitize_text_field($row['country'] ?? ''),
                'lat' => $lat,
                'lon' => $lon,
                'geo_status' => 'resolved',
                'source' => 'homecall',
            ));
            return array(
                'lat' => $lat,
                'lon' => $lon,
                'city' => sanitize_text_field($row['city'] ?? ''),
                'region' => sanitize_text_field($row['region'] ?? ''),
                'country' => sanitize_text_field($row['country'] ?? ''),
            );
        }

        return array();
    }

    private function store_geo_cache($ip, $geo_data) {
        if (empty($ip) || !filter_var($ip, FILTER_VALIDATE_IP)) {
            return;
        }

        global $wpdb;
        $geo_cache_table = $wpdb->prefix . 'themisdb_gnm_geo_cache';
        $geo_cache_table = esc_sql($geo_cache_table);
        $city = isset($geo_data['city']) ? sanitize_text_field($geo_data['city']) : '';
        $region = isset($geo_data['region']) ? sanitize_text_field($geo_data['region']) : '';
        $country = isset($geo_data['country']) ? sanitize_text_field($geo_data['country']) : '';
        $lat = $this->sanitize_coordinate($geo_data['lat'] ?? null, 'lat');
        $lon = $this->sanitize_coordinate($geo_data['lon'] ?? null, 'lon');
        $geo_status = isset($geo_data['geo_status']) ? sanitize_key($geo_data['geo_status']) : 'resolved';
        $source = isset($geo_data['source']) ? sanitize_key($geo_data['source']) : 'cache';

        if ($lat === null || $lon === null) {
            return;
        }

        $wpdb->query($wpdb->prepare(
            "INSERT INTO $geo_cache_table (ip_address, city, region, country, lat, lon, geo_status, source, updated_at)
             VALUES (%s, %s, %s, %s, %f, %f, %s, %s, %s)
             ON DUPLICATE KEY UPDATE city = VALUES(city), region = VALUES(region), country = VALUES(country), lat = VALUES(lat), lon = VALUES(lon), geo_status = VALUES(geo_status), source = VALUES(source), updated_at = VALUES(updated_at)",
            $ip,
            $city,
            $region,
            $country,
            $lat,
            $lon,
            $geo_status,
            $source,
            current_time('mysql')
        ));
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
            'updated_at' => sanitize_text_field($node['updated_at'] ?? current_time('mysql')),
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

        return array_values(array_map(array($this, 'normalize_node'), $nodes));
    }

    private function sanitize_coordinate($value, $kind) {
        if ($value === null || $value === '') {
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
