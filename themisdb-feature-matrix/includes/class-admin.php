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
    • Total Lines:     298                                            ║
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
 * Admin Settings Class
 * 
 * Handles WordPress admin functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class ThemisDB_Feature_Matrix_Admin {
    
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
            __('ThemisDB Feature Matrix Settings', 'themisdb-feature-matrix'),
            __('Feature Matrix', 'themisdb-feature-matrix'),
            'manage_options',
            'themisdb-feature-matrix',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        // Register settings group
        register_setting('themisdb_fm_settings', 'themisdb_fm_default_view');
        register_setting('themisdb_fm_settings', 'themisdb_fm_enable_filters');
        register_setting('themisdb_fm_settings', 'themisdb_fm_enable_csv_export');
        register_setting('themisdb_fm_settings', 'themisdb_fm_show_themis_highlight');
        register_setting('themisdb_fm_settings', 'themisdb_fm_default_style');
        register_setting('themisdb_fm_settings', 'themisdb_fm_sticky_header');
        register_setting('themisdb_fm_settings', 'themisdb_fm_enable_tooltips');
        register_setting('themisdb_fm_settings', 'themisdb_fm_custom_colors');
        
        // Add settings section
        add_settings_section(
            'themisdb_fm_general',
            __('General Settings', 'themisdb-feature-matrix'),
            array($this, 'general_section_callback'),
            'themisdb-feature-matrix'
        );
        
        // Add settings fields
        add_settings_field(
            'default_view',
            __('Default View', 'themisdb-feature-matrix'),
            array($this, 'default_view_callback'),
            'themisdb-feature-matrix',
            'themisdb_fm_general'
        );
        
        add_settings_field(
            'default_style',
            __('Default Style', 'themisdb-feature-matrix'),
            array($this, 'default_style_callback'),
            'themisdb-feature-matrix',
            'themisdb_fm_general'
        );
        
        add_settings_field(
            'enable_filters',
            __('Enable Category Filters', 'themisdb-feature-matrix'),
            array($this, 'enable_filters_callback'),
            'themisdb-feature-matrix',
            'themisdb_fm_general'
        );
        
        add_settings_field(
            'enable_csv_export',
            __('Enable CSV Export', 'themisdb-feature-matrix'),
            array($this, 'enable_csv_export_callback'),
            'themisdb-feature-matrix',
            'themisdb_fm_general'
        );
        
        add_settings_field(
            'show_themis_highlight',
            __('Show ThemisDB Highlight', 'themisdb-feature-matrix'),
            array($this, 'show_themis_highlight_callback'),
            'themisdb-feature-matrix',
            'themisdb_fm_general'
        );
        
        add_settings_field(
            'sticky_header',
            __('Sticky Header', 'themisdb-feature-matrix'),
            array($this, 'sticky_header_callback'),
            'themisdb-feature-matrix',
            'themisdb_fm_general'
        );
        
        add_settings_field(
            'enable_tooltips',
            __('Enable Tooltips', 'themisdb-feature-matrix'),
            array($this, 'enable_tooltips_callback'),
            'themisdb-feature-matrix',
            'themisdb_fm_general'
        );
    }
    
    /**
     * General section callback
     */
    public function general_section_callback() {
        echo '<p>' . esc_html__('Configure default settings for the Feature Matrix plugin.', 'themisdb-feature-matrix') . '</p>';
    }
    
    /**
     * Default view field callback
     */
    public function default_view_callback() {
        $value = get_option('themisdb_fm_default_view', 'all');
        ?>
        <select name="themisdb_fm_default_view">
            <option value="all" <?php selected($value, 'all'); ?>><?php _e('All Features', 'themisdb-feature-matrix'); ?></option>
            <option value="data_models" <?php selected($value, 'data_models'); ?>><?php _e('Data Models', 'themisdb-feature-matrix'); ?></option>
            <option value="ai_ml" <?php selected($value, 'ai_ml'); ?>><?php _e('AI/ML', 'themisdb-feature-matrix'); ?></option>
            <option value="performance" <?php selected($value, 'performance'); ?>><?php _e('Performance', 'themisdb-feature-matrix'); ?></option>
            <option value="compatibility" <?php selected($value, 'compatibility'); ?>><?php _e('Compatibility', 'themisdb-feature-matrix'); ?></option>
            <option value="licensing" <?php selected($value, 'licensing'); ?>><?php _e('Licensing', 'themisdb-feature-matrix'); ?></option>
        </select>
        <p class="description"><?php _e('Select the default category to display.', 'themisdb-feature-matrix'); ?></p>
        <?php
    }
    
    /**
     * Default style field callback
     */
    public function default_style_callback() {
        $value = get_option('themisdb_fm_default_style', 'modern');
        ?>
        <select name="themisdb_fm_default_style">
            <option value="modern" <?php selected($value, 'modern'); ?>><?php _e('Modern', 'themisdb-feature-matrix'); ?></option>
            <option value="compact" <?php selected($value, 'compact'); ?>><?php _e('Compact', 'themisdb-feature-matrix'); ?></option>
            <option value="minimal" <?php selected($value, 'minimal'); ?>><?php _e('Minimal', 'themisdb-feature-matrix'); ?></option>
        </select>
        <p class="description"><?php _e('Select the default visual style.', 'themisdb-feature-matrix'); ?></p>
        <?php
    }
    
    /**
     * Enable filters field callback
     */
    public function enable_filters_callback() {
        $value = get_option('themisdb_fm_enable_filters', 'yes');
        ?>
        <label>
            <input type="checkbox" name="themisdb_fm_enable_filters" value="yes" <?php checked($value, 'yes'); ?> />
            <?php _e('Show category filter buttons', 'themisdb-feature-matrix'); ?>
        </label>
        <p class="description"><?php _e('Allow users to filter features by category.', 'themisdb-feature-matrix'); ?></p>
        <?php
    }
    
    /**
     * Enable CSV export field callback
     */
    public function enable_csv_export_callback() {
        $value = get_option('themisdb_fm_enable_csv_export', 'yes');
        ?>
        <label>
            <input type="checkbox" name="themisdb_fm_enable_csv_export" value="yes" <?php checked($value, 'yes'); ?> />
            <?php _e('Show CSV export button', 'themisdb-feature-matrix'); ?>
        </label>
        <p class="description"><?php _e('Allow users to export the comparison as CSV.', 'themisdb-feature-matrix'); ?></p>
        <?php
    }
    
    /**
     * Show ThemisDB highlight field callback
     */
    public function show_themis_highlight_callback() {
        $value = get_option('themisdb_fm_show_themis_highlight', 'yes');
        ?>
        <label>
            <input type="checkbox" name="themisdb_fm_show_themis_highlight" value="yes" <?php checked($value, 'yes'); ?> />
            <?php _e('Highlight ThemisDB column with "⭐ Recommended" badge', 'themisdb-feature-matrix'); ?>
        </label>
        <p class="description"><?php _e('Add visual emphasis to the ThemisDB column.', 'themisdb-feature-matrix'); ?></p>
        <?php
    }
    
    /**
     * Sticky header field callback
     */
    public function sticky_header_callback() {
        $value = get_option('themisdb_fm_sticky_header', 'yes');
        ?>
        <label>
            <input type="checkbox" name="themisdb_fm_sticky_header" value="yes" <?php checked($value, 'yes'); ?> />
            <?php _e('Keep table header visible when scrolling', 'themisdb-feature-matrix'); ?>
        </label>
        <p class="description"><?php _e('Make the table header sticky for easier navigation.', 'themisdb-feature-matrix'); ?></p>
        <?php
    }
    
    /**
     * Enable tooltips field callback
     */
    public function enable_tooltips_callback() {
        $value = get_option('themisdb_fm_enable_tooltips', 'yes');
        ?>
        <label>
            <input type="checkbox" name="themisdb_fm_enable_tooltips" value="yes" <?php checked($value, 'yes'); ?> />
            <?php _e('Show info tooltips on hover', 'themisdb-feature-matrix'); ?>
        </label>
        <p class="description"><?php _e('Display helpful tooltips when hovering over features.', 'themisdb-feature-matrix'); ?></p>
        <?php
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Show success message
        if (isset($_GET['settings-updated'])) {
            add_settings_error(
                'themisdb_fm_messages',
                'themisdb_fm_message',
                __('Settings Saved', 'themisdb-feature-matrix'),
                'updated'
            );
        }
        
        settings_errors('themisdb_fm_messages');
        $page_slug = 'themisdb-feature-matrix';
        $active_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'settings';
        $allowed_tabs = array('settings', 'shortcodes');

        if (!in_array($active_tab, $allowed_tabs, true)) {
            $active_tab = 'settings';
        }

        $tab_url = static function ($tab) use ($page_slug) {
            return admin_url('options-general.php?page=' . $page_slug . '&tab=' . $tab);
        };

        $default_view = get_option('themisdb_fm_default_view', 'all');
        $default_style = get_option('themisdb_fm_default_style', 'modern');
        $filters_enabled = get_option('themisdb_fm_enable_filters', 'yes');
        $csv_enabled = get_option('themisdb_fm_enable_csv_export', 'yes');
        $sticky_header = get_option('themisdb_fm_sticky_header', 'yes');
        $tooltips_enabled = get_option('themisdb_fm_enable_tooltips', 'yes');
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

                .themisdb-tab-content .widefat thead th {
                    font-weight: 600;
                }
            </style>

            <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
            <a href="<?php echo esc_url($tab_url('settings')); ?>" class="page-title-action"><?php esc_html_e('Einstellungen bearbeiten', 'themisdb-feature-matrix'); ?></a>
            <a href="<?php echo esc_url($tab_url('shortcodes')); ?>" class="page-title-action"><?php esc_html_e('Shortcodes anzeigen', 'themisdb-feature-matrix'); ?></a>
            <hr class="wp-header-end">

            <nav class="nav-tab-wrapper wp-clearfix" aria-label="<?php esc_attr_e('Feature Matrix Einstellungen', 'themisdb-feature-matrix'); ?>">
                <a href="<?php echo esc_url($tab_url('settings')); ?>" class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Einstellungen', 'themisdb-feature-matrix'); ?>
                </a>
                <a href="<?php echo esc_url($tab_url('shortcodes')); ?>" class="nav-tab <?php echo $active_tab === 'shortcodes' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Shortcodes', 'themisdb-feature-matrix'); ?>
                </a>
            </nav>

            <div class="themisdb-tab-content">
                <?php if ($active_tab === 'settings') : ?>
                    <div class="themisdb-admin-modules">
                        <div class="card">
                            <h2><?php esc_html_e('Schnellaktionen', 'themisdb-feature-matrix'); ?></h2>
                            <div class="themisdb-tab-toolbar">
                                <a href="#themisdb-feature-matrix-form" class="button button-primary"><?php esc_html_e('Zur Konfiguration', 'themisdb-feature-matrix'); ?></a>
                                <a href="<?php echo esc_url($tab_url('shortcodes')); ?>" class="button"><?php esc_html_e('Shortcode-Referenz', 'themisdb-feature-matrix'); ?></a>
                            </div>
                            <p><?php esc_html_e('Konfiguriere Standardansicht, Export und Interaktionen zentral für alle Feature-Matrix-Ausgaben.', 'themisdb-feature-matrix'); ?></p>
                        </div>

                        <div class="card">
                            <h2><?php esc_html_e('Aktive Defaults', 'themisdb-feature-matrix'); ?></h2>
                            <table class="widefat striped">
                                <tbody>
                                    <tr>
                                        <th><?php esc_html_e('Standard-Kategorie', 'themisdb-feature-matrix'); ?></th>
                                        <td><?php echo esc_html($default_view); ?></td>
                                    </tr>
                                    <tr>
                                        <th><?php esc_html_e('Standard-Stil', 'themisdb-feature-matrix'); ?></th>
                                        <td><?php echo esc_html($default_style); ?></td>
                                    </tr>
                                    <tr>
                                        <th><?php esc_html_e('Filter / CSV / Sticky Header', 'themisdb-feature-matrix'); ?></th>
                                        <td><?php echo esc_html(($filters_enabled === 'yes' ? 'On' : 'Off') . ' / ' . ($csv_enabled === 'yes' ? 'On' : 'Off') . ' / ' . ($sticky_header === 'yes' ? 'On' : 'Off')); ?></td>
                                    </tr>
                                    <tr>
                                        <th><?php esc_html_e('Tooltips', 'themisdb-feature-matrix'); ?></th>
                                        <td><?php echo esc_html($tooltips_enabled === 'yes' ? 'On' : 'Off'); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <form id="themisdb-feature-matrix-form" action="options.php" method="post">
                        <?php
                        settings_fields('themisdb_fm_settings');
                        do_settings_sections('themisdb-feature-matrix');
                        submit_button(__('Save Settings', 'themisdb-feature-matrix'));
                        ?>
                    </form>
                <?php else : ?>
                    <div class="themisdb-admin-modules">
                        <div class="card">
                            <h2><?php esc_html_e('Schnellaktionen', 'themisdb-feature-matrix'); ?></h2>
                            <div class="themisdb-tab-toolbar">
                                <a href="<?php echo esc_url($tab_url('settings')); ?>" class="button button-primary"><?php esc_html_e('Einstellungen öffnen', 'themisdb-feature-matrix'); ?></a>
                            </div>
                            <p><?php esc_html_e('Verwende die Shortcodes, um Vergleichstabellen mit Standardwerten oder gezielten Overrides in Seiten und Beiträgen einzubinden.', 'themisdb-feature-matrix'); ?></p>
                        </div>
                    </div>

                    <div class="card">
                        <h2><?php _e('Shortcode Usage', 'themisdb-feature-matrix'); ?></h2>
                        <p><?php _e('Use this shortcode in your posts or pages:', 'themisdb-feature-matrix'); ?></p>
                        <p><code>[themisdb_feature_matrix]</code></p>

                        <h3><?php _e('Shortcode Parameters', 'themisdb-feature-matrix'); ?></h3>
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Parameter', 'themisdb-feature-matrix'); ?></th>
                                    <th><?php esc_html_e('Beschreibung', 'themisdb-feature-matrix'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>category</code></td>
                                    <td><?php esc_html_e('Filter by category: all, data_models, ai_ml, performance, compatibility, licensing', 'themisdb-feature-matrix'); ?></td>
                                </tr>
                                <tr>
                                    <td><code>style</code></td>
                                    <td><?php esc_html_e('Visual style: modern, compact, minimal', 'themisdb-feature-matrix'); ?></td>
                                </tr>
                                <tr>
                                    <td><code>highlight_themis</code></td>
                                    <td><?php esc_html_e('Highlight ThemisDB column: yes, no', 'themisdb-feature-matrix'); ?></td>
                                </tr>
                                <tr>
                                    <td><code>sticky_header</code></td>
                                    <td><?php esc_html_e('Enable sticky header: yes, no', 'themisdb-feature-matrix'); ?></td>
                                </tr>
                                <tr>
                                    <td><code>filterable</code></td>
                                    <td><?php esc_html_e('Show category filters: yes, no', 'themisdb-feature-matrix'); ?></td>
                                </tr>
                            </tbody>
                        </table>

                        <h3><?php _e('Example', 'themisdb-feature-matrix'); ?></h3>
                        <p><code>[themisdb_feature_matrix category="ai_ml" style="modern" highlight_themis="yes"]</code></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
