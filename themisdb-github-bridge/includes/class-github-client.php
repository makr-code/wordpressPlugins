<?php

if (!defined('ABSPATH')) {
    exit;
}

class ThemisDB_GitHub_Client {

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

        $response = wp_remote_post($url, array(
            'timeout' => 25,
            'headers' => array(
                'Accept' => 'application/vnd.github+json',
                'Authorization' => 'Bearer ' . $token,
                'X-GitHub-Api-Version' => '2022-11-28',
                'User-Agent' => 'ThemisDB-GitHub-Bridge/' . THEMISDB_GITHUB_BRIDGE_VERSION,
            ),
            'body' => wp_json_encode(array(
                'title' => sanitize_text_field($title),
                'body' => (string) $body,
                'labels' => array_values(array_unique(array_filter(array_map('sanitize_text_field', (array) $labels)))),
            )),
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $raw_body = (string) wp_remote_retrieve_body($response);
        $payload = json_decode($raw_body, true);

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
