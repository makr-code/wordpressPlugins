# ThemisDB Lifecycle Roadmap

Version: 2.0
Stand: 2. Mai 2026
Status: Umsetzung geplant und priorisiert

## Zielbild

Der komplette Prozess von Bestellung bis Support und Build-Auslieferung laeuft Ende-zu-Ende ohne Medienbruch:

1. Bestellung erfasst
2. Angebot intern geprueft
3. Vertrag/PDF und Zahlungsprozess ausgeliefert
4. Supportzugang provisioniert
5. Build im Portal ausgeloest
6. Build-Ergebnis und Download bereitgestellt
7. Service-Desk uebernimmt Incident- und Change-Prozess
8. Kuendigungen und Vertragsaenderungen laufen ueber standardisierte Workflows

## Phase 0: Konsistenz-Haertung (1 Woche)

Ziel: Technische Widersprueche beseitigen, damit Folgephasen stabil implementierbar sind.

### Aufgaben

1. Ticket-Datenmodell konsolidieren
- Entscheidung: Ein gemeinsames Ticketschema mit source-Feld oder zwei Tabellen mit einheitlicher Repository-Schicht.
- Migration fuer bestehende Tickets planen.

2. GitHub-Sync zentralisieren
- ThemisDB GitHub Bridge wird alleinige Sync-Instanz.
- Legacy-Optionen aus Order als deprecated markieren und auf Bridge-Optionen mappen.

3. Event-Kontrakt einfrieren
- Standard-Events fuer ticket.created, offer.approved, offer.rejected, build.requested, build.completed, build.failed, support.access.provisioned, contract.change.requested, contract.change.approved, contract.change.rejected, contract.termination.requested, contract.termination.confirmed, contract.termination.executed.

### Deliverables

- Migrationsskript
- Kompatibilitaetslayer
- Event-Kontrakt-Doku

## Phase 1: Sales und Vertragsfluss (2 Wochen)

Ziel: Vollstaendiger kaufmaennischer Prozess von Bestellung bis Vertragszustellung.

### Aufgaben

1. Angebotsentscheidung im Admin
- Statusmaschine: new -> reviewed -> approved/rejected -> contracted -> paid -> active.
- Operator-Aktionen mit Audit-Log.

2. Mail-Flow erweitern
- Kunde: Bestellbestaetigung sofort.
- Betreiber: interne Benachrichtigung sofort.
- Kunde: bei approved Vertrag + PDF + Zahlungsaufforderung.
- Kunde: bei rejected Ablehnung mit Grund.

3. PDF- und Vertragsdaten haerten
- Vertragsvorlagen versionieren.
- Rechnungs-/Zahlungsreferenz sauber mitschicken.

### Deliverables

- Freigabe-Workflow im Admin
- E-Mail-Templates in de/en
- Rechtsfester PDF-Workflow

## Phase 2: Supportzugang und Provisionierung (1 Woche)

Ziel: Nach Vertragsannahme und Zahlungsfreigabe wird der Supportzugang automatisch bereitgestellt.

### Aufgaben

1. Zugang erzeugen
- Account, Rolle, Mandant, Lizenzverknuepfung.

2. Zweite Kundenmail
- Zugangsdaten, URL, Erstlogin-Hinweise, Security-Hinweis.

3. Policy-Engine
- SLA/Tier aus Lizenz ableiten.
- Ticketlimits und Prioritaetsrechte aktivieren.

### Deliverables

- Provisioning-Job
- Zugangsmail
- SLA/Tier-Aktivierung

## Phase 3: Build Orchestrierung via GitHub CI (2 Wochen)

Ziel: Kunden koennen Build ausloesen und erhalten verlaessliches Feedback bis Download.

### Aufgaben

1. Build Request API
- Portal-Endpoint mit Berechtigungspruefung und idempotenter Request-ID.
- Speichern von build_id, workflow_run_id, requested_by.

2. CI-Anbindung
- GitHub Actions Dispatch mit signierten Inputs.
- Rueckkanal ueber Workflow-Webhook oder Polling.

3. Benachrichtigung und Download
- Erfolgs-/Fehlermail an Kunde.
- Downloadlink mit Ablaufzeit und Audit.

### Deliverables

- Build-Request-Service
- CI-Status-Sync
- Download-Freigabe + Mail

## Phase 4: Service-Desk Integration (2 Wochen)

Ziel: Support-Portal wird zum echten Service-Desk mit Incident/Change-Faehigkeiten.

### Aufgaben

1. Service-Desk Kern
- Queue, Assignment, Priorisierung, Eskalationen.
- Tickettypen Incident, Service Request, Change Request.

2. Runbook- und Knowledge-Link
- Ticket mit internen Runbooks verknuepfen.
- Standardantworten und Loesungsbausteine.

3. Partner-/Kundenfaehigkeit
- Organisations-/Mandantenmodell.
- Sichtbarkeit pro Kunde/Partner trennen.

### Deliverables

- Service-Desk Modul
- Eskalationsregeln
- Partnerfaehiges Berechtigungskonzept

## Phase 5: Monitoring, KPIs, Betrieb (1 Woche)

Ziel: Vollstaendige Betriebssteuerung fuer dich.

### Aufgaben

1. Dashboard
- Conversion, Angebotsquote, Build-Lead-Time, Ticket-SLA, Erstloesungsquote.

2. Alarmierung
- Build-Fail-Spikes, SLA-Verletzungen, Mail-Fehler, GitHub-Rate-Limits.

3. Operations
- Daily Health Checks, Weekly Review, Monthly KPI-Report.

### Deliverables

- Betreiber-Dashboard
- Alerting-Regeln
- Operations-Runbook

## Phase 6: Kuendigungs- und Change-Workflow (2 Wochen)

Ziel: Kunden koennen Kuendigungen und Leistungsaenderungen rechts- und betriebssicher durchlaufen.

### Aufgaben

1. Kuendigungsworkflow implementieren
- Statusmaschine: termination_requested -> termination_in_review -> termination_confirmed/termination_rejected -> termination_scheduled -> terminated.
- Frist- und Vertragspruefung (Laufzeit, Kuendigungsfrist, Sonderkuendigung).
- Automatische Enddatum-Deaktivierung fuer Lizenz und Supportzugang.

2. Change-Workflow implementieren
- Statusmaschine: change_requested -> change_scoped -> change_offer_sent -> change_approved/declined -> change_applied.
- Auswirkungen auf Preis, SLA, Module, Benutzerkontingente und Build-Profile berechnen.
- Bei kostenpflichtigen Aenderungen Nachtragsangebot + PDF + Zahlungsworkflow.

3. Kommunikation und Nachvollziehbarkeit
- E-Mail-Templates fuer Eingang, Pruefung, Entscheidung, Umsetzung.
- Vollstaendiger Audit-Trail inkl. Operator, Zeitpunkt, Begruendung.

### Deliverables

- Kuendigungsmodul inkl. Scheduler
- Change-Request-Modul inkl. Angebots-/Freigabeschritt
- Rechts- und Audit-konforme Historie

## Akzeptanzkriterien Ende Gesamtprojekt

1. Jeder Auftrag ist in einem eindeutigen Lifecycle-Status.
2. Jede Freigabe/Ablehnung erzeugt nachvollziehbare Kommunikation.
3. Supportzugang wird automatisiert bereitgestellt.
4. Build-Auftraege sind end-to-end verfolgbar.
5. Service-Desk arbeitet mit SLA, Queue und Eskalation.
6. Kunden und Geschaeftspartner erhalten transparente, zeitnahe Informationen.
7. Kuendigungen und Vertragsaenderungen sind mit klaren Status und Fristen durchgaengig abgebildet.
