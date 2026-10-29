<?php
/** Isolated bank persistence and payment handoff regression. */
define('ABSPATH', __DIR__ . DIRECTORY_SEPARATOR);
define('ARRAY_A', 'ARRAY_A');
class WP_Error {
    public $code;
    public $data;
    public $message;
    public function __construct($code, $message, $data = null) { $this->code = $code; $this->message = $message; $this->data = $data; }
    public function get_error_data() { return $this->data; }
    public function get_error_message() { return $this->message; }
}
function is_wp_error($value) { return $value instanceof WP_Error; }
function __($value, $domain) { return $value; }
function sanitize_text_field($value) { return trim($value); }
function sanitize_textarea_field($value) { return trim($value); }
function sanitize_file_name($value) { return basename($value); }
function wp_generate_uuid4() { return 'synthetic-import'; }
function wp_json_encode($value) { return json_encode($value); }
function get_current_user_id() { return 7; }
function get_transient($key) { return $GLOBALS['bank_preview']; }
function delete_transient($key) {}
class BankAdminFailure extends RuntimeException {}
function wp_die($message) { throw new BankAdminFailure($message); }
function wp_redirect($url) { throw new RuntimeException('Unexpected success-shaped redirect: ' . $url); }
function admin_url($path) { return $path; }
class ThemisDB_Payment_Manager {
    public static $calls = 0;
    public static $result = true;
    public static $payment;
    public static function get_payment($id) { return self::$payment; }
    public static function verify_payment($id, $user) {
        self::$calls++;
        if (self::$result) { self::$payment['payment_status'] = 'verified'; }
        return self::$result;
    }
}
class BankDatabaseDouble {
    public $prefix = 'fixture_';
    public $last_error = '';
    public $insert_id = 0;
    public $rows = array();
    public $fail = '';
    public $fail_completion = false;
    public $zero_assignment = false;
    public $summary_read_failure = false;
    public $summary_write_failure = false;
    public $duplicate_count = 0;
    public $queries = array();
    public $transaction;
    public function prepare($sql, ...$args) { $this->queries[] = array($sql, $args); return $sql; }
    public function get_row($sql, $format) {
        if (strpos($sql, 'themisdb_payments') !== false) { return ThemisDB_Payment_Manager::$payment; }
        if (strpos($sql, 'themisdb_orders') !== false) { return array('id' => 3); }
        return $this->transaction;
    }
    public function get_var($sql) { return $this->duplicate_count; }
    public function get_results($sql, $format) {
        if ($this->summary_read_failure) { $this->last_error = 'Synthetic summary query error'; return array(); }
        $counts = array();
        foreach ($this->rows['fixture_themisdb_bank_transactions'] ?? array() as $row) {
            $counts[$row['match_status']] = ($counts[$row['match_status']] ?? 0) + 1;
        }
        $result = array();
        foreach ($counts as $status => $count) { $result[] = array('match_status' => $status, 'row_count' => $count); }
        return $result;
    }
    public function insert($table, $data) {
        if ($this->fail === $table) { return false; }
        $this->insert_id++;
        $this->rows[$table][$this->insert_id] = $data;
        return 1;
    }
    public function update($table, $data, $where, $formats = null, $where_formats = null) {
        if ($this->fail === $table) { return false; }
        if ($this->summary_write_failure && $table === 'fixture_themisdb_bank_imports') { return false; }
        if ($this->fail_completion && in_array($data['match_status'] ?? '', array('matched', 'manual'), true)) { return false; }
        if ($this->zero_assignment && $table === 'fixture_themisdb_bank_transactions') { return 0; }
        $id = $where['id'];
        $this->rows[$table][$id] = array_merge($this->rows[$table][$id] ?? array(), $data);
        return 1;
    }
}
require dirname(__DIR__) . '/includes/class-bank-import.php';
$checks = $failures = 0;
function bank_check($condition, $message) {
    global $checks, $failures;
    $checks++;
    $failures += $condition ? 0 : 1;
    echo ($condition ? 'PASS ' : 'FAIL ') . $message . PHP_EOL;
}
function bank_fixture() {
    global $wpdb;
    $wpdb = new BankDatabaseDouble();
    ThemisDB_Payment_Manager::$calls = 0;
    ThemisDB_Payment_Manager::$result = true;
    ThemisDB_Payment_Manager::$payment = array('id' => 5, 'amount' => 120, 'currency' => 'EUR', 'payment_status' => 'pending');
    return array('id' => 21, 'import_id' => 1, 'booking_date' => '2026-10-29', 'value_date' => '2026-10-29',
        'amount' => 120, 'currency' => 'EUR', 'purpose' => 'SYNTHETIC ONLY',
        'matched_payment_id' => 5, 'match_status' => 'matched', 'match_confidence' => 'auto');
}
foreach (array('success', 'header_insert', 'transaction_insert', 'payment_write', 'verification', 'completion', 'summary') as $scenario) {
    $tx = bank_fixture();
    if ($scenario === 'header_insert') { $wpdb->fail = 'fixture_themisdb_bank_imports'; }
    if ($scenario === 'transaction_insert') { $wpdb->fail = 'fixture_themisdb_bank_transactions'; }
    if ($scenario === 'payment_write') { $wpdb->fail = 'fixture_themisdb_payments'; }
    $wpdb->fail_completion = $scenario === 'completion';
    ThemisDB_Payment_Manager::$result = $scenario !== 'verification';
    if ($scenario === 'summary') { $wpdb->summary_write_failure = true; }
    $result = ThemisDB_Bank_Import::save_import(array('filename' => 'synthetic.csv', 'bank_format' => 'generic'), array($tx));
    bank_check($scenario === 'success' ? is_int($result) : is_wp_error($result), "$scenario reports real import outcome");
    $expected_calls = in_array($scenario, array('success', 'verification', 'completion', 'summary'), true) ? 1 : 0;
    bank_check(ThemisDB_Payment_Manager::$calls === $expected_calls, "$scenario gates payment verification on persistence");
    if (isset($wpdb->rows['fixture_themisdb_bank_transactions'])) {
        $saved = reset($wpdb->rows['fixture_themisdb_bank_transactions']);
        bank_check($saved['match_status'] === (in_array($scenario, array('success', 'summary'), true) ? 'matched' : 'unmatched'), "$scenario persists truthful transaction status");
    }
    if (isset($wpdb->rows['fixture_themisdb_bank_imports'])) {
        $header = reset($wpdb->rows['fixture_themisdb_bank_imports']);
        $expected_count = $scenario === 'success' || $scenario === 'summary' ? 1 : 0;
        if ($scenario !== 'summary') {
            bank_check($header['rows_matched'] === $expected_count, "$scenario summary counts only completed matches");
            bank_check($header['rows_total'] === ($scenario === 'transaction_insert' ? 0 : 1), "$scenario summary counts saved rows");
        }
    }
    if (is_wp_error($result) && $scenario !== 'header_insert') {
        bank_check($result->get_error_data()['import_id'] === 1, "$scenario exposes partial import ID");
    }
}
foreach (array('success', 'missing', 'write_failure', 'payment_write', 'verification', 'amount', 'currency', 'outgoing', 'duplicate', 'verified', 'failed', 'completion', 'summary', 'conflict') as $scenario) {
    $tx = bank_fixture();
    $tx['match_status'] = $scenario === 'duplicate' ? 'matched' : 'unmatched';
    if ($scenario === 'amount') { $tx['amount'] = 1; }
    if ($scenario === 'currency') { $tx['currency'] = 'USD'; }
    if ($scenario === 'outgoing') { $tx['amount'] = -120; }
    if (in_array($scenario, array('verified', 'failed'), true)) { ThemisDB_Payment_Manager::$payment['payment_status'] = $scenario; }
    $wpdb->fail_completion = $scenario === 'completion';
    $wpdb->summary_read_failure = $scenario === 'summary';
    $wpdb->zero_assignment = $scenario === 'conflict';
    if ($scenario === 'conflict') { $tx['matched_payment_id'] = 999; }
    $wpdb->transaction = $scenario === 'missing' ? null : $tx;
    $wpdb->rows['fixture_themisdb_bank_transactions'][21] = $tx;
    if ($scenario === 'write_failure') { $wpdb->fail = 'fixture_themisdb_bank_transactions'; }
    if ($scenario === 'payment_write') { $wpdb->fail = 'fixture_themisdb_payments'; }
    ThemisDB_Payment_Manager::$result = $scenario !== 'verification';
    $result = ThemisDB_Bank_Import::assign_transaction(21, 5);
    bank_check($scenario === 'success' ? $result === true : is_wp_error($result), "$scenario reports real manual assignment outcome");
    bank_check(ThemisDB_Payment_Manager::$calls === (in_array($scenario, array('success', 'verification', 'completion', 'summary'), true) ? 1 : 0), "$scenario gates manual payment verification");
    if ($scenario === 'verification') {
        bank_check($wpdb->rows['fixture_themisdb_bank_transactions'][21]['match_status'] === 'unmatched', 'Failed verification leaves manual transaction open');
    }
    if ($scenario === 'success') {
        bank_check($wpdb->rows['fixture_themisdb_bank_imports'][1]['rows_matched'] === 1, 'Manual match refreshes persisted import totals');
        bank_check($wpdb->rows['fixture_themisdb_bank_imports'][1]['rows_unmatched'] === 0, 'Manual match removes open summary count');
    }
}
foreach (array('valid', 'cent', 'two_cents', 'currency', 'zero', 'verified', 'failed', 'duplicate', 'current_order', 'legacy_order') as $scenario) {
    $tx = bank_fixture();
    $tx['purpose'] = in_array($scenario, array('current_order', 'legacy_order'), true)
        ? ($scenario === 'current_order' ? 'TDB' : 'ORD') . '-20261029-ABC123' : 'PAY-20261029-ABC123';
    if ($scenario === 'cent') { $tx['amount'] = 120.01; }
    if ($scenario === 'two_cents') { $tx['amount'] = 120.02; }
    if ($scenario === 'currency') { $tx['currency'] = 'USD'; }
    if ($scenario === 'zero') { $tx['amount'] = 0; }
    if (in_array($scenario, array('verified', 'failed'), true)) { ThemisDB_Payment_Manager::$payment['payment_status'] = $scenario; }
    if ($scenario === 'duplicate') { $wpdb->duplicate_count = 1; }
    $matched = ThemisDB_Bank_Import::match_transactions(array($tx))[0];
    $expected = in_array($scenario, array('valid', 'cent', 'current_order', 'legacy_order'), true)
        ? 'matched' : ($scenario === 'duplicate' ? 'duplicate' : 'unmatched');
    bank_check($matched['match_status'] === $expected, "$scenario preview matches safely");
    bank_check(ThemisDB_Payment_Manager::$calls === 0, "$scenario preview never verifies a payment");
    if ($scenario === 'current_order') {
        bank_check(count($wpdb->queries) >= 3, 'Current TDB reference resolves order and payment');
    }
}
$tx = bank_fixture();
$result = ThemisDB_Bank_Import::save_import(array('filename' => 'synthetic.csv', 'bank_format' => 'generic'), array($tx, $tx));
bank_check(is_wp_error($result), 'Second same-batch payment match is reported for review');
bank_check(ThemisDB_Payment_Manager::$calls === 1, 'Same batch cannot verify one payment twice');
$header = reset($wpdb->rows['fixture_themisdb_bank_imports']);
bank_check($header['rows_matched'] === 1 && $header['rows_unmatched'] === 1, 'Same-batch summary preserves completed and open rows');

require dirname(__DIR__) . '/includes/class-admin.php';
$admin = (new ReflectionClass('ThemisDB_Order_Admin'))->newInstanceWithoutConstructor();
foreach (array('bank_import_handle_confirm', 'bank_import_handle_assign') as $handler) {
    $tx = bank_fixture();
    $tx['match_status'] = $handler === 'bank_import_handle_assign' ? 'unmatched' : 'matched';
    $wpdb->transaction = $tx;
    ThemisDB_Payment_Manager::$result = false;
    $bank_preview = array('filename' => 'synthetic.csv', 'bank_format' => 'generic', 'notes' => '', 'transactions' => array($tx));
    $_POST = array('preview_token' => 'synthetic', 'transaction_id' => 21, 'assign_payment_id' => 5, 'import_id' => 1);
    $method = new ReflectionMethod($admin, $handler);
    $method->setAccessible(true);
    try {
        $method->invoke($admin);
        bank_check(false, "$handler must display processing failure");
    } catch (BankAdminFailure $e) {
        bank_check(strpos($e->getMessage(), 'manuell') !== false, "$handler displays actionable error, not success redirect");
    }
}
echo "$checks checks; $failures failures.\n";
exit($failures ? 1 : 0);
