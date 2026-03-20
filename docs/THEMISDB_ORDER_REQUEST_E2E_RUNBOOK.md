# ThemisDB Order Request E2E Runbook

## Ziel
Dieses Runbook definiert die End-to-End Abnahme fuer das Shop-License-Order-System.

## Scope
- Plugin: themisdb-order-request
- Workflow-Kette: Bestellung -> Vertrag -> Zahlung -> Lizenz -> Lifecycle
- Admin UX: Listen, Suche, Sortierung, Pagination, Bulk, AJAX Navigation

## Voraussetzungen
- WordPress Instanz mit aktivem Plugin
- Admin Benutzer mit manage_options
- Test-Mail Setup aktiv (SMTP oder lokaler Mail-Catcher)
- Optional: WP-CLI fuer halbautomatische Smoke-Checks

## Testdaten
Empfohlene Referenzdaten:
- 2 Kunden
- 3 Produkte (product/module/training)
- 2 Lagerartikel mit Kategorien
- 1 pending Zahlung
- 1 aktive Lizenz

## E2E Testmatrix
### E2E-01 Bestellung anlegen
- Schritte:
1. Neue Bestellung im Admin anlegen
2. Pflichtfelder speichern
- Erwartung:
1. Bestellung hat gueltige order_number
2. Status initial korrekt (z. B. pending)

### E2E-02 Bestellung zu Vertrag
- Schritte:
1. In Bestellliste Vertrag erstellen
2. Vertragsseite oeffnen
- Erwartung:
1. contract_id vorhanden
2. Relation zur Bestellung korrekt

### E2E-03 Zahlung anlegen und verifizieren
- Schritte:
1. Zahlung fuer bestehende Bestellung anlegen
2. Status pending -> verified
- Erwartung:
1. Zahlungsstatus korrekt gespeichert
2. Verifizierungszeitpunkt gesetzt

### E2E-04 Lizenz anlegen aus Order/Contract
- Schritte:
1. Neue Lizenz mit Order-ID und Contract-ID anlegen
2. Lizenzdetailseite pruefen
- Erwartung:
1. license_key erzeugt
2. Status und Laufzeitfelder plausibel

### E2E-05 Lizenz Lifecycle
- Schritte:
1. Lizenz suspendieren
2. Lizenz wieder aktivieren
3. Lizenz kuendigen
- Erwartung:
1. Statuswechsel in Reihenfolge nachvollziehbar
2. Audit/Notizen vorhanden

### E2E-06 Payment Lifecycle
- Schritte:
1. Pending Zahlung auf overdue setzen
2. Overdue auf failed setzen
- Erwartung:
1. Statuswechsel korrekt
2. UI Tabs/Counts konsistent

### E2E-07 Bulk Workflows
- Schritte:
1. Mehrere Rows in Orders/Contracts/Payments/Licenses markieren
2. Bulk Aktion ausfuehren
- Erwartung:
1. Sammelmeldung mit verarbeitet/fehler
2. Zielstatus fuer alle gueltigen Datensaetze gesetzt

### E2E-08 Katalog und Lager CRUD
- Schritte:
1. Kategorie anlegen/umbenennen
2. Kategorie-Loeschschutz pruefen (in_use)
3. Produkt/Lagerdatensatz bearbeiten
- Erwartung:
1. Migration bei Rename funktioniert
2. in_use verhindert Loeschung

### E2E-09 Listen SPA Verhalten
- Schritte:
1. In Produkten/Lager/Zahlungen/Lizenzen sortieren
2. Pagination nutzen
3. Browser Back/Forward testen
- Erwartung:
1. Kein Full Page Reload bei Listeninteraktionen
2. URL-State bleibt konsistent
3. Zustand bleibt pro Benutzer erhalten (Tab, Sortierung, per_page)

### E2E-10 Fehlerpfade
- Schritte:
1. Ungueltige IDs absenden
2. Nonce Fehler simulieren
- Erwartung:
1. Sichere Fehlerbehandlung
2. Keine stillen Datenkorruptionen

## Go/No-Go Kriterien
Go nur wenn:
1. Alle E2E-01 bis E2E-10 bestanden
2. Keine PHP-Fehler/Warnungen im Debug-Log waehrend Tests
3. Keine Dateninkonsistenzen in Beziehungen order/contract/payment/license
4. Release-Artefakte und update-info Versionen konsistent

No-Go wenn:
1. Ein kritischer Workflow blockiert ist
2. Statusuebergaenge inkonsistent sind
3. Bulk Aktionen unvollstaendig oder falsch wirken

## Ergebnisprotokoll (Template)
- Testdatum:
- Tester:
- Umgebung:
- Plugin Version:
- Ergebnis E2E-01 bis E2E-10:
- Offene Defekte:
- Risikoabschaetzung:
- Entscheidung: GO oder NO-GO

## Verknuepfte Doku
- docs/WORDPRESS_PLUGIN_OPERATIONS.md
- docs/WORDPRESS_PLUGIN_RELEASE_PIPELINE.md
