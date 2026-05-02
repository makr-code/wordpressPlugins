<?php
/**
 * ThemisDB Downloads – Dashboard Widget
 *
 * Zeigt auf dem WordPress-Admin-Dashboard einen Kurzüberblick über
 * GitHub-Release-Konfiguration und Cache-Status des Downloads-Plugins.
 *
 * @package ThemisDB_Downloads
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ThemisDB_Downloads_Dashboard_Widget {

    public static function init() {
        add_action( 'wp_dashboard_setup', array( __CLASS__, 'register_widget' ) );
    }

    public static function register_widget() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        wp_add_dashboard_widget(
            'themisdb_downloads_status',
            __( 'ThemisDB Downloads', 'themisdb-downloads' ),
            array( __CLASS__, 'render' )
        );
    }

    public static function render() {
        $repo          = get_option( 'themisdb_github_repo', 'makr-code/wordpressPlugins' );
        $releases_count = (int) get_option( 'themisdb_releases_count', 10 );
        $show_prerelease = (bool) get_option( 'themisdb_show_prerelease', 0 );
        $cache_duration = (int) get_option( 'themisdb_cache_duration', 3600 );

        // Check if cached releases are available.
        $cached = get_transient( 'themisdb_all_releases' );
        $cached_count = is_array( $cached ) ? count( $cached ) : 0;
        $cache_status = $cached !== false
            ? __( 'Aktiv', 'themisdb-downloads' )
            : __( 'Leer', 'themisdb-downloads' );

        $latest = get_transient( 'themisdb_latest_release' );
        $latest_tag = ( is_array( $latest ) && isset( $latest['tag_name'] ) )
            ? esc_html( $latest['tag_name'] )
            : '—';

        $admin_url = admin_url( 'options-general.php?page=themisdb-downloads' );
        ?>
        <div class="themisdb-widget-downloads">
            <table class="widefat fixed" style="border:none;box-shadow:none;">
                <tbody>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Repository', 'themisdb-downloads' ); ?></td>
                        <td><code><?php echo esc_html( $repo ); ?></code></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Neuestes Release', 'themisdb-downloads' ); ?></td>
                        <td><?php echo $latest_tag; ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Releases im Cache', 'themisdb-downloads' ); ?></td>
                        <td><?php echo esc_html( $cached_count ); ?> (<?php echo esc_html( $cache_status ); ?>)</td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Max. Anzahl', 'themisdb-downloads' ); ?></td>
                        <td><?php echo esc_html( $releases_count ); ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Pre-Releases', 'themisdb-downloads' ); ?></td>
                        <td><?php echo $show_prerelease ? esc_html__( 'Aktiv', 'themisdb-downloads' ) : esc_html__( 'Inaktiv', 'themisdb-downloads' ); ?></td>
                    </tr>
                </tbody>
            </table>
            <p style="margin-top:8px;">
                <a href="<?php echo esc_url( $admin_url ); ?>" class="button button-small">
                    <?php esc_html_e( 'Einstellungen', 'themisdb-downloads' ); ?>
                </a>
            </p>
        </div>
        <?php
    }
}
