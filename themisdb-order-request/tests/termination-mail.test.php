<?php
/**
 * Isolated notification tests with synthetic files and a mail double.
 */
define('ABSPATH', __DIR__ . DIRECTORY_SEPARATOR);
define('ARRAY_A', 'ARRAY_A');
function __($value, $domain = '') { return $value; }
function esc_html__($value, $domain = '') { return esc_html($value); }
function esc_html($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function esc_url($value) { return $value; }
function sanitize_text_field($value) { return strip_tags((string) $value); }
function sanitize_textarea_field($value) { return strip_tags((string) $value); }
function sanitize_key($value) { return $value; }
function sanitize_email($value) { return $value; }
function is_email($value) { return filter_var($value, FILTER_VALIDATE_EMAIL); }
function wp_json_encode($value) { return json_encode($value); }
function get_option($name, $default = '') { return $name === 'date_format' ? 'Y-m-d' : 'Synthetic site'; }
function home_url($path) { return 'https://example.invalid' . $path; }
function current_time($format) { return $format === 'timestamp' ? 1793293200 : '2026-10-29 17:00:00'; }
function date_i18n($format, $time) { return gmdate($format, $time); }
function wp_upload_dir() { return array('basedir' => $GLOBALS['fixture_dir']); }
function get_temp_dir() { return ($GLOBALS['unavailable_temp'] ?? false) ? $GLOBALS['fixture_dir'] . DIRECTORY_SEPARATOR . 'not-created' . DIRECTORY_SEPARATOR : $GLOBALS['fixture_dir'] . DIRECTORY_SEPARATOR; }
function wp_generate_uuid4() { return bin2hex(random_bytes(16)); }
function add_filter($name, $callback) { $GLOBALS['filters'][$name] = $callback; }
function remove_filter($name, $callback) { unset($GLOBALS['filters'][$name]); }
function wp_mail($to, $subject, $body, $headers = array(), $attachments = array()) {
    $GLOBALS['mail'] = compact('to', 'subject', 'body', 'attachments');
    foreach ($attachments as $file) {
        if (!is_file($file) || filesize($file) === 0) {
            throw new RuntimeException('Missing synthetic attachment');
        }
        $GLOBALS['attachment_content'] = file_get_contents($file);
    }
    if ($GLOBALS['mail_mode'] === 'throw') {
        throw new RuntimeException('Synthetic transport exception');
    }
    return $GLOBALS['mail_mode'] !== 'false';
}
class ThemisDB_License_Manager {
    public static function get_license($id) { return array('license_name' => '<fixture>', 'customer_name' => 'Synthetic Customer'); }
}
class NotificationDatabaseDouble {
    public $prefix = 'fixture_';
    public $users = 'fixture_users';
    public $logs = array();
    public function prepare($sql, ...$args) { return $sql; }
    public function get_row($sql, $format) {
        return array('id' => 1, 'license_id' => 11, 'executed_at' => '2026-10-29 17:00:00', 'reason' => 'Synthetic & reason');
    }
    public function get_var($sql) { return 'synthetic@example.invalid'; }
    public function insert($table, $data, $formats) { $this->logs[] = $data; return 1; }
}
require dirname(__DIR__) . '/includes/class-contract-lifecycle.php';
$fixture_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'themisdb-mail-test-' . bin2hex(random_bytes(8));
mkdir($fixture_dir);
$checks = $failures = 0;
function check_mail($condition, $message) {
    global $checks, $failures;
    $checks++;
    $failures += $condition ? 0 : 1;
    echo ($condition ? 'PASS ' : 'FAIL ') . $message . PHP_EOL;
}
$method = new ReflectionMethod('ThemisDB_Contract_Lifecycle', 'notify_customer_termination_executed');
$method->setAccessible(true);
try {
    foreach (array('success', 'false', 'throw') as $mail_mode) {
        $wpdb = new NotificationDatabaseDouble();
        $filters = $mail = array();
        $caught = null;
        try {
            $method->invoke(null, 1);
        } catch (RuntimeException $error) {
            $caught = $error;
        }
        check_mail($mail['to'] === 'synthetic@example.invalid', "$mail_mode uses synthetic customer recipient");
        check_mail(count($mail['attachments']) === 1 && strpos($attachment_content, 'Synthetic &amp; reason') !== false, "$mail_mode attaches escaped printable HTML without TCPDF");
        check_mail(!glob($fixture_dir . DIRECTORY_SEPARATOR . '*'), "$mail_mode removes temporary document");
        check_mail(!$filters, "$mail_mode restores content-type filter");
        check_mail(($caught !== null) === ($mail_mode === 'throw'), "$mail_mode preserves unexpected transport exceptions");
        if ($mail_mode !== 'success') {
            check_mail(in_array('notification_failed', array_column($wpdb->logs, 'event'), true), "$mail_mode audits notification failure");
        }
        foreach (glob($fixture_dir . DIRECTORY_SEPARATOR . '*') as $file) {
            unlink($file);
        }
    }
    $generate = new ReflectionMethod('ThemisDB_Contract_Lifecycle', 'generate_termination_pdf');
    $generate->setAccessible(true);
    $request = $wpdb->get_row('', ARRAY_A);
    $first = $generate->invoke(null, $request);
    $second = $generate->invoke(null, $request);
    check_mail($first !== $second && is_file($first) && is_file($second), 'Overlapping HTML generation uses independent files');
    if ($first) { unlink($first); }
    if ($second && $first !== $second) { unlink($second); }
    if (!class_exists('TCPDF')) {
        class TCPDF {
            public static $mode = 'missing';
            public function __construct(...$args) {}
            public function SetMargins(...$args) {}
            public function SetAutoPageBreak(...$args) {}
            public function AddPage() {}
            public function SetFont(...$args) {}
            public function writeHTML(...$args) {}
            public function Output($path, $mode) {
                if (self::$mode === 'throw') {
                    if ($mode === 'F') { file_put_contents($path, 'Partial synthetic PDF'); }
                    throw new RuntimeException('Synthetic renderer failure');
                }
                if (self::$mode === 'success') {
                    if ($mode === 'F') { file_put_contents($path, '%PDF-synthetic'); }
                    return '%PDF-synthetic';
                }
                return '';
            }
        }
    }
    $wpdb = new NotificationDatabaseDouble();
    $filters = $mail = array();
    $mail_mode = 'success';
    $method->invoke(null, 1);
    check_mail(!$mail['attachments'], 'Missing PDF is not attached as a nonexistent file');
    check_mail(in_array('document_failed', array_column($wpdb->logs, 'event'), true), 'Missing PDF is explicitly audited');
    check_mail(!in_array('notification_failed', array_column($wpdb->logs, 'event'), true), 'Successful mail handoff without document is not reported as transport failure');
    check_mail(!$filters && !glob($fixture_dir . DIRECTORY_SEPARATOR . '*'), 'Missing PDF leaves no document or content-type filter');
    TCPDF::$mode = 'success';
    $first = $generate->invoke(null, $request);
    $second = $generate->invoke(null, $request);
    check_mail($first !== $second && is_file($first) && is_file($second), 'Overlapping PDF generation uses independent files');
    check_mail($first && file_get_contents($first) === '%PDF-synthetic' && pathinfo($first, PATHINFO_EXTENSION) === 'pdf', 'PDF bytes and extension are preserved');
    if ($first) { unlink($first); }
    if ($second && $first !== $second) { unlink($second); }
    TCPDF::$mode = 'throw';
    $fallback = $generate->invoke(null, $request);
    check_mail($fallback && pathinfo($fallback, PATHINFO_EXTENSION) === 'html', 'Renderer exception retains explicit printable HTML fallback');
    check_mail(count(glob($fixture_dir . DIRECTORY_SEPARATOR . '*')) === 1, 'Renderer failure leaves no partial PDF beside fallback');
    if ($fallback) { unlink($fallback); }
    TCPDF::$mode = 'success';
    $unavailable_temp = true;
    $warnings = array();
    set_error_handler(static function ($severity, $message) use (&$warnings) {
        $warnings[] = $message;
        return true;
    });
    try {
        $wpdb = new NotificationDatabaseDouble();
        $mail = $filters = array();
        $method->invoke(null, 1);
    } finally {
        restore_error_handler();
        $unavailable_temp = false;
    }
    check_mail(count($warnings) > 0 && !$mail['attachments'], 'Unavailable temporary directory does not create an attachment');
    check_mail(in_array('document_failed', array_column($wpdb->logs, 'event'), true), 'Temporary file creation failure is audited');
    check_mail(!$filters && !glob($fixture_dir . DIRECTORY_SEPARATOR . '*'), 'Temporary file failure leaves no files or mail filter');
} finally {
    foreach (glob($fixture_dir . DIRECTORY_SEPARATOR . '*') as $file) {
        unlink($file);
    }
    rmdir($fixture_dir);
}
echo "$checks checks; $failures failures.\n";
exit($failures ? 1 : 0);
