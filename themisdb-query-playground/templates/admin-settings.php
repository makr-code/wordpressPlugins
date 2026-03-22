<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB - Hybrid Database System                                   ║
╠═════════════════════════════════════════════════════════════════════╣
  File:            admin-settings.php                                 ║
  Version:         0.0.2                                              ║
  Last Modified:   2026-03-09 04:08:20                                ║
  Author:          unknown                                            ║
╠═════════════════════════════════════════════════════════════════════╣
  Quality Metrics:                                                    ║
    • Maturity Level:  🟢 PRODUCTION-READY                             ║
    • Quality Score:   100.0/100                                      ║
    • Total Lines:     142                                            ║
    • Open Issues:     TODOs: 0, Stubs: 0                             ║
╠═════════════════════════════════════════════════════════════════════╣
  Revision History:                                                   ║
    • 2a1fb0423  2026-03-03  Merge branch 'develop' into copilot/audit-src-module-docu... ║
    • 9d3ecaa0e  2026-02-28  Add ThemisDB Wiki Integration plugin with documentation i... ║
╠═════════════════════════════════════════════════════════════════════╣
  Status: ✅ Production Ready                                          ║
╚═════════════════════════════════════════════════════════════════════╝
 */


if (!defined('ABSPATH')) exit;

$_tqp_page = 'themisdb-qp-settings';
$_tqp_tab  = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'connection';
if ( ! in_array( $_tqp_tab, array( 'connection', 'display', 'shortcodes' ), true ) ) {
    $_tqp_tab = 'connection';
}
$_tqp_url = function ( $tab ) use ( $_tqp_page ) {
    return esc_url( admin_url( 'options-general.php?page=' . $_tqp_page . '&tab=' . $tab ) );
};
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php echo esc_html( get_admin_page_title() ); ?>
        <a href="<?php echo $_tqp_url( 'shortcodes' ); ?>" class="page-title-action">
            <?php _e( 'Shortcodes', 'themisdb-query-playground' ); ?>
        </a>
    </h1>
    <hr class="wp-header-end">

    <?php settings_errors( 'themisdb_qp_settings' ); ?>

    <nav class="nav-tab-wrapper wp-clearfix">
        <a href="<?php echo $_tqp_url( 'connection' ); ?>"
           class="nav-tab <?php echo $_tqp_tab === 'connection' ? 'nav-tab-active' : ''; ?>">
            <?php _e( 'Verbindung &amp; Sicherheit', 'themisdb-query-playground' ); ?>
        </a>
        <a href="<?php echo $_tqp_url( 'display' ); ?>"
           class="nav-tab <?php echo $_tqp_tab === 'display' ? 'nav-tab-active' : ''; ?>">
            <?php _e( 'Anzeige', 'themisdb-query-playground' ); ?>
        </a>
        <a href="<?php echo $_tqp_url( 'shortcodes' ); ?>"
           class="nav-tab <?php echo $_tqp_tab === 'shortcodes' ? 'nav-tab-active' : ''; ?>">
            <?php _e( 'Shortcodes', 'themisdb-query-playground' ); ?>
        </a>
    </nav>

    <div class="themisdb-tab-content">

    <?php if ( $_tqp_tab === 'connection' ) : ?>

    <div class="themisdb-admin-modules">
        <div class="card">
            <h2><?php _e( 'Schnellaktionen', 'themisdb-query-playground' ); ?></h2>
            <p><?php _e( 'Öffnen Sie direkt die Anzeigeoptionen oder die Shortcode-Referenz für die Einbettung im Frontend.', 'themisdb-query-playground' ); ?></p>
            <p>
                <a href="<?php echo $_tqp_url( 'display' ); ?>" class="button button-secondary"><?php _e( 'Anzeige konfigurieren', 'themisdb-query-playground' ); ?></a>
                <a href="<?php echo $_tqp_url( 'shortcodes' ); ?>" class="button button-secondary"><?php _e( 'Shortcodes', 'themisdb-query-playground' ); ?></a>
            </p>
        </div>
        <div class="card">
            <h2><?php _e( 'Verbindungsstatus', 'themisdb-query-playground' ); ?></h2>
            <table class="widefat striped">
                <tbody>
                    <tr><th><?php _e( 'Endpunkt', 'themisdb-query-playground' ); ?></th><td><code><?php echo esc_html( get_option( 'themisdb_qp_endpoint', '—' ) ); ?></code></td></tr>
                    <tr><th><?php _e( 'Namespace', 'themisdb-query-playground' ); ?></th><td><code><?php echo esc_html( get_option( 'themisdb_qp_namespace', 'default' ) ); ?></code></td></tr>
                    <tr><th><?php _e( 'Read Only', 'themisdb-query-playground' ); ?></th><td><?php echo get_option( 'themisdb_qp_read_only_mode', 1 ) ? esc_html__( 'Aktiv', 'themisdb-query-playground' ) : esc_html__( 'Deaktiviert', 'themisdb-query-playground' ); ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <form method="post" action="options.php">
        <?php settings_fields( 'themisdb_qp_settings' ); ?>

        <h2><?php _e( 'Verbindungseinstellungen', 'themisdb-query-playground' ); ?></h2>
        <table class="form-table">
            <tr>
                <th><label for="themisdb_qp_endpoint"><?php _e( 'ThemisDB Endpunkt', 'themisdb-query-playground' ); ?></label></th>
                <td>
                    <input type="text" name="themisdb_qp_endpoint" id="themisdb_qp_endpoint"
                           value="<?php echo esc_attr( get_option( 'themisdb_qp_endpoint' ) ); ?>"
                           class="regular-text" placeholder="http://localhost:8080" />
                    <p class="description"><?php _e( 'URL zur ThemisDB-Instanz (z.B. http://themisdb:8080)', 'themisdb-query-playground' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="themisdb_qp_client_path"><?php _e( 'PHP-Client-Pfad', 'themisdb-query-playground' ); ?></label></th>
                <td>
                    <input type="text" name="themisdb_qp_client_path" id="themisdb_qp_client_path"
                           value="<?php echo esc_attr( get_option( 'themisdb_qp_client_path' ) ); ?>"
                           class="regular-text" placeholder="/pfad/zum/ThemisDB/clients/php" />
                    <p class="description"><?php _e( 'Pfad zum ThemisDB-PHP-Client-Verzeichnis', 'themisdb-query-playground' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="themisdb_qp_namespace"><?php _e( 'Namespace', 'themisdb-query-playground' ); ?></label></th>
                <td>
                    <input type="text" name="themisdb_qp_namespace" id="themisdb_qp_namespace"
                           value="<?php echo esc_attr( get_option( 'themisdb_qp_namespace' ) ); ?>"
                           class="regular-text" placeholder="default" />
                </td>
            </tr>
            <tr>
                <th><label for="themisdb_qp_timeout"><?php _e( 'Timeout (Sekunden)', 'themisdb-query-playground' ); ?></label></th>
                <td>
                    <input type="number" name="themisdb_qp_timeout" id="themisdb_qp_timeout"
                           value="<?php echo esc_attr( get_option( 'themisdb_qp_timeout' ) ); ?>"
                           min="5" max="300" class="small-text" />
                </td>
            </tr>
        </table>

        <h2><?php _e( 'Sicherheitseinstellungen', 'themisdb-query-playground' ); ?></h2>
        <table class="form-table">
            <tr>
                <th><label for="themisdb_qp_enable_execution"><?php _e( 'Abfrage-Ausführung erlauben', 'themisdb-query-playground' ); ?></label></th>
                <td>
                    <input type="checkbox" name="themisdb_qp_enable_execution" id="themisdb_qp_enable_execution"
                           value="1" <?php checked( get_option( 'themisdb_qp_enable_execution' ), 1 ); ?> />
                    <label for="themisdb_qp_enable_execution"><?php _e( 'Benutzern erlauben, Abfragen auszuführen', 'themisdb-query-playground' ); ?></label>
                </td>
            </tr>
            <tr>
                <th><label for="themisdb_qp_read_only_mode"><?php _e( 'Nur-Lese-Modus', 'themisdb-query-playground' ); ?></label></th>
                <td>
                    <input type="checkbox" name="themisdb_qp_read_only_mode" id="themisdb_qp_read_only_mode"
                           value="1" <?php checked( get_option( 'themisdb_qp_read_only_mode' ), 1 ); ?> />
                    <label for="themisdb_qp_read_only_mode"><?php _e( 'INSERT/UPDATE/DELETE-Abfragen blockieren', 'themisdb-query-playground' ); ?></label>
                </td>
            </tr>
            <tr>
                <th><label for="themisdb_qp_max_results"><?php _e( 'Max. Ergebnisse', 'themisdb-query-playground' ); ?></label></th>
                <td>
                    <input type="number" name="themisdb_qp_max_results" id="themisdb_qp_max_results"
                           value="<?php echo esc_attr( get_option( 'themisdb_qp_max_results' ) ); ?>"
                           min="10" max="1000" class="small-text" />
                    <p class="description"><?php _e( 'Maximale Anzahl zurückgegebener Ergebnisse', 'themisdb-query-playground' ); ?></p>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>

    <?php elseif ( $_tqp_tab === 'display' ) : ?>

    <form method="post" action="options.php">
        <?php settings_fields( 'themisdb_qp_settings' ); ?>

        <h2><?php _e( 'Anzeigeeinstellungen', 'themisdb-query-playground' ); ?></h2>
        <table class="form-table">
            <tr>
                <th><label for="themisdb_qp_enable_examples"><?php _e( 'Beispielabfragen anzeigen', 'themisdb-query-playground' ); ?></label></th>
                <td>
                    <input type="checkbox" name="themisdb_qp_enable_examples" id="themisdb_qp_enable_examples"
                           value="1" <?php checked( get_option( 'themisdb_qp_enable_examples' ), 1 ); ?> />
                    <label for="themisdb_qp_enable_examples"><?php _e( 'Vorgefertigte Beispielabfragen einblenden', 'themisdb-query-playground' ); ?></label>
                </td>
            </tr>
            <tr>
                <th><label for="themisdb_qp_theme"><?php _e( 'Editor-Theme', 'themisdb-query-playground' ); ?></label></th>
                <td>
                    <select name="themisdb_qp_theme" id="themisdb_qp_theme">
                        <option value="monokai" <?php selected( get_option( 'themisdb_qp_theme' ), 'monokai' ); ?>>Monokai</option>
                        <option value="dracula" <?php selected( get_option( 'themisdb_qp_theme' ), 'dracula' ); ?>>Dracula</option>
                        <option value="default" <?php selected( get_option( 'themisdb_qp_theme' ), 'default' ); ?>>Default</option>
                    </select>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>

    <?php elseif ( $_tqp_tab === 'shortcodes' ) : ?>

    <h2><?php _e( 'Shortcode-Verwendung', 'themisdb-query-playground' ); ?></h2>
    <table class="widefat striped" style="max-width:860px;">
        <thead>
            <tr>
                <th><?php _e( 'Shortcode', 'themisdb-query-playground' ); ?></th>
                <th><?php _e( 'Beschreibung', 'themisdb-query-playground' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>[themisdb_query_playground]</code></td>
                <td><?php _e( 'Standard-Playground', 'themisdb-query-playground' ); ?></td>
            </tr>
            <tr>
                <td><code>[themisdb_query_playground default_query="SELECT * FROM urn:themis:relational:wikipedia_articles LIMIT 10"]</code></td>
                <td><?php _e( 'Vorausgefüllte Standardabfrage', 'themisdb-query-playground' ); ?></td>
            </tr>
            <tr>
                <td><code>[themisdb_query_playground height="600px"]</code></td>
                <td><?php _e( 'Benutzerdefinierte Höhe', 'themisdb-query-playground' ); ?></td>
            </tr>
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
