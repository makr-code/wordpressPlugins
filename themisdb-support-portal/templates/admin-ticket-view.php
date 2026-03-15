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

    <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-support')); ?>" class="page-title-action">
        &larr; <?php esc_html_e('Zurück zur Übersicht', 'themisdb-support-portal'); ?>
    </a>
    <hr class="wp-header-end">

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

        </div><!-- /.sidebar -->
    </div><!-- /.layout -->
</div>
