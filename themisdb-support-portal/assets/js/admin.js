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

}(jQuery));
