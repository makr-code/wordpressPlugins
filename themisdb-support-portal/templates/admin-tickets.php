<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB Support Portal – Admin Tickets Template                    ║
╚═════════════════════════════════════════════════════════════════════╝
 */

if (!defined('ABSPATH')) {
    exit;
}

$assignee_labels = array();
foreach ((array) $assignable_agents as $agent) {
    $assignee_labels[intval($agent['id'])] = $agent['label'];
}

$render_bulk_assignment_options = static function ($assignable_agents) {
    ?>
    <option value="assign_none"><?php esc_html_e('Zuweisung entfernen', 'themisdb-support-portal'); ?></option>
    <?php foreach ((array) $assignable_agents as $agent): ?>
        <option value="<?php echo esc_attr('assign_user_' . intval($agent['id'])); ?>">
            <?php echo esc_html(sprintf(__('Bearbeiter: %s', 'themisdb-support-portal'), $agent['label'])); ?>
        </option>
    <?php endforeach;
};
?>
<div class="wrap themisdb-support-admin-wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-sos"></span>
        <?php esc_html_e('ThemisDB Support - Tickets', 'themisdb-support-portal'); ?>
    </h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-support&tab=list')); ?>" class="page-title-action"><?php esc_html_e('Ticket-Uebersicht', 'themisdb-support-portal'); ?></a>
    <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-support&tab=create')); ?>" class="page-title-action"><?php esc_html_e('Neues Ticket', 'themisdb-support-portal'); ?></a>
    <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-support-settings')); ?>" class="page-title-action"><?php esc_html_e('Einstellungen', 'themisdb-support-portal'); ?></a>
    <hr class="wp-header-end">

    <?php if (!empty($notice_message)): ?>
        <div class="notice notice-<?php echo esc_attr($notice_type); ?> is-dismissible">
            <p><?php echo esc_html($notice_message); ?></p>
        </div>
    <?php endif; ?>

    <nav class="nav-tab-wrapper themisdb-support-top-tabs">
        <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-support&tab=list')); ?>"
           class="nav-tab <?php echo $active_tab === 'list' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Ticket-Uebersicht', 'themisdb-support-portal'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-support&tab=create')); ?>"
           class="nav-tab <?php echo $active_tab === 'create' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Neues Ticket', 'themisdb-support-portal'); ?>
        </a>
    </nav>

    <?php
    $all_count = 0;
    foreach ((array) $status_counts as $status_key => $status_count) {
        $all_count += intval($status_count);
    }

    $open_count = isset($status_counts['open']) ? intval($status_counts['open']) : 0;
    $in_progress_count = isset($status_counts['in_progress']) ? intval($status_counts['in_progress']) : 0;
    $resolved_count = isset($status_counts['resolved']) ? intval($status_counts['resolved']) : 0;
    $closed_count = isset($status_counts['closed']) ? intval($status_counts['closed']) : 0;
    ?>

    <?php if ($active_tab === 'create'): ?>
        <div class="themisdb-admin-modules">
            <div class="card">
                <h2><?php esc_html_e('Schnellaktionen', 'themisdb-support-portal'); ?></h2>
                <p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-support&tab=list')); ?>" class="button button-primary"><?php esc_html_e('Zur Ticket-Uebersicht', 'themisdb-support-portal'); ?></a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-support-settings')); ?>" class="button"><?php esc_html_e('Portal konfigurieren', 'themisdb-support-portal'); ?></a>
                </p>
                <p><?php esc_html_e('Erstelle Support-Tickets direkt im Admin und springe bei Bedarf zur Uebersicht oder in die zentrale Portal-Konfiguration.', 'themisdb-support-portal'); ?></p>
            </div>

            <div class="card">
                <h2><?php esc_html_e('Aktuelle Ticketlage', 'themisdb-support-portal'); ?></h2>
                <table class="widefat striped">
                    <tbody>
                        <tr>
                            <th><?php esc_html_e('Alle Tickets', 'themisdb-support-portal'); ?></th>
                            <td><span data-dashboard-count="all"><?php echo esc_html($all_count); ?></span></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Offen', 'themisdb-support-portal'); ?></th>
                            <td><span data-dashboard-count="open"><?php echo esc_html($open_count); ?></span></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('In Bearbeitung', 'themisdb-support-portal'); ?></th>
                            <td><span data-dashboard-count="in_progress"><?php echo esc_html($in_progress_count); ?></span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card themisdb-support-create-ticket">
            <h2><?php esc_html_e('Neues Support-Ticket erstellen', 'themisdb-support-portal'); ?></h2>
            <form method="post" class="themisdb-support-create-form">
                <?php wp_nonce_field('themisdb_support_create_ticket', 'themisdb_support_nonce'); ?>
                <input type="hidden" name="themisdb_support_action" value="create_ticket">

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="themisdb-subject"><?php esc_html_e('Betreff', 'themisdb-support-portal'); ?></label></th>
                            <td><input type="text" name="subject" id="themisdb-subject" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="themisdb-message"><?php esc_html_e('Nachricht', 'themisdb-support-portal'); ?></label></th>
                            <td><textarea name="message" id="themisdb-message" class="large-text" rows="6" required></textarea></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="themisdb-priority"><?php esc_html_e('Prioritaet', 'themisdb-support-portal'); ?></label></th>
                            <td>
                                <select name="priority" id="themisdb-priority">
                                    <?php foreach ($priority_labels as $value => $label): ?>
                                        <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="themisdb-customer-name"><?php esc_html_e('Kundenname', 'themisdb-support-portal'); ?></label></th>
                            <td><input type="text" name="customer_name" id="themisdb-customer-name" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="themisdb-customer-email"><?php esc_html_e('Kunden-E-Mail', 'themisdb-support-portal'); ?></label></th>
                            <td><input type="email" name="customer_email" id="themisdb-customer-email" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="themisdb-customer-company"><?php esc_html_e('Firma', 'themisdb-support-portal'); ?></label></th>
                            <td><input type="text" name="customer_company" id="themisdb-customer-company" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="themisdb-license-key"><?php esc_html_e('Lizenzschluessel', 'themisdb-support-portal'); ?></label></th>
                            <td><input type="text" name="license_key" id="themisdb-license-key" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="themisdb-user-id"><?php esc_html_e('WordPress User-ID', 'themisdb-support-portal'); ?></label></th>
                            <td><input type="number" name="user_id" id="themisdb-user-id" class="small-text" min="0" step="1"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="themisdb-assignee-user-id"><?php esc_html_e('Bearbeiter zuweisen', 'themisdb-support-portal'); ?></label></th>
                            <td>
                                <select name="assignee_user_id" id="themisdb-assignee-user-id">
                                    <option value="0"><?php esc_html_e('Nicht zugewiesen', 'themisdb-support-portal'); ?></option>
                                    <?php foreach ((array) $assignable_agents as $agent): ?>
                                        <option value="<?php echo esc_attr($agent['id']); ?>"><?php echo esc_html($agent['label']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php esc_html_e('Benachrichtigungen werden bevorzugt an den zugewiesenen Bearbeiter versendet.', 'themisdb-support-portal'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php submit_button(__('Ticket erstellen', 'themisdb-support-portal')); ?>
            </form>
        </div>
    <?php else: ?>
        <div class="themisdb-admin-modules">
            <div class="card">
                <h2><?php esc_html_e('Schnellaktionen', 'themisdb-support-portal'); ?></h2>
                <p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-support&tab=create')); ?>" class="button button-primary"><?php esc_html_e('Neues Ticket', 'themisdb-support-portal'); ?></a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-support-settings')); ?>" class="button"><?php esc_html_e('Einstellungen', 'themisdb-support-portal'); ?></a>
                </p>
                <p><?php esc_html_e('Nutze die Uebersicht fuer Filter, Bulk-Aktionen und Ticketnavigation. Neue Tickets und Portal-Einstellungen bleiben direkt erreichbar.', 'themisdb-support-portal'); ?></p>
            </div>

            <div class="card">
                <h2><?php esc_html_e('Status-Ueberblick', 'themisdb-support-portal'); ?></h2>
                <table class="widefat striped">
                    <tbody>
                        <tr>
                            <th><?php esc_html_e('Alle Tickets', 'themisdb-support-portal'); ?></th>
                            <td><span data-dashboard-count="all"><?php echo esc_html($all_count); ?></span></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Offen', 'themisdb-support-portal'); ?></th>
                            <td><span data-dashboard-count="open"><?php echo esc_html($open_count); ?></span></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('In Bearbeitung', 'themisdb-support-portal'); ?></th>
                            <td><span data-dashboard-count="in_progress"><?php echo esc_html($in_progress_count); ?></span></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Geloest', 'themisdb-support-portal'); ?></th>
                            <td><span data-dashboard-count="resolved"><?php echo esc_html($resolved_count); ?></span></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Geschlossen', 'themisdb-support-portal'); ?></th>
                            <td><span data-dashboard-count="closed"><?php echo esc_html($closed_count); ?></span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <ul class="subsubsub themisdb-support-status-tabs">
            <?php $status_tab_keys = array_merge(array(''), array_keys($status_labels)); ?>
            <?php $status_tab_index = 0; ?>
            <?php foreach ($status_tab_keys as $status_key): ?>
                <?php
                $status_url_args = array(
                    'page' => 'themisdb-support',
                    'tab' => 'list',
                );
                if ($status_key !== '') {
                    $status_url_args['status'] = $status_key;
                }
                if (!empty($priority)) {
                    $status_url_args['priority'] = $priority;
                }
                if (!empty($assignee_user_id)) {
                    $status_url_args['assignee_user_id'] = intval($assignee_user_id);
                }

                $is_current_status = ($status_key === '' && $status === '') || ($status_key !== '' && $status === $status_key);
                $status_count_value = $status_key === ''
                    ? $all_count
                    : (isset($status_counts[$status_key]) ? intval($status_counts[$status_key]) : 0);
                $status_label_text = $status_key === ''
                    ? __('Alle', 'themisdb-support-portal')
                    : $status_labels[$status_key];
                ?>
                <li class="<?php echo $is_current_status ? 'current' : ''; ?>">
                    <a class="<?php echo $is_current_status ? 'current' : ''; ?>"
                       href="<?php echo esc_url(add_query_arg($status_url_args, admin_url('admin.php'))); ?>">
                        <?php echo esc_html($status_label_text); ?> <span class="count" data-status-tab-count="<?php echo esc_attr($status_key === '' ? 'all' : $status_key); ?>">(<?php echo esc_html($status_count_value); ?>)</span>
                    </a>
                    <?php if ($status_tab_index < count($status_tab_keys) - 1): ?> | <?php endif; ?>
                </li>
                <?php $status_tab_index++; ?>
            <?php endforeach; ?>
        </ul>

        <?php
        $quick_filter_links = array(
            'open_high' => array(
                'label' => __('Offen + Hoch', 'themisdb-support-portal'),
                'args' => array('status' => 'open', 'priority' => 'high'),
                'active' => ($status === 'open' && $priority === 'high'),
            ),
            'open_urgent' => array(
                'label' => __('Offen + Dringend', 'themisdb-support-portal'),
                'args' => array('status' => 'open', 'priority' => 'urgent'),
                'active' => ($status === 'open' && $priority === 'urgent'),
            ),
            'progress_high' => array(
                'label' => __('In Bearbeitung + Hoch', 'themisdb-support-portal'),
                'args' => array('status' => 'in_progress', 'priority' => 'high'),
                'active' => ($status === 'in_progress' && $priority === 'high'),
            ),
        );
        ?>
        <ul class="subsubsub themisdb-support-quick-tabs">
            <?php $quick_tab_index = 0; ?>
            <?php foreach ($quick_filter_links as $quick_filter_key => $quick_link): ?>
                <?php
                $quick_args = array_merge(
                    array('page' => 'themisdb-support', 'tab' => 'list'),
                    $quick_link['args']
                );
                if (!empty($assignee_user_id)) {
                    $quick_args['assignee_user_id'] = intval($assignee_user_id);
                }
                $quick_filter_count = isset($quick_filter_counts[$quick_filter_key]) 
                    ? intval($quick_filter_counts[$quick_filter_key]) 
                    : 0;
                ?>
                <li class="<?php echo !empty($quick_link['active']) ? 'current' : ''; ?>" data-quick-filter-key="<?php echo esc_attr($quick_filter_key); ?>">
                    <a class="<?php echo !empty($quick_link['active']) ? 'current' : ''; ?>"
                       href="<?php echo esc_url(add_query_arg($quick_args, admin_url('admin.php'))); ?>">
                        <?php echo esc_html($quick_link['label']); ?>
                        <span class="themisdb-support-quick-filter-count">
                            (<?php echo $quick_filter_count; ?>)
                        </span>
                    </a>
                    <?php if ($quick_tab_index < count($quick_filter_links) - 1): ?> | <?php endif; ?>
                </li>
                <?php $quick_tab_index++; ?>
            <?php endforeach; ?>
        </ul>

        <div class="themisdb-support-admin-filters">
            <form method="get" class="themisdb-support-filter-form">
                <input type="hidden" name="page" value="themisdb-support">
                <input type="hidden" name="tab" value="list">

                <label for="filter-status" class="screen-reader-text"><?php esc_html_e('Status', 'themisdb-support-portal'); ?></label>
                <select id="filter-status" name="status">
                    <option value=""><?php esc_html_e('Alle Status', 'themisdb-support-portal'); ?></option>
                    <?php foreach ($status_labels as $value => $label): ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($status, $value); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="filter-priority" class="screen-reader-text"><?php esc_html_e('Prioritaet', 'themisdb-support-portal'); ?></label>
                <select id="filter-priority" name="priority">
                    <option value=""><?php esc_html_e('Alle Prioritaeten', 'themisdb-support-portal'); ?></option>
                    <?php foreach ($priority_labels as $value => $label): ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($priority, $value); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="filter-assignee" class="screen-reader-text"><?php esc_html_e('Bearbeiter', 'themisdb-support-portal'); ?></label>
                <select id="filter-assignee" name="assignee_user_id">
                    <option value="0"><?php esc_html_e('Alle Bearbeiter', 'themisdb-support-portal'); ?></option>
                    <?php foreach ((array) $assignable_agents as $agent): ?>
                        <option value="<?php echo esc_attr($agent['id']); ?>" <?php selected(intval($assignee_user_id), intval($agent['id'])); ?>>
                            <?php echo esc_html($agent['label']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="button"><?php esc_html_e('Filtern', 'themisdb-support-portal'); ?></button>

                <?php if ($status || $priority || !empty($assignee_user_id)): ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-support&tab=list')); ?>" class="button button-secondary">
                        <?php esc_html_e('Alle Filter zuruecksetzen', 'themisdb-support-portal'); ?>
                    </a>
                <?php endif; ?>

                <?php if ($status): ?>
                    <a href="<?php echo esc_url(add_query_arg(array('page' => 'themisdb-support', 'tab' => 'list', 'priority' => $priority, 'assignee_user_id' => intval($assignee_user_id)), admin_url('admin.php'))); ?>" class="button button-link">
                        <?php esc_html_e('Nur Status zuruecksetzen', 'themisdb-support-portal'); ?>
                    </a>
                <?php endif; ?>

                <?php if ($priority): ?>
                    <a href="<?php echo esc_url(add_query_arg(array('page' => 'themisdb-support', 'tab' => 'list', 'status' => $status, 'assignee_user_id' => intval($assignee_user_id)), admin_url('admin.php'))); ?>" class="button button-link">
                        <?php esc_html_e('Nur Prioritaet zuruecksetzen', 'themisdb-support-portal'); ?>
                    </a>
                <?php endif; ?>

                <?php if (!empty($assignee_user_id)): ?>
                    <a href="<?php echo esc_url(add_query_arg(array('page' => 'themisdb-support', 'tab' => 'list', 'status' => $status, 'priority' => $priority), admin_url('admin.php'))); ?>" class="button button-link">
                        <?php esc_html_e('Nur Bearbeiter zuruecksetzen', 'themisdb-support-portal'); ?>
                    </a>
                <?php endif; ?>
            </form>

            <span class="themisdb-support-ticket-count-total" data-visible-ticket-count>
                <?php echo esc_html(sprintf(_n('%d Ticket', '%d Tickets', $total, 'themisdb-support-portal'), $total)); ?>
            </span>
        </div>

        <?php if (empty($tickets)): ?>
            <div class="themisdb-support-admin-empty">
                <span class="dashicons dashicons-clipboard"></span>
                <p><?php esc_html_e('Keine Tickets gefunden.', 'themisdb-support-portal'); ?></p>
            </div>
        <?php else: ?>
            <?php
            $ticket_link_base_args = array(
                'page' => 'themisdb-support',
                'tab' => 'list',
            );
            if (!empty($status)) {
                $ticket_link_base_args['status'] = $status;
            }
            if (!empty($priority)) {
                $ticket_link_base_args['priority'] = $priority;
            }
            if (!empty($assignee_user_id)) {
                $ticket_link_base_args['assignee_user_id'] = intval($assignee_user_id);
            }
            if (!empty($paged) && intval($paged) > 1) {
                $ticket_link_base_args['paged'] = intval($paged);
            }
            ?>
            <form method="post" class="themisdb-support-bulk-form">
                <?php wp_nonce_field('themisdb_support_bulk_tickets', 'themisdb_support_nonce'); ?>
                <input type="hidden" name="themisdb_support_action" value="bulk_tickets">
                <input type="hidden" name="bulk_action" value="">
                <input type="hidden" name="current_status" value="<?php echo esc_attr($status); ?>">
                <input type="hidden" name="current_priority" value="<?php echo esc_attr($priority); ?>">
                <input type="hidden" name="current_assignee_user_id" value="<?php echo esc_attr(intval($assignee_user_id)); ?>">
                <input type="hidden" name="current_paged" value="<?php echo esc_attr($paged); ?>">

                <div class="tablenav top">
                    <div class="alignleft actions bulkactions">
                        <label for="bulk-action-selector-top" class="screen-reader-text"><?php esc_html_e('Bulk-Aktion auswaehlen', 'themisdb-support-portal'); ?></label>
                        <select name="bulk_action_top" id="bulk-action-selector-top">
                            <option value=""><?php esc_html_e('Bulk-Aktionen', 'themisdb-support-portal'); ?></option>
                            <option value="status_open"><?php esc_html_e('Status auf Offen setzen', 'themisdb-support-portal'); ?></option>
                            <option value="status_in_progress"><?php esc_html_e('Status auf In Bearbeitung setzen', 'themisdb-support-portal'); ?></option>
                            <option value="status_resolved"><?php esc_html_e('Status auf Geloest setzen', 'themisdb-support-portal'); ?></option>
                            <option value="status_closed"><?php esc_html_e('Status auf Geschlossen setzen', 'themisdb-support-portal'); ?></option>
                            <?php $render_bulk_assignment_options($assignable_agents); ?>
                            <option value="delete"><?php esc_html_e('Loeschen', 'themisdb-support-portal'); ?></option>
                        </select>
                        <button type="submit" class="button action themisdb-support-bulk-apply" data-bulk-source="top"><?php esc_html_e('Uebernehmen', 'themisdb-support-portal'); ?></button>
                    </div>
                    <div class="alignleft actions themisdb-support-bulk-preview" data-preview-source="top">
                        <?php esc_html_e('Keine Bulk-Aktion ausgewaehlt.', 'themisdb-support-portal'); ?>
                    </div>
                    <div class="alignleft actions themisdb-support-selection-tools">
                        <label>
                            <input type="checkbox" class="themisdb-support-show-selected-only" data-selection-source="top">
                            <?php esc_html_e('Nur ausgewaehlte anzeigen', 'themisdb-support-portal'); ?>
                        </label>
                    </div>
                    <br class="clear">
                </div>

                <table class="wp-list-table widefat fixed striped themisdb-support-tickets-table">
                    <thead>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <label class="screen-reader-text" for="cb-select-all-1"><?php esc_html_e('Alle auswaehlen', 'themisdb-support-portal'); ?></label>
                                <input id="cb-select-all-1" type="checkbox" class="themisdb-support-select-all">
                            </td>
                            <th scope="col" class="column-ticket-number"><?php esc_html_e('Ticket-Nr.', 'themisdb-support-portal'); ?></th>
                            <th scope="col" class="column-subject"><?php esc_html_e('Betreff', 'themisdb-support-portal'); ?></th>
                            <th scope="col" class="column-customer"><?php esc_html_e('Kunde', 'themisdb-support-portal'); ?></th>
                            <th scope="col" class="column-status"><?php esc_html_e('Status', 'themisdb-support-portal'); ?></th>
                            <th scope="col" class="column-priority"><?php esc_html_e('Prioritaet', 'themisdb-support-portal'); ?></th>
                            <th scope="col" class="column-assignee"><?php esc_html_e('Bearbeiter', 'themisdb-support-portal'); ?></th>
                            <th scope="col" class="column-date"><?php esc_html_e('Datum', 'themisdb-support-portal'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $ticket): ?>
                            <tr class="themisdb-support-row-status-<?php echo esc_attr($ticket['status']); ?>">
                                <?php
                                $ticket_link_args = array_merge($ticket_link_base_args, array('ticket_id' => intval($ticket['id'])));
                                $ticket_detail_url = add_query_arg($ticket_link_args, admin_url('admin.php'));
                                ?>
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="ticket_ids[]" value="<?php echo esc_attr($ticket['id']); ?>" class="themisdb-support-ticket-checkbox">
                                </th>
                                <td class="column-ticket-number">
                                    <strong>
                                        <a href="<?php echo esc_url($ticket_detail_url); ?>">
                                            <?php echo esc_html($ticket['ticket_number']); ?>
                                        </a>
                                    </strong>
                                </td>
                                <td class="column-subject">
                                    <a href="<?php echo esc_url($ticket_detail_url); ?>">
                                        <?php echo esc_html($ticket['subject']); ?>
                                    </a>
                                    <div class="row-actions">
                                        <span class="view"><a href="<?php echo esc_url($ticket_detail_url); ?>"><?php esc_html_e('Ansehen', 'themisdb-support-portal'); ?></a></span>
                                    </div>
                                </td>                                <td class="column-customer">
                                    <?php echo esc_html($ticket['customer_name']); ?><br>
                                    <small><a href="mailto:<?php echo esc_attr($ticket['customer_email']); ?>"><?php echo esc_html($ticket['customer_email']); ?></a></small>
                                    <?php if ($ticket['customer_company']): ?>
                                        <br><small><?php echo esc_html($ticket['customer_company']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="column-status">
                                    <div class="themisdb-support-inline-status-control" data-ticket-id="<?php echo esc_attr(intval($ticket['id'])); ?>">
                                        <select class="themisdb-support-inline-status-select" data-ticket-id="<?php echo esc_attr(intval($ticket['id'])); ?>">
                                            <?php foreach ($status_labels as $value => $label): ?>
                                                <option value="<?php echo esc_attr($value); ?>" <?php selected($ticket['status'], $value); ?>>
                                                    <?php echo esc_html($label); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="themisdb-support-inline-status-message" data-ticket-id="<?php echo esc_attr(intval($ticket['id'])); ?>"></div>
                                    </div>
                                </td>
                                <td class="column-priority">
                                    <span class="themisdb-support-admin-priority themisdb-support-priority-<?php echo esc_attr($ticket['priority']); ?>">
                                        <?php echo esc_html(isset($priority_labels[$ticket['priority']]) ? $priority_labels[$ticket['priority']] : $ticket['priority']); ?>
                                    </span>
                                </td>
                                <td class="column-assignee">
                                    <?php
                                    $ticket_assignee_user_id = isset($ticket['assignee_user_id']) ? intval($ticket['assignee_user_id']) : 0;
                                    $quick_assign_form_id = 'themisdb-quick-assign-' . intval($ticket['id']);
                                    ?>
                                    <select
                                        name="assignee_user_id"
                                        form="<?php echo esc_attr($quick_assign_form_id); ?>"
                                        class="themisdb-support-quick-assign-select"
                                        data-ticket-id="<?php echo esc_attr(intval($ticket['id'])); ?>"
                                        data-form-id="<?php echo esc_attr($quick_assign_form_id); ?>">
                                        <option value="0"><?php esc_html_e('Nicht zugewiesen', 'themisdb-support-portal'); ?></option>
                                        <?php foreach ((array) $assignable_agents as $agent): ?>
                                            <option value="<?php echo esc_attr($agent['id']); ?>" <?php selected($ticket_assignee_user_id, intval($agent['id'])); ?>>
                                                <?php echo esc_html($agent['label']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button
                                        type="submit"
                                        class="button button-small themisdb-support-quick-assign-button"
                                        form="<?php echo esc_attr($quick_assign_form_id); ?>"
                                        data-ticket-id="<?php echo esc_attr(intval($ticket['id'])); ?>"
                                        data-form-id="<?php echo esc_attr($quick_assign_form_id); ?>">
                                        <?php esc_html_e('Speichern', 'themisdb-support-portal'); ?>
                                    </button>
                                    <div class="themisdb-support-quick-assign-message" data-ticket-id="<?php echo esc_attr(intval($ticket['id'])); ?>"></div>
                                </td>
                                <td class="column-date">
                                    <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($ticket['created_at']))); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <label class="screen-reader-text" for="cb-select-all-2"><?php esc_html_e('Alle auswaehlen', 'themisdb-support-portal'); ?></label>
                                <input id="cb-select-all-2" type="checkbox" class="themisdb-support-select-all">
                            </td>
                            <th scope="col" class="column-ticket-number"><?php esc_html_e('Ticket-Nr.', 'themisdb-support-portal'); ?></th>
                            <th scope="col" class="column-subject"><?php esc_html_e('Betreff', 'themisdb-support-portal'); ?></th>
                            <th scope="col" class="column-customer"><?php esc_html_e('Kunde', 'themisdb-support-portal'); ?></th>
                            <th scope="col" class="column-status"><?php esc_html_e('Status', 'themisdb-support-portal'); ?></th>
                            <th scope="col" class="column-priority"><?php esc_html_e('Prioritaet', 'themisdb-support-portal'); ?></th>
                            <th scope="col" class="column-assignee"><?php esc_html_e('Bearbeiter', 'themisdb-support-portal'); ?></th>
                            <th scope="col" class="column-date"><?php esc_html_e('Datum', 'themisdb-support-portal'); ?></th>
                        </tr>
                    </tfoot>
                </table>

                <div class="tablenav bottom">
                    <div class="alignleft actions bulkactions">
                        <label for="bulk-action-selector-bottom" class="screen-reader-text"><?php esc_html_e('Bulk-Aktion auswaehlen', 'themisdb-support-portal'); ?></label>
                        <select name="bulk_action_bottom" id="bulk-action-selector-bottom">
                            <option value=""><?php esc_html_e('Bulk-Aktionen', 'themisdb-support-portal'); ?></option>
                            <option value="status_open"><?php esc_html_e('Status auf Offen setzen', 'themisdb-support-portal'); ?></option>
                            <option value="status_in_progress"><?php esc_html_e('Status auf In Bearbeitung setzen', 'themisdb-support-portal'); ?></option>
                            <option value="status_resolved"><?php esc_html_e('Status auf Geloest setzen', 'themisdb-support-portal'); ?></option>
                            <option value="status_closed"><?php esc_html_e('Status auf Geschlossen setzen', 'themisdb-support-portal'); ?></option>
                            <?php $render_bulk_assignment_options($assignable_agents); ?>
                            <option value="delete"><?php esc_html_e('Loeschen', 'themisdb-support-portal'); ?></option>
                        </select>
                        <button type="submit" class="button action themisdb-support-bulk-apply" data-bulk-source="bottom"><?php esc_html_e('Uebernehmen', 'themisdb-support-portal'); ?></button>
                    </div>
                    <div class="alignleft actions themisdb-support-bulk-preview" data-preview-source="bottom">
                        <?php esc_html_e('Keine Bulk-Aktion ausgewaehlt.', 'themisdb-support-portal'); ?>
                    </div>
                    <div class="alignleft actions themisdb-support-selection-tools">
                        <label>
                            <input type="checkbox" class="themisdb-support-show-selected-only" data-selection-source="bottom">
                            <?php esc_html_e('Nur ausgewaehlte anzeigen', 'themisdb-support-portal'); ?>
                        </label>
                    </div>
                    <br class="clear">
                </div>
            </form>

            <?php foreach ($tickets as $ticket): ?>
                <form method="post" id="<?php echo esc_attr('themisdb-quick-assign-' . intval($ticket['id'])); ?>" class="themisdb-support-quick-assign-form">
                    <?php wp_nonce_field('themisdb_support_quick_assign_ticket', 'themisdb_support_nonce'); ?>
                    <input type="hidden" name="themisdb_support_action" value="quick_assign_ticket">
                    <input type="hidden" name="ticket_id" value="<?php echo esc_attr(intval($ticket['id'])); ?>">
                    <input type="hidden" name="current_status" value="<?php echo esc_attr($status); ?>">
                    <input type="hidden" name="current_priority" value="<?php echo esc_attr($priority); ?>">
                    <input type="hidden" name="current_assignee_user_id" value="<?php echo esc_attr(intval($assignee_user_id)); ?>">
                    <input type="hidden" name="current_paged" value="<?php echo esc_attr($paged); ?>">
                </form>
            <?php endforeach; ?>

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
                            echo '<span class="displaying-num">' . esc_html(sprintf(_n('%d Ticket', '%d Tickets', $total, 'themisdb-support-portal'), $total)) . '</span>';
                            echo wp_kses_post($page_links);
                        }
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>
