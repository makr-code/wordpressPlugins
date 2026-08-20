<?php

if (!defined('ABSPATH')) {
    exit;
}

class ThemisDB_Support_Customer_Context {

    public static function is_customer_authenticated() {
        return null !== self::current_customer();
    }

    public static function require_customer_or_null() {
        return self::current_customer();
    }

    public static function current_customer() {
        $mode = (string) get_option('themisdb_support_customer_auth_mode', 'wp_user');

        if ('customer_session' === $mode) {
            return self::resolve_customer_from_session();
        }

        return self::resolve_customer_from_wp_user();
    }

    public static function resolve_customer_from_session() {
        $session = ThemisDB_Support_Customer_Session_Manager::resolve_session_from_request();
        if (!$session || empty($session['customer_account_id'])) {
            return null;
        }

        $account = ThemisDB_Support_Customer_Account_Repository::find_by_id((int) $session['customer_account_id']);
        if (!$account) {
            return null;
        }

        $account['session'] = $session;

        return $account;
    }

    public static function resolve_customer_from_wp_user() {
        if (!is_user_logged_in() || current_user_can('manage_options')) {
            return null;
        }

        $user = wp_get_current_user();
        if (!$user || empty($user->ID)) {
            return null;
        }

        $account = ThemisDB_Support_Customer_Account_Repository::find_by_email($user->user_email);
        if ($account) {
            return $account;
        }

        $license_id = (int) get_user_meta((int) $user->ID, 'themisdb_license_id', true);
        if ($license_id > 0 && class_exists('ThemisDB_License_Manager')) {
            $license = ThemisDB_License_Manager::get_license($license_id);
            if ($license && !empty($license['customer_email'])) {
                $payload = array(
                    'customer_email' => $license['customer_email'],
                    'customer_name' => isset($license['customer_name']) ? $license['customer_name'] : $user->display_name,
                    'customer_company' => isset($license['customer_company']) ? $license['customer_company'] : '',
                    'support_tier' => isset($license['product_edition']) ? $license['product_edition'] : '',
                    'primary_license_id' => $license_id,
                );

                $account = ThemisDB_Support_Customer_Account_Repository::create_or_update_from_license($payload);
                if ($account) {
                    return $account;
                }
            }
        }

        return null;
    }
}