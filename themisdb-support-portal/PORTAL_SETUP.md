# Support Portal – WordPress-Seiten-Setup

Dieses Dokument beschreibt, wie die Frontend-Seiten für das **themisdb-support-portal**-Plugin in WordPress eingerichtet werden.

## Überblick

Das Plugin stellt folgende **Shortcodes** zur Verfügung:

| Shortcode | Zweck | Zielgruppe |
|-----------|-------|-----------|
| `[themisdb_support_login]` | Login-Gate | Öffentlich → Kunden |
| `[themisdb_support_portal]` | Haupt-Ticket-Portal | Eingeloggte Kunden |
| `[themisdb_support_hub]` | Support, Profil und optionale Order-/Lizenzbereiche als Tabs | Besucher und Kunden |
| `[themisdb_lifecycle_portal]` | Vertragsänderungs-/Kündigungsportal | Eingeloggte Kunden |
| `[themisdb_cockpit]` | Unified-Status-Dashboard (Lizenz, Tickets, Builds) | Eingeloggte Kunden |

## Empfohlenes Seiten-Layout

### Gemeinsame Einbindung mit dem eigenen Shop

WooCommerce ist keine Voraussetzung und kein vorgesehener Integrationspartner.
Der eigene Lizenzshop wird ueber `/products/` bereitgestellt; Warenkorb
und Kasse erscheinen in einem gemeinsamen Plugin-Overlay, erreichbar
ueber das Header-Warenkorbsymbol. `/support/` dient als Kundenportal.
Ein einzelner `[themisdb_support_hub]`-Block verbindet dort Support,
Bestellungen und Vertraege, soweit die zugehoerigen Pluginmodule verfuegbar sind.
Bereits eingebettete Portale nicht doppeln. Der Hub verwendet bei aktivem
Checkoutmodul denselben Warenkorbdialog statt eines zweiten Bestelloverlays.
Separate Warenkorb- und Bestellseiten sind nicht erforderlich.

Layout, Login und Interaktion gehoeren dem Plugin. Das Theme passt die
Palette ueber `--themisdb-ui-primary`, `--themisdb-ui-primary-hover`,
`--themisdb-ui-text`, `--themisdb-ui-muted`, `--themisdb-ui-border`,
`--themisdb-ui-surface` und `--themisdb-ui-surface-alt` auf den
Support-Wrappers an. Ohne Theme-Werte bleiben die bisherigen Plugin-Defaults
aktiv. Pulse belegt diese Schnittstelle mit seinen Farbvarianten.

### 1. Kundenportal (`/kundenportal`)
- **Sichtbar für:** Alle
- **Inhalt:**
  ```
  [themisdb_support_portal]
  ```
- **Beschreibung:** Öffentlich sichtbare Seite. Das Portal zeigt unangemeldeten Besuchern bereits das Anmeldeformular. Nach dem Login wird das Ticket-Portal angezeigt. Nicht zusätzlich das Login-Shortcode einbetten, da sonst dasselbe Formular doppelt erscheint.
- **Einrichtung:** Siehe [Automated Setup](#automated-setup) oder manuell unten.

### 2. Mein Cockpit (`/mein-cockpit`)
- **Sichtbar für:** Eingeloggte Kunden nur
- **Inhalt:**
  ```
  [themisdb_cockpit]
  ```
- **Beschreibung:** Dashboard mit aggregiertem Status über Lizenz, Tickets, Bestellungen, Builds, etc.
- **Automatische Authentifizierung:** Das Shortcode prüft automatisch `is_user_logged_in()`.

### 3. Vertragsänderung & Kündigung (`/vertragsaenderung`)
- **Sichtbar für:** Eingeloggte Kunden nur
- **Inhalt:**
  ```
  [themisdb_lifecycle_portal]
  ```
- **Beschreibung:** Verwaltung von Vertragsänderungsanträgen und Kündigungen (§8.6 & §8.7 ARCHITECTUR.md).

---

## Theme-unabhaengige Integration (1.1.1)

- Auf Seiten mit den Slugs `support`, `kundenportal` und `support-portal` ergaenzt
  der Content-Filter das Portal, sofern kein Support-Shortcode im Editorinhalt
  vorhanden ist. Das Theme muss den Seiteninhalt ausgeben. Weitere Slugs sind
  ueber `themisdb_support_embed_page_slugs` moeglich.
- Explizite Support-Shortcodes in einzelnen Beitraegen erhalten ebenfalls die
  Plugin-Assets. Fuer direkt in Templates erzeugte UI koennen Themes waehrend
  `wp_enqueue_scripts` `themisdb_support_enqueue_frontend_assets()` aufrufen.
  Diese Funktion liefert Skript, Icons und dieselbe lokalisierte Konfiguration;
  Themes sollen keine eigene Kopie der Konfiguration pflegen.
- Der Hub puffert ausschliesslich sein eigenes HTML. Tabs, verschachteltes
  Profil-Dashboard und Auftragsdialog funktionieren mit den Plugin-Assets ohne
  Pulse. Pfeiltasten, Home/End und Escape werden unterstuetzt; beim Schliessen
  des Auftragsdialogs geht der Fokus zum Ausloeser zurueck.
- Nicht verfuegbare Order-/Lizenzmodule erhalten keine leeren Tabs. Pulse-
  Breadcrumbs erscheinen nur, wenn deren Shortcodes registriert sind.
- Der Auftragsdialog wird vom Plugin gesteuert. Pulse behaelt seinen Handler
  nur als Fallback fuer aeltere Plugin-Versionen. Seine bestehenden speziellen
  Support-Seitenvorlagen und Login-Overlays bleiben erhalten.
- Hub-CSS fuer gemeinsame `tv3-*`-Klassennamen ist auf den Hub begrenzt und
  veraendert keine gleichnamigen Theme-Komponenten ausserhalb des Plugins.

## Automated Setup

Für eine schnelle Einrichtung in der Entwicklung oder beim Deployment stehen **Python-Scripts** in `page-content/` zur Verfügung:

### `create_portal_pages.py`
Legt die **Produktionsseiten** an.

**Aufruf (Dry-Run):**
```bash
cd page-content
python create_portal_pages.py \
  --base "http://localhost/wordpress/wp-json/wp/v2/" \
  --user "admin" \
  --pass "app-password-hier"
```

**Aufruf (mit Änderungen):**
```bash
python create_portal_pages.py --apply \
  --base "http://localhost/wordpress/wp-json/wp/v2/" \
  --user "admin" \
  --pass "app-password-hier"
```

**Umgebungsvariablen (Alternative):**
```powershell
$env:WP_BASE = "http://localhost/wordpress/wp-json/wp/v2/"
$env:WP_USER = "admin"
$env:WP_APP_PASSWORD = "app-password-hier"

python create_portal_pages.py --apply
```

### `create_compat_pages.py`
Legt zusätzliche **Test-Seiten** für die Kompatibilitätsprüfung an (optional, für Entwicklung).

---

## Manual Setup

Wenn Sie die Seiten von Hand erstellen möchten:

1. **Seite anlegen:**
   - Im WordPress Admin → Seiten → Neue Seite erstellen
   - **Titel:** (wie oben in der Tabelle)
   - **Slug:** (wie oben in der Tabelle)
   - **Veröffentlichungsstatus:** Veröffentlicht

2. **Inhalt einfügen:**
   - Im Block-Editor: Block „Custom HTML" hinzufügen (oder Shortcode direkt einfügen)
   - Den entsprechenden Shortcode aus der Tabelle oben einfügen
   - Speichern

3. **Kommentare/Pings deaktivieren** (optional):
   - Im Block-Editor-Einstellungen unter „Diskussion" prüfen

---

## Shortcode-Verhalten

### `[themisdb_support_login]`
- **Ohne Login:** Zeigt Anmeldeformular
- **Mit Login:** Zeigt Meldung "Sie sind angemeldet als [Name]" + Logout-Link
- **Automatische Authentifizierung:** Validiert Lizenz-Datei oder Benutzer-Email

### `[themisdb_support_portal]`
- **Ohne Login:** Zeigt Meldung "Bitte melden Sie sich an"
- **Mit Login:** Vollständiges Ticket-Portal
  - Meine Tickets (offene, gelöste, geschlossene)
  - Neues Ticket erstellen
  - Admin-Dashboards (für `manage_options`-Nutzer)

### `[themisdb_cockpit]`
- **Dashboard mit:**
  - Lizenzstatus (Edition, Tier, Ablauf)
  - Offene Tickets + SLA-Status
  - Bestellungen + Build-Status
  - Offene Lifecycle-Requests (Change / Termination)
  - Health-Indikatoren (OK / Warnung / Kritisch)

### `[themisdb_lifecycle_portal]`
- **Funktionen:**
  - Vertragsänderungsantrag stellen (mit Impact-Vorschau)
  - Kündigungsantrag einreichen (mit Enddatum-Vorschlag)
  - Status der eigenen Anträge anzeigen
  - E-Mail-Benachrichtigungen bei Änderung des Status

---

## Events & Mail-Integration

Die Shortcodes lösen folgende **WordPress-Hooks** aus, die von anderen Plugins abgehört werden können:

### Support Portal Events
```php
do_action('themisdb_support_portal_ticket_created', $ticket_id, $user_id, $license_id);
```

### Lifecycle Events (Contract Change & Termination)
```php
do_action('contract.change.requested', $request_id, $license_id);
do_action('contract.change.approved', $request_id, $license_id);
do_action('contract.change.rejected', $request_id, $license_id);
do_action('contract.change.executed', $request_id, $license_id);

do_action('contract.termination.requested', $request_id, $license_id);
do_action('contract.termination.confirmed', $request_id, $license_id);
do_action('contract.termination.rejected', $request_id, $license_id);
do_action('contract.termination.executed', $request_id, $license_id);
```

**Automatische Mails:** Der `ThemisDB_Mail_Orchestrator` (§8.4 ARCHITECTUR.md) abonniert diese Events und versendet Kundenmails automatisch.

---

## Admin-Dashboard-Seiten

Zusätzlich zu den Kunden-Seiten bietet das Plugin folgende **Admin-Seiten** im WordPress-Dashboard:

| Seite | URL | Zweck |
|-------|-----|-------|
| Tickets | `/wp-admin/admin.php?page=themisdb-support-portal` | Ticket-Management |
| Incidents | `/wp-admin/admin.php?page=themisdb-support-incidents` | Incident-Log-Sicht |
| Observability | `/wp-admin/admin.php?page=themisdb-support-observability` | Metriken-Dashboard |
| Änderungsanträge | `/wp-admin/admin.php?page=themisdb-support-change-requests` | Change-Request-Review |
| Kündigungsanträge | `/wp-admin/admin.php?page=themisdb-support-terminations` | Termination-Request-Review |
| Mail-Log | `/wp-admin/admin.php?page=themisdb-support-maillog` | E-Mail-Versand-Historie |
| Einstellungen | `/wp-admin/admin.php?page=themisdb-support-settings` | Plugin-Konfiguration |

---

## Troubleshooting

### Shortcode wird nicht angezeigt / als Text gezeigt
- **Ursache:** Plugin nicht aktiviert
- **Lösung:** Aktivieren Sie `themisdb-support-portal` unter Plugins

### "Sie müssen angemeldet sein"
- **Ursache:** Kunden ist nicht angemeldet
- **Lösung:** Login-Shortcode verwenden oder Benutzer im WP-Admin erstellen

### Keine Mails ankommen
- **Ursache:** Mail-Orchestrator nicht aktiv, oder E-Mail-Konfiguration falsch
- **Lösung:** 
  1. Plugin `themisdb-order-request` muss aktiv sein
  2. WP-Mail-Plugin (z. B. `WP Mail SMTP`) konfigurieren
  3. Admin-Mail-Log prüfen: `/wp-admin/admin.php?page=themisdb-support-maillog`

### Lifecycle-Requests erscheinen nicht
- **Ursache:** `themisdb-order-request`-Plugin nicht aktiv
- **Lösung:** `themisdb-order-request` aktivieren und DB-Tabellen initialisieren (`wp db query < schema.sql`)

---

## Weitere Ressourcen

- **ARCHITECTUR.md:** Gesamt-System-Architektur (§8 für Support-Portal)
- **class-shortcodes.php:** Implementierung der Shortcodes
- **class-admin.php:** Admin-Dashboard-Seiten
- **class-mail-orchestrator.php:** E-Mail-Integration (§8.4)
