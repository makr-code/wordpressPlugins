<?php
/**
 * Plugin Name: Chimera Benchmark Data
 * Plugin URI: https://github.com/makr-code/wordpressPlugins
 * Description: Verwaltung und Bereitstellung von Chimera Benchmark-Daten inklusive Admin-Erfassung, CSV-Import, REST-API und Frontend-Shortcode.
 * Version: 1.0.0
 * Author: ThemisDB Team
 * Author URI: https://github.com/makr-code/wordpressPlugins
 * License: MIT
 * Text Domain: chimera-benchmark-data
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

if (version_compare(PHP_VERSION, '7.4', '<')) {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p><strong>Chimera Benchmark Data:</strong> Dieses Plugin benoetigt PHP 7.4 oder hoeher.</p></div>';
    });
    return;
}

define('CHIMERA_BENCHMARK_DATA_VERSION', '1.0.0');
define('CHIMERA_BENCHMARK_DATA_FILE', __FILE__);
define('CHIMERA_BENCHMARK_DATA_DIR', plugin_dir_path(__FILE__));

require_once CHIMERA_BENCHMARK_DATA_DIR . 'includes/class-chimera-benchmark-list-table.php';

class Chimera_Benchmark_Data_Plugin {

    const OPTION_DB_VERSION = 'chimera_benchmark_data_db_version';
    const DB_VERSION = '1.0.0';

    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        register_activation_hook(CHIMERA_BENCHMARK_DATA_FILE, array($this, 'activate'));

        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('admin_menu', array($this, 'register_admin_menu'));
        add_action('admin_post_chimera_benchmark_add', array($this, 'handle_add_benchmark'));
        add_action('admin_post_chimera_benchmark_update', array($this, 'handle_update_benchmark'));
        add_action('admin_post_chimera_benchmark_quick_update', array($this, 'handle_quick_update'));
        add_action('admin_post_chimera_benchmark_delete', array($this, 'handle_delete_benchmark'));
        add_action('admin_post_chimera_benchmark_bulk_action', array($this, 'handle_bulk_action'));
        add_action('admin_post_chimera_benchmark_export_csv', array($this, 'handle_export_csv'));
        add_action('admin_post_chimera_benchmark_import_csv', array($this, 'handle_import_csv'));

        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));

        add_shortcode('chimera_benchmark_table', array($this, 'render_shortcode_table'));
        add_shortcode('chimera_benchmark_chart', array($this, 'render_shortcode_chart'));
    }

    public function enqueue_frontend_assets() {
        global $post;

        if (!is_a($post, 'WP_Post')) {
            return;
        }

        if (!has_shortcode($post->post_content, 'chimera_benchmark_chart')) {
            return;
        }

        wp_enqueue_script(
            'chimera-chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
            array(),
            '4.4.1',
            true
        );
    }

    public function load_textdomain() {
        load_plugin_textdomain('chimera-benchmark-data', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function activate() {
        $this->maybe_upgrade_schema(true);
    }

    private function maybe_upgrade_schema($force = false) {
        $installed = get_option(self::OPTION_DB_VERSION, '0');
        if (!$force && version_compare($installed, self::DB_VERSION, '>=')) {
            return;
        }

        global $wpdb;
        $table = $this->table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            benchmark_name varchar(190) NOT NULL,
            workload varchar(100) NOT NULL,
            dataset_size varchar(100) NOT NULL,
            engine varchar(100) NOT NULL,
            environment varchar(100) DEFAULT '',
            metric_name varchar(100) NOT NULL,
            metric_unit varchar(30) NOT NULL,
            metric_value decimal(18,6) NOT NULL,
            run_at datetime DEFAULT NULL,
            notes text DEFAULT NULL,
            metadata longtext DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY workload (workload),
            KEY engine (engine),
            KEY metric_name (metric_name),
            KEY run_at (run_at)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option(self::OPTION_DB_VERSION, self::DB_VERSION);
    }

    private function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'chimera_benchmarks';
    }

    public function register_admin_menu() {
        add_menu_page(
            __('Chimera Benchmarks', 'chimera-benchmark-data'),
            __('Chimera Benchmarks', 'chimera-benchmark-data'),
            'manage_options',
            'chimera-benchmark-data',
            array($this, 'render_admin_page'),
            'dashicons-chart-line',
            59
        );
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $this->maybe_upgrade_schema();

        // ── Query params ─────────────────────────────────────────────────────
        $filter_workload    = isset($_GET['filter_workload'])   ? sanitize_text_field(wp_unslash($_GET['filter_workload']))   : '';
        $filter_engine      = isset($_GET['filter_engine'])     ? sanitize_text_field(wp_unslash($_GET['filter_engine']))     : '';
        $filter_metric_name = isset($_GET['filter_metric_name'])? sanitize_text_field(wp_unslash($_GET['filter_metric_name'])): '';
        $sort_by            = isset($_GET['orderby'])           ? sanitize_key(wp_unslash($_GET['orderby']))                  : 'run_at';
        $sort_order         = isset($_GET['order'])             ? strtoupper(sanitize_text_field(wp_unslash($_GET['order']))) : 'DESC';
        $current_page       = isset($_GET['paged'])             ? max(1, intval($_GET['paged']))                              : 1;
        $per_page           = isset($_GET['per_page'])          ? max(10, min(200, intval($_GET['per_page'])))                : 50;

        $edit_id  = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
        $edit_row = null;
        if ($edit_id > 0) {
            $edit_row = $this->get_record_by_id($edit_id);
        }

        $notice   = isset($_GET['chimera_notice']) ? sanitize_key(wp_unslash($_GET['chimera_notice'])) : '';
        $imported = isset($_GET['imported'])       ? intval($_GET['imported'])                         : 0;
        $failed   = isset($_GET['failed'])         ? intval($_GET['failed'])                           : 0;
        $affected = isset($_GET['affected'])       ? intval($_GET['affected'])                         : 0;

        // ── Active tab ───────────────────────────────────────────────────────
        // Auto-switch to "Anlegen" tab when an edit_id is present.
        $default_tab = ($edit_id > 0 || in_array($notice, array('created', 'updated'), true)) ? 'add' : 'list';
        if (in_array($notice, array('imported', 'exported'), true)) {
            $default_tab = 'import';
        }
        $active_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : $default_tab;
        if (!in_array($active_tab, array('list', 'add', 'import'), true)) {
            $active_tab = 'list';
        }

        $base_url = admin_url('admin.php?page=chimera-benchmark-data');

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Chimera Benchmark Data', 'chimera-benchmark-data') . '</h1>';

        // ── Notices ──────────────────────────────────────────────────────────
        $notice_map = array(
            'created'      => array('success', __('Benchmark-Datensatz gespeichert.',                                           'chimera-benchmark-data')),
            'updated'      => array('success', __('Benchmark-Datensatz aktualisiert.',                                          'chimera-benchmark-data')),
            'quick_updated'=> array('success', __('Quick Edit gespeichert.',                                                    'chimera-benchmark-data')),
            'deleted'      => array('success', __('Benchmark-Datensatz geloescht.',                                              'chimera-benchmark-data')),
            'bulk_deleted' => array('success', sprintf(__('Bulk-Loeschung abgeschlossen. Entfernt: %d',   'chimera-benchmark-data'), $affected)),
            'bulk_updated' => array('success', sprintf(__('Bulk-Update abgeschlossen. Aktualisiert: %d', 'chimera-benchmark-data'), $affected)),
            'exported'     => array('success', __('CSV-Export wurde erstellt.',                                                 'chimera-benchmark-data')),
            'imported'     => array('success', sprintf(__('CSV-Import abgeschlossen. Erfolgreich: %d, Fehler: %d', 'chimera-benchmark-data'), $imported, $failed)),
            'error'        => array('error',   __('Aktion fehlgeschlagen.',                                                     'chimera-benchmark-data')),
        );
        if (isset($notice_map[$notice])) {
            list($type, $msg) = $notice_map[$notice];
            echo '<div class="notice notice-' . esc_attr($type) . ' is-dismissible"><p>' . esc_html($msg) . '</p></div>';
        }

        // ── Tab navigation ───────────────────────────────────────────────────
        $tabs = array(
            'list'   => __('Datensätze',     'chimera-benchmark-data'),
            'add'    => $edit_id > 0 ? __('Bearbeiten', 'chimera-benchmark-data') : __('Anlegen', 'chimera-benchmark-data'),
            'import' => __('Import / Export','chimera-benchmark-data'),
        );
        echo '<nav class="nav-tab-wrapper wp-clearfix" aria-label="' . esc_attr__('Tabs', 'chimera-benchmark-data') . '">';
        foreach ($tabs as $slug => $label) {
            $tab_url = add_query_arg('tab', $slug, $base_url);
            $class   = 'nav-tab' . ($active_tab === $slug ? ' nav-tab-active' : '');
            echo '<a href="' . esc_url($tab_url) . '" class="' . esc_attr($class) . '">' . esc_html($label) . '</a>';
        }
        echo '</nav>';

        // ════════════════════════════════════════════════════════════════════
        // TAB: Datensätze
        // ════════════════════════════════════════════════════════════════════
        if ($active_tab === 'list') {
            $list_table = new Chimera_Benchmark_List_Table(
                $this->table_name(),
                array(
                    'workload'     => $filter_workload,
                    'engine'       => $filter_engine,
                    'metric_name'  => $filter_metric_name,
                ),
                $per_page
            );
            $list_table->prepare_items();

            echo '<div class="chimera-tab-content">';
            echo '<p>' . esc_html__('Shortcode: [chimera_benchmark_table]', 'chimera-benchmark-data') . '</p>';

            // Filter bar
            $list_tab_url = add_query_arg('tab', 'list', $base_url);
            echo '<form method="get" action="' . esc_url(admin_url('admin.php')) . '" style="margin:12px 0;">';
            echo '<input type="hidden" name="page" value="chimera-benchmark-data">';
            echo '<input type="hidden" name="tab" value="list">';
            echo '<input type="text" name="filter_workload" value="' . esc_attr($filter_workload) . '" placeholder="' . esc_attr__('Filter Workload', 'chimera-benchmark-data') . '"> ';
            echo '<input type="text" name="filter_engine"   value="' . esc_attr($filter_engine)   . '" placeholder="' . esc_attr__('Filter Engine',   'chimera-benchmark-data') . '"> ';
            echo '<input type="text" name="filter_metric_name" value="' . esc_attr($filter_metric_name) . '" placeholder="' . esc_attr__('Filter Metric Name', 'chimera-benchmark-data') . '"> ';
            echo '<label for="chimera_orderby">' . esc_html__('Sortierung', 'chimera-benchmark-data') . '</label> ';
            echo '<select id="chimera_orderby" name="orderby">';
            foreach (array('run_at', 'benchmark_name', 'workload', 'engine', 'metric_name', 'metric_value', 'created_at') as $orderby_option) {
                echo '<option value="' . esc_attr($orderby_option) . '" ' . selected($sort_by, $orderby_option, false) . '>' . esc_html($orderby_option) . '</option>';
            }
            echo '</select> ';
            echo '<select name="order">';
            echo '<option value="DESC" ' . selected($sort_order, 'DESC', false) . '>DESC</option>';
            echo '<option value="ASC"  ' . selected($sort_order, 'ASC',  false) . '>ASC</option>';
            echo '</select> ';
            echo '<label for="chimera_per_page">' . esc_html__('Pro Seite', 'chimera-benchmark-data') . '</label> ';
            echo '<select id="chimera_per_page" name="per_page">';
            foreach (array(25, 50, 100, 200) as $opt) {
                echo '<option value="' . intval($opt) . '" ' . selected($per_page, $opt, false) . '>' . intval($opt) . '</option>';
            }
            echo '</select> ';
            submit_button(__('Filtern', 'chimera-benchmark-data'), 'secondary', 'submit', false);
            echo ' <a class="button" href="' . esc_url($list_tab_url) . '">' . esc_html__('Filter zurücksetzen', 'chimera-benchmark-data') . '</a>';
            echo '</form>';

            // List table with bulk actions
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            wp_nonce_field('chimera_benchmark_bulk_action');
            echo '<input type="hidden" name="action"             value="chimera_benchmark_bulk_action">';
            echo '<input type="hidden" name="filter_workload"    value="' . esc_attr($filter_workload)    . '">';
            echo '<input type="hidden" name="filter_engine"      value="' . esc_attr($filter_engine)      . '">';
            echo '<input type="hidden" name="filter_metric_name" value="' . esc_attr($filter_metric_name) . '">';
            echo '<input type="hidden" name="orderby"            value="' . esc_attr($sort_by)            . '">';
            echo '<input type="hidden" name="order"              value="' . esc_attr($sort_order)         . '">';
            echo '<input type="hidden" name="per_page"           value="' . intval($per_page)             . '">';
            echo '<input type="hidden" name="paged"              value="' . intval($list_table->get_pagenum()) . '">';
            $list_table->display();
            echo '</form>';
            echo '</div>';

            // Inline quick-edit styles + JS
            echo '<style>';
            echo '.chimera-inline-editor-row td{background:#f6f7f7;}';
            echo '.chimera-inline-editor{display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;}';
            echo '.chimera-inline-editor label{display:flex;flex-direction:column;font-size:12px;color:#50575e;min-width:180px;}';
            echo '.chimera-inline-editor input[type="text"]{min-width:180px;}';
            echo '.chimera-inline-editor .chimera-inline-actions{display:flex;gap:8px;align-items:center;padding-bottom:2px;}';
            echo '</style>';
            echo '<script>';
            echo 'document.addEventListener("DOMContentLoaded", function () {';
            echo '  var currentEditorRow = null;';
            echo '  function escAttr(value) {';
            echo '    return String(value || "")';
            echo '      .replace(/&/g, "&amp;")';
            echo '      .replace(/"/g, "&quot;")';
            echo '      .replace(/</g, "&lt;")';
            echo '      .replace(/>/g, "&gt;");';
            echo '  }';
            echo '  function closeInlineEditor() {';
            echo '    if (currentEditorRow && currentEditorRow.parentNode) {';
            echo '      currentEditorRow.parentNode.removeChild(currentEditorRow);';
            echo '    }';
            echo '    currentEditorRow = null;';
            echo '  }';
            echo '  function submitInlineQuickEdit(data) {';
            echo '    var form = document.createElement("form");';
            echo '    form.method = "post";';
            echo '    form.action = ' . wp_json_encode(admin_url('admin-post.php')) . ';';
            echo '    var payload = {';
            echo '      action: "chimera_benchmark_quick_update",';
            echo '      _wpnonce: ' . wp_json_encode(wp_create_nonce('chimera_benchmark_quick_update')) . ',';
            echo '      id: data.id,';
            echo '      workload: data.workload,';
            echo '      engine: data.engine,';
            echo '      metric_name: data.metricName,';
            echo '      dataset_size: data.datasetSize,';
            echo '      environment: data.environment,';
            echo '      filter_workload: ' . wp_json_encode($filter_workload) . ',';
            echo '      filter_engine: ' . wp_json_encode($filter_engine) . ',';
            echo '      filter_metric_name: ' . wp_json_encode($filter_metric_name) . ',';
            echo '      orderby: ' . wp_json_encode($sort_by) . ',';
            echo '      order: ' . wp_json_encode($sort_order) . ',';
            echo '      per_page: ' . intval($per_page) . ',';
            echo '      paged: ' . intval($current_page);
            echo '    };';
            echo '    Object.keys(payload).forEach(function (key) {';
            echo '      var input = document.createElement("input");';
            echo '      input.type = "hidden";';
            echo '      input.name = key;';
            echo '      input.value = payload[key];';
            echo '      form.appendChild(input);';
            echo '    });';
            echo '    document.body.appendChild(form);';
            echo '    form.submit();';
            echo '  }';
            echo '  document.querySelectorAll(".chimera-quick-edit-trigger").forEach(function (link) {';
            echo '    link.addEventListener("click", function (event) {';
            echo '      event.preventDefault();';
            echo '      var row = this.closest("tr");';
            echo '      if (!row || !row.parentNode) { return; }';
            echo '      closeInlineEditor();';
            echo '      var editorRow = document.createElement("tr");';
            echo '      editorRow.className = "chimera-inline-editor-row";';
            echo '      var cell = document.createElement("td");';
            echo '      cell.colSpan = row.children.length || 9;';
            echo '      var id          = this.dataset.id          || "";';
            echo '      var workload    = this.dataset.workload    || "";';
            echo '      var engine      = this.dataset.engine      || "";';
            echo '      var metricName  = this.dataset.metricName  || "";';
            echo '      var datasetSize = this.dataset.datasetSize || "";';
            echo '      var environment = this.dataset.environment || "";';
            echo '      cell.innerHTML = "<div class=\"chimera-inline-editor\">" +';
            echo '        "<label>' . esc_html__('Workload',     'chimera-benchmark-data') . '<input type=\"text\" class=\"chimera-inline-workload\"     value=\"" + escAttr(workload)    + "\" required></label>" +';
            echo '        "<label>' . esc_html__('Engine',       'chimera-benchmark-data') . '<input type=\"text\" class=\"chimera-inline-engine\"       value=\"" + escAttr(engine)      + "\" required></label>" +';
            echo '        "<label>' . esc_html__('Metric Name',  'chimera-benchmark-data') . '<input type=\"text\" class=\"chimera-inline-metric-name\"  value=\"" + escAttr(metricName)  + "\" required></label>" +';
            echo '        "<label>' . esc_html__('Dataset Size', 'chimera-benchmark-data') . '<input type=\"text\" class=\"chimera-inline-dataset-size\" value=\"" + escAttr(datasetSize) + "\" required></label>" +';
            echo '        "<label>' . esc_html__('Environment',  'chimera-benchmark-data') . '<input type=\"text\" class=\"chimera-inline-environment\"  value=\"" + escAttr(environment) + "\"></label>" +';
            echo '        "<div class=\"chimera-inline-actions\"><button type=\"button\" class=\"button button-primary chimera-inline-save\">'  . esc_html__('Speichern', 'chimera-benchmark-data') . '</button><button type=\"button\" class=\"button chimera-inline-cancel\">' . esc_html__('Abbrechen', 'chimera-benchmark-data') . '</button></div>" +';
            echo '      "</div>";';
            echo '      editorRow.appendChild(cell);';
            echo '      row.parentNode.insertBefore(editorRow, row.nextSibling);';
            echo '      currentEditorRow = editorRow;';
            echo '      var saveButton   = editorRow.querySelector(".chimera-inline-save");';
            echo '      var cancelButton = editorRow.querySelector(".chimera-inline-cancel");';
            echo '      if (cancelButton) {';
            echo '        cancelButton.addEventListener("click", function () { closeInlineEditor(); });';
            echo '      }';
            echo '      if (saveButton) {';
            echo '        saveButton.addEventListener("click", function () {';
            echo '          var nextWorkload    = (editorRow.querySelector(".chimera-inline-workload")     || {}).value || "";';
            echo '          var nextEngine      = (editorRow.querySelector(".chimera-inline-engine")       || {}).value || "";';
            echo '          var nextMetricName  = (editorRow.querySelector(".chimera-inline-metric-name")  || {}).value || "";';
            echo '          var nextDatasetSize = (editorRow.querySelector(".chimera-inline-dataset-size") || {}).value || "";';
            echo '          var nextEnvironment = (editorRow.querySelector(".chimera-inline-environment")  || {}).value || "";';
            echo '          if (!nextWorkload.trim() || !nextEngine.trim() || !nextMetricName.trim() || !nextDatasetSize.trim()) {';
            echo '            window.alert(' . wp_json_encode(__('Bitte alle Pflichtfelder im Quick Edit ausfüllen.', 'chimera-benchmark-data')) . ');';
            echo '            return;';
            echo '          }';
            echo '          submitInlineQuickEdit({ id: id, workload: nextWorkload, engine: nextEngine, metricName: nextMetricName, datasetSize: nextDatasetSize, environment: nextEnvironment });';
            echo '        });';
            echo '      }';
            echo '    });';
            echo '  });';
            echo '});';
            echo '</script>';
        }

        // ════════════════════════════════════════════════════════════════════
        // TAB: Anlegen / Bearbeiten
        // ════════════════════════════════════════════════════════════════════
        if ($active_tab === 'add') {
            echo '<div class="chimera-tab-content">';

            if (!empty($edit_row)) {
                // ── Edit existing record ──────────────────────────────────
                echo '<h2>' . esc_html__('Benchmark bearbeiten', 'chimera-benchmark-data') . ' #' . intval($edit_row['id']) . '</h2>';
                echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
                wp_nonce_field('chimera_benchmark_update_' . intval($edit_row['id']));
                echo '<input type="hidden" name="action" value="chimera_benchmark_update">';
                echo '<input type="hidden" name="id"     value="' . intval($edit_row['id']) . '">';
                echo '<table class="form-table">';
                $this->render_text_input('benchmark_name', __('Benchmark Name',             'chimera-benchmark-data'), true,  (string) $edit_row['benchmark_name']);
                $this->render_text_input('workload',       __('Workload',                   'chimera-benchmark-data'), true,  (string) $edit_row['workload']);
                $this->render_text_input('dataset_size',   __('Dataset Size',               'chimera-benchmark-data'), true,  (string) $edit_row['dataset_size']);
                $this->render_text_input('engine',         __('Engine',                     'chimera-benchmark-data'), true,  (string) $edit_row['engine']);
                $this->render_text_input('environment',    __('Environment',                'chimera-benchmark-data'), false, (string) $edit_row['environment']);
                $this->render_text_input('metric_name',    __('Metric Name',                'chimera-benchmark-data'), true,  (string) $edit_row['metric_name']);
                $this->render_text_input('metric_unit',    __('Metric Unit',                'chimera-benchmark-data'), true,  (string) $edit_row['metric_unit']);
                $this->render_number_input('metric_value', __('Metric Value',               'chimera-benchmark-data'), true,  '0.000001', (string) $edit_row['metric_value']);
                $this->render_text_input('run_at',         __('Run At (YYYY-MM-DD HH:MM:SS)', 'chimera-benchmark-data'), false, (string) ($edit_row['run_at'] ?? ''));
                echo '<tr><th><label for="chimera_notes_edit">'   . esc_html__('Notes',         'chimera-benchmark-data') . '</label></th><td><textarea id="chimera_notes_edit"    name="notes"    rows="3" class="large-text">' . esc_textarea((string) $edit_row['notes'])    . '</textarea></td></tr>';
                echo '<tr><th><label for="chimera_metadata_edit">' . esc_html__('Metadata (JSON)', 'chimera-benchmark-data') . '</label></th><td><textarea id="chimera_metadata_edit" name="metadata" rows="3" class="large-text">' . esc_textarea((string) $edit_row['metadata']) . '</textarea></td></tr>';
                echo '</table>';
                submit_button(__('Benchmark aktualisieren', 'chimera-benchmark-data'), 'primary', 'submit', false);
                echo ' <a class="button" href="' . esc_url(add_query_arg('tab', 'add', $base_url)) . '">' . esc_html__('Bearbeiten abbrechen', 'chimera-benchmark-data') . '</a>';
                echo '</form>';
                echo '<hr>';
                echo '<h2>' . esc_html__('Neuen Benchmark anlegen', 'chimera-benchmark-data') . '</h2>';
            } else {
                echo '<h2>' . esc_html__('Neuen Benchmark anlegen', 'chimera-benchmark-data') . '</h2>';
            }

            // ── Add new record ────────────────────────────────────────────
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            wp_nonce_field('chimera_benchmark_add');
            echo '<input type="hidden" name="action" value="chimera_benchmark_add">';
            echo '<table class="form-table">';
            $this->render_text_input('benchmark_name', __('Benchmark Name',               'chimera-benchmark-data'), true);
            $this->render_text_input('workload',       __('Workload',                     'chimera-benchmark-data'), true);
            $this->render_text_input('dataset_size',   __('Dataset Size',                 'chimera-benchmark-data'), true);
            $this->render_text_input('engine',         __('Engine',                       'chimera-benchmark-data'), true);
            $this->render_text_input('environment',    __('Environment',                  'chimera-benchmark-data'), false);
            $this->render_text_input('metric_name',    __('Metric Name',                  'chimera-benchmark-data'), true);
            $this->render_text_input('metric_unit',    __('Metric Unit',                  'chimera-benchmark-data'), true);
            $this->render_number_input('metric_value', __('Metric Value',                 'chimera-benchmark-data'), true, '0.000001');
            $this->render_text_input('run_at',         __('Run At (YYYY-MM-DD HH:MM:SS)', 'chimera-benchmark-data'), false);
            echo '<tr><th><label for="chimera_notes">'    . esc_html__('Notes',         'chimera-benchmark-data') . '</label></th><td><textarea id="chimera_notes"    name="notes"    rows="3" class="large-text"></textarea></td></tr>';
            echo '<tr><th><label for="chimera_metadata">' . esc_html__('Metadata (JSON)', 'chimera-benchmark-data') . '</label></th><td><textarea id="chimera_metadata" name="metadata" rows="3" class="large-text" placeholder="{&quot;nodes&quot;: 3}"></textarea></td></tr>';
            echo '</table>';
            submit_button(__('Benchmark speichern', 'chimera-benchmark-data'));
            echo '</form>';
            echo '</div>';
        }

        // ════════════════════════════════════════════════════════════════════
        // TAB: Import / Export
        // ════════════════════════════════════════════════════════════════════
        if ($active_tab === 'import') {
            echo '<div class="chimera-tab-content">';

            echo '<h2>' . esc_html__('CSV Import', 'chimera-benchmark-data') . '</h2>';
            echo '<p>' . esc_html__('Erwartete Header: benchmark_name, workload, dataset_size, engine, environment, metric_name, metric_unit, metric_value, run_at, notes, metadata', 'chimera-benchmark-data') . '</p>';
            echo '<form method="post" enctype="multipart/form-data" action="' . esc_url(admin_url('admin-post.php')) . '">';
            wp_nonce_field('chimera_benchmark_import_csv');
            echo '<input type="hidden" name="action" value="chimera_benchmark_import_csv">';
            echo '<p><input type="file" name="chimera_csv" accept=".csv,text/csv" required> ';
            submit_button(__('CSV importieren', 'chimera-benchmark-data'), 'secondary', 'submit', false);
            echo '</p>';
            echo '</form>';

            echo '<hr>';
            echo '<h2>' . esc_html__('CSV Export', 'chimera-benchmark-data') . '</h2>';
            echo '<p>' . esc_html__('Optional: Datensätze vor dem Export filtern.', 'chimera-benchmark-data') . '</p>';
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            wp_nonce_field('chimera_benchmark_export_csv');
            echo '<input type="hidden" name="action" value="chimera_benchmark_export_csv">';
            echo '<p>';
            echo '<input type="text" name="workload"     placeholder="' . esc_attr__('Filter Workload',     'chimera-benchmark-data') . '"> ';
            echo '<input type="text" name="engine"       placeholder="' . esc_attr__('Filter Engine',       'chimera-benchmark-data') . '"> ';
            echo '<input type="text" name="metric_name"  placeholder="' . esc_attr__('Filter Metric Name',  'chimera-benchmark-data') . '"> ';
            submit_button(__('CSV exportieren', 'chimera-benchmark-data'), 'secondary', 'submit', false);
            echo '</p>';
            echo '</form>';
            echo '</div>';
        }

        // ── Shared tab-panel styles ──────────────────────────────────────────
        echo '<style>.chimera-tab-content{margin-top:16px;}</style>';
        echo '</div>'; // .wrap
    }

    private function render_text_input($name, $label, $required, $value = '') {
        $id = 'chimera_' . $name;
        echo '<tr><th><label for="' . esc_attr($id) . '">' . esc_html($label) . '</label></th><td>';
        echo '<input type="text" class="regular-text" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" value="' . esc_attr((string) $value) . '" ' . ($required ? 'required' : '') . '>';
        echo '</td></tr>';
    }

    private function render_number_input($name, $label, $required, $step = 'any', $value = '') {
        $id = 'chimera_' . $name;
        echo '<tr><th><label for="' . esc_attr($id) . '">' . esc_html($label) . '</label></th><td>';
        echo '<input type="number" class="regular-text" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" value="' . esc_attr((string) $value) . '" step="' . esc_attr($step) . '" ' . ($required ? 'required' : '') . '>';
        echo '</td></tr>';
    }

    private function get_record_by_id($id) {
        global $wpdb;

        $table = $this->table_name();
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE id = %d", intval($id)),
            ARRAY_A
        );
    }

    public function handle_add_benchmark() {
        if (!current_user_can('manage_options')) {
            wp_die('Forbidden');
        }

        check_admin_referer('chimera_benchmark_add');

        $record = $this->normalize_record($_POST);
        if (is_wp_error($record)) {
            $this->redirect_admin('error');
        }

        global $wpdb;
        $table = $this->table_name();
        $ok = $wpdb->insert($table, $record, array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s'));

        $this->redirect_admin($ok ? 'created' : 'error', $ok ? 'add' : 'list');
    }

    public function handle_delete_benchmark() {
        if (!current_user_can('manage_options')) {
            wp_die('Forbidden');
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        check_admin_referer('chimera_benchmark_delete_' . $id);

        if ($id <= 0) {
            $this->redirect_admin('error');
        }

        global $wpdb;
        $table = $this->table_name();
        $ok = $wpdb->delete($table, array('id' => $id), array('%d'));

        $this->redirect_admin($ok !== false ? 'deleted' : 'error', 'list');
    }

    public function handle_bulk_action() {
        if (!current_user_can('manage_options')) {
            wp_die('Forbidden');
        }

        check_admin_referer('chimera_benchmark_bulk_action');

        $bulk_action = isset($_POST['bulk_action']) ? sanitize_key(wp_unslash($_POST['bulk_action'])) : '';
        if ($bulk_action === '' || $bulk_action === '-1') {
            $bulk_action = isset($_POST['bulk_action2']) ? sanitize_key(wp_unslash($_POST['bulk_action2'])) : '';
        }
        $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? array_map('intval', $_POST['ids']) : array();
        $ids = array_values(array_filter(array_unique($ids), function ($id) {
            return $id > 0;
        }));

        if ($bulk_action === '' || $bulk_action === '-1' || empty($ids)) {
            $this->redirect_admin('error');
        }

        global $wpdb;
        $table = $this->table_name();
        $affected = 0;
        $redirect_args = array(
            'page'               => 'chimera-benchmark-data',
            'tab'                => 'list',
            'filter_workload'    => sanitize_text_field(wp_unslash($_POST['filter_workload'] ?? '')),
            'filter_engine'      => sanitize_text_field(wp_unslash($_POST['filter_engine'] ?? '')),
            'filter_metric_name' => sanitize_text_field(wp_unslash($_POST['filter_metric_name'] ?? '')),
            'orderby'            => sanitize_key(wp_unslash($_POST['orderby'] ?? 'run_at')),
            'order'              => strtoupper(sanitize_text_field(wp_unslash($_POST['order'] ?? 'DESC'))),
            'per_page'           => max(10, min(200, intval($_POST['per_page'] ?? 50))),
            'paged'              => max(1, intval($_POST['paged'] ?? 1)),
        );

        if ($bulk_action === 'delete') {
            $placeholders = implode(',', array_fill(0, count($ids), '%d'));
            $sql = "DELETE FROM $table WHERE id IN ($placeholders)";
            $result = $wpdb->query($wpdb->prepare($sql, $ids));
            if ($result === false) {
                $this->redirect_admin('error');
            }
            $affected = intval($result);

            wp_safe_redirect(add_query_arg(array_merge($redirect_args, array(
                'chimera_notice' => 'bulk_deleted',
                'affected' => $affected,
            )), admin_url('admin.php')));
            exit;
        }

        if ($bulk_action === 'set_field' || $bulk_action === 'set_engine') {
            $allowed_fields = array('engine', 'workload', 'metric_name', 'environment');
            $bulk_field = $bulk_action === 'set_engine'
                ? 'engine'
                : sanitize_key(wp_unslash($_POST['bulk_field'] ?? ''));
            $bulk_value = sanitize_text_field(wp_unslash($_POST['bulk_value'] ?? ($_POST['bulk_engine'] ?? '')));

            if (!in_array($bulk_field, $allowed_fields, true) || $bulk_value === '') {
                $this->redirect_admin('error');
            }

            $placeholders = implode(',', array_fill(0, count($ids), '%d'));
            $sql = "UPDATE $table SET {$bulk_field} = %s WHERE id IN ($placeholders)";
            $params = array_merge(array($bulk_value), $ids);
            $result = $wpdb->query($wpdb->prepare($sql, $params));
            if ($result === false) {
                $this->redirect_admin('error');
            }
            $affected = intval($result);

            wp_safe_redirect(add_query_arg(array_merge($redirect_args, array(
                'chimera_notice' => 'bulk_updated',
                'affected' => $affected,
            )), admin_url('admin.php')));
            exit;
        }

        $this->redirect_admin('error');
    }

    public function handle_export_csv() {
        if (!current_user_can('manage_options')) {
            wp_die('Forbidden');
        }

        check_admin_referer('chimera_benchmark_export_csv');

        $filters = array(
            'workload' => sanitize_text_field(wp_unslash($_POST['workload'] ?? '')),
            'engine' => sanitize_text_field(wp_unslash($_POST['engine'] ?? '')),
            'metric_name' => sanitize_text_field(wp_unslash($_POST['metric_name'] ?? '')),
        );

        $rows = $this->query_records($filters, 5000, 0);

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=chimera-benchmarks-' . gmdate('Ymd-His') . '.csv');

        $out = fopen('php://output', 'w');
        if ($out === false) {
            exit;
        }

        fputcsv($out, array('id', 'benchmark_name', 'workload', 'dataset_size', 'engine', 'environment', 'metric_name', 'metric_unit', 'metric_value', 'run_at', 'notes', 'metadata', 'created_at', 'updated_at'));
        foreach ($rows as $row) {
            fputcsv($out, array(
                $row['id'],
                $row['benchmark_name'],
                $row['workload'],
                $row['dataset_size'],
                $row['engine'],
                $row['environment'],
                $row['metric_name'],
                $row['metric_unit'],
                $row['metric_value'],
                $row['run_at'],
                $row['notes'],
                $row['metadata'],
                $row['created_at'],
                $row['updated_at'],
            ));
        }

        fclose($out);
        exit;
    }

    public function handle_update_benchmark() {
        if (!current_user_can('manage_options')) {
            wp_die('Forbidden');
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        check_admin_referer('chimera_benchmark_update_' . $id);

        if ($id <= 0) {
            $this->redirect_admin('error');
        }

        $record = $this->normalize_record($_POST);
        if (is_wp_error($record)) {
            $this->redirect_admin('error');
        }

        global $wpdb;
        $table = $this->table_name();
        $ok = $wpdb->update(
            $table,
            $record,
            array('id' => $id),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s'),
            array('%d')
        );

        $this->redirect_admin($ok !== false ? 'updated' : 'error', 'add');
    }

    public function handle_quick_update() {
        if (!current_user_can('manage_options')) {
            wp_die('Forbidden');
        }

        check_admin_referer('chimera_benchmark_quick_update');

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id <= 0) {
            $this->redirect_admin('error');
        }

        $workload = sanitize_text_field(wp_unslash($_POST['workload'] ?? ''));
        $engine = sanitize_text_field(wp_unslash($_POST['engine'] ?? ''));
        $metric_name = sanitize_text_field(wp_unslash($_POST['metric_name'] ?? ''));
        $dataset_size = sanitize_text_field(wp_unslash($_POST['dataset_size'] ?? ''));
        $environment = sanitize_text_field(wp_unslash($_POST['environment'] ?? ''));
        if ($workload === '' || $engine === '' || $metric_name === '' || $dataset_size === '') {
            $this->redirect_admin('error');
        }

        global $wpdb;
        $table = $this->table_name();
        $ok = $wpdb->update(
            $table,
            array(
                'workload' => $workload,
                'engine' => $engine,
                'metric_name' => $metric_name,
                'dataset_size' => $dataset_size,
                'environment' => $environment,
            ),
            array('id' => $id),
            array('%s', '%s', '%s', '%s', '%s'),
            array('%d')
        );

        wp_safe_redirect(add_query_arg(array(
            'page'               => 'chimera-benchmark-data',
            'tab'                => 'list',
            'chimera_notice'     => $ok !== false ? 'quick_updated' : 'error',
            'filter_workload'    => sanitize_text_field(wp_unslash($_POST['filter_workload'] ?? '')),
            'filter_engine'      => sanitize_text_field(wp_unslash($_POST['filter_engine'] ?? '')),
            'filter_metric_name' => sanitize_text_field(wp_unslash($_POST['filter_metric_name'] ?? '')),
            'orderby'            => sanitize_key(wp_unslash($_POST['orderby'] ?? 'run_at')),
            'order'              => strtoupper(sanitize_text_field(wp_unslash($_POST['order'] ?? 'DESC'))),
            'per_page'           => max(10, min(200, intval($_POST['per_page'] ?? 50))),
            'paged'              => max(1, intval($_POST['paged'] ?? 1)),
        ), admin_url('admin.php')));
        exit;
    }

    public function handle_import_csv() {
        if (!current_user_can('manage_options')) {
            wp_die('Forbidden');
        }

        check_admin_referer('chimera_benchmark_import_csv');

        if (empty($_FILES['chimera_csv']['tmp_name'])) {
            $this->redirect_admin('error');
        }

        $tmp_name = $_FILES['chimera_csv']['tmp_name'];
        $handle = fopen($tmp_name, 'r');
        if ($handle === false) {
            $this->redirect_admin('error');
        }

        $headers = fgetcsv($handle);
        if (!is_array($headers) || empty($headers)) {
            fclose($handle);
            $this->redirect_admin('error');
        }

        $headers = array_map(function ($header) {
            return sanitize_key((string) $header);
        }, $headers);

        $inserted = 0;
        $failed = 0;

        global $wpdb;
        $table = $this->table_name();

        while (($row = fgetcsv($handle)) !== false) {
            if (!is_array($row) || empty(array_filter($row, 'strlen'))) {
                continue;
            }

            $assoc = array();
            foreach ($headers as $index => $header) {
                $assoc[$header] = isset($row[$index]) ? $row[$index] : '';
            }

            $record = $this->normalize_record($assoc);
            if (is_wp_error($record)) {
                $failed++;
                continue;
            }

            $ok = $wpdb->insert($table, $record, array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s'));
            if ($ok) {
                $inserted++;
            } else {
                $failed++;
            }
        }

        fclose($handle);

        wp_safe_redirect(add_query_arg(array(
            'page'           => 'chimera-benchmark-data',
            'tab'            => 'import',
            'chimera_notice' => 'imported',
            'imported'       => $inserted,
            'failed'         => $failed,
        ), admin_url('admin.php')));
        exit;
    }

    private function normalize_record($input) {
        $benchmark_name = sanitize_text_field(wp_unslash($input['benchmark_name'] ?? ''));
        $workload = sanitize_text_field(wp_unslash($input['workload'] ?? ''));
        $dataset_size = sanitize_text_field(wp_unslash($input['dataset_size'] ?? ''));
        $engine = sanitize_text_field(wp_unslash($input['engine'] ?? ''));
        $environment = sanitize_text_field(wp_unslash($input['environment'] ?? ''));
        $metric_name = sanitize_text_field(wp_unslash($input['metric_name'] ?? ''));
        $metric_unit = sanitize_text_field(wp_unslash($input['metric_unit'] ?? ''));
        $metric_value_raw = wp_unslash($input['metric_value'] ?? '');
        $notes = sanitize_textarea_field(wp_unslash($input['notes'] ?? ''));
        $metadata = trim((string) wp_unslash($input['metadata'] ?? ''));

        if ($benchmark_name === '' || $workload === '' || $dataset_size === '' || $engine === '' || $metric_name === '' || $metric_unit === '' || $metric_value_raw === '') {
            return new WP_Error('missing_required', 'Required fields missing');
        }

        if (!is_numeric($metric_value_raw)) {
            return new WP_Error('invalid_metric_value', 'metric_value must be numeric');
        }

        $run_at = trim((string) wp_unslash($input['run_at'] ?? ''));
        if ($run_at !== '') {
            $timestamp = strtotime($run_at);
            if ($timestamp === false) {
                return new WP_Error('invalid_run_at', 'run_at is invalid');
            }
            $run_at = gmdate('Y-m-d H:i:s', $timestamp);
        } else {
            $run_at = null;
        }

        if ($metadata !== '') {
            json_decode($metadata, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return new WP_Error('invalid_metadata', 'metadata must be valid JSON');
            }
        } else {
            $metadata = null;
        }

        return array(
            'benchmark_name' => $benchmark_name,
            'workload' => $workload,
            'dataset_size' => $dataset_size,
            'engine' => $engine,
            'environment' => $environment,
            'metric_name' => $metric_name,
            'metric_unit' => $metric_unit,
            'metric_value' => floatval($metric_value_raw),
            'run_at' => $run_at,
            'notes' => $notes,
            'metadata' => $metadata,
        );
    }

    private function redirect_admin($notice, $tab = 'list') {
        wp_safe_redirect(add_query_arg(array(
            'page'           => 'chimera-benchmark-data',
            'tab'            => sanitize_key($tab),
            'chimera_notice' => sanitize_key($notice),
        ), admin_url('admin.php')));
        exit;
    }

    private function rest_write_permission($request) {
        return current_user_can('manage_options');
    }

    private function request_to_record_input(WP_REST_Request $request) {
        $data = $request->get_json_params();
        if (!is_array($data) || empty($data)) {
            $data = $request->get_params();
        }

        $fields = array('benchmark_name', 'workload', 'dataset_size', 'engine', 'environment', 'metric_name', 'metric_unit', 'metric_value', 'run_at', 'notes', 'metadata');
        $output = array();

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $value = $data[$field];
                if (is_array($value)) {
                    $value = wp_json_encode($value);
                }
                $output[$field] = (string) $value;
            }
        }

        return $output;
    }

    public function register_rest_routes() {
        register_rest_route('chimera-benchmark/v1', '/records', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'rest_get_records'),
            'permission_callback' => '__return_true',
            'args' => array(
                'workload' => array('type' => 'string', 'required' => false),
                'engine' => array('type' => 'string', 'required' => false),
                'metric_name' => array('type' => 'string', 'required' => false),
                'limit' => array('type' => 'integer', 'required' => false, 'default' => 100),
                'page' => array('type' => 'integer', 'required' => false, 'default' => 1),
                'per_page' => array('type' => 'integer', 'required' => false, 'default' => 100),
                'orderby' => array('type' => 'string', 'required' => false, 'default' => 'run_at'),
                'order' => array('type' => 'string', 'required' => false, 'default' => 'DESC'),
            ),
        ));

        register_rest_route('chimera-benchmark/v1', '/records', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'rest_create_record'),
            'permission_callback' => array($this, 'rest_write_permission'),
        ));

        register_rest_route('chimera-benchmark/v1', '/records/(?P<id>\d+)', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'rest_get_record'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route('chimera-benchmark/v1', '/records/(?P<id>\d+)', array(
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => array($this, 'rest_update_record'),
            'permission_callback' => array($this, 'rest_write_permission'),
        ));

        register_rest_route('chimera-benchmark/v1', '/records/(?P<id>\d+)', array(
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => array($this, 'rest_delete_record'),
            'permission_callback' => array($this, 'rest_write_permission'),
        ));

        register_rest_route('chimera-benchmark/v1', '/records/bulk', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'rest_bulk_action'),
            'permission_callback' => array($this, 'rest_write_permission'),
        ));
    }

    private function query_records($filters = array(), $limit = 1000, $offset = 0, $orderby = 'run_at', $order = 'DESC') {
        global $wpdb;
        $table = $this->table_name();

        $where = array('1=1');
        $params = array();

        $workload = sanitize_text_field((string) ($filters['workload'] ?? ''));
        $engine = sanitize_text_field((string) ($filters['engine'] ?? ''));
        $metric_name = sanitize_text_field((string) ($filters['metric_name'] ?? ''));

        if ($workload !== '') {
            $where[] = 'workload = %s';
            $params[] = $workload;
        }
        if ($engine !== '') {
            $where[] = 'engine = %s';
            $params[] = $engine;
        }
        if ($metric_name !== '') {
            $where[] = 'metric_name = %s';
            $params[] = $metric_name;
        }

        $safe_limit = max(1, min(10000, intval($limit)));
        $safe_offset = max(0, intval($offset));

        $allowed_orderby = array('id', 'benchmark_name', 'workload', 'dataset_size', 'engine', 'environment', 'metric_name', 'metric_unit', 'metric_value', 'run_at', 'created_at', 'updated_at');
        $safe_orderby = in_array($orderby, $allowed_orderby, true) ? $orderby : 'run_at';
        $safe_order = strtoupper((string) $order) === 'ASC' ? 'ASC' : 'DESC';

        if ($safe_orderby === 'run_at') {
            $order_sql = "COALESCE(run_at, created_at) {$safe_order}";
        } else {
            $order_sql = "{$safe_orderby} {$safe_order}";
        }

        $sql = "SELECT * FROM $table WHERE " . implode(' AND ', $where) . " ORDER BY {$order_sql} LIMIT %d OFFSET %d";
        $params[] = $safe_limit;
        $params[] = $safe_offset;

        return $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
    }

    private function count_records($filters = array()) {
        global $wpdb;
        $table = $this->table_name();

        $where = array('1=1');
        $params = array();

        $workload = sanitize_text_field((string) ($filters['workload'] ?? ''));
        $engine = sanitize_text_field((string) ($filters['engine'] ?? ''));
        $metric_name = sanitize_text_field((string) ($filters['metric_name'] ?? ''));

        if ($workload !== '') {
            $where[] = 'workload = %s';
            $params[] = $workload;
        }
        if ($engine !== '') {
            $where[] = 'engine = %s';
            $params[] = $engine;
        }
        if ($metric_name !== '') {
            $where[] = 'metric_name = %s';
            $params[] = $metric_name;
        }

        $sql = "SELECT COUNT(*) FROM $table WHERE " . implode(' AND ', $where);
        return intval($wpdb->get_var($wpdb->prepare($sql, $params)));
    }

    public function rest_get_records(WP_REST_Request $request) {
        $page = max(1, intval($request->get_param('page')));
        $per_page_param = $request->get_param('per_page');
        if ($per_page_param === null || $per_page_param === '') {
            $per_page_param = $request->get_param('limit');
        }
        $per_page = max(1, min(1000, intval($per_page_param)));
        $offset = ($page - 1) * $per_page;

        $filters = array(
            'workload' => (string) $request->get_param('workload'),
            'engine' => (string) $request->get_param('engine'),
            'metric_name' => (string) $request->get_param('metric_name'),
        );

        $orderby = sanitize_key((string) $request->get_param('orderby'));
        $order = strtoupper(sanitize_text_field((string) $request->get_param('order')));

        $records = $this->query_records(array(
            'workload' => $filters['workload'],
            'engine' => $filters['engine'],
            'metric_name' => $filters['metric_name'],
        ), $per_page, $offset, $orderby, $order);

        $total = $this->count_records($filters);
        $total_pages = max(1, (int) ceil($total / $per_page));

        $base_params = array(
            'workload' => $filters['workload'],
            'engine' => $filters['engine'],
            'metric_name' => $filters['metric_name'],
            'per_page' => $per_page,
            'orderby' => $orderby === '' ? 'run_at' : $orderby,
            'order' => $order === 'ASC' ? 'ASC' : 'DESC',
        );

        $first_link = add_query_arg(array_merge($base_params, array('page' => 1)), rest_url('chimera-benchmark/v1/records'));
        $last_link = add_query_arg(array_merge($base_params, array('page' => $total_pages)), rest_url('chimera-benchmark/v1/records'));
        $prev_link = $page > 1
            ? add_query_arg(array_merge($base_params, array('page' => $page - 1)), rest_url('chimera-benchmark/v1/records'))
            : null;
        $next_link = $page < $total_pages
            ? add_query_arg(array_merge($base_params, array('page' => $page + 1)), rest_url('chimera-benchmark/v1/records'))
            : null;

        return rest_ensure_response(array(
            'count' => count($records),
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => $total_pages,
            'orderby' => $orderby === '' ? 'run_at' : $orderby,
            'order' => $order === 'ASC' ? 'ASC' : 'DESC',
            'links' => array(
                'first' => $first_link,
                'prev' => $prev_link,
                'next' => $next_link,
                'last' => $last_link,
            ),
            'records' => $records,
        ));
    }

    public function rest_get_record(WP_REST_Request $request) {
        $id = intval($request['id']);
        $record = $this->get_record_by_id($id);

        if (!$record) {
            return new WP_Error('not_found', 'Record not found', array('status' => 404));
        }

        return rest_ensure_response($record);
    }

    public function rest_create_record(WP_REST_Request $request) {
        $input = $this->request_to_record_input($request);
        $record = $this->normalize_record($input);
        if (is_wp_error($record)) {
            return $record;
        }

        global $wpdb;
        $table = $this->table_name();
        $ok = $wpdb->insert($table, $record, array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s'));

        if (!$ok) {
            return new WP_Error('db_insert_failed', 'Record could not be created', array('status' => 500));
        }

        $id = intval($wpdb->insert_id);
        $created = $this->get_record_by_id($id);

        return new WP_REST_Response($created, 201);
    }

    public function rest_update_record(WP_REST_Request $request) {
        $id = intval($request['id']);
        if (!$this->get_record_by_id($id)) {
            return new WP_Error('not_found', 'Record not found', array('status' => 404));
        }

        $input = $this->request_to_record_input($request);
        $record = $this->normalize_record($input);
        if (is_wp_error($record)) {
            return $record;
        }

        global $wpdb;
        $table = $this->table_name();
        $ok = $wpdb->update(
            $table,
            $record,
            array('id' => $id),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s'),
            array('%d')
        );

        if ($ok === false) {
            return new WP_Error('db_update_failed', 'Record could not be updated', array('status' => 500));
        }

        return rest_ensure_response($this->get_record_by_id($id));
    }

    public function rest_delete_record(WP_REST_Request $request) {
        $id = intval($request['id']);
        if (!$this->get_record_by_id($id)) {
            return new WP_Error('not_found', 'Record not found', array('status' => 404));
        }

        global $wpdb;
        $table = $this->table_name();
        $ok = $wpdb->delete($table, array('id' => $id), array('%d'));

        if ($ok === false) {
            return new WP_Error('db_delete_failed', 'Record could not be deleted', array('status' => 500));
        }

        return rest_ensure_response(array('deleted' => true, 'id' => $id));
    }

    public function rest_bulk_action(WP_REST_Request $request) {
        $payload = $request->get_json_params();
        if (!is_array($payload)) {
            return new WP_Error('invalid_payload', 'JSON payload required', array('status' => 400));
        }

        $operation = sanitize_key((string) ($payload['operation'] ?? ''));
        $ids = isset($payload['ids']) && is_array($payload['ids']) ? array_map('intval', $payload['ids']) : array();
        $ids = array_values(array_filter(array_unique($ids), function ($id) {
            return $id > 0;
        }));

        if ($operation === '' || empty($ids)) {
            return new WP_Error('invalid_bulk_request', 'operation and ids are required', array('status' => 400));
        }

        global $wpdb;
        $table = $this->table_name();
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));

        if ($operation === 'delete') {
            $sql = "DELETE FROM $table WHERE id IN ($placeholders)";
            $result = $wpdb->query($wpdb->prepare($sql, $ids));
            if ($result === false) {
                return new WP_Error('db_delete_failed', 'Bulk delete failed', array('status' => 500));
            }

            return rest_ensure_response(array(
                'operation' => 'delete',
                'affected' => intval($result),
            ));
        }

        if ($operation === 'update') {
            $allowed_fields = array('engine', 'workload', 'metric_name', 'environment');
            $field = sanitize_key((string) ($payload['field'] ?? ''));
            $value = sanitize_text_field((string) ($payload['value'] ?? ''));

            if (!in_array($field, $allowed_fields, true) || $value === '') {
                return new WP_Error('invalid_update_payload', 'field/value invalid', array('status' => 400));
            }

            $sql = "UPDATE $table SET {$field} = %s WHERE id IN ($placeholders)";
            $params = array_merge(array($value), $ids);
            $result = $wpdb->query($wpdb->prepare($sql, $params));
            if ($result === false) {
                return new WP_Error('db_update_failed', 'Bulk update failed', array('status' => 500));
            }

            return rest_ensure_response(array(
                'operation' => 'update',
                'field' => $field,
                'affected' => intval($result),
            ));
        }

        return new WP_Error('unsupported_operation', 'Unsupported bulk operation', array('status' => 400));
    }

    public function render_shortcode_table($atts) {
        $atts = shortcode_atts(array(
            'workload' => '',
            'engine' => '',
            'metric_name' => '',
            'limit' => 50,
        ), $atts, 'chimera_benchmark_table');

        global $wpdb;
        $table = $this->table_name();

        $where = array('1=1');
        $params = array();

        if ($atts['workload'] !== '') {
            $where[] = 'workload = %s';
            $params[] = sanitize_text_field($atts['workload']);
        }
        if ($atts['engine'] !== '') {
            $where[] = 'engine = %s';
            $params[] = sanitize_text_field($atts['engine']);
        }
        if ($atts['metric_name'] !== '') {
            $where[] = 'metric_name = %s';
            $params[] = sanitize_text_field($atts['metric_name']);
        }

        $limit = max(1, min(500, intval($atts['limit'])));
        $sql = "SELECT * FROM $table WHERE " . implode(' AND ', $where) . " ORDER BY COALESCE(run_at, created_at) DESC LIMIT %d";
        $params[] = $limit;

        $rows = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);

        ob_start();
        echo '<div class="chimera-benchmark-table-wrap">';

        if (empty($rows)) {
            echo '<p>' . esc_html__('Keine Chimera Benchmark-Daten gefunden.', 'chimera-benchmark-data') . '</p>';
        } else {
            echo '<table class="chimera-benchmark-table" style="width:100%;border-collapse:collapse;">';
            echo '<thead><tr>';
            echo '<th style="text-align:left;border-bottom:1px solid #ddd;padding:8px;">Benchmark</th>';
            echo '<th style="text-align:left;border-bottom:1px solid #ddd;padding:8px;">Workload</th>';
            echo '<th style="text-align:left;border-bottom:1px solid #ddd;padding:8px;">Dataset</th>';
            echo '<th style="text-align:left;border-bottom:1px solid #ddd;padding:8px;">Engine</th>';
            echo '<th style="text-align:left;border-bottom:1px solid #ddd;padding:8px;">Metric</th>';
            echo '<th style="text-align:right;border-bottom:1px solid #ddd;padding:8px;">Value</th>';
            echo '<th style="text-align:left;border-bottom:1px solid #ddd;padding:8px;">Run At</th>';
            echo '</tr></thead><tbody>';

            foreach ($rows as $row) {
                echo '<tr>';
                echo '<td style="border-bottom:1px solid #eee;padding:8px;">' . esc_html($row['benchmark_name']) . '</td>';
                echo '<td style="border-bottom:1px solid #eee;padding:8px;">' . esc_html($row['workload']) . '</td>';
                echo '<td style="border-bottom:1px solid #eee;padding:8px;">' . esc_html($row['dataset_size']) . '</td>';
                echo '<td style="border-bottom:1px solid #eee;padding:8px;">' . esc_html($row['engine']) . '</td>';
                echo '<td style="border-bottom:1px solid #eee;padding:8px;">' . esc_html($row['metric_name'] . ' (' . $row['metric_unit'] . ')') . '</td>';
                echo '<td style="border-bottom:1px solid #eee;padding:8px;text-align:right;">' . esc_html((string) $row['metric_value']) . '</td>';
                echo '<td style="border-bottom:1px solid #eee;padding:8px;">' . esc_html((string) ($row['run_at'] ?: '-')) . '</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        }

        echo '</div>';
        return ob_get_clean();
    }

    public function render_shortcode_chart($atts) {
        $atts = shortcode_atts(array(
            'workload' => '',
            'engine' => '',
            'metric_name' => '',
            'limit' => 20,
            'chart_type' => 'bar',
            'label_field' => 'engine',
            'group_by' => '',
            'order' => 'DESC',
        ), $atts, 'chimera_benchmark_chart');

        $label_field = sanitize_key((string) $atts['label_field']);
        $allowed_label_fields = array('engine', 'workload', 'benchmark_name', 'dataset_size', 'run_at');
        if (!in_array($label_field, $allowed_label_fields, true)) {
            $label_field = 'engine';
        }

        $chart_type = sanitize_key((string) $atts['chart_type']);
        if (!in_array($chart_type, array('bar', 'line', 'radar'), true)) {
            $chart_type = 'bar';
        }

        $group_by = sanitize_key((string) $atts['group_by']);
        if ($group_by !== '' && !in_array($group_by, $allowed_label_fields, true)) {
            $group_by = '';
        }

        $query_args = array(
            'per_page' => max(1, min(200, intval($atts['limit']))),
            'page' => 1,
            'orderby' => 'run_at',
            'order' => strtoupper((string) $atts['order']) === 'ASC' ? 'ASC' : 'DESC',
        );

        if ($atts['workload'] !== '') {
            $query_args['workload'] = sanitize_text_field((string) $atts['workload']);
        }
        if ($atts['engine'] !== '') {
            $query_args['engine'] = sanitize_text_field((string) $atts['engine']);
        }
        if ($atts['metric_name'] !== '') {
            $query_args['metric_name'] = sanitize_text_field((string) $atts['metric_name']);
        }

        $rest_url = add_query_arg($query_args, rest_url('chimera-benchmark/v1/records'));
        $canvas_id = 'chimera-benchmark-chart-' . wp_rand(1000, 999999);

        ob_start();
        echo '<div class="chimera-benchmark-chart-wrap">';
        echo '<canvas id="' . esc_attr($canvas_id) . '" height="300"></canvas>';
        echo '</div>';

        $config = array(
            'canvasId' => $canvas_id,
            'endpoint' => esc_url_raw($rest_url),
            'chartType' => $chart_type,
            'labelField' => $label_field,
            'groupBy' => $group_by,
            'metricName' => sanitize_text_field((string) $atts['metric_name']),
        );

        echo '<script>(function(){';
        echo 'var cfg=' . wp_json_encode($config) . ';';
        echo 'if(typeof Chart==="undefined"){return;}';
        echo 'var canvas=document.getElementById(cfg.canvasId);if(!canvas){return;}';
        echo 'fetch(cfg.endpoint,{credentials:"same-origin"}).then(function(r){return r.json();}).then(function(payload){';
        echo 'var rows=(payload&&payload.records)?payload.records:[];';
        echo 'var labels=[];var values=[];';
        echo 'if(cfg.groupBy&&cfg.groupBy!=""){var grouped={};for(var i=0;i<rows.length;i++){var row=rows[i]||{};var key=String(row[cfg.groupBy]||("#"+(row.id||i+1)));var val=parseFloat(row.metric_value||0);if(!grouped[key]){grouped[key]={sum:0,count:0};}grouped[key].sum+=val;grouped[key].count+=1;}for(var key in grouped){if(!grouped.hasOwnProperty(key)){continue;}labels.push(key);values.push(grouped[key].sum/grouped[key].count);}}else{for(var j=0;j<rows.length;j++){var r=rows[j]||{};labels.push(String(r[cfg.labelField]||("#"+(r.id||j+1))));values.push(parseFloat(r.metric_value||0));}}';
        echo 'var metricLabel=cfg.metricName&&cfg.metricName!==""?cfg.metricName:"metric_value";';
        echo 'new Chart(canvas,{type:cfg.chartType,data:{labels:labels,datasets:[{label:metricLabel,data:values,backgroundColor:"rgba(20,115,230,0.35)",borderColor:"rgba(20,115,230,1)",borderWidth:2,tension:0.25}]},options:{responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true}}}});';
        echo '}).catch(function(){var p=document.createElement("p");p.textContent="Chart data konnte nicht geladen werden.";canvas.parentNode.appendChild(p);});';
        echo '}());</script>';

        return ob_get_clean();
    }
}

Chimera_Benchmark_Data_Plugin::instance();
