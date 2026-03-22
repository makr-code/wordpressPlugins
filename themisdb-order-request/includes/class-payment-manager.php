<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB - Hybrid Database System                                   ║
╠═════════════════════════════════════════════════════════════════════╣
  File:            class-payment-manager.php                          ║
  Version:         0.0.2                                              ║
  Last Modified:   2026-03-09 04:08:19                                ║
  Author:          unknown                                            ║
╠═════════════════════════════════════════════════════════════════════╣
  Quality Metrics:                                                    ║
    • Maturity Level:  🟢 PRODUCTION-READY                             ║
    • Quality Score:   100.0/100                                      ║
    • Total Lines:     318                                            ║
    • Open Issues:     TODOs: 0, Stubs: 0                             ║
╠═════════════════════════════════════════════════════════════════════╣
  Revision History:                                                   ║
    • 2a1fb0423  2026-03-03  Merge branch 'develop' into copilot/audit-src-module-docu... ║
    • 9d3ecaa0e  2026-02-28  Add ThemisDB Wiki Integration plugin with documentation i... ║
╠═════════════════════════════════════════════════════════════════════╣
  Status: ✅ Production Ready                                          ║
╚═════════════════════════════════════════════════════════════════════╝
 */


/**
 * Payment Manager for ThemisDB Order Request Plugin
 * Handles payment verification and tracking
 */

if (!defined('ABSPATH')) {
    exit;
}

class ThemisDB_Payment_Manager {

    /**
     * Normalize external/internal status values to canonical payment statuses.
     */
    private static function normalize_payment_status($status) {
        $status = sanitize_key((string) $status);

        $map = array(
            'completed' => 'verified',
            'paid' => 'verified',
            'processing' => 'pending',
        );

        if (isset($map[$status])) {
            $status = $map[$status];
        }

        $allowed = array('pending', 'overdue', 'verified', 'failed');
        return in_array($status, $allowed, true) ? $status : null;
    }

    /**
     * Validate payment status transition.
     */
    private static function can_transition_payment_status($current_status, $new_status) {
        $current_status = self::normalize_payment_status($current_status);
        $new_status = self::normalize_payment_status($new_status);

        if ($current_status === null || $new_status === null) {
            return false;
        }

        if ($current_status === $new_status) {
            return true;
        }

        $allowed = array(
            'pending' => array('overdue', 'verified', 'failed'),
            'overdue' => array('verified', 'failed'),
            'verified' => array(),
            'failed' => array(),
        );

        return in_array($new_status, $allowed[$current_status], true);
    }
    
    /**
     * Create a new payment record
     */
    public static function create_payment($data) {
        global $wpdb;
        
        $table_payments = $wpdb->prefix . 'themisdb_payments';
        
        // Generate payment number
        $payment_number = self::generate_payment_number();
        
        $payment_data = array(
            'payment_number' => $payment_number,
            'order_id' => intval($data['order_id']),
            'contract_id' => isset($data['contract_id']) ? intval($data['contract_id']) : null,
            'amount' => floatval($data['amount']),
            'currency' => isset($data['currency']) ? sanitize_text_field($data['currency']) : 'EUR',
            'payment_method' => sanitize_text_field($data['payment_method']),
            'payment_status' => 'pending',
            'transaction_id' => isset($data['transaction_id']) ? sanitize_text_field($data['transaction_id']) : null,
            'notes' => isset($data['notes']) ? sanitize_textarea_field($data['notes']) : null,
            'metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null
        );
        
        $result = $wpdb->insert($table_payments, $payment_data);
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Verify payment
     */
    public static function verify_payment($payment_id, $verified_by = null) {
        global $wpdb;
        
        $table_payments = $wpdb->prefix . 'themisdb_payments';
        
        $update_data = array(
            'payment_status' => 'verified',
            'verified_at' => current_time('mysql'),
            'verified_by' => $verified_by ? $verified_by : get_current_user_id()
        );
        
        $result = $wpdb->update(
            $table_payments,
            $update_data,
            array('id' => $payment_id),
            null,
            array('%d')
        );
        
        if ($result !== false) {
            // Get payment details
            $payment = self::get_payment($payment_id);
            
            if ($payment) {
                $order = ThemisDB_Order_Manager::get_order($payment['order_id']);
                $is_consumer = $order && (($order['customer_type'] ?? 'consumer') === 'consumer');
                $has_withdrawal_waiver = $order && !empty($order['legal_withdrawal_waiver']);

                // Keep order status aligned with the lifecycle used in admin workflows.
                $target_order_status = !empty($payment['contract_id']) ? 'active' : 'confirmed';
                if (!empty($payment['contract_id']) && $is_consumer && !$has_withdrawal_waiver) {
                    // For consumer digital contracts, stay in confirmed until explicit waiver exists.
                    $target_order_status = 'confirmed';
                }

                $status_synced = ThemisDB_Order_Manager::set_order_status($payment['order_id'], $target_order_status);
                if (!$status_synced) {
                    error_log('ThemisDB Payment Sync Error: Failed to set order status for order ID ' . $payment['order_id'] . ' to ' . $target_order_status);
                    if (class_exists('ThemisDB_Error_Handler')) {
                        ThemisDB_Error_Handler::log('error', 'Payment verify sync failed: order status update failed', array(
                            'payment_id' => intval($payment_id),
                            'order_id' => intval($payment['order_id']),
                            'target_status' => $target_order_status,
                        ));
                    }
                }
                
                // Activate license if exists
                if ($payment['contract_id']) {
                    $license = ThemisDB_License_Manager::get_license_by_contract($payment['contract_id']);
                    if ($license) {
                        $allow_license_activation = true;
                        if ($is_consumer && !$has_withdrawal_waiver) {
                            $allow_license_activation = false;
                            if (class_exists('ThemisDB_Error_Handler')) {
                                ThemisDB_Error_Handler::log('warning', 'License activation deferred: missing withdrawal waiver for consumer order', array(
                                    'payment_id' => intval($payment_id),
                                    'order_id' => intval($payment['order_id']),
                                    'license_id' => intval($license['id']),
                                ));
                            }
                        }

                        $activated = $allow_license_activation ? ThemisDB_License_Manager::activate_license($license['id']) : false;

                        if ($activated) {
                            try {
                                ThemisDB_Email_Handler::send_license_email($license['id']);
                            } catch (Exception $e) {
                                error_log('ThemisDB License Email Error: ' . $e->getMessage());
                                if (class_exists('ThemisDB_Error_Handler')) {
                                    ThemisDB_Error_Handler::log('warning', 'License email send failed after payment verification', array(
                                        'payment_id' => intval($payment_id),
                                        'license_id' => intval($license['id']),
                                        'exception' => $e->getMessage(),
                                    ));
                                }
                            }
                        }
                    }
                }
            }
        }
        
        return $result !== false;
    }
    
    /**
     * Mark payment as failed
     */
    public static function mark_payment_failed($payment_id, $reason = '') {
        global $wpdb;
        
        $table_payments = $wpdb->prefix . 'themisdb_payments';
        
        $update_data = array(
            'payment_status' => 'failed',
            'notes' => $reason
        );
        
        return $wpdb->update(
            $table_payments,
            $update_data,
            array('id' => $payment_id),
            null,
            array('%d')
        ) !== false;
    }

    /**
     * Mark payment as overdue.
     */
    public static function mark_payment_overdue($payment_id, $reason = '') {
        global $wpdb;

        $table_payments = $wpdb->prefix . 'themisdb_payments';
        $payment = self::get_payment($payment_id);

        if (!$payment) {
            return false;
        }

        if (in_array($payment['payment_status'], array('verified', 'failed'), true)) {
            return false;
        }

        return $wpdb->update(
            $table_payments,
            array(
                'payment_status' => 'overdue',
                'notes' => $reason !== '' ? sanitize_textarea_field($reason) : $payment['notes']
            ),
            array('id' => $payment_id),
            null,
            array('%d')
        ) !== false;
    }

    /**
     * Update editable payment fields.
     */
    public static function update_payment($payment_id, $data) {
        global $wpdb;

        $table_payments = $wpdb->prefix . 'themisdb_payments';
        $payment = self::get_payment($payment_id);

        if (!$payment) {
            return false;
        }

        $update_data = array();

        if (isset($data['payment_status'])) {
            $next_status = self::normalize_payment_status($data['payment_status']);
            if ($next_status === null) {
                return false;
            }
            if (!self::can_transition_payment_status($payment['payment_status'], $next_status)) {
                return false;
            }

            $update_data['payment_status'] = $next_status;
            if ($next_status === 'verified' && empty($payment['verified_at'])) {
                $update_data['verified_at'] = current_time('mysql');
            }
        }
        if (isset($data['order_id'])) {
            $update_data['order_id'] = intval($data['order_id']);
        }
        if (isset($data['contract_id'])) {
            $update_data['contract_id'] = !empty($data['contract_id']) ? intval($data['contract_id']) : null;
        }
        if (isset($data['amount'])) {
            $update_data['amount'] = floatval($data['amount']);
        }
        if (isset($data['currency'])) {
            $update_data['currency'] = sanitize_text_field($data['currency']);
        }
        if (isset($data['payment_method'])) {
            $update_data['payment_method'] = sanitize_text_field($data['payment_method']);
        }
        if (isset($data['transaction_id'])) {
            $update_data['transaction_id'] = sanitize_text_field($data['transaction_id']);
        }
        if (isset($data['notes'])) {
            $update_data['notes'] = sanitize_textarea_field($data['notes']);
        }
        if (array_key_exists('metadata', $data)) {
            $update_data['metadata'] = !empty($data['metadata']) ? wp_json_encode($data['metadata']) : null;
        }
        if (isset($data['payment_date'])) {
            $update_data['payment_date'] = sanitize_text_field($data['payment_date']);
        }

        if (empty($update_data)) {
            return true;
        }

        return $wpdb->update(
            $table_payments,
            $update_data,
            array('id' => intval($payment_id)),
            null,
            array('%d')
        ) !== false;
    }

    /**
     * Delete payment row.
     */
    public static function delete_payment($payment_id) {
        global $wpdb;

        $table_payments = $wpdb->prefix . 'themisdb_payments';

        return $wpdb->delete(
            $table_payments,
            array('id' => intval($payment_id)),
            array('%d')
        ) !== false;
    }
    
    /**
     * Get payment by ID
     */
    public static function get_payment($payment_id) {
        global $wpdb;
        
        $table_payments = $wpdb->prefix . 'themisdb_payments';
        
        $payment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_payments WHERE id = %d",
            $payment_id
        ), ARRAY_A);
        
        if ($payment && $payment['metadata']) {
            $payment['metadata'] = json_decode($payment['metadata'], true);
        }
        
        return $payment;
    }
    
    /**
     * Get payment by order
     */
    public static function get_payments_by_order($order_id) {
        global $wpdb;
        
        $table_payments = $wpdb->prefix . 'themisdb_payments';
        
        $payments = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_payments WHERE order_id = %d ORDER BY created_at DESC",
            $order_id
        ), ARRAY_A);
        
        foreach ($payments as &$payment) {
            if ($payment['metadata']) {
                $payment['metadata'] = json_decode($payment['metadata'], true);
            }
        }
        
        return $payments;
    }

    /**
     * Get payments by contract.
     */
    public static function get_payments_by_contract($contract_id) {
        global $wpdb;

        $table_payments = $wpdb->prefix . 'themisdb_payments';

        $payments = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_payments WHERE contract_id = %d ORDER BY created_at DESC",
            $contract_id
        ), ARRAY_A);

        foreach ($payments as &$payment) {
            if ($payment['metadata']) {
                $payment['metadata'] = json_decode($payment['metadata'], true);
            }
        }

        return $payments;
    }

    /**
     * Get the most relevant payment for a contract.
     */
    public static function get_primary_payment_by_contract($contract_id) {
        $payments = self::get_payments_by_contract($contract_id);

        if (empty($payments)) {
            return null;
        }

        foreach ($payments as $payment) {
            if (in_array($payment['payment_status'], array('pending', 'overdue'), true)) {
                return $payment;
            }
        }

        return $payments[0];
    }
    
    /**
     * Get all payments
     */
    public static function get_all_payments($args = array()) {
        global $wpdb;
        
        $table_payments = $wpdb->prefix . 'themisdb_payments';
        
        $defaults = array(
            'status' => null,
            'limit' => 50,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);

        $allowed_orderby = array('id', 'payment_number', 'order_id', 'contract_id', 'amount', 'currency', 'payment_method', 'payment_status', 'payment_date', 'created_at', 'updated_at');
        $orderby = in_array($args['orderby'], $allowed_orderby, true) ? $args['orderby'] : 'created_at';
        $order = strtoupper((string) $args['order']) === 'ASC' ? 'ASC' : 'DESC';
        $limit = max(1, absint($args['limit']));
        $offset = max(0, absint($args['offset']));
        
        $where = "1=1";
        $where_values = array();
        
        if ($args['status']) {
            $where .= " AND payment_status = %s";
            $where_values[] = $args['status'];
        }
        
        $query = "SELECT * FROM $table_payments WHERE $where ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
        $where_values[] = $limit;
        $where_values[] = $offset;
        
        $payments = $wpdb->get_results($wpdb->prepare($query, $where_values), ARRAY_A);
        
        foreach ($payments as &$payment) {
            if ($payment['metadata']) {
                $payment['metadata'] = json_decode($payment['metadata'], true);
            }
        }
        
        return $payments;
    }
    
    /**
     * Check payment with epServer
     */
    public static function check_payment_with_epserver($payment_id) {
        $payment = self::get_payment($payment_id);
        
        if (!$payment || !$payment['epserver_payment_id']) {
            return false;
        }
        
        // Query epServer for payment status
        $response = ThemisDB_EPServer_API::check_payment_status($payment['epserver_payment_id']);
        
        if ($response && isset($response['status'])) {
            // Update payment status based on epServer response
            global $wpdb;
            $table_payments = $wpdb->prefix . 'themisdb_payments';

            $next_status = self::normalize_payment_status($response['status']);
            if ($next_status === null || !self::can_transition_payment_status($payment['payment_status'], $next_status)) {
                return $response;
            }

            $update_data = array(
                'payment_status' => $next_status
            );

            if ($next_status === 'verified') {
                $update_data['verified_at'] = current_time('mysql');
                $update_data['payment_date'] = isset($response['payment_date']) ? $response['payment_date'] : current_time('mysql');
            }
            
            $wpdb->update(
                $table_payments,
                $update_data,
                array('id' => $payment_id),
                null,
                array('%d')
            );
            
            return $response;
        }
        
        return false;
    }
    
    /**
     * Get payment by bank reference (Verwendungszweck)
     */
    public static function get_payment_by_reference($bank_reference) {
        global $wpdb;
        
        $table_payments = $wpdb->prefix . 'themisdb_payments';
        
        $payment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_payments WHERE bank_reference = %s LIMIT 1",
            $bank_reference
        ), ARRAY_A);
        
        if ($payment && $payment['metadata']) {
            $payment['metadata'] = json_decode($payment['metadata'], true);
        }
        
        return $payment;
    }
    
    /**
     * Get payment by payment number
     */
    public static function get_payment_by_number($payment_number) {
        global $wpdb;
        
        $table_payments = $wpdb->prefix . 'themisdb_payments';
        
        $payment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_payments WHERE payment_number = %s LIMIT 1",
            $payment_number
        ), ARRAY_A);
        
        if ($payment && $payment['metadata']) {
            $payment['metadata'] = json_decode($payment['metadata'], true);
        }
        
        return $payment;
    }
    
    /**
     * Generate payment number
     */
    private static function generate_payment_number() {
        $prefix = 'PAY';
        $date = date('Ymd');
        $random = strtoupper(bin2hex(random_bytes(3)));
        
        return $prefix . '-' . $date . '-' . $random;
    }
    
    /**
     * Get payment statistics
     */
    public static function get_payment_stats() {
        global $wpdb;
        
        $table_payments = $wpdb->prefix . 'themisdb_payments';
        
        $stats = array(
            'total_payments' => 0,
            'verified_payments' => 0,
            'pending_payments' => 0,
            'failed_payments' => 0,
            'total_amount' => 0,
            'verified_amount' => 0
        );
        
        $results = $wpdb->get_results(
            "SELECT 
                payment_status,
                COUNT(*) as count,
                SUM(amount) as total
            FROM $table_payments
            GROUP BY payment_status",
            ARRAY_A
        );
        
        foreach ($results as $row) {
            $stats['total_payments'] += $row['count'];
            $stats['total_amount'] += floatval($row['total']);
            
            switch ($row['payment_status']) {
                case 'verified':
                    $stats['verified_payments'] = $row['count'];
                    $stats['verified_amount'] = floatval($row['total']);
                    break;
                case 'pending':
                    $stats['pending_payments'] = $row['count'];
                    break;
                case 'failed':
                    $stats['failed_payments'] = $row['count'];
                    break;
            }
        }
        
        return $stats;
    }
}
