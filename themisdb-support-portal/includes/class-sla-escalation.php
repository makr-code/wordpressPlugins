<?php
/**
 * SLA Escalation Engine for ThemisDB Support Portal.
 *
 * Implements ARCHITECTUR.md §5.3:
 *  - Warning at 70 % SLA consumption.
 *  - Escalation at 100 % (breach).
 *  - Management-Alert for repeat breaches per customer.
 */

if (!defined('ABSPATH')) {
    exit;
}

class ThemisDB_SLA_Escalation {

    // SLA window per priority in hours
    const SLA_HOURS = array(
        'urgent' => 4,
        'high'   => 8,
        'normal' => 24,
        'low'    => 72,
    );

    // Number of breaches before management-alert is triggered
    const MANAGEMENT_ALERT_THRESHOLD = 2;

    // -------------------------------------------------------------------------
    // Bootstrap
    // -------------------------------------------------------------------------

    public static function init() {
        if (!wp_next_scheduled('themisdb_sla_check')) {
            wp_schedule_event(time(), 'hourly', 'themisdb_sla_check');
        }
        add_action('themisdb_sla_check', array(__CLASS__, 'run_check'));
    }

    public static function deactivate() {
        wp_clear_scheduled_hook('themisdb_sla_check');
    }

    // -------------------------------------------------------------------------
    // SLA helpers
    // -------------------------------------------------------------------------

    /**
     * Return the SLA deadline for a given priority as a MySQL datetime string.
     *
     * @param string $priority  Ticket priority key.
     * @return string           MySQL datetime.
     */
    public static function calculate_sla_due_at($priority) {
        $hours = isset(self::SLA_HOURS[$priority]) ? (int) self::SLA_HOURS[$priority] : 24;
        return gmdate('Y-m-d H:i:s', time() + $hours * 3600);
    }

    /**
     * Return elapsed percentage of SLA time for a ticket (0-100+).
     *
     * @param array $ticket  Ticket row with 'created_at', 'sla_due_at'.
     * @return float
     */
    public static function sla_percent(array $ticket) {
        $created = strtotime((string) ($ticket['created_at'] ?? ''));
        $due     = strtotime((string) ($ticket['sla_due_at'] ?? ''));
        if (!$created || !$due || $due <= $created) {
            return 0.0;
        }
        $total   = $due - $created;
        $elapsed = time() - $created;
        return round($elapsed / $total * 100.0, 1);
    }

    // -------------------------------------------------------------------------
    // Cron: main check
    // -------------------------------------------------------------------------

    /**
     * Hourly cron callback: check all open tickets for SLA status.
     */
    public static function run_check() {
        global $wpdb;

        $table = $wpdb->prefix . 'themisdb_support_tickets';

        // Fetch open/in-progress tickets that have an SLA deadline
        $tickets = $wpdb->get_results(
            "SELECT * FROM {$table}
             WHERE status IN ('open','in_progress')
               AND sla_due_at IS NOT NULL",
            ARRAY_A
        );

        if (empty($tickets)) {
            return;
        }

        $management_breaches = array(); // customer_email => breach count

        foreach ($tickets as $ticket) {
            $pct = self::sla_percent($ticket);

            if ($pct >= 100.0 && empty($ticket['sla_breached_at'])) {
                // Mark breach and escalate
                self::mark_sla_field($ticket['id'], 'sla_breached_at', current_time('mysql', true));
                self::notify_sla_breach($ticket);

                // Track for management-alert
                $email = $ticket['customer_email'];
                if (!isset($management_breaches[$email])) {
                    $management_breaches[$email] = 0;
                }
                $management_breaches[$email]++;

            } elseif ($pct >= 70.0 && $pct < 100.0 && empty($ticket['sla_warned_at'])) {
                // Warning at 70 %
                self::mark_sla_field($ticket['id'], 'sla_warned_at', current_time('mysql', true));
                self::notify_sla_warning($ticket);
            }
        }

        // Management-alerts: customers with repeated current-run breaches
        // + look up total historic breaches from DB
        foreach ($management_breaches as $email => $count) {
            $total_breaches = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$table}
                     WHERE customer_email = %s AND sla_breached_at IS NOT NULL",
                    $email
                )
            );

            if ($total_breaches >= self::MANAGEMENT_ALERT_THRESHOLD) {
                self::notify_management_alert($email, $total_breaches);
            }
        }
    }

    // -------------------------------------------------------------------------
    // DB helpers
    // -------------------------------------------------------------------------

    /**
     * Set a single SLA timestamp column on a ticket row.
     *
     * @param int    $ticket_id
     * @param string $column     One of 'sla_warned_at', 'sla_breached_at'.
     * @param string $value      MySQL datetime string.
     */
    private static function mark_sla_field($ticket_id, $column, $value) {
        global $wpdb;

        $allowed = array('sla_warned_at', 'sla_breached_at');
        if (!in_array($column, $allowed, true)) {
            return;
        }

        $wpdb->update(
            $wpdb->prefix . 'themisdb_support_tickets',
            array($column => $value),
            array('id' => intval($ticket_id)),
            array('%s'),
            array('%d')
        );
    }

    // -------------------------------------------------------------------------
    // Notifications
    // -------------------------------------------------------------------------

    /**
     * Notify assignee/admin that a ticket is approaching its SLA deadline (70 %).
     *
     * @param array $ticket
     */
    private static function notify_sla_warning(array $ticket) {
        $to = self::get_assignee_email($ticket);
        if (!is_email($to)) {
            return;
        }

        $ticket_number = esc_html((string) $ticket['ticket_number']);
        $subject_line  = esc_html((string) $ticket['subject']);
        $priority      = esc_html((string) $ticket['priority']);
        $due           = esc_html((string) $ticket['sla_due_at']);
        $pct           = self::sla_percent($ticket);

        $subject = sprintf('[ThemisDB] SLA-Warnung: Ticket %s hat %s%% SLA verbraucht', $ticket_number, $pct);
        $body    = self::render_mail(
            'SLA-Warnung – ' . $ticket_number,
            sprintf(
                '<p>Das folgende Support-Ticket nahert sich seiner SLA-Deadline.</p>
                <table style="width:100%%;border-collapse:collapse;font-size:14px;">
                    <tr><td style="padding:6px 0;color:#555;width:40%%;">Ticket-Nummer</td><td><strong>%s</strong></td></tr>
                    <tr><td style="padding:6px 0;color:#555;">Betreff</td><td><strong>%s</strong></td></tr>
                    <tr><td style="padding:6px 0;color:#555;">Prioritat</td><td><strong>%s</strong></td></tr>
                    <tr><td style="padding:6px 0;color:#555;">SLA-Deadline</td><td><strong>%s</strong></td></tr>
                    <tr><td style="padding:6px 0;color:#555;">SLA-Verbrauch</td><td><strong style="color:#ff6b00;">%s%%</strong></td></tr>
                </table>
                <p style="margin-top:16px;color:#d4700a;font-weight:bold;">Bitte reagieren Sie umgehend, um einen SLA-Verstoss zu vermeiden.</p>',
                $ticket_number,
                $subject_line,
                $priority,
                $due,
                $pct
            )
        );

        add_filter('wp_mail_content_type', array(__CLASS__, '_html_type'));
        wp_mail($to, $subject, $body);
        remove_filter('wp_mail_content_type', array(__CLASS__, '_html_type'));
    }

    /**
     * Notify assignee/admin about an SLA breach (100 % exceeded).
     *
     * @param array $ticket
     */
    private static function notify_sla_breach(array $ticket) {
        $to = self::get_assignee_email($ticket);
        if (!is_email($to)) {
            return;
        }

        $ticket_number = esc_html((string) $ticket['ticket_number']);
        $subject_line  = esc_html((string) $ticket['subject']);
        $priority      = esc_html((string) $ticket['priority']);
        $due           = esc_html((string) $ticket['sla_due_at']);
        $customer      = esc_html((string) $ticket['customer_name']);
        $customer_mail = esc_html((string) $ticket['customer_email']);

        $subject = sprintf('[ThemisDB] SLA-VERSTOSS: Ticket %s ist uberfällig', $ticket_number);
        $body    = self::render_mail(
            'SLA-Verstoss – ' . $ticket_number,
            sprintf(
                '<p style="color:#dc3545;font-weight:bold;">Dieses Ticket hat seine SLA-Deadline uberschritten.</p>
                <table style="width:100%%;border-collapse:collapse;font-size:14px;">
                    <tr><td style="padding:6px 0;color:#555;width:40%%;">Ticket-Nummer</td><td><strong>%s</strong></td></tr>
                    <tr><td style="padding:6px 0;color:#555;">Betreff</td><td><strong>%s</strong></td></tr>
                    <tr><td style="padding:6px 0;color:#555;">Prioritat</td><td><strong>%s</strong></td></tr>
                    <tr><td style="padding:6px 0;color:#555;">SLA-Deadline</td><td><strong style="color:#dc3545;">%s</strong></td></tr>
                    <tr><td style="padding:6px 0;color:#555;">Kunde</td><td><strong>%s</strong> (%s)</td></tr>
                </table>
                <p style="margin-top:16px;color:#dc3545;font-weight:bold;">Sofortige Bearbeitung erforderlich!</p>',
                $ticket_number,
                $subject_line,
                $priority,
                $due,
                $customer,
                $customer_mail
            )
        );

        add_filter('wp_mail_content_type', array(__CLASS__, '_html_type'));
        wp_mail($to, $subject, $body);
        remove_filter('wp_mail_content_type', array(__CLASS__, '_html_type'));

        // Persist SLA breach as operational incident.
        if (class_exists('ThemisDB_Incident_Log')) {
            ThemisDB_Incident_Log::log(
                ThemisDB_Incident_Log::DOMAIN_SLA,
                ThemisDB_Incident_Log::SEVERITY_HIGH,
                sprintf('SLA-Verstoss: Ticket %s (%s) fuer Kunde %s', $ticket['ticket_number'], $ticket['priority'], $ticket['customer_email']),
                array(
                    'ticket_id'      => (int) $ticket['id'],
                    'ticket_number'  => $ticket['ticket_number'],
                    'priority'       => $ticket['priority'],
                    'sla_due_at'     => $ticket['sla_due_at'],
                    'customer_email' => $ticket['customer_email'],
                )
            );
        }
    }

    /**
     * Send management-alert when a customer has repeated SLA breaches.
     *
     * @param string $customer_email
     * @param int    $breach_count
     */
    private static function notify_management_alert($customer_email, $breach_count) {
        $management_email = get_option('themisdb_support_management_email', get_option('admin_email'));
        if (!is_email($management_email)) {
            return;
        }

        // Avoid duplicate alerts: track last alert per customer in option
        $last_alerts = (array) get_option('themisdb_sla_last_management_alert', array());
        $last_alert_time = isset($last_alerts[$customer_email]) ? (int) $last_alerts[$customer_email] : 0;
        if (time() - $last_alert_time < 3600 * 24) {
            // Already alerted today for this customer
            return;
        }

        $last_alerts[$customer_email] = time();
        update_option('themisdb_sla_last_management_alert', $last_alerts);

        $subject = sprintf('[ThemisDB] Management-Alert: Kunde %s hat %d SLA-Verstoss(e)', $customer_email, $breach_count);
        $body    = self::render_mail(
            'Management-Alert – Wiederholte SLA-Verstossmeldungen',
            sprintf(
                '<p>Der folgende Kunde hat wiederholt SLA-Deadlines verletzt. Bitte priorisieren.</p>
                <table style="width:100%%;border-collapse:collapse;font-size:14px;">
                    <tr><td style="padding:6px 0;color:#555;width:40%%;">Kunden-E-Mail</td><td><strong>%s</strong></td></tr>
                    <tr><td style="padding:6px 0;color:#555;">Anzahl SLA-Verstösse</td><td><strong style="color:#dc3545;">%d</strong></td></tr>
                    <tr><td style="padding:6px 0;color:#555;">Management-Schwelle</td><td>%d Verstösse</td></tr>
                </table>
                <p style="margin-top:16px;">Bitte wenden Sie sich an den zustandigen Support-Lead und klaren Sie die Ursachen.</p>',
                esc_html($customer_email),
                $breach_count,
                self::MANAGEMENT_ALERT_THRESHOLD
            )
        );

        add_filter('wp_mail_content_type', array(__CLASS__, '_html_type'));
        wp_mail($management_email, $subject, $body);
        remove_filter('wp_mail_content_type', array(__CLASS__, '_html_type'));
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Return the email address to notify for a ticket.
     * Prefers assignee → admin.
     *
     * @param array $ticket
     * @return string
     */
    private static function get_assignee_email(array $ticket) {
        if (!empty($ticket['assignee_user_id'])) {
            $assignee = get_user_by('id', intval($ticket['assignee_user_id']));
            if ($assignee && is_email($assignee->user_email)) {
                return $assignee->user_email;
            }
        }

        $admin = get_option('themisdb_support_admin_email', get_option('admin_email'));
        return is_email($admin) ? $admin : '';
    }

    /**
     * Render a standard HTML mail wrapper.
     *
     * @param string $heading
     * @param string $content HTML (already escaped).
     * @return string
     */
    private static function render_mail($heading, $content) {
        $site_name = get_option('blogname', 'ThemisDB');
        $site_url  = home_url('/');
        $year      = gmdate('Y');

        return '<!DOCTYPE html>
<html lang="de">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f5f5f5;font-family:Arial,Helvetica,sans-serif;color:#333;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;padding:32px 0;">
    <tr><td align="center">
      <table width="580" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:6px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">
        <tr><td style="background:#0073aa;padding:24px 32px;">
          <h1 style="margin:0;color:#fff;font-size:20px;">' . esc_html($site_name) . '</h1>
          <p style="margin:4px 0 0;color:#cce8f4;font-size:13px;">Service-Desk – SLA-Monitor</p>
        </td></tr>
        <tr><td style="padding:28px 32px 8px;">
          <h2 style="margin:0;font-size:18px;color:#0073aa;">' . esc_html($heading) . '</h2>
        </td></tr>
        <tr><td style="padding:12px 32px 28px;">' . $content . '</td></tr>
        <tr><td style="background:#f5f5f5;padding:18px 32px;border-top:1px solid #e0e0e0;text-align:center;">
          <p style="margin:0;font-size:12px;color:#888;">&copy; ' . esc_html($year) . ' <a href="' . esc_url($site_url) . '" style="color:#0073aa;text-decoration:none;">' . esc_html($site_name) . '</a></p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>';
    }

    /** Content-type filter callback. */
    public static function _html_type() {
        return 'text/html';
    }
}
