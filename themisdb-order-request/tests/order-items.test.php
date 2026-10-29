<?php
/** Isolated selection/line-item regression using the actual catalog column names. */
define('ABSPATH', __DIR__ . DIRECTORY_SEPARATOR);
define('ARRAY_A', 'ARRAY_A');
function sanitize_text_field($value) { return trim((string) $value); }
function sanitize_email($value) { return trim($value); }
function wp_json_encode($value) { return json_encode($value); }
class SelectionDatabaseDouble {
    public $prefix = 'fixture_';
    public $items = array();
    public $modules = array('MOD-DEMO');
    public function prepare($sql, ...$args) { return $sql; }
    public function get_row($sql, $format = null) {
        if (strpos($sql, 'themisdb_orders') !== false) {
            return array('product_edition' => 'demo', 'modules' => json_encode($this->modules),
                'training_modules' => '["TRAIN-DEMO"]', 'currency' => 'EUR');
        }
        $product = array('id' => 1, 'product_code' => 'PRODUCT-DEMO', 'product_name' => 'Demo product', 'price' => '129.95');
        return $format === ARRAY_A ? $product : (object) $product;
    }
    public function get_results($sql, $format) {
        if (strpos($sql, 'themisdb_training_modules') !== false) {
            return array(array('id' => 3, 'training_code' => 'TRAIN-DEMO', 'training_name' => 'Demo training', 'price' => '65.75'));
        }
        return array(array('id' => 2, 'module_code' => 'MOD-DEMO', 'module_name' => 'Demo module', 'price' => '24.30'));
    }
    public function get_var($sql) {
        if (strpos($sql, 'themisdb_training_modules') !== false) { return 65.75; }
        return 24.30;
    }
    public function delete($table, $where, $format) { $this->items = array(); return 1; }
    public function insert($table, $data) { $this->items[] = $data; return 1; }
}
require dirname(__DIR__) . '/includes/class-order-manager.php';
$wpdb = new SelectionDatabaseDouble();
$checks = $failures = 0;
function selection_check($condition, $message) {
    global $checks, $failures;
    $checks++;
    $failures += $condition ? 0 : 1;
    echo ($condition ? 'PASS ' : 'FAIL ') . $message . PHP_EOL;
}
$method = new ReflectionMethod('ThemisDB_Order_Manager', 'sync_order_items_from_selection');
$method->setAccessible(true);
selection_check($method->invoke(null, 1) === true, 'Selection sync succeeds');
selection_check(count($wpdb->items) === 3, 'Product, module and training are retained');
selection_check(array_column($wpdb->items, 'sku') === array('PRODUCT-DEMO', 'MOD-DEMO', 'TRAIN-DEMO'), 'Catalog codes become line-item SKUs');
selection_check(array_column($wpdb->items, 'unit_price') === array(129.95, 24.30, 65.75), 'Canonical catalog prices are preserved');
$total = ThemisDB_Order_Manager::calculate_total('demo', array('MOD-DEMO'), array('TRAIN-DEMO'));
selection_check(round(array_sum(array_column($wpdb->items, 'total_price')), 2) === round($total, 2), 'Cart calculation and persisted line items agree');
selection_check(round($total, 2) === 220.0, 'Exact expected total is 220 EUR');
$wpdb->modules = array();
selection_check($method->invoke(null, 1) === true, 'Changed selection sync succeeds');
selection_check(array_column($wpdb->items, 'sku') === array('PRODUCT-DEMO', 'TRAIN-DEMO'), 'Removing a module removes its persisted line item');
selection_check(round(array_sum(array_column($wpdb->items, 'total_price')), 2) === 195.70, 'Removed module is not charged');
echo "$checks checks; $failures failures.\n";
exit($failures ? 1 : 0);
