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
        add_shortcode('themisdb_express_checkout', array($this, 'express_checkout_shortcode'));
        add_shortcode('themisdb_my_orders', array($this, 'my_orders_shortcode'));
        add_shortcode('themisdb_my_contracts', array($this, 'my_contracts_shortcode'));
        add_shortcode('themisdb_pricing', array($this, 'pricing_shortcode'));
        add_shortcode('themisdb_pricing_table', array($this, 'pricing_table_shortcode'));
        add_shortcode('themisdb_product_detail', array($this, 'product_detail_shortcode'));
            add_shortcode('themisdb_shop', array($this, 'shop_shortcode'));
        add_shortcode('themisdb_shopping_cart',  array($this, 'shopping_cart_shortcode'));

        // AJAX handlers
        add_action('wp_ajax_themisdb_save_order_step', array($this, 'ajax_save_order_step'));
        add_action('wp_ajax_nopriv_themisdb_save_order_step', array($this, 'ajax_save_order_step'));
        add_action('wp_ajax_themisdb_calculate_total', array($this, 'ajax_calculate_total'));
        add_action('wp_ajax_nopriv_themisdb_calculate_total', array($this, 'ajax_calculate_total'));
        add_action('wp_ajax_themisdb_submit_order', array($this, 'ajax_submit_order'));
        add_action('wp_ajax_nopriv_themisdb_submit_order', array($this, 'ajax_submit_order'));
        // Shopping cart AJAX.
        add_action('wp_ajax_themisdb_cart_remove_item',   array($this, 'ajax_cart_remove_item'));
        add_action('wp_ajax_nopriv_themisdb_cart_remove_item', array($this, 'ajax_cart_remove_item'));
        add_action('wp_ajax_themisdb_cart_clear',         array($this, 'ajax_cart_clear'));
        add_action('wp_ajax_nopriv_themisdb_cart_clear',  array($this, 'ajax_cart_clear'));
    }
    
    /**
     * Order flow shortcode - Dialog-based order process
     */
    public function order_flow_shortcode($atts) {
        $atts = shortcode_atts(array(
            'step' => 1,
            'product' => '',
        ), $atts);

        $preset_product = $this->get_requested_product_edition($atts['product']);
        $available_modules = ThemisDB_Order_Manager::get_modules();
        $available_trainings = ThemisDB_Order_Manager::get_training_modules();
        $preset_modules = $this->get_requested_code_list(array('modules', 'module'), array_column($available_modules, 'module_code'));
        $preset_training = $this->get_requested_code_list(array('training', 'trainings'), array_column($available_trainings, 'training_code'));
        if ($preset_product !== '') {
            $target_step = 1;
            if (isset($_GET['checkout']) && sanitize_key((string) wp_unslash($_GET['checkout'])) === '1') {
                $target_step = 4;
            } elseif (!empty($preset_training)) {
                $target_step = 3;
            } elseif (!empty($preset_modules)) {
                $target_step = 2;
            }

            $this->bootstrap_preset_order($preset_product, $target_step, $preset_modules, $preset_training);
        }
        
        ob_start();
        $this->render_order_flow(intval($atts['step']), array(
            'flow_mode' => 'default',
            'skip_modules' => false,
            'skip_training' => false,
        ));
        return ob_get_clean();
    }

    /**
     * Express checkout shortcode (3-step wrapper over the existing 5-step engine).
     * Usage: [themisdb_express_checkout product="community" show_modules="false" show_training="false"]
     */
    public function express_checkout_shortcode($atts) {
        $atts = shortcode_atts(array(
            'product' => '',
            'show_modules' => 'false',
            'show_training' => 'false',
            'step' => 1,
        ), $atts);

        $show_modules = sanitize_text_field($atts['show_modules']) === 'true';
        $show_training = sanitize_text_field($atts['show_training']) === 'true';

        // If add-ons are requested, use the regular 5-step flow.
        if ($show_modules || $show_training) {
            return $this->order_flow_shortcode(array('step' => intval($atts['step'])));
        }

        $preset_product = $this->get_requested_product_edition($atts['product']);
        $start_step = intval($atts['step']);
        if ($start_step < 1) {
            $start_step = 1;
        }

        if ($preset_product !== '') {
            $available_modules = ThemisDB_Order_Manager::get_modules();
            $available_trainings = ThemisDB_Order_Manager::get_training_modules();
            $preset_modules = $this->get_requested_code_list(array('modules', 'module'), array_column($available_modules, 'module_code'));
            $preset_training = $this->get_requested_code_list(array('training', 'trainings'), array_column($available_trainings, 'training_code'));
            $this->bootstrap_preset_order($preset_product, 4, $preset_modules, $preset_training);
        }

        ob_start();
        $this->render_order_flow($start_step, array(
            'flow_mode' => 'express',
            'skip_modules' => true,
            'skip_training' => true,
        ));
        return ob_get_clean();
    }

    /**
     * Ensure a draft session order exists for express checkout and apply an optional preset product.
     */
    private function bootstrap_preset_order($preset_product, $target_step = 1, $preset_modules = array(), $preset_training = array()) {
        if (!session_id()) {
            session_start();
        }

        $preset_product = sanitize_key((string) $preset_product);
        if ($preset_product === '') {
            return;
        }

        $target_step = max(1, intval($target_step));

        $product = ThemisDB_Order_Manager::get_product_by_edition($preset_product);
        if (!$product) {
            return;
        }

        $order_id = isset($_SESSION['themisdb_order_id']) ? intval($_SESSION['themisdb_order_id']) : 0;
        $order = $order_id > 0 ? ThemisDB_Order_Manager::get_order($order_id) : null;

        if (!$order || !in_array(($order['status'] ?? 'draft'), array('draft', 'pending'), true)) {
            $order_id = ThemisDB_Order_Manager::create_order(array(
                'product_edition' => $preset_product,
                'product_type' => $product['product_type'] ?? 'database',
            ));
            if (!$order_id) {
                return;
            }
            $_SESSION['themisdb_order_id'] = $order_id;
        }

        $preset_modules = is_array($preset_modules) ? array_values(array_unique(array_map('sanitize_text_field', $preset_modules))) : array();
        $preset_training = is_array($preset_training) ? array_values(array_unique(array_map('sanitize_text_field', $preset_training))) : array();

        $total = ThemisDB_Order_Manager::calculate_total($preset_product, $preset_modules, $preset_training);
        ThemisDB_Order_Manager::update_order($order_id, array(
            'product_edition' => $preset_product,
            'product_type' => $product['product_type'] ?? 'database',
            'modules' => $preset_modules,
            'training_modules' => $preset_training,
            'total_amount' => $total,
            'step' => $target_step,
        ));
    }

    /**
     * Resolve a requested product edition from shortcode attributes or URL parameters.
     *
     * @param string $fallback
     * @return string
     */
    private function get_requested_product_edition($fallback = '') {
        $requested_product = isset($_GET['product']) ? wp_unslash($_GET['product']) : '';
        if ($requested_product === '' && isset($_GET['edition'])) {
            $requested_product = wp_unslash($_GET['edition']);
        }

        if ($requested_product === '') {
            $requested_product = $fallback;
        }

        return sanitize_key((string) $requested_product);
    }

    /**
     * Resolve a sanitized list of requested codes from URL parameters.
     *
     * @param array|string $query_keys
     * @param array        $allowed_codes
     * @param string       $fallback
     * @return array
     */
    private function get_requested_code_list($query_keys, $allowed_codes = array(), $fallback = '') {
        $query_keys = is_array($query_keys) ? $query_keys : array($query_keys);
        $raw_value = '';

        foreach ($query_keys as $query_key) {
            if (isset($_GET[$query_key])) {
                $raw_value = wp_unslash($_GET[$query_key]);
                break;
            }
        }

        if ($raw_value === '') {
            $raw_value = $fallback;
        }

        if ($raw_value === '') {
            return array();
        }

        $allowed_lookup = array();
        foreach ((array) $allowed_codes as $allowed_code) {
            $allowed_lookup[sanitize_text_field((string) $allowed_code)] = true;
        }

        $codes = array();
        foreach (explode(',', (string) $raw_value) as $code) {
            $sanitized_code = sanitize_text_field(trim((string) $code));
            if ($sanitized_code === '') {
                continue;
            }
            if (!empty($allowed_lookup) && !isset($allowed_lookup[$sanitized_code])) {
                continue;
            }
            $codes[] = $sanitized_code;
        }

        return array_values(array_unique($codes));
    }
    
    /**
     * Render order flow
     */
    private function render_order_flow($current_step = 1, $options = array()) {
        $options = wp_parse_args($options, array(
            'flow_mode' => 'default',
            'skip_modules' => false,
            'skip_training' => false,
        ));

        $is_express = ($options['flow_mode'] === 'express');

        // Get or create order session
        $order_id = isset($_SESSION['themisdb_order_id']) ? $_SESSION['themisdb_order_id'] : null;
        $order = null;
        
        if ($order_id) {
            $order = ThemisDB_Order_Manager::get_order($order_id);
            if ($order) {
                $current_step = $order['step'];
            }
        }

        if ($is_express) {
            $current_step = intval($current_step);
            if (in_array($current_step, array(2, 3), true)) {
                $current_step = 4;
            }

            if ($order && !empty($order['product_edition']) && $current_step < 4) {
                $current_step = 4;
            }

            if (!in_array($current_step, array(1, 4, 5), true)) {
                $current_step = 1;
            }
        }
        
        ?>
        <div class="themisdb-order-flow <?php echo $is_express ? 'themisdb-order-flow--express' : ''; ?>" data-flow-mode="<?php echo esc_attr($is_express ? 'express' : 'default'); ?>">
            <!-- Progress Steps -->
            <div class="order-steps">
                <?php if ($is_express) : ?>
                <div class="step <?php echo $current_step >= 1 ? 'active' : ''; ?> <?php echo $current_step > 1 ? 'completed' : ''; ?>" data-step-value="1">
                    <span class="step-number">1</span>
                    <span class="step-title"><?php _e('Produkt', 'themisdb-order-request'); ?></span>
                </div>
                <div class="step <?php echo $current_step >= 4 ? 'active' : ''; ?> <?php echo $current_step > 4 ? 'completed' : ''; ?>" data-step-value="4">
                    <span class="step-number">2</span>
                    <span class="step-title"><?php _e('Checkout', 'themisdb-order-request'); ?></span>
                </div>
                <div class="step <?php echo $current_step >= 5 ? 'active' : ''; ?>" data-step-value="5">
                    <span class="step-number">3</span>
                    <span class="step-title"><?php _e('Bestätigung', 'themisdb-order-request'); ?></span>
                </div>
                <?php else : ?>
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
                <?php endif; ?>
            </div>
            
            <!-- Step Content -->
            <div class="order-content">
                <?php
                if ($is_express) {
                    switch ($current_step) {
                        case 4:
                            $this->render_step_customer($order);
                            break;
                        case 5:
                            $this->render_step_summary($order);
                            break;
                        case 1:
                        default:
                            $this->render_step_product($order);
                            break;
                    }
                } else {
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
                }
                ?>
            </div>

            <?php if ($is_express) : ?>
            <script>
            (function($){
                var $wrap = $('.themisdb-order-flow--express');
                if (!$wrap.length) return;
                $wrap.find('.order-step-content[data-step="1"] .button-next').attr('data-next-step', '4');
                $wrap.find('.order-step-content[data-step="4"] .button-prev').attr('data-prev-step', '1');
            })(jQuery);
            </script>
            <?php endif; ?>
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
        <div class="order-step-content" data-step="2" id="modules">
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
        <div class="order-step-content" data-step="3" id="training">
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

                <?php
                $selected_payment_method = sanitize_key((string) ($order['payment_method'] ?? 'bank_transfer'));
                if (!in_array($selected_payment_method, array('bank_transfer', 'stripe', 'paypal'), true)) {
                    $selected_payment_method = 'bank_transfer';
                }
                ?>
                <div class="summary-section themisdb-payment-methods">
                    <h3><?php _e('Zahlungsmethode', 'themisdb-order-request'); ?></h3>

                    <label class="themisdb-payment-option" style="display:block;margin:.4rem 0;">
                        <input type="radio" name="payment_method" value="bank_transfer" <?php checked($selected_payment_method, 'bank_transfer'); ?>>
                        <?php _e('Banküberweisung (Rechnung)', 'themisdb-order-request'); ?>
                    </label>

                    <label class="themisdb-payment-option" style="display:block;margin:.4rem 0;">
                        <input type="radio" name="payment_method" value="stripe" <?php checked($selected_payment_method, 'stripe'); ?>>
                        <?php _e('Kreditkarte (Stripe)', 'themisdb-order-request'); ?>
                    </label>

                    <label class="themisdb-payment-option" style="display:block;margin:.4rem 0;">
                        <input type="radio" name="payment_method" value="paypal" <?php checked($selected_payment_method, 'paypal'); ?>>
                        <?php _e('PayPal', 'themisdb-order-request'); ?>
                    </label>

                    <p style="margin:.6rem 0 0;color:#64748b;font-size:.9em;">
                        <?php _e('Stripe/PayPal werden als Sofortzahlung verarbeitet. Bei Banküberweisung bleibt der Status auf ausstehend, bis der Zahlungseingang verifiziert ist.', 'themisdb-order-request'); ?>
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
        $flow_mode = isset($_POST['flow_mode']) ? sanitize_key($_POST['flow_mode']) : 'default';
        $is_express = ($flow_mode === 'express');
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
                if (class_exists('ThemisDB_Error_Handler')) {
                    ThemisDB_Error_Handler::log('error', 'Step save failed: order creation returned false', array(
                        'step' => $step,
                        'flow_mode' => $flow_mode,
                    ));
                }
                wp_send_json_error(array('message' => __('Bestellung konnte nicht erstellt werden. Bitte versuchen Sie später erneut.', 'themisdb-order-request')));
                return;
            }
            $_SESSION['themisdb_order_id'] = $order_id;
        } else {
            // Update existing order
            ThemisDB_Order_Manager::update_order($order_id, $data);
        }
        
        $next_step = $step + 1;
        if ($is_express && $step === 1) {
            $next_step = 4;
        }
        if ($next_step < 1) {
            $next_step = 1;
        }
        if ($next_step > 5) {
            $next_step = 5;
        }

        // Calculate total
        if (isset($data['product_edition']) || isset($data['modules']) || isset($data['training_modules'])) {
            $order = ThemisDB_Order_Manager::get_order($order_id);
            $total = ThemisDB_Order_Manager::calculate_total(
                $order['product_edition'],
                isset($data['modules']) ? $data['modules'] : ($order['modules'] ?: array()),
                isset($data['training_modules']) ? $data['training_modules'] : ($order['training_modules'] ?: array())
            );
            
            ThemisDB_Order_Manager::update_order($order_id, array('total_amount' => $total, 'step' => $next_step));
        } else {
            ThemisDB_Order_Manager::update_order($order_id, array('step' => $next_step));
        }
        
        wp_send_json_success(array(
            'order_id' => $order_id,
            'next_step' => $next_step
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

        $payment_method = isset($_POST['payment_method']) ? sanitize_key($_POST['payment_method']) : 'bank_transfer';
        if (!in_array($payment_method, array('bank_transfer', 'stripe', 'paypal'), true)) {
            $payment_method = 'bank_transfer';
        }
        
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
                'payment_method' => $payment_method,
            ));
            $order = ThemisDB_Order_Manager::get_order($order_id);
        }

        if (($order['payment_method'] ?? '') !== $payment_method) {
            ThemisDB_Order_Manager::update_order($order_id, array('payment_method' => $payment_method));
            $order = ThemisDB_Order_Manager::get_order($order_id);
        }

        // Apply B2B department custom pricing before status/payment processing.
        if (class_exists('ThemisDB_B2B_Portal')) {
            $b2b_pricing = ThemisDB_B2B_Portal::apply_custom_pricing_to_order($order_id, $order);
            if (!empty($b2b_pricing)) {
                $order = ThemisDB_Order_Manager::get_order($order_id);
            }
        }

        // Optional procurement metadata can be passed from a custom checkout extension.
        $purchase_order_number = isset($_POST['purchase_order_number']) ? sanitize_text_field($_POST['purchase_order_number']) : '';
        $billing_reference = isset($_POST['billing_reference']) ? sanitize_text_field($_POST['billing_reference']) : '';
        if (($purchase_order_number !== '' || $billing_reference !== '') && class_exists('ThemisDB_B2B_Portal')) {
            ThemisDB_B2B_Portal::save_procurement($order_id, array(
                'purchase_order_number' => $purchase_order_number,
                'billing_reference' => $billing_reference,
                'procurement_status' => 'submitted',
                'invoice_required' => 1,
                'invoice_status' => 'pending',
                'invoice_due_date' => date('Y-m-d', strtotime('+' . intval(get_option('themisdb_b2b_default_invoice_due_days', 30)) . ' days')),
            ));
        }
        
        // Update order status and stop immediately if the transition fails.
        $status_updated = ThemisDB_Order_Manager::set_order_status($order_id, 'pending');
        if (!$status_updated) {
            error_log('ThemisDB Order Submit Error: Failed to set order status to pending for order ID ' . $order_id);
            if (class_exists('ThemisDB_Error_Handler')) {
                ThemisDB_Error_Handler::log('error', 'Order submit failed: status transition to pending failed', array(
                    'order_id' => intval($order_id),
                    'payment_method' => $payment_method,
                ));
            }
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
        $created_payment_id = 0;
        $instant_methods = array('stripe', 'paypal');

        if ($order['product_type'] === 'merchandise') {
            $payment_data = array(
                'order_id' => $order_id,
                'amount' => $order['total_amount'],
                'currency' => $order['currency'],
                'payment_method' => $payment_method,
                'metadata' => array(
                    'source' => 'frontend_checkout',
                    'is_instant' => in_array($payment_method, $instant_methods, true),
                ),
            );
            $created_payment_id = intval(ThemisDB_Payment_Manager::create_payment($payment_data));

            ThemisDB_Order_Manager::reserve_inventory_for_order($order_id);

            try {
                ThemisDB_Email_Handler::send_invoice_email($order_id);
            } catch (Exception $e) {
                error_log('ThemisDB Invoice Email Error (Merchandise Submit): ' . $e->getMessage());
                if (class_exists('ThemisDB_Error_Handler')) {
                    ThemisDB_Error_Handler::log('warning', 'Invoice email failed in merchandise submit', array(
                        'order_id' => intval($order_id),
                        'exception' => $e->getMessage(),
                    ));
                }
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
                    if (class_exists('ThemisDB_Error_Handler')) {
                        ThemisDB_Error_Handler::log('warning', 'Invoice email failed in license submit', array(
                            'order_id' => intval($order_id),
                            'exception' => $e->getMessage(),
                        ));
                    }
                }

                $existing_payments = ThemisDB_Payment_Manager::get_payments_by_order($order_id);
                if (empty($existing_payments)) {
                    $payment_data = array(
                        'order_id' => $order_id,
                        'contract_id' => $contract_id,
                        'amount' => $order['total_amount'],
                        'currency' => $order['currency'],
                        'payment_method' => $payment_method,
                        'metadata' => array(
                            'source' => 'frontend_checkout',
                            'is_instant' => in_array($payment_method, $instant_methods, true),
                        ),
                    );
                    $created_payment_id = intval(ThemisDB_Payment_Manager::create_payment($payment_data));
                } else {
                    $created_payment_id = intval($existing_payments[0]['id']);
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

        // Instant methods are auto-verified for the current phase (full provider callbacks can be added via webhook handlers).
        if ($created_payment_id > 0 && in_array($payment_method, $instant_methods, true)) {
            ThemisDB_Payment_Manager::verify_payment($created_payment_id);
        }

        // Notify integrations (e.g., affiliate conversion tracking).
        do_action('themisdb_order_submitted', $order_id, $order);
        
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
        list($prices_by_edition, $features_by_type) = $this->get_license_pricing_snapshot($currency, $show_features);
        $prices = array_values($prices_by_edition);
        
        if (empty($prices)) {
            echo '<p>' . esc_html__('Keine Lizenzpreise verfügbar.', 'themisdb-order-request') . '</p>';
            return;
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
        $order_flow_url = $this->resolve_frontend_page_url(
            'themisdb_order_page_url',
            array('bestellung', 'checkout'),
            'themisdb_order_flow'
        );
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
                        <a href="<?php echo esc_url($order_flow_url); ?>" class="button button-primary">
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

    // ──────────────────────────────────────────────────────────────────
    //  PRODUCT DETAIL PAGE (Phase 2.2)
    // ──────────────────────────────────────────────────────────────────

    /**
     * Shortcode: [themisdb_product_detail]
     *
     * Attributes:
     *   edition            - pre-selected edition slug (default: '')
     *   order_url          - URL of the order flow page (default: get_permalink of page with [themisdb_order_flow])
     *   show_support_table - 'yes'|'no' (default: 'yes')
     *   currency           - currency symbol displayed (default: '€')
     */
    public function product_detail_shortcode($atts) {
        $atts = shortcode_atts(array(
            'edition'            => '',
            'order_url'          => '',
            'show_support_table' => 'yes',
            'currency'           => '€',
        ), $atts);

        $requested_edition = $this->get_requested_product_edition($atts['edition']);

        wp_enqueue_style(
            'themisdb-product-detail-style',
            THEMISDB_ORDER_PLUGIN_URL . 'assets/css/product-detail.css',
            array(),
            THEMISDB_ORDER_VERSION
        );
        wp_enqueue_script(
            'themisdb-product-selector',
            THEMISDB_ORDER_PLUGIN_URL . 'assets/js/product-selector.js',
            array('jquery'),
            THEMISDB_ORDER_VERSION,
            true
        );

        // Build data for JS.
        $products_raw  = ThemisDB_Order_Manager::get_products();
        $modules_raw   = ThemisDB_Order_Manager::get_modules();
        $trainings_raw = ThemisDB_Order_Manager::get_training_modules();

        $products_map = array();
        foreach ($products_raw as $p) {
            $products_map[sanitize_key($p['edition'])] = array(
                'name'  => esc_html($p['product_name']),
                'price' => floatval($p['price']),
            );
        }

        $modules_map = array();
        foreach ($modules_raw as $m) {
            $modules_map[sanitize_text_field($m['module_code'])] = array(
                'name'     => esc_html($m['module_name']),
                'price'    => floatval($m['price']),
                'category' => sanitize_key($m['module_category']),
            );
        }

        $trainings_map = array();
        foreach ($trainings_raw as $t) {
            $trainings_map[sanitize_text_field($t['training_code'])] = array(
                'name'    => esc_html($t['training_name']),
                'price'   => floatval($t['price']),
                'type'    => sanitize_key($t['training_type']),
                'hours'   => intval($t['duration_hours']),
            );
        }

        $default_modules = $this->get_requested_code_list(array('modules', 'module'), array_keys($modules_map));
        $default_training = $this->get_requested_code_list(array('training', 'trainings'), array_keys($trainings_map));

        // Fallback order URL: use a page with [themisdb_order_flow] if none given.
        $order_url = esc_url_raw($atts['order_url']);
        if ($order_url === '') {
            $order_url = $this->resolve_frontend_page_url(
                'themisdb_order_page_url',
                array('bestellung', 'checkout'),
                'themisdb_order_flow'
            );
        }

        wp_localize_script('themisdb-product-selector', 'themisdbProductSelector', array(
            'products'       => $products_map,
            'modules'        => $modules_map,
            'trainings'      => $trainings_map,
            'defaultEdition' => $requested_edition,
            'defaultModules' => $default_modules,
            'defaultTraining' => $default_training,
            'orderUrl'       => $order_url,
            'currency'       => sanitize_text_field($atts['currency']),
            'i18n'           => array(
                'free'        => __('Kostenlos', 'themisdb-order-request'),
                'showSupport' => __('Support-Details anzeigen', 'themisdb-order-request'),
                'hideSupport' => __('Support-Details ausblenden', 'themisdb-order-request'),
            ),
        ));

        ob_start();
        $this->render_product_detail(
            $products_raw,
            $modules_raw,
            $trainings_raw,
            $requested_edition,
            $order_url,
            $atts['show_support_table'] === 'yes'
        );
        return ob_get_clean();
    }

    /**
     * Render the product detail page HTML.
     *
     * @param array  $products
     * @param array  $modules
     * @param array  $trainings
     * @param string $default_edition
     * @param string $order_url
     * @param bool   $show_support
     */
    private function render_product_detail($products, $modules, $trainings, $default_edition, $order_url, $show_support) {
        if (empty($products)) {
            echo '<p class="themisdb-notice">' . esc_html__('Keine Produkte verfügbar.', 'themisdb-order-request') . '</p>';
            return;
        }

        // Determine pre-selected edition.
        if ($default_edition === '') {
            $default_edition = !empty($products[0]['edition']) ? sanitize_key($products[0]['edition']) : '';
        }

        ?>
        <div class="themisdb-product-detail">
            <div class="tpd-layout">

                <!-- Left: configurator -->
                <div class="tpd-configurator">

                    <!-- 1. Edition selector -->
                    <div class="tpd-section" id="edition">
                        <h2 class="tpd-section-title"><?php esc_html_e('Edition wählen', 'themisdb-order-request'); ?></h2>
                        <p class="tpd-section-subtitle"><?php esc_html_e('Wählen Sie die Edition, die am besten zu Ihren Anforderungen passt.', 'themisdb-order-request'); ?></p>
                        <div class="tpd-edition-grid">
                            <?php foreach ($products as $product) :
                                $edition = sanitize_key($product['edition']);
                                $is_selected = $edition === $default_edition;
                            ?>
                            <div class="tpd-edition-card<?php echo $is_selected ? ' tpd-edition-card--selected' : ''; ?>" data-edition="<?php echo esc_attr($edition); ?>">
                                <input type="radio" name="tpd_edition" value="<?php echo esc_attr($edition); ?>"<?php echo $is_selected ? ' checked' : ''; ?>>
                                <?php if ($edition === 'enterprise') : ?>
                                    <span class="tpd-edition-badge"><?php esc_html_e('Beliebt', 'themisdb-order-request'); ?></span>
                                <?php endif; ?>
                                <p class="tpd-edition-name"><?php echo esc_html($product['product_name']); ?></p>
                                <p class="tpd-edition-desc"><?php echo esc_html($product['description'] ?? ''); ?></p>
                                <?php if (floatval($product['price']) > 0) : ?>
                                    <p class="tpd-edition-price"><?php echo esc_html(number_format(floatval($product['price']), 2, ',', '.') . ' €'); ?></p>
                                <?php else : ?>
                                    <p class="tpd-edition-price tpd-edition-price--free"><?php esc_html_e('Kostenlos', 'themisdb-order-request'); ?></p>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- 2. Module selector -->
                    <?php if (!empty($modules)) : ?>
                    <div class="tpd-section" id="modules">
                        <h2 class="tpd-section-title"><?php esc_html_e('Module', 'themisdb-order-request'); ?></h2>
                        <p class="tpd-section-subtitle"><?php esc_html_e('Optionale Erweiterungen für Ihre ThemisDB-Installation.', 'themisdb-order-request'); ?></p>
                        <?php
                        $grouped_modules = array();
                        foreach ($modules as $m) {
                            $grouped_modules[$m['module_category']][] = $m;
                        }
                        foreach ($grouped_modules as $cat => $cat_modules) : ?>
                        <div class="tpd-category-group">
                            <p class="tpd-category-title"><?php echo esc_html(ucfirst($cat)); ?></p>
                            <div class="tpd-item-grid">
                                <?php foreach ($cat_modules as $mod) : ?>
                                <div class="tpd-module-item" data-code="<?php echo esc_attr($mod['module_code']); ?>">
                                    <label class="tpd-item-label">
                                        <input type="checkbox" class="tpd-module-check" value="<?php echo esc_attr($mod['module_code']); ?>">
                                        <div class="tpd-item-info">
                                            <p class="tpd-item-name"><?php echo esc_html($mod['module_name']); ?></p>
                                            <p class="tpd-item-desc"><?php echo esc_html($mod['description'] ?? ''); ?></p>
                                            <div class="tpd-item-meta">
                                                <?php if (floatval($mod['price']) > 0) : ?>
                                                    <span class="tpd-item-price">+ <?php echo esc_html(number_format(floatval($mod['price']), 2, ',', '.') . ' €'); ?></span>
                                                <?php else : ?>
                                                    <span class="tpd-item-price tpd-item-price--free"><?php esc_html_e('Inklusive', 'themisdb-order-request'); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- 3. Training selector -->
                    <?php if (!empty($trainings)) : ?>
                    <div class="tpd-section" id="training">
                        <h2 class="tpd-section-title"><?php esc_html_e('Schulungen', 'themisdb-order-request'); ?></h2>
                        <p class="tpd-section-subtitle"><?php esc_html_e('Professionelle Schulungen für Ihr Team.', 'themisdb-order-request'); ?></p>
                        <?php
                        $grouped_trainings = array();
                        foreach ($trainings as $t) {
                            $grouped_trainings[$t['training_type']][] = $t;
                        }
                        foreach ($grouped_trainings as $type => $type_trainings) : ?>
                        <div class="tpd-category-group">
                            <p class="tpd-category-title"><?php echo esc_html(ucfirst($type)); ?></p>
                            <div class="tpd-item-grid">
                                <?php foreach ($type_trainings as $train) : ?>
                                <div class="tpd-training-item" data-code="<?php echo esc_attr($train['training_code']); ?>">
                                    <label class="tpd-item-label">
                                        <input type="checkbox" class="tpd-training-check" value="<?php echo esc_attr($train['training_code']); ?>">
                                        <div class="tpd-item-info">
                                            <p class="tpd-item-name"><?php echo esc_html($train['training_name']); ?></p>
                                            <p class="tpd-item-desc"><?php echo esc_html($train['description'] ?? ''); ?></p>
                                            <div class="tpd-item-meta">
                                                <span class="tpd-item-price">+ <?php echo esc_html(number_format(floatval($train['price']), 2, ',', '.') . ' €'); ?></span>
                                                <?php if (!empty($train['duration_hours'])) : ?>
                                                    <span class="tpd-item-duration"><?php echo esc_html(absint($train['duration_hours'])); ?> <?php esc_html_e('Std.', 'themisdb-order-request'); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- 4. Support comparison table -->
                    <?php if ($show_support) : ?>
                    <div class="tpd-section">
                        <h2 class="tpd-section-title"><?php esc_html_e('Support-Level', 'themisdb-order-request'); ?></h2>
                        <p class="tpd-section-subtitle"><?php esc_html_e('Vergleichen Sie den enthaltenen Support je Edition.', 'themisdb-order-request'); ?></p>
                        <button type="button" class="tpd-support-toggle"><?php esc_html_e('Support-Details anzeigen', 'themisdb-order-request'); ?></button>
                        <table class="tpd-support-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Merkmal', 'themisdb-order-request'); ?></th>
                                    <th><?php esc_html_e('Community', 'themisdb-order-request'); ?></th>
                                    <th><?php esc_html_e('Enterprise', 'themisdb-order-request'); ?></th>
                                    <th><?php esc_html_e('Hyperscaler', 'themisdb-order-request'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $support_rows = apply_filters('themisdb_product_detail_support_rows', array(
                                    array(
                                        'label'       => __('Community Forum', 'themisdb-order-request'),
                                        'community'   => true,
                                        'enterprise'  => true,
                                        'hyperscaler' => true,
                                    ),
                                    array(
                                        'label'       => __('E-Mail Support', 'themisdb-order-request'),
                                        'community'   => false,
                                        'enterprise'  => true,
                                        'hyperscaler' => true,
                                    ),
                                    array(
                                        'label'       => __('Telefon-/Chat-Support', 'themisdb-order-request'),
                                        'community'   => false,
                                        'enterprise'  => false,
                                        'hyperscaler' => true,
                                    ),
                                    array(
                                        'label'       => __('SLA Reaktionszeit', 'themisdb-order-request'),
                                        'community'   => __('&ndash;', 'themisdb-order-request'),
                                        'enterprise'  => __('8&nbsp;h', 'themisdb-order-request'),
                                        'hyperscaler' => __('2&nbsp;h', 'themisdb-order-request'),
                                    ),
                                    array(
                                        'label'       => __('Dedizierter Account-Manager', 'themisdb-order-request'),
                                        'community'   => false,
                                        'enterprise'  => false,
                                        'hyperscaler' => true,
                                    ),
                                    array(
                                        'label'       => __('Security-Patches (priorisiert)', 'themisdb-order-request'),
                                        'community'   => false,
                                        'enterprise'  => true,
                                        'hyperscaler' => true,
                                    ),
                                    array(
                                        'label'       => __('Onboarding-Session', 'themisdb-order-request'),
                                        'community'   => false,
                                        'enterprise'  => true,
                                        'hyperscaler' => true,
                                    ),
                                ));

                                foreach ($support_rows as $row) :
                                ?>
                                <tr>
                                    <td><?php echo wp_kses_post($row['label']); ?></td>
                                    <?php foreach (array('community', 'enterprise', 'hyperscaler') as $col) :
                                        $val = $row[$col];
                                        if ($val === true) :
                                    ?><td class="tpd-check">&#10003;</td><?php
                                        elseif ($val === false) :
                                    ?><td class="tpd-dash">&ndash;</td><?php
                                        else :
                                    ?><td><?php echo wp_kses_post((string) $val); ?></td><?php
                                        endif;
                                    endforeach; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>

                </div><!-- /tpd-configurator -->

                <!-- Right: sticky pricing sidebar -->
                <div class="tpd-pricing-sidebar">
                    <div class="tpd-pricing-box">
                        <p class="tpd-pricing-box-title"><?php esc_html_e('Ihre Konfiguration', 'themisdb-order-request'); ?></p>
                        <p class="tpd-base-price-line"><?php esc_html_e('Basispreis:', 'themisdb-order-request'); ?> <span class="tpd-base-price">&ndash;</span></p>
                        <ul class="tpd-price-summary"></ul>
                        <div class="tpd-total-line">
                            <span class="tpd-total-label"><?php esc_html_e('Gesamt', 'themisdb-order-request'); ?></span>
                            <span class="tpd-total-amount">0,00&nbsp;€</span>
                        </div>
                        <span class="tpd-total-note"><?php esc_html_e('Netto, zzgl. gesetzl. MwSt.', 'themisdb-order-request'); ?></span>
                        <a href="<?php echo esc_url($order_url); ?>" class="tpd-order-btn">
                            <?php esc_html_e('Jetzt bestellen', 'themisdb-order-request'); ?>
                        </a>
                    </div>
                </div>

            </div><!-- /tpd-layout -->
        </div><!-- /themisdb-product-detail -->
        <?php
    }

    /**
     * Shortcode: [themisdb_shop]
     *
     * Attributes:
     *   order_url        - URL of the order flow page (default: option themisdb_order_page_url)
     *   product_url      - URL of the detail/configurator page (default: option themisdb_product_page_url or order_url)
     *   preferred_edition - Preferred edition slug for CTA/deep-link recommendations
     *   sales_email      - Contact email for commercial inquiries
     *   training_email   - Contact email for training inquiries
     *   enterprise_email - Contact email for enterprise inquiries
     */
    public function shop_shortcode($atts) {
        $atts = shortcode_atts(array(
            'order_url' => '',
            'product_url' => '',
            'preferred_edition' => '',
            'currency' => 'EUR',
            'show_features' => 'yes',
            'sales_email' => 'sales@themisdb.org',
            'training_email' => 'training@themisdb.org',
            'enterprise_email' => 'enterprise@themisdb.org',
        ), $atts);

        $order_url = esc_url_raw($atts['order_url']);
        if ($order_url === '') {
            $order_url = $this->resolve_frontend_page_url(
                'themisdb_order_page_url',
                array('bestellung', 'checkout'),
                'themisdb_order_flow'
            );
        }

        $product_url = esc_url_raw($atts['product_url']);
        if ($product_url === '') {
            $product_url = $this->resolve_frontend_page_url(
                'themisdb_product_page_url',
                array('produkte', 'produkt', 'konfigurator'),
                'themisdb_product_detail'
            );
            if ($product_url === '') {
                $product_url = $order_url;
            }
        }

        $show_features = sanitize_text_field($atts['show_features']) === 'yes';
        list($license_prices, $license_features) = $this->get_license_pricing_snapshot(
            sanitize_text_field($atts['currency']),
            $show_features
        );

        $products = ThemisDB_Order_Manager::get_products();
        $modules = ThemisDB_Order_Manager::get_modules();
        $trainings = ThemisDB_Order_Manager::get_training_modules();

        ob_start();
        $this->render_shop_page(
            $products,
            $modules,
            $trainings,
            $license_prices,
            $license_features,
            $show_features,
            sanitize_key($atts['preferred_edition']),
            $order_url,
            $product_url,
            sanitize_email($atts['sales_email']),
            sanitize_email($atts['training_email']),
            sanitize_email($atts['enterprise_email'])
        );
        return ob_get_clean();
    }

    /**
     * Render the shop page from live order plugin catalog data.
     *
     * @param array  $products
     * @param array  $modules
     * @param array  $trainings
     * @param array  $license_prices
     * @param array  $license_features
     * @param bool   $show_features
    * @param string $preferred_edition
     * @param string $order_url
     * @param string $product_url
     * @param string $sales_email
     * @param string $training_email
     * @param string $enterprise_email
     */
    private function render_shop_page($products, $modules, $trainings, $license_prices, $license_features, $show_features, $preferred_edition, $order_url, $product_url, $sales_email, $training_email, $enterprise_email) {
        $has_distinct_configurator_target = !$this->urls_point_to_same_location($product_url, $order_url);

        $module_groups = array();
        foreach ($modules as $module) {
            $category = sanitize_key($module['module_category'] ?? 'general');
            if ($category === '') {
                $category = 'general';
            }

            if (!isset($module_groups[$category])) {
                $module_groups[$category] = array();
            }

            $module_groups[$category][] = $module;
        }

        $training_groups = array();
        foreach ($trainings as $training) {
            $type = sanitize_key($training['training_type'] ?? 'training');
            if ($type === '') {
                $type = 'training';
            }

            if (!isset($training_groups[$type])) {
                $training_groups[$type] = array();
            }

            $training_groups[$type][] = $training;
        }

        $faq_items = apply_filters('themisdb_shop_faq_items', array(
            array(
                'question' => __('Kann ich mit einer Edition starten und später wechseln?', 'themisdb-order-request'),
                'answer' => __('Ja. Editionen, Module und Schulungen werden zentral im Order-Plugin verwaltet und können jederzeit angepasst werden.', 'themisdb-order-request'),
            ),
            array(
                'question' => __('Woher kommen Preise und Angebotsinhalte?', 'themisdb-order-request'),
                'answer' => __('Die Shop-Seite liest Produkte, Module und Schulungen direkt aus den Tabellen des Order-Plugins und ergänzt Editionen mit aktiven Lizenzpreisen und Features.', 'themisdb-order-request'),
            ),
            array(
                'question' => __('Wie bestelle ich Add-ons und Trainings?', 'themisdb-order-request'),
                'answer' => __('Produkte führen in den Bestellfluss. Module und Schulungen können über den Produktkonfigurator oder über die Kontaktwege angefragt werden.', 'themisdb-order-request'),
            ),
        ));

        $preferred_edition = sanitize_key($preferred_edition);
        $primary_edition = $this->resolve_shop_recommended_edition($products, $license_prices, 'default', '', $preferred_edition);
        $primary_product = null;
        foreach ($products as $candidate_product) {
            if (sanitize_key($candidate_product['edition'] ?? '') === $primary_edition) {
                $primary_product = $candidate_product;
                break;
            }
        }
        if ($primary_product === null && !empty($products)) {
            $primary_product = reset($products);
        }

        $primary_order_link = $primary_edition !== '' ? add_query_arg(array('product' => $primary_edition), $order_url) : $order_url;
        $primary_configurator_link = $primary_edition !== '' ? add_query_arg(array('edition' => $primary_edition), $product_url) : $product_url;
        $primary_label = $primary_product['product_name'] ?? __('Ihre Edition', 'themisdb-order-request');

        ?>
        <div class="themisdb-shop-page">
            <style>
                .themisdb-shop-page {
                    --tds-navy: #0b1e3d;
                    --tds-blue: #1e6fba;
                    --tds-cyan: #1ab5c8;
                    --tds-panel: #ffffff;
                    --tds-border: rgba(11, 30, 61, 0.1);
                    --tds-text: #10233f;
                    --tds-text-soft: #5d6f84;
                    --tds-shadow: 0 18px 38px rgba(11, 30, 61, 0.12);
                    color: var(--tds-text);
                }
                .themisdb-shop-page a { text-decoration: none; }
                .tds-hero {
                    background: linear-gradient(160deg, var(--tds-navy) 0%, var(--tds-blue) 60%, var(--tds-cyan) 100%);
                    padding: 4rem 2rem;
                    text-align: center;
                    border-radius: 22px;
                    margin-bottom: 2rem;
                    color: #fff;
                }
                .tds-hero p { max-width: 760px; margin: 0 auto; color: rgba(255,255,255,0.88); }
                .tds-eyebrow {
                    color: #c5f7fb;
                    text-transform: uppercase;
                    letter-spacing: 0.12em;
                    font-size: 0.8rem;
                    font-weight: 700;
                    margin-bottom: 0.75rem;
                }
                .tds-nav {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 0.65rem;
                    margin: 0 0 2rem;
                }
                .tds-chip, .tds-btn {
                    border-radius: 999px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: 700;
                    transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
                }
                .tds-chip {
                    padding: 0.6rem 1rem;
                    border: 1px solid var(--tds-border);
                    background: #fff;
                    color: var(--tds-text);
                }
                .tds-btn {
                    padding: 0.8rem 1.05rem;
                    border: 1px solid transparent;
                }
                .tds-btn:hover, .tds-chip:hover { transform: translateY(-1px); }
                .tds-btn-primary {
                    background: linear-gradient(135deg, var(--tds-blue), var(--tds-cyan));
                    color: #fff;
                    box-shadow: 0 12px 24px rgba(30, 111, 186, 0.22);
                }
                .tds-btn-secondary {
                    background: #fff;
                    border-color: var(--tds-border);
                    color: var(--tds-text);
                }
                .tds-section { margin-bottom: 3rem; }
                .tds-section-head { margin-bottom: 1rem; }
                .tds-section-head h2 { margin: 0 0 0.5rem; }
                .tds-section-head p { margin: 0; color: var(--tds-text-soft); }
                .tds-product-grid, .tds-info-grid, .tds-contact-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
                    gap: 1.25rem;
                }
                .tds-card {
                    background: var(--tds-panel);
                    border: 1px solid var(--tds-border);
                    border-radius: 18px;
                    padding: 1.4rem;
                    box-shadow: var(--tds-shadow);
                    display: flex;
                    flex-direction: column;
                    min-height: 100%;
                }
                .tds-card--featured {
                    background: linear-gradient(160deg, var(--tds-navy) 0%, #173a6b 100%);
                    color: #fff;
                    border-color: rgba(255,255,255,0.12);
                }
                .tds-card--featured .tds-meta,
                .tds-card--featured .tds-description,
                .tds-card--featured .tds-list,
                .tds-card--featured .tds-muted { color: rgba(255,255,255,0.84); }
                .tds-meta {
                    margin: 0 0 0.5rem;
                    font-size: 0.82rem;
                    font-weight: 700;
                    letter-spacing: 0.08em;
                    text-transform: uppercase;
                    color: var(--tds-text-soft);
                }
                .tds-description, .tds-muted { color: var(--tds-text-soft); }
                .tds-price {
                    margin: 0.25rem 0 0.9rem;
                    font-size: 1.9rem;
                    font-weight: 800;
                    color: var(--tds-blue);
                }
                .tds-card--featured .tds-price { color: #8de7f0; }
                .tds-list {
                    margin: 0 0 1rem 1rem;
                    padding: 0;
                    display: flex;
                    flex-direction: column;
                    gap: 0.4rem;
                }
                .tds-actions {
                    margin-top: auto;
                    display: flex;
                    gap: 0.65rem;
                    flex-wrap: wrap;
                }
                .tds-info-group {
                    background: #fff;
                    border: 1px solid var(--tds-border);
                    border-radius: 18px;
                    padding: 1.25rem;
                }
                .tds-info-group h3 { margin-top: 0; }
                .tds-info-group ul {
                    margin: 0;
                    padding-left: 1rem;
                    display: flex;
                    flex-direction: column;
                    gap: 0.45rem;
                }
                .tds-faq details {
                    background: #fff;
                    border: 1px solid var(--tds-border);
                    border-radius: 14px;
                    padding: 1rem 1.2rem;
                    margin-bottom: 0.8rem;
                }
                .tds-faq summary {
                    cursor: pointer;
                    font-weight: 700;
                    color: var(--tds-text);
                }
                .tds-contact-card {
                    background: #fff;
                    border: 1px solid var(--tds-border);
                    border-radius: 18px;
                    padding: 1.2rem;
                }
                .tds-contact-card p { margin: 0 0 0.9rem; color: var(--tds-text-soft); }
                .tds-inline-link {
                    display: inline-block;
                    margin-left: 0.5rem;
                    font-size: 0.88rem;
                    color: var(--tds-blue);
                    font-weight: 700;
                }
                .tds-inline-link--checkout {
                    color: var(--tds-navy);
                }
                .tds-cta {
                    background: linear-gradient(155deg, var(--tds-navy) 0%, #173a6b 100%);
                    border-radius: 22px;
                    padding: 2rem;
                    color: #fff;
                }
                .tds-cta p { color: rgba(255,255,255,0.84); }
                .tds-cta .tds-actions { margin-top: 1rem; }
                @media (max-width: 640px) {
                    .tds-hero { padding: 2.8rem 1.25rem; }
                    .tds-card, .tds-info-group, .tds-contact-card { padding: 1.1rem; }
                }
            </style>

            <section class="tds-hero">
                <p class="tds-eyebrow"><?php esc_html_e('Live aus dem Order-Plugin', 'themisdb-order-request'); ?></p>
                <h1><?php esc_html_e('ThemisDB Shop', 'themisdb-order-request'); ?></h1>
                <p><?php esc_html_e('Diese Shop-Seite liest Produkte, Module und Schulungen direkt aus dem gepflegten Katalog des Order-Plugins.', 'themisdb-order-request'); ?></p>
            </section>

            <nav class="tds-nav" aria-label="<?php esc_attr_e('Shop-Bereiche', 'themisdb-order-request'); ?>">
                <a href="#products" class="tds-chip"><?php esc_html_e('Produkte', 'themisdb-order-request'); ?></a>
                <a href="#modules" class="tds-chip"><?php esc_html_e('Module', 'themisdb-order-request'); ?></a>
                <a href="#training" class="tds-chip"><?php esc_html_e('Schulungen', 'themisdb-order-request'); ?></a>
                <a href="#faq" class="tds-chip"><?php esc_html_e('FAQ', 'themisdb-order-request'); ?></a>
                <a href="#contact" class="tds-chip"><?php esc_html_e('Kontakt', 'themisdb-order-request'); ?></a>
            </nav>

            <section id="products" class="tds-section">
                <div class="tds-section-head">
                    <p class="tds-eyebrow"><?php esc_html_e('Editionen', 'themisdb-order-request'); ?></p>
                    <h2><?php esc_html_e('Produkte aus dem Katalog', 'themisdb-order-request'); ?></h2>
                    <p><?php esc_html_e('Diese Karten werden direkt aus themisdb_products gerendert.', 'themisdb-order-request'); ?></p>
                </div>
                <?php if (empty($products)) : ?>
                    <p class="tds-muted"><?php esc_html_e('Aktuell sind keine aktiven Produkte hinterlegt.', 'themisdb-order-request'); ?></p>
                <?php else : ?>
                    <div class="tds-product-grid">
                        <?php foreach ($products as $product) :
                            $edition = sanitize_key($product['edition'] ?? '');
                            $is_featured = $edition === 'enterprise';
                            $order_link = $order_url;
                            $configurator_link = $product_url;
                            $license_price = $edition !== '' && isset($license_prices[$edition]) ? $license_prices[$edition] : null;
                            $feature_items = $edition !== '' && isset($license_features[$edition]) ? $license_features[$edition] : array();

                            if ($edition !== '') {
                                $order_link = add_query_arg(array('product' => $edition), $order_url);
                                $configurator_link = add_query_arg(array('edition' => $edition), $product_url);
                            }
                        ?>
                            <article class="tds-card<?php echo $is_featured ? ' tds-card--featured' : ''; ?>" id="product-<?php echo esc_attr($edition !== '' ? $edition : 'item-' . intval($product['id'] ?? 0)); ?>">
                                <p class="tds-meta"><?php echo esc_html($product['product_type'] ?: __('Produkt', 'themisdb-order-request')); ?></p>
                                <h3><?php echo esc_html($product['product_name'] ?: $edition); ?></h3>
                                <p class="tds-description"><?php echo esc_html($product['description'] ?? ''); ?></p>
                                <div class="tds-price"><?php echo esc_html($this->format_shop_price($license_price['base_price'] ?? ($product['price'] ?? 0), $license_price['currency'] ?? ($product['currency'] ?? 'EUR'))); ?></div>
                                <ul class="tds-list">
                                    <li><?php echo esc_html(sprintf(__('Edition: %s', 'themisdb-order-request'), $edition !== '' ? $edition : __('ohne Kennung', 'themisdb-order-request'))); ?></li>
                                    <li><?php echo esc_html(sprintf(__('Code: %s', 'themisdb-order-request'), $product['product_code'] ?? '')); ?></li>
                                    <li><?php echo esc_html(sprintf(__('Währung: %s', 'themisdb-order-request'), $license_price['currency'] ?? ($product['currency'] ?? 'EUR'))); ?></li>
                                    <?php if ($license_price) : ?>
                                    <li><?php echo esc_html(sprintf(__('Knoten: %s', 'themisdb-order-request'), $this->format_shop_limit($license_price['max_nodes'] ?? null))); ?></li>
                                    <li><?php echo esc_html(sprintf(__('CPU-Cores: %s', 'themisdb-order-request'), $this->format_shop_limit($license_price['max_cores'] ?? null))); ?></li>
                                    <li><?php echo esc_html(sprintf(__('Speicher: %s', 'themisdb-order-request'), $this->format_shop_limit($license_price['max_storage_gb'] ?? null, 'GB'))); ?></li>
                                    <?php endif; ?>
                                </ul>
                                <?php if ($show_features && !empty($feature_items)) : ?>
                                    <div class="tds-info-group" style="margin:0 0 1rem; padding:1rem; box-shadow:none;">
                                        <h4 style="margin:0 0 .6rem;"><?php esc_html_e('Enthaltene Features', 'themisdb-order-request'); ?></h4>
                                        <ul>
                                            <?php foreach (array_slice($feature_items, 0, 4) as $feature_item) : ?>
                                                <li><?php echo esc_html($feature_item['feature_name'] ?? ''); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                <div class="tds-actions">
                                    <a href="<?php echo esc_url($order_link); ?>" class="tds-btn tds-btn-primary"><?php esc_html_e('Bestellfluss öffnen', 'themisdb-order-request'); ?></a>
                                    <a href="<?php echo esc_url($configurator_link); ?>" class="tds-btn tds-btn-secondary"><?php esc_html_e('Konfigurator', 'themisdb-order-request'); ?></a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section id="modules" class="tds-section">
                <div class="tds-section-head">
                    <p class="tds-eyebrow"><?php esc_html_e('Add-ons', 'themisdb-order-request'); ?></p>
                    <h2><?php esc_html_e('Module und Services', 'themisdb-order-request'); ?></h2>
                    <p><?php esc_html_e('Die Kategorien und Preise kommen live aus themisdb_modules.', 'themisdb-order-request'); ?></p>
                </div>
                <?php if (empty($module_groups)) : ?>
                    <p class="tds-muted"><?php esc_html_e('Aktuell sind keine aktiven Module hinterlegt.', 'themisdb-order-request'); ?></p>
                <?php else : ?>
                    <div class="tds-info-grid">
                        <?php foreach ($module_groups as $category => $items) : ?>
                            <?php
                            $module_edition = $this->resolve_shop_recommended_edition($products, $license_prices, 'module', $category, $preferred_edition);
                            $module_configurator_url = $product_url;
                            if ($module_edition !== '') {
                                $module_configurator_url = add_query_arg(array('edition' => $module_edition), $product_url);
                            }
                            if ($has_distinct_configurator_target) {
                                $module_configurator_url .= '#modules';
                            }
                            ?>
                            <section class="tds-info-group" id="module-<?php echo esc_attr($category); ?>">
                                <h3><?php echo esc_html($this->humanize_shop_key($category)); ?></h3>
                                <ul>
                                    <?php foreach ($items as $item) : ?>
                                        <li>
                                            <strong><?php echo esc_html($item['module_name'] ?? ''); ?></strong>
                                            <?php echo esc_html(' - ' . $this->format_shop_price($item['price'] ?? 0, $item['currency'] ?? 'EUR')); ?>
                                            <?php if ($module_edition !== '') : ?>
                                                <a class="tds-inline-link" href="<?php echo esc_url(add_query_arg(array('modules' => $item['module_code']), $module_configurator_url)); ?>"><?php esc_html_e('Im Konfigurator öffnen', 'themisdb-order-request'); ?></a>
                                                <a class="tds-inline-link tds-inline-link--checkout" href="<?php echo esc_url(add_query_arg(array('edition' => $module_edition, 'modules' => $item['module_code'], 'checkout' => 1), $order_url)); ?>"><?php esc_html_e('Direkt zum Checkout', 'themisdb-order-request'); ?></a>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </section>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section id="training" class="tds-section">
                <div class="tds-section-head">
                    <p class="tds-eyebrow"><?php esc_html_e('Enablement', 'themisdb-order-request'); ?></p>
                    <h2><?php esc_html_e('Schulungen aus dem Katalog', 'themisdb-order-request'); ?></h2>
                    <p><?php esc_html_e('Trainings werden direkt aus themisdb_training_modules gelesen.', 'themisdb-order-request'); ?></p>
                </div>
                <?php if (empty($training_groups)) : ?>
                    <p class="tds-muted"><?php esc_html_e('Aktuell sind keine aktiven Schulungen hinterlegt.', 'themisdb-order-request'); ?></p>
                <?php else : ?>
                    <div class="tds-info-grid">
                        <?php foreach ($training_groups as $type => $items) : ?>
                            <?php
                            $training_edition = $this->resolve_shop_recommended_edition($products, $license_prices, 'training', $type, $preferred_edition);
                            $training_configurator_url = $product_url;
                            if ($training_edition !== '') {
                                $training_configurator_url = add_query_arg(array('edition' => $training_edition), $product_url);
                            }
                            if ($has_distinct_configurator_target) {
                                $training_configurator_url .= '#training';
                            }
                            ?>
                            <section class="tds-info-group" id="training-<?php echo esc_attr($type); ?>">
                                <h3><?php echo esc_html($this->humanize_shop_key($type)); ?></h3>
                                <ul>
                                    <?php foreach ($items as $item) :
                                        $duration = !empty($item['duration_hours']) ? sprintf(__(' (%d Std.)', 'themisdb-order-request'), absint($item['duration_hours'])) : '';
                                    ?>
                                        <li>
                                            <strong><?php echo esc_html($item['training_name'] ?? ''); ?></strong>
                                            <?php echo esc_html($duration . ' - ' . $this->format_shop_price($item['price'] ?? 0, $item['currency'] ?? 'EUR')); ?>
                                            <?php if ($training_edition !== '') : ?>
                                                <a class="tds-inline-link" href="<?php echo esc_url(add_query_arg(array('training' => $item['training_code']), $training_configurator_url)); ?>"><?php esc_html_e('Im Konfigurator öffnen', 'themisdb-order-request'); ?></a>
                                                <a class="tds-inline-link tds-inline-link--checkout" href="<?php echo esc_url(add_query_arg(array('edition' => $training_edition, 'training' => $item['training_code'], 'checkout' => 1), $order_url)); ?>"><?php esc_html_e('Direkt zum Checkout', 'themisdb-order-request'); ?></a>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </section>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section id="faq" class="tds-section tds-faq">
                <div class="tds-section-head">
                    <p class="tds-eyebrow"><?php esc_html_e('FAQ', 'themisdb-order-request'); ?></p>
                    <h2><?php esc_html_e('Fragen zum Shop', 'themisdb-order-request'); ?></h2>
                </div>
                <?php foreach ($faq_items as $faq_item) : ?>
                    <details>
                        <summary><?php echo esc_html($faq_item['question'] ?? ''); ?></summary>
                        <p class="tds-muted"><?php echo esc_html($faq_item['answer'] ?? ''); ?></p>
                    </details>
                <?php endforeach; ?>
            </section>

            <section id="contact" class="tds-section">
                <div class="tds-section-head">
                    <p class="tds-eyebrow"><?php esc_html_e('Kontakt', 'themisdb-order-request'); ?></p>
                    <h2><?php esc_html_e('Shop-Anfragen und Angebot', 'themisdb-order-request'); ?></h2>
                    <p><?php esc_html_e('Die Kontaktkarten bleiben bewusst einfach, damit Produkte und Preise vollständig aus dem Plugin kommen und Kontakte separat gepflegt werden können.', 'themisdb-order-request'); ?></p>
                </div>
                <div class="tds-contact-grid">
                    <section class="tds-contact-card">
                        <h3><?php esc_html_e('Vertrieb', 'themisdb-order-request'); ?></h3>
                        <p><?php esc_html_e('Allgemeine Shop-, Lizenz- und Bestellanfragen.', 'themisdb-order-request'); ?></p>
                        <a href="mailto:<?php echo antispambot(esc_attr($sales_email)); ?>" class="tds-btn tds-btn-primary"><?php echo esc_html($sales_email); ?></a>
                    </section>
                    <section class="tds-contact-card">
                        <h3><?php esc_html_e('Schulungen', 'themisdb-order-request'); ?></h3>
                        <p><?php esc_html_e('Onboarding, Workshops und Team-Enablement.', 'themisdb-order-request'); ?></p>
                        <a href="mailto:<?php echo antispambot(esc_attr($training_email)); ?>" class="tds-btn tds-btn-secondary"><?php echo esc_html($training_email); ?></a>
                    </section>
                    <section class="tds-contact-card">
                        <h3><?php esc_html_e('Enterprise', 'themisdb-order-request'); ?></h3>
                        <p><?php esc_html_e('Dedizierte Architektur-, Compliance- und Air-Gap-Anforderungen.', 'themisdb-order-request'); ?></p>
                        <a href="mailto:<?php echo antispambot(esc_attr($enterprise_email)); ?>" class="tds-btn tds-btn-secondary"><?php echo esc_html($enterprise_email); ?></a>
                    </section>
                </div>
            </section>

            <section class="tds-cta">
                <p class="tds-eyebrow"><?php esc_html_e('Nächster Schritt', 'themisdb-order-request'); ?></p>
                <h2><?php echo esc_html(sprintf(__('Direkt mit %s starten', 'themisdb-order-request'), $primary_label)); ?></h2>
                <p><?php esc_html_e('Wenn Preise oder Module im Admin geändert werden, aktualisiert sich dieser Shop automatisch beim nächsten Seitenaufruf.', 'themisdb-order-request'); ?></p>
                <div class="tds-actions">
                    <a href="<?php echo esc_url($primary_order_link); ?>" class="tds-btn tds-btn-primary"><?php esc_html_e('Edition direkt bestellen', 'themisdb-order-request'); ?></a>
                    <a href="<?php echo esc_url($primary_configurator_link); ?>" class="tds-btn tds-btn-secondary"><?php esc_html_e('Edition konfigurieren', 'themisdb-order-request'); ?></a>
                </div>
            </section>
        </div>
        <?php
    }

    /**
     * Load the active license pricing snapshot keyed by edition.
     *
     * @param string $currency
     * @param bool   $include_features
     * @return array
     */
    private function get_license_pricing_snapshot($currency = 'EUR', $include_features = true) {
        global $wpdb;

        $currency = strtoupper(sanitize_text_field($currency));
        $prices_query = "
            SELECT lp.*, p.edition, p.product_name, p.description
            FROM {$wpdb->prefix}themisdb_license_prices lp
            LEFT JOIN {$wpdb->prefix}themisdb_products p ON lp.product_edition = p.edition
            WHERE lp.valid_from <= CURDATE()
            AND (lp.valid_until IS NULL OR lp.valid_until >= CURDATE())
            AND lp.currency = %s
            AND lp.id = (
                SELECT lp2.id
                FROM {$wpdb->prefix}themisdb_license_prices lp2
                WHERE lp2.product_edition = lp.product_edition
                AND lp2.valid_from <= CURDATE()
                AND (lp2.valid_until IS NULL OR lp2.valid_until >= CURDATE())
                AND lp2.currency = %s
                ORDER BY lp2.valid_from DESC, lp2.id DESC
                LIMIT 1
            )
            ORDER BY lp.product_edition ASC
        ";

        $raw_prices = $wpdb->get_results($wpdb->prepare($prices_query, $currency, $currency), ARRAY_A);
        $prices_by_edition = array();
        $edition_by_license_id = array();
        foreach ((array) $raw_prices as $price) {
            $edition = sanitize_key($price['product_edition'] ?? '');
            if ($edition === '') {
                continue;
            }
            $prices_by_edition[$edition] = $price;

            $license_id = absint($price['license_id'] ?? 0);
            if ($license_id > 0) {
                $edition_by_license_id[$license_id] = $edition;
            }
        }

        $features_by_type = array();
        if ($include_features && !empty($prices_by_edition) && !empty($edition_by_license_id)) {
            $license_ids = array_keys($edition_by_license_id);
            $license_placeholders = implode(',', array_fill(0, count($license_ids), '%d'));
            $features_query = "
                SELECT lf.*
                FROM {$wpdb->prefix}themisdb_license_features lf
                WHERE lf.is_active = 1
                AND lf.valid_from <= CURDATE()
                AND (lf.valid_until IS NULL OR lf.valid_until >= CURDATE())
                AND lf.license_id IN ({$license_placeholders})
                ORDER BY lf.license_id ASC, lf.feature_code ASC, lf.id ASC
            ";

            $raw_features = $wpdb->get_results($wpdb->prepare($features_query, $license_ids), ARRAY_A);
            $seen_feature_keys = array();
            foreach ((array) $raw_features as $feature) {
                $license_id = absint($feature['license_id'] ?? 0);
                $edition = $license_id > 0 && isset($edition_by_license_id[$license_id]) ? $edition_by_license_id[$license_id] : '';
                if ($edition === '' || !isset($prices_by_edition[$edition])) {
                    continue;
                }

                $feature_code = sanitize_key($feature['feature_code'] ?? '');
                $feature_name = sanitize_text_field($feature['feature_name'] ?? '');
                $dedupe_token = $feature_code !== '' ? $feature_code : sanitize_key($feature_name);
                if ($dedupe_token === '') {
                    $dedupe_token = (string) absint($feature['id'] ?? 0);
                }

                $dedupe_key = $edition . '|' . $dedupe_token;
                if (isset($seen_feature_keys[$dedupe_key])) {
                    continue;
                }

                $seen_feature_keys[$dedupe_key] = true;

                if (!isset($features_by_type[$edition])) {
                    $features_by_type[$edition] = array();
                }
                $features_by_type[$edition][] = $feature;
            }
        }

        return array($prices_by_edition, $features_by_type);
    }

    /**
     * Resolve a recommended edition for shop deep links.
     *
     * @param array  $products
     * @param array  $license_prices
     * @param string $context
     * @param string $detail
     * @param string $preferred_edition
     * @return string
     */
    private function resolve_shop_recommended_edition($products, $license_prices, $context = 'default', $detail = '', $preferred_edition = '') {
        $context = sanitize_key($context);
        $available_editions = array();
        foreach ((array) $products as $product) {
            $edition = sanitize_key($product['edition'] ?? '');
            if ($edition !== '') {
                $available_editions[$edition] = true;
            }
        }
        foreach (array_keys((array) $license_prices) as $edition) {
            $available_editions[sanitize_key($edition)] = true;
        }

        if (empty($available_editions)) {
            return '';
        }

        $detail = sanitize_key($detail);
        $preferred_edition = sanitize_key($preferred_edition);

        if ($preferred_edition !== '' && isset($available_editions[$preferred_edition])) {
            return $preferred_edition;
        }

        $preferences = array('enterprise', 'hyperscaler', 'reseller', 'community');

        if ($context === 'module') {
            if (in_array($detail, array('scaling', 'cluster', 'high-availability'), true)) {
                $preferences = array('hyperscaler', 'enterprise', 'reseller', 'community');
            } elseif (in_array($detail, array('storage', 'ai-ml', 'security', 'compliance'), true)) {
                $preferences = array('enterprise', 'hyperscaler', 'reseller', 'community');
            }
        }

        if ($context === 'training') {
            if (in_array($detail, array('onsite', 'consulting', 'workshop'), true)) {
                $preferences = array('enterprise', 'hyperscaler', 'reseller', 'community');
            } else {
                $preferences = array('enterprise', 'community', 'hyperscaler', 'reseller');
            }
        }

        $preferences = apply_filters(
            'themisdb_shop_recommended_edition_preferences',
            $preferences,
            $context,
            $detail,
            array_keys($available_editions),
            $products,
            $license_prices
        );

        if (!is_array($preferences) || empty($preferences)) {
            $preferences = array('enterprise', 'hyperscaler', 'reseller', 'community');
        }

        $preferences = array_values(array_unique(array_filter(array_map('sanitize_key', $preferences))));

        foreach ($preferences as $preferred_edition) {
            if (isset($available_editions[$preferred_edition])) {
                $resolved = $preferred_edition;
                return sanitize_key((string) apply_filters(
                    'themisdb_shop_recommended_edition',
                    $resolved,
                    $context,
                    $detail,
                    $preferences,
                    array_keys($available_editions),
                    $products,
                    $license_prices
                ));
            }
        }

        $fallback_editions = array_keys($available_editions);
        $resolved = isset($fallback_editions[0]) ? $fallback_editions[0] : '';

        return sanitize_key((string) apply_filters(
            'themisdb_shop_recommended_edition',
            $resolved,
            $context,
            $detail,
            $preferences,
            array_keys($available_editions),
            $products,
            $license_prices
        ));
    }

    /**
     * Format a catalog price for the dynamic shop.
     *
     * @param mixed  $price
     * @param string $currency
     * @return string
     */
    private function format_shop_price($price, $currency) {
        $amount = floatval($price);
        $currency = strtoupper(sanitize_text_field($currency));

        if ($amount <= 0) {
            return __('Kostenlos', 'themisdb-order-request');
        }

        return number_format($amount, 2, ',', '.') . ' ' . $currency;
    }

    /**
     * Format license limits for the dynamic shop.
     *
     * @param mixed  $value
     * @param string $suffix
     * @return string
     */
    private function format_shop_limit($value, $suffix = '') {
        if ($value === null || $value === '' || intval($value) <= 0) {
            return $suffix !== '' ? '∞ ' . $suffix : '∞';
        }

        $formatted = number_format(intval($value), 0, ',', '.');
        return $suffix !== '' ? $formatted . ' ' . $suffix : $formatted;
    }

    /**
     * Turn a slug-like catalog key into a readable label.
     *
     * @param string $key
     * @return string
     */
    private function humanize_shop_key($key) {
        $label = str_replace(array('-', '_'), ' ', sanitize_text_field($key));
        $label = trim($label);

        if ($label === '') {
            return __('Allgemein', 'themisdb-order-request');
        }

        return ucwords($label);
    }

    /**
     * Compare two URLs by target location (scheme/host/port/path), ignoring query and fragment.
     *
     * @param string $left
     * @param string $right
     * @return bool
     */
    private function urls_point_to_same_location($left, $right) {
        $left = esc_url_raw((string) $left);
        $right = esc_url_raw((string) $right);

        if ($left === '' || $right === '') {
            return false;
        }

        $left_parts = wp_parse_url($left);
        $right_parts = wp_parse_url($right);

        if (!is_array($left_parts) || !is_array($right_parts)) {
            return false;
        }

        $left_scheme = strtolower((string) ($left_parts['scheme'] ?? ''));
        $right_scheme = strtolower((string) ($right_parts['scheme'] ?? ''));
        $left_host = strtolower((string) ($left_parts['host'] ?? ''));
        $right_host = strtolower((string) ($right_parts['host'] ?? ''));
        $left_port = intval($left_parts['port'] ?? 0);
        $right_port = intval($right_parts['port'] ?? 0);

        $left_path = '/' . ltrim((string) ($left_parts['path'] ?? ''), '/');
        $right_path = '/' . ltrim((string) ($right_parts['path'] ?? ''), '/');
        $left_path = untrailingslashit($left_path);
        $right_path = untrailingslashit($right_path);

        if ($left_path === '') {
            $left_path = '/';
        }
        if ($right_path === '') {
            $right_path = '/';
        }

        return $left_scheme === $right_scheme
            && $left_host === $right_host
            && $left_port === $right_port
            && $left_path === $right_path;
    }

    /**
     * Resolve a frontend page URL using native WordPress lookups.
     *
     * Resolution order:
     * 1) Option value as page ID or URL
     * 2) First published page containing the requested shortcode
     * 3) First published page matching a fallback slug
     * 4) home_url for the first fallback slug
     *
     * @param string $option_key
     * @param array  $fallback_slugs
     * @param string $shortcode_tag
     * @return string
     */
    private function resolve_frontend_page_url($option_key, $fallback_slugs = array(), $shortcode_tag = '') {
        $option_key = sanitize_key((string) $option_key);
        $shortcode_tag = sanitize_key((string) $shortcode_tag);

        $fallback_slugs = array_values(array_filter(array_map(function($slug) {
            return sanitize_title((string) $slug);
        }, (array) $fallback_slugs)));

        $configured = get_option($option_key, '');
        if (is_numeric($configured) && intval($configured) > 0) {
            $configured_permalink = get_permalink(intval($configured));
            if (is_string($configured_permalink) && $configured_permalink !== '') {
                return esc_url_raw($configured_permalink);
            }
        }

        $configured_url = esc_url_raw((string) $configured);
        if ($configured_url !== '') {
            return $configured_url;
        }

        if ($shortcode_tag !== '') {
            $page_ids = get_posts(array(
                'post_type' => 'page',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'no_found_rows' => true,
            ));

            foreach ((array) $page_ids as $page_id) {
                $content = (string) get_post_field('post_content', intval($page_id));
                if ($content !== '' && has_shortcode($content, $shortcode_tag)) {
                    $shortcode_permalink = get_permalink(intval($page_id));
                    if (is_string($shortcode_permalink) && $shortcode_permalink !== '') {
                        return esc_url_raw($shortcode_permalink);
                    }
                }
            }
        }

        foreach ($fallback_slugs as $slug) {
            $page = get_page_by_path($slug, OBJECT, 'page');
            if ($page instanceof WP_Post && $page->post_status === 'publish') {
                $page_permalink = get_permalink($page->ID);
                if (is_string($page_permalink) && $page_permalink !== '') {
                    return esc_url_raw($page_permalink);
                }
            }
        }

        if (!empty($fallback_slugs)) {
            return esc_url_raw(home_url('/' . $fallback_slugs[0]));
        }

        return '';
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  SHOPPING CART (Phase 2.3)
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Shortcode: [themisdb_shopping_cart]
     *
     * Attributes:
     *   checkout_url  - URL of the order-flow page (default: option themisdb_order_page_url)
     *   show_tax_note - 'yes'|'no' (default: 'yes')
     *   currency      - currency symbol (default: '€')
     */
    public function shopping_cart_shortcode($atts) {
        $atts = shortcode_atts(array(
            'checkout_url' => '',
            'show_tax_note' => 'yes',
            'currency'     => '€',
        ), $atts);

        wp_enqueue_style(
            'themisdb-shopping-cart-style',
            THEMISDB_ORDER_PLUGIN_URL . 'assets/css/shopping-cart.css',
            array(),
            THEMISDB_ORDER_VERSION
        );
        wp_enqueue_script(
            'themisdb-shopping-cart',
            THEMISDB_ORDER_PLUGIN_URL . 'assets/js/shopping-cart.js',
            array('jquery'),
            THEMISDB_ORDER_VERSION,
            true
        );

        $checkout_url = esc_url_raw($atts['checkout_url']);
        if ($checkout_url === '') {
            $checkout_url = $this->resolve_frontend_page_url(
                'themisdb_order_page_url',
                array('bestellung', 'checkout'),
                'themisdb_order_flow'
            );
        }

        wp_localize_script('themisdb-shopping-cart', 'themisdbCart', array(
            'ajaxUrl'     => admin_url('admin-ajax.php'),
            'nonce'       => wp_create_nonce('themisdb_order_nonce'),
            'checkoutUrl' => $checkout_url,
            'currency'    => sanitize_text_field($atts['currency']),
            'i18n'        => array(
                'empty'         => __('Ihr Warenkorb ist leer.', 'themisdb-order-request'),
                'removing'      => __('Wird entfernt…', 'themisdb-order-request'),
                'clearing'      => __('Wird geleert…', 'themisdb-order-request'),
                'error'         => __('Ein Fehler ist aufgetreten.', 'themisdb-order-request'),
                'confirmClear'  => __('Warenkorb wirklich leeren?', 'themisdb-order-request'),
            ),
        ));

        ob_start();
        $this->render_shopping_cart(
            $checkout_url,
            $atts['show_tax_note'] === 'yes',
            sanitize_text_field($atts['currency'])
        );
        return ob_get_clean();
    }

    /**
     * Render the shopping cart HTML from the current session order.
     *
     * @param string $checkout_url
     * @param bool   $show_tax_note
     * @param string $currency
     */
    private function render_shopping_cart($checkout_url, $show_tax_note, $currency) {
        if (!session_id()) {
            session_start();
        }

        $order_id = isset($_SESSION['themisdb_order_id']) ? intval($_SESSION['themisdb_order_id']) : 0;
        $order    = $order_id > 0 ? ThemisDB_Order_Manager::get_order($order_id) : null;
        $is_empty = !$order || empty($order['product_edition']);

        // Resolve readable product label.
        $product_label = '';
        $product_price = 0.0;
        if ($order && !empty($order['product_edition'])) {
            $product = ThemisDB_Order_Manager::get_product_by_edition($order['product_edition']);
            $product_label = $product ? $product['product_name'] : ucfirst($order['product_edition']);
            $product_price = $product ? floatval($product['price']) : 0.0;
        }

        // Resolve module details.
        $module_list = array();
        if ($order && !empty($order['modules'])) {
            $all_modules = ThemisDB_Order_Manager::get_modules();
            $mod_map     = array();
            foreach ($all_modules as $m) {
                $mod_map[$m['module_code']] = $m;
            }
            foreach ((array) $order['modules'] as $code) {
                $info = isset($mod_map[$code]) ? $mod_map[$code] : null;
                $module_list[] = array(
                    'code'  => $code,
                    'name'  => $info ? $info['module_name']  : $code,
                    'price' => $info ? floatval($info['price']) : 0.0,
                );
            }
        }

        // Resolve training details.
        $training_list = array();
        if ($order && !empty($order['training_modules'])) {
            $all_trainings = ThemisDB_Order_Manager::get_training_modules();
            $train_map     = array();
            foreach ($all_trainings as $t) {
                $train_map[$t['training_code']] = $t;
            }
            foreach ((array) $order['training_modules'] as $code) {
                $info = isset($train_map[$code]) ? $train_map[$code] : null;
                $training_list[] = array(
                    'code'  => $code,
                    'name'  => $info ? $info['training_name'] : $code,
                    'price' => $info ? floatval($info['price']) : 0.0,
                );
            }
        }

        $total = floatval($order['total_amount'] ?? 0);
        if ($total <= 0 && !$is_empty) {
            $mod_codes   = array_column($module_list,   'code');
            $train_codes = array_column($training_list, 'code');
            $total       = ThemisDB_Order_Manager::calculate_total($order['product_edition'], $mod_codes, $train_codes);
        }

        ?>
        <div class="themisdb-shopping-cart" data-order-id="<?php echo esc_attr($order_id); ?>">

            <?php if ($is_empty) : ?>
            <div class="tsc-empty">
                <p class="tsc-empty-msg"><?php esc_html_e('Ihr Warenkorb ist leer.', 'themisdb-order-request'); ?></p>
                <?php
                $product_page_url = $this->resolve_frontend_page_url(
                    'themisdb_product_page_url',
                    array('produkte', 'produkt', 'konfigurator'),
                    'themisdb_product_detail'
                );
                if ($product_page_url) :
                ?>
                <a href="<?php echo esc_url($product_page_url); ?>" class="tsc-btn tsc-btn--secondary">
                    <?php esc_html_e('Produkte ansehen', 'themisdb-order-request'); ?>
                </a>
                <?php endif; ?>
            </div>
            <?php else : ?>

            <table class="tsc-table">
                <thead>
                    <tr>
                        <th class="tsc-col-product"><?php esc_html_e('Produkt', 'themisdb-order-request'); ?></th>
                        <th class="tsc-col-price"><?php esc_html_e('Preis', 'themisdb-order-request'); ?></th>
                        <th class="tsc-col-action"></th>
                    </tr>
                </thead>
                <tbody class="tsc-items">

                    <!-- Base product row (not removable while edition is set) -->
                    <tr class="tsc-row tsc-row--product">
                        <td class="tsc-col-product">
                            <span class="tsc-item-type"><?php esc_html_e('Edition', 'themisdb-order-request'); ?></span>
                            <strong class="tsc-item-name"><?php echo esc_html($product_label); ?></strong>
                        </td>
                        <td class="tsc-col-price">
                            <?php if ($product_price > 0) : ?>
                                <?php echo esc_html(number_format($product_price, 2, ',', '.') . "\xc2\xa0" . $currency); ?>
                            <?php else : ?>
                                <em><?php esc_html_e('Kostenlos', 'themisdb-order-request'); ?></em>
                            <?php endif; ?>
                        </td>
                        <td class="tsc-col-action">
                            <a href="<?php echo esc_url($checkout_url); ?>" class="tsc-edit-link" title="<?php esc_attr_e('Edition ändern', 'themisdb-order-request'); ?>">
                                <?php esc_html_e('Ändern', 'themisdb-order-request'); ?>
                            </a>
                        </td>
                    </tr>

                    <!-- Module rows -->
                    <?php foreach ($module_list as $mod) : ?>
                    <tr class="tsc-row tsc-row--module" data-item-type="module" data-item-code="<?php echo esc_attr($mod['code']); ?>">
                        <td class="tsc-col-product">
                            <span class="tsc-item-type"><?php esc_html_e('Modul', 'themisdb-order-request'); ?></span>
                            <span class="tsc-item-name"><?php echo esc_html($mod['name']); ?></span>
                        </td>
                        <td class="tsc-col-price">
                            <?php if ($mod['price'] > 0) : ?>
                                +&nbsp;<?php echo esc_html(number_format($mod['price'], 2, ',', '.') . "\xc2\xa0" . $currency); ?>
                            <?php else : ?>
                                <em><?php esc_html_e('Inklusive', 'themisdb-order-request'); ?></em>
                            <?php endif; ?>
                        </td>
                        <td class="tsc-col-action">
                            <button type="button" class="tsc-remove-btn"
                                    data-item-type="module"
                                    data-item-code="<?php echo esc_attr($mod['code']); ?>"
                                    aria-label="<?php esc_attr_e('Modul entfernen', 'themisdb-order-request'); ?>">
                                &times;
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                    <!-- Training rows -->
                    <?php foreach ($training_list as $train) : ?>
                    <tr class="tsc-row tsc-row--training" data-item-type="training" data-item-code="<?php echo esc_attr($train['code']); ?>">
                        <td class="tsc-col-product">
                            <span class="tsc-item-type"><?php esc_html_e('Schulung', 'themisdb-order-request'); ?></span>
                            <span class="tsc-item-name"><?php echo esc_html($train['name']); ?></span>
                        </td>
                        <td class="tsc-col-price">
                            +&nbsp;<?php echo esc_html(number_format($train['price'], 2, ',', '.') . "\xc2\xa0" . $currency); ?>
                        </td>
                        <td class="tsc-col-action">
                            <button type="button" class="tsc-remove-btn"
                                    data-item-type="training"
                                    data-item-code="<?php echo esc_attr($train['code']); ?>"
                                    aria-label="<?php esc_attr_e('Schulung entfernen', 'themisdb-order-request'); ?>">
                                &times;
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                </tbody>
                <tfoot>
                    <tr class="tsc-total-row">
                        <th colspan="2" class="tsc-total-label">
                            <?php esc_html_e('Gesamt', 'themisdb-order-request'); ?>
                            <?php if ($show_tax_note) : ?>
                                <small class="tsc-tax-note"><?php esc_html_e('(Netto, zzgl. gesetzl. MwSt.)', 'themisdb-order-request'); ?></small>
                            <?php endif; ?>
                        </th>
                        <td class="tsc-total-amount" data-raw="<?php echo esc_attr($total); ?>">
                            <?php echo esc_html(number_format($total, 2, ',', '.') . "\xc2\xa0" . $currency); ?>
                        </td>
                    </tr>
                </tfoot>
            </table>

            <div class="tsc-actions">
                <button type="button" class="tsc-btn tsc-btn--danger tsc-clear-btn">
                    <?php esc_html_e('Warenkorb leeren', 'themisdb-order-request'); ?>
                </button>
                <a href="<?php echo esc_url(add_query_arg('step', '5', $checkout_url)); ?>" class="tsc-btn tsc-btn--primary tsc-checkout-btn">
                    <?php esc_html_e('Zur Kasse', 'themisdb-order-request'); ?>
                </a>
            </div>

            <?php endif; ?>

        </div><!-- /.themisdb-shopping-cart -->
        <?php
    }

    /**
     * AJAX: Remove one module or training from the session order.
     *
     * POST params: nonce, item_type (module|training), item_code
     */
    public function ajax_cart_remove_item() {
        check_ajax_referer('themisdb_order_nonce', 'nonce');

        $item_type = isset($_POST['item_type']) ? sanitize_key($_POST['item_type']) : '';
        $item_code = isset($_POST['item_code']) ? sanitize_text_field($_POST['item_code']) : '';

        if (!in_array($item_type, array('module', 'training'), true) || $item_code === '') {
            wp_send_json_error(array('message' => __('Ungültige Parameter.', 'themisdb-order-request')));
            return;
        }

        if (!session_id()) {
            session_start();
        }

        $order_id = isset($_SESSION['themisdb_order_id']) ? intval($_SESSION['themisdb_order_id']) : 0;
        if (!$order_id) {
            wp_send_json_error(array('message' => __('Kein aktiver Warenkorb.', 'themisdb-order-request')));
            return;
        }

        $order = ThemisDB_Order_Manager::get_order($order_id);
        if (!$order) {
            wp_send_json_error(array('message' => __('Bestellung nicht gefunden.', 'themisdb-order-request')));
            return;
        }

        if ($item_type === 'module') {
            $modules = is_array($order['modules']) ? $order['modules'] : array();
            $modules = array_values(array_filter($modules, function ($c) use ($item_code) { return $c !== $item_code; }));
            ThemisDB_Order_Manager::update_order($order_id, array('modules' => $modules));
        } else {
            $trainings = is_array($order['training_modules']) ? $order['training_modules'] : array();
            $trainings = array_values(array_filter($trainings, function ($c) use ($item_code) { return $c !== $item_code; }));
            ThemisDB_Order_Manager::update_order($order_id, array('training_modules' => $trainings));
        }

        // Recalculate total.
        $updated = ThemisDB_Order_Manager::get_order($order_id);
        $new_total = ThemisDB_Order_Manager::calculate_total(
            $updated['product_edition'],
            is_array($updated['modules'])          ? $updated['modules']          : array(),
            is_array($updated['training_modules']) ? $updated['training_modules'] : array()
        );
        ThemisDB_Order_Manager::update_order($order_id, array('total_amount' => $new_total));

        wp_send_json_success(array(
            'new_total'  => $new_total,
            'item_type'  => $item_type,
            'item_code'  => $item_code,
        ));
    }

    /**
     * AJAX: Clear the entire session cart (destroys the draft order from the session).
     *
     * POST params: nonce
     */
    public function ajax_cart_clear() {
        check_ajax_referer('themisdb_order_nonce', 'nonce');

        if (!session_id()) {
            session_start();
        }

        $order_id = isset($_SESSION['themisdb_order_id']) ? intval($_SESSION['themisdb_order_id']) : 0;
        if ($order_id > 0) {
            $order = ThemisDB_Order_Manager::get_order($order_id);
            // Only delete truly draft/pending orders; leave confirmed ones intact.
            if ($order && in_array($order['status'], array('draft', 'pending'), true)) {
                ThemisDB_Order_Manager::delete_order($order_id);
            }
        }

        unset($_SESSION['themisdb_order_id']);

        wp_send_json_success(array('cleared' => true));
    }
}
