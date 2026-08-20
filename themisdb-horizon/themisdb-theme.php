<?php
/**
 * Plugin Name: ThemisDB Theme Shortcodes
 * Plugin URI: https://github.com/makr-code/wordpressPlugins
 * Update URI: https://github.com/makr-code/wordpressPlugins
 * Description: Stellt ThemisDB-Theme-Shortcodes (Sections, FAQ, Timeline, State-Grid, Kontakt) theme-neutral bereit.
 * Version: 1.0.1
 * Author: makr-code
 * Author URI: https://github.com/makr-code/wordpressPlugins
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: themisdb-theme
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Nicht laden wenn themisdb-theme bereits als aktives Theme läuft (functions.php wird dann via Theme-Bootstrap geladen)
if ( get_stylesheet() === 'themisdb-theme' || get_template() === 'themisdb-theme' ) {
	return;
}

// Konstanten mit Plugin-Pfad vorbelegen – functions.php-define() wird dann ignoriert
if ( ! defined( 'THEMISDB_THEME_VERSION' ) ) {
	define( 'THEMISDB_THEME_VERSION', '1.0.0' );
}
if ( ! defined( 'THEMISDB_THEME_FILE' ) ) {
	define( 'THEMISDB_THEME_FILE', __FILE__ );
}
if ( ! defined( 'THEMISDB_THEME_DIR' ) ) {
	define( 'THEMISDB_THEME_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'THEMISDB_THEME_URI' ) ) {
	define( 'THEMISDB_THEME_URI', plugin_dir_url( __FILE__ ) );
}

// Load updater class (prefer shared copy for uniform behavior across plugins).
if (!class_exists('ThemisDB_Plugin_Updater')) {
	$themisdb_updater_shared = dirname(THEMISDB_THEME_DIR) . '/includes/class-themisdb-plugin-updater.php';
	$themisdb_updater_local = THEMISDB_THEME_DIR . 'includes/class-themisdb-plugin-updater.php';

	if (file_exists($themisdb_updater_shared)) {
		require_once $themisdb_updater_shared;
	} elseif (file_exists($themisdb_updater_local)) {
		require_once $themisdb_updater_local;
	}
}

// Initialize automatic updates
if (class_exists('ThemisDB_Plugin_Updater')) {
	new ThemisDB_Plugin_Updater(
		THEMISDB_THEME_FILE,
		'themisdb-theme',
		THEMISDB_THEME_VERSION
	);
}

require_once plugin_dir_path( __FILE__ ) . 'functions.php';


