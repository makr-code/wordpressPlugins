<?php
/**
 * Read-only Support/Order integration regression tests.
 * Usage: php integration-test.php C:\path\to\wordpress\wp-load.php
 */
if (PHP_SAPI !== 'cli' || empty($argv[1])) {
    exit("Run from CLI with the WordPress bootstrap path.\n");
}

$_SERVER['REQUEST_METHOD'] = 'GET';
require $argv[1];
require_once ABSPATH . 'wp-admin/includes/plugin.php';
set_error_handler(static function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
    return false;
});

if (in_array('--fixture', $argv, true)) {
    // Exercise the standalone fallback when the shared checkout module is absent.
    remove_shortcode('themisdb_cart_button');
    foreach (array('themisdb_v3_breadcrumbs', 'themisdb_v3_hero_context_nav') as $tag) {
        remove_shortcode($tag);
    }
    foreach (array('themisdb_support_portal', 'themisdb_order_flow', 'themisdb_my_orders', 'themisdb_my_contracts', 'themisdb_license_portal', 'themisdb_b2b_portal') as $tag) {
        add_shortcode($tag, static function () { return '<p>Read-only integration fixture</p>'; });
    }
    add_filter('themisdb_support_hub_access_policy', static function ($policy) {
        foreach ($policy as $key => &$area) {
            $area['visible'] = $key !== 'admin';
        }
        return $policy;
    });
    echo do_shortcode('[themisdb_support_hub]');
    exit;
}

$failures = 0;
$checks = 0;
function integration_check($condition, $message) {
    global $failures, $checks;
    $checks++;
    if (!$condition) {
        $failures++;
    }
    echo ($condition ? 'PASS ' : 'FAIL ') . $message . PHP_EOL;
}

wp_set_current_user(0);
$initial_level = ob_get_level();
ob_start();
echo 'outer-buffer-sentinel';
$html = do_shortcode('[themisdb_support_hub]');
$buffer_preserved = ob_get_level() === $initial_level + 1;
$outer = $buffer_preserved ? ob_get_clean() : '';
while (ob_get_level() > $initial_level) {
    ob_end_clean();
}
integration_check($buffer_preserved && $outer === 'outer-buffer-sentinel', 'Hub preserves the caller output buffer');
integration_check(strpos($html, 'themisdb-support-hub-wrap') !== false, 'Hub returns its markup');
integration_check(strpos($html, 'outer-buffer-sentinel') === false, 'Hub does not consume caller markup');
preg_match_all('/\bid="([^"]+)"/', $html, $ids);
integration_check(count($ids[1]) === count(array_unique($ids[1])), 'Guest hub does not duplicate form or tab IDs');
preg_match_all('/href="#(themisdb-[^"]+)"/', $html, $anchors);
integration_check(!array_diff($anchors[1], $ids[1]), 'Guest hub shortcuts target existing tabs');

$theme_shortcodes = array('themisdb_v3_breadcrumbs', 'themisdb_v3_hero_context_nav');
$saved_shortcodes = $shortcode_tags;
foreach ($theme_shortcodes as $tag) {
    remove_shortcode($tag);
}
ob_start();
ob_start();
$standalone_html = do_shortcode('[themisdb_support_hub]');
while (ob_get_level() > $initial_level) {
    ob_end_clean();
}
integration_check(!preg_match('/\[themisdb_v3_/', $standalone_html), 'Hub does not print unavailable theme shortcodes');
$all_areas = static function ($policy) {
    foreach ($policy as $key => &$area) {
        $area['visible'] = $key !== 'admin';
    }
    return $policy;
};
add_filter('themisdb_support_hub_access_policy', $all_areas);
foreach (array('themisdb_order_flow', 'themisdb_my_orders', 'themisdb_my_contracts') as $tag) {
    add_shortcode($tag, static function () { return '<p>Read-only order component</p>'; });
}
$shared_checkout = do_shortcode('[themisdb_support_hub]');
integration_check(strpos($shared_checkout, 'data-themisdb-open-cart') !== false, 'Hub opens shared cart when checkout module is active');
integration_check(strpos($shared_checkout, 'id="themisdb-order-overlay"') === false, 'Hub does not render a second order overlay');
remove_shortcode('themisdb_cart_button');
$legacy_checkout = do_shortcode('[themisdb_support_hub]');
integration_check(strpos($legacy_checkout, 'data-themisdb-open-order-overlay') !== false, 'Hub retains standalone order fallback');
integration_check(strpos($legacy_checkout, 'id="themisdb-order-overlay"') !== false, 'Legacy fallback still renders its order dialog');
foreach (array('themisdb_license_portal', 'themisdb_order_flow', 'themisdb_my_orders', 'themisdb_my_contracts', 'themisdb_b2b_portal') as $tag) {
    remove_shortcode($tag);
}
$without_order = do_shortcode('[themisdb_support_hub]');
integration_check(!preg_match('/role="tab"[^>]+aria-controls="themisdb-support-hub-panel-(licenses|orders|b2b)"/', $without_order), 'Hub omits tabs for unavailable Order modules');
preg_match_all('/\bid="([^"]+)"/', $without_order, $module_ids);
preg_match_all('/href="#(themisdb-[^"]+)"/', $without_order, $module_anchors);
integration_check(!array_diff($module_anchors[1], $module_ids[1]), 'Hub shortcuts omit unavailable Order modules');
remove_filter('themisdb_support_hub_access_policy', $all_areas);
$shortcode_tags = $saved_shortcodes;

global $wp_query, $wp_the_query, $post;
$saved_query = $wp_query;
$saved_main_query = $wp_the_query;
$saved_post = $post;
$post = new WP_Post((object) array(
    'ID' => 0, 'post_name' => 'support', 'post_type' => 'page',
    'post_status' => 'publish', 'post_content' => '', 'filter' => 'raw',
));
$wp_query = new WP_Query();
$wp_query->is_page = true;
$wp_query->is_singular = true;
$wp_query->in_the_loop = true;
$wp_query->queried_object = $post;
$wp_the_query = $wp_query;
integration_check(themisdb_support_should_render_frontend_ui($post), 'Dedicated support page is detected');
$embedded = themisdb_support_maybe_embed_portal_into_page('Page content');
integration_check(strpos($embedded, 'themisdb-support-license-form') !== false, 'Dedicated page embeds portal without a theme bridge');
$post->post_content = '[themisdb_support_portal]';
integration_check(themisdb_support_maybe_embed_portal_into_page('Existing portal') === 'Existing portal', 'Explicit portal is not duplicated');

$post->post_type = 'post';
$wp_query->is_page = false;
$wp_query->is_single = true;
integration_check(themisdb_support_should_render_frontend_ui($post), 'Support shortcodes in posts receive frontend assets');
$post->post_content = '';
integration_check(!themisdb_support_should_render_frontend_ui($post), 'Unrelated posts do not receive support assets');
$wp_query = $saved_query;
$wp_the_query = $saved_main_query;
$post = $saved_post;

if (!class_exists('ThemisDB_Order_Admin')) {
    require_once WP_PLUGIN_DIR . '/themisdb-order-request/includes/class-admin.php';
}
global $menu, $submenu, $admin_page_hooks, $_registered_pages, $_parent_pages;
$menu = $submenu = $admin_page_hooks = $_registered_pages = $_parent_pages = array();
$administrators = get_users(array('role' => 'administrator', 'number' => 1, 'fields' => 'ID'));
if (empty($administrators)) {
    exit("An existing administrator is required for in-process menu registration.\n");
}
wp_set_current_user((int) $administrators[0]);
$order_admin = new ThemisDB_Order_Admin();
$order_admin->add_admin_menu();
integration_check(!empty($submenu['themisdb-order-dashboard']), 'Order admin menu registers for administrator');
foreach ($submenu['themisdb-order-dashboard'] as $item) {
    $hook = get_plugin_page_hookname($item[2], 'themisdb-order-dashboard');
    wp_dequeue_script('themisdb-order-admin-script');
    wp_dequeue_style('themisdb-order-admin-style');
    themisdb_order_request_admin_enqueue_scripts($hook);
    integration_check(
        wp_script_is('themisdb-order-admin-script', 'enqueued') && wp_style_is('themisdb-order-admin-style', 'enqueued'),
        'Order admin assets: ' . $item[2]
    );
}
wp_dequeue_script('themisdb-order-admin-script');
themisdb_order_request_admin_enqueue_scripts('dashboard_page_unrelated');
integration_check(!wp_script_is('themisdb-order-admin-script', 'enqueued'), 'Order admin script stays off unrelated pages');

require_once ABSPATH . 'wp-admin/includes/admin.php';
$support_admin = new ThemisDB_Support_Admin();
$method = new ReflectionMethod($support_admin, 'get_assignable_agents');
$method->setAccessible(true);
$agents = $method->invoke($support_admin);
integration_check(in_array((int) $administrators[0], array_column($agents, 'id'), true), 'Existing administrator can be assigned a support ticket');

$read_only = static function ($query) {
    if (!preg_match('/^\s*(SELECT|SHOW|DESCRIBE|EXPLAIN)\b/i', $query)) {
        throw new RuntimeException('Read-only test blocked a database write via ' . implode(' > ', array_column(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 12), 'function')));
    }
    return $query;
};
add_filter('query', $read_only);
add_filter('update_user_metadata', static function ($check, $user_id, $key) {
    return strpos($key, 'themisdb_list_prefs_') === 0 ? true : $check;
}, 10, 3);
add_filter('pre_wp_mail', static function () { throw new RuntimeException('Read-only test blocked mail'); });
add_filter('pre_http_request', static function () { throw new RuntimeException('Read-only test blocked HTTP'); });
foreach (array(
    array($support_admin, 'tickets_page', 'themisdb-support'),
    array($order_admin, 'orders_page', 'themisdb-orders'),
    array($order_admin, 'contracts_page', 'themisdb-contracts'),
    array($order_admin, 'licenses_page', 'themisdb-licenses'),
) as $screen) {
    $_GET = array('page' => $screen[2]);
    $_POST = array();
    set_current_screen($screen[2]);
    $wpdb->last_error = '';
    $level = ob_get_level();
    ob_start();
    try {
        $screen[0]->{$screen[1]}();
        $output = ob_get_clean();
        integration_check(trim($output) !== '' && $wpdb->last_error === '', 'Read-only admin screen: ' . $screen[2]);
    } catch (Throwable $error) {
        while (ob_get_level() > $level) {
            ob_end_clean();
        }
        integration_check(false, 'Read-only admin screen: ' . $screen[2] . ' - ' . $error->getMessage());
    }
}
remove_filter('query', $read_only);

echo "$checks checks; $failures failures.\n";
exit($failures ? 1 : 0);
