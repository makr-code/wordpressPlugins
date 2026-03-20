/* ThemisDB Order Request Plugin - Admin JavaScript */

jQuery(document).ready(function($) {
    'use strict';

    function isModifiedClick(event) {
        return event.metaKey || event.ctrlKey || event.shiftKey || event.altKey;
    }

    function parseWrapFromHtml(html) {
        var parsed = $.parseHTML(html, document, true);
        if (!parsed) {
            return $();
        }

        var $container = $('<div></div>').append(parsed);
        return $container.find('.wrap').first();
    }

    function replaceWrapContent($newWrap) {
        if (!$newWrap.length) {
            return false;
        }

        var $currentWrap = $('.wrap').first();
        if (!$currentWrap.length) {
            return false;
        }

        $currentWrap.replaceWith($newWrap);
        return true;
    }

    function loadAdminUrl(url, pushState) {
        var separator = url.indexOf('?') === -1 ? '?' : '&';
        var ajaxUrl = url + separator + 'themisdb_ajax=1';

        $('body').addClass('themisdb-loading');

        $.get(ajaxUrl)
            .done(function(response) {
                var $newWrap = parseWrapFromHtml(response);
                if (!replaceWrapContent($newWrap)) {
                    window.location.href = url;
                    return;
                }

                if (pushState) {
                    window.history.pushState({ themisdbAdminUrl: url }, '', url);
                }
            })
            .fail(function() {
                window.location.href = url;
            })
            .always(function() {
                $('body').removeClass('themisdb-loading');
            });
    }

    $(document).on('click', 'a.themisdb-ajax-link', function(event) {
        var href = $(this).attr('href');

        if (!href || isModifiedClick(event) || this.target === '_blank') {
            return;
        }

        if (href.indexOf('admin.php?page=themisdb-') === -1) {
            return;
        }

        event.preventDefault();
        loadAdminUrl(href, true);
    });

    $(document).on('submit', 'form.themisdb-ajax-form', function(event) {
        var $form = $(this);
        var method = ($form.attr('method') || 'get').toLowerCase();

        if (method !== 'get') {
            return;
        }

        event.preventDefault();

        var action = $form.attr('action') || window.location.pathname;
        var query = $form.serialize();
        var url = action;

        if (query) {
            url += (url.indexOf('?') === -1 ? '?' : '&') + query;
        }

        loadAdminUrl(url, true);
    });

    $(document).on('change', 'select.themisdb-auto-submit', function() {
        var $form = $(this).closest('form');
        if ($form.length) {
            $form.trigger('submit');
        }
    });

    window.addEventListener('popstate', function(event) {
        if (!event.state || !event.state.themisdbAdminUrl) {
            return;
        }

        loadAdminUrl(event.state.themisdbAdminUrl, false);
    });

    // ===== Compliance-Formular-Helfer =====

    // Auto-Uppercase für Land-ISO-Felder (Admin create/edit)
    $(document).on('input', '#billing_country, #shipping_country', function() {
        var val = $(this).val().toUpperCase().replace(/[^A-Z]/g, '').substring(0, 2);
        $(this).val(val);
    });

    // B2B-Felder: customer_company-Zeile ein-/ausblenden je nach customer_type (Admin-Select)
    function syncB2bRow() {
        var type = $('#customer_type').val();
        var $companyRow = $('#customer_company').closest('tr');
        var $vatRow = $('#vat_id').closest('tr');
        if (type === 'business') {
            $companyRow.show();
            $vatRow.show();
        } else {
            $companyRow.hide();
            $vatRow.hide();
        }
    }

    $(document).on('change', '#customer_type', syncB2bRow);

    // Initial-Zustand beim Laden der Seite setzen
    if ($('#customer_type').length) {
        syncB2bRow();
    }
});
