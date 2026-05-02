<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB - Hybrid Database System                                   ║
╠═════════════════════════════════════════════════════════════════════╣
  File:            class-incident-log.php                             ║
  Plugin:          themisdb-support-portal                            ║
╚═════════════════════════════════════════════════════════════════════╝
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Incident Log for ThemisDB Service-Desk.
 *
 * Persists operational incidents (SLA breaches, mail failures, sync errors, …)
 * in the `wp_themisdb_incident_log` table as defined in ARCHITECTUR.md §3.5.
 *
 * Duplicate detection: identical domain+message in an open state within the
 * last hour simply bumps the retry_count instead of creating a new row.
 */
class ThemisDB_Incident_Log {

    // -------------------------------------------------------------------------
    // Constants
    // -------------------------------------------------------------------------

    const SEVERITY_LOW      = 'low';
    const SEVERITY_MEDIUM   = 'medium';
    const SEVERITY_HIGH     = 'high';
    const SEVERITY_CRITICAL = 'critical';

    const STATUS_OPEN     = 'open';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_IGNORED  = 'ignored';

    const DOMAIN_SLA    = 'sla';
    const DOMAIN_MAIL   = 'mail';
    const DOMAIN_BUILD  = 'build';
    const DOMAIN_SYNC   = 'sync';
    const DOMAIN_SYSTEM = 'system';

    // -------------------------------------------------------------------------
    // Table helper
    // -------------------------------------------------------------------------

    public static function get_table() {
        global $wpdb;
        return $wpdb->prefix . 'themisdb_incident_log';
    }

    // -------------------------------------------------------------------------
    // Write
    // -------------------------------------------------------------------------

    /**
     * Log an incident.  Returns the incident ID (new or existing).
     *
     * @param string $domain    One of DOMAIN_* constants.
     * @param string $severity  One of SEVERITY_* constants.
     * @param string $message   Short human-readable error description.
     * @param array  $context   Optional structured data (stored as JSON).
     * @return int|false
     */
    public static function log($domain, $severity, $message, $context = array()) {
        global $wpdb;
        $table = self::get_table();

        // De-duplicate: same domain + message that is still open and recent.
        $existing_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM `$table`
                 WHERE domain = %s
                   AND last_error = %s
                   AND status = 'open'
                   AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                 LIMIT 1",
                sanitize_text_field($domain),
                sanitize_text_field(substr($message, 0, 1000))
            )
        );

        if ($existing_id) {
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE `$table` SET retry_count = retry_count + 1, updated_at = NOW() WHERE id = %d",
                    intval($existing_id)
                )
            );
            return intval($existing_id);
        }

        // Validate severity.
        $valid_severities = array('low', 'medium', 'high', 'critical');
        $severity = in_array($severity, $valid_severities, true) ? $severity : self::SEVERITY_MEDIUM;

        $context_json = !empty($context) ? wp_json_encode($context) : null;

        $inserted = $wpdb->insert(
            $table,
            array(
                'domain'      => sanitize_text_field($domain),
                'severity'    => $severity,
                'status'      => self::STATUS_OPEN,
                'last_error'  => sanitize_text_field(substr($message, 0, 1000)),
                'retry_count' => 0,
                'context'     => $context_json,
            ),
            array('%s', '%s', '%s', '%s', '%d', '%s')
        );

        return $inserted ? (int) $wpdb->insert_id : false;
    }

    /**
     * Mark an incident as resolved.
     *
     * @param int $incident_id
     */
    public static function resolve($incident_id) {
        global $wpdb;
        $wpdb->update(
            self::get_table(),
            array(
                'status'     => self::STATUS_RESOLVED,
                'updated_at' => current_time('mysql', true),
            ),
            array('id' => intval($incident_id)),
            array('%s', '%s'),
            array('%d')
        );
    }

    /**
     * Mark an incident as ignored.
     *
     * @param int $incident_id
     */
    public static function ignore($incident_id) {
        global $wpdb;
        $wpdb->update(
            self::get_table(),
            array(
                'status'     => self::STATUS_IGNORED,
                'updated_at' => current_time('mysql', true),
            ),
            array('id' => intval($incident_id)),
            array('%s', '%s'),
            array('%d')
        );
    }

    // -------------------------------------------------------------------------
    // Read
    // -------------------------------------------------------------------------

    /**
     * Return incidents, optionally filtered.
     *
     * @param array $args {
     *     @type string $status    Filter by status.
     *     @type string $domain    Filter by domain.
     *     @type string $severity  Filter by severity.
     *     @type int    $limit     Max rows (default 100).
     * }
     * @return array[]
     */
    public static function get_all($args = array()) {
        global $wpdb;
        $table  = self::get_table();
        $where  = array('1=1');
        $params = array();

        if (!empty($args['status'])) {
            $where[]  = 'status = %s';
            $params[] = $args['status'];
        }
        if (!empty($args['domain'])) {
            $where[]  = 'domain = %s';
            $params[] = $args['domain'];
        }
        if (!empty($args['severity'])) {
            $where[]  = 'severity = %s';
            $params[] = $args['severity'];
        }

        $where_sql = implode(' AND ', $where);
        $limit     = isset($args['limit']) ? max(1, intval($args['limit'])) : 100;

        $sql = "SELECT * FROM `$table` WHERE $where_sql ORDER BY created_at DESC LIMIT $limit";

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, ...$params);
        }

        return $wpdb->get_results($sql, ARRAY_A) ?: array();
    }

    /**
     * Count open incidents.
     *
     * @return int
     */
    public static function get_open_count() {
        global $wpdb;
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM `" . self::get_table() . "` WHERE status = 'open'"
        );
    }

    // -------------------------------------------------------------------------
    // Display helpers
    // -------------------------------------------------------------------------

    /**
     * CSS colour for severity badges.
     *
     * @param string $severity
     * @return string
     */
    public static function severity_color($severity) {
        $colors = array(
            'low'      => '#27ae60',
            'medium'   => '#f39c12',
            'high'     => '#e67e22',
            'critical' => '#c0392b',
        );
        return isset($colors[$severity]) ? $colors[$severity] : '#999999';
    }

    /**
     * Human-readable severity label.
     *
     * @param string $severity
     * @return string
     */
    public static function severity_label($severity) {
        $labels = array(
            'low'      => 'Niedrig',
            'medium'   => 'Mittel',
            'high'     => 'Hoch',
            'critical' => 'Kritisch',
        );
        return isset($labels[$severity]) ? $labels[$severity] : ucfirst((string) $severity);
    }

    /**
     * Human-readable status label.
     *
     * @param string $status
     * @return string
     */
    public static function status_label($status) {
        $labels = array(
            'open'     => 'Offen',
            'resolved' => 'Geloest',
            'ignored'  => 'Ignoriert',
        );
        return isset($labels[$status]) ? $labels[$status] : ucfirst((string) $status);
    }
}
