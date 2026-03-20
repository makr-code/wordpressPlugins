<?php
/**
 * ThemisDB Theme v3 – Functions
 *
 * WordPress Block Theme (Full Site Editing) – complete theme setup,
 * pattern registration, block styles, editor support, jQuery animations
 * and plugin compatibility layer for all ThemisDB plugins.
 *
 * Inspired by postgresql.org × Azure Fluent Design.
 *
 * @package ThemisDB_V3
 * @since   3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'THEMISDB_V3_VERSION', '3.0.0' );
define( 'THEMISDB_V3_DIR',     get_template_directory() );
define( 'THEMISDB_V3_URI',     get_template_directory_uri() );

/* ================================================================
   1. THEME SETUP
   ================================================================ */

add_action( 'after_setup_theme', 'themisdb_v3_setup' );

function themisdb_v3_setup() {
	load_theme_textdomain( 'themisdb-v3', THEMISDB_V3_DIR . '/languages' );

	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'html5', array(
		'search-form', 'comment-form', 'comment-list',
		'gallery', 'caption', 'style', 'script',
	) );

	// Block theme: Full Site Editing
	add_theme_support( 'block-templates' );

	// Custom logo
	add_theme_support( 'custom-logo', array(
		'height'      => 48,
		'width'       => 200,
		'flex-height' => true,
		'flex-width'  => true,
		'header-text' => array( 'site-title' ),
	) );

	// Editor styles (applied inside Gutenberg)
	add_theme_support( 'editor-styles' );
	add_editor_style( array(
		'style.css',
		'assets/css/editor.css',
	) );

	// Custom image sizes
	add_image_size( 'themisdb-v3-hero',      1920, 960,  true );
	add_image_size( 'themisdb-v3-featured',  1200, 675,  true );
	add_image_size( 'themisdb-v3-card',      640,  400,  true );
	add_image_size( 'themisdb-v3-thumbnail', 400,  300,  true );
	add_image_size( 'themisdb-v3-avatar',    80,   80,   true );

	// Navigation menus
	register_nav_menus( array(
		'primary'      => __( 'Primary Menu',       'themisdb-v3' ),
		'docs'         => __( 'Documentation Menu', 'themisdb-v3' ),
		'footer-col-1' => __( 'Footer: Products',   'themisdb-v3' ),
		'footer-col-2' => __( 'Footer: Resources',  'themisdb-v3' ),
		'footer-col-3' => __( 'Footer: Community',  'themisdb-v3' ),
		'footer-col-4' => __( 'Footer: Legal',      'themisdb-v3' ),
	) );
}

/**
 * Flush rewrite rules when switching to this theme so pretty permalinks
 * are available immediately.
 */
function themisdb_v3_flush_rewrite_on_switch() {
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'themisdb_v3_flush_rewrite_on_switch' );

/* ================================================================
   2. ENQUEUE ASSETS
   ================================================================ */

add_action( 'wp_enqueue_scripts', 'themisdb_v3_enqueue' );

function themisdb_v3_enqueue() {
	// Main theme stylesheet
	wp_enqueue_style(
		'themisdb-v3-style',
		get_stylesheet_uri(),
		array(),
		THEMISDB_V3_VERSION
	);

	// Navigation / mobile menu JS – vanilla JS, no jQuery dependency
	wp_enqueue_script(
		'themisdb-v3-navigation',
		THEMISDB_V3_URI . '/assets/js/navigation.js',
		array(),
		THEMISDB_V3_VERSION,
		true
	);

	// Animations JS – requires jQuery (wp_enqueue_script handles jQuery loading)
	wp_enqueue_script(
		'themisdb-v3-animations',
		THEMISDB_V3_URI . '/assets/js/animations.js',
		array( 'jquery' ),
		THEMISDB_V3_VERSION,
		true
	);

	// Pass runtime data to both scripts
	wp_localize_script( 'themisdb-v3-navigation', 'themisdbV3', array(
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'themisdb_v3_nonce' ),
		'homeUrl' => esc_url( home_url( '/' ) ),
		'version' => THEMISDB_V3_VERSION,
	) );
}

/**
 * Convert root-relative links from block HTML to full site URLs.
 *
 * This keeps links stable when WordPress is installed in a subdirectory.
 */
function themisdb_v3_normalize_block_root_relative_urls( $content ) {
	if ( ! is_string( $content ) || '' === $content ) {
		return $content;
	}

	if (
		false === strpos( $content, 'href="/' ) &&
		false === strpos( $content, "href='/'" ) &&
		false === strpos( $content, 'src="/' ) &&
		false === strpos( $content, "src='/'" )
	) {
		return $content;
	}

	return (string) preg_replace_callback(
		'/(href|src)=(["\'])\/(?!\/)([^"\']*)\2/i',
		static function( $matches ) {
			$attribute = strtolower( $matches[1] );
			$quote     = $matches[2];
			$path      = ltrim( (string) $matches[3], '/' );

			return $attribute . '=' . $quote . esc_url( home_url( '/' . $path ) ) . $quote;
		},
		$content
	);
}

function themisdb_v3_filter_render_block_urls( $block_content ) {
	return themisdb_v3_normalize_block_root_relative_urls( $block_content );
}
add_filter( 'render_block', 'themisdb_v3_filter_render_block_urls', 20, 1 );

/* ================================================================
   3. BLOCK PATTERNS
   ================================================================ */

add_action( 'init', 'themisdb_v3_register_pattern_categories' );

function themisdb_v3_register_pattern_categories() {
	register_block_pattern_category( 'themisdb-v3', array(
		'label' => __( 'ThemisDB v3', 'themisdb-v3' ),
	) );
	register_block_pattern_category( 'themisdb-v3-landing', array(
		'label' => __( 'ThemisDB v3 – Landing Page', 'themisdb-v3' ),
	) );
	register_block_pattern_category( 'themisdb-v3-docs', array(
		'label' => __( 'ThemisDB v3 – Documentation', 'themisdb-v3' ),
	) );
}

// Patterns are auto-discovered from /patterns/ directory (WP 6.0+).

/* ================================================================
   4. BLOCK STYLES (custom variants for core blocks)
   ================================================================ */

add_action( 'init', 'themisdb_v3_register_block_styles' );

function themisdb_v3_register_block_styles() {

	// Buttons
	register_block_style( 'core/button', array(
		'name'  => 'themis-v3-primary',
		'label' => __( 'TV3 Primary (Azure)', 'themisdb-v3' ),
	) );
	register_block_style( 'core/button', array(
		'name'  => 'themis-v3-ghost',
		'label' => __( 'TV3 Ghost (White Outline)', 'themisdb-v3' ),
	) );
	register_block_style( 'core/button', array(
		'name'  => 'themis-v3-outline',
		'label' => __( 'TV3 Outline (Azure Border)', 'themisdb-v3' ),
	) );
	register_block_style( 'core/button', array(
		'name'  => 'themis-v3-dark',
		'label' => __( 'TV3 Dark (Navy)', 'themisdb-v3' ),
	) );

	// Group / section
	register_block_style( 'core/group', array(
		'name'  => 'themis-v3-card',
		'label' => __( 'TV3 Card', 'themisdb-v3' ),
	) );
	register_block_style( 'core/group', array(
		'name'  => 'themis-v3-callout',
		'label' => __( 'TV3 Callout Box', 'themisdb-v3' ),
	) );

	// Code
	register_block_style( 'core/code', array(
		'name'  => 'themis-v3-terminal',
		'label' => __( 'TV3 Terminal / Shell', 'themisdb-v3' ),
	) );

	// Image
	register_block_style( 'core/image', array(
		'name'  => 'themis-v3-rounded',
		'label' => __( 'TV3 Rounded (12px)', 'themisdb-v3' ),
	) );
	register_block_style( 'core/image', array(
		'name'  => 'themis-v3-shadow',
		'label' => __( 'TV3 Shadow', 'themisdb-v3' ),
	) );

	// Separator
	register_block_style( 'core/separator', array(
		'name'  => 'themis-v3-accent',
		'label' => __( 'TV3 Azure Accent Line', 'themisdb-v3' ),
	) );

	// Quote
	register_block_style( 'core/quote', array(
		'name'  => 'themis-v3-featured',
		'label' => __( 'TV3 Featured Quote', 'themisdb-v3' ),
	) );
}

/* ================================================================
   5. PLUGIN COMPATIBILITY
   ================================================================ */

/**
 * Outputs inline CSS to support all ThemisDB plugin CSS classes,
 * ensuring they inherit the v3 design tokens correctly.
 */
add_action( 'wp_head', 'themisdb_v3_plugin_compatibility_styles' );

function themisdb_v3_plugin_compatibility_styles() {
	?>
	<style id="themisdb-v3-plugin-compat">
	/* ThemisDB v3 – Plugin Compatibility Inline CSS */
	.themisdb-benchmark-chart .bar-fill { background: var(--tv3-azure, #0078d4); }
	.themisdb-benchmark-chart .bar-label { color: var(--tv3-text-primary, #12202f); }
	.themisdb-feature-matrix .status-full::before { color: var(--tv3-green, #107c10); }
	.themisdb-feature-matrix .status-limited::before { color: var(--tv3-yellow, #ffb900); }
	.themisdb-feature-matrix .status-no::before { color: var(--tv3-red, #d13438); }
	.themisdb-feature-matrix th { background: var(--tv3-navy, #003366); color: #fff; }
	.themisdb-download-btn,
	.themisdb-docker-btn { background: var(--tv3-azure, #0078d4) !important; border-radius: 8px !important; }
	.themisdb-download-btn:hover,
	.themisdb-docker-btn:hover { background: var(--tv3-azure-dark, #005a9e) !important; }
	.themisdb-query-run-btn { background: var(--tv3-green, #107c10) !important; border-radius: 8px !important; }
	.themisdb-tco-primary-color { color: var(--tv3-azure, #0078d4) !important; }
	.themisdb-timeline-dot { background: var(--tv3-azure, #0078d4) !important; border-color: var(--tv3-azure-pale, #cfe4fc) !important; }
	.themisdb-taxonomy-badge { background: var(--tv3-azure-pale, #cfe4fc); color: var(--tv3-azure, #0078d4); border-radius: 100px; }
	.themisdb-wiki-toc a:hover { color: var(--tv3-azure, #0078d4); }
	.themisdb-graph-node { fill: var(--tv3-azure, #0078d4) !important; }
	.themisdb-graph-link { stroke: var(--tv3-border, #dde3ec) !important; }
	.themisdb-order-submit { background: var(--tv3-azure, #0078d4) !important; border-radius: 8px !important; }
	.themisdb-formula-block { background: #0d1821 !important; color: #cdd9e5 !important; border-radius: 12px !important; }
	.themisdb-release-tag { background: var(--tv3-navy, #003366); color: var(--tv3-cyan, #50e6ff); border-radius: 100px; }
	</style>
	<?php
}

/**
 * Shortcode wrapper for all ThemisDB plugin shortcodes.
 */
add_filter( 'themisdb_shortcode_wrapper_class', 'themisdb_v3_shortcode_wrapper' );
function themisdb_v3_shortcode_wrapper( $class ) {
	return $class . ' themisdb-v3-plugin-output';
}

/**
 * Yoast SEO breadcrumb support.
 */
add_action( 'wp_head', 'themisdb_v3_yoast_breadcrumb_support' );
function themisdb_v3_yoast_breadcrumb_support() {
	if ( function_exists( 'yoast_breadcrumb' ) ) {
		add_theme_support( 'yoast-seo-breadcrumbs' );
	}
}

/**
 * Easy Table of Contents: apply theme-specific container class.
 */
add_filter( 'ez_toc_container_class', function( $class ) {
	return 'tv3-toc ' . $class;
} );

/**
 * Filter Kadence Blocks color palette to match TV3 tokens.
 */
add_filter( 'kadence_blocks_color_palette', 'themisdb_v3_kadence_palette' );
function themisdb_v3_kadence_palette( $palette ) {
	return array(
		array( 'color' => '#003366', 'name' => 'TV3 Navy'        ),
		array( 'color' => '#001a33', 'name' => 'TV3 Navy Dark'    ),
		array( 'color' => '#0078d4', 'name' => 'TV3 Azure'        ),
		array( 'color' => '#005a9e', 'name' => 'TV3 Azure Dark'   ),
		array( 'color' => '#50e6ff', 'name' => 'TV3 Cyan'         ),
		array( 'color' => '#107c10', 'name' => 'TV3 Green'        ),
		array( 'color' => '#ffb900', 'name' => 'TV3 Yellow'       ),
		array( 'color' => '#d13438', 'name' => 'TV3 Red'          ),
		array( 'color' => '#f5f7fa', 'name' => 'TV3 Light BG'     ),
		array( 'color' => '#ffffff', 'name' => 'White'            ),
	);
}

/* ================================================================
   6. WIDGET AREAS (backwards compat)
   ================================================================ */

add_action( 'widgets_init', 'themisdb_v3_widgets' );

function themisdb_v3_widgets() {
	$shared = array(
		'before_widget' => '<div id="%1$s" class="widget themisdb-v3-widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h4 class="widget-title">',
		'after_title'   => '</h4>',
	);

	register_sidebar( array_merge( $shared, array(
		'name'        => __( 'Blog Sidebar', 'themisdb-v3' ),
		'id'          => 'sidebar-blog-v3',
		'description' => __( 'Sidebar for blog / single posts', 'themisdb-v3' ),
	) ) );

	register_sidebar( array_merge( $shared, array(
		'name'        => __( 'Docs Sidebar', 'themisdb-v3' ),
		'id'          => 'sidebar-docs-v3',
		'description' => __( 'Sidebar for documentation pages', 'themisdb-v3' ),
	) ) );

	for ( $i = 1; $i <= 4; $i++ ) {
		register_sidebar( array_merge( $shared, array(
			'name'        => sprintf( __( 'Footer Column %d (v3)', 'themisdb-v3' ), $i ),
			'id'          => 'footer-v3-' . $i,
			'description' => sprintf( __( 'Footer widget area column %d', 'themisdb-v3' ), $i ),
		) ) );
	}
}

/* ================================================================
   7. BODY CLASSES
   ================================================================ */

add_filter( 'body_class', 'themisdb_v3_body_classes' );

function themisdb_v3_body_classes( $classes ) {
	$classes[] = 'themisdb-v3';
	if ( is_singular() )                          $classes[] = 'is-singular';
	if ( is_front_page() )                        $classes[] = 'is-front-page';
	if ( is_page_template( 'page-docs' ) )        $classes[] = 'is-docs-page';
	if ( is_page_template( 'page-full-width' ) )  $classes[] = 'is-full-width';
	return $classes;
}

/* ================================================================
   8. SECURITY / HARDENING
   ================================================================ */

function themisdb_v3_cleanup_head() {
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'rest_output_link_wp_head' );
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
}
add_action( 'after_setup_theme', 'themisdb_v3_cleanup_head', 11 );

add_filter( 'xmlrpc_enabled', '__return_false' );

/* ================================================================
   9. PERFORMANCE
   ================================================================ */

// Defer non-critical JS (navigation is vanilla JS, safe to defer)
add_filter( 'script_loader_tag', 'themisdb_v3_defer_scripts', 10, 3 );
function themisdb_v3_defer_scripts( $tag, $handle, $src ) {
	$defer = array( 'themisdb-v3-navigation' );
	if ( in_array( $handle, $defer, true ) ) {
		return str_replace( ' src', ' defer src', $tag );
	}
	return $tag;
}

/* ================================================================
   10. EXCERPT SETTINGS
   ================================================================ */

add_filter( 'excerpt_length', function() { return 28; } );
add_filter( 'excerpt_more',   function() {
	return ' &hellip; <a class="tv3-read-more" href="' . esc_url( get_permalink() ) . '">'
		. esc_html__( 'Read more', 'themisdb-v3' ) . ' →</a>';
} );

/* ================================================================
   11. CUSTOM BLOCK CATEGORY
   ================================================================ */

add_filter( 'block_categories_all', 'themisdb_v3_block_categories', 10, 2 );

function themisdb_v3_block_categories( $categories, $post ) {
	return array_merge(
		array( array(
			'slug'  => 'themisdb-v3',
			'title' => __( 'ThemisDB v3', 'themisdb-v3' ),
			'icon'  => 'database',
		) ),
		$categories
	);
}

/* ================================================================
   12. RELEVANSSI / SEARCH COMPAT
   ================================================================ */

add_filter( 'relevanssi_search_ok', '__return_true' );
