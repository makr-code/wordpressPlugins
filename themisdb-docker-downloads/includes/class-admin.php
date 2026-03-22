<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB - Hybrid Database System                                   ║
╠═════════════════════════════════════════════════════════════════════╣
  File:            class-admin.php                                    ║
  Version:         0.0.2                                              ║
  Last Modified:   2026-03-09 04:08:17                                ║
  Author:          unknown                                            ║
╠═════════════════════════════════════════════════════════════════════╣
  Quality Metrics:                                                    ║
    • Maturity Level:  🟢 PRODUCTION-READY                             ║
    • Quality Score:   100.0/100                                      ║
    • Total Lines:     301                                            ║
    • Open Issues:     TODOs: 0, Stubs: 0                             ║
╠═════════════════════════════════════════════════════════════════════╣
  Revision History:                                                   ║
    • 2a1fb0423  2026-03-03  Merge branch 'develop' into copilot/audit-src-module-docu... ║
    • 9d3ecaa0e  2026-02-28  Add ThemisDB Wiki Integration plugin with documentation i... ║
╠═════════════════════════════════════════════════════════════════════╣
  Status: ✅ Production Ready                                          ║
╚═════════════════════════════════════════════════════════════════════╝
 */


/**
 * Admin Panel Handler
 * Handles the WordPress admin interface for the plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class ThemisDB_Docker_Downloads_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_themisdb_docker_test_connection', array($this, 'ajax_test_connection'));
        add_action('wp_ajax_themisdb_docker_clear_cache', array($this, 'ajax_clear_cache'));
    }
    
    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_options_page(
            'ThemisDB Docker Downloads Einstellungen',
            'ThemisDB Docker',
            'manage_options',
            'themisdb-docker-downloads',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('themisdb_docker_downloads_settings', 'themisdb_docker_namespace');
        register_setting('themisdb_docker_downloads_settings', 'themisdb_docker_repository');
        register_setting('themisdb_docker_downloads_settings', 'themisdb_docker_cache_duration');
        register_setting('themisdb_docker_downloads_settings', 'themisdb_docker_token');
        register_setting('themisdb_docker_downloads_settings', 'themisdb_docker_tags_count');
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'settings_page_themisdb-docker-downloads') {
            return;
        }
        
        wp_enqueue_style(
            'themisdb-docker-downloads-admin',
            THEMISDB_DOCKER_DOWNLOADS_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            THEMISDB_DOCKER_DOWNLOADS_VERSION
        );
        
        wp_enqueue_script(
            'themisdb-docker-downloads-admin',
            THEMISDB_DOCKER_DOWNLOADS_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            THEMISDB_DOCKER_DOWNLOADS_VERSION,
            true
        );
        
        wp_localize_script('themisdb-docker-downloads-admin', 'themisdbDockerAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('themisdb_docker_admin_nonce')
        ));
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $page_slug = 'themisdb-docker-downloads';
        $active_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'settings';
        $allowed_tabs = array('settings', 'tools', 'shortcodes');

        if (!in_array($active_tab, $allowed_tabs, true)) {
            $active_tab = 'settings';
        }

        $tab_url = static function ($tab) use ($page_slug) {
            return admin_url('options-general.php?page=' . $page_slug . '&tab=' . $tab);
        };

        $namespace = get_option('themisdb_docker_namespace', 'themisdb');
        $repository = get_option('themisdb_docker_repository', 'themisdb');
        $cache_duration = get_option('themisdb_docker_cache_duration', 3600);
        $tags_count = get_option('themisdb_docker_tags_count', 10);
        $token_set = get_option('themisdb_docker_token', '') !== '';
        
        ?>
        <div class="wrap">
            <style>
                .themisdb-tab-content { background: #fff; border: 1px solid #c3c4c7; border-top: none; padding: 20px 24px; }
                .themisdb-tab-content > :first-child,
                .themisdb-tab-content .themisdb-admin-modules:first-child,
                .themisdb-tab-content .card:first-child,
                .themisdb-tab-content form:first-child { margin-top: 0; }
                .themisdb-admin-modules { display: grid; gap: 20px; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); margin: 0 0 24px; }
                .themisdb-admin-modules .card, .themisdb-tab-content .card { margin: 0; max-width: none; padding: 20px 24px; }
                .themisdb-tab-toolbar { display: flex; gap: 8px; flex-wrap: wrap; margin: 0 0 16px; }
            </style>

            <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
            <a href="<?php echo esc_url($tab_url('settings')); ?>" class="page-title-action">Einstellungen bearbeiten</a>
            <a href="<?php echo esc_url($tab_url('tools')); ?>" class="page-title-action">Tools & Tests</a>
            <a href="<?php echo esc_url($tab_url('shortcodes')); ?>" class="page-title-action">Shortcodes anzeigen</a>
            <hr class="wp-header-end">

            <nav class="nav-tab-wrapper wp-clearfix" aria-label="Docker Downloads Einstellungen">
                <a href="<?php echo esc_url($tab_url('settings')); ?>" class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">Einstellungen</a>
                <a href="<?php echo esc_url($tab_url('tools')); ?>" class="nav-tab <?php echo $active_tab === 'tools' ? 'nav-tab-active' : ''; ?>">Tools & Tests</a>
                <a href="<?php echo esc_url($tab_url('shortcodes')); ?>" class="nav-tab <?php echo $active_tab === 'shortcodes' ? 'nav-tab-active' : ''; ?>">Shortcodes</a>
            </nav>

            <div class="themisdb-tab-content">
                <?php if ($active_tab === 'settings') : ?>
                    <div class="themisdb-admin-modules">
                        <div class="card">
                            <h2>Schnellaktionen</h2>
                            <div class="themisdb-tab-toolbar">
                                <a href="#themisdb-docker-settings-form" class="button button-primary">Zur Konfiguration</a>
                                <a href="<?php echo esc_url($tab_url('tools')); ?>" class="button">Verbindung testen</a>
                            </div>
                            <p>Konfiguriert Namespace, Repository und API-Caching für die Docker-Tag-Ausgabe.</p>
                        </div>
                        <div class="card">
                            <h2>Aktive Defaults</h2>
                            <table class="widefat striped"><tbody>
                                <tr><th>Namespace / Repository</th><td><?php echo esc_html($namespace . '/' . $repository); ?></td></tr>
                                <tr><th>Cache-Dauer</th><td><?php echo esc_html((string) $cache_duration); ?></td></tr>
                                <tr><th>Tag-Limit</th><td><?php echo esc_html((string) $tags_count); ?></td></tr>
                                <tr><th>Token</th><td><?php echo esc_html($token_set ? 'Hinterlegt' : 'Nicht hinterlegt'); ?></td></tr>
                            </tbody></table>
                        </div>
                    </div>

                    <form id="themisdb-docker-settings-form" method="post" action="options.php">
                        <?php settings_fields('themisdb_docker_downloads_settings'); ?>
                        <table class="form-table">
                            <tr><th scope="row"><label for="themisdb_docker_namespace">Docker Hub Namespace</label></th><td><input type="text" id="themisdb_docker_namespace" name="themisdb_docker_namespace" value="<?php echo esc_attr($namespace); ?>" class="regular-text"><p class="description">Der Namespace auf Docker Hub (z.B. "themisdb" für themisdb/themisdb)</p></td></tr>
                            <tr><th scope="row"><label for="themisdb_docker_repository">Docker Repository Name</label></th><td><input type="text" id="themisdb_docker_repository" name="themisdb_docker_repository" value="<?php echo esc_attr($repository); ?>" class="regular-text"><p class="description">Der Repository-Name auf Docker Hub (z.B. "themisdb" für themisdb/themisdb)</p></td></tr>
                            <tr><th scope="row"><label for="themisdb_docker_cache_duration">Cache Dauer (Sekunden)</label></th><td><input type="number" id="themisdb_docker_cache_duration" name="themisdb_docker_cache_duration" value="<?php echo esc_attr($cache_duration); ?>" min="60" step="60" class="small-text"><p class="description">Wie lange sollen Docker Hub Daten gecacht werden? (Empfohlen: 3600 = 1 Stunde)</p></td></tr>
                            <tr><th scope="row"><label for="themisdb_docker_tags_count">Anzahl Tags</label></th><td><input type="number" id="themisdb_docker_tags_count" name="themisdb_docker_tags_count" value="<?php echo esc_attr($tags_count); ?>" min="1" max="100" class="small-text"><p class="description">Anzahl der anzuzeigenden Docker Tags (1-100)</p></td></tr>
                            <tr><th scope="row"><label for="themisdb_docker_token">Docker Hub Token (Optional)</label></th><td><input type="password" id="themisdb_docker_token" name="themisdb_docker_token" value="<?php echo esc_attr(get_option('themisdb_docker_token', '')); ?>" class="regular-text"><p class="description">Optional: Docker Hub Personal Access Token für höhere Rate Limits</p></td></tr>
                        </table>
                        <?php submit_button('Einstellungen speichern'); ?>
                    </form>
                <?php elseif ($active_tab === 'tools') : ?>
                    <div class="themisdb-admin-modules">
                        <div class="card"><h2>Docker Hub Verbindung testen</h2><button type="button" id="test-dockerhub-connection" class="button button-primary">Verbindung testen</button><span id="connection-test-result"></span></div>
                        <div class="card"><h2>Cache leeren</h2><button type="button" id="clear-cache" class="button button-secondary">Cache leeren</button><p class="description">Löscht alle gecachten Docker Hub Daten</p><span id="clear-cache-result"></span></div>
                    </div>
                <?php else : ?>
                    <div class="card">
                        <h2>Shortcodes</h2>
                        <table class="widefat striped">
                            <tbody>
                                <tr><th scope="row">Neueste Tags anzeigen</th><td><code>[themisdb_docker_tags]</code></td></tr>
                                <tr><th scope="row">Alle Tags anzeigen</th><td><code>[themisdb_docker_tags show="all"]</code></td></tr>
                                <tr><th scope="row">Spezifische Architektur filtern</th><td><code>[themisdb_docker_tags architecture="amd64"]</code><br><code>[themisdb_docker_tags architecture="arm64"]</code></td></tr>
                                <tr><th scope="row">Kompakte Ansicht</th><td><code>[themisdb_docker_tags style="compact"]</code></td></tr>
                                <tr><th scope="row">Tabellen-Ansicht</th><td><code>[themisdb_docker_tags style="table"]</code></td></tr>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX handler to test Docker Hub connection
     */
    public function ajax_test_connection() {
        check_ajax_referer('themisdb_docker_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Keine Berechtigung');
        }
        
        $api = new ThemisDB_Docker_Downloads_DockerHub_API();
        $latest = $api->get_latest_tag();
        
        if (is_wp_error($latest)) {
            wp_send_json_error($latest->get_error_message());
        }
        
        wp_send_json_success('Verbindung erfolgreich! Neuester Tag: ' . $latest['name']);
    }
    
    /**
     * AJAX handler to clear cache
     */
    public function ajax_clear_cache() {
        check_ajax_referer('themisdb_docker_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Keine Berechtigung');
        }
        
        $api = new ThemisDB_Docker_Downloads_DockerHub_API();
        $api->clear_cache();
        
        wp_send_json_success('Cache erfolgreich geleert');
    }
}
