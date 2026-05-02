# themisdb-support-portal: Deployment & Operations Guide

**Version:** 1.0.5  
**Status:** ✅ Production Ready  
**Last Updated:** 2024-01-15

---

## 📋 Deployment-Checkliste

### Phase 1: Pre-Deployment (Local Development)

- [ ] **Code Review**
  - [ ] All PHP files: `php -l <file>` ✅
  - [ ] All SQL schemas: Syntax valid ✅
  - [ ] Security audit passed:
    - [ ] All SQL: `$wpdb->prepare()` ✅
    - [ ] All HTML output: `esc_html()` / `esc_url()` / `esc_attr()` ✅
    - [ ] All admin forms: Nonce checks ✅
    - [ ] All admin posts: `current_user_can("manage_options")` ✅
  - [ ] E2E Tests: `bash tests/e2e-test-runner.sh` ✅

- [ ] **Database**
  - [ ] Schema Version: 1.0.5 ✅
  - [ ] Tables created: 4 tables (tickets, messages, incident_log, mail_log) ✅
  - [ ] Foreign keys intact ✅
  - [ ] Indexes optimized (especially on `ticket_id`, `status`, `created_at`) ✅

- [ ] **Dependencies**
  - [ ] ThemisDB_License_Manager: Class exists & initialized ✅
  - [ ] ThemisDB_Contract_Lifecycle: Class exists & initialized ✅
  - [ ] ThemisDB_Mail_Orchestrator: Class exists & initialized ✅
  - [ ] Optional: ThemisDB_License_Pricing: class_exists() guard ✅

- [ ] **Configuration**
  - [ ] `THEMISDB_SUPPORT_PORTAL_VERSION = "1.0.5"` ✅
  - [ ] `THEMISDB_SUPPORT_PORTAL_DB_VERSION = "1.0.5"` ✅
  - [ ] All constants in place (TIER_RANK, TIER_SLA_HOURS, STATUS_*) ✅

### Phase 2: Staging Deployment

- [ ] **Environment Setup**
  - [ ] Target: WordPress 6.0+ with PHP 7.4+
  - [ ] Database: MySQL 8.0+ or MariaDB 10.5+
  - [ ] Storage: 100 MB available for plugin + uploads
  - [ ] Network: SMTP server configured for mail delivery

- [ ] **Plugin Installation**
  ```bash
  # Via Git
  cd /var/www/html/wp-content/plugins
  git clone https://github.com/makr-code/wordpressPlugins.git
  cd wordpressPlugins/themisdb-support-portal
  
  # Via ZIP (GitHub Release)
  wget https://github.com/makr-code/wordpressPlugins/releases/download/v1.0.5/themisdb-support-portal.zip
  unzip themisdb-support-portal.zip -d /var/www/html/wp-content/plugins
  ```

- [ ] **Plugin Activation**
  ```bash
  wp plugin activate themisdb-support-portal
  
  # Verify
  wp plugin is-active themisdb-support-portal
  wp plugin list | grep themisdb-support-portal
  ```

- [ ] **Database Setup**
  ```bash
  # Run dbDelta() to create tables
  wp eval 'do_action("wp_themisdb_support_portal_activate");'
  
  # Verify tables created
  wp db query "SHOW TABLES LIKE 'wp_themisdb_%';" --skip-column-names
  ```

- [ ] **Portal Page Setup**
  ```bash
  # Create WordPress pages with shortcodes
  python3 page-content/create_portal_pages.py
  
  # Follow prompts:
  # Enter WordPress base URL: https://staging.themisdb.org
  # Enter WordPress username: admin
  # Enter app password: <app-password>
  # [Preview shortcodes before applying]
  # Apply changes? [y/n]: y
  ```

- [ ] **Shortcode Verification**
  - [ ] `/kundenportal` page exists with `[themisdb_support_login]` + `[themisdb_support_portal]` ✅
  - [ ] `/mein-cockpit` page exists with `[themisdb_cockpit]` ✅
  - [ ] `/vertragsaenderung` page exists with `[themisdb_lifecycle_portal]` ✅
  - [ ] All shortcodes render without 500 errors ✅

- [ ] **Admin Dashboard Setup**
  - [ ] Navigate to: `/wp-admin/admin.php?page=themisdb-support-portal`
  - [ ] Verify 7 submenu pages present:
    - [ ] Tickets ✅
    - [ ] Incidents ✅
    - [ ] Observability ✅
    - [ ] Change-Requests ✅
    - [ ] Terminations ✅
    - [ ] Mail-Log ✅
    - [ ] Einstellungen ✅

- [ ] **Mail System**
  - [ ] SMTP configured in WordPress ✅
  - [ ] Test mail sent & received ✅
  - [ ] Mail-Log table records events ✅
  - [ ] All critical events have mail:
    - [ ] contract.change.requested → Customer ✅
    - [ ] contract.change.approved → Customer ✅
    - [ ] contract.change.rejected → Customer ✅
    - [ ] contract.termination.requested → Customer ✅
    - [ ] contract.termination.confirmed → Customer ✅
    - [ ] contract.termination.rejected → Customer ✅
    - [ ] sla_warning → Support + Customer ✅
    - [ ] sla_breach → Support + Management ✅
    - [ ] ticket_closed → Customer ✅

- [ ] **Scheduler (WP-Cron)**
  - [ ] Cron jobs registered in wp-cli:
    ```bash
    wp cron test
    ```
  - [ ] Expected jobs:
    - [ ] `themisdb_contract_lifecycle_execute` (hourly) ✅
    - [ ] `themisdb_sla_check` (every 6 hours) ✅
  - [ ] Verify execution in logs

- [ ] **Staging Tests**
  ```bash
  # Run full E2E test suite
  bash tests/e2e-test-runner.sh
  
  # Expected output: ✅ E2E-Tests BESTANDEN
  ```

### Phase 3: Production Deployment

- [ ] **Backup & Rollback Plan**
  - [ ] Database backup created ✅
  - [ ] Previous plugin version backed up ✅
  - [ ] Rollback procedure documented ✅
  - [ ] Team informed of maintenance window ✅

- [ ] **Production Installation**
  ```bash
  # Stop any running cron processes
  # (Optional: disable WP-Cron during deployment)
  
  # Upload plugin
  cd /var/www/html/wp-content/plugins
  git fetch origin
  git checkout v1.0.5  # or: main for latest
  
  # Activate
  wp plugin activate themisdb-support-portal --allow-root
  ```

- [ ] **Database Migration (if upgrading)**
  ```bash
  # Run any pending schema updates
  wp eval 'do_action("wp_themisdb_support_portal_activate");' --allow-root
  
  # Verify
  wp option get themisdb_support_portal_db_version --allow-root
  # Should show: 1.0.5
  ```

- [ ] **Portal Pages Deployment**
  ```bash
  # Option 1: Automated (recommended)
  WP_BASE=https://themisdb.org WP_USER=admin WP_APP_PASSWORD=<pw> \
  python3 page-content/create_portal_pages.py --apply
  
  # Option 2: Manual (via WordPress UI)
  # Create pages in Admin → Pages with shortcodes
  ```

- [ ] **Health Check (5 min after deploy)**
  - [ ] `/kundenportal` loads without errors ✅
  - [ ] Admin dashboard loads ✅
  - [ ] Sample ticket created & shows in admin ✅
  - [ ] Mail log records events ✅
  - [ ] No 500 errors in logs ✅

- [ ] **Communication**
  - [ ] Support team notified ✅
  - [ ] Customers informed (if downtime) ✅
  - [ ] Status page updated ✅
  - [ ] Deployment log created ✅

---

## 🚀 Post-Deployment Operations

### Daily Monitoring

```bash
# Check for PHP errors
tail -f /var/log/apache2/error.log | grep themisdb

# Monitor mail queue
wp eval 'echo count(get_transient("themisdb_mail_queue") ?: []);'

# Check active cron jobs
wp cron test

# Monitor SLA breaches
wp db query "SELECT COUNT(*) FROM wp_themisdb_support_tickets WHERE sla_breached_at IS NOT NULL AND DATEDIFF(NOW(), sla_breached_at) < 1;" --skip-column-names
```

### Weekly Tasks

1. **Review Metrics**
   ```bash
   # Dashboard: /wp-admin/admin.php?page=themisdb-support-observability
   # Check: First Response Time, SLA Breach Rate, Ticket Volume
   ```

2. **Check Mail-Log**
   ```bash
   wp db query "SELECT COUNT(*) FROM wp_themisdb_mail_log WHERE DATE(created_at) >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND status='sent';" --skip-column-names
   ```

3. **Verify SLA Escalations**
   ```bash
   wp db query "SELECT COUNT(*) FROM wp_themisdb_incident_log WHERE DATE(created_at) >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND incident_type='sla_breach';" --skip-column-names
   ```

### Monthly Maintenance

1. **Database Optimization**
   ```bash
   wp db query "OPTIMIZE TABLE wp_themisdb_support_tickets;
               OPTIMIZE TABLE wp_themisdb_support_messages;
               OPTIMIZE TABLE wp_themisdb_incident_log;
               OPTIMIZE TABLE wp_themisdb_mail_log;"
   ```

2. **Archive Old Data** (optional)
   ```bash
   # Archive closed tickets older than 90 days
   wp db query "INSERT INTO wp_themisdb_support_tickets_archive 
               SELECT * FROM wp_themisdb_support_tickets 
               WHERE status='closed' AND updated_at < DATE_SUB(NOW(), INTERVAL 90 DAY);"
   ```

3. **Clear Cache**
   ```bash
   wp transient delete-all
   ```

4. **Review Logs**
   - Check `/wp-admin/admin.php?page=themisdb-support-mail-log`
   - Filter by failed/resent mails
   - Investigate any anomalies

### Quarterly Updates

1. **Performance Benchmarking**
   ```bash
   # Run E2E tests under load
   # Check response times vs baseline
   bash tests/e2e-test-runner.sh
   ```

2. **Security Audit**
   - Review security checklist (this document)
   - Run PHP linter on all files
   - Check for new vulnerabilities

3. **Feature Review**
   - Review ROADMAP.md for upcoming features
   - Plan next sprint

---

## 🔧 Troubleshooting

### Plugin Won't Activate

```bash
# Check PHP syntax
php -l themisdb-support-portal/themisdb-support-portal.php

# Check dependencies
wp plugin list | grep -E "themisdb-contracts|themisdb-licenses|themisdb-mail"

# View activation error
wp plugin activate themisdb-support-portal --debug
```

### Database Tables Not Created

```bash
# Manually trigger dbDelta
wp eval 'require_once(WP_PLUGIN_DIR . "/themisdb-support-portal/includes/class-db.php"); 
         ThemisDB_Support_Portal_DB::create_tables();'

# Verify
wp db query "SHOW TABLES LIKE 'wp_themisdb_%';" --skip-column-names
```

### Shortcodes Not Rendering

```bash
# Check if plugin is active
wp plugin is-active themisdb-support-portal

# Check if shortcodes registered
wp eval 'global $wp_filter; print_r(array_keys($wp_filter["init"] ?? []) | grep -i support);'

# Check for PHP errors in page render
wp eval --url=https://themisdb.org/kundenportal 'do_shortcode("[themisdb_support_portal]");'
```

### Mail Not Sending

```bash
# Check mail configuration
wp eval 'echo wp_mail("test@example.com", "Test", "Test mail"); // returns true/false'

# Check mail log
wp db query "SELECT * FROM wp_themisdb_mail_log ORDER BY created_at DESC LIMIT 5;" --format=table

# Resend failed mails
wp eval 'do_action("themisdb_mail_retry_failed");'
```

### SLA Not Escalating

```bash
# Check if cron is running
wp cron test

# Manually trigger SLA check
wp eval 'do_action("themisdb_sla_check");'

# Check incident log
wp db query "SELECT * FROM wp_themisdb_incident_log WHERE incident_type='sla_warning' ORDER BY created_at DESC LIMIT 5;" --format=table
```

### Admin Pages Show Blank

```bash
# Check for fatal errors
tail -f /var/log/apache2/error.log

# Enable debug logging
wp config set WP_DEBUG true
wp config set WP_DEBUG_LOG /var/log/wordpress-debug.log
wp config set WP_DEBUG_DISPLAY false

# Check capability
wp eval 'echo current_user_can("manage_options") ? "YES" : "NO";'
```

---

## 📊 Performance Baselines

Expected performance metrics:

| Metric | Value | Notes |
|--------|-------|-------|
| Page Load (Ticket List) | < 500ms | 100 open tickets |
| Page Load (Admin Dashboard) | < 800ms | Full metrics calculation |
| Ticket Creation | < 200ms | Including email trigger |
| Change-Request Impact Analysis | < 300ms | Full tier comparison |
| E2E Test Suite | ~12s | 6 tests, full coverage |
| SLA Check Job | < 2s | 1000 open tickets |
| Scheduler Execution (Terminations) | < 5s | 100 due terminations |
| Database Query (Index-based) | < 50ms | With proper indexing |

---

## 🔐 Security Hardening

### Recommended Production Settings

```php
// wp-config.php additions
define( 'WP_MEMORY_LIMIT', '256M' );
define( 'WP_MAX_MEMORY_LIMIT', '512M' );
define( 'DISABLE_FILE_EDIT', true );
define( 'WP_AUTOMATIC_UPDATES_CHANNEL', 'security' );

// Database
// Use separate DB user for plugin with limited privileges
// GRANT SELECT, INSERT, UPDATE ON wordpress_db.wp_themisdb_* TO 'plugin_user'@'localhost';
```

### Required WordPress Hardening

- [ ] HTTPS enforced (ssl_redirect)
- [ ] Security headers configured (CSP, HSTS, X-Frame-Options)
- [ ] Regular WordPress updates
- [ ] Regular plugin/theme updates
- [ ] Strong passwords & 2FA for admins
- [ ] Regular backups (daily minimum)
- [ ] WAF rules for SQL injection prevention

### Plugin-Specific Security

- [ ] All admin posts validate nonces ✅
- [ ] All SQL uses prepared statements ✅
- [ ] All output is escaped ✅
- [ ] Capabilities checked for all admin operations ✅
- [ ] Cross-plugin calls guarded with class_exists() ✅
- [ ] No sensitive data in logs ✅

---

## 📞 Support & Escalation

### Escalation Path

1. **Level 1: Support Team**
   - Handle tickets via `/kundenportal`
   - Respond to customers
   - Use admin dashboard for management

2. **Level 2: Operations**
   - Investigate SLA breaches
   - Check scheduler jobs
   - Review mail delivery

3. **Level 3: Development**
   - Debug PHP errors
   - Investigate database issues
   - Review code for bugs

### Contact Information

- **Plugin Developer:** makr-code
- **GitHub Issues:** https://github.com/makr-code/wordpressPlugins/issues
- **Documentation:** `/README.md` in plugin directory
- **Architecture:** `/ARCHITECTUR.md`

---

## 📈 Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0.5 | 2024-01-15 | §8 Complete: Change/Termination Engines, Portal Infrastructure, E2E Tests |
| 1.0.4 | 2024-01-14 | §8.5 Observability Dashboard + SLA Escalation |
| 1.0.3 | 2024-01-13 | §8.1-8.4 Support Portal Core |
| 1.0.0 | 2024-01-01 | Initial Release |

---

## ✅ Final Deployment Confirmation

Before going live, confirm:

- [ ] All tests passing ✅
- [ ] Security audit completed ✅
- [ ] Performance baselines acceptable ✅
- [ ] Team trained on operations ✅
- [ ] Runbooks created ✅
- [ ] Monitoring configured ✅
- [ ] Backups tested ✅
- [ ] Rollback procedure documented ✅

**Deployment Approved:** _______________  
**Date:** _______________  
**Environment:** Production / Staging / Development

---

**Status:** 🟢 READY FOR PRODUCTION  
**Last Review:** 2024-01-15  
**Next Review:** 2024-04-15
