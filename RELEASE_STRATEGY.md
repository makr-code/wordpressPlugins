# Release-Strategie für wordpressPlugins

## Versionierung

Alle Plugins folgen der **Semantic Versioning** (SemVer) Konvention: `MAJOR.MINOR.PATCH`

- **MAJOR**: Breaking Changes (Plugin-API ändert sich)
- **MINOR**: Neue Features (abwärtskompatibel)
- **PATCH**: Bug Fixes und kleine Verbesserungen

## Release-Prozess

### 1. Versionsnummern erhöhen
```bash
# Automatisch für alle Plugins oder selektiv
python update_versions.py
```

Jedes Plugin hat eine eigene Versionsnummer im Header:
```php
/**
 * Plugin Name: Plugin Name
 * Version: 1.0.1
 * ...
 */
```

### 2. Tag in Git erstellen
Vor dem Release ein Git-Tag mit dem Format `v1.0.1` erstellen:
```bash
git tag -a v1.0.1 -m "Release 1.0.1: Beschreibung"
git push origin v1.0.1
```

### 3. GitHub Release erstellen
Der GitHub Actions Workflow `release.yml` erstellt automatisch ein Release:
- Nutze "Create Release" in GitHub UI
- Oder triggere den Workflow manual über "Actions > Create Release"

### 4. Release-Notes generieren
Release-Notes enthalten:
- Liste der geänderten Plugins mit ihren neuen Versionen
- Commit-Messages seit letztem Release
- Migration-Hinweise (falls nötig)

**Format:**
```markdown
## v1.0.1 Release (2026-08-20)

### Updates
- **plugin-name**: 1.0.0 → 1.0.1
  - Feature description
  - Bug fix description

### Migration
- Falls Breaking Changes: Anleitung zum Upgrade
```

## Update-Mechanism

### WordPress Plugin Updates
Alle Plugins haben `Update URI` konfiguriert:
```php
* Update URI: https://github.com/makr-code/wordpressPlugins
```

WordPress 5.8+ erkennt die neuen GitHub Releases automatisch und zeigt sie im Admin an.

### Update-Flow
1. WordPress prüft regelmäßig auf neue Releases
2. GitHub API wird aufgerufen: `/repos/makr-code/wordpressPlugins/releases`
3. Neue Versionen werden erkannt
4. Admin kann Plugins einzeln oder in Bulk updaten

## CI/CD Integration

### Automatische Checks vor Release
- Version-Format validieren
- Plugin-Metadaten prüfen
- PHP-Syntax validieren
- WordPress-Kompatibilität prüfen

### Release-Workflow
```
Commit → Tag erstellen → GitHub Release → Plugin-Update verfügbar
```

## Versioning für mehrere Plugins

Da es 26 aktive Plugins gibt, jedes mit eigener Version:

### Option 1: Koordinierte Releases (empfohlen)
- Alle Plugins zusammen versionieren
- Ein gemeinsames Release-Tag pro Zeitpunkt
- Einfaches Rollback bei Problemen

**Tag-Format:** `v1.0.1` (alle Plugins zusammen)

### Option 2: Unabhängige Releases
- Jedes Plugin bei Bedarf einzeln versionieren
- Feingranulare Kontrolle
- Höhere Komplexität

**Tag-Format:** `plugin-name/v1.0.1`

## Branching-Strategie

```
main (stable) ← Production Releases
  ↓
develop ← Feature Development
  ↓
feature/* ← Individual Features
```

- **main**: Nur stabile Releases
- **develop**: Staging für nächstes Release
- **feature/**: Feature-Branches

## Changelog

Alle Änderungen sollten dokumentiert werden:

- **CHANGELOG.md**: Zentral für das gesamte Repo
- **Plugin-spezifische CHANGELOG.md**: In jedem Plugin-Verzeichnis (optional)

**Format:**
```markdown
## [1.0.1] - 2026-08-20

### Added
- Neue Features

### Fixed
- Bug Fixes

### Changed
- Veränderte Funktionalität
```

## Release-Checklist

- [ ] Alle Versionen aktualisiert
- [ ] CHANGELOG.md aktualisiert
- [ ] Tests durchlaufen
- [ ] Code-Review durchgeführt
- [ ] Git-Tag erstellt
- [ ] GitHub Release erstellt
- [ ] Release-Notes veröffentlicht
- [ ] Plugin-Updates im Admin sichtbar

## Rollback

Wenn ein Release Probleme verursacht:
```bash
# Git-Tag löschen
git tag -d v1.0.1
git push origin :v1.0.1

# GitHub Release löschen
# Manuell in GitHub UI

# Zu vorherigem Release zurückkehren
git checkout v1.0.0
```

## Automatische Updates für Nutzer

Nach einem Release auf GitHub:
1. WordPress Admin zeigt "Update verfügbar" an
2. Nutzer können direkt über Admin updaten
3. Alte Version wird automatisch gesichert

**Keine manuellen Installations-Schritte nötig!**
