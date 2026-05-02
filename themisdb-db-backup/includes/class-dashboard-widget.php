<?php
/**
 * ThemisDB DB Backup – Dashboard Widget
 *
 * Zeigt auf dem WordPress-Admin-Dashboard eine kompakte Backup-Übersicht:
 * letztes Backup, Anzahl Backups, Chain-Integrity-Status.
 *
 * @package ThemisDB_DB_Backup
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ThemisDB_DB_Backup_Dashboard_Widget {

    public static function init() {
        add_action( 'wp_dashboard_setup', array( __CLASS__, 'register_widget' ) );
    }

    public static function register_widget() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        wp_add_dashboard_widget(
            'themisdb_db_backup_status',
            __( 'ThemisDB DB Backup', 'themisdb-db-backup' ),
            array( __CLASS__, 'render' )
        );
    }

    public static function render() {
        $index_file = ThemisDB_DB_Backup_Service::get_backup_dir() . 'backup-index.jsonl';
        $entries    = array();

        if ( file_exists( $index_file ) ) {
            $lines = file( $index_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
            foreach ( (array) $lines as $line ) {
                $entry = json_decode( $line, true );
                if ( is_array( $entry ) ) {
                    $entries[] = $entry;
                }
            }
        }

        $count       = count( $entries );
        $last        = $count > 0 ? end( $entries ) : null;
        $last_date   = $last ? esc_html( $last['created_at'] ?? '—' ) : '—';
        $last_size   = $last ? self::format_bytes( $last['size_bytes'] ?? 0 ) : '—';
        $last_trigger = $last ? esc_html( $last['trigger'] ?? '—' ) : '—';

        // Quick chain check (recompute last entry only for speed)
        $chain_ok = null;
        if ( $last ) {
            $prev     = $count >= 2 ? ( $entries[ $count - 2 ]['chain_hash'] ?? '' ) : '';
            $expected = hash( 'sha256',
                $prev .
                ( $last['sha256'] ?? '' ) .
                ( $last['created_at'] ?? '' ) .
                ( $last['filename'] ?? '' )
            );
            $chain_ok = hash_equals( $expected, $last['chain_hash'] ?? '' );
        }

        $admin_url = admin_url( 'admin.php?page=themisdb-db-backup' );
        ?>
        <div class="themisdb-widget-db-backup">
            <table class="widefat fixed" style="border:none;box-shadow:none;">
                <tbody>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Backups gesamt', 'themisdb-db-backup' ); ?></td>
                        <td><strong><?php echo esc_html( $count ); ?></strong></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Letztes Backup', 'themisdb-db-backup' ); ?></td>
                        <td><?php echo $last_date; ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Dateigröße', 'themisdb-db-backup' ); ?></td>
                        <td><?php echo esc_html( $last_size ); ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Auslöser', 'themisdb-db-backup' ); ?></td>
                        <td><?php echo $last_trigger; ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Chain-Integrität', 'themisdb-db-backup' ); ?></td>
                        <td>
                        <?php if ( $chain_ok === null ): ?>
                            <span style="color:#999;">—</span>
                        <?php elseif ( $chain_ok ): ?>
                            <span style="color:#46b450;">&#10003; <?php esc_html_e( 'OK', 'themisdb-db-backup' ); ?></span>
                        <?php else: ?>
                            <span style="color:#dc3232;">&#10007; <?php esc_html_e( 'Fehler', 'themisdb-db-backup' ); ?></span>
                        <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p style="margin-top:8px;">
                <a href="<?php echo esc_url( $admin_url ); ?>" class="button button-small">
                    <?php esc_html_e( 'Zur Backup-Verwaltung', 'themisdb-db-backup' ); ?>
                </a>
            </p>
        </div>
        <?php
    }

    private static function format_bytes( $bytes ) {
        $bytes = (int) $bytes;
        if ( $bytes <= 0 ) {
            return '0 B';
        }
        $units = array( 'B', 'KB', 'MB', 'GB' );
        $i     = (int) floor( log( $bytes, 1024 ) );
        $i     = min( $i, count( $units ) - 1 );
        return round( $bytes / pow( 1024, $i ), 2 ) . ' ' . $units[ $i ];
    }
}
