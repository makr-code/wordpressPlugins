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
?>

<div class="themisdb-benchmark-wrapper themisdb-benchmark-wrapper--minimal">
    <div class="themisdb-benchmark-header">
        <h2>Benchmarks</h2>
    </div>

    <?php if ($show_chart) : ?>
        <div class="themisdb-benchmark themisdb-benchmark--minimal">
            <div class="themisdb-benchmark-canvas">
                <canvas id="themisdb-benchmark-chart" aria-label="Benchmark chart"></canvas>
            </div>
            <div class="themisdb-benchmark-focus" id="themisdb-benchmark-focus" aria-live="polite">
                <strong id="themisdb-benchmark-focus__title">Vector Search</strong>
                <span id="themisdb-benchmark-focus__value">2.30 ms</span>
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

<?php if ($show_mermaid) : ?>
    <div id="bv-loading" class="themisdb-loading" style="display: none;">
        <div class="themisdb-spinner"></div>
        <p><?php _e('Loading benchmark data...', 'themisdb-benchmark-visualizer'); ?></p>
    </div>
<?php endif; ?>

