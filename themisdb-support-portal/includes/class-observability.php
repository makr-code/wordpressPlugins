<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB - Hybrid Database System                                   ║
╠═════════════════════════════════════════════════════════════════════╣
  File:            class-observability.php                            ║
  Plugin:          themisdb-support-portal                            ║
╚═════════════════════════════════════════════════════════════════════╝
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Observability metrics for ThemisDB Service-Desk (ARCHITECTUR.md §7).
 *
 * All queries are cached via transients (5-minute TTL) to avoid repeated
 * full-table scans on every admin page load.
 *
 * Metrics provided:
 *   - First Response Time (avg hours from ticket creation to first admin reply)
 *   - SLA Breach Rate     (% of closed tickets with sla_breached_at in last 30 d)
 *   - Ticket Volume       (created per day, last 14 days)
 *   - Queue Distribution  (ticket count per queue, open/in_progress only)
 *   - Incident Summary    (open incidents grouped by severity)
 *   - Avg Resolution Time (hours from creation to resolved/closed, last 30 d)
 */
class ThemisDB_Observability {

    const CACHE_TTL = 300; // 5 minutes

    // -------------------------------------------------------------------------
    // First Response Time
    // -------------------------------------------------------------------------

    /**
     * Average hours between ticket creation and the first admin reply.
     * Only counts tickets with at least one admin reply in the last 30 days.
     *
     * @return float|null  Hours, or null if no data.
     */
    public static function get_first_response_time() {
        $cached = get_transient('themisdb_obs_frt');
        if ($cached !== false) {
            return $cached === 'null' ? null : (float) $cached;
        }

        global $wpdb;
        $t_tickets  = $wpdb->prefix . 'themisdb_support_tickets';
        $t_messages = $wpdb->prefix . 'themisdb_support_messages';

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $result = $wpdb->get_var(
            "SELECT AVG(TIMESTAMPDIFF(SECOND, t.created_at, m.first_reply)) / 3600
             FROM `{$t_tickets}` t
             INNER JOIN (
                 SELECT ticket_id, MIN(created_at) AS first_reply
                 FROM `{$t_messages}`
                 WHERE is_admin_reply = 1
                 GROUP BY ticket_id
             ) m ON m.ticket_id = t.id
             WHERE t.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );

        $value = ($result !== null) ? round((float) $result, 2) : null;
        set_transient('themisdb_obs_frt', $value === null ? 'null' : (string) $value, self::CACHE_TTL);
        return $value;
    }

    // -------------------------------------------------------------------------
    // SLA Breach Rate
    // -------------------------------------------------------------------------

    /**
     * Percentage of tickets closed in the last 30 days that had an SLA breach.
     *
     * @return float|null  0–100 percent, or null if no data.
     */
    public static function get_sla_breach_rate() {
        $cached = get_transient('themisdb_obs_sla_rate');
        if ($cached !== false) {
            return $cached === 'null' ? null : (float) $cached;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'themisdb_support_tickets';

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $row = $wpdb->get_row(
            "SELECT
                COUNT(*) AS total,
                SUM(sla_breached_at IS NOT NULL) AS breached
             FROM `{$table}`
             WHERE status IN ('resolved','closed')
               AND updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            ARRAY_A
        );

        $total = isset($row['total']) ? (int) $row['total'] : 0;
        if ($total === 0) {
            set_transient('themisdb_obs_sla_rate', 'null', self::CACHE_TTL);
            return null;
        }

        $rate = round((int) $row['breached'] / $total * 100, 1);
        set_transient('themisdb_obs_sla_rate', (string) $rate, self::CACHE_TTL);
        return $rate;
    }

    // -------------------------------------------------------------------------
    // Ticket Volume (last 14 days)
    // -------------------------------------------------------------------------

    /**
     * Tickets created per day for the last 14 days.
     *
     * @return array  Associative array: [ 'YYYY-MM-DD' => int, … ]
     */
    public static function get_ticket_volume() {
        $cached = get_transient('themisdb_obs_volume');
        if ($cached !== false) {
            return $cached;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'themisdb_support_tickets';

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rows = $wpdb->get_results(
            "SELECT DATE(created_at) AS day, COUNT(*) AS cnt
             FROM `{$table}`
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
             GROUP BY DATE(created_at)
             ORDER BY day ASC",
            ARRAY_A
        ) ?: array();

        // Fill in missing days with 0.
        $result = array();
        for ($i = 13; $i >= 0; $i--) {
            $day            = date('Y-m-d', strtotime("-{$i} days"));
            $result[$day]   = 0;
        }
        foreach ($rows as $row) {
            if (isset($result[$row['day']])) {
                $result[$row['day']] = (int) $row['cnt'];
            }
        }

        set_transient('themisdb_obs_volume', $result, self::CACHE_TTL);
        return $result;
    }

    // -------------------------------------------------------------------------
    // Queue Distribution
    // -------------------------------------------------------------------------

    /**
     * Count of open + in-progress tickets per queue.
     *
     * @return array  [ 'queue_name' => int, … ]
     */
    public static function get_queue_distribution() {
        $cached = get_transient('themisdb_obs_queues');
        if ($cached !== false) {
            return $cached;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'themisdb_support_tickets';

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rows = $wpdb->get_results(
            "SELECT COALESCE(queue,'triage') AS queue, COUNT(*) AS cnt
             FROM `{$table}`
             WHERE status IN ('open','in_progress')
             GROUP BY queue
             ORDER BY cnt DESC",
            ARRAY_A
        ) ?: array();

        $result = array();
        foreach ($rows as $row) {
            $result[$row['queue']] = (int) $row['cnt'];
        }

        set_transient('themisdb_obs_queues', $result, self::CACHE_TTL);
        return $result;
    }

    // -------------------------------------------------------------------------
    // Incident Summary
    // -------------------------------------------------------------------------

    /**
     * Count of open incidents grouped by severity.
     *
     * @return array  [ 'severity' => int, … ]
     */
    public static function get_incident_summary() {
        $cached = get_transient('themisdb_obs_incidents');
        if ($cached !== false) {
            return $cached;
        }

        if (!class_exists('ThemisDB_Incident_Log')) {
            return array();
        }

        global $wpdb;
        $table = ThemisDB_Incident_Log::get_table();

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rows = $wpdb->get_results(
            "SELECT severity, COUNT(*) AS cnt
             FROM `{$table}`
             WHERE status = 'open'
             GROUP BY severity
             ORDER BY FIELD(severity,'critical','high','medium','low')",
            ARRAY_A
        ) ?: array();

        $result = array();
        foreach ($rows as $row) {
            $result[$row['severity']] = (int) $row['cnt'];
        }

        set_transient('themisdb_obs_incidents', $result, self::CACHE_TTL);
        return $result;
    }

    // -------------------------------------------------------------------------
    // Average Resolution Time
    // -------------------------------------------------------------------------

    /**
     * Average hours from ticket creation to resolution/closure, last 30 days.
     *
     * @return float|null  Hours, or null if no data.
     */
    public static function get_avg_resolution_time() {
        $cached = get_transient('themisdb_obs_resolution');
        if ($cached !== false) {
            return $cached === 'null' ? null : (float) $cached;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'themisdb_support_tickets';

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $result = $wpdb->get_var(
            "SELECT AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) / 3600
             FROM `{$table}`
             WHERE status IN ('resolved','closed')
               AND updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );

        $value = ($result !== null) ? round((float) $result, 1) : null;
        set_transient('themisdb_obs_resolution', $value === null ? 'null' : (string) $value, self::CACHE_TTL);
        return $value;
    }

    // -------------------------------------------------------------------------
    // Cache invalidation
    // -------------------------------------------------------------------------

    /**
     * Clear all observability transients (call after ticket/incident changes).
     */
    public static function flush_cache() {
        delete_transient('themisdb_obs_frt');
        delete_transient('themisdb_obs_sla_rate');
        delete_transient('themisdb_obs_volume');
        delete_transient('themisdb_obs_queues');
        delete_transient('themisdb_obs_incidents');
        delete_transient('themisdb_obs_resolution');
    }
}
