<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB Support Portal – Admin Tickets Template                    ║
╚═════════════════════════════════════════════════════════════════════╝
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap themisdb-support-admin-wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-sos"></span>
        <?php esc_html_e('ThemisDB Support – Tickets', 'themisdb-support-portal'); ?>
    </h1>
    <hr class="wp-header-end">

    <!-- Filter bar -->
    <div class="themisdb-support-admin-filters">
        <form method="get" class="themisdb-support-filter-form">
            <input type="hidden" name="page" value="themisdb-support">

            <label for="filter-status" class="screen-reader-text"><?php esc_html_e('Status', 'themisdb-support-portal'); ?></label>
            <select id="filter-status" name="status">
                <option value=""><?php esc_html_e('Alle Status', 'themisdb-support-portal'); ?></option>
                <?php foreach ($status_labels as $value => $label): ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected($status, $value); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="filter-priority" class="screen-reader-text"><?php esc_html_e('Priorität', 'themisdb-support-portal'); ?></label>
            <select id="filter-priority" name="priority">
                <option value=""><?php esc_html_e('Alle Prioritäten', 'themisdb-support-portal'); ?></option>
                <?php foreach ($priority_labels as $value => $label): ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected($priority, $value); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="button"><?php esc_html_e('Filtern', 'themisdb-support-portal'); ?></button>

            <?php if ($status || $priority): ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-support')); ?>" class="button button-secondary">
                    <?php esc_html_e('Filter zurücksetzen', 'themisdb-support-portal'); ?>
                </a>
            <?php endif; ?>
        </form>

        <span class="themisdb-support-ticket-count-total">
            <?php echo esc_html(sprintf(
                /* translators: %d: number of tickets */
                _n('%d Ticket', '%d Tickets', $total, 'themisdb-support-portal'),
                $total
            )); ?>
        </span>
    </div>

    <?php if (empty($tickets)): ?>
        <div class="themisdb-support-admin-empty">
            <span class="dashicons dashicons-clipboard"></span>
            <p><?php esc_html_e('Keine Tickets gefunden.', 'themisdb-support-portal'); ?></p>
        </div>
    <?php else: ?>
        <!-- Tickets Table -->
        <table class="wp-list-table widefat fixed striped themisdb-support-tickets-table">
            <thead>
                <tr>
                    <th scope="col" class="column-ticket-number"><?php esc_html_e('Ticket-Nr.', 'themisdb-support-portal'); ?></th>
                    <th scope="col" class="column-subject"><?php esc_html_e('Betreff', 'themisdb-support-portal'); ?></th>
                    <th scope="col" class="column-customer"><?php esc_html_e('Kunde', 'themisdb-support-portal'); ?></th>
                    <th scope="col" class="column-status"><?php esc_html_e('Status', 'themisdb-support-portal'); ?></th>
                    <th scope="col" class="column-priority"><?php esc_html_e('Priorität', 'themisdb-support-portal'); ?></th>
                    <th scope="col" class="column-date"><?php esc_html_e('Datum', 'themisdb-support-portal'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $ticket): ?>
                    <tr class="themisdb-support-row-status-<?php echo esc_attr($ticket['status']); ?>">
                        <td class="column-ticket-number">
                            <strong>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-support&ticket_id=' . $ticket['id'])); ?>">
                                    <?php echo esc_html($ticket['ticket_number']); ?>
                                </a>
                            </strong>
                        </td>
                        <td class="column-subject">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-support&ticket_id=' . $ticket['id'])); ?>">
                                <?php echo esc_html($ticket['subject']); ?>
                            </a>
                        </td>
                        <td class="column-customer">
                            <?php echo esc_html($ticket['customer_name']); ?><br>
                            <small><a href="mailto:<?php echo esc_attr($ticket['customer_email']); ?>">
                                <?php echo esc_html($ticket['customer_email']); ?>
                            </a></small>
                            <?php if ($ticket['customer_company']): ?>
                                <br><small><?php echo esc_html($ticket['customer_company']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="column-status">
                            <span class="themisdb-support-admin-status themisdb-support-status-<?php echo esc_attr($ticket['status']); ?>">
                                <?php echo esc_html(isset($status_labels[$ticket['status']]) ? $status_labels[$ticket['status']] : $ticket['status']); ?>
                            </span>
                        </td>
                        <td class="column-priority">
                            <span class="themisdb-support-admin-priority themisdb-support-priority-<?php echo esc_attr($ticket['priority']); ?>">
                                <?php echo esc_html(isset($priority_labels[$ticket['priority']]) ? $priority_labels[$ticket['priority']] : $ticket['priority']); ?>
                            </span>
                        </td>
                        <td class="column-date">
                            <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($ticket['created_at']))); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    $page_links = paginate_links(array(
                        'base'      => add_query_arg('paged', '%#%'),
                        'format'    => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total'     => $total_pages,
                        'current'   => $paged,
                    ));

                    if ($page_links) {
                        echo '<span class="displaying-num">'
                            . esc_html(sprintf(
                                /* translators: %d: number of tickets */
                                _n('%d Ticket', '%d Tickets', $total, 'themisdb-support-portal'),
                                $total
                            ))
                            . '</span>';
                        echo wp_kses_post($page_links);
                    }
                    ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
