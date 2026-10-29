<?php
/** Isolated payment-to-license regression; no WordPress or external services. */
define('ABSPATH', __DIR__ . DIRECTORY_SEPARATOR);
define('ARRAY_A', 'ARRAY_A');
function sanitize_key($value) { return strtolower($value); }
function current_time($format) { return '2026-10-29 17:00:00'; }
function get_current_user_id() { return 7; }
function sanitize_text_field($value) { return trim($value); }
function sanitize_textarea_field($value) { return trim($value); }
class ThemisDB_EPServer_API {
    public static function check_payment_status($id) { return array('status' => 'paid'); }
}
class ThemisDB_Error_Handler {
    public static $logs = array();
    public static function log($level, $message, $context) { self::$logs[] = $message; }
}
class ThemisDB_Order_Manager {
    public static $order;
    public static $status_result = true;
    public static $calls = 0;
    public static function get_order($id) { return self::$order; }
    public static function set_order_status($id, $status) { self::$calls++; return self::$status_result; }
}
class ThemisDB_License_Manager {
    public static $calls = 0;
    public static $status = 'pending';
    public static $result = true;
    public static function get_license_by_contract($id) { return array('id' => 11, 'license_status' => self::$status); }
    public static function activate_license($id) {
        self::$calls++;
        if (self::$result) { self::$status = 'active'; }
        return self::$result;
    }
}
class ThemisDB_Email_Handler {
    public static $calls = 0;
    public static function send_license_email($id) { self::$calls++; }
}
class PaymentDatabaseDouble {
    public $prefix = 'fixture_';
    public $payment;
    public $result = 1;
    public $updates = 0;
    public $where;
    public function prepare($sql, ...$args) { return $sql; }
    public function get_row($sql, $format) { return $this->payment; }
    public function update($table, $data, $where, $formats, $where_formats) {
        $this->updates++;
        $this->where = $where;
        if ($this->result === 1 && $this->payment) { $this->payment = array_merge($this->payment, $data); }
        return $this->result;
    }
}
require dirname(__DIR__) . '/includes/class-payment-manager.php';
$checks = $failures = 0;
function payment_check($condition, $message) {
    global $checks, $failures;
    $checks++;
    $failures += $condition ? 0 : 1;
    echo ($condition ? 'PASS ' : 'FAIL ') . $message . PHP_EOL;
}
foreach (array('pending', 'overdue', 'verified', 'failed', 'missing', 'db_failure', 'conflict', 'blocked_order', 'missing_order', 'closed_order', 'consumer', 'active_license', 'license_failure') as $scenario) {
    $wpdb = new PaymentDatabaseDouble();
    $wpdb->payment = $scenario === 'missing' ? null : array(
        'id' => 1, 'order_id' => 2, 'contract_id' => 3, 'metadata' => null,
        'payment_status' => in_array($scenario, array('verified', 'failed', 'overdue'), true) ? $scenario : 'pending',
    );
    $wpdb->result = $scenario === 'db_failure' ? false : ($scenario === 'conflict' ? 0 : 1);
    ThemisDB_Order_Manager::$order = $scenario === 'missing_order' ? null : array(
        'customer_type' => $scenario === 'consumer' ? 'consumer' : 'business',
        'legal_withdrawal_waiver' => 0,
        'status' => $scenario === 'closed_order' ? 'cancelled' : 'pending',
    );
    ThemisDB_Order_Manager::$status_result = $scenario !== 'blocked_order';
    ThemisDB_Order_Manager::$calls = ThemisDB_License_Manager::$calls = ThemisDB_Email_Handler::$calls = 0;
    ThemisDB_License_Manager::$status = $scenario === 'active_license' ? 'active' : 'pending';
    ThemisDB_License_Manager::$result = $scenario !== 'license_failure';
    ThemisDB_Error_Handler::$logs = array();
    $result = ThemisDB_Payment_Manager::verify_payment(1, 7);
    $success = in_array($scenario, array('pending', 'overdue', 'verified', 'consumer', 'active_license'), true);
    payment_check($result === $success, "$scenario reports expected outcome");
    $activate = in_array($scenario, array('pending', 'overdue', 'license_failure'), true);
    payment_check(ThemisDB_License_Manager::$calls === ($activate ? 1 : 0), "$scenario gates license activation");
    payment_check(ThemisDB_Email_Handler::$calls === ($activate && $scenario !== 'license_failure' ? 1 : 0), "$scenario gates license email");
    if (!$success) {
        payment_check(!empty(ThemisDB_Error_Handler::$logs), "$scenario logs the processing failure");
    }
    if (in_array($scenario, array('verified', 'failed', 'missing', 'missing_order', 'closed_order'), true)) {
        payment_check($wpdb->updates === 0, "$scenario avoids payment rewrite");
    } elseif (!in_array($scenario, array('blocked_order'), true)) {
        payment_check(isset($wpdb->where['payment_status']), "$scenario guards previous payment status");
    }
    if ($scenario === 'verified') {
        payment_check(ThemisDB_Order_Manager::$calls === 0, 'Repeated verification has no downstream status side effects');
    }
}
foreach (array('edit', 'epserver') as $entry) {
    $wpdb = new PaymentDatabaseDouble();
    $wpdb->payment = array('id' => 1, 'order_id' => 2, 'contract_id' => 3, 'metadata' => null,
        'payment_status' => 'pending', 'epserver_payment_id' => 'synthetic-only');
    ThemisDB_Order_Manager::$order = array('customer_type' => 'business', 'status' => 'pending');
    ThemisDB_Order_Manager::$status_result = true;
    ThemisDB_License_Manager::$status = 'pending';
    ThemisDB_License_Manager::$result = true;
    ThemisDB_License_Manager::$calls = ThemisDB_Email_Handler::$calls = 0;
    $result = $entry === 'edit'
        ? ThemisDB_Payment_Manager::update_payment(1, array('payment_status' => 'verified', 'notes' => 'Synthetic'))
        : ThemisDB_Payment_Manager::check_payment_with_epserver(1);
    payment_check((bool) $result, "$entry reports verification");
    payment_check(ThemisDB_License_Manager::$calls === 1, "$entry uses common activation workflow");
    payment_check(ThemisDB_Email_Handler::$calls === 1, "$entry sends one synthetic mail");
    payment_check(ThemisDB_Payment_Manager::verify_payment(1), "$entry repeated callback succeeds");
    payment_check(ThemisDB_License_Manager::$calls === 1 && ThemisDB_Email_Handler::$calls === 1, "$entry repeated callback causes no duplicate side effects");
    payment_check(!ThemisDB_Payment_Manager::mark_payment_failed(1), "$entry cannot demote verified payment to failed");
    payment_check($wpdb->payment['payment_status'] === 'verified', "$entry retains verified status");
}
foreach (array('pending', 'overdue', 'verified', 'failed') as $status) {
    foreach (array('mark_payment_failed', 'mark_payment_overdue') as $method) {
        $wpdb = new PaymentDatabaseDouble();
        $wpdb->payment = array('id' => 1, 'payment_status' => $status, 'metadata' => null, 'notes' => '');
        $expected = in_array($status, array('pending', 'overdue'), true)
            || ($method === 'mark_payment_failed' && $status === 'failed');
        payment_check(ThemisDB_Payment_Manager::$method(1, 'Synthetic') === $expected, "$method respects $status transition");
        if ($wpdb->updates) {
            payment_check($wpdb->where['payment_status'] === $status, "$method guards $status transition");
        } else {
            payment_check($wpdb->payment['payment_status'] === $status, "$method leaves terminal $status untouched");
        }
    }
}
echo "$checks checks; $failures failures.\n";
exit($failures ? 1 : 0);
