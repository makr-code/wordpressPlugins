<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB - Hybrid Database System                                   ║
╠═════════════════════════════════════════════════════════════════════╣
  File:            class-shortcodes.php                               ║
  Plugin:          themisdb-support-portal                            ║
  Version:         1.0.0                                              ║
╚═════════════════════════════════════════════════════════════════════╝
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Frontend shortcodes for the ThemisDB Support Portal.
 *
 * Usage:
 *   [themisdb_support_portal]
 *       Renders the full portal: login form (license upload) for unauthenticated
 *       visitors, or the ticket list + new-ticket form for licensed users.
 *
 *   [themisdb_support_login]
 *       Renders only the license file login form.
 */
class ThemisDB_Support_Shortcodes {

    public function __construct() {
        add_shortcode('themisdb_support_portal', array($this, 'portal_shortcode'));
        add_shortcode('themisdb_support_login',  array($this, 'login_shortcode'));

        // AJAX handler for new ticket submission
        add_action('wp_ajax_themisdb_support_new_ticket',        array($this, 'handle_new_ticket'));
        add_action('wp_ajax_nopriv_themisdb_support_new_ticket', array($this, 'handle_new_ticket_denied'));

        // AJAX handler for fetching a single ticket detail
        add_action('wp_ajax_themisdb_support_get_ticket', array($this, 'handle_get_ticket'));
    }

    // -------------------------------------------------------------------------
    // Shortcodes
    // -------------------------------------------------------------------------

    /**
     * Main support portal shortcode.
     *
     * @param array $atts Shortcode attributes (unused, reserved for future use).
     * @return string HTML output
     */
    public function portal_shortcode($atts) {
        if (!ThemisDB_Support_License_Auth::current_user_has_license()) {
            return $this->render_login_form();
        }

        return $this->render_portal();
    }

    /**
     * Standalone login-form shortcode (useful to embed on a custom login page).
     *
     * @return string
     */
    public function login_shortcode($atts) {
        if (ThemisDB_Support_License_Auth::current_user_has_license()) {
            $redirect = get_option('themisdb_support_redirect_url', home_url('/'));
            return '<p class="themisdb-support-notice themisdb-support-notice-info">'
                . esc_html__('Sie sind bereits angemeldet.', 'themisdb-support-portal')
                . ' <a href="' . esc_url($redirect) . '">'
                . esc_html__('Zum Support-Portal', 'themisdb-support-portal')
                . '</a></p>';
        }

        return $this->render_login_form();
    }

    // -------------------------------------------------------------------------
    // AJAX Handlers
    // -------------------------------------------------------------------------

    /**
     * Handle new ticket submission.
     */
    public function handle_new_ticket() {
        check_ajax_referer('themisdb_support_nonce', 'nonce');

        if (!ThemisDB_Support_License_Auth::current_user_has_license()) {
            wp_send_json_error(array(
                'message' => __('Zugriff verweigert. Bitte melden Sie sich mit Ihrer Lizenzdatei an.', 'themisdb-support-portal'),
            ));
        }

        $subject = isset($_POST['subject']) ? sanitize_text_field(wp_unslash($_POST['subject'])) : '';
        $message = isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '';
        $priority = isset($_POST['priority']) ? sanitize_text_field($_POST['priority']) : 'normal';

        if (empty($subject) || empty(trim($message))) {
            wp_send_json_error(array(
                'message' => __('Bitte füllen Sie Betreff und Nachricht aus.', 'themisdb-support-portal'),
            ));
        }

        $user         = wp_get_current_user();
        $license_key  = get_user_meta($user->ID, 'themisdb_support_license_key', true);

        // Try to get the license key from the order-request plugin as well
        if (empty($license_key) && class_exists('ThemisDB_License_Manager')) {
            $license_id = get_user_meta($user->ID, 'themisdb_license_id', true);
            if ($license_id) {
                $license = ThemisDB_License_Manager::get_license(intval($license_id));
                if ($license) {
                    $license_key = $license['license_key'];
                }
            }
        }

        $company = get_user_meta($user->ID, 'company', true) ?: '';

        $ticket_id = ThemisDB_SupportPortal_Ticket_Manager::create_ticket(array(
            'subject'          => $subject,
            'message'          => $message,
            'priority'         => $priority,
            'customer_name'    => $user->display_name,
            'customer_email'   => $user->user_email,
            'customer_company' => $company,
            'license_key'      => $license_key,
            'user_id'          => $user->ID,
        ));

        if (!$ticket_id) {
            $error_message = ThemisDB_SupportPortal_Ticket_Manager::get_last_error();
            wp_send_json_error(array(
                'message' => !empty($error_message)
                    ? $error_message
                    : __('Ticket konnte nicht erstellt werden. Bitte versuchen Sie es erneut.', 'themisdb-support-portal'),
            ));
        }

        $ticket = ThemisDB_SupportPortal_Ticket_Manager::get_ticket($ticket_id);

        wp_send_json_success(array(
            'message'       => __('Ihr Support-Ticket wurde erfolgreich erstellt!', 'themisdb-support-portal'),
            'ticket_number' => $ticket['ticket_number'],
            'ticket_id'     => $ticket_id,
        ));
    }

    /**
     * Reject ticket submission for unauthenticated users.
     */
    public function handle_new_ticket_denied() {
        check_ajax_referer('themisdb_support_nonce', 'nonce');
        wp_send_json_error(array(
            'message' => __('Bitte melden Sie sich mit Ihrer Lizenzdatei an.', 'themisdb-support-portal'),
        ));
    }

    /**
     * Return ticket details for the inline detail view.
     */
    public function handle_get_ticket() {
        check_ajax_referer('themisdb_support_nonce', 'nonce');

        if (!ThemisDB_Support_License_Auth::current_user_has_license()) {
            wp_send_json_error(array('message' => __('Zugriff verweigert', 'themisdb-support-portal')));
        }

        $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
        $ticket    = ThemisDB_SupportPortal_Ticket_Manager::get_ticket($ticket_id);

        if (!$ticket) {
            wp_send_json_error(array('message' => __('Ticket nicht gefunden', 'themisdb-support-portal')));
        }

        // Non-admin users may only view their own tickets
        if (!current_user_can('manage_options') && intval($ticket['user_id']) !== get_current_user_id()) {
            wp_send_json_error(array('message' => __('Zugriff verweigert', 'themisdb-support-portal')));
        }

        $messages        = ThemisDB_SupportPortal_Ticket_Manager::get_messages($ticket_id);
        $status_labels   = ThemisDB_SupportPortal_Ticket_Manager::get_status_labels();
        $priority_labels = ThemisDB_SupportPortal_Ticket_Manager::get_priority_labels();

        ob_start();
        include THEMISDB_SUPPORT_PLUGIN_DIR . 'templates/portal-ticket-detail.php';
        $html = ob_get_clean();

        wp_send_json_success(array('html' => $html));
    }

    // -------------------------------------------------------------------------
    // Renderers
    // -------------------------------------------------------------------------

    /**
     * Render the license-file login form.
     *
     * @return string
     */
    private function render_login_form() {
        ob_start();
        ?>
        <div class="themisdb-support-login-wrap">
            <div class="themisdb-support-login-box">
                <div class="themisdb-support-login-header">
                    <span class="themisdb-support-logo dashicons dashicons-sos"></span>
                    <h2><?php esc_html_e('ThemisDB Support-Portal', 'themisdb-support-portal'); ?></h2>
                    <p class="themisdb-support-subtitle">
                        <?php esc_html_e('Bitte melden Sie sich mit Ihrer ThemisDB-Lizenzdatei an, um Zugang zum Support-System zu erhalten.', 'themisdb-support-portal'); ?>
                    </p>
                </div>

                <form id="themisdb-support-license-form" method="post" enctype="multipart/form-data" novalidate>
                    <?php wp_nonce_field('themisdb_support_nonce', 'themisdb_support_nonce_field'); ?>

                    <div class="themisdb-support-upload-area" id="themisdb-support-upload-area">
                        <span class="dashicons dashicons-upload themisdb-support-upload-icon"></span>
                        <p class="themisdb-support-upload-label">
                            <?php esc_html_e('Lizenzdatei hierher ziehen oder klicken zum Auswählen', 'themisdb-support-portal'); ?>
                        </p>
                        <p class="themisdb-support-upload-hint">
                            <?php esc_html_e('Nur .json Dateien werden akzeptiert', 'themisdb-support-portal'); ?>
                        </p>
                        <input type="file" id="themisdb-support-license-file" name="license_file" accept=".json" class="themisdb-support-file-input">
                        <p class="themisdb-support-file-name" id="themisdb-support-file-name" style="display:none;"></p>
                    </div>

                    <div class="themisdb-support-form-actions">
                        <button type="submit" id="themisdb-support-login-btn" class="themisdb-support-btn themisdb-support-btn-primary" disabled>
                            <?php esc_html_e('Mit Lizenz anmelden', 'themisdb-support-portal'); ?>
                        </button>
                    </div>

                    <div class="themisdb-support-messages" id="themisdb-support-login-messages"></div>
                </form>

                <div class="themisdb-support-login-footer">
                    <p><?php esc_html_e('Sie haben noch keine Lizenz?', 'themisdb-support-portal'); ?>
                        <a href="<?php echo esc_url(home_url('/')); ?>" target="_blank">
                            <?php esc_html_e('Jetzt erwerben', 'themisdb-support-portal'); ?>
                        </a>
                    </p>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render the authenticated support portal (ticket list + new ticket form).
     *
     * @return string
     */
    private function render_portal() {
        $user    = wp_get_current_user();
        $tickets = ThemisDB_SupportPortal_Ticket_Manager::get_user_tickets($user->ID);

        $status_labels   = ThemisDB_SupportPortal_Ticket_Manager::get_status_labels();
        $priority_labels = ThemisDB_SupportPortal_Ticket_Manager::get_priority_labels();

        // Retrieve license info for display
        $license_info = $this->get_current_user_license_info($user->ID);
        $support_benefit_info = $this->get_current_user_support_benefit_info($user->ID);

        $new_ticket_allowed = true;
        $limit_reason = '';
        if (!empty($support_benefit_info) && !empty($support_benefit_info['benefit_id']) && class_exists('ThemisDB_Support_Benefits_Manager')) {
            $check = ThemisDB_Support_Benefits_Manager::check_limits(intval($support_benefit_info['benefit_id']), 'normal');
            if (is_array($check) && isset($check['allowed']) && !$check['allowed']) {
                $new_ticket_allowed = false;
                $limit_reason = isset($check['reason']) ? $check['reason'] : '';
            }
        }

        ob_start();
        ?>
        <div class="themisdb-support-portal-wrap">

            <!-- Portal Header -->
            <div class="themisdb-support-portal-header">
                <div class="themisdb-support-portal-header-left">
                    <span class="dashicons dashicons-sos"></span>
                    <h2><?php esc_html_e('ThemisDB Support-Portal', 'themisdb-support-portal'); ?></h2>
                </div>
                <div class="themisdb-support-portal-header-right">
                    <?php if ($license_info): ?>
                        <span class="themisdb-support-license-badge themisdb-support-license-<?php echo esc_attr($license_info['edition']); ?>">
                            <?php echo esc_html(strtoupper($license_info['edition'])); ?>
                        </span>
                    <?php endif; ?>
                    <span class="themisdb-support-user-name"><?php echo esc_html($user->display_name); ?></span>
                    <a href="#" id="themisdb-support-logout-btn" class="themisdb-support-btn themisdb-support-btn-secondary themisdb-support-btn-sm">
                        <?php esc_html_e('Abmelden', 'themisdb-support-portal'); ?>
                    </a>
                </div>
            </div>

            <?php if ($support_benefit_info): ?>
                <div class="themisdb-support-benefit-banner">
                    <div class="themisdb-support-benefit-grid">
                        <div>
                            <strong><?php esc_html_e('Support-Tier', 'themisdb-support-portal'); ?>:</strong>
                            <?php echo esc_html($support_benefit_info['tier_label']); ?>
                        </div>
                        <div>
                            <strong><?php esc_html_e('Status', 'themisdb-support-portal'); ?>:</strong>
                            <?php echo esc_html($support_benefit_info['status_label']); ?>
                        </div>
                        <div>
                            <strong><?php esc_html_e('Antwort-SLA', 'themisdb-support-portal'); ?>:</strong>
                            <?php echo esc_html($support_benefit_info['sla_label']); ?>
                        </div>
                        <div>
                            <strong><?php esc_html_e('Offene Tickets', 'themisdb-support-portal'); ?>:</strong>
                            <?php echo esc_html($support_benefit_info['open_tickets_label']); ?>
                        </div>
                    </div>
                    <?php if (!empty($support_benefit_info['expires_at_label'])): ?>
                        <p class="themisdb-support-benefit-meta">
                            <em><?php echo esc_html($support_benefit_info['expires_at_label']); ?></em>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- New Ticket Form -->
            <div class="themisdb-support-section">
                <div class="themisdb-support-section-header">
                    <h3><?php esc_html_e('Neues Ticket erstellen', 'themisdb-support-portal'); ?></h3>
                    <button type="button" id="themisdb-support-toggle-form" class="themisdb-support-btn themisdb-support-btn-primary" <?php disabled(!$new_ticket_allowed); ?>>
                        <span class="dashicons dashicons-plus"></span>
                        <?php echo esc_html($new_ticket_allowed ? __('Neues Ticket', 'themisdb-support-portal') : __('Limit erreicht', 'themisdb-support-portal')); ?>
                    </button>
                </div>

                <?php if (!$new_ticket_allowed): ?>
                    <div class="themisdb-support-limit-warning">
                        <?php echo esc_html(!empty($limit_reason) ? $limit_reason : __('Sie haben Ihr aktuelles Ticket-Limit erreicht.', 'themisdb-support-portal')); ?>
                    </div>
                <?php endif; ?>

                <div id="themisdb-support-new-ticket-form" class="themisdb-support-form-wrap" style="display:none;">
                    <form id="themisdb-new-ticket-form" method="post" novalidate>
                        <?php wp_nonce_field('themisdb_support_nonce', 'themisdb_support_nonce_field'); ?>

                        <div class="themisdb-support-form-row">
                            <div class="themisdb-support-form-group">
                                <label for="themisdb-ticket-subject">
                                    <?php esc_html_e('Betreff', 'themisdb-support-portal'); ?> <span class="themisdb-required">*</span>
                                </label>
                                <input type="text" id="themisdb-ticket-subject" name="subject" required
                                    placeholder="<?php esc_attr_e('Kurze Beschreibung des Problems', 'themisdb-support-portal'); ?>">
                            </div>
                            <div class="themisdb-support-form-group themisdb-support-form-group-sm">
                                <label for="themisdb-ticket-priority">
                                    <?php esc_html_e('Priorität', 'themisdb-support-portal'); ?>
                                </label>
                                <select id="themisdb-ticket-priority" name="priority" <?php disabled(!$new_ticket_allowed); ?>>
                                    <?php foreach ($priority_labels as $value => $label): ?>
                                        <option value="<?php echo esc_attr($value); ?>"
                                            <?php selected($value, 'normal'); ?>>
                                            <?php echo esc_html($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="themisdb-support-form-group">
                            <label for="themisdb-ticket-message">
                                <?php esc_html_e('Nachricht', 'themisdb-support-portal'); ?> <span class="themisdb-required">*</span>
                            </label>
                            <textarea id="themisdb-ticket-message" name="message" rows="6" required <?php disabled(!$new_ticket_allowed); ?>
                                placeholder="<?php esc_attr_e('Bitte beschreiben Sie das Problem so detailliert wie möglich…', 'themisdb-support-portal'); ?>"></textarea>
                        </div>

                        <div class="themisdb-support-form-actions">
                            <button type="submit" id="themisdb-submit-ticket" class="themisdb-support-btn themisdb-support-btn-primary" <?php disabled(!$new_ticket_allowed); ?>>
                                <?php esc_html_e('Ticket senden', 'themisdb-support-portal'); ?>
                            </button>
                            <button type="button" id="themisdb-cancel-ticket" class="themisdb-support-btn themisdb-support-btn-secondary">
                                <?php esc_html_e('Abbrechen', 'themisdb-support-portal'); ?>
                            </button>
                        </div>

                        <div class="themisdb-support-messages" id="themisdb-new-ticket-messages"></div>
                    </form>
                </div>
            </div>

            <!-- Ticket List -->
            <div class="themisdb-support-section">
                <div class="themisdb-support-section-header">
                    <h3><?php esc_html_e('Meine Tickets', 'themisdb-support-portal'); ?></h3>
                    <span class="themisdb-support-ticket-count">
                        <?php echo esc_html(sprintf(
                            /* translators: %d: ticket count */
                            _n('%d Ticket', '%d Tickets', count($tickets), 'themisdb-support-portal'),
                            count($tickets)
                        )); ?>
                    </span>
                </div>

                <?php if (empty($tickets)): ?>
                    <div class="themisdb-support-empty">
                        <span class="dashicons dashicons-clipboard"></span>
                        <p><?php esc_html_e('Sie haben noch keine Support-Tickets erstellt.', 'themisdb-support-portal'); ?></p>
                    </div>
                <?php else: ?>
                    <div class="themisdb-support-ticket-list">
                        <?php foreach ($tickets as $ticket): ?>
                            <div class="themisdb-support-ticket-row themisdb-support-status-<?php echo esc_attr($ticket['status']); ?>">
                                <div class="themisdb-support-ticket-meta">
                                    <span class="themisdb-support-ticket-number">
                                        <?php echo esc_html($ticket['ticket_number']); ?>
                                    </span>
                                    <span class="themisdb-support-ticket-date">
                                        <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($ticket['created_at']))); ?>
                                    </span>
                                </div>
                                <div class="themisdb-support-ticket-main">
                                    <a href="#" class="themisdb-support-ticket-subject themisdb-view-ticket"
                                        data-ticket-id="<?php echo esc_attr($ticket['id']); ?>">
                                        <?php echo esc_html($ticket['subject']); ?>
                                    </a>
                                </div>
                                <div class="themisdb-support-ticket-badges">
                                    <span class="themisdb-support-badge themisdb-support-status-badge themisdb-support-status-<?php echo esc_attr($ticket['status']); ?>">
                                        <?php echo esc_html(isset($status_labels[$ticket['status']]) ? $status_labels[$ticket['status']] : $ticket['status']); ?>
                                    </span>
                                    <span class="themisdb-support-badge themisdb-support-priority-badge themisdb-support-priority-<?php echo esc_attr($ticket['priority']); ?>">
                                        <?php echo esc_html(isset($priority_labels[$ticket['priority']]) ? $priority_labels[$ticket['priority']] : $ticket['priority']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Ticket Detail Modal -->
            <div id="themisdb-support-ticket-modal" class="themisdb-support-modal" style="display:none;" role="dialog" aria-modal="true">
                <div class="themisdb-support-modal-backdrop"></div>
                <div class="themisdb-support-modal-content">
                    <button type="button" class="themisdb-support-modal-close" aria-label="<?php esc_attr_e('Schließen', 'themisdb-support-portal'); ?>">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                    <div id="themisdb-support-ticket-detail-content">
                        <div class="themisdb-support-loading">
                            <span class="dashicons dashicons-update themisdb-spin"></span>
                            <?php esc_html_e('Lädt…', 'themisdb-support-portal'); ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <?php
        return ob_get_clean();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Retrieve a minimal license info summary for display in the portal header.
     *
     * @param int $user_id
     * @return array|null
     */
    private function get_current_user_license_info($user_id) {
        if (class_exists('ThemisDB_License_Manager')) {
            $license_id = get_user_meta($user_id, 'themisdb_license_id', true);
            if ($license_id) {
                $license = ThemisDB_License_Manager::get_license(intval($license_id));
                if ($license) {
                    return array(
                        'edition' => $license['product_edition'],
                        'key'     => substr($license['license_key'], 0, 16) . '...',
                    );
                }
            }
        }

        $license_key = get_user_meta($user_id, 'themisdb_support_license_key', true);
        if ($license_key) {
            // Derive edition from key tier code (THEMIS-{TIER}-…)
            $parts   = explode('-', $license_key);
            $tier_map = array(
                'COM' => 'community',
                'ENT' => 'enterprise',
                'HYP' => 'hyperscaler',
                'RES' => 'reseller',
            );
            $edition = isset($parts[1], $tier_map[$parts[1]]) ? $tier_map[$parts[1]] : 'standard';

            return array(
                'edition' => $edition,
                'key'     => substr($license_key, 0, 16) . '...',
            );
        }

        return null;
    }

    /**
     * Retrieve support benefit summary for portal banner and limit hints.
     *
     * @param int $user_id
     * @return array|null
     */
    private function get_current_user_support_benefit_info($user_id) {
        if (!class_exists('ThemisDB_Support_Benefits_Manager')) {
            return null;
        }

        $license_id = get_user_meta($user_id, 'themisdb_license_id', true);
        if (!$license_id && class_exists('ThemisDB_License_Manager')) {
            $license_key = get_user_meta($user_id, 'themisdb_support_license_key', true);
            if ($license_key) {
                $license = ThemisDB_License_Manager::get_license_by_key($license_key);
                if (!empty($license['id'])) {
                    $license_id = intval($license['id']);
                }
            }
        }

        if (!$license_id) {
            return null;
        }

        $benefit = ThemisDB_Support_Benefits_Manager::get_by_license(intval($license_id));
        if (!$benefit) {
            return null;
        }

        $open_tickets_label = ($benefit['max_open_tickets'] == -1)
            ? __('Unbegrenzt', 'themisdb-support-portal')
            : sprintf(
                '%d / %d',
                intval($benefit['tickets_used_this_month']),
                intval($benefit['max_open_tickets'])
            );

        $expires_at_label = '';
        if (!empty($benefit['expires_at'])) {
            $expires_at_label = sprintf(
                __('Support aktiv bis: %s', 'themisdb-support-portal'),
                date_i18n(get_option('date_format'), strtotime($benefit['expires_at']))
            );
        }

        return array(
            'benefit_id' => intval($benefit['id']),
            'tier_label' => ucfirst(strval($benefit['tier_level'])),
            'status_label' => ucfirst(strval($benefit['benefit_status'])),
            'sla_label' => sprintf('%d h', intval($benefit['response_sla_hours'])),
            'open_tickets_label' => $open_tickets_label,
            'expires_at_label' => $expires_at_label,
        );
    }
}
