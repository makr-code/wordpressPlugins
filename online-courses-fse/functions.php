<?php

/**
 * Online Courses FSE Theme
 *
 * @package OnlineCoursesFSE
 */

use OnlineCoursesFSE\Assets_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

! defined( 'ONLINE_COURSES_FSE_THEME_FILE' ) && define( 'ONLINE_COURSES_FSE_THEME_FILE', __FILE__ );

if ( file_exists( get_theme_file_path( 'vendor/autoload.php' ) ) ) {
	require_once get_theme_file_path( 'vendor/autoload.php' );
}

require_once get_theme_file_path( 'inc/constants.php' );
require_once get_theme_file_path( 'inc/admin.php' );

require_once get_theme_file_path( 'inc/theme-setup.php' );
require_once get_theme_file_path( 'inc/register_block_patterns.php' );
require_once get_theme_file_path( 'inc/assets-manager.php' );

/**
 * Theme setup
 */
if ( ! function_exists( 'online_courses_fse_setup' ) ) {
	function online_courses_fse_setup() {
		add_theme_support( 'editor-styles' );
	}
}

add_action( 'after_setup_theme', 'online_courses_fse_setup' );

/**
 * Enqueue theme stylesheet
 */
function online_courses_fse_enqueue_styles() {
	wp_enqueue_style(
		'online-courses-fse-style',
		get_stylesheet_uri(),
		array(),
		wp_get_theme()->get( 'Version' )
	);
}

add_action( 'wp_enqueue_scripts', 'online_courses_fse_enqueue_styles' );

/**
 * Provide default logo when none is set
 */
function online_courses_fse_get_custom_logo( $html ) {
	if ( ! empty( $html ) ) {
		return $html;
	}

	$default_logo_filename = 'fse-theme-logo.png';
	$default_logo_url      = Assets_Manager::get_image_url( $default_logo_filename );

	if ( file_exists( get_template_directory() . '/assets/images/' . $default_logo_filename ) ) {
		$html = sprintf(
			'<a href="%1$s" class="custom-logo-link" rel="home" aria-label="%3$s">
				<img src="%2$s" class="custom-logo" alt="%3$s" width="200" height="80" />
			</a>',
			esc_url( home_url( '/' ) ),
			esc_url( $default_logo_url ),
			esc_attr( get_bloginfo( 'name' ) )
		);
	} else {
		$html = sprintf(
			'<a href="%1$s" class="custom-logo-link site-title-fallback" rel="home">%2$s</a>',
			esc_url( home_url( '/' ) ),
			esc_html( get_bloginfo( 'name' ) )
		);
	}

	return $html;
}

add_filter( 'get_custom_logo', 'online_courses_fse_get_custom_logo' );


/**
 * Provide a default fallback tagline if the site tagline is empty.
 */
function online_courses_fse_default_tagline( $blogdescription ) {
	if ( empty( trim( $blogdescription ) ) ) {
		return 'Join thousands of learners & explore courses from top instructors. Effortlessly to launch, manage, & grow.';
	}
	return $blogdescription;
}
add_filter( 'option_blogdescription', 'online_courses_fse_default_tagline' );
