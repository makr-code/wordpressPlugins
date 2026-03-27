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
    • Total Lines:     221                                            ║
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
 * Template: Admin Settings Page
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check user capabilities
if (!current_user_can('manage_options')) {
    return;
}

// Save settings
if (isset($_POST['themisdb_rt_save_settings'])) {
    check_admin_referer('themisdb_rt_settings');
    
    update_option('themisdb_rt_github_repo', sanitize_text_field($_POST['github_repo']));
    update_option('themisdb_rt_changelog_path', sanitize_text_field($_POST['changelog_path']));
    update_option('themisdb_rt_manual_releases', wp_kses_post($_POST['manual_releases']));
    update_option('themisdb_rt_default_view', sanitize_text_field($_POST['default_view']));
    update_option('themisdb_rt_default_theme', sanitize_text_field($_POST['default_theme']));
    update_option('themisdb_rt_show_breaking', isset($_POST['show_breaking']) ? '1' : '0');
    update_option('themisdb_rt_show_features', isset($_POST['show_features']) ? '1' : '0');
    
    // Clear cache
    global $wpdb;
    $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
        '_transient_themisdb_rt_%'
    ));
    
    echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
}

// Get current settings
$github_repo = get_option('themisdb_rt_github_repo', 'makr-code/wordpressPlugins');
$changelog_path = get_option('themisdb_rt_changelog_path', '');
$manual_releases = get_option('themisdb_rt_manual_releases', '');
$default_view = get_option('themisdb_rt_default_view', 'chronological');
$default_theme = get_option('themisdb_rt_default_theme', 'neutral');
$show_breaking = get_option('themisdb_rt_show_breaking', '1');
$show_features = get_option('themisdb_rt_show_features', '1');

$_trt_page = 'themisdb-release-timeline';
$_trt_tab  = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'settings';
if ( ! in_array( $_trt_tab, array( 'settings', 'cache', 'shortcodes' ), true ) ) {
    $_trt_tab = 'settings';
}
$_trt_url = function ( $tab ) use ( $_trt_page ) {
    return esc_url( admin_url( 'options-general.php?page=' . $_trt_page . '&tab=' . $tab ) );
};
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php _e( 'Release Timeline', 'themisdb-release-timeline' ); ?>
        <a href="<?php echo $_trt_url( 'shortcodes' ); ?>" class="page-title-action">
            <?php _e( 'Shortcodes', 'themisdb-release-timeline' ); ?>
        </a>
    </h1>
    <hr class="wp-header-end">

    <nav class="nav-tab-wrapper wp-clearfix">
        <a href="<?php echo $_trt_url( 'settings' ); ?>"
           class="nav-tab <?php echo $_trt_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
            <?php _e( 'Einstellungen', 'themisdb-release-timeline' ); ?>
        </a>
        <a href="<?php echo $_trt_url( 'cache' ); ?>"
           class="nav-tab <?php echo $_trt_tab === 'cache' ? 'nav-tab-active' : ''; ?>">
            <?php _e( 'Cache', 'themisdb-release-timeline' ); ?>
        </a>
        <a href="<?php echo $_trt_url( 'shortcodes' ); ?>"
           class="nav-tab <?php echo $_trt_tab === 'shortcodes' ? 'nav-tab-active' : ''; ?>">
            <?php _e( 'Shortcodes', 'themisdb-release-timeline' ); ?>
        </a>
    </nav>

    <div class="themisdb-tab-content">

    <?php if ( $_trt_tab === 'settings' ) : ?>

    <div class="themisdb-admin-modules">
        <div class="card">
            <h2><?php _e( 'Schnellaktionen', 'themisdb-release-timeline' ); ?></h2>
            <p><?php _e( 'Springen Sie direkt zum Cache oder zur Shortcode-Referenz, ohne die Seite zu verlassen.', 'themisdb-release-timeline' ); ?></p>
            <p>
                <a href="<?php echo $_trt_url( 'cache' ); ?>" class="button button-secondary"><?php _e( 'Cache verwalten', 'themisdb-release-timeline' ); ?></a>
                <a href="<?php echo $_trt_url( 'shortcodes' ); ?>" class="button button-secondary"><?php _e( 'Shortcodes', 'themisdb-release-timeline' ); ?></a>
            </p>
        </div>
        <div class="card">
            <h2><?php _e( 'Aktive Konfiguration', 'themisdb-release-timeline' ); ?></h2>
            <table class="widefat striped">
                <tbody>
                    <tr><th><?php _e( 'Repository', 'themisdb-release-timeline' ); ?></th><td><code><?php echo esc_html( $github_repo ?: '—' ); ?></code></td></tr>
                    <tr><th><?php _e( 'Standardansicht', 'themisdb-release-timeline' ); ?></th><td><code><?php echo esc_html( $default_view ); ?></code></td></tr>
                    <tr><th><?php _e( 'Theme', 'themisdb-release-timeline' ); ?></th><td><code><?php echo esc_html( $default_theme ); ?></code></td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <form method="post" action="">
        <?php wp_nonce_field('themisdb_rt_settings'); ?>

        <h2><?php _e( 'Datenquellen', 'themisdb-release-timeline' ); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="github_repo"><?php _e( 'GitHub Repository', 'themisdb-release-timeline' ); ?></label>
                </th>
                <td>
                    <input type="text" id="github_repo" name="github_repo"
                           value="<?php echo esc_attr( $github_repo ); ?>"
                           class="regular-text" placeholder="owner/repository">
                    <p class="description"><?php _e( 'Format: owner/repository (z.B. makr-code/wordpressPlugins)', 'themisdb-release-timeline' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="changelog_path"><?php _e( 'CHANGELOG-Pfad', 'themisdb-release-timeline' ); ?></label>
                </th>
                <td>
                    <input type="text" id="changelog_path" name="changelog_path"
                           value="<?php echo esc_attr( $changelog_path ); ?>"
                           class="regular-text" placeholder="/pfad/zur/CHANGELOG.md">
                    <p class="description"><?php _e( 'Absoluter Serverpfad zur CHANGELOG.md-Datei', 'themisdb-release-timeline' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="manual_releases"><?php _e( 'Manuelle Release-Daten', 'themisdb-release-timeline' ); ?></label>
                </th>
                <td>
                    <textarea id="manual_releases" name="manual_releases"
                              rows="8" class="large-text code"><?php echo esc_textarea( $manual_releases ); ?></textarea>
                    <p class="description">
                        <?php _e( 'JSON-Array mit Release-Objekten. Beispiel:', 'themisdb-release-timeline' ); ?><br>
                        <code>[{"version":"v1.0.0","name":"Erstes Release","date":"2024-01-01","features":["Feature 1"],"breaking":false}]</code>
                    </p>
                </td>
            </tr>
        </table>

        <h2><?php _e( 'Anzeigeoptionen', 'themisdb-release-timeline' ); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="default_view"><?php _e( 'Standard-Ansicht', 'themisdb-release-timeline' ); ?></label>
                </th>
                <td>
                    <select id="default_view" name="default_view">
                        <option value="chronological" <?php selected( $default_view, 'chronological' ); ?>><?php _e( 'Chronologisch', 'themisdb-release-timeline' ); ?></option>
                        <option value="gantt"         <?php selected( $default_view, 'gantt' ); ?>><?php _e( 'Gantt-Diagramm', 'themisdb-release-timeline' ); ?></option>
                        <option value="mindmap"       <?php selected( $default_view, 'mindmap' ); ?>><?php _e( 'Mind-Map', 'themisdb-release-timeline' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="default_theme"><?php _e( 'Standard-Theme', 'themisdb-release-timeline' ); ?></label>
                </th>
                <td>
                    <select id="default_theme" name="default_theme">
                        <option value="neutral" <?php selected( $default_theme, 'neutral' ); ?>>Neutral</option>
                        <option value="dark"    <?php selected( $default_theme, 'dark' ); ?>>Dark</option>
                        <option value="forest"  <?php selected( $default_theme, 'forest' ); ?>>Forest</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e( 'Anzeigeoptionen', 'themisdb-release-timeline' ); ?></th>
                <td>
                    <fieldset>
                        <label>
                            <input type="checkbox" name="show_breaking" value="1" <?php checked( $show_breaking, '1' ); ?>>
                            <?php _e( 'Breaking-Changes-Warnung anzeigen', 'themisdb-release-timeline' ); ?>
                        </label><br>
                        <label>
                            <input type="checkbox" name="show_features" value="1" <?php checked( $show_features, '1' ); ?>>
                            <?php _e( 'Feature-Highlights anzeigen', 'themisdb-release-timeline' ); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" name="themisdb_rt_save_settings" class="button button-primary"
                   value="<?php esc_attr_e( 'Einstellungen speichern', 'themisdb-release-timeline' ); ?>">
        </p>
    </form>

    <?php elseif ( $_trt_tab === 'cache' ) : ?>

    <h2><?php _e( 'Cache-Verwaltung', 'themisdb-release-timeline' ); ?></h2>
    <p><?php _e( 'Release-Daten werden 1 Stunde gecacht. Cache leeren, um sofort neu von der Quelle zu laden.', 'themisdb-release-timeline' ); ?></p>

    <?php if ( isset( $_POST['clear_cache'] ) ) :
        check_admin_referer( 'themisdb_rt_clear_cache' );
        global $wpdb;
        $deleted = $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            '_transient_themisdb_rt_%'
        ) );
        echo '<div class="notice notice-success"><p>' . sprintf( esc_html__( 'Cache geleert (%d Einträge entfernt).', 'themisdb-release-timeline' ), (int) $deleted ) . '</p></div>';
    endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field( 'themisdb_rt_clear_cache' ); ?>
        <p>
            <input type="submit" name="clear_cache" class="button button-secondary"
                   value="<?php esc_attr_e( 'Cache leeren', 'themisdb-release-timeline' ); ?>"
                   onclick="return confirm('<?php esc_attr_e( 'Cache wirklich leeren?', 'themisdb-release-timeline' ); ?>');">
        </p>
    </form>

    <?php elseif ( $_trt_tab === 'shortcodes' ) : ?>

    <h2><?php _e( 'Shortcode-Verwendung', 'themisdb-release-timeline' ); ?></h2>
    <table class="widefat striped" style="max-width:860px;">
        <thead>
            <tr>
                <th><?php _e( 'Shortcode', 'themisdb-release-timeline' ); ?></th>
                <th><?php _e( 'Beschreibung', 'themisdb-release-timeline' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>[themisdb_release_timeline]</code></td>
                <td><?php _e( 'Standard-Ansicht', 'themisdb-release-timeline' ); ?></td>
            </tr>
            <tr>
                <td><code>[themisdb_release_timeline view="chronological"]</code></td>
                <td><?php _e( 'Chronologische Ansicht', 'themisdb-release-timeline' ); ?></td>
            </tr>
            <tr>
                <td><code>[themisdb_release_timeline view="gantt" theme="dark"]</code></td>
                <td><?php _e( 'Gantt-Diagramm im Dark-Theme', 'themisdb-release-timeline' ); ?></td>
            </tr>
            <tr>
                <td><code>[themisdb_release_timeline view="mindmap" releases="5"]</code></td>
                <td><?php _e( 'Mind-Map der letzten 5 Releases', 'themisdb-release-timeline' ); ?></td>
            </tr>
            <tr>
                <td><code>[themisdb_release_timeline source="changelog"]</code></td>
                <td><?php _e( 'Daten aus CHANGELOG-Datei lesen', 'themisdb-release-timeline' ); ?></td>
            </tr>
            <tr>
                <td><code>[themisdb_release_timeline source="github_milestones" releases="8"]</code></td>
                <td><?php _e( 'Daten aus GitHub Milestones + Issues lesen', 'themisdb-release-timeline' ); ?></td>
            </tr>
            <tr>
                <td><code>[themisdb_release_timeline source="github_tags" releases="8"]</code></td>
                <td><?php _e( 'Daten aus GitHub Tags lesen', 'themisdb-release-timeline' ); ?></td>
            </tr>
            <tr>
                <td><code>[themisdb_release_timeline source="github_milestones" releases="8" render_mode="list" interactive="false"]</code></td>
                <td><?php _e( 'Stabile Listenansicht ohne Mermaid (empfohlen fuer Startseite)', 'themisdb-release-timeline' ); ?></td>
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

