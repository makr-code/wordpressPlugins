<?php
/**
 * Plugin Name: ThemisDB Engagement Tracker
 * Plugin URI: https://github.com/makr-code/wordpressPlugins
 * Update URI: https://github.com/makr-code/wordpressPlugins
 * Description: Tracks post views, read completions and podcast plays. Exposes an engagement bonus via filter so any theme can incorporate runtime signals into its relevance score.
 * Version: 1.0.2
 * Author: makr-code
 * Author URI: https://github.com/makr-code/wordpressPlugins
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: themisdb-engagement-tracker
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'TDET_VERSION', '1.0.0' );
define( 'TDET_FILE', __FILE__ );
define( 'TDET_DIR', plugin_dir_path( __FILE__ ) );
define( 'TDET_URL', plugin_dir_url( __FILE__ ) );

// Load updater class (prefer shared copy for uniform behavior across plugins).
if (!class_exists('ThemisDB_Plugin_Updater')) {
    $themisdb_updater_shared = dirname(TDET_DIR) . '/includes/class-themisdb-plugin-updater.php';
    $themisdb_updater_local = TDET_DIR . 'includes/class-themisdb-plugin-updater.php';

    if (file_exists($themisdb_updater_shared)) {
        require_once $themisdb_updater_shared;
    } elseif (file_exists($themisdb_updater_local)) {
        require_once $themisdb_updater_local;
    }
}

// Initialize automatic updates
if (class_exists('ThemisDB_Plugin_Updater')) {
    new ThemisDB_Plugin_Updater(
        TDET_FILE,
        'themisdb-engagement-tracker',
        TDET_VERSION
    );
}

require_once TDET_DIR . 'includes/class-tracker.php';
require_once TDET_DIR . 'includes/class-score.php';
require_once TDET_DIR . 'includes/class-admin.php';
require_once TDET_DIR . 'includes/class-dashboard-widget.php';

add_action( 'plugins_loaded', function () {
    TDET_Tracker::get_instance();
    TDET_Admin::get_instance();
    TDET_Dashboard_Widget::init();
} );

/**
 * Expose the stored engagement bonus via the generic filter hook.
 * Any theme that applies `themisdb_engagement_bonus_for_post` gets the value
 * without needing to know about this plugin's internals.
 */
add_filter( 'themisdb_engagement_bonus_for_post', function ( int $bonus, int $post_id ): int {
    return TDET_Score::get_bonus( $post_id );
}, 10, 2 );

