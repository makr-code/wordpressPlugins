<?php
/**
 * Contract lifecycle workflows (termination + change requests).
 */

if (!defined('ABSPATH')) {
    exit;
}

class ThemisDB_Contract_Lifecycle {

    const STATUS_REQUESTED = 'requested';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_REJECTED  = 'rejected';
    const STATUS_EXECUTED  = 'executed';

    const TYPE_TERMINATION = 'termination';
    const TYPE_CHANGE      = 'change';

    public static function init() {
        if (!wp_next_scheduled('themisdb_contract_lifecycle_execute')) {
            wp_schedule_event(time(), 'hourly', 'themisdb_contract_lifecycle_execute');
        }

        add_action('themisdb_contract_lifecycle_execute', array(__CLASS__, 'execute_due_terminations'));
    }

    public static function create_tables() {
        global $wpdb;

        $table = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            request_type varchar(30) NOT NULL,
            status varchar(30) NOT NULL DEFAULT 'requested',
            license_id bigint(20) unsigned DEFAULT NULL,
            order_id bigint(20) unsigned DEFAULT NULL,
            requested_by bigint(20) unsigned DEFAULT NULL,
            requested_end_date datetime DEFAULT NULL,
            reason text DEFAULT NULL,
            payload longtext DEFAULT NULL,
            reviewed_by bigint(20) unsigned DEFAULT NULL,
            review_note text DEFAULT NULL,
            reviewed_at datetime DEFAULT NULL,
            effective_at datetime DEFAULT NULL,
            executed_at datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY request_type (request_type),
            KEY status (status),
            KEY license_id (license_id),
            KEY effective_at (effective_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public static function request_termination($license_id, $requested_end_date = '', $reason = '', $requested_by = 0) {
        global $wpdb;

        $license_id = intval($license_id);
        if ($license_id <= 0) {
            return new WP_Error('invalid_license', __('Ungueltige Lizenz-ID.', 'themisdb-order-request'));
        }

        if (!class_exists('ThemisDB_License_Manager')) {
            return new WP_Error('license_manager_missing', __('License Manager nicht verfuegbar.', 'themisdb-order-request'));
        }

        $license = ThemisDB_License_Manager::get_license($license_id);
        if (!$license) {
            return new WP_Error('license_not_found', __('Lizenz wurde nicht gefunden.', 'themisdb-order-request'));
        }

        if (isset($license['license_status']) && $license['license_status'] === 'cancelled') {
            return new WP_Error('license_already_cancelled', __('Lizenz ist bereits gekuendigt.', 'themisdb-order-request'));
        }

        // Business rule: block if open invoices exist (via order plugin).
        $rules_error = self::check_termination_preconditions($license_id, $license);
        if (is_wp_error($rules_error)) {
            return $rules_error;
        }

        $effective_at = self::resolve_termination_effective_date($requested_end_date, $license);
        $table = self::get_table_name();

        $inserted = $wpdb->insert(
            $table,
            array(
                'request_type' => self::TYPE_TERMINATION,
                'status' => self::STATUS_REQUESTED,
                'license_id' => $license_id,
                'order_id' => isset($license['order_id']) ? intval($license['order_id']) : null,
                'requested_by' => $requested_by > 0 ? intval($requested_by) : (get_current_user_id() ?: null),
                'requested_end_date' => $effective_at,
                'reason' => sanitize_textarea_field((string) $reason),
                'effective_at' => $effective_at,
            ),
            array('%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s')
        );

        if (!$inserted) {
            return new WP_Error('termination_insert_failed', __('Kuendigungsantrag konnte nicht gespeichert werden.', 'themisdb-order-request'));
        }

        $request_id = intval($wpdb->insert_id);

        do_action('contract.termination.requested', $request_id, $license_id);
        self::notify_admin_request_created($request_id, self::TYPE_TERMINATION, $license_id);

        return $request_id;
    }

    public static function request_change($license_id, $change_payload = array(), $reason = '', $requested_by = 0) {
        global $wpdb;

        $license_id = intval($license_id);
        if ($license_id <= 0) {
            return new WP_Error('invalid_license', __('Ungueltige Lizenz-ID.', 'themisdb-order-request'));
        }

        if (!class_exists('ThemisDB_License_Manager')) {
            return new WP_Error('license_manager_missing', __('License Manager nicht verfuegbar.', 'themisdb-order-request'));
        }

        $license = ThemisDB_License_Manager::get_license($license_id);
        if (!$license) {
            return new WP_Error('license_not_found', __('Lizenz wurde nicht gefunden.', 'themisdb-order-request'));
        }

        $table = self::get_table_name();
        $inserted = $wpdb->insert(
            $table,
            array(
                'request_type' => self::TYPE_CHANGE,
                'status' => self::STATUS_REQUESTED,
                'license_id' => $license_id,
                'order_id' => isset($license['order_id']) ? intval($license['order_id']) : null,
                'requested_by' => $requested_by > 0 ? intval($requested_by) : (get_current_user_id() ?: null),
                'reason' => sanitize_textarea_field((string) $reason),
                'payload' => wp_json_encode((array) $change_payload),
            ),
            array('%s', '%s', '%d', '%d', '%d', '%s', '%s')
        );

        if (!$inserted) {
            return new WP_Error('change_insert_failed', __('Aenderungsantrag konnte nicht gespeichert werden.', 'themisdb-order-request'));
        }

        $request_id = intval($wpdb->insert_id);

        do_action('contract.change.requested', $request_id, $license_id, (array) $change_payload);
        self::notify_admin_request_created($request_id, self::TYPE_CHANGE, $license_id);

        return $request_id;
    }

    public static function review_request($request_id, $approve, $review_note = '', $reviewed_by = 0) {
        global $wpdb;

        $request_id = intval($request_id);
        $request = self::get_request($request_id);
        if (!$request) {
            return new WP_Error('request_not_found', __('Lifecycle-Antrag nicht gefunden.', 'themisdb-order-request'));
        }

        if ($request['status'] !== self::STATUS_REQUESTED) {
            return new WP_Error('request_not_pending', __('Antrag ist nicht mehr im offenen Status.', 'themisdb-order-request'));
        }

        $new_status = $approve ? self::STATUS_CONFIRMED : self::STATUS_REJECTED;
        $reviewed_by = $reviewed_by > 0 ? intval($reviewed_by) : (get_current_user_id() ?: 0);

        $update_data = array(
            'status' => $new_status,
            'reviewed_by' => $reviewed_by > 0 ? $reviewed_by : null,
            'review_note' => sanitize_textarea_field((string) $review_note),
            'reviewed_at' => current_time('mysql'),
        );
        $update_formats = array('%s', '%d', '%s', '%s');

        // For immediate termination requests set an effective timestamp if missing.
        if ($approve && $request['request_type'] === self::TYPE_TERMINATION && empty($request['effective_at'])) {
            $update_data['effective_at'] = current_time('mysql');
            $update_formats[] = '%s';
        }

        $wpdb->update(
            self::get_table_name(),
            $update_data,
            array('id' => $request_id),
            $update_formats,
            array('%d')
        );

        if ($approve) {
            if ($request['request_type'] === self::TYPE_CHANGE) {
                self::execute_change_request($request_id);
                do_action('contract.change.approved', $request_id, intval($request['license_id']));
            } else {
                do_action('contract.termination.confirmed', $request_id, intval($request['license_id']));
            }
        } else {
            if ($request['request_type'] === self::TYPE_CHANGE) {
                do_action('contract.change.rejected', $request_id, intval($request['license_id']));
            } else {
                do_action('contract.termination.rejected', $request_id, intval($request['license_id']));
            }
        }

        self::notify_customer_review_result($request_id, $new_status);

        return true;
    }

    public static function list_requests($args = array()) {
        global $wpdb;

        $defaults = array(
            'request_type' => '',
            'status'       => '',
            'license_id'   => 0,
            'limit'        => 100,
            'offset'       => 0,
        );
        $args = wp_parse_args($args, $defaults);

        $where  = array('1=1');
        $params = array();

        if ($args['request_type'] !== '') {
            $where[]  = 'request_type = %s';
            $params[] = sanitize_key($args['request_type']);
        }
        if ($args['status'] !== '') {
            $where[]  = 'status = %s';
            $params[] = sanitize_key($args['status']);
        }
        if (intval($args['license_id']) > 0) {
            $where[]  = 'license_id = %d';
            $params[] = intval($args['license_id']);
        }

        $limit = max(1, min(500, intval($args['limit'])));
        $offset = max(0, intval($args['offset']));

        $sql = 'SELECT * FROM ' . self::get_table_name() . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY created_at DESC LIMIT %d OFFSET %d';
        $params[] = $limit;
        $params[] = $offset;

        return $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
    }

    public static function execute_due_terminations() {
        global $wpdb;

        $table = self::get_table_name();
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table}
                 WHERE request_type = %s
                   AND status = %s
                   AND executed_at IS NULL
                   AND effective_at IS NOT NULL
                   AND effective_at <= %s
                 ORDER BY effective_at ASC
                 LIMIT 50",
                self::TYPE_TERMINATION,
                self::STATUS_CONFIRMED,
                current_time('mysql')
            ),
            ARRAY_A
        );

        if (empty($rows)) {
            return array('processed' => 0, 'executed' => 0, 'failed' => 0);
        }

        $result = array('processed' => 0, 'executed' => 0, 'failed' => 0);

        foreach ($rows as $row) {
            $result['processed']++;
            $license_id = intval($row['license_id']);
            $reason = (string) ($row['reason'] ?? 'Scheduled termination');

            $cancelled = class_exists('ThemisDB_License_Manager')
                ? ThemisDB_License_Manager::cancel_license($license_id, $reason, 0)
                : false;

            if ($cancelled) {
                $wpdb->update(
                    $table,
                    array(
                        'status' => self::STATUS_EXECUTED,
                        'executed_at' => current_time('mysql'),
                    ),
                    array('id' => intval($row['id'])),
                    array('%s', '%s'),
                    array('%d')
                );

                do_action('contract.termination.executed', intval($row['id']), $license_id);
                self::notify_customer_termination_executed(intval($row['id']));
                $result['executed']++;
            } else {
                $result['failed']++;
            }
        }

        return $result;
    }

    public static function get_request($request_id) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM " . self::get_table_name() . " WHERE id = %d",
                intval($request_id)
            ),
            ARRAY_A
        );
    }

    // -------------------------------------------------------------------------
    // Business rules helpers
    // -------------------------------------------------------------------------

    /**
     * Verify pre-conditions before accepting a termination request.
     * Returns WP_Error or null.
     */
    private static function check_termination_preconditions($license_id, $license) {
        // Duplicate open termination request?
        global $wpdb;
        $existing = $wpdb->get_var(
            $wpdb->prepare(
                'SELECT COUNT(*) FROM ' . self::get_table_name() . ' WHERE license_id = %d AND request_type = %s AND status IN (%s, %s)',
                intval($license_id), self::TYPE_TERMINATION, self::STATUS_REQUESTED, self::STATUS_CONFIRMED
            )
        );
        if (intval($existing) > 0) {
            return new WP_Error('duplicate_termination', __('Fuer diese Lizenz existiert bereits ein offener Kuendigungsantrag.', 'themisdb-order-request'));
        }

        // Minimum contract term: block if license created less than 30 days ago
        // and no override filter is active.
        $min_days = intval(apply_filters('themisdb_contract_min_term_days', 30, $license));
        if ($min_days > 0 && !empty($license['created_at'])) {
            $created_ts = strtotime($license['created_at']);
            if ($created_ts && (time() - $created_ts) < ($min_days * DAY_IN_SECONDS)) {
                return new WP_Error(
                    'min_term_not_met',
                    sprintf(
                        /* translators: %d: minimum days */
                        __('Kuendigung erst nach %d Tagen Mindestlaufzeit moeglich.', 'themisdb-order-request'),
                        $min_days
                    )
                );
            }
        }

        // Open invoices block (if order-plugin table accessible)
        if (apply_filters('themisdb_termination_block_on_open_invoices', true, $license_id, $license)) {
            $order_id = isset($license['order_id']) ? intval($license['order_id']) : 0;
            if ($order_id > 0) {
                $invoices_table = $wpdb->prefix . 'themisdb_invoices';
                if ($wpdb->get_var("SHOW TABLES LIKE '{$invoices_table}'") === $invoices_table) {
                    $open_count = intval($wpdb->get_var(
                        $wpdb->prepare(
                            "SELECT COUNT(*) FROM {$invoices_table} WHERE order_id = %d AND status IN ('open','overdue')",
                            $order_id
                        )
                    ));
                    if ($open_count > 0) {
                        return new WP_Error(
                            'open_invoices',
                            sprintf(
                                /* translators: %d: open invoice count */
                                __('Kuendigung nicht moeglich: Es bestehen %d offene Rechnungen.', 'themisdb-order-request'),
                                $open_count
                            )
                        );
                    }
                }
            }
        }

        return null;
    }

    /**
     * Determine the effective termination date respecting notice period.
     * Falls back to NOTICE_PERIOD_DAYS after today if no date given or date is too early.
     */
    private static function resolve_termination_effective_date($requested_end_date, $license) {
        $notice_days = intval(apply_filters('themisdb_contract_notice_period_days', 30, $license));
        $earliest_ts = time() + ($notice_days * DAY_IN_SECONDS);

        if (!empty($requested_end_date)) {
            $requested_ts = strtotime($requested_end_date);
            if ($requested_ts && $requested_ts >= $earliest_ts) {
                return gmdate('Y-m-d H:i:s', $requested_ts);
            }

            // Requested date is too early: snap to earliest
            return gmdate('Y-m-d H:i:s', $earliest_ts);
        }

        return gmdate('Y-m-d H:i:s', $earliest_ts);
    }

    private static function notify_admin_request_created($request_id, $request_type, $license_id) {
        $admin_email = get_option('admin_email');
        if (!is_email($admin_email)) {
            return;
        }

        $subject = sprintf('[ThemisDB] Neuer %s Antrag #%d', $request_type === self::TYPE_TERMINATION ? 'Kuendigungs' : 'Aenderungs', intval($request_id));
        $body = sprintf(
            "Ein neuer Lifecycle-Antrag wurde erstellt.\n\nAntrag-ID: %d\nTyp: %s\nLizenz-ID: %d\n\nBitte im Admin pruefen.",
            intval($request_id),
            $request_type,
            intval($license_id)
        );

        wp_mail($admin_email, $subject, $body);
    }

    private static function notify_customer_review_result($request_id, $status) {
        $request = self::get_request($request_id);
        if (!$request) {
            return;
        }

        $to = self::get_customer_email_by_license_id(intval($request['license_id']));
        if (!is_email($to)) {
            return;
        }

        $is_approved = $status === self::STATUS_CONFIRMED;
        $subject = sprintf(
            '[ThemisDB] Ihr %s Antrag #%d wurde %s',
            $request['request_type'] === self::TYPE_TERMINATION ? 'Kuendigungs' : 'Aenderungs',
            intval($request_id),
            $is_approved ? 'bestaetigt' : 'abgelehnt'
        );

        $body = sprintf(
            "Ihr Antrag wurde bearbeitet.\n\nAntrag-ID: %d\nTyp: %s\nStatus: %s\n\nHinweis: %s",
            intval($request_id),
            sanitize_text_field((string) $request['request_type']),
            sanitize_text_field((string) $status),
            sanitize_text_field((string) ($request['review_note'] ?? ''))
        );

        wp_mail($to, $subject, $body);
    }

    private static function notify_customer_termination_executed($request_id) {
        $request = self::get_request($request_id);
        if (!$request) {
            return;
        }

        $to = self::get_customer_email_by_license_id(intval($request['license_id']));
        if (!is_email($to)) {
            return;
        }

        $subject = sprintf('[ThemisDB] Kuendigung ausgefuehrt (Antrag #%d)', intval($request_id));
        $body = sprintf(
            "Die Kuendigung Ihrer Lizenz wurde ausgefuehrt.\n\nAntrag-ID: %d\nLizenz-ID: %d\nAusgefuehrt am: %s",
            intval($request_id),
            intval($request['license_id']),
            current_time('mysql')
        );

        wp_mail($to, $subject, $body);
    }

    private static function execute_change_request($request_id) {
        $request = self::get_request($request_id);
        if (!$request || $request['request_type'] !== self::TYPE_CHANGE) {
            return false;
        }

        $payload = json_decode((string) $request['payload'], true);
        if (!is_array($payload) || empty($payload)) {
            return true;
        }

        if (!class_exists('ThemisDB_License_Manager')) {
            return false;
        }

        $license_id = intval($request['license_id']);
        $license = ThemisDB_License_Manager::get_license($license_id);
        if (!$license) {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'themisdb_licenses';

        $allowed_fields = array('product_edition', 'license_type', 'max_nodes', 'max_cores', 'max_storage_gb', 'expiry_date');
        $update_data = array();
        $update_format = array();

        foreach ($allowed_fields as $field) {
            if (!array_key_exists($field, $payload)) {
                continue;
            }
            $value = $payload[$field];
            if (in_array($field, array('max_nodes', 'max_cores', 'max_storage_gb'), true)) {
                $update_data[$field] = intval($value);
                $update_format[] = '%d';
            } else {
                $update_data[$field] = sanitize_text_field((string) $value);
                $update_format[] = '%s';
            }
        }

        if (empty($update_data)) {
            return true;
        }

        $result = $wpdb->update(
            $table,
            $update_data,
            array('id' => $license_id),
            $update_format,
            array('%d')
        );

        return $result !== false;
    }

    private static function get_customer_email_by_license_id($license_id) {
        global $wpdb;

        $license_id = intval($license_id);
        if ($license_id <= 0) {
            return '';
        }

        $licenses_table = $wpdb->prefix . 'themisdb_licenses';
        $users_table = $wpdb->users;

        $email = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT u.user_email
                 FROM {$licenses_table} l
                 LEFT JOIN {$users_table} u ON u.ID = l.customer_id
                 WHERE l.id = %d
                 LIMIT 1",
                $license_id
            )
        );

        return is_email($email) ? sanitize_email($email) : '';
    }

    private static function normalize_effective_at($requested_end_date) {
        $requested_end_date = trim((string) $requested_end_date);
        if ($requested_end_date === '') {
            return current_time('mysql');
        }

        $ts = strtotime($requested_end_date);
        if ($ts === false) {
            return current_time('mysql');
        }

        return gmdate('Y-m-d H:i:s', $ts);
    }

    public static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'themisdb_contract_lifecycle';
    }
}
