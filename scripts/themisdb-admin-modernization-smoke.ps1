param(
    [Parameter(Mandatory = $true)]
    [string]$WpPath,

    [string]$RepoRoot = (Split-Path -Parent $PSScriptRoot),

    [string]$DebugLogPath,

    [string]$ResultPath,

    [switch]$SkipRenderChecks
)

$ErrorActionPreference = 'Stop'

function Write-Ok($msg) { Write-Host "[OK] $msg" -ForegroundColor Green }
function Write-WarnLine($msg) { Write-Host "[WARN] $msg" -ForegroundColor Yellow }
function Write-Fail($msg) { Write-Host "[FAIL] $msg" -ForegroundColor Red }

function Add-Result {
    param(
        [Parameter(Mandatory = $true)]
        [System.Collections.Generic.List[object]]$Results,

        [Parameter(Mandatory = $true)]
        [string]$Label,

        [Parameter(Mandatory = $true)]
        [string]$Stage,

        [Parameter(Mandatory = $true)]
        [string]$Status,

        [Parameter(Mandatory = $true)]
        [string]$Message
    )

    $Results.Add([pscustomobject]@{
        label = $Label
        stage = $Stage
        status = $Status
        message = $Message
    }) | Out-Null
}

function Get-DebugLogState {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Path
    )

    if (-not (Test-Path $Path)) {
        return [pscustomobject]@{
            exists = $false
            length = 0
        }
    }

    $item = Get-Item -Path $Path
    return [pscustomobject]@{
        exists = $true
        length = [int64]$item.Length
    }
}

function Test-DebugLogGrowth {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Path,

        [Parameter(Mandatory = $true)]
        [int64]$StartLength
    )

    if (-not (Test-Path $Path)) {
        return [pscustomobject]@{
            status = 'missing'
            message = 'Debug log not found after smoke test run'
            newErrors = @()
        }
    }

    $content = Get-Content -Path $Path -Raw
    $fullText = if ($null -eq $content) { '' } else { [string]$content }
    $tailText = if ($StartLength -lt $fullText.Length) { $fullText.Substring([int]$StartLength) } else { '' }
    $matches = [regex]::Matches($tailText, '(?im)^.*(PHP\s+(Fatal error|Warning|Notice)|Fatal error|Warning|Notice).*$')
    $newErrors = @($matches | ForEach-Object { $_.Value.Trim() } | Select-Object -Unique)

    if ($newErrors.Count -gt 0) {
        return [pscustomobject]@{
            status = 'failed'
            message = 'New warning/notice/fatal entries detected in debug log'
            newErrors = $newErrors
        }
    }

    return [pscustomobject]@{
        status = 'passed'
        message = 'No new warning/notice/fatal entries detected in debug log'
        newErrors = @()
    }
}

function Invoke-WpEval {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Code
    )

    $result = wp --path="$WpPath" eval $Code 2>&1
    if ($LASTEXITCODE -ne 0) {
        throw "WP-CLI eval failed: $result"
    }

    return (($result | Out-String).Trim())
}

function Test-PluginActive {
    param(
        [Parameter(Mandatory = $true)]
        [string]$PluginSlug
    )

    wp --path="$WpPath" plugin is-active $PluginSlug *> $null
    return ($LASTEXITCODE -eq 0)
}

function Test-SourceMarkers {
    param(
        [Parameter(Mandatory = $true)]
        [string]$RelativePath,

        [Parameter(Mandatory = $true)]
        [string[]]$Markers
    )

    $fullPath = Join-Path $RepoRoot $RelativePath
    if (-not (Test-Path $fullPath)) {
        throw "Admin file not found: $RelativePath"
    }

    $content = Get-Content -Path $fullPath -Raw
    foreach ($marker in $Markers) {
        if ($content -notmatch [regex]::Escape($marker)) {
            throw "Marker '$marker' missing in $RelativePath"
        }
    }
}

function Test-MenuRegistration {
    param(
        [Parameter(Mandatory = $true)]
        [string]$PageSlug
    )

    $code = @"
require_once ABSPATH . 'wp-admin/includes/plugin.php';

`$admins = get_users(array(
    'role' => 'administrator',
    'number' => 1,
    'fields' => 'ids',
));

if (empty(`$admins)) {
    echo 'NO_ADMIN';
    return;
}

wp_set_current_user(intval(`$admins[0]));
do_action('admin_menu');

`$found = false;
global `$menu, `$submenu;

foreach ((array) `$menu as `$item) {
    if (!empty(`$item[2]) && `$item[2] === '$PageSlug') {
        `$found = true;
        break;
    }
}

if (!`$found) {
    foreach ((array) `$submenu as `$parent => `$items) {
        foreach ((array) `$items as `$item) {
            if (!empty(`$item[2]) && `$item[2] === '$PageSlug') {
                `$found = true;
                break 2;
            }
        }
    }
}

echo `$found ? '1' : '0';
"@

    return (Invoke-WpEval -Code $code)
}

function Test-RenderedStructure {
    param(
        [Parameter(Mandatory = $true)]
        [string]$PageSlug,

        [Parameter(Mandatory = $true)]
        [string]$HookName,

        [string]$Tab = ''
    )

    $tabAssignment = ''
    if ($Tab -ne '') {
        $tabAssignment = "`$_GET['tab'] = '$Tab';"
    }

    $code = @"
require_once ABSPATH . 'wp-admin/includes/plugin.php';

`$admins = get_users(array(
    'role' => 'administrator',
    'number' => 1,
    'fields' => 'ids',
));

if (empty(`$admins)) {
    echo 'NO_ADMIN';
    return;
}

wp_set_current_user(intval(`$admins[0]));
do_action('admin_menu');

if (!has_action('$HookName')) {
    echo 'NO_HOOK';
    return;
}

`$_GET['page'] = '$PageSlug';
$tabAssignment

ob_start();
do_action('$HookName');
`$html = ob_get_clean();

`$ok = strpos(`$html, 'wp-heading-inline') !== false
    && strpos(`$html, 'wp-header-end') !== false;

echo `$ok ? '1' : '0';
"@

    return (Invoke-WpEval -Code $code)
}

if (-not (Test-Path $WpPath)) {
    Write-Fail "WordPress path not found: $WpPath"
    exit 1
}

if (-not (Test-Path $RepoRoot)) {
    Write-Fail "Repository path not found: $RepoRoot"
    exit 1
}

$wpCmd = Get-Command wp -ErrorAction SilentlyContinue
if (-not $wpCmd) {
    Write-Fail 'wp command not found. Install WP-CLI first.'
    exit 1
}

Write-Host "Running ThemisDB admin modernization smoke checks in $WpPath..."

$results = [System.Collections.Generic.List[object]]::new()
$debugLogState = $null

if ($DebugLogPath) {
    $debugLogState = Get-DebugLogState -Path $DebugLogPath
    if ($debugLogState.exists) {
        Write-Ok "Debug log baseline captured: $DebugLogPath"
    } else {
        Write-WarnLine "Debug log baseline file not found yet: $DebugLogPath"
    }
}

try {
    wp --path="$WpPath" core is-installed | Out-Null
    Write-Ok 'WordPress installation detected'
} catch {
    Write-Fail 'WordPress is not installed or wp --path is invalid'
    exit 1
}

$checks = @(
    [pscustomobject]@{
        Label = 'Architecture Diagrams settings'
        PluginSlug = 'themisdb-architecture-diagrams'
        PageSlug = 'themisdb-architecture-diagrams'
        HookName = 'settings_page_themisdb-architecture-diagrams'
        Tab = 'settings'
        FilePath = 'themisdb-architecture-diagrams/templates/admin-settings.php'
        Markers = @('wp-heading-inline', 'nav-tab-wrapper', 'wp-header-end')
    },
    [pscustomobject]@{
        Label = 'Benchmark Visualizer settings'
        PluginSlug = 'themisdb-benchmark-visualizer'
        PageSlug = 'themisdb-benchmark-visualizer'
        HookName = 'settings_page_themisdb-benchmark-visualizer'
        Tab = 'settings'
        FilePath = 'themisdb-benchmark-visualizer/templates/admin-settings.php'
        Markers = @('wp-heading-inline', 'nav-tab-wrapper', 'wp-header-end')
    },
    [pscustomobject]@{
        Label = 'Query Playground settings'
        PluginSlug = 'themisdb-query-playground'
        PageSlug = 'themisdb-query-playground'
        HookName = 'settings_page_themisdb-query-playground'
        Tab = 'settings'
        FilePath = 'themisdb-query-playground/templates/admin-settings.php'
        Markers = @('wp-heading-inline', 'nav-tab-wrapper', 'wp-header-end')
    },
    [pscustomobject]@{
        Label = 'Release Timeline settings'
        PluginSlug = 'themisdb-release-timeline'
        PageSlug = 'themisdb-release-timeline'
        HookName = 'settings_page_themisdb-release-timeline'
        Tab = 'settings'
        FilePath = 'themisdb-release-timeline/templates/admin-settings.php'
        Markers = @('wp-heading-inline', 'nav-tab-wrapper', 'wp-header-end')
    },
    [pscustomobject]@{
        Label = 'TCO Calculator settings'
        PluginSlug = 'themisdb-tco-calculator'
        PageSlug = 'themisdb-tco-calculator'
        HookName = 'settings_page_themisdb-tco-calculator'
        Tab = 'settings'
        FilePath = 'themisdb-tco-calculator/templates/admin-settings.php'
        Markers = @('wp-heading-inline', 'nav-tab-wrapper', 'wp-header-end')
    },
    [pscustomobject]@{
        Label = 'Test Dashboard settings'
        PluginSlug = 'themisdb-test-dashboard'
        PageSlug = 'themisdb-test-dashboard'
        HookName = 'settings_page_themisdb-test-dashboard'
        Tab = 'settings'
        FilePath = 'themisdb-test-dashboard/templates/admin-settings.php'
        Markers = @('wp-heading-inline', 'nav-tab-wrapper', 'wp-header-end')
    },
    [pscustomobject]@{
        Label = 'Wiki Integration settings'
        PluginSlug = 'themisdb-wiki-integration'
        PageSlug = 'themisdb-wiki-integration'
        HookName = 'settings_page_themisdb-wiki-integration'
        Tab = 'settings'
        FilePath = 'themisdb-wiki-integration/templates/admin-settings.php'
        Markers = @('wp-heading-inline', 'nav-tab-wrapper', 'wp-header-end')
    },
    [pscustomobject]@{
        Label = 'Feature Matrix settings'
        PluginSlug = 'themisdb-feature-matrix'
        PageSlug = 'themisdb-feature-matrix'
        HookName = 'settings_page_themisdb-feature-matrix'
        Tab = 'settings'
        FilePath = 'themisdb-feature-matrix/templates/admin-settings.php'
        Markers = @('wp-heading-inline', 'page-title-action', 'wp-header-end')
    },
    [pscustomobject]@{
        Label = 'Support Portal settings'
        PluginSlug = 'themisdb-support-portal'
        PageSlug = 'themisdb-support-settings'
        HookName = 'themisdb-support_page_themisdb-support-settings'
        Tab = 'settings'
        FilePath = 'themisdb-support-portal/templates/admin-settings.php'
        Markers = @('wp-heading-inline', 'nav-tab-wrapper', 'wp-header-end')
    },
    [pscustomobject]@{
        Label = 'Support Portal tickets'
        PluginSlug = 'themisdb-support-portal'
        PageSlug = 'themisdb-support'
        HookName = 'toplevel_page_themisdb-support'
        Tab = 'list'
        FilePath = 'themisdb-support-portal/templates/admin-tickets.php'
        Markers = @('wp-heading-inline', 'page-title-action', 'nav-tab-wrapper', 'themisdb-admin-modules')
    },
    [pscustomobject]@{
        Label = 'Front Slider settings'
        PluginSlug = 'themisdb-front-slider'
        PageSlug = 'themisdb-front-slider'
        HookName = 'settings_page_themisdb-front-slider'
        Tab = ''
        FilePath = 'themisdb-front-slider/themisdb-front-slider.php'
        Markers = @('wp-heading-inline', 'page-title-action', 'wp-header-end')
    },
    [pscustomobject]@{
        Label = 'Formula Library'
        PluginSlug = 'themisdb-formula-renderer'
        PageSlug = 'themisdb-formula-library'
        HookName = 'settings_page_themisdb-formula-library'
        Tab = ''
        FilePath = 'themisdb-formula-renderer/includes/class-formula-library.php'
        Markers = @('wp-heading-inline', 'page-title-action', 'wp-header-end')
    },
    [pscustomobject]@{
        Label = 'GitHub Bridge settings'
        PluginSlug = 'themisdb-github-bridge'
        PageSlug = 'themisdb-github-bridge'
        HookName = 'settings_page_themisdb-github-bridge'
        Tab = ''
        FilePath = 'themisdb-github-bridge/includes/class-github-bridge.php'
        Markers = @('wp-heading-inline', 'page-title-action', 'wp-header-end')
    },
    [pscustomobject]@{
        Label = 'Order Request dashboard'
        PluginSlug = 'themisdb-order-request'
        PageSlug = 'themisdb-order-dashboard'
        HookName = 'toplevel_page_themisdb-order-dashboard'
        Tab = ''
        FilePath = 'themisdb-order-request/includes/class-admin.php'
        Markers = @('wp-heading-inline', 'page-title-action', 'wp-header-end')
    },
    [pscustomobject]@{
        Label = 'Order Request orders'
        PluginSlug = 'themisdb-order-request'
        PageSlug = 'themisdb-orders'
        HookName = 'themisdb-order-dashboard_page_themisdb-orders'
        Tab = ''
        FilePath = 'themisdb-order-request/includes/class-admin.php'
        Markers = @('Bestellungen', 'page-title-action', 'wp-header-end')
    },
    [pscustomobject]@{
        Label = 'Order Request products'
        PluginSlug = 'themisdb-order-request'
        PageSlug = 'themisdb-products'
        HookName = 'themisdb-order-dashboard_page_themisdb-products'
        Tab = ''
        FilePath = 'themisdb-order-request/includes/class-admin.php'
        Markers = @('Produkte und Module (CRUD)', 'page-title-action', 'wp-header-end')
    },
    [pscustomobject]@{
        Label = 'Order Request payments'
        PluginSlug = 'themisdb-order-request'
        PageSlug = 'themisdb-payments'
        HookName = 'themisdb-order-dashboard_page_themisdb-payments'
        Tab = ''
        FilePath = 'themisdb-order-request/includes/class-admin.php'
        Markers = @('Zahlungen', 'page-title-action', 'wp-header-end')
    },
    [pscustomobject]@{
        Label = 'Order Request licenses'
        PluginSlug = 'themisdb-order-request'
        PageSlug = 'themisdb-licenses'
        HookName = 'themisdb-order-dashboard_page_themisdb-licenses'
        Tab = ''
        FilePath = 'themisdb-order-request/includes/class-admin.php'
        Markers = @('Lizenzen', 'page-title-action', 'wp-header-end')
    },
    [pscustomobject]@{
        Label = 'Order Request support tickets'
        PluginSlug = 'themisdb-order-request'
        PageSlug = 'themisdb-support-tickets'
        HookName = 'themisdb-order-dashboard_page_themisdb-support-tickets'
        Tab = ''
        FilePath = 'themisdb-order-request/includes/class-admin.php'
        Markers = @('Support Tickets', 'page-title-action', 'wp-header-end')
    },
    [pscustomobject]@{
        Label = 'Order Request bank import'
        PluginSlug = 'themisdb-order-request'
        PageSlug = 'themisdb-bank-import'
        HookName = 'themisdb-order-dashboard_page_themisdb-bank-import'
        Tab = ''
        FilePath = 'themisdb-order-request/includes/class-admin.php'
        Markers = @('Bankimport', 'page-title-action', 'wp-header-end')
    },
    [pscustomobject]@{
        Label = 'Order Request settings'
        PluginSlug = 'themisdb-order-request'
        PageSlug = 'themisdb-order-settings'
        HookName = 'themisdb-order-dashboard_page_themisdb-order-settings'
        Tab = ''
        FilePath = 'themisdb-order-request/includes/class-admin.php'
        Markers = @('Einstellungen', 'page-title-action', 'wp-header-end')
    },
    [pscustomobject]@{
        Label = 'Wiki Doc Import tool'
        PluginSlug = 'themisdb-wiki-integration'
        PageSlug = 'themisdb-doc-import'
        HookName = 'tools_page_themisdb-doc-import'
        Tab = ''
        FilePath = 'themisdb-wiki-integration/wordpress_doc_importer.php'
        Markers = @('wp-heading-inline', 'page-title-action', 'wp-header-end')
    }
)

$failed = 0
$warnings = 0
$executed = 0

foreach ($check in $checks) {
    try {
        Test-SourceMarkers -RelativePath $check.FilePath -Markers $check.Markers
        Write-Ok "$($check.Label): source markers present"
        Add-Result -Results $results -Label $check.Label -Stage 'source' -Status 'passed' -Message 'Required source markers found'
    } catch {
        Write-Fail "$($check.Label): $($_.Exception.Message)"
        Add-Result -Results $results -Label $check.Label -Stage 'source' -Status 'failed' -Message $_.Exception.Message
        $failed++
        continue
    }

    if (-not (Test-PluginActive -PluginSlug $check.PluginSlug)) {
        Write-WarnLine "$($check.Label): plugin inactive in test instance, runtime checks skipped"
        Add-Result -Results $results -Label $check.Label -Stage 'runtime' -Status 'warning' -Message 'Plugin inactive in target WordPress instance; runtime checks skipped'
        $warnings++
        continue
    }

    try {
        $menuResult = Test-MenuRegistration -PageSlug $check.PageSlug
        if ($menuResult -ne '1') {
            throw "menu slug '$($check.PageSlug)' not registered (result: $menuResult)"
        }

        Write-Ok "$($check.Label): admin menu slug registered"
        Add-Result -Results $results -Label $check.Label -Stage 'menu' -Status 'passed' -Message 'Admin menu slug registered'
    } catch {
        Write-Fail "$($check.Label): $($_.Exception.Message)"
        Add-Result -Results $results -Label $check.Label -Stage 'menu' -Status 'failed' -Message $_.Exception.Message
        $failed++
        continue
    }

    if (-not $SkipRenderChecks) {
        try {
            $renderResult = Test-RenderedStructure -PageSlug $check.PageSlug -HookName $check.HookName -Tab $check.Tab
            if ($renderResult -ne '1') {
                throw "rendered admin markup did not include expected base structure (result: $renderResult)"
            }

            Write-Ok "$($check.Label): rendered admin markup contains base header structure"
            Add-Result -Results $results -Label $check.Label -Stage 'render' -Status 'passed' -Message 'Rendered admin markup contains base header structure'
        } catch {
            Write-Fail "$($check.Label): $($_.Exception.Message)"
            Add-Result -Results $results -Label $check.Label -Stage 'render' -Status 'failed' -Message $_.Exception.Message
            $failed++
            continue
        }
    }

    $executed++
}

if ($debugLogState -ne $null) {
    $debugLogResult = Test-DebugLogGrowth -Path $DebugLogPath -StartLength $debugLogState.length
    if ($debugLogResult.status -eq 'passed') {
        Write-Ok $debugLogResult.message
        Add-Result -Results $results -Label 'Debug log' -Stage 'debug-log' -Status 'passed' -Message $debugLogResult.message
    } elseif ($debugLogResult.status -eq 'missing') {
        Write-WarnLine $debugLogResult.message
        Add-Result -Results $results -Label 'Debug log' -Stage 'debug-log' -Status 'warning' -Message $debugLogResult.message
        $warnings++
    } else {
        Write-Fail $debugLogResult.message
        foreach ($entry in $debugLogResult.newErrors) {
            Write-Fail "Debug log entry: $entry"
        }
        Add-Result -Results $results -Label 'Debug log' -Stage 'debug-log' -Status 'failed' -Message (($debugLogResult.newErrors -join " || ").Trim())
        $failed++
    }
}

Write-Host ''
Write-Host "Checks passed: $executed"
Write-Host "Warnings: $warnings"
Write-Host "Failures: $failed"

if ($ResultPath) {
    $report = [pscustomobject]@{
        timestamp = (Get-Date).ToString('s')
        wpPath = $WpPath
        repoRoot = $RepoRoot
        skipRenderChecks = [bool]$SkipRenderChecks
        debugLogPath = $DebugLogPath
        passedChecks = $executed
        warnings = $warnings
        failures = $failed
        results = $results
    }

    $reportDir = Split-Path -Parent $ResultPath
    if ($reportDir -and -not (Test-Path $reportDir)) {
        New-Item -ItemType Directory -Path $reportDir -Force | Out-Null
    }

    $report | ConvertTo-Json -Depth 5 | Set-Content -Path $ResultPath -Encoding UTF8
    Write-Ok "Smoke report written to $ResultPath"
}

if ($failed -gt 0) {
    exit 1
}

Write-Ok 'ThemisDB admin modernization smoke checks completed successfully'