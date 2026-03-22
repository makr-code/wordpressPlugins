<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Chimera_Benchmark_List_Table extends WP_List_Table {

    /** @var string */
    private $table_name;

    /** @var array */
    private $filters;

    /** @var int */
    private $per_page;

    public function __construct($table_name, $filters = array(), $per_page = 50) {
        parent::__construct(array(
            'singular' => 'chimera_benchmark',
            'plural' => 'chimera_benchmarks',
            'ajax' => false,
        ));

        $this->table_name = (string) $table_name;
        $this->filters = array(
            'workload' => sanitize_text_field((string) ($filters['workload'] ?? '')),
            'engine' => sanitize_text_field((string) ($filters['engine'] ?? '')),
            'metric_name' => sanitize_text_field((string) ($filters['metric_name'] ?? '')),
        );
        $this->per_page = max(10, min(200, intval($per_page)));
    }

    public function get_columns() {
        return array(
            'cb' => '<input type="checkbox" />',
            'id' => 'ID',
            'benchmark_name' => __('Benchmark', 'chimera-benchmark-data'),
            'workload' => __('Workload', 'chimera-benchmark-data'),
            'dataset_size' => __('Dataset', 'chimera-benchmark-data'),
            'engine' => __('Engine', 'chimera-benchmark-data'),
            'metric_name' => __('Metric', 'chimera-benchmark-data'),
            'metric_value' => __('Value', 'chimera-benchmark-data'),
            'run_at' => __('Run At', 'chimera-benchmark-data'),
        );
    }

    protected function get_sortable_columns() {
        return array(
            'id' => array('id', false),
            'benchmark_name' => array('benchmark_name', false),
            'workload' => array('workload', false),
            'dataset_size' => array('dataset_size', false),
            'engine' => array('engine', false),
            'metric_name' => array('metric_name', false),
            'metric_value' => array('metric_value', false),
            'run_at' => array('run_at', true),
        );
    }

    protected function get_bulk_actions() {
        return array(
            'delete' => __('Ausgewählte löschen', 'chimera-benchmark-data'),
            'set_field' => __('Feld setzen', 'chimera-benchmark-data'),
        );
    }

    public function current_action() {
        if (isset($_REQUEST['bulk_action']) && $_REQUEST['bulk_action'] !== '-1' && $_REQUEST['bulk_action'] !== '') {
            return sanitize_key(wp_unslash($_REQUEST['bulk_action']));
        }

        if (isset($_REQUEST['bulk_action2']) && $_REQUEST['bulk_action2'] !== '-1' && $_REQUEST['bulk_action2'] !== '') {
            return sanitize_key(wp_unslash($_REQUEST['bulk_action2']));
        }

        return false;
    }

    protected function bulk_actions($which = '') {
        if (is_null($this->_actions)) {
            $this->_actions = $this->get_bulk_actions();
            $this->_actions = apply_filters('bulk_actions-' . $this->screen->id, $this->_actions);
            $two = '';
        } else {
            $two = '2';
        }

        if (empty($this->_actions)) {
            return;
        }

        echo '<label for="bulk-action-selector-' . esc_attr($which) . '" class="screen-reader-text">' . esc_html__('Massenaktion auswählen', 'chimera-benchmark-data') . '</label>';
        echo '<select name="bulk_action' . esc_attr($two) . '" id="bulk-action-selector-' . esc_attr($which) . '">';
        echo '<option value="-1">' . esc_html__('Bulk-Aktion wählen', 'chimera-benchmark-data') . '</option>';

        foreach ($this->_actions as $name => $title) {
            echo '<option value="' . esc_attr($name) . '">' . esc_html($title) . '</option>';
        }

        echo '</select>';

        submit_button(__('Ausführen', 'chimera-benchmark-data'), 'action', 'doaction' . $two, false, array('id' => 'doaction' . $two));
    }

    protected function extra_tablenav($which) {
        if ($which !== 'top') {
            return;
        }

        echo '<div class="alignleft actions">';
        echo '<label for="chimera_bulk_field" class="screen-reader-text">' . esc_html__('Feld', 'chimera-benchmark-data') . '</label>';
        echo '<select id="chimera_bulk_field" name="bulk_field">';
        echo '<option value="engine">engine</option>';
        echo '<option value="workload">workload</option>';
        echo '<option value="metric_name">metric_name</option>';
        echo '<option value="environment">environment</option>';
        echo '</select> ';
        echo '<input type="text" name="bulk_value" placeholder="' . esc_attr__('Neuer Feldwert (für Feld setzen)', 'chimera-benchmark-data') . '">';
        echo '</div>';
    }

    protected function column_cb($item) {
        return '<input type="checkbox" name="ids[]" value="' . intval($item['id']) . '" />';
    }

    protected function column_benchmark_name($item) {
        $edit_url = add_query_arg(array(
            'page' => 'chimera-benchmark-data',
            'edit_id' => intval($item['id']),
            'filter_workload' => $this->filters['workload'],
            'filter_engine' => $this->filters['engine'],
            'filter_metric_name' => $this->filters['metric_name'],
            'per_page' => $this->per_page,
            'paged' => $this->get_pagenum(),
            'orderby' => sanitize_key((string) ($_REQUEST['orderby'] ?? 'run_at')),
            'order' => strtoupper(sanitize_text_field((string) ($_REQUEST['order'] ?? 'DESC'))),
        ), admin_url('admin.php'));

        $delete_form = '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline;">';
        $delete_form .= wp_nonce_field('chimera_benchmark_delete_' . intval($item['id']), '_wpnonce', true, false);
        $delete_form .= '<input type="hidden" name="action" value="chimera_benchmark_delete">';
        $delete_form .= '<input type="hidden" name="id" value="' . intval($item['id']) . '">';
        $delete_form .= '<button type="submit" class="button-link delete" onclick="return confirm(\'' . esc_js(__('Wirklich loeschen?', 'chimera-benchmark-data')) . '\')">' . esc_html__('Loeschen', 'chimera-benchmark-data') . '</button>';
        $delete_form .= '</form>';

        $actions = array(
            'edit' => '<a href="' . esc_url($edit_url) . '">' . esc_html__('Bearbeiten', 'chimera-benchmark-data') . '</a>',
            'quick_edit' => '<a href="#" class="chimera-quick-edit-trigger" data-id="' . intval($item['id']) . '" data-workload="' . esc_attr((string) $item['workload']) . '" data-engine="' . esc_attr((string) $item['engine']) . '" data-metric-name="' . esc_attr((string) $item['metric_name']) . '" data-dataset-size="' . esc_attr((string) $item['dataset_size']) . '" data-environment="' . esc_attr((string) $item['environment']) . '">' . esc_html__('Quick Edit', 'chimera-benchmark-data') . '</a>',
            'delete' => $delete_form,
        );

        return esc_html((string) $item['benchmark_name']) . $this->row_actions($actions);
    }

    protected function column_metric_name($item) {
        return esc_html((string) $item['metric_name'] . ' (' . (string) $item['metric_unit'] . ')');
    }

    protected function column_metric_value($item) {
        return esc_html((string) $item['metric_value']);
    }

    protected function column_default($item, $column_name) {
        return isset($item[$column_name]) ? esc_html((string) $item[$column_name]) : '';
    }

    public function no_items() {
        esc_html_e('Keine Datensaetze vorhanden.', 'chimera-benchmark-data');
    }

    public function prepare_items() {
        global $wpdb;

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $where = array('1=1');
        $params = array();

        if ($this->filters['workload'] !== '') {
            $where[] = 'workload = %s';
            $params[] = $this->filters['workload'];
        }
        if ($this->filters['engine'] !== '') {
            $where[] = 'engine = %s';
            $params[] = $this->filters['engine'];
        }
        if ($this->filters['metric_name'] !== '') {
            $where[] = 'metric_name = %s';
            $params[] = $this->filters['metric_name'];
        }

        $total_sql = "SELECT COUNT(*) FROM {$this->table_name} WHERE " . implode(' AND ', $where);
        $total_items = intval($wpdb->get_var($wpdb->prepare($total_sql, $params)));

        $allowed_orderby = array('id', 'benchmark_name', 'workload', 'dataset_size', 'engine', 'metric_name', 'metric_value', 'run_at', 'created_at');
        $orderby = sanitize_key((string) ($_REQUEST['orderby'] ?? 'run_at'));
        $orderby = in_array($orderby, $allowed_orderby, true) ? $orderby : 'run_at';
        $order = strtoupper(sanitize_text_field((string) ($_REQUEST['order'] ?? 'DESC')));
        $order = $order === 'ASC' ? 'ASC' : 'DESC';

        $paged = $this->get_pagenum();
        $offset = ($paged - 1) * $this->per_page;

        if ($orderby === 'run_at') {
            $order_sql = "COALESCE(run_at, created_at) {$order}";
        } else {
            $order_sql = "{$orderby} {$order}";
        }

        $sql = "SELECT * FROM {$this->table_name} WHERE " . implode(' AND ', $where) . " ORDER BY {$order_sql} LIMIT %d OFFSET %d";
        $query_params = array_merge($params, array($this->per_page, $offset));

        $this->items = $wpdb->get_results($wpdb->prepare($sql, $query_params), ARRAY_A);

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $this->per_page,
            'total_pages' => max(1, (int) ceil($total_items / $this->per_page)),
        ));
    }
}
