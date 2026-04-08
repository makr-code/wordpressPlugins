<?php
/**
 * ThemisDB Theme  |  functions.php
 *
 * WordPress Block Theme (Full Site Editing) für das
 * ThemisDB.
 *
 * @package ThemisDB_Theme
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'THEMISDB_THEME_VERSION', '1.0.0' );
define( 'THEMISDB_THEME_DIR',     get_template_directory() );
define( 'THEMISDB_THEME_URI',     get_template_directory_uri() );

/* =====================================================================
   1. THEME SETUP
   ===================================================================== */

add_action( 'after_setup_theme', 'themisdb_setup' );

function themisdb_setup() {
	load_theme_textdomain( 'themisdb-theme', THEMISDB_THEME_DIR . '/languages' );

	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'html5', array(
		'search-form', 'comment-form', 'comment-list',
		'gallery', 'caption', 'style', 'script',
	) );

	// Block theme / Full Site Editing
	add_theme_support( 'block-templates' );

	// Custom logo
	add_theme_support( 'custom-logo', array(
		'height'      => 48,
		'width'       => 200,
		'flex-height' => true,
		'flex-width'  => true,
		'header-text' => array( 'site-title' ),
	) );

	// Editor styles
	add_theme_support( 'editor-styles' );
	add_editor_style( array( 'style.css', 'assets/css/editor.css' ) );

	// Custom image sizes
	add_image_size( 'themisdb-hero',       1920, 960,  true );
	add_image_size( 'themisdb-featured',   1200, 675,  true );
	add_image_size( 'themisdb-card',        640, 400,  true );
	add_image_size( 'themisdb-thumbnail',   400, 300,  true );
	add_image_size( 'themisdb-gallery',     900, 563,  true );  // 16:10 aspect

	// Navigation menus
	register_nav_menus( array(
		'primary'   => __( 'Hauptnavigation',     'themisdb-theme' ),
		'footer-1'  => __( 'Footer: Inhalte',     'themisdb-theme' ),
		'footer-2'  => __( 'Footer: Federführung','themisdb-theme' ),
	) );
}

/**
 * Flush rewrite rules on theme switch.
 */
add_action( 'after_switch_theme', function() {
	flush_rewrite_rules();
} );

/* =====================================================================
   2. ASSETS ENQUEUEING
   ===================================================================== */

add_action( 'wp_enqueue_scripts', 'themisdb_enqueue' );

function themisdb_enqueue() {

	// ── Google Fonts: Plus Jakarta Sans ──────────────────────────────
	wp_enqueue_style(
		'themisdb-google-fonts',
		'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap',
		array(),
		null
	);

	// ── Font Awesome (CDN) ────────────────────────────────────────────
	// NOTE: In Produktion durch Self-hosted oder WP-Plugin ersetzen.
	wp_enqueue_style(
		'themisdb-font-awesome',
		'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
		array(),
		'6.4.0'
	);

	// ── Theme Stylesheet ──────────────────────────────────────────────
	wp_enqueue_style(
		'themisdb-style',
		get_stylesheet_uri(),
		array( 'themisdb-google-fonts' ),
		THEMISDB_THEME_VERSION
	);

	// ── Block CSS (modularisiert) ─────────────────────────────────────
	$block_css = array(
		'themisdb-hero-slider-css'    => 'assets/css/blocks/hero-slider.css',
		'themisdb-section-blocks-css' => 'assets/css/blocks/section-blocks.css',
		'themisdb-gallery-css'        => 'assets/css/blocks/gallery.css',
		'themisdb-faq-css'            => 'assets/css/blocks/faq.css',
		'themisdb-contact-form-css'   => 'assets/css/blocks/contact-form.css',
		'themisdb-motion-css'         => 'assets/css/blocks/motion.css',
	);
	foreach ( $block_css as $handle => $path ) {
		wp_enqueue_style(
			$handle,
			THEMISDB_THEME_URI . '/' . $path,
			array( 'themisdb-style' ),
			THEMISDB_THEME_VERSION
		);
	}

	// ── Mermaid.js (CDN) – für Prozessdiagramme ───────────────────────
	// NOTE: In Produktion lokal bundlen.
	wp_enqueue_script(
		'mermaid',
		'https://cdn.jsdelivr.net/npm/mermaid@11/dist/mermaid.min.js',
		array(),
		'11',
		true
	);

	// ── Navigation JS (Mobile Menu + Smooth Scroll) ────────────────────
	wp_enqueue_script(
		'themisdb-navigation',
		THEMISDB_THEME_URI . '/assets/js/navigation.js',
		array(),
		THEMISDB_THEME_VERSION,
		true
	);

	// ── Hero Slider ────────────────────────────────────────────────────
	wp_enqueue_script(
		'themisdb-hero-slider',
		THEMISDB_THEME_URI . '/assets/js/hero-slider.js',
		array(),
		THEMISDB_THEME_VERSION,
		true
	);

	// ── Gallery + Lightbox ────────────────────────────────────────────
	wp_enqueue_script(
		'themisdb-gallery',
		THEMISDB_THEME_URI . '/assets/js/gallery.js',
		array(),
		THEMISDB_THEME_VERSION,
		true
	);

	// ── FAQ Accordion ─────────────────────────────────────────────────
	wp_enqueue_script(
		'themisdb-faq',
		THEMISDB_THEME_URI . '/assets/js/faq.js',
		array(),
		THEMISDB_THEME_VERSION,
		true
	);

	// ── Shared Motion Controller for dynamic blocks ───────────────────
	wp_enqueue_script(
		'themisdb-motion',
		THEMISDB_THEME_URI . '/assets/js/motion.js',
		array(),
		THEMISDB_THEME_VERSION,
		true
	);

	// ── Legal Accordion (Impressum / Datenschutz) ─────────────────────
	wp_enqueue_script(
		'themisdb-legal',
		THEMISDB_THEME_URI . '/assets/js/legal-accordion.js',
		array(),
		THEMISDB_THEME_VERSION,
		true
	);

	// ── Contact Form ──────────────────────────────────────────────────
	wp_enqueue_script(
		'themisdb-contact-form',
		THEMISDB_THEME_URI . '/assets/js/contact-form.js',
		array(),
		THEMISDB_THEME_VERSION,
		true
	);

	// ── Mermaid Init ──────────────────────────────────────────────────
	wp_enqueue_script(
		'themisdb-mermaid-init',
		THEMISDB_THEME_URI . '/assets/js/mermaid-init.js',
		array( 'mermaid' ),
		THEMISDB_THEME_VERSION,
		true
	);

	// Pass runtime data to scripts
	// ── Coat-of-Arms Loader ───────────────────────────────────────────
	wp_enqueue_script(
		'themisdb-crest-loader',
		THEMISDB_THEME_URI . '/assets/js/crest-loader.js',
		array(),
		THEMISDB_THEME_VERSION,
		true
	);

	// Pass runtime data to scripts
	wp_localize_script( 'themisdb-navigation', 'themisdbTheme', array(
		'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
		'nonce'     => wp_create_nonce( 'themisdb_nonce' ),
		'homeUrl'   => esc_url( home_url( '/' ) ),
		'assetsUrl' => esc_url( THEMISDB_THEME_URI . '/assets/' ),
		'version'   => THEMISDB_THEME_VERSION,
		'i18n'      => array(
			'menuOpen'  => __( 'Menü öffnen',     'themisdb-theme' ),
			'menuClose' => __( 'Menü schließen',  'themisdb-theme' ),
		),
	) );
}

/* =====================================================================
   3. BLOCK PATTERNS
   ===================================================================== */

add_action( 'init', 'themisdb_register_pattern_categories' );

function themisdb_register_pattern_categories() {
	register_block_pattern_category( 'themisdb', array(
		'label' => __( 'ThemisDB', 'themisdb-theme' ),
	) );
	register_block_pattern_category( 'themisdb-sections', array(
		'label' => __( 'ThemisDB – Seitenabschnitte', 'themisdb-theme' ),
	) );
}
// Patterns werden aus /patterns/ automatisch eingelesen (WP 6.0+).

/* =====================================================================
   4. SHORTCODE FALLBACKS
   Stellt sicher, dass fehlende Plugin-Shortcodes die Seite nicht
   beschädigen und liefert nutzbare Theme-Standards ohne Zusatz-Plugins.
   ===================================================================== */

add_action( 'init', 'themisdb_register_shortcode_fallbacks', 30 );

function themisdb_render_plugin_compat_cards( $tag, $atts, $defaults ) {
	$atts = shortcode_atts( $defaults, $atts, $tag );

	$card_args = array(
		'section'       => sanitize_title( $atts['section'] ),
		'per_page'      => max( 1, min( 12, (int) $atts['per_page'] ) ),
		'columns'       => max( 1, min( 4, (int) $atts['columns'] ) ),
		'excerpt_words' => max( 6, min( 60, (int) $atts['excerpt_words'] ) ),
		'show_header'   => '1',
		'show_desc'     => '1',
		'show_image'    => ! empty( $atts['show_image'] ) ? '1' : '0',
		'show_date'     => ! empty( $atts['show_date'] ) ? '1' : '0',
		'show_excerpt'  => ! empty( $atts['show_excerpt'] ) ? '1' : '0',
		'order'         => isset( $atts['order'] ) ? sanitize_key( $atts['order'] ) : 'DESC',
		'orderby'       => isset( $atts['orderby'] ) ? sanitize_key( $atts['orderby'] ) : 'date',
	);

	$card_args = apply_filters( 'themisdb_theme_shortcode_args_' . $tag, $card_args, $atts, $defaults );
	$html      = themisdb_section_cards_shortcode( $card_args );

	return apply_filters( 'themisdb_theme_shortcode_html_' . $tag, $html, $card_args, $atts, $defaults );
}

function themisdb_get_plugin_compat_defaults( $tag ) {
	$map = array(
		'themisdb_latest' => array(
			'section'       => 'aktuelles',
			'per_page'      => 4,
			'columns'       => 4,
			'excerpt_words' => 18,
			'show_image'    => true,
			'show_date'     => true,
			'show_excerpt'  => true,
			'order'         => 'DESC',
			'orderby'       => 'date',
		),
		'themisdb_docker_latest' => array(
			'section'       => 'digital',
			'per_page'      => 4,
			'columns'       => 4,
			'excerpt_words' => 14,
			'show_image'    => false,
			'show_date'     => false,
			'show_excerpt'  => true,
			'order'         => 'DESC',
			'orderby'       => 'date',
		),
		'themisdb_compendium_downloads' => array(
			'section'       => 'digital',
			'per_page'      => 4,
			'columns'       => 4,
			'excerpt_words' => 14,
			'show_image'    => false,
			'show_date'     => false,
			'show_excerpt'  => true,
			'order'         => 'DESC',
			'orderby'       => 'date',
		),
		'themisdb_benchmark_visualizer' => array(
			'section'       => 'evolution',
			'per_page'      => 3,
			'columns'       => 3,
			'excerpt_words' => 20,
			'show_image'    => false,
			'show_date'     => true,
			'show_excerpt'  => true,
			'order'         => 'DESC',
			'orderby'       => 'date',
		),
	);

	$defaults = isset( $map[ $tag ] ) ? $map[ $tag ] : array();

	/**
	 * Allows plugins to feed data/configuration while the theme keeps rendering ownership.
	 */
	return apply_filters( 'themisdb_theme_shortcode_defaults_' . $tag, $defaults );
}

function themisdb_register_shortcode_fallbacks() {
	$fallbacks = array(
		'themisdb_latest'               => themisdb_get_plugin_compat_defaults( 'themisdb_latest' ),
		'themisdb_docker_latest'        => themisdb_get_plugin_compat_defaults( 'themisdb_docker_latest' ),
		'themisdb_compendium_downloads' => themisdb_get_plugin_compat_defaults( 'themisdb_compendium_downloads' ),
		'themisdb_benchmark_visualizer' => themisdb_get_plugin_compat_defaults( 'themisdb_benchmark_visualizer' ),
	);

	foreach ( $fallbacks as $tag => $defaults ) {
		if ( ! shortcode_exists( $tag ) ) {
			add_shortcode( $tag, function( $atts ) use ( $tag, $defaults ) {
				return themisdb_render_plugin_compat_cards( $tag, $atts, $defaults );
			} );
		}
	}
}

function themisdb_latest_shortcode( $atts ) {
	return themisdb_render_plugin_compat_cards( 'themisdb_latest', $atts, themisdb_get_plugin_compat_defaults( 'themisdb_latest' ) );
}

function themisdb_docker_latest_shortcode( $atts ) {
	return themisdb_render_plugin_compat_cards( 'themisdb_docker_latest', $atts, themisdb_get_plugin_compat_defaults( 'themisdb_docker_latest' ) );
}

function themisdb_compendium_downloads_shortcode( $atts ) {
	return themisdb_render_plugin_compat_cards( 'themisdb_compendium_downloads', $atts, themisdb_get_plugin_compat_defaults( 'themisdb_compendium_downloads' ) );
}

function themisdb_benchmark_visualizer_shortcode( $atts ) {
	return themisdb_render_plugin_compat_cards( 'themisdb_benchmark_visualizer', $atts, themisdb_get_plugin_compat_defaults( 'themisdb_benchmark_visualizer' ) );
}

add_action( 'init', 'themisdb_register_download_family_theme_adapters', 45 );
function themisdb_register_download_family_theme_adapters() {
	add_filter( 'themisdb_downloads_shortcode_html', 'themisdb_theme_render_downloads_from_plugin', 10, 3 );
	add_filter( 'themisdb_latest_shortcode_html', 'themisdb_theme_render_latest_from_plugin', 10, 3 );
	add_filter( 'themisdb_verify_shortcode_html', 'themisdb_theme_render_verify_from_plugin', 10, 3 );
	add_filter( 'themisdb_readme_shortcode_html', 'themisdb_theme_render_readme_from_plugin', 10, 3 );
	add_filter( 'themisdb_changelog_shortcode_html', 'themisdb_theme_render_changelog_from_plugin', 10, 3 );
	add_filter( 'themisdb_docker_downloads_shortcode_html', 'themisdb_theme_render_docker_downloads_from_plugin', 10, 3 );
	add_filter( 'themisdb_docker_latest_shortcode_html', 'themisdb_theme_render_docker_latest_from_plugin', 10, 3 );
	add_filter( 'themisdb_compendium_downloads_shortcode_html', 'themisdb_theme_render_compendium_downloads_from_plugin', 10, 3 );
	add_filter( 'themisdb_feature_matrix_shortcode_html', 'themisdb_theme_render_feature_matrix_from_plugin', 10, 3 );
	add_filter( 'themisdb_release_timeline_shortcode_html', 'themisdb_theme_render_release_timeline_from_plugin', 10, 3 );
	add_filter( 'themisdb_front_slider_shortcode_html', 'themisdb_theme_render_front_slider_from_plugin', 10, 3 );
	add_filter( 'themisdb_gallery_shortcode_html', 'themisdb_theme_render_gallery_from_plugin', 10, 3 );
	add_filter( 'themisdb_benchmark_visualizer_shortcode_html', 'themisdb_theme_render_benchmark_visualizer_from_plugin', 10, 3 );
	add_filter( 'themisdb_architecture_shortcode_html', 'themisdb_theme_render_architecture_from_plugin', 10, 3 );
	add_filter( 'themisdb_support_portal_shortcode_html', 'themisdb_theme_render_support_portal_from_plugin', 10, 3 );
	add_filter( 'themisdb_support_login_shortcode_html', 'themisdb_theme_render_support_login_from_plugin', 10, 3 );
	add_filter( 'themisdb_formula_shortcode_html', 'themisdb_theme_render_formula_from_plugin', 10, 3 );
	add_filter( 'themisdb_wiki_shortcode_html', 'themisdb_theme_render_wiki_from_plugin', 10, 3 );
	add_filter( 'themisdb_docs_shortcode_html', 'themisdb_theme_render_docs_from_plugin', 10, 3 );
	add_filter( 'themisdb_wiki_nav_shortcode_html', 'themisdb_theme_render_wiki_nav_from_plugin', 10, 3 );
	add_filter( 'themisdb_tco_calculator_shortcode_html', 'themisdb_theme_render_tco_calculator_from_plugin', 10, 3 );
	add_filter( 'themisdb_tco_workload_shortcode_html', 'themisdb_theme_render_tco_workload_from_plugin', 10, 3 );
	add_filter( 'themisdb_tco_infrastructure_shortcode_html', 'themisdb_theme_render_tco_infrastructure_from_plugin', 10, 3 );
	add_filter( 'themisdb_tco_personnel_shortcode_html', 'themisdb_theme_render_tco_personnel_from_plugin', 10, 3 );
	add_filter( 'themisdb_tco_operations_shortcode_html', 'themisdb_theme_render_tco_operations_from_plugin', 10, 3 );
	add_filter( 'themisdb_tco_ai_shortcode_html', 'themisdb_theme_render_tco_ai_from_plugin', 10, 3 );
	add_filter( 'themisdb_tco_results_shortcode_html', 'themisdb_theme_render_tco_results_from_plugin', 10, 3 );
	add_filter( 'themisdb_test_dashboard_shortcode_html', 'themisdb_theme_render_test_dashboard_from_plugin', 10, 3 );
	add_filter( 'themisdb_persistent_podcast_player_html', 'themisdb_theme_render_persistent_podcast_player_from_plugin', 10, 2 );
	add_filter( 'themisdb_graph_navigation_js_payload', 'themisdb_theme_filter_graph_navigation_payload', 10, 1 );
	add_filter( 'themisdb_taxonomy_shortcode_html', 'themisdb_theme_render_taxonomy_from_plugin', 10, 3 );
	add_filter( 'themisdb_taxonomy_info_shortcode_html', 'themisdb_theme_render_taxonomy_info_from_plugin', 10, 3 );
	add_filter( 'themisdb_term_card_shortcode_html', 'themisdb_theme_render_term_card_from_plugin', 10, 3 );
	add_filter( 'themisdb_query_playground_shortcode_html', 'themisdb_theme_render_query_playground_from_plugin', 10, 3 );
	add_filter( 'themisdb_order_flow_shortcode_html', 'themisdb_theme_render_order_flow_from_plugin', 10, 3 );
	add_filter( 'themisdb_my_orders_shortcode_html', 'themisdb_theme_render_my_orders_from_plugin', 10, 3 );
	add_filter( 'themisdb_my_contracts_shortcode_html', 'themisdb_theme_render_my_contracts_from_plugin', 10, 3 );
	add_filter( 'themisdb_pricing_shortcode_html', 'themisdb_theme_render_pricing_from_plugin', 10, 3 );
	add_filter( 'themisdb_pricing_table_shortcode_html', 'themisdb_theme_render_pricing_table_from_plugin', 10, 3 );
	add_filter( 'themisdb_product_detail_shortcode_html', 'themisdb_theme_render_product_detail_from_plugin', 10, 3 );
	add_filter( 'themisdb_shop_shortcode_html', 'themisdb_theme_render_shop_from_plugin', 10, 3 );
	add_filter( 'themisdb_shopping_cart_shortcode_html', 'themisdb_theme_render_shopping_cart_from_plugin', 10, 3 );
	add_filter( 'themisdb_login_shortcode_html', 'themisdb_theme_render_login_from_plugin', 10, 3 );
	add_filter( 'themisdb_license_upload_shortcode_html', 'themisdb_theme_render_license_upload_from_plugin', 10, 3 );
	add_filter( 'themisdb_license_portal_shortcode_html', 'themisdb_theme_render_license_portal_from_plugin', 10, 3 );
	add_filter( 'themisdb_affiliate_dashboard_shortcode_html', 'themisdb_theme_render_affiliate_dashboard_from_plugin', 10, 3 );
	add_filter( 'themisdb_b2b_portal_shortcode_html', 'themisdb_theme_render_b2b_portal_from_plugin', 10, 3 );
	add_filter( 'themisdb_advanced_reporting_shortcode_html', 'themisdb_theme_render_advanced_reporting_from_plugin', 10, 3 );
}

function themisdb_theme_render_downloads_from_plugin( $html, $releases, $atts ) {
	if ( null !== $html ) {
		return $html;
	}

	if ( ! is_array( $releases ) || empty( $releases ) ) {
		return '<p class="themisdb-downloads-empty">' . esc_html__( 'Keine Downloads verfügbar.', 'themisdb-theme' ) . '</p>';
	}

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-plugin-downloads">';
	echo '<div class="themisdb-section-cards" style="--lis-cols:3;display:grid;gap:1rem;grid-template-columns:repeat(var(--lis-cols),minmax(0,1fr));">';

	foreach ( $releases as $release ) {
		$version      = isset( $release['version'] ) ? (string) $release['version'] : '';
		$published_at = isset( $release['published_at'] ) ? (string) $release['published_at'] : '';
		$assets       = isset( $release['assets'] ) && is_array( $release['assets'] ) ? $release['assets'] : array();

		echo '<article class="themisdb-section-card" style="border:1px solid #e2e8f0;border-radius:1rem;background:#fff;padding:1rem;">';
		echo '<h3 style="margin:0 0 .35rem;color:#0f172a;font-size:1.05rem;">' . esc_html( $version ) . '</h3>';
		if ( ! empty( $published_at ) ) {
			echo '<p style="margin:0 0 .75rem;color:#64748b;font-size:.78rem;">' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $published_at ) ) ) . '</p>';
		}

		if ( empty( $assets ) ) {
			echo '<p style="margin:0;color:#64748b;font-size:.9rem;">' . esc_html__( 'Keine Assets.', 'themisdb-theme' ) . '</p>';
		} else {
			echo '<ul style="margin:0;padding-left:1rem;color:#334155;display:grid;gap:.4rem;">';
			$count = 0;
			foreach ( $assets as $asset ) {
				if ( $count >= 4 ) {
					break;
				}
				$name = isset( $asset['name'] ) ? (string) $asset['name'] : '';
				$url  = isset( $asset['download_url'] ) ? (string) $asset['download_url'] : '';
				if ( '' === $name || '' === $url ) {
					continue;
				}
				echo '<li><a href="' . esc_url( $url ) . '" target="_blank" rel="noopener" style="color:#0c4a6e;text-decoration:none;font-weight:700;">' . esc_html( $name ) . '</a></li>';
				$count++;
			}
			echo '</ul>';
		}

		echo '</article>';
	}

	echo '</div>';
	echo '</section>';

	return ob_get_clean();
}

function themisdb_theme_render_docker_downloads_from_plugin( $html, $tags, $atts ) {
	if ( null !== $html ) {
		return $html;
	}

	if ( ! is_array( $tags ) || empty( $tags ) ) {
		return '<p class="themisdb-downloads-empty">' . esc_html__( 'Keine Docker-Tags verfügbar.', 'themisdb-theme' ) . '</p>';
	}

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-plugin-docker-downloads">';
	echo '<div class="themisdb-section-cards" style="--lis-cols:3;display:grid;gap:1rem;grid-template-columns:repeat(var(--lis-cols),minmax(0,1fr));">';

	foreach ( $tags as $tag ) {
		$name         = isset( $tag['name'] ) ? (string) $tag['name'] : '';
		$pull_command = isset( $tag['pull_command'] ) ? (string) $tag['pull_command'] : '';
		$tag_url      = isset( $tag['tag_url'] ) ? (string) $tag['tag_url'] : '';
		$images       = isset( $tag['images'] ) && is_array( $tag['images'] ) ? $tag['images'] : array();

		echo '<article class="themisdb-section-card" style="border:1px solid #e2e8f0;border-radius:1rem;background:#fff;padding:1rem;">';
		echo '<h3 style="margin:0 0 .5rem;color:#0f172a;font-size:1.05rem;">🐳 ' . esc_html( $name ) . '</h3>';
		if ( ! empty( $pull_command ) ) {
			echo '<code style="display:block;background:#f8fafc;border:1px solid #e2e8f0;border-radius:.6rem;padding:.45rem .6rem;font-size:.78rem;word-break:break-word;">' . esc_html( $pull_command ) . '</code>';
		}

		if ( ! empty( $images ) ) {
			echo '<p style="margin:.6rem 0 .7rem;color:#64748b;font-size:.78rem;">' . esc_html( count( $images ) ) . ' ' . esc_html__( 'Architekturen', 'themisdb-theme' ) . '</p>';
		}

		if ( ! empty( $tag_url ) ) {
			echo '<a href="' . esc_url( $tag_url ) . '" target="_blank" rel="noopener" style="color:#0c4a6e;text-decoration:none;font-weight:700;">' . esc_html__( 'Auf Docker Hub öffnen', 'themisdb-theme' ) . '</a>';
		}
		echo '</article>';
	}

	echo '</div>';
	echo '</section>';

	return ob_get_clean();
}

function themisdb_theme_render_docker_latest_from_plugin( $html, $payload, $atts ) {
	if ( null !== $html ) {
		return $html;
	}

	$tag  = ( isset( $payload['tag'] ) && is_array( $payload['tag'] ) ) ? $payload['tag'] : array();
	$show = isset( $payload['show'] ) ? (string) $payload['show'] : 'name';

	if ( empty( $tag ) ) {
		return '<span class="themisdb-version">' . esc_html__( 'n/a', 'themisdb-theme' ) . '</span>';
	}

	if ( 'pull_command' === $show && ! empty( $tag['pull_command'] ) ) {
		return '<code class="themisdb-docker-pull">' . esc_html( (string) $tag['pull_command'] ) . '</code>';
	}

	$name = isset( $tag['name'] ) ? (string) $tag['name'] : '';
	return '<span class="themisdb-version">' . esc_html( $name ) . '</span>';
}

function themisdb_theme_render_compendium_downloads_from_plugin( $html, $release_data, $atts ) {
	if ( null !== $html ) {
		return $html;
	}

	if ( ! is_array( $release_data ) || empty( $release_data['assets'] ) || ! is_array( $release_data['assets'] ) ) {
		return '<p class="themisdb-downloads-empty">' . esc_html__( 'Keine Kompendium-Downloads verfügbar.', 'themisdb-theme' ) . '</p>';
	}

	$assets = $release_data['assets'];

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-plugin-compendium-downloads">';
	echo '<div class="themisdb-section-cards" style="--lis-cols:3;display:grid;gap:1rem;grid-template-columns:repeat(var(--lis-cols),minmax(0,1fr));">';
	foreach ( $assets as $asset ) {
		$name = isset( $asset['name'] ) ? (string) $asset['name'] : '';
		$url  = isset( $asset['browser_download_url'] ) ? (string) $asset['browser_download_url'] : '';
		$size = isset( $asset['size'] ) ? (int) $asset['size'] : 0;

		if ( '' === $name || '' === $url ) {
			continue;
		}

		echo '<article class="themisdb-section-card" style="border:1px solid #e2e8f0;border-radius:1rem;background:#fff;padding:1rem;">';
		echo '<h3 style="margin:0 0 .5rem;color:#0f172a;font-size:1.05rem;">📚 ' . esc_html( $name ) . '</h3>';
		if ( $size > 0 ) {
			echo '<p style="margin:0 0 .7rem;color:#64748b;font-size:.78rem;">' . esc_html( size_format( $size ) ) . '</p>';
		}
		echo '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener" style="color:#0c4a6e;text-decoration:none;font-weight:700;">' . esc_html__( 'PDF herunterladen', 'themisdb-theme' ) . '</a>';
		echo '</article>';
	}
	echo '</div>';
	echo '</section>';

	return ob_get_clean();
}

function themisdb_theme_render_latest_from_plugin( $html, $payload, $atts ) {
	if ( null !== $html ) {
		return $html;
	}

	$latest = ( isset( $payload['latest'] ) && is_array( $payload['latest'] ) ) ? $payload['latest'] : array();
	if ( empty( $latest ) ) {
		return '<span class="themisdb-version">' . esc_html__( 'n/a', 'themisdb-theme' ) . '</span>';
	}

	$version = isset( $latest['version'] ) ? (string) $latest['version'] : '';
	$date    = isset( $latest['published_at'] ) ? (string) $latest['published_at'] : '';
	$url     = isset( $latest['html_url'] ) ? (string) $latest['html_url'] : '';
	$show    = isset( $payload['show'] ) ? (string) $payload['show'] : 'version';

	if ( 'date' === $show && '' !== $date ) {
		return '<span class="themisdb-date">' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $date ) ) ) . '</span>';
	}

	if ( 'link' === $show && '' !== $url ) {
		return '<a href="' . esc_url( $url ) . '" class="themisdb-link" target="_blank" rel="noopener">' . esc_html__( 'Neueste Version:', 'themisdb-theme' ) . ' ' . esc_html( $version ) . '</a>';
	}

	return '<span class="themisdb-version">' . esc_html( $version ) . '</span>';
}

function themisdb_theme_render_verify_from_plugin( $html, $payload, $atts ) {
	if ( null !== $html ) {
		return $html;
	}

	$title = isset( $payload['title'] ) ? (string) $payload['title'] : __( 'Download-Verifizierung', 'themisdb-theme' );

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-plugin-verify-tool">';
	echo '<article class="themisdb-section-card" style="border:1px solid #e2e8f0;border-radius:1rem;background:#fff;padding:1rem;">';
	echo '<h3 style="margin:0 0 .5rem;color:#0f172a;">' . esc_html( $title ) . '</h3>';
	echo '<p style="margin:0;color:#475569;font-size:.9rem;">' . esc_html__( 'Theme-Adapter aktiv. Die Verifizierungslogik und interaktive Controls bleiben im Plugin.', 'themisdb-theme' ) . '</p>';
	echo '<p style="margin:.5rem 0 0;color:#0c4a6e;font-size:.82rem;font-weight:700;">Get-FileHash -Algorithm SHA256 themis-*.zip</p>';
	echo '</article>';
	echo '</section>';

	return ob_get_clean();
}

function themisdb_theme_render_readme_from_plugin( $html, $payload, $atts ) {
	if ( null !== $html ) {
		return $html;
	}

	$release = ( isset( $payload['release'] ) && is_array( $payload['release'] ) ) ? $payload['release'] : array();
	$version = isset( $release['version'] ) ? (string) $release['version'] : '';
	$readme  = isset( $payload['readme'] ) ? (string) $payload['readme'] : '';
	$style   = isset( $payload['style'] ) ? (string) $payload['style'] : 'default';

	if ( '' === $readme ) {
		return '<div class="themisdb-downloads-notice">' . esc_html__( 'README nicht verfügbar.', 'themisdb-theme' ) . '</div>';
	}

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-plugin-readme">';
	echo '<article class="themisdb-section-card" style="border:1px solid #e2e8f0;border-radius:1rem;background:#fff;padding:1rem;">';
	echo '<h3 style="margin:0 0 .5rem;color:#0f172a;">README - ' . esc_html( $version ) . '</h3>';
	echo '<p style="margin:0 0 .6rem;color:#475569;font-size:.82rem;">' . esc_html__( 'Darstellung:', 'themisdb-theme' ) . ' ' . esc_html( $style ) . '</p>';
	echo '<div style="margin-top:.45rem;color:#334155;font-size:.9rem;line-height:1.45;">' . wp_kses_post( wpautop( wp_trim_words( $readme, 120 ) ) ) . '</div>';
	echo '</article>';
	echo '</section>';

	return ob_get_clean();
}

function themisdb_theme_render_changelog_from_plugin( $html, $payload, $atts ) {
	if ( null !== $html ) {
		return $html;
	}

	$release   = ( isset( $payload['release'] ) && is_array( $payload['release'] ) ) ? $payload['release'] : array();
	$version   = isset( $release['version'] ) ? (string) $release['version'] : '';
	$changelog = isset( $payload['changelog'] ) ? (string) $payload['changelog'] : '';
	$style     = isset( $payload['style'] ) ? (string) $payload['style'] : 'default';

	if ( '' === $changelog ) {
		return '<div class="themisdb-downloads-notice">' . esc_html__( 'CHANGELOG nicht verfügbar.', 'themisdb-theme' ) . '</div>';
	}

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-plugin-changelog">';
	echo '<article class="themisdb-section-card" style="border:1px solid #e2e8f0;border-radius:1rem;background:#fff;padding:1rem;">';
	echo '<h3 style="margin:0 0 .5rem;color:#0f172a;">CHANGELOG - ' . esc_html( $version ) . '</h3>';
	echo '<p style="margin:0 0 .6rem;color:#475569;font-size:.82rem;">' . esc_html__( 'Darstellung:', 'themisdb-theme' ) . ' ' . esc_html( $style ) . '</p>';
	echo '<div style="margin-top:.45rem;color:#334155;font-size:.9rem;line-height:1.45;">' . wp_kses_post( wpautop( wp_trim_words( $changelog, 120 ) ) ) . '</div>';
	echo '</article>';
	echo '</section>';

	return ob_get_clean();
}

function themisdb_theme_render_feature_matrix_from_plugin( $html, $payload, $atts ) {
	if ( null !== $html ) {
		return $html;
	}

	$features = ( isset( $payload['features'] ) && is_array( $payload['features'] ) ) ? $payload['features'] : array();
	if ( empty( $features ) ) {
		return '<p class="themisdb-downloads-empty">' . esc_html__( 'Keine Feature-Daten verfügbar.', 'themisdb-theme' ) . '</p>';
	}

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-plugin-feature-matrix">';
	echo '<div class="themisdb-section-cards" style="--lis-cols:2;display:grid;gap:1rem;grid-template-columns:repeat(var(--lis-cols),minmax(0,1fr));">';
	foreach ( array_slice( $features, 0, 8 ) as $feature ) {
		$name = isset( $feature['name'] ) ? (string) $feature['name'] : '';
		$category_name = isset( $feature['category_name'] ) ? (string) $feature['category_name'] : '';
		$themis_status = isset( $feature['themisdb'] ) ? (string) $feature['themisdb'] : '';

		echo '<article class="themisdb-section-card" style="border:1px solid #e2e8f0;border-radius:1rem;background:#fff;padding:1rem;">';
		echo '<h3 style="margin:0 0 .4rem;color:#0f172a;font-size:1.02rem;">' . esc_html( $name ) . '</h3>';
		if ( '' !== $category_name ) {
			echo '<p style="margin:0 0 .5rem;color:#64748b;font-size:.78rem;">' . esc_html( $category_name ) . '</p>';
		}
		if ( '' !== $themis_status ) {
			echo '<p style="margin:0;color:#0c4a6e;font-size:.86rem;font-weight:700;">ThemisDB: ' . esc_html( $themis_status ) . '</p>';
		}
		echo '</article>';
	}
	echo '</div>';
	echo '</section>';

	return ob_get_clean();
}

function themisdb_theme_render_release_timeline_from_plugin( $html, $timeline_data, $atts ) {
	if ( null !== $html ) {
		return $html;
	}

	if ( ! is_array( $timeline_data ) || empty( $timeline_data ) ) {
		return '<p class="themisdb-downloads-empty">' . esc_html__( 'Keine Timeline-Daten verfügbar.', 'themisdb-theme' ) . '</p>';
	}

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-plugin-release-timeline">';
	echo '<div class="themisdb-timeline" style="display:grid;gap:1rem;">';
	foreach ( array_slice( $timeline_data, 0, 8 ) as $item ) {
		$version = isset( $item['version'] ) ? (string) $item['version'] : '';
		$name    = isset( $item['name'] ) ? (string) $item['name'] : '';
		$date    = isset( $item['date'] ) ? (string) $item['date'] : '';
		$url     = isset( $item['url'] ) ? (string) $item['url'] : '';

		echo '<article style="border:1px solid #e2e8f0;border-radius:1rem;background:#fff;padding:1rem 1.1rem;">';
		if ( '' !== $date ) {
			echo '<p style="margin:0 0 .3rem;font-size:.72rem;font-weight:700;color:#64748b;">' . esc_html( $date ) . '</p>';
		}
		echo '<h3 style="margin:0 0 .35rem;font-size:1rem;color:#0f172a;">' . esc_html( $version ) . '</h3>';
		if ( '' !== $name ) {
			echo '<p style="margin:0 0 .55rem;color:#475569;font-size:.9rem;">' . esc_html( $name ) . '</p>';
		}
		if ( '' !== $url ) {
			echo '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener" style="color:#0c4a6e;text-decoration:none;font-weight:700;">' . esc_html__( 'Release öffnen', 'themisdb-theme' ) . '</a>';
		}
		echo '</article>';
	}
	echo '</div>';
	echo '</section>';

	return ob_get_clean();
}

function themisdb_theme_render_front_slider_from_plugin( $html, $payload, $atts ) {
	if ( null !== $html ) {
		return $html;
	}

	$slides       = ( isset( $payload['slides'] ) && is_array( $payload['slides'] ) ) ? $payload['slides'] : array();
	$interval     = isset( $payload['interval'] ) ? (int) $payload['interval'] : 5000;
	$autoplay     = ! empty( $payload['autoplay'] );
	$overlay      = isset( $payload['overlay'] ) ? (string) $payload['overlay'] : 'normal';
	$image_height = isset( $payload['image_height'] ) ? (int) $payload['image_height'] : 420;

	if ( empty( $slides ) ) {
		return '<p class="themisdb-fs-no-posts">' . esc_html__( 'Keine Artikel gefunden.', 'themisdb-theme' ) . '</p>';
	}

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-plugin-front-slider">';
	echo '<article class="themisdb-section-card" style="border:1px solid #e2e8f0;border-radius:1rem;background:#fff;padding:1rem;">';
	echo '<h3 style="margin:0 0 .5rem;color:#0f172a;">' . esc_html__( 'ThemisDB Front Slider', 'themisdb-theme' ) . '</h3>';
	echo '<p style="margin:0;color:#475569;font-size:.9rem;">' . esc_html__( 'Theme-Adapter aktiv. Die Theme-Darstellung ersetzt den Plugin-Slider-Container.', 'themisdb-theme' ) . '</p>';
	echo '<p style="margin:.45rem 0 0;color:#475569;font-size:.82rem;">' . esc_html__( 'Slides:', 'themisdb-theme' ) . ' ' . esc_html( (string) count( $slides ) ) . ' | ' . esc_html__( 'Intervall:', 'themisdb-theme' ) . ' ' . esc_html( (string) $interval ) . 'ms | ' . esc_html__( 'Autoplay:', 'themisdb-theme' ) . ' ' . esc_html( $autoplay ? __( 'ja', 'themisdb-theme' ) : __( 'nein', 'themisdb-theme' ) ) . '</p>';
	echo '<p style="margin:.35rem 0 0;color:#475569;font-size:.82rem;">' . esc_html__( 'Overlay:', 'themisdb-theme' ) . ' ' . esc_html( $overlay ) . ' | ' . esc_html__( 'Bildhoehe:', 'themisdb-theme' ) . ' ' . esc_html( (string) $image_height ) . 'px</p>';
	echo '<ul style="margin:.7rem 0 0;padding-left:1.1rem;color:#334155;display:grid;gap:.35rem;">';
	foreach ( array_slice( $slides, 0, 5 ) as $slide ) {
		$title = isset( $slide['title'] ) ? (string) $slide['title'] : '';
		$url   = isset( $slide['url'] ) ? (string) $slide['url'] : '';
		if ( '' === $title || '' === $url ) {
			continue;
		}
		echo '<li><a href="' . esc_url( $url ) . '" style="color:#0c4a6e;text-decoration:none;font-weight:700;">' . esc_html( $title ) . '</a></li>';
	}
	echo '</ul>';
	echo '</article>';
	echo '</section>';

	return ob_get_clean();
}

function themisdb_theme_render_gallery_from_plugin( $html, $payload, $atts ) {
	if ( null !== $html ) {
		return $html;
	}

	$items = ( isset( $payload['items'] ) && is_array( $payload['items'] ) ) ? $payload['items'] : array();
	if ( empty( $items ) ) {
		return '<p class="themisdb-downloads-empty">' . esc_html__( 'Keine Galerie-Elemente verfügbar.', 'themisdb-theme' ) . '</p>';
	}

	$columns = isset( $atts['columns'] ) ? max( 1, min( 4, (int) $atts['columns'] ) ) : 3;

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-plugin-gallery">';
	echo '<div class="themisdb-gallery-grid" style="grid-template-columns:repeat(' . esc_attr( (string) $columns ) . ',1fr);">';
	foreach ( $items as $item ) {
		$thumb = isset( $item['thumb'] ) ? (string) $item['thumb'] : '';
		$url   = isset( $item['url'] ) ? (string) $item['url'] : '';
		$title = isset( $item['title'] ) ? (string) $item['title'] : '';
		if ( '' === $thumb || '' === $url ) {
			continue;
		}

		echo '<a class="themisdb-gallery-item" href="' . esc_url( $url ) . '" data-title="' . esc_attr( $title ) . '" data-desc="" tabindex="0">';
		echo '<img src="' . esc_url( $thumb ) . '" alt="' . esc_attr( $title ) . '">';
		echo '<span class="themisdb-gallery-overlay"><span class="themisdb-gallery-zoom-icon">+</span></span>';
		echo '</a>';
	}
	echo '</div>';
	echo '</section>';

	return ob_get_clean();
}

function themisdb_theme_render_benchmark_visualizer_from_plugin( $html, $payload, $atts ) {
	if ( null !== $html ) {
		return $html;
	}

	$category   = isset( $payload['category'] ) ? (string) $payload['category'] : 'all';
	$metric     = isset( $payload['metric'] ) ? (string) $payload['metric'] : 'latency';
	$chart_type = isset( $payload['chart_type'] ) ? (string) $payload['chart_type'] : 'bar';
	$compare    = isset( $payload['compare'] ) ? (string) $payload['compare'] : '';
	$theme      = isset( $payload['chart_theme'] ) ? (string) $payload['chart_theme'] : 'light';

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-plugin-benchmark-visualizer">';
	echo '<article class="themisdb-section-card" style="border:1px solid #e2e8f0;border-radius:1rem;background:#fff;padding:1rem;">';
	echo '<h3 style="margin:0 0 .5rem;color:#0f172a;">' . esc_html__( 'Benchmark Visualizer', 'themisdb-theme' ) . '</h3>';
	echo '<p style="margin:0 0 .6rem;color:#475569;font-size:.9rem;">' . esc_html__( 'Theme-gesteuerter Container fuer Benchmark-Vergleiche und Chart-Interaktion.', 'themisdb-theme' ) . '</p>';
	echo '<div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.6rem;">';
	echo '<p style="margin:0;color:#475569;font-size:.86rem;">Kategorie: <strong>' . esc_html( $category ) . '</strong></p>';
	echo '<p style="margin:0;color:#475569;font-size:.86rem;">Metrik: <strong>' . esc_html( $metric ) . '</strong></p>';
	echo '<p style="margin:0;color:#475569;font-size:.86rem;">Diagramm: <strong>' . esc_html( $chart_type ) . '</strong></p>';
	echo '<p style="margin:0;color:#475569;font-size:.86rem;">Theme: <strong>' . esc_html( $theme ) . '</strong></p>';
	echo '</div>';
	if ( '' !== $compare ) {
		echo '<p style="margin:.6rem 0 0;color:#0c4a6e;font-size:.82rem;font-weight:700;word-break:break-word;">' . esc_html__( 'Vergleichsdatenbanken:', 'themisdb-theme' ) . ' ' . esc_html( $compare ) . '</p>';
	}
	echo '<div class="themisdb-benchmark-visualizer-canvas" style="margin-top:.9rem;min-height:18rem;border:1px dashed #cbd5e1;border-radius:.8rem;display:grid;place-items:center;color:#64748b;font-size:.9rem;">';
	echo esc_html__( 'Benchmark-Visualisierung wird durch das Plugin-JavaScript befuellt.', 'themisdb-theme' );
	echo '</div>';
	echo '</article>';
	echo '</section>';

	return ob_get_clean();
}

function themisdb_theme_render_architecture_from_plugin( $html, $payload, $atts ) {
	if ( null !== $html ) {
		return $html;
	}

	$view              = isset( $payload['view'] ) ? (string) $payload['view'] : 'high_level';
	$theme             = isset( $payload['theme'] ) ? (string) $payload['theme'] : 'neutral';
	$interactive       = ! empty( $payload['interactive'] );
	$show_controls     = ! empty( $payload['show_controls'] );
	$color_scheme      = isset( $payload['color_scheme'] ) ? (string) $payload['color_scheme'] : 'light';
	$enable_export     = ! empty( $payload['enable_export'] );
	$show_descriptions = ! empty( $payload['show_descriptions'] );

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-plugin-architecture-diagrams">';
	echo '<article class="themisdb-section-card" style="border:1px solid #e2e8f0;border-radius:1rem;background:#fff;padding:1rem;">';
	echo '<h3 style="margin:0 0 .5rem;color:#0f172a;">' . esc_html__( 'Architecture Diagrams', 'themisdb-theme' ) . '</h3>';
	echo '<p style="margin:0 0 .6rem;color:#475569;font-size:.9rem;">' . esc_html__( 'Theme-gesteuerter Container fuer Mermaid-basierte Architekturdiagramme und zugehoerige Steuerung.', 'themisdb-theme' ) . '</p>';
	echo '<div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.6rem;">';
	echo '<p style="margin:0;color:#475569;font-size:.86rem;">View: <strong>' . esc_html( $view ) . '</strong></p>';
	echo '<p style="margin:0;color:#475569;font-size:.86rem;">Theme: <strong>' . esc_html( $theme ) . '</strong></p>';
	echo '<p style="margin:0;color:#475569;font-size:.86rem;">Farbschema: <strong>' . esc_html( $color_scheme ) . '</strong></p>';
	echo '<p style="margin:0;color:#475569;font-size:.86rem;">Interaktiv: <strong>' . esc_html( $interactive ? 'ja' : 'nein' ) . '</strong></p>';
	echo '</div>';
	if ( $show_controls || $enable_export || $show_descriptions ) {
		echo '<p style="margin:.7rem 0 0;color:#0c4a6e;font-size:.82rem;font-weight:700;">';
		$flags = array();
		if ( $show_controls ) {
			$flags[] = __( 'Controls aktiv', 'themisdb-theme' );
		}
		if ( $enable_export ) {
			$flags[] = __( 'Export aktiv', 'themisdb-theme' );
		}
		if ( $show_descriptions ) {
			$flags[] = __( 'Beschreibungen aktiv', 'themisdb-theme' );
		}
		echo esc_html( implode( ' | ', $flags ) );
		echo '</p>';
	}
	echo '<div class="themisdb-architecture-diagram-canvas" style="margin-top:.9rem;min-height:18rem;border:1px dashed #cbd5e1;border-radius:.8rem;display:grid;place-items:center;color:#64748b;font-size:.9rem;">';
	echo esc_html__( 'Diagrammoberflaeche wird durch das Plugin-JavaScript befuellt.', 'themisdb-theme' );
	echo '</div>';
	echo '</article>';
	echo '</section>';

	return ob_get_clean();
}

function themisdb_theme_render_support_portal_from_plugin( $html, $payload, $atts ) {
	if ( null !== $html ) {
		return $html;
	}

	$has_license          = ! empty( $payload['has_license'] );
	$ticket_count         = isset( $payload['ticket_count'] ) ? (int) $payload['ticket_count'] : 0;
	$license_info         = ( isset( $payload['license_info'] ) && is_array( $payload['license_info'] ) ) ? $payload['license_info'] : array();
	$support_benefit_info = ( isset( $payload['support_benefit_info'] ) && is_array( $payload['support_benefit_info'] ) ) ? $payload['support_benefit_info'] : array();

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-plugin-support-portal">';
	echo '<article class="themisdb-section-card" style="border:1px solid #e2e8f0;border-radius:1rem;background:#fff;padding:1rem;">';
	echo '<h3 style="margin:0 0 .5rem;color:#0f172a;">' . esc_html__( 'Support Portal', 'themisdb-theme' ) . '</h3>';
	if ( ! $has_license ) {
		echo '<p style="margin:0;color:#475569;font-size:.9rem;">' . esc_html__( 'Lizenzanmeldung erforderlich, um Support-Tickets anzuzeigen.', 'themisdb-theme' ) . '</p>';
	} else {
		echo '<p style="margin:0;color:#475569;font-size:.9rem;">' . esc_html__( 'Offene Portalansicht mit Ticketliste und Ticket-Erstellung.', 'themisdb-theme' ) . '</p>';
		echo '<p style="margin:.35rem 0 0;color:#0c4a6e;font-size:.82rem;font-weight:700;">' . esc_html__( 'Tickets:', 'themisdb-theme' ) . ' ' . esc_html( (string) $ticket_count ) . '</p>';
		if ( ! empty( $license_info['edition'] ) ) {
			echo '<p style="margin:.35rem 0 0;color:#475569;font-size:.82rem;">' . esc_html__( 'Lizenz:', 'themisdb-theme' ) . ' ' . esc_html( strtoupper( (string) $license_info['edition'] ) ) . '</p>';
		}
		if ( ! empty( $support_benefit_info['tier_label'] ) ) {
			echo '<p style="margin:.35rem 0 0;color:#475569;font-size:.82rem;">' . esc_html__( 'Support-Tier:', 'themisdb-theme' ) . ' ' . esc_html( (string) $support_benefit_info['tier_label'] ) . '</p>';
		}
	}
	echo '</article>';
	echo '</section>';

	return ob_get_clean();
}

function themisdb_theme_render_support_login_from_plugin( $html, $payload, $atts ) {
	if ( null !== $html ) {
		return $html;
	}

	$has_license = ! empty( $payload['has_license'] );
	$redirect    = isset( $payload['redirect'] ) ? (string) $payload['redirect'] : home_url( '/' );
	$message     = isset( $payload['message'] ) ? (string) $payload['message'] : '';

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-plugin-support-login">';
	echo '<article class="themisdb-section-card" style="border:1px solid #e2e8f0;border-radius:1rem;background:#fff;padding:1rem;">';
	echo '<h3 style="margin:0 0 .5rem;color:#0f172a;">' . esc_html__( 'Support Login', 'themisdb-theme' ) . '</h3>';
	if ( $has_license ) {
		echo '<p style="margin:0 0 .6rem;color:#065f46;font-size:.86rem;font-weight:700;">' . esc_html( $message !== '' ? $message : __( 'Sie sind bereits angemeldet.', 'themisdb-theme' ) ) . '</p>';
		echo '<a href="' . esc_url( $redirect ) . '" class="button button-primary">' . esc_html__( 'Zum Portal', 'themisdb-theme' ) . '</a>';
	} else {
		echo '<p style="margin:0;color:#475569;font-size:.9rem;">' . esc_html__( 'Lizenzdatei-Login für den Zugriff auf das Support-Portal.', 'themisdb-theme' ) . '</p>';
	}
	echo '</article>';
	echo '</section>';

	return ob_get_clean();
}

function themisdb_theme_render_formula_from_plugin( $html, $payload, $atts ) {
	if ( null !== $html ) {
		return $html;
	}

	$is_block = ! empty( $payload['is_block'] );
	$formula  = isset( $payload['formula'] ) ? (string) $payload['formula'] : '';
	$class    = isset( $payload['class'] ) ? (string) $payload['class'] : '';

	$classes = array( 'themisdb-theme-formula' );
	$classes[] = $is_block ? 'themisdb-theme-formula-block' : 'themisdb-theme-formula-inline';
	if ( '' !== $class ) {
		$classes[] = sanitize_html_class( $class );
	}
	$class_attr = implode( ' ', $classes );

	if ( $is_block ) {
		return '<div class="' . esc_attr( $class_attr ) . '">' . esc_html( $formula ) . '</div>';
	}

	return '<span class="' . esc_attr( $class_attr ) . '">' . esc_html( $formula ) . '</span>';
}

function themisdb_theme_render_wiki_from_plugin( $html, $payload, $atts ) {
	if ( null !== $html ) {
		return $html;
	}

	$file     = isset( $payload['file'] ) ? (string) $payload['file'] : 'README.md';
	$lang     = isset( $payload['lang'] ) ? (string) $payload['lang'] : 'de';
	$show_toc = ! empty( $payload['show_toc'] );
	$error    = isset( $payload['error'] ) ? (string) $payload['error'] : '';

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-plugin-wiki">';
	echo '<article class="themisdb-section-card" style="border:1px solid #e2e8f0;border-radius:1rem;background:#fff;padding:1rem;">';
	echo '<h3 style="margin:0 0 .5rem;color:#0f172a;">' . esc_html__( 'Wiki Content', 'themisdb-theme' ) . '</h3>';
	if ( '' !== $error ) {
		echo '<p style="margin:0;color:#b91c1c;font-size:.9rem;">' . esc_html( $error ) . '</p>';
	} else {
		echo '<p style="margin:0;color:#475569;font-size:.9rem;">' . esc_html__( 'Dokument:', 'themisdb-theme' ) . ' ' . esc_html( $file ) . '</p>';
		echo '<p style="margin:.35rem 0 0;color:#475569;font-size:.82rem;">' . esc_html__( 'Sprache:', 'themisdb-theme' ) . ' ' . esc_html( strtoupper( $lang ) ) . '</p>';
		if ( $show_toc ) {
			echo '<p style="margin:.35rem 0 0;color:#0c4a6e;font-size:.82rem;font-weight:700;">' . esc_html__( 'Inhaltsverzeichnis aktiv.', 'themisdb-theme' ) . '</p>';
		}
	}
	echo '</article>';
	echo '</section>';

	return ob_get_clean();
}

function themisdb_theme_render_docs_from_plugin( $html, $payload, $atts ) {
	if ( null !== $html ) {
		return $html;
	}

	$lang   = isset( $payload['lang'] ) ? (string) $payload['lang'] : 'de';
	$layout = isset( $payload['layout'] ) ? (string) $payload['layout'] : 'list';
	$files  = ( isset( $payload['files'] ) && is_array( $payload['files'] ) ) ? $payload['files'] : array();
	$error  = isset( $payload['error'] ) ? (string) $payload['error'] : '';

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-plugin-docs">';
	echo '<article class="themisdb-section-card" style="border:1px solid #e2e8f0;border-radius:1rem;background:#fff;padding:1rem;">';
	echo '<h3 style="margin:0 0 .5rem;color:#0f172a;">' . esc_html__( 'Docs Index', 'themisdb-theme' ) . '</h3>';
	if ( '' !== $error ) {
		echo '<p style="margin:0;color:#b91c1c;font-size:.9rem;">' . esc_html( $error ) . '</p>';
	} else {
		echo '<p style="margin:0;color:#475569;font-size:.9rem;">' . esc_html__( 'Layout:', 'themisdb-theme' ) . ' ' . esc_html( $layout ) . '</p>';
		echo '<p style="margin:.35rem 0 0;color:#475569;font-size:.82rem;">' . esc_html__( 'Sprache:', 'themisdb-theme' ) . ' ' . esc_html( strtoupper( $lang ) ) . '</p>';
		echo '<p style="margin:.35rem 0 0;color:#0c4a6e;font-size:.82rem;font-weight:700;">' . esc_html__( 'Gefundene Einträge:', 'themisdb-theme' ) . ' ' . esc_html( (string) count( $files ) ) . '</p>';
	}
	echo '</article>';
	echo '</section>';

	return ob_get_clean();
}

function themisdb_theme_render_wiki_nav_from_plugin( $html, $payload, $atts ) {
	if ( null !== $html ) {
		return $html;
	}

	$lang  = isset( $payload['lang'] ) ? (string) $payload['lang'] : 'de';
	$style = isset( $payload['style'] ) ? (string) $payload['style'] : 'sidebar';
	$error = isset( $payload['error'] ) ? (string) $payload['error'] : '';

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-plugin-wiki-nav">';
	echo '<article class="themisdb-section-card" style="border:1px solid #e2e8f0;border-radius:1rem;background:#fff;padding:1rem;">';
	echo '<h3 style="margin:0 0 .5rem;color:#0f172a;">' . esc_html__( 'Wiki Navigation', 'themisdb-theme' ) . '</h3>';
	if ( '' !== $error ) {
		echo '<p style="margin:0;color:#b91c1c;font-size:.9rem;">' . esc_html( $error ) . '</p>';
	} else {
		echo '<p style="margin:0;color:#475569;font-size:.9rem;">' . esc_html__( 'Darstellung:', 'themisdb-theme' ) . ' ' . esc_html( $style ) . '</p>';
		echo '<p style="margin:.35rem 0 0;color:#475569;font-size:.82rem;">' . esc_html__( 'Sprache:', 'themisdb-theme' ) . ' ' . esc_html( strtoupper( $lang ) ) . '</p>';
	}
	echo '</article>';
	echo '</section>';

	return ob_get_clean();
}

function themisdb_theme_render_tco_calculator_from_plugin( $html, $payload, $atts ) {
	if ( null !== $html ) {
		return $html;
	}

	$title      = isset( $payload['title'] ) ? (string) $payload['title'] : __( 'ThemisDB TCO-Rechner', 'themisdb-theme' );
	$show_intro = ! empty( $payload['show_intro'] );

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-plugin-tco-calculator">';
	echo '<article class="themisdb-section-card" style="border:1px solid #e2e8f0;border-radius:1rem;background:#fff;padding:1rem;">';
	echo '<h3 style="margin:0 0 .5rem;color:#0f172a;">' . esc_html( $title ) . '</h3>';
	echo '<p style="margin:0;color:#475569;font-size:.9rem;">' . esc_html__( 'Theme-Adapter fuer den TCO-Rechner ist aktiv. Chart.js-, Mermaid- und Berechnungslogik verbleiben im Plugin.', 'themisdb-theme' ) . '</p>';
	if ( $show_intro ) {
		echo '<p style="margin:.45rem 0 0;color:#0c4a6e;font-size:.82rem;font-weight:700;">' . esc_html__( 'Intro-Abschnitt aktiviert.', 'themisdb-theme' ) . '</p>';
	}
	echo '</article>';
	echo '</section>';

	return ob_get_clean();
}

function themisdb_theme_render_tco_section_card( $html, $payload, $heading, $description ) {
	if ( null !== $html ) {
		return $html;
	}

	$scale     = isset( $payload['scale'] ) ? (string) $payload['scale'] : '1';
	$animation = isset( $payload['animation'] ) ? (string) $payload['animation'] : 'fade-in';
	$delay     = isset( $payload['delay'] ) ? (string) $payload['delay'] : '0';

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-plugin-tco-section">';
	echo '<article class="themisdb-section-card" style="border:1px solid #e2e8f0;border-radius:1rem;background:#fff;padding:1rem;">';
	echo '<h3 style="margin:0 0 .5rem;color:#0f172a;">' . esc_html( $heading ) . '</h3>';
	echo '<p style="margin:0;color:#475569;font-size:.9rem;">' . esc_html( $description ) . '</p>';
	echo '<p style="margin:.45rem 0 0;color:#475569;font-size:.82rem;">' . esc_html__( 'Animation:', 'themisdb-theme' ) . ' ' . esc_html( $animation ) . ' | ' . esc_html__( 'Verzoegerung:', 'themisdb-theme' ) . ' ' . esc_html( $delay ) . ' | ' . esc_html__( 'Skalierung:', 'themisdb-theme' ) . ' ' . esc_html( $scale ) . '</p>';
	echo '</article>';
	echo '</section>';

	return ob_get_clean();
}

function themisdb_theme_render_tco_workload_from_plugin( $html, $payload, $atts ) {
	return themisdb_theme_render_tco_section_card( $html, $payload, __( 'TCO Workload', 'themisdb-theme' ), __( 'Theme-Container fuer Workload-Eingaben des Rechners.', 'themisdb-theme' ) );
}

function themisdb_theme_render_tco_infrastructure_from_plugin( $html, $payload, $atts ) {
	return themisdb_theme_render_tco_section_card( $html, $payload, __( 'TCO Infrastructure', 'themisdb-theme' ), __( 'Theme-Container fuer Infrastrukturparameter des Rechners.', 'themisdb-theme' ) );
}

function themisdb_theme_render_tco_personnel_from_plugin( $html, $payload, $atts ) {
	return themisdb_theme_render_tco_section_card( $html, $payload, __( 'TCO Personnel', 'themisdb-theme' ), __( 'Theme-Container fuer Personal- und Rollenparameter.', 'themisdb-theme' ) );
}

function themisdb_theme_render_tco_operations_from_plugin( $html, $payload, $atts ) {
	return themisdb_theme_render_tco_section_card( $html, $payload, __( 'TCO Operations', 'themisdb-theme' ), __( 'Theme-Container fuer Betriebs- und Wartungskosten.', 'themisdb-theme' ) );
}

function themisdb_theme_render_tco_ai_from_plugin( $html, $payload, $atts ) {
	return themisdb_theme_render_tco_section_card( $html, $payload, __( 'TCO AI', 'themisdb-theme' ), __( 'Theme-Container fuer KI-bezogene Kosten- und Nutzenparameter.', 'themisdb-theme' ) );
}

function themisdb_theme_render_tco_results_from_plugin( $html, $payload, $atts ) {
	return themisdb_theme_render_tco_section_card( $html, $payload, __( 'TCO Results', 'themisdb-theme' ), __( 'Theme-Container fuer Ergebnisdarstellung und Vergleichsausgabe.', 'themisdb-theme' ) );
}

function themisdb_theme_render_persistent_podcast_player_from_plugin( $html, $payload ) {
	if ( null !== $html ) {
		return $html;
	}

	$player_id   = isset( $payload['player_id'] ) ? (string) $payload['player_id'] : 'ppp-player';
	$audio_id    = isset( $payload['audio_id'] ) ? (string) $payload['audio_id'] : 'ppp-audio';
	$playlist_id = isset( $payload['playlist_id'] ) ? (string) $payload['playlist_id'] : 'ppp-playlist';

	ob_start();
	echo '<div id="' . esc_attr( $player_id ) . '" class="ppp-player themisdb-theme-player-shell">';
	echo '<div class="ppp-player-container">';
	echo '<div class="ppp-controls">';
	echo '<button id="ppp-prev" class="ppp-btn" aria-label="' . esc_attr__( 'Vorherige Episode', 'themisdb-theme' ) . '"><span>&#9664;</span></button>';
	echo '<button id="ppp-skip-backward" class="ppp-btn ppp-btn-skip" aria-label="' . esc_attr__( '15 Sekunden zurueck', 'themisdb-theme' ) . '"><span>&#8634; 15</span></button>';
	echo '<button id="ppp-play-pause" class="ppp-btn ppp-btn-play" aria-label="' . esc_attr__( 'Play/Pause', 'themisdb-theme' ) . '"><span class="ppp-play-icon">&#9654;</span><span class="ppp-pause-icon">&#10074;&#10074;</span></button>';
	echo '<button id="ppp-skip-forward" class="ppp-btn ppp-btn-skip" aria-label="' . esc_attr__( '30 Sekunden vor', 'themisdb-theme' ) . '"><span>30 &#8635;</span></button>';
	echo '<button id="ppp-next" class="ppp-btn" aria-label="' . esc_attr__( 'Naechste Episode', 'themisdb-theme' ) . '"><span>&#9654;</span></button>';
	echo '</div>';
	echo '<div class="ppp-time-display"><span id="ppp-current-time">0:00</span></div>';
	echo '<div class="ppp-progress-container"><div class="ppp-progress-bar" id="ppp-progress-bar" role="slider" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" tabindex="0"><div class="ppp-progress-fill" id="ppp-progress-fill"></div><div class="ppp-progress-buffer" id="ppp-progress-buffer"></div></div></div>';
	echo '<div class="ppp-time-display"><span id="ppp-total-time">0:00</span></div>';
	echo '<div class="ppp-volume-container"><button id="ppp-volume-btn" class="ppp-btn ppp-btn-small" aria-label="' . esc_attr__( 'Lautstaerke', 'themisdb-theme' ) . '"><span class="ppp-volume-icon ppp-volume-on">&#128266;</span><span class="ppp-volume-icon ppp-volume-off">&#128263;</span></button><div class="ppp-volume-slider-container"><input type="range" id="ppp-volume-slider" class="ppp-volume-slider" min="0" max="100" value="100" aria-label="' . esc_attr__( 'Lautstaerkepegel', 'themisdb-theme' ) . '"></div></div>';
	echo '<div class="ppp-speed-container"><button id="ppp-speed-btn" class="ppp-btn ppp-btn-small" aria-label="' . esc_attr__( 'Wiedergabegeschwindigkeit', 'themisdb-theme' ) . '"><span id="ppp-speed-label">1x</span></button><div id="ppp-speed-menu" class="ppp-speed-menu" style="display:none;"><button class="ppp-speed-option" data-speed="0.5">0.5x</button><button class="ppp-speed-option" data-speed="0.75">0.75x</button><button class="ppp-speed-option ppp-speed-active" data-speed="1">1x</button><button class="ppp-speed-option" data-speed="1.25">1.25x</button><button class="ppp-speed-option" data-speed="1.5">1.5x</button><button class="ppp-speed-option" data-speed="1.75">1.75x</button><button class="ppp-speed-option" data-speed="2">2x</button></div></div>';
	echo '<div class="ppp-info"><div class="ppp-title" id="ppp-title">' . esc_html__( 'Episode auswaehlen', 'themisdb-theme' ) . '</div><div class="ppp-excerpt" id="ppp-excerpt"></div></div>';
	echo '<div class="ppp-link-container"><a href="#" id="ppp-link" class="ppp-link" target="_blank" rel="noopener noreferrer" style="display:none;">' . esc_html__( 'Zum Artikel', 'themisdb-theme' ) . '</a></div>';
	echo '<div class="ppp-options"><label class="ppp-checkbox-label"><input type="checkbox" id="ppp-continuous-play" checked><span>' . esc_html__( 'Continuous Play', 'themisdb-theme' ) . '</span></label><label class="ppp-checkbox-label"><input type="checkbox" id="ppp-auto-minimize" checked><span>' . esc_html__( 'Auto-Minimize', 'themisdb-theme' ) . '</span></label></div>';
	echo '<div class="ppp-playlist-toggle"><button id="ppp-toggle-playlist" class="ppp-btn-toggle" aria-label="' . esc_attr__( 'Playlist umschalten', 'themisdb-theme' ) . '">' . esc_html__( 'Playlist', 'themisdb-theme' ) . '</button></div>';
	echo '<div class="ppp-minimize-toggle"><button id="ppp-toggle-minimize" class="ppp-btn-toggle ppp-btn-minimize" aria-label="' . esc_attr__( 'Player minimieren', 'themisdb-theme' ) . '">▾</button></div>';
	echo '</div>';
	echo '<div id="ppp-loading" class="ppp-loading" style="display:none;"><div class="ppp-spinner"></div></div>';
	echo '<div id="ppp-error" class="ppp-error" style="display:none;"><span class="ppp-error-icon">⚠</span><span class="ppp-error-message" id="ppp-error-message">' . esc_html__( 'Fehler beim Laden der Audiodatei', 'themisdb-theme' ) . '</span><button id="ppp-error-retry" class="ppp-btn-retry">' . esc_html__( 'Erneut versuchen', 'themisdb-theme' ) . '</button></div>';
	echo '<div id="' . esc_attr( $playlist_id ) . '" class="ppp-playlist" style="display:none;"><div class="ppp-playlist-items" id="ppp-playlist-items"></div></div>';
	echo '<audio id="' . esc_attr( $audio_id ) . '" preload="metadata"></audio>';
	echo '</div>';

	return ob_get_clean();
}

function themisdb_theme_render_test_dashboard_from_plugin( $html, $payload, $atts ) {
	if ( null !== $html ) {
		return $html;
	}

	$view       = isset( $payload['view'] ) ? (string) $payload['view'] : 'overview';
	$period     = isset( $payload['period'] ) ? (int) $payload['period'] : 30;
	$repo       = isset( $payload['repo'] ) ? (string) $payload['repo'] : 'makr-code/wordpressPlugins';
	$chart_type = isset( $payload['chart_type'] ) ? (string) $payload['chart_type'] : 'line';
	$height     = isset( $payload['height'] ) ? (string) $payload['height'] : '600px';

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-plugin-test-dashboard">';
	echo '<article class="themisdb-section-card" style="border:1px solid #e2e8f0;border-radius:1rem;background:#fff;padding:1rem;">';
	echo '<h3 style="margin:0 0 .5rem;color:#0f172a;">' . esc_html__( 'ThemisDB Test Dashboard', 'themisdb-theme' ) . '</h3>';
	echo '<p style="margin:0;color:#475569;font-size:.9rem;">' . esc_html__( 'Theme-Adapter aktiv. Datenabruf, AJAX-Endpoints und Chart.js-Logik verbleiben im Plugin.', 'themisdb-theme' ) . '</p>';
	echo '<p style="margin:.45rem 0 0;color:#475569;font-size:.82rem;">' . esc_html__( 'View:', 'themisdb-theme' ) . ' ' . esc_html( $view ) . ' | ' . esc_html__( 'Period:', 'themisdb-theme' ) . ' ' . esc_html( (string) $period ) . ' | Repo: ' . esc_html( $repo ) . '</p>';
	echo '<p style="margin:.35rem 0 0;color:#475569;font-size:.82rem;">' . esc_html__( 'Chart-Typ:', 'themisdb-theme' ) . ' ' . esc_html( $chart_type ) . ' | ' . esc_html__( 'Hoehe:', 'themisdb-theme' ) . ' ' . esc_html( $height ) . '</p>';
	echo '<div id="tdb-content" style="margin-top:.75rem;min-height:10rem;border:1px dashed #cbd5e1;border-radius:.8rem;display:grid;place-items:center;color:#64748b;font-size:.9rem;">' . esc_html__( 'Dashboard-Inhalt wird durch Plugin-JavaScript geladen.', 'themisdb-theme' ) . '</div>';
	echo '</article>';
	echo '</section>';

	return ob_get_clean();
}

function themisdb_theme_filter_graph_navigation_payload( $payload ) {
	if ( ! is_array( $payload ) ) {
		return array( 'nodes' => array(), 'links' => array() );
	}

	$payload['nodes'] = ( isset( $payload['nodes'] ) && is_array( $payload['nodes'] ) ) ? $payload['nodes'] : array();
	$payload['links'] = ( isset( $payload['links'] ) && is_array( $payload['links'] ) ) ? $payload['links'] : array();

	return $payload;
}

function themisdb_theme_render_taxonomy_from_plugin( $html, $payload, $atts ) {
	if ( null !== $html ) {
		return $html;
	}

	$terms = ( isset( $payload['terms'] ) && is_array( $payload['terms'] ) ) ? $payload['terms'] : array();
	if ( empty( $terms ) ) {
		return '<p class="themisdb-downloads-empty">' . esc_html__( 'Keine Taxonomie-Begriffe verfügbar.', 'themisdb-theme' ) . '</p>';
	}

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-plugin-taxonomy">';
	echo '<div class="themisdb-button-box-grid" style="--lis-button-cols:3;">';
	foreach ( $terms as $term ) {
		$link = isset( $term['link'] ) ? (string) $term['link'] : '';
		$name = isset( $term['name'] ) ? (string) $term['name'] : '';
		$count = isset( $term['count'] ) ? (int) $term['count'] : 0;
		if ( '' === $link || '' === $name ) {
			continue;
		}

		echo '<a class="themisdb-button-box" href="' . esc_url( $link ) . '">';
		echo '<strong class="themisdb-button-box-title">' . esc_html( $name ) . '</strong>';
		echo '<span style="display:block;margin-top:.35rem;color:#64748b;font-size:.78rem;">' . esc_html( (string) $count ) . ' ' . esc_html__( 'Beiträge', 'themisdb-theme' ) . '</span>';
		echo '</a>';
	}
	echo '</div>';
	echo '</section>';

	return ob_get_clean();
}

function themisdb_theme_render_taxonomy_info_from_plugin( $html, $payload, $atts ) {
	if ( null !== $html ) {
		return $html;
	}

	$name        = isset( $payload['name'] ) ? (string) $payload['name'] : '';
	$description = isset( $payload['description'] ) ? (string) $payload['description'] : '';
	$count       = isset( $payload['count'] ) ? (int) $payload['count'] : 0;
	$icon        = isset( $payload['icon'] ) ? (string) $payload['icon'] : '';
	$color       = isset( $payload['color'] ) ? (string) $payload['color'] : '#0c4a6e';

	if ( '' === $name ) {
		return '<p class="themisdb-downloads-empty">' . esc_html__( 'Kein Taxonomie-Info-Element verfügbar.', 'themisdb-theme' ) . '</p>';
	}

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-plugin-taxonomy-info">';
	echo '<article class="themisdb-section-card" style="border:1px solid #e2e8f0;border-left:6px solid ' . esc_attr( $color ) . ';border-radius:1rem;background:#fff;padding:1rem;">';
	if ( '' !== $icon ) {
		echo '<p style="margin:0 0 .35rem;font-size:1.1rem;line-height:1;">' . esc_html( $icon ) . '</p>';
	}
	echo '<h3 style="margin:0 0 .45rem;color:#0f172a;">' . esc_html( $name ) . '</h3>';
	if ( '' !== $description ) {
		echo '<p style="margin:0;color:#475569;font-size:.9rem;">' . esc_html( $description ) . '</p>';
	}
	echo '<p style="margin:.45rem 0 0;color:#0c4a6e;font-size:.82rem;font-weight:700;">' . esc_html( (string) $count ) . ' ' . esc_html__( 'Beiträge', 'themisdb-theme' ) . '</p>';
	echo '</article>';
	echo '</section>';

	return ob_get_clean();
}

function themisdb_theme_render_term_card_from_plugin( $html, $payload, $atts ) {
	if ( null !== $html ) {
		return $html;
	}

	$term = ( isset( $payload['term'] ) && is_array( $payload['term'] ) ) ? $payload['term'] : array();
	if ( empty( $term ) ) {
		return '<p class="themisdb-downloads-empty">' . esc_html__( 'Kein Taxonomie-Element verfügbar.', 'themisdb-theme' ) . '</p>';
	}

	$name = isset( $term['name'] ) ? (string) $term['name'] : '';
	$link = isset( $term['link'] ) ? (string) $term['link'] : '';
	$desc = isset( $term['description'] ) ? (string) $term['description'] : '';
	$count = isset( $term['count'] ) ? (int) $term['count'] : 0;
	$icon = isset( $term['icon'] ) ? (string) $term['icon'] : '';

	if ( '' === $name || '' === $link ) {
		return '<p class="themisdb-downloads-empty">' . esc_html__( 'Kein Taxonomie-Element verfügbar.', 'themisdb-theme' ) . '</p>';
	}

	ob_start();
	echo '<article class="themisdb-section-card" style="border:1px solid #e2e8f0;border-radius:1rem;background:#fff;padding:1rem;">';
	if ( '' !== $icon ) {
		echo '<p style="margin:0 0 .35rem;font-size:1.1rem;">' . esc_html( $icon ) . '</p>';
	}
	echo '<h3 style="margin:0 0 .35rem;"><a href="' . esc_url( $link ) . '" style="color:#0c4a6e;text-decoration:none;">' . esc_html( $name ) . '</a></h3>';
	if ( '' !== $desc ) {
		echo '<p style="margin:0 0 .55rem;color:#475569;font-size:.9rem;line-height:1.6;">' . esc_html( $desc ) . '</p>';
	}
	echo '<p style="margin:0;color:#64748b;font-size:.78rem;">' . esc_html( (string) $count ) . ' ' . esc_html__( 'Beiträge', 'themisdb-theme' ) . '</p>';
	echo '</article>';

	return ob_get_clean();
}

function themisdb_theme_render_query_playground_from_plugin( $html, $payload, $atts ) {
	if ( null !== $html ) {
		return $html;
	}

	$client_available = ! empty( $payload['client_available'] );
	$default_query = isset( $atts['default_query'] ) ? (string) $atts['default_query'] : '';

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-plugin-query-playground">';
	echo '<article class="themisdb-section-card" style="border:1px solid #e2e8f0;border-radius:1rem;background:#fff;padding:1rem;">';
	echo '<h3 style="margin:0 0 .5rem;color:#0f172a;">' . esc_html__( 'Query Playground', 'themisdb-theme' ) . '</h3>';
	echo '<p style="margin:0 0 .6rem;color:#475569;font-size:.9rem;">' . esc_html__( 'Interaktive Query-Ausführung mit Theme-gesteuertem Container.', 'themisdb-theme' ) . '</p>';
	if ( $client_available ) {
		echo '<p style="margin:0 0 .6rem;color:#065f46;font-size:.82rem;font-weight:700;">' . esc_html__( 'Client-Verbindung verfügbar', 'themisdb-theme' ) . '</p>';
	} else {
		echo '<p style="margin:0 0 .6rem;color:#92400e;font-size:.82rem;font-weight:700;">' . esc_html__( 'Client-Verbindung nicht verfügbar', 'themisdb-theme' ) . '</p>';
	}
	echo '<pre style="margin:0;background:#f8fafc;border:1px solid #e2e8f0;border-radius:.7rem;padding:.7rem;white-space:pre-wrap;word-break:break-word;">' . esc_html( $default_query ) . '</pre>';
	echo '</article>';
	echo '</section>';

	return ob_get_clean();
}

function themisdb_theme_render_order_flow_from_plugin( $html, $payload, $atts ) {
	if ( null !== $html ) {
		return $html;
	}

	$flow_mode      = isset( $payload['flow_mode'] ) ? (string) $payload['flow_mode'] : 'default';
	$step           = isset( $payload['step'] ) ? (int) $payload['step'] : 1;
	$preset_product = isset( $payload['preset_product'] ) ? (string) $payload['preset_product'] : '';

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-plugin-order-flow">';
	echo '<article class="themisdb-section-card" style="border:1px solid #e2e8f0;border-radius:1rem;background:#fff;padding:1rem;">';
	echo '<h3 style="margin:0 0 .5rem;color:#0f172a;">' . esc_html__( 'Bestellprozess', 'themisdb-theme' ) . '</h3>';
	echo '<p style="margin:0 0 .6rem;color:#475569;font-size:.9rem;">' . esc_html__( 'Theme-gesteuerter Container fuer den mehrstufigen Bestellablauf.', 'themisdb-theme' ) . '</p>';
	echo '<p style="margin:0 0 .4rem;color:#0c4a6e;font-size:.82rem;font-weight:700;">' . esc_html__( 'Modus:', 'themisdb-theme' ) . ' ' . esc_html( $flow_mode ) . '</p>';
	echo '<p style="margin:0;color:#0c4a6e;font-size:.82rem;font-weight:700;">' . esc_html__( 'Startschritt:', 'themisdb-theme' ) . ' ' . esc_html( (string) $step ) . '</p>';
	if ( '' !== $preset_product ) {
		echo '<p style="margin:.35rem 0 0;color:#0c4a6e;font-size:.82rem;font-weight:700;">' . esc_html__( 'Vorgewaehltes Produkt:', 'themisdb-theme' ) . ' ' . esc_html( $preset_product ) . '</p>';
	}
	echo '</article>';
	echo '</section>';

	return ob_get_clean();
}

function themisdb_theme_render_my_orders_from_plugin( $html, $payload, $atts ) {
	if ( null !== $html ) {
		return $html;
	}

	$is_logged_in = ! empty( $payload['is_logged_in'] );
	$message      = isset( $payload['message'] ) ? (string) $payload['message'] : '';
	$orders       = ( isset( $payload['orders'] ) && is_array( $payload['orders'] ) ) ? $payload['orders'] : array();

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-plugin-my-orders">';
	echo '<article class="themisdb-section-card" style="border:1px solid #e2e8f0;border-radius:1rem;background:#fff;padding:1rem;">';
	echo '<h3 style="margin:0 0 .5rem;color:#0f172a;">' . esc_html__( 'Meine Bestellungen', 'themisdb-theme' ) . '</h3>';
	if ( ! $is_logged_in ) {
		echo '<p style="margin:0;color:#475569;font-size:.9rem;">' . esc_html( $message ) . '</p>';
	} else {
		echo '<p style="margin:0;color:#475569;font-size:.9rem;">' . esc_html__( 'Gefundene Bestellungen:', 'themisdb-theme' ) . ' ' . esc_html( (string) count( $orders ) ) . '</p>';
	}
	echo '</article>';
	echo '</section>';

	return ob_get_clean();
}

function themisdb_theme_render_my_contracts_from_plugin( $html, $payload, $atts ) {
	if ( null !== $html ) {
		return $html;
	}

	$is_logged_in = ! empty( $payload['is_logged_in'] );
	$message      = isset( $payload['message'] ) ? (string) $payload['message'] : '';
	$contracts    = ( isset( $payload['contracts'] ) && is_array( $payload['contracts'] ) ) ? $payload['contracts'] : array();

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-plugin-my-contracts">';
	echo '<article class="themisdb-section-card" style="border:1px solid #e2e8f0;border-radius:1rem;background:#fff;padding:1rem;">';
	echo '<h3 style="margin:0 0 .5rem;color:#0f172a;">' . esc_html__( 'Meine Verträge', 'themisdb-theme' ) . '</h3>';
	if ( ! $is_logged_in ) {
		echo '<p style="margin:0;color:#475569;font-size:.9rem;">' . esc_html( $message ) . '</p>';
	} else {
		echo '<p style="margin:0;color:#475569;font-size:.9rem;">' . esc_html__( 'Gefundene Verträge:', 'themisdb-theme' ) . ' ' . esc_html( (string) count( $contracts ) ) . '</p>';
	}
	echo '</article>';
	echo '</section>';

	return ob_get_clean();
}

function themisdb_theme_render_pricing_from_plugin( $html, $payload, $atts ) {
	if ( null !== $html ) {
		return $html;
	}

	$format        = isset( $payload['format'] ) ? (string) $payload['format'] : 'cards';
	$currency      = isset( $payload['currency'] ) ? (string) $payload['currency'] : 'EUR';
	$show_features = ! empty( $payload['show_features'] );

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-plugin-pricing">';
	echo '<article class="themisdb-section-card" style="border:1px solid #e2e8f0;border-radius:1rem;background:#fff;padding:1rem;">';
	echo '<h3 style="margin:0 0 .5rem;color:#0f172a;">' . esc_html__( 'Preismodelle', 'themisdb-theme' ) . '</h3>';
	echo '<p style="margin:0 0 .35rem;color:#0c4a6e;font-size:.82rem;font-weight:700;">' . esc_html__( 'Format:', 'themisdb-theme' ) . ' ' . esc_html( $format ) . '</p>';
	echo '<p style="margin:0;color:#475569;font-size:.86rem;">' . esc_html__( 'Währung:', 'themisdb-theme' ) . ' ' . esc_html( $currency ) . '</p>';
	if ( $show_features ) {
		echo '<p style="margin:.35rem 0 0;color:#475569;font-size:.82rem;">' . esc_html__( 'Featurevergleich ist aktiviert.', 'themisdb-theme' ) . '</p>';
	}
	echo '</article>';
	echo '</section>';

	return ob_get_clean();
}

function themisdb_theme_render_pricing_table_from_plugin( $html, $payload, $atts ) {
	if ( null !== $html ) {
		return $html;
	}

	$currency = isset( $payload['currency'] ) ? (string) $payload['currency'] : 'EUR';

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-plugin-pricing-table">';
	echo '<article class="themisdb-section-card" style="border:1px solid #e2e8f0;border-radius:1rem;background:#fff;padding:1rem;">';
	echo '<h3 style="margin:0 0 .5rem;color:#0f172a;">' . esc_html__( 'Preistabelle', 'themisdb-theme' ) . '</h3>';
	echo '<p style="margin:0;color:#475569;font-size:.86rem;">' . esc_html__( 'Tabellenansicht für Lizenzpreise ist aktiv.', 'themisdb-theme' ) . '</p>';
	echo '<p style="margin:.35rem 0 0;color:#0c4a6e;font-size:.82rem;font-weight:700;">' . esc_html__( 'Währung:', 'themisdb-theme' ) . ' ' . esc_html( $currency ) . '</p>';
	echo '</article>';
	echo '</section>';

	return ob_get_clean();
}

function themisdb_theme_render_product_detail_from_plugin( $html, $payload, $atts ) {
	if ( null !== $html ) {
		return $html;
	}

	$requested_edition = isset( $payload['requested_edition'] ) ? (string) $payload['requested_edition'] : '';
	$products          = ( isset( $payload['products'] ) && is_array( $payload['products'] ) ) ? $payload['products'] : array();
	$modules           = ( isset( $payload['modules'] ) && is_array( $payload['modules'] ) ) ? $payload['modules'] : array();
	$trainings         = ( isset( $payload['trainings'] ) && is_array( $payload['trainings'] ) ) ? $payload['trainings'] : array();
	$order_url         = isset( $payload['order_url'] ) ? (string) $payload['order_url'] : '';

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-plugin-product-detail">';
	echo '<article class="themisdb-section-card" style="border:1px solid #e2e8f0;border-radius:1rem;background:#fff;padding:1rem;">';
	echo '<h3 style="margin:0 0 .5rem;color:#0f172a;">' . esc_html__( 'Produktkonfigurator', 'themisdb-theme' ) . '</h3>';
	echo '<div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.6rem;">';
	echo '<p style="margin:0;color:#475569;font-size:.86rem;">Produkte: <strong>' . esc_html( (string) count( $products ) ) . '</strong></p>';
	echo '<p style="margin:0;color:#475569;font-size:.86rem;">Module: <strong>' . esc_html( (string) count( $modules ) ) . '</strong></p>';
	echo '<p style="margin:0;color:#475569;font-size:.86rem;">Trainings: <strong>' . esc_html( (string) count( $trainings ) ) . '</strong></p>';
	if ( '' !== $requested_edition ) {
		echo '<p style="margin:0;color:#0c4a6e;font-size:.86rem;font-weight:700;">' . esc_html__( 'Vorauswahl:', 'themisdb-theme' ) . ' ' . esc_html( strtoupper( $requested_edition ) ) . '</p>';
	}
	echo '</div>';
	if ( '' !== $order_url ) {
		echo '<p style="margin:.75rem 0 0;"><a href="' . esc_url( $order_url ) . '" class="button button-primary">' . esc_html__( 'Zur Bestellung', 'themisdb-theme' ) . '</a></p>';
	}
	echo '</article>';
	echo '</section>';

	return ob_get_clean();
}

function themisdb_theme_render_shop_from_plugin( $html, $payload, $atts ) {
	if ( null !== $html ) {
		return $html;
	}

	$products          = ( isset( $payload['products'] ) && is_array( $payload['products'] ) ) ? $payload['products'] : array();
	$modules           = ( isset( $payload['modules'] ) && is_array( $payload['modules'] ) ) ? $payload['modules'] : array();
	$trainings         = ( isset( $payload['trainings'] ) && is_array( $payload['trainings'] ) ) ? $payload['trainings'] : array();
	$preferred_edition = isset( $payload['preferred_edition'] ) ? (string) $payload['preferred_edition'] : '';
	$product_url       = isset( $payload['product_url'] ) ? (string) $payload['product_url'] : '';
	$order_url         = isset( $payload['order_url'] ) ? (string) $payload['order_url'] : '';

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-plugin-shop">';
	echo '<article class="themisdb-section-card" style="border:1px solid #e2e8f0;border-radius:1rem;background:#fff;padding:1rem;">';
	echo '<h3 style="margin:0 0 .5rem;color:#0f172a;">' . esc_html__( 'Shop', 'themisdb-theme' ) . '</h3>';
	echo '<div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.6rem;">';
	echo '<p style="margin:0;color:#475569;font-size:.86rem;">Produkte: <strong>' . esc_html( (string) count( $products ) ) . '</strong></p>';
	echo '<p style="margin:0;color:#475569;font-size:.86rem;">Module: <strong>' . esc_html( (string) count( $modules ) ) . '</strong></p>';
	echo '<p style="margin:0;color:#475569;font-size:.86rem;">Trainings: <strong>' . esc_html( (string) count( $trainings ) ) . '</strong></p>';
	if ( '' !== $preferred_edition ) {
		echo '<p style="margin:0;color:#0c4a6e;font-size:.86rem;font-weight:700;">' . esc_html__( 'Empfohlene Edition:', 'themisdb-theme' ) . ' ' . esc_html( strtoupper( $preferred_edition ) ) . '</p>';
	}
	echo '</div>';
	echo '<div style="display:flex;gap:.75rem;flex-wrap:wrap;margin-top:.75rem;">';
	if ( '' !== $product_url ) {
		echo '<a href="' . esc_url( $product_url ) . '" class="button">' . esc_html__( 'Produktseite', 'themisdb-theme' ) . '</a>';
	}
	if ( '' !== $order_url ) {
		echo '<a href="' . esc_url( $order_url ) . '" class="button button-primary">' . esc_html__( 'Direkt bestellen', 'themisdb-theme' ) . '</a>';
	}
	echo '</div>';
	echo '</article>';
	echo '</section>';

	return ob_get_clean();
}

function themisdb_theme_render_shopping_cart_from_plugin( $html, $payload, $atts ) {
	if ( null !== $html ) {
		return $html;
	}

	$checkout_url  = isset( $payload['checkout_url'] ) ? (string) $payload['checkout_url'] : '';
	$show_tax_note = ! empty( $payload['show_tax_note'] );
	$currency      = isset( $payload['currency'] ) ? (string) $payload['currency'] : 'EUR';

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-plugin-shopping-cart">';
	echo '<article class="themisdb-section-card" style="border:1px solid #e2e8f0;border-radius:1rem;background:#fff;padding:1rem;">';
	echo '<h3 style="margin:0 0 .5rem;color:#0f172a;">' . esc_html__( 'Warenkorb', 'themisdb-theme' ) . '</h3>';
	echo '<p style="margin:0 0 .4rem;color:#475569;font-size:.9rem;">' . esc_html__( 'Theme-gesteuerter Container fuer Checkout-Positionen und den Uebergang in den Bestellprozess.', 'themisdb-theme' ) . '</p>';
	echo '<p style="margin:0;color:#0c4a6e;font-size:.82rem;font-weight:700;">' . esc_html__( 'Waehrung:', 'themisdb-theme' ) . ' ' . esc_html( $currency ) . '</p>';
	if ( $show_tax_note ) {
		echo '<p style="margin:.35rem 0 0;color:#475569;font-size:.82rem;">' . esc_html__( 'Steuerhinweis ist aktiv.', 'themisdb-theme' ) . '</p>';
	}
	if ( '' !== $checkout_url ) {
		echo '<p style="margin:.75rem 0 0;"><a href="' . esc_url( $checkout_url ) . '" class="button button-primary">' . esc_html__( 'Zum Checkout', 'themisdb-theme' ) . '</a></p>';
	}
	echo '</article>';
	echo '</section>';

	return ob_get_clean();
}

function themisdb_theme_render_login_from_plugin( $html, $payload, $atts ) {
	if ( null !== $html ) {
		return $html;
	}

	$is_logged_in = ! empty( $payload['is_logged_in'] );
	$login_url    = isset( $payload['login_url'] ) ? (string) $payload['login_url'] : wp_login_url();
	$logout_url   = isset( $payload['logout_url'] ) ? (string) $payload['logout_url'] : wp_logout_url();

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-plugin-login">';
	echo '<article class="themisdb-section-card" style="border:1px solid #e2e8f0;border-radius:1rem;background:#fff;padding:1rem;">';
	echo '<h3 style="margin:0 0 .5rem;color:#0f172a;">' . esc_html__( 'Kontozugang', 'themisdb-theme' ) . '</h3>';

	if ( $is_logged_in ) {
		echo '<p style="margin:0 0 .6rem;color:#065f46;font-size:.86rem;font-weight:700;">' . esc_html__( 'Sie sind angemeldet.', 'themisdb-theme' ) . '</p>';
		echo '<a href="' . esc_url( $logout_url ) . '" class="button">' . esc_html__( 'Abmelden', 'themisdb-theme' ) . '</a>';
	} else {
		echo '<p style="margin:0 0 .6rem;color:#475569;font-size:.9rem;">' . esc_html__( 'Melden Sie sich an, um auf Ihre Lizenzen und Bestellungen zuzugreifen.', 'themisdb-theme' ) . '</p>';
		echo '<a href="' . esc_url( $login_url ) . '" class="button button-primary">' . esc_html__( 'Anmelden', 'themisdb-theme' ) . '</a>';
	}

	echo '</article>';
	echo '</section>';

	return ob_get_clean();
}

function themisdb_theme_render_license_upload_from_plugin( $html, $payload, $atts ) {
	if ( null !== $html ) {
		return $html;
	}

	$has_license = ! empty( $payload['has_license'] );
	$license     = ( isset( $payload['license'] ) && is_array( $payload['license'] ) ) ? $payload['license'] : array();
	$fallback    = isset( $payload['fallback'] ) ? (string) $payload['fallback'] : '';

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-plugin-license-upload">';
	echo '<article class="themisdb-section-card" style="border:1px solid #e2e8f0;border-radius:1rem;background:#fff;padding:1rem;">';
	echo '<h3 style="margin:0 0 .5rem;color:#0f172a;">' . esc_html__( 'Lizenzzugang', 'themisdb-theme' ) . '</h3>';

	if ( $has_license ) {
		$status = isset( $license['license_status'] ) ? (string) $license['license_status'] : 'unknown';
		echo '<p style="margin:0 0 .4rem;color:#0c4a6e;font-size:.86rem;font-weight:700;">' . esc_html__( 'Lizenzstatus:', 'themisdb-theme' ) . ' ' . esc_html( $status ) . '</p>';
		if ( ! empty( $license['product_edition'] ) ) {
			echo '<p style="margin:0;color:#475569;font-size:.9rem;">' . esc_html__( 'Edition:', 'themisdb-theme' ) . ' ' . esc_html( strtoupper( (string) $license['product_edition'] ) ) . '</p>';
		}
	} elseif ( 'themisdb_login' === $fallback ) {
		echo '<p style="margin:0;color:#475569;font-size:.9rem;">' . esc_html__( 'Bitte melden Sie sich an, um Ihre Lizenzdaten zu laden.', 'themisdb-theme' ) . '</p>';
	} else {
		echo '<p style="margin:0;color:#475569;font-size:.9rem;">' . esc_html__( 'Es liegen noch keine Lizenzdetails vor.', 'themisdb-theme' ) . '</p>';
	}

	echo '</article>';
	echo '</section>';

	return ob_get_clean();
}

function themisdb_theme_render_license_portal_from_plugin( $html, $payload, $atts ) {
	if ( null !== $html ) {
		return $html;
	}

	$mode         = isset( $payload['mode'] ) ? (string) $payload['mode'] : 'full';
	$is_logged_in = ! empty( $payload['is_logged_in'] );
	$licenses     = ( isset( $payload['licenses'] ) && is_array( $payload['licenses'] ) ) ? $payload['licenses'] : array();
	$license      = ( isset( $payload['license'] ) && is_array( $payload['license'] ) ) ? $payload['license'] : array();
	$login_url    = isset( $payload['login_url'] ) ? (string) $payload['login_url'] : wp_login_url();

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-plugin-license-portal">';
	echo '<article class="themisdb-section-card" style="border:1px solid #e2e8f0;border-radius:1rem;background:#fff;padding:1rem;">';
	echo '<h3 style="margin:0 0 .5rem;color:#0f172a;">' . esc_html__( 'License Portal', 'themisdb-theme' ) . '</h3>';

	if ( ! $is_logged_in ) {
		echo '<p style="margin:0 0 .6rem;color:#475569;font-size:.9rem;">' . esc_html__( 'Bitte anmelden, um Lizenzen anzuzeigen.', 'themisdb-theme' ) . '</p>';
		echo '<a href="' . esc_url( $login_url ) . '" class="button button-primary">' . esc_html__( 'Zum Login', 'themisdb-theme' ) . '</a>';
	} elseif ( 'download' === $mode && ! empty( $license ) ) {
		$key    = isset( $license['license_key'] ) ? (string) $license['license_key'] : '';
		$status = isset( $license['license_status'] ) ? (string) $license['license_status'] : 'unknown';
		echo '<p style="margin:0 0 .4rem;color:#475569;font-size:.9rem;">' . esc_html__( 'Einzellizenz-Downloadansicht aktiv.', 'themisdb-theme' ) . '</p>';
		if ( '' !== $key ) {
			echo '<p style="margin:0 0 .35rem;color:#0c4a6e;font-size:.82rem;font-weight:700;">' . esc_html__( 'Lizenz:', 'themisdb-theme' ) . ' ' . esc_html( $key ) . '</p>';
		}
		echo '<p style="margin:0;color:#0c4a6e;font-size:.82rem;font-weight:700;">' . esc_html__( 'Status:', 'themisdb-theme' ) . ' ' . esc_html( $status ) . '</p>';
	} else {
		echo '<p style="margin:0;color:#475569;font-size:.9rem;">' . esc_html__( 'Gefundene Lizenzen:', 'themisdb-theme' ) . ' ' . esc_html( (string) count( $licenses ) ) . '</p>';
	}

	echo '</article>';
	echo '</section>';

	return ob_get_clean();
}

function themisdb_theme_render_affiliate_dashboard_from_plugin( $html, $payload, $atts ) {
	if ( null !== $html ) {
		return $html;
	}

	$is_logged_in = ! empty( $payload['is_logged_in'] );
	$ref_link     = isset( $payload['ref_link'] ) ? (string) $payload['ref_link'] : '';
	$pending      = isset( $payload['pending_total'] ) ? (float) $payload['pending_total'] : 0.0;

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-plugin-affiliate-dashboard">';
	echo '<article class="themisdb-section-card" style="border:1px solid #e2e8f0;border-radius:1rem;background:#fff;padding:1rem;">';
	echo '<h3 style="margin:0 0 .5rem;color:#0f172a;">' . esc_html__( 'Affiliate Dashboard', 'themisdb-theme' ) . '</h3>';

	if ( ! $is_logged_in ) {
		echo '<p style="margin:0;color:#475569;font-size:.9rem;">' . esc_html__( 'Bitte anmelden, um Affiliate-Daten zu sehen.', 'themisdb-theme' ) . '</p>';
	} else {
		echo '<p style="margin:0 0 .45rem;color:#0c4a6e;font-size:.82rem;font-weight:700;">' . esc_html__( 'Offene Provisionen:', 'themisdb-theme' ) . ' ' . esc_html( number_format_i18n( $pending, 2 ) ) . ' EUR</p>';
		if ( '' !== $ref_link ) {
			echo '<p style="margin:0;color:#475569;font-size:.82rem;word-break:break-word;">' . esc_html( $ref_link ) . '</p>';
		}
	}

	echo '</article>';
	echo '</section>';

	return ob_get_clean();
}

function themisdb_theme_render_b2b_portal_from_plugin( $html, $payload, $atts ) {
	if ( null !== $html ) {
		return $html;
	}

	$is_logged_in = ! empty( $payload['is_logged_in'] );
	$nonce        = isset( $payload['nonce'] ) ? (string) $payload['nonce'] : '';

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-plugin-b2b-portal">';
	echo '<article class="themisdb-section-card" style="border:1px solid #e2e8f0;border-radius:1rem;background:#fff;padding:1rem;">';
	echo '<h3 style="margin:0 0 .5rem;color:#0f172a;">' . esc_html__( 'B2B Portal', 'themisdb-theme' ) . '</h3>';

	if ( ! $is_logged_in ) {
		echo '<p style="margin:0;color:#475569;font-size:.9rem;">' . esc_html__( 'Bitte anmelden, um das B2B-Portal zu nutzen.', 'themisdb-theme' ) . '</p>';
	} else {
		echo '<p style="margin:0 0 .45rem;color:#475569;font-size:.9rem;">' . esc_html__( 'B2B-Funktionen sind aktiv (Abteilungen, Bulk-Upload, Pricing, Procurement).', 'themisdb-theme' ) . '</p>';
		if ( '' !== $nonce ) {
			echo '<p style="margin:0;color:#64748b;font-size:.75rem;word-break:break-word;">Nonce: ' . esc_html( $nonce ) . '</p>';
		}
	}

	echo '</article>';
	echo '</section>';

	return ob_get_clean();
}

function themisdb_theme_render_advanced_reporting_from_plugin( $html, $payload, $atts ) {
	if ( null !== $html ) {
		return $html;
	}

	$cohort  = ( isset( $payload['cohort'] ) && is_array( $payload['cohort'] ) ) ? $payload['cohort'] : array();
	$ltv_cac = ( isset( $payload['ltv_cac'] ) && is_array( $payload['ltv_cac'] ) ) ? $payload['ltv_cac'] : array();
	$churn   = ( isset( $payload['churn'] ) && is_array( $payload['churn'] ) ) ? $payload['churn'] : array();
	$mix     = ( isset( $payload['mix'] ) && is_array( $payload['mix'] ) ) ? $payload['mix'] : array();

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-plugin-advanced-reporting">';
	echo '<article class="themisdb-section-card" style="border:1px solid #e2e8f0;border-radius:1rem;background:#fff;padding:1rem;">';
	echo '<h3 style="margin:0 0 .5rem;color:#0f172a;">' . esc_html__( 'Advanced Reporting', 'themisdb-theme' ) . '</h3>';
	echo '<div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.6rem;">';
	echo '<p style="margin:0;color:#475569;font-size:.86rem;">Cohorts: <strong>' . esc_html( (string) count( $cohort ) ) . '</strong></p>';
	echo '<p style="margin:0;color:#475569;font-size:.86rem;">LTV/CAC: <strong>' . esc_html( (string) count( $ltv_cac ) ) . '</strong></p>';
	echo '<p style="margin:0;color:#475569;font-size:.86rem;">Churn: <strong>' . esc_html( (string) count( $churn ) ) . '</strong></p>';
	echo '<p style="margin:0;color:#475569;font-size:.86rem;">Mix: <strong>' . esc_html( (string) count( $mix ) ) . '</strong></p>';
	echo '</div>';
	echo '</article>';
	echo '</section>';

	return ob_get_clean();
}

/* =====================================================================
   5. SECTION DATA MODEL + DYNAMISCHE SEKTIONEN
   Einheitliche Datenbasis für Frontpage-Abschnitte über Kategorie-Slugs.
   ===================================================================== */

function themisdb_get_section_definitions() {
	return array(
		'zahlen'    => __( 'Zahlen & Fakten', 'themisdb-theme' ),
		'evolution' => __( 'Evolution', 'themisdb-theme' ),
		'module'    => __( 'Module', 'themisdb-theme' ),
		'workflow'  => __( 'Workflow', 'themisdb-theme' ),
		'legal'     => __( 'Recht', 'themisdb-theme' ),
		'digital'   => __( 'Digitalisierung', 'themisdb-theme' ),
		'aktuelles' => __( 'Aktuelles', 'themisdb-theme' ),
		'faq'       => __( 'FAQ', 'themisdb-theme' ),
	);
}

add_action( 'init', 'themisdb_register_section_terms', 5 );
function themisdb_register_section_terms() {
	foreach ( themisdb_get_section_definitions() as $slug => $name ) {
		$term = term_exists( $slug, 'category' );
		if ( ! $term ) {
			wp_insert_term( $name, 'category', array( 'slug' => $slug ) );
		}
	}
}

function themisdb_get_contact_email() {
	return sanitize_email( get_theme_mod( 'themisdb_contact_email', get_option( 'admin_email' ) ) );
}

function themisdb_get_footer_text( $mod_name, $default = '' ) {
	$value = get_theme_mod( $mod_name, $default );

	if ( ! is_string( $value ) ) {
		return '';
	}

	return sanitize_text_field( $value );
}

function themisdb_get_footer_description() {
	$default = get_bloginfo( 'description' );
	$value   = get_theme_mod( 'themisdb_footer_description', $default );

	if ( ! is_string( $value ) ) {
		return '';
	}

	return wp_kses_post( wpautop( $value ) );
}

function themisdb_get_footer_menu_markup( $location ) {
	if ( has_nav_menu( $location ) ) {
		return wp_nav_menu( array(
			'theme_location' => $location,
			'container'      => false,
			'echo'           => false,
			'depth'          => 1,
			'fallback_cb'    => false,
			'items_wrap'     => '<ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:0.75rem;">%3$s</ul>',
			'link_before'    => '<span style="font-size:0.875rem;color:#94a3b8;text-decoration:none;transition:color 0.2s">',
			'link_after'     => '</span>',
		) );
	}

	if ( 'footer-1' !== $location ) {
		return '';
	}

	$pages = get_pages( array(
		'sort_column' => 'menu_order,post_title',
		'parent'      => 0,
	) );

	if ( empty( $pages ) ) {
		return '';
	}

	$items = array();
	foreach ( array_slice( $pages, 0, 6 ) as $page ) {
		$items[] = sprintf(
			'<li><a href="%1$s" style="font-size:0.875rem;color:#94a3b8;text-decoration:none;transition:color 0.2s">%2$s</a></li>',
			esc_url( get_permalink( $page->ID ) ),
			esc_html( get_the_title( $page->ID ) )
		);
	}

	return '<ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:0.75rem;">' . implode( '', $items ) . '</ul>';
}

function themisdb_get_footer_notice_markup() {
	$notice = themisdb_get_footer_text( 'themisdb_footer_notice', '' );

	if ( '' === $notice ) {
		return '';
	}

	return sprintf(
		'<div style="font-size:0.625rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:#334155">%s</div>',
		esc_html( $notice )
	);
}

function themisdb_get_footer_copyright_markup() {
	return sprintf(
		'&copy; %1$s %2$s',
		esc_html( gmdate( 'Y' ) ),
		esc_html( get_bloginfo( 'name' ) )
	);
}

function themisdb_get_legal_page_id( $mod_name, $fallback_slug = '' ) {
	$page_id = absint( get_theme_mod( $mod_name, 0 ) );

	if ( $page_id > 0 && get_post( $page_id ) instanceof WP_Post ) {
		return $page_id;
	}

	if ( '' !== $fallback_slug ) {
		$page = get_page_by_path( sanitize_title( $fallback_slug ) );
		if ( $page instanceof WP_Post ) {
			return (int) $page->ID;
		}
	}

	return 0;
}

function themisdb_get_legal_page_title( $mod_name, $default_title, $fallback_slug = '' ) {
	$page_id = themisdb_get_legal_page_id( $mod_name, $fallback_slug );

	if ( $page_id > 0 ) {
		$title = get_the_title( $page_id );
		if ( is_string( $title ) && '' !== $title ) {
			return $title;
		}
	}

	return $default_title;
}

function themisdb_get_legal_page_content( $mod_name, $fallback_slug = '' ) {
	$page_id = themisdb_get_legal_page_id( $mod_name, $fallback_slug );

	if ( $page_id <= 0 ) {
		return '';
	}

	$page = get_post( $page_id );
	if ( ! ( $page instanceof WP_Post ) ) {
		return '';
	}

	return apply_filters( 'the_content', (string) $page->post_content );
}

function themisdb_bool_atts( $value, $default = true ) {
	if ( null === $value || '' === $value ) {
		return (bool) $default;
	}
	return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
}

function themisdb_query_section_posts( $section_slug, $per_page = 4, $order = 'DESC', $orderby = 'date' ) {
	$defs = themisdb_get_section_definitions();
	if ( ! isset( $defs[ $section_slug ] ) ) {
		return new WP_Query( array( 'post__in' => array( 0 ) ) );
	}

	return new WP_Query( array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => max( 1, min( 50, absint( $per_page ) ) ),
		'category_name'       => sanitize_title( $section_slug ),
		'orderby'             => sanitize_key( $orderby ),
		'order'               => ( 'ASC' === strtoupper( $order ) ) ? 'ASC' : 'DESC',
		'ignore_sticky_posts' => true,
	) );
}

function themisdb_get_section_term( $section_slug ) {
	$section_slug = sanitize_title( $section_slug );
	if ( '' === $section_slug ) {
		return null;
	}

	$term = get_term_by( 'slug', $section_slug, 'category' );
	if ( $term && ! is_wp_error( $term ) ) {
		return $term;
	}

	return null;
}

function themisdb_render_section_header( $section_slug, $show_description = true ) {
	$term = themisdb_get_section_term( $section_slug );
	if ( ! $term ) {
		return '';
	}

	ob_start();
	echo '<header class="themisdb-section-header" style="margin-bottom:1rem;">';
	echo '<h2 class="themisdb-section-title" style="margin:0 0 .35rem;color:#0f172a;font-size:clamp(1.35rem,3vw,2rem);line-height:1.2;">' . esc_html( $term->name ) . '</h2>';
	if ( $show_description && ! empty( $term->description ) ) {
		echo '<p class="themisdb-section-description" style="margin:0;color:#64748b;line-height:1.65;">' . esc_html( $term->description ) . '</p>';
	}
	echo '</header>';

	return ob_get_clean();
}

function themisdb_render_section_empty_state( $section_slug ) {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return '';
	}

	$defs = themisdb_get_section_definitions();
	if ( ! isset( $defs[ $section_slug ] ) ) {
		return '';
	}

	return '<div class="themisdb-empty-state" role="status">' .
		'<div class="themisdb-empty-state-icon" aria-hidden="true">◌</div>' .
		'<div class="themisdb-empty-state-body">' .
			'<h3 class="themisdb-empty-state-title">' . esc_html__( 'Noch keine Inhalte vorhanden', 'themisdb-theme' ) . '</h3>' .
			'<p class="themisdb-empty-state-text">' . esc_html( sprintf( __( 'In der Kategorie "%s" wurden noch keine passenden Beiträge gefunden.', 'themisdb-theme' ), $section_slug ) ) . '</p>' .
		'</div>' .
	'</div>';
}

function themisdb_get_seed_blueprint() {
	return array(
		'zahlen'    => array( 'count' => 4, 'prefix' => __( 'Kennzahl', 'themisdb-theme' ) ),
		'evolution' => array( 'count' => 1, 'prefix' => __( 'Meilenstein', 'themisdb-theme' ) ),
		'module'    => array( 'count' => 6, 'prefix' => __( 'Modul', 'themisdb-theme' ) ),
		'workflow'  => array( 'count' => 6, 'prefix' => __( 'Prozessschritt', 'themisdb-theme' ) ),
		'legal'     => array( 'count' => 3, 'prefix' => __( 'Rechtsgrundlage', 'themisdb-theme' ) ),
		'digital'   => array( 'count' => 4, 'prefix' => __( 'Digitalbaustein', 'themisdb-theme' ) ),
		'aktuelles' => array( 'count' => 4, 'prefix' => __( 'Changelog', 'themisdb-theme' ) ),
		'faq'       => array( 'count' => 6, 'prefix' => __( 'FAQ', 'themisdb-theme' ) ),
	);
}

function themisdb_seed_meta_key() {
	return '_themisdb_seeded_frontpage';
}

/* =====================================================================
   5.1 GUTENBERG-BLOCKS FUR DYNAMISCHE FRONTPAGE-SEKTIONEN
   Galerie, 3-Row-Grid und Button-Box-Grid mit Inspector-Controls.
   ===================================================================== */

add_action( 'init', 'themisdb_register_dynamic_blocks' );
function themisdb_register_dynamic_blocks() {
	wp_register_script(
		'themisdb-dynamic-section-blocks',
		THEMISDB_THEME_URI . '/assets/js/section-blocks.js',
		array( 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-components', 'wp-block-editor', 'wp-server-side-render', 'wp-data', 'wp-core-data' ),
		THEMISDB_THEME_VERSION,
		true
	);

	register_block_type( 'themisdb/gallery-grid', array(
		'editor_script'   => 'themisdb-dynamic-section-blocks',
		'render_callback' => 'themisdb_render_gallery_grid_block',
		'attributes'      => array(
			'section' => array(
				'type'    => 'string',
				'default' => 'digital',
			),
			'perPage' => array(
				'type'    => 'number',
				'default' => 6,
			),
			'columns' => array(
				'type'    => 'number',
				'default' => 3,
			),
			'showHeader' => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'showDescription' => array(
				'type'    => 'boolean',
				'default' => true,
			),
		),
	) );

	register_block_type( 'themisdb/three-row-grid', array(
		'editor_script'   => 'themisdb-dynamic-section-blocks',
		'render_callback' => 'themisdb_render_three_row_grid_block',
		'attributes'      => array(
			'section' => array(
				'type'    => 'string',
				'default' => 'module',
			),
			'perPage' => array(
				'type'    => 'number',
				'default' => 6,
			),
			'excerptWords' => array(
				'type'    => 'number',
				'default' => 20,
			),
			'showHeader' => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'showDescription' => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'showImage' => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'showDate' => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'showExcerpt' => array(
				'type'    => 'boolean',
				'default' => true,
			),
		),
	) );

	register_block_type( 'themisdb/button-box-grid', array(
		'editor_script'   => 'themisdb-dynamic-section-blocks',
		'render_callback' => 'themisdb_render_button_box_grid_block',
		'attributes'      => array(
			'section' => array(
				'type'    => 'string',
				'default' => 'workflow',
			),
			'perPage' => array(
				'type'    => 'number',
				'default' => 6,
			),
			'columns' => array(
				'type'    => 'number',
				'default' => 3,
			),
			'showHeader' => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'showDescription' => array(
				'type'    => 'boolean',
				'default' => true,
			),
		),
	) );

	register_block_type( 'themisdb/section-cards', array(
		'editor_script'   => 'themisdb-dynamic-section-blocks',
		'render_callback' => 'themisdb_render_section_cards_block',
		'attributes'      => array(
			'section' => array(
				'type'    => 'string',
				'default' => 'zahlen',
			),
			'perPage' => array(
				'type'    => 'number',
				'default' => 4,
			),
			'columns' => array(
				'type'    => 'number',
				'default' => 4,
			),
			'excerptWords' => array(
				'type'    => 'number',
				'default' => 20,
			),
			'showHeader' => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'showDescription' => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'showImage' => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'showDate' => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'showExcerpt' => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'order' => array(
				'type'    => 'string',
				'default' => 'DESC',
			),
			'orderby' => array(
				'type'    => 'string',
				'default' => 'date',
			),
		),
	) );

	register_block_type( 'themisdb/section-feature', array(
		'editor_script'   => 'themisdb-dynamic-section-blocks',
		'render_callback' => 'themisdb_render_section_feature_block',
		'attributes'      => array(
			'section' => array(
				'type'    => 'string',
				'default' => 'evolution',
			),
			'excerptWords' => array(
				'type'    => 'number',
				'default' => 36,
			),
			'showHeader' => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'showDescription' => array(
				'type'    => 'boolean',
				'default' => true,
			),
		),
	) );

	register_block_type( 'themisdb/section-timeline', array(
		'editor_script'   => 'themisdb-dynamic-section-blocks',
		'render_callback' => 'themisdb_render_section_timeline_block',
		'attributes'      => array(
			'section' => array(
				'type'    => 'string',
				'default' => 'aktuelles',
			),
			'perPage' => array(
				'type'    => 'number',
				'default' => 4,
			),
			'showHeader' => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'showDescription' => array(
				'type'    => 'boolean',
				'default' => true,
			),
		),
	) );

	register_block_type( 'themisdb/section-faq', array(
		'editor_script'   => 'themisdb-dynamic-section-blocks',
		'render_callback' => 'themisdb_render_section_faq_block',
		'attributes'      => array(
			'section' => array(
				'type'    => 'string',
				'default' => 'faq',
			),
			'perPage' => array(
				'type'    => 'number',
				'default' => 6,
			),
			'showHeader' => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'showDescription' => array(
				'type'    => 'boolean',
				'default' => true,
			),
		),
	) );

	register_block_type( 'themisdb/contact-form', array(
		'editor_script'   => 'themisdb-dynamic-section-blocks',
		'render_callback' => 'themisdb_render_contact_form_block',
		'attributes'      => array(),
	) );

	register_block_type( 'themisdb/state-grid', array(
		'editor_script'   => 'themisdb-dynamic-section-blocks',
		'render_callback' => 'themisdb_render_state_grid_block',
		'attributes'      => array(
			'showHeader' => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'showDescription' => array(
				'type'    => 'boolean',
				'default' => true,
			),
		),
	) );

	$registry = WP_Block_Type_Registry::get_instance();
	$plugin_slider_available = function_exists( 'themisdb_fs_shortcode' );

	// Guardrail: Plugin-Block nie im Theme überschreiben.
	// Fallback-Block nur registrieren, wenn kein Plugin-Block vorhanden ist.
	if ( ! $registry->is_registered( 'themisdb/front-slider' ) ) {
		register_block_type( 'themisdb/front-slider', array(
			'editor_script'   => 'themisdb-dynamic-section-blocks',
			'render_callback' => 'themisdb_render_front_slider_block',
			'attributes'      => array(
				'posts' => array(
					'type'    => 'number',
					'default' => 5,
				),
				'interval' => array(
					'type'    => 'number',
					'default' => (int) get_theme_mod( 'themisdb_slider_interval', 5000 ),
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
				'overlay' => array(
					'type'    => 'string',
					'default' => 'normal',
				),
			),
		) );
	}
}

function themisdb_get_state_grid_items() {
	if ( ! get_theme_mod( 'themisdb_show_state_grid', false ) ) {
		return array();
	}

	$items = array();

	return apply_filters( 'themisdb_theme_state_grid_items', $items );
}

function themisdb_front_slider_defaults() {
	$plugin_opts = (array) get_option( 'themisdb_fs_options', array() );

	$defaults = array(
		'posts'     => isset( $plugin_opts['posts_count'] ) ? (int) $plugin_opts['posts_count'] : 5,
		'interval'  => isset( $plugin_opts['interval'] ) ? (int) $plugin_opts['interval'] : (int) get_theme_mod( 'themisdb_slider_interval', 5000 ),
		'category'  => isset( $plugin_opts['category'] ) ? sanitize_text_field( (string) $plugin_opts['category'] ) : '',
		'excerpt'   => array_key_exists( 'show_excerpt', $plugin_opts ) ? (bool) $plugin_opts['show_excerpt'] : true,
		'date'      => array_key_exists( 'show_date', $plugin_opts ) ? (bool) $plugin_opts['show_date'] : true,
		'cat_label' => array_key_exists( 'show_category', $plugin_opts ) ? (bool) $plugin_opts['show_category'] : true,
		'autoplay'  => array_key_exists( 'autoplay', $plugin_opts ) ? (bool) $plugin_opts['autoplay'] : true,
		'overlay'   => 'normal',
	);

	return apply_filters( 'themisdb_theme_front_slider_defaults', $defaults, $plugin_opts );
}

function themisdb_theme_render_plugin_front_slider( $atts ) {
	if ( ! function_exists( 'themisdb_fs_shortcode' ) ) {
		return null;
	}

	if ( wp_style_is( 'themisdb-front-slider-css', 'registered' ) ) {
		wp_enqueue_style( 'themisdb-front-slider-css' );
	}

	$plugin_atts = array(
		'posts'         => isset( $atts['posts'] ) ? $atts['posts'] : null,
		'interval'      => isset( $atts['interval'] ) ? $atts['interval'] : null,
		'category'      => isset( $atts['category'] ) ? $atts['category'] : null,
		'excerpt'       => isset( $atts['excerpt'] ) ? $atts['excerpt'] : null,
		'date'          => isset( $atts['date'] ) ? $atts['date'] : null,
		'cat_label'     => isset( $atts['cat_label'] ) ? $atts['cat_label'] : null,
		'autoplay'      => isset( $atts['autoplay'] ) ? $atts['autoplay'] : null,
		'layout_preset' => 'standard',
	);

	$plugin_atts = array_filter(
		$plugin_atts,
		static function( $value ) {
			return null !== $value;
		}
	);

	return themisdb_fs_shortcode( $plugin_atts );
}

function themisdb_render_front_slider_block( $attributes ) {
	if ( ! is_array( $attributes ) ) {
		$attributes = array();
	}

	$atts = array(
		'posts'     => isset( $attributes['posts'] ) ? $attributes['posts'] : null,
		'interval'  => isset( $attributes['interval'] ) ? $attributes['interval'] : null,
		'category'  => isset( $attributes['category'] ) ? $attributes['category'] : null,
		'excerpt'   => isset( $attributes['excerpt'] ) ? $attributes['excerpt'] : null,
		'date'      => isset( $attributes['date'] ) ? $attributes['date'] : null,
		'cat_label' => isset( $attributes['cat_label'] ) ? $attributes['cat_label'] : null,
		'autoplay'  => isset( $attributes['autoplay'] ) ? $attributes['autoplay'] : null,
		'overlay'   => isset( $attributes['overlay'] ) ? $attributes['overlay'] : null,
	);

	$atts = array_filter(
		$atts,
		static function( $value ) {
			return null !== $value;
		}
	);

	$plugin_html = themisdb_theme_render_plugin_front_slider( $atts );
	if ( null !== $plugin_html ) {
		return $plugin_html;
	}

	return themisdb_front_slider_shortcode( $atts );
}

function themisdb_front_slider_shortcode( $atts ) {
	$plugin_html = themisdb_theme_render_plugin_front_slider( (array) $atts );
	if ( null !== $plugin_html ) {
		return $plugin_html;
	}

	$defaults = themisdb_front_slider_defaults();

	$atts = shortcode_atts( $defaults, $atts, 'themisdb_front_slider' );
	$atts = apply_filters( 'themisdb_theme_front_slider_atts', $atts, $defaults );

	$posts_count   = max( 1, min( 20, (int) $atts['posts'] ) );
	$interval      = max( 1000, min( 30000, (int) $atts['interval'] ) );
	$category      = sanitize_text_field( (string) $atts['category'] );
	$show_excerpt  = filter_var( $atts['excerpt'], FILTER_VALIDATE_BOOLEAN );
	$show_date     = filter_var( $atts['date'], FILTER_VALIDATE_BOOLEAN );
	$show_category = filter_var( $atts['cat_label'], FILTER_VALIDATE_BOOLEAN );
	$autoplay      = filter_var( $atts['autoplay'], FILTER_VALIDATE_BOOLEAN );
	$overlay       = sanitize_key( (string) $atts['overlay'] );
	$overlay       = in_array( $overlay, array( 'normal', 'strong' ), true ) ? $overlay : 'normal';

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

	$query_args = apply_filters( 'themisdb_theme_front_slider_query_args', $query_args, $atts );

	$query = new WP_Query( $query_args );
	if ( ! $query->have_posts() ) {
		return '<p class="themisdb-fs-no-posts">' . esc_html__( 'Keine Artikel gefunden.', 'themisdb-theme' ) . '</p>';
	}

	$slider_id = 'themisdb-fs-' . wp_unique_id();

	ob_start();
	?>
	<div class="themisdb-fs-wrapper themisdb-hero-slider themisdb-hero-slider--fallback"
		id="<?php echo esc_attr( $slider_id ); ?>"
		data-interval="<?php echo esc_attr( $interval ); ?>"
		data-autoplay="<?php echo $autoplay ? '1' : '0'; ?>"
		data-overlay="<?php echo esc_attr( $overlay ); ?>"
		role="region"
		aria-label="<?php esc_attr_e( 'Neueste Artikel', 'themisdb-theme' ); ?>"
		aria-roledescription="carousel">
		<div class="themisdb-fs-track-outer">
			<div class="themisdb-fs-track" aria-live="<?php echo $autoplay ? 'off' : 'polite'; ?>">
				<?php
				$slide_index = 0;
				while ( $query->have_posts() ) :
					$query->the_post();
					$post_id    = get_the_ID();
					$categories = get_the_category();
					$first_cat  = ! empty( $categories ) ? $categories[0] : null;
					$has_thumb  = has_post_thumbnail();
					$thumb_url  = $has_thumb ? get_the_post_thumbnail_url( $post_id, 'large' ) : '';
					$is_active  = ( 0 === $slide_index );
					?>
					<div class="themisdb-fs-slide<?php echo $is_active ? ' is-active' : ''; ?>"
						role="group"
						aria-roledescription="slide"
						aria-label="<?php echo esc_attr( sprintf( '%d / %d', $slide_index + 1, $query->post_count ) ); ?>"
						aria-hidden="<?php echo $is_active ? 'false' : 'true'; ?>"
						style="<?php echo $has_thumb ? 'background-image:url(' . esc_url( $thumb_url ) . ');' : ''; ?>">
						<div class="themisdb-fs-slide-overlay"></div>
						<div class="themisdb-fs-slide-content themisdb-slide-content">
							<?php if ( $show_category && $first_cat ) : ?>
								<a class="themisdb-fs-category" href="<?php echo esc_url( get_category_link( $first_cat->term_id ) ); ?>" tabindex="<?php echo $is_active ? '0' : '-1'; ?>"><?php echo esc_html( $first_cat->name ); ?></a>
							<?php endif; ?>
							<h2 class="themisdb-fs-title" style="margin-bottom:0.75rem;">
								<a href="<?php the_permalink(); ?>" tabindex="<?php echo $is_active ? '0' : '-1'; ?>"><?php the_title(); ?></a>
							</h2>
							<?php if ( $show_excerpt ) : ?>
								<p class="themisdb-fs-excerpt"><?php echo wp_kses_post( wp_trim_words( get_the_excerpt(), 20, '…' ) ); ?></p>
							<?php endif; ?>
							<div class="themisdb-fs-meta">
								<?php if ( $show_date ) : ?>
									<time class="themisdb-fs-date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
								<?php endif; ?>
								<a class="themisdb-fs-readmore" href="<?php the_permalink(); ?>" tabindex="<?php echo $is_active ? '0' : '-1'; ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Weiterlesen: %s', 'themisdb-theme' ), get_the_title() ) ); ?>"><?php esc_html_e( 'Weiterlesen →', 'themisdb-theme' ); ?></a>
							</div>
						</div>
					</div>
					<?php
					$slide_index++;
				endwhile;
				?>
			</div>
		</div>
		<button class="themisdb-fs-btn themisdb-fs-prev themisdb-slider-arrow themisdb-slider-arrow-prev" aria-label="<?php esc_attr_e( 'Vorheriger Artikel', 'themisdb-theme' ); ?>" aria-controls="<?php echo esc_attr( $slider_id ); ?>">
			<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><polyline points="15 18 9 12 15 6"></polyline></svg>
		</button>
		<button class="themisdb-fs-btn themisdb-fs-next themisdb-slider-arrow themisdb-slider-arrow-next" aria-label="<?php esc_attr_e( 'Nächster Artikel', 'themisdb-theme' ); ?>" aria-controls="<?php echo esc_attr( $slider_id ); ?>">
			<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><polyline points="9 18 15 12 9 6"></polyline></svg>
		</button>
		<div class="themisdb-fs-dots themisdb-slider-dots" role="tablist" aria-label="<?php esc_attr_e( 'Slides', 'themisdb-theme' ); ?>">
			<?php for ( $i = 0; $i < $slide_index; $i++ ) : ?>
				<button class="themisdb-fs-dot themisdb-slider-dot<?php echo ( 0 === $i ) ? ' is-active' : ''; ?>" role="tab" aria-selected="<?php echo ( 0 === $i ) ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Slide %d', 'themisdb-theme' ), $i + 1 ) ); ?>" data-index="<?php echo esc_attr( $i ); ?>"></button>
			<?php endfor; ?>
		</div>
		<div class="themisdb-fs-timer-bar" aria-hidden="true"><div class="themisdb-fs-timer-fill themisdb-hero-progress-bar"></div></div>
	</div>
	<?php
	wp_reset_postdata();

	$html = ob_get_clean();

	return apply_filters( 'themisdb_theme_front_slider_html', $html, $atts );
}

function themisdb_render_gallery_grid_block( $attributes ) {
	$section_slug      = isset( $attributes['section'] ) ? sanitize_title( $attributes['section'] ) : 'digital';
	$per_page          = isset( $attributes['perPage'] ) ? (int) $attributes['perPage'] : 6;
	$columns           = isset( $attributes['columns'] ) ? (int) $attributes['columns'] : 3;
	$show_header       = isset( $attributes['showHeader'] ) ? (bool) $attributes['showHeader'] : true;
	$show_description  = isset( $attributes['showDescription'] ) ? (bool) $attributes['showDescription'] : true;

	$columns = max( 2, min( 4, $columns ) );
	$query   = themisdb_query_section_posts( $section_slug, max( 1, min( 24, $per_page ) ), 'DESC', 'date' );

	if ( ! $query->have_posts() ) {
		return themisdb_render_section_empty_state( $section_slug );
	}

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-gallery-section">';
	if ( $show_header ) {
		echo themisdb_render_section_header( $section_slug, $show_description );
	}
	echo '<div class="themisdb-gallery-toolbar">';
	echo '<p class="themisdb-gallery-results" data-gallery-results aria-live="polite">' . esc_html( sprintf( _n( '%d Eintrag', '%d Einträge', $query->post_count, 'themisdb-theme' ), (int) $query->post_count ) ) . '</p>';
	echo '</div>';
	echo '<div class="themisdb-gallery-grid" data-gallery-grid style="grid-template-columns:repeat(' . esc_attr( (string) $columns ) . ',1fr);">';
	$item_index = 0;
	while ( $query->have_posts() ) {
		$query->the_post();
		$thumb = get_the_post_thumbnail_url( get_the_ID(), 'themisdb-gallery' );
		if ( ! $thumb ) {
			$thumb = get_the_post_thumbnail_url( get_the_ID(), 'large' );
		}
		echo '<a class="themisdb-gallery-item is-media-loading" href="' . esc_url( get_permalink() ) . '" data-title="' . esc_attr( get_the_title() ) . '" data-desc="' . esc_attr( wp_strip_all_tags( get_the_excerpt() ) ) . '" data-category="' . esc_attr( $section_slug ) . '" data-gallery-index="' . esc_attr( (string) $item_index ) . '" tabindex="0">';
		if ( $thumb ) {
			$img_loading = 0 === $item_index ? 'eager' : 'lazy';
			$img_fetch   = 0 === $item_index ? 'high' : 'auto';
			echo '<img src="' . esc_url( $thumb ) . '" alt="' . esc_attr( get_the_title() ) . '" loading="' . esc_attr( $img_loading ) . '" decoding="async" fetchpriority="' . esc_attr( $img_fetch ) . '">';
		}
		echo '<span class="themisdb-gallery-overlay"><span class="themisdb-gallery-zoom-icon">+</span></span>';
		echo '</a>';
		$item_index++;
	}
	echo '</div>';
	echo '<div class="themisdb-lightbox" data-gallery-lightbox aria-hidden="true" role="dialog" aria-modal="true" aria-label="' . esc_attr__( 'Galerieansicht', 'themisdb-theme' ) . '">';
	echo '<div class="themisdb-lb-inner" tabindex="-1">';
	echo '<button class="themisdb-lb-close" data-gallery-close type="button" aria-label="' . esc_attr__( 'Lightbox schließen', 'themisdb-theme' ) . '">&times;</button>';
	echo '<button class="themisdb-lb-nav themisdb-lb-prev" data-gallery-prev type="button" aria-label="' . esc_attr__( 'Vorheriges Bild', 'themisdb-theme' ) . '">&#8249;</button>';
	echo '<button class="themisdb-lb-nav themisdb-lb-next" data-gallery-next type="button" aria-label="' . esc_attr__( 'Nächstes Bild', 'themisdb-theme' ) . '">&#8250;</button>';
	echo '<img class="themisdb-lb-img" data-gallery-image src="" alt="">';
	echo '<div class="themisdb-lb-caption">';
	echo '<div class="themisdb-lb-meta">';
	echo '<p class="themisdb-lb-count" data-gallery-position aria-live="polite"></p>';
	echo '</div>';
	echo '<h3 data-gallery-title></h3>';
	echo '<p data-gallery-desc></p>';
	echo '</div>';
	echo '</div>';
	echo '</div>';
	echo '</section>';
	wp_reset_postdata();

	return ob_get_clean();
}

function themisdb_render_three_row_grid_block( $attributes ) {
	$atts = array(
		'section'       => isset( $attributes['section'] ) ? sanitize_title( $attributes['section'] ) : 'module',
		'per_page'      => isset( $attributes['perPage'] ) ? (int) $attributes['perPage'] : 6,
		'columns'       => 3,
		'excerpt_words' => isset( $attributes['excerptWords'] ) ? (int) $attributes['excerptWords'] : 20,
		'show_header'   => ! empty( $attributes['showHeader'] ) ? '1' : '0',
		'show_desc'     => ! empty( $attributes['showDescription'] ) ? '1' : '0',
		'show_image'    => ! empty( $attributes['showImage'] ) ? '1' : '0',
		'show_date'     => ! empty( $attributes['showDate'] ) ? '1' : '0',
		'show_excerpt'  => ! empty( $attributes['showExcerpt'] ) ? '1' : '0',
		'order'         => 'ASC',
		'orderby'       => 'menu_order',
	);

	return themisdb_section_cards_shortcode( $atts );
}

function themisdb_render_button_box_grid_block( $attributes ) {
	$section_slug      = isset( $attributes['section'] ) ? sanitize_title( $attributes['section'] ) : 'workflow';
	$per_page          = isset( $attributes['perPage'] ) ? (int) $attributes['perPage'] : 6;
	$columns           = isset( $attributes['columns'] ) ? (int) $attributes['columns'] : 3;
	$show_header       = isset( $attributes['showHeader'] ) ? (bool) $attributes['showHeader'] : true;
	$show_description  = isset( $attributes['showDescription'] ) ? (bool) $attributes['showDescription'] : true;

	$columns = max( 2, min( 4, $columns ) );
	$query   = themisdb_query_section_posts( $section_slug, max( 1, min( 24, $per_page ) ), 'ASC', 'menu_order' );

	if ( ! $query->have_posts() ) {
		return themisdb_render_section_empty_state( $section_slug );
	}

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-button-grid-section">';
	if ( $show_header ) {
		echo themisdb_render_section_header( $section_slug, $show_description );
	}
	echo '<div class="themisdb-button-box-grid" style="--lis-button-cols:' . esc_attr( (string) $columns ) . ';">';
	while ( $query->have_posts() ) {
		$query->the_post();
		echo '<a class="themisdb-button-box" href="' . esc_url( get_permalink() ) . '">';
		echo '<strong class="themisdb-button-box-title">' . esc_html( get_the_title() ) . '</strong>';
		echo '</a>';
	}
	echo '</div>';
	echo '</section>';
	wp_reset_postdata();

	return ob_get_clean();
}

function themisdb_render_section_cards_block( $attributes ) {
	$atts = array(
		'section'       => isset( $attributes['section'] ) ? sanitize_title( $attributes['section'] ) : 'zahlen',
		'per_page'      => isset( $attributes['perPage'] ) ? (int) $attributes['perPage'] : 4,
		'columns'       => isset( $attributes['columns'] ) ? (int) $attributes['columns'] : 4,
		'excerpt_words' => isset( $attributes['excerptWords'] ) ? (int) $attributes['excerptWords'] : 20,
		'show_header'   => ! empty( $attributes['showHeader'] ) ? '1' : '0',
		'show_desc'     => ! empty( $attributes['showDescription'] ) ? '1' : '0',
		'show_image'    => ! empty( $attributes['showImage'] ) ? '1' : '0',
		'show_date'     => ! empty( $attributes['showDate'] ) ? '1' : '0',
		'show_excerpt'  => ! empty( $attributes['showExcerpt'] ) ? '1' : '0',
		'order'         => isset( $attributes['order'] ) ? sanitize_key( $attributes['order'] ) : 'DESC',
		'orderby'       => isset( $attributes['orderby'] ) ? sanitize_key( $attributes['orderby'] ) : 'date',
	);

	return themisdb_section_cards_shortcode( $atts );
}

function themisdb_render_section_feature_block( $attributes ) {
	$atts = array(
		'section'       => isset( $attributes['section'] ) ? sanitize_title( $attributes['section'] ) : 'evolution',
		'excerpt_words' => isset( $attributes['excerptWords'] ) ? (int) $attributes['excerptWords'] : 36,
		'show_header'   => ! empty( $attributes['showHeader'] ) ? '1' : '0',
		'show_desc'     => ! empty( $attributes['showDescription'] ) ? '1' : '0',
	);

	return themisdb_section_feature_shortcode( $atts );
}

function themisdb_render_section_timeline_block( $attributes ) {
	$atts = array(
		'section'     => isset( $attributes['section'] ) ? sanitize_title( $attributes['section'] ) : 'aktuelles',
		'per_page'    => isset( $attributes['perPage'] ) ? (int) $attributes['perPage'] : 4,
		'show_header' => ! empty( $attributes['showHeader'] ) ? '1' : '0',
		'show_desc'   => ! empty( $attributes['showDescription'] ) ? '1' : '0',
	);

	return themisdb_section_timeline_shortcode( $atts );
}

function themisdb_render_section_faq_block( $attributes ) {
	$atts = array(
		'section'     => isset( $attributes['section'] ) ? sanitize_title( $attributes['section'] ) : 'faq',
		'per_page'    => isset( $attributes['perPage'] ) ? (int) $attributes['perPage'] : 6,
		'show_header' => ! empty( $attributes['showHeader'] ) ? '1' : '0',
		'show_desc'   => ! empty( $attributes['showDescription'] ) ? '1' : '0',
	);

	return themisdb_section_faq_shortcode( $atts );
}

function themisdb_render_contact_form_block() {
	return themisdb_contact_form_shortcode();
}

function themisdb_render_state_grid_block( $attributes ) {
	$show_header      = isset( $attributes['showHeader'] ) ? (bool) $attributes['showHeader'] : true;
	$show_description = isset( $attributes['showDescription'] ) ? (bool) $attributes['showDescription'] : true;
	$items            = themisdb_get_state_grid_items();

	if ( empty( $items ) ) {
		return '';
	}

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-state-grid-section">';
	if ( $show_header ) {
		echo '<header class="themisdb-section-header" style="margin-bottom:1rem;">';
		echo '<h2 class="themisdb-section-title" style="margin:0 0 .35rem;color:#0f172a;font-size:clamp(1.35rem,3vw,2rem);line-height:1.2;">' . esc_html__( 'Starke Partner. Ein System.', 'themisdb-theme' ) . '</h2>';
		if ( $show_description ) {
			echo '<p class="themisdb-section-description" style="margin:0;color:#64748b;line-height:1.65;">' . esc_html__( 'Die Entwicklung erfolgt im länderübergreifenden Verbund. Dies sichert Synergieeffekte und einen bundesweit einheitlichen Standard.', 'themisdb-theme' ) . '</p>';
		}
		echo '</header>';
	}
	echo '<div class="themisdb-state-grid-shell" style="padding:1rem 1rem 1.5rem;background:#fff;border:1px solid #e2e8f0;border-radius:2rem;box-shadow:0 10px 30px -15px rgba(15,23,42,0.08);">';
	echo '<div class="themisdb-state-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:1.5rem;justify-items:center;">';
	foreach ( $items as $state ) {
		echo '<a href="#" class="themisdb-land-link">';
		echo '<div class="themisdb-crest-container">';
		echo '<img class="themisdb-crest-img" data-crest="' . esc_attr( $state['crest'] ) . '" alt="' . esc_attr( $state['name'] ) . '">';
		echo '<span class="themisdb-crest-placeholder" aria-hidden="true">' . esc_html( $state['code'] ) . '</span>';
		echo '</div>';
		echo '<span class="themisdb-land-code">' . esc_html( $state['code'] ) . '</span>';
		echo '<span class="themisdb-land-name">' . esc_html( $state['name'] ) . '</span>';
		echo '</a>';
	}
	echo '</div>';
	echo '</div>';
	echo '</section>';

	return ob_get_clean();
}

add_action( 'admin_post_themisdb_seed_frontpage_content', 'themisdb_seed_frontpage_content' );
function themisdb_seed_frontpage_content() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Keine Berechtigung.', 'themisdb-theme' ) );
	}

	check_admin_referer( 'themisdb_seed_frontpage_content' );

	$created = 0;
	$skipped = 0;

	foreach ( themisdb_get_seed_blueprint() as $section_slug => $cfg ) {
		$term = themisdb_get_section_term( $section_slug );
		if ( ! $term ) {
			$skipped++;
			continue;
		}

		$has_posts = new WP_Query( array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => 1,
			'fields'              => 'ids',
			'category__in'        => array( (int) $term->term_id ),
			'ignore_sticky_posts' => true,
		) );

		if ( $has_posts->have_posts() ) {
			$skipped++;
			wp_reset_postdata();
			continue;
		}

		wp_reset_postdata();

		$count  = max( 1, min( 12, (int) $cfg['count'] ) );
		$prefix = isset( $cfg['prefix'] ) ? (string) $cfg['prefix'] : __( 'Eintrag', 'themisdb-theme' );

		for ( $i = 1; $i <= $count; $i++ ) {
			$title = sprintf( '%s %d', $prefix, $i );
			$post_id = wp_insert_post( array(
				'post_title'   => $title,
				'post_content' => '',
				'post_excerpt' => '',
				'post_status'  => 'publish',
				'post_type'    => 'post',
				'menu_order'   => $i,
			), true );

			if ( is_wp_error( $post_id ) || ! $post_id ) {
				continue;
			}

			wp_set_post_terms( $post_id, array( (int) $term->term_id ), 'category', true );
			update_post_meta( $post_id, themisdb_seed_meta_key(), '1' );
			$created++;
		}
	}

	$target = add_query_arg(
		array(
			'post_type'          => 'post',
			'themisdb_seed_created' => $created,
			'themisdb_seed_skipped' => $skipped,
		),
		admin_url( 'edit.php' )
	);

	wp_safe_redirect( $target );
	exit;
}

add_action( 'admin_post_themisdb_reset_seed_frontpage_content', 'themisdb_reset_seed_frontpage_content' );
function themisdb_reset_seed_frontpage_content() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Keine Berechtigung.', 'themisdb-theme' ) );
	}

	check_admin_referer( 'themisdb_reset_seed_frontpage_content' );

	$removed = 0;
	$query   = new WP_Query( array(
		'post_type'      => 'post',
		'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
		'posts_per_page' => 200,
		'fields'         => 'ids',
		'meta_key'       => themisdb_seed_meta_key(),
		'meta_value'     => '1',
	) );

	if ( ! empty( $query->posts ) ) {
		foreach ( $query->posts as $post_id ) {
			if ( wp_trash_post( (int) $post_id ) ) {
				$removed++;
			}
		}
	}

	$target = add_query_arg(
		array(
			'post_type'          => 'post',
			'themisdb_seed_removed' => $removed,
		),
		admin_url( 'edit.php' )
	);

	wp_safe_redirect( $target );
	exit;
}

add_action( 'admin_post_themisdb_restore_seed_frontpage_content', 'themisdb_restore_seed_frontpage_content' );
function themisdb_restore_seed_frontpage_content() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Keine Berechtigung.', 'themisdb-theme' ) );
	}

	check_admin_referer( 'themisdb_restore_seed_frontpage_content' );

	$restored = 0;
	$query    = new WP_Query( array(
		'post_type'      => 'post',
		'post_status'    => 'trash',
		'posts_per_page' => 200,
		'fields'         => 'ids',
		'meta_key'       => themisdb_seed_meta_key(),
		'meta_value'     => '1',
	) );

	if ( ! empty( $query->posts ) ) {
		foreach ( $query->posts as $post_id ) {
			if ( wp_untrash_post( (int) $post_id ) ) {
				$restored++;
			}
		}
	}

	$target = add_query_arg(
		array(
			'post_type'           => 'post',
			'themisdb_seed_restored' => $restored,
		),
		admin_url( 'edit.php' )
	);

	wp_safe_redirect( $target );
	exit;
}

add_action( 'admin_post_themisdb_purge_seed_frontpage_content', 'themisdb_purge_seed_frontpage_content' );
function themisdb_purge_seed_frontpage_content() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Keine Berechtigung.', 'themisdb-theme' ) );
	}

	check_admin_referer( 'themisdb_purge_seed_frontpage_content' );

	$purged = 0;
	$query  = new WP_Query( array(
		'post_type'      => 'post',
		'post_status'    => 'trash',
		'posts_per_page' => 200,
		'fields'         => 'ids',
		'meta_key'       => themisdb_seed_meta_key(),
		'meta_value'     => '1',
	) );

	if ( ! empty( $query->posts ) ) {
		foreach ( $query->posts as $post_id ) {
			if ( wp_delete_post( (int) $post_id, true ) ) {
				$purged++;
			}
		}
	}

	$target = add_query_arg(
		array(
			'post_type'         => 'post',
			'themisdb_seed_purged' => $purged,
		),
		admin_url( 'edit.php' )
	);

	wp_safe_redirect( $target );
	exit;
}

add_shortcode( 'themisdb_section_cards', 'themisdb_section_cards_shortcode' );
function themisdb_section_cards_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'section'       => '',
		'per_page'      => 4,
		'columns'       => 3,
		'excerpt_words' => 20,
		'show_header'   => '1',
		'show_desc'     => '1',
		'show_image'    => '1',
		'show_date'     => '1',
		'show_excerpt'  => '1',
		'order'         => 'DESC',
		'orderby'       => 'date',
	), $atts, 'themisdb_section_cards' );

	$section_slug = sanitize_title( $atts['section'] );
	$query        = themisdb_query_section_posts( $section_slug, (int) $atts['per_page'], $atts['order'], $atts['orderby'] );

	$columns      = max( 1, min( 4, absint( $atts['columns'] ) ) );
	$show_header  = themisdb_bool_atts( $atts['show_header'], true );
	$show_desc    = themisdb_bool_atts( $atts['show_desc'], true );
	$show_image   = themisdb_bool_atts( $atts['show_image'], true );
	$show_date    = themisdb_bool_atts( $atts['show_date'], true );
	$show_excerpt = themisdb_bool_atts( $atts['show_excerpt'], true );
	$words        = max( 6, min( 80, absint( $atts['excerpt_words'] ) ) );

	if ( ! $query->have_posts() ) {
		return themisdb_render_section_empty_state( $section_slug );
	}

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-cards-section">';
	if ( $show_header ) {
		echo themisdb_render_section_header( $section_slug, $show_desc );
	}
	$style_grid = '--lis-cols:' . $columns . ';display:grid;gap:1.5rem;grid-template-columns:repeat(var(--lis-cols),minmax(0,1fr));';
	echo '<div class="themisdb-section-cards" style="' . esc_attr( $style_grid ) . '">';
	$card_index = 0;
	while ( $query->have_posts() ) {
		$query->the_post();
		echo '<article class="themisdb-section-card' . ( $show_image && has_post_thumbnail() ? ' is-media-loading' : '' ) . '" style="border:1px solid #e2e8f0;border-radius:1rem;background:#fff;overflow:hidden;">';
		if ( $show_image && has_post_thumbnail() ) {
			echo '<a href="' . esc_url( get_permalink() ) . '">';
			the_post_thumbnail( 'themisdb-card', array(
				'style' => 'width:100%;height:auto;display:block;',
				'loading' => 0 === $card_index ? 'eager' : 'lazy',
				'decoding' => 'async',
				'fetchpriority' => 0 === $card_index ? 'high' : 'auto',
			) );
			echo '</a>';
		}
		echo '<div style="padding:1rem 1rem 1.1rem;">';
		if ( $show_date ) {
			echo '<p style="margin:0 0 .35rem;font-size:.72rem;font-weight:700;color:#64748b;">' . esc_html( get_the_date() ) . '</p>';
		}
		echo '<h3 style="margin:0 0 .5rem;font-size:1.05rem;line-height:1.3;"><a href="' . esc_url( get_permalink() ) . '" style="color:#0c4a6e;text-decoration:none;">' . esc_html( get_the_title() ) . '</a></h3>';
		if ( $show_excerpt ) {
			echo '<p style="margin:0;color:#475569;font-size:.92rem;line-height:1.6;">' . esc_html( wp_trim_words( get_the_excerpt() ? get_the_excerpt() : wp_strip_all_tags( get_the_content() ), $words, '…' ) ) . '</p>';
		}
		echo '</div>';
		echo '</article>';
		$card_index++;
	}
	echo '</div>';
	echo '</section>';
	wp_reset_postdata();

	return ob_get_clean();
}

add_shortcode( 'themisdb_section_feature', 'themisdb_section_feature_shortcode' );
function themisdb_section_feature_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'section'       => '',
		'excerpt_words' => 36,
		'show_header'   => '1',
		'show_desc'     => '1',
	), $atts, 'themisdb_section_feature' );

	$section_slug = sanitize_title( $atts['section'] );
	$show_header  = themisdb_bool_atts( $atts['show_header'], true );
	$show_desc    = themisdb_bool_atts( $atts['show_desc'], true );

	$query = themisdb_query_section_posts( $section_slug, 1, 'DESC', 'date' );
	if ( ! $query->have_posts() ) {
		return themisdb_render_section_empty_state( $section_slug );
	}

	$query->the_post();
	$words = max( 10, min( 100, absint( $atts['excerpt_words'] ) ) );

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-feature-section">';
	if ( $show_header ) {
		echo themisdb_render_section_header( $section_slug, $show_desc );
	}
	echo '<article class="themisdb-feature' . ( has_post_thumbnail() ? ' is-media-loading' : '' ) . '" style="display:grid;gap:2rem;grid-template-columns:1.1fr .9fr;align-items:center;">';
	echo '<div>';
	echo '<h2 style="margin:0 0 .75rem;"><a href="' . esc_url( get_permalink() ) . '" style="color:#0f172a;text-decoration:none;">' . esc_html( get_the_title() ) . '</a></h2>';
	echo '<p style="margin:0;color:#475569;line-height:1.7;">' . esc_html( wp_trim_words( get_the_excerpt() ? get_the_excerpt() : wp_strip_all_tags( get_the_content() ), $words, '…' ) ) . '</p>';
	echo '</div>';
	echo '<div>';
	if ( has_post_thumbnail() ) {
		echo '<a href="' . esc_url( get_permalink() ) . '">';
		the_post_thumbnail( 'themisdb-featured', array(
			'style' => 'width:100%;height:auto;border-radius:1rem;display:block;',
			'loading' => 'eager',
			'decoding' => 'async',
			'fetchpriority' => 'high',
		) );
		echo '</a>';
	}
	echo '</div>';
	echo '</article>';
	echo '</section>';
	wp_reset_postdata();

	return ob_get_clean();
}

add_shortcode( 'themisdb_section_timeline', 'themisdb_section_timeline_shortcode' );
function themisdb_section_timeline_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'section'  => 'aktuelles',
		'per_page' => 4,
		'show_header' => '1',
		'show_desc'   => '1',
	), $atts, 'themisdb_section_timeline' );

	$section_slug = sanitize_title( $atts['section'] );
	$show_header  = themisdb_bool_atts( $atts['show_header'], true );
	$show_desc    = themisdb_bool_atts( $atts['show_desc'], true );

	$query = themisdb_query_section_posts( $section_slug, (int) $atts['per_page'], 'DESC', 'date' );
	if ( ! $query->have_posts() ) {
		return themisdb_render_section_empty_state( $section_slug );
	}

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-timeline-section">';
	if ( $show_header ) {
		echo themisdb_render_section_header( $section_slug, $show_desc );
	}
	echo '<div class="themisdb-timeline" style="display:grid;gap:1rem;">';
	while ( $query->have_posts() ) {
		$query->the_post();
		echo '<article style="border:1px solid #e2e8f0;border-radius:1rem;padding:1rem 1.1rem;background:#fff;">';
		echo '<p style="margin:0 0 .35rem;font-size:.72rem;font-weight:700;color:#64748b;">' . esc_html( get_the_date() ) . '</p>';
		echo '<h3 style="margin:0 0 .35rem;font-size:1rem;"><a href="' . esc_url( get_permalink() ) . '" style="color:#0c4a6e;text-decoration:none;">' . esc_html( get_the_title() ) . '</a></h3>';
		echo '<p style="margin:0;color:#475569;font-size:.92rem;line-height:1.6;">' . esc_html( wp_trim_words( get_the_excerpt() ? get_the_excerpt() : wp_strip_all_tags( get_the_content() ), 24, '…' ) ) . '</p>';
		echo '</article>';
	}
	echo '</div>';
	echo '</section>';
	wp_reset_postdata();

	return ob_get_clean();
}

add_shortcode( 'themisdb_section_faq', 'themisdb_section_faq_shortcode' );
function themisdb_section_faq_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'section'  => 'faq',
		'per_page' => 8,
		'show_header' => '1',
		'show_desc'   => '1',
	), $atts, 'themisdb_section_faq' );

	$section_slug = sanitize_title( $atts['section'] );
	$show_header  = themisdb_bool_atts( $atts['show_header'], true );
	$show_desc    = themisdb_bool_atts( $atts['show_desc'], true );

	$query = themisdb_query_section_posts( $section_slug, (int) $atts['per_page'], 'ASC', 'menu_order' );
	if ( ! $query->have_posts() ) {
		return themisdb_render_section_empty_state( $section_slug );
	}

	ob_start();
	echo '<section class="themisdb-section-shell themisdb-faq-section">';
	if ( $show_header ) {
		echo themisdb_render_section_header( $section_slug, $show_desc );
	}
	$faq_instance_id = wp_unique_id( 'themisdb-faq-' );
	echo '<div class="themisdb-faq-list" data-faq-container id="' . esc_attr( $faq_instance_id ) . '" style="border:1px solid #e2e8f0;border-radius:1rem;background:#fff;overflow:hidden;">';
	$item_index = 0;
	while ( $query->have_posts() ) {
		$query->the_post();
		$toggle_id = $faq_instance_id . '-toggle-' . $item_index;
		$answer_id = $faq_instance_id . '-answer-' . $item_index;
		echo '<div class="themisdb-faq-item" style="border-bottom:1px solid #e2e8f0;">';
		echo '<button type="button" id="' . esc_attr( $toggle_id ) . '" class="themisdb-faq-toggle" data-faq-toggle aria-expanded="false" aria-controls="' . esc_attr( $answer_id ) . '" style="width:100%;text-align:left;background:none;border:none;padding:1rem 1.1rem;font-weight:700;color:#0f172a;cursor:pointer;">' . esc_html( get_the_title() ) . '<span class="themisdb-faq-icon" aria-hidden="true">+</span></button>';
		echo '<div id="' . esc_attr( $answer_id ) . '" class="themisdb-faq-answer" data-faq-answer role="region" aria-labelledby="' . esc_attr( $toggle_id ) . '" aria-hidden="true" style="padding:0 1.1rem;color:#475569;line-height:1.7;">' . wp_kses_post( apply_filters( 'the_content', get_the_content() ) ) . '</div>';
		echo '</div>';
		$item_index++;
	}
	echo '</div>';
	echo '</section>';
	wp_reset_postdata();

	return ob_get_clean();
}

add_shortcode( 'themisdb_state_grid', 'themisdb_state_grid_shortcode' );
function themisdb_state_grid_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'show_header' => '1',
		'show_desc'   => '1',
	), $atts, 'themisdb_state_grid' );

	$render_atts = array(
		'showHeader'      => themisdb_bool_atts( $atts['show_header'], true ),
		'showDescription' => themisdb_bool_atts( $atts['show_desc'], true ),
	);

	$render_atts = apply_filters( 'themisdb_theme_state_grid_atts', $render_atts, $atts );
	$html        = themisdb_render_state_grid_block( $render_atts );

	return apply_filters( 'themisdb_theme_state_grid_html', $html, $render_atts, $atts );
}

add_action( 'init', 'themisdb_register_native_compat_shortcodes', 40 );
function themisdb_register_native_compat_shortcodes() {
	$theme_owned_shortcodes = array(
		'themisdb_latest'               => 'themisdb_latest_shortcode',
		'themisdb_docker_latest'        => 'themisdb_docker_latest_shortcode',
		'themisdb_compendium_downloads' => 'themisdb_compendium_downloads_shortcode',
		'themisdb_benchmark_visualizer' => 'themisdb_benchmark_visualizer_shortcode',
		'themisdb_state_grid'           => 'themisdb_state_grid_shortcode',
		'themisdb_gallery'              => 'themisdb_gallery_shortcode',
		'themisdb_changelog'            => 'themisdb_changelog_shortcode',
		'themisdb_front_slider'         => 'themisdb_front_slider_shortcode',
	);

	foreach ( $theme_owned_shortcodes as $tag => $callback ) {
		if ( shortcode_exists( $tag ) ) {
			remove_shortcode( $tag );
		}
		add_shortcode( $tag, $callback );
	}
}

function themisdb_gallery_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'section'     => 'digital',
		'per_page'    => 6,
		'columns'     => 3,
		'show_header' => '1',
		'show_desc'   => '1',
	), $atts, 'themisdb_gallery' );

	$render_atts = array(
		'section'         => sanitize_title( $atts['section'] ),
		'perPage'         => (int) $atts['per_page'],
		'columns'         => (int) $atts['columns'],
		'showHeader'      => themisdb_bool_atts( $atts['show_header'], true ),
		'showDescription' => themisdb_bool_atts( $atts['show_desc'], true ),
	);

	$render_atts = apply_filters( 'themisdb_theme_gallery_atts', $render_atts, $atts );
	$html        = themisdb_render_gallery_grid_block( $render_atts );

	return apply_filters( 'themisdb_theme_gallery_html', $html, $render_atts, $atts );
}

function themisdb_changelog_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'section'     => 'aktuelles',
		'per_page'    => 4,
		'show_header' => '1',
		'show_desc'   => '1',
	), $atts, 'themisdb_changelog' );

	$render_atts = array(
		'section'         => sanitize_title( $atts['section'] ),
		'perPage'         => (int) $atts['per_page'],
		'showHeader'      => themisdb_bool_atts( $atts['show_header'], true ),
		'showDescription' => themisdb_bool_atts( $atts['show_desc'], true ),
	);

	$render_atts = apply_filters( 'themisdb_theme_changelog_atts', $render_atts, $atts );
	$html        = themisdb_render_section_timeline_block( $render_atts );

	return apply_filters( 'themisdb_theme_changelog_html', $html, $render_atts, $atts );
}

add_shortcode( 'themisdb_contact_form', 'themisdb_contact_form_shortcode' );
function themisdb_contact_form_shortcode() {
	ob_start();
	?>
	<form id="themisdb-contact-form" class="themisdb-contact-form" novalidate>
		<div class="themisdb-contact-form-grid">
			<label class="themisdb-visually-hidden" for="themisdb-cf-behoerde"><?php esc_html_e( 'Behoerde / Institution', 'themisdb-theme' ); ?></label>
			<input id="themisdb-cf-behoerde" type="text" name="behoerde" placeholder="Behoerde / Institution" autocomplete="organization" aria-describedby="themisdb-cf-behoerde-help themisdb-cf-behoerde-count themisdb-cf-error themisdb-cf-success" maxlength="120" required>
			<p id="themisdb-cf-behoerde-help" class="themisdb-field-help"><?php esc_html_e( 'Bitte geben Sie den offiziellen Namen Ihrer Behoerde oder Institution an.', 'themisdb-theme' ); ?></p>
			<p id="themisdb-cf-behoerde-count" class="themisdb-field-meta" aria-live="polite">0 / 120</p>
			<label class="themisdb-visually-hidden" for="themisdb-cf-email"><?php esc_html_e( 'Dienstliche E-Mail', 'themisdb-theme' ); ?></label>
			<input id="themisdb-cf-email" type="email" name="email" placeholder="Dienstliche E-Mail" autocomplete="email" aria-describedby="themisdb-cf-email-help themisdb-cf-error themisdb-cf-success" required>
			<p id="themisdb-cf-email-help" class="themisdb-field-help"><?php esc_html_e( 'Verwenden Sie eine dienstliche Adresse fuer eine schnellere Zuordnung.', 'themisdb-theme' ); ?></p>
			<div class="themisdb-honeypot-field" aria-hidden="true">
				<label class="themisdb-visually-hidden" for="themisdb-cf-website"><?php esc_html_e( 'Website', 'themisdb-theme' ); ?></label>
				<input id="themisdb-cf-website" type="text" name="website" tabindex="-1" autocomplete="off">
			</div>
			<p id="themisdb-cf-error" class="themisdb-form-message themisdb-form-message--error" role="alert" aria-live="assertive"></p>
			<p id="themisdb-cf-success" class="themisdb-form-message themisdb-form-message--success" role="status" aria-live="polite"></p>
			<button type="submit" class="themisdb-btn-primary"><?php esc_html_e( 'Anfrage senden', 'themisdb-theme' ); ?></button>
		</div>
	</form>
	<?php
	return ob_get_clean();
}

add_filter( 'views_edit-post', 'themisdb_section_views_edit_post' );
function themisdb_section_views_edit_post( $views ) {
	$base_url = admin_url( 'edit.php?post_type=post' );
	foreach ( themisdb_get_section_definitions() as $slug => $label ) {
		$term = themisdb_get_section_term( $slug );
		if ( ! $term ) {
			continue;
		}

		$count = (int) $term->count;
		$url   = add_query_arg( array( 'cat' => (int) $term->term_id ), $base_url );
		$key   = 'themisdb_sec_' . $slug;
		$views[ $key ] = '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . ' <span class="count">(' . $count . ')</span></a>';
	}

	return $views;
}

add_action( 'admin_notices', 'themisdb_section_admin_hint' );
function themisdb_section_admin_hint() {
	$screen = get_current_screen();
	if ( ! $screen || 'post' !== $screen->id ) {
		return;
	}

	echo '<div class="notice notice-info"><p><strong>' . esc_html__( 'ThemisDB Frontpage-Sektionen:', 'themisdb-theme' ) . '</strong> ';
	$chunks = array();
	foreach ( themisdb_get_section_definitions() as $slug => $label ) {
		$chunks[] = esc_html( $label ) . ' (' . esc_html( $slug ) . ')';
	}
	echo esc_html( implode( ' | ', $chunks ) );

	if ( current_user_can( 'manage_options' ) ) {
		$seed_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=themisdb_seed_frontpage_content' ),
			'themisdb_seed_frontpage_content'
		);
		$reset_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=themisdb_reset_seed_frontpage_content' ),
			'themisdb_reset_seed_frontpage_content'
		);
		$restore_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=themisdb_restore_seed_frontpage_content' ),
			'themisdb_restore_seed_frontpage_content'
		);
		$purge_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=themisdb_purge_seed_frontpage_content' ),
			'themisdb_purge_seed_frontpage_content'
		);

		echo ' &nbsp; <a class="button button-small" href="' . esc_url( $seed_url ) . '">' . esc_html__( 'Beispieldaten für leere Sektionen anlegen', 'themisdb-theme' ) . '</a>';
		echo ' &nbsp; <a class="button button-small" href="' . esc_url( $reset_url ) . '" onclick="return confirm(\'' . esc_js( __( 'Seed-Beiträge wirklich in den Papierkorb verschieben?', 'themisdb-theme' ) ) . '\');">' . esc_html__( 'Seed-Beiträge zurücksetzen', 'themisdb-theme' ) . '</a>';
		echo ' &nbsp; <a class="button button-small" href="' . esc_url( $restore_url ) . '">' . esc_html__( 'Seed-Beiträge wiederherstellen', 'themisdb-theme' ) . '</a>';
		echo ' &nbsp; <a class="button button-small button-link-delete" href="' . esc_url( $purge_url ) . '" onclick="return confirm(\'' . esc_js( __( 'Seed-Beiträge endgültig löschen? Dieser Schritt kann nicht rückgängig gemacht werden.', 'themisdb-theme' ) ) . '\');">' . esc_html__( 'Seed-Beiträge endgültig löschen', 'themisdb-theme' ) . '</a>';
	}

	echo '</p></div>';

	if ( isset( $_GET['themisdb_seed_created'] ) ) {
		$created = absint( wp_unslash( $_GET['themisdb_seed_created'] ) );
		$skipped = isset( $_GET['themisdb_seed_skipped'] ) ? absint( wp_unslash( $_GET['themisdb_seed_skipped'] ) ) : 0;
		echo '<div class="notice notice-success is-dismissible"><p>' .
		esc_html( sprintf( __( 'ThemisDB Seed abgeschlossen: %1$d Beiträge erstellt, %2$d Sektionen übersprungen (bereits befüllt).', 'themisdb-theme' ), $created, $skipped ) ) .
		'</p></div>';
	}

	if ( isset( $_GET['themisdb_seed_removed'] ) ) {
		$removed = absint( wp_unslash( $_GET['themisdb_seed_removed'] ) );
		echo '<div class="notice notice-warning is-dismissible"><p>' .
		esc_html( sprintf( __( 'ThemisDB Reset abgeschlossen: %d Seed-Beiträge in den Papierkorb verschoben.', 'themisdb-theme' ), $removed ) ) .
		'</p></div>';
	}

	if ( isset( $_GET['themisdb_seed_restored'] ) ) {
		$restored = absint( wp_unslash( $_GET['themisdb_seed_restored'] ) );
		echo '<div class="notice notice-success is-dismissible"><p>' .
		esc_html( sprintf( __( 'ThemisDB Restore abgeschlossen: %d Seed-Beiträge aus dem Papierkorb wiederhergestellt.', 'themisdb-theme' ), $restored ) ) .
		'</p></div>';
	}

	if ( isset( $_GET['themisdb_seed_purged'] ) ) {
		$purged = absint( wp_unslash( $_GET['themisdb_seed_purged'] ) );
		echo '<div class="notice notice-error is-dismissible"><p>' .
		esc_html( sprintf( __( 'ThemisDB Purge abgeschlossen: %d Seed-Beiträge endgültig gelöscht.', 'themisdb-theme' ), $purged ) ) .
		'</p></div>';
	}
}

/* =====================================================================
   6. CONTACT FORM AJAX HANDLER
   Verarbeitet das "Systemzugang anfragen"-Formular.
   ===================================================================== */

add_action( 'wp_ajax_nopriv_themisdb_contact', 'themisdb_handle_contact' );
add_action( 'wp_ajax_themisdb_contact',        'themisdb_handle_contact' );

function themisdb_get_request_ip() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '';
	$ip = sanitize_text_field( $ip );

	if ( '' === $ip ) {
		return 'unknown';
	}

	return $ip;
}

function themisdb_contact_rate_limit_seconds() {
	$seconds = (int) get_theme_mod( 'themisdb_contact_rate_limit_seconds', 60 );

	return max( 15, min( 900, $seconds ) );
}

function themisdb_contact_rate_limit_key( $email ) {
	$identifier = strtolower( trim( (string) $email ) );
	if ( '' === $identifier ) {
		$identifier = themisdb_get_request_ip();
	}

	return 'themisdb_cf_rl_' . md5( themisdb_get_request_ip() . '|' . $identifier );
}

function themisdb_contact_rate_limited( $email ) {
	$key = themisdb_contact_rate_limit_key( $email );

	return false !== get_transient( $key );
}

function themisdb_contact_rate_limit_remaining( $email ) {
	$key       = themisdb_contact_rate_limit_key( $email );
	$lock_until = (int) get_transient( $key );
	$remaining  = $lock_until - time();

	if ( $remaining <= 0 ) {
		return 0;
	}

	return $remaining;
}

function themisdb_mark_contact_attempt( $email ) {
	$key     = themisdb_contact_rate_limit_key( $email );
	$seconds = themisdb_contact_rate_limit_seconds();
	set_transient( $key, time() + $seconds, $seconds );
}

function themisdb_create_support_ticket_from_contact( $behoerde, $email ) {
	if ( ! class_exists( 'ThemisDB_SupportPortal_Ticket_Manager' ) ) {
		return new WP_Error(
			'support_plugin_missing',
			__( 'Support-Portal ist nicht aktiv. Ticket konnte nicht erstellt werden.', 'themisdb-theme' )
		);
	}

	$subject = sprintf(
		/* translators: %s: Behoerde/Institution */
		__( 'Systemzugang-Anfrage: %s', 'themisdb-theme' ),
		$behoerde
	);

	$request_url = home_url( '/' );
	if ( isset( $GLOBALS['wp'] ) && is_object( $GLOBALS['wp'] ) && isset( $GLOBALS['wp']->request ) ) {
		$request_path = trim( (string) $GLOBALS['wp']->request, '/' );
		if ( '' !== $request_path ) {
			$request_url = home_url( '/' . $request_path . '/' );
		}
	}

	$message_lines = array(
		sprintf( __( 'Behoerde/Institution: %s', 'themisdb-theme' ), $behoerde ),
		sprintf( __( 'Dienstliche E-Mail: %s', 'themisdb-theme' ), $email ),
		sprintf( __( 'Anfragezeitpunkt: %s', 'themisdb-theme' ), wp_date( 'd.m.Y H:i' ) ),
		sprintf( __( 'Anfrageseite: %s', 'themisdb-theme' ), esc_url_raw( $request_url ) ),
		sprintf( __( 'Anfrage-IP: %s', 'themisdb-theme' ), themisdb_get_request_ip() ),
	);

	$ticket_id = ThemisDB_SupportPortal_Ticket_Manager::create_ticket(
		array(
			'subject'          => $subject,
			'message'          => implode( "\n", $message_lines ),
			'customer_name'    => $behoerde,
			'customer_email'   => $email,
			'customer_company' => $behoerde,
			'priority'         => 'normal',
			'user_id'          => get_current_user_id(),
		)
	);

	if ( ! $ticket_id ) {
		$last_error = '';
		if ( is_callable( array( 'ThemisDB_SupportPortal_Ticket_Manager', 'get_last_error' ) ) ) {
			$last_error = (string) ThemisDB_SupportPortal_Ticket_Manager::get_last_error();
		}

		return new WP_Error(
			'support_ticket_failed',
			'' !== $last_error
				? $last_error
				: __( 'Support-Ticket konnte nicht erstellt werden.', 'themisdb-theme' )
		);
	}

	$ticket_number = '';
	if ( is_callable( array( 'ThemisDB_SupportPortal_Ticket_Manager', 'get_ticket' ) ) ) {
		$ticket = ThemisDB_SupportPortal_Ticket_Manager::get_ticket( (int) $ticket_id );
		if ( is_array( $ticket ) && ! empty( $ticket['ticket_number'] ) ) {
			$ticket_number = (string) $ticket['ticket_number'];
		}
	}

	return array(
		'ticket_id'     => (int) $ticket_id,
		'ticket_number' => $ticket_number,
	);
}

function themisdb_handle_contact() {
	check_ajax_referer( 'themisdb_contact_nonce', 'nonce' );

	$honeypot = isset( $_POST['website'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['website'] ) ) ) : '';
	$behoerde = isset( $_POST['behoerde'] ) ? sanitize_text_field( wp_unslash( $_POST['behoerde'] ) ) : '';
	$email    = isset( $_POST['email']    ) ? sanitize_email( wp_unslash( $_POST['email'] ) )         : '';

	// Honeypot-Treffer werden still wie Erfolg behandelt.
	if ( '' !== $honeypot ) {
		wp_send_json_success( array(
			'message' => __( 'Ihre Anfrage wurde erfolgreich uebermittelt. Wir melden uns zeitnah.', 'themisdb-theme' ),
			'code'    => 'ignored_honeypot',
		) );
		return;
	}

	if ( themisdb_contact_rate_limited( $email ) ) {
		$remaining = themisdb_contact_rate_limit_remaining( $email );
		wp_send_json_error(
			array(
				'code'        => 'rate_limited',
				'message'     => __( 'Bitte warten Sie kurz, bevor Sie eine weitere Anfrage senden.', 'themisdb-theme' ),
				'retry_after' => $remaining,
			),
			429
		);
		return;
	}

	if ( empty( $behoerde ) || empty( $email ) || ! is_email( $email ) ) {
		$invalid_fields = array();
		if ( empty( $behoerde ) ) {
			$invalid_fields[] = 'behoerde';
		}
		if ( empty( $email ) || ! is_email( $email ) ) {
			$invalid_fields[] = 'email';
		}

		wp_send_json_error(
			array(
				'code'    => 'invalid_input',
				'fields'  => $invalid_fields,
				'message' => __( 'Bitte Behoerde und dienstliche E-Mail-Adresse eingeben.', 'themisdb-theme' ),
			),
			400
		);
		return;
	}

	themisdb_mark_contact_attempt( $email );
	$ticket_result = themisdb_create_support_ticket_from_contact( $behoerde, $email );

	if ( is_wp_error( $ticket_result ) ) {
		wp_send_json_error(
			array(
				'code'    => $ticket_result->get_error_code(),
				'message' => $ticket_result->get_error_message(),
			),
			500
		);
		return;
	}

	if ( is_array( $ticket_result ) ) {
		$ticket_number = isset( $ticket_result['ticket_number'] ) ? (string) $ticket_result['ticket_number'] : '';
		$success_msg   = __( 'Ihre Anfrage wurde als Support-Ticket gespeichert. Wir melden uns zeitnah.', 'themisdb-theme' );

		if ( '' !== $ticket_number ) {
			$success_msg = sprintf(
				/* translators: %s: Ticketnummer */
				__( 'Ihre Anfrage wurde als Support-Ticket %s gespeichert. Wir melden uns zeitnah.', 'themisdb-theme' ),
				esc_html( $ticket_number )
			);
		}

		wp_send_json_success( array(
			'code'          => 'ticket_created',
			'message'       => $success_msg,
			'ticket_id'     => (int) $ticket_result['ticket_id'],
			'ticket_number' => $ticket_number,
		) );
		return;
	}
}

/* =====================================================================
   6. THEME OPTIONS (Customizer)
   ===================================================================== */

add_action( 'customize_register', 'themisdb_customizer' );

function themisdb_customizer( $wp_customize ) {

	$wp_customize->add_section( 'themisdb_options', array(
		'title'    => __( 'ThemisDB Einstellungen', 'themisdb-theme' ),
		'description' => __( 'Kontakt, Footer und rechtliche Inhalte. Inhalte sollten aus WordPress-Seiten, Menues und Theme-Optionen gepflegt werden.', 'themisdb-theme' ),
		'priority' => 30,
	) );

	// Kontakt-E-Mail
	$wp_customize->add_setting( 'themisdb_contact_email', array(
		'default'           => get_option( 'admin_email' ),
		'sanitize_callback' => 'sanitize_email',
	) );
	$wp_customize->add_control( 'themisdb_contact_email', array(
		'label'       => __( 'Kontakt-E-Mail (Systemzugang-Anfragen)', 'themisdb-theme' ),
		'description' => __( 'Empfaengeradresse fuer Formularanfragen auf der Startseite.', 'themisdb-theme' ),
		'section'     => 'themisdb_options',
		'type'        => 'email',
	) );

	$wp_customize->add_setting( 'themisdb_contact_rate_limit_seconds', array(
		'default'           => 60,
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( 'themisdb_contact_rate_limit_seconds', array(
		'label'       => __( 'Kontaktformular Rate-Limit (Sekunden)', 'themisdb-theme' ),
		'description' => __( 'Empfohlener Bereich: 30 bis 120 Sekunden pro Anfrage und Absender.', 'themisdb-theme' ),
		'section'     => 'themisdb_options',
		'type'        => 'number',
		'input_attrs' => array(
			'min'  => 15,
			'max'  => 900,
			'step' => 5,
		),
	) );

	$wp_customize->add_setting( 'themisdb_footer_description', array(
		'default'           => get_bloginfo( 'description' ),
		'sanitize_callback' => 'sanitize_textarea_field',
	) );
	$wp_customize->add_control( 'themisdb_footer_description', array(
		'label'       => __( 'Footer-Beschreibung', 'themisdb-theme' ),
		'description' => __( 'Kurztext fuer den Footer-Bereich. Empfehlung: 1 bis 2 Saetze mit Zweck und Zielgruppe.', 'themisdb-theme' ),
		'section'     => 'themisdb_options',
		'type'        => 'textarea',
	) );

	$wp_customize->add_setting( 'themisdb_footer_contact_heading', array(
		'default'           => __( 'Kontakt', 'themisdb-theme' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'themisdb_footer_contact_heading', array(
		'label'       => __( 'Footer-Kontaktüberschrift', 'themisdb-theme' ),
		'description' => __( 'Kurze Ueberschrift fuer die Kontaktspalte im Footer.', 'themisdb-theme' ),
		'section'     => 'themisdb_options',
		'type'        => 'text',
	) );

	$wp_customize->add_setting( 'themisdb_footer_contact_name', array(
		'default'           => get_bloginfo( 'name' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'themisdb_footer_contact_name', array(
		'label'       => __( 'Footer-Kontaktname', 'themisdb-theme' ),
		'description' => __( 'Bezeichnung der zustaendigen Stelle, z. B. Referat oder Teamname.', 'themisdb-theme' ),
		'section'     => 'themisdb_options',
		'type'        => 'text',
	) );

	$wp_customize->add_setting( 'themisdb_footer_contact_subline', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'themisdb_footer_contact_subline', array(
		'label'       => __( 'Footer-Kontakt-Unterzeile', 'themisdb-theme' ),
		'description' => __( 'Optionaler Zusatz, z. B. Erreichbarkeit oder Bereich.', 'themisdb-theme' ),
		'section'     => 'themisdb_options',
		'type'        => 'text',
	) );

	$wp_customize->add_setting( 'themisdb_footer_notice', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'themisdb_footer_notice', array(
		'label'       => __( 'Footer-Hinweis unten rechts', 'themisdb-theme' ),
		'description' => __( 'Optionaler kurzer Hinweis, z. B. Sicherheits- oder Betriebsstatus.', 'themisdb-theme' ),
		'section'     => 'themisdb_options',
		'type'        => 'text',
	) );

	$wp_customize->add_setting( 'themisdb_impressum_page', array(
		'default'           => 0,
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( 'themisdb_impressum_page', array(
		'label'       => __( 'Seite für Impressum', 'themisdb-theme' ),
		'description' => __( 'Waehlen Sie die WordPress-Seite, deren Inhalt als Impressum ausgegeben wird.', 'themisdb-theme' ),
		'section'     => 'themisdb_options',
		'type'        => 'dropdown-pages',
	) );

	$wp_customize->add_setting( 'themisdb_datenschutz_page', array(
		'default'           => 0,
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( 'themisdb_datenschutz_page', array(
		'label'       => __( 'Seite für Datenschutz', 'themisdb-theme' ),
		'description' => __( 'Waehlen Sie die WordPress-Seite, deren Inhalt als Datenschutzhinweis ausgegeben wird.', 'themisdb-theme' ),
		'section'     => 'themisdb_options',
		'type'        => 'dropdown-pages',
	) );

	$wp_customize->add_setting( 'themisdb_show_state_grid', array(
		'default'           => false,
		'sanitize_callback' => 'rest_sanitize_boolean',
	) );
	$wp_customize->add_control( 'themisdb_show_state_grid', array(
		'label'       => __( 'Bundesländer-Grid anzeigen', 'themisdb-theme' ),
		'description' => __( 'Aktivieren nur wenn Inhalte fuer alle Bundeslaender gepflegt sind.', 'themisdb-theme' ),
		'section'     => 'themisdb_options',
		'type'        => 'checkbox',
	) );

	// Slider-Autoplay-Intervall
	$wp_customize->add_setting( 'themisdb_slider_interval', array(
		'default'           => 5000,
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( 'themisdb_slider_interval', array(
		'label'       => __( 'Hero-Slider Intervall (ms)', 'themisdb-theme' ),
		'description' => __( 'Empfehlung: 5000 bis 7000 fuer gute Lesbarkeit ohne Traegheit.', 'themisdb-theme' ),
		'section'     => 'themisdb_options',
		'type'        => 'number',
		'input_attrs' => array(
			'min'  => 2000,
			'max'  => 30000,
			'step' => 500,
		),
	) );

	// Coat-of-arms assets base URL
	$wp_customize->add_setting( 'themisdb_crest_base_url', array(
		'default'           => THEMISDB_THEME_URI . '/assets/crests/',
		'sanitize_callback' => 'esc_url_raw',
	) );
	$wp_customize->add_control( 'themisdb_crest_base_url', array(
		'label'       => __( 'Wappen-Verzeichnis URL', 'themisdb-theme' ),
		'description' => __( 'URL des Ordners mit den SVG-Wappendateien. Abschluss mit / verwenden.', 'themisdb-theme' ),
		'section'     => 'themisdb_options',
		'type'        => 'url',
	) );
}

/* =====================================================================
   7. GUTENBERG EDITOR: Inline JS-Daten für Customizer-Werte
   ===================================================================== */

add_action( 'wp_footer', 'themisdb_footer_js_data', 5 );

function themisdb_footer_js_data() {
	$data = array(
		'sliderInterval' => (int) get_theme_mod( 'themisdb_slider_interval', 5000 ),
		'crestBaseUrl'   => esc_url( get_theme_mod( 'themisdb_crest_base_url', THEMISDB_THEME_URI . '/assets/crests/' ) ),
		'contactNonce'   => wp_create_nonce( 'themisdb_contact_nonce' ),
		'contactEmail'   => themisdb_get_contact_email(),
		'ajaxUrl'        => esc_url( admin_url( 'admin-ajax.php' ) ),
		'i18n'           => array(
			'contactSubmitDefault'   => __( 'Anfrage senden', 'themisdb-theme' ),
			'contactSubmitBusy'      => __( 'Wird gesendet...', 'themisdb-theme' ),
			'contactWaitSeconds'     => __( 'Bitte warten Sie %d Sekunden, bevor Sie eine weitere Anfrage senden.', 'themisdb-theme' ),
			'contactWaitGeneric'     => __( 'Bitte warten Sie kurz, bevor Sie eine weitere Anfrage senden.', 'themisdb-theme' ),
			'contactOrgRequired'     => __( 'Bitte geben Sie Ihre Behoerde / Institution an.', 'themisdb-theme' ),
			'contactEmailInvalid'    => __( 'Bitte geben Sie eine gueltige dienstliche E-Mail-Adresse an.', 'themisdb-theme' ),
			'contactEmailClientOpen' => __( 'Ihr E-Mail-Client wurde geoeffnet. Bitte senden Sie die Nachricht ab.', 'themisdb-theme' ),
			'contactMailtoSubject'   => __( 'ThemisDB Zugangsanfrage von %s', 'themisdb-theme' ),
			'contactMailtoBody'      => __( 'Behoerde: %1$s\r\nE-Mail: %2$s\r\n\r\nBitte nehmen Sie Kontakt auf.', 'themisdb-theme' ),
			'contactSendSuccess'     => __( 'Ihre Anfrage wurde erfolgreich gesendet. Wir melden uns in Kuerze.', 'themisdb-theme' ),
			'contactSendError'       => __( 'Fehler beim Senden. Bitte versuchen Sie es erneut oder schreiben Sie direkt an %s.', 'themisdb-theme' ),
			'contactNetworkError'    => __( 'Netzwerkfehler. Bitte schreiben Sie direkt an %s.', 'themisdb-theme' ),
		),
	);

	printf(
		'<script id="themisdb-data">var themisdbThemeData = %s;</script>',
		wp_json_encode( $data )
	);
}

/* =====================================================================
   8. ROOT-RELATIVE URL-NORMALISIERUNG
   (Übernommen aus ThemisDB v3 für Sub-Directory-Kompatibilität)
   ===================================================================== */

add_filter( 'render_block', 'themisdb_normalize_root_relative_urls', 20, 2 );

function themisdb_normalize_root_relative_urls( $content, $block ) {
	if ( ! is_string( $content ) || '' === $content ) {
		return $content;
	}

	$block_name      = ( is_array( $block ) && isset( $block['blockName'] ) ) ? (string) $block['blockName'] : '';
	$is_theme_block  = '' !== $block_name && 0 === strpos( $block_name, 'themisdb-theme/' );
	$is_templatepart = 'core/template-part' === $block_name;
	$has_placeholder = false !== strpos( $content, '%%THEMISDB_' );

	if ( ! $is_theme_block && ! $is_templatepart && ! $has_placeholder ) {
		return $content;
	}

	if (
		false === strpos( $content, 'href="/' ) &&
		false === strpos( $content, 'src="/'  )
	) {
		return themisdb_replace_template_placeholders( $content );
	}

	$content = (string) preg_replace_callback(
		'/(href|src)=(["\'])\/(?!\/)([^"\']*)\2/i',
		static function( $m ) {
			$attr = strtolower( $m[1] );
			$q    = $m[2];
			$path = ltrim( (string) $m[3], '/' );

			if ( 'href' === $attr ) {
				$page = get_page_by_path( $path );
				if ( $page instanceof WP_Post ) {
					return $attr . '=' . $q . esc_url( get_permalink( $page->ID ) ) . $q;
				}
				return $attr . '=' . $q . esc_url( add_query_arg( 'pagename', $path, home_url( '/' ) ) ) . $q;
			}

			return $attr . '=' . $q . esc_url( home_url( '/' . $path ) ) . $q;
		},
		$content
	);

	return themisdb_replace_template_placeholders( $content );
}

function themisdb_replace_template_placeholders( $content ) {
	$replacements = array(
		'%%THEMISDB_CONTACT_EMAIL%%'          => themisdb_get_contact_email(),
		'%%THEMISDB_SITE_TITLE%%'             => esc_html( get_bloginfo( 'name' ) ),
		'%%THEMISDB_SITE_TAGLINE%%'           => esc_html( get_bloginfo( 'description' ) ),
		'%%THEMISDB_FOOTER_DESCRIPTION%%'     => themisdb_get_footer_description(),
		'%%THEMISDB_FOOTER_LINKS%%'           => themisdb_get_footer_menu_markup( 'footer-1' ),
		'%%THEMISDB_FOOTER_CONTACT_HEADING%%' => esc_html( themisdb_get_footer_text( 'themisdb_footer_contact_heading', __( 'Kontakt', 'themisdb-theme' ) ) ),
		'%%THEMISDB_FOOTER_CONTACT_NAME%%'    => esc_html( themisdb_get_footer_text( 'themisdb_footer_contact_name', get_bloginfo( 'name' ) ) ),
		'%%THEMISDB_FOOTER_CONTACT_SUBLINE%%' => esc_html( themisdb_get_footer_text( 'themisdb_footer_contact_subline', '' ) ),
		'%%THEMISDB_FOOTER_META%%'            => themisdb_get_footer_menu_markup( 'footer-2' ),
		'%%THEMISDB_FOOTER_COPYRIGHT%%'       => themisdb_get_footer_copyright_markup(),
		'%%THEMISDB_FOOTER_NOTICE%%'          => themisdb_get_footer_notice_markup(),
		'%%THEMISDB_IMPRESSUM_TITLE%%'        => esc_html( themisdb_get_legal_page_title( 'themisdb_impressum_page', __( 'Impressum', 'themisdb-theme' ), 'impressum' ) ),
		'%%THEMISDB_IMPRESSUM_CONTENT%%'      => themisdb_get_legal_page_content( 'themisdb_impressum_page', 'impressum' ),
		'%%THEMISDB_DATENSCHUTZ_TITLE%%'      => esc_html( themisdb_get_legal_page_title( 'themisdb_datenschutz_page', __( 'Datenschutz', 'themisdb-theme' ), 'datenschutz' ) ),
		'%%THEMISDB_DATENSCHUTZ_CONTENT%%'    => themisdb_get_legal_page_content( 'themisdb_datenschutz_page', 'datenschutz' ),
	);

	return strtr( $content, $replacements );
}

/* =====================================================================
   9. FLUSH REWRITE RULES (einmalig pro Version)
   ===================================================================== */

add_action( 'init', 'themisdb_maybe_flush_rewrites', 20 );

function themisdb_maybe_flush_rewrites() {
	$key = 'themisdb_rewrite_flushed_v';
	if ( get_option( $key ) === THEMISDB_THEME_VERSION ) {
		return;
	}
	flush_rewrite_rules( false );
	update_option( $key, THEMISDB_THEME_VERSION );
}
