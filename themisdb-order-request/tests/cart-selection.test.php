<?php
/** Execute real license-cart preparation with synthetic persistence only. */
define('ABSPATH', __DIR__ . DIRECTORY_SEPARATOR);
function sanitize_key($value) { return strtolower($value); }
function sanitize_text_field($value) { return trim($value); }
function wp_unslash($value) { return $value; }
function __($value, $domain) { return $value; }
function is_wp_error($value) { return $value instanceof WP_Error; }
class WP_Error {
    public $code;
    public function __construct($code, $message) { $this->code = $code; }
}
class ThemisDB_Order_Manager {
    public static $create = true, $save = true, $existing = null, $saved, $modules = array();
    public static function get_product_by_edition($edition) {
        return $edition === 'missing' ? null : array('product_type' => $edition === 'shirt' ? 'merchandise' : 'database');
    }
    public static function get_modules() { return self::$modules; }
    public static function get_order($id) { return self::$existing; }
    public static function create_order($data) { return self::$create ? 2 : false; }
    public static function calculate_total($edition, $modules, $training) { return 100 + count($modules) * 20; }
    public static function update_order($id, $data) { self::$saved = $data; return self::$save; }
}
require dirname(__DIR__) . '/includes/class-shortcodes.php';
$shortcodes = (new ReflectionClass('ThemisDB_Order_Shortcodes'))->newInstanceWithoutConstructor();
$checks = $failures = 0;
session_start();
$messages = array();
foreach (array('normal', 'empty_modules', 'create_failure', 'save_failure', 'existing_save_failure', 'invalid', 'merchandise') as $case) {
    $_SESSION = array();
    $_GET = array('modules' => 'module-a,unknown,module-a', 'training' => 'ignored');
    ThemisDB_Order_Manager::$modules = $case === 'empty_modules' ? array() : array(array('module_code' => 'module-a'));
    ThemisDB_Order_Manager::$create = $case !== 'create_failure';
    ThemisDB_Order_Manager::$save = !in_array($case, array('save_failure', 'existing_save_failure'), true);
    ThemisDB_Order_Manager::$existing = null;
    ThemisDB_Order_Manager::$saved = null;
    if ($case === 'existing_save_failure') {
        $_SESSION['themisdb_order_id'] = 2;
        ThemisDB_Order_Manager::$existing = array('id' => 2, 'status' => 'draft', 'product_edition' => 'enterprise');
    }
    $result = $shortcodes->prepare_license_cart($case === 'invalid' ? 'missing' : ($case === 'merchandise' ? 'shirt' : 'enterprise'));
    $success = in_array($case, array('normal', 'empty_modules'), true);
    $conditions = array($success ? $result === true : is_wp_error($result));
    if ($success) {
        $conditions[] = ThemisDB_Order_Manager::$saved['modules'] === ($case === 'empty_modules' ? array() : array('module-a'));
        $conditions[] = ThemisDB_Order_Manager::$saved['training_modules'] === array();
        $conditions[] = ThemisDB_Order_Manager::$saved['step'] === 4;
        $conditions[] = ThemisDB_Order_Manager::$saved['total_amount'] === ($case === 'empty_modules' ? 100 : 120);
        $conditions[] = $_SESSION['themisdb_order_id'] === 2;
    }
    foreach ($conditions as $index => $condition) {
        $checks++;
        $failures += $condition ? 0 : 1;
        $messages[] = ($condition ? 'PASS ' : 'FAIL ') . "$case condition $index";
    }
}
session_destroy();
echo implode(PHP_EOL, $messages), PHP_EOL, "$checks checks; $failures failures.\n";
exit($failures ? 1 : 0);
