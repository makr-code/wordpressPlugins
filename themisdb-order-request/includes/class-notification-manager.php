<?php
/**
 * ThemisDB – Notification & Alerts System
 *
 * Manages operational alerts for orders, support SLA, errors, database size,
 * and cron job monitoring. Renders WordPress admin notices for critical conditions.
 *
 * @version 1.0.0
 * @since Phase 4.2
 */

if (!defined('ABSPATH')) {
    exit;
}

class ThemisDB_Notification_Manager {

    /**
     * Option key for storing active notifications in WordPress options.
     */
    const OPTION_ACTIVE_NOTIFICATIONS = 'themisdb_active_notifications';

    /**
     * Option key for storing dismissed notification timestamps.
     */
    const OPTION_DISMISSED_NOTIFICATIONS = 'themisdb_dismissed_notifications';

    /**
     * Initialize notification system hooks.
     */
    public static function init() {
        // Register admin notices hook.
        add_action('admin_notices', array(__CLASS__, 'render_admin_notices'), 10, 0);

        // Register notification check cron (runs every 30 minutes).
        if (!wp_next_scheduled('themisdb_run_notification_checks')) {
            wp_schedule_event(
                time(),
                'themisdb_half_hour',
                'themisdb_run_notification_checks'
            );
        }

        // Register custom cron interval if not already registered.
        add_filter('cron_schedules', array(__CLASS__, 'add_cron_intervals'));

        // Hook cron event to check all alerts.
        add_action('themisdb_run_notification_checks', array(__CLASS__, 'run_all_checks'));

        // Add AJAX dismiss handler.
        add_action('wp_ajax_themisdb_dismiss_notification', array(__CLASS__, 'ajax_dismiss_notification'));
    }

    /**
     * Add custom cron interval for 30 minutes.
     *
     * @param array $schedules
     * @return array
     */
    public static function add_cron_intervals($schedules) {
        if (!isset($schedules['themisdb_half_hour'])) {
            $schedules['themisdb_half_hour'] = array(
                'interval' => 30 * 60, // 30 minutes in seconds
                'display'  => __('Every 30 Minutes', 'themisdb-order-request'),
            );
        }
        return $schedules;
    }

    /**
     * Run all alert checks and store active notifications.
     */
    public static function run_all_checks() {
        $active_alerts = array();

        // Check 1: Payment Overdue (> 7 days pending)
        $overdue = self::check_payment_overdue();
        if (!empty($overdue)) {
            $active_alerts[] = $overdue;
        }

        // Check 2: Support SLA Breached
        $sla_breach = self::check_support_sla_breach();
        if (!empty($sla_breach)) {
            $active_alerts[] = $sla_breach;
        }

        // Check 3: High Error Rate (> 10 errors in 24h)
        $error_rate = self::check_high_error_rate();
        if (!empty($error_rate)) {
            $active_alerts[] = $error_rate;
        }

        // Check 4: Database Size Warning (> 1 GB)
        $db_size = self::check_database_size();
        if (!empty($db_size)) {
            $active_alerts[] = $db_size;
        }

        // Check 5: Cron Job Skipped
        $cron_skip = self::check_cron_skip();
        if (!empty($cron_skip)) {
            $active_alerts[] = $cron_skip;
        }

        // Store active alerts (non-dismissed only).
        self::update_active_notifications($active_alerts);
    }

    /**
     * Check for payment orders pending > 7 days.
     *
     * @return array|null Alert data or null if no alerts
     */
    private static function check_payment_overdue() {
        global $wpdb;

        if (!class_exists('ThemisDB_Order_Manager')) {
            return null;
        }

        $table = $wpdb->prefix . 'themisdb_orders';
        $threshold_date = date('Y-m-d H:i:s', strtotime('-7 days'));

        $overdue_count = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE status = %s AND created_at < %s",
            'pending',
            $threshold_date
        )));

        if ($overdue_count === 0) {
            return null;
        }

        return array(
            'id'       => 'payment-overdue',
            'severity' => 'warning',
            'title'    => __('Payment Overdue', 'themisdb-order-request'),
            'message'  => sprintf(
                __('%d order(s) pending payment for more than 7 days. Gentle payment reminder recommended.', 'themisdb-order-request'),
                $overdue_count
            ),
            'action_url' => admin_url('admin.php?page=themisdb-orders&status=pending'),
            'action_text' => __('View Orders', 'themisdb-order-request'),
            'checked_at' => current_time('mysql'),
        );
    }

    /**
     * Check for support tickets exceeding SLA response time.
     *
     * @return array|null Alert data or null if no alerts
     */
    private static function check_support_sla_breach() {
        global $wpdb;

        if (!class_exists('ThemisDB_Support_Ticket_Manager') && !class_exists('ThemisDB_Order_Support_Ticket_Manager')) {
            return null;
        }

        $table_tickets = $wpdb->prefix . 'themisdb_support_tickets';
        $table_benefits = $wpdb->prefix . 'themisdb_support_benefits';

        // Find tickets in progress with SLA exceeded.
        $sla_breaches = intval($wpdb->get_var(
            "SELECT COUNT(*)
             FROM {$table_tickets} t
             LEFT JOIN {$table_benefits} b ON t.benefit_id = b.id
             WHERE t.status IN ('open', 'in_progress')
             AND TIMESTAMPDIFF(HOUR, t.created_at, NOW()) > COALESCE(b.response_sla_hours, 48)
             LIMIT 1"
        ));

        if ($sla_breaches === 0) {
            return null;
        }

        return array(
            'id'       => 'sla-breach',
            'severity' => 'error',
            'title'    => __('Support SLA Breached', 'themisdb-order-request'),
            'message'  => sprintf(
                __('%d support ticket(s) have exceeded their SLA response time. Immediate action recommended.', 'themisdb-order-request'),
                $sla_breaches
            ),
            'action_url' => admin_url('admin.php?page=themisdb-support'),
            'action_text' => __('View Tickets', 'themisdb-order-request'),
            'checked_at' => current_time('mysql'),
        );
    }

    /**
     * Check for high error rate (> 10 errors in last 24 hours).
     *
     * @return array|null Alert data or null if no alerts
     */
    private static function check_high_error_rate() {
        global $wpdb;

        $table = $wpdb->prefix . 'themisdb_error_log';

        // Check if table exists first.
        $table_exists = $wpdb->get_var($wpdb->prepare(
            'SHOW TABLES LIKE %s',
            $table
        ));

        if (!$table_exists) {
            return null;
        }

        $threshold_date = date('Y-m-d H:i:s', strtotime('-24 hours'));

        $error_count = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE level = %s AND created_at >= %s",
            'error',
            $threshold_date
        )));

        if ($error_count <= 10) {
            return null;
        }

        return array(
            'id'       => 'high-error-rate',
            'severity' => 'error',
            'title'    => __('High Error Rate Detected', 'themisdb-order-request'),
            'message'  => sprintf(
                __('%d error(s) logged in the last 24 hours. Please review the error log.', 'themisdb-order-request'),
                $error_count
            ),
            'action_url' => admin_url('admin.php?page=themisdb-error-log'),
            'action_text' => __('View Error Log', 'themisdb-order-request'),
            'checked_at' => current_time('mysql'),
        );
    }

    /**
     * Check if database size exceeds 1 GB (approx).
     *
     * @return array|null Alert data or null if no alerts
     */
    private static function check_database_size() {
        global $wpdb;

        // Get total size of ThemisDB tables in bytes.
        $size_bytes = intval($wpdb->get_var(
            "SELECT SUM(data_length + index_length)
             FROM information_schema.tables
             WHERE table_schema = DATABASE()
             AND table_name LIKE '%themisdb%'"
        ));

        $size_gb = $size_bytes / (1024 * 1024 * 1024);

        if ($size_gb <= 1.0) {
            return null;
        }

        return array(
            'id'       => 'db-size-warning',
            'severity' => 'warning',
            'title'    => __('Database Size Warning', 'themisdb-order-request'),
            'message'  => sprintf(
                __('ThemisDB tables consume approximately %.2f GB. Consider archiving old records.', 'themisdb-order-request'),
                $size_gb
            ),
            'action_url' => admin_url('admin.php?page=themisdb-settings&tab=maintenance'),
            'action_text' => __('Database Maintenance', 'themisdb-order-request'),
            'checked_at' => current_time('mysql'),
        );
    }

    /**
     * Check if cron jobs are being skipped (no successful run in 24h).
     *
     * @return array|null Alert data or null if no alerts
     */
    private static function check_cron_skip() {
        // Check WordPress loopback request mechanism.
        $loopback_available = wp_remote_post(
            admin_url('admin-ajax.php'),
            array(
                'blocking'   => false,
                'sslverify'  => apply_filters('https_local_over_ssl', false),
                'user-agent' => 'WordPress ThemisDB Cron Check',
            )
        );

        if (is_wp_error($loopback_available)) {
            return array(
                'id'       => 'cron-skip',
                'severity' => 'error',
                'title'    => __('Cron Job Not Running', 'themisdb-order-request'),
                'message'  => __(
                    'WordPress background job scheduler (loopback requests) appears to be disabled. ' .
                    'Scheduled tasks may not execute. Please check server logs.',
                    'themisdb-order-request'
                ),
                'action_url' => admin_url('admin.php?page=themisdb-settings&tab=cron'),
                'action_text' => __('Cron Settings', 'themisdb-order-request'),
                'checked_at' => current_time('mysql'),
            );
        }

        // Also check our custom cron last run time.
        $last_cron_run = get_option('themisdb_notification_check_last_run', 0);
        $hours_since = (time() - intval($last_cron_run)) / 3600;

        if ($hours_since > 24) {
            return array(
                'id'       => 'cron-skip',
                'severity' => 'warning',
                'title'    => __('ThemisDB Cron Delayed', 'themisdb-order-request'),
                'message'  => sprintf(
                    __('Notification checks last ran %.1f hours ago. System may need restart.', 'themisdb-order-request'),
                    $hours_since
                ),
                'action_url' => admin_url('admin.php?page=themisdb-settings&tab=system'),
                'action_text' => __('System Status', 'themisdb-order-request'),
                'checked_at' => current_time('mysql'),
            );
        }

        return null;
    }

    /**
     * Update active notifications in WordPress options.
     *
     * @param array $alerts Array of alert data
     */
    private static function update_active_notifications($alerts) {
        $dismissed = self::get_dismissed_notifications();
        $active = array();

        foreach ($alerts as $alert) {
            $alert_id = isset($alert['id']) ? $alert['id'] : '';
            if ($alert_id === '') {
                continue;
            }

            // Skip if this alert is currently dismissed.
            if (isset($dismissed[$alert_id]) && $dismissed[$alert_id] > (time() - 24 * 3600)) {
                continue;
            }

            $active[$alert_id] = $alert;
        }

        update_option(self::OPTION_ACTIVE_NOTIFICATIONS, $active, false);

        // Log last check time for cron monitoring.
        update_option('themisdb_notification_check_last_run', time(), false);
    }

    /**
     * Get dismissed notifications (active dismissals < 24h old).
     *
     * @return array Map of dismissed alert IDs to dismiss timestamps
     */
    private static function get_dismissed_notifications() {
        $dismissed = get_option(self::OPTION_DISMISSED_NOTIFICATIONS, array());
        if (!is_array($dismissed)) {
            return array();
        }

        $active_dismiss = array();
        $now = time();

        foreach ($dismissed as $alert_id => $dismiss_time) {
            $age = $now - intval($dismiss_time);
            // Keep dismissals for 24 hours.
            if ($age < 24 * 3600) {
                $active_dismiss[$alert_id] = $dismiss_time;
            }
        }

        // Clean up old dismissals.
        if (count($active_dismiss) < count($dismissed)) {
            update_option(self::OPTION_DISMISSED_NOTIFICATIONS, $active_dismiss, false);
        }

        return $active_dismiss;
    }

    /**
     * Render active notifications as WordPress admin notices.
     */
    public static function render_admin_notices() {
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        $active = get_option(self::OPTION_ACTIVE_NOTIFICATIONS, array());
        if (empty($active) || !is_array($active)) {
            return;
        }

        foreach ($active as $alert_id => $alert) {
            self::render_single_notice($alert_id, $alert);
        }
    }

    /**
     * Render a single admin notice.
     *
     * @param string $alert_id
     * @param array  $alert
     */
    private static function render_single_notice($alert_id, $alert) {
        $severity = isset($alert['severity']) ? $alert['severity'] : 'warning';
        $title = isset($alert['title']) ? $alert['title'] : '';
        $message = isset($alert['message']) ? $alert['message'] : '';
        $action_url = isset($alert['action_url']) ? $alert['action_url'] : '';
        $action_text = isset($alert['action_text']) ? $alert['action_text'] : '';

        $notice_class = 'notice notice-' . sanitize_html_class($severity);
        if ($severity === 'error') {
            $notice_class = 'notice notice-error';
        }

        ?>
        <div class="<?php echo esc_attr($notice_class); ?> is-dismissible themisdb-notification" data-alert-id="<?php echo esc_attr($alert_id); ?>">
            <p>
                <strong><?php echo esc_html($title); ?></strong><br />
                <?php echo esc_html($message); ?>
                <?php if (!empty($action_url) && !empty($action_text)): ?>
                    <br /><a href="<?php echo esc_url($action_url); ?>" class="button button-primary" style="margin-top: 8px;">
                        <?php echo esc_html($action_text); ?>
                    </a>
                <?php endif; ?>
            </p>
        </div>

        <script type="text/javascript">
            (function($) {
                $(document).on('click', '.themisdb-notification .notice-dismiss', function(e) {
                    var $notice = $(this).closest('.themisdb-notification');
                    var alertId = $notice.data('alert-id');
                    if (!alertId) return;

                    $.post(ajaxurl, {
                        action: 'themisdb_dismiss_notification',
                        alert_id: alertId,
                        nonce: '<?php echo wp_create_nonce('themisdb_dismiss_notification'); ?>'
                    });
                });
            })(jQuery);
        </script>
        <?php
    }

    /**
     * AJAX handler for dismissing notifications.
     */
    public static function ajax_dismiss_notification() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }

        check_ajax_referer('themisdb_dismiss_notification');

        $alert_id = isset($_POST['alert_id']) ? sanitize_key($_POST['alert_id']) : '';
        if ($alert_id === '') {
            wp_send_json_error(array('message' => 'Invalid alert ID'));
        }

        // Store dismissal timestamp.
        $dismissed = get_option(self::OPTION_DISMISSED_NOTIFICATIONS, array());
        if (!is_array($dismissed)) {
            $dismissed = array();
        }

        $dismissed[$alert_id] = time();
        update_option(self::OPTION_DISMISSED_NOTIFICATIONS, $dismissed, false);

        wp_send_json_success(array('message' => 'Notification dismissed'));
    }

    /**
     * Force a notification check immediately (useful for testing/diagnostics).
     *
     * @return array Map of all current alerts
     */
    public static function trigger_check_now() {
        self::run_all_checks();
        return get_option(self::OPTION_ACTIVE_NOTIFICATIONS, array());
    }

    /**
     * Manually add a custom notification (for plugin/extension use).
     *
     * @param string $alert_id
     * @param array  $alert_data
     */
    public static function add_custom_alert($alert_id, $alert_data) {
        $active = get_option(self::OPTION_ACTIVE_NOTIFICATIONS, array());
        if (!is_array($active)) {
            $active = array();
        }

        $alert_id = sanitize_key($alert_id);
        if ($alert_id === '') {
            return false;
        }

        $alert_data['id'] = $alert_id;
        $alert_data['checked_at'] = current_time('mysql');

        $active[$alert_id] = $alert_data;
        update_option(self::OPTION_ACTIVE_NOTIFICATIONS, $active, false);

        return true;
    }

    /**
     * Clear all active notifications (for testing/reset).
     */
    public static function clear_all() {
        delete_option(self::OPTION_ACTIVE_NOTIFICATIONS);
        delete_option(self::OPTION_DISMISSED_NOTIFICATIONS);
    }
}
