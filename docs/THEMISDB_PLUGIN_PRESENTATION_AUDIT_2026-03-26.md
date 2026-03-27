# ThemisDB Plugin Presentation Audit (2026-03-26)

Ziel: Plugins liefern Funktionen/Daten, Themes liefern Darstellung.

## Bewertungsregeln

- `OK (Backend-only)`: Admin-Oberflaechen-Styles bleiben im Plugin erlaubt.
- `MIGRATE (Frontend style ownership)`: Frontend-CSS/Markup-Varianten gehoeren ins Theme.
- `KEEP (Data/logic)`: API, Queries, Datenmodelle, Validierung bleiben im Plugin.

## Schnellscan (initial)

| Plugin | Befund | Prioritaet | Empfehlung |
|---|---|---:|---|
| themisdb-front-slider | Frontend-CSS jetzt fallback-only + Shortcode-Hook-Pipeline + aktiver Theme-Adapter | Erledigt | Plugin-Slider bleibt Fallback, Theme uebernimmt Darstellung standardmaessig ueber Hook-Adapter |
| themisdb-downloads | Frontend-CSS nur noch Fallback; Theme-Adapter uebernimmt Shortcode-HTML via Hook | Hoch | Optional: semantische Klassen weiter vereinheitlichen |
| themisdb-docker-downloads | Frontend-CSS nur noch Fallback; Theme-Adapter uebernimmt Shortcode-HTML via Hook | Hoch | Optional: semantische Klassen weiter vereinheitlichen |
| themisdb-compendium-downloads | Frontend-CSS nur noch Fallback; Theme-Adapter uebernimmt Shortcode-HTML via Hook | Hoch | Optional: semantische Klassen weiter vereinheitlichen |
| themisdb-feature-matrix | Frontend-CSS nur noch Fallback; Theme-Adapter uebernimmt Shortcode-HTML via Hook | Hoch | Optional: semantische Klassen weiter vereinheitlichen |
| themisdb-gallery | Frontend-CSS nur noch Fallback; Theme-Adapter uebernimmt Shortcode-HTML via Hook | Mittel | Optional: semantische Klassen weiter vereinheitlichen |
| themisdb-release-timeline | Frontend-CSS nur noch Fallback; Theme-Adapter uebernimmt Shortcode-HTML via Hook | Mittel | Optional: semantische Klassen weiter vereinheitlichen |
| themisdb-benchmark-visualizer | Frontend-CSS jetzt fallback-only + Shortcode-Hook-Pipeline + aktiver Theme-Adapter | Mittel | Optional: semantische Klassen im Theme-Renderer weiter ausbauen |
| themisdb-architecture-diagrams | Frontend-CSS jetzt fallback-only + Shortcode-Hook-Pipeline + aktiver Theme-Adapter | Mittel | Optional: semantische Klassen fuer Panels/Legend im Theme weiter ausbauen |
| themisdb-taxonomy-manager | Frontend-CSS nur noch Fallback; Theme-Adapter uebernimmt Shortcode-HTML via Hook | Hoch | Optional: inline-style-Reste schrittweise auf semantische Klassen reduzieren |
| themisdb-order-request | Frontend-CSS jetzt fallback-only + Shortcode-Hook-Pipeline fuer Theme-Override | Hoch | Optional: weitere Inline-Styles schrittweise auf semantische Klassen reduzieren |
| themisdb-query-playground | Frontend-CSS nur noch Fallback; Theme-Adapter uebernimmt Shortcode-HTML via Hook | Mittel | Optional: inline-style-Reste schrittweise auf semantische Klassen reduzieren |
| themisdb-formula-renderer | Frontend-CSS jetzt fallback-only + Shortcode-Hook-Pipeline + aktiver Theme-Adapter | Mittel | KaTeX-Rendering bleibt Plugin, visuelle Container liegen jetzt im Theme-Pfad |
| themisdb-support-portal | Frontend-CSS jetzt fallback-only + Shortcode-Hook-Pipeline + aktive Theme-Adapter | Mittel | Optional: semantische Klassen im Theme-Renderer weiter ausbauen |
| themisdb-wiki-integration | Frontend-CSS jetzt fallback-only + Shortcode-Hook-Pipeline + aktive Theme-Adapter | Niedrig-Mittel | Theme uebernimmt Wiki-/Docs-/Nav-Container, GitHub/Markdown-Logik bleibt im Plugin |
| themisdb-tco-calculator | Frontend-CSS jetzt fallback-only + Shortcode-Hook-Pipeline + aktive Theme-Adapter | Niedrig-Mittel | Chart.js/Mermaid/Berechnungslogik bleiben im Plugin, visuelle Container laufen jetzt ueber das Theme |
| themisdb-persistent-podcast-player | Frontend-CSS jetzt fallback-only + Render-Hook-Pipeline + aktiver Theme-Adapter | Niedrig-Mittel | Player-Logik/REST/Audio-Steuerung bleiben im Plugin, visuelle Player-Shell kommt jetzt aus dem Theme |
| themisdb-test-dashboard | Frontend-CSS jetzt fallback-only + Shortcode-Hook-Pipeline + aktiver Theme-Adapter | Niedrig-Mittel | AJAX/Chart.js/Datenlogik bleiben im Plugin, Dashboard-Container wird im Theme gerendert |
| themisdb-graph-navigation | Frontend-Script jetzt fallback-only + Daten-/Payload-Hook-Pipeline + aktiver Theme-Adapter | Niedrig-Mittel | Graph-Logik und Overlay bleiben im Plugin, Theme kann Payload und Enqueue-Verhalten steuern |
| themisdb-github-bridge | OK (Backend-only) | Niedrig | Admin-Settings und Ticket->Issue-Sync bleiben im Plugin; keine Frontend-UI-Migration erforderlich |

## Bereits umgesetzt im Theme

- Theme-owned Integrations-Tags und Hook-Schnittstellen dokumentiert in `themisdb-theme/PLUGIN_INTEGRATION.md`.
- Theme erzwingt Darstellungshoheit fuer bekannte Integrations-Shortcodes.
- Front-Slider-Plugin laedt bei aktivem ThemisDB-Theme keine eigene Frontend-Darstellungs-CSS mehr und bietet jetzt eine Hook-Pipeline mit aktivem Theme-Adapter.
- Download-Familie (`themisdb-downloads`, `themisdb-docker-downloads`, `themisdb-compendium-downloads`) laedt Frontend-CSS nur noch als Fallback und bietet HTML-Override-Hooks fuer Theme-Renderer (inkl. `themisdb_latest`, `themisdb_verify`, `themisdb_readme`, `themisdb_changelog`, `themisdb_docker_latest`).
- Theme-Adapter fuer die Download-Familie sind aktiv und liefern das Frontend-Markup bereits zentral aus dem Theme.
- Feature-Matrix und Release-Timeline folgen jetzt demselben Muster (Fallback-CSS + aktive Theme-Adapter via Shortcode-Hooks).
- Gallery folgt jetzt demselben Muster (Fallback-CSS + aktiver Theme-Adapter via Shortcode-Hooks).
- Wave-2-Konsistenzschritt abgeschlossen: `themisdb_feature_matrix_shortcode_atts`, `themisdb_release_timeline_shortcode_atts` und `themisdb_gallery_shortcode_atts` erhalten nun einheitlich `($atts, $raw_atts)`.
- Taxonomy Manager folgt jetzt demselben Muster (Fallback-CSS + aktive Theme-Adapter via Shortcode-Hooks inkl. `themisdb_taxonomy_info`).
- Query Playground folgt jetzt demselben Muster (Fallback-CSS + aktiver Theme-Adapter via Shortcode-Hooks).
- Order Request folgt jetzt in der Kernstrecke demselben Muster (Fallback-CSS + Hook-Pipelines fuer Order/Portal/Auth/Affiliate/B2B/Reporting/Shop/Account + aktive Theme-Adapter fuer Order Flow, My Orders, My Contracts, Pricing, Pricing Table, Product Detail, Shop, Shopping Cart, Login, License Upload, License Portal, Affiliate Dashboard, B2B Portal und Advanced Reporting).
- Benchmark Visualizer folgt jetzt demselben Muster (Fallback-CSS + Shortcode-Hook-Pipeline + aktiver Theme-Adapter via Shortcode-Hooks).
- Architecture Diagrams folgt jetzt demselben Muster (Fallback-CSS + Shortcode-Hook-Pipeline + aktiver Theme-Adapter via Shortcode-Hooks).
- Support Portal folgt jetzt demselben Muster (Fallback-CSS + Shortcode-Hook-Pipeline + aktive Theme-Adapter via Shortcode-Hooks).
- Formula Renderer folgt jetzt demselben Muster (Fallback-CSS + Shortcode-Hook-Pipeline + aktiver Theme-Adapter via Shortcode-Hooks).
- Wiki Integration folgt jetzt demselben Muster (Fallback-CSS + Shortcode-Hook-Pipeline + aktive Theme-Adapter via Shortcode-Hooks).
- TCO Calculator folgt jetzt demselben Muster (Fallback-CSS + Shortcode-Hook-Pipeline + aktive Theme-Adapter fuer Rechner und Teilsektionen via Shortcode-Hooks).
- Persistent Podcast Player folgt jetzt demselben Muster (Fallback-CSS + Render-Hook-Pipeline + aktiver Theme-Adapter fuer die Player-Shell).
- Test Dashboard folgt jetzt demselben Muster (Fallback-CSS + Shortcode-Hook-Pipeline + aktiver Theme-Adapter via Shortcode-Hooks).
- Graph Navigation folgt jetzt demselben Muster (Fallback-Script + Daten-/Payload-Hook-Pipeline + aktiver Theme-Adapter).
- GitHub Bridge ist explizit als Backend-only klassifiziert (Admin-Oberflaeche + Sync-Logik, keine Frontend-Migrationsflaeche).
- GitHub-Datenabrufe sind in der Kernstrecke auf die Bridge umgestellt (u.a. Releases, Tags, Milestones, Milestone-Issues, Workflow-Dispatch, Wiki-Sync, Plugin-Update-Checks), damit Tags/Milestones/Issues zentral ueber die Bridge nach WordPress geladen werden; die migrierten GitHub-Datenpfade laufen jetzt Bridge-only ohne direkten API-Fallback.

## Migrationsmuster pro Plugin

1. Plugin-Frontend-Styles optional machen (nur Fallback fuer fremde Themes).
2. Plugin-Output auf semantische, stabile Klassen reduzieren.
3. Theme uebernimmt CSS und visuelle Varianten.
4. Plugin bietet Daten-/Args-Hooks (Defaults, Query-Args, Payload).
5. Theme konsumiert diese Hooks und rendert final.

## Naechste 3 Umsetzungswellen

### Welle 1 (hoher Nutzen, geringes Risiko)

- themisdb-downloads
- themisdb-docker-downloads
- themisdb-compendium-downloads

Erwartetes Ergebnis:
- Ein gemeinsamer Theme-Renderer fuer Download-Listen/Karten/Tabellen.
- Plugins liefern nur Download-Daten und optionale Filter-Parameter.

### Welle 2 (Frontend-Komponenten)

- themisdb-feature-matrix
- themisdb-release-timeline
- themisdb-gallery

Erwartetes Ergebnis:
- Einheitliche UI-Designsprache im Theme.
- Plugin-spezifische Logik bleibt erhalten.

### Welle 3 (komplexe Spezialplugins)

- themisdb-order-request
- themisdb-taxonomy-manager
- themisdb-query-playground

Erwartetes Ergebnis:
- Grobe Trennung in Funktionsplugin + Theme-Komponenten.
- Reduktion von inline styles und style-Optionen im Plugin.

## Definition of Done pro Plugin

- Kein zwingendes Frontend-`wp_enqueue_style` mehr im Plugin (nur optionaler Fallback).
- Keine plugininternen Style-Varianten als Produktfeature (`style="..."`, `default_style`) ohne Theme-Alternative.
- Theme-Renderer vorhanden und als Standard aktiv.
- Plugin-Datenfluss ueber Hooks dokumentiert.
- Frontend-Darstellung bleibt bei Plugin-Aktivierung identisch zum Theme-Standard.
