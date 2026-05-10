param(
    [string]$ThemePath = "themisdb-theme-v3",
    [int]$MaxAllowed = 0,
    [string[]]$AllowedHtmlFiles = @()
)

$ErrorActionPreference = "Stop"

if (-not (Test-Path $ThemePath)) {
    Write-Error "Theme path not found: $ThemePath"
}

$paths = @(
    Join-Path $ThemePath "patterns"
    Join-Path $ThemePath "templates"
)

$fileList = @()
foreach ($path in $paths) {
    if (Test-Path $path) {
        $fileList += Get-ChildItem -Path $path -Recurse -File | Where-Object {
            $_.Extension -eq ".php" -or $_.Extension -eq ".html"
        }
    }
}

$hits = @()
if (@($fileList).Count -gt 0) {
    $hits = Select-String -Path ($fileList.FullName) -Pattern "wp:html" -SimpleMatch | Where-Object {
        $_.Line -match "<!--\s*wp:html"
    }
}

$count = @($hits).Count

Write-Output "wp:html block matches: $count"
foreach ($hit in $hits) {
    Write-Output ("{0}:{1}: {2}" -f $hit.Path, $hit.LineNumber, $hit.Line.Trim())
}

if (@($AllowedHtmlFiles).Count -gt 0 -and $count -gt 0) {
    $themeRoot = (Resolve-Path $ThemePath).Path
    $allowedSet = @{}
    foreach ($allowed in $AllowedHtmlFiles) {
        $normalizedAllowed = $allowed.Replace('\', '/').TrimStart('/')
        $allowedSet[$normalizedAllowed.ToLowerInvariant()] = $true
    }

    $unexpectedHits = @()
    foreach ($hit in $hits) {
        $hitPath = (Resolve-Path $hit.Path).Path
        $relativePath = $hitPath.Substring($themeRoot.Length).TrimStart([char[]]@('\', '/'))
        $relativePath = $relativePath.Replace('\', '/')
        if (-not $allowedSet.ContainsKey($relativePath.ToLowerInvariant())) {
            $unexpectedHits += "${relativePath}:${($hit.LineNumber)}"
        }
    }

    if (@($unexpectedHits).Count -gt 0) {
        Write-Error ("Found wp:html blocks in non-allowed files: " + (($unexpectedHits | Sort-Object -Unique) -join ', '))
    }
}

if ($count -gt $MaxAllowed) {
    Write-Error "wp:html block count $count exceeds allowed threshold $MaxAllowed"
}

Write-Output "wp:html block check passed."
