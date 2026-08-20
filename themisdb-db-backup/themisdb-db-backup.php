<?php
/**
 * Plugin Name: ThemisDB DB Backup
 * Plugin URI: https://github.com/makr-code/wordpressPlugins
 * Update URI: https://github.com/makr-code/wordpressPlugins
 * Description: Erstellt revisionssichere Datenbank-Backups als ZIP mit Hash-Chain und Integritaetspruefung.
 * Version: 1.0.1
 * Author: ThemisDB Team
 * Author URI: https://github.com/makr-code/wordpressPlugins
 * License: MIT
 * Text Domain: themisdb-db-backup
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

if (version_compare(PHP_VERSION, '7.4', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="error"><p><strong>ThemisDB DB Backup:</strong> Dieses Plugin benoetigt PHP 7.4 oder hoeher.</p></div>';
    });
    return;
}

define('THEMISDB_DB_BACKUP_VERSION', '1.0.1');
define('THEMISDB_DB_BACKUP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('THEMISDB_DB_BACKUP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('THEMISDB_DB_BACKUP_PLUGIN_FILE', __FILE__);
define('THEMISDB_DB_BACKUP_CRON_HOOK', 'themisdb_db_backup_cron_event');

$themisdb_updater_local = THEMISDB_DB_BACKUP_PLUGIN_DIR . 'includes/class-themisdb-plugin-updater.php';
$themisdb_updater_shared = dirname(THEMISDB_DB_BACKUP_PLUGIN_DIR) . '/includes/class-themisdb-plugin-updater.php';

if (file_exists($themisdb_updater_local)) {
    require_once $themisdb_updater_local;
} elseif (file_exists($themisdb_updater_shared)) {
    require_once $themisdb_updater_shared;
}

if (class_exists('ThemisDB_Plugin_Updater')) {
    new ThemisDB_Plugin_Updater(
        THEMISDB_DB_BACKUP_PLUGIN_FILE,
        'themisdb-db-backup',
        THEMISDB_DB_BACKUP_VERSION
    );
}

require_once THEMISDB_DB_BACKUP_PLUGIN_DIR . 'includes/class-backup-service.php';
require_once THEMISDB_DB_BACKUP_PLUGIN_DIR . 'includes/class-admin.php';
require_once THEMISDB_DB_BACKUP_PLUGIN_DIR . 'includes/class-dashboard-widget.php';

function themisdb_db_backup_activate() {
    if (get_option('themisdb_db_backup_schedule') === false) {
        add_option('themisdb_db_backup_schedule', 'daily');
    }

    if (get_option('themisdb_db_backup_auto_enabled') === false) {
        add_option('themisdb_db_backup_auto_enabled', 1);
    }

    if (get_option('themisdb_db_backup_retention') === false) {
        add_option('themisdb_db_backup_retention', 120);
    }

    ThemisDB_DB_Backup_Service::ensure_storage();
    themisdb_db_backup_refresh_schedule();
}
register_activation_hook(__FILE__, 'themisdb_db_backup_activate');

function themisdb_db_backup_deactivate() {
    wp_clear_scheduled_hook(THEMISDB_DB_BACKUP_CRON_HOOK);
}
register_deactivation_hook(__FILE__, 'themisdb_db_backup_deactivate');

function themisdb_db_backup_refresh_schedule() {
    wp_clear_scheduled_hook(THEMISDB_DB_BACKUP_CRON_HOOK);

    $enabled = (int) get_option('themisdb_db_backup_auto_enabled', 1) === 1;
    if (!$enabled) {
        return;
    }

    $schedule = (string) get_option('themisdb_db_backup_schedule', 'daily');
    $allowed = array('hourly', 'twicedaily', 'daily');

    if (!in_array($schedule, $allowed, true)) {
        $schedule = 'daily';
    }

    if (!wp_next_scheduled(THEMISDB_DB_BACKUP_CRON_HOOK)) {
        wp_schedule_event(time() + 60, $schedule, THEMISDB_DB_BACKUP_CRON_HOOK);
    }
}

add_action(THEMISDB_DB_BACKUP_CRON_HOOK, function() {
    $result = ThemisDB_DB_Backup_Service::create_backup('cron');
    if (is_wp_error($result)) {
        set_transient('themisdb_db_backup_last_error', $result->get_error_message(), 12 * HOUR_IN_SECONDS);
    }
});

function themisdb_db_backup_init() {
    if (is_admin()) {
        new ThemisDB_DB_Backup_Admin();
        ThemisDB_DB_Backup_Dashboard_Widget::init();
    }

    load_plugin_textdomain('themisdb-db-backup', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'themisdb_db_backup_init');
