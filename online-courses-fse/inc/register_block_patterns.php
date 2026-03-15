<?php
/**
 * Block patterns
 *
 * @package online-courses-fse
 * @since 1.0.2
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register block patterns.
 *
 * @since 1.0.2
 * @package online-courses-fse
 */
function online_courses_fse_register_block_patterns() {

	/**
	 * Block pattern categories.
	 *
	 * @since 1.0.2
	 * @package online-courses-fse
	 */
	$block_pattern_categories = apply_filters(
		'online_courses_fse_block_pattern_categories',
		array(
			'online-courses-fse-cta'          => array(
				'label' => __( 'Online Courses FSE - CTA', 'online-courses-fse' ),
			),
			'online-courses-fse-faq'          => array(
				'label' => __( 'Online Courses FSE - FAQs', 'online-courses-fse' ),
			),
			'online-courses-fse-instructors'  => array(
				'label' => __( 'Online Courses FSE - Instructors', 'online-courses-fse' ),
			),
			'online-courses-fse-about-us'     => array(
				'label' => __( 'Online Courses FSE - About Us', 'online-courses-fse' ),
			),
			'online-courses-fse-reviews'      => array(
				'label' => __( 'Online Courses FSE - Reviews', 'online-courses-fse' ),
			),
			'online-courses-fse-pricing'      => array(
				'label' => __( 'Online Courses FSE - Pricing', 'online-courses-fse' ),
			),
			'online-courses-fse-partners'     => array(
				'label' => __( 'Online Courses FSE - Partners', 'online-courses-fse' ),
			),
			'online-courses-fse-categories'   => array(
				'label' => __( 'Online Courses FSE - Categories', 'online-courses-fse' ),
			),
			'online-courses-fse-courses'      => array(
				'label' => __( 'Online Courses FSE - Courses', 'online-courses-fse' ),
			),
			'online-courses-fse-features'     => array(
				'label' => __( 'Online Courses FSE - Features', 'online-courses-fse' ),
			),
			'online-courses-fse-testimonials' => array(
				'label' => __( 'Online Courses FSE - Testimonials', 'online-courses-fse' ),
			),
			'online-courses-fse-content'      => array(
				'label' => __( 'Online Courses FSE - Content', 'online-courses-fse' ),
			),
			'online-courses-fse-hero'         => array(
				'label' => __( 'Online Courses FSE - Hero', 'online-courses-fse' ),
			),
			'online-courses-fse-404'          => array(
				'label' => __( 'Online Courses FSE - 404', 'online-courses-fse' ),
			),
			'online-courses-fse-search'       => array(
				'label' => __( 'Online Courses FSE - Search', 'online-courses-fse' ),
			),
			'online-courses-fse-archive'      => array(
				'label' => __( 'Online Courses FSE - Archive', 'online-courses-fse' ),
			),
			'online-courses-fse-no-title'     => array(
				'label' => __( 'Online Courses FSE - No Title', 'online-courses-fse' ),
			),
			'online-courses-fse-home'         => array(
				'label' => __( 'Online Courses FSE - Home', 'online-courses-fse' ),
			),
			'online-courses-fse-pages'        => array(
				'label' => __( 'Online Courses FSE - Pages', 'online-courses-fse' ),
			),
			'online-courses-fse-single-post'  => array(
				'label' => __( 'Online Courses FSE - Single Post', 'online-courses-fse' ),
			),
		)
	);

	// Register pattern categories.
	if ( ! empty( $block_pattern_categories ) ) {
		foreach ( $block_pattern_categories as $category_name => $category_properties ) {
			register_block_pattern_category(
				$category_name,
				$category_properties
			);
		}
	}

	/**
	 * Block patterns list.
	 *
	 * These files must exist in: /inc/patterns/{pattern-name}.php
	 *
	 * @since 1.0.2
	 */
	$block_patterns = apply_filters(
		'online_courses_fse_block_patterns',
		array(
			'cta/default',
			'faq/default',
			'instructors/default',
			'about-us/default',
			'reviews/default',
			'pricing/default',
			'partners/default',
			'categories/default',
			'courses/default',
			'features/default',
			'hero/default',
			'testimonials/default',
			'content/content-3',
			'content/content-2',
			'content/content-1',
			'404/default',
			'search/default',
			'archive/default',
			'no-title/default',
			'home/default',
			'pages/default',
			'single-post/default',
		)
	);

	if ( ! empty( $block_patterns ) ) {
		foreach ( $block_patterns as $block_pattern ) {

			$pattern_file = get_theme_file_path( "inc/patterns/$block_pattern.php" );

			if ( file_exists( $pattern_file ) ) {
				$block_pattern_properties = require $pattern_file;

				register_block_pattern(
					"online-courses-fse/$block_pattern",
					$block_pattern_properties
				);
			}
		}
	}
}

add_action( 'init', 'online_courses_fse_register_block_patterns' );
