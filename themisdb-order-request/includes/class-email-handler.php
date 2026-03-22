<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB - Hybrid Database System                                   ║
╠═════════════════════════════════════════════════════════════════════╣
  File:            class-email-handler.php                            ║
  Version:         0.0.2                                              ║
  Last Modified:   2026-03-09 04:08:19                                ║
  Author:          unknown                                            ║
╠═════════════════════════════════════════════════════════════════════╣
  Quality Metrics:                                                    ║
    • Maturity Level:  🟢 PRODUCTION-READY                             ║
    • Quality Score:   100.0/100                                      ║
    • Total Lines:     475                                            ║
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
 * Email Handler for ThemisDB Order Request Plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class ThemisDB_Email_Handler {
    
    /**
     * Send license cancellation email to the customer
     *
     * @param  int   $license_id  License row primary key.
     * @return bool  True if email was sent, false on failure.
     */
    public static function send_cancellation_email($license_id) {
        $license = ThemisDB_License_Manager::get_license($license_id);
        if (!$license) {
            return false;
        }
        
        $order = ThemisDB_Order_Manager::get_order($license['order_id']);
        if (!$order) {
            return false;
        }
        
        $to      = $order['customer_email'];
        $subject = sprintf(
            __('Ihre ThemisDB Lizenz %s wurde gekündigt', 'themisdb-order-request'),
            $license['license_key']
        );
        
        $message = self::get_cancellation_template($license, $order);
        
        return self::send_email(
            $to,
            $subject,
            $message,
            array(),   // no attachments
            $order['id'],
            null       // no contract_id applicable for cancellation emails
        );
    }
    
    /**
     * Send order confirmation email
     */
    public static function send_order_confirmation($order_id) {
        $order = ThemisDB_Order_Manager::get_order($order_id);
        
        if (!$order) {
            return false;
        }
        
        $to = $order['customer_email'];
        $subject = sprintf(__('Ihre Bestellung %s wurde bestätigt', 'themisdb-order-request'), $order['order_number']);
        
        $message = self::get_order_confirmation_template($order);
        
        // Generate PDF attachment
        $pdf_data = ThemisDB_PDF_Generator::generate_order_pdf($order_id);
        
        $attachments = array();
        if ($pdf_data) {
            $temp_file = tempnam(sys_get_temp_dir(), 'order_pdf');
            file_put_contents($temp_file, $pdf_data);
            $attachments[] = $temp_file;
        }
        
        $result = self::send_email($to, $subject, $message, $attachments, $order_id, null);
        
        // Clean up temp files
        if (!empty($attachments)) {
            foreach ($attachments as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Send contract email
     */
    public static function send_contract_email($contract_id) {
        $contract = ThemisDB_Contract_Manager::get_contract($contract_id);
        
        if (!$contract) {
            return false;
        }
        
        $order = ThemisDB_Order_Manager::get_order($contract['order_id']);
        
        $to = $order['customer_email'];
        $subject = sprintf(__('Ihr Vertrag %s', 'themisdb-order-request'), $contract['contract_number']);
        
        $message = self::get_contract_template($contract, $order);
        
        // Get PDF from contract
        $pdf_data = ThemisDB_PDF_Generator::get_contract_pdf($contract_id);
        
        $attachments = array();
        if ($pdf_data) {
            $temp_file = tempnam(sys_get_temp_dir(), 'contract_pdf');
            file_put_contents($temp_file, $pdf_data);
            $attachments[] = $temp_file;
        }
        
        $result = self::send_email($to, $subject, $message, $attachments, $order['id'], $contract_id);
        
        // Clean up temp files
        if (!empty($attachments)) {
            foreach ($attachments as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
        
        return $result;
    }

    /**
     * Send activated license file to customer.
     *
     * @param  int   $license_id License row primary key.
     * @return bool  True if email was sent, false on failure.
     */
    public static function send_license_email($license_id) {
        $license = ThemisDB_License_Manager::get_license($license_id);

        if (!$license) {
            return false;
        }

        $order = ThemisDB_Order_Manager::get_order($license['order_id']);
        if (!$order) {
            return false;
        }

        $to = $order['customer_email'];
        $subject = sprintf(
            __('Ihre ThemisDB Lizenz %s ist aktiv', 'themisdb-order-request'),
            $license['license_key']
        );

        $message = self::get_license_template($license, $order);

        $attachments = array();
        if (!empty($license['license_file_data'])) {
            $temp_file = tempnam(sys_get_temp_dir(), 'license_json');
            file_put_contents($temp_file, wp_json_encode($license['license_file_data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $attachments[] = $temp_file;
        }

        $result = self::send_email($to, $subject, $message, $attachments, $order['id'], $license['contract_id']);

        if (!empty($attachments)) {
            foreach ($attachments as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }

        return $result;
    }
    
    /**
     * Send email with logging
     */
    private static function send_email($to, $subject, $message, $attachments = array(), $order_id = null, $contract_id = null) {
        global $wpdb;
        
        // Get from email settings
        $from_email = get_option('themisdb_order_email_from', get_option('admin_email'));
        $from_name = get_option('themisdb_order_email_from_name', get_option('blogname'));
        
        // Set headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            sprintf('From: %s <%s>', $from_name, $from_email)
        );
        
        // Log email
        $table_email_log = $wpdb->prefix . 'themisdb_email_log';
        $log_data = array(
            'order_id' => $order_id,
            'contract_id' => $contract_id,
            'recipient' => $to,
            'subject' => $subject,
            'body' => $message,
            'attachments' => !empty($attachments) ? json_encode($attachments) : null,
            'status' => 'pending'
        );
        
        $wpdb->insert($table_email_log, $log_data);
        $log_id = $wpdb->insert_id;
        
        // Send email
        $result = wp_mail($to, $subject, $message, $headers, $attachments);
        
        // Update log
        if ($result) {
            $wpdb->update(
                $table_email_log,
                array(
                    'status' => 'sent',
                    'sent_at' => current_time('mysql')
                ),
                array('id' => $log_id),
                null,
                array('%d')
            );
        } else {
            $wpdb->update(
                $table_email_log,
                array(
                    'status' => 'failed',
                    'error_message' => 'wp_mail returned false'
                ),
                array('id' => $log_id),
                null,
                array('%d')
            );
        }
        
        return $result;
    }
    
    /**
     * Get order confirmation email template
     */
    private static function get_order_confirmation_template($order) {
        $agb_url = get_option('themisdb_order_legal_agb_url', home_url('/agb'));
        $privacy_url = get_option('themisdb_order_legal_privacy_url', home_url('/datenschutz'));
        $withdrawal_url = get_option('themisdb_order_legal_withdrawal_url', home_url('/widerruf'));
        $is_consumer = (($order['customer_type'] ?? 'consumer') === 'consumer');

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                }
                .header {
                    background-color: #0073aa;
                    color: white;
                    padding: 20px;
                    text-align: center;
                }
                .content {
                    padding: 20px;
                    background-color: #f9f9f9;
                }
                .order-details {
                    background-color: white;
                    padding: 15px;
                    margin: 20px 0;
                    border: 1px solid #ddd;
                }
                .footer {
                    text-align: center;
                    padding: 20px;
                    color: #666;
                    font-size: 12px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                }
                th, td {
                    padding: 8px;
                    text-align: left;
                    border-bottom: 1px solid #ddd;
                }
                th {
                    background-color: #f5f5f5;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Bestellbestätigung</h1>
                </div>
                
                <div class="content">
                    <p>Sehr geehrte(r) <?php echo esc_html($order['customer_name']); ?>,</p>
                    
                    <p>vielen Dank für Ihre Bestellung bei ThemisDB. Wir haben Ihre Bestellung mit der Nummer <strong><?php echo esc_html($order['order_number']); ?></strong> erhalten und bearbeiten diese umgehend.</p>
                    
                    <div class="order-details">
                        <h2>Bestelldetails</h2>
                        
                        <table>
                            <tr>
                                <th>Bestellnummer:</th>
                                <td><?php echo esc_html($order['order_number']); ?></td>
                            </tr>
                            <tr>
                                <th>Bestelldatum:</th>
                                <td><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?> Uhr</td>
                            </tr>
                            <tr>
                                <th>Produkt:</th>
                                <td>ThemisDB <?php echo esc_html(ucfirst($order['product_edition'])); ?> Edition</td>
                            </tr>
                            <tr>
                                <th>Gesamtbetrag:</th>
                                <td><strong><?php echo number_format($order['total_amount'], 2, ',', '.'); ?> <?php echo esc_html($order['currency']); ?></strong></td>
                            </tr>
                        </table>
                        
                        <?php if (!empty($order['modules'])): ?>
                        <h3>Ausgewählte Module:</h3>
                        <ul>
                            <?php 
                            $modules = ThemisDB_Order_Manager::get_modules();
                            foreach ($modules as $module):
                                if (in_array($module['module_code'], $order['modules'])):
                            ?>
                            <li><?php echo esc_html($module['module_name']); ?></li>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </ul>
                        <?php endif; ?>
                        
                        <?php if (!empty($order['training_modules'])): ?>
                        <h3>Ausgewählte Schulungen:</h3>
                        <ul>
                            <?php 
                            $trainings = ThemisDB_Order_Manager::get_training_modules();
                            foreach ($trainings as $training):
                                if (in_array($training['training_code'], $order['training_modules'])):
                            ?>
                            <li><?php echo esc_html($training['training_name']); ?></li>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </ul>
                        <?php endif; ?>
                    </div>
                    
                    <p>Eine detaillierte Bestellbestätigung finden Sie im Anhang dieser E-Mail.</p>

                    <div class="order-details">
                        <h2>Rechtliche Informationen</h2>
                        <ul>
                            <li><a href="<?php echo esc_url($agb_url); ?>" target="_blank" rel="noopener noreferrer">AGB</a></li>
                            <li><a href="<?php echo esc_url($privacy_url); ?>" target="_blank" rel="noopener noreferrer">Datenschutzerklärung</a></li>
                            <li><a href="<?php echo esc_url($withdrawal_url); ?>" target="_blank" rel="noopener noreferrer">Widerrufsbelehrung</a></li>
                        </ul>
                        <p style="margin-top:.75rem; font-size:.95em;">
                            Erfasste Zustimmungsversion: <strong><?php echo esc_html($order['legal_acceptance_version'] ?? 'de-v1'); ?></strong>
                            <?php if (!empty($order['legal_accepted_at'])): ?>
                                (<?php echo esc_html(date('d.m.Y H:i', strtotime($order['legal_accepted_at']))); ?> Uhr)
                            <?php endif; ?>
                        </p>
                        <?php if ($is_consumer && empty($order['legal_withdrawal_waiver'])): ?>
                            <p style="margin-top:.5rem; color:#6b7280; font-size:.92em;">
                                Für Verbraucher beginnt die Bereitstellung digitaler Leistungen erst nach zusätzlicher ausdrücklicher Zustimmung zum vorzeitigen Leistungsbeginn.
                            </p>
                        <?php endif; ?>
                    </div>
                    
                    <p>Bei Fragen zu Ihrer Bestellung stehen wir Ihnen gerne zur Verfügung.</p>
                    
                    <p>Mit freundlichen Grüßen<br>
                    Ihr ThemisDB Team</p>
                </div>
                
                <div class="footer">
                    <p><?php echo esc_html(get_option('blogname')); ?><br>
                    <?php echo esc_html(get_option('admin_email')); ?></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get contract email template
     */
    private static function get_contract_template($contract, $order) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                }
                .header {
                    background-color: #0073aa;
                    color: white;
                    padding: 20px;
                    text-align: center;
                }
                .content {
                    padding: 20px;
                    background-color: #f9f9f9;
                }
                .contract-details {
                    background-color: white;
                    padding: 15px;
                    margin: 20px 0;
                    border: 1px solid #ddd;
                }
                .footer {
                    text-align: center;
                    padding: 20px;
                    color: #666;
                    font-size: 12px;
                }
                .important {
                    background-color: #fff3cd;
                    border: 1px solid #ffc107;
                    padding: 15px;
                    margin: 20px 0;
                    border-radius: 4px;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Ihr Vertrag</h1>
                </div>
                
                <div class="content">
                    <p>Sehr geehrte(r) <?php echo esc_html($order['customer_name']); ?>,</p>
                    
                    <p>anbei erhalten Sie Ihren Vertrag mit der Nummer <strong><?php echo esc_html($contract['contract_number']); ?></strong> im Anhang.</p>
                    
                    <div class="contract-details">
                        <h2>Vertragsdetails</h2>
                        
                        <table style="width: 100%;">
                            <tr>
                                <td style="width: 40%; padding: 8px; border-bottom: 1px solid #ddd;"><strong>Vertragsnummer:</strong></td>
                                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo esc_html($contract['contract_number']); ?></td>
                            </tr>
                            <tr>
                                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Vertragstyp:</strong></td>
                                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo esc_html(ucfirst($contract['contract_type'])); ?></td>
                            </tr>
                            <tr>
                                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Gültig von:</strong></td>
                                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo date('d.m.Y', strtotime($contract['valid_from'])); ?></td>
                            </tr>
                            <?php if ($contract['valid_until']): ?>
                            <tr>
                                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Gültig bis:</strong></td>
                                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo date('d.m.Y', strtotime($contract['valid_until'])); ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Status:</strong></td>
                                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo esc_html(ucfirst($contract['status'])); ?></td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="important">
                        <strong>Wichtig:</strong> Bitte prüfen Sie den Vertrag sorgfältig. Bei Fragen oder Änderungswünschen kontaktieren Sie uns bitte umgehend.
                    </div>
                    
                    <p>Um den Vertrag rechtsverbindlich abzuschließen, senden Sie uns bitte den unterschriebenen Vertrag zurück.</p>
                    
                    <p>Mit freundlichen Grüßen<br>
                    Ihr ThemisDB Team</p>
                </div>
                
                <div class="footer">
                    <p><?php echo esc_html(get_option('blogname')); ?><br>
                    <?php echo esc_html(get_option('admin_email')); ?></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get cancellation email HTML template
     *
     * @param  array  $license  License row.
     * @param  array  $order    Associated order row.
     * @return string HTML body.
     */
    private static function get_cancellation_template($license, $order) {
        $cancellation_date = !empty($license['cancellation_date'])
            ? date('d.m.Y H:i', strtotime($license['cancellation_date'])) . ' Uhr'
            : date('d.m.Y H:i') . ' Uhr';
        $reason = !empty($license['cancellation_reason']) ? $license['cancellation_reason'] : '—';
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #721c24; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .license-details { background-color: white; padding: 15px; margin: 20px 0; border: 1px solid #ddd; }
                .warning { background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 20px 0; border-radius: 4px; color: #721c24; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                table { width: 100%; border-collapse: collapse; }
                th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
                th { background-color: #f5f5f5; width: 40%; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1><?php esc_html_e('Lizenzkündigung', 'themisdb-order-request'); ?></h1>
                </div>
                
                <div class="content">
                    <p><?php echo esc_html(sprintf(__('Sehr geehrte(r) %s,', 'themisdb-order-request'), $order['customer_name'])); ?></p>
                    
                    <p><?php esc_html_e('wir bestätigen hiermit die Kündigung Ihrer ThemisDB Lizenz. Bitte beachten Sie, dass die Lizenz ab sofort nicht mehr genutzt werden kann.', 'themisdb-order-request'); ?></p>
                    
                    <div class="warning">
                        <strong><?php esc_html_e('Wichtiger Hinweis:', 'themisdb-order-request'); ?></strong>
                        <?php esc_html_e('Diese Kündigung ist unwiderruflich. Die Lizenz wurde dauerhaft deaktiviert und kann nicht wiederhergestellt werden. Bitte sichern Sie Ihre Daten rechtzeitig.', 'themisdb-order-request'); ?>
                    </div>
                    
                    <div class="license-details">
                        <h2><?php esc_html_e('Lizenzdetails', 'themisdb-order-request'); ?></h2>
                        <table>
                            <tr>
                                <th><?php esc_html_e('Lizenzschlüssel:', 'themisdb-order-request'); ?></th>
                                <td><code><?php echo esc_html($license['license_key']); ?></code></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Edition:', 'themisdb-order-request'); ?></th>
                                <td>ThemisDB <?php echo esc_html(ucfirst($license['product_edition'])); ?> Edition</td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Bestellnummer:', 'themisdb-order-request'); ?></th>
                                <td><?php echo esc_html($order['order_number']); ?></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Kündigungsdatum:', 'themisdb-order-request'); ?></th>
                                <td><?php echo esc_html($cancellation_date); ?></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Kündigungsgrund:', 'themisdb-order-request'); ?></th>
                                <td><?php echo esc_html($reason); ?></td>
                            </tr>
                        </table>
                    </div>
                    
                    <p><?php esc_html_e('Falls Sie der Meinung sind, dass diese Kündigung irrtümlich erfolgt ist, kontaktieren Sie uns bitte umgehend.', 'themisdb-order-request'); ?></p>
                    
                    <p><?php esc_html_e('Mit freundlichen Grüßen', 'themisdb-order-request'); ?><br>
                    <?php esc_html_e('Ihr ThemisDB Team', 'themisdb-order-request'); ?></p>
                </div>
                
                <div class="footer">
                    <p><?php echo esc_html(get_option('blogname')); ?><br>
                    <?php echo esc_html(get_option('admin_email')); ?></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Get activated license email HTML template.
     *
     * @param  array  $license License row.
     * @param  array  $order   Associated order row.
     * @return string HTML body.
     */
    private static function get_license_template($license, $order) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #155724; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .license-details { background-color: white; padding: 15px; margin: 20px 0; border: 1px solid #ddd; }
                .success { background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 20px 0; border-radius: 4px; color: #155724; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                table { width: 100%; border-collapse: collapse; }
                th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
                th { background-color: #f5f5f5; width: 40%; }
                code { font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1><?php esc_html_e('Ihre Lizenz ist aktiv', 'themisdb-order-request'); ?></h1>
                </div>

                <div class="content">
                    <p><?php echo esc_html(sprintf(__('Sehr geehrte(r) %s,', 'themisdb-order-request'), $order['customer_name'])); ?></p>

                    <div class="success">
                        <strong><?php esc_html_e('Zahlung erfolgreich verifiziert.', 'themisdb-order-request'); ?></strong>
                        <?php esc_html_e('Ihre ThemisDB Lizenz wurde aktiviert und die Lizenzdatei ist dieser E-Mail beigefügt.', 'themisdb-order-request'); ?>
                    </div>

                    <div class="license-details">
                        <h2><?php esc_html_e('Lizenzdetails', 'themisdb-order-request'); ?></h2>
                        <table>
                            <tr>
                                <th><?php esc_html_e('Lizenzschlüssel:', 'themisdb-order-request'); ?></th>
                                <td><code><?php echo esc_html($license['license_key']); ?></code></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Edition:', 'themisdb-order-request'); ?></th>
                                <td><?php echo esc_html(ucfirst($license['product_edition'])); ?></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Status:', 'themisdb-order-request'); ?></th>
                                <td><?php echo esc_html(ucfirst($license['license_status'])); ?></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Aktivierungsdatum:', 'themisdb-order-request'); ?></th>
                                <td><?php echo !empty($license['activation_date']) ? esc_html(date_i18n('d.m.Y H:i', strtotime($license['activation_date']))) : '—'; ?></td>
                            </tr>
                            <?php if (!empty($license['expiry_date'])): ?>
                            <tr>
                                <th><?php esc_html_e('Ablaufdatum:', 'themisdb-order-request'); ?></th>
                                <td><?php echo esc_html(date_i18n('d.m.Y', strtotime($license['expiry_date']))); ?></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>

                    <p><?php esc_html_e('Sie können die Lizenzdatei direkt verwenden oder zusätzlich über das Lizenzportal herunterladen.', 'themisdb-order-request'); ?></p>
                    <p><?php esc_html_e('Mit freundlichen Grüßen', 'themisdb-order-request'); ?><br><?php esc_html_e('Ihr ThemisDB Team', 'themisdb-order-request'); ?></p>
                </div>

                <div class="footer">
                    <p><?php echo esc_html(get_option('blogname')); ?><br><?php echo esc_html(get_option('admin_email')); ?></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Send invoice email to customer
     *
     * @param  int   $order_id  Order row primary key.
     * @return bool  True if email was sent, false on failure.
     */
    public static function send_invoice_email($order_id) {
        $order = ThemisDB_Order_Manager::get_order($order_id);

        if (!$order) {
            return false;
        }

        $to      = $order['customer_email'];
        $subject = sprintf(
            __('Rechnung fuer Bestellung %s - ThemisDB', 'themisdb-order-request'),
            $order['order_number']
        );

        $message = self::get_invoice_template($order);

        // Generate invoice PDF with order details
        $invoice_data = self::generate_invoice_data($order);

        $attachments = array();
        if ($invoice_data) {
            $temp_file = tempnam(sys_get_temp_dir(), 'invoice_pdf');
            file_put_contents($temp_file, $invoice_data);
            $attachments[] = $temp_file;
        }

        $result = self::send_email($to, $subject, $message, $attachments, $order_id, null);

        // Clean up temp files
        if (!empty($attachments)) {
            foreach ($attachments as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }

        return $result;
    }

    /**
     * Generate invoice PDF data from order.
     *
     * @param  array  $order  Order row.
     * @return string|false PDF binary or false.
     */
    private static function generate_invoice_data($order) {
        $pdf_data = ThemisDB_Document_Renderer::render_invoice_pdf($order, 'invoice_default');

        if ($pdf_data !== false) {
            return $pdf_data;
        }

        // Hard fallback for backward compatibility when template rendering fails.
        return ThemisDB_PDF_Generator::generate_invoice_pdf($order['id']);
    }

    /**
     * Generate unique invoice number.
     */
    private static function generate_invoice_number($order) {
        return ThemisDB_Document_Renderer::generate_invoice_number($order);
    }

    /**
     * Get invoice email HTML template.
     *
     * @param  array  $order  Order row.
     * @return string HTML body.
     */
    private static function get_invoice_template($order) {
        $invoice_number = self::generate_invoice_number($order);

        ob_start();
            ?>
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        line-height: 1.6;
                        color: #333;
                    }
                    .container {
                        max-width: 600px;
                        margin: 0 auto;
                        padding: 20px;
                    }
                    .header {
                        background-color: #0073aa;
                        color: white;
                        padding: 20px;
                        text-align: center;
                    }
                    .content {
                        padding: 20px;
                        background-color: #f9f9f9;
                    }
                    .invoice-details {
                        background-color: white;
                        padding: 15px;
                        margin: 20px 0;
                        border: 1px solid #ddd;
                    }
                    .payment-terms {
                        background-color: #e3f2fd;
                        border: 1px solid #2196F3;
                        padding: 15px;
                        margin: 20px 0;
                        border-radius: 4px;
                    }
                    .footer {
                        text-align: center;
                        padding: 20px;
                        color: #666;
                        font-size: 12px;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                    }
                    th, td {
                        padding: 8px;
                        text-align: left;
                        border-bottom: 1px solid #ddd;
                    }
                    th {
                        background-color: #f5f5f5;
                        font-weight: bold;
                    }
                    .amount {
                        text-align: right;
                    }
                    .total-row {
                        background-color: #f0f0f0;
                        font-weight: bold;
                        font-size: 16px;
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h1><?php esc_html_e('Rechnung', 'themisdb-order-request'); ?></h1>
                    </div>
                
                    <div class="content">
                        <p><?php echo esc_html(sprintf(__('Sehr geehrte(r) %s,', 'themisdb-order-request'), $order['customer_name'])); ?></p>
                    
                        <p><?php esc_html_e('anbei finden Sie die Rechnung für Ihre Bestellung bei ThemisDB.', 'themisdb-order-request'); ?></p>
                    
                        <div class="invoice-details">
                            <h2><?php esc_html_e('Rechnungsdetails', 'themisdb-order-request'); ?></h2>
                        
                            <table>
                                <tr>
                                    <th><?php esc_html_e('Rechnungsnummer:', 'themisdb-order-request'); ?></th>
                                    <td><?php echo esc_html($invoice_number); ?></td>
                                    <th><?php esc_html_e('Bestellnummer:', 'themisdb-order-request'); ?></th>
                                    <td><?php echo esc_html($order['order_number']); ?></td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e('Rechnungsdatum:', 'themisdb-order-request'); ?></th>
                                    <td><?php echo date_i18n('d.m.Y'); ?></td>
                                    <th><?php esc_html_e('Bestelldatum:', 'themisdb-order-request'); ?></th>
                                    <td><?php echo date_i18n('d.m.Y', strtotime($order['created_at'])); ?></td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e('Kunde:', 'themisdb-order-request'); ?></th>
                                    <td>
                                        <?php echo esc_html($order['customer_name']); ?>
                                        <?php if (!empty($order['customer_company'])): ?>
                                            <br><?php echo esc_html($order['customer_company']); ?>
                                        <?php endif; ?>
                                    </td>
                                    <th><?php esc_html_e('Fälligkeitsdatum:', 'themisdb-order-request'); ?></th>
                                    <td><?php echo date_i18n('d.m.Y', strtotime('+14 days')); ?></td>
                                </tr>
                            </table>
                        </div>
                    
                        <div class="invoice-details">
                            <h2><?php esc_html_e('Leistungsübersicht', 'themisdb-order-request'); ?></h2>
                            <table>
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Position', 'themisdb-order-request'); ?></th>
                                        <th class="amount"><?php esc_html_e('Menge', 'themisdb-order-request'); ?></th>
                                        <th class="amount"><?php esc_html_e('Einzelpreis', 'themisdb-order-request'); ?></th>
                                        <th class="amount"><?php esc_html_e('Gesamtpreis', 'themisdb-order-request'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><?php echo esc_html(sprintf(__('ThemisDB %s Edition', 'themisdb-order-request'), ucfirst($order['product_edition']))); ?></td>
                                        <td class="amount">1</td>
                                        <td class="amount"><?php echo number_format($order['total_amount'], 2, ',', '.'); ?> <?php echo esc_html($order['currency']); ?></td>
                                        <td class="amount"><?php echo number_format($order['total_amount'], 2, ',', '.'); ?> <?php echo esc_html($order['currency']); ?></td>
                                    </tr>
                                    <tr class="total-row">
                                        <td colspan="3" class="amount"><?php esc_html_e('Gesamtsumme:', 'themisdb-order-request'); ?></td>
                                        <td class="amount"><?php echo number_format($order['total_amount'], 2, ',', '.'); ?> <?php echo esc_html($order['currency']); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    
                        <div class="payment-terms">
                            <h3><?php esc_html_e('💳 Zahlungsinformationen', 'themisdb-order-request'); ?></h3>
                            <p><strong><?php esc_html_e('Zahlungsart:', 'themisdb-order-request'); ?></strong> Banküberweisung oder Kreditkarte</p>
                            <p><strong><?php esc_html_e('Verwendungszweck:', 'themisdb-order-request'); ?></strong> <code><?php echo esc_html($order['order_number']); ?></code></p>
                            <p><?php esc_html_e('Die Zahlung ist innerhalb von 14 Tagen nach Rechnungsdatum fällig.', 'themisdb-order-request'); ?></p>
                        </div>
                    
                        <p><?php esc_html_e('Für Fragen zu dieser Rechnung stehen wir Ihnen gerne zur Verfügung.', 'themisdb-order-request'); ?></p>
                    
                        <p><?php esc_html_e('Mit freundlichen Grüßen', 'themisdb-order-request'); ?><br>
                        <?php esc_html_e('Ihr ThemisDB Team', 'themisdb-order-request'); ?></p>
                    </div>
                
                    <div class="footer">
                        <p><?php echo esc_html(get_option('blogname')); ?><br>
                        <?php echo esc_html(get_option('admin_email')); ?></p>
                    </div>
                </div>
            </body>
            </html>
            <?php
        return ob_get_clean();
    }

    /**
     * Send support benefit expiry notification email.
     *
     * @param  int    $benefit_id       Support benefit ID.
     * @param  int    $days_until_expiry Days remaining before expiry.
     * @return bool   True on success, false on failure.
     */
    public static function send_support_expiry_notification($benefit_id, $days_until_expiry = 30) {
        if (!class_exists('ThemisDB_License_Manager') || !class_exists('ThemisDB_Order_Manager')) {
            return false;
        }

        // Load the benefit's license and order to get customer contact data.
        $benefit_id = intval($benefit_id);
        $table_benefits = $GLOBALS['wpdb']->prefix . 'themisdb_support_benefits';
        $benefit = $GLOBALS['wpdb']->get_row(
            $GLOBALS['wpdb']->prepare("SELECT * FROM $table_benefits WHERE id = %d", $benefit_id),
            ARRAY_A
        );
        if (!$benefit) {
            return false;
        }

        $license = ThemisDB_License_Manager::get_license(intval($benefit['license_id']));
        if (!$license) {
            return false;
        }

        $order = ThemisDB_Order_Manager::get_order(intval($license['order_id']));
        if (!$order) {
            return false;
        }

        $to = $order['customer_email'];
        $subject = sprintf(
            /* translators: %d: days until expiry */
            _n(
                'Ihr ThemisDB Support-Paket läuft in %d Tag ab',
                'Ihr ThemisDB Support-Paket läuft in %d Tagen ab',
                intval($days_until_expiry),
                'themisdb-order-request'
            ),
            intval($days_until_expiry)
        );

        $message = self::get_support_expiry_template($benefit, $license, $order, intval($days_until_expiry));

        return self::send_email($to, $subject, $message, array(), intval($order['id']), null);
    }

    /**
     * Build HTML email body for support benefit expiry notification.
     *
     * @param  array  $benefit          Support benefit row.
     * @param  array  $license          License row.
     * @param  array  $order            Order row.
     * @param  int    $days_until_expiry
     * @return string HTML body.
     */
    private static function get_support_expiry_template($benefit, $license, $order, $days_until_expiry) {
        $expires_formatted = !empty($benefit['expires_at'])
            ? date_i18n('d.m.Y', strtotime($benefit['expires_at']))
            : '—';

        $tier_label = ucfirst(sanitize_text_field($benefit['tier_level'] ?? 'community'));

        $sla_hours = isset($benefit['response_sla_hours']) ? intval($benefit['response_sla_hours']) : null;
        $max_tickets = isset($benefit['max_open_tickets']) ? intval($benefit['max_open_tickets']) : -1;
        $included_hours = isset($benefit['included_hours_per_month']) ? intval($benefit['included_hours_per_month']) : 0;

        $header_color = $days_until_expiry <= 7 ? '#856404' : '#0c5460';
        $header_bg    = $days_until_expiry <= 7 ? '#fff3cd' : '#d1ecf1';
        $border_color = $days_until_expiry <= 7 ? '#ffc107' : '#17a2b8';

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #1a237e; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .notice { background-color: <?php echo esc_attr($header_bg); ?>; border: 1px solid <?php echo esc_attr($border_color); ?>; color: <?php echo esc_attr($header_color); ?>; padding: 15px; margin: 20px 0; border-radius: 4px; }
                .benefit-details { background-color: white; padding: 15px; margin: 20px 0; border: 1px solid #ddd; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                table { width: 100%; border-collapse: collapse; }
                th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
                th { background-color: #f5f5f5; width: 40%; }
                .cta { display: inline-block; margin-top: 12px; padding: 10px 24px; background-color: #1a237e; color: white; text-decoration: none; border-radius: 4px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1><?php esc_html_e('Support-Paket läuft bald ab', 'themisdb-order-request'); ?></h1>
                </div>
                <div class="content">
                    <p><?php echo esc_html(sprintf(__('Sehr geehrte(r) %s,', 'themisdb-order-request'), $order['customer_name'])); ?></p>

                    <div class="notice">
                        <?php if ($days_until_expiry <= 1) : ?>
                            <strong><?php esc_html_e('Dringende Benachrichtigung:', 'themisdb-order-request'); ?></strong>
                            <?php esc_html_e('Ihr ThemisDB Support-Paket läuft heute ab. Bitte verlängern Sie umgehend, um die Unterstützung nicht zu unterbrechen.', 'themisdb-order-request'); ?>
                        <?php elseif ($days_until_expiry <= 7) : ?>
                            <strong><?php esc_html_e('Wichtige Hinweis:', 'themisdb-order-request'); ?></strong>
                            <?php echo esc_html(sprintf(
                                __('Ihr Support-Paket läuft in %d Tagen ab. Handeln Sie jetzt, um eine Unterbrechung zu vermeiden.', 'themisdb-order-request'),
                                $days_until_expiry
                            )); ?>
                        <?php else : ?>
                            <?php echo esc_html(sprintf(
                                __('Wir möchten Sie darauf hinweisen, dass Ihr ThemisDB Support-Paket in %d Tagen abläuft.', 'themisdb-order-request'),
                                $days_until_expiry
                            )); ?>
                        <?php endif; ?>
                    </div>

                    <div class="benefit-details">
                        <h2><?php esc_html_e('Ihr aktuelles Support-Paket', 'themisdb-order-request'); ?></h2>
                        <table>
                            <tr>
                                <th><?php esc_html_e('Lizenzschlüssel:', 'themisdb-order-request'); ?></th>
                                <td><code><?php echo esc_html($license['license_key']); ?></code></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Edition:', 'themisdb-order-request'); ?></th>
                                <td>ThemisDB <?php echo esc_html($tier_label); ?> Edition</td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Ablaufdatum:', 'themisdb-order-request'); ?></th>
                                <td><strong><?php echo esc_html($expires_formatted); ?></strong></td>
                            </tr>
                            <?php if ($sla_hours) : ?>
                            <tr>
                                <th><?php esc_html_e('Reaktionszeit (SLA):', 'themisdb-order-request'); ?></th>
                                <td><?php echo esc_html(sprintf(__('%d Stunden', 'themisdb-order-request'), $sla_hours)); ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <th><?php esc_html_e('Max. offene Tickets:', 'themisdb-order-request'); ?></th>
                                <td><?php echo $max_tickets === -1 ? esc_html__('Unbegrenzt', 'themisdb-order-request') : esc_html($max_tickets); ?></td>
                            </tr>
                            <?php if ($included_hours > 0 || $included_hours === -1) : ?>
                            <tr>
                                <th><?php esc_html_e('Inkludierte Stunden/Monat:', 'themisdb-order-request'); ?></th>
                                <td><?php echo $included_hours === -1 ? esc_html__('Unbegrenzt', 'themisdb-order-request') : esc_html(sprintf(__('%d Std.', 'themisdb-order-request'), $included_hours)); ?></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>

                    <p><?php esc_html_e('Um Ihren Support-Zugang ohne Unterbrechung zu erhalten, empfehlen wir eine rechtzeitige Verlängerung.', 'themisdb-order-request'); ?></p>
                    <p><?php esc_html_e('Für Fragen zur Verlängerung oder einem Upgrade stehen wir Ihnen gerne zur Verfügung.', 'themisdb-order-request'); ?></p>

                    <p>
                        <a href="<?php echo esc_url(home_url('/support/')); ?>" class="cta"><?php esc_html_e('Support-Paket verlängern', 'themisdb-order-request'); ?></a>
                    </p>

                    <p><?php esc_html_e('Mit freundlichen Grüßen', 'themisdb-order-request'); ?><br>
                    <?php esc_html_e('Ihr ThemisDB Team', 'themisdb-order-request'); ?></p>
                </div>
                <div class="footer">
                    <p><?php echo esc_html(get_option('blogname')); ?><br>
                    <?php echo esc_html(get_option('admin_email')); ?></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Get email logs
     */
    public static function get_email_logs($args = array()) {
        global $wpdb;

        $table_email_log = $wpdb->prefix . 'themisdb_email_log';
        
        $defaults = array(
            'order_id' => null,
            'contract_id' => null,
            'status' => null,
            'limit' => 50,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);

        $allowed_orderby = array('id', 'order_id', 'contract_id', 'recipient_email', 'subject', 'status', 'sent_at', 'created_at');
        $orderby = in_array($args['orderby'], $allowed_orderby, true) ? $args['orderby'] : 'created_at';
        $order = strtoupper((string) $args['order']) === 'ASC' ? 'ASC' : 'DESC';
        $limit = max(1, absint($args['limit']));
        $offset = max(0, absint($args['offset']));
        
        $where = "1=1";
        $where_values = array();
        
        if ($args['order_id']) {
            $where .= " AND order_id = %d";
            $where_values[] = $args['order_id'];
        }
        
        if ($args['contract_id']) {
            $where .= " AND contract_id = %d";
            $where_values[] = $args['contract_id'];
        }
        
        if ($args['status']) {
            $where .= " AND status = %s";
            $where_values[] = $args['status'];
        }
        
        $query = "SELECT * FROM $table_email_log WHERE $where ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
        $where_values[] = $limit;
        $where_values[] = $offset;
        
        return $wpdb->get_results($wpdb->prepare($query, $where_values), ARRAY_A);
    }
}
