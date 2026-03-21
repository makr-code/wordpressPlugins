<?php
/**
 * Advanced Reporting (Phase 5.4)
 *
 * Provides:
 * - Cohort analysis
 * - LTV & CAC tracking
 * - Churn analysis
 * - Product mix analysis
 */

if (!defined('ABSPATH')) {
    exit;
}

class ThemisDB_Advanced_Reporting {

    public static function init() {
        if (is_admin()) {
            add_action('admin_menu', array(__CLASS__, 'register_admin_page'));
        }
        add_shortcode('themisdb_advanced_reporting', array(__CLASS__, 'shortcode'));
    }

    public static function register_admin_page() {
        add_submenu_page(
            'themisdb-order-dashboard',
            __('Advanced Reporting', 'themisdb-order-request'),
            __('Advanced Reporting', 'themisdb-order-request'),
            'manage_options',
            'themisdb-advanced-reporting',
            array(__CLASS__, 'render_admin_page')
        );
    }

    public static function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Keine Berechtigung', 'themisdb-order-request'));
        }

        $cohort = self::get_cohort_analysis(12);
        $ltv_cac = self::get_ltv_cac_tracking(12);
        $churn = self::get_churn_analysis(6);
        $mix = self::get_product_mix_analysis(12);

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Advanced Reporting', 'themisdb-order-request') . '</h1>';

        self::render_table(__('Cohort Analysis (12 months)', 'themisdb-order-request'), $cohort, array(
            'cohort_month' => 'Cohort',
            'customers' => 'Customers',
            'retained_m1' => 'Retained M+1',
            'retained_m3' => 'Retained M+3',
            'retained_m6' => 'Retained M+6',
        ));

        self::render_table(__('LTV & CAC Tracking', 'themisdb-order-request'), $ltv_cac, array(
            'acquisition_month' => 'Month',
            'new_customers' => 'New Customers',
            'avg_ltv' => 'Avg LTV',
            'marketing_spend' => 'Marketing Spend',
            'cac' => 'CAC',
        ));

        self::render_table(__('Churn Analysis', 'themisdb-order-request'), $churn, array(
            'month' => 'Month',
            'active_start' => 'Active @ Start',
            'churned' => 'Churned',
            'churn_rate' => 'Churn Rate %',
        ));

        self::render_table(__('Product Mix Analysis', 'themisdb-order-request'), $mix, array(
            'product_edition' => 'Edition',
            'orders' => 'Orders',
            'revenue' => 'Revenue',
            'share_orders' => 'Order Share %',
            'share_revenue' => 'Revenue Share %',
        ));

        echo '</div>';
    }

    public static function shortcode($atts) {
        ob_start();
        self::render_admin_page();
        return ob_get_clean();
    }

    private static function render_table($title, $rows, $columns) {
        echo '<h2 style="margin-top:24px;">' . esc_html($title) . '</h2>';

        if (empty($rows)) {
            echo '<p>' . esc_html__('No data available.', 'themisdb-order-request') . '</p>';
            return;
        }

        echo '<table class="widefat striped"><thead><tr>';
        foreach ($columns as $key => $label) {
            echo '<th>' . esc_html($label) . '</th>';
        }
        echo '</tr></thead><tbody>';

        foreach ($rows as $row) {
            echo '<tr>';
            foreach ($columns as $key => $label) {
                $val = isset($row[$key]) ? $row[$key] : '';
                echo '<td>' . esc_html((string) $val) . '</td>';
            }
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    public static function get_cohort_analysis($months = 12) {
        global $wpdb;

        $months = max(1, intval($months));
        $table_orders = $wpdb->prefix . 'themisdb_orders';

        $cohorts = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE_FORMAT(first_order_date, '%%Y-%%m') AS cohort_month, COUNT(*) AS customers
             FROM (
                 SELECT customer_email, MIN(created_at) AS first_order_date
                 FROM {$table_orders}
                 WHERE customer_email IS NOT NULL AND customer_email <> ''
                 GROUP BY customer_email
             ) f
             WHERE first_order_date >= DATE_SUB(CURDATE(), INTERVAL %d MONTH)
             GROUP BY cohort_month
             ORDER BY cohort_month DESC",
            $months
        ), ARRAY_A);

        if (empty($cohorts)) {
            return array();
        }

        foreach ($cohorts as &$cohort) {
            $cm = $cohort['cohort_month'];
            $cohort['retained_m1'] = self::get_retained_for_cohort_month($cm, 1);
            $cohort['retained_m3'] = self::get_retained_for_cohort_month($cm, 3);
            $cohort['retained_m6'] = self::get_retained_for_cohort_month($cm, 6);
        }

        return $cohorts;
    }

    private static function get_retained_for_cohort_month($cohort_month, $offset_months) {
        global $wpdb;

        $table_orders = $wpdb->prefix . 'themisdb_orders';

        return intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT o.customer_email)
             FROM {$table_orders} o
             JOIN (
                 SELECT customer_email, MIN(created_at) AS first_order_date
                 FROM {$table_orders}
                 WHERE customer_email IS NOT NULL AND customer_email <> ''
                 GROUP BY customer_email
             ) f ON f.customer_email = o.customer_email
             WHERE DATE_FORMAT(f.first_order_date, '%%Y-%%m') = %s
               AND o.created_at >= DATE_ADD(f.first_order_date, INTERVAL %d MONTH)",
            $cohort_month,
            intval($offset_months)
        )));
    }

    public static function get_ltv_cac_tracking($months = 12) {
        global $wpdb;

        $months = max(1, intval($months));
        $table_orders = $wpdb->prefix . 'themisdb_orders';
        $table_payments = $wpdb->prefix . 'themisdb_payments';

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT a.acquisition_month, a.new_customers,
                    COALESCE(ROUND(p.revenue / NULLIF(a.new_customers,0), 2), 0) AS avg_ltv
             FROM (
                 SELECT DATE_FORMAT(MIN(created_at), '%%Y-%%m') AS acquisition_month,
                        COUNT(*) AS new_customers
                 FROM (
                    SELECT customer_email, MIN(created_at) AS created_at
                    FROM {$table_orders}
                    WHERE customer_email IS NOT NULL AND customer_email <> ''
                    GROUP BY customer_email
                 ) c
                 WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL %d MONTH)
                 GROUP BY acquisition_month
             ) a
             LEFT JOIN (
                 SELECT DATE_FORMAT(f.first_order_date, '%%Y-%%m') AS acquisition_month,
                        SUM(p.amount) AS revenue
                 FROM {$table_payments} p
                 JOIN {$table_orders} o ON o.id = p.order_id
                 JOIN (
                     SELECT customer_email, MIN(created_at) AS first_order_date
                     FROM {$table_orders}
                     WHERE customer_email IS NOT NULL AND customer_email <> ''
                     GROUP BY customer_email
                 ) f ON f.customer_email = o.customer_email
                 WHERE p.payment_status = 'verified'
                 GROUP BY acquisition_month
             ) p ON p.acquisition_month = a.acquisition_month
             ORDER BY a.acquisition_month DESC",
            $months
        ), ARRAY_A);

        $marketing_spend = self::get_marketing_spend_map();

        foreach ($rows as &$row) {
            $month = $row['acquisition_month'];
            $spend = isset($marketing_spend[$month]) ? floatval($marketing_spend[$month]) : 0.0;
            $new_customers = max(0, intval($row['new_customers']));
            $row['marketing_spend'] = number_format($spend, 2, '.', '');
            $row['cac'] = $new_customers > 0 ? number_format($spend / $new_customers, 2, '.', '') : '0.00';
            $row['avg_ltv'] = number_format(floatval($row['avg_ltv']), 2, '.', '');
        }

        return $rows;
    }

    private static function get_marketing_spend_map() {
        $raw = get_option('themisdb_reporting_marketing_spend_json', '{}');
        if (!is_string($raw) || trim($raw) === '') {
            return array();
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return array();
        }

        $out = array();
        foreach ($decoded as $k => $v) {
            $key = preg_replace('/[^0-9\-]/', '', (string) $k);
            if (preg_match('/^\d{4}\-\d{2}$/', $key)) {
                $out[$key] = floatval($v);
            }
        }

        return $out;
    }

    public static function get_churn_analysis($months = 6) {
        global $wpdb;

        $months = max(1, intval($months));
        $table_licenses = $wpdb->prefix . 'themisdb_licenses';

        $results = array();

        for ($i = 0; $i < $months; $i++) {
            $month_start = date('Y-m-01', strtotime("-{$i} months"));
            $month_end = date('Y-m-t', strtotime($month_start));
            $month_label = date('Y-m', strtotime($month_start));

            $active_start = intval($wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*)
                 FROM {$table_licenses}
                 WHERE (activation_date IS NOT NULL AND activation_date <= %s)
                   AND (expiry_date IS NULL OR expiry_date = '9999-12-31' OR expiry_date >= %s)
                   AND (cancellation_date IS NULL OR cancellation_date >= %s)",
                $month_start . ' 00:00:00',
                $month_start . ' 00:00:00',
                $month_start . ' 00:00:00'
            )));

            $churned = intval($wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*)
                 FROM {$table_licenses}
                 WHERE (
                     (expiry_date IS NOT NULL AND expiry_date <> '9999-12-31' AND DATE(expiry_date) BETWEEN %s AND %s)
                     OR
                     (cancellation_date IS NOT NULL AND DATE(cancellation_date) BETWEEN %s AND %s)
                 )",
                $month_start,
                $month_end,
                $month_start,
                $month_end
            )));

            $rate = $active_start > 0 ? round(($churned / $active_start) * 100, 2) : 0;

            $results[] = array(
                'month' => $month_label,
                'active_start' => $active_start,
                'churned' => $churned,
                'churn_rate' => number_format($rate, 2, '.', ''),
            );
        }

        return $results;
    }

    public static function get_product_mix_analysis($months = 12) {
        global $wpdb;

        $months = max(1, intval($months));
        $table_orders = $wpdb->prefix . 'themisdb_orders';
        $table_payments = $wpdb->prefix . 'themisdb_payments';

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT o.product_edition,
                    COUNT(DISTINCT o.id) AS orders,
                    COALESCE(SUM(p.amount), 0) AS revenue
             FROM {$table_orders} o
             LEFT JOIN {$table_payments} p ON p.order_id = o.id AND p.payment_status = 'verified'
             WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL %d MONTH)
             GROUP BY o.product_edition
             ORDER BY revenue DESC",
            $months
        ), ARRAY_A);

        if (empty($rows)) {
            return array();
        }

        $total_orders = 0;
        $total_revenue = 0.0;
        foreach ($rows as $row) {
            $total_orders += intval($row['orders']);
            $total_revenue += floatval($row['revenue']);
        }

        foreach ($rows as &$row) {
            $orders = intval($row['orders']);
            $revenue = floatval($row['revenue']);
            $row['revenue'] = number_format($revenue, 2, '.', '');
            $row['share_orders'] = $total_orders > 0 ? number_format(($orders / $total_orders) * 100, 2, '.', '') : '0.00';
            $row['share_revenue'] = $total_revenue > 0 ? number_format(($revenue / $total_revenue) * 100, 2, '.', '') : '0.00';
        }

        return $rows;
    }
}
