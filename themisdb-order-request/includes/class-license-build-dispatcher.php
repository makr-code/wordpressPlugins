<?php
/**
 * Prepare and dispatch licensed product build requests to GitHub Actions.
 */

if (!defined('ABSPATH')) {
    exit;
}

class ThemisDB_License_Build_Dispatcher {

    /**
     * Return dispatcher settings.
     */
    public static function get_settings() {
        return array(
            'token' => (string) get_option('themisdb_license_build_github_token', ''),
            'repository' => trim((string) get_option('themisdb_license_build_github_repository', '')),
            'dispatch_mode' => (string) get_option('themisdb_license_build_dispatch_mode', 'repository_dispatch'),
            'workflow_id' => trim((string) get_option('themisdb_license_build_workflow_id', '')),
            'target_ref' => trim((string) get_option('themisdb_license_build_target_ref', 'main')),
            'event_type' => trim((string) get_option('themisdb_license_build_event_type', 'licensed-product-build')),
        );
    }

    /**
     * Build a normalized payload from a license.
     */
    public static function build_payload_from_license($license_id) {
        $license = ThemisDB_License_Manager::get_license($license_id);

        if (!$license) {
            return false;
        }

        $order = ThemisDB_Order_Manager::get_order($license['order_id']);
        $contract = ThemisDB_Contract_Manager::get_contract($license['contract_id']);
        $contract_data = ($contract && !empty($contract['contract_data']) && is_array($contract['contract_data']))
            ? $contract['contract_data']
            : array();

        $modules = array();
        if (!empty($contract_data['modules']) && is_array($contract_data['modules'])) {
            $modules = $contract_data['modules'];
        } elseif ($order && !empty($order['modules']) && is_array($order['modules'])) {
            $modules = $order['modules'];
        }

        $training_modules = array();
        if (!empty($contract_data['training_modules']) && is_array($contract_data['training_modules'])) {
            $training_modules = $contract_data['training_modules'];
        } elseif ($order && !empty($order['training_modules']) && is_array($order['training_modules'])) {
            $training_modules = $order['training_modules'];
        }

        $payload = array(
            'schema_version' => '1.0',
            'event_source' => 'themisdb-order-request',
            'generated_at' => gmdate('c'),
            'license' => array(
                'id' => intval($license['id']),
                'license_key' => (string) $license['license_key'],
                'product_edition' => (string) $license['product_edition'],
                'license_type' => (string) $license['license_type'],
                'license_status' => (string) $license['license_status'],
                'customer_id' => intval($license['customer_id']),
                'contract_id' => intval($license['contract_id']),
                'order_id' => intval($license['order_id']),
                'max_nodes' => intval($license['max_nodes']),
                'max_cores' => intval($license['max_cores']),
                'max_storage_gb' => intval($license['max_storage_gb']),
                'activation_date' => !empty($license['activation_date']) ? (string) $license['activation_date'] : '',
                'expiry_date' => !empty($license['expiry_date']) ? (string) $license['expiry_date'] : '',
            ),
            'order' => array(
                'id' => $order ? intval($order['id']) : 0,
                'order_number' => $order ? (string) $order['order_number'] : '',
                'status' => $order ? (string) $order['status'] : '',
                'customer_name' => $order ? (string) $order['customer_name'] : '',
                'customer_email' => $order ? (string) $order['customer_email'] : '',
                'customer_company' => $order ? (string) ($order['customer_company'] ?? '') : '',
                'currency' => $order ? (string) ($order['currency'] ?? 'EUR') : 'EUR',
                'total_amount' => $order ? floatval($order['total_amount']) : 0,
            ),
            'contract' => array(
                'id' => $contract ? intval($contract['id']) : 0,
                'contract_number' => $contract ? (string) $contract['contract_number'] : '',
                'status' => $contract ? (string) $contract['status'] : '',
                'contract_type' => $contract ? (string) $contract['contract_type'] : '',
                'valid_from' => $contract ? (string) ($contract['valid_from'] ?? '') : '',
                'valid_until' => $contract ? (string) ($contract['valid_until'] ?? '') : '',
            ),
            'build' => array(
                'product_family' => 'themisdb',
                'channel' => 'licensed',
                'package_slug' => sanitize_title('themisdb-' . strtolower((string) $license['product_edition']) . '-licensed'),
                'edition' => (string) $license['product_edition'],
                'modules' => array_values($modules),
                'training_modules' => array_values($training_modules),
                'entitlements' => array(
                    'max_nodes' => intval($license['max_nodes']),
                    'max_cores' => intval($license['max_cores']),
                    'max_storage_gb' => intval($license['max_storage_gb']),
                ),
            ),
        );

        return $payload;
    }

    /**
     * Return a JSON preview for admin/UI.
     */
    public static function get_payload_json($license_id) {
        $payload = self::build_payload_from_license($license_id);

        if (!$payload) {
            return '';
        }

        return wp_json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Dispatch a build request for the given license.
     */
    public static function dispatch_for_license($license_id, $mode_override = '') {
        $payload = self::build_payload_from_license($license_id);
        if (!$payload) {
            return array(
                'success' => false,
                'message' => 'License payload could not be generated.'
            );
        }

        $settings = self::get_settings();
        if ($mode_override !== '') {
            $settings['dispatch_mode'] = $mode_override;
        }

        if ($settings['repository'] === '' || $settings['token'] === '') {
            return array(
                'success' => false,
                'message' => 'GitHub repository or token is not configured.'
            );
        }

        if ($settings['dispatch_mode'] === 'workflow_dispatch' && $settings['workflow_id'] === '') {
            return array(
                'success' => false,
                'message' => 'Workflow file or workflow ID is not configured.'
            );
        }

        $response = ($settings['dispatch_mode'] === 'workflow_dispatch')
            ? self::dispatch_workflow($settings, $payload)
            : self::dispatch_repository_event($settings, $payload);

        self::record_dispatch_result($license_id, $settings, $payload, $response);

        return $response;
    }

    /**
     * Send repository_dispatch to GitHub.
     */
    private static function dispatch_repository_event($settings, $payload) {
        $url = 'https://api.github.com/repos/' . rawurlencode($settings['repository']) . '/dispatches';

        $body = array(
            'event_type' => $settings['event_type'] !== '' ? $settings['event_type'] : 'licensed-product-build',
            'client_payload' => $payload,
        );

        return self::perform_dispatch_request($url, $settings['token'], $body);
    }

    /**
     * Send workflow_dispatch to GitHub.
     */
    private static function dispatch_workflow($settings, $payload) {
        $workflow_id = rawurlencode($settings['workflow_id']);
        $url = 'https://api.github.com/repos/' . rawurlencode($settings['repository']) . '/actions/workflows/' . $workflow_id . '/dispatches';

        $body = array(
            'ref' => $settings['target_ref'] !== '' ? $settings['target_ref'] : 'main',
            'inputs' => self::build_workflow_inputs($payload),
        );

        return self::perform_dispatch_request($url, $settings['token'], $body);
    }

    /**
     * Create workflow_dispatch inputs from the normalized payload.
     */
    private static function build_workflow_inputs($payload) {
        return array(
            'license_id' => (string) $payload['license']['id'],
            'license_key' => (string) $payload['license']['license_key'],
            'product_edition' => (string) $payload['license']['product_edition'],
            'license_type' => (string) $payload['license']['license_type'],
            'customer_id' => (string) $payload['license']['customer_id'],
            'contract_id' => (string) $payload['license']['contract_id'],
            'order_id' => (string) $payload['license']['order_id'],
            'package_channel' => (string) $payload['build']['channel'],
            'package_slug' => (string) $payload['build']['package_slug'],
            'license_payload_base64' => base64_encode(wp_json_encode($payload)),
        );
    }

    /**
     * Execute the GitHub API request.
     */
    private static function perform_dispatch_request($url, $token, $body) {
        $response = wp_remote_post($url, array(
            'timeout' => 20,
            'headers' => array(
                'Accept' => 'application/vnd.github+json',
                'Authorization' => 'Bearer ' . $token,
                'X-GitHub-Api-Version' => '2022-11-28',
                'User-Agent' => 'ThemisDB-Order-Request/' . (defined('THEMISDB_ORDER_VERSION') ? THEMISDB_ORDER_VERSION : '1.0.0'),
            ),
            'body' => wp_json_encode($body),
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message(),
            );
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($status_code < 200 || $status_code >= 300) {
            return array(
                'success' => false,
                'status_code' => intval($status_code),
                'message' => $response_body !== '' ? $response_body : 'GitHub dispatch failed.',
            );
        }

        return array(
            'success' => true,
            'status_code' => intval($status_code),
            'message' => 'GitHub dispatch accepted.',
        );
    }

    /**
     * Persist dispatch history on the license.
     */
    private static function record_dispatch_result($license_id, $settings, $payload, $response) {
        global $wpdb;

        $table_licenses = $wpdb->prefix . 'themisdb_licenses';
        $license = ThemisDB_License_Manager::get_license($license_id);

        if (!$license) {
            return;
        }

        $usage_data = !empty($license['usage_data']) && is_array($license['usage_data'])
            ? $license['usage_data']
            : array();

        $history = isset($usage_data['ci_cd_dispatches']) && is_array($usage_data['ci_cd_dispatches'])
            ? $usage_data['ci_cd_dispatches']
            : array();

        $history[] = array(
            'timestamp' => current_time('mysql'),
            'mode' => $settings['dispatch_mode'],
            'repository' => $settings['repository'],
            'workflow_id' => $settings['workflow_id'],
            'event_type' => $settings['event_type'],
            'success' => !empty($response['success']),
            'status_code' => isset($response['status_code']) ? intval($response['status_code']) : 0,
            'message' => isset($response['message']) ? sanitize_textarea_field($response['message']) : '',
            'package_slug' => isset($payload['build']['package_slug']) ? sanitize_text_field($payload['build']['package_slug']) : '',
        );

        $usage_data['ci_cd_dispatches'] = array_slice($history, -10);

        $wpdb->update(
            $table_licenses,
            array('usage_data' => wp_json_encode($usage_data)),
            array('id' => intval($license_id)),
            array('%s'),
            array('%d')
        );
    }
}