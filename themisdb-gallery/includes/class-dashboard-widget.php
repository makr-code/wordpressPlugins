<?php
/**
 * ThemisDB Gallery – Dashboard Widget
 *
 * Zeigt Provider-Konfiguration und API-Schlüssel-Status auf dem
 * WordPress-Admin-Dashboard.
 *
 * @package ThemisDB_Gallery
 * @since   1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ThemisDB_Gallery_Dashboard_Widget {

    public static function init() {
        add_action( 'wp_dashboard_setup', array( __CLASS__, 'register_widget' ) );
    }

    public static function register_widget() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        wp_add_dashboard_widget(
            'themisdb_gallery_status',
            __( 'ThemisDB Gallery', 'themisdb-gallery' ),
            array( __CLASS__, 'render' )
        );
    }

    public static function render() {
        $default_provider = get_option( 'themisdb_gallery_default_provider', 'unsplash' );
        $images_per_page  = (int) get_option( 'themisdb_gallery_images_per_page', 20 );
        $cache_duration   = (int) get_option( 'themisdb_gallery_cache_duration', 3600 );

        $providers = array(
            'Unsplash' => '' !== trim( (string) get_option( 'themisdb_gallery_unsplash_key', '' ) ),
            'Pexels'   => '' !== trim( (string) get_option( 'themisdb_gallery_pexels_key', '' ) ),
            'Pixabay'  => '' !== trim( (string) get_option( 'themisdb_gallery_pixabay_key', '' ) ),
            'OpenAI'   => '' !== trim( (string) get_option( 'themisdb_gallery_openai_key', '' ) ),
        );
        $configured = array_keys( array_filter( $providers ) );

        $settings_url = admin_url( 'options-general.php?page=themisdb-gallery' );
        ?>
        <div class="themisdb-widget-gallery">
            <table class="widefat fixed" style="border:none;box-shadow:none;">
                <tbody>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Standard-Provider', 'themisdb-gallery' ); ?></td>
                        <td><?php echo esc_html( $default_provider ); ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Konfigurierte APIs', 'themisdb-gallery' ); ?></td>
                        <td>
                        <?php if ( ! empty( $configured ) ) : ?>
                            <?php echo esc_html( implode( ', ', $configured ) ); ?>
                        <?php else : ?>
                            <span style="color:#dc3232;"><?php esc_html_e( 'Keine API-Schlüssel', 'themisdb-gallery' ); ?></span>
                        <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Bilder pro Seite', 'themisdb-gallery' ); ?></td>
                        <td><?php echo esc_html( $images_per_page ); ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Cache-Dauer', 'themisdb-gallery' ); ?></td>
                        <td><?php echo esc_html( $cache_duration / 60 ) . ' min'; ?></td>
                    </tr>
                </tbody>
            </table>
            <p style="margin-top:8px;">
                <a href="<?php echo esc_url( $settings_url ); ?>" class="button button-small">
                    <?php esc_html_e( 'Einstellungen', 'themisdb-gallery' ); ?>
                </a>
            </p>
        </div>
        <?php
    }
}
