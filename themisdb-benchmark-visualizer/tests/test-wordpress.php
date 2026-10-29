<?php
if (PHP_SAPI !== 'cli' || empty($argv[1])) {
    exit("Usage: php test-wordpress.php <wp-load.php>\n");
}
require $argv[1];
$plugin = ThemisDB_Benchmark_Visualizer::get_instance();
$checks = 0;
function bv_check($condition, $message) {
    global $checks;
    if (!$condition) {
        throw new RuntimeException("FAIL: $message");
    }
    ++$checks;
    echo "PASS: $message\n";
}
function bv_call($method, ...$args) {
    global $plugin;
    $reflection = new ReflectionMethod($plugin, $method);
    $reflection->setAccessible(true);
    return $reflection->invoke($plugin, ...$args);
}
function bv_row($name, $value, $engine = 'ThemisDB', $metric = 'latency', $unit = 'ms') {
    return array('benchmark_name' => $name, 'metric_value' => $value, 'metric_name' => $metric, 'metric_unit' => $unit, 'workload' => 'vector_search', 'engine' => $engine);
}
$rows = array(bv_row('BM_SIMD_L2/64', 0.000002), bv_row('BM_Scalar_L2/64', 0.000008), bv_row('BM_SIMD_L2/128', 0.000004));
$data = bv_call('parse_catalog_records', $rows, 'latency', 'vector_search');
bv_check(count($data['labels']) === 3, 'Operations and numeric parameters do not collapse into one task');
bv_check(min($data['datasets'][0]['data']) > 0, 'Sub-microsecond latency is not rounded to zero');
bv_check(bv_call('normalize_metric_value', 2000, 'us', 'latency') === 2.0, 'Latency units normalize to ms');
bv_check(bv_call('normalize_metric_value', 1048576, 'bytes', 'memory') === 1.0, 'Memory normalizes to MiB');
bv_check(bv_call('normalize_metric_value', 50, 'MB/s', 'memory') === null, 'Bandwidth is not mislabeled as memory usage');
bv_check(bv_call('extract_metric_value', array('bytes_per_second' => 999), 'memory') === null, 'Legacy bandwidth is not used as memory occupancy');
bv_check(bv_call('extract_metric_value', array(), 'latency') === null, 'Missing latency remains absent');
bv_check(bv_call('normalize_metric_value', -1, 'ms', 'latency') === null, 'Negative values are excluded');
bv_check(bv_call('normalize_metric_value', 1e308, 's', 'latency') === null, 'Unit conversion overflow is excluded');
bv_check(bv_call('normalize_metric_value', 1, array(), 'latency') === null, 'Malformed unit does not throw');
$malformed = array(bv_row('valid', 0), array('benchmark_name' => array(), 'engine' => array(), 'metric_name' => 'latency'));
$data = bv_call('parse_catalog_records', $malformed, 'latency', 'all');
bv_check($data['datasets'][0]['data'] === array(0.0), 'Malformed records are excluded, genuine zero is retained');
$rows[] = bv_row('BM_SIMD_L2/64', 0.1, 'PostgreSQL');
$data = bv_call('parse_catalog_records', $rows, 'latency', 'vector_search');
bv_check(count($data['labels']) === 4 && $data['summary']['comparable_task_count'] === 0, 'Matching names without protocol are isolated, never a cross-engine comparison');
$protocol = json_decode(file_get_contents(__DIR__ . '/publication-fixture.json'), true);
$controlled = array(
    array_merge(bv_row('vector-query', 1), array('publication' => $protocol)),
    array_merge(bv_row('vector-query', 2, 'PostgreSQL'), array('publication' => array_merge($protocol, array('engine_version' => 'pg-fixture', 'index_config' => 'pg-index-fixture')))),
);
$data = bv_call('parse_catalog_records', $controlled, 'latency', 'vector_search');
bv_check(count($data['labels']) === 1 && $data['summary']['comparable_task_count'] === 1, 'Complete matching vector protocol permits comparison across engines');
foreach (array('hardware_fingerprint' => str_repeat('d', 64), 'statistic' => 'p99', 'dataset_sha256' => str_repeat('e', 64), 'concurrency' => 8, 'recall_target' => 0.96, 'run_id' => 'other-run') as $field => $value) {
    $different = $controlled;
    $different[1]['publication'][$field] = $value;
    $data = bv_call('parse_catalog_records', $different, 'latency', 'vector_search');
    bv_check(count($data['labels']) === 2 && $data['summary']['comparable_task_count'] === 0, "Protocol mismatch isolates $field");
}
$different = $controlled;
$different[1]['publication']['recall_achieved'] = 0.5;
$data = bv_call('parse_catalog_records', $different, 'latency', 'vector_search');
bv_check($data['summary']['comparable_task_count'] === 0 && $data['summary']['unverified_records'] === 1, 'Below-target recall is not comparison eligible');
bv_check(is_wp_error(bv_call('parse_catalog_records', array($controlled[0], $controlled[0]), 'latency', 'vector_search')), 'Duplicate run statistics cause an explicit error, never averaged percentiles');
$incomplete = $protocol;
unset($incomplete['license']);
bv_check(in_array('license', bv_call('publication_errors', $incomplete, 'latency'), true), 'Publication requires a declared reuse license');
bv_check(in_array('statistic does not match metric', bv_call('publication_errors', $protocol, 'throughput'), true), 'Percentile latency cannot masquerade as throughput');
$rows = array();
for ($i = 1; $i <= 60; ++$i) {
    $rows[] = bv_row('BM_Task/' . $i, $i);
}
$data = bv_call('parse_catalog_records', $rows, 'latency', 'vector_search');
bv_check(count($data['labels']) === 60, 'Server does not silently truncate all tasks to thirty');
$data = bv_call('parse_catalog_records', array(bv_row('task', 1, 'ThemisDB', 'latency', 'seconds')), 'latency', 'vector_search');
bv_check(empty($data['labels']), 'Unknown units produce no fabricated results');
$data = bv_call('parse_catalog_records', array(
    array_merge(bv_row('task', 1), array('environment' => 'host A')),
    array_merge(bv_row('task', 2), array('environment' => 'host B')),
), 'latency', 'vector_search');
bv_check(count($data['labels']) === 2, 'Distinct environments are not averaged together');
$missing = function () { return 'C:\\nonexistent-bv-test-catalog.json'; };
add_filter('pre_option_themisdb_bv_catalog_path', $missing);
bv_check(is_wp_error(bv_call('load_catalog_benchmark_data', 'vector_search', 'latency')), 'Missing catalog is an explicit error, never demo data');
remove_filter('pre_option_themisdb_bv_catalog_path', $missing);
$fixture = tempnam(sys_get_temp_dir(), 'bv-measurement-');
$fixture_filter = function () use ($fixture) { return $fixture; };
add_filter('pre_option_themisdb_bv_catalog_path', $fixture_filter);
try {
    file_put_contents($fixture, '{"records":[]}');
    bv_check(bv_call('load_catalog_benchmark_data', 'all', 'latency')['labels'] === array(), 'Valid empty catalog remains empty');
    file_put_contents($fixture, '{broken JSON');
    bv_check(is_wp_error(bv_call('load_catalog_benchmark_data', 'all', 'latency')), 'Malformed catalog is an explicit error');
    file_put_contents($fixture, '{"benchmarks":false}');
    bv_check(is_wp_error(bv_call('parse_benchmark_files', array($fixture), 'latency')), 'Malformed legacy measurement list is rejected');
    file_put_contents($fixture, wp_json_encode(array('benchmarks' => array(
        array('name' => 'BM_Task/1', 'real_time' => 1, 'time_unit' => 'ns'),
        array('name' => 'BM_Task/1', 'real_time' => 999, 'time_unit' => 'ms', 'run_type' => 'aggregate'),
        array('name' => 'BM_Task/2', 'real_time' => 50, 'time_unit' => 'ms', 'error_occurred' => true),
        array('name' => array()),
    ))));
    $legacy = bv_call('parse_benchmark_files', array($fixture), 'latency');
    bv_check(count($legacy['labels']) === 1 && $legacy['datasets'][0]['data'][0] === 0.000001, 'Legacy aggregate and error rows do not contaminate measurements');
} finally {
    remove_filter('pre_option_themisdb_bv_catalog_path', $fixture_filter);
    unlink($fixture);
}
bv_check(is_wp_error(bv_call('load_github_benchmark_data', 'vector_search', 'latency')), 'Unimplemented GitHub source reports an error instead of reading local files');
$source_filter = function () { return 'catalog'; };
add_filter('pre_option_themisdb_bv_data_source', $source_filter);
bv_check(!is_wp_error(bv_call('load_benchmark_data', 'vector_search', 'latency')), 'Catalog adapter is reachable through configured source dispatch');
remove_filter('pre_option_themisdb_bv_data_source', $source_filter);
$saved_source = get_option('themisdb_bv_data_source', 'catalog');
$demo_filter = function () { return 'demo'; };
add_filter('pre_option_themisdb_bv_data_source', $demo_filter);
try {
    foreach (array('vector_search', 'graph_traversal', 'transaction') as $category) {
        foreach (array('latency', 'throughput', 'memory') as $metric) {
            $demo = bv_call('load_benchmark_data', $category, $metric);
            bv_check($demo['source']['type'] === 'demo' && count($demo['labels']) === 60 && count($demo['datasets']) === 3, "Demo provides sixty $category/$metric tasks and three fictional engines");
            bv_check($demo['summary']['comparable_task_count'] === 0 && $demo['summary']['synthetic'], 'Synthetic tasks never become verified publication comparisons');
        }
    }
    $again = bv_call('load_benchmark_data', 'transaction', 'memory');
    bv_check($demo === $again, 'Demo measurements are deterministic');
    bv_check(in_array(null, $demo['datasets'][0]['data'], true), 'Demo exercises genuine missing cells');
    bv_check(strpos($plugin->render_visualizer(array('show_controls' => 'false')), 'class="themisdb-benchmark-demo"') !== false, 'Demo warning remains visible without controls');
    $raw_demo = themisdb_bv_demo_records();
    $normal = bv_call('parse_catalog_records', $raw_demo, 'latency', 'vector_search');
    bv_check($normal['summary']['comparable_task_count'] === 0 && !$normal['summary']['synthetic'], 'Synthetic records outside explicit demo mode cannot bypass publication rules');
} finally {
    remove_filter('pre_option_themisdb_bv_data_source', $demo_filter);
}
bv_check(get_option('themisdb_bv_data_source', 'catalog') === $saved_source, 'Tests do not change the selected WordPress data source');
$source_filter = function () { return 'invalid'; };
add_filter('pre_option_themisdb_bv_data_source', $source_filter);
bv_check(is_wp_error(bv_call('load_benchmark_data', 'vector_search', 'latency')), 'Unknown source is not silently replaced by catalog data');
remove_filter('pre_option_themisdb_bv_data_source', $source_filter);
$markup = $plugin->render_visualizer(array('category' => 'all', 'metric' => 'memory', 'chart_type' => 'radar', 'aspect_ratio' => '0:0', 'show_chart' => 'false', 'show_table' => 'true', 'show_header' => 'false'));
bv_check(strpos($markup, '<canvas') === false && strpos($markup, '<h2') === false, 'Shortcode visibility flags are honored independently');
preg_match('/data-bv-config="([^"]+)"/', $markup, $match);
$config = json_decode(html_entity_decode($match[1], ENT_QUOTES), true);
bv_check($config['settings']['default_metric'] === 'memory' && $config['settings']['default_category'] === 'all' && $config['settings']['default_chart_type'] === 'radar', 'Per-shortcode configuration survives without controls');
$markup .= $plugin->render_visualizer(array());
bv_check(strpos(wpautop($markup), '<br') === false, 'Later WordPress paragraph formatting does not insert grid rows between controls');
preg_match_all('/\bid="([^"]+)"/', $markup, $ids);
bv_check(count($ids[1]) === count(array_unique($ids[1])), 'Multiple visualizers have unique IDs');
bv_check(wp_style_is('themisdb-bv-style', 'enqueued') && wp_script_is('themisdb-bv-script', 'enqueued'), 'Shortcodes enqueue their own presentation outside the front page');
bv_check(strpos(wp_scripts()->registered['themisdb-bv-chart']->src, THEMISDB_BV_PLUGIN_URL) === 0, 'Chart library is bundled locally');
foreach (array('bar-horizontal', 'bar-vertical', 'bar-isometric', 'line', 'line-dot', 'stepped', 'area', 'radar') as $type) {
    bv_check($plugin->sanitize_chart_type_option($type) === $type, "Chart type $type accepted in settings and shortcodes");
    $html = $plugin->render_visualizer(array('chart_type' => $type, 'show_controls' => 'false'));
    preg_match('/data-bv-config="([^"]+)"/', $html, $match);
    $config = json_decode(html_entity_decode($match[1], ENT_QUOTES), true);
    bv_check($config['settings']['default_chart_type'] === $type, "Chart type $type survives hidden controls");
}
echo "$checks WordPress checks passed.\n";
