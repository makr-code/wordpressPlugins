<?php
/**
 * ThemisDB Theme Functions
 *
 * @package ThemisDB
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Theme setup
 */
function themisdb_setup() {
    // Make theme available for translation
    load_theme_textdomain( 'themisdb', get_template_directory() . '/languages' );

    // Add default posts and comments RSS feed links to head
    add_theme_support( 'automatic-feed-links' );

    // Let WordPress manage the document title
    add_theme_support( 'title-tag' );

    // Enable support for Post Thumbnails
    add_theme_support( 'post-thumbnails' );
    set_post_thumbnail_size( 1200, 675, true );

    // Add custom image sizes
    add_image_size( 'themisdb-featured', 1200, 675, true );
    add_image_size( 'themisdb-thumbnail', 400, 300, true );

    // Register navigation menus
    register_nav_menus( array(
        'primary'   => esc_html__( 'Primary Menu', 'themisdb' ),
        'footer'    => esc_html__( 'Footer Menu', 'themisdb' ),
        'hamburger' => esc_html__( 'Hamburger Menu', 'themisdb' ),
    ) );

    // Switch default core markup to output valid HTML5
    add_theme_support( 'html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ) );

    // Add theme support for selective refresh for widgets
    add_theme_support( 'customize-selective-refresh-widgets' );

    // Add support for custom logo
    add_theme_support( 'custom-logo', array(
        'height'      => 60,
        'width'       => 200,
        'flex-height' => true,
        'flex-width'  => true,
    ) );

    // Add support for custom background
    add_theme_support( 'custom-background', array(
        'default-color' => 'f8f9fa',
    ) );

    // Add support for custom header
    add_theme_support( 'custom-header', array(
        'default-image' => '',
        'width'         => 1920,
        'height'        => 400,
        'flex-width'    => true,
        'flex-height'   => true,
    ) );

    // Add support for editor styles
    add_theme_support( 'editor-styles' );
    add_editor_style( 'editor-style.css' );

    // Add support for responsive embeds
    add_theme_support( 'responsive-embeds' );

    // Add support for wide alignment
    add_theme_support( 'align-wide' );

    // Add support for editor color palette (v3 color scheme)
    add_theme_support( 'editor-color-palette', array(
        array(
            'name'  => esc_html__( 'Navy (Primary)', 'themisdb' ),
            'slug'  => 'primary',
            'color' => '#1a2e52',
        ),
        array(
            'name'  => esc_html__( 'Azure Blue', 'themisdb' ),
            'slug'  => 'secondary',
            'color' => '#1e6fba',
        ),
        array(
            'name'  => esc_html__( 'Teal-Cyan', 'themisdb' ),
            'slug'  => 'accent-purple',
            'color' => '#1ab5c8',
        ),
        array(
            'name'  => esc_html__( 'Forest Green', 'themisdb' ),
            'slug'  => 'success',
            'color' => '#1a6e46',
        ),
        array(
            'name'  => esc_html__( 'Amber', 'themisdb' ),
            'slug'  => 'warning',
            'color' => '#d68910',
        ),
        array(
            'name'  => esc_html__( 'Danger', 'themisdb' ),
            'slug'  => 'danger',
            'color' => '#c0392b',
        ),
    ) );
}
add_action( 'after_setup_theme', 'themisdb_setup' );

/**
 * Set the content width in pixels
 */
function themisdb_content_width() {
    $GLOBALS['content_width'] = apply_filters( 'themisdb_content_width', 1200 );
}
add_action( 'after_setup_theme', 'themisdb_content_width', 0 );

/**
 * Flush rewrite rules on theme switch so permalink structures like
 * "Post name" (/%postname%/) resolve correctly immediately without
 * requiring a manual visit to Settings → Permalinks.
 */
function themisdb_flush_rewrite_on_switch() {
    flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'themisdb_flush_rewrite_on_switch' );

/**
 * Run a one-time rewrite refresh after updates while the theme is active.
 */
function themisdb_maybe_flush_rewrite_rules() {
    $theme        = wp_get_theme();
    $theme_version = (string) $theme->get( 'Version' );
    $option_key   = 'themisdb_theme_rewrite_flushed_version';

    if ( get_option( $option_key ) === $theme_version ) {
        return;
    }

    flush_rewrite_rules( false );
    update_option( $option_key, $theme_version );
}
add_action( 'init', 'themisdb_maybe_flush_rewrite_rules', 20 );

/**
 * Optional permalink fallback for environments with unreliable rewrite rules.
 * Disabled by default to keep WordPress-native behavior.
 */
function themisdb_is_plesk_permalink_fallback_enabled() {
    $enabled = (bool) get_theme_mod( 'themisdb_enable_plesk_permalink_fallback', false );

    return (bool) apply_filters( 'themisdb_enable_plesk_permalink_fallback', $enabled );
}

function themisdb_maybe_redirect_404_to_resolved_permalink() {
    if ( ! themisdb_is_plesk_permalink_fallback_enabled() ) {
        return;
    }

    if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
        return;
    }

    if ( ! is_404() || ! empty( $_GET ) ) {
        return;
    }

    $request_uri    = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
    $request_path   = (string) wp_parse_url( $request_uri, PHP_URL_PATH );
    $normalized_path = trim( $request_path, '/' );

    if ( '' === $normalized_path ) {
        return;
    }

    if ( preg_match( '/\.(?:css|js|map|png|jpe?g|gif|svg|webp|ico|txt|xml|json|woff2?)$/i', $normalized_path ) ) {
        return;
    }

    $target_url = '';
    $page = get_page_by_path( $normalized_path, OBJECT, 'page' );

    if ( $page instanceof WP_Post ) {
        $target_url = get_permalink( $page->ID );
    } else {
        $post = get_page_by_path( $normalized_path, OBJECT, 'post' );
        if ( $post instanceof WP_Post ) {
            $target_url = get_permalink( $post->ID );
        }
    }

    if ( empty( $target_url ) ) {
        return;
    }

    wp_safe_redirect( $target_url, 301 );
    exit;
}
add_action( 'template_redirect', 'themisdb_maybe_redirect_404_to_resolved_permalink', 1 );

/**
 * Provide a 60-second cron interval used by some scheduling libraries.
 */
function themisdb_add_every_minute_schedule( $schedules ) {
    if ( ! isset( $schedules['every_minute'] ) ) {
        $schedules['every_minute'] = array(
            'interval' => 60,
            'display'  => __( 'Every Minute', 'themisdb' ),
        );
    }

    return $schedules;
}
add_filter( 'cron_schedules', 'themisdb_add_every_minute_schedule' );

/**
 * Register widget areas
 */
function themisdb_widgets_init() {
    register_sidebar( array(
        'name'          => esc_html__( 'Sidebar', 'themisdb' ),
        'id'            => 'sidebar-1',
        'description'   => esc_html__( 'Add widgets here to appear in your sidebar.', 'themisdb' ),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ) );

    register_sidebar( array(
        'name'          => esc_html__( 'Front Page Hero Widgets', 'themisdb' ),
        'id'            => 'frontpage-hero',
        'description'   => esc_html__( 'Widgets below the hero section on the static front page.', 'themisdb' ),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ) );

    register_sidebar( array(
        'name'          => esc_html__( 'Front Page Content Widgets', 'themisdb' ),
        'id'            => 'frontpage-content',
        'description'   => esc_html__( 'Widgets between intro content and latest posts on the static front page.', 'themisdb' ),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ) );

    // Footer widgets
    for ( $i = 1; $i <= 3; $i++ ) {
        register_sidebar( array(
            'name'          => sprintf( esc_html__( 'Footer Widget %d', 'themisdb' ), $i ),
            'id'            => 'footer-' . $i,
            'description'   => sprintf( esc_html__( 'Add widgets here to appear in footer column %d.', 'themisdb' ), $i ),
            'before_widget' => '<div id="%1$s" class="footer-widget %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<h3 class="widget-title">',
            'after_title'   => '</h3>',
        ) );
    }
}
add_action( 'widgets_init', 'themisdb_widgets_init' );

/**
 * Load widget classes after theme setup to avoid early i18n notices.
 */
function themisdb_load_widgets_file() {
    require_once get_template_directory() . '/inc/widgets.php';
}
add_action( 'after_setup_theme', 'themisdb_load_widgets_file', 20 );

/**
 * Primary navigation fallback.
 *
 * Renders a list of top-level pages when no menu is assigned to the
 * 'primary' location, so the header always contains navigation.
 *
 * @since 1.0.0
 * @return void
 */
function themisdb_nav_fallback() {
    echo '<ul id="' . esc_attr( 'primary-menu' ) . '" class="' . esc_attr( 'menu' ) . '">';
    wp_list_pages( array(
        'title_li'    => '',
        'depth'       => 1,
        'sort_column' => 'menu_order, post_title',
    ) );
    echo '</ul>';
}

/**
 * Enqueue scripts and styles
 */
function themisdb_scripts() {
    // Main stylesheet
    wp_enqueue_style( 'themisdb-style', get_stylesheet_uri(), array(), '1.0.0' );

    // Widgets stylesheet
    wp_enqueue_style( 'themisdb-widgets', get_template_directory_uri() . '/css/widgets.css', array( 'themisdb-style' ), '1.0.0' );

    // Theme JavaScript
    wp_enqueue_script( 'themisdb-navigation', get_template_directory_uri() . '/js/navigation.js', array(), '1.0.0', true );

    // Modern enhancements (animations, lazy loading, etc.)
    wp_enqueue_script( 'themisdb-enhancements', get_template_directory_uri() . '/js/enhancements.js', array(), '1.0.0', true );

    // Featured Slider
    wp_enqueue_script( 'themisdb-slider', get_template_directory_uri() . '/js/slider.js', array(), '1.0.0', true );

    // Carousel widgets (testimonials, images, timeline)
    wp_enqueue_script( 'themisdb-carousel', get_template_directory_uri() . '/js/carousel.js', array(), '1.0.0', true );

    // Comment reply script
    if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
        wp_enqueue_script( 'comment-reply' );
    }
}
add_action( 'wp_enqueue_scripts', 'themisdb_scripts' );

/**
 * Add body classes
 */
function themisdb_body_classes( $classes ) {
    // Add class if sidebar is active (not on front page – it uses full-width layout)
    if ( is_active_sidebar( 'sidebar-1' ) && ! is_page_template( 'template-full-width.php' ) && ! is_front_page() ) {
        $classes[] = 'has-sidebar';
    }

    // Add class for single posts
    if ( is_singular() ) {
        $classes[] = 'singular';
    }

    return $classes;
}
add_filter( 'body_class', 'themisdb_body_classes' );

/**
 * Remove search widgets from primary sidebar globally.
 *
 * The header already has a search control, so search widgets are removed
 * from `sidebar-1` before rendering.
 *
 * @param array<string,array<int,string>> $sidebars_widgets Sidebar-to-widget map.
 * @return array<string,array<int,string>>
 */
function themisdb_remove_search_widgets_from_primary_sidebar( $sidebars_widgets ) {
    if ( is_admin() && ! wp_doing_ajax() ) {
        return $sidebars_widgets;
    }

    if ( empty( $sidebars_widgets['sidebar-1'] ) || ! is_array( $sidebars_widgets['sidebar-1'] ) ) {
        return $sidebars_widgets;
    }

    $filtered_widgets = array();
    $block_instances  = get_option( 'widget_block', array() );

    foreach ( $sidebars_widgets['sidebar-1'] as $widget_id ) {
        $is_search_widget = false;

        if ( 0 === strpos( $widget_id, 'search-' ) || 'search' === $widget_id ) {
            $is_search_widget = true;
        }

        if ( ! $is_search_widget && 0 === strpos( $widget_id, 'block-' ) ) {
            $widget_number = (int) str_replace( 'block-', '', $widget_id );
            if ( isset( $block_instances[ $widget_number ]['content'] ) ) {
                $block_content = (string) $block_instances[ $widget_number ]['content'];
                if ( false !== strpos( $block_content, 'wp:search' ) ) {
                    $is_search_widget = true;
                }
            }
        }

        if ( ! $is_search_widget ) {
            $filtered_widgets[] = $widget_id;
        }
    }

    $sidebars_widgets['sidebar-1'] = $filtered_widgets;
    return $sidebars_widgets;
}
add_filter( 'sidebars_widgets', 'themisdb_remove_search_widgets_from_primary_sidebar', 20 );

/**
 * Custom excerpt length
 */
function themisdb_excerpt_length( $length ) {
    return 40;
}
add_filter( 'excerpt_length', 'themisdb_excerpt_length', 999 );

/**
 * Custom excerpt more
 */
function themisdb_excerpt_more( $more ) {
    return '&hellip;';
}
add_filter( 'excerpt_more', 'themisdb_excerpt_more' );

/**
 * Display post meta information
 */
function themisdb_posted_on() {
    $time_string = '<time class="entry-date published updated" datetime="%1$s">%2$s</time>';
    if ( get_the_time( 'U' ) !== get_the_modified_time( 'U' ) ) {
        $time_string = '<time class="entry-date published" datetime="%1$s">%2$s</time><time class="updated" datetime="%3$s">%4$s</time>';
    }

    $time_string = sprintf( $time_string,
        esc_attr( get_the_date( DATE_W3C ) ),
        esc_html( get_the_date() ),
        esc_attr( get_the_modified_date( DATE_W3C ) ),
        esc_html( get_the_modified_date() )
    );

    echo '<span class="posted-on">';
    echo '📅 ';
    echo $time_string;
    echo '</span>';
}

/**
 * Display post author
 */
function themisdb_posted_by() {
    echo '<span class="byline">';
    echo '👤 ';
    printf(
        '<a href="%s">%s</a>',
        esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ),
        esc_html( get_the_author() )
    );
    echo '</span>';
}

/**
 * Display category links
 */
function themisdb_categories() {
    $categories_list = get_the_category_list( ', ' );
    if ( $categories_list ) {
        echo '<span class="cat-links">';
        echo '📁 ';
        echo $categories_list;
        echo '</span>';
    }
}

/**
 * Display tag links
 */
function themisdb_tags() {
    $tags_list = get_the_tag_list( '', ', ' );
    if ( $tags_list ) {
        echo '<span class="tags-links">';
        echo '🏷️ ';
        echo $tags_list;
        echo '</span>';
    }
}

/**
 * Display comments link
 */
function themisdb_comments_link() {
    if ( ! is_single() && ! post_password_required() && ( comments_open() || get_comments_number() ) ) {
        echo '<span class="comments-link">';
        echo '💬 ';
        comments_popup_link(
            sprintf(
                wp_kses(
                    __( 'Leave a Comment<span class="screen-reader-text"> on %s</span>', 'themisdb' ),
                    array(
                        'span' => array(
                            'class' => array(),
                        ),
                    )
                ),
                wp_kses_post( get_the_title() )
            )
        );
        echo '</span>';
    }
}

/**
 * Customize the read more link
 */
function themisdb_read_more_link() {
    return sprintf(
        '<div class="more-link-wrapper"><a href="%s" class="more-link">%s</a></div>',
        esc_url( get_permalink() ),
        esc_html__( 'Read More', 'themisdb' )
    );
}
add_filter( 'the_content_more_link', 'themisdb_read_more_link' );

/**
 * Sanitize checkbox values from the Customizer.
 *
 * @param mixed $value Raw checkbox value.
 * @return bool
 */
function themisdb_sanitize_checkbox( $value ) {
    return (bool) $value;
}

/**
 * Sanitize front page latest articles count.
 *
 * @param mixed $value Raw Customizer value.
 * @return int
 */
function themisdb_sanitize_latest_articles_count( $value ) {
    $value = absint( $value );

    if ( $value < 3 ) {
        return 3;
    }

    if ( $value > 8 ) {
        return 8;
    }

    return $value;
}

/**
 * Sanitize lead excerpt word count.
 *
 * @param mixed $value Raw Customizer value.
 * @return int
 */
function themisdb_sanitize_lead_excerpt_words( $value ) {
    $value = absint( $value );

    if ( $value < 20 ) {
        return 20;
    }

    if ( $value > 60 ) {
        return 60;
    }

    return $value;
}

/**
 * Sanitize compact excerpt word count.
 *
 * @param mixed $value Raw Customizer value.
 * @return int
 */
function themisdb_sanitize_compact_excerpt_words( $value ) {
    $value = absint( $value );

    if ( $value < 8 ) {
        return 8;
    }

    if ( $value > 30 ) {
        return 30;
    }

    return $value;
}

/**
 * Sanitize front-page slider variant value.
 *
 * @param string $value Raw slider variant.
 * @return string
 */
function themisdb_sanitize_slider_variant( $value ) {
    $allowed = array( 'standard', 'magazine', 'editorial' );
    $value   = sanitize_key( (string) $value );

    if ( in_array( $value, $allowed, true ) ) {
        return $value;
    }

    return 'standard';
}

/**
 * Sanitize footer tone value.
 *
 * @param string $value Raw footer tone.
 * @return string
 */
function themisdb_sanitize_footer_tone( $value ) {
    $allowed = array( 'marketing', 'technical' );
    $value   = sanitize_key( (string) $value );

    if ( in_array( $value, $allowed, true ) ) {
        return $value;
    }

    return 'marketing';
}

/**
 * Customizer control for resetting front page options.
 */
if ( class_exists( 'WP_Customize_Control' ) && ! class_exists( 'ThemisDB_Reset_Customize_Control' ) ) {
    class ThemisDB_Reset_Customize_Control extends WP_Customize_Control {
        public $type = 'themisdb_reset_control';

        public function render_content() {
            ?>
            <div class="themisdb-reset-control-wrap">
                <span class="customize-control-title"><?php esc_html_e( 'Reset Front Page Options', 'themisdb' ); ?></span>
                <p><?php esc_html_e( 'Set all Front Page customization fields in this section back to their default values.', 'themisdb' ); ?></p>
                <button type="button" class="button button-secondary themisdb-reset-homepage-settings">
                    <?php esc_html_e( 'Reset To Defaults', 'themisdb' ); ?>
                </button>
            </div>
            <?php
        }
    }
}

/**
 * Customizer additions
 */
function themisdb_customize_register( $wp_customize ) {
    // Theme Colors Section
    $wp_customize->add_section( 'themisdb_colors', array(
        'title'    => esc_html__( 'Theme Colors', 'themisdb' ),
        'priority' => 30,
    ) );

    // Primary Color
    $wp_customize->add_setting( 'themisdb_primary_color', array(
        'default'           => '#1a2e52',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );

    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'themisdb_primary_color', array(
        'label'    => esc_html__( 'Primary Color (Navy)', 'themisdb' ),
        'section'  => 'themisdb_colors',
        'settings' => 'themisdb_primary_color',
    ) ) );

    // Secondary Color
    $wp_customize->add_setting( 'themisdb_secondary_color', array(
        'default'           => '#1e6fba',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );

    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'themisdb_secondary_color', array(
        'label'    => esc_html__( 'Secondary Color (Azure)', 'themisdb' ),
        'section'  => 'themisdb_colors',
        'settings' => 'themisdb_secondary_color',
    ) ) );

    // Accent Color
    $wp_customize->add_setting( 'themisdb_accent_color', array(
        'default'           => '#1ab5c8',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );

    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'themisdb_accent_color', array(
        'label'    => esc_html__( 'Accent Color (Teal-Cyan)', 'themisdb' ),
        'section'  => 'themisdb_colors',
        'settings' => 'themisdb_accent_color',
    ) ) );

    // Front page section options
    $wp_customize->add_section( 'themisdb_front_page', array(
        'title'       => esc_html__( 'Front Page', 'themisdb' ),
        'priority'    => 31,
        'description' => esc_html__( 'Control the homepage latest articles block.', 'themisdb' ),
    ) );

    $wp_customize->add_setting( 'themisdb_home_show_latest_articles', array(
        'default'           => true,
        'transport'         => 'postMessage',
        'sanitize_callback' => 'themisdb_sanitize_checkbox',
    ) );

    $wp_customize->add_control( 'themisdb_home_show_latest_articles', array(
        'label'   => esc_html__( 'Show latest articles section', 'themisdb' ),
        'section' => 'themisdb_front_page',
        'type'    => 'checkbox',
    ) );

    $wp_customize->add_setting( 'themisdb_home_latest_articles_count', array(
        'default'           => 4,
        'transport'         => 'postMessage',
        'sanitize_callback' => 'themisdb_sanitize_latest_articles_count',
    ) );

    $wp_customize->add_control( 'themisdb_home_latest_articles_count', array(
        'label'       => esc_html__( 'Latest articles count', 'themisdb' ),
        'section'     => 'themisdb_front_page',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 3,
            'max'  => 8,
            'step' => 1,
        ),
    ) );

    $wp_customize->add_setting( 'themisdb_home_hero_kicker', array(
        'default'           => esc_html__( 'ThemisDB Startseite', 'themisdb' ),
        'transport'         => 'postMessage',
        'sanitize_callback' => 'sanitize_text_field',
    ) );

    $wp_customize->add_control( 'themisdb_home_hero_kicker', array(
        'label'   => esc_html__( 'Hero kicker text', 'themisdb' ),
        'section' => 'themisdb_front_page',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'themisdb_home_hero_subtitle', array(
        'default'           => '',
        'transport'         => 'postMessage',
        'sanitize_callback' => 'sanitize_text_field',
    ) );

    $wp_customize->add_control( 'themisdb_home_hero_subtitle', array(
        'label'       => esc_html__( 'Hero subtitle override', 'themisdb' ),
        'description' => esc_html__( 'If empty, the page excerpt/content intro is used.', 'themisdb' ),
        'section'     => 'themisdb_front_page',
        'type'        => 'text',
    ) );

    $wp_customize->add_setting( 'themisdb_home_latest_cta_label', array(
        'default'           => esc_html__( 'Neueste Artikel', 'themisdb' ),
        'transport'         => 'postMessage',
        'sanitize_callback' => 'sanitize_text_field',
    ) );

    $wp_customize->add_control( 'themisdb_home_latest_cta_label', array(
        'label'   => esc_html__( 'Latest articles CTA label', 'themisdb' ),
        'section' => 'themisdb_front_page',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'themisdb_home_blog_cta_label', array(
        'default'           => esc_html__( 'Zum Blog', 'themisdb' ),
        'transport'         => 'postMessage',
        'sanitize_callback' => 'sanitize_text_field',
    ) );

    $wp_customize->add_control( 'themisdb_home_blog_cta_label', array(
        'label'   => esc_html__( 'Blog CTA label', 'themisdb' ),
        'section' => 'themisdb_front_page',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'themisdb_home_blog_cta_url', array(
        'default'           => '',
        'transport'         => 'postMessage',
        'sanitize_callback' => 'esc_url_raw',
    ) );

    $wp_customize->add_control( 'themisdb_home_blog_cta_url', array(
        'label'       => esc_html__( 'Blog CTA URL override', 'themisdb' ),
        'description' => esc_html__( 'If empty, the configured Posts page URL is used.', 'themisdb' ),
        'section'     => 'themisdb_front_page',
        'type'        => 'url',
    ) );

    $wp_customize->add_setting( 'themisdb_home_show_stats', array(
        'default'           => true,
        'transport'         => 'postMessage',
        'sanitize_callback' => 'themisdb_sanitize_checkbox',
    ) );

    $wp_customize->add_control( 'themisdb_home_show_stats', array(
        'label'   => esc_html__( 'Show stats section', 'themisdb' ),
        'section' => 'themisdb_front_page',
        'type'    => 'checkbox',
    ) );

    $stats_controls = array(
        'posts'      => array( 'icon' => '📝', 'label' => esc_html__( 'Artikel', 'themisdb' ) ),
        'pages'      => array( 'icon' => '📄', 'label' => esc_html__( 'Seiten', 'themisdb' ) ),
        'categories' => array( 'icon' => '🗂️', 'label' => esc_html__( 'Kategorien', 'themisdb' ) ),
        'tags'       => array( 'icon' => '🏷️', 'label' => esc_html__( 'Tags', 'themisdb' ) ),
    );

    foreach ( $stats_controls as $key => $defaults ) {
        $icon_setting = 'themisdb_home_stat_' . $key . '_icon';
        $label_setting = 'themisdb_home_stat_' . $key . '_label';

        $wp_customize->add_setting( $icon_setting, array(
            'default'           => $defaults['icon'],
            'transport'         => 'postMessage',
            'sanitize_callback' => 'sanitize_text_field',
        ) );

        $wp_customize->add_control( $icon_setting, array(
            'label'   => sprintf( esc_html__( 'Stat icon: %s', 'themisdb' ), ucfirst( $key ) ),
            'section' => 'themisdb_front_page',
            'type'    => 'text',
        ) );

        $wp_customize->add_setting( $label_setting, array(
            'default'           => $defaults['label'],
            'transport'         => 'postMessage',
            'sanitize_callback' => 'sanitize_text_field',
        ) );

        $wp_customize->add_control( $label_setting, array(
            'label'   => sprintf( esc_html__( 'Stat label: %s', 'themisdb' ), ucfirst( $key ) ),
            'section' => 'themisdb_front_page',
            'type'    => 'text',
        ) );
    }

    $wp_customize->add_setting( 'themisdb_home_show_intro_section', array(
        'default'           => true,
        'transport'         => 'postMessage',
        'sanitize_callback' => 'themisdb_sanitize_checkbox',
    ) );

    $wp_customize->add_control( 'themisdb_home_show_intro_section', array(
        'label'   => esc_html__( 'Show intro section', 'themisdb' ),
        'section' => 'themisdb_front_page',
        'type'    => 'checkbox',
    ) );

    $wp_customize->add_setting( 'themisdb_home_intro_eyebrow', array(
        'default'           => esc_html__( 'Einleitung', 'themisdb' ),
        'transport'         => 'postMessage',
        'sanitize_callback' => 'sanitize_text_field',
    ) );

    $wp_customize->add_control( 'themisdb_home_intro_eyebrow', array(
        'label'   => esc_html__( 'Intro section badge', 'themisdb' ),
        'section' => 'themisdb_front_page',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'themisdb_home_intro_title', array(
        'default'           => esc_html__( 'Was diese Seite bietet', 'themisdb' ),
        'transport'         => 'postMessage',
        'sanitize_callback' => 'sanitize_text_field',
    ) );

    $wp_customize->add_control( 'themisdb_home_intro_title', array(
        'label'   => esc_html__( 'Intro section title', 'themisdb' ),
        'section' => 'themisdb_front_page',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'themisdb_home_latest_eyebrow', array(
        'default'           => esc_html__( 'Magazin', 'themisdb' ),
        'transport'         => 'postMessage',
        'sanitize_callback' => 'sanitize_text_field',
    ) );

    $wp_customize->add_control( 'themisdb_home_latest_eyebrow', array(
        'label'   => esc_html__( 'Latest section badge', 'themisdb' ),
        'section' => 'themisdb_front_page',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'themisdb_home_latest_title', array(
        'default'           => esc_html__( 'Neueste Artikel', 'themisdb' ),
        'transport'         => 'postMessage',
        'sanitize_callback' => 'sanitize_text_field',
    ) );

    $wp_customize->add_control( 'themisdb_home_latest_title', array(
        'label'   => esc_html__( 'Latest section title', 'themisdb' ),
        'section' => 'themisdb_front_page',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'themisdb_home_latest_link_label', array(
        'default'           => esc_html__( 'Alle Artikel ansehen', 'themisdb' ),
        'transport'         => 'postMessage',
        'sanitize_callback' => 'sanitize_text_field',
    ) );

    $wp_customize->add_control( 'themisdb_home_latest_link_label', array(
        'label'   => esc_html__( 'Latest section link label', 'themisdb' ),
        'section' => 'themisdb_front_page',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'themisdb_home_latest_lead_cta_label', array(
        'default'           => esc_html__( 'Artikel lesen', 'themisdb' ),
        'transport'         => 'postMessage',
        'sanitize_callback' => 'sanitize_text_field',
    ) );

    $wp_customize->add_control( 'themisdb_home_latest_lead_cta_label', array(
        'label'   => esc_html__( 'Lead article CTA label', 'themisdb' ),
        'section' => 'themisdb_front_page',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'themisdb_home_latest_lead_excerpt_words', array(
        'default'           => 34,
        'transport'         => 'postMessage',
        'sanitize_callback' => 'themisdb_sanitize_lead_excerpt_words',
    ) );

    $wp_customize->add_control( 'themisdb_home_latest_lead_excerpt_words', array(
        'label'       => esc_html__( 'Lead article excerpt words', 'themisdb' ),
        'description' => esc_html__( 'Range: 20-60', 'themisdb' ),
        'section'     => 'themisdb_front_page',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 20,
            'max'  => 60,
            'step' => 1,
        ),
    ) );

    $wp_customize->add_setting( 'themisdb_home_latest_compact_excerpt_words', array(
        'default'           => 14,
        'transport'         => 'postMessage',
        'sanitize_callback' => 'themisdb_sanitize_compact_excerpt_words',
    ) );

    $wp_customize->add_control( 'themisdb_home_latest_compact_excerpt_words', array(
        'label'       => esc_html__( 'Compact article excerpt words', 'themisdb' ),
        'description' => esc_html__( 'Range: 8-30', 'themisdb' ),
        'section'     => 'themisdb_front_page',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 8,
            'max'  => 30,
            'step' => 1,
        ),
    ) );

    $wp_customize->add_setting( 'themisdb_home_slider_variant', array(
        'default'           => 'standard',
        'transport'         => 'refresh',
        'sanitize_callback' => 'themisdb_sanitize_slider_variant',
    ) );

    $wp_customize->add_control( 'themisdb_home_slider_variant', array(
        'label'       => esc_html__( 'Latest slider style', 'themisdb' ),
        'description' => esc_html__( 'Switch between Standard, Magazine and Editorial Minimal look.', 'themisdb' ),
        'section'     => 'themisdb_front_page',
        'type'        => 'select',
        'choices'     => array(
            'standard' => esc_html__( 'Standard', 'themisdb' ),
            'magazine' => esc_html__( 'Magazine', 'themisdb' ),
            'editorial' => esc_html__( 'Editorial Minimal', 'themisdb' ),
        ),
    ) );

    $wp_customize->add_setting( 'themisdb_footer_tone', array(
        'default'           => 'marketing',
        'transport'         => 'refresh',
        'sanitize_callback' => 'themisdb_sanitize_footer_tone',
    ) );

    $wp_customize->add_control( 'themisdb_footer_tone', array(
        'label'       => esc_html__( 'Footer text tone', 'themisdb' ),
        'description' => esc_html__( 'Choose whether footer copy sounds technical or marketing-focused.', 'themisdb' ),
        'section'     => 'themisdb_front_page',
        'type'        => 'select',
        'choices'     => array(
            'marketing' => esc_html__( 'Marketing', 'themisdb' ),
            'technical' => esc_html__( 'Technical', 'themisdb' ),
        ),
    ) );

    $wp_customize->add_setting( 'themisdb_home_reset_defaults', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ) );

    $wp_customize->add_control( new ThemisDB_Reset_Customize_Control( $wp_customize, 'themisdb_home_reset_defaults', array(
        'section'  => 'themisdb_front_page',
        'settings' => 'themisdb_home_reset_defaults',
        'priority' => 999,
    ) ) );
}
add_action( 'customize_register', 'themisdb_customize_register' );

/**
 * Enqueue Customizer preview logic for front-page blocks.
 *
 * @return void
 */
function themisdb_customize_preview_js() {
    wp_enqueue_script(
        'themisdb-customizer-preview',
        get_template_directory_uri() . '/js/customizer-preview.js',
        array( 'customize-preview' ),
        '1.0.0',
        true
    );
}
add_action( 'customize_preview_init', 'themisdb_customize_preview_js' );

/**
 * Enqueue Customizer controls script for reset button behavior.
 *
 * @return void
 */
function themisdb_customize_controls_js() {
    wp_enqueue_script(
        'themisdb-customizer-controls',
        get_template_directory_uri() . '/js/customizer-controls.js',
        array( 'customize-controls' ),
        '1.0.0',
        true
    );

    wp_localize_script(
        'themisdb-customizer-controls',
        'themisdbCustomizerDefaults',
        array(
            'confirmMessage' => esc_html__( 'Alle Front-Page-Einstellungen auf Standardwerte zuruecksetzen?', 'themisdb' ),
            'settings'       => array(
                'themisdb_home_show_latest_articles'         => true,
                'themisdb_home_latest_articles_count'        => 4,
                'themisdb_home_hero_kicker'                  => esc_html__( 'ThemisDB Startseite', 'themisdb' ),
                'themisdb_home_hero_subtitle'                => '',
                'themisdb_home_latest_cta_label'             => esc_html__( 'Neueste Artikel', 'themisdb' ),
                'themisdb_home_blog_cta_label'               => esc_html__( 'Zum Blog', 'themisdb' ),
                'themisdb_home_blog_cta_url'                 => '',
                'themisdb_home_show_stats'                   => true,
                'themisdb_home_stat_posts_icon'              => '📝',
                'themisdb_home_stat_posts_label'             => esc_html__( 'Artikel', 'themisdb' ),
                'themisdb_home_stat_pages_icon'              => '📄',
                'themisdb_home_stat_pages_label'             => esc_html__( 'Seiten', 'themisdb' ),
                'themisdb_home_stat_categories_icon'         => '🗂️',
                'themisdb_home_stat_categories_label'        => esc_html__( 'Kategorien', 'themisdb' ),
                'themisdb_home_stat_tags_icon'               => '🏷️',
                'themisdb_home_stat_tags_label'              => esc_html__( 'Tags', 'themisdb' ),
                'themisdb_home_show_intro_section'           => true,
                'themisdb_home_intro_eyebrow'                => esc_html__( 'Einleitung', 'themisdb' ),
                'themisdb_home_intro_title'                  => esc_html__( 'Was diese Seite bietet', 'themisdb' ),
                'themisdb_home_latest_eyebrow'               => esc_html__( 'Magazin', 'themisdb' ),
                'themisdb_home_latest_title'                 => esc_html__( 'Neueste Artikel', 'themisdb' ),
                'themisdb_home_latest_link_label'            => esc_html__( 'Alle Artikel ansehen', 'themisdb' ),
                'themisdb_home_latest_lead_cta_label'        => esc_html__( 'Artikel lesen', 'themisdb' ),
                'themisdb_home_latest_lead_excerpt_words'    => 34,
                'themisdb_home_latest_compact_excerpt_words' => 14,
                'themisdb_home_slider_variant'               => 'standard',
                'themisdb_footer_tone'                       => 'marketing',
            ),
        )
    );
}
add_action( 'customize_controls_enqueue_scripts', 'themisdb_customize_controls_js' );

/**
 * Output custom colors CSS
 */
function themisdb_custom_colors_css() {
    $primary_color   = get_theme_mod( 'themisdb_primary_color', '#1a2e52' );
    $secondary_color = get_theme_mod( 'themisdb_secondary_color', '#1e6fba' );
    $accent_color    = get_theme_mod( 'themisdb_accent_color', '#1ab5c8' );

    $css = "
        :root {
            --primary-color: {$primary_color};
            --secondary-color: {$secondary_color};
            --accent-purple: {$accent_color};
        }
    ";

    wp_add_inline_style( 'themisdb-style', $css );
}
add_action( 'wp_enqueue_scripts', 'themisdb_custom_colors_css' );

/**
 * Breadcrumb Navigation
 * Display hierarchical navigation path
 */
function themisdb_breadcrumbs() {
    // Don't display on homepage
    if ( is_front_page() ) {
        return;
    }

    $separator = ' 🔸 ';
    $home_title = '🏠 ' . esc_html__( 'Home', 'themisdb' );

    echo '<nav class="breadcrumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'themisdb' ) . '">';
    echo '<ol class="breadcrumb-list">';

    // Home link
    echo '<li class="breadcrumb-item"><a href="' . esc_url( home_url( '/' ) ) . '">' . $home_title . '</a></li>';

    if ( is_category() || is_single() ) {
        $categories = get_the_category();
        if ( ! empty( $categories ) ) {
            $category = $categories[0];
            if ( $category->parent ) {
                $parent_cats = array();
                $current_cat = $category;
                while ( $current_cat->parent ) {
                    $current_cat = get_category( $current_cat->parent );
                    $parent_cats[] = $current_cat;
                }
                $parent_cats = array_reverse( $parent_cats );
                foreach ( $parent_cats as $parent_cat ) {
                    echo '<li class="breadcrumb-item">' . $separator . '<a href="' . esc_url( get_category_link( $parent_cat->term_id ) ) . '">📁 ' . esc_html( $parent_cat->name ) . '</a></li>';
                }
            }
            echo '<li class="breadcrumb-item">' . $separator . '<a href="' . esc_url( get_category_link( $category->term_id ) ) . '">📁 ' . esc_html( $category->name ) . '</a></li>';
        }

        if ( is_single() ) {
            echo '<li class="breadcrumb-item active" aria-current="page">' . $separator . '📝 ' . esc_html( get_the_title() ) . '</li>';
        }
    } elseif ( is_page() ) {
        if ( $post = get_post() ) {
            if ( $post->post_parent ) {
                $parent_id  = $post->post_parent;
                $breadcrumbs = array();
                while ( $parent_id ) {
                    $page = get_post( $parent_id );
                    $breadcrumbs[] = '<li class="breadcrumb-item">' . $separator . '<a href="' . esc_url( get_permalink( $page->ID ) ) . '">📄 ' . esc_html( get_the_title( $page->ID ) ) . '</a></li>';
                    $parent_id = $page->post_parent;
                }
                $breadcrumbs = array_reverse( $breadcrumbs );
                foreach ( $breadcrumbs as $crumb ) {
                    echo $crumb;
                }
            }
            echo '<li class="breadcrumb-item active" aria-current="page">' . $separator . '📄 ' . esc_html( get_the_title() ) . '</li>';
        }
    } elseif ( is_tag() ) {
        echo '<li class="breadcrumb-item active" aria-current="page">' . $separator . '🏷️ ' . esc_html( single_tag_title( '', false ) ) . '</li>';
    } elseif ( is_author() ) {
        echo '<li class="breadcrumb-item active" aria-current="page">' . $separator . '👤 ' . esc_html( get_the_author() ) . '</li>';
    } elseif ( is_day() ) {
        echo '<li class="breadcrumb-item">' . $separator . '<a href="' . esc_url( get_year_link( get_the_time( 'Y' ) ) ) . '">📅 ' . esc_html( get_the_time( 'Y' ) ) . '</a></li>';
        echo '<li class="breadcrumb-item">' . $separator . '<a href="' . esc_url( get_month_link( get_the_time( 'Y' ), get_the_time( 'm' ) ) ) . '">' . esc_html( get_the_time( 'F' ) ) . '</a></li>';
        echo '<li class="breadcrumb-item active" aria-current="page">' . $separator . esc_html( get_the_time( 'd' ) ) . '</li>';
    } elseif ( is_month() ) {
        echo '<li class="breadcrumb-item">' . $separator . '<a href="' . esc_url( get_year_link( get_the_time( 'Y' ) ) ) . '">📅 ' . esc_html( get_the_time( 'Y' ) ) . '</a></li>';
        echo '<li class="breadcrumb-item active" aria-current="page">' . $separator . esc_html( get_the_time( 'F' ) ) . '</li>';
    } elseif ( is_year() ) {
        echo '<li class="breadcrumb-item active" aria-current="page">' . $separator . '📅 ' . esc_html( get_the_time( 'Y' ) ) . '</li>';
    } elseif ( is_search() ) {
        echo '<li class="breadcrumb-item active" aria-current="page">' . $separator . '🔍 ' . esc_html__( 'Search Results', 'themisdb' ) . '</li>';
    } elseif ( is_404() ) {
        echo '<li class="breadcrumb-item active" aria-current="page">' . $separator . '❌ ' . esc_html__( '404 Error', 'themisdb' ) . '</li>';
    }

    echo '</ol>';
    echo '</nav>';
}

/**
 * Social Share Buttons
 * Display social sharing options for posts
 */
function themisdb_social_share_buttons() {
    if ( ! is_single() ) {
        return;
    }

    $post_url = urlencode( get_permalink() );
    $post_title = urlencode( get_the_title() );
    
    ?>
    <div class="social-share">
        <h3 class="social-share-title">🔗 <?php esc_html_e( 'Share this post:', 'themisdb' ); ?></h3>
        <div class="social-share-buttons">
            <a href="https://twitter.com/intent/tweet?url=<?php echo $post_url; ?>&text=<?php echo $post_title; ?>" 
               target="_blank" 
               rel="noopener noreferrer" 
               class="share-button share-twitter"
               aria-label="<?php esc_attr_e( 'Share on Twitter', 'themisdb' ); ?>">
                🐦 Twitter
            </a>
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $post_url; ?>" 
               target="_blank" 
               rel="noopener noreferrer" 
               class="share-button share-facebook"
               aria-label="<?php esc_attr_e( 'Share on Facebook', 'themisdb' ); ?>">
                📘 Facebook
            </a>
            <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo $post_url; ?>&title=<?php echo $post_title; ?>" 
               target="_blank" 
               rel="noopener noreferrer" 
               class="share-button share-linkedin"
               aria-label="<?php esc_attr_e( 'Share on LinkedIn', 'themisdb' ); ?>">
                💼 LinkedIn
            </a>
            <a href="mailto:?subject=<?php echo $post_title; ?>&body=<?php echo $post_url; ?>" 
               class="share-button share-email"
               aria-label="<?php esc_attr_e( 'Share via Email', 'themisdb' ); ?>">
                ✉️ Email
            </a>
            <button class="share-button share-copy" 
                    data-url="<?php echo esc_url( get_permalink() ); ?>"
                    aria-label="<?php esc_attr_e( 'Copy link', 'themisdb' ); ?>">
                📋 <?php esc_html_e( 'Copy Link', 'themisdb' ); ?>
            </button>
        </div>
    </div>
    <?php
}

/**
 * Estimated Reading Time
 * Calculate and display reading time for posts
 */
function themisdb_reading_time() {
    $content = get_post_field( 'post_content', get_the_ID() );
    $word_count = str_word_count( strip_tags( $content ) );
    $reading_time = ceil( $word_count / 200 ); // Average reading speed: 200 words per minute

    if ( $reading_time > 0 ) {
        printf(
            '<span class="reading-time">⏱️ %s</span>',
            sprintf(
                _n( '%d minute read', '%d minutes read', $reading_time, 'themisdb' ),
                $reading_time
            )
        );
    }
}

/**
 * Hamburger Menu Fallback
 * Display default items when no menu is assigned
 */
function themisdb_hamburger_menu_fallback() {
    ?>
    <ul id="hamburger-menu" class="menu">
        <li><a href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>">⚙️ <?php esc_html_e( 'Settings', 'themisdb' ); ?></a></li>
        <li><a href="<?php echo esc_url( home_url( '/about' ) ); ?>">ℹ️ <?php esc_html_e( 'About', 'themisdb' ); ?></a></li>
        <li><a href="<?php echo esc_url( home_url( '/contact' ) ); ?>">📧 <?php esc_html_e( 'Contact', 'themisdb' ); ?></a></li>
    </ul>
    <?php
}
