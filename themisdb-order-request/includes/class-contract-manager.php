<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB - Hybrid Database System                                   ║
╠═════════════════════════════════════════════════════════════════════╣
  File:            class-contract-manager.php                         ║
  Version:         0.0.2                                              ║
  Last Modified:   2026-03-09 04:08:19                                ║
  Author:          unknown                                            ║
╠═════════════════════════════════════════════════════════════════════╣
  Quality Metrics:                                                    ║
    • Maturity Level:  🟢 PRODUCTION-READY                             ║
    • Quality Score:   100.0/100                                      ║
    • Total Lines:     418                                            ║
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
 * Contract Manager for ThemisDB Order Request Plugin
 * Handles CRUD operations for contracts with legal compliance
 */

if (!defined('ABSPATH')) {
    exit;
}

class ThemisDB_Contract_Manager {

    /**
     * Ensure a contract exists for an order and optionally run follow-up actions.
     *
     * @param int   $order_id Order ID.
     * @param array $args     Optional behavior flags.
     * @return array{
     *     success: bool,
     *     contract_id?: int,
     *     created?: bool,
     *     message?: string,
     *     contract?: array
     * }
     */
    public static function ensure_contract_for_order($order_id, $args = array()) {
        $order_id = intval($order_id);
        if ($order_id <= 0) {
            return array(
                'success' => false,
                'message' => 'Invalid order ID.'
            );
        }

        $defaults = array(
            'contract_type' => 'license',
            'generate_pdf' => true,
            'send_email' => false,
            'order_status_after' => null,
            'exclude_existing_statuses' => array('cancelled', 'ended')
        );
        $args = wp_parse_args($args, $defaults);

        $order = ThemisDB_Order_Manager::get_order($order_id);
        if (!$order) {
            return array(
                'success' => false,
                'message' => 'Order not found.'
            );
        }

        if (isset($order['status']) && $order['status'] === 'cancelled') {
            return array(
                'success' => false,
                'message' => 'Cancelled orders cannot be converted to contracts.'
            );
        }

        $contracts = self::get_contracts_by_order($order_id);
        $existing_contract = null;
        foreach ($contracts as $candidate) {
            if (in_array($candidate['status'], $args['exclude_existing_statuses'], true)) {
                continue;
            }
            $existing_contract = $candidate;
            break;
        }

        if (!$existing_contract && !empty($contracts)) {
            $existing_contract = $contracts[0];
        }

        $created = false;
        if ($existing_contract) {
            $contract_id = intval($existing_contract['id']);
            $contract = self::get_contract($contract_id);
        } else {
            $contract_data = array(
                'order_id' => $order_id,
                'customer_id' => !empty($order['customer_id']) ? intval($order['customer_id']) : get_current_user_id(),
                'contract_type' => sanitize_text_field($args['contract_type']),
                'contract_data' => array(
                    'order_number' => $order['order_number'] ?? '',
                    'customer_name' => $order['customer_name'] ?? '',
                    'customer_email' => $order['customer_email'] ?? '',
                    'customer_company' => $order['customer_company'] ?? '',
                    'product_edition' => $order['product_edition'] ?? '',
                    'product_type' => $order['product_type'] ?? '',
                    'total_amount' => $order['total_amount'] ?? 0,
                    'currency' => $order['currency'] ?? 'EUR',
                    'modules' => $order['modules'] ?? array(),
                    'training_modules' => $order['training_modules'] ?? array(),
                    'order_status' => $order['status'] ?? 'draft'
                )
            );

            $contract_id = self::create_contract($contract_data);
            if (!$contract_id) {
                return array(
                    'success' => false,
                    'message' => 'Contract could not be created.'
                );
            }

            $created = true;
            $contract = self::get_contract($contract_id);
        }

        if (!empty($args['generate_pdf'])) {
            try {
                ThemisDB_PDF_Generator::generate_contract_pdf($contract_id);
            } catch (Throwable $e) {
                error_log('ThemisDB Contract PDF Error: ' . $e->getMessage());
            }
        }

        if (!empty($args['send_email'])) {
            try {
                ThemisDB_Email_Handler::send_contract_email($contract_id);
            } catch (Throwable $e) {
                error_log('ThemisDB Contract Email Error: ' . $e->getMessage());
            }
        }

        if (!empty($args['order_status_after'])) {
            $new_status = sanitize_text_field($args['order_status_after']);
            if (!empty($order['status']) && $order['status'] !== $new_status) {
                ThemisDB_Order_Manager::set_order_status($order_id, $new_status);
            }
        }

        return array(
            'success' => true,
            'contract_id' => intval($contract_id),
            'created' => $created,
            'contract' => $contract,
            'message' => $created ? 'Contract created.' : 'Existing contract reused.'
        );
    }

    /**
     * Return allowed contract status transitions.
     */
    public static function get_allowed_status_transitions($current_status) {
        $map = array(
            'draft' => array('signed', 'cancelled'),
            'signed' => array('active', 'suspended', 'cancelled'),
            'active' => array('suspended', 'ended', 'cancelled'),
            'suspended' => array('active', 'ended', 'cancelled'),
            'ended' => array(),
            'cancelled' => array(),
        );

        $current_status = sanitize_text_field((string) $current_status);

        return isset($map[$current_status]) ? $map[$current_status] : array();
    }

    /**
     * Change the lifecycle status of a contract.
     */
    public static function transition_contract_status($contract_id, $new_status, $reason = '', $changed_by = null) {
        $contract = self::get_contract($contract_id);

        if (!$contract) {
            return array(
                'success' => false,
                'message' => 'Contract not found.'
            );
        }

        $new_status = sanitize_text_field($new_status);
        $current_status = isset($contract['status']) ? sanitize_text_field($contract['status']) : 'draft';

        if ($current_status === $new_status) {
            return array(
                'success' => true,
                'contract_id' => intval($contract_id),
                'message' => 'Contract status unchanged.'
            );
        }

        $allowed = self::get_allowed_status_transitions($current_status);
        if (!in_array($new_status, $allowed, true)) {
            return array(
                'success' => false,
                'message' => 'Invalid contract status transition.'
            );
        }

        $contract_data = is_array($contract['contract_data']) ? $contract['contract_data'] : array();
        $workflow = isset($contract_data['workflow']) && is_array($contract_data['workflow'])
            ? $contract_data['workflow']
            : array();
        $history = isset($workflow['history']) && is_array($workflow['history'])
            ? $workflow['history']
            : array();

        $changed_by = $changed_by !== null ? intval($changed_by) : get_current_user_id();
        $timestamp = current_time('mysql');

        $history[] = array(
            'from' => $current_status,
            'to' => $new_status,
            'reason' => sanitize_textarea_field($reason),
            'changed_by' => $changed_by,
            'changed_at' => $timestamp,
        );

        $workflow['last_status_change'] = $timestamp;
        $workflow['history'] = $history;

        if ($new_status === 'suspended') {
            $workflow['suspended_at'] = $timestamp;
            $workflow['suspension_reason'] = sanitize_textarea_field($reason);
        }

        if ($new_status === 'ended') {
            $workflow['ended_at'] = $timestamp;
            $workflow['ending_reason'] = sanitize_textarea_field($reason);
        }

        if ($new_status === 'cancelled') {
            $workflow['cancelled_at'] = $timestamp;
            $workflow['cancellation_reason'] = sanitize_textarea_field($reason);
        }

        $contract_data['workflow'] = $workflow;

        $update_payload = array(
            'status' => $new_status,
            'contract_data' => $contract_data,
        );

        if ($new_status === 'signed' && empty($contract['signed_at'])) {
            $update_payload['signed_at'] = $timestamp;
        }

        if ($new_status === 'ended') {
            $today = gmdate('Y-m-d');
            if (empty($contract['valid_until']) || $contract['valid_until'] > $today) {
                $update_payload['valid_until'] = $today;
            }
        }

        $updated = self::update_contract(
            $contract_id,
            $update_payload,
            $reason !== '' ? $reason : sprintf('Status changed from %s to %s', $current_status, $new_status)
        );

        if (!$updated) {
            return array(
                'success' => false,
                'message' => 'Contract update failed.'
            );
        }

        $updated_contract = self::get_contract($contract_id);
        self::sync_related_records_for_status($updated_contract, $current_status, $new_status, $reason, $changed_by);

        return array(
            'success' => true,
            'contract_id' => intval($contract_id),
            'contract' => $updated_contract,
            'message' => 'Contract status updated.'
        );
    }

    /**
     * End expired contracts and run dunning workflow.
     */
    public static function run_daily_lifecycle_jobs() {
        self::end_expired_contracts();
        self::process_payment_dunning();
    }

    /**
     * End contracts whose validity has lapsed.
     */
    public static function end_expired_contracts() {
        global $wpdb;

        $table_contracts = $wpdb->prefix . 'themisdb_contracts';
        $today = gmdate('Y-m-d');

        $contracts = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM $table_contracts WHERE status IN ('active', 'suspended') AND valid_until IS NOT NULL AND valid_until < %s",
            $today
        ), ARRAY_A);

        foreach ($contracts as $contract_row) {
            self::transition_contract_status(
                intval($contract_row['id']),
                'ended',
                __('Automatisch beendet: Vertragslaufzeit abgelaufen.', 'themisdb-order-request'),
                0
            );
        }
    }

    /**
     * Escalate overdue payments with reminder levels.
     */
    public static function process_payment_dunning() {
        global $wpdb;

        $table_payments = $wpdb->prefix . 'themisdb_payments';
        $payments = $wpdb->get_results(
            "SELECT * FROM $table_payments WHERE payment_status IN ('pending', 'overdue') ORDER BY created_at ASC",
            ARRAY_A
        );

        if (empty($payments)) {
            return;
        }

        $today = strtotime(gmdate('Y-m-d') . ' 00:00:00');
        $thresholds = array(
            1 => 7,
            2 => 14,
            3 => 30,
        );

        foreach ($payments as $payment) {
            $created_at = !empty($payment['created_at']) ? strtotime($payment['created_at']) : false;
            if (!$created_at) {
                continue;
            }

            $days_open = (int) floor(($today - strtotime(gmdate('Y-m-d', $created_at) . ' 00:00:00')) / DAY_IN_SECONDS);
            if ($days_open < 7) {
                continue;
            }

            $metadata = !empty($payment['metadata']) ? json_decode($payment['metadata'], true) : array();
            if (!is_array($metadata)) {
                $metadata = array();
            }

            $last_level = isset($metadata['dunning_level']) ? intval($metadata['dunning_level']) : 0;
            $target_level = 0;

            foreach ($thresholds as $level => $min_days) {
                if ($days_open >= $min_days) {
                    $target_level = $level;
                }
            }

            if ($target_level === 0 || $target_level <= $last_level) {
                continue;
            }

            ThemisDB_Payment_Manager::mark_payment_overdue(
                intval($payment['id']),
                sprintf('Payment overdue for %d day(s).', $days_open)
            );

            $metadata['dunning_level'] = $target_level;
            $metadata['dunning_last_sent_at'] = current_time('mysql');
            $metadata['dunning_days_open'] = $days_open;
            ThemisDB_Payment_Manager::update_payment(intval($payment['id']), array(
                'metadata' => $metadata,
            ));

            try {
                ThemisDB_Email_Handler::send_payment_dunning_email(intval($payment['id']), $target_level);
            } catch (Throwable $e) {
                error_log('ThemisDB Dunning Email Error: ' . $e->getMessage());
            }

            if ($target_level >= 3 && !empty($payment['contract_id'])) {
                $contract = self::get_contract(intval($payment['contract_id']));
                if ($contract && $contract['status'] === 'active') {
                    self::transition_contract_status(
                        intval($payment['contract_id']),
                        'suspended',
                        __('Automatisch suspendiert wegen offener Zahlung nach Mahnstufe 3.', 'themisdb-order-request'),
                        0
                    );
                }
            }
        }
    }

    /**
     * Synchronize linked order, license and notifications after contract status changes.
     */
    private static function sync_related_records_for_status($contract, $previous_status, $new_status, $reason, $changed_by) {
        if (!$contract) {
            return;
        }

        $order_id = !empty($contract['order_id']) ? intval($contract['order_id']) : 0;
        $license = ThemisDB_License_Manager::get_license_by_contract(intval($contract['id']));

        if ($new_status === 'active') {
            if ($order_id > 0) {
                ThemisDB_Order_Manager::set_order_status($order_id, 'active');
            }

            if ($license && $license['license_status'] !== 'cancelled') {
                ThemisDB_License_Manager::activate_license(intval($license['id']));
            }
        }

        if ($new_status === 'suspended') {
            if ($order_id > 0) {
                ThemisDB_Order_Manager::set_order_status($order_id, 'suspended');
            }

            if ($license && $license['license_status'] !== 'cancelled') {
                ThemisDB_License_Manager::suspend_license(intval($license['id']), $reason);
            }
        }

        if ($new_status === 'ended') {
            if ($order_id > 0) {
                ThemisDB_Order_Manager::set_order_status($order_id, 'ended');
            }

            if ($license && !in_array($license['license_status'], array('cancelled', 'expired'), true)) {
                ThemisDB_License_Manager::update_license(intval($license['id']), array(
                    'license_status' => 'expired',
                    'expiry_date' => gmdate('Y-m-d'),
                ));
            }
        }

        if ($new_status === 'cancelled') {
            if ($order_id > 0) {
                ThemisDB_Order_Manager::set_order_status($order_id, 'cancelled');
            }

            if ($license && $license['license_status'] !== 'cancelled') {
                ThemisDB_License_Manager::cancel_license(intval($license['id']), $reason, $changed_by);
            }
        }

        if (in_array($new_status, array('suspended', 'ended', 'cancelled'), true)) {
            try {
                ThemisDB_Email_Handler::send_contract_status_email(intval($contract['id']), $new_status, array(
                    'reason' => $reason,
                    'previous_status' => $previous_status,
                ));
            } catch (Throwable $e) {
                error_log('ThemisDB Contract Status Email Error: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Create a new contract
     */
    public static function create_contract($data) {
        global $wpdb;
        
        $table_contracts = $wpdb->prefix . 'themisdb_contracts';
        
        // Generate contract number
        $contract_number = self::generate_contract_number($data['contract_type']);
        
        $contract_data = array(
            'contract_number' => $contract_number,
            'order_id' => intval($data['order_id']),
            'customer_id' => isset($data['customer_id']) ? intval($data['customer_id']) : get_current_user_id(),
            'contract_type' => sanitize_text_field($data['contract_type']),
            'contract_data' => json_encode($data['contract_data']),
            'status' => 'draft',
            'valid_from' => isset($data['valid_from']) ? $data['valid_from'] : date('Y-m-d'),
            'valid_until' => isset($data['valid_until']) ? $data['valid_until'] : null
        );
        
        $result = $wpdb->insert($table_contracts, $contract_data);
        
        if ($result) {
            $contract_id = $wpdb->insert_id;
            
            // Create initial revision
            self::create_revision($contract_id, $data['contract_data'], get_current_user_id(), 'Initial creation');
            
            return $contract_id;
        }
        
        return false;
    }
    
    /**
     * Update an existing contract
     */
    public static function update_contract($contract_id, $data, $change_reason = '') {
        global $wpdb;
        
        $table_contracts = $wpdb->prefix . 'themisdb_contracts';
        
        // Get current contract for revision
        $current_contract = self::get_contract($contract_id);
        if (!$current_contract) {
            return false;
        }
        
        $update_data = array();
        
        if (isset($data['contract_type'])) {
            $update_data['contract_type'] = sanitize_text_field($data['contract_type']);
        }
        if (isset($data['contract_data'])) {
            $update_data['contract_data'] = json_encode($data['contract_data']);
            
            // Create revision for data changes
            self::create_revision(
                $contract_id,
                $data['contract_data'],
                get_current_user_id(),
                $change_reason
            );
        }
        if (isset($data['status'])) {
            $update_data['status'] = sanitize_text_field($data['status']);
        }
        if (isset($data['signed_at'])) {
            $update_data['signed_at'] = $data['signed_at'];
        }
        if (isset($data['valid_from'])) {
            $update_data['valid_from'] = $data['valid_from'];
        }
        if (isset($data['valid_until'])) {
            $update_data['valid_until'] = $data['valid_until'];
        }
        if (isset($data['pdf_file_id'])) {
            $update_data['pdf_file_id'] = intval($data['pdf_file_id']);
        }
        if (isset($data['pdf_data'])) {
            $update_data['pdf_data'] = $data['pdf_data'];
        }
        
        if (empty($update_data)) {
            return false;
        }
        
        $result = $wpdb->update(
            $table_contracts,
            $update_data,
            array('id' => $contract_id),
            null,
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Get contract by ID
     */
    public static function get_contract($contract_id) {
        global $wpdb;
        
        $table_contracts = $wpdb->prefix . 'themisdb_contracts';
        
        $contract = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_contracts WHERE id = %d",
            $contract_id
        ), ARRAY_A);
        
        if ($contract && $contract['contract_data']) {
            $contract['contract_data'] = json_decode($contract['contract_data'], true);
        }
        
        return $contract;
    }
    
    /**
     * Get contract by contract number
     */
    public static function get_contract_by_number($contract_number) {
        global $wpdb;
        
        $table_contracts = $wpdb->prefix . 'themisdb_contracts';
        
        $contract = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_contracts WHERE contract_number = %s",
            $contract_number
        ), ARRAY_A);
        
        if ($contract && $contract['contract_data']) {
            $contract['contract_data'] = json_decode($contract['contract_data'], true);
        }
        
        return $contract;
    }
    
    /**
     * Get contracts by order
     */
    public static function get_contracts_by_order($order_id) {
        global $wpdb;
        
        $table_contracts = $wpdb->prefix . 'themisdb_contracts';
        
        $contracts = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_contracts WHERE order_id = %d ORDER BY created_at DESC",
            $order_id
        ), ARRAY_A);
        
        foreach ($contracts as &$contract) {
            if ($contract['contract_data']) {
                $contract['contract_data'] = json_decode($contract['contract_data'], true);
            }
        }
        
        return $contracts;
    }
    
    /**
     * Get contracts by customer
     */
    public static function get_customer_contracts($customer_id, $status = null) {
        global $wpdb;
        
        $table_contracts = $wpdb->prefix . 'themisdb_contracts';
        
        if ($status) {
            $contracts = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_contracts WHERE customer_id = %d AND status = %s ORDER BY created_at DESC",
                $customer_id,
                $status
            ), ARRAY_A);
        } else {
            $contracts = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_contracts WHERE customer_id = %d ORDER BY created_at DESC",
                $customer_id
            ), ARRAY_A);
        }
        
        foreach ($contracts as &$contract) {
            if ($contract['contract_data']) {
                $contract['contract_data'] = json_decode($contract['contract_data'], true);
            }
        }
        
        return $contracts;
    }
    
    /**
     * Get all contracts
     */
    public static function get_all_contracts($args = array()) {
        global $wpdb;
        
        $table_contracts = $wpdb->prefix . 'themisdb_contracts';
        
        $defaults = array(
            'status' => null,
            'contract_type' => null,
            'limit' => 50,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = "1=1";
        $where_values = array();
        
        if ($args['status']) {
            $where .= " AND status = %s";
            $where_values[] = $args['status'];
        }
        
        if ($args['contract_type']) {
            $where .= " AND contract_type = %s";
            $where_values[] = $args['contract_type'];
        }
        
        $query = "SELECT * FROM $table_contracts WHERE $where ORDER BY {$args['orderby']} {$args['order']} LIMIT %d OFFSET %d";
        $where_values[] = $args['limit'];
        $where_values[] = $args['offset'];
        
        $contracts = $wpdb->get_results($wpdb->prepare($query, $where_values), ARRAY_A);
        
        foreach ($contracts as &$contract) {
            if ($contract['contract_data']) {
                $contract['contract_data'] = json_decode($contract['contract_data'], true);
            }
        }
        
        return $contracts;
    }
    
    /**
     * Delete a contract
     */
    public static function delete_contract($contract_id) {
        global $wpdb;
        
        $table_contracts = $wpdb->prefix . 'themisdb_contracts';
        $table_revisions = $wpdb->prefix . 'themisdb_contract_revisions';
        
        // Delete revisions first
        $wpdb->delete(
            $table_revisions,
            array('contract_id' => $contract_id),
            array('%d')
        );
        
        // Delete contract
        $result = $wpdb->delete(
            $table_contracts,
            array('id' => $contract_id),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Create a contract revision
     */
    private static function create_revision($contract_id, $contract_data, $user_id, $change_reason = '') {
        global $wpdb;
        
        $table_revisions = $wpdb->prefix . 'themisdb_contract_revisions';
        
        // Get current revision number
        $last_revision = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(revision_number) FROM $table_revisions WHERE contract_id = %d",
            $contract_id
        ));
        
        $revision_number = $last_revision ? $last_revision + 1 : 1;
        
        $revision_data = array(
            'contract_id' => $contract_id,
            'revision_number' => $revision_number,
            'contract_data' => json_encode($contract_data),
            'changed_by' => $user_id,
            'change_reason' => sanitize_text_field($change_reason)
        );
        
        return $wpdb->insert($table_revisions, $revision_data);
    }
    
    /**
     * Get contract revisions
     */
    public static function get_contract_revisions($contract_id) {
        global $wpdb;
        
        $table_revisions = $wpdb->prefix . 'themisdb_contract_revisions';
        
        $revisions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_revisions WHERE contract_id = %d ORDER BY revision_number DESC",
            $contract_id
        ), ARRAY_A);
        
        foreach ($revisions as &$revision) {
            if ($revision['contract_data']) {
                $revision['contract_data'] = json_decode($revision['contract_data'], true);
            }
        }
        
        return $revisions;
    }
    
    /**
     * Generate contract number
     */
    private static function generate_contract_number($contract_type) {
        $prefix = 'CTR';
        
        // Add type prefix
        switch ($contract_type) {
            case 'license':
                $prefix = 'LIC';
                break;
            case 'service':
                $prefix = 'SRV';
                break;
            case 'support':
                $prefix = 'SUP';
                break;
            default:
                $prefix = 'CTR';
        }
        
        $date = date('Ymd');
        $random = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
        
        return $prefix . '-' . $date . '-' . $random;
    }
    
    /**
     * Sign contract (mark as signed)
     */
    public static function sign_contract($contract_id, $signature_data = null) {
        global $wpdb;
        
        $table_contracts = $wpdb->prefix . 'themisdb_contracts';
        
        $update_data = array(
            'status' => 'signed',
            'signed_at' => current_time('mysql')
        );
        
        if ($signature_data) {
            $current_data = self::get_contract($contract_id);
            if ($current_data) {
                $contract_data = $current_data['contract_data'];
                $contract_data['signature'] = $signature_data;
                $update_data['contract_data'] = json_encode($contract_data);
            }
        }
        
        $result = $wpdb->update(
            $table_contracts,
            $update_data,
            array('id' => $contract_id),
            null,
            array('%d')
        );
        
        if ($result !== false) {
            // Create revision for signing
            self::create_revision(
                $contract_id,
                $current_data['contract_data'],
                get_current_user_id(),
                'Contract signed'
            );
        }
        
        return $result !== false;
    }
}
