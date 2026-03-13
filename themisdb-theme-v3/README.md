# ThemisDB Theme v3

**WordPress Block Theme (Full Site Editing)** für ThemisDB — inspiriert von [postgresql.org](https://www.postgresql.org/) und [Microsoft Azure](https://azure.microsoft.com/de-de/).

Version: **3.0.0** | Requires WordPress: **6.3+** | PHP: **7.4+**

---

## Inhalt

1. [Was ist dieses Theme?](#was-ist-dieses-theme)
2. [Was ist neu in v3?](#was-ist-neu-in-v3)
3. [Theme aktivieren](#theme-aktivieren)
4. [Struktur](#struktur)
5. [Templates](#templates)
6. [Template Parts](#template-parts)
7. [Block Patterns](#block-patterns)
8. [Design Tokens](#design-tokens)
9. [jQuery & Animationen](#jquery--animationen)
10. [Plugin-Kompatibilität](#plugin-kompatibilität)
11. [Landingpage einrichten](#landingpage-einrichten)
12. [Entwicklung](#entwicklung)

---

## Was ist dieses Theme?

`themisdb-theme-v3` ist ein **WordPress Block Theme** (Full Site Editing, FSE), das eine frische, alternative Designsprache für ThemisDB bietet — **ohne `themisdb-theme-v2` zu verändern**. Es bietet:

- **PostgreSQL × Azure Fluent Design** — Navy `#003366` + Azure-Blau `#0078d4` + Cyan `#50e6ff`
- **jQuery-gestützte Interaktionen** — Animierte Statistik-Counter, Scroll-Animationen, Tab-Panels, Accordion
- **Vollständige FSE-Unterstützung** — Site Editor, Global Styles, Template Editor
- **9 Block Patterns** — inkl. **2 neue Patterns**: Pricing-Sektion und Tabbed Feature-Showcase
- **Rückwärtskompatibilität** für alle ThemisDB-Plugins (CSS-Variablen-Aliases)
- **Breiteres Layout** — Content: 800px, Wide: 1200px
- **Robuste Sicherheit** — WP-Versionsinfo aus `<head>` entfernt, ABSPATH-Check

---

## Was ist neu in v3?

| Feature | v2 | v3 |
|---------|----|----|
| **Primärfarbe** | Blau-Grau `#2c3e50` | PostgreSQL Navy `#003366` |
| **Interaktivfarbe** | Bootstrap Blau `#3498db` | Azure Blau `#0078d4` |
| **Akzentfarbe** | Lila `#7c4dff` | Cyan `#50e6ff` |
| **Announcement Bar** | Lila | Azure-Blau |
| **Content-Breite** | 760px / 1100px | 800px / 1200px |
| **Border-Radius** | 6–10px | 12px (einheitlich) |
| **JavaScript** | Vanilla JS | Vanilla JS + jQuery-Animationen |
| **Neue Patterns** | – | `pricing-section`, `tabs-section` |
| **Stat-Counter** | Statisch | Animiert (count-up via IntersectionObserver) |
| **Scroll-Animationen** | – | Fade-in, Slide-up beim Scrollen |
| **jQuery Tabs/Accordion** | – | `.themis-v3-tabs`, `.themis-v3-accordion` |

---

## Theme aktivieren

1. Stelle sicher, dass der Ordner `themisdb-theme-v3` unter `wp-content/themes/` liegt.
2. Gehe in WordPress zu **Darstellung → Themes**.
3. Wähle **ThemisDB v3** und klicke **Aktivieren**.
4. Navigiere zu **Darstellung → Editor** (Site Editor), um Header, Footer und Startseite anzupassen.

> ⚠️ `themisdb-theme-v2` und `themisdb-theme` bleiben unverändert und können jederzeit reaktiviert werden.

---

## Struktur

```
themisdb-theme-v3/
├── style.css                    # Theme-Header + CSS Custom Properties (--tv3-*)
├── theme.json                   # Design-Tokens: Palette, Typografie, Spacing, Layout
├── functions.php                # Theme-Setup, jQuery-Enqueue, Pattern-Kategorien
│
├── templates/
│   ├── front-page.html          # ⭐ Azure-Style Landingpage (Hero + Stats + Features + Pricing)
│   ├── index.html               # Blog-Index (3-Spalten Grid)
│   ├── page.html                # Standard-Seite
│   ├── single.html              # Einzelner Beitrag mit Meta + Navigation
│   ├── 404.html                 # 404-Fehlerseite
│   ├── page-docs.html           # Dokumentations-Layout mit Sidebar
│   └── page-full-width.html     # Vollbreite-Seite
│
├── parts/
│   ├── header.html              # Navy Sticky-Header (Logo + Nav + CTA)
│   ├── header-docs.html         # Docs-Variante
│   ├── footer.html              # 4-Spalten Dark Footer (Navy)
│   ├── announcement-bar.html    # Azure-Blau Release-Topbar
│   ├── page-hero.html           # Grauer Seiten-Titel-Block
│   ├── breadcrumbs.html         # Schema.org Breadcrumb-Navigation
│   ├── post-meta.html           # Autor + Datum + Lesezeit + Tags
│   ├── post-navigation.html     # Prev/Next Artikel-Navigation
│   ├── sidebar-docs.html        # Sticky Docs-Sidebar mit TOC
│   ├── cta-banner.html          # Gradient CTA-Strip (Download/Docs)
│   ├── search-bar.html          # Suchfeld-Komponente
│   └── 404-content.html         # 404-Inhalt mit Suche + Schnelllinks
│
├── patterns/
│   ├── hero-home.php            # Vollbreite Hero (Navy+Cyan) mit CTAs + Docker-Snippet
│   ├── feature-cards.php        # 6 Feature-Kacheln (3×2 Grid)
│   ├── stats-bar.php            # 4 animierte Statistik-Zahlen (count-up)
│   ├── cta-download.php         # Download-Optionen (Docker/Binary/Compendium)
│   ├── docs-grid.php            # 8 Docs/Ressourcen-Kacheln
│   ├── query-showcase.php       # Code-Demo mit SQL/JSON/Python Tabs
│   ├── pricing-section.php      # ⭐ NEU: Free vs Enterprise Pricing Cards
│   ├── tabs-section.php         # ⭐ NEU: jQuery-Tabbed Feature-Showcase
│   └── testimonial.php          # 3 Testimonial-Cards
│
└── assets/
    ├── css/
    │   └── editor.css           # Editor-Styles (Gutenberg)
    └── js/
        ├── navigation.js        # Vanilla JS: Mobile Menu, Scroll-Header, Copy-Buttons
        └── animations.js        # jQuery: Counter-Animationen, Fade-in, Tabs, Accordion
```

---

## Templates

| Template | Beschreibung | Verwendung |
|---|---|---|
| `front-page.html` | Azure-Style Landingpage mit Hero, Stats, Features, Pricing | Startseite |
| `index.html` | Blog-Grid (3 Spalten) | Blog-Übersicht, Archive |
| `page.html` | Standard-Seite + Kommentare | Seiten allgemein |
| `single.html` | Artikel mit Meta, Prev/Next | Blog-Beiträge |
| `404.html` | 404 mit Suche + Quicklinks | Fehlerseite |
| `page-docs.html` | Zwei-Spalten Docs-Layout | Dokumentation |
| `page-full-width.html` | Volle Breite ohne Sidebar | Landing-Unterseiten |

---

## Template Parts

| Part | Bereich | Beschreibung |
|---|---|---|
| `header` | Header | Navy Sticky-Header, Logo + Nav + CTA |
| `header-docs` | Header | Docs-Variante mit Docs-Suche |
| `footer` | Footer | 4-Spalten Footer: Products / Resources / Community / Legal |
| `announcement-bar` | – | Azure-Blau Topbar für aktuelle Releases |
| `page-hero` | – | Grauer Seiten-Titel-Block |
| `breadcrumbs` | – | Schema.org konform |
| `post-meta` | – | Autorbild, Datum, Lesezeit, Tags |
| `post-navigation` | – | Prev/Next mit Titel-Preview |
| `sidebar-docs` | – | Sticky Docs-Navigation + Quick Links |
| `cta-banner` | – | Gradient CTA-Strip |
| `search-bar` | – | Suchfeld mit Quicklinks |
| `404-content` | – | 404-Inhalt mit Suche |

---

## Block Patterns

Alle Patterns sind im **Site Editor → Patterns** und im **Block-Inserter** unter **ThemisDB v3** verfügbar.

| Pattern | Slug | Einsatz |
|---|---|---|
| Hero – Home | `themisdb-v3/hero-home` | Startseiten-Hero |
| Feature Cards | `themisdb-v3/feature-cards` | Features-Übersicht |
| Stats Bar | `themisdb-v3/stats-bar` | Animierte Kennzahlen / Trust |
| CTA – Download | `themisdb-v3/cta-download` | Download-Sektion |
| Docs Grid | `themisdb-v3/docs-grid` | Ressourcen-Übersicht |
| Query Showcase | `themisdb-v3/query-showcase` | Code-Demo Sektion |
| **Pricing Section** | `themisdb-v3/pricing-section` | ⭐ NEU: Preisübersicht |
| **Tabs Section** | `themisdb-v3/tabs-section` | ⭐ NEU: jQuery Tab-Panel |
| Testimonial | `themisdb-v3/testimonial` | Social Proof |

---

## Design Tokens

Alle Design-Tokens sind als CSS Custom Properties mit dem Präfix `--tv3-` definiert.

```css
/* Brand Colors */
--tv3-navy:          #003366;   /* PostgreSQL navy – Header-Hintergrund */
--tv3-azure:         #0078d4;   /* Azure blue – Buttons, Links */
--tv3-azure-dark:    #005a9e;   /* Azure dunkel – Hover */
--tv3-cyan:          #50e6ff;   /* Azure cyan – Akzent, Badges */

/* Semantic */
--tv3-green:         #107c10;   /* Microsoft green – Erfolg */
--tv3-red:           #d13438;   /* Microsoft red – Fehler */
--tv3-yellow:        #ffb900;   /* Microsoft yellow – Warnung */

/* Layout */
--tv3-content-width: 800px;
--tv3-wide-width:    1200px;

/* Typography */
--tv3-font-sans:     -apple-system, BlinkMacSystemFont, "Segoe UI", …
--tv3-font-mono:     "Cascadia Code", "SFMono-Regular", Consolas, …
```

### Rückwärtskompatibilität

Das Theme mappt alle v2-CSS-Variablen auf v3-Tokens:

```css
--themis-primary:    var(--tv3-navy);
--themis-secondary:  var(--tv3-azure);
--primary-color:     var(--tv3-azure);
--themisdb-primary:  var(--tv3-navy);
```

---

## jQuery & Animationen

### `assets/js/navigation.js` (Vanilla JS, kein jQuery)
- Mobile-Menu Toggle (Aria-Labels)
- Announcement Bar Dismiss (sessionStorage)
- Scroll-aware Header (`is-scrolled` Klasse)
- Code Copy Buttons
- Active Nav Link Highlight

### `assets/js/animations.js` (jQuery required)

#### Animierte Statistik-Counter
```html
<span class="themis-v3-counter" data-target="500" data-suffix="K+">0</span>
```

#### Scroll Fade-in
```html
<div class="themis-v3-fade-in">Dieser Block blendet sich beim Scrollen ein</div>
```

#### jQuery Tabs
```html
<div class="themis-v3-tabs">
    <ul>
        <li><a href="#tab-1">Tab 1</a></li>
        <li><a href="#tab-2">Tab 2</a></li>
    </ul>
    <div id="tab-1">Inhalt 1</div>
    <div id="tab-2">Inhalt 2</div>
</div>
```

#### jQuery Accordion
```html
<div class="themis-v3-accordion">
    <h3>Frage 1</h3>
    <div><p>Antwort 1</p></div>
</div>
```

---

## Plugin-Kompatibilität

Das Theme styled alle ThemisDB-Plugin-Ausgaben automatisch:

| Plugin | Shortcode | CSS-Klasse |
|---|---|---|
| Feature Matrix | `[themisdb_feature_matrix]` | `.themisdb-feature-wrapper` |
| Benchmark Visualizer | `[themisdb_benchmark_visualizer]` | `.themisdb-benchmark-wrapper` |
| Docker Downloads | `[themisdb_docker_tags]`, `[themisdb_docker_latest]` | `.themisdb-docker-wrapper` |
| Downloads | `[themisdb_downloads]`, `[themisdb_latest]` | `.themisdb-downloads-wrapper` |
| Compendium | `[themisdb_compendium_downloads]` | `.themisdb-downloads-wrapper` |
| Release Timeline | `[themisdb_release_timeline]` | `.themisdb-timeline-wrapper` |
| Order/License | `[themisdb_order_flow]`, `[themisdb_license_portal]` | `.themisdb-order-form` |
| Taxonomy | `[themisdb_taxonomy]`, `[themisdb_term_card]` | `.themisdb-taxonomy-list` |
| Gallery | `[themisdb_gallery]` | `.themisdb-gallery-grid` |
| Architecture | `[themisdb_architecture]` | `.themisdb-diagram-wrapper` |
| Query Playground | Shortcode | `.themisdb-playground-wrapper` |
| TCO Calculator | `[themisdb_tco_calculator]` | `.themisdb-tco-wrapper` |
| Formula Renderer | `[themisdb_formula]` | `.themisdb-formula-wrapper` |

---

## Landingpage einrichten

### Schritt 1: Startseite konfigurieren
1. **Einstellungen → Lesen** → „Eine statische Seite anzeigen"
2. Neue Seite „Home" erstellen
3. Template `front-page` wird automatisch zugewiesen

### Schritt 2: Navigationsmenü anlegen
1. **Darstellung → Editor → Navigation**
2. Menü-Einträge: Features / Docs / Downloads / Benchmarks / Community / Blog
3. Das Theme ordnet dieses Menü dem Header automatisch zu

### Schritt 3: Announcement Bar anpassen
Datei `parts/announcement-bar.html` im Site Editor bearbeiten.

### Schritt 4: Stats-Counter konfigurieren
Im `stats-bar`-Pattern die `data-target`-Werte der `.themis-v3-counter`-Elemente anpassen.

### Schritt 5: Pricing-Sektion einbinden
Im **Block-Inserter** → **ThemisDB v3 – Landing Page** → **Pricing Section** einfügen.

---

## Entwicklung

### Anforderungen
- WordPress 6.3+
- PHP 7.4+
- jQuery (von WordPress bereitgestellt – kein zusätzliches Setup nötig)
- Node.js nicht erforderlich

### CSS-Anpassungen
```css
/* In einer Child-Theme style.css */
:root {
    --tv3-azure: #0066cc;       /* Eigene Akzentfarbe */
    --tv3-wide-width: 1400px;   /* Breiteres Layout */
}
```

### Child-Theme
```php
// child-theme/functions.php
add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_style(
        'child-style',
        get_stylesheet_uri(),
        array('themisdb-v3-style'),
        '1.0.0'
    );
} );
```

---

## Changelog

### v3.0.0 (2025)
- Initiales Release als alternatives WordPress Block Theme (FSE)
- PostgreSQL × Azure Fluent Design (Navy + Azure-Blau + Cyan)
- 7 Templates, 12 Template Parts, 9 Block Patterns
- 2 neue Patterns: Pricing Section & jQuery Tab-Showcase
- jQuery-Animationen: count-up Counter, Scroll Fade-in, Tabs, Accordion
- Breiteres Layout (1200px wide), 12px Border-Radius
- Vollständige Plugin-Kompatibilität (alle ThemisDB-Plugins)
- `--tv3-*` CSS-Token-System + Rückwärtskompatibilität für `--themis-*`, `--themisdb-*`

---

*ThemisDB v3 Theme — MIT License — https://github.com/makr-code/wordpressPlugins*
