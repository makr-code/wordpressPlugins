<?php
/**
 * Run with: php tests\test-wordpress.php <absolute-path-to-wp-load.php>
 * Fixtures are isolated in an InnoDB transaction and always rolled back.
 */
if (PHP_SAPI !== 'cli' || empty($argv[1])) {
    fwrite(STDERR, "Pass the absolute path to wp-load.php.\n");
    exit(1);
}
require $argv[1];

global $wpdb;
$plugin = ThemisDB_Global_Network_Map::get_instance();
$homecalls = $wpdb->prefix . 'themisdb_gnm_homecalls';
$cache = $wpdb->prefix . 'themisdb_gnm_geo_cache';
$cache_key = 'themisdb_gnm_v3_nodes_1000';
$prefix = 'gnm-test-' . bin2hex(random_bytes(5));
$checks = 0;
$check = function ($condition, $message) use (&$checks) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $checks++;
    echo "PASS: $message\n";
};
$insert = function ($table, $values) use ($wpdb) {
    if ($wpdb->insert($table, $values) === false) {
        throw new RuntimeException('Fixture insert failed: ' . $wpdb->last_error);
    }
};

foreach (array($homecalls, $cache, $wpdb->options) as $table) {
    $status = $wpdb->get_row($wpdb->prepare('SHOW TABLE STATUS LIKE %s', $wpdb->esc_like($table)), ARRAY_A);
    if (!$status || strtoupper($status['Engine']) !== 'INNODB') {
        throw new RuntimeException('Transactional tests require InnoDB: ' . $table);
    }
}

delete_transient($cache_key);
$wpdb->query('START TRANSACTION');
try {
    $base = array(
        'instance_id' => $prefix . '-first', 'ip_address' => '192.0.2.201',
        'network' => $prefix, 'lat' => 0, 'lon' => 0, 'city' => 'Zero',
        'country' => 'ZZ', 'instances' => 2, 'load' => 35, 'is_active' => 1,
        'created_at' => '2026-01-01 10:00:00', 'updated_at' => '2026-01-01 10:00:00',
    );
    $insert($homecalls, $base);
    $insert($homecalls, array_merge($base, array('instances' => 3, 'updated_at' => '2026-01-02 10:00:00')));
    $insert($homecalls, array_merge($base, array('instance_id' => $prefix . '-nat', 'instances' => 4)));
    $insert($cache, array('ip_address' => $base['ip_address'], 'lat' => 10, 'lon' => 20, 'city' => 'Stale cache', 'geo_status' => 'resolved'));
    $insert($homecalls, array_merge($base, array('instance_id' => $prefix . '-cached', 'ip_address' => '192.0.2.202', 'network' => $prefix . '-cached', 'lat' => null, 'lon' => null)));
    $insert($cache, array('ip_address' => '192.0.2.202', 'lat' => 0, 'lon' => 0, 'city' => 'Cached zero', 'geo_status' => 'resolved'));
    $insert($homecalls, array_merge($base, array('instance_id' => $prefix . '-invalid', 'network' => $prefix . '-invalid', 'ip_address' => '192.0.2.203', 'lat' => 91)));
    $inactive = array_merge($base, array('instance_id' => $prefix . '-inactive', 'network' => $prefix . '-inactive'));
    $insert($homecalls, $inactive);
    $insert($homecalls, array_merge($inactive, array('is_active' => 0, 'updated_at' => '2026-01-03 10:00:00')));

    $nodes = $plugin->get_network_nodes('production', 1000);
    $check(!is_wp_error($nodes), 'Production DB query succeeds');
    $fixtures = array_values(array_filter($nodes, function ($node) use ($prefix) {
        return strpos($node['network'], $prefix) === 0;
    }));
    $by_network = array_column($fixtures, null, 'network');
    $check(count($fixtures) === 2, 'Invalid and deactivated instances are excluded');
    $check($by_network[$prefix]['instances'] === 7, 'Latest homecall per instance is counted; distinct NAT instances remain');
    $check($by_network[$prefix]['lat'] === 0.0 && $by_network[$prefix]['lon'] === 0.0, 'Fresh zero coordinates override stale geocache');
    $check($by_network[$prefix . '-cached']['city'] === 'Cached zero', 'Resolved geocache accepts equator and prime meridian');
    $check(empty($by_network[$prefix]['is_demo']), 'Production instances are not labeled demo');
    $demo = $plugin->get_network_nodes('demo', 2);
    $check(count($demo) === 2 && $demo[0]['is_demo'] === true, 'Explicit demo mode is capped and labeled');

    $method = new ReflectionMethod($plugin, 'sanitize_coordinate');
    $method->setAccessible(true);
    $check($method->invoke($plugin, 'invalid', 'lat') === null, 'Nonnumeric coordinates are not converted to zero');
    $check($method->invoke($plugin, 0, 'lat') === 0.0, 'Zero is a valid coordinate');

    $html = do_shortcode('[themisdb_global_network_map mode="production" height="420" show_controls="false" show_legend="false"]');
    $check(strpos($html, 'data-gnm-config=') !== false && strpos($html, 'mode=production') !== false, 'Shortcode supplies independent per-instance configuration');
    $check(strpos($html, 'data-map-mode=') === false && strpos($html, '__legend') === false, 'Shortcode control and legend flags are honored');
    $check(strpos($html, "\n") === false && strpos($html, 'id="themisdb') === false, 'Shortcode is wpautop-safe and has no duplicate global IDs');
    $check(strpos($html, '__menu') !== false && strpos($html, 'data-map-source') !== false, 'Read-only map keeps accessible menu and source overlay');
    $request = new WP_REST_Request('GET', '/themisdb-gnm/v1/nodes');
    $request->set_param('mode', 'invalid');
    $check(rest_do_request($request)->get_status() === 400, 'REST rejects unsupported data mode');
    $request = new WP_REST_Request('GET', '/themisdb-gnm/v1/nodes');
    $request->set_param('max_nodes', 1001);
    $check(rest_do_request($request)->get_status() === 400, 'REST rejects excessive node limit');
    echo "$checks WordPress integration checks passed.\n";
} finally {
    $wpdb->query('ROLLBACK');
    delete_transient($cache_key);
    wp_cache_delete('_transient_' . $cache_key, 'options');
}
