<?php
/**
 * B2B Portal Manager (Phase 5.3)
 *
 * Implements:
 * - Department management
 * - Bulk user upload
 * - Custom pricing
 * - PO / invoice management
 */

if (!defined('ABSPATH')) {
    exit;
}

class ThemisDB_B2B_Portal {

    public static function init() {
        add_shortcode('themisdb_b2b_portal', array(__CLASS__, 'b2b_portal_shortcode'));

        add_action('wp_ajax_themisdb_b2b_create_department', array(__CLASS__, 'ajax_create_department'));
        add_action('wp_ajax_themisdb_b2b_bulk_upload_users', array(__CLASS__, 'ajax_bulk_upload_users'));
        add_action('wp_ajax_themisdb_b2b_save_pricing_rule', array(__CLASS__, 'ajax_save_pricing_rule'));
        add_action('wp_ajax_themisdb_b2b_save_procurement', array(__CLASS__, 'ajax_save_procurement'));
    }

    /**
     * Create B2B department.
     */
    public static function create_department($department_name, $company_name, $contact_email = '', $department_code = '') {
        global $wpdb;

        $table = $wpdb->prefix . 'themisdb_b2b_departments';

        $department_name = sanitize_text_field($department_name);
        $company_name = sanitize_text_field($company_name);
        $contact_email = sanitize_email($contact_email);

        if ($department_name === '' || $company_name === '') {
            return false;
        }

        if ($department_code === '') {
            $department_code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $company_name), 0, 4)) . '-' . strtoupper(wp_generate_password(4, false, false));
        }
        $department_code = sanitize_text_field($department_code);

        $ok = $wpdb->insert($table, array(
            'department_code' => $department_code,
            'department_name' => $department_name,
            'company_name' => $company_name,
            'contact_email' => $contact_email ?: null,
            'status' => 'active',
            'created_by' => get_current_user_id(),
        ));

        if (!$ok) {
            return false;
        }

        return intval($wpdb->insert_id);
    }

    /**
     * Bulk import department users from lines "email,name,role".
     */
    public static function bulk_upload_users($department_id, $csv_text) {
        global $wpdb;

        $department_id = intval($department_id);
        if ($department_id <= 0 || trim((string)$csv_text) === '') {
            return array('inserted' => 0, 'updated' => 0, 'errors' => 0);
        }

        $table = $wpdb->prefix . 'themisdb_b2b_department_users';
        $lines = preg_split('/\r\n|\r|\n/', (string)$csv_text);

        $inserted = 0;
        $updated = 0;
        $errors = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $parts = array_map('trim', str_getcsv($line));
            $email = sanitize_email($parts[0] ?? '');
            $name = sanitize_text_field($parts[1] ?? '');
            $role = sanitize_key($parts[2] ?? 'member');
            if (!in_array($role, array('member', 'manager', 'approver'), true)) {
                $role = 'member';
            }

            if ($email === '') {
                $errors++;
                continue;
            }

            $user = get_user_by('email', $email);
            $user_id = $user ? intval($user->ID) : null;

            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM {$table} WHERE department_id = %d AND user_email = %s LIMIT 1",
                $department_id,
                $email
            ), ARRAY_A);

            if ($existing) {
                $ok = $wpdb->update($table, array(
                    'user_id' => $user_id,
                    'full_name' => $name ?: null,
                    'role' => $role,
                    'status' => 'active',
                ), array('id' => intval($existing['id'])));
                if ($ok !== false) {
                    $updated++;
                } else {
                    $errors++;
                }
                continue;
            }

            $ok = $wpdb->insert($table, array(
                'department_id' => $department_id,
                'user_id' => $user_id,
                'user_email' => $email,
                'full_name' => $name ?: null,
                'role' => $role,
                'status' => 'active',
            ));

            if ($ok) {
                $inserted++;
            } else {
                $errors++;
            }
        }

        return array('inserted' => $inserted, 'updated' => $updated, 'errors' => $errors);
    }

    /**
     * Upsert custom pricing rule for department + edition.
     */
    public static function save_pricing_rule($department_id, $product_edition, $discount_type, $discount_value, $min_quantity = 1) {
        global $wpdb;

        $table = $wpdb->prefix . 'themisdb_b2b_pricing_rules';

        $department_id = intval($department_id);
        $product_edition = sanitize_key($product_edition);
        $discount_type = sanitize_key($discount_type);
        $discount_value = floatval($discount_value);
        $min_quantity = max(1, intval($min_quantity));

        if ($department_id <= 0 || $product_edition === '' || !in_array($discount_type, array('percent', 'fixed'), true)) {
            return false;
        }

        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$table} WHERE department_id = %d AND product_edition = %s AND is_active = 1 LIMIT 1",
            $department_id,
            $product_edition
        ), ARRAY_A);

        $payload = array(
            'department_id' => $department_id,
            'product_edition' => $product_edition,
            'discount_type' => $discount_type,
            'discount_value' => $discount_value,
            'min_quantity' => $min_quantity,
            'is_active' => 1,
            'created_by' => get_current_user_id(),
        );

        if ($existing) {
            $ok = $wpdb->update($table, $payload, array('id' => intval($existing['id'])));
            return $ok !== false ? intval($existing['id']) : false;
        }

        $ok = $wpdb->insert($table, $payload);
        return $ok ? intval($wpdb->insert_id) : false;
    }

    /**
     * Apply B2B custom pricing for an order by customer e-mail and edition.
     */
    public static function apply_custom_pricing_to_order($order_id, $order) {
        global $wpdb;

        if (!class_exists('ThemisDB_Order_Manager')) {
            return false;
        }

        $order_id = intval($order_id);
        if ($order_id <= 0 || empty($order) || empty($order['customer_email'])) {
            return false;
        }

        $table_users = $wpdb->prefix . 'themisdb_b2b_department_users';
        $table_rules = $wpdb->prefix . 'themisdb_b2b_pricing_rules';

        $dept_user = $wpdb->get_row($wpdb->prepare(
            "SELECT department_id FROM {$table_users} WHERE user_email = %s AND status = 'active' LIMIT 1",
            sanitize_email($order['customer_email'])
        ), ARRAY_A);

        if (!$dept_user) {
            return false;
        }

        $department_id = intval($dept_user['department_id']);
        $edition = sanitize_key($order['product_edition'] ?? '');
        $rule = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_rules} WHERE department_id = %d AND product_edition = %s AND is_active = 1 LIMIT 1",
            $department_id,
            $edition
        ), ARRAY_A);

        if (!$rule) {
            return false;
        }

        $original_total = floatval($order['total_amount']);
        $discount = 0.0;
        if ($rule['discount_type'] === 'percent') {
            $discount = round($original_total * (floatval($rule['discount_value']) / 100), 2);
        } else {
            $discount = floatval($rule['discount_value']);
        }

        $discount = max(0, min($discount, $original_total));
        $new_total = round($original_total - $discount, 2);

        if ($new_total === $original_total) {
            return false;
        }

        $ok = ThemisDB_Order_Manager::update_order($order_id, array('total_amount' => $new_total));
        if (!$ok) {
            return false;
        }

        // Persist pricing application snapshot in procurement metadata.
        self::save_procurement($order_id, array(
            'department_id' => $department_id,
            'procurement_status' => 'approved',
            'invoice_required' => 1,
            'metadata' => array(
                'b2b_pricing_applied' => true,
                'pricing_rule_id' => intval($rule['id']),
                'original_total' => $original_total,
                'discount' => $discount,
                'discount_type' => sanitize_key($rule['discount_type']),
                'discount_value' => floatval($rule['discount_value']),
                'discounted_total' => $new_total,
            ),
        ));

        return array(
            'department_id' => $department_id,
            'original_total' => $original_total,
            'discount' => $discount,
            'new_total' => $new_total,
        );
    }

    /**
     * Save PO/invoice procurement record for order.
     */
    public static function save_procurement($order_id, $data) {
        global $wpdb;

        $table = $wpdb->prefix . 'themisdb_b2b_procurements';
        $order_id = intval($order_id);
        if ($order_id <= 0) {
            return false;
        }

        $row = array(
            'order_id' => $order_id,
            'department_id' => !empty($data['department_id']) ? intval($data['department_id']) : null,
            'purchase_order_number' => !empty($data['purchase_order_number']) ? sanitize_text_field($data['purchase_order_number']) : null,
            'procurement_status' => sanitize_key($data['procurement_status'] ?? 'draft'),
            'invoice_required' => isset($data['invoice_required']) ? (intval($data['invoice_required']) ? 1 : 0) : 1,
            'invoice_number' => !empty($data['invoice_number']) ? sanitize_text_field($data['invoice_number']) : null,
            'invoice_status' => sanitize_key($data['invoice_status'] ?? 'pending'),
            'invoice_due_date' => !empty($data['invoice_due_date']) ? sanitize_text_field($data['invoice_due_date']) : null,
            'billing_reference' => !empty($data['billing_reference']) ? sanitize_text_field($data['billing_reference']) : null,
            'metadata' => !empty($data['metadata']) ? wp_json_encode($data['metadata']) : null,
            'created_by' => get_current_user_id(),
        );

        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$table} WHERE order_id = %d LIMIT 1",
            $order_id
        ), ARRAY_A);

        if ($existing) {
            $ok = $wpdb->update($table, $row, array('id' => intval($existing['id'])));
            return $ok !== false ? intval($existing['id']) : false;
        }

        $ok = $wpdb->insert($table, $row);
        return $ok ? intval($wpdb->insert_id) : false;
    }

    /**
     * Minimal B2B portal shortcode output.
     */
    public static function b2b_portal_shortcode($atts) {
        $atts = shortcode_atts(array(), $atts, 'themisdb_b2b_portal');
        $atts = apply_filters('themisdb_b2b_portal_shortcode_atts', $atts, (array) $atts);

        $payload = array(
            'is_logged_in' => is_user_logged_in(),
        );

        if (!is_user_logged_in()) {
            $payload['message'] = esc_html__('Bitte anmelden, um das B2B-Portal zu nutzen.', 'themisdb-order-request');
            $payload = apply_filters('themisdb_b2b_portal_shortcode_payload', $payload, $atts);
            $custom_html = apply_filters('themisdb_b2b_portal_shortcode_html', null, $payload, $atts);
            if (null !== $custom_html) {
                return (string) $custom_html;
            }
            return apply_filters('themisdb_b2b_portal_shortcode_html_output', '<p>' . esc_html($payload['message']) . '</p>', $payload, $atts);
        }

        $nonce = wp_create_nonce('themisdb_b2b_portal_nonce');

        $payload = array_merge($payload, array(
            'nonce' => $nonce,
        ));

        $payload = apply_filters('themisdb_b2b_portal_shortcode_payload', $payload, $atts);
        $custom_html = apply_filters('themisdb_b2b_portal_shortcode_html', null, $payload, $atts);
        if (null !== $custom_html) {
            return (string) $custom_html;
        }

        ob_start();
        ?>
        <div class="themisdb-b2b-portal">
            <h3><?php esc_html_e('B2B Portal', 'themisdb-order-request'); ?></h3>
            <p><?php esc_html_e('Department Management, Bulk User Upload, Custom Pricing und PO/Invoice Management sind aktiv.', 'themisdb-order-request'); ?></p>
            <p style="font-size:12px;color:#64748b;">Nonce: <?php echo esc_html($nonce); ?></p>
        </div>
        <?php
        $html = ob_get_clean();
        return apply_filters('themisdb_b2b_portal_shortcode_html_output', $html, $payload, $atts);
    }

    public static function ajax_create_department() {
        check_ajax_referer('themisdb_b2b_portal_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $id = self::create_department(
            sanitize_text_field($_POST['department_name'] ?? ''),
            sanitize_text_field($_POST['company_name'] ?? ''),
            sanitize_email($_POST['contact_email'] ?? ''),
            sanitize_text_field($_POST['department_code'] ?? '')
        );

        if (!$id) {
            wp_send_json_error(array('message' => 'Create department failed'));
        }

        wp_send_json_success(array('department_id' => intval($id)));
    }

    public static function ajax_bulk_upload_users() {
        check_ajax_referer('themisdb_b2b_portal_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $result = self::bulk_upload_users(
            intval($_POST['department_id'] ?? 0),
            wp_unslash($_POST['csv_text'] ?? '')
        );

        wp_send_json_success($result);
    }

    public static function ajax_save_pricing_rule() {
        check_ajax_referer('themisdb_b2b_portal_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $id = self::save_pricing_rule(
            intval($_POST['department_id'] ?? 0),
            sanitize_key($_POST['product_edition'] ?? ''),
            sanitize_key($_POST['discount_type'] ?? 'percent'),
            floatval($_POST['discount_value'] ?? 0),
            intval($_POST['min_quantity'] ?? 1)
        );

        if (!$id) {
            wp_send_json_error(array('message' => 'Save pricing rule failed'));
        }

        wp_send_json_success(array('rule_id' => intval($id)));
    }

    public static function ajax_save_procurement() {
        check_ajax_referer('themisdb_b2b_portal_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $id = self::save_procurement(intval($_POST['order_id'] ?? 0), array(
            'department_id' => intval($_POST['department_id'] ?? 0),
            'purchase_order_number' => sanitize_text_field($_POST['purchase_order_number'] ?? ''),
            'procurement_status' => sanitize_key($_POST['procurement_status'] ?? 'draft'),
            'invoice_required' => intval($_POST['invoice_required'] ?? 1),
            'invoice_number' => sanitize_text_field($_POST['invoice_number'] ?? ''),
            'invoice_status' => sanitize_key($_POST['invoice_status'] ?? 'pending'),
            'invoice_due_date' => sanitize_text_field($_POST['invoice_due_date'] ?? ''),
            'billing_reference' => sanitize_text_field($_POST['billing_reference'] ?? ''),
        ));

        if (!$id) {
            wp_send_json_error(array('message' => 'Save procurement failed'));
        }

        wp_send_json_success(array('procurement_id' => intval($id)));
    }
}
