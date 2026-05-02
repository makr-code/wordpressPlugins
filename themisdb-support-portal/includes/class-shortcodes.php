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

        // AJAX handlers for contract lifecycle self-service
        add_action('wp_ajax_themisdb_lifecycle_request',     array($this, 'handle_lifecycle_request'));
        add_action('wp_ajax_themisdb_lifecycle_list',        array($this, 'handle_lifecycle_list'));

        // Standalone lifecycle portal shortcode
        add_shortcode('themisdb_lifecycle_portal', array($this, 'lifecycle_portal_shortcode'));

        // Unified customer cockpit (ARCHITECTUR.md §8.5)
        add_shortcode('themisdb_cockpit', array($this, 'cockpit_shortcode'));
    }

    /**
     * Build normalized shortcode data and pass it through the shared hook pipeline.
     */
    private function prepare_shortcode_context($shortcode_tag, $raw_atts, $default_atts, $payload = array()) {
        $atts = shortcode_atts($default_atts, (array) $raw_atts, $shortcode_tag);
        $atts = apply_filters($shortcode_tag . '_shortcode_atts', $atts, (array) $raw_atts);

        if (!is_array($payload)) {
            $payload = array();
        }

        return array($atts, $payload);
    }

    /**
     * Allow themes to fully override plugin HTML for a shortcode.
     */
    private function resolve_shortcode_html_override($shortcode_tag, $payload, $atts) {
        $html = apply_filters($shortcode_tag . '_shortcode_html', null, $payload, $atts);
        return (null !== $html) ? (string) $html : null;
    }

    /**
     * Final pass for post-processing plugin HTML.
     */
    private function finalize_shortcode_html($shortcode_tag, $html, $payload, $atts) {
        return apply_filters($shortcode_tag . '_shortcode_html_output', (string) $html, $payload, $atts);
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
        list($atts, $payload) = $this->prepare_shortcode_context('themisdb_support_portal', $atts, array());
        $payload = array_merge($payload, array(
            'has_license' => ThemisDB_Support_License_Auth::current_user_has_license(),
        ));

        if (!$payload['has_license']) {
            $payload['fallback'] = 'login_form';
            $payload = apply_filters('themisdb_support_portal_shortcode_payload', $payload, $atts);
            $override_html = $this->resolve_shortcode_html_override('themisdb_support_portal', $payload, $atts);
            if (null !== $override_html) {
                return $override_html;
            }

            return $this->finalize_shortcode_html('themisdb_support_portal', $this->render_login_form(), $payload, $atts);
        }

        $user = wp_get_current_user();
        $tickets = ThemisDB_SupportPortal_Ticket_Manager::get_user_tickets($user->ID);
        $license_info = $this->get_current_user_license_info($user->ID);
        $support_benefit_info = $this->get_current_user_support_benefit_info($user->ID);
        $payload = array_merge($payload, array(
            'user_id' => $user->ID,
            'tickets' => $tickets,
            'ticket_count' => is_array($tickets) ? count($tickets) : 0,
            'license_info' => $license_info,
            'support_benefit_info' => $support_benefit_info,
        ));
        $payload = apply_filters('themisdb_support_portal_shortcode_payload', $payload, $atts);
        $override_html = $this->resolve_shortcode_html_override('themisdb_support_portal', $payload, $atts);
        if (null !== $override_html) {
            return $override_html;
        }

        return $this->finalize_shortcode_html('themisdb_support_portal', $this->render_portal(), $payload, $atts);
    }

    /**
     * Standalone login-form shortcode (useful to embed on a custom login page).
     *
     * @return string
     */
    public function login_shortcode($atts) {
        list($atts, $payload) = $this->prepare_shortcode_context('themisdb_support_login', $atts, array());
        $payload = array_merge($payload, array(
            'has_license' => ThemisDB_Support_License_Auth::current_user_has_license(),
            'redirect' => get_option('themisdb_support_redirect_url', home_url('/')),
        ));

        if ($payload['has_license']) {
            $redirect = get_option('themisdb_support_redirect_url', home_url('/'));
            $payload['message'] = __('Sie sind bereits angemeldet.', 'themisdb-support-portal');
            $payload = apply_filters('themisdb_support_login_shortcode_payload', $payload, $atts);
            $override_html = $this->resolve_shortcode_html_override('themisdb_support_login', $payload, $atts);
            if (null !== $override_html) {
                return $override_html;
            }

            $html = '<p class="themisdb-support-notice themisdb-support-notice-info">'
                . esc_html__('Sie sind bereits angemeldet.', 'themisdb-support-portal')
                . ' <a href="' . esc_url($redirect) . '">'
                . esc_html__('Zum Support-Portal', 'themisdb-support-portal')
                . '</a></p>';
            return $this->finalize_shortcode_html('themisdb_support_login', $html, $payload, $atts);
        }

        $payload['fallback'] = 'login_form';
        $payload = apply_filters('themisdb_support_login_shortcode_payload', $payload, $atts);
        $override_html = $this->resolve_shortcode_html_override('themisdb_support_login', $payload, $atts);
        if (null !== $override_html) {
            return $override_html;
        }

        return $this->finalize_shortcode_html('themisdb_support_login', $this->render_login_form(), $payload, $atts);
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

            <?php if (class_exists('ThemisDB_Contract_Lifecycle')) : ?>
            <!-- Vertragsmanagement -->
            <div class="themisdb-support-section" id="themisdb-lifecycle-section">
                <div class="themisdb-support-section-header">
                    <h3><?php esc_html_e('Vertragsmanagement', 'themisdb-support-portal'); ?></h3>
                    <button type="button" id="themisdb-lifecycle-toggle" class="themisdb-support-btn themisdb-support-btn-secondary">
                        <span class="dashicons dashicons-editor-contract"></span>
                        <?php esc_html_e('Kuendigung / Aenderung beantragen', 'themisdb-support-portal'); ?>
                    </button>
                </div>

                <div id="themisdb-lifecycle-form-wrap" style="display:none;">
                    <div style="margin-bottom:12px;">
                        <label><strong><?php esc_html_e('Antragstyp', 'themisdb-support-portal'); ?></strong></label><br>
                        <label style="margin-right:16px;">
                            <input type="radio" name="themisdb_lifecycle_type" value="termination" id="lc-type-termination" checked />
                            <?php esc_html_e('Kuendigung', 'themisdb-support-portal'); ?>
                        </label>
                        <label>
                            <input type="radio" name="themisdb_lifecycle_type" value="change" id="lc-type-change" />
                            <?php esc_html_e('Aenderung', 'themisdb-support-portal'); ?>
                        </label>
                    </div>

                    <div id="themisdb-lc-termination-fields">
                        <div class="themisdb-support-form-group">
                            <label for="themisdb-lc-end-date"><?php esc_html_e('Gewuenschtes Kuendigungsdatum (optional)', 'themisdb-support-portal'); ?></label>
                            <input type="date" id="themisdb-lc-end-date" name="requested_end_date" class="themisdb-support-input" />
                        </div>
                    </div>

                    <div id="themisdb-lc-change-fields" style="display:none;">
                        <div class="themisdb-support-form-row">
                            <div class="themisdb-support-form-group">
                                <label for="themisdb-lc-edition"><?php esc_html_e('Neue Edition', 'themisdb-support-portal'); ?></label>
                                <select id="themisdb-lc-edition" name="product_edition" class="themisdb-support-input">
                                    <option value=""><?php esc_html_e('— keine Aenderung —', 'themisdb-support-portal'); ?></option>
                                    <option value="community">Community</option>
                                    <option value="enterprise">Enterprise</option>
                                    <option value="hyperscaler">Hyperscaler</option>
                                    <option value="reseller">Reseller</option>
                                </select>
                            </div>
                            <div class="themisdb-support-form-group">
                                <label for="themisdb-lc-expiry"><?php esc_html_e('Neues Ablaufdatum', 'themisdb-support-portal'); ?></label>
                                <input type="date" id="themisdb-lc-expiry" name="expiry_date" class="themisdb-support-input" />
                            </div>
                        </div>
                    </div>

                    <div class="themisdb-support-form-group">
                        <label for="themisdb-lc-reason"><?php esc_html_e('Begruendung', 'themisdb-support-portal'); ?> <span class="themisdb-required">*</span></label>
                        <textarea id="themisdb-lc-reason" name="reason" rows="4" required class="themisdb-support-input" style="width:100%;"
                            placeholder="<?php esc_attr_e('Bitte beschreiben Sie Ihr Anliegen…', 'themisdb-support-portal'); ?>"></textarea>
                    </div>

                    <div class="themisdb-support-form-actions">
                        <button type="button" id="themisdb-lc-submit" class="themisdb-support-btn themisdb-support-btn-primary">
                            <?php esc_html_e('Antrag einreichen', 'themisdb-support-portal'); ?>
                        </button>
                        <button type="button" id="themisdb-lc-cancel" class="themisdb-support-btn themisdb-support-btn-secondary">
                            <?php esc_html_e('Abbrechen', 'themisdb-support-portal'); ?>
                        </button>
                    </div>
                    <div id="themisdb-lc-messages" class="themisdb-support-messages"></div>
                </div>

                <div id="themisdb-lifecycle-requests-wrap" style="margin-top:16px;">
                    <div class="themisdb-support-loading" id="themisdb-lc-loading">
                        <span class="dashicons dashicons-update themisdb-spin"></span>
                        <?php esc_html_e('Lade Lifecycle-Antraege…', 'themisdb-support-portal'); ?>
                    </div>
                    <table class="themisdb-support-lc-table" id="themisdb-lc-table" style="display:none;width:100%;border-collapse:collapse;font-size:13px;">
                        <thead>
                            <tr>
                                <th style="text-align:left;padding:6px 10px;border-bottom:1px solid #ddd;"><?php esc_html_e('ID', 'themisdb-support-portal'); ?></th>
                                <th style="text-align:left;padding:6px 10px;border-bottom:1px solid #ddd;"><?php esc_html_e('Typ', 'themisdb-support-portal'); ?></th>
                                <th style="text-align:left;padding:6px 10px;border-bottom:1px solid #ddd;"><?php esc_html_e('Status', 'themisdb-support-portal'); ?></th>
                                <th style="text-align:left;padding:6px 10px;border-bottom:1px solid #ddd;"><?php esc_html_e('Effektiv ab', 'themisdb-support-portal'); ?></th>
                                <th style="text-align:left;padding:6px 10px;border-bottom:1px solid #ddd;"><?php esc_html_e('Erstellt', 'themisdb-support-portal'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="themisdb-lc-tbody"></tbody>
                    </table>
                    <p id="themisdb-lc-empty" style="display:none;color:#666;"><?php esc_html_e('Keine Lifecycle-Antraege vorhanden.', 'themisdb-support-portal'); ?></p>
                </div>
            </div>

            <script>
            (function() {
                var lcNonce = <?php echo wp_json_encode(wp_create_nonce('themisdb_lifecycle_nonce')); ?>;
                var ajaxUrl = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;

                function lcMsg(msg, isError) {
                    var el = document.getElementById('themisdb-lc-messages');
                    if (!el) return;
                    el.innerHTML = '<div class="themisdb-support-notice themisdb-support-notice-' + (isError ? 'error' : 'success') + '">' + msg + '</div>';
                }

                function loadRequests() {
                    var tbody = document.getElementById('themisdb-lc-tbody');
                    var table = document.getElementById('themisdb-lc-table');
                    var empty = document.getElementById('themisdb-lc-empty');
                    var loading = document.getElementById('themisdb-lc-loading');
                    var fd = new FormData();
                    fd.append('action', 'themisdb_lifecycle_list');
                    fd.append('nonce', lcNonce);
                    fetch(ajaxUrl, {method:'POST', body:fd, credentials:'same-origin'})
                        .then(function(r){return r.json();})
                        .then(function(data) {
                            if (loading) loading.style.display = 'none';
                            if (!data.success) { if (empty) { empty.style.display=''; empty.textContent = data.data && data.data.message ? data.data.message : 'Fehler'; } return; }
                            var rows = data.data && data.data.requests ? data.data.requests : [];
                            if (!rows.length) { if (empty) empty.style.display=''; return; }
                            if (table) table.style.display='';
                            var statusColors = {requested:'#0073aa',confirmed:'#28a745',rejected:'#dc3545',executed:'#6c757d'};
                            if (tbody) {
                                tbody.innerHTML = rows.map(function(r) {
                                    var sc = statusColors[r.status] || '#333';
                                    var logHtml = '';
                                    if (r.log && r.log.length) {
                                        logHtml = r.log.map(function(e) {
                                            return '<li style="display:flex;gap:8px;padding:4px 0;border-bottom:1px solid #eee;font-size:11px;">'
                                                +'<span style="color:#aaa;min-width:130px;">'+e.created_at+'</span>'
                                                +'<strong style="min-width:90px;">'+e.event+'</strong>'
                                                +'<span style="color:#555;min-width:100px;">'+e.actor_name+'</span>'
                                                +'<span>'+e.note+'</span></li>';
                                        }).join('');
                                    } else {
                                        logHtml = '<li style="color:#aaa;font-size:11px;">Keine Eintraege</li>';
                                    }
                                    
                                    // Build visual timeline
                                    var stageStates = {
                                        'requested': {label:'Angefordert', order:1},
                                        'ops': {label:'Ops Review', order:2},
                                        'finance': {label:'Finance Review', order:3},
                                        'executed': {label:'Ausgefuehrt', order:4},
                                        'rejected': {label:'Abgelehnt', order:5}
                                    };
                                    
                                    var hasOpsReview = r.log && r.log.some(function(e) { return e.event.indexOf('ops_')===0; });
                                    var hasFinanceReview = r.log && r.log.some(function(e) { return e.event.indexOf('finance_')===0; });
                                    var isRejected = r.status === 'rejected';
                                    var isExecuted = r.status === 'executed';
                                    
                                    var timelineHtml = '<div style="display:flex;gap:6px;align-items:center;margin:8px 0;font-size:11px;overflow-x:auto;">'
                                        +'<span style="background:#0073aa;color:#fff;padding:3px 6px;border-radius:3px;white-space:nowrap;">Angefordert</span>';
                                    if (hasOpsReview) timelineHtml += '<span style="flex-grow:1;height:2px;background:#28a745;"></span><span style="background:#28a745;color:#fff;padding:3px 6px;border-radius:3px;white-space:nowrap;">✓ Ops</span>';
                                    if (hasFinanceReview) timelineHtml += '<span style="flex-grow:1;height:2px;background:#28a745;"></span><span style="background:#28a745;color:#fff;padding:3px 6px;border-radius:3px;white-space:nowrap;">✓ Finance</span>';
                                    if (isRejected) timelineHtml += '<span style="flex-grow:1;height:2px;background:#dc3545;"></span><span style="background:#dc3545;color:#fff;padding:3px 6px;border-radius:3px;white-space:nowrap;">✗ Abgelehnt</span>';
                                    if (isExecuted) timelineHtml += '<span style="flex-grow:1;height:2px;background:#6c757d;"></span><span style="background:#6c757d;color:#fff;padding:3px 6px;border-radius:3px;white-space:nowrap;">✓ Ausgeführt</span>';
                                    timelineHtml += '</div>';
                                    
                                    return '<tr style="cursor:pointer;" onclick="var d=document.getElementById(\'lclog'+r.id+'\');if(d)d.style.display=d.style.display===\'none\'?\'\':\'none\';">'
                                        +'<td style="padding:5px 10px;"><strong>#'+r.id+'</strong></td>'
                                        +'<td style="padding:5px 10px;">'+r.request_type+'</td>'
                                        +'<td style="padding:5px 10px;"><span style="display:inline-block;padding:1px 8px;border-radius:3px;background:'+sc+';color:#fff;font-size:11px;">'+r.status+'</span></td>'
                                        +'<td style="padding:5px 10px;">'+(r.effective_at||'—')+'</td>'
                                        +'<td style="padding:5px 10px;">'+(r.created_at||'—')+'</td>'
                                        +'</tr>'
                                        +'<tr id="lclog'+r.id+'" style="display:none;"><td colspan="5" style="background:#f9f9f9;padding:12px 20px;">'
                                        +'<div style="margin-bottom:12px;">'+timelineHtml+'</div>'
                                        +'<ul style="margin:0;padding:0;list-style:none;">'+logHtml+'</ul></td></tr>';
                                }).join('');
                            }
                        })
                        .catch(function() { if (loading) loading.style.display='none'; });
                var formWrap  = document.getElementById('themisdb-lifecycle-form-wrap');
                if (toggleBtn && formWrap) {
                    toggleBtn.addEventListener('click', function() {
                        formWrap.style.display = formWrap.style.display === 'none' ? '' : 'none';
                    });
                }

                // Cancel
                var cancelBtn = document.getElementById('themisdb-lc-cancel');
                if (cancelBtn && formWrap) {
                    cancelBtn.addEventListener('click', function() { formWrap.style.display='none'; });
                }

                // Toggle termination/change fields
                var typeRadios = document.querySelectorAll('[name="themisdb_lifecycle_type"]');
                var termFields = document.getElementById('themisdb-lc-termination-fields');
                var changeFields = document.getElementById('themisdb-lc-change-fields');
                Array.prototype.forEach.call(typeRadios, function(radio) {
                    radio.addEventListener('change', function() {
                        if (this.value === 'termination') {
                            if (termFields) termFields.style.display='';
                            if (changeFields) changeFields.style.display='none';
                        } else {
                            if (termFields) termFields.style.display='none';
                            if (changeFields) changeFields.style.display='';
                        }
                    });
                });

                // Submit
                var submitBtn = document.getElementById('themisdb-lc-submit');
                if (submitBtn) {
                    submitBtn.addEventListener('click', function() {
                        var typeEl = document.querySelector('[name="themisdb_lifecycle_type"]:checked');
                        var type   = typeEl ? typeEl.value : 'termination';
                        var reason = (document.getElementById('themisdb-lc-reason')||{}).value||'';
                        if (!reason.trim()) { lcMsg('Bitte geben Sie eine Begruendung an.', true); return; }

                        var fd = new FormData();
                        fd.append('action', 'themisdb_lifecycle_request');
                        fd.append('nonce', lcNonce);
                        fd.append('request_type', type);
                        fd.append('reason', reason);

                        if (type === 'termination') {
                            var endDate = (document.getElementById('themisdb-lc-end-date')||{}).value||'';
                            if (endDate) fd.append('requested_end_date', endDate);
                        } else {
                            var edition = (document.getElementById('themisdb-lc-edition')||{}).value||'';
                            var expiry  = (document.getElementById('themisdb-lc-expiry')||{}).value||'';
                            if (edition) fd.append('product_edition', edition);
                            if (expiry)  fd.append('expiry_date', expiry);
                        }

                        submitBtn.disabled = true;
                        fetch(ajaxUrl, {method:'POST', body:fd, credentials:'same-origin'})
                            .then(function(r){return r.json();})
                            .then(function(data) {
                                submitBtn.disabled = false;
                                if (data.success) {
                                    lcMsg(data.data.message, false);
                                    if (formWrap) formWrap.style.display='none';
                                    loadRequests();
                                } else {
                                    lcMsg(data.data && data.data.message ? data.data.message : 'Fehler', true);
                                }
                            })
                            .catch(function() { submitBtn.disabled=false; lcMsg('Netzwerkfehler', true); });
                    });
                }

                loadRequests();
            })();
            </script>
            <?php endif; ?>

        </div>
        <?php
        return ob_get_clean();
    }

    // -------------------------------------------------------------------------
    // Lifecycle AJAX Handlers
    // -------------------------------------------------------------------------

    /**
     * Standalone lifecycle portal shortcode [themisdb_lifecycle_portal].
     * Shows only the contract management section (termination / change requests).
     * Can be placed on any page independently of the support portal.
     *
     * @param array $atts Shortcode attributes (unused, reserved).
     * @return string HTML output
     */
    public function lifecycle_portal_shortcode($atts) {
        if (!ThemisDB_Support_License_Auth::current_user_has_license()) {
            return '<div class="themisdb-support-login-wrap">' . $this->render_login_form() . '</div>';
        }

        if (!class_exists('ThemisDB_Contract_Lifecycle')) {
            return '<p class="themisdb-support-notice">'
                . esc_html__('Das Vertragsmanagement ist derzeit nicht verfuegbar.', 'themisdb-support-portal')
                . '</p>';
        }

        ob_start();
        ?>
        <div class="themisdb-support-portal-wrap">
            <?php if (class_exists('ThemisDB_Contract_Lifecycle')) : ?>
            <div class="themisdb-support-section" id="themisdb-lifecycle-section">
                <div class="themisdb-support-section-header">
                    <h3><?php esc_html_e('Vertragsmanagement', 'themisdb-support-portal'); ?></h3>
                    <button type="button" id="themisdb-lifecycle-toggle" class="themisdb-support-btn themisdb-support-btn-secondary">
                        <span class="dashicons dashicons-editor-contract"></span>
                        <?php esc_html_e('Kuendigung / Aenderung beantragen', 'themisdb-support-portal'); ?>
                    </button>
                </div>

                <div id="themisdb-lifecycle-form-wrap" style="display:none;">
                    <div style="margin-bottom:12px;">
                        <label><strong><?php esc_html_e('Antragstyp', 'themisdb-support-portal'); ?></strong></label><br>
                        <label style="margin-right:16px;">
                            <input type="radio" name="themisdb_lifecycle_type" value="termination" checked />
                            <?php esc_html_e('Kuendigung', 'themisdb-support-portal'); ?>
                        </label>
                        <label>
                            <input type="radio" name="themisdb_lifecycle_type" value="change" />
                            <?php esc_html_e('Aenderung', 'themisdb-support-portal'); ?>
                        </label>
                    </div>

                    <div id="themisdb-lc-termination-fields">
                        <div class="themisdb-support-form-group">
                            <label for="themisdb-lc-end-date"><?php esc_html_e('Gewuenschtes Kuendigungsdatum (optional)', 'themisdb-support-portal'); ?></label>
                            <input type="date" id="themisdb-lc-end-date" name="requested_end_date" class="themisdb-support-input" />
                        </div>
                    </div>

                    <div id="themisdb-lc-change-fields" style="display:none;">
                        <div class="themisdb-support-form-row">
                            <div class="themisdb-support-form-group">
                                <label for="themisdb-lc-edition"><?php esc_html_e('Neue Edition', 'themisdb-support-portal'); ?></label>
                                <select id="themisdb-lc-edition" name="product_edition" class="themisdb-support-input">
                                    <option value=""><?php esc_html_e('— keine Aenderung —', 'themisdb-support-portal'); ?></option>
                                    <option value="community">Community</option>
                                    <option value="enterprise">Enterprise</option>
                                    <option value="hyperscaler">Hyperscaler</option>
                                    <option value="reseller">Reseller</option>
                                </select>
                            </div>
                            <div class="themisdb-support-form-group">
                                <label for="themisdb-lc-expiry"><?php esc_html_e('Neues Ablaufdatum', 'themisdb-support-portal'); ?></label>
                                <input type="date" id="themisdb-lc-expiry" name="expiry_date" class="themisdb-support-input" />
                            </div>
                        </div>
                    </div>

                    <div class="themisdb-support-form-group">
                        <label for="themisdb-lc-reason"><?php esc_html_e('Begruendung', 'themisdb-support-portal'); ?> <span class="themisdb-required">*</span></label>
                        <textarea id="themisdb-lc-reason" rows="4" required class="themisdb-support-input" style="width:100%;"
                            placeholder="<?php esc_attr_e('Bitte beschreiben Sie Ihr Anliegen…', 'themisdb-support-portal'); ?>"></textarea>
                    </div>

                    <div class="themisdb-support-form-actions">
                        <button type="button" id="themisdb-lc-submit" class="themisdb-support-btn themisdb-support-btn-primary">
                            <?php esc_html_e('Antrag einreichen', 'themisdb-support-portal'); ?>
                        </button>
                        <button type="button" id="themisdb-lc-cancel" class="themisdb-support-btn themisdb-support-btn-secondary">
                            <?php esc_html_e('Abbrechen', 'themisdb-support-portal'); ?>
                        </button>
                    </div>
                    <div id="themisdb-lc-messages" class="themisdb-support-messages"></div>
                </div>

                <div id="themisdb-lifecycle-requests-wrap" style="margin-top:16px;">
                    <div class="themisdb-support-loading" id="themisdb-lc-loading">
                        <span class="dashicons dashicons-update themisdb-spin"></span>
                        <?php esc_html_e('Lade Lifecycle-Antraege…', 'themisdb-support-portal'); ?>
                    </div>
                    <table class="themisdb-support-lc-table" id="themisdb-lc-table" style="display:none;width:100%;border-collapse:collapse;font-size:13px;">
                        <thead>
                            <tr>
                                <th style="text-align:left;padding:6px 10px;border-bottom:1px solid #ddd;">ID</th>
                                <th style="text-align:left;padding:6px 10px;border-bottom:1px solid #ddd;"><?php esc_html_e('Typ', 'themisdb-support-portal'); ?></th>
                                <th style="text-align:left;padding:6px 10px;border-bottom:1px solid #ddd;"><?php esc_html_e('Status', 'themisdb-support-portal'); ?></th>
                                <th style="text-align:left;padding:6px 10px;border-bottom:1px solid #ddd;"><?php esc_html_e('Effektiv ab', 'themisdb-support-portal'); ?></th>
                                <th style="text-align:left;padding:6px 10px;border-bottom:1px solid #ddd;"><?php esc_html_e('Erstellt', 'themisdb-support-portal'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="themisdb-lc-tbody"></tbody>
                    </table>
                    <p id="themisdb-lc-empty" style="display:none;color:#666;"><?php esc_html_e('Keine Lifecycle-Antraege vorhanden.', 'themisdb-support-portal'); ?></p>
                </div>
            </div>

            <script>
            (function() {
                var lcNonce = <?php echo wp_json_encode(wp_create_nonce('themisdb_lifecycle_nonce')); ?>;
                var ajaxUrl = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;

                function lcMsg(msg, isError) {
                    var el = document.getElementById('themisdb-lc-messages');
                    if (!el) return;
                    el.innerHTML = '<div class="themisdb-support-notice themisdb-support-notice-' + (isError ? 'error' : 'success') + '">' + msg + '</div>';
                }

                function loadRequests() {
                    var tbody = document.getElementById('themisdb-lc-tbody');
                    var table = document.getElementById('themisdb-lc-table');
                    var empty = document.getElementById('themisdb-lc-empty');
                    var loading = document.getElementById('themisdb-lc-loading');
                    var fd = new FormData();
                    fd.append('action', 'themisdb_lifecycle_list');
                    fd.append('nonce', lcNonce);
                    fetch(ajaxUrl, {method:'POST', body:fd, credentials:'same-origin'})
                        .then(function(r){return r.json();})
                        .then(function(data) {
                            if (loading) loading.style.display = 'none';
                            if (!data.success) { if (empty) { empty.style.display=''; } return; }
                            var rows = data.data && data.data.requests ? data.data.requests : [];
                            if (!rows.length) { if (empty) empty.style.display=''; return; }
                            if (table) table.style.display='';
                            var statusColors = {requested:'#0073aa',confirmed:'#28a745',rejected:'#dc3545',executed:'#6c757d'};
                            if (tbody) {
                                tbody.innerHTML = rows.map(function(r) {
                                    var sc = statusColors[r.status] || '#333';
                                    var logHtml = r.log && r.log.length
                                        ? r.log.map(function(e) {
                                            return '<li style="display:flex;gap:8px;padding:4px 0;border-bottom:1px solid #eee;font-size:11px;">'
                                                +'<span style="color:#aaa;min-width:130px;">'+e.created_at+'</span>'
                                                +'<strong style="min-width:90px;">'+e.event+'</strong>'
                                                +'<span style="color:#555;min-width:100px;">'+e.actor_name+'</span>'
                                                +'<span>'+e.note+'</span></li>';
                                        }).join('')
                                        : '<li style="color:#aaa;font-size:11px;">Keine Eintraege</li>';
                                    
                                    // Build visual timeline
                                    var hasOpsReview = r.log && r.log.some(function(e) { return e.event.indexOf('ops_')===0; });
                                    var hasFinanceReview = r.log && r.log.some(function(e) { return e.event.indexOf('finance_')===0; });
                                    var isRejected = r.status === 'rejected';
                                    var isExecuted = r.status === 'executed';
                                    
                                    var timelineHtml = '<div style="display:flex;gap:6px;align-items:center;margin:8px 0;font-size:11px;overflow-x:auto;">'
                                        +'<span style="background:#0073aa;color:#fff;padding:3px 6px;border-radius:3px;white-space:nowrap;">Angefordert</span>';
                                    if (hasOpsReview) timelineHtml += '<span style="flex-grow:1;height:2px;background:#28a745;"></span><span style="background:#28a745;color:#fff;padding:3px 6px;border-radius:3px;white-space:nowrap;">✓ Ops</span>';
                                    if (hasFinanceReview) timelineHtml += '<span style="flex-grow:1;height:2px;background:#28a745;"></span><span style="background:#28a745;color:#fff;padding:3px 6px;border-radius:3px;white-space:nowrap;">✓ Finance</span>';
                                    if (isRejected) timelineHtml += '<span style="flex-grow:1;height:2px;background:#dc3545;"></span><span style="background:#dc3545;color:#fff;padding:3px 6px;border-radius:3px;white-space:nowrap;">✗ Abgelehnt</span>';
                                    if (isExecuted) timelineHtml += '<span style="flex-grow:1;height:2px;background:#6c757d;"></span><span style="background:#6c757d;color:#fff;padding:3px 6px;border-radius:3px;white-space:nowrap;">✓ Ausgeführt</span>';
                                    timelineHtml += '</div>';
                                    
                                    return '<tr style="cursor:pointer;" onclick="var d=document.getElementById(\'lclog'+r.id+'\');if(d)d.style.display=d.style.display===\'none\'?\'\':\' none\';">'
                                        +'<td style="padding:5px 10px;"><strong>#'+r.id+'</strong></td>'
                                        +'<td style="padding:5px 10px;">'+r.request_type+'</td>'
                                        +'<td style="padding:5px 10px;"><span style="display:inline-block;padding:1px 8px;border-radius:3px;background:'+sc+';color:#fff;font-size:11px;">'+r.status+'</span></td>'
                                        +'<td style="padding:5px 10px;">'+(r.effective_at||'—')+'</td>'
                                        +'<td style="padding:5px 10px;">'+(r.created_at||'—')+'</td>'
                                        +'</tr>'
                                        +'<tr id="lclog'+r.id+'" style="display:none;"><td colspan="5" style="background:#f9f9f9;padding:12px 20px;">'
                                        +'<div style="margin-bottom:12px;">'+timelineHtml+'</div>'
                                        +'<ul style="margin:0;padding:0;list-style:none;">'+logHtml+'</ul></td></tr>';
                                }).join('');
                            }
                        })
                        .catch(function() { if (loading) loading.style.display='none'; });
                }

                var toggleBtn = document.getElementById('themisdb-lifecycle-toggle');
                var formWrap  = document.getElementById('themisdb-lifecycle-form-wrap');
                if (toggleBtn && formWrap) {
                    toggleBtn.addEventListener('click', function() {
                        formWrap.style.display = formWrap.style.display === 'none' ? '' : 'none';
                    });
                }
                var cancelBtn = document.getElementById('themisdb-lc-cancel');
                if (cancelBtn && formWrap) {
                    cancelBtn.addEventListener('click', function() { formWrap.style.display='none'; });
                }
                var typeRadios = document.querySelectorAll('[name="themisdb_lifecycle_type"]');
                var termFields = document.getElementById('themisdb-lc-termination-fields');
                var changeFields = document.getElementById('themisdb-lc-change-fields');
                Array.prototype.forEach.call(typeRadios, function(radio) {
                    radio.addEventListener('change', function() {
                        if (this.value === 'termination') {
                            if (termFields) termFields.style.display='';
                            if (changeFields) changeFields.style.display='none';
                        } else {
                            if (termFields) termFields.style.display='none';
                            if (changeFields) changeFields.style.display='';
                        }
                    });
                });
                var submitBtn = document.getElementById('themisdb-lc-submit');
                if (submitBtn) {
                    submitBtn.addEventListener('click', function() {
                        var typeEl = document.querySelector('[name="themisdb_lifecycle_type"]:checked');
                        var type   = typeEl ? typeEl.value : 'termination';
                        var reason = (document.getElementById('themisdb-lc-reason')||{}).value||'';
                        if (!reason.trim()) { lcMsg('Bitte geben Sie eine Begruendung an.', true); return; }
                        var fd = new FormData();
                        fd.append('action', 'themisdb_lifecycle_request');
                        fd.append('nonce', lcNonce);
                        fd.append('request_type', type);
                        fd.append('reason', reason);
                        if (type === 'termination') {
                            var endDate = (document.getElementById('themisdb-lc-end-date')||{}).value||'';
                            if (endDate) fd.append('requested_end_date', endDate);
                        } else {
                            var edition = (document.getElementById('themisdb-lc-edition')||{}).value||'';
                            var expiry  = (document.getElementById('themisdb-lc-expiry')||{}).value||'';
                            if (edition) fd.append('product_edition', edition);
                            if (expiry)  fd.append('expiry_date', expiry);
                        }
                        submitBtn.disabled = true;
                        fetch(ajaxUrl, {method:'POST', body:fd, credentials:'same-origin'})
                            .then(function(r){return r.json();})
                            .then(function(data) {
                                submitBtn.disabled = false;
                                if (data.success) {
                                    lcMsg(data.data.message, false);
                                    if (formWrap) formWrap.style.display='none';
                                    loadRequests();
                                } else {
                                    lcMsg(data.data && data.data.message ? data.data.message : 'Fehler', true);
                                }
                            })
                            .catch(function() { submitBtn.disabled=false; lcMsg('Netzwerkfehler', true); });
                    });
                }
                loadRequests();
            })();
            </script>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Handle lifecycle request submission (termination or change) from portal.     */
    public function handle_lifecycle_request() {
        check_ajax_referer('themisdb_lifecycle_nonce', 'nonce');

        if (!ThemisDB_Support_License_Auth::current_user_has_license()) {
            wp_send_json_error(array('message' => __('Zugriff verweigert. Bitte melden Sie sich mit Ihrer Lizenzdatei an.', 'themisdb-support-portal')));
        }

        if (!class_exists('ThemisDB_Contract_Lifecycle')) {
            wp_send_json_error(array('message' => __('Das Lifecycle-Modul ist derzeit nicht verfuegbar.', 'themisdb-support-portal')));
        }

        $type   = isset($_POST['request_type']) ? sanitize_key((string) wp_unslash($_POST['request_type'])) : '';
        $reason = isset($_POST['reason'])       ? sanitize_textarea_field((string) wp_unslash($_POST['reason'])) : '';
        $user   = wp_get_current_user();

        // Resolve license ID from order plugin
        $license_id = 0;
        if (class_exists('ThemisDB_License_Manager')) {
            $lid = get_user_meta($user->ID, 'themisdb_license_id', true);
            if ($lid) {
                $license_id = intval($lid);
            } else {
                $license_key = get_user_meta($user->ID, 'themisdb_support_license_key', true);
                if ($license_key) {
                    $lic = ThemisDB_License_Manager::get_license_by_key($license_key);
                    if (!empty($lic['id'])) {
                        $license_id = intval($lic['id']);
                    }
                }
            }
        }

        if ($license_id <= 0) {
            wp_send_json_error(array('message' => __('Keine zugehoerige Lizenz gefunden.', 'themisdb-support-portal')));
        }

        if ($type === 'termination') {
            $end_date = isset($_POST['requested_end_date']) ? sanitize_text_field((string) wp_unslash($_POST['requested_end_date'])) : '';
            $result   = ThemisDB_Contract_Lifecycle::request_termination($license_id, $end_date, $reason, $user->ID);
        } elseif ($type === 'change') {
            $payload  = array();
            $allowed  = array('product_edition', 'license_type', 'max_nodes', 'max_cores', 'max_storage_gb', 'expiry_date');
            foreach ($allowed as $field) {
                if (!isset($_POST[$field]) || '' === $_POST[$field]) {
                    continue;
                }
                $raw = wp_unslash($_POST[$field]);
                if (in_array($field, array('max_nodes', 'max_cores', 'max_storage_gb'), true)) {
                    $payload[$field] = intval($raw);
                } else {
                    $payload[$field] = sanitize_text_field((string) $raw);
                }
            }
            if (empty($payload)) {
                wp_send_json_error(array('message' => __('Bitte mindestens ein Feld fuer den Aenderungsantrag angeben.', 'themisdb-support-portal')));
            }
            $result = ThemisDB_Contract_Lifecycle::request_change($license_id, $payload, $reason, $user->ID);
        } else {
            wp_send_json_error(array('message' => __('Unbekannter Antragstyp.', 'themisdb-support-portal')));
        }

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array(
            'message'    => $type === 'termination'
                ? __('Ihr Kuendigungsantrag wurde erfolgreich eingereicht. Ein Administrator wird ihn pruefen.', 'themisdb-support-portal')
                : __('Ihr Aenderungsantrag wurde erfolgreich eingereicht. Ein Administrator wird ihn pruefen.', 'themisdb-support-portal'),
            'request_id' => $result,
        ));
    }

    /**
     * Return the current user's lifecycle requests as JSON.
     */
    public function handle_lifecycle_list() {
        check_ajax_referer('themisdb_lifecycle_nonce', 'nonce');

        if (!ThemisDB_Support_License_Auth::current_user_has_license()) {
            wp_send_json_error(array('message' => __('Zugriff verweigert.', 'themisdb-support-portal')));
        }

        if (!class_exists('ThemisDB_Contract_Lifecycle')) {
            wp_send_json_success(array('requests' => array()));
        }

        $user       = wp_get_current_user();
        $license_id = 0;
        if (class_exists('ThemisDB_License_Manager')) {
            $lid = get_user_meta($user->ID, 'themisdb_license_id', true);
            if ($lid) {
                $license_id = intval($lid);
            } else {
                $lk = get_user_meta($user->ID, 'themisdb_support_license_key', true);
                if ($lk) {
                    $lic = ThemisDB_License_Manager::get_license_by_key($lk);
                    if (!empty($lic['id'])) {
                        $license_id = intval($lic['id']);
                    }
                }
            }
        }

        if ($license_id <= 0) {
            wp_send_json_success(array('requests' => array()));
        }

        $requests = ThemisDB_Contract_Lifecycle::list_requests(array(
            'license_id' => $license_id,
            'limit'      => 50,
        ));

        // Enrich with audit log per request
        $enriched = array();
        if (is_array($requests)) {
            foreach ($requests as $req) {
                $log = ThemisDB_Contract_Lifecycle::get_log(intval($req['id']));
                $req['log'] = array_map(function($e) {
                    return array(
                        'event'      => $e['event'],
                        'actor_name' => $e['actor_name'] ?: 'System',
                        'note'       => $e['note'],
                        'created_at' => $e['created_at'],
                    );
                }, $log ?: array());
                $enriched[] = $req;
            }
        }

        wp_send_json_success(array('requests' => $enriched));
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

    // -------------------------------------------------------------------------
    // Unified Customer Cockpit (ARCHITECTUR.md §8.5)
    // -------------------------------------------------------------------------

    /**
     * [themisdb_cockpit] – Kunden-Cockpit mit aggregiertem Status.
     *
     * Zeigt Lizenzstatus, offene Tickets, Bestellungen, Build-History
     * und offene Lifecycle-Anträge in einer konsolidierten Ansicht.
     *
     * @param array $atts  Shortcode-Attribute (derzeit nicht genutzt).
     * @return string       HTML-Output.
     */
    public function cockpit_shortcode($atts) {
        if (!ThemisDB_Support_License_Auth::current_user_has_license()) {
            return '<div class="themisdb-support-login-wrap">' . $this->render_login_form() . '</div>';
        }

        if (!class_exists('ThemisDB_Status_Resolver')) {
            return '<p class="themisdb-support-notice">'
                . esc_html__('Status Resolver nicht verfügbar.', 'themisdb-support-portal')
                . '</p>';
        }

        $user    = wp_get_current_user();
        $summary = ThemisDB_Status_Resolver::for_user($user->ID);

        $license   = $summary['license'];
        $tickets   = $summary['tickets'];
        $orders    = $summary['orders'];
        $builds    = $summary['builds'];
        $lifecycle = $summary['lifecycle'];
        $health    = $summary['health'];

        ob_start();
        ?>
        <div class="themisdb-cockpit-wrap" style="font-family:inherit;max-width:900px;">

            <!-- Health-Banner -->
            <?php
            $hcolor = ThemisDB_Status_Resolver::health_color($health['level']);
            $hicon  = ThemisDB_Status_Resolver::health_icon($health['level']);
            ?>
            <div style="display:flex;align-items:flex-start;gap:12px;padding:14px 18px;background:<?php echo esc_attr($hcolor); ?>18;border-left:4px solid <?php echo esc_attr($hcolor); ?>;border-radius:4px;margin-bottom:20px;">
                <span class="dashicons <?php echo esc_attr($hicon); ?>" style="color:<?php echo esc_attr($hcolor); ?>;font-size:24px;flex-shrink:0;margin-top:1px;"></span>
                <div>
                    <strong style="color:<?php echo esc_attr($hcolor); ?>;font-size:14px;">
                        <?php
                        if ($health['level'] === 'ok') {
                            esc_html_e('Alles in Ordnung', 'themisdb-support-portal');
                        } elseif ($health['level'] === 'warn') {
                            esc_html_e('Hinweise vorhanden', 'themisdb-support-portal');
                        } else {
                            esc_html_e('Handlungsbedarf', 'themisdb-support-portal');
                        }
                        ?>
                    </strong>
                    <?php if (!empty($health['reasons'])): ?>
                        <ul style="margin:6px 0 0;padding-left:18px;font-size:13px;color:#333;">
                            <?php foreach ($health['reasons'] as $reason): ?>
                                <li><?php echo esc_html($reason); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

                <!-- Lizenz-Karte -->
                <div style="background:#fff;border:1px solid #ddd;border-radius:6px;padding:16px 18px;">
                    <h3 style="margin-top:0;font-size:14px;color:#333;display:flex;align-items:center;gap:8px;">
                        <span class="dashicons dashicons-admin-network" style="color:#4f7df3;"></span>
                        <?php esc_html_e('Lizenz', 'themisdb-support-portal'); ?>
                    </h3>
                    <?php if ($license['available']): ?>
                        <table style="width:100%;font-size:13px;border-collapse:collapse;">
                            <tr>
                                <td style="padding:4px 0;color:#666;width:50%;"><?php esc_html_e('Schlüssel', 'themisdb-support-portal'); ?></td>
                                <td style="padding:4px 0;font-family:monospace;"><?php echo esc_html($license['license_key']); ?></td>
                            </tr>
                            <tr>
                                <td style="padding:4px 0;color:#666;"><?php esc_html_e('Tier', 'themisdb-support-portal'); ?></td>
                                <td style="padding:4px 0;"><?php echo esc_html(ucfirst($license['tier'])); ?></td>
                            </tr>
                            <tr>
                                <td style="padding:4px 0;color:#666;"><?php esc_html_e('Status', 'themisdb-support-portal'); ?></td>
                                <td style="padding:4px 0;">
                                    <?php
                                    $ls_label = ThemisDB_Status_Resolver::license_status_label($license['status']);
                                    $ls_color = in_array($license['status'], array('expired', 'revoked', 'suspended'), true) ? '#e74c3c' : '#27ae60';
                                    ?>
                                    <span style="color:<?php echo esc_attr($ls_color); ?>;font-weight:600;"><?php echo esc_html($ls_label); ?></span>
                                </td>
                            </tr>
                            <?php if ($license['expires_at']): ?>
                            <tr>
                                <td style="padding:4px 0;color:#666;"><?php esc_html_e('Läuft ab', 'themisdb-support-portal'); ?></td>
                                <td style="padding:4px 0;"><?php echo esc_html(mysql2date('d.m.Y', $license['expires_at'])); ?></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    <?php else: ?>
                        <p style="color:#888;font-size:13px;"><?php esc_html_e('Keine Lizenzdaten verfügbar.', 'themisdb-support-portal'); ?></p>
                    <?php endif; ?>
                </div>

                <!-- Offene Lifecycle-Anträge -->
                <div style="background:#fff;border:1px solid #ddd;border-radius:6px;padding:16px 18px;">
                    <h3 style="margin-top:0;font-size:14px;color:#333;display:flex;align-items:center;gap:8px;">
                        <span class="dashicons dashicons-editor-contract" style="color:#9b59b6;"></span>
                        <?php esc_html_e('Offene Anträge', 'themisdb-support-portal'); ?>
                    </h3>
                    <?php if (empty($lifecycle)): ?>
                        <p style="color:#888;font-size:13px;"><?php esc_html_e('Keine offenen Vertragsanträge.', 'themisdb-support-portal'); ?></p>
                    <?php else: ?>
                        <ul style="margin:0;padding-left:16px;font-size:13px;">
                            <?php foreach ($lifecycle as $req): ?>
                                <li style="margin-bottom:4px;">
                                    <strong><?php echo esc_html(ucfirst(isset($req['type']) ? $req['type'] : '')); ?></strong>
                                    — <?php echo esc_html(isset($req['status']) ? $req['status'] : ''); ?>
                                    <?php if (!empty($req['created_at'])): ?>
                                        <span style="color:#aaa;font-size:11px;"><?php echo esc_html(mysql2date('d.m.Y', $req['created_at'])); ?></span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

            </div><!-- end 2-col grid -->

            <!-- Tickets -->
            <div style="background:#fff;border:1px solid #ddd;border-radius:6px;padding:16px 18px;margin-top:20px;">
                <h3 style="margin-top:0;font-size:14px;color:#333;display:flex;align-items:center;gap:8px;">
                    <span class="dashicons dashicons-tickets-alt" style="color:#3498db;"></span>
                    <?php esc_html_e('Aktuelle Tickets', 'themisdb-support-portal'); ?>
                </h3>
                <?php if (empty($tickets)): ?>
                    <p style="color:#888;font-size:13px;"><?php esc_html_e('Keine offenen Tickets.', 'themisdb-support-portal'); ?></p>
                <?php else: ?>
                    <table style="width:100%;border-collapse:collapse;font-size:13px;">
                        <thead>
                            <tr style="border-bottom:1px solid #eee;">
                                <th style="text-align:left;padding:6px 8px;color:#666;font-weight:600;"><?php esc_html_e('Nr.', 'themisdb-support-portal'); ?></th>
                                <th style="text-align:left;padding:6px 8px;color:#666;font-weight:600;"><?php esc_html_e('Betreff', 'themisdb-support-portal'); ?></th>
                                <th style="text-align:left;padding:6px 8px;color:#666;font-weight:600;"><?php esc_html_e('Status', 'themisdb-support-portal'); ?></th>
                                <th style="text-align:left;padding:6px 8px;color:#666;font-weight:600;"><?php esc_html_e('Priorität', 'themisdb-support-portal'); ?></th>
                                <th style="text-align:left;padding:6px 8px;color:#666;font-weight:600;"><?php esc_html_e('Aktualisiert', 'themisdb-support-portal'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $ticket):
                                $tstatus = isset($ticket['status']) ? $ticket['status'] : '';
                                $tcolor  = ThemisDB_Status_Resolver::ticket_status_color($tstatus);
                                $tlabel  = ThemisDB_Status_Resolver::ticket_status_label($tstatus);
                            ?>
                                <tr style="border-bottom:1px solid #f5f5f5;">
                                    <td style="padding:6px 8px;font-family:monospace;"><?php echo esc_html(isset($ticket['ticket_number']) ? $ticket['ticket_number'] : '—'); ?></td>
                                    <td style="padding:6px 8px;"><?php echo esc_html(isset($ticket['subject']) ? mb_strimwidth($ticket['subject'], 0, 60, '…') : ''); ?></td>
                                    <td style="padding:6px 8px;">
                                        <span style="display:inline-block;padding:1px 7px;border-radius:3px;background:<?php echo esc_attr($tcolor); ?>;color:#fff;font-size:11px;"><?php echo esc_html($tlabel); ?></span>
                                    </td>
                                    <td style="padding:6px 8px;"><?php echo esc_html(ucfirst(isset($ticket['priority']) ? $ticket['priority'] : '')); ?></td>
                                    <td style="padding:6px 8px;color:#aaa;font-size:11px;"><?php echo esc_html(isset($ticket['updated_at']) ? mysql2date('d.m.Y', $ticket['updated_at']) : ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Bestellungen + Builds nebeneinander -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px;">

                <!-- Bestellungen -->
                <div style="background:#fff;border:1px solid #ddd;border-radius:6px;padding:16px 18px;">
                    <h3 style="margin-top:0;font-size:14px;color:#333;display:flex;align-items:center;gap:8px;">
                        <span class="dashicons dashicons-cart" style="color:#27ae60;"></span>
                        <?php esc_html_e('Bestellungen', 'themisdb-support-portal'); ?>
                    </h3>
                    <?php if (empty($orders)): ?>
                        <p style="color:#888;font-size:13px;"><?php esc_html_e('Keine Bestellungen gefunden.', 'themisdb-support-portal'); ?></p>
                    <?php else: ?>
                        <ul style="margin:0;padding-left:0;list-style:none;font-size:13px;">
                            <?php foreach ($orders as $order): ?>
                                <li style="border-bottom:1px solid #f5f5f5;padding:6px 0;">
                                    <strong><?php echo esc_html(isset($order['order_number']) ? $order['order_number'] : '#' . (isset($order['id']) ? $order['id'] : '')); ?></strong>
                                    — <?php echo esc_html(isset($order['status']) ? ucfirst($order['status']) : ''); ?>
                                    <?php if (!empty($order['created_at'])): ?>
                                        <span style="color:#aaa;font-size:11px;display:block;"><?php echo esc_html(mysql2date('d.m.Y', $order['created_at'])); ?></span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <!-- Build-History -->
                <div style="background:#fff;border:1px solid #ddd;border-radius:6px;padding:16px 18px;">
                    <h3 style="margin-top:0;font-size:14px;color:#333;display:flex;align-items:center;gap:8px;">
                        <span class="dashicons dashicons-hammer" style="color:#e67e22;"></span>
                        <?php esc_html_e('Build-History', 'themisdb-support-portal'); ?>
                    </h3>
                    <?php if (empty($builds)): ?>
                        <p style="color:#888;font-size:13px;"><?php esc_html_e('Keine Build-Einträge gefunden.', 'themisdb-support-portal'); ?></p>
                    <?php else: ?>
                        <ul style="margin:0;padding-left:0;list-style:none;font-size:13px;">
                            <?php foreach ($builds as $build):
                                $bstatus = isset($build['status']) ? $build['status'] : '';
                                $bcolor  = ThemisDB_Status_Resolver::build_status_color($bstatus);
                                $blabel  = ThemisDB_Status_Resolver::build_status_label($bstatus);
                            ?>
                                <li style="border-bottom:1px solid #f5f5f5;padding:6px 0;">
                                    <span style="display:inline-block;padding:1px 7px;border-radius:3px;background:<?php echo esc_attr($bcolor); ?>;color:#fff;font-size:11px;"><?php echo esc_html($blabel); ?></span>
                                    <?php if (!empty($build['artifact_url'])): ?>
                                        <a href="<?php echo esc_url($build['artifact_url']); ?>" style="font-size:11px;margin-left:6px;" target="_blank" rel="noopener"><?php esc_html_e('Download', 'themisdb-support-portal'); ?></a>
                                    <?php endif; ?>
                                    <?php if (!empty($build['created_at'])): ?>
                                        <span style="color:#aaa;font-size:11px;display:block;"><?php echo esc_html(mysql2date('d.m.Y H:i', $build['created_at'])); ?></span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

            </div><!-- end 2-col grid -->

        </div><!-- .themisdb-cockpit-wrap -->
        <?php

        return ob_get_clean();
    }
}
