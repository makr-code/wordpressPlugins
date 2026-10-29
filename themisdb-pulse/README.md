# ThemisDB Pulse

**WordPress Block Theme (Full Site Editing)** für ThemisDB — inspiriert von [postgresql.org](https://www.postgresql.org/) und [Microsoft Azure](https://azure.microsoft.com/de-de/).

Version: **3.5.0** | Requires WordPress: **7.1+** | PHP: **7.4+**

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
9. [Plugin-Kompatibilität](#plugin-kompatibilität)
10. [Landingpage einrichten](#landingpage-einrichten)
11. [Entwicklung](#entwicklung)

---

## Was ist dieses Theme?

`themisdb-pulse` ist das **Master Block Theme** (Full Site Editing, FSE) für ThemisDB mit Azure-inspirierter Oberfläche und WordPress-nativer Editor-Architektur. Es bietet:

- **PostgreSQL × Azure Fluent Design** — Navy `#003366` + Azure-Blau `#0078d4` + Cyan `#50e6ff`
- **WordPress-native Oberfläche** — Navigation, Suche, Layout und Inhaltsblöcke basieren auf Gutenberg und `theme.json`
- **Vollständige FSE-Unterstützung** — Site Editor, Global Styles, Template Editor
- **Block Patterns** — wiederverwendbare Layouts für redaktionelle Inhalte
- **Gezielte Integrationen** für ThemisDB-Funktionen, die WordPress-Core nicht bereitstellt
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
| **JavaScript** | Vanilla JS | Nur erforderliche Frontend-Interaktionen |
| **Neue Patterns** | – | `pricing-section`, `tabs-section` |
| **Stat-Counter** | Statisch | Serverseitig gerenderte Inhaltszahlen |

### v3.4 – Zugänglichkeit & visuelle Verfeinerung

- deutlich sichtbare `:focus-visible`-Ringe für Tastatur-Navigation
- bessere Kontrastwerte für Buttons, Links, Formularfelder und Cards
- reduzierter Motion-Fallback bei `prefers-reduced-motion`
- weichere Schatten, klarere Card-Layouts und optische Einheiten für eine attraktivere Startseite und Inhaltsseiten

### Einheitliche Seiten- und Beitragsgestaltung

- Die Standard-Templates `page.html` und `single.html` nutzen denselben nativen
  Template-Part `parts/content-header.html` mit Titel, Metadaten und optionalem
  Beitragsbild und einem nativen Auszug. Er folgt der Hero-Slider-Komposition:
  direkt unter dem Header, ueber die gesamte Breite, mit linksbuendigem Text und
  optionalem Beitragsbild als abgedunkeltem Hintergrund. Ohne Bild bleibt der
  Farbverlauf sichtbar; lange Titel und Auszuege vergroessern die Hoehe.
- Beitraege nutzen Petrol, Seiten ein gedecktes Violett statt des Navy-Blaus der
  Startseite. Beide bleiben statisch: keine Slider-Pfeile, kein Autoplay und
  keine Abhaengigkeit vom Slider-Plugin. Die Farbvariablen
  `--tv3-content-hero-start` / `--tv3-content-hero-end` sind gezielt anpassbar.
- Wie die Startseite verwenden beide einen 1.200-px-Aussenrahmen. Fliesstext bleibt
  in einer maximal 800 px breiten Lesespalte; Gutenberg-Bloecke mit `alignwide`
  oder `alignfull` koennen die verfuegbare Inhaltsbreite nutzen.
- Die Artikeloberflaechen folgen den vorhandenen `--tv3-*`-Tokens und damit dem
  gewaehlten Farbschema; der Hero hat eigene kontrastreiche Farben. Der Artikel selbst ist keine zusaetzliche verschachtelte
  Karte; gezielte Callouts, Medien und Plugin-Komponenten behalten ihre Gestaltung.
- Breadcrumbs stehen einheitlich unter dem Inhaltskopf. Autoren, Qualitaet,
  Medien, Schlagwoerter, Beitragsnavigation und Kommentare bleiben erhalten.
  Dokumentations- und andere Spezial-Templates behalten ihre eigene Struktur.
- Der gemeinsame Header bleibt einzeilig. Unter 1.200 px oeffnet die native
  WordPress-Navigation ein Menue-Overlay statt die Produktlinks umzubrechen.
  Die native Suchschaltflaeche klappt das Suchfeld ueber der Kopfzeile aus, ohne
  deren Hoehe zu veraendern. Graph und Anmeldung bleiben erreichbar; lange
  Seitennamen werden bei Platzmangel visuell gekuerzt.
- Alles bleibt im Site Editor bearbeitbar. Gespeicherte benutzerdefinierte
  Templates/Template-Parts haben wie in WordPress ueblich Vorrang vor Theme-Dateien.

Layout-Pruefung gegen eine lokale WordPress-Installation (ohne Testdaten anzulegen):

```powershell
& C:\xampp\php\php.exe .\themisdb-pulse\tests\test-content-layout.php C:\xampp\htdocs\wordpress\wp-load.php
node --test .\themisdb-pulse\tests\header-layout.test.cjs
```

Der Browser-Test verwendet das vorhandene Playwright im Repository und prueft
Einzeiligkeit, Breakpoints sowie Oeffnen/Schliessen und Fokus von nativem Menue
und Suche. Die Zielinstallation kann mit `WORDPRESS_URL` angepasst werden.

---

## Theme aktivieren

1. Stelle sicher, dass der Ordner `themisdb-pulse` unter `wp-content/themes/` liegt.
2. Gehe in WordPress zu **Darstellung → Themes**.
3. Wähle **ThemisDB Pulse** und klicke **Aktivieren**.
4. Navigiere zu **Darstellung → Editor** (Site Editor), um Header, Footer und Startseite anzupassen.

> ⚠️ `themisdb-theme-v2` und `themisdb-theme` bleiben unverändert und können jederzeit reaktiviert werden.

---

## Struktur

```
themisdb-pulse/
├── style.css                    # Theme-Header + CSS Custom Properties (--tv3-*)
├── theme.json                   # Design-Tokens: Palette, Typografie, Spacing, Layout
├── functions.php                # Theme-Setup, notwendige Integrationen, Pattern-Kategorien
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
│   ├── stats-bar.php            # Serverseitig berechnete Inhaltszahlen
│   ├── cta-download.php         # Download-Optionen (Docker/Binary/Compendium)
│   ├── docs-grid.php            # 8 Docs/Ressourcen-Kacheln
│   ├── query-showcase.php       # Code-Demo mit SQL/JSON/Python Tabs
│   ├── pricing-section.php      # ⭐ NEU: Free vs Enterprise Pricing Cards
│   ├── tabs-section.php         # Feature-Showcase mit Gutenberg-Core-Tabs
│   └── testimonial.php          # 3 Testimonial-Cards
│
└── assets/
    ├── css/                     # Ergänzende Frontend-Styles
    └── js/                      # Seiten- und blockbezogene Interaktionen
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

Alle Patterns sind im **Site Editor → Patterns** und im **Block-Inserter** unter **ThemisDB Pulse** verfügbar.

| Pattern | Slug | Einsatz |
|---|---|---|
| Hero – Home | `themisdb-v3/hero-home` | Startseiten-Hero |
| Feature Cards | `themisdb-v3/feature-cards` | Features-Übersicht |
| Stats Bar | `themisdb-v3/stats-bar` | Serverseitig berechnete Kennzahlen |
| CTA – Download | `themisdb-v3/cta-download` | Download-Sektion |
| Docs Grid | `themisdb-v3/docs-grid` | Ressourcen-Übersicht |
| Query Showcase | `themisdb-v3/query-showcase` | Code-Demo Sektion |
| **Pricing Section** | `themisdb-v3/pricing-section` | ⭐ NEU: Preisübersicht |
| **Tabs Section** | `themisdb-v3/tabs-section` | Gutenberg-Core-Tabs für Feature-Übersichten |
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

## WordPress-native Oberfläche

Navigation, Suche, Layout und Inhaltslisten verwenden Gutenberg-Core-Blöcke und
`theme.json`, sofern keine spezielle ThemisDB-Funktion erforderlich ist.
Zusätzliche Skripte werden nur für tatsächlich benötigte Theme- oder
Plugin-Interaktionen geladen.

Interne Theme-Links werden beim Rendern mit WordPress-APIs auf vorhandene
Permalinks aufgelöst. Nicht zuordenbare Pfade verwenden weiterhin `home_url()`,
damit Installationen in Unterverzeichnissen funktionieren.

## Plugin-Kompatibilität

### Support-/Order-Integration

Der eigene Shop ist vorgesehen; WooCommerce dient nur als Ideengeber.
Plugins liefern Funktion, Markup und Basisstile, Pulse liefert die visuelle
Palette ueber `--themisdb-ui-*` auf den Shop-, Order-, Produkt-, Warenkorb-
und Support-Wrappers. Diese Variablen werden aus den aktuellen `--tv3-*`-
Farbwerten abgeleitet. Plugin-CSS wird nicht mehr durch Theme-Erkennung
unterdrueckt; ohne Pulse gelten die eigenstaendigen Plugin-Defaults.

Der Lizenzeinstieg ist `/products/`. Der Header verwendet
`[themisdb_cart_button]` fuer das Warenkorbsymbol; Warenkorb und Checkout
laufen in einem gemeinsamen plugin-eigenen Overlay, nicht auf separaten Seiten.
`/support/` bleibt das Kundenportal. Shortcodes, Overlay-Verhalten und
die Farbvariablen sind in der
[Order-Dokumentation](../themisdb-order-request/README.md#wordpress-einbindung-eigener-shop-und-supportportal)
beschrieben. Der mobile Breadcrumb bleibt innerhalb seiner Breite horizontal
scrollbar, damit lange Shop-Ankerlisten die Seite nicht verbreitern.

Pulse bezieht Support-Assets und lokalisierte Meldungen ueber
`themisdb_support_enqueue_frontend_assets()` direkt vom Plugin, statt seine
Konfiguration zu duplizieren. Automatische Portal-Einbettungen bleiben beim
Plugin; der Theme-Content-Filter dient nur noch als Legacy-Fallback. Spezielle
Support-Seitenvorlagen und das kontextbezogene Login-Overlay bleiben erhalten.
Der Auftragsdialog im Support-Hub gehoert ebenfalls dem Plugin; Pulse
aktiviert seinen alten Handler nur ohne die entsprechende Plugin-Kennung.

### Benchmark-Design

Pulse passt den eigenstaendigen Benchmark Visualizer ausschliesslich ueber
`--themisdb-bv-*`-Tokens in `style.css` an die aktuelle `--tv3-*`-Farbpalette an.
Das umfasst Oberflaechen, Texte, Bedienelemente, Diagrammreihen, Achsen und Tooltips
sowie Schrift, Radius und Schatten. PHP, HTML, JavaScript und Layout bleiben
beim Plugin; seine CSS-Datei muss aktiviert bleiben. Ohne Pulse verwendet das
Plugin seine eigenen hellen/dunklen Standardfarben. Explizite Theme-Tokens haben
Vorrang vor der im Plugin gespeicherten Standardpalette.

Das Theme styled alle ThemisDB-Plugin-Ausgaben automatisch:

| Plugin | Shortcode | CSS-Klasse |
|---|---|---|
| Feature Matrix | `[themisdb_feature_matrix]` | `.themisdb-feature-wrapper` |
| Benchmark Visualizer | `[themisdb_benchmark_visualizer]` | `.themisdb-benchmark-wrapper` |
| Docker Downloads | `[themisdb_docker_tags]`, `[themisdb_docker_latest]` | `.themisdb-docker-downloads-container` |
| Downloads | `[themisdb_downloads]`, `[themisdb_latest]` | `.themisdb-downloads-wrapper` |
| Compendium | `[themisdb_compendium_downloads]` | `.themisdb-downloads-wrapper` |
| Release Timeline | `[themisdb_release_timeline]` | `.themisdb-timeline-wrapper` |
| Order/License | `[themisdb_order_flow]`, `[themisdb_license_portal]` | `.themisdb-order-form`, `.themisdb-order-flow`, `.themisdb-license-portal` |
| Taxonomy | `[themisdb_taxonomy]`, `[themisdb_term_card]` | `.themisdb-taxonomy-list` |
| Gallery | `[themisdb_gallery]` | `.themisdb-gallery-grid` |
| Architecture | `[themisdb_architecture]` | `.themisdb-diagram-container`, `.themisdb-architecture-wrapper` |
| Query Playground | Shortcode | `.themisdb-playground-wrapper`, `.themisdb-query-wrapper` |
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
Das `stats-bar`-Pattern berechnet die angezeigten Inhaltszahlen serverseitig.

### Schritt 5: Pricing-Sektion einbinden
Im **Block-Inserter** → **ThemisDB v3 – Landing Page** → **Pricing Section** einfügen.

---

## Relevance Card Grid (Mixed Content)

Das Theme bietet jetzt ein internes Relevanz-Grid fuer gemischte Inhalte (Artikel, Seiten, Podcast-Episoden) als Cards mit variabler Groesse.

### Shortcode

```text
[themisdb_v3_relevance_grid posts_per_page="24" min_relevance="0"]
```

### Was wird dargestellt?
- `post`
- `page`
- `pod_episode` (nur wenn der Podcast-CPT aktiv ist)

### Card-Groessen als Vielfache
- `1x`: Standard-Card (`1x1`)
- `2x`: Breite oder hohe Card (`2x1` oder `1x2`)
- `4x`: Hero-Card (`2x2`)

### Internes Gewichtungssystem (Relevanz)
- `relevance_score` Meta (0..1 oder 0..100)
- `priority` Meta (numerisch)
- `featured` Meta (Flag)
- Sticky-Boost fuer normale Artikel
- Engagement-Boost ueber Kommentaranzahl
- Recency-Boost (neuere Inhalte groesser)
- Podcast-Bonus fuer `pod_episode`

### Erweiterbarkeit
- Filter fuer Score: `themisdb_v3_relevance_score`
- Filter fuer Span-Klasse: `themisdb_v3_relevance_span_class`

Damit kann ein internes Ranking-System die Card-Flaeche direkt steuern, ohne Plugin-Fork.

---

## Entwicklung

### Anforderungen
- WordPress 7.1+
- PHP 7.4+
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
- 2 neue Patterns: Pricing Section & Tab-Showcase
- Breiteres Layout (1200px wide), 12px Border-Radius
- Vollständige Plugin-Kompatibilität (alle ThemisDB-Plugins)
- `--tv3-*` CSS-Token-System

---

*ThemisDB v3 Theme — MIT License — https://github.com/makr-code/wordpressPlugins*
