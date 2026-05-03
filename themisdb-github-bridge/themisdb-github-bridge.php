<?php
/**
 * Plugin Name: ThemisDB GitHub Bridge
 * Plugin URI: https://github.com/makr-code/wordpressPlugins
 * Update URI: https://github.com/makr-code/wordpressPlugins
 */

/**

 * Plugin URI: https://github.com/makr-code/wordpressPlugins


 * Update URI: https://github.com/makr-code/wordpressPlugins
 * Description: Zentrale GitHub-Kommunikation fuer ThemisDB Order Request und ThemisDB Support Portal. Erstellt Issues automatisiert aus Tickets.
 * Version: 1.0.1
 * Author: ThemisDB Team
 * Author URI: https://github.com/makr-code/wordpressPlugins
 * License: MIT
 * Text Domain: themisdb-github-bridge
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

if (version_compare(PHP_VERSION, '7.4', '<')) {
    add_action('admin_notices', function () {
        echo '<div class="error"><p><strong>ThemisDB GitHub Bridge:</strong> Dieses Plugin benoetigt PHP 7.4 oder hoeher. Sie verwenden PHP ' . esc_html(PHP_VERSION) . '</p></div>';
    });
    return;
}

define('THEMISDB_GITHUB_BRIDGE_VERSION', '1.0.1');
define('THEMISDB_GITHUB_BRIDGE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('THEMISDB_GITHUB_BRIDGE_PLUGIN_FILE', __FILE__);

if (!class_exists('ThemisDB_Plugin_Updater')) {
    $themisdb_updater_shared = dirname(THEMISDB_GITHUB_BRIDGE_PLUGIN_DIR) . '/includes/class-themisdb-plugin-updater.php';
    $themisdb_updater_local = THEMISDB_GITHUB_BRIDGE_PLUGIN_DIR . 'includes/class-themisdb-plugin-updater.php';

    if (file_exists($themisdb_updater_shared)) {
        require_once $themisdb_updater_shared;
    } elseif (file_exists($themisdb_updater_local)) {
        require_once $themisdb_updater_local;
    }
}

if (class_exists('ThemisDB_Plugin_Updater')) {
    new ThemisDB_Plugin_Updater(
        THEMISDB_GITHUB_BRIDGE_PLUGIN_FILE,
        'themisdb-github-bridge',
        THEMISDB_GITHUB_BRIDGE_VERSION
    );
}

require_once THEMISDB_GITHUB_BRIDGE_PLUGIN_DIR . 'includes/class-github-client.php';
require_once THEMISDB_GITHUB_BRIDGE_PLUGIN_DIR . 'includes/class-github-bridge.php';
require_once THEMISDB_GITHUB_BRIDGE_PLUGIN_DIR . 'includes/class-dashboard-widget.php';

if (!function_exists('themisdb_github_bridge_is_active')) {
    function themisdb_github_bridge_is_active() {
        return class_exists('ThemisDB_GitHub_Bridge');
    }
}

if (!function_exists('themisdb_github_bridge_request')) {
    /**
     * Execute a GitHub API request via the central bridge client.
     *
     * @param string $method HTTP method.
     * @param string $url Full API URL.
     * @param array  $args Request options.
     * @return array|WP_Error
     */
    function themisdb_github_bridge_request($method, $url, $args = array()) {
        if (!class_exists('ThemisDB_GitHub_Client')) {
            return new WP_Error('bridge_unavailable', 'GitHub Bridge Client ist nicht verfügbar.');
        }

        return ThemisDB_GitHub_Client::request($method, $url, $args);
    }
}

if (!function_exists('themisdb_github_bridge_build_repo_api_url')) {
    /**
     * Build a GitHub API URL for a repository endpoint.
     *
     * @param string $repository owner/repo.
     * @param string $endpoint Endpoint relative to /repos/{owner}/{repo}/.
     * @param array  $query Optional query params.
     * @return string|WP_Error
     */
    function themisdb_github_bridge_build_repo_api_url($repository, $endpoint, $query = array()) {
        $repository = trim((string) $repository);
        if ('' === $repository || strpos($repository, '/') === false) {
            return new WP_Error('invalid_repository', 'GitHub Repository muss im Format owner/repo angegeben werden.');
        }

        list($owner, $repo) = array_map('trim', explode('/', $repository, 2));
        if ('' === $owner || '' === $repo) {
            return new WP_Error('invalid_repository', 'GitHub Repository ist ungueltig.');
        }

        $endpoint = ltrim((string) $endpoint, '/');
        $url = sprintf(
            'https://api.github.com/repos/%s/%s/%s',
            rawurlencode($owner),
            rawurlencode($repo),
            $endpoint
        );

        if (!empty($query)) {
            $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        return $url;
    }
}

if (!function_exists('themisdb_github_bridge_fetch_repo_json')) {
    /**
     * Fetch JSON data from a repository endpoint via bridge.
     *
     * @param string $repository owner/repo.
     * @param string $endpoint Endpoint relative to /repos/{owner}/{repo}/.
     * @param array  $query Optional query params.
     * @param array  $args Optional request args.
     * @return array|WP_Error
     */
    function themisdb_github_bridge_fetch_repo_json($repository, $endpoint, $query = array(), $args = array()) {
        $url = themisdb_github_bridge_build_repo_api_url($repository, $endpoint, $query);
        if (is_wp_error($url)) {
            return $url;
        }

        $result = themisdb_github_bridge_request('GET', $url, $args);
        if (is_wp_error($result)) {
            return $result;
        }

        $status_code = (int) ($result['status_code'] ?? 0);
        if ($status_code < 200 || $status_code >= 300) {
            $message = '';
            if (isset($result['json']['message'])) {
                $message = (string) $result['json']['message'];
            }
            if ('' === $message) {
                $message = (string) ($result['body'] ?? 'GitHub API Fehler.');
            }

            return new WP_Error('github_api_error', $message, array('status_code' => $status_code));
        }

        if (is_array($result['json'])) {
            return $result['json'];
        }

        return new WP_Error('github_json_error', 'GitHub API Antwort konnte nicht als JSON gelesen werden.');
    }
}

if (!function_exists('themisdb_github_bridge_fetch_releases')) {
    function themisdb_github_bridge_fetch_releases($repository, $per_page = 10) {
        return themisdb_github_bridge_fetch_repo_json($repository, 'releases', array(
            'per_page' => max(1, (int) $per_page),
        ));
    }
}

if (!function_exists('themisdb_github_bridge_fetch_tags')) {
    function themisdb_github_bridge_fetch_tags($repository, $per_page = 30) {
        return themisdb_github_bridge_fetch_repo_json($repository, 'tags', array(
            'per_page' => max(1, (int) $per_page),
        ));
    }
}

if (!function_exists('themisdb_github_bridge_fetch_milestones')) {
    function themisdb_github_bridge_fetch_milestones($repository, $query = array()) {
        $defaults = array(
            'state' => 'all',
            'sort' => 'due_on',
            'direction' => 'desc',
            'per_page' => 10,
        );

        return themisdb_github_bridge_fetch_repo_json($repository, 'milestones', array_merge($defaults, (array) $query));
    }
}

if (!function_exists('themisdb_github_bridge_fetch_issues')) {
    function themisdb_github_bridge_fetch_issues($repository, $query = array()) {
        $defaults = array(
            'state' => 'all',
            'sort' => 'updated',
            'direction' => 'desc',
            'per_page' => 20,
        );

        return themisdb_github_bridge_fetch_repo_json($repository, 'issues', array_merge($defaults, (array) $query));
    }
}

register_activation_hook(__FILE__, array('ThemisDB_GitHub_Bridge', 'activate'));

/**
 * Ensure plugin assets stay functional regardless of active theme.
 */
function themisdb_github_bridge_register_theme_compat_filters() {
    $style_filters = array(
        'themisdb_architecture_enqueue_frontend_style',
        'themisdb_benchmark_visualizer_enqueue_frontend_style',
        'themisdb_compendium_enqueue_frontend_style',
        'themisdb_docker_downloads_enqueue_frontend_style',
        'themisdb_downloads_enqueue_frontend_style',
        'themisdb_feature_matrix_enqueue_frontend_style',
        'themisdb_formula_enqueue_frontend_style',
        'themisdb_front_slider_enqueue_frontend_style',
        'themisdb_gallery_enqueue_frontend_style',
        'themisdb_order_request_enqueue_frontend_style',
        'themisdb_product_detail_enqueue_frontend_style',
        'themisdb_shopping_cart_enqueue_frontend_style',
        'themisdb_license_portal_enqueue_frontend_style',
        'themisdb_persistent_podcast_player_enqueue_frontend_style',
        'themisdb_query_playground_enqueue_frontend_style',
        'themisdb_release_timeline_enqueue_frontend_style',
        'themisdb_support_portal_enqueue_frontend_style',
        'themisdb_taxonomy_enqueue_frontend_style',
        'themisdb_tco_calculator_enqueue_frontend_style',
        'themisdb_test_dashboard_enqueue_frontend_style',
        'themisdb_wiki_enqueue_frontend_style',
    );

    foreach ($style_filters as $filter_name) {
        add_filter($filter_name, '__return_true', 1000);
    }

    add_filter('themisdb_graph_navigation_enqueue_frontend_script', '__return_true', 1000);
    add_filter('themisdb_front_slider_enqueue_frontend_script', '__return_true', 1000);
}
add_action('plugins_loaded', 'themisdb_github_bridge_register_theme_compat_filters', 5);

add_action('plugins_loaded', function () {
    ThemisDB_GitHub_Bridge::instance();
    ThemisDB_GitHub_Bridge_Dashboard_Widget::init();
    load_plugin_textdomain('themisdb-github-bridge', false, dirname(plugin_basename(__FILE__)) . '/languages');
});
