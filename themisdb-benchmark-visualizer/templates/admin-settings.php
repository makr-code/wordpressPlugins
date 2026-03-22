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
    • Total Lines:     277                                            ║
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
 * Admin Settings Template
 * Tab-based layout: Einstellungen | Shortcodes
 */

if (!defined('ABSPATH')) {
    exit;
}

$_tbv_page = 'themisdb-bv-settings';
$_tbv_tab  = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'settings';
if ( ! in_array( $_tbv_tab, array( 'settings', 'shortcodes' ), true ) ) {
    $_tbv_tab = 'settings';
}
$_tbv_url = function ( $tab ) use ( $_tbv_page ) {
    return esc_url( admin_url( 'options-general.php?page=' . $_tbv_page . '&tab=' . $tab ) );
};
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php echo esc_html( get_admin_page_title() ); ?>
        <a href="<?php echo $_tbv_url( 'shortcodes' ); ?>" class="page-title-action">
            <?php _e( 'Shortcodes', 'themisdb-benchmark-visualizer' ); ?>
        </a>
    </h1>
    <hr class="wp-header-end">

    <?php settings_errors( 'themisdb_bv_settings' ); ?>

    <nav class="nav-tab-wrapper wp-clearfix">
        <a href="<?php echo $_tbv_url( 'settings' ); ?>"
           class="nav-tab <?php echo $_tbv_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
            <?php _e( 'Einstellungen', 'themisdb-benchmark-visualizer' ); ?>
        </a>
        <a href="<?php echo $_tbv_url( 'shortcodes' ); ?>"
           class="nav-tab <?php echo $_tbv_tab === 'shortcodes' ? 'nav-tab-active' : ''; ?>">
            <?php _e( 'Shortcodes', 'themisdb-benchmark-visualizer' ); ?>
        </a>
    </nav>

    <div class="themisdb-tab-content">

    <?php if ( $_tbv_tab === 'settings' ) : ?>

    <div class="themisdb-admin-modules">
        <div class="card">
            <h2><?php _e( 'Schnellaktionen', 'themisdb-benchmark-visualizer' ); ?></h2>
            <p><?php _e( 'Öffnen Sie direkt die Shortcode-Referenz oder prüfen Sie die aktuelle Datenquellen-Konfiguration.', 'themisdb-benchmark-visualizer' ); ?></p>
            <p>
                <a href="<?php echo $_tbv_url( 'shortcodes' ); ?>" class="button button-secondary"><?php _e( 'Shortcodes anzeigen', 'themisdb-benchmark-visualizer' ); ?></a>
            </p>
        </div>
        <div class="card">
            <h2><?php _e( 'Aktive Defaults', 'themisdb-benchmark-visualizer' ); ?></h2>
            <table class="widefat striped">
                <tbody>
                    <tr><th><?php _e( 'Datenquelle', 'themisdb-benchmark-visualizer' ); ?></th><td><code><?php echo esc_html( get_option( 'themisdb_bv_data_source', 'local' ) ); ?></code></td></tr>
                    <tr><th><?php _e( 'Kategorie', 'themisdb-benchmark-visualizer' ); ?></th><td><code><?php echo esc_html( get_option( 'themisdb_bv_default_category', 'all' ) ); ?></code></td></tr>
                    <tr><th><?php _e( 'Metrik', 'themisdb-benchmark-visualizer' ); ?></th><td><code><?php echo esc_html( get_option( 'themisdb_bv_default_metric', 'latency' ) ); ?></code></td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <form method="post" action="options.php">
        <?php
        settings_fields( 'themisdb_bv_settings' );
        do_settings_sections( 'themisdb_bv_settings' );
        ?>

        <table class="form-table" role="presentation">
            <!-- Data Source -->
            <tr>
                <th scope="row">
                    <label for="themisdb_bv_data_source"><?php _e('Data Source', 'themisdb-benchmark-visualizer'); ?></label>
                </th>
                <td>
                    <select name="themisdb_bv_data_source" id="themisdb_bv_data_source">
                        <option value="local" <?php selected(get_option('themisdb_bv_data_source'), 'local'); ?>>
                            <?php _e('Local Files', 'themisdb-benchmark-visualizer'); ?>
                        </option>
                        <option value="github" <?php selected(get_option('themisdb_bv_data_source'), 'github'); ?>>
                            <?php _e('GitHub Repository', 'themisdb-benchmark-visualizer'); ?>
                        </option>
                    </select>
                    <p class="description">
                        <?php _e('Choose whether to load benchmark data from local files or GitHub repository.', 'themisdb-benchmark-visualizer'); ?>
                    </p>
                </td>
            </tr>

            <!-- GitHub Data URL -->
            <tr>
                <th scope="row">
                    <label for="themisdb_bv_github_data_url"><?php _e('GitHub Data URL', 'themisdb-benchmark-visualizer'); ?></label>
                </th>
                <td>
                    <input type="url" 
                           name="themisdb_bv_github_data_url" 
                           id="themisdb_bv_github_data_url" 
                           value="<?php echo esc_attr(get_option('themisdb_bv_github_data_url')); ?>" 
                           class="regular-text" />
                    <p class="description">
                        <?php _e('Base URL for benchmark data in GitHub repository.', 'themisdb-benchmark-visualizer'); ?>
                    </p>
                </td>
            </tr>

            <!-- Default Comparison Databases -->
            <tr>
                <th scope="row">
                    <label for="themisdb_bv_default_comparison_dbs"><?php _e('Default Comparison Databases', 'themisdb-benchmark-visualizer'); ?></label>
                </th>
                <td>
                    <input type="text" 
                           name="themisdb_bv_default_comparison_dbs" 
                           id="themisdb_bv_default_comparison_dbs" 
                           value="<?php echo esc_attr(get_option('themisdb_bv_default_comparison_dbs')); ?>" 
                           class="regular-text" />
                    <p class="description">
                        <?php _e('Comma-separated list of databases to compare (e.g., postgresql,mongodb,neo4j).', 'themisdb-benchmark-visualizer'); ?>
                    </p>
                </td>
            </tr>

            <!-- Default Category -->
            <tr>
                <th scope="row">
                    <label for="themisdb_bv_default_category"><?php _e('Default Category', 'themisdb-benchmark-visualizer'); ?></label>
                </th>
                <td>
                    <select name="themisdb_bv_default_category" id="themisdb_bv_default_category">
                        <option value="all" <?php selected(get_option('themisdb_bv_default_category'), 'all'); ?>>
                            <?php _e('All Operations', 'themisdb-benchmark-visualizer'); ?>
                        </option>
                        <option value="vector_search" <?php selected(get_option('themisdb_bv_default_category'), 'vector_search'); ?>>
                            <?php _e('Vector Search', 'themisdb-benchmark-visualizer'); ?>
                        </option>
                        <option value="aql_query" <?php selected(get_option('themisdb_bv_default_category'), 'aql_query'); ?>>
                            <?php _e('AQL Queries', 'themisdb-benchmark-visualizer'); ?>
                        </option>
                        <option value="graph_traversal" <?php selected(get_option('themisdb_bv_default_category'), 'graph_traversal'); ?>>
                            <?php _e('Graph Traversal', 'themisdb-benchmark-visualizer'); ?>
                        </option>
                    </select>
                    <p class="description">
                        <?php _e('Default category to display when the visualizer loads.', 'themisdb-benchmark-visualizer'); ?>
                    </p>
                </td>
            </tr>

            <!-- Default Metric -->
            <tr>
                <th scope="row">
                    <label for="themisdb_bv_default_metric"><?php _e('Default Metric', 'themisdb-benchmark-visualizer'); ?></label>
                </th>
                <td>
                    <select name="themisdb_bv_default_metric" id="themisdb_bv_default_metric">
                        <option value="latency" <?php selected(get_option('themisdb_bv_default_metric'), 'latency'); ?>>
                            <?php _e('Latency', 'themisdb-benchmark-visualizer'); ?>
                        </option>
                        <option value="throughput" <?php selected(get_option('themisdb_bv_default_metric'), 'throughput'); ?>>
                            <?php _e('Throughput', 'themisdb-benchmark-visualizer'); ?>
                        </option>
                        <option value="memory" <?php selected(get_option('themisdb_bv_default_metric'), 'memory'); ?>>
                            <?php _e('Memory Usage', 'themisdb-benchmark-visualizer'); ?>
                        </option>
                    </select>
                    <p class="description">
                        <?php _e('Default metric to display in charts.', 'themisdb-benchmark-visualizer'); ?>
                    </p>
                </td>
            </tr>

            <!-- Chart Theme -->
            <tr>
                <th scope="row">
                    <label for="themisdb_bv_chart_theme"><?php _e('Chart Theme', 'themisdb-benchmark-visualizer'); ?></label>
                </th>
                <td>
                    <select name="themisdb_bv_chart_theme" id="themisdb_bv_chart_theme">
                        <option value="light" <?php selected(get_option('themisdb_bv_chart_theme'), 'light'); ?>>
                            <?php _e('Light', 'themisdb-benchmark-visualizer'); ?>
                        </option>
                        <option value="dark" <?php selected(get_option('themisdb_bv_chart_theme'), 'dark'); ?>>
                            <?php _e('Dark', 'themisdb-benchmark-visualizer'); ?>
                        </option>
                    </select>
                    <p class="description">
                        <?php _e('Choose between light and dark theme for charts.', 'themisdb-benchmark-visualizer'); ?>
                    </p>
                </td>
            </tr>

            <!-- Auto Update Interval -->
            <tr>
                <th scope="row">
                    <label for="themisdb_bv_auto_update_interval"><?php _e('Cache Duration', 'themisdb-benchmark-visualizer'); ?></label>
                </th>
                <td>
                    <select name="themisdb_bv_auto_update_interval" id="themisdb_bv_auto_update_interval">
                        <option value="3600" <?php selected(get_option('themisdb_bv_auto_update_interval'), '3600'); ?>>
                            <?php _e('1 Hour', 'themisdb-benchmark-visualizer'); ?>
                        </option>
                        <option value="21600" <?php selected(get_option('themisdb_bv_auto_update_interval'), '21600'); ?>>
                            <?php _e('6 Hours', 'themisdb-benchmark-visualizer'); ?>
                        </option>
                        <option value="43200" <?php selected(get_option('themisdb_bv_auto_update_interval'), '43200'); ?>>
                            <?php _e('12 Hours', 'themisdb-benchmark-visualizer'); ?>
                        </option>
                        <option value="86400" <?php selected(get_option('themisdb_bv_auto_update_interval'), '86400'); ?>>
                            <?php _e('24 Hours', 'themisdb-benchmark-visualizer'); ?>
                        </option>
                        <option value="604800" <?php selected(get_option('themisdb_bv_auto_update_interval'), '604800'); ?>>
                            <?php _e('1 Week', 'themisdb-benchmark-visualizer'); ?>
                        </option>
                    </select>
                    <p class="description">
                        <?php _e('How long to cache benchmark data before refreshing.', 'themisdb-benchmark-visualizer'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>

    <?php elseif ( $_tbv_tab === 'shortcodes' ) : ?>

    <h2><?php _e( 'Shortcode-Verwendung', 'themisdb-benchmark-visualizer' ); ?></h2>
    <p><?php _e( 'Fügen Sie einen der folgenden Shortcodes in eine Seite oder einen Beitrag ein.', 'themisdb-benchmark-visualizer' ); ?></p>

    <table class="widefat striped" style="max-width:860px;">
        <thead>
            <tr>
                <th><?php _e( 'Shortcode', 'themisdb-benchmark-visualizer' ); ?></th>
                <th><?php _e( 'Beschreibung', 'themisdb-benchmark-visualizer' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>[themisdb_benchmark_visualizer]</code></td>
                <td><?php _e( 'Standardansicht gemäß Plugin-Einstellungen', 'themisdb-benchmark-visualizer' ); ?></td>
            </tr>
            <tr>
                <td><code>[themisdb_benchmark_visualizer category="vector_search"]</code></td>
                <td><?php _e( 'Kategorie vorfiltern (all, vector_search, aql_query, graph_traversal)', 'themisdb-benchmark-visualizer' ); ?></td>
            </tr>
            <tr>
                <td><code>[themisdb_benchmark_visualizer metric="throughput"]</code></td>
                <td><?php _e( 'Metrik vorauswählen (latency, throughput, memory)', 'themisdb-benchmark-visualizer' ); ?></td>
            </tr>
            <tr>
                <td><code>[themisdb_benchmark_visualizer compare="postgresql,mongodb"]</code></td>
                <td><?php _e( 'Bestimmte Datenbanken vergleichen (kommagetrennt)', 'themisdb-benchmark-visualizer' ); ?></td>
            </tr>
            <tr>
                <td><code>[themisdb_benchmark_visualizer chart_type="line"]</code></td>
                <td><?php _e( 'Diagrammtyp setzen (bar, line, radar)', 'themisdb-benchmark-visualizer' ); ?></td>
            </tr>
            <tr>
                <td><code>[themisdb_benchmark_visualizer category="vector_search" metric="latency" chart_type="bar"]</code></td>
                <td><?php _e( 'Mehrere Parameter kombiniert', 'themisdb-benchmark-visualizer' ); ?></td>
            </tr>
        </tbody>
    </table>

    <div class="notice notice-info inline" style="margin-top:24px;">
        <p>
            <strong><?php _e( 'Version:', 'themisdb-benchmark-visualizer' ); ?></strong> <?php echo esc_html( THEMISDB_BV_VERSION ); ?>&nbsp;&nbsp;
            <strong><?php _e( 'GitHub:', 'themisdb-benchmark-visualizer' ); ?></strong>
            <a href="https://github.com/makr-code/wordpressPlugins" target="_blank" rel="noopener">makr-code/wordpressPlugins</a>
        </p>
    </div>

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
