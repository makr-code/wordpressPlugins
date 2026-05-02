<?php
/**
 * Plugin Name: ThemisDB Theme Shortcodes
 * Plugin URI:  https://github.com/makr-code/wordpressPlugins/tree/main/themisdb-theme
 * Description: Stellt ThemisDB-Theme-Shortcodes (Sections, FAQ, Timeline, State-Grid, Kontakt) theme-neutral bereit.
 * Version:     1.0.0
 * Author:      ThemisDB Team
 * License:     MIT
 * Text Domain: themisdb-theme
 * Update URI:  https://github.com/makr-code/wordpressPlugins/tree/main/themisdb-theme
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
if ( ! defined( 'THEMISDB_THEME_DIR' ) ) {
	define( 'THEMISDB_THEME_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'THEMISDB_THEME_URI' ) ) {
	define( 'THEMISDB_THEME_URI', plugin_dir_url( __FILE__ ) );
}

require_once plugin_dir_path( __FILE__ ) . 'functions.php';
