# ThemisDB – WordPress Seitenliste

Alle Seiten, die im WordPress-Admin unter **Seiten → Neu hinzufügen** angelegt werden müssen,
damit Navigation, interne Links und Templates korrekt funktionieren.

---

## Pflichtseiten (Hauptnavigation)

| Titel | Slug / URL | Template | Beschreibung |
|-------|-----------|----------|--------------|
| Features | `/features` | Standard-Seite | Übersicht aller ThemisDB-Features |
| Docs | `/docs` | Dokumentationsseite (`page-docs`) | Einstiegsseite der Dokumentation |
| Downloads | `/downloads` | Standard-Seite | Download-Seite (nutzt Plugin `themisdb-downloads`) |
| Benchmarks | `/benchmarks` | Standard-Seite | Performance-Benchmarks (nutzt Plugin `themisdb-benchmark-visualizer`) |
| Community | `/community` | Standard-Seite | Community-Forum / Übersicht |
| Blog | `/blog` | Index (automatisch) | WordPress-Beitragsarchiv |

---

## Dokumentationsseiten (Unterseiten von /docs)

| Titel | Slug / URL | Template | Beschreibung |
|-------|-----------|----------|--------------|
| Getting Started | `/docs/getting-started` | Dokumentationsseite | Schnellstart-Anleitung |
| API Reference | `/docs/api` | Dokumentationsseite | Vollständige API-Dokumentation |
| Architecture Guide | `/docs/architecture` | Dokumentationsseite | Architekturübersicht |
| SQL Guide | `/docs/sql` | Dokumentationsseite | SQL-Referenz und Beispiele |
| Docker Setup | `/docs/docker` | Dokumentationsseite | Docker-Installation und -Konfiguration |
| Changelog | `/docs/changelog` | Dokumentationsseite | Versionshistorie |

---

## Weitere Seiten (Footer-Links & interne Links)

| Titel | Slug / URL | Template | Beschreibung |
|-------|-----------|----------|--------------|
| Query Playground | `/query-playground` | Standard-Seite | Interaktiver Query-Editor (nutzt Plugin `themisdb-query-playground`) |
| Docker Hub | `/docker` | Standard-Seite | Docker-Hub-Übersicht und Anleitung |
| Wiki | `/wiki` | Standard-Seite | Wissensdatenbank (nutzt Plugin `themisdb-wiki-integration`) |
| Support | `/support` | Standard-Seite | Support-Portal (nutzt Plugin `themisdb-order-request`) |
| Contact | `/contact` | Standard-Seite | Kontaktformular |
| Privacy Policy | `/privacy` | Vollbreite-Seite (`page-full-width`) | Datenschutzerklärung |
| Terms of Service | `/terms` | Vollbreite-Seite (`page-full-width`) | Nutzungsbedingungen |
| Imprint | `/imprint` | Vollbreite-Seite (`page-full-width`) | Impressum |

---

## Startseite (Pflicht)

| Titel | Slug / URL | Template | Beschreibung |
|-------|-----------|----------|--------------|
| Home | `/` | Startseite (`front-page`) | Hauptseite mit Hero, Features, CTA |

> **Einstellung:** WordPress-Admin → Einstellungen → Lesen → „Startseite anzeigen" auf
> **Statische Seite** setzen, und die Seite „Home" als Startseite auswählen.

---

## Navigation einrichten (nach dem Anlegen der Seiten)

Die Themes verwenden einen selbstschließenden `wp:navigation`-Block, der auf WordPress-Navigationsmenüs
aus der Datenbank zugreift. Menüs werden im **Site-Editor** (Erscheinungsbild → Editor → Navigation)
konfiguriert.

### Primäres Menü (für `header.html`)

Empfohlene Einträge:
1. Features → `/features`
2. Docs → `/docs`
3. Downloads → `/downloads`
4. Benchmarks → `/benchmarks`
5. Community → `/community`
6. Blog → `/blog`

### Docs-Menü (für `header-docs.html`)

Empfohlene Einträge:
1. Getting Started → `/docs/getting-started`
2. API Reference → `/docs/api`
3. Architecture → `/docs/architecture`
4. SQL Guide → `/docs/sql`
5. Docker → `/docs/docker`
6. Changelog → `/docs/changelog`

---

## Hinweise zu Templates

- **Dokumentationsseite** (`page-docs`): Template mit Sidebar – für alle `/docs/*`-Seiten verwenden.
- **Startseite** (`front-page`): Enthält Hero, Feature-Abschnitte, CTA-Banner.
- **Vollbreite-Seite** (`page-full-width`): Ohne Sidebar, volle Breite – für rechtliche Seiten.
- **Standard-Seite** (`page`): Standard-Layout mit optionalem Page-Hero.

Das Template einer Seite kann beim Bearbeiten rechts unter **Vorlage** ausgewählt werden.
