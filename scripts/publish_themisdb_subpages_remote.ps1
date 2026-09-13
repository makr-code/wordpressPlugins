param(
    [string]$BaseUrl = $env:WP_BASE_URL,
    [string]$Username = $env:WP_USER,
    [string]$AppPassword = $env:WP_APP_PASSWORD,
    [ValidateSet('publish','draft')]
    [string]$Status = 'publish',
    [switch]$DryRun,
    [switch]$SkipPreflight
)

$ErrorActionPreference = 'Stop'

function Assert-NotEmpty {
    param(
        [string]$Value,
        [string]$Name
    )

    if ([string]::IsNullOrWhiteSpace($Value)) {
        throw "Required value missing: $Name"
    }
}

function New-WpHeaders {
    param(
        [string]$User,
        [string]$Password
    )

    $token = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes("${User}:${Password}"))
    return @{
        Authorization = "Basic $token"
        'Content-Type' = 'application/json'
    }
}

function Invoke-WpRequest {
    param(
        [ValidateSet('GET','POST')]
        [string]$Method,
        [string]$Uri,
        [hashtable]$Headers,
        [object]$Body
    )

    $params = @{
        Uri = $Uri
        Method = $Method
        Headers = $Headers
        SkipHttpErrorCheck = $true
    }

    if ($null -ne $Body) {
        $params['Body'] = ($Body | ConvertTo-Json -Depth 20)
    }

    $response = Invoke-WebRequest @params
    $content = $null

    if (-not [string]::IsNullOrWhiteSpace($response.Content)) {
        try {
            $content = $response.Content | ConvertFrom-Json -Depth 20
        } catch {
            $content = $response.Content
        }
    }

    return [pscustomobject]@{
        StatusCode = [int]$response.StatusCode
        Content = $content
        Raw = $response.Content
    }
}

function New-PagePayload {
    param(
        [hashtable]$Page,
        [string]$PageStatus
    )

    return @{
        title = $Page.title
        slug = $Page.slug
        status = $PageStatus
        type = 'page'
        excerpt = $Page.excerpt
        content = $Page.content
    }
}

function Ensure-MenuBar {
    param(
        [string]$ApiRoot,
        [hashtable]$Headers,
        [string]$MenuName,
        [string]$Location,
        [string[]]$PageSlugs,
        [string]$BaseUrl
    )

    $assignedMenu = Invoke-WpRequest -Method GET -Uri "$ApiRoot/menu-locations/$Location" -Headers $Headers -Body $null
    if ($assignedMenu.StatusCode -lt 200 -or $assignedMenu.StatusCode -ge 300) {
        throw "Unable to read menu location '$Location' (HTTP $($assignedMenu.StatusCode))."
    }

    $menuList = Invoke-WpRequest -Method GET -Uri "$ApiRoot/menus?per_page=100" -Headers $Headers -Body $null
    if ($menuList.StatusCode -lt 200 -or $menuList.StatusCode -ge 300) {
        throw "Unable to list menus for '$MenuName' (HTTP $($menuList.StatusCode))."
    }

    $menu = $null
    if ($assignedMenu.Content -and $assignedMenu.Content.menu) {
        $menuIdFromLocation = [int]$assignedMenu.Content.menu
        $menuFromLocation = @($menuList.Content | Where-Object { [int]$_.id -eq $menuIdFromLocation })[0]
        if ($menuFromLocation -and $menuFromLocation.name -eq $MenuName) {
            $menu = $menuFromLocation
        }
    }

    if (-not $menu) {
        $menu = @($menuList.Content | Where-Object { $_.name -eq $MenuName })[0]
    }

    if (-not $menu) {
        $createResult = Invoke-WpRequest -Method POST -Uri "$ApiRoot/menus" -Headers $Headers -Body @{ name = $MenuName }
        if ($createResult.StatusCode -lt 200 -or $createResult.StatusCode -ge 300) {
            throw "Menu creation failed for '$MenuName' (HTTP $($createResult.StatusCode))."
        }
        $menu = $createResult.Content
    }

    $menuId = [int]$menu.id
    $assignResult = Invoke-WpRequest -Method POST -Uri "$ApiRoot/menus/$menuId" -Headers $Headers -Body @{ locations = @($Location) }
    if ($assignResult.StatusCode -lt 200 -or $assignResult.StatusCode -ge 300) {
        throw "Menu assignment failed for location '$Location' via menu '$MenuName' (HTTP $($assignResult.StatusCode))."
    }

    $menuItemsResult = Invoke-WpRequest -Method GET -Uri "$ApiRoot/menu-items?menu=$menuId&per_page=100" -Headers $Headers -Body $null
    if ($menuItemsResult.StatusCode -lt 200 -or $menuItemsResult.StatusCode -ge 300) {
        throw "Unable to fetch menu items for '$MenuName' (HTTP $($menuItemsResult.StatusCode))."
    }

    $items = @($menuItemsResult.Content)
    $existingMap = @{}
    foreach ($item in $items) {
        if ($item.object -eq 'page' -and $item.object_id -gt 0) {
            $existingMap[[string]$item.object_id] = $item
        }
    }

    $pageOrder = 0
    foreach ($slug in $PageSlugs) {
        $lookupUrl = "$ApiRoot/pages?slug=$([Uri]::EscapeDataString($slug))&status=publish&per_page=20"
        $lookup = Invoke-WpRequest -Method GET -Uri $lookupUrl -Headers $Headers -Body $null
        if ($lookup.StatusCode -lt 200 -or $lookup.StatusCode -ge 300) {
            continue
        }

        $page = @($lookup.Content | Where-Object { $_.slug -eq $slug })[0]
        if (-not $page) {
            continue
        }

        $pageId = [int]$page.id
        $existingItem = $existingMap[[string]$pageId]
        $payload = @{
            menu_order = $pageOrder + 1
            title = if ($page.title -and $page.title.rendered) { [string]$page.title.rendered } else { [string]$slug }
            type = 'post_type'
            object = 'page'
            object_id = $pageId
            parent = 0
        }

        if ($existingItem) {
            $updateResult = Invoke-WpRequest -Method POST -Uri "$ApiRoot/menu-items/$($existingItem.id)" -Headers $Headers -Body $payload
            if ($updateResult.StatusCode -lt 200 -or $updateResult.StatusCode -ge 300) {
                throw "Menu item update failed for '$slug' in '$MenuName' (HTTP $($updateResult.StatusCode))."
            }
        } else {
            $createResult = Invoke-WpRequest -Method POST -Uri "$ApiRoot/menu-items" -Headers $Headers -Body @{ menu_id = $menuId; title = $payload.title; type = 'post_type'; object = 'page'; object_id = $pageId; parent = 0; menu_order = $pageOrder + 1 }
            if ($createResult.StatusCode -lt 200 -or $createResult.StatusCode -ge 300) {
                throw "Menu item creation failed for '$slug' in '$MenuName' (HTTP $($createResult.StatusCode))."
            }
        }

        $pageOrder++
    }

    Write-Output "Ensured menu '$MenuName' for location '$Location' at $BaseUrl"
}

function Get-RequiredShortcodes {
    param(
        [array]$Pages
    )

    $set = [System.Collections.Generic.HashSet[string]]::new([System.StringComparer]::OrdinalIgnoreCase)

    foreach ($page in $Pages) {
        $content = [string]$page.content
        $matches = [regex]::Matches($content, '\[(?!\/)([a-zA-Z0-9_:-]+)\b[^\]]*\]')
        foreach ($match in $matches) {
            $name = $match.Groups[1].Value
            if (-not [string]::IsNullOrWhiteSpace($name)) {
                [void]$set.Add($name)
            }
        }
    }

    return $set | Sort-Object
}

function Test-ShortcodeAvailability {
    param(
        [string]$ApiRoot,
        [hashtable]$Headers,
        [string[]]$Shortcodes
    )

    if (-not $Shortcodes -or $Shortcodes.Count -eq 0) {
        return
    }

    $missing = @()

    foreach ($shortcode in $Shortcodes) {
        $raw = "[$shortcode]"
        $encoded = [Uri]::EscapeDataString($raw)
        $uri = "$ApiRoot/block-renderer/core/shortcode?context=edit&attributes[text]=$encoded"
        $result = Invoke-WpRequest -Method GET -Uri $uri -Headers $Headers -Body $null

        if ($result.StatusCode -lt 200 -or $result.StatusCode -ge 300) {
            throw "Shortcode preflight failed for '$shortcode' with HTTP $($result.StatusCode). Use -SkipPreflight to bypass checks."
        }

        $rendered = ''
        if ($result.Content -and $result.Content.rendered) {
            $rendered = [string]$result.Content.rendered
        } elseif ($result.Raw) {
            $rendered = [string]$result.Raw
        }

        if ($rendered -match [regex]::Escape($raw)) {
            $missing += $shortcode
        }
    }

    if ($missing.Count -gt 0) {
        $list = ($missing | Sort-Object -Unique) -join ', '
        throw "Preflight found unresolved shortcodes: $list"
    }
}

Assert-NotEmpty -Value $BaseUrl -Name 'BaseUrl (or env WP_BASE_URL)'

$base = $BaseUrl.TrimEnd('/')
$apiRoot = "$base/wp-json/wp/v2"

$pages = @(
    @{
        title = 'Blog'
        slug = 'blog'
        excerpt = 'Neueste ThemisDB Artikel, Produkt-Updates und technische Deep Dives.'
        content = @'
<!-- wp:group {"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group">
<!-- wp:heading {"level":1} --><h1>ThemisDB Blog</h1><!-- /wp:heading -->
<!-- wp:paragraph --><p>Aktuelle Artikel rund um Architektur, Releases, Benchmarks und Best Practices.</p><!-- /wp:paragraph -->
<!-- wp:shortcode -->[themisdb_front_slider posts="5" interval="5000" autoplay="yes" excerpt="yes" date="yes" cat_label="yes" filter="hero,featured"]<!-- /wp:shortcode -->
<!-- wp:spacer {"height":"24px"} --><div aria-hidden="true" class="wp-block-spacer" style="height:24px"></div><!-- /wp:spacer -->
<!-- wp:themisdb/mixed-cards {"limit":12,"postType":"post"} /-->
</div>
<!-- /wp:group -->
'@
    }
    @{
        title = 'Beitraege'
        slug = 'beitraege'
        excerpt = 'Zentrale Artikeluebersicht mit Hero-Slider und Infinity-Matrix.'
        content = @'
<!-- wp:group {"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group">
<!-- wp:heading {"level":1} --><h1>ThemisDB Beitraege</h1><!-- /wp:heading -->
<!-- wp:paragraph --><p>Alle redaktionellen Beitraege in einer durchsuchbaren, progressiv ladenden Kartenansicht.</p><!-- /wp:paragraph -->
<!-- wp:shortcode -->[themisdb_front_slider posts="5" interval="5000" autoplay="yes" excerpt="yes" date="yes" cat_label="yes" filter="hero,featured"]<!-- /wp:shortcode -->
<!-- wp:spacer {"height":"24px"} --><div aria-hidden="true" class="wp-block-spacer" style="height:24px"></div><!-- /wp:spacer -->
<!-- wp:themisdb/mixed-cards {"limit":12,"postType":"post"} /-->
</div>
<!-- /wp:group -->
'@
    }
    @{
        title = 'Features'
        slug = 'features'
        excerpt = 'Produktfunktionen, Vergleich und Architektur-Highlights von ThemisDB.'
        content = @'
<!-- wp:group {"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group">
<!-- wp:heading {"level":1} --><h1>ThemisDB Features</h1><!-- /wp:heading -->
<!-- wp:paragraph --><p>Von Multi-Model und Vektorsuche bis zu verteilten Workloads: die wichtigsten Capabilities im Ueberblick.</p><!-- /wp:paragraph -->
<!-- wp:shortcode -->[themisdb_v3_feature_cards limit="9" post_types="post,page" category="features" priority_tag="frontpage-feature,hero,featured" excerpt_words="22"]<!-- /wp:shortcode -->
<!-- wp:spacer {"height":"28px"} --><div aria-hidden="true" class="wp-block-spacer" style="height:28px"></div><!-- /wp:spacer -->
<!-- wp:shortcode -->[themisdb_feature_matrix]<!-- /wp:shortcode -->
</div>
<!-- /wp:group -->
'@
    }
    @{
        title = 'Dokumentation'
        slug = 'documentation'
        excerpt = 'Strukturierte ThemisDB Dokumentation fuer Betrieb, Entwicklung und Integration.'
        content = @'
<!-- wp:group {"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group">
<!-- wp:heading {"level":1} --><h1>ThemisDB Dokumentation</h1><!-- /wp:heading -->
<!-- wp:paragraph --><p>Guides, Referenzen und praxisnahe How-Tos fuer Teams, die ThemisDB produktiv betreiben.</p><!-- /wp:paragraph -->
<!-- wp:shortcode -->[themisdb_v3_docs_cards limit="9" post_types="page,post" category="documentation,docs" priority_tag="frontpage-documentation,documentation,docs" excerpt_words="22"]<!-- /wp:shortcode -->
</div>
<!-- /wp:group -->
'@
    }
    @{
        title = 'Pricing'
        slug = 'pricing'
        excerpt = 'Editionen, Leistungsumfang und Einstiegsoptionen fuer ThemisDB.'
        content = @'
<!-- wp:group {"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group">
<!-- wp:heading {"level":1} --><h1>ThemisDB Pricing</h1><!-- /wp:heading -->
<!-- wp:paragraph --><p>Vergleiche Community, Enterprise und Managed Optionen inkl. klarer Entscheidungsgrundlage.</p><!-- /wp:paragraph -->
<!-- wp:shortcode -->[themisdb_v3_pricing_cards limit="3" post_types="page,post" category="pricing" priority_tag="pricing-featured,frontpage-feature"]<!-- /wp:shortcode -->
</div>
<!-- /wp:group -->
'@
    }
    @{
        title = 'Support'
        slug = 'support'
        excerpt = 'Support Hub, Lifecycle-Portal und Kunden-Cockpit fuer ThemisDB.'
        content = @'
<!-- wp:group {"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group">
<!-- wp:heading {"level":1} --><h1>ThemisDB Support</h1><!-- /wp:heading -->
<!-- wp:paragraph --><p>Support-Kanaele, Lifecycle-Anfragen und Cockpit in einer zentralen Seite.</p><!-- /wp:paragraph -->
<!-- wp:shortcode -->[themisdb_support_hub]<!-- /wp:shortcode -->
<!-- wp:spacer {"height":"24px"} --><div aria-hidden="true" class="wp-block-spacer" style="height:24px"></div><!-- /wp:spacer -->
<!-- wp:shortcode -->[themisdb_lifecycle_portal]<!-- /wp:shortcode -->
<!-- wp:spacer {"height":"24px"} --><div aria-hidden="true" class="wp-block-spacer" style="height:24px"></div><!-- /wp:spacer -->
<!-- wp:shortcode -->[themisdb_cockpit]<!-- /wp:shortcode -->
</div>
<!-- /wp:group -->
'@
    }
    @{
        title = 'Shop'
        slug = 'shop'
        excerpt = 'Produkt- und Angebotsseite fuer ThemisDB Editionen.'
        content = @'
<!-- wp:group {"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group">
<!-- wp:heading {"level":1} --><h1>ThemisDB Shop</h1><!-- /wp:heading -->
<!-- wp:paragraph --><p>Editionen vergleichen und direkt den passenden Angebotsprozess starten.</p><!-- /wp:paragraph -->
<!-- wp:shortcode -->[themisdb_shop preferred_edition="enterprise"]<!-- /wp:shortcode -->
</div>
<!-- /wp:group -->
'@
    }
    @{
        title = 'Download'
        slug = 'download'
        excerpt = 'Aktuelle Releases, Docker-Tags und Compendium-Downloads.'
        content = @'
<!-- wp:group {"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group">
<!-- wp:heading {"level":1} --><h1>ThemisDB Download</h1><!-- /wp:heading -->
<!-- wp:paragraph --><p>Stabile Releases, Docker-Artefakte und verifizierte Download-Pakete.</p><!-- /wp:paragraph -->
<!-- wp:shortcode -->[themisdb_latest]<!-- /wp:shortcode -->
<!-- wp:spacer {"height":"24px"} --><div aria-hidden="true" class="wp-block-spacer" style="height:24px"></div><!-- /wp:spacer -->
<!-- wp:shortcode -->[themisdb_downloads]<!-- /wp:shortcode -->
<!-- wp:spacer {"height":"24px"} --><div aria-hidden="true" class="wp-block-spacer" style="height:24px"></div><!-- /wp:spacer -->
<!-- wp:shortcode -->[themisdb_docker_latest] [themisdb_docker_tags]<!-- /wp:shortcode -->
<!-- wp:spacer {"height":"24px"} --><div aria-hidden="true" class="wp-block-spacer" style="height:24px"></div><!-- /wp:spacer -->
<!-- wp:shortcode -->[themisdb_compendium_downloads]<!-- /wp:shortcode -->
</div>
<!-- /wp:group -->
'@
    }
    @{
        title = 'Downloads'
        slug = 'downloads'
        excerpt = 'Alias-Seite fuer Download-Inhalte und Releases.'
        content = @'
<!-- wp:group {"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group">
<!-- wp:heading {"level":1} --><h1>ThemisDB Downloads</h1><!-- /wp:heading -->
<!-- wp:shortcode -->[themisdb_latest]<!-- /wp:shortcode -->
<!-- wp:spacer {"height":"24px"} --><div aria-hidden="true" class="wp-block-spacer" style="height:24px"></div><!-- /wp:spacer -->
<!-- wp:shortcode -->[themisdb_downloads]<!-- /wp:shortcode -->
<!-- wp:spacer {"height":"24px"} --><div aria-hidden="true" class="wp-block-spacer" style="height:24px"></div><!-- /wp:spacer -->
<!-- wp:shortcode -->[themisdb_docker_latest] [themisdb_docker_tags]<!-- /wp:shortcode -->
<!-- wp:spacer {"height":"24px"} --><div aria-hidden="true" class="wp-block-spacer" style="height:24px"></div><!-- /wp:spacer -->
<!-- wp:shortcode -->[themisdb_compendium_downloads]<!-- /wp:shortcode -->
</div>
<!-- /wp:group -->
'@
    }
    @{
        title = 'Community'
        slug = 'community'
        excerpt = 'Community-News, Featured Posts und aktuelle Inhalte.'
        content = @'
<!-- wp:group {"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group">
<!-- wp:heading {"level":1} --><h1>ThemisDB Community</h1><!-- /wp:heading -->
<!-- wp:paragraph --><p>Neuigkeiten aus der Community, Events und empfohlene Beitraege.</p><!-- /wp:paragraph -->
<!-- wp:shortcode -->[themisdb_front_slider posts="5" interval="5000" autoplay="yes" excerpt="yes" date="yes" cat_label="yes" filter="community,featured"]<!-- /wp:shortcode -->
<!-- wp:spacer {"height":"24px"} --><div aria-hidden="true" class="wp-block-spacer" style="height:24px"></div><!-- /wp:spacer -->
<!-- wp:themisdb/mixed-cards {"limit":12,"postType":"post"} /-->
</div>
<!-- /wp:group -->
'@
    }
    @{
        title = 'Dokumentation'
        slug = 'dokumentation'
        excerpt = 'Alias-Seite fuer die deutschsprachige Dokumentation.'
        content = @'
<!-- wp:group {"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group">
<!-- wp:heading {"level":1} --><h1>ThemisDB Dokumentation</h1><!-- /wp:heading -->
<!-- wp:shortcode -->[themisdb_v3_docs_cards limit="9" post_types="page,post" category="documentation,docs" priority_tag="frontpage-documentation,documentation,docs" excerpt_words="22"]<!-- /wp:shortcode -->
<!-- wp:spacer {"height":"24px"} --><div aria-hidden="true" class="wp-block-spacer" style="height:24px"></div><!-- /wp:spacer -->
<!-- wp:themisdb/mixed-cards {"limit":12,"postType":"page","category":"documentation,docs"} /-->
</div>
<!-- /wp:group -->
'@
    }
)

Write-Output "Prepared $($pages.Count) subpages for upsert to $base"

$requiredShortcodes = Get-RequiredShortcodes -Pages $pages
Write-Output "Detected $($requiredShortcodes.Count) required shortcodes for this page set."

if ($DryRun) {
    Write-Output 'DryRun active. No remote writes will be executed.'
    Write-Output "- would configure header menu 'Primary Navigation' with pages: blog, features, documentation, pricing, support, download"
    Write-Output "- would configure footer menu 'Footer Navigation' with pages: support, documentation, blog, shop"
    foreach ($page in $pages) {
        Write-Output "- would upsert /$($page.slug)/ as status '$Status'"
    }
    exit 0
}

if ($SkipPreflight) {
    Write-Output 'Shortcode preflight skipped via -SkipPreflight.'
} elseif ([string]::IsNullOrWhiteSpace($Username) -or [string]::IsNullOrWhiteSpace($AppPassword)) {
    Write-Output 'Shortcode preflight skipped in DryRun (no credentials provided).'
} else {
    $headers = New-WpHeaders -User $Username -Password $AppPassword
    Test-ShortcodeAvailability -ApiRoot $apiRoot -Headers $headers -Shortcodes $requiredShortcodes
    Write-Output 'Shortcode preflight passed.'
}

Assert-NotEmpty -Value $Username -Name 'Username (or env WP_USER)'
Assert-NotEmpty -Value $AppPassword -Name 'AppPassword (or env WP_APP_PASSWORD)'

$headers = New-WpHeaders -User $Username -Password $AppPassword

if ($SkipPreflight) {
    Write-Output 'Shortcode preflight skipped via -SkipPreflight.'
} else {
    Test-ShortcodeAvailability -ApiRoot $apiRoot -Headers $headers -Shortcodes $requiredShortcodes
    Write-Output 'Shortcode preflight passed.'
}

$created = 0
$updated = 0

$headerPages = @('blog','features','documentation','pricing','support','download')
$footerPages = @('support','documentation','blog','shop')
Ensure-MenuBar -ApiRoot $apiRoot -Headers $headers -MenuName 'Primary Navigation' -Location 'primary' -PageSlugs $headerPages -BaseUrl $base
Ensure-MenuBar -ApiRoot $apiRoot -Headers $headers -MenuName 'Footer Navigation' -Location 'footer' -PageSlugs $footerPages -BaseUrl $base

Write-Output 'Menu bars configured for header and footer.'

$created = 0
$updated = 0

foreach ($page in $pages) {
    $slug = [Uri]::EscapeDataString([string]$page.slug)
    $lookupUrl = "$apiRoot/pages?slug=$slug&context=edit"
    $lookup = Invoke-WpRequest -Method GET -Uri $lookupUrl -Headers $headers -Body $null

    if ($lookup.StatusCode -lt 200 -or $lookup.StatusCode -ge 300) {
        throw "Lookup failed for slug '$($page.slug)' with HTTP $($lookup.StatusCode)."
    }

    $payload = New-PagePayload -Page $page -PageStatus $Status

    if ($lookup.Content -is [array] -and $lookup.Content.Count -gt 0) {
        $id = [int]$lookup.Content[0].id
        $updateUrl = "$apiRoot/pages/$id"
        $result = Invoke-WpRequest -Method POST -Uri $updateUrl -Headers $headers -Body $payload
        if ($result.StatusCode -ge 200 -and $result.StatusCode -lt 300) {
            $updated++
            Write-Output "UPDATED /$($page.slug)/ (ID $id)"
        } else {
            throw "Update failed for '/$($page.slug)/' with HTTP $($result.StatusCode)."
        }
    } else {
        $createUrl = "$apiRoot/pages"
        $result = Invoke-WpRequest -Method POST -Uri $createUrl -Headers $headers -Body $payload
        if ($result.StatusCode -eq 201 -or ($result.StatusCode -ge 200 -and $result.StatusCode -lt 300)) {
            $created++
            $newId = if ($result.Content -and $result.Content.id) { [int]$result.Content.id } else { 0 }
            Write-Output "CREATED /$($page.slug)/ (ID $newId)"
        } else {
            throw "Create failed for '/$($page.slug)/' with HTTP $($result.StatusCode)."
        }
    }
}

Write-Output "Done. Created: $created | Updated: $updated"

exit 0

if ($DryRun) {
    Write-Output 'DryRun active. No remote writes will be executed.'
    if ($SkipPreflight) {
        Write-Output 'Shortcode preflight skipped via -SkipPreflight.'
    } elseif ([string]::IsNullOrWhiteSpace($Username) -or [string]::IsNullOrWhiteSpace($AppPassword)) {
        Write-Output 'Shortcode preflight skipped in DryRun (no credentials provided).'
    } else {
        $headers = New-WpHeaders -User $Username -Password $AppPassword
        Test-ShortcodeAvailability -ApiRoot $apiRoot -Headers $headers -Shortcodes $requiredShortcodes
        Write-Output 'Shortcode preflight passed.'
    }

    foreach ($page in $pages) {
        Write-Output "- would upsert /$($page.slug)/ as status '$Status'"
    }
    exit 0
}

Assert-NotEmpty -Value $Username -Name 'Username (or env WP_USER)'
Assert-NotEmpty -Value $AppPassword -Name 'AppPassword (or env WP_APP_PASSWORD)'

$headers = New-WpHeaders -User $Username -Password $AppPassword

if ($SkipPreflight) {
    Write-Output 'Shortcode preflight skipped via -SkipPreflight.'
} else {
    Test-ShortcodeAvailability -ApiRoot $apiRoot -Headers $headers -Shortcodes $requiredShortcodes
    Write-Output 'Shortcode preflight passed.'
}

$created = 0
$updated = 0

$headerPages = @('blog','features','documentation','pricing','support','download')
$footerPages = @('support','documentation','blog','shop')
Ensure-MenuBar -ApiRoot $apiRoot -Headers $headers -MenuName 'Primary Navigation' -Location 'primary' -PageSlugs $headerPages -BaseUrl $base
Ensure-MenuBar -ApiRoot $apiRoot -Headers $headers -MenuName 'Footer Navigation' -Location 'footer' -PageSlugs $footerPages -BaseUrl $base

Write-Output 'Menu bars configured for header and footer.'

foreach ($page in $pages) {
    $slug = [Uri]::EscapeDataString([string]$page.slug)
    $lookupUrl = "$apiRoot/pages?slug=$slug&context=edit"
    $lookup = Invoke-WpRequest -Method GET -Uri $lookupUrl -Headers $headers -Body $null

    if ($lookup.StatusCode -lt 200 -or $lookup.StatusCode -ge 300) {
        throw "Lookup failed for slug '$($page.slug)' with HTTP $($lookup.StatusCode)."
    }

    $payload = New-PagePayload -Page $page -PageStatus $Status

    if ($lookup.Content -is [array] -and $lookup.Content.Count -gt 0) {
        $id = [int]$lookup.Content[0].id
        $updateUrl = "$apiRoot/pages/$id"
        $result = Invoke-WpRequest -Method POST -Uri $updateUrl -Headers $headers -Body $payload
        if ($result.StatusCode -ge 200 -and $result.StatusCode -lt 300) {
            $updated++
            Write-Output "UPDATED /$($page.slug)/ (ID $id)"
        } else {
            throw "Update failed for '/$($page.slug)/' with HTTP $($result.StatusCode)."
        }
    } else {
        $createUrl = "$apiRoot/pages"
        $result = Invoke-WpRequest -Method POST -Uri $createUrl -Headers $headers -Body $payload
        if ($result.StatusCode -eq 201 -or ($result.StatusCode -ge 200 -and $result.StatusCode -lt 300)) {
            $created++
            $newId = if ($result.Content -and $result.Content.id) { [int]$result.Content.id } else { 0 }
            Write-Output "CREATED /$($page.slug)/ (ID $newId)"
        } else {
            throw "Create failed for '/$($page.slug)/' with HTTP $($result.StatusCode)."
        }
    }
}

Write-Output "Done. Created: $created | Updated: $updated"