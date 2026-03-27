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
