# ThemisDB Admin Release Acceptance Matrix

## Ziel

Diese Matrix dient als kompakte Release-Abnahme für die modernisierten ThemisDB-Admin-Oberflächen.

Sie verbindet drei Ebenen:

- Implementierungsstand
- manuelle QA nach Checkliste
- technischer Smoke-Check

Referenzen:

- `docs/THEMISDB_ADMIN_TABS_IMPLEMENTATION_SUMMARY.md`
- `docs/THEMISDB_ADMIN_MODERNIZATION_QA_CHECKLIST.md`
- `scripts/themisdb-admin-modernization-smoke.ps1`

## Statuslegende

- `OPEN` = noch nicht geprüft
- `PASS` = geprüft und bestanden
- `WARN` = geprüft, aber mit Restpunkt oder Beobachtung
- `FAIL` = nicht bestanden, Release blockiert
- `N/A` = für dieses Plugin nicht relevant

## Release-Metadaten

- Testdatum:
- Tester:
- Umgebung:
- WordPress-Version:
- PHP-Version:
- Debug-Log-Pfad:
- Smoke-Report-Pfad:

## Matrix

| Plugin / Admin-Bereich | Implementiert | Manuelle QA | Smoke-Check | Debug-Log | Freigabe | Hinweise |
| --- | --- | --- | --- | --- | --- | --- |
| themisdb-architecture-diagrams | PASS | OPEN | OPEN | OPEN | OPEN | |
| themisdb-benchmark-visualizer | PASS | OPEN | OPEN | OPEN | OPEN | |
| themisdb-query-playground | PASS | OPEN | OPEN | OPEN | OPEN | |
| themisdb-release-timeline | PASS | OPEN | OPEN | OPEN | OPEN | |
| themisdb-tco-calculator | PASS | OPEN | OPEN | OPEN | OPEN | |
| themisdb-test-dashboard | PASS | OPEN | OPEN | OPEN | OPEN | |
| themisdb-wiki-integration Settings | PASS | OPEN | OPEN | OPEN | OPEN | |
| themisdb-wiki-integration Importer | PASS | OPEN | OPEN | OPEN | OPEN | |
| themisdb-feature-matrix Settings | PASS | OPEN | OPEN | OPEN | OPEN | |
| themisdb-feature-matrix Admin | PASS | OPEN | OPEN | OPEN | OPEN | |
| themisdb-support-portal Settings | PASS | OPEN | OPEN | OPEN | OPEN | |
| themisdb-support-portal Ticket-Uebersicht | PASS | OPEN | OPEN | OPEN | OPEN | |
| themisdb-support-portal Ticket-Detail | PASS | OPEN | N/A | OPEN | OPEN | derzeit nur manuelle Detailabnahme |
| themisdb-compendium-downloads | PASS | OPEN | OPEN | OPEN | OPEN | |
| themisdb-docker-downloads | PASS | OPEN | OPEN | OPEN | OPEN | |
| themisdb-downloads | PASS | OPEN | OPEN | OPEN | OPEN | |
| themisdb-formula-renderer Settings | PASS | OPEN | OPEN | OPEN | OPEN | |
| themisdb-formula-renderer Library | PASS | OPEN | OPEN | OPEN | OPEN | |
| themisdb-front-slider | PASS | OPEN | OPEN | OPEN | OPEN | |
| themisdb-gallery | PASS | OPEN | OPEN | OPEN | OPEN | |
| themisdb-github-bridge | PASS | OPEN | OPEN | OPEN | OPEN | |
| themisdb-taxonomy-manager Admin | PASS | OPEN | OPEN | OPEN | OPEN | |
| themisdb-taxonomy-manager Tree View | PASS | OPEN | OPEN | OPEN | OPEN | |
| themisdb-order-request Dashboard | PASS | OPEN | OPEN | OPEN | OPEN | |
| themisdb-order-request Orders | PASS | OPEN | OPEN | OPEN | OPEN | |
| themisdb-order-request Contracts | PASS | OPEN | N/A | OPEN | OPEN | Detailprüfung primär manuell |
| themisdb-order-request Products & Modules | PASS | OPEN | OPEN | OPEN | OPEN | |
| themisdb-order-request Inventory | PASS | OPEN | N/A | OPEN | OPEN | Detailprüfung primär manuell |
| themisdb-order-request Payments | PASS | OPEN | OPEN | OPEN | OPEN | |
| themisdb-order-request Licenses | PASS | OPEN | OPEN | OPEN | OPEN | |
| themisdb-order-request Support Tickets | PASS | OPEN | OPEN | OPEN | OPEN | |
| themisdb-order-request E-Mail Log | PASS | OPEN | N/A | OPEN | OPEN | Log-Seite vor allem manuell prüfen |
| themisdb-order-request License Audit Log | PASS | OPEN | N/A | OPEN | OPEN | Log-Seite vor allem manuell prüfen |
| themisdb-order-request Bankimport | PASS | OPEN | OPEN | OPEN | OPEN | Testdaten erforderlich |
| themisdb-order-request Settings | PASS | OPEN | OPEN | OPEN | OPEN | |
| themisdb-order-request Advanced Reporting | PASS | OPEN | OPEN | OPEN | OPEN | Frontend-Abgrenzung mitprüfen |

## Empfohlener Ablauf

1. Smoke-Check gegen Testinstanz ausführen.
2. Ergebnisdatei und Debug-Log sichten.
3. Manuelle QA gemäß Checkliste pro Plugin/Seite durchführen.
4. Matrixzeilen von `OPEN` auf `PASS`, `WARN` oder `FAIL` setzen.
5. Gesamtfreigabe nur erteilen, wenn keine `FAIL`-Zeile verbleibt.

## Gesamtentscheidung

- Gesamtstatus:
- Blocker:
- Restpunkte:
- Release freigegeben: Ja/Nein