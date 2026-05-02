<?php
/**
 * ThemisDB Plugin Updater
 * 
 * Handles automatic plugin updates with license validation
 * 
 * Integration in main plugin file:
 * 
 * require_once dirname(__FILE__) . '/includes/class-plugin-updater.php';
 * new ThemisDB_Plugin_Updater(
 *     __FILE__,
 *     'themisdb-order-request',
 *     '1.2.0',
 *     'https://updates.themisdb.org/api/update-check'
 * );
 */

class ThemisDB_Plugin_Updater {
    private $plugin_file;
    private $plugin_slug;
    private $version;
    private $update_api_url;
    
    /**
     * Initialize updater
     */
    public function __construct($plugin_file, $plugin_slug, $version, $api_url = null) {
        $this->plugin_file = $plugin_file;
        $this->plugin_slug = $plugin_slug;
        $this->version = $version;
        $this->update_api_url = $api_url ?? 'https://updates.themisdb.org/api/update-check';
        
        // Hook into update checks
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_updates'));
        add_filter('plugins_api', array($this, 'plugin_info_api'), 10, 3);
        add_filter('plugin_row_meta', array($this, 'plugin_row_meta'), 10, 2);
        add_action('admin_init', array($this, 'admin_notices'));
        
        // Custom update handler
        add_filter('upgrader_pre_install', array($this, 'pre_install_check'), 10, 2);
        add_filter('upgrader_post_install', array($this, 'post_install_check'), 10, 3);
    }
    
    /**
     * Check for plugin updates
     */
    public function check_for_updates($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }
        
        // Get license key
        $license_key = $this->get_license_key();
        
        if (!$license_key) {
            // No license, no updates
            return $transient;
        }
        
        // Check for updates
        $update_data = $this->get_remote_update_data($license_key);
        
        if ($update_data && isset($update_data['update_available']) && $update_data['update_available']) {
            $plugin_basename = plugin_basename($this->plugin_file);
            
            $transient->response[$plugin_basename] = (object) array(
                'id' => $update_data['version'],
                'slug' => $this->plugin_slug,
                'plugin' => $plugin_basename,
                'new_version' => $update_data['version'],
                'url' => 'https://github.com/makr-code/wordpressPlugins',
                'package' => $update_data['download_url'] ?? $update_data['package'] ?? '',
                'requires' => $update_data['requires_wp'] ?? '5.0',
                'requires_php' => $update_data['requires_php'] ?? '7.4',
                'tested' => $update_data['tested_up_to'] ?? '6.4',
                'upgrade_notice' => isset($update_data['upgrade_notice']) ? $update_data['upgrade_notice'] : '',
            );
            
            // Log update availability
            $this->log_update_check(true, $update_data['version']);
        } else {
            $this->log_update_check(false, $this->version);
        }
        
        return $transient;
    }
    
    /**
     * Get plugin information for modal
     */
    public function plugin_info_api($res, $action, $args) {
        if (!isset($args->slug) || $args->slug !== $this->plugin_slug) {
            return $res;
        }
        
        $license_key = $this->get_license_key();
        
        if (!$license_key) {
            return $res;
        }
        
        $response = wp_remote_get(
            add_query_arg(array(
                'action' => 'plugin_info',
                'plugin' => $this->plugin_slug,
                'license' => $license_key,
            ), $this->update_api_url),
            array(
                'timeout' => 10,
                'sslverify' => apply_filters('https_local_ssl_verify', false),
            )
        );
        
        if (is_wp_error($response)) {
            return $res;
        }
        
        $plugin_info = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($plugin_info) {
            $res = (object) $plugin_info;
        }
        
        return $res;
    }
    
    /**
     * Add license info to plugin row
     */
    public function plugin_row_meta($plugin_meta, $plugin_file) {
        if (plugin_basename($this->plugin_file) !== $plugin_file) {
            return $plugin_meta;
        }
        
        $license = $this->get_license_info();
        
        if ($license) {
            if (strtotime($license['expiry_date']) < time()) {
                $plugin_meta[] = '<span style="color: red;">🔒 License Expired</span>';
            } elseif (strtotime($license['expiry_date']) < strtotime('+30 days')) {
                $days = ceil((strtotime($license['expiry_date']) - time()) / DAY_IN_SECONDS);
                $plugin_meta[] = sprintf(
                    '<span style="color: orange;">⚠️ Expires in %d days</span>',
                    $days
                );
            } else {
                $plugin_meta[] = '✅ License Active';
            }
        }
        
        return $plugin_meta;
    }
    
    /**
     * Pre-installation checks
     */
    public function pre_install_check($result, $package) {
        // Verify update package
        if (strpos($package, $this->plugin_slug) === false) {
            return $result;
        }
        
        // Could add additional checks here
        return $result;
    }
    
    /**
     * Post-installation checks
     */
    public function post_install_check($result, $hook_extra, $result_data) {
        // Verify installation
        if (!isset($result_data['destination_name']) || strpos($result_data['destination_name'], $this->plugin_slug) === false) {
            return $result;
        }
        
        // Log successful update
        $this->log_update_installation($result_data['destination_name'] ?? 'unknown');
        
        return $result;
    }
    
    /**
     * Show admin notices
     */
    public function admin_notices() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Check license status
        $license = $this->get_license_info();
        
        if (!$license) {
            if (wp_verify_nonce($_GET['_nonce'] ?? '', 'themisdb_license_notice')) {
                return;  // Dismissed
            }
            ?>
            <div class="notice notice-warning is-dismissible" id="themisdb-license-notice">
                <p>
                    <strong>📄 ThemisDB License Required:</strong>
                    This plugin requires a valid license for automatic updates.
                    <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-license')); ?>">
                        Manage License
                    </a>
                    or
                    <a href="<?php echo esc_url(home_url('/shop')); ?>">
                        Purchase License
                    </a>
                </p>
            </div>
            <?php
        } elseif (strtotime($license['expiry_date']) < strtotime('+30 days')) {
            $days = ceil((strtotime($license['expiry_date']) - time()) / DAY_IN_SECONDS);
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong>⏰ ThemisDB License Expiring:</strong>
                    Your license will expire in <?php echo esc_html($days); ?> days.
                    <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-license')); ?>">
                        Renew Now
                    </a>
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * Get remote update data from API
     */
    private function get_remote_update_data($license_key) {
        $transient_key = 'themisdb_update_' . sanitize_key($this->plugin_slug);
        $cached = get_transient($transient_key);
        
        if ($cached) {
            return $cached;
        }
        
        $response = wp_remote_post($this->update_api_url, array(
            'timeout' => 10,
            'sslverify' => apply_filters('https_local_ssl_verify', false),
            'body' => array(
                'action' => 'check_update',
                'plugin' => $this->plugin_slug,
                'version' => $this->version,
                'license' => $license_key,
                'site_url' => site_url(),
                'wp_version' => get_bloginfo('version'),
            ),
        ));
        
        if (is_wp_error($response)) {
            do_action('themisdb_update_check_error', $response, $this->plugin_slug);
            return null;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($data) {
            // Cache for 12 hours
            set_transient($transient_key, $data, 12 * HOUR_IN_SECONDS);
        }
        
        return $data;
    }
    
    /**
     * Get license key from various sources
     */
    private function get_license_key() {
        // 1. Try plugin options
        $license = get_option('themisdb_license_key');
        if ($license) {
            return $license;
        }
        
        // 2. Try license file
        $license_file = WP_CONTENT_DIR . '/themisdb-license.json';
        if (file_exists($license_file)) {
            $data = json_decode(file_get_contents($license_file), true);
            if (isset($data['key'])) {
                return $data['key'];
            }
        }
        
        // 3. Try custom function hook
        $license = apply_filters('themisdb_get_license_key', null);
        if ($license) {
            return $license;
        }
        
        return null;
    }
    
    /**
     * Get license info
     */
    private function get_license_info() {
        $license_key = $this->get_license_key();
        
        if (!$license_key) {
            return null;
        }
        
        // Try to get license info from database or cache
        $transient_key = 'themisdb_license_info_' . sanitize_key($license_key);
        $cached = get_transient($transient_key);
        
        if ($cached) {
            return $cached;
        }
        
        // Could query API for license info
        // For now, return basic structure
        return array(
            'key' => $license_key,
            'expiry_date' => get_option('themisdb_license_expiry'),
        );
    }
    
    /**
     * Log update check for analytics
     */
    private function log_update_check($update_available, $version) {
        if (!apply_filters('themisdb_log_update_checks', false)) {
            return;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'themisdb_update_check_log';
        
        // Create table if not exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return;
        }
        
        $wpdb->insert(
            $table,
            array(
                'plugin' => $this->plugin_slug,
                'current_version' => $this->version,
                'latest_version' => $version,
                'update_available' => $update_available ? 1 : 0,
                'timestamp' => current_time('mysql', true),
            ),
            array('%s', '%s', '%s', '%d', '%s')
        );
    }
    
    /**
     * Log successful update installation
     */
    private function log_update_installation($destination) {
        if (!apply_filters('themisdb_log_installations', false)) {
            return;
        }
        
        // Log for analytics
        error_log(sprintf(
            '[ThemisDB] Updated %s to version %s at %s',
            $this->plugin_slug,
            $this->version,
            current_time('mysql', true)
        ));
    }
}
