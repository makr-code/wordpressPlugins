<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB - Hybrid Database System                                   ║
╠═════════════════════════════════════════════════════════════════════╣
  File:            visualizer.php                                     ║
  Version:         0.0.2                                              ║
  Last Modified:   2026-03-09 04:08:16                                ║
  Author:          makr-code                                            ║
╠═════════════════════════════════════════════════════════════════════╣
  Quality Metrics:                                                    ║
    • Maturity Level:  🟢 PRODUCTION-READY                             ║
    • Quality Score:   100.0/100                                      ║
    • Total Lines:     159                                            ║
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
 * Benchmark Visualizer Template
 * 
 * This template displays the benchmark visualizer interface
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<?php
$show_header = !empty($atts['show_header']);
$show_controls = !empty($atts['show_controls']);
$show_chart = !empty($atts['show_chart']);
$show_overlay = !empty($atts['show_overlay']);
$show_mermaid = !empty($atts['show_mermaid']);
$show_nav = !empty($atts['show_nav']);
$show_stats = !empty($atts['show_stats']);
$show_table = !empty($atts['show_table']);
$default_category = (string) get_option('themisdb_bv_default_category', 'vector_search');
$selected_category = isset($atts['category']) ? sanitize_key((string) $atts['category']) : $default_category;
if ($selected_category === 'all' && $default_category !== 'all') {
    $selected_category = $default_category;
}
$default_top_n = (string) get_option('themisdb_bv_default_top_n', '30');
$chart_ratio = isset($atts['aspect_ratio']) ? sanitize_text_field($atts['aspect_ratio']) : '4:3';
$chart_ratio_css = preg_replace('/\s*:\s*/', ' / ', $chart_ratio);
if (!preg_match('/^\d+(?:\.\d+)?\s*\/\s*\d+(?:\.\d+)?$/', $chart_ratio_css)) {
    $chart_ratio_css = '4 / 3';
}
?>

<div class="themisdb-benchmark-wrapper themisdb-benchmark-wrapper--minimal">
    <div class="themisdb-benchmark-header">
        <div class="themisdb-benchmark-headline">
            <h2>Benchmarks</h2>
        </div>
    </div>

    <?php if ($show_chart) : ?>
        <div class="themisdb-benchmark-stage">
            <div class="themisdb-benchmark-window">
                <div class="themisdb-benchmark-window__chrome" aria-hidden="true">
                    <div class="themisdb-benchmark-window__controls">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                    <div class="themisdb-benchmark-window__title"><?php _e('Benchmark Overview', 'themisdb-benchmark-visualizer'); ?></div>
                </div>

                <div class="themisdb-benchmark-panel themisdb-benchmark-panel--minimal">
                    <header class="themisdb-benchmark-panel-header">
                        <?php if ($show_controls) : ?>
                            <div class="themisdb-benchmark-menu">
                                <button class="themisdb-benchmark-menu-toggle" type="button" aria-label="<?php esc_attr_e('Diagramm-Optionen', 'themisdb-benchmark-visualizer'); ?>" aria-expanded="false" aria-controls="bv-control-panel" aria-haspopup="true">
                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                        <path d="M4 7.25A1.25 1.25 0 0 1 5.25 6h13.5A1.25 1.25 0 0 1 20 7.25v.5A1.25 1.25 0 0 1 18.75 9H5.25A1.25 1.25 0 0 1 4 7.75v-.5Zm0 9A1.25 1.25 0 0 1 5.25 15h13.5a1.25 1.25 0 0 1 0 2.5H5.25A1.25 1.25 0 0 1 4 16.25v-.5Zm0-4.5A1.25 1.25 0 0 1 5.25 10.5h13.5A1.25 1.25 0 0 1 20 11.75v.5a1.25 1.25 0 0 1-2.5 0v-.5H5.25v.5A1.25 1.25 0 0 1 4 13.75v-.5Z" fill="currentColor"/>
                                    </svg>
                                </button>
                                <div id="bv-control-panel" class="themisdb-benchmark-controls themisdb-benchmark-controls--overlay">
                                    <select id="bv-category-filter" class="themisdb-select">
                                        <option value="<?php echo esc_attr($selected_category); ?>"><?php echo esc_html($selected_category); ?></option>
                                    </select>
                                    <select id="bv-metric-filter" class="themisdb-select">
                                        <option value="latency" <?php selected(($atts['metric'] ?? 'latency'), 'latency'); ?>><?php _e('Latency', 'themisdb-benchmark-visualizer'); ?></option>
                                        <option value="throughput" <?php selected(($atts['metric'] ?? 'latency'), 'throughput'); ?>><?php _e('Throughput', 'themisdb-benchmark-visualizer'); ?></option>
                                        <option value="memory" <?php selected(($atts['metric'] ?? 'latency'), 'memory'); ?>><?php _e('Memory', 'themisdb-benchmark-visualizer'); ?></option>
                                    </select>
                                    <select id="bv-chart-type" class="themisdb-select">
                                        <option value="bar" <?php selected(($atts['chart_type'] ?? 'bar'), 'bar'); ?>><?php _e('Bar', 'themisdb-benchmark-visualizer'); ?></option>
                                        <option value="line" <?php selected(($atts['chart_type'] ?? 'bar'), 'line'); ?>><?php _e('Line', 'themisdb-benchmark-visualizer'); ?></option>
                                        <option value="area" <?php selected(($atts['chart_type'] ?? 'bar'), 'area'); ?>><?php _e('Area', 'themisdb-benchmark-visualizer'); ?></option>
                                        <option value="radar" <?php selected(($atts['chart_type'] ?? 'bar'), 'radar'); ?>><?php _e('Radar', 'themisdb-benchmark-visualizer'); ?></option>
                                    </select>
                                    <select id="bv-engine-filter" class="themisdb-select">
                                        <option value="all"><?php _e('Alle Engines', 'themisdb-benchmark-visualizer'); ?></option>
                                    </select>
                                    <select id="bv-top-n" class="themisdb-select">
                                        <option value="10" <?php selected($default_top_n, '10'); ?>><?php _e('Top 10', 'themisdb-benchmark-visualizer'); ?></option>
                                        <option value="20" <?php selected($default_top_n, '20'); ?>><?php _e('Top 20', 'themisdb-benchmark-visualizer'); ?></option>
                                        <option value="30" <?php selected($default_top_n, '30'); ?>><?php _e('Top 30', 'themisdb-benchmark-visualizer'); ?></option>
                                        <option value="50" <?php selected($default_top_n, '50'); ?>><?php _e('Top 50', 'themisdb-benchmark-visualizer'); ?></option>
                                    </select>
                                    <input id="bv-task-search" class="themisdb-select" type="search" placeholder="<?php esc_attr_e('Task suchen…', 'themisdb-benchmark-visualizer'); ?>" />

                                    <div class="themisdb-split-button" data-split-target="refresh-menu">
                                        <button id="bv-refresh-data" class="themisdb-btn-primary themisdb-split-button__primary" type="button"><?php _e('Refresh', 'themisdb-benchmark-visualizer'); ?></button>
                                        <button class="themisdb-btn-primary themisdb-split-button__toggle" type="button" aria-label="<?php esc_attr_e('Mehr Aktionen', 'themisdb-benchmark-visualizer'); ?>" aria-expanded="false" aria-haspopup="true" aria-controls="bv-refresh-menu"><?php esc_html_e('▾', 'themisdb-benchmark-visualizer'); ?></button>
                                    </div>

                                    <div class="themisdb-split-button themisdb-split-button--compact" data-split-target="export-menu">
                                        <button id="bv-export-csv" class="themisdb-btn-secondary themisdb-split-button__primary" type="button"><?php _e('CSV', 'themisdb-benchmark-visualizer'); ?></button>
                                        <button class="themisdb-btn-secondary themisdb-split-button__toggle" type="button" aria-label="<?php esc_attr_e('Export Optionen', 'themisdb-benchmark-visualizer'); ?>" aria-expanded="false" aria-haspopup="true" aria-controls="bv-export-menu"><?php esc_html_e('▾', 'themisdb-benchmark-visualizer'); ?></button>
                                        <div id="bv-export-menu" class="themisdb-split-button__menu" role="menu" aria-label="<?php esc_attr_e('Export Optionen', 'themisdb-benchmark-visualizer'); ?>">
                                            <button id="bv-export-pdf" class="themisdb-btn-secondary" type="button" role="menuitem"><?php _e('PDF', 'themisdb-benchmark-visualizer'); ?></button>
                                            <button id="bv-print" class="themisdb-btn-secondary" type="button" role="menuitem"><?php _e('Print', 'themisdb-benchmark-visualizer'); ?></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="themisdb-benchmark-panel-title"><?php _e('Benchmark Overview', 'themisdb-benchmark-visualizer'); ?></div>
                    </header>

                    <div class="themisdb-chart-container" style="--themisdb-chart-ratio: <?php echo esc_attr($chart_ratio_css); ?>;">
                        <canvas id="themisdb-benchmark-chart" aria-label="Benchmark chart"></canvas>
                    </div>

                    <?php if ($show_mermaid) : ?>
                        <div class="themisdb-mermaid-section">
                            <div class="themisdb-mermaid-header"><?php _e('Architecture snapshot', 'themisdb-benchmark-visualizer'); ?></div>
                            <div id="themisdb-benchmark-mermaid" class="themisdb-mermaid-diagram"></div>
                        </div>
                    <?php endif; ?>

                    <div id="bv-loading" class="themisdb-loading" style="display: none;">
                        <div class="themisdb-spinner"></div>
                        <p><?php _e('Loading benchmark data...', 'themisdb-benchmark-visualizer'); ?></p>
                    </div>

                    <?php if ($show_nav) : ?>
                        <div class="themisdb-benchmark-nav themisdb-benchmark-nav--overlay">
                            <button class="themisdb-benchmark-arrow" data-bv-dir="prev" type="button" aria-label="<?php esc_attr_e('Vorherige Engine', 'themisdb-benchmark-visualizer'); ?>">‹</button>
                            <div id="bv-slider-dots" class="themisdb-benchmark-dots"></div>
                            <button class="themisdb-benchmark-arrow" data-bv-dir="next" type="button" aria-label="<?php esc_attr_e('Nächste Engine', 'themisdb-benchmark-visualizer'); ?>">›</button>
                        </div>
                    <?php endif; ?>
                </div>

                <?php $stats_mode = isset($atts['stats_mode']) ? sanitize_key((string) $atts['stats_mode']) : 'toolbar'; ?>
                <?php if ($show_stats && $stats_mode !== 'hidden') : ?>
                    <footer class="themisdb-benchmark-meta themisdb-benchmark-meta--<?php echo esc_attr($stats_mode); ?>" data-stats-mode="<?php echo esc_attr($stats_mode); ?>" role="status">
                        <div class="themisdb-benchmark-stat">
                            <span><?php _e('Engine', 'themisdb-benchmark-visualizer'); ?></span>
                            <strong id="bv-slide-title">-</strong>
                        </div>
                        <div class="themisdb-benchmark-stat">
                            <span><?php _e('Durchschnitt', 'themisdb-benchmark-visualizer'); ?></span>
                            <strong id="bv-summary-avg">-</strong>
                        </div>
                        <div class="themisdb-benchmark-stat">
                            <span><?php _e('Bestwert', 'themisdb-benchmark-visualizer'); ?></span>
                            <strong id="bv-summary-fastest">-</strong>
                        </div>
                        <div class="themisdb-benchmark-stat">
                            <span><?php _e('Tasks sichtbar', 'themisdb-benchmark-visualizer'); ?></span>
                            <strong id="bv-summary-count">0</strong>
                        </div>
                    </footer>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if ($show_stats || $show_table) : ?>
    <div class="themisdb-benchmark-grid">
        <?php if ($show_stats) : ?>
            <div id="bv-stats-summary" class="themisdb-stats-summary"></div>
        <?php endif; ?>
        <?php if ($show_table) : ?>
            <div id="bv-results-table" class="themisdb-results-table"></div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($show_stats || $show_table || $show_mermaid) : ?>
    <div class="themisdb-insights">
        <h3><?php _e('Insights', 'themisdb-benchmark-visualizer'); ?></h3>
        <div id="bv-insights" class="themisdb-insights-content"></div>
    </div>
<?php endif; ?>
