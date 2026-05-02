<?php
/**
 * Plugin Name: ThemisDB Engagement Tracker
 * Plugin URI:  https://themisdb.de
 * Description: Tracks post views, read completions and podcast plays. Exposes an engagement bonus via filter so any theme can incorporate runtime signals into its relevance score.
 * Version:     1.0.0
 * Author:      ThemisDB
 * Text Domain: themisdb-engagement-tracker
 * License:     GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'TDET_VERSION', '1.0.0' );
define( 'TDET_DIR', plugin_dir_path( __FILE__ ) );
define( 'TDET_URL', plugin_dir_url( __FILE__ ) );

require_once TDET_DIR . 'includes/class-tracker.php';
require_once TDET_DIR . 'includes/class-score.php';
require_once TDET_DIR . 'includes/class-admin.php';

add_action( 'plugins_loaded', function () {
    TDET_Tracker::get_instance();
    TDET_Admin::get_instance();
} );

/**
 * Expose the stored engagement bonus via the generic filter hook.
 * Any theme that applies `themisdb_engagement_bonus_for_post` gets the value
 * without needing to know about this plugin's internals.
 */
add_filter( 'themisdb_engagement_bonus_for_post', function ( int $bonus, int $post_id ): int {
    return TDET_Score::get_bonus( $post_id );
}, 10, 2 );
