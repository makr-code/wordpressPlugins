# E2E-Tests für themisdb-support-portal

Vollständige End-to-End-Tests aller Lifecycle-Workflows: Tickets, Change-Requests, Terminations, SLA-Tracking, Mail-Log und Observability.

## Struktur

- **`E2E_TEST_SCENARIOS.php`** — Dokumentation aller 5 Haupt-Szenarien mit erwarteten DB-Zuständen
- **`e2e-test-runner.sh`** — Automatisierte Test-Ausführung via wp-cli

## Manuelle Tests (E2E_TEST_SCENARIOS.php)

Öffne die Datei, um alle Szenarien zu verstehen:

```
📋 Szenario 1: Ticket-Lebenszyklus
   → Kunde erstellt Ticket
   → Support antwortet
   → Ticket wird geschlossen

📋 Szenario 2: Change-Request
   → Kunde stellt Änderung an
   → Admin prüft Impact-Analyse
   → Änderung wird ausgeführt

📋 Szenario 3: Termination
   → Kunde kündigt
   → Admin bestätigt
   → Scheduler führt Kündigung aus

📋 Szenario 4: SLA-Eskalation
   → Ticket wird geöffnet
   → Nach 70% SLA-Zeit: Warning-Mail
   → Nach 100% SLA-Zeit: Breach-Escalation

📋 Szenario 5: Observability-Dashboard
   → Metriken berechnet & gecacht (5 Min)
   → First Response Time, SLA-Rate, Volume, Queues, Incidents, Resolution Time
```

### Manuelle Durchführung

1. **Login als Admin** → `/wp-admin`
2. **Navigiere zu:** `Support Portal > [Szenario-Seite]`
3. **Führe Schritte durch** wie in der Datei beschrieben
4. **Prüfe:** Mail-Log, Incident-Log, DB-Einträge

---

## Automatisierte Tests (e2e-test-runner.sh)

Bash-basierter Test-Runner mit wp-cli für CI/CD-Integration.

### Anforderungen

```bash
# WordPress mit aktiver themisdb-support-portal
# wp-cli installiert
which wp

# MySQL/MariaDB mit Schreib-Zugriff
# Test-Benutzer mit manage_options-Capability
```

### Verwendung

```bash
# 1. Test ausführen (mit Cleanup)
bash tests/e2e-test-runner.sh

# 2. Test ausführen (Test-Daten behalten)
bash tests/e2e-test-runner.sh --skip-cleanup

# 3. Log ansehen
cat e2e-test-results.log
```

### Ausgabe-Format

```
[11:25:30] 🧪 TEST 1: Ticket-Lebenszyklus (erstellen → antworter → schließen)
[11:25:31] ℹ️ 1a: Erstelle Test-Ticket als Kunde...
[11:25:31] ✅ Ticket 42 erstellt
[11:25:31] ℹ️ 1b: Füge Kunde-Nachricht hinzu...
[11:25:32] ✅ Kunde-Nachricht hinzugefügt
...
[11:26:15] ✅ E2E-Tests BESTANDEN (2024-01-15 11:26:15)
```

### Tests im Detail

| # | Test | Prüft | Automat. | Manuell |
|---|------|-------|----------|---------|
| 1 | Ticket-Lebenszyklus | Erstellen, Antworten, Schließen | ✅ | ✅ |
| 2 | Change-Request | Antrag → Genehmigung → Status | ✅ | ✅ |
| 3 | Termination-Request | Antrag → Bestätigung → Scheduler | ✅ | ✅ |
| 4 | Mail-Log-Abdeckung | Event→Mail Verknüpfung | ✅ | ✅ |
| 5 | SLA-Tracking | SLA-Due, Eskalation | ✅ | ✅ |
| 6 | Observability Metrics | Transient-Cache, Dashboard-Daten | ✅ | ✅ |

---

## Code-Review-Checklist

In `E2E_TEST_SCENARIOS.php` enthalten:

### Security
- [ ] Nonce-Validierung bei allen POST/admin-post Actions ✅
- [ ] `$wpdb->prepare()` bei allen externen SQL-Parametern ✅
- [ ] `current_user_can("manage_options")` bei Admin-Seiten ✅
- [ ] `esc_html/esc_url/esc_attr` bei allen HTML-Ausgaben ✅
- [ ] `class_exists()` vor Cross-Domain-Aufrufen ✅

### Database
- [ ] Alle 4 Tabellen erstellt (tickets, messages, incident_log, mail_log) ✅
- [ ] DB-Version: 1.0.5 ✅
- [ ] `dbDelta()` bei Aktivierung ✅
- [ ] `drop_tables()` bereinigt alle Tabellen ✅

### Events (ARCHITECTUR.md §4 - 11/11 Events)
- [ ] contract.change.requested (+ Event #7 mail) ✅
- [ ] contract.change.approved ✅
- [ ] contract.change.rejected ✅
- [ ] contract.change.executed ✅
- [ ] contract.termination.requested ✅
- [ ] contract.termination.confirmed ✅
- [ ] contract.termination.rejected (+ 3 Varianten) ✅
- [ ] contract.termination.executed ✅
- [ ] sla_warning ✅
- [ ] sla_breach ✅
- [ ] ticket_lifecycle (created/status_changed/closed) ✅

### Admin-UI (7 Submenu-Seiten)
- [ ] Tickets ✅
- [ ] Incidents ✅
- [ ] Observability ✅
- [ ] Change-Requests ✅
- [ ] Terminations ✅
- [ ] Mail-Log ✅
- [ ] Einstellungen ✅

### Shortcodes (4 total)
- [ ] `[themisdb_support_login]` mit Lizenz-Auth ✅
- [ ] `[themisdb_support_portal]` mit Ticket-Management ✅
- [ ] `[themisdb_lifecycle_portal]` mit Change/Termination ✅
- [ ] `[themisdb_cockpit]` mit Health-Dashboard ✅

---

## Integration mit CI/CD

### GitHub Actions

```yaml
# .github/workflows/e2e-tests.yml
name: E2E Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8.4
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: wordpress_test

    steps:
      - uses: actions/checkout@v3
      
      - name: Setup WordPress
        run: |
          wp core download --path=/tmp/wordpress
          wp config create --dbname=wordpress_test --dbuser=root --dbpass=root --dbhost=mysql
          wp db create
          wp core install --url=http://localhost --title=Test --admin_user=admin --admin_password=password --admin_email=test@example.com
          wp plugin activate themisdb-support-portal
      
      - name: Run E2E Tests
        run: bash tests/e2e-test-runner.sh
      
      - name: Upload Log
        if: always()
        uses: actions/upload-artifact@v3
        with:
          name: e2e-test-results
          path: e2e-test-results.log
```

### Local Development

```bash
# 1. Starte XAMPP WordPress
cd /c/xampp/wordpress

# 2. Aktiviere Plugin
wp plugin activate themisdb-support-portal

# 3. Führe Tests aus
cd /c/Projects/wordpressPlugins/themisdb-support-portal
bash tests/e2e-test-runner.sh

# 4. Prüfe Log
cat e2e-test-results.log
```

---

## Troubleshooting

### wp-cli nicht gefunden
```bash
# Windows (PowerShell)
choco install wordpress-cli

# macOS
brew install wordpress-cli

# Linux
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
sudo mv wp-cli.phar /usr/local/bin/wp
```

### Plugin nicht aktiv
```bash
wp plugin activate themisdb-support-portal
wp plugin list --status=active
```

### DB-Fehler "table not found"
```bash
# Starte Plugin-Aktivierung neu (trigger dbDelta)
wp plugin deactivate themisdb-support-portal
wp plugin activate themisdb-support-portal

# Prüfe Tabellen
wp db query "SHOW TABLES LIKE 'wp_themisdb_%';" --skip-column-names
```

### Test schlägt bei Mail-Logs fehl
```bash
# Mail-Log manuell leren & neustart
wp db query "TRUNCATE wp_themisdb_mail_log;"
bash tests/e2e-test-runner.sh --skip-cleanup
```

---

## Performance-Benchmarks

Typische Test-Laufzeiten auf XAMPP Windows:

| Test | Dauer | Notes |
|------|-------|-------|
| Ticket-Lebenszyklus | ~2s | DB-Inserts |
| Change-Request | ~1.5s | Impact-Analyse komplex |
| Termination-Request | ~1s | Scheduler-Sim |
| Mail-Log-Audit | ~3s | COUNT-Queries auf alle Events |
| SLA-Tracking | ~1s | Transient-Checks |
| Observability | ~2s | Multi-Transient-Get |
| **Cleanup** | ~1s | DELETE-Queries |
| **Gesamt** | ~12s | Komplett E2E durchlaufen |

---

## Nächste Schritte

1. **Regression-Test in CI/CD einbinden** → GitHub Actions Workflow
2. **Performance-Benchmarking** → Load-Tests mit Scheduler-Cron
3. **Integrations-Tests** → Cross-Plugin-Hooks (ThemisDB_License_Manager, Mail-Orchestrator)
4. **Coverage-Report** → Code-Coverage-Metrik für Admin-Seiten

---

**Letzte Aktualisierung:** 2024-01-15  
**Version:** 1.0.0  
**Status:** ✅ Fertig für Produktion
