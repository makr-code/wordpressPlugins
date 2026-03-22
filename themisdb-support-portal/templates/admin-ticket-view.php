<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB Support Portal – Admin Ticket Detail Template              ║
╚═════════════════════════════════════════════════════════════════════╝
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap themisdb-support-admin-wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-sos"></span>
        <?php echo esc_html(sprintf(
            /* translators: %s: ticket number */
            __('Ticket %s', 'themisdb-support-portal'),
            $ticket['ticket_number']
        )); ?>
    </h1>

    <a href="<?php echo esc_url($back_to_list_url); ?>" class="page-title-action">
        &larr; <?php esc_html_e('Zurück zur Übersicht', 'themisdb-support-portal'); ?>
    </a>
    <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-support&tab=create')); ?>" class="page-title-action">
        <?php esc_html_e('Neues Ticket', 'themisdb-support-portal'); ?>
    </a>
    <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-support-settings')); ?>" class="page-title-action">
        <?php esc_html_e('Einstellungen', 'themisdb-support-portal'); ?>
    </a>

    <?php
    $has_previous_ticket = !empty($ticket_navigation['previous']);
    $has_next_ticket = !empty($ticket_navigation['next']);

    if ($has_previous_ticket || $has_next_ticket):
        $previous_ticket_url = $has_previous_ticket
            ? add_query_arg(array_merge($list_state_args, array('page' => 'themisdb-support', 'ticket_id' => intval($ticket_navigation['previous']))), admin_url('admin.php'))
            : '';
        $next_ticket_url = $has_next_ticket
            ? add_query_arg(array_merge($list_state_args, array('page' => 'themisdb-support', 'ticket_id' => intval($ticket_navigation['next']))), admin_url('admin.php'))
            : '';
    ?>
        <span class="themisdb-support-ticket-nav-actions">
            <?php if ($has_previous_ticket): ?>
                <a href="<?php echo esc_url($previous_ticket_url); ?>" class="page-title-action">
                    &larr; <?php esc_html_e('Vorheriges Ticket', 'themisdb-support-portal'); ?>
                </a>
            <?php endif; ?>
            <?php if ($has_next_ticket): ?>
                <a href="<?php echo esc_url($next_ticket_url); ?>" class="page-title-action">
                    <?php esc_html_e('Nächstes Ticket', 'themisdb-support-portal'); ?> &rarr;
                </a>
            <?php endif; ?>
        </span>
    <?php endif; ?>
    <hr class="wp-header-end">

    <?php if (!empty($notice_message)): ?>
        <div class="notice notice-<?php echo esc_attr($notice_type); ?> is-dismissible">
            <p><?php echo esc_html($notice_message); ?></p>
        </div>
    <?php endif; ?>

    <div class="themisdb-admin-modules">
        <div class="card">
            <h2><?php esc_html_e('Schnellaktionen', 'themisdb-support-portal'); ?></h2>
            <p>
                <a href="<?php echo esc_url($back_to_list_url); ?>" class="button button-primary"><?php esc_html_e('Zur Ticket-Uebersicht', 'themisdb-support-portal'); ?></a>
                <a href="#themisdb-support-admin-reply-wrap" class="button"><?php esc_html_e('Zur Antwort', 'themisdb-support-portal'); ?></a>
                <a href="#themisdb-edit-subject" class="button"><?php esc_html_e('Ticket bearbeiten', 'themisdb-support-portal'); ?></a>
            </p>
            <p><?php esc_html_e('Wechsle direkt zwischen Verlauf, Antwort und Pflege des Tickets, ohne die aktuelle Detailansicht zu verlassen.', 'themisdb-support-portal'); ?></p>
        </div>

        <div class="card">
            <h2><?php esc_html_e('Ticket-Status', 'themisdb-support-portal'); ?></h2>
            <table class="widefat striped">
                <tbody>
                    <tr>
                        <th><?php esc_html_e('Status', 'themisdb-support-portal'); ?></th>
                        <td><?php echo esc_html(isset($status_labels[$ticket['status']]) ? $status_labels[$ticket['status']] : $ticket['status']); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Priorität', 'themisdb-support-portal'); ?></th>
                        <td><?php echo esc_html(isset($priority_labels[$ticket['priority']]) ? $priority_labels[$ticket['priority']] : $ticket['priority']); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Kunde', 'themisdb-support-portal'); ?></th>
                        <td><?php echo esc_html($ticket['customer_name']); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Letzte Aktualisierung', 'themisdb-support-portal'); ?></th>
                        <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($ticket['updated_at']))); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2><?php esc_html_e('Support-Kontext', 'themisdb-support-portal'); ?></h2>
            <table class="widefat striped">
                <tbody>
                    <tr>
                        <th><?php esc_html_e('Lizenzbezug', 'themisdb-support-portal'); ?></th>
                        <td>
                            <?php
                            if (!empty($ticket['license_key'])) {
                                echo esc_html(substr($ticket['license_key'], 0, 16) . '...');
                            } else {
                                esc_html_e('Kein Lizenzschlüssel hinterlegt', 'themisdb-support-portal');
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Support Benefits', 'themisdb-support-portal'); ?></th>
                        <td>
                            <?php
                            if (!empty($support_benefit['tier_level'])) {
                                echo esc_html(ucfirst($support_benefit['tier_level']));
                            } else {
                                esc_html_e('Keine Benefits gefunden', 'themisdb-support-portal');
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Antwort-SLA', 'themisdb-support-portal'); ?></th>
                        <td>
                            <?php
                            if (!empty($support_benefit['response_sla_hours'])) {
                                echo esc_html(intval($support_benefit['response_sla_hours']) . ' h');
                            } else {
                                esc_html_e('Nicht definiert', 'themisdb-support-portal');
                            }
                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="themisdb-support-admin-ticket-layout">

        <!-- Main column: messages + reply -->
        <div class="themisdb-support-admin-ticket-main">
            <div class="themisdb-support-admin-card">
                <h2 class="themisdb-support-admin-card-title"><?php echo esc_html($ticket['subject']); ?></h2>

                <!-- Messages thread -->
                <div class="themisdb-support-admin-messages" id="themisdb-support-admin-messages">
                    <?php foreach ($messages as $msg): ?>
                        <div class="themisdb-support-admin-message <?php echo $msg['is_admin_reply'] ? 'themisdb-support-admin-message-admin' : 'themisdb-support-admin-message-customer'; ?>">
                            <div class="themisdb-support-admin-message-header">
                                <strong><?php echo esc_html($msg['author_name']); ?></strong>
                                <?php if ($msg['is_admin_reply']): ?>
                                    <span class="themisdb-support-admin-badge"><?php esc_html_e('Support-Team', 'themisdb-support-portal'); ?></span>
                                <?php endif; ?>
                                <span class="themisdb-support-admin-message-date">
                                    <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($msg['created_at']))); ?>
                                </span>
                            </div>
                            <div class="themisdb-support-admin-message-body">
                                <?php echo wp_kses_post(nl2br($msg['message'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Reply form -->
                <?php if (!in_array($ticket['status'], array('closed', 'resolved'), true)): ?>
                    <div class="themisdb-support-admin-reply-form" id="themisdb-support-admin-reply-wrap">
                        <h3><?php esc_html_e('Antwort senden', 'themisdb-support-portal'); ?></h3>
                        <form id="themisdb-support-admin-reply-form" method="post" novalidate>
                            <input type="hidden" name="ticket_id" value="<?php echo esc_attr($ticket['id']); ?>">
                            <?php wp_nonce_field('themisdb_support_admin_nonce', 'themisdb_support_admin_nonce_field'); ?>

                            <div class="themisdb-support-form-group">
                                <label for="admin-reply-message">
                                    <?php esc_html_e('Nachricht', 'themisdb-support-portal'); ?>
                                </label>
                                <textarea id="admin-reply-message" name="message" rows="6" required
                                    placeholder="<?php esc_attr_e('Antwort eingeben…', 'themisdb-support-portal'); ?>"></textarea>
                            </div>

                            <div class="themisdb-support-form-actions">
                                <button type="submit" id="themisdb-support-admin-reply-btn" class="button button-primary">
                                    <?php esc_html_e('Antwort senden', 'themisdb-support-portal'); ?>
                                </button>
                            </div>

                            <div class="themisdb-support-admin-reply-messages" id="themisdb-support-admin-reply-messages"></div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="themisdb-support-admin-notice">
                        <?php esc_html_e('Dieses Ticket ist geschlossen.', 'themisdb-support-portal'); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar: ticket info + status -->
        <div class="themisdb-support-admin-ticket-sidebar">

            <div class="themisdb-support-admin-card">
                <h3 class="themisdb-support-admin-card-title">
                    <?php esc_html_e('Ticket-Informationen', 'themisdb-support-portal'); ?>
                </h3>
                <table class="themisdb-support-admin-info-table">
                    <tr>
                        <th><?php esc_html_e('Ticket-Nr.', 'themisdb-support-portal'); ?></th>
                        <td><code><?php echo esc_html($ticket['ticket_number']); ?></code></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Erstellt', 'themisdb-support-portal'); ?></th>
                        <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($ticket['created_at']))); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Aktualisiert', 'themisdb-support-portal'); ?></th>
                        <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($ticket['updated_at']))); ?></td>
                    </tr>
                    <?php if ($ticket['license_key']): ?>
                    <tr>
                        <th><?php esc_html_e('Lizenzschlüssel', 'themisdb-support-portal'); ?></th>
                        <td><code><?php echo esc_html(substr($ticket['license_key'], 0, 16)); ?>…</code></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>

            <div class="themisdb-support-admin-card">
                <h3 class="themisdb-support-admin-card-title">
                    <?php esc_html_e('Kunde', 'themisdb-support-portal'); ?>
                </h3>
                <table class="themisdb-support-admin-info-table">
                    <tr>
                        <th><?php esc_html_e('Name', 'themisdb-support-portal'); ?></th>
                        <td><?php echo esc_html($ticket['customer_name']); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('E-Mail', 'themisdb-support-portal'); ?></th>
                        <td><a href="mailto:<?php echo esc_attr($ticket['customer_email']); ?>"><?php echo esc_html($ticket['customer_email']); ?></a></td>
                    </tr>
                    <?php if ($ticket['customer_company']): ?>
                    <tr>
                        <th><?php esc_html_e('Unternehmen', 'themisdb-support-portal'); ?></th>
                        <td><?php echo esc_html($ticket['customer_company']); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>

            <div class="themisdb-support-admin-card">
                <h3 class="themisdb-support-admin-card-title">
                    <?php esc_html_e('Status & Priorität', 'themisdb-support-portal'); ?>
                </h3>

                <div class="themisdb-support-form-group">
                    <label for="themisdb-admin-change-status"><?php esc_html_e('Status ändern', 'themisdb-support-portal'); ?></label>
                    <select id="themisdb-admin-change-status" data-ticket-id="<?php echo esc_attr($ticket['id']); ?>">
                        <?php foreach ($status_labels as $value => $label): ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($ticket['status'], $value); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="themisdb-support-admin-current-priority">
                    <strong><?php esc_html_e('Priorität:', 'themisdb-support-portal'); ?></strong>
                    <span class="themisdb-support-admin-priority themisdb-support-priority-<?php echo esc_attr($ticket['priority']); ?>">
                        <?php echo esc_html(isset($priority_labels[$ticket['priority']]) ? $priority_labels[$ticket['priority']] : $ticket['priority']); ?>
                    </span>
                </div>

                <div id="themisdb-admin-status-message"></div>
            </div>

            <div class="themisdb-support-admin-card">
                <h3 class="themisdb-support-admin-card-title">
                    <?php esc_html_e('Ticket bearbeiten', 'themisdb-support-portal'); ?>
                </h3>

                <form method="post" class="themisdb-support-admin-edit-form">
                    <?php wp_nonce_field('themisdb_support_update_ticket', 'themisdb_support_nonce'); ?>
                    <input type="hidden" name="themisdb_support_action" value="update_ticket">
                    <input type="hidden" name="ticket_id" value="<?php echo esc_attr($ticket['id']); ?>">

                    <div class="themisdb-support-form-group">
                        <label for="themisdb-edit-subject"><?php esc_html_e('Betreff', 'themisdb-support-portal'); ?></label>
                        <input type="text" id="themisdb-edit-subject" name="subject" value="<?php echo esc_attr($ticket['subject']); ?>" required>
                    </div>

                    <div class="themisdb-support-form-group">
                        <label for="themisdb-edit-status"><?php esc_html_e('Status', 'themisdb-support-portal'); ?></label>
                        <select id="themisdb-edit-status" name="status">
                            <?php foreach ($status_labels as $value => $label): ?>
                                <option value="<?php echo esc_attr($value); ?>" <?php selected($ticket['status'], $value); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="themisdb-support-form-group">
                        <label for="themisdb-edit-priority"><?php esc_html_e('Priorität', 'themisdb-support-portal'); ?></label>
                        <select id="themisdb-edit-priority" name="priority">
                            <?php foreach ($priority_labels as $value => $label): ?>
                                <option value="<?php echo esc_attr($value); ?>" <?php selected($ticket['priority'], $value); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="themisdb-support-form-group">
                        <label for="themisdb-edit-customer-name"><?php esc_html_e('Kundenname', 'themisdb-support-portal'); ?></label>
                        <input type="text" id="themisdb-edit-customer-name" name="customer_name" value="<?php echo esc_attr($ticket['customer_name']); ?>">
                    </div>

                    <div class="themisdb-support-form-group">
                        <label for="themisdb-edit-customer-email"><?php esc_html_e('Kunden-E-Mail', 'themisdb-support-portal'); ?></label>
                        <input type="email" id="themisdb-edit-customer-email" name="customer_email" value="<?php echo esc_attr($ticket['customer_email']); ?>" required>
                    </div>

                    <div class="themisdb-support-form-group">
                        <label for="themisdb-edit-customer-company"><?php esc_html_e('Unternehmen', 'themisdb-support-portal'); ?></label>
                        <input type="text" id="themisdb-edit-customer-company" name="customer_company" value="<?php echo esc_attr($ticket['customer_company']); ?>">
                    </div>

                    <div class="themisdb-support-form-group">
                        <label for="themisdb-edit-license-key"><?php esc_html_e('Lizenzschlüssel', 'themisdb-support-portal'); ?></label>
                        <input type="text" id="themisdb-edit-license-key" name="license_key" value="<?php echo esc_attr($ticket['license_key']); ?>">
                    </div>

                    <div class="themisdb-support-form-actions">
                        <button type="submit" class="button button-primary"><?php esc_html_e('Änderungen speichern', 'themisdb-support-portal'); ?></button>
                    </div>
                </form>
            </div>

            <div class="themisdb-support-admin-card themisdb-support-admin-card-danger">
                <h3 class="themisdb-support-admin-card-title">
                    <?php esc_html_e('Ticket löschen', 'themisdb-support-portal'); ?>
                </h3>

                <form method="post" onsubmit="return confirm('<?php echo esc_js(__('Dieses Ticket wirklich löschen?', 'themisdb-support-portal')); ?>');">
                    <?php wp_nonce_field('themisdb_support_delete_ticket', 'themisdb_support_nonce'); ?>
                    <input type="hidden" name="themisdb_support_action" value="delete_ticket">
                    <input type="hidden" name="ticket_id" value="<?php echo esc_attr($ticket['id']); ?>">
                    <button type="submit" class="button button-secondary themisdb-support-danger-button">
                        <?php esc_html_e('Ticket löschen', 'themisdb-support-portal'); ?>
                    </button>
                </form>
            </div>

            <div class="themisdb-support-admin-card themisdb-support-admin-card-benefits">
                <h3 class="themisdb-support-admin-card-title">
                    <?php esc_html_e('Support Benefits', 'themisdb-support-portal'); ?>
                </h3>

                <?php if (!empty($support_benefit)): ?>
                    <table class="themisdb-support-admin-info-table">
                        <tr>
                            <th><?php esc_html_e('Tier', 'themisdb-support-portal'); ?></th>
                            <td><?php echo esc_html(ucfirst($support_benefit['tier_level'])); ?></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Status', 'themisdb-support-portal'); ?></th>
                            <td><?php echo esc_html(ucfirst($support_benefit['benefit_status'])); ?></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('SLA', 'themisdb-support-portal'); ?></th>
                            <td><?php echo esc_html(intval($support_benefit['response_sla_hours']) . ' h'); ?></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Offene Tickets', 'themisdb-support-portal'); ?></th>
                            <td>
                                <?php
                                if (intval($support_benefit['max_open_tickets']) === -1) {
                                    esc_html_e('Unbegrenzt', 'themisdb-support-portal');
                                } else {
                                    echo esc_html(intval($support_benefit['tickets_used_this_month']) . ' / ' . intval($support_benefit['max_open_tickets']));
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Monatliche Tickets', 'themisdb-support-portal'); ?></th>
                            <td>
                                <?php
                                if (intval($support_benefit['max_tickets_per_month']) === -1) {
                                    esc_html_e('Unbegrenzt', 'themisdb-support-portal');
                                } else {
                                    echo esc_html(intval($support_benefit['tickets_used_this_month']) . ' / ' . intval($support_benefit['max_tickets_per_month']));
                                }
                                ?>
                            </td>
                        </tr>
                        <?php if (!empty($support_benefit['expires_at'])): ?>
                        <tr>
                            <th><?php esc_html_e('Ablauf', 'themisdb-support-portal'); ?></th>
                            <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($support_benefit['expires_at']))); ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>

                    <?php if (!empty($support_license)): ?>
                        <p class="themisdb-support-admin-benefit-note">
                            <?php
                            $license_preview = substr($support_license['license_key'], 0, 16) . '...';
                            echo esc_html(sprintf(__('Lizenz: %s', 'themisdb-support-portal'), $license_preview));
                            ?>
                        </p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="themisdb-support-admin-benefit-empty">
                        <?php esc_html_e('Keine Support-Benefits für dieses Ticket gefunden.', 'themisdb-support-portal'); ?>
                    </p>
                <?php endif; ?>
            </div>

        </div><!-- /.sidebar -->
    </div><!-- /.layout -->
</div>
