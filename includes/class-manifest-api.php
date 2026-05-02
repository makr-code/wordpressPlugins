<?php
/**
 * ThemisDB Plugin Update Manifest API
 * 
 * REST API endpoints for:
 * - Update checking
 * - Plugin information
 * - License validation
 * - Download logging
 * 
 * Endpoints:
 * POST /api/update-check
 * GET  /api/plugin-info
 * POST /api/manifest
 * GET  /api/releases
 */

// Register REST API routes
add_action('rest_api_init', function() {
    // Update check endpoint
    register_rest_route('themisdb/v1', '/update-check', array(
        'methods' => array('GET', 'POST'),
        'callback' => 'themisdb_api_update_check',
        'permission_callback' => '__return_true',
        'args' => array(
            'plugin' => array(
                'type' => 'string',
                'required' => true,
                'description' => 'Plugin slug (e.g., themisdb-order-request)',
            ),
            'version' => array(
                'type' => 'string',
                'required' => true,
                'description' => 'Current plugin version (e.g., 1.1.0)',
            ),
            'license' => array(
                'type' => 'string',
                'required' => true,
                'description' => 'License key',
            ),
        ),
    ));
    
    // Plugin info endpoint
    register_rest_route('themisdb/v1', '/plugin-info', array(
        'methods' => 'GET',
        'callback' => 'themisdb_api_plugin_info',
        'permission_callback' => '__return_true',
        'args' => array(
            'plugin' => array(
                'type' => 'string',
                'required' => true,
            ),
        ),
    ));
    
    // Release list endpoint
    register_rest_route('themisdb/v1', '/releases', array(
        'methods' => 'GET',
        'callback' => 'themisdb_api_releases',
        'permission_callback' => '__return_true',
        'args' => array(
            'plugin' => array(
                'type' => 'string',
                'required' => false,
            ),
            'limit' => array(
                'type' => 'integer',
                'required' => false,
                'default' => 10,
            ),
        ),
    ));
    
    // Manifest update endpoint (admin only)
    register_rest_route('themisdb/v1', '/manifest', array(
        'methods' => 'POST',
        'callback' => 'themisdb_api_update_manifest',
        'permission_callback' => 'themisdb_api_check_admin',
        'args' => array(
            'plugins' => array(
                'type' => 'object',
                'required' => true,
            ),
        ),
    ));
});

/**
 * Update check endpoint
 * 
 * Validates license and returns latest version info
 */
function themisdb_api_update_check($request) {
    $params = $request->get_params();
    
    // Validate input
    $plugin = sanitize_text_field($params['plugin'] ?? '');
    $version = sanitize_text_field($params['version'] ?? '');
    $license_key = sanitize_text_field($params['license'] ?? '');
    $site_url = sanitize_url($params['site_url'] ?? home_url());
    
    // Log request
    do_action('themisdb_api_update_check_requested', $plugin, $version, $license_key);
    
    // Validate license
    if (!validate_themisdb_license($license_key)) {
        return new WP_REST_Response(
            array(
                'success' => false,
                'message' => 'Invalid or expired license',
            ),
            403
        );
    }
    
    // Get latest release
    $latest = get_latest_release($plugin);
    
    if (!$latest) {
        return new WP_REST_Response(
            array(
                'success' => false,
                'message' => 'Plugin not found',
            ),
            404
        );
    }
    
    // Compare versions
    $update_available = version_compare($version, $latest->version, '<');
    
    if (!$update_available) {
        return new WP_REST_Response(
            array(
                'success' => true,
                'update_available' => false,
                'current_version' => $version,
                'latest_version' => $latest->version,
            ),
            200
        );
    }
    
    // Log download request
    log_download_request(array(
        'license_key' => $license_key,
        'plugin' => $plugin,
        'from_version' => $version,
        'to_version' => $latest->version,
        'site_url' => $site_url,
    ));
    
    // Return update info
    return new WP_REST_Response(
        array(
            'success' => true,
            'update_available' => true,
            'current_version' => $version,
            'latest_version' => $latest->version,
            'package' => $latest->download_url,
            'download_url' => $latest->download_url,
            'requires_php' => $latest->requires_php,
            'requires_wp' => $latest->requires_wp,
            'tested_up_to' => $latest->tested_up_to,
            'changelog' => $latest->changelog,
            'breaking_changes' => (bool) $latest->breaking_changes,
            'security_update' => (bool) $latest->security_update,
        ),
        200
    );
}

/**
 * Plugin information endpoint
 * 
 * Returns detailed plugin information
 */
function themisdb_api_plugin_info($request) {
    $params = $request->get_params();
    $plugin = sanitize_text_field($params['plugin'] ?? '');
    
    // Get plugin info from database or cache
    $info = get_plugin_info_cached($plugin);
    
    if (!$info) {
        return new WP_Error(
            'plugin_not_found',
            'Plugin not found',
            array('status' => 404)
        );
    }
    
    return new WP_REST_Response($info, 200);
}

/**
 * Releases endpoint
 * 
 * List available releases
 */
function themisdb_api_releases($request) {
    $params = $request->get_params();
    $plugin = sanitize_text_field($params['plugin'] ?? '');
    $limit = intval($params['limit'] ?? 10);
    $limit = min($limit, 100);  // Max 100
    
    global $wpdb;
    $table = $wpdb->prefix . 'themisdb_releases';
    
    $query = "
        SELECT * FROM $table
        WHERE status = 'published'
    ";
    $bindings = array();
    
    if ($plugin) {
        $query .= " AND plugin_slug = %s";
        $bindings[] = $plugin;
    }
    
    $query .= " ORDER BY release_date DESC LIMIT %d";
    $bindings[] = $limit;
    
    $releases = $wpdb->get_results(
        $wpdb->prepare($query, ...$bindings),
        ARRAY_A
    );
    
    return new WP_REST_Response(
        array(
            'success' => true,
            'count' => count($releases),
            'releases' => $releases,
        ),
        200
    );
}

/**
 * Update manifest endpoint (admin only)
 * 
 * Allows updating plugin version info
 */
function themisdb_api_update_manifest($request) {
    $params = $request->get_json_params();
    $plugins = $params['plugins'] ?? array();
    
    if (empty($plugins)) {
        return new WP_Error(
            'invalid_payload',
            'No plugins provided',
            array('status' => 400)
        );
    }
    
    $results = array();
    
    foreach ($plugins as $plugin_file => $plugin_data) {
        // Parse plugin file to get slug
        $slug = dirname($plugin_file);
        
        // Update or create release
        $result = update_plugin_release(
            $slug,
            $plugin_data['version'],
            $plugin_data
        );
        
        $results[] = array(
            'plugin' => $slug,
            'status' => $result ? 'success' : 'failed',
        );
    }
    
    return new WP_REST_Response(
        array(
            'success' => true,
            'updated_count' => count($results),
            'results' => $results,
        ),
        200
    );
}

/**
 * Helper functions
 */

function validate_themisdb_license($license_key) {
    // Query license database
    $license = get_license_by_key($license_key);
    
    if (!$license) {
        return false;
    }
    
    // Check expiration
    if (strtotime($license['expiry_date']) < time()) {
        return false;
    }
    
    // Check if active
    if ($license['status'] !== 'active') {
        return false;
    }
    
    return true;
}

function get_license_by_key($key) {
    global $wpdb;
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}themisdb_licenses WHERE license_key = %s",
        $key
    ), ARRAY_A);
}

function get_latest_release($plugin_slug) {
    global $wpdb;
    $table = $wpdb->prefix . 'themisdb_releases';
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table
         WHERE plugin_slug = %s AND status = 'published'
         ORDER BY version DESC LIMIT 1",
        $plugin_slug
    ));
}

function get_plugin_info_cached($plugin_slug) {
    $cache_key = 'themisdb_plugin_info_' . $plugin_slug;
    $cached = get_transient($cache_key);
    
    if ($cached) {
        return $cached;
    }
    
    // Get from database
    global $wpdb;
    $table = $wpdb->prefix . 'themisdb_plugins';
    
    $info = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE slug = %s",
        $plugin_slug
    ), ARRAY_A);
    
    if ($info) {
        set_transient($cache_key, $info, 24 * HOUR_IN_SECONDS);
    }
    
    return $info;
}

function log_download_request($data) {
    global $wpdb;
    
    $wpdb->insert(
        $wpdb->prefix . 'themisdb_download_log',
        array(
            'license_key' => $data['license_key'] ?? '',
            'plugin' => $data['plugin'],
            'from_version' => $data['from_version'],
            'to_version' => $data['to_version'],
            'site_url' => $data['site_url'] ?? '',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            'timestamp' => current_time('mysql', true),
        ),
        array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
    );
}

function update_plugin_release($plugin_slug, $version, $data) {
    global $wpdb;
    $table = $wpdb->prefix . 'themisdb_releases';
    
    // Check if version exists
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM $table WHERE plugin_slug = %s AND version = %s",
        $plugin_slug,
        $version
    ));
    
    $release_data = array(
        'plugin_slug' => $plugin_slug,
        'version' => $version,
        'status' => $data['status'] ?? 'published',
        'download_url' => $data['download_url'] ?? '',
        'changelog' => $data['changelog'] ?? '',
        'requires_php' => $data['requires_php'] ?? '7.4',
        'requires_wp' => $data['requires_wp'] ?? '5.0',
        'tested_up_to' => $data['tested_up_to'] ?? '',
        'breaking_changes' => $data['breaking_changes'] ?? 0,
        'security_update' => $data['security_update'] ?? 0,
    );
    
    if ($existing) {
        // Update
        return $wpdb->update(
            $table,
            $release_data,
            array('id' => $existing->id)
        );
    } else {
        // Insert
        $release_data['release_date'] = current_time('mysql', true);
        return $wpdb->insert($table, $release_data);
    }
}

function themisdb_api_check_admin() {
    // Check if user has proper authorization
    if (!is_user_logged_in() && !current_user_can('manage_options')) {
        // Allow Bearer token authentication
        $auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (strpos($auth_header, 'Bearer ') !== 0) {
            return false;
        }
        
        $token = substr($auth_header, 7);
        $valid_token = get_option('themisdb_api_manifest_token');
        
        return hash_equals($valid_token, $token);
    }
    
    return true;
}

/**
 * Example API usage:
 * 
 * Check for updates:
 * POST /wp-json/themisdb/v1/update-check
 * {
 *   "plugin": "themisdb-order-request",
 *   "version": "1.1.0",
 *   "license": "license-key-12345"
 * }
 * 
 * Get plugin info:
 * GET /wp-json/themisdb/v1/plugin-info?plugin=themisdb-order-request
 * 
 * List releases:
 * GET /wp-json/themisdb/v1/releases?plugin=themisdb-order-request&limit=10
 * 
 * Update manifest (admin):
 * POST /wp-json/themisdb/v1/manifest
 * Authorization: Bearer token
 * {
 *   "plugins": {
 *     "themisdb-order-request/themisdb-order-request.php": {
 *       "version": "1.2.0",
 *       "download_url": "https://...",
 *       "changelog": "..."
 *     }
 *   }
 * }
 */
