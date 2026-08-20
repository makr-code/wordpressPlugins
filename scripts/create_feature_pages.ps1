$ErrorActionPreference = 'Stop'

$baseUrl = 'http://localhost/wordpress'
$pages = @(
    @{title='Benchmarks'; slug='benchmarks'; content='Leistungstests und Benchmarks der ThemisDB Datenbank.'},
    @{title='Query Playground'; slug='query-playground'; content='Interaktiver Query Builder fuer ThemisDB Datenabfragen.'},
    @{title='Docker Setup'; slug='docker'; content='Docker-Integration und Container-Setup fuer ThemisDB.'},
    @{title='Advanced Analytics'; slug='advanced-analytics'; content='Erweiterte Analytics-Features fuer Datenbankabfragen.'},
    @{title='Vector Search'; slug='vector-search'; content='Vektorbasierte Suche und Similarity-Queries in ThemisDB.'},
    @{title='Real-time Sync'; slug='real-time-sync'; content='Echtzeit-Synchronisation von Datenaenderungen.'}
)

Write-Output "Creating feature pages..."
$createdIds = @()

foreach ($page in $pages) {
    $json = @{
        title = $page.title
        content = $page.content
        slug = $page.slug
        status = 'publish'
        type = 'page'
    } | ConvertTo-Json

    try {
        $response = Invoke-WebRequest -Uri "$baseUrl/wp-json/wp/v2/pages" `
            -Method Post `
            -ContentType 'application/json' `
            -Body $json `
            -SkipHttpErrorCheck
        
        if ($response.StatusCode -eq 201) {
            $result = ($response.Content | ConvertFrom-Json)
            $createdIds += $result.id
            Write-Output "[OK] Created: $($page.title) (ID: $($result.id))"
        } else {
            Write-Output "[FAIL] $($page.title) - Status: $($response.StatusCode)"
        }
    } catch {
        Write-Output "[ERROR] $($page.title) - Exception"
    }
}

Write-Output "Adding 'feature' tag to pages..."

try {
    $tagJson = @{
        name = 'feature'
        slug = 'feature'
    } | ConvertTo-Json

    $tagResponse = Invoke-WebRequest -Uri "$baseUrl/wp-json/wp/v2/tags" `
        -Method Post `
        -ContentType 'application/json' `
        -Body $tagJson `
        -SkipHttpErrorCheck

    if ($tagResponse.StatusCode -in @(201, 400)) {
        $tagData = ($tagResponse.Content | ConvertFrom-Json)
        $featureTagId = $tagData.id
        Write-Output "[OK] Feature tag ID: $featureTagId"
    }
} catch {
    Write-Output "[ERROR] Creating tag failed"
}

if ($featureTagId) {
    foreach ($pageId in $createdIds) {
        $tagJson = @{ tags = @($featureTagId) } | ConvertTo-Json
        try {
            $response = Invoke-WebRequest -Uri "$baseUrl/wp-json/wp/v2/pages/$pageId" `
                -Method Post `
                -ContentType 'application/json' `
                -Body $tagJson
            Write-Output "[OK] Tagged page ID $pageId"
        } catch {
            Write-Output "[ERROR] Tagging page $pageId failed"
        }
    }
}

Write-Output "Done!"
