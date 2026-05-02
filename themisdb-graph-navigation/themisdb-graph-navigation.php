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
 * Version: 1.0.0
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

define('THEMISDB_GRAPH_NAV_VERSION', '1.0.0');
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

        $links[] = array(
            'source' => 'home',
            'target' => 'cat_' . $category->term_id,
            'type' => 'contains',
        );
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

        $links[] = array(
            'source' => 'home',
            'target' => 'tag_' . $tag->term_id,
            'type' => 'tagged',
        );
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
            $links[] = array(
                'source' => 'cat_' . $cat->term_id,
                'target' => $post_id,
                'type' => 'has_post',
            );
        }

        $post_tags = get_the_tags($post->ID);
        if ($post_tags) {
            foreach ($post_tags as $tag) {
                $links[] = array(
                    'source' => 'tag_' . $tag->term_id,
                    'target' => $post_id,
                    'type' => 'has_tag',
                );
            }
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

        $links[] = array(
            'source' => 'home',
            'target' => $page_id,
            'type' => 'page_of',
        );
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
