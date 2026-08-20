# ThemisDB Pulse Master-Implementierungsplan

Stand: 2026-08-20

## Ziel
Die zukünftige Haupt-Theme-Linie wird auf `themisdb-pulse` aufgebaut. Die Oberfläche orientiert sich an Azure-Designprinzipien (klar, technisch, vertrauenswürdig, strukturstark), während WordPress-Kernfähigkeiten nativ genutzt werden:

- Block-Theme (FSE)
- Navigation, Seiten, Beiträge, Query Loops
- Patterns und Template Parts
- Plugin-Integration über Hooks/Services

Der technische Kern folgt strikt SoC und OOP.

## Architekturleitlinien (SoC + OOP)

### 1) Schichtenmodell
1. Präsentation (`themisdb-pulse`): Templates, Template Parts, Patterns, `theme.json`, `style.css`, minimale UI-Hooks.
2. Domänenlogik (Companion-Plugins): Geschäftsregeln, API-Integrationen, Datenaufbereitung, Security-Policies.
3. Integrationsschicht: WordPress Actions/Filters, klar dokumentierte Contracts zwischen Theme und Plugins.
4. Datenmodell: Taxonomien, Metafelder, CPTs, Schema-/Versionierungsregeln.

### 2) Regeln für das Theme
1. Keine Business-Logik in Templates/Patterns.
2. Kein Persistenzzugriff direkt aus Pattern-Dateien.
3. Nur UI-nahe Helfer in `functions.php`.
4. Komponenten-Assets kapseln (pro Feature eigene CSS/JS-Blöcke).

### 3) Regeln für Plugins (OOP)
1. Klassen nach Verantwortung trennen: `Controller`, `Service`, `Repository`, `Policy`, `Presenter`.
2. Schnittstellen vor Implementierung (Interface-First).
3. Abhängigkeiten über Konstruktor-Injection.
4. Öffentliche Integrationspunkte als Hooks dokumentieren.

## Designziel (Azure-inspiriert, WP-nativ)

### 1) Visuelle Prinzipien
1. Klare Hierarchie und ruhiger Informationsfluss.
2. Token-basierte Farb- und Typografie-Logik.
3. Kontraststarke Flächen und strukturierte Karten/Module.
4. Zurückhaltende, funktionale Motion.

### 2) WordPress-Mapping
1. Layout: `wp:group`, `wp:columns`, `wp:cover`, `wp:spacer`.
2. Content: `wp:heading`, `wp:paragraph`, `wp:list`, `wp:image`.
3. Navigation: `wp:navigation` + Header-/Footer-Template-Parts.
4. Dynamik: Query Loop und Post Templates.
5. Wiederverwendung: Block Patterns statt harter Seitenkopien.

## Umsetzungsphasen (6 Sprints)

## Sprint 1: Foundation
Ziel: belastbare Baseline für Master-Theme.

Lieferobjekte:
1. Baseline-Audit von `themisdb-pulse` (Templates, Parts, Patterns, Assets).
2. Token-Matrix in `theme.json` finalisieren (Farben, Typo, Spacing, Shadows).
3. Navigations- und Template-Inventar dokumentieren.
4. DoD-Definition (A11y, Performance, SEO, Test).

Akzeptanz:
1. Alle Kernseiten rendern ohne Block-Fehler.
2. Token-Namenskonvention vollständig dokumentiert.

## Sprint 2: Azure UI Core
Ziel: konsistente Azure-inspirierte Grundoberfläche.

Lieferobjekte:
1. Header/Announcement/Footer als robuste Template-Parts.
2. Primäre Seitenraster (Landing, Content, Docs, Blog).
3. Einheitliche Karten- und CTA-Stile als Block Styles.
4. Motion-Standards (nur meaningful transitions).

Akzeptanz:
1. Responsive Darstellung auf 360/768/1280 px ohne Layout-Brüche.
2. Keine Inline-Styles in Patterns/Templates.

## Sprint 3: Pattern-System
Ziel: redaktionell nutzbares Baukastensystem.

Lieferobjekte:
1. Hero-, Feature-, CTA-, Docs-, Pricing-, Compare-Patterns.
2. Pattern-Kategorien und Naming-Schema.
3. Pattern-Guidelines für Redaktion.

Akzeptanz:
1. Landingpage ist zu 100% aus Patterns zusammensetzbar.
2. Keine `wp:html`-Blöcke ohne dokumentierte Ausnahme.

## Sprint 4: SoC/OOP Plugin-Integration
Ziel: klare Trennung von UI und Domäne.

Lieferobjekte:
1. Companion-Plugin-Struktur (falls noch nicht vorhanden) für Theme-nahe Services.
2. Contracts zwischen Theme und Kern-Plugins dokumentieren.
3. Refactoring von Theme-seitiger Logik in Services/Filter.

Akzeptanz:
1. Keine Business-Regel verbleibt in Pattern-Dateien.
2. Integrationspunkte sind in einem Contract-Dokument beschrieben.

## Sprint 5: Quality Gates
Ziel: produktionsreife Qualität.

Lieferobjekte:
1. A11y-Checks (Semantik, Fokus, Tastaturpfade, Kontraste).
2. Performance-Budget (LCP/CLS/INP Zielwerte lokal definiert).
3. Theme-CI-Gates (Syntax, Inline-Style=0, wp:html=0, Smoke-Render).

Akzeptanz:
1. Gate-Läufe sind grün.
2. Release-Kandidaten bestehen visuelle Regressionen.

## Sprint 6: Rollout und Governance
Ziel: kontrollierte Einführung als Master.

Lieferobjekte:
1. Migrationsleitfaden von bisherigen Themes nach `themisdb-pulse`.
2. Betriebsdoku für Updates, Fallback, Incident-Pfad.
3. Redaktions-Quickstart inkl. Pattern-Einsatzregeln.

Akzeptanz:
1. Staging-Abnahme abgeschlossen.
2. Rollback-Plan ist getestet.

## Priorisierte Backlog-Pakete (Start sofort)

## Paket A: Theme-Identität und Naming bereinigen
1. README und Headertexte konsequent auf `themisdb-pulse` und "ThemisDB Pulse" umstellen.
2. Veraltete Verweise auf frühere Ordnernamen entfernen.

## Paket B: Token-Harmonisierung
1. Doppelte/alte Farbvariablen konsolidieren.
2. Tokens in `theme.json` und `style.css` synchronisieren.

## Paket C: Navigation und IA
1. Primäre Menühierarchie für Produkt, Docs, Blog, Support definieren.
2. Docs-spezifische Seitenvorlage mit konsistenter Sidebar stabilisieren.

## Paket D: Plugin-Contracts
1. Liste aller themisdb-Plugins mit UI-Abhängigkeiten erstellen.
2. Für jedes Plugin einen klaren Theme-Contract definieren (Daten rein, Darstellung raus).

## Qualitätskriterien (Definition of Done)
1. SoC: Keine Domänenlogik in Theme-Patterns.
2. OOP: Neue Logik nur in testbaren Klassen mit klaren Interfaces.
3. Editor-Fähigkeit: Redakteure können Seiten ohne Custom-CSS erstellen.
4. Accessibility: WCAG 2.2 AA für Kernseiten.
5. Performance: definierte Budgetwerte werden eingehalten.
6. Updatefähigkeit: Keine Core-Hacks, keine hardcoded Abhängigkeiten.

## Risiken und Gegenmaßnahmen
1. Risiko: Theme-Logik wächst wieder in `functions.php`.
   Gegenmaßnahme: PR-Gate "UI-only im Theme" + Review-Checklist.
2. Risiko: Plugin/Theme-Kopplung über CSS-Klassen.
   Gegenmaßnahme: Contracts + Presenter-Schicht im Plugin.
3. Risiko: Design drift durch ad-hoc Pattern-Änderungen.
   Gegenmaßnahme: Pattern-Registry und Freigabeprozess.

## Erste 10 Umsetzungsaufgaben (konkret)
1. README in `themisdb-pulse` auf neuen Master-Status aktualisieren.
2. Theme-Header und Text Domain auf Konsistenz prüfen.
3. `theme.json` Token-Sektion reviewen und normalisieren.
4. `parts/header*.html` auf einheitliche Navigation abstimmen.
5. `templates/front-page.html` gegen Azure-Layoutziele mappen.
6. Pattern-Inventar mit Statusmatrix in Doku aufnehmen.
7. Companion-Plugin-Scope für OOP-Services definieren.
8. CI-Gates für Inline-Styles/wp:html in Workflows prüfen.
9. Redaktionsleitfaden für Landingpage-Baukasten erstellen.
10. Staging-Abnahmecheckliste für Sprint-2 vorbereiten.

## Entscheidungsstatus
1. Master-Basis ist auf `themisdb-pulse` festgelegt.
2. Alle neuen Design- und Architekturarbeiten laufen gegen diese Linie.
3. Andere Themes bleiben bis zum finalen Rollout als Fallback erhalten.
