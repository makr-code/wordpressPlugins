<?php
/**
 * Plugin Name: ThemisDB Quality Meter
 * Plugin URI: https://github.com/makr-code/wordpressPlugins
 * Update URI: https://github.com/makr-code/wordpressPlugins
 * Description: KI-gestützte Qualitätsbewertung mit interaktivem Benutzer-Feedback-Slider.
 * Version: 1.0.1
 * Author: makr-code
 * Author URI: https://github.com/makr-code/wordpressPlugins
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: aqm
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'AQM_VERSION',   '1.0.0' );
define( 'AQM_FILE',      __FILE__ );
define( 'AQM_DIR',       plugin_dir_path( __FILE__ ) );
define( 'AQM_URL',       plugin_dir_url( __FILE__ ) );
define( 'AQM_ASSETS',    AQM_URL . 'assets/' );

// Load updater class (prefer shared copy for uniform behavior across plugins).
if (!class_exists('ThemisDB_Plugin_Updater')) {
    $themisdb_updater_shared = dirname(AQM_DIR) . '/includes/class-themisdb-plugin-updater.php';
    $themisdb_updater_local = AQM_DIR . 'includes/class-themisdb-plugin-updater.php';

    if (file_exists($themisdb_updater_shared)) {
        require_once $themisdb_updater_shared;
    } elseif (file_exists($themisdb_updater_local)) {
        require_once $themisdb_updater_local;
    }
}

// Initialize automatic updates
if (class_exists('ThemisDB_Plugin_Updater')) {
    new ThemisDB_Plugin_Updater(
        AQM_FILE,
        'themisdb-quality-meter',
        AQM_VERSION
    );
}

require_once AQM_DIR . 'includes/class-aqm-config.php';
require_once AQM_DIR . 'includes/class-aqm-meta.php';
require_once AQM_DIR . 'includes/class-aqm-rest.php';
require_once AQM_DIR . 'includes/class-aqm-shortcode.php';
require_once AQM_DIR . 'includes/class-aqm-assets.php';

add_action( 'init',          array( 'AQM_Meta',      'register' ) );
add_action( 'rest_api_init', array( 'AQM_Rest',      'register_routes' ) );
add_action( 'init',          array( 'AQM_Shortcode', 'register' ) );
add_action( 'wp_enqueue_scripts', array( 'AQM_Assets', 'enqueue' ) );

// PHP-Session so früh wie möglich starten (frontend + REST).
add_action( 'init', static function (): void {
    if ( session_status() === PHP_SESSION_NONE && ! headers_sent() ) {
        session_start();
    }
}, 1 );

