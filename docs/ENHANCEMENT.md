# ThemisDB Enhancement Plan

Stand: 2. Mai 2026
Fokus: Konkrete Weiterentwicklung fuer Kunden, Geschaeftspartner und Betreiber

## 1. Kundenorientierte Verbesserungen

### 1.1 Transparentes Kunden-Cockpit

Ziele:
- Kunde sieht Bestellstatus, Vertragsstatus, Zahlungsstatus, Build-Status und Supportstatus auf einer Seite.

Implementierung:
1. Neues Portal-Widget Lifecycle Timeline.
2. Statusquelle aus Order, License, Build und Ticket-Service aggregieren.
3. E-Mail-Inhalte mit denselben Statusbegriffen synchronisieren.

Erfolgskriterien:
- Weniger Rueckfragen zu "Wo steht meine Bestellung?"
- Hoehere Selbstbedienungsquote.

### 1.2 Verlaessliche Build-Kommunikation

Ziele:
- Kein Build ohne Statusrueckmeldung.

Implementierung:
1. Build-Request erhaelt immutable request_id.
2. CI-Run-ID und Artefakt-Link werden in lokaler Build-Tabelle persistiert.
3. Kunde bekommt Erfolg-/Fehlernachricht mit naechsten Schritten.

Erfolgskriterien:
- 100 Prozent Build-Auftraege mit sichtbarem Endstatus.

### 1.3 Support mit SLA-Sichtbarkeit

Ziele:
- Kunde sieht erwartete Reaktionszeit und Eskalationsstufe.

Implementierung:
1. SLA je Lizenz-Tier ermitteln.
2. Anzeige im Ticketkopf: Zielantwortzeit, Restzeit, Prioritaet.
3. Eskalation bei Deadline-Verletzung.

Erfolgskriterien:
- Reduktion SLA-Verletzungen.

### 1.4 Kuendigungs-Self-Service mit kontrollierter Ausfuehrung

Ziele:
- Kunde kann Kuendigung transparent und rechtssicher ausloesen.

Implementierung:
1. Portal-Formular fuer Kuendigungsantrag mit Auswahlgrund und gewuenschtem Enddatum.
2. Validierung gegen Vertragslaufzeit, Kuendigungsfrist und offene Zahlungen.
3. Betreiber-Freigabe mit Standardtexten fuer Bestaetigung/Ablehnung.
4. Terminierte technische Deaktivierung (Lizenz, Supportzugang, Build-Rechte).

Erfolgskriterien:
- Jede Kuendigung ist revisionssicher dokumentiert und fristgerecht umgesetzt.

### 1.5 Aenderungsworkflow (Change Request)

Ziele:
- Kunden koennen Leistungs- und Vertragsaenderungen strukturiert beantragen.

Implementierung:
1. Tickettyp "Change Request" mit Pflichtfeldern fuer betroffene Leistungen.
2. Automatische Impact-Analyse (Preis, SLA, Build, Supportkontingente).
3. Genehmigungspfad mit optionalem Nachtragsangebot (PDF + Zahlungslink).
4. Technische Umsetzung ueber idempotente Apply-Operationen auf Lizenz- und Service-Ebene.

Erfolgskriterien:
- Aenderungen laufen nachvollziehbar von Antrag bis Umsetzung ohne manuelle Seiteneffekte.

## 2. Verbesserungen fuer Geschaeftspartner

### 2.1 Mandanten- und Partnerfaehigkeit

Ziele:
- Partner verwalten mehrere Endkunden sauber getrennt.

Implementierung:
1. Organisationstabelle mit parent-child Struktur.
2. Rollenmodell: Partner Admin, Partner Agent, Customer Admin, Customer User.
3. Filter und Datenzugriff strikt organisationsgebunden.

### 2.2 Partner API und Webhooks

Ziele:
- CRM/PSA/ITSM Integrationen fuer Partner.

Implementierung:
1. REST-Endpunkte fuer Bestellung, Ticket, Build-Status.
2. Signierte Webhooks fuer lifecycle.changed und build.completed.
3. Retry und Dead-Letter Queue fuer fehlerhafte Webhook-Ziele.

### 2.3 Vertrags- und Rechnungsdaten fuer B2B

Ziele:
- Standardisierte kaufmaennische Daten fuer Partnerabrechnung.

Implementierung:
1. Rechnungsreferenz, VAT, Kostenstelle, Purchase Order in Vertragsdatenmodell.
2. Export als CSV/JSON fuer ERP.

## 3. Verbesserungen fuer dich (Betrieb und Steuerung)

### 3.1 Einheitliche Sync-Steuerung

Ziele:
- Keine Doppelkonfiguration bei GitHub-Sync.

Implementierung:
1. Bridge als einzige Konfigurationsstelle.
2. Legacy-Order-Settings nur read-only anzeigen und mappen.
3. Health-Checks auf fehlende Token/Repo/Scopes.

### 3.2 Operatives Incident-Management

Ziele:
- Fehler im Prozess frueh erkennen und beheben.

Implementierung:
1. Incident-Tabelle fuer Mail-, Build-, Sync-Fehler.
2. Admin-Seite mit Requeue, Ack, Resolve.
3. Triage-Regeln nach Schweregrad.

### 3.3 KPI-gesteuerte Steuerung

Ziele:
- Entscheidungen auf Kennzahlen statt Bauchgefuehl.

Implementierung:
1. KPI Pipeline fuer Conversion, Angebot->Vertrag, Ticket-SLA, Build-Lead-Time.
2. Weekly Report per Mail.
3. Zielwerte und Abweichungsalarme.

### 3.4 Vertrags-Lifecycle Governance

Ziele:
- Volle Steuerbarkeit ueber Neuanlage, Aenderung, Verlaengerung und Kuendigung.

Implementierung:
1. Lifecycle-Dashboard mit Vertragsstatus inkl. pending changes und pending terminations.
2. Fristenmonitor fuer Kuendigungen und SLA-relevante Aenderungen.
3. Pflicht-Audit fuer operatorseitige Entscheidungen.

## 4. Technische Enhancements (konkret)

1. Datenmodell-Haertung
- Gemeinsames Ticket-Repository oder getrennte Tabellen mit Mapping-Layer.
- Eindeutiger source_type und source_id fuer alle Tickets.

2. Event-Bus im WordPress-Kontext
- Standardisierte do_action Events.
- Event-Payload-Versionierung.

3. Hintergrundjobs
- Action Scheduler fuer retries und langlaufende Tasks.
- Backoff-Strategien fuer externe APIs.

4. Security
- PII-Minimierung in Logs.
- Token-Rotation und Scope-Validierung.
- Revisionssichere Audit-Trails.

## 5. Priorisierte Backlog-Items

Prioritaet A:
1. Ticketmodell konsolidieren.
2. GitHub-Sync zentralisieren.
3. Build-Rueckkanal mit Kundenmail abschliessen.
4. Kuendigungsworkflow inkl. Deaktivierungspfad implementieren.
5. Change-Workflow inkl. Impact- und Genehmigungslogik implementieren.

Prioritaet B:
1. Service-Desk Queue und Eskalation.
2. Partnerfaehigkeit und Organisationsmodell.
3. KPI Dashboard und Incident Board.

Prioritaet C:
1. Webhooks fuer Partner.
2. ERP Export.
3. Erweiterte Self-Service-Flows.
