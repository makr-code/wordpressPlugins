/**
 * ThemisDB Admin Dashboard – Client Logic (Phase 4.1)
 *
 * Handles dashboard widget interactions:
 * - Refresh metrics via AJAX
 * - Auto-refresh timer
 *
 * @version 1.0.0
 */
(function ($) {
    'use strict';

    // Guard: bail if data not localized
    if (!window.themisdbDashboard) return;

    var cfg = window.themisdbDashboard;
    var refreshInterval = 5 * 60 * 1000; // 5 minutes default
    var refreshTimers = {};

    /**
     * Refresh a single widget via AJAX.
     *
     * @param {string} widget - Widget identifier
     * @param {jQuery} $button - Refresh button element (optional)
     */
    function refreshWidget(widget, $button) {
        if (!$button) {
            $button = $('[data-widget="' + widget + '"]');
        }

        if ($button.length) {
            $button.prop('disabled', true).text('...');
        }

        $.ajax({
            url: cfg.ajaxUrl,
            method: 'POST',
            data: {
                action: 'themisdb_dashboard_refresh',
                nonce: cfg.nonce,
                widget: widget,
            },
            success: function (res) {
                if (res && res.success && res.data && res.data.html) {
                    // Find the widget container and replace its content
                    var selector = '#dashboard-widget-themisdb-' + widget + ' .inside';
                    var $container = $(selector);

                    if ($container.length) {
                        $container.fadeOut(200, function () {
                            $(this).html(res.data.html).fadeIn(200);
                            // Re-bind refresh buttons
                            bindRefreshButtons();
                        });
                    }
                }

                if ($button.length) {
                    var btnText = $button.data('originalText') || 'Aktualisieren';
                    $button.prop('disabled', false).text(btnText);
                }
            },
            error: function () {
                if ($button.length) {
                    $button.prop('disabled', false).text('Fehler');
                    setTimeout(function () {
                        var btnText = $button.data('originalText') || 'Aktualisieren';
                        $button.text(btnText);
                    }, 2000);
                }
            },
        });
    }

    /**
     * Bind click handlers to all refresh buttons.
     */
    function bindRefreshButtons() {
        $('.themisdb-dashboard-refresh').each(function () {
            var $btn = $(this);
            if (!$btn.data('bound')) {
                $btn.data('originalText', $btn.text());
                $btn.on('click', function () {
                    var widget = $(this).data('widget');
                    if (widget) {
                        refreshWidget(widget, $(this));
                    }
                });
                $btn.data('bound', true);
            }
        });
    }

    /**
     * Setup automatic refresh intervals for all widgets.
     */
    function setupAutoRefresh() {
        var widgets = ['orders', 'revenue', 'support', 'license', 'health'];

        $.each(widgets, function (_, widget) {
            // Clear existing timer if any
            if (refreshTimers[widget]) {
                clearInterval(refreshTimers[widget]);
            }

            // Set new timer (refresh every 5 minutes)
            refreshTimers[widget] = setInterval(function () {
                refreshWidget(widget);
            }, refreshInterval);
        });
    }

    /**
     * Initialize dashboard.
     */
    function init() {
        bindRefreshButtons();
        setupAutoRefresh();
    }

    // Initialize on document ready
    $(document).ready(function () {
        init();
    });

    // Cleanup on page unload
    $(window).on('beforeunload', function () {
        $.each(refreshTimers, function (_, timer) {
            if (timer) {
                clearInterval(timer);
            }
        });
    });

})(jQuery);
