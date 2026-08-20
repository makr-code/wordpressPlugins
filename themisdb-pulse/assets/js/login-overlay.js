( function () {
    'use strict';

    function ready(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
            return;
        }
        fn();
    }

    ready(function () {
        var cfg = window.themisdbV3LoginOverlay || {};
        var overlay = document.getElementById('tv3-login-overlay');
        var triggerLink = document.querySelector('.tv3-header-login a');

        if (triggerLink && cfg.isLoggedIn) {
            if (cfg.supportUrl) {
                triggerLink.setAttribute('href', cfg.supportUrl);
            }
            triggerLink.textContent = 'Portal';
            triggerLink.setAttribute('aria-label', 'Zum Support-Portal');
        }

        if (!overlay) {
            return;
        }

        var body = document.body;
        var closeTargets = overlay.querySelectorAll('[data-tv3-login-close]');
        var tabButtons = overlay.querySelectorAll('[data-tv3-login-tab]');
        var panels = overlay.querySelectorAll('[data-tv3-login-panel]');

        function openOverlay() {
            overlay.hidden = false;
            overlay.setAttribute('aria-hidden', 'false');
            body.classList.add('tv3-login-overlay-open');
        }

        function closeOverlay() {
            overlay.hidden = true;
            overlay.setAttribute('aria-hidden', 'true');
            body.classList.remove('tv3-login-overlay-open');
        }

        function setActivePanel(panelName) {
            tabButtons.forEach(function (button) {
                var isActive = button.getAttribute('data-tv3-login-tab') === panelName;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });

            panels.forEach(function (panel) {
                var isActive = panel.getAttribute('data-tv3-login-panel') === panelName;
                panel.classList.toggle('is-active', isActive);
            });
        }

        if (triggerLink) {
            triggerLink.addEventListener('click', function (event) {
                if (cfg.isLoggedIn) {
                    return;
                }
                event.preventDefault();
                openOverlay();
            });
        }

        closeTargets.forEach(function (el) {
            el.addEventListener('click', function () {
                closeOverlay();
            });
        });

        tabButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                var panelName = button.getAttribute('data-tv3-login-tab');
                if (panelName) {
                    setActivePanel(panelName);
                }
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !overlay.hidden) {
                closeOverlay();
            }
        });
    });
}() );
