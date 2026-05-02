<?php
/**
 * ThemisDB Docker Downloads – Dashboard Widget
 *
 * Zeigt einen Kurzüberblick über die Docker-Hub-Konfiguration und
 * den Cache-Status des Docker-Downloads-Plugins.
 *
 * @package ThemisDB_Docker_Downloads
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ThemisDB_Docker_Downloads_Dashboard_Widget {

    public static function init() {
        add_action( 'wp_dashboard_setup', array( __CLASS__, 'register_widget' ) );
    }

    public static function register_widget() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        wp_add_dashboard_widget(
            'themisdb_docker_downloads_status',
            __( 'ThemisDB Docker Downloads', 'themisdb-docker-downloads' ),
            array( __CLASS__, 'render' )
        );
    }

    public static function render() {
        $namespace  = get_option( 'themisdb_docker_namespace', 'themisdb' );
        $repository = get_option( 'themisdb_docker_repository', 'themisdb' );

        $cached_latest = get_transient( 'themisdb_docker_latest_tags' );
        $cached_all    = get_transient( 'themisdb_docker_all_tags' );

        $latest_tag    = ( is_array( $cached_latest ) && ! empty( $cached_latest ) )
            ? esc_html( $cached_latest[0]['name'] ?? '—' )
            : '—';
        $tag_count     = is_array( $cached_all ) ? count( $cached_all ) : 0;
        $cache_status  = $cached_all !== false
            ? __( 'Aktiv', 'themisdb-docker-downloads' )
            : __( 'Leer', 'themisdb-docker-downloads' );

        $admin_url = admin_url( 'options-general.php?page=themisdb-docker-downloads' );
        ?>
        <div class="themisdb-widget-docker-downloads">
            <table class="widefat fixed" style="border:none;box-shadow:none;">
                <tbody>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Docker-Image', 'themisdb-docker-downloads' ); ?></td>
                        <td><code><?php echo esc_html( $namespace . '/' . $repository ); ?></code></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Neuester Tag', 'themisdb-docker-downloads' ); ?></td>
                        <td><?php echo $latest_tag; ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Tags im Cache', 'themisdb-docker-downloads' ); ?></td>
                        <td><?php echo esc_html( $tag_count ); ?> (<?php echo esc_html( $cache_status ); ?>)</td>
                    </tr>
                </tbody>
            </table>
            <p style="margin-top:8px;">
                <a href="<?php echo esc_url( $admin_url ); ?>" class="button button-small">
                    <?php esc_html_e( 'Einstellungen', 'themisdb-docker-downloads' ); ?>
                </a>
            </p>
        </div>
        <?php
    }
}
