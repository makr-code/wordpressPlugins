<?php
/**
 * Plugin Name: ThemisDB Front Slider
 * Plugin URI:  https://github.com/makr-code/wordpressPlugins
 * Description: Titelseiten-Slider mit Timer, der die neuesten Artikel auf der Hauptseite darstellt. Shortcode: [themisdb_front_slider]
 * Version:     1.1.0
 * Author:      ThemisDB Team
 * License:     MIT
 * Text Domain: themisdb-front-slider
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'THEMISDB_FS_VERSION',    '1.1.0' );
define( 'THEMISDB_FS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'THEMISDB_FS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'THEMISDB_FS_PLUGIN_FILE', __FILE__ );

add_action( 'init', 'themisdb_fs_register_assets' );
function themisdb_fs_register_assets() {
    wp_register_style(
        'themisdb-front-slider-css',
        THEMISDB_FS_PLUGIN_URL . 'assets/css/front-slider.css',
        array(),
        THEMISDB_FS_VERSION
    );

    wp_register_style(
        'themisdb-front-slider-editor-css',
        THEMISDB_FS_PLUGIN_URL . 'assets/css/block-editor.css',
        array( 'wp-edit-blocks', 'themisdb-front-slider-css' ),
        THEMISDB_FS_VERSION
    );
}

// Load shared updater class (checks plugin directory and parent directory)
$_themisdb_fs_updater_local  = THEMISDB_FS_PLUGIN_DIR . 'includes/class-themisdb-plugin-updater.php';
$_themisdb_fs_updater_shared = dirname( THEMISDB_FS_PLUGIN_DIR ) . '/includes/class-themisdb-plugin-updater.php';

if ( file_exists( $_themisdb_fs_updater_local ) ) {
    require_once $_themisdb_fs_updater_local;
} elseif ( file_exists( $_themisdb_fs_updater_shared ) ) {
    require_once $_themisdb_fs_updater_shared;
}
unset( $_themisdb_fs_updater_local, $_themisdb_fs_updater_shared );

if ( class_exists( 'ThemisDB_Plugin_Updater' ) ) {
    new ThemisDB_Plugin_Updater(
        THEMISDB_FS_PLUGIN_FILE,
        'themisdb-front-slider',
        THEMISDB_FS_VERSION
    );
}

/* --------------------------------------------------------------------------
 * Activation / Deactivation
 * ---------------------------------------------------------------------- */

register_activation_hook( __FILE__, 'themisdb_fs_activate' );
function themisdb_fs_activate() {
    $defaults = array(
        'posts_count'   => 5,
        'interval'      => 5000,
        'category'      => '',
        'show_excerpt'  => true,
        'show_date'     => true,
        'show_category' => true,
        'autoplay'      => true,
    );
    if ( ! get_option( 'themisdb_fs_options' ) ) {
        add_option( 'themisdb_fs_options', $defaults );
    }
}

register_deactivation_hook( __FILE__, 'themisdb_fs_deactivate' );
function themisdb_fs_deactivate() {
    // Nothing to clean up on deactivation.
}

/* --------------------------------------------------------------------------
 * Front-end Assets
 * ---------------------------------------------------------------------- */

add_action( 'wp_enqueue_scripts', 'themisdb_fs_enqueue' );
function themisdb_fs_enqueue() {
    $theme_controls_presentation =
        wp_style_is( 'themisdb-style', 'enqueued' ) ||
        wp_style_is( 'themisdb-style', 'registered' ) ||
        wp_style_is( 'lis-a-style', 'enqueued' ) ||
        wp_style_is( 'lis-a-style', 'registered' );

    $should_enqueue_frontend_style = apply_filters(
        'themisdb_front_slider_enqueue_frontend_style',
        ! $theme_controls_presentation
    );

    if ( $should_enqueue_frontend_style ) {
        wp_enqueue_style( 'themisdb-front-slider-css' );
    }

    // Falls das aktive Theme den Slider-Controller nicht bereitstellt,
    // nutzen wir den Plugin-Controller als Fallback.
    // Fallback: In anderen Themes weiterhin Plugin-eigenes JS laden.
    $theme_controls_slider_js =
        wp_script_is( 'themisdb-hero-slider', 'enqueued' ) ||
        wp_script_is( 'themisdb-hero-slider', 'registered' ) ||
        wp_script_is( 'lis-a-hero-slider', 'enqueued' ) ||
        wp_script_is( 'lis-a-hero-slider', 'registered' );

    if ( ! $theme_controls_slider_js ) {
        wp_enqueue_script(
            'themisdb-front-slider-js',
            THEMISDB_FS_PLUGIN_URL . 'assets/js/front-slider.js',
            array(),
            THEMISDB_FS_VERSION,
            true
        );
    }
}

/* --------------------------------------------------------------------------
 * Shortcode  [themisdb_front_slider]
 *
 * Attributes:
 *   posts     – number of posts to show            (default: 5)
 *   interval  – autoplay interval in milliseconds  (default: 5000)
 *   category  – category slug to filter by         (default: '')
 *   excerpt       – show post excerpt (1/0)         (default: 1)
 *   date          – show post date    (1/0)         (default: 1)
 *   cat_label     – show category label (1/0)       (default: 1)
 *   autoplay      – enable autoplay   (1/0)         (default: 1)
 *   accent_color  – accent hex color                (default: #0284c7)
 *   readmore_text – readmore button text            (default: Weiterlesen →)
 *   img_size      – WP image size                   (default: large)
 * ---------------------------------------------------------------------- */

add_shortcode( 'themisdb_front_slider', 'themisdb_fs_shortcode' );
function themisdb_fs_shortcode( $atts ) {
    $opts = (array) get_option( 'themisdb_fs_options', array() );
    $raw_atts = (array) $atts;

    // Backward-compatible aliases for older shortcode names.
    if ( isset( $raw_atts['count'] ) && ! isset( $raw_atts['posts'] ) ) {
        $raw_atts['posts'] = $raw_atts['count'];
    }
    if ( isset( $raw_atts['show_excerpt'] ) && ! isset( $raw_atts['excerpt'] ) ) {
        $raw_atts['excerpt'] = $raw_atts['show_excerpt'];
    }
    if ( isset( $raw_atts['show_date'] ) && ! isset( $raw_atts['date'] ) ) {
        $raw_atts['date'] = $raw_atts['show_date'];
    }
    if ( isset( $raw_atts['show_category'] ) && ! isset( $raw_atts['cat_label'] ) ) {
        $raw_atts['cat_label'] = $raw_atts['show_category'];
    }

    $atts = shortcode_atts(
        array(
            'posts'         => isset( $opts['posts_count'] )   ? $opts['posts_count']   : 5,
            'interval'      => isset( $opts['interval'] )      ? $opts['interval']      : 5000,
            'category'      => isset( $opts['category'] )      ? $opts['category']      : '',
            'excerpt'       => isset( $opts['show_excerpt'] )  ? $opts['show_excerpt']  : true,
            'date'          => isset( $opts['show_date'] )     ? $opts['show_date']     : true,
            'cat_label'     => isset( $opts['show_category'] ) ? $opts['show_category'] : true,
            'autoplay'      => isset( $opts['autoplay'] )      ? $opts['autoplay']      : true,
            'accent_color'  => '#0284c7',
            'readmore_text' => 'Weiterlesen →',
            'img_size'      => 'large',
        ),
        $raw_atts,
        'themisdb_front_slider'
    );

    $atts = apply_filters( 'themisdb_front_slider_shortcode_atts', $atts, $raw_atts, $opts );

    // Sanitize.
    $posts_count   = max( 1, min( 20, (int) $atts['posts'] ) );
    $interval      = max( 1000, min( 30000, (int) $atts['interval'] ) );
    $category      = sanitize_text_field( $atts['category'] );
    $show_excerpt  = filter_var( $atts['excerpt'],   FILTER_VALIDATE_BOOLEAN );
    $show_date     = filter_var( $atts['date'],      FILTER_VALIDATE_BOOLEAN );
    $show_category = filter_var( $atts['cat_label'], FILTER_VALIDATE_BOOLEAN );
    $autoplay      = filter_var( $atts['autoplay'],  FILTER_VALIDATE_BOOLEAN );
    $raw_accent    = (string) $atts['accent_color'];
    $accent_color  = preg_match( '/^#[0-9a-fA-F]{3,6}$/', $raw_accent ) ? $raw_accent : '#0284c7';
    $readmore_text = sanitize_text_field( (string) $atts['readmore_text'] );
    if ( '' === $readmore_text ) { $readmore_text = 'Weiterlesen →'; }
    $image_size    = sanitize_key( (string) $atts['img_size'] );
    $image_size    = in_array( $image_size, array( 'thumbnail', 'medium', 'medium_large', 'large', 'full' ), true ) ? $image_size : 'large';

    // Query posts.
    $query_args = array(
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'posts_per_page'      => $posts_count,
        'ignore_sticky_posts' => false,
        'orderby'             => 'date',
        'order'               => 'DESC',
    );

    if ( ! empty( $category ) ) {
        $query_args['category_name'] = $category;
    }

    $query_args = apply_filters( 'themisdb_front_slider_shortcode_query_args', $query_args, $atts );

    $query = new WP_Query( $query_args );

    if ( ! $query->have_posts() ) {
        return '<p class="themisdb-fs-no-posts">' . esc_html__( 'Keine Artikel gefunden.', 'themisdb-front-slider' ) . '</p>';
    }

    $slides = array();
    foreach ( $query->posts as $post_obj ) {
        $slides[] = array(
            'id' => (int) $post_obj->ID,
            'title' => (string) get_the_title( $post_obj->ID ),
            'url' => (string) get_permalink( $post_obj->ID ),
            'date' => (string) get_the_date( '', $post_obj->ID ),
            'excerpt' => (string) wp_trim_words( get_the_excerpt( $post_obj->ID ), 24 ),
            'thumbnail' => (string) get_the_post_thumbnail_url( $post_obj->ID, 'full' ),
        );
    }

    $payload = array(
        'posts_count'   => $posts_count,
        'interval'      => $interval,
        'category'      => $category,
        'show_excerpt'  => $show_excerpt,
        'show_date'     => $show_date,
        'show_category' => $show_category,
        'autoplay'      => $autoplay,
        'accent_color'  => $accent_color,
        'readmore_text' => $readmore_text,
        'image_size'    => $image_size,
        'query_args'    => $query_args,
        'slides'        => $slides,
    );
    $payload = apply_filters( 'themisdb_front_slider_shortcode_payload', $payload, $atts );

    $custom_html = apply_filters( 'themisdb_front_slider_shortcode_html', null, $payload, $atts );
    if ( null !== $custom_html ) {
        wp_reset_postdata();
        return (string) $custom_html;
    }

    ob_start();
    include THEMISDB_FS_PLUGIN_DIR . 'templates/slider.php';
    $html = ob_get_clean();
    wp_reset_postdata();

    return apply_filters( 'themisdb_front_slider_shortcode_html_output', $html, $payload, $atts );
}

/* --------------------------------------------------------------------------
 * Gutenberg Block  themisdb/front-slider
 * Dynamischer Block mit Inspector-Controls und serverseitigem Rendering.
 * ---------------------------------------------------------------------- */

add_action( 'init', 'themisdb_fs_register_block' );
function themisdb_fs_register_block() {
    $block_json_path = THEMISDB_FS_PLUGIN_DIR . 'block.json';

    wp_register_script(
        'themisdb-front-slider-block-editor',
        THEMISDB_FS_PLUGIN_URL . 'assets/js/block.js',
        array( 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-components', 'wp-block-editor', 'wp-server-side-render' ),
        THEMISDB_FS_VERSION,
        true
    );

    if ( function_exists( 'register_block_type_from_metadata' ) && file_exists( $block_json_path ) ) {
        register_block_type_from_metadata(
            THEMISDB_FS_PLUGIN_DIR,
            array(
                'style'           => 'themisdb-front-slider-css',
                'editor_style'    => 'themisdb-front-slider-editor-css',
                'render_callback' => 'themisdb_fs_render_block',
            )
        );
        return;
    }

    register_block_type(
        'themisdb/front-slider',
        array(
            'editor_script'   => 'themisdb-front-slider-block-editor',
            'style'           => 'themisdb-front-slider-css',
            'editor_style'    => 'themisdb-front-slider-editor-css',
            'render_callback' => 'themisdb_fs_render_block',
            'attributes'      => array(
                'posts' => array(
                    'type'    => 'number',
                    'default' => 5,
                ),
                'interval' => array(
                    'type'    => 'number',
                    'default' => 5000,
                ),
                'category' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
                'excerpt' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
                'date' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
                'cat_label' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
                'autoplay' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
                'accent_color' => array(
                    'type'    => 'string',
                    'default' => '#0284c7',
                ),
                'readmore_text' => array(
                    'type'    => 'string',
                    'default' => 'Weiterlesen →',
                ),
                'img_size' => array(
                    'type'    => 'string',
                    'default' => 'large',
                ),
            ),
        )
    );
}

function themisdb_fs_render_block( $attributes ) {
    if ( ! is_array( $attributes ) ) {
        $attributes = array();
    }

    $atts = array(
        'posts'         => isset( $attributes['posts'] )        ? $attributes['posts']        : null,
        'interval'      => isset( $attributes['interval'] )     ? $attributes['interval']     : null,
        'category'      => isset( $attributes['category'] )     ? $attributes['category']     : null,
        'excerpt'       => isset( $attributes['excerpt'] )      ? $attributes['excerpt']      : null,
        'date'          => isset( $attributes['date'] )         ? $attributes['date']         : null,
        'cat_label'     => isset( $attributes['cat_label'] )    ? $attributes['cat_label']    : null,
        'autoplay'      => isset( $attributes['autoplay'] )     ? $attributes['autoplay']     : null,
        'accent_color'  => isset( $attributes['accent_color'] ) ? $attributes['accent_color'] : null,
        'readmore_text' => isset( $attributes['readmore_text'] )? $attributes['readmore_text']: null,
        'img_size'      => isset( $attributes['img_size'] )     ? $attributes['img_size']     : null,
    );

    $atts = array_filter(
        $atts,
        static function( $value ) {
            return null !== $value;
        }
    );

    return themisdb_fs_shortcode( $atts );
}

/* --------------------------------------------------------------------------
 * Admin Settings Page
 * ---------------------------------------------------------------------- */

add_action( 'admin_menu', 'themisdb_fs_admin_menu' );
function themisdb_fs_admin_menu() {
    add_options_page(
        __( 'Front Slider Einstellungen', 'themisdb-front-slider' ),
        __( 'Front Slider', 'themisdb-front-slider' ),
        'manage_options',
        'themisdb-front-slider',
        'themisdb_fs_settings_page'
    );
}

add_action( 'admin_init', 'themisdb_fs_register_settings' );
function themisdb_fs_register_settings() {
    register_setting(
        'themisdb_fs_options_group',
        'themisdb_fs_options',
        'themisdb_fs_sanitize_options'
    );
}

function themisdb_fs_sanitize_options( $input ) {
    $clean = array();
    $clean['posts_count']   = max( 1, min( 20,    (int)  $input['posts_count'] ) );
    $clean['interval']      = max( 1000, min( 30000, (int) $input['interval'] ) );
    $clean['category']      = sanitize_text_field( $input['category'] );
    $clean['show_excerpt']  = ! empty( $input['show_excerpt'] );
    $clean['show_date']     = ! empty( $input['show_date'] );
    $clean['show_category'] = ! empty( $input['show_category'] );
    $clean['autoplay']      = ! empty( $input['autoplay'] );
    return $clean;
}

function themisdb_fs_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $opts          = (array) get_option( 'themisdb_fs_options', array() );
    $posts_count   = isset( $opts['posts_count'] )   ? (int)  $opts['posts_count']   : 5;
    $interval      = isset( $opts['interval'] )      ? (int)  $opts['interval']      : 5000;
    $category      = isset( $opts['category'] )      ?        $opts['category']      : '';
    $show_excerpt  = isset( $opts['show_excerpt'] )  ? (bool) $opts['show_excerpt']  : true;
    $show_date     = isset( $opts['show_date'] )     ? (bool) $opts['show_date']     : true;
    $show_category = isset( $opts['show_category'] ) ? (bool) $opts['show_category'] : true;
    $autoplay      = isset( $opts['autoplay'] )      ? (bool) $opts['autoplay']      : true;

    $_tfs_page = 'themisdb-front-slider';
    $_tfs_tab  = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'settings';
    if ( ! in_array( $_tfs_tab, array( 'settings', 'shortcode' ), true ) ) {
        $_tfs_tab = 'settings';
    }
    $_tfs_url = function( $tab ) use ( $_tfs_page ) {
        return esc_url( admin_url( 'options-general.php?page=' . $_tfs_page . '&tab=' . $tab ) );
    };
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">
            <?php echo esc_html( get_admin_page_title() ); ?>
            <a href="<?php echo $_tfs_url( 'shortcode' ); ?>" class="page-title-action"><?php esc_html_e( 'Shortcode-Info', 'themisdb-front-slider' ); ?></a>
        </h1>
        <hr class="wp-header-end">

        <?php settings_errors( 'themisdb_fs_options' ); ?>

        <nav class="nav-tab-wrapper wp-clearfix">
            <a href="<?php echo $_tfs_url( 'settings' ); ?>"
               class="nav-tab <?php echo $_tfs_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e( 'Einstellungen', 'themisdb-front-slider' ); ?>
            </a>
            <a href="<?php echo $_tfs_url( 'shortcode' ); ?>"
               class="nav-tab <?php echo $_tfs_tab === 'shortcode' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e( 'Shortcode-Info', 'themisdb-front-slider' ); ?>
            </a>
        </nav>

        <div class="themisdb-tab-content">

            <?php if ( $_tfs_tab === 'settings' ): ?>
            <div class="themisdb-admin-modules">
                <div class="card">
                    <h2><?php esc_html_e( 'Schnellaktionen', 'themisdb-front-slider' ); ?></h2>
                    <p><?php esc_html_e( 'Öffnen Sie direkt die Shortcode-Referenz für die Einbettung des Sliders.', 'themisdb-front-slider' ); ?></p>
                    <p>
                        <a href="<?php echo $_tfs_url( 'shortcode' ); ?>" class="button button-secondary"><?php esc_html_e( 'Shortcode-Info', 'themisdb-front-slider' ); ?></a>
                    </p>
                </div>
                <div class="card">
                    <h2><?php esc_html_e( 'Aktive Slider-Defaults', 'themisdb-front-slider' ); ?></h2>
                    <table class="widefat striped">
                        <tbody>
                            <tr><th><?php esc_html_e( 'Beiträge', 'themisdb-front-slider' ); ?></th><td><?php echo esc_html( $posts_count ); ?></td></tr>
                            <tr><th><?php esc_html_e( 'Intervall', 'themisdb-front-slider' ); ?></th><td><?php echo esc_html( $interval ); ?> ms</td></tr>
                            <tr><th><?php esc_html_e( 'Autoplay', 'themisdb-front-slider' ); ?></th><td><?php echo $autoplay ? esc_html__( 'Aktiv', 'themisdb-front-slider' ) : esc_html__( 'Deaktiviert', 'themisdb-front-slider' ); ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <form method="post" action="options.php">
                <?php settings_fields( 'themisdb_fs_options_group' ); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="posts_count"><?php esc_html_e( 'Anzahl Artikel', 'themisdb-front-slider' ); ?></label>
                        </th>
                        <td>
                            <input type="number" id="posts_count" name="themisdb_fs_options[posts_count]"
                                   value="<?php echo esc_attr( $posts_count ); ?>" min="1" max="20" class="small-text">
                            <p class="description"><?php esc_html_e( 'Anzahl der im Slider angezeigten Beiträge (1–20)', 'themisdb-front-slider' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="interval"><?php esc_html_e( 'Timer-Intervall (ms)', 'themisdb-front-slider' ); ?></label>
                        </th>
                        <td>
                            <input type="number" id="interval" name="themisdb_fs_options[interval]"
                                   value="<?php echo esc_attr( $interval ); ?>" min="1000" max="30000" step="500" class="small-text">
                            <p class="description"><?php esc_html_e( 'Anzeigedauer je Slide in Millisekunden (Standard: 5000 = 5 Sekunden)', 'themisdb-front-slider' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="category"><?php esc_html_e( 'Kategorie-Slug (optional)', 'themisdb-front-slider' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="category" name="themisdb_fs_options[category]"
                                   value="<?php echo esc_attr( $category ); ?>" class="regular-text">
                            <p class="description"><?php esc_html_e( 'Nur Beiträge aus dieser Kategorie anzeigen (leer = alle)', 'themisdb-front-slider' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Sichtbare Elemente', 'themisdb-front-slider' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="themisdb_fs_options[show_excerpt]" value="1" <?php checked( $show_excerpt ); ?>>
                                <?php esc_html_e( 'Auszug anzeigen', 'themisdb-front-slider' ); ?>
                            </label><br>
                            <label>
                                <input type="checkbox" name="themisdb_fs_options[show_date]" value="1" <?php checked( $show_date ); ?>>
                                <?php esc_html_e( 'Datum anzeigen', 'themisdb-front-slider' ); ?>
                            </label><br>
                            <label>
                                <input type="checkbox" name="themisdb_fs_options[show_category]" value="1" <?php checked( $show_category ); ?>>
                                <?php esc_html_e( 'Kategorie anzeigen', 'themisdb-front-slider' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>

                        <th scope="row"><?php esc_html_e( 'Autoplay', 'themisdb-front-slider' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="themisdb_fs_options[autoplay]" value="1" <?php checked( $autoplay ); ?>>
                                <?php esc_html_e( 'Slider automatisch weiterschalten', 'themisdb-front-slider' ); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                <?php submit_button( esc_attr__( 'Einstellungen speichern', 'themisdb-front-slider' ) ); ?>
            </form>

            <?php elseif ( $_tfs_tab === 'shortcode' ): ?>
            <h2><?php esc_html_e( 'Shortcode-Verwendung', 'themisdb-front-slider' ); ?></h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Shortcode', 'themisdb-front-slider' ); ?></th>
                        <th><?php esc_html_e( 'Beschreibung', 'themisdb-front-slider' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>[themisdb_front_slider]</code></td>
                        <td><?php esc_html_e( 'Slider mit den globalen Einstellungen anzeigen.', 'themisdb-front-slider' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>[themisdb_front_slider posts="3"]</code></td>
                        <td><?php esc_html_e( 'Slider mit 3 Beiträgen anzeigen.', 'themisdb-front-slider' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>[themisdb_front_slider category="news" posts="5"]</code></td>
                        <td><?php esc_html_e( 'Slider mit 5 Beiträgen aus der Kategorie „news".', 'themisdb-front-slider' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>[themisdb_front_slider interval="3000" autoplay="yes"]</code></td>
                        <td><?php esc_html_e( 'Autoplay mit 3-Sekunden-Intervall.', 'themisdb-front-slider' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>[themisdb_front_slider excerpt="no" date="no" readmore_text="Zum Beitrag"]</code></td>
                        <td><?php esc_html_e( 'Slider ohne Auszug und Datum.', 'themisdb-front-slider' ); ?></td>
                    </tr>
                </tbody>
            </table>

            <h3 style="margin-top:24px;"><?php esc_html_e( 'Verfügbare Parameter', 'themisdb-front-slider' ); ?></h3>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Parameter', 'themisdb-front-slider' ); ?></th>
                        <th><?php esc_html_e( 'Beschreibung', 'themisdb-front-slider' ); ?></th>
                        <th><?php esc_html_e( 'Standard', 'themisdb-front-slider' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>posts</code></td>
                        <td><?php esc_html_e( 'Anzahl der angezeigten Beiträge', 'themisdb-front-slider' ); ?></td>
                        <td><?php echo esc_html( $posts_count ); ?></td>
                    </tr>
                    <tr>
                        <td><code>interval</code></td>
                        <td><?php esc_html_e( 'Anzeigedauer je Slide (Millisekunden)', 'themisdb-front-slider' ); ?></td>
                        <td><?php echo esc_html( $interval ); ?></td>
                    </tr>
                    <tr>
                        <td><code>category</code></td>
                        <td><?php esc_html_e( 'Kategorie-Slug (leer = alle)', 'themisdb-front-slider' ); ?></td>
                        <td><?php echo esc_html( $category ?: '—' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>excerpt</code></td>
                        <td><?php esc_html_e( 'Auszug anzeigen (yes/no)', 'themisdb-front-slider' ); ?></td>
                        <td><?php echo $show_excerpt ? 'yes' : 'no'; ?></td>
                    </tr>
                    <tr>
                        <td><code>date</code></td>
                        <td><?php esc_html_e( 'Datum anzeigen (yes/no)', 'themisdb-front-slider' ); ?></td>
                        <td><?php echo $show_date ? 'yes' : 'no'; ?></td>
                    </tr>
                    <tr>
                        <td><code>cat_label</code></td>
                        <td><?php esc_html_e( 'Kategorie anzeigen (yes/no)', 'themisdb-front-slider' ); ?></td>
                        <td><?php echo $show_category ? 'yes' : 'no'; ?></td>
                    </tr>
                    <tr>
                        <td><code>accent_color</code></td>
                        <td><?php esc_html_e( 'Akzentfarbe als HEX (z. B. #0284c7)', 'themisdb-front-slider' ); ?></td>
                        <td>#0284c7</td>
                    </tr>
                    <tr>
                        <td><code>readmore_text</code></td>
                        <td><?php esc_html_e( 'Beschriftung des Weiterlesen-Buttons', 'themisdb-front-slider' ); ?></td>
                        <td>Weiterlesen →</td>
                    </tr>
                    <tr>
                        <td><code>img_size</code></td>
                        <td><?php esc_html_e( 'WordPress-Bildgröße (thumbnail|medium|large|full)', 'themisdb-front-slider' ); ?></td>
                        <td>large</td>
                    </tr>
                    <tr>
                        <td><code>autoplay</code></td>
                        <td><?php esc_html_e( 'Autoplay aktivieren (yes/no)', 'themisdb-front-slider' ); ?></td>
                        <td><?php echo $autoplay ? 'yes' : 'no'; ?></td>
                    </tr>
                </tbody>
            </table>
            <?php endif; ?>

        </div><!-- .themisdb-tab-content -->
    </div><!-- .wrap -->

    <style>
    .themisdb-admin-modules { display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:16px; margin:0 0 20px; }
    .themisdb-admin-modules .card { margin:0; max-width:none; }
    .themisdb-tab-content { background:#fff; border:1px solid #c3c4c7; border-top:none; padding:20px 24px; }
    .themisdb-tab-content > h2:first-child,
    .themisdb-tab-content > h3:first-child,
    .themisdb-tab-content > p:first-child { margin-top:0; }
    .themisdb-tab-content .widefat th { width:auto; }
    .themisdb-tab-content table.widefat code { background:#f6f7f7; padding:2px 6px; border-radius:3px; font-size:12px; }
    </style>
    <?php
}
