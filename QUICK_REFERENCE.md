# 🚀 Release Management Quick Reference

**Für schnellen Zugriff: Die wichtigsten Befehle für Release-Management**

---

## 📋 Standard Release Process

### 1️⃣ Vorbereitung (vor Release)

```bash
# Branch aktualisieren
git checkout main
git pull origin main

# Feature-Branch mergen (falls nötig)
git merge origin/feature/name
```

### 2️⃣ Version erhöhen

```bash
# Alle Versionen automatisch erhöhen (1.0.0 → 1.0.1)
python update_versions.py

# Oder spezifische Plugins
python update_special_versions.py
```

**Was wird gemacht:**
- Version in Plugin-Header: `Version: X.Y.Z`
- Version in Konstanten: `define('PLUGIN_VERSION', 'X.Y.Z')`
- Für alle 26 Plugins

### 3️⃣ Änderungen committen

```bash
# Änderungen hinzufügen
git add -A

# Commit mit Release-Nachricht
git commit -m "Release vX.Y.Z: Neue Features + Bug Fixes"

# Zu GitHub pushen
git push origin main
```

### 4️⃣ Git-Tags erstellen

```bash
# Tags für alle Plugins erstellen (Format: plugin-name/vX.Y.Z)
python create_plugin_tags.py

# Tags zu GitHub pushen
git push origin --tags
```

**Was wird gemacht:**
- Annotated Tags erstellt
- Alte Tags übersprungen (keine Duplikate)
- Nur neue Tags gepusht

### 5️⃣ GitHub Releases erstellen

```bash
# Releases für alle Plugin-Tags generieren
python create_github_releases.py
```

**Was wird gemacht:**
- Release pro Plugin auf GitHub
- Release-Notes automatisch generiert
- Installation-Anleitung hinzugefügt
- Kompatibilität-Info aufgenommen

### 6️⃣ Verifikation in WordPress Admin

```
1. Browser: http://localhost/wordpress/wp-admin
2. Plugins → Updates
3. "Updates verfügbar" sollte sichtbar sein
4. Optional: Ein Plugin als Test updaten
```

---

## 🎯 Schnell-Kommandos

### Nur einzelnes Plugin versionieren

```bash
# Beispiel: themisdb-support-portal von 1.0.1 → 1.0.2
# Datei editieren: themisdb-support-portal/themisdb-support-portal.php
# Beide stellen anpassen:
# - Header: Version: 1.0.2
# - Konstante: define('THEMISDB_SUPPORT_PORTAL_VERSION', '1.0.2');
# Danach:
git commit -m "Bump themisdb-support-portal to v1.0.2"
git push origin main
python create_plugin_tags.py  # nur dieses Tag wird erstellt
git push origin --tags
python create_github_releases.py  # nur diese Release wird erstellt
```

### Nur Tags createN (ohne Release)

```bash
python create_plugin_tags.py
git push origin --tags
```

### Nur Releases erstellen (Tags existieren bereits)

```bash
python create_github_releases.py
```

### Alle verfügbaren Tags anzeigen

```bash
git tag -l
```

### Spezifischen Plugin-Tag anzeigen

```bash
git tag -l "themisdb-support-portal*"
```

---

## 📊 Status-Überblick

### Aktuelle Versionen anzeigen

```bash
# Alle Plugin-Versionen auflisten
python -c "
import os
import re

plugins_dir = '.'
versions = {}

for plugin_folder in os.listdir(plugins_dir):
    plugin_file = os.path.join(plugin_folder, f'{plugin_folder}.php')
    
    if os.path.exists(plugin_file):
        with open(plugin_file, 'r') as f:
            content = f.read()
            match = re.search(r'Version:\s*(.+)', content)
            if match:
                version = match.group(1).strip()
                versions[plugin_folder] = version

for plugin, version in sorted(versions.items()):
    print(f'{plugin}: {version}')
"
```

### GitHub Release Status

```bash
# Alle Releases auf GitHub anzeigen
gh release list -R makr-code/wordpressPlugins

# Nur für spezifischen Plugin
gh release list -R makr-code/wordpressPlugins | grep "themisdb-support-portal"
```

---

## 🔍 Troubleshooting

### "Command not found: python"
```bash
# Vollständigen Pfad nutzen
C:\Python\python.exe update_versions.py
# oder PowerShell: python.exe statt python
```

### "gh command not found"
```bash
# GitHub CLI installieren
# Windows: winget install github.cli
# oder https://cli.github.com/ downloaden
```

### Git-Tags nicht auf GitHub
```bash
# Prüfe ob gepusht wurde
git push origin --tags --verbose

# Wenn nicht:
git push origin --tags

# Verifizieren
gh api repos/makr-code/wordpressPlugins/tags
```

### Alte Version noch in WordPress angezeigt
```bash
# WordPress Cache leeren
wp_cache_flush();

# Oder: Transient löschen
wp transient delete update_plugins

# Oder: Im WordPress Admin
# Plugins → Update Cache leeren (falls Plugin vorhanden)
```

---

## 📝 Datei-Übersicht

| Datei | Zweck |
|-------|-------|
| `update_versions.py` | Alle Versionen erhöhen |
| `update_special_versions.py` | Einzelne Versionen erhöhen |
| `create_plugin_tags.py` | Git-Tags erstellen |
| `create_github_releases.py` | GitHub Releases erstellen |
| `create_release.py` | Kompletter Release-Flow |
| `PRODUCTION_DOCUMENTATION.md` | Ausführliche Dokumentation |
| `RELEASE_STRATEGY.md` | Release-Strategie & Best Practices |
| `CHANGELOG.md` | Version-Historie |
| `DEPLOYMENT_REPORT.md` | Aktuelle Status-Report |

---

## 🎓 Beispiel: Kompletter Release-Flow

```bash
# 1. Änderungen vornehmen und testen
cd C:\Projects\wordpressPlugins
# ... code changes ...

# 2. Versionen erhöhen
python update_versions.py

# 3. Änderungen committen
git add -A
git commit -m "Release v1.0.2: Bug fixes + improvements"
git push origin main

# 4. Tags erstellen
python create_plugin_tags.py
git push origin --tags

# 5. Releases erstellen
python create_github_releases.py

# 6. Verifizieren
gh release list -R makr-code/wordpressPlugins | head -20

# Fertig! WordPress erkennt Updates automatisch.
```

---

## ⚡ Performance-Tipps

### Schneller Tagging (nur neue Tags)
```bash
# create_plugin_tags.py skippt automatisch existierende Tags
# → Blitzschnell auch beim 10. Release
python create_plugin_tags.py  # ~1 Sekunde für 26 Plugins
```

### Batch-Release (mehrere Plugins)
```bash
# Alle Scripts sind für Batch-Processing optimiert
python create_plugin_tags.py      # alle Tags auf einmal
python create_github_releases.py  # alle Releases auf einmal
```

### WordPress Admin Performance
```
Update-Check: ~1-3 Sekunden
GitHub API-Abruf: pro 26 Plugins
Keine zusätzliche Last durch Update-System
```

---

## 🔐 Best Practices

✅ **Immer machen:**
- Version erhöhen vor Release
- Aussagekräftige Commit-Messages
- Tests vor Push
- Tags mit Versionen pushen
- CHANGELOG.md aktualisieren

❌ **Nie machen:**
- Version direkt in GitHub editieren (nicht synced)
- Tags manually erstellen (Fehlerquelle)
- WordPress.org mit inkorrekten Versions mischen
- Update-URIs verändern ohne Grund

---

## 📞 Hilfe & Support

**Fragen zu Release-Process?**
→ Siehe `PRODUCTION_DOCUMENTATION.md`

**Fragen zur Strategie?**
→ Siehe `RELEASE_STRATEGY.md`

**Fehler oder Bugs?**
→ GitHub Issues: https://github.com/makr-code/wordpressPlugins/issues

**Script-Fehler?**
→ Prüfe Pfade und Python-Installation

---

## 🎉 Status Summary

- ✅ 26 Plugins produktionsbereit
- ✅ Alle Versionen synced
- ✅ 70 Git-Tags vorhanden
- ✅ 40+ GitHub Releases erstellt
- ✅ WordPress Update-System aktiv
- ✅ Automation vollständig

**Nächstes Release:** Folge diesen Schritten und es läuft automatisch! 🚀

---

**Last Updated:** 2026-08-20  
**Version:** 1.0.1
