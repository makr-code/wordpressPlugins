<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB - Hybrid Database System                                   ║
║ License Pricing Manager                                             ║
╠═════════════════════════════════════════════════════════════════════╣
  File:            class-license-pricing.php                          ║
  Version:         1.0.0                                              ║
  Last Modified:   2026-03-16 10:00:00                                ║
  Author:          ThemisDB Team                                      ║
╠═════════════════════════════════════════════════════════════════════╣
  Purpose:                                                            ║
    Verwaltung von Lizenzpreisen mit vollständiger Versionierung,     ║
    Upgrade-Pfaden, Feature-Verwaltung und Änderungshistorie für    ║
    vertragsgenau dokumentierte Preisänderungen.                     ║
╚═════════════════════════════════════════════════════════════════════╝
 */

if (!defined('ABSPATH')) {
    exit;
}

class ThemisDB_License_Pricing {
    
    /**
     * Erstelle Lizenz-Preiseintrag (neue Version)
     */
    public static function create_price_entry($license_id, $contract_id, $data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'themisdb_license_prices';
        
        $price_data = array(
            'license_id' => intval($license_id),
            'contract_id' => intval($contract_id),
            'license_type' => sanitize_text_field($data['license_type']),
            'product_edition' => sanitize_text_field($data['product_edition']),
            'base_price' => floatval($data['base_price']),
            'currency' => sanitize_text_field($data['currency'] ?? 'EUR'),
            'max_nodes' => !empty($data['max_nodes']) ? intval($data['max_nodes']) : 1,
            'max_cores' => !empty($data['max_cores']) ? intval($data['max_cores']) : NULL,
            'max_storage_gb' => !empty($data['max_storage_gb']) ? intval($data['max_storage_gb']) : NULL,
            'valid_from' => $data['valid_from'] ?? date('Y-m-d'),
            'valid_until' => $data['valid_until'] ?? NULL,
            'notes' => sanitize_textarea_field($data['notes'] ?? ''),
            'created_by' => get_current_user_id()
        );
        
        $result = $wpdb->insert($table, $price_data);
        
        if ($result) {
            // Log change
            self::add_history_entry($license_id, $contract_id, 'price_created', 
                NULL, json_encode($price_data), 'base_price', 
                'Neue Preisversion erstellt');
            
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Get aktuellen Preis für eine Lizenz
     */
    public static function get_current_price($license_id, $as_of_date = NULL) {
        global $wpdb;
        
        $as_of_date = $as_of_date ?: date('Y-m-d');
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}themisdb_license_prices 
             WHERE license_id = %d 
             AND valid_from <= %s 
             AND (valid_until IS NULL OR valid_until >= %s)
             ORDER BY valid_from DESC
             LIMIT 1",
            $license_id,
            $as_of_date,
            $as_of_date
        );
        
        return $wpdb->get_row($query, ARRAY_A);
    }
    
    /**
     * Get alle Propreisversionen für Lizenz
     */
    public static function get_price_history($license_id) {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}themisdb_license_prices 
             WHERE license_id = %d
             ORDER BY valid_from DESC",
            $license_id
        );
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Update price und erstelle neue Version
     */
    public static function update_price($license_id, $contract_id, $new_data, $reason = '') {
        global $wpdb;
        
        // Finde aktuelle Preisversion
        $current = self::get_current_price($license_id);
        
        if (!$current) {
            return self::create_price_entry($license_id, $contract_id, $new_data);
        }
        
        // Alte Version als ungültig ab heute markieren
        $wpdb->update(
            $wpdb->prefix . 'themisdb_license_prices',
            array('valid_until' => date('Y-m-d', strtotime('-1 day'))),
            array('id' => $current['id']),
            array('%s'),
            array('%d')
        );
        
        // Neue Version erstellen
        $new_data['valid_from'] = date('Y-m-d');
        $new_id = self::create_price_entry($license_id, $contract_id, $new_data);
        
        if ($new_id) {
            // Log detaillierte Änderungen
            $old_price = $current['base_price'] . ' ' . $current['currency'];
            $new_price = $new_data['base_price'] . ' ' . $new_data['currency'];
            
            self::add_history_entry(
                $license_id,
                $contract_id,
                'price_updated',
                $old_price,
                $new_price,
                'base_price',
                $reason ?: 'Preis aktualisiert'
            );
        }
        
        return $new_id;
    }
    
    /**
     * Erstelle Upgrade-Antrag
     */
    public static function create_upgrade($license_id, $contract_id, $upgrade_data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'themisdb_license_upgrades';
        
        $current_price = self::get_current_price($license_id);
        
        $upgrade = array(
            'license_id' => intval($license_id),
            'contract_id' => intval($contract_id),
            'upgrade_from' => sanitize_text_field($upgrade_data['upgrade_from']),
            'upgrade_to' => sanitize_text_field($upgrade_data['upgrade_to']),
            'upgrade_type' => sanitize_text_field($upgrade_data['upgrade_type']), // edition, nodes, cores, storage
            'upgrade_cost' => floatval($upgrade_data['upgrade_cost']),
            'currency' => sanitize_text_field($upgrade_data['currency'] ?? 'EUR'),
            'upgrade_date' => current_time('mysql'),
            'effective_date' => $upgrade_data['effective_date'] ?? date('Y-m-d'),
            'status' => 'pending',
            'notes' => sanitize_textarea_field($upgrade_data['notes'] ?? ''),
            'created_by' => get_current_user_id()
        );
        
        $result = $wpdb->insert($table, $upgrade);
        
        if ($result) {
            self::add_history_entry(
                $license_id,
                $contract_id,
                'upgrade_created',
                $upgrade_data['upgrade_from'],
                $upgrade_data['upgrade_to'],
                'upgrade_type',
                sprintf('Upgrade %s: %s → %s (%s %s)',
                    $upgrade_data['upgrade_type'],
                    $upgrade_data['upgrade_from'],
                    $upgrade_data['upgrade_to'],
                    $upgrade_data['upgrade_cost'],
                    $upgrade_data['currency']
                )
            );
            
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Genehmige Upgrade
     */
    public static function approve_upgrade($upgrade_id, $notes = '') {
        global $wpdb;
        
        $table = $wpdb->prefix . 'themisdb_license_upgrades';
        
        $upgrade = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $upgrade_id),
            ARRAY_A
        );
        
        if (!$upgrade) {
            return false;
        }
        
        $result = $wpdb->update(
            $table,
            array(
                'status' => 'approved',
                'approved_by' => get_current_user_id(),
                'approved_at' => current_time('mysql')
            ),
            array('id' => $upgrade_id),
            array('%s', '%d', '%s'),
            array('%d')
        );
        
        if ($result) {
            self::add_history_entry(
                $upgrade['license_id'],
                $upgrade['contract_id'],
                'upgrade_approved',
                'pending',
                'approved',
                'upgrade_status',
                $notes ?: 'Upgrade genehmigt'
            );
        }
        
        return $result !== false;
    }
    
    /**
     * Lehne Upgrade ab
     */
    public static function reject_upgrade($upgrade_id, $reason = '') {
        global $wpdb;
        
        $table = $wpdb->prefix . 'themisdb_license_upgrades';
        
        $upgrade = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $upgrade_id),
            ARRAY_A
        );
        
        if (!$upgrade) {
            return false;
        }
        
        $result = $wpdb->update(
            $table,
            array(
                'status' => 'rejected',
                'notes' => $reason
            ),
            array('id' => $upgrade_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result) {
            self::add_history_entry(
                $upgrade['license_id'],
                $upgrade['contract_id'],
                'upgrade_rejected',
                'pending',
                'rejected',
                'upgrade_status',
                $reason ?: 'Upgrade abgelehnt'
            );
        }
        
        return $result !== false;
    }
    
    /**
     * Setze Lizenz-Feature
     */
    public static function set_feature($license_id, $contract_id, $feature_data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'themisdb_license_features';
        
        // Deaktiviere alte Versionen des Features
        $wpdb->update(
            $table,
            array('is_active' => 0),
            array(
                'license_id' => $license_id,
                'feature_code' => $feature_data['feature_code'],
                'is_active' => 1
            ),
            array('%d'),
            array('%d', '%s', '%d')
        );
        
        // Erstelle neue Feature-Version
        $feature = array(
            'license_id' => intval($license_id),
            'contract_id' => intval($contract_id),
            'feature_code' => sanitize_text_field($feature_data['feature_code']),
            'feature_name' => sanitize_text_field($feature_data['feature_name']),
            'feature_value' => sanitize_textarea_field($feature_data['feature_value'] ?? ''),
            'is_active' => 1,
            'valid_from' => $feature_data['valid_from'] ?? date('Y-m-d'),
            'valid_until' => $feature_data['valid_until'] ?? NULL,
            'notes' => sanitize_textarea_field($feature_data['notes'] ?? '')
        );
        
        $result = $wpdb->insert($table, $feature);
        
        if ($result) {
            self::add_history_entry(
                $license_id,
                $contract_id,
                'feature_updated',
                NULL,
                $feature_data['feature_value'] ?? '—',
                'feature:' . $feature_data['feature_code'],
                sprintf('Feature %s: %s', $feature_data['feature_code'], $feature_data['feature_name'])
            );
            
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Get aktive Features für Lizenz
     */
    public static function get_active_features($license_id, $as_of_date = NULL) {
        global $wpdb;
        
        $as_of_date = $as_of_date ?: date('Y-m-d');
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}themisdb_license_features 
             WHERE license_id = %d 
             AND is_active = 1
             AND valid_from <= %s 
             AND (valid_until IS NULL OR valid_until >= %s)
             ORDER BY feature_code ASC",
            $license_id,
            $as_of_date,
            $as_of_date
        );
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Get Feature-Historie
     */
    public static function get_feature_history($license_id, $feature_code = NULL) {
        global $wpdb;
        
        if ($feature_code) {
            $query = $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}themisdb_license_features 
                 WHERE license_id = %d AND feature_code = %s
                 ORDER BY valid_from DESC",
                $license_id,
                $feature_code
            );
        } else {
            $query = $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}themisdb_license_features 
                 WHERE license_id = %d
                 ORDER BY feature_code ASC, valid_from DESC",
                $license_id
            );
        }
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Füge Änderung zu History hinzu
     */
    public static function add_history_entry($license_id, $contract_id, $change_type, $old_value, $new_value, $changed_field, $change_reason = '') {
        global $wpdb;
        
        $table = $wpdb->prefix . 'themisdb_license_history';
        
        $entry = array(
            'license_id' => intval($license_id),
            'contract_id' => intval($contract_id),
            'change_type' => sanitize_text_field($change_type),
            'old_value' => is_array($old_value) ? json_encode($old_value) : $old_value,
            'new_value' => is_array($new_value) ? json_encode($new_value) : $new_value,
            'changed_field' => sanitize_text_field($changed_field),
            'change_reason' => sanitize_textarea_field($change_reason),
            'changed_by' => get_current_user_id(),
            'created_at' => current_time('mysql')
        );
        
        return $wpdb->insert($table, $entry);
    }
    
    /**
     * Get komplette Änderungshistorie
     */
    public static function get_history($license_id, $contract_id = NULL) {
        global $wpdb;
        
        if ($contract_id) {
            $query = $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}themisdb_license_history 
                 WHERE license_id = %d AND contract_id = %d
                 ORDER BY created_at DESC
                 LIMIT 100",
                $license_id,
                $contract_id
            );
        } else {
            $query = $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}themisdb_license_history 
                 WHERE license_id = %d
                 ORDER BY created_at DESC
                 LIMIT 100",
                $license_id
            );
        }
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Calculate Upgrade-Kosten (Preis-Differenz)
     */
    public static function calculate_upgrade_cost($license_id, $from_edition, $to_edition) {
        global $wpdb;
        
        $from_product = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT price FROM {$wpdb->prefix}themisdb_products 
                 WHERE edition = %s LIMIT 1",
                $from_edition
            ),
            ARRAY_A
        );
        
        $to_product = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT price FROM {$wpdb->prefix}themisdb_products 
                 WHERE edition = %s LIMIT 1",
                $to_edition
            ),
            ARRAY_A
        );
        
        if (!$from_product || !$to_product) {
            return false;
        }
        
        $difference = $to_product['price'] - $from_product['price'];
        
        return array(
            'from_price' => floatval($from_product['price']),
            'to_price' => floatval($to_product['price']),
            'upgrade_cost' => floatval($difference),
            'discount_percentage' => 0 // kann später für zeitbasierte Rabatte genutzt werden
        );
    }
    
    /**
     * Erstelle PDF-Zusammenfassung aller Lizenzkosten/Preisänderungen
     */
    public static function generate_pricing_summary_pdf($license_id, $contract_id) {
        $license = ThemisDB_License_Manager::get_license($license_id);
        $prices = self::get_price_history($license_id);
        $upgrades = self::get_license_upgrades($license_id);
        $features = self::get_feature_history($license_id);
        $history = self::get_history($license_id, $contract_id);
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Lizenzpreis-Zusammenfassung</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; }
                h1 { font-size: 20pt; border-bottom: 2px solid #333; padding-bottom: 10px; }
                h2 { font-size: 14pt; margin-top: 30px; border-bottom: 1px solid #666; }
                table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
                th { background-color: #f5f5f5; font-weight: bold; }
                .section { margin-bottom: 30px; }
                .price { text-align: right; font-weight: bold; }
            </style>
        </head>
        <body>
            <h1>Lizenzpreis-Zusammenfassung</h1>
            <p><strong>Lizenzschlüssel:</strong> <?php echo esc_html($license['license_key']); ?></p>
            <p><strong>Edition:</strong> <?php echo esc_html($license['product_edition']); ?></p>
            
            <?php if (!empty($prices)): ?>
            <div class="section">
                <h2>Preisversionen</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Gültig ab</th>
                            <th>Gültig bis</th>
                            <th>Lizenztyp</th>
                            <th class="price">Preis</th>
                            <th>Knoten</th>
                            <th>Speicher</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($prices as $price): ?>
                        <tr>
                            <td><?php echo esc_html($price['valid_from']); ?></td>
                            <td><?php echo esc_html($price['valid_until'] ?: '—'); ?></td>
                            <td><?php echo esc_html($price['license_type']); ?></td>
                            <td class="price"><?php echo number_format($price['base_price'], 2, ',', '.'); ?> <?php echo esc_html($price['currency']); ?></td>
                            <td><?php echo $price['max_nodes'] ?: '∞'; ?></td>
                            <td><?php echo $price['max_storage_gb'] ? $price['max_storage_gb'] . ' GB' : '∞'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($upgrades)): ?>
            <div class="section">
                <h2>Upgrades & Umstufungen</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Datum</th>
                            <th>Typ</th>
                            <th>Von</th>
                            <th>Nach</th>
                            <th class="price">Kosten</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upgrades as $upgrade): ?>
                        <tr style="background-color: <?php echo ($upgrade['status'] === 'approved') ? '#d4edda' : '#fff3cd'; ?>;">
                            <td><?php echo esc_html(date('d.m.Y', strtotime($upgrade['upgrade_date']))); ?></td>
                            <td><?php echo esc_html($upgrade['upgrade_type']); ?></td>
                            <td><?php echo esc_html($upgrade['upgrade_from']); ?></td>
                            <td><?php echo esc_html($upgrade['upgrade_to']); ?></td>
                            <td class="price"><?php echo number_format($upgrade['upgrade_cost'], 2, ',', '.'); ?> <?php echo esc_html($upgrade['currency']); ?></td>
                            <td><?php echo esc_html(ucfirst($upgrade['status'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($features)): ?>
            <div class="section">
                <h2>Features & Erweiterungen</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Feature</th>
                            <th>Gültig ab</th>
                            <th>Gültig bis</th>
                            <th>Wert / Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($features as $feature): ?>
                        <tr>
                            <td><strong><?php echo esc_html($feature['feature_code']); ?></strong><br><small><?php echo esc_html($feature['feature_name']); ?></small></td>
                            <td><?php echo esc_html($feature['valid_from']); ?></td>
                            <td><?php echo esc_html($feature['valid_until'] ?: '—'); ?></td>
                            <td><?php echo esc_html($feature['feature_value']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($history)): ?>
            <div class="section">
                <h2>Änderungshistorie (letzte 20)</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Datum/Zeit</th>
                            <th>Änderungstyp</th>
                            <th>Feld</th>
                            <th>Veränderung</th>
                            <th>Grund</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($history, 0, 20) as $entry): ?>
                        <tr>
                            <td><?php echo esc_html(date('d.m.Y H:i', strtotime($entry['created_at']))); ?></td>
                            <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $entry['change_type']))); ?></td>
                            <td><?php echo esc_html($entry['changed_field']); ?></td>
                            <td><small><?php echo esc_html($entry['old_value'] ?? '—'); ?> → <?php echo esc_html($entry['new_value'] ?? '—'); ?></small></td>
                            <td><?php echo esc_html($entry['change_reason'] ?: '—'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </body>
        </html>
        <?php
        
        $html = ob_get_clean();
        
        // Hier würde die HTML zu PDF Konvertierung stattfinden
        // return ThemisDB_PDF_Generator::html_to_pdf($html, 'pricing-summary-' . $license_id);
        
        return $html;
    }
    
    /**
     * Helper: Get Upgrades für Lizenz
     */
    private static function get_license_upgrades($license_id) {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}themisdb_license_upgrades 
             WHERE license_id = %d
             ORDER BY upgrade_date DESC",
            $license_id
        );
        
        return $wpdb->get_results($query, ARRAY_A);
    }
}
