<?php
/**
 * Admin class.
 *
 * @package OnlineCoursesFSE
 * @since 1.0.2
 */

namespace OnlineCoursesFSE;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin class.
 */
class Admin {

	/**
	 * Masteriyo LMS reference key.
	 *
	 * @var string
	 */
	const MASTERIYO_REF = 'masteriyo_reference_key';

	/**
	 * Admin constructor.
	 */
	public function __construct() {
		$this->setup_admin_hooks();
		$this->add_install_time();
	}

	/**
	 * Add the installation time.
	 * This is needed here while the SDK is not available.
	 * Once the SDK is available, this can safely be removed.
	 *
	 * @return void
	 */
	private function add_install_time() {
		$install = get_option( Constants::PRODUCT_KEY . '_install', 0 );
		if ( 0 === $install ) {
			update_option( Constants::PRODUCT_KEY . '_install', time() );
		}
	}

	/**
	 * Setup admin hooks.
	 *
	 * @return void
	 */
	public function setup_admin_hooks() {
		add_action( 'admin_notices', array( $this, 'render_welcome_notice' ), 0 );
		add_action( 'activated_plugin', array( $this, 'after_masteriyo_activation' ) );
		add_action( 'wp_ajax_online_courses_fse_dismiss_welcome_notice', array( $this, 'remove_welcome_notice' ) );
		add_action( 'wp_ajax_online_courses_fse_set_masteriyo_ref', array( $this, 'set_masteriyo_ref' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_internal_page' ) );
	}

	/**
	 * Render the welcome notice.
	 *
	 * @return void
	 */
	public function render_welcome_notice() {
		if ( ! $this->should_show_welcome_notice() ) {
			return;
		}

		$masteriyo_status = $this->get_masteriyo_status();

		Assets_Manager::enqueue_style( Assets_Manager::ASSETS_SLUGS['welcome-notice'], 'welcome-notice' );
		Assets_Manager::enqueue_script(
			Assets_Manager::ASSETS_SLUGS['welcome-notice'],
			'welcome-notice',
			true,
			array(),
			array(
				'nonce'             => wp_create_nonce( 'online-courses-fse-dismiss-welcome-notice' ),
				'masteriyoRefNonce' => wp_create_nonce( 'online-courses-fse-set-masteriyo-ref' ),
				'ajaxUrl'           => esc_url( admin_url( 'admin-ajax.php' ) ),
				'masteriyoStatus'   => $masteriyo_status,
				'activationUrl'     => esc_url(
					add_query_arg(
						array(
							'plugin_status' => 'all',
							'paged'         => '1',
							'action'        => 'activate',
							'plugin'        => rawurlencode( 'learning-management-system/lms.php' ),
							'_wpnonce'      => wp_create_nonce( 'activate-plugin_learning-management-system/lms.php' ),
						),
						admin_url( 'plugins.php' )
					)
				),
				'redirectUrl'       => esc_url( admin_url( 'admin.php?page=masteriyo-onboard' ) ),
				'activating'        => __( 'Activating', 'online-courses-fse' ) . '&hellip;',
				'installing'        => __( 'Installing', 'online-courses-fse' ) . '&hellip;',
				'done'              => __( 'Done', 'online-courses-fse' ),
			)
		);

		$notice_html  = '<div class="notice notice-info online-courses-fse-welcome-notice">';
		$notice_html .= '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>';
		$notice_html .= '<div class="notice-content">';

		$notice_html .= '<div class="notice-copy">';

		$notice_html .= '<h2 class="notice-subtitle">';
		$notice_html .= '<span class="dashicons dashicons-welcome-learn-more"></span>';
		$notice_html .= __( 'This theme works best with Masteriyo LMS', 'online-courses-fse' );
		$notice_html .= '</h2>';

		$notice_html .= '<h1 class="notice-title">';
		/* translators: %s: Masteriyo LMS */
		$notice_html .= sprintf( __( 'Create and Sell Online Courses with %s!', 'online-courses-fse' ), '<span>Masteriyo LMS</span>' );
		$notice_html .= '</h1>';

		$notice_html .= '<p class="description">' . __( 'The complete WordPress LMS solution for educators and businesses. Build engaging courses, track student progress, and monetize your knowledge with zero coding required.', 'online-courses-fse' ) . '</p>';
		$notice_html .= '<p class="description"><span class="dashicons dashicons-yes"></span><strong>' . __( 'Quick setup', 'online-courses-fse' ) . '</strong> - ' . __( 'Install and create your first course in minutes', 'online-courses-fse' ) . '</p>';
		$notice_html .= '<p class="description"><span class="dashicons dashicons-yes"></span><strong>' . __( 'Multiple payment options', 'online-courses-fse' ) . '</strong> - ' . __( 'Built-in payment system with Stripe, PayPal & more', 'online-courses-fse' ) . '</p>';
		$notice_html .= '<p class="description"><span class="dashicons dashicons-yes"></span><strong>' . __( 'Complete course builder', 'online-courses-fse' ) . '</strong> - ' . __( 'Drag & drop builder with quizzes, certificates & assignments', 'online-courses-fse' ) . '</p>';

		$notice_html .= '<div class="actions">';

		$notice_html .= '<button id="online-courses-fse-install-masteriyo" class="button button-primary button-hero">';
		$notice_html .= '<span class="dashicons dashicons-update hidden"></span>';
		$notice_html .= '<span class="text">';
		$notice_html .= 'installed' === $masteriyo_status ?
			/* translators: %s: Masteriyo LMS */
			sprintf( __( 'Activate %s', 'online-courses-fse' ), 'Masteriyo LMS' ) :
			/* translators: %s: Masteriyo LMS */
			sprintf( __( 'Install & Activate %s', 'online-courses-fse' ), 'Masteriyo LMS' );
		$notice_html .= '</span>';
		$notice_html .= '</button>';

		$notice_html .= '<a href="https://wordpress.org/plugins/learning-management-system/" target="_blank" class="button button-secondary button-hero">';
		$notice_html .= '<span>' . __( 'Learn More', 'online-courses-fse' ) . '</span>';
		$notice_html .= '<span class="dashicons dashicons-external"></span>';
		$notice_html .= '</a>';

		$notice_html .= '</div>';

		$notice_html .= '</div>';

		$notice_html .= '<img class="masteriyo-preview" src="' . esc_url( Assets_Manager::get_image_url( 'welcome-notice.png' ) ) . '" alt="' . esc_attr__( 'Masteriyo LMS preview', 'online-courses-fse' ) . '"/>';
		$notice_html .= '</div>';
		$notice_html .= '</div>';

		echo wp_kses_post( $notice_html );
	}

	/**
	 * Dismiss the welcome notice.
	 *
	 * @return void
	 */
	public function remove_welcome_notice() {
		if ( ! isset( $_POST['nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'online-courses-fse-dismiss-welcome-notice' ) ) {
			return;
		}
		update_option( Constants::CACHE_KEYS['dismissed-welcome-notice'], 'yes' );
		wp_die();
	}

	/**
	 * Should we show the welcome notice?
	 *
	 * @return bool
	 */
	private function should_show_welcome_notice(): bool {
		// Already using Masteriyo.
		if ( is_plugin_active( 'learning-management-system/lms.php' ) ) {
			return false;
		}

		// Notice was dismissed.
		if ( get_option( Constants::CACHE_KEYS['dismissed-welcome-notice'], 'no' ) === 'yes' ) {
			return false;
		}

		$screen = get_current_screen();

		// Only show in dashboard/themes.
		if ( ! in_array( $screen->id, array( 'dashboard', 'themes' ), true ) ) {
			return false;
		}

		// AJAX actions.
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return false;
		}

		// Don't show in network admin.
		if ( is_network_admin() ) {
			return false;
		}

		// User can't dismiss. We don't show it.
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		// User can't install plugins. We don't show it.
		if ( ! current_user_can( 'install_plugins' ) ) {
			return false;
		}

		// Block editor context.
		if ( $screen->is_block_editor() ) {
			return false;
		}

		// Dismiss after one week from activation.
		$activated_time = get_option( 'online_courses_fse_install' );

		if ( ! empty( $activated_time ) && time() - intval( $activated_time ) > WEEK_IN_SECONDS ) {
			update_option( Constants::CACHE_KEYS['dismissed-welcome-notice'], 'yes' );
			return false;
		}

		return true;
	}

	/**
	 * Get the Masteriyo LMS plugin status.
	 *
	 * @return string
	 */
	private function get_masteriyo_status(): string {
		$status = 'not-installed';

		if ( is_plugin_active( 'learning-management-system/lms.php' ) ) {
			return 'active';
		}

		if ( file_exists( ABSPATH . 'wp-content/plugins/learning-management-system/lms.php' ) ) {
			return 'installed';
		}

		return $status;
	}

	/**
	 * Run after Masteriyo LMS activation.
	 *
	 * @param string $plugin Plugin name.
	 *
	 * @return void
	 */
	public function after_masteriyo_activation( $plugin ) {
		if ( 'learning-management-system/lms.php' !== $plugin ) {
			return;
		}

		update_option( Constants::CACHE_KEYS['dismissed-welcome-notice'], 'yes' );
		exit;
	}

	/**
	 * Update Masteriyo LMS reference key.
	 *
	 * @return void
	 */
	public function set_masteriyo_ref() {
		if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'online-courses-fse-set-masteriyo-ref' ) ) {
			return;
		}

		update_option( self::MASTERIYO_REF, 'online-courses-fse' );

		wp_send_json_success();
	}

	/**
	 * Register internal pages.
	 *
	 * @return void
	 */
	public function register_internal_page() {
		$screen = get_current_screen();

		if ( 'dashboard' !== $screen->id && 'themes' !== $screen->id ) {
			return;
		}

		do_action( 'themegrill_internal_page', Constants::PRODUCT_SLUG, $screen->id );
	}
}

// Initialize Admin class.
new Admin();
