# Create Landing Pages via WordPress REST API
$pages = @(
    @{ title = 'Blog'; slug = 'blog'; content = 'Blog - Aktuelle Artikel und News zu ThemisDB' },
    @{ title = 'Dokumentation'; slug = 'documentation'; content = 'Dokumentation - Alles was Sie ueber ThemisDB wissen muessen' },
    @{ title = 'Features'; slug = 'features'; content = 'Features - Entdecken Sie die Funktionen von ThemisDB' },
    @{ title = 'Downloads'; slug = 'downloads'; content = 'Downloads - Laden Sie ThemisDB herunter' },
    @{ title = 'Newsletter'; slug = 'newsletter'; content = 'Newsletter - Erhalten Sie Updates zu ThemisDB' },
    @{ title = 'Docker'; slug = 'docker'; content = 'Docker - Offizielles Docker Image fuer ThemisDB' }
)

$baseUrl = "http://localhost/wordpress/wp-json/wp/v2/pages"

foreach ($page in $pages) {
    $body = @{
        title = $page.title
        slug = $page.slug
        status = 'publish'
        content = $page.content
    } | ConvertTo-Json

    Write-Host "Creating: $($page.title) (/$($page.slug)/)"
    
    try {
        $response = Invoke-WebRequest -Uri $baseUrl `
            -Method POST `
            -Headers @{ 'Content-Type' = 'application/json' } `
            -Body $body `
            -UseBasicParsing
        
        Write-Host "[OK] Erstellt"
    } 
    catch {
        $statusCode = $_.Exception.Response.StatusCode.Value__
        if ($statusCode -eq 401) {
            Write-Host "[WARN] Authentifizierung erforderlich"
        } elseif ($statusCode -eq 404) {
            Write-Host "[WARN] REST API nicht verfuegbar"
        } else {
            Write-Host "[ERROR] $statusCode - $($_.Exception.Message)"
        }
    }
}
