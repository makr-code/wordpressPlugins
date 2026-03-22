<?php
/**
 * Plugin Name: ThemisDB GitHub Bridge
 * Plugin URI: https://github.com/makr-code/wordpressPlugins
 * Description: Zentrale GitHub-Kommunikation fuer ThemisDB Order Request und ThemisDB Support Portal. Erstellt Issues automatisiert aus Tickets.
 * Version: 1.0.0
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

define('THEMISDB_GITHUB_BRIDGE_VERSION', '1.0.0');
define('THEMISDB_GITHUB_BRIDGE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('THEMISDB_GITHUB_BRIDGE_PLUGIN_FILE', __FILE__);

$themisdb_updater_local = THEMISDB_GITHUB_BRIDGE_PLUGIN_DIR . 'includes/class-themisdb-plugin-updater.php';
$themisdb_updater_shared = dirname(THEMISDB_GITHUB_BRIDGE_PLUGIN_DIR) . '/includes/class-themisdb-plugin-updater.php';

if (file_exists($themisdb_updater_local)) {
    require_once $themisdb_updater_local;
} elseif (file_exists($themisdb_updater_shared)) {
    require_once $themisdb_updater_shared;
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

register_activation_hook(__FILE__, array('ThemisDB_GitHub_Bridge', 'activate'));

add_action('plugins_loaded', function () {
    ThemisDB_GitHub_Bridge::instance();
    load_plugin_textdomain('themisdb-github-bridge', false, dirname(plugin_basename(__FILE__)) . '/languages');
});
