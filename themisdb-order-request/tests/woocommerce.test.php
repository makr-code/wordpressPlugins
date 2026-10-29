<?php
/** Isolated Woo lifecycle regression; WooCommerce and WordPress are not loaded. */
define('ABSPATH', __DIR__ . DIRECTORY_SEPARATOR);
class WooCommerce {}
class WC_Order {}
function sanitize_key($value) { return strtolower($value); }
function sanitize_text_field($value) { return trim($value); }
function current_time($format) { return '2026-10-29 18:35:00'; }
function apply_filters($name, $value, ...$args) { return $value; }
function add_action($name, $callback, ...$args) { $GLOBALS['hooks'][$name][] = $callback; }
function do_action($name, ...$args) {
    foreach ($GLOBALS['hooks'][$name] ?? array() as $callback) { $callback(...$args); }
}
function wc_get_order($id) { return $GLOBALS['woo']; }
function get_post_meta($id, $key, $single) { return $GLOBALS['woo']->get_meta($key); }
function update_post_meta($id, $key, $value) { throw new RuntimeException('Order linkage must use Woo CRUD'); }
class WooOrderDouble extends WC_Order {
    public $meta = array('_themisdb_order_id' => 2, '_themisdb_license_id' => 11);
    public $paid = true;
    public $status = 'completed';
    public $gateway = 'stripe';
    public $notes = array();
    public $saved = 0;
    public $save_result = 1;
    public function get_id() { return 1; }
    public function get_meta($key, $single = true) { return $this->meta[$key] ?? ''; }
    public function update_meta_data($key, $value) { $this->meta[$key] = $value; }
    public function save() { $this->saved++; return $this->save_result; }
    public function is_paid() { return $this->paid; }
    public function get_status() { return $this->status; }
    public function get_payment_method() { return $this->gateway; }
    public function add_order_note($note) { $this->notes[] = $note; }
    public function get_total_refunded() { return 120; }
    public function get_total() { return 120; }
    public function get_items($type) { return array(new WooItemDouble()); }
    public function __call($method, $args) {
        $fields = array('get_billing_first_name' => 'Synthetic', 'get_billing_last_name' => 'Customer',
            'get_billing_company' => '', 'get_customer_id' => 7, 'get_billing_email' => 'synthetic@example.invalid',
            'get_billing_address_1' => 'Teststrasse 1', 'get_billing_address_2' => '', 'get_billing_postcode' => '12345',
            'get_billing_city' => 'Test', 'get_billing_country' => 'DE', 'get_shipping_first_name' => '',
            'get_shipping_last_name' => '', 'get_shipping_address_1' => '', 'get_shipping_address_2' => '',
            'get_shipping_postcode' => '', 'get_shipping_city' => '', 'get_shipping_country' => 'DE',
            'get_shipping_method' => '', 'get_shipping_total' => 0, 'get_currency' => 'EUR');
        if (!array_key_exists($method, $fields)) { throw new RuntimeException('Unexpected Woo method ' . $method); }
        return $fields[$method];
    }
}
class WooItemDouble {
    public function get_product() { return null; }
    public function get_name() { return 'Synthetic Enterprise'; }
    public function get_quantity() { return 1; }
    public function get_total() { return 120; }
    public function get_id() { return 31; }
}
class ThemisDB_Order_Manager {
    public static $order;
    public static $result = true;
    public static $item_result = true;
    public static $created = 0;
    public static function create_order($data) {
        self::$created++;
        self::$order = array_merge($data, array('id' => 2));
        return 2;
    }
    public static function set_order_items($id, $items, $currency) { return self::$item_result; }
    public static function get_order($id) { return self::$order; }
    public static function set_order_status($id, $status) {
        if (self::$result) { self::$order['status'] = $status; }
        return self::$result;
    }
}
class ThemisDB_Contract_Manager {
    public static $calls = 0;
    public static function ensure_contract_for_order($id, $args) { self::$calls++; return array('success' => true, 'contract_id' => 3); }
}
class ThemisDB_License_Manager {
    public static $license;
    public static $calls = array();
    public static $result = true;
    public static $create = false;
    public static function get_license($id) { return self::$license; }
    public static function get_license_by_contract($id) { return self::$create ? null : self::$license; }
    public static function create_license($data) {
        self::$license = array('id' => 11, 'order_id' => $data['order_id'], 'license_status' => 'pending');
        return 11;
    }
    public static function activate_license($id) {
        self::$calls[] = 'activate';
        if (self::$result) { self::$license['license_status'] = 'active'; }
        return self::$result;
    }
    public static function suspend_license($id, $reason) { self::$calls[] = 'suspend'; return self::$result; }
    public static function cancel_license($id, $reason, $user) { self::$calls[] = 'cancel'; return self::$result; }
}
class ThemisDB_Error_Handler {
    public static $logs = array();
    public static function log($level, $message, $context) { self::$logs[] = $message; }
}
class ThemisDB_Email_Handler {
    public static $calls = 0;
    public static function send_license_email($id) { self::$calls++; }
}
require dirname(__DIR__) . '/includes/class-woocommerce-bridge.php';
$bridge = new ThemisDB_WooCommerce_Bridge();
$checks = $failures = 0;
function woo_check($condition, $message) {
    global $checks, $failures;
    $checks++;
    $failures += $condition ? 0 : 1;
    echo ($condition ? 'PASS ' : 'FAIL ') . $message . PHP_EOL;
}
foreach (array('thankyou', 'request', 'completed', 'payment_complete', 'status_changed') as $entry) {
    foreach (array('paid', 'unpaid', 'cheque', 'blocked', 'waiver', 'closed', 'merchandise', 'cancelled_license', 'wrong_link') as $scenario) {
        $woo = new WooOrderDouble();
        $woo->paid = $scenario !== 'unpaid';
        if ($scenario === 'cheque') { $woo->gateway = 'cheque'; $woo->status = 'processing'; }
        if ($scenario === 'wrong_link') { $woo->meta['_themisdb_order_id'] = 999; }
        ThemisDB_Order_Manager::$order = array('id' => 2, 'status' => $scenario === 'closed' ? 'cancelled' : 'confirmed',
            'customer_type' => 'consumer', 'legal_withdrawal_waiver' => $scenario === 'waiver' ? 0 : 1,
            'product_type' => $scenario === 'merchandise' ? 'merchandise' : 'database');
        ThemisDB_Order_Manager::$result = $scenario !== 'blocked';
        ThemisDB_License_Manager::$license = array('id' => 11, 'order_id' => 2, 'license_status' => $scenario === 'cancelled_license' ? 'cancelled' : 'pending');
        ThemisDB_License_Manager::$calls = ThemisDB_Error_Handler::$logs = array();
        ThemisDB_Email_Handler::$calls = 0;
        if ($entry === 'thankyou') { $bridge->maybe_generate_license(1); }
        if ($entry === 'request') { $bridge->handle_license_generation_requested(2, 1); }
        if ($entry === 'completed') { $bridge->on_order_completed(1); }
        if ($entry === 'payment_complete') { $bridge->on_payment_complete(1); }
        if ($entry === 'status_changed') { $bridge->on_order_status_changed(1, 'pending', $woo->status, $woo); }
        woo_check(in_array('activate', ThemisDB_License_Manager::$calls, true) === ($scenario === 'paid'), "$entry/$scenario gates activation");
        if ($scenario === 'paid') {
            $bridge->maybe_generate_license(1);
            woo_check(count(ThemisDB_License_Manager::$calls) === 1, "$entry duplicate callback does not reactivate");
        }
    }
}
foreach (array('ready', 'new_license', 'unpaid', 'waiver', 'blocked', 'cancelled_license', 'wrong_license', 'activation_failure', 'save_failure') as $scenario) {
    $woo = new WooOrderDouble();
    unset($woo->meta['_themisdb_license_id']);
    $woo->paid = $scenario !== 'unpaid';
    $woo->save_result = $scenario === 'save_failure' ? 0 : 1;
    ThemisDB_Order_Manager::$order = array('id' => 2, 'status' => 'confirmed', 'product_type' => 'database',
        'customer_type' => 'consumer', 'legal_withdrawal_waiver' => $scenario === 'waiver' ? 0 : 1);
    ThemisDB_Order_Manager::$result = $scenario !== 'blocked';
    ThemisDB_License_Manager::$license = array('id' => 11, 'order_id' => $scenario === 'wrong_license' ? 999 : 2,
        'license_status' => $scenario === 'cancelled_license' ? 'cancelled' : 'pending');
    ThemisDB_License_Manager::$result = $scenario !== 'activation_failure';
    ThemisDB_License_Manager::$create = $scenario === 'new_license';
    ThemisDB_License_Manager::$calls = ThemisDB_Error_Handler::$logs = array();
    ThemisDB_Email_Handler::$calls = ThemisDB_Contract_Manager::$calls = 0;
    $bridge->handle_license_generation_requested(2, 1);
    $eligible = in_array($scenario, array('ready', 'new_license', 'activation_failure', 'save_failure'), true);
    woo_check(count(ThemisDB_License_Manager::$calls) === ($eligible ? 1 : 0), "$scenario contract license respects activation gate");
    woo_check(ThemisDB_Email_Handler::$calls === (in_array($scenario, array('ready', 'new_license'), true) ? 1 : 0), "$scenario contract license email follows successful persistence");
    if ($scenario === 'ready') {
        woo_check($woo->saved === 1 && $woo->meta['_themisdb_license_id'] === 11, 'License linkage uses Woo CRUD save');
        $bridge->handle_license_generation_requested(2, 1);
        woo_check(ThemisDB_Email_Handler::$calls === 1 && count(ThemisDB_License_Manager::$calls) === 1, 'Repeated generation emits no duplicate mail or activation');
    }
    ThemisDB_License_Manager::$create = false;
    if (in_array($scenario, array('activation_failure', 'save_failure'), true)) {
        woo_check(!empty(ThemisDB_Error_Handler::$logs), "$scenario exposes manual reconciliation error");
    }
}
foreach (array('pending', 'on-hold', 'failed', 'cancelled', 'refunded', 'partial', 'blocked', 'license_failure', 'wrong_license') as $scenario) {
    $woo = new WooOrderDouble();
    $woo->status = $scenario === 'partial' ? 'completed' : $scenario;
    ThemisDB_Order_Manager::$order = array('id' => 2, 'status' => 'confirmed', 'product_type' => 'database',
        'customer_type' => 'business');
    ThemisDB_Order_Manager::$result = $scenario !== 'blocked';
    ThemisDB_License_Manager::$license = array('id' => 11, 'order_id' => $scenario === 'wrong_license' ? 999 : 2, 'license_status' => 'active');
    ThemisDB_License_Manager::$result = $scenario !== 'license_failure';
    ThemisDB_License_Manager::$calls = ThemisDB_Error_Handler::$logs = array();
    $target = in_array($scenario, array('blocked', 'license_failure', 'wrong_license'), true) ? 'cancelled' : $scenario;
    if ($scenario === 'partial') {
        $bridge->on_order_status_changed(1, 'completed', 'partially-refunded', $woo);
    } elseif ($scenario === 'refunded') {
        $bridge->on_order_refunded(1, 21);
    } else {
        $bridge->on_order_status_changed(1, 'completed', $target, $woo);
    }
    $expected = in_array($scenario, array('partial', 'blocked', 'wrong_license'), true) ? array()
        : (in_array($scenario, array('pending', 'on-hold', 'failed'), true) ? array('suspend') : array('cancel'));
    woo_check(ThemisDB_License_Manager::$calls === $expected, "$scenario preserves suspension/cancellation/refund semantics");
    if (in_array($scenario, array('blocked', 'license_failure', 'wrong_license'), true)) {
        woo_check(!empty(ThemisDB_Error_Handler::$logs), "$scenario logs failed lifecycle processing");
    }
}
foreach (array('completed', 'processing') as $status) {
    $woo = new WooOrderDouble();
    $woo->status = $status;
    ThemisDB_Order_Manager::$order = array('id' => 2, 'status' => 'confirmed', 'product_type' => 'database', 'customer_type' => 'business');
    ThemisDB_Order_Manager::$result = true;
    ThemisDB_License_Manager::$license = array('id' => 11, 'order_id' => 2, 'license_status' => 'pending');
    ThemisDB_License_Manager::$calls = array();
    ThemisDB_License_Manager::$result = false;
    $bridge->on_order_status_changed(1, 'pending', $status, $woo);
    woo_check(ThemisDB_License_Manager::$calls === array('activate'), "$status failed activation is not retried within the same callback");
}
foreach (array('paid', 'legal_missing', 'unpaid', 'items_failed', 'link_failed') as $scenario) {
    $woo = new WooOrderDouble();
    $woo->meta = $scenario === 'legal_missing' ? array() : array(
        '_themisdb_terms_accepted' => 'yes', '_themisdb_privacy_accepted' => 'yes',
        '_themisdb_withdrawal_acknowledged' => 'yes', '_themisdb_withdrawal_waiver' => 'yes');
    $woo->paid = $scenario !== 'unpaid';
    $woo->save_result = $scenario === 'link_failed' ? 0 : 1;
    ThemisDB_Order_Manager::$order = null;
    ThemisDB_Order_Manager::$result = true;
    ThemisDB_Order_Manager::$item_result = $scenario !== 'items_failed';
    ThemisDB_Order_Manager::$created = 0;
    ThemisDB_License_Manager::$license = array('id' => 11, 'order_id' => 2, 'license_status' => 'pending');
    ThemisDB_License_Manager::$calls = ThemisDB_Error_Handler::$logs = array();
    ThemisDB_License_Manager::$result = true;
    ThemisDB_Email_Handler::$calls = 0;
    $bridge->on_order_completed(1);
    woo_check(ThemisDB_Order_Manager::$created === 1, "$scenario imports one synthetic Woo order");
    woo_check(in_array('activate', ThemisDB_License_Manager::$calls, true) === ($scenario === 'paid'), "$scenario imported order gates license");
    if (in_array($scenario, array('legal_missing', 'unpaid'), true)) {
        woo_check(ThemisDB_Order_Manager::$order['status'] === 'pending', "$scenario imported order stays pending");
    }
    if ($scenario === 'paid') {
        woo_check($woo->meta['_themisdb_order_id'] === 2 && $woo->saved >= 1, 'Imported order linkage uses Woo CRUD');
        woo_check(ThemisDB_Email_Handler::$calls === 1, 'Imported paid order gets one license email');
    }
    if (in_array($scenario, array('items_failed', 'link_failed'), true)) {
        woo_check(!empty(ThemisDB_Error_Handler::$logs), "$scenario logs failed order import");
    }
}
echo "$checks checks; $failures failures.\n";
exit($failures ? 1 : 0);
