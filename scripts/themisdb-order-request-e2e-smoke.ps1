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
    "echo class_exists('ThemisDB_WooCommerce_Bridge') ? '1' : '0';"
)

$labels = @(
    'Order manager class loaded',
    'Contract manager class loaded',
    'Payment manager class loaded',
    'License manager class loaded',
    'Woo bridge class loaded'
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
Write-WarnLine "Run full acceptance using docs/THEMISDB_ORDER_REQUEST_E2E_RUNBOOK.md"

Write-Ok "Smoke checks completed"
exit 0
