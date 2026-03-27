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
    // Inline status change in ticket list
    // -------------------------------------------------------------------------

    $('.themisdb-support-inline-status-select').each(function () {
        $(this).data('previous-value', $(this).val());
    });

    $('.themisdb-support-inline-status-select').on('change', function () {
        var $select = $(this);
        var ticketId = $select.data('ticket-id');
        var status = $select.val();
        var previousValue = $select.data('previous-value');
        var $message = $('.themisdb-support-inline-status-message[data-ticket-id="' + ticketId + '"]');
        var $row = $select.closest('tr');

        $select.prop('disabled', true);
        $message.empty();

        $.ajax({
            url: settings.ajaxUrl,
            type: 'POST',
            data: {
                action: 'themisdb_support_admin_status',
                ticket_id: ticketId,
                status: status,
                nonce: settings.nonce
            },
            success: function (response) {
                if (response.success) {
                    $select.data('previous-value', status);
                    $row.removeClass('themisdb-support-row-status-open themisdb-support-row-status-in_progress themisdb-support-row-status-resolved themisdb-support-row-status-closed');
                    $row.addClass('themisdb-support-row-status-' + response.data.status);
                    updateCountSummary(response.data.count_summary);
                    if (!rowMatchesCurrentFilters($row)) {
                        removeRowAndQuickAssignForm($row);
                        updateVisibleTicketCount();
                    }
                    showMsg($message, 'success', response.data.message);
                } else {
                    $select.val(previousValue);
                    showMsg($message, 'error', response.data.message || settings.strings.error || 'Ein Fehler ist aufgetreten.');
                }
            },
            error: function () {
                $select.val(previousValue);
                showMsg($message, 'error', settings.strings.error || 'Ein Fehler ist aufgetreten.');
            },
            complete: function () {
                $select.prop('disabled', false);
            }
        });
    });

    // -------------------------------------------------------------------------
    // Quick assignee change in list
    // -------------------------------------------------------------------------

    $('.themisdb-support-quick-assign-form').on('submit', function (e) {
        e.preventDefault();

        var $form = $(this);
        var ticketId = $form.find('input[name="ticket_id"]').val();
        var formId = $form.attr('id');
        var $select = $('.themisdb-support-quick-assign-select[data-form-id="' + formId + '"]');
        var $button = $('.themisdb-support-quick-assign-button[data-form-id="' + formId + '"]');
        var $message = $('.themisdb-support-quick-assign-message[data-ticket-id="' + ticketId + '"]');
        var assigneeUserId = $select.val();

        $button.prop('disabled', true).text(settings.strings.saving_assignment || '...');
        $message.empty();

        $.ajax({
            url: settings.ajaxUrl,
            type: 'POST',
            data: {
                action: 'themisdb_support_admin_assign',
                ticket_id: ticketId,
                assignee_user_id: assigneeUserId,
                nonce: settings.nonce
            },
            success: function (response) {
                if (response.success) {
                    $select.val(String(response.data.assignee_user_id));
                    if (!rowMatchesCurrentFilters($form.closest('tr'))) {
                        removeRowAndQuickAssignForm($form.closest('tr'));
                        updateVisibleTicketCount();
                    }
                    showMsg($message, 'success', response.data.message);
                } else {
                    showMsg($message, 'error', response.data.message || settings.strings.error || 'Ein Fehler ist aufgetreten.');
                }
            },
            error: function () {
                showMsg($message, 'error', settings.strings.error || 'Ein Fehler ist aufgetreten.');
            },
            complete: function () {
                $button.prop('disabled', false).text(settings.strings.save_assignment || 'Speichern');
            }
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

    function getCurrentListFilters() {
        var $bulkStateForm = $('.themisdb-support-bulk-form');

        return {
            status: String($bulkStateForm.find('input[name="current_status"]').val() || ''),
            assigneeUserId: String($bulkStateForm.find('input[name="current_assignee_user_id"]').val() || '0')
        };
    }

    function formatTicketCount(count) {
        var template = count === 1
            ? (settings.strings.ticket_singular || '%d Ticket')
            : (settings.strings.ticket_plural || '%d Tickets');

        return template.replace('%d', count);
    }

    function updateVisibleTicketCount() {
        var visibleCount = $('.themisdb-support-ticket-checkbox').length;
        $('[data-visible-ticket-count]').text(formatTicketCount(visibleCount));
    }

    function updateCountSummary(countSummary) {
        if (!countSummary) {
            return;
        }

        // Update status counts
        if (countSummary.status_counts) {
            $('[data-dashboard-count="all"]').text(countSummary.total_count);
            $('[data-status-tab-count="all"]').text('(' + countSummary.total_count + ')');

            $.each(countSummary.status_counts, function (statusKey, statusCount) {
                $('[data-dashboard-count="' + statusKey + '"]').text(statusCount);
                $('[data-status-tab-count="' + statusKey + '"]').text('(' + statusCount + ')');
            });
        }

        // Update quick-filter counts
        if (countSummary.quick_filter_counts) {
            $.each(countSummary.quick_filter_counts, function (filterKey, filterCount) {
                $('[data-quick-filter-key="' + filterKey + '"] .themisdb-support-quick-filter-count').text('(' + filterCount + ')');
            });
        }
    }

    function removeRowAndQuickAssignForm($row) {
        var formId = $row.find('.themisdb-support-quick-assign-select').data('form-id');
        if (formId) {
            $('#' + formId).remove();
        }

        $row.remove();
    }

    function rowMatchesCurrentFilters($row) {
        var filters = getCurrentListFilters();
        var rowStatus = String($row.find('.themisdb-support-inline-status-select').val() || '');
        var rowAssigneeUserId = String($row.find('.themisdb-support-quick-assign-select').val() || '0');

        if (filters.status && rowStatus !== filters.status) {
            return false;
        }

        if (filters.assigneeUserId !== '0' && rowAssigneeUserId !== filters.assigneeUserId) {
            return false;
        }

        return true;
    }

    function resetBulkSelection() {
        $('.themisdb-support-select-all').prop('checked', false);
        $('.themisdb-support-ticket-checkbox').prop('checked', false);
        applySelectedOnlyFilter();
        updateVisibleTicketCount();
    }

    function showBulkMessage(type, text) {
        showMsg($bulkPreviewTop, type, text);
        showMsg($bulkPreviewBottom, type, text);
    }

    function updateRowsAfterBulkAction(effect, ticketIds, responseData) {
        var statusClassNames = 'themisdb-support-row-status-open themisdb-support-row-status-in_progress themisdb-support-row-status-resolved themisdb-support-row-status-closed';

        $.each(ticketIds, function (_, ticketId) {
            var selector = '.themisdb-support-ticket-checkbox[value="' + ticketId + '"]';
            var $checkbox = $(selector);
            var $row = $checkbox.closest('tr');

            if (!$row.length) {
                return;
            }

            if (effect === 'status' && responseData.status) {
                var $statusSelectInline = $row.find('.themisdb-support-inline-status-select');
                $statusSelectInline.val(responseData.status).data('previous-value', responseData.status);
                $row.removeClass(statusClassNames).addClass('themisdb-support-row-status-' + responseData.status);
            }

            if (effect === 'assign' && typeof responseData.assignee_user_id !== 'undefined') {
                $row.find('.themisdb-support-quick-assign-select').val(String(responseData.assignee_user_id));
            }

            if (effect === 'delete') {
                removeRowAndQuickAssignForm($row);
                return;
            }

            if (!rowMatchesCurrentFilters($row)) {
                removeRowAndQuickAssignForm($row);
            }
        });

        if ($('.themisdb-support-ticket-checkbox').length === 0) {
            window.location.reload();
            return;
        }

        resetBulkSelection();
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
        e.preventDefault();

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
            window.alert('Bitte waehlen Sie eine Bulk-Aktion aus.');
            return;
        }

        if (selected < 1) {
            window.alert('Bitte waehlen Sie mindestens ein Ticket aus.');
            return;
        }

        var confirmMessage = selected + ' Ticket(s) -> ' + actionText + '. Fortfahren?';
        if (action === 'delete') {
            confirmMessage = selected + ' Ticket(s) werden geloescht. Fortfahren?';
        }

        if (!window.confirm(confirmMessage)) {
            return;
        }

        var ticketIds = $('.themisdb-support-ticket-checkbox:checked').map(function () {
            return $(this).val();
        }).get();

        var $submitButtons = $form.find('.themisdb-support-bulk-apply');
        $submitButtons.prop('disabled', true);
        showBulkMessage('success', settings.strings.processing_bulk || 'Bulk-Aktion wird ausgefuehrt...');

        $.ajax({
            url: settings.ajaxUrl,
            type: 'POST',
            traditional: true,
            data: {
                action: 'themisdb_support_admin_bulk',
                bulk_action: action,
                ticket_ids: ticketIds,
                nonce: settings.nonce
            },
            success: function (response) {
                if (response.success) {
                    updateCountSummary(response.data.count_summary);
                    showBulkMessage(response.data.notice_type === 'warning' ? 'error' : 'success', response.data.message);
                    updateRowsAfterBulkAction(response.data.effect, response.data.ticket_ids || ticketIds, response.data);
                } else {
                    showBulkMessage('error', (response.data && response.data.message) || settings.strings.error || 'Ein Fehler ist aufgetreten.');
                }
            },
            error: function () {
                showBulkMessage('error', settings.strings.error || 'Ein Fehler ist aufgetreten.');
            },
            complete: function () {
                $submitButtons.prop('disabled', false);
            }
        });
    });

    refreshBothBulkPreviews();
    applySelectedOnlyFilter();
    updateVisibleTicketCount();

}(jQuery));
