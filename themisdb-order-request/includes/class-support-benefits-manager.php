<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB - Hybrid Database System                                   ║
╠═════════════════════════════════════════════════════════════════════╣
  File:            class-support-benefits-manager.php                 ║
  Version:         0.0.1                                              ║
  Last Modified:   2026-03-20 10:00:00                                ║
  Author:          Development Team                                   ║
╠═════════════════════════════════════════════════════════════════════╣
  Quality Metrics:                                                    ║
    • Maturity Level:  🟡 IN DEVELOPMENT                              ║
    • Quality Score:   80.0/100 (Phase 1 Implementation)              ║
    • Total Lines:     550+                                           ║
    • Open Issues:     TODOs: 2                                       ║
╠═════════════════════════════════════════════════════════════════════╣
  Status: 🚀 PHASE 1.2 - License Integration Hooks                    ║
╚═════════════════════════════════════════════════════════════════════╝
 */

/**
 * Support Benefits Manager for ThemisDB Order Request Plugin
 * 
 * Handles automatic support benefit creation, activation, suspension,
 * and deactivation tied to license lifecycle.
 * 
 * Tier Configuration:
 * - Community: 5 open tickets, 12/month, 48h SLA
 * - Enterprise: Unlimited, unlimited, 8h SLA  
 * - Hyperscaler: Unlimited, unlimited, 4h SLA
 * - Reseller: Unlimited, unlimited, 2h SLA
 */

if (!defined('ABSPATH')) {
    exit;
}

class ThemisDB_Support_Benefits_Manager {
    
    /**
     * Tier configuration (default)
     * Can be overridden via settings
     */
    private static function get_tier_config() {
        $global_config = get_option('themisdb_support_tier_config', array());
        
        $defaults = array(
            'community' => array(
                'max_open_tickets' => 5,
                'max_tickets_per_month' => 12,
                'response_sla_hours' => 48,
                'priority_can_assign' => false,
                'included_hours_per_month' => 0,
            ),
            'enterprise' => array(
                'max_open_tickets' => -1,
                'max_tickets_per_month' => -1,
                'response_sla_hours' => 8,
                'priority_can_assign' => true,
                'included_hours_per_month' => 40,
            ),
            'hyperscaler' => array(
                'max_open_tickets' => -1,
                'max_tickets_per_month' => -1,
                'response_sla_hours' => 4,
                'priority_can_assign' => true,
                'included_hours_per_month' => -1,
            ),
            'reseller' => array(
                'max_open_tickets' => -1,
                'max_tickets_per_month' => -1,
                'response_sla_hours' => 2,
                'priority_can_assign' => true,
                'included_hours_per_month' => -1,
            ),
        );
        
        // Merge with global config if set
        if (!empty($global_config)) {
            $defaults = wp_parse_args($global_config, $defaults);
        }
        
        return $defaults;
    }
    
    /**
     * Create support benefits for a license
     * 
     * Called automatically after license creation.
     * 
     * @param int $license_id ID of the newly created license
     * @param string $tier_level Edition tier (community|enterprise|hyperscaler|reseller)
     * @return int|false Benefit ID on success, false on failure
     */
    public static function create_for_license($license_id, $tier_level = 'community') {
        global $wpdb;
        
        $license_id = intval($license_id);
        $tier_level = sanitize_text_field($tier_level);
        
        // Get tier configuration
        $tier_config = self::get_tier_config();
        if (!isset($tier_config[$tier_level])) {
            error_log("Support Benefits: Invalid tier level '$tier_level' for license ID $license_id");
            return false;
        }
        
        $config = $tier_config[$tier_level];
        
        $table = $wpdb->prefix . 'themisdb_support_benefits';
        
        // Check if benefits already exist for this license
        $existing = $wpdb->get_row(
            $wpdb->prepare("SELECT id FROM $table WHERE license_id = %d", $license_id),
            ARRAY_A
        );
        
        if ($existing) {
            error_log("Support Benefits: Benefits already exist for license ID $license_id");
            return $existing['id'];
        }
        
        $benefit_data = array(
            'license_id' => $license_id,
            'tier_level' => $tier_level,
            'max_open_tickets' => intval($config['max_open_tickets']),
            'max_tickets_per_month' => intval($config['max_tickets_per_month']),
            'response_sla_hours' => intval($config['response_sla_hours']),
            'priority_can_assign' => $config['priority_can_assign'] ? 1 : 0,
            'included_hours_per_month' => intval($config['included_hours_per_month']),
            'benefit_status' => 'pending',
            'created_at' => current_time('mysql'),
            'tickets_used_this_month' => 0,
            'hours_used_this_month' => 0.00,
        );
        
        $result = $wpdb->insert($table, $benefit_data);
        
        if ($result) {
            $benefit_id = $wpdb->insert_id;
            error_log("Support Benefits created for License ID: $license_id, Benefit ID: $benefit_id, Tier: $tier_level");
            return $benefit_id;
        }
        
        error_log("Support Benefits: INSERT failed for license ID $license_id. Error: " . $wpdb->last_error);
        return false;
    }
    
    /**
     * Get support benefits by license ID
     * 
     * @param int $license_id
     * @return array|null Benefit record or null if not found
     */
    public static function get_by_license($license_id) {
        global $wpdb;
        
        $license_id = intval($license_id);
        $table = $wpdb->prefix . 'themisdb_support_benefits';
        
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE license_id = %d", $license_id),
            ARRAY_A
        );
    }
    
    /**
     * Get benefit ID by license ID (helper)
     * 
     * @param int $license_id
     * @return int|false Benefit ID or false
     */
    public static function get_benefit_id_by_license($license_id) {
        global $wpdb;
        
        $license_id = intval($license_id);
        $table = $wpdb->prefix . 'themisdb_support_benefits';
        
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT id FROM $table WHERE license_id = %d", $license_id),
            ARRAY_A
        );
        
        return $row ? intval($row['id']) : false;
    }
    
    /**
     * Activate support benefits
     * 
     * Called when license is activated.
     * Sets benefit_status to 'active' and records activation time.
     * 
     * @param int $benefit_id
     * @return bool Success/failure
     */
    public static function activate($benefit_id) {
        global $wpdb;
        
        $benefit_id = intval($benefit_id);
        $table = $wpdb->prefix . 'themisdb_support_benefits';
        
        $result = $wpdb->update(
            $table,
            array(
                'benefit_status' => 'active',
                'activated_at' => current_time('mysql'),
            ),
            array('id' => $benefit_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            error_log("Support Benefits activated: ID $benefit_id");
            return true;
        }
        
        error_log("Support Benefits: UPDATE failed for ID $benefit_id. Error: " . $wpdb->last_error);
        return false;
    }
    
    /**
     * Suspend support benefits
     * 
     * Called when license is suspended.
     * Sets benefit_status to 'suspended' but preserves data.
     * 
     * @param int $benefit_id
     * @param string $reason Reason for suspension
     * @return bool Success/failure
     */
    public static function suspend($benefit_id, $reason = '') {
        global $wpdb;
        
        $benefit_id = intval($benefit_id);
        $table = $wpdb->prefix . 'themisdb_support_benefits';
        
        $result = $wpdb->update(
            $table,
            array(
                'benefit_status' => 'suspended',
            ),
            array('id' => $benefit_id),
            array('%s'),
            array('%d')
        );
        
        if ($result !== false) {
            error_log("Support Benefits suspended: ID $benefit_id. Reason: $reason");
            return true;
        }
        
        error_log("Support Benefits: UPDATE failed for ID $benefit_id. Error: " . $wpdb->last_error);
        return false;
    }
    
    /**
     * Deactivate support benefits
     * 
     * Called when license is cancelled/deleted.
     * Sets benefit_status to 'expired' (permanent).
     * 
     * @param int $benefit_id
     * @return bool Success/failure
     */
    public static function deactivate($benefit_id) {
        global $wpdb;
        
        $benefit_id = intval($benefit_id);
        $table = $wpdb->prefix . 'themisdb_support_benefits';
        
        $result = $wpdb->update(
            $table,
            array(
                'benefit_status' => 'expired',
                'expires_at' => current_time('mysql'),
            ),
            array('id' => $benefit_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            error_log("Support Benefits deactivated: ID $benefit_id");
            return true;
        }
        
        error_log("Support Benefits: UPDATE failed for ID $benefit_id. Error: " . $wpdb->last_error);
        return false;
    }
    
    /**
     * Check if a customer can create a ticket based on their support benefits
     * 
     * Validates:
     * - Benefit exists and is active
     * - Max open tickets limit not reached
     * - Max tickets per month limit not reached
     * - Priority assignment allowed (if applicable)
     * 
     * @param int $benefit_id
     * @param string $priority Ticket priority (low|normal|high|urgent)
     * @return array {
     *     @type bool $allowed Whether ticket can be created
     *     @type string $reason Reason if denied
     *     @type array $usage Current usage stats
     * }
     */
    public static function check_limits($benefit_id, $priority = 'normal') {
        global $wpdb;
        
        $benefit_id = intval($benefit_id);
        $table = $wpdb->prefix . 'themisdb_support_benefits';
        
        $benefit = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $benefit_id),
            ARRAY_A
        );
        
        if (!$benefit) {
            return array(
                'allowed' => false,
                'reason' => 'Support benefits not found',
                'usage' => array(),
            );
        }
        
        if ($benefit['benefit_status'] !== 'active') {
            return array(
                'allowed' => false,
                'reason' => "Support benefits are {$benefit['benefit_status']}",
                'usage' => array(),
            );
        }
        
        // Check ticket limits
        $ticket_table = $wpdb->prefix . 'themisdb_support_tickets';
        
        // Count open tickets
        $open_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $ticket_table WHERE benefit_id = %d AND status IN ('open', 'in_progress')",
                $benefit_id
            )
        );
        
        if ($benefit['max_open_tickets'] !== -1 && intval($open_count) >= $benefit['max_open_tickets']) {
            return array(
                'allowed' => false,
                'reason' => "Maximum {$benefit['max_open_tickets']} open tickets reached for your tier",
                'usage' => array(
                    'max_open' => $benefit['max_open_tickets'],
                    'open' => intval($open_count),
                ),
            );
        }
        
        // Check monthly ticket limit
        $month_start = date('Y-m-01', strtotime('now'));
        $month_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $ticket_table WHERE benefit_id = %d AND DATE(created_at) >= %s",
                $benefit_id,
                $month_start
            )
        );
        
        if ($benefit['max_tickets_per_month'] !== -1 && intval($month_count) >= $benefit['max_tickets_per_month']) {
            return array(
                'allowed' => false,
                'reason' => "Maximum {$benefit['max_tickets_per_month']} tickets per month reached",
                'usage' => array(
                    'max_monthly' => $benefit['max_tickets_per_month'],
                    'this_month' => intval($month_count),
                ),
            );
        }
        
        // Check priority assignment权限
        if (in_array($priority, array('high', 'urgent'), true) && !$benefit['priority_can_assign']) {
            return array(
                'allowed' => false,
                'reason' => "Your tier does not allow setting high or urgent priority",
                'usage' => array('priority_allowed' => 'normal'),
            );
        }
        
        return array(
            'allowed' => true,
            'reason' => 'OK',
            'usage' => array(
                'open_tickets' => intval($open_count),
                'max_open' => $benefit['max_open_tickets'] === -1 ? 'unlimited' : $benefit['max_open_tickets'],
                'monthly_tickets' => intval($month_count),
                'max_monthly' => $benefit['max_tickets_per_month'] === -1 ? 'unlimited' : $benefit['max_tickets_per_month'],
            ),
        );
    }
    
    /**
     * Increment ticket usage counters
     * 
     * Called after ticket creation.
     * 
     * @param int $benefit_id
     * @return bool Success/failure
     */
    public static function increment_ticket_usage($benefit_id) {
        global $wpdb;
        
        $benefit_id = intval($benefit_id);
        $table = $wpdb->prefix . 'themisdb_support_benefits';
        
        // Use raw SQL to increment
        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE $table SET tickets_used_this_month = tickets_used_this_month + 1 WHERE id = %d",
                $benefit_id
            )
        );
        
        return $result !== false;
    }
    
    /**
     * Increment hours usage for a ticket
     * 
     * Called when support staff logs hours.
     * 
     * @param int $benefit_id
     * @param float $hours Hours to add
     * @return bool Success/failure
     */
    public static function increment_hours_usage($benefit_id, $hours = 0) {
        global $wpdb;
        
        $benefit_id = intval($benefit_id);
        $hours = floatval($hours);
        $table = $wpdb->prefix . 'themisdb_support_benefits';
        
        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE $table SET hours_used_this_month = hours_used_this_month + %f WHERE id = %d",
                $hours,
                $benefit_id
            )
        );
        
        return $result !== false;
    }
    
    /**
     * Reset monthly counters
     * 
     * Called via cron job daily (resets if last_reset was > 1 month ago).
     * This allows monthly quotas to refresh.
     * 
     * @param int $benefit_id Optional, if set only reset this benefit
     * @return int Count of benefits reset
     */
    public static function reset_monthly_counts($benefit_id = null) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'themisdb_support_benefits';
        
        if ($benefit_id) {
            $benefit_id = intval($benefit_id);
            $result = $wpdb->query(
                $wpdb->prepare(
                    "UPDATE $table SET tickets_used_this_month = 0, hours_used_this_month = 0, last_reset = NOW() WHERE id = %d",
                    $benefit_id
                )
            );
            return $result !== false ? 1 : 0;
        } else {
            // Reset all benefits where last_reset was > 1 month ago or null
            $result = $wpdb->query(
                "UPDATE $table 
                 SET tickets_used_this_month = 0, 
                     hours_used_this_month = 0, 
                     last_reset = NOW() 
                 WHERE last_reset IS NULL 
                    OR last_reset < DATE_SUB(NOW(), INTERVAL 1 MONTH)"
            );
            
            error_log("Support Benefits: Monthly counts reset for multiple benefits. Affected: $result");
            return intval($result);
        }
    }
    
    /**
     * Expire benefits (when license expires)
     * 
     * Called via cron job daily. Checks for expired licenses and
     * updates corresponding benefits to 'expired' status.
     * 
     * @return int Count of benefits expired
     */
    public static function expire_expired_licenses() {
        global $wpdb;
        
        $benefits_table = $wpdb->prefix . 'themisdb_support_benefits';
        $licenses_table = $wpdb->prefix . 'themisdb_licenses';
        
        // Find benefits where license has expired
        $result = $wpdb->query("
            UPDATE $benefits_table b
            INNER JOIN $licenses_table l ON b.license_id = l.id
            SET b.benefit_status = 'expired', 
                b.expires_at = NOW()
            WHERE b.benefit_status = 'active'
              AND l.expiry_date IS NOT NULL
              AND l.expiry_date < NOW()
              AND l.license_status NOT IN ('cancelled', 'suspended')
        ");
        
        if ($result > 0) {
            error_log("Support Benefits: Expired $result benefits due to license expiry");
        }
        
        return intval($result);
    }
    
    /**
     * Send expiry notification email
     * 
     * Called before expiration (30d, 7d, 1d before expiry).
     * 
     * @param int $benefit_id
     * @param int $days_until_expiry Days remaining before expiry
     * @return bool Email sent success/failure
     */
    public static function send_expiry_notification($benefit_id, $days_until_expiry = 30) {
        global $wpdb;
        
        $benefit_id = intval($benefit_id);
        
        $benefit = self::get_by_license_id($benefit_id);
        if (!$benefit) {
            return false;
        }
        
        // TODO: Implementieren Sie die Email-Benachrichtigung
        // - Customer Email abrufen
        // - Template laden
        // - Email versenden
        // - Notification Log erstellen
        
        error_log("Support Benefits: Send expiry notification - benefit ID $benefit_id, $days_until_expiry days remaining (TODO: Implement)");
        return true;
    }
}
