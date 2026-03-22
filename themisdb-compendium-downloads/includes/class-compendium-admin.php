<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB - Hybrid Database System                                   ║
╠═════════════════════════════════════════════════════════════════════╣
  File:            class-compendium-admin.php                         ║
  Version:         0.0.2                                              ║
  Last Modified:   2026-03-09 04:08:16                                ║
  Author:          unknown                                            ║
╠═════════════════════════════════════════════════════════════════════╣
  Quality Metrics:                                                    ║
    • Maturity Level:  🟢 PRODUCTION-READY                             ║
    • Quality Score:   100.0/100                                      ║
    • Total Lines:     255                                            ║
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
 * ThemisDB Compendium Admin Settings
 * 
 * Handles admin settings page
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ThemisDB_Compendium_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('ThemisDB Kompendium Downloads', 'themisdb-compendium-downloads'),
            __('Kompendium Downloads', 'themisdb-compendium-downloads'),
            'manage_options',
            'themisdb-compendium-downloads',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('themisdb_compendium_settings', 'themisdb_compendium_github_repo');
        register_setting('themisdb_compendium_settings', 'themisdb_compendium_show_file_sizes');
        register_setting('themisdb_compendium_settings', 'themisdb_compendium_cache_duration');
        register_setting('themisdb_compendium_settings', 'themisdb_compendium_button_style');
        register_setting('themisdb_compendium_settings', 'themisdb_compendium_search_term');
        
        add_settings_section(
            'themisdb_compendium_main_section',
            __('Haupteinstellungen', 'themisdb-compendium-downloads'),
            array($this, 'render_section_info'),
            'themisdb-compendium-downloads'
        );
        
        add_settings_field(
            'themisdb_compendium_github_repo',
            __('GitHub Repository', 'themisdb-compendium-downloads'),
            array($this, 'render_github_repo_field'),
            'themisdb-compendium-downloads',
            'themisdb_compendium_main_section'
        );
        
        add_settings_field(
            'themisdb_compendium_show_file_sizes',
            __('Dateigrößen anzeigen', 'themisdb-compendium-downloads'),
            array($this, 'render_show_file_sizes_field'),
            'themisdb-compendium-downloads',
            'themisdb_compendium_main_section'
        );
        
        add_settings_field(
            'themisdb_compendium_cache_duration',
            __('Cache-Dauer (Sekunden)', 'themisdb-compendium-downloads'),
            array($this, 'render_cache_duration_field'),
            'themisdb-compendium-downloads',
            'themisdb_compendium_main_section'
        );
        
        add_settings_field(
            'themisdb_compendium_button_style',
            __('Button-Stil', 'themisdb-compendium-downloads'),
            array($this, 'render_button_style_field'),
            'themisdb-compendium-downloads',
            'themisdb_compendium_main_section'
        );
        
        add_settings_field(
            'themisdb_compendium_search_term',
            __('Dateiname-Filter', 'themisdb-compendium-downloads'),
            array($this, 'render_search_term_field'),
            'themisdb-compendium-downloads',
            'themisdb_compendium_main_section'
        );
    }
    
    /**
     * Render section info
     */
    public function render_section_info() {
        echo '<p>' . __('Konfigurieren Sie die Einstellungen für die Kompendium-Downloads.', 'themisdb-compendium-downloads') . '</p>';
    }
    
    /**
     * Render GitHub repo field
     */
    public function render_github_repo_field() {
        $value = get_option('themisdb_compendium_github_repo', 'makr-code/wordpressPlugins');
        echo '<input type="text" name="themisdb_compendium_github_repo" value="' . esc_attr($value) . '" class="regular-text">';
        echo '<p class="description">' . __('Format: owner/repository (z.B. makr-code/wordpressPlugins)', 'themisdb-compendium-downloads') . '</p>';
    }
    
    /**
     * Render show file sizes field
     */
    public function render_show_file_sizes_field() {
        $value = get_option('themisdb_compendium_show_file_sizes', 1);
        echo '<label>';
        echo '<input type="checkbox" name="themisdb_compendium_show_file_sizes" value="1" ' . checked($value, 1, false) . '>';
        echo ' ' . __('Dateigröße bei Downloads anzeigen', 'themisdb-compendium-downloads');
        echo '</label>';
    }
    
    /**
     * Render cache duration field
     */
    public function render_cache_duration_field() {
        $value = get_option('themisdb_compendium_cache_duration', 3600);
        echo '<input type="number" name="themisdb_compendium_cache_duration" value="' . esc_attr($value) . '" class="small-text" min="60">';
        echo '<p class="description">' . __('Wie lange sollen Release-Daten zwischengespeichert werden? (Standard: 3600 = 1 Stunde)', 'themisdb-compendium-downloads') . '</p>';
    }
    
    /**
     * Render button style field
     */
    public function render_button_style_field() {
        $value = get_option('themisdb_compendium_button_style', 'modern');
        $styles = array(
            'modern' => __('Modern', 'themisdb-compendium-downloads'),
            'classic' => __('Klassisch', 'themisdb-compendium-downloads'),
            'minimal' => __('Minimal', 'themisdb-compendium-downloads')
        );
        
        echo '<select name="themisdb_compendium_button_style">';
        foreach ($styles as $key => $label) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($value, $key, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
    }
    
    /**
     * Render search term field
     */
    public function render_search_term_field() {
        $value = get_option('themisdb_compendium_search_term', 'kompendium');
        echo '<input type="text" name="themisdb_compendium_search_term" value="' . esc_attr($value) . '" class="regular-text">';
        echo '<p class="description">' . __('Suchbegriff für PDF-Dateinamen in Releases (z.B. "kompendium", "compendium", "documentation")', 'themisdb-compendium-downloads') . '</p>';
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Handle cache clear
        if (isset($_POST['clear_cache']) && check_admin_referer('themisdb_clear_cache')) {
            delete_transient('themisdb_compendium_release_data');
            echo '<div class="notice notice-success is-dismissible"><p>' . 
                 __('Cache wurde erfolgreich geleert.', 'themisdb-compendium-downloads') . 
                 '</p></div>';
        }

        $page_slug = 'themisdb-compendium-downloads';
        $active_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'settings';
        $allowed_tabs = array('settings', 'cache', 'shortcodes');

        if (!in_array($active_tab, $allowed_tabs, true)) {
            $active_tab = 'settings';
        }

        $tab_url = static function ($tab) use ($page_slug) {
            return admin_url('options-general.php?page=' . $page_slug . '&tab=' . $tab);
        };

        $stats = get_option('themisdb_compendium_download_stats', array());
        $button_style = get_option('themisdb_compendium_button_style', 'modern');
        $search_term = get_option('themisdb_compendium_search_term', 'kompendium');
        
        ?>
        <div class="wrap">
            <style>
                .themisdb-tab-content {
                    background: #fff;
                    border: 1px solid #c3c4c7;
                    border-top: none;
                    padding: 20px 24px;
                }

                .themisdb-tab-content > :first-child,
                .themisdb-tab-content .themisdb-admin-modules:first-child,
                .themisdb-tab-content .card:first-child,
                .themisdb-tab-content form:first-child {
                    margin-top: 0;
                }

                .themisdb-admin-modules {
                    display: grid;
                    gap: 20px;
                    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                    margin: 0 0 24px;
                }

                .themisdb-admin-modules .card,
                .themisdb-tab-content .card {
                    margin: 0;
                    max-width: none;
                    padding: 20px 24px;
                }

                .themisdb-tab-toolbar {
                    display: flex;
                    gap: 8px;
                    flex-wrap: wrap;
                    margin: 0 0 16px;
                }
            </style>

            <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
            <a href="<?php echo esc_url($tab_url('settings')); ?>" class="page-title-action"><?php esc_html_e('Einstellungen bearbeiten', 'themisdb-compendium-downloads'); ?></a>
            <a href="<?php echo esc_url($tab_url('cache')); ?>" class="page-title-action"><?php esc_html_e('Cache & Statistik', 'themisdb-compendium-downloads'); ?></a>
            <a href="<?php echo esc_url($tab_url('shortcodes')); ?>" class="page-title-action"><?php esc_html_e('Shortcodes anzeigen', 'themisdb-compendium-downloads'); ?></a>
            <hr class="wp-header-end">

            <nav class="nav-tab-wrapper wp-clearfix" aria-label="<?php esc_attr_e('Compendium Downloads Einstellungen', 'themisdb-compendium-downloads'); ?>">
                <a href="<?php echo esc_url($tab_url('settings')); ?>" class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Einstellungen', 'themisdb-compendium-downloads'); ?></a>
                <a href="<?php echo esc_url($tab_url('cache')); ?>" class="nav-tab <?php echo $active_tab === 'cache' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Cache & Statistik', 'themisdb-compendium-downloads'); ?></a>
                <a href="<?php echo esc_url($tab_url('shortcodes')); ?>" class="nav-tab <?php echo $active_tab === 'shortcodes' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Shortcodes', 'themisdb-compendium-downloads'); ?></a>
            </nav>

            <div class="themisdb-tab-content">
                <?php if ($active_tab === 'settings') : ?>
                    <div class="themisdb-admin-modules">
                        <div class="card">
                            <h2><?php esc_html_e('Schnellaktionen', 'themisdb-compendium-downloads'); ?></h2>
                            <div class="themisdb-tab-toolbar">
                                <a href="#themisdb-compendium-settings-form" class="button button-primary"><?php esc_html_e('Zur Konfiguration', 'themisdb-compendium-downloads'); ?></a>
                                <a href="<?php echo esc_url($tab_url('cache')); ?>" class="button"><?php esc_html_e('Cache prüfen', 'themisdb-compendium-downloads'); ?></a>
                            </div>
                            <p><?php esc_html_e('Steuere Release-Fetching, Suchbegriff und Darstellung der Kompendium-Downloads zentral in einem Bereich.', 'themisdb-compendium-downloads'); ?></p>
                        </div>
                        <div class="card">
                            <h2><?php esc_html_e('Aktive Defaults', 'themisdb-compendium-downloads'); ?></h2>
                            <table class="widefat striped">
                                <tbody>
                                    <tr><th><?php esc_html_e('Button-Stil', 'themisdb-compendium-downloads'); ?></th><td><?php echo esc_html($button_style); ?></td></tr>
                                    <tr><th><?php esc_html_e('Suchbegriff', 'themisdb-compendium-downloads'); ?></th><td><?php echo esc_html($search_term); ?></td></tr>
                                    <tr><th><?php esc_html_e('Erfasste Dateien', 'themisdb-compendium-downloads'); ?></th><td><?php echo esc_html((string) count($stats)); ?></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <form id="themisdb-compendium-settings-form" method="post" action="options.php">
                        <?php
                        settings_fields('themisdb_compendium_settings');
                        do_settings_sections('themisdb-compendium-downloads');
                        submit_button(__('Einstellungen speichern', 'themisdb-compendium-downloads'));
                        ?>
                    </form>
                <?php elseif ($active_tab === 'cache') : ?>
                    <div class="themisdb-admin-modules">
                        <div class="card">
                            <h2><?php esc_html_e('Cache verwalten', 'themisdb-compendium-downloads'); ?></h2>
                            <p><?php esc_html_e('Löschen Sie den Cache, um die neuesten Release-Daten von GitHub abzurufen.', 'themisdb-compendium-downloads'); ?></p>
                            <form method="post">
                                <?php wp_nonce_field('themisdb_clear_cache'); ?>
                                <input type="submit" name="clear_cache" class="button button-secondary" value="<?php esc_attr_e('Cache leeren', 'themisdb-compendium-downloads'); ?>">
                            </form>
                        </div>
                        <div class="card">
                            <h2><?php esc_html_e('Statistik-Überblick', 'themisdb-compendium-downloads'); ?></h2>
                            <p><?php echo esc_html(sprintf(__('Es sind aktuell %d Download-Einträge erfasst.', 'themisdb-compendium-downloads'), count($stats))); ?></p>
                        </div>
                    </div>

                    <div class="card">
                        <h2><?php _e('Download-Statistiken', 'themisdb-compendium-downloads'); ?></h2>
                        <?php
                        if (!empty($stats)) {
                            echo '<table class="widefat striped">';
                            echo '<thead><tr><th>' . esc_html__('Datei', 'themisdb-compendium-downloads') . '</th><th>' . esc_html__('Downloads', 'themisdb-compendium-downloads') . '</th></tr></thead>';
                            echo '<tbody>';
                            foreach ($stats as $file => $count) {
                                echo '<tr><td>' . esc_html($file) . '</td><td>' . esc_html($count) . '</td></tr>';
                            }
                            echo '</tbody></table>';
                        } else {
                            echo '<p>' . esc_html__('Noch keine Downloads erfasst.', 'themisdb-compendium-downloads') . '</p>';
                        }
                        ?>
                    </div>
                <?php else : ?>
                    <div class="themisdb-admin-modules">
                        <div class="card">
                            <h2><?php esc_html_e('Schnellaktionen', 'themisdb-compendium-downloads'); ?></h2>
                            <div class="themisdb-tab-toolbar">
                                <a href="<?php echo esc_url($tab_url('settings')); ?>" class="button button-primary"><?php esc_html_e('Einstellungen öffnen', 'themisdb-compendium-downloads'); ?></a>
                            </div>
                            <p><?php esc_html_e('Nutze Shortcodes mit verschiedenen Layouts und Anzeigeoptionen für Download-Listen aus GitHub-Releases.', 'themisdb-compendium-downloads'); ?></p>
                        </div>
                    </div>

                    <div class="card">
                        <h2><?php _e('Verwendung', 'themisdb-compendium-downloads'); ?></h2>
                        <p><?php _e('Fügen Sie den folgenden Shortcode in Ihre Seiten oder Beiträge ein:', 'themisdb-compendium-downloads'); ?></p>
                        <p><code>[themisdb_compendium_downloads]</code></p>

                        <h3><?php _e('Shortcode-Optionen', 'themisdb-compendium-downloads'); ?></h3>
                        <table class="widefat striped">
                            <thead>
                                <tr><th><?php esc_html_e('Option', 'themisdb-compendium-downloads'); ?></th><th><?php esc_html_e('Beschreibung', 'themisdb-compendium-downloads'); ?></th></tr>
                            </thead>
                            <tbody>
                                <tr><td><code>style</code></td><td><?php _e('Stil: modern, classic, minimal', 'themisdb-compendium-downloads'); ?></td></tr>
                                <tr><td><code>show_version</code></td><td><?php _e('Version anzeigen: yes, no', 'themisdb-compendium-downloads'); ?></td></tr>
                                <tr><td><code>show_date</code></td><td><?php _e('Datum anzeigen: yes, no', 'themisdb-compendium-downloads'); ?></td></tr>
                                <tr><td><code>show_size</code></td><td><?php _e('Dateigröße anzeigen: yes, no', 'themisdb-compendium-downloads'); ?></td></tr>
                                <tr><td><code>layout</code></td><td><?php _e('Layout: cards, list, compact', 'themisdb-compendium-downloads'); ?></td></tr>
                            </tbody>
                        </table>

                        <h3><?php _e('Beispiele', 'themisdb-compendium-downloads'); ?></h3>
                        <p><code>[themisdb_compendium_downloads style="modern" layout="cards"]</code></p>
                        <p><code>[themisdb_compendium_downloads style="minimal" show_version="no"]</code></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
