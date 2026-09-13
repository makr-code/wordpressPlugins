# Slider-Plugin Integration Fix (v1.1.5+)

## Behobene Probleme

### 1. **Fehlende Script-Registrierung**
**Problem**: Das Frontend-Slider-Script (`front-slider.js`) wurde nicht registriert, sondern nur enqueued. Dies führte zu Problemen, wenn WordPress das Script in Abhängigkeitsketten verwaltet.

**Lösung**: 
- Script jetzt in `themisdb_fs_register_assets()` registriert
- Ermöglicht korrekte Abhängigkeitsverwaltung durch WordPress

### 2. **Fallback-Logik zu restriktiv**
**Problem**: Die Enqueue-Logik prüfte auf registrierte Theme-Scripts und **skippte komplett das Plugin-Script**, wenn ein Theme-Script registriert war. Dies führte zu Problemen, wenn das Theme-Script unvollständig oder fehlerhaft war.

**Lösung**:
- Fallback-Logik verfeinert: Prüft nur auf **enqueued** Scripts, nicht auf registrierte
- Neuer Filter `themisdb_front_slider_enqueue_frontend_script` ermöglicht Override
- Plugin-Script lädt immer als Fallback, wenn kein Theme-Script aktiv enqueued ist

### 3. **Block-Script redundant registriert**
**Problem**: Das Gutenberg-Block-Script wurde zweimal registriert:
1. In `themisdb_fs_register_block()` (alt)
2. Als Teil von `register_block_type()` oder `register_block_type_from_metadata()`

**Lösung**:
- Block-Script jetzt zentral in `themisdb_fs_register_assets()` registriert
- Nur ein Registrierungspunkt (DRY-Prinzip)
- `block.json` aktualisiert auf neuen Script-Namen: `themisdb-front-slider-block-js`

## Technische Details

### Asset-Registrierung (init-Hook)
```php
// Alle Assets werden zentralisiert registriert:
- themisdb-front-slider-css (Frontend-Styles)
- themisdb-front-slider-editor-css (Block-Editor-Styles)
- themisdb-front-slider-js (Frontend-Slider-Controller)
- themisdb-front-slider-block-js (Gutenberg-Block-Editor)
```

### Enqueue-Strategie (wp_enqueue_scripts-Hook)
```
1. Styles: Bedingt enqueued (Theme-Override-Filter)
2. Scripts: Immer verfügbar via wp_enqueue_script()
   - Prüft: Ist Theme-Slider-Script enqueued?
   - Ja: Skip Plugin-Script
   - Nein: Enqueue Plugin-Script (Fallback)
```

### Block-Registrierung (init-Hook)
```
1. Nutzt pre-registered Script: themisdb-front-slider-block-js
2. Metadata-gesteuert via block.json (WP >= 5.5)
3. Fallback auf register_block_type() für ältere WP
```

## Kompatibilität

- ✅ WordPress 5.0+
- ✅ PHP 7.4+
- ✅ Gutenberg (Block-Editor)
- ✅ Classic Editor (via Shortcode)
- ✅ Theme-Overrides (Filter-System)

## Filter zur Konfiguration

### Frontend-Stil enqueued
```php
apply_filters( 'themisdb_front_slider_enqueue_frontend_style', 
    $should_enqueue );  // Default: theme_controls_presentation ? false : true
```

### Frontend-Script enqueued
```php
apply_filters( 'themisdb_front_slider_enqueue_frontend_script', 
    $should_enqueue );  // Default: theme_controls_slider_js ? false : true
```

## Migration von älteren Versionen

Keine Breaking Changes! Die Behebungen sind vollständig rückwärtskompatibel:
- Shortcode-Syntax: Unverändert
- Block-Attribute: Unverändert
- JavaScript-API: Unverändert

## Verifikation

Nach der Aktualisierung prüfen Sie:

1. **Frontend-Slider funktioniert**
   - Shortcode: `[themisdb_front_slider posts="5" interval="5000"]`
   - Block: Gutenberg-Editor → "ThemisDB Front Slider" Block hinzufügen
   - Prüfe: Autoplay, Navigationspfeile, Dot-Navigation, Tastatur-Steuerung

2. **Assets korrekt geladen**
   - Browser-DevTools → Network-Tab
   - Verifiziere CSS + JS geladen (200 OK)

3. **Keine Konflikte mit Theme**
   - Console-Fehler prüfen
   - Inspector: `.themisdb-fs-wrapper` hat korrekte Klassen
