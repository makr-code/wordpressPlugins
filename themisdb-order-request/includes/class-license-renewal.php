<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB - Hybrid Database System                                   ║
╠═════════════════════════════════════════════════════════════════════╣
  File:            class-license-renewal.php                          ║
  Version:         0.0.2                                              ║
  Last Modified:   2026-03-09 04:08:19                                ║
  Author:          unknown                                            ║
╠═════════════════════════════════════════════════════════════════════╣
  Quality Metrics:                                                    ║
    • Maturity Level:  🟢 PRODUCTION-READY                             ║
    • Quality Score:   100.0/100                                      ║
    • Total Lines:     249                                            ║
    • Open Issues:     TODOs: 0, Stubs: 0                             ║
╠═════════════════════════════════════════════════════════════════════╣
  Revision History:                                                   ║
    • 2a1fb0423  2026-03-03  Merge branch 'develop' into copilot/audit-src-module-docu... ║
    • 9d3ecaa0e  2026-02-28  Add ThemisDB Wiki Integration plugin with documentation i... ║
╠═════════════════════════════════════════════════════════════════════╣
  Status: ✅ Production Ready                                          ║
╚═════════════════════════════════════════════════════════════════════╝
 */


/**
 * License Renewal Reminder for ThemisDB
 *
 * Runs as a daily WP-Cron job (registered on plugin activation).
 * For every active license whose expiry date falls within the configured
 * reminder window, it sends one renewal reminder e-mail per day until the
 * license is renewed or expires.
 *
 * The number of days before expiry to start sending reminders is read from
 * the WordPress option `themisdb_license_renewal_reminder_days` (default: 30).
 *
 * To prevent duplicate e-mails within a single day, a meta record is kept
 * in the license `usage_data` JSON field:
 *   usage_data.renewal_reminder_sent_date  →  last date a reminder was sent
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ThemisDB_License_Renewal {

    /**
     * Register renewal hooks.
     */
    public static function init() {
        add_action( 'init', array( __CLASS__, 'handle_one_click_request' ) );
    }

    /**
     * Check all active licenses and send renewal reminders as needed.
     * Called daily by WP-Cron via the `themisdb_license_renewal_check` action.
     */
    public static function send_renewal_reminders() {
        global $wpdb;

        // Reminder milestones required by roadmap phase 5.1.
        $reminder_days_list = apply_filters(
            'themisdb_license_renewal_reminder_days_list',
            array( 30, 7, 1 )
        );
        $reminder_days_list = array_values(
            array_unique(
                array_filter(
                    array_map( 'intval', (array) $reminder_days_list ),
                    function ( $v ) {
                        return $v > 0;
                    }
                )
            )
        );

        if ( empty( $reminder_days_list ) ) {
            return;
        }

        $table  = $wpdb->prefix . 'themisdb_licenses';
        $today  = gmdate( 'Y-m-d' );
        $max_days = max( $reminder_days_list );
        $cutoff = gmdate( 'Y-m-d', strtotime( "+{$max_days} days" ) );

        // Find active licenses expiring within the reminder window
        $licenses = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT l.*, o.customer_name, o.customer_email, o.customer_company
                 FROM {$table} l
                 LEFT JOIN {$wpdb->prefix}themisdb_orders o ON o.id = l.order_id
                 WHERE l.license_status = 'active'
                   AND l.expiry_date IS NOT NULL
                   AND l.expiry_date != '9999-12-31'
                   AND l.expiry_date > %s
                   AND l.expiry_date <= %s",
                $today,
                $cutoff
            ),
            ARRAY_A
        );

        foreach ( $licenses as $license ) {
            self::maybe_send_reminder( $license, $today, $reminder_days_list );
            self::maybe_auto_renew( $license );
        }
    }

    /**
     * Send a reminder for a single license if one has not already been sent today.
     *
     * @param array  $license            License row with joined customer columns.
     * @param string $today              Today's date (Y-m-d).
     * @param array  $reminder_days_list Reminder milestone days.
     */
    private static function maybe_send_reminder( array $license, string $today, array $reminder_days_list ) {
        global $wpdb;

        if ( empty( $license['expiry_date'] ) || $license['expiry_date'] === '9999-12-31' ) {
            return;
        }

        $days_left = (int) floor(
            ( strtotime( $license['expiry_date'] . ' 00:00:00' ) - strtotime( gmdate( 'Y-m-d' ) . ' 00:00:00' ) ) / DAY_IN_SECONDS
        );

        if ( ! in_array( $days_left, $reminder_days_list, true ) ) {
            return;
        }

        // Decode usage_data to check last reminder date
        $usage_data = ! empty( $license['usage_data'] )
            ? json_decode( $license['usage_data'], true )
            : array();

        if ( ! is_array( $usage_data ) ) {
            $usage_data = array();
        }

        $sent_days = isset( $usage_data['renewal_reminder_sent_days'] ) && is_array( $usage_data['renewal_reminder_sent_days'] )
            ? $usage_data['renewal_reminder_sent_days']
            : array();

        $today_milestones = isset( $sent_days[ $today ] ) && is_array( $sent_days[ $today ] )
            ? array_map( 'intval', $sent_days[ $today ] )
            : array();

        if ( in_array( $days_left, $today_milestones, true ) ) {
            return; // Already sent today
        }

        $sent = self::send_reminder_email( $license, $days_left );

        if ( $sent ) {
            // Keep old flag for backward compatibility.
            $usage_data['renewal_reminder_sent_date'] = $today;

            $today_milestones[] = $days_left;
            $sent_days[ $today ] = array_values( array_unique( $today_milestones ) );
            $usage_data['renewal_reminder_sent_days'] = $sent_days;

            $wpdb->update(
                $wpdb->prefix . 'themisdb_licenses',
                array( 'usage_data' => wp_json_encode( $usage_data ) ),
                array( 'id' => (int) $license['id'] ),
                array( '%s' ),
                array( '%d' )
            );
        }
    }

    /**
     * Auto-renew active licenses when enabled in usage_data and when due date is reached.
     *
     * @param array $license
     * @return void
     */
    private static function maybe_auto_renew( array $license ) {
        if ( get_option( 'themisdb_license_allow_auto_renewal', '1' ) !== '1' ) {
            return;
        }

        if ( empty( $license['expiry_date'] ) || $license['expiry_date'] === '9999-12-31' ) {
            return;
        }

        if ( $license['license_status'] !== 'active' ) {
            return;
        }

        $usage_data = ! empty( $license['usage_data'] )
            ? json_decode( $license['usage_data'], true )
            : array();
        if ( ! is_array( $usage_data ) ) {
            $usage_data = array();
        }

        $auto_renew_enabled = ! empty( $usage_data['auto_renew_enabled'] );
        if ( ! $auto_renew_enabled ) {
            return;
        }

        $today = gmdate( 'Y-m-d' );
        if ( strtotime( $license['expiry_date'] . ' 00:00:00' ) > strtotime( $today . ' 00:00:00' ) ) {
            return;
        }

        self::renew_license_term( intval( $license['id'] ), 'auto_renewal' );
    }

    /**
     * Extend a license by configured renewal term and reactivate status.
     *
     * @param int    $license_id
     * @param string $reason
     * @return bool
     */
    public static function renew_license_term( $license_id, $reason = 'manual' ) {
        global $wpdb;

        $license_id = intval( $license_id );
        if ( $license_id <= 0 ) {
            return false;
        }

        if ( ! class_exists( 'ThemisDB_License_Manager' ) ) {
            return false;
        }

        $license = ThemisDB_License_Manager::get_license( $license_id );
        if ( ! $license || $license['license_status'] === 'cancelled' ) {
            return false;
        }

        $term_days = intval( get_option( 'themisdb_license_default_term_days', 365 ) );
        if ( $term_days < 1 ) {
            $term_days = 365;
        }

        $base_date = ! empty( $license['expiry_date'] )
            ? $license['expiry_date']
            : gmdate( 'Y-m-d' );

        if ( $base_date < gmdate( 'Y-m-d' ) ) {
            $base_date = gmdate( 'Y-m-d' );
        }

        $new_expiry = gmdate( 'Y-m-d', strtotime( $base_date . " +{$term_days} days" ) );

        $usage_data = isset( $license['usage_data'] ) && is_array( $license['usage_data'] )
            ? $license['usage_data']
            : array();

        $usage_data['last_renewed_at'] = current_time( 'mysql' );
        $usage_data['last_renewal_reason'] = sanitize_text_field( $reason );
        $usage_data['renewal_term_days'] = $term_days;

        $result = $wpdb->update(
            $wpdb->prefix . 'themisdb_licenses',
            array(
                'expiry_date' => $new_expiry,
                'license_status' => 'active',
                'usage_data' => wp_json_encode( $usage_data ),
            ),
            array( 'id' => $license_id ),
            array( '%s', '%s', '%s' ),
            array( '%d' )
        );

        if ( class_exists( 'ThemisDB_Error_Handler' ) ) {
            ThemisDB_Error_Handler::log(
                $result !== false ? 'info' : 'error',
                $result !== false ? 'License renewed successfully' : 'License renewal failed',
                array(
                    'license_id' => $license_id,
                    'reason' => sanitize_text_field( $reason ),
                    'new_expiry' => $new_expiry,
                )
            );
        }

        return $result !== false;
    }

    /**
     * Create a one-click renewal URL for a license.
     *
     * @param int $license_id
     * @return string
     */
    public static function generate_one_click_url( $license_id ) {
        $license_id = intval( $license_id );
        if ( $license_id <= 0 ) {
            return '';
        }

        $token = wp_generate_password( 32, false, false );
        set_transient( 'themisdb_renew_token_' . $token, $license_id, DAY_IN_SECONDS * 3 );

        return add_query_arg(
            array(
                'themisdb_renew_token' => rawurlencode( $token ),
            ),
            home_url( '/' )
        );
    }

    /**
     * Process one-click renewal request from renewal email.
     */
    public static function handle_one_click_request() {
        if ( empty( $_GET['themisdb_renew_token'] ) ) {
            return;
        }

        $token = sanitize_text_field( wp_unslash( $_GET['themisdb_renew_token'] ) );
        $license_id = intval( get_transient( 'themisdb_renew_token_' . $token ) );
        if ( $license_id <= 0 ) {
            wp_die( esc_html__( 'Invalid or expired renewal token.', 'themisdb-order-request' ) );
        }

        $ok = self::renew_license_term( $license_id, 'one_click_renewal' );
        delete_transient( 'themisdb_renew_token_' . $token );

        $redirect = add_query_arg(
            array( 'themisdb_renewal' => $ok ? 'success' : 'failed' ),
            home_url( '/contact/' )
        );
        wp_safe_redirect( $redirect );
        exit;
    }

    /**
     * Send the renewal reminder e-mail to the customer.
     *
     * @param array $license  License row with joined customer columns.
     * @param int   $days_left
     * @return bool           True if wp_mail succeeded.
     */
    private static function send_reminder_email( array $license, $days_left ) {
        $to = $license['customer_email'] ?? '';
        if ( empty( $to ) || ! is_email( $to ) ) {
            return false;
        }

        $expiry_date  = $license['expiry_date'];
        $days_left    = intval( $days_left );
        $edition      = strtoupper( $license['product_edition'] ?? 'COMMUNITY' );
        $license_key  = $license['license_key'] ?? '';
        $customer     = $license['customer_name'] ?? $to;
        $company      = $license['customer_company'] ?? '';

        $subject = sprintf(
            /* translators: %1$s = edition, %2$d = days left */
            __( '[ThemisDB] Your %1$s license expires in %2$d day(s)', 'themisdb-order-request' ),
            $edition,
            $days_left
        );

        $renewal_url = apply_filters(
            'themisdb_renewal_url',
            home_url( '/contact/' ),
            $license
        );

        $one_click_url = self::generate_one_click_url( intval( $license['id'] ) );

        $message = self::get_reminder_template( array(
            'customer'    => $customer,
            'company'     => $company,
            'edition'     => $edition,
            'license_key' => $license_key,
            'expiry_date' => $expiry_date,
            'days_left'   => $days_left,
            'renewal_url' => $renewal_url,
            'one_click_url' => $one_click_url,
        ) );

        $from_email = get_option( 'themisdb_order_email_from', get_option( 'admin_email' ) );
        $from_name  = get_option( 'themisdb_order_email_from_name', get_option( 'blogname' ) );

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            sprintf( 'From: %s <%s>', $from_name, $from_email ),
        );

        return wp_mail( $to, $subject, $message, $headers );
    }

    /**
     * Build the HTML e-mail body for a renewal reminder.
     *
     * @param array $data  Template variables.
     * @return string      HTML e-mail body.
     */
    private static function get_reminder_template( array $data ) {
        $site_name = get_option( 'blogname' );
        $site_url  = home_url();

        ob_start();
        ?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px;">
    <h2 style="color:#333;"><?php esc_html_e( 'License Renewal Reminder', 'themisdb-order-request' ); ?></h2>

    <p><?php printf(
        /* translators: customer name */
        esc_html__( 'Dear %s,', 'themisdb-order-request' ),
        esc_html( $data['customer'] )
    ); ?></p>

    <p><?php printf(
        /* translators: %1$s = edition, %2$d = days, %3$s = date */
        esc_html__( 'Your ThemisDB %1$s license will expire in <strong>%2$d day(s)</strong> on %3$s.', 'themisdb-order-request' ),
        '<strong>' . esc_html( $data['edition'] ) . '</strong>',
        (int) $data['days_left'],
        '<strong>' . esc_html( $data['expiry_date'] ) . '</strong>'
    ); ?></p>

    <table style="border-collapse:collapse;width:100%;margin:20px 0;">
        <tr style="background:#f5f5f5;">
            <th style="text-align:left;padding:8px;border:1px solid #ddd;"><?php esc_html_e( 'License Key', 'themisdb-order-request' ); ?></th>
            <td style="padding:8px;border:1px solid #ddd;"><code><?php echo esc_html( $data['license_key'] ); ?></code></td>
        </tr>
        <tr>
            <th style="text-align:left;padding:8px;border:1px solid #ddd;"><?php esc_html_e( 'Edition', 'themisdb-order-request' ); ?></th>
            <td style="padding:8px;border:1px solid #ddd;"><?php echo esc_html( $data['edition'] ); ?></td>
        </tr>
        <tr style="background:#f5f5f5;">
            <th style="text-align:left;padding:8px;border:1px solid #ddd;"><?php esc_html_e( 'Expiry Date', 'themisdb-order-request' ); ?></th>
            <td style="padding:8px;border:1px solid #ddd;"><?php echo esc_html( $data['expiry_date'] ); ?></td>
        </tr>
        <?php if ( ! empty( $data['company'] ) ) : ?>
        <tr>
            <th style="text-align:left;padding:8px;border:1px solid #ddd;"><?php esc_html_e( 'Organization', 'themisdb-order-request' ); ?></th>
            <td style="padding:8px;border:1px solid #ddd;"><?php echo esc_html( $data['company'] ); ?></td>
        </tr>
        <?php endif; ?>
    </table>

    <p style="margin:24px 0;">
        <a href="<?php echo esc_url( $data['renewal_url'] ); ?>"
           style="background:#0073aa;color:#fff;padding:12px 24px;text-decoration:none;border-radius:4px;display:inline-block;">
            <?php esc_html_e( 'Renew License Now', 'themisdb-order-request' ); ?>
        </a>
    </p>

    <?php if ( ! empty( $data['one_click_url'] ) ) : ?>
    <p style="margin:0 0 24px;">
        <a href="<?php echo esc_url( $data['one_click_url'] ); ?>"
           style="background:#0f766e;color:#fff;padding:12px 24px;text-decoration:none;border-radius:4px;display:inline-block;">
            <?php esc_html_e( 'One-Click Renewal', 'themisdb-order-request' ); ?>
        </a>
    </p>
    <?php endif; ?>

    <p style="color:#777;font-size:12px;">
        <?php printf(
            /* translators: %s = site name */
            esc_html__( 'This message was sent automatically by %s.', 'themisdb-order-request' ),
            esc_html( $site_name )
        ); ?>
        <br>
        <a href="<?php echo esc_url( $site_url ); ?>"><?php echo esc_url( $site_url ); ?></a>
    </p>
</body>
</html>
        <?php
        return ob_get_clean();
    }
}
