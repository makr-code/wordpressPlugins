<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB - Hybrid Database System                                   ║
╠═════════════════════════════════════════════════════════════════════╣
  File:            admin-settings.php                                 ║
  Version:         0.0.2                                              ║
  Last Modified:   2026-03-09 04:08:16                                ║
  Author:          makr-code                                            ║
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
                    <tr><th><?php _e( 'Datenquelle', 'themisdb-benchmark-visualizer' ); ?></th><td><code><?php echo esc_html( get_option( 'themisdb_bv_data_source', 'catalog' ) ); ?></code></td></tr>
                    <tr><th><?php _e( 'Catalog Path', 'themisdb-benchmark-visualizer' ); ?></th><td><code><?php echo esc_html( get_option( 'themisdb_bv_catalog_path', '' ) ?: 'auto-detect' ); ?></code></td></tr>
                    <tr><th><?php _e( 'Kategorie', 'themisdb-benchmark-visualizer' ); ?></th><td><code><?php echo esc_html( get_option( 'themisdb_bv_default_category', 'vector_search' ) ); ?></code></td></tr>
                    <tr><th><?php _e( 'Metrik', 'themisdb-benchmark-visualizer' ); ?></th><td><code><?php echo esc_html( get_option( 'themisdb_bv_default_metric', 'latency' ) ); ?></code></td></tr>
                    <tr><th><?php _e( 'Diagrammtyp', 'themisdb-benchmark-visualizer' ); ?></th><td><code><?php echo esc_html( get_option( 'themisdb_bv_default_chart_type', 'bar' ) ); ?></code></td></tr>
                    <tr><th><?php _e( 'Top-N', 'themisdb-benchmark-visualizer' ); ?></th><td><code><?php echo esc_html( get_option( 'themisdb_bv_default_top_n', '30' ) ); ?></code></td></tr>
                    <tr><th><?php _e( 'Statistik-Layout', 'themisdb-benchmark-visualizer' ); ?></th><td><code><?php echo esc_html( get_option( 'themisdb_bv_default_stats_mode', 'toolbar' ) ); ?></code></td></tr>
                    <tr><th><?php _e( 'Vergleichsmodus', 'themisdb-benchmark-visualizer' ); ?></th><td><code><?php echo esc_html( get_option( 'themisdb_bv_require_comparable_tasks', '1' ) === '1' ? 'strict-cross-engine' : 'all-tasks' ); ?></code></td></tr>
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
                        <option value="catalog" <?php selected(get_option('themisdb_bv_data_source'), 'catalog'); ?>>
                            <?php _e('Benchmark Catalog JSON (recommended)', 'themisdb-benchmark-visualizer'); ?>
                        </option>
                        <option value="local" <?php selected(get_option('themisdb_bv_data_source'), 'local'); ?>>
                            <?php _e('Local Files', 'themisdb-benchmark-visualizer'); ?>
                        </option>
                        <option value="demo" <?php selected(get_option('themisdb_bv_data_source'), 'demo'); ?>>
                            <?php _e('DEMO - synthetic data, fictional databases', 'themisdb-benchmark-visualizer'); ?>
                        </option>
                        <option value="github" <?php selected(get_option('themisdb_bv_data_source'), 'github'); ?>>
                            <?php _e('GitHub Repository (not implemented)', 'themisdb-benchmark-visualizer'); ?>
                        </option>
                    </select>
                    <p class="description">
                        <?php esc_html_e('Demo mode uses only fictional engines and bypasses the default comparison-engine list. It never substitutes real measurements automatically.', 'themisdb-benchmark-visualizer'); ?>
                        <?php _e('Choose the normalized catalog (recommended) or legacy local files. The GitHub adapter is not implemented and reports an error instead of substituting local data.', 'themisdb-benchmark-visualizer'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="themisdb_bv_catalog_path"><?php _e('Catalog File Path', 'themisdb-benchmark-visualizer'); ?></label>
                </th>
                <td>
                    <input type="text"
                           name="themisdb_bv_catalog_path"
                           id="themisdb_bv_catalog_path"
                           value="<?php echo esc_attr(get_option('themisdb_bv_catalog_path', '')); ?>"
                           class="regular-text code" />
                    <p class="description">
                        <?php _e('Absolute path to benchmark_catalog.json. Leave empty for automatic detection in source-links/benchmarks/benchmark_results.', 'themisdb-benchmark-visualizer'); ?>
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
                        <?php _e('Reserved for a future GitHub adapter. This URL is currently not fetched.', 'themisdb-benchmark-visualizer'); ?>
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

            <tr>
                <th scope="row">
                    <label for="themisdb_bv_require_comparable_tasks"><?php _e('Strict Cross-Engine Comparability', 'themisdb-benchmark-visualizer'); ?></label>
                </th>
                <td>
                    <input type="hidden" name="themisdb_bv_require_comparable_tasks" value="0" />
                    <label for="themisdb_bv_require_comparable_tasks">
                        <input type="checkbox"
                               name="themisdb_bv_require_comparable_tasks"
                               id="themisdb_bv_require_comparable_tasks"
                               value="1"
                               <?php checked(get_option('themisdb_bv_require_comparable_tasks', '1'), '1'); ?> />
                        <?php _e('Prefer tasks with at least two engines and a matching vector publication protocol. Legacy/reference data never becomes comparable by disabling this filter.', 'themisdb-benchmark-visualizer'); ?>
                    </label>
                    <p class="description">
                        <?php _e('Disable this to include all tasks even when only one engine has data.', 'themisdb-benchmark-visualizer'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="themisdb_bv_category_keyword_map"><?php _e('Category Keyword Mapping (JSON)', 'themisdb-benchmark-visualizer'); ?></label>
                </th>
                <td>
                    <?php
                    $default_map = array(
                        'vector_search' => array('vector', 'embedding', 'hnsw', 'simd_distance', 'knn'),
                        'graph_traversal' => array('graph', 'pagerank', 'traversal', 'bfs', 'dfs'),
                        'transaction' => array('tpcc', 'transaction', 'ycsb', 'mvcc', 'lock'),
                        'compression' => array('compression', 'zstd', 'gorilla', 'codec'),
                        'advanced' => array('aql', 'changefeed', 'async', 'batch', 'querymix', 'query'),
                        'content' => array('content', 'blob', 'branch', 'document', 'index'),
                        'gpu' => array('gpu', 'cuda', 'rocm', 'vulkan'),
                    );
                    $category_map_value = (string) get_option('themisdb_bv_category_keyword_map', '');
                    if ($category_map_value === '') {
                        $category_map_value = wp_json_encode($default_map, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                    }
                    ?>
                    <textarea name="themisdb_bv_category_keyword_map"
                              id="themisdb_bv_category_keyword_map"
                              rows="14"
                              class="large-text code"><?php echo esc_textarea($category_map_value); ?></textarea>
                    <p class="description">
                        <?php _e('Use JSON object format: {"category_key":["keyword1","keyword2"]}. This controls task-to-category assignment.', 'themisdb-benchmark-visualizer'); ?>
                    </p>
                </td>
            </tr>

            <!-- Default Category -->
            <tr>
                <th scope="row">
                    <label for="themisdb_bv_default_category"><?php _e('Default Category', 'themisdb-benchmark-visualizer'); ?></label>
                </th>
                <td>
                    <?php
                    $default_category_value = (string) get_option('themisdb_bv_default_category', 'vector_search');
                    $default_category_suggestions = array(
                        'vector_search',
                        'graph_traversal',
                        'transaction',
                        'compression',
                        'advanced',
                        'content',
                        'gpu',
                    );
                    ?>
                    <input type="text"
                           name="themisdb_bv_default_category"
                           id="themisdb_bv_default_category"
                           value="<?php echo esc_attr($default_category_value); ?>"
                           class="regular-text"
                           list="themisdb_bv_default_category_suggestions" />
                    <datalist id="themisdb_bv_default_category_suggestions">
                        <?php foreach ($default_category_suggestions as $suggestion) : ?>
                            <option value="<?php echo esc_attr($suggestion); ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                    <p class="description">
                        <?php _e('Default category key to display when the visualizer loads. You can use custom keys from your category keyword mapping.', 'themisdb-benchmark-visualizer'); ?>
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

            <tr>
                <th scope="row">
                    <label for="themisdb_bv_default_chart_type"><?php _e('Default Chart Type', 'themisdb-benchmark-visualizer'); ?></label>
                </th>
                <td>
                    <select name="themisdb_bv_default_chart_type" id="themisdb_bv_default_chart_type">
                        <?php foreach ($this->get_chart_type_labels() as $type => $label) : ?>
                        <option value="<?php echo esc_attr($type); ?>" <?php selected(get_option('themisdb_bv_default_chart_type', 'bar'), $type); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php _e('Initial chart type for the frontend visualizer.', 'themisdb-benchmark-visualizer'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="themisdb_bv_default_top_n"><?php _e('Default Top-N', 'themisdb-benchmark-visualizer'); ?></label>
                </th>
                <td>
                    <select name="themisdb_bv_default_top_n" id="themisdb_bv_default_top_n">
                        <option value="10" <?php selected(get_option('themisdb_bv_default_top_n', '30'), '10'); ?>>10</option>
                        <option value="20" <?php selected(get_option('themisdb_bv_default_top_n', '30'), '20'); ?>>20</option>
                        <option value="30" <?php selected(get_option('themisdb_bv_default_top_n', '30'), '30'); ?>>30</option>
                        <option value="50" <?php selected(get_option('themisdb_bv_default_top_n', '30'), '50'); ?>>50</option>
                    </select>
                    <p class="description">
                        <?php _e('Initial number of tasks shown before user filtering.', 'themisdb-benchmark-visualizer'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="themisdb_bv_default_stats_mode"><?php _e('Default Stats Layout', 'themisdb-benchmark-visualizer'); ?></label>
                </th>
                <td>
                    <select name="themisdb_bv_default_stats_mode" id="themisdb_bv_default_stats_mode">
                        <option value="toolbar" <?php selected(get_option('themisdb_bv_default_stats_mode', 'toolbar'), 'toolbar'); ?>><?php _e('Compact toolbar', 'themisdb-benchmark-visualizer'); ?></option>
                        <option value="cards" <?php selected(get_option('themisdb_bv_default_stats_mode', 'toolbar'), 'cards'); ?>><?php _e('Cards', 'themisdb-benchmark-visualizer'); ?></option>
                        <option value="hidden" <?php selected(get_option('themisdb_bv_default_stats_mode', 'toolbar'), 'hidden'); ?>><?php _e('Hidden', 'themisdb-benchmark-visualizer'); ?></option>
                    </select>
                    <p class="description">
                        <?php _e('Use a compact toolbar for the live benchmark summary or keep the larger stat cards.', 'themisdb-benchmark-visualizer'); ?>
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
                <td><?php _e( 'Kategorie vorfiltern (vector_search, graph_traversal, transaction, compression, advanced, content, gpu)', 'themisdb-benchmark-visualizer' ); ?></td>
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
                <td><?php _e( 'Diagrammtyp setzen (bar, bar-horizontal, bar-vertical, bar-isometric, line, line-dot, stepped, area, radar)', 'themisdb-benchmark-visualizer' ); ?></td>
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
