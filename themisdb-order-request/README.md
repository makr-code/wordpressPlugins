# ThemisDB Order Request & Contract Management Plugin

Ein umfassendes WordPress-Plugin für automatisierte Bestellanfragen und Vertragsverwaltung für ThemisDB mit vollständiger rechtlicher Compliance, PDF-Generierung und E-Mail-Integration.

## 🎯 Überblick

Dieses Plugin bietet einen dialog-basierten Bestellprozess für ThemisDB-Produkte, Module und Schulungen mit automatischer Vertragserstellung, PDF-Generierung und E-Mail-Versand. Es erfüllt alle Aspekte des Vertragsrechts (CRUD) und integriert sich nahtlos mit dem epServer für Stammdatenverwaltung.

## ✨ Hauptfunktionen

### Dialog-basierter Bestellprozess
- **Schritt 1**: Produktauswahl (Community, Enterprise, Hyperscaler)
- **Schritt 2**: Modulauswahl (Vector Search, LLM Integration, Graph DB, etc.)
- **Schritt 3**: Schulungen (Online, On-Site, verschiedene Themen)
- **Schritt 4**: Kundendaten (Name, E-Mail, Firma)
- **Schritt 5**: Zusammenfassung und Bestätigung

### Vertragsrechtliche Funktionen (CRUD)
- ✅ **Create**: Automatische Vertragserstellung aus Bestellungen
- ✅ **Read**: Vollständige Vertragsansicht mit allen Details
- ✅ **Update**: Versionierung mit Revisionshistorie
- ✅ **Delete**: Sichere Löschung mit Backup

### Zahlungsverifizierung
- 💰 **Zahlungsüberwachung**: Automatische Erfassung aller Zahlungen
- ✅ **Verifizierung**: Manuelle und automatische Zahlungsbestätigung
- 📊 **Status-Tracking**: Pending, Verified, Failed
- 💳 **Transaktions-IDs**: Vollständige Nachverfolgbarkeit
- 📈 **Statistiken**: Zahlungsübersicht und Auswertungen
- 🔄 **epServer-Sync**: Automatische Synchronisation mit epServer

### Lizenzverwaltung
- 🔑 **Automatische Lizenzgenerierung**: Nach jeder Bestellung
- 📄 **Lizenzdatei**: Sichere JSON-Datei mit digitaler Signatur
- ⏱️ **Status-Management**: Pending, Active, Suspended, Expired
- 🎯 **Limitierungen**: Node-, Core- und Storage-Limits
- 🔐 **Validierung**: Echtheitsprüfung und Ablaufdatum-Check
- 📱 **epServer-Integration**: Echtzeit-Lizenzvalidierung

### Anmeldesystem mit Lizenzdatei
- 🔓 **Dual-Login**: Standard (Username/Password) + Lizenzdatei
- 📤 **Lizenz-Upload**: Sichere Lizenzdatei-Authentifizierung (.json)
- ✍️ **Signatur-Prüfung**: HMAC-SHA256 Verifikation
- 👤 **Auto-User-Creation**: Automatische Benutzererstellung aus Lizenz
- 🔒 **Session-Management**: Sichere Sitzungsverwaltung
- 📋 **Audit-Log**: Vollständige Authentifizierungs-Historie

### PDF-Generierung
- Automatische PDF-Erstellung für Verträge
- Professionelle Vorlagen mit Firmenbranding
- Speicherung in Datenbank oder Dateisystem
- Rechtlich konforme Dokumentation

### E-Mail-Funktionen
- Bestellbestätigung mit PDF-Anhang
- Vertragsversand per E-Mail
- E-Mail-Logging für Nachverfolgbarkeit
- Anpassbare E-Mail-Vorlagen

### epServer Integration
- Synchronisation von Produktdaten
- Automatische Kundenregistrierung
- Abonnement-Verwaltung
- Lizenzschlüssel-Validierung
- Zahlungsstatus-Abfragen
- Lizenzinformations-Abfragen

## Support-Portal-Integration

Der Login-Shortcode initialisiert seine Inline-Bedienung erst nach dem Parsen
der Seite. Damit funktioniert die Einbettung im Support-Hub auch dann, wenn
WordPress jQuery erst im Footer ausgibt. Die schreibgeschuetzten gemeinsamen
Integrationspruefungen sind unter
[`themisdb-support-portal/tests`](../themisdb-support-portal/tests/README_E2E_TESTS.md)
dokumentiert; sie pruefen auch die Admin-Assetzuordnung aller registrierten
Order-Unterseiten und das Rendern der Bestell-, Vertrags- und Lizenzlisten.

### Eigener Shop: Zahlung, Positionen und Supportrechte

Der eigene Shop und die optionale WooCommerce-Bruecke sind getrennte
Integrationspfade. Die folgenden Korrekturen und isolierten Tests betreffen
den eigenen Checkout, die Admin-Zahlungsverarbeitung und den epServer-Statusabgleich.

- Die Auswahl von Stripe oder PayPal ist kein Zahlungsnachweis. Im eigenen
  Checkout gibt es noch keine Anbieteranbindung oder automatische Abbuchung.
  Auch diese Zahlungen bleiben `pending`; der Checkout weist darauf hin.
  Eine Bestaetigung muss separat durch einen berechtigten Admin, den
  Bankabgleich oder einen tatsaechlichen epServer-Zahlungsstatus erfolgen.
- Die Positionssynchronisierung verwendet dieselben Katalogfelder wie die
  Preisberechnung: `price`, `module_code` und `training_code`. Vorher konnten
  Produktpositionen mit Nullpreis entstehen und Module/Schulungen fehlen.
  Vorhandene Bestellungen werden nicht automatisch migriert oder nachberechnet;
  bereits falsch gespeicherte Positionen muessen geprueft werden.
- Die Verifizierung prueft Ausgangsstatus und Speicherung. Wiederholte
  Bestaetigungen einer bereits verifizierten Zahlung schreiben nichts neu
  und loesen keine erneute Lizenzaktivierung oder Lizenzmail aus.
  Fehlgeschlagene Zahlungen lassen sich nicht direkt verifizieren;
  verifizierte Zahlungen nicht nachtraeglich als fehlgeschlagen markieren.
- Scheitert der Bestellstatusabgleich, wird keine Lizenz aktiviert.
  Damit werden ueber diesen Pfad auch keine Supportrechte freigeschaltet.
  Verbraucher ohne Widerrufsverzicht bleiben von der sofortigen
  digitalen Lizenzaktivierung ausgeschlossen.
- Admin-Bearbeitung und epServer-Bestaetigung nutzen denselben
  Verifizierungspfad. Einzel- und Neuanlageaktionen im Zahlungsadmin zeigen
  Verarbeitungsfehler an statt einen erfolgreichen Abschluss vorzutäuschen.

```powershell
& C:\xampp\php\php.exe .\themisdb-order-request\tests\payment.test.php
& C:\xampp\php\php.exe .\themisdb-order-request\tests\order-items.test.php
& C:\xampp\php\php.exe .\themisdb-order-request\tests\checkout.test.php
```

Diese Tests fuehren die echten Manager-/Checkoutmethoden mit synthetischen
In-Memory-Doubles aus. Sie laden WordPress nicht, versenden keine E-Mails und
kontaktieren weder Zahlungsanbieter noch epServer. Geprueft werden auch
Lizenz- und Merchandise-Bestellungen fuer alle drei Zahlungsmethoden.

Zahlungsverifizierung, Bestellstatus, Lizenzaktivierung und Mailversand sind
keine gemeinsame Transaktion. Ist die Zahlung gespeichert, aber die weitere
Verarbeitung fehlgeschlagen, bleibt sie `verified`; Logs und Adminfehler
verlangen einen manuellen Abgleich. Ein erneuter Callback repariert diese
Folgen nicht automatisch. Echte parallele Datenbankprozesse, Supportrechte
und externe Provider wurden mit diesen Doubles nicht Ende-zu-Ende getestet.
Die historische WooCommerce-Bruecke ist kein aktiver Integrationspfad
(siehe unten); der eigene Shop benoetigt WooCommerce nicht.

### WordPress-Einbindung: eigener Shop und Supportportal

WooCommerce ist ausschliesslich Ideengeber. Es ist weder Voraussetzung noch
vorgesehener Integrationspartner. Das Order-Plugin laedt und initialisiert die
historische WooCommerce-Bruecke nicht mehr automatisch, auch dann nicht,
wenn WooCommerce installiert ist. Die vorhandene Legacy-Klasse bleibt im
Quellbaum, ist aber kein aktiver Shop-Pfad.

Die Plugins liefern Geschaeftslogik, HTML, Bedienung und funktionierende
Basisstile. Das Theme liefert die visuelle Anpassung. Das Order-Stylesheet
wird deshalb nicht mehr anhand eines erkannten Themes unterdrueckt; der
explizite Filter `themisdb_order_request_enqueue_frontend_style` bleibt fuer
bewusste Integrationen erhalten. Generische Flow-Klassen wie `.form-group`
und `.loading` sind auf die Order-/Vertragskomponenten begrenzt.

Der oeffentliche Einstieg ist `/products/`, ausschliesslich fuer
ThemisDB-Lizenzprodukte und deren Module. Merchandise und separat angebotene
Schulungen gehoeren nicht zu diesem Produktkonfigurator.

| Seite | Shortcode | Funktion |
|-------|-----------|----------|
| `/products/` | `[themisdb_product_detail]` | Lizenzkonfigurator und Produktuebergabe an das Overlay |
| `/support/` | `[themisdb_support_hub]` | Kundenportal inkl. Order-/Vertragsbereichen |

`[themisdb_cart_button]` liefert ein zugreifbar beschriftetes Warenkorbsymbol.
Pulse bindet es im Header ein; andere Themes koennen denselben Shortcode
verwenden. Der Produktbutton und das Header-Symbol oeffnen denselben
plugin-eigenen nativen Dialog. Auch der Support-Hub verwendet ihn, wenn
das Checkoutmodul verfuegbar ist, statt einen zweiten Auftragsdialog zu rendern.

Warenkorb und Kasse werden in einem gleichurspruenglichen Frame innerhalb
des Dialogs dargestellt. Dadurch bleibt die bestehende Session-/Bestelllogik
nutzbar, ohne doppelte Formular-IDs oder eine zweite Checkoutimplementierung.
Der Frame verwendet Plugin-Basisstile und die aktive Theme-Palette.
Escape, Schliessen, Backdrop und Fokus-Rueckgabe werden unterstuetzt.
Der Frame-Link ist nonce-geschuetzt und nicht cachebar. Erst die ausdrueckliche
Produktauswahl erzeugt bzw. aktualisiert einen Entwurf; das blosse Oeffnen
eines leeren Warenkorbs legt keine Bestellung an.

Es werden keine `/warenkorb/`- oder `/bestellung/`-Seiten benoetigt.
Die bisherige `/shop/`-Seite bleibt unangetastet, ist aber nicht der neue
Haupteinstieg. Historische Einzelshortcodes bleiben fuer bestehende
Einbettungen erhalten. Das Overlay enthaelt Kundendaten, rechtliche
Zustimmungen, Bestellzusammenfassung und Zahlungsmethodenwahl. Die Auswahl
einer Zahlungsmethode ist weiterhin kein Zahlungsnachweis; Stripe/PayPal
haben keine aktive Anbieteranbindung oder automatische Abbuchung.

Der Hub enthaelt bereits den Supportbereich; nicht zusaetzlich ein zweites
Loginformular oder Portal daneben einbetten. Pulse hat spezielle Shop- und
Supporttemplates; im Supporttemplate wird das Portal bei Bedarf automatisch
eingebettet. Ein vorhandenes Ticketportal muss nicht sofort durch den Hub
ersetzt werden.

Lokal wurde ausschliesslich die genehmigte `/products/`-Seite mit dem
Produktshortcode veroeffentlicht. Keine zusaetzlichen Warenkorb- oder
Bestellseiten wurden angelegt. Bei anderen Installationen wird die Seite
bewusst im WordPress-Editor angelegt; sie ist keine Aktivierungs-Nebenwirkung.

Themes koennen die folgenden Custom Properties auf den jeweiligen
Plugin-Wrappers definieren; ohne diese Werte gelten die Plugin-Defaults:

```css
.themisdb-shop-page,
.themisdb-cart-button,
.themisdb-checkout-dialog,
.themisdb-checkout-frame,
.themisdb-order-flow,
.themisdb-product-detail,
.themisdb-shopping-cart,
.themisdb-support-hub-wrap,
.themisdb-support-portal-wrap,
.themisdb-support-login-wrap {
    --themisdb-ui-primary: #245c81;
    --themisdb-ui-primary-hover: #173d56;
    --themisdb-ui-on-primary: #fff;
    --themisdb-ui-heading: #173d56;
    --themisdb-ui-accent: #327cac;
    --themisdb-ui-text: #172635;
    --themisdb-ui-muted: #526579;
    --themisdb-ui-border: #b8c9da;
    --themisdb-ui-surface: #fff;
    --themisdb-ui-surface-alt: #f4f7fb;
}
```

Diese Variablen steuern die gemeinsame Palette, nicht jede einzelne
Statusfarbe oder dekorative Schattierung. Pulse belegt sie komponentenlokal
mit seinen `--tv3-*`-Tokens; Farbvarianten wechseln dadurch gemeinsam.
Plugins enthalten keine Abhaengigkeit von Pulse-Variablen.

```powershell
node --test .\themisdb-order-request\tests\presentation.test.cjs
node --test .\themisdb-order-request\tests\overlay.test.cjs
& C:\xampp\php\php.exe .\themisdb-order-request\tests\overlay.test.php
```

Die Browserpruefung testet Plugin-Defaults ohne Theme, die Begrenzung generischer
Styles, zwei Pulse-Farbvarianten sowie die vorhandenen Shop-/Supportseiten
bei 1440, 390 und 320 Pixeln. Sie legt keine Seiten an und bestellt nichts.
Die Overlay-Browserpruefung oeffnet den echten leeren Warenkorb; explizite
Produktauswahlen werden im Test abgefangen, um keine echten Entwuerfe
anzulegen. Isolierte PHP-Doubles pruefen Frame-Routing und Lizenzgrenzen.

### Historische WooCommerce-Bruecke: isolierte Regression

Der folgende Abschnitt beschreibt ausschliesslich die nicht automatisch
geladene Legacy-Klasse, nicht eine vorgesehene Zusammenarbeit der Shopsysteme.

```powershell
& C:\xampp\php\php.exe .\themisdb-order-request\tests\woocommerce.test.php
```

Diese Suite laedt die echte Brueckenklasse mit WooCommerce-, Order-, Lizenz-
und Mail-Doubles, nicht WordPress oder WooCommerce. Lokal war WooCommerce
bei dieser Pruefung nicht aktiv. Es werden keine echten Bestellungen erzeugt,
keine Provider kontaktiert und keine E-Mails versendet.

- Danke-Seite, direkter Generierungs-Hook, Payment-Complete und
  Statuswechsel verwenden dieselbe Aktivierungspruefung: Woo muss die
  Bestellung als bezahlt melden, das Gateway-Mapping muss Freigabe erlauben,
  und die verknuepfte ThemisDB-Bestellung muss bestaetigt und rechtlich
  vollstaendig sein. Die zentrale Bestellstatusmethode prueft Compliance
  und Speicherung auch bei direkten Hook-Aufrufen.
- Verbraucher ohne Widerrufsverzicht und Merchandise-Bestellungen erhalten
  ueber diese Pfade keine aktive digitale Lizenz. Ein Scheck im Status
  `processing` bleibt entsprechend dem bestehenden Mapping gesperrt.
- Fehlgeschlagene Bestellsynchronisierung stoppt nachfolgende Lizenzaktionen.
  Bezahlte Callbacks oeffnen keine geschlossenen ThemisDB-Bestellungen.
  Gekuendigte Lizenzen bleiben gekuendigt; bereits aktive Lizenzen werden
  nicht erneut aktiviert oder erneut mit einer Generierungsmail versendet.
- Vor Aktivierung, Sperrung und Kuendigung wird geprueft, ob die verknuepfte
  Lizenz zur zugeordneten Bestellung gehoert. Fehler der Lizenzaktionen
  werden technisch protokolliert.
- Vollstaendige Erstattungen kuendigen die verknuepfte Lizenz, teilweise
  Erstattungen behalten die bestehende Lizenz. Ausstehende/fehlgeschlagene
  Woo-Status suspendieren aktive Lizenzen wie bisher, sofern der
  Bestellstatusabgleich erfolgreich war.
- Bestell- und Lizenzverknuepfungen werden ueber `WC_Order`-Metadaten und
  `save()` gespeichert, nicht direkt als Post-Meta. Produktmetadaten bleiben
  davon unberuehrt. Das ist keine Zertifizierung der gesamten HPOS-Kompatibilitaet.

Die Suite prueft auch neue Bestellimporte, die Wiederverwendung einer
Vertragslizenz, neue Lizenzanlage, CRUD-Speicherfehler und wiederholte Hooks.
Sie ersetzt keine echten Gateway-, WooCommerce-/HPOS- oder Parallelitaetstests.
Die Produktkatalogsynchronisierung und gemischte Warenkoerbe sind noch nicht
vollstaendig geprueft. Woo-Zahlungen bleiben in diesem Integrationspfad die
Zahlungsquelle; eine gemeinsame Zahlungsbuchung mit dem nativen Checkout
wird nicht eingefuehrt.

Import, Vertrag, Lizenzaktivierung, Woo-Verknuepfung und Mail sind keine
gemeinsame Transaktion. Teilfehler verlangen manuellen Abgleich; beispielsweise
kann eine Lizenz schon aktiv sein, wenn das Speichern ihrer Woo-Verknuepfung
scheitert. Fehler werden protokolliert, aber keine automatische
Rollback-/Retry-Verarbeitung oder historische Datenkorrektur eingefuehrt.
Auch ein fehlgeschlagener Import kann bereits eine ThemisDB-Bestellung
hinterlassen; vor erneutem Import muss eine doppelte Anlage ausgeschlossen werden.

### Bankimport: Zuordnung ist nicht gleich Zahlungsbestaetigung

```powershell
& C:\xampp\php\php.exe .\themisdb-order-request\tests\bank-import.test.php
```

Diese isolierte Suite verwendet ausschliesslich synthetische Buchungen und
In-Memory-Doubles; sie schreibt weder in WordPress noch verarbeitet sie echte
Bankdaten. Auch die beiden Admin-Fehlerpfade werden ohne WordPress ausgefuehrt.

- Vor der Verifizierung muessen Importzeile und Bankreferenz gespeichert sein.
  Ein fehlgeschlagenes INSERT oder UPDATE darf keine Zahlung freigeben.
- Ein automatischer Kandidat wird zunaechst `unmatched` gespeichert. Erst nach
  erfolgreicher Zahlungs-/Bestell-/Lizenzverarbeitung wird er `matched`;
  manuelle Zuordnungen erhalten entsprechend erst danach `manual`.
- Datenbank- und Verarbeitungsfehler ergeben `WP_Error` statt einer
  erfolgreichen Import-ID oder `true`. Teilimporte enthalten ihre Import-ID
  im Fehlerkontext. Die Adminaktionen zeigen einen Fehler mit manuellem
  Pruefauftrag statt einer Erfolgsmeldung.
- Vorschau und Verarbeitung pruefen positive Betraege, dieselbe Waehrung und
  offene Zahlungen (`pending` oder `overdue`). Die bisherige Betragtoleranz
  bleibt ein Cent, jetzt mit Vergleich gerundeter Centwerte. Auch manuelle
  Zuordnungen duerfen keine Teilzahlung, abweichende Waehrung oder bereits
  verifizierte/fehlgeschlagene Zahlung freigeben.
- Aktuelle Bestellreferenzen `TDB-...` werden neben alten `ORD-...` erkannt.
  Der Bestellreferenz-Fallback findet auch ueberfaellige Zahlungen.
- Importzaehler spiegeln gespeicherte Zeilen und abgeschlossene Zuordnungen
  wider. Nach manueller Zuordnung werden sie neu berechnet; `manual` zaehlt
  auch in der Adminansicht als zugeordnet.

Es gibt weiterhin keine gemeinsame Transaktion fuer Import, Zahlung,
Bestellung und Lizenz. Bei einem Teilfehler bleiben bereits gespeicherte
Datensaetze und erfolgreich verarbeitete Zeilen erhalten; der Lauf kann
weitere Zeilen verarbeiten und meldet am Ende den Teilfehler. Bereits
verifizierte Zahlungen werden nicht automatisch erneut verarbeitet, auch
nicht zum Reparieren eines zuvor fehlgeschlagenen Folgeprozesses.
Keine automatische Wiederholung, keine Exactly-once-Garantie fuer parallele
Importe und keine historische Datenkorrektur werden eingefuehrt. Scheitert
das Speichern der Zusammenfassung, koennen deren Zaehler veraltet sein;
die einzelnen Buchungs-/Zahlungsdatensaetze muessen dann geprueft werden.
Die CSV-Parser aller Bankformate und reale Datenbankparallelitaet sind mit
dieser isolierten Suite nicht vollstaendig getestet.

### Isolierte Lifecycle-Regression

```powershell
& C:\xampp\php\php.exe .\themisdb-order-request\tests\lifecycle.test.php
```

Diese Tests laden ausschliesslich die Lifecycle-Klasse mit kleinen
WordPress-Funktions- und Datenbank-Doubles, nicht die WordPress-Installation.
Sie erzeugen keine echten Daten, E-Mails, Dateien oder externen Requests.
Geprueft werden Genehmigung und Ablehnung von Aenderungs- und
Kuendigungsantraegen in der direkten, Operations- und Finance-Pruefung:

- Datenbankfehler ergeben `review_update_failed`, ohne Erfolgsereignis,
  Audit-Erfolgseintrag oder Kundenbenachrichtigung.
- Ein inzwischen geaenderter Status ergibt `review_conflict`. Das UPDATE
  prueft den erwarteten Ausgangsstatus direkt in seiner WHERE-Bedingung.
- Audit-Eintraege folgen erst nach gespeicherter Pruefung.
- Kuendigungen in `pending_finance` gelten weiterhin als offene Antraege
  und blockieren einen zweiten Kuendigungsantrag.
- Direkte und Finance-Freigaben pruefen auch das Ergebnis der anschliessenden
  Lizenzanwendung. Fehlende Lizenzen, defektes JSON, verschachtelte Werte in
  Lizenzfeldern und fehlgeschlagene Lizenz-Updates ergeben einen konkreten
  `WP_Error` und einen `execute_failed`-Auditeintrag. Genehmigungs- und
  Ausfuehrungsbenachrichtigungen werden dann nicht ausgeloest.
- Die bereits gespeicherte Freigabe bleibt bei Anwendungsfehlern `confirmed`;
  der Fehlertext weist auf die notwendige manuelle Pruefung hin. Es gibt
  keinen automatischen Retry und kein Zuruecksetzen der Freigabe. Bereits
  passende Lizenzwerte (UPDATE liefert 0) bleiben ein erfolgreicher Fall.
- Zusaetzliche Impact-Metadaten werden nicht als Lizenzfelder geschrieben.
  Gueltige leere Aenderungspayloads behalten das bisherige No-op-Verhalten.

Auch die Scheduler-Ausfuehrung wird mit Doubles geprueft:

- Eine Kuendigung zaehlt erst nach gespeichertem Ausfuehrungsstatus als
  `executed`. Fehlgeschlagene oder inzwischen ueberholte Status-Updates
  erhoehen `failed` und erzeugen `execute_failed`, ohne Abschlussereignis
  oder Abschlussbenachrichtigung.
- Ist die Lizenz schon gekuendigt, kann ein noch bestaetigter Antrag im
  naechsten Lauf abgeschlossen werden, ohne erneut `cancel_license` aufzurufen.
  Bereits abgeschlossene Antraege werden nicht erneut verarbeitet.
- Fehler beim Laden der faelligen Antraege ergeben `termination_query_failed`
  und eine technische Logmeldung statt einer leeren Erfolgsauswertung.
  Der manuelle Support-Admin-Trigger zeigt diesen Fehler an.

Die Tests pruefen Lizenzanwendung und Scheduler mit Doubles, nicht mit
echten Lizenzdatensaetzen. Sie ersetzen keine Datenbank-/Parallelitaets-
E2E-Pruefung und garantieren keine Exactly-once-Verarbeitung bei gleichzeitig
laufenden Schedulern. Lizenzkuendigung und Antragstatus sind keine gemeinsame
Transaktion. PDF-Erzeugung, Mailzustellung und die Deaktivierung echter
Support-Berechtigungen sind nicht Teil dieser isolierten Suite.

### Isolierte Abschlussmail-Regression

```powershell
& C:\xampp\php\php.exe .\themisdb-order-request\tests\termination-mail.test.php
```

Diese separate Suite verwendet synthetische Kundendaten, eine Mail-Double und
ein eigenes temporaeres Testverzeichnis. Ohne TCPDF wird der bestehende
druckbare HTML-Anhang geprueft; eine TCPDF-Double simuliert eine fehlende
PDF-Ausgabedatei. Der Test sendet keine E-Mails und entfernt seine Dateien.

Die Abschlussmail bereinigt Dokument und HTML-Content-Type-Filter auch bei
Transport-Ausnahmen. `wp_mail() === false` sowie Transport-Ausnahmen erhalten
einen `notification_failed`-Auditeintrag; Ausnahmen werden nicht verschluckt.
Fehlende Dokumente werden als `document_failed` protokolliert, die Mail wird
dann weiterhin ohne Anhang versucht. Ein erfolgreicher Mail-Aufruf bestaetigt
nur die Uebergabe an den Transport, nicht die Zustellung beim Empfaenger.
Versandfehler setzen die ausgefuehrte Kuendigung nicht zurueck und erzeugen
keinen automatischen Wiederholungsversand.

Dokumente erhalten pro Aufruf eine UUID und werden exklusiv im WordPress-
Tempverzeichnis angelegt, nicht im oeffentlichen Uploadverzeichnis. PDF-Inhalt
wird zunaechst im Speicher erzeugt; ein Renderer-Abbruch hinterlaesst daher
keine halbe PDF-Datei. Der Writer prueft vollstaendiges Schreiben und Flushen.
Ueberlappende Aufrufe fuer denselben Antrag besitzen unabhaengige Dateien.
Leere Renderer-Ausgaben und Dateierzeugungsfehler werden explizit gemeldet;
ohne TCPDF oder nach einer Renderer-Ausnahme bleibt der HTML-Fallback erhalten.

Die Suite prueft getrennte Dateinamen bei ueberlappender Lebensdauer,
PDF-Bytes mit einer TCPDF-Double und ein nicht verfuegbares Tempverzeichnis.
Echte TCPDF-Rasterisierung, SMTP-Zustellung und parallele Prozesse sind damit
nicht verifiziert. Ein Prozessabbruch vor dem Cleanup kann weiterhin eine
Tempdatei hinterlassen; ein automatischer Bereinigungsjob wird nicht angelegt.

## 📋 Systemanforderungen

- **WordPress**: 5.0 oder höher
- **PHP**: 7.4 oder höher
- **MySQL**: 5.6 oder höher (oder MariaDB)
- **Speicherplatz**: Min. 50 MB
- **Optional**: wkhtmltopdf für PDF-Generierung (empfohlen)

## 🚀 Installation

### Methode 1: WordPress Admin
1. Laden Sie das Plugin als ZIP herunter
2. WordPress Admin → Plugins → Installieren → Plugin hochladen
3. Wählen Sie die ZIP-Datei aus
4. Klicken Sie auf "Jetzt installieren"
5. Aktivieren Sie das Plugin

### Methode 2: Manuell via FTP
1. Entpacken Sie die ZIP-Datei
2. Laden Sie den Ordner `themisdb-order-request` nach `wp-content/plugins/` hoch
3. WordPress Admin → Plugins → Aktivieren

## ⚙️ Konfiguration

### epServer Verbindung einrichten

1. WordPress Admin → **ThemisDB Orders → Einstellungen**
2. Konfigurieren Sie:
   - **epServer URL**: `https://service.themisdb.org:6734` (Standard)
   - **API Schlüssel**: Optional für authentifizierte Anfragen
3. Klicken Sie auf **"Verbindung testen"**
4. Klicken Sie auf **"Daten synchronisieren"** um Produkte zu aktualisieren

### E-Mail Einstellungen

1. **Absender E-Mail**: Die E-Mail-Adresse für ausgehende Nachrichten
2. **Absender Name**: Der Name, der in E-Mails angezeigt wird

### PDF Einstellungen

**Speicherung**:
- **Datenbank**: PDFs werden in der Datenbank gespeichert (Standard)
- **Dateisystem**: PDFs werden als Dateien unter `wp-content/uploads/themisdb-contracts/` gespeichert

**PDF-Generierung**:
- Installieren Sie `wkhtmltopdf` für optimale PDF-Qualität:
  ```bash
  # Ubuntu/Debian
  sudo apt-get install wkhtmltopdf
  
  # macOS
  brew install wkhtmltopdf
  ```

## 📖 Verwendung

### Frontend Integration

#### Bestellformular einbinden

Verwenden Sie den Shortcode auf einer Seite oder in einem Beitrag:

```
[themisdb_order_flow]
```

#### Kundenbereichseiten

**Meine Bestellungen**:
```
[themisdb_my_orders]
```

**Meine Verträge**:
```
[themisdb_my_contracts]
```

**Anmeldeformular** (Standard + Lizenzdatei):
```
[themisdb_login]
```

**Lizenz-Upload**:
```
[themisdb_license_upload]
```

### Admin-Bereich

#### Bestellungen verwalten

1. **ThemisDB Orders → Bestellungen**
2. Sehen Sie alle Bestellungen mit Status
3. Klicken Sie auf "Ansehen" für Details
4. Status ändern: Draft → Pending → Processing → Completed

#### Zahlungen verwalten

1. **ThemisDB Orders → Zahlungen**
2. Übersicht aller Zahlungen mit Status (Pending, Verified, Failed)
3. Klicken Sie auf "Ansehen" für Details
4. **"Verifizieren"** Button für manuelle Zahlungsbestätigung
5. Statistik-Dashboard:
   - Gesamt Zahlungen und Betrag
   - Verifizierte Zahlungen und Betrag
   - Ausstehende und fehlgeschlagene Zahlungen
6. Automatische Lizenzaktivierung nach Zahlungsverifizierung

#### Lizenzen verwalten

1. **ThemisDB Orders → Lizenzen**
2. Übersicht aller Lizenzen mit Status (Pending, Active, Suspended, Expired)
3. Klicken Sie auf "Ansehen" für Details:
   - Vollständiger Lizenzschlüssel
   - Edition und Typ
   - Node-, Core-, Storage-Limits
   - Aktivierungs- und Ablaufdatum
   - Zugehörige Bestellung und Vertrag
   - Lizenzdatei (JSON) zum Download
4. Statistik-Dashboard:
   - Gesamt Lizenzen
   - Aktive, ausstehende, suspendierte Lizenzen

#### Verträge verwalten

1. **ThemisDB Orders → Verträge**
2. Alle Verträge mit Gültigkeitsdaten
3. PDF herunterladen oder per E-Mail versenden
4. Revisionshistorie einsehen

#### Produkte und Module

1. **ThemisDB Orders → Produkte**
2. Übersicht aller verfügbaren Produkte, Module und Schulungen
3. Preise werden automatisch vom epServer synchronisiert

#### E-Mail Log

1. **ThemisDB Orders → E-Mail Log**
2. Alle gesendeten E-Mails mit Status
3. Fehlersuche bei E-Mail-Problemen

### Anmeldesystem nutzen

#### Als Kunde - Standard-Anmeldung

1. Gehen Sie zur Login-Seite: `[themisdb_login]`
2. Wählen Sie Tab **"Standard-Anmeldung"**
3. Geben Sie E-Mail/Benutzername und Passwort ein
4. Optional: "Angemeldet bleiben" aktivieren
5. Klicken Sie auf "Anmelden"

#### Als Kunde - Lizenz-Anmeldung

1. Gehen Sie zur Login-Seite: `[themisdb_login]`
2. Wählen Sie Tab **"Lizenz-Anmeldung"**
3. Laden Sie Ihre Lizenzdatei (.json) hoch
4. System verifiziert automatisch die Lizenz
5. Bei erfolgreicher Verifizierung werden Sie eingeloggt
6. Falls noch kein Account existiert, wird dieser automatisch erstellt

**Lizenzdatei-Format**:
```json
{
  "version": "1.0",
  "license_key": "ENT-XXXX-XXXX-XXXX-XXXX-XXXX-XXXX-XXXX",
  "product_edition": "enterprise",
  "customer_name": "Max Mustermann",
  "customer_email": "max@example.com",
  "max_nodes": 100,
  "expiry_date": "2027-01-08",
  "signature": "..."
}
```

#### Ablauf: Von Bestellung bis Login

1. **Kunde bestellt** → Bestellung wird erstellt
2. **System erstellt automatisch**:
   - Zahlung (Status: Pending)
   - Vertrag mit PDF
   - Lizenz mit Lizenzdatei
3. **Admin verifiziert Zahlung** → Lizenz wird aktiviert
4. **Kunde erhält E-Mail** mit:
   - Bestellbestätigung
   - Vertrag als PDF
   - Download-Link für Lizenzdatei (optional)
5. **Kunde kann sich anmelden**:
   - Mit Lizenzdatei hochladen
   - Automatisch eingeloggt
   - Zugriff auf Bestellungen und Verträge

## 🗄️ Datenbankstruktur

Das Plugin erstellt folgende Tabellen:

- `wp_themisdb_orders` - Bestellungen
- `wp_themisdb_contracts` - Verträge
- `wp_themisdb_contract_revisions` - Vertragsrevisionen
- `wp_themisdb_payments` - Zahlungen mit Verifizierung
- `wp_themisdb_licenses` - Lizenzen mit Lizenzdateien
- `wp_themisdb_license_auth_log` - Authentifizierungs-Log
- `wp_themisdb_products` - Produkte
- `wp_themisdb_modules` - Module
- `wp_themisdb_training_modules` - Schulungen
- `wp_themisdb_email_log` - E-Mail Protokoll

## 🔐 Sicherheit und Compliance

### Rechtliche Compliance
- DSGVO-konform durch Einwilligungssystem
- Vollständige Revisionshistorie für Verträge
- Sichere Speicherung sensibler Daten
- E-Mail-Protokollierung für Nachweise

### Datenschutz
- Alle personenbezogenen Daten werden verschlüsselt gespeichert
- Benutzer können ihre Daten einsehen und löschen
- Transparente Datenverarbeitung

### Best Practices
- SQL-Injection-Schutz durch prepared statements
- XSS-Schutz durch sanitize/escape functions
- CSRF-Schutz durch nonces
- Sichere AJAX-Kommunikation

## 🎨 Anpassung

### CSS anpassen

Erstellen Sie eine custom CSS-Datei in Ihrem Theme:

```css
/* Überschreiben Sie Plugin-Styles */
.themisdb-order-flow {
    /* Ihre Anpassungen */
}
```

### E-Mail-Vorlagen anpassen

Filter verwenden, um E-Mail-Templates zu überschreiben:

```php
add_filter('themisdb_order_confirmation_template', function($html, $order) {
    // Ihre angepasste HTML-Template
    return $html;
}, 10, 2);
```

### PDF-Vorlagen anpassen

```php
add_filter('themisdb_contract_pdf_html', function($html, $contract, $order) {
    // Ihre angepasste PDF-Template
    return $html;
}, 10, 3);
```

## 🔄 Workflow-Beispiel

### Typischer Bestellablauf

1. **Kunde** besucht die Bestellseite
2. **Schritt 1**: Wählt ThemisDB Enterprise Edition
3. **Schritt 2**: Fügt Module hinzu (Vector Search, Sharding)
4. **Schritt 3**: Wählt Schulungen aus (Admin Training)
5. **Schritt 4**: Gibt Kontaktdaten ein
6. **Schritt 5**: Bestätigt die Bestellung

**Automatischer Prozess**:
- ✅ Bestellung wird in Datenbank gespeichert
- ✅ Bestellbestätigung per E-Mail mit PDF
- ✅ Vertrag wird automatisch erstellt
- ✅ Vertrags-PDF wird generiert und gespeichert
- ✅ Vertrag wird per E-Mail versendet
- ✅ Optional: Kunde wird im epServer registriert

### Vertragsänderung (Rechtlich konform)

1. Admin öffnet Vertrag
2. Ändert Vertragsdetails
3. System erstellt automatisch neue Revision
4. Änderungsgrund wird protokolliert
5. Neue Version wird gespeichert
6. Kunde wird über Änderung informiert

## 🐛 Fehlerbehebung

### E-Mails werden nicht versendet

1. Prüfen Sie die E-Mail-Einstellungen in WordPress
2. Testen Sie mit einem SMTP-Plugin (z.B. WP Mail SMTP)
3. Überprüfen Sie den E-Mail-Log: **ThemisDB Orders → E-Mail Log**

### PDFs werden nicht generiert

1. Installieren Sie wkhtmltopdf:
   ```bash
   which wkhtmltopdf
   ```
2. Stellen Sie sicher, dass PHP exec() aktiviert ist
3. Prüfen Sie Schreibrechte für Upload-Verzeichnis

### epServer-Verbindung fehlgeschlagen

1. Prüfen Sie die URL: `https://service.themisdb.org:6734`
2. Testen Sie die Verbindung: **Einstellungen → Verbindung testen**
3. Überprüfen Sie Firewall-Regeln
4. Kontaktieren Sie den epServer-Administrator

### Datenbank-Fehler

1. Deaktivieren und reaktivieren Sie das Plugin
2. Prüfen Sie MySQL-Logs
3. Stellen Sie sicher, dass ausreichend Speicherplatz vorhanden ist

## 📊 Performance

### Optimierung

- **Cache**: Plugin nutzt WordPress Transient API
- **Lazy Loading**: Bilder und Daten werden bei Bedarf geladen
- **Database Indexing**: Optimierte Indizes für schnelle Abfragen
- **AJAX**: Asynchrone Anfragen für bessere UX

### Empfohlene Einstellungen

- **PHP Memory Limit**: Min. 128 MB (256 MB empfohlen)
- **Max Execution Time**: Min. 60 Sekunden
- **Upload Max Filesize**: Min. 10 MB (für PDFs)

## 🔌 Hooks und Filter

### Actions

```php
// Nach Bestellung erstellt
do_action('themisdb_after_order_created', $order_id);

// Nach Vertrag erstellt
do_action('themisdb_after_contract_created', $contract_id);

// Vor E-Mail-Versand
do_action('themisdb_before_email_send', $to, $subject, $message);
```

### Filters

```php
// Order-Daten modifizieren
$order_data = apply_filters('themisdb_order_data', $order_data);

// Vertragsdaten modifizieren
$contract_data = apply_filters('themisdb_contract_data', $contract_data);

// E-Mail-Template ändern
$html = apply_filters('themisdb_email_template', $html, $type, $data);
```

## 📞 Support

- **GitHub Issues**: https://github.com/makr-code/wordpressPlugins/issues
- **Dokumentation**: https://github.com/makr-code/wordpressPlugins/tree/main/wordpress-plugin
- **E-Mail**: support@themisdb.com

## 📄 Lizenz

MIT License - Siehe [LICENSE](../../LICENSE) Datei

## 🙏 Credits

Entwickelt mit ❤️ für das ThemisDB-Projekt

- **ThemisDB Team**: https://github.com/makr-code/wordpressPlugins
- **Contributors**: Siehe GitHub Contributors

## 🗺️ Roadmap

### Geplante Features

- [ ] Multi-Währungs-Unterstützung
- [ ] Automatische Rechnungserstellung
- [ ] Zahlungsintegration (Stripe, PayPal)
- [ ] Elektronische Signaturen
- [ ] Multi-Sprachen-Support
- [ ] Erweiterte Reporting-Funktionen
- [ ] Workflow-Automatisierung
- [ ] Mobile App Integration

## 📚 Weitere Dokumentation

- [API-Dokumentation](API.md)
- [Entwickler-Handbuch](DEVELOPMENT.md)
- [Changelog](CHANGELOG.md)
- [Upgrade-Guide](UPGRADE.md)

---

**Version**: 1.0.0  
**Letzte Aktualisierung**: 2026-01-08  
**Autor**: ThemisDB Team
