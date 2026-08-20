<?php

if (!defined('ABSPATH')) {
    exit;
}

class ThemisDB_Support_Customer_Session_Manager {

    const COOKIE_NAME = 'themisdb_support_customer_session';

    public static function init() {
        add_action('themisdb_support_customer_session_gc', array(__CLASS__, 'gc_expired_sessions'));
    }

    public static function table_name() {
        global $wpdb;

        return $wpdb->prefix . 'themisdb_customer_sessions';
    }

    public static function create_session($customer_account_id) {
        global $wpdb;

        $customer_account_id = absint($customer_account_id);
        if ($customer_account_id <= 0) {
            return false;
        }

        $token = wp_generate_password(64, false, false);
        $nonce = wp_generate_password(64, false, false);
        $token_hash = hash('sha256', $token);
        $expires_at = gmdate('Y-m-d H:i:s', time() + (30 * DAY_IN_SECONDS));
        $now = current_time('mysql');

        $inserted = $wpdb->insert(
            self::table_name(),
            array(
                'customer_account_id' => $customer_account_id,
                'session_token_hash' => $token_hash,
                'session_nonce' => $nonce,
                'expires_at' => $expires_at,
                'last_seen_at' => $now,
                'ip_address' => self::get_client_ip(),
                'user_agent' => self::get_user_agent(),
                'created_at' => $now,
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        if (false === $inserted) {
            return false;
        }

        self::set_session_cookie($token, $nonce, $expires_at);

        return array(
            'id' => (int) $wpdb->insert_id,
            'customer_account_id' => $customer_account_id,
            'token' => $token,
            'nonce' => $nonce,
            'expires_at' => $expires_at,
        );
    }

    public static function resolve_session_from_request() {
        global $wpdb;

        $raw = self::get_cookie_value();
        if (empty($raw)) {
            return null;
        }

        $parts = explode('|', $raw, 2);
        if (2 !== count($parts)) {
            return null;
        }

        $token = trim((string) $parts[0]);
        $nonce = trim((string) $parts[1]);
        if ('' === $token || '' === $nonce) {
            return null;
        }

        $token_hash = hash('sha256', $token);
        $table = self::table_name();
        $session = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM `$table` WHERE session_token_hash = %s AND session_nonce = %s LIMIT 1", $token_hash, $nonce),
            ARRAY_A
        );

        if (!$session) {
            return null;
        }

        if (!empty($session['expires_at']) && strtotime((string) $session['expires_at']) !== false && strtotime((string) $session['expires_at']) < time()) {
            self::destroy_session_by_id((int) $session['id']);
            return null;
        }

        $wpdb->update(
            $table,
            array('last_seen_at' => current_time('mysql')),
            array('id' => (int) $session['id']),
            array('%s'),
            array('%d')
        );

        return $session;
    }

    public static function rotate_session($session_id) {
        global $wpdb;

        $session_id = absint($session_id);
        if ($session_id <= 0) {
            return false;
        }

        $session = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM `" . self::table_name() . "` WHERE id = %d LIMIT 1", $session_id),
            ARRAY_A
        );

        if (!$session) {
            return false;
        }

        $token = wp_generate_password(64, false, false);
        $nonce = wp_generate_password(64, false, false);
        $token_hash = hash('sha256', $token);
        $expires_at = !empty($session['expires_at']) ? (string) $session['expires_at'] : gmdate('Y-m-d H:i:s', time() + (30 * DAY_IN_SECONDS));

        $updated = $wpdb->update(
            self::table_name(),
            array(
                'session_token_hash' => $token_hash,
                'session_nonce' => $nonce,
                'expires_at' => $expires_at,
                'last_seen_at' => current_time('mysql'),
            ),
            array('id' => $session_id),
            array('%s', '%s', '%s', '%s'),
            array('%d')
        );

        if (false === $updated) {
            return false;
        }

        self::set_session_cookie($token, $nonce, $expires_at);

        return array(
            'id' => $session_id,
            'customer_account_id' => (int) $session['customer_account_id'],
            'token' => $token,
            'nonce' => $nonce,
            'expires_at' => $expires_at,
        );
    }

    public static function destroy_session() {
        $session = self::resolve_session_from_request();
        if (!$session || empty($session['id'])) {
            self::clear_session_cookie();
            return false;
        }

        $deleted = self::destroy_session_by_id((int) $session['id']);
        self::clear_session_cookie();

        return $deleted;
    }

    public static function destroy_session_by_id($session_id) {
        global $wpdb;

        $session_id = absint($session_id);
        if ($session_id <= 0) {
            return false;
        }

        return false !== $wpdb->delete(
            self::table_name(),
            array('id' => $session_id),
            array('%d')
        );
    }

    public static function gc_expired_sessions() {
        global $wpdb;

        return (int) $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM `" . self::table_name() . "` WHERE expires_at < %s",
                gmdate('Y-m-d H:i:s')
            )
        );
    }

    public static function get_cookie_value() {
        $name = apply_filters('themisdb_support_customer_session_cookie_name', self::COOKIE_NAME);
        return isset($_COOKIE[$name]) ? sanitize_text_field(wp_unslash($_COOKIE[$name])) : '';
    }

    public static function set_session_cookie($token, $nonce, $expires_at) {
        $name = apply_filters('themisdb_support_customer_session_cookie_name', self::COOKIE_NAME);
        $expires_timestamp = strtotime((string) $expires_at);
        if (false === $expires_timestamp) {
            $expires_timestamp = time() + (30 * DAY_IN_SECONDS);
        }

        $cookie_value = $token . '|' . $nonce;
        setcookie($name, $cookie_value, array(
            'expires' => $expires_timestamp,
            'path' => defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/',
            'domain' => defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '',
            'secure' => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        ));

        $_COOKIE[$name] = $cookie_value;
    }

    public static function clear_session_cookie() {
        $name = apply_filters('themisdb_support_customer_session_cookie_name', self::COOKIE_NAME);
        setcookie($name, '', array(
            'expires' => time() - DAY_IN_SECONDS,
            'path' => defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/',
            'domain' => defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '',
            'secure' => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        ));

        unset($_COOKIE[$name]);
    }

    private static function get_client_ip() {
        $keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
        foreach ($keys as $key) {
            if (!empty($_SERVER[$key])) {
                return sanitize_text_field(wp_unslash((string) $_SERVER[$key]));
            }
        }

        return '';
    }

    private static function get_user_agent() {
        if (empty($_SERVER['HTTP_USER_AGENT'])) {
            return '';
        }

        return substr(sanitize_text_field(wp_unslash((string) $_SERVER['HTTP_USER_AGENT'])), 0, 255);
    }
}