#!/usr/bin/env pwsh
<#
.SYNOPSIS
    Creates a new release tag for ThemisDB plugins.

.DESCRIPTION
    Bumps the version in all plugin PHP headers + update-info.json files,
    commits the changes, and pushes a Git tag to trigger GitHub Actions CI.

.PARAMETER Version
    New version (e.g. 1.2.0, 2.0.0-beta.1)

.PARAMETER Plugins
    Comma-separated plugin slugs to release. Defaults to all main plugins.

.PARAMETER DryRun
    Show what would happen without actually making changes.

.EXAMPLE
    .\scripts\create-release.ps1 -Version 1.2.0
    .\scripts\create-release.ps1 -Version 1.2.0 -Plugins themisdb-order-request
    .\scripts\create-release.ps1 -Version 2.0.0-beta.1 -DryRun
#>

[CmdletBinding()]
param(
    [Parameter(Mandatory)]
    [ValidatePattern('^\d+\.\d+\.\d+(-[a-z0-9.]+)?$')]
    [string]$Version,

    [string]$Plugins = 'themisdb-order-request,themisdb-support-portal',

    [switch]$DryRun
)

$ErrorActionPreference = 'Stop'
Set-Location (Split-Path $PSScriptRoot)

function Write-Step($msg) { Write-Host "`n==> $msg" -ForegroundColor Cyan }
function Write-OK($msg)   { Write-Host "    [OK] $msg" -ForegroundColor Green }
function Write-Skip($msg) { Write-Host "    [--] $msg" -ForegroundColor Gray }
function Write-Warn($msg) { Write-Host "    [!!] $msg" -ForegroundColor Yellow }

# Validate git state
Write-Step "Checking git state..."
$branch = git rev-parse --abbrev-ref HEAD
if ($branch -ne 'main' -and $branch -ne 'develop') {
    Write-Warn "Not on main/develop branch (current: $branch). Continue? (y/n)"
    if ((Read-Host) -ne 'y') { exit 0 }
}

$dirty = git status --porcelain | Where-Object { $_ -match '^[MADRCU ]M|^ M|^M |^A |^D |^R ' }
if ($dirty -and -not $DryRun) {
    Write-Warn "Working directory has uncommitted tracked changes:`n$dirty"
    Write-Warn "Commit or stash tracked changes first. Aborting."
    exit 1
} elseif ($dirty) {
    Write-Warn "Working directory has uncommitted changes (DryRun - continuing):`n$dirty"
}

# Check if tag already exists
if (git tag -l "v$Version") {
    Write-Host "Tag v$Version already exists!" -ForegroundColor Red
    exit 1
}

$pluginList = $Plugins -split ','

Write-Step "Planning release v$Version for: $($pluginList -join ', ')"

foreach ($plugin in $pluginList) {
    $pluginPhp = Join-Path $plugin "$plugin.php"
    $updateJson = Join-Path $plugin 'update-info.json'

    if (!(Test-Path $pluginPhp)) {
        Write-Skip "$plugin/$plugin.php not found - skipping"
        continue
    }

    # --- bump version in PHP header ---
    $php = Get-Content $pluginPhp -Raw
    $oldVersion = if ($php -match '(?m)^\s*\*?\s*Version:\s*([\d.]+(?:-[\w.]+)?)') { $Matches[1] } else { '?' }
    
    Write-Host "  $plugin : $oldVersion -> $Version" -ForegroundColor White

    if (!$DryRun) {
        # Update "Version: x.y.z" in plugin header
        $php = $php -replace "(?m)(^\s*\*?\s*Version:\s*)[\d.]+(?:-[\w.]+)?", "`${1}$Version"
        
        # Update define('THEMISDB_*_VERSION', ...)
        $php = $php -replace "(?m)(define\('[A-Z_]+VERSION',\s*')([\d.]+(?:-[\w.]+)?)'", "`${1}$Version'"
        
        Set-Content $pluginPhp -Value $php -Encoding UTF8 -NoNewline
        Write-OK "Updated $pluginPhp"
    } else {
        Write-Skip "[DryRun] Would update $pluginPhp ($oldVersion -> $Version)"
    }

    # --- bump version in update-info.json ---
    if (Test-Path $updateJson) {
        if (!$DryRun) {
            $json = Get-Content $updateJson -Raw | ConvertFrom-Json
            $json.version = $Version
            $json | ConvertTo-Json -Depth 10 | Set-Content $updateJson -Encoding UTF8
            Write-OK "Updated $updateJson"
        } else {
            Write-Skip "[DryRun] Would update $updateJson"
        }
    }
}

if ($DryRun) {
    Write-Host "`n[DryRun] No changes made. Remove -DryRun to apply." -ForegroundColor Yellow
    exit 0
}

# --- Git commit ---
Write-Step "Committing version bump..."
git add --all
git diff --cached --quiet
if ($LASTEXITCODE -ne 0) {
    git commit -m "chore(release): bump to v$Version"
    Write-OK "Committed version bump"
} else {
    Write-Skip "Nothing to commit (versions already at $Version)"
}

# --- Push tag ---
Write-Step "Creating and pushing tag v$Version..."
git tag -a "v$Version" -m "Release v$Version"
git push origin HEAD
git push origin "v$Version"

Write-OK "Tag v$Version pushed to GitHub"

Write-Host @"

======================================================
  Release v$Version triggered!
======================================================

GitHub Actions is now:
  1. Running tests (PHP lint, PHPCS, PHPUnit)
  2. Building plugin ZIPs
  3. Generating SHA256 checksums
  4. Publishing GitHub Release
  5. Uploading artifacts

Monitor progress:
  https://github.com/makr-code/wordpressPlugins/actions

Release will appear at:
  https://github.com/makr-code/wordpressPlugins/releases/tag/v$Version

Customer WordPress installs will see the update within 12 hours.
To force immediate check: wp transient delete --all

"@ -ForegroundColor Cyan
