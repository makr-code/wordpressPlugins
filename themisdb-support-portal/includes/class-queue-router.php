<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB - Hybrid Database System                                   ║
╠═════════════════════════════════════════════════════════════════════╣
  File:            class-queue-router.php                             ║
  Plugin:          themisdb-support-portal                            ║
╚═════════════════════════════════════════════════════════════════════╝
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Queue routing logic for the ThemisDB Service-Desk.
 *
 * Implements ARCHITECTUR.md §5.2:
 *   1. Ticket type and priority determine target queue.
 *   2. Tier determines SLA window and escalation threshold.
 *   3. Unassigned tickets go to Triage Queue.
 */
class ThemisDB_Queue_Router {

    // -------------------------------------------------------------------------
    // Queue names
    // -------------------------------------------------------------------------

    const QUEUE_TRIAGE   = 'triage';
    const QUEUE_URGENT   = 'urgent';
    const QUEUE_INCIDENT = 'incident';
    const QUEUE_CHANGE   = 'change';
    const QUEUE_REQUEST  = 'request';

    // -------------------------------------------------------------------------
    // Ticket types
    // -------------------------------------------------------------------------

    const TYPE_INCIDENT = 'incident';
    const TYPE_CHANGE   = 'change';
    const TYPE_REQUEST  = 'request';

    // -------------------------------------------------------------------------
    // Tier-based SLA windows (hours)
    // -------------------------------------------------------------------------

    const TIER_SLA_HOURS = array(
        'enterprise'   => array('urgent' => 2,  'high' => 4,  'normal' => 16, 'low' => 48 ),
        'professional' => array('urgent' => 4,  'high' => 8,  'normal' => 24, 'low' => 72 ),
        'basic'        => array('urgent' => 8,  'high' => 16, 'normal' => 48, 'low' => 120),
    );

    // -------------------------------------------------------------------------
    // Routing
    // -------------------------------------------------------------------------

    /**
     * Resolve the target queue for a ticket.
     *
     * @param string $ticket_type  One of TYPE_INCIDENT, TYPE_CHANGE, TYPE_REQUEST.
     * @param string $priority     One of urgent, high, normal, low.
     * @param string $tier         Customer tier key (enterprise/professional/basic) or '' for none.
     * @return string              QUEUE_* constant.
     */
    public static function resolve_queue($ticket_type, $priority, $tier = '') {
        // Urgent priority always goes to the urgent fast-lane queue.
        if ($priority === 'urgent') {
            return self::QUEUE_URGENT;
        }
        // No identifiable tier → Triage.
        if (empty($tier)) {
            return self::QUEUE_TRIAGE;
        }
        switch ($ticket_type) {
            case self::TYPE_INCIDENT:
                return self::QUEUE_INCIDENT;
            case self::TYPE_CHANGE:
                return self::QUEUE_CHANGE;
            default:
                return self::QUEUE_REQUEST;
        }
    }

    // -------------------------------------------------------------------------
    // SLA helpers
    // -------------------------------------------------------------------------

    /**
     * Return SLA hours for a given tier and priority combination.
     * Falls back to ThemisDB_SLA_Escalation defaults when the tier is unknown.
     *
     * @param string $tier
     * @param string $priority
     * @return int
     */
    public static function get_sla_hours($tier, $priority) {
        $tier = strtolower((string) $tier);
        if (isset(self::TIER_SLA_HOURS[$tier][$priority])) {
            return self::TIER_SLA_HOURS[$tier][$priority];
        }
        // Fallback: use priority-based defaults from SLA_Escalation class.
        if (class_exists('ThemisDB_SLA_Escalation') && isset(ThemisDB_SLA_Escalation::SLA_HOURS[$priority])) {
            return (int) ThemisDB_SLA_Escalation::SLA_HOURS[$priority];
        }
        return 24;
    }

    // -------------------------------------------------------------------------
    // Display helpers
    // -------------------------------------------------------------------------

    /**
     * Human-readable queue label.
     *
     * @param string $queue
     * @return string
     */
    public static function queue_label($queue) {
        $labels = array(
            'triage'   => 'Triage',
            'urgent'   => 'Dringend',
            'incident' => 'Incident',
            'change'   => 'Change',
            'request'  => 'Anfrage',
        );
        return isset($labels[$queue]) ? $labels[$queue] : ucfirst((string) $queue);
    }

    /**
     * Badge colour for use in admin UI inline styles.
     *
     * @param string $queue
     * @return string  CSS hex colour.
     */
    public static function queue_color($queue) {
        $colors = array(
            'triage'   => '#999999',
            'urgent'   => '#c0392b',
            'incident' => '#e67e22',
            'change'   => '#8e44ad',
            'request'  => '#2980b9',
        );
        return isset($colors[$queue]) ? $colors[$queue] : '#666666';
    }

    /**
     * Human-readable ticket type label.
     *
     * @param string $type
     * @return string
     */
    public static function type_label($type) {
        $labels = array(
            'incident' => 'Incident',
            'change'   => 'Change',
            'request'  => 'Anfrage',
        );
        return isset($labels[$type]) ? $labels[$type] : ucfirst((string) $type);
    }

    /**
     * All valid queue keys.
     *
     * @return string[]
     */
    public static function all_queues() {
        return array(
            self::QUEUE_TRIAGE,
            self::QUEUE_URGENT,
            self::QUEUE_INCIDENT,
            self::QUEUE_CHANGE,
            self::QUEUE_REQUEST,
        );
    }

    /**
     * All valid ticket type keys.
     *
     * @return string[]
     */
    public static function all_types() {
        return array(self::TYPE_INCIDENT, self::TYPE_CHANGE, self::TYPE_REQUEST);
    }
}
