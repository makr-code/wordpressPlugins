<?php
/**
 * Assets Manager
 *
 * Helper to resolve theme asset URLs and enqueue scripts/styles
 *
 * @package OnlineCoursesFSE
 */

namespace OnlineCoursesFSE;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Assets_Manager {

	/**
	 * Assets slugs.
	 *
	 * @var array
	 */
	const ASSETS_SLUGS = array(
		'welcome-notice' => 'online-courses-fse-welcome-notice',
	);

	/**
	 * Get image URL from theme assets folder.
	 *
	 * Looks in: /assets/images/<$filename>
	 * If file doesn't exist and the passed $filename looks like a URL, returns it.
	 *
	 * @param string $filename Filename (e.g. 'cta-scaled.jpg') or full URL.
	 * @return string Escaped URL.
	 */
	public static function get_image_url( string $file ): string {
		return trailingslashit( get_template_directory_uri() ) . 'assets/images/' . $file;
	}

	/**
	 * Enqueue style.
	 *
	 * @param string $handle The style handle.
	 * @param string $file   The file name without extension.
	 * @param array  $deps   Dependencies.
	 * @return void
	 */
	public static function enqueue_style( $handle, $file, $deps = array() ) {
		$file_path = get_template_directory() . '/assets/css/' . $file . '.css';
		$file_uri  = get_template_directory_uri() . '/assets/css/' . $file . '.css';

		if ( file_exists( $file_path ) ) {
			wp_enqueue_style(
				$handle,
				$file_uri,
				$deps,
				filemtime( $file_path )
			);
		}
	}

	/**
	 * Enqueue script.
	 *
	 * @param string $handle        The script handle.
	 * @param string $file          The file name without extension.
	 * @param bool   $in_footer     Whether to enqueue in footer.
	 * @param array  $deps          Dependencies.
	 * @param array  $localize_data Data to localize.
	 * @return void
	 */
	public static function enqueue_script( $handle, $file, $in_footer = true, $deps = array(), $localize_data = array() ) {
		$file_path = get_template_directory() . '/assets/js/' . $file . '.js';
		$file_uri  = get_template_directory_uri() . '/assets/js/' . $file . '.js';

		if ( file_exists( $file_path ) ) {
			wp_enqueue_script(
				$handle,
				$file_uri,
				array_merge( array( 'jquery' ), $deps ),
				filemtime( $file_path ),
				$in_footer
			);

			if ( ! empty( $localize_data ) ) {
				wp_localize_script( $handle, str_replace( '-', '_', $handle ) . '_params', $localize_data );
			}
		}
	}
}
