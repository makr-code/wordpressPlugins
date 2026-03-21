/**
 * ThemisDB Shopping Cart – Client Logic (Phase 2.3)
 *
 * Depends on: jQuery, themisdbCart (wp_localize_script)
 */
(function ($) {
    'use strict';

    // Guard: bail if not on a cart page.
    if (!window.themisdbCart) return;

    var cfg = window.themisdbCart;

    /* ── Helpers ───────────────────────────────────────────────────── */

    /**
     * Format a raw number as a localised price string.
     * Matches the PHP number_format(x, 2, ',', '.') pattern.
     *
     * @param  {number|string} raw
     * @return {string}  e.g. "1.234,56 €"
     */
    function formatPrice(raw) {
        var n = parseFloat(raw) || 0;
        var parts = n.toFixed(2).split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        return parts.join(',') + '\u00a0' + cfg.currency;
    }

    /* ── Remove item ───────────────────────────────────────────────── */

    $(document).on('click', '.tsc-remove-btn', function () {
        var $btn  = $(this);
        var $row  = $btn.closest('.tsc-row');
        var type  = $btn.data('item-type');
        var code  = $btn.data('item-code');

        if (!type || !code) return;

        $btn.prop('disabled', true).text('…');
        $row.addClass('tsc-is-removing');

        $.ajax({
            url:    cfg.ajaxUrl,
            method: 'POST',
            data: {
                action:    'themisdb_cart_remove_item',
                nonce:     cfg.nonce,
                item_type: type,
                item_code: code,
            },
            success: function (res) {
                if (res && res.success) {
                    // Remove the row from the DOM.
                    $row.remove();

                    // Update the displayed total.
                    var $total = $('.tsc-total-amount');
                    if ($total.length) {
                        $total.data('raw', res.data.new_total);
                        $total.text(formatPrice(res.data.new_total));
                    }

                    // If no module/training rows remain check for empty state.
                    _maybeShowEmptyState();
                } else {
                    _restoreRow($btn, $row);
                    _showError(res && res.data && res.data.message ? res.data.message : cfg.i18n.error);
                }
            },
            error: function () {
                _restoreRow($btn, $row);
                _showError(cfg.i18n.error);
            },
        });
    });

    /* ── Clear cart ────────────────────────────────────────────────── */

    $(document).on('click', '.tsc-clear-btn', function () {
        if (!window.confirm(cfg.i18n.confirmClear)) return;

        var $btn  = $(this);
        var $cart = $btn.closest('.themisdb-shopping-cart');

        $btn.prop('disabled', true).text(cfg.i18n.clearing);

        $.ajax({
            url:    cfg.ajaxUrl,
            method: 'POST',
            data: {
                action: 'themisdb_cart_clear',
                nonce:  cfg.nonce,
            },
            success: function (res) {
                if (res && res.success) {
                    // Replace cart contents with empty-state markup.
                    $cart.html(
                        '<div class="tsc-empty">' +
                        '<p class="tsc-empty-msg">' + _esc(cfg.i18n.empty) + '</p>' +
                        '</div>'
                    );
                } else {
                    $btn.prop('disabled', false).text(_esc(cfg.i18n.clearing.replace('…', '')));
                    _showError(cfg.i18n.error);
                }
            },
            error: function () {
                $btn.prop('disabled', false);
                _showError(cfg.i18n.error);
            },
        });
    });

    /* ── Private helpers ───────────────────────────────────────────── */

    function _restoreRow($btn, $row) {
        $btn.prop('disabled', false).text('\u00d7');
        $row.removeClass('tsc-is-removing');
    }

    function _maybeShowEmptyState() {
        var $cart    = $('.themisdb-shopping-cart');
        var $rows    = $cart.find('.tsc-row--module, .tsc-row--training');
        var $product = $cart.find('.tsc-row--product');

        if ($rows.length === 0 && $product.length === 0) {
            $cart.html(
                '<div class="tsc-empty">' +
                '<p class="tsc-empty-msg">' + _esc(cfg.i18n.empty) + '</p>' +
                '</div>'
            );
        }
    }

    function _showError(msg) {
        var $cart = $('.themisdb-shopping-cart');
        var $err  = $cart.find('.tsc-error-notice');
        var text  = _esc(msg);

        if ($err.length) {
            $err.text(text).show();
        } else {
            $cart.prepend(
                '<p class="tsc-error-notice" style="color:#ef4444;font-weight:600;margin-bottom:.75rem;">' +
                text + '</p>'
            );
        }

        // Auto-hide after 4 s.
        setTimeout(function () {
            $cart.find('.tsc-error-notice').fadeOut(300, function () { $(this).remove(); });
        }, 4000);
    }

    /** Minimal HTML-escape for dynamic text inserted via .html() or prepend(). */
    function _esc(str) {
        return (str + '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

})(jQuery);
