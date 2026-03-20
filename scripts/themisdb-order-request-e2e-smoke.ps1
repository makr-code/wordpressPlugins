param(
    [Parameter(Mandatory = $true)]
    [string]$WpPath,

    [string]$PluginSlug = 'themisdb-order-request'
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
    "echo class_exists('ThemisDB_License_Manager') ? '1' : '0';"
)

$labels = @(
    'Order manager class loaded',
    'Contract manager class loaded',
    'Payment manager class loaded',
    'License manager class loaded'
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

Write-WarnLine "This smoke script checks prerequisites only."
Write-WarnLine "Run full acceptance using docs/THEMISDB_ORDER_REQUEST_E2E_RUNBOOK.md"

Write-Ok "Smoke checks completed"
exit 0
