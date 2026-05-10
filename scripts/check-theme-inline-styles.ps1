param(
    [string]$ThemePath = "themisdb-theme-v3",
    [int]$MaxAllowed = 0
)

$ErrorActionPreference = "Stop"

if (-not (Test-Path $ThemePath)) {
    Write-Error "Theme path not found: $ThemePath"
}

$targets = @(
    Join-Path $ThemePath "patterns",
    Join-Path $ThemePath "templates"
)

$files = @()
foreach ($target in $targets) {
    if (Test-Path $target) {
        $files += Get-ChildItem -Path $target -File -Recurse | Where-Object { $_.Extension -in '.php', '.html' }
    }
}

$styleHits = @()
if (@($files).Count -gt 0) {
    $styleHits = Select-String -Path ($files.FullName) -Pattern 'style="' -SimpleMatch
}

$count = @($styleHits).Count

Write-Output "Inline style matches: $count"
foreach ($m in $styleHits) {
    Write-Output ("{0}:{1}: {2}" -f $m.Path, $m.LineNumber, $m.Line.Trim())
}

if ($count -gt $MaxAllowed) {
    Write-Error "Inline style count $count exceeds allowed threshold $MaxAllowed"
}

Write-Output "Inline style check passed."
