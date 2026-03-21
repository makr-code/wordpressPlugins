<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB - Hybrid Database System                                   ║
╠═════════════════════════════════════════════════════════════════════╣
  File:            class-woocommerce-bridge.php                       ║
    Version:         0.4.0                                              ║
  Last Modified:   2026-03-21                                         ║
  Author:          ThemisDB Team                                      ║
╠═════════════════════════════════════════════════════════════════════╣
    Status: 🚧 Phase 2.1 In Progress                                    ║
╚═════════════════════════════════════════════════════════════════════╝
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WooCommerce bridge scaffold for ThemisDB order synchronization.
 */
class ThemisDB_WooCommerce_Bridge {

    /**
     * Wire WordPress/WooCommerce hooks.
     */
    public function __construct() {
        if (!$this->is_available()) {
            return;
        }

        add_action('woocommerce_order_status_completed', array($this, 'on_order_completed'), 10, 1);
        add_action('woocommerce_payment_complete', array($this, 'on_payment_complete'), 10, 1);
        add_action('woocommerce_order_status_changed', array($this, 'on_order_status_changed'), 10, 4);
        add_action('woocommerce_order_refunded', array($this, 'on_order_refunded'), 10, 2);
        add_action('woocommerce_thankyou', array($this, 'maybe_generate_license'), 20, 1);
        add_action('themisdb_woocommerce_license_generation_requested', array($this, 'handle_license_generation_requested'), 10, 2);

        // Product catalog sync.
        add_action('woocommerce_new_product',    array($this, 'on_product_created'),   20, 1);
        add_action('woocommerce_update_product', array($this, 'on_product_updated'),   20, 1);
        add_action('woocommerce_trash_product',  array($this, 'on_product_trashed'),   10, 1);
        add_action('woocommerce_untrash_product', array($this, 'on_product_untrashed'), 10, 1);
        // woocommerce_product_set_stock fires when stock quantity is saved; keeps
        // the ThemisDB metadata (price, is_active) in sync even for stock-only edits.
        add_action('woocommerce_product_set_stock', array($this, 'on_product_stock_changed'), 20, 1);
    }

    /**
     * Check if WooCommerce APIs are available.
     *
     * @return bool
     */
    private function is_available() {
        return class_exists('WooCommerce') && function_exists('wc_get_order');
    }

    /**
     * Sync order on completed status.
     *
     * @param int $woo_order_id
     */
    public function on_order_completed($woo_order_id) {
        $this->sync_order($woo_order_id, 'completed', 'confirmed');
    }

    /**
     * Sync order after payment complete callback.
     *
     * @param int $woo_order_id
     */
    public function on_payment_complete($woo_order_id) {
        $this->sync_order($woo_order_id, 'payment_complete', 'confirmed');
    }

    /**
     * Map WooCommerce lifecycle changes into ThemisDB order/license lifecycle.
     *
     * @param int      $woo_order_id
     * @param string   $from_status
     * @param string   $to_status
     * @param WC_Order $woo_order
     */
    public function on_order_status_changed($woo_order_id, $from_status, $to_status, $woo_order) {
        $this->apply_status_sync($woo_order_id, $to_status, 'status_changed');
    }

    /**
     * Handle WooCommerce refund events with full vs partial distinction.
     *
     * @param int $woo_order_id
     * @param int $refund_id
     */
    public function on_order_refunded($woo_order_id, $refund_id) {
        $woo_order = wc_get_order(intval($woo_order_id));
        if (!$woo_order) {
            return;
        }

        $fully_refunded = floatval($woo_order->get_total_refunded()) >= floatval($woo_order->get_total());
        $status = $fully_refunded ? 'refunded' : 'partially-refunded';
        $this->apply_status_sync($woo_order_id, $status, 'refund_event');
    }

    /**
     * Create or update a ThemisDB order mapped from WooCommerce.
     *
     * @param int    $woo_order_id
     * @param string $trigger
     * @return int|false
     */
    private function sync_order($woo_order_id, $trigger, $target_status = 'confirmed') {
        if (!class_exists('ThemisDB_Order_Manager')) {
            return false;
        }

        $woo_order = wc_get_order(intval($woo_order_id));
        if (!$woo_order) {
            return false;
        }

        $existing_themis_order_id = intval(get_post_meta($woo_order->get_id(), '_themisdb_order_id', true));
        if ($existing_themis_order_id > 0) {
            // Keep mapped orders aligned with the paid/confirmed state.
            ThemisDB_Order_Manager::set_order_status($existing_themis_order_id, $target_status);
            return $existing_themis_order_id;
        }

        $mapping = $this->map_woo_order_to_themisdb($woo_order);
        $mapped_data = $mapping['order_data'];
        $mapped_data['status'] = $target_status;

        $themis_order_id = ThemisDB_Order_Manager::create_order($mapped_data);
        if (!$themis_order_id) {
            error_log('ThemisDB Woo Bridge: Failed to create ThemisDB order for Woo order ' . intval($woo_order_id));
            if (class_exists('ThemisDB_Error_Handler')) {
                ThemisDB_Error_Handler::log('error', 'Woo sync failed: order creation failed', array(
                    'woo_order_id' => intval($woo_order_id),
                    'trigger' => sanitize_text_field($trigger),
                ));
            }
            return false;
        }

        if (!empty($mapping['line_items']) && method_exists('ThemisDB_Order_Manager', 'set_order_items')) {
            ThemisDB_Order_Manager::set_order_items($themis_order_id, $mapping['line_items'], $mapped_data['currency']);
        }

        update_post_meta($woo_order->get_id(), '_themisdb_order_id', $themis_order_id);
        update_post_meta($woo_order->get_id(), '_themisdb_sync_trigger', sanitize_text_field($trigger));

        $woo_order->add_order_note(sprintf(
            'ThemisDB order synced (ID %d) via trigger "%s".',
            intval($themis_order_id),
            sanitize_text_field($trigger)
        ));

        do_action('themisdb_woocommerce_order_synced', $themis_order_id, $woo_order->get_id(), $woo_order, $mapped_data);

        return $themis_order_id;
    }

    /**
     * Apply one Woo status change to ThemisDB order and license lifecycle.
     *
     * @param int    $woo_order_id
     * @param string $woo_status
     * @param string $trigger
     */
    private function apply_status_sync($woo_order_id, $woo_status, $trigger) {
        if (!class_exists('ThemisDB_Order_Manager')) {
            return;
        }

        $woo_order_id = intval($woo_order_id);
        if ($woo_order_id <= 0) {
            return;
        }

        $woo_order = wc_get_order($woo_order_id);
        if (!$woo_order) {
            return;
        }

        $woo_status = sanitize_key((string) $woo_status);
        $target_themis_status = $this->map_woo_status_to_themis_status($woo_status, $woo_order);
        if ($target_themis_status === null) {
            return;
        }

        $themis_order_id = intval(get_post_meta($woo_order_id, '_themisdb_order_id', true));

        if ($themis_order_id <= 0 && in_array($woo_status, array('processing', 'completed'), true)) {
            $themis_order_id = $this->sync_order($woo_order_id, $trigger . ':' . $woo_status, $target_themis_status);
        } elseif ($themis_order_id > 0) {
            ThemisDB_Order_Manager::set_order_status($themis_order_id, $target_themis_status);
        }

        if ($themis_order_id > 0 && $woo_status === 'completed') {
            do_action('themisdb_woocommerce_license_generation_requested', $themis_order_id, $woo_order_id);
        }

        $this->sync_existing_license_status_from_woo($woo_order_id, $woo_status, $woo_order);
    }

    /**
     * Convert Woo order status into ThemisDB order status.
     *
     * @param string $woo_status
     * @return string|null
     */
    private function map_woo_status_to_themis_status($woo_status, $woo_order = null) {
        $map = array(
            'pending' => 'pending',
            'on-hold' => 'pending',
            'processing' => 'confirmed',
            'completed' => 'confirmed',
            'cancelled' => 'cancelled',
            'failed' => 'failed',
            'refunded' => 'ended',
            'partially-refunded' => 'confirmed',
        );

        if ($woo_order instanceof WC_Order) {
            $gateway_overrides = $this->get_gateway_status_overrides($woo_order);
            if (isset($gateway_overrides[$woo_status])) {
                $map[$woo_status] = $gateway_overrides[$woo_status];
            }
        }

        $target = isset($map[$woo_status]) ? $map[$woo_status] : null;

        return apply_filters(
            'themisdb_woo_status_mapping',
            $target,
            $woo_status,
            $woo_order
        );
    }

    /**
     * Return gateway-specific ThemisDB status overrides.
     *
     * @param WC_Order $woo_order
     * @return array
     */
    private function get_gateway_status_overrides($woo_order) {
        $payment_method = sanitize_key((string) $woo_order->get_payment_method());
        $default = array();
        $overrides = array(
            'stripe' => array(
                'on-hold' => 'pending',
                'partially-refunded' => 'confirmed',
            ),
            'paypal' => array(
                'on-hold' => 'pending',
                'partially-refunded' => 'confirmed',
            ),
            'bacs' => array(
                'on-hold' => 'pending',
                'processing' => 'confirmed',
            ),
            // Cash on delivery: delivery = payment; confirmed immediately on processing.
            'cod' => array(
                'processing' => 'confirmed',
                'on-hold'    => 'pending',
            ),
            // Cheque: payment arrives later; stay pending until bank confirms.
            'cheque' => array(
                'processing' => 'pending',
                'on-hold'    => 'pending',
                'completed'  => 'confirmed',
            ),
        );

        $selected = isset($overrides[$payment_method]) ? $overrides[$payment_method] : $default;

        return apply_filters(
            'themisdb_woo_gateway_status_overrides',
            $selected,
            $payment_method,
            $woo_order
        );
    }

    /**
     * Apply Woo status changes to an already linked ThemisDB license.
     *
     * @param int    $woo_order_id
     * @param string $woo_status
     */
    private function sync_existing_license_status_from_woo($woo_order_id, $woo_status, $woo_order = null) {
        if (!class_exists('ThemisDB_License_Manager')) {
            return;
        }

        $license_id = intval(get_post_meta($woo_order_id, '_themisdb_license_id', true));
        if ($license_id <= 0) {
            return;
        }

        $license = ThemisDB_License_Manager::get_license($license_id);
        if (!$license) {
            return;
        }

        $status = isset($license['license_status']) ? $license['license_status'] : 'pending';
        $license_action = 'none';

        if (in_array($woo_status, array('completed', 'processing'), true)) {
            $license_action = 'activate';
        } elseif (in_array($woo_status, array('on-hold', 'pending', 'failed'), true)) {
            $license_action = 'suspend';
        } elseif (in_array($woo_status, array('cancelled', 'refunded'), true)) {
            $license_action = 'cancel';
        }

        $license_action = apply_filters(
            'themisdb_woo_license_sync_action',
            $license_action,
            $woo_status,
            $license,
            $woo_order
        );

        if ($license_action === 'activate') {
            if ($status !== 'active' && $status !== 'cancelled') {
                ThemisDB_License_Manager::activate_license($license_id);
            }
            return;
        }

        if ($license_action === 'suspend') {
            if ($status === 'active') {
                ThemisDB_License_Manager::suspend_license($license_id, 'WooCommerce status changed to ' . $woo_status);
            }
            return;
        }

        if ($license_action === 'cancel') {
            if ($status !== 'cancelled') {
                ThemisDB_License_Manager::cancel_license($license_id, 'WooCommerce status changed to ' . $woo_status, 0);
            }
        }
    }

    /**
     * Hook point for future automated license generation.
     *
     * @param int $woo_order_id
     */
    public function maybe_generate_license($woo_order_id) {
        $woo_order_id = intval($woo_order_id);
        if ($woo_order_id <= 0) {
            return;
        }

        $themis_order_id = intval(get_post_meta($woo_order_id, '_themisdb_order_id', true));
        if ($themis_order_id <= 0) {
            return;
        }

        do_action('themisdb_woocommerce_license_generation_requested', $themis_order_id, $woo_order_id);
    }

    /**
     * Create and activate a license for a synced WooCommerce order.
     *
     * @param int $themis_order_id
     * @param int $woo_order_id
     */
    public function handle_license_generation_requested($themis_order_id, $woo_order_id) {
        if (!class_exists('ThemisDB_Order_Manager') || !class_exists('ThemisDB_Contract_Manager') || !class_exists('ThemisDB_License_Manager')) {
            return;
        }

        $themis_order_id = intval($themis_order_id);
        $woo_order_id = intval($woo_order_id);
        if ($themis_order_id <= 0 || $woo_order_id <= 0) {
            return;
        }

        $existing_license_id = intval(get_post_meta($woo_order_id, '_themisdb_license_id', true));
        if ($existing_license_id > 0) {
            $existing_license = ThemisDB_License_Manager::get_license($existing_license_id);
            if ($existing_license && $existing_license['license_status'] !== 'active') {
                ThemisDB_License_Manager::activate_license($existing_license_id);
            }
            return;
        }

        $order = ThemisDB_Order_Manager::get_order($themis_order_id);
        if (!$order) {
            return;
        }

        $contract_result = ThemisDB_Contract_Manager::ensure_contract_for_order($themis_order_id, array(
            'generate_pdf' => false,
            'send_email' => false,
            'order_status_after' => 'confirmed',
        ));

        if (empty($contract_result['success']) || empty($contract_result['contract_id'])) {
            error_log('ThemisDB Woo Bridge: Could not ensure contract for order ' . $themis_order_id);
            if (class_exists('ThemisDB_Error_Handler')) {
                ThemisDB_Error_Handler::log('error', 'Woo license generation failed: contract ensure failed', array(
                    'themis_order_id' => intval($themis_order_id),
                    'woo_order_id' => intval($woo_order_id),
                ));
            }
            return;
        }

        $contract_id = intval($contract_result['contract_id']);
        $license = ThemisDB_License_Manager::get_license_by_contract($contract_id);

        if (!$license) {
            $license_id = ThemisDB_License_Manager::create_license(array(
                'order_id' => $themis_order_id,
                'contract_id' => $contract_id,
                'customer_id' => !empty($order['customer_id']) ? intval($order['customer_id']) : 0,
                'product_edition' => !empty($order['product_edition']) ? $order['product_edition'] : 'community',
                'license_type' => 'standard',
            ));

            if (!$license_id) {
                error_log('ThemisDB Woo Bridge: Could not create license for order ' . $themis_order_id . ' / contract ' . $contract_id);
                if (class_exists('ThemisDB_Error_Handler')) {
                    ThemisDB_Error_Handler::log('error', 'Woo license generation failed: license creation failed', array(
                        'themis_order_id' => intval($themis_order_id),
                        'woo_order_id' => intval($woo_order_id),
                        'contract_id' => intval($contract_id),
                    ));
                }
                return;
            }

            $license = ThemisDB_License_Manager::get_license($license_id);
        }

        if (!$license) {
            return;
        }

        $license_id = intval($license['id']);
        $should_send_license_email = false;

        if ($license['license_status'] !== 'active') {
            $activated = ThemisDB_License_Manager::activate_license($license_id);
            if (!$activated) {
                error_log('ThemisDB Woo Bridge: Could not activate license ' . $license_id);
                if (class_exists('ThemisDB_Error_Handler')) {
                    ThemisDB_Error_Handler::log('error', 'Woo license generation failed: license activation failed', array(
                        'license_id' => intval($license_id),
                        'themis_order_id' => intval($themis_order_id),
                        'woo_order_id' => intval($woo_order_id),
                    ));
                }
                return;
            }

            $should_send_license_email = true;
        }

        update_post_meta($woo_order_id, '_themisdb_license_id', $license_id);

        $woo_order = wc_get_order($woo_order_id);
        if ($woo_order) {
            $woo_order->add_order_note(sprintf(
                'ThemisDB license ready (ID %d) for synced order %d.',
                $license_id,
                $themis_order_id
            ));
        }

        try {
            if ($should_send_license_email && class_exists('ThemisDB_Email_Handler')) {
                ThemisDB_Email_Handler::send_license_email($license_id);
            }
        } catch (Throwable $e) {
            error_log('ThemisDB Woo Bridge: License email failed for license ' . $license_id . '. ' . $e->getMessage());
            if (class_exists('ThemisDB_Error_Handler')) {
                ThemisDB_Error_Handler::log('warning', 'Woo license email failed', array(
                    'license_id' => intval($license_id),
                    'woo_order_id' => intval($woo_order_id),
                    'exception' => $e->getMessage(),
                ));
            }
        }
    }

    /**
     * Map WooCommerce order fields to ThemisDB order payload.
     *
     * @param WC_Order $woo_order
     * @return array
     */
    private function map_woo_order_to_themisdb($woo_order) {
        $customer_name = trim($woo_order->get_billing_first_name() . ' ' . $woo_order->get_billing_last_name());
        if ($customer_name === '') {
            $customer_name = trim((string) $woo_order->get_formatted_billing_full_name());
        }
        if ($customer_name === '') {
            $customer_name = (string) $woo_order->get_billing_company();
        }
        if ($customer_name === '') {
            $customer_name = 'WooCommerce Customer';
        }

        $line_item_map = $this->analyze_line_items($woo_order);
        $product_edition = $line_item_map['product_edition'];

        $order_data = array(
            'customer_id' => intval($woo_order->get_customer_id()),
            'customer_email' => (string) $woo_order->get_billing_email(),
            'customer_name' => $customer_name,
            'customer_company' => (string) $woo_order->get_billing_company(),
            'customer_type' => 'business',
            'vat_id' => (string) $woo_order->get_meta('_billing_vat', true),
            'billing_name' => $customer_name,
            'billing_address_line1' => (string) $woo_order->get_billing_address_1(),
            'billing_address_line2' => (string) $woo_order->get_billing_address_2(),
            'billing_postal_code' => (string) $woo_order->get_billing_postcode(),
            'billing_city' => (string) $woo_order->get_billing_city(),
            'billing_country' => (string) $woo_order->get_billing_country(),
            'shipping_name' => trim((string) $woo_order->get_shipping_first_name() . ' ' . (string) $woo_order->get_shipping_last_name()),
            'shipping_address_line1' => (string) $woo_order->get_shipping_address_1(),
            'shipping_address_line2' => (string) $woo_order->get_shipping_address_2(),
            'shipping_postal_code' => (string) $woo_order->get_shipping_postcode(),
            'shipping_city' => (string) $woo_order->get_shipping_city(),
            'shipping_country' => (string) $woo_order->get_shipping_country(),
            'shipping_method' => (string) $woo_order->get_shipping_method(),
            'shipping_cost' => floatval($woo_order->get_shipping_total()),
            'product_type' => $line_item_map['product_type'],
            'product_edition' => $product_edition,
            'modules' => $line_item_map['modules'],
            'training_modules' => $line_item_map['training_modules'],
            // Orders imported from Woo checkout are implicitly consented there.
            'legal_terms_accepted' => 1,
            'legal_privacy_accepted' => 1,
            'legal_withdrawal_acknowledged' => 1,
            'legal_withdrawal_waiver' => 0,
            'legal_acceptance_version' => 'woo-import-v1',
            'legal_accepted_at' => current_time('mysql'),
            'total_amount' => floatval($woo_order->get_total()),
            'currency' => (string) $woo_order->get_currency(),
            'status' => 'confirmed',
        );

        return array(
            'order_data' => $order_data,
            'line_items' => $line_item_map['order_items'],
        );
    }

    /**
     * Analyze WooCommerce line items and derive ThemisDB mapping values.
     *
     * @param WC_Order $woo_order
     * @return array
     */
    private function analyze_line_items($woo_order) {
        $modules = array();
        $training_modules = array();
        $order_items = array();
        $derived_product_type = '';
        $derived_product_edition = '';

        foreach ($woo_order->get_items('line_item') as $item) {
            $product = $item->get_product();
            $product_name = (string) $item->get_name();
            $sku = $product ? (string) $product->get_sku() : '';
            $signal = strtolower($product_name . ' ' . $sku);

            $item_type = 'product';
            $module_code = '';
            $training_code = '';

            if ($product) {
                $meta_item_type = (string) $product->get_meta('themisdb_item_type', true);
                if ($meta_item_type !== '') {
                    $item_type = sanitize_key($meta_item_type);
                }

                $meta_module_code = (string) $product->get_meta('themisdb_module_code', true);
                if ($meta_module_code !== '') {
                    $module_code = sanitize_text_field($meta_module_code);
                }

                $meta_training_code = (string) $product->get_meta('themisdb_training_code', true);
                if ($meta_training_code !== '') {
                    $training_code = sanitize_text_field($meta_training_code);
                }

                if ($derived_product_type === '') {
                    $meta_product_type = (string) $product->get_meta('themisdb_product_type', true);
                    if ($meta_product_type !== '') {
                        $derived_product_type = sanitize_key($meta_product_type);
                    }
                }

                if ($derived_product_edition === '') {
                    $meta_edition = (string) $product->get_meta('themisdb_product_edition', true);
                    if ($meta_edition !== '') {
                        $derived_product_edition = sanitize_key($meta_edition);
                    }
                }
            }

            if ($module_code === '' && stripos($sku, 'MOD-') === 0) {
                $module_code = sanitize_text_field($sku);
                if ($item_type === 'product') {
                    $item_type = 'module';
                }
            }

            if ($training_code === '' && stripos($sku, 'TRAIN-') === 0) {
                $training_code = sanitize_text_field($sku);
                if ($item_type === 'product') {
                    $item_type = 'training';
                }
            }

            if ($module_code !== '') {
                $modules[] = $module_code;
                $item_type = 'module';
            }

            if ($training_code !== '') {
                $training_modules[] = $training_code;
                $item_type = 'training';
            }

            if ($derived_product_edition === '') {
                $derived_product_edition = $this->edition_from_signal($signal);
            }

            $order_items[] = array(
                'item_type' => $item_type,
                'product_id' => $product ? intval($product->get_id()) : null,
                'sku' => $sku !== '' ? $sku : null,
                'item_name' => $product_name,
                'quantity' => max(1, intval($item->get_quantity())),
                'unit_price' => floatval($item->get_total() / max(1, intval($item->get_quantity()))),
                'variant_data' => array(
                    'woo_line_item_id' => intval($item->get_id()),
                    'woo_variation_id' => method_exists($item, 'get_variation_id') ? intval($item->get_variation_id()) : 0,
                ),
                'metadata' => array(
                    'source' => 'woocommerce',
                    'woo_order_id' => intval($woo_order->get_id()),
                    'woo_line_item_id' => intval($item->get_id()),
                    'module_code' => $module_code,
                    'training_code' => $training_code,
                ),
            );
        }

        $modules = array_values(array_unique(array_filter($modules)));
        $training_modules = array_values(array_unique(array_filter($training_modules)));

        if ($derived_product_type === '') {
            $derived_product_type = 'database';
        }

        if ($derived_product_edition === '') {
            $derived_product_edition = $this->guess_product_edition($woo_order);
        }

        return array(
            'product_type' => $derived_product_type,
            'product_edition' => $derived_product_edition,
            'modules' => $modules,
            'training_modules' => $training_modules,
            'order_items' => $order_items,
        );
    }

    /**
     * Infer ThemisDB edition from normalized textual signal.
     *
     * @param string $signal
     * @return string
     */
    private function edition_from_signal($signal) {
        if (strpos($signal, 'reseller') !== false) {
            return 'reseller';
        }
        if (strpos($signal, 'hyperscaler') !== false || strpos($signal, 'hyper') !== false) {
            return 'hyperscaler';
        }
        if (strpos($signal, 'enterprise') !== false) {
            return 'enterprise';
        }
        if (strpos($signal, 'community') !== false) {
            return 'community';
        }

        return '';
    }

    /**
     * Best-effort product edition mapping from Woo line items.
     *
     * @param WC_Order $woo_order
     * @return string
     */
    private function guess_product_edition($woo_order) {
        foreach ($woo_order->get_items() as $item) {
            $product = $item->get_product();
            if (!$product) {
                continue;
            }

            $meta_edition = $product->get_meta('themisdb_product_edition', true);
            if (is_string($meta_edition) && $meta_edition !== '') {
                return sanitize_key($meta_edition);
            }

            $signal = strtolower((string) $product->get_name() . ' ' . (string) $product->get_sku());
            $edition = $this->edition_from_signal($signal);
            if ($edition !== '') {
                return $edition;
            }
        }

        return 'community';
    }

    // ──────────────────────────────────────────────────────────────────
    //  PRODUCT CATALOG SYNC
    // ──────────────────────────────────────────────────────────────────

    /**
     * Hook: stock quantity stored on a product (re-sync so price/active stay current).
     *
     * @param WC_Product $product
     */
    public function on_product_stock_changed($product) {
        if ($product instanceof WC_Product) {
            $this->sync_product_to_themisdb($product->get_id());
        }
    }

    /**
     * Hook: new Woo product saved.
     *
     * @param int $product_id
     */
    public function on_product_created($product_id) {
        $this->sync_product_to_themisdb($product_id);
    }

    /**
     * Hook: existing Woo product updated.
     *
     * @param int $product_id
     */
    public function on_product_updated($product_id) {
        $this->sync_product_to_themisdb($product_id);
    }

    /**
     * Hook: Woo product moved to trash → mark ThemisDB record inactive.
     *
     * @param int $product_id
     */
    public function on_product_trashed($product_id) {
        $this->set_themisdb_product_active($product_id, false);
    }

    /**
     * Hook: Woo product restored from trash → reactivate ThemisDB record.
     *
     * @param int $product_id
     */
    public function on_product_untrashed($product_id) {
        $this->set_themisdb_product_active($product_id, true);
    }

    /**
     * Sync a WooCommerce product into the matching ThemisDB catalog table.
     *
     * Routing:
     *   - themisdb_item_type = 'module'   → themisdb_modules
     *   - themisdb_item_type = 'training' → themisdb_training_modules
     *   - otherwise (has edition meta)    → themisdb_products
     *   - neither set                     → skip (not ThemisDB-managed)
     *
     * Reverse mapping stored as WP post meta:
     *   _themisdb_product_id  (for products)
     *   _themisdb_module_id   (for modules)
     *   _themisdb_training_id (for training)
     *
     * @param  int  $woo_product_id
     * @return bool True on successful upsert, false if skipped or failed.
     */
    public function sync_product_to_themisdb($woo_product_id) {
        if (!function_exists('wc_get_product')) {
            return false;
        }

        $product = wc_get_product($woo_product_id);
        if (!$product) {
            return false;
        }

        $item_type = sanitize_key((string) $product->get_meta('themisdb_item_type', true));
        $edition   = sanitize_key((string) $product->get_meta('themisdb_product_edition', true));

        // Guard: only sync products explicitly configured for ThemisDB.
        if ($item_type === '' && $edition === '') {
            return false;
        }

        $sku       = sanitize_text_field((string) $product->get_sku());
        $name      = sanitize_text_field((string) $product->get_name());
        $raw_price = $product->get_regular_price();
        $price     = floatval($raw_price !== '' ? $raw_price : $product->get_price());
        $desc      = sanitize_textarea_field((string) $product->get_description());
        $is_active = $product->get_status() === 'publish' ? 1 : 0;
        $currency  = function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : 'EUR';

        $meta_json = wp_json_encode(array(
            'woo_product_id'   => $woo_product_id,
            'woo_product_type' => $product->get_type(),
            'synced_at'        => current_time('mysql'),
        ));

        if ($item_type === 'module') {
            return $this->upsert_module($product, $woo_product_id, $sku, $name, $price, $desc, $is_active, $currency, $meta_json);
        }

        if ($item_type === 'training') {
            return $this->upsert_training($product, $woo_product_id, $sku, $name, $price, $desc, $is_active, $currency, $meta_json);
        }

        return $this->upsert_product($product, $woo_product_id, $sku, $name, $price, $desc, $is_active, $edition, $currency, $meta_json);
    }

    /**
     * Upsert a Woo product into themisdb_products.
     *
     * @param WC_Product $product
     * @param int        $woo_product_id
     * @param string     $sku
     * @param string     $name
     * @param float      $price
     * @param string     $desc
     * @param int        $is_active
     * @param string     $edition
     * @param string     $currency
     * @param string     $meta_json
     * @return bool
     */
    private function upsert_product($product, $woo_product_id, $sku, $name, $price, $desc, $is_active, $edition, $currency, $meta_json) {
        global $wpdb;

        if ($edition === '') {
            $signal  = strtolower($name . ' ' . $sku);
            $edition = $this->edition_from_signal($signal);
        }
        if ($edition === '') {
            $edition = 'community';
        }

        $product_type_meta = sanitize_key((string) $product->get_meta('themisdb_product_type', true));
        $product_type      = $product_type_meta !== '' ? $product_type_meta : 'database';
        $code              = $sku !== '' ? $sku : 'WOOPOST-' . $woo_product_id;
        $table             = $wpdb->prefix . 'themisdb_products';

        $existing_id = intval(get_post_meta($woo_product_id, '_themisdb_product_id', true));
        if (!$existing_id) {
            $existing_id = intval($wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table WHERE product_code = %s",
                $code
            )));
        }

        $row = array(
            'product_code' => $code,
            'product_name' => $name,
            'product_type' => $product_type,
            'edition'      => $edition,
            'description'  => $desc,
            'price'        => $price,
            'currency'     => $currency,
            'is_active'    => $is_active,
            'metadata'     => $meta_json,
        );

        if ($existing_id) {
            $wpdb->update($table, $row, array('id' => $existing_id));
            $db_id = $existing_id;
        } else {
            $wpdb->insert($table, $row);
            $db_id = intval($wpdb->insert_id);
        }

        if ($db_id) {
            update_post_meta($woo_product_id, '_themisdb_product_id', $db_id);
            do_action('themisdb_woo_product_synced', $woo_product_id, $db_id, 'product');
            return true;
        }

        error_log('ThemisDB Woo Bridge: upsert_product failed for Woo product ' . $woo_product_id);
        if (class_exists('ThemisDB_Error_Handler')) {
            ThemisDB_Error_Handler::log('warning', 'Woo product upsert failed', array(
                'woo_product_id' => intval($woo_product_id),
                'sku' => sanitize_text_field($sku),
            ));
        }
        return false;
    }

    /**
     * Upsert a Woo product into themisdb_modules.
     *
     * @param WC_Product $product
     * @param int        $woo_product_id
     * @param string     $sku
     * @param string     $name
     * @param float      $price
     * @param string     $desc
     * @param int        $is_active
     * @param string     $currency
     * @param string     $meta_json
     * @return bool
     */
    private function upsert_module($product, $woo_product_id, $sku, $name, $price, $desc, $is_active, $currency, $meta_json) {
        global $wpdb;

        $module_code = sanitize_text_field((string) $product->get_meta('themisdb_module_code', true));
        if ($module_code === '') {
            $module_code = $sku !== '' ? $sku : 'WOOMOD-' . $woo_product_id;
        }

        $category = sanitize_key((string) $product->get_meta('themisdb_module_category', true));
        if ($category === '') {
            $category = 'general';
        }

        $table       = $wpdb->prefix . 'themisdb_modules';
        $existing_id = intval(get_post_meta($woo_product_id, '_themisdb_module_id', true));
        if (!$existing_id) {
            $existing_id = intval($wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table WHERE module_code = %s",
                $module_code
            )));
        }

        $row = array(
            'module_code'     => $module_code,
            'module_name'     => $name,
            'module_category' => $category,
            'description'     => $desc,
            'price'           => $price,
            'currency'        => $currency,
            'is_active'       => $is_active,
            'metadata'        => $meta_json,
        );

        if ($existing_id) {
            $wpdb->update($table, $row, array('id' => $existing_id));
            $db_id = $existing_id;
        } else {
            $wpdb->insert($table, $row);
            $db_id = intval($wpdb->insert_id);
        }

        if ($db_id) {
            update_post_meta($woo_product_id, '_themisdb_module_id', $db_id);
            do_action('themisdb_woo_product_synced', $woo_product_id, $db_id, 'module');
            return true;
        }

        error_log('ThemisDB Woo Bridge: upsert_module failed for Woo product ' . $woo_product_id);
        if (class_exists('ThemisDB_Error_Handler')) {
            ThemisDB_Error_Handler::log('warning', 'Woo module upsert failed', array(
                'woo_product_id' => intval($woo_product_id),
                'sku' => sanitize_text_field($sku),
            ));
        }
        return false;
    }

    /**
     * Upsert a Woo product into themisdb_training_modules.
     *
     * @param WC_Product $product
     * @param int        $woo_product_id
     * @param string     $sku
     * @param string     $name
     * @param float      $price
     * @param string     $desc
     * @param int        $is_active
     * @param string     $currency
     * @param string     $meta_json
     * @return bool
     */
    private function upsert_training($product, $woo_product_id, $sku, $name, $price, $desc, $is_active, $currency, $meta_json) {
        global $wpdb;

        $training_code = sanitize_text_field((string) $product->get_meta('themisdb_training_code', true));
        if ($training_code === '') {
            $training_code = $sku !== '' ? $sku : 'WOOTRAIN-' . $woo_product_id;
        }

        $training_type = sanitize_key((string) $product->get_meta('themisdb_training_type', true));
        if ($training_type === '') {
            $training_type = 'online';
        }

        $duration    = intval($product->get_meta('themisdb_training_duration_hours', true));
        $table       = $wpdb->prefix . 'themisdb_training_modules';
        $existing_id = intval(get_post_meta($woo_product_id, '_themisdb_training_id', true));
        if (!$existing_id) {
            $existing_id = intval($wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table WHERE training_code = %s",
                $training_code
            )));
        }

        $row = array(
            'training_code'  => $training_code,
            'training_name'  => $name,
            'training_type'  => $training_type,
            'duration_hours' => $duration > 0 ? $duration : null,
            'description'    => $desc,
            'price'          => $price,
            'currency'       => $currency,
            'is_active'      => $is_active,
            'metadata'       => $meta_json,
        );

        if ($existing_id) {
            $wpdb->update($table, $row, array('id' => $existing_id));
            $db_id = $existing_id;
        } else {
            $wpdb->insert($table, $row);
            $db_id = intval($wpdb->insert_id);
        }

        if ($db_id) {
            update_post_meta($woo_product_id, '_themisdb_training_id', $db_id);
            do_action('themisdb_woo_product_synced', $woo_product_id, $db_id, 'training');
            return true;
        }

        error_log('ThemisDB Woo Bridge: upsert_training failed for Woo product ' . $woo_product_id);
        if (class_exists('ThemisDB_Error_Handler')) {
            ThemisDB_Error_Handler::log('warning', 'Woo training upsert failed', array(
                'woo_product_id' => intval($woo_product_id),
                'sku' => sanitize_text_field($sku),
            ));
        }
        return false;
    }

    /**
     * Update the is_active flag for all ThemisDB catalog records linked to a Woo product.
     *
     * @param int  $woo_product_id
     * @param bool $is_active
     */
    private function set_themisdb_product_active($woo_product_id, $is_active) {
        global $wpdb;

        $flag        = $is_active ? 1 : 0;
        $product_id  = intval(get_post_meta($woo_product_id, '_themisdb_product_id',  true));
        $module_id   = intval(get_post_meta($woo_product_id, '_themisdb_module_id',   true));
        $training_id = intval(get_post_meta($woo_product_id, '_themisdb_training_id', true));

        if ($product_id) {
            $wpdb->update($wpdb->prefix . 'themisdb_products',         array('is_active' => $flag), array('id' => $product_id));
        }
        if ($module_id) {
            $wpdb->update($wpdb->prefix . 'themisdb_modules',          array('is_active' => $flag), array('id' => $module_id));
        }
        if ($training_id) {
            $wpdb->update($wpdb->prefix . 'themisdb_training_modules', array('is_active' => $flag), array('id' => $training_id));
        }
    }
}
