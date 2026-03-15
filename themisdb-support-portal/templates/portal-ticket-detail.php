<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB Support Portal – Portal Ticket Detail Template             ║
║ Rendered via AJAX into the frontend modal.                          ║
╚═════════════════════════════════════════════════════════════════════╝
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="themisdb-support-ticket-detail">
    <div class="themisdb-support-ticket-detail-header">
        <div class="themisdb-support-ticket-detail-meta">
            <span class="themisdb-support-ticket-number"><?php echo esc_html($ticket['ticket_number']); ?></span>
            <span class="themisdb-support-badge themisdb-support-status-badge themisdb-support-status-<?php echo esc_attr($ticket['status']); ?>">
                <?php echo esc_html(isset($status_labels[$ticket['status']]) ? $status_labels[$ticket['status']] : $ticket['status']); ?>
            </span>
            <span class="themisdb-support-badge themisdb-support-priority-badge themisdb-support-priority-<?php echo esc_attr($ticket['priority']); ?>">
                <?php echo esc_html(isset($priority_labels[$ticket['priority']]) ? $priority_labels[$ticket['priority']] : $ticket['priority']); ?>
            </span>
        </div>
        <h3 class="themisdb-support-ticket-detail-subject"><?php echo esc_html($ticket['subject']); ?></h3>
        <p class="themisdb-support-ticket-detail-date">
            <?php echo esc_html(sprintf(
                /* translators: %s: formatted date */
                __('Erstellt am %s', 'themisdb-support-portal'),
                date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($ticket['created_at']))
            )); ?>
        </p>
    </div>

    <div class="themisdb-support-ticket-messages">
        <?php foreach ($messages as $msg): ?>
            <div class="themisdb-support-message <?php echo $msg['is_admin_reply'] ? 'themisdb-support-message-admin' : 'themisdb-support-message-customer'; ?>">
                <div class="themisdb-support-message-header">
                    <strong><?php echo esc_html($msg['author_name']); ?></strong>
                    <?php if ($msg['is_admin_reply']): ?>
                        <span class="themisdb-support-message-role"><?php esc_html_e('Support-Team', 'themisdb-support-portal'); ?></span>
                    <?php endif; ?>
                    <span class="themisdb-support-message-date">
                        <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($msg['created_at']))); ?>
                    </span>
                </div>
                <div class="themisdb-support-message-body">
                    <?php echo wp_kses_post(nl2br($msg['message'])); ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
