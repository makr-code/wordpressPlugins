# ThemisDB Theme Plugin Integration

Dieses Dokument beschreibt, wie Plugins Daten/Funktionalität bereitstellen, waehrend das Theme die Darstellung uebernimmt.

## Architekturprinzip

- Theme ist Darstellungs-Schicht (HTML/CSS/Frontend-Interaktion).
- Plugins sind Daten-/Funktions-Schicht (Defaults, Query-Anpassung, optionales Post-Processing).
- Bekannte Integrations-Tags werden im Theme bewusst uebernommen.

## Theme-Owned Integrations-Tags

- `themisdb_latest`
- `themisdb_verify`
- `themisdb_readme`
- `themisdb_docker_latest`
- `themisdb_compendium_downloads`
- `themisdb_benchmark_visualizer`
- `themisdb_architecture`
- `themisdb_support_portal`
- `themisdb_support_login`
- `themisdb_formula`
- `themisdb_wiki`
- `themisdb_docs`
- `themisdb_wiki_nav`
- `themisdb_tco_calculator`
- `themisdb_tco_workload`
- `themisdb_tco_infrastructure`
- `themisdb_tco_personnel`
- `themisdb_tco_operations`
- `themisdb_tco_ai`
- `themisdb_tco_results`
- `themisdb_test_dashboard`
- `themisdb_persistent_podcast_player`
- `themisdb_state_grid`
- `themisdb_gallery`
- `themisdb_changelog`
- `themisdb_front_slider`

## Hook Uebersicht

### 1. Allgemeine Shortcode-Defaults pro Tag

Filter:
- `themisdb_theme_shortcode_defaults_<tag>`

Beispiel fuer `themisdb_latest`:

```php
add_filter( 'themisdb_theme_shortcode_defaults_themisdb_latest', function( $defaults ) {
    $defaults['section']  = 'aktuelles';
    $defaults['per_page'] = 6;
    return $defaults;
} );
```

### 2. Allgemeine Render-Argumente pro Tag

Filter:
- `themisdb_theme_shortcode_args_<tag>`

```php
add_filter( 'themisdb_theme_shortcode_args_themisdb_latest', function( $args ) {
    $args['orderby'] = 'modified';
    $args['order']   = 'DESC';
    return $args;
} );
```

### 3. Allgemeines HTML-Post-Processing pro Tag

Filter:
- `themisdb_theme_shortcode_html_<tag>`

```php
add_filter( 'themisdb_theme_shortcode_html_themisdb_latest', function( $html ) {
    return '<div class="plugin-wrapper">' . $html . '</div>';
} );
```

## Spezifische Hook-Gruppen

### Download-Familie (Welle 1)

Fuer theme-owned Rendering stehen je Plugin drei Ebenen bereit:

- `*_shortcode_atts` (Attribute normalisieren/ergänzen)
- `*_shortcode_<payload>` (Datensatz vor Rendering anpassen)
- `*_shortcode_html` (vollstaendiges Markup ersetzen)
- `*_shortcode_html_output` (Plugin-HTML nachbearbeiten)

Konkrete Hooks:

- `themisdb_downloads_shortcode_atts`
- `themisdb_downloads_shortcode_releases`
- `themisdb_downloads_shortcode_html`
- `themisdb_downloads_shortcode_html_output`
- `themisdb_latest_shortcode_atts`
- `themisdb_latest_shortcode_payload`
- `themisdb_latest_shortcode_html`
- `themisdb_latest_shortcode_html_output`
- `themisdb_verify_shortcode_atts`
- `themisdb_verify_shortcode_payload`
- `themisdb_verify_shortcode_html`
- `themisdb_verify_shortcode_html_output`
- `themisdb_readme_shortcode_atts`
- `themisdb_readme_shortcode_payload`
- `themisdb_readme_shortcode_html`
- `themisdb_readme_shortcode_html_output`
- `themisdb_changelog_shortcode_atts`
- `themisdb_changelog_shortcode_payload`
- `themisdb_changelog_shortcode_html`
- `themisdb_changelog_shortcode_html_output`
- `themisdb_docker_downloads_shortcode_atts`
- `themisdb_docker_downloads_shortcode_payload`
- `themisdb_docker_latest_shortcode_atts`
- `themisdb_docker_latest_shortcode_payload`
- `themisdb_docker_latest_shortcode_html`
- `themisdb_docker_latest_shortcode_html_output`

Hinweis zur Signatur:

- Attribut-Filter der Form `*_shortcode_atts` werden in den migrierten Frontend-Plugins mit zwei Argumenten aufgerufen: `(array $atts, array $raw_atts)`.
- Dadurch kann das Theme neben normalisierten Defaults auch unveraenderte Shortcode-Eingaben konsistent auswerten.

### Benchmark Visualizer

- `themisdb_benchmark_visualizer_enqueue_frontend_style`
- `themisdb_benchmark_visualizer_shortcode_atts`
- `themisdb_benchmark_visualizer_shortcode_payload`
- `themisdb_benchmark_visualizer_shortcode_html`
- `themisdb_benchmark_visualizer_shortcode_html_output`

### Architecture Diagrams

- `themisdb_architecture_enqueue_frontend_style`
- `themisdb_architecture_shortcode_atts`
- `themisdb_architecture_shortcode_payload`
- `themisdb_architecture_shortcode_html`
- `themisdb_architecture_shortcode_html_output`

### Support Portal

- `themisdb_support_portal_enqueue_frontend_style`
- `themisdb_support_portal_shortcode_atts`
- `themisdb_support_portal_shortcode_payload`
- `themisdb_support_portal_shortcode_html`
- `themisdb_support_portal_shortcode_html_output`
- `themisdb_support_login_shortcode_atts`
- `themisdb_support_login_shortcode_payload`
- `themisdb_support_login_shortcode_html`
- `themisdb_support_login_shortcode_html_output`

### Formula Renderer

- `themisdb_formula_enqueue_frontend_style`
- `themisdb_formula_shortcode_atts`
- `themisdb_formula_shortcode_payload`
- `themisdb_formula_shortcode_html`
- `themisdb_formula_shortcode_html_output`

### Wiki Integration

- `themisdb_wiki_enqueue_frontend_style`
- `themisdb_wiki_shortcode_atts`
- `themisdb_wiki_shortcode_payload`
- `themisdb_wiki_shortcode_html`
- `themisdb_wiki_shortcode_html_output`
- `themisdb_docs_shortcode_atts`
- `themisdb_docs_shortcode_payload`
- `themisdb_docs_shortcode_html`
- `themisdb_docs_shortcode_html_output`
- `themisdb_wiki_nav_shortcode_atts`
- `themisdb_wiki_nav_shortcode_payload`
- `themisdb_wiki_nav_shortcode_html`
- `themisdb_wiki_nav_shortcode_html_output`
- `themisdb_docker_downloads_shortcode_tags`
- `themisdb_docker_downloads_shortcode_html`
- `themisdb_docker_downloads_shortcode_html_output`
- `themisdb_compendium_downloads_shortcode_atts`
- `themisdb_compendium_downloads_shortcode_release_data`
- `themisdb_compendium_downloads_shortcode_payload`
- `themisdb_compendium_downloads_shortcode_html`
- `themisdb_compendium_downloads_shortcode_html_output`

### GitHub Bridge (Datenzugriff)

Die GitHub-Bridge stellt jetzt neben der Issue-Sync-Strecke auch zentrale Fetch-Helfer fuer GitHub-Metadaten bereit, damit Plugins Tags/Milestones/Issues/Releases einheitlich und mit gemeinsamen Header-/Token-Regeln laden koennen:

- `themisdb_github_bridge_request( $method, $url, $args )`
- `themisdb_github_bridge_fetch_releases( $repository, $per_page )`
- `themisdb_github_bridge_fetch_tags( $repository, $per_page )`
- `themisdb_github_bridge_fetch_milestones( $repository, $query )`
- `themisdb_github_bridge_fetch_issues( $repository, $query )`

Damit koennen Plugin-Datenquellen wie Release Timeline (Tags, Milestones + Milestone-Issues), Download-Familie (Releases) und Update-Checks konsistent ueber die Bridge laufen.

Hinweis Betrieb:

- Fuer GitHub-basierte Datenquellen in den migrierten Plugins ist die aktive GitHub Bridge nun Voraussetzung (bridge-only Pfad, kein direkter API-Fallback mehr).
- In den Bridge-Einstellungen gibt es einen Bridge-Health-Bereich mit Konfigurationschecks (aktiviert, Repository-Format, Token gesetzt) und Aktivstatus der wichtigsten konsumierenden Plugins.
- Im Order-Request-Admin ist der manuelle GitHub-Status-Refresh fuer Support-Tickets per Cooldown steuerbar (Option: `themisdb_support_github_manual_refresh_cooldown`, Standard 30 Sekunden).

### TCO Calculator

- `themisdb_tco_calculator_enqueue_frontend_style`
- `themisdb_tco_calculator_shortcode_atts`
- `themisdb_tco_calculator_shortcode_payload`
- `themisdb_tco_calculator_shortcode_html`
- `themisdb_tco_calculator_shortcode_html_output`
- `themisdb_tco_workload_shortcode_atts`
- `themisdb_tco_workload_shortcode_payload`
- `themisdb_tco_workload_shortcode_html`
- `themisdb_tco_workload_shortcode_html_output`
- `themisdb_tco_infrastructure_shortcode_atts`
- `themisdb_tco_infrastructure_shortcode_payload`
- `themisdb_tco_infrastructure_shortcode_html`
- `themisdb_tco_infrastructure_shortcode_html_output`
- `themisdb_tco_personnel_shortcode_atts`
- `themisdb_tco_personnel_shortcode_payload`
- `themisdb_tco_personnel_shortcode_html`
- `themisdb_tco_personnel_shortcode_html_output`
- `themisdb_tco_operations_shortcode_atts`
- `themisdb_tco_operations_shortcode_payload`
- `themisdb_tco_operations_shortcode_html`
- `themisdb_tco_operations_shortcode_html_output`
- `themisdb_tco_ai_shortcode_atts`
- `themisdb_tco_ai_shortcode_payload`
- `themisdb_tco_ai_shortcode_html`
- `themisdb_tco_ai_shortcode_html_output`
- `themisdb_tco_results_shortcode_atts`
- `themisdb_tco_results_shortcode_payload`
- `themisdb_tco_results_shortcode_html`
- `themisdb_tco_results_shortcode_html_output`

Status:

- Theme-Adapter fuer TCO Calculator und alle Teilsektionen sind in `functions.php` aktiv.

### Persistent Podcast Player

- `themisdb_persistent_podcast_player_enqueue_frontend_style`
- `themisdb_persistent_podcast_player_payload`
- `themisdb_persistent_podcast_player_html`
- `themisdb_persistent_podcast_player_html_output`

Status:

- Theme-Adapter fuer den Persistent Podcast Player ist in `functions.php` aktiv.

### Test Dashboard

- `themisdb_test_dashboard_enqueue_frontend_style`
- `themisdb_test_dashboard_shortcode_atts`
- `themisdb_test_dashboard_shortcode_payload`
- `themisdb_test_dashboard_shortcode_html`
- `themisdb_test_dashboard_shortcode_html_output`

Status:

- Theme-Adapter fuer Test Dashboard ist in `functions.php` aktiv.

### Graph Navigation

- `themisdb_graph_navigation_enqueue_frontend_script`
- `themisdb_graph_navigation_data`
- `themisdb_graph_navigation_js_payload`

Status:

- Theme-Adapter fuer Graph-Navigation-Payload ist in `functions.php` aktiv.

Aktueller Status:

- Das Theme nutzt diese Hooks bereits aktiv in `functions.php` (Theme-Adapter fuer Downloads, Docker-Tags und Compendium).
- Damit bleibt die Plugin-Logik erhalten, waehrend das finale Frontend-Markup durch das Theme geliefert wird.
- ThemisDB GitHub Bridge ist Backend-only (Admin/Sync) und benoetigt keine Theme-Frontend-Adapter.

Beispiel (Theme ersetzt Download-Markup komplett):

```php
add_filter( 'themisdb_downloads_shortcode_html', function( $html, $releases, $atts ) {
    if ( null !== $html ) {
        return $html;
    }

    ob_start();
    ?>
    <section class="themisdb-theme-download-grid">
        <?php foreach ( $releases as $release ) : ?>
            <article class="download-release-card">
                <h3><?php echo esc_html( $release['version'] ); ?></h3>
            </article>
        <?php endforeach; ?>
    </section>
    <?php
    return ob_get_clean();
}, 10, 3 );
```

### Front Slider

- `themisdb_theme_front_slider_defaults`
- `themisdb_theme_front_slider_atts`
- `themisdb_theme_front_slider_query_args`
- `themisdb_theme_front_slider_html`
- `themisdb_front_slider_enqueue_frontend_style`
- `themisdb_front_slider_shortcode_atts`
- `themisdb_front_slider_shortcode_query_args`
- `themisdb_front_slider_shortcode_payload`
- `themisdb_front_slider_shortcode_html`
- `themisdb_front_slider_shortcode_html_output`

Status:

- Theme-Adapter fuer Front Slider ist in `functions.php` aktiv.

```php
add_filter( 'themisdb_theme_front_slider_query_args', function( $query_args, $atts ) {
    $query_args['meta_key'] = '_featured';
    $query_args['orderby']  = 'meta_value_num';
    return $query_args;
}, 10, 2 );
```

### Welle 2 (Feature Matrix + Release Timeline)

- `themisdb_feature_matrix_enqueue_frontend_style`
- `themisdb_feature_matrix_shortcode_atts`
- `themisdb_feature_matrix_shortcode_payload`
- `themisdb_feature_matrix_shortcode_html`
- `themisdb_feature_matrix_shortcode_html_output`
- `themisdb_release_timeline_enqueue_frontend_style`
- `themisdb_release_timeline_shortcode_atts`
- `themisdb_release_timeline_shortcode_data`
- `themisdb_release_timeline_shortcode_html`
- `themisdb_release_timeline_shortcode_html_output`

Status:

- Theme-Adapter fuer Feature Matrix und Release Timeline sind in `functions.php` aktiv.

### Welle 2 (Gallery)

- `themisdb_gallery_enqueue_frontend_style`
- `themisdb_gallery_shortcode_atts`
- `themisdb_gallery_shortcode_payload`
- `themisdb_gallery_shortcode_html`
- `themisdb_gallery_shortcode_html_output`

Status:

- Theme-Adapter fuer Gallery ist in `functions.php` aktiv.

### Welle 3 (Taxonomy Manager)

- `themisdb_taxonomy_enqueue_frontend_style`
- `themisdb_taxonomy_shortcode_atts`
- `themisdb_taxonomy_shortcode_payload`
- `themisdb_taxonomy_shortcode_html`
- `themisdb_taxonomy_shortcode_html_output`
- `themisdb_taxonomy_info_shortcode_atts`
- `themisdb_taxonomy_info_shortcode_payload`
- `themisdb_taxonomy_info_shortcode_html`
- `themisdb_taxonomy_info_shortcode_html_output`
- `themisdb_term_card_shortcode_atts`
- `themisdb_term_card_shortcode_payload`
- `themisdb_term_card_shortcode_html`
- `themisdb_term_card_shortcode_html_output`

Status:

- Theme-Adapter fuer Taxonomy-Shortcodes sind in `functions.php` aktiv.

### Welle 3 (Query Playground)

- `themisdb_query_playground_enqueue_frontend_style`
- `themisdb_query_playground_shortcode_atts`
- `themisdb_query_playground_shortcode_payload`
- `themisdb_query_playground_shortcode_html`
- `themisdb_query_playground_shortcode_html_output`

Status:

- Theme-Adapter fuer Query Playground ist in `functions.php` aktiv.

### Welle 3 (Order Request)

- `themisdb_order_request_enqueue_frontend_style`
- `themisdb_order_flow_shortcode_atts`
- `themisdb_order_flow_shortcode_payload`
- `themisdb_order_flow_shortcode_html`
- `themisdb_order_flow_shortcode_html_output`
- `themisdb_express_checkout_shortcode_atts`
- `themisdb_express_checkout_shortcode_payload`
- `themisdb_express_checkout_shortcode_html`
- `themisdb_express_checkout_shortcode_html_output`
- `themisdb_my_orders_shortcode_atts`
- `themisdb_my_orders_shortcode_payload`
- `themisdb_my_orders_shortcode_html`
- `themisdb_my_orders_shortcode_html_output`
- `themisdb_my_contracts_shortcode_atts`
- `themisdb_my_contracts_shortcode_payload`
- `themisdb_my_contracts_shortcode_html`
- `themisdb_my_contracts_shortcode_html_output`
- `themisdb_pricing_shortcode_atts`
- `themisdb_pricing_shortcode_payload`
- `themisdb_pricing_shortcode_html`
- `themisdb_pricing_shortcode_html_output`
- `themisdb_pricing_table_shortcode_atts`
- `themisdb_pricing_table_shortcode_payload`
- `themisdb_pricing_table_shortcode_html`
- `themisdb_pricing_table_shortcode_html_output`
- `themisdb_product_detail_enqueue_frontend_style`
- `themisdb_product_detail_shortcode_atts`
- `themisdb_product_detail_shortcode_payload`
- `themisdb_product_detail_shortcode_html`
- `themisdb_product_detail_shortcode_html_output`
- `themisdb_shop_shortcode_atts`
- `themisdb_shop_shortcode_payload`
- `themisdb_shop_shortcode_html`
- `themisdb_shop_shortcode_html_output`
- `themisdb_shopping_cart_enqueue_frontend_style`
- `themisdb_shopping_cart_shortcode_atts`
- `themisdb_shopping_cart_shortcode_payload`
- `themisdb_shopping_cart_shortcode_html`
- `themisdb_shopping_cart_shortcode_html_output`
- `themisdb_login_shortcode_atts`
- `themisdb_login_shortcode_payload`
- `themisdb_login_shortcode_html`
- `themisdb_login_shortcode_html_output`
- `themisdb_license_upload_shortcode_atts`
- `themisdb_license_upload_shortcode_payload`
- `themisdb_license_upload_shortcode_html`
- `themisdb_license_upload_shortcode_html_output`
- `themisdb_license_portal_enqueue_frontend_style`
- `themisdb_license_portal_shortcode_atts`
- `themisdb_license_portal_shortcode_payload`
- `themisdb_license_portal_shortcode_html`
- `themisdb_license_portal_shortcode_html_output`
- `themisdb_affiliate_dashboard_shortcode_atts`
- `themisdb_affiliate_dashboard_shortcode_payload`
- `themisdb_affiliate_dashboard_shortcode_html`
- `themisdb_affiliate_dashboard_shortcode_html_output`
- `themisdb_b2b_portal_shortcode_atts`
- `themisdb_b2b_portal_shortcode_payload`
- `themisdb_b2b_portal_shortcode_html`
- `themisdb_b2b_portal_shortcode_html_output`
- `themisdb_advanced_reporting_shortcode_atts`
- `themisdb_advanced_reporting_shortcode_payload`
- `themisdb_advanced_reporting_shortcode_html`
- `themisdb_advanced_reporting_shortcode_html_output`

Status:

- Theme-Adapter fuer `themisdb_benchmark_visualizer_shortcode_html`, `themisdb_architecture_shortcode_html`, `themisdb_support_portal_shortcode_html`, `themisdb_support_login_shortcode_html`, `themisdb_formula_shortcode_html`, `themisdb_wiki_shortcode_html`, `themisdb_docs_shortcode_html`, `themisdb_wiki_nav_shortcode_html`, `themisdb_order_flow_shortcode_html`, `themisdb_my_orders_shortcode_html`, `themisdb_my_contracts_shortcode_html`, `themisdb_pricing_shortcode_html`, `themisdb_pricing_table_shortcode_html`, `themisdb_product_detail_shortcode_html`, `themisdb_shop_shortcode_html`, `themisdb_shopping_cart_shortcode_html`, `themisdb_login_shortcode_html`, `themisdb_license_upload_shortcode_html`, `themisdb_license_portal_shortcode_html`, `themisdb_affiliate_dashboard_shortcode_html`, `themisdb_b2b_portal_shortcode_html` und `themisdb_advanced_reporting_shortcode_html` sind in `functions.php` aktiv.

### Gallery

- `themisdb_theme_gallery_atts`
- `themisdb_theme_gallery_html`

### Changelog

- `themisdb_theme_changelog_atts`
- `themisdb_theme_changelog_html`

### State Grid

- `themisdb_theme_state_grid_items`
- `themisdb_theme_state_grid_atts`
- `themisdb_theme_state_grid_html`

```php
add_filter( 'themisdb_theme_state_grid_items', function( $items ) {
    $items[] = array(
        'code'  => 'EU',
        'name'  => 'EU Pilot',
        'crest' => 'Coat_of_arms_of_Europe.svg',
    );
    return $items;
} );
```

## Integrations-Checkliste fuer Plugin-Autoren

1. Keine eigenen Template-HTML-Ausgaben fuer die oben genannten Integrations-Tags erzwingen.
2. Stattdessen Daten ueber die Theme-Hooks einspeisen.
3. Hook-Callbacks defensiv schreiben (fehlende Keys tolerieren).
4. Rueckgabewerte strikt typkonform halten (`array` bei Arg-Filtern, `string` bei HTML-Filtern).
5. Prioritaeten nur erhoehen, wenn eine bestehende Integration bewusst ueberschrieben werden soll.

## Kompatibilitaet

- Das Theme funktioniert ohne zusaetzliche ThemisDB-Plugins.
- Wenn Plugins aktiv sind, koennen sie die Ausgabe beeinflussen, ohne die zentrale Theme-Darstellung zu brechen.

## Systematische Pruefung

Der aktuelle pluginweite Darstellungs-Audit mit Prioritaeten ist dokumentiert in:

- `docs/THEMISDB_PLUGIN_PRESENTATION_AUDIT_2026-03-26.md`
