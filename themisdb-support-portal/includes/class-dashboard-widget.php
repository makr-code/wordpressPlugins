<?php
/**
 * ThemisDB Support Portal – Dashboard Widget
 *
 * Zeigt eine kompakte Ticket-Übersicht auf dem WordPress-Admin-Dashboard.
 *
 * @package ThemisDB_Support_Portal
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ThemisDB_Support_Dashboard_Widget {

    public static function init() {
        add_action( 'wp_dashboard_setup', array( __CLASS__, 'register_widget' ) );
    }

    public static function register_widget() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        wp_add_dashboard_widget(
            'themisdb_support_portal_status',
            __( 'ThemisDB Support-Tickets', 'themisdb-support-portal' ),
            array( __CLASS__, 'render' )
        );
    }

    public static function render() {
        global $wpdb;

        $table = $wpdb->prefix . 'themisdb_support_tickets';

        // Check table exists to avoid SQL errors when plugin not fully installed.
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) !== $table ) {
            echo '<p>' . esc_html__( 'Ticket-Tabelle nicht gefunden.', 'themisdb-support-portal' ) . '</p>';
            return;
        }

        $data = get_transient( 'themisdb_support_dashboard_counts' );
        if ( false === $data ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $rows = $wpdb->get_results( "SELECT status, COUNT(*) AS cnt FROM `{$table}` GROUP BY status", ARRAY_A );
            $data = array(
                'open'        => 0,
                'in_progress' => 0,
                'resolved'    => 0,
                'closed'      => 0,
                'total'       => 0,
            );
            foreach ( (array) $rows as $row ) {
                $status = sanitize_key( $row['status'] ?? '' );
                $cnt    = (int) ( $row['cnt'] ?? 0 );
                if ( isset( $data[ $status ] ) ) {
                    $data[ $status ] = $cnt;
                }
                $data['total'] += $cnt;
            }
            // High-priority open tickets.
            $data['urgent'] = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM `{$table}` WHERE status = 'open' AND priority IN ('high','urgent')"
            );
            set_transient( 'themisdb_support_dashboard_counts', $data, 5 * MINUTE_IN_SECONDS );
        }

        $admin_url = admin_url( 'admin.php?page=themisdb-support-portal' );
        ?>
        <div class="themisdb-widget-support">
            <?php if ( $data['urgent'] > 0 ) : ?>
            <div style="background:#fcf0f1;border-left:4px solid #dc3232;padding:6px 10px;margin-bottom:8px;">
                <strong style="color:#dc3232;">
                    <?php
                    printf(
                        /* translators: %d: number of urgent tickets */
                        esc_html( _n( '%d dringendes Ticket offen', '%d dringende Tickets offen', $data['urgent'], 'themisdb-support-portal' ) ),
                        esc_html( $data['urgent'] )
                    );
                    ?>
                </strong>
            </div>
            <?php endif; ?>
            <table class="widefat fixed" style="border:none;box-shadow:none;">
                <tbody>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Offen', 'themisdb-support-portal' ); ?></td>
                        <td><strong><?php echo esc_html( $data['open'] ); ?></strong></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'In Bearbeitung', 'themisdb-support-portal' ); ?></td>
                        <td><?php echo esc_html( $data['in_progress'] ); ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Gelöst', 'themisdb-support-portal' ); ?></td>
                        <td><?php echo esc_html( $data['resolved'] ); ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Gesamt', 'themisdb-support-portal' ); ?></td>
                        <td><?php echo esc_html( $data['total'] ); ?></td>
                    </tr>
                </tbody>
            </table>
            <p style="margin-top:8px;">
                <a href="<?php echo esc_url( $admin_url ); ?>" class="button button-small">
                    <?php esc_html_e( 'Alle Tickets', 'themisdb-support-portal' ); ?>
                </a>
            </p>
        </div>
        <?php
    }
}
