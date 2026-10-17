# ThemisDB System-Architektur fuer Shop, Build und Service-Desk

Stand: 2. Mai 2026

## 1. Architekturprinzipien

1. Single Source of Truth je Domane.
2. Ereignisgetriebene Kopplung zwischen Plugins.
3. Nachvollziehbarkeit durch Audit- und Status-Historie.
4. Idempotenz bei externen Integrationen (CI, Mail, GitHub API).

## 2. Domanen und Komponenten

### 2.1 Sales und Vertragsdomane

Komponente:
- themisdb-order-request

Verantwortung:
- Angebot/Bestellung
- Vertrags-PDF
- Zahlungsaufforderung
- Lizenzbereitstellung triggern
- Vertragsaenderungen und Kuendigungen orchestrieren

### 2.2 Support- und Service-Desk-Domane

Komponente:
- themisdb-support-portal

Verantwortung:
- Kundenportal fuer Tickets
- Service-Desk Queue, Assignment, Eskalation
- SLA und Priorisierung

### 2.3 Integrationsdomane

Komponente:
- themisdb-github-bridge

Verantwortung:
- Issue-Sync
- Build-Dispatch an GitHub Actions
- Build-Status-Rueckkanal

## 3. Ziel-Datenmodell (vereinheitlicht)

### 3.1 Order
- order_id
- customer_id
- status
- offered_at, approved_at, rejected_at
- contract_pdf_url

### 3.1.1 Contract Change
- change_id
- order_id
- requested_by
- change_type
- impact_summary
- status
- approved_at, applied_at

### 3.1.2 Contract Termination
- termination_id
- license_id
- requested_by
- requested_end_date
- effective_end_date
- reason
- status
- confirmed_at, executed_at

### 3.2 License
- license_id
- order_id
- tier
- status
- support_access_status

### 3.3 Support Ticket
- ticket_id
- source_plugin
- source_external_id
- customer_id
- type (incident/request/change)
- priority
- status
- sla_due_at
- github_issue_number
- github_issue_url

### 3.4 Build Job
- build_id
- license_id
- requested_by
- github_workflow_run_id
- status
- artifact_url
- started_at, finished_at

### 3.5 Incident Log
- incident_id
- domain (mail/build/sync)
- severity
- status
- last_error
- retry_count

## 4. Event-Flow (Soll)

1. order.created
- erzeugt Kunden- und Betreiber-Mail.

2. order.approved oder order.rejected
- approved: contract.generated, payment.requested.
- rejected: rejection.sent.

3. license.activated
- support.access.provisioned.
- support.access.mail.sent.

4. build.requested
- github.workflow.dispatched.

5. build.completed oder build.failed
- customer.notified.
- download.released (nur completed).

6. support.ticket.created
- optional github.issue.created.
- service_desk.queue.assigned.

7. contract.change.requested
- change.impact.calculated.
- change.offer.created (optional).

8. contract.change.approved oder contract.change.rejected
- approved: change.applied, customer.notified.
- rejected: change.closed, customer.notified.

9. contract.termination.requested
- termination.validated (Frist/Laufzeit).

10. contract.termination.confirmed
- termination.scheduled.

11. contract.termination.executed
- license.deactivated.
- support.access.revoked.
- customer.notified.

## 5. Integrationspunkte Service-Desk

### 5.1 Ticket-Inbound

Eingaenge:
- Portalformular
- E-Mail Inbound (optional)
- Partner-API

Validierung:
- Lizenzstatus
- Rollenrechte
- SLA-Zuordnung
- Bei Change/Termination: Vertragsfristen und kaufmaennische Voraussetzungen

### 5.2 Queue und Routing

Regeln:
1. Tickettyp und Prioritaet bestimmen Zielqueue.
2. Tier bestimmt SLA und Eskalationsgrenze.
3. Unzugeordnete Tickets gehen in Triage Queue.

### 5.3 Eskalation

Regeln:
- Warnung bei 70 Prozent SLA-Verbrauch.
- Eskalation bei 100 Prozent SLA-Verletzung.
- Management-Alert bei mehrfachen Verletzungen pro Kunde.

## 6. Sicherheits- und Compliance-Architektur

1. Alle Admin-Aktionen mit capability checks und nonce.
2. API-Token nur serverseitig, nie im Frontend.
3. Audit-Trail fuer Statuswechsel und Operator-Aktionen.
4. PII-Schutz in Logs und Exports.

## 7. Observability

Metriken:
- Order Conversion Rate
- Angebot -> Vertrag Rate
- Build Success Rate
- Build Lead Time
- First Response Time
- SLA Breach Rate

Technik:
- Persistente Job- und Incident-Tabellen
- Admin Dashboard mit Echtzeitstatus
- Geplante Reports (daily/weekly)

## 8. Konkrete Integrationsaufgaben

1. Ticketschema harmonisieren.
2. Build-Service in Bridge kapseln (dispatch + polling/webhook).
3. Service-Desk Modul in Support-Portal integrieren.
4. Mail-Orchestrator fuer alle Lifecycle-Mails einfuehren.
5. Unified Status Resolver fuer Kunden-Cockpit bauen.
6. Contract-Change-Engine mit Impact-Analyse und Genehmigungspfad bauen.
7. Contract-Termination-Engine mit Scheduler fuer Enddatum-Deaktivierung bauen.

## 9. Definition of Done

1. Jeder Lifecycle-Schritt erzeugt einen persistierten Status.
2. Kunde erhaelt in jedem kritischen Schritt eine klare Mail.
3. Build und Ticket sind voll auditierbar.
4. Service-Desk arbeitet mit SLA und Eskalation produktiv.
5. Betreiber hat ein zentrales Steuerungsdashboard.
6. Kuendigung und Aenderung laufen mit klaren Events, Status und Audit Ende-zu-Ende.
