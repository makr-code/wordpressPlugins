<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB - Hybrid Database System                                   ║
║ Document Template Manager                                           ║
╠═════════════════════════════════════════════════════════════════════╣
  File:            class-document-template-manager.php                ║
  Version:         1.0.0                                              ║
  Last Modified:   2026-03-16 10:00:00                                ║
  Author:          ThemisDB Team                                      ║
╠═════════════════════════════════════════════════════════════════════╣
  Quality Metrics:                                                    ║
    • Maturity Level:  🟢 PRODUCTION-READY                             ║
    • Quality Score:   100.0/100                                      ║
    • Total Lines:     550+                                           ║
    • Open Issues:     0                                              ║
╠═════════════════════════════════════════════════════════════════════╣
  Purpose:                                                            ║
    Template management for contracts, invoices, and letters           ║
    with variable replacement {{customer_name}} → John Doe            ║
╚═════════════════════════════════════════════════════════════════════╝
 */

if (!defined('ABSPATH')) {
    exit;
}

class ThemisDB_Document_Template_Manager {
    
    /**
     * Get all available templates
     */
    public static function get_templates() {
        $templates = get_option('themisdb_document_templates', array());
        $defaults = self::get_default_templates();
        
        if (empty($templates)) {
            $templates = $defaults;
            update_option('themisdb_document_templates', $templates);
            return $templates;
        }

        // Backfill newly introduced system templates without overriding user customizations.
        $missing = false;
        foreach ($defaults as $template_id => $template_data) {
            if (!isset($templates[$template_id])) {
                $templates[$template_id] = $template_data;
                $missing = true;
            }
        }

        if ($missing) {
            update_option('themisdb_document_templates', $templates);
        }
        
        return $templates;
    }
    
    /**
     * Get single template
     */
    public static function get_template($template_id) {
        $templates = self::get_templates();
        return isset($templates[$template_id]) ? $templates[$template_id] : null;
    }
    
    /**
     * Create or update template
     */
    public static function save_template($template_id, $data) {
        $templates = self::get_templates();
        
        $templates[$template_id] = array(
            'id' => $template_id,
            'name' => sanitize_text_field($data['name']),
            'type' => sanitize_text_field($data['type']), // contract, invoice, letter
            'subject' => sanitize_text_field($data['subject']),
            'content' => wp_kses_post($data['content']),
            'updated_at' => date('Y-m-d H:i:s'),
            'variables' => self::extract_variables($data['content'])
        );
        
        update_option('themisdb_document_templates', $templates);
        return $templates[$template_id];
    }
    
    /**
     * Delete template
     */
    public static function delete_template($template_id) {
        $templates = self::get_templates();
        unset($templates[$template_id]);
        update_option('themisdb_document_templates', $templates);
    }

    /**
     * Get one default system template definition.
     */
    public static function get_default_template($template_id) {
        $defaults = self::get_default_templates();
        return isset($defaults[$template_id]) ? $defaults[$template_id] : null;
    }

    /**
     * Reset one template to its shipped default definition.
     */
    public static function reset_template_to_default($template_id) {
        $default = self::get_default_template($template_id);
        if (empty($default)) {
            return false;
        }

        $templates = self::get_templates();
        $templates[$template_id] = $default;
        $templates[$template_id]['updated_at'] = date('Y-m-d H:i:s');
        update_option('themisdb_document_templates', $templates);

        return $templates[$template_id];
    }

    /**
     * Reload a system template strictly from its template file.
     * Returns false when no template file exists for the given template ID.
     */
    public static function reload_template_from_file($template_id) {
        $template_id = sanitize_key((string) $template_id);
        $file_name = self::get_default_template_file_name($template_id);
        if ($file_name === '') {
            return false;
        }

        $file_content = self::load_default_template_file($file_name);
        if ($file_content === '') {
            return false;
        }

        $default = self::get_default_template($template_id);
        if (empty($default)) {
            return false;
        }

        $templates = self::get_templates();
        $templates[$template_id] = array(
            'id' => $template_id,
            'name' => $default['name'] ?? $template_id,
            'type' => $default['type'] ?? 'letter',
            'subject' => $default['subject'] ?? '',
            'content' => $file_content,
            'updated_at' => date('Y-m-d H:i:s'),
            'variables' => isset($default['variables']) && is_array($default['variables'])
                ? $default['variables']
                : self::extract_variables($file_content),
        );

        update_option('themisdb_document_templates', $templates);
        return $templates[$template_id];
    }
    
    /**
     * Extract {{variable}} names from template content
     */
    private static function extract_variables($content) {
        $variables = array();
        
        if (preg_match_all('/\{\{(\w+)\}\}/', $content, $matches)) {
            $variables = array_unique($matches[1]);
        }
        
        return $variables;
    }
    
    /**
     * Replace variables in template
     * {{customer_name}} => John Doe
     * {{order_number}} => ORD-20260101-ABC123
     * {{contract_number}} => CON-20260101-ABC123
     */
    public static function replace_variables($template_content, $data) {
        $content = $template_content;
        
        foreach ($data as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $content = str_replace($placeholder, $value, $content);
        }
        
        // Remove any unreplaced variables
        $content = preg_replace('/\{\{\w+\}\}/', '', $content);
        
        return $content;
    }
    
    /**
     * Get default templates (installed on first use)
     */
    private static function get_default_templates() {
        return array(
            'contract_default' => array(
                'id' => 'contract_default',
                'name' => 'Standard Vertrag',
                'type' => 'contract',
                'subject' => 'Vertrag {{contract_number}}',
                'content' => self::get_default_contract_template(),
                'variables' => array(
                    'customer_name', 'customer_company', 'customer_email',
                    'order_number', 'contract_number', 'valid_from', 'valid_until',
                    'product_name', 'total_amount', 'currency'
                ),
                'updated_at' => date('Y-m-d H:i:s')
            ),
            'invoice_default' => array(
                'id' => 'invoice_default',
                'name' => 'Standard Rechnung',
                'type' => 'invoice',
                'subject' => 'Rechnung {{invoice_number}}',
                'content' => self::get_default_invoice_template(),
                'variables' => array(
                    'customer_name', 'customer_company', 'customer_email', 'customer_address',
                    'invoice_number', 'invoice_date', 'due_date',
                    'order_number', 'product_name', 'total_amount', 'currency'
                ),
                'updated_at' => date('Y-m-d H:i:s')
            ),
            'letter_default' => array(
                'id' => 'letter_default',
                'name' => 'Standard Brief',
                'type' => 'letter',
                'subject' => 'Wichtige Mitteilung',
                'content' => self::get_default_letter_template(),
                'variables' => array(
                    'customer_name', 'customer_company', 'company_name',
                    'company_address', 'contract_number', 'order_number'
                ),
                'updated_at' => date('Y-m-d H:i:s')
            ),
            'terms_default' => array(
                'id' => 'terms_default',
                'name' => 'AGB / Bedingungen',
                'type' => 'terms',
                'subject' => 'Allgemeine Geschaeftsbedingungen',
                'content' => self::get_default_terms_template(),
                'variables' => array(
                    'company_name', 'company_address', 'company_email',
                    'customer_name', 'order_number', 'order_date', 'product_name'
                ),
                'updated_at' => date('Y-m-d H:i:s')
            ),
            'callback_default' => array(
                'id' => 'callback_default',
                'name' => 'Rueckrufbestaetigung',
                'type' => 'callback',
                'subject' => 'Rueckruf angefragt: Bestellung {{order_number}}',
                'content' => self::get_default_callback_template(),
                'variables' => array(
                    'customer_name', 'customer_company', 'customer_email',
                    'order_number', 'order_date', 'company_name', 'company_email'
                ),
                'updated_at' => date('Y-m-d H:i:s')
            ),
            'payment_request_default' => array(
                'id' => 'payment_request_default',
                'name' => 'Zahlungsaufforderung',
                'type' => 'payment_request',
                'subject' => 'Zahlungsaufforderung Rechnung {{invoice_number}}',
                'content' => self::get_default_payment_request_template(),
                'variables' => array(
                    'customer_name', 'customer_company', 'order_number',
                    'invoice_number', 'invoice_date', 'due_date',
                    'total_amount', 'currency', 'company_name', 'company_email'
                ),
                'updated_at' => date('Y-m-d H:i:s')
            ),
            'license_default' => array(
                'id' => 'license_default',
                'name' => 'Lizenzdokument',
                'type' => 'license',
                'subject' => 'Lizenz {{license_key}}',
                'content' => self::get_default_license_template(),
                'variables' => array(
                    'license_key', 'license_type', 'license_status',
                    'product_edition', 'max_nodes', 'max_cores', 'max_storage_gb',
                    'created_date', 'activation_date', 'expiry_date',
                    'customer_name', 'customer_email', 'customer_company',
                    'order_number', 'company_name', 'company_email'
                ),
                'updated_at' => date('Y-m-d H:i:s')
            )
        );
    }
    
    /**
     * Default contract template
     */
    private static function get_default_contract_template() {
        $template = self::load_default_template_file('contract_default.html');
        if ($template !== '') {
            return $template;
        }

        return <<<'HTML'
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 12pt; line-height: 1.6; margin: 40px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
        .header h1 { margin: 0; font-size: 24pt; }
        .header p { margin: 5px 0; }
        .section { margin: 30px 0; }
        .section h2 { font-size: 16pt; border-bottom: 1px solid #666; padding-bottom: 5px; margin-bottom: 15px; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 8px; border: 1px solid #ddd; }
        .info-table td:first-child { font-weight: bold; width: 30%; background-color: #f5f5f5; }
        .footer { margin-top: 50px; border-top: 2px solid #333; padding-top: 20px; }
        .signature-boxes { margin-top: 40px; display: flex; justify-content: space-between; }
        .signature-box { width: 45%; }
        .signature-line { border-top: 1px solid #333; margin-top: 60px; padding-top: 5px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>VERTRAG</h1>
        <p>Vertragsnummer: {{contract_number}}</p>
        <p>Gültig von {{valid_from}} bis {{valid_until}}</p>
    </div>

    <div class="section">
        <h2>Vertragspartner</h2>
        <table class="info-table">
            <tr>
                <td>Name:</td>
                <td>{{customer_name}}</td>
            </tr>
            <tr>
                <td>Unternehmen:</td>
                <td>{{customer_company}}</td>
            </tr>
            <tr>
                <td>E-Mail:</td>
                <td>{{customer_email}}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2>Leistungen</h2>
        <table class="info-table">
            <tr>
                <td>Bestellnummer:</td>
                <td>{{order_number}}</td>
            </tr>
            <tr>
                <td>Produkt:</td>
                <td>{{product_name}}</td>
            </tr>
            <tr>
                <td>Gesamtbetrag:</td>
                <td>{{total_amount}} {{currency}}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2>Vertragsbedingungen</h2>
        <p>
            Dieser Vertrag regelt die Bereitstellung und Nutzung unserer Produkte und Dienstleistungen.
            Die Vertragslaufzeit beträgt die oben festgelegte Gültigkeitsdauer. Lesen Sie bitte unsere
            Allgemeinen Geschäftsbedingungen zu Rate.
        </p>
    </div>

    <div class="footer">
        <div class="signature-boxes">
            <div class="signature-box">
                <div class="signature-line">Für ThemisDB GmbH</div>
            </div>
            <div class="signature-box">
                <div class="signature-line">{{customer_name}}</div>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * Default invoice template
     */
    private static function get_default_invoice_template() {
        $template = self::load_default_template_file('invoice_default.html');
        if ($template !== '') {
            return $template;
        }

        return <<<'HTML'
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 12pt; line-height: 1.6; margin: 40px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { margin: 0; font-size: 24pt; }
        .invoice-info { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .invoice-info div { width: 48%; }
        .invoice-info strong { display: block; margin-bottom: 5px; }
        .section { margin: 30px 0; }
        .section h2 { font-size: 14pt; border-bottom: 1px solid #666; padding-bottom: 5px; margin-bottom: 15px; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 8px; border: 1px solid #ddd; }
        .info-table td:first-child { font-weight: bold; width: 30%; background-color: #f5f5f5; }
        .total-section { margin-top: 30px; text-align: right; }
        .total-section .total-row { font-size: 14pt; font-weight: bold; }
        .footer { margin-top: 50px; border-top: 1px solid #ddd; padding-top: 20px; text-align: center; font-size: 10pt; }
    </style>
</head>
<body>
    <div class="header">
        <h1>RECHNUNG</h1>
    </div>

    <div class="invoice-info">
        <div>
            <strong>Rechnungsadresse:</strong>
            {{customer_name}}<br>
            {{customer_company}}<br>
            {{customer_address}}<br>
            {{customer_email}}
        </div>
        <div style="text-align: right;">
            <strong>Rechnungsnummer:</strong> {{invoice_number}}<br>
            <strong>Rechnungsdatum:</strong> {{invoice_date}}<br>
            <strong>Fälligkeitsdatum:</strong> {{due_date}}<br>
            <strong>Bestellnummer:</strong> {{order_number}}
        </div>
    </div>

    <div class="section">
        <h2>Leistungsdetails</h2>
        {{line_items_table}}
        <table class="info-table">
            <tr>
                <td>Leistung:</td>
                <td>{{product_name}}</td>
            </tr>
            <tr>
                <td>Gesamtbetrag:</td>
                <td>{{total_amount}} {{currency}}</td>
            </tr>
        </table>
    </div>

    <div class="total-section">
        <div style="margin-bottom: 10px;">
            <span>Summe netto: {{total_amount}} {{currency}}</span>
        </div>
        <div class="total-row">
            GESAMTBETRAG: {{total_amount}} {{currency}}
        </div>
    </div>

    <div class="footer">
        <p>Zahlungsart: Überweisung | Bankverbindung: [Bankdetails einfügen]</p>
        <p>Vielen Dank für Ihre Geschäftstätigkeit!</p>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * Default letter template
     */
    private static function get_default_letter_template() {
        $template = self::load_default_template_file('letter_default.html');
        if ($template !== '') {
            return $template;
        }

        return <<<'HTML'
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 12pt; line-height: 1.6; margin: 40px; }
        .letterhead { text-align: center; margin-bottom: 30px; border-bottom: 1px solid #333; padding-bottom: 20px; }
        .letterhead h1 { margin: 0; font-size: 18pt; }
        .recipient { margin-bottom: 30px; }
        .content { margin: 30px 0; }
        .footer { margin-top: 50px; border-top: 1px solid #ddd; padding-top: 20px; }
        .signature { margin-top: 60px; }
    </style>
</head>
<body>
    <div class="letterhead">
        <h1>{{company_name}}</h1>
        <p>{{company_address}}</p>
    </div>

    <div class="recipient">
        <p>
            {{customer_name}}<br>
            {{customer_company}}
        </p>
    </div>

    <div class="content">
        <p><strong>Betreff: Vertrag {{contract_number}} - Bestellung {{order_number}}</strong></p>
        
        <p>Sehr geehrte Damen und Herren,</p>
        
        <p>
            vielen Dank für Ihre Bestellung. Wir freuen uns, Sie als Kunden begrüßen zu dürfen 
            und möchten Sie hiermit auf die wichtigsten Details informieren.
        </p>
        
        <p>
            Anbei erhalten Sie die Unterlagen zu Ihrem Vertrag sowie alle notwendigen Informationen
            zu den verschiedenen Leistungsoptionen. Bitte überprüfen Sie die Angaben und 
            unterzeichnen Sie diese, sofern alles dem gewünschten Stand entspricht.
        </p>
        
        <p>
            Bei Fragen oder Wünschen zur Anpassung der Leistungen stehen wir Ihnen gerne zur Verfügung.
        </p>

        <p>Freundliche Grüße,</p>
    </div>

    <div class="footer">
        <div class="signature">
            <p>
                ThemisDB Team<br>
                support@themisdb.local
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Default terms and conditions template
     */
    private static function get_default_terms_template() {
        $template = self::load_default_template_file('terms_default.html');
        if ($template !== '') {
            return $template;
        }

        return <<<'HTML'
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 11pt; line-height: 1.6; margin: 40px; }
        h1 { font-size: 20pt; margin-bottom: 6px; }
        h2 { font-size: 13pt; margin-top: 24px; }
        .meta { margin-bottom: 24px; color: #444; }
        .box { border: 1px solid #ddd; padding: 12px; background: #fafafa; }
    </style>
</head>
<body>
    <h1>Allgemeine Geschaeftsbedingungen</h1>
    <div class="meta">
        <p>{{company_name}} | {{company_address}} | {{company_email}}</p>
        <p>Kunde: {{customer_name}} | Bestellung: {{order_number}} vom {{order_date}}</p>
    </div>

    <h2>1. Vertragsgegenstand</h2>
    <p>Gegenstand dieses Vertrages ist die Bereitstellung von {{product_name}} gemaess den jeweils gueltigen Leistungsbeschreibungen.</p>

    <h2>2. Preise und Zahlung</h2>
    <p>Es gelten die zum Bestellzeitpunkt vereinbarten Preise. Rechnungen sind innerhalb der angegebenen Fristen zu begleichen.</p>

    <h2>3. Laufzeit und Kuendigung</h2>
    <p>Laufzeit und Kuendigungsbedingungen richten sich nach dem gewaehlten Vertragsmodell und den produktbezogenen Bedingungen.</p>

    <h2>4. Schlussbestimmungen</h2>
    <div class="box">
        <p>Sollten einzelne Bestimmungen unwirksam sein oder werden, bleibt die Wirksamkeit der uebrigen Bestimmungen unberuehrt.</p>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Default callback confirmation template
     */
    private static function get_default_callback_template() {
        $template = self::load_default_template_file('callback_default.html');
        if ($template !== '') {
            return $template;
        }

        return <<<'HTML'
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 12pt; line-height: 1.6; margin: 40px; }
        h1 { margin-bottom: 20px; }
        .meta { margin: 20px 0; }
    </style>
</head>
<body>
    <h1>Rueckrufbestaetigung</h1>
    <p>Sehr geehrte/r {{customer_name}},</p>
    <p>wir bestaetigen Ihren Rueckrufwunsch zur Bestellung {{order_number}} ({{order_date}}).</p>
    <div class="meta">
        <p>Unternehmen: {{customer_company}}</p>
        <p>E-Mail: {{customer_email}}</p>
    </div>
    <p>Unser Team meldet sich schnellstmoeglich bei Ihnen.</p>
    <p>Freundliche Gruesse<br>{{company_name}}<br>{{company_email}}</p>
</body>
</html>
HTML;
    }

    /**
     * Default payment request template
     */
    private static function get_default_payment_request_template() {
        $template = self::load_default_template_file('payment_request_default.html');
        if ($template !== '') {
            return $template;
        }

        return <<<'HTML'
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 12pt; line-height: 1.6; margin: 40px; }
        .notice { border: 1px solid #efc25c; background: #fff8e8; padding: 12px; margin: 18px 0; }
        .amount { font-size: 18pt; font-weight: bold; margin: 12px 0; }
    </style>
</head>
<body>
    <h1>Zahlungsaufforderung</h1>
    <p>Sehr geehrte/r {{customer_name}},</p>
    <p>bezugnehmend auf Ihre Bestellung {{order_number}} erinnern wir an die Zahlung der Rechnung {{invoice_number}}.</p>

    <div class="amount">Offener Betrag: {{total_amount}} {{currency}}</div>
    <p>Rechnungsdatum: {{invoice_date}}<br>Faelligkeitsdatum: {{due_date}}</p>

    <div class="notice">
        Bitte ueberweisen Sie den offenen Betrag unter Angabe der Rechnungsnummer.
    </div>

    <p>Bei Rueckfragen kontaktieren Sie uns bitte unter {{company_email}}.</p>
    <p>Freundliche Gruesse<br>{{company_name}}</p>
</body>
</html>
HTML;
    }

    /**
     * Default license document template
     */
    private static function get_default_license_template() {
        $template = self::load_default_template_file('license_default.html');
        if ($template !== '') {
            return $template;
        }

        return <<<'HTML'
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 12pt; line-height: 1.6; margin: 40px; }
        h1 { margin-bottom: 10px; }
        .meta { margin-bottom: 20px; color: #444; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { border: 1px solid #ddd; padding: 8px; }
        .info-table td:first-child { font-weight: bold; width: 30%; background: #f8f8f8; }
    </style>
</head>
<body>
    <h1>LIZENZDOKUMENT</h1>
    <div class="meta">
        <p>{{company_name}} | {{company_email}}</p>
    </div>

    <table class="info-table">
        <tr><td>Lizenzschluessel</td><td>{{license_key}}</td></tr>
        <tr><td>Lizenztyp</td><td>{{license_type}}</td></tr>
        <tr><td>Status</td><td>{{license_status}}</td></tr>
        <tr><td>Edition</td><td>{{product_edition}}</td></tr>
        <tr><td>Order</td><td>{{order_number}}</td></tr>
        <tr><td>Kunde</td><td>{{customer_name}} ({{customer_email}})</td></tr>
        <tr><td>Unternehmen</td><td>{{customer_company}}</td></tr>
        <tr><td>Max Nodes</td><td>{{max_nodes}}</td></tr>
        <tr><td>Max Cores</td><td>{{max_cores}}</td></tr>
        <tr><td>Max Storage (GB)</td><td>{{max_storage_gb}}</td></tr>
        <tr><td>Erstellt am</td><td>{{created_date}}</td></tr>
        <tr><td>Aktiviert am</td><td>{{activation_date}}</td></tr>
        <tr><td>Gueltig bis</td><td>{{expiry_date}}</td></tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Load a default template from templates/documents if present.
     */
    private static function load_default_template_file($file_name) {
        if (!defined('THEMISDB_ORDER_PLUGIN_DIR')) {
            return '';
        }

        $file_name = sanitize_file_name((string) $file_name);
        $path = trailingslashit(THEMISDB_ORDER_PLUGIN_DIR) . 'templates/documents/' . $file_name;

        if (!file_exists($path) || !is_readable($path)) {
            return '';
        }

        $content = file_get_contents($path);
        if (!is_string($content)) {
            return '';
        }

        $content = trim($content);
        return $content !== '' ? $content : '';
    }

    /**
     * Map system template IDs to template file names.
     */
    private static function get_default_template_file_name($template_id) {
        $map = array(
            'contract_default' => 'contract_default.html',
            'invoice_default' => 'invoice_default.html',
            'letter_default' => 'letter_default.html',
            'terms_default' => 'terms_default.html',
            'callback_default' => 'callback_default.html',
            'payment_request_default' => 'payment_request_default.html',
            'license_default' => 'license_default.html',
        );

        return isset($map[$template_id]) ? $map[$template_id] : '';
    }
}
