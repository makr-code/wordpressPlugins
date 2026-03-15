<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB - Hybrid Database System                                   ║
╠═════════════════════════════════════════════════════════════════════╣
  File:            class-license-auth.php                             ║
  Plugin:          themisdb-support-portal                            ║
  Version:         1.0.0                                              ║
╚═════════════════════════════════════════════════════════════════════╝
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * License-file authentication gate for the ThemisDB Support Portal.
 *
 * Authentication flow:
 *  1. Customer uploads their .json license file in the portal login form.
 *  2. If the ThemisDB_License_Manager class is available (i.e. the
 *     themisdb-order-request plugin is active), it is used directly for
 *     full validation including expiry, status, and HMAC signature.
 *  3. Otherwise, a standalone verification path is taken:
 *     - License key format is checked (THEMIS-{TIER}-{HASH}-{RANDOM}).
 *     - Expiry date (if present) is checked.
 *     - The HMAC-SHA256 signature is verified against the licenses stored in
 *       the themisdb_licenses database table (created by themisdb-order-request).
 *  4. On success a WordPress user is created (if needed) and logged in.
 */
class ThemisDB_Support_License_Auth {

    public function __construct() {
        // AJAX handlers – available for both logged-in and logged-out users
        add_action('wp_ajax_nopriv_themisdb_support_license_auth', array($this, 'handle_license_auth'));
        add_action('wp_ajax_themisdb_support_license_auth', array($this, 'handle_license_auth'));
        add_action('wp_ajax_nopriv_themisdb_support_logout', array($this, 'handle_logout'));
        add_action('wp_ajax_themisdb_support_logout', array($this, 'handle_logout'));
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Authenticate a customer via the content of a license file.
     *
     * @param string $file_content Raw JSON content of the license file.
     * @return array {
     *     @type bool   $success
     *     @type int    $user_id  (on success)
     *     @type string $error    (on failure)
     * }
     */
    public static function authenticate_with_license_file($file_content) {
        // Delegate to the order-request plugin's manager when available – it
        // handles the full license lifecycle (status, tier limits, audit log).
        if (class_exists('ThemisDB_License_Manager')) {
            return ThemisDB_License_Manager::authenticate_with_license_file($file_content);
        }

        return self::standalone_authenticate($file_content);
    }

    /**
     * Check whether the currently logged-in user holds a valid license.
     * Administrators always pass the check.
     *
     * @return bool
     */
    public static function current_user_has_license() {
        if (!is_user_logged_in()) {
            return false;
        }

        // Administrators always have access to the support portal
        if (current_user_can('manage_options')) {
            return true;
        }

        $user_id = get_current_user_id();

        // Primary check: via themisdb-order-request license manager
        if (class_exists('ThemisDB_License_Manager')) {
            $license_id = get_user_meta($user_id, 'themisdb_license_id', true);
            if ($license_id) {
                $license = ThemisDB_License_Manager::get_license(intval($license_id));
                if ($license && $license['license_status'] === 'active') {
                    return true;
                }
            }
        }

        // Fallback check: license key stored in user meta by this plugin
        $license_key = get_user_meta($user_id, 'themisdb_support_license_key', true);
        if (!empty($license_key)) {
            return true;
        }

        return false;
    }

    // -------------------------------------------------------------------------
    // AJAX Handlers
    // -------------------------------------------------------------------------

    /**
     * Handle license file authentication via AJAX.
     */
    public function handle_license_auth() {
        check_ajax_referer('themisdb_support_nonce', 'nonce');

        $file_content = isset($_POST['license_file_content']) ? wp_unslash($_POST['license_file_content']) : '';

        if (empty($file_content)) {
            wp_send_json_error(array(
                'message' => __('Lizenzdatei ist leer', 'themisdb-support-portal'),
            ));
        }

        $result = self::authenticate_with_license_file($file_content);

        if (empty($result['success'])) {
            wp_send_json_error(array(
                'message' => isset($result['error'])
                    ? $result['error']
                    : __('Authentifizierung fehlgeschlagen', 'themisdb-support-portal'),
            ));
        }

        $redirect = get_option('themisdb_support_redirect_url', home_url('/'));

        wp_send_json_success(array(
            'message'  => __('Erfolgreich mit Lizenz angemeldet!', 'themisdb-support-portal'),
            'redirect' => $redirect,
        ));
    }

    /**
     * Handle logout via AJAX.
     */
    public function handle_logout() {
        check_ajax_referer('themisdb_support_nonce', 'nonce');

        wp_logout();

        wp_send_json_success(array(
            'redirect' => home_url('/'),
        ));
    }

    // -------------------------------------------------------------------------
    // Standalone authentication (no themisdb-order-request plugin)
    // -------------------------------------------------------------------------

    /**
     * Validate and authenticate a license file without relying on
     * ThemisDB_License_Manager.
     *
     * @param string $file_content Raw JSON content.
     * @return array
     */
    private static function standalone_authenticate($file_content) {
        $data = json_decode($file_content, true);

        if (!$data || !isset($data['license_key'])) {
            return array(
                'success' => false,
                'error'   => __('Ungültiges Lizenzdatei-Format', 'themisdb-support-portal'),
            );
        }

        // Validate key format: THEMIS-{TIER}-{HASH}-{RANDOM}
        if (!self::validate_license_key_format($data['license_key'])) {
            return array(
                'success' => false,
                'error'   => __('Ungültiger Lizenzschlüssel', 'themisdb-support-portal'),
            );
        }

        // Check expiry date if present
        if (!empty($data['expiry_date'])) {
            $expiry = strtotime($data['expiry_date']);
            if ($expiry !== false && $expiry < time()) {
                return array(
                    'success' => false,
                    'error'   => __('Lizenz ist abgelaufen', 'themisdb-support-portal'),
                );
            }
        }

        // Signature must be present
        if (empty($data['signature'])) {
            return array(
                'success' => false,
                'error'   => __('Lizenzsignatur fehlt', 'themisdb-support-portal'),
            );
        }

        // Verify HMAC signature using the same algorithm as ThemisDB_License_Manager
        if (!self::verify_license_signature($data)) {
            return array(
                'success' => false,
                'error'   => __('Lizenzsignatur ist ungültig', 'themisdb-support-portal'),
            );
        }

        // Get customer data from the license file
        $customer_email   = isset($data['customer_email']) ? sanitize_email($data['customer_email']) : '';
        $customer_name    = isset($data['customer_name'])  ? sanitize_text_field($data['customer_name'])  : '';
        $customer_company = isset($data['customer_company']) ? sanitize_text_field($data['customer_company']) : '';

        if (empty($customer_email)) {
            return array(
                'success' => false,
                'error'   => __('Keine Kunden-E-Mail in der Lizenzdatei gefunden', 'themisdb-support-portal'),
            );
        }

        $user = self::get_or_create_user(
            $customer_email,
            $customer_name,
            $customer_company,
            sanitize_text_field($data['license_key'])
        );

        if (!$user) {
            return array(
                'success' => false,
                'error'   => __('Benutzeranmeldung fehlgeschlagen', 'themisdb-support-portal'),
            );
        }

        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);

        return array(
            'success' => true,
            'user_id' => $user->ID,
        );
    }

    /**
     * Verify the HMAC-SHA256 signature of a license file against the stored
     * license in the themisdb_licenses table (created by themisdb-order-request).
     *
     * Uses the same signing algorithm as ThemisDB_License_Manager::sign_license_data().
     *
     * @param array $license_data Decoded license file data.
     * @return bool
     */
    private static function verify_license_signature($license_data) {
        global $wpdb;

        $license_key = $license_data['license_key'];
        $table       = $wpdb->prefix . 'themisdb_licenses';

        // Check whether the order-request table exists (use prepare() for LIKE pattern)
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
            return false;
        }

        $license = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM `$table` WHERE license_key = %s", $license_key),
            ARRAY_A
        );

        if (!$license) {
            return false;
        }

        // Replicate ThemisDB_License_Manager::sign_license_data()
        $data_to_sign = $license['license_key']
            . $license['product_edition']
            . $license['customer_id']
            . $license['created_at'];

        $expected = hash_hmac('sha256', $data_to_sign, wp_salt('auth'));

        return hash_equals($expected, $license_data['signature']);
    }

    /**
     * Check whether a license key matches the ThemisDB format.
     * Format: THEMIS-{TIER}-{8_CHAR_HASH}-{8_CHAR_RANDOM}
     *
     * @param string $license_key
     * @return bool
     */
    private static function validate_license_key_format($license_key) {
        if (!is_string($license_key) || strpos($license_key, 'THEMIS-') !== 0) {
            return false;
        }

        $parts = explode('-', $license_key);

        if (count($parts) !== 4) {
            return false;
        }

        $valid_tiers = array('COM', 'ENT', 'HYP', 'RES');
        if (!in_array($parts[1], $valid_tiers, true)) {
            return false;
        }

        if (strlen($parts[2]) !== 8 || strlen($parts[3]) !== 8) {
            return false;
        }

        return true;
    }

    /**
     * Return the existing WordPress user for the given email, or create a new
     * subscriber account and store the associated license key in user meta.
     *
     * @param string $email
     * @param string $name
     * @param string $company
     * @param string $license_key
     * @return WP_User|false
     */
    private static function get_or_create_user($email, $name, $company, $license_key) {
        $user = get_user_by('email', $email);

        if (!$user) {
            // Derive a unique username from the email local part
            $base_username = sanitize_user(explode('@', $email)[0]);
            if (empty($base_username)) {
                $base_username = sanitize_user($email);
            }

            $username = $base_username;
            if (username_exists($username)) {
                $username = $base_username . '_' . substr(md5($email), 0, 6);
            }

            $password = wp_generate_password(20, true, true);
            $user_id  = wp_create_user($username, $password, $email);

            if (is_wp_error($user_id)) {
                return false;
            }

            $user = get_user_by('id', $user_id);

            if (!$user) {
                return false;
            }

            // Store name parts
            $name_parts = explode(' ', $name, 2);
            update_user_meta($user_id, 'first_name', $name_parts[0]);
            if (isset($name_parts[1])) {
                update_user_meta($user_id, 'last_name', $name_parts[1]);
            }
            if (!empty($company)) {
                update_user_meta($user_id, 'company', $company);
            }
        }

        // Always persist / refresh the license key association
        update_user_meta($user->ID, 'themisdb_support_license_key', $license_key);

        return $user;
    }
}
