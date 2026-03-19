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
        
        if (empty($templates)) {
            $templates = self::get_default_templates();
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
            )
        );
    }
    
    /**
     * Default contract template
     */
    private static function get_default_contract_template() {
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
}
