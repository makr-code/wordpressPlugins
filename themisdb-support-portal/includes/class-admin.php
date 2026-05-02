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
        add_action('wp_ajax_themisdb_support_admin_assign', array($this, 'handle_quick_assign_ajax'));
        add_action('wp_ajax_themisdb_support_admin_bulk', array($this, 'handle_bulk_action_ajax'));

        // Change-Request-Aktionen (approve / reject via POST)
        add_action('admin_post_themisdb_change_review', array($this, 'handle_change_review'));

        // Termination-Request-Aktionen
        add_action('admin_post_themisdb_termination_review', array($this, 'handle_termination_review'));
        add_action('admin_post_themisdb_termination_run_now', array($this, 'handle_termination_run_now'));
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
            __('Incidents', 'themisdb-support-portal'),
            __('Incidents', 'themisdb-support-portal'),
            'manage_options',
            'themisdb-support-incidents',
            array($this, 'incidents_page')
        );

        add_submenu_page(
            'themisdb-support',
            __('Observability', 'themisdb-support-portal'),
            __('Observability', 'themisdb-support-portal'),
            'manage_options',
            'themisdb-support-observability',
            array($this, 'observability_page')
        );

        add_submenu_page(
            'themisdb-support',
            __('Änderungsanträge', 'themisdb-support-portal'),
            __('Änderungsanträge', 'themisdb-support-portal'),
            'manage_options',
            'themisdb-support-change-requests',
            array($this, 'change_requests_page')
        );

        add_submenu_page(
            'themisdb-support',
            __('Kündigungsanträge', 'themisdb-support-portal'),
            __('Kündigungsanträge', 'themisdb-support-portal'),
            'manage_options',
            'themisdb-support-terminations',
            array($this, 'termination_requests_page')
        );

        add_submenu_page(
            'themisdb-support',
            __('Mail-Log', 'themisdb-support-portal'),
            __('Mail-Log', 'themisdb-support-portal'),
            'manage_options',
            'themisdb-support-maillog',
            array($this, 'mail_log_page')
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
        register_setting('themisdb_support_settings', 'themisdb_support_status_email_notifications', array(
            'sanitize_callback' => 'absint',
        ));
        register_setting('themisdb_support_settings', 'themisdb_support_assignee_email_notifications', array(
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
        register_setting('themisdb_support_settings', 'themisdb_support_default_assignee_user_id', array(
            'sanitize_callback' => 'absint',
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

        $this->handle_ticket_get_actions();
        $this->handle_ticket_post_actions();

        // Single ticket view
        if (!empty($_GET['ticket_id'])) {
            $this->view_ticket_page(intval($_GET['ticket_id']));
            return;
        }

        $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'list';
        if (!in_array($active_tab, array('list', 'create'), true)) {
            $active_tab = 'list';
        }

        $notice_message = isset($_GET['support_notice']) ? sanitize_text_field(wp_unslash($_GET['support_notice'])) : '';
        $notice_type = isset($_GET['support_notice_type']) ? sanitize_key($_GET['support_notice_type']) : 'success';
        if (!in_array($notice_type, array('success', 'error', 'warning', 'info'), true)) {
            $notice_type = 'success';
        }

        // Build filter arguments from GET params
        $status           = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $priority         = isset($_GET['priority']) ? sanitize_text_field($_GET['priority']) : '';
        $assignee_user_id = isset($_GET['assignee_user_id']) ? max(0, intval($_GET['assignee_user_id'])) : 0;
        $paged            = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;

        $result = ThemisDB_SupportPortal_Ticket_Manager::get_tickets(array(
            'status'           => $status,
            'priority'         => $priority,
            'assignee_user_id' => $assignee_user_id,
            'per_page'         => 20,
            'page'             => $paged,
        ));

        $tickets        = $result['tickets'];
        $total          = $result['total'];
        $total_pages    = ceil($total / 20);
        $status_labels  = ThemisDB_SupportPortal_Ticket_Manager::get_status_labels();
        $priority_labels = ThemisDB_SupportPortal_Ticket_Manager::get_priority_labels();
        $status_counts = $this->get_ticket_status_counts();
        $quick_filter_counts = $this->get_quick_filter_counts();
        $assignable_agents = $this->get_assignable_agents();

        include THEMISDB_SUPPORT_PLUGIN_DIR . 'templates/admin-tickets.php';
    }

    /**
     * Return assignable WordPress editors/admins for ticket ownership.
     *
     * @return array[]
     */
    private function get_assignable_agents() {
        $users = get_users(array(
            'role__in' => array('administrator', 'editor'),
            'orderby'  => 'display_name',
            'order'    => 'ASC',
            'fields'   => array('ID', 'display_name', 'user_email'),
        ));

        $agents = array();
        foreach ((array) $users as $user) {
            if (!($user instanceof WP_User)) {
                continue;
            }
            if (!user_can($user, 'edit_posts')) {
                continue;
            }

            $agents[] = array(
                'id'    => intval($user->ID),
                'label' => sprintf('%s (%s)', $user->display_name, $user->user_email),
            );
        }

        return $agents;
    }

    /**
     * Process ticket-related admin GET actions.
     */
    private function handle_ticket_get_actions() {
        if (wp_doing_ajax() || strtoupper($_SERVER['REQUEST_METHOD']) !== 'GET') {
            return;
        }

        $action = isset($_GET['themisdb_support_action']) ? sanitize_key(wp_unslash($_GET['themisdb_support_action'])) : '';
        if ($action === '') {
            return;
        }

        if ($action === 'quick_status') {
            $this->handle_quick_status_action();
        }
    }

    /**
     * Handle quick status update from list row actions.
     */
    private function handle_quick_status_action() {
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'themisdb_support_quick_status')) {
            $this->redirect_to_tickets(array(
                'support_notice' => __('Sicherheitspruefung fehlgeschlagen.', 'themisdb-support-portal'),
                'support_notice_type' => 'error',
            ));
        }

        $ticket_id = isset($_GET['ticket_id']) ? intval($_GET['ticket_id']) : 0;
        $status = isset($_GET['target_status']) ? sanitize_key(wp_unslash($_GET['target_status'])) : '';

        $allowed_status = array(
            ThemisDB_SupportPortal_Ticket_Manager::STATUS_OPEN,
            ThemisDB_SupportPortal_Ticket_Manager::STATUS_IN_PROGRESS,
            ThemisDB_SupportPortal_Ticket_Manager::STATUS_RESOLVED,
            ThemisDB_SupportPortal_Ticket_Manager::STATUS_CLOSED,
        );

        $list_state_args = $this->get_list_state_args_from_get();

        if ($ticket_id <= 0 || !in_array($status, $allowed_status, true)) {
            $this->redirect_to_tickets(array_merge($list_state_args, array(
                'support_notice' => __('Ungueltige Schnellaktion.', 'themisdb-support-portal'),
                'support_notice_type' => 'error',
            )));
        }

        $updated = ThemisDB_SupportPortal_Ticket_Manager::update_ticket_status($ticket_id, $status);
        $this->redirect_to_tickets(array_merge($list_state_args, array(
            'support_notice' => $updated
                ? __('Ticket-Status aktualisiert.', 'themisdb-support-portal')
                : __('Status konnte nicht aktualisiert werden.', 'themisdb-support-portal'),
            'support_notice_type' => $updated ? 'success' : 'error',
        )));
    }

    /**
     * Get ticket counts per status for list subtabs.
     *
     * @return array
     */
    private function get_ticket_status_counts() {
        global $wpdb;

        $table = $wpdb->prefix . 'themisdb_support_tickets';
        $rows = $wpdb->get_results("SELECT status, COUNT(*) AS count FROM $table GROUP BY status", ARRAY_A);

        $counts = array(
            ThemisDB_SupportPortal_Ticket_Manager::STATUS_OPEN => 0,
            ThemisDB_SupportPortal_Ticket_Manager::STATUS_IN_PROGRESS => 0,
            ThemisDB_SupportPortal_Ticket_Manager::STATUS_RESOLVED => 0,
            ThemisDB_SupportPortal_Ticket_Manager::STATUS_CLOSED => 0,
        );

        foreach ((array) $rows as $row) {
            $status = isset($row['status']) ? sanitize_key($row['status']) : '';
            if ($status !== '' && isset($counts[$status])) {
                $counts[$status] = intval($row['count']);
            }
        }

        return $counts;
    }

    /**
     * Get counts for quick-filter combinations.
     *
     * @return array
     */
    private function get_quick_filter_counts() {
        global $wpdb;

        $table = $wpdb->prefix . 'themisdb_support_tickets';
        
        return array(
            'open_high' => intval($wpdb->get_var(
                "SELECT COUNT(*) FROM $table WHERE status = 'open' AND priority = 'high'"
            )),
            'open_urgent' => intval($wpdb->get_var(
                "SELECT COUNT(*) FROM $table WHERE status = 'open' AND priority = 'urgent'"
            )),
            'progress_high' => intval($wpdb->get_var(
                "SELECT COUNT(*) FROM $table WHERE status = 'in_progress' AND priority = 'high'"
            )),
        );
    }

    /**
     * Build status and total count summary for admin list UI refreshes.
     *
     * @return array
     */
    private function get_ticket_count_summary() {
        $status_counts = $this->get_ticket_status_counts();
        $quick_filter_counts = $this->get_quick_filter_counts();

        return array(
            'status_counts' => $status_counts,
            'quick_filter_counts' => $quick_filter_counts,
            'total_count' => array_sum(array_map('intval', $status_counts)),
        );
    }

    /**
     * Build list-state args from current GET query.
     *
     * @return array
     */
    private function get_list_state_args_from_get() {
        $args = array(
            'tab' => 'list',
        );

        $status = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : '';
        $priority = isset($_GET['priority']) ? sanitize_key(wp_unslash($_GET['priority'])) : '';
        $assignee_user_id = isset($_GET['assignee_user_id']) ? max(0, intval($_GET['assignee_user_id'])) : 0;
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;

        if ($status !== '') {
            $args['status'] = $status;
        }

        if ($priority !== '') {
            $args['priority'] = $priority;
        }

        if ($assignee_user_id > 0) {
            $args['assignee_user_id'] = $assignee_user_id;
        }

        if ($paged > 1) {
            $args['paged'] = $paged;
        }

        return $args;
    }

    /**
     * Process ticket-related admin POST actions.
     */
    private function handle_ticket_post_actions() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || wp_doing_ajax()) {
            return;
        }

        $action = isset($_POST['themisdb_support_action']) ? sanitize_key($_POST['themisdb_support_action']) : '';
        if ($action === '') {
            return;
        }

        switch ($action) {
            case 'create_ticket':
                $this->handle_create_ticket_action();
                break;
            case 'bulk_tickets':
                $this->handle_bulk_ticket_action();
                break;
            case 'quick_assign_ticket':
                $this->handle_quick_assign_ticket_action();
                break;
            case 'update_ticket':
                $this->handle_update_ticket_action();
                break;
            case 'delete_ticket':
                $this->handle_delete_ticket_action();
                break;
        }
    }

    /**
     * Handle create ticket form.
     */
    private function handle_create_ticket_action() {
        check_admin_referer('themisdb_support_create_ticket', 'themisdb_support_nonce');

        $data = array(
            'subject' => isset($_POST['subject']) ? sanitize_text_field(wp_unslash($_POST['subject'])) : '',
            'message' => isset($_POST['message']) ? wp_kses_post(wp_unslash($_POST['message'])) : '',
            'priority' => isset($_POST['priority']) ? sanitize_key($_POST['priority']) : ThemisDB_SupportPortal_Ticket_Manager::PRIORITY_NORMAL,
            'customer_name' => isset($_POST['customer_name']) ? sanitize_text_field(wp_unslash($_POST['customer_name'])) : '',
            'customer_email' => isset($_POST['customer_email']) ? sanitize_email(wp_unslash($_POST['customer_email'])) : '',
            'customer_company' => isset($_POST['customer_company']) ? sanitize_text_field(wp_unslash($_POST['customer_company'])) : '',
            'license_key' => isset($_POST['license_key']) ? sanitize_text_field(wp_unslash($_POST['license_key'])) : '',
            'user_id' => isset($_POST['user_id']) ? intval($_POST['user_id']) : 0,
            'assignee_user_id' => isset($_POST['assignee_user_id']) ? intval($_POST['assignee_user_id']) : 0,
        );

        if ($data['subject'] === '' || trim(wp_strip_all_tags($data['message'])) === '' || !is_email($data['customer_email'])) {
            $this->redirect_to_tickets(array(
                'tab' => 'create',
                'support_notice' => __('Bitte Betreff, gueltige E-Mail und Nachricht angeben.', 'themisdb-support-portal'),
                'support_notice_type' => 'error',
            ));
        }

        $ticket_id = ThemisDB_SupportPortal_Ticket_Manager::create_ticket($data);

        if (!$ticket_id) {
            $this->redirect_to_tickets(array(
                'tab' => 'create',
                'support_notice' => __('Ticket konnte nicht erstellt werden.', 'themisdb-support-portal'),
                'support_notice_type' => 'error',
            ));
        }

        $this->redirect_to_tickets(array(
            'ticket_id' => $ticket_id,
            'support_notice' => __('Ticket erfolgreich erstellt.', 'themisdb-support-portal'),
            'support_notice_type' => 'success',
        ));
    }

    /**
     * Handle bulk operations for tickets.
     */
    private function handle_bulk_ticket_action() {
        check_admin_referer('themisdb_support_bulk_tickets', 'themisdb_support_nonce');

        $bulk_action = $this->get_bulk_action_from_request($_POST);
        $ticket_ids = isset($_POST['ticket_ids']) ? array_map('intval', (array) $_POST['ticket_ids']) : array();
        $list_state_args = $this->get_list_state_args_from_post();

        if (empty($bulk_action) || empty($ticket_ids)) {
            $this->redirect_to_tickets(array_merge($list_state_args, array(
                'support_notice' => __('Bitte Aktion und mindestens ein Ticket auswaehlen.', 'themisdb-support-portal'),
                'support_notice_type' => 'warning',
            )));
        }

        $result = $this->process_bulk_ticket_action($bulk_action, $ticket_ids);
        $this->redirect_to_tickets(array_merge($list_state_args, array(
            'support_notice' => $result['message'],
            'support_notice_type' => $result['notice_type'],
        )));
    }

    /**
     * Normalize bulk action from POST payload.
     *
     * @param array $request
     * @return string
     */
    private function get_bulk_action_from_request($request) {
        $bulk_action = '';
        if (isset($request['bulk_action_top'])) {
            $bulk_action = sanitize_key(wp_unslash($request['bulk_action_top']));
        }
        if ($bulk_action === '' && isset($request['bulk_action_bottom'])) {
            $bulk_action = sanitize_key(wp_unslash($request['bulk_action_bottom']));
        }
        if ($bulk_action === '' && isset($request['bulk_action'])) {
            $bulk_action = sanitize_key(wp_unslash($request['bulk_action']));
        }

        return $bulk_action;
    }

    /**
     * Execute bulk ticket action and return structured result.
     *
     * @param string $bulk_action
     * @param array  $ticket_ids
     * @return array
     */
    private function process_bulk_ticket_action($bulk_action, $ticket_ids) {
        $status_actions = array(
            'status_open' => ThemisDB_SupportPortal_Ticket_Manager::STATUS_OPEN,
            'status_in_progress' => ThemisDB_SupportPortal_Ticket_Manager::STATUS_IN_PROGRESS,
            'status_resolved' => ThemisDB_SupportPortal_Ticket_Manager::STATUS_RESOLVED,
            'status_closed' => ThemisDB_SupportPortal_Ticket_Manager::STATUS_CLOSED,
        );

        if (isset($status_actions[$bulk_action])) {
            $count = ThemisDB_SupportPortal_Ticket_Manager::bulk_update_status($ticket_ids, $status_actions[$bulk_action]);
            return array(
                'message' => sprintf(__('Status bei %d Ticket(s) aktualisiert.', 'themisdb-support-portal'), $count),
                'notice_type' => $count > 0 ? 'success' : 'warning',
                'effect' => 'status',
                'status' => $status_actions[$bulk_action],
                'count' => $count,
            );
        }

        if ($bulk_action === 'assign_none') {
            $count = ThemisDB_SupportPortal_Ticket_Manager::bulk_update_assignee($ticket_ids, null);
            return array(
                'message' => sprintf(__('Zuweisung bei %d Ticket(s) entfernt.', 'themisdb-support-portal'), $count),
                'notice_type' => $count > 0 ? 'success' : 'warning',
                'effect' => 'assign',
                'assignee_user_id' => 0,
                'count' => $count,
            );
        }

        if (strpos($bulk_action, 'assign_user_') === 0) {
            $assignee_user_id = intval(substr($bulk_action, strlen('assign_user_')));
            if ($assignee_user_id <= 0) {
                return array(
                    'message' => __('Ungueltiger Bearbeiter fuer Bulk-Zuweisung.', 'themisdb-support-portal'),
                    'notice_type' => 'error',
                    'effect' => 'assign',
                );
            }

            $count = ThemisDB_SupportPortal_Ticket_Manager::bulk_update_assignee($ticket_ids, $assignee_user_id);
            return array(
                'message' => sprintf(__('Bearbeiter bei %d Ticket(s) aktualisiert.', 'themisdb-support-portal'), $count),
                'notice_type' => $count > 0 ? 'success' : 'warning',
                'effect' => 'assign',
                'assignee_user_id' => $assignee_user_id,
                'count' => $count,
            );
        }

        if ($bulk_action === 'delete') {
            $count = ThemisDB_SupportPortal_Ticket_Manager::bulk_delete_tickets($ticket_ids);
            return array(
                'message' => sprintf(__('%d Ticket(s) geloescht.', 'themisdb-support-portal'), $count),
                'notice_type' => $count > 0 ? 'success' : 'warning',
                'effect' => 'delete',
                'count' => $count,
            );
        }

        return array(
            'message' => __('Unbekannte Bulk-Aktion.', 'themisdb-support-portal'),
            'notice_type' => 'error',
            'effect' => 'unknown',
            'count' => 0,
        );
    }

    /**
     * Extract current list page state from bulk form POST fields.
     *
     * @return array
     */
    private function get_list_state_args_from_post() {
        $args = array(
            'tab' => 'list',
        );

        $status = isset($_POST['current_status']) ? sanitize_key($_POST['current_status']) : '';
        $priority = isset($_POST['current_priority']) ? sanitize_key($_POST['current_priority']) : '';
        $assignee_user_id = isset($_POST['current_assignee_user_id']) ? max(0, intval($_POST['current_assignee_user_id'])) : 0;
        $paged = isset($_POST['current_paged']) ? max(1, intval($_POST['current_paged'])) : 1;

        if ($status !== '') {
            $args['status'] = $status;
        }

        if ($priority !== '') {
            $args['priority'] = $priority;
        }

        if ($assignee_user_id > 0) {
            $args['assignee_user_id'] = $assignee_user_id;
        }

        if ($paged > 1) {
            $args['paged'] = $paged;
        }

        return $args;
    }

    /**
     * Handle per-row quick assignee update from the ticket list.
     */
    private function handle_quick_assign_ticket_action() {
        check_admin_referer('themisdb_support_quick_assign_ticket', 'themisdb_support_nonce');

        $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
        $assignee_user_id = isset($_POST['assignee_user_id']) ? intval($_POST['assignee_user_id']) : 0;
        $list_state_args = $this->get_list_state_args_from_post();

        if ($ticket_id <= 0) {
            $this->redirect_to_tickets(array_merge($list_state_args, array(
                'support_notice' => __('Ungueltige Ticket-ID fuer Schnellzuweisung.', 'themisdb-support-portal'),
                'support_notice_type' => 'error',
            )));
        }

        $updated = ThemisDB_SupportPortal_Ticket_Manager::update_ticket($ticket_id, array(
            'assignee_user_id' => $assignee_user_id,
        ));

        $this->redirect_to_tickets(array_merge($list_state_args, array(
            'support_notice' => $updated
                ? __('Bearbeiter erfolgreich aktualisiert.', 'themisdb-support-portal')
                : __('Bearbeiter konnte nicht aktualisiert werden.', 'themisdb-support-portal'),
            'support_notice_type' => $updated ? 'success' : 'error',
        )));
    }

    /**
     * Handle full ticket update from detail view.
     */
    private function handle_update_ticket_action() {
        check_admin_referer('themisdb_support_update_ticket', 'themisdb_support_nonce');

        $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
        if ($ticket_id <= 0) {
            $this->redirect_to_tickets(array(
                'support_notice' => __('Ungueltige Ticket-ID.', 'themisdb-support-portal'),
                'support_notice_type' => 'error',
            ));
        }

        $data = array(
            'subject' => isset($_POST['subject']) ? sanitize_text_field(wp_unslash($_POST['subject'])) : '',
            'priority' => isset($_POST['priority']) ? sanitize_key($_POST['priority']) : '',
            'status' => isset($_POST['status']) ? sanitize_key($_POST['status']) : '',
            'customer_name' => isset($_POST['customer_name']) ? sanitize_text_field(wp_unslash($_POST['customer_name'])) : '',
            'customer_email' => isset($_POST['customer_email']) ? sanitize_email(wp_unslash($_POST['customer_email'])) : '',
            'customer_company' => isset($_POST['customer_company']) ? sanitize_text_field(wp_unslash($_POST['customer_company'])) : '',
            'license_key' => isset($_POST['license_key']) ? sanitize_text_field(wp_unslash($_POST['license_key'])) : '',
            'assignee_user_id' => isset($_POST['assignee_user_id']) ? intval($_POST['assignee_user_id']) : 0,
        );

        $updated = ThemisDB_SupportPortal_Ticket_Manager::update_ticket($ticket_id, $data);
        $this->redirect_to_tickets(array(
            'ticket_id' => $ticket_id,
            'support_notice' => $updated
                ? __('Ticket erfolgreich aktualisiert.', 'themisdb-support-portal')
                : __('Ticket konnte nicht aktualisiert werden.', 'themisdb-support-portal'),
            'support_notice_type' => $updated ? 'success' : 'error',
        ));
    }

    /**
     * Handle single ticket delete.
     */
    private function handle_delete_ticket_action() {
        check_admin_referer('themisdb_support_delete_ticket', 'themisdb_support_nonce');

        $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
        if ($ticket_id <= 0) {
            $this->redirect_to_tickets(array(
                'support_notice' => __('Ungueltige Ticket-ID.', 'themisdb-support-portal'),
                'support_notice_type' => 'error',
            ));
        }

        $deleted = ThemisDB_SupportPortal_Ticket_Manager::delete_ticket($ticket_id);
        $this->redirect_to_tickets(array(
            'support_notice' => $deleted
                ? __('Ticket erfolgreich geloescht.', 'themisdb-support-portal')
                : __('Ticket konnte nicht geloescht werden.', 'themisdb-support-portal'),
            'support_notice_type' => $deleted ? 'success' : 'error',
        ));
    }

    /**
     * Redirect helper for admin ticket pages.
     *
     * @param array $args
     */
    private function redirect_to_tickets($args = array()) {
        $url = add_query_arg(array_merge(array(
            'page' => 'themisdb-support',
        ), $args), admin_url('admin.php'));

        wp_safe_redirect($url);
        exit;
    }

    /**
     * Render the single ticket detail view.
     *
     * @param int $ticket_id
     */
    private function view_ticket_page($ticket_id) {
        $ticket   = ThemisDB_SupportPortal_Ticket_Manager::get_ticket($ticket_id);
        $messages = ThemisDB_SupportPortal_Ticket_Manager::get_messages($ticket_id);
        $list_state_args = $this->get_list_state_args_from_get();
        $ticket_navigation = $this->get_ticket_navigation($ticket_id, $list_state_args);
        $back_to_list_url = add_query_arg(array_merge(array(
            'page' => 'themisdb-support',
        ), $list_state_args), admin_url('admin.php'));

        $notice_message = isset($_GET['support_notice']) ? sanitize_text_field(wp_unslash($_GET['support_notice'])) : '';
        $notice_type = isset($_GET['support_notice_type']) ? sanitize_key($_GET['support_notice_type']) : 'success';
        if (!in_array($notice_type, array('success', 'error', 'warning', 'info'), true)) {
            $notice_type = 'success';
        }

        if (!$ticket) {
            echo '<div class="wrap"><div class="notice notice-error"><p>' . esc_html__('Ticket nicht gefunden.', 'themisdb-support-portal') . '</p></div></div>';
            return;
        }

        $status_labels   = ThemisDB_SupportPortal_Ticket_Manager::get_status_labels();
        $priority_labels = ThemisDB_SupportPortal_Ticket_Manager::get_priority_labels();
        $assignable_agents = $this->get_assignable_agents();
        $support_context = $this->resolve_support_context_for_ticket($ticket);
        $support_license = isset($support_context['license']) ? $support_context['license'] : null;
        $support_benefit = isset($support_context['benefit']) ? $support_context['benefit'] : null;

        include THEMISDB_SUPPORT_PLUGIN_DIR . 'templates/admin-ticket-view.php';
    }

    /**
     * Resolve previous and next ticket IDs for current list filters.
     *
     * @param int   $current_ticket_id
     * @param array $list_state_args
     * @return array
     */
    private function get_ticket_navigation($current_ticket_id, $list_state_args = array()) {
        global $wpdb;

        $current_ticket_id = intval($current_ticket_id);
        if ($current_ticket_id <= 0) {
            return array('previous' => null, 'next' => null);
        }

        $table = $wpdb->prefix . 'themisdb_support_tickets';
        $where = array('1=1');
        $params = array();

        if (!empty($list_state_args['status'])) {
            $where[] = 'status = %s';
            $params[] = sanitize_key($list_state_args['status']);
        }

        if (!empty($list_state_args['priority'])) {
            $where[] = 'priority = %s';
            $params[] = sanitize_key($list_state_args['priority']);
        }

        if (!empty($list_state_args['assignee_user_id'])) {
            $where[] = 'assignee_user_id = %d';
            $params[] = intval($list_state_args['assignee_user_id']);
        }

        $sql = "SELECT id FROM $table WHERE " . implode(' AND ', $where) . ' ORDER BY created_at DESC, id DESC';

        if (!empty($params)) {
            $ids = $wpdb->get_col($wpdb->prepare($sql, $params));
        } else {
            $ids = $wpdb->get_col($sql);
        }

        $ids = array_map('intval', (array) $ids);
        $index = array_search($current_ticket_id, $ids, true);

        if ($index === false) {
            return array('previous' => null, 'next' => null);
        }

        return array(
            'previous' => isset($ids[$index - 1]) ? intval($ids[$index - 1]) : null,
            'next' => isset($ids[$index + 1]) ? intval($ids[$index + 1]) : null,
        );
    }

    /**
     * Resolve related license and support benefit data for a ticket.
     *
     * @param array $ticket
     * @return array
     */
    private function resolve_support_context_for_ticket($ticket) {
        global $wpdb;

        $context = array(
            'license' => null,
            'benefit' => null,
        );

        if (empty($ticket) || !is_array($ticket)) {
            return $context;
        }

        $license = null;
        if (!empty($ticket['license_key']) && class_exists('ThemisDB_License_Manager')) {
            $license = ThemisDB_License_Manager::get_license_by_key($ticket['license_key']);
        }

        if (is_array($license)) {
            $context['license'] = $license;
        }

        if (!empty($ticket['benefit_id'])) {
            $table = $wpdb->prefix . 'themisdb_support_benefits';
            $benefit = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM $table WHERE id = %d", intval($ticket['benefit_id'])),
                ARRAY_A
            );
            if (is_array($benefit)) {
                $context['benefit'] = $benefit;
                return $context;
            }
        }

        if (!empty($license['id']) && class_exists('ThemisDB_Support_Benefits_Manager')) {
            $benefit = ThemisDB_Support_Benefits_Manager::get_by_license(intval($license['id']));
            if (is_array($benefit)) {
                $context['benefit'] = $benefit;
            }
        }

        return $context;
    }

    /**
     * Render the Incident Log admin page.
     */
    public function incidents_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Keine Berechtigung', 'themisdb-support-portal'));
        }

        // Handle resolve / ignore actions.
        if (!empty($_POST['themisdb_incident_action']) && check_admin_referer('themisdb_incident_action', 'themisdb_incident_nonce')) {
            $action      = sanitize_key($_POST['themisdb_incident_action']);
            $incident_id = isset($_POST['incident_id']) ? absint($_POST['incident_id']) : 0;

            if ($incident_id > 0 && class_exists('ThemisDB_Incident_Log')) {
                if ($action === 'resolve') {
                    ThemisDB_Incident_Log::resolve($incident_id);
                } elseif ($action === 'ignore') {
                    ThemisDB_Incident_Log::ignore($incident_id);
                }
            }
        }

        // Filter parameters.
        $filter_status   = isset($_GET['inc_status'])   ? sanitize_key($_GET['inc_status'])   : '';
        $filter_domain   = isset($_GET['inc_domain'])   ? sanitize_key($_GET['inc_domain'])   : '';
        $filter_severity = isset($_GET['inc_severity']) ? sanitize_key($_GET['inc_severity']) : '';

        $query_args = array('limit' => 200);
        if ($filter_status)   { $query_args['status']   = $filter_status; }
        if ($filter_domain)   { $query_args['domain']   = $filter_domain; }
        if ($filter_severity) { $query_args['severity'] = $filter_severity; }

        $incidents    = class_exists('ThemisDB_Incident_Log') ? ThemisDB_Incident_Log::get_all($query_args) : array();
        $open_count   = class_exists('ThemisDB_Incident_Log') ? ThemisDB_Incident_Log::get_open_count()     : 0;

        $severity_labels = array(
            ''         => __('Alle Schweregrade', 'themisdb-support-portal'),
            'low'      => __('Niedrig', 'themisdb-support-portal'),
            'medium'   => __('Mittel', 'themisdb-support-portal'),
            'high'     => __('Hoch', 'themisdb-support-portal'),
            'critical' => __('Kritisch', 'themisdb-support-portal'),
        );
        $status_labels = array(
            ''         => __('Alle Status', 'themisdb-support-portal'),
            'open'     => __('Offen', 'themisdb-support-portal'),
            'resolved' => __('Geloest', 'themisdb-support-portal'),
            'ignored'  => __('Ignoriert', 'themisdb-support-portal'),
        );
        $domain_labels = array(
            ''       => __('Alle Domains', 'themisdb-support-portal'),
            'sla'    => 'SLA',
            'mail'   => 'Mail',
            'build'  => 'Build',
            'sync'   => 'Sync',
            'system' => 'System',
        );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Incident Log', 'themisdb-support-portal'); ?></h1>
            <?php if ($open_count > 0): ?>
                <div class="notice notice-warning inline">
                    <p><?php echo esc_html(sprintf(__('%d offene Incidents', 'themisdb-support-portal'), $open_count)); ?></p>
                </div>
            <?php endif; ?>

            <form method="get" style="margin-bottom:16px;">
                <input type="hidden" name="page" value="themisdb-support-incidents">
                <select name="inc_status">
                    <?php foreach ($status_labels as $val => $lbl): ?>
                        <option value="<?php echo esc_attr($val); ?>" <?php selected($filter_status, $val); ?>><?php echo esc_html($lbl); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="inc_domain">
                    <?php foreach ($domain_labels as $val => $lbl): ?>
                        <option value="<?php echo esc_attr($val); ?>" <?php selected($filter_domain, $val); ?>><?php echo esc_html($lbl); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="inc_severity">
                    <?php foreach ($severity_labels as $val => $lbl): ?>
                        <option value="<?php echo esc_attr($val); ?>" <?php selected($filter_severity, $val); ?>><?php echo esc_html($lbl); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="button"><?php esc_html_e('Filtern', 'themisdb-support-portal'); ?></button>
                <?php if ($filter_status || $filter_domain || $filter_severity): ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-support-incidents')); ?>" class="button button-secondary"><?php esc_html_e('Zuruecksetzen', 'themisdb-support-portal'); ?></a>
                <?php endif; ?>
            </form>

            <?php if (empty($incidents)): ?>
                <p><?php esc_html_e('Keine Incidents gefunden.', 'themisdb-support-portal'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width:40px;"><?php esc_html_e('ID', 'themisdb-support-portal'); ?></th>
                            <th style="width:80px;"><?php esc_html_e('Domain', 'themisdb-support-portal'); ?></th>
                            <th style="width:90px;"><?php esc_html_e('Schwere', 'themisdb-support-portal'); ?></th>
                            <th style="width:80px;"><?php esc_html_e('Status', 'themisdb-support-portal'); ?></th>
                            <th><?php esc_html_e('Fehlermeldung', 'themisdb-support-portal'); ?></th>
                            <th style="width:60px;"><?php esc_html_e('Wiederh.', 'themisdb-support-portal'); ?></th>
                            <th style="width:140px;"><?php esc_html_e('Zeitpunkt', 'themisdb-support-portal'); ?></th>
                            <th style="width:140px;"><?php esc_html_e('Aktionen', 'themisdb-support-portal'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($incidents as $inc): ?>
                            <?php
                            $severity_color = class_exists('ThemisDB_Incident_Log')
                                ? ThemisDB_Incident_Log::severity_color($inc['severity'])
                                : '#999';
                            $severity_lbl = class_exists('ThemisDB_Incident_Log')
                                ? ThemisDB_Incident_Log::severity_label($inc['severity'])
                                : $inc['severity'];
                            $status_lbl = class_exists('ThemisDB_Incident_Log')
                                ? ThemisDB_Incident_Log::status_label($inc['status'])
                                : $inc['status'];
                            ?>
                            <tr>
                                <td><?php echo esc_html($inc['id']); ?></td>
                                <td><code><?php echo esc_html($inc['domain']); ?></code></td>
                                <td>
                                    <span style="display:inline-block;padding:2px 8px;border-radius:3px;background:<?php echo esc_attr($severity_color); ?>;color:#fff;font-size:11px;font-weight:600;">
                                        <?php echo esc_html($severity_lbl); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($status_lbl); ?></td>
                                <td><?php echo esc_html($inc['last_error']); ?></td>
                                <td style="text-align:center;"><?php echo esc_html($inc['retry_count']); ?></td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format') . ' H:i', strtotime($inc['created_at']))); ?></td>
                                <td>
                                    <?php if ($inc['status'] === 'open'): ?>
                                        <form method="post" style="display:inline;">
                                            <?php wp_nonce_field('themisdb_incident_action', 'themisdb_incident_nonce'); ?>
                                            <input type="hidden" name="incident_id" value="<?php echo esc_attr($inc['id']); ?>">
                                            <button type="submit" name="themisdb_incident_action" value="resolve" class="button button-small button-primary">
                                                <?php esc_html_e('Geloest', 'themisdb-support-portal'); ?>
                                            </button>
                                            <button type="submit" name="themisdb_incident_action" value="ignore" class="button button-small">
                                                <?php esc_html_e('Ignorieren', 'themisdb-support-portal'); ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        &mdash;
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render the Observability Dashboard (ARCHITECTUR.md §7).
     */
    public function observability_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Keine Berechtigung', 'themisdb-support-portal'));
        }

        // Allow manual cache flush via URL param.
        if (!empty($_GET['obs_flush_cache']) && check_admin_referer('themisdb_obs_flush', '_wpnonce')) {
            if (class_exists('ThemisDB_Observability')) {
                ThemisDB_Observability::flush_cache();
            }
            wp_safe_redirect(admin_url('admin.php?page=themisdb-support-observability'));
            exit;
        }

        $frt       = class_exists('ThemisDB_Observability') ? ThemisDB_Observability::get_first_response_time()  : null;
        $sla_rate  = class_exists('ThemisDB_Observability') ? ThemisDB_Observability::get_sla_breach_rate()       : null;
        $volume    = class_exists('ThemisDB_Observability') ? ThemisDB_Observability::get_ticket_volume()         : array();
        $queues    = class_exists('ThemisDB_Observability') ? ThemisDB_Observability::get_queue_distribution()    : array();
        $incidents = class_exists('ThemisDB_Observability') ? ThemisDB_Observability::get_incident_summary()      : array();
        $res_time  = class_exists('ThemisDB_Observability') ? ThemisDB_Observability::get_avg_resolution_time()   : null;

        $flush_url = wp_nonce_url(admin_url('admin.php?page=themisdb-support-observability&obs_flush_cache=1'), 'themisdb_obs_flush');
        ?>
        <div class="wrap">
            <h1 style="display:flex;align-items:center;gap:12px;">
                <?php esc_html_e('Observability Dashboard', 'themisdb-support-portal'); ?>
                <a href="<?php echo esc_url($flush_url); ?>" class="button button-small" style="font-size:11px;margin-top:2px;"><?php esc_html_e('Cache leeren', 'themisdb-support-portal'); ?></a>
            </h1>
            <p style="color:#666;margin-top:-4px;"><?php esc_html_e('Metriken werden 5 Minuten gecacht. Zeitraum: letzte 30 Tage sofern nicht anders angegeben.', 'themisdb-support-portal'); ?></p>

            <!-- KPI-Karten -->
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;margin-bottom:24px;">
                <?php
                $kpis = array(
                    array(
                        'label'  => __('Erste Antwortzeit', 'themisdb-support-portal'),
                        'value'  => $frt !== null ? number_format_i18n($frt, 1) . ' h' : '—',
                        'sub'    => __('Ø Stunden bis erste Admin-Antwort', 'themisdb-support-portal'),
                        'color'  => $frt !== null && $frt <= 4 ? '#27ae60' : ($frt !== null && $frt <= 8 ? '#f39c12' : '#e74c3c'),
                        'icon'   => 'dashicons-clock',
                    ),
                    array(
                        'label'  => __('SLA Breach Rate', 'themisdb-support-portal'),
                        'value'  => $sla_rate !== null ? number_format_i18n($sla_rate, 1) . ' %' : '—',
                        'sub'    => __('Anteil geschlossener Tickets mit SLA-Verstoss', 'themisdb-support-portal'),
                        'color'  => $sla_rate !== null && $sla_rate <= 5 ? '#27ae60' : ($sla_rate !== null && $sla_rate <= 15 ? '#f39c12' : '#e74c3c'),
                        'icon'   => 'dashicons-warning',
                    ),
                    array(
                        'label'  => __('Ø Loesezeit', 'themisdb-support-portal'),
                        'value'  => $res_time !== null ? number_format_i18n($res_time, 1) . ' h' : '—',
                        'sub'    => __('Ø Stunden von Erstellung bis Schliessung', 'themisdb-support-portal'),
                        'color'  => $res_time !== null && $res_time <= 24 ? '#27ae60' : ($res_time !== null && $res_time <= 72 ? '#f39c12' : '#e74c3c'),
                        'icon'   => 'dashicons-yes-alt',
                    ),
                    array(
                        'label'  => __('Offene Incidents', 'themisdb-support-portal'),
                        'value'  => array_sum($incidents),
                        'sub'    => __('Alle offenen Incidents im Incident Log', 'themisdb-support-portal'),
                        'color'  => array_sum($incidents) === 0 ? '#27ae60' : (array_sum($incidents) <= 3 ? '#f39c12' : '#e74c3c'),
                        'icon'   => 'dashicons-flag',
                    ),
                );
                foreach ($kpis as $kpi): ?>
                    <div style="background:#fff;border:1px solid #ddd;border-radius:6px;padding:16px 18px;border-top:4px solid <?php echo esc_attr($kpi['color']); ?>;">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                            <span class="dashicons <?php echo esc_attr($kpi['icon']); ?>" style="color:<?php echo esc_attr($kpi['color']); ?>;font-size:20px;"></span>
                            <strong style="font-size:13px;color:#333;"><?php echo esc_html($kpi['label']); ?></strong>
                        </div>
                        <div style="font-size:28px;font-weight:700;color:<?php echo esc_attr($kpi['color']); ?>;line-height:1.1;"><?php echo esc_html($kpi['value']); ?></div>
                        <div style="font-size:11px;color:#888;margin-top:4px;"><?php echo esc_html($kpi['sub']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
                <!-- Ticket-Volumen letzte 14 Tage -->
                <div style="background:#fff;border:1px solid #ddd;border-radius:6px;padding:16px 18px;">
                    <h3 style="margin-top:0;"><?php esc_html_e('Ticket-Volumen (letzte 14 Tage)', 'themisdb-support-portal'); ?></h3>
                    <?php if (empty($volume) || array_sum($volume) === 0): ?>
                        <p style="color:#888;"><?php esc_html_e('Keine Daten', 'themisdb-support-portal'); ?></p>
                    <?php else:
                        $max_vol = max(array_values($volume));
                        $max_vol = $max_vol > 0 ? $max_vol : 1;
                        ?>
                        <div style="display:flex;align-items:flex-end;gap:4px;height:80px;border-bottom:1px solid #eee;">
                            <?php foreach ($volume as $day => $cnt): ?>
                                <?php $pct = round($cnt / $max_vol * 100); ?>
                                <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;height:100%;">
                                    <div title="<?php echo esc_attr($day . ': ' . $cnt); ?>" style="width:100%;background:#4f7df3;border-radius:2px 2px 0 0;height:<?php echo esc_attr($pct); ?>%;min-height:<?php echo ($cnt > 0 ? 2 : 0); ?>px;"></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:10px;color:#aaa;margin-top:4px;">
                            <span><?php echo esc_html(array_key_first($volume)); ?></span>
                            <span><?php echo esc_html(array_key_last($volume)); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Queue-Verteilung -->
                <div style="background:#fff;border:1px solid #ddd;border-radius:6px;padding:16px 18px;">
                    <h3 style="margin-top:0;"><?php esc_html_e('Queue-Verteilung (aktive Tickets)', 'themisdb-support-portal'); ?></h3>
                    <?php if (empty($queues)): ?>
                        <p style="color:#888;"><?php esc_html_e('Keine offenen Tickets', 'themisdb-support-portal'); ?></p>
                    <?php else:
                        $total_q = array_sum($queues);
                        foreach ($queues as $queue => $cnt):
                            $color = class_exists('ThemisDB_Queue_Router') ? ThemisDB_Queue_Router::queue_color($queue) : '#666';
                            $label = class_exists('ThemisDB_Queue_Router') ? ThemisDB_Queue_Router::queue_label($queue) : ucfirst($queue);
                            $pct   = $total_q > 0 ? round($cnt / $total_q * 100) : 0;
                            ?>
                            <div style="margin-bottom:8px;">
                                <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:2px;">
                                    <span style="font-weight:600;color:<?php echo esc_attr($color); ?>"><?php echo esc_html($label); ?></span>
                                    <span><?php echo esc_html($cnt); ?> (<?php echo esc_html($pct); ?>%)</span>
                                </div>
                                <div style="background:#eee;border-radius:3px;height:6px;">
                                    <div style="background:<?php echo esc_attr($color); ?>;width:<?php echo esc_attr($pct); ?>%;height:6px;border-radius:3px;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Incidents nach Schweregrad -->
                <div style="background:#fff;border:1px solid #ddd;border-radius:6px;padding:16px 18px;">
                    <h3 style="margin-top:0;"><?php esc_html_e('Offene Incidents nach Schweregrad', 'themisdb-support-portal'); ?></h3>
                    <?php if (empty($incidents) || array_sum($incidents) === 0): ?>
                        <p style="color:#27ae60;font-weight:600;"><?php esc_html_e('Keine offenen Incidents', 'themisdb-support-portal'); ?></p>
                    <?php else:
                        $severity_order = array('critical', 'high', 'medium', 'low');
                        foreach ($severity_order as $sev):
                            if (!isset($incidents[$sev])) { continue; }
                            $color = class_exists('ThemisDB_Incident_Log') ? ThemisDB_Incident_Log::severity_color($sev) : '#999';
                            $label = class_exists('ThemisDB_Incident_Log') ? ThemisDB_Incident_Log::severity_label($sev) : ucfirst($sev);
                            ?>
                            <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
                                <span style="display:inline-block;width:80px;padding:2px 8px;border-radius:3px;background:<?php echo esc_attr($color); ?>;color:#fff;font-size:11px;font-weight:600;text-align:center;"><?php echo esc_html($label); ?></span>
                                <strong style="font-size:18px;"><?php echo esc_html($incidents[$sev]); ?></strong>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-support-incidents&inc_status=open&inc_severity=' . $sev)); ?>" style="font-size:11px;"><?php esc_html_e('Anzeigen', 'themisdb-support-portal'); ?> &rarr;</a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <p style="margin-top:12px;margin-bottom:0;">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-support-incidents')); ?>" class="button button-small"><?php esc_html_e('Alle Incidents', 'themisdb-support-portal'); ?></a>
                    </p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Vertragsänderungsantrag genehmigen oder ablehnen (admin_post handler).
     */
    public function handle_change_review() {
        check_admin_referer('themisdb_change_review', '_wpnonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Keine Berechtigung', 'themisdb-support-portal'));
        }

        $request_id  = isset($_POST['request_id'])  ? intval($_POST['request_id'])                           : 0;
        $action      = isset($_POST['cr_action'])    ? sanitize_text_field(wp_unslash($_POST['cr_action']))   : '';
        $review_note = isset($_POST['review_note'])  ? sanitize_textarea_field(wp_unslash($_POST['review_note'])) : '';

        $redirect = admin_url('admin.php?page=themisdb-support-change-requests');

        if (!$request_id || !in_array($action, array('approve', 'reject'), true)) {
            wp_safe_redirect(add_query_arg('cr_error', 'invalid', $redirect));
            exit;
        }

        if (!class_exists('ThemisDB_Contract_Change_Engine')) {
            wp_safe_redirect(add_query_arg('cr_error', 'missing_class', $redirect));
            exit;
        }

        if ($action === 'approve') {
            $result = ThemisDB_Contract_Change_Engine::approve($request_id, $review_note);
        } else {
            $result = ThemisDB_Contract_Change_Engine::reject($request_id, $review_note);
        }

        if (is_wp_error($result)) {
            wp_safe_redirect(add_query_arg('cr_error', urlencode($result->get_error_message()), $redirect));
        } else {
            wp_safe_redirect(add_query_arg('cr_done', $action, $redirect));
        }
        exit;
    }

    /**
     * Render the Change-Requests review page (ARCHITECTUR.md §8.6).
     */
    public function change_requests_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Keine Berechtigung', 'themisdb-support-portal'));
        }

        // Status-Meldungen.
        if (!empty($_GET['cr_done'])) {
            $verb = $_GET['cr_done'] === 'approve'
                ? __('genehmigt', 'themisdb-support-portal')
                : __('abgelehnt', 'themisdb-support-portal');
            echo '<div class="notice notice-success is-dismissible"><p>'
                . esc_html(sprintf(__('Antrag erfolgreich %s.', 'themisdb-support-portal'), $verb))
                . '</p></div>';
        }

        if (!empty($_GET['cr_error'])) {
            echo '<div class="notice notice-error is-dismissible"><p>'
                . esc_html(urldecode(sanitize_text_field(wp_unslash($_GET['cr_error']))))
                . '</p></div>';
        }

        if (!class_exists('ThemisDB_Contract_Change_Engine') || !class_exists('ThemisDB_Contract_Lifecycle')) {
            echo '<div class="wrap"><p style="color:#e74c3c;">'
                . esc_html__('Contract-Change-Engine oder Contract-Lifecycle nicht verfügbar. Bitte stellen Sie sicher, dass themisdb-order-request aktiv ist.', 'themisdb-support-portal')
                . '</p></div>';
            return;
        }

        // Filter.
        $filter_status = isset($_GET['cr_status']) ? sanitize_text_field(wp_unslash($_GET['cr_status'])) : '';
        $paged         = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page      = 20;

        $list_args = array('limit' => $per_page, 'offset' => ($paged - 1) * $per_page);
        if ($filter_status) {
            $list_args['status'] = $filter_status;
        }

        $requests = ThemisDB_Contract_Change_Engine::list_change_requests($list_args);

        $status_options = array(
            ''                => __('Alle', 'themisdb-support-portal'),
            'requested'       => __('Ausstehend', 'themisdb-support-portal'),
            'confirmed'       => __('Genehmigt', 'themisdb-support-portal'),
            'rejected'        => __('Abgelehnt', 'themisdb-support-portal'),
            'executed'        => __('Ausgeführt', 'themisdb-support-portal'),
            'pending_finance' => __('Finance-Review', 'themisdb-support-portal'),
        );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Vertragsänderungsanträge', 'themisdb-support-portal'); ?></h1>
            <p style="color:#666;"><?php esc_html_e('Eingehende Änderungsanträge mit Impact-Analyse. Genehmigung oder Ablehnung werden sofort ausgeführt.', 'themisdb-support-portal'); ?></p>

            <!-- Filter -->
            <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" style="display:flex;gap:10px;align-items:center;margin-bottom:16px;">
                <input type="hidden" name="page" value="themisdb-support-change-requests">
                <select name="cr_status">
                    <?php foreach ($status_options as $val => $label): ?>
                        <option value="<?php echo esc_attr($val); ?>" <?php selected($filter_status, $val); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="button"><?php esc_html_e('Filtern', 'themisdb-support-portal'); ?></button>
                <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-support-change-requests')); ?>" class="button button-secondary"><?php esc_html_e('Zurücksetzen', 'themisdb-support-portal'); ?></a>
            </form>

            <?php if (empty($requests)): ?>
                <p style="color:#888;"><?php esc_html_e('Keine Änderungsanträge gefunden.', 'themisdb-support-portal'); ?></p>
            <?php else: ?>

                <?php foreach ($requests as $req):
                    $status  = isset($req['status'])   ? $req['status']   : 'requested';
                    $req_id  = isset($req['id'])        ? intval($req['id']) : 0;
                    $lic_id  = isset($req['license_id']) ? intval($req['license_id']) : 0;
                    $impact  = ThemisDB_Contract_Change_Engine::get_impact($req_id);
                    $sc = ThemisDB_Contract_Change_Engine::status_color($status);
                    $sl = ThemisDB_Contract_Change_Engine::status_label($status);
                    ?>
                    <div style="background:#fff;border:1px solid #ddd;border-radius:6px;margin-bottom:20px;overflow:hidden;">
                        <!-- Header -->
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid #eee;background:#fafafa;">
                            <div style="display:flex;align-items:center;gap:12px;">
                                <span style="font-size:13px;font-weight:700;"><?php esc_html_e('Antrag', 'themisdb-support-portal'); ?> #<?php echo esc_html((string)$req_id); ?></span>
                                <span style="display:inline-block;padding:2px 8px;border-radius:3px;background:<?php echo esc_attr($sc); ?>;color:#fff;font-size:11px;font-weight:600;"><?php echo esc_html($sl); ?></span>
                                <?php if ($impact): ?>
                                    <?php $rl = isset($impact['risk_level']) ? $impact['risk_level'] : 'low'; ?>
                                    <span style="display:inline-block;padding:2px 8px;border-radius:3px;background:<?php echo esc_attr(ThemisDB_Contract_Change_Engine::risk_color($rl)); ?>;color:#fff;font-size:11px;">
                                        <?php esc_html_e('Risiko:', 'themisdb-support-portal'); ?> <?php echo esc_html(ThemisDB_Contract_Change_Engine::risk_label($rl)); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <span style="font-size:11px;color:#aaa;"><?php echo esc_html(isset($req['created_at']) ? mysql2date('d.m.Y H:i', $req['created_at']) : ''); ?></span>
                        </div>

                        <div style="padding:14px 16px;">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">

                                <!-- Antrag-Details -->
                                <div>
                                    <h4 style="margin:0 0 8px;font-size:13px;"><?php esc_html_e('Antragsdaten', 'themisdb-support-portal'); ?></h4>
                                    <table style="font-size:12px;width:100%;border-collapse:collapse;">
                                        <tr>
                                            <td style="padding:3px 0;color:#666;width:45%;"><?php esc_html_e('Lizenz-ID', 'themisdb-support-portal'); ?></td>
                                            <td style="padding:3px 0;"><?php echo esc_html($lic_id > 0 ? (string)$lic_id : '—'); ?></td>
                                        </tr>
                                        <?php if (!empty($req['reason'])): ?>
                                        <tr>
                                            <td style="padding:3px 0;color:#666;vertical-align:top;"><?php esc_html_e('Begründung', 'themisdb-support-portal'); ?></td>
                                            <td style="padding:3px 0;"><?php echo esc_html(mb_strimwidth($req['reason'], 0, 200, '…')); ?></td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php if (!empty($req['review_note'])): ?>
                                        <tr>
                                            <td style="padding:3px 0;color:#666;vertical-align:top;"><?php esc_html_e('Review-Notiz', 'themisdb-support-portal'); ?></td>
                                            <td style="padding:3px 0;color:#e74c3c;"><?php echo esc_html($req['review_note']); ?></td>
                                        </tr>
                                        <?php endif; ?>
                                    </table>
                                </div>

                                <!-- Impact-Analyse -->
                                <div>
                                    <h4 style="margin:0 0 8px;font-size:13px;"><?php esc_html_e('Impact-Analyse', 'themisdb-support-portal'); ?></h4>
                                    <?php if (!$impact): ?>
                                        <p style="color:#aaa;font-size:12px;"><?php esc_html_e('Keine Impact-Daten verfügbar.', 'themisdb-support-portal'); ?></p>
                                    <?php elseif (empty($impact['changes'])): ?>
                                        <p style="color:#888;font-size:12px;"><?php esc_html_e('Keine Felder geändert.', 'themisdb-support-portal'); ?></p>
                                    <?php else: ?>
                                        <table style="font-size:12px;width:100%;border-collapse:collapse;">
                                            <?php foreach ($impact['changes'] as $ch): ?>
                                                <tr style="border-bottom:1px solid #f5f5f5;">
                                                    <td style="padding:3px 0;color:#666;width:40%;"><?php echo esc_html(isset($ch['label']) ? $ch['label'] : $ch['field']); ?></td>
                                                    <td style="padding:3px 0;font-family:monospace;"><?php echo esc_html(isset($ch['old']) ? $ch['old'] : '—'); ?></td>
                                                    <td style="padding:3px 0;color:#555;">→</td>
                                                    <td style="padding:3px 0;font-family:monospace;font-weight:700;"><?php echo esc_html(isset($ch['new']) ? $ch['new'] : '—'); ?></td>
                                                    <td style="padding:3px 0;font-size:11px;color:#777;"><?php echo isset($ch['direction']) ? esc_html(ThemisDB_Contract_Change_Engine::direction_label($ch['direction'])) : ''; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </table>
                                        <?php if (!is_null($impact['price_delta']) && $impact['price_delta'] != 0): ?>
                                            <p style="margin:6px 0 0;font-size:12px;color:<?php echo $impact['price_delta'] > 0 ? '#27ae60' : '#e74c3c'; ?>;">
                                                <?php esc_html_e('Preisänderung (geschätzt):', 'themisdb-support-portal'); ?>
                                                <?php echo esc_html(($impact['price_delta'] > 0 ? '+' : '') . number_format_i18n($impact['price_delta'], 2) . ' EUR/Jahr'); ?>
                                            </p>
                                        <?php endif; ?>
                                        <?php if ($impact['requires_ops'] || $impact['requires_finance']): ?>
                                            <p style="margin:6px 0 0;font-size:11px;color:#888;">
                                                <?php if ($impact['requires_ops']): ?>
                                                    <span style="background:#e67e22;color:#fff;padding:1px 5px;border-radius:2px;margin-right:4px;"><?php esc_html_e('Ops-Review', 'themisdb-support-portal'); ?></span>
                                                <?php endif; ?>
                                                <?php if ($impact['requires_finance']): ?>
                                                    <span style="background:#9b59b6;color:#fff;padding:1px 5px;border-radius:2px;"><?php esc_html_e('Finance-Review', 'themisdb-support-portal'); ?></span>
                                                <?php endif; ?>
                                            </p>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Genehmigungsformular (nur bei Status requested) -->
                            <?php if ($status === 'requested'): ?>
                                <div style="border-top:1px solid #eee;margin-top:14px;padding-top:12px;">
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
                                        <?php wp_nonce_field('themisdb_change_review', '_wpnonce'); ?>
                                        <input type="hidden" name="action" value="themisdb_change_review">
                                        <input type="hidden" name="request_id" value="<?php echo esc_attr((string)$req_id); ?>">

                                        <div style="flex:1;min-width:200px;">
                                            <label style="display:block;font-size:12px;margin-bottom:4px;"><?php esc_html_e('Review-Notiz (optional)', 'themisdb-support-portal'); ?></label>
                                            <input type="text" name="review_note" style="width:100%;padding:4px 8px;border:1px solid #ddd;border-radius:3px;font-size:12px;" placeholder="<?php esc_attr_e('Begründung für Entscheidung…', 'themisdb-support-portal'); ?>">
                                        </div>

                                        <button type="submit" name="cr_action" value="approve" class="button button-primary" style="background:#27ae60;border-color:#219150;">
                                            <?php esc_html_e('Genehmigen', 'themisdb-support-portal'); ?>
                                        </button>
                                        <button type="submit" name="cr_action" value="reject" class="button" style="color:#e74c3c;border-color:#e74c3c;" onclick="return confirm('<?php esc_attr_e('Antrag wirklich ablehnen?', 'themisdb-support-portal'); ?>');">
                                            <?php esc_html_e('Ablehnen', 'themisdb-support-portal'); ?>
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Kündigungsantrag bestätigen oder ablehnen (admin_post handler, §8.7).
     */
    public function handle_termination_review() {
        check_admin_referer('themisdb_termination_review', '_wpnonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Keine Berechtigung', 'themisdb-support-portal'));
        }

        $request_id  = isset($_POST['request_id'])  ? intval($_POST['request_id'])                               : 0;
        $action      = isset($_POST['tr_action'])    ? sanitize_text_field(wp_unslash($_POST['tr_action']))       : '';
        $review_note = isset($_POST['review_note'])  ? sanitize_textarea_field(wp_unslash($_POST['review_note'])) : '';

        $redirect = admin_url('admin.php?page=themisdb-support-terminations');

        if (!$request_id || !in_array($action, array('approve', 'reject'), true)) {
            wp_safe_redirect(add_query_arg('tr_error', 'invalid', $redirect));
            exit;
        }

        if (!class_exists('ThemisDB_Contract_Termination_Engine')) {
            wp_safe_redirect(add_query_arg('tr_error', 'missing_class', $redirect));
            exit;
        }

        if ($action === 'approve') {
            $result = ThemisDB_Contract_Termination_Engine::approve($request_id, $review_note);
        } else {
            $result = ThemisDB_Contract_Termination_Engine::reject($request_id, $review_note);
        }

        if (is_wp_error($result)) {
            wp_safe_redirect(add_query_arg('tr_error', urlencode($result->get_error_message()), $redirect));
        } else {
            wp_safe_redirect(add_query_arg('tr_done', $action, $redirect));
        }
        exit;
    }

    /**
     * Scheduler sofort manuell ausführen (admin_post handler, §8.7).
     */
    public function handle_termination_run_now() {
        check_admin_referer('themisdb_termination_run_now', '_wpnonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Keine Berechtigung', 'themisdb-support-portal'));
        }

        $redirect = admin_url('admin.php?page=themisdb-support-terminations');

        if (!class_exists('ThemisDB_Contract_Termination_Engine')) {
            wp_safe_redirect(add_query_arg('tr_error', 'missing_class', $redirect));
            exit;
        }

        $result = ThemisDB_Contract_Termination_Engine::run_scheduler_now();
        wp_safe_redirect(add_query_arg(array(
            'tr_run_done'      => '1',
            'tr_executed'      => intval($result['executed']),
            'tr_failed'        => intval($result['failed']),
        ), $redirect));
        exit;
    }

    /**
     * Render the Termination-Requests review page (ARCHITECTUR.md §8.7).
     */
    public function termination_requests_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Keine Berechtigung', 'themisdb-support-portal'));
        }

        // Status-Meldungen.
        if (!empty($_GET['tr_done'])) {
            $verb = $_GET['tr_done'] === 'approve'
                ? __('bestätigt', 'themisdb-support-portal')
                : __('abgelehnt', 'themisdb-support-portal');
            echo '<div class="notice notice-success is-dismissible"><p>'
                . esc_html(sprintf(__('Kündigungsantrag erfolgreich %s.', 'themisdb-support-portal'), $verb))
                . '</p></div>';
        }

        if (!empty($_GET['tr_run_done'])) {
            $executed = intval($_GET['tr_executed'] ?? 0);
            $failed   = intval($_GET['tr_failed']   ?? 0);
            echo '<div class="notice notice-success is-dismissible"><p>'
                . esc_html(sprintf(
                    __('Scheduler ausgeführt: %d Kündigung(en) ausgeführt, %d fehlgeschlagen.', 'themisdb-support-portal'),
                    $executed,
                    $failed
                ))
                . '</p></div>';
        }

        if (!empty($_GET['tr_error'])) {
            echo '<div class="notice notice-error is-dismissible"><p>'
                . esc_html(urldecode(sanitize_text_field(wp_unslash($_GET['tr_error']))))
                . '</p></div>';
        }

        if (!class_exists('ThemisDB_Contract_Termination_Engine') || !class_exists('ThemisDB_Contract_Lifecycle')) {
            echo '<div class="wrap"><p style="color:#e74c3c;">'
                . esc_html__('Termination-Engine oder Contract-Lifecycle nicht verfügbar. Bitte stellen Sie sicher, dass themisdb-order-request aktiv ist.', 'themisdb-support-portal')
                . '</p></div>';
            return;
        }

        // Filter.
        $filter_status = isset($_GET['tr_status']) ? sanitize_text_field(wp_unslash($_GET['tr_status'])) : '';
        $paged         = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page      = 20;

        $list_args = array('limit' => $per_page, 'offset' => ($paged - 1) * $per_page);
        if ($filter_status) {
            $list_args['status'] = $filter_status;
        }

        $requests = ThemisDB_Contract_Termination_Engine::list_termination_requests($list_args);

        // Zähle fällige (bestätigte, noch nicht ausgeführte) Kündigungen.
        $due_args     = array('status' => ThemisDB_Contract_Lifecycle::STATUS_CONFIRMED, 'limit' => 500);
        $all_confirmed = ThemisDB_Contract_Termination_Engine::list_termination_requests($due_args);
        $now_ts        = time();
        $due_count = 0;
        foreach ($all_confirmed as $cr) {
            if (!empty($cr['effective_at']) && strtotime($cr['effective_at']) <= $now_ts) {
                $due_count++;
            }
        }

        $status_options = array(
            ''          => __('Alle', 'themisdb-support-portal'),
            'requested' => __('Ausstehend', 'themisdb-support-portal'),
            'confirmed' => __('Bestätigt / geplant', 'themisdb-support-portal'),
            'rejected'  => __('Abgelehnt', 'themisdb-support-portal'),
            'executed'  => __('Ausgeführt', 'themisdb-support-portal'),
        );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Kündigungsanträge', 'themisdb-support-portal'); ?></h1>
            <p style="color:#666;"><?php esc_html_e('Eingehende Kündigungsanträge mit Wirkungsdatum-Scheduler. Bestätigte Anträge werden stündlich automatisch ausgeführt.', 'themisdb-support-portal'); ?></p>

            <!-- Scheduler-Info + manueller Trigger -->
            <div style="background:#f8f9fa;border:1px solid #ddd;border-radius:6px;padding:12px 16px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;gap:16px;">
                <div>
                    <strong><?php esc_html_e('Scheduler-Status', 'themisdb-support-portal'); ?></strong>
                    <?php
                    $next = wp_next_scheduled('themisdb_contract_lifecycle_execute');
                    if ($next) {
                        echo ' &mdash; <span style="color:#27ae60;">'
                            . esc_html(sprintf(__('Nächste Ausführung: %s', 'themisdb-support-portal'), date_i18n('d.m.Y H:i', $next)))
                            . '</span>';
                    } else {
                        echo ' &mdash; <span style="color:#e74c3c;">' . esc_html__('Kein Cron-Job geplant.', 'themisdb-support-portal') . '</span>';
                    }
                    ?>
                    <?php if ($due_count > 0): ?>
                        <span style="display:inline-block;margin-left:12px;background:#e74c3c;color:#fff;padding:2px 8px;border-radius:3px;font-size:12px;">
                            <?php echo esc_html(sprintf(_n('%d fällig', '%d fällig', $due_count, 'themisdb-support-portal'), $due_count)); ?>
                        </span>
                    <?php endif; ?>
                </div>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('themisdb_termination_run_now', '_wpnonce'); ?>
                    <input type="hidden" name="action" value="themisdb_termination_run_now">
                    <button type="submit" class="button" onclick="return confirm('<?php esc_attr_e('Scheduler jetzt ausführen? Alle fälligen Kündigungen werden sofort verarbeitet.', 'themisdb-support-portal'); ?>');">
                        <?php esc_html_e('Jetzt ausführen', 'themisdb-support-portal'); ?>
                    </button>
                </form>
            </div>

            <!-- Filter -->
            <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" style="display:flex;gap:10px;align-items:center;margin-bottom:16px;">
                <input type="hidden" name="page" value="themisdb-support-terminations">
                <select name="tr_status">
                    <?php foreach ($status_options as $val => $label): ?>
                        <option value="<?php echo esc_attr($val); ?>" <?php selected($filter_status, $val); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="button"><?php esc_html_e('Filtern', 'themisdb-support-portal'); ?></button>
                <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-support-terminations')); ?>" class="button button-secondary"><?php esc_html_e('Zurücksetzen', 'themisdb-support-portal'); ?></a>
            </form>

            <?php if (empty($requests)): ?>
                <p style="color:#888;"><?php esc_html_e('Keine Kündigungsanträge gefunden.', 'themisdb-support-portal'); ?></p>
            <?php else: ?>

                <table class="widefat fixed striped" style="margin-top:0;">
                    <thead>
                        <tr>
                            <th style="width:50px;"><?php esc_html_e('ID', 'themisdb-support-portal'); ?></th>
                            <th style="width:80px;"><?php esc_html_e('Lizenz', 'themisdb-support-portal'); ?></th>
                            <th><?php esc_html_e('Begründung', 'themisdb-support-portal'); ?></th>
                            <th style="width:120px;"><?php esc_html_e('Beantragt am', 'themisdb-support-portal'); ?></th>
                            <th style="width:120px;"><?php esc_html_e('Wirkungsdatum', 'themisdb-support-portal'); ?></th>
                            <th style="width:130px;"><?php esc_html_e('Status', 'themisdb-support-portal'); ?></th>
                            <th style="width:240px;"><?php esc_html_e('Aktion', 'themisdb-support-portal'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req):
                            $status  = isset($req['status'])     ? $req['status']     : 'requested';
                            $req_id  = isset($req['id'])         ? intval($req['id']) : 0;
                            $lic_id  = isset($req['license_id']) ? intval($req['license_id']) : 0;
                            $reason  = isset($req['reason'])     ? trim((string) $req['reason']) : '';
                            $eff     = !empty($req['effective_at']) ? mysql2date('d.m.Y', $req['effective_at']) : '—';
                            $is_due  = !empty($req['effective_at']) && strtotime($req['effective_at']) <= $now_ts;
                            $sc = ThemisDB_Contract_Termination_Engine::status_color($status);
                            $sl = ThemisDB_Contract_Termination_Engine::status_label($status);
                        ?>
                            <tr>
                                <td><?php echo esc_html((string)$req_id); ?></td>
                                <td><?php echo esc_html($lic_id > 0 ? (string)$lic_id : '—'); ?></td>
                                <td style="max-width:280px;word-break:break-word;"><?php echo esc_html($reason ? mb_strimwidth($reason, 0, 150, '…') : '—'); ?></td>
                                <td><?php echo esc_html(!empty($req['created_at']) ? mysql2date('d.m.Y', $req['created_at']) : '—'); ?></td>
                                <td>
                                    <?php echo esc_html($eff); ?>
                                    <?php if ($status === 'confirmed' && $is_due): ?>
                                        <span style="display:inline-block;background:#e74c3c;color:#fff;font-size:10px;padding:1px 4px;border-radius:2px;margin-left:4px;"><?php esc_html_e('fällig', 'themisdb-support-portal'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><span style="display:inline-block;padding:2px 8px;border-radius:3px;background:<?php echo esc_attr($sc); ?>;color:#fff;font-size:11px;font-weight:600;"><?php echo esc_html($sl); ?></span></td>
                                <td>
                                    <?php if ($status === 'requested'): ?>
                                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-flex;flex-wrap:wrap;gap:6px;align-items:center;">
                                            <?php wp_nonce_field('themisdb_termination_review', '_wpnonce'); ?>
                                            <input type="hidden" name="action" value="themisdb_termination_review">
                                            <input type="hidden" name="request_id" value="<?php echo esc_attr((string)$req_id); ?>">
                                            <input type="text" name="review_note" style="padding:3px 6px;border:1px solid #ddd;border-radius:3px;font-size:11px;width:120px;" placeholder="<?php esc_attr_e('Notiz…', 'themisdb-support-portal'); ?>">
                                            <button type="submit" name="tr_action" value="approve" class="button button-small" style="background:#27ae60;border-color:#219150;color:#fff;"><?php esc_html_e('Bestätigen', 'themisdb-support-portal'); ?></button>
                                            <button type="submit" name="tr_action" value="reject" class="button button-small" style="color:#e74c3c;border-color:#e74c3c;" onclick="return confirm('<?php esc_attr_e('Antrag wirklich ablehnen?', 'themisdb-support-portal'); ?>');"><?php esc_html_e('Ablehnen', 'themisdb-support-portal'); ?></button>
                                        </form>
                                    <?php elseif ($status === 'confirmed' && !empty($req['effective_at'])): ?>
                                        <span style="font-size:11px;color:#555;"><?php echo esc_html(sprintf(__('Ausführung am %s', 'themisdb-support-portal'), mysql2date('d.m.Y H:i', $req['effective_at']))); ?></span>
                                    <?php else: ?>
                                        <span style="color:#aaa;font-size:11px;"><?php echo esc_html($sl); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render the Mail-Log page (ARCHITECTUR.md §8.4).
     */
    public function mail_log_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Keine Berechtigung', 'themisdb-support-portal'));
        }

        if (!class_exists('ThemisDB_Mail_Orchestrator')) {
            echo '<div class="wrap"><p>' . esc_html__('Mail-Orchestrator nicht verfügbar.', 'themisdb-support-portal') . '</p></div>';
            return;
        }

        // Purge-Aktion.
        if (!empty($_GET['ml_purge']) && check_admin_referer('themisdb_ml_purge', '_wpnonce')) {
            $days    = isset($_GET['ml_purge_days']) ? max(1, intval($_GET['ml_purge_days'])) : 90;
            $deleted = ThemisDB_Mail_Orchestrator::purge_log($days);
            echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(
                /* translators: %d: Anzahl gelöschter Einträge */
                esc_html__('%d Einträge gelöscht.', 'themisdb-support-portal'),
                $deleted
            ) . '</p></div>';
        }

        // Filter-Parameter.
        $filter_event  = isset($_GET['ml_event'])  ? sanitize_text_field(wp_unslash($_GET['ml_event']))  : '';
        $filter_status = isset($_GET['ml_status']) ? sanitize_text_field(wp_unslash($_GET['ml_status'])) : '';
        $paged         = isset($_GET['paged'])      ? max(1, intval($_GET['paged']))                       : 1;
        $per_page      = 50;
        $offset        = ($paged - 1) * $per_page;

        $filter_args = array(
            'limit'  => $per_page,
            'offset' => $offset,
        );
        if ($filter_event)  { $filter_args['event_type'] = $filter_event; }
        if ($filter_status) { $filter_args['status']     = $filter_status; }

        $entries = ThemisDB_Mail_Orchestrator::get_log($filter_args);
        $total   = ThemisDB_Mail_Orchestrator::count_log($filter_args);
        $pages   = $total > 0 ? (int) ceil($total / $per_page) : 1;

        $purge_url = wp_nonce_url(
            admin_url('admin.php?page=themisdb-support-maillog&ml_purge=1&ml_purge_days=90'),
            'themisdb_ml_purge'
        );

        $all_events = array(
            ThemisDB_Mail_Orchestrator::EVENT_TICKET_CREATED,
            ThemisDB_Mail_Orchestrator::EVENT_TICKET_STATUS_CHANGED,
            ThemisDB_Mail_Orchestrator::EVENT_ORDER_CREATED,
            ThemisDB_Mail_Orchestrator::EVENT_ORDER_APPROVED,
            ThemisDB_Mail_Orchestrator::EVENT_ORDER_REJECTED,
            ThemisDB_Mail_Orchestrator::EVENT_LICENSE_ACTIVATED,
            ThemisDB_Mail_Orchestrator::EVENT_BUILD_COMPLETED,
            ThemisDB_Mail_Orchestrator::EVENT_BUILD_FAILED,
        );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Mail-Log', 'themisdb-support-portal'); ?></h1>
            <p style="color:#666;"><?php esc_html_e('Alle vom Mail-Orchestrator versendeten Lifecycle-Mails.', 'themisdb-support-portal'); ?></p>

            <!-- Filter-Leiste -->
            <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" style="display:flex;gap:10px;align-items:center;margin-bottom:16px;flex-wrap:wrap;">
                <input type="hidden" name="page" value="themisdb-support-maillog">

                <select name="ml_event">
                    <option value=""><?php esc_html_e('Alle Events', 'themisdb-support-portal'); ?></option>
                    <?php foreach ($all_events as $ev): ?>
                        <option value="<?php echo esc_attr($ev); ?>" <?php selected($filter_event, $ev); ?>>
                            <?php echo esc_html(ThemisDB_Mail_Orchestrator::event_label($ev)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="ml_status">
                    <option value=""><?php esc_html_e('Alle Status', 'themisdb-support-portal'); ?></option>
                    <option value="sent"   <?php selected($filter_status, 'sent');   ?>><?php esc_html_e('Gesendet', 'themisdb-support-portal'); ?></option>
                    <option value="failed" <?php selected($filter_status, 'failed'); ?>><?php esc_html_e('Fehlgeschlagen', 'themisdb-support-portal'); ?></option>
                </select>

                <button type="submit" class="button"><?php esc_html_e('Filtern', 'themisdb-support-portal'); ?></button>
                <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-support-maillog')); ?>" class="button button-secondary"><?php esc_html_e('Zurücksetzen', 'themisdb-support-portal'); ?></a>
                <span style="flex:1;"></span>
                <a href="<?php echo esc_url($purge_url); ?>" class="button" onclick="return confirm('<?php esc_attr_e('Einträge älter als 90 Tage löschen?', 'themisdb-support-portal'); ?>');"><?php esc_html_e('Alte Einträge bereinigen', 'themisdb-support-portal'); ?></a>
            </form>

            <p><?php printf(
                /* translators: %d: Anzahl Einträge */
                esc_html__('%d Einträge gefunden.', 'themisdb-support-portal'),
                $total
            ); ?></p>

            <table class="wp-list-table widefat fixed striped" style="font-size:13px;">
                <thead>
                    <tr>
                        <th style="width:160px;"><?php esc_html_e('Datum', 'themisdb-support-portal'); ?></th>
                        <th style="width:160px;"><?php esc_html_e('Event', 'themisdb-support-portal'); ?></th>
                        <th><?php esc_html_e('Empfänger', 'themisdb-support-portal'); ?></th>
                        <th><?php esc_html_e('Betreff', 'themisdb-support-portal'); ?></th>
                        <th style="width:90px;"><?php esc_html_e('Status', 'themisdb-support-portal'); ?></th>
                        <th style="width:200px;"><?php esc_html_e('Fehler', 'themisdb-support-portal'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($entries)): ?>
                        <tr><td colspan="6" style="text-align:center;color:#888;"><?php esc_html_e('Keine Einträge', 'themisdb-support-portal'); ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($entries as $row):
                            $status_color = $row['status'] === 'sent' ? '#27ae60' : '#e74c3c';
                            $status_label = $row['status'] === 'sent'
                                ? __('Gesendet', 'themisdb-support-portal')
                                : __('Fehler', 'themisdb-support-portal');
                            ?>
                            <tr>
                                <td><?php echo esc_html(mysql2date('d.m.Y H:i', $row['sent_at'])); ?></td>
                                <td><?php echo esc_html(ThemisDB_Mail_Orchestrator::event_label($row['event_type'])); ?></td>
                                <td><?php echo esc_html($row['recipient']); ?></td>
                                <td title="<?php echo esc_attr($row['subject']); ?>"><?php echo esc_html(mb_strimwidth($row['subject'], 0, 70, '…')); ?></td>
                                <td>
                                    <span style="display:inline-block;padding:2px 8px;border-radius:3px;background:<?php echo esc_attr($status_color); ?>;color:#fff;font-size:11px;font-weight:600;">
                                        <?php echo esc_html($status_label); ?>
                                    </span>
                                </td>
                                <td style="color:#e74c3c;font-size:11px;"><?php echo esc_html($row['error_msg'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($pages > 1): ?>
                <div style="margin-top:12px;">
                    <?php
                    $base_url = admin_url('admin.php?page=themisdb-support-maillog');
                    if ($filter_event)  { $base_url = add_query_arg('ml_event',  $filter_event,  $base_url); }
                    if ($filter_status) { $base_url = add_query_arg('ml_status', $filter_status, $base_url); }
                    for ($p = 1; $p <= $pages; $p++):
                        $url = add_query_arg('paged', $p, $base_url);
                        if ($p === $paged):
                            echo '<strong style="margin-right:4px;">' . esc_html((string)$p) . '</strong>';
                        else:
                            echo '<a href="' . esc_url($url) . '" style="margin-right:4px;">' . esc_html((string)$p) . '</a>';
                        endif;
                    endfor;
                    ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render the settings page.
     */
    public function settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Keine Berechtigung', 'themisdb-support-portal'));
        }

        $assignable_agents = $this->get_assignable_agents();

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
        $message   = isset($_POST['message'])   ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '';

        if (!$ticket_id || empty(trim($message))) {
            wp_send_json_error(array('message' => __('Fehlende Parameter', 'themisdb-support-portal')));
        }

        $ticket = ThemisDB_SupportPortal_Ticket_Manager::get_ticket($ticket_id);
        if (!$ticket) {
            wp_send_json_error(array('message' => __('Ticket nicht gefunden', 'themisdb-support-portal')));
        }

        $current_user = wp_get_current_user();

        $message_id = ThemisDB_SupportPortal_Ticket_Manager::add_message($ticket_id, array(
            'author_name'    => $current_user->display_name,
            'author_email'   => $current_user->user_email,
            'message'        => $message,
            'is_admin_reply' => true,
        ));

        if (!$message_id) {
            wp_send_json_error(array('message' => __('Antwort konnte nicht gespeichert werden', 'themisdb-support-portal')));
        }

        // If ticket was open, move it to in_progress
        if ($ticket['status'] === ThemisDB_SupportPortal_Ticket_Manager::STATUS_OPEN) {
            ThemisDB_SupportPortal_Ticket_Manager::update_ticket_status($ticket_id, ThemisDB_SupportPortal_Ticket_Manager::STATUS_IN_PROGRESS);
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

        $updated = ThemisDB_SupportPortal_Ticket_Manager::update_ticket_status($ticket_id, $status);

        if (!$updated) {
            wp_send_json_error(array('message' => __('Status konnte nicht aktualisiert werden', 'themisdb-support-portal')));
        }

        $status_labels = ThemisDB_SupportPortal_Ticket_Manager::get_status_labels();
        $count_summary = $this->get_ticket_count_summary();

        wp_send_json_success(array(
            'message'      => __('Status aktualisiert', 'themisdb-support-portal'),
            'status'       => $status,
            'status_label' => isset($status_labels[$status]) ? $status_labels[$status] : $status,
            'count_summary' => $count_summary,
        ));
    }

    /**
     * Handle AJAX quick assignee update from admin ticket list.
     */
    public function handle_quick_assign_ajax() {
        check_ajax_referer('themisdb_support_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Keine Berechtigung', 'themisdb-support-portal')));
        }

        $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
        $assignee_user_id = isset($_POST['assignee_user_id']) ? intval($_POST['assignee_user_id']) : 0;

        if ($ticket_id <= 0) {
            wp_send_json_error(array('message' => __('Ungueltige Ticket-ID', 'themisdb-support-portal')));
        }

        $updated = ThemisDB_SupportPortal_Ticket_Manager::update_ticket($ticket_id, array(
            'assignee_user_id' => $assignee_user_id,
        ));

        if (!$updated) {
            wp_send_json_error(array('message' => __('Bearbeiter konnte nicht aktualisiert werden', 'themisdb-support-portal')));
        }

        $ticket = ThemisDB_SupportPortal_Ticket_Manager::get_ticket($ticket_id);
        $assignee_label = __('Nicht zugewiesen', 'themisdb-support-portal');
        if ($ticket && !empty($ticket['assignee_user_id'])) {
            $assignee_user = get_user_by('id', intval($ticket['assignee_user_id']));
            if ($assignee_user instanceof WP_User) {
                $assignee_label = sprintf('%s (%s)', $assignee_user->display_name, $assignee_user->user_email);
            }
        }

        wp_send_json_success(array(
            'message' => __('Bearbeiter aktualisiert', 'themisdb-support-portal'),
            'assignee_label' => $assignee_label,
            'assignee_user_id' => $ticket && isset($ticket['assignee_user_id']) ? intval($ticket['assignee_user_id']) : 0,
        ));
    }

    /**
     * Handle bulk ticket actions via AJAX.
     */
    public function handle_bulk_action_ajax() {
        check_ajax_referer('themisdb_support_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Keine Berechtigung', 'themisdb-support-portal')));
        }

        $bulk_action = $this->get_bulk_action_from_request($_POST);
        $ticket_ids = isset($_POST['ticket_ids']) ? array_map('intval', (array) $_POST['ticket_ids']) : array();

        if ($bulk_action === '' || empty($ticket_ids)) {
            wp_send_json_error(array('message' => __('Bitte Aktion und mindestens ein Ticket auswaehlen.', 'themisdb-support-portal')));
        }

        $result = $this->process_bulk_ticket_action($bulk_action, $ticket_ids);
        $response = array(
            'message' => $result['message'],
            'notice_type' => $result['notice_type'],
            'effect' => $result['effect'],
            'count' => $result['count'],
            'ticket_ids' => array_values(array_map('intval', $ticket_ids)),
        );

        if (isset($result['status'])) {
            $response['status'] = $result['status'];
        }
        if (isset($result['assignee_user_id'])) {
            $response['assignee_user_id'] = intval($result['assignee_user_id']);
        }
        $response['count_summary'] = $this->get_ticket_count_summary();

        if ($result['notice_type'] === 'error') {
            wp_send_json_error($response);
        }

        wp_send_json_success($response);
    }
}
