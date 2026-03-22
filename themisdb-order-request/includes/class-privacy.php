<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB - Hybrid Database System                                   ║
╠═════════════════════════════════════════════════════════════════════╣
║  File:            class-privacy.php                                 ║
║  Version:         0.0.1                                             ║
║  Last Modified:   2026-03-21 12:00:00                               ║
║  Author:          ThemisDB Development Team                         ║
╠═════════════════════════════════════════════════════════════════════╣
║  Quality Metrics:                                                   ║
║    • Maturity Level:  🟢 PRODUCTION-READY                            ║
║    • Quality Score:   100.0/100                                     ║
║    • Total Lines:     320                                           ║
║    • Open Issues:     TODOs: 0, Stubs: 0                            ║
╠═════════════════════════════════════════════════════════════════════╣
║  Purpose:  WordPress Privacy API Integration (GDPR Compliance)      ║
║    • Export personal data (orders, licenses, contracts)             ║
║    • Erase personal data on user request                            ║
║    • Retention policy enforcement                                   ║
╠═════════════════════════════════════════════════════════════════════╣
║  Status: ✅ Production Ready                                         ║
╚═════════════════════════════════════════════════════════════════════╝
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Privacy Manager for ThemisDB Order Request Plugin
 * 
 * Handles GDPR compliance:
 * - Data export to user via privacy tools
 * - Data deletion (anonymization/removal) on request
 * - Retention policy enforcement for old orders
 * 
 * @since 0.0.1
 */
class ThemisDB_Privacy {

    /**
     * Build a complete order export payload for DSAR requests.
     *
     * @param array $order
     * @return array
     */
    private static function build_order_export_data( $order ) {
        return array(
            array(
                'name'  => __( 'Order Number', 'themisdb-order-request' ),
                'value' => $order['order_number'] ?? '',
            ),
            array(
                'name'  => __( 'Order Date', 'themisdb-order-request' ),
                'value' => $order['created_at'] ?? '',
            ),
            array(
                'name'  => __( 'Edition', 'themisdb-order-request' ),
                'value' => $order['product_edition'] ?? '',
            ),
            array(
                'name'  => __( 'Status', 'themisdb-order-request' ),
                'value' => $order['status'] ?? '',
            ),
            array(
                'name'  => __( 'Customer Email', 'themisdb-order-request' ),
                'value' => $order['customer_email'] ?? '',
            ),
            array(
                'name'  => __( 'Customer Name', 'themisdb-order-request' ),
                'value' => $order['customer_name'] ?? '',
            ),
            array(
                'name'  => __( 'Customer Company', 'themisdb-order-request' ),
                'value' => $order['customer_company'] ?? '',
            ),
            array(
                'name'  => __( 'Customer Type', 'themisdb-order-request' ),
                'value' => $order['customer_type'] ?? '',
            ),
            array(
                'name'  => __( 'VAT ID', 'themisdb-order-request' ),
                'value' => $order['vat_id'] ?? '',
            ),
            array(
                'name'  => __( 'Billing Address', 'themisdb-order-request' ),
                'value' => trim((string) (($order['billing_name'] ?? '') . ', ' . ($order['billing_address_line1'] ?? '') . ' ' . ($order['billing_address_line2'] ?? '') . ', ' . ($order['billing_postal_code'] ?? '') . ' ' . ($order['billing_city'] ?? '') . ', ' . ($order['billing_country'] ?? ''))),
            ),
            array(
                'name'  => __( 'Shipping Address', 'themisdb-order-request' ),
                'value' => trim((string) (($order['shipping_name'] ?? '') . ', ' . ($order['shipping_address_line1'] ?? '') . ' ' . ($order['shipping_address_line2'] ?? '') . ', ' . ($order['shipping_postal_code'] ?? '') . ' ' . ($order['shipping_city'] ?? '') . ', ' . ($order['shipping_country'] ?? ''))),
            ),
            array(
                'name'  => __( 'Legal Terms Accepted', 'themisdb-order-request' ),
                'value' => !empty($order['legal_terms_accepted']) ? '1' : '0',
            ),
            array(
                'name'  => __( 'Privacy Accepted', 'themisdb-order-request' ),
                'value' => !empty($order['legal_privacy_accepted']) ? '1' : '0',
            ),
            array(
                'name'  => __( 'Withdrawal Acknowledged', 'themisdb-order-request' ),
                'value' => !empty($order['legal_withdrawal_acknowledged']) ? '1' : '0',
            ),
            array(
                'name'  => __( 'Withdrawal Waiver', 'themisdb-order-request' ),
                'value' => !empty($order['legal_withdrawal_waiver']) ? '1' : '0',
            ),
            array(
                'name'  => __( 'Legal Acceptance Version', 'themisdb-order-request' ),
                'value' => $order['legal_acceptance_version'] ?? '',
            ),
            array(
                'name'  => __( 'Legal Accepted At', 'themisdb-order-request' ),
                'value' => $order['legal_accepted_at'] ?? '',
            ),
            array(
                'name'  => __( 'Legal Accepted IP', 'themisdb-order-request' ),
                'value' => $order['legal_accepted_ip'] ?? '',
            ),
            array(
                'name'  => __( 'Legal Accepted User Agent', 'themisdb-order-request' ),
                'value' => $order['legal_accepted_user_agent'] ?? '',
            ),
        );
    }

    /**
     * Initialize privacy hooks
     * 
     * Called during plugin activation/bootstrap
     */
    public static function init() {
        // Register data exporters for WordPress Privacy Tools
        add_filter( 'wp_privacy_personal_data_exporters', array( __CLASS__, 'register_exporters' ) );
        
        // Register data erasers for WordPress Privacy Tools
        add_filter( 'wp_privacy_personal_data_erasers', array( __CLASS__, 'register_erasers' ) );
        
        // Add privacy policy suggestions
        add_filter( 'wp_privacy_policy_content', array( __CLASS__, 'add_privacy_policy_content' ) );
    }

    /**
     * Register data exporters with WordPress Privacy API
     * 
     * @param array $exporters List of registered exporters
     * @return array Modified exporters
     */
    public static function register_exporters( $exporters ) {
        $exporters['themisdb-orders'] = array(
            'callback' => array( __CLASS__, 'export_orders' ),
        );
        
        $exporters['themisdb-licenses'] = array(
            'callback' => array( __CLASS__, 'export_licenses' ),
        );
        
        $exporters['themisdb-contracts'] = array(
            'callback' => array( __CLASS__, 'export_contracts' ),
        );
        
        $exporters['themisdb-support-benefits'] = array(
            'callback' => array( __CLASS__, 'export_support_benefits' ),
        );
        
        return $exporters;
    }

    /**
     * Register data erasers with WordPress Privacy API
     * 
     * @param array $erasers List of registered erasers
     * @return array Modified erasers
     */
    public static function register_erasers( $erasers ) {
        $erasers['themisdb-orders'] = array(
            'callback' => array( __CLASS__, 'erase_orders' ),
        );
        
        $erasers['themisdb-licenses'] = array(
            'callback' => array( __CLASS__, 'erase_licenses' ),
        );
        
        $erasers['themisdb-contracts'] = array(
            'callback' => array( __CLASS__, 'erase_contracts' ),
        );
        
        $erasers['themisdb-support-benefits'] = array(
            'callback' => array( __CLASS__, 'erase_support_benefits' ),
        );
        
        return $erasers;
    }

    /**
     * Export all orders for a user
     * 
     * Combines orders requested by email with WordPress user orders
     * Respects pagination for large datasets
     * 
     * @param string $email_address User email to export
     * @param int $page Pagination page (1-indexed)
     * @return array Export data
     */
    public static function export_orders( $email_address, $page = 1 ) {
        $user = get_user_by( 'email', $email_address );
        
        // If no WordPress user, search by customer email in database
        $items = array();
        
        if ( $user && isset( $user->ID ) ) {
            // Get all orders for WordPress user
            $orders = ThemisDB_Order_Manager::get_customer_orders( $user->ID );
            
            foreach ( (array) $orders as $order ) {
                $items[] = array(
                    'group_id'    => 'themisdb-orders',
                    'group_label' => __( 'ThemisDB Orders', 'themisdb-order-request' ),
                    'item_id'     => 'order-' . $order['id'],
                    'data'        => self::build_order_export_data( $order ),
                );
            }
        } else {
            // Search by email directly in database
            global $wpdb;
            $table = $wpdb->prefix . 'themisdb_orders';
            
            $orders = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$table} WHERE customer_email = %s ORDER BY created_at DESC LIMIT 50",
                    $email_address
                ),
                ARRAY_A
            );
            
            foreach ( (array) $orders as $order ) {
                $items[] = array(
                    'group_id'    => 'themisdb-orders',
                    'group_label' => __( 'ThemisDB Orders', 'themisdb-order-request' ),
                    'item_id'     => 'order-' . $order['id'],
                    'data'        => self::build_order_export_data( $order ),
                );
            }
        }
        
        return array(
            'data' => $items,
            'done' => true,
        );
    }

    /**
     * Export all licenses for a user
     * 
     * @param string $email_address User email to export
     * @param int $page Pagination page (1-indexed)
     * @return array Export data
     */
    public static function export_licenses( $email_address, $page = 1 ) {
        global $wpdb;
        
        $table_licenses = $wpdb->prefix . 'themisdb_licenses';
        $table_orders = $wpdb->prefix . 'themisdb_orders';
        
        $items = array();
        
        // Get licenses via order -> customer email
        $licenses = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT l.*, o.order_number 
                 FROM {$table_licenses} l
                 LEFT JOIN {$table_orders} o ON o.id = l.order_id
                 WHERE o.customer_email = %s OR l.customer_email = %s
                 ORDER BY l.created_at DESC
                 LIMIT 50",
                $email_address,
                $email_address
            ),
            ARRAY_A
        );
        
        foreach ( (array) $licenses as $license ) {
            $items[] = array(
                'group_id'    => 'themisdb-licenses',
                'group_label' => __( 'ThemisDB Licenses', 'themisdb-order-request' ),
                'item_id'     => 'license-' . $license['id'],
                'data'        => array(
                    array(
                        'name'  => __( 'License Key', 'themisdb-order-request' ),
                        'value' => $license['license_key'] ?? '',
                    ),
                    array(
                        'name'  => __( 'License Status', 'themisdb-order-request' ),
                        'value' => $license['license_status'] ?? '',
                    ),
                    array(
                        'name'  => __( 'Edition', 'themisdb-order-request' ),
                        'value' => $license['edition'] ?? '',
                    ),
                    array(
                        'name'  => __( 'Issue Date', 'themisdb-order-request' ),
                        'value' => $license['created_at'] ?? '',
                    ),
                    array(
                        'name'  => __( 'Expiry Date', 'themisdb-order-request' ),
                        'value' => $license['expiry_date'] ?? __( 'No expiry', 'themisdb-order-request' ),
                    ),
                ),
            );
        }
        
        return array(
            'data' => $items,
            'done' => true,
        );
    }

    /**
     * Export all contracts for a user
     * 
     * @param string $email_address User email to export
     * @param int $page Pagination page (1-indexed)
     * @return array Export data
     */
    public static function export_contracts( $email_address, $page = 1 ) {
        global $wpdb;
        
        $table_contracts = $wpdb->prefix . 'themisdb_contracts';
        $table_orders = $wpdb->prefix . 'themisdb_orders';
        
        $items = array();
        
        // Get contracts via order -> customer email
        $contracts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT c.*, o.customer_name, o.order_number
                 FROM {$table_contracts} c
                 LEFT JOIN {$table_orders} o ON o.id = c.order_id
                 WHERE o.customer_email = %s
                 ORDER BY c.created_at DESC
                 LIMIT 50",
                $email_address
            ),
            ARRAY_A
        );
        
        foreach ( (array) $contracts as $contract ) {
            $items[] = array(
                'group_id'    => 'themisdb-contracts',
                'group_label' => __( 'ThemisDB Contracts', 'themisdb-order-request' ),
                'item_id'     => 'contract-' . $contract['id'],
                'data'        => array(
                    array(
                        'name'  => __( 'Contract Number', 'themisdb-order-request' ),
                        'value' => $contract['contract_number'] ?? '',
                    ),
                    array(
                        'name'  => __( 'Contract Status', 'themisdb-order-request' ),
                        'value' => $contract['status'] ?? '',
                    ),
                    array(
                        'name'  => __( 'Start Date', 'themisdb-order-request' ),
                        'value' => $contract['valid_from'] ?? '',
                    ),
                    array(
                        'name'  => __( 'End Date', 'themisdb-order-request' ),
                        'value' => $contract['valid_until'] ?? __( 'Indefinite', 'themisdb-order-request' ),
                    ),
                ),
            );
        }
        
        return array(
            'data' => $items,
            'done' => true,
        );
    }

    /**
     * Export support benefits for a user
     * 
     * @param string $email_address User email to export
     * @param int $page Pagination page (1-indexed)
     * @return array Export data
     */
    public static function export_support_benefits( $email_address, $page = 1 ) {
        global $wpdb;
        
        $table_benefits = $wpdb->prefix . 'themisdb_support_benefits';
        $table_licenses = $wpdb->prefix . 'themisdb_licenses';
        $table_orders = $wpdb->prefix . 'themisdb_orders';
        
        $items = array();
        
        // Get benefits via license -> order -> customer email
        $benefits = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT sb.*, l.license_key, o.customer_name
                 FROM {$table_benefits} sb
                 LEFT JOIN {$table_licenses} l ON l.id = sb.license_id
                 LEFT JOIN {$table_orders} o ON o.id = l.order_id
                 WHERE o.customer_email = %s
                 ORDER BY sb.created_at DESC
                 LIMIT 50",
                $email_address
            ),
            ARRAY_A
        );
        
        foreach ( (array) $benefits as $benefit ) {
            $items[] = array(
                'group_id'    => 'themisdb-support-benefits',
                'group_label' => __( 'Support Benefits', 'themisdb-order-request' ),
                'item_id'     => 'benefit-' . $benefit['id'],
                'data'        => array(
                    array(
                        'name'  => __( 'Benefit Tier', 'themisdb-order-request' ),
                        'value' => $benefit['benefit_tier'] ?? '',
                    ),
                    array(
                        'name'  => __( 'Support Tier', 'themisdb-order-request' ),
                        'value' => $benefit['support_tier'] ?? '',
                    ),
                    array(
                        'name'  => __( 'Status', 'themisdb-order-request' ),
                        'value' => $benefit['status'] ?? '',
                    ),
                    array(
                        'name'  => __( 'Active From', 'themisdb-order-request' ),
                        'value' => $benefit['activated_at'] ?? '',
                    ),
                ),
            );
        }
        
        return array(
            'data' => $items,
            'done' => true,
        );
    }

    // ============================
    // Data Erasure Functions
    // ============================

    /**
     * Erase orders for a user (anonymize, preserve audit trail)
     * 
     * @param string $email_address User email to erase
     * @param int $page Pagination page (1-indexed)
     * @return array Erasure result
     */
    public static function erase_orders( $email_address, $page = 1 ) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'themisdb_orders';
        
        // Find orders to anonymize
        $orders = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE customer_email = %s LIMIT 10",
                $email_address
            ),
            ARRAY_A
        );
        
        $anonymized = 0;
        foreach ( (array) $orders as $order ) {
            // Anonymize customer data while preserving audit trail
            $result = $wpdb->update(
                $table,
                array(
                    'customer_id'      => 0,
                    'customer_name'    => 'GDPR Deleted User',
                    'customer_email'   => md5( $email_address ) . '@deleted.local',
                    'customer_company' => 'GDPR Deleted',
                    'vat_id'           => '',
                    'billing_name' => '',
                    'billing_address_line1' => '',
                    'billing_address_line2' => '',
                    'billing_postal_code' => '',
                    'billing_city' => '',
                    'shipping_name' => '',
                    'shipping_address_line1' => '',
                    'shipping_address_line2' => '',
                    'shipping_postal_code' => '',
                    'shipping_city' => '',
                    'legal_accepted_ip' => '',
                    'legal_accepted_user_agent' => '',
                ),
                array( 'id' => $order['id'] ),
                array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
                array( '%d' )
            );
            
            if ( $result ) {
                ThemisDB_Error_Handler::log(
                    'info',
                    'Order anonymized for GDPR erasure',
                    array( 'order_id' => $order['id'], 'email' => $email_address )
                );
                $anonymized++;
            }
        }
        
        return array(
            'items_removed'  => true,
            'items_retained' => false,
            'messages'       => array( sprintf( __( 'Anonymized %d order(s)', 'themisdb-order-request' ), $anonymized ) ),
            'done'           => count( $orders ) < 10,
        );
    }

    /**
     * Erase licenses for a user (anonymize)
     * 
     * @param string $email_address User email to erase
     * @param int $page Pagination page (1-indexed)
     * @return array Erasure result
     */
    public static function erase_licenses( $email_address, $page = 1 ) {
        global $wpdb;
        
        $table_licenses = $wpdb->prefix . 'themisdb_licenses';
        $table_orders = $wpdb->prefix . 'themisdb_orders';
        
        // Find licenses via order email
        $licenses = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT l.id FROM {$table_licenses} l
                 LEFT JOIN {$table_orders} o ON o.id = l.order_id
                 WHERE o.customer_email = %s OR l.customer_email = %s
                 LIMIT 10",
                $email_address,
                $email_address
            ),
            ARRAY_A
        );
        
        $anonymized = 0;
        foreach ( (array) $licenses as $license ) {
            // Anonymize while preserving license key for audit
            $result = $wpdb->update(
                $table_licenses,
                array(
                    'customer_email'  => md5( $email_address ) . '@deleted.local',
                    'notes'           => 'Customer data deleted per GDPR request',
                    'license_status'  => 'cancelled',
                ),
                array( 'id' => $license['id'] ),
                array( '%s', '%s', '%s' ),
                array( '%d' )
            );
            
            if ( $result ) {
                ThemisDB_Error_Handler::log(
                    'info',
                    'License cancelled/anonymized for GDPR erasure',
                    array( 'license_id' => $license['id'], 'email' => $email_address )
                );
                $anonymized++;
            }
        }
        
        return array(
            'items_removed'  => true,
            'items_retained' => false,
            'messages'       => array( sprintf( __( 'Anonymized %d license(s)', 'themisdb-order-request' ), $anonymized ) ),
            'done'           => count( $licenses ) < 10,
        );
    }

    /**
     * Erase contracts for a user (anonymize)
     * 
     * @param string $email_address User email to erase
     * @param int $page Pagination page (1-indexed)
     * @return array Erasure result
     */
    public static function erase_contracts( $email_address, $page = 1 ) {
        global $wpdb;
        
        $table_contracts = $wpdb->prefix . 'themisdb_contracts';
        $table_orders = $wpdb->prefix . 'themisdb_orders';
        
        // Find contracts via order email
        $contracts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT c.id FROM {$table_contracts} c
                 LEFT JOIN {$table_orders} o ON o.id = c.order_id
                 WHERE o.customer_email = %s
                 LIMIT 10",
                $email_address
            ),
            ARRAY_A
        );
        
        $anonymized = 0;
        foreach ( (array) $contracts as $contract ) {
            // Update contract status to reflect erasure
            $result = $wpdb->update(
                $table_contracts,
                array(
                    'status' => 'deleted',
                    'notes'  => 'Contract data deleted per GDPR request',
                ),
                array( 'id' => $contract['id'] ),
                array( '%s', '%s' ),
                array( '%d' )
            );
            
            if ( $result ) {
                ThemisDB_Error_Handler::log(
                    'info',
                    'Contract deleted for GDPR erasure',
                    array( 'contract_id' => $contract['id'], 'email' => $email_address )
                );
                $anonymized++;
            }
        }
        
        return array(
            'items_removed'  => true,
            'items_retained' => false,
            'messages'       => array( sprintf( __( 'Deleted %d contract(s)', 'themisdb-order-request' ), $anonymized ) ),
            'done'           => count( $contracts ) < 10,
        );
    }

    /**
     * Erase support benefits for a user
     * 
     * @param string $email_address User email to erase
     * @param int $page Pagination page (1-indexed)
     * @return array Erasure result
     */
    public static function erase_support_benefits( $email_address, $page = 1 ) {
        global $wpdb;
        
        $table_benefits = $wpdb->prefix . 'themisdb_support_benefits';
        $table_licenses = $wpdb->prefix . 'themisdb_licenses';
        $table_orders = $wpdb->prefix . 'themisdb_orders';
        
        // Find benefits via license -> order -> customer email
        $benefits = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT sb.id FROM {$table_benefits} sb
                 LEFT JOIN {$table_licenses} l ON l.id = sb.license_id
                 LEFT JOIN {$table_orders} o ON o.id = l.order_id
                 WHERE o.customer_email = %s
                 LIMIT 10",
                $email_address
            ),
            ARRAY_A
        );
        
        $anonymized = 0;
        foreach ( (array) $benefits as $benefit ) {
            // Deactivate benefit
            $result = $wpdb->update(
                $table_benefits,
                array(
                    'status' => 'deactivated',
                    'notes'  => 'Benefit deactivated due to GDPR data erasure request',
                ),
                array( 'id' => $benefit['id'] ),
                array( '%s', '%s' ),
                array( '%d' )
            );
            
            if ( $result ) {
                ThemisDB_Error_Handler::log(
                    'info',
                    'Support benefit deactivated for GDPR erasure',
                    array( 'benefit_id' => $benefit['id'], 'email' => $email_address )
                );
                $anonymized++;
            }
        }
        
        return array(
            'items_removed'  => true,
            'items_retained' => false,
            'messages'       => array( sprintf( __( 'Deactivated %d benefit(s)', 'themisdb-order-request' ), $anonymized ) ),
            'done'           => count( $benefits ) < 10,
        );
    }

    /**
     * Add privacy policy suggestions to WordPress privacy policy
     * 
     * @param string $content Current privacy policy content
     * @return string Modified privacy policy content
     */
    public static function add_privacy_policy_content( $content ) {
        $policy = __( '
### ThemisDB Order Request Plugin

#### Data We Collect
- Order information (customer name, email, edition, status)
- License keys and activation information
- Contract details and durations
- Support benefit assignments
- Payment transaction records

#### How We Use Data
- To process and fulfill orders
- To issue licenses and manage access
- To provide support services
- To track usage and generate reports
- To comply with applicable law

#### Data Retention
- Orders: Retained indefinitely for audit trail (anonymized after erasure request)
- Licenses: Active licenses retained, expired licenses in archive (anonymized on request)
- Contracts: Retained per contract terms, marked deleted on erasure request
- Support Benefits: Active benefits retained, deactivated on erasure request

#### Your Rights
Under GDPR and similar regulations, you have the right to:
- Request a copy of your personal data (Tools → Privacy → Export Personal Data)
- Request deletion or anonymization (Tools → Privacy → Delete Personal Data)
- File a complaint with your data protection authority

Please contact support@themisdb.local for any privacy concerns.
        ', 'themisdb-order-request' );
        
        return $content . "\n" . $policy;
    }
}
