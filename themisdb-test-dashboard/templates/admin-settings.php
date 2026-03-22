<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB - Hybrid Database System                                   ║
╠═════════════════════════════════════════════════════════════════════╣
  File:            admin-settings.php                                 ║
  Version:         0.0.2                                              ║
  Last Modified:   2026-03-09 04:08:22                                ║
  Author:          unknown                                            ║
╠═════════════════════════════════════════════════════════════════════╣
  Quality Metrics:                                                    ║
    • Maturity Level:  🟢 PRODUCTION-READY                             ║
    • Quality Score:   100.0/100                                      ║
    • Total Lines:     278                                            ║
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
 * Admin Settings Page for ThemisDB Test Dashboard
 * Tab-based layout: Einstellungen | Cache | Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$repo           = get_option( 'themisdb_test_dashboard_repo', 'makr-code/wordpressPlugins' );
$github_token   = get_option( 'themisdb_test_dashboard_github_token', '' );
$default_view   = get_option( 'themisdb_test_dashboard_default_view', 'overview' );
$default_period = get_option( 'themisdb_test_dashboard_default_period', 30 );

$_ttd_page = 'themisdb-test-dashboard';
$_ttd_tab  = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'settings';
if ( ! in_array( $_ttd_tab, array( 'settings', 'cache', 'shortcodes' ), true ) ) {
    $_ttd_tab = 'settings';
}
$_ttd_url = function ( $tab ) use ( $_ttd_page ) {
    return esc_url( admin_url( 'options-general.php?page=' . $_ttd_page . '&tab=' . $tab ) );
};
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php _e( 'Test Dashboard', 'themisdb-test-dashboard' ); ?>
        <a href="<?php echo $_ttd_url( 'shortcodes' ); ?>" class="page-title-action">
            <?php _e( 'Shortcodes', 'themisdb-test-dashboard' ); ?>
        </a>
        <a href="<?php echo $_ttd_url( 'cache' ); ?>" class="page-title-action">
            <?php _e( 'Cache', 'themisdb-test-dashboard' ); ?>
        </a>
    </h1>
    <hr class="wp-header-end">

    <nav class="nav-tab-wrapper wp-clearfix">
        <a href="<?php echo $_ttd_url( 'settings' ); ?>"
           class="nav-tab <?php echo $_ttd_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
            <?php _e( 'Einstellungen', 'themisdb-test-dashboard' ); ?>
        </a>
        <a href="<?php echo $_ttd_url( 'cache' ); ?>"
           class="nav-tab <?php echo $_ttd_tab === 'cache' ? 'nav-tab-active' : ''; ?>">
            <?php _e( 'Cache', 'themisdb-test-dashboard' ); ?>
        </a>
        <a href="<?php echo $_ttd_url( 'shortcodes' ); ?>"
           class="nav-tab <?php echo $_ttd_tab === 'shortcodes' ? 'nav-tab-active' : ''; ?>">
            <?php _e( 'Shortcodes', 'themisdb-test-dashboard' ); ?>
        </a>
    </nav>

    <div class="themisdb-tab-content">

    <?php if ( $_ttd_tab === 'settings' ) : ?>

    <?php if ( isset( $_POST['themisdb_test_dashboard_save'] ) ) :
        check_admin_referer( 'themisdb_test_dashboard_settings' );
        update_option( 'themisdb_test_dashboard_repo', sanitize_text_field( $_POST['repo'] ) );
        update_option( 'themisdb_test_dashboard_github_token', sanitize_text_field( $_POST['github_token'] ) );
        update_option( 'themisdb_test_dashboard_default_view', sanitize_text_field( $_POST['default_view'] ) );
        update_option( 'themisdb_test_dashboard_default_period', intval( $_POST['default_period'] ) );
        // Refresh vars after save
        $repo           = get_option( 'themisdb_test_dashboard_repo' );
        $github_token   = get_option( 'themisdb_test_dashboard_github_token' );
        $default_view   = get_option( 'themisdb_test_dashboard_default_view' );
        $default_period = get_option( 'themisdb_test_dashboard_default_period' );
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Einstellungen gespeichert.', 'themisdb-test-dashboard' ) . '</p></div>';
    endif; ?>

    <div class="themisdb-admin-modules">
        <div class="card">
            <h2><?php _e( 'Schnellaktionen', 'themisdb-test-dashboard' ); ?></h2>
            <p><?php _e( 'Wechseln Sie direkt zur Cache-Verwaltung oder zur Shortcode-Referenz des Dashboards.', 'themisdb-test-dashboard' ); ?></p>
            <p>
                <a href="<?php echo $_ttd_url( 'cache' ); ?>" class="button button-secondary"><?php _e( 'Cache', 'themisdb-test-dashboard' ); ?></a>
                <a href="<?php echo $_ttd_url( 'shortcodes' ); ?>" class="button button-secondary"><?php _e( 'Shortcodes', 'themisdb-test-dashboard' ); ?></a>
            </p>
        </div>
        <div class="card">
            <h2><?php _e( 'Aktive Defaults', 'themisdb-test-dashboard' ); ?></h2>
            <table class="widefat striped">
                <tbody>
                    <tr><th><?php _e( 'Repository', 'themisdb-test-dashboard' ); ?></th><td><code><?php echo esc_html( $repo ); ?></code></td></tr>
                    <tr><th><?php _e( 'Ansicht', 'themisdb-test-dashboard' ); ?></th><td><code><?php echo esc_html( $default_view ); ?></code></td></tr>
                    <tr><th><?php _e( 'Zeitraum', 'themisdb-test-dashboard' ); ?></th><td><?php echo esc_html( $default_period ); ?> <?php _e( 'Tage', 'themisdb-test-dashboard' ); ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <form method="post" action="">
        <?php wp_nonce_field( 'themisdb_test_dashboard_settings' ); ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="repo"><?php _e( 'Repository', 'themisdb-test-dashboard' ); ?></label>
                </th>
                <td>
                    <input type="text" id="repo" name="repo"
                           value="<?php echo esc_attr( $repo ); ?>"
                           class="regular-text" placeholder="owner/repository">
                    <p class="description"><?php _e( 'GitHub-Repository im Format "owner/repo"', 'themisdb-test-dashboard' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="github_token"><?php _e( 'GitHub Token (Optional)', 'themisdb-test-dashboard' ); ?></label>
                </th>
                <td>
                    <input type="password" id="github_token" name="github_token"
                           value="<?php echo esc_attr( $github_token ); ?>"
                           class="regular-text" placeholder="ghp_xxxxxxxxxxxx">
                    <p class="description">
                        <?php _e( 'Persönlicher GitHub-Token für private Repositories und höhere API-Rate-Limits.', 'themisdb-test-dashboard' ); ?><br>
                        <strong><?php _e( 'Benötigte Berechtigungen:', 'themisdb-test-dashboard' ); ?></strong> <code>repo</code> <?php _e( 'oder', 'themisdb-test-dashboard' ); ?> <code>public_repo</code><br>
                        <a href="https://github.com/settings/tokens/new" target="_blank" rel="noopener"><?php _e( 'Token erstellen', 'themisdb-test-dashboard' ); ?> &rarr;</a>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="default_view"><?php _e( 'Standard-Ansicht', 'themisdb-test-dashboard' ); ?></label>
                </th>
                <td>
                    <select id="default_view" name="default_view">
                        <option value="overview"  <?php selected( $default_view, 'overview' ); ?>><?php _e( 'Übersicht', 'themisdb-test-dashboard' ); ?></option>
                        <option value="coverage"  <?php selected( $default_view, 'coverage' ); ?>><?php _e( 'Test-Abdeckung', 'themisdb-test-dashboard' ); ?></option>
                        <option value="pipeline"  <?php selected( $default_view, 'pipeline' ); ?>><?php _e( 'CI/CD-Pipeline', 'themisdb-test-dashboard' ); ?></option>
                        <option value="quality"   <?php selected( $default_view, 'quality' ); ?>><?php _e( 'Quality Gates', 'themisdb-test-dashboard' ); ?></option>
                    </select>
                    <p class="description"><?php _e( 'Standard-Ansicht beim Laden des Dashboards', 'themisdb-test-dashboard' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="default_period"><?php _e( 'Standard-Zeitraum', 'themisdb-test-dashboard' ); ?></label>
                </th>
                <td>
                    <select id="default_period" name="default_period">
                        <option value="7"   <?php selected( $default_period, 7 ); ?>><?php _e( 'Letzte 7 Tage', 'themisdb-test-dashboard' ); ?></option>
                        <option value="14"  <?php selected( $default_period, 14 ); ?>><?php _e( 'Letzte 14 Tage', 'themisdb-test-dashboard' ); ?></option>
                        <option value="30"  <?php selected( $default_period, 30 ); ?>><?php _e( 'Letzte 30 Tage', 'themisdb-test-dashboard' ); ?></option>
                        <option value="90"  <?php selected( $default_period, 90 ); ?>><?php _e( 'Letzte 90 Tage', 'themisdb-test-dashboard' ); ?></option>
                    </select>
                    <p class="description"><?php _e( 'Standard-Zeitraum für Metriken', 'themisdb-test-dashboard' ); ?></p>
                </td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" name="themisdb_test_dashboard_save" class="button button-primary"
                   value="<?php esc_attr_e( 'Einstellungen speichern', 'themisdb-test-dashboard' ); ?>">
        </p>
    </form>

    <?php elseif ( $_ttd_tab === 'cache' ) : ?>

    <h2><?php _e( 'Cache-Verwaltung', 'themisdb-test-dashboard' ); ?></h2>
    <p><?php _e( 'Dashboard-Daten werden 1 Stunde gecacht, um API-Aufrufe zu reduzieren und die Performance zu verbessern.', 'themisdb-test-dashboard' ); ?></p>

    <?php if ( isset( $_POST['themisdb_test_dashboard_clear_cache'] ) ) :
        check_admin_referer( 'themisdb_test_dashboard_clear_cache' );
        global $wpdb;
        $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            $wpdb->esc_like( '_transient_themisdb_test_dashboard_' ) . '%'
        ) );
        $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            $wpdb->esc_like( '_transient_timeout_themisdb_test_dashboard_' ) . '%'
        ) );
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Cache geleert.', 'themisdb-test-dashboard' ) . '</p></div>';
    endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field( 'themisdb_test_dashboard_clear_cache' ); ?>
        <p class="submit">
            <input type="submit" name="themisdb_test_dashboard_clear_cache" class="button button-secondary"
                   value="<?php esc_attr_e( 'Cache leeren', 'themisdb-test-dashboard' ); ?>"
                   onclick="return confirm('<?php esc_attr_e( 'Cache wirklich leeren?', 'themisdb-test-dashboard' ); ?>');">
        </p>
    </form>

    <?php elseif ( $_ttd_tab === 'shortcodes' ) : ?>

    <h2><?php _e( 'Shortcode-Verwendung', 'themisdb-test-dashboard' ); ?></h2>
    <table class="widefat striped" style="max-width:900px;">
        <thead>
            <tr>
                <th><?php _e( 'Shortcode', 'themisdb-test-dashboard' ); ?></th>
                <th><?php _e( 'Beschreibung', 'themisdb-test-dashboard' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>[themisdb_test_dashboard]</code></td>
                <td><?php _e( 'Standard-Dashboard', 'themisdb-test-dashboard' ); ?></td>
            </tr>
            <tr>
                <td><code>[themisdb_test_dashboard view="coverage" period="30"]</code></td>
                <td><?php _e( 'Bestimmte Ansicht und Zeitraum', 'themisdb-test-dashboard' ); ?></td>
            </tr>
            <tr>
                <td><code>[themisdb_test_dashboard view="pipeline" repo="makr-code/wordpressPlugins"]</code></td>
                <td><?php _e( 'Bestimmtes Repository anzeigen', 'themisdb-test-dashboard' ); ?></td>
            </tr>
            <tr>
                <td><code>[themisdb_test_dashboard height="800px"]</code></td>
                <td><?php _e( 'Benutzerdefinierte Mindesthöhe', 'themisdb-test-dashboard' ); ?></td>
            </tr>
        </tbody>
    </table>

    <h2 style="margin-top:24px;"><?php _e( 'Parameter-Übersicht', 'themisdb-test-dashboard' ); ?></h2>
    <table class="widefat" style="max-width:900px;">
        <thead>
            <tr>
                <th><?php _e( 'Parameter', 'themisdb-test-dashboard' ); ?></th>
                <th><?php _e( 'Beschreibung', 'themisdb-test-dashboard' ); ?></th>
                <th><?php _e( 'Werte', 'themisdb-test-dashboard' ); ?></th>
                <th><?php _e( 'Standard', 'themisdb-test-dashboard' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr><td><code>view</code></td><td><?php _e( 'Anfangsansicht', 'themisdb-test-dashboard' ); ?></td><td>overview, coverage, pipeline, quality</td><td>overview</td></tr>
            <tr><td><code>period</code></td><td><?php _e( 'Zeitraum (Tage)', 'themisdb-test-dashboard' ); ?></td><td>7, 14, 30, 90</td><td>30</td></tr>
            <tr><td><code>repo</code></td><td><?php _e( 'GitHub-Repository', 'themisdb-test-dashboard' ); ?></td><td>owner/repo</td><td><?php echo esc_html( $repo ); ?></td></tr>
            <tr><td><code>height</code></td><td><?php _e( 'Mindesthöhe', 'themisdb-test-dashboard' ); ?></td><td><?php _e( 'CSS-Wert', 'themisdb-test-dashboard' ); ?></td><td>600px</td></tr>
        </tbody>
    </table>

    <?php endif; ?>

    </div><!-- .themisdb-tab-content -->
</div><!-- .wrap -->

<style>
.themisdb-admin-modules {
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));
    gap:16px;
    margin:0 0 20px;
}
.themisdb-admin-modules .card { margin:0; max-width:none; }
.themisdb-tab-content {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-top: none;
    padding: 20px 24px;
}
.themisdb-tab-content > h2:first-child,
.themisdb-tab-content > h3:first-child,
.themisdb-tab-content > p:first-child { margin-top:0; }
.themisdb-tab-content .widefat th { width:auto; }
.themisdb-tab-content table.widefat code {
    background: #f6f7f7;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 12px;
}
</style>

