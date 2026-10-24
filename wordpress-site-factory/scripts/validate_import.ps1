$ErrorActionPreference = "Stop"
$SitePath = "C:\xampp\htdocs\wordpress"

if (-not (Test-Path $SitePath)) {
    throw "WordPress site path not found at $SitePath"
}

Write-Host "== Posts =="
& wp --path $SitePath post list --post_type=post --status=publish --format=table

Write-Host ""
Write-Host "== Categories =="
& wp --path $SitePath term list category --format=table

Write-Host ""
Write-Host "== Tags =="
& wp --path $SitePath term list post_tag --format=table

Write-Host ""
Write-Host "== Media =="
& wp --path $SitePath media list --format=table
