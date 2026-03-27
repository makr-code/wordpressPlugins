<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB - Hybrid Database System                                   ║
╠═════════════════════════════════════════════════════════════════════╣
  File:            themisdb-order-request.php                         ║
  Version:         1.1.0                                              ║
  Last Modified:   2026-03-20 00:00:00                                ║
  Author:          unknown                                            ║
╠═════════════════════════════════════════════════════════════════════╣
  Quality Metrics:                                                    ║
    • Maturity Level:  🟢 PRODUCTION-READY                             ║
    • Quality Score:   100.0/100                                      ║
    • Total Lines:     211                                            ║
    • Open Issues:     TODOs: 0, Stubs: 0                             ║
╠═════════════════════════════════════════════════════════════════════╣
  Revision History:                                                   ║
    • 2a1fb0423  2026-03-03  Merge branch 'develop' into copilot/audit-src-module-docu... ║
    • 9d3ecaa0e  2026-02-28  Add ThemisDB Wiki Integration plugin with documentation i... ║
╠═════════════════════════════════════════════════════════════════════╣
  Status: ✅ Production Ready                                          ║
╚═════════════════════════════════════════════════════════════════════╝
 */


/**
 * Plugin Name: ThemisDB Order Request & Contract Management
 * Plugin URI: https://github.com/makr-code/wordpressPlugins
 * Description: Dialog-basiertes Bestellanfrage-System für ThemisDB mit Vertragsrecht-CRUD, automatischer PDF-Generierung und E-Mail-Versand. Integriert mit epServer für Stammdaten.
 * Version: 1.1.0
 * Author: ThemisDB Team
 * Author URI: https://github.com/makr-code/wordpressPlugins
 * License: MIT
 * Text Domain: themisdb-order-request
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
    add_action('admin_notices', function() {
        echo '<div class="error"><p><strong>ThemisDB Order Request:</strong> Dieses Plugin benötigt PHP 7.4 oder höher. Sie verwenden PHP ' . esc_html(PHP_VERSION) . '</p></div>';
    });
    return;
}

// Plugin constants
define('THEMISDB_ORDER_VERSION', '1.1.0');
define('THEMISDB_ORDER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('THEMISDB_ORDER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('THEMISDB_ORDER_PLUGIN_FILE', __FILE__);

// Load updater class
$themisdb_updater_local = THEMISDB_ORDER_PLUGIN_DIR . 'includes/class-themisdb-plugin-updater.php';
$themisdb_updater_shared = dirname(THEMISDB_ORDER_PLUGIN_DIR) . '/includes/class-themisdb-plugin-updater.php';

if (file_exists($themisdb_updater_local)) {
    require_once $themisdb_updater_local;
} elseif (file_exists($themisdb_updater_shared)) {
    require_once $themisdb_updater_shared;
}

// Initialize automatic updates
if (class_exists('ThemisDB_Plugin_Updater')) {
    new ThemisDB_Plugin_Updater(
        THEMISDB_ORDER_PLUGIN_FILE,
        'themisdb-order-request',
        THEMISDB_ORDER_VERSION
    );
}

// Include required files
require_once THEMISDB_ORDER_PLUGIN_DIR . 'includes/class-database.php';
require_once THEMISDB_ORDER_PLUGIN_DIR . 'includes/class-order-manager.php';
require_once THEMISDB_ORDER_PLUGIN_DIR . 'includes/class-contract-manager.php';
require_once THEMISDB_ORDER_PLUGIN_DIR . 'includes/class-payment-manager.php';
require_once THEMISDB_ORDER_PLUGIN_DIR . 'includes/class-license-manager.php';
require_once THEMISDB_ORDER_PLUGIN_DIR . 'includes/class-support-benefits-manager.php';
require_once THEMISDB_ORDER_PLUGIN_DIR . 'includes/class-support-ticket-manager.php';
require_once THEMISDB_ORDER_PLUGIN_DIR . 'includes/class-license-build-dispatcher.php';
require_once THEMISDB_ORDER_PLUGIN_DIR . 'includes/class-license-api.php';
require_once THEMISDB_ORDER_PLUGIN_DIR . 'includes/class-license-portal.php';
require_once THEMISDB_ORDER_PLUGIN_DIR . 'includes/class-license-renewal.php';
require_once THEMISDB_ORDER_PLUGIN_DIR . 'includes/class-pdf-generator.php';
require_once THEMISDB_ORDER_PLUGIN_DIR . 'includes/class-email-handler.php';
require_once THEMISDB_ORDER_PLUGIN_DIR . 'includes/class-document-template-manager.php';
require_once THEMISDB_ORDER_PLUGIN_DIR . 'includes/class-document-renderer.php';
require_once THEMISDB_ORDER_PLUGIN_DIR . 'includes/class-license-pricing.php';
require_once THEMISDB_ORDER_PLUGIN_DIR . 'includes/class-affiliate-program.php';
require_once THEMISDB_ORDER_PLUGIN_DIR . 'includes/class-b2b-portal.php';
require_once THEMISDB_ORDER_PLUGIN_DIR . 'includes/class-advanced-reporting.php';
require_once THEMISDB_ORDER_PLUGIN_DIR . 'includes/class-epserver-api.php';
require_once THEMISDB_ORDER_PLUGIN_DIR . 'includes/class-error-handler.php';
require_once THEMISDB_ORDER_PLUGIN_DIR . 'includes/class-privacy.php';
require_once THEMISDB_ORDER_PLUGIN_DIR . 'includes/class-woocommerce-bridge.php';
require_once THEMISDB_ORDER_PLUGIN_DIR . 'includes/class-bank-import.php';
require_once THEMISDB_ORDER_PLUGIN_DIR . 'includes/class-admin.php';
require_once THEMISDB_ORDER_PLUGIN_DIR . 'includes/class-admin-dashboard.php';
require_once THEMISDB_ORDER_PLUGIN_DIR . 'includes/class-notification-manager.php';
require_once THEMISDB_ORDER_PLUGIN_DIR . 'includes/class-shortcodes.php';
require_once THEMISDB_ORDER_PLUGIN_DIR . 'includes/class-auth-system.php';

/**
 * Initialize the plugin
 */
function themisdb_order_request_init() {
    // Initialize error logging system
    if (class_exists('ThemisDB_Error_Handler') && method_exists('ThemisDB_Error_Handler', 'init')) {
        ThemisDB_Error_Handler::init();
    }

    // Initialize GDPR/Privacy compliance
    ThemisDB_Privacy::init();

    // Initialize database
    ThemisDB_Order_Database::init();

    // Run DB schema upgrade for existing installations when plugin version changes
    $installed_ver = get_option('themisdb_order_db_version', '0');
    if (version_compare($installed_ver, THEMISDB_ORDER_VERSION, '<')) {
        ThemisDB_Order_Database::create_tables();
        update_option('themisdb_order_db_version', THEMISDB_ORDER_VERSION);
    }
    
    // Initialize admin panel
    if (is_admin()) {
        new ThemisDB_Order_Admin();
        ThemisDB_Admin_Dashboard::init();
        
        // Initialize notifications & alerts system
        ThemisDB_Notification_Manager::init();
    }
    
    // Initialize shortcodes
    new ThemisDB_Order_Shortcodes();

    // Initialize renewal handlers (one-click flow).
    if (class_exists('ThemisDB_License_Renewal')) {
        ThemisDB_License_Renewal::init();
    }

    // Initialize support ticket manager.
    if (class_exists('ThemisDB_Order_Support_Ticket_Manager')) {
        ThemisDB_Order_Support_Ticket_Manager::init();
    }

    // Initialize affiliate program (referral capture + commission tracking).
    if (class_exists('ThemisDB_Affiliate_Program')) {
        ThemisDB_Affiliate_Program::init();
    }

    // Initialize B2B portal services.
    if (class_exists('ThemisDB_B2B_Portal')) {
        ThemisDB_B2B_Portal::init();
    }

    // Initialize advanced reporting pages/shortcode.
    if (class_exists('ThemisDB_Advanced_Reporting')) {
        ThemisDB_Advanced_Reporting::init();
    }

    // Initialize WooCommerce bridge (no-op when WooCommerce is not active)
    new ThemisDB_WooCommerce_Bridge();

    // Initialize license REST API and customer portal
    new ThemisDB_License_API();
    new ThemisDB_License_Portal();

    // Ensure core public pages exist independently of the active theme.
    themisdb_order_ensure_contact_page();

    // Ensure new scheduled jobs are present for already-installed instances.
    themisdb_order_request_ensure_scheduled_events();
    
    // Load text domain for translations
    load_plugin_textdomain('themisdb-order-request', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'themisdb_order_request_init');

/**
 * Ensure recurring cron events exist for operational maintenance.
 */
function themisdb_order_request_ensure_scheduled_events() {
    if (!wp_next_scheduled('themisdb_support_github_status_refresh')) {
        wp_schedule_event(time(), 'hourly', 'themisdb_support_github_status_refresh');
    }
}

/**
 * Ensure a published /contact page exists.
 *
 * This keeps https://.../contact available even when themes change.
 */
function themisdb_order_ensure_contact_page() {
    if (wp_installing()) {
        return;
    }

    $sync_version = '1';
    $sync_option  = 'themisdb_order_contact_page_sync';

    if (get_option($sync_option) === $sync_version) {
        return;
    }

    $contact_page = get_page_by_path('contact', OBJECT, 'page');

    if ($contact_page instanceof WP_Post) {
        if ($contact_page->post_status !== 'publish') {
            wp_update_post(array(
                'ID'          => $contact_page->ID,
                'post_status' => 'publish',
            ));
        }

        update_option($sync_option, $sync_version);
        return;
    }

    $contact_candidates = get_posts(array(
        'post_type'        => 'page',
        'name'             => 'contact',
        'post_status'      => array('publish', 'draft', 'pending', 'private', 'future', 'trash'),
        'numberposts'      => 1,
        'suppress_filters' => true,
    ));

    if (!empty($contact_candidates) && $contact_candidates[0] instanceof WP_Post) {
        wp_update_post(array(
            'ID'          => $contact_candidates[0]->ID,
            'post_name'   => 'contact',
            'post_title'  => 'Contact',
            'post_status' => 'publish',
        ));

        update_option($sync_option, $sync_version);
        return;
    }

    $insert_result = wp_insert_post(array(
        'post_type'    => 'page',
        'post_status'  => 'publish',
        'post_title'   => 'Contact',
        'post_name'    => 'contact',
        'post_content' => "<h2>Contact ThemisDB</h2>\n<p>For sales, support, and general questions, please use our support channels or email us directly at <a href=\"mailto:info@themisdb.org\">info@themisdb.org</a>.</p>",
    ), true);

    if (!is_wp_error($insert_result)) {
        update_option($sync_option, $sync_version);
    }
}

/**
 * Activation hook
 */
function themisdb_order_request_activate() {
    // Create database tables
    ThemisDB_Order_Database::create_tables();

    // Ensure public pages are present immediately after activation.
    themisdb_order_ensure_contact_page();
    
    // Set default options
    if (!get_option('themisdb_order_epserver_url')) {
        add_option('themisdb_order_epserver_url', 'https://service.themisdb.org:6734');
    }
    if (!get_option('themisdb_order_epserver_api_key')) {
        add_option('themisdb_order_epserver_api_key', '');
    }
    if (!get_option('themisdb_order_email_from')) {
        add_option('themisdb_order_email_from', get_option('admin_email'));
    }
    if (!get_option('themisdb_order_email_from_name')) {
        add_option('themisdb_order_email_from_name', get_option('blogname'));
    }
    if (!get_option('themisdb_order_pdf_storage')) {
        add_option('themisdb_order_pdf_storage', 'database'); // database or filesystem
    }
    if (!get_option('themisdb_order_legal_compliance')) {
        add_option('themisdb_order_legal_compliance', '1'); // Enable legal compliance checks
    }
    if (!get_option('themisdb_license_api_key')) {
        add_option('themisdb_license_api_key', ''); // Set via Settings → ThemisDB License API
    }
    if (!get_option('themisdb_license_admin_secret')) {
        add_option('themisdb_license_admin_secret', ''); // Optional extra admin secret for admin endpoints
    }
    if (!get_option('themisdb_license_renewal_reminder_days')) {
        add_option('themisdb_license_renewal_reminder_days', '30'); // Days before expiry to send renewal reminder
    }
    if (!get_option('themisdb_license_default_term_days')) {
        add_option('themisdb_license_default_term_days', '365'); // Default renewal term
    }
    if (!get_option('themisdb_license_allow_auto_renewal')) {
        add_option('themisdb_license_allow_auto_renewal', '1'); // Allow auto-renew processing
    }
    if (!get_option('themisdb_affiliate_default_commission_rate')) {
        add_option('themisdb_affiliate_default_commission_rate', '10');
    }
    if (!get_option('themisdb_affiliate_cookie_days')) {
        add_option('themisdb_affiliate_cookie_days', '30');
    }
    if (!get_option('themisdb_b2b_default_invoice_due_days')) {
        add_option('themisdb_b2b_default_invoice_due_days', '30');
    }
    if (!get_option('themisdb_reporting_marketing_spend_json')) {
        add_option('themisdb_reporting_marketing_spend_json', '{}');
    }
    if (!get_option('themisdb_support_github_enabled')) {
        add_option('themisdb_support_github_enabled', '0');
    }
    if (!get_option('themisdb_support_github_token')) {
        add_option('themisdb_support_github_token', '');
    }
    if (!get_option('themisdb_support_github_repository')) {
        add_option('themisdb_support_github_repository', '');
    }
    if (!get_option('themisdb_support_github_labels')) {
        add_option('themisdb_support_github_labels', 'support,themisdb');
    }
    if (!get_option('themisdb_support_github_manual_refresh_cooldown')) {
        add_option('themisdb_support_github_manual_refresh_cooldown', '30');
    }

    // Schedule daily renewal reminder cron job
    if (!wp_next_scheduled('themisdb_license_renewal_check')) {
        wp_schedule_event(time(), 'daily', 'themisdb_license_renewal_check');
    }
    
    // Schedule support benefits monthly reset cron job
    if (!wp_next_scheduled('themisdb_support_benefits_monthly_reset')) {
        wp_schedule_event(time(), 'daily', 'themisdb_support_benefits_monthly_reset');
    }
    
    // Schedule support benefits expiry check (daily)
    if (!wp_next_scheduled('themisdb_support_benefits_expiry_check')) {
        wp_schedule_event(time(), 'daily', 'themisdb_support_benefits_expiry_check');
    }

    // Schedule support ticket GitHub issue status refresh (hourly)
    if (!wp_next_scheduled('themisdb_support_github_status_refresh')) {
        wp_schedule_event(time(), 'hourly', 'themisdb_support_github_status_refresh');
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'themisdb_order_request_activate');

/**
 * Deactivation hook
 */
function themisdb_order_request_deactivate() {
    // Clear the renewal reminder cron job
    $timestamp = wp_next_scheduled('themisdb_license_renewal_check');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'themisdb_license_renewal_check');
    }
    
    // Clear support benefits cron jobs
    $timestamp = wp_next_scheduled('themisdb_support_benefits_monthly_reset');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'themisdb_support_benefits_monthly_reset');
    }
    
    $timestamp = wp_next_scheduled('themisdb_support_benefits_expiry_check');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'themisdb_support_benefits_expiry_check');
    }

    $timestamp = wp_next_scheduled('themisdb_support_github_status_refresh');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'themisdb_support_github_status_refresh');
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'themisdb_order_request_deactivate');

/**
 * Renewal reminder cron hook
 */
add_action('themisdb_license_renewal_check', 'themisdb_run_license_renewal_check');
function themisdb_run_license_renewal_check() {
    if (class_exists('ThemisDB_License_Renewal')) {
        ThemisDB_License_Renewal::send_renewal_reminders();
    }
}

/**
 * Support benefits monthly reset cron hook
 */
add_action('themisdb_support_benefits_monthly_reset', 'themisdb_run_support_benefits_monthly_reset');
function themisdb_run_support_benefits_monthly_reset() {
    if (class_exists('ThemisDB_Support_Benefits_Manager')) {
        ThemisDB_Support_Benefits_Manager::reset_monthly_counts();
    }
}

/**
 * Support benefits expiry check cron hook
 */
add_action('themisdb_support_benefits_expiry_check', 'themisdb_run_support_benefits_expiry_check');
function themisdb_run_support_benefits_expiry_check() {
    if (class_exists('ThemisDB_Support_Benefits_Manager')) {
        ThemisDB_Support_Benefits_Manager::expire_expired_licenses();
    }
}

/**
 * Support ticket GitHub issue status refresh cron hook.
 */
add_action('themisdb_support_github_status_refresh', 'themisdb_run_support_github_status_refresh');
function themisdb_run_support_github_status_refresh() {
    $last_run = time();

    if (!class_exists('ThemisDB_Order_Support_Ticket_Manager')) {
        update_option('themisdb_support_github_status_refresh_last_run', $last_run, false);
        update_option('themisdb_support_github_status_refresh_last_result', array(
            'processed' => 0,
            'updated' => 0,
            'failed' => 1,
            'skipped' => 0,
            'message' => 'support_ticket_manager_missing',
        ), false);
        return;
    }

    $result = ThemisDB_Order_Support_Ticket_Manager::refresh_github_issue_statuses_batch(array(
        'limit' => 30,
        'ticket_statuses' => array('open', 'in_progress'),
    ));

    update_option('themisdb_support_github_status_refresh_last_run', $last_run, false);
    update_option('themisdb_support_github_status_refresh_last_result', array(
        'processed' => isset($result['processed']) ? absint($result['processed']) : 0,
        'updated' => isset($result['updated']) ? absint($result['updated']) : 0,
        'failed' => isset($result['failed']) ? absint($result['failed']) : 0,
        'skipped' => isset($result['skipped']) ? absint($result['skipped']) : 0,
        'message' => isset($result['message']) ? sanitize_text_field((string) $result['message']) : '',
    ), false);
}

/**
 * Enqueue frontend scripts and styles
 */
function themisdb_order_request_enqueue_scripts() {
    // Theme-first presentation: ThemisDB themes own frontend visuals.
    $theme_controls_presentation =
        wp_style_is('themisdb-style', 'enqueued') ||
        wp_style_is('themisdb-style', 'registered') ||
        wp_style_is('lis-a-style', 'enqueued') ||
        wp_style_is('lis-a-style', 'registered');

    $should_enqueue_plugin_style = apply_filters(
        'themisdb_order_request_enqueue_frontend_style',
        ! $theme_controls_presentation
    );

    if ($should_enqueue_plugin_style) {
        wp_enqueue_style('themisdb-order-request-style', THEMISDB_ORDER_PLUGIN_URL . 'assets/css/order-request.css', array(), THEMISDB_ORDER_VERSION);
    }

    wp_enqueue_script('themisdb-order-request-script', THEMISDB_ORDER_PLUGIN_URL . 'assets/js/order-request.js', array('jquery'), THEMISDB_ORDER_VERSION, true);

    // Product detail page assets (loaded globally; the shortcode guard via wp_enqueue is idempotent).
    wp_register_style('themisdb-product-detail-style', THEMISDB_ORDER_PLUGIN_URL . 'assets/css/product-detail.css', array(), THEMISDB_ORDER_VERSION);
    wp_register_script('themisdb-product-selector', THEMISDB_ORDER_PLUGIN_URL . 'assets/js/product-selector.js', array('jquery'), THEMISDB_ORDER_VERSION, true);

        // Shopping cart assets – Phase 2.3 (enqueued lazily by the shortcode).
        wp_register_style('themisdb-shopping-cart-style', THEMISDB_ORDER_PLUGIN_URL . 'assets/css/shopping-cart.css', array(), THEMISDB_ORDER_VERSION);
        wp_register_script('themisdb-shopping-cart', THEMISDB_ORDER_PLUGIN_URL . 'assets/js/shopping-cart.js', array('jquery'), THEMISDB_ORDER_VERSION, true);
    
    wp_localize_script('themisdb-order-request-script', 'themisdbOrder', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('themisdb_order_nonce'),
        'strings' => array(
            'loading' => __('Lädt...', 'themisdb-order-request'),
            'error' => __('Ein Fehler ist aufgetreten', 'themisdb-order-request'),
            'success' => __('Erfolgreich gespeichert', 'themisdb-order-request'),
        )
    ));
}
add_action('wp_enqueue_scripts', 'themisdb_order_request_enqueue_scripts');

/**
 * Enqueue admin scripts and styles
 */
function themisdb_order_request_admin_enqueue_scripts($hook) {
    // Only load on our plugin pages
    if (strpos($hook, 'themisdb-order') === false && strpos($hook, 'themisdb-bank') === false
        && strpos($hook, 'themisdb-license') === false && strpos($hook, 'themisdb-payments') === false
        && strpos($hook, 'themisdb-contracts') === false && strpos($hook, 'themisdb-email') === false) {
        return;
    }
    
    wp_enqueue_style('themisdb-order-admin-style', THEMISDB_ORDER_PLUGIN_URL . 'assets/css/admin.css', array(), THEMISDB_ORDER_VERSION);
    wp_enqueue_script('themisdb-order-admin-script', THEMISDB_ORDER_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), THEMISDB_ORDER_VERSION, true);
}
add_action('admin_enqueue_scripts', 'themisdb_order_request_admin_enqueue_scripts');
