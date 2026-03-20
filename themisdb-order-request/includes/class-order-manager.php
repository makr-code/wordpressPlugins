<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB - Hybrid Database System                                   ║
╠═════════════════════════════════════════════════════════════════════╣
  File:            class-order-manager.php                            ║
  Version:         0.0.2                                              ║
  Last Modified:   2026-03-09 04:08:19                                ║
  Author:          unknown                                            ║
╠═════════════════════════════════════════════════════════════════════╣
  Quality Metrics:                                                    ║
    • Maturity Level:  🟢 PRODUCTION-READY                             ║
    • Quality Score:   100.0/100                                      ║
    • Total Lines:     385                                            ║
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
 * Order Manager for ThemisDB Order Request Plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class ThemisDB_Order_Manager {
    
    /**
     * Create a new order
     */
    public static function create_order($data) {
        global $wpdb;
        
        $table_orders = $wpdb->prefix . 'themisdb_orders';

        $product_edition = isset($data['product_edition']) ? sanitize_text_field($data['product_edition']) : 'community';
        $product_type = isset($data['product_type']) ? sanitize_text_field($data['product_type']) : '';
        if ($product_type === '') {
            $product = self::get_product_by_edition($product_edition);
            $product_type = ($product && !empty($product['product_type'])) ? $product['product_type'] : 'database';
        }
        
        // Generate order number
        $order_number = self::generate_order_number();
        
        $initial_status = self::normalize_order_status(isset($data['status']) ? $data['status'] : 'draft');
        if ($initial_status === null) {
            $initial_status = 'draft';
        }

        $order_data = array(
            'order_number' => $order_number,
            'customer_id' => isset($data['customer_id']) ? intval($data['customer_id']) : get_current_user_id(),
            'customer_email' => sanitize_email($data['customer_email']),
            'customer_name' => sanitize_text_field($data['customer_name']),
            'customer_company' => isset($data['customer_company']) ? sanitize_text_field($data['customer_company']) : null,
            'customer_type' => isset($data['customer_type']) ? sanitize_text_field($data['customer_type']) : 'consumer',
            'vat_id' => isset($data['vat_id']) ? sanitize_text_field($data['vat_id']) : null,
            'billing_name' => isset($data['billing_name']) ? sanitize_text_field($data['billing_name']) : null,
            'billing_address_line1' => isset($data['billing_address_line1']) ? sanitize_text_field($data['billing_address_line1']) : null,
            'billing_address_line2' => isset($data['billing_address_line2']) ? sanitize_text_field($data['billing_address_line2']) : null,
            'billing_postal_code' => isset($data['billing_postal_code']) ? sanitize_text_field($data['billing_postal_code']) : null,
            'billing_city' => isset($data['billing_city']) ? sanitize_text_field($data['billing_city']) : null,
            'billing_country' => isset($data['billing_country']) ? strtoupper(sanitize_text_field($data['billing_country'])) : 'DE',
            'product_type' => $product_type,
            'product_edition' => $product_edition,
            'modules' => isset($data['modules']) ? json_encode($data['modules']) : null,
            'training_modules' => isset($data['training_modules']) ? json_encode($data['training_modules']) : null,
            'legal_terms_accepted' => !empty($data['legal_terms_accepted']) ? 1 : 0,
            'legal_privacy_accepted' => !empty($data['legal_privacy_accepted']) ? 1 : 0,
            'legal_withdrawal_acknowledged' => !empty($data['legal_withdrawal_acknowledged']) ? 1 : 0,
            'legal_withdrawal_waiver' => !empty($data['legal_withdrawal_waiver']) ? 1 : 0,
            'legal_acceptance_version' => isset($data['legal_acceptance_version']) ? sanitize_text_field($data['legal_acceptance_version']) : 'de-v1',
            'legal_accepted_at' => isset($data['legal_accepted_at']) ? sanitize_text_field($data['legal_accepted_at']) : null,
            'legal_accepted_ip' => isset($data['legal_accepted_ip']) ? sanitize_text_field($data['legal_accepted_ip']) : null,
            'legal_accepted_user_agent' => isset($data['legal_accepted_user_agent']) ? sanitize_text_field($data['legal_accepted_user_agent']) : null,
            'shipping_name' => isset($data['shipping_name']) ? sanitize_text_field($data['shipping_name']) : null,
            'shipping_address_line1' => isset($data['shipping_address_line1']) ? sanitize_text_field($data['shipping_address_line1']) : null,
            'shipping_address_line2' => isset($data['shipping_address_line2']) ? sanitize_text_field($data['shipping_address_line2']) : null,
            'shipping_postal_code' => isset($data['shipping_postal_code']) ? sanitize_text_field($data['shipping_postal_code']) : null,
            'shipping_city' => isset($data['shipping_city']) ? sanitize_text_field($data['shipping_city']) : null,
            'shipping_country' => isset($data['shipping_country']) ? strtoupper(sanitize_text_field($data['shipping_country'])) : 'DE',
            'shipping_method' => isset($data['shipping_method']) ? sanitize_text_field($data['shipping_method']) : null,
            'shipping_cost' => isset($data['shipping_cost']) ? floatval($data['shipping_cost']) : 0.00,
            'tracking_number' => isset($data['tracking_number']) ? sanitize_text_field($data['tracking_number']) : null,
            'fulfillment_status' => isset($data['fulfillment_status']) ? sanitize_text_field($data['fulfillment_status']) : 'not_required',
            'fulfilled_at' => isset($data['fulfilled_at']) ? sanitize_text_field($data['fulfilled_at']) : null,
            'total_amount' => isset($data['total_amount']) ? floatval($data['total_amount']) : 0.00,
            'currency' => isset($data['currency']) ? sanitize_text_field($data['currency']) : 'EUR',
            'status' => $initial_status,
            'step' => 1
        );
        
        $result = $wpdb->insert($table_orders, $order_data);
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }

    /**
     * Get one active product by edition key.
     */
    public static function get_product_by_edition($edition) {
        global $wpdb;

        $table_products = $wpdb->prefix . 'themisdb_products';
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_products WHERE edition = %s AND is_active = 1 LIMIT 1",
                $edition
            ),
            ARRAY_A
        );
    }
    
    /**
     * Normalize order status to the canonical lifecycle value.
     */
    private static function normalize_order_status($status) {
        $normalized = sanitize_key((string) $status);

        if ($normalized === 'paid') {
            $normalized = 'confirmed';
        }

        $allowed_statuses = array('draft', 'pending', 'confirmed', 'signed', 'active', 'suspended', 'ended', 'cancelled', 'fulfilled', 'failed');
        if (!in_array($normalized, $allowed_statuses, true)) {
            return null;
        }

        return $normalized;
    }

    /**
     * Set only the order status using a dedicated, validated update path.
     */
    public static function set_order_status($order_id, $status) {
        global $wpdb;

        $normalized_status = self::normalize_order_status($status);
        if ($normalized_status === null) {
            return false;
        }

        $table_orders = $wpdb->prefix . 'themisdb_orders';

        $result = $wpdb->update(
            $table_orders,
            array('status' => $normalized_status),
            array('id' => intval($order_id)),
            array('%s'),
            array('%d')
        );

        return $result !== false;
    }

    /**
     * Update an existing order
     */
    public static function update_order($order_id, $data) {
        global $wpdb;
        
        $table_orders = $wpdb->prefix . 'themisdb_orders';
        
        $update_data = array();
        
        if (isset($data['customer_email'])) {
            $update_data['customer_email'] = sanitize_email($data['customer_email']);
        }
        if (isset($data['customer_name'])) {
            $update_data['customer_name'] = sanitize_text_field($data['customer_name']);
        }
        if (isset($data['customer_company'])) {
            $update_data['customer_company'] = sanitize_text_field($data['customer_company']);
        }
        if (isset($data['customer_type'])) {
            $update_data['customer_type'] = sanitize_text_field($data['customer_type']);
        }
        if (isset($data['vat_id'])) {
            $update_data['vat_id'] = sanitize_text_field($data['vat_id']);
        }
        if (isset($data['billing_name'])) {
            $update_data['billing_name'] = sanitize_text_field($data['billing_name']);
        }
        if (isset($data['billing_address_line1'])) {
            $update_data['billing_address_line1'] = sanitize_text_field($data['billing_address_line1']);
        }
        if (isset($data['billing_address_line2'])) {
            $update_data['billing_address_line2'] = sanitize_text_field($data['billing_address_line2']);
        }
        if (isset($data['billing_postal_code'])) {
            $update_data['billing_postal_code'] = sanitize_text_field($data['billing_postal_code']);
        }
        if (isset($data['billing_city'])) {
            $update_data['billing_city'] = sanitize_text_field($data['billing_city']);
        }
        if (isset($data['billing_country'])) {
            $update_data['billing_country'] = strtoupper(sanitize_text_field($data['billing_country']));
        }
        if (isset($data['product_type'])) {
            $update_data['product_type'] = sanitize_text_field($data['product_type']);
        }
        if (isset($data['product_edition'])) {
            $update_data['product_edition'] = sanitize_text_field($data['product_edition']);
        }
        if (isset($data['modules'])) {
            $update_data['modules'] = json_encode($data['modules']);
        }
        if (isset($data['training_modules'])) {
            $update_data['training_modules'] = json_encode($data['training_modules']);
        }
        if (isset($data['legal_terms_accepted'])) {
            $update_data['legal_terms_accepted'] = !empty($data['legal_terms_accepted']) ? 1 : 0;
        }
        if (isset($data['legal_privacy_accepted'])) {
            $update_data['legal_privacy_accepted'] = !empty($data['legal_privacy_accepted']) ? 1 : 0;
        }
        if (isset($data['legal_withdrawal_acknowledged'])) {
            $update_data['legal_withdrawal_acknowledged'] = !empty($data['legal_withdrawal_acknowledged']) ? 1 : 0;
        }
        if (isset($data['legal_withdrawal_waiver'])) {
            $update_data['legal_withdrawal_waiver'] = !empty($data['legal_withdrawal_waiver']) ? 1 : 0;
        }
        if (isset($data['legal_acceptance_version'])) {
            $update_data['legal_acceptance_version'] = sanitize_text_field($data['legal_acceptance_version']);
        }
        if (isset($data['legal_accepted_at'])) {
            $update_data['legal_accepted_at'] = sanitize_text_field($data['legal_accepted_at']);
        }
        if (isset($data['legal_accepted_ip'])) {
            $update_data['legal_accepted_ip'] = sanitize_text_field($data['legal_accepted_ip']);
        }
        if (isset($data['legal_accepted_user_agent'])) {
            $update_data['legal_accepted_user_agent'] = sanitize_text_field($data['legal_accepted_user_agent']);
        }
        if (isset($data['shipping_name'])) {
            $update_data['shipping_name'] = sanitize_text_field($data['shipping_name']);
        }
        if (isset($data['shipping_address_line1'])) {
            $update_data['shipping_address_line1'] = sanitize_text_field($data['shipping_address_line1']);
        }
        if (isset($data['shipping_address_line2'])) {
            $update_data['shipping_address_line2'] = sanitize_text_field($data['shipping_address_line2']);
        }
        if (isset($data['shipping_postal_code'])) {
            $update_data['shipping_postal_code'] = sanitize_text_field($data['shipping_postal_code']);
        }
        if (isset($data['shipping_city'])) {
            $update_data['shipping_city'] = sanitize_text_field($data['shipping_city']);
        }
        if (isset($data['shipping_country'])) {
            $update_data['shipping_country'] = strtoupper(sanitize_text_field($data['shipping_country']));
        }
        if (isset($data['shipping_method'])) {
            $update_data['shipping_method'] = sanitize_text_field($data['shipping_method']);
        }
        if (isset($data['shipping_cost'])) {
            $update_data['shipping_cost'] = floatval($data['shipping_cost']);
        }
        if (isset($data['tracking_number'])) {
            $update_data['tracking_number'] = sanitize_text_field($data['tracking_number']);
        }
        if (isset($data['fulfillment_status'])) {
            $update_data['fulfillment_status'] = sanitize_text_field($data['fulfillment_status']);
        }
        if (isset($data['fulfilled_at'])) {
            $update_data['fulfilled_at'] = sanitize_text_field($data['fulfilled_at']);
        }
        if (isset($data['total_amount'])) {
            $update_data['total_amount'] = floatval($data['total_amount']);
        }
        if (isset($data['currency'])) {
            $update_data['currency'] = sanitize_text_field($data['currency']);
        }
        if (isset($data['status'])) {
            $status = self::normalize_order_status($data['status']);
            if ($status !== null) {
                $update_data['status'] = $status;
            }
        }
        if (isset($data['step'])) {
            $update_data['step'] = intval($data['step']);
        }
        
        if (empty($update_data)) {
            return false;
        }
        
        $result = $wpdb->update(
            $table_orders,
            $update_data,
            array('id' => $order_id),
            null,
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Get order by ID
     */
    public static function get_order($order_id) {
        global $wpdb;
        
        $table_orders = $wpdb->prefix . 'themisdb_orders';
        
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_orders WHERE id = %d",
            $order_id
        ), ARRAY_A);
        
        if ($order) {
            // Decode JSON fields
            if ($order['modules']) {
                $order['modules'] = json_decode($order['modules'], true);
            }
            if ($order['training_modules']) {
                $order['training_modules'] = json_decode($order['training_modules'], true);
            }
        }
        
        return $order;
    }
    
    /**
     * Get order by order number
     */
    public static function get_order_by_number($order_number) {
        global $wpdb;
        
        $table_orders = $wpdb->prefix . 'themisdb_orders';
        
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_orders WHERE order_number = %s",
            $order_number
        ), ARRAY_A);
        
        if ($order) {
            // Decode JSON fields
            if ($order['modules']) {
                $order['modules'] = json_decode($order['modules'], true);
            }
            if ($order['training_modules']) {
                $order['training_modules'] = json_decode($order['training_modules'], true);
            }
        }
        
        return $order;
    }
    
    /**
     * Get orders by customer
     */
    public static function get_customer_orders($customer_id, $status = null) {
        global $wpdb;
        
        $table_orders = $wpdb->prefix . 'themisdb_orders';
        
        if ($status) {
            $orders = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_orders WHERE customer_id = %d AND status = %s ORDER BY created_at DESC",
                $customer_id,
                $status
            ), ARRAY_A);
        } else {
            $orders = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_orders WHERE customer_id = %d ORDER BY created_at DESC",
                $customer_id
            ), ARRAY_A);
        }
        
        foreach ($orders as &$order) {
            if ($order['modules']) {
                $order['modules'] = json_decode($order['modules'], true);
            }
            if ($order['training_modules']) {
                $order['training_modules'] = json_decode($order['training_modules'], true);
            }
        }
        
        return $orders;
    }
    
    /**
     * Get all orders
     */
    public static function get_all_orders($args = array()) {
        global $wpdb;
        
        $table_orders = $wpdb->prefix . 'themisdb_orders';
        
        $defaults = array(
            'status' => null,
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
        
        $query = "SELECT * FROM $table_orders WHERE $where ORDER BY {$args['orderby']} {$args['order']} LIMIT %d OFFSET %d";
        $where_values[] = $args['limit'];
        $where_values[] = $args['offset'];
        
        $orders = $wpdb->get_results($wpdb->prepare($query, $where_values), ARRAY_A);
        
        foreach ($orders as &$order) {
            if ($order['modules']) {
                $order['modules'] = json_decode($order['modules'], true);
            }
            if ($order['training_modules']) {
                $order['training_modules'] = json_decode($order['training_modules'], true);
            }
        }
        
        return $orders;
    }
    
    /**
     * Delete an order
     */
    public static function delete_order($order_id) {
        global $wpdb;
        
        $table_orders = $wpdb->prefix . 'themisdb_orders';
        $table_order_items = $wpdb->prefix . 'themisdb_order_items';

        // Delete line items first
        $wpdb->delete(
            $table_order_items,
            array('order_id' => $order_id),
            array('%d')
        );
        
        $result = $wpdb->delete(
            $table_orders,
            array('id' => $order_id),
            array('%d')
        );
        
        return $result !== false;
    }

    /**
     * Replace all order items for one order.
     */
    public static function set_order_items($order_id, $items, $currency = 'EUR') {
        global $wpdb;

        $table_order_items = $wpdb->prefix . 'themisdb_order_items';

        $wpdb->delete(
            $table_order_items,
            array('order_id' => $order_id),
            array('%d')
        );

        if (empty($items) || !is_array($items)) {
            return true;
        }

        foreach ($items as $item) {
            $quantity = isset($item['quantity']) ? max(1, intval($item['quantity'])) : 1;
            $unit_price = isset($item['unit_price']) ? floatval($item['unit_price']) : 0.00;

            $wpdb->insert(
                $table_order_items,
                array(
                    'order_id' => $order_id,
                    'item_type' => isset($item['item_type']) ? sanitize_text_field($item['item_type']) : 'product',
                    'product_id' => isset($item['product_id']) ? intval($item['product_id']) : null,
                    'sku' => isset($item['sku']) ? sanitize_text_field($item['sku']) : null,
                    'item_name' => isset($item['item_name']) ? sanitize_text_field($item['item_name']) : '',
                    'variant_data' => isset($item['variant_data']) ? wp_json_encode($item['variant_data']) : null,
                    'quantity' => $quantity,
                    'unit_price' => $unit_price,
                    'total_price' => $unit_price * $quantity,
                    'currency' => sanitize_text_field($currency),
                    'metadata' => isset($item['metadata']) ? wp_json_encode($item['metadata']) : null,
                )
            );
        }

        return true;
    }

    /**
     * Return all line items for an order.
     */
    public static function get_order_items($order_id) {
        global $wpdb;

        $table_order_items = $wpdb->prefix . 'themisdb_order_items';
        $items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_order_items WHERE order_id = %d ORDER BY id ASC",
                $order_id
            ),
            ARRAY_A
        );

        foreach ($items as &$item) {
            if (!empty($item['variant_data'])) {
                $item['variant_data'] = json_decode($item['variant_data'], true);
            }
            if (!empty($item['metadata'])) {
                $item['metadata'] = json_decode($item['metadata'], true);
            }
        }

        return $items;
    }

    /**
     * Recalculate order total from stored line items.
     */
    public static function recalculate_order_total_from_items($order_id) {
        global $wpdb;

        $table_order_items = $wpdb->prefix . 'themisdb_order_items';
        $sum = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COALESCE(SUM(total_price), 0) FROM $table_order_items WHERE order_id = %d",
                $order_id
            )
        );

        self::update_order($order_id, array('total_amount' => floatval($sum)));
        return floatval($sum);
    }

    /**
     * Upsert inventory stock by SKU.
     */
    public static function set_inventory_stock($sku, $product_name, $stock_on_hand, $product_id = null, $reorder_level = 0, $category_slug = '', $is_active = 1) {
        global $wpdb;

        $table_inventory = $wpdb->prefix . 'themisdb_inventory_stock';
        $sku = sanitize_text_field($sku);

        return self::save_inventory_item(
            array(
                'sku' => $sku,
                'product_name' => $product_name,
                'stock_on_hand' => $stock_on_hand,
                'product_id' => $product_id,
                'reorder_level' => $reorder_level,
                'category_slug' => $category_slug,
                'is_active' => $is_active,
            )
        );
    }

    /**
     * Get one inventory record by SKU.
     */
    public static function get_inventory_stock($sku) {
        global $wpdb;

        $table_inventory = $wpdb->prefix . 'themisdb_inventory_stock';
        $item = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_inventory WHERE sku = %s", sanitize_text_field($sku)),
            ARRAY_A
        );

        return $item ? self::hydrate_inventory_item($item) : null;
    }

    /**
     * Get one inventory record by ID.
     */
    public static function get_inventory_item($id) {
        global $wpdb;

        $table_inventory = $wpdb->prefix . 'themisdb_inventory_stock';
        $item = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_inventory WHERE id = %d", intval($id)),
            ARRAY_A
        );

        return $item ? self::hydrate_inventory_item($item) : null;
    }

    /**
     * Get all inventory rows, optionally filtered by category.
     */
    public static function get_inventory_items($include_inactive = true, $category_slug = '') {
        global $wpdb;

        $table_inventory = $wpdb->prefix . 'themisdb_inventory_stock';
        $query = $include_inactive
            ? "SELECT * FROM $table_inventory ORDER BY is_active DESC, product_name ASC"
            : "SELECT * FROM $table_inventory WHERE is_active = 1 ORDER BY product_name ASC";

        $items = $wpdb->get_results($query, ARRAY_A);
        $items = array_map(array(__CLASS__, 'hydrate_inventory_item'), $items);

        if ($category_slug === '') {
            return $items;
        }

        return array_values(array_filter($items, function ($item) use ($category_slug) {
            return isset($item['category_slug']) && $item['category_slug'] === $category_slug;
        }));
    }

    /**
     * Create or update one inventory row.
     */
    public static function save_inventory_item($data, $id = 0) {
        global $wpdb;

        $table_inventory = $wpdb->prefix . 'themisdb_inventory_stock';
        $inventory_id = intval($id ?: ($data['id'] ?? 0));
        $sku = sanitize_text_field($data['sku'] ?? '');

        if ($sku === '') {
            return false;
        }

        $existing = null;
        if ($inventory_id > 0) {
            $existing = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM $table_inventory WHERE id = %d", $inventory_id),
                ARRAY_A
            );
        }

        if (!$existing) {
            $existing = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM $table_inventory WHERE sku = %s", $sku),
                ARRAY_A
            );
        }

        $category_slug = self::resolve_inventory_category_slug($data, $existing);
        $metadata = self::prepare_inventory_metadata($existing['metadata'] ?? null, $category_slug);
        $payload = array(
            'product_id' => !empty($data['product_id']) ? intval($data['product_id']) : null,
            'product_name' => sanitize_text_field($data['product_name'] ?? ''),
            'stock_on_hand' => intval($data['stock_on_hand'] ?? 0),
            'reorder_level' => intval($data['reorder_level'] ?? 0),
            'is_active' => isset($data['is_active']) ? (!empty($data['is_active']) ? 1 : 0) : 1,
            'metadata' => wp_json_encode($metadata),
        );

        if ($existing) {
            $payload['sku'] = $sku;
            $result = $wpdb->update($table_inventory, $payload, array('id' => intval($existing['id'])));
            return $result !== false ? intval($existing['id']) : false;
        }

        $payload['sku'] = $sku;
        $payload['reserved_stock'] = intval($data['reserved_stock'] ?? 0);
        $result = $wpdb->insert($table_inventory, $payload);

        return $result ? intval($wpdb->insert_id) : false;
    }

    /**
     * Toggle active state for inventory.
     */
    public static function set_inventory_item_active($id, $is_active) {
        global $wpdb;

        $table_inventory = $wpdb->prefix . 'themisdb_inventory_stock';
        $result = $wpdb->update(
            $table_inventory,
            array('is_active' => $is_active ? 1 : 0),
            array('id' => intval($id)),
            array('%d'),
            array('%d')
        );

        return $result !== false;
    }

    /**
     * Soft-delete one inventory row.
     */
    public static function deactivate_inventory_item($id) {
        return self::set_inventory_item_active($id, 0);
    }

    /**
     * Register an inventory movement.
     */
    public static function add_inventory_movement($sku, $quantity_delta, $movement_type, $reason = '', $order_id = null, $created_by = null, $adjust_stock = true) {
        global $wpdb;

        $table_movements = $wpdb->prefix . 'themisdb_inventory_movements';
        $table_inventory = $wpdb->prefix . 'themisdb_inventory_stock';

        $sku = sanitize_text_field($sku);
        $quantity_delta = intval($quantity_delta);

        $wpdb->insert(
            $table_movements,
            array(
                'sku' => $sku,
                'order_id' => $order_id ? intval($order_id) : null,
                'movement_type' => sanitize_text_field($movement_type),
                'quantity_delta' => $quantity_delta,
                'reason' => sanitize_text_field($reason),
                'created_by' => $created_by ? intval($created_by) : get_current_user_id(),
            )
        );

        if ($adjust_stock) {
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE $table_inventory SET stock_on_hand = stock_on_hand + %d WHERE sku = %s",
                    $quantity_delta,
                    $sku
                )
            );
        }

        return $wpdb->insert_id;
    }

    /**
     * Reserve stock for all merchandise items in an order.
     */
    public static function reserve_inventory_for_order($order_id) {
        global $wpdb;

        $table_inventory = $wpdb->prefix . 'themisdb_inventory_stock';
        $items = self::get_order_items($order_id);

        foreach ($items as $item) {
            if (empty($item['sku']) || intval($item['quantity']) <= 0) {
                continue;
            }

            if (self::inventory_movement_exists($order_id, $item['sku'], 'reserve')) {
                continue;
            }

            $inventory = self::get_inventory_stock($item['sku']);
            if (!$inventory) {
                continue;
            }

            $available = intval($inventory['stock_on_hand']) - intval($inventory['reserved_stock']);
            if ($available < intval($item['quantity'])) {
                error_log('ThemisDB Inventory Warning: insufficient stock for SKU ' . $item['sku'] . ' on order ' . $order_id);
                continue;
            }

            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE $table_inventory SET reserved_stock = reserved_stock + %d WHERE sku = %s",
                    intval($item['quantity']),
                    $item['sku']
                )
            );

            self::add_inventory_movement(
                $item['sku'],
                intval($item['quantity']),
                'reserve',
                'Stock reservation for order',
                $order_id,
                null,
                false
            );
        }

        return true;
    }

    /**
     * Release previously reserved stock for an order.
     */
    public static function release_inventory_for_order($order_id) {
        global $wpdb;

        $table_inventory = $wpdb->prefix . 'themisdb_inventory_stock';
        $items = self::get_order_items($order_id);

        foreach ($items as $item) {
            if (empty($item['sku']) || intval($item['quantity']) <= 0) {
                continue;
            }

            if (!self::inventory_movement_exists($order_id, $item['sku'], 'reserve') || self::inventory_movement_exists($order_id, $item['sku'], 'release')) {
                continue;
            }

            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE $table_inventory SET reserved_stock = GREATEST(reserved_stock - %d, 0) WHERE sku = %s",
                    intval($item['quantity']),
                    $item['sku']
                )
            );

            self::add_inventory_movement(
                $item['sku'],
                intval($item['quantity']) * -1,
                'release',
                'Stock release for order',
                $order_id,
                null,
                false
            );
        }

        return true;
    }

    /**
     * Convert reserved stock into shipped stock for an order.
     */
    public static function fulfill_inventory_for_order($order_id) {
        global $wpdb;

        $table_inventory = $wpdb->prefix . 'themisdb_inventory_stock';
        $items = self::get_order_items($order_id);

        foreach ($items as $item) {
            if (empty($item['sku']) || intval($item['quantity']) <= 0) {
                continue;
            }

            if (self::inventory_movement_exists($order_id, $item['sku'], 'fulfill')) {
                continue;
            }

            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE $table_inventory SET reserved_stock = GREATEST(reserved_stock - %d, 0) WHERE sku = %s",
                    intval($item['quantity']),
                    $item['sku']
                )
            );

            self::add_inventory_movement(
                $item['sku'],
                intval($item['quantity']) * -1,
                'fulfill',
                'Stock shipped for order',
                $order_id,
                null,
                true
            );
        }

        return true;
    }

    /**
     * Check whether a movement already exists for an order/SKU/type.
     */
    private static function inventory_movement_exists($order_id, $sku, $movement_type) {
        global $wpdb;

        $table_movements = $wpdb->prefix . 'themisdb_inventory_movements';
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_movements WHERE order_id = %d AND sku = %s AND movement_type = %s",
                $order_id,
                sanitize_text_field($sku),
                sanitize_text_field($movement_type)
            )
        );

        return intval($count) > 0;
    }
    
    /**
     * Calculate order total
     */
    public static function calculate_total($product_edition, $modules = array(), $training_modules = array()) {
        global $wpdb;
        
        $total = 0.00;
        
        // Get product price
        $table_products = $wpdb->prefix . 'themisdb_products';
        $product = $wpdb->get_row($wpdb->prepare(
            "SELECT price FROM $table_products WHERE edition = %s AND is_active = 1",
            $product_edition
        ));
        
        if ($product) {
            $total += floatval($product->price);
        }
        
        // Get modules prices
        if (!empty($modules)) {
            $table_modules = $wpdb->prefix . 'themisdb_modules';
            $placeholders = implode(',', array_fill(0, count($modules), '%s'));
            $query = "SELECT SUM(price) as total FROM $table_modules WHERE module_code IN ($placeholders) AND is_active = 1";
            $module_total = $wpdb->get_var($wpdb->prepare($query, $modules));
            if ($module_total) {
                $total += floatval($module_total);
            }
        }
        
        // Get training prices
        if (!empty($training_modules)) {
            $table_training = $wpdb->prefix . 'themisdb_training_modules';
            $placeholders = implode(',', array_fill(0, count($training_modules), '%s'));
            $query = "SELECT SUM(price) as total FROM $table_training WHERE training_code IN ($placeholders) AND is_active = 1";
            $training_total = $wpdb->get_var($wpdb->prepare($query, $training_modules));
            if ($training_total) {
                $total += floatval($training_total);
            }
        }
        
        return $total;
    }
    
    /**
     * Generate order number
     */
    private static function generate_order_number() {
        $prefix = 'TDB';
        $date = date('Ymd');
        $random = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
        
        return $prefix . '-' . $date . '-' . $random;
    }
    
    /**
     * Get products
     */
    public static function get_products($include_inactive = false) {
        global $wpdb;
        
        $table_products = $wpdb->prefix . 'themisdb_products';

        if ($include_inactive) {
            return $wpdb->get_results(
                "SELECT * FROM $table_products ORDER BY is_active DESC, price ASC",
                ARRAY_A
            );
        }
        
        return $wpdb->get_results(
            "SELECT * FROM $table_products WHERE is_active = 1 ORDER BY price ASC",
            ARRAY_A
        );
    }
    
    /**
     * Get modules
     */
    public static function get_modules($category = null, $include_inactive = false) {
        global $wpdb;
        
        $table_modules = $wpdb->prefix . 'themisdb_modules';
        
        if ($category) {
            if ($include_inactive) {
                return $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM $table_modules WHERE module_category = %s ORDER BY is_active DESC, module_name ASC",
                    $category
                ), ARRAY_A);
            }

            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_modules WHERE is_active = 1 AND module_category = %s ORDER BY module_name ASC",
                $category
            ), ARRAY_A);
        }

        if ($include_inactive) {
            return $wpdb->get_results(
                "SELECT * FROM $table_modules ORDER BY is_active DESC, module_category, module_name ASC",
                ARRAY_A
            );
        }
        
        return $wpdb->get_results(
            "SELECT * FROM $table_modules WHERE is_active = 1 ORDER BY module_category, module_name ASC",
            ARRAY_A
        );
    }
    
    /**
     * Get training modules
     */
    public static function get_training_modules($type = null, $include_inactive = false) {
        global $wpdb;
        
        $table_training = $wpdb->prefix . 'themisdb_training_modules';
        
        if ($type) {
            if ($include_inactive) {
                return $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM $table_training WHERE training_type = %s ORDER BY is_active DESC, training_name ASC",
                    $type
                ), ARRAY_A);
            }

            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_training WHERE is_active = 1 AND training_type = %s ORDER BY training_name ASC",
                $type
            ), ARRAY_A);
        }

        if ($include_inactive) {
            return $wpdb->get_results(
                "SELECT * FROM $table_training ORDER BY is_active DESC, training_type, training_name ASC",
                ARRAY_A
            );
        }
        
        return $wpdb->get_results(
            "SELECT * FROM $table_training WHERE is_active = 1 ORDER BY training_type, training_name ASC",
            ARRAY_A
        );
    }

    /**
     * Get one product by ID (optionally including inactive entries).
     */
    public static function get_product($id, $include_inactive = true) {
        global $wpdb;

        $table_products = $wpdb->prefix . 'themisdb_products';
        $query = "SELECT * FROM $table_products WHERE id = %d";

        if (!$include_inactive) {
            $query .= " AND is_active = 1";
        }

        return $wpdb->get_row($wpdb->prepare($query, intval($id)), ARRAY_A);
    }

    /**
     * Get one module by ID (optionally including inactive entries).
     */
    public static function get_module($id, $include_inactive = true) {
        global $wpdb;

        $table_modules = $wpdb->prefix . 'themisdb_modules';
        $query = "SELECT * FROM $table_modules WHERE id = %d";

        if (!$include_inactive) {
            $query .= " AND is_active = 1";
        }

        return $wpdb->get_row($wpdb->prepare($query, intval($id)), ARRAY_A);
    }

    /**
     * Get one training module by ID (optionally including inactive entries).
     */
    public static function get_training_module($id, $include_inactive = true) {
        global $wpdb;

        $table_training = $wpdb->prefix . 'themisdb_training_modules';
        $query = "SELECT * FROM $table_training WHERE id = %d";

        if (!$include_inactive) {
            $query .= " AND is_active = 1";
        }

        return $wpdb->get_row($wpdb->prepare($query, intval($id)), ARRAY_A);
    }

    /**
     * Save (create/update) one product row.
     */
    public static function save_product($data, $id = 0) {
        global $wpdb;

        $table_products = $wpdb->prefix . 'themisdb_products';
        $payload = array(
            'product_code' => sanitize_text_field($data['product_code'] ?? ''),
            'product_name' => sanitize_text_field($data['product_name'] ?? ''),
            'product_type' => sanitize_text_field($data['product_type'] ?? 'database'),
            'edition' => sanitize_text_field($data['edition'] ?? ''),
            'description' => isset($data['description']) ? sanitize_textarea_field($data['description']) : null,
            'price' => floatval($data['price'] ?? 0),
            'currency' => sanitize_text_field($data['currency'] ?? 'EUR'),
            'is_active' => !empty($data['is_active']) ? 1 : 0,
        );

        if (intval($id) > 0) {
            $result = $wpdb->update($table_products, $payload, array('id' => intval($id)));
            return $result !== false;
        }

        $result = $wpdb->insert($table_products, $payload);
        return $result ? intval($wpdb->insert_id) : false;
    }

    /**
     * Save (create/update) one module row.
     */
    public static function save_module($data, $id = 0) {
        global $wpdb;

        $table_modules = $wpdb->prefix . 'themisdb_modules';
        $payload = array(
            'module_code' => sanitize_text_field($data['module_code'] ?? ''),
            'module_name' => sanitize_text_field($data['module_name'] ?? ''),
            'module_category' => sanitize_text_field($data['module_category'] ?? 'general'),
            'description' => isset($data['description']) ? sanitize_textarea_field($data['description']) : null,
            'price' => floatval($data['price'] ?? 0),
            'currency' => sanitize_text_field($data['currency'] ?? 'EUR'),
            'is_active' => !empty($data['is_active']) ? 1 : 0,
        );

        if (intval($id) > 0) {
            $result = $wpdb->update($table_modules, $payload, array('id' => intval($id)));
            return $result !== false;
        }

        $result = $wpdb->insert($table_modules, $payload);
        return $result ? intval($wpdb->insert_id) : false;
    }

    /**
     * Save (create/update) one training row.
     */
    public static function save_training_module($data, $id = 0) {
        global $wpdb;

        $table_training = $wpdb->prefix . 'themisdb_training_modules';
        $payload = array(
            'training_code' => sanitize_text_field($data['training_code'] ?? ''),
            'training_name' => sanitize_text_field($data['training_name'] ?? ''),
            'training_type' => sanitize_text_field($data['training_type'] ?? 'online'),
            'duration_hours' => isset($data['duration_hours']) ? intval($data['duration_hours']) : null,
            'description' => isset($data['description']) ? sanitize_textarea_field($data['description']) : null,
            'price' => floatval($data['price'] ?? 0),
            'currency' => sanitize_text_field($data['currency'] ?? 'EUR'),
            'is_active' => !empty($data['is_active']) ? 1 : 0,
        );

        if (intval($id) > 0) {
            $result = $wpdb->update($table_training, $payload, array('id' => intval($id)));
            return $result !== false;
        }

        $result = $wpdb->insert($table_training, $payload);
        return $result ? intval($wpdb->insert_id) : false;
    }

    /**
     * Soft-delete (deactivate) one catalog row by table kind.
     */
    public static function deactivate_catalog_item($entity, $id) {
        global $wpdb;

        $map = array(
            'product' => $wpdb->prefix . 'themisdb_products',
            'module' => $wpdb->prefix . 'themisdb_modules',
            'training' => $wpdb->prefix . 'themisdb_training_modules',
        );

        if (!isset($map[$entity])) {
            return false;
        }

        $result = $wpdb->update(
            $map[$entity],
            array('is_active' => 0),
            array('id' => intval($id)),
            array('%d'),
            array('%d')
        );

        return $result !== false;
    }

    /**
     * Get all persisted catalog categories.
     */
    public static function get_catalog_categories($include_inactive = true) {
        self::sync_catalog_categories_from_data();

        $categories = self::load_catalog_categories_option();

        if (!$include_inactive) {
            $categories = array_values(array_filter($categories, function ($category) {
                return !empty($category['is_active']);
            }));
        }

        usort($categories, function ($left, $right) {
            $left_order = intval($left['sort_order'] ?? 0);
            $right_order = intval($right['sort_order'] ?? 0);

            if ($left_order === $right_order) {
                return strcasecmp($left['name'] ?? '', $right['name'] ?? '');
            }

            return $left_order <=> $right_order;
        });

        return $categories;
    }

    /**
     * Get one catalog category by ID.
     */
    public static function get_catalog_category($id) {
        foreach (self::load_catalog_categories_option() as $category) {
            if (intval($category['id']) === intval($id)) {
                return $category;
            }
        }

        return null;
    }

    /**
     * Create or update one catalog category.
     */
    public static function save_catalog_category($data, $id = 0) {
        $categories = self::load_catalog_categories_option();
        $category_id = intval($id ?: ($data['id'] ?? 0));
        $name = sanitize_text_field($data['name'] ?? '');
        $slug = sanitize_title($data['slug'] ?? $name);

        if ($name === '' || $slug === '') {
            return false;
        }

        foreach ($categories as $existing) {
            if (intval($existing['id']) !== $category_id && ($existing['slug'] ?? '') === $slug) {
                return false;
            }
        }

        $payload = array(
            'id' => $category_id > 0 ? $category_id : self::get_next_catalog_category_id($categories),
            'slug' => $slug,
            'name' => $name,
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'sort_order' => intval($data['sort_order'] ?? 0),
            'is_active' => isset($data['is_active']) ? (!empty($data['is_active']) ? 1 : 0) : 1,
        );

        $updated = false;
        foreach ($categories as $index => $existing) {
            if (intval($existing['id']) === intval($payload['id'])) {
                $categories[$index] = $payload;
                $updated = true;
                break;
            }
        }

        if (!$updated) {
            $categories[] = $payload;
        }

        return update_option('themisdb_catalog_categories', array_values($categories), false) ? $payload['id'] : $payload['id'];
    }

    /**
     * Toggle one catalog category.
     */
    public static function set_catalog_category_active($id, $is_active) {
        $categories = self::load_catalog_categories_option();
        $updated = false;

        foreach ($categories as $index => $category) {
            if (intval($category['id']) === intval($id)) {
                $categories[$index]['is_active'] = $is_active ? 1 : 0;
                $updated = true;
                break;
            }
        }

        if (!$updated) {
            return false;
        }

        return update_option('themisdb_catalog_categories', array_values($categories), false);
    }

    /**
     * Delete one catalog category definition.
     */
    public static function delete_catalog_category($id) {
        $categories = array_values(array_filter(self::load_catalog_categories_option(), function ($category) use ($id) {
            return intval($category['id']) !== intval($id);
        }));

        return update_option('themisdb_catalog_categories', $categories, false);
    }

    /**
     * Ensure category definitions exist for current catalog data.
     */
    public static function sync_catalog_categories_from_data() {
        $categories = self::load_catalog_categories_option();
        $known_slugs = array();

        foreach ($categories as $category) {
            $known_slugs[$category['slug']] = true;
        }

        $raw_slugs = array();
        foreach (self::get_products(true) as $product) {
            if (!empty($product['product_type'])) {
                $raw_slugs[] = $product['product_type'];
            }
        }

        foreach (self::get_modules(null, true) as $module) {
            if (!empty($module['module_category'])) {
                $raw_slugs[] = $module['module_category'];
            }
        }

        foreach (self::get_training_modules(null, true) as $training) {
            if (!empty($training['training_type'])) {
                $raw_slugs[] = $training['training_type'];
            }
        }

        foreach (self::get_inventory_items(true) as $inventory_item) {
            if (!empty($inventory_item['category_slug'])) {
                $raw_slugs[] = $inventory_item['category_slug'];
            }
        }

        $next_id = self::get_next_catalog_category_id($categories);
        $changed = false;

        foreach (array_unique(array_filter(array_map('sanitize_title', $raw_slugs))) as $slug) {
            if (isset($known_slugs[$slug])) {
                continue;
            }

            $categories[] = array(
                'id' => $next_id++,
                'slug' => $slug,
                'name' => ucwords(str_replace(array('-', '_'), ' ', $slug)),
                'description' => '',
                'sort_order' => 0,
                'is_active' => 1,
            );
            $changed = true;
        }

        if ($changed) {
            update_option('themisdb_catalog_categories', array_values($categories), false);
        }
    }

    /**
     * Hydrate one inventory row with metadata-derived category information.
     */
    private static function hydrate_inventory_item($item) {
        if (!is_array($item)) {
            return $item;
        }

        $metadata = array();
        if (!empty($item['metadata'])) {
            $decoded = json_decode($item['metadata'], true);
            if (is_array($decoded)) {
                $metadata = $decoded;
            }
        }

        $item['metadata_array'] = $metadata;
        $item['category_slug'] = sanitize_title($metadata['category_slug'] ?? '');

        if ($item['category_slug'] === '' && !empty($item['product_id'])) {
            $product = self::get_product($item['product_id'], true);
            if ($product && !empty($product['product_type'])) {
                $item['category_slug'] = sanitize_title($product['product_type']);
            }
        }

        return $item;
    }

    /**
     * Prepare inventory metadata payload.
     */
    private static function prepare_inventory_metadata($existing_metadata, $category_slug) {
        $metadata = array();

        if (!empty($existing_metadata)) {
            $decoded = json_decode($existing_metadata, true);
            if (is_array($decoded)) {
                $metadata = $decoded;
            }
        }

        $metadata['category_slug'] = sanitize_title($category_slug);

        return $metadata;
    }

    /**
     * Resolve the inventory category to persist.
     */
    private static function resolve_inventory_category_slug($data, $existing = null) {
        if (!empty($data['category_slug'])) {
            return sanitize_title($data['category_slug']);
        }

        if (!empty($data['product_id'])) {
            $product = self::get_product($data['product_id'], true);
            if ($product && !empty($product['product_type'])) {
                return sanitize_title($product['product_type']);
            }
        }

        if (!empty($existing['metadata'])) {
            $decoded = json_decode($existing['metadata'], true);
            if (is_array($decoded) && !empty($decoded['category_slug'])) {
                return sanitize_title($decoded['category_slug']);
            }
        }

        return 'uncategorized';
    }

    /**
     * Load category definitions from the WordPress option.
     */
    private static function load_catalog_categories_option() {
        $categories = get_option('themisdb_catalog_categories', array());

        if (!is_array($categories)) {
            return array();
        }

        return array_values(array_filter(array_map(function ($category) {
            if (!is_array($category) || empty($category['slug']) || empty($category['name'])) {
                return null;
            }

            return array(
                'id' => intval($category['id'] ?? 0),
                'slug' => sanitize_title($category['slug']),
                'name' => sanitize_text_field($category['name']),
                'description' => sanitize_textarea_field($category['description'] ?? ''),
                'sort_order' => intval($category['sort_order'] ?? 0),
                'is_active' => !empty($category['is_active']) ? 1 : 0,
            );
        }, $categories)));
    }

    /**
     * Compute the next category ID.
     */
    private static function get_next_catalog_category_id($categories) {
        $max_id = 0;

        foreach ($categories as $category) {
            $max_id = max($max_id, intval($category['id'] ?? 0));
        }

        return $max_id + 1;
    }

    /**
     * Toggle active state for one catalog row by table kind.
     */
    public static function set_catalog_item_active($entity, $id, $is_active) {
        global $wpdb;

        $map = array(
            'product' => $wpdb->prefix . 'themisdb_products',
            'module' => $wpdb->prefix . 'themisdb_modules',
            'training' => $wpdb->prefix . 'themisdb_training_modules',
        );

        if (!isset($map[$entity])) {
            return false;
        }

        $result = $wpdb->update(
            $map[$entity],
            array('is_active' => $is_active ? 1 : 0),
            array('id' => intval($id)),
            array('%d'),
            array('%d')
        );

        return $result !== false;
    }
}
