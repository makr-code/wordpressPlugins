<?php
/** Isolated overlay routing regression, without WordPress, database or mail. */
define('ABSPATH', __DIR__ . DIRECTORY_SEPARATOR);
class OverlayResponse extends RuntimeException {}
function add_action($name, $callback, ...$args) {}
function add_shortcode($name, $callback) {}
function sanitize_text_field($value) { return trim($value); }
function sanitize_key($value) { return strtolower($value); }
function wp_unslash($value) { return $value; }
function wp_verify_nonce($value, $action) { return $value === 'synthetic'; }
function wp_create_nonce($action) { return 'synthetic'; }
function nocache_headers() {}
function status_header($code) {}
function home_url($path) { return 'http://example.invalid' . $path; }
function add_query_arg($args, $url) { return $url . '?' . http_build_query($args); }
function esc_html__($value, $domain) { return $value; }
function esc_html_e($value, $domain) { echo $value; }
function esc_attr_e($value, $domain) { echo $value; }
function esc_url($value) { return htmlspecialchars($value); }
function wp_die($message, $title, $args) { throw new OverlayResponse('error:' . $args['response']); }
function language_attributes() { echo 'lang="de"'; }
function bloginfo($name) { echo 'UTF-8'; }
function wp_head() {}
function body_class($class) { echo 'class="' . $class . '"'; }
function wp_print_footer_scripts() { throw new OverlayResponse('rendered'); }
function is_wp_error($value) { return false; }
class ThemisDB_Order_Manager {
    public static $order;
    public static $product;
    public static function get_product_by_edition($edition) { return self::$product; }
    public static function get_order($id) { return self::$order; }
}
class ThemisDB_Order_Shortcodes {
    public static $bootstrap = 0;
    public static $view;
    public static function reset() { self::$bootstrap = 0; self::$view = ''; }
    public function prepare_license_cart($edition) { self::$bootstrap++; return true; }
    public function express_checkout_shortcode($args) {
        if (!empty($args['product'])) { self::$bootstrap++; }
        self::$view = 'checkout';
        return '<div>synthetic checkout</div>';
    }
    public function shopping_cart_shortcode($args) { self::$view = 'cart'; return '<div>synthetic cart</div>'; }
}
require dirname(__DIR__) . '/includes/class-checkout-overlay.php';
$checks = $failures = 0;
$messages = array();
function overlay_check($condition, $message) {
    global $checks, $failures, $messages;
    $checks++;
    $failures += $condition ? 0 : 1;
    $messages[] = ($condition ? 'PASS ' : 'FAIL ') . $message;
}
session_start();
$overlay = new ThemisDB_Checkout_Overlay();
foreach (array('empty', 'cart', 'checkout', 'selection', 'bad_nonce', 'unavailable', 'merchandise', 'save_failure', 'closed') as $scenario) {
    $_GET = array('themisdb_checkout_frame' => 1, '_wpnonce' => $scenario === 'bad_nonce' ? 'expired' : 'synthetic',
        'view' => $scenario === 'checkout' ? 'checkout' : 'cart');
    $_SESSION['themisdb_order_id'] = 2;
    ThemisDB_Order_Manager::$order = array('id' => 2, 'status' => $scenario === 'closed' ? 'active' : 'draft',
        'product_type' => 'database', 'product_edition' => 'enterprise');
    ThemisDB_Order_Manager::$product = array('product_type' => $scenario === 'merchandise' ? 'merchandise' : 'database');
    if ($scenario === 'empty') { unset($_SESSION['themisdb_order_id']); ThemisDB_Order_Manager::$order = null; }
    if (in_array($scenario, array('selection', 'unavailable', 'merchandise', 'save_failure'), true)) {
        $_GET['edition'] = 'enterprise';
        $_GET['training'] = 'IGNORED';
    }
    if ($scenario === 'unavailable') { ThemisDB_Order_Manager::$product = null; }
    if ($scenario === 'save_failure') { ThemisDB_Order_Manager::$order = null; }
    ThemisDB_Order_Shortcodes::reset();
    ob_start();
    try { $overlay->frame(); throw new RuntimeException('Missing frame response'); }
    catch (OverlayResponse $response) { $result = $response->getMessage(); }
    finally { $html = ob_get_clean(); }
    $expected = $scenario === 'bad_nonce' ? 'error:403'
        : (in_array($scenario, array('unavailable', 'merchandise'), true) ? 'error:400'
        : ($scenario === 'save_failure' ? 'error:500' : 'rendered'));
    overlay_check($result === $expected, "$scenario returns expected frame response");
    if ($expected === 'rendered') {
        overlay_check(ThemisDB_Order_Shortcodes::$view === ($scenario === 'checkout' ? 'checkout' : 'cart'), "$scenario reuses correct existing component");
        overlay_check(strpos($html, 'target="_parent"') !== false, "$scenario returns to products in parent page");
    }
    overlay_check(ThemisDB_Order_Shortcodes::$bootstrap === (in_array($scenario, array('selection', 'save_failure'), true) ? 1 : 0), "$scenario never bootstraps a draft without valid explicit selection");
    if ($scenario === 'selection') {
        overlay_check(!isset($_GET['training']), 'License-only selection excludes training');
    }
    if ($scenario === 'closed') {
        overlay_check(!isset($_SESSION['themisdb_order_id']), 'Closed orders are detached without modifying business records');
    }
}
session_destroy();
echo implode(PHP_EOL, $messages), PHP_EOL, "$checks checks; $failures failures.\n";
exit($failures ? 1 : 0);
