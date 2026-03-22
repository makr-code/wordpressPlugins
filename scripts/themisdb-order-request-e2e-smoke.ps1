param(
    [Parameter(Mandatory = $true)]
    [string]$WpPath,

    [string]$PluginSlug = 'themisdb-order-request',

    [string]$WooPluginSlug = 'woocommerce',

    [switch]$CheckWooBridge
)

$ErrorActionPreference = 'Stop'

function Write-Ok($msg) { Write-Host "[OK] $msg" -ForegroundColor Green }
function Write-WarnLine($msg) { Write-Host "[WARN] $msg" -ForegroundColor Yellow }
function Write-Fail($msg) { Write-Host "[FAIL] $msg" -ForegroundColor Red }

if (-not (Test-Path $WpPath)) {
    Write-Fail "WordPress path not found: $WpPath"
    exit 1
}

$wpCmd = Get-Command wp -ErrorAction SilentlyContinue
if (-not $wpCmd) {
    Write-Fail "wp command not found. Install WP-CLI first."
    exit 1
}

Write-Host "Running smoke checks for $PluginSlug in $WpPath..."

try {
    wp --path="$WpPath" core is-installed | Out-Null
    Write-Ok "WordPress installation detected"
} catch {
    Write-Fail "WordPress is not installed or wp --path is invalid"
    exit 1
}

try {
    wp --path="$WpPath" plugin is-active $PluginSlug | Out-Null
    Write-Ok "Plugin is active: $PluginSlug"
} catch {
    Write-Fail "Plugin is not active: $PluginSlug"
    exit 1
}

$checks = @(
    "echo class_exists('ThemisDB_Order_Manager') ? '1' : '0';",
    "echo class_exists('ThemisDB_Contract_Manager') ? '1' : '0';",
    "echo class_exists('ThemisDB_Payment_Manager') ? '1' : '0';",
    "echo class_exists('ThemisDB_License_Manager') ? '1' : '0';",
    "echo class_exists('ThemisDB_WooCommerce_Bridge') ? '1' : '0';",
    "echo shortcode_exists('themisdb_shop') ? '1' : '0';"
)

$labels = @(
    'Order manager class loaded',
    'Contract manager class loaded',
    'Payment manager class loaded',
    'License manager class loaded',
    'Woo bridge class loaded',
    'Dynamic shop shortcode registered'
)

for ($i = 0; $i -lt $checks.Count; $i++) {
    $result = wp --path="$WpPath" eval $checks[$i]
    if ($result -eq '1') {
        Write-Ok $labels[$i]
    } else {
        Write-Fail $labels[$i]
        exit 1
    }
}

$orderPresetCheck = @'
if (!session_id()) {
    session_start();
}

unset($_SESSION['themisdb_order_id']);

$products = ThemisDB_Order_Manager::get_products();
$modules = ThemisDB_Order_Manager::get_modules();
$trainings = ThemisDB_Order_Manager::get_training_modules();

if (empty($products)) {
    echo 'NO_PRODUCTS';
    return;
}

$edition = sanitize_key($products[0]['edition'] ?? '');
$moduleCode = !empty($modules) ? sanitize_text_field($modules[0]['module_code']) : '';
$trainingCode = !empty($trainings) ? sanitize_text_field($trainings[0]['training_code']) : '';

$_GET['edition'] = $edition;
if ($moduleCode !== '') {
    $_GET['modules'] = $moduleCode;
}
if ($trainingCode !== '') {
    $_GET['training'] = $trainingCode;
}
$_GET['checkout'] = '1';

$html = do_shortcode('[themisdb_order_flow]');
$orderId = isset($_SESSION['themisdb_order_id']) ? intval($_SESSION['themisdb_order_id']) : 0;
$order = $orderId > 0 ? ThemisDB_Order_Manager::get_order($orderId) : null;

$modulesOk = ($moduleCode === '') || (!empty($order['modules']) && in_array($moduleCode, (array) $order['modules'], true));
$trainingOk = ($trainingCode === '') || (!empty($order['training_modules']) && in_array($trainingCode, (array) $order['training_modules'], true));
$editionOk = !empty($order) && ($order['product_edition'] ?? '') === $edition;
$stepOk = !empty($order) && intval($order['step'] ?? 0) === 4;
$markupOk = strpos($html, 'data-step="4"') !== false;

echo ($editionOk && $modulesOk && $trainingOk && $stepOk && $markupOk) ? '1' : '0';
'@
$orderPresetResult = wp --path="$WpPath" eval $orderPresetCheck
if ($orderPresetResult -eq '1') {
    Write-Ok "Order flow applies edition/module/training checkout presets"
} elseif ($orderPresetResult -eq 'NO_PRODUCTS') {
    Write-WarnLine "Order flow preset smoke skipped because no active products are available"
} else {
    Write-Fail "Order flow preset smoke failed"
    exit 1
}

$shopRenderCheck = wp --path="$WpPath" eval "`$html = do_shortcode('[themisdb_shop preferred_edition=\"enterprise\"]'); echo ((strpos(`$html, 'themisdb-shop-page') !== false) && (strpos(`$html, '#products') !== false) && (strpos(`$html, 'ThemisDB Shop') !== false)) ? '1' : '0';"
if ($shopRenderCheck -eq '1') {
    Write-Ok "Dynamic shop shortcode renders base markup"
} else {
    Write-Fail "Dynamic shop shortcode did not render the expected base markup"
    exit 1
}

$preferredEditionCheck = @'
$products = ThemisDB_Order_Manager::get_products();
if (empty($products)) {
    echo 'NO_PRODUCTS';
    return;
}

$preferred = sanitize_key($products[0]['edition'] ?? '');
if ($preferred === '') {
    echo 'NO_EDITION';
    return;
}

$html = do_shortcode('[themisdb_shop preferred_edition="' . $preferred . '"]');
$orderNeedle = 'product=' . rawurlencode($preferred);
$configNeedle = 'edition=' . rawurlencode($preferred);

$hasOrderLink = strpos($html, $orderNeedle) !== false;
$hasConfigLink = strpos($html, $configNeedle) !== false;

echo ($hasOrderLink && $hasConfigLink) ? '1' : '0';
'@

$preferredEditionResult = wp --path="$WpPath" eval $preferredEditionCheck
if ($preferredEditionResult -eq '1') {
    Write-Ok "Shop preferred_edition propagates into order and configurator links"
} elseif ($preferredEditionResult -eq 'NO_PRODUCTS' -or $preferredEditionResult -eq 'NO_EDITION') {
    Write-WarnLine "Shop preferred_edition smoke skipped because no usable product edition is available"
} else {
    Write-Fail "Shop preferred_edition smoke failed"
    exit 1
}

$guardrailPaymentCheck = @'
$orderId = ThemisDB_Order_Manager::create_order(array(
    'customer_email' => 'smoke-payment@example.com',
    'customer_name' => 'Smoke Payment',
    'product_edition' => 'community',
    'total_amount' => 10.00,
    'currency' => 'EUR',
    'legal_terms_accepted' => 1,
    'legal_privacy_accepted' => 1,
));

if (!$orderId) {
    echo 'SETUP_FAILED';
    return;
}

$paymentId = ThemisDB_Payment_Manager::create_payment(array(
    'order_id' => intval($orderId),
    'amount' => 10.00,
    'currency' => 'EUR',
    'payment_method' => 'bank_transfer',
));

if (!$paymentId) {
    echo 'SETUP_FAILED';
    return;
}

$first = ThemisDB_Payment_Manager::update_payment($paymentId, array('payment_status' => 'verified'));
$second = ThemisDB_Payment_Manager::update_payment($paymentId, array('payment_status' => 'pending'));

echo ($first === true && $second === false) ? '1' : '0';
'@

$guardrailPaymentResult = wp --path="$WpPath" eval $guardrailPaymentCheck
if ($guardrailPaymentResult -eq '1') {
    Write-Ok "Payment status guard blocks invalid transition from verified to pending"
} elseif ($guardrailPaymentResult -eq 'SETUP_FAILED') {
    Write-WarnLine "Payment guardrail smoke skipped because setup data could not be created"
} else {
    Write-Fail "Payment status guardrail smoke failed"
    exit 1
}

$guardrailContractCheck = @'
$orderId = ThemisDB_Order_Manager::create_order(array(
    'customer_email' => 'smoke-contract@example.com',
    'customer_name' => 'Smoke Contract',
    'product_edition' => 'community',
    'total_amount' => 10.00,
    'currency' => 'EUR',
    'legal_terms_accepted' => 1,
    'legal_privacy_accepted' => 1,
));

if (!$orderId) {
    echo 'SETUP_FAILED';
    return;
}

$contractId = ThemisDB_Contract_Manager::create_contract(array(
    'order_id' => intval($orderId),
    'customer_id' => get_current_user_id() ?: 1,
    'contract_type' => 'license',
    'contract_data' => array('source' => 'smoke'),
));

if (!$contractId) {
    echo 'SETUP_FAILED';
    return;
}

$valid = ThemisDB_Contract_Manager::update_contract($contractId, array('status' => 'signed'), 'smoke valid transition');
$invalid = ThemisDB_Contract_Manager::update_contract($contractId, array('status' => 'draft'), 'smoke invalid transition');

echo ($valid === true && $invalid === false) ? '1' : '0';
'@

$guardrailContractResult = wp --path="$WpPath" eval $guardrailContractCheck
if ($guardrailContractResult -eq '1') {
    Write-Ok "Contract status guard blocks invalid lifecycle rollback"
} elseif ($guardrailContractResult -eq 'SETUP_FAILED') {
    Write-WarnLine "Contract guardrail smoke skipped because setup data could not be created"
} else {
    Write-Fail "Contract status guardrail smoke failed"
    exit 1
}

$orderItemsSyncCheck = @'
$products = ThemisDB_Order_Manager::get_products();
if (empty($products)) {
    echo 'NO_PRODUCTS';
    return;
}

$edition = sanitize_key($products[0]['edition'] ?? 'community');
$orderId = ThemisDB_Order_Manager::create_order(array(
    'customer_email' => 'smoke-items@example.com',
    'customer_name' => 'Smoke Items',
    'product_edition' => $edition,
    'total_amount' => 0,
    'currency' => 'EUR',
    'legal_terms_accepted' => 1,
    'legal_privacy_accepted' => 1,
));

if (!$orderId) {
    echo 'SETUP_FAILED';
    return;
}

$items = ThemisDB_Order_Manager::get_order_items($orderId);
echo (!empty($items)) ? '1' : '0';
'@

$orderItemsSyncResult = wp --path="$WpPath" eval $orderItemsSyncCheck
if ($orderItemsSyncResult -eq '1') {
    Write-Ok "Order item sync writes canonical order_items during order creation"
} elseif ($orderItemsSyncResult -eq 'NO_PRODUCTS') {
    Write-WarnLine "Order item sync smoke skipped because no active products are available"
} else {
    Write-Fail "Order item sync smoke failed"
    exit 1
}

$storageSentinelCheck = @'
$orderId = ThemisDB_Order_Manager::create_order(array(
    'customer_email' => 'smoke-license@example.com',
    'customer_name' => 'Smoke License',
    'product_edition' => 'community',
    'total_amount' => 10.00,
    'currency' => 'EUR',
    'legal_terms_accepted' => 1,
    'legal_privacy_accepted' => 1,
));

if (!$orderId) {
    echo 'SETUP_FAILED';
    return;
}

$contractId = ThemisDB_Contract_Manager::create_contract(array(
    'order_id' => intval($orderId),
    'customer_id' => get_current_user_id() ?: 1,
    'contract_type' => 'license',
    'contract_data' => array('source' => 'smoke-storage'),
));

if (!$contractId) {
    echo 'SETUP_FAILED';
    return;
}

$licenseId = ThemisDB_License_Manager::create_license(array(
    'order_id' => intval($orderId),
    'contract_id' => intval($contractId),
    'customer_id' => get_current_user_id() ?: 1,
    'product_edition' => 'community',
));

if (!$licenseId) {
    echo 'SETUP_FAILED';
    return;
}

$license = ThemisDB_License_Manager::get_license($licenseId);
echo (isset($license['max_storage_gb']) && intval($license['max_storage_gb']) === -1) ? '1' : '0';
'@

$storageSentinelResult = wp --path="$WpPath" eval $storageSentinelCheck
if ($storageSentinelResult -eq '1') {
    Write-Ok "Unlimited storage sentinel is stored as -1 in licenses"
} elseif ($storageSentinelResult -eq 'SETUP_FAILED') {
    Write-WarnLine "Storage sentinel smoke skipped because setup data could not be created"
} else {
    Write-Fail "Storage sentinel smoke failed"
    exit 1
}

$supportLimitCheck = @'
global $wpdb;

$licensesTable = $wpdb->prefix . 'themisdb_licenses';
$benefitsTable = $wpdb->prefix . 'themisdb_support_benefits';
$ticketsTable = $wpdb->prefix . 'themisdb_support_tickets';

$uniq = wp_generate_password(10, false, false) . '-' . time();
$licenseKey = 'THEMIS-COM-' . strtoupper(substr(md5($uniq), 0, 8)) . '-SMOKE123';

$licenseInserted = $wpdb->insert($licensesTable, array(
    'license_key' => $licenseKey,
    'order_id' => 1,
    'contract_id' => 1,
    'customer_id' => 1,
    'product_edition' => 'community',
    'license_type' => 'standard',
    'max_nodes' => 1,
    'max_cores' => -1,
    'max_storage_gb' => -1,
    'license_status' => 'active',
));

if (!$licenseInserted) {
    echo 'SETUP_FAILED';
    return;
}

$licenseId = intval($wpdb->insert_id);

$benefitInserted = $wpdb->insert($benefitsTable, array(
    'license_id' => $licenseId,
    'tier_level' => 'community',
    'max_open_tickets' => 0,
    'max_tickets_per_month' => -1,
    'response_sla_hours' => 48,
    'priority_can_assign' => 0,
    'included_hours_per_month' => 0,
    'benefit_status' => 'active',
    'tickets_used_this_month' => 0,
    'hours_used_this_month' => 0.00,
));

if (!$benefitInserted) {
    $wpdb->delete($licensesTable, array('id' => $licenseId), array('%d'));
    echo 'SETUP_FAILED';
    return;
}

$benefitId = intval($wpdb->insert_id);

$result = ThemisDB_Order_Support_Ticket_Manager::create_ticket(array(
    'subject' => 'Smoke Limit Test',
    'description' => 'Should be blocked by max_open_tickets = 0',
    'priority' => 'normal',
    'benefit_id' => $benefitId,
));

$ok = is_wp_error($result) && $result->get_error_code() === 'support_limit_reached';

$wpdb->delete($ticketsTable, array('benefit_id' => $benefitId), array('%d'));
$wpdb->delete($benefitsTable, array('id' => $benefitId), array('%d'));
$wpdb->delete($licensesTable, array('id' => $licenseId), array('%d'));

echo $ok ? '1' : '0';
'@

$supportLimitResult = wp --path="$WpPath" eval $supportLimitCheck
if ($supportLimitResult -eq '1') {
    Write-Ok "Support ticket creation is blocked when benefit limits are exceeded"
} elseif ($supportLimitResult -eq 'SETUP_FAILED') {
    Write-WarnLine "Support limit smoke skipped because temporary setup data could not be created"
} else {
    Write-Fail "Support limit enforcement smoke failed"
    exit 1
}

if ($CheckWooBridge) {
    Write-Host "Running Woo bridge checks..."

    try {
        wp --path="$WpPath" plugin is-active $WooPluginSlug | Out-Null
        Write-Ok "WooCommerce is active: $WooPluginSlug"
    } catch {
        Write-Fail "WooCommerce is not active: $WooPluginSlug"
        exit 1
    }

    $wooHookCheck = wp --path="$WpPath" eval "echo (has_action('woocommerce_order_status_completed') !== false && has_action('woocommerce_payment_complete') !== false && has_action('woocommerce_order_status_changed') !== false && has_action('woocommerce_order_refunded') !== false && has_action('themisdb_woocommerce_license_generation_requested') !== false) ? '1' : '0';"
    if ($wooHookCheck -eq '1') {
        Write-Ok "Woo bridge hooks are registered"
    } else {
        Write-Fail "Woo bridge hooks are not fully registered"
        exit 1
    }

    $wooMethodCheck = wp --path="$WpPath" eval "echo (method_exists('ThemisDB_WooCommerce_Bridge', 'on_order_status_changed') && method_exists('ThemisDB_WooCommerce_Bridge', 'on_order_refunded') && method_exists('ThemisDB_WooCommerce_Bridge', 'handle_license_generation_requested')) ? '1' : '0';"
    if ($wooMethodCheck -eq '1') {
        Write-Ok "Woo bridge lifecycle methods are available"
    } else {
        Write-Fail "Woo bridge lifecycle methods are missing"
        exit 1
    }

    # Product sync hooks (added in v0.3.0)
    $prodHookCheck = wp --path="$WpPath" eval "echo (has_action('woocommerce_new_product') !== false && has_action('woocommerce_update_product') !== false && has_action('woocommerce_trash_product') !== false && has_action('woocommerce_untrash_product') !== false) ? '1' : '0';"
    if ($prodHookCheck -eq '1') {
        Write-Ok "Woo bridge product sync hooks are registered"
    } else {
        Write-Fail "Woo bridge product sync hooks are not fully registered"
        exit 1
    }

    $prodMethodCheck = wp --path="$WpPath" eval "echo (method_exists('ThemisDB_WooCommerce_Bridge', 'sync_product_to_themisdb') && method_exists('ThemisDB_WooCommerce_Bridge', 'on_product_created') && method_exists('ThemisDB_WooCommerce_Bridge', 'on_product_updated')) ? '1' : '0';"
    if ($prodMethodCheck -eq '1') {
        Write-Ok "Woo bridge product sync methods are available"
    } else {
        Write-Fail "Woo bridge product sync methods are missing"
        exit 1
    }
}

Write-WarnLine "This smoke script checks prerequisites only."
Write-WarnLine "Shop smoke covers shortcode registration, render output and basic preset propagation, not full browser click-through behavior."
Write-WarnLine "Guardrail smoke covers core lifecycle/status/limit protections with lightweight runtime checks."
Write-WarnLine "Run full acceptance using docs/THEMISDB_ORDER_REQUEST_E2E_RUNBOOK.md"

Write-Ok "Smoke checks completed"
exit 0
