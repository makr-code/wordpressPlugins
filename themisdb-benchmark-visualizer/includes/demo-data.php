<?php
if (!defined('ABSPATH')) {
    exit;
}

function themisdb_bv_demo_records() {
    $records = array();
    $engines = array('VectorFox Demo', 'GraphNest Demo', 'NimbusMock Demo');
    $categories = array('vector_search', 'graph_traversal', 'transaction');
    foreach ($categories as $category_index => $category) {
        for ($task = 1; $task <= 60; ++$task) {
            foreach ($engines as $engine_index => $engine) {
                foreach (array('latency', 'throughput', 'memory') as $metric) {
                    // Fixed gaps exercise missing cells without inventing measured zero.
                    if (($task + $engine_index * 3) % 17 === 0) {
                        continue;
                    }
                    $scale = 1 + (($task * 7 + $engine_index * 11) % 23) / 10;
                    $value = $metric === 'latency' ? pow(10, ($task % 5) - 3) * $scale :
                        ($metric === 'throughput' ? 100000 / ($task * $scale) : 64 + $task * 8 * $scale);
                    $records[] = array(
                        'benchmark_name' => 'DEMO ' . $category . '/scenario-' . $task . '/dimension-' . (64 * (1 + $task % 4)),
                        'engine' => $engine,
                        'workload' => $category,
                        'dataset_size' => (string) (1000 * (1 + $task % 10)),
                        'environment' => 'SYNTHETIC-DEMO-NOT-MEASURED',
                        'threads' => 1,
                        'metric_name' => $metric,
                        'metric_unit' => $metric === 'latency' ? 'ms' : ($metric === 'throughput' ? 'ops/s' : 'MiB'),
                        'metric_value' => $value,
                        'source_file' => 'synthetic-demo-v1',
                    );
                }
            }
        }
    }
    return $records;
}
