<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB - Hybrid Database System                                   ║
╠═════════════════════════════════════════════════════════════════════╣
  File:            class-admin.php                                    ║
  Plugin:          themisdb-support-portal                            ║
  Version:         1.0.0                                              ║
╚═════════════════════════════════════════════════════════════════════╝
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin interface for ThemisDB Support Portal.
 * Provides ticket management and settings pages in the WordPress admin.
 */
class ThemisDB_Support_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));

        // AJAX handlers for admin actions
        add_action('wp_ajax_themisdb_support_admin_reply', array($this, 'handle_admin_reply'));
        add_action('wp_ajax_themisdb_support_admin_status', array($this, 'handle_status_change'));
    }

    // -------------------------------------------------------------------------
    // Menu
    // -------------------------------------------------------------------------

    /**
     * Register the admin menu and sub-pages.
     */
    public function add_admin_menu() {
        add_menu_page(
            __('ThemisDB Support', 'themisdb-support-portal'),
            __('ThemisDB Support', 'themisdb-support-portal'),
            'manage_options',
            'themisdb-support',
            array($this, 'tickets_page'),
            'dashicons-sos',
            56
        );

        add_submenu_page(
            'themisdb-support',
            __('Tickets', 'themisdb-support-portal'),
            __('Tickets', 'themisdb-support-portal'),
            'manage_options',
            'themisdb-support',
            array($this, 'tickets_page')
        );

        add_submenu_page(
            'themisdb-support',
            __('Einstellungen', 'themisdb-support-portal'),
            __('Einstellungen', 'themisdb-support-portal'),
            'manage_options',
            'themisdb-support-settings',
            array($this, 'settings_page')
        );
    }

    // -------------------------------------------------------------------------
    // Settings
    // -------------------------------------------------------------------------

    /**
     * Register plugin settings via the WordPress Settings API.
     */
    public function register_settings() {
        register_setting('themisdb_support_settings', 'themisdb_support_redirect_url', array(
            'sanitize_callback' => 'esc_url_raw',
        ));
        register_setting('themisdb_support_settings', 'themisdb_support_email_notifications', array(
            'sanitize_callback' => 'absint',
        ));
        register_setting('themisdb_support_settings', 'themisdb_support_email_from', array(
            'sanitize_callback' => 'sanitize_email',
        ));
        register_setting('themisdb_support_settings', 'themisdb_support_email_from_name', array(
            'sanitize_callback' => 'sanitize_text_field',
        ));
        register_setting('themisdb_support_settings', 'themisdb_support_admin_email', array(
            'sanitize_callback' => 'sanitize_email',
        ));
    }

    // -------------------------------------------------------------------------
    // Pages
    // -------------------------------------------------------------------------

    /**
     * Render the tickets list page (or a single ticket if ?ticket_id is set).
     */
    public function tickets_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Keine Berechtigung', 'themisdb-support-portal'));
        }

        // Single ticket view
        if (!empty($_GET['ticket_id'])) {
            $this->view_ticket_page(intval($_GET['ticket_id']));
            return;
        }

        // Build filter arguments from GET params
        $status   = isset($_GET['status'])   ? sanitize_text_field($_GET['status'])   : '';
        $priority = isset($_GET['priority']) ? sanitize_text_field($_GET['priority']) : '';
        $paged    = isset($_GET['paged'])    ? max(1, intval($_GET['paged']))          : 1;

        $result = ThemisDB_Support_Ticket_Manager::get_tickets(array(
            'status'   => $status,
            'priority' => $priority,
            'per_page' => 20,
            'page'     => $paged,
        ));

        $tickets        = $result['tickets'];
        $total          = $result['total'];
        $total_pages    = ceil($total / 20);
        $status_labels  = ThemisDB_Support_Ticket_Manager::get_status_labels();
        $priority_labels = ThemisDB_Support_Ticket_Manager::get_priority_labels();

        include THEMISDB_SUPPORT_PLUGIN_DIR . 'templates/admin-tickets.php';
    }

    /**
     * Render the single ticket detail view.
     *
     * @param int $ticket_id
     */
    private function view_ticket_page($ticket_id) {
        $ticket   = ThemisDB_Support_Ticket_Manager::get_ticket($ticket_id);
        $messages = ThemisDB_Support_Ticket_Manager::get_messages($ticket_id);

        if (!$ticket) {
            echo '<div class="wrap"><div class="notice notice-error"><p>' . esc_html__('Ticket nicht gefunden.', 'themisdb-support-portal') . '</p></div></div>';
            return;
        }

        $status_labels   = ThemisDB_Support_Ticket_Manager::get_status_labels();
        $priority_labels = ThemisDB_Support_Ticket_Manager::get_priority_labels();

        include THEMISDB_SUPPORT_PLUGIN_DIR . 'templates/admin-ticket-view.php';
    }

    /**
     * Render the settings page.
     */
    public function settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Keine Berechtigung', 'themisdb-support-portal'));
        }

        include THEMISDB_SUPPORT_PLUGIN_DIR . 'templates/admin-settings.php';
    }

    // -------------------------------------------------------------------------
    // AJAX handlers
    // -------------------------------------------------------------------------

    /**
     * Handle admin reply to a ticket.
     */
    public function handle_admin_reply() {
        check_ajax_referer('themisdb_support_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Keine Berechtigung', 'themisdb-support-portal')));
        }

        $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
        $message   = isset($_POST['message'])   ? wp_unslash($_POST['message']) : '';

        if (!$ticket_id || empty(trim($message))) {
            wp_send_json_error(array('message' => __('Fehlende Parameter', 'themisdb-support-portal')));
        }

        $ticket = ThemisDB_Support_Ticket_Manager::get_ticket($ticket_id);
        if (!$ticket) {
            wp_send_json_error(array('message' => __('Ticket nicht gefunden', 'themisdb-support-portal')));
        }

        $current_user = wp_get_current_user();

        $message_id = ThemisDB_Support_Ticket_Manager::add_message($ticket_id, array(
            'author_name'    => $current_user->display_name,
            'author_email'   => $current_user->user_email,
            'message'        => $message,
            'is_admin_reply' => true,
        ));

        if (!$message_id) {
            wp_send_json_error(array('message' => __('Antwort konnte nicht gespeichert werden', 'themisdb-support-portal')));
        }

        // If ticket was open, move it to in_progress
        if ($ticket['status'] === ThemisDB_Support_Ticket_Manager::STATUS_OPEN) {
            ThemisDB_Support_Ticket_Manager::update_ticket_status($ticket_id, ThemisDB_Support_Ticket_Manager::STATUS_IN_PROGRESS);
        }

        wp_send_json_success(array(
            'message'    => __('Antwort gesendet', 'themisdb-support-portal'),
            'message_id' => $message_id,
        ));
    }

    /**
     * Handle ticket status change.
     */
    public function handle_status_change() {
        check_ajax_referer('themisdb_support_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Keine Berechtigung', 'themisdb-support-portal')));
        }

        $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
        $status    = isset($_POST['status'])    ? sanitize_text_field($_POST['status']) : '';

        if (!$ticket_id || empty($status)) {
            wp_send_json_error(array('message' => __('Fehlende Parameter', 'themisdb-support-portal')));
        }

        $updated = ThemisDB_Support_Ticket_Manager::update_ticket_status($ticket_id, $status);

        if (!$updated) {
            wp_send_json_error(array('message' => __('Status konnte nicht aktualisiert werden', 'themisdb-support-portal')));
        }

        $status_labels = ThemisDB_Support_Ticket_Manager::get_status_labels();

        wp_send_json_success(array(
            'message'      => __('Status aktualisiert', 'themisdb-support-portal'),
            'status'       => $status,
            'status_label' => isset($status_labels[$status]) ? $status_labels[$status] : $status,
        ));
    }
}
