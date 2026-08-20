# Changelog

Alle bemerkenswerten Änderungen an diesem Projekt werden in dieser Datei dokumentiert.

Das Format basiert auf [Keep a Changelog](https://keepachangelog.com/de/) und folgt [Semantic Versioning](https://semver.org/).

## [1.0.1] - 2026-08-20

### Added
- Vollständige Update-Infrastruktur für WordPress 5.8+ automatische Plugin-Updates
- ThemisDB_Plugin_Updater-Klasse in allen aktiven Plugins für konsistentes Update-Management
- GitHub Release-Workflow für automatisierte Versionsverwaltung
- Support-Seite mit vollständiger Theme-Shell-Integration (Header + Footer)
- Standardisierte Plugin-Metadaten (Author: makr-code, Author URI: GitHub-Repository)

### Changed
- Alle Plugin-Versionen erhöht von 1.0.0 auf 1.0.1 (koordinierter Minor-Release)
- Plugin-Header normalisiert mit GitHub-Repositoy-URIs
- Update URI auf zentrales GitHub-Repository gesetzt für alle Plugins

### Fixed
- Support-Seite zeigt jetzt korrekte Theme-Navigation statt generisches Template
- Doppelte Plugin-Metadaten-Blöcke in graph-navigation entfernt
- Version-Konstanten in allen Plugins auf einheitliches Format aktualisiert

## [1.0.0] - 2026-08-19

### Initial Release
- 26 aktive WordPress-Plugins
- Grundlegende Plugin-Struktur mit WordPress Best-Practices
- Plugin-Support Portal mit Shortcode-Integration
- Graph Navigation für Content-Linking
- Engagement Tracker für Post-Metriken
- Mehrsprachige Theme-Unterstützung (themisdb-pulse)

---

## Versioning-Details

### Aktive Plugins (26)

| Plugin | v1.0.1 |
|--------|--------|
| chimera-benchmark-data | ✓ |
| themisdb-architecture-diagrams | ✓ |
| themisdb-benchmark-visualizer | ✓ |
| themisdb-compendium-downloads | ✓ |
| themisdb-db-backup | v1.0.2 |
| themisdb-docker-downloads | ✓ |
| themisdb-downloads | ✓ |
| themisdb-engagement-tracker | v1.0.2 |
| themisdb-feature-matrix | ✓ |
| themisdb-formula-renderer | ✓ |
| themisdb-front-slider | v1.1.5 |
| themisdb-gallery | ✓ |
| themisdb-github-bridge | ✓ |
| themisdb-graph-navigation | ✓ |
| themisdb-horizon | ✓ |
| themisdb-order-request | ✓ |
| themisdb-persistent-podcast-player | ✓ |
| themisdb-quality-meter | ✓ |
| themisdb-query-playground | ✓ |
| themisdb-release-timeline | ✓ |
| themisdb-support-portal | ✓ |
| themisdb-taxonomy-manager | ✓ |
| themisdb-tco-calculator | ✓ |
| themisdb-test-dashboard | ✓ |
| themisdb-wiki-integration | ✓ |
| wordpress-integration-example | ✓ |

### Update-Mechanismus

Alle Plugins unterstützen automatische Updates über GitHub:
- **Update URI**: https://github.com/makr-code/wordpressPlugins
- **Updater-Klasse**: ThemisDB_Plugin_Updater
- **WordPress Kompatibilität**: 5.0+ mit Optional PHP 7.4+

### Migration

Kein Datenbank-Migration erforderlich für v1.0.0 → v1.0.1
Alle Updates sind abwärtskompatibel.
