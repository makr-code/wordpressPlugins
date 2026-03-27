<?php
/**
 * Support ticket manager with optional GitHub Issue sync.
 */

if (!defined('ABSPATH')) {
    exit;
}

class ThemisDB_Order_Support_Ticket_Manager {

    public static function init() {
        // Reserved for future hooks.
    }

    public static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'themisdb_support_tickets';
    }

    public static function get_priorities() {
        return array(
            'low' => __('Niedrig', 'themisdb-order-request'),
            'normal' => __('Normal', 'themisdb-order-request'),
            'high' => __('Hoch', 'themisdb-order-request'),
            'urgent' => __('Dringend', 'themisdb-order-request'),
        );
    }

    public static function get_statuses() {
        return array(
            'open' => __('Offen', 'themisdb-order-request'),
            'in_progress' => __('In Bearbeitung', 'themisdb-order-request'),
            'resolved' => __('Gelost', 'themisdb-order-request'),
            'closed' => __('Geschlossen', 'themisdb-order-request'),
        );
    }

    public static function create_ticket($data) {
        global $wpdb;

        $table = self::get_table_name();
        $subject = sanitize_text_field($data['subject'] ?? '');
        $description = wp_kses_post($data['description'] ?? '');
        $customer_email = sanitize_email($data['customer_email'] ?? '');
        $priority = sanitize_key($data['priority'] ?? 'normal');
        $status = sanitize_key($data['status'] ?? 'open');

        if ($subject === '' || $description === '') {
            return new WP_Error('invalid_ticket', __('Betreff und Beschreibung sind erforderlich.', 'themisdb-order-request'));
        }

        if ($customer_email !== '' && !is_email($customer_email)) {
            return new WP_Error('invalid_email', __('Ungultige E-Mail-Adresse.', 'themisdb-order-request'));
        }

        if (!array_key_exists($priority, self::get_priorities())) {
            $priority = 'normal';
        }

        if (!array_key_exists($status, self::get_statuses())) {
            $status = 'open';
        }

        $benefit_id = isset($data['benefit_id']) ? intval($data['benefit_id']) : 0;
        if ($benefit_id > 0 && class_exists('ThemisDB_Support_Benefits_Manager')) {
            $limit_check = ThemisDB_Support_Benefits_Manager::check_limits($benefit_id, $priority);
            if (empty($limit_check['allowed'])) {
                return new WP_Error(
                    'support_limit_reached',
                    isset($limit_check['reason']) ? (string) $limit_check['reason'] : __('Support-Limit erreicht.', 'themisdb-order-request'),
                    array('limits' => $limit_check)
                );
            }
        }

        $inserted = $wpdb->insert(
            $table,
            array(
                'benefit_id' => $benefit_id > 0 ? $benefit_id : null,
                'license_id' => isset($data['license_id']) ? intval($data['license_id']) : null,
                'order_id' => isset($data['order_id']) ? intval($data['order_id']) : null,
                'customer_email' => $customer_email,
                'subject' => $subject,
                'description' => $description,
                'priority' => $priority,
                'status' => $status,
                'created_by' => get_current_user_id() ?: null,
            ),
            array('%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d')
        );

        if (!$inserted) {
            return new WP_Error('db_insert_failed', __('Ticket konnte nicht gespeichert werden.', 'themisdb-order-request'));
        }

        $ticket_id = intval($wpdb->insert_id);

        if ($benefit_id > 0 && class_exists('ThemisDB_Support_Benefits_Manager')) {
            ThemisDB_Support_Benefits_Manager::increment_ticket_usage($benefit_id);
        }

        if (!empty($data['auto_sync_github'])) {
            self::create_github_issue_for_ticket($ticket_id);
        }

        /**
         * Fires after a support ticket is created in the order plugin.
         *
         * @param int   $ticket_id New ticket ID.
         * @param array $payload   Raw ticket creation payload.
         */
        do_action('themisdb_order_support_ticket_created', $ticket_id, $data);

        return $ticket_id;
    }

    public static function get_tickets($args = array()) {
        global $wpdb;

        $table = self::get_table_name();
        $defaults = array(
            'status' => '',
            'limit' => 100,
            'offset' => 0,
        );
        $args = wp_parse_args($args, $defaults);

        $limit = max(1, min(500, intval($args['limit'])));
        $offset = max(0, intval($args['offset']));

        $where = '1=1';
        $params = array();

        if ($args['status'] !== '') {
            $where .= ' AND status = %s';
            $params[] = sanitize_key($args['status']);
        }

        $sql = "SELECT * FROM {$table} WHERE {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;

        return $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
    }

    public static function get_ticket($ticket_id) {
        global $wpdb;

        $table = self::get_table_name();
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", intval($ticket_id)),
            ARRAY_A
        );
    }

    public static function create_github_issue_for_ticket($ticket_id, $force = false) {
        global $wpdb;

        $table = self::get_table_name();
        $ticket = self::get_ticket($ticket_id);

        if (!$ticket) {
            return array(
                'success' => false,
                'message' => __('Ticket nicht gefunden.', 'themisdb-order-request'),
            );
        }

        if (!$force && !empty($ticket['github_issue_number'])) {
            return array(
                'success' => true,
                'message' => __('Ticket ist bereits mit einem GitHub-Issue verknupft.', 'themisdb-order-request'),
                'issue_number' => intval($ticket['github_issue_number']),
                'issue_url' => (string) $ticket['github_issue_url'],
            );
        }

        $settings = self::get_github_settings();
        if (!$settings['enabled']) {
            return array(
                'success' => false,
                'message' => __('GitHub-Sync ist deaktiviert.', 'themisdb-order-request'),
            );
        }

        if ($settings['token'] === '' || $settings['repository'] === '') {
            return array(
                'success' => false,
                'message' => __('GitHub Repository oder Token fehlt.', 'themisdb-order-request'),
            );
        }

        $repo_parts = explode('/', $settings['repository']);
        if (count($repo_parts) !== 2 || trim($repo_parts[0]) === '' || trim($repo_parts[1]) === '') {
            return array(
                'success' => false,
                'message' => __('GitHub Repository muss im Format owner/repo angegeben sein.', 'themisdb-order-request'),
            );
        }

        $repo_owner = rawurlencode(trim($repo_parts[0]));
        $repo_name = rawurlencode(trim($repo_parts[1]));
        $url = "https://api.github.com/repos/{$repo_owner}/{$repo_name}/issues";

        $title = sprintf('[Support Ticket #%d] %s', intval($ticket['id']), (string) $ticket['subject']);
        $body = self::build_github_issue_body($ticket);

        $labels = array_filter(array_map('trim', explode(',', $settings['labels'])));
        $labels[] = 'source:wordpress-support';
        $labels[] = 'priority:' . sanitize_key($ticket['priority']);
        $labels = array_values(array_unique($labels));

        $request_args = array(
            'timeout' => 20,
            'headers' => array(
                'Accept' => 'application/vnd.github+json',
                'Authorization' => 'Bearer ' . $settings['token'],
                'X-GitHub-Api-Version' => '2022-11-28',
                'User-Agent' => 'ThemisDB-Order-Request/' . (defined('THEMISDB_ORDER_VERSION') ? THEMISDB_ORDER_VERSION : '1.0.0'),
            ),
            'body' => wp_json_encode(array(
                'title' => $title,
                'body' => $body,
                'labels' => $labels,
            )),
        );

        if (!function_exists('themisdb_github_bridge_request')) {
            $error_message = __('ThemisDB GitHub Bridge ist erforderlich.', 'themisdb-order-request');
            self::set_sync_error($table, $ticket_id, $error_message);
            return array(
                'success' => false,
                'message' => $error_message,
            );
        }

        $response = themisdb_github_bridge_request('POST', $url, $request_args);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            self::set_sync_error($table, $ticket_id, $error_message);
            return array(
                'success' => false,
                'message' => $error_message,
            );
        }

        $status_code = is_array($response) ? (int) ($response['status_code'] ?? 0) : wp_remote_retrieve_response_code($response);
        $response_body = is_array($response) ? (string) ($response['body'] ?? '') : (string) wp_remote_retrieve_body($response);

        if ($status_code < 200 || $status_code >= 300) {
            $error_message = $response_body !== '' ? $response_body : __('GitHub-Issue konnte nicht erstellt werden.', 'themisdb-order-request');
            self::set_sync_error($table, $ticket_id, $error_message);
            return array(
                'success' => false,
                'status_code' => intval($status_code),
                'message' => $error_message,
            );
        }

        $payload = json_decode($response_body, true);
        $issue_number = isset($payload['number']) ? intval($payload['number']) : null;
        $issue_url = isset($payload['html_url']) ? esc_url_raw($payload['html_url']) : '';
        $issue_state = isset($payload['state']) ? sanitize_key($payload['state']) : 'open';

        $wpdb->update(
            $table,
            array(
                'github_issue_number' => $issue_number,
                'github_issue_url' => $issue_url,
                'github_issue_state' => $issue_state,
                'github_synced_at' => current_time('mysql'),
                'github_sync_error' => '',
            ),
            array('id' => intval($ticket_id)),
            array('%d', '%s', '%s', '%s', '%s'),
            array('%d')
        );

        return array(
            'success' => true,
            'issue_number' => $issue_number,
            'issue_url' => $issue_url,
            'message' => __('GitHub-Issue erfolgreich erstellt.', 'themisdb-order-request'),
        );
    }

    public static function refresh_github_issue_status($ticket_id) {
        global $wpdb;

        $table = self::get_table_name();
        $ticket = self::get_ticket($ticket_id);

        if (!$ticket) {
            return array(
                'success' => false,
                'message' => __('Ticket nicht gefunden.', 'themisdb-order-request'),
            );
        }

        $issue_number = isset($ticket['github_issue_number']) ? intval($ticket['github_issue_number']) : 0;
        if ($issue_number <= 0) {
            return array(
                'success' => false,
                'message' => __('Ticket ist noch nicht mit einem GitHub-Issue verknupft.', 'themisdb-order-request'),
            );
        }

        if (!function_exists('themisdb_github_bridge_request')) {
            $error_message = __('ThemisDB GitHub Bridge ist erforderlich.', 'themisdb-order-request');
            self::set_sync_error($table, $ticket_id, $error_message);
            return array(
                'success' => false,
                'message' => $error_message,
            );
        }

        $settings = self::get_github_settings();
        $issue_url = self::build_github_issue_api_url($settings['repository'], $issue_number);
        if ($issue_url === '') {
            return array(
                'success' => false,
                'message' => __('GitHub Repository muss im Format owner/repo angegeben sein.', 'themisdb-order-request'),
            );
        }

        $request_args = array(
            'timeout' => 20,
            'headers' => array(
                'Accept' => 'application/vnd.github+json',
            ),
        );

        if ($settings['token'] !== '') {
            $request_args['headers']['Authorization'] = 'Bearer ' . $settings['token'];
        }

        $response = themisdb_github_bridge_request('GET', $issue_url, $request_args);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            self::set_sync_error($table, $ticket_id, $error_message);
            return array(
                'success' => false,
                'message' => $error_message,
            );
        }

        $status_code = is_array($response) ? (int) ($response['status_code'] ?? 0) : wp_remote_retrieve_response_code($response);
        $response_body = is_array($response) ? (string) ($response['body'] ?? '') : (string) wp_remote_retrieve_body($response);

        if ($status_code < 200 || $status_code >= 300) {
            $error_message = $response_body !== '' ? $response_body : __('GitHub-Issue-Status konnte nicht geladen werden.', 'themisdb-order-request');
            self::set_sync_error($table, $ticket_id, $error_message);
            return array(
                'success' => false,
                'status_code' => intval($status_code),
                'message' => $error_message,
            );
        }

        $payload = json_decode($response_body, true);
        $issue_state = isset($payload['state']) ? sanitize_key($payload['state']) : '';
        $issue_html_url = isset($payload['html_url']) ? esc_url_raw($payload['html_url']) : '';

        if ($issue_state === '') {
            $error_message = __('GitHub-Issue-Status konnte nicht ausgelesen werden.', 'themisdb-order-request');
            self::set_sync_error($table, $ticket_id, $error_message);
            return array(
                'success' => false,
                'message' => $error_message,
            );
        }

        $wpdb->update(
            $table,
            array(
                'github_issue_state' => $issue_state,
                'github_issue_url' => $issue_html_url !== '' ? $issue_html_url : (string) ($ticket['github_issue_url'] ?? ''),
                'github_synced_at' => current_time('mysql'),
                'github_sync_error' => '',
            ),
            array('id' => intval($ticket_id)),
            array('%s', '%s', '%s', '%s'),
            array('%d')
        );

        return array(
            'success' => true,
            'state' => $issue_state,
            'issue_url' => $issue_html_url,
            'message' => __('GitHub-Issue-Status aktualisiert.', 'themisdb-order-request'),
        );
    }

    public static function refresh_github_issue_statuses_batch($args = array()) {
        global $wpdb;

        $defaults = array(
            'limit' => 25,
            'ticket_statuses' => array('open', 'in_progress'),
        );
        $args = wp_parse_args($args, $defaults);

        $settings = self::get_github_settings();
        if (!$settings['enabled']) {
            return array(
                'processed' => 0,
                'updated' => 0,
                'failed' => 0,
                'skipped' => 0,
                'message' => __('GitHub-Sync ist deaktiviert.', 'themisdb-order-request'),
            );
        }

        $ticket_statuses = array_values(array_filter(array_map('sanitize_key', (array) $args['ticket_statuses'])));
        if (empty($ticket_statuses)) {
            $ticket_statuses = array('open', 'in_progress');
        }

        $limit = max(1, min(200, intval($args['limit'])));
        $table = self::get_table_name();

        if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
            return array(
                'processed' => 0,
                'updated' => 0,
                'failed' => 0,
                'skipped' => 0,
                'message' => __('Interner Tabellenname ist ungueltig.', 'themisdb-order-request'),
            );
        }

        $status_placeholders = implode(',', array_fill(0, count($ticket_statuses), '%s'));
        $sql = "SELECT id FROM {$table}
                WHERE github_issue_number IS NOT NULL
                  AND github_issue_number > 0
                  AND status IN ({$status_placeholders})
                ORDER BY github_synced_at ASC, updated_at ASC
                LIMIT %d";

        $query_params = array_merge($ticket_statuses, array($limit));
        $ticket_ids = $wpdb->get_col($wpdb->prepare($sql, $query_params));

        $result = array(
            'processed' => 0,
            'updated' => 0,
            'failed' => 0,
            'skipped' => 0,
        );

        foreach ((array) $ticket_ids as $ticket_id_raw) {
            $ticket_id = intval($ticket_id_raw);
            if ($ticket_id <= 0) {
                $result['skipped']++;
                continue;
            }

            $result['processed']++;
            $refresh = self::refresh_github_issue_status($ticket_id);
            if (!empty($refresh['success'])) {
                $result['updated']++;
            } else {
                $result['failed']++;
            }
        }

        return $result;
    }

    private static function build_github_issue_api_url($repository, $issue_number) {
        $repository = trim((string) $repository);
        $issue_number = intval($issue_number);

        if ($issue_number <= 0) {
            return '';
        }

        $repo_parts = explode('/', $repository);
        if (count($repo_parts) !== 2 || trim($repo_parts[0]) === '' || trim($repo_parts[1]) === '') {
            return '';
        }

        if (function_exists('themisdb_github_bridge_build_repo_api_url')) {
            return themisdb_github_bridge_build_repo_api_url($repository, '/issues/' . $issue_number);
        }

        $repo_owner = rawurlencode(trim($repo_parts[0]));
        $repo_name = rawurlencode(trim($repo_parts[1]));

        return "https://api.github.com/repos/{$repo_owner}/{$repo_name}/issues/{$issue_number}";
    }

    private static function build_github_issue_body($ticket) {
        $lines = array();

        $lines[] = '## Ticket-Metadaten';
        $lines[] = '- Ticket-ID: #' . intval($ticket['id']);
        $lines[] = '- Prioritat: ' . sanitize_text_field($ticket['priority']);
        $lines[] = '- Status: ' . sanitize_text_field($ticket['status']);

        if (!empty($ticket['customer_email'])) {
            $lines[] = '- Kunde: ' . sanitize_email($ticket['customer_email']);
        }

        if (!empty($ticket['license_id'])) {
            $lines[] = '- License-ID: ' . intval($ticket['license_id']);
        }

        if (!empty($ticket['order_id'])) {
            $lines[] = '- Order-ID: ' . intval($ticket['order_id']);
        }

        if (!empty($ticket['benefit_id'])) {
            $lines[] = '- Support-Benefit-ID: ' . intval($ticket['benefit_id']);
        }

        $lines[] = '- Erfasst am: ' . sanitize_text_field((string) $ticket['created_at']);
        $lines[] = '';
        $lines[] = '## Beschreibung';
        $lines[] = trim(wp_strip_all_tags((string) $ticket['description']));

        return implode("\n", $lines);
    }

    private static function set_sync_error($table, $ticket_id, $error_message) {
        global $wpdb;

        $wpdb->update(
            $table,
            array(
                'github_sync_error' => sanitize_text_field($error_message),
            ),
            array('id' => intval($ticket_id)),
            array('%s'),
            array('%d')
        );
    }

    public static function get_github_settings() {
        return array(
            'enabled' => get_option('themisdb_support_github_enabled', '0') === '1',
            'token' => (string) get_option('themisdb_support_github_token', ''),
            'repository' => trim((string) get_option('themisdb_support_github_repository', '')),
            'labels' => trim((string) get_option('themisdb_support_github_labels', 'support,themisdb')),
        );
    }
}
