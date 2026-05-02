<?php
/**
 * ThemisDB Engagement Tracker – Dashboard Widget
 *
 * Zeigt aggregierte Engagement-Signale (Views, Reads, Podcast-Plays,
 * Completions) für alle veröffentlichten Beiträge im Überblick.
 *
 * @package ThemisDB_Engagement_Tracker
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TDET_Dashboard_Widget {

    public static function init() {
        add_action( 'wp_dashboard_setup', array( __CLASS__, 'register_widget' ) );
    }

    public static function register_widget() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        wp_add_dashboard_widget(
            'themisdb_engagement_tracker_status',
            __( 'ThemisDB Engagement', 'themisdb-engagement-tracker' ),
            array( __CLASS__, 'render' )
        );
    }

    public static function render() {
        global $wpdb;

        // Aggregate signals from post meta using transient cache.
        $data = get_transient( 'tdet_dashboard_totals' );
        if ( false === $data ) {
            $meta_keys = array(
                'views'       => '_themisdb_views',
                'reads'       => '_themisdb_read_completions',
                'plays'       => '_themisdb_podcast_plays',
                'completions' => '_themisdb_podcast_completions',
            );
            $data = array(
                'views'       => 0,
                'reads'       => 0,
                'plays'       => 0,
                'completions' => 0,
                'posts'       => 0,
            );
            foreach ( $meta_keys as $key => $meta_key ) {
                $sum = (int) $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COALESCE(SUM(CAST(meta_value AS UNSIGNED)),0) FROM {$wpdb->postmeta} WHERE meta_key = %s",
                        $meta_key
                    )
                );
                $data[ $key ] = $sum;
            }
            $data['posts'] = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = %s",
                    '_themisdb_views'
                )
            );
            set_transient( 'tdet_dashboard_totals', $data, 5 * MINUTE_IN_SECONDS );
        }

        $admin_url = admin_url( 'edit.php?orderby=tdet_engagement' );
        ?>
        <div class="themisdb-widget-engagement">
            <table class="widefat fixed" style="border:none;box-shadow:none;">
                <tbody>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Beiträge mit Tracking', 'themisdb-engagement-tracker' ); ?></td>
                        <td><strong><?php echo esc_html( number_format_i18n( $data['posts'] ) ); ?></strong></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Views gesamt', 'themisdb-engagement-tracker' ); ?></td>
                        <td><?php echo esc_html( number_format_i18n( $data['views'] ) ); ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Leseabschlüsse', 'themisdb-engagement-tracker' ); ?></td>
                        <td><?php echo esc_html( number_format_i18n( $data['reads'] ) ); ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Podcast-Plays', 'themisdb-engagement-tracker' ); ?></td>
                        <td><?php echo esc_html( number_format_i18n( $data['plays'] ) ); ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Podcast-Abschlüsse', 'themisdb-engagement-tracker' ); ?></td>
                        <td><?php echo esc_html( number_format_i18n( $data['completions'] ) ); ?></td>
                    </tr>
                </tbody>
            </table>
            <p style="margin-top:8px;">
                <a href="<?php echo esc_url( $admin_url ); ?>" class="button button-small">
                    <?php esc_html_e( 'Beiträge nach Engagement', 'themisdb-engagement-tracker' ); ?>
                </a>
            </p>
        </div>
        <?php
    }
}
