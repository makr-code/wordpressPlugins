/**
 * ThemisDB Support Portal – Admin JavaScript
 *
 * Handles:
 *  - Admin reply form submission via AJAX
 *  - Ticket status change via AJAX (select change on ticket detail page)
 */
(function ($) {
    'use strict';

    var settings = window.themisdbSupportAdmin || {};

    // -------------------------------------------------------------------------
    // Utility
    // -------------------------------------------------------------------------

    function showMsg($container, type, text) {
        var cssClass = type === 'success'
            ? 'themisdb-support-admin-msg-success'
            : 'themisdb-support-admin-msg-error';
        $container.html(
            '<p class="' + cssClass + '">' + $('<span>').text(text).html() + '</p>'
        );
    }

    // -------------------------------------------------------------------------
    // Admin reply
    // -------------------------------------------------------------------------

    var $replyForm = $('#themisdb-support-admin-reply-form');
    var $replyMessages = $('#themisdb-support-admin-reply-messages');

    $replyForm.on('submit', function (e) {
        e.preventDefault();

        var ticketId = $('input[name="ticket_id"]', this).val();
        var message  = $('#admin-reply-message').val().trim();

        if (!message) {
            showMsg($replyMessages, 'error', 'Bitte geben Sie eine Antwort ein.');
            return;
        }

        var $btn = $('#themisdb-support-admin-reply-btn');
        $btn.prop('disabled', true).text(settings.strings.replying || '…');
        $replyMessages.empty();

        $.ajax({
            url:  settings.ajaxUrl,
            type: 'POST',
            data: {
                action:    'themisdb_support_admin_reply',
                ticket_id: ticketId,
                message:   message,
                nonce:     settings.nonce,
            },
            success: function (response) {
                if (response.success) {
                    showMsg($replyMessages, 'success', response.data.message);
                    // Reload page to show the new message in the thread
                    setTimeout(function () { window.location.reload(); }, 1000);
                } else {
                    showMsg($replyMessages, 'error', response.data.message);
                    $btn.prop('disabled', false).text(settings.strings.send_reply);
                }
            },
            error: function () {
                showMsg($replyMessages, 'error', 'Ein Fehler ist aufgetreten.');
                $btn.prop('disabled', false).text(settings.strings.send_reply);
            },
        });
    });

    // -------------------------------------------------------------------------
    // Ticket status change
    // -------------------------------------------------------------------------

    var $statusSelect = $('#themisdb-admin-change-status');
    var $statusMsg    = $('#themisdb-admin-status-message');

    $statusSelect.on('change', function () {
        var ticketId = $(this).data('ticket-id');
        var status   = $(this).val();

        $statusMsg.empty();

        $.ajax({
            url:  settings.ajaxUrl,
            type: 'POST',
            data: {
                action:    'themisdb_support_admin_status',
                ticket_id: ticketId,
                status:    status,
                nonce:     settings.nonce,
            },
            success: function (response) {
                if (response.success) {
                    showMsg($statusMsg, 'success', response.data.message);
                } else {
                    showMsg($statusMsg, 'error', response.data.message);
                }
            },
            error: function () {
                showMsg($statusMsg, 'error', 'Ein Fehler ist aufgetreten.');
            },
        });
    });

    // -------------------------------------------------------------------------
    // Bulk selection + action safety
    // -------------------------------------------------------------------------

    var $bulkForm = $('.themisdb-support-bulk-form');
    var $bulkPreviewTop = $('.themisdb-support-bulk-preview[data-preview-source="top"]');
    var $bulkPreviewBottom = $('.themisdb-support-bulk-preview[data-preview-source="bottom"]');
    var $showSelectedOnlyToggles = $('.themisdb-support-show-selected-only');

    function updateBulkPreview(source) {
        var selectedCount = $('.themisdb-support-ticket-checkbox:checked').length;
        var $selector = source === 'bottom' ? $('#bulk-action-selector-bottom') : $('#bulk-action-selector-top');
        var actionText = $selector.find('option:selected').text();
        var hasAction = !!$selector.val();

        if (!hasAction) {
            actionText = 'Keine Bulk-Aktion ausgewaehlt.';
        } else {
            actionText = selectedCount + ' Ticket(s) -> ' + actionText;
        }

        if (source === 'bottom') {
            $bulkPreviewBottom.text(actionText);
        } else {
            $bulkPreviewTop.text(actionText);
        }
    }

    function refreshBothBulkPreviews() {
        updateBulkPreview('top');
        updateBulkPreview('bottom');
    }

    function applySelectedOnlyFilter() {
        var showOnlySelected = $showSelectedOnlyToggles.first().is(':checked');

        $('.themisdb-support-ticket-checkbox').each(function () {
            var $checkbox = $(this);
            var $row = $checkbox.closest('tr');
            var hideRow = showOnlySelected && !$checkbox.is(':checked');
            $row.toggleClass('themisdb-support-row-hidden-by-selection', hideRow);
        });
    }

    $('.themisdb-support-select-all').on('change', function () {
        var checked = $(this).is(':checked');
        $('.themisdb-support-select-all').prop('checked', checked);
        $('.themisdb-support-ticket-checkbox').prop('checked', checked);
        refreshBothBulkPreviews();
        applySelectedOnlyFilter();
    });

    $('.themisdb-support-ticket-checkbox').on('change', function () {
        var total = $('.themisdb-support-ticket-checkbox').length;
        var checked = $('.themisdb-support-ticket-checkbox:checked').length;
        $('.themisdb-support-select-all').prop('checked', total > 0 && checked === total);
        refreshBothBulkPreviews();
        applySelectedOnlyFilter();
    });

    $showSelectedOnlyToggles.on('change', function () {
        var checked = $(this).is(':checked');
        $showSelectedOnlyToggles.prop('checked', checked);
        applySelectedOnlyFilter();
    });

    $('#bulk-action-selector-top').on('change', function () {
        updateBulkPreview('top');
    });

    $('#bulk-action-selector-bottom').on('change', function () {
        updateBulkPreview('bottom');
    });

    $bulkForm.on('submit', function (e) {
        var $form = $(this);
        var submitter = document.activeElement;
        var source = $(submitter).data('bulk-source');
        var action = '';
        var actionText = '';

        if (source === 'bottom') {
            action = $('#bulk-action-selector-bottom').val();
            actionText = $('#bulk-action-selector-bottom option:selected').text();
        } else if (source === 'top') {
            action = $('#bulk-action-selector-top').val();
            actionText = $('#bulk-action-selector-top option:selected').text();
        }

        if (!action) {
            action = $('#bulk-action-selector-top').val() || $('#bulk-action-selector-bottom').val() || '';
            actionText = $('#bulk-action-selector-top option:selected').text() || $('#bulk-action-selector-bottom option:selected').text() || '';
        }

        var selected = $('.themisdb-support-ticket-checkbox:checked').length;

        $form.find('input[name="bulk_action"]').val(action);

        if (!action) {
            e.preventDefault();
            window.alert('Bitte waehlen Sie eine Bulk-Aktion aus.');
            return;
        }

        if (selected < 1) {
            e.preventDefault();
            window.alert('Bitte waehlen Sie mindestens ein Ticket aus.');
            return;
        }

        var confirmMessage = selected + ' Ticket(s) -> ' + actionText + '. Fortfahren?';
        if (action === 'delete') {
            confirmMessage = selected + ' Ticket(s) werden geloescht. Fortfahren?';
        }

        if (!window.confirm(confirmMessage)) {
            e.preventDefault();
        }
    });

    refreshBothBulkPreviews();
    applySelectedOnlyFilter();

}(jQuery));
