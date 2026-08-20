<?php

if (!defined('ABSPATH')) {
    exit;
}

class ThemisDB_Support_Customer_Account_Repository {

    public static function table_name() {
        global $wpdb;

        return $wpdb->prefix . 'themisdb_customer_accounts';
    }

    public static function find_by_email($email) {
        global $wpdb;

        $email = sanitize_email((string) $email);
        if (empty($email)) {
            return null;
        }

        $table = self::table_name();
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM `$table` WHERE customer_email = %s LIMIT 1", $email),
            ARRAY_A
        );

        return $row ? $row : null;
    }

    public static function find_by_id($id) {
        global $wpdb;

        $id = absint($id);
        if ($id <= 0) {
            return null;
        }

        $table = self::table_name();
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM `$table` WHERE id = %d LIMIT 1", $id),
            ARRAY_A
        );

        return $row ? $row : null;
    }

    public static function find_by_license_id($license_id) {
        global $wpdb;

        $license_id = absint($license_id);
        if ($license_id <= 0) {
            return null;
        }

        $table = self::table_name();
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM `$table` WHERE primary_license_id = %d LIMIT 1", $license_id),
            ARRAY_A
        );

        return $row ? $row : null;
    }

    public static function create_or_update_from_license(array $license_payload) {
        global $wpdb;

        $email = isset($license_payload['customer_email']) ? sanitize_email((string) $license_payload['customer_email']) : '';
        if (empty($email)) {
            return false;
        }

        $account = self::find_by_email($email);
        $now = current_time('mysql');

        if (!$account) {
            $data = array(
                'customer_uuid' => isset($license_payload['customer_uuid']) && '' !== (string) $license_payload['customer_uuid']
                    ? sanitize_text_field((string) $license_payload['customer_uuid'])
                    : wp_generate_uuid4(),
                'guest_author_id' => array_key_exists('guest_author_id', $license_payload) ? absint($license_payload['guest_author_id']) : null,
                'primary_license_id' => array_key_exists('primary_license_id', $license_payload) ? absint($license_payload['primary_license_id']) : null,
                'customer_email' => $email,
                'customer_name' => isset($license_payload['customer_name']) ? sanitize_text_field((string) $license_payload['customer_name']) : '',
                'customer_company' => isset($license_payload['customer_company']) ? sanitize_text_field((string) $license_payload['customer_company']) : '',
                'support_tier' => isset($license_payload['support_tier']) ? sanitize_text_field((string) $license_payload['support_tier']) : '',
                'account_status' => isset($license_payload['account_status']) ? sanitize_text_field((string) $license_payload['account_status']) : 'active',
                'updated_at' => $now,
                'created_at' => $now,
            );
            $result = $wpdb->insert(self::table_name(), $data, array('%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s'));
            if (false === $result) {
                return false;
            }

            return self::find_by_id((int) $wpdb->insert_id);
        }

        $update_data = array(
            'updated_at' => $now,
        );

        if (array_key_exists('guest_author_id', $license_payload)) {
            $update_data['guest_author_id'] = absint($license_payload['guest_author_id']);
        }
        if (array_key_exists('primary_license_id', $license_payload)) {
            $update_data['primary_license_id'] = absint($license_payload['primary_license_id']);
        }
        if (array_key_exists('customer_name', $license_payload)) {
            $update_data['customer_name'] = sanitize_text_field((string) $license_payload['customer_name']);
        }
        if (array_key_exists('customer_company', $license_payload)) {
            $update_data['customer_company'] = sanitize_text_field((string) $license_payload['customer_company']);
        }
        if (array_key_exists('support_tier', $license_payload)) {
            $update_data['support_tier'] = sanitize_text_field((string) $license_payload['support_tier']);
        }
        if (array_key_exists('account_status', $license_payload)) {
            $update_data['account_status'] = sanitize_text_field((string) $license_payload['account_status']);
        }

        $wpdb->update(
            self::table_name(),
            $update_data,
            array('id' => (int) $account['id']),
            array_fill(0, count($update_data), '%s'),
            array('%d')
        );

        return self::find_by_id((int) $account['id']);
    }

    public static function attach_guest_author($account_id, $guest_author_id) {
        global $wpdb;

        $account_id = absint($account_id);
        $guest_author_id = absint($guest_author_id);

        if ($account_id <= 0 || $guest_author_id <= 0) {
            return false;
        }

        return false !== $wpdb->update(
            self::table_name(),
            array(
                'guest_author_id' => $guest_author_id,
                'updated_at' => current_time('mysql'),
            ),
            array('id' => $account_id),
            array('%d', '%s'),
            array('%d')
        );
    }

    public static function touch_last_login($account_id) {
        global $wpdb;

        $account_id = absint($account_id);
        if ($account_id <= 0) {
            return false;
        }

        return false !== $wpdb->update(
            self::table_name(),
            array(
                'last_login_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ),
            array('id' => $account_id),
            array('%s', '%s'),
            array('%d')
        );
    }
}