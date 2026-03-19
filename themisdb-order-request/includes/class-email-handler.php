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
                    <h1><?php _e('Lizenzkündigung', 'themisdb-order-request'); ?></h1>
                </div>
                
                <div class="content">
                    <p><?php echo esc_html(sprintf(__('Sehr geehrte(r) %s,', 'themisdb-order-request'), $order['customer_name'])); ?></p>
                    
                    <p><?php _e('wir bestätigen hiermit die Kündigung Ihrer ThemisDB Lizenz. Bitte beachten Sie, dass die Lizenz ab sofort nicht mehr genutzt werden kann.', 'themisdb-order-request'); ?></p>
                    
                    <div class="warning">
                        <strong><?php _e('Wichtiger Hinweis:', 'themisdb-order-request'); ?></strong>
                        <?php _e('Diese Kündigung ist unwiderruflich. Die Lizenz wurde dauerhaft deaktiviert und kann nicht wiederhergestellt werden. Bitte sichern Sie Ihre Daten rechtzeitig.', 'themisdb-order-request'); ?>
                    </div>
                    
                    <div class="license-details">
                        <h2><?php _e('Lizenzdetails', 'themisdb-order-request'); ?></h2>
                        <table>
                            <tr>
                                <th><?php _e('Lizenzschlüssel:', 'themisdb-order-request'); ?></th>
                                <td><code><?php echo esc_html($license['license_key']); ?></code></td>
                            </tr>
                            <tr>
                                <th><?php _e('Edition:', 'themisdb-order-request'); ?></th>
                                <td>ThemisDB <?php echo esc_html(ucfirst($license['product_edition'])); ?> Edition</td>
                            </tr>
                            <tr>
                                <th><?php _e('Bestellnummer:', 'themisdb-order-request'); ?></th>
                                <td><?php echo esc_html($order['order_number']); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Kündigungsdatum:', 'themisdb-order-request'); ?></th>
                                <td><?php echo esc_html($cancellation_date); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Kündigungsgrund:', 'themisdb-order-request'); ?></th>
                                <td><?php echo esc_html($reason); ?></td>
                            </tr>
                        </table>
                    </div>
                    
                    <p><?php _e('Falls Sie der Meinung sind, dass diese Kündigung irrtümlich erfolgt ist, kontaktieren Sie uns bitte umgehend.', 'themisdb-order-request'); ?></p>
                    
                    <p><?php _e('Mit freundlichen Grüßen', 'themisdb-order-request'); ?><br>
                    <?php _e('Ihr ThemisDB Team', 'themisdb-order-request'); ?></p>
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
                    <h1><?php _e('Ihre Lizenz ist aktiv', 'themisdb-order-request'); ?></h1>
                </div>

                <div class="content">
                    <p><?php echo esc_html(sprintf(__('Sehr geehrte(r) %s,', 'themisdb-order-request'), $order['customer_name'])); ?></p>

                    <div class="success">
                        <strong><?php _e('Zahlung erfolgreich verifiziert.', 'themisdb-order-request'); ?></strong>
                        <?php _e('Ihre ThemisDB Lizenz wurde aktiviert und die Lizenzdatei ist dieser E-Mail beigefügt.', 'themisdb-order-request'); ?>
                    </div>

                    <div class="license-details">
                        <h2><?php _e('Lizenzdetails', 'themisdb-order-request'); ?></h2>
                        <table>
                            <tr>
                                <th><?php _e('Lizenzschlüssel:', 'themisdb-order-request'); ?></th>
                                <td><code><?php echo esc_html($license['license_key']); ?></code></td>
                            </tr>
                            <tr>
                                <th><?php _e('Edition:', 'themisdb-order-request'); ?></th>
                                <td><?php echo esc_html(ucfirst($license['product_edition'])); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Status:', 'themisdb-order-request'); ?></th>
                                <td><?php echo esc_html(ucfirst($license['license_status'])); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Aktivierungsdatum:', 'themisdb-order-request'); ?></th>
                                <td><?php echo !empty($license['activation_date']) ? esc_html(date_i18n('d.m.Y H:i', strtotime($license['activation_date']))) : '—'; ?></td>
                            </tr>
                            <?php if (!empty($license['expiry_date'])): ?>
                            <tr>
                                <th><?php _e('Ablaufdatum:', 'themisdb-order-request'); ?></th>
                                <td><?php echo esc_html(date_i18n('d.m.Y', strtotime($license['expiry_date']))); ?></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>

                    <p><?php _e('Sie können die Lizenzdatei direkt verwenden oder zusätzlich über das Lizenzportal herunterladen.', 'themisdb-order-request'); ?></p>
                    <p><?php _e('Mit freundlichen Grüßen', 'themisdb-order-request'); ?><br><?php _e('Ihr ThemisDB Team', 'themisdb-order-request'); ?></p>
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
        // get_templates() returns all templates; filter for invoice.
        $templates = ThemisDB_Document_Template_Manager::get_templates();
        $invoice_template = null;

        foreach ($templates as $template) {
            if (!empty($template['type']) && $template['type'] === 'invoice') {
                $invoice_template = $template;
                break;
            }
        }

        if (empty($invoice_template)) {
            // Fallback if no invoice template exists
            return ThemisDB_PDF_Generator::generate_invoice_pdf($order['id']);
        }

        $variables = array(
            'customer_name' => $order['customer_name'],
            'customer_email' => $order['customer_email'],
            'customer_company' => $order['customer_company'],
            'order_number' => $order['order_number'],
            'order_date' => date_i18n('d.m.Y', strtotime($order['created_at'])),
            'total_amount' => number_format($order['total_amount'], 2, ',', '.'),
            'currency' => $order['currency'],
            'invoice_number' => self::generate_invoice_number($order),
            'invoice_date' => date_i18n('d.m.Y'),
            'due_date' => date_i18n('d.m.Y', strtotime('+14 days')),
        );

        $content = ThemisDB_Document_Template_Manager::replace_variables($invoice_template['content'], $variables);

        return ThemisDB_PDF_Generator::generate_pdf_from_html($content, 'invoice-' . self::generate_invoice_number($order));
    }

    /**
     * Generate unique invoice number.
     */
    private static function generate_invoice_number($order) {
        $year = date('Y');
        $order_id = str_pad($order['id'], 5, '0', STR_PAD_LEFT);
        return 'INV-' . $year . '-' . $order_id;
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
                        <h1><?php _e('Rechnung', 'themisdb-order-request'); ?></h1>
                    </div>
                
                    <div class="content">
                        <p><?php echo esc_html(sprintf(__('Sehr geehrte(r) %s,', 'themisdb-order-request'), $order['customer_name'])); ?></p>
                    
                        <p><?php _e('anbei finden Sie die Rechnung für Ihre Bestellung bei ThemisDB.', 'themisdb-order-request'); ?></p>
                    
                        <div class="invoice-details">
                            <h2><?php _e('Rechnungsdetails', 'themisdb-order-request'); ?></h2>
                        
                            <table>
                                <tr>
                                    <th><?php _e('Rechnungsnummer:', 'themisdb-order-request'); ?></th>
                                    <td><?php echo esc_html($invoice_number); ?></td>
                                    <th><?php _e('Bestellnummer:', 'themisdb-order-request'); ?></th>
                                    <td><?php echo esc_html($order['order_number']); ?></td>
                                </tr>
                                <tr>
                                    <th><?php _e('Rechnungsdatum:', 'themisdb-order-request'); ?></th>
                                    <td><?php echo date_i18n('d.m.Y'); ?></td>
                                    <th><?php _e('Bestelldatum:', 'themisdb-order-request'); ?></th>
                                    <td><?php echo date_i18n('d.m.Y', strtotime($order['created_at'])); ?></td>
                                </tr>
                                <tr>
                                    <th><?php _e('Kunde:', 'themisdb-order-request'); ?></th>
                                    <td>
                                        <?php echo esc_html($order['customer_name']); ?>
                                        <?php if (!empty($order['customer_company'])): ?>
                                            <br><?php echo esc_html($order['customer_company']); ?>
                                        <?php endif; ?>
                                    </td>
                                    <th><?php _e('Fälligkeitsdatum:', 'themisdb-order-request'); ?></th>
                                    <td><?php echo date_i18n('d.m.Y', strtotime('+14 days')); ?></td>
                                </tr>
                            </table>
                        </div>
                    
                        <div class="invoice-details">
                            <h2><?php _e('Leistungsübersicht', 'themisdb-order-request'); ?></h2>
                            <table>
                                <thead>
                                    <tr>
                                        <th><?php _e('Position', 'themisdb-order-request'); ?></th>
                                        <th class="amount"><?php _e('Menge', 'themisdb-order-request'); ?></th>
                                        <th class="amount"><?php _e('Einzelpreis', 'themisdb-order-request'); ?></th>
                                        <th class="amount"><?php _e('Gesamtpreis', 'themisdb-order-request'); ?></th>
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
                                        <td colspan="3" class="amount"><?php _e('Gesamtsumme:', 'themisdb-order-request'); ?></td>
                                        <td class="amount"><?php echo number_format($order['total_amount'], 2, ',', '.'); ?> <?php echo esc_html($order['currency']); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    
                        <div class="payment-terms">
                            <h3><?php _e('💳 Zahlungsinformationen', 'themisdb-order-request'); ?></h3>
                            <p><strong><?php _e('Zahlungsart:', 'themisdb-order-request'); ?></strong> Banküberweisung oder Kreditkarte</p>
                            <p><strong><?php _e('Verwendungszweck:', 'themisdb-order-request'); ?></strong> <code><?php echo esc_html($order['order_number']); ?></code></p>
                            <p><?php _e('Die Zahlung ist innerhalb von 14 Tagen nach Rechnungsdatum fällig.', 'themisdb-order-request'); ?></p>
                        </div>
                    
                        <p><?php _e('Für Fragen zu dieser Rechnung stehen wir Ihnen gerne zur Verfügung.', 'themisdb-order-request'); ?></p>
                    
                        <p><?php _e('Mit freundlichen Grüßen', 'themisdb-order-request'); ?><br>
                        <?php _e('Ihr ThemisDB Team', 'themisdb-order-request'); ?></p>
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
        
        $query = "SELECT * FROM $table_email_log WHERE $where ORDER BY {$args['orderby']} {$args['order']} LIMIT %d OFFSET %d";
        $where_values[] = $args['limit'];
        $where_values[] = $args['offset'];
        
        return $wpdb->get_results($wpdb->prepare($query, $where_values), ARRAY_A);
    }
}
