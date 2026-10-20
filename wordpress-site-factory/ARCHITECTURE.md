# ThemisDB WordPress Site Factory – Architektur, Zielmodell und Migrations-Contract

## 1. Ziel und Zweck

Die Site Factory im Ordner `wordpress-site-factory` definiert das verbindliche Architekturmodell für neue ThemisDB-WordPress-Installationen. Sie bildet den Greenfield-Standard sowie die Referenz der laufenden ThemisDB-Instanz unter `themisdb.org` ab und dokumentiert gleichzeitig die Delta-Liste zu einer bestehenden Live-Installation.

Die Factory ist die Grundlage für:

- neue WordPress-Instanzen mit reproduzierbarem Aufbau
- konsistente Theme-, Plugin- und Seitenstruktur
- verbindliche Taxonomie und Tags
- definierte URL-/Domain-Strategie
- Migrations- und Import-Contract für Inhalte, Medien und Redirects
- transparente Dokumentation der Abweichungen zwischen Greenfield-Setup und laufender Installation

## 2. Architekturmodell

### 2.1 Grundprinzip

Der Prozess folgt einem klaren, sequentiellen Aufbau:

1. Bootstrap der WordPress-Instanz
2. Sicherstellen von Theme und Plugins
3. Anlegen der reservierten Taxonomien / Tags
4. Erzeugen der öffentlichen Seitenstruktur
5. Validieren der Public-Content-Modelle
6. Importieren von Dokumenten, Inhalten und Medien aus den repo-basierten Quellen

Die Factory soll dabei nicht als ad-hoc-Prozess interpretiert werden, sondern als deterministisches Build-Script für eine ThemisDB-Site.

### 2.2 Referenzarchitektur

Die Referenzinstallation basiert auf:

- WordPress Core
- Theme: `themisdb-pulse`
- Plugins: `themisdb-quality-meter`, `themisdb-front-slider`, `themisdb-support-portal`
- Public-Tag-Filter: aktiv
- Standard-Setup über `config/site.json`, `config/taxonomy.json` und `config/pages.json`

Das ist der Mindest-Contract für eine betriebene ThemisDB-WordPress-Site. Abweichungen sind nur dann erlaubt, wenn sie als explizite Live-Differenz dokumentiert werden.

### 2.3 Module der Factory

- `build_site.py` – zentraler Einstiegspunkt; orchestriert Gesamtaufbau
- `bootstrap.py` – prüft WordPress-Verbindung, Theme- und Pluginstatus, aktiviert Abhängigkeiten
- `taxonomy.py` – erstellt die reservierten Tags und trennt Auswahl-, Sichtbarkeits- und Descriptive-Tags
- `pages.py` – erzeugt die öffentliche Seitenstruktur und setzt die Startseite
- `validation.py` – prüft die installierten Strukturen und erwartet Public-Model-Validität
- `.secure/site.json` – System- und Site-Config pro Profil, getrennt vom Git-tracked Repo
- `config/taxonomy.json` – verbindliche Tag-Spezifikation
- `config/pages.json` – definierte Seitenstruktur mit Slugs, Menu-Order und Tags

## 3. Zielmodell für Theme, Plugins und Seiten

### 3.1 Theme-Modell

Das aktive Theme ist `themisdb-pulse` und gilt als verbindlich für das ThemisDB-Public-Frontend.

Erwartete Rolle:

- Public-Frontend für Homepage, Features, Dokumentation, Blog, Download, Pricing, Support
- Semantische Page-Templates außerhalb von Plain HTML
- Front-end-Auswahl auf Basis reservierter Tags

### 3.2 Plugin-Model

Die Factory erwartet mindestens diese Plugins in einem installierten oder aktivierten Zustand:

- `themisdb-quality-meter/themisdb-quality-meter`
- `themisdb-front-slider/themisdb-front-slider`
- `themisdb-support-portal/themisdb-support-portal`

Zusätzliche Plugins sind erlaubt, aber nur dann Teil des Contractes, wenn sie in `config/site.json` explizit aufgeführt sind oder als Teil der Live-Differenz dokumentiert werden.

### 3.3 Seitenmodell

Das Public-Content-Modell definiert folgende Kernseiten:

- `home` – Startseite / Frontpage
- `features` – Produkt- und Leistungsübersicht
- `documentation` – Dokumentationsbereich
- `blog` – News- und Blog-Feed
- `downloads` – Downloads und Distributionspakete
- `pricing` – Preismodell und Pläne
- `support` – Support- und Kontaktbereich
- `getting-started` – Unterseite im Dokumentationsbereich
- `api-reference` – technische Referenzseite

Die Seiten sind nicht „frei erfunden“, sondern Teil des verbindlichen Public-Structures. Jede neue Seite muss entweder:

- in `config/pages.json` als Teil des Target Models definiert sein, oder
- als Live-Differenz mit Begründung und Mapping dokumentiert werden.

## 4. Taxonomie- und Tag-Definitionen

Die Tag-Semantik ist strikt getrennt in drei Bereiche.

### 4.1 Selection-relevant tags (reserviert)

Diese Tags sind für ThemisDB-Selection-Logic und Public-Frontend-Filtering reserviert:

- `announcement`
- `hero`
- `feature`
- `documentation`
- `pricing`
- `download`
- `edition`
- `blog`
- `support`
- `recommended`
- `lesenswert`
- `editor-pick`
- `release`
- `event`
- `conference`
- `security`
- `benchmark`
- `case-study`
- `trial`

Im aktuellen Factory-Contract ist `security` ausdrücklich Teil der reservierten Public-Selection-Tags, weil das Tag-Set in `config/taxonomy.json` als Greenfield-Definition definiert ist. Descriptive/Legacy-Bezeichnungen können weiterhin existieren, aber der Canonical-Contract der Factory bleibt der Selection-Set.

Diese Tags sind der fachliche Kern des Frontend-Selectors und dürfen nicht durch freie, generische Werte ersetzt werden.

### 4.2 Restricted / visibility tags

Diese Tags sind keine Public-Tags und werden für Sichtbarkeits- und Rollenlogik verwendet:

- `restricted`
- `internal`
- `admin-only`

### 4.3 Descriptive / optional tags

Diese Tags dienen der editorialen Einordnung und sind nur sekundär für Auswahl nutzbar:

- `webinar`, `docker`, `binary`, `package`, `compendium`, `diagram`, `architecture`, `roadmap`, `changelog`, `community`, `demo`, `how-to`, `guide`, `customer-story`, `support-portal`, `ticket`, `knowledge-base`, `helpdesk`, `api`, `reference`, `runbook`, `audit`, `compliance`, `performance`, `testing`, `governance`, `analysis`, `research`, `air-gap`, `ddil`, `local-ai`, `sovereignty`, `data-sovereignty`, `zero-trust`, `on-premise`, `self-hosted`, `mission-critical`, `incident-response`, `operational-resilience`, `resilience`

### 4.4 Regel

Diese Trennung ist verbindlich:

- Selection = Public and theme-relevant
- Restricted = Visibility and role logic
- Descriptive = Optional editorial metadata only

Alle Inhalte, die im Public-Frontend verwendet werden, müssen auf den Auswahl-Tag-Set zurückgreifen. Freie Tags ohne dieses Definitions-Schema sind keine gültige Alternative.

## 5. URL- und Domain-Strategie

### 5.1 Canonical domain

Die primäre Canonical-URL des Public-Frontends ist:

- `https://themisdb.org`

### 5.2 Environment mapping

- Development / local: `http://localhost/wordpress`
- Test / staging: `https://staging.themisdb.org` oder eigenes Test-Subdomain
- Production: `https://themisdb.org`

### 5.3 Slug-Strategie

Seiten werden als lowercase, hyphen-separated slugs definiert:

- `home`
- `features`
- `documentation`
- `getting-started`
- `api-reference`
- `pricing`
- `support`
- `downloads`
- `blog`

### 5.4 Redirect-Contract

Jede Migration einer bestehenden WordPress-Instanz muss für alle veränderten Slugs, Seitentitel und Domainwechsel explizit Redirects definieren.

Gültig sind:

- 301 für inhaltlich verschobene Seiten
- 302 nur für temporäre Übergänge
- keine Silent-Redirects ohne Eintrag in der Redirect-Liste

Der Redirect-Contract ist Teil der Migrations- und Import-Definition und muss in der Migration dokumentiert werden.

## 6. Migrations- und Import-Contract

### 6.1 Greenfield-Setup

Ein neues ThemisDB-Setup folgt dem Target Model dieser Factory:

- Theme `themisdb-pulse` aktiv
- definierte Plugins aktiv
- Standard-Site-Konfiguration aus `config/site.json`
- reservierte Tag-Sets angelegt
- Public-Seitenstruktur aus `config/pages.json`
- Frontpage auf `home` gesetzt
- Import der Inhalte aus `source-links/` oder repo-basierten Content-Sources

### 6.2 Live-Referenz und Delta-Liste

Die laufende ThemisDB-Instanz unter `themisdb.org` dient als Referenzmodell. Jede bestehende Installation wird als Live-Differenz betrachtet, wenn sie eine der folgenden Abweichungen aufweist:

- andere Theme- / Plugin-Konfiguration
- zusätzliche oder fehlende Public-Seiten
- abweichende Slugs oder Menüpunkte
- zusätzliche Tags, die nicht im reservierten Auswahl-Set liegen
- abweichende Redirect- oder Canonical-Logik
- Medien und Dateinamen, die im repo nicht durch einen importierten Standard abgebildet sind

Diese Delta-Liste muss explizit dokumentiert werden, bevor ein neues Setup aus einer Live-Instanz übernommen wird.

### 6.3 Import-Contract

Für neue ThemisDB-Instanzen gilt:

1. Quellinhalte kommen aus der repo-lokalen Source-Bridge (`source-links/`) oder aus dem dokumentierten Import-Source-Set.
2. Inhalte werden zunächst semantisch normalisiert und als Block-/Markdown-Model validiert, bevor sie in WordPress gerendert werden.
3. Medien werden in einer deterministischen Reihenfolge importiert und mit stabilen Dateinamen / Slugs versehen.
4. Attachments, Dokumente und Bilddateien müssen als Teil des Import-Contracts verwaltet werden.
5. Redirects und Domain-Änderungen werden priorisiert und müssen in der Migrationsliste gepflegt werden.
6. Ein Import darf nur dann als abgeschlossen gelten, wenn die Ziel-Seite, Tags und URL-Struktur dem Zielmodell entsprechen.

### 6.4 Media- und Redirect-Import

Der Import muss folgende Kriterien erfüllen:

- Medien aus vorhandener Instanz werden als Assets dokumentiert und bei einem Seiten-/Domain-Wechsel auf neue Slugs verwiesen
- Bildverweise, internes Link-Mapping und relativer Pfad-Resolution werden in der Migration geprüft
- Redirects müssen vollständig dokumentiert werden, damit veraltete `themisdb.org`-Links weiterhin korrekt auflösen und erreichbar bleiben
- Inhalte mit einem geänderten Slug erhalten einen 301-Redirect nach dem neuen Ziel

### 6.5 Übergang von einer laufenden Installation zu einem neuen Setup

Der Übergang erfolgt in diesem Muster:

1. Live-Instanz analysieren
2. Delta-Liste dokumentieren
3. Zielmodell der Factory anwenden
4. Tags, Seiten und Theme strukturell auf Greenfield-Standard normalisieren
5. Inhalte / Medien / Redirects migrieren
6. Replikation mit Validierung prüfen
7. Go-live nur mit dokumentierter Differenzliste

### 6.6 Migrations-Checkliste für Greenfield- und Live-Delta-Übergänge

Für jede neue ThemisDB-Installation oder Migration gilt die folgende Ausführungsreihenfolge als verbindlicher Ablauf:

- [ ] Zielmodell und Scope prüfen: Theme, Plugins, Site-Config und Seitenmodell festlegen
- [ ] Source-Bridge und Canonical-Inputs prüfen: `source-links/`, Markdown-/Dokumentquellen und Medienordner validieren
- [ ] Delta-Liste der Live-Instanz erfassen: Theme, Plugins, Seiten, Slugs, Tags, Domain, Redirects, Medien und Legacy-HTML dokumentieren
- [ ] Greenfield-Target aufbauen: WordPress-Connection, Theme, Plugins und reservierte Taxonomie aus `config/*` anwenden
- [ ] Public-Page-Model erzeugen: Home, Features, Documentation, Blog, Downloads, Pricing, Support und Referenzseiten sichern
- [ ] Redirect- und URL-Plan definieren: Canonical-URL, Slugs, 301/302-Regeln und Übergang von Legacy-URLs kennzeichnen
- [ ] Inhalte importieren: Markdown-/Block-Format normalisieren, Metadaten validieren und semantische Tags zuordnen
- [ ] Medien importieren: Dateien, Attachments und Bild-Links mit stabilen Namen und zugeordneten Slugs migrieren
- [ ] Replikations-Check durchführen: Seiten, Tags, Links, Redirects und Frontend-Auswahl gegen das Zielmodell validieren
- [ ] Go-live Freigabe: nur mit dokumentierter Differenzliste und erfolgreicher Validierung freigeben

Der Checklisten-Block ist die verbindliche Execute-Liste für neue Instanzen und für den Wechsel von einer laufenden Installation auf das Greenfield-Zielmodell.

## 7. Abgrenzung Greenfield-Setup vs. Live-Differenz

### Greenfield-Setup

Ein Greenfield-Setup ist die Standard-Definition der Factory. Es wird erwartet, dass:

- Theme und Plugins standardisiert sind
- die Tag-Sets aus `config/taxonomy.json` angewendet werden
- die Core-Seiten aus `config/pages.json` existieren
- Seiten und Inhalte auf `themisdb.org` / `localhost`-Konventionen aufgesetzt werden
- keine ungebuchte Abweichung zwischen Referenzmodell und Setup existiert

### Live-Differenz

Eine Live-Differenz ist jede von der Factory abweichende Konfiguration, die in einer realen Installation bestehen kann. Dazu gehören:

- handgefertigte Seiten über Legacy-HTML
- zusätzliche Tags ohne reservierten Zweck
- veraltete Slugs oder eingebaute Redirect-Workarounds
- alte Domain-/Subdomain-Strategien
- unstrukturierte Inhalte ohne importierbaren Standard

Live-Differenzen sind nicht „fehlerhaft“, aber sie müssen im Import- und Migrationsplan als Inkonsistenzen sichtbar sein.

## 8. Akzeptanzkriterien und Verifikation

Die Architektur ist erfüllt, sobald diese Punkte im Repo dokumentiert und reproduzierbar sind:

- Architektur-Doku existiert im Repo
- Theme-, Plugin- und Seitenmodell sind dokumentiert
- Taxonomie-/Tag-Definitionen sind verbindlich festgelegt
- URL-/Domain-Strategie ist beschrieben
- Migrations- und Import-Contract für neue Instanzen ist klar formuliert
- Abgrenzung zwischen Greenfield-Setup und Live-Differenz ist dokumentiert

Dieses Dokument ist die verbindliche Spezifikation für die Site Factory und muss bei Änderungen am Factory-Contract mit aktualisiert werden.
