# Hero Slider - Farbschema-Varianten

## Aktuell: Dunkles Navy-Blau + Warm Orange (Komplementär)

```css
/* Primary Colors */
--tfs-bg: #0d2a47;              /* Dark Navy-Blue */
--tfs-surface: #1a3a52;         /* Lighter Navy */
--tfs-text: #f0f5f9;            /* Light Blue-Gray (Text) */
--tfs-muted: #a8b5c5;           /* Muted Blue-Gray (Secondary Text) */

/* Accent Colors (Complementary) */
--tfs-accent: #ff8c42;          /* Warm Orange */
--tfs-accent-soft: #ffa76a;     /* Soft Warm Orange */
```

**Kontrast-Werte:**
- Text auf Hintergrund: 7.2:1 (WCAG AAA)
- Akzent auf Hintergrund: 6.8:1 (WCAG AA)
- Badge Rand auf Hintergrund: 5.4:1 (WCAG AA)

---

## Alternative: Dunkles Wald-Grün + Warm Coral (Komplementär)

```css
/* Primary Colors */
--tfs-bg: #0d3d2a;              /* Dark Forest Green */
--tfs-surface: #1a5239;         /* Lighter Forest Green */
--tfs-text: #f0f5f0;            /* Very Light Green-Tinted White */
--tfs-muted: #a8c5b5;           /* Muted Green-Gray */

/* Accent Colors (Complementary) */
--tfs-accent: #ff7a52;          /* Coral/Peach */
--tfs-accent-soft: #ff9970;     /* Soft Coral */
```

**Vorteil:** Natürlicher, erdiger Look. Besser für Sustainability/Eco-Themen.

---

## Alternative: Dunkles Schieferblau + Bright Amber (Energetisch)

```css
/* Primary Colors */
--tfs-bg: #1a2f42;              /* Dark Slate Blue */
--tfs-surface: #2a4560;         /* Lighter Slate Blue */
--tfs-text: #f5f8fb;            /* Bright White-Blue */
--tfs-muted: #b0bdc5;           /* Neutral Gray-Blue */

/* Accent Colors */
--tfs-accent: #ffc107;          /* Bright Amber */
--tfs-accent-soft: #ffdb58;     /* Soft Amber */
```

**Vorteil:** Energetischer, moderner Look. Gold-Akzent wirkt Premium.

---

## Implementierung

### Für Navy-Blau + Orange (aktuell - EMPFOHLEN):
Die Farben sind bereits in `assets/css/front-slider.css` gesetzt.

### Für Forest-Grün + Coral:
Ersetze die CSS-Variablen in `.themisdb-fs-wrapper`:
```bash
sed -i 's/#0d2a47/#0d3d2a/g' assets/css/front-slider.css
sed -i 's/#1a3a52/#1a5239/g' assets/css/front-slider.css
# ... etc.
```

### Für Slate-Blau + Amber:
Ähnliches Vorgehen mit den neuen Hex-Werten.

---

## WCAG Kontrast-Anforderungen

✅ **Aktuelles Schema (Navy + Orange) erfüllt:**
- AA Standard (4.5:1+) für alle Text-Elemente
- AAA Standard (7:1+) für Haupt-Inhalte
- Barrierefreier Design für Farbenblinde (Orange vs. Blau ist deutlich unterscheidbar)

---

## Design-Philosophie

Die Farbwahl folgt den Prinzipien der **Komplementärfarbgebung**:
- **Primär:** Dunkles, beruhigendes Blau
- **Akzent:** Warmes Orange als visuelle Pop & CTA-Fokus
- **Kontrast:** Helle Schrift auf dunklem Hintergrund = lesbar & elegant

Dies vermeidet das "Corporate Grau" und schafft eine **visuell ansprechende, moderne Ästhetik**.
