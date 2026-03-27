<?php

if (!defined('ABSPATH')) {
    exit;
}

class ThemisDB_GitHub_Client {

    /**
     * Build base request headers for GitHub API calls.
     *
     * @param string $token Optional token override.
     * @return array
     */
    public static function get_base_headers($token = '') {
        $headers = array(
            'Accept' => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => '2022-11-28',
            'User-Agent' => 'ThemisDB-GitHub-Bridge/' . THEMISDB_GITHUB_BRIDGE_VERSION,
        );

        $resolved_token = trim((string) $token);
        if ('' === $resolved_token) {
            $resolved_token = trim((string) get_option('themisdb_github_bridge_token', ''));
        }

        if ('' !== $resolved_token) {
            $headers['Authorization'] = 'Bearer ' . $resolved_token;
        }

        return $headers;
    }

    /**
     * Execute a GitHub API request.
     *
     * @param string $method HTTP method.
     * @param string $url Full GitHub API URL.
     * @param array  $args Request options.
     * @return array|WP_Error ['status_code' => int, 'body' => string, 'json' => mixed, 'headers' => array]
     */
    public static function request($method, $url, $args = array()) {
        $method = strtoupper((string) $method);
        $url = trim((string) $url);

        if ('' === $url) {
            return new WP_Error('invalid_url', 'GitHub URL fehlt.');
        }

        $request_args = array_merge(array(
            'timeout' => 25,
            'headers' => array(),
        ), (array) $args);

        $token = '';
        if (isset($request_args['token'])) {
            $token = (string) $request_args['token'];
            unset($request_args['token']);
        }

        $base_headers = self::get_base_headers($token);
        $request_args['headers'] = array_merge($base_headers, (array) $request_args['headers']);

        $response = wp_remote_request($url, array_merge($request_args, array('method' => $method)));

        if (is_wp_error($response)) {
            return $response;
        }

        $body = (string) wp_remote_retrieve_body($response);
        $json = null;
        if ('' !== $body) {
            $decoded = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $json = $decoded;
            }
        }

        return array(
            'status_code' => (int) wp_remote_retrieve_response_code($response),
            'body' => $body,
            'json' => $json,
            'headers' => (array) wp_remote_retrieve_headers($response),
        );
    }

    /**
     * Create one GitHub issue.
     *
     * @param string $repository owner/repo
     * @param string $token      GitHub token
     * @param string $title      Issue title
     * @param string $body       Issue body
     * @param array  $labels     Labels list
     * @return array|WP_Error
     */
    public static function create_issue($repository, $token, $title, $body, $labels = array()) {
        $repository = trim((string) $repository);
        $token = trim((string) $token);

        if ($repository === '' || strpos($repository, '/') === false) {
            return new WP_Error('invalid_repository', 'GitHub Repository muss im Format owner/repo angegeben werden.');
        }

        if ($token === '') {
            return new WP_Error('missing_token', 'GitHub Token fehlt.');
        }

        list($owner, $repo) = array_map('trim', explode('/', $repository, 2));
        if ($owner === '' || $repo === '') {
            return new WP_Error('invalid_repository', 'GitHub Repository ist ungueltig.');
        }

        $url = sprintf(
            'https://api.github.com/repos/%s/%s/issues',
            rawurlencode($owner),
            rawurlencode($repo)
        );

        $response = self::request('POST', $url, array(
            'token' => $token,
            'body' => wp_json_encode(array(
                'title' => sanitize_text_field($title),
                'body' => (string) $body,
                'labels' => array_values(array_unique(array_filter(array_map('sanitize_text_field', (array) $labels)))),
            )),
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = (int) ($response['status_code'] ?? 0);
        $raw_body = (string) ($response['body'] ?? '');
        $payload = is_array($response['json']) ? $response['json'] : null;

        if ($status_code < 200 || $status_code >= 300) {
            $message = isset($payload['message']) ? (string) $payload['message'] : $raw_body;
            if ($message === '') {
                $message = 'GitHub API Fehler beim Erstellen des Issues.';
            }
            return new WP_Error('github_api_error', $message, array('status_code' => $status_code));
        }

        return array(
            'issue_number' => isset($payload['number']) ? intval($payload['number']) : 0,
            'issue_url' => isset($payload['html_url']) ? esc_url_raw($payload['html_url']) : '',
            'issue_state' => isset($payload['state']) ? sanitize_key($payload['state']) : 'open',
            'repository' => $repository,
            'response' => is_array($payload) ? $payload : array(),
        );
    }
}
