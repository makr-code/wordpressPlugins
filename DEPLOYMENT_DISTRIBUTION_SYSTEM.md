# ThemisDB Plugin Deployment & Distribution System

**Version:** 1.0  
**Date:** 2. Mai 2026  
**Scope:** End-to-End Software Distribution for WordPress Plugins  
**Status:** Design Phase ✅

---

## 🎯 System Overview

Das Deployment & Distribution System orchestriert:
- 📦 **Artifact Creation**: GitHub Actions baut Release-Packages
- 🚀 **Server Deployment**: Packages werden auf produktive Server übertragen
- 📥 **Customer Download**: Kunden laden Plugins direkt herunter
- 🔄 **Auto-Updates**: WordPress Plugin Updater prüft & installiert Updates
- 📊 **License Validation**: Update-Downloads sind an gültige Lizenzen gebunden
- 📈 **Telemetry**: Update-Statistiken & Error-Tracking

---

## 1. Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                    GITHUB REPOSITORY                             │
│  (makr-code/wordpressPlugins)                                    │
└──────────────────────────┬──────────────────────────────────────┘
                           │
                ┌──────────▼───────────┐
                │  GitHub Actions CI   │
                │  (Build & Test)      │
                └──────────┬───────────┘
                           │
           ┌───────────────┼───────────────┐
           │               │               │
    ┌──────▼────────┐ ┌────▼────────┐ ┌───▼─────────┐
    │   Release     │ │   Publish   │ │   Artifact  │
    │   Artifacts   │ │   Release   │ │   Store     │
    │   (ZIP)       │ │   to GitHub │ │   (S3/Obj)  │
    └──────┬────────┘ └────────────┘ └───┬─────────┘
           │                             │
      ┌────▼──────────────────────────────▼────┐
      │   Download CDN / Artifact Server        │
      │   (https://releases.themisdb.org)       │
      └────┬────────────────┬───────────────────┘
           │                │
    ┌──────▼────────┐ ┌────▼────────────┐
    │  WordPress    │ │  Customer       │
    │  Auto-Update  │ │  Download       │
    │  Hook         │ │  Portal         │
    │  (License     │ │  (License       │
    │  Validated)   │ │  Check)         │
    └──────┬────────┘ └────┬────────────┘
           │                │
    ┌──────▼────────────────▼────────┐
    │   Customer WordPress Instance   │
    │   (Plugin Activated & Running)   │
    └─────────────────────────────────┘
```

---

## 2. GitHub Actions CI/CD Pipeline

### 2.1 Build & Test Workflow

**File:** `.github/workflows/build.yml` (NEW)

```yaml
name: Build & Test

on:
  push:
    branches: [main, develop]
    tags: ['v*']
  pull_request:
    branches: [main, develop]

env:
  PHP_VERSION: 7.4
  WORDPRESS_VERSION: 6.0

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.4
        env:
          MYSQL_DATABASE: wordpress_test
          MYSQL_ROOT_PASSWORD: root
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3
    
    steps:
      - uses: actions/checkout@v3
        with:
          fetch-depth: 0  # Full history for versioning
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ env.PHP_VERSION }}
          extensions: mysqli, curl, gd, mbstring
          tools: composer
      
      - name: Install dependencies
        run: |
          composer install --prefer-dist --no-progress
          npm install
      
      - name: PHP Lint
        run: |
          find . -name "*.php" ! -path "./vendor/*" -exec php -l {} \;
      
      - name: PHPCS (WordPress Coding Standards)
        run: |
          ./vendor/bin/phpcs --standard=WordPress --extensions=php \
            themisdb-support-portal/includes/ \
            themisdb-order-request/includes/
      
      - name: PHPUnit Tests
        run: |
          ./vendor/bin/phpunit --coverage-text --coverage-clover=coverage.xml
      
      - name: Upload Coverage to Codecov
        uses: codecov/codecov-action@v3
        with:
          files: ./coverage.xml
          flags: unittests
          fail_ci_if_error: false

  build-artifacts:
    needs: test
    runs-on: ubuntu-latest
    if: success()
    
    strategy:
      matrix:
        plugin:
          - themisdb-support-portal
          - themisdb-order-request
          - themisdb-license-manager
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Extract version from plugin file
        id: version
        run: |
          VERSION=$(grep -m1 "Version:" ${{ matrix.plugin }}/${{ matrix.plugin }}.php | \
                    sed -E 's/.*Version:\s*([0-9.]+).*/\1/')
          echo "version=$VERSION" >> $GITHUB_OUTPUT
      
      - name: Create release artifact
        run: |
          mkdir -p build/
          zip -r build/${{ matrix.plugin }}-${{ steps.version.outputs.version }}.zip \
            ${{ matrix.plugin }}/ \
            -x "${{ matrix.plugin }}/.*" "${{ matrix.plugin }}/*.lock"
      
      - name: Upload artifact to GitHub
        uses: actions/upload-artifact@v3
        with:
          name: ${{ matrix.plugin }}-${{ steps.version.outputs.version }}
          path: build/${{ matrix.plugin }}-${{ steps.version.outputs.version }}.zip
          retention-days: 90
```

---

### 2.2 Release & Publish Workflow

**File:** `.github/workflows/release.yml` (NEW)

```yaml
name: Release & Publish

on:
  push:
    tags: ['v*']  # e.g., v1.2.0

env:
  ARTIFACT_SERVER: ${{ secrets.ARTIFACT_SERVER }}
  ARTIFACT_USER: ${{ secrets.ARTIFACT_USER }}
  ARTIFACT_PASS: ${{ secrets.ARTIFACT_PASS }}

jobs:
  release:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v3
        with:
          fetch-depth: 0
      
      - name: Parse version from tag
        id: version
        run: |
          TAG=${{ github.ref }}
          VERSION=${TAG#refs/tags/v}
          echo "version=$VERSION" >> $GITHUB_OUTPUT
          echo "tag=$TAG" >> $GITHUB_OUTPUT
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 7.4
      
      - name: Run tests
        run: |
          composer install --prefer-dist
          ./vendor/bin/phpunit
      
      - name: Build release packages
        run: |
          mkdir -p releases/
          for plugin in themisdb-support-portal themisdb-order-request themisdb-license-manager; do
            zip -r releases/$plugin-${{ steps.version.outputs.version }}.zip $plugin/ \
              -x "$plugin/.*" "$plugin/*.lock" "$plugin/tests/*"
          done
      
      - name: Generate checksums
        run: |
          cd releases/
          sha256sum *.zip > SHA256SUMS
          cat SHA256SUMS
      
      - name: Create GitHub Release
        uses: actions/create-release@v1
        env:
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
        with:
          tag_name: v${{ steps.version.outputs.version }}
          release_name: Release v${{ steps.version.outputs.version }}
          body: |
            ## Changes
            
            See CHANGELOG.md for detailed changes.
            
            ## Installation
            
            Download plugin ZIP and extract to wp-content/plugins/
            
            ## Security
            
            SHA256 Checksums:
            ```
            $(cat releases/SHA256SUMS)
            ```
          draft: false
          prerelease: false
      
      - name: Upload artifacts to GitHub Release
        uses: softprops/action-gh-release@v1
        with:
          files: |
            releases/*.zip
            releases/SHA256SUMS
      
      - name: Upload to artifact server
        run: |
          for file in releases/*.zip; do
            echo "Uploading $file to artifact server..."
            curl -X POST \
              -H "Authorization: Bearer ${{ secrets.ARTIFACT_TOKEN }}" \
              -F "file=@$file" \
              -F "version=${{ steps.version.outputs.version }}" \
              https://${{ env.ARTIFACT_SERVER }}/api/upload
          done
      
      - name: Update plugin update manifest
        run: |
          curl -X POST \
            -H "Authorization: Bearer ${{ secrets.MANIFEST_TOKEN }}" \
            -H "Content-Type: application/json" \
            -d '{
              "plugins": {
                "themisdb-support-portal/themisdb-support-portal.php": {
                  "version": "${{ steps.version.outputs.version }}",
                  "download_url": "https://releases.themisdb.org/themisdb-support-portal-${{ steps.version.outputs.version }}.zip",
                  "changelog": "See https://github.com/makr-code/wordpressPlugins/releases/tag/v${{ steps.version.outputs.version }}"
                }
              }
            }' \
            https://updates.themisdb.org/api/manifest
      
      - name: Notify Slack
        uses: slackapi/slack-github-action@v1.24.0
        if: always()
        with:
          webhook-url: ${{ secrets.SLACK_WEBHOOK }}
          payload: |
            {
              "text": "Release v${{ steps.version.outputs.version }} deployed successfully! 🚀",
              "blocks": [
                {
                  "type": "section",
                  "text": {
                    "type": "mrkdwn",
                    "text": "*Release Published*\nVersion: `${{ steps.version.outputs.version }}`\nTag: `${{ steps.version.outputs.tag }}`"
                  }
                }
              ]
            }
```

---

## 3. Artifact Server Setup

### 3.1 Artifact Storage Architecture

**Infrastructure:** S3-compatible or self-hosted

```
artifact-server.themisdb.org/
├── releases/
│   ├── v1.1.0/
│   │   ├── themisdb-support-portal-1.1.0.zip
│   │   ├── themisdb-order-request-1.1.0.zip
│   │   ├── themisdb-license-manager-1.1.0.zip
│   │   └── SHA256SUMS
│   ├── v1.2.0/
│   │   ├── (...)
│   └── latest/
│       ├── themisdb-support-portal.zip → v1.2.0
│       ├── themisdb-order-request.zip → v1.2.0
│       └── (symlinks to latest)
├── api/
│   ├── /manifest.json (Update-Check-Endpoint)
│   ├── /upload (GitHub Actions Upload)
│   └── /verify (License Validation)
└── stats/
    ├── downloads.csv
    ├── active-versions.csv
    └── upgrade-paths.json
```

### 3.2 Artifact Server Endpoint

**File:** `artifact-server/api/manifest.php` (NEW)

```php
<?php
/**
 * Plugin Update Manifest API
 * 
 * Called by WordPress plugins to check for updates.
 * Validates license before releasing download links.
 */

// REST API endpoint for update checking
add_action('rest_api_init', function() {
    register_rest_route('themisdb/v1', '/update-check', array(
        'methods'             => 'POST',
        'callback'            => 'themisdb_update_check',
        'permission_callback' => '__return_true',  // License validated in callback
    ));
});

function themisdb_update_check($request) {
    $params = $request->get_json_params();
    
    // Validate request
    $plugin_slug = sanitize_text_field($params['plugin'] ?? '');
    $current_version = sanitize_text_field($params['version'] ?? '');
    $license_key = sanitize_text_field($params['license'] ?? '');
    $site_url = sanitize_url($params['site_url'] ?? '');
    
    if (!$plugin_slug || !$current_version || !$license_key) {
        return new WP_Error(
            'invalid_request',
            'Missing required parameters',
            array('status' => 400)
        );
    }
    
    // Validate license
    $license_valid = validate_license_for_update($license_key, $site_url);
    if (!$license_valid) {
        return new WP_Error(
            'invalid_license',
            'License invalid or expired',
            array('status' => 403)
        );
    }
    
    // Get latest version info
    $latest_version = get_latest_version($plugin_slug);
    
    if (!$latest_version) {
        return new WP_REST_Response(
            array('update_available' => false),
            200
        );
    }
    
    // Compare versions
    if (version_compare($current_version, $latest_version['version'], '>=')) {
        return new WP_REST_Response(
            array('update_available' => false),
            200
        );
    }
    
    // Return update info
    return new WP_REST_Response(
        array(
            'update_available' => true,
            'version' => $latest_version['version'],
            'download_url' => $latest_version['download_url'],
            'changelog' => $latest_version['changelog'],
            'requires_php' => $latest_version['requires_php'],
            'requires_wp' => $latest_version['requires_wp'],
            'tested_up_to' => $latest_version['tested_up_to'],
            'package' => $latest_version['package_url'],  // Direct download
        ),
        200
    );
}

/**
 * Validate license for update eligibility
 */
function validate_license_for_update($license_key, $site_url) {
    // Query license database
    $license = get_license_by_key($license_key);
    
    if (!$license) {
        return false;
    }
    
    // Check expiration
    if (strtotime($license->expiry_date) < time()) {
        return false;
    }
    
    // Check site registration (optional - can be one license per site)
    // if ($license->site_url !== $site_url && !$license->unlimited_sites) {
    //     return false;
    // }
    
    // Log download for analytics
    log_update_download(array(
        'license_id' => $license->id,
        'plugin' => $_REQUEST['plugin'],
        'from_version' => $_REQUEST['version'],
        'site_url' => $site_url,
        'timestamp' => current_time('mysql', true),
    ));
    
    return true;
}

/**
 * Get latest version info from database or cache
 */
function get_latest_version($plugin_slug) {
    $cache_key = 'themisdb_latest_version_' . $plugin_slug;
    $cached = get_transient($cache_key);
    
    if ($cached) {
        return $cached;
    }
    
    // Query from releases table
    global $wpdb;
    $release = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}themisdb_releases 
         WHERE plugin_slug = %s 
         AND status = 'published'
         ORDER BY version DESC 
         LIMIT 1",
        $plugin_slug
    ));
    
    if (!$release) {
        return null;
    }
    
    $result = array(
        'version' => $release->version,
        'download_url' => $release->download_url,
        'package_url' => $release->package_url,
        'changelog' => $release->changelog,
        'requires_php' => $release->requires_php,
        'requires_wp' => $release->requires_wp,
        'tested_up_to' => $release->tested_up_to,
    );
    
    // Cache for 1 hour
    set_transient($cache_key, $result, HOUR_IN_SECONDS);
    
    return $result;
}

/**
 * Log update download for analytics
 */
function log_update_download($data) {
    global $wpdb;
    
    $wpdb->insert(
        $wpdb->prefix . 'themisdb_download_log',
        array(
            'license_id' => $data['license_id'],
            'plugin' => $data['plugin'],
            'from_version' => $data['from_version'],
            'site_url' => $data['site_url'],
            'timestamp' => $data['timestamp'],
        ),
        array('%d', '%s', '%s', '%s', '%s')
    );
}
```

---

## 4. WordPress Plugin Updater Integration

### 4.1 Update Checker Class

**File:** `includes/class-plugin-updater.php` (EXISTING - ENHANCE)

```php
<?php
/**
 * Plugin Updater with License Validation
 * 
 * Hooks into WordPress update mechanism to check for new plugin versions.
 */

class ThemisDB_Plugin_Updater {
    private $plugin_file;
    private $plugin_slug;
    private $version;
    private $update_api_url = 'https://updates.themisdb.org/api/update-check';
    
    public function __construct($plugin_file, $plugin_slug, $version) {
        $this->plugin_file = $plugin_file;
        $this->plugin_slug = $plugin_slug;
        $this->version = $version;
        
        // Hook into update checks
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_updates'));
        add_filter('plugins_api', array($this, 'plugin_info_api'), 10, 3);
        add_action('admin_init', array($this, 'admin_notices'));
    }
    
    /**
     * Check for plugin updates
     */
    public function check_for_updates($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }
        
        // Get license key (from options, license table, etc.)
        $license_key = $this->get_license_key();
        
        if (!$license_key) {
            // No license, skip update check
            return $transient;
        }
        
        // Make update check request
        $update_data = $this->get_remote_update_data($license_key);
        
        if ($update_data && $update_data['update_available']) {
            // Create update object
            $plugin_basename = plugin_basename($this->plugin_file);
            
            $transient->response[$plugin_basename] = (object) array(
                'id' => $update_data['version'],
                'slug' => $this->plugin_slug,
                'plugin' => $plugin_basename,
                'new_version' => $update_data['version'],
                'url' => 'https://github.com/makr-code/wordpressPlugins',
                'package' => $update_data['download_url'],
                'upgrade_notice' => isset($update_data['upgrade_notice']) ? $update_data['upgrade_notice'] : '',
                'requires' => $update_data['requires_wp'],
                'requires_php' => $update_data['requires_php'],
                'tested' => $update_data['tested_up_to'],
                'icons' => array(
                    '1x' => 'https://github.com/makr-code/wordpressPlugins/raw/main/icon-128x128.png',
                    '2x' => 'https://github.com/makr-code/wordpressPlugins/raw/main/icon-256x256.png',
                ),
            );
        }
        
        return $transient;
    }
    
    /**
     * Get plugin information from API
     */
    public function plugin_info_api($res, $action, $args) {
        if (isset($args->slug) && $args->slug === $this->plugin_slug) {
            $license_key = $this->get_license_key();
            
            if (!$license_key) {
                return $res;
            }
            
            $plugin_info = $this->get_remote_plugin_info($license_key);
            
            if ($plugin_info) {
                $res = (object) $plugin_info;
            }
        }
        
        return $res;
    }
    
    /**
     * Make remote API call to check for updates
     */
    private function get_remote_update_data($license_key) {
        $transient_key = 'themisdb_update_' . $this->plugin_slug;
        $cached = get_transient($transient_key);
        
        if ($cached) {
            return $cached;
        }
        
        $response = wp_remote_post($this->update_api_url, array(
            'timeout' => 10,
            'sslverify' => apply_filters('https_local_ssl_verify', false),
            'body' => array(
                'plugin' => $this->plugin_slug,
                'version' => $this->version,
                'license' => $license_key,
                'site_url' => site_url(),
                'wp_version' => get_bloginfo('version'),
            ),
        ));
        
        if (is_wp_error($response)) {
            do_action('themisdb_update_check_failed', $response);
            return null;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data) {
            return null;
        }
        
        // Cache for 12 hours
        set_transient($transient_key, $data, 12 * HOUR_IN_SECONDS);
        
        return $data;
    }
    
    /**
     * Get plugin information
     */
    private function get_remote_plugin_info($license_key) {
        $transient_key = 'themisdb_plugin_info_' . $this->plugin_slug;
        $cached = get_transient($transient_key);
        
        if ($cached) {
            return $cached;
        }
        
        $response = wp_remote_get(
            'https://updates.themisdb.org/api/plugin-info?plugin=' . 
            urlencode($this->plugin_slug) .
            '&license=' . urlencode($license_key),
            array('timeout' => 10)
        );
        
        if (is_wp_error($response)) {
            return null;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($data) {
            set_transient($transient_key, $data, 24 * HOUR_IN_SECONDS);
        }
        
        return $data;
    }
    
    /**
     * Get license key for this site
     */
    private function get_license_key() {
        // Try multiple sources
        
        // 1. From options
        $license = get_option('themisdb_license_key');
        if ($license) {
            return $license;
        }
        
        // 2. From license file (if uploaded)
        $license_file = WP_CONTENT_DIR . '/themisdb-license.json';
        if (file_exists($license_file)) {
            $license_data = json_decode(file_get_contents($license_file), true);
            if (isset($license_data['key'])) {
                return $license_data['key'];
            }
        }
        
        // 3. From WordPress options (license manager plugin)
        if (function_exists('themisdb_get_license_key')) {
            return themisdb_get_license_key();
        }
        
        return null;
    }
    
    /**
     * Show admin notices for update status
     */
    public function admin_notices() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Check if license is about to expire
        $license = $this->get_license_info();
        
        if ($license && strtotime($license['expiry_date']) < strtotime('+30 days')) {
            $days = ceil((strtotime($license['expiry_date']) - time()) / DAY_IN_SECONDS);
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong>ThemisDB License Expiring:</strong>
                    Your license will expire in <?php echo esc_html($days); ?> days.
                    <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-license')); ?>">
                        Renew now
                    </a>
                </p>
            </div>
            <?php
        }
    }
}
```

---

## 5. Customer Download Portal

### 5.1 License-Gated Download Page

**File:** `page-content/customer-downloads.php` (NEW)

```php
<?php
/**
 * Customer Download Portal
 * 
 * Shortcode: [themisdb_customer_downloads]
 * 
 * Allows customers to download plugins based on valid license.
 */

class ThemisDB_Customer_Downloads {
    public static function init() {
        add_shortcode('themisdb_customer_downloads', array(self::class, 'render_portal'));
    }
    
    public static function render_portal() {
        // Require login
        if (!is_user_logged_in()) {
            return '<div class="themisdb-alert alert-warning">' .
                   'Please <a href="' . wp_login_url(get_permalink()) . '">log in</a> to download plugins.' .
                   '</div>';
        }
        
        $user_id = get_current_user_id();
        $licenses = self::get_user_licenses($user_id);
        
        if (empty($licenses)) {
            return '<div class="themisdb-alert alert-info">' .
                   'No active licenses found. <a href="' . esc_url(home_url('/shop')) . '">Purchase a license</a>' .
                   '</div>';
        }
        
        ob_start();
        ?>
        <div class="themisdb-downloads-portal">
            <h2>Plugin Downloads</h2>
            <p>Download plugins based on your active licenses.</p>
            
            <div class="licenses-list">
                <?php foreach ($licenses as $license): ?>
                    <div class="license-card">
                        <h3><?php echo esc_html($license['product_name']); ?></h3>
                        <p><strong>License:</strong> <?php echo esc_html($license['license_key']); ?></p>
                        <p><strong>Expires:</strong> <?php echo esc_html(date('Y-m-d', strtotime($license['expiry_date']))); ?></p>
                        
                        <div class="downloads">
                            <h4>Available Downloads:</h4>
                            <ul>
                                <?php foreach (self::get_license_downloads($license['id']) as $download): ?>
                                    <li>
                                        <a href="<?php echo esc_url($download['download_url']); ?>" 
                                           class="button button-primary"
                                           download>
                                            <?php echo esc_html($download['name']); ?> 
                                            (v<?php echo esc_html($download['version']); ?>)
                                        </a>
                                        <small>
                                            <a href="#" class="show-changelog">[Changelog]</a>
                                        </small>
                                        <div class="changelog" style="display:none;">
                                            <?php echo wp_kses_post($download['changelog']); ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="download-history">
                <h3>Recent Downloads</h3>
                <?php self::render_download_history($user_id); ?>
            </div>
        </div>
        
        <style>
            .themisdb-downloads-portal {
                padding: 20px;
            }
            
            .license-card {
                border: 1px solid #ddd;
                padding: 15px;
                margin-bottom: 15px;
                border-radius: 5px;
                background: #f9f9f9;
            }
            
            .license-card h3 {
                margin-top: 0;
            }
            
            .downloads ul {
                list-style: none;
                padding: 0;
            }
            
            .downloads li {
                padding: 10px;
                background: white;
                margin-bottom: 10px;
                border-left: 3px solid #0073aa;
            }
            
            .changelog {
                margin-top: 10px;
                padding: 10px;
                background: #f0f0f0;
                border-radius: 3px;
                font-size: 12px;
            }
        </style>
        
        <script>
            jQuery(document).ready(function() {
                jQuery('.show-changelog').on('click', function(e) {
                    e.preventDefault();
                    jQuery(this).closest('li').find('.changelog').slideToggle();
                });
            });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get licenses for user
     */
    private static function get_user_licenses($user_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'themisdb_licenses';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, p.post_title as product_name
             FROM $table l
             JOIN {$wpdb->posts} p ON l.product_id = p.ID
             WHERE l.customer_id = %d 
             AND l.status = 'active'
             AND l.expiry_date > NOW()
             ORDER BY l.expiry_date DESC",
            $user_id
        ), ARRAY_A);
    }
    
    /**
     * Get downloads for license
     */
    private static function get_license_downloads($license_id) {
        global $wpdb;
        
        // Query available products for this license type
        $license = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}themisdb_licenses WHERE id = %d",
            $license_id
        ));
        
        if (!$license) {
            return array();
        }
        
        $releases_table = $wpdb->prefix . 'themisdb_releases';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $releases_table
             WHERE plugin_slug = %s
             AND status = 'published'
             ORDER BY version DESC
             LIMIT 10",
            $license->plugin_slug
        ), ARRAY_A);
    }
    
    /**
     * Render download history
     */
    private static function render_download_history($user_id) {
        global $wpdb;
        
        $downloads = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}themisdb_download_log
             WHERE customer_id = %d
             ORDER BY timestamp DESC
             LIMIT 20",
            $user_id
        ));
        
        if (empty($downloads)) {
            echo '<p>No downloads yet.</p>';
            return;
        }
        
        echo '<table class="wp-list-table widefat">';
        echo '<thead><tr><th>Plugin</th><th>Version</th><th>Downloaded</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($downloads as $download) {
            printf(
                '<tr><td>%s</td><td>%s</td><td>%s</td></tr>',
                esc_html($download->plugin_name),
                esc_html($download->version),
                esc_html(date('Y-m-d H:i', strtotime($download->timestamp)))
            );
        }
        
        echo '</tbody></table>';
    }
}

add_action('init', array('ThemisDB_Customer_Downloads', 'init'));
```

---

## 6. Version Management & Release Tracking

### 6.1 Database Schema for Releases

**File:** `includes/class-database.php` (ADD TABLE)

```php
private static function create_releases_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "
        CREATE TABLE IF NOT EXISTS {$wpdb->prefix}themisdb_releases (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            plugin_slug VARCHAR(100) NOT NULL,
            version VARCHAR(20) NOT NULL,
            status ENUM('draft', 'beta', 'published', 'deprecated') DEFAULT 'draft',
            release_date DATETIME,
            download_url VARCHAR(500),
            package_url VARCHAR(500),
            changelog LONGTEXT,
            requires_php VARCHAR(10),
            requires_wp VARCHAR(10),
            tested_up_to VARCHAR(10),
            release_notes TEXT,
            breaking_changes TINYINT(1) DEFAULT 0,
            security_update TINYINT(1) DEFAULT 0,
            download_count INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            UNIQUE KEY version_unique (plugin_slug, version),
            KEY status_idx (status),
            KEY plugin_idx (plugin_slug),
            KEY release_date_idx (release_date)
        ) $charset_collate;
    ";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

private static function create_download_log_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "
        CREATE TABLE IF NOT EXISTS {$wpdb->prefix}themisdb_download_log (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            license_id BIGINT,
            customer_id BIGINT,
            plugin VARCHAR(100),
            plugin_name VARCHAR(255),
            from_version VARCHAR(20),
            version VARCHAR(20),
            site_url VARCHAR(500),
            ip_address VARCHAR(45),
            user_agent TEXT,
            referer VARCHAR(500),
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            
            KEY license_idx (license_id),
            KEY customer_idx (customer_id),
            KEY plugin_idx (plugin),
            KEY timestamp_idx (timestamp)
        ) $charset_collate;
    ";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
```

---

## 7. Rollback & Version Management

### 7.1 Version Downgrade Support

**File:** `includes/class-rollback-manager.php` (NEW)

```php
<?php
/**
 * Rollback Manager
 * 
 * Allows customers to downgrade to previous plugin versions.
 */

class ThemisDB_Rollback_Manager {
    /**
     * Get available versions for rollback
     */
    public static function get_previous_versions($plugin_slug, $current_version, $limit = 5) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'themisdb_releases';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT id, version, release_date, changelog, breaking_changes
             FROM $table
             WHERE plugin_slug = %s
             AND status IN ('published', 'deprecated')
             AND version != %s
             ORDER BY version DESC
             LIMIT %d",
            $plugin_slug,
            $current_version,
            $limit
        ));
    }
    
    /**
     * Check if rollback is safe
     */
    public static function can_rollback($from_version, $to_version) {
        // Check for breaking changes between versions
        global $wpdb;
        $table = $wpdb->prefix . 'themisdb_releases';
        
        $breaking_releases = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table
             WHERE version > %s AND version < %s
             AND breaking_changes = 1",
            $to_version,
            $from_version
        ));
        
        return $breaking_releases == 0;
    }
    
    /**
     * Request rollback (creates support ticket)
     */
    public static function request_rollback($plugin_slug, $from_version, $to_version, $reason = '') {
        // Create support ticket
        if (function_exists('themisdb_create_support_ticket')) {
            $ticket_id = themisdb_create_support_ticket(array(
                'title' => "Rollback Request: $plugin_slug from $from_version to $to_version",
                'description' => $reason,
                'category' => 'technical',
                'priority' => 'medium',
                'type' => 'request',
            ));
            
            // Log in rollback request table
            global $wpdb;
            $wpdb->insert($wpdb->prefix . 'themisdb_rollback_requests', array(
                'ticket_id' => $ticket_id,
                'plugin_slug' => $plugin_slug,
                'from_version' => $from_version,
                'to_version' => $to_version,
                'user_id' => get_current_user_id(),
                'timestamp' => current_time('mysql', true),
                'status' => 'pending',
            ));
            
            return $ticket_id;
        }
        
        return null;
    }
}
```

---

## 8. Update Strategy & Timeline

### 8.1 Release Types

| Type | Frequency | Break Changes | Example |
|------|-----------|---------------|-----------| 
| **Patch** | As needed | No | 1.2.1 → 1.2.2 (bug fix) |
| **Minor** | Quarterly | No | 1.2.0 → 1.3.0 (new feature) |
| **Major** | Annually | Possible | 1.x.x → 2.0.0 (rewrite) |
| **Security** | ASAP | No | 1.2.3-sec (vulnerability fix) |
| **Beta** | Pre-release | Varies | 2.0.0-beta.1 (testing) |

### 8.2 Upgrade Path Decision Tree

```
Customer Current Version
    ↓
Is update available?
    ├─ No → Check next version in 30 days
    ├─ Yes (Patch/Minor/Security) → Safe Update ✅
    │   └─ Download & Auto-Update
    │
    └─ Yes (Major) → Requires Manual Review
        ├─ Read Changelog
        ├─ Compatibility Check
        ├─ Backup Database
        └─ Manual Update or Support Ticket
```

---

## 9. Statistics & Analytics

### 9.1 Release Analytics Dashboard

```sql
-- Active version distribution
SELECT version, COUNT(*) as active_count
FROM wp_themisdb_licenses
WHERE status = 'active'
GROUP BY version
ORDER BY active_count DESC;

-- Download trends by version
SELECT DATE(timestamp) as download_date, COUNT(*) as downloads
FROM wp_themisdb_download_log
WHERE timestamp > DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY download_date
ORDER BY download_date DESC;

-- Update lag (customers not on latest)
SELECT 
    l.version as current_version,
    (SELECT version FROM wp_themisdb_releases 
     WHERE status = 'published' 
     LIMIT 1) as latest_version,
    COUNT(*) as customer_count
FROM wp_themisdb_licenses l
WHERE status = 'active'
GROUP BY l.version;

-- Upgrade failure rate
SELECT 
    version,
    COUNT(*) as total_attempts,
    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count,
    ROUND(SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) / COUNT(*) * 100, 2) as failure_rate
FROM wp_themisdb_upgrade_log
GROUP BY version;
```

---

## 10. Deployment Checklist

### Before Release
- [ ] All tests passing (unit, integration, E2E)
- [ ] Code review completed
- [ ] Security audit passed
- [ ] Documentation updated
- [ ] Changelog written
- [ ] Database migrations tested
- [ ] Backward compatibility verified

### During Release
- [ ] Create GitHub tag (v1.2.0)
- [ ] GitHub Actions builds artifacts
- [ ] Artifacts uploaded to server
- [ ] Manifest updated
- [ ] Release notes published
- [ ] Customers notified (email)
- [ ] Support team informed

### After Release
- [ ] Monitor error rates
- [ ] Track download statistics
- [ ] Check for rollback requests
- [ ] Gather customer feedback
- [ ] Update support documentation

---

## 11. Disaster Recovery

### 11.1 Emergency Rollback Procedure

If critical issue found:

1. **Immediately:**
   ```bash
   # Mark current release as deprecated
   UPDATE wp_themisdb_releases SET status='deprecated' WHERE version='1.2.0';
   
   # Create hotfix
   git tag v1.2.1-hotfix
   git push --tags
   ```

2. **Notify customers:**
   - Send urgent email: "Critical update available - Please upgrade immediately"
   - Alert via dashboard notification
   - Create support tickets for affected customers

3. **Rollback instructions:**
   - Provide manual rollback steps
   - Offer manual download link if auto-update fails
   - Provide database backup/restore guide

---

## Summary

This system provides:
- ✅ **Automated CI/CD** via GitHub Actions
- ✅ **Secure Downloads** with license validation
- ✅ **Auto-Updates** integrated with WordPress
- ✅ **Customer Portal** for managed downloads
- ✅ **Analytics** for upgrade tracking
- ✅ **Rollback Support** for version issues
- ✅ **Emergency Procedures** for critical issues

**Next Steps:**
1. Set up GitHub Actions secrets (S3 credentials, API tokens)
2. Create artifact server infrastructure
3. Deploy update manifest API
4. Test full workflow (build → upload → customer download → auto-update)
5. Customer communication & training

---

**Status:** Architecture Complete ✅  
**Implementation:** Ready for Development  
**Timeline:** 2-3 weeks setup + deployment
