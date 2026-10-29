<?php
/** Execute real checkout submission against synthetic in-memory collaborators. */
define('ABSPATH', __DIR__ . DIRECTORY_SEPARATOR);
function check_ajax_referer($action, $field) {}
function sanitize_key($value) { return strtolower($value); }
function sanitize_text_field($value) { return trim($value); }
function is_email($value) { return filter_var($value, FILTER_VALIDATE_EMAIL); }
function __($value, $domain) { return $value; }
function do_action($action, ...$args) { $GLOBALS['checkout_events'][] = $action; }
class CheckoutResponse extends RuntimeException {
    public $success;
    public function __construct($success) { $this->success = $success; }
}
function wp_send_json_success($data) { throw new CheckoutResponse(true); }
function wp_send_json_error($data) { throw new CheckoutResponse(false); }
class ThemisDB_Order_Manager {
    public static $order;
    public static function get_order($id) { return self::$order; }
    public static function set_order_status($id, $status) { self::$order['status'] = $status; return true; }
    public static function get_order_items($id) { return array(array('total_price' => 220)); }
    public static function reserve_inventory_for_order($id) { return true; }
}
class ThemisDB_Email_Handler {
    public static function send_order_confirmation($id) {}
    public static function send_invoice_email($id) {}
}
class ThemisDB_Contract_Manager {
    public static function ensure_contract_for_order($id, $options) { return array('success' => true, 'contract_id' => 3); }
}
class ThemisDB_License_Manager {
    public static $created = 0;
    public static function get_license_by_contract($id) { return null; }
    public static function create_license($data) { self::$created++; return 11; }
}
class ThemisDB_Payment_Manager {
    public static $created = array();
    public static $verified = 0;
    public static function create_payment($data) {
        self::$created[] = array_merge($data, array('payment_status' => 'pending'));
        return 12;
    }
    public static function get_payments_by_order($id) { return array(); }
    public static function verify_payment($id) { self::$verified++; return true; }
}
require dirname(__DIR__) . '/includes/class-shortcodes.php';
$shortcodes = (new ReflectionClass('ThemisDB_Order_Shortcodes'))->newInstanceWithoutConstructor();
$checks = $failures = 0;
function checkout_check($condition, $message) {
    global $checks, $failures;
    $checks++;
    $failures += $condition ? 0 : 1;
    echo ($condition ? 'PASS ' : 'FAIL ') . $message . PHP_EOL;
}
session_start();
foreach (array('database', 'merchandise') as $type) {
    foreach (array('bank_transfer', 'stripe', 'paypal') as $method) {
        ThemisDB_Order_Manager::$order = array(
            'id' => 2, 'order_number' => 'SYNTHETIC-ONLY', 'customer_id' => 9,
            'customer_type' => 'consumer', 'customer_name' => 'Synthetic', 'customer_email' => 'synthetic@example.invalid',
            'billing_name' => 'Synthetic', 'billing_address_line1' => 'Teststrasse 1',
            'billing_postal_code' => '12345', 'billing_city' => 'Test', 'billing_country' => 'DE',
            'legal_terms_accepted' => 1, 'legal_privacy_accepted' => 1, 'legal_withdrawal_acknowledged' => 1,
            'legal_withdrawal_waiver' => 1, 'legal_accepted_at' => '2026-10-29 12:00:00',
            'payment_method' => $method, 'product_type' => $type, 'product_edition' => 'demo',
            'total_amount' => 220, 'currency' => 'EUR', 'status' => 'draft',
        );
        $_SESSION['themisdb_order_id'] = 2;
        $_POST = array('payment_method' => $method);
        $checkout_events = array();
        ThemisDB_Payment_Manager::$created = array();
        ThemisDB_Payment_Manager::$verified = ThemisDB_License_Manager::$created = 0;
        try {
            $shortcodes->ajax_submit_order();
            throw new RuntimeException('Checkout did not return JSON');
        } catch (CheckoutResponse $response) {
            checkout_check($response->success, "$type/$method submits successfully");
        }
        checkout_check(count(ThemisDB_Payment_Manager::$created) === 1, "$type/$method creates one payment");
        checkout_check(ThemisDB_Payment_Manager::$created[0]['payment_status'] === 'pending', "$type/$method remains unpaid");
        checkout_check(ThemisDB_Payment_Manager::$verified === 0, "$type/$method never verifies without payment evidence");
        checkout_check(ThemisDB_Order_Manager::$order['status'] === 'pending', "$type/$method order remains pending");
        checkout_check(ThemisDB_License_Manager::$created === ($type === 'database' ? 1 : 0), "$type/$method keeps license and merchandise paths separate");
        checkout_check(!isset($_SESSION['themisdb_order_id']), "$type/$method clears completed cart session");
        checkout_check($checkout_events === array('themisdb_order_submitted'), "$type/$method emits submission, not payment events");
    }
}
session_destroy();
echo "$checks checks; $failures failures.\n";
exit($failures ? 1 : 0);
