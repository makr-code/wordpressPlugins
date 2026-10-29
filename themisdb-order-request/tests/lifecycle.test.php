<?php
/**
 * Isolated lifecycle tests: no WordPress bootstrap, database, mail or network.
 */
define('ABSPATH', __DIR__ . DIRECTORY_SEPARATOR);
define('ARRAY_A', 'ARRAY_A');
define('DAY_IN_SECONDS', 86400);

class WP_Error {
    public $code;
    public $message;
    public function __construct($code, $message = '') { $this->code = $code; $this->message = $message; }
    public function get_error_code() { return $this->code; }
    public function get_error_message() { return $this->message; }
}
function __($message, $domain = '') { return $message; }
function sanitize_textarea_field($value) { return trim(strip_tags($value)); }
function sanitize_key($value) { return $value; }
function sanitize_text_field($value) { return trim(strip_tags($value)); }
function current_time($format) { return '2026-10-29 17:00:00'; }
function get_current_user_id() { return 7; }
function wp_json_encode($value) { return json_encode($value); }
function is_wp_error($value) { return $value instanceof WP_Error; }
function is_email($value) { return filter_var($value, FILTER_VALIDATE_EMAIL); }
function do_action($name, ...$args) { $GLOBALS['events'][] = $name; }
function apply_filters($name, $value, ...$args) { return $value; }

class ThemisDB_License_Manager {
    public static $license = array('id' => 11);
    public static $cancel_result = true;
    public static $cancel_calls = 0;
    public static function get_license($id) { return self::$license; }
    public static function cancel_license($id, $reason, $actor) {
        self::$cancel_calls++;
        if (!self::$license || self::$license['license_status'] === 'cancelled') {
            return false;
        }
        if (self::$cancel_result) {
            self::$license['license_status'] = 'cancelled';
        }
        return self::$cancel_result;
    }
}

class LifecycleDatabaseDouble {
    public $prefix = 'fixture_';
    public $users = 'fixture_users';
    public $request;
    public $update_result = 1;
    public $logs = array();
    public $where;
    public $prepared = array();
    public $notification_lookups = 0;
    public $license_update_result = 1;
    public $license_updates = array();
    public $rows = array();
    public $last_error = '';
    public $query_failure = false;

    public function __construct($status) {
        $this->request = array(
            'id' => 1, 'license_id' => 11, 'status' => $status,
            'request_type' => 'termination', 'effective_at' => '2026-11-30 12:00:00',
            'payload' => '{}',
        );
    }
    public function prepare($sql, ...$args) {
        $this->prepared = $args;
        return $sql;
    }
    public function get_row($sql, $format) { return $this->request; }
    public function get_results($sql, $format) {
        $this->last_error = $this->query_failure ? 'Synthetic database failure' : '';
        return $this->query_failure ? null : array_values(array_filter($this->rows, function ($row) {
            return $this->request['status'] === 'confirmed' && empty($this->request['executed_at']);
        }));
    }
    public function get_var($sql) {
        if (strpos($sql, 'COUNT(*)') !== false) {
            return in_array('pending_finance', $this->prepared, true) ? 1 : 0;
        }
        $this->notification_lookups++;
        return '';
    }
    public function update($table, $data, $where, $formats, $where_formats) {
        if ($table === $this->prefix . 'themisdb_licenses') {
            $this->license_updates[] = $data;
            return $this->license_update_result;
        }
        $this->where = $where;
        if ($this->update_result === 1) {
            $this->request = array_merge($this->request, $data);
        }
        return $this->update_result;
    }
    public function insert($table, $data, $formats) {
        if ($this->request['status'] === 'requested') {
            throw new RuntimeException('Audit logged before review was persisted');
        }
        $this->logs[] = $data;
        return 1;
    }
}

require dirname(__DIR__) . '/includes/class-contract-lifecycle.php';
set_error_handler(static function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$checks = $failures = 0;
function check_lifecycle($condition, $message) {
    global $checks, $failures;
    $checks++;
    $failures += $condition ? 0 : 1;
    echo ($condition ? 'PASS ' : 'FAIL ') . $message . PHP_EOL;
}

foreach (array(
    array('review_request', 'requested', 'confirmed', 'rejected'),
    array('review_request_ops', 'requested', 'pending_finance', 'rejected'),
    array('review_request_finance', 'pending_finance', 'confirmed', 'rejected'),
) as $stage) {
    foreach (array('termination', 'change') as $type) {
    foreach (array(true, false) as $approve) {
        foreach (array(false, 0, 1) as $outcome) {
            $wpdb = new LifecycleDatabaseDouble($stage[1]);
            $wpdb->request['request_type'] = $type;
            $wpdb->update_result = $outcome;
            $events = array();
            $result = ThemisDB_Contract_Lifecycle::{$stage[0]}(1, $approve, 'Fixture review', 7);
            $label = $type . ' ' . $stage[0] . ($approve ? ' approve' : ' reject') . ' update=' . var_export($outcome, true);
            if ($outcome === 1) {
                check_lifecycle($result === true && $wpdb->request['status'] === $stage[$approve ? 2 : 3], $label . ' persists transition');
                check_lifecycle(count($wpdb->logs) === 1, $label . ' logs persisted transition');
            } else {
                $expected_code = $outcome === false ? 'review_update_failed' : 'review_conflict';
                check_lifecycle(is_wp_error($result) && $result->get_error_code() === $expected_code, $label . ' returns explicit error');
                check_lifecycle(!$events && !$wpdb->logs && !$wpdb->notification_lookups && $wpdb->request['status'] === $stage[1], $label . ' has no success side effects');
            }
            check_lifecycle(isset($wpdb->where['status']) && $wpdb->where['status'] === $stage[1], $label . ' guards expected state');
        }
        }
        $wpdb = new LifecycleDatabaseDouble('executed');
        $events = array();
        $result = ThemisDB_Contract_Lifecycle::{$stage[0]}(1, true);
        check_lifecycle(is_wp_error($result) && !$events && !$wpdb->where && !$wpdb->logs, $stage[0] . ' refuses already processed requests');
    }
}
$wpdb = new LifecycleDatabaseDouble('pending_finance');
$method = new ReflectionMethod('ThemisDB_Contract_Lifecycle', 'check_termination_preconditions');
$method->setAccessible(true);
$result = $method->invoke(null, 11, array());
check_lifecycle(is_wp_error($result) && $result->get_error_code() === 'duplicate_termination', 'Pending finance termination blocks duplicate requests');

foreach (array('review_request' => 'requested', 'review_request_finance' => 'pending_finance') as $review => $status) {
    foreach (array('database_failure', 'missing_license', 'invalid_json', 'invalid_field', 'unchanged', 'updated') as $scenario) {
        $wpdb = new LifecycleDatabaseDouble($status);
        $wpdb->request['request_type'] = 'change';
        $wpdb->request['payload'] = $scenario === 'invalid_json' ? '{broken' : '{"max_nodes":4,"impact_analysis":{"risk_level":"low"}}';
        if ($scenario === 'invalid_field') {
            $wpdb->request['payload'] = '{"product_edition":["enterprise"],"max_nodes":4}';
        }
        $wpdb->license_update_result = $scenario === 'database_failure' ? false : ($scenario === 'unchanged' ? 0 : 1);
        ThemisDB_License_Manager::$license = $scenario === 'missing_license' ? null : array('id' => 11);
        $events = array();
        $result = ThemisDB_Contract_Lifecycle::$review(1, true, 'Apply fixture', 7);
        $success = in_array($scenario, array('unchanged', 'updated'), true);
        check_lifecycle($success ? $result === true : is_wp_error($result), "$review $scenario reports execution outcome");
        check_lifecycle(
            in_array('contract.change.approved', $events, true) === $success &&
            in_array('contract.change.executed', $events, true) === $success,
            "$review $scenario emits success only after application"
        );
        if (!$success) {
            check_lifecycle($wpdb->notification_lookups === 0, "$review $scenario does not notify customer of success");
            check_lifecycle(in_array('execute_failed', array_column($wpdb->logs, 'event'), true), "$review $scenario records execution failure");
            $codes = array(
                'database_failure' => 'change_update_failed', 'missing_license' => 'license_not_found',
                'invalid_json' => 'change_payload_invalid', 'invalid_field' => 'change_payload_invalid',
            );
            check_lifecycle(is_wp_error($result) && $result->get_error_code() === $codes[$scenario], "$review $scenario exposes specific error");
            check_lifecycle($wpdb->request['status'] === 'confirmed', "$review $scenario preserves the saved review");
            if ($scenario !== 'database_failure') {
                check_lifecycle(!$wpdb->license_updates, "$review $scenario never updates license");
            }
            $update_count = count($wpdb->license_updates);
            $repeated = ThemisDB_Contract_Lifecycle::$review(1, true, 'Repeated fixture', 7);
            check_lifecycle(is_wp_error($repeated) && count($wpdb->license_updates) === $update_count, "$review $scenario does not silently reapply on repeated approval");
        } else {
            check_lifecycle($wpdb->license_updates === array(array('max_nodes' => 4)), "$review $scenario applies only supported fields");
        }
    }
}

foreach (array('cancel_failed', 'save_failed', 'save_conflict', 'success', 'already_cancelled', 'missing_license') as $scenario) {
    $wpdb = new LifecycleDatabaseDouble('confirmed');
    $wpdb->rows = array($wpdb->request);
    $wpdb->update_result = $scenario === 'save_failed' ? false : ($scenario === 'save_conflict' ? 0 : 1);
    ThemisDB_License_Manager::$license = $scenario === 'missing_license' ? null : array(
        'id' => 11, 'license_status' => $scenario === 'already_cancelled' ? 'cancelled' : 'active',
    );
    ThemisDB_License_Manager::$cancel_result = $scenario !== 'cancel_failed';
    ThemisDB_License_Manager::$cancel_calls = 0;
    $events = array();
    $result = ThemisDB_Contract_Lifecycle::execute_due_terminations();
    $success = in_array($scenario, array('success', 'already_cancelled'), true);
    check_lifecycle($result === array('processed' => 1, 'executed' => $success ? 1 : 0, 'failed' => $success ? 0 : 1), "Scheduler $scenario counts persisted outcomes");
    check_lifecycle(in_array('contract.termination.executed', $events, true) === $success, "Scheduler $scenario emits success only after persistence");
    check_lifecycle(in_array($success ? 'executed' : 'execute_failed', array_column($wpdb->logs, 'event'), true), "Scheduler $scenario audits outcome");
    if (!$success) {
        check_lifecycle(!$wpdb->notification_lookups, "Scheduler $scenario avoids execution notification");
    }
    if (in_array($scenario, array('already_cancelled', 'missing_license'), true)) {
        check_lifecycle(ThemisDB_License_Manager::$cancel_calls === 0, "Scheduler $scenario avoids redundant cancellation");
    }
    if ($scenario === 'save_failed') {
        $wpdb->update_result = 1;
        $retried = ThemisDB_Contract_Lifecycle::execute_due_terminations();
        check_lifecycle($retried['executed'] === 1 && ThemisDB_License_Manager::$cancel_calls === 1, 'Scheduler recovers failed persistence without recancelling license');
    }
    if ($success || $scenario === 'save_failed' || $scenario === 'save_conflict') {
        check_lifecycle(($wpdb->where['status'] ?? '') === 'confirmed', "Scheduler $scenario guards final state transition");
    }
    if ($success || $scenario === 'save_failed') {
        $event_count = count($events);
        $repeated = ThemisDB_Contract_Lifecycle::execute_due_terminations();
        check_lifecycle($repeated === array('processed' => 0, 'executed' => 0, 'failed' => 0) && count($events) === $event_count, "Scheduler $scenario does not process completed request again");
    }
}

$wpdb = new LifecycleDatabaseDouble('confirmed');
$events = array();
$empty = ThemisDB_Contract_Lifecycle::execute_due_terminations();
check_lifecycle($empty === array('processed' => 0, 'executed' => 0, 'failed' => 0), 'Scheduler empty queue returns zero counts');
$wpdb->query_failure = true;
$failed_query = ThemisDB_Contract_Lifecycle::execute_due_terminations();
check_lifecycle(is_wp_error($failed_query) && $failed_query->get_error_code() === 'termination_query_failed', 'Scheduler query failure returns explicit error instead of empty success');
check_lifecycle(!$events && !$wpdb->logs && !$wpdb->where, 'Scheduler query failure has no execution side effects');
require dirname(__DIR__, 2) . '/themisdb-support-portal/includes/class-contract-termination-engine.php';
$wrapped = ThemisDB_Contract_Termination_Engine::run_scheduler_now();
check_lifecycle(is_wp_error($wrapped) && $wrapped->get_error_code() === 'termination_query_failed', 'Support scheduler wrapper propagates load errors');

echo "$checks checks; $failures failures.\n";
exit($failures ? 1 : 0);
