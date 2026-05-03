<?php
/**
 * Plugin Name:  ThemisDB Quality Meter
 * Plugin URI:   https://themisdb.de
 * Description:  KI-gestützte Qualitätsbewertung mit interaktivem Benutzer-Feedback-Slider.
 *               Themenunabhängig, beliebig viele Metriken (Qualität, Impact, Lesbarkeit, Audio …).
 *               Scores werden extern per REST API geschrieben (Import-Pipeline, KI-Tools).
 * Version:      1.0.0
 * Requires PHP: 8.1
 * Author:       ThemisDB
 * License:      GPL-2.0-or-later
 * Text Domain:  aqm
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'AQM_VERSION',   '1.0.0' );
define( 'AQM_DIR',       plugin_dir_path( __FILE__ ) );
define( 'AQM_URL',       plugin_dir_url( __FILE__ ) );
define( 'AQM_ASSETS',    AQM_URL . 'assets/' );

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
