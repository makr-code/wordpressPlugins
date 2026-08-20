(function (wp, window, document) {
    'use strict';

    if (!wp || !wp.customize) {
        return;
    }

    function resetFrontPageSettings() {
        var payload = window.themisdbCustomizerDefaults || {};
        var settings = payload.settings || {};
        var confirmMessage = payload.confirmMessage || 'Reset all front page settings to defaults?';

        if (!window.confirm(confirmMessage)) {
            return;
        }

        Object.keys(settings).forEach(function (settingId) {
            var setting = wp.customize(settingId);
            if (setting) {
                setting.set(settings[settingId]);
            }
        });
    }

    wp.customize.bind('ready', function () {
        document.addEventListener('click', function (event) {
            var trigger = event.target.closest('.themisdb-reset-homepage-settings');
            if (!trigger) {
                return;
            }

            event.preventDefault();
            resetFrontPageSettings();
        });
    });
}(window.wp, window, document));
