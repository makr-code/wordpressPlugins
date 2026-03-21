<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB - Hybrid Database System                                   ║
╠═════════════════════════════════════════════════════════════════════╣
  File:            class-shortcodes.php                               ║
  Version:         0.0.2                                              ║
  Last Modified:   2026-03-09 04:08:20                                ║
  Author:          unknown                                            ║
╠═════════════════════════════════════════════════════════════════════╣
  Quality Metrics:                                                    ║
    • Maturity Level:  🟢 PRODUCTION-READY                             ║
    • Quality Score:   100.0/100                                      ║
    • Total Lines:     668                                            ║
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
 * Shortcodes for ThemisDB Order Request Plugin
 * Provides dialog-based order flow
 */

if (!defined('ABSPATH')) {
    exit;
}

class ThemisDB_Order_Shortcodes {
    
    public function __construct() {
        add_shortcode('themisdb_order_flow', array($this, 'order_flow_shortcode'));
        add_shortcode('themisdb_my_orders', array($this, 'my_orders_shortcode'));
        add_shortcode('themisdb_my_contracts', array($this, 'my_contracts_shortcode'));
        add_shortcode('themisdb_pricing', array($this, 'pricing_shortcode'));
        add_shortcode('themisdb_pricing_table', array($this, 'pricing_table_shortcode'));
        
        // AJAX handlers
        add_action('wp_ajax_themisdb_save_order_step', array($this, 'ajax_save_order_step'));
        add_action('wp_ajax_nopriv_themisdb_save_order_step', array($this, 'ajax_save_order_step'));
        add_action('wp_ajax_themisdb_calculate_total', array($this, 'ajax_calculate_total'));
        add_action('wp_ajax_nopriv_themisdb_calculate_total', array($this, 'ajax_calculate_total'));
        add_action('wp_ajax_themisdb_submit_order', array($this, 'ajax_submit_order'));
        add_action('wp_ajax_nopriv_themisdb_submit_order', array($this, 'ajax_submit_order'));
    }
    
    /**
     * Order flow shortcode - Dialog-based order process
     */
    public function order_flow_shortcode($atts) {
        $atts = shortcode_atts(array(
            'step' => 1
        ), $atts);
        
        ob_start();
        $this->render_order_flow(intval($atts['step']));
        return ob_get_clean();
    }
    
    /**
     * Render order flow
     */
    private function render_order_flow($current_step = 1) {
        // Get or create order session
        $order_id = isset($_SESSION['themisdb_order_id']) ? $_SESSION['themisdb_order_id'] : null;
        $order = null;
        
        if ($order_id) {
            $order = ThemisDB_Order_Manager::get_order($order_id);
            if ($order) {
                $current_step = $order['step'];
            }
        }
        
        ?>
        <div class="themisdb-order-flow">
            <!-- Progress Steps -->
            <div class="order-steps">
                <div class="step <?php echo $current_step >= 1 ? 'active' : ''; ?> <?php echo $current_step > 1 ? 'completed' : ''; ?>">
                    <span class="step-number">1</span>
                    <span class="step-title"><?php _e('Produkt wählen', 'themisdb-order-request'); ?></span>
                </div>
                <div class="step <?php echo $current_step >= 2 ? 'active' : ''; ?> <?php echo $current_step > 2 ? 'completed' : ''; ?>">
                    <span class="step-number">2</span>
                    <span class="step-title"><?php _e('Module auswählen', 'themisdb-order-request'); ?></span>
                </div>
                <div class="step <?php echo $current_step >= 3 ? 'active' : ''; ?> <?php echo $current_step > 3 ? 'completed' : ''; ?>">
                    <span class="step-number">3</span>
                    <span class="step-title"><?php _e('Schulungen', 'themisdb-order-request'); ?></span>
                </div>
                <div class="step <?php echo $current_step >= 4 ? 'active' : ''; ?> <?php echo $current_step > 4 ? 'completed' : ''; ?>">
                    <span class="step-number">4</span>
                    <span class="step-title"><?php _e('Kundendaten', 'themisdb-order-request'); ?></span>
                </div>
                <div class="step <?php echo $current_step >= 5 ? 'active' : ''; ?>">
                    <span class="step-number">5</span>
                    <span class="step-title"><?php _e('Zusammenfassung', 'themisdb-order-request'); ?></span>
                </div>
            </div>
            
            <!-- Step Content -->
            <div class="order-content">
                <?php
                switch ($current_step) {
                    case 1:
                        $this->render_step_product($order);
                        break;
                    case 2:
                        $this->render_step_modules($order);
                        break;
                    case 3:
                        $this->render_step_training($order);
                        break;
                    case 4:
                        $this->render_step_customer($order);
                        break;
                    case 5:
                        $this->render_step_summary($order);
                        break;
                    default:
                        $this->render_step_product($order);
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render Step 1: Product Selection
     */
    private function render_step_product($order) {
        $products = ThemisDB_Order_Manager::get_products();
        $selected_edition = $order ? $order['product_edition'] : '';
        
        ?>
        <div class="order-step-content" data-step="1">
            <h2><?php _e('Wählen Sie Ihre ThemisDB Edition', 'themisdb-order-request'); ?></h2>
            <p><?php _e('Wählen Sie die Edition, die am besten zu Ihren Anforderungen passt.', 'themisdb-order-request'); ?></p>
            
            <div class="product-selection">
                <?php foreach ($products as $product): ?>
                <div class="product-card <?php echo $selected_edition === $product['edition'] ? 'selected' : ''; ?>">
                    <label>
                        <input type="radio" name="product_edition" value="<?php echo esc_attr($product['edition']); ?>" 
                               <?php checked($selected_edition, $product['edition']); ?>>
                        <div class="product-details">
                            <h3><?php echo esc_html($product['product_name']); ?></h3>
                            <p class="product-description"><?php echo esc_html($product['description']); ?></p>
                            <p class="product-price">
                                <?php if ($product['price'] == 0): ?>
                                    <strong><?php _e('Kostenlos', 'themisdb-order-request'); ?></strong>
                                <?php else: ?>
                                    <strong><?php echo number_format($product['price'], 2, ',', '.'); ?> €</strong> / <?php _e('Monat', 'themisdb-order-request'); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="order-navigation">
                <button type="button" class="button button-primary button-next" data-step="1">
                    <?php _e('Weiter', 'themisdb-order-request'); ?> →
                </button>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render Step 2: Module Selection
     */
    private function render_step_modules($order) {
        $modules = ThemisDB_Order_Manager::get_modules();
        $selected_modules = $order && !empty($order['modules']) ? $order['modules'] : array();
        
        // Group modules by category
        $grouped_modules = array();
        foreach ($modules as $module) {
            $grouped_modules[$module['module_category']][] = $module;
        }
        
        ?>
        <div class="order-step-content" data-step="2">
            <h2><?php _e('Wählen Sie Ihre Module', 'themisdb-order-request'); ?></h2>
            <p><?php _e('Erweitern Sie Ihre ThemisDB-Installation mit zusätzlichen Modulen.', 'themisdb-order-request'); ?></p>
            
            <?php foreach ($grouped_modules as $category => $category_modules): ?>
            <div class="module-category">
                <h3><?php echo esc_html(ucfirst($category)); ?></h3>
                <div class="module-selection">
                    <?php foreach ($category_modules as $module): ?>
                    <div class="module-card">
                        <label>
                            <input type="checkbox" name="modules[]" value="<?php echo esc_attr($module['module_code']); ?>"
                                   <?php checked(in_array($module['module_code'], $selected_modules)); ?>>
                            <div class="module-details">
                                <h4><?php echo esc_html($module['module_name']); ?></h4>
                                <p><?php echo esc_html($module['description']); ?></p>
                                <p class="module-price">
                                    <?php if ($module['price'] == 0): ?>
                                        <em><?php _e('Inklusive', 'themisdb-order-request'); ?></em>
                                    <?php else: ?>
                                        <strong>+<?php echo number_format($module['price'], 2, ',', '.'); ?> €</strong>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            
            <div class="order-navigation">
                <button type="button" class="button button-secondary button-prev" data-step="2">
                    ← <?php _e('Zurück', 'themisdb-order-request'); ?>
                </button>
                <button type="button" class="button button-primary button-next" data-step="2">
                    <?php _e('Weiter', 'themisdb-order-request'); ?> →
                </button>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render Step 3: Training Selection
     */
    private function render_step_training($order) {
        $trainings = ThemisDB_Order_Manager::get_training_modules();
        $selected_trainings = $order && !empty($order['training_modules']) ? $order['training_modules'] : array();
        
        // Group trainings by type
        $grouped_trainings = array();
        foreach ($trainings as $training) {
            $grouped_trainings[$training['training_type']][] = $training;
        }
        
        ?>
        <div class="order-step-content" data-step="3">
            <h2><?php _e('Wählen Sie Ihre Schulungen', 'themisdb-order-request'); ?></h2>
            <p><?php _e('Profitieren Sie von professionellen Schulungen für Ihr Team.', 'themisdb-order-request'); ?></p>
            
            <?php foreach ($grouped_trainings as $type => $type_trainings): ?>
            <div class="training-category">
                <h3><?php echo esc_html(ucfirst($type)); ?></h3>
                <div class="training-selection">
                    <?php foreach ($type_trainings as $training): ?>
                    <div class="training-card">
                        <label>
                            <input type="checkbox" name="training_modules[]" value="<?php echo esc_attr($training['training_code']); ?>"
                                   <?php checked(in_array($training['training_code'], $selected_trainings)); ?>>
                            <div class="training-details">
                                <h4><?php echo esc_html($training['training_name']); ?></h4>
                                <p><?php echo esc_html($training['description']); ?></p>
                                <p class="training-meta">
                                    <span class="training-duration">
                                        <strong><?php echo absint($training['duration_hours']); ?></strong> <?php _e('Stunden', 'themisdb-order-request'); ?>
                                    </span>
                                    <span class="training-price">
                                        <strong>+<?php echo number_format($training['price'], 2, ',', '.'); ?> €</strong>
                                    </span>
                                </p>
                            </div>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            
            <div class="order-navigation">
                <button type="button" class="button button-secondary button-prev" data-step="3">
                    ← <?php _e('Zurück', 'themisdb-order-request'); ?>
                </button>
                <button type="button" class="button button-primary button-next" data-step="3">
                    <?php _e('Weiter', 'themisdb-order-request'); ?> →
                </button>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render Step 4: Customer Data
     */
    private function render_step_customer($order) {
        $customer_name = $order ? $order['customer_name'] : '';
        $customer_email = $order ? $order['customer_email'] : '';
        $customer_company = $order ? $order['customer_company'] : '';
        $customer_type = $order ? ($order['customer_type'] ?? 'consumer') : 'consumer';
        $vat_id = $order ? ($order['vat_id'] ?? '') : '';
        $billing_name = $order ? ($order['billing_name'] ?? '') : '';
        $billing_address_line1 = $order ? ($order['billing_address_line1'] ?? '') : '';
        $billing_address_line2 = $order ? ($order['billing_address_line2'] ?? '') : '';
        $billing_postal_code = $order ? ($order['billing_postal_code'] ?? '') : '';
        $billing_city = $order ? ($order['billing_city'] ?? '') : '';
        $billing_country = $order ? ($order['billing_country'] ?? 'DE') : 'DE';
        $legal_terms_accepted = !empty($order['legal_terms_accepted']);
        $legal_privacy_accepted = !empty($order['legal_privacy_accepted']);
        $legal_withdrawal_acknowledged = !empty($order['legal_withdrawal_acknowledged']);
        $legal_withdrawal_waiver = !empty($order['legal_withdrawal_waiver']);
        $agb_url = get_option('themisdb_order_legal_agb_url', home_url('/agb'));
        $privacy_url = get_option('themisdb_order_legal_privacy_url', home_url('/datenschutz'));
        $withdrawal_url = get_option('themisdb_order_legal_withdrawal_url', home_url('/widerruf'));
        $shipping_name = $order ? ($order['shipping_name'] ?? '') : '';
        $shipping_address_line1 = $order ? ($order['shipping_address_line1'] ?? '') : '';
        $shipping_address_line2 = $order ? ($order['shipping_address_line2'] ?? '') : '';
        $shipping_postal_code = $order ? ($order['shipping_postal_code'] ?? '') : '';
        $shipping_city = $order ? ($order['shipping_city'] ?? '') : '';
        $shipping_country = $order ? ($order['shipping_country'] ?? 'DE') : 'DE';
        $shipping_method = $order ? ($order['shipping_method'] ?? 'standard') : 'standard';
        
        ?>
        <div class="order-step-content" data-step="4">
            <h2><?php _e('Ihre Kontaktdaten', 'themisdb-order-request'); ?></h2>
            <p><?php _e('Bitte geben Sie Ihre Kontakt- und Rechnungsdaten ein.', 'themisdb-order-request'); ?></p>

            <div class="customer-form">

                <!-- Kontaktdaten -->
                <div class="compliance-section">
                    <h3><?php _e('Kontakt', 'themisdb-order-request'); ?></h3>

                    <div class="form-group">
                        <label for="customer_name"><?php _e('Name', 'themisdb-order-request'); ?> *</label>
                        <input type="text" id="customer_name" name="customer_name"
                               value="<?php echo esc_attr($customer_name); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="customer_email"><?php _e('E-Mail-Adresse', 'themisdb-order-request'); ?> *</label>
                        <input type="email" id="customer_email" name="customer_email"
                               value="<?php echo esc_attr($customer_email); ?>" required>
                    </div>
                </div>

                <!-- Kundentyp -->
                <div class="compliance-section">
                    <h3><?php _e('Kundentyp', 'themisdb-order-request'); ?></h3>
                    <div class="customer-type-group">
                        <label class="customer-type-option <?php echo $customer_type === 'consumer' ? 'selected' : ''; ?>">
                            <input type="radio" name="customer_type" value="consumer" <?php checked($customer_type, 'consumer'); ?>>
                            <strong><?php _e('Verbraucher', 'themisdb-order-request'); ?></strong>
                            <small><?php _e('Privatperson (B2C)', 'themisdb-order-request'); ?></small>
                        </label>
                        <label class="customer-type-option <?php echo $customer_type === 'business' ? 'selected' : ''; ?>">
                            <input type="radio" name="customer_type" value="business" <?php checked($customer_type, 'business'); ?>>
                            <strong><?php _e('Unternehmer / Firma', 'themisdb-order-request'); ?></strong>
                            <small><?php _e('Gewerblicher Käufer (B2B)', 'themisdb-order-request'); ?></small>
                        </label>
                    </div>

                    <!-- B2B-Felder (nur sichtbar wenn Unternehmer gewählt) -->
                    <div class="themisdb-b2b-fields" <?php echo $customer_type !== 'business' ? 'style="display:none"' : ''; ?>>
                        <div class="form-group">
                            <label for="customer_company"><?php _e('Firmenname', 'themisdb-order-request'); ?> *</label>
                            <input type="text" id="customer_company" name="customer_company"
                                   value="<?php echo esc_attr($customer_company); ?>">
                        </div>

                        <div class="form-group">
                            <label for="vat_id"><?php _e('USt-IdNr.', 'themisdb-order-request'); ?></label>
                            <input type="text" id="vat_id" name="vat_id"
                                   value="<?php echo esc_attr($vat_id); ?>"
                                   placeholder="DE123456789">
                            <span class="themisdb-field-hint"><?php _e('Format: zwei Buchstaben Ländercode + Ziffern/Buchstaben, z. B. DE123456789', 'themisdb-order-request'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Rechnungsadresse -->
                <div class="compliance-section">
                    <h3><?php _e('Rechnungsadresse', 'themisdb-order-request'); ?></h3>

                    <div class="form-group">
                        <label for="billing_name"><?php _e('Rechnungsempfänger (Name / Firma)', 'themisdb-order-request'); ?> *</label>
                        <input type="text" id="billing_name" name="billing_name"
                               value="<?php echo esc_attr($billing_name); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="billing_address_line1"><?php _e('Straße und Hausnummer', 'themisdb-order-request'); ?> *</label>
                        <input type="text" id="billing_address_line1" name="billing_address_line1"
                               value="<?php echo esc_attr($billing_address_line1); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="billing_address_line2"><?php _e('Adresszusatz (optional)', 'themisdb-order-request'); ?></label>
                        <input type="text" id="billing_address_line2" name="billing_address_line2"
                               value="<?php echo esc_attr($billing_address_line2); ?>">
                    </div>

                    <div class="address-grid">
                        <div class="form-group">
                            <label for="billing_postal_code"><?php _e('PLZ', 'themisdb-order-request'); ?> *</label>
                            <input type="text" id="billing_postal_code" name="billing_postal_code"
                                   value="<?php echo esc_attr($billing_postal_code); ?>"
                                   placeholder="z. B. 80333" maxlength="10" required>
                        </div>

                        <div class="form-group">
                            <label for="billing_city"><?php _e('Ort', 'themisdb-order-request'); ?> *</label>
                            <input type="text" id="billing_city" name="billing_city"
                                   value="<?php echo esc_attr($billing_city); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="billing_country"><?php _e('Land', 'themisdb-order-request'); ?> *</label>
                            <input type="text" id="billing_country" name="billing_country"
                                   value="<?php echo esc_attr($billing_country); ?>"
                                   placeholder="DE" maxlength="2" required>
                            <span class="themisdb-field-hint"><?php _e('ISO-Ländercode, 2 Großbuchstaben (z. B. DE, AT, CH)', 'themisdb-order-request'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Lieferadresse (optional) -->
                <div class="compliance-section optional-section">
                    <h3><?php _e('Lieferadresse', 'themisdb-order-request'); ?> <small style="font-weight:normal;color:#666;">(<?php _e('optional, z. B. für Merchandise', 'themisdb-order-request'); ?>)</small></h3>

                    <div class="form-group">
                        <label for="shipping_name"><?php _e('Empfänger', 'themisdb-order-request'); ?></label>
                        <input type="text" id="shipping_name" name="shipping_name"
                               value="<?php echo esc_attr($shipping_name); ?>">
                    </div>

                    <div class="form-group">
                        <label for="shipping_address_line1"><?php _e('Straße und Hausnummer', 'themisdb-order-request'); ?></label>
                        <input type="text" id="shipping_address_line1" name="shipping_address_line1"
                               value="<?php echo esc_attr($shipping_address_line1); ?>">
                    </div>

                    <div class="form-group">
                        <label for="shipping_address_line2"><?php _e('Adresszusatz', 'themisdb-order-request'); ?></label>
                        <input type="text" id="shipping_address_line2" name="shipping_address_line2"
                               value="<?php echo esc_attr($shipping_address_line2); ?>">
                    </div>

                    <div class="address-grid">
                        <div class="form-group">
                            <label for="shipping_postal_code"><?php _e('PLZ', 'themisdb-order-request'); ?></label>
                            <input type="text" id="shipping_postal_code" name="shipping_postal_code"
                                   value="<?php echo esc_attr($shipping_postal_code); ?>"
                                   placeholder="z. B. 80333" maxlength="10">
                        </div>

                        <div class="form-group">
                            <label for="shipping_city"><?php _e('Ort', 'themisdb-order-request'); ?></label>
                            <input type="text" id="shipping_city" name="shipping_city"
                                   value="<?php echo esc_attr($shipping_city); ?>">
                        </div>

                        <div class="form-group">
                            <label for="shipping_country"><?php _e('Land', 'themisdb-order-request'); ?></label>
                            <input type="text" id="shipping_country" name="shipping_country"
                                   value="<?php echo esc_attr($shipping_country); ?>"
                                   placeholder="DE" maxlength="2">
                            <span class="themisdb-field-hint"><?php _e('ISO-Ländercode, 2 Großbuchstaben', 'themisdb-order-request'); ?></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="shipping_method"><?php _e('Versandart', 'themisdb-order-request'); ?></label>
                        <select id="shipping_method" name="shipping_method">
                            <option value="standard" <?php selected($shipping_method, 'standard'); ?>><?php _e('Standard', 'themisdb-order-request'); ?></option>
                            <option value="express" <?php selected($shipping_method, 'express'); ?>><?php _e('Express', 'themisdb-order-request'); ?></option>
                        </select>
                    </div>
                </div>

                <!-- Rechtliche Zustimmungen -->
                <div class="legal-checkboxes">
                    <h4><?php _e('Rechtliche Zustimmungen', 'themisdb-order-request'); ?></h4>

                    <div class="legal-check-item">
                        <input type="checkbox" id="legal_terms_accepted" name="legal_terms_accepted" value="1" <?php checked($legal_terms_accepted); ?> required>
                        <label for="legal_terms_accepted">
                            <?php printf(
                                __('Ich akzeptiere die <a href="%s" target="_blank" rel="noopener noreferrer">Allgemeinen Geschäftsbedingungen (AGB)</a>.', 'themisdb-order-request'),
                                esc_url($agb_url)
                            ); ?> *
                        </label>
                    </div>

                    <div class="legal-check-item">
                        <input type="checkbox" id="legal_privacy_accepted" name="legal_privacy_accepted" value="1" <?php checked($legal_privacy_accepted); ?> required>
                        <label for="legal_privacy_accepted">
                            <?php printf(
                                __('Ich habe die <a href="%s" target="_blank" rel="noopener noreferrer">Datenschutzerklärung</a> gelesen und akzeptiere die Verarbeitung meiner Daten.', 'themisdb-order-request'),
                                esc_url($privacy_url)
                            ); ?> *
                        </label>
                    </div>

                    <div class="legal-check-item legal-withdrawal-consent">
                        <input type="checkbox" id="legal_withdrawal_acknowledged" name="legal_withdrawal_acknowledged" value="1" <?php checked($legal_withdrawal_acknowledged); ?>>
                        <label for="legal_withdrawal_acknowledged">
                            <?php printf(
                                __('Ich habe die <a href="%s" target="_blank" rel="noopener noreferrer">Widerrufsbelehrung</a> zur Kenntnis genommen.', 'themisdb-order-request'),
                                esc_url($withdrawal_url)
                            ); ?> *
                        </label>
                    </div>

                    <div class="legal-check-item legal-withdrawal-waiver">
                        <input type="checkbox" id="legal_withdrawal_waiver" name="legal_withdrawal_waiver" value="1" <?php checked($legal_withdrawal_waiver); ?>>
                        <label for="legal_withdrawal_waiver">
                            <?php _e('Ich stimme ausdrücklich zu, dass mit der Ausführung der digitalen Leistungen vor Ablauf der Widerrufsfrist begonnen wird und erkenne an, dass mein Widerrufsrecht damit erlischt (§ 356 Abs. 5 BGB).', 'themisdb-order-request'); ?>
                        </label>
                    </div>
                </div>

            </div>
            
            <div class="order-navigation">
                <button type="button" class="button button-secondary button-prev" data-step="4">
                    ← <?php _e('Zurück', 'themisdb-order-request'); ?>
                </button>
                <button type="button" class="button button-primary button-next" data-step="4">
                    <?php _e('Weiter', 'themisdb-order-request'); ?> →
                </button>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render Step 5: Summary
     */
    private function render_step_summary($order) {
        if (!$order) {
            echo '<p>' . __('Keine Bestelldaten gefunden.', 'themisdb-order-request') . '</p>';
            return;
        }
        
        $products = ThemisDB_Order_Manager::get_products();
        $modules = ThemisDB_Order_Manager::get_modules();
        $trainings = ThemisDB_Order_Manager::get_training_modules();
        
        ?>
        <div class="order-step-content" data-step="5">
            <h2><?php _e('Zusammenfassung Ihrer Bestellung', 'themisdb-order-request'); ?></h2>
            
            <div class="order-summary">
                <div class="summary-section">
                    <h3><?php _e('Produkt', 'themisdb-order-request'); ?></h3>
                    <?php foreach ($products as $product): ?>
                        <?php if ($product['edition'] === $order['product_edition']): ?>
                            <p><strong><?php echo esc_html($product['product_name']); ?></strong><br>
                            <?php echo esc_html($product['description']); ?></p>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                
                <?php if (!empty($order['modules'])): ?>
                <div class="summary-section">
                    <h3><?php _e('Module', 'themisdb-order-request'); ?></h3>
                    <ul>
                        <?php foreach ($modules as $module): ?>
                            <?php if (in_array($module['module_code'], $order['modules'])): ?>
                                <li><?php echo esc_html($module['module_name']); ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($order['training_modules'])): ?>
                <div class="summary-section">
                    <h3><?php _e('Schulungen', 'themisdb-order-request'); ?></h3>
                    <ul>
                        <?php foreach ($trainings as $training): ?>
                            <?php if (in_array($training['training_code'], $order['training_modules'])): ?>
                                <li><?php echo esc_html($training['training_name']); ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <div class="summary-section">
                    <h3><?php _e('Kontaktdaten', 'themisdb-order-request'); ?></h3>
                    <p>
                        <strong><?php echo esc_html($order['customer_name']); ?></strong><br>
                        <?php if (!empty($order['customer_company'])): ?>
                            <?php echo esc_html($order['customer_company']); ?><br>
                        <?php endif; ?>
                        <?php echo esc_html($order['customer_email']); ?><br>
                        <?php
                        $ct_label = (($order['customer_type'] ?? 'consumer') === 'business')
                            ? __('Unternehmer / Firma (B2B)', 'themisdb-order-request')
                            : __('Verbraucher (B2C)', 'themisdb-order-request');
                        echo '<em>' . esc_html($ct_label) . '</em>';
                        ?>
                        <?php if (!empty($order['vat_id'])): ?><br>
                            <?php echo esc_html__('USt-IdNr.', 'themisdb-order-request') . ': ' . esc_html($order['vat_id']); ?>
                        <?php endif; ?>
                    </p>
                </div>

                <?php if (!empty($order['billing_address_line1'])): ?>
                <div class="summary-section">
                    <h3><?php _e('Rechnungsadresse', 'themisdb-order-request'); ?></h3>
                    <p>
                        <strong><?php echo esc_html($order['billing_name'] ?: $order['customer_name']); ?></strong><br>
                        <?php echo esc_html($order['billing_address_line1']); ?><br>
                        <?php if (!empty($order['billing_address_line2'])): ?>
                            <?php echo esc_html($order['billing_address_line2']); ?><br>
                        <?php endif; ?>
                        <?php echo esc_html(trim(($order['billing_postal_code'] ?? '') . ' ' . ($order['billing_city'] ?? ''))); ?><br>
                        <?php echo esc_html($order['billing_country'] ?? 'DE'); ?>
                    </p>
                </div>
                <?php endif; ?>

                <?php if (!empty($order['shipping_address_line1'])): ?>
                <div class="summary-section">
                    <h3><?php _e('Lieferadresse', 'themisdb-order-request'); ?></h3>
                    <p>
                        <?php if (!empty($order['shipping_name'])): ?>
                            <strong><?php echo esc_html($order['shipping_name']); ?></strong><br>
                        <?php endif; ?>
                        <?php echo esc_html($order['shipping_address_line1']); ?><br>
                        <?php if (!empty($order['shipping_address_line2'])): ?>
                            <?php echo esc_html($order['shipping_address_line2']); ?><br>
                        <?php endif; ?>
                        <?php echo esc_html(trim(($order['shipping_postal_code'] ?? '') . ' ' . ($order['shipping_city'] ?? ''))); ?><br>
                        <?php echo esc_html($order['shipping_country'] ?? 'DE'); ?>
                    </p>
                </div>
                <?php endif; ?>
                
                <!-- Rechtliche Zustimmungen (Bestätigung vor Absenden) -->
                <div class="summary-section">
                    <h3><?php _e('Rechtliche Zustimmungen', 'themisdb-order-request'); ?></h3>
                    <ul style="margin:0;padding-left:1.4em;">
                        <li style="color:<?php echo !empty($order['legal_terms_accepted']) ? '#155724' : '#b32d2e'; ?>">
                            <?php echo !empty($order['legal_terms_accepted'])
                                ? '&#10003; ' . esc_html__('AGB akzeptiert', 'themisdb-order-request')
                                : '&#10007; ' . esc_html__('AGB NICHT akzeptiert', 'themisdb-order-request'); ?>
                        </li>
                        <li style="color:<?php echo !empty($order['legal_privacy_accepted']) ? '#155724' : '#b32d2e'; ?>">
                            <?php echo !empty($order['legal_privacy_accepted'])
                                ? '&#10003; ' . esc_html__('Datenschutzerklärung akzeptiert', 'themisdb-order-request')
                                : '&#10007; ' . esc_html__('Datenschutzerklärung NICHT akzeptiert', 'themisdb-order-request'); ?>
                        </li>
                        <?php if (($order['customer_type'] ?? 'consumer') === 'consumer'): ?>
                        <li style="color:<?php echo !empty($order['legal_withdrawal_acknowledged']) ? '#155724' : '#b32d2e'; ?>">
                            <?php echo !empty($order['legal_withdrawal_acknowledged'])
                                ? '&#10003; ' . esc_html__('Widerrufsbelehrung zur Kenntnis genommen', 'themisdb-order-request')
                                : '&#10007; ' . esc_html__('Widerrufsbelehrung NICHT bestätigt', 'themisdb-order-request'); ?>
                        </li>
                        <?php endif; ?>
                        <?php if (!empty($order['legal_withdrawal_waiver'])): ?>
                        <li style="color:#155724;">
                            &#10003; <?php _e('Verzicht auf Widerrufsrecht (§ 356 Abs. 5 BGB) erklärt', 'themisdb-order-request'); ?>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="summary-total">
                    <h3><?php _e('Gesamtbetrag', 'themisdb-order-request'); ?></h3>
                    <p class="total-amount">
                        <strong><?php echo number_format($order['total_amount'], 2, ',', '.'); ?> <?php echo esc_html($order['currency']); ?></strong>
                    </p>
                </div>
            </div>
            
            <div class="order-navigation">
                <button type="button" class="button button-secondary button-prev" data-step="5">
                    ← <?php _e('Zurück', 'themisdb-order-request'); ?>
                </button>
                <button type="button" class="button button-primary button-submit">
                    <?php _e('Bestellung absenden', 'themisdb-order-request'); ?>
                </button>
            </div>
        </div>
        <?php
    }
    
    /**
     * My orders shortcode
     */
    public function my_orders_shortcode() {
        if (!is_user_logged_in()) {
            return '<p>' . __('Bitte melden Sie sich an, um Ihre Bestellungen zu sehen.', 'themisdb-order-request') . '</p>';
        }
        
        $user_id = get_current_user_id();
        $orders = ThemisDB_Order_Manager::get_customer_orders($user_id);
        
        ob_start();
        ?>
        <div class="themisdb-my-orders">
            <h2><?php _e('Meine Bestellungen', 'themisdb-order-request'); ?></h2>
            
            <?php if (empty($orders)): ?>
                <p><?php _e('Sie haben noch keine Bestellungen.', 'themisdb-order-request'); ?></p>
            <?php else: ?>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th><?php _e('Bestellnummer', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Produkt', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Betrag', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Status', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Datum', 'themisdb-order-request'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?php echo esc_html($order['order_number']); ?></td>
                            <td>ThemisDB <?php echo esc_html(ucfirst($order['product_edition'])); ?></td>
                            <td><?php echo number_format($order['total_amount'], 2, ',', '.'); ?> <?php echo esc_html($order['currency']); ?></td>
                            <td><?php echo esc_html(ucfirst($order['status'])); ?></td>
                            <td><?php echo date('d.m.Y', strtotime($order['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * My contracts shortcode
     */
    public function my_contracts_shortcode() {
        if (!is_user_logged_in()) {
            return '<p>' . __('Bitte melden Sie sich an, um Ihre Verträge zu sehen.', 'themisdb-order-request') . '</p>';
        }
        
        $user_id = get_current_user_id();
        $contracts = ThemisDB_Contract_Manager::get_customer_contracts($user_id);
        
        ob_start();
        ?>
        <div class="themisdb-my-contracts">
            <h2><?php _e('Meine Verträge', 'themisdb-order-request'); ?></h2>
            
            <?php if (empty($contracts)): ?>
                <p><?php _e('Sie haben noch keine Verträge.', 'themisdb-order-request'); ?></p>
            <?php else: ?>
                <table class="contracts-table">
                    <thead>
                        <tr>
                            <th><?php _e('Vertragsnummer', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Typ', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Status', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Gültig bis', 'themisdb-order-request'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contracts as $contract): ?>
                        <tr>
                            <td><?php echo esc_html($contract['contract_number']); ?></td>
                            <td><?php echo esc_html(ucfirst($contract['contract_type'])); ?></td>
                            <td><?php echo esc_html(ucfirst($contract['status'])); ?></td>
                            <td><?php echo $contract['valid_until'] ? date('d.m.Y', strtotime($contract['valid_until'])) : '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Validate billing/legal fields for German compliance.
     */
    private function validate_regulatory_fields($data, $strict_legal = true) {
        $errors = array();
        $field_errors = array();

        $customer_type = isset($data['customer_type']) && in_array($data['customer_type'], array('consumer', 'business'), true)
            ? $data['customer_type']
            : 'consumer';
        $billing_country = strtoupper(trim((string) ($data['billing_country'] ?? '')));
        $billing_postal_code = trim((string) ($data['billing_postal_code'] ?? ''));
        $shipping_country = strtoupper(trim((string) ($data['shipping_country'] ?? 'DE')));
        $shipping_postal_code = trim((string) ($data['shipping_postal_code'] ?? ''));
        $vat_id = strtoupper(trim((string) ($data['vat_id'] ?? '')));

        $required_fields = array('customer_name', 'customer_email', 'billing_name', 'billing_address_line1', 'billing_postal_code', 'billing_city', 'billing_country');
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                $field_errors[$field] = __('Pflichtfeld.', 'themisdb-order-request');
            }
        }

        if (!empty($data['customer_email']) && !is_email($data['customer_email'])) {
            $field_errors['customer_email'] = __('Ungültige E-Mail-Adresse.', 'themisdb-order-request');
        }

        if ($billing_country !== '' && !preg_match('/^[A-Z]{2}$/', $billing_country)) {
            $field_errors['billing_country'] = __('Land als 2-stelliger ISO-Code (z. B. DE).', 'themisdb-order-request');
        }

        if ($billing_country === 'DE' && $billing_postal_code !== '' && !preg_match('/^\d{5}$/', $billing_postal_code)) {
            $field_errors['billing_postal_code'] = __('Für Deutschland sind 5 Ziffern erforderlich.', 'themisdb-order-request');
        }

        if ($shipping_country !== '' && !preg_match('/^[A-Z]{2}$/', $shipping_country)) {
            $field_errors['shipping_country'] = __('Land als 2-stelliger ISO-Code (z. B. DE).', 'themisdb-order-request');
        }

        if ($shipping_postal_code !== '' && $shipping_country === 'DE' && !preg_match('/^\d{5}$/', $shipping_postal_code)) {
            $field_errors['shipping_postal_code'] = __('Für Deutschland sind 5 Ziffern erforderlich.', 'themisdb-order-request');
        }

        if ($customer_type === 'business' && empty($data['customer_company'])) {
            $field_errors['customer_company'] = __('Firmennamen für Unternehmenskunden angeben.', 'themisdb-order-request');
        }

        if ($vat_id !== '' && !preg_match('/^[A-Z]{2}[A-Z0-9]{2,12}$/', $vat_id)) {
            $field_errors['vat_id'] = __('Ungültiges USt-IdNr.-Format (z. B. DE123456789).', 'themisdb-order-request');
        }

        if ($strict_legal) {
            if (empty($data['legal_terms_accepted'])) {
                $field_errors['legal_terms_accepted'] = __('AGB müssen akzeptiert werden.', 'themisdb-order-request');
            }

            if (empty($data['legal_privacy_accepted'])) {
                $field_errors['legal_privacy_accepted'] = __('Datenschutz muss akzeptiert werden.', 'themisdb-order-request');
            }

            if ($customer_type === 'consumer' && empty($data['legal_withdrawal_acknowledged'])) {
                $field_errors['legal_withdrawal_acknowledged'] = __('Widerrufsbelehrung muss bestätigt werden.', 'themisdb-order-request');
            }
        }

        if (!empty($field_errors)) {
            $errors[] = __('Bitte prüfen Sie die markierten Eingaben.', 'themisdb-order-request');
        }

        return array(
            'errors' => $errors,
            'field_errors' => $field_errors,
        );
    }
    
    /**
     * AJAX: Save order step
     */
    public function ajax_save_order_step() {
        check_ajax_referer('themisdb_order_nonce', 'nonce');
        
        $step = isset($_POST['step']) ? intval($_POST['step']) : 0;
        $data = isset($_POST['data']) ? $_POST['data'] : array();
        
        // Sanitize the data array
        $sanitized_data = array();
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                // Sanitize array values (for modules and training_modules)
                $sanitized_data[$key] = array_map('sanitize_text_field', $value);
            } else {
                // Sanitize scalar values
                $sanitized_data[$key] = sanitize_text_field($value);
            }
        }
        $data = $sanitized_data;

        if ($step === 4) {
            $customer_type = isset($data['customer_type']) && in_array($data['customer_type'], array('consumer', 'business'), true)
                ? $data['customer_type']
                : 'consumer';

            $legal_compliance_enabled = get_option('themisdb_order_legal_compliance') === '1';
            $validation = $this->validate_regulatory_fields($data, $legal_compliance_enabled);
            if (!empty($validation['field_errors'])) {
                wp_send_json_error(array(
                    'message' => __('Bitte korrigieren Sie die Eingaben in Schritt 4.', 'themisdb-order-request'),
                    'field_errors' => $validation['field_errors'],
                ));
                return;
            }

            $data['customer_type'] = $customer_type;
            $data['legal_acceptance_version'] = get_option('themisdb_order_legal_version', 'de-v1');
            $data['legal_accepted_at'] = current_time('mysql');
            $data['legal_accepted_ip'] = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : null;
            $data['legal_accepted_user_agent'] = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : null;
        }
        
        // Start session if not started
        if (!session_id()) {
            session_start();
        }
        
        $order_id = isset($_SESSION['themisdb_order_id']) ? $_SESSION['themisdb_order_id'] : null;
        
        if (!$order_id) {
            // Create new order
            $product_edition = isset($data['product_edition']) ? $data['product_edition'] : 'community';
            $product = ThemisDB_Order_Manager::get_product_by_edition($product_edition);
            $product_type = ($product && !empty($product['product_type'])) ? $product['product_type'] : 'database';

            $order_data = array(
                'customer_email' => isset($data['customer_email']) ? $data['customer_email'] : '',
                'customer_name' => isset($data['customer_name']) ? $data['customer_name'] : '',
                'customer_company' => isset($data['customer_company']) ? $data['customer_company'] : '',
                'product_type' => $product_type,
                'product_edition' => $product_edition
            );
            
            $order_id = ThemisDB_Order_Manager::create_order($order_data);
            if (!$order_id) {
                error_log('ThemisDB Step Save Error: Failed to create new order.');
                wp_send_json_error(array('message' => __('Bestellung konnte nicht erstellt werden. Bitte versuchen Sie später erneut.', 'themisdb-order-request')));
                return;
            }
            $_SESSION['themisdb_order_id'] = $order_id;
        } else {
            // Update existing order
            ThemisDB_Order_Manager::update_order($order_id, $data);
        }
        
        // Calculate total
        if (isset($data['product_edition']) || isset($data['modules']) || isset($data['training_modules'])) {
            $order = ThemisDB_Order_Manager::get_order($order_id);
            $total = ThemisDB_Order_Manager::calculate_total(
                $order['product_edition'],
                isset($data['modules']) ? $data['modules'] : ($order['modules'] ?: array()),
                isset($data['training_modules']) ? $data['training_modules'] : ($order['training_modules'] ?: array())
            );
            
            ThemisDB_Order_Manager::update_order($order_id, array('total_amount' => $total, 'step' => $step + 1));
        }
        
        wp_send_json_success(array(
            'order_id' => $order_id,
            'next_step' => $step + 1
        ));
    }
    
    /**
     * AJAX: Calculate total
     */
    public function ajax_calculate_total() {
        check_ajax_referer('themisdb_order_nonce', 'nonce');
        
        $product_edition = isset($_POST['product_edition']) ? sanitize_text_field($_POST['product_edition']) : '';
        $modules = isset($_POST['modules']) && is_array($_POST['modules']) ? array_map('sanitize_text_field', $_POST['modules']) : array();
        $training_modules = isset($_POST['training_modules']) && is_array($_POST['training_modules']) ? array_map('sanitize_text_field', $_POST['training_modules']) : array();
        
        $total = ThemisDB_Order_Manager::calculate_total($product_edition, $modules, $training_modules);
        
        wp_send_json_success(array('total' => $total));
    }
    
    /**
     * AJAX: Submit order
     */
    public function ajax_submit_order() {
        check_ajax_referer('themisdb_order_nonce', 'nonce');
        
        if (!session_id()) {
            session_start();
        }
        
        $order_id = isset($_SESSION['themisdb_order_id']) ? $_SESSION['themisdb_order_id'] : null;
        
        if (!$order_id) {
            wp_send_json_error(array('message' => __('Keine Bestellung gefunden', 'themisdb-order-request')));
            return;
        }

        $order = ThemisDB_Order_Manager::get_order($order_id);
        if (!$order) {
            wp_send_json_error(array('message' => __('Bestellung nicht gefunden', 'themisdb-order-request')));
            return;
        }

        $validation = $this->validate_regulatory_fields($order, true);
        if (!empty($validation['field_errors'])) {
            wp_send_json_error(array(
                'message' => __('Rechnungs- und Rechtsdaten sind unvollständig oder ungültig. Bitte Schritt 4 prüfen.', 'themisdb-order-request'),
                'field_errors' => $validation['field_errors'],
            ));
            return;
        }

        if (empty($order['legal_accepted_at'])) {
            ThemisDB_Order_Manager::update_order($order_id, array(
                'legal_accepted_at' => current_time('mysql'),
                'legal_accepted_ip' => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : null,
                'legal_accepted_user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : null,
                'legal_acceptance_version' => get_option('themisdb_order_legal_version', 'de-v1'),
            ));
            $order = ThemisDB_Order_Manager::get_order($order_id);
        }
        
        // Update order status and stop immediately if the transition fails.
        $status_updated = ThemisDB_Order_Manager::set_order_status($order_id, 'pending');
        if (!$status_updated) {
            error_log('ThemisDB Order Submit Error: Failed to set order status to pending for order ID ' . $order_id);
            wp_send_json_error(array('message' => __('Bestellung konnte nicht finalisiert werden. Bitte erneut versuchen.', 'themisdb-order-request')));
            return;
        }
        
        // Send confirmation email
        ThemisDB_Email_Handler::send_order_confirmation($order_id);
        
        $order = ThemisDB_Order_Manager::get_order($order_id);

        // Ensure at least one order item exists for downstream processing.
        $existing_items = ThemisDB_Order_Manager::get_order_items($order_id);
        if (empty($existing_items)) {
            $product = ThemisDB_Order_Manager::get_product_by_edition($order['product_edition']);
            if ($product) {
                ThemisDB_Order_Manager::set_order_items($order_id, array(
                    array(
                        'item_type' => $order['product_type'],
                        'product_id' => $product['id'],
                        'sku' => !empty($product['product_code']) ? $product['product_code'] : null,
                        'item_name' => $product['product_name'],
                        'quantity' => 1,
                        'unit_price' => $product['price'],
                    )
                ), $order['currency']);
                ThemisDB_Order_Manager::recalculate_order_total_from_items($order_id);
                $order = ThemisDB_Order_Manager::get_order($order_id);
            }
        }

        // Merchandise flow: no contract/license creation, only payment + invoice.
        if ($order['product_type'] === 'merchandise') {
            $payment_data = array(
                'order_id' => $order_id,
                'amount' => $order['total_amount'],
                'currency' => $order['currency'],
                'payment_method' => 'bank_transfer'
            );
            ThemisDB_Payment_Manager::create_payment($payment_data);

            ThemisDB_Order_Manager::reserve_inventory_for_order($order_id);

            try {
                ThemisDB_Email_Handler::send_invoice_email($order_id);
            } catch (Exception $e) {
                error_log('ThemisDB Invoice Email Error (Merchandise Submit): ' . $e->getMessage());
            }
        } else {
            // License flow: keep current contract/license workflow.
            $conversion = ThemisDB_Contract_Manager::ensure_contract_for_order(
                $order_id,
                array(
                    'contract_type' => 'license',
                    'generate_pdf' => true,
                    'send_email' => true
                )
            );

            if (!empty($conversion['success']) && !empty($conversion['contract_id'])) {
                $contract_id = intval($conversion['contract_id']);

                try {
                    ThemisDB_Email_Handler::send_invoice_email($order_id);
                } catch (Exception $e) {
                    error_log('ThemisDB Invoice Email Error (AJAX Submit): ' . $e->getMessage());
                }

                $existing_payments = ThemisDB_Payment_Manager::get_payments_by_order($order_id);
                if (empty($existing_payments)) {
                    $payment_data = array(
                        'order_id' => $order_id,
                        'contract_id' => $contract_id,
                        'amount' => $order['total_amount'],
                        'currency' => $order['currency'],
                        'payment_method' => 'bank_transfer'
                    );
                    ThemisDB_Payment_Manager::create_payment($payment_data);
                }

                $existing_license = ThemisDB_License_Manager::get_license_by_contract($contract_id);
                if (!$existing_license) {
                    $license_data = array(
                        'order_id' => $order_id,
                        'contract_id' => $contract_id,
                        'customer_id' => $order['customer_id'],
                        'product_edition' => $order['product_edition'],
                        'license_type' => 'standard'
                    );
                    ThemisDB_License_Manager::create_license($license_data);
                }
            }
        }
        
        // Clear session
        unset($_SESSION['themisdb_order_id']);
        
        wp_send_json_success(array(
            'order_number' => $order['order_number'],
            'message' => __('Ihre Bestellung wurde erfolgreich übermittelt!', 'themisdb-order-request')
        ));
    }
    
    /**
     * Dynamic pricing display shortcode
     * Usage: [themisdb_pricing] or [themisdb_pricing format="cards"]
     */
    public function pricing_shortcode($atts) {
        $atts = shortcode_atts(array(
            'format' => 'cards', // cards, table, comparison
            'currency' => 'EUR',
            'show_features' => 'yes'
        ), $atts);
        
        ob_start();
        $this->render_pricing(
            sanitize_text_field($atts['format']),
            sanitize_text_field($atts['currency']),
            sanitize_text_field($atts['show_features']) === 'yes'
        );
        return ob_get_clean();
    }
    
    /**
     * Pricing table shortcode (legacy/alternative)
     * Usage: [themisdb_pricing_table]
     */
    public function pricing_table_shortcode($atts) {
        ob_start();
        $this->render_pricing('table', 'EUR', true);
        return ob_get_clean();
    }
    
    /**
     * Render pricing from database
     */
    private function render_pricing($format = 'cards', $currency = 'EUR', $show_features = true) {
        global $wpdb;
        
        // Get latest license prices (distinct by product_edition, valid_from DESC)
        $query = "
            SELECT lp.*, p.edition, p.product_name, p.description
            FROM {$wpdb->prefix}themisdb_license_prices lp
            LEFT JOIN {$wpdb->prefix}themisdb_products p ON lp.product_edition = p.edition
            WHERE lp.valid_from <= CURDATE()
            AND (lp.valid_until IS NULL OR lp.valid_until >= CURDATE())
            AND lp.currency = %s
            ORDER BY lp.product_edition, lp.valid_from DESC
            GROUP BY lp.product_edition
        ";
        
        $prices = $wpdb->get_results($wpdb->prepare($query, $currency), ARRAY_A);
        
        if (empty($prices)) {
            echo '<p>' . esc_html__('Keine Lizenzpreise verfügbar.', 'themisdb-order-request') . '</p>';
            return;
        }
        
        // Get features for each license type
        $features_by_type = array();
        if ($show_features) {
            $features_query = "
                SELECT lf.*, lp.product_edition
                FROM {$wpdb->prefix}themisdb_license_features lf
                LEFT JOIN {$wpdb->prefix}themisdb_license_prices lp ON lf.license_id = lp.license_id
                WHERE lf.is_active = 1
                AND lf.valid_from <= CURDATE()
                AND (lf.valid_until IS NULL OR lf.valid_until >= CURDATE())
                GROUP BY lf.feature_code, lp.product_edition
            ";
            
            $features = $wpdb->get_results($features_query, ARRAY_A);
            foreach ($features as $feature) {
                if (!isset($features_by_type[$feature['product_edition']])) {
                    $features_by_type[$feature['product_edition']] = array();
                }
                $features_by_type[$feature['product_edition']][] = $feature;
            }
        }
        
        // Render based on format
        if ($format === 'table') {
            $this->render_pricing_table($prices, $features_by_type, $show_features);
        } elseif ($format === 'comparison') {
            $this->render_pricing_comparison($prices, $features_by_type, $show_features);
        } else {
            // Default: cards
            $this->render_pricing_cards($prices, $features_by_type, $show_features);
        }
    }
    
    /**
     * Render pricing as cards
     */
    private function render_pricing_cards($prices, $features_by_type, $show_features) {
        ?>
        <div class="themisdb-pricing-cards">
            <div class="pricing-grid">
                <?php foreach ($prices as $price): ?>
                <div class="pricing-card">
                    <div class="pricing-header">
                        <h3 class="pricing-title">
                            <?php echo esc_html($price['product_name'] ?: ucfirst($price['product_edition'])); ?>
                        </h3>
                        <p class="pricing-description">
                            <?php echo esc_html($price['description'] ?: ''); ?>
                        </p>
                    </div>
                    
                    <div class="pricing-price">
                        <?php if ($price['base_price'] == 0): ?>
                            <p class="price-amount"><strong><?php _e('Kostenlos', 'themisdb-order-request'); ?></strong></p>
                        <?php else: ?>
                            <p class="price-currency"><?php echo esc_html($price['currency']); ?></p>
                            <p class="price-amount"><strong><?php echo number_format($price['base_price'], 0, ',', '.'); ?></strong></p>
                            <p class="price-period"><?php _e('pro Jahr', 'themisdb-order-request'); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="pricing-specs">
                        <?php if ($price['max_nodes']): ?>
                        <div class="spec"><strong><?php _e('Knoten', 'themisdb-order-request'); ?>:</strong> <?php echo esc_html($price['max_nodes']); ?></div>
                        <?php endif; ?>
                        <?php if ($price['max_cores']): ?>
                        <div class="spec"><strong><?php _e('CPU-Cores', 'themisdb-order-request'); ?>:</strong> <?php echo esc_html($price['max_cores']); ?></div>
                        <?php endif; ?>
                        <?php if ($price['max_storage_gb']): ?>
                        <div class="spec"><strong><?php _e('Speicher', 'themisdb-order-request'); ?>:</strong> <?php echo number_format($price['max_storage_gb'], 0, '', '.'); ?> GB</div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($show_features && isset($features_by_type[$price['product_edition']])): ?>
                    <div class="pricing-features">
                        <h4><?php _e('Features', 'themisdb-order-request'); ?></h4>
                        <ul>
                            <?php foreach ($features_by_type[$price['product_edition']] as $feature): ?>
                            <li>
                                <span class="feature-check">✓</span>
                                <span class="feature-name"><?php echo esc_html($feature['feature_name']); ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <div class="pricing-action">
                        <a href="<?php echo esc_url(home_url('/bestellung')); ?>" class="button button-primary">
                            <?php _e('Jetzt wählen', 'themisdb-order-request'); ?>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <style>
            .themisdb-pricing-cards { padding: 40px 0; }
            .pricing-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 30px;
                max-width: 1200px;
                margin: 0 auto;
            }
            .pricing-card {
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                padding: 30px;
                background: #fff;
                display: flex;
                flex-direction: column;
                transition: transform 0.3s, box-shadow 0.3s;
            }
            .pricing-card:hover { 
                transform: translateY(-5px);
                box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            }
            .pricing-title { margin: 0 0 10px 0; font-size: 22px; }
            .pricing-description { margin: 0 0 20px 0; color: #666; font-size: 14px; }
            .pricing-price { 
                text-align: center; 
                padding: 20px 0; 
                border-top: 1px solid #f0f0f0;
                border-bottom: 1px solid #f0f0f0;
                margin-bottom: 20px;
            }
            .price-amount { font-size: 36px; margin: 10px 0; }
            .price-period { font-size: 12px; color: #999; margin: 0; }
            .pricing-specs { margin-bottom: 20px; }
            .spec { padding: 8px 0; font-size: 14px; }
            .pricing-features { background: #f9f9f9; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
            .pricing-features h4 { margin-top: 0; font-size: 14px; }
            .pricing-features ul { list-style: none; padding: 0; margin: 0; }
            .pricing-features li { padding: 6px 0; font-size: 13px; }
            .feature-check { color: #28a745; margin-right: 8px; font-weight: bold; }
            .pricing-action { flex-grow: 1; display: flex; align-items: flex-end; }
        </style>
        <?php
    }
    
    /**
     * Render pricing as table
     */
    private function render_pricing_table($prices, $features_by_type, $show_features) {
        ?>
        <table class="themisdb-pricing-table">
            <thead>
                <tr>
                    <th><?php _e('Edition', 'themisdb-order-request'); ?></th>
                    <th><?php _e('Preis', 'themisdb-order-request'); ?></th>
                    <th><?php _e('Knoten', 'themisdb-order-request'); ?></th>
                    <th><?php _e('CPU-Cores', 'themisdb-order-request'); ?></th>
                    <th><?php _e('Speicher', 'themisdb-order-request'); ?></th>
                    <?php if ($show_features): ?>
                    <th><?php _e('Hauptfeatures', 'themisdb-order-request'); ?></th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($prices as $price): ?>
                <tr>
                    <td><strong><?php echo esc_html($price['product_name'] ?: ucfirst($price['product_edition'])); ?></strong></td>
                    <td>
                        <?php if ($price['base_price'] == 0): ?>
                            <strong><?php _e('Kostenlos', 'themisdb-order-request'); ?></strong>
                        <?php else: ?>
                            <strong><?php echo number_format($price['base_price'], 2, ',', '.'); ?> <?php echo esc_html($price['currency']); ?></strong> / a
                        <?php endif; ?>
                    </td>
                    <td><?php echo $price['max_nodes'] ?: '∞'; ?></td>
                    <td><?php echo $price['max_cores'] ?: '—'; ?></td>
                    <td><?php echo $price['max_storage_gb'] ? number_format($price['max_storage_gb'], 0, '', '.') . ' GB' : '∞'; ?></td>
                    <?php if ($show_features): ?>
                    <td>
                        <?php if (isset($features_by_type[$price['product_edition']])): ?>
                            <?php 
                            $feature_names = array_column($features_by_type[$price['product_edition']], 'feature_name');
                            echo esc_html(implode(', ', array_slice($feature_names, 0, 3)));
                            if (count($feature_names) > 3) {
                                echo sprintf(' <em>+%d weitere</em>', count($feature_names) - 3);
                            }
                            ?>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <style>
            .themisdb-pricing-table { 
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
            }
            .themisdb-pricing-table th, .themisdb-pricing-table td {
                padding: 15px;
                border: 1px solid #ddd;
                text-align: left;
            }
            .themisdb-pricing-table th {
                background-color: #f5f5f5;
                font-weight: bold;
            }
            .themisdb-pricing-table tbody tr:nth-child(even) {
                background-color: #fafafa;
            }
        </style>
        <?php
    }
    
    /**
     * Render pricing as comparison view
     */
    private function render_pricing_comparison($prices, $features_by_type, $show_features) {
        ?>
        <div class="themisdb-pricing-comparison">
            <h2><?php _e('Vergleichen Sie unsere Editionen', 'themisdb-order-request'); ?></h2>
            
            <table class="comparison-table">
                <thead>
                    <tr>
                        <th<?php _e('Funktion', 'themisdb-order-request'); ?></th>
                        <?php foreach ($prices as $price): ?>
                        <th>
                            <div class="edition-name"><?php echo esc_html($price['product_name'] ?: ucfirst($price['product_edition'])); ?></div>
                            <div class="edition-price">
                                <?php if ($price['base_price'] == 0): ?>
                                    <?php _e('Kostenlos', 'themisdb-order-request'); ?>
                                <?php else: ?>
                                    <?php echo number_format($price['base_price'], 0, ',', '.'); ?> <?php echo esc_html($price['currency']); ?>/a
                                <?php endif; ?>
                            </div>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Collect all unique features
                    $all_features = array();
                    foreach ($features_by_type as $features) {
                        foreach ($features as $feature) {
                            $all_features[$feature['feature_code']] = $feature['feature_name'];
                        }
                    }
                    ?>
                    
                    <?php foreach ($all_features as $code => $name): ?>
                    <tr>
                        <td class="feature-name"><strong><?php echo esc_html($name); ?></strong></td>
                        <?php foreach ($prices as $price): ?>
                        <td class="feature-availability">
                            <?php
                            $has_feature = false;
                            if (isset($features_by_type[$price['product_edition']])) {
                                foreach ($features_by_type[$price['product_edition']] as $feature) {
                                    if ($feature['feature_code'] === $code) {
                                        $has_feature = true;
                                        break;
                                    }
                                }
                            }
                            
                            if ($has_feature) {
                                echo '<span class="feature-included">✓</span>';
                            } else {
                                echo '<span class="feature-excluded">—</span>';
                            }
                            ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <style>
            .themisdb-pricing-comparison { padding: 40px 0; }
            .comparison-table {
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
                background: white;
            }
            .comparison-table th {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 20px;
                text-align: center;
                font-weight: bold;
            }
            .edition-name { font-size: 16px; margin-bottom: 8px; }
            .edition-price { font-size: 14px; opacity: 0.9; }
            .comparison-table td {
                padding: 15px 20px;
                border: 1px solid #e0e0e0;
                text-align: center;
            }
            .comparison-table td.feature-name {
                text-align: left;
                background: #f9f9f9;
                font-weight: 600;
            }
            .feature-included { color: #28a745; font-weight: bold; font-size: 18px; }
            .feature-excluded { color: #ccc; }
            .comparison-table tbody tr:hover {
                background-color: #f5f5f5;
            }
        </style>
        <?php
    }
}
