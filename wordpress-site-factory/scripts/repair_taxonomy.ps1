$ErrorActionPreference = "Stop"
$SitePath = "C:\xampp\htdocs\wordpress"

if (-not (Test-Path $SitePath)) {
    throw "WordPress site path not found at $SitePath"
}

Write-Host "== category terms =="
& wp --path $SitePath term list category --format=json

Write-Host ""
Write-Host "== post_tag terms =="
& wp --path $SitePath term list post_tag --format=json

Write-Host ""
Write-Host "Review the output and clean duplicate or stale taxonomy items before rerunning the batch import."
