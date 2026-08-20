<?php

if (!defined('ABSPATH')) {
    exit;
}

class ThemisDB_Support_Customer_Auth {

    public static function authenticate_with_license_file($file_content) {
        $data = json_decode((string) $file_content, true);

        if (!$data || !isset($data['license_key'])) {
            return array(
                'success' => false,
                'error' => __('Ungültiges Lizenzdatei-Format', 'themisdb-support-portal'),
            );
        }

        if (!self::validate_license_key_format((string) $data['license_key'])) {
            return array(
                'success' => false,
                'error' => __('Ungültiger Lizenzschlüssel', 'themisdb-support-portal'),
            );
        }

        if (!empty($data['expiry_date'])) {
            $expiry = strtotime((string) $data['expiry_date']);
            if ($expiry !== false && $expiry < time()) {
                return array(
                    'success' => false,
                    'error' => __('Lizenz ist abgelaufen', 'themisdb-support-portal'),
                );
            }
        }

        if (empty($data['signature']) || !self::verify_license_signature($data)) {
            return array(
                'success' => false,
                'error' => __('Lizenzsignatur ist ungültig', 'themisdb-support-portal'),
            );
        }

        $customer_email = isset($data['customer_email']) ? sanitize_email((string) $data['customer_email']) : '';
        if (empty($customer_email)) {
            return array(
                'success' => false,
                'error' => __('Keine Kunden-E-Mail in der Lizenzdatei gefunden', 'themisdb-support-portal'),
            );
        }

        $payload = array(
            'customer_email' => $customer_email,
            'customer_name' => isset($data['customer_name']) ? sanitize_text_field((string) $data['customer_name']) : '',
            'customer_company' => isset($data['customer_company']) ? sanitize_text_field((string) $data['customer_company']) : '',
            'support_tier' => isset($data['product_edition']) ? sanitize_text_field((string) $data['product_edition']) : '',
            'primary_license_id' => isset($data['license_id']) ? absint($data['license_id']) : null,
        );

        $account = ThemisDB_Support_Customer_Account_Repository::create_or_update_from_license($payload);
        if (!$account) {
            return array(
                'success' => false,
                'error' => __('Kundenkonto konnte nicht angelegt werden', 'themisdb-support-portal'),
            );
        }

        $session = ThemisDB_Support_Customer_Session_Manager::create_session((int) $account['id']);
        if (!$session) {
            return array(
                'success' => false,
                'error' => __('Kundensitzung konnte nicht erstellt werden', 'themisdb-support-portal'),
            );
        }

        ThemisDB_Support_Customer_Account_Repository::touch_last_login((int) $account['id']);

        return array(
            'success' => true,
            'customer_account_id' => (int) $account['id'],
            'session_id' => (int) $session['id'],
        );
    }

    public static function logout_customer() {
        return ThemisDB_Support_Customer_Session_Manager::destroy_session();
    }

    private static function validate_license_key_format($license_key) {
        if (!is_string($license_key) || strpos($license_key, 'THEMIS-') !== 0) {
            return false;
        }

        $parts = explode('-', $license_key);
        if (4 !== count($parts)) {
            return false;
        }

        $valid_tiers = array('COM', 'ENT', 'HYP', 'RES');
        if (!in_array($parts[1], $valid_tiers, true)) {
            return false;
        }

        return 8 === strlen($parts[2]) && 8 === strlen($parts[3]);
    }

    private static function verify_license_signature(array $license_data) {
        global $wpdb;

        if (empty($license_data['license_key']) || empty($license_data['signature'])) {
            return false;
        }

        $license_key = (string) $license_data['license_key'];
        $table = $wpdb->prefix . 'themisdb_licenses';

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

        $data_to_sign = $license['license_key'] . $license['product_edition'] . $license['customer_id'] . $license['created_at'];
        $expected = hash_hmac('sha256', $data_to_sign, wp_salt('auth'));

        return hash_equals($expected, (string) $license_data['signature']);
    }
}