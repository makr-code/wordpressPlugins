<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB - Hybrid Database System                                   ║
║ Document Renderer                                                   ║
╠═════════════════════════════════════════════════════════════════════╣
  File:            class-document-renderer.php                        ║
  Version:         1.0.0                                              ║
  Last Modified:   2026-03-22 00:00:00                                ║
  Author:          ThemisDB Team                                      ║
╚═════════════════════════════════════════════════════════════════════╝
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Central renderer for template-based business documents.
 *
 * Supported use cases:
 * - invoices (Rechnung)
 * - licenses (Lizenzdokument)
 * - terms/AGB attachments
 * - callback letters (Rueckruf)
 * - payment requests (Zahlungsaufforderung)
 */
class ThemisDB_Document_Renderer {

    /**
     * Render a template to HTML using order-based variables.
     *
     * @param string     $template_id Template key from themisdb_document_templates option.
     * @param int|array  $order       Order ID or full order row.
     * @param array      $extra_data  Optional additional variables overriding defaults.
     * @return string|false
     */
    public static function render_template_html($template_id, $order, $extra_data = array()) {
        $template = ThemisDB_Document_Template_Manager::get_template($template_id);
        if (empty($template) || empty($template['content'])) {
            return false;
        }

        $variables = self::build_order_variables($order, $extra_data);
        return ThemisDB_Document_Template_Manager::replace_variables($template['content'], $variables);
    }

    /**
     * Render a template to PDF binary.
     *
     * @param string     $template_id Template key.
     * @param int|array  $order       Order ID or full order row.
     * @param array      $extra_data  Optional additional variables.
     * @param string     $filename    Output filename (without extension).
     * @return string|false
     */
    public static function render_template_pdf($template_id, $order, $extra_data = array(), $filename = 'document') {
        $html = self::render_template_html($template_id, $order, $extra_data);
        if ($html === false) {
            return false;
        }

        return ThemisDB_PDF_Generator::generate_pdf_from_html($html, sanitize_file_name($filename));
    }

    /**
     * Render invoice PDF from order data and invoice template.
     *
     * @param int|array $order Order ID or full order row.
     * @param string    $template_id Optional template key.
     * @param array     $extra_data Optional variable overrides.
     * @return string|false
     */
    public static function render_invoice_pdf($order, $template_id = 'invoice_default', $extra_data = array()) {
        $order_row = self::normalize_order($order);
        if (!$order_row) {
            return false;
        }

        $invoice_number = self::generate_invoice_number($order_row);
        $filename = 'invoice-' . $invoice_number;

        $invoice_vars = array(
            'invoice_number' => $invoice_number,
            'invoice_date' => date_i18n('d.m.Y'),
            'due_date' => date_i18n('d.m.Y', self::get_due_timestamp()),
            'customer_address' => self::build_customer_address($order_row),
            'line_items_table' => self::build_line_items_table($order_row),
            'line_items_text' => self::build_line_items_text($order_row),
        );

        $vars = array_merge($invoice_vars, $extra_data);
        return self::render_template_pdf($template_id, $order_row, $vars, $filename);
    }

    /**
     * Render license PDF from license/order data and license template.
     *
     * @param int|array $license License ID or full license row.
     * @param string    $template_id Optional template key.
     * @param array     $extra_data Optional variable overrides.
     * @return string|false
     */
    public static function render_license_pdf($license, $template_id = 'license_default', $extra_data = array()) {
        $license_row = self::normalize_license($license);
        if (!$license_row) {
            return false;
        }

        $order_row = !empty($license_row['order_id']) ? ThemisDB_Order_Manager::get_order(intval($license_row['order_id'])) : false;
        $variables = self::build_license_variables($license_row, $order_row, $extra_data);

        $license_key = !empty($license_row['license_key']) ? sanitize_file_name((string) $license_row['license_key']) : ('license-' . intval($license_row['id']));
        $filename = 'license-' . $license_key;

        $template = ThemisDB_Document_Template_Manager::get_template($template_id);
        if (empty($template) || empty($template['content'])) {
            return false;
        }

        $html = ThemisDB_Document_Template_Manager::replace_variables($template['content'], $variables);
        return ThemisDB_PDF_Generator::generate_pdf_from_html($html, $filename);
    }

    /**
     * Generate invoice number in canonical format.
     *
     * @param int|array $order Order ID or order row.
     * @return string
     */
    public static function generate_invoice_number($order) {
        $order_row = self::normalize_order($order);
        if (!$order_row) {
            return '';
        }

        $year = date('Y');
        $order_id = str_pad((string) intval($order_row['id']), 5, '0', STR_PAD_LEFT);
        return 'INV-' . $year . '-' . $order_id;
    }

    /**
     * Build common template variables from an order.
     *
     * @param int|array $order Order ID or full order row.
     * @param array     $extra_data Additional variables overriding defaults.
     * @return array
     */
    public static function build_order_variables($order, $extra_data = array()) {
        $order_row = self::normalize_order($order);
        if (!$order_row) {
            return array();
        }

        $created_at = !empty($order_row['created_at']) ? strtotime($order_row['created_at']) : time();
        $currency = !empty($order_row['currency']) ? (string) $order_row['currency'] : 'EUR';
        $amount = isset($order_row['total_amount']) ? floatval($order_row['total_amount']) : 0.00;

        $base = array(
            'company_name' => esc_html(get_option('blogname', 'ThemisDB')),
            'company_address' => esc_html((string) get_option('themisdb_order_company_address', '')),
            'company_email' => esc_html(get_option('admin_email', '')),
            'customer_name' => esc_html((string) ($order_row['customer_name'] ?? '')),
            'customer_email' => esc_html((string) ($order_row['customer_email'] ?? '')),
            'customer_company' => esc_html((string) ($order_row['customer_company'] ?? '')),
            'customer_address' => esc_html(self::build_customer_address($order_row)),
            'billing_name' => esc_html((string) ($order_row['billing_name'] ?? '')),
            'billing_address_line1' => esc_html((string) ($order_row['billing_address_line1'] ?? '')),
            'billing_address_line2' => esc_html((string) ($order_row['billing_address_line2'] ?? '')),
            'billing_postal_code' => esc_html((string) ($order_row['billing_postal_code'] ?? '')),
            'billing_city' => esc_html((string) ($order_row['billing_city'] ?? '')),
            'billing_country' => esc_html((string) ($order_row['billing_country'] ?? '')),
            'order_id' => esc_html((string) intval($order_row['id'] ?? 0)),
            'order_number' => esc_html((string) ($order_row['order_number'] ?? '')),
            'order_date' => esc_html(date_i18n('d.m.Y', $created_at)),
            'product_name' => esc_html(self::build_product_name($order_row)),
            'product_edition' => esc_html((string) ($order_row['product_edition'] ?? '')),
            'product_type' => esc_html((string) ($order_row['product_type'] ?? '')),
            'currency' => esc_html($currency),
            'total_amount' => esc_html(number_format($amount, 2, ',', '.')),
            'total_amount_raw' => esc_html((string) $amount),
            'invoice_number' => esc_html(self::generate_invoice_number($order_row)),
            'invoice_date' => esc_html(date_i18n('d.m.Y')),
            'due_date' => esc_html(date_i18n('d.m.Y', self::get_due_timestamp())),
            'line_items_table' => self::build_line_items_table($order_row),
            'line_items_text' => esc_html(self::build_line_items_text($order_row)),
        );

        $merged = array_merge($base, $extra_data);

        // Escape every non-HTML override to keep templates safe by default.
        foreach ($merged as $key => $value) {
            if (!is_scalar($value)) {
                $merged[$key] = '';
                continue;
            }

            $key = (string) $key;
            if (substr($key, -5) === '_html' || $key === 'line_items_table') {
                $merged[$key] = wp_kses_post((string) $value);
                continue;
            }

            $merged[$key] = esc_html((string) $value);
        }

        return $merged;
    }

    /**
     * Build template variables for license documents.
     *
     * @param array      $license_row Resolved license row.
     * @param array|bool $order_row Optional linked order row.
     * @param array      $extra_data Additional variable overrides.
     * @return array
     */
    public static function build_license_variables($license_row, $order_row = false, $extra_data = array()) {
        $created_at = !empty($license_row['created_at']) ? strtotime($license_row['created_at']) : time();
        $activation_date = !empty($license_row['activation_date']) ? strtotime($license_row['activation_date']) : null;
        $expiry_date = !empty($license_row['expiry_date']) ? strtotime($license_row['expiry_date']) : null;

        $customer_name = '';
        $customer_email = '';
        $customer_company = '';
        $order_number = '';
        if ($order_row) {
            $customer_name = (string) ($order_row['customer_name'] ?? '');
            $customer_email = (string) ($order_row['customer_email'] ?? '');
            $customer_company = (string) ($order_row['customer_company'] ?? '');
            $order_number = (string) ($order_row['order_number'] ?? '');
        }

        $base = array(
            'company_name' => esc_html(get_option('blogname', 'ThemisDB')),
            'company_address' => esc_html((string) get_option('themisdb_order_company_address', '')),
            'company_email' => esc_html(get_option('admin_email', '')),
            'license_id' => esc_html((string) intval($license_row['id'] ?? 0)),
            'license_key' => esc_html((string) ($license_row['license_key'] ?? '')),
            'license_type' => esc_html((string) ($license_row['license_type'] ?? 'standard')),
            'license_status' => esc_html((string) ($license_row['license_status'] ?? 'pending')),
            'product_edition' => esc_html((string) ($license_row['product_edition'] ?? '')),
            'max_nodes' => esc_html((string) intval($license_row['max_nodes'] ?? 0)),
            'max_cores' => esc_html((string) intval($license_row['max_cores'] ?? 0)),
            'max_storage_gb' => esc_html((string) intval($license_row['max_storage_gb'] ?? 0)),
            'created_date' => esc_html(date_i18n('d.m.Y', $created_at)),
            'activation_date' => esc_html($activation_date ? date_i18n('d.m.Y', $activation_date) : ''),
            'expiry_date' => esc_html($expiry_date ? date_i18n('d.m.Y', $expiry_date) : ''),
            'customer_name' => esc_html($customer_name),
            'customer_email' => esc_html($customer_email),
            'customer_company' => esc_html($customer_company),
            'order_number' => esc_html($order_number),
        );

        $merged = array_merge($base, $extra_data);
        foreach ($merged as $key => $value) {
            if (!is_scalar($value)) {
                $merged[$key] = '';
                continue;
            }
            $merged[$key] = esc_html((string) $value);
        }

        return $merged;
    }

    /**
     * Resolve order input to a full order row.
     *
     * @param int|array $order Order ID or row.
     * @return array|false
     */
    private static function normalize_order($order) {
        if (is_array($order) && !empty($order['id'])) {
            return $order;
        }

        if (is_numeric($order)) {
            $order_id = intval($order);
            if ($order_id > 0) {
                return ThemisDB_Order_Manager::get_order($order_id);
            }
        }

        return false;
    }

    /**
     * Resolve license input to a full license row.
     *
     * @param int|array $license License ID or row.
     * @return array|false
     */
    private static function normalize_license($license) {
        if (is_array($license) && !empty($license['id'])) {
            return $license;
        }

        if (is_numeric($license)) {
            $license_id = intval($license);
            if ($license_id > 0) {
                return ThemisDB_License_Manager::get_license($license_id);
            }
        }

        return false;
    }

    /**
     * Build a compact product description.
     */
    private static function build_product_name($order) {
        $edition = !empty($order['product_edition']) ? ucfirst((string) $order['product_edition']) : '';
        $type = !empty($order['product_type']) ? ucfirst((string) $order['product_type']) : '';

        if ($edition !== '' && $type !== '') {
            return 'ThemisDB ' . $edition . ' (' . $type . ')';
        }
        if ($edition !== '') {
            return 'ThemisDB ' . $edition;
        }

        return 'ThemisDB';
    }

    /**
     * Build billing address block used in templates.
     */
    private static function build_customer_address($order) {
        $lines = array_filter(array(
            $order['billing_name'] ?? $order['customer_name'] ?? '',
            $order['customer_company'] ?? '',
            $order['billing_address_line1'] ?? '',
            $order['billing_address_line2'] ?? '',
            trim((string) ($order['billing_postal_code'] ?? '') . ' ' . (string) ($order['billing_city'] ?? '')),
            $order['billing_country'] ?? '',
        ));

        return implode(', ', array_map('strval', $lines));
    }

    /**
     * Build a simple HTML line item table for template injection.
     */
    private static function build_line_items_table($order) {
        $items = ThemisDB_Order_Manager::get_order_items(intval($order['id']));
        if (empty($items)) {
            return '<p>' . esc_html__('Keine Positionen vorhanden.', 'themisdb-order-request') . '</p>';
        }

        $rows = '';
        foreach ($items as $item) {
            $item_name = esc_html((string) ($item['item_name'] ?? ''));
            $quantity = max(1, intval($item['quantity'] ?? 1));
            $unit_price = floatval($item['unit_price'] ?? 0);
            $total_price = floatval($item['total_price'] ?? 0);
            $currency = esc_html((string) ($item['currency'] ?? ($order['currency'] ?? 'EUR')));

            $rows .= '<tr>'
                . '<td>' . $item_name . '</td>'
                . '<td style="text-align:right;">' . esc_html((string) $quantity) . '</td>'
                . '<td style="text-align:right;">' . esc_html(number_format($unit_price, 2, ',', '.')) . ' ' . $currency . '</td>'
                . '<td style="text-align:right;">' . esc_html(number_format($total_price, 2, ',', '.')) . ' ' . $currency . '</td>'
                . '</tr>';
        }

        return '<table style="width:100%;border-collapse:collapse;">'
            . '<thead><tr>'
            . '<th style="text-align:left;border-bottom:1px solid #ddd;">' . esc_html__('Position', 'themisdb-order-request') . '</th>'
            . '<th style="text-align:right;border-bottom:1px solid #ddd;">' . esc_html__('Menge', 'themisdb-order-request') . '</th>'
            . '<th style="text-align:right;border-bottom:1px solid #ddd;">' . esc_html__('Einzelpreis', 'themisdb-order-request') . '</th>'
            . '<th style="text-align:right;border-bottom:1px solid #ddd;">' . esc_html__('Gesamtpreis', 'themisdb-order-request') . '</th>'
            . '</tr></thead><tbody>' . $rows . '</tbody></table>';
    }

    /**
     * Build a text summary of line items for plain template usage.
     */
    private static function build_line_items_text($order) {
        $items = ThemisDB_Order_Manager::get_order_items(intval($order['id']));
        if (empty($items)) {
            return '';
        }

        $parts = array();
        foreach ($items as $item) {
            $name = (string) ($item['item_name'] ?? '');
            $qty = max(1, intval($item['quantity'] ?? 1));
            $price = number_format(floatval($item['total_price'] ?? 0), 2, ',', '.');
            $currency = (string) ($item['currency'] ?? ($order['currency'] ?? 'EUR'));
            $parts[] = trim($name) . ' x' . $qty . ' = ' . $price . ' ' . $currency;
        }

        return implode('; ', $parts);
    }

    /**
     * Resolve due-date base timestamp from plugin settings.
     */
    private static function get_due_timestamp() {
        $days = intval(get_option('themisdb_b2b_default_invoice_due_days', '30'));
        if ($days < 1) {
            $days = 30;
        }
        return strtotime('+' . $days . ' days');
    }
}
