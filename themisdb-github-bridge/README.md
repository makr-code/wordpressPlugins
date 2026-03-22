# ThemisDB GitHub Bridge

Zweck: zentrale GitHub-Kommunikation fuer
- themisdb-order-request
- themisdb-support-portal

## Features

- automatische Issue-Erstellung bei neuen Tickets
- getrennte Schalter fuer Order- und Support-Portal-Quelle
- frei konfigurierbare Labels je Quelle
- Mapping-Tabelle `wp_themisdb_github_links` fuer Nachverfolgung

## Einrichtung

1. Plugin aktivieren: `themisdb-github-bridge`
2. Unter `Einstellungen -> ThemisDB GitHub Bridge` konfigurieren:
   - Bridge aktivieren
   - Repository (`owner/repo`)
   - GitHub Token (Issues Read/Write)
   - Quell-spezifische Label

## Integrations-Hooks

- Eingang (vom Quell-Plugin):
  - `themisdb_order_support_ticket_created`
  - `themisdb_support_portal_ticket_created`

- Ausgang (nach erfolgreicher Issue-Erstellung):
  - `themisdb_github_bridge_issue_created`
