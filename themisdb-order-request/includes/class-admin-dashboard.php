<?php
/**
 * ThemisDB Admin Dashboard – Monitoring & Metrics (Phase 4.1)
 *
 * Provides WordPress admin dashboard with operational KPIs:
 * - Order pipeline status widget
 * - Revenue charts & trends
 * - Support tier metrics
 * - License lifecycle overview
 * - System health status
 *
 * @version 1.0.0
 * @since 2026-03-21
 */

if (!defined('ABSPATH')) {
    exit;
}

class ThemisDB_Admin_Dashboard {

    /**
     * Register hooks and initialize dashboard.
     */
    public static function init() {
        add_action('wp_dashboard_setup', array(__CLASS__, 'register_widgets'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_styles'));
        add_action('wp_ajax_themisdb_dashboard_refresh', array(__CLASS__, 'ajax_refresh_metrics'));
    }

    /**
     * Enqueue dashboard-specific styles.
     *
     * @param string $hook
     */
    public static function enqueue_styles($hook) {
        if ('index.php' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'themisdb-dashboard',
            THEMISDB_ORDER_PLUGIN_URL . 'assets/css/admin-dashboard.css',
            array(),
            THEMISDB_ORDER_VERSION
        );

        wp_enqueue_script(
            'themisdb-dashboard-js',
            THEMISDB_ORDER_PLUGIN_URL . 'assets/js/admin-dashboard.js',
            array('jquery'),
            THEMISDB_ORDER_VERSION,
            true
        );

        wp_localize_script('themisdb-dashboard-js', 'themisdbDashboard', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('themisdb_dashboard_nonce'),
        ));
    }

    /**
     * Register dashboard widgets.
     */
    public static function register_widgets() {
        wp_add_dashboard_widget(
            'themisdb_order_pipeline',
            __('ThemisDB Bestellungs-Pipeline', 'themisdb-order-request'),
            array(__CLASS__, 'render_order_pipeline_widget')
        );

        wp_add_dashboard_widget(
            'themisdb_revenue_chart',
            __('ThemisDB Umsatz-Übersicht', 'themisdb-order-request'),
            array(__CLASS__, 'render_revenue_chart_widget')
        );

        wp_add_dashboard_widget(
            'themisdb_support_metrics',
            __('ThemisDB Support-Metriken', 'themisdb-order-request'),
            array(__CLASS__, 'render_support_metrics_widget')
        );

        wp_add_dashboard_widget(
            'themisdb_license_metrics',
            __('ThemisDB Lizenz-Übersicht', 'themisdb-order-request'),
            array(__CLASS__, 'render_license_metrics_widget')
        );

        wp_add_dashboard_widget(
            'themisdb_health_status',
            __('ThemisDB System-Status', 'themisdb-order-request'),
            array(__CLASS__, 'render_health_status_widget')
        );
    }

    // ──────────────────────────────────────────────────────────────────
    // WIDGET: Order Pipeline
    // ──────────────────────────────────────────────────────────────────

    /**
     * Render order pipeline status widget.
     */
    public static function render_order_pipeline_widget() {
        global $wpdb;

        if (!class_exists('ThemisDB_Order_Manager')) {
            echo '<p>' . esc_html__('Order Manager nicht verfügbar.', 'themisdb-order-request') . '</p>';
            return;
        }

        $table = $wpdb->prefix . 'themisdb_orders';
        $statuses = array('draft', 'pending', 'processing', 'confirmed', 'cancelled', 'failed', 'ended');

        $pipeline = array();
        foreach ($statuses as $status) {
            $count = intval($wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM $table WHERE status = %s", $status)
            ));
            $pipeline[$status] = $count;
        }

        $total = array_sum($pipeline);
        ?>
        <div class="themisdb-dashboard-widget">
            <div class="tdb-pipeline-grid">
                <?php foreach ($pipeline as $status => $count) : ?>
                    <div class="tdb-pipeline-box tdb-pipeline-<?php echo esc_attr($status); ?>">
                        <div class="tdb-pipeline-count"><?php echo intval($count); ?></div>
                        <div class="tdb-pipeline-label">
                            <?php echo esc_html(ucfirst(str_replace('_', ' ', $status))); ?>
                        </div>
                        <?php if ($total > 0) : ?>
                            <div class="tdb-pipeline-percent">
                                <?php echo esc_html(number_format(($count / $total) * 100, 0)); ?>%
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="tdb-summary-row">
                <strong><?php esc_html_e('Gesamt Bestellungen:', 'themisdb-order-request'); ?></strong>
                <span><?php echo intval($total); ?></span>
            </div>

            <div class="tdb-summary-row">
                <strong><?php esc_html_e('Letzte 7 Tage:', 'themisdb-order-request'); ?></strong>
                <span id="themisdb-orders-7d"><?php echo intval(self::get_orders_last_n_days(7)); ?></span>
            </div>

            <div class="tdb-actions">
                <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-orders')); ?>" class="button">
                    <?php esc_html_e('Alle Bestellungen ansehen', 'themisdb-order-request'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    // ──────────────────────────────────────────────────────────────────
    // WIDGET: Revenue Chart
    // ──────────────────────────────────────────────────────────────────

    /**
     * Render revenue chart widget.
     */
    public static function render_revenue_chart_widget() {
        global $wpdb;

        if (!class_exists('ThemisDB_Payment_Manager')) {
            echo '<p>' . esc_html__('Payment Manager nicht verfügbar.', 'themisdb-order-request') . '</p>';
            return;
        }

        $table = $wpdb->prefix . 'themisdb_payments';
        $revenue_data = self::get_monthly_revenue(12);

        $total_revenue = 0;
        $total_payments = 0;
        foreach ($revenue_data as $month) {
            $total_revenue += floatval($month['amount']);
            $total_payments += intval($month['count']);
        }

        $avg_transaction = $total_payments > 0 ? ($total_revenue / $total_payments) : 0;
        ?>
        <div class="themisdb-dashboard-widget">
            <div class="tdb-revenue-stats">
                <div class="tdb-stat-box">
                    <div class="tdb-stat-label"><?php esc_html_e('Gesamt Umsatz (12M)', 'themisdb-order-request'); ?></div>
                    <div class="tdb-stat-value">
                        <?php echo esc_html(number_format($total_revenue, 2, ',', '.')); ?>&nbsp;€
                    </div>
                </div>

                <div class="tdb-stat-box">
                    <div class="tdb-stat-label"><?php esc_html_e('Durchschn. Transaktion', 'themisdb-order-request'); ?></div>
                    <div class="tdb-stat-value">
                        <?php echo esc_html(number_format($avg_transaction, 2, ',', '.')); ?>&nbsp;€
                    </div>
                </div>

                <div class="tdb-stat-box">
                    <div class="tdb-stat-label"><?php esc_html_e('Zahlungen (12M)', 'themisdb-order-request'); ?></div>
                    <div class="tdb-stat-value"><?php echo intval($total_payments); ?></div>
                </div>
            </div>

            <div class="tdb-monthly-bar-chart">
                <div class="tdb-chart-title"><?php esc_html_e('Monatlicher Umsatz (letzter Monat zuerst)', 'themisdb-order-request'); ?></div>
                <div class="tdb-bars">
                    <?php
                    $max_amount = max(array_column($revenue_data, 'amount'));
                    $max_height = max(1, $max_amount);

                    foreach (array_reverse($revenue_data) as $month) :
                        $pct = ($max_height > 0) ? (floatval($month['amount']) / $max_height) * 100 : 0;
                        $month_label = date_i18n('M Y', strtotime($month['month'] . '-01'));
                    ?>
                        <div class="tdb-bar-group">
                            <div class="tdb-bar" style="height: <?php echo esc_attr($pct); ?>%;"
                                 title="<?php esc_attr_e($month['amount']); ?>"></div>
                            <div class="tdb-bar-label"><?php echo esc_html($month_label); ?></div>
                            <div class="tdb-bar-value">
                                <?php echo esc_html(number_format(floatval($month['amount']), 0, ',', '.')); ?>&nbsp;€
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="tdb-actions">
                <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-payments')); ?>" class="button">
                    <?php esc_html_e('Zahlungen verwalten', 'themisdb-order-request'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    // ──────────────────────────────────────────────────────────────────
    // WIDGET: Support Metrics
    // ──────────────────────────────────────────────────────────────────

    /**
     * Render support metrics widget.
     */
    public static function render_support_metrics_widget() {
        global $wpdb;

        if (!class_exists('ThemisDB_Support_Benefits_Manager')) {
            echo '<p>' . esc_html__('Support Manager nicht verfügbar.', 'themisdb-order-request') . '</p>';
            return;
        }

        $table = $wpdb->prefix . 'themisdb_support_benefits';
        $tier_stats = array();

        $tiers = array('community', 'enterprise', 'hyperscaler', 'reseller');
        foreach ($tiers as $tier) {
            $count = intval($wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM $table WHERE tier_level = %s AND benefit_status = 'active'", $tier)
            ));
            $tier_stats[$tier] = $count;
        }

        $total_active = array_sum($tier_stats);
        $pending_count = intval($wpdb->get_var("SELECT COUNT(*) FROM $table WHERE benefit_status = 'pending'"));
        $suspended_count = intval($wpdb->get_var("SELECT COUNT(*) FROM $table WHERE benefit_status = 'suspended'"));

        // Ticket stats
        $tickets_table = $wpdb->prefix . 'themisdb_support_tickets';
        $open_tickets = intval($wpdb->get_var("SELECT COUNT(*) FROM $tickets_table WHERE status = 'open'"));
        $in_progress_tickets = intval($wpdb->get_var("SELECT COUNT(*) FROM $tickets_table WHERE status = 'in_progress'"));
        $avg_response_time = intval($wpdb->get_var(
            "SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) FROM $tickets_table WHERE status IN ('in_progress', 'resolved')"
        ));
        ?>
        <div class="themisdb-dashboard-widget">
            <div class="tdb-support-section">
                <h3><?php esc_html_e('Support-Tiers (aktiv)', 'themisdb-order-request'); ?></h3>
                <div class="tdb-tier-grid">
                    <?php foreach ($tier_stats as $tier => $count) : ?>
                        <div class="tdb-tier-box tdb-tier-<?php echo esc_attr($tier); ?>">
                            <div class="tdb-tier-name"><?php echo esc_html(ucfirst($tier)); ?></div>
                            <div class="tdb-tier-count"><?php echo intval($count); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="tdb-support-section">
                <h3><?php esc_html_e('Ticket-Status', 'themisdb-order-request'); ?></h3>
                <div class="tdb-summary-row">
                    <span><?php esc_html_e('Offene Tickets:', 'themisdb-order-request'); ?></span>
                    <strong><?php echo intval($open_tickets); ?></strong>
                </div>
                <div class="tdb-summary-row">
                    <span><?php esc_html_e('In Bearbeitung:', 'themisdb-order-request'); ?></span>
                    <strong><?php echo intval($in_progress_tickets); ?></strong>
                </div>
                <div class="tdb-summary-row">
                    <span><?php esc_html_e('Ø Antwortzeit:', 'themisdb-order-request'); ?></span>
                    <strong><?php echo intval($avg_response_time); ?>h</strong>
                </div>
            </div>

            <div class="tdb-support-section">
                <h3><?php esc_html_e('Status Overview', 'themisdb-order-request'); ?></h3>
                <div class="tdb-summary-row">
                    <span><?php esc_html_e('Ausstehend:', 'themisdb-order-request'); ?></span>
                    <span class="tdb-status-pending"><?php echo intval($pending_count); ?></span>
                </div>
                <div class="tdb-summary-row">
                    <span><?php esc_html_e('Gesperrt:', 'themisdb-order-request'); ?></span>
                    <span class="tdb-status-suspended"><?php echo intval($suspended_count); ?></span>
                </div>
                <div class="tdb-summary-row">
                    <span><?php esc_html_e('Aktiv insgesamt:', 'themisdb-order-request'); ?></span>
                    <strong><?php echo intval($total_active); ?></strong>
                </div>
            </div>

            <div class="tdb-actions">
                <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-support')); ?>" class="button">
                    <?php esc_html_e('Support-Portal', 'themisdb-order-request'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    // ──────────────────────────────────────────────────────────────────
    // WIDGET: License Metrics
    // ──────────────────────────────────────────────────────────────────

    /**
     * Render license metrics widget.
     */
    public static function render_license_metrics_widget() {
        global $wpdb;

        if (!class_exists('ThemisDB_License_Manager')) {
            echo '<p>' . esc_html__('License Manager nicht verfügbar.', 'themisdb-order-request') . '</p>';
            return;
        }

        $table = $wpdb->prefix . 'themisdb_licenses';

        $active = intval($wpdb->get_var("SELECT COUNT(*) FROM $table WHERE license_status = 'active'"));
        $expired = intval($wpdb->get_var("SELECT COUNT(*) FROM $table WHERE license_status = 'expired'"));
        $suspended = intval($wpdb->get_var("SELECT COUNT(*) FROM $table WHERE license_status = 'suspended'"));
        $cancelled = intval($wpdb->get_var("SELECT COUNT(*) FROM $table WHERE license_status = 'cancelled'"));

        $expiring_soon = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE license_status = 'active' AND expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY)"
        )));

        // Edition breakdown
        $editions = array('community', 'enterprise', 'hyperscaler', 'reseller');
        $edition_counts = array();
        foreach ($editions as $edition) {
            $edition_counts[$edition] = intval($wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM $table WHERE license_status = 'active' AND product_edition = %s", $edition)
            ));
        }

        $total_licenses = $active + $expired + $suspended + $cancelled;
        ?>
        <div class="themisdb-dashboard-widget">
            <div class="tdb-license-stats">
                <div class="tdb-license-box tdb-license-active">
                    <div class="tdb-license-count"><?php echo intval($active); ?></div>
                    <div class="tdb-license-label"><?php esc_html_e('Aktiv', 'themisdb-order-request'); ?></div>
                </div>

                <div class="tdb-license-box tdb-license-expired">
                    <div class="tdb-license-count"><?php echo intval($expired); ?></div>
                    <div class="tdb-license-label"><?php esc_html_e('Abgelaufen', 'themisdb-order-request'); ?></div>
                </div>

                <div class="tdb-license-box tdb-license-suspended">
                    <div class="tdb-license-count"><?php echo intval($suspended); ?></div>
                    <div class="tdb-license-label"><?php esc_html_e('Gesperrt', 'themisdb-order-request'); ?></div>
                </div>

                <div class="tdb-license-box tdb-license-cancelled">
                    <div class="tdb-license-count"><?php echo intval($cancelled); ?></div>
                    <div class="tdb-license-label"><?php esc_html_e('Storniert', 'themisdb-order-request'); ?></div>
                </div>
            </div>

            <div class="tdb-alert-box" style="<?php echo intval($expiring_soon) > 0 ? 'display: block;' : 'display: none;'; ?>">
                ⚠️ <strong><?php echo intval($expiring_soon); ?></strong> 
                <?php esc_html_e('Lizenzen verfallen in 30 Tagen', 'themisdb-order-request'); ?>
            </div>

            <div class="tdb-edition-breakdown">
                <h3><?php esc_html_e('Editionen (aktiv)', 'themisdb-order-request'); ?></h3>
                <?php foreach ($edition_counts as $edition => $count) : ?>
                    <div class="tdb-summary-row">
                        <span><?php echo esc_html(ucfirst($edition)); ?></span>
                        <strong><?php echo intval($count); ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="tdb-summary-row">
                <span><?php esc_html_e('Gesamt (alle Status)', 'themisdb-order-request'); ?></span>
                <strong><?php echo intval($total_licenses); ?></strong>
            </div>

            <div class="tdb-actions">
                <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-licenses')); ?>" class="button">
                    <?php esc_html_e('Lizenzen verwalten', 'themisdb-order-request'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    // ──────────────────────────────────────────────────────────────────
    // WIDGET: Health Status
    // ──────────────────────────────────────────────────────────────────

    /**
     * Render system health status widget.
     */
    public static function render_health_status_widget() {
        global $wpdb;

        $health_items = array();

        // Database size check
        $db_size = self::get_database_size();
        $health_items[] = array(
            'label' => __('Datenbank-Größe', 'themisdb-order-request'),
            'value' => size_format($db_size, 2),
            'status' => ($db_size > 1000000000) ? 'warning' : 'good', // > 1GB
        );

        // Error log check
        $error_table = $wpdb->prefix . 'themisdb_error_log';
        $recent_errors = intval($wpdb->get_var(
            "SELECT COUNT(*) FROM $error_table WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        ));
        $health_items[] = array(
            'label' => __('Fehler (24h)', 'themisdb-order-request'),
            'value' => intval($recent_errors),
            'status' => ($recent_errors > 10) ? 'warning' : 'good',
        );

        // Table row count
        $orders_table = $wpdb->prefix . 'themisdb_orders';
        $row_count = intval($wpdb->get_var("SELECT COUNT(*) FROM $orders_table"));
        $health_items[] = array(
            'label' => __('Bestellungs-Einträge', 'themisdb-order-request'),
            'value' => number_format($row_count, 0, ',', '.'),
            'status' => 'good',
        );

        // Cron status (simple check)
        $cron_events = _get_cron_array();
        $themisdb_crons = array_filter((array) $cron_events, function ($event) {
            return isset($event['themisdb']) || strpos(wp_json_encode($event), 'themisdb') !== false;
        });
        $health_items[] = array(
            'label' => __('Cron-Jobs', 'themisdb-order-request'),
            'value' => count($themisdb_crons),
            'status' => 'good',
        );

        // Plugin version check
        $plugin_version = defined('THEMISDB_ORDER_VERSION') ? THEMISDB_ORDER_VERSION : 'unknown';
        $health_items[] = array(
            'label' => __('Plugin-Version', 'themisdb-order-request'),
            'value' => sanitize_text_field($plugin_version),
            'status' => 'good',
        );
        ?>
        <div class="themisdb-dashboard-widget">
            <div class="tdb-health-list">
                <?php foreach ($health_items as $item) : ?>
                    <div class="tdb-health-item tdb-health-<?php echo esc_attr($item['status']); ?>">
                        <span class="tdb-health-label"><?php echo esc_html($item['label']); ?></span>
                        <span class="tdb-health-value"><?php echo esc_html($item['value']); ?></span>
                        <span class="tdb-health-badge">
                            <?php echo $item['status'] === 'warning' ? '⚠️' : '✔'; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="tdb-health-note">
                <em><?php esc_html_e('Zuletzt aktualisiert:', 'themisdb-order-request'); ?> 
                <?php echo esc_html(date_i18n('H:i:s')); ?></em>
            </div>

            <div class="tdb-actions">
                <button type="button" class="button themisdb-dashboard-refresh" data-widget="health">
                    <?php esc_html_e('Aktualisieren', 'themisdb-order-request'); ?>
                </button>
            </div>
        </div>
        <?php
    }

    // ──────────────────────────────────────────────────────────────────
    // AJAX: Refresh Metrics
    // ──────────────────────────────────────────────────────────────────

    /**
     * Handle AJAX refresh request for dashboard metrics.
     */
    public static function ajax_refresh_metrics() {
        check_ajax_referer('themisdb_dashboard_nonce', 'nonce');

        $widget = isset($_POST['widget']) ? sanitize_key($_POST['widget']) : '';

        if (!in_array($widget, array('health', 'orders', 'revenue', 'support', 'license'), true)) {
            wp_send_json_error(array('message' => __('Invalid widget.', 'themisdb-order-request')));
            return;
        }

        ob_start();

        switch ($widget) {
            case 'health':
                self::render_health_status_widget();
                break;
            case 'orders':
                self::render_order_pipeline_widget();
                break;
            case 'revenue':
                self::render_revenue_chart_widget();
                break;
            case 'support':
                self::render_support_metrics_widget();
                break;
            case 'license':
                self::render_license_metrics_widget();
                break;
        }

        $output = ob_get_clean();

        wp_send_json_success(array('html' => $output));
    }

    // ──────────────────────────────────────────────────────────────────
    // HELPERS
    // ──────────────────────────────────────────────────────────────────

    /**
     * Get count of orders created in last N days.
     *
     * @param int $days
     * @return int
     */
    private static function get_orders_last_n_days($days) {
        global $wpdb;

        $table = $wpdb->prefix . 'themisdb_orders';
        $count = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
            intval($days)
        )));

        return $count;
    }

    /**
     * Get monthly revenue data for last 12 months.
     *
     * @param int $months
     * @return array
     */
    private static function get_monthly_revenue($months = 12) {
        global $wpdb;

        $table = $wpdb->prefix . 'themisdb_payments';
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE_FORMAT(payment_date, '%Y-%m') as month,
                SUM(CAST(amount AS DECIMAL(12,2))) as amount,
                COUNT(*) as count
            FROM $table
            WHERE payment_date >= DATE_SUB(NOW(), INTERVAL %d MONTH)
            AND payment_status IN ('completed', 'verified')
            GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
            ORDER BY month ASC",
            intval($months)
        ), ARRAY_A);

        // Fill gaps for missing months
        $all_months = array();
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $found = false;

            foreach ($results as $row) {
                if ($row['month'] === $month) {
                    $all_months[] = array(
                        'month' => $month,
                        'amount' => floatval($row['amount']),
                        'count' => intval($row['count']),
                    );
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $all_months[] = array(
                    'month' => $month,
                    'amount' => 0,
                    'count' => 0,
                );
            }
        }

        return $all_months;
    }

    /**
     * Get total database size for ThemisDB tables.
     *
     * @return int Size in bytes
     */
    private static function get_database_size() {
        global $wpdb;

        $db_name = DB_NAME;
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(data_length + index_length) FROM information_schema.tables WHERE table_schema = %s AND table_name LIKE %s",
            $db_name,
            $wpdb->prefix . 'themisdb_%'
        ));

        return intval($result ?: 0);
    }
}
