<?php
if (!defined('ABSPATH')) {
    exit;
}
$id = wp_unique_id('themisdb-bv-');
$config = $this->get_frontend_config($atts);
$category_labels = array_merge(array('all' => __('Alle Kategorien', 'themisdb-benchmark-visualizer')), $config['settings']['category_labels']);
$metric_labels = array('latency' => __('Latenz', 'themisdb-benchmark-visualizer'), 'throughput' => __('Durchsatz', 'themisdb-benchmark-visualizer'), 'memory' => __('Speicherbelegung', 'themisdb-benchmark-visualizer'));
$top_choices = array('10' => '10', '20' => '20', '30' => '30', '50' => '50', 'all' => __('Alle', 'themisdb-benchmark-visualizer'));
$top_choices[$config['settings']['default_top_n']] = $config['settings']['default_top_n'] === 'all' ? __('Alle', 'themisdb-benchmark-visualizer') : $config['settings']['default_top_n'];
?>
<div class="themisdb-benchmark-wrapper" data-bv-config="<?php echo esc_attr(wp_json_encode($config)); ?>" data-bv-theme="<?php echo $config['settings']['chart_theme'] === 'dark' ? 'dark' : 'light'; ?>" data-bv-state="loading">
    <?php if ($config['demo']) : ?>
    <p class="themisdb-benchmark-demo" role="note"><?php echo esc_html($config['labels']['demo']); ?></p>
    <?php endif; ?>
    <?php if ($atts['show_header']) : ?>
        <h2><?php esc_html_e('Performance-Benchmarks', 'themisdb-benchmark-visualizer'); ?></h2>
    <?php endif; ?>
    <div class="themisdb-benchmark-toolbar">
        <strong data-bv="title"><?php echo esc_html($metric_labels[$atts['metric']]); ?></strong>
        <?php if ($atts['show_controls']) : ?>
        <details class="themisdb-benchmark-options">
            <summary><?php esc_html_e('Diagramm-Optionen', 'themisdb-benchmark-visualizer'); ?></summary>
            <div class="themisdb-benchmark-controls">
                <?php foreach (array(
                    'category' => array(__('Kategorie', 'themisdb-benchmark-visualizer'), $category_labels, $atts['category']),
                    'metric' => array(__('Metrik', 'themisdb-benchmark-visualizer'), $metric_labels, $atts['metric']),
                    'chart-type' => array(__('Diagramm', 'themisdb-benchmark-visualizer'), $this->get_chart_type_labels(), $atts['chart_type']),
                    'task-labels' => array(__('Testnamen im Diagramm', 'themisdb-benchmark-visualizer'), array('hidden' => __('Ausblenden', 'themisdb-benchmark-visualizer'), 'visible' => __('Einblenden', 'themisdb-benchmark-visualizer')), 'hidden'),
                    'engine' => array(__('Engine', 'themisdb-benchmark-visualizer'), array('all' => __('Alle Engines', 'themisdb-benchmark-visualizer')), 'all'),
                    'scale' => array(__('Werteskala', 'themisdb-benchmark-visualizer'), array('linear' => __('Linear', 'themisdb-benchmark-visualizer'), 'logarithmic' => __('Logarithmisch', 'themisdb-benchmark-visualizer')), 'linear'),
                    'sort' => array(__('Sortierung', 'themisdb-benchmark-visualizer'), array('best' => __('Beste Werte zuerst', 'themisdb-benchmark-visualizer'), 'worst' => __('Schlechteste Werte zuerst', 'themisdb-benchmark-visualizer'), 'name' => __('Testname A-Z', 'themisdb-benchmark-visualizer'), 'source' => __('Quellreihenfolge', 'themisdb-benchmark-visualizer')), 'best'),
                    'reference' => array(__('Referenz-Engine (Sortierung und Faktoren)', 'themisdb-benchmark-visualizer'), array('auto' => __('Automatisch', 'themisdb-benchmark-visualizer')), 'auto'),
                    'top-n' => array(__('Anzahl Testaufgaben', 'themisdb-benchmark-visualizer'), $top_choices, $config['settings']['default_top_n']),
                ) as $key => $control) : ?>
                <label for="<?php echo esc_attr($id . $key); ?>"><?php echo esc_html($control[0]); ?></label>
                <select id="<?php echo esc_attr($id . $key); ?>" data-bv="<?php echo esc_attr($key); ?>">
                    <?php foreach ($control[1] as $value => $label) : ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected((string) $control[2], (string) $value); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php endforeach; ?>
                <label for="<?php echo esc_attr($id . 'search'); ?>"><?php esc_html_e('Testaufgabe suchen', 'themisdb-benchmark-visualizer'); ?></label>
                <input id="<?php echo esc_attr($id . 'search'); ?>" data-bv="search" type="search" />
                <button data-bv="refresh" type="button"><?php esc_html_e('Neu laden', 'themisdb-benchmark-visualizer'); ?></button>
                <button data-bv="csv" type="button"><?php esc_html_e('CSV exportieren', 'themisdb-benchmark-visualizer'); ?></button>
                <button data-bv="print" type="button"><?php esc_html_e('Drucken / PDF', 'themisdb-benchmark-visualizer'); ?></button>
            </div>
        </details>
        <?php endif; ?>
    </div>
    <p data-bv="status" role="status" aria-live="polite"><?php esc_html_e('Messdaten werden geladen ...', 'themisdb-benchmark-visualizer'); ?></p>
    <p data-bv="source" class="themisdb-benchmark-source"></p>
    <p data-bv="analysis-note" class="themisdb-benchmark-source" role="status" hidden></p>
    <p data-bv="chart-note" class="themisdb-benchmark-source" hidden><?php esc_html_e('Isometrische 2.5D-Ansicht: Die Vorderkante zeigt den Messwert. Die Tiefe ist rein dekorativ, keine dritte Datenachse. Exakte Werte stehen im Tooltip und in der Tabelle.', 'themisdb-benchmark-visualizer'); ?></p>
    <?php if ($atts['show_chart']) : ?>
    <div class="themisdb-chart-container" style="--themisdb-chart-ratio: <?php echo esc_attr($atts['aspect_ratio']); ?>;">
        <canvas data-bv="chart" role="img" aria-label="<?php esc_attr_e('Benchmark-Diagramm; Messwerte stehen auch in der Datentabelle.', 'themisdb-benchmark-visualizer'); ?>"></canvas>
    </div>
    <?php endif; ?>
    <?php if ($atts['show_nav']) : ?>
    <div data-bv="nav" class="themisdb-benchmark-nav" hidden>
        <button data-bv="previous" type="button" aria-label="<?php esc_attr_e('Vorherige Engine hervorheben', 'themisdb-benchmark-visualizer'); ?>">&lsaquo;</button>
        <span data-bv="active-engine"></span>
        <button data-bv="next" type="button" aria-label="<?php esc_attr_e('Naechste Engine hervorheben', 'themisdb-benchmark-visualizer'); ?>">&rsaquo;</button>
    </div>
    <?php endif; ?>
    <?php if ($atts['show_stats'] && $atts['stats_mode'] !== 'hidden') : ?>
    <div data-bv="stats" class="themisdb-benchmark-stats" data-stats-mode="<?php echo esc_attr($atts['stats_mode']); ?>"></div>
    <?php endif; ?>
    <details class="themisdb-benchmark-data" <?php echo $atts['show_table'] || !$atts['show_chart'] ? 'open' : ''; ?>>
        <summary><?php esc_html_e('Messwerte und Quellen', 'themisdb-benchmark-visualizer'); ?></summary>
        <div data-bv="table" class="themisdb-benchmark-table" tabindex="0" role="region" aria-label="<?php esc_attr_e('Benchmark-Messwerte', 'themisdb-benchmark-visualizer'); ?>"></div>
    </details>
    <?php if ($atts['show_mermaid']) : ?>
    <p><?php esc_html_e('Messdaten beschreiben keine Systemtopologie. Deshalb wird kein abgeleitetes Architekturdiagramm erzeugt.', 'themisdb-benchmark-visualizer'); ?></p>
    <?php endif; ?>
    <noscript><?php esc_html_e('Die interaktive Benchmark-Ansicht benoetigt JavaScript.', 'themisdb-benchmark-visualizer'); ?></noscript>
</div>
