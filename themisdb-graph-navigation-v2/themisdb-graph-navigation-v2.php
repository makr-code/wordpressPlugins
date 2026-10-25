<?php
/**
 * Plugin Name: ThemisDB Graph Navigation V2
 * Plugin URI: https://github.com/makr-code/wordpressPlugins
 * Description: Semantic graph navigation for heterogeneous WordPress content with a compact overlay and optional WebGPU acceleration.
 * Version: 2.0.0
 * Author: makr-code
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: themisdb-graph-navigation-v2
 */

if (!defined('ABSPATH')) {
    exit;
}

define('THEMISDB_GRAPH_NAV_V2_VERSION', '2.0.15');
define('THEMISDB_GRAPH_NAV_V2_DIR', plugin_dir_path(__FILE__));
define('THEMISDB_GRAPH_NAV_V2_URL', plugin_dir_url(__FILE__));

require_once THEMISDB_GRAPH_NAV_V2_DIR . 'includes/class-themisdb-graph-cache.php';
require_once THEMISDB_GRAPH_NAV_V2_DIR . 'includes/class-themisdb-graph-normalizer.php';
require_once THEMISDB_GRAPH_NAV_V2_DIR . 'includes/class-themisdb-graph-data.php';
require_once THEMISDB_GRAPH_NAV_V2_DIR . 'includes/class-themisdb-graph-settings.php';

final class ThemisDB_Graph_Runtime_Config {
    public static function defaults() {
        return array(
            'enable_gpu' => false,
            'max_visible_nodes' => 120,
            'include_external' => true,
        );
    }

    public static function get($key, $default = null) {
        $defaults = self::defaults();
        $resolved_default = array_key_exists($key, $defaults) ? $defaults[$key] : $default;
        return ThemisDB_Graph_Settings::get($key, $resolved_default);
    }

    public static function build() {
        $graph = new ThemisDB_Graph_Data();
        $config = array(
            'graph' => $graph->get_graph_data(),
            'assetUrl' => THEMISDB_GRAPH_NAV_V2_URL,
            'renderer' => array(
                'preferWebGPU' => false,
                'allowExperimentalWebGPU' => false,
                'fallbackToCanvas' => true,
                'fallbackToSVG' => false,
                'mode' => 'semantic-2d',
            ),
            'labels' => array(
                'title' => __('Graph Navigation', 'themisdb-graph-navigation-v2'),
            ),
            'settings' => array(
                'maxVisibleNodes' => (int) self::get('max_visible_nodes', 120),
                'showExternal' => (bool) self::get('include_external', true),
            ),
        );

        return apply_filters('themisdb_graph_nav_v2_runtime_config', $config);
    }
}

ThemisDB_Graph_Settings::boot();

function themisdb_graph_nav_v2_get_runtime_config() {
    return ThemisDB_Graph_Runtime_Config::build();
}

function themisdb_graph_nav_v2_enqueue_assets() {
    if (is_admin()) {
        return;
    }

    $should_enqueue = apply_filters('themisdb_graph_nav_v2_enqueue_frontend', true);
    if (!$should_enqueue) {
        return;
    }

    wp_enqueue_style(
        'themisdb-graph-nav-v2-style',
        THEMISDB_GRAPH_NAV_V2_URL . 'assets/css/graph.css',
        array(),
        THEMISDB_GRAPH_NAV_V2_VERSION
    );

    wp_enqueue_script(
        'themisdb-graph-nav-v2-state',
        THEMISDB_GRAPH_NAV_V2_URL . 'assets/js/state.js',
        array(),
        THEMISDB_GRAPH_NAV_V2_VERSION,
        true
    );

    wp_enqueue_script(
        'themisdb-graph-nav-v2-store',
        THEMISDB_GRAPH_NAV_V2_URL . 'assets/js/graph-store.js',
        array('themisdb-graph-nav-v2-state'),
        THEMISDB_GRAPH_NAV_V2_VERSION,
        true
    );

    wp_enqueue_script(
        'themisdb-graph-nav-v2-canvas-renderer',
        THEMISDB_GRAPH_NAV_V2_URL . 'assets/js/render/canvas-renderer.js',
        array('themisdb-graph-nav-v2-state', 'themisdb-graph-nav-v2-store'),
        THEMISDB_GRAPH_NAV_V2_VERSION,
        true
    );

    wp_enqueue_script(
        'themisdb-graph-nav-v2-graph-view',
        THEMISDB_GRAPH_NAV_V2_URL . 'assets/js/render/graph-view.js',
        array('themisdb-graph-nav-v2-state', 'themisdb-graph-nav-v2-store', 'themisdb-graph-nav-v2-canvas-renderer'),
        THEMISDB_GRAPH_NAV_V2_VERSION,
        true
    );

    wp_enqueue_script(
        'themisdb-graph-nav-v2-webgpu',
        THEMISDB_GRAPH_NAV_V2_URL . 'assets/js/render/webgpu-renderer.js',
        array('themisdb-graph-nav-v2-state', 'themisdb-graph-nav-v2-store', 'themisdb-graph-nav-v2-canvas-renderer', 'themisdb-graph-nav-v2-graph-view'),
        THEMISDB_GRAPH_NAV_V2_VERSION,
        true
    );

    wp_enqueue_script(
        'themisdb-graph-nav-v2-script',
        THEMISDB_GRAPH_NAV_V2_URL . 'assets/js/themisdb-graph-v2.js',
        array('themisdb-graph-nav-v2-state', 'themisdb-graph-nav-v2-store', 'themisdb-graph-nav-v2-canvas-renderer', 'themisdb-graph-nav-v2-graph-view', 'themisdb-graph-nav-v2-webgpu'),
        THEMISDB_GRAPH_NAV_V2_VERSION,
        true
    );

    $runtime_config = themisdb_graph_nav_v2_get_runtime_config();
    wp_localize_script('themisdb-graph-nav-v2-script', 'themisdbGraphV2', $runtime_config);
}
add_action('wp_enqueue_scripts', 'themisdb_graph_nav_v2_enqueue_assets', 20);

function themisdb_graph_nav_v2_direct_runtime_assets() {
    if (is_admin()) {
        return;
    }

    $should_render = apply_filters('themisdb_graph_nav_v2_enqueue_frontend', true);
    if (!$should_render) {
        return;
    }

    $runtime_config = themisdb_graph_nav_v2_get_runtime_config();
    $runtime_json = wp_json_encode($runtime_config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    if (false === $runtime_json) {
        $runtime_json = '{}';
    }

    echo '<script>window.themisdbGraphV2 = window.themisdbGraphV2 || ' . $runtime_json . ';</script>';
    echo '<link rel="stylesheet" href="' . esc_url(THEMISDB_GRAPH_NAV_V2_URL . 'assets/css/graph.css') . '?ver=' . esc_attr(THEMISDB_GRAPH_NAV_V2_VERSION) . '" type="text/css" media="all" />';
    $scripts = array(
        'state.js',
        'graph-store.js',
        'render/canvas-renderer.js',
        'render/graph-view.js',
        'render/webgpu-renderer.js',
        'themisdb-graph-v2.js',
    );

    foreach ($scripts as $script) {
        echo '<script src="' . esc_url(THEMISDB_GRAPH_NAV_V2_URL . 'assets/js/' . ltrim($script, '/')) . '?ver=' . esc_attr(THEMISDB_GRAPH_NAV_V2_VERSION) . '" defer></script>';
    }
}

function themisdb_graph_nav_v2_shortcode($atts = array()) {
    $atts = shortcode_atts(array(
        'title' => __('Graph Navigation', 'themisdb-graph-navigation-v2'),
        'button_text' => __('Graph anzeigen', 'themisdb-graph-navigation-v2'),
        'expanded' => '0',
        'compact' => '0',
    ), $atts);

    $expanded = filter_var($atts['expanded'], FILTER_VALIDATE_BOOLEAN);
    $compact = filter_var($atts['compact'], FILTER_VALIDATE_BOOLEAN);
    $panel_id = wp_unique_id('themisdb-graph-v2-panel-');

    ob_start();
    ?>
    <div class="themisdb-graph-v2-overlay" data-role="graph-overlay" data-graph-v2-overlay="1">
        <button
            type="button"
            class="themisdb-graph-v2-toggle<?php echo $compact ? ' themisdb-graph-v2-toggle--compact' : ''; ?>"
            data-graph-v2-toggle="1"
            data-graph-v2-target="<?php echo esc_attr($panel_id); ?>"
            aria-expanded="<?php echo esc_attr($expanded ? 'true' : 'false'); ?>"
            aria-controls="<?php echo esc_attr($panel_id); ?>"
            aria-label="<?php echo esc_attr__('Graph anzeigen', 'themisdb-graph-navigation-v2'); ?>"
        >
            <?php if ($compact) : ?>
                <span class="themisdb-graph-v2-toggle-mark" aria-hidden="true">◉</span>
                <span class="themisdb-graph-v2-toggle-label"><?php echo esc_html($atts['button_text']); ?></span>
            <?php else : ?>
                <?php echo esc_html($atts['button_text']); ?>
            <?php endif; ?>
        </button>
        <div id="<?php echo esc_attr($panel_id); ?>" class="themisdb-graph-v2-shell<?php echo $expanded ? ' is-open' : ''; ?>" data-role="graph-container" data-graph-v2-shell="1" data-graph-v2-expanded="<?php echo esc_attr($expanded ? '1' : '0'); ?>">
            <div class="themisdb-graph-v2-header">
                <strong><?php echo esc_html($atts['title']); ?></strong>
                <button type="button" class="themisdb-graph-v2-close" data-graph-v2-close="1" aria-label="<?php esc_attr_e('Graph schließen', 'themisdb-graph-navigation-v2'); ?>">×</button>
            </div>
            <div class="themisdb-graph-v2-canvas" data-graph-v2-canvas="1"<?php echo $expanded ? '' : ' hidden'; ?>></div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('themisdb_graph_navigation_v2', 'themisdb_graph_nav_v2_shortcode');

function themisdb_graph_nav_v2_append_frontpage_content($content) {
    return $content;
}

function themisdb_graph_nav_v2_render_footer_overlay() {
    return;
}

class ThemisDB_Graph_Navigation_V2_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'themisdb_graph_navigation_v2_widget',
            __('ThemisDB Graph Navigation', 'themisdb-graph-navigation-v2'),
            array('description' => __('Displays the WordPress graph navigation as an independent widget.', 'themisdb-graph-navigation-v2'))
        );
    }

    public function widget($args, $instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Graph Navigation', 'themisdb-graph-navigation-v2');
        $button_text = !empty($instance['button_text']) ? $instance['button_text'] : __('Graph anzeigen', 'themisdb-graph-navigation-v2');

        echo $args['before_widget'];
        echo $args['before_title'] . esc_html($title) . $args['after_title'];
        echo do_shortcode('[themisdb_graph_navigation_v2 title="' . esc_attr($title) . '" button_text="' . esc_attr($button_text) . '"]');
        echo $args['after_widget'];
    }

    public function form($instance) {
        $title = isset($instance['title']) ? $instance['title'] : __('Graph Navigation', 'themisdb-graph-navigation-v2');
        $button_text = isset($instance['button_text']) ? $instance['button_text'] : __('Graph anzeigen', 'themisdb-graph-navigation-v2');
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_html_e('Title', 'themisdb-graph-navigation-v2'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('button_text')); ?>"><?php esc_html_e('Button text', 'themisdb-graph-navigation-v2'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('button_text')); ?>" name="<?php echo esc_attr($this->get_field_name('button_text')); ?>" type="text" value="<?php echo esc_attr($button_text); ?>" />
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = sanitize_text_field($new_instance['title']);
        $instance['button_text'] = sanitize_text_field($new_instance['button_text']);
        return $instance;
    }
}

function themisdb_graph_nav_v2_register_widget_area() {
    register_sidebar(array(
        'name' => __('ThemisDB Graph Homepage', 'themisdb-graph-navigation-v2'),
        'id' => 'themisdb-graph-homepage',
        'before_widget' => '<section id="%1$s" class="widget %2$s themisdb-graph-v2-widget">',
        'after_widget' => '</section>',
        'before_title' => '<h2 class="widget-title">',
        'after_title' => '</h2>',
    ));

    register_widget('ThemisDB_Graph_Navigation_V2_Widget');
}
add_action('widgets_init', 'themisdb_graph_nav_v2_register_widget_area');

function themisdb_graph_nav_v2_flush_cache() {
    ThemisDB_Graph_Cache::clear('themisdb_graph_v2_payload');
}

function themisdb_graph_nav_v2_activate() {
    foreach (ThemisDB_Graph_Settings::default_values() as $key => $value) {
        add_option(ThemisDB_Graph_Settings::option_name($key), $value);
    }
    themisdb_graph_nav_v2_flush_cache();
}
register_activation_hook(__FILE__, 'themisdb_graph_nav_v2_activate');

function themisdb_graph_nav_v2_deactivate() {
    themisdb_graph_nav_v2_flush_cache();
}
register_deactivation_hook(__FILE__, 'themisdb_graph_nav_v2_deactivate');

add_action('save_post', 'themisdb_graph_nav_v2_flush_cache');
add_action('deleted_post', 'themisdb_graph_nav_v2_flush_cache');
add_action('created_term', 'themisdb_graph_nav_v2_flush_cache');
add_action('edited_term', 'themisdb_graph_nav_v2_flush_cache');
add_action('delete_term', 'themisdb_graph_nav_v2_flush_cache');
