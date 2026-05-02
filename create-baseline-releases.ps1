# create-baseline-releases.ps1
# Erstellt fuer jedes Plugin und Theme einen GitHub-Baseline-Release.
# Aufruf: .\create-baseline-releases.ps1
# Voraussetzung: gh CLI authenticated, PowerShell 5+

Set-Location "c:\Projects\wordpressPlugins"

$tmpDir = "c:\Temp\themisdb-release-zips"
New-Item -ItemType Directory -Force -Path $tmpDir | Out-Null

$items = @(
    @{ Type='plugin'; Slug='themisdb-architecture-diagrams';  Version='1.1.0' },
    @{ Type='plugin'; Slug='themisdb-benchmark-visualizer';   Version='1.0.0' },
    @{ Type='plugin'; Slug='themisdb-compendium-downloads';   Version='1.0.0' },
    @{ Type='plugin'; Slug='themisdb-docker-downloads';       Version='1.0.0' },
    @{ Type='plugin'; Slug='themisdb-downloads';              Version='1.2.0' },
    @{ Type='plugin'; Slug='themisdb-engagement-tracker';     Version='1.0.0' },
    @{ Type='plugin'; Slug='themisdb-feature-matrix';         Version='1.0.0' },
    @{ Type='plugin'; Slug='themisdb-formula-renderer';       Version='1.1.0' },
    @{ Type='plugin'; Slug='themisdb-front-slider';           Version='1.1.0' },
    @{ Type='plugin'; Slug='themisdb-gallery';                Version='1.0.1' },
    @{ Type='plugin'; Slug='themisdb-github-bridge';          Version='1.0.0' },
    @{ Type='plugin'; Slug='themisdb-graph-navigation';       Version='1.0.0' },
    @{ Type='plugin'; Slug='themisdb-order-request';          Version='1.1.0' },
    @{ Type='plugin'; Slug='themisdb-query-playground';       Version='1.0.0' },
    @{ Type='plugin'; Slug='themisdb-release-timeline';       Version='1.0.2' },
    @{ Type='plugin'; Slug='themisdb-support-portal';         Version='1.0.0' },
    @{ Type='plugin'; Slug='themisdb-taxonomy-manager';       Version='1.0.0' },
    @{ Type='plugin'; Slug='themisdb-tco-calculator';         Version='1.0.0' },
    @{ Type='plugin'; Slug='themisdb-test-dashboard';         Version='1.0.0' },
    @{ Type='plugin'; Slug='themisdb-wiki-integration';       Version='1.0.1' },
    @{ Type='theme';  Slug='themisdb-theme';                  Version='1.0.0' },
    @{ Type='theme';  Slug='themisdb-theme-plain';            Version='1.0.0' },
    @{ Type='theme';  Slug='themisdb-theme-v1';               Version='1.0.0' },
    @{ Type='theme';  Slug='themisdb-theme-v2';               Version='2.0.0' },
    @{ Type='theme';  Slug='themisdb-theme-v3';               Version='3.1.0' }
)

$ok    = @()
$skipped = @()
$failed  = @()

foreach ($item in $items) {
    $slug    = $item.Slug
    $version = $item.Version
    $type    = $item.Type
    $tag     = "$slug/v$version"
    $zipPath = "$tmpDir\$slug.zip"

    Write-Host "`n[$type] $slug @ v$version" -ForegroundColor Cyan

    # Check if release already exists
    $exists = gh release view $tag --repo makr-code/wordpressPlugins 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "  SKIP – Release $tag existiert bereits." -ForegroundColor Yellow
        $skipped += $tag
        continue
    }

    # Create ZIP
    $srcPath = "c:\Projects\wordpressPlugins\$slug"
    if (-not (Test-Path $srcPath)) {
        Write-Host "  FEHLER – Quellpfad nicht gefunden: $srcPath" -ForegroundColor Red
        $failed += $slug
        continue
    }

    if (Test-Path $zipPath) { Remove-Item -Force $zipPath }
    Compress-Archive -Path $srcPath -DestinationPath $zipPath
    $zipSize = (Get-Item $zipPath).Length
    Write-Host "  ZIP: $zipPath ($("{0:N0}" -f $zipSize) Bytes)"

    # Create GitHub release
    $notes = "Baseline-Release fuer $slug v$version.`n`nDieser Release entspricht dem aktuellen Stand im ``main``-Branch."
    $title = "$slug v$version"

    $result = gh release create $tag "$zipPath#$slug.zip" `
        --repo makr-code/wordpressPlugins `
        --title $title `
        --notes $notes 2>&1

    if ($LASTEXITCODE -eq 0) {
        Write-Host "  OK – $result" -ForegroundColor Green
        $ok += $tag
    } else {
        Write-Host "  FEHLER – $result" -ForegroundColor Red
        $failed += $slug
    }

    # Small pause to avoid GitHub API rate limiting
    Start-Sleep -Milliseconds 500
}

# Cleanup ZIPs
Remove-Item -Recurse -Force $tmpDir -ErrorAction SilentlyContinue

Write-Host "`n============================================" -ForegroundColor White
Write-Host "Erstellt  : $($ok.Count)" -ForegroundColor Green
Write-Host "Uebersprungen: $($skipped.Count)" -ForegroundColor Yellow
Write-Host "Fehler    : $($failed.Count)" -ForegroundColor Red
if ($ok)      { $ok      | ForEach-Object { Write-Host "  + $_" -ForegroundColor Green } }
if ($skipped) { $skipped | ForEach-Object { Write-Host "  ~ $_" -ForegroundColor Yellow } }
if ($failed)  { $failed  | ForEach-Object { Write-Host "  x $_" -ForegroundColor Red } }
