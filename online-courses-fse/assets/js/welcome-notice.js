/**
 * Welcome Notice JavaScript
 *
 * Handles Masteriyo LMS installation and activation from the welcome notice.
 *
 * @package OnlineCoursesFSE
 */

(function ($) {
	'use strict';

	const welcomeNotice = {
		/**
		 * Initialize the welcome notice.
		 */
		init: function () {
			this.params = window.online_courses_fse_welcome_notice_params || {};
			this.bindEvents();
		},

		/**
		 * Bind events.
		 */
		bindEvents: function () {
			const self = this;

			// Handle dismiss button.
			$('.online-courses-fse-welcome-notice .notice-dismiss').on('click', function (e) {
				e.preventDefault();
				self.dismissNotice();
			});

			// Handle install/activate button.
			$('#online-courses-fse-install-masteriyo').on('click', function (e) {
				e.preventDefault();
				self.handleMasteriyoInstall($(this));
			});
		},

		/**
		 * Dismiss the welcome notice.
		 */
		dismissNotice: function () {
			const self = this;

			$.ajax({
				url: self.params.ajaxUrl,
				type: 'POST',
				data: {
					action: 'online_courses_fse_dismiss_welcome_notice',
					nonce: self.params.nonce,
				},
				success: function () {
					$('.online-courses-fse-welcome-notice').fadeOut();
				},
			});
		},

		/**
		 * Handle Masteriyo installation/activation.
		 *
		 * @param {jQuery} $button The button element.
		 */
		handleMasteriyoInstall: function ($button) {
			const self = this;
			const status = self.params.masteriyoStatus;

			// Disable button and show loading state.
			$button.prop('disabled', true);
			$button.find('.dashicons').removeClass('hidden');

			if (status === 'installed') {
				// Just activate the plugin.
				self.activateMasteriyo($button);
			} else {
				// Install and then activate.
				self.installMasteriyo($button);
			}
		},

		/**
		 * Install Masteriyo LMS.
		 *
		 * @param {jQuery} $button The button element.
		 */
		installMasteriyo: function ($button) {
			const self = this;

			$button.find('.text').text(self.params.installing);

			wp.updates.ajax('install-plugin', {
				slug: 'learning-management-system',
				success: function () {
					// Plugin installed, now activate it.
					self.activateMasteriyo($button);
				},
				error: function (error) {
					console.error('Installation failed:', error);
					$button.find('.dashicons').addClass('hidden');
					$button.prop('disabled', false);
					alert('Failed to install Masteriyo LMS. Please try again or install manually.');
				},
			});
		},

		/**
		 * Activate Masteriyo LMS.
		 *
		 * @param {jQuery} $button The button element.
		 */
		activateMasteriyo: function ($button) {
			const self = this;

			$button.find('.text').text(self.params.activating);

			// Set the reference before activation.
			self.setMasteriyoReference(function () {
				// Redirect to activation URL.
				window.location.href = self.params.activationUrl;
			});
		},

		/**
		 * Set Masteriyo reference key.
		 *
		 * @param {Function} callback Callback function after setting reference.
		 */
		setMasteriyoReference: function (callback) {
			const self = this;

			$.ajax({
				url: self.params.ajaxUrl,
				type: 'POST',
				data: {
					action: 'online_courses_fse_set_masteriyo_ref',
					nonce: self.params.masteriyoRefNonce,
				},
				success: function () {
					if (callback && typeof callback === 'function') {
						callback();
					}
				},
				error: function () {
					// Continue even if reference setting fails.
					if (callback && typeof callback === 'function') {
						callback();
					}
				},
			});
		},
	};

	// Initialize on document ready.
	$(document).ready(function () {
		welcomeNotice.init();
	});
})(jQuery);
