param(
    [int]$Offset = 0,
    [int]$WindowSize = 20,
    [switch]$DryRun,
    [switch]$UpdateOnly,
    [switch]$IncludeLowQuality
)

$ErrorActionPreference = "Stop"

$RepoRoot = (Resolve-Path (Join-Path $PSScriptRoot "..\..")).Path
$SourceRoot = Join-Path $RepoRoot "page-content-blog\sources"
$Importer = Join-Path $RepoRoot "wordpress-site-factory\importers\docs_import.py"

if (-not (Test-Path $Importer)) {
    throw "Importer not found at $Importer"
}

if (-not (Test-Path $SourceRoot)) {
    throw "Source root not found at $SourceRoot"
}

$userName = if ($env:WP_LOCAL_USER) { $env:WP_LOCAL_USER } else { "test" }
$baseUrl = if ($env:WP_LOCAL_BASE_URL) { $env:WP_LOCAL_BASE_URL } else { "http://localhost/wordpress" }

$arguments = @(
    $Importer,
    "--blog-sources-root", $SourceRoot,
    "--base-url", $baseUrl,
    "--user", $userName
)

if ($env:WP_LOCAL_APP_PASSWORD) {
    $arguments += @("--password", $env:WP_LOCAL_APP_PASSWORD)
}

if ($DryRun) {
    $arguments += "--dry-run"
}

if ($UpdateOnly) {
    $arguments += "--update-only"
}

if ($IncludeLowQuality) {
    $arguments += "--include-low-quality"
}

if ($Offset -gt 0) {
    $arguments += @("--offset", [string]$Offset)
}

if ($WindowSize -gt 0) {
    $arguments += @("--window-size", [string]$WindowSize)
}

Write-Host "Running importer batch:"
Write-Host ($arguments -join " ")

& python @arguments
if ($LASTEXITCODE -ne 0) {
    exit $LASTEXITCODE
}
