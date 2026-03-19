<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB - Hybrid Database System                                   ║
╠═════════════════════════════════════════════════════════════════════╣
  File:            class-admin.php                                    ║
  Version:         0.0.2                                              ║
  Last Modified:   2026-03-09 04:08:19                                ║
  Author:          unknown                                            ║
╠═════════════════════════════════════════════════════════════════════╣
  Quality Metrics:                                                    ║
    • Maturity Level:  🟢 PRODUCTION-READY                             ║
    • Quality Score:   100.0/100                                      ║
    • Total Lines:     1350                                           ║
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
 * Admin interface for ThemisDB Order Request Plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class ThemisDB_Order_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_post_themisdb_sync_epserver', array($this, 'handle_sync'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('ThemisDB Bestellungen', 'themisdb-order-request'),
            __('ThemisDB Orders', 'themisdb-order-request'),
            'manage_options',
            'themisdb-orders',
            array($this, 'orders_page'),
            'dashicons-cart',
            30
        );
        
        add_submenu_page(
            'themisdb-orders',
            __('Alle Bestellungen', 'themisdb-order-request'),
            __('Bestellungen', 'themisdb-order-request'),
            'manage_options',
            'themisdb-orders',
            array($this, 'orders_page')
        );
        
        add_submenu_page(
            'themisdb-orders',
            __('Verträge', 'themisdb-order-request'),
            __('Verträge', 'themisdb-order-request'),
            'manage_options',
            'themisdb-contracts',
            array($this, 'contracts_page')
        );
        
        add_submenu_page(
            'themisdb-orders',
            __('Produkte', 'themisdb-order-request'),
            __('Produkte', 'themisdb-order-request'),
            'manage_options',
            'themisdb-products',
            array($this, 'products_page')
        );

        add_submenu_page(
            'themisdb-orders',
            __('Lagerbestand', 'themisdb-order-request'),
            __('Lagerbestand', 'themisdb-order-request'),
            'manage_options',
            'themisdb-inventory',
            array($this, 'inventory_page')
        );
        
        add_submenu_page(
            'themisdb-orders',
            __('Zahlungen', 'themisdb-order-request'),
            __('Zahlungen', 'themisdb-order-request'),
            'manage_options',
            'themisdb-payments',
            array($this, 'payments_page')
        );
        
        add_submenu_page(
            'themisdb-orders',
            __('Lizenzen', 'themisdb-order-request'),
            __('Lizenzen', 'themisdb-order-request'),
            'manage_options',
            'themisdb-licenses',
            array($this, 'licenses_page')
        );
        
        add_submenu_page(
            'themisdb-orders',
            __('E-Mail Log', 'themisdb-order-request'),
            __('E-Mail Log', 'themisdb-order-request'),
            'manage_options',
            'themisdb-email-log',
            array($this, 'email_log_page')
        );

        add_submenu_page(
            'themisdb-orders',
            __('License Audit Log', 'themisdb-order-request'),
            __('License Audit Log', 'themisdb-order-request'),
            'manage_options',
            'themisdb-license-audit',
            array($this, 'license_audit_page')
        );

        add_submenu_page(
            'themisdb-orders',
            __('Bankimport', 'themisdb-order-request'),
            __('Bankimport', 'themisdb-order-request'),
            'manage_options',
            'themisdb-bank-import',
            array($this, 'bank_import_page')
        );
        
        add_submenu_page(
            'themisdb-orders',
            __('Einstellungen', 'themisdb-order-request'),
            __('Einstellungen', 'themisdb-order-request'),
            'manage_options',
            'themisdb-order-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('themisdb_order_settings', 'themisdb_order_epserver_url');
        register_setting('themisdb_order_settings', 'themisdb_order_epserver_api_key');
        register_setting('themisdb_order_settings', 'themisdb_order_email_from');
        register_setting('themisdb_order_settings', 'themisdb_order_email_from_name');
        register_setting('themisdb_order_settings', 'themisdb_order_pdf_storage');
        register_setting('themisdb_order_settings', 'themisdb_order_legal_compliance');
        register_setting('themisdb_order_settings', 'themisdb_order_legal_agb_url');
        register_setting('themisdb_order_settings', 'themisdb_order_legal_privacy_url');
        register_setting('themisdb_order_settings', 'themisdb_order_legal_withdrawal_url');
        register_setting('themisdb_order_settings', 'themisdb_order_legal_version');
        register_setting('themisdb_order_settings', 'themisdb_license_api_key');
        register_setting('themisdb_order_settings', 'themisdb_license_admin_secret');
        register_setting('themisdb_order_settings', 'themisdb_license_renewal_reminder_days');
    }
    
    /**
     * Orders page
     */
    public function orders_page() {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        
        // Handle POST actions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['action'])) {
                $post_action = sanitize_text_field($_POST['action']);
                
                if ($post_action === 'save_order' && isset($_POST['order_id'])) {
                    check_admin_referer('themisdb_save_order');
                    $this->handle_save_order(intval($_POST['order_id']));
                    return;
                }
                
                if ($post_action === 'delete_order' && isset($_POST['order_id'])) {
                    check_admin_referer('themisdb_delete_order');
                    $this->handle_delete_order(intval($_POST['order_id']));
                    return;
                }
                
                if ($post_action === 'change_status' && isset($_POST['order_id'])) {
                    check_admin_referer('themisdb_change_order_status');
                    $this->handle_change_order_status(intval($_POST['order_id']), sanitize_text_field($_POST['new_status']));
                    return;
                }
                
                if ($post_action === 'create_contract' && isset($_POST['order_id'])) {
                    check_admin_referer('themisdb_create_contract');
                    $this->handle_create_contract(intval($_POST['order_id']));
                    return;
                }

                if ($post_action === 'update_fulfillment' && isset($_POST['order_id'])) {
                    check_admin_referer('themisdb_update_fulfillment');
                    $this->handle_update_fulfillment(intval($_POST['order_id']));
                    return;
                }
            }
        }
        
        // Handle GET actions
        if ($action === 'edit' && $order_id) {
            $this->edit_order($order_id);
        } elseif ($action === 'view' && $order_id) {
            $this->view_order($order_id);
        } elseif ($action === 'new') {
            $this->create_order();
        } else {
            $this->list_orders();
        }
    }
    
    /**
     * List orders
     */
    private function list_orders() {
        $orders = ThemisDB_Order_Manager::get_all_orders();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Bestellungen', 'themisdb-order-request'); ?></h1>
            
            <div style="margin-bottom: 1.5rem;">
                <a href="?page=themisdb-orders&action=new" class="button button-primary">
                    <?php _e('+ Neue Bestellung', 'themisdb-order-request'); ?>
                </a>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Bestellnummer', 'themisdb-order-request'); ?></th>
                        <th><?php _e('Kunde', 'themisdb-order-request'); ?></th>
                        <th><?php _e('Produkt', 'themisdb-order-request'); ?></th>
                        <th><?php _e('Betrag', 'themisdb-order-request'); ?></th>
                        <th><?php _e('Status', 'themisdb-order-request'); ?></th>
                        <th><?php _e('Datum', 'themisdb-order-request'); ?></th>
                        <th><?php _e('Aktionen', 'themisdb-order-request'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="7"><?php _e('Keine Bestellungen gefunden', 'themisdb-order-request'); ?></td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><strong><?php echo esc_html($order['order_number']); ?></strong></td>
                            <td>
                                <?php echo esc_html($order['customer_name']); ?>
                                <?php if ($order['customer_company']): ?>
                                    <br><small><?php echo esc_html($order['customer_company']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html(ucfirst($order['product_edition'])); ?></td>
                            <td><?php echo number_format($order['total_amount'], 2, ',', '.'); ?> <?php echo esc_html($order['currency']); ?></td>
                            <td>
                                <span class="order-status status-<?php echo esc_attr($order['status']); ?>">
                                    <?php echo esc_html(ucfirst($order['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html(wp_date('d.m.Y H:i', strtotime($order['created_at']))); ?></td>
                            <td>
                                <a href="?page=themisdb-orders&action=view&order_id=<?php echo absint($order['id']); ?>" class="button button-small">
                                    <?php _e('Ansehen', 'themisdb-order-request'); ?>
                                </a>
                                <a href="?page=themisdb-orders&action=edit&order_id=<?php echo absint($order['id']); ?>" class="button button-small">
                                    <?php _e('Ändern', 'themisdb-order-request'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * View single order
     */
    private function view_order($order_id) {
        $order = ThemisDB_Order_Manager::get_order($order_id);
        
        if (!$order) {
            echo '<div class="notice notice-error"><p>' . __('Bestellung nicht gefunden', 'themisdb-order-request') . '</p></div>';
            return;
        }
        
        $contracts = ThemisDB_Contract_Manager::get_contracts_by_order($order_id);
        $order_items = ThemisDB_Order_Manager::get_order_items($order_id);
        
        ?>
        <div class="wrap">
            <h1><?php _e('Bestellung', 'themisdb-order-request'); ?>: <?php echo esc_html($order['order_number']); ?></h1>

            <?php if (isset($_GET['message']) && $_GET['message'] === 'fulfillment_updated'): ?>
            <div class="notice notice-success"><p><?php _e('Fulfillment-Status wurde aktualisiert.', 'themisdb-order-request'); ?></p></div>
            <?php endif; ?>
            
            <div class="card">
                <h2><?php _e('Kundendaten', 'themisdb-order-request'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Name', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html($order['customer_name']); ?></td>
                    </tr>
                    <?php if ($order['customer_company']): ?>
                    <tr>
                        <th><?php _e('Unternehmen', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html($order['customer_company']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th><?php _e('E-Mail', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html($order['customer_email']); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Kundentyp', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html(($order['customer_type'] ?? 'consumer') === 'business' ? __('Unternehmer', 'themisdb-order-request') : __('Verbraucher', 'themisdb-order-request')); ?></td>
                    </tr>
                    <?php if (!empty($order['vat_id'])): ?>
                    <tr>
                        <th><?php _e('USt-IdNr.', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html($order['vat_id']); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>

            <?php if (!empty($order['billing_address_line1'])): ?>
            <div class="card">
                <h2><?php _e('Rechnungsdaten', 'themisdb-order-request'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Rechnungsempfänger', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html($order['billing_name'] ?: $order['customer_name']); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Adresse', 'themisdb-order-request'); ?>:</th>
                        <td>
                            <?php echo esc_html($order['billing_address_line1']); ?><br>
                            <?php if (!empty($order['billing_address_line2'])): ?>
                                <?php echo esc_html($order['billing_address_line2']); ?><br>
                            <?php endif; ?>
                            <?php echo esc_html(trim(($order['billing_postal_code'] ?? '') . ' ' . ($order['billing_city'] ?? ''))); ?><br>
                            <?php echo esc_html($order['billing_country'] ?? 'DE'); ?>
                        </td>
                    </tr>
                </table>
            </div>
            <?php endif; ?>

            <div class="card">
                <h2><?php _e('Rechtliche Zustimmung', 'themisdb-order-request'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('AGB akzeptiert', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo !empty($order['legal_terms_accepted']) ? 'Ja' : 'Nein'; ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Datenschutz akzeptiert', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo !empty($order['legal_privacy_accepted']) ? 'Ja' : 'Nein'; ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Widerruf bestätigt', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo !empty($order['legal_withdrawal_acknowledged']) ? 'Ja' : 'Nein'; ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Verzicht Sofortausführung', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo !empty($order['legal_withdrawal_waiver']) ? 'Ja' : 'Nein'; ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Version Rechtstexte', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html($order['legal_acceptance_version'] ?: 'de-v1'); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Zugestimmt am', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo !empty($order['legal_accepted_at']) ? esc_html(date('d.m.Y H:i', strtotime($order['legal_accepted_at']))) : '—'; ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('IP', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html($order['legal_accepted_ip'] ?: '—'); ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="card">
                <h2><?php _e('Bestelldetails', 'themisdb-order-request'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Bestellnummer', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html($order['order_number']); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Produkt', 'themisdb-order-request'); ?>:</th>
                        <td>
                            <?php if ($order['product_type'] === 'merchandise'): ?>
                                <?php echo esc_html($order['product_edition']); ?>
                            <?php else: ?>
                                ThemisDB <?php echo esc_html(ucfirst($order['product_edition'])); ?> Edition
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Produkttyp', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html(ucfirst($order['product_type'])); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Gesamtbetrag', 'themisdb-order-request'); ?>:</th>
                        <td><strong><?php echo number_format($order['total_amount'], 2, ',', '.'); ?> <?php echo esc_html($order['currency']); ?></strong></td>
                    </tr>
                    <tr>
                        <th><?php _e('Status', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html(ucfirst($order['status'])); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Erstellt am', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></td>
                    </tr>
                </table>

                <?php if (!empty($order_items)): ?>
                <h3><?php _e('Positionen', 'themisdb-order-request'); ?></h3>
                <table class="wp-list-table widefat" style="max-width:none;">
                    <thead>
                        <tr>
                            <th><?php _e('Artikel', 'themisdb-order-request'); ?></th>
                            <th><?php _e('SKU', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Menge', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Einzelpreis', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Gesamt', 'themisdb-order-request'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td><?php echo esc_html($item['item_name']); ?></td>
                            <td><?php echo esc_html($item['sku'] ?: '—'); ?></td>
                            <td><?php echo absint($item['quantity']); ?></td>
                            <td><?php echo number_format($item['unit_price'], 2, ',', '.'); ?> <?php echo esc_html($item['currency']); ?></td>
                            <td><?php echo number_format($item['total_price'], 2, ',', '.'); ?> <?php echo esc_html($item['currency']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
                
                <?php if (!empty($order['modules'])): ?>
                <h3><?php _e('Module', 'themisdb-order-request'); ?></h3>
                <ul>
                    <?php 
                    $modules = ThemisDB_Order_Manager::get_modules();
                    foreach ($modules as $module):
                        if (in_array($module['module_code'], $order['modules'])):
                    ?>
                    <li><?php echo esc_html($module['module_name']); ?> - <?php echo number_format($module['price'], 2, ',', '.'); ?> €</li>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </ul>
                <?php endif; ?>
                
                <?php if (!empty($order['training_modules'])): ?>
                <h3><?php _e('Schulungen', 'themisdb-order-request'); ?></h3>
                <ul>
                    <?php 
                    $trainings = ThemisDB_Order_Manager::get_training_modules();
                    foreach ($trainings as $training):
                        if (in_array($training['training_code'], $order['training_modules'])):
                    ?>
                    <li><?php echo esc_html($training['training_name']); ?> - <?php echo number_format($training['price'], 2, ',', '.'); ?> €</li>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </ul>
                <?php endif; ?>
            </div>

            <?php if ($order['product_type'] === 'merchandise'): ?>
            <div class="card">
                <h2><?php _e('Versand und Fulfillment', 'themisdb-order-request'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Empfänger', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html($order['shipping_name'] ?: $order['customer_name']); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Lieferadresse', 'themisdb-order-request'); ?>:</th>
                        <td>
                            <?php echo esc_html($order['shipping_address_line1'] ?: '—'); ?><br>
                            <?php if (!empty($order['shipping_address_line2'])): ?>
                                <?php echo esc_html($order['shipping_address_line2']); ?><br>
                            <?php endif; ?>
                            <?php echo esc_html(trim(($order['shipping_postal_code'] ?? '') . ' ' . ($order['shipping_city'] ?? ''))); ?><br>
                            <?php echo esc_html($order['shipping_country'] ?: 'DE'); ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Versandart', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html($order['shipping_method'] ?: 'standard'); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Fulfillment-Status', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html(ucfirst($order['fulfillment_status'] ?: 'not_required')); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Tracking-Nummer', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html($order['tracking_number'] ?: '—'); ?></td>
                    </tr>
                </table>

                <form method="post" style="margin-top:1rem;">
                    <?php wp_nonce_field('themisdb_update_fulfillment'); ?>
                    <input type="hidden" name="action" value="update_fulfillment">
                    <input type="hidden" name="order_id" value="<?php echo absint($order_id); ?>">
                    <table class="form-table">
                        <tr>
                            <th><label for="fulfillment_status"><?php _e('Neuer Status', 'themisdb-order-request'); ?></label></th>
                            <td>
                                <select id="fulfillment_status" name="fulfillment_status">
                                    <?php foreach (array('pending', 'picking', 'packed', 'shipped', 'delivered', 'cancelled') as $status): ?>
                                    <option value="<?php echo esc_attr($status); ?>" <?php selected($order['fulfillment_status'], $status); ?>>
                                        <?php echo esc_html(ucfirst($status)); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="tracking_number"><?php _e('Tracking-Nummer', 'themisdb-order-request'); ?></label></th>
                            <td><input type="text" id="tracking_number" name="tracking_number" class="regular-text" value="<?php echo esc_attr($order['tracking_number'] ?? ''); ?>"></td>
                        </tr>
                    </table>
                    <p><button type="submit" class="button button-primary"><?php _e('Fulfillment speichern', 'themisdb-order-request'); ?></button></p>
                </form>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($contracts)): ?>
            <div class="card">
                <h2><?php _e('Verträge', 'themisdb-order-request'); ?></h2>
                <table class="wp-list-table widefat">
                    <thead>
                        <tr>
                            <th><?php _e('Vertragsnummer', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Typ', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Status', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Aktionen', 'themisdb-order-request'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contracts as $contract): ?>
                        <tr>
                            <td><?php echo esc_html($contract['contract_number']); ?></td>
                            <td><?php echo esc_html(ucfirst($contract['contract_type'])); ?></td>
                            <td><?php echo esc_html(ucfirst($contract['status'])); ?></td>
                            <td>
                                <a href="?page=themisdb-contracts&action=view&contract_id=<?php echo absint($contract['id']); ?>" class="button button-small">
                                    <?php _e('Ansehen', 'themisdb-order-request'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="card">
                <h2><?php _e('Verträge', 'themisdb-order-request'); ?></h2>
                <p><?php _e('Keine Verträge zu dieser Bestellung vorhanden.', 'themisdb-order-request'); ?></p>
                <?php if ($order['status'] === 'confirmed' || $order['status'] === 'draft'): ?>
                    <form method="post" style="margin-top: 1rem;">
                        <?php wp_nonce_field('themisdb_create_contract'); ?>
                        <input type="hidden" name="action" value="create_contract">
                        <input type="hidden" name="order_id" value="<?php echo absint($order_id); ?>">
                        <button type="submit" class="button button-primary">
                            <?php _e('Vertrag aus dieser Bestellung erstellen', 'themisdb-order-request'); ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Workflow Status Buttons -->
            <div class="card" style="background-color:#f9f9f9;">
                <h2><?php _e('Workflow-Aktionen', 'themisdb-order-request'); ?></h2>
                
                <?php 
                $allowed_transitions = array(
                    'draft' => array('confirmed', 'cancelled'),
                    'confirmed' => array('signed', 'cancelled'),
                    'signed' => array('active', 'cancelled'),
                    'active' => array('ended', 'suspended'),
                    'suspended' => array('active', 'ended'),
                    'ended' => array(),
                    'cancelled' => array()
                );
                
                $current_transitions = isset($allowed_transitions[$order['status']]) ? $allowed_transitions[$order['status']] : array();
                ?>
                
                <p><?php _e('Aktueller Status:', 'themisdb-order-request'); ?> <strong><?php echo esc_html(ucfirst($order['status'])); ?></strong></p>
                
                <?php if (!empty($current_transitions)): ?>
                <div style="margin: 1rem 0;">
                    <?php foreach ($current_transitions as $transition): ?>
                    <form method="post" style="display: inline;">
                        <?php wp_nonce_field('themisdb_change_order_status'); ?>
                        <input type="hidden" name="action" value="change_status">
                        <input type="hidden" name="order_id" value="<?php echo absint($order_id); ?>">
                        <input type="hidden" name="new_status" value="<?php echo esc_attr($transition); ?>">
                        <button type="submit" class="button button-<?php echo ($transition === 'cancelled' || $transition === 'ended') ? 'secondary' : 'primary'; ?>" style="margin-right: 0.5rem;">
                            <?php echo esc_html(ucfirst($transition)); ?>
                        </button>
                    </form>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p style="color: #666;"><?php _e('Keine weiteren Statusübergänge möglich.', 'themisdb-order-request'); ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Delete/Edit Buttons -->
            <p style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #ddd;">
                <a href="?page=themisdb-orders&action=edit&order_id=<?php echo absint($order_id); ?>" class="button button-primary">
                    <?php _e('✎ Bestellung ändern', 'themisdb-order-request'); ?>
                </a>
                <a href="?page=themisdb-orders" class="button">
                    <?php _e('Zurück zur Übersicht', 'themisdb-order-request'); ?>
                </a>
                
                <?php if ($order['status'] === 'draft' || $order['status'] === 'cancelled'): ?>
                <form method="post" style="display: inline;" onsubmit="return confirm('<?php _e('Möchten Sie diese Bestellung wirklich löschen? Dies kann nicht rückgängig gemacht werden.', 'themisdb-order-request'); ?>');">
                    <?php wp_nonce_field('themisdb_delete_order'); ?>
                    <input type="hidden" name="action" value="delete_order">
                    <input type="hidden" name="order_id" value="<?php echo absint($order_id); ?>">
                    <button type="submit" class="button button-link-delete">
                        <?php _e('Bestellung löschen', 'themisdb-order-request'); ?>
                    </button>
                </form>
                <?php endif; ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * Create new order form
     */
    public function create_order() {
        $products = ThemisDB_Order_Manager::get_products();
        $modules = ThemisDB_Order_Manager::get_modules();
        $trainings = ThemisDB_Order_Manager::get_training_modules();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Neue Bestellung', 'themisdb-order-request'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('themisdb_save_order'); ?>
                <input type="hidden" name="action" value="save_order">
                <input type="hidden" name="order_id" value="0">
                
                <div class="card">
                    <h2><?php _e('Kundendaten', 'themisdb-order-request'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><label for="customer_name"><?php _e('Name *', 'themisdb-order-request'); ?>:</label></th>
                            <td><input type="text" id="customer_name" name="customer_name" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="customer_email"><?php _e('E-Mail *', 'themisdb-order-request'); ?>:</label></th>
                            <td><input type="email" id="customer_email" name="customer_email" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="customer_company"><?php _e('Unternehmen', 'themisdb-order-request'); ?>:</label></th>
                            <td><input type="text" id="customer_company" name="customer_company" class="regular-text"></td>
                        </tr>
                    </table>
                </div>
                
                <div class="card">
                    <h2><?php _e('Bestelldetails', 'themisdb-order-request'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><label for="product_edition"><?php _e('Edition *', 'themisdb-order-request'); ?>:</label></th>
                            <td>
                                <select id="product_edition" name="product_edition" required>
                                    <option value=""><?php _e('-- Wählen Sie eine Edition --', 'themisdb-order-request'); ?></option>
                                    <?php foreach ($products as $product): ?>
                                    <option value="<?php echo esc_attr($product['edition']); ?>">
                                        ThemisDB <?php echo esc_html($product['edition']); ?> - <?php echo number_format($product['price'], 2, ',', '.'); ?> €
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="currency"><?php _e('Währung', 'themisdb-order-request'); ?>:</label></th>
                            <td>
                                <select id="currency" name="currency">
                                    <option value="EUR" selected>EUR (€)</option>
                                    <option value="USD">USD ($)</option>
                                    <option value="GBP">GBP (£)</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    
                    <h3><?php _e('Module (optional)', 'themisdb-order-request'); ?></h3>
                    <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 1rem;">
                        <?php foreach ($modules as $module): ?>
                        <label style="display:block; margin-bottom:.5rem;">
                            <input type="checkbox" name="modules[]" value="<?php echo esc_attr($module['module_code']); ?>">
                            <?php echo esc_html($module['module_name']); ?> - <?php echo number_format($module['price'], 2, ',', '.'); ?> €
                        </label>
                        <?php endforeach; ?>
                    </div>
                    
                    <h3 style="margin-top: 1.5rem;"><?php _e('Schulungen (optional)', 'themisdb-order-request'); ?></h3>
                    <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 1rem;">
                        <?php foreach ($trainings as $training): ?>
                        <label style="display:block; margin-bottom:.5rem;">
                            <input type="checkbox" name="training_modules[]" value="<?php echo esc_attr($training['training_code']); ?>">
                            <?php echo esc_html($training['training_name']); ?> - <?php echo number_format($training['price'], 2, ',', '.'); ?> €
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <p style="margin-top: 2rem;">
                    <button type="submit" class="button button-primary"><?php _e('Bestellung erstellen', 'themisdb-order-request'); ?></button>
                    <a href="?page=themisdb-orders" class="button"><?php _e('Abbrechen', 'themisdb-order-request'); ?></a>
                </p>
            </form>
        </div>
        <?php
    }
    
    /**
     * Edit existing order form
     */
    public function edit_order($order_id) {
        $order = ThemisDB_Order_Manager::get_order($order_id);
        if (!$order) {
            echo '<div class="notice notice-error"><p>' . __('Bestellung nicht gefunden', 'themisdb-order-request') . '</p></div>';
            return;
        }
        
        $products = ThemisDB_Order_Manager::get_products();
        $modules = ThemisDB_Order_Manager::get_modules();
        $trainings = ThemisDB_Order_Manager::get_training_modules();
        
        $order_modules = $order['modules'] ?? array();
        $order_trainings = $order['training_modules'] ?? array();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Bestellung bearbeiten', 'themisdb-order-request'); ?>: <?php echo esc_html($order['order_number']); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('themisdb_save_order'); ?>
                <input type="hidden" name="action" value="save_order">
                <input type="hidden" name="order_id" value="<?php echo absint($order_id); ?>">
                
                <div class="card">
                    <h2><?php _e('Kundendaten', 'themisdb-order-request'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><label for="customer_name"><?php _e('Name *', 'themisdb-order-request'); ?>:</label></th>
                            <td><input type="text" id="customer_name" name="customer_name" class="regular-text" value="<?php echo esc_attr($order['customer_name']); ?>" required></td>
                        </tr>
                        <tr>
                            <th><label for="customer_email"><?php _e('E-Mail *', 'themisdb-order-request'); ?>:</label></th>
                            <td><input type="email" id="customer_email" name="customer_email" class="regular-text" value="<?php echo esc_attr($order['customer_email']); ?>" required></td>
                        </tr>
                        <tr>
                            <th><label for="customer_company"><?php _e('Unternehmen', 'themisdb-order-request'); ?>:</label></th>
                            <td><input type="text" id="customer_company" name="customer_company" class="regular-text" value="<?php echo esc_attr($order['customer_company'] ?? ''); ?>"></td>
                        </tr>
                    </table>
                </div>
                
                <div class="card">
                    <h2><?php _e('Bestelldetails', 'themisdb-order-request'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><label for="product_edition"><?php _e('Edition *', 'themisdb-order-request'); ?>:</label></th>
                            <td>
                                <select id="product_edition" name="product_edition" required>
                                    <option value=""><?php _e('-- Wählen Sie eine Edition --', 'themisdb-order-request'); ?></option>
                                    <?php foreach ($products as $product): ?>
                                    <option value="<?php echo esc_attr($product['edition']); ?>" <?php selected($order['product_edition'], $product['edition']); ?>>
                                        ThemisDB <?php echo esc_html($product['edition']); ?> - <?php echo number_format($product['price'], 2, ',', '.'); ?> €
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="currency"><?php _e('Währung', 'themisdb-order-request'); ?>:</label></th>
                            <td>
                                <select id="currency" name="currency">
                                    <option value="EUR" <?php selected($order['currency'], 'EUR'); ?>>EUR (€)</option>
                                    <option value="USD" <?php selected($order['currency'], 'USD'); ?>>USD ($)</option>
                                    <option value="GBP" <?php selected($order['currency'], 'GBP'); ?>>GBP (£)</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    
                    <h3><?php _e('Module (optional)', 'themisdb-order-request'); ?></h3>
                    <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 1rem;">
                        <?php foreach ($modules as $module): ?>
                        <label style="display:block; margin-bottom:.5rem;">
                            <input type="checkbox" name="modules[]" value="<?php echo esc_attr($module['module_code']); ?>" <?php echo in_array($module['module_code'], $order_modules) ? 'checked' : ''; ?>>
                            <?php echo esc_html($module['module_name']); ?> - <?php echo number_format($module['price'], 2, ',', '.'); ?> €
                        </label>
                        <?php endforeach; ?>
                    </div>
                    
                    <h3 style="margin-top: 1.5rem;"><?php _e('Schulungen (optional)', 'themisdb-order-request'); ?></h3>
                    <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 1rem;">
                        <?php foreach ($trainings as $training): ?>
                        <label style="display:block; margin-bottom:.5rem;">
                            <input type="checkbox" name="training_modules[]" value="<?php echo esc_attr($training['training_code']); ?>" <?php echo in_array($training['training_code'], $order_trainings) ? 'checked' : ''; ?>>
                            <?php echo esc_html($training['training_name']); ?> - <?php echo number_format($training['price'], 2, ',', '.'); ?> €
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <p style="margin-top: 2rem;">
                    <button type="submit" class="button button-primary"><?php _e('Änderungen speichern', 'themisdb-order-request'); ?></button>
                    <a href="?page=themisdb-orders&action=view&order_id=<?php echo absint($order_id); ?>" class="button"><?php _e('Abbrechen', 'themisdb-order-request'); ?></a>
                </p>
            </form>
        </div>
        <?php
    }
    
    /**
     * Handle save order (POST)
     */
    private function handle_save_order($order_id) {
        // Validate input
        if (!isset($_POST['customer_name']) || !isset($_POST['customer_email']) || !isset($_POST['product_edition'])) {
            wp_die(__('Erforderliche Felder fehlen', 'themisdb-order-request'));
        }
        
        $data = array(
            'customer_name' => sanitize_text_field($_POST['customer_name']),
            'customer_email' => sanitize_email($_POST['customer_email']),
            'customer_company' => isset($_POST['customer_company']) ? sanitize_text_field($_POST['customer_company']) : '',
            'product_edition' => sanitize_text_field($_POST['product_edition']),
            'product_type' => 'database',
            'currency' => isset($_POST['currency']) ? sanitize_text_field($_POST['currency']) : 'EUR',
            'modules' => isset($_POST['modules']) ? array_map('sanitize_text_field', $_POST['modules']) : array(),
            'training_modules' => isset($_POST['training_modules']) ? array_map('sanitize_text_field', $_POST['training_modules']) : array(),
        );
        
        // Calculate total
        $data['total_amount'] = ThemisDB_Order_Manager::calculate_total(
            $data['product_edition'],
            $data['modules'],
            $data['training_modules']
        );
        
        if ($order_id === 0) {
            // Create new order
            $result = ThemisDB_Order_Manager::create_order($data);
            $message = __('Bestellung erfolgreich erstellt.', 'themisdb-order-request');
            $order_id = $result;
            
                    // Send invoice email to customer after order is created
                    if ($result) {
                        try {
                            ThemisDB_Email_Handler::send_invoice_email($result);
                        } catch (Exception $e) {
                            // Log error but don't fail the order creation
                            error_log('ThemisDB Invoice Email Error: ' . $e->getMessage());
                        }
                    }
        } else {
            // Update existing order
            $result = ThemisDB_Order_Manager::update_order($order_id, $data);
            $message = __('Bestellung erfolgreich aktualisiert.', 'themisdb-order-request');
        }
        
        if ($result) {
            wp_redirect(admin_url('admin.php?page=themisdb-orders&action=view&order_id=' . $order_id . '&message=saved'));
        } else {
            wp_die(__('Fehler beim Speichern der Bestellung', 'themisdb-order-request'));
        }
    }
    
    /**
     * Handle delete order (POST)
     */
    private function handle_delete_order($order_id) {
        if (ThemisDB_Order_Manager::delete_order($order_id)) {
            wp_redirect(admin_url('admin.php?page=themisdb-orders&message=deleted'));
        } else {
            wp_die(__('Fehler beim Löschen der Bestellung', 'themisdb-order-request'));
        }
    }
    
    /**
     * Handle change order status (POST)
     */
    private function handle_change_order_status($order_id, $new_status) {
        $order = ThemisDB_Order_Manager::get_order($order_id);
        $result = ThemisDB_Order_Manager::update_order($order_id, array('status' => $new_status));

        if ($result && in_array($new_status, array('confirmed', 'signed'), true)) {
            try {
                ThemisDB_Email_Handler::send_invoice_email($order_id);
            } catch (Exception $e) {
                error_log('ThemisDB Invoice Email Error: ' . $e->getMessage());
            }
        }

        if ($result && $order && $order['product_type'] === 'merchandise' && $new_status === 'cancelled') {
            ThemisDB_Order_Manager::release_inventory_for_order($order_id);
        }
        
        if ($result) {
            wp_redirect(admin_url('admin.php?page=themisdb-orders&action=view&order_id=' . $order_id . '&message=status_changed'));
        } else {
            wp_die(__('Fehler beim Ändern des Status', 'themisdb-order-request'));
        }
    }

    /**
     * Handle fulfillment update for merchandise orders.
     */
    private function handle_update_fulfillment($order_id) {
        $order = ThemisDB_Order_Manager::get_order($order_id);
        $status = isset($_POST['fulfillment_status']) ? sanitize_text_field($_POST['fulfillment_status']) : 'pending';
        $tracking_number = isset($_POST['tracking_number']) ? sanitize_text_field($_POST['tracking_number']) : '';

        $data = array(
            'fulfillment_status' => $status,
            'tracking_number' => $tracking_number,
        );

        if (in_array($status, array('shipped', 'delivered'), true)) {
            $data['fulfilled_at'] = current_time('mysql');
        }

        $result = ThemisDB_Order_Manager::update_order($order_id, $data);

        if ($result && $order && $order['product_type'] === 'merchandise') {
            $previous_status = isset($order['fulfillment_status']) ? $order['fulfillment_status'] : 'pending';

            if (!in_array($previous_status, array('shipped', 'delivered'), true) && in_array($status, array('shipped', 'delivered'), true)) {
                ThemisDB_Order_Manager::fulfill_inventory_for_order($order_id);
            }

            if ($previous_status !== 'cancelled' && $status === 'cancelled') {
                ThemisDB_Order_Manager::release_inventory_for_order($order_id);
            }
        }

        if ($result) {
            wp_redirect(admin_url('admin.php?page=themisdb-orders&action=view&order_id=' . $order_id . '&message=fulfillment_updated'));
            exit;
        }

        wp_die(__('Fehler beim Aktualisieren des Fulfillment-Status', 'themisdb-order-request'));
    }
    
    /**
     * Handle create contract from order (POST)
     */
    private function handle_create_contract($order_id) {
        $order = ThemisDB_Order_Manager::get_order($order_id);
        
        if (!$order) {
            wp_die(__('Bestellung nicht gefunden', 'themisdb-order-request'));
        }
        
        // Prepare contract data
        $contract_data = array(
            'order_id' => $order_id,
            'customer_id' => $order['customer_id'],
            'contract_type' => 'license', // oder 'invoice' je nach Bedarf
            'contract_data' => array(
                'order_number' => $order['order_number'],
                'customer_name' => $order['customer_name'],
                'customer_email' => $order['customer_email'],
                'customer_company' => $order['customer_company'],
                'product_edition' => $order['product_edition'],
                'total_amount' => $order['total_amount'],
                'currency' => $order['currency'],
                'modules' => $order['modules'] ?? array(),
                'training_modules' => $order['training_modules'] ?? array()
            )
        );
        
        // Create contract
        $contract_id = ThemisDB_Contract_Manager::create_contract($contract_data);
        
        if (!$contract_id) {
            wp_die(__('Fehler beim Erstellen des Vertrags', 'themisdb-order-request'));
        }
        
        // Generate PDF
        $pdf_result = ThemisDB_PDF_Generator::generate_contract_pdf($contract_id);
        
        // Send contract email to customer
        try {
            ThemisDB_Email_Handler::send_contract_email($contract_id);
        } catch (Exception $e) {
            // Log error but don't fail the contract creation
            error_log('ThemisDB Contract Email Error: ' . $e->getMessage());
        }
        
        // Update order status to 'signed' (optional - nur wenn Vertrag direkt nach Create signiert sein soll)
        // ThemisDB_Order_Manager::update_order($order_id, array('status' => 'signed'));
        
        // Redirect to contract view with success message
        wp_redirect(admin_url('admin.php?page=themisdb-contracts&action=view&contract_id=' . $contract_id . '&message=created'));
        exit;
    }
    
    /**
     * Contracts page
     */
    public function contracts_page() {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $contract_id = isset($_GET['contract_id']) ? intval($_GET['contract_id']) : 0;
        
        if ($action === 'view' && $contract_id) {
            $this->view_contract($contract_id);
        } else {
            $this->list_contracts();
        }
    }
    
    /**
     * List contracts
     */
    private function list_contracts() {
        $contracts = ThemisDB_Contract_Manager::get_all_contracts();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Verträge', 'themisdb-order-request'); ?></h1>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Vertragsnummer', 'themisdb-order-request'); ?></th>
                        <th><?php _e('Typ', 'themisdb-order-request'); ?></th>
                        <th><?php _e('Status', 'themisdb-order-request'); ?></th>
                        <th><?php _e('Gültig von', 'themisdb-order-request'); ?></th>
                        <th><?php _e('Gültig bis', 'themisdb-order-request'); ?></th>
                        <th><?php _e('Erstellt', 'themisdb-order-request'); ?></th>
                        <th><?php _e('Aktionen', 'themisdb-order-request'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($contracts)): ?>
                    <tr>
                        <td colspan="7"><?php _e('Keine Verträge gefunden', 'themisdb-order-request'); ?></td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($contracts as $contract): ?>
                        <tr>
                            <td><strong><?php echo esc_html($contract['contract_number']); ?></strong></td>
                            <td><?php echo esc_html(ucfirst($contract['contract_type'])); ?></td>
                            <td><?php echo esc_html(ucfirst($contract['status'])); ?></td>
                            <td><?php echo wp_date('d.m.Y', strtotime($contract['valid_from'])); ?></td>
                            <td><?php echo $contract['valid_until'] ? esc_html(wp_date('d.m.Y', strtotime($contract['valid_until']))) : '-'; ?></td>
                            <td><?php echo esc_html(wp_date('d.m.Y', strtotime($contract['created_at']))); ?></td>
                            <td>
                                <a href="?page=themisdb-contracts&action=view&contract_id=<?php echo absint($contract['id']); ?>" class="button button-small">
                                    <?php _e('Ansehen', 'themisdb-order-request'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * View single contract
     */
    private function view_contract($contract_id) {
        $contract = ThemisDB_Contract_Manager::get_contract($contract_id);
        
        if (!$contract) {
            echo '<div class="notice notice-error"><p>' . __('Vertrag nicht gefunden', 'themisdb-order-request') . '</p></div>';
            return;
        }
        
        $order = ThemisDB_Order_Manager::get_order($contract['order_id']);
        $revisions = ThemisDB_Contract_Manager::get_contract_revisions($contract_id);
        
        ?>
        <div class="wrap">
            <h1><?php _e('Vertrag', 'themisdb-order-request'); ?>: <?php echo esc_html($contract['contract_number']); ?></h1>
            
            <div class="card">
                <h2><?php _e('Vertragsdetails', 'themisdb-order-request'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Vertragsnummer', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html($contract['contract_number']); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Typ', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html(ucfirst($contract['contract_type'])); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Status', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html(ucfirst($contract['status'])); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Gültig von', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo date('d.m.Y', strtotime($contract['valid_from'])); ?></td>
                    </tr>
                    <?php if ($contract['valid_until']): ?>
                    <tr>
                        <th><?php _e('Gültig bis', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo date('d.m.Y', strtotime($contract['valid_until'])); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($contract['signed_at']): ?>
                    <tr>
                        <th><?php _e('Unterzeichnet am', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo date('d.m.Y H:i', strtotime($contract['signed_at'])); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
            
            <?php if ($order): ?>
            <div class="card">
                <h2><?php _e('Zugehörige Bestellung', 'themisdb-order-request'); ?></h2>
                <p>
                    <strong><?php _e('Bestellnummer', 'themisdb-order-request'); ?>:</strong> <?php echo esc_html($order['order_number']); ?><br>
                    <strong><?php _e('Kunde', 'themisdb-order-request'); ?>:</strong> <?php echo esc_html($order['customer_name']); ?><br>
                    <a href="?page=themisdb-orders&action=view&order_id=<?php echo $order['id']; ?>" class="button button-small">
                        <?php _e('Bestellung ansehen', 'themisdb-order-request'); ?>
                    </a>
                </p>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($revisions)): ?>
            <div class="card">
                <h2><?php _e('Revisionen', 'themisdb-order-request'); ?> (<?php echo count($revisions); ?>)</h2>
                <table class="wp-list-table widefat">
                    <thead>
                        <tr>
                            <th><?php _e('Revision', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Geändert von', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Grund', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Datum', 'themisdb-order-request'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($revisions as $revision): ?>
                        <?php $user = get_userdata($revision['changed_by']); ?>
                        <tr>
                            <td><?php echo $revision['revision_number']; ?></td>
                            <td><?php echo $user ? esc_html($user->display_name) : 'N/A'; ?></td>
                            <td><?php echo esc_html($revision['change_reason']); ?></td>
                            <td><?php echo date('d.m.Y H:i', strtotime($revision['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <p>
                <a href="?page=themisdb-contracts" class="button"><?php _e('Zurück zur Übersicht', 'themisdb-order-request'); ?></a>
            </p>
        </div>
        <?php
    }
    
    /**
     * Products page
     */
    public function products_page() {
        $entity = isset($_GET['entity']) ? sanitize_text_field($_GET['entity']) : 'product';
        $edit_id = isset($_GET['edit_id']) ? absint($_GET['edit_id']) : 0;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $post_action = sanitize_text_field($_POST['action']);

            if ($post_action === 'catalog_save_item') {
                check_admin_referer('themisdb_catalog_save_item');

                $save_entity = sanitize_text_field($_POST['entity'] ?? 'product');
                $save_id = absint($_POST['item_id'] ?? 0);
                $save_result = false;

                if ($save_entity === 'product') {
                    $save_result = ThemisDB_Order_Manager::save_product($_POST, $save_id);
                } elseif ($save_entity === 'module') {
                    $save_result = ThemisDB_Order_Manager::save_module($_POST, $save_id);
                } elseif ($save_entity === 'training') {
                    $save_result = ThemisDB_Order_Manager::save_training_module($_POST, $save_id);
                }

                wp_redirect(admin_url('admin.php?page=themisdb-products&message=' . ($save_result ? 'saved' : 'save_error')));
                exit;
            }

            if ($post_action === 'catalog_toggle_item') {
                check_admin_referer('themisdb_catalog_toggle_item');

                $toggle_entity = sanitize_text_field($_POST['entity'] ?? 'product');
                $toggle_id = absint($_POST['item_id'] ?? 0);
                $is_active = !empty($_POST['is_active']) ? 1 : 0;
                $toggle_result = ThemisDB_Order_Manager::set_catalog_item_active($toggle_entity, $toggle_id, $is_active);

                wp_redirect(admin_url('admin.php?page=themisdb-products&message=' . ($toggle_result ? 'status_saved' : 'status_error')));
                exit;
            }

            if ($post_action === 'catalog_delete_item') {
                check_admin_referer('themisdb_catalog_delete_item');

                $delete_entity = sanitize_text_field($_POST['entity'] ?? 'product');
                $delete_id = absint($_POST['item_id'] ?? 0);
                $delete_result = ThemisDB_Order_Manager::deactivate_catalog_item($delete_entity, $delete_id);

                wp_redirect(admin_url('admin.php?page=themisdb-products&message=' . ($delete_result ? 'deleted' : 'delete_error')));
                exit;
            }
        }

        $products = ThemisDB_Order_Manager::get_products(true);
        $modules = ThemisDB_Order_Manager::get_modules(null, true);
        $trainings = ThemisDB_Order_Manager::get_training_modules(null, true);

        $edit_item = null;
        if ($edit_id > 0) {
            if ($entity === 'product') {
                $edit_item = ThemisDB_Order_Manager::get_product($edit_id, true);
            } elseif ($entity === 'module') {
                $edit_item = ThemisDB_Order_Manager::get_module($edit_id, true);
            } elseif ($entity === 'training') {
                $edit_item = ThemisDB_Order_Manager::get_training_module($edit_id, true);
            }
        }

        ?>
        <div class="wrap">
            <h1><?php _e('Produkte und Module (CRUD)', 'themisdb-order-request'); ?></h1>

            <?php if (isset($_GET['message']) && $_GET['message'] === 'saved'): ?>
            <div class="notice notice-success"><p><?php _e('Datensatz wurde gespeichert.', 'themisdb-order-request'); ?></p></div>
            <?php endif; ?>
            <?php if (isset($_GET['message']) && $_GET['message'] === 'deleted'): ?>
            <div class="notice notice-success"><p><?php _e('Datensatz wurde deaktiviert.', 'themisdb-order-request'); ?></p></div>
            <?php endif; ?>
            <?php if (isset($_GET['message']) && $_GET['message'] === 'status_saved'): ?>
            <div class="notice notice-success"><p><?php _e('Status wurde aktualisiert.', 'themisdb-order-request'); ?></p></div>
            <?php endif; ?>

            <div class="card" style="max-width:none;">
                <h2><?php echo $edit_item ? __('Datensatz bearbeiten', 'themisdb-order-request') : __('Neuen Datensatz anlegen', 'themisdb-order-request'); ?></h2>
                <form method="post">
                    <?php wp_nonce_field('themisdb_catalog_save_item'); ?>
                    <input type="hidden" name="action" value="catalog_save_item">
                    <input type="hidden" name="item_id" value="<?php echo $edit_item ? absint($edit_item['id']) : 0; ?>">

                    <table class="form-table">
                        <tr>
                            <th><label for="catalog_entity"><?php _e('Typ', 'themisdb-order-request'); ?></label></th>
                            <td>
                                <select id="catalog_entity" name="entity">
                                    <option value="product" <?php selected($entity, 'product'); ?>><?php _e('Produkt/SKU', 'themisdb-order-request'); ?></option>
                                    <option value="module" <?php selected($entity, 'module'); ?>><?php _e('Modul', 'themisdb-order-request'); ?></option>
                                    <option value="training" <?php selected($entity, 'training'); ?>><?php _e('Schulung', 'themisdb-order-request'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="catalog_code"><?php _e('Code', 'themisdb-order-request'); ?></label></th>
                            <td><input id="catalog_code" name="product_code" class="regular-text" value="<?php echo esc_attr($edit_item['product_code'] ?? $edit_item['module_code'] ?? $edit_item['training_code'] ?? ''); ?>" required></td>
                        </tr>
                        <tr>
                            <th><label for="catalog_name"><?php _e('Name', 'themisdb-order-request'); ?></label></th>
                            <td><input id="catalog_name" name="product_name" class="regular-text" value="<?php echo esc_attr($edit_item['product_name'] ?? $edit_item['module_name'] ?? $edit_item['training_name'] ?? ''); ?>" required></td>
                        </tr>
                        <tr>
                            <th><label for="catalog_type"><?php _e('Produkttyp/Kategorie', 'themisdb-order-request'); ?></label></th>
                            <td>
                                <input id="catalog_type" name="product_type" class="regular-text" value="<?php echo esc_attr($edit_item['product_type'] ?? $edit_item['module_category'] ?? $edit_item['training_type'] ?? 'database'); ?>">
                                <p class="description"><?php _e('Beispiele: database, module_license, plugin, support_sku, merchandise, online, onsite', 'themisdb-order-request'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="catalog_edition"><?php _e('Edition / Gruppe', 'themisdb-order-request'); ?></label></th>
                            <td><input id="catalog_edition" name="edition" class="regular-text" value="<?php echo esc_attr($edit_item['edition'] ?? ''); ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="catalog_duration"><?php _e('Dauer (Stunden, nur Schulung)', 'themisdb-order-request'); ?></label></th>
                            <td><input id="catalog_duration" type="number" min="0" name="duration_hours" value="<?php echo isset($edit_item['duration_hours']) ? absint($edit_item['duration_hours']) : ''; ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="catalog_price"><?php _e('Preis', 'themisdb-order-request'); ?></label></th>
                            <td><input id="catalog_price" type="number" min="0" step="0.01" name="price" value="<?php echo esc_attr($edit_item['price'] ?? '0.00'); ?>" required></td>
                        </tr>
                        <tr>
                            <th><label for="catalog_currency"><?php _e('Währung', 'themisdb-order-request'); ?></label></th>
                            <td><input id="catalog_currency" name="currency" value="<?php echo esc_attr($edit_item['currency'] ?? 'EUR'); ?>" maxlength="10"></td>
                        </tr>
                        <tr>
                            <th><label for="catalog_description"><?php _e('Beschreibung', 'themisdb-order-request'); ?></label></th>
                            <td><textarea id="catalog_description" name="description" rows="3" class="large-text"><?php echo esc_textarea($edit_item['description'] ?? ''); ?></textarea></td>
                        </tr>
                        <tr>
                            <th><?php _e('Aktiv', 'themisdb-order-request'); ?></th>
                            <td><label><input type="checkbox" name="is_active" value="1" <?php checked(isset($edit_item['is_active']) ? intval($edit_item['is_active']) : 1, 1); ?>> <?php _e('Datensatz aktiv', 'themisdb-order-request'); ?></label></td>
                        </tr>
                    </table>

                    <p>
                        <button type="submit" class="button button-primary"><?php _e('Speichern', 'themisdb-order-request'); ?></button>
                        <?php if ($edit_item): ?>
                        <a href="?page=themisdb-products" class="button"><?php _e('Abbrechen', 'themisdb-order-request'); ?></a>
                        <?php endif; ?>
                    </p>
                </form>
            </div>

            <div class="card">
                <h2><?php _e('Produkte/SKUs', 'themisdb-order-request'); ?></h2>
                <table class="wp-list-table widefat striped">
                    <thead>
                        <tr>
                            <th><?php _e('Code', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Name', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Typ', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Edition', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Preis', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Status', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Aktionen', 'themisdb-order-request'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo esc_html($product['product_code']); ?></td>
                            <td><?php echo esc_html($product['product_name']); ?></td>
                            <td><?php echo esc_html($product['product_type']); ?></td>
                            <td><?php echo esc_html($product['edition']); ?></td>
                            <td><?php echo number_format($product['price'], 2, ',', '.'); ?> <?php echo esc_html($product['currency']); ?></td>
                            <td><?php echo !empty($product['is_active']) ? __('Aktiv', 'themisdb-order-request') : __('Inaktiv', 'themisdb-order-request'); ?></td>
                            <td>
                                <a class="button button-small" href="?page=themisdb-products&entity=product&edit_id=<?php echo absint($product['id']); ?>"><?php _e('Bearbeiten', 'themisdb-order-request'); ?></a>
                                <form method="post" style="display:inline;">
                                    <?php wp_nonce_field('themisdb_catalog_toggle_item'); ?>
                                    <input type="hidden" name="action" value="catalog_toggle_item">
                                    <input type="hidden" name="entity" value="product">
                                    <input type="hidden" name="item_id" value="<?php echo absint($product['id']); ?>">
                                    <input type="hidden" name="is_active" value="<?php echo !empty($product['is_active']) ? '0' : '1'; ?>">
                                    <button type="submit" class="button button-small"><?php echo !empty($product['is_active']) ? __('Deaktivieren', 'themisdb-order-request') : __('Aktivieren', 'themisdb-order-request'); ?></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h2><?php _e('Module', 'themisdb-order-request'); ?></h2>
                <table class="wp-list-table widefat striped">
                    <thead>
                        <tr>
                            <th><?php _e('Code', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Name', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Kategorie', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Preis', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Status', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Aktionen', 'themisdb-order-request'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modules as $module): ?>
                        <tr>
                            <td><?php echo esc_html($module['module_code']); ?></td>
                            <td><?php echo esc_html($module['module_name']); ?></td>
                            <td><?php echo esc_html($module['module_category']); ?></td>
                            <td><?php echo number_format($module['price'], 2, ',', '.'); ?> <?php echo esc_html($module['currency']); ?></td>
                            <td><?php echo !empty($module['is_active']) ? __('Aktiv', 'themisdb-order-request') : __('Inaktiv', 'themisdb-order-request'); ?></td>
                            <td>
                                <a class="button button-small" href="?page=themisdb-products&entity=module&edit_id=<?php echo absint($module['id']); ?>"><?php _e('Bearbeiten', 'themisdb-order-request'); ?></a>
                                <form method="post" style="display:inline;">
                                    <?php wp_nonce_field('themisdb_catalog_toggle_item'); ?>
                                    <input type="hidden" name="action" value="catalog_toggle_item">
                                    <input type="hidden" name="entity" value="module">
                                    <input type="hidden" name="item_id" value="<?php echo absint($module['id']); ?>">
                                    <input type="hidden" name="is_active" value="<?php echo !empty($module['is_active']) ? '0' : '1'; ?>">
                                    <button type="submit" class="button button-small"><?php echo !empty($module['is_active']) ? __('Deaktivieren', 'themisdb-order-request') : __('Aktivieren', 'themisdb-order-request'); ?></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h2><?php _e('Schulungen', 'themisdb-order-request'); ?></h2>
                <table class="wp-list-table widefat striped">
                    <thead>
                        <tr>
                            <th><?php _e('Code', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Name', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Typ', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Dauer', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Preis', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Status', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Aktionen', 'themisdb-order-request'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trainings as $training): ?>
                        <tr>
                            <td><?php echo esc_html($training['training_code']); ?></td>
                            <td><?php echo esc_html($training['training_name']); ?></td>
                            <td><?php echo esc_html($training['training_type']); ?></td>
                            <td><?php echo absint($training['duration_hours']); ?> h</td>
                            <td><?php echo number_format($training['price'], 2, ',', '.'); ?> <?php echo esc_html($training['currency']); ?></td>
                            <td><?php echo !empty($training['is_active']) ? __('Aktiv', 'themisdb-order-request') : __('Inaktiv', 'themisdb-order-request'); ?></td>
                            <td>
                                <a class="button button-small" href="?page=themisdb-products&entity=training&edit_id=<?php echo absint($training['id']); ?>"><?php _e('Bearbeiten', 'themisdb-order-request'); ?></a>
                                <form method="post" style="display:inline;">
                                    <?php wp_nonce_field('themisdb_catalog_toggle_item'); ?>
                                    <input type="hidden" name="action" value="catalog_toggle_item">
                                    <input type="hidden" name="entity" value="training">
                                    <input type="hidden" name="item_id" value="<?php echo absint($training['id']); ?>">
                                    <input type="hidden" name="is_active" value="<?php echo !empty($training['is_active']) ? '0' : '1'; ?>">
                                    <button type="submit" class="button button-small"><?php echo !empty($training['is_active']) ? __('Deaktivieren', 'themisdb-order-request') : __('Aktivieren', 'themisdb-order-request'); ?></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <script>
        (function() {
            const entitySelect = document.getElementById('catalog_entity');
            if (!entitySelect) return;

            const codeInput = document.getElementById('catalog_code');
            const nameInput = document.getElementById('catalog_name');
            const typeInput = document.getElementById('catalog_type');

            entitySelect.addEventListener('change', function() {
                if (this.value === 'module') {
                    codeInput.name = 'module_code';
                    nameInput.name = 'module_name';
                    typeInput.name = 'module_category';
                } else if (this.value === 'training') {
                    codeInput.name = 'training_code';
                    nameInput.name = 'training_name';
                    typeInput.name = 'training_type';
                } else {
                    codeInput.name = 'product_code';
                    nameInput.name = 'product_name';
                    typeInput.name = 'product_type';
                }
            });

            entitySelect.dispatchEvent(new Event('change'));
        })();
        </script>
        <?php
    }

    /**
     * Basic inventory admin page.
     */
    public function inventory_page() {
        global $wpdb;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_inventory') {
            check_admin_referer('themisdb_save_inventory');

            $sku = isset($_POST['sku']) ? sanitize_text_field($_POST['sku']) : '';
            $product_name = isset($_POST['product_name']) ? sanitize_text_field($_POST['product_name']) : '';
            $stock_on_hand = isset($_POST['stock_on_hand']) ? intval($_POST['stock_on_hand']) : 0;
            $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : null;
            $reorder_level = isset($_POST['reorder_level']) ? intval($_POST['reorder_level']) : 0;

            if ($sku && $product_name) {
                ThemisDB_Order_Manager::set_inventory_stock($sku, $product_name, $stock_on_hand, $product_id, $reorder_level);
                wp_redirect(admin_url('admin.php?page=themisdb-inventory&message=saved'));
                exit;
            }
        }

        $table_inventory = $wpdb->prefix . 'themisdb_inventory_stock';
        $inventory_items = $wpdb->get_results("SELECT * FROM $table_inventory ORDER BY product_name ASC", ARRAY_A);
        $products = ThemisDB_Order_Manager::get_products();
        ?>
        <div class="wrap">
            <h1><?php _e('Lagerbestand', 'themisdb-order-request'); ?></h1>

            <?php if (isset($_GET['message']) && $_GET['message'] === 'saved'): ?>
            <div class="notice notice-success"><p><?php _e('Lagerbestand wurde gespeichert.', 'themisdb-order-request'); ?></p></div>
            <?php endif; ?>

            <div class="card" style="max-width:none; margin-bottom:20px;">
                <h2><?php _e('Bestand anlegen oder aktualisieren', 'themisdb-order-request'); ?></h2>
                <form method="post">
                    <?php wp_nonce_field('themisdb_save_inventory'); ?>
                    <input type="hidden" name="action" value="save_inventory">
                    <table class="form-table">
                        <tr>
                            <th><label for="product_id"><?php _e('Produkt', 'themisdb-order-request'); ?></label></th>
                            <td>
                                <select id="product_id" name="product_id">
                                    <option value=""><?php _e('— Optional verknüpfen —', 'themisdb-order-request'); ?></option>
                                    <?php foreach ($products as $product): ?>
                                    <option value="<?php echo absint($product['id']); ?>"><?php echo esc_html($product['product_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="sku"><?php _e('SKU', 'themisdb-order-request'); ?></label></th>
                            <td><input type="text" id="sku" name="sku" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="product_name"><?php _e('Artikelname', 'themisdb-order-request'); ?></label></th>
                            <td><input type="text" id="product_name" name="product_name" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="stock_on_hand"><?php _e('Bestand', 'themisdb-order-request'); ?></label></th>
                            <td><input type="number" id="stock_on_hand" name="stock_on_hand" value="0" min="0"></td>
                        </tr>
                        <tr>
                            <th><label for="reorder_level"><?php _e('Meldebestand', 'themisdb-order-request'); ?></label></th>
                            <td><input type="number" id="reorder_level" name="reorder_level" value="0" min="0"></td>
                        </tr>
                    </table>
                    <p><button type="submit" class="button button-primary"><?php _e('Bestand speichern', 'themisdb-order-request'); ?></button></p>
                </form>
            </div>

            <div class="card" style="max-width:none;">
                <h2><?php _e('Aktueller Lagerbestand', 'themisdb-order-request'); ?></h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('SKU', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Artikel', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Bestand', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Reserviert', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Verfügbar', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Meldebestand', 'themisdb-order-request'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($inventory_items)): ?>
                        <tr>
                            <td colspan="6"><?php _e('Noch keine Lagerartikel vorhanden.', 'themisdb-order-request'); ?></td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($inventory_items as $item): ?>
                            <?php $available = intval($item['stock_on_hand']) - intval($item['reserved_stock']); ?>
                            <tr>
                                <td><?php echo esc_html($item['sku']); ?></td>
                                <td><?php echo esc_html($item['product_name']); ?></td>
                                <td><?php echo intval($item['stock_on_hand']); ?></td>
                                <td><?php echo intval($item['reserved_stock']); ?></td>
                                <td><?php echo intval($available); ?></td>
                                <td>
                                    <?php if ($available <= intval($item['reorder_level'])): ?>
                                        <span style="color:#b32d2e;font-weight:bold;"><?php echo intval($item['reorder_level']); ?></span>
                                    <?php else: ?>
                                        <?php echo intval($item['reorder_level']); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    /**
     * Payments page
     */
    public function payments_page() {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $payment_id = isset($_GET['payment_id']) ? intval($_GET['payment_id']) : 0;
        
        // Handle payment verification
        if ($action === 'verify' && $payment_id && check_admin_referer('verify_payment_' . $payment_id)) {
            ThemisDB_Payment_Manager::verify_payment($payment_id);
            wp_redirect(admin_url('admin.php?page=themisdb-payments&verified=1'));
            exit;
        }
        
        if ($action === 'view' && $payment_id) {
            $this->view_payment($payment_id);
        } else {
            $this->list_payments();
        }
    }
    
    /**
     * List payments
     */
    private function list_payments() {
        $payments = ThemisDB_Payment_Manager::get_all_payments();
        $stats = ThemisDB_Payment_Manager::get_payment_stats();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Zahlungen', 'themisdb-order-request'); ?></h1>
            
            <?php if (isset($_GET['verified'])): ?>
            <div class="notice notice-success"><p><?php _e('Zahlung wurde erfolgreich verifiziert', 'themisdb-order-request'); ?></p></div>
            <?php endif; ?>
            
            <div class="card" style="max-width: none; margin-bottom: 20px;">
                <h2><?php _e('Zahlungsübersicht', 'themisdb-order-request'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Gesamt Zahlungen', 'themisdb-order-request'); ?>:</th>
                        <td><strong><?php echo $stats['total_payments']; ?></strong></td>
                        <th><?php _e('Gesamtbetrag', 'themisdb-order-request'); ?>:</th>
                        <td><strong><?php echo number_format($stats['total_amount'], 2, ',', '.'); ?> €</strong></td>
                    </tr>
                    <tr>
                        <th><?php _e('Verifiziert', 'themisdb-order-request'); ?>:</th>
                        <td><span style="color: green;"><strong><?php echo $stats['verified_payments']; ?></strong></span></td>
                        <th><?php _e('Verifizierter Betrag', 'themisdb-order-request'); ?>:</th>
                        <td><span style="color: green;"><strong><?php echo number_format($stats['verified_amount'], 2, ',', '.'); ?> €</strong></span></td>
                    </tr>
                    <tr>
                        <th><?php _e('Ausstehend', 'themisdb-order-request'); ?>:</th>
                        <td><span style="color: orange;"><strong><?php echo $stats['pending_payments']; ?></strong></span></td>
                        <th><?php _e('Fehlgeschlagen', 'themisdb-order-request'); ?>:</th>
                        <td><span style="color: red;"><strong><?php echo $stats['failed_payments']; ?></strong></span></td>
                    </tr>
                </table>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Zahlungsnummer', 'themisdb-order-request'); ?></th>
                        <th><?php _e('Bestellung', 'themisdb-order-request'); ?></th>
                        <th><?php _e('Betrag', 'themisdb-order-request'); ?></th>
                        <th><?php _e('Methode', 'themisdb-order-request'); ?></th>
                        <th><?php _e('Status', 'themisdb-order-request'); ?></th>
                        <th><?php _e('Datum', 'themisdb-order-request'); ?></th>
                        <th><?php _e('Aktionen', 'themisdb-order-request'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payments)): ?>
                    <tr>
                        <td colspan="7"><?php _e('Keine Zahlungen gefunden', 'themisdb-order-request'); ?></td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($payments as $payment): ?>
                        <?php $order = ThemisDB_Order_Manager::get_order($payment['order_id']); ?>
                        <tr>
                            <td><strong><?php echo esc_html($payment['payment_number']); ?></strong></td>
                            <td>
                                <?php if ($order): ?>
                                    <a href="?page=themisdb-orders&action=view&order_id=<?php echo absint($order['id']); ?>">
                                        <?php echo esc_html($order['order_number']); ?>
                                    </a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?php echo number_format($payment['amount'], 2, ',', '.'); ?> <?php echo esc_html($payment['currency']); ?></td>
                            <td><?php echo esc_html(ucfirst($payment['payment_method'])); ?></td>
                            <td>
                                <span class="payment-status status-<?php echo esc_attr($payment['payment_status']); ?>">
                                    <?php echo esc_html(ucfirst($payment['payment_status'])); ?>
                                </span>
                            </td>
                            <td><?php echo date('d.m.Y H:i', strtotime($payment['created_at'])); ?></td>
                            <td>
                                <a href="?page=themisdb-payments&action=view&payment_id=<?php echo $payment['id']; ?>" class="button button-small">
                                    <?php _e('Ansehen', 'themisdb-order-request'); ?>
                                </a>
                                <?php if ($payment['payment_status'] === 'pending'): ?>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=themisdb-payments&action=verify&payment_id=' . $payment['id']), 'verify_payment_' . $payment['id']); ?>" 
                                   class="button button-small button-primary">
                                    <?php _e('Verifizieren', 'themisdb-order-request'); ?>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * View single payment
     */
    private function view_payment($payment_id) {
        $payment = ThemisDB_Payment_Manager::get_payment($payment_id);
        
        if (!$payment) {
            echo '<div class="notice notice-error"><p>' . __('Zahlung nicht gefunden', 'themisdb-order-request') . '</p></div>';
            return;
        }
        
        $order = ThemisDB_Order_Manager::get_order($payment['order_id']);
        
        ?>
        <div class="wrap">
            <h1><?php _e('Zahlung', 'themisdb-order-request'); ?>: <?php echo esc_html($payment['payment_number']); ?></h1>
            
            <div class="card">
                <h2><?php _e('Zahlungsdetails', 'themisdb-order-request'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Zahlungsnummer', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html($payment['payment_number']); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Betrag', 'themisdb-order-request'); ?>:</th>
                        <td><strong><?php echo number_format($payment['amount'], 2, ',', '.'); ?> <?php echo esc_html($payment['currency']); ?></strong></td>
                    </tr>
                    <tr>
                        <th><?php _e('Zahlungsmethode', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html(ucfirst($payment['payment_method'])); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Status', 'themisdb-order-request'); ?>:</th>
                        <td>
                            <span class="payment-status status-<?php echo esc_attr($payment['payment_status']); ?>">
                                <?php echo esc_html(ucfirst($payment['payment_status'])); ?>
                            </span>
                        </td>
                    </tr>
                    <?php if ($payment['transaction_id']): ?>
                    <tr>
                        <th><?php _e('Transaktions-ID', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html($payment['transaction_id']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($payment['payment_date']): ?>
                    <tr>
                        <th><?php _e('Zahlungsdatum', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo date('d.m.Y H:i', strtotime($payment['payment_date'])); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($payment['verified_at']): ?>
                    <tr>
                        <th><?php _e('Verifiziert am', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo date('d.m.Y H:i', strtotime($payment['verified_at'])); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th><?php _e('Erstellt am', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo date('d.m.Y H:i', strtotime($payment['created_at'])); ?></td>
                    </tr>
                    <?php if ($payment['notes']): ?>
                    <tr>
                        <th><?php _e('Notizen', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html($payment['notes']); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
            
            <?php if ($order): ?>
            <div class="card">
                <h2><?php _e('Zugehörige Bestellung', 'themisdb-order-request'); ?></h2>
                <p>
                    <strong><?php _e('Bestellnummer', 'themisdb-order-request'); ?>:</strong> <?php echo esc_html($order['order_number']); ?><br>
                    <strong><?php _e('Kunde', 'themisdb-order-request'); ?>:</strong> <?php echo esc_html($order['customer_name']); ?><br>
                    <a href="?page=themisdb-orders&action=view&order_id=<?php echo absint($order['id']); ?>" class="button button-small">
                        <?php _e('Bestellung ansehen', 'themisdb-order-request'); ?>
                    </a>
                </p>
            </div>
            <?php endif; ?>
            
            <p>
                <a href="?page=themisdb-payments" class="button"><?php _e('Zurück zur Übersicht', 'themisdb-order-request'); ?></a>
                <?php if ($payment['payment_status'] === 'pending'): ?>
                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=themisdb-payments&action=verify&payment_id=' . $payment['id']), 'verify_payment_' . $payment['id']); ?>" 
                   class="button button-primary">
                    <?php _e('Zahlung verifizieren', 'themisdb-order-request'); ?>
                </a>
                <?php endif; ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * Licenses page
     */
    public function licenses_page() {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $license_id = isset($_GET['license_id']) ? intval($_GET['license_id']) : 0;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $post_action = sanitize_text_field($_POST['action']);

            if ($post_action === 'save_license' && isset($_POST['license_id'])) {
                check_admin_referer('themisdb_save_license');
                $this->handle_save_license(absint($_POST['license_id']));
                return;
            }

            if ($post_action === 'change_license_status' && isset($_POST['license_id'])) {
                check_admin_referer('themisdb_change_license_status');
                $this->handle_change_license_status(absint($_POST['license_id']), sanitize_text_field($_POST['new_status'] ?? ''));
                return;
            }

            if ($post_action === 'delete_license' && isset($_POST['license_id'])) {
                check_admin_referer('themisdb_delete_license');
                $this->handle_delete_license(absint($_POST['license_id']));
                return;
            }
        }
        
        // Handle cancellation action
        if ($action === 'cancel' && $license_id && check_admin_referer('cancel_license_' . $license_id)) {
            $reason = isset($_POST['cancel_reason']) ? sanitize_textarea_field($_POST['cancel_reason']) : '';
            $result = ThemisDB_License_Manager::cancel_license($license_id, $reason, get_current_user_id());
            if ($result) {
                // Send cancellation email
                ThemisDB_Email_Handler::send_cancellation_email($license_id);
                wp_redirect(admin_url('admin.php?page=themisdb-licenses&action=view&license_id=' . $license_id . '&cancelled=1'));
            } else {
                wp_redirect(admin_url('admin.php?page=themisdb-licenses&action=view&license_id=' . $license_id . '&cancel_error=1'));
            }
            exit;
        }
        
        if ($action === 'new') {
            $this->create_license();
        } elseif ($action === 'edit' && $license_id) {
            $this->edit_license($license_id);
        } elseif ($action === 'view' && $license_id) {
            $this->view_license($license_id);
        } else {
            $this->list_licenses();
        }
    }
    
    /**
     * List licenses
     */
    private function list_licenses() {
        global $wpdb;
        $table_licenses = $wpdb->prefix . 'themisdb_licenses';
        // Use esc_sql to ensure table name is safe, and use prepare for the entire query
        $licenses = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}themisdb_licenses ORDER BY created_at DESC LIMIT %d",
                50
            ),
            ARRAY_A
        );
        $stats = ThemisDB_License_Manager::get_license_stats();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Lizenzen', 'themisdb-order-request'); ?></h1>

            <div style="margin-bottom:1rem;">
                <a class="button button-primary" href="?page=themisdb-licenses&action=new"><?php _e('+ Neue Lizenz', 'themisdb-order-request'); ?></a>
            </div>

            <?php if (isset($_GET['message']) && $_GET['message'] === 'deleted'): ?>
            <div class="notice notice-success"><p><?php _e('Lizenz wurde gelöscht.', 'themisdb-order-request'); ?></p></div>
            <?php endif; ?>
            
            <div class="card" style="max-width: none; margin-bottom: 20px;">
                <h2><?php _e('Lizenzübersicht', 'themisdb-order-request'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Gesamt Lizenzen', 'themisdb-order-request'); ?>:</th>
                        <td><strong><?php echo $stats['total_licenses']; ?></strong></td>
                        <th><?php _e('Aktiv', 'themisdb-order-request'); ?>:</th>
                        <td><span style="color: green;"><strong><?php echo $stats['active_licenses']; ?></strong></span></td>
                    </tr>
                    <tr>
                        <th><?php _e('Ausstehend', 'themisdb-order-request'); ?>:</th>
                        <td><span style="color: orange;"><strong><?php echo $stats['pending_licenses']; ?></strong></span></td>
                        <th><?php _e('Suspendiert', 'themisdb-order-request'); ?>:</th>
                        <td><span style="color: #856404;"><strong><?php echo $stats['suspended_licenses']; ?></strong></span></td>
                    </tr>
                    <tr>
                        <th><?php _e('Abgelaufen', 'themisdb-order-request'); ?>:</th>
                        <td><span style="color: #3c434a;"><strong><?php echo $stats['expired_licenses']; ?></strong></span></td>
                        <th><?php _e('Gekündigt', 'themisdb-order-request'); ?>:</th>
                        <td><span style="color: #721c24;"><strong><?php echo $stats['cancelled_licenses']; ?></strong></span></td>
                    </tr>
                </table>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Lizenzschlüssel', 'themisdb-order-request'); ?></th>
                        <th><?php _e('Edition', 'themisdb-order-request'); ?></th>
                        <th><?php _e('Kunde', 'themisdb-order-request'); ?></th>
                        <th><?php _e('Status', 'themisdb-order-request'); ?></th>
                        <th><?php _e('Aktiviert', 'themisdb-order-request'); ?></th>
                        <th><?php _e('Läuft ab', 'themisdb-order-request'); ?></th>
                        <th><?php _e('Aktionen', 'themisdb-order-request'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($licenses)): ?>
                    <tr>
                        <td colspan="7"><?php _e('Keine Lizenzen gefunden', 'themisdb-order-request'); ?></td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($licenses as $license): ?>
                        <?php $order = ThemisDB_Order_Manager::get_order($license['order_id']); ?>
                        <tr>
                            <td><code style="font-size: 10px;"><?php echo esc_html(substr($license['license_key'], 0, 20)); ?>...</code></td>
                            <td><?php echo esc_html(ucfirst($license['product_edition'])); ?></td>
                            <td>
                                <?php if ($order): ?>
                                    <?php echo esc_html($order['customer_name']); ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="license-status status-<?php echo esc_attr($license['license_status']); ?>">
                                    <?php echo esc_html(ucfirst($license['license_status'])); ?>
                                </span>
                            </td>
                            <td><?php echo $license['activation_date'] ? date('d.m.Y', strtotime($license['activation_date'])) : '-'; ?></td>
                            <td><?php echo $license['expiry_date'] ? date('d.m.Y', strtotime($license['expiry_date'])) : '∞'; ?></td>
                            <td>
                                <a href="?page=themisdb-licenses&action=view&license_id=<?php echo $license['id']; ?>" class="button button-small">
                                    <?php _e('Ansehen', 'themisdb-order-request'); ?>
                                </a>
                                <a href="?page=themisdb-licenses&action=edit&license_id=<?php echo $license['id']; ?>" class="button button-small">
                                    <?php _e('Bearbeiten', 'themisdb-order-request'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * View single license
     */
    private function view_license($license_id) {
        $license = ThemisDB_License_Manager::get_license($license_id);
        
        if (!$license) {
            echo '<div class="notice notice-error"><p>' . __('Lizenz nicht gefunden', 'themisdb-order-request') . '</p></div>';
            return;
        }
        
        $order = ThemisDB_Order_Manager::get_order($license['order_id']);
        $contract = ThemisDB_Contract_Manager::get_contract($license['contract_id']);
        $is_cancelled = ($license['license_status'] === 'cancelled');
        
        ?>
        <div class="wrap">
            <h1><?php _e('Lizenz', 'themisdb-order-request'); ?>: <?php echo esc_html($license['product_edition']); ?></h1>
            
            <?php if (isset($_GET['cancelled'])): ?>
            <div class="notice notice-success"><p><?php _e('Die Lizenz wurde erfolgreich gekündigt. Der Kunde wurde per E-Mail informiert.', 'themisdb-order-request'); ?></p></div>
            <?php endif; ?>
            
            <?php if (isset($_GET['cancel_error'])): ?>
            <div class="notice notice-error"><p><?php _e('Fehler beim Kündigen der Lizenz. Bitte versuchen Sie es erneut.', 'themisdb-order-request'); ?></p></div>
            <?php endif; ?>

            <?php if (isset($_GET['message']) && $_GET['message'] === 'saved'): ?>
            <div class="notice notice-success"><p><?php _e('Lizenzdaten wurden gespeichert.', 'themisdb-order-request'); ?></p></div>
            <?php endif; ?>

            <?php if (isset($_GET['message']) && $_GET['message'] === 'status_saved'): ?>
            <div class="notice notice-success"><p><?php _e('Lizenzstatus wurde aktualisiert.', 'themisdb-order-request'); ?></p></div>
            <?php endif; ?>
            
            <div class="card">
                <h2><?php _e('Lizenzdetails', 'themisdb-order-request'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Lizenzschlüssel', 'themisdb-order-request'); ?>:</th>
                        <td><code><?php echo esc_html($license['license_key']); ?></code></td>
                    </tr>
                    <tr>
                        <th><?php _e('Edition', 'themisdb-order-request'); ?>:</th>
                        <td><strong><?php echo esc_html(ucfirst($license['product_edition'])); ?></strong></td>
                    </tr>
                    <tr>
                        <th><?php _e('Lizenztyp', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html(ucfirst($license['license_type'])); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Status', 'themisdb-order-request'); ?>:</th>
                        <td>
                            <span class="license-status status-<?php echo esc_attr($license['license_status']); ?>">
                                <?php echo esc_html(ucfirst($license['license_status'])); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Max. Nodes', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo $license['max_nodes'] ? $license['max_nodes'] : '∞'; ?></td>
                    </tr>
                    <?php if ($license['max_cores']): ?>
                    <tr>
                        <th><?php _e('Max. Cores', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo $license['max_cores']; ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($license['max_storage_gb']): ?>
                    <tr>
                        <th><?php _e('Max. Storage', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo $license['max_storage_gb']; ?> GB</td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($license['activation_date']): ?>
                    <tr>
                        <th><?php _e('Aktivierungsdatum', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo date('d.m.Y H:i', strtotime($license['activation_date'])); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($license['expiry_date']): ?>
                    <tr>
                        <th><?php _e('Ablaufdatum', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo date('d.m.Y', strtotime($license['expiry_date'])); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($license['last_check']): ?>
                    <tr>
                        <th><?php _e('Letzte Prüfung', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo date('d.m.Y H:i', strtotime($license['last_check'])); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th><?php _e('Erstellt am', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo date('d.m.Y H:i', strtotime($license['created_at'])); ?></td>
                    </tr>
                </table>
            </div>
            
            <?php if ($is_cancelled): ?>
            <div class="card" style="border-left: 4px solid #721c24;">
                <h2 style="color:#721c24;"><?php _e('Kündigungsdetails', 'themisdb-order-request'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Gekündigt am', 'themisdb-order-request'); ?>:</th>
                        <td><strong><?php echo $license['cancellation_date'] ? esc_html(date('d.m.Y H:i', strtotime($license['cancellation_date']))) : '—'; ?></strong></td>
                    </tr>
                    <tr>
                        <th><?php _e('Kündigungsgrund', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html($license['cancellation_reason'] ?: '—'); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Gekündigt von', 'themisdb-order-request'); ?>:</th>
                        <td>
                            <?php
                            if ($license['cancelled_by']) {
                                $user = get_user_by('id', $license['cancelled_by']);
                                echo $user ? esc_html($user->display_name) : esc_html('User #' . $license['cancelled_by']);
                            } else {
                                echo '—';
                            }
                            ?>
                        </td>
                    </tr>
                </table>
            </div>
            <?php endif; ?>
            
            <?php if ($order): ?>
            <div class="card">
                <h2><?php _e('Zugehörige Bestellung', 'themisdb-order-request'); ?></h2>
                <p>
                    <strong><?php _e('Bestellnummer', 'themisdb-order-request'); ?>:</strong> <?php echo esc_html($order['order_number']); ?><br>
                    <strong><?php _e('Kunde', 'themisdb-order-request'); ?>:</strong> <?php echo esc_html($order['customer_name']); ?><br>
                    <strong><?php _e('E-Mail', 'themisdb-order-request'); ?>:</strong> <?php echo esc_html($order['customer_email']); ?><br>
                    <a href="?page=themisdb-orders&action=view&order_id=<?php echo absint($order['id']); ?>" class="button button-small">
                        <?php _e('Bestellung ansehen', 'themisdb-order-request'); ?>
                    </a>
                </p>
            </div>
            <?php endif; ?>
            
            <?php if ($contract): ?>
            <div class="card">
                <h2><?php _e('Zugehöriger Vertrag', 'themisdb-order-request'); ?></h2>
                <p>
                    <strong><?php _e('Vertragsnummer', 'themisdb-order-request'); ?>:</strong> <?php echo esc_html($contract['contract_number']); ?><br>
                    <strong><?php _e('Status', 'themisdb-order-request'); ?>:</strong> <?php echo esc_html(ucfirst($contract['status'])); ?><br>
                    <a href="?page=themisdb-contracts&action=view&contract_id=<?php echo absint($contract['id']); ?>" class="button button-small">
                        <?php _e('Vertrag ansehen', 'themisdb-order-request'); ?>
                    </a>
                </p>
            </div>
            <?php endif; ?>
            
            <?php if ($license['license_file_data']): ?>
            <div class="card">
                <h2><?php _e('Lizenzdatei', 'themisdb-order-request'); ?></h2>
                <p><?php _e('Die Lizenzdatei wurde generiert und kann für die Authentifizierung verwendet werden.', 'themisdb-order-request'); ?></p>
                <textarea readonly style="width: 100%; height: 200px; font-family: monospace; font-size: 12px;"><?php echo esc_textarea(json_encode($license['license_file_data'], JSON_PRETTY_PRINT)); ?></textarea>
            </div>
            <?php endif; ?>
            
            <p>
                <a href="?page=themisdb-licenses" class="button"><?php _e('Zurück zur Übersicht', 'themisdb-order-request'); ?></a>
                
                <?php if (!$is_cancelled): ?>
                <button type="button" id="btn-cancel-license" class="button button-cancel-license" style="margin-left:8px;">
                    <?php _e('Lizenz kündigen', 'themisdb-order-request'); ?>
                </button>
                <?php endif; ?>
            </p>
            
            <?php if (!$is_cancelled): ?>
            <!-- Cancellation confirmation modal -->
            <div id="cancel-license-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:99999; align-items:center; justify-content:center;">
                <div style="background:white; padding:30px; border-radius:4px; max-width:500px; width:90%; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
                    <h2 style="color:#721c24; margin-top:0;"><?php _e('Lizenz wirklich kündigen?', 'themisdb-order-request'); ?></h2>
                    <p style="color:#721c24; font-weight:bold;"><?php _e('⚠️ Diese Aktion ist unwiderruflich! Die Lizenz kann danach nicht mehr aktiviert werden.', 'themisdb-order-request'); ?></p>
                    <p><?php echo esc_html(sprintf(
                        __('Lizenzschlüssel: %s', 'themisdb-order-request'),
                        $license['license_key']
                    )); ?></p>
                    <form method="post" action="<?php echo esc_url(wp_nonce_url(
                        admin_url('admin.php?page=themisdb-licenses&action=cancel&license_id=' . absint($license_id)),
                        'cancel_license_' . $license_id
                    )); ?>">
                        <p>
                            <label for="cancel_reason"><strong><?php _e('Kündigungsgrund (optional):', 'themisdb-order-request'); ?></strong></label><br>
                            <textarea id="cancel_reason" name="cancel_reason" rows="3" style="width:100%; margin-top:5px;"
                                      placeholder="<?php esc_attr_e('z.B. Vertrag beendet, Zahlungsausfall, Kundenanfrage...', 'themisdb-order-request'); ?>"></textarea>
                        </p>
                        <p style="margin-bottom:0;">
                            <input type="submit" class="button button-cancel-license"
                                   value="<?php esc_attr_e('Ja, Lizenz endgültig kündigen', 'themisdb-order-request'); ?>" />
                            <button type="button" id="btn-cancel-modal" class="button" style="margin-left:8px;">
                                <?php _e('Abbrechen', 'themisdb-order-request'); ?>
                            </button>
                        </p>
                    </form>
                </div>
            </div>
            <script>
            (function($) {
                $('#btn-cancel-license').on('click', function() {
                    var $modal = $('#cancel-license-modal');
                    $modal.css('display', 'flex');
                });
                $('#btn-cancel-modal').on('click', function() {
                    $('#cancel-license-modal').hide();
                });
                // Close on backdrop click
                $('#cancel-license-modal').on('click', function(e) {
                    if ($(e.target).is('#cancel-license-modal')) {
                        $(this).hide();
                    }
                });
            })(jQuery);
            </script>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Edit single license form.
     */
    private function edit_license($license_id) {
        $license = ThemisDB_License_Manager::get_license($license_id);

        if (!$license) {
            echo '<div class="notice notice-error"><p>' . __('Lizenz nicht gefunden', 'themisdb-order-request') . '</p></div>';
            return;
        }

        ?>
        <div class="wrap">
            <h1><?php _e('Lizenz bearbeiten', 'themisdb-order-request'); ?></h1>

            <form method="post">
                <?php wp_nonce_field('themisdb_save_license'); ?>
                <input type="hidden" name="action" value="save_license">
                <input type="hidden" name="license_id" value="<?php echo absint($license_id); ?>">

                <div class="card">
                    <h2><?php _e('Stammdaten', 'themisdb-order-request'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><?php _e('Lizenzschlüssel', 'themisdb-order-request'); ?></th>
                            <td><code><?php echo esc_html($license['license_key']); ?></code></td>
                        </tr>
                        <tr>
                            <th><label for="license_type"><?php _e('Lizenztyp', 'themisdb-order-request'); ?></label></th>
                            <td><input id="license_type" name="license_type" class="regular-text" value="<?php echo esc_attr($license['license_type']); ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="max_nodes"><?php _e('Max. Nodes', 'themisdb-order-request'); ?></label></th>
                            <td><input id="max_nodes" type="number" name="max_nodes" value="<?php echo esc_attr($license['max_nodes']); ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="max_cores"><?php _e('Max. Cores', 'themisdb-order-request'); ?></label></th>
                            <td><input id="max_cores" type="number" name="max_cores" value="<?php echo esc_attr($license['max_cores']); ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="max_storage_gb"><?php _e('Max. Storage (GB)', 'themisdb-order-request'); ?></label></th>
                            <td><input id="max_storage_gb" type="number" name="max_storage_gb" value="<?php echo esc_attr($license['max_storage_gb']); ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="expiry_date"><?php _e('Ablaufdatum', 'themisdb-order-request'); ?></label></th>
                            <td><input id="expiry_date" type="date" name="expiry_date" value="<?php echo !empty($license['expiry_date']) ? esc_attr(date('Y-m-d', strtotime($license['expiry_date']))) : ''; ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="epserver_subscription_id"><?php _e('epServer Subscription-ID', 'themisdb-order-request'); ?></label></th>
                            <td><input id="epserver_subscription_id" name="epserver_subscription_id" class="regular-text" value="<?php echo esc_attr($license['epserver_subscription_id']); ?>"></td>
                        </tr>
                    </table>
                </div>

                <p>
                    <button type="submit" class="button button-primary"><?php _e('Lizenz speichern', 'themisdb-order-request'); ?></button>
                    <a href="?page=themisdb-licenses&action=view&license_id=<?php echo absint($license_id); ?>" class="button"><?php _e('Abbrechen', 'themisdb-order-request'); ?></a>
                </p>
            </form>

            <div class="card" style="margin-top:20px;">
                <h2><?php _e('Status-Aktionen', 'themisdb-order-request'); ?></h2>
                <p><?php _e('Aktueller Status:', 'themisdb-order-request'); ?> <strong><?php echo esc_html($license['license_status']); ?></strong></p>

                <?php if ($license['license_status'] !== 'cancelled'): ?>
                <form method="post" style="display:inline; margin-right:8px;">
                    <?php wp_nonce_field('themisdb_change_license_status'); ?>
                    <input type="hidden" name="action" value="change_license_status">
                    <input type="hidden" name="license_id" value="<?php echo absint($license_id); ?>">
                    <input type="hidden" name="new_status" value="active">
                    <button type="submit" class="button button-primary"><?php _e('Auf Aktiv setzen', 'themisdb-order-request'); ?></button>
                </form>

                <form method="post" style="display:inline; margin-right:8px;">
                    <?php wp_nonce_field('themisdb_change_license_status'); ?>
                    <input type="hidden" name="action" value="change_license_status">
                    <input type="hidden" name="license_id" value="<?php echo absint($license_id); ?>">
                    <input type="hidden" name="new_status" value="suspended">
                    <button type="submit" class="button"><?php _e('Suspendieren', 'themisdb-order-request'); ?></button>
                </form>
                <?php endif; ?>

                <form method="post" style="display:inline;" onsubmit="return confirm('<?php _e('Lizenz wirklich dauerhaft löschen?', 'themisdb-order-request'); ?>');">
                    <?php wp_nonce_field('themisdb_delete_license'); ?>
                    <input type="hidden" name="action" value="delete_license">
                    <input type="hidden" name="license_id" value="<?php echo absint($license_id); ?>">
                    <button type="submit" class="button button-link-delete"><?php _e('Lizenz löschen', 'themisdb-order-request'); ?></button>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Create new license form.
     */
    private function create_license() {
        ?>
        <div class="wrap">
            <h1><?php _e('Neue Lizenz anlegen', 'themisdb-order-request'); ?></h1>
            <form method="post">
                <?php wp_nonce_field('themisdb_save_license'); ?>
                <input type="hidden" name="action" value="save_license">
                <input type="hidden" name="license_id" value="0">

                <div class="card">
                    <h2><?php _e('Lizenzdaten', 'themisdb-order-request'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><label for="order_id"><?php _e('Order-ID', 'themisdb-order-request'); ?></label></th>
                            <td><input id="order_id" name="order_id" type="number" min="1" required></td>
                        </tr>
                        <tr>
                            <th><label for="contract_id"><?php _e('Contract-ID', 'themisdb-order-request'); ?></label></th>
                            <td><input id="contract_id" name="contract_id" type="number" min="1" required></td>
                        </tr>
                        <tr>
                            <th><label for="customer_id"><?php _e('Customer-ID', 'themisdb-order-request'); ?></label></th>
                            <td><input id="customer_id" name="customer_id" type="number" min="1" required></td>
                        </tr>
                        <tr>
                            <th><label for="product_edition"><?php _e('Edition', 'themisdb-order-request'); ?></label></th>
                            <td><input id="product_edition" name="product_edition" class="regular-text" value="community" required></td>
                        </tr>
                        <tr>
                            <th><label for="license_type_new"><?php _e('Lizenztyp', 'themisdb-order-request'); ?></label></th>
                            <td><input id="license_type_new" name="license_type" class="regular-text" value="standard"></td>
                        </tr>
                        <tr>
                            <th><label for="expiry_date_new"><?php _e('Ablaufdatum', 'themisdb-order-request'); ?></label></th>
                            <td><input id="expiry_date_new" name="expiry_date" type="date"></td>
                        </tr>
                    </table>
                </div>

                <p>
                    <button type="submit" class="button button-primary"><?php _e('Lizenz erstellen', 'themisdb-order-request'); ?></button>
                    <a href="?page=themisdb-licenses" class="button"><?php _e('Abbrechen', 'themisdb-order-request'); ?></a>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Save license changes from admin form.
     */
    private function handle_save_license($license_id) {
        if ($license_id === 0) {
            $create_payload = array(
                'order_id' => absint($_POST['order_id'] ?? 0),
                'contract_id' => absint($_POST['contract_id'] ?? 0),
                'customer_id' => absint($_POST['customer_id'] ?? 0),
                'product_edition' => sanitize_text_field($_POST['product_edition'] ?? 'community'),
                'license_type' => sanitize_text_field($_POST['license_type'] ?? 'standard'),
                'expiry_date' => sanitize_text_field($_POST['expiry_date'] ?? ''),
            );

            if (empty($create_payload['order_id']) || empty($create_payload['contract_id']) || empty($create_payload['customer_id'])) {
                wp_die(__('Bitte Order-ID, Contract-ID und Customer-ID angeben.', 'themisdb-order-request'));
            }

            $new_id = ThemisDB_License_Manager::create_license($create_payload);
            if ($new_id) {
                wp_redirect(admin_url('admin.php?page=themisdb-licenses&action=view&license_id=' . $new_id . '&message=saved'));
                exit;
            }

            wp_die(__('Fehler beim Erstellen der Lizenz', 'themisdb-order-request'));
        }

        $payload = array(
            'license_type' => sanitize_text_field($_POST['license_type'] ?? ''),
            'max_nodes' => intval($_POST['max_nodes'] ?? 0),
            'max_cores' => intval($_POST['max_cores'] ?? 0),
            'max_storage_gb' => intval($_POST['max_storage_gb'] ?? 0),
            'expiry_date' => sanitize_text_field($_POST['expiry_date'] ?? ''),
            'epserver_subscription_id' => sanitize_text_field($_POST['epserver_subscription_id'] ?? ''),
        );

        $result = ThemisDB_License_Manager::update_license($license_id, $payload);

        if ($result) {
            wp_redirect(admin_url('admin.php?page=themisdb-licenses&action=view&license_id=' . $license_id . '&message=saved'));
            exit;
        }

        wp_die(__('Fehler beim Speichern der Lizenz', 'themisdb-order-request'));
    }

    /**
     * Change license status via admin action.
     */
    private function handle_change_license_status($license_id, $new_status) {
        $license = ThemisDB_License_Manager::get_license($license_id);
        if (!$license) {
            wp_die(__('Lizenz nicht gefunden', 'themisdb-order-request'));
        }

        $result = false;
        if ($new_status === 'active') {
            $result = ThemisDB_License_Manager::activate_license($license_id);
        } elseif ($new_status === 'suspended') {
            $result = ThemisDB_License_Manager::suspend_license($license_id, __('Manuell durch Admin gesetzt', 'themisdb-order-request'));
        } else {
            $result = ThemisDB_License_Manager::update_license($license_id, array('license_status' => $new_status));
        }

        if ($result) {
            wp_redirect(admin_url('admin.php?page=themisdb-licenses&action=view&license_id=' . $license_id . '&message=status_saved'));
            exit;
        }

        wp_die(__('Fehler beim Ändern des Lizenzstatus', 'themisdb-order-request'));
    }

    /**
     * Delete license row from admin.
     */
    private function handle_delete_license($license_id) {
        $result = ThemisDB_License_Manager::delete_license($license_id);

        if ($result) {
            wp_redirect(admin_url('admin.php?page=themisdb-licenses&message=deleted'));
            exit;
        }

        wp_die(__('Fehler beim Löschen der Lizenz', 'themisdb-order-request'));
    }
    
    /**
     * License Audit Log admin page
     */
    public function license_audit_page() {
        global $wpdb;
        $table   = $wpdb->prefix . 'themisdb_license_audit_log';
        $per_page = 50;
        $page_num = max(1, isset($_GET['paged']) ? absint($_GET['paged']) : 1);
        $offset   = ($page_num - 1) * $per_page;

        // Ensure table exists before querying
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) !== $table ) {
            echo '<div class="wrap"><h1>' . esc_html__( 'License Audit Log', 'themisdb-order-request' ) . '</h1>';
            echo '<p>' . esc_html__( 'No audit log entries yet. The log table is created on first REST API call.', 'themisdb-order-request' ) . '</p></div>';
            return;
        }

        $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
        $rows  = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ),
            ARRAY_A
        );

        $total_pages = (int) ceil( $total / $per_page );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'License Audit Log', 'themisdb-order-request' ); ?></h1>
            <p class="description">
                <?php printf(
                    esc_html__( 'Showing %d of %d total entries.', 'themisdb-order-request' ),
                    count( $rows ),
                    $total
                ); ?>
            </p>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Date / Time', 'themisdb-order-request' ); ?></th>
                        <th><?php esc_html_e( 'License Key', 'themisdb-order-request' ); ?></th>
                        <th><?php esc_html_e( 'Action', 'themisdb-order-request' ); ?></th>
                        <th><?php esc_html_e( 'Result', 'themisdb-order-request' ); ?></th>
                        <th><?php esc_html_e( 'IP Address', 'themisdb-order-request' ); ?></th>
                        <th><?php esc_html_e( 'User Agent', 'themisdb-order-request' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $rows ) ) : ?>
                    <tr><td colspan="6"><?php esc_html_e( 'No entries found.', 'themisdb-order-request' ); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ( $rows as $row ) : ?>
                        <tr>
                            <td><?php echo esc_html( $row['created_at'] ); ?></td>
                            <td><code><?php echo esc_html( $row['license_key'] ?? '—' ); ?></code></td>
                            <td><?php echo esc_html( $row['action'] ); ?></td>
                            <td>
                                <span class="license-status status-<?php echo esc_attr( $row['result'] ); ?>">
                                    <?php echo esc_html( $row['result'] ); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html( $row['ip_address'] ?? '—' ); ?></td>
                            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                                title="<?php echo esc_attr( $row['user_agent'] ?? '' ); ?>">
                                <?php echo esc_html( $row['user_agent'] ?? '—' ); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ( $total_pages > 1 ) : ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links( array(
                        'base'      => add_query_arg( 'paged', '%#%' ),
                        'format'    => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total'     => $total_pages,
                        'current'   => $page_num,
                    ) );
                    ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Email log page
     */
    public function email_log_page() {
        $logs = ThemisDB_Email_Handler::get_email_logs();
        
        ?>
        <div class="wrap">
            <h1><?php _e('E-Mail Log', 'themisdb-order-request'); ?></h1>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Empfänger', 'themisdb-order-request'); ?></th>
                        <th><?php _e('Betreff', 'themisdb-order-request'); ?></th>
                        <th><?php _e('Status', 'themisdb-order-request'); ?></th>
                        <th><?php _e('Gesendet am', 'themisdb-order-request'); ?></th>
                        <th><?php _e('Erstellt am', 'themisdb-order-request'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="5"><?php _e('Keine E-Mails gefunden', 'themisdb-order-request'); ?></td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo esc_html($log['recipient']); ?></td>
                            <td><?php echo esc_html($log['subject']); ?></td>
                            <td>
                                <span class="status-<?php echo esc_attr($log['status']); ?>">
                                    <?php echo esc_html(ucfirst($log['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo $log['sent_at'] ? date('d.m.Y H:i', strtotime($log['sent_at'])) : '-'; ?></td>
                            <td><?php echo date('d.m.Y H:i', strtotime($log['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        // Test connection if requested
        if (isset($_GET['test_connection'])) {
            $test_result = ThemisDB_EPServer_API::test_connection();
            if ($test_result['success']) {
                echo '<div class="notice notice-success"><p>' . __('Verbindung erfolgreich!', 'themisdb-order-request') . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . __('Verbindung fehlgeschlagen:', 'themisdb-order-request') . ' ' . esc_html($test_result['message']) . '</p></div>';
            }
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Einstellungen', 'themisdb-order-request'); ?></h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('themisdb_order_settings'); ?>
                
                <h2><?php _e('epServer Integration', 'themisdb-order-request'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label for="themisdb_order_epserver_url"><?php _e('epServer URL', 'themisdb-order-request'); ?></label></th>
                        <td>
                            <input type="text" id="themisdb_order_epserver_url" name="themisdb_order_epserver_url" 
                                   value="<?php echo esc_attr(get_option('themisdb_order_epserver_url')); ?>" 
                                   class="regular-text" />
                            <p class="description"><?php _e('Standard: https://service.themisdb.org:6734', 'themisdb-order-request'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="themisdb_order_epserver_api_key"><?php _e('API Schlüssel', 'themisdb-order-request'); ?></label></th>
                        <td>
                            <input type="password" id="themisdb_order_epserver_api_key" name="themisdb_order_epserver_api_key" 
                                   value="<?php echo esc_attr(get_option('themisdb_order_epserver_api_key')); ?>" 
                                   class="regular-text" />
                            <p class="description"><?php _e('Optional: Bearer Token für epServer API', 'themisdb-order-request'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th></th>
                        <td>
                            <a href="?page=themisdb-order-settings&test_connection=1" class="button">
                                <?php _e('Verbindung testen', 'themisdb-order-request'); ?>
                            </a>
                            <a href="<?php echo admin_url('admin-post.php?action=themisdb_sync_epserver'); ?>" class="button">
                                <?php _e('Daten synchronisieren', 'themisdb-order-request'); ?>
                            </a>
                        </td>
                    </tr>
                </table>
                
                <h2><?php _e('E-Mail Einstellungen', 'themisdb-order-request'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label for="themisdb_order_email_from"><?php _e('Absender E-Mail', 'themisdb-order-request'); ?></label></th>
                        <td>
                            <input type="email" id="themisdb_order_email_from" name="themisdb_order_email_from" 
                                   value="<?php echo esc_attr(get_option('themisdb_order_email_from')); ?>" 
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th><label for="themisdb_order_email_from_name"><?php _e('Absender Name', 'themisdb-order-request'); ?></label></th>
                        <td>
                            <input type="text" id="themisdb_order_email_from_name" name="themisdb_order_email_from_name" 
                                   value="<?php echo esc_attr(get_option('themisdb_order_email_from_name')); ?>" 
                                   class="regular-text" />
                        </td>
                    </tr>
                </table>
                
                <h2><?php _e('PDF Einstellungen', 'themisdb-order-request'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label for="themisdb_order_pdf_storage"><?php _e('PDF Speicherung', 'themisdb-order-request'); ?></label></th>
                        <td>
                            <select id="themisdb_order_pdf_storage" name="themisdb_order_pdf_storage">
                                <option value="database" <?php selected(get_option('themisdb_order_pdf_storage'), 'database'); ?>>
                                    <?php _e('Datenbank', 'themisdb-order-request'); ?>
                                </option>
                                <option value="filesystem" <?php selected(get_option('themisdb_order_pdf_storage'), 'filesystem'); ?>>
                                    <?php _e('Dateisystem', 'themisdb-order-request'); ?>
                                </option>
                            </select>
                            <p class="description"><?php _e('Wo sollen PDF-Dateien gespeichert werden?', 'themisdb-order-request'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <h2><?php _e('Rechtliche Einstellungen', 'themisdb-order-request'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label for="themisdb_order_legal_compliance"><?php _e('Rechtliche Compliance', 'themisdb-order-request'); ?></label></th>
                        <td>
                            <input type="checkbox" id="themisdb_order_legal_compliance" name="themisdb_order_legal_compliance" 
                                   value="1" <?php checked(get_option('themisdb_order_legal_compliance'), '1'); ?> />
                            <label for="themisdb_order_legal_compliance"><?php _e('Rechtliche Compliance-Prüfungen aktivieren', 'themisdb-order-request'); ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="themisdb_order_legal_agb_url"><?php _e('URL AGB', 'themisdb-order-request'); ?></label></th>
                        <td>
                            <input type="url" id="themisdb_order_legal_agb_url" name="themisdb_order_legal_agb_url"
                                   value="<?php echo esc_attr(get_option('themisdb_order_legal_agb_url', site_url('/agb'))); ?>"
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th><label for="themisdb_order_legal_privacy_url"><?php _e('URL Datenschutzerklärung', 'themisdb-order-request'); ?></label></th>
                        <td>
                            <input type="url" id="themisdb_order_legal_privacy_url" name="themisdb_order_legal_privacy_url"
                                   value="<?php echo esc_attr(get_option('themisdb_order_legal_privacy_url', site_url('/datenschutz'))); ?>"
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th><label for="themisdb_order_legal_withdrawal_url"><?php _e('URL Widerrufsbelehrung', 'themisdb-order-request'); ?></label></th>
                        <td>
                            <input type="url" id="themisdb_order_legal_withdrawal_url" name="themisdb_order_legal_withdrawal_url"
                                   value="<?php echo esc_attr(get_option('themisdb_order_legal_withdrawal_url', site_url('/widerruf'))); ?>"
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th><label for="themisdb_order_legal_version"><?php _e('Version Rechtstexte', 'themisdb-order-request'); ?></label></th>
                        <td>
                            <input type="text" id="themisdb_order_legal_version" name="themisdb_order_legal_version"
                                   value="<?php echo esc_attr(get_option('themisdb_order_legal_version', 'de-v1')); ?>"
                                   class="regular-text" />
                            <p class="description"><?php _e('Diese Version wird bei der Zustimmung zur Nachvollziehbarkeit mitgespeichert.', 'themisdb-order-request'); ?></p>
                        </td>
                    </tr>
                </table>

                <h2><?php _e('License API Settings', 'themisdb-order-request'); ?></h2>
                <p class="description"><?php _e('These credentials are used by ThemisDB server instances to validate licenses via the REST API.', 'themisdb-order-request'); ?></p>
                <table class="form-table">
                    <tr>
                        <th><label for="themisdb_license_api_key"><?php _e('License API Key', 'themisdb-order-request'); ?></label></th>
                        <td>
                            <input type="password" id="themisdb_license_api_key" name="themisdb_license_api_key"
                                   value="<?php echo esc_attr(get_option('themisdb_license_api_key')); ?>"
                                   class="regular-text" autocomplete="new-password" />
                            <p class="description"><?php echo wp_kses( __('Shared secret sent by ThemisDB servers as <code>Authorization: Bearer &lt;key&gt;</code>. Generate a strong random value (32+ chars).', 'themisdb-order-request'), array( 'code' => array() ) ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="themisdb_license_admin_secret"><?php _e('Admin Secret', 'themisdb-order-request'); ?></label></th>
                        <td>
                            <input type="password" id="themisdb_license_admin_secret" name="themisdb_license_admin_secret"
                                   value="<?php echo esc_attr(get_option('themisdb_license_admin_secret')); ?>"
                                   class="regular-text" autocomplete="new-password" />
                            <p class="description"><?php echo wp_kses( __('Additional secret required for admin endpoints (renew, revoke). Sent as <code>X-ThemisDB-Admin-Secret</code> header.', 'themisdb-order-request'), array( 'code' => array() ) ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="themisdb_license_renewal_reminder_days"><?php _e('Renewal Reminder (days)', 'themisdb-order-request'); ?></label></th>
                        <td>
                            <input type="number" id="themisdb_license_renewal_reminder_days" name="themisdb_license_renewal_reminder_days"
                                   value="<?php echo esc_attr(get_option('themisdb_license_renewal_reminder_days', '30')); ?>"
                                   min="1" max="365" class="small-text" />
                            <p class="description"><?php _e('Send renewal reminder e-mail this many days before a license expires. A daily cron job checks for upcoming expirations.', 'themisdb-order-request'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th></th>
                        <td>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-license-audit')); ?>" class="button">
                                <?php _e('View License Audit Log', 'themisdb-order-request'); ?>
                            </a>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Handle sync action
     */
    public function handle_sync() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Keine Berechtigung', 'themisdb-order-request'));
        }
        
        $result = ThemisDB_EPServer_API::sync_all();
        
        if ($result['products']) {
            wp_redirect(admin_url('admin.php?page=themisdb-order-settings&sync=success'));
        } else {
            wp_redirect(admin_url('admin.php?page=themisdb-order-settings&sync=error'));
        }
        exit;
    }

    // =========================================================================
    // Bank Import Page
    // =========================================================================

    /**
     * Route the bank import page based on action parameter.
     */
    public function bank_import_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Keine Berechtigung', 'themisdb-order-request'));
        }

        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';

        // --- Handle POST: parse CSV and store preview in transient ---
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'preview') {
            check_admin_referer('themisdb_bank_upload');
            $this->bank_import_handle_upload();
            return;
        }

        // --- Handle POST: confirm and execute the import ---
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'import') {
            check_admin_referer('themisdb_bank_import_confirm');
            $this->bank_import_handle_confirm();
            return;
        }

        // --- Handle POST: manual assignment of unmatched transaction ---
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'assign') {
            check_admin_referer('themisdb_bank_assign');
            $this->bank_import_handle_assign();
            return;
        }

        // --- GET: view details of a completed import session ---
        if ($action === 'view' && !empty($_GET['import_id'])) {
            $this->bank_import_view_session(intval($_GET['import_id']));
            return;
        }

        // --- GET: show assignment form for an unmatched transaction ---
        if ($action === 'assign' && !empty($_GET['transaction_id'])) {
            $this->bank_import_assign_form(intval($_GET['transaction_id']));
            return;
        }

        // --- GET: show preview after CSV upload (from transient) ---
        if ($action === 'preview' && !empty($_GET['token'])) {
            $this->bank_import_show_preview(sanitize_text_field($_GET['token']));
            return;
        }

        // --- Default: upload form + history ---
        $this->bank_import_list();
    }

    /**
     * Step 1 landing page: reference guide + CSV upload form + import history.
     */
    private function bank_import_list() {
        $imports = ThemisDB_Bank_Import::get_imports(array('limit' => 20));
        $formats = ThemisDB_Bank_Import::get_supported_formats();
        ?>
        <div class="wrap">
            <h1><?php _e('Bankimport – Zahlungsabgleich', 'themisdb-order-request'); ?></h1>

            <!-- Error/Success Messages -->
            <?php if (isset($_GET['upload_error'])): ?>
            <div class="notice notice-error"><p>
                <strong><?php _e('❌ Upload fehlgeschlagen:', 'themisdb-order-request'); ?></strong><br>
                <?php 
                    $error_msg = sanitize_text_field($_GET['upload_error']);
                    $error_messages = array(
                        'no_file' => __('Keine Datei ausgewählt. Bitte eine CSV-Datei hochladen.', 'themisdb-order-request'),
                    );
                    echo esc_html($error_messages[$error_msg] ?? $error_msg);
                ?>
            </p></div>
            <?php endif; ?>

            <?php if (isset($_GET['import_error'])): ?>
            <div class="notice notice-error"><p>
                <strong><?php _e('❌ Import fehlgeschlagen:', 'themisdb-order-request'); ?></strong><br>
                <?php 
                    $error_msg = sanitize_text_field($_GET['import_error']);
                    $error_messages = array(
                        'expired' => __('Vorschau-Session abgelaufen. Bitte die CSV-Datei erneut hochladen.', 'themisdb-order-request'),
                        'db' => __('Datenbankfehler beim Speichern des Imports. Bitte versuchen Sie es später erneut.', 'themisdb-order-request'),
                    );
                    echo esc_html($error_messages[$error_msg] ?? $error_msg);
                ?>
            </p></div>
            <?php endif; ?>

            <?php if (isset($_GET['assign_error'])): ?>
            <div class="notice notice-error"><p>
                <strong><?php _e('❌ Zuweisung fehlgeschlagen:', 'themisdb-order-request'); ?></strong><br>
                <?php 
                    $error_msg = sanitize_text_field($_GET['assign_error']);
                    $error_messages = array(
                        'missing' => __('Erforderliche Felder sind leer. Bitte versuchen Sie es erneut.', 'themisdb-order-request'),
                        'db' => __('Datenbankfehler bei der Zuweisung. Bitte versuchen Sie es später erneut.', 'themisdb-order-request'),
                    );
                    echo esc_html($error_messages[$error_msg] ?? $error_msg);
                ?>
            </p></div>
            <?php endif; ?>

            <?php if (isset($_GET['imported'])): ?>
            <div class="notice notice-success"><p>
                ✅ <strong><?php _e('Import erfolgreich abgeschlossen!', 'themisdb-order-request'); ?></strong>
                <?php _e('Gematchte Zahlungen wurden automatisch verifiziert.', 'themisdb-order-request'); ?>
            </p></div>
            <?php endif; ?>

            <?php if (isset($_GET['assigned'])): ?>
            <div class="notice notice-success"><p>
                ✅ <strong><?php _e('Transktion erfolgreich zugeordnet und verifiziert!', 'themisdb-order-request'); ?></strong>
            </p></div>
            <?php endif; ?>

            <!-- Reference guide box -->
            <div class="notice notice-info" style="padding:12px 16px;">
                <h3 style="margin-top:0;">
                    <?php _e('📋 Welche Referenz gehört auf den Überweisungsträger?', 'themisdb-order-request'); ?>
                </h3>
                <p><?php _e('Damit Banküberweisungen automatisch einer Lizenz/Bestellung zugeordnet werden können, <strong>muss der Kunde im Feld „Verwendungszweck" exakt eine der folgenden Referenzen angeben:</strong>', 'themisdb-order-request'); ?></p>
                <table class="widefat" style="max-width:700px;">
                    <thead>
                        <tr>
                            <th><?php _e('Priorität', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Feld auf dem Überweisungsträger', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Format / Beispiel', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Wo finden?', 'themisdb-order-request'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="background:#d4edda;">
                            <td><strong><?php _e('1 (bevorzugt)', 'themisdb-order-request'); ?></strong></td>
                            <td><?php _e('Verwendungszweck', 'themisdb-order-request'); ?></td>
                            <td><code>PAY-20261201-A3B2C1</code></td>
                            <td><?php _e('Zahlungsnummer in der Auftragsbestätigungs-E-Mail oder im Kunden-Portal', 'themisdb-order-request'); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e('2 (Fallback)', 'themisdb-order-request'); ?></td>
                            <td><?php _e('Verwendungszweck', 'themisdb-order-request'); ?></td>
                            <td><code>ORD-20261130-F9E8D7</code></td>
                            <td><?php _e('Bestellnummer in der Auftragsbestätigungs-E-Mail', 'themisdb-order-request'); ?></td>
                        </tr>
                    </tbody>
                </table>
                <p style="margin-bottom:0;">
                    <strong><?php _e('Empfehlung:', 'themisdb-order-request'); ?></strong>
                    <?php _e('Tragen Sie <em>beide</em> Referenzen in den Verwendungszweck ein, z.&nbsp;B.:', 'themisdb-order-request'); ?>
                    <code>PAY-20261201-A3B2C1 ORD-20261130-F9E8D7</code>
                </p>
            </div>

            <!-- Upload form -->
            <div class="card" style="max-width:none; margin:20px 0;">
                <h2><?php _e('CSV-Datei hochladen', 'themisdb-order-request'); ?></h2>
                <form method="post"
                      action="<?php echo esc_url(admin_url('admin.php?page=themisdb-bank-import&action=preview')); ?>"
                      enctype="multipart/form-data">
                    <?php wp_nonce_field('themisdb_bank_upload'); ?>
                    <table class="form-table">
                        <tr>
                            <th><label for="bank_csv_file"><?php _e('CSV-Datei (Kontoauszug)', 'themisdb-order-request'); ?></label></th>
                            <td>
                                <input type="file" id="bank_csv_file" name="bank_csv_file" accept=".csv,.txt" required />
                                <p class="description">
                                    <?php _e('Exportieren Sie den Kontoauszug als CSV-Datei aus Ihrem Online-Banking. Unterstützte Banken: Sparkasse, DKB, Deutsche Bank, Commerzbank, ING und generische CSV-Formate.', 'themisdb-order-request'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="bank_format"><?php _e('Bankformat', 'themisdb-order-request'); ?></label></th>
                            <td>
                                <select id="bank_format" name="bank_format">
                                    <?php foreach ($formats as $key => $label): ?>
                                        <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php _e('„Automatisch erkennen" funktioniert für die meisten deutschen Banken.', 'themisdb-order-request'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="bank_import_notes"><?php _e('Notizen (optional)', 'themisdb-order-request'); ?></label></th>
                            <td>
                                <textarea id="bank_import_notes" name="bank_import_notes" rows="2" class="large-text"></textarea>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(__('CSV hochladen &amp; Vorschau anzeigen', 'themisdb-order-request')); ?>
                </form>
            </div>

            <!-- Import history -->
            <?php if (!empty($imports)): ?>
            <div class="card" style="max-width:none; margin:20px 0;">
                <h2><?php _e('Import-Verlauf', 'themisdb-order-request'); ?></h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Datum', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Dateiname', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Format', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Gesamt', 'themisdb-order-request'); ?></th>
                            <th style="color:green;"><?php _e('Gematcht', 'themisdb-order-request'); ?></th>
                            <th style="color:orange;"><?php _e('Offen', 'themisdb-order-request'); ?></th>
                            <th style="color:#666;"><?php _e('Duplikat', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Importiert von', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Aktionen', 'themisdb-order-request'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($imports as $imp): ?>
                        <tr>
                            <td><?php echo esc_html(date('d.m.Y H:i', strtotime($imp['created_at']))); ?></td>
                            <td><?php echo esc_html($imp['filename']); ?></td>
                            <td><?php echo esc_html(strtoupper($imp['bank_format'])); ?></td>
                            <td><?php echo (int) $imp['rows_total']; ?></td>
                            <td style="color:green;font-weight:bold;"><?php echo (int) $imp['rows_matched']; ?></td>
                            <td style="color:orange;font-weight:bold;"><?php echo (int) $imp['rows_unmatched']; ?></td>
                            <td style="color:#666;"><?php echo (int) $imp['rows_duplicate']; ?></td>
                            <td><?php echo esc_html($imp['imported_by_name'] ?: '—'); ?></td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-bank-import&action=view&import_id=' . intval($imp['id']))); ?>"
                                   class="button button-small">
                                    <?php _e('Details', 'themisdb-order-request'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Handle CSV upload: parse, match, store preview in transient, redirect.
     */
    private function bank_import_handle_upload() {
        if (empty($_FILES['bank_csv_file']['tmp_name'])) {
            wp_redirect(admin_url('admin.php?page=themisdb-bank-import&upload_error=no_file'));
            exit;
        }

        $file      = $_FILES['bank_csv_file'];
        $format    = isset($_POST['bank_format']) ? sanitize_text_field($_POST['bank_format']) : 'auto';
        $notes     = isset($_POST['bank_import_notes']) ? sanitize_textarea_field($_POST['bank_import_notes']) : '';
        $filename  = sanitize_file_name($file['name']);

        // Parse CSV
        $transactions = ThemisDB_Bank_Import::parse_csv_file($file['tmp_name'], $format);

        if (is_wp_error($transactions)) {
            wp_redirect(admin_url('admin.php?page=themisdb-bank-import&upload_error=' . urlencode($transactions->get_error_message())));
            exit;
        }

        // Match against existing payments
        $transactions = ThemisDB_Bank_Import::match_transactions($transactions);

        // Store preview data in a short-lived transient
        $token = wp_generate_uuid4();
        set_transient('themisdb_bank_preview_' . $token, array(
            'filename'     => $filename,
            'bank_format'  => $format,
            'notes'        => $notes,
            'transactions' => $transactions,
        ), 30 * MINUTE_IN_SECONDS);

        wp_redirect(admin_url('admin.php?page=themisdb-bank-import&action=preview&token=' . urlencode($token)));
        exit;
    }

    /**
     * Show the parsed CSV preview for admin review before saving.
     *
     * @param string $token  Transient key suffix.
     */
    private function bank_import_show_preview($token) {
        $data = get_transient('themisdb_bank_preview_' . $token);

        if (!$data) {
            echo '<div class="notice notice-error"><p>' .
                 esc_html__('Vorschau abgelaufen oder ungültig. Bitte die CSV-Datei erneut hochladen.', 'themisdb-order-request') .
                 '</p></div>';
            $this->bank_import_list();
            return;
        }

        $transactions = $data['transactions'];
        $counts = $this->bank_import_count_statuses($transactions);
        $formats = ThemisDB_Bank_Import::get_supported_formats();
        ?>
        <div class="wrap">
            <h1><?php _e('Bankimport – Vorschau', 'themisdb-order-request'); ?></h1>

            <div class="card" style="max-width:none; margin-bottom:20px;">
                <h2><?php _e('Import-Zusammenfassung', 'themisdb-order-request'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Datei', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html($data['filename']); ?></td>
                        <th><?php _e('Format', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html($formats[$data['bank_format']] ?? $data['bank_format']); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Gesamt Zeilen', 'themisdb-order-request'); ?>:</th>
                        <td><strong><?php echo (int) $counts['total']; ?></strong></td>
                        <th style="color:green;"><?php _e('Automatisch gematcht', 'themisdb-order-request'); ?>:</th>
                        <td style="color:green;"><strong><?php echo (int) $counts['matched']; ?></strong></td>
                    </tr>
                    <tr>
                        <th style="color:orange;"><?php _e('Nicht zugeordnet', 'themisdb-order-request'); ?>:</th>
                        <td style="color:orange;"><strong><?php echo (int) $counts['unmatched']; ?></strong></td>
                        <th style="color:#666;"><?php _e('Bereits importiert (Duplikat)', 'themisdb-order-request'); ?>:</th>
                        <td style="color:#666;"><strong><?php echo (int) $counts['duplicate']; ?></strong></td>
                    </tr>
                    <tr>
                        <th><?php _e('Übersprungen (ausgehend)', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo (int) $counts['skipped']; ?></td>
                        <th></th><td></td>
                    </tr>
                </table>
            </div>

            <?php if ($counts['matched'] === 0 && $counts['unmatched'] === 0): ?>
            <div class="notice notice-warning">
                <p><?php _e('Es wurden keine importierbaren Transaktionen gefunden. Prüfen Sie das Dateiformat und den Inhalt.', 'themisdb-order-request'); ?></p>
            </div>
            <?php endif; ?>

            <!-- Transaction preview table -->
            <div class="card" style="max-width:none; margin-bottom:20px;">
                <h2><?php _e('Transaktionen', 'themisdb-order-request'); ?></h2>
                <table class="wp-list-table widefat fixed striped" style="table-layout:auto;">
                    <thead>
                        <tr>
                            <th><?php _e('Buchungsdatum', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Auftraggeber', 'themisdb-order-request'); ?></th>
                            <th><?php _e('IBAN', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Betrag', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Verwendungszweck', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Status', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Zahlungsnummer', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Hinweis', 'themisdb-order-request'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $tx): ?>
                        <?php
                            $payment = null;
                            if (!empty($tx['matched_payment_id'])) {
                                $payment = ThemisDB_Payment_Manager::get_payment($tx['matched_payment_id']);
                            }
                            $status_label = $this->bank_import_status_badge($tx['match_status']);
                        ?>
                        <tr>
                            <td><?php echo esc_html($tx['booking_date'] ? date('d.m.Y', strtotime($tx['booking_date'])) : '—'); ?></td>
                            <td><?php echo esc_html($tx['payer_name'] ?: '—'); ?></td>
                            <td><code style="font-size:10px;"><?php echo esc_html($tx['payer_iban'] ?: '—'); ?></code></td>
                            <td style="white-space:nowrap;">
                                <strong><?php echo number_format(floatval($tx['amount']), 2, ',', '.'); ?> <?php echo esc_html($tx['currency']); ?></strong>
                            </td>
                            <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                                title="<?php echo esc_attr($tx['purpose']); ?>">
                                <?php echo esc_html(mb_strimwidth($tx['purpose'] ?? '', 0, 60, '…')); ?>
                            </td>
                            <td><?php echo $status_label; ?></td>
                            <td>
                                <?php if ($payment): ?>
                                    <code><?php echo esc_html($payment['payment_number']); ?></code>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td style="font-size:11px;color:#666;"><?php echo esc_html($tx['match_note'] ?? ''); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Confirm / cancel -->
            <?php if ($counts['matched'] > 0 || $counts['unmatched'] > 0): ?>
            <form method="post"
                  action="<?php echo esc_url(admin_url('admin.php?page=themisdb-bank-import&action=import')); ?>">
                <?php wp_nonce_field('themisdb_bank_import_confirm'); ?>
                <input type="hidden" name="preview_token" value="<?php echo esc_attr($token); ?>" />
                <p>
                    <input type="submit"
                           class="button button-primary button-large"
                           value="<?php esc_attr_e('Import bestätigen &amp; Zahlungen verarbeiten', 'themisdb-order-request'); ?>" />
                    <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-bank-import')); ?>"
                       class="button button-large" style="margin-left:8px;">
                        <?php _e('Abbrechen', 'themisdb-order-request'); ?>
                    </a>
                </p>
            </form>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Handle confirmed import: persist to DB, redirect to result.
     */
    private function bank_import_handle_confirm() {
        $token = isset($_POST['preview_token']) ? sanitize_text_field($_POST['preview_token']) : '';
        $data  = get_transient('themisdb_bank_preview_' . $token);

        if (!$data) {
            wp_redirect(admin_url('admin.php?page=themisdb-bank-import&import_error=expired'));
            exit;
        }

        $import_id = ThemisDB_Bank_Import::save_import(
            array(
                'filename'    => $data['filename'],
                'bank_format' => $data['bank_format'],
                'notes'       => $data['notes'],
            ),
            $data['transactions']
        );

        delete_transient('themisdb_bank_preview_' . $token);

        if ($import_id) {
            wp_redirect(admin_url('admin.php?page=themisdb-bank-import&action=view&import_id=' . $import_id . '&imported=1'));
        } else {
            wp_redirect(admin_url('admin.php?page=themisdb-bank-import&import_error=db'));
        }
        exit;
    }

    /**
     * View a completed import session (with all its transactions).
     *
     * @param int $import_id
     */
    private function bank_import_view_session($import_id) {
        global $wpdb;
        $table_imports = $wpdb->prefix . 'themisdb_bank_imports';
        $import = $wpdb->get_row($wpdb->prepare(
            "SELECT i.*, u.display_name AS imported_by_name
               FROM $table_imports i
          LEFT JOIN {$wpdb->users} u ON u.ID = i.imported_by
              WHERE i.id = %d",
            $import_id
        ), ARRAY_A);

        if (!$import) {
            echo '<div class="notice notice-error"><p>' .
                 esc_html__('Import nicht gefunden.', 'themisdb-order-request') .
                 '</p></div>';
            $this->bank_import_list();
            return;
        }

        $transactions = ThemisDB_Bank_Import::get_transactions($import_id);
        $formats      = ThemisDB_Bank_Import::get_supported_formats();
        ?>
        <div class="wrap">
            <h1><?php _e('Bankimport – Detailansicht', 'themisdb-order-request'); ?></h1>

            <?php if (isset($_GET['imported'])): ?>
            <div class="notice notice-success">
                <p><?php _e('Import erfolgreich abgeschlossen. Gematchte Zahlungen wurden automatisch verifiziert.', 'themisdb-order-request'); ?></p>
            </div>
            <?php endif; ?>

            <div class="card" style="max-width:none; margin-bottom:20px;">
                <h2><?php _e('Import-Details', 'themisdb-order-request'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Datei', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html($import['filename']); ?></td>
                        <th><?php _e('Bankformat', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html($formats[$import['bank_format']] ?? $import['bank_format']); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Importiert am', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html(date('d.m.Y H:i', strtotime($import['created_at']))); ?></td>
                        <th><?php _e('Importiert von', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html($import['imported_by_name'] ?: '—'); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Gesamt', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo (int) $import['rows_total']; ?></td>
                        <th style="color:green;"><?php _e('Gematcht', 'themisdb-order-request'); ?>:</th>
                        <td style="color:green;font-weight:bold;"><?php echo (int) $import['rows_matched']; ?></td>
                    </tr>
                    <tr>
                        <th style="color:orange;"><?php _e('Nicht zugeordnet', 'themisdb-order-request'); ?>:</th>
                        <td style="color:orange;font-weight:bold;"><?php echo (int) $import['rows_unmatched']; ?></td>
                        <th style="color:#666;"><?php _e('Duplikate', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo (int) $import['rows_duplicate']; ?></td>
                    </tr>
                    <?php if ($import['notes']): ?>
                    <tr>
                        <th><?php _e('Notizen', 'themisdb-order-request'); ?>:</th>
                        <td colspan="3"><?php echo esc_html($import['notes']); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>

            <div class="card" style="max-width:none;">
                <h2><?php _e('Transaktionen', 'themisdb-order-request'); ?></h2>
                <table class="wp-list-table widefat fixed striped" style="table-layout:auto;">
                    <thead>
                        <tr>
                            <th><?php _e('Buchungsdatum', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Auftraggeber', 'themisdb-order-request'); ?></th>
                            <th><?php _e('IBAN', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Betrag', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Verwendungszweck', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Status', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Zahlung', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Aktionen', 'themisdb-order-request'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $tx): ?>
                        <tr>
                            <td><?php echo esc_html($tx['booking_date'] ? date('d.m.Y', strtotime($tx['booking_date'])) : '—'); ?></td>
                            <td><?php echo esc_html($tx['payer_name'] ?: '—'); ?></td>
                            <td><code style="font-size:10px;"><?php echo esc_html($tx['payer_iban'] ?: '—'); ?></code></td>
                            <td style="white-space:nowrap;">
                                <strong><?php echo number_format(floatval($tx['amount']), 2, ',', '.'); ?> <?php echo esc_html($tx['currency']); ?></strong>
                            </td>
                            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                                title="<?php echo esc_attr($tx['purpose']); ?>">
                                <?php echo esc_html(mb_strimwidth($tx['purpose'] ?? '', 0, 55, '…')); ?>
                            </td>
                            <td><?php echo $this->bank_import_status_badge($tx['match_status']); ?></td>
                            <td>
                                <?php if ($tx['payment_number']): ?>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-payments&action=view&payment_id=' . intval($tx['matched_payment_id']))); ?>">
                                        <code><?php echo esc_html($tx['payment_number']); ?></code>
                                    </a>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($tx['match_status'] === 'unmatched'): ?>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-bank-import&action=assign&transaction_id=' . intval($tx['id']))); ?>"
                                   class="button button-small">
                                    <?php _e('Manuell zuordnen', 'themisdb-order-request'); ?>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <p style="margin-top:16px;">
                <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-bank-import')); ?>" class="button">
                    <?php _e('Zurück zur Übersicht', 'themisdb-order-request'); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Render the manual assignment form for an unmatched transaction.
     *
     * @param int $transaction_id
     */
    private function bank_import_assign_form($transaction_id) {
        global $wpdb;
        $table_tx = $wpdb->prefix . 'themisdb_bank_transactions';
        $tx = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_tx WHERE id = %d",
            $transaction_id
        ), ARRAY_A);

        if (!$tx) {
            echo '<div class="notice notice-error"><p>' .
                 esc_html__('Transaktion nicht gefunden.', 'themisdb-order-request') .
                 '</p></div>';
            $this->bank_import_list();
            return;
        }

        // Load pending payments for selection
        $pending_payments = ThemisDB_Payment_Manager::get_all_payments(array('status' => 'pending', 'limit' => 200));
        ?>
        <div class="wrap">
            <h1><?php _e('Transaktion manuell zuordnen', 'themisdb-order-request'); ?></h1>

            <div class="card" style="max-width:700px; margin-bottom:20px;">
                <h2><?php _e('Bankbuchung', 'themisdb-order-request'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Buchungsdatum', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html($tx['booking_date'] ? date('d.m.Y', strtotime($tx['booking_date'])) : '—'); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Auftraggeber', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html($tx['payer_name'] ?: '—'); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('IBAN', 'themisdb-order-request'); ?>:</th>
                        <td><code><?php echo esc_html($tx['payer_iban'] ?: '—'); ?></code></td>
                    </tr>
                    <tr>
                        <th><?php _e('Betrag', 'themisdb-order-request'); ?>:</th>
                        <td><strong><?php echo number_format(floatval($tx['amount']), 2, ',', '.'); ?> <?php echo esc_html($tx['currency']); ?></strong></td>
                    </tr>
                    <tr>
                        <th><?php _e('Verwendungszweck', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html($tx['purpose'] ?: '—'); ?></td>
                    </tr>
                </table>
            </div>

            <form method="post"
                  action="<?php echo esc_url(admin_url('admin.php?page=themisdb-bank-import&action=assign')); ?>">
                <?php wp_nonce_field('themisdb_bank_assign'); ?>
                <input type="hidden" name="transaction_id" value="<?php echo absint($transaction_id); ?>" />
                <input type="hidden" name="import_id" value="<?php echo absint($tx['import_id']); ?>" />

                <div class="card" style="max-width:700px;">
                    <h2><?php _e('Zahlung auswählen', 'themisdb-order-request'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><label for="assign_payment_id"><?php _e('Ausstehende Zahlung', 'themisdb-order-request'); ?></label></th>
                            <td>
                                <select id="assign_payment_id" name="assign_payment_id" required style="min-width:350px;">
                                    <option value=""><?php _e('— Zahlung auswählen —', 'themisdb-order-request'); ?></option>
                                    <?php foreach ($pending_payments as $p): ?>
                                        <?php $order = ThemisDB_Order_Manager::get_order($p['order_id']); ?>
                                        <option value="<?php echo absint($p['id']); ?>">
                                            <?php echo esc_html($p['payment_number']); ?> —
                                            <?php echo esc_html(number_format($p['amount'], 2, ',', '.')); ?> <?php echo esc_html($p['currency']); ?>
                                            <?php if ($order): ?>
                                                (<?php echo esc_html($order['customer_name']); ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php _e('Nur ausstehende (pending) Zahlungen werden aufgelistet.', 'themisdb-order-request'); ?></p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(__('Zahlung zuordnen &amp; verifizieren', 'themisdb-order-request')); ?>
                </div>
            </form>

            <p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-bank-import&action=view&import_id=' . absint($tx['import_id']))); ?>"
                   class="button">
                    <?php _e('Zurück zum Import', 'themisdb-order-request'); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Handle manual transaction assignment form POST.
     */
    private function bank_import_handle_assign() {
        $transaction_id = isset($_POST['transaction_id']) ? intval($_POST['transaction_id']) : 0;
        $payment_id     = isset($_POST['assign_payment_id']) ? intval($_POST['assign_payment_id']) : 0;
        $import_id      = isset($_POST['import_id']) ? intval($_POST['import_id']) : 0;

        if (!$transaction_id || !$payment_id) {
            wp_redirect(admin_url('admin.php?page=themisdb-bank-import&assign_error=missing'));
            exit;
        }

        $success = ThemisDB_Bank_Import::assign_transaction($transaction_id, $payment_id);

        if ($success) {
            wp_redirect(admin_url('admin.php?page=themisdb-bank-import&action=view&import_id=' . $import_id . '&assigned=1'));
        } else {
            wp_redirect(admin_url('admin.php?page=themisdb-bank-import&action=view&import_id=' . $import_id . '&assign_error=db'));
        }
        exit;
    }

    /**
     * Count transactions by match status.
     *
     * @param  array  $transactions
     * @return array  Keys: total, matched, unmatched, duplicate, skipped.
     */
    private function bank_import_count_statuses(array $transactions) {
        $counts = array('total' => count($transactions), 'matched' => 0, 'unmatched' => 0, 'duplicate' => 0, 'skipped' => 0);
        foreach ($transactions as $tx) {
            $s = $tx['match_status'];
            if (isset($counts[$s])) {
                $counts[$s]++;
            }
        }
        return $counts;
    }

    /**
     * Return a coloured status badge span for a match status.
     *
     * @param  string  $status
     * @return string  HTML span (pre-escaped).
     */
    private function bank_import_status_badge($status) {
        $map = array(
            'matched'   => array('color' => '#155724', 'bg' => '#d4edda', 'label' => __('Gematcht', 'themisdb-order-request')),
            'unmatched' => array('color' => '#856404', 'bg' => '#fff3cd', 'label' => __('Offen', 'themisdb-order-request')),
            'duplicate' => array('color' => '#3c434a', 'bg' => '#f0f0f1', 'label' => __('Duplikat', 'themisdb-order-request')),
            'skipped'   => array('color' => '#6c757d', 'bg' => '#e2e3e5', 'label' => __('Übersprungen', 'themisdb-order-request')),
            'manual'    => array('color' => '#004085', 'bg' => '#cce5ff', 'label' => __('Manuell', 'themisdb-order-request')),
        );
        $s = isset($map[$status]) ? $map[$status] : array('color' => '#000', 'bg' => '#eee', 'label' => esc_html($status));
        return sprintf(
            '<span style="display:inline-block;padding:3px 10px;border-radius:3px;font-size:11px;font-weight:bold;background:%s;color:%s;">%s</span>',
            esc_attr($s['bg']),
            esc_attr($s['color']),
            esc_html($s['label'])
        );
    }
}
