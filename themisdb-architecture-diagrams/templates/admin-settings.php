<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB - Hybrid Database System                                   ║
╠═════════════════════════════════════════════════════════════════════╣
  File:            admin-settings.php                                 ║
  Version:         0.0.2                                              ║
  Last Modified:   2026-03-09 04:08:16                                ║
  Author:          unknown                                            ║
╠═════════════════════════════════════════════════════════════════════╣
  Quality Metrics:                                                    ║
    • Maturity Level:  🟢 PRODUCTION-READY                             ║
    • Quality Score:   100.0/100                                      ║
    • Total Lines:     246                                            ║
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
 * Admin Settings Template for Architecture Diagrams
 * Tab-based layout: Einstellungen | Shortcodes & Ansichten
 */

if (!defined('ABSPATH')) {
    exit;
}

$_tad_page   = 'themisdb-ad-settings';
$_tad_tab    = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'settings';
if ( ! in_array( $_tad_tab, array( 'settings', 'shortcodes' ), true ) ) {
    $_tad_tab = 'settings';
}
$_tad_url = function ( $tab ) use ( $_tad_page ) {
    return esc_url( admin_url( 'options-general.php?page=' . $_tad_page . '&tab=' . $tab ) );
};
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php echo esc_html( get_admin_page_title() ); ?>
        <a href="<?php echo $_tad_url( 'shortcodes' ); ?>" class="page-title-action">
            <?php _e( 'Shortcodes &amp; Ansichten', 'themisdb-architecture-diagrams' ); ?>
        </a>
    </h1>
    <hr class="wp-header-end">

    <?php settings_errors( 'themisdb_ad_settings' ); ?>

    <nav class="nav-tab-wrapper wp-clearfix">
        <a href="<?php echo $_tad_url( 'settings' ); ?>"
           class="nav-tab <?php echo $_tad_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
            <?php _e( 'Einstellungen', 'themisdb-architecture-diagrams' ); ?>
        </a>
        <a href="<?php echo $_tad_url( 'shortcodes' ); ?>"
           class="nav-tab <?php echo $_tad_tab === 'shortcodes' ? 'nav-tab-active' : ''; ?>">
            <?php _e( 'Shortcodes &amp; Ansichten', 'themisdb-architecture-diagrams' ); ?>
        </a>
    </nav>

    <div class="themisdb-tab-content">

    <?php if ( $_tad_tab === 'settings' ) : ?>

    <div class="themisdb-admin-modules">
        <div class="card">
            <h2><?php _e( 'Schnellaktionen', 'themisdb-architecture-diagrams' ); ?></h2>
            <p><?php _e( 'Wechseln Sie direkt zu den Einbettungsbeispielen oder öffnen Sie die wichtigsten Architekturansichten im Frontend.', 'themisdb-architecture-diagrams' ); ?></p>
            <p>
                <a href="<?php echo $_tad_url( 'shortcodes' ); ?>" class="button button-secondary"><?php _e( 'Shortcodes anzeigen', 'themisdb-architecture-diagrams' ); ?></a>
            </p>
        </div>
        <div class="card">
            <h2><?php _e( 'Konfigurations-Überblick', 'themisdb-architecture-diagrams' ); ?></h2>
            <table class="widefat striped">
                <tbody>
                    <tr><th><?php _e( 'Standardansicht', 'themisdb-architecture-diagrams' ); ?></th><td><code><?php echo esc_html( get_option( 'themisdb_ad_default_view', 'high_level' ) ); ?></code></td></tr>
                    <tr><th><?php _e( 'Theme', 'themisdb-architecture-diagrams' ); ?></th><td><code><?php echo esc_html( get_option( 'themisdb_ad_theme', 'themis' ) ); ?></code></td></tr>
                    <tr><th><?php _e( 'Interaktiv', 'themisdb-architecture-diagrams' ); ?></th><td><?php echo get_option( 'themisdb_ad_interactive', 1 ) ? esc_html__( 'Ja', 'themisdb-architecture-diagrams' ) : esc_html__( 'Nein', 'themisdb-architecture-diagrams' ); ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <form method="post" action="options.php">
        <?php
        settings_fields( 'themisdb_ad_settings' );
        do_settings_sections( 'themisdb_ad_settings' );
        ?>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="themisdb_ad_default_view"><?php _e('Default View', 'themisdb-architecture-diagrams'); ?></label>
                </th>
                <td>
                    <select name="themisdb_ad_default_view" id="themisdb_ad_default_view">
                        <option value="high_level" <?php selected(get_option('themisdb_ad_default_view'), 'high_level'); ?>>
                            <?php _e('High-Level Architecture', 'themisdb-architecture-diagrams'); ?>
                        </option>
                        <option value="storage_layer" <?php selected(get_option('themisdb_ad_default_view'), 'storage_layer'); ?>>
                            <?php _e('Storage Layer', 'themisdb-architecture-diagrams'); ?>
                        </option>
                        <option value="llm_integration" <?php selected(get_option('themisdb_ad_default_view'), 'llm_integration'); ?>>
                            <?php _e('LLM Integration', 'themisdb-architecture-diagrams'); ?>
                        </option>
                        <option value="sharding_raid" <?php selected(get_option('themisdb_ad_default_view'), 'sharding_raid'); ?>>
                            <?php _e('Sharding & RAID', 'themisdb-architecture-diagrams'); ?>
                        </option>
                    </select>
                    <p class="description">
                        <?php _e('Default architecture view when the plugin loads.', 'themisdb-architecture-diagrams'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="themisdb_ad_theme"><?php _e('Mermaid Theme', 'themisdb-architecture-diagrams'); ?></label>
                </th>
                <td>
                    <select name="themisdb_ad_theme" id="themisdb_ad_theme">
                        <option value="themis" <?php selected(get_option('themisdb_ad_theme'), 'themis'); ?>>
                            <?php _e('Themis (Recommended)', 'themisdb-architecture-diagrams'); ?>
                        </option>
                        <option value="neutral" <?php selected(get_option('themisdb_ad_theme'), 'neutral'); ?>>
                            <?php _e('Neutral', 'themisdb-architecture-diagrams'); ?>
                        </option>
                        <option value="default" <?php selected(get_option('themisdb_ad_theme'), 'default'); ?>>
                            <?php _e('Default', 'themisdb-architecture-diagrams'); ?>
                        </option>
                        <option value="dark" <?php selected(get_option('themisdb_ad_theme'), 'dark'); ?>>
                            <?php _e('Dark', 'themisdb-architecture-diagrams'); ?>
                        </option>
                        <option value="forest" <?php selected(get_option('themisdb_ad_theme'), 'forest'); ?>>
                            <?php _e('Forest', 'themisdb-architecture-diagrams'); ?>
                        </option>
                    </select>
                    <p class="description">
                        <?php _e('Color theme for diagram rendering. Themis uses official brand colors.', 'themisdb-architecture-diagrams'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="themisdb_ad_enable_dark_mode"><?php _e('Enable Dark Mode', 'themisdb-architecture-diagrams'); ?></label>
                </th>
                <td>
                    <input type="checkbox" 
                           name="themisdb_ad_enable_dark_mode" 
                           id="themisdb_ad_enable_dark_mode" 
                           value="1" 
                           <?php checked(get_option('themisdb_ad_enable_dark_mode', 1), 1); ?> />
                    <label for="themisdb_ad_enable_dark_mode">
                        <?php _e('Enable automatic dark mode detection (system preference, theme, plugins)', 'themisdb-architecture-diagrams'); ?>
                    </label>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="themisdb_ad_enable_lazy_loading"><?php _e('Enable Lazy Loading', 'themisdb-architecture-diagrams'); ?></label>
                </th>
                <td>
                    <input type="checkbox" 
                           name="themisdb_ad_enable_lazy_loading" 
                           id="themisdb_ad_enable_lazy_loading" 
                           value="1" 
                           <?php checked(get_option('themisdb_ad_enable_lazy_loading', 1), 1); ?> />
                    <label for="themisdb_ad_enable_lazy_loading">
                        <?php _e('Load diagrams only when they become visible (improves performance)', 'themisdb-architecture-diagrams'); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="themisdb_ad_interactive"><?php _e('Enable Interactivity', 'themisdb-architecture-diagrams'); ?></label>
                </th>
                <td>
                    <input type="checkbox" 
                           name="themisdb_ad_interactive" 
                           id="themisdb_ad_interactive" 
                           value="1" 
                           <?php checked(get_option('themisdb_ad_interactive'), 1); ?> />
                    <label for="themisdb_ad_interactive">
                        <?php _e('Enable clickable components with details', 'themisdb-architecture-diagrams'); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="themisdb_ad_enable_export"><?php _e('Enable Export', 'themisdb-architecture-diagrams'); ?></label>
                </th>
                <td>
                    <input type="checkbox" 
                           name="themisdb_ad_enable_export" 
                           id="themisdb_ad_enable_export" 
                           value="1" 
                           <?php checked(get_option('themisdb_ad_enable_export'), 1); ?> />
                    <label for="themisdb_ad_enable_export">
                        <?php _e('Enable SVG/PNG export functionality', 'themisdb-architecture-diagrams'); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="themisdb_ad_show_descriptions"><?php _e('Show Descriptions', 'themisdb-architecture-diagrams'); ?></label>
                </th>
                <td>
                    <input type="checkbox" 
                           name="themisdb_ad_show_descriptions" 
                           id="themisdb_ad_show_descriptions" 
                           value="1" 
                           <?php checked(get_option('themisdb_ad_show_descriptions'), 1); ?> />
                    <label for="themisdb_ad_show_descriptions">
                        <?php _e('Display architecture descriptions below diagrams', 'themisdb-architecture-diagrams'); ?>
                    </label>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>

    <?php elseif ( $_tad_tab === 'shortcodes' ) : ?>

    <h2><?php _e( 'Shortcode-Verwendung', 'themisdb-architecture-diagrams' ); ?></h2>
    <p><?php _e( 'Fügen Sie einen der folgenden Shortcodes in eine Seite oder einen Beitrag ein.', 'themisdb-architecture-diagrams' ); ?></p>

    <table class="widefat striped" style="max-width:800px;">
        <thead>
            <tr>
                <th><?php _e( 'Beispiel', 'themisdb-architecture-diagrams' ); ?></th>
                <th><?php _e( 'Beschreibung', 'themisdb-architecture-diagrams' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>[themisdb_architecture]</code></td>
                <td><?php _e( 'Standardansicht mit Plugin-Einstellungen', 'themisdb-architecture-diagrams' ); ?></td>
            </tr>
            <tr>
                <td><code>[themisdb_architecture view="storage_layer"]</code></td>
                <td><?php _e( 'Bestimmte Ansicht direkt angeben', 'themisdb-architecture-diagrams' ); ?></td>
            </tr>
            <tr>
                <td><code>[themisdb_architecture theme="dark"]</code></td>
                <td><?php _e( 'Eigenes Farbschema (dark, forest, neutral, default, themis)', 'themisdb-architecture-diagrams' ); ?></td>
            </tr>
            <tr>
                <td><code>[themisdb_architecture show_controls="false"]</code></td>
                <td><?php _e( 'Ansichts-Umschalter ausblenden', 'themisdb-architecture-diagrams' ); ?></td>
            </tr>
            <tr>
                <td><code>[themisdb_architecture view="llm_integration" theme="neutral" interactive="true"]</code></td>
                <td><?php _e( 'Mehrere Parameter kombiniert', 'themisdb-architecture-diagrams' ); ?></td>
            </tr>
        </tbody>
    </table>

    <h2 style="margin-top:30px;"><?php _e( 'Verfügbare Ansichten', 'themisdb-architecture-diagrams' ); ?></h2>
    <table class="widefat striped" style="max-width:800px;">
        <thead>
            <tr>
                <th><?php _e( 'Schlüssel (view=)', 'themisdb-architecture-diagrams' ); ?></th>
                <th><?php _e( 'Bezeichnung', 'themisdb-architecture-diagrams' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr><td><code>high_level</code></td><td><?php _e( 'Gesamt-Systemarchitektur', 'themisdb-architecture-diagrams' ); ?></td></tr>
            <tr><td><code>storage_layer</code></td><td><?php _e( 'Speicher-Engine &amp; Persistenz', 'themisdb-architecture-diagrams' ); ?></td></tr>
            <tr><td><code>llm_integration</code></td><td><?php _e( 'LLM / KI-Integration', 'themisdb-architecture-diagrams' ); ?></td></tr>
            <tr><td><code>sharding_raid</code></td><td><?php _e( 'Sharding &amp; RAID', 'themisdb-architecture-diagrams' ); ?></td></tr>
            <tr><td><code>database_comparison</code></td><td><?php _e( 'Datenbankvergleich', 'themisdb-architecture-diagrams' ); ?></td></tr>
            <tr><td><code>llm_comparison</code></td><td><?php _e( 'LLM-Vergleich', 'themisdb-architecture-diagrams' ); ?></td></tr>
            <tr><td><code>hardware_architecture</code></td><td><?php _e( 'Hardware-Architektur', 'themisdb-architecture-diagrams' ); ?></td></tr>
            <tr><td><code>performance_comparison</code></td><td><?php _e( 'Performance-Vergleich', 'themisdb-architecture-diagrams' ); ?></td></tr>
            <tr><td><code>tco_comparison</code></td><td><?php _e( 'TCO-Vergleich', 'themisdb-architecture-diagrams' ); ?></td></tr>
            <tr><td><code>feature_matrix</code></td><td><?php _e( 'Feature-Matrix', 'themisdb-architecture-diagrams' ); ?></td></tr>
            <tr><td><code>deployment_options</code></td><td><?php _e( 'Deployment-Optionen', 'themisdb-architecture-diagrams' ); ?></td></tr>
            <tr><td><code>use_case_recommendations</code></td><td><?php _e( 'Use-Case-Empfehlungen', 'themisdb-architecture-diagrams' ); ?></td></tr>
            <tr><td><code>migration_paths</code></td><td><?php _e( 'Migrationspfade', 'themisdb-architecture-diagrams' ); ?></td></tr>
        </tbody>
    </table>

    <?php endif; ?>

    </div><!-- .themisdb-tab-content -->
</div><!-- .wrap -->

<style>
.themisdb-admin-modules {
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));
    gap:16px;
    margin:0 0 20px;
}
.themisdb-admin-modules .card { margin:0; max-width:none; }
.themisdb-tab-content {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-top: none;
    padding: 20px 24px;
}
.themisdb-tab-content > h2:first-child,
.themisdb-tab-content > h3:first-child,
.themisdb-tab-content > p:first-child { margin-top:0; }
.themisdb-tab-content .widefat th { width:auto; }
.themisdb-tab-content table.widefat code {
    background: #f6f7f7;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 12px;
}
</style>
