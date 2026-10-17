# Deployment & Distribution System - Implementation Guide

**Version:** 1.0  
**Date:** 2. Mai 2026  
**Status:** Ready for Development

---

## Quick Start (TL;DR)

```bash
# 1. Set GitHub Actions secrets
gh secret set ARTIFACT_SERVER --body "releases.themisdb.org"
gh secret set ARTIFACT_TOKEN --body "your-token"
gh secret set MANIFEST_TOKEN --body "your-token"

# 2. Create release tag
git tag v1.2.0
git push --tags

# 3. GitHub Actions builds artifacts automatically
# (Check Actions tab in GitHub)

# 4. Deploy to server
ssh user@server.com
./deploy.sh themisdb-order-request v1.2.0 /var/www/wordpress

# 5. Customers see update automatically
# (WordPress checks updates via plugin updater hook)
```

---

## Phase 1: GitHub Actions Setup (Week 1)

### Step 1.1: Create Workflow Files

```bash
cd /workspace
mkdir -p .github/workflows

# Copy provided workflow files
cp ../build-and-test.yml .github/workflows/
```

### Step 1.2: Set GitHub Secrets

Navigate to: **Settings → Secrets and variables → Actions**

Required secrets:

| Secret | Value | Example |
|--------|-------|---------|
| `ARTIFACT_SERVER` | Artifact server hostname | `releases.themisdb.org` |
| `ARTIFACT_TOKEN` | API token for uploads | (generate in artifact server) |
| `MANIFEST_TOKEN` | Token for manifest updates | (generate in artifact server) |
| `SLACK_WEBHOOK` | Slack webhook URL | `https://hooks.slack.com/services/...` |

### Step 1.3: Create Release Tag

```bash
# Create and push a version tag
git tag -a v1.2.0 -m "Release v1.2.0"
git push origin v1.2.0

# GitHub Actions triggers automatically
# Monitor in: Settings → Actions → Workflows
```

### Step 1.4: Verify Build

1. Go to **GitHub Repository → Actions**
2. Find "Build & Test" workflow
3. Verify all steps pass:
   - ✅ PHP Lint
   - ✅ PHPCS
   - ✅ PHPUnit Tests
   - ✅ Build Artifacts
   - ✅ Upload to Release

---

## Phase 2: Artifact Server Setup (Week 1-2)

### Option A: Use GitHub Releases (Recommended for small teams)

Artifacts automatically uploaded to GitHub Releases.

**Advantages:**
- Free, no infrastructure needed
- GitHub handles CDN/downloads
- Built-in versioning

**Disadvantages:**
- No custom branding
- GitHub download URLs only

### Option B: Self-Hosted Artifact Server (Enterprise)

```bash
# Create artifact storage directory
mkdir -p /var/www/releases.themisdb.org/{releases,manifests}
chmod 755 /var/www/releases.themisdb.org

# Install Apache/Nginx config
# (See artifact-server-nginx.conf)

# Set up artifact upload API
# (Copy artifact-server/api/manifest.php to server)
```

---

## Phase 3: WordPress Plugin Updater Integration (Week 2)

### Step 3.1: Add Updater to Main Plugin File

**File:** `themisdb-order-request/themisdb-order-request.php`

```php
<?php
/**
 * Plugin Name: ThemisDB Order Request
 * Plugin URI: https://github.com/makr-code/wordpressPlugins
 * Description: Order & contract management for ThemisDB
 * Version: 1.2.0
 * Requires PHP: 7.4
 * Requires WP: 5.0
 * Author: ThemisDB
 */

// Load plugin updater
require_once dirname(__FILE__) . '/includes/class-plugin-updater.php';

// Initialize updater
if (!function_exists('themisdb_order_request_updater')) {
    function themisdb_order_request_updater() {
        new ThemisDB_Plugin_Updater(
            __FILE__,
            'themisdb-order-request',
            '1.2.0',
            apply_filters('themisdb_update_api_url', 
                'https://updates.themisdb.org/api/update-check'
            )
        );
    }
    add_action('plugins_loaded', 'themisdb_order_request_updater');
}

// ... rest of plugin code
```

### Step 3.2: Test Update Check

```php
// Add to wp-admin manually to test
wp eval "
  \$updater = new ThemisDB_Plugin_Updater(
    WP_PLUGIN_DIR . '/themisdb-order-request/themisdb-order-request.php',
    'themisdb-order-request',
    '1.1.0'
  );
  \$result = \$updater->check_for_updates((object)[]);
  print_r(\$result);
"
```

---

## Phase 4: Database Schema Setup (Week 2)

### Step 4.1: Create Release Tracking Table

```bash
# SSH to WordPress server
ssh user@production.server

cd /var/www/wordpress

# Create table
wp eval "
  require_once('wp-admin/includes/upgrade.php');
  global \$wpdb;
  
  \$table = \$wpdb->prefix . 'themisdb_releases';
  \$charset = \$wpdb->get_charset_collate();
  
  \$sql = \"
    CREATE TABLE IF NOT EXISTS \$table (
      id BIGINT AUTO_INCREMENT PRIMARY KEY,
      plugin_slug VARCHAR(100) NOT NULL,
      version VARCHAR(20) NOT NULL,
      status ENUM('draft', 'beta', 'published', 'deprecated') DEFAULT 'draft',
      release_date DATETIME,
      download_url VARCHAR(500),
      changelog LONGTEXT,
      requires_php VARCHAR(10),
      requires_wp VARCHAR(10),
      tested_up_to VARCHAR(10),
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      
      UNIQUE KEY version_unique (plugin_slug, version),
      KEY status_idx (status)
    ) \$charset;
  \";
  
  dbDelta(\$sql);
  echo 'Table created!';
"
```

### Step 4.2: Create Download Log Table

```bash
wp eval "
  require_once('wp-admin/includes/upgrade.php');
  global \$wpdb;
  
  \$table = \$wpdb->prefix . 'themisdb_download_log';
  \$charset = \$wpdb->get_charset_collate();
  
  \$sql = \"
    CREATE TABLE IF NOT EXISTS \$table (
      id BIGINT AUTO_INCREMENT PRIMARY KEY,
      license_id BIGINT,
      plugin VARCHAR(100),
      version VARCHAR(20),
      timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
      
      KEY license_idx (license_id),
      KEY plugin_idx (plugin)
    ) \$charset;
  \";
  
  dbDelta(\$sql);
  echo 'Table created!';
"
```

---

## Phase 5: Server Deployment Setup (Week 3)

### Step 5.1: Install Deploy Script

```bash
# On production server
ssh user@production.server

# Download deploy script
curl https://raw.githubusercontent.com/makr-code/wordpressPlugins/main/scripts/deploy.sh \
  -o /usr/local/bin/themisdb-deploy
chmod +x /usr/local/bin/themisdb-deploy

# Test
themisdb-deploy themisdb-order-request v1.2.0 /var/www/wordpress
```

### Step 5.2: Set Up Automated Deployments (Optional)

```bash
# Create cron job for scheduled checks
# /etc/cron.d/themisdb-deployments

SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin

# Check for updates every 6 hours
0 */6 * * * root /usr/local/bin/themisdb-auto-deploy >> /var/log/themisdb-deployments.log 2>&1
```

### Step 5.3: Manual Deployment Procedure

```bash
# 1. Backup database
mysqldump -u user -p wordpress > /backups/wp-$(date +%Y%m%d).sql

# 2. Deploy plugin
/usr/local/bin/themisdb-deploy themisdb-order-request v1.2.0 /var/www/wordpress

# 3. Run smoke tests
wp plugin is-active themisdb-order-request

# 4. Monitor error logs
tail -f /var/www/wordpress/wp-content/debug.log
```

---

## Phase 6: Customer Download Portal (Week 3)

### Step 6.1: Create Download Page

```bash
cd /var/www/wordpress

# Create page via WP CLI
wp post create \
  --post_type=page \
  --post_title="Download Plugins" \
  --post_content="[themisdb_customer_downloads]" \
  --post_status=publish

# Get page ID
wp post list --post_type=page --format=ids
```

### Step 6.2: Register Shortcode

Add to plugin or theme `functions.php`:

```php
// Activation hook - load customer downloads class
add_action('themisdb_order_request_loaded', function() {
    require_once dirname(__FILE__) . '/page-content/customer-downloads.php';
});
```

### Step 6.3: Create License Check API

**File:** `customer-portal/api-update-check.php`

```php
<?php
// API endpoint for license & update validation

// Allow CORS for browser requests
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Validate license
$license_key = $_POST['license'] ?? $_GET['license'] ?? '';

if (!$license_key) {
    http_response_code(400);
    die(json_encode(['error' => 'License key required']));
}

// Query license status
global $wpdb;
$license = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}themisdb_licenses WHERE license_key = %s",
    $license_key
));

if (!$license || $license->status !== 'active') {
    http_response_code(403);
    die(json_encode(['error' => 'License invalid or expired']));
}

// Return available downloads
echo json_encode([
    'downloads' => [
        [
            'name' => 'ThemisDB Order Request',
            'version' => '1.2.0',
            'url' => 'https://github.com/makr-code/wordpressPlugins/releases/download/v1.2.0/themisdb-order-request-1.2.0.zip'
        ]
    ]
]);
?>
```

---

## Phase 7: Testing & Verification (Week 4)

### Test Checklist

- [ ] GitHub Actions builds on tag push
- [ ] Artifacts upload to GitHub Releases
- [ ] Checksums verify correctly
- [ ] Plugin updater detects new versions
- [ ] License validation prevents unauthorized downloads
- [ ] Customer portal displays available downloads
- [ ] Download links work and plugins install
- [ ] Auto-updates install without errors
- [ ] Rollback procedure works

### Test Commands

```bash
# Test plugin activation
wp plugin activate themisdb-order-request --allow-root

# Test license validation
wp eval "
  \$license = 'test-key-12345';
  \$valid = validate_license_for_update(\$license, site_url());
  echo \$valid ? 'Valid' : 'Invalid';
"

# Test update check API
curl -X POST https://updates.themisdb.org/api/update-check \
  -d "plugin=themisdb-order-request&version=1.1.0&license=test-key"

# Test download
wget "https://github.com/makr-code/wordpressPlugins/releases/download/v1.2.0/themisdb-order-request-1.2.0.zip"

# Verify checksum
sha256sum themisdb-order-request-1.2.0.zip
```

---

## Phase 8: Monitoring & Analytics (Week 4+)

### Set Up Monitoring

```sql
-- Query active version distribution
SELECT version, COUNT(*) as active_count
FROM wp_themisdb_licenses
WHERE status = 'active'
GROUP BY version
ORDER BY active_count DESC;

-- Query download trends
SELECT DATE(timestamp) as download_date, COUNT(*) as downloads
FROM wp_themisdb_download_log
WHERE timestamp > DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY download_date;

-- Query upgrade lag
SELECT 
    l.version as current_version,
    COUNT(*) as customer_count
FROM wp_themisdb_licenses l
WHERE status = 'active'
GROUP BY l.version;
```

### Create Dashboard (Optional)

```bash
# Add admin page for analytics
wp post create \
  --post_type=page \
  --post_title="Deployment Dashboard" \
  --post_content="[themisdb_deployment_analytics]" \
  --post_status=publish
```

---

## Troubleshooting

### Issue: GitHub Actions Build Fails

```bash
# Check workflow logs
gh run list --workflow=build-and-test.yml
gh run view <run-id> --log

# Common fixes
# 1. PHP syntax errors
find . -name "*.php" -exec php -l {} \;

# 2. Missing dependencies
composer install
npm install

# 3. Test database issues
# Ensure MySQL service is running in GitHub Actions
```

### Issue: Deployment Script Fails

```bash
# Debug deployment
bash -x /usr/local/bin/themisdb-deploy themisdb-order-request v1.2.0 /var/www/wordpress

# Check permissions
ls -la /var/www/wordpress/wp-content/plugins/

# Verify WordPress CLI
wp --allow-root plugin list
```

### Issue: Customers Can't Download

```bash
# Check license validation
wp eval "
  require_once 'wp-content/plugins/themisdb-order-request/includes/class-database.php';
  \$license = get_license_by_key('test-key');
  print_r(\$license);
"

# Check downloads table
SELECT * FROM wp_themisdb_download_log LIMIT 10;

# Check shortcode loaded
wp shortcode list | grep themisdb
```

---

## Performance Optimization

### Cache Update Checks

```php
// Transients cache update checks for 12 hours
set_transient('themisdb_update_check', $data, 12 * HOUR_IN_SECONDS);

// Clear cache manually when needed
delete_transient('themisdb_update_check');
```

### CDN for Artifacts

```bash
# If using CloudFront
# Point downloads to CDN instead of direct server
https://cdn.themisdb.org/releases/themisdb-order-request-1.2.0.zip

# Update in manifest
{
  "download_url": "https://cdn.themisdb.org/releases/..."
}
```

---

## Security Checklist

- [ ] All secrets stored in GitHub Secrets (not in code)
- [ ] Checksums verified before installation
- [ ] License validation on all downloads
- [ ] HTTPS enforced for all downloads
- [ ] Rate limiting on API endpoints
- [ ] SQL injection prevention in queries
- [ ] XSS protection in customer portal
- [ ] CSRF tokens on download forms

---

## Support Resources

| Resource | URL |
|----------|-----|
| GitHub Actions Docs | https://docs.github.com/en/actions |
| WordPress Plugin Update API | https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion-svn/ |
| WP-CLI Documentation | https://developer.wordpress.org/cli/ |
| ThemisDB GitHub | https://github.com/makr-code/wordpressPlugins |

---

## Success Criteria

✅ All phases complete when:
- GitHub Actions builds artifacts on every release
- Customers see updates in WordPress admin
- Customers can download via customer portal
- Deployments complete without errors
- System is monitored and documented

**Estimated Timeline:** 4 weeks end-to-end  
**Team Required:** 1 DevOps + 1 Backend Developer
