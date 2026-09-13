<?php
/**
 * Plugin Name: ThemisDB Front Slider
 * Plugin URI: https://github.com/makr-code/wordpressPlugins
 * Update URI: https://github.com/makr-code/wordpressPlugins
 * Description: Titelseiten-Slider mit Timer, der die neuesten Artikel auf der Hauptseite darstellt. Shortcode: [themisdb_front_slider]
 * Version: 1.1.5
 * Author: makr-code
 * Author URI: https://github.com/makr-code/wordpressPlugins
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: themisdb-front-slider
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define('THEMISDB_FS_VERSION', '1.1.5');
define( 'THEMISDB_FS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'THEMISDB_FS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'THEMISDB_FS_PLUGIN_FILE', __FILE__ );

add_action( 'init', 'themisdb_fs_register_assets' );
function themisdb_fs_register_assets() {
    $front_css_version = THEMISDB_FS_VERSION;
    $editor_css_version = THEMISDB_FS_VERSION;
    $front_js_version = THEMISDB_FS_VERSION;
    $block_js_version = THEMISDB_FS_VERSION;

    $front_css_file = THEMISDB_FS_PLUGIN_DIR . 'assets/css/front-slider.css';
    $editor_css_file = THEMISDB_FS_PLUGIN_DIR . 'assets/css/block-editor.css';
    $front_js_file = THEMISDB_FS_PLUGIN_DIR . 'assets/js/front-slider.js';
    $block_js_file = THEMISDB_FS_PLUGIN_DIR . 'assets/js/block.js';

    if ( file_exists( $front_css_file ) ) {
        $front_css_version .= '.' . (string) filemtime( $front_css_file );
    }
    if ( file_exists( $editor_css_file ) ) {
        $editor_css_version .= '.' . (string) filemtime( $editor_css_file );
    }
    if ( file_exists( $front_js_file ) ) {
        $front_js_version .= '.' . (string) filemtime( $front_js_file );
    }
    if ( file_exists( $block_js_file ) ) {
        $block_js_version .= '.' . (string) filemtime( $block_js_file );
    }

    wp_register_style(
        'themisdb-front-slider-css',
        THEMISDB_FS_PLUGIN_URL . 'assets/css/front-slider.css',
        array(),
        $front_css_version
    );

    wp_register_style(
        'themisdb-front-slider-editor-css',
        THEMISDB_FS_PLUGIN_URL . 'assets/css/block-editor.css',
        array( 'wp-edit-blocks', 'themisdb-front-slider-css' ),
        $editor_css_version
    );

    // Register slider controller script (always available as fallback).
    wp_register_script(
        'themisdb-front-slider-js',
        THEMISDB_FS_PLUGIN_URL . 'assets/js/front-slider.js',
        array(),
        $front_js_version,
        true
    );

    // Register Gutenberg block script.
    wp_register_script(
        'themisdb-front-slider-block-js',
        THEMISDB_FS_PLUGIN_URL . 'assets/js/block.js',
        array( 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-components', 'wp-block-editor', 'wp-server-side-render' ),
        $block_js_version,
        false
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
        'filter'        => 'hero',
        'hero_label'    => 'hero',
        'respect_reduced_motion' => false,
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

// Run late so theme/plugin integrations can enqueue first.
add_action( 'wp_enqueue_scripts', 'themisdb_fs_enqueue', 100 );
function themisdb_fs_enqueue() {
    // SOC: plugin owns baseline slider styling; themes only add adjustments.
    $should_enqueue_frontend_style = apply_filters(
        'themisdb_front_slider_enqueue_frontend_style',
        true
    );

    if ( $should_enqueue_frontend_style ) {
        wp_enqueue_style( 'themisdb-front-slider-css' );
    }

    // SOC: plugin owns slider behavior; themes should not replace controller logic.
    $should_enqueue_plugin_js = apply_filters(
        'themisdb_front_slider_enqueue_frontend_script',
        true
    );

    if ( $should_enqueue_plugin_js ) {
        wp_enqueue_script( 'themisdb-front-slider-js' );
    }
}

function themisdb_fs_get_post_type_labels() {
    $post_type_object = get_post_type_object( 'post' );

    if ( $post_type_object && isset( $post_type_object->labels ) ) {
        return $post_type_object->labels;
    }

    return null;
}

function themisdb_fs_get_default_readmore_text() {
    $labels = themisdb_fs_get_post_type_labels();

    if ( $labels && ! empty( $labels->view_item ) ) {
        return sanitize_text_field( $labels->view_item );
    }

    return esc_html__( 'Beitrag ansehen', 'themisdb-front-slider' );
}

function themisdb_fs_get_default_no_posts_text() {
    $labels = themisdb_fs_get_post_type_labels();

    if ( $labels && ! empty( $labels->not_found ) ) {
        return sanitize_text_field( $labels->not_found );
    }

    return esc_html__( 'Keine Inhalte gefunden.', 'themisdb-front-slider' );
}

function themisdb_fs_get_region_label( $category_slug ) {
    if ( ! empty( $category_slug ) ) {
        $term = get_category_by_slug( $category_slug );

        if ( $term && ! is_wp_error( $term ) && ! empty( $term->name ) ) {
            return sanitize_text_field( $term->name );
        }
    }

    $labels = themisdb_fs_get_post_type_labels();

    if ( $labels && ! empty( $labels->name ) ) {
        return sanitize_text_field( $labels->name );
    }

    return sanitize_text_field( get_bloginfo( 'name' ) );
}

function themisdb_fs_get_slider_labels( $category_slug, $readmore_text ) {
    $post_type_labels = themisdb_fs_get_post_type_labels();
    $singular_label = $post_type_labels && ! empty( $post_type_labels->singular_name )
        ? sanitize_text_field( $post_type_labels->singular_name )
        : esc_html__( 'Eintrag', 'themisdb-front-slider' );
    $plural_label = $post_type_labels && ! empty( $post_type_labels->name )
        ? sanitize_text_field( $post_type_labels->name )
        : esc_html__( 'Inhalte', 'themisdb-front-slider' );

    $labels = array(
        'region'        => themisdb_fs_get_region_label( $category_slug ),
        'previous'      => sprintf( __( 'Vorheriger %s', 'themisdb-front-slider' ), $singular_label ),
        'next'          => sprintf( __( 'Nächster %s', 'themisdb-front-slider' ), $singular_label ),
        'pagination'    => sprintf( __( 'Navigation für %s', 'themisdb-front-slider' ), $plural_label ),
        'slide'         => sprintf( __( '%1$s %2$s von %3$s', 'themisdb-front-slider' ), $singular_label, '%1$d', '%2$d' ),
        'readmore_aria' => __( '%1$s: %2$s', 'themisdb-front-slider' ),
        'empty'         => themisdb_fs_get_default_no_posts_text(),
    );

    if ( '' === $labels['region'] ) {
        $labels['region'] = sanitize_text_field( get_bloginfo( 'name' ) );
    }

    return apply_filters( 'themisdb_front_slider_labels', $labels, $category_slug, $readmore_text );
}

/**
 * Parse a comma/space separated tag slug list.
 *
 * @param string $raw Raw tag list.
 * @return string[]
 */
function themisdb_fs_parse_tag_slugs( $raw ) {
    $raw = sanitize_text_field( (string) $raw );
    $parts = preg_split( '/[\s,;]+/', $raw );
    if ( ! is_array( $parts ) ) {
        return array();
    }

    $tags = array();
    foreach ( $parts as $part ) {
        $slug = sanitize_title( (string) $part );
        if ( '' !== $slug ) {
            $tags[] = $slug;
        }
    }

    return array_values( array_unique( $tags ) );
}

/**
 * Parse free filter input for slider queries.
 *
 * Supported values:
 * - hero,featured
 * - tag:hero,featured
 * - category:news,blog
 * - search:vector index
 * - none|all|off|*
 *
 * @param string $raw Raw filter value.
 * @return array{mode:string,terms:string[],search:string,raw:string}
 */
function themisdb_fs_parse_filter( $raw ) {
    $raw = trim( sanitize_text_field( (string) $raw ) );
    if ( '' === $raw ) {
        return array(
            'mode'   => 'tag',
            'terms'  => array( 'hero' ),
            'search' => '',
            'raw'    => 'hero',
        );
    }

    $normalized = strtolower( $raw );
    if ( in_array( $normalized, array( 'none', 'all', 'off', '*' ), true ) ) {
        return array(
            'mode'   => 'none',
            'terms'  => array(),
            'search' => '',
            'raw'    => $raw,
        );
    }

    if ( 0 === strpos( $normalized, 'category:' ) ) {
        return array(
            'mode'   => 'category',
            'terms'  => themisdb_fs_parse_tag_slugs( substr( $raw, 9 ) ),
            'search' => '',
            'raw'    => $raw,
        );
    }

    if ( 0 === strpos( $normalized, 'search:' ) ) {
        $search = trim( (string) substr( $raw, 7 ) );
        return array(
            'mode'   => '' !== $search ? 'search' : 'none',
            'terms'  => array(),
            'search' => $search,
            'raw'    => $raw,
        );
    }

    $tag_source = 0 === strpos( $normalized, 'tag:' ) ? (string) substr( $raw, 4 ) : $raw;
    $terms      = themisdb_fs_parse_tag_slugs( $tag_source );
    if ( empty( $terms ) ) {
        $terms = array( 'hero' );
    }

    return array(
        'mode'   => 'tag',
        'terms'  => $terms,
        'search' => '',
        'raw'    => $raw,
    );
}

function themisdb_fs_compact_markup( $html ) {
    if ( ! is_string( $html ) || '' === $html ) {
        return '';
    }

    // Strip template comments.
    $html = preg_replace( '/<!--.*?-->/s', '', $html );

    // Normalize tag formatting to a single line per tag to avoid wpautop edge-cases
    // with multi-line opening tags inside shortcode output.
    $html = preg_replace_callback(
        '/<[^>]+>/s',
        static function( $match ) {
            $tag = preg_replace( '/\s+/', ' ', (string) $match[0] );
            $tag = preg_replace( '/\s*(\/?\s*)>$/', '$1>', (string) $tag );
            return trim( (string) $tag );
        },
        $html
    );

    // Collapse whitespace between tags while preserving inner text content.
    $html = preg_replace( '/>\s+</', '><', $html );

    // Trim whitespace around text nodes (e.g. link labels) to prevent wpautop
    // from injecting <br> tags into shortcode output.
    $html = preg_replace( '/>\s+([^<]*?)\s+</u', '>$1<', $html );

    // Defensive cleanup for occasional wpautop paragraph artifacts around block elements.
    $html = preg_replace( '/(?:<p>\s*)+(?=<(?:div|section|article|header|footer|nav|aside|main|figure|ul|ol|li|button)\b)/i', '', $html );
    $html = preg_replace( '/<\/p>\s*(?=<(?:div|section|article|header|footer|nav|aside|main|figure|ul|ol|li|button)\b)/i', '', $html );

    return trim( (string) $html );
}

/**
 * Resolve a published podcast episode linked to a post via related_post_id.
 *
 * @param int $post_id Post ID.
 * @return array{url:string,label:string,style:string}|null
 */
function themisdb_fs_get_related_podcast_cta( $post_id ) {
    $post_id = (int) $post_id;
    if ( $post_id <= 0 || ! post_type_exists( 'pod_episode' ) ) {
        return null;
    }

    static $cache = array();
    if ( array_key_exists( $post_id, $cache ) ) {
        return $cache[ $post_id ];
    }

    $episode_query = new WP_Query(
        array(
            'post_type'              => 'pod_episode',
            'post_status'            => 'publish',
            'posts_per_page'         => 1,
            'orderby'                => 'date',
            'order'                  => 'DESC',
            'ignore_sticky_posts'    => true,
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'meta_query'             => array(
                array(
                    'key'     => 'related_post_id',
                    'value'   => $post_id,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ),
            ),
        )
    );

    if ( ! $episode_query->have_posts() ) {
        $cache[ $post_id ] = null;
        return null;
    }

    $episode_id = (int) $episode_query->posts[0]->ID;
    $episode_url = (string) get_permalink( $episode_id );
    if ( '' === $episode_url ) {
        $cache[ $post_id ] = null;
        return null;
    }

    $cache[ $post_id ] = array(
        'label' => esc_html__( 'Podcast anhoeren', 'themisdb-front-slider' ),
        'url'   => esc_url_raw( $episode_url ),
        'style' => 'tertiary',
    );

    return $cache[ $post_id ];
}

/**
 * Collect optional per-post CTA buttons for hero slides.
 *
 * Supported meta formats:
 * - themisdb_hero_cta_buttons (JSON array of objects with label/url/style)
 * - themisdb_hero_cta_1_label + themisdb_hero_cta_1_url (+ optional _style)
 * - themisdb_hero_cta_2_label + themisdb_hero_cta_2_url (+ optional _style)
 *
 * @param int $post_id Post ID.
 * @param int $limit   Maximum number of additional buttons.
 * @return array<int,array<string,string>>
 */
function themisdb_fs_get_post_cta_buttons( $post_id, $limit = 2 ) {
    $post_id = (int) $post_id;
    $limit = max( 0, min( 4, (int) $limit ) );
    if ( $post_id <= 0 || 0 === $limit ) {
        return array();
    }

    $items = array();

    $json_raw = get_post_meta( $post_id, 'themisdb_hero_cta_buttons', true );
    if ( is_string( $json_raw ) && '' !== trim( $json_raw ) ) {
        $decoded = json_decode( $json_raw, true );
        if ( is_array( $decoded ) ) {
            foreach ( $decoded as $entry ) {
                if ( ! is_array( $entry ) ) {
                    continue;
                }
                $label = isset( $entry['label'] ) ? sanitize_text_field( (string) $entry['label'] ) : '';
                $url = isset( $entry['url'] ) ? esc_url_raw( (string) $entry['url'] ) : '';
                $style = isset( $entry['style'] ) ? sanitize_key( (string) $entry['style'] ) : '';

                if ( '' === $label || '' === $url ) {
                    continue;
                }

                $items[] = array(
                    'label' => $label,
                    'url'   => $url,
                    'style' => in_array( $style, array( 'secondary', 'tertiary' ), true ) ? $style : 'secondary',
                );
            }
        }
    }

    for ( $i = 1; $i <= 2; $i++ ) {
        $label = sanitize_text_field( (string) get_post_meta( $post_id, 'themisdb_hero_cta_' . $i . '_label', true ) );
        $url = esc_url_raw( (string) get_post_meta( $post_id, 'themisdb_hero_cta_' . $i . '_url', true ) );
        $style = sanitize_key( (string) get_post_meta( $post_id, 'themisdb_hero_cta_' . $i . '_style', true ) );

        if ( '' === $label || '' === $url ) {
            continue;
        }

        $items[] = array(
            'label' => $label,
            'url'   => $url,
            'style' => in_array( $style, array( 'secondary', 'tertiary' ), true ) ? $style : 'secondary',
        );
    }

    $podcast_cta = themisdb_fs_get_related_podcast_cta( $post_id );
    if ( is_array( $podcast_cta ) ) {
        $items[] = $podcast_cta;
    }

    $items = array_values( array_unique( $items, SORT_REGULAR ) );
    $items = array_slice( $items, 0, $limit );

    return apply_filters( 'themisdb_front_slider_post_cta_buttons', $items, $post_id, $limit );
}

/**
 * Sanitize CSS background-position values for hero slide focus points.
 *
 * Accepts either keyword pairs (e.g. "left top") or percentage pairs
 * (e.g. "50% 35%"). Falls back to center when invalid.
 *
 * @param string $raw_position Raw user-supplied position value.
 * @return string
 */
function themisdb_fs_sanitize_background_position( $raw_position ) {
    $position = strtolower( trim( (string) $raw_position ) );
    if ( '' === $position ) {
        return '50% 50%';
    }

    if ( preg_match( '/^([0-9]{1,3})%\s+([0-9]{1,3})%$/', $position, $match ) ) {
        $x = max( 0, min( 100, (int) $match[1] ) );
        $y = max( 0, min( 100, (int) $match[2] ) );
        return $x . '% ' . $y . '%';
    }

    $keywords = array( 'left', 'center', 'right', 'top', 'bottom' );
    $parts = preg_split( '/\s+/', $position );
    if ( is_array( $parts ) && 2 === count( $parts ) ) {
        $x = in_array( $parts[0], $keywords, true ) ? $parts[0] : '';
        $y = in_array( $parts[1], $keywords, true ) ? $parts[1] : '';
        if ( '' !== $x && '' !== $y ) {
            return $x . ' ' . $y;
        }
    }

    return '50% 50%';
}

/**
 * Resolve a visual focus position for a slide background image.
 *
 * Priority:
 * 1) Post meta override via themisdb_hero_bg_focus (e.g. "50% 30%")
 * 2) Image orientation heuristic based on attachment metadata
 * 3) Center fallback
 *
 * @param int $post_id  Post ID.
 * @param int $thumb_id Featured image attachment ID.
 * @return string
 */
function themisdb_fs_get_slide_background_position( $post_id, $thumb_id = 0 ) {
    $post_id = (int) $post_id;
    $thumb_id = (int) $thumb_id;

    $meta_position = get_post_meta( $post_id, 'themisdb_hero_bg_focus', true );
    if ( is_string( $meta_position ) && '' !== trim( $meta_position ) ) {
        return themisdb_fs_sanitize_background_position( $meta_position );
    }

    if ( $thumb_id > 0 ) {
        $attachment_meta = wp_get_attachment_metadata( $thumb_id );
        if ( is_array( $attachment_meta ) ) {
            $width = isset( $attachment_meta['width'] ) ? (int) $attachment_meta['width'] : 0;
            $height = isset( $attachment_meta['height'] ) ? (int) $attachment_meta['height'] : 0;
            if ( $width > 0 && $height > 0 ) {
                if ( $height > ( $width * 1.15 ) ) {
                    return '50% 28%';
                }
                if ( $width > ( $height * 1.7 ) ) {
                    return '50% 42%';
                }
            }
        }
    }

    return '50% 50%';
}

/**
 * Resolve a mobile visual focus position for a slide background image.
 *
 * Priority:
 * 1) Post meta override via themisdb_hero_bg_focus_mobile
 * 2) Mobile-oriented image orientation heuristic
 * 3) Desktop focus as fallback
 *
 * @param int $post_id  Post ID.
 * @param int $thumb_id Featured image attachment ID.
 * @return string
 */
function themisdb_fs_get_slide_background_position_mobile( $post_id, $thumb_id = 0 ) {
    $post_id = (int) $post_id;
    $thumb_id = (int) $thumb_id;

    $meta_position = get_post_meta( $post_id, 'themisdb_hero_bg_focus_mobile', true );
    if ( is_string( $meta_position ) && '' !== trim( $meta_position ) ) {
        return themisdb_fs_sanitize_background_position( $meta_position );
    }

    if ( $thumb_id > 0 ) {
        $attachment_meta = wp_get_attachment_metadata( $thumb_id );
        if ( is_array( $attachment_meta ) ) {
            $width = isset( $attachment_meta['width'] ) ? (int) $attachment_meta['width'] : 0;
            $height = isset( $attachment_meta['height'] ) ? (int) $attachment_meta['height'] : 0;
            if ( $width > 0 && $height > 0 ) {
                if ( $height > ( $width * 1.15 ) ) {
                    return '50% 24%';
                }
                if ( $width > ( $height * 1.7 ) ) {
                    return '50% 36%';
                }
            }
        }
    }

    return themisdb_fs_get_slide_background_position( $post_id, $thumb_id );
}

/**
 * Validate optional background-position input for editor UI.
 *
 * @param string $raw_position Raw position value.
 * @return string Empty string when invalid/empty, otherwise normalized value.
 */
function themisdb_fs_validate_optional_background_position( $raw_position ) {
    $raw_position = trim( (string) $raw_position );
    if ( '' === $raw_position ) {
        return '';
    }

    $normalized = themisdb_fs_sanitize_background_position( $raw_position );
    if ( '50% 50%' === $normalized ) {
        $candidate = strtolower( trim( (string) $raw_position ) );
        if ( '50% 50%' !== $candidate && 'center center' !== $candidate ) {
            return '';
        }
    }

    return $normalized;
}

/**
 * Add per-post hero background focus field to post/page editors.
 */
add_action( 'add_meta_boxes', 'themisdb_fs_register_hero_focus_metabox' );
function themisdb_fs_register_hero_focus_metabox() {
    $post_types = apply_filters( 'themisdb_front_slider_focus_meta_post_types', array( 'post', 'page' ) );
    if ( ! is_array( $post_types ) || empty( $post_types ) ) {
        return;
    }

    foreach ( $post_types as $post_type ) {
        $post_type = sanitize_key( (string) $post_type );
        if ( '' === $post_type ) {
            continue;
        }

        add_meta_box(
            'themisdb_fs_hero_focus',
            __( 'Hero Slider Bildfokus', 'themisdb-front-slider' ),
            'themisdb_fs_render_hero_focus_metabox',
            $post_type,
            'side',
            'default'
        );
    }
}

/**
 * Render hero focus metabox content.
 *
 * @param WP_Post $post Current post object.
 */
function themisdb_fs_render_hero_focus_metabox( $post ) {
    if ( ! $post || ! isset( $post->ID ) ) {
        return;
    }

    $current = (string) get_post_meta( (int) $post->ID, 'themisdb_hero_bg_focus', true );
    $current_mobile = (string) get_post_meta( (int) $post->ID, 'themisdb_hero_bg_focus_mobile', true );
    wp_nonce_field( 'themisdb_fs_save_hero_focus', 'themisdb_fs_hero_focus_nonce' );
    ?>
    <p>
        <label for="themisdb_fs_hero_bg_focus">
            <?php esc_html_e( 'Hintergrund-Fokus (optional)', 'themisdb-front-slider' ); ?>
        </label>
        <input
            type="text"
            class="widefat"
            id="themisdb_fs_hero_bg_focus"
            name="themisdb_fs_hero_bg_focus"
            value="<?php echo esc_attr( $current ); ?>"
            placeholder="50% 35%"
            autocomplete="off"
        />
    </p>
    <p class="description">
        <?php esc_html_e( 'Format: "50% 35%" oder "left top". Leer lassen = automatische Erkennung.', 'themisdb-front-slider' ); ?>
    </p>
    <p>
        <label for="themisdb_fs_hero_bg_focus_mobile">
            <?php esc_html_e( 'Hintergrund-Fokus mobil (optional)', 'themisdb-front-slider' ); ?>
        </label>
        <input
            type="text"
            class="widefat"
            id="themisdb_fs_hero_bg_focus_mobile"
            name="themisdb_fs_hero_bg_focus_mobile"
            value="<?php echo esc_attr( $current_mobile ); ?>"
            placeholder="50% 30%"
            autocomplete="off"
        />
    </p>
    <p class="description">
        <?php esc_html_e( 'Ueberschreibt den Desktop-Wert auf kleineren Viewports. Leer lassen = mobile Automatik.', 'themisdb-front-slider' ); ?>
    </p>
    <?php
}

/**
 * Save hero focus metabox value.
 *
 * @param int $post_id Post ID.
 */
add_action( 'save_post', 'themisdb_fs_save_hero_focus_metabox' );
function themisdb_fs_save_hero_focus_metabox( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
        return;
    }

    if ( ! isset( $_POST['themisdb_fs_hero_focus_nonce'] ) ) {
        return;
    }

    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['themisdb_fs_hero_focus_nonce'] ) ), 'themisdb_fs_save_hero_focus' ) ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    if ( ! isset( $_POST['themisdb_fs_hero_bg_focus'] ) && ! isset( $_POST['themisdb_fs_hero_bg_focus_mobile'] ) ) {
        return;
    }

    $desktop_raw = isset( $_POST['themisdb_fs_hero_bg_focus'] ) ? wp_unslash( $_POST['themisdb_fs_hero_bg_focus'] ) : '';
    $desktop_value = themisdb_fs_validate_optional_background_position( $desktop_raw );
    $mobile_raw = isset( $_POST['themisdb_fs_hero_bg_focus_mobile'] ) ? wp_unslash( $_POST['themisdb_fs_hero_bg_focus_mobile'] ) : '';
    $mobile_value = themisdb_fs_validate_optional_background_position( $mobile_raw );

    if ( '' === $desktop_value ) {
        delete_post_meta( $post_id, 'themisdb_hero_bg_focus' );
    } else {
        update_post_meta( $post_id, 'themisdb_hero_bg_focus', $desktop_value );
    }

    if ( '' === $mobile_value ) {
        delete_post_meta( $post_id, 'themisdb_hero_bg_focus_mobile' );
    } else {
        update_post_meta( $post_id, 'themisdb_hero_bg_focus_mobile', $mobile_value );
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
 *   filter        – free filter                      (default: 'hero')
 *   hero_label    – tag slug(s) used to select hero posts/pages (default: 'hero')
 *   accent_color  – accent hex color                (default: #0284c7)
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
    if ( isset( $raw_atts['tag'] ) && ! isset( $raw_atts['filter'] ) ) {
        $raw_atts['filter'] = $raw_atts['tag'];
    }
    if ( isset( $raw_atts['hero_label'] ) && ! isset( $raw_atts['filter'] ) ) {
        $raw_atts['filter'] = $raw_atts['hero_label'];
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
            'filter'        => isset( $opts['filter'] )        ? $opts['filter']        : ( isset( $opts['hero_label'] ) ? $opts['hero_label'] : 'hero' ),
            'hero_label'    => isset( $opts['hero_label'] )    ? $opts['hero_label']    : 'hero',
            'accent_color'  => '#0284c7',
            'img_size'      => 'large',
            'layout_preset' => 'standard',
            'respect_reduced_motion' => isset( $opts['respect_reduced_motion'] ) ? $opts['respect_reduced_motion'] : false,
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
    $filter_meta   = themisdb_fs_parse_filter( $atts['filter'] );
    $hero_tags     = 'tag' === $filter_meta['mode'] ? (array) $filter_meta['terms'] : array();
    if ( empty( $hero_tags ) ) {
        $hero_tags = array( 'hero' );
    }
    $hero_label    = implode( ',', $hero_tags );
    $raw_accent    = (string) $atts['accent_color'];
    $accent_color  = preg_match( '/^#[0-9a-fA-F]{3,6}$/', $raw_accent ) ? $raw_accent : '#0284c7';
    $readmore_text = themisdb_fs_get_default_readmore_text();
    $image_size    = sanitize_key( (string) $atts['img_size'] );
    $image_size    = in_array( $image_size, array( 'thumbnail', 'medium', 'medium_large', 'large', 'full' ), true ) ? $image_size : 'large';
    $layout_preset = sanitize_key( (string) $atts['layout_preset'] );
    $layout_preset = in_array( $layout_preset, array( 'standard', 'compact', 'magazine' ), true ) ? $layout_preset : 'standard';
    $respect_reduced_motion = filter_var( $atts['respect_reduced_motion'], FILTER_VALIDATE_BOOLEAN );
    $labels        = themisdb_fs_get_slider_labels( $category, $readmore_text );

    // Query hero content (posts + pages) filtered by configurable tag slugs.
    $query_args = array(
        'post_type'           => array( 'post', 'page' ),
        'post_status'         => 'publish',
        'posts_per_page'      => $posts_count,
        'ignore_sticky_posts' => false,
        'orderby'             => 'date',
        'order'               => 'DESC',
    );

    if ( ! empty( $category ) ) {
        $query_args['category_name'] = $category;
    }

    if ( 'tag' === $filter_meta['mode'] && ! empty( $filter_meta['terms'] ) ) {
        $query_args['tax_query'] = array(
            array(
                'taxonomy' => 'post_tag',
                'field'    => 'slug',
                'terms'    => $filter_meta['terms'],
                'operator' => 'IN',
            ),
        );
    } elseif ( 'category' === $filter_meta['mode'] && ! empty( $filter_meta['terms'] ) ) {
        $query_args['category_name'] = implode( ',', array_map( 'sanitize_title', $filter_meta['terms'] ) );
    } elseif ( 'search' === $filter_meta['mode'] && '' !== $filter_meta['search'] ) {
        $query_args['s'] = sanitize_text_field( $filter_meta['search'] );
    }

    $query_args = apply_filters( 'themisdb_front_slider_shortcode_query_args', $query_args, $atts );

    $query = new WP_Query( $query_args );

    if ( ! $query->have_posts() ) {
        return '<p class="themisdb-fs-no-posts">' . esc_html( $labels['empty'] ) . '</p>';
    }

    $slides = array();
    foreach ( $query->posts as $post_obj ) {
        $slides[] = array(
            'id' => (int) $post_obj->ID,
            'title' => (string) get_the_title( $post_obj->ID ),
            'url' => (string) get_permalink( $post_obj->ID ),
            'date' => (string) get_the_date( '', $post_obj->ID ),
            'excerpt' => (string) get_the_excerpt( $post_obj->ID ),
            'thumbnail' => (string) get_the_post_thumbnail_url( $post_obj->ID, 'full' ),
        );
    }

    $initial_active_id = 0;
    if ( ! empty( $slides ) ) {
        $initial_active_id = (int) $slides[0]['id'];
        $found_image_slide = false;

        // Prefer a slide with a featured image so the blended hero background is
        // visually available immediately on first paint.
        foreach ( $slides as $slide_meta ) {
            $has_thumb = ! empty( trim( (string) $slide_meta['thumbnail'] ) );
            if ( $has_thumb ) {
                $initial_active_id = (int) $slide_meta['id'];
                $found_image_slide = true;
                break;
            }
        }

        // Fallback to first slide with teaser text when no featured image exists.
        if ( ! $found_image_slide ) {
            foreach ( $slides as $slide_meta ) {
                $has_teaser = ! empty( trim( (string) $slide_meta['excerpt'] ) );
                if ( $has_teaser ) {
                    $initial_active_id = (int) $slide_meta['id'];
                    break;
                }
            }
        }
    }

    $payload = array(
        'query'         => $query,
        'posts_count'   => $posts_count,
        'interval'      => $interval,
        'category'      => $category,
        'show_excerpt'  => $show_excerpt,
        'show_date'     => $show_date,
        'show_category' => $show_category,
        'autoplay'      => $autoplay,
        'filter'        => (string) $atts['filter'],
        'filter_meta'   => $filter_meta,
        'hero_label'    => $hero_label,
        'accent_color'  => $accent_color,
        'readmore_text' => $readmore_text,
        'image_size'    => $image_size,
        'layout_preset' => $layout_preset,
        'respect_reduced_motion' => $respect_reduced_motion,
        'labels'        => $labels,
        'query_args'    => $query_args,
        'slides'        => $slides,
        'initial_active_id' => $initial_active_id,
    );
    $payload = apply_filters( 'themisdb_front_slider_shortcode_payload', $payload, $atts );

    $custom_html = apply_filters( 'themisdb_front_slider_shortcode_html', null, $payload, $atts );
    if ( null !== $custom_html ) {
        wp_reset_postdata();
        return (string) $custom_html;
    }

    ob_start();
    // Extract payload variables for template access
    extract( $payload, EXTR_SKIP );
    include THEMISDB_FS_PLUGIN_DIR . 'templates/slider.php';
    $html = ob_get_clean();
    $html = themisdb_fs_compact_markup( (string) $html );
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

    // Script is pre-registered in themisdb_fs_register_assets()
    if ( function_exists( 'register_block_type_from_metadata' ) && file_exists( $block_json_path ) ) {
        register_block_type_from_metadata(
            THEMISDB_FS_PLUGIN_DIR,
            array(
                'editor_script'   => 'themisdb-front-slider-block-js',
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
            'editor_script'   => 'themisdb-front-slider-block-js',
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
                'filter' => array(
                    'type'    => 'string',
                    'default' => 'hero',
                ),
                'hero_label' => array(
                    'type'    => 'string',
                    'default' => 'hero',
                ),
                'accent_color' => array(
                    'type'    => 'string',
                    'default' => '#0284c7',
                ),
                'img_size' => array(
                    'type'    => 'string',
                    'default' => 'large',
                ),
                'layout_preset' => array(
                    'type'    => 'string',
                    'default' => 'standard',
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
        'posts'         => isset( $attributes['posts'] )         ? $attributes['posts']         : null,
        'interval'      => isset( $attributes['interval'] )      ? $attributes['interval']      : null,
        'category'      => isset( $attributes['category'] )      ? $attributes['category']      : null,
        'excerpt'       => isset( $attributes['excerpt'] )       ? $attributes['excerpt']       : null,
        'date'          => isset( $attributes['date'] )          ? $attributes['date']          : null,
        'cat_label'     => isset( $attributes['cat_label'] )     ? $attributes['cat_label']     : null,
        'autoplay'      => isset( $attributes['autoplay'] )      ? $attributes['autoplay']      : null,
        'filter'        => isset( $attributes['filter'] )        ? $attributes['filter']        : null,
        'hero_label'    => isset( $attributes['hero_label'] )    ? $attributes['hero_label']    : null,
        'accent_color'  => isset( $attributes['accent_color'] )  ? $attributes['accent_color']  : null,
        'img_size'      => isset( $attributes['img_size'] )      ? $attributes['img_size']      : null,
        'layout_preset' => isset( $attributes['layout_preset'] ) ? $attributes['layout_preset'] : null,
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
    $raw_filter             = isset( $input['filter'] ) ? $input['filter'] : ( isset( $input['hero_label'] ) ? $input['hero_label'] : 'hero' );
    $filter                 = themisdb_fs_parse_filter( $raw_filter );
    $clean['filter']        = $filter['raw'];
    $clean['hero_label']    = 'tag' === $filter['mode'] ? implode( ',', (array) $filter['terms'] ) : 'hero';
    $clean['show_excerpt']  = ! empty( $input['show_excerpt'] );
    $clean['show_date']     = ! empty( $input['show_date'] );
    $clean['show_category'] = ! empty( $input['show_category'] );
    $clean['autoplay']      = ! empty( $input['autoplay'] );
    $clean['respect_reduced_motion'] = ! empty( $input['respect_reduced_motion'] );
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
    $filter        = isset( $opts['filter'] )        ?        $opts['filter']        : ( isset( $opts['hero_label'] ) ? $opts['hero_label'] : 'hero' );
    $hero_label    = isset( $opts['hero_label'] )    ?        $opts['hero_label']    : 'hero';
    $show_excerpt  = isset( $opts['show_excerpt'] )  ? (bool) $opts['show_excerpt']  : true;
    $show_date     = isset( $opts['show_date'] )     ? (bool) $opts['show_date']     : true;
    $show_category = isset( $opts['show_category'] ) ? (bool) $opts['show_category'] : true;
    $autoplay      = isset( $opts['autoplay'] )      ? (bool) $opts['autoplay']      : true;
    $respect_reduced_motion = isset( $opts['respect_reduced_motion'] ) ? (bool) $opts['respect_reduced_motion'] : false;

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
                            <tr><th><?php esc_html_e( 'Filter', 'themisdb-front-slider' ); ?></th><td><?php echo esc_html( $filter ); ?></td></tr>
                            <tr><th><?php esc_html_e( 'Autoplay', 'themisdb-front-slider' ); ?></th><td><?php echo $autoplay ? esc_html__( 'Aktiv', 'themisdb-front-slider' ) : esc_html__( 'Deaktiviert', 'themisdb-front-slider' ); ?></td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="card">
                    <h2><?php esc_html_e( 'Bildfokus Schnellhilfe', 'themisdb-front-slider' ); ?></h2>
                    <p><?php esc_html_e( 'Pro Beitrag/Seite kann im Editor-Feld „Hero Slider Bildfokus“ ein Fokuspunkt gesetzt werden.', 'themisdb-front-slider' ); ?></p>
                    <table class="widefat striped">
                        <tbody>
                            <tr><th><?php esc_html_e( 'Portraet', 'themisdb-front-slider' ); ?></th><td><code>50% 28%</code></td></tr>
                            <tr><th><?php esc_html_e( 'Landschaft', 'themisdb-front-slider' ); ?></th><td><code>50% 42%</code></td></tr>
                            <tr><th><?php esc_html_e( 'Gesicht links', 'themisdb-front-slider' ); ?></th><td><code>35% 35%</code></td></tr>
                            <tr><th><?php esc_html_e( 'Gesicht rechts', 'themisdb-front-slider' ); ?></th><td><code>65% 35%</code></td></tr>
                            <tr><th><?php esc_html_e( 'Obere Motivkante', 'themisdb-front-slider' ); ?></th><td><code>50% 20%</code></td></tr>
                        </tbody>
                    </table>
                    <p class="description"><?php esc_html_e( 'Gueltige Formate: Prozentpaare (x% y%) oder Keywords (left|center|right + top|center|bottom). Leer lassen nutzt die Automatik.', 'themisdb-front-slider' ); ?></p>
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
                        <th scope="row">
                            <label for="filter"><?php esc_html_e( 'Freier Filter', 'themisdb-front-slider' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="filter" name="themisdb_fs_options[filter]"
                                   value="<?php echo esc_attr( $filter ); ?>" class="regular-text">
                            <p class="description"><?php esc_html_e( 'Standard: hero. Beispiele: hero,featured | tag:hero,featured | category:news | search:vector | none', 'themisdb-front-slider' ); ?></p>
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
                            <br>
                            <label>
                                <input type="checkbox" name="themisdb_fs_options[respect_reduced_motion]" value="1" <?php checked( $respect_reduced_motion ); ?>>
                                <?php esc_html_e( 'Systemeinstellung „Bewegung reduzieren" respektieren', 'themisdb-front-slider' ); ?>
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
                        <td><code>[themisdb_front_slider filter="hero,featured"]</code></td>
                        <td><?php esc_html_e( 'Freier Filter auf Tag-Slugs.', 'themisdb-front-slider' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>[themisdb_front_slider filter="category:news"]</code></td>
                        <td><?php esc_html_e( 'Nur Inhalte aus der Kategorie „news" anzeigen.', 'themisdb-front-slider' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>[themisdb_front_slider interval="3000" autoplay="yes"]</code></td>
                        <td><?php esc_html_e( 'Autoplay mit 3-Sekunden-Intervall.', 'themisdb-front-slider' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>[themisdb_front_slider excerpt="no" date="no"]</code></td>
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
                        <td><code>filter</code></td>
                        <td><?php esc_html_e( 'Freier Filter: tags (default), category:, search:, oder none', 'themisdb-front-slider' ); ?></td>
                        <td><?php echo esc_html( $filter ); ?></td>
                    </tr>
                    <tr>
                        <td><code>hero_label</code></td>
                        <td><?php esc_html_e( 'Legacy-Alias für tagbasierten Filter', 'themisdb-front-slider' ); ?></td>
                        <td><?php echo esc_html( $hero_label ); ?></td>
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


