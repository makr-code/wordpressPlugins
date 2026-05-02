<?php

if (!defined('ABSPATH')) {
    exit;
}

class ThemisDB_DB_Backup_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_post_themisdb_db_backup_create', array($this, 'handle_create_backup'));
        add_action('admin_post_themisdb_db_backup_download', array($this, 'handle_download_backup'));
        add_action('admin_post_themisdb_db_backup_save_settings', array($this, 'handle_save_settings'));
        add_action('admin_post_themisdb_db_backup_verify', array($this, 'handle_verify')); 
    }

    public function add_menu() {
        add_menu_page(
            __('ThemisDB Backup', 'themisdb-db-backup'),
            __('ThemisDB Backup', 'themisdb-db-backup'),
            'manage_options',
            'themisdb-db-backup',
            array($this, 'render_page'),
            'dashicons-database-export',
            58
        );
    }

    public function handle_create_backup() {
        $this->guard_request('themisdb_db_backup_create');

        $result = ThemisDB_DB_Backup_Service::create_backup('manual');

        if (is_wp_error($result)) {
            $this->redirect_with_notice('error', $result->get_error_message());
        }

        $this->redirect_with_notice('success', 'Backup erfolgreich erstellt: ' . $result['zip_filename']);
    }

    public function handle_download_backup() {
        $this->guard_request('themisdb_db_backup_download');

        $file = isset($_GET['file']) ? sanitize_file_name(wp_unslash($_GET['file'])) : '';
        if ($file === '') {
            wp_die('Dateiname fehlt.');
        }

        $path = trailingslashit(ThemisDB_DB_Backup_Service::get_storage_dir()) . $file;

        if (!file_exists($path) || !is_readable($path)) {
            wp_die('Backup-Datei nicht gefunden.');
        }

        nocache_headers();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    public function handle_save_settings() {
        $this->guard_request('themisdb_db_backup_save_settings');

        $enabled = isset($_POST['auto_enabled']) ? 1 : 0;
        $schedule = isset($_POST['schedule']) ? sanitize_text_field(wp_unslash($_POST['schedule'])) : 'daily';
        $retention = isset($_POST['retention']) ? (int) $_POST['retention'] : 120;

        if (!in_array($schedule, array('hourly', 'twicedaily', 'daily'), true)) {
            $schedule = 'daily';
        }

        if ($retention < 5) {
            $retention = 5;
        }

        update_option('themisdb_db_backup_auto_enabled', $enabled);
        update_option('themisdb_db_backup_schedule', $schedule);
        update_option('themisdb_db_backup_retention', $retention);

        themisdb_db_backup_refresh_schedule();

        $this->redirect_with_notice('success', 'Einstellungen gespeichert.');
    }

    public function handle_verify() {
        $this->guard_request('themisdb_db_backup_verify');

        $result = ThemisDB_DB_Backup_Service::verify_chain();

        if ($result['ok']) {
            $this->redirect_with_notice('success', 'Integritaetspruefung OK (' . $result['count'] . ' Backups).');
        }

        $this->redirect_with_notice('error', 'Integritaetspruefung fehlgeschlagen: ' . implode(' | ', $result['issues']));
    }

    private function guard_request($action) {
        if (!current_user_can('manage_options')) {
            wp_die('Keine Berechtigung.');
        }

        check_admin_referer($action);
    }

    private function redirect_with_notice($type, $message) {
        $url = add_query_arg(
            array(
                'page' => 'themisdb-db-backup',
                'notice_type' => $type,
                'notice_msg' => $message,
            ),
            admin_url('admin.php')
        );

        wp_safe_redirect($url);
        exit;
    }

    public function render_page() {
        $backups = ThemisDB_DB_Backup_Service::list_backups();
        $last_error = get_transient('themisdb_db_backup_last_error');

        $notice_type = isset($_GET['notice_type']) ? sanitize_text_field(wp_unslash($_GET['notice_type'])) : '';
        $notice_msg = isset($_GET['notice_msg']) ? sanitize_text_field(wp_unslash($_GET['notice_msg'])) : '';

        $auto_enabled = (int) get_option('themisdb_db_backup_auto_enabled', 1);
        $schedule = (string) get_option('themisdb_db_backup_schedule', 'daily');
        $retention = (int) get_option('themisdb_db_backup_retention', 120);

        echo '<div class="wrap">';
        echo '<h1>ThemisDB DB Backup</h1>';
        echo '<p>Revisionssichere Datenbank-Backups als ZIP mit SHA256-Hash-Chain und Signatur.</p>';

        if ($notice_msg !== '') {
            $class = $notice_type === 'success' ? 'notice notice-success' : 'notice notice-error';
            echo '<div class="' . esc_attr($class) . '"><p>' . esc_html($notice_msg) . '</p></div>';
        }

        if (!empty($last_error)) {
            echo '<div class="notice notice-warning"><p><strong>Letzter Cron-Fehler:</strong> ' . esc_html($last_error) . '</p></div>';
        }

        echo '<h2>Backup erstellen</h2>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('themisdb_db_backup_create');
        echo '<input type="hidden" name="action" value="themisdb_db_backup_create" />';
        submit_button('Jetzt Backup erstellen', 'primary', 'submit', false);
        echo '</form>';

        echo '<hr />';
        echo '<h2>Einstellungen</h2>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('themisdb_db_backup_save_settings');
        echo '<input type="hidden" name="action" value="themisdb_db_backup_save_settings" />';

        echo '<table class="form-table" role="presentation">';
        echo '<tr><th scope="row">Automatische Backups</th><td>';
        echo '<label><input type="checkbox" name="auto_enabled" value="1" ' . checked(1, $auto_enabled, false) . ' /> Aktiv</label>';
        echo '</td></tr>';

        echo '<tr><th scope="row">Intervall</th><td>';
        echo '<select name="schedule">';
        echo '<option value="hourly" ' . selected($schedule, 'hourly', false) . '>Stündlich</option>';
        echo '<option value="twicedaily" ' . selected($schedule, 'twicedaily', false) . '>2x täglich</option>';
        echo '<option value="daily" ' . selected($schedule, 'daily', false) . '>Täglich</option>';
        echo '</select>';
        echo '</td></tr>';

        echo '<tr><th scope="row">Retention</th><td>';
        echo '<input type="number" min="5" step="1" name="retention" value="' . esc_attr((string) $retention) . '" />';
        echo '<p class="description">Wie viele Backups maximal behalten werden.</p>';
        echo '</td></tr>';
        echo '</table>';

        submit_button('Einstellungen speichern');
        echo '</form>';

        echo '<hr />';
        echo '<h2>Integritaet pruefen</h2>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('themisdb_db_backup_verify');
        echo '<input type="hidden" name="action" value="themisdb_db_backup_verify" />';
        submit_button('Hash-Chain verifizieren', 'secondary', 'submit', false);
        echo '</form>';

        echo '<hr />';
        echo '<h2>Backups</h2>';

        if (empty($backups)) {
            echo '<p>Noch keine Backups vorhanden.</p>';
            echo '</div>';
            return;
        }

        echo '<table class="widefat striped">';
        echo '<thead><tr>';
        echo '<th>Datei</th><th>Erstellt (UTC)</th><th>Groesse</th><th>SHA256</th><th>Trigger</th><th>Aktion</th>';
        echo '</tr></thead><tbody>';

        foreach ($backups as $entry) {
            $file = isset($entry['zip_filename']) ? (string) $entry['zip_filename'] : '';
            $created = isset($entry['created_at_utc']) ? (string) $entry['created_at_utc'] : '';
            $size = isset($entry['zip_size_bytes']) ? (int) $entry['zip_size_bytes'] : 0;
            $sha = isset($entry['zip_sha256']) ? (string) $entry['zip_sha256'] : '';
            $trigger = isset($entry['trigger']) ? (string) $entry['trigger'] : '';

            $download_url = wp_nonce_url(
                add_query_arg(
                    array(
                        'action' => 'themisdb_db_backup_download',
                        'file' => $file,
                    ),
                    admin_url('admin-post.php')
                ),
                'themisdb_db_backup_download'
            );

            echo '<tr>';
            echo '<td><code>' . esc_html($file) . '</code></td>';
            echo '<td>' . esc_html($created) . '</td>';
            echo '<td>' . esc_html(size_format($size)) . '</td>';
            echo '<td><code style="font-size:11px;">' . esc_html(substr($sha, 0, 24)) . '...</code></td>';
            echo '<td>' . esc_html($trigger) . '</td>';
            echo '<td><a class="button button-small" href="' . esc_url($download_url) . '">Download</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
    }
}
