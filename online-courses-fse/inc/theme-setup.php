<?php

/**
 * Theme setup and SDK integration
 *
 * @package OnlineCoursesFSE
 * @since  xx.xx.xx
 */

namespace OnlineCoursesFSE;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ThemeSetup {
	public function __construct() {
		add_filter( 'themegrill_sdk_products', array( $this, 'add_product' ) );
		add_filter( 'online_courses_fse_logger_data', array( $this, 'provide_tracking_data' ) );
		add_filter(
			'online_courses_fse_sdk_enable_logger',
			function ( $enabled ) {
				return 'yes';
			}
		);
	}

	/**
	 * Add the product.
	 *
	 * @param mixed $product The product.
	 * @return array
	 */
	public function add_product( $product ) {
		$product[] = ONLINE_COURSES_FSE_THEME_FILE;
		return $product;
	}

	/**
	 * Callback for SDK tracking filter.
	 * Only sends minimal data including installation info.
	 *
	 * @param array $data Existing tracking data.
	 * @return array Minimal tracking data payload.
	 */
	public function provide_tracking_data( $data ) {
		if ( ! $this->is_usage_allowed() ) {
			return array();
		}

		return $this->get_minimal_data();
	}

	/**
	 * Get minimal tracking data with installation info.
	 *
	 * @return array Minimal tracking data.
	 */
	private function get_minimal_data() {
		$theme = wp_get_theme();

		return array(
			// Installation information
			'site_id'       => $this->get_anonymous_site_id(),
			'install_date'  => $this->get_install_date(),

			// Version information
			'theme_version' => $theme->get( 'Version' ),
			'wp_version'    => $this->get_wp_version_range(),
			'php_version'   => $this->get_php_version_range(),

			// Localization
			'locale'        => get_locale(),
		);
	}

	/**
	 * Get anonymous site identifier.
	 * Uses a hashed value to track unique installations.
	 *
	 * @return string Hashed site identifier.
	 */
	private function get_anonymous_site_id() {
		$site_id = get_option( 'online_courses_fse_site_id' );

		if ( ! $site_id ) {
			$site_id = wp_generate_password( 32, false );
			update_option( 'online_courses_fse_site_id', $site_id );
		}

		return $site_id;
	}

	/**
	 * Get theme installation date.
	 *
	 * @return string Installation date in Y-m-d format.
	 */
	private function get_install_date() {
		$install_date = get_option( 'online_courses_fse_install_date' );

		if ( ! $install_date ) {
			$install_date = current_time( 'Y-m-d' );
			update_option( 'online_courses_fse_install_date', $install_date );
		}

		return $install_date;
	}

	/**
	 * Get WordPress version range.
	 *
	 * @return string WordPress version range (e.g., "6.4").
	 */
	private function get_wp_version_range() {
		global $wp_version;
		$version_parts = explode( '.', $wp_version );

		if ( count( $version_parts ) >= 2 ) {
			return $version_parts[0] . '.' . $version_parts[1];
		}

		return 'unknown';
	}

	/**
	 * Get PHP version range.
	 *
	 * @return string PHP version range (e.g., "8.1").
	 */
	private function get_php_version_range() {
		$php_version   = phpversion();
		$version_parts = explode( '.', $php_version );

		if ( count( $version_parts ) >= 2 ) {
			return $version_parts[0] . '.' . $version_parts[1];
		}

		return 'unknown';
	}

	/**
	 * Check if usage tracking is allowed.
	 *
	 * @return bool Whether tracking is allowed.
	 */
	private function is_usage_allowed() {
		$tracking_allowed = get_option( 'online_courses_fse_allow_tracking', false );
		return apply_filters( 'online_courses_fse_allow_tracking', $tracking_allowed );
	}
}

new ThemeSetup();
