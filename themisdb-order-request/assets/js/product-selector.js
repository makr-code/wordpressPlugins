/**
 * ThemisDB Product Detail Page – Live Pricing Calculator
 *
 * Depends on: jQuery
 * Localized data: themisdbProductSelector (see class-shortcodes.php)
 *
 * @version 1.0.0
 */
jQuery(function ($) {
    'use strict';

    var cfg = window.themisdbProductSelector || {};
    var products   = cfg.products   || {};   // { edition: { price, name } }
    var modules    = cfg.modules    || {};   // { module_code: { price, name, category } }
    var trainings  = cfg.trainings  || {};   // { training_code: { price, name, type } }
    var defaultModules  = Array.isArray(cfg.defaultModules) ? cfg.defaultModules : [];
    var defaultTraining = Array.isArray(cfg.defaultTraining) ? cfg.defaultTraining : [];
    var orderUrl   = cfg.orderUrl   || '';
    var currency   = cfg.currency   || '€';
    var i18n       = cfg.i18n       || {};

    // ─── State ────────────────────────────────────────────────────────────────

    var state = {
        edition:          cfg.defaultEdition || '',
        selectedModules:  [],
        selectedTraining: [],
    };

    // ─── DOM refs ─────────────────────────────────────────────────────────────

    var $root            = $('.themisdb-product-detail');
    var $editionCards    = $root.find('.tpd-edition-card');
    var $moduleChecks    = $root.find('.tpd-module-check');
    var $trainingChecks  = $root.find('.tpd-training-check');
    var $totalDisplay    = $root.find('.tpd-total-amount');
    var $baseDisplay     = $root.find('.tpd-base-price');
    var $orderBtn        = $root.find('.tpd-order-btn');
    var $summaryList     = $root.find('.tpd-price-summary');

    // ─── Formatting ───────────────────────────────────────────────────────────

    function formatPrice(val) {
        var num = parseFloat(val) || 0;
        return num.toLocaleString('de-DE', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }) + '\u00a0' + currency;
    }

    // ─── Recalculate total ────────────────────────────────────────────────────

    function recalculate() {
        var base  = 0;
        var extra = 0;
        var rows  = [];

        // Base product price.
        if (state.edition && products[state.edition]) {
            base = parseFloat(products[state.edition].price) || 0;
            if (base > 0) {
                rows.push({
                    label: products[state.edition].name,
                    price: base,
                });
            } else {
                rows.push({
                    label: products[state.edition].name,
                    price: 0,
                    free: true,
                });
            }
        }

        // Selected modules.
        $.each(state.selectedModules, function (_, code) {
            if (modules[code]) {
                var p = parseFloat(modules[code].price) || 0;
                extra += p;
                rows.push({ label: modules[code].name, price: p });
            }
        });

        // Selected training.
        $.each(state.selectedTraining, function (_, code) {
            if (trainings[code]) {
                var p = parseFloat(trainings[code].price) || 0;
                extra += p;
                rows.push({ label: trainings[code].name, price: p });
            }
        });

        var total = base + extra;

        // Update base display.
        if ($baseDisplay.length) {
            $baseDisplay.text(base > 0 ? formatPrice(base) : (i18n.free || 'Kostenlos'));
        }

        // Update total display.
        if ($totalDisplay.length) {
            $totalDisplay.text(formatPrice(total));
        }

        // Rebuild summary list.
        if ($summaryList.length) {
            $summaryList.empty();
            $.each(rows, function (_, row) {
                var priceText = row.free ? (i18n.free || 'Kostenlos') : '+\u00a0' + formatPrice(row.price);
                $summaryList.append(
                    $('<li>').append(
                        $('<span class="tpd-summary-label">').text(row.label),
                        $('<span class="tpd-summary-price">').text(priceText)
                    )
                );
            });
        }

        // Update order button URL.
        if ($orderBtn.length && orderUrl) {
            var url = orderUrl;
            var params = [];
            if (state.edition) {
                params.push('edition=' + encodeURIComponent(state.edition));
            }
            if (state.selectedModules.length) {
                params.push('modules=' + encodeURIComponent(state.selectedModules.join(',')));
            }
            if (state.selectedTraining.length) {
                params.push('training=' + encodeURIComponent(state.selectedTraining.join(',')));
            }
            if (params.length) {
                params.push('checkout=1');
                url += (url.indexOf('?') === -1 ? '?' : '&') + params.join('&');
            }
            $orderBtn.attr('href', url);
            $orderBtn.toggleClass('tpd-order-btn--ready', !!state.edition);
        }
    }

    function applyPresetSelections() {
        if (defaultModules.length) {
            $moduleChecks.each(function () {
                var code = $(this).val();
                if (defaultModules.includes(code)) {
                    $(this).prop('checked', true);
                    $(this).closest('.tpd-module-item').addClass('tpd-module-item--selected');
                    if (!state.selectedModules.includes(code)) {
                        state.selectedModules.push(code);
                    }
                }
            });
        }

        if (defaultTraining.length) {
            $trainingChecks.each(function () {
                var code = $(this).val();
                if (defaultTraining.includes(code)) {
                    $(this).prop('checked', true);
                    $(this).closest('.tpd-training-item').addClass('tpd-training-item--selected');
                    if (!state.selectedTraining.includes(code)) {
                        state.selectedTraining.push(code);
                    }
                }
            });
        }
    }

    // ─── Edition selector ─────────────────────────────────────────────────────

    $editionCards.on('click', function () {
        var newEdition = $(this).data('edition');
        if (!newEdition) return;

        state.edition = newEdition;
        $editionCards.removeClass('tpd-edition-card--selected');
        $(this).addClass('tpd-edition-card--selected');
        $(this).find('input[type="radio"]').prop('checked', true);

        // Show/hide edition-restricted modules.
        $root.find('[data-editions]').each(function () {
            var allowed = String($(this).data('editions')).split(',');
            $(this).closest('.tpd-module-item, .tpd-training-item')
                .toggleClass('tpd-item--disabled', allowed.length > 0 && !allowed.includes(newEdition));
        });

        // Deselect modules that are incompatible with the new edition.
        $moduleChecks.each(function () {
            var $cb = $(this);
            var allowed = String($cb.data('editions') || '').split(',').filter(Boolean);
            if (allowed.length && !allowed.includes(newEdition)) {
                $cb.prop('checked', false);
                $cb.closest('.tpd-module-item').addClass('tpd-item--disabled');
            }
        });

        state.selectedModules = [];
        $moduleChecks.filter(':checked').each(function () {
            state.selectedModules.push($(this).val());
        });

        recalculate();
    });

    // ─── Module selector ──────────────────────────────────────────────────────

    $moduleChecks.on('change', function () {
        var code = $(this).val();
        if ($(this).is(':checked')) {
            if (!state.selectedModules.includes(code)) {
                state.selectedModules.push(code);
            }
            $(this).closest('.tpd-module-item').addClass('tpd-module-item--selected');
        } else {
            state.selectedModules = state.selectedModules.filter(function (c) { return c !== code; });
            $(this).closest('.tpd-module-item').removeClass('tpd-module-item--selected');
        }
        recalculate();
    });

    // ─── Training selector ────────────────────────────────────────────────────

    $trainingChecks.on('change', function () {
        var code = $(this).val();
        if ($(this).is(':checked')) {
            if (!state.selectedTraining.includes(code)) {
                state.selectedTraining.push(code);
            }
            $(this).closest('.tpd-training-item').addClass('tpd-training-item--selected');
        } else {
            state.selectedTraining = state.selectedTraining.filter(function (c) { return c !== code; });
            $(this).closest('.tpd-training-item').removeClass('tpd-training-item--selected');
        }
        recalculate();
    });

    // ─── Support table expand/collapse ────────────────────────────────────────

    $root.find('.tpd-support-toggle').on('click', function () {
        var $table = $root.find('.tpd-support-table');
        var isExpanded = $table.hasClass('tpd-support-table--visible');
        $table.toggleClass('tpd-support-table--visible', !isExpanded);
        $(this).text(isExpanded ? (i18n.showSupport || 'Support-Details anzeigen') : (i18n.hideSupport || 'Support-Details ausblenden'));
    });

    // ─── Init ─────────────────────────────────────────────────────────────────

    // Trigger initial selection on pre-selected edition card.
    var $preselected = $editionCards.filter('.tpd-edition-card--selected').first();
    if ($preselected.length) {
        $preselected.trigger('click');
    } else if ($editionCards.length) {
        $editionCards.first().trigger('click');
    }

    applyPresetSelections();
    recalculate();
});
