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
    • Total Lines:     229                                            ║
    • Open Issues:     TODOs: 0, Stubs: 0                             ║
╠═════════════════════════════════════════════════════════════════════╣
  Revision History:                                                   ║
    • 2a1fb0423  2026-03-03  Merge branch 'develop' into copilot/audit-src-module-docu... ║
    • 9d3ecaa0e  2026-02-28  Add ThemisDB Wiki Integration plugin with documentation i... ║
╠═════════════════════════════════════════════════════════════════════╣
  Status: ✅ Production Ready                                          ║
╚═════════════════════════════════════════════════════════════════════╝
 */

<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$_ttco_page = 'themisdb-tco-calculator';
$_ttco_tab  = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'settings';
if ( ! in_array( $_ttco_tab, array( 'settings', 'shortcodes', 'updates' ), true ) ) {
    $_ttco_tab = 'settings';
}
$_ttco_url = function ( $tab ) use ( $_ttco_page ) {
    return esc_url( admin_url( 'options-general.php?page=' . $_ttco_page . '&tab=' . $tab ) );
};

if ( isset( $_GET['settings-updated'] ) ) {
    add_settings_error(
        'themisdb_tco_messages',
        'themisdb_tco_message',
        __( 'Einstellungen gespeichert', 'themisdb-tco-calculator' ),
        'updated'
    );
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php echo esc_html( get_admin_page_title() ); ?>
        <a href="<?php echo $_ttco_url( 'shortcodes' ); ?>" class="page-title-action">
            <?php _e( 'Shortcodes', 'themisdb-tco-calculator' ); ?>
        </a>
    </h1>
    <hr class="wp-header-end">

    <?php settings_errors( 'themisdb_tco_messages' ); ?>

    <nav class="nav-tab-wrapper wp-clearfix">
        <a href="<?php echo $_ttco_url( 'settings' ); ?>"
           class="nav-tab <?php echo $_ttco_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
            <?php _e( 'Einstellungen', 'themisdb-tco-calculator' ); ?>
        </a>
        <a href="<?php echo $_ttco_url( 'shortcodes' ); ?>"
           class="nav-tab <?php echo $_ttco_tab === 'shortcodes' ? 'nav-tab-active' : ''; ?>">
            <?php _e( 'Shortcodes', 'themisdb-tco-calculator' ); ?>
        </a>
        <a href="<?php echo $_ttco_url( 'updates' ); ?>"
           class="nav-tab <?php echo $_ttco_tab === 'updates' ? 'nav-tab-active' : ''; ?>">
            <?php _e( 'Updates &amp; Info', 'themisdb-tco-calculator' ); ?>
        </a>
    </nav>

    <div class="themisdb-tab-content">

    <?php if ( $_ttco_tab === 'settings' ) : ?>

    <div class="themisdb-admin-modules">
        <div class="card">
            <h2><?php _e( 'Schnellaktionen', 'themisdb-tco-calculator' ); ?></h2>
            <p><?php _e( 'Öffnen Sie direkt die Shortcode-Referenz oder den Update-Bereich des Rechners.', 'themisdb-tco-calculator' ); ?></p>
            <p>
                <a href="<?php echo $_ttco_url( 'shortcodes' ); ?>" class="button button-secondary"><?php _e( 'Shortcodes', 'themisdb-tco-calculator' ); ?></a>
                <a href="<?php echo $_ttco_url( 'updates' ); ?>" class="button button-secondary"><?php _e( 'Updates & Info', 'themisdb-tco-calculator' ); ?></a>
            </p>
        </div>
        <div class="card">
            <h2><?php _e( 'Aktive Defaults', 'themisdb-tco-calculator' ); ?></h2>
            <table class="widefat striped">
                <tbody>
                    <tr><th><?php _e( 'Anfragen/Tag', 'themisdb-tco-calculator' ); ?></th><td><?php echo esc_html( get_option( 'themisdb_tco_default_requests_per_day', 1000000 ) ); ?></td></tr>
                    <tr><th><?php _e( 'Datengröße', 'themisdb-tco-calculator' ); ?></th><td><?php echo esc_html( get_option( 'themisdb_tco_default_data_size', 500 ) ); ?> GB</td></tr>
                    <tr><th><?php _e( 'AI Features', 'themisdb-tco-calculator' ); ?></th><td><?php echo get_option( 'themisdb_tco_enable_ai_features', true ) ? esc_html__( 'Aktiv', 'themisdb-tco-calculator' ) : esc_html__( 'Deaktiviert', 'themisdb-tco-calculator' ); ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <form action="options.php" method="post">
        <?php settings_fields( 'themisdb_tco_options' ); ?>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="themisdb_tco_enable_ai_features">
                            <?php _e( 'AI Features aktivieren', 'themisdb-tco-calculator' ); ?>
                        </label>
                    </th>
                    <td>
                        <input type="checkbox"
                               id="themisdb_tco_enable_ai_features"
                               name="themisdb_tco_enable_ai_features"
                               value="1"
                               <?php checked( 1, get_option( 'themisdb_tco_enable_ai_features', true ) ); ?>>
                        <p class="description"><?php _e( 'Ermöglicht AI/LLM Features im Rechner', 'themisdb-tco-calculator' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="themisdb_tco_default_requests_per_day">
                            <?php _e( 'Standard Anfragen/Tag', 'themisdb-tco-calculator' ); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number"
                               id="themisdb_tco_default_requests_per_day"
                               name="themisdb_tco_default_requests_per_day"
                               value="<?php echo esc_attr( get_option( 'themisdb_tco_default_requests_per_day', 1000000 ) ); ?>"
                               class="regular-text" min="1000" step="10000">
                        <p class="description"><?php _e( 'Standardwert für durchschnittliche Anfragen pro Tag', 'themisdb-tco-calculator' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="themisdb_tco_default_data_size">
                            <?php _e( 'Standard Datengröße (GB)', 'themisdb-tco-calculator' ); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number"
                               id="themisdb_tco_default_data_size"
                               name="themisdb_tco_default_data_size"
                               value="<?php echo esc_attr( get_option( 'themisdb_tco_default_data_size', 500 ) ); ?>"
                               class="regular-text" min="1" step="10">
                        <p class="description"><?php _e( 'Standardwert für Datenmenge in Gigabyte', 'themisdb-tco-calculator' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="themisdb_tco_default_peak_load">
                            <?php _e( 'Standard Spitzenlast-Faktor', 'themisdb-tco-calculator' ); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number"
                               id="themisdb_tco_default_peak_load"
                               name="themisdb_tco_default_peak_load"
                               value="<?php echo esc_attr( get_option( 'themisdb_tco_default_peak_load', 3 ) ); ?>"
                               class="regular-text" min="1" max="10" step="0.5">
                        <p class="description"><?php _e( 'Verhältnis Spitzenlast zu Durchschnittslast (1–10×)', 'themisdb-tco-calculator' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="themisdb_tco_default_availability">
                            <?php _e( 'Standard Verfügbarkeit (%)', 'themisdb-tco-calculator' ); ?>
                        </label>
                    </th>
                    <td>
                        <select id="themisdb_tco_default_availability"
                                name="themisdb_tco_default_availability"
                                class="regular-text">
                            <option value="99"     <?php selected( get_option( 'themisdb_tco_default_availability', 99.999 ), 99 ); ?>>99% (Standard)</option>
                            <option value="99.9"   <?php selected( get_option( 'themisdb_tco_default_availability', 99.999 ), 99.9 ); ?>>99,9% (High)</option>
                            <option value="99.99"  <?php selected( get_option( 'themisdb_tco_default_availability', 99.999 ), 99.99 ); ?>>99,99% (Very High)</option>
                            <option value="99.999" <?php selected( get_option( 'themisdb_tco_default_availability', 99.999 ), 99.999 ); ?>>99,999% (Mission Critical)</option>
                        </select>
                        <p class="description"><?php _e( 'Standardwert für Verfügbarkeitsanforderung', 'themisdb-tco-calculator' ); ?></p>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php submit_button( __( 'Einstellungen speichern', 'themisdb-tco-calculator' ) ); ?>
    </form>

    <?php elseif ( $_ttco_tab === 'shortcodes' ) : ?>

    <h2><?php _e( 'Shortcode-Verwendung', 'themisdb-tco-calculator' ); ?></h2>
    <table class="widefat striped" style="max-width:860px;">
        <thead>
            <tr>
                <th><?php _e( 'Shortcode', 'themisdb-tco-calculator' ); ?></th>
                <th><?php _e( 'Beschreibung', 'themisdb-tco-calculator' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>[themisdb_tco_calculator]</code></td>
                <td><?php _e( 'Vollständiger TCO-Rechner', 'themisdb-tco-calculator' ); ?></td>
            </tr>
            <tr>
                <td><code>[themisdb_tco_calculator show_intro="no"]</code></td>
                <td><?php _e( 'Einführungsbereich ausblenden', 'themisdb-tco-calculator' ); ?></td>
            </tr>
            <tr>
                <td><code>[themisdb_tco_calculator title="Mein TCO-Rechner"]</code></td>
                <td><?php _e( 'Eigener Titel', 'themisdb-tco-calculator' ); ?></td>
            </tr>
            <tr>
                <td><code>[themisdb_tco_workload]</code></td>
                <td><?php _e( 'Nur Workload-Sektion', 'themisdb-tco-calculator' ); ?></td>
            </tr>
            <tr>
                <td><code>[themisdb_tco_infrastructure]</code></td>
                <td><?php _e( 'Nur Infrastruktur-Sektion', 'themisdb-tco-calculator' ); ?></td>
            </tr>
            <tr>
                <td><code>[themisdb_tco_results]</code></td>
                <td><?php _e( 'Nur Ergebnis-Sektion', 'themisdb-tco-calculator' ); ?></td>
            </tr>
        </tbody>
    </table>

    <?php elseif ( $_ttco_tab === 'updates' ) : ?>

    <h2><?php _e( 'Plugin-Updates', 'themisdb-tco-calculator' ); ?></h2>
    <div class="notice notice-info inline">
        <p>
            <strong><?php _e( 'Automatische Updates von GitHub', 'themisdb-tco-calculator' ); ?></strong><br>
            <?php _e( 'Dieses Plugin unterstützt automatische Updates direkt von GitHub. Neue Versionen werden automatisch unter Dashboard → Aktualisierungen angezeigt.', 'themisdb-tco-calculator' ); ?>
        </p>
        <p>
            <strong><?php _e( 'Installierte Version:', 'themisdb-tco-calculator' ); ?></strong> <?php echo esc_html( THEMISDB_TCO_VERSION ); ?><br>
            <strong>GitHub:</strong>
            <a href="https://github.com/<?php echo esc_attr( THEMISDB_TCO_GITHUB_REPO ); ?>" target="_blank" rel="noopener">
                <?php echo esc_html( THEMISDB_TCO_GITHUB_REPO ); ?>
            </a>
        </p>
        <?php
        $latest = get_transient( 'themisdb_tco_github_release' );
        if ( ! $latest ) {
            echo '<p><em>' . esc_html__( 'Prüfen Sie unter Dashboard → Aktualisierungen auf neue Versionen.', 'themisdb-tco-calculator' ) . '</em></p>';
        } else {
            $lv = isset( $latest->tag_name ) ? $latest->tag_name : 'Unbekannt';
            if ( version_compare( THEMISDB_TCO_VERSION, $lv, '<' ) ) {
                echo '<p style="color:#d63638;"><strong>' . sprintf( esc_html__( 'Eine neue Version (%s) ist verfügbar!', 'themisdb-tco-calculator' ), esc_html( $lv ) ) . '</strong></p>';
            } else {
                echo '<p style="color:#00a32a;"><strong>' . esc_html__( 'Plugin ist auf dem neuesten Stand.', 'themisdb-tco-calculator' ) . '</strong></p>';
            }
        }
        ?>
    </div>

    <h2 style="margin-top:24px;"><?php _e( 'Features', 'themisdb-tco-calculator' ); ?></h2>
    <ul>
        <li><?php _e( 'Interaktive TCO-Berechnung für ThemisDB vs. Cloud-Hyperscaler', 'themisdb-tco-calculator' ); ?></li>
        <li><?php _e( 'Umfassende Kostenanalyse (Infrastruktur, Personal, Lizenzen, Betrieb)', 'themisdb-tco-calculator' ); ?></li>
        <li><?php _e( 'Visuelle Darstellung mit Chart.js', 'themisdb-tco-calculator' ); ?></li>
        <li><?php _e( 'Export-Funktionen (PDF, CSV)', 'themisdb-tco-calculator' ); ?></li>
        <li><?php _e( 'Responsive Design', 'themisdb-tco-calculator' ); ?></li>
        <li><?php _e( 'WordPress-Integration via Shortcode', 'themisdb-tco-calculator' ); ?></li>
    </ul>

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

