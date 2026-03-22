<?php

if (!defined('ABSPATH')) {
    exit;
}

class ThemisDB_GitHub_Bridge {

    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function activate() {
        global $wpdb;

        $table = $wpdb->prefix . 'themisdb_github_links';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            source_plugin varchar(64) NOT NULL,
            source_type varchar(64) NOT NULL,
            source_id bigint(20) unsigned NOT NULL,
            repository varchar(255) NOT NULL,
            issue_number int(11) NOT NULL,
            issue_url varchar(512) NOT NULL,
            issue_state varchar(32) NOT NULL DEFAULT 'open',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_source (source_plugin, source_type, source_id),
            KEY issue_number (issue_number),
            KEY source_plugin (source_plugin)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        add_option('themisdb_github_bridge_enabled', '0');
        add_option('themisdb_github_bridge_repository', '');
        add_option('themisdb_github_bridge_token', '');
        add_option('themisdb_github_bridge_sync_order_tickets', '1');
        add_option('themisdb_github_bridge_sync_support_tickets', '1');
        add_option('themisdb_github_bridge_order_labels', 'order,support');
        add_option('themisdb_github_bridge_support_labels', 'support,portal');
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));

        add_action('themisdb_order_support_ticket_created', array($this, 'handle_order_ticket_created'), 10, 2);
        add_action('themisdb_support_portal_ticket_created', array($this, 'handle_support_portal_ticket_created'), 10, 3);
    }

    public function add_admin_menu() {
        add_options_page(
            __('ThemisDB GitHub Bridge', 'themisdb-github-bridge'),
            __('ThemisDB GitHub Bridge', 'themisdb-github-bridge'),
            'manage_options',
            'themisdb-github-bridge',
            array($this, 'render_settings_page')
        );
    }

    public function register_settings() {
        register_setting('themisdb_github_bridge_settings', 'themisdb_github_bridge_enabled');
        register_setting('themisdb_github_bridge_settings', 'themisdb_github_bridge_repository');
        register_setting('themisdb_github_bridge_settings', 'themisdb_github_bridge_token');
        register_setting('themisdb_github_bridge_settings', 'themisdb_github_bridge_sync_order_tickets');
        register_setting('themisdb_github_bridge_settings', 'themisdb_github_bridge_sync_support_tickets');
        register_setting('themisdb_github_bridge_settings', 'themisdb_github_bridge_order_labels');
        register_setting('themisdb_github_bridge_settings', 'themisdb_github_bridge_support_labels');
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $links = $this->get_recent_links();
        $page_slug = 'themisdb-github-bridge';
        $active_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'settings';
        $allowed_tabs = array('settings', 'links');

        if (!in_array($active_tab, $allowed_tabs, true)) {
            $active_tab = 'settings';
        }

        $tab_url = static function ($tab) use ($page_slug) {
            return admin_url('options-general.php?page=' . $page_slug . '&tab=' . $tab);
        };
        ?>
        <div class="wrap">
            <style>
                .themisdb-tab-content { background: #fff; border: 1px solid #c3c4c7; border-top: none; padding: 20px 24px; }
                .themisdb-tab-content > :first-child,
                .themisdb-tab-content .themisdb-admin-modules:first-child,
                .themisdb-tab-content .card:first-child,
                .themisdb-tab-content form:first-child { margin-top: 0; }
                .themisdb-admin-modules { display: grid; gap: 20px; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); margin: 0 0 24px; }
                .themisdb-admin-modules .card, .themisdb-tab-content .card { margin: 0; max-width: none; padding: 20px 24px; }
                .themisdb-tab-toolbar { display: flex; gap: 8px; flex-wrap: wrap; margin: 0 0 16px; }
            </style>

            <h1 class="wp-heading-inline"><?php esc_html_e('ThemisDB GitHub Bridge', 'themisdb-github-bridge'); ?></h1>
            <a href="<?php echo esc_url($tab_url('settings')); ?>" class="page-title-action"><?php esc_html_e('Einstellungen bearbeiten', 'themisdb-github-bridge'); ?></a>
            <a href="<?php echo esc_url($tab_url('links')); ?>" class="page-title-action"><?php esc_html_e('Sync-Verknüpfungen', 'themisdb-github-bridge'); ?></a>
            <hr class="wp-header-end">

            <nav class="nav-tab-wrapper wp-clearfix" aria-label="<?php esc_attr_e('GitHub Bridge Einstellungen', 'themisdb-github-bridge'); ?>">
                <a href="<?php echo esc_url($tab_url('settings')); ?>" class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Einstellungen', 'themisdb-github-bridge'); ?></a>
                <a href="<?php echo esc_url($tab_url('links')); ?>" class="nav-tab <?php echo $active_tab === 'links' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Verknüpfungen', 'themisdb-github-bridge'); ?></a>
            </nav>

            <div class="themisdb-tab-content">
            <?php if ($active_tab === 'settings') : ?>
            <div class="themisdb-admin-modules">
                <div class="card">
                    <h2><?php esc_html_e('Schnellaktionen', 'themisdb-github-bridge'); ?></h2>
                    <div class="themisdb-tab-toolbar">
                        <a href="#themisdb-github-bridge-settings-form" class="button button-primary"><?php esc_html_e('Zur Konfiguration', 'themisdb-github-bridge'); ?></a>
                        <a href="<?php echo esc_url($tab_url('links')); ?>" class="button"><?php esc_html_e('Letzte Verknüpfungen', 'themisdb-github-bridge'); ?></a>
                    </div>
                    <p><?php esc_html_e('Automatisiert GitHub-Issue-Erstellung fuer Order- und Support-Tickets.', 'themisdb-github-bridge'); ?></p>
                </div>
                <div class="card">
                    <h2><?php esc_html_e('Status', 'themisdb-github-bridge'); ?></h2>
                    <table class="widefat striped"><tbody>
                        <tr><th><?php esc_html_e('Bridge aktiv', 'themisdb-github-bridge'); ?></th><td><?php echo esc_html(get_option('themisdb_github_bridge_enabled', '0') === '1' ? 'Aktiv' : 'Inaktiv'); ?></td></tr>
                        <tr><th><?php esc_html_e('Repository', 'themisdb-github-bridge'); ?></th><td><?php echo esc_html((string) get_option('themisdb_github_bridge_repository', '')); ?></td></tr>
                        <tr><th><?php esc_html_e('Links', 'themisdb-github-bridge'); ?></th><td><?php echo esc_html((string) count($links)); ?></td></tr>
                    </tbody></table>
                </div>
            </div>

            <form id="themisdb-github-bridge-settings-form" method="post" action="options.php">
                <?php settings_fields('themisdb_github_bridge_settings'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Bridge aktiv', 'themisdb-github-bridge'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="themisdb_github_bridge_enabled" value="1" <?php checked(get_option('themisdb_github_bridge_enabled', '0'), '1'); ?>>
                                <?php esc_html_e('GitHub-Sync aktivieren', 'themisdb-github-bridge'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Repository', 'themisdb-github-bridge'); ?></th>
                        <td>
                            <input type="text" class="regular-text" name="themisdb_github_bridge_repository" value="<?php echo esc_attr((string) get_option('themisdb_github_bridge_repository', '')); ?>" placeholder="owner/repo">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('GitHub Token', 'themisdb-github-bridge'); ?></th>
                        <td>
                            <input type="password" class="regular-text" name="themisdb_github_bridge_token" value="<?php echo esc_attr((string) get_option('themisdb_github_bridge_token', '')); ?>" autocomplete="new-password">
                            <p class="description"><?php esc_html_e('Empfohlen: Fine-grained Token mit Issues Read/Write.', 'themisdb-github-bridge'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Order Tickets syncen', 'themisdb-github-bridge'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="themisdb_github_bridge_sync_order_tickets" value="1" <?php checked(get_option('themisdb_github_bridge_sync_order_tickets', '1'), '1'); ?>>
                                <?php esc_html_e('Neue Order-Plugin-Tickets als Issues erstellen', 'themisdb-github-bridge'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Support-Portal-Tickets syncen', 'themisdb-github-bridge'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="themisdb_github_bridge_sync_support_tickets" value="1" <?php checked(get_option('themisdb_github_bridge_sync_support_tickets', '1'), '1'); ?>>
                                <?php esc_html_e('Neue Support-Portal-Tickets als Issues erstellen', 'themisdb-github-bridge'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Order Labels', 'themisdb-github-bridge'); ?></th>
                        <td>
                            <input type="text" class="regular-text" name="themisdb_github_bridge_order_labels" value="<?php echo esc_attr((string) get_option('themisdb_github_bridge_order_labels', 'order,support')); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Support Labels', 'themisdb-github-bridge'); ?></th>
                        <td>
                            <input type="text" class="regular-text" name="themisdb_github_bridge_support_labels" value="<?php echo esc_attr((string) get_option('themisdb_github_bridge_support_labels', 'support,portal')); ?>">
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
            <?php else : ?>
            <div class="card">
            <h2><?php esc_html_e('Letzte Sync-Verknuepfungen', 'themisdb-github-bridge'); ?></h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Quelle', 'themisdb-github-bridge'); ?></th>
                        <th><?php esc_html_e('Datensatz-ID', 'themisdb-github-bridge'); ?></th>
                        <th><?php esc_html_e('Repository', 'themisdb-github-bridge'); ?></th>
                        <th><?php esc_html_e('Issue', 'themisdb-github-bridge'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($links)) : ?>
                        <tr><td colspan="4"><?php esc_html_e('Noch keine Verknuepfungen vorhanden.', 'themisdb-github-bridge'); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ($links as $link) : ?>
                            <tr>
                                <td><?php echo esc_html($link['source_plugin'] . ' / ' . $link['source_type']); ?></td>
                                <td><?php echo esc_html((string) $link['source_id']); ?></td>
                                <td><?php echo esc_html($link['repository']); ?></td>
                                <td>
                                    <a href="<?php echo esc_url($link['issue_url']); ?>" target="_blank" rel="noopener noreferrer">
                                        #<?php echo esc_html((string) $link['issue_number']); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
            </div>
        </div>
        <?php
    }

    public function handle_order_ticket_created($ticket_id, $payload = array()) {
        if (get_option('themisdb_github_bridge_sync_order_tickets', '1') !== '1') {
            return;
        }

        if (!class_exists('ThemisDB_Order_Support_Ticket_Manager')) {
            return;
        }

        $ticket = ThemisDB_Order_Support_Ticket_Manager::get_ticket($ticket_id);
        if (!$ticket) {
            return;
        }

        if (!empty($ticket['github_issue_number'])) {
            return;
        }

        $title = sprintf('[Order Support #%d] %s', intval($ticket_id), (string) ($ticket['subject'] ?? 'Support Ticket'));
        $body = $this->build_order_ticket_body($ticket);
        $labels = $this->parse_labels(get_option('themisdb_github_bridge_order_labels', 'order,support'));

        $this->create_issue_for_source('themisdb-order-request', 'support_ticket', $ticket_id, $title, $body, $labels, $ticket);
    }

    public function handle_support_portal_ticket_created($ticket_id, $ticket_data = array(), $raw_payload = array()) {
        if (get_option('themisdb_github_bridge_sync_support_tickets', '1') !== '1') {
            return;
        }

        if (!class_exists('ThemisDB_SupportPortal_Ticket_Manager')) {
            return;
        }

        $ticket = ThemisDB_SupportPortal_Ticket_Manager::get_ticket($ticket_id);
        if (!$ticket) {
            $ticket = is_array($ticket_data) ? $ticket_data : array();
            $ticket['id'] = $ticket_id;
        }

        $title = sprintf('[Support Portal #%d] %s', intval($ticket_id), (string) ($ticket['subject'] ?? 'Support Ticket'));
        $body = $this->build_support_ticket_body($ticket, $raw_payload);
        $labels = $this->parse_labels(get_option('themisdb_github_bridge_support_labels', 'support,portal'));

        $this->create_issue_for_source('themisdb-support-portal', 'support_ticket', $ticket_id, $title, $body, $labels, $ticket);
    }

    private function create_issue_for_source($source_plugin, $source_type, $source_id, $title, $body, $labels, $ticket = array()) {
        if (get_option('themisdb_github_bridge_enabled', '0') !== '1') {
            return;
        }

        if ($this->has_link($source_plugin, $source_type, $source_id)) {
            return;
        }

        $repository = (string) get_option('themisdb_github_bridge_repository', '');
        $token = (string) get_option('themisdb_github_bridge_token', '');

        $result = ThemisDB_GitHub_Client::create_issue($repository, $token, $title, $body, $labels);
        if (is_wp_error($result)) {
            error_log('ThemisDB GitHub Bridge: issue creation failed for ' . $source_plugin . ' #' . intval($source_id) . ': ' . $result->get_error_message());
            return;
        }

        $this->store_link(
            $source_plugin,
            $source_type,
            intval($source_id),
            (string) $result['repository'],
            intval($result['issue_number']),
            (string) $result['issue_url'],
            (string) $result['issue_state']
        );

        do_action('themisdb_github_bridge_issue_created', $source_plugin, $source_type, intval($source_id), $result, $ticket);
    }

    private function has_link($source_plugin, $source_type, $source_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'themisdb_github_links';
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE source_plugin = %s AND source_type = %s AND source_id = %d",
            $source_plugin,
            $source_type,
            intval($source_id)
        ));

        return intval($count) > 0;
    }

    private function store_link($source_plugin, $source_type, $source_id, $repository, $issue_number, $issue_url, $issue_state) {
        global $wpdb;

        $table = $wpdb->prefix . 'themisdb_github_links';

        $wpdb->replace(
            $table,
            array(
                'source_plugin' => sanitize_text_field($source_plugin),
                'source_type' => sanitize_text_field($source_type),
                'source_id' => intval($source_id),
                'repository' => sanitize_text_field($repository),
                'issue_number' => intval($issue_number),
                'issue_url' => esc_url_raw($issue_url),
                'issue_state' => sanitize_key($issue_state),
            ),
            array('%s', '%s', '%d', '%s', '%d', '%s', '%s')
        );
    }

    private function get_recent_links() {
        global $wpdb;

        $table = $wpdb->prefix . 'themisdb_github_links';
        if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
            return array();
        }

        return $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC LIMIT 20", ARRAY_A);
    }

    private function parse_labels($csv) {
        return array_values(array_filter(array_map('sanitize_key', array_map('trim', explode(',', (string) $csv)))));
    }

    private function build_order_ticket_body($ticket) {
        $lines = array();
        $lines[] = '## Quelle';
        $lines[] = '- Plugin: themisdb-order-request';
        $lines[] = '- Ticket-ID: #' . intval($ticket['id'] ?? 0);
        $lines[] = '- Prioritaet: ' . sanitize_text_field((string) ($ticket['priority'] ?? 'normal'));
        $lines[] = '- Status: ' . sanitize_text_field((string) ($ticket['status'] ?? 'open'));
        if (!empty($ticket['customer_email'])) {
            $lines[] = '- E-Mail: ' . sanitize_email((string) $ticket['customer_email']);
        }
        if (!empty($ticket['license_id'])) {
            $lines[] = '- License-ID: ' . intval($ticket['license_id']);
        }
        if (!empty($ticket['order_id'])) {
            $lines[] = '- Order-ID: ' . intval($ticket['order_id']);
        }
        $lines[] = '';
        $lines[] = '## Beschreibung';
        $lines[] = trim(wp_strip_all_tags((string) ($ticket['description'] ?? '')));

        return implode("\n", $lines);
    }

    private function build_support_ticket_body($ticket, $raw_payload = array()) {
        $lines = array();
        $lines[] = '## Quelle';
        $lines[] = '- Plugin: themisdb-support-portal';
        $lines[] = '- Ticket-ID: #' . intval($ticket['id'] ?? 0);
        $lines[] = '- Prioritaet: ' . sanitize_text_field((string) ($ticket['priority'] ?? 'normal'));
        $lines[] = '- Status: ' . sanitize_text_field((string) ($ticket['status'] ?? 'open'));
        if (!empty($ticket['customer_email'])) {
            $lines[] = '- E-Mail: ' . sanitize_email((string) $ticket['customer_email']);
        }
        if (!empty($ticket['license_key'])) {
            $lines[] = '- License-Key: ' . sanitize_text_field((string) $ticket['license_key']);
        }
        $lines[] = '';
        $lines[] = '## Beschreibung';

        $body_message = '';

        if (!empty($raw_payload['message'])) {
            $body_message = (string) $raw_payload['message'];
        } elseif (class_exists('ThemisDB_SupportPortal_Ticket_Manager') && !empty($ticket['id'])) {
            $messages = ThemisDB_SupportPortal_Ticket_Manager::get_messages(intval($ticket['id']));
            if (!empty($messages) && !empty($messages[0]['message'])) {
                $body_message = (string) $messages[0]['message'];
            }
        }

        if ($body_message === '') {
            $body_message = (string) ($ticket['subject'] ?? '');
        }

        $lines[] = trim(wp_strip_all_tags($body_message));

        return implode("\n", $lines);
    }
}
