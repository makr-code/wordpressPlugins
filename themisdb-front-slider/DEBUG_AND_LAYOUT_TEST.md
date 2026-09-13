# ThemisDB Front Slider - Debug & Layout Test Guide

## Integrations-Status: ✅ BESTANDEN (47/48)

Die statische Analyse zeigt, dass die Plugin-Integration **korrekt funktioniert**.

### Test-Ergebnisse

#### ✅ Bestandene Tests (47)

**Dateistruktur:**
- ✓ Alle 7 erforderlichen Dateien vorhanden
- ✓ Dateigröße normal (Plugin: 39KB, JS: 16KB, Template: 14KB)

**Plugin-Header:**
- ✓ Name, Version, Author, License korrekt konfiguriert

**Asset-Management:**
- ✓ 4 Assets registriert (CSS, JS, Block-JS)
- ✓ Enqueue-Logik mit Filter-Hooks
- ✓ Fallback-System aktiviert

**Block-Registrierung:**
- ✓ Gutenberg-Block funktionsfähig
- ✓ Editor-Script korrekt verlinkt
- ✓ Block.json konsistent

**JavaScript-Features:**
- ✓ initSlider() - Slider-Initialisierung
- ✓ boot() - Auto-Discovery von Slider-Elementen
- ✓ goTo() - Slide-Navigation
- ✓ startTimer/stopTimer - Autoplay-Kontrolle
- ✓ Keyboard-Navigation (Arrow-Tasten)
- ✓ Touch/Swipe-Events
- ✓ ARIA-Accessibility-Attribute

**Template & CSS:**
- ✓ Vollständige HTML-Struktur
- ✓ Navigation (Prev/Next)
- ✓ Dot-Pagination
- ✓ Timer-Bar
- ✓ Responsive Design
- ✓ Transform-Animationen

#### ⚠️ Minor-Issue (1)

**CSS-Variablen:**
- ❌ `--tfs-*` nicht in separater CSS-Datei definiert
- ℹ️ **Ist OK**: Variablen werden inline im PHP-Template gesetzt
  ```html
  <div ... style="--tfs-accent: #0284c7;">
  ```
- 💡 **Optional-Verbesserung**: Könnten in `assets/css/front-slider.css` definiert werden:
  ```css
  .themisdb-fs-wrapper {
      --tfs-accent: #0284c7;
  }
  ```

---

## Debug-Checkliste

Führen Sie diese Checks durch, wenn Sie das Plugin testen:

### 1. **WordPress aktiviert das Plugin**
```php
// Prüfe in wp-content/debug.log:
// - "Plugin activated: themisdb-front-slider"
// - Keine Fehler bei der Aktivierung
```

### 2. **Assets sind korrekt geladen**
Browser DevTools → Network-Tab:
```
✓ assets/css/front-slider.css (200 OK)
✓ assets/js/front-slider.js (200 OK)
✓ assets/css/block-editor.css (bei Block-Editor: 200 OK)
✓ assets/js/block.js (bei Block-Editor: 200 OK)
```

### 3. **Shortcode funktioniert**
Seite mit `[themisdb_front_slider posts="5"]` öffnen:
```
✓ HTML enthält: <div class="themisdb-fs-wrapper">
✓ Keine JavaScript-Fehler in Console
✓ Slider animiert beim Laden
```

### 4. **Gutenberg-Block funktioniert**
Block-Editor → "ThemisDB Front Slider" Block:
```
✓ Block ist in der Block-Library sichtbar
✓ Block-Vorschau rendert
✓ Inspector Controls funktionieren (Posts, Interval, Layout)
✓ Live-Vorschau aktualisiert sich
```

### 5. **Debug-Logs überprüfen**
```bash
# Logs anschauen:
tail -f wp-content/debug.log

# Keine Fehler wie:
# - "Failed to enqueue script"
# - "Call to undefined function"
# - "Undefined variable"
```

---

## Layout & Rendering Überprüfung

### Desktop-Layout
```
┌─────────────────────────────────────┐
│  Navigation  [< Previous]  [Next >]  │
│  ┌─────────────────────────────────┐ │
│  │ [Kategorien/Tags]               │ │
│  │                                 │ │
│  │ Artikel-Titel                   │ │
│  │ Lorem ipsum dolor...            │ │
│  │                                 │ │
│  │ [Beitrag ansehen] [PDF] [Audio] │ │
│  │                                 │ │
│  │ [Artikel-Bild]                  │ │
│  │                                 │ │
│  └─────────────────────────────────┘ │
│  [●] [○] [○] [○] [○]  Dots          │
│  ████░░░░░░░░░░░░░░░  Progress Bar   │
└─────────────────────────────────────┘
```

### Responsive Verhalten
- **Mobile** (<768px): Stack vertikal, Bild oben/unten
- **Tablet** (768-1024px): 2-spaltig, kompakte Abstände
- **Desktop** (>1024px): Full-Layout mit Blob-Dekorationen

---

## Automatische Tests ausführen

### Test 1: Statische Integration (ohne WordPress)
```bash
cd themisdb-front-slider
node test-integration-static.js
```
Ergebnis: Überprüft PHP-Code, JavaScript, Template, CSS-Struktur

### Test 2: Layout & Debug (mit Playwright + Browser)
```bash
# Erfordert: WordPress läuft auf http://localhost:8888
# Seite mit Slider muss existieren (z.B. /test-slider)

WP_BASE_URL=http://localhost:8888 node test-playwright.js
```
Outputs:
- `slider-test-screenshot.png` - Visueller Check
- Console-Logs - Debug-Ausgaben
- DOM-Struktur - HTML-Validierung
- Performance-Metriken

---

## Häufige Fehlerursachen

| Fehler | Ursache | Lösung |
|--------|--------|---------|
| "Script not loaded" | Script nicht enqueued | Prüfe `wp_enqueue_scripts` Hook |
| "Slider nicht sichtbar" | `display: none` CSS | Check `.themisdb-fs-wrapper` Styles |
| "JS Errors in Console" | Dependency-Problem | Prüfe `wp_register_script` Dependencies |
| "Block nicht im Editor" | Script nicht registriert | Prüfe Block.json `editorScript` |
| "Autoplay funktioniert nicht" | `data-autoplay="0"` | Template: `autoplay` Parameter prüfen |
| "Animationen ruckelig" | Browser-Performance | Prüfe `will-change: transform` CSS |

---

## Debugging: JavaScript-Aktivierung

Im Browser Console (`F12`):

```javascript
// 1. Slider-Element finden
document.querySelector('.themisdb-fs-wrapper')
// → sollte das Slider-Element zurückgeben

// 2. Initialisierungs-Status prüfen
document.querySelector('.themisdb-fs-wrapper').dataset.tfsInit
// → sollte "1" sein (oder "true")

// 3. Aktiven Slide überprüfen
document.querySelector('.themisdb-fs-slide.is-active')
// → sollte den aktiven Slide zeigen

// 4. Track-Position überprüfen
document.querySelector('.themisdb-fs-track').style.transform
// → sollte "translateX(-0%)" oder ähnlich sein

// 5. Nächsten Slide navigieren
document.querySelector('.themisdb-fs-next').click()
// → Slider sollte sich bewegen
```

---

## Performance-Metriken

Empfohlene Werte:

| Metrik | Ziel |
|--------|------|
| JavaScript Load | < 50ms |
| First Paint | < 1s |
| Autoplay Start | < 2s |
| Navigation Response | < 100ms |
| CSS Rendering | GPU-beschleunigt |

Überprüfung:
```javascript
// In Console:
performance.getEntriesByName('front-slider.js')
// → Zeigt Load-Zeit
```

---

## Verifikation der Fixes (v1.1.5+)

### ✅ Fix 1: Script-Registrierung
```php
// Vor: nur wp_enqueue_script()
// Nach: wp_register_script() + wp_enqueue_script()
if ( wp_script_is( 'themisdb-front-slider-js', 'registered' ) ) {
  echo "✓ Script korrekt registriert";
}
```

### ✅ Fix 2: Fallback-Logik
```php
// Vor: prüft "registered" + "enqueued"
// Nach: prüft nur "enqueued"
$theme_controls = wp_script_is( 'themisdb-hero-slider', 'enqueued' );
echo $theme_controls ? "Theme-Script aktiv" : "Plugin-Fallback aktiv";
```

### ✅ Fix 3: Block-Script Konsistenz
```json
// block.json
{
  "editorScript": "themisdb-front-slider-block-js"
}
```
Vorher: Duplikate/Inkonsistenzen. Jetzt: Ein Source-Of-Truth.

---

## Weitere Ressourcen

- [Plugin-Dokumentation](./INTEGRATION_FIX.md)
- [Slider-JavaScript](./assets/js/front-slider.js)
- [Gutenberg-Block](./assets/js/block.js)
- [HTML-Template](./templates/slider.php)
- [Styling](./assets/css/front-slider.css)
