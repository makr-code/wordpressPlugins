<?php
/**
 * Plugin Name: ThemisDB Front Slider
 * Plugin URI:  https://github.com/makr-code/wordpressPlugins
 * Description: Titelseiten-Slider mit Timer, der die neuesten Artikel auf der Hauptseite darstellt. Shortcode: [themisdb_front_slider]
 * Version:     1.0.0
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

define( 'THEMISDB_FS_VERSION',    '1.0.0' );
define( 'THEMISDB_FS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'THEMISDB_FS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'THEMISDB_FS_PLUGIN_FILE', __FILE__ );

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
        'image_height'  => 420,
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
    wp_enqueue_style(
        'themisdb-front-slider-css',
        THEMISDB_FS_PLUGIN_URL . 'assets/css/front-slider.css',
        array(),
        THEMISDB_FS_VERSION
    );

    wp_enqueue_script(
        'themisdb-front-slider-js',
        THEMISDB_FS_PLUGIN_URL . 'assets/js/front-slider.js',
        array(),
        THEMISDB_FS_VERSION,
        true
    );
}

/* --------------------------------------------------------------------------
 * Shortcode  [themisdb_front_slider]
 *
 * Attributes:
 *   posts     – number of posts to show            (default: 5)
 *   interval  – autoplay interval in milliseconds  (default: 5000)
 *   category  – category slug to filter by         (default: '')
 *   excerpt   – show post excerpt (1/0)             (default: 1)
 *   date      – show post date    (1/0)             (default: 1)
 *   cat_label – show category label (1/0)           (default: 1)
 *   height    – slide image height in px            (default: 420)
 *   autoplay  – enable autoplay   (1/0)             (default: 1)
 * ---------------------------------------------------------------------- */

add_shortcode( 'themisdb_front_slider', 'themisdb_fs_shortcode' );
function themisdb_fs_shortcode( $atts ) {
    $opts = (array) get_option( 'themisdb_fs_options', array() );

    $atts = shortcode_atts(
        array(
            'posts'     => isset( $opts['posts_count'] )   ? $opts['posts_count']   : 5,
            'interval'  => isset( $opts['interval'] )      ? $opts['interval']      : 5000,
            'category'  => isset( $opts['category'] )      ? $opts['category']      : '',
            'excerpt'   => isset( $opts['show_excerpt'] )  ? $opts['show_excerpt']  : true,
            'date'      => isset( $opts['show_date'] )     ? $opts['show_date']     : true,
            'cat_label' => isset( $opts['show_category'] ) ? $opts['show_category'] : true,
            'height'    => isset( $opts['image_height'] )  ? $opts['image_height']  : 420,
            'autoplay'  => isset( $opts['autoplay'] )      ? $opts['autoplay']      : true,
        ),
        $atts,
        'themisdb_front_slider'
    );

    // Sanitize.
    $posts_count   = max( 1, min( 20, (int) $atts['posts'] ) );
    $interval      = max( 1000, min( 30000, (int) $atts['interval'] ) );
    $category      = sanitize_text_field( $atts['category'] );
    $show_excerpt  = filter_var( $atts['excerpt'],   FILTER_VALIDATE_BOOLEAN );
    $show_date     = filter_var( $atts['date'],      FILTER_VALIDATE_BOOLEAN );
    $show_category = filter_var( $atts['cat_label'], FILTER_VALIDATE_BOOLEAN );
    $image_height  = max( 200, min( 900, (int) $atts['height'] ) );
    $autoplay      = filter_var( $atts['autoplay'],  FILTER_VALIDATE_BOOLEAN );

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

    $query = new WP_Query( $query_args );

    if ( ! $query->have_posts() ) {
        return '<p class="themisdb-fs-no-posts">' . esc_html__( 'Keine Artikel gefunden.', 'themisdb-front-slider' ) . '</p>';
    }

    ob_start();
    include THEMISDB_FS_PLUGIN_DIR . 'templates/slider.php';
    wp_reset_postdata();

    return ob_get_clean();
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
    $clean['image_height']  = max( 200, min( 900, (int) $input['image_height'] ) );
    $clean['autoplay']      = ! empty( $input['autoplay'] );
    return $clean;
}

function themisdb_fs_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $opts = (array) get_option( 'themisdb_fs_options', array() );
    $posts_count   = isset( $opts['posts_count'] )   ? (int)  $opts['posts_count']   : 5;
    $interval      = isset( $opts['interval'] )      ? (int)  $opts['interval']      : 5000;
    $category      = isset( $opts['category'] )      ?        $opts['category']      : '';
    $show_excerpt  = isset( $opts['show_excerpt'] )  ? (bool) $opts['show_excerpt']  : true;
    $show_date     = isset( $opts['show_date'] )     ? (bool) $opts['show_date']     : true;
    $show_category = isset( $opts['show_category'] ) ? (bool) $opts['show_category'] : true;
    $image_height  = isset( $opts['image_height'] )  ? (int)  $opts['image_height']  : 420;
    $autoplay      = isset( $opts['autoplay'] )      ? (bool) $opts['autoplay']      : true;
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'ThemisDB Front Slider – Einstellungen', 'themisdb-front-slider' ); ?></h1>
        <p><?php esc_html_e( 'Shortcode: [themisdb_front_slider]', 'themisdb-front-slider' ); ?></p>

        <form method="post" action="options.php">
            <?php settings_fields( 'themisdb_fs_options_group' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="posts_count"><?php esc_html_e( 'Anzahl Artikel', 'themisdb-front-slider' ); ?></label></th>
                    <td><input type="number" id="posts_count" name="themisdb_fs_options[posts_count]"
                               value="<?php echo esc_attr( $posts_count ); ?>" min="1" max="20" class="small-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="interval"><?php esc_html_e( 'Timer-Intervall (ms)', 'themisdb-front-slider' ); ?></label></th>
                    <td><input type="number" id="interval" name="themisdb_fs_options[interval]"
                               value="<?php echo esc_attr( $interval ); ?>" min="1000" max="30000" step="500" class="small-text">
                        <p class="description"><?php esc_html_e( 'Zeit je Slide in Millisekunden (Standard: 5000 = 5 Sekunden)', 'themisdb-front-slider' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="category"><?php esc_html_e( 'Kategorie-Slug (optional)', 'themisdb-front-slider' ); ?></label></th>
                    <td><input type="text" id="category" name="themisdb_fs_options[category]"
                               value="<?php echo esc_attr( $category ); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Sichtbare Elemente', 'themisdb-front-slider' ); ?></th>
                    <td>
                        <label><input type="checkbox" name="themisdb_fs_options[show_excerpt]" value="1" <?php checked( $show_excerpt ); ?>>
                            <?php esc_html_e( 'Auszug anzeigen', 'themisdb-front-slider' ); ?></label><br>
                        <label><input type="checkbox" name="themisdb_fs_options[show_date]" value="1" <?php checked( $show_date ); ?>>
                            <?php esc_html_e( 'Datum anzeigen', 'themisdb-front-slider' ); ?></label><br>
                        <label><input type="checkbox" name="themisdb_fs_options[show_category]" value="1" <?php checked( $show_category ); ?>>
                            <?php esc_html_e( 'Kategorie anzeigen', 'themisdb-front-slider' ); ?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="image_height"><?php esc_html_e( 'Bildhöhe (px)', 'themisdb-front-slider' ); ?></label></th>
                    <td><input type="number" id="image_height" name="themisdb_fs_options[image_height]"
                               value="<?php echo esc_attr( $image_height ); ?>" min="200" max="900" class="small-text"></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Autoplay', 'themisdb-front-slider' ); ?></th>
                    <td>
                        <label><input type="checkbox" name="themisdb_fs_options[autoplay]" value="1" <?php checked( $autoplay ); ?>>
                            <?php esc_html_e( 'Slider automatisch weiterschalten', 'themisdb-front-slider' ); ?></label>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
