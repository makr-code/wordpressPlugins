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
    • Maturity Level:  � PRODUCTION-READY                              ║
    • Quality Score:   100.0/100                                      ║
    • Total Lines:     550+                                           ║
    • Open Issues:     TODOs: 0, Stubs: 0                             ║
╠═════════════════════════════════════════════════════════════════════╣
  Status: ✅ Production Ready                                          ║
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
            if (class_exists('ThemisDB_Error_Handler')) {
                ThemisDB_Error_Handler::log('error', 'Support benefits creation failed: invalid tier', array(
                    'license_id' => intval($license_id),
                    'tier_level' => sanitize_key($tier_level),
                ));
            }
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
            if (class_exists('ThemisDB_Error_Handler')) {
                ThemisDB_Error_Handler::log('info', 'Support benefits already exist for license', array(
                    'license_id' => intval($license_id),
                    'benefit_id' => intval($existing['id']),
                ));
            }
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
            if (class_exists('ThemisDB_Error_Handler')) {
                ThemisDB_Error_Handler::log('info', 'Support benefits created', array(
                    'license_id' => intval($license_id),
                    'benefit_id' => intval($benefit_id),
                    'tier_level' => sanitize_key($tier_level),
                ));
            }
            return $benefit_id;
        }
        
        error_log("Support Benefits: INSERT failed for license ID $license_id. Error: " . $wpdb->last_error);
        if (class_exists('ThemisDB_Error_Handler')) {
            ThemisDB_Error_Handler::log('error', 'Support benefits insert failed', array(
                'license_id' => intval($license_id),
                'db_error' => (string) $wpdb->last_error,
            ));
        }
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
     * Get support benefits by benefit ID.
     *
     * @param int $benefit_id
     * @return array|null
     */
    public static function get_by_id($benefit_id) {
        global $wpdb;

        $benefit_id = intval($benefit_id);
        $table = $wpdb->prefix . 'themisdb_support_benefits';

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $benefit_id),
            ARRAY_A
        );
    }

    /**
     * Update support tier configuration for an existing license.
     *
     * @param int    $license_id
     * @param string $tier_level
     * @return bool
     */
    public static function update_tier_for_license($license_id, $tier_level) {
        global $wpdb;

        $license_id = intval($license_id);
        $tier_level = sanitize_key($tier_level);

        $tier_config = self::get_tier_config();
        if (!isset($tier_config[$tier_level])) {
            return false;
        }

        $benefit = self::get_by_license($license_id);
        if (!$benefit) {
            $created = self::create_for_license($license_id, $tier_level);
            return !empty($created);
        }

        $cfg = $tier_config[$tier_level];
        $table = $wpdb->prefix . 'themisdb_support_benefits';

        $result = $wpdb->update(
            $table,
            array(
                'tier_level' => $tier_level,
                'max_open_tickets' => intval($cfg['max_open_tickets']),
                'max_tickets_per_month' => intval($cfg['max_tickets_per_month']),
                'response_sla_hours' => intval($cfg['response_sla_hours']),
                'priority_can_assign' => $cfg['priority_can_assign'] ? 1 : 0,
                'included_hours_per_month' => intval($cfg['included_hours_per_month']),
                'updated_at' => current_time('mysql'),
            ),
            array('id' => intval($benefit['id'])),
            array('%s', '%d', '%d', '%d', '%d', '%d', '%s'),
            array('%d')
        );

        if (class_exists('ThemisDB_Error_Handler')) {
            ThemisDB_Error_Handler::log(
                $result !== false ? 'info' : 'error',
                $result !== false ? 'Support tier updated for license' : 'Support tier update failed for license',
                array(
                    'license_id' => $license_id,
                    'benefit_id' => intval($benefit['id']),
                    'tier_level' => $tier_level,
                )
            );
        }

        return $result !== false;
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
            if (class_exists('ThemisDB_Error_Handler')) {
                ThemisDB_Error_Handler::log('info', 'Support benefits activated', array(
                    'benefit_id' => intval($benefit_id),
                ));
            }
            return true;
        }
        
        error_log("Support Benefits: UPDATE failed for ID $benefit_id. Error: " . $wpdb->last_error);
        if (class_exists('ThemisDB_Error_Handler')) {
            ThemisDB_Error_Handler::log('error', 'Support benefits activate failed', array(
                'benefit_id' => intval($benefit_id),
                'db_error' => (string) $wpdb->last_error,
            ));
        }
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
            if (class_exists('ThemisDB_Error_Handler')) {
                ThemisDB_Error_Handler::log('info', 'Support benefits suspended', array(
                    'benefit_id' => intval($benefit_id),
                    'reason' => sanitize_text_field($reason),
                ));
            }
            return true;
        }
        
        error_log("Support Benefits: UPDATE failed for ID $benefit_id. Error: " . $wpdb->last_error);
        if (class_exists('ThemisDB_Error_Handler')) {
            ThemisDB_Error_Handler::log('error', 'Support benefits suspend failed', array(
                'benefit_id' => intval($benefit_id),
                'db_error' => (string) $wpdb->last_error,
            ));
        }
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
            if (class_exists('ThemisDB_Error_Handler')) {
                ThemisDB_Error_Handler::log('info', 'Support benefits deactivated', array(
                    'benefit_id' => intval($benefit_id),
                ));
            }
            return true;
        }
        
        error_log("Support Benefits: UPDATE failed for ID $benefit_id. Error: " . $wpdb->last_error);
        if (class_exists('ThemisDB_Error_Handler')) {
            ThemisDB_Error_Handler::log('error', 'Support benefits deactivate failed', array(
                'benefit_id' => intval($benefit_id),
                'db_error' => (string) $wpdb->last_error,
            ));
        }
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
        
        if (intval($benefit['max_open_tickets']) !== -1 && intval($open_count) >= intval($benefit['max_open_tickets'])) {
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
        
        if (intval($benefit['max_tickets_per_month']) !== -1 && intval($month_count) >= intval($benefit['max_tickets_per_month'])) {
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
                'max_open' => intval($benefit['max_open_tickets']) === -1 ? 'unlimited' : intval($benefit['max_open_tickets']),
                'monthly_tickets' => intval($month_count),
                'max_monthly' => intval($benefit['max_tickets_per_month']) === -1 ? 'unlimited' : intval($benefit['max_tickets_per_month']),
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
            if (class_exists('ThemisDB_Error_Handler')) {
                ThemisDB_Error_Handler::log('info', 'Support monthly counters reset', array(
                    'benefit_id' => null,
                    'affected_rows' => intval($result),
                ));
            }
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
            if (class_exists('ThemisDB_Error_Handler')) {
                ThemisDB_Error_Handler::log('info', 'Support benefits expired due to license expiry', array(
                    'expired_count' => intval($result),
                ));
            }
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

        $benefit = self::get_by_id($benefit_id);
        if (!$benefit) {
            return false;
        }

        if (!class_exists('ThemisDB_Email_Handler')) {
            error_log("Support Benefits: ThemisDB_Email_Handler not available for expiry notification (benefit ID $benefit_id)");
            return false;
        }

        $result = ThemisDB_Email_Handler::send_support_expiry_notification($benefit_id, intval($days_until_expiry));

        if (class_exists('ThemisDB_Error_Handler')) {
            ThemisDB_Error_Handler::log(
                $result ? 'info' : 'warning',
                $result
                    ? 'Support expiry notification sent'
                    : 'Support expiry notification failed',
                array(
                    'benefit_id'       => $benefit_id,
                    'days_until_expiry' => intval($days_until_expiry),
                )
            );
        }

        return $result;
    }
}
