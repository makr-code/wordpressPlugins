<?php
/**
 * ThemisDB GitHub Bridge – Dashboard Widget
 *
 * Zeigt den Bridge-Aktivierungsstatus, das konfigurierte Repository
 * und Sync-Einstellungen auf dem WordPress-Admin-Dashboard.
 *
 * @package ThemisDB_GitHub_Bridge
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ThemisDB_GitHub_Bridge_Dashboard_Widget {

    public static function init() {
        add_action( 'wp_dashboard_setup', array( __CLASS__, 'register_widget' ) );
    }

    public static function register_widget() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        wp_add_dashboard_widget(
            'themisdb_github_bridge_status',
            __( 'ThemisDB GitHub Bridge', 'themisdb-github-bridge' ),
            array( __CLASS__, 'render' )
        );
    }

    public static function render() {
        $enabled          = get_option( 'themisdb_github_bridge_enabled', '0' ) === '1';
        $repo             = get_option( 'themisdb_github_bridge_repository', '' );
        $sync_orders      = get_option( 'themisdb_github_bridge_sync_order_tickets', '1' ) === '1';
        $sync_support     = get_option( 'themisdb_github_bridge_sync_support_tickets', '1' ) === '1';
        $token_set        = '' !== trim( (string) get_option( 'themisdb_github_bridge_token', '' ) );

        $settings_url = admin_url( 'options-general.php?page=themisdb-github-bridge' );
        ?>
        <div class="themisdb-widget-github-bridge">
            <?php if ( ! $enabled ) : ?>
            <div style="background:#fcf9e8;border-left:4px solid #dba617;padding:6px 10px;margin-bottom:8px;">
                <strong style="color:#dba617;"><?php esc_html_e( 'Bridge ist deaktiviert', 'themisdb-github-bridge' ); ?></strong>
            </div>
            <?php endif; ?>
            <table class="widefat fixed" style="border:none;box-shadow:none;">
                <tbody>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Status', 'themisdb-github-bridge' ); ?></td>
                        <td>
                        <?php if ( $enabled ) : ?>
                            <span style="color:#46b450;">&#10003; <?php esc_html_e( 'Aktiv', 'themisdb-github-bridge' ); ?></span>
                        <?php else : ?>
                            <span style="color:#999;"><?php esc_html_e( 'Inaktiv', 'themisdb-github-bridge' ); ?></span>
                        <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Repository', 'themisdb-github-bridge' ); ?></td>
                        <td><?php echo '' !== $repo ? '<code>' . esc_html( $repo ) . '</code>' : '<span style="color:#999;">—</span>'; ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'API-Token', 'themisdb-github-bridge' ); ?></td>
                        <td><?php echo $token_set ? esc_html__( 'Gesetzt', 'themisdb-github-bridge' ) : '<span style="color:#dc3232;">' . esc_html__( 'Nicht gesetzt', 'themisdb-github-bridge' ) . '</span>'; ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Sync: Bestellungen', 'themisdb-github-bridge' ); ?></td>
                        <td><?php echo $sync_orders ? esc_html__( 'Ja', 'themisdb-github-bridge' ) : esc_html__( 'Nein', 'themisdb-github-bridge' ); ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Sync: Support', 'themisdb-github-bridge' ); ?></td>
                        <td><?php echo $sync_support ? esc_html__( 'Ja', 'themisdb-github-bridge' ) : esc_html__( 'Nein', 'themisdb-github-bridge' ); ?></td>
                    </tr>
                </tbody>
            </table>
            <p style="margin-top:8px;">
                <a href="<?php echo esc_url( $settings_url ); ?>" class="button button-small">
                    <?php esc_html_e( 'Einstellungen', 'themisdb-github-bridge' ); ?>
                </a>
            </p>
        </div>
        <?php
    }
}
