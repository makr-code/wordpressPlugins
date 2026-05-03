<?php
/**
 * Plugin Name: ThemisDB Graph Navigation
 * Plugin URI: https://github.com/makr-code/wordpressPlugins
 * Update URI: https://github.com/makr-code/wordpressPlugins
 */

/**

 * Plugin URI: https://github.com/makr-code/wordpressPlugins


 * Update URI: https://github.com/makr-code/wordpressPlugins
 * Description: Lagert die Graph-Navigation aus dem Theme in ein eigenstaendiges Plugin aus.
 * Version: 1.1.1
 * Author: ThemisDB Team
 * Author URI: https://github.com/makr-code/wordpressPlugins
 * License: MIT
 * Text Domain: themisdb-graph-navigation
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('THEMISDB_GRAPH_NAV_VERSION', '1.1.1');
define('THEMISDB_GRAPH_NAV_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('THEMISDB_GRAPH_NAV_PLUGIN_URL', plugin_dir_url(__FILE__));
define('THEMISDB_GRAPH_NAV_PLUGIN_FILE', __FILE__);

// Load updater class (prefer shared copy for uniform behavior across plugins).
if (!class_exists('ThemisDB_Plugin_Updater')) {
    $themisdb_updater_shared = dirname(THEMISDB_GRAPH_NAV_PLUGIN_DIR) . '/includes/class-themisdb-plugin-updater.php';
    $themisdb_updater_local = THEMISDB_GRAPH_NAV_PLUGIN_DIR . 'includes/class-themisdb-plugin-updater.php';

    if (file_exists($themisdb_updater_shared)) {
        require_once $themisdb_updater_shared;
    } elseif (file_exists($themisdb_updater_local)) {
        require_once $themisdb_updater_local;
    }
}

// Initialize automatic updates
if (class_exists('ThemisDB_Plugin_Updater')) {
    new ThemisDB_Plugin_Updater(
        THEMISDB_GRAPH_NAV_PLUGIN_FILE,
        'themisdb-graph-navigation',
        THEMISDB_GRAPH_NAV_VERSION
    );
}

/**
 * Plugin Name: ThemisDB Graph Navigation
 * Plugin URI: https://github.com/makr-code/wordpressPlugins
 * Update URI: https://github.com/makr-code/wordpressPlugins
 * Plugin activation callback.
 */
function themisdb_graph_nav_activate() {
    themisdb_graph_nav_flush_cache();
}
register_activation_hook(__FILE__, 'themisdb_graph_nav_activate');

/**
 * Plugin deactivation callback.
 */
function themisdb_graph_nav_deactivate() {
    themisdb_graph_nav_flush_cache();
}
register_deactivation_hook(__FILE__, 'themisdb_graph_nav_deactivate');

/**
 * Enqueue graph navigation assets.
 */
function themisdb_graph_navigation_enqueue_assets()
{
    if (is_admin()) {
        return;
    }

    $should_enqueue_frontend_script = apply_filters(
        'themisdb_graph_navigation_enqueue_frontend_script',
        true
    );

    if (!$should_enqueue_frontend_script) {
        return;
    }

    wp_enqueue_script(
        'themisdb-graph-navigation',
        THEMISDB_GRAPH_NAV_PLUGIN_URL . 'assets/js/graph-navigation.js',
        array(),
        THEMISDB_GRAPH_NAV_VERSION,
        true
    );

    $graph_payload = themisdb_graph_navigation_get_graph_data();
    $graph_payload = apply_filters('themisdb_graph_navigation_js_payload', $graph_payload);

    wp_localize_script('themisdb-graph-navigation', 'themisdbGraphData', $graph_payload);
}
add_action('wp_enqueue_scripts', 'themisdb_graph_navigation_enqueue_assets', 20);

/**
 * Build graph data for visualization.
 *
 * Results are cached in a Transient for one hour and invalidated automatically
 * when posts or taxonomy terms are created/updated/deleted.
 *
 * @return array{nodes: array, links: array}
 */
function themisdb_graph_navigation_get_graph_data()
{
    $cache_key = 'themisdb_graph_nav_data';
    $cached    = get_transient($cache_key);
    if (false !== $cached) {
        return apply_filters('themisdb_graph_navigation_data', $cached);
    }

    $nodes = array();
    $links = array();
    $link_index = array();

    $add_link = function($source, $target, $type, $extra = array()) use (&$links, &$link_index) {
        $key = $source . '|' . $target . '|' . $type;
        if (isset($link_index[$key])) {
            return;
        }
        $link_index[$key] = true;
        $links[] = array_merge(array(
            'source' => $source,
            'target' => $target,
            'type' => $type,
        ), $extra);
    };

    // Keep backward compatibility with previous filter names.
    $post_limit = apply_filters('themisdb_graph_post_limit', apply_filters('themisdb_graph_navigation_post_limit', 50));
    $page_limit = apply_filters('themisdb_graph_page_limit', apply_filters('themisdb_graph_navigation_page_limit', 30));

    $include_tags  = (bool) get_option('themisdb_graph_nav_include_tags', 1);
    $include_pages = (bool) get_option('themisdb_graph_nav_include_pages', 1);
    $cache_seconds = (int) get_option('themisdb_graph_nav_cache_seconds', HOUR_IN_SECONDS);

    $categories = get_categories(array(
        'hide_empty' => false,
    ));

    $tags = $include_tags ? get_tags(array('hide_empty' => false)) : array();

    $posts = get_posts(array(
        'numberposts' => $post_limit,
        'post_status' => 'publish',
        'post_type' => 'post',
    ));

    $pages = get_posts(array(
        'numberposts' => $include_pages ? $page_limit : 0,
        'post_status' => 'publish',
        'post_type' => 'page',
    ));

    $content_node_by_wp_id = array();
    foreach ($posts as $post) {
        $content_node_by_wp_id[(int) $post->ID] = 'post_' . $post->ID;
    }
    foreach ($pages as $page) {
        $content_node_by_wp_id[(int) $page->ID] = 'page_' . $page->ID;
    }

    $external_nodes = array();

    $nodes[] = array(
        'id' => 'home',
        'label' => get_bloginfo('name'),
        'url' => home_url('/'),
        'type' => 'home',
        'level' => 0,
    );

    foreach ($categories as $category) {
        $nodes[] = array(
            'id' => 'cat_' . $category->term_id,
            'label' => $category->name,
            'url' => get_category_link($category->term_id),
            'type' => 'category',
            'level' => 1,
            'count' => $category->count,
        );

        $add_link('home', 'cat_' . $category->term_id, 'contains', array('weight' => 0.2, 'provenance' => 'taxonomy'));
    }

    foreach ($tags as $tag) {
        $nodes[] = array(
            'id' => 'tag_' . $tag->term_id,
            'label' => $tag->name,
            'url' => get_tag_link($tag->term_id),
            'type' => 'tag',
            'level' => 1,
            'count' => $tag->count,
        );

        $add_link('home', 'tag_' . $tag->term_id, 'tagged', array('weight' => 0.2, 'provenance' => 'taxonomy'));
    }

    foreach ($posts as $post) {
        $post_id = 'post_' . $post->ID;

        $nodes[] = array(
            'id' => $post_id,
            'label' => $post->post_title,
            'url' => get_permalink($post->ID),
            'type' => 'post',
            'level' => 2,
            'date' => $post->post_date,
            'excerpt' => wp_trim_words(get_the_excerpt($post->ID), 20),
        );

        $post_categories = get_the_category($post->ID);
        foreach ($post_categories as $cat) {
            $add_link('cat_' . $cat->term_id, $post_id, 'has_post', array('weight' => 0.2, 'provenance' => 'taxonomy'));
        }

        $post_tags = get_the_tags($post->ID);
        if ($post_tags) {
            foreach ($post_tags as $tag) {
                $add_link('tag_' . $tag->term_id, $post_id, 'has_tag', array('weight' => 0.2, 'provenance' => 'taxonomy'));
            }
        }

        $content_urls = themisdb_graph_nav_extract_content_urls($post->post_content);
        foreach ($content_urls as $content_url) {
            if (themisdb_graph_nav_is_internal_url($content_url)) {
                $target_wp_id = url_to_postid($content_url);
                if ($target_wp_id > 0 && isset($content_node_by_wp_id[(int) $target_wp_id])) {
                    $target_node_id = $content_node_by_wp_id[(int) $target_wp_id];
                    if ($target_node_id !== $post_id) {
                        $add_link($post_id, $target_node_id, 'internal_link', array('weight' => 1.0, 'provenance' => 'internal_link'));
                    }
                }
                continue;
            }

            $external_node = themisdb_graph_nav_build_external_node($content_url);
            $external_node_id = $external_node['id'];
            if (!isset($external_nodes[$external_node_id])) {
                $external_nodes[$external_node_id] = true;
                $nodes[] = array(
                    'id' => $external_node_id,
                    'label' => $external_node['label'],
                    'url' => $external_node['url'],
                    'type' => 'external_resource',
                    'level' => 3,
                );
            }
            $add_link($post_id, $external_node_id, 'external_link', array('weight' => 0.8, 'provenance' => 'external_link'));
        }
    }

    foreach ($pages as $page) {
        $page_id = 'page_' . $page->ID;

        $nodes[] = array(
            'id' => $page_id,
            'label' => $page->post_title,
            'url' => get_permalink($page->ID),
            'type' => 'page',
            'level' => 1,
            'excerpt' => wp_trim_words(get_the_excerpt($page->ID), 20),
        );

        $add_link('home', $page_id, 'page_of', array('weight' => 0.2, 'provenance' => 'taxonomy'));

        $content_urls = themisdb_graph_nav_extract_content_urls($page->post_content);
        foreach ($content_urls as $content_url) {
            if (themisdb_graph_nav_is_internal_url($content_url)) {
                $target_wp_id = url_to_postid($content_url);
                if ($target_wp_id > 0 && isset($content_node_by_wp_id[(int) $target_wp_id])) {
                    $target_node_id = $content_node_by_wp_id[(int) $target_wp_id];
                    if ($target_node_id !== $page_id) {
                        $add_link($page_id, $target_node_id, 'internal_link', array('weight' => 1.0, 'provenance' => 'internal_link'));
                    }
                }
                continue;
            }

            $external_node = themisdb_graph_nav_build_external_node($content_url);
            $external_node_id = $external_node['id'];
            if (!isset($external_nodes[$external_node_id])) {
                $external_nodes[$external_node_id] = true;
                $nodes[] = array(
                    'id' => $external_node_id,
                    'label' => $external_node['label'],
                    'url' => $external_node['url'],
                    'type' => 'external_resource',
                    'level' => 3,
                );
            }
            $add_link($page_id, $external_node_id, 'external_link', array('weight' => 0.8, 'provenance' => 'external_link'));
        }
    }

    $data = array(
        'nodes' => $nodes,
        'links' => $links,
    );
    if ($cache_seconds > 0) {
        set_transient('themisdb_graph_nav_data', $data, $cache_seconds);
    }
    return apply_filters('themisdb_graph_navigation_data', $data);
}

/**
 * Flush graph-data cache when content or taxonomy changes.
 */
function themisdb_graph_nav_flush_cache() {
    delete_transient('themisdb_graph_nav_data');
}
add_action('save_post',    'themisdb_graph_nav_flush_cache');
add_action('deleted_post', 'themisdb_graph_nav_flush_cache');
add_action('created_term', 'themisdb_graph_nav_flush_cache');
add_action('edited_term',  'themisdb_graph_nav_flush_cache');
add_action('delete_term',  'themisdb_graph_nav_flush_cache');

/**
 * Extract and normalize all HTTP(S) URLs from content.
 *
 * @param string $content
 * @return string[]
 */
function themisdb_graph_nav_extract_content_urls($content) {
    if (!is_string($content) || $content === '') {
        return array();
    }

    $raw_urls = wp_extract_urls($content);
    if (!is_array($raw_urls) || empty($raw_urls)) {
        return array();
    }

    $result = array();
    foreach ($raw_urls as $url) {
        $normalized = themisdb_graph_nav_normalize_url($url);
        if ($normalized === '') {
            continue;
        }
        $result[$normalized] = true;
    }
    return array_keys($result);
}

/**
 * Normalize URL for stable graph identity and matching.
 *
 * @param string $url
 * @return string
 */
function themisdb_graph_nav_normalize_url($url) {
    if (!is_string($url) || $url === '') {
        return '';
    }

    $parsed = wp_parse_url(trim($url));
    if (!is_array($parsed) || empty($parsed['scheme']) || empty($parsed['host'])) {
        return '';
    }

    $scheme = strtolower((string) $parsed['scheme']);
    if ($scheme !== 'http' && $scheme !== 'https') {
        return '';
    }

    $host = strtolower((string) $parsed['host']);
    $path = isset($parsed['path']) ? $parsed['path'] : '/';
    $path = $path === '' ? '/' : $path;

    $normalized = $scheme . '://' . $host . $path;
    if (!empty($parsed['query'])) {
        $normalized .= '?' . $parsed['query'];
    }
    return $normalized;
}

/**
 * Check whether URL points to the current site.
 *
 * @param string $url
 * @return bool
 */
function themisdb_graph_nav_is_internal_url($url) {
    $normalized_target = themisdb_graph_nav_normalize_url($url);
    $normalized_home = themisdb_graph_nav_normalize_url(home_url('/'));
    if ($normalized_target === '' || $normalized_home === '') {
        return false;
    }

    $target_host = wp_parse_url($normalized_target, PHP_URL_HOST);
    $home_host = wp_parse_url($normalized_home, PHP_URL_HOST);

    return is_string($target_host) && is_string($home_host) && strtolower($target_host) === strtolower($home_host);
}

/**
 * Build external node identity for URL.
 *
 * Well-known reference domains (Wikipedia, arXiv, GitHub, etc.) are collapsed
 * into a single shared hub node each, so the graph stays readable instead of
 * sprouting hundreds of individual Wikipedia-article nodes.
 *
 * @param string $url
 * @return array{id: string, label: string, url: string}
 */
function themisdb_graph_nav_build_external_node($url) {
    $normalized = themisdb_graph_nav_normalize_url($url);
    if ($normalized === '') {
        return array(
            'id' => 'ext_unknown',
            'label' => 'External',
            'url' => '',
        );
    }

    $host = strtolower((string) wp_parse_url($normalized, PHP_URL_HOST));

    // Hub domains: every link to these domains collapses to one shared node.
    $hubs = array(
        'wikipedia.org'        => array('id' => 'ext_wikipedia', 'label' => 'Wikipedia',  'url' => 'https://de.wikipedia.org/'),
        'arxiv.org'            => array('id' => 'ext_arxiv',     'label' => 'arXiv',       'url' => 'https://arxiv.org/'),
        'github.com'           => array('id' => 'ext_github',    'label' => 'GitHub',      'url' => 'https://github.com/'),
        'stackoverflow.com'    => array('id' => 'ext_so',        'label' => 'Stack Overflow', 'url' => 'https://stackoverflow.com/'),
        'scholar.google.com'   => array('id' => 'ext_scholar',   'label' => 'Google Scholar', 'url' => 'https://scholar.google.com/'),
        'huggingface.co'       => array('id' => 'ext_hf',        'label' => 'Hugging Face', 'url' => 'https://huggingface.co/'),
    );

    foreach ($hubs as $domain => $node) {
        $suffix = '.' . $domain;
        $is_subdomain = (strlen($host) > strlen($suffix))
            && (substr($host, -strlen($suffix)) === $suffix);
        if ($host === $domain || $is_subdomain) {
            return $node;
        }
    }

    return array(
        'id' => 'ext_' . substr(md5($normalized), 0, 16),
        'label' => $host !== '' ? $host : $normalized,
        'url' => $normalized,
    );
}

/**
 * Backward-compatible function name from theme implementation.
 *
 * @return array{nodes: array, links: array}
 */
if (!function_exists('themisdb_get_graph_data')) {
    function themisdb_get_graph_data()
    {
        return themisdb_graph_navigation_get_graph_data();
    }
}

    /* =========================================================================
       ADMIN – Einstellungsseite
       ========================================================================= */

    /**
     * Graph-Daten-Limits aus den gespeicherten Optionen in die Filter eintragen.
     * Diese Hooks laufen vor themisdb_graph_navigation_get_graph_data().
     */
    add_filter('themisdb_graph_navigation_post_limit', 'themisdb_graph_nav_option_post_limit');
    function themisdb_graph_nav_option_post_limit($default) {
        $v = (int) get_option('themisdb_graph_nav_post_limit', 0);
        return $v > 0 ? $v : $default;
    }

    add_filter('themisdb_graph_navigation_page_limit', 'themisdb_graph_nav_option_page_limit');
    function themisdb_graph_nav_option_page_limit($default) {
        $v = (int) get_option('themisdb_graph_nav_page_limit', 0);
        return $v > 0 ? $v : $default;
    }

    /**
     * Admin-Menü registrieren.
     */
    add_action('admin_menu', 'themisdb_graph_nav_admin_menu');
    function themisdb_graph_nav_admin_menu() {
        add_options_page(
            __('Graph Navigation Einstellungen', 'themisdb-graph-navigation'),
            __('Graph Navigation', 'themisdb-graph-navigation'),
            'manage_options',
            'themisdb-graph-navigation',
            'themisdb_graph_nav_settings_page'
        );
    }

    /**
     * Einstellungen registrieren.
     */
    add_action('admin_init', 'themisdb_graph_nav_register_settings');
    function themisdb_graph_nav_register_settings() {
        register_setting('themisdb_graph_nav_options', 'themisdb_graph_nav_post_limit', array(
            'type' => 'integer', 'default' => 50, 'sanitize_callback' => 'absint',
        ));
        register_setting('themisdb_graph_nav_options', 'themisdb_graph_nav_page_limit', array(
            'type' => 'integer', 'default' => 30, 'sanitize_callback' => 'absint',
        ));
        register_setting('themisdb_graph_nav_options', 'themisdb_graph_nav_cache_seconds', array(
            'type' => 'integer', 'default' => HOUR_IN_SECONDS, 'sanitize_callback' => 'absint',
        ));
        register_setting('themisdb_graph_nav_options', 'themisdb_graph_nav_include_tags', array(
            'type' => 'boolean', 'default' => 1, 'sanitize_callback' => 'absint',
        ));
        register_setting('themisdb_graph_nav_options', 'themisdb_graph_nav_include_pages', array(
            'type' => 'boolean', 'default' => 1, 'sanitize_callback' => 'absint',
        ));
    }

    /**
     * Settings-Page HTML rendern.
     */
    function themisdb_graph_nav_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $tab = isset($_GET['tab']) ? sanitize_key((string) $_GET['tab']) : 'settings';
        if (!in_array($tab, array('settings', 'cache', 'integration'), true)) {
            $tab = 'settings';
        }

        if (isset($_GET['flush_cache']) && check_admin_referer('themisdb_graph_nav_flush')) {
            themisdb_graph_nav_flush_cache();
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Graph-Cache geleert.', 'themisdb-graph-navigation') . '</p></div>';
        }

        $tab_url = function($target_tab) {
            return esc_url(add_query_arg(
                array('page' => 'themisdb-graph-navigation', 'tab' => $target_tab),
                admin_url('options-general.php')
            ));
        };

        $flush_url = wp_nonce_url(
            add_query_arg(array('page' => 'themisdb-graph-navigation', 'flush_cache' => '1'), admin_url('options-general.php')),
            'themisdb_graph_nav_flush'
        );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Graph Navigation Einstellungen', 'themisdb-graph-navigation'); ?></h1>

            <nav class="nav-tab-wrapper wp-clearfix" aria-label="<?php esc_attr_e('Graph Navigation Tabs', 'themisdb-graph-navigation'); ?>">
                <a href="<?php echo $tab_url('settings'); ?>" class="nav-tab <?php echo $tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Einstellungen', 'themisdb-graph-navigation'); ?>
                </a>
                <a href="<?php echo $tab_url('cache'); ?>" class="nav-tab <?php echo $tab === 'cache' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Cache', 'themisdb-graph-navigation'); ?>
                </a>
                <a href="<?php echo $tab_url('integration'); ?>" class="nav-tab <?php echo $tab === 'integration' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Integration', 'themisdb-graph-navigation'); ?>
                </a>
            </nav>

            <div class="themisdb-tab-content">
                <?php if ($tab === 'settings') : ?>
                <div class="themisdb-admin-modules">
                    <div class="card">
                        <h2><?php esc_html_e('Schnellaktionen', 'themisdb-graph-navigation'); ?></h2>
                        <p><?php esc_html_e('Konfiguriere Datenlimits und steuere, ob Tags und Seiten in der Visualisierung auftauchen.', 'themisdb-graph-navigation'); ?></p>
                        <p>
                            <a href="<?php echo $tab_url('cache'); ?>" class="button button-secondary"><?php esc_html_e('Cache verwalten', 'themisdb-graph-navigation'); ?></a>
                            <a href="<?php echo $tab_url('integration'); ?>" class="button button-secondary"><?php esc_html_e('Integrations-Hooks', 'themisdb-graph-navigation'); ?></a>
                        </p>
                    </div>
                    <div class="card">
                        <h2><?php esc_html_e('Aktive Defaults', 'themisdb-graph-navigation'); ?></h2>
                        <table class="widefat striped"><tbody>
                            <tr><th><?php esc_html_e('Beiträge', 'themisdb-graph-navigation'); ?></th><td><?php echo esc_html((string) get_option('themisdb_graph_nav_post_limit', 50)); ?></td></tr>
                            <tr><th><?php esc_html_e('Seiten', 'themisdb-graph-navigation'); ?></th><td><?php echo esc_html((string) get_option('themisdb_graph_nav_page_limit', 30)); ?></td></tr>
                            <tr><th><?php esc_html_e('Cache', 'themisdb-graph-navigation'); ?></th><td><?php echo esc_html((string) get_option('themisdb_graph_nav_cache_seconds', HOUR_IN_SECONDS)); ?> s</td></tr>
                        </tbody></table>
                    </div>
                </div>

                <form method="post" action="options.php">
                    <?php settings_fields('themisdb_graph_nav_options'); ?>

                    <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Max. Beiträge', 'themisdb-graph-navigation'); ?></th>
                        <td>
                            <input type="number" name="themisdb_graph_nav_post_limit" min="1" max="500"
                                   value="<?php echo esc_attr(get_option('themisdb_graph_nav_post_limit', 50)); ?>" class="small-text">
                            <p class="description"><?php esc_html_e('Wie viele Beiträge in den Graphen geladen werden (Standard: 50).', 'themisdb-graph-navigation'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Max. Seiten', 'themisdb-graph-navigation'); ?></th>
                        <td>
                            <input type="number" name="themisdb_graph_nav_page_limit" min="0" max="200"
                                   value="<?php echo esc_attr(get_option('themisdb_graph_nav_page_limit', 30)); ?>" class="small-text">
                            <p class="description"><?php esc_html_e('Wie viele WordPress-Seiten eingebunden werden (0 = deaktiviert, Standard: 30).', 'themisdb-graph-navigation'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Cache-Dauer (Sekunden)', 'themisdb-graph-navigation'); ?></th>
                        <td>
                            <input type="number" name="themisdb_graph_nav_cache_seconds" min="0" max="86400"
                                   value="<?php echo esc_attr(get_option('themisdb_graph_nav_cache_seconds', HOUR_IN_SECONDS)); ?>" class="small-text">
                            <p class="description"><?php esc_html_e('Wie lange die Graph-Daten zwischengespeichert werden (0 = kein Cache, Standard: 3600).', 'themisdb-graph-navigation'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Tags einschließen', 'themisdb-graph-navigation'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="themisdb_graph_nav_include_tags" value="1"
                                       <?php checked(1, get_option('themisdb_graph_nav_include_tags', 1)); ?>>
                                <?php esc_html_e('WordPress-Tags als Graph-Knoten anzeigen', 'themisdb-graph-navigation'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Seiten einschließen', 'themisdb-graph-navigation'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="themisdb_graph_nav_include_pages" value="1"
                                       <?php checked(1, get_option('themisdb_graph_nav_include_pages', 1)); ?>>
                                <?php esc_html_e('WordPress-Seiten als Graph-Knoten anzeigen', 'themisdb-graph-navigation'); ?>
                            </label>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
            <?php elseif ($tab === 'cache') : ?>
            <div class="card" style="max-width:860px;">
                <h2><?php esc_html_e('Cache-Verwaltung', 'themisdb-graph-navigation'); ?></h2>
                <p><?php esc_html_e('Graph-Daten werden bei Änderungen an Beiträgen und Taxonomien automatisch invalidiert.', 'themisdb-graph-navigation'); ?></p>
                <p>
                    <a href="<?php echo esc_url($flush_url); ?>" class="button button-primary">
                        <?php esc_html_e('Graph-Cache jetzt leeren', 'themisdb-graph-navigation'); ?>
                    </a>
                </p>
            </div>
            <?php else : ?>
            <div class="card" style="max-width:860px;">
                <h2><?php esc_html_e('Einbindung & Hooks', 'themisdb-graph-navigation'); ?></h2>
                <p><?php esc_html_e('Die Visualisierung wird über assets/js/graph-navigation.js geladen und mit lokalisierten Daten versorgt.', 'themisdb-graph-navigation'); ?></p>
                <ul>
                    <li><code>themisdb_graph_navigation_enqueue_frontend_script</code> &mdash; <?php esc_html_e('Skript-Laden an/aus.', 'themisdb-graph-navigation'); ?></li>
                    <li><code>themisdb_graph_navigation_data</code> &mdash; <?php esc_html_e('Datenstruktur vor Ausgabe filtern.', 'themisdb-graph-navigation'); ?></li>
                    <li><code>themisdb_graph_navigation_js_payload</code> &mdash; <?php esc_html_e('Kompletten JS-Payload filtern.', 'themisdb-graph-navigation'); ?></li>
                </ul>
            </div>
            <?php endif; ?>
        </div>

        </div>
        <?php
    }
