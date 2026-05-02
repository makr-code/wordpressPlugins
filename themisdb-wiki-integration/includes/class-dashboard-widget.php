<?php
/**
 * ThemisDB Wiki Integration – Dashboard Widget
 *
 * Zeigt Artikelanzahl, Sync-Konfiguration und Cache-Status auf dem
 * WordPress-Admin-Dashboard.
 *
 * @package ThemisDB_Wiki_Integration
 * @since   1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ThemisDB_Wiki_Dashboard_Widget {

    public static function init() {
        add_action( 'wp_dashboard_setup', array( __CLASS__, 'register_widget' ) );
    }

    public static function register_widget() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        wp_add_dashboard_widget(
            'themisdb_wiki_status',
            __( 'ThemisDB Wiki', 'themisdb-wiki-integration' ),
            array( __CLASS__, 'render' )
        );
    }

    public static function render() {
        $repo      = get_option( 'themisdb_wiki_github_repo', 'makr-code/wordpressPlugins' );
        $branch    = get_option( 'themisdb_wiki_github_branch', 'main' );
        $docs_path = get_option( 'themisdb_wiki_docs_path', 'docs' );
        $auto_sync = get_option( 'themisdb_wiki_auto_sync', 'no' );

        $article_count = wp_count_posts( 'themisdb_wiki' );
        $published     = isset( $article_count->publish ) ? (int) $article_count->publish : 0;
        $draft         = isset( $article_count->draft ) ? (int) $article_count->draft : 0;

        $admin_url = admin_url( 'edit.php?post_type=themisdb_wiki' );
        $settings_url = admin_url( 'options-general.php?page=themisdb-wiki-integration' );
        ?>
        <div class="themisdb-widget-wiki">
            <table class="widefat fixed" style="border:none;box-shadow:none;">
                <tbody>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Artikel veröffentlicht', 'themisdb-wiki-integration' ); ?></td>
                        <td><strong><?php echo esc_html( $published ); ?></strong></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Entwürfe', 'themisdb-wiki-integration' ); ?></td>
                        <td><?php echo esc_html( $draft ); ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Repository', 'themisdb-wiki-integration' ); ?></td>
                        <td><code><?php echo esc_html( $repo ); ?></code></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Branch / Pfad', 'themisdb-wiki-integration' ); ?></td>
                        <td><?php echo esc_html( $branch . ' / ' . $docs_path ); ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Auto-Sync', 'themisdb-wiki-integration' ); ?></td>
                        <td><?php echo esc_html( 'yes' === $auto_sync ? __( 'Aktiv', 'themisdb-wiki-integration' ) : __( 'Manuell', 'themisdb-wiki-integration' ) ); ?></td>
                    </tr>
                </tbody>
            </table>
            <p style="margin-top:8px;">
                <a href="<?php echo esc_url( $admin_url ); ?>" class="button button-small">
                    <?php esc_html_e( 'Artikel', 'themisdb-wiki-integration' ); ?>
                </a>
                <a href="<?php echo esc_url( $settings_url ); ?>" class="button button-small" style="margin-left:4px;">
                    <?php esc_html_e( 'Einstellungen', 'themisdb-wiki-integration' ); ?>
                </a>
            </p>
        </div>
        <?php
    }
}
