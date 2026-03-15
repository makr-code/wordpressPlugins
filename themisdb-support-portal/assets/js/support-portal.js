/**
 * ThemisDB Support Portal – Frontend JavaScript
 *
 * Handles:
 *  - License file drag-and-drop / selection + AJAX authentication
 *  - New ticket form submission via AJAX
 *  - Ticket detail modal (loads ticket HTML via AJAX)
 *  - Logout
 */
(function ($) {
    'use strict';

    var settings = window.themisdbSupport || {};

    // -------------------------------------------------------------------------
    // Utility helpers
    // -------------------------------------------------------------------------

    function showMessage($container, type, text) {
        var cssClass = type === 'success'
            ? 'themisdb-support-notice-success'
            : 'themisdb-support-notice-error';
        $container.html(
            '<div class="themisdb-support-notice ' + cssClass + '">' +
            '<p>' + $('<span>').text(text).html() + '</p>' +
            '</div>'
        );
    }

    // -------------------------------------------------------------------------
    // License file login
    // -------------------------------------------------------------------------

    var $loginForm     = $('#themisdb-support-license-form');
    var $fileInput     = $('#themisdb-support-license-file');
    var $uploadArea    = $('#themisdb-support-upload-area');
    var $fileName      = $('#themisdb-support-file-name');
    var $loginBtn      = $('#themisdb-support-login-btn');
    var $loginMessages = $('#themisdb-support-login-messages');

    // Enable submit button and show file name after a file is chosen
    $fileInput.on('change', function () {
        var file = this.files && this.files[0];
        if (file) {
            $fileName.text(file.name).show();
            $uploadArea.addClass('themisdb-file-selected');
            $loginBtn.prop('disabled', false);
        } else {
            $fileName.hide();
            $uploadArea.removeClass('themisdb-file-selected');
            $loginBtn.prop('disabled', true);
        }
    });

    // Drag-and-drop visual feedback
    $uploadArea
        .on('dragover dragenter', function (e) {
            e.preventDefault();
            $(this).addClass('themisdb-drag-over');
        })
        .on('dragleave dragend drop', function () {
            $(this).removeClass('themisdb-drag-over');
        });

    // Form submission
    $loginForm.on('submit', function (e) {
        e.preventDefault();

        var file = $fileInput[0].files && $fileInput[0].files[0];
        if (!file) {
            showMessage($loginMessages, 'error', settings.strings.select_file);
            return;
        }

        $loginBtn.prop('disabled', true).text(settings.strings.verifying || '…');
        $loginMessages.empty();

        var reader = new FileReader();

        reader.onload = function (evt) {
            var fileContent = evt.target.result;

            $.ajax({
                url:  settings.ajaxUrl,
                type: 'POST',
                data: {
                    action:               'themisdb_support_license_auth',
                    license_file_content: fileContent,
                    nonce:                settings.nonce,
                },
                success: function (response) {
                    if (response.success) {
                        showMessage($loginMessages, 'success', response.data.message);
                        setTimeout(function () {
                            window.location.href = response.data.redirect || window.location.href;
                        }, 1200);
                    } else {
                        showMessage($loginMessages, 'error', response.data.message);
                        $loginBtn.prop('disabled', false).text(settings.strings.auth_with_license);
                    }
                },
                error: function () {
                    showMessage($loginMessages, 'error', settings.strings.error);
                    $loginBtn.prop('disabled', false).text(settings.strings.auth_with_license);
                },
            });
        };

        reader.readAsText(file);
    });

    // -------------------------------------------------------------------------
    // New ticket form
    // -------------------------------------------------------------------------

    var $toggleFormBtn  = $('#themisdb-support-toggle-form');
    var $newTicketForm  = $('#themisdb-support-new-ticket-form');
    var $cancelTicket   = $('#themisdb-cancel-ticket');
    var $submitTicket   = $('#themisdb-submit-ticket');
    var $ticketMessages = $('#themisdb-new-ticket-messages');

    $toggleFormBtn.on('click', function () {
        var $form = $('#themisdb-support-new-ticket-form');
        if ($form.is(':visible')) {
            $form.slideUp(200);
        } else {
            $form.slideDown(200);
            $form.find('input, textarea').first().focus();
        }
    });

    $cancelTicket.on('click', function () {
        $('#themisdb-support-new-ticket-form').slideUp(200);
        $newTicketForm[0].reset();
        $ticketMessages.empty();
    });

    $newTicketForm.on('submit', function (e) {
        e.preventDefault();

        var subject  = $('#themisdb-ticket-subject').val().trim();
        var message  = $('#themisdb-ticket-message').val().trim();
        var priority = $('#themisdb-ticket-priority').val();

        if (!subject || !message) {
            showMessage($ticketMessages, 'error', settings.strings.fill_required_fields || 'Bitte füllen Sie Betreff und Nachricht aus.');
            return;
        }

        $submitTicket.prop('disabled', true).text(settings.strings.submitting || '…');
        $ticketMessages.empty();

        $.ajax({
            url:  settings.ajaxUrl,
            type: 'POST',
            data: {
                action:   'themisdb_support_new_ticket',
                subject:  subject,
                message:  message,
                priority: priority,
                nonce:    settings.nonce,
            },
            success: function (response) {
                if (response.success) {
                    showMessage($ticketMessages, 'success',
                        response.data.message + ' (' + response.data.ticket_number + ')'
                    );
                    $newTicketForm[0].reset();
                    // Reload the page after a short delay to show the new ticket
                    setTimeout(function () { window.location.reload(); }, 2000);
                } else {
                    showMessage($ticketMessages, 'error', response.data.message);
                    $submitTicket.prop('disabled', false).text(settings.strings.submit_ticket);
                }
            },
            error: function () {
                showMessage($ticketMessages, 'error', settings.strings.error);
                $submitTicket.prop('disabled', false).text(settings.strings.submit_ticket);
            },
        });
    });

    // -------------------------------------------------------------------------
    // Ticket detail modal
    // -------------------------------------------------------------------------

    var $modal         = $('#themisdb-support-ticket-modal');
    var $modalContent  = $('#themisdb-support-ticket-detail-content');
    var $modalClose    = $modal.find('.themisdb-support-modal-close');
    var $modalBackdrop = $modal.find('.themisdb-support-modal-backdrop');

    function openModal(ticketId) {
        $modal.show();
        $('body').addClass('themisdb-modal-open');
        $modalContent.html(
            '<div class="themisdb-support-loading">' +
            '<span class="dashicons dashicons-update themisdb-spin"></span>' +
            (settings.strings.loading || 'Lädt…') +
            '</div>'
        );

        $.ajax({
            url:  settings.ajaxUrl,
            type: 'POST',
            data: {
                action:    'themisdb_support_get_ticket',
                ticket_id: ticketId,
                nonce:     settings.nonce,
            },
            success: function (response) {
                if (response.success) {
                    $modalContent.html(response.data.html);
                } else {
                    $modalContent.html(
                        '<p class="themisdb-support-notice themisdb-support-notice-error">' +
                        $('<span>').text(response.data.message).html() +
                        '</p>'
                    );
                }
            },
            error: function () {
                $modalContent.html(
                    '<p class="themisdb-support-notice themisdb-support-notice-error">' +
                    (settings.strings.error || 'Error') + '</p>'
                );
            },
        });
    }

    function closeModal() {
        $modal.hide();
        $('body').removeClass('themisdb-modal-open');
        $modalContent.empty();
    }

    $(document).on('click', '.themisdb-view-ticket', function (e) {
        e.preventDefault();
        openModal($(this).data('ticket-id'));
    });

    $modalClose.on('click', closeModal);
    $modalBackdrop.on('click', closeModal);

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && $modal.is(':visible')) {
            closeModal();
        }
    });

    // -------------------------------------------------------------------------
    // Logout
    // -------------------------------------------------------------------------

    $('#themisdb-support-logout-btn').on('click', function (e) {
        e.preventDefault();

        $.ajax({
            url:  settings.ajaxUrl,
            type: 'POST',
            data: {
                action: 'themisdb_support_logout',
                nonce:  settings.nonce,
            },
            success: function (response) {
                window.location.href = (response.success && response.data.redirect)
                    ? response.data.redirect
                    : window.location.href;
            },
            error: function () {
                window.location.reload();
            },
        });
    });

}(jQuery));
