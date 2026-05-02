<?php
/**
 * ThemisDB Compendium Downloads – Dashboard Widget
 *
 * @package ThemisDB_Compendium
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ThemisDB_Compendium_Dashboard_Widget {

    public static function init() {
        add_action( 'wp_dashboard_setup', array( __CLASS__, 'register_widget' ) );
    }

    public static function register_widget() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        wp_add_dashboard_widget(
            'themisdb_compendium_status',
            __( 'ThemisDB Compendium Downloads', 'themisdb-compendium-downloads' ),
            array( __CLASS__, 'render' )
        );
    }

    public static function render() {
        $repo        = get_option( 'themisdb_compendium_github_repo', 'makr-code/wordpressPlugins' );
        $search_term = get_option( 'themisdb_compendium_search_term', 'kompendium' );
        $cached      = get_transient( 'themisdb_compendium_release_data' );

        $asset_count  = 0;
        $latest_ver   = '—';
        if ( is_array( $cached ) ) {
            if ( isset( $cached['assets'] ) && is_array( $cached['assets'] ) ) {
                $asset_count = count( $cached['assets'] );
            }
            if ( isset( $cached['tag_name'] ) ) {
                $latest_ver = esc_html( $cached['tag_name'] );
            }
        }
        $cache_status = $cached !== false
            ? __( 'Aktiv', 'themisdb-compendium-downloads' )
            : __( 'Leer', 'themisdb-compendium-downloads' );

        $admin_url = admin_url( 'options-general.php?page=themisdb-compendium-downloads' );
        ?>
        <div class="themisdb-widget-compendium">
            <table class="widefat fixed" style="border:none;box-shadow:none;">
                <tbody>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Repository', 'themisdb-compendium-downloads' ); ?></td>
                        <td><code><?php echo esc_html( $repo ); ?></code></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Suchbegriff', 'themisdb-compendium-downloads' ); ?></td>
                        <td><code><?php echo esc_html( $search_term ); ?></code></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Neuestes Release', 'themisdb-compendium-downloads' ); ?></td>
                        <td><?php echo $latest_ver; ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Assets im Cache', 'themisdb-compendium-downloads' ); ?></td>
                        <td><?php echo esc_html( $asset_count ); ?> (<?php echo esc_html( $cache_status ); ?>)</td>
                    </tr>
                </tbody>
            </table>
            <p style="margin-top:8px;">
                <a href="<?php echo esc_url( $admin_url ); ?>" class="button button-small">
                    <?php esc_html_e( 'Einstellungen', 'themisdb-compendium-downloads' ); ?>
                </a>
            </p>
        </div>
        <?php
    }
}
