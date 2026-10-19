<?php
/**
 * Plugin Name: ThemisDB Global Network Map
 * Plugin URI: https://github.com/makr-code/wordpressPlugins
 * Description: Canvas-based world heatmap showing live ThemisDB runtime locations derived from network IP ranges.
 * Version: 0.1.3
 * Author: makr-code
 * Author URI: https://github.com/makr-code/wordpressPlugins
 * License: MIT
 * Text Domain: themisdb-global-network-map
 */

if (!defined('ABSPATH')) {
    exit;
}

define('THEMISDB_GNM_VERSION', '0.1.3');
define('THEMISDB_GNM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('THEMISDB_GNM_PLUGIN_URL', plugin_dir_url(__FILE__));

class ThemisDB_Global_Network_Map {
    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
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

            $css_version = THEMISDB_GNM_VERSION . '.' . filemtime(THEMISDB_GNM_PLUGIN_DIR . 'assets/css/themisdb-global-network-map.css');
            $js_version = THEMISDB_GNM_VERSION . '.' . filemtime(THEMISDB_GNM_PLUGIN_DIR . 'assets/js/themisdb-global-network-map.js');

            wp_enqueue_style(
                'themisdb-global-network-map-style',
                THEMISDB_GNM_PLUGIN_URL . 'assets/css/themisdb-global-network-map.css',
                array(),
                $css_version
            );

            wp_enqueue_script(
                'themisdb-global-network-map-script',
                THEMISDB_GNM_PLUGIN_URL . 'assets/js/themisdb-global-network-map.js',
                array(),
                $js_version,
                true
            );

            wp_localize_script('themisdb-global-network-map-script', 'themisdbGnmData', array(
                'nodes' => $this->get_network_nodes(),
                'title' => '',
            ));
        }
    }

    public function render_shortcode($atts = array()) {
        $atts = shortcode_atts(array(
            'title' => '',
            'height' => 540,
            'show_legend' => true,
        ), $atts, 'themisdb_global_network_map');

        $height = (int) $atts['height'];
        $height = max(360, min(760, $height));

        ob_start();
        include THEMISDB_GNM_PLUGIN_DIR . 'templates/map.php';
        return ob_get_clean();
    }

    private function get_network_nodes() {
        return array(
            array(
                'network' => '198.51.100.8/24',
                'city' => 'Los Angeles',
                'region' => 'Pacific Time',
                'country' => 'US',
                'lat' => 34.0522,
                'lon' => -118.2437,
                'instances' => 8,
                'load' => 52,
            ),
            array(
                'network' => '198.51.100.9/24',
                'city' => 'New York',
                'region' => 'Eastern Time',
                'country' => 'US',
                'lat' => 40.7128,
                'lon' => -74.0060,
                'instances' => 11,
                'load' => 78,
            ),
            array(
                'network' => '172.16.44.8/24',
                'city' => 'São Paulo',
                'region' => 'Brazil',
                'country' => 'BR',
                'lat' => -23.5505,
                'lon' => -46.6333,
                'instances' => 8,
                'load' => 63,
            ),
            array(
                'network' => '192.168.80.12/24',
                'city' => 'London',
                'region' => 'Greenwich Mean Time',
                'country' => 'GB',
                'lat' => 51.5074,
                'lon' => -0.1278,
                'instances' => 12,
                'load' => 71,
            ),
            array(
                'network' => '203.0.113.11/24',
                'city' => 'Frankfurt',
                'region' => 'Central European Time',
                'country' => 'DE',
                'lat' => 50.1109,
                'lon' => 8.6821,
                'instances' => 14,
                'load' => 82,
            ),
            array(
                'network' => '10.20.10.7/24',
                'city' => 'Dubai',
                'region' => 'Gulf Standard Time',
                'country' => 'AE',
                'lat' => 25.2048,
                'lon' => 55.2708,
                'instances' => 7,
                'load' => 68,
            ),
            array(
                'network' => '192.0.2.14/24',
                'city' => 'Singapore',
                'region' => 'Singapore Standard Time',
                'country' => 'SG',
                'lat' => 1.3521,
                'lon' => 103.8198,
                'instances' => 9,
                'load' => 66,
            ),
            array(
                'network' => '10.10.4.17/24',
                'city' => 'Tokyo',
                'region' => 'Japan Standard Time',
                'country' => 'JP',
                'lat' => 35.6762,
                'lon' => 139.6503,
                'instances' => 10,
                'load' => 74,
            ),
            array(
                'network' => '10.20.22.7/24',
                'city' => 'Sydney',
                'region' => 'Australia Eastern Time',
                'country' => 'AU',
                'lat' => -33.8688,
                'lon' => 151.2093,
                'instances' => 7,
                'load' => 59,
            ),
        );
    }
}

ThemisDB_Global_Network_Map::get_instance();
