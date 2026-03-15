<?php
/**
 * Constants class.
 *
 * @package OnlineCoursesFSE
 * @since 1.0.2
 */

namespace OnlineCoursesFSE;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Constants class.
 */
class Constants {

	/**
	 * Product key for the theme.
	 *
	 * @var string
	 */
	const PRODUCT_KEY = 'online_courses_fse';

	/**
	 * Product slug for the theme.
	 *
	 * @var string
	 */
	const PRODUCT_SLUG = 'online-courses-fse';

	/**
	 * Cache keys used throughout the theme.
	 *
	 * @var array
	 */
	const CACHE_KEYS = array(
		'dismissed-welcome-notice' => 'online_courses_fse_dismissed_welcome_notice',
	);

	/**
	 * Text domain for the theme.
	 *
	 * @var string
	 */
	const TEXT_DOMAIN = 'online-courses-fse';
}
