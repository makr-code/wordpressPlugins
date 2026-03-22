# ThemisDB Plain Theme

Minimales WordPress Block-Theme im ThemisDB-Farbschema.

## Ziel

- Schlichte, wartungsarme Basis
- Konsistentes ThemisDB Branding
- Gute Lesbarkeit ohne visuelle Ueberladung

## Enthalten

- `theme.json` mit ThemisDB-Palette und Basis-Typografie
- `style.css` mit klaren Variablen und einfachen Komponenten
- `parts/header.html` und `parts/footer.html`
- Templates: `front-page`, `index`, `page`, `single`
- Zusatz-Templates:
	- `page-front-compact` (kompakte Landingpage)
	- `page-front-docs-compact` (kompakte Docs-Landingpage)
- Stilvariationen unter `styles/`:
	- `Plain Light`
	- `Plain Dark Header`
	- `Plain Landing Focus`
- Block Patterns unter `patterns/`:
	- `Hero Plain`
	- `Feature Grid Plain`
	- `CTA Strip Plain`
	- `Docs Hero + Quick Links Plain`
	- `Pricing Comparison Plain`

## Startseiten-Setup

Die `front-page` ist vorkonfiguriert und rendert automatisch folgende Muster in Reihenfolge:

1. `Hero Plain`
2. `Feature Grid Plain`
3. `Pricing Comparison Plain`
4. `Docs Hero + Quick Links Plain`
5. `CTA Strip Plain`

Damit wirkt das Theme nach Aktivierung direkt wie eine komplette Landingpage.

## Kompakte Startseite

Optional steht eine reduzierte Variante bereit:

- Template: `page-front-compact.html`
- Inhalt: `Hero Plain` + `Pricing Comparison Plain` + `CTA Strip Plain`

Verwendung:

1. Seite im WordPress Editor öffnen (z. B. Startseite).
2. Rechts unter Template das Template `Front Compact` (bzw. `page-front-compact`) wählen.
3. Speichern.

## Kompakte Docs-Startseite

Optional steht eine dokumentationsfokussierte Variante bereit:

- Template: `page-front-docs-compact.html`
- Inhalt: `Hero Plain` + `Docs Hero + Quick Links Plain` + `CTA Strip Plain`

Empfehlung:

- `page-front-compact`: wenn Download/Pricing im Vordergrund steht
- `page-front-docs-compact`: wenn Dokumentation/Onboarding im Vordergrund steht

## Aktivierung

1. Ordner `themisdb-theme-plain` nach `wp-content/themes/` kopieren.
2. In WordPress unter Darstellung > Themes aktivieren.
3. Optional: im Site Editor Navigation und Startseite anpassen.
4. Unter Site Editor > Stile kannst du eine der Plain-Varianten aktivieren.
5. Unter Site Editor > Muster findest du die Plain-Patterns.

## Farbschema

- Primary: `#003366`
- Secondary: `#0078d4`
- Accent: `#50e6ff`
- Surface: `#f5f8fc`
- Text: `#12263a`
