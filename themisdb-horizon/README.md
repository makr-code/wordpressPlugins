# ThemisDB

WordPress Block Theme (Full Site Editing) für ThemisDB.

---

## Voraussetzungen

| Anforderung | Version |
|-------------|---------|
| WordPress   | >= 6.3  |
| PHP         | >= 7.4  |
| Gutenberg   | Block Theme / FSE |

---

## Installation

1. Verzeichnis `themisdb-theme` in `wp-content/themes/` kopieren
2. Im WP-Admin: **Design → Themes → ThemisDB** aktivieren
3. Wappenbilder (SVG) in `assets/crests/` ablegen (s. u.)
4. Ggf. Kontakt-E-Mail unter **Design → Customizer → ThemisDB Einstellungen** konfigurieren

---

## Struktur

```
themisdb-theme/
├── style.css                  # Theme-Header + Custom CSS Design System
├── theme.json                 # Design-Tokens (Farben, Schriften, Abstände)
├── functions.php              # Theme-Setup, Enqueues, AJAX, Customizer
│
├── templates/
│   ├── front-page.html        # Hauptseite (alle 15 Sektionen)
│   ├── index.html             # Blog-Index-Fallback
│   ├── page.html              # Standard-Seite
│   ├── single.html            # Einzelner Beitrag
│   └── 404.html               # 404-Fehlerseite
│
├── parts/
│   ├── header.html            # Glas-Navbar mit Mobile-Menü
│   ├── footer.html            # Footer (3-Spalten, Rechtliches)
│   ├── legal-impressum.html   # Impressum-Akkordeon
│   └── legal-datenschutz.html # Datenschutz-Akkordeon
│
├── assets/
│   ├── js/
│   │   ├── navigation.js      # Mobile-Menü + Smooth Scroll
│   │   ├── hero-slider.js     # Slider (Autoplay, Dots, Pfeil, Touch)
│   │   ├── gallery.js         # Galerie-Filter + Lightbox
│   │   ├── faq.js             # FAQ-Akkordeon
│   │   ├── legal-accordion.js # Impressum/Datenschutz Toggle
│   │   ├── contact-form.js    # Formular-Validierung + AJAX-Submit
│   │   ├── mermaid-init.js    # Mermaid.js-Konfiguration
│   │   └── crest-loader.js    # Dynamisches Laden der Wappen-SVGs
│   ├── css/
│   │   └── editor.css         # Gutenberg-Editor-Stile
│   ├── crests/                # SVG-Wappendateien der Bundesländer (*)
│   └── gallery/               # Optionale lokale Gallery-Screenshots (**)
│
└── patterns/
    ├── hero-banner.php        # Hero-Block-Pattern
    └── stat-cards.php         # Statistik-Karten-Pattern
```

---

## Wappenbilder (*)

Die Wappendateien müssen manuell in `assets/crests/` abgelegt werden.  
Erwartete Dateinamen (Wikimedia-Commons-Konvention):

```
Coat_of_arms_of_Baden-Württemberg.svg
Coat_of_arms_of_Bayern.svg
Coat_of_arms_of_Berlin.svg
Coat_of_arms_of_Brandenburg.svg
Coat_of_arms_of_Bremen.svg
Coat_of_arms_of_Hamburg.svg
Coat_of_arms_of_Hesse.svg
Coat_of_arms_of_Mecklenburg-Vorpommern.svg
Coat_of_arms_of_Niedersachsen.svg
Coat_of_arms_of_North_Rhine-Westphalia.svg
Coat_of_arms_of_Rhineland-Palatinate.svg
Coat_of_arms_of_Saarlands.svg
Coat_of_arms_of_Sachsen-Anhalt.svg
Coat_of_arms_of_Saxony.svg
Coat_of_arms_of_Schleswig-Holstein.svg
Coat_of_arms_of_Thuringia.svg
```

Bei fehlendem Bild zeigt das Grid einen Zwei-Buchstaben-Platzhalter (z. B. `BB`).  
> Die Basis-URL ist im Customizer unter **ThemisDB Einstellungen → Wappen-Verzeichnis URL** konfigurierbar.

---

## Gallery-Screenshots (**)

Optionale lokale Screenshots in `assets/gallery/` ablegen.  
Der `front-page.html` nutzt derzeit Unsplash-Placeholder.  
Für Produktivbetrieb ersetzen durch eigene Screenshots der ThemisDB-Oberfläche.

---

## Customizer-Einstellungen

| Einstellung | Standard | Beschreibung |
|-------------|----------|--------------|
| Kontakt-E-Mail | `admin_email` | Empfänger der Systemzugang-Anfragen |
| Slider-Intervall (ms) | `5000` | Autoplay-Geschwindigkeit Hero-Slider |
| Wappen-Verzeichnis URL | `{theme}/assets/crests/` | Basis-URL für SVG-Wappen |

---

## Externe Abhängigkeiten (CDN)

| Bibliothek | CDN | Version |
|------------|-----|---------|
| Plus Jakarta Sans | Google Fonts | – |
| Font Awesome | cdnjs.cloudflare.com | 6.4.0 |
| Mermaid.js | cdn.jsdelivr.net | 11 |

> **Produktions-Hinweis:** Für DSGVO-konformen Betrieb sollten Google Fonts und CDN-Ressourcen durch selbst gehostete Versionen ersetzt werden.

---

## Test-Placeholders

Test-Placeholders (`.themisdb-test-placeholder`) werden nur noch für optionale Spezialfälle verwendet (z. B. Mermaid-Fallback bei fehlender Laufzeit).

Betroffene Shortcode-Kompatibilität im Theme (Darstellung bleibt im Theme):
- `[themisdb_latest]` – Neueste Releases
- `[themisdb_docker_latest]` – Docker Downloads
- `[themisdb_compendium_downloads]` – Compendium Downloads
- `[themisdb_benchmark_visualizer]` – Benchmark Visualizer

---

## Plugin-Kompatibilität

| Plugin | Status | Anmerkung |
|--------|--------|-----------|
| `themisdb-front-slider` | Optional | Plugin liefert Funktionen/Einstellungen; Darstellung (Block/Shortcode) übernimmt das Theme |
| Other ThemisDB Plugins | Kompatibel | Funktionen/Daten können via Theme-Filter und bekannte Shortcode-Tags integriert werden |

Architekturprinzip: Plugins liefern primär Funktionalität und Daten, die visuelle Darstellung wird im Theme gerendert.
Bekannte Integrations-Tags werden im Theme bewusst übernommen; Plugins können Daten/Defaults über `themisdb_theme_shortcode_defaults_*` und `themisdb_theme_shortcode_args_*` einspeisen.
Details und Copy-Paste-Beispiele für Plugin-Autoren: siehe `PLUGIN_INTEGRATION.md`.

Der Länderverbund ist im Theme nativ als Block `themisdb/state-grid` und zusätzlich als Shortcode `[themisdb_state_grid]` verfügbar.
Die Systemvorschau ist im Theme nativ als Block `themisdb/gallery-grid` und zusätzlich als Shortcode `[themisdb_gallery]` verfügbar.
Die Timeline ist im Theme nativ als Block `themisdb/section-timeline` und zusätzlich als Shortcode `[themisdb_changelog]` verfügbar.
Der Hero-Slider ist nativ als Block `themisdb/front-slider` und zusätzlich als Shortcode `[themisdb_front_slider]` verfügbar.

---

## Changelog

### 1.0.0 (Initial)
- Vollständiges WordPress FSE Block Theme für ThemisDB
- 15 Inhaltssektionen in `templates/front-page.html`
- 7 interaktive JavaScript-Module
- DSGVO-konforme Einräumung ohne externe Fonts/Tracking (wenn CDN ersetzt)
- Plugin-nahe Integrations-Tags mit Theme-Darstellung und optionaler Plugin-Funktionalität

---

**Federführung:** LfU Brandenburg | Referat T14  
**System:** ThemisDB  
**Kontakt:** über die in ThemisDB Einstellungen konfigurierte Kontakt-E-Mail
