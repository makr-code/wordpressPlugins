# ThemisDB Admin Modernization QA Checklist

## Ziel

Diese Checkliste dient der manuellen Abnahme der modernisierten ThemisDB-Admin-Oberflächen.

Geprüft werden sollen:

- konsistente WordPress-Admin-Struktur
- funktionierende Tabs und Toolbar-Aktionen
- modulare Karten- und Statusbereiche
- unveränderte Fachlogik hinter Formularen, Listen und CRUD-Aktionen
- stabile Navigation ohne visuelle oder funktionale Regressionen

## Voraussetzungen

- WordPress-Testinstanz mit aktivierten ThemisDB-Plugins
- Benutzer mit `manage_options`
- `WP_DEBUG` und `WP_DEBUG_LOG` aktiv
- Testdaten für CRUD-lastige Plugins, insbesondere `themisdb-order-request`

## Allgemeine UI-Prüfung fuer jede modernisierte Admin-Seite

Jede Seite gilt erst dann als abgenommen, wenn alle folgenden Punkte erfüllt sind.

### Header und Toolbar

- Seite zeigt einen Header mit `wp-heading-inline`
- vorhandene Primäraktionen stehen als `page-title-action` im Header
- `hr.wp-header-end` trennt Header und Inhalt sauber
- Seitentitel ist fachlich korrekt und entspricht dem Menüeintrag oder der Aktion

### Tabs und Navigation

- Tabs sind sichtbar, falls die Seite mehrere Bereiche besitzt
- aktiver Tab ist visuell eindeutig markiert
- direkter Aufruf per URL mit `?tab=` funktioniert
- ungültige oder manipulierte Tab-Werte fallen sauber auf einen erlaubten Standard zurück
- Zurück-/Vorwärtsnavigation im Browser führt nicht in einen inkonsistenten Zustand

### Modulare Bereiche

- Schnellaktionen, Status- und Überblickskarten sind vor dem Hauptinhalt sichtbar
- Karten enthalten nur kontextrelevante Informationen
- Karten umbrechen auf kleineren Viewports ohne Layoutbruch
- Hinweise, Erfolgs- und Fehlermeldungen bleiben sichtbar und überdecken keine Toolbar-Aktionen

### Formulare und Listen

- bestehende Formulare speichern weiterhin korrekt
- Nonce-geschützte Aktionen funktionieren unverändert
- Tabellen, Filter, Suche, Pagination und Bulk-Aktionen bleiben nutzbar
- Leerzustände sind strukturiert und optisch konsistent
- bestehende POST- und GET-Flows wurden durch den UI-Umbau nicht verändert

### Fehlersicherheit

- keine PHP-Warnings oder Notices im Debug-Log beim Laden der Seite
- keine kaputten Links durch neue Toolbar-Buttons
- keine doppelt gerenderten Header oder doppelten `wrap`-Container

## Plugin-Matrix fuer die Abnahme

### Settings- und Template-Seiten

Prüfen:

- `themisdb-architecture-diagrams`
- `themisdb-benchmark-visualizer`
- `themisdb-query-playground`
- `themisdb-release-timeline`
- `themisdb-tco-calculator`
- `themisdb-test-dashboard`
- `themisdb-wiki-integration`
- `themisdb-feature-matrix`
- `themisdb-support-portal`

Erwartung:

- Header/Toolbar einheitlich
- Tabs korrekt
- Settings API unverändert funktionsfähig
- Shortcode-/Info-Tabs weiterhin lesbar und vollständig

### Controller- und Spezialseiten

Prüfen:

- `themisdb-compendium-downloads`
- `themisdb-docker-downloads`
- `themisdb-downloads`
- `themisdb-feature-matrix`
- `themisdb-formula-renderer`
- `themisdb-front-slider`
- `themisdb-gallery`
- `themisdb-github-bridge`
- `themisdb-taxonomy-manager`
- `themisdb-wiki-integration`
- `themisdb-wiki-integration` Importer-Tool

Erwartung:

- Spezialaktionen wie Sync, Cache-Clear, Tree-Ansicht, Bibliotheks-Grid oder Import laufen unverändert
- neue Kartenbereiche verdecken keine vorhandene Fachbedienung
- Toolbars verlinken auf sinnvolle Folgeaktionen

## Schwerpunktabnahme: themisdb-order-request

Dieses Plugin hat die größte UI-Änderung und muss vollständig geprüft werden.

### Dashboard

- Dashboard lädt ohne Fehler
- Modulübersichtskarten verlinken auf die korrekten Listen oder Aktionen
- Header-Aktionen sind sichtbar und fachlich sinnvoll

### Bestellungen

- Bestellliste zeigt Header, Toolbar und Listenfunktionen korrekt
- neue Bestellung kann angelegt werden
- Detailansicht zeigt Header und Navigationsaktionen konsistent
- Bearbeiten speichert ohne UI-Regression

### Verträge

- Vertragsliste ist lesbar und filterbar
- Detailansicht enthält konsistente Toolbar-Aktionen
- Relationen zu Bestellung und Lizenz bleiben korrekt navigierbar

### Produkte und Module

- CRUD-Bereich für Produkte/Module lädt vollständig
- Wechsel zwischen Listen-/Bearbeitungs-/Kategoriebereichen funktioniert
- Such-, Sortier- und Filterzustände bleiben erhalten
- Aktionen verändern nur Fachdaten, nicht den UI-Zustand unerwartet

### Lagerbestand

- Inventarlisten und Kategorienansichten folgen demselben Standard
- Bearbeiten und Kategorie-Aktionen funktionieren weiterhin
- Kontext bleibt nach Speichern oder Toggle erhalten

### Zahlungen

- Zahlungsliste lädt mit Übersichtskarten
- neue Zahlung kann angelegt werden
- Detail- und Bearbeitungsseite haben konsistente Header und Zurück-Links
- Statuswechsel bleiben funktional korrekt

### Lizenzen

- Lizenzliste lädt mit Toolbar und Statusbereichen
- neue Lizenz kann angelegt werden
- Detail- und Bearbeitungsseite sind vollständig navigierbar
- Lifecycle-relevante Felder bleiben funktional korrekt

### Logs und Support

- License Audit Log rendert mit neuem Header, ohne doppelte Strukturen
- E-Mail Log zeigt Status-/Übersichtsinformationen korrekt
- Support-Tickets lassen sich anlegen und anzeigen
- GitHub-bezogene Folgeaktionen bleiben funktionsfähig, sofern konfiguriert

### Bankimport

- Übersichtsseite lädt mit Toolbar und Statuskarten
- Vorschau einer Importsession rendert vollständig
- Detailansicht ist konsistent und zeigt keine Restlogik alter Tabs
- manuelle Zuordnung ist weiterhin möglich

### Reporting

- Advanced Reporting lädt im neuen Admin-Muster
- Frontend-Shortcode-Ausgabe enthält kein versehentliches Admin-Markup

## Debug-Log-Prüfung

Nach jeder Plugin-Abnahme prüfen:

1. betroffene Admin-Seite laden
2. zentrale Aktion ausführen, zum Beispiel Speichern, Sync, Bulk oder Erstellen
3. `debug.log` auf neue Einträge prüfen

No-Go bei:

- PHP Fatal Errors
- Warnings/Notices durch neue Variablen oder Header-Logik
- Redirect-Loops oder ungültigen Admin-URLs

## Smoke-Check Skript

Für eine schnelle technische Basisprüfung kann zusätzlich das Skript `scripts/themisdb-admin-modernization-smoke.ps1` verwendet werden.

Standardaufruf:

```powershell
pwsh -File scripts/themisdb-admin-modernization-smoke.ps1 -WpPath "C:\path\to\wordpress"
```

Mit Debug-Log-Prüfung und Ergebnisdatei:

```powershell
pwsh -File scripts/themisdb-admin-modernization-smoke.ps1 -WpPath "C:\path\to\wordpress" -DebugLogPath "C:\path\to\wordpress\wp-content\debug.log" -ResultPath "C:\temp\themisdb-admin-smoke.json"
```

Nur Quellstruktur und Menüregistrierung prüfen, ohne Render-Checks:

```powershell
pwsh -File scripts/themisdb-admin-modernization-smoke.ps1 -WpPath "C:\path\to\wordpress" -SkipRenderChecks
```

Das Skript prüft:

- Marker in den modernisierten Admin-Dateien wie `wp-heading-inline`, `page-title-action` und `wp-header-end`
- registrierte Admin-Menü-Slugs in einer aktiven WordPress-Instanz
- optional die Basisausgabe der Seiten-Hooks auf Header-Struktur
- optional neue Warning-/Notice-/Fatal-Einträge im `debug.log`
- optional einen JSON-Report mit den Einzelresultaten pro Check

Für die eigentliche Release-Freigabe siehe zusätzlich `docs/THEMISDB_ADMIN_RELEASE_ACCEPTANCE_MATRIX.md`.

## Empfohlene Reihenfolge der Abnahme

1. Settings-Templates und kleine Plugins
2. Spezialseiten mit Import, Sync oder Bibliotheken
3. `themisdb-support-portal`
4. vollständige CRUD-Abnahme von `themisdb-order-request`
5. Abschlussprüfung des Debug-Logs

## Ergebnisprotokoll

- Testdatum:
- Tester:
- Umgebung:
- Geprüfte Plugins:
- Auffälligkeiten:
- Blocker:
- Debug-Log sauber: Ja/Nein
- Entscheidung: GO oder NO-GO