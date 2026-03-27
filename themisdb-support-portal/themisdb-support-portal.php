<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB - Hybrid Database System                                   ║
╠═════════════════════════════════════════════════════════════════════╣
  File:            themisdb-support-portal.php                        ║
  Version:         1.0.0                                              ║
  Last Modified:   2026-03-15                                         ║
  Author:          ThemisDB Team                                      ║
╠═════════════════════════════════════════════════════════════════════╣
  Quality Metrics:                                                    ║
    • Maturity Level:  🟢 PRODUCTION-READY                             ║
    • Quality Score:   100.0/100                                      ║
    • Open Issues:     TODOs: 0, Stubs: 0                             ║
╠═════════════════════════════════════════════════════════════════════╣
  Status: ✅ Production Ready                                          ║
╚═════════════════════════════════════════════════════════════════════╝
 */

/**
 * Plugin Name: ThemisDB Support Portal
 * Plugin URI: https://github.com/makr-code/wordpressPlugins
 * Description: Exklusives Support-Portal für lizensierte ThemisDB-Kunden. Zugang nur mit gültiger Lizenzdatei. Ticket-System für Kundensupport.
 * Version: 1.0.0
 * Author: ThemisDB Team
 * Author URI: https://github.com/makr-code/wordpressPlugins
 * License: MIT
 * Text Domain: themisdb-support-portal
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check PHP version
if (version_compare(PHP_VERSION, '7.4', '<')) {
    add_action('admin_notices', function () {
        echo '<div class="error"><p><strong>ThemisDB Support Portal:</strong> Dieses Plugin benötigt PHP 7.4 oder höher. Sie verwenden PHP ' . esc_html(PHP_VERSION) . '</p></div>';
    });
    return;
}

// Plugin constants
define('THEMISDB_SUPPORT_VERSION', '1.0.0');
define('THEMISDB_SUPPORT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('THEMISDB_SUPPORT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('THEMISDB_SUPPORT_PLUGIN_FILE', __FILE__);

// Load shared updater class (checks plugin directory and parent directory)
$themisdb_updater_local  = THEMISDB_SUPPORT_PLUGIN_DIR . 'includes/class-themisdb-plugin-updater.php';
$themisdb_updater_shared = dirname(THEMISDB_SUPPORT_PLUGIN_DIR) . '/includes/class-themisdb-plugin-updater.php';

if (file_exists($themisdb_updater_local)) {
    require_once $themisdb_updater_local;
} elseif (file_exists($themisdb_updater_shared)) {
    require_once $themisdb_updater_shared;
}

if (class_exists('ThemisDB_Plugin_Updater')) {
    new ThemisDB_Plugin_Updater(
        THEMISDB_SUPPORT_PLUGIN_FILE,
        'themisdb-support-portal',
        THEMISDB_SUPPORT_VERSION
    );
}

// Include required files
require_once THEMISDB_SUPPORT_PLUGIN_DIR . 'includes/class-database.php';
require_once THEMISDB_SUPPORT_PLUGIN_DIR . 'includes/class-license-auth.php';
require_once THEMISDB_SUPPORT_PLUGIN_DIR . 'includes/class-ticket-manager.php';
require_once THEMISDB_SUPPORT_PLUGIN_DIR . 'includes/class-admin.php';
require_once THEMISDB_SUPPORT_PLUGIN_DIR . 'includes/class-shortcodes.php';

/**
 * Initialize the plugin on plugins_loaded so all other plugins (e.g.,
 * themisdb-order-request) are already loaded and their classes available.
 */
function themisdb_support_portal_init() {
    ThemisDB_Support_Database::init();

    if (is_admin()) {
        new ThemisDB_Support_Admin();
    }

    new ThemisDB_Support_License_Auth();
    new ThemisDB_Support_Shortcodes();

    if (is_admin()) {
        themisdb_support_portal_maybe_warn_ticket_manager_conflict();
    }

    load_plugin_textdomain('themisdb-support-portal', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'themisdb_support_portal_init');

/**
 * Warn admins when a legacy support ticket manager class is present
 * from a different file/plugin and could indicate mixed deployments.
 */
function themisdb_support_portal_maybe_warn_ticket_manager_conflict() {
    if (!class_exists('ThemisDB_Support_Ticket_Manager') || !class_exists('ThemisDB_SupportPortal_Ticket_Manager')) {
        return;
    }

    // Show warning only for critical legacy state: old class is loaded but
    // the renamed order-plugin class is missing (likely outdated deployment).
    if (class_exists('ThemisDB_Order_Support_Ticket_Manager')) {
        return;
    }

    try {
        $legacy_ref = new ReflectionClass('ThemisDB_Support_Ticket_Manager');
        $portal_ref = new ReflectionClass('ThemisDB_SupportPortal_Ticket_Manager');
    } catch (ReflectionException $e) {
        return;
    }

    $legacy_file = wp_normalize_path((string) $legacy_ref->getFileName());
    $portal_file = wp_normalize_path((string) $portal_ref->getFileName());

    if ($legacy_file === '' || $portal_file === '' || $legacy_file === $portal_file) {
        return;
    }

    add_action('admin_notices', function () use ($legacy_file) {
        if (!current_user_can('manage_options')) {
            return;
        }

        echo '<div class="notice notice-warning"><p>'
            . esc_html__('ThemisDB Support Portal detected an outdated Order plugin ticket class. Please deploy the latest themisdb-order-request version (with ThemisDB_Order_Support_Ticket_Manager) to prevent class conflicts.', 'themisdb-support-portal')
            . '<br><code>' . esc_html($legacy_file) . '</code>'
            . '</p></div>';
    });
}

/**
 * Activation hook – create DB tables and set defaults.
 */
function themisdb_support_portal_activate() {
    ThemisDB_Support_Database::create_tables();

    if (!get_option('themisdb_support_redirect_url')) {
        add_option('themisdb_support_redirect_url', home_url('/'));
    }
    if (!get_option('themisdb_support_email_notifications')) {
        add_option('themisdb_support_email_notifications', '1');
    }
    if (!get_option('themisdb_support_status_email_notifications')) {
        add_option('themisdb_support_status_email_notifications', '1');
    }
    if (!get_option('themisdb_support_assignee_email_notifications')) {
        add_option('themisdb_support_assignee_email_notifications', '1');
    }
    if (!get_option('themisdb_support_email_from')) {
        add_option('themisdb_support_email_from', get_option('admin_email'));
    }
    if (!get_option('themisdb_support_email_from_name')) {
        add_option('themisdb_support_email_from_name', get_option('blogname'));
    }
    if (!get_option('themisdb_support_admin_email')) {
        add_option('themisdb_support_admin_email', get_option('admin_email'));
    }
    if (!get_option('themisdb_support_default_assignee_user_id')) {
        add_option('themisdb_support_default_assignee_user_id', 0);
    }

    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'themisdb_support_portal_activate');

/**
 * Deactivation hook.
 */
function themisdb_support_portal_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'themisdb_support_portal_deactivate');

/**
 * Enqueue frontend assets (only on pages that need them).
 */
function themisdb_support_enqueue_scripts() {
    global $post;

    if (!is_a($post, 'WP_Post') || (!has_shortcode($post->post_content, 'themisdb_support_portal') && !has_shortcode($post->post_content, 'themisdb_support_login'))) {
        return;
    }

    $theme_controls_presentation =
        wp_style_is('themisdb-style', 'enqueued') ||
        wp_style_is('themisdb-style', 'registered') ||
        wp_style_is('lis-a-style', 'enqueued') ||
        wp_style_is('lis-a-style', 'registered');

    $should_enqueue_frontend_style = apply_filters(
        'themisdb_support_portal_enqueue_frontend_style',
        !$theme_controls_presentation
    );

    if ($should_enqueue_frontend_style) {
    wp_enqueue_style(
        'themisdb-support-portal-style',
        THEMISDB_SUPPORT_PLUGIN_URL . 'assets/css/support-portal.css',
        array(),
        THEMISDB_SUPPORT_VERSION
    );
    }

    wp_enqueue_script(
        'themisdb-support-portal-script',
        THEMISDB_SUPPORT_PLUGIN_URL . 'assets/js/support-portal.js',
        array('jquery'),
        THEMISDB_SUPPORT_VERSION,
        true
    );

    wp_localize_script('themisdb-support-portal-script', 'themisdbSupport', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('themisdb_support_nonce'),
        'strings' => array(
            'loading'               => __('Lädt...', 'themisdb-support-portal'),
            'error'                 => __('Ein Fehler ist aufgetreten', 'themisdb-support-portal'),
            'success'               => __('Erfolgreich gespeichert', 'themisdb-support-portal'),
            'verifying'             => __('Lizenz wird verifiziert...', 'themisdb-support-portal'),
            'submitting'            => __('Ticket wird übermittelt...', 'themisdb-support-portal'),
            'select_file'           => __('Bitte wählen Sie eine Lizenzdatei aus', 'themisdb-support-portal'),
            'auth_with_license'     => __('Mit Lizenz anmelden', 'themisdb-support-portal'),
            'submit_ticket'         => __('Ticket senden', 'themisdb-support-portal'),
            'fill_required_fields'  => __('Bitte füllen Sie Betreff und Nachricht aus.', 'themisdb-support-portal'),
        ),
    ));
}
add_action('wp_enqueue_scripts', 'themisdb_support_enqueue_scripts');

/**
 * Enqueue admin assets (only on this plugin's pages).
 */
function themisdb_support_admin_enqueue_scripts($hook) {
    if (strpos($hook, 'themisdb-support') === false) {
        return;
    }

    wp_enqueue_style(
        'themisdb-support-admin-style',
        THEMISDB_SUPPORT_PLUGIN_URL . 'assets/css/admin.css',
        array(),
        THEMISDB_SUPPORT_VERSION
    );

    wp_enqueue_script(
        'themisdb-support-admin-script',
        THEMISDB_SUPPORT_PLUGIN_URL . 'assets/js/admin.js',
        array('jquery'),
        THEMISDB_SUPPORT_VERSION,
        true
    );

    wp_localize_script('themisdb-support-admin-script', 'themisdbSupportAdmin', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('themisdb_support_admin_nonce'),
        'strings' => array(
            'replying'         => __('Antwort wird gesendet...', 'themisdb-support-portal'),
            'send_reply'       => __('Antwort senden', 'themisdb-support-portal'),
            'confirm_close'    => __('Ticket wirklich schließen?', 'themisdb-support-portal'),
            'saving_assignment' => __('Speichert...', 'themisdb-support-portal'),
            'save_assignment'   => __('Speichern', 'themisdb-support-portal'),
            'processing_bulk'   => __('Bulk-Aktion wird ausgefuehrt...', 'themisdb-support-portal'),
            'ticket_singular'   => __('%d Ticket', 'themisdb-support-portal'),
            'ticket_plural'     => __('%d Tickets', 'themisdb-support-portal'),
            'error'             => __('Ein Fehler ist aufgetreten', 'themisdb-support-portal'),
        ),
    ));
}
add_action('admin_enqueue_scripts', 'themisdb_support_admin_enqueue_scripts');
