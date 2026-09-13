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

        function applyOverlayLayout() {
            overlay.style.position = 'fixed';
            overlay.style.inset = '0';
            overlay.style.zIndex = '10020';
            overlay.style.display = 'flex';
            overlay.style.alignItems = 'center';
            overlay.style.justifyContent = 'center';
            overlay.style.padding = 'clamp(0.45rem, 1.5vw, 0.95rem)';

            var backdrop = overlay.querySelector('.tv3-login-overlay__backdrop');
            var dialog = overlay.querySelector('.tv3-login-overlay__dialog');

            if (backdrop ) {
                backdrop.style.position = 'absolute';
                backdrop.style.inset = '0';
                backdrop.style.background = 'rgba(8, 16, 28, 0.64)';
                backdrop.style.backdropFilter = 'blur(10px)';
            }

            if (dialog ) {
                dialog.style.position = 'relative';
                dialog.style.zIndex = '1';
                dialog.style.width = 'min(720px, 100%)';
                dialog.style.maxHeight = 'min(82dvh, 760px)';
                dialog.style.overflow = 'auto';
                dialog.style.borderRadius = '18px';
                dialog.style.border = '1px solid rgba(140, 166, 191, 0.2)';
                dialog.style.background = 'rgba(255, 255, 255, 0.98)';
                dialog.style.boxShadow = '0 18px 52px rgba(8, 16, 28, 0.28)';
                dialog.style.padding = '0.7rem 0.75rem 0.8rem';
            }
        }

        function openOverlay() {
            applyOverlayLayout();
            overlay.hidden = false;
            overlay.setAttribute('aria-hidden', 'false');
            overlay.style.display = 'flex';
            body.style.overflow = 'hidden';
            body.style.touchAction = 'none';
            body.classList.add('tv3-login-overlay-open');
        }

        function closeOverlay() {
            overlay.hidden = true;
            overlay.setAttribute('aria-hidden', 'true');
            overlay.style.display = '';
            body.style.overflow = '';
            body.style.touchAction = '';
            body.classList.remove('tv3-login-overlay-open');
        }

        function setActivePanel(panelName) {
            tabButtons.forEach(function (button) {
                var isActive = button.getAttribute('data-tv3-login-tab') === panelName;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-selected', isActive ? 'true' : 'false');
                button.setAttribute('tabindex', isActive ? '0' : '-1');
            });

            panels.forEach(function (panel) {
                var isActive = panel.getAttribute('data-tv3-login-panel') === panelName;
                panel.classList.toggle('is-active', isActive);
                panel.hidden = !isActive;
                panel.style.display = isActive ? 'block' : 'none';
                panel.setAttribute('aria-hidden', isActive ? 'false' : 'true');
            });
        }

        function activateTabByIndex(index) {
            if (!tabButtons.length) {
                return;
            }

            var normalized = ((index % tabButtons.length) + tabButtons.length) % tabButtons.length;
            var target = tabButtons[normalized];
            var panelName = target.getAttribute('data-tv3-login-tab');
            if (!panelName) {
                return;
            }

            setActivePanel(panelName);
            target.focus();
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

        applyOverlayLayout();

        if (tabButtons.length > 0) {
            var selectedTab = overlay.querySelector('[data-tv3-login-tab][aria-selected="true"]');
            var initialPanel = selectedTab ? selectedTab.getAttribute('data-tv3-login-tab') : tabButtons[0].getAttribute('data-tv3-login-tab');
            if (initialPanel) {
                setActivePanel(initialPanel);
            }
        } else if (panels.length === 1) {
            panels[0].hidden = false;
            panels[0].style.display = 'block';
            panels[0].setAttribute('aria-hidden', 'false');
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

            button.addEventListener('keydown', function (event) {
                var currentIndex = Array.prototype.indexOf.call(tabButtons, button);
                if (currentIndex < 0) {
                    return;
                }

                if (event.key === 'ArrowRight') {
                    event.preventDefault();
                    activateTabByIndex(currentIndex + 1);
                } else if (event.key === 'ArrowLeft') {
                    event.preventDefault();
                    activateTabByIndex(currentIndex - 1);
                } else if (event.key === 'Home') {
                    event.preventDefault();
                    activateTabByIndex(0);
                } else if (event.key === 'End') {
                    event.preventDefault();
                    activateTabByIndex(tabButtons.length - 1);
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
