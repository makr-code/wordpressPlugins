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
        add_action('admin_init', array($this, 'handle_early_post'));
        add_action('admin_post_themisdb_sync_epserver', array($this, 'handle_sync'));
    }

    private function log_admin_event($level, $message, $context = array()) {
        if (class_exists('ThemisDB_Error_Handler')) {
            ThemisDB_Error_Handler::log($level, $message, $context);
        }
    }

    private function abort_with_log($user_message, $log_message, $context = array(), $level = 'error') {
        $this->log_admin_event($level, $log_message, $context);
        wp_die($user_message);
    }

    /**
     * Handle POST requests before page output starts to avoid "headers already sent" errors.
     * WordPress calls admin page callbacks after the <head> is rendered, so wp_redirect()
     * must be invoked here (admin_init), before any output has been sent.
     */
    public function handle_early_post() {
        if ( ! is_admin() || $_SERVER['REQUEST_METHOD'] !== 'POST' || ! isset( $_POST['action'] ) ) {
            return;
        }

        $page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

        switch ( $page ) {
            case 'themisdb-orders':
                $this->orders_page();
                break;
            case 'themisdb-contracts':
                $this->contracts_page();
                break;
            case 'themisdb-products':
                $this->products_page();
                break;
            case 'themisdb-inventory':
                $this->inventory_page();
                break;
            case 'themisdb-payments':
                $this->payments_page();
                break;
            case 'themisdb-licenses':
                $this->licenses_page();
                break;
            case 'themisdb-support-tickets':
                $this->support_tickets_page();
                break;
            case 'themisdb-bank-import':
                $this->bank_import_page();
                break;
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('ThemisDB Bestellungen', 'themisdb-order-request'),
            __('ThemisDB Orders', 'themisdb-order-request'),
            'manage_options',
            'themisdb-order-dashboard',
            array($this, 'order_dashboard_page'),
            'dashicons-cart',
            30
        );

        add_submenu_page(
            'themisdb-order-dashboard',
            __('Order Dashboard', 'themisdb-order-request'),
            __('Dashboard', 'themisdb-order-request'),
            'manage_options',
            'themisdb-order-dashboard',
            array($this, 'order_dashboard_page')
        );
        
        add_submenu_page(
            'themisdb-order-dashboard',
            __('Alle Bestellungen', 'themisdb-order-request'),
            __('Bestellungen', 'themisdb-order-request'),
            'manage_options',
            'themisdb-orders',
            array($this, 'orders_page')
        );
        
        add_submenu_page(
            'themisdb-order-dashboard',
            __('Verträge', 'themisdb-order-request'),
            __('Verträge', 'themisdb-order-request'),
            'manage_options',
            'themisdb-contracts',
            array($this, 'contracts_page')
        );
        
        add_submenu_page(
            'themisdb-order-dashboard',
            __('Produkte', 'themisdb-order-request'),
            __('Produkte', 'themisdb-order-request'),
            'manage_options',
            'themisdb-products',
            array($this, 'products_page')
        );

        add_submenu_page(
            'themisdb-order-dashboard',
            __('Lagerbestand', 'themisdb-order-request'),
            __('Lagerbestand', 'themisdb-order-request'),
            'manage_options',
            'themisdb-inventory',
            array($this, 'inventory_page')
        );
        
        add_submenu_page(
            'themisdb-order-dashboard',
            __('Zahlungen', 'themisdb-order-request'),
            __('Zahlungen', 'themisdb-order-request'),
            'manage_options',
            'themisdb-payments',
            array($this, 'payments_page')
        );
        
        add_submenu_page(
            'themisdb-order-dashboard',
            __('Lizenzen', 'themisdb-order-request'),
            __('Lizenzen', 'themisdb-order-request'),
            'manage_options',
            'themisdb-licenses',
            array($this, 'licenses_page')
        );

        add_submenu_page(
            'themisdb-order-dashboard',
            __('Support Tickets', 'themisdb-order-request'),
            __('Support Tickets', 'themisdb-order-request'),
            'manage_options',
            'themisdb-support-tickets',
            array($this, 'support_tickets_page')
        );
        
        add_submenu_page(
            'themisdb-order-dashboard',
            __('E-Mail Log', 'themisdb-order-request'),
            __('E-Mail Log', 'themisdb-order-request'),
            'manage_options',
            'themisdb-email-log',
            array($this, 'email_log_page')
        );

        add_submenu_page(
            'themisdb-order-dashboard',
            __('License Audit Log', 'themisdb-order-request'),
            __('License Audit Log', 'themisdb-order-request'),
            'manage_options',
            'themisdb-license-audit',
            array($this, 'license_audit_page')
        );

        add_submenu_page(
            'themisdb-order-dashboard',
            __('Bankimport', 'themisdb-order-request'),
            __('Bankimport', 'themisdb-order-request'),
            'manage_options',
            'themisdb-bank-import',
            array($this, 'bank_import_page')
        );
        
        add_submenu_page(
            'themisdb-order-dashboard',
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
        register_setting('themisdb_order_settings', 'themisdb_license_default_term_days');
        register_setting('themisdb_order_settings', 'themisdb_license_allow_auto_renewal');
        register_setting('themisdb_order_settings', 'themisdb_affiliate_default_commission_rate');
        register_setting('themisdb_order_settings', 'themisdb_affiliate_cookie_days');
        register_setting('themisdb_order_settings', 'themisdb_b2b_default_invoice_due_days');
        register_setting('themisdb_order_settings', 'themisdb_reporting_marketing_spend_json');
        register_setting('themisdb_order_settings', 'themisdb_support_github_enabled');
        register_setting('themisdb_order_settings', 'themisdb_support_github_token');
        register_setting('themisdb_order_settings', 'themisdb_support_github_repository');
        register_setting('themisdb_order_settings', 'themisdb_support_github_labels');
        register_setting('themisdb_order_settings', 'themisdb_order_page_url', array(
            'sanitize_callback' => array($this, 'sanitize_order_page_reference'),
        ));
        register_setting('themisdb_order_settings', 'themisdb_product_page_url', array(
            'sanitize_callback' => array($this, 'sanitize_product_page_reference'),
        ));
    }

    /**
     * Sanitize order flow page reference (page ID or legacy URL).
     */
    public function sanitize_order_page_reference($value) {
        return $this->sanitize_page_reference_value($value, 'themisdb_order_page_url');
    }

    /**
     * Sanitize product detail page reference (page ID or legacy URL).
     */
    public function sanitize_product_page_reference($value) {
        return $this->sanitize_page_reference_value($value, 'themisdb_product_page_url');
    }

    /**
     * Normalize a page reference value while keeping backward compatibility with URL-based options.
     */
    private function sanitize_page_reference_value($value, $option_name) {
        if (is_numeric($value) && intval($value) > 0) {
            return (string) intval($value);
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            $existing = get_option($option_name, '');
            if (is_string($existing) && $existing !== '' && !is_numeric($existing)) {
                return esc_url_raw($existing);
            }
            return '';
        }

        $url = esc_url_raw($raw);
        if ($url === '') {
            return '';
        }

        $page_id = url_to_postid($url);
        if ($page_id > 0) {
            return (string) intval($page_id);
        }

        return $url;
    }

    /**
     * Resolve a stored option (ID or URL) to a page ID for native page selectors.
     */
    private function resolve_page_id_from_option($option_name) {
        $raw = get_option($option_name, '');
        if (is_numeric($raw) && intval($raw) > 0) {
            return intval($raw);
        }

        $url = esc_url_raw((string) $raw);
        if ($url === '') {
            return 0;
        }

        $page_id = url_to_postid($url);
        if ($page_id > 0) {
            return intval($page_id);
        }

        $path = (string) wp_parse_url($url, PHP_URL_PATH);
        $path = trim($path, '/');
        if ($path !== '') {
            $page = get_page_by_path($path, OBJECT, 'page');
            if ($page instanceof WP_Post) {
                return intval($page->ID);
            }
        }

        return 0;
    }

    /**
     * Render global module navigation tabs across admin views.
     */
    /**
     * Render details page with tab navigation.
     * Usage: Call once at the top of the page, then call render_detail_tab_pane() for each tab content.
     */
    private function render_detail_page_tabs($tabs, $current_tab = null) {
        if (empty($tabs) || !is_array($tabs)) {
            return;
        }

        // Auto-select first tab if none specified
        if ($current_tab === null || !isset($tabs[$current_tab])) {
            reset($tabs);
            $current_tab = key($tabs);
        }

        echo '<nav class="nav-tab-wrapper" style="margin-bottom:1.5rem;">';
        foreach ($tabs as $key => $label) {
            $class = ($key === $current_tab) ? 'nav-tab nav-tab-active' : 'nav-tab';
            echo '<a href="#" class="' . esc_attr($class) . '" data-tab="' . esc_attr($key) . '">' . esc_html($label) . '</a>';
        }
        echo '</nav>';

        // Inline CSS for tab panes
        echo '<style>
.themisdb-detail-pane-section {
    display: none;
    padding: 0;
}
.themisdb-detail-pane-section.active {
    display: block;
}
.nav-tab[data-tab] {
    cursor: pointer;
}
        </style>';

        // Inline JS for tab switching
        echo '<script>
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll(".nav-tab[data-tab]").forEach(function(tab) {
        tab.addEventListener("click", function(e) {
            e.preventDefault();
            var tabKey = this.getAttribute("data-tab");
            
            // Deactivate all tabs and panes
            document.querySelectorAll(".nav-tab[data-tab]").forEach(function(t) {
                t.classList.remove("nav-tab-active");
            });
            document.querySelectorAll(".themisdb-detail-pane-section").forEach(function(pane) {
                pane.classList.remove("active");
            });

            // Activate selected tab and pane
            this.classList.add("nav-tab-active");
            var pane = document.getElementById("themisdb-pane-" + tabKey);
            if (pane) {
                pane.classList.add("active");
            }
        });
    });
});
        </script>';

        return $current_tab;
    }

    /**
     * Render a single detail tab pane wrapper.
     */
    private function render_detail_tab_pane($tab_key, $is_active = false) {
        $class = $is_active ? 'themisdb-detail-pane-section active' : 'themisdb-detail-pane-section';
        echo '<div id="themisdb-pane-' . esc_attr($tab_key) . '" class="' . esc_attr($class) . '">';
    }

    /**
     * Close a detail tab pane.
     */
    private function close_detail_tab_pane() {
        echo '</div>';
    }

    private function render_module_navigation_tabs($current_page) {
        // Global cross-module tab navigation is intentionally disabled.
        // Each module now uses its own module-specific tab view.
        return;
    }

    /**
     * Render an expandable table row with detail tabs.
     * Call this once before the table to enqueue styles and scripts.
     */
    public function enqueue_expandable_row_assets() {
        static $enqueued = false;
        if ($enqueued) {
            return;
        }
        $enqueued = true;

        // Inline CSS for expandable rows
        echo '<style>
.themisdb-expand-toggle {
    cursor: pointer;
    user-select: none;
    padding: 4px 8px;
    background: #f0f0f0;
    border: 1px solid #ddd;
    border-radius: 3px;
    font-size: 12px;
    display: inline-block;
}
.themisdb-expand-toggle:hover {
    background: #e0e0e0;
}
.themisdb-expand-toggle.expanded::before {
    content: "▼ ";
}
.themisdb-expand-toggle.collapsed::before {
    content: "▶ ";
}
.themisdb-row-details {
    display: none;
}
.themisdb-row-details.expanded {
    display: table-row;
}
.themisdb-row-details td {
    padding: 12px !important;
    background: #fafafa;
    border-top: 1px solid #ddd;
}
.themisdb-detail-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 12px;
    border-bottom: 1px solid #ddd;
}
.themisdb-detail-tab {
    padding: 8px 12px;
    cursor: pointer;
    background: #f0f0f0;
    border: 1px solid #ddd;
    border-bottom: none;
    border-radius: 3px 3px 0 0;
    font-size: 13px;
    transition: background 0.2s;
}
.themisdb-detail-tab:hover {
    background: #e8e8e8;
}
.themisdb-detail-tab.active {
    background: #fff;
    border-bottom: 1px solid #fff;
    font-weight: 600;
}
.themisdb-detail-pane {
    display: none;
    padding: 12px 0;
}
.themisdb-detail-pane.active {
    display: block;
}
.themisdb-detail-section h4 {
    margin: 12px 0 6px 0;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    color: #666;
}
.themisdb-detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    font-size: 13px;
}
.themisdb-detail-field {
    display: flex;
    flex-direction: column;
}
.themisdb-detail-field strong {
    color: #333;
    margin-bottom: 2px;
}
.themisdb-detail-field span {
    color: #666;
    word-break: break-word;
}
        </style>';

        // Inline JavaScript for toggle functionality
        echo '<script>
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll(".themisdb-expand-toggle").forEach(function(btn) {
        btn.addEventListener("click", function(e) {
            e.preventDefault();
            var rowId = this.getAttribute("data-row-id");
            var detailRow = document.getElementById("themisdb-details-" + rowId);
            
            if (!detailRow) return;
            
            var isExpanded = detailRow.classList.contains("expanded");
            
            if (isExpanded) {
                detailRow.classList.remove("expanded");
                this.classList.remove("expanded");
                this.classList.add("collapsed");
            } else {
                detailRow.classList.add("expanded");
                this.classList.remove("collapsed");
                this.classList.add("expanded");
            }
        });
    });

    document.querySelectorAll(".themisdb-detail-tab").forEach(function(tab) {
        tab.addEventListener("click", function(e) {
            e.preventDefault();
            var paneId = this.getAttribute("data-pane");
            var container = this.closest(".themisdb-detail-tabs").nextElementSibling;
            
            if (!container) return;

            // Hide all panes in this container
            container.querySelectorAll(".themisdb-detail-pane").forEach(function(pane) {
                pane.classList.remove("active");
            });

            // Deactivate all tabs
            this.parentElement.querySelectorAll(".themisdb-detail-tab").forEach(function(t) {
                t.classList.remove("active");
            });

            // Show selected pane and activate tab
            var pane = container.querySelector("#" + paneId);
            if (pane) {
                pane.classList.add("active");
                this.classList.add("active");
            }
        });

        // Activate first tab by default
        if (this.parentElement.querySelector(".themisdb-detail-tab.active") === null) {
            var firstTab = this.parentElement.querySelector(".themisdb-detail-tab");
            if (firstTab) {
                firstTab.click();
            }
        }
    });
});
        </script>';
    }

    /**
     * Render start of an expandable table row.
     * Usage: Before rendering table cells, call this to get expand button.
     */
    private function render_row_expand_button($row_id) {
        echo '<td class="check-column">';
        echo '<button type="button" class="themisdb-expand-toggle collapsed" data-row-id="' . esc_attr($row_id) . '">' . __('Details', 'themisdb-order-request') . '</button>';
        echo '</td>';
    }

    /**
     * Render the detail row with tabs and content.
     * $tabs = array key => label
     * $panes = array key => html content for each pane
     */
    private function render_row_details($row_id, $colspan, $tabs, $panes) {
        ?>
        <tr id="themisdb-details-<?php echo esc_attr($row_id); ?>" class="themisdb-row-details">
            <td colspan="<?php echo intval($colspan); ?>" style="padding: 12px;">
                <div class="themisdb-detail-tabs">
                    <?php
                    $first = true;
                    foreach ($tabs as $key => $label): ?>
                        <a href="#" class="themisdb-detail-tab <?php echo $first ? 'active' : ''; ?>" data-pane="themisdb-pane-<?php echo esc_attr($row_id . '-' . $key); ?>">
                            <?php echo esc_html($label); ?>
                        </a>
                        <?php $first = false; ?>
                    <?php endforeach; ?>
                </div>

                <div class="themisdb-detail-panes">
                    <?php
                    $first = true;
                    foreach ($panes as $key => $content): ?>
                        <div id="themisdb-pane-<?php echo esc_attr($row_id . '-' . $key); ?>" class="themisdb-detail-pane <?php echo $first ? 'active' : ''; ?>">
                            <?php echo wp_kses_post($content); ?>
                        </div>
                        <?php $first = false; ?>
                    <?php endforeach; ?>
                </div>
            </td>
        </tr>
        <?php
    }


    /**
     * Render a compact button bar for local filters (status/category/entity).
     */
    private function render_filter_button_bar($param_name, $items, $active_key, $base_url, $query_args = array(), $extra_nav_class = '') {
        if (empty($items) || !is_array($items)) {
            return;
        }

        $nav_class = 'nav-tab-wrapper';
        if (!empty($extra_nav_class)) {
            $nav_class .= ' ' . sanitize_html_class($extra_nav_class);
        }

        echo '<nav class="' . esc_attr($nav_class) . '" style="margin-bottom:1rem; display:flex !important; flex-wrap:nowrap; overflow-x:auto; overflow-y:hidden; white-space:nowrap; -webkit-overflow-scrolling:touch;">';

        foreach ($items as $key => $label) {
            $url = add_query_arg(array_merge((array) $query_args, array($param_name => $key)), $base_url);
            $class = ($active_key === $key) ? 'nav-tab nav-tab-active' : 'nav-tab';

            echo '<a href="' . esc_url($url) . '" class="' . esc_attr($class) . ' themisdb-ajax-link" style="float:none; flex:0 0 auto; white-space:nowrap;">' . esc_html($label) . '</a>';
        }

        echo '</nav>';
    }

    /**
     * Translate payment status slugs to localized labels.
     */
    private function get_payment_status_label($status) {
        $labels = array(
            'pending' => __('Ausstehend', 'themisdb-order-request'),
            'verified' => __('Verifiziert', 'themisdb-order-request'),
            'overdue' => __('Überfällig', 'themisdb-order-request'),
            'failed' => __('Fehlgeschlagen', 'themisdb-order-request'),
        );

        $status = sanitize_key((string) $status);
        return isset($labels[$status]) ? $labels[$status] : ucfirst($status);
    }

    /**
     * Translate order status slugs to localized labels.
     */
    private function get_order_status_label($status) {
        $labels = array(
            'draft' => __('Entwurf', 'themisdb-order-request'),
            'pending' => __('Ausstehend', 'themisdb-order-request'),
            'confirmed' => __('Bestätigt', 'themisdb-order-request'),
            'signed' => __('Unterschrieben', 'themisdb-order-request'),
            'active' => __('Aktiv', 'themisdb-order-request'),
            'suspended' => __('Suspendiert', 'themisdb-order-request'),
            'ended' => __('Beendet', 'themisdb-order-request'),
            'cancelled' => __('Storniert', 'themisdb-order-request'),
            'fulfilled' => __('Erfüllt', 'themisdb-order-request'),
            'failed' => __('Fehlgeschlagen', 'themisdb-order-request'),
        );

        $status = sanitize_key((string) $status);
        return isset($labels[$status]) ? $labels[$status] : ucfirst($status);
    }

    /**
     * Translate contract status slugs to localized labels.
     */
    private function get_contract_status_label($status) {
        $labels = array(
            'draft' => __('Entwurf', 'themisdb-order-request'),
            'signed' => __('Unterschrieben', 'themisdb-order-request'),
            'active' => __('Aktiv', 'themisdb-order-request'),
            'suspended' => __('Suspendiert', 'themisdb-order-request'),
            'ended' => __('Beendet', 'themisdb-order-request'),
            'cancelled' => __('Storniert', 'themisdb-order-request'),
        );

        $status = sanitize_key((string) $status);
        return isset($labels[$status]) ? $labels[$status] : ucfirst($status);
    }

    /**
     * Translate license status slugs to localized labels.
     */
    private function get_license_status_label($status) {
        $labels = array(
            'pending' => __('Ausstehend', 'themisdb-order-request'),
            'active' => __('Aktiv', 'themisdb-order-request'),
            'suspended' => __('Suspendiert', 'themisdb-order-request'),
            'expired' => __('Abgelaufen', 'themisdb-order-request'),
            'cancelled' => __('Gekündigt', 'themisdb-order-request'),
        );

        $status = sanitize_key((string) $status);
        return isset($labels[$status]) ? $labels[$status] : ucfirst($status);
    }

    /**
     * Translate fulfillment status slugs to localized labels.
     */
    private function get_fulfillment_status_label($status) {
        $labels = array(
            'not_required' => __('Nicht erforderlich', 'themisdb-order-request'),
            'pending' => __('Ausstehend', 'themisdb-order-request'),
            'picking' => __('Kommissionierung', 'themisdb-order-request'),
            'packed' => __('Verpackt', 'themisdb-order-request'),
            'shipped' => __('Versendet', 'themisdb-order-request'),
            'delivered' => __('Zugestellt', 'themisdb-order-request'),
            'cancelled' => __('Storniert', 'themisdb-order-request'),
        );

        $status = sanitize_key((string) $status);
        return isset($labels[$status]) ? $labels[$status] : ucfirst($status);
    }

    /**
     * Translate product edition slugs to localized labels.
     */
    private function get_product_edition_label($edition) {
        $labels = array(
            'community' => __('Community', 'themisdb-order-request'),
            'enterprise' => __('Enterprise', 'themisdb-order-request'),
            'hyperscaler' => __('Hyperscaler', 'themisdb-order-request'),
            'reseller' => __('Reseller', 'themisdb-order-request'),
        );

        $edition = sanitize_key((string) $edition);
        return isset($labels[$edition]) ? $labels[$edition] : ucfirst($edition);
    }

    /**
     * Translate product type slugs to localized labels.
     */
    private function get_product_type_label($type) {
        $labels = array(
            'database' => __('Datenbank', 'themisdb-order-request'),
            'module_license' => __('Modullizenz', 'themisdb-order-request'),
            'plugin' => __('Plugin', 'themisdb-order-request'),
            'support_sku' => __('Support', 'themisdb-order-request'),
            'merchandise' => __('Merchandise', 'themisdb-order-request'),
            'training' => __('Schulung', 'themisdb-order-request'),
            'online' => __('Online', 'themisdb-order-request'),
            'onsite' => __('Vor Ort', 'themisdb-order-request'),
        );

        $type = sanitize_key((string) $type);
        return isset($labels[$type]) ? $labels[$type] : ucfirst($type);
    }

    /**
     * Translate contract type slugs to localized labels.
     */
    private function get_contract_type_label($type) {
        $labels = array(
            'license' => __('Lizenzvertrag', 'themisdb-order-request'),
            'invoice' => __('Rechnungsvertrag', 'themisdb-order-request'),
            'service' => __('Servicevertrag', 'themisdb-order-request'),
        );

        $type = sanitize_key((string) $type);
        return isset($labels[$type]) ? $labels[$type] : ucfirst($type);
    }

    /**
     * Translate license type slugs to localized labels.
     */
    private function get_license_type_label($type) {
        $labels = array(
            'standard' => __('Standard', 'themisdb-order-request'),
            'trial' => __('Testlizenz', 'themisdb-order-request'),
            'subscription' => __('Abonnement', 'themisdb-order-request'),
            'perpetual' => __('Unbefristet', 'themisdb-order-request'),
        );

        $type = sanitize_key((string) $type);
        return isset($labels[$type]) ? $labels[$type] : ucfirst($type);
    }

    /**
     * Translate payment method slugs to localized labels.
     */
    private function get_payment_method_label($method) {
        $labels = array(
            'invoice' => __('Rechnung', 'themisdb-order-request'),
            'bank_transfer' => __('Banküberweisung', 'themisdb-order-request'),
            'sepa' => __('SEPA', 'themisdb-order-request'),
            'credit_card' => __('Kreditkarte', 'themisdb-order-request'),
            'paypal' => __('PayPal', 'themisdb-order-request'),
        );

        $method = sanitize_key((string) $method);
        return isset($labels[$method]) ? $labels[$method] : ucfirst($method);
    }

    /**
     * Translate customer type slugs to localized labels.
     */
    private function get_customer_type_label($type) {
        $labels = array(
            'consumer' => __('Privatkunde', 'themisdb-order-request'),
            'business' => __('Geschäftskunde', 'themisdb-order-request'),
            'government' => __('Behörde', 'themisdb-order-request'),
            'education' => __('Bildungseinrichtung', 'themisdb-order-request'),
            'reseller' => __('Wiederverkäufer', 'themisdb-order-request'),
        );

        $type = sanitize_key((string) $type);
        return isset($labels[$type]) ? $labels[$type] : ucfirst($type);
    }

    /**
     * Translate email log status slugs to localized labels.
     */
    private function get_email_log_status_label($status) {
        $labels = array(
            'queued' => __('In Warteschlange', 'themisdb-order-request'),
            'pending' => __('Ausstehend', 'themisdb-order-request'),
            'sent' => __('Gesendet', 'themisdb-order-request'),
            'failed' => __('Fehlgeschlagen', 'themisdb-order-request'),
        );

        $status = sanitize_key((string) $status);
        return isset($labels[$status]) ? $labels[$status] : ucfirst($status);
    }

    /**
     * Order dashboard with compact KPIs and latest activity.
     */
    public function order_dashboard_page() {
        global $wpdb;

        $table_orders = $wpdb->prefix . 'themisdb_orders';
        $table_contracts = $wpdb->prefix . 'themisdb_contracts';

        if (!preg_match('/^[A-Za-z0-9_]+$/', $table_orders) || !preg_match('/^[A-Za-z0-9_]+$/', $table_contracts)) {
            $this->abort_with_log(
                __('Ungültige Tabellenkonfiguration.', 'themisdb-order-request'),
                'Admin dashboard aborted due to invalid table configuration',
                array(
                    'table_orders' => (string) $table_orders,
                    'table_contracts' => (string) $table_contracts,
                ),
                'critical'
            );
        }

        $table_orders_sql = '`' . $table_orders . '`';
        $table_contracts_sql = '`' . $table_contracts . '`';

        $order_total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_orders_sql}");
        $order_open = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table_orders_sql} WHERE status IN ('draft','pending','confirmed')"
        );
        $order_active = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table_orders_sql} WHERE status IN ('active','fulfilled')"
        );
        $order_cancelled = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table_orders_sql} WHERE status IN ('cancelled','failed')"
        );

        $contract_total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_contracts_sql}");
        $payment_stats = ThemisDB_Payment_Manager::get_payment_stats();
        $license_stats = ThemisDB_License_Manager::get_license_stats();
        $products_count = count((array) ThemisDB_Order_Manager::get_products(true));
        $modules_count = count((array) ThemisDB_Order_Manager::get_modules(null, true));
        $trainings_count = count((array) ThemisDB_Order_Manager::get_training_modules(null, true));
        $inventory_count = count((array) ThemisDB_Order_Manager::get_inventory_items(true));

        $module_tiles = array(
            array(
                'title' => __('Bestellungen', 'themisdb-order-request'),
                'slug' => 'themisdb-orders',
                'count' => $order_total,
                'meta' => __('Offen: ', 'themisdb-order-request') . number_format_i18n($order_open),
                'state' => $order_open > 0 ? 'info' : 'ok',
            ),
            array(
                'title' => __('Verträge', 'themisdb-order-request'),
                'slug' => 'themisdb-contracts',
                'count' => $contract_total,
                'meta' => __('Gesamt', 'themisdb-order-request'),
                'state' => 'ok',
            ),
            array(
                'title' => __('Produkte', 'themisdb-order-request'),
                'slug' => 'themisdb-products',
                'count' => $products_count,
                'meta' => __('Module: ', 'themisdb-order-request') . number_format_i18n($modules_count) . ' | ' . __('Trainings: ', 'themisdb-order-request') . number_format_i18n($trainings_count),
                'state' => 'ok',
            ),
            array(
                'title' => __('Lagerbestand', 'themisdb-order-request'),
                'slug' => 'themisdb-inventory',
                'count' => $inventory_count,
                'meta' => __('Artikel', 'themisdb-order-request'),
                'state' => 'ok',
            ),
            array(
                'title' => __('Zahlungen', 'themisdb-order-request'),
                'slug' => 'themisdb-payments',
                'count' => (int) ($payment_stats['total_payments'] ?? 0),
                'meta' => __('Offen: ', 'themisdb-order-request') . number_format_i18n((int) ($payment_stats['pending_payments'] ?? 0)),
                'state' => ((int) ($payment_stats['pending_payments'] ?? 0)) > 0 ? 'warn' : 'ok',
            ),
            array(
                'title' => __('Lizenzen', 'themisdb-order-request'),
                'slug' => 'themisdb-licenses',
                'count' => (int) ($license_stats['total_licenses'] ?? 0),
                'meta' => __('Aktiv: ', 'themisdb-order-request') . number_format_i18n((int) ($license_stats['active_licenses'] ?? 0)),
                'state' => ((int) ($license_stats['suspended_licenses'] ?? 0)) > 0 ? 'info' : 'ok',
            ),
            array(
                'title' => __('E-Mail Log', 'themisdb-order-request'),
                'slug' => 'themisdb-email-log',
                'count' => null,
                'meta' => __('Kommunikation und Versandstatus', 'themisdb-order-request'),
                'state' => 'ok',
            ),
            array(
                'title' => __('License Audit Log', 'themisdb-order-request'),
                'slug' => 'themisdb-license-audit',
                'count' => null,
                'meta' => __('Lizenzereignisse und Historie', 'themisdb-order-request'),
                'state' => 'ok',
            ),
            array(
                'title' => __('Bankimport', 'themisdb-order-request'),
                'slug' => 'themisdb-bank-import',
                'count' => null,
                'meta' => __('Import und Zuordnung von Buchungen', 'themisdb-order-request'),
                'state' => 'ok',
            ),
            array(
                'title' => __('Einstellungen', 'themisdb-order-request'),
                'slug' => 'themisdb-order-settings',
                'count' => null,
                'meta' => __('Konfiguration und Integrationen', 'themisdb-order-request'),
                'state' => 'ok',
            ),
        );

        $recent_orders = ThemisDB_Order_Manager::get_all_orders(array(
            'limit' => 8,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC',
        ));

        ?>
        <div class="wrap themisdb-order-dashboard">
            <h1 class="wp-heading-inline"><?php _e('Order Dashboard - Modulübersicht', 'themisdb-order-request'); ?></h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-orders&action=new')); ?>" class="page-title-action"><?php _e('Neue Bestellung', 'themisdb-order-request'); ?></a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-order-settings')); ?>" class="page-title-action"><?php _e('Einstellungen', 'themisdb-order-request'); ?></a>
            <hr class="wp-header-end">
            <?php $this->render_module_navigation_tabs('themisdb-order-dashboard'); ?>

            <div class="themisdb-dashboard-cards">
                <div class="themisdb-dashboard-card">
                    <h2><?php _e('Bestellungen gesamt', 'themisdb-order-request'); ?></h2>
                    <p class="themisdb-dashboard-value"><?php echo esc_html(number_format_i18n($order_total)); ?></p>
                </div>
                <div class="themisdb-dashboard-card">
                    <h2><?php _e('Offene Bestellungen', 'themisdb-order-request'); ?></h2>
                    <p class="themisdb-dashboard-value"><?php echo esc_html(number_format_i18n($order_open)); ?></p>
                </div>
                <div class="themisdb-dashboard-card">
                    <h2><?php _e('Aktive Bestellungen', 'themisdb-order-request'); ?></h2>
                    <p class="themisdb-dashboard-value"><?php echo esc_html(number_format_i18n($order_active)); ?></p>
                </div>
                <div class="themisdb-dashboard-card">
                    <h2><?php _e('Abgebrochen/Fehlgeschlagen', 'themisdb-order-request'); ?></h2>
                    <p class="themisdb-dashboard-value"><?php echo esc_html(number_format_i18n($order_cancelled)); ?></p>
                </div>
                <div class="themisdb-dashboard-card">
                    <h2><?php _e('Verträge', 'themisdb-order-request'); ?></h2>
                    <p class="themisdb-dashboard-value"><?php echo esc_html(number_format_i18n($contract_total)); ?></p>
                </div>
                <div class="themisdb-dashboard-card">
                    <h2><?php _e('Zahlungen (offen)', 'themisdb-order-request'); ?></h2>
                    <p class="themisdb-dashboard-value"><?php echo esc_html(number_format_i18n((int) ($payment_stats['pending_payments'] ?? 0))); ?></p>
                </div>
                <div class="themisdb-dashboard-card">
                    <h2><?php _e('Lizenzen (aktiv)', 'themisdb-order-request'); ?></h2>
                    <p class="themisdb-dashboard-value"><?php echo esc_html(number_format_i18n((int) ($license_stats['active_licenses'] ?? 0))); ?></p>
                </div>
            </div>

            <div class="themisdb-dashboard-actions" style="margin: 12px 0 18px; display:flex; gap:8px; flex-wrap:wrap;">
                <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-orders&action=new')); ?>" class="button button-primary"><?php _e('+ Neue Bestellung', 'themisdb-order-request'); ?></a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-orders')); ?>" class="button"><?php _e('Bestellungen öffnen', 'themisdb-order-request'); ?></a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-contracts')); ?>" class="button"><?php _e('Verträge öffnen', 'themisdb-order-request'); ?></a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-payments')); ?>" class="button"><?php _e('Zahlungen öffnen', 'themisdb-order-request'); ?></a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-licenses')); ?>" class="button"><?php _e('Lizenzen öffnen', 'themisdb-order-request'); ?></a>
            </div>

            <div class="card" style="max-width:none; margin-bottom:16px;">
                <h2><?php _e('Module', 'themisdb-order-request'); ?></h2>
                <div class="themisdb-module-grid">
                    <?php foreach ($module_tiles as $tile): ?>
                        <a class="themisdb-module-tile themisdb-module-tile--<?php echo esc_attr($tile['state'] ?? 'ok'); ?>" href="<?php echo esc_url(admin_url('admin.php?page=' . $tile['slug'])); ?>">
                            <strong class="themisdb-module-title"><?php echo esc_html($tile['title']); ?></strong>
                            <span class="themisdb-module-meta"><?php echo esc_html($tile['meta']); ?></span>
                            <?php if ($tile['count'] !== null): ?>
                                <span class="themisdb-module-count"><?php echo esc_html(number_format_i18n((int) $tile['count'])); ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card" style="max-width:none;">
                <h2><?php _e('Neueste Bestellungen', 'themisdb-order-request'); ?></h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Bestellnummer', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Kunde', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Betrag', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Status', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Datum', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Aktion', 'themisdb-order-request'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_orders)): ?>
                        <tr>
                            <td colspan="6"><?php _e('Noch keine Bestellungen vorhanden.', 'themisdb-order-request'); ?></td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($recent_orders as $order): ?>
                            <tr>
                                <td><strong><?php echo esc_html($order['order_number']); ?></strong></td>
                                <td><?php echo esc_html($order['customer_name']); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) ($order['total_amount'] ?? 0), 2)); ?> <?php echo esc_html($order['currency'] ?? 'EUR'); ?></td>
                                <td><span class="order-status status-<?php echo esc_attr($order['status']); ?>"><?php echo esc_html($this->get_order_status_label($order['status'])); ?></span></td>
                                <td><?php echo esc_html(wp_date('d.m.Y H:i', strtotime((string) $order['created_at']))); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-orders&action=view&order_id=' . absint($order['id']))); ?>" class="button button-small">
                                        <?php _e('Ansehen', 'themisdb-order-request'); ?>
                                    </a>
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

                    if ($post_action === 'bulk_order_workflow') {
                        check_admin_referer('themisdb_bulk_order_workflow');
                        $this->handle_bulk_order_workflow();
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
            $prefs = $this->get_list_preferences('orders');
            $status_tab = isset($_GET['status_tab']) ? sanitize_text_field($_GET['status_tab']) : ($prefs['status_tab'] ?? 'all');
            $search_query = sanitize_text_field($_GET['q'] ?? '');
            $sort_by = sanitize_key($_GET['sort_by'] ?? ($prefs['sort_by'] ?? 'date'));
            $sort_dir = sanitize_key($_GET['sort_dir'] ?? ($prefs['sort_dir'] ?? 'desc'));
            $per_page = $this->resolve_per_page($_GET['per_page'] ?? ($prefs['per_page'] ?? 25), 25);
            $paged = max(1, absint($_GET['paged'] ?? 1));

            // Get orders, excluding those that already have contracts
            $all_orders = ThemisDB_Order_Manager::get_all_orders(array(
                'exclude_with_contracts' => true,
                'limit' => 9999,  // Get all (we'll filter client-side for counts)
            ));

            $status_counts = array(
                'draft' => 0, 'pending' => 0, 'confirmed' => 0,
                'signed' => 0, 'active' => 0, 'suspended' => 0, 'ended' => 0, 'cancelled' => 0,
            );
            foreach ($all_orders as $o) {
                $s = $o['status'] ?? '';
                if (isset($status_counts[$s])) {
                    $status_counts[$s]++;
                }
            }

            $orders = $all_orders;
            if ($status_tab !== 'all') {
                $orders = array_values(array_filter($orders, function ($o) use ($status_tab) {
                    return ($o['status'] ?? '') === $status_tab;
                }));
            }

            if ($search_query !== '') {
                $orders = $this->filter_rows_by_search($orders, $search_query, array('order_number', 'customer_name', 'customer_company', 'product_edition', 'status'));
            }

            $sort_map = array(
                'number'   => 'order_number',
                'customer' => 'customer_name',
                'edition'  => 'product_edition',
                'amount'   => 'total_amount',
                'status'   => 'status',
                'date'     => 'created_at',
            );
            $sort_by  = array_key_exists($sort_by, $sort_map) ? $sort_by : 'date';
            $sort_dir = in_array($sort_dir, array('asc', 'desc'), true) ? $sort_dir : 'desc';

            $this->save_list_preferences('orders', array(
                'status_tab' => $status_tab,
                'sort_by'    => $sort_by,
                'sort_dir'   => $sort_dir,
                'per_page'   => $per_page,
            ));

            $orders     = $this->sort_rows($orders, $sort_map[$sort_by], $sort_dir, array('total_amount'), array('created_at'));
            $pagination = $this->paginate_rows($orders, $per_page, $paged);
            $orders     = $pagination['items'];

            $bulk_actions = array(
                'confirmed' => __('Bestätigen', 'themisdb-order-request'),
                'active'    => __('Aktivieren', 'themisdb-order-request'),
                'suspended' => __('Suspendieren', 'themisdb-order-request'),
                'ended'     => __('Beenden', 'themisdb-order-request'),
                'cancelled' => __('Stornieren', 'themisdb-order-request'),
                'delete'    => __('Löschen', 'themisdb-order-request'),
            );

            $tab_base   = admin_url('admin.php?page=themisdb-orders');
            $order_tabs = array(
                'all'       => __('Alle', 'themisdb-order-request') . ' (' . count($all_orders) . ')',
                'draft'     => __('Entwurf', 'themisdb-order-request') . ' (' . $status_counts['draft'] . ')',
                'pending'   => __('Ausstehend', 'themisdb-order-request') . ' (' . $status_counts['pending'] . ')',
                'confirmed' => __('Bestätigt', 'themisdb-order-request') . ' (' . $status_counts['confirmed'] . ')',
                'signed'    => __('Unterschrieben', 'themisdb-order-request') . ' (' . $status_counts['signed'] . ')',
                'active'    => __('Aktiv', 'themisdb-order-request') . ' (' . $status_counts['active'] . ')',
                'suspended' => __('Suspendiert', 'themisdb-order-request') . ' (' . $status_counts['suspended'] . ')',
                'ended'     => __('Beendet', 'themisdb-order-request') . ' (' . $status_counts['ended'] . ')',
                'cancelled' => __('Storniert', 'themisdb-order-request') . ' (' . $status_counts['cancelled'] . ')',
            );

            ?>
            <div class="wrap">
                <h1 class="wp-heading-inline"><?php _e('Bestellungen', 'themisdb-order-request'); ?></h1>
                <a class="page-title-action" href="<?php echo esc_url(admin_url('admin.php?page=themisdb-orders&action=new')); ?>"><?php _e('Neue Bestellung', 'themisdb-order-request'); ?></a>
                <a class="page-title-action" href="<?php echo esc_url(admin_url('admin.php?page=themisdb-contracts')); ?>"><?php _e('Zu Verträgen', 'themisdb-order-request'); ?></a>
                <a class="page-title-action" href="<?php echo esc_url(admin_url('admin.php?page=themisdb-order-dashboard')); ?>"><?php _e('Zum Dashboard', 'themisdb-order-request'); ?></a>
                <hr class="wp-header-end">
                <?php $this->render_module_navigation_tabs('themisdb-orders'); ?>

                <?php $this->render_bulk_notice('bulk_orders'); ?>

                <?php $this->render_filter_button_bar('status_tab', $order_tabs, $status_tab, $tab_base, array(
                    'q'        => $search_query,
                    'sort_by'  => $sort_by,
                    'sort_dir' => $sort_dir,
                    'per_page' => $per_page,
                )); ?>

                <form method="get" class="themisdb-ajax-form" style="margin:8px 0 12px; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                    <input type="hidden" name="page" value="themisdb-orders">
                    <input type="hidden" name="status_tab" value="<?php echo esc_attr($status_tab); ?>">
                    <input type="hidden" name="sort_by" value="<?php echo esc_attr($sort_by); ?>">
                    <input type="hidden" name="sort_dir" value="<?php echo esc_attr($sort_dir); ?>">
                    <input type="hidden" name="paged" value="1">
                    <input type="search" name="q" value="<?php echo esc_attr($search_query); ?>" placeholder="<?php esc_attr_e('Suche nach Bestellnummer, Kunde, Edition …', 'themisdb-order-request'); ?>" class="regular-text">
                    <button type="submit" class="button"><?php _e('Suchen', 'themisdb-order-request'); ?></button>
                    <?php $this->render_per_page_select('per_page', $per_page); ?>
                </form>

                <?php $this->render_inline_help_box(
                    __('Inline-Hilfe: Bestellungen', 'themisdb-order-request'),
                    array(
                        __('Status-Tabs und Suche wirken zusammen auf dieselbe Liste.', 'themisdb-order-request'),
                        __('Sortierung und Seitenlänge bleiben beim Tab-Wechsel erhalten.', 'themisdb-order-request'),
                        __('Bulk-Workflows gelten nur für markierte Bestellungen.', 'themisdb-order-request'),
                        __('Bereits in Verträge übernommene Bestellungen erscheinen nur im Bereich Verträge.', 'themisdb-order-request'),
                    )
                ); ?>

                <div class="themisdb-admin-modules" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;margin:20px 0;">
                    <div class="card" style="max-width:none;">
                        <h2><?php _e('Bestellstatus', 'themisdb-order-request'); ?></h2>
                        <table class="widefat striped" style="border:none;box-shadow:none;">
                            <tbody>
                                <tr>
                                    <td><?php _e('Gesamt', 'themisdb-order-request'); ?></td>
                                    <td><strong><?php echo esc_html(number_format_i18n(count($all_orders))); ?></strong></td>
                                </tr>
                                <tr>
                                    <td><?php _e('Ausstehend', 'themisdb-order-request'); ?></td>
                                    <td><?php echo esc_html(number_format_i18n((int) $status_counts['pending'])); ?></td>
                                </tr>
                                <tr>
                                    <td><?php _e('Bestätigt', 'themisdb-order-request'); ?></td>
                                    <td><?php echo esc_html(number_format_i18n((int) $status_counts['confirmed'])); ?></td>
                                </tr>
                                <tr>
                                    <td><?php _e('Aktiv', 'themisdb-order-request'); ?></td>
                                    <td><?php echo esc_html(number_format_i18n((int) $status_counts['active'])); ?></td>
                                </tr>
                                <tr>
                                    <td><?php _e('Suspendiert', 'themisdb-order-request'); ?></td>
                                    <td><?php echo esc_html(number_format_i18n((int) $status_counts['suspended'])); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="card" style="max-width:none;">
                        <h2><?php _e('Schnellaktionen', 'themisdb-order-request'); ?></h2>
                        <p><?php _e('Neue Bestellungen anlegen, aktive Vertrage prufen oder offene Bestellungen fokussieren.', 'themisdb-order-request'); ?></p>
                        <p>
                            <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=themisdb-orders&action=new')); ?>"><?php _e('Bestellung anlegen', 'themisdb-order-request'); ?></a>
                            <a class="button" href="<?php echo esc_url(add_query_arg(array('page' => 'themisdb-orders', 'status_tab' => 'pending'), admin_url('admin.php'))); ?>"><?php _e('Offene Bestellungen', 'themisdb-order-request'); ?></a>
                            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=themisdb-contracts')); ?>"><?php _e('Verträge ansehen', 'themisdb-order-request'); ?></a>
                        </p>
                    </div>
                </div>

                <?php if (isset($_GET['message']) && $_GET['message'] === 'saved'): ?>
                <div class="notice notice-success"><p><?php _e('Bestellung wurde gespeichert.', 'themisdb-order-request'); ?></p></div>
                <?php endif; ?>
                <?php if (isset($_GET['message']) && $_GET['message'] === 'deleted'): ?>
                <div class="notice notice-success"><p><?php _e('Bestellung wurde gelöscht.', 'themisdb-order-request'); ?></p></div>
                <?php endif; ?>

                <form method="post" class="themisdb-list-root" id="themisdb-orders-list-root">
                    <?php 
                    $this->enqueue_expandable_row_assets();
                    wp_nonce_field('themisdb_bulk_order_workflow'); 
                    ?>
                    <input type="hidden" name="action" value="bulk_order_workflow">
                    <?php $this->render_bulk_action_bar('order_workflow', $bulk_actions, __('Ausgewählte Bestellungen verarbeiten', 'themisdb-order-request')); ?>

                    <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td class="check-column" style="width:100px;"><input type="checkbox" class="themisdb-bulk-toggle" aria-label="<?php esc_attr_e('Alle Bestellungen auswählen', 'themisdb-order-request'); ?>"></td>
                            <th><?php $this->render_sortable_column(__('Bestellnummer', 'themisdb-order-request'), 'number', $sort_by, $sort_dir, admin_url('admin.php?page=themisdb-orders'), array('status_tab' => $status_tab, 'q' => $search_query, 'per_page' => $per_page)); ?></th>
                            <th><?php $this->render_sortable_column(__('Kunde', 'themisdb-order-request'), 'customer', $sort_by, $sort_dir, admin_url('admin.php?page=themisdb-orders'), array('status_tab' => $status_tab, 'q' => $search_query, 'per_page' => $per_page)); ?></th>
                            <th><?php $this->render_sortable_column(__('Edition', 'themisdb-order-request'), 'edition', $sort_by, $sort_dir, admin_url('admin.php?page=themisdb-orders'), array('status_tab' => $status_tab, 'q' => $search_query, 'per_page' => $per_page)); ?></th>
                            <th><?php $this->render_sortable_column(__('Betrag', 'themisdb-order-request'), 'amount', $sort_by, $sort_dir, admin_url('admin.php?page=themisdb-orders'), array('status_tab' => $status_tab, 'q' => $search_query, 'per_page' => $per_page)); ?></th>
                            <th><?php $this->render_sortable_column(__('Status', 'themisdb-order-request'), 'status', $sort_by, $sort_dir, admin_url('admin.php?page=themisdb-orders'), array('status_tab' => $status_tab, 'q' => $search_query, 'per_page' => $per_page)); ?></th>
                            <th><?php $this->render_sortable_column(__('Datum', 'themisdb-order-request'), 'date', $sort_by, $sort_dir, admin_url('admin.php?page=themisdb-orders'), array('status_tab' => $status_tab, 'q' => $search_query, 'per_page' => $per_page)); ?></th>
                            <th><?php _e('Aktionen', 'themisdb-order-request'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="8"><?php _e('Keine Bestellungen gefunden', 'themisdb-order-request'); ?></td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <th scope="row" class="check-column" style="width:100px;">
                                    <input type="checkbox" name="order_ids[]" value="<?php echo absint($order['id']); ?>">
                                </th>
                                <td><strong><?php echo esc_html($order['order_number']); ?></strong></td>
                                <td>
                                    <?php echo esc_html($order['customer_name']); ?>
                                    <?php if ($order['customer_company']): ?>
                                        <br><small><?php echo esc_html($order['customer_company']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($this->get_product_edition_label($order['product_edition'])); ?></td>
                                <td><?php echo number_format($order['total_amount'], 2, ',', '.'); ?> <?php echo esc_html($order['currency']); ?></td>
                                <td>
                                    <span class="order-status status-<?php echo esc_attr($order['status']); ?>">
                                        <?php echo esc_html($this->get_order_status_label($order['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html(wp_date('d.m.Y H:i', strtotime($order['created_at']))); ?></td>
                                <td>
                                    <a href="?page=themisdb-orders&action=view&order_id=<?php echo absint($order['id']); ?>" class="button button-small"><?php _e('Ansehen', 'themisdb-order-request'); ?></a>
                                    <a href="?page=themisdb-orders&action=edit&order_id=<?php echo absint($order['id']); ?>" class="button button-small"><?php _e('Ändern', 'themisdb-order-request'); ?></a>
                                </td>
                            </tr>

                            <!-- Order Detail Tabs -->
                            <?php
                            $order_tabs = array(
                                'info' => __('Informationen', 'themisdb-order-request'),
                                'details' => __('Details', 'themisdb-order-request'),
                                'contract' => __('Vertrag', 'themisdb-order-request'),
                                'payment' => __('Zahlung', 'themisdb-order-request'),
                            );

                            $contract = !empty($order['id']) ? current(ThemisDB_Contract_Manager::get_contracts_by_order(intval($order['id']))) : null;
                            $payment = !empty($order['id']) ? current(ThemisDB_Payment_Manager::get_payments_by_order(intval($order['id']))) : null;
                            $order_items = !empty($order['id']) ? ThemisDB_Order_Manager::get_order_items(intval($order['id'])) : array();

                            ob_start();
                            ?>
                            <div class="themisdb-detail-section">
                                <h4><?php _e('Kundenangaben', 'themisdb-order-request'); ?></h4>
                                <div class="themisdb-detail-grid">
                                    <div class="themisdb-detail-field">
                                        <strong><?php _e('E-Mail', 'themisdb-order-request'); ?></strong>
                                        <span><?php echo esc_html($order['customer_email'] ?? '—'); ?></span>
                                    </div>
                                    <div class="themisdb-detail-field">
                                        <strong><?php _e('Typ', 'themisdb-order-request'); ?></strong>
                                        <span><?php echo esc_html($this->get_customer_type_label($order['customer_type'] ?? '')); ?></span>
                                    </div>
                                    <div class="themisdb-detail-field">
                                        <strong><?php _e('Betrag', 'themisdb-order-request'); ?></strong>
                                        <span><?php echo esc_html(number_format($order['total_amount'], 2, ',', '.') . ' ' . ($order['currency'] ?? 'EUR')); ?></span>
                                    </div>
                                    <div class="themisdb-detail-field">
                                        <strong><?php _e('Produkttyp', 'themisdb-order-request'); ?></strong>
                                        <span><?php echo esc_html($this->get_product_type_label($order['product_type'] ?? '')); ?></span>
                                    </div>
                                </div>
                            </div>
                            <?php
                            $tab_info = ob_get_clean();

                            ob_start();
                            ?>
                            <div class="themisdb-detail-section">
                                <h4><?php _e('Artikel', 'themisdb-order-request'); ?></h4>
                                <?php if (empty($order_items)): ?>
                                    <p style="color:#999;"><?php _e('Keine Artikel', 'themisdb-order-request'); ?></p>
                                <?php else: ?>
                                    <table style="width:100%; font-size:12px;">
                                        <tr style="border-bottom:1px solid #ddd;">
                                            <th style="text-align:left; padding:4px;"><?php _e('Name', 'themisdb-order-request'); ?></th>
                                            <th style="text-align:center; padding:4px;"><?php _e('Menge', 'themisdb-order-request'); ?></th>
                                            <th style="text-align:right; padding:4px;"><?php _e('Preis', 'themisdb-order-request'); ?></th>
                                        </tr>
                                        <?php foreach ($order_items as $item): ?>
                                        <tr style="border-bottom:1px solid #eee;">
                                            <td style="padding:4px;"><?php echo esc_html($item['item_name'] ?? '—'); ?></td>
                                            <td style="text-align:center; padding:4px;"><?php echo intval($item['quantity'] ?? 1); ?></td>
                                            <td style="text-align:right; padding:4px;"><?php echo number_format($item['unit_price'] ?? 0, 2, ',', '.'); ?> €</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </table>
                                <?php endif; ?>
                            </div>
                            <?php
                            $tab_details = ob_get_clean();

                            ob_start();
                            ?>
                            <div class="themisdb-detail-section">
                                <h4><?php _e('Vertragsangaben', 'themisdb-order-request'); ?></h4>
                                <?php if (!$contract): ?>
                                    <p style="color:#999;"><?php _e('Kein Vertrag verknüpft', 'themisdb-order-request'); ?></p>
                                <?php else: ?>
                                    <div class="themisdb-detail-grid">
                                        <div class="themisdb-detail-field">
                                            <strong><?php _e('Vertragstyp', 'themisdb-order-request'); ?></strong>
                                            <span><?php echo esc_html($this->get_contract_type_label($contract['contract_type'] ?? '')); ?></span>
                                        </div>
                                        <div class="themisdb-detail-field">
                                            <strong><?php _e('Status', 'themisdb-order-request'); ?></strong>
                                            <span><?php echo esc_html($this->get_contract_status_label($contract['status'] ?? '')); ?></span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php
                            $tab_contract = ob_get_clean();

                            ob_start();
                            ?>
                            <div class="themisdb-detail-section">
                                <h4><?php _e('Zahlungsstatus', 'themisdb-order-request'); ?></h4>
                                <?php if (!$payment): ?>
                                    <p style="color:#999;"><?php _e('Keine Zahlung vorhanden', 'themisdb-order-request'); ?></p>
                                <?php else: ?>
                                    <div class="themisdb-detail-grid">
                                        <div class="themisdb-detail-field">
                                            <strong><?php _e('Status', 'themisdb-order-request'); ?></strong>
                                            <span><?php echo esc_html($this->get_payment_status_label($payment['payment_status'] ?? '')); ?></span>
                                        </div>
                                        <div class="themisdb-detail-field">
                                            <strong><?php _e('Methode', 'themisdb-order-request'); ?></strong>
                                            <span><?php echo esc_html($this->get_payment_method_label($payment['payment_method'] ?? '')); ?></span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php
                            $tab_payment = ob_get_clean();

                            $detail_tabs = array(
                                'info' => $tab_info,
                                'details' => $tab_details,
                                'contract' => $tab_contract,
                                'payment' => $tab_payment,
                            );

                            $this->render_row_details(
                                'order-' . intval($order['id']),
                                8,
                                $order_tabs,
                                $detail_tabs
                            );
                            ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    </table>
                </form>

                <?php $this->render_simple_pagination(
                    admin_url('admin.php?page=themisdb-orders'),
                    $pagination['paged'],
                    $pagination['total_pages'],
                    array(
                        'status_tab' => $status_tab,
                        'q'          => $search_query,
                        'sort_by'    => $sort_by,
                        'sort_dir'   => $sort_dir,
                        'per_page'   => $per_page,
                    )
                ); ?>

                <?php $this->render_bulk_table_script(); ?>
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
        
        $tabs = array(
            'customer' => __('Kundendaten', 'themisdb-order-request'),
            'billing' => __('Rechnungsdaten', 'themisdb-order-request'),
            'legal' => __('Rechtlich', 'themisdb-order-request'),
            'items' => __('Artikel', 'themisdb-order-request'),
            'contracts' => __('Verträge', 'themisdb-order-request'),
        );
        
        // Add shipping tab only for merchandise orders
        if ($order['product_type'] === 'merchandise') {
            $tabs = array_slice($tabs, 0, 3, true) + array('shipping' => __('Versand', 'themisdb-order-request')) + array_slice($tabs, 3, null, true);
        }
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Bestellung', 'themisdb-order-request'); ?>: <?php echo esc_html($order['order_number']); ?></h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-orders&action=edit&order_id=' . absint($order_id))); ?>" class="page-title-action"><?php _e('Bearbeiten', 'themisdb-order-request'); ?></a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-orders')); ?>" class="page-title-action"><?php _e('Zur Bestellliste', 'themisdb-order-request'); ?></a>
            <?php if (!empty($contracts)) : ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-contracts')); ?>" class="page-title-action"><?php _e('Verträge', 'themisdb-order-request'); ?></a>
            <?php endif; ?>
            <hr class="wp-header-end">
            <?php $this->render_module_navigation_tabs('themisdb-orders'); ?>

            <?php if (isset($_GET['message']) && $_GET['message'] === 'fulfillment_updated'): ?>
            <div class="notice notice-success"><p><?php _e('Fulfillment-Status wurde aktualisiert.', 'themisdb-order-request'); ?></p></div>
            <?php endif; ?>
            <?php if (isset($_GET['message']) && $_GET['message'] === 'compliance_required'): ?>
            <div class="notice notice-error"><p><?php _e('Statuswechsel blockiert: Rechtliche Pflichtangaben und Rechnungsdaten sind unvollständig.', 'themisdb-order-request'); ?></p></div>
            <?php endif; ?>
            
            <?php $current_tab = $this->render_detail_page_tabs($tabs); ?>
            
            <div class="card">
                <?php $this->render_detail_tab_pane('customer', $current_tab === 'customer'); ?>
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
            <?php $this->close_detail_tab_pane(); ?>

            <?php $this->render_detail_tab_pane('billing', $current_tab === 'billing'); ?>
            <div class="card">
                <h2><?php _e('Rechnungsdaten', 'themisdb-order-request'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Rechnungsempfänger', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html(($order['billing_name'] ?? '') !== '' ? $order['billing_name'] : ($order['customer_name'] ?? '—')); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Adresse', 'themisdb-order-request'); ?>:</th>
                        <td>
                            <?php echo esc_html($order['billing_address_line1'] ?? '—'); ?><br>
                            <?php if (!empty($order['billing_address_line2'])): ?>
                                <?php echo esc_html($order['billing_address_line2']); ?><br>
                            <?php endif; ?>
                            <?php echo esc_html(trim(($order['billing_postal_code'] ?? '') . ' ' . ($order['billing_city'] ?? ''))); ?><br>
                            <?php echo esc_html($order['billing_country'] ?? 'DE'); ?>
                        </td>
                    </tr>
                </table>
            </div>
            <?php $this->close_detail_tab_pane(); ?>

            <?php $this->render_detail_tab_pane('legal', $current_tab === 'legal'); ?>
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
                        <td><?php echo esc_html($order['legal_acceptance_version'] ?? 'de-v1'); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Zugestimmt am', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo !empty($order['legal_accepted_at']) ? esc_html(date('d.m.Y H:i', strtotime($order['legal_accepted_at']))) : '—'; ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('IP', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html($order['legal_accepted_ip'] ?? '—'); ?></td>
                    </tr>
                </table>
            </div>
            <?php $this->close_detail_tab_pane(); ?>

            <?php $this->render_detail_tab_pane('items', $current_tab === 'items'); ?>
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
                                ThemisDB <?php echo esc_html($this->get_product_edition_label($order['product_edition'])); ?> Edition
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Produkttyp', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html($this->get_product_type_label($order['product_type'])); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Gesamtbetrag', 'themisdb-order-request'); ?>:</th>
                        <td><strong><?php echo number_format($order['total_amount'], 2, ',', '.'); ?> <?php echo esc_html($order['currency']); ?></strong></td>
                    </tr>
                    <tr>
                        <th><?php _e('Status', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html($this->get_order_status_label($order['status'])); ?></td>
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
            <?php $this->close_detail_tab_pane(); ?>

            <?php if ($order['product_type'] === 'merchandise'): ?>
            <?php $this->render_detail_tab_pane('shipping', $current_tab === 'shipping'); ?>
            <div class="card">
                <h2><?php _e('Versand und Fulfillment', 'themisdb-order-request'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Empfänger', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html(($order['shipping_name'] ?? '') !== '' ? $order['shipping_name'] : ($order['customer_name'] ?? '—')); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Lieferadresse', 'themisdb-order-request'); ?>:</th>
                        <td>
                            <?php echo esc_html(($order['shipping_address_line1'] ?? '') !== '' ? $order['shipping_address_line1'] : '—'); ?><br>
                            <?php if (!empty($order['shipping_address_line2'])): ?>
                                <?php echo esc_html($order['shipping_address_line2']); ?><br>
                            <?php endif; ?>
                            <?php echo esc_html(trim(($order['shipping_postal_code'] ?? '') . ' ' . ($order['shipping_city'] ?? ''))); ?><br>
                            <?php echo esc_html(($order['shipping_country'] ?? '') !== '' ? $order['shipping_country'] : 'DE'); ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Versandart', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html(($order['shipping_method'] ?? '') !== '' ? $order['shipping_method'] : 'standard'); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Fulfillment-Status', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html($this->get_fulfillment_status_label($order['fulfillment_status'] ?: 'not_required')); ?></td>
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
                                        <?php echo esc_html($this->get_fulfillment_status_label($status)); ?>
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
            <?php $this->close_detail_tab_pane(); ?>
            <?php endif; ?>
            
            <?php $this->render_detail_tab_pane('contracts', $current_tab === 'contracts'); ?>
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
                            <td><?php echo esc_html($this->get_contract_type_label($contract['contract_type'])); ?></td>
                            <td><?php echo esc_html($this->get_contract_status_label($contract['status'])); ?></td>
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
                <?php if ($order['status'] === 'confirmed' || $order['status'] === 'pending' || $order['status'] === 'draft'): ?>
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
            <?php $this->close_detail_tab_pane(); ?>
            
            <!-- Workflow Status Buttons -->
            <div class="card" style="background-color:#f9f9f9;">
                <h2><?php _e('Workflow-Aktionen', 'themisdb-order-request'); ?></h2>
                
                <?php 
                $allowed_transitions = array(
                    'draft' => array('confirmed', 'cancelled'),
                    'pending' => array('confirmed', 'cancelled'),
                    'confirmed' => array('signed', 'cancelled'),
                    'signed' => array('active', 'cancelled'),
                    'active' => array('ended', 'suspended'),
                    'suspended' => array('active', 'ended'),
                    'ended' => array(),
                    'cancelled' => array()
                );
                
                $current_transitions = isset($allowed_transitions[$order['status']]) ? $allowed_transitions[$order['status']] : array();
                ?>
                
                <p><?php _e('Aktueller Status:', 'themisdb-order-request'); ?> <strong><?php echo esc_html($this->get_order_status_label($order['status'])); ?></strong></p>
                
                <?php if (!empty($current_transitions)): ?>
                <div style="margin: 1rem 0;">
                    <?php foreach ($current_transitions as $transition): ?>
                    <form method="post" style="display: inline;">
                        <?php wp_nonce_field('themisdb_change_order_status'); ?>
                        <input type="hidden" name="action" value="change_status">
                        <input type="hidden" name="order_id" value="<?php echo absint($order_id); ?>">
                        <input type="hidden" name="new_status" value="<?php echo esc_attr($transition); ?>">
                        <button type="submit" class="button button-<?php echo ($transition === 'cancelled' || $transition === 'ended') ? 'secondary' : 'primary'; ?>" style="margin-right: 0.5rem;">
                            <?php echo esc_html($this->get_order_status_label($transition)); ?>
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
            <h1 class="wp-heading-inline"><?php _e('Neue Bestellung', 'themisdb-order-request'); ?></h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-orders')); ?>" class="page-title-action"><?php _e('Zur Bestellliste', 'themisdb-order-request'); ?></a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-order-dashboard')); ?>" class="page-title-action"><?php _e('Zum Dashboard', 'themisdb-order-request'); ?></a>
            <hr class="wp-header-end">
            <?php $this->render_module_navigation_tabs('themisdb-orders'); ?>
            
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
                            <th><label for="customer_type"><?php _e('Kundentyp *', 'themisdb-order-request'); ?>:</label></th>
                            <td>
                                <select id="customer_type" name="customer_type" required>
                                    <option value="consumer"><?php _e('Verbraucher', 'themisdb-order-request'); ?></option>
                                    <option value="business"><?php _e('Unternehmer / Firma', 'themisdb-order-request'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="customer_company"><?php _e('Unternehmen', 'themisdb-order-request'); ?>:</label></th>
                            <td><input type="text" id="customer_company" name="customer_company" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="vat_id"><?php _e('USt-IdNr.', 'themisdb-order-request'); ?>:</label></th>
                            <td>
                                <input type="text" id="vat_id" name="vat_id" class="regular-text" placeholder="DE123456789">
                                <span class="themisdb-field-hint"><?php _e('Pflicht für B2B. Format: Ländercode + Ziffern, z.&nbsp;B. DE123456789', 'themisdb-order-request'); ?></span>
                            </td>
                        </tr>
                    </table>

                    <h3 class="themisdb-form-section-header"><?php _e('Rechnungsdaten', 'themisdb-order-request'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th><label for="billing_name"><?php _e('Rechnungsempfänger *', 'themisdb-order-request'); ?>:</label></th>
                            <td><input type="text" id="billing_name" name="billing_name" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="billing_address_line1"><?php _e('Straße und Hausnummer *', 'themisdb-order-request'); ?>:</label></th>
                            <td><input type="text" id="billing_address_line1" name="billing_address_line1" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="billing_address_line2"><?php _e('Adresszusatz', 'themisdb-order-request'); ?>:</label></th>
                            <td><input type="text" id="billing_address_line2" name="billing_address_line2" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="billing_postal_code"><?php _e('PLZ *', 'themisdb-order-request'); ?>:</label></th>
                            <td><input type="text" id="billing_postal_code" name="billing_postal_code" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="billing_city"><?php _e('Ort *', 'themisdb-order-request'); ?>:</label></th>
                            <td><input type="text" id="billing_city" name="billing_city" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="billing_country"><?php _e('Land (ISO) *', 'themisdb-order-request'); ?>:</label></th>
                            <td>
                                <input type="text" id="billing_country" name="billing_country" class="small-text" maxlength="2" value="DE" placeholder="DE" required>
                                <span class="themisdb-field-hint"><?php _e('2-stelliger ISO-Code in Großbuchstaben, z.&nbsp;B. DE, AT, CH', 'themisdb-order-request'); ?></span>
                            </td>
                        </tr>
                    </table>

                    <h3 class="themisdb-form-section-header"><?php _e('Lieferdaten', 'themisdb-order-request'); ?> <small style="font-weight:normal;">(<?php _e('optional', 'themisdb-order-request'); ?>)</small></h3>
                    <table class="form-table">
                        <tr>
                            <th><label for="shipping_name"><?php _e('Empfänger', 'themisdb-order-request'); ?>:</label></th>
                            <td><input type="text" id="shipping_name" name="shipping_name" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="shipping_address_line1"><?php _e('Straße und Hausnummer', 'themisdb-order-request'); ?>:</label></th>
                            <td><input type="text" id="shipping_address_line1" name="shipping_address_line1" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="shipping_address_line2"><?php _e('Adresszusatz', 'themisdb-order-request'); ?>:</label></th>
                            <td><input type="text" id="shipping_address_line2" name="shipping_address_line2" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="shipping_postal_code"><?php _e('PLZ', 'themisdb-order-request'); ?>:</label></th>
                            <td><input type="text" id="shipping_postal_code" name="shipping_postal_code" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="shipping_city"><?php _e('Ort', 'themisdb-order-request'); ?>:</label></th>
                            <td><input type="text" id="shipping_city" name="shipping_city" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="shipping_country"><?php _e('Land (ISO)', 'themisdb-order-request'); ?>:</label></th>
                            <td>
                                <input type="text" id="shipping_country" name="shipping_country" class="small-text" maxlength="2" value="DE" placeholder="DE">
                                <span class="themisdb-field-hint"><?php _e('ISO-Code, 2 Großbuchstaben', 'themisdb-order-request'); ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="shipping_method"><?php _e('Versandart', 'themisdb-order-request'); ?>:</label></th>
                            <td>
                                <select id="shipping_method" name="shipping_method">
                                    <option value="standard"><?php _e('Standard', 'themisdb-order-request'); ?></option>
                                    <option value="express"><?php _e('Express', 'themisdb-order-request'); ?></option>
                                </select>
                            </td>
                        </tr>
                    </table>

                    <h3 class="themisdb-form-section-header"><?php _e('Rechtliche Zustimmung', 'themisdb-order-request'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th><?php _e('AGB akzeptiert *', 'themisdb-order-request'); ?>:</th>
                            <td><label><input type="checkbox" name="legal_terms_accepted" value="1" required> <?php _e('Bestätigt', 'themisdb-order-request'); ?></label></td>
                        </tr>
                        <tr>
                            <th><?php _e('Datenschutz akzeptiert *', 'themisdb-order-request'); ?>:</th>
                            <td><label><input type="checkbox" name="legal_privacy_accepted" value="1" required> <?php _e('Bestätigt', 'themisdb-order-request'); ?></label></td>
                        </tr>
                        <tr>
                            <th><?php _e('Widerrufsbelehrung bestätigt', 'themisdb-order-request'); ?>:</th>
                            <td><label><input type="checkbox" name="legal_withdrawal_acknowledged" value="1"> <?php _e('Bestätigt', 'themisdb-order-request'); ?></label></td>
                        </tr>
                        <tr>
                            <th><?php _e('Sofortausführung', 'themisdb-order-request'); ?>:</th>
                            <td><label><input type="checkbox" name="legal_withdrawal_waiver" value="1"> <?php _e('Beginn digitaler Leistung vor Ablauf der Widerrufsfrist gewünscht', 'themisdb-order-request'); ?></label></td>
                        </tr>
                    </table>
                    <input type="hidden" name="legal_acceptance_version" value="<?php echo esc_attr(get_option('themisdb_order_legal_version', 'de-v1')); ?>">
                    <input type="hidden" name="legal_accepted_at" value="<?php echo esc_attr(current_time('mysql')); ?>">
                    <input type="hidden" name="legal_accepted_ip" value="<?php echo esc_attr($_SERVER['REMOTE_ADDR'] ?? ''); ?>">
                    <input type="hidden" name="legal_accepted_user_agent" value="<?php echo esc_attr(wp_unslash($_SERVER['HTTP_USER_AGENT'] ?? '')); ?>">
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
        
        $tabs = array(
            'customer' => __('Kundendaten', 'themisdb-order-request'),
            'billing' => __('Rechnungsdaten', 'themisdb-order-request'),
            'shipping' => __('Lieferdaten', 'themisdb-order-request'),
            'legal' => __('Rechtlich', 'themisdb-order-request'),
            'items' => __('Bestelldetails', 'themisdb-order-request'),
        );
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Bestellung bearbeiten', 'themisdb-order-request'); ?>: <?php echo esc_html($order['order_number']); ?></h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-orders&action=view&order_id=' . absint($order_id))); ?>" class="page-title-action"><?php _e('Zur Detailansicht', 'themisdb-order-request'); ?></a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-orders')); ?>" class="page-title-action"><?php _e('Zur Bestellliste', 'themisdb-order-request'); ?></a>
            <hr class="wp-header-end">
            <?php $this->render_module_navigation_tabs('themisdb-orders'); ?>
            
            <?php $current_tab = $this->render_detail_page_tabs($tabs); ?>
            
            <form method="post" action="">
                <?php wp_nonce_field('themisdb_save_order'); ?>
                <input type="hidden" name="action" value="save_order">
                <input type="hidden" name="order_id" value="<?php echo absint($order_id); ?>">
                
                <div class="card">
                    <?php $this->render_detail_tab_pane('customer', $current_tab === 'customer'); ?>
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
                            <th><label for="customer_type"><?php _e('Kundentyp *', 'themisdb-order-request'); ?>:</label></th>
                            <td>
                                <select id="customer_type" name="customer_type" required>
                                    <option value="consumer" <?php selected($order['customer_type'] ?? 'consumer', 'consumer'); ?>><?php _e('Verbraucher', 'themisdb-order-request'); ?></option>
                                    <option value="business" <?php selected($order['customer_type'] ?? 'consumer', 'business'); ?>><?php _e('Unternehmer / Firma', 'themisdb-order-request'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="customer_company"><?php _e('Unternehmen', 'themisdb-order-request'); ?>:</label></th>
                            <td><input type="text" id="customer_company" name="customer_company" class="regular-text" value="<?php echo esc_attr($order['customer_company'] ?? ''); ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="vat_id"><?php _e('USt-IdNr.', 'themisdb-order-request'); ?>:</label></th>
                            <td>
                                <input type="text" id="vat_id" name="vat_id" class="regular-text" value="<?php echo esc_attr($order['vat_id'] ?? ''); ?>" placeholder="DE123456789">
                                <span class="themisdb-field-hint"><?php _e('Pflicht für B2B. Format: Ländercode + Ziffern, z.&nbsp;B. DE123456789', 'themisdb-order-request'); ?></span>
                            </td>
                        </tr>
                    </table>

                    <?php $this->close_detail_tab_pane(); ?>
                    <?php $this->render_detail_tab_pane('billing', $current_tab === 'billing'); ?>
                    <h3 class="themisdb-form-section-header"><?php _e('Rechnungsdaten', 'themisdb-order-request'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th><label for="billing_name"><?php _e('Rechnungsempfänger *', 'themisdb-order-request'); ?>:</label></th>
                            <td><input type="text" id="billing_name" name="billing_name" class="regular-text" value="<?php echo esc_attr($order['billing_name'] ?? $order['customer_name']); ?>" required></td>
                        </tr>
                        <tr>
                            <th><label for="billing_address_line1"><?php _e('Straße und Hausnummer *', 'themisdb-order-request'); ?>:</label></th>
                            <td><input type="text" id="billing_address_line1" name="billing_address_line1" class="regular-text" value="<?php echo esc_attr($order['billing_address_line1'] ?? ''); ?>" required></td>
                        </tr>
                        <tr>
                            <th><label for="billing_address_line2"><?php _e('Adresszusatz', 'themisdb-order-request'); ?>:</label></th>
                            <td><input type="text" id="billing_address_line2" name="billing_address_line2" class="regular-text" value="<?php echo esc_attr($order['billing_address_line2'] ?? ''); ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="billing_postal_code"><?php _e('PLZ *', 'themisdb-order-request'); ?>:</label></th>
                            <td><input type="text" id="billing_postal_code" name="billing_postal_code" class="regular-text" value="<?php echo esc_attr($order['billing_postal_code'] ?? ''); ?>" required></td>
                        </tr>
                        <tr>
                            <th><label for="billing_city"><?php _e('Ort *', 'themisdb-order-request'); ?>:</label></th>
                            <td><input type="text" id="billing_city" name="billing_city" class="regular-text" value="<?php echo esc_attr($order['billing_city'] ?? ''); ?>" required></td>
                        </tr>
                        <tr>
                            <th><label for="billing_country"><?php _e('Land (ISO) *', 'themisdb-order-request'); ?>:</label></th>
                            <td>
                                <input type="text" id="billing_country" name="billing_country" class="small-text" maxlength="2" value="<?php echo esc_attr($order['billing_country'] ?? 'DE'); ?>" placeholder="DE" required>
                                <span class="themisdb-field-hint"><?php _e('2-stelliger ISO-Code in Großbuchstaben, z.&nbsp;B. DE, AT, CH', 'themisdb-order-request'); ?></span>
                            </td>
                        </tr>
                    </table>

                    <?php $this->close_detail_tab_pane(); ?>
                    <?php $this->render_detail_tab_pane('shipping', $current_tab === 'shipping'); ?>
                    <h3 class="themisdb-form-section-header"><?php _e('Lieferdaten', 'themisdb-order-request'); ?> <small style="font-weight:normal;">(<?php _e('optional', 'themisdb-order-request'); ?>)</small></h3>
                    <table class="form-table">
                        <tr>
                            <th><label for="shipping_name"><?php _e('Empfänger', 'themisdb-order-request'); ?>:</label></th>
                            <td><input type="text" id="shipping_name" name="shipping_name" class="regular-text" value="<?php echo esc_attr($order['shipping_name'] ?? ''); ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="shipping_address_line1"><?php _e('Straße und Hausnummer', 'themisdb-order-request'); ?>:</label></th>
                            <td><input type="text" id="shipping_address_line1" name="shipping_address_line1" class="regular-text" value="<?php echo esc_attr($order['shipping_address_line1'] ?? ''); ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="shipping_address_line2"><?php _e('Adresszusatz', 'themisdb-order-request'); ?>:</label></th>
                            <td><input type="text" id="shipping_address_line2" name="shipping_address_line2" class="regular-text" value="<?php echo esc_attr($order['shipping_address_line2'] ?? ''); ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="shipping_postal_code"><?php _e('PLZ', 'themisdb-order-request'); ?>:</label></th>
                            <td><input type="text" id="shipping_postal_code" name="shipping_postal_code" class="regular-text" value="<?php echo esc_attr($order['shipping_postal_code'] ?? ''); ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="shipping_city"><?php _e('Ort', 'themisdb-order-request'); ?>:</label></th>
                            <td><input type="text" id="shipping_city" name="shipping_city" class="regular-text" value="<?php echo esc_attr($order['shipping_city'] ?? ''); ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="shipping_country"><?php _e('Land (ISO)', 'themisdb-order-request'); ?>:</label></th>
                            <td>
                                <input type="text" id="shipping_country" name="shipping_country" class="small-text" maxlength="2" value="<?php echo esc_attr($order['shipping_country'] ?? 'DE'); ?>" placeholder="DE">
                                <span class="themisdb-field-hint"><?php _e('ISO-Code, 2 Großbuchstaben', 'themisdb-order-request'); ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="shipping_method"><?php _e('Versandart', 'themisdb-order-request'); ?>:</label></th>
                            <td>
                                <select id="shipping_method" name="shipping_method">
                                    <option value="standard" <?php selected($order['shipping_method'] ?? 'standard', 'standard'); ?>><?php _e('Standard', 'themisdb-order-request'); ?></option>
                                    <option value="express" <?php selected($order['shipping_method'] ?? 'standard', 'express'); ?>><?php _e('Express', 'themisdb-order-request'); ?></option>
                                </select>
                            </td>
                        </tr>
                    </table>

                    <?php $this->close_detail_tab_pane(); ?>
                    <?php $this->render_detail_tab_pane('legal', $current_tab === 'legal'); ?>
                    <h3 class="themisdb-form-section-header"><?php _e('Rechtliche Zustimmung', 'themisdb-order-request'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th><?php _e('AGB akzeptiert *', 'themisdb-order-request'); ?>:</th>
                            <td><label><input type="checkbox" name="legal_terms_accepted" value="1" <?php checked(!empty($order['legal_terms_accepted'])); ?> required> <?php _e('Bestätigt', 'themisdb-order-request'); ?></label></td>
                        </tr>
                        <tr>
                            <th><?php _e('Datenschutz akzeptiert *', 'themisdb-order-request'); ?>:</th>
                            <td><label><input type="checkbox" name="legal_privacy_accepted" value="1" <?php checked(!empty($order['legal_privacy_accepted'])); ?> required> <?php _e('Bestätigt', 'themisdb-order-request'); ?></label></td>
                        </tr>
                        <tr>
                            <th><?php _e('Widerrufsbelehrung bestätigt', 'themisdb-order-request'); ?>:</th>
                            <td><label><input type="checkbox" name="legal_withdrawal_acknowledged" value="1" <?php checked(!empty($order['legal_withdrawal_acknowledged'])); ?>> <?php _e('Bestätigt', 'themisdb-order-request'); ?></label></td>
                        </tr>
                        <tr>
                            <th><?php _e('Sofortausführung', 'themisdb-order-request'); ?>:</th>
                            <td><label><input type="checkbox" name="legal_withdrawal_waiver" value="1" <?php checked(!empty($order['legal_withdrawal_waiver'])); ?>> <?php _e('Beginn digitaler Leistung vor Ablauf der Widerrufsfrist gewünscht', 'themisdb-order-request'); ?></label></td>
                        </tr>
                    </table>
                    <input type="hidden" name="legal_acceptance_version" value="<?php echo esc_attr($order['legal_acceptance_version'] ?? get_option('themisdb_order_legal_version', 'de-v1')); ?>">
                    <input type="hidden" name="legal_accepted_at" value="<?php echo esc_attr($order['legal_accepted_at'] ?? current_time('mysql')); ?>">
                    <input type="hidden" name="legal_accepted_ip" value="<?php echo esc_attr($order['legal_accepted_ip'] ?? ($_SERVER['REMOTE_ADDR'] ?? '')); ?>">
                    <input type="hidden" name="legal_accepted_user_agent" value="<?php echo esc_attr($order['legal_accepted_user_agent'] ?? wp_unslash($_SERVER['HTTP_USER_AGENT'] ?? '')); ?>">
                </div>
                
                <?php $this->close_detail_tab_pane(); ?>
                <?php $this->render_detail_tab_pane('items', $current_tab === 'items'); ?>
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
                
                <?php $this->close_detail_tab_pane(); ?>
                
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
            $this->abort_with_log(
                __('Erforderliche Felder fehlen', 'themisdb-order-request'),
                'Admin save order failed due to missing required fields',
                array(
                    'order_id' => intval($order_id),
                    'has_customer_name' => isset($_POST['customer_name']),
                    'has_customer_email' => isset($_POST['customer_email']),
                    'has_product_edition' => isset($_POST['product_edition']),
                )
            );
        }

        $customer_type = isset($_POST['customer_type']) && in_array(sanitize_text_field($_POST['customer_type']), array('consumer', 'business'), true)
            ? sanitize_text_field($_POST['customer_type'])
            : 'consumer';

        $billing_country = strtoupper(sanitize_text_field($_POST['billing_country'] ?? 'DE'));
        if (strlen($billing_country) !== 2) {
            $billing_country = 'DE';
        }

        $shipping_country = strtoupper(sanitize_text_field($_POST['shipping_country'] ?? 'DE'));
        if (strlen($shipping_country) !== 2) {
            $shipping_country = 'DE';
        }

        $legal_terms_accepted = !empty($_POST['legal_terms_accepted']) ? 1 : 0;
        $legal_privacy_accepted = !empty($_POST['legal_privacy_accepted']) ? 1 : 0;
        $legal_withdrawal_acknowledged = !empty($_POST['legal_withdrawal_acknowledged']) ? 1 : 0;
        $legal_withdrawal_waiver = !empty($_POST['legal_withdrawal_waiver']) ? 1 : 0;
        $legal_acceptance_version = sanitize_text_field($_POST['legal_acceptance_version'] ?? get_option('themisdb_order_legal_version', 'de-v1'));
        $legal_accepted_at = sanitize_text_field($_POST['legal_accepted_at'] ?? '');
        $legal_accepted_ip = sanitize_text_field($_POST['legal_accepted_ip'] ?? (isset($_SERVER['REMOTE_ADDR']) ? wp_unslash($_SERVER['REMOTE_ADDR']) : ''));
        $legal_accepted_user_agent = sanitize_text_field($_POST['legal_accepted_user_agent'] ?? (isset($_SERVER['HTTP_USER_AGENT']) ? wp_unslash($_SERVER['HTTP_USER_AGENT']) : ''));

        $data = array(
            'customer_name' => sanitize_text_field($_POST['customer_name']),
            'customer_email' => sanitize_email($_POST['customer_email']),
            'customer_company' => isset($_POST['customer_company']) ? sanitize_text_field($_POST['customer_company']) : '',
            'customer_type' => $customer_type,
            'vat_id' => isset($_POST['vat_id']) ? sanitize_text_field($_POST['vat_id']) : '',
            'billing_name' => isset($_POST['billing_name']) ? sanitize_text_field($_POST['billing_name']) : '',
            'billing_address_line1' => isset($_POST['billing_address_line1']) ? sanitize_text_field($_POST['billing_address_line1']) : '',
            'billing_address_line2' => isset($_POST['billing_address_line2']) ? sanitize_text_field($_POST['billing_address_line2']) : '',
            'billing_postal_code' => isset($_POST['billing_postal_code']) ? sanitize_text_field($_POST['billing_postal_code']) : '',
            'billing_city' => isset($_POST['billing_city']) ? sanitize_text_field($_POST['billing_city']) : '',
            'billing_country' => $billing_country,
            'shipping_name' => isset($_POST['shipping_name']) ? sanitize_text_field($_POST['shipping_name']) : '',
            'shipping_address_line1' => isset($_POST['shipping_address_line1']) ? sanitize_text_field($_POST['shipping_address_line1']) : '',
            'shipping_address_line2' => isset($_POST['shipping_address_line2']) ? sanitize_text_field($_POST['shipping_address_line2']) : '',
            'shipping_postal_code' => isset($_POST['shipping_postal_code']) ? sanitize_text_field($_POST['shipping_postal_code']) : '',
            'shipping_city' => isset($_POST['shipping_city']) ? sanitize_text_field($_POST['shipping_city']) : '',
            'shipping_country' => $shipping_country,
            'shipping_method' => isset($_POST['shipping_method']) ? sanitize_text_field($_POST['shipping_method']) : 'standard',
            'product_edition' => sanitize_text_field($_POST['product_edition']),
            'product_type' => 'database',
            'currency' => isset($_POST['currency']) ? sanitize_text_field($_POST['currency']) : 'EUR',
            'modules' => isset($_POST['modules']) ? array_map('sanitize_text_field', $_POST['modules']) : array(),
            'training_modules' => isset($_POST['training_modules']) ? array_map('sanitize_text_field', $_POST['training_modules']) : array(),
            'legal_terms_accepted' => $legal_terms_accepted,
            'legal_privacy_accepted' => $legal_privacy_accepted,
            'legal_withdrawal_acknowledged' => $legal_withdrawal_acknowledged,
            'legal_withdrawal_waiver' => $legal_withdrawal_waiver,
            'legal_acceptance_version' => $legal_acceptance_version,
            'legal_accepted_at' => $legal_accepted_at,
            'legal_accepted_ip' => $legal_accepted_ip,
            'legal_accepted_user_agent' => $legal_accepted_user_agent,
        );
        
        if (empty($data['billing_name']) || empty($data['billing_address_line1']) || empty($data['billing_postal_code']) || empty($data['billing_city'])) {
            $this->abort_with_log(
                __('Bitte vollständige Rechnungsdaten angeben.', 'themisdb-order-request'),
                'Admin save order failed due to incomplete billing data',
                array('order_id' => intval($order_id))
            );
        }

        if ($customer_type === 'business' && empty($data['customer_company'])) {
            $this->abort_with_log(
                __('Für Unternehmenskunden ist ein Firmenname erforderlich.', 'themisdb-order-request'),
                'Admin save order failed due to missing business company name',
                array('order_id' => intval($order_id))
            );
        }

        $regulatory_errors = $this->validate_order_regulatory_fields($data);
        if (!empty($regulatory_errors)) {
            $this->abort_with_log(
                implode(' ', $regulatory_errors),
                'Admin save order failed due to regulatory validation errors',
                array(
                    'order_id' => intval($order_id),
                    'error_count' => count($regulatory_errors),
                ),
                'warning'
            );
        }

        if (!$this->is_order_compliance_complete($data)) {
            $this->abort_with_log(
                __('Bitte rechtliche Zustimmungen vollständig erfassen (AGB, Datenschutz und Widerrufsbelehrung für Verbraucher).', 'themisdb-order-request'),
                'Admin save order failed due to incomplete legal compliance flags',
                array('order_id' => intval($order_id)),
                'warning'
            );
        }

        if ($data['legal_accepted_at'] === '' && $data['legal_terms_accepted'] && $data['legal_privacy_accepted']) {
            $data['legal_accepted_at'] = current_time('mysql');
        }
        
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
                            $this->log_admin_event('error', 'Admin invoice email send failed after order creation', array(
                                'order_id' => intval($result),
                                'exception_message' => $e->getMessage(),
                            ));
                        }
                    }
        } else {
            // Update existing order
            $result = ThemisDB_Order_Manager::update_order($order_id, $data);
            $message = __('Bestellung erfolgreich aktualisiert.', 'themisdb-order-request');
        }
        
        if ($result) {
            wp_redirect(admin_url('admin.php?page=themisdb-orders&action=view&order_id=' . $order_id . '&message=saved'));
            exit;
        } else {
            $this->abort_with_log(
                __('Fehler beim Speichern der Bestellung', 'themisdb-order-request'),
                'Admin save order failed in order manager',
                array('order_id' => intval($order_id))
            );
        }
    }

    /**
     * Validate legal compliance completeness for one order payload.
     */
    private function is_order_compliance_complete($order) {
        $customer_type = isset($order['customer_type']) ? sanitize_text_field($order['customer_type']) : 'consumer';
        $terms = !empty($order['legal_terms_accepted']);
        $privacy = !empty($order['legal_privacy_accepted']);
        $withdrawal = !empty($order['legal_withdrawal_acknowledged']);

        if (!$terms || !$privacy) {
            return false;
        }

        if ($customer_type === 'consumer' && !$withdrawal) {
            return false;
        }

        return true;
    }

    /**
     * Validate German billing/buyer regulatory fields.
     */
    private function validate_order_regulatory_fields($order) {
        $errors = array();
        $billing_country = strtoupper(sanitize_text_field($order['billing_country'] ?? 'DE'));
        $shipping_country = strtoupper(sanitize_text_field($order['shipping_country'] ?? 'DE'));
        $billing_postal_code = trim((string) ($order['billing_postal_code'] ?? ''));
        $shipping_postal_code = trim((string) ($order['shipping_postal_code'] ?? ''));
        $customer_type = sanitize_text_field($order['customer_type'] ?? 'consumer');
        $vat_id = strtoupper(trim((string) ($order['vat_id'] ?? '')));

        if (!preg_match('/^[A-Z]{2}$/', $billing_country)) {
            $errors[] = __('Rechnungsland muss als 2-stelliger ISO-Code angegeben werden (z. B. DE).', 'themisdb-order-request');
        }

        if ($billing_country === 'DE' && !preg_match('/^\d{5}$/', $billing_postal_code)) {
            $errors[] = __('Für Deutschland muss die Rechnungs-PLZ genau 5 Ziffern haben.', 'themisdb-order-request');
        }

        if (!preg_match('/^[A-Z]{2}$/', $shipping_country)) {
            $errors[] = __('Lieferland muss als 2-stelliger ISO-Code angegeben werden (z. B. DE).', 'themisdb-order-request');
        }

        if ($shipping_postal_code !== '' && $shipping_country === 'DE' && !preg_match('/^\d{5}$/', $shipping_postal_code)) {
            $errors[] = __('Für Deutschland muss die Liefer-PLZ genau 5 Ziffern haben.', 'themisdb-order-request');
        }

        if ($customer_type === 'business' && trim((string) ($order['customer_company'] ?? '')) === '') {
            $errors[] = __('Für Unternehmenskunden ist der Firmenname verpflichtend.', 'themisdb-order-request');
        }

        if ($vat_id !== '' && !preg_match('/^[A-Z]{2}[A-Z0-9]{2,12}$/', $vat_id)) {
            $errors[] = __('Die USt-IdNr. hat ein ungültiges Format (z. B. DE123456789).', 'themisdb-order-request');
        }

        return $errors;
    }
    
    /**
     * Handle delete order (POST)
     */
    private function handle_delete_order($order_id) {
        if (ThemisDB_Order_Manager::delete_order($order_id)) {
            wp_redirect(admin_url('admin.php?page=themisdb-orders&message=deleted'));
                exit;
            } else {
            $this->abort_with_log(
                __('Fehler beim Löschen der Bestellung', 'themisdb-order-request'),
                'Admin delete order failed in order manager',
                array('order_id' => intval($order_id))
            );
        }
    }
    
    /**
     * Handle change order status (POST)
     */
    private function handle_change_order_status($order_id, $new_status) {
        $order = ThemisDB_Order_Manager::get_order($order_id);

        if ($order && in_array($new_status, array('confirmed', 'signed', 'active'), true)) {
            if (!$this->is_order_compliance_complete($order) || !empty($this->validate_order_regulatory_fields($order))) {
                wp_redirect(admin_url('admin.php?page=themisdb-orders&action=view&order_id=' . $order_id . '&message=compliance_required'));
                exit;
            }
        }

        $result = ThemisDB_Order_Manager::set_order_status($order_id, $new_status);

        if (!$result) {
            error_log('ThemisDB Admin Status Change Error: Failed to set order status for order ID ' . $order_id . ' to ' . $new_status);
            $this->log_admin_event('error', 'Admin order status change failed', array(
                'order_id' => intval($order_id),
                'new_status' => sanitize_text_field($new_status),
            ));
        }

        if ($result && in_array($new_status, array('confirmed', 'signed'), true)) {
            try {
                ThemisDB_Email_Handler::send_invoice_email($order_id);
            } catch (Exception $e) {
                error_log('ThemisDB Invoice Email Error: ' . $e->getMessage());
                $this->log_admin_event('error', 'Admin invoice email send failed after status change', array(
                    'order_id' => intval($order_id),
                    'new_status' => sanitize_text_field($new_status),
                    'exception_message' => $e->getMessage(),
                ));
            }
        }

        if ($result && $order && $order['product_type'] === 'merchandise' && $new_status === 'cancelled') {
            ThemisDB_Order_Manager::release_inventory_for_order($order_id);
        }
        
        if ($result) {
            wp_redirect(admin_url('admin.php?page=themisdb-orders&action=view&order_id=' . $order_id . '&message=status_changed'));
            exit;
        } else {
            $this->abort_with_log(
                __('Fehler beim Ändern des Status', 'themisdb-order-request'),
                'Admin change order status failed in order manager',
                array(
                    'order_id' => intval($order_id),
                    'new_status' => sanitize_text_field($new_status),
                )
            );
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

        $this->abort_with_log(
            __('Fehler beim Aktualisieren des Fulfillment-Status', 'themisdb-order-request'),
            'Admin fulfillment update failed in order manager',
            array(
                'order_id' => intval($order_id),
                'status' => sanitize_text_field($status),
            )
        );
    }
    
    /**
     * Handle create contract from order (POST)
     */
    private function handle_create_contract($order_id) {
        $order = ThemisDB_Order_Manager::get_order($order_id);
        
        if (!$order) {
            $this->abort_with_log(
                __('Bestellung nicht gefunden', 'themisdb-order-request'),
                'Admin create contract aborted because order was not found',
                array('order_id' => intval($order_id)),
                'warning'
            );
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
                'customer_type' => $order['customer_type'] ?? 'consumer',
                'vat_id' => $order['vat_id'] ?? '',
                'billing_name' => $order['billing_name'] ?? '',
                'billing_address_line1' => $order['billing_address_line1'] ?? '',
                'billing_address_line2' => $order['billing_address_line2'] ?? '',
                'billing_postal_code' => $order['billing_postal_code'] ?? '',
                'billing_city' => $order['billing_city'] ?? '',
                'billing_country' => $order['billing_country'] ?? 'DE',
                'legal_terms_accepted' => !empty($order['legal_terms_accepted']) ? 1 : 0,
                'legal_privacy_accepted' => !empty($order['legal_privacy_accepted']) ? 1 : 0,
                'legal_withdrawal_acknowledged' => !empty($order['legal_withdrawal_acknowledged']) ? 1 : 0,
                'legal_withdrawal_waiver' => !empty($order['legal_withdrawal_waiver']) ? 1 : 0,
                'legal_acceptance_version' => $order['legal_acceptance_version'] ?? 'de-v1',
                'legal_accepted_at' => $order['legal_accepted_at'] ?? null,
                'legal_accepted_ip' => $order['legal_accepted_ip'] ?? null,
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
            $this->abort_with_log(
                __('Fehler beim Erstellen des Vertrags', 'themisdb-order-request'),
                'Admin create contract failed in contract manager',
                array('order_id' => intval($order_id))
            );
        }
        
        // Generate PDF
        $pdf_result = ThemisDB_PDF_Generator::generate_contract_pdf($contract_id);
        
        // Send contract email to customer
        try {
            ThemisDB_Email_Handler::send_contract_email($contract_id);
        } catch (Exception $e) {
            // Log error but don't fail the contract creation
            error_log('ThemisDB Contract Email Error: ' . $e->getMessage());
            $this->log_admin_event('error', 'Admin contract email send failed after contract creation', array(
                'contract_id' => intval($contract_id),
                'order_id' => intval($order_id),
                'exception_message' => $e->getMessage(),
            ));
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

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $post_action = sanitize_text_field($_POST['action']);
            if ($post_action === 'bulk_contract_workflow') {
                check_admin_referer('themisdb_bulk_contract_workflow');
                $this->handle_bulk_contract_workflow();
                return;
            }
        }

        if ($action === 'view' && $contract_id) {
            $this->view_contract($contract_id);
        } else {
            $this->list_contracts();
        }
    }

    /**
     * Handle bulk contract workflow actions (status change).
     */
    private function handle_bulk_contract_workflow() {
        $contract_ids = isset($_POST['contract_ids']) ? array_map('absint', (array) $_POST['contract_ids']) : array();
        $workflow = sanitize_text_field($_POST['contract_workflow'] ?? '');

        if (empty($contract_ids) || $workflow === '') {
            $this->redirect_with_bulk_result('themisdb-contracts', 'bulk_contracts', __('Keine Verträge oder kein Workflow ausgewählt.', 'themisdb-order-request'), 0, 0);
        }

        $processed = 0;
        $failed = 0;

        foreach (array_unique($contract_ids) as $contract_id) {
            if (in_array($workflow, array('active', 'ended', 'cancelled'), true)) {
                global $wpdb;
                $result = $wpdb->update(
                    $wpdb->prefix . 'themisdb_contracts',
                    array('status' => $workflow),
                    array('id' => $contract_id),
                    array('%s'),
                    array('%d')
                );
                if ($result !== false) {
                    $processed++;
                } else {
                    $failed++;
                }
            } else {
                $failed++;
            }
        }

        $this->redirect_with_bulk_result('themisdb-contracts', 'bulk_contracts', __('Vertrags-Workflow abgeschlossen.', 'themisdb-order-request'), $processed, $failed);
    }

    /**
     * List contracts
     */
    private function list_contracts() {
        $prefs        = $this->get_list_preferences('contracts');
            $status_tab   = isset($_GET['status_tab']) ? sanitize_text_field($_GET['status_tab']) : ($prefs['status_tab'] ?? 'all');
            $search_query = sanitize_text_field($_GET['q'] ?? '');
            $sort_by      = sanitize_key($_GET['sort_by'] ?? ($prefs['sort_by'] ?? 'date'));
            $sort_dir     = sanitize_key($_GET['sort_dir'] ?? ($prefs['sort_dir'] ?? 'desc'));
            $per_page     = $this->resolve_per_page($_GET['per_page'] ?? ($prefs['per_page'] ?? 25), 25);
            $paged        = max(1, absint($_GET['paged'] ?? 1));

            $all_contracts = ThemisDB_Contract_Manager::get_all_contracts();

            $status_counts = array('draft' => 0, 'active' => 0, 'ended' => 0, 'cancelled' => 0);
            foreach ($all_contracts as $c) {
                $s = $c['status'] ?? '';
                if (isset($status_counts[$s])) {
                    $status_counts[$s]++;
                }
            }

            $contracts = $all_contracts;
            if ($status_tab !== 'all') {
                $contracts = array_values(array_filter($contracts, function ($c) use ($status_tab) {
                    return ($c['status'] ?? '') === $status_tab;
                }));
            }

            if ($search_query !== '') {
                $contracts = $this->filter_rows_by_search($contracts, $search_query, array('contract_number', 'contract_type', 'status'));
            }

            $sort_map = array(
                'number' => 'contract_number',
                'type'   => 'contract_type',
                'status' => 'status',
                'from'   => 'valid_from',
                'until'  => 'valid_until',
                'date'   => 'created_at',
            );
            $sort_by  = array_key_exists($sort_by, $sort_map) ? $sort_by : 'date';
            $sort_dir = in_array($sort_dir, array('asc', 'desc'), true) ? $sort_dir : 'desc';

            $this->save_list_preferences('contracts', array(
                'status_tab' => $status_tab,
                'sort_by'    => $sort_by,
                'sort_dir'   => $sort_dir,
                'per_page'   => $per_page,
            ));

            $contracts  = $this->sort_rows($contracts, $sort_map[$sort_by], $sort_dir, array(), array('valid_from', 'valid_until', 'created_at'));
            $pagination = $this->paginate_rows($contracts, $per_page, $paged);
            $contracts  = $pagination['items'];

            $tab_base       = admin_url('admin.php?page=themisdb-contracts');
            $contract_tabs  = array(
                'all'       => __('Alle', 'themisdb-order-request') . ' (' . count($all_contracts) . ')',
                'draft'     => __('Entwurf', 'themisdb-order-request') . ' (' . $status_counts['draft'] . ')',
                'active'    => __('Aktiv', 'themisdb-order-request') . ' (' . $status_counts['active'] . ')',
                'ended'     => __('Beendet', 'themisdb-order-request') . ' (' . $status_counts['ended'] . ')',
                'cancelled' => __('Storniert', 'themisdb-order-request') . ' (' . $status_counts['cancelled'] . ')',
            );
    
            $bulk_actions = array(
                'active'    => __('Aktivieren', 'themisdb-order-request'),
                'ended'     => __('Beenden', 'themisdb-order-request'),
                'cancelled' => __('Stornieren', 'themisdb-order-request'),
            );

            ?>
            <div class="wrap">
                <h1 class="wp-heading-inline"><?php _e('Verträge', 'themisdb-order-request'); ?></h1>
                <a class="page-title-action" href="<?php echo esc_url(admin_url('admin.php?page=themisdb-orders')); ?>"><?php _e('Zu Bestellungen', 'themisdb-order-request'); ?></a>
                <a class="page-title-action" href="<?php echo esc_url(admin_url('admin.php?page=themisdb-licenses')); ?>"><?php _e('Zu Lizenzen', 'themisdb-order-request'); ?></a>
                <a class="page-title-action" href="<?php echo esc_url(admin_url('admin.php?page=themisdb-order-dashboard')); ?>"><?php _e('Zum Dashboard', 'themisdb-order-request'); ?></a>
                <hr class="wp-header-end">
                <?php $this->render_module_navigation_tabs('themisdb-contracts'); ?>

                <?php $this->render_bulk_notice('bulk_contracts'); ?>

                <?php $this->render_filter_button_bar('status_tab', $contract_tabs, $status_tab, $tab_base, array(
                    'q'        => $search_query,
                    'sort_by'  => $sort_by,
                    'sort_dir' => $sort_dir,
                    'per_page' => $per_page,
                )); ?>

                <form method="get" class="themisdb-ajax-form" style="margin:8px 0 12px; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                    <input type="hidden" name="page" value="themisdb-contracts">
                    <input type="hidden" name="status_tab" value="<?php echo esc_attr($status_tab); ?>">
                    <input type="hidden" name="sort_by" value="<?php echo esc_attr($sort_by); ?>">
                    <input type="hidden" name="sort_dir" value="<?php echo esc_attr($sort_dir); ?>">
                    <input type="hidden" name="paged" value="1">
                    <input type="search" name="q" value="<?php echo esc_attr($search_query); ?>" placeholder="<?php esc_attr_e('Suche nach Vertragsnummer, Typ …', 'themisdb-order-request'); ?>" class="regular-text">
                    <button type="submit" class="button"><?php _e('Suchen', 'themisdb-order-request'); ?></button>
                    <?php $this->render_per_page_select('per_page', $per_page); ?>
                </form>

                <?php $this->render_inline_help_box(
                    __('Inline-Hilfe: Verträge', 'themisdb-order-request'),
                    array(
                        __('Status-Tabs filtern die Vertragsliste, die Suche verfeinert zusätzlich.', 'themisdb-order-request'),
                        __('Sortierung und Seitenlänge bleiben beim Wechsel erhalten.', 'themisdb-order-request'),
                        __('Bulk-Workflows gelten nur für markierte Verträge.', 'themisdb-order-request'),
                    )
                ); ?>

                <div class="themisdb-admin-modules" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;margin:20px 0;">
                    <div class="card" style="max-width:none;">
                        <h2><?php _e('Vertragsstatus', 'themisdb-order-request'); ?></h2>
                        <table class="widefat striped" style="border:none;box-shadow:none;">
                            <tbody>
                                <tr>
                                    <td><?php _e('Gesamt', 'themisdb-order-request'); ?></td>
                                    <td><strong><?php echo esc_html(number_format_i18n(count($all_contracts))); ?></strong></td>
                                </tr>
                                <tr>
                                    <td><?php _e('Entwurf', 'themisdb-order-request'); ?></td>
                                    <td><?php echo esc_html(number_format_i18n((int) $status_counts['draft'])); ?></td>
                                </tr>
                                <tr>
                                    <td><?php _e('Aktiv', 'themisdb-order-request'); ?></td>
                                    <td><?php echo esc_html(number_format_i18n((int) $status_counts['active'])); ?></td>
                                </tr>
                                <tr>
                                    <td><?php _e('Beendet', 'themisdb-order-request'); ?></td>
                                    <td><?php echo esc_html(number_format_i18n((int) $status_counts['ended'])); ?></td>
                                </tr>
                                <tr>
                                    <td><?php _e('Storniert', 'themisdb-order-request'); ?></td>
                                    <td><?php echo esc_html(number_format_i18n((int) $status_counts['cancelled'])); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="card" style="max-width:none;">
                        <h2><?php _e('Schnellaktionen', 'themisdb-order-request'); ?></h2>
                        <p><?php _e('Vertragsstatus prufen, aktive Vertrage filtern oder in Bestellungen und Lizenzen springen.', 'themisdb-order-request'); ?></p>
                        <p>
                            <a class="button button-primary" href="<?php echo esc_url(add_query_arg(array('page' => 'themisdb-contracts', 'status_tab' => 'active'), admin_url('admin.php'))); ?>"><?php _e('Aktive Verträge', 'themisdb-order-request'); ?></a>
                            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=themisdb-orders')); ?>"><?php _e('Bestellungen', 'themisdb-order-request'); ?></a>
                            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=themisdb-licenses')); ?>"><?php _e('Lizenzen', 'themisdb-order-request'); ?></a>
                        </p>
                    </div>
                </div>

                <form method="post" class="themisdb-list-root" id="themisdb-contracts-list-root">
                    <?php wp_nonce_field('themisdb_bulk_contract_workflow'); ?>
                    <input type="hidden" name="action" value="bulk_contract_workflow">
                    <?php $this->render_bulk_action_bar('contract_workflow', $bulk_actions, __('Ausgewählte Verträge verarbeiten', 'themisdb-order-request')); ?>

                    <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td class="check-column"><input type="checkbox" class="themisdb-bulk-toggle" aria-label="<?php esc_attr_e('Alle Verträge auswählen', 'themisdb-order-request'); ?>"></td>
                            <th><?php $this->render_sortable_column(__('Vertragsnummer', 'themisdb-order-request'), 'number', $sort_by, $sort_dir, $tab_base, array('status_tab' => $status_tab, 'q' => $search_query, 'per_page' => $per_page)); ?></th>
                            <th><?php $this->render_sortable_column(__('Typ', 'themisdb-order-request'), 'type', $sort_by, $sort_dir, $tab_base, array('status_tab' => $status_tab, 'q' => $search_query, 'per_page' => $per_page)); ?></th>
                            <th><?php $this->render_sortable_column(__('Status', 'themisdb-order-request'), 'status', $sort_by, $sort_dir, $tab_base, array('status_tab' => $status_tab, 'q' => $search_query, 'per_page' => $per_page)); ?></th>
                            <th><?php $this->render_sortable_column(__('Gültig von', 'themisdb-order-request'), 'from', $sort_by, $sort_dir, $tab_base, array('status_tab' => $status_tab, 'q' => $search_query, 'per_page' => $per_page)); ?></th>
                            <th><?php $this->render_sortable_column(__('Gültig bis', 'themisdb-order-request'), 'until', $sort_by, $sort_dir, $tab_base, array('status_tab' => $status_tab, 'q' => $search_query, 'per_page' => $per_page)); ?></th>
                            <th><?php $this->render_sortable_column(__('Erstellt', 'themisdb-order-request'), 'date', $sort_by, $sort_dir, $tab_base, array('status_tab' => $status_tab, 'q' => $search_query, 'per_page' => $per_page)); ?></th>
                            <th><?php _e('Aktionen', 'themisdb-order-request'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($contracts)): ?>
                        <tr>
                            <td colspan="8"><?php _e('Keine Verträge gefunden', 'themisdb-order-request'); ?></td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($contracts as $contract): ?>
                            <tr>
                                <th scope="row" class="check-column"><input type="checkbox" name="contract_ids[]" value="<?php echo absint($contract['id']); ?>"></th>
                                <td><strong><?php echo esc_html($contract['contract_number']); ?></strong></td>
                                <td><?php echo esc_html($this->get_contract_type_label($contract['contract_type'])); ?></td>
                                <td>
                                    <span class="contract-status status-<?php echo esc_attr($contract['status']); ?>">
                                        <?php echo esc_html($this->get_contract_status_label($contract['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html(wp_date('d.m.Y', strtotime($contract['valid_from']))); ?></td>
                                <td><?php echo $contract['valid_until'] ? esc_html(wp_date('d.m.Y', strtotime($contract['valid_until']))) : '∞'; ?></td>
                                <td><?php echo esc_html(wp_date('d.m.Y', strtotime($contract['created_at']))); ?></td>
                                <td>
                                    <a href="?page=themisdb-contracts&action=view&contract_id=<?php echo absint($contract['id']); ?>" class="button button-small"><?php _e('Ansehen', 'themisdb-order-request'); ?></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    </table>
                </form>

                <?php $this->render_simple_pagination(
                    $tab_base,
                    $pagination['paged'],
                    $pagination['total_pages'],
                    array(
                        'status_tab' => $status_tab,
                        'q'          => $search_query,
                        'sort_by'    => $sort_by,
                        'sort_dir'   => $sort_dir,
                        'per_page'   => $per_page,
                    )
                ); ?>

                <?php $this->render_bulk_table_script(); ?>
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
        
        $tabs = array(
            'details' => __('Vertragsdetails', 'themisdb-order-request'),
            'order' => __('Bestellung', 'themisdb-order-request'),
        );
        
        if (!empty($revisions)) {
            $tabs['revisions'] = __('Revisionen', 'themisdb-order-request');
        }
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Vertrag', 'themisdb-order-request'); ?>: <?php echo esc_html($contract['contract_number']); ?></h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-contracts')); ?>" class="page-title-action"><?php _e('Zur Vertragsliste', 'themisdb-order-request'); ?></a>
            <?php if ($order): ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-orders&action=view&order_id=' . absint($order['id']))); ?>" class="page-title-action"><?php _e('Bestellung ansehen', 'themisdb-order-request'); ?></a>
            <?php endif; ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-order-dashboard')); ?>" class="page-title-action"><?php _e('Zum Dashboard', 'themisdb-order-request'); ?></a>
            <hr class="wp-header-end">
            <?php $this->render_module_navigation_tabs('themisdb-contracts'); ?>
            
            <?php $current_tab = $this->render_detail_page_tabs($tabs); ?>
            
            <div class="card">
                <?php $this->render_detail_tab_pane('details', $current_tab === 'details'); ?>
                <h2><?php _e('Vertragsdetails', 'themisdb-order-request'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Vertragsnummer', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html($contract['contract_number']); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Typ', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html($this->get_contract_type_label($contract['contract_type'])); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Status', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html($this->get_contract_status_label($contract['status'])); ?></td>
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
            <?php $this->close_detail_tab_pane(); ?>
            
            <?php if ($order): ?>
            <?php $this->render_detail_tab_pane('order', $current_tab === 'order'); ?>
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
            <?php $this->close_detail_tab_pane(); ?>
            <?php endif; ?>
            
            <?php if (!empty($revisions)): ?>
            <?php $this->render_detail_tab_pane('revisions', $current_tab === 'revisions'); ?>
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
            <?php $this->close_detail_tab_pane(); ?>
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
        $prefs = $this->get_list_preferences('products');
        $entity = isset($_GET['entity']) ? sanitize_text_field($_GET['entity']) : 'product';
        $entity_tab = isset($_GET['entity_tab']) ? sanitize_text_field($_GET['entity_tab']) : ($prefs['entity_tab'] ?? $entity);
        if (!in_array($entity_tab, array('product', 'module', 'training'), true)) {
            $entity_tab = 'product';
        }

        $entity = $entity_tab;
        $edit_id = isset($_GET['edit_id']) ? absint($_GET['edit_id']) : 0;
        $category_tab = isset($_GET['category_tab']) ? sanitize_text_field($_GET['category_tab']) : ($prefs['category_tab'] ?? 'all');
        $search_query = sanitize_text_field($_GET['q'] ?? '');
        $sort_by = sanitize_key($_GET['sort_by'] ?? ($prefs['sort_by'] ?? 'code'));
        $sort_dir = sanitize_key($_GET['sort_dir'] ?? ($prefs['sort_dir'] ?? 'asc'));
        $per_page = $this->resolve_per_page($_GET['per_page'] ?? ($prefs['per_page'] ?? 25), 25);
        $paged = max(1, absint($_GET['paged'] ?? 1));
        $manage_tab = isset($_GET['manage_tab']) ? sanitize_text_field($_GET['manage_tab']) : 'categories';
        if (!in_array($manage_tab, array('categories', 'items'), true)) {
            $manage_tab = 'categories';
        }
        $manage_category_search = sanitize_text_field($_GET['mq'] ?? '');
        $manage_category_filter = sanitize_key($_GET['mfilter'] ?? 'all');
        if (!in_array($manage_category_filter, array('all', 'used', 'unused'), true)) {
            $manage_category_filter = 'all';
        }
        $manage_category_sort_by = sanitize_key($_GET['msort_by'] ?? 'name');
        if (!in_array($manage_category_sort_by, array('name', 'slug', 'count'), true)) {
            $manage_category_sort_by = 'name';
        }
        $manage_category_sort_dir = sanitize_key($_GET['msort_dir'] ?? 'asc');
        if (!in_array($manage_category_sort_dir, array('asc', 'desc'), true)) {
            $manage_category_sort_dir = 'asc';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $post_action = sanitize_text_field($_POST['action']);

            if ($post_action === 'catalog_save_category') {
                check_admin_referer('themisdb_catalog_save_category');

                $save_entity = sanitize_text_field($_POST['entity_tab'] ?? $entity_tab);
                $category_name = sanitize_text_field($_POST['category_name'] ?? '');
                $category_slug = sanitize_title($_POST['category_slug'] ?? '');
                $old_slug = sanitize_title($_POST['old_slug'] ?? '');
                $manage_search = sanitize_text_field($_POST['mq'] ?? $manage_category_search);
                $manage_filter = sanitize_key($_POST['mfilter'] ?? $manage_category_filter);
                if (!in_array($manage_filter, array('all', 'used', 'unused'), true)) {
                    $manage_filter = 'all';
                }
                $manage_sort_by = sanitize_key($_POST['msort_by'] ?? $manage_category_sort_by);
                if (!in_array($manage_sort_by, array('name', 'slug', 'count'), true)) {
                    $manage_sort_by = 'name';
                }
                $manage_sort_dir = sanitize_key($_POST['msort_dir'] ?? $manage_category_sort_dir);
                if (!in_array($manage_sort_dir, array('asc', 'desc'), true)) {
                    $manage_sort_dir = 'asc';
                }
                $result = $this->save_catalog_category($save_entity, $category_name, $category_slug, $old_slug);
                $message = $result ? 'category_saved' : 'category_error';

                wp_redirect(add_query_arg(array(
                    'page' => 'themisdb-products',
                    'entity_tab' => $save_entity,
                    'manage_tab' => 'categories',
                    'mq' => $manage_search,
                    'mfilter' => $manage_filter,
                    'msort_by' => $manage_sort_by,
                    'msort_dir' => $manage_sort_dir,
                    'message' => $message,
                ), admin_url('admin.php')));
                exit;
            }

            if ($post_action === 'catalog_delete_category') {
                check_admin_referer('themisdb_catalog_delete_category');

                $delete_entity = sanitize_text_field($_POST['entity_tab'] ?? $entity_tab);
                $delete_slug = sanitize_title($_POST['category_slug'] ?? '');
                $manage_search = sanitize_text_field($_POST['mq'] ?? $manage_category_search);
                $manage_filter = sanitize_key($_POST['mfilter'] ?? $manage_category_filter);
                if (!in_array($manage_filter, array('all', 'used', 'unused'), true)) {
                    $manage_filter = 'all';
                }
                $manage_sort_by = sanitize_key($_POST['msort_by'] ?? $manage_category_sort_by);
                if (!in_array($manage_sort_by, array('name', 'slug', 'count'), true)) {
                    $manage_sort_by = 'name';
                }
                $manage_sort_dir = sanitize_key($_POST['msort_dir'] ?? $manage_category_sort_dir);
                if (!in_array($manage_sort_dir, array('asc', 'desc'), true)) {
                    $manage_sort_dir = 'asc';
                }
                $result = $this->delete_catalog_category($delete_entity, $delete_slug);
                $message = $result === true ? 'category_deleted' : ($result === 'in_use' ? 'category_in_use' : 'category_error');

                wp_redirect(add_query_arg(array(
                    'page' => 'themisdb-products',
                    'entity_tab' => $delete_entity,
                    'manage_tab' => 'categories',
                    'mq' => $manage_search,
                    'mfilter' => $manage_filter,
                    'msort_by' => $manage_sort_by,
                    'msort_dir' => $manage_sort_dir,
                    'message' => $message,
                ), admin_url('admin.php')));
                exit;
            }

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

                $redirect_manage_tab = sanitize_text_field($_POST['manage_tab'] ?? 'items');
                if (!in_array($redirect_manage_tab, array('categories', 'items'), true)) {
                    $redirect_manage_tab = 'items';
                }

                wp_redirect(add_query_arg(array(
                    'page' => 'themisdb-products',
                    'entity_tab' => $save_entity,
                    'manage_tab' => $redirect_manage_tab,
                    'category_tab' => sanitize_text_field($_POST['category_tab'] ?? $category_tab),
                    'q' => sanitize_text_field($_POST['q'] ?? $search_query),
                    'sort_by' => sanitize_key($_POST['sort_by'] ?? $sort_by),
                    'sort_dir' => sanitize_key($_POST['sort_dir'] ?? $sort_dir),
                    'per_page' => $this->resolve_per_page($_POST['per_page'] ?? $per_page, 25),
                    'paged' => max(1, absint($_POST['paged'] ?? $paged)),
                    'message' => ($save_result ? 'saved' : 'save_error'),
                ), admin_url('admin.php')));
                exit;
            }

            if ($post_action === 'catalog_toggle_item') {
                check_admin_referer('themisdb_catalog_toggle_item');

                $toggle_entity = sanitize_text_field($_POST['entity'] ?? 'product');
                $toggle_id = absint($_POST['item_id'] ?? 0);
                $is_active = !empty($_POST['is_active']) ? 1 : 0;
                $toggle_result = ThemisDB_Order_Manager::set_catalog_item_active($toggle_entity, $toggle_id, $is_active);

                wp_redirect(add_query_arg(array(
                    'page' => 'themisdb-products',
                    'entity_tab' => $toggle_entity,
                    'manage_tab' => sanitize_text_field($_POST['manage_tab'] ?? $manage_tab),
                    'category_tab' => sanitize_text_field($_POST['category_tab'] ?? $category_tab),
                    'q' => sanitize_text_field($_POST['q'] ?? $search_query),
                    'sort_by' => sanitize_key($_POST['sort_by'] ?? $sort_by),
                    'sort_dir' => sanitize_key($_POST['sort_dir'] ?? $sort_dir),
                    'per_page' => $this->resolve_per_page($_POST['per_page'] ?? $per_page, 25),
                    'paged' => max(1, absint($_POST['paged'] ?? $paged)),
                    'message' => ($toggle_result ? 'status_saved' : 'status_error'),
                ), admin_url('admin.php')));
                exit;
            }

            if ($post_action === 'catalog_delete_item') {
                check_admin_referer('themisdb_catalog_delete_item');

                $delete_entity = sanitize_text_field($_POST['entity'] ?? 'product');
                $delete_id = absint($_POST['item_id'] ?? 0);
                $delete_result = ThemisDB_Order_Manager::deactivate_catalog_item($delete_entity, $delete_id);

                wp_redirect(add_query_arg(array(
                    'page' => 'themisdb-products',
                    'entity_tab' => $delete_entity,
                    'manage_tab' => sanitize_text_field($_POST['manage_tab'] ?? $manage_tab),
                    'category_tab' => sanitize_text_field($_POST['category_tab'] ?? $category_tab),
                    'q' => sanitize_text_field($_POST['q'] ?? $search_query),
                    'sort_by' => sanitize_key($_POST['sort_by'] ?? $sort_by),
                    'sort_dir' => sanitize_key($_POST['sort_dir'] ?? $sort_dir),
                    'per_page' => $this->resolve_per_page($_POST['per_page'] ?? $per_page, 25),
                    'paged' => max(1, absint($_POST['paged'] ?? $paged)),
                    'message' => ($delete_result ? 'deleted' : 'delete_error'),
                ), admin_url('admin.php')));
                exit;
            }

            if ($post_action === 'catalog_bulk_items') {
                check_admin_referer('themisdb_catalog_bulk_items');
                $this->handle_catalog_bulk_items();
                return;
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

        $entity_items = $entity === 'product' ? $products : ($entity === 'module' ? $modules : $trainings);
        $code_field = $entity === 'product' ? 'product_code' : ($entity === 'module' ? 'module_code' : 'training_code');
        $name_field = $entity === 'product' ? 'product_name' : ($entity === 'module' ? 'module_name' : 'training_name');
        $category_field = $entity === 'product' ? 'product_type' : ($entity === 'module' ? 'module_category' : 'training_type');
        $sort_map = array(
            'code' => $code_field,
            'name' => $name_field,
            'category' => $category_field,
            'price' => 'price',
            'status' => 'is_active',
        );

        if ($entity === 'product') {
            $sort_map['edition'] = 'edition';
        }

        if ($entity === 'training') {
            $sort_map['duration'] = 'duration_hours';
        }

        $sort_by = array_key_exists($sort_by, $sort_map) ? $sort_by : 'code';
        $sort_dir = in_array($sort_dir, array('asc', 'desc'), true) ? $sort_dir : 'asc';

        $this->save_list_preferences('products', array(
            'entity_tab' => $entity_tab,
            'category_tab' => $category_tab,
            'sort_by' => $sort_by,
            'sort_dir' => $sort_dir,
            'per_page' => $per_page,
        ));
        $categories = array();
        $managed_categories = $this->get_catalog_categories($entity_tab);
        $edit_category_slug = sanitize_title($_GET['edit_category'] ?? '');
        $edit_category_label = isset($managed_categories[$edit_category_slug]) ? $managed_categories[$edit_category_slug] : '';

        foreach ($entity_items as $item) {
            $cat = sanitize_title($item[$category_field] ?? '');
            if ($cat !== '') {
                $categories[$cat] = isset($categories[$cat]) ? $categories[$cat] + 1 : 1;
            }
        }

        foreach ($managed_categories as $slug => $label) {
            if (!isset($categories[$slug])) {
                $categories[$slug] = 0;
            }
        }

        $managed_category_rows = array();
        foreach ($managed_categories as $slug => $label) {
            $managed_category_rows[] = array(
                'slug' => $slug,
                'label' => $label,
                'count' => intval($categories[$slug] ?? 0),
            );
        }

        if ($manage_category_filter !== 'all') {
            $managed_category_rows = array_values(array_filter($managed_category_rows, function ($row) use ($manage_category_filter) {
                $is_used = intval($row['count']) > 0;
                return $manage_category_filter === 'used' ? $is_used : !$is_used;
            }));
        }

        if ($manage_category_search !== '') {
            $needle = strtolower($manage_category_search);
            $managed_category_rows = array_values(array_filter($managed_category_rows, function ($row) use ($needle) {
                return stripos((string) $row['label'], $needle) !== false || stripos((string) $row['slug'], $needle) !== false;
            }));
        }

        usort($managed_category_rows, function ($a, $b) use ($manage_category_sort_by, $manage_category_sort_dir) {
            $dir = $manage_category_sort_dir === 'desc' ? -1 : 1;

            if ($manage_category_sort_by === 'count') {
                return (intval($a['count']) <=> intval($b['count'])) * $dir;
            }

            $left = $manage_category_sort_by === 'slug' ? (string) $a['slug'] : (string) $a['label'];
            $right = $manage_category_sort_by === 'slug' ? (string) $b['slug'] : (string) $b['label'];
            return strcasecmp($left, $right) * $dir;
        });

        ksort($categories);

        $filtered_items = $entity_items;
        if ($category_tab !== 'all') {
            $filtered_items = array_values(array_filter($entity_items, function ($item) use ($category_field, $category_tab) {
                return sanitize_title($item[$category_field] ?? '') === $category_tab;
            }));
        }

        if ($search_query !== '') {
            $filtered_items = $this->filter_rows_by_search($filtered_items, $search_query, array(
                $code_field,
                $name_field,
                $category_field,
                'edition',
            ));
        }

        $numeric_fields = array('price', 'is_active', 'duration_hours');
        $filtered_items = $this->sort_rows($filtered_items, $sort_map[$sort_by], $sort_dir, $numeric_fields);

        $pagination = $this->paginate_rows($filtered_items, $per_page, $paged);
        $filtered_items = $pagination['items'];

        $bulk_actions = array(
            'activate' => __('Aktivieren', 'themisdb-order-request'),
            'deactivate' => __('Deaktivieren', 'themisdb-order-request'),
            'delete' => __('Deaktivieren/Löschen', 'themisdb-order-request'),
        );

        $entity_label = $entity === 'product'
            ? __('Produkte', 'themisdb-order-request')
            : ($entity === 'module' ? __('Module', 'themisdb-order-request') : __('Schulungen', 'themisdb-order-request'));

        $manage_tabs = array(
            'categories' => __('Kategorien verwalten', 'themisdb-order-request'),
            'items' => __('Datensätze', 'themisdb-order-request'),
        );

        $manage_tab_query_args = array(
            'categories' => array(
                'page' => 'themisdb-products',
                'entity_tab' => $entity_tab,
                'manage_tab' => 'categories',
                'mq' => $manage_category_search,
                'mfilter' => $manage_category_filter,
                'msort_by' => $manage_category_sort_by,
                'msort_dir' => $manage_category_sort_dir,
            ),
            'items' => array(
                'page' => 'themisdb-products',
                'entity_tab' => $entity_tab,
                'manage_tab' => 'items',
                'category_tab' => $category_tab,
                'q' => $search_query,
                'sort_by' => $sort_by,
                'sort_dir' => $sort_dir,
                'per_page' => $per_page,
                'paged' => $paged,
            ),
        );

        $active_entity_count = 0;
        $inactive_entity_count = 0;
        foreach ($entity_items as $entity_item) {
            if (!empty($entity_item['is_active'])) {
                $active_entity_count++;
            } else {
                $inactive_entity_count++;
            }
        }

        $used_category_count = 0;
        foreach ($categories as $category_usage_count) {
            if (intval($category_usage_count) > 0) {
                $used_category_count++;
            }
        }
        $unused_category_count = max(0, count($managed_categories) - $used_category_count);

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Produkte und Module (CRUD)', 'themisdb-order-request'); ?></h1>
            <a class="page-title-action" href="<?php echo esc_url(add_query_arg($manage_tab_query_args['items'], admin_url('admin.php'))); ?>"><?php _e('Datensätze', 'themisdb-order-request'); ?></a>
            <a class="page-title-action" href="<?php echo esc_url(add_query_arg($manage_tab_query_args['categories'], admin_url('admin.php'))); ?>"><?php _e('Kategorien', 'themisdb-order-request'); ?></a>
            <a class="page-title-action" href="<?php echo esc_url(admin_url('admin.php?page=themisdb-inventory')); ?>"><?php _e('Lagerbestand', 'themisdb-order-request'); ?></a>
            <a class="page-title-action" href="<?php echo esc_url(admin_url('admin.php?page=themisdb-order-dashboard')); ?>"><?php _e('Zum Dashboard', 'themisdb-order-request'); ?></a>
            <hr class="wp-header-end">
            <?php $this->render_module_navigation_tabs('themisdb-products'); ?>

            <style>
            .themisdb-products-tabs-single-line {
                display: flex;
                flex-wrap: nowrap;
                overflow-x: auto;
                overflow-y: hidden;
                white-space: nowrap;
                -webkit-overflow-scrolling: touch;
            }
            .themisdb-products-tabs-single-line .nav-tab {
                float: none;
                flex: 0 0 auto;
                white-space: nowrap;
            }
            .themisdb-products-detail-tabs .nav-tab.nav-tab-active {
                background: #ffffff;
                border-bottom-color: #ffffff;
                font-weight: 600;
            }
            .themisdb-products-detail-tab-close {
                border-color: #b32d2e;
                color: #b32d2e;
                background: #fff5f5;
                font-weight: 600;
            }
            .themisdb-products-detail-tab-close:hover {
                background: #b32d2e;
                color: #ffffff;
            }
            </style>

            <?php $this->render_bulk_notice('bulk_products'); ?>

            <?php
            $entity_tabs = array(
                'product' => __('Produkte', 'themisdb-order-request') . ' (' . count($products) . ')',
                'module' => __('Module', 'themisdb-order-request') . ' (' . count($modules) . ')',
                'training' => __('Schulungen', 'themisdb-order-request') . ' (' . count($trainings) . ')',
            );
            $this->render_filter_button_bar('entity_tab', $entity_tabs, $entity_tab, admin_url('admin.php?page=themisdb-products'), array(
                'manage_tab' => $manage_tab,
                'category_tab' => $category_tab,
                'q' => $search_query,
                'sort_by' => $sort_by,
                'sort_dir' => $sort_dir,
                'per_page' => $per_page,
                'mq' => $manage_category_search,
                'mfilter' => $manage_category_filter,
                'msort_by' => $manage_category_sort_by,
                'msort_dir' => $manage_category_sort_dir,
            ), 'themisdb-products-tabs-single-line');
            
            $category_buttons = array(
                'all' => esc_html__('Alle Kategorien', 'themisdb-order-request') . ' (' . count($entity_items) . ')',
            );
            foreach ($categories as $cat => $cat_count) {
                $category_buttons[$cat] = ucwords(str_replace(array('-', '_'), ' ', (string) $cat)) . ' (' . $cat_count . ')';
            }
            
            ?>
            
            <nav class="nav-tab-wrapper themisdb-products-tabs-single-line" style="margin-bottom:1.5rem; margin-top:1.5rem; display:flex !important; flex-wrap:nowrap; overflow-x:auto; overflow-y:hidden; white-space:nowrap; -webkit-overflow-scrolling:touch;">
                <?php foreach ($manage_tabs as $key => $label): ?>
                    <a href="<?php echo esc_url(add_query_arg($manage_tab_query_args[$key], admin_url('admin.php'))); ?>" class="nav-tab <?php echo ($key === $manage_tab) ? 'nav-tab-active' : ''; ?>" style="float:none; flex:0 0 auto; white-space:nowrap;">
                        <?php echo esc_html($label); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="themisdb-admin-modules" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;margin:20px 0;">
                <?php if ($manage_tab === 'items') : ?>
                <div class="card" style="max-width:none;">
                    <h2><?php echo esc_html(sprintf(__('%s Status', 'themisdb-order-request'), $entity_label)); ?></h2>
                    <table class="widefat striped" style="border:none;box-shadow:none;">
                        <tbody>
                            <tr>
                                <td><?php _e('Datensätze gesamt', 'themisdb-order-request'); ?></td>
                                <td><strong><?php echo esc_html(number_format_i18n(count($entity_items))); ?></strong></td>
                            </tr>
                            <tr>
                                <td><?php _e('Aktiv', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html(number_format_i18n($active_entity_count)); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Inaktiv', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html(number_format_i18n($inactive_entity_count)); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Gefilterte Treffer', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html(number_format_i18n($pagination['total_items'])); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Kategorien im Typ', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html(number_format_i18n(count($categories))); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="card" style="max-width:none;">
                    <h2><?php _e('Schnellaktionen', 'themisdb-order-request'); ?></h2>
                    <p><?php _e('Datensätze pflegen, den Bestand öffnen oder direkt in einen gefilterten Typ springen.', 'themisdb-order-request'); ?></p>
                    <p>
                        <a class="button button-primary" href="#themisdb-products-list-root"><?php _e('Zur Datensatzliste', 'themisdb-order-request'); ?></a>
                        <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=themisdb-inventory')); ?>"><?php _e('Lagerbestand', 'themisdb-order-request'); ?></a>
                        <a class="button" href="<?php echo esc_url(add_query_arg(array('page' => 'themisdb-products', 'entity_tab' => $entity_tab, 'manage_tab' => 'items', 'category_tab' => 'all', 'sort_by' => 'status', 'sort_dir' => 'asc', 'per_page' => $per_page), admin_url('admin.php'))); ?>"><?php _e('Inaktive Datensätze prüfen', 'themisdb-order-request'); ?></a>
                    </p>
                </div>
                <?php else : ?>
                <div class="card" style="max-width:none;">
                    <h2><?php echo esc_html(sprintf(__('%s Kategorien', 'themisdb-order-request'), $entity_label)); ?></h2>
                    <table class="widefat striped" style="border:none;box-shadow:none;">
                        <tbody>
                            <tr>
                                <td><?php _e('Verwaltete Kategorien', 'themisdb-order-request'); ?></td>
                                <td><strong><?php echo esc_html(number_format_i18n(count($managed_categories))); ?></strong></td>
                            </tr>
                            <tr>
                                <td><?php _e('Verwendet', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html(number_format_i18n($used_category_count)); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Ungenutzt', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html(number_format_i18n($unused_category_count)); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Gefilterte Treffer', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html(number_format_i18n(count($managed_category_rows))); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="card" style="max-width:none;">
                    <h2><?php _e('Schnellaktionen', 'themisdb-order-request'); ?></h2>
                    <p><?php _e('Kategorien pflegen, ungenutzte Einträge bereinigen oder in die Datensatzansicht wechseln.', 'themisdb-order-request'); ?></p>
                    <p>
                        <a class="button button-primary" href="#themisdb-products-category-card"><?php _e('Zur Kategorienpflege', 'themisdb-order-request'); ?></a>
                        <a class="button" href="<?php echo esc_url(add_query_arg(array('page' => 'themisdb-products', 'entity_tab' => $entity_tab, 'manage_tab' => 'categories', 'mfilter' => 'unused', 'msort_by' => 'count', 'msort_dir' => 'asc'), admin_url('admin.php'))); ?>"><?php _e('Ungenutzte Kategorien', 'themisdb-order-request'); ?></a>
                        <a class="button" href="<?php echo esc_url(add_query_arg($manage_tab_query_args['items'], admin_url('admin.php'))); ?>"><?php _e('Zur Datensatzansicht', 'themisdb-order-request'); ?></a>
                    </p>
                </div>
                <?php endif; ?>
            </div>

            <form method="get" class="themisdb-ajax-form" style="margin-bottom:14px; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                <input type="hidden" name="page" value="themisdb-products">
                <input type="hidden" name="entity_tab" value="<?php echo esc_attr($entity_tab); ?>">
                <input type="hidden" name="manage_tab" value="<?php echo esc_attr($manage_tab); ?>">

                <?php if ($manage_tab === 'items'): ?>
                    <input type="hidden" name="category_tab" value="<?php echo esc_attr($category_tab); ?>">
                    <input type="hidden" name="sort_by" value="<?php echo esc_attr($sort_by); ?>">
                    <input type="hidden" name="sort_dir" value="<?php echo esc_attr($sort_dir); ?>">
                    <input type="hidden" name="paged" value="1">
                    <input type="search" name="q" value="<?php echo esc_attr($search_query); ?>" placeholder="<?php esc_attr_e('Suche nach Code, Name, Kategorie ...', 'themisdb-order-request'); ?>" class="regular-text">
                    <button type="submit" class="button"><?php _e('Suchen', 'themisdb-order-request'); ?></button>
                    <?php $this->render_per_page_select('per_page', $per_page); ?>
                <?php else: ?>
                    <input type="search" name="mq" value="<?php echo esc_attr($manage_category_search); ?>" placeholder="<?php esc_attr_e('Kategorie suchen (Name oder Slug) ...', 'themisdb-order-request'); ?>" class="regular-text">
                    <select name="mfilter">
                        <option value="all" <?php selected($manage_category_filter, 'all'); ?>><?php _e('Alle Kategorien', 'themisdb-order-request'); ?></option>
                        <option value="used" <?php selected($manage_category_filter, 'used'); ?>><?php _e('Nur verwendet', 'themisdb-order-request'); ?></option>
                        <option value="unused" <?php selected($manage_category_filter, 'unused'); ?>><?php _e('Nur ungenutzt', 'themisdb-order-request'); ?></option>
                    </select>
                    <select name="msort_by">
                        <option value="name" <?php selected($manage_category_sort_by, 'name'); ?>><?php _e('Sortierung: Name', 'themisdb-order-request'); ?></option>
                        <option value="slug" <?php selected($manage_category_sort_by, 'slug'); ?>><?php _e('Sortierung: Slug', 'themisdb-order-request'); ?></option>
                        <option value="count" <?php selected($manage_category_sort_by, 'count'); ?>><?php _e('Sortierung: Elemente', 'themisdb-order-request'); ?></option>
                    </select>
                    <select name="msort_dir">
                        <option value="asc" <?php selected($manage_category_sort_dir, 'asc'); ?>><?php _e('Aufsteigend', 'themisdb-order-request'); ?></option>
                        <option value="desc" <?php selected($manage_category_sort_dir, 'desc'); ?>><?php _e('Absteigend', 'themisdb-order-request'); ?></option>
                    </select>
                    <button type="submit" class="button"><?php _e('Anwenden', 'themisdb-order-request'); ?></button>
                <?php endif; ?>
            </form>

            <?php
            if ($manage_tab === 'items') {
                $this->render_inline_help_box(
                    __('Inline-Hilfe: Datensätze', 'themisdb-order-request'),
                    array(
                        __('Suche filtert Code, Name und Kategorie im aktiven Produkttyp.', 'themisdb-order-request'),
                        __('Kategorie-Buttons verengen die Liste zusätzlich, ohne den Listenstatus zu verlieren.', 'themisdb-order-request'),
                        __('Bearbeiten öffnet die Einzelansicht als Tab; Esc schließt sie wieder.', 'themisdb-order-request'),
                        __('Bulk gilt nur für markierte Zeilen.', 'themisdb-order-request'),
                    )
                );
            } else {
                $this->render_inline_help_box(
                    __('Inline-Hilfe: Kategorien', 'themisdb-order-request'),
                    array(
                        __('Suche, Filter und Sortierung gelten nur für die Kategorien dieses Produkttyps.', 'themisdb-order-request'),
                        __('Nur verwendet zeigt belegte, Nur ungenutzt leere Kategorien.', 'themisdb-order-request'),
                        __('Bearbeiten und Löschen behalten den aktuellen Filterzustand.', 'themisdb-order-request'),
                    )
                );
            }
            ?>

            <?php if ($manage_tab === 'items'): ?>
                <?php $this->render_filter_button_bar('category_tab', $category_buttons, $category_tab, admin_url('admin.php?page=themisdb-products'), array(
                    'entity_tab' => $entity_tab,
                    'q' => $search_query,
                    'sort_by' => $sort_by,
                    'sort_dir' => $sort_dir,
                    'per_page' => $per_page,
                    'manage_tab' => 'items',
                ), 'themisdb-products-tabs-single-line');
            endif;
            ?>

            <?php if (isset($_GET['message']) && $_GET['message'] === 'saved'): ?>
            <div class="notice notice-success"><p><?php _e('Datensatz wurde gespeichert.', 'themisdb-order-request'); ?></p></div>
            <?php endif; ?>
            <?php if (isset($_GET['message']) && $_GET['message'] === 'category_saved'): ?>
            <div class="notice notice-success"><p><?php _e('Kategorie wurde gespeichert.', 'themisdb-order-request'); ?></p></div>
            <?php endif; ?>
            <?php if (isset($_GET['message']) && $_GET['message'] === 'category_deleted'): ?>
            <div class="notice notice-success"><p><?php _e('Kategorie wurde gelöscht.', 'themisdb-order-request'); ?></p></div>
            <?php endif; ?>
            <?php if (isset($_GET['message']) && $_GET['message'] === 'category_error'): ?>
            <div class="notice notice-error"><p><?php _e('Kategorie konnte nicht gespeichert werden.', 'themisdb-order-request'); ?></p></div>
            <?php endif; ?>
            <?php if (isset($_GET['message']) && $_GET['message'] === 'category_in_use'): ?>
            <div class="notice notice-warning"><p><?php _e('Kategorie wird noch von Datensätzen verwendet und kann nicht gelöscht werden.', 'themisdb-order-request'); ?></p></div>
            <?php endif; ?>
            <?php if (isset($_GET['message']) && $_GET['message'] === 'deleted'): ?>
            <div class="notice notice-success"><p><?php _e('Datensatz wurde deaktiviert.', 'themisdb-order-request'); ?></p></div>
            <?php endif; ?>
            <?php if (isset($_GET['message']) && $_GET['message'] === 'status_saved'): ?>
            <div class="notice notice-success"><p><?php _e('Status wurde aktualisiert.', 'themisdb-order-request'); ?></p></div>
            <?php endif; ?>
            <?php if (isset($_GET['message']) && $_GET['message'] === 'save_error'): ?>
            <div class="notice notice-error"><p><?php _e('Datensatz konnte nicht gespeichert werden.', 'themisdb-order-request'); ?></p></div>
            <?php endif; ?>
            <?php if (isset($_GET['message']) && $_GET['message'] === 'status_error'): ?>
            <div class="notice notice-error"><p><?php _e('Status konnte nicht aktualisiert werden.', 'themisdb-order-request'); ?></p></div>
            <?php endif; ?>
            <?php if (isset($_GET['message']) && $_GET['message'] === 'delete_error'): ?>
            <div class="notice notice-error"><p><?php _e('Datensatz konnte nicht deaktiviert werden.', 'themisdb-order-request'); ?></p></div>
            <?php endif; ?>

            <?php if ($manage_tab === 'items'): ?>

            <?php if ($edit_item): ?>
            <?php
                $edit_code = $edit_item['product_code'] ?? $edit_item['module_code'] ?? $edit_item['training_code'] ?? '#' . absint($edit_item['id'] ?? 0);
                $close_edit_url = add_query_arg(array(
                    'page' => 'themisdb-products',
                    'entity_tab' => $entity_tab,
                    'manage_tab' => 'items',
                    'category_tab' => $category_tab,
                    'q' => $search_query,
                    'sort_by' => $sort_by,
                    'sort_dir' => $sort_dir,
                    'per_page' => $per_page,
                    'paged' => $paged,
                ), admin_url('admin.php'));
            ?>
            <nav class="nav-tab-wrapper themisdb-products-tabs-single-line themisdb-products-detail-tabs" style="margin-bottom:1rem; margin-top:1rem; display:flex !important; flex-wrap:nowrap; overflow-x:auto; overflow-y:hidden; white-space:nowrap; -webkit-overflow-scrolling:touch;">
                <a href="<?php echo esc_url($close_edit_url); ?>" class="nav-tab" style="float:none; flex:0 0 auto; white-space:nowrap;"><?php _e('Datensätze', 'themisdb-order-request'); ?></a>
                <a href="#" class="nav-tab nav-tab-active" aria-current="page" style="float:none; flex:0 0 auto; white-space:nowrap;"><?php echo esc_html(sprintf(__('Einzelansicht: %s', 'themisdb-order-request'), $edit_code)); ?></a>
                <a id="themisdb-products-close-tab" href="<?php echo esc_url($close_edit_url); ?>" class="nav-tab themisdb-products-detail-tab-close" title="<?php esc_attr_e('Einzelansicht schließen (Esc)', 'themisdb-order-request'); ?>" style="float:none; flex:0 0 auto; white-space:nowrap;"><?php _e('Schließen [X]', 'themisdb-order-request'); ?></a>
            </nav>
            <?php endif; ?>
            
            <div class="card" style="max-width:none; margin-top:20px;">
                <h2><?php echo $edit_item ? __('Datensatz bearbeiten', 'themisdb-order-request') : __('Neuen Datensatz anlegen', 'themisdb-order-request'); ?></h2>
                <form method="post">
                    <?php wp_nonce_field('themisdb_catalog_save_item'); ?>
                    <input type="hidden" name="action" value="catalog_save_item">
                    <input type="hidden" name="item_id" value="<?php echo $edit_item ? absint($edit_item['id']) : 0; ?>">
                    <input type="hidden" name="manage_tab" value="items">
                    <input type="hidden" name="category_tab" value="<?php echo esc_attr($category_tab); ?>">
                    <input type="hidden" name="q" value="<?php echo esc_attr($search_query); ?>">
                    <input type="hidden" name="sort_by" value="<?php echo esc_attr($sort_by); ?>">
                    <input type="hidden" name="sort_dir" value="<?php echo esc_attr($sort_dir); ?>">
                    <input type="hidden" name="per_page" value="<?php echo absint($per_page); ?>">
                    <input type="hidden" name="paged" value="<?php echo absint($paged); ?>">

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
                        <a href="<?php echo esc_url(add_query_arg(array('page' => 'themisdb-products', 'entity_tab' => $entity_tab, 'manage_tab' => 'items', 'category_tab' => $category_tab, 'q' => $search_query, 'sort_by' => $sort_by, 'sort_dir' => $sort_dir, 'per_page' => $per_page, 'paged' => $paged), admin_url('admin.php'))); ?>" class="button"><?php _e('Abbrechen', 'themisdb-order-request'); ?></a>
                        <?php endif; ?>
                    </p>
                </form>
            </div>

            <?php endif; // end if manage_tab === 'items' ?>
            
            <?php if ($manage_tab === 'categories'): ?>

            <div id="themisdb-products-category-card" class="card" style="max-width:none; margin-top:20px;">
                <h2><?php _e('Kategorien verwalten', 'themisdb-order-request'); ?></h2>
                <form method="post" style="margin-bottom:12px;">
                    <?php wp_nonce_field('themisdb_catalog_save_category'); ?>
                    <input type="hidden" name="action" value="catalog_save_category">
                    <input type="hidden" name="entity_tab" value="<?php echo esc_attr($entity_tab); ?>">
                    <input type="hidden" name="old_slug" value="<?php echo esc_attr($edit_category_slug); ?>">
                    <input type="hidden" name="mq" value="<?php echo esc_attr($manage_category_search); ?>">
                    <input type="hidden" name="mfilter" value="<?php echo esc_attr($manage_category_filter); ?>">
                    <input type="hidden" name="msort_by" value="<?php echo esc_attr($manage_category_sort_by); ?>">
                    <input type="hidden" name="msort_dir" value="<?php echo esc_attr($manage_category_sort_dir); ?>">
                    <table class="form-table">
                        <tr>
                            <th><label for="catalog_category_name"><?php _e('Name', 'themisdb-order-request'); ?></label></th>
                            <td><input id="catalog_category_name" name="category_name" class="regular-text" value="<?php echo esc_attr($edit_category_label); ?>" required></td>
                        </tr>
                        <tr>
                            <th><label for="catalog_category_slug"><?php _e('Slug', 'themisdb-order-request'); ?></label></th>
                            <td><input id="catalog_category_slug" name="category_slug" class="regular-text" value="<?php echo esc_attr($edit_category_slug); ?>"></td>
                        </tr>
                    </table>
                    <p>
                        <button type="submit" class="button button-secondary"><?php echo $edit_category_slug !== '' ? esc_html__('Kategorie aktualisieren', 'themisdb-order-request') : esc_html__('Kategorie anlegen', 'themisdb-order-request'); ?></button>
                        <?php if ($edit_category_slug !== ''): ?>
                        <a class="button" href="<?php echo esc_url(add_query_arg(array('page' => 'themisdb-products', 'entity_tab' => $entity_tab, 'manage_tab' => 'categories', 'mq' => $manage_category_search, 'mfilter' => $manage_category_filter, 'msort_by' => $manage_category_sort_by, 'msort_dir' => $manage_category_sort_dir), admin_url('admin.php'))); ?>"><?php _e('Abbrechen', 'themisdb-order-request'); ?></a>
                        <?php endif; ?>
                    </p>
                </form>

                <table class="wp-list-table widefat striped">
                    <thead>
                        <tr>
                            <th><?php _e('Name', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Slug', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Elemente', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Aktionen', 'themisdb-order-request'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($managed_category_rows)): ?>
                        <tr><td colspan="4"><?php _e('Keine verwalteten Kategorien vorhanden.', 'themisdb-order-request'); ?></td></tr>
                        <?php else: ?>
                            <?php foreach ($managed_category_rows as $category_row): ?>
                            <?php $cat_slug = $category_row['slug']; $cat_label = $category_row['label']; ?>
                            <tr>
                                <td><?php echo esc_html($cat_label); ?></td>
                                <td><code><?php echo esc_html($cat_slug); ?></code></td>
                                <td><?php echo intval($category_row['count']); ?></td>
                                <td>
                                    <a class="button button-small" href="<?php echo esc_url(add_query_arg(array('page' => 'themisdb-products', 'entity_tab' => $entity_tab, 'manage_tab' => 'categories', 'edit_category' => $cat_slug, 'mq' => $manage_category_search, 'mfilter' => $manage_category_filter, 'msort_by' => $manage_category_sort_by, 'msort_dir' => $manage_category_sort_dir), admin_url('admin.php'))); ?>"><?php _e('Bearbeiten', 'themisdb-order-request'); ?></a>
                                    <form method="post" style="display:inline;" onsubmit="return confirm('<?php _e('Kategorie wirklich löschen?', 'themisdb-order-request'); ?>');">
                                        <?php wp_nonce_field('themisdb_catalog_delete_category'); ?>
                                        <input type="hidden" name="action" value="catalog_delete_category">
                                        <input type="hidden" name="entity_tab" value="<?php echo esc_attr($entity_tab); ?>">
                                        <input type="hidden" name="category_slug" value="<?php echo esc_attr($cat_slug); ?>">
                                        <input type="hidden" name="mq" value="<?php echo esc_attr($manage_category_search); ?>">
                                        <input type="hidden" name="mfilter" value="<?php echo esc_attr($manage_category_filter); ?>">
                                        <input type="hidden" name="msort_by" value="<?php echo esc_attr($manage_category_sort_by); ?>">
                                        <input type="hidden" name="msort_dir" value="<?php echo esc_attr($manage_category_sort_dir); ?>">
                                        <button type="submit" class="button button-small button-link-delete"><?php _e('Löschen', 'themisdb-order-request'); ?></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php endif; // end if manage_tab === 'categories' ?>

            <?php if ($manage_tab === 'items'): ?>

            <div class="card themisdb-list-root" id="themisdb-products-list-root">
                <h2><?php _e('Datensätze', 'themisdb-order-request'); ?></h2>

                <form method="post">
                    <?php wp_nonce_field('themisdb_catalog_bulk_items'); ?>
                    <input type="hidden" name="action" value="catalog_bulk_items">
                    <input type="hidden" name="entity" value="<?php echo esc_attr($entity_tab); ?>">
                    <input type="hidden" name="category_tab" value="<?php echo esc_attr($category_tab); ?>">
                    <input type="hidden" name="manage_tab" value="items">
                    <input type="hidden" name="q" value="<?php echo esc_attr($search_query); ?>">
                    <input type="hidden" name="sort_by" value="<?php echo esc_attr($sort_by); ?>">
                    <input type="hidden" name="sort_dir" value="<?php echo esc_attr($sort_dir); ?>">
                    <input type="hidden" name="per_page" value="<?php echo absint($per_page); ?>">
                    <input type="hidden" name="paged" value="<?php echo absint($paged); ?>">
                    <?php $this->render_bulk_action_bar('catalog_bulk_action', $bulk_actions, __('Ausgewählte Datensätze verarbeiten', 'themisdb-order-request')); ?>

                    <table class="wp-list-table widefat striped">
                        <thead>
                            <tr>
                                <td class="check-column"><input type="checkbox" class="themisdb-bulk-toggle" aria-label="<?php esc_attr_e('Alle Datensätze auswählen', 'themisdb-order-request'); ?>"></td>
                                <th><?php $this->render_sortable_column(__('Code', 'themisdb-order-request'), 'code', $sort_by, $sort_dir, admin_url('admin.php?page=themisdb-products'), array('entity_tab' => $entity_tab, 'manage_tab' => 'items', 'category_tab' => $category_tab, 'q' => $search_query, 'per_page' => $per_page)); ?></th>
                                <th><?php $this->render_sortable_column(__('Name', 'themisdb-order-request'), 'name', $sort_by, $sort_dir, admin_url('admin.php?page=themisdb-products'), array('entity_tab' => $entity_tab, 'manage_tab' => 'items', 'category_tab' => $category_tab, 'q' => $search_query, 'per_page' => $per_page)); ?></th>
                                <th><?php $this->render_sortable_column(__('Kategorie', 'themisdb-order-request'), 'category', $sort_by, $sort_dir, admin_url('admin.php?page=themisdb-products'), array('entity_tab' => $entity_tab, 'manage_tab' => 'items', 'category_tab' => $category_tab, 'q' => $search_query, 'per_page' => $per_page)); ?></th>
                                <?php if ($entity_tab === 'product'): ?><th><?php $this->render_sortable_column(__('Edition', 'themisdb-order-request'), 'edition', $sort_by, $sort_dir, admin_url('admin.php?page=themisdb-products'), array('entity_tab' => $entity_tab, 'manage_tab' => 'items', 'category_tab' => $category_tab, 'q' => $search_query, 'per_page' => $per_page)); ?></th><?php endif; ?>
                                <?php if ($entity_tab === 'training'): ?><th><?php $this->render_sortable_column(__('Dauer', 'themisdb-order-request'), 'duration', $sort_by, $sort_dir, admin_url('admin.php?page=themisdb-products'), array('entity_tab' => $entity_tab, 'manage_tab' => 'items', 'category_tab' => $category_tab, 'q' => $search_query, 'per_page' => $per_page)); ?></th><?php endif; ?>
                                <th><?php $this->render_sortable_column(__('Preis', 'themisdb-order-request'), 'price', $sort_by, $sort_dir, admin_url('admin.php?page=themisdb-products'), array('entity_tab' => $entity_tab, 'manage_tab' => 'items', 'category_tab' => $category_tab, 'q' => $search_query, 'per_page' => $per_page)); ?></th>
                                <th><?php $this->render_sortable_column(__('Status', 'themisdb-order-request'), 'status', $sort_by, $sort_dir, admin_url('admin.php?page=themisdb-products'), array('entity_tab' => $entity_tab, 'manage_tab' => 'items', 'category_tab' => $category_tab, 'q' => $search_query, 'per_page' => $per_page)); ?></th>
                                <th><?php _e('Aktionen', 'themisdb-order-request'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($filtered_items)): ?>
                            <tr>
                                <td colspan="8"><?php _e('Keine Datensätze in dieser Kategorie gefunden.', 'themisdb-order-request'); ?></td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($filtered_items as $row): ?>
                                <?php
                                $row_id = absint($row['id']);
                                $row_code = $entity_tab === 'product' ? ($row['product_code'] ?? '') : ($entity_tab === 'module' ? ($row['module_code'] ?? '') : ($row['training_code'] ?? ''));
                                $row_name = $entity_tab === 'product' ? ($row['product_name'] ?? '') : ($entity_tab === 'module' ? ($row['module_name'] ?? '') : ($row['training_name'] ?? ''));
                                $row_cat = $entity_tab === 'product' ? ($row['product_type'] ?? '') : ($entity_tab === 'module' ? ($row['module_category'] ?? '') : ($row['training_type'] ?? ''));
                                $row_active = !empty($row['is_active']);
                                $row_edit_url = add_query_arg(array(
                                    'page' => 'themisdb-products',
                                    'entity_tab' => $entity_tab,
                                    'entity' => $entity_tab,
                                    'manage_tab' => 'items',
                                    'category_tab' => $category_tab,
                                    'q' => $search_query,
                                    'sort_by' => $sort_by,
                                    'sort_dir' => $sort_dir,
                                    'per_page' => $per_page,
                                    'paged' => $paged,
                                    'edit_id' => $row_id,
                                ), admin_url('admin.php'));
                                ?>
                                <tr>
                                    <th scope="row" class="check-column"><input type="checkbox" name="item_ids[]" value="<?php echo $row_id; ?>"></th>
                                    <td><?php echo esc_html($row_code); ?></td>
                                    <td><?php echo esc_html($row_name); ?></td>
                                    <td><?php echo esc_html($row_cat); ?></td>
                                    <?php if ($entity_tab === 'product'): ?><td><?php echo esc_html($row['edition'] ?? ''); ?></td><?php endif; ?>
                                    <?php if ($entity_tab === 'training'): ?><td><?php echo absint($row['duration_hours'] ?? 0); ?> h</td><?php endif; ?>
                                    <td><?php echo number_format(floatval($row['price'] ?? 0), 2, ',', '.'); ?> <?php echo esc_html($row['currency'] ?? 'EUR'); ?></td>
                                    <td><?php echo $row_active ? __('Aktiv', 'themisdb-order-request') : __('Inaktiv', 'themisdb-order-request'); ?></td>
                                    <td>
                                        <a class="button button-small" href="<?php echo esc_url($row_edit_url); ?>"><?php _e('Bearbeiten', 'themisdb-order-request'); ?></a>
                                        <form method="post" style="display:inline;">
                                            <?php wp_nonce_field('themisdb_catalog_toggle_item'); ?>
                                            <input type="hidden" name="action" value="catalog_toggle_item">
                                            <input type="hidden" name="entity" value="<?php echo esc_attr($entity_tab); ?>">
                                            <input type="hidden" name="item_id" value="<?php echo $row_id; ?>">
                                            <input type="hidden" name="is_active" value="<?php echo $row_active ? '0' : '1'; ?>">
                                            <input type="hidden" name="manage_tab" value="items">
                                            <input type="hidden" name="category_tab" value="<?php echo esc_attr($category_tab); ?>">
                                            <input type="hidden" name="q" value="<?php echo esc_attr($search_query); ?>">
                                            <input type="hidden" name="sort_by" value="<?php echo esc_attr($sort_by); ?>">
                                            <input type="hidden" name="sort_dir" value="<?php echo esc_attr($sort_dir); ?>">
                                            <input type="hidden" name="per_page" value="<?php echo absint($per_page); ?>">
                                            <input type="hidden" name="paged" value="<?php echo absint($paged); ?>">
                                            <button type="submit" class="button button-small"><?php echo $row_active ? __('Deaktivieren', 'themisdb-order-request') : __('Aktivieren', 'themisdb-order-request'); ?></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </form>

                <?php
                $this->render_simple_pagination(
                    admin_url('admin.php?page=themisdb-products'),
                    $pagination['paged'],
                    $pagination['total_pages'],
                    array(
                        'entity_tab' => $entity_tab,
                        'manage_tab' => 'items',
                        'category_tab' => $category_tab,
                        'q' => $search_query,
                        'sort_by' => $sort_by,
                        'sort_dir' => $sort_dir,
                        'per_page' => $per_page,
                    )
                );
                ?>
            </div>

            <?php $this->render_bulk_table_script(); ?>
            
            <?php endif; // end if manage_tab === 'items' ?>
        </div>
        <script>
        (function() {
            const closeEditTab = document.getElementById('themisdb-products-close-tab');
            if (closeEditTab) {
                document.addEventListener('keydown', function(event) {
                    if (event.key !== 'Escape') {
                        return;
                    }

                    if (event.ctrlKey || event.metaKey || event.altKey || event.shiftKey) {
                        return;
                    }

                    const target = event.target;
                    if (target && (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.tagName === 'SELECT')) {
                        return;
                    }

                    event.preventDefault();
                    window.location.href = closeEditTab.getAttribute('href');
                });
            }

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
        $prefs = $this->get_list_preferences('inventory');
        $edit_id = isset($_GET['edit_id']) ? absint($_GET['edit_id']) : 0;
        $manage_tab = isset($_GET['manage_tab']) ? sanitize_text_field($_GET['manage_tab']) : 'categories';
        if (!in_array($manage_tab, array('categories', 'items'), true)) {
            $manage_tab = 'categories';
        }
        $category_tab = isset($_GET['category_tab']) ? sanitize_text_field($_GET['category_tab']) : ($prefs['category_tab'] ?? 'all');
        $edit_category_slug = sanitize_title($_GET['edit_category'] ?? '');
        $search_query = sanitize_text_field($_GET['q'] ?? '');
        $sort_by = sanitize_key($_GET['sort_by'] ?? ($prefs['sort_by'] ?? 'sku'));
        $sort_dir = sanitize_key($_GET['sort_dir'] ?? ($prefs['sort_dir'] ?? 'asc'));
        $category_manage_search = sanitize_text_field($_GET['cq'] ?? '');
        $category_manage_filter = sanitize_key($_GET['cfilter'] ?? 'all');
        if (!in_array($category_manage_filter, array('all', 'used', 'unused'), true)) {
            $category_manage_filter = 'all';
        }
        $category_manage_sort_by = sanitize_key($_GET['csort_by'] ?? 'name');
        if (!in_array($category_manage_sort_by, array('name', 'slug', 'count'), true)) {
            $category_manage_sort_by = 'name';
        }
        $category_manage_sort_dir = sanitize_key($_GET['csort_dir'] ?? 'asc');
        if (!in_array($category_manage_sort_dir, array('asc', 'desc'), true)) {
            $category_manage_sort_dir = 'asc';
        }
        $per_page = $this->resolve_per_page($_GET['per_page'] ?? ($prefs['per_page'] ?? 25), 25);
        $paged = max(1, absint($_GET['paged'] ?? 1));

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $post_action = sanitize_text_field($_POST['action']);

            if ($post_action === 'inventory_save_category') {
                check_admin_referer('themisdb_inventory_save_category');

                $category_name = sanitize_text_field($_POST['category_name'] ?? '');
                $category_slug = sanitize_title($_POST['category_slug'] ?? '');
                $old_slug = sanitize_title($_POST['old_slug'] ?? '');
                $manage_search = sanitize_text_field($_POST['cq'] ?? $category_manage_search);
                $manage_filter = sanitize_key($_POST['cfilter'] ?? $category_manage_filter);
                if (!in_array($manage_filter, array('all', 'used', 'unused'), true)) {
                    $manage_filter = 'all';
                }
                $manage_sort_by = sanitize_key($_POST['csort_by'] ?? $category_manage_sort_by);
                if (!in_array($manage_sort_by, array('name', 'slug', 'count'), true)) {
                    $manage_sort_by = 'name';
                }
                $manage_sort_dir = sanitize_key($_POST['csort_dir'] ?? $category_manage_sort_dir);
                if (!in_array($manage_sort_dir, array('asc', 'desc'), true)) {
                    $manage_sort_dir = 'asc';
                }
                $result = $this->save_inventory_category($category_name, $category_slug, $old_slug);
                $message = $result ? 'category_saved' : 'category_error';
                wp_redirect(add_query_arg(array(
                    'page' => 'themisdb-inventory',
                    'manage_tab' => 'categories',
                    'cq' => $manage_search,
                    'cfilter' => $manage_filter,
                    'csort_by' => $manage_sort_by,
                    'csort_dir' => $manage_sort_dir,
                    'message' => $message,
                ), admin_url('admin.php')));
                exit;
            }

            if ($post_action === 'inventory_delete_category') {
                check_admin_referer('themisdb_inventory_delete_category');

                $delete_slug = sanitize_title($_POST['category_slug'] ?? '');
                $manage_search = sanitize_text_field($_POST['cq'] ?? $category_manage_search);
                $manage_filter = sanitize_key($_POST['cfilter'] ?? $category_manage_filter);
                if (!in_array($manage_filter, array('all', 'used', 'unused'), true)) {
                    $manage_filter = 'all';
                }
                $manage_sort_by = sanitize_key($_POST['csort_by'] ?? $category_manage_sort_by);
                if (!in_array($manage_sort_by, array('name', 'slug', 'count'), true)) {
                    $manage_sort_by = 'name';
                }
                $manage_sort_dir = sanitize_key($_POST['csort_dir'] ?? $category_manage_sort_dir);
                if (!in_array($manage_sort_dir, array('asc', 'desc'), true)) {
                    $manage_sort_dir = 'asc';
                }
                $result = $this->delete_inventory_category($delete_slug);
                $message = $result === true ? 'category_deleted' : ($result === 'in_use' ? 'category_in_use' : 'category_error');
                wp_redirect(add_query_arg(array(
                    'page' => 'themisdb-inventory',
                    'manage_tab' => 'categories',
                    'cq' => $manage_search,
                    'cfilter' => $manage_filter,
                    'csort_by' => $manage_sort_by,
                    'csort_dir' => $manage_sort_dir,
                    'message' => $message,
                ), admin_url('admin.php')));
                exit;
            }

            if ($post_action === 'save_inventory') {
                check_admin_referer('themisdb_save_inventory');

                $item_id = absint($_POST['item_id'] ?? 0);
                $redirect_args = array(
                    'page' => 'themisdb-inventory',
                    'manage_tab' => 'items',
                    'category_tab' => sanitize_text_field($_POST['category_tab'] ?? $category_tab),
                    'q' => sanitize_text_field($_POST['q'] ?? $search_query),
                    'sort_by' => sanitize_key($_POST['sort_by'] ?? $sort_by),
                    'sort_dir' => sanitize_key($_POST['sort_dir'] ?? $sort_dir),
                    'per_page' => $this->resolve_per_page($_POST['per_page'] ?? $per_page, 25),
                    'paged' => max(1, absint($_POST['paged'] ?? $paged)),
                );
                $payload = array(
                    'id' => $item_id,
                    'sku' => sanitize_text_field($_POST['sku'] ?? ''),
                    'product_name' => sanitize_text_field($_POST['product_name'] ?? ''),
                    'stock_on_hand' => intval($_POST['stock_on_hand'] ?? 0),
                    'product_id' => absint($_POST['product_id'] ?? 0),
                    'reorder_level' => intval($_POST['reorder_level'] ?? 0),
                    'category_slug' => sanitize_title($_POST['category_slug'] ?? ''),
                    'is_active' => !empty($_POST['is_active']) ? 1 : 0,
                );

                if ($payload['sku'] !== '' && $payload['product_name'] !== '') {
                    $save_result = ThemisDB_Order_Manager::save_inventory_item($payload, $item_id);
                    $redirect_args['message'] = $save_result ? 'saved' : 'save_error';
                    wp_redirect(add_query_arg($redirect_args, admin_url('admin.php')));
                    exit;
                }

                $redirect_args['message'] = 'save_error';
                wp_redirect(add_query_arg($redirect_args, admin_url('admin.php')));
                exit;
            }

            if ($post_action === 'inventory_bulk_items') {
                check_admin_referer('themisdb_inventory_bulk_items');
                $this->handle_inventory_bulk_items();
                return;
            }

            if ($post_action === 'sync_inventory_all') {
                check_admin_referer('themisdb_sync_inventory_all');

                $redirect_manage_tab = sanitize_text_field($_POST['manage_tab'] ?? $manage_tab);
                if (!in_array($redirect_manage_tab, array('categories', 'items'), true)) {
                    $redirect_manage_tab = 'items';
                }
                $redirect_category_tab = sanitize_text_field($_POST['category_tab'] ?? $category_tab);
                $redirect_search_query = sanitize_text_field($_POST['q'] ?? $search_query);
                $redirect_sort_by = sanitize_key($_POST['sort_by'] ?? $sort_by);
                $redirect_sort_dir = sanitize_key($_POST['sort_dir'] ?? $sort_dir);
                if (!in_array($redirect_sort_dir, array('asc', 'desc'), true)) {
                    $redirect_sort_dir = 'asc';
                }
                $redirect_per_page = $this->resolve_per_page($_POST['per_page'] ?? $per_page, 25);
                $redirect_paged = max(1, absint($_POST['paged'] ?? $paged));
                $redirect_manage_search = sanitize_text_field($_POST['cq'] ?? $category_manage_search);
                $redirect_manage_filter = sanitize_key($_POST['cfilter'] ?? $category_manage_filter);
                if (!in_array($redirect_manage_filter, array('all', 'used', 'unused'), true)) {
                    $redirect_manage_filter = 'all';
                }
                $redirect_manage_sort_by = sanitize_key($_POST['csort_by'] ?? $category_manage_sort_by);
                if (!in_array($redirect_manage_sort_by, array('name', 'slug', 'count'), true)) {
                    $redirect_manage_sort_by = 'name';
                }
                $redirect_manage_sort_dir = sanitize_key($_POST['csort_dir'] ?? $category_manage_sort_dir);
                if (!in_array($redirect_manage_sort_dir, array('asc', 'desc'), true)) {
                    $redirect_manage_sort_dir = 'asc';
                }

                $sync_results = ThemisDB_Order_Manager::sync_all_to_inventory();
                $total_synced = intval($sync_results['total_synced']);
                $total_errors = intval($sync_results['total_errors']);

                $message = 'sync_completed';
                if ($total_errors > 0) {
                    $message = 'sync_with_errors';
                }

                wp_redirect(add_query_arg(array(
                    'page' => 'themisdb-inventory',
                    'manage_tab' => $redirect_manage_tab,
                    'category_tab' => $redirect_category_tab,
                    'q' => $redirect_search_query,
                    'sort_by' => $redirect_sort_by,
                    'sort_dir' => $redirect_sort_dir,
                    'per_page' => $redirect_per_page,
                    'paged' => $redirect_paged,
                    'cq' => $redirect_manage_search,
                    'cfilter' => $redirect_manage_filter,
                    'csort_by' => $redirect_manage_sort_by,
                    'csort_dir' => $redirect_manage_sort_dir,
                    'message' => $message,
                    'synced' => $total_synced,
                    'errors' => $total_errors,
                ), admin_url('admin.php')));
                exit;
            }
        }

        $inventory_items = ThemisDB_Order_Manager::get_inventory_items(true);
        $products = ThemisDB_Order_Manager::get_products(true);
        $edit_item = $edit_id > 0 ? ThemisDB_Order_Manager::get_inventory_item($edit_id) : null;
        $managed_categories = $this->get_inventory_categories();
        $edit_category_label = isset($managed_categories[$edit_category_slug]) ? $managed_categories[$edit_category_slug] : '';

        $categories = array();
        foreach ($inventory_items as $item) {
            $cat = sanitize_title($item['category_slug'] ?? '');
            if ($cat !== '') {
                $categories[$cat] = isset($categories[$cat]) ? $categories[$cat] + 1 : 1;
            }
        }

        foreach ($managed_categories as $slug => $label) {
            if (!isset($categories[$slug])) {
                $categories[$slug] = 0;
            }
        }
        ksort($categories);

        $managed_category_rows = array();
        foreach ($managed_categories as $slug => $label) {
            $managed_category_rows[] = array(
                'slug' => $slug,
                'label' => $label,
                'count' => intval($categories[$slug] ?? 0),
            );
        }

        if ($category_manage_filter !== 'all') {
            $managed_category_rows = array_values(array_filter($managed_category_rows, function ($row) use ($category_manage_filter) {
                $is_used = intval($row['count']) > 0;
                return $category_manage_filter === 'used' ? $is_used : !$is_used;
            }));
        }

        if ($category_manage_search !== '') {
            $needle = strtolower($category_manage_search);
            $managed_category_rows = array_values(array_filter($managed_category_rows, function ($row) use ($needle) {
                return stripos((string) $row['label'], $needle) !== false || stripos((string) $row['slug'], $needle) !== false;
            }));
        }

        usort($managed_category_rows, function ($a, $b) use ($category_manage_sort_by, $category_manage_sort_dir) {
            $dir = $category_manage_sort_dir === 'desc' ? -1 : 1;

            if ($category_manage_sort_by === 'count') {
                return (intval($a['count']) <=> intval($b['count'])) * $dir;
            }

            $left = $category_manage_sort_by === 'slug' ? (string) $a['slug'] : (string) $a['label'];
            $right = $category_manage_sort_by === 'slug' ? (string) $b['slug'] : (string) $b['label'];
            return strcasecmp($left, $right) * $dir;
        });

        $filtered_inventory = $inventory_items;
        if ($category_tab !== 'all') {
            $filtered_inventory = array_values(array_filter($inventory_items, function ($item) use ($category_tab) {
                return sanitize_title($item['category_slug'] ?? '') === $category_tab;
            }));
        }

        if ($search_query !== '') {
            $filtered_inventory = $this->filter_rows_by_search($filtered_inventory, $search_query, array('sku', 'product_name', 'category_slug'));
        }

        $sort_map = array(
            'sku' => 'sku',
            'name' => 'product_name',
            'category' => 'category_slug',
            'stock' => 'stock_on_hand',
            'reserved' => 'reserved_stock',
            'reorder' => 'reorder_level',
            'status' => 'is_active',
        );
        $sort_by = array_key_exists($sort_by, $sort_map) ? $sort_by : 'sku';
        $sort_dir = in_array($sort_dir, array('asc', 'desc'), true) ? $sort_dir : 'asc';
        $this->save_list_preferences('inventory', array(
            'category_tab' => $category_tab,
            'sort_by' => $sort_by,
            'sort_dir' => $sort_dir,
            'per_page' => $per_page,
        ));
        $filtered_inventory = $this->sort_rows($filtered_inventory, $sort_map[$sort_by], $sort_dir, array('stock_on_hand', 'reserved_stock', 'reorder_level', 'is_active'));

        $pagination = $this->paginate_rows($filtered_inventory, $per_page, $paged);
        $filtered_inventory = $pagination['items'];

        $bulk_actions = array(
            'activate' => __('Aktivieren', 'themisdb-order-request'),
            'deactivate' => __('Deaktivieren', 'themisdb-order-request'),
            'delete' => __('Deaktivieren/Löschen', 'themisdb-order-request'),
        );

        $active_inventory_count = 0;
        $inactive_inventory_count = 0;
        $infinite_inventory_count = 0;
        $low_stock_count = 0;

        foreach ($inventory_items as $inventory_item) {
            $stock_on_hand = intval($inventory_item['stock_on_hand'] ?? 0);
            $reserved_stock = intval($inventory_item['reserved_stock'] ?? 0);
            $reorder_level = intval($inventory_item['reorder_level'] ?? 0);
            $is_active = !empty($inventory_item['is_active']);

            if ($is_active) {
                $active_inventory_count++;
            } else {
                $inactive_inventory_count++;
            }

            if ($stock_on_hand === -1) {
                $infinite_inventory_count++;
                continue;
            }

            if (($stock_on_hand - $reserved_stock) <= $reorder_level) {
                $low_stock_count++;
            }
        }

        $used_category_count = 0;
        foreach ($categories as $category_usage_count) {
            if (intval($category_usage_count) > 0) {
                $used_category_count++;
            }
        }
        $unused_category_count = max(0, count($managed_categories) - $used_category_count);

        $manage_tabs = array(
            'categories' => __('Kategorien verwalten', 'themisdb-order-request'),
            'items' => __('Lagerbestand', 'themisdb-order-request'),
        );

        $manage_tab_query_args = array(
            'categories' => array(
                'page' => 'themisdb-inventory',
                'manage_tab' => 'categories',
                'cq' => $category_manage_search,
                'cfilter' => $category_manage_filter,
                'csort_by' => $category_manage_sort_by,
                'csort_dir' => $category_manage_sort_dir,
            ),
            'items' => array(
                'page' => 'themisdb-inventory',
                'manage_tab' => 'items',
                'category_tab' => $category_tab,
                'q' => $search_query,
                'sort_by' => $sort_by,
                'sort_dir' => $sort_dir,
                'per_page' => $per_page,
                'paged' => $paged,
            ),
        );
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Lagerbestand', 'themisdb-order-request'); ?></h1>
            <a class="page-title-action" href="<?php echo esc_url(add_query_arg($manage_tab_query_args['items'], admin_url('admin.php'))); ?>"><?php _e('Artikelansicht', 'themisdb-order-request'); ?></a>
            <a class="page-title-action" href="<?php echo esc_url(add_query_arg($manage_tab_query_args['categories'], admin_url('admin.php'))); ?>"><?php _e('Kategorien', 'themisdb-order-request'); ?></a>
            <a class="page-title-action" href="<?php echo esc_url(admin_url('admin.php?page=themisdb-products')); ?>"><?php _e('Produkte', 'themisdb-order-request'); ?></a>
            <a class="page-title-action" href="<?php echo esc_url(admin_url('admin.php?page=themisdb-order-dashboard')); ?>"><?php _e('Zum Dashboard', 'themisdb-order-request'); ?></a>
            <hr class="wp-header-end">
            <?php $this->render_module_navigation_tabs('themisdb-inventory'); ?>

            <?php ?>
            
            <nav class="nav-tab-wrapper" style="margin-bottom:1.5rem; margin-top:1.5rem; display:flex; flex-wrap:nowrap; overflow-x:auto; overflow-y:hidden; white-space:nowrap; -webkit-overflow-scrolling:touch;">
                <?php foreach ($manage_tabs as $key => $label): ?>
                    <a href="<?php echo esc_url(add_query_arg($manage_tab_query_args[$key], admin_url('admin.php'))); ?>" class="nav-tab <?php echo ($key === $manage_tab) ? 'nav-tab-active' : ''; ?>" style="float:none; flex:0 0 auto; white-space:nowrap;">
                        <?php echo esc_html($label); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="themisdb-admin-modules" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;margin:20px 0;">
                <?php if ($manage_tab === 'items') : ?>
                <div class="card" style="max-width:none;">
                    <h2><?php _e('Bestandsstatus', 'themisdb-order-request'); ?></h2>
                    <table class="widefat striped" style="border:none;box-shadow:none;">
                        <tbody>
                            <tr>
                                <td><?php _e('Artikel gesamt', 'themisdb-order-request'); ?></td>
                                <td><strong><?php echo esc_html(number_format_i18n(count($inventory_items))); ?></strong></td>
                            </tr>
                            <tr>
                                <td><?php _e('Aktiv', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html(number_format_i18n($active_inventory_count)); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Inaktiv', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html(number_format_i18n($inactive_inventory_count)); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Unbegrenzt', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html(number_format_i18n($infinite_inventory_count)); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Am oder unter Meldebestand', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html(number_format_i18n($low_stock_count)); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="card" style="max-width:none;">
                    <h2><?php _e('Schnellaktionen', 'themisdb-order-request'); ?></h2>
                    <p><?php _e('Synchronisieren Sie Produktbestände, springen Sie in die Katalogpflege oder fokussieren Sie einen Bestandsbereich.', 'themisdb-order-request'); ?></p>
                    <p>
                        <a class="button button-primary" href="#inventory-sync-card"><?php _e('Zur Synchronisierung', 'themisdb-order-request'); ?></a>
                        <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=themisdb-products')); ?>"><?php _e('Produktkatalog', 'themisdb-order-request'); ?></a>
                        <a class="button" href="<?php echo esc_url(add_query_arg(array('page' => 'themisdb-inventory', 'manage_tab' => 'items', 'category_tab' => 'all', 'sort_by' => 'stock', 'sort_dir' => 'asc', 'per_page' => $per_page), admin_url('admin.php'))); ?>"><?php _e('Niedrige Bestände prüfen', 'themisdb-order-request'); ?></a>
                    </p>
                </div>
                <?php else : ?>
                <div class="card" style="max-width:none;">
                    <h2><?php _e('Kategoriebestand', 'themisdb-order-request'); ?></h2>
                    <table class="widefat striped" style="border:none;box-shadow:none;">
                        <tbody>
                            <tr>
                                <td><?php _e('Verwaltete Kategorien', 'themisdb-order-request'); ?></td>
                                <td><strong><?php echo esc_html(number_format_i18n(count($managed_categories))); ?></strong></td>
                            </tr>
                            <tr>
                                <td><?php _e('Verwendet', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html(number_format_i18n($used_category_count)); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Ungenutzt', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html(number_format_i18n($unused_category_count)); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Gefilterte Treffer', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html(number_format_i18n(count($managed_category_rows))); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="card" style="max-width:none;">
                    <h2><?php _e('Schnellaktionen', 'themisdb-order-request'); ?></h2>
                    <p><?php _e('Kategorien pflegen, ungenutzte Einträge prüfen und zwischen Katalog und Bestandsansicht wechseln.', 'themisdb-order-request'); ?></p>
                    <p>
                        <a class="button button-primary" href="#inventory-category-card"><?php _e('Zur Kategorienpflege', 'themisdb-order-request'); ?></a>
                        <a class="button" href="<?php echo esc_url(add_query_arg(array('page' => 'themisdb-inventory', 'manage_tab' => 'categories', 'cfilter' => 'unused', 'csort_by' => 'count', 'csort_dir' => 'asc'), admin_url('admin.php'))); ?>"><?php _e('Ungenutzte Kategorien', 'themisdb-order-request'); ?></a>
                        <a class="button" href="<?php echo esc_url(add_query_arg($manage_tab_query_args['items'], admin_url('admin.php'))); ?>"><?php _e('Zur Artikelansicht', 'themisdb-order-request'); ?></a>
                    </p>
                </div>
                <?php endif; ?>
            </div>

            <form method="get" class="themisdb-ajax-form" style="margin-bottom:14px; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                <input type="hidden" name="page" value="themisdb-inventory">
                <input type="hidden" name="manage_tab" value="<?php echo esc_attr($manage_tab); ?>">

                <?php if ($manage_tab === 'items'): ?>
                    <input type="hidden" name="category_tab" value="<?php echo esc_attr($category_tab); ?>">
                    <input type="hidden" name="sort_by" value="<?php echo esc_attr($sort_by); ?>">
                    <input type="hidden" name="sort_dir" value="<?php echo esc_attr($sort_dir); ?>">
                    <input type="hidden" name="paged" value="1">
                    <input type="search" name="q" value="<?php echo esc_attr($search_query); ?>" placeholder="<?php esc_attr_e('Suche nach SKU, Artikel, Kategorie ...', 'themisdb-order-request'); ?>" class="regular-text">
                    <button type="submit" class="button"><?php _e('Suchen', 'themisdb-order-request'); ?></button>
                    <?php $this->render_per_page_select('per_page', $per_page); ?>
                <?php else: ?>
                    <input type="search" name="cq" value="<?php echo esc_attr($category_manage_search); ?>" placeholder="<?php esc_attr_e('Kategorie suchen (Name oder Slug) ...', 'themisdb-order-request'); ?>" class="regular-text">
                    <select name="cfilter">
                        <option value="all" <?php selected($category_manage_filter, 'all'); ?>><?php _e('Alle Kategorien', 'themisdb-order-request'); ?></option>
                        <option value="used" <?php selected($category_manage_filter, 'used'); ?>><?php _e('Nur verwendet', 'themisdb-order-request'); ?></option>
                        <option value="unused" <?php selected($category_manage_filter, 'unused'); ?>><?php _e('Nur ungenutzt', 'themisdb-order-request'); ?></option>
                    </select>
                    <select name="csort_by">
                        <option value="name" <?php selected($category_manage_sort_by, 'name'); ?>><?php _e('Sortierung: Name', 'themisdb-order-request'); ?></option>
                        <option value="slug" <?php selected($category_manage_sort_by, 'slug'); ?>><?php _e('Sortierung: Slug', 'themisdb-order-request'); ?></option>
                        <option value="count" <?php selected($category_manage_sort_by, 'count'); ?>><?php _e('Sortierung: Elemente', 'themisdb-order-request'); ?></option>
                    </select>
                    <select name="csort_dir">
                        <option value="asc" <?php selected($category_manage_sort_dir, 'asc'); ?>><?php _e('Aufsteigend', 'themisdb-order-request'); ?></option>
                        <option value="desc" <?php selected($category_manage_sort_dir, 'desc'); ?>><?php _e('Absteigend', 'themisdb-order-request'); ?></option>
                    </select>
                    <button type="submit" class="button"><?php _e('Anwenden', 'themisdb-order-request'); ?></button>
                <?php endif; ?>
            </form>

            <?php
            if ($manage_tab === 'items') {
                $this->render_inline_help_box(
                    __('Inline-Hilfe: Lagerbestand', 'themisdb-order-request'),
                    array(
                        __('Suche filtert SKU, Artikelname und Kategorie.', 'themisdb-order-request'),
                        __('Kategorie-Buttons verengen die Liste zusätzlich, ohne den Listenstatus zu verlieren.', 'themisdb-order-request'),
                        __('Synchronisierung übernimmt den aktuellen Kontext; ∞ steht für unbegrenzten Bestand.', 'themisdb-order-request'),
                        __('Bulk gilt nur für markierte Lagerartikel.', 'themisdb-order-request'),
                    )
                );
            } else {
                $this->render_inline_help_box(
                    __('Inline-Hilfe: Lagerkategorien', 'themisdb-order-request'),
                    array(
                        __('Suche filtert Kategorien nach Name oder Slug.', 'themisdb-order-request'),
                        __('Nur verwendet und Nur ungenutzt helfen beim Aufräumen.', 'themisdb-order-request'),
                        __('Bearbeiten, Löschen und Abbrechen behalten den Filterzustand.', 'themisdb-order-request'),
                    )
                );
            }
            ?>

            <?php $this->render_bulk_notice('bulk_inventory'); ?>

            <?php if (isset($_GET['message']) && $_GET['message'] === 'saved'): ?>
            <div class="notice notice-success"><p><?php _e('Lagerbestand wurde gespeichert.', 'themisdb-order-request'); ?></p></div>
            <?php endif; ?>
            <?php if (isset($_GET['message']) && $_GET['message'] === 'save_error'): ?>
            <div class="notice notice-error"><p><?php _e('Lagerbestand konnte nicht gespeichert werden.', 'themisdb-order-request'); ?></p></div>
            <?php endif; ?>
            <?php if (isset($_GET['message']) && $_GET['message'] === 'category_saved'): ?>
            <div class="notice notice-success"><p><?php _e('Kategorie wurde gespeichert.', 'themisdb-order-request'); ?></p></div>
            <?php endif; ?>
            <?php if (isset($_GET['message']) && $_GET['message'] === 'category_deleted'): ?>
            <div class="notice notice-success"><p><?php _e('Kategorie wurde gelöscht.', 'themisdb-order-request'); ?></p></div>
            <?php endif; ?>
            <?php if (isset($_GET['message']) && $_GET['message'] === 'category_error'): ?>
            <div class="notice notice-error"><p><?php _e('Kategorie konnte nicht gespeichert werden.', 'themisdb-order-request'); ?></p></div>
            <?php endif; ?>
            <?php if (isset($_GET['message']) && $_GET['message'] === 'category_in_use'): ?>
            <div class="notice notice-warning"><p><?php _e('Kategorie wird noch von Lagerartikeln verwendet und kann nicht gelöscht werden.', 'themisdb-order-request'); ?></p></div>
            <?php endif; ?>
            <?php if (isset($_GET['message']) && $_GET['message'] === 'sync_completed'): ?>
            <div class="notice notice-success"><p><?php printf(__('Lagerbestände synchronisiert: %d Einträge aktualisiert.', 'themisdb-order-request'), absint($_GET['synced'] ?? 0)); ?></p></div>
            <?php endif; ?>
            <?php if (isset($_GET['message']) && $_GET['message'] === 'sync_with_errors'): ?>
            <div class="notice notice-warning"><p><?php printf(__('Lagerbestände teilweise synchronisiert: %d Einträge aktualisiert, %d Fehler.', 'themisdb-order-request'), absint($_GET['synced'] ?? 0), absint($_GET['errors'] ?? 0)); ?></p></div>
            <?php endif; ?>

            <?php
            $inventory_category_buttons = array(
                'all' => esc_html__('Alle Kategorien', 'themisdb-order-request') . ' (' . count($inventory_items) . ')',
            );
            foreach ($categories as $cat => $cat_count) {
                $inventory_category_buttons[$cat] = ucwords(str_replace(array('-', '_'), ' ', (string) $cat)) . ' (' . $cat_count . ')';
            }

            if ($manage_tab === 'items') {
                $this->render_filter_button_bar('category_tab', $inventory_category_buttons, $category_tab, admin_url('admin.php?page=themisdb-inventory'), array(
                    'manage_tab' => 'items',
                    'q' => $search_query,
                    'sort_by' => $sort_by,
                    'sort_dir' => $sort_dir,
                    'per_page' => $per_page,
                ));
            }
            ?>

            <div id="inventory-sync-card" class="card" style="max-width:none; margin-bottom:20px;">
                <h2><?php _e('Produkt-Lagerbestand synchronisieren', 'themisdb-order-request'); ?></h2>
                <p><?php _e('Synchronisiert alle Produkte, Lizenzen, Module und Schulungsmodule in den Lagerbestand. Alle synchronisierten Einträge erhalten eine interne UUID und werden mit unendlichem Bestand gekennzeichnet.', 'themisdb-order-request'); ?></p>
                <form method="post" style="display:inline;">
                    <?php wp_nonce_field('themisdb_sync_inventory_all'); ?>
                    <input type="hidden" name="action" value="sync_inventory_all">
                    <input type="hidden" name="manage_tab" value="<?php echo esc_attr($manage_tab); ?>">
                    <input type="hidden" name="category_tab" value="<?php echo esc_attr($category_tab); ?>">
                    <input type="hidden" name="q" value="<?php echo esc_attr($search_query); ?>">
                    <input type="hidden" name="sort_by" value="<?php echo esc_attr($sort_by); ?>">
                    <input type="hidden" name="sort_dir" value="<?php echo esc_attr($sort_dir); ?>">
                    <input type="hidden" name="per_page" value="<?php echo absint($per_page); ?>">
                    <input type="hidden" name="paged" value="<?php echo absint($paged); ?>">
                    <input type="hidden" name="cq" value="<?php echo esc_attr($category_manage_search); ?>">
                    <input type="hidden" name="cfilter" value="<?php echo esc_attr($category_manage_filter); ?>">
                    <input type="hidden" name="csort_by" value="<?php echo esc_attr($category_manage_sort_by); ?>">
                    <input type="hidden" name="csort_dir" value="<?php echo esc_attr($category_manage_sort_dir); ?>">
                    <button type="submit" class="button button-primary"><?php _e('Jetzt synchronisieren', 'themisdb-order-request'); ?></button>
                    <small><?php _e('Dies beseitigt Duplikate und weist jedem Element eine eindeutige UUID zu.', 'themisdb-order-request'); ?></small>
                </form>
            </div>
            
            <?php if ($manage_tab === 'items'): ?>

            <div class="card" style="max-width:none; margin-bottom:20px;">
                <h2><?php echo $edit_item ? __('Lagerartikel bearbeiten', 'themisdb-order-request') : __('Bestand anlegen oder aktualisieren', 'themisdb-order-request'); ?></h2>
                <form method="post">
                    <?php wp_nonce_field('themisdb_save_inventory'); ?>
                    <input type="hidden" name="action" value="save_inventory">
                    <input type="hidden" name="item_id" value="<?php echo $edit_item ? absint($edit_item['id']) : 0; ?>">
                    <table class="form-table">
                        <tr>
                            <th><label for="product_id"><?php _e('Produkt', 'themisdb-order-request'); ?></label></th>
                            <td>
                                <select id="inventory_product_id" name="product_id">
                                    <option value=""><?php _e('— Optional verknüpfen —', 'themisdb-order-request'); ?></option>
                                    <?php foreach ($products as $product): ?>
                                    <option
                                        value="<?php echo absint($product['id']); ?>"
                                        data-sku="<?php echo esc_attr($product['product_code']); ?>"
                                        data-name="<?php echo esc_attr($product['product_name']); ?>"
                                        data-category="<?php echo esc_attr(sanitize_title($product['product_type'])); ?>"
                                        <?php selected($edit_item['product_id'] ?? 0, $product['id']); ?>
                                    ><?php echo esc_html($product['product_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="sku"><?php _e('SKU', 'themisdb-order-request'); ?></label></th>
                            <td><input type="text" id="inventory_sku" name="sku" class="regular-text" value="<?php echo esc_attr($edit_item['sku'] ?? ''); ?>" required></td>
                        </tr>
                        <tr>
                            <th><label for="product_name"><?php _e('Artikelname', 'themisdb-order-request'); ?></label></th>
                            <td><input type="text" id="inventory_product_name" name="product_name" class="regular-text" value="<?php echo esc_attr($edit_item['product_name'] ?? ''); ?>" required></td>
                        </tr>
                        <tr>
                            <th><label for="inventory_category_slug"><?php _e('Kategorie', 'themisdb-order-request'); ?></label></th>
                            <td><input type="text" id="inventory_category_slug" name="category_slug" class="regular-text" value="<?php echo esc_attr($edit_item['category_slug'] ?? ''); ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="stock_on_hand"><?php _e('Bestand', 'themisdb-order-request'); ?></label></th>
                            <td><input type="number" id="stock_on_hand" name="stock_on_hand" value="<?php echo esc_attr($edit_item['stock_on_hand'] ?? 0); ?>" min="0"></td>
                        </tr>
                        <tr>
                            <th><label for="reorder_level"><?php _e('Meldebestand', 'themisdb-order-request'); ?></label></th>
                            <td><input type="number" id="reorder_level" name="reorder_level" value="<?php echo esc_attr($edit_item['reorder_level'] ?? 0); ?>" min="0"></td>
                        </tr>
                        <tr>
                            <th><?php _e('Aktiv', 'themisdb-order-request'); ?></th>
                            <td><label><input type="checkbox" name="is_active" value="1" <?php checked(isset($edit_item['is_active']) ? intval($edit_item['is_active']) : 1, 1); ?>> <?php _e('Datensatz aktiv', 'themisdb-order-request'); ?></label></td>
                        </tr>
                    </table>
                    <p>
                        <button type="submit" class="button button-primary"><?php _e('Bestand speichern', 'themisdb-order-request'); ?></button>
                        <input type="hidden" name="category_tab" value="<?php echo esc_attr($category_tab); ?>">
                        <input type="hidden" name="q" value="<?php echo esc_attr($search_query); ?>">
                        <input type="hidden" name="sort_by" value="<?php echo esc_attr($sort_by); ?>">
                        <input type="hidden" name="sort_dir" value="<?php echo esc_attr($sort_dir); ?>">
                        <input type="hidden" name="per_page" value="<?php echo absint($per_page); ?>">
                        <input type="hidden" name="paged" value="<?php echo absint($paged); ?>">
                        <?php if ($edit_item): ?>
                        <a href="<?php echo esc_url(add_query_arg(array('page' => 'themisdb-inventory', 'manage_tab' => 'items', 'category_tab' => $category_tab, 'q' => $search_query, 'sort_by' => $sort_by, 'sort_dir' => $sort_dir, 'per_page' => $per_page, 'paged' => $paged), admin_url('admin.php'))); ?>" class="button"><?php _e('Abbrechen', 'themisdb-order-request'); ?></a>
                        <?php endif; ?>
                    </p>
                </form>
            </div>

            <?php endif; // end if manage_tab === 'items' ?>

            <?php if ($manage_tab === 'categories'): ?>

            <div id="inventory-category-card" class="card" style="max-width:none; margin-bottom:20px;">
                <h2><?php _e('Kategorien verwalten', 'themisdb-order-request'); ?></h2>
                <form method="post" style="margin-bottom:12px;">
                    <?php wp_nonce_field('themisdb_inventory_save_category'); ?>
                    <input type="hidden" name="action" value="inventory_save_category">
                    <input type="hidden" name="old_slug" value="<?php echo esc_attr($edit_category_slug); ?>">
                    <input type="hidden" name="cq" value="<?php echo esc_attr($category_manage_search); ?>">
                    <input type="hidden" name="cfilter" value="<?php echo esc_attr($category_manage_filter); ?>">
                    <input type="hidden" name="csort_by" value="<?php echo esc_attr($category_manage_sort_by); ?>">
                    <input type="hidden" name="csort_dir" value="<?php echo esc_attr($category_manage_sort_dir); ?>">
                    <table class="form-table">
                        <tr><th><label for="inventory_category_name"><?php _e('Name', 'themisdb-order-request'); ?></label></th><td><input id="inventory_category_name" name="category_name" class="regular-text" value="<?php echo esc_attr($edit_category_label); ?>" required></td></tr>
                        <tr><th><label for="inventory_category_slug_edit"><?php _e('Slug', 'themisdb-order-request'); ?></label></th><td><input id="inventory_category_slug_edit" name="category_slug" class="regular-text" value="<?php echo esc_attr($edit_category_slug); ?>"></td></tr>
                    </table>
                    <p>
                        <button type="submit" class="button button-secondary"><?php echo $edit_category_slug !== '' ? esc_html__('Kategorie aktualisieren', 'themisdb-order-request') : esc_html__('Kategorie anlegen', 'themisdb-order-request'); ?></button>
                        <?php if ($edit_category_slug !== ''): ?>
                        <a class="button" href="<?php echo esc_url(add_query_arg(array('page' => 'themisdb-inventory', 'manage_tab' => 'categories', 'cq' => $category_manage_search, 'cfilter' => $category_manage_filter, 'csort_by' => $category_manage_sort_by, 'csort_dir' => $category_manage_sort_dir), admin_url('admin.php'))); ?>"><?php _e('Abbrechen', 'themisdb-order-request'); ?></a>
                        <?php endif; ?>
                    </p>
                </form>

                <table class="wp-list-table widefat striped">
                    <thead><tr><th><?php _e('Name', 'themisdb-order-request'); ?></th><th><?php _e('Slug', 'themisdb-order-request'); ?></th><th><?php _e('Elemente', 'themisdb-order-request'); ?></th><th><?php _e('Aktionen', 'themisdb-order-request'); ?></th></tr></thead>
                    <tbody>
                        <?php if (empty($managed_category_rows)): ?>
                        <tr><td colspan="4"><?php _e('Keine verwalteten Kategorien vorhanden.', 'themisdb-order-request'); ?></td></tr>
                        <?php else: ?>
                            <?php foreach ($managed_category_rows as $category_row): ?>
                            <?php $cat_slug = $category_row['slug']; $cat_label = $category_row['label']; ?>
                            <tr>
                                <td><?php echo esc_html($cat_label); ?></td>
                                <td><code><?php echo esc_html($cat_slug); ?></code></td>
                                <td><?php echo intval($category_row['count']); ?></td>
                                <td>
                                    <a class="button button-small" href="<?php echo esc_url(add_query_arg(array('page' => 'themisdb-inventory', 'manage_tab' => 'categories', 'edit_category' => $cat_slug, 'cq' => $category_manage_search, 'cfilter' => $category_manage_filter, 'csort_by' => $category_manage_sort_by, 'csort_dir' => $category_manage_sort_dir), admin_url('admin.php'))); ?>"><?php _e('Bearbeiten', 'themisdb-order-request'); ?></a>
                                    <form method="post" style="display:inline;" onsubmit="return confirm('<?php _e('Kategorie wirklich löschen?', 'themisdb-order-request'); ?>');">
                                        <?php wp_nonce_field('themisdb_inventory_delete_category'); ?>
                                        <input type="hidden" name="action" value="inventory_delete_category">
                                        <input type="hidden" name="category_slug" value="<?php echo esc_attr($cat_slug); ?>">
                                        <input type="hidden" name="cq" value="<?php echo esc_attr($category_manage_search); ?>">
                                        <input type="hidden" name="cfilter" value="<?php echo esc_attr($category_manage_filter); ?>">
                                        <input type="hidden" name="csort_by" value="<?php echo esc_attr($category_manage_sort_by); ?>">
                                        <input type="hidden" name="csort_dir" value="<?php echo esc_attr($category_manage_sort_dir); ?>">
                                        <button type="submit" class="button button-small button-link-delete"><?php _e('Löschen', 'themisdb-order-request'); ?></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php endif; // end if manage_tab === 'categories' ?>

            <?php if ($manage_tab === 'items'): ?>

            <div class="card themisdb-list-root" id="themisdb-inventory-list-root" style="max-width:none;">
                <h2><?php _e('Aktueller Lagerbestand', 'themisdb-order-request'); ?></h2>

                <form method="post">
                    <?php wp_nonce_field('themisdb_inventory_bulk_items'); ?>
                    <input type="hidden" name="action" value="inventory_bulk_items">
                    <input type="hidden" name="category_tab" value="<?php echo esc_attr($category_tab); ?>">
                    <input type="hidden" name="q" value="<?php echo esc_attr($search_query); ?>">
                    <input type="hidden" name="sort_by" value="<?php echo esc_attr($sort_by); ?>">
                    <input type="hidden" name="sort_dir" value="<?php echo esc_attr($sort_dir); ?>">
                    <input type="hidden" name="per_page" value="<?php echo absint($per_page); ?>">
                    <input type="hidden" name="paged" value="<?php echo absint($paged); ?>">
                    <?php $this->render_bulk_action_bar('inventory_bulk_action', $bulk_actions, __('Ausgewählte Lagerartikel verarbeiten', 'themisdb-order-request')); ?>

                    <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td class="check-column"><input type="checkbox" class="themisdb-bulk-toggle" aria-label="<?php esc_attr_e('Alle Lagerartikel auswählen', 'themisdb-order-request'); ?>"></td>
                            <th><?php $this->render_sortable_column(__('SKU', 'themisdb-order-request'), 'sku', $sort_by, $sort_dir, admin_url('admin.php?page=themisdb-inventory'), array('manage_tab' => 'items', 'category_tab' => $category_tab, 'q' => $search_query, 'per_page' => $per_page)); ?></th>
                            <th><?php $this->render_sortable_column(__('Artikel', 'themisdb-order-request'), 'name', $sort_by, $sort_dir, admin_url('admin.php?page=themisdb-inventory'), array('manage_tab' => 'items', 'category_tab' => $category_tab, 'q' => $search_query, 'per_page' => $per_page)); ?></th>
                            <th><?php $this->render_sortable_column(__('Kategorie', 'themisdb-order-request'), 'category', $sort_by, $sort_dir, admin_url('admin.php?page=themisdb-inventory'), array('manage_tab' => 'items', 'category_tab' => $category_tab, 'q' => $search_query, 'per_page' => $per_page)); ?></th>
                            <th><?php $this->render_sortable_column(__('Bestand', 'themisdb-order-request'), 'stock', $sort_by, $sort_dir, admin_url('admin.php?page=themisdb-inventory'), array('manage_tab' => 'items', 'category_tab' => $category_tab, 'q' => $search_query, 'per_page' => $per_page)); ?></th>
                            <th><?php $this->render_sortable_column(__('Reserviert', 'themisdb-order-request'), 'reserved', $sort_by, $sort_dir, admin_url('admin.php?page=themisdb-inventory'), array('manage_tab' => 'items', 'category_tab' => $category_tab, 'q' => $search_query, 'per_page' => $per_page)); ?></th>
                            <th><?php _e('Verfügbar', 'themisdb-order-request'); ?></th>
                            <th><?php $this->render_sortable_column(__('Meldebestand', 'themisdb-order-request'), 'reorder', $sort_by, $sort_dir, admin_url('admin.php?page=themisdb-inventory'), array('manage_tab' => 'items', 'category_tab' => $category_tab, 'q' => $search_query, 'per_page' => $per_page)); ?></th>
                            <th><?php $this->render_sortable_column(__('Status', 'themisdb-order-request'), 'status', $sort_by, $sort_dir, admin_url('admin.php?page=themisdb-inventory'), array('manage_tab' => 'items', 'category_tab' => $category_tab, 'q' => $search_query, 'per_page' => $per_page)); ?></th>
                            <th><?php _e('Aktionen', 'themisdb-order-request'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($filtered_inventory)): ?>
                        <tr>
                            <td colspan="10"><?php _e('Noch keine Lagerartikel vorhanden.', 'themisdb-order-request'); ?></td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($filtered_inventory as $item): ?>
                            <?php 
                                $stock_on_hand = intval($item['stock_on_hand']);
                                $reserved = intval($item['reserved_stock']);
                                $is_infinite = $stock_on_hand === -1;
                                $available = $is_infinite ? -1 : ($stock_on_hand - $reserved);
                            ?>
                            <tr>
                                <th scope="row" class="check-column"><input type="checkbox" name="item_ids[]" value="<?php echo absint($item['id']); ?>"></th>
                                <td><?php echo esc_html($item['sku']); ?></td>
                                <td><?php echo esc_html($item['product_name']); ?></td>
                                <td><?php echo esc_html($item['category_slug'] ?? ''); ?></td>
                                <td><?php echo ($is_infinite) ? '<strong style="font-size:1.2em; color:#2271b1;">∞</strong> ' . __('Unbegrenzt', 'themisdb-order-request') : esc_html($stock_on_hand); ?></td>
                                <td><?php echo esc_html($reserved); ?></td>
                                <td><?php echo ($is_infinite) ? '<strong style="font-size:1.2em; color:#2271b1;">∞</strong> ' . __('Unbegrenzt', 'themisdb-order-request') : esc_html($available); ?></td>
                                <td>
                                    <?php if ($available <= intval($item['reorder_level'])): ?>
                                        <span style="color:#b32d2e;font-weight:bold;"><?php echo intval($item['reorder_level']); ?></span>
                                    <?php else: ?>
                                        <?php echo intval($item['reorder_level']); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo !empty($item['is_active']) ? __('Aktiv', 'themisdb-order-request') : __('Inaktiv', 'themisdb-order-request'); ?></td>
                                <td>
                                    <a class="button button-small" href="<?php echo esc_url(add_query_arg(array('page' => 'themisdb-inventory', 'manage_tab' => 'items', 'category_tab' => $category_tab, 'q' => $search_query, 'sort_by' => $sort_by, 'sort_dir' => $sort_dir, 'per_page' => $per_page, 'paged' => $paged, 'edit_id' => absint($item['id'])), admin_url('admin.php'))); ?>"><?php _e('Bearbeiten', 'themisdb-order-request'); ?></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                </form>

                <?php
                $this->render_simple_pagination(
                    admin_url('admin.php?page=themisdb-inventory'),
                    $pagination['paged'],
                    $pagination['total_pages'],
                    array(
                        'manage_tab' => 'items',
                        'category_tab' => $category_tab,
                        'q' => $search_query,
                        'sort_by' => $sort_by,
                        'sort_dir' => $sort_dir,
                        'per_page' => $per_page,
                    )
                );
                ?>

                <?php $this->render_bulk_table_script(); ?>
            </div>

            <?php endif; // end if manage_tab === 'items' ?>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            var select = document.getElementById('inventory_product_id');
            if (!select) {
                return;
            }

            select.addEventListener('change', function () {
                var option = select.options[select.selectedIndex];
                if (!option) {
                    return;
                }

                var skuInput = document.getElementById('inventory_sku');
                var nameInput = document.getElementById('inventory_product_name');
                var categoryInput = document.getElementById('inventory_category_slug');

                if (option.dataset.sku && skuInput && !skuInput.value) {
                    skuInput.value = option.dataset.sku;
                }

                if (option.dataset.name && nameInput && !nameInput.value) {
                    nameInput.value = option.dataset.name;
                }

                if (option.dataset.category && categoryInput && !categoryInput.value) {
                    categoryInput.value = option.dataset.category;
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Handle bulk actions for catalog rows.
     */
    private function handle_catalog_bulk_items() {
        $action_name = sanitize_text_field($_POST['catalog_bulk_action'] ?? '');
        $entity = sanitize_text_field($_POST['entity'] ?? 'product');
        $category_tab = sanitize_text_field($_POST['category_tab'] ?? 'all');
        $manage_tab = sanitize_text_field($_POST['manage_tab'] ?? 'items');
        if (!in_array($manage_tab, array('categories', 'items'), true)) {
            $manage_tab = 'items';
        }
        $search_query = sanitize_text_field($_POST['q'] ?? '');
        $sort_by = sanitize_key($_POST['sort_by'] ?? 'code');
        $sort_dir = sanitize_key($_POST['sort_dir'] ?? 'asc');
        if (!in_array($sort_dir, array('asc', 'desc'), true)) {
            $sort_dir = 'asc';
        }
        $per_page = $this->resolve_per_page($_POST['per_page'] ?? 25, 25);
        $paged = max(1, absint($_POST['paged'] ?? 1));
        $item_ids = isset($_POST['item_ids']) ? array_map('absint', (array) $_POST['item_ids']) : array();

        if ($action_name === '' || empty($item_ids)) {
            wp_redirect(add_query_arg(array(
                'page' => 'themisdb-products',
                'entity_tab' => $entity,
                'manage_tab' => $manage_tab,
                'category_tab' => $category_tab,
                'q' => $search_query,
                'sort_by' => $sort_by,
                'sort_dir' => $sort_dir,
                'per_page' => $per_page,
                'paged' => $paged,
                'bulk_products' => rawurlencode(base64_encode(wp_json_encode(array(
                    'success' => false,
                    'message' => __('Keine Katalogdatensätze oder Aktion ausgewählt.', 'themisdb-order-request'),
                    'processed' => 0,
                    'failed' => 0,
                )))),
            ), admin_url('admin.php')));
            exit;
        }

        $processed = 0;
        $failed = 0;

        foreach (array_unique($item_ids) as $item_id) {
            if ($action_name === 'activate') {
                $success = ThemisDB_Order_Manager::set_catalog_item_active($entity, $item_id, 1);
            } elseif ($action_name === 'deactivate') {
                $success = ThemisDB_Order_Manager::set_catalog_item_active($entity, $item_id, 0);
            } else {
                $success = ThemisDB_Order_Manager::deactivate_catalog_item($entity, $item_id);
            }

            if ($success) {
                $processed++;
            } else {
                $failed++;
            }
        }

        wp_redirect(add_query_arg(array(
            'page' => 'themisdb-products',
            'entity_tab' => $entity,
            'manage_tab' => $manage_tab,
            'category_tab' => $category_tab,
            'q' => $search_query,
            'sort_by' => $sort_by,
            'sort_dir' => $sort_dir,
            'per_page' => $per_page,
            'paged' => $paged,
            'bulk_products' => rawurlencode(base64_encode(wp_json_encode(array(
                'success' => $failed === 0,
                'message' => __('Katalog-Sammelaktion abgeschlossen.', 'themisdb-order-request'),
                'processed' => $processed,
                'failed' => $failed,
            )))),
        ), admin_url('admin.php')));
        exit;
    }

    /**
     * Handle bulk actions for inventory rows.
     */
    private function handle_inventory_bulk_items() {
        $action_name = sanitize_text_field($_POST['inventory_bulk_action'] ?? '');
        $item_ids = isset($_POST['item_ids']) ? array_map('absint', (array) $_POST['item_ids']) : array();
        $category_tab = sanitize_text_field($_POST['category_tab'] ?? 'all');
        $search_query = sanitize_text_field($_POST['q'] ?? '');
        $sort_by = sanitize_key($_POST['sort_by'] ?? 'sku');
        $sort_dir = sanitize_key($_POST['sort_dir'] ?? 'asc');
        if (!in_array($sort_dir, array('asc', 'desc'), true)) {
            $sort_dir = 'asc';
        }
        $per_page = $this->resolve_per_page($_POST['per_page'] ?? 25, 25);
        $paged = max(1, absint($_POST['paged'] ?? 1));

        if ($action_name === '' || empty($item_ids)) {
            wp_redirect(add_query_arg(array(
                'page' => 'themisdb-inventory',
                'manage_tab' => 'items',
                'category_tab' => $category_tab,
                'q' => $search_query,
                'sort_by' => $sort_by,
                'sort_dir' => $sort_dir,
                'per_page' => $per_page,
                'paged' => $paged,
                'bulk_inventory' => rawurlencode(base64_encode(wp_json_encode(array(
                    'success' => false,
                    'message' => __('Keine Lagerdatensätze oder Aktion ausgewählt.', 'themisdb-order-request'),
                    'processed' => 0,
                    'failed' => 0,
                )))),
            ), admin_url('admin.php')));
            exit;
        }

        $processed = 0;
        $failed = 0;

        foreach (array_unique($item_ids) as $item_id) {
            if ($action_name === 'activate') {
                $success = ThemisDB_Order_Manager::set_inventory_item_active($item_id, 1);
            } elseif ($action_name === 'deactivate') {
                $success = ThemisDB_Order_Manager::set_inventory_item_active($item_id, 0);
            } else {
                $success = ThemisDB_Order_Manager::deactivate_inventory_item($item_id);
            }

            if ($success) {
                $processed++;
            } else {
                $failed++;
            }
        }

        wp_redirect(add_query_arg(array(
            'page' => 'themisdb-inventory',
            'manage_tab' => 'items',
            'category_tab' => $category_tab,
            'q' => $search_query,
            'sort_by' => $sort_by,
            'sort_dir' => $sort_dir,
            'per_page' => $per_page,
            'paged' => $paged,
            'bulk_inventory' => rawurlencode(base64_encode(wp_json_encode(array(
                'success' => $failed === 0,
                'message' => __('Lager-Sammelaktion abgeschlossen.', 'themisdb-order-request'),
                'processed' => $processed,
                'failed' => $failed,
            )))),
        ), admin_url('admin.php')));
        exit;
    }

    /**
     * Get managed catalog categories for one entity type.
     */
    private function get_catalog_categories($entity) {
        $all = get_option($this->get_catalog_category_option_key($entity), array());
        $entity = in_array($entity, array('product', 'module', 'training'), true) ? $entity : 'product';

        if (!is_array($all)) {
            return array();
        }

        return $all;
    }

    /**
     * Save or update one managed catalog category.
     */
    private function save_catalog_category($entity, $name, $slug = '', $old_slug = '') {
        $entity = in_array($entity, array('product', 'module', 'training'), true) ? $entity : 'product';
        $name = trim($name);
        $slug = $slug !== '' ? sanitize_title($slug) : sanitize_title($name);
        $old_slug = sanitize_title($old_slug);

        if ($name === '' || $slug === '') {
            return false;
        }

        $all = $this->get_catalog_categories($entity);

        if ($old_slug !== '' && $old_slug !== $slug && isset($all[$old_slug])) {
            unset($all[$old_slug]);
            $this->migrate_catalog_category_slug($entity, $old_slug, $slug);
        }

        $all[$slug] = $name;
        ksort($all);

        return update_option($this->get_catalog_category_option_key($entity), $all, false);
    }

    /**
     * Delete one managed catalog category.
     */
    private function delete_catalog_category($entity, $slug) {
        $entity = in_array($entity, array('product', 'module', 'training'), true) ? $entity : 'product';
        $slug = sanitize_title($slug);
        if ($slug === '') {
            return false;
        }

        $all = $this->get_catalog_categories($entity);
        if (!isset($all[$slug])) {
            return false;
        }

        if ($this->count_catalog_category_usage($entity, $slug) > 0) {
            return 'in_use';
        }

        unset($all[$slug]);
        return update_option($this->get_catalog_category_option_key($entity), $all, false);
    }

    /**
     * Get managed inventory categories.
     */
    private function get_inventory_categories() {
        $categories = get_option('themisdb_inventory_categories', array());
        return is_array($categories) ? $categories : array();
    }

    /**
     * Save or update one managed inventory category.
     */
    private function save_inventory_category($name, $slug = '', $old_slug = '') {
        $name = trim($name);
        $slug = $slug !== '' ? sanitize_title($slug) : sanitize_title($name);
        $old_slug = sanitize_title($old_slug);

        if ($name === '' || $slug === '') {
            return false;
        }

        $categories = $this->get_inventory_categories();

        if ($old_slug !== '' && $old_slug !== $slug && isset($categories[$old_slug])) {
            unset($categories[$old_slug]);
            $this->migrate_inventory_category_slug($old_slug, $slug);
        }

        $categories[$slug] = $name;
        ksort($categories);

        return update_option('themisdb_inventory_categories', $categories, false);
    }

    /**
     * Delete one managed inventory category.
     */
    private function delete_inventory_category($slug) {
        $slug = sanitize_title($slug);
        if ($slug === '') {
            return false;
        }

        $categories = $this->get_inventory_categories();
        if (!isset($categories[$slug])) {
            return false;
        }

        if ($this->count_inventory_category_usage($slug) > 0) {
            return 'in_use';
        }

        unset($categories[$slug]);
        return update_option('themisdb_inventory_categories', $categories, false);
    }

    /**
     * Option key for managed catalog categories per entity.
     */
    private function get_catalog_category_option_key($entity) {
        $entity = in_array($entity, array('product', 'module', 'training'), true) ? $entity : 'product';
        return 'themisdb_catalog_categories_' . $entity;
    }

    /**
     * Count catalog records that use one category slug.
     */
    private function count_catalog_category_usage($entity, $slug) {
        $slug = sanitize_title($slug);

        if ($entity === 'product') {
            $items = ThemisDB_Order_Manager::get_products(true);
            return count(array_filter($items, function ($item) use ($slug) {
                return sanitize_title($item['product_type'] ?? '') === $slug;
            }));
        }

        if ($entity === 'module') {
            $items = ThemisDB_Order_Manager::get_modules(null, true);
            return count(array_filter($items, function ($item) use ($slug) {
                return sanitize_title($item['module_category'] ?? '') === $slug;
            }));
        }

        $items = ThemisDB_Order_Manager::get_training_modules(null, true);
        return count(array_filter($items, function ($item) use ($slug) {
            return sanitize_title($item['training_type'] ?? '') === $slug;
        }));
    }

    /**
     * Update stored catalog rows when a category slug is renamed.
     */
    private function migrate_catalog_category_slug($entity, $old_slug, $new_slug) {
        global $wpdb;

        $old_slug = sanitize_title($old_slug);
        $new_slug = sanitize_title($new_slug);

        if ($old_slug === '' || $new_slug === '' || $old_slug === $new_slug) {
            return;
        }

        if ($entity === 'product') {
            $wpdb->update($wpdb->prefix . 'themisdb_products', array('product_type' => $new_slug), array('product_type' => $old_slug));
            return;
        }

        if ($entity === 'module') {
            $wpdb->update($wpdb->prefix . 'themisdb_modules', array('module_category' => $new_slug), array('module_category' => $old_slug));
            return;
        }

        $wpdb->update($wpdb->prefix . 'themisdb_training_modules', array('training_type' => $new_slug), array('training_type' => $old_slug));
    }

    /**
     * Count inventory records that use one category slug.
     */
    private function count_inventory_category_usage($slug) {
        $slug = sanitize_title($slug);
        $items = ThemisDB_Order_Manager::get_inventory_items(true);

        return count(array_filter($items, function ($item) use ($slug) {
            return sanitize_title($item['category_slug'] ?? '') === $slug;
        }));
    }

    /**
     * Update stored inventory rows when a category slug is renamed.
     */
    private function migrate_inventory_category_slug($old_slug, $new_slug) {
        $old_slug = sanitize_title($old_slug);
        $new_slug = sanitize_title($new_slug);

        if ($old_slug === '' || $new_slug === '' || $old_slug === $new_slug) {
            return;
        }

        $items = ThemisDB_Order_Manager::get_inventory_items(true);

        foreach ($items as $item) {
            if (sanitize_title($item['category_slug'] ?? '') !== $old_slug) {
                continue;
            }

            ThemisDB_Order_Manager::save_inventory_item(array(
                'id' => intval($item['id']),
                'sku' => $item['sku'],
                'product_name' => $item['product_name'],
                'stock_on_hand' => intval($item['stock_on_hand']),
                'product_id' => intval($item['product_id'] ?? 0),
                'reorder_level' => intval($item['reorder_level']),
                'category_slug' => $new_slug,
                'is_active' => !empty($item['is_active']) ? 1 : 0,
            ), intval($item['id']));
        }
    }
    
    /**
     * Payments page
     */
    public function payments_page() {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $payment_id = isset($_GET['payment_id']) ? intval($_GET['payment_id']) : 0;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $post_action = sanitize_text_field($_POST['action']);

            if ($post_action === 'save_payment' && isset($_POST['payment_id'])) {
                check_admin_referer('themisdb_save_payment');
                $this->handle_save_payment(absint($_POST['payment_id']));
                return;
            }

            if ($post_action === 'delete_payment' && isset($_POST['payment_id'])) {
                check_admin_referer('themisdb_delete_payment');
                $this->handle_delete_payment(absint($_POST['payment_id']));
                return;
            }

            if ($post_action === 'bulk_payment_workflow') {
                check_admin_referer('themisdb_bulk_payment_workflow');
                $this->handle_bulk_payment_workflow();
                return;
            }
        }
        
        // Handle payment verification
        if ($action === 'verify' && $payment_id && check_admin_referer('verify_payment_' . $payment_id)) {
            ThemisDB_Payment_Manager::verify_payment($payment_id);
            wp_redirect(admin_url('admin.php?page=themisdb-payments&verified=1'));
            exit;
        }
        
        if ($action === 'new') {
            $this->create_payment();
        } elseif ($action === 'edit' && $payment_id) {
            $this->edit_payment($payment_id);
        } elseif ($action === 'view' && $payment_id) {
            $this->view_payment($payment_id);
        } else {
            $this->list_payments();
        }
    }
    
    /**
     * List payments
     */
    private function list_payments() {
        global $wpdb;

        $prefs = $this->get_list_preferences('payments');
        $status_tab = isset($_GET['status_tab']) ? sanitize_text_field($_GET['status_tab']) : ($prefs['status_tab'] ?? 'all');
        $search_query = sanitize_text_field($_GET['q'] ?? '');
        $sort_by = sanitize_key($_GET['sort_by'] ?? ($prefs['sort_by'] ?? 'date'));
        $sort_dir = sanitize_key($_GET['sort_dir'] ?? ($prefs['sort_dir'] ?? 'desc'));
        $per_page = $this->resolve_per_page($_GET['per_page'] ?? ($prefs['per_page'] ?? 25), 25);
        $paged = max(1, absint($_GET['paged'] ?? 1));
        $payment_args = array('limit' => 1000, 'offset' => 0);

        if ($status_tab !== 'all') {
            $payment_args['status'] = $status_tab;
        }

        $payments = ThemisDB_Payment_Manager::get_all_payments($payment_args);

        if ($search_query !== '') {
            $payments = $this->filter_rows_by_search($payments, $search_query, array('payment_number', 'payment_method', 'payment_status', 'transaction_id', 'currency'));
        }

        $sort_map = array(
            'number' => 'payment_number',
            'amount' => 'amount',
            'method' => 'payment_method',
            'status' => 'payment_status',
            'date' => 'created_at',
        );
        $sort_by = array_key_exists($sort_by, $sort_map) ? $sort_by : 'date';
        $sort_dir = in_array($sort_dir, array('asc', 'desc'), true) ? $sort_dir : 'desc';
        $this->save_list_preferences('payments', array(
            'status_tab' => $status_tab,
            'sort_by' => $sort_by,
            'sort_dir' => $sort_dir,
            'per_page' => $per_page,
        ));
        $payments = $this->sort_rows($payments, $sort_map[$sort_by], $sort_dir, array('amount'), array('created_at'));

        $pagination = $this->paginate_rows($payments, $per_page, $paged);
        $payments = $pagination['items'];

        $stats = ThemisDB_Payment_Manager::get_payment_stats();

        $table_payments = $wpdb->prefix . 'themisdb_payments';
        if (!preg_match('/^[A-Za-z0-9_]+$/', $table_payments)) {
            $status_counts_raw = array();
        } else {
            $table_payments_sql = '`' . $table_payments . '`';
            $status_counts_raw = $wpdb->get_results("SELECT payment_status, COUNT(*) AS total FROM {$table_payments_sql} GROUP BY payment_status", ARRAY_A);
        }
        $status_counts = array(
            'pending' => 0,
            'verified' => 0,
            'overdue' => 0,
            'failed' => 0,
        );

        foreach ($status_counts_raw as $row) {
            $key = sanitize_text_field($row['payment_status']);
            if (isset($status_counts[$key])) {
                $status_counts[$key] = intval($row['total']);
            }
        }

        $bulk_actions = array(
            'verify' => __('Verifizieren', 'themisdb-order-request'),
            'overdue' => __('Als überfällig markieren', 'themisdb-order-request'),
            'failed' => __('Als fehlgeschlagen markieren', 'themisdb-order-request'),
            'delete' => __('Löschen', 'themisdb-order-request'),
        );
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Zahlungen', 'themisdb-order-request'); ?></h1>
            <a class="page-title-action" href="<?php echo esc_url(admin_url('admin.php?page=themisdb-payments&action=new')); ?>"><?php _e('Neue Zahlung', 'themisdb-order-request'); ?></a>
            <a class="page-title-action" href="<?php echo esc_url(admin_url('admin.php?page=themisdb-bank-import')); ?>"><?php _e('Bankimport', 'themisdb-order-request'); ?></a>
            <a class="page-title-action" href="<?php echo esc_url(admin_url('admin.php?page=themisdb-order-dashboard')); ?>"><?php _e('Zum Dashboard', 'themisdb-order-request'); ?></a>
            <hr class="wp-header-end">
            <?php $this->render_module_navigation_tabs('themisdb-payments'); ?>
            
            <?php if (isset($_GET['verified'])): ?>
            <div class="notice notice-success"><p><?php _e('Zahlung wurde erfolgreich verifiziert', 'themisdb-order-request'); ?></p></div>
            <?php endif; ?>

            <?php if (isset($_GET['message']) && $_GET['message'] === 'saved'): ?>
            <div class="notice notice-success"><p><?php _e('Zahlung wurde gespeichert.', 'themisdb-order-request'); ?></p></div>
            <?php endif; ?>

            <?php if (isset($_GET['message']) && $_GET['message'] === 'deleted'): ?>
            <div class="notice notice-success"><p><?php _e('Zahlung wurde gelöscht.', 'themisdb-order-request'); ?></p></div>
            <?php endif; ?>

            <?php $this->render_bulk_notice('bulk_payments'); ?>

            <?php
            $tab_base = admin_url('admin.php?page=themisdb-payments');
            $payment_tabs = array(
                'all' => __('Alle', 'themisdb-order-request'),
                'pending' => __('Ausstehend', 'themisdb-order-request'),
                'verified' => __('Verifiziert', 'themisdb-order-request'),
                'overdue' => __('Überfällig', 'themisdb-order-request'),
                'failed' => __('Fehlgeschlagen', 'themisdb-order-request'),
            );
            ?>
            <?php
            $payment_buttons = array();
            foreach ($payment_tabs as $tab_key => $tab_label) {
                $count = $tab_key === 'all'
                    ? intval($stats['total_payments'])
                    : intval($status_counts[$tab_key] ?? 0);
                $payment_buttons[$tab_key] = $tab_label . ' (' . $count . ')';
            }
            $this->render_filter_button_bar('status_tab', $payment_buttons, $status_tab, $tab_base, array(
                'q' => $search_query,
                'sort_by' => $sort_by,
                'sort_dir' => $sort_dir,
                'per_page' => $per_page,
            ));
            ?>

            <form method="get" class="themisdb-ajax-form" style="margin:8px 0 12px; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                <input type="hidden" name="page" value="themisdb-payments">
                <input type="hidden" name="status_tab" value="<?php echo esc_attr($status_tab); ?>">
                <input type="hidden" name="sort_by" value="<?php echo esc_attr($sort_by); ?>">
                <input type="hidden" name="sort_dir" value="<?php echo esc_attr($sort_dir); ?>">
                <input type="hidden" name="paged" value="1">
                <input type="search" name="q" value="<?php echo esc_attr($search_query); ?>" placeholder="<?php esc_attr_e('Suche nach Zahlungsnummer, Methode ...', 'themisdb-order-request'); ?>" class="regular-text">
                <button type="submit" class="button"><?php _e('Suchen', 'themisdb-order-request'); ?></button>
                <?php $this->render_per_page_select('per_page', $per_page); ?>
            </form>

            <?php $this->render_inline_help_box(
                __('Inline-Hilfe: Zahlungen', 'themisdb-order-request'),
                array(
                    __('Status-Tabs filtern die Liste, die Suche verfeinert zusätzlich.', 'themisdb-order-request'),
                    __('Die Übersichtskarte zeigt Summen, die Tabelle darunter die einzelnen Vorgänge.', 'themisdb-order-request'),
                    __('Bulk-Workflows gelten nur für markierte Zahlungen.', 'themisdb-order-request'),
                )
            ); ?>

            <div class="themisdb-admin-modules" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;margin:20px 0;">
                <div class="card" style="max-width:none;">
                    <h2><?php _e('Zahlungsübersicht', 'themisdb-order-request'); ?></h2>
                    <table class="widefat striped" style="border:none;box-shadow:none;">
                        <tbody>
                            <tr>
                                <td><?php _e('Gesamt Zahlungen', 'themisdb-order-request'); ?></td>
                                <td><strong><?php echo esc_html(number_format_i18n((int) $stats['total_payments'])); ?></strong></td>
                            </tr>
                            <tr>
                                <td><?php _e('Gesamtbetrag', 'themisdb-order-request'); ?></td>
                                <td><strong><?php echo esc_html(number_format_i18n((float) $stats['total_amount'], 2)); ?> €</strong></td>
                            </tr>
                            <tr>
                                <td><?php _e('Verifiziert', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html(number_format_i18n((int) $stats['verified_payments'])); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Ausstehend', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html(number_format_i18n((int) $stats['pending_payments'])); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Fehlgeschlagen', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html(number_format_i18n((int) $stats['failed_payments'])); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="card" style="max-width:none;">
                    <h2><?php _e('Schnellaktionen', 'themisdb-order-request'); ?></h2>
                    <p><?php _e('Neue Zahlung anlegen, Bankabgleiche starten oder offene Zahlungen filtern.', 'themisdb-order-request'); ?></p>
                    <p>
                        <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=themisdb-payments&action=new')); ?>"><?php _e('Zahlung erfassen', 'themisdb-order-request'); ?></a>
                        <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=themisdb-bank-import')); ?>"><?php _e('CSV-Import', 'themisdb-order-request'); ?></a>
                        <a class="button" href="<?php echo esc_url(add_query_arg(array('page' => 'themisdb-payments', 'status_tab' => 'pending'), admin_url('admin.php'))); ?>"><?php _e('Offene Zahlungen', 'themisdb-order-request'); ?></a>
                    </p>
                </div>
            </div>
            
            <form method="post" class="themisdb-list-root" id="themisdb-payments-list-root">
                <?php wp_nonce_field('themisdb_bulk_payment_workflow'); ?>
                <input type="hidden" name="action" value="bulk_payment_workflow">
                <?php $this->render_bulk_action_bar('payment_workflow', $bulk_actions, __('Ausgewählte Zahlungen verarbeiten', 'themisdb-order-request')); ?>

                <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td class="check-column"><input type="checkbox" class="themisdb-bulk-toggle" aria-label="<?php esc_attr_e('Alle Zahlungen auswählen', 'themisdb-order-request'); ?>"></td>
                        <th><?php $this->render_sortable_column(__('Zahlungsnummer', 'themisdb-order-request'), 'number', $sort_by, $sort_dir, admin_url('admin.php?page=themisdb-payments'), array('status_tab' => $status_tab, 'q' => $search_query, 'per_page' => $per_page)); ?></th>
                        <th><?php _e('Bestellung', 'themisdb-order-request'); ?></th>
                        <th><?php $this->render_sortable_column(__('Betrag', 'themisdb-order-request'), 'amount', $sort_by, $sort_dir, admin_url('admin.php?page=themisdb-payments'), array('status_tab' => $status_tab, 'q' => $search_query, 'per_page' => $per_page)); ?></th>
                        <th><?php $this->render_sortable_column(__('Methode', 'themisdb-order-request'), 'method', $sort_by, $sort_dir, admin_url('admin.php?page=themisdb-payments'), array('status_tab' => $status_tab, 'q' => $search_query, 'per_page' => $per_page)); ?></th>
                        <th><?php $this->render_sortable_column(__('Status', 'themisdb-order-request'), 'status', $sort_by, $sort_dir, admin_url('admin.php?page=themisdb-payments'), array('status_tab' => $status_tab, 'q' => $search_query, 'per_page' => $per_page)); ?></th>
                        <th><?php $this->render_sortable_column(__('Datum', 'themisdb-order-request'), 'date', $sort_by, $sort_dir, admin_url('admin.php?page=themisdb-payments'), array('status_tab' => $status_tab, 'q' => $search_query, 'per_page' => $per_page)); ?></th>
                        <th><?php _e('Aktionen', 'themisdb-order-request'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payments)): ?>
                    <tr>
                        <td colspan="8"><?php _e('Keine Zahlungen gefunden', 'themisdb-order-request'); ?></td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($payments as $payment): ?>
                        <?php $order = ThemisDB_Order_Manager::get_order($payment['order_id']); ?>
                        <tr>
                            <th scope="row" class="check-column"><input type="checkbox" name="payment_ids[]" value="<?php echo absint($payment['id']); ?>"></th>
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
                            <td><?php echo esc_html($this->get_payment_method_label($payment['payment_method'])); ?></td>
                            <td>
                                <span class="payment-status status-<?php echo esc_attr($payment['payment_status']); ?>">
                                    <?php echo esc_html($this->get_payment_status_label($payment['payment_status'])); ?>
                                </span>
                            </td>
                            <td><?php echo date('d.m.Y H:i', strtotime($payment['created_at'])); ?></td>
                            <td>
                                <a href="?page=themisdb-payments&action=view&payment_id=<?php echo $payment['id']; ?>" class="button button-small">
                                    <?php _e('Ansehen', 'themisdb-order-request'); ?>
                                </a>
                                <a href="?page=themisdb-payments&action=edit&payment_id=<?php echo $payment['id']; ?>" class="button button-small">
                                    <?php _e('Bearbeiten', 'themisdb-order-request'); ?>
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
            </form>

            <?php
            $this->render_simple_pagination(
                admin_url('admin.php?page=themisdb-payments'),
                $pagination['paged'],
                $pagination['total_pages'],
                array(
                    'status_tab' => $status_tab,
                    'q' => $search_query,
                    'sort_by' => $sort_by,
                    'sort_dir' => $sort_dir,
                    'per_page' => $per_page,
                )
            );
            ?>

            <?php $this->render_bulk_table_script(); ?>
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
            <h1 class="wp-heading-inline"><?php _e('Zahlung', 'themisdb-order-request'); ?>: <?php echo esc_html($payment['payment_number']); ?></h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-payments&action=edit&payment_id=' . absint($payment_id))); ?>" class="page-title-action"><?php _e('Bearbeiten', 'themisdb-order-request'); ?></a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-payments')); ?>" class="page-title-action"><?php _e('Zur Zahlungsliste', 'themisdb-order-request'); ?></a>
            <?php if ($order): ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-orders&action=view&order_id=' . absint($order['id']))); ?>" class="page-title-action"><?php _e('Bestellung ansehen', 'themisdb-order-request'); ?></a>
            <?php endif; ?>
            <hr class="wp-header-end">
            <?php $this->render_module_navigation_tabs('themisdb-payments'); ?>
            
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
                        <td><?php echo esc_html($this->get_payment_method_label($payment['payment_method'])); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Status', 'themisdb-order-request'); ?>:</th>
                        <td>
                            <span class="payment-status status-<?php echo esc_attr($payment['payment_status']); ?>">
                                <?php echo esc_html($this->get_payment_status_label($payment['payment_status'])); ?>
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
                <a href="?page=themisdb-payments&action=edit&payment_id=<?php echo absint($payment['id']); ?>" class="button"><?php _e('Bearbeiten', 'themisdb-order-request'); ?></a>
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
     * Create new payment form.
     */
    private function create_payment() {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Neue Zahlung anlegen', 'themisdb-order-request'); ?></h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-payments')); ?>" class="page-title-action"><?php _e('Zur Zahlungsliste', 'themisdb-order-request'); ?></a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-bank-import')); ?>" class="page-title-action"><?php _e('Bankimport', 'themisdb-order-request'); ?></a>
            <hr class="wp-header-end">
            <?php $this->render_module_navigation_tabs('themisdb-payments'); ?>

            <form method="post">
                <?php wp_nonce_field('themisdb_save_payment'); ?>
                <input type="hidden" name="action" value="save_payment">
                <input type="hidden" name="payment_id" value="0">

                <div class="card">
                    <h2><?php _e('Zahlungsdaten', 'themisdb-order-request'); ?></h2>
                    <table class="form-table">
                        <tr><th><label for="payment_order_id"><?php _e('Order-ID', 'themisdb-order-request'); ?></label></th><td><input id="payment_order_id" name="order_id" type="number" min="1" required></td></tr>
                        <tr><th><label for="payment_contract_id"><?php _e('Contract-ID', 'themisdb-order-request'); ?></label></th><td><input id="payment_contract_id" name="contract_id" type="number" min="0"></td></tr>
                        <tr><th><label for="payment_amount"><?php _e('Betrag', 'themisdb-order-request'); ?></label></th><td><input id="payment_amount" name="amount" type="number" min="0" step="0.01" required></td></tr>
                        <tr><th><label for="payment_currency"><?php _e('Währung', 'themisdb-order-request'); ?></label></th><td><input id="payment_currency" name="currency" value="EUR" maxlength="10"></td></tr>
                        <tr><th><label for="payment_method"><?php _e('Zahlungsmethode', 'themisdb-order-request'); ?></label></th><td><input id="payment_method" name="payment_method" class="regular-text" value="invoice" required></td></tr>
                        <tr><th><label for="payment_transaction_id"><?php _e('Transaktions-ID', 'themisdb-order-request'); ?></label></th><td><input id="payment_transaction_id" name="transaction_id" class="regular-text"></td></tr>
                        <tr>
                            <th><label for="payment_status"><?php _e('Status', 'themisdb-order-request'); ?></label></th>
                            <td>
                                <select id="payment_status" name="payment_status">
                                    <option value="pending"><?php _e('Ausstehend', 'themisdb-order-request'); ?></option>
                                    <option value="verified"><?php _e('Verifiziert', 'themisdb-order-request'); ?></option>
                                    <option value="overdue"><?php _e('Überfällig', 'themisdb-order-request'); ?></option>
                                    <option value="failed"><?php _e('Fehlgeschlagen', 'themisdb-order-request'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr><th><label for="payment_notes"><?php _e('Notizen', 'themisdb-order-request'); ?></label></th><td><textarea id="payment_notes" name="notes" rows="3" class="large-text"></textarea></td></tr>
                    </table>
                </div>

                <p>
                    <button type="submit" class="button button-primary"><?php _e('Zahlung speichern', 'themisdb-order-request'); ?></button>
                    <a href="?page=themisdb-payments" class="button"><?php _e('Abbrechen', 'themisdb-order-request'); ?></a>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Edit payment form.
     */
    private function edit_payment($payment_id) {
        $payment = ThemisDB_Payment_Manager::get_payment($payment_id);
        if (!$payment) {
            echo '<div class="notice notice-error"><p>' . __('Zahlung nicht gefunden', 'themisdb-order-request') . '</p></div>';
            return;
        }
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Zahlung bearbeiten', 'themisdb-order-request'); ?></h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-payments&action=view&payment_id=' . absint($payment_id))); ?>" class="page-title-action"><?php _e('Zur Detailansicht', 'themisdb-order-request'); ?></a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-payments')); ?>" class="page-title-action"><?php _e('Zur Zahlungsliste', 'themisdb-order-request'); ?></a>
            <hr class="wp-header-end">
            <?php $this->render_module_navigation_tabs('themisdb-payments'); ?>
            <form method="post">
                <?php wp_nonce_field('themisdb_save_payment'); ?>
                <input type="hidden" name="action" value="save_payment">
                <input type="hidden" name="payment_id" value="<?php echo absint($payment_id); ?>">
                <div class="card">
                    <h2><?php _e('Zahlungsdaten', 'themisdb-order-request'); ?></h2>
                    <table class="form-table">
                        <tr><th><?php _e('Zahlungsnummer', 'themisdb-order-request'); ?></th><td><code><?php echo esc_html($payment['payment_number']); ?></code></td></tr>
                        <tr><th><label for="payment_order_id"><?php _e('Order-ID', 'themisdb-order-request'); ?></label></th><td><input id="payment_order_id" name="order_id" type="number" min="1" value="<?php echo esc_attr($payment['order_id']); ?>" required></td></tr>
                        <tr><th><label for="payment_contract_id"><?php _e('Contract-ID', 'themisdb-order-request'); ?></label></th><td><input id="payment_contract_id" name="contract_id" type="number" min="0" value="<?php echo esc_attr($payment['contract_id']); ?>"></td></tr>
                        <tr><th><label for="payment_amount"><?php _e('Betrag', 'themisdb-order-request'); ?></label></th><td><input id="payment_amount" name="amount" type="number" min="0" step="0.01" value="<?php echo esc_attr($payment['amount']); ?>" required></td></tr>
                        <tr><th><label for="payment_currency"><?php _e('Währung', 'themisdb-order-request'); ?></label></th><td><input id="payment_currency" name="currency" value="<?php echo esc_attr($payment['currency']); ?>" maxlength="10"></td></tr>
                        <tr><th><label for="payment_method"><?php _e('Zahlungsmethode', 'themisdb-order-request'); ?></label></th><td><input id="payment_method" name="payment_method" class="regular-text" value="<?php echo esc_attr($payment['payment_method']); ?>" required></td></tr>
                        <tr><th><label for="payment_transaction_id"><?php _e('Transaktions-ID', 'themisdb-order-request'); ?></label></th><td><input id="payment_transaction_id" name="transaction_id" class="regular-text" value="<?php echo esc_attr($payment['transaction_id']); ?>"></td></tr>
                        <tr>
                            <th><label for="payment_status"><?php _e('Status', 'themisdb-order-request'); ?></label></th>
                            <td>
                                <select id="payment_status" name="payment_status">
                                    <option value="pending" <?php selected($payment['payment_status'], 'pending'); ?>><?php _e('Ausstehend', 'themisdb-order-request'); ?></option>
                                    <option value="verified" <?php selected($payment['payment_status'], 'verified'); ?>><?php _e('Verifiziert', 'themisdb-order-request'); ?></option>
                                    <option value="overdue" <?php selected($payment['payment_status'], 'overdue'); ?>><?php _e('Überfällig', 'themisdb-order-request'); ?></option>
                                    <option value="failed" <?php selected($payment['payment_status'], 'failed'); ?>><?php _e('Fehlgeschlagen', 'themisdb-order-request'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr><th><label for="payment_notes"><?php _e('Notizen', 'themisdb-order-request'); ?></label></th><td><textarea id="payment_notes" name="notes" rows="3" class="large-text"><?php echo esc_textarea($payment['notes']); ?></textarea></td></tr>
                    </table>
                </div>
                <p>
                    <button type="submit" class="button button-primary"><?php _e('Zahlung speichern', 'themisdb-order-request'); ?></button>
                    <a href="?page=themisdb-payments&action=view&payment_id=<?php echo absint($payment_id); ?>" class="button"><?php _e('Zurück', 'themisdb-order-request'); ?></a>
                </p>
            </form>
            <div class="card" style="margin-top:20px;">
                <h2><?php _e('Löschen', 'themisdb-order-request'); ?></h2>
                <form method="post" onsubmit="return confirm('<?php _e('Zahlung wirklich löschen?', 'themisdb-order-request'); ?>');">
                    <?php wp_nonce_field('themisdb_delete_payment'); ?>
                    <input type="hidden" name="action" value="delete_payment">
                    <input type="hidden" name="payment_id" value="<?php echo absint($payment_id); ?>">
                    <button type="submit" class="button button-link-delete"><?php _e('Zahlung löschen', 'themisdb-order-request'); ?></button>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Save payment changes from admin form.
     */
    private function handle_save_payment($payment_id) {
        $payload = array(
            'order_id' => absint($_POST['order_id'] ?? 0),
            'contract_id' => absint($_POST['contract_id'] ?? 0),
            'amount' => floatval($_POST['amount'] ?? 0),
            'currency' => sanitize_text_field($_POST['currency'] ?? 'EUR'),
            'payment_method' => sanitize_text_field($_POST['payment_method'] ?? 'invoice'),
            'payment_status' => sanitize_text_field($_POST['payment_status'] ?? 'pending'),
            'transaction_id' => sanitize_text_field($_POST['transaction_id'] ?? ''),
            'notes' => sanitize_textarea_field($_POST['notes'] ?? ''),
        );

        if (empty($payload['order_id']) || $payload['amount'] <= 0) {
            $this->abort_with_log(
                __('Bitte gültige Order-ID und Betrag angeben.', 'themisdb-order-request'),
                'Admin save payment failed due to invalid order or amount',
                array(
                    'payment_id' => intval($payment_id),
                    'order_id' => intval($payload['order_id']),
                    'amount' => floatval($payload['amount']),
                ),
                'warning'
            );
        }

        if ($payment_id === 0) {
            $new_id = ThemisDB_Payment_Manager::create_payment($payload);
            if (!$new_id) {
                $this->abort_with_log(
                    __('Fehler beim Erstellen der Zahlung.', 'themisdb-order-request'),
                    'Admin create payment failed in payment manager',
                    array('order_id' => intval($payload['order_id']))
                );
            }

            if ($payload['payment_status'] === 'verified') {
                ThemisDB_Payment_Manager::verify_payment($new_id, get_current_user_id());
            } elseif ($payload['payment_status'] === 'overdue') {
                ThemisDB_Payment_Manager::mark_payment_overdue($new_id, $payload['notes']);
            } elseif ($payload['payment_status'] === 'failed') {
                ThemisDB_Payment_Manager::mark_payment_failed($new_id, $payload['notes']);
            }

            wp_redirect(admin_url('admin.php?page=themisdb-payments&action=view&payment_id=' . $new_id . '&message=saved'));
            exit;
        }

        $result = ThemisDB_Payment_Manager::update_payment($payment_id, $payload);
        if ($result) {
            wp_redirect(admin_url('admin.php?page=themisdb-payments&action=view&payment_id=' . $payment_id . '&message=saved'));
            exit;
        }

        $this->abort_with_log(
            __('Fehler beim Speichern der Zahlung.', 'themisdb-order-request'),
            'Admin update payment failed in payment manager',
            array(
                'payment_id' => intval($payment_id),
                'order_id' => intval($payload['order_id']),
            )
        );
    }

    /**
     * Delete payment row from admin.
     */
    private function handle_delete_payment($payment_id) {
        $result = ThemisDB_Payment_Manager::delete_payment($payment_id);

        if ($result) {
            wp_redirect(admin_url('admin.php?page=themisdb-payments&message=deleted'));
            exit;
        }

        $this->abort_with_log(
            __('Fehler beim Löschen der Zahlung.', 'themisdb-order-request'),
            'Admin delete payment failed in payment manager',
            array('payment_id' => intval($payment_id))
        );
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

            if ($post_action === 'bulk_license_workflow') {
                check_admin_referer('themisdb_bulk_license_workflow');
                $this->handle_bulk_license_workflow();
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
        $prefs = $this->get_list_preferences('licenses');
        $status_tab = isset($_GET['status_tab']) ? sanitize_text_field($_GET['status_tab']) : ($prefs['status_tab'] ?? 'all');
        $search_query = sanitize_text_field($_GET['q'] ?? '');
        $sort_by = sanitize_key($_GET['sort_by'] ?? ($prefs['sort_by'] ?? 'date'));
        $sort_dir = sanitize_key($_GET['sort_dir'] ?? ($prefs['sort_dir'] ?? 'desc'));
        $per_page = $this->resolve_per_page($_GET['per_page'] ?? ($prefs['per_page'] ?? 25), 25);
        $paged = max(1, absint($_GET['paged'] ?? 1));
        $table_licenses = $wpdb->prefix . 'themisdb_licenses';

        if ($status_tab === 'all') {
            $licenses = $wpdb->get_results(
                $wpdb->prepare("SELECT * FROM {$table_licenses} ORDER BY created_at DESC LIMIT %d", 200),
                ARRAY_A
            );
        } else {
            $licenses = $wpdb->get_results(
                $wpdb->prepare("SELECT * FROM {$table_licenses} WHERE license_status = %s ORDER BY created_at DESC LIMIT %d", $status_tab, 200),
                ARRAY_A
            );
        }

        if ($search_query !== '') {
            $licenses = $this->filter_rows_by_search($licenses, $search_query, array('license_key', 'product_edition', 'license_status', 'license_type'));
        }

        $sort_map = array(
            'key' => 'license_key',
            'edition' => 'product_edition',
            'status' => 'license_status',
            'activation' => 'activation_date',
            'expiry' => 'expiry_date',
            'date' => 'created_at',
        );
        $sort_by = array_key_exists($sort_by, $sort_map) ? $sort_by : 'date';
        $sort_dir = in_array($sort_dir, array('asc', 'desc'), true) ? $sort_dir : 'desc';
        $this->save_list_preferences('licenses', array(
            'status_tab' => $status_tab,
            'sort_by' => $sort_by,
            'sort_dir' => $sort_dir,
            'per_page' => $per_page,
        ));
        $licenses = $this->sort_rows($licenses, $sort_map[$sort_by], $sort_dir, array(), array('activation_date', 'expiry_date', 'created_at'));

        $pagination = $this->paginate_rows($licenses, $per_page, $paged);
        $licenses = $pagination['items'];

        $stats = ThemisDB_License_Manager::get_license_stats();
        $bulk_actions = array(
            'active' => __('Aktivieren', 'themisdb-order-request'),
            'suspended' => __('Suspendieren', 'themisdb-order-request'),
            'cancelled' => __('Kündigen', 'themisdb-order-request'),
            'dispatch_build' => __('CI/CD vorbereiten', 'themisdb-order-request'),
            'delete' => __('Löschen', 'themisdb-order-request'),
        );
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Lizenzen', 'themisdb-order-request'); ?></h1>
            <a class="page-title-action" href="<?php echo esc_url(admin_url('admin.php?page=themisdb-licenses&action=new')); ?>"><?php _e('Neue Lizenz', 'themisdb-order-request'); ?></a>
            <a class="page-title-action" href="<?php echo esc_url(admin_url('admin.php?page=themisdb-license-audit')); ?>"><?php _e('Audit Log', 'themisdb-order-request'); ?></a>
            <a class="page-title-action" href="<?php echo esc_url(admin_url('admin.php?page=themisdb-order-dashboard')); ?>"><?php _e('Zum Dashboard', 'themisdb-order-request'); ?></a>
            <hr class="wp-header-end">
            <?php $this->render_module_navigation_tabs('themisdb-licenses'); ?>

            <?php $this->render_bulk_notice('bulk_licenses'); ?>

            <?php
            $tab_base = admin_url('admin.php?page=themisdb-licenses');
            $license_tabs = array(
                'all' => __('Alle', 'themisdb-order-request') . ' (' . intval($stats['total_licenses']) . ')',
                'active' => __('Aktiv', 'themisdb-order-request') . ' (' . intval($stats['active_licenses']) . ')',
                'pending' => __('Ausstehend', 'themisdb-order-request') . ' (' . intval($stats['pending_licenses']) . ')',
                'suspended' => __('Suspendiert', 'themisdb-order-request') . ' (' . intval($stats['suspended_licenses']) . ')',
                'expired' => __('Abgelaufen', 'themisdb-order-request') . ' (' . intval($stats['expired_licenses']) . ')',
                'cancelled' => __('Gekündigt', 'themisdb-order-request') . ' (' . intval($stats['cancelled_licenses']) . ')',
            );
            ?>
            <?php $this->render_filter_button_bar('status_tab', $license_tabs, $status_tab, $tab_base, array(
                'q' => $search_query,
                'sort_by' => $sort_by,
                'sort_dir' => $sort_dir,
                'per_page' => $per_page,
            )); ?>

            <form method="get" class="themisdb-ajax-form" style="margin:8px 0 12px; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                <input type="hidden" name="page" value="themisdb-licenses">
                <input type="hidden" name="status_tab" value="<?php echo esc_attr($status_tab); ?>">
                <input type="hidden" name="sort_by" value="<?php echo esc_attr($sort_by); ?>">
                <input type="hidden" name="sort_dir" value="<?php echo esc_attr($sort_dir); ?>">
                <input type="hidden" name="paged" value="1">
                <input type="search" name="q" value="<?php echo esc_attr($search_query); ?>" placeholder="<?php esc_attr_e('Suche nach Lizenzschlüssel, Edition ...', 'themisdb-order-request'); ?>" class="regular-text">
                <button type="submit" class="button"><?php _e('Suchen', 'themisdb-order-request'); ?></button>
                <?php $this->render_per_page_select('per_page', $per_page); ?>
            </form>

            <?php $this->render_inline_help_box(
                __('Inline-Hilfe: Lizenzen', 'themisdb-order-request'),
                array(
                    __('Status-Tabs und Suche arbeiten auf derselben Lizenzliste.', 'themisdb-order-request'),
                    __('Die Übersicht zeigt den Bestand, die Tabelle darunter die einzelnen Lizenzen.', 'themisdb-order-request'),
                    __('Bulk-Workflows gelten nur für markierte Lizenzen.', 'themisdb-order-request'),
                )
            ); ?>

            <?php if (isset($_GET['message']) && $_GET['message'] === 'deleted'): ?>
            <div class="notice notice-success"><p><?php _e('Lizenz wurde gelöscht.', 'themisdb-order-request'); ?></p></div>
            <?php endif; ?>

            <div class="themisdb-admin-modules" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;margin:20px 0;">
                <div class="card" style="max-width:none;">
                    <h2><?php _e('Lizenzübersicht', 'themisdb-order-request'); ?></h2>
                    <table class="widefat striped" style="border:none;box-shadow:none;">
                        <tbody>
                            <tr>
                                <td><?php _e('Gesamt Lizenzen', 'themisdb-order-request'); ?></td>
                                <td><strong><?php echo esc_html(number_format_i18n((int) $stats['total_licenses'])); ?></strong></td>
                            </tr>
                            <tr>
                                <td><?php _e('Aktiv', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html(number_format_i18n((int) $stats['active_licenses'])); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Ausstehend', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html(number_format_i18n((int) $stats['pending_licenses'])); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Suspendiert', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html(number_format_i18n((int) $stats['suspended_licenses'])); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Abgelaufen', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html(number_format_i18n((int) $stats['expired_licenses'])); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Gekündigt', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html(number_format_i18n((int) $stats['cancelled_licenses'])); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="card" style="max-width:none;">
                    <h2><?php _e('Schnellaktionen', 'themisdb-order-request'); ?></h2>
                    <p><?php _e('Neue Lizenzen anlegen, Audit-Eintrage einsehen oder aktive Lizenzen filtern.', 'themisdb-order-request'); ?></p>
                    <p>
                        <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=themisdb-licenses&action=new')); ?>"><?php _e('Lizenz anlegen', 'themisdb-order-request'); ?></a>
                        <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=themisdb-license-audit')); ?>"><?php _e('Audit Log', 'themisdb-order-request'); ?></a>
                        <a class="button" href="<?php echo esc_url(add_query_arg(array('page' => 'themisdb-licenses', 'status_tab' => 'active'), admin_url('admin.php'))); ?>"><?php _e('Aktive Lizenzen', 'themisdb-order-request'); ?></a>
                    </p>
                </div>
            </div>
            
            <form method="post" class="themisdb-list-root" id="themisdb-licenses-list-root">
                <?php wp_nonce_field('themisdb_bulk_license_workflow'); ?>
                <input type="hidden" name="action" value="bulk_license_workflow">
                <?php $this->render_bulk_action_bar('license_workflow', $bulk_actions, __('Ausgewählte Lizenzen verarbeiten', 'themisdb-order-request')); ?>

                <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td class="check-column"><input type="checkbox" class="themisdb-bulk-toggle" aria-label="<?php esc_attr_e('Alle Lizenzen auswählen', 'themisdb-order-request'); ?>"></td>
                        <th><?php $this->render_sortable_column(__('Lizenzschlüssel', 'themisdb-order-request'), 'key', $sort_by, $sort_dir, admin_url('admin.php?page=themisdb-licenses'), array('status_tab' => $status_tab, 'q' => $search_query, 'per_page' => $per_page)); ?></th>
                        <th><?php $this->render_sortable_column(__('Edition', 'themisdb-order-request'), 'edition', $sort_by, $sort_dir, admin_url('admin.php?page=themisdb-licenses'), array('status_tab' => $status_tab, 'q' => $search_query, 'per_page' => $per_page)); ?></th>
                        <th><?php _e('Kunde', 'themisdb-order-request'); ?></th>
                        <th><?php $this->render_sortable_column(__('Status', 'themisdb-order-request'), 'status', $sort_by, $sort_dir, admin_url('admin.php?page=themisdb-licenses'), array('status_tab' => $status_tab, 'q' => $search_query, 'per_page' => $per_page)); ?></th>
                        <th><?php $this->render_sortable_column(__('Aktiviert', 'themisdb-order-request'), 'activation', $sort_by, $sort_dir, admin_url('admin.php?page=themisdb-licenses'), array('status_tab' => $status_tab, 'q' => $search_query, 'per_page' => $per_page)); ?></th>
                        <th><?php $this->render_sortable_column(__('Läuft ab', 'themisdb-order-request'), 'expiry', $sort_by, $sort_dir, admin_url('admin.php?page=themisdb-licenses'), array('status_tab' => $status_tab, 'q' => $search_query, 'per_page' => $per_page)); ?></th>
                        <th><?php _e('Aktionen', 'themisdb-order-request'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($licenses)): ?>
                    <tr>
                        <td colspan="8"><?php _e('Keine Lizenzen gefunden', 'themisdb-order-request'); ?></td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($licenses as $license): ?>
                        <?php $order = ThemisDB_Order_Manager::get_order($license['order_id']); ?>
                        <tr>
                            <th scope="row" class="check-column"><input type="checkbox" name="license_ids[]" value="<?php echo absint($license['id']); ?>"></th>
                            <td><code style="font-size: 10px;"><?php echo esc_html(substr($license['license_key'], 0, 20)); ?>...</code></td>
                            <td><?php echo esc_html($this->get_product_edition_label($license['product_edition'])); ?></td>
                            <td>
                                <?php if ($order): ?>
                                    <?php echo esc_html($order['customer_name']); ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="license-status status-<?php echo esc_attr($license['license_status']); ?>">
                                    <?php echo esc_html($this->get_license_status_label($license['license_status'])); ?>
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
            </form>

            <?php
            $this->render_simple_pagination(
                admin_url('admin.php?page=themisdb-licenses'),
                $pagination['paged'],
                $pagination['total_pages'],
                array(
                    'status_tab' => $status_tab,
                    'q' => $search_query,
                    'sort_by' => $sort_by,
                    'sort_dir' => $sort_dir,
                    'per_page' => $per_page,
                )
            );
            ?>

            <?php $this->render_bulk_table_script(); ?>
        </div>
        <?php
    }

    /**
     * Render bulk action toolbar.
     */
    private function render_bulk_action_bar($field_name, $actions, $button_label) {
        ?>
        <div style="display:flex; gap:8px; align-items:center; margin:12px 0;">
            <select name="<?php echo esc_attr($field_name); ?>">
                <option value=""><?php _e('Workflow wählen', 'themisdb-order-request'); ?></option>
                <?php foreach ($actions as $value => $label): ?>
                <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="button button-secondary"><?php echo esc_html($button_label); ?></button>
        </div>
        <?php
    }

    /**
     * Render a bulk workflow result notice.
     */
    private function render_bulk_notice($param_name) {
        if (empty($_GET[$param_name])) {
            return;
        }

        $raw = wp_unslash($_GET[$param_name]);
        $data = json_decode(base64_decode($raw), true);

        if (!is_array($data)) {
            return;
        }

        $success = !empty($data['success']);
        $processed = isset($data['processed']) ? intval($data['processed']) : 0;
        $failed = isset($data['failed']) ? intval($data['failed']) : 0;
        $message = isset($data['message']) ? sanitize_text_field($data['message']) : '';
        $class = $success && $failed === 0 ? 'notice-success' : 'notice-warning';

        echo '<div class="notice ' . esc_attr($class) . '"><p>';
        echo esc_html($message !== '' ? $message : __('Sammelworkflow wurde ausgeführt.', 'themisdb-order-request'));
        echo ' ' . esc_html(sprintf(__('Verarbeitet: %d, Fehler: %d', 'themisdb-order-request'), $processed, $failed));
        echo '</p></div>';
    }

    /**
     * Render a compact inline help box.
     */
    private function render_inline_help_box($title, $items) {
        if ($title === '' || empty($items) || !is_array($items)) {
            return;
        }

        echo '<details class="notice notice-info" open style="margin:0 0 12px 0; padding:12px 16px;">';
        echo '<summary style="cursor:pointer; font-weight:600; margin:0 0 8px 0;">' . esc_html($title) . '</summary>';
        echo '<ul style="margin:8px 0 0 0; padding-left:18px;">';

        foreach ($items as $item) {
            if (!is_string($item) || $item === '') {
                continue;
            }

            echo '<li>' . esc_html($item) . '</li>';
        }

        echo '</ul>';
        echo '</details>';
    }

    /**
     * Render checkbox toggle script for bulk tables.
     */
    private function render_bulk_table_script() {
        static $rendered = false;

        if ($rendered) {
            return;
        }

        $rendered = true;
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.themisdb-bulk-toggle').forEach(function (toggle) {
                toggle.addEventListener('change', function () {
                    var table = toggle.closest('table');
                    if (!table) {
                        return;
                    }

                    table.querySelectorAll('tbody input[type="checkbox"]').forEach(function (checkbox) {
                        checkbox.checked = toggle.checked;
                    });
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Filter rows by search query over selected fields.
     */
    private function filter_rows_by_search($rows, $query, $fields) {
        $needle = mb_strtolower(trim((string) $query));

        if ($needle === '') {
            return $rows;
        }

        return array_values(array_filter((array) $rows, function ($row) use ($needle, $fields) {
            foreach ($fields as $field) {
                $value = isset($row[$field]) ? mb_strtolower((string) $row[$field]) : '';
                if ($value !== '' && strpos($value, $needle) !== false) {
                    return true;
                }
            }

            return false;
        }));
    }

    /**
     * Sort rows by one field and direction.
     */
    private function sort_rows($rows, $field, $direction = 'asc', $numeric_fields = array(), $date_fields = array()) {
        $rows = array_values((array) $rows);
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        usort($rows, function ($left, $right) use ($field, $direction, $numeric_fields, $date_fields) {
            $a = $left[$field] ?? null;
            $b = $right[$field] ?? null;

            if (in_array($field, $date_fields, true)) {
                $a = $a ? strtotime((string) $a) : 0;
                $b = $b ? strtotime((string) $b) : 0;
            } elseif (in_array($field, $numeric_fields, true)) {
                $a = floatval($a ?? 0);
                $b = floatval($b ?? 0);
            } else {
                $a = mb_strtolower(trim((string) ($a ?? '')));
                $b = mb_strtolower(trim((string) ($b ?? '')));
            }

            if ($a == $b) {
                return 0;
            }

            if ($direction === 'asc') {
                return ($a < $b) ? -1 : 1;
            }

            return ($a > $b) ? -1 : 1;
        });

        return $rows;
    }

    /**
     * Render sortable table header link.
     */
    private function render_sortable_column($label, $key, $current_sort_by, $current_sort_dir, $base_url, $args = array()) {
        $is_active = $current_sort_by === $key;
        $next_dir = ($is_active && $current_sort_dir === 'asc') ? 'desc' : 'asc';
        $indicator = '';

        if ($is_active) {
            $indicator = $current_sort_dir === 'asc' ? ' &uarr;' : ' &darr;';
        }

        $url = add_query_arg(array_merge((array) $args, array(
            'sort_by' => $key,
            'sort_dir' => $next_dir,
            'paged' => 1,
        )), $base_url);

        echo '<a class="themisdb-ajax-link" href="' . esc_url($url) . '">' . esc_html($label) . wp_kses_post($indicator) . '</a>';
    }

    /**
     * Get persisted list preferences for current user.
     */
    private function get_list_preferences($list_key) {
        $user_id = get_current_user_id();

        if ($user_id <= 0) {
            return array();
        }

        $prefs = get_user_meta($user_id, 'themisdb_list_prefs_' . sanitize_key($list_key), true);
        return is_array($prefs) ? $prefs : array();
    }

    /**
     * Persist list preferences for current user.
     */
    private function save_list_preferences($list_key, $prefs) {
        $user_id = get_current_user_id();

        if ($user_id <= 0 || !is_array($prefs)) {
            return;
        }

        update_user_meta($user_id, 'themisdb_list_prefs_' . sanitize_key($list_key), $prefs);
    }

    /**
     * Resolve and validate per-page list size.
     */
    private function resolve_per_page($value, $default = 25) {
        $allowed = array(25, 50, 100);
        $value = intval($value);

        if (!in_array($value, $allowed, true)) {
            return intval($default);
        }

        return $value;
    }

    /**
     * Render per-page selector.
     */
    private function render_per_page_select($name, $current) {
        $options = array(25, 50, 100);

        echo '<label style="display:inline-flex; gap:6px; align-items:center;">';
        echo '<span>' . esc_html__('Pro Seite', 'themisdb-order-request') . '</span>';
        echo '<select name="' . esc_attr($name) . '" class="themisdb-auto-submit">';

        foreach ($options as $option) {
            echo '<option value="' . esc_attr($option) . '"' . selected(intval($current), $option, false) . '>' . esc_html($option) . '</option>';
        }

        echo '</select>';
        echo '</label>';
    }

    /**
     * Paginate array rows.
     */
    private function paginate_rows($rows, $per_page = 25, $paged = 1) {
        $rows = array_values((array) $rows);
        $per_page = max(1, intval($per_page));
        $total_items = count($rows);
        $total_pages = max(1, (int) ceil($total_items / $per_page));
        $paged = max(1, min(intval($paged), $total_pages));
        $offset = ($paged - 1) * $per_page;

        return array(
            'items' => array_slice($rows, $offset, $per_page),
            'paged' => $paged,
            'total_pages' => $total_pages,
            'total_items' => $total_items,
            'per_page' => $per_page,
        );
    }

    /**
     * Render simple previous/next pagination.
     */
    private function render_simple_pagination($base_url, $paged, $total_pages, $args = array()) {
        if ($total_pages <= 1) {
            return;
        }

        $paged = max(1, intval($paged));
        $args = is_array($args) ? $args : array();

        $prev_url = $paged > 1 ? add_query_arg(array_merge($args, array('paged' => $paged - 1)), $base_url) : '';
        $next_url = $paged < $total_pages ? add_query_arg(array_merge($args, array('paged' => $paged + 1)), $base_url) : '';

        echo '<div class="tablenav" style="margin-top:10px;"><div class="tablenav-pages">';
        if ($prev_url !== '') {
            echo '<a class="button themisdb-ajax-link" href="' . esc_url($prev_url) . '">' . esc_html__('Zurück', 'themisdb-order-request') . '</a> ';
        }
        echo '<span class="displaying-num" style="margin:0 8px;">' . esc_html(sprintf(__('Seite %d von %d', 'themisdb-order-request'), $paged, $total_pages)) . '</span>';
        if ($next_url !== '') {
            echo ' <a class="button themisdb-ajax-link" href="' . esc_url($next_url) . '">' . esc_html__('Weiter', 'themisdb-order-request') . '</a>';
        }
        echo '</div></div>';
    }

    /**
     * Redirect to list page with bulk workflow result.
     */
    private function redirect_with_bulk_result($page_slug, $param_name, $message, $processed, $failed) {
        $payload = array(
            'success' => $failed === 0,
            'message' => $message,
            'processed' => intval($processed),
            'failed' => intval($failed),
        );

        wp_redirect(admin_url('admin.php?page=' . $page_slug . '&' . $param_name . '=' . rawurlencode(base64_encode(wp_json_encode($payload)))));
        exit;
    }

    /**
     * Handle bulk payment workflow actions.
     */
    private function handle_bulk_payment_workflow() {
        $payment_ids = isset($_POST['payment_ids']) ? array_map('absint', (array) $_POST['payment_ids']) : array();
        $workflow = sanitize_text_field($_POST['payment_workflow'] ?? '');


        if (empty($payment_ids) || $workflow === '') {
            $this->redirect_with_bulk_result('themisdb-payments', 'bulk_payments', __('Keine Zahlungen oder kein Workflow ausgewählt.', 'themisdb-order-request'), 0, 0);
        }

        $processed = 0;
        $failed = 0;

        foreach (array_unique($payment_ids) as $payment_id) {
            $success = false;

            if ($workflow === 'verify') {
                $success = ThemisDB_Payment_Manager::verify_payment($payment_id, get_current_user_id());
            } elseif ($workflow === 'overdue') {
                $success = ThemisDB_Payment_Manager::mark_payment_overdue($payment_id, __('Sammelaktion: als überfällig markiert.', 'themisdb-order-request'));
            } elseif ($workflow === 'failed') {
                $success = ThemisDB_Payment_Manager::mark_payment_failed($payment_id, __('Sammelaktion: als fehlgeschlagen markiert.', 'themisdb-order-request'));
            } elseif ($workflow === 'delete') {
                $success = ThemisDB_Payment_Manager::delete_payment($payment_id);
            }

            if ($success) {
                $processed++;
            } else {
                $failed++;
            }
        }

        $this->redirect_with_bulk_result('themisdb-payments', 'bulk_payments', __('Zahlungs-Workflow abgeschlossen.', 'themisdb-order-request'), $processed, $failed);
    }

    /**
     * Handle bulk order workflow actions (status changes, delete).
     */
    private function handle_bulk_order_workflow() {
        $order_ids = isset($_POST['order_ids']) ? array_map('absint', (array) $_POST['order_ids']) : array();
        $workflow  = sanitize_text_field($_POST['order_workflow'] ?? '');

        if (empty($order_ids) || $workflow === '') {
            $this->redirect_with_bulk_result('themisdb-orders', 'bulk_orders', __('Keine Bestellungen oder kein Workflow ausgewählt.', 'themisdb-order-request'), 0, 0);
        }

        $processed = 0;
        $failed    = 0;

        foreach (array_unique($order_ids) as $order_id) {
            $success = false;

            if ($workflow === 'delete') {
                $success = ThemisDB_Order_Manager::delete_order($order_id);
            } elseif (in_array($workflow, array('confirmed', 'active', 'suspended', 'ended', 'cancelled'), true)) {
                $success = ThemisDB_Order_Manager::set_order_status($order_id, $workflow);
            }

            if ($success) {
                $processed++;
            } else {
                $failed++;
            }
        }

        $this->redirect_with_bulk_result('themisdb-orders', 'bulk_orders', __('Bestellungs-Workflow abgeschlossen.', 'themisdb-order-request'), $processed, $failed);
    }

    /**
     * Handle bulk license workflow actions.
     */
    private function handle_bulk_license_workflow() {
        $license_ids = isset($_POST['license_ids']) ? array_map('absint', (array) $_POST['license_ids']) : array();
        $workflow = sanitize_text_field($_POST['license_workflow'] ?? '');

        if (empty($license_ids) || $workflow === '') {
            $this->redirect_with_bulk_result('themisdb-licenses', 'bulk_licenses', __('Keine Lizenzen oder kein Workflow ausgewählt.', 'themisdb-order-request'), 0, 0);
        }

        $processed = 0;
        $failed = 0;

        foreach (array_unique($license_ids) as $license_id) {
            $success = false;

            if ($workflow === 'active') {
                $success = ThemisDB_License_Manager::activate_license($license_id);
            } elseif ($workflow === 'suspended') {
                $success = ThemisDB_License_Manager::suspend_license($license_id, __('Sammelaktion in der Lizenzliste.', 'themisdb-order-request'));
            } elseif ($workflow === 'cancelled') {
                $success = ThemisDB_License_Manager::cancel_license($license_id, __('Sammelaktion in der Lizenzliste.', 'themisdb-order-request'), get_current_user_id());
            } elseif ($workflow === 'dispatch_build') {
                $result = ThemisDB_License_Build_Dispatcher::dispatch_for_license($license_id);
                $success = !empty($result['success']);
            } elseif ($workflow === 'delete') {
                $success = ThemisDB_License_Manager::delete_license($license_id);
            }

            if ($success) {
                $processed++;
            } else {
                $failed++;
            }
        }

        $this->redirect_with_bulk_result('themisdb-licenses', 'bulk_licenses', __('Lizenz-Workflow abgeschlossen.', 'themisdb-order-request'), $processed, $failed);
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
            <h1 class="wp-heading-inline"><?php _e('Lizenz', 'themisdb-order-request'); ?>: <?php echo esc_html($license['product_edition']); ?></h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-licenses&action=edit&license_id=' . absint($license_id))); ?>" class="page-title-action"><?php _e('Bearbeiten', 'themisdb-order-request'); ?></a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-licenses')); ?>" class="page-title-action"><?php _e('Zur Lizenzliste', 'themisdb-order-request'); ?></a>
            <?php if ($contract): ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-contracts&action=view&contract_id=' . absint($contract['id']))); ?>" class="page-title-action"><?php _e('Vertrag ansehen', 'themisdb-order-request'); ?></a>
            <?php endif; ?>
            <hr class="wp-header-end">
            <?php $this->render_module_navigation_tabs('themisdb-licenses'); ?>
            
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
                        <td><strong><?php echo esc_html($this->get_product_edition_label($license['product_edition'])); ?></strong></td>
                    </tr>
                    <tr>
                        <th><?php _e('Lizenztyp', 'themisdb-order-request'); ?>:</th>
                        <td><?php echo esc_html($this->get_license_type_label($license['license_type'])); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Status', 'themisdb-order-request'); ?>:</th>
                        <td>
                            <span class="license-status status-<?php echo esc_attr($license['license_status']); ?>">
                                <?php echo esc_html($this->get_license_status_label($license['license_status'])); ?>
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
                    <strong><?php _e('Status', 'themisdb-order-request'); ?>:</strong> <?php echo esc_html($this->get_contract_status_label($contract['status'])); ?><br>
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
            <h1 class="wp-heading-inline"><?php _e('Lizenz bearbeiten', 'themisdb-order-request'); ?></h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-licenses&action=view&license_id=' . absint($license_id))); ?>" class="page-title-action"><?php _e('Zur Detailansicht', 'themisdb-order-request'); ?></a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-licenses')); ?>" class="page-title-action"><?php _e('Zur Lizenzliste', 'themisdb-order-request'); ?></a>
            <hr class="wp-header-end">
            <?php $this->render_module_navigation_tabs('themisdb-licenses'); ?>

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
                <p><?php _e('Aktueller Status:', 'themisdb-order-request'); ?> <strong><?php echo esc_html($this->get_license_status_label($license['license_status'])); ?></strong></p>

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
            <h1 class="wp-heading-inline"><?php _e('Neue Lizenz anlegen', 'themisdb-order-request'); ?></h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-licenses')); ?>" class="page-title-action"><?php _e('Zur Lizenzliste', 'themisdb-order-request'); ?></a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-license-audit')); ?>" class="page-title-action"><?php _e('Audit Log', 'themisdb-order-request'); ?></a>
            <hr class="wp-header-end">
            <?php $this->render_module_navigation_tabs('themisdb-licenses'); ?>
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
                $this->abort_with_log(
                    __('Bitte Order-ID, Contract-ID und Customer-ID angeben.', 'themisdb-order-request'),
                    'Admin create license failed due to missing foreign keys',
                    array(
                        'order_id' => intval($create_payload['order_id']),
                        'contract_id' => intval($create_payload['contract_id']),
                        'customer_id' => intval($create_payload['customer_id']),
                    ),
                    'warning'
                );
            }

            $new_id = ThemisDB_License_Manager::create_license($create_payload);
            if ($new_id) {
                wp_redirect(admin_url('admin.php?page=themisdb-licenses&action=view&license_id=' . $new_id . '&message=saved'));
                exit;
            }

            $this->abort_with_log(
                __('Fehler beim Erstellen der Lizenz', 'themisdb-order-request'),
                'Admin create license failed in license manager',
                array(
                    'order_id' => intval($create_payload['order_id']),
                    'contract_id' => intval($create_payload['contract_id']),
                    'customer_id' => intval($create_payload['customer_id']),
                )
            );
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

        $this->abort_with_log(
            __('Fehler beim Speichern der Lizenz', 'themisdb-order-request'),
            'Admin update license failed in license manager',
            array('license_id' => intval($license_id))
        );
    }

    /**
     * Change license status via admin action.
     */
    private function handle_change_license_status($license_id, $new_status) {
        $license = ThemisDB_License_Manager::get_license($license_id);
        if (!$license) {
            $this->abort_with_log(
                __('Lizenz nicht gefunden', 'themisdb-order-request'),
                'Admin change license status aborted because license was not found',
                array('license_id' => intval($license_id)),
                'warning'
            );
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

        $this->abort_with_log(
            __('Fehler beim Ändern des Lizenzstatus', 'themisdb-order-request'),
            'Admin change license status failed in license manager',
            array(
                'license_id' => intval($license_id),
                'new_status' => sanitize_text_field($new_status),
            )
        );
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

        $this->abort_with_log(
            __('Fehler beim Löschen der Lizenz', 'themisdb-order-request'),
            'Admin delete license failed in license manager',
            array('license_id' => intval($license_id))
        );
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
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
            echo '<div class="wrap"><h1 class="wp-heading-inline">' . esc_html__( 'License Audit Log', 'themisdb-order-request' ) . '</h1>';
            echo '<a href="' . esc_url( admin_url( 'admin.php?page=themisdb-order-settings&tab=license' ) ) . '" class="page-title-action">' . esc_html__( 'License API konfigurieren', 'themisdb-order-request' ) . '</a>';
            echo '<a href="' . esc_url( admin_url( 'admin.php?page=themisdb-order-dashboard' ) ) . '" class="page-title-action">' . esc_html__( 'Zum Dashboard', 'themisdb-order-request' ) . '</a>';
            echo '<hr class="wp-header-end">';
            $this->render_module_navigation_tabs('themisdb-license-audit');
            echo '<p>' . esc_html__( 'No audit log entries yet. The log table is created on first REST API call.', 'themisdb-order-request' ) . '</p></div>';
            return;
        }

        if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Ungültiger Tabellenname für Audit-Log.', 'themisdb-order-request') . '</p></div>';
            return;
        }

        $table_sql = '`' . $table . '`';
        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_sql}");
        $rows  = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_sql} ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ),
            ARRAY_A
        );

        $total_pages = (int) ceil( $total / $per_page );
        $success_total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_sql} WHERE result = 'success'");
        $failed_total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_sql} WHERE result <> 'success'");
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e( 'License Audit Log', 'themisdb-order-request' ); ?></h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-order-settings&tab=license')); ?>" class="page-title-action"><?php esc_html_e( 'License API konfigurieren', 'themisdb-order-request' ); ?></a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-licenses')); ?>" class="page-title-action"><?php esc_html_e( 'Lizenzen verwalten', 'themisdb-order-request' ); ?></a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-order-dashboard')); ?>" class="page-title-action"><?php esc_html_e( 'Zum Dashboard', 'themisdb-order-request' ); ?></a>
            <hr class="wp-header-end">
            <?php $this->render_module_navigation_tabs('themisdb-license-audit'); ?>

            <div class="themisdb-admin-modules" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:20px;margin:20px 0;">
                <div class="card" style="max-width:none;">
                    <h2><?php esc_html_e( 'Audit-Status', 'themisdb-order-request' ); ?></h2>
                    <table class="widefat striped" style="border:none;box-shadow:none;">
                        <tbody>
                            <tr>
                                <td><?php esc_html_e( 'Gesamteintrage', 'themisdb-order-request' ); ?></td>
                                <td><strong><?php echo esc_html(number_format_i18n($total)); ?></strong></td>
                            </tr>
                            <tr>
                                <td><?php esc_html_e( 'Erfolgreich', 'themisdb-order-request' ); ?></td>
                                <td><?php echo esc_html(number_format_i18n($success_total)); ?></td>
                            </tr>
                            <tr>
                                <td><?php esc_html_e( 'Auffallig/fehlgeschlagen', 'themisdb-order-request' ); ?></td>
                                <td><?php echo esc_html(number_format_i18n($failed_total)); ?></td>
                            </tr>
                            <tr>
                                <td><?php esc_html_e( 'Aktuelle Seite', 'themisdb-order-request' ); ?></td>
                                <td><?php echo esc_html(sprintf('%d / %d', $page_num, max(1, $total_pages))); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="card" style="max-width:none;">
                    <h2><?php esc_html_e( 'Kontext', 'themisdb-order-request' ); ?></h2>
                    <p class="description">
                        <?php printf(
                            esc_html__( 'Showing %d of %d total entries.', 'themisdb-order-request' ),
                            count( $rows ),
                            $total
                        ); ?>
                    </p>
                    <p>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-order-settings&tab=license')); ?>" class="button button-primary"><?php esc_html_e( 'License API prufen', 'themisdb-order-request' ); ?></a>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-licenses')); ?>" class="button"><?php esc_html_e( 'Zu den Lizenzen', 'themisdb-order-request' ); ?></a>
                    </p>
                </div>
            </div>

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
        $sent_count = 0;
        $pending_count = 0;
        $failed_count = 0;

        foreach ($logs as $log) {
            if (($log['status'] ?? '') === 'sent') {
                $sent_count++;
            } elseif (($log['status'] ?? '') === 'failed') {
                $failed_count++;
            } else {
                $pending_count++;
            }
        }
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('E-Mail Log', 'themisdb-order-request'); ?></h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-order-settings&tab=email')); ?>" class="page-title-action"><?php _e('E-Mail Einstellungen', 'themisdb-order-request'); ?></a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-order-dashboard')); ?>" class="page-title-action"><?php _e('Zum Dashboard', 'themisdb-order-request'); ?></a>
            <hr class="wp-header-end">
            <?php $this->render_module_navigation_tabs('themisdb-email-log'); ?>

            <div class="themisdb-admin-modules" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:20px;margin:20px 0;">
                <div class="card" style="max-width:none;">
                    <h2><?php _e('Versandstatus', 'themisdb-order-request'); ?></h2>
                    <table class="widefat striped" style="border:none;box-shadow:none;">
                        <tbody>
                            <tr>
                                <td><?php _e('Eintrage gesamt', 'themisdb-order-request'); ?></td>
                                <td><strong><?php echo esc_html(number_format_i18n(count($logs))); ?></strong></td>
                            </tr>
                            <tr>
                                <td><?php _e('Gesendet', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html(number_format_i18n($sent_count)); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Ausstehend', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html(number_format_i18n($pending_count)); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Fehlgeschlagen', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html(number_format_i18n($failed_count)); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="card" style="max-width:none;">
                    <h2><?php _e('Schnellaktionen', 'themisdb-order-request'); ?></h2>
                    <p><?php _e('Prufen Sie Absenderdaten und den Zustand ausgehender Kommunikation.', 'themisdb-order-request'); ?></p>
                    <p>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-order-settings&tab=email')); ?>" class="button button-primary"><?php _e('E-Mail Einstellungen offnen', 'themisdb-order-request'); ?></a>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-support-tickets&tab=tickets')); ?>" class="button"><?php _e('Support-Tickets ansehen', 'themisdb-order-request'); ?></a>
                    </p>
                </div>
            </div>
            
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
                                    <?php echo esc_html($this->get_email_log_status_label($log['status'])); ?>
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
        // Handle test connection action
        if (isset($_GET['test_connection'])) {
            $test_result = ThemisDB_EPServer_API::test_connection();
            if ($test_result['success']) {
                echo '<div class="notice notice-success"><p>' . __('Verbindung erfolgreich!', 'themisdb-order-request') . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . __('Verbindung fehlgeschlagen:', 'themisdb-order-request') . ' ' . esc_html($test_result['message']) . '</p></div>';
            }
        }

        if (isset($_GET['sync'])) {
            if ($_GET['sync'] === 'success') {
                echo '<div class="notice notice-success"><p>' . __('Daten erfolgreich synchronisiert.', 'themisdb-order-request') . '</p></div>';
            } elseif ($_GET['sync'] === 'error') {
                echo '<div class="notice notice-error"><p>' . __('Synchronisierungsfehler.', 'themisdb-order-request') . '</p></div>';
            }
        }
        
        // Get current tab
        $active_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'integration';
        $allowed_tabs = array('integration', 'email', 'pdf', 'legal', 'license');
        if (!in_array($active_tab, $allowed_tabs, true)) {
            $active_tab = 'integration';
        }
        
        $settings_tabs = array(
            'integration' => __('epServer Integration', 'themisdb-order-request'),
            'email'       => __('E-Mail', 'themisdb-order-request'),
            'pdf'         => __('PDF', 'themisdb-order-request'),
            'legal'       => __('Rechtlich', 'themisdb-order-request'),
            'license'     => __('License API', 'themisdb-order-request'),
        );

        $tab_base = admin_url('admin.php?page=themisdb-order-settings');
        $test_connection_url = add_query_arg(
            array(
                'page' => 'themisdb-order-settings',
                'tab' => 'integration',
                'test_connection' => 1,
            ),
            admin_url('admin.php')
        );
        $sync_url = admin_url('admin-post.php?action=themisdb_sync_epserver');

        $integration_ready = get_option('themisdb_order_epserver_url') && get_option('themisdb_order_epserver_api_key');
        $email_sender = get_option('themisdb_order_email_from');
        $pdf_storage = get_option('themisdb_order_pdf_storage', 'database');
        $legal_enabled = get_option('themisdb_order_legal_compliance') === '1';
        $license_api_ready = get_option('themisdb_license_api_key') && get_option('themisdb_license_admin_secret');
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Einstellungen', 'themisdb-order-request'); ?></h1>
            <a href="<?php echo esc_url($test_connection_url); ?>" class="page-title-action"><?php _e('Verbindung testen', 'themisdb-order-request'); ?></a>
            <a href="<?php echo esc_url($sync_url); ?>" class="page-title-action"><?php _e('Daten synchronisieren', 'themisdb-order-request'); ?></a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-order-dashboard')); ?>" class="page-title-action"><?php _e('Zum Dashboard', 'themisdb-order-request'); ?></a>
            <hr class="wp-header-end">
            <?php $this->render_module_navigation_tabs('themisdb-order-settings'); ?>

            <div class="themisdb-admin-modules" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;margin:20px 0;">
                <div class="card" style="max-width:none;">
                    <h2><?php _e('Schnellaktionen', 'themisdb-order-request'); ?></h2>
                    <p><?php _e('Wichtige Wartungs- und Navigationsaktionen fur die Bestellplattform.', 'themisdb-order-request'); ?></p>
                    <p>
                        <a href="<?php echo esc_url($test_connection_url); ?>" class="button button-primary"><?php _e('Integration testen', 'themisdb-order-request'); ?></a>
                        <a href="<?php echo esc_url($sync_url); ?>" class="button"><?php _e('Produktdaten synchronisieren', 'themisdb-order-request'); ?></a>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-license-audit')); ?>" class="button"><?php _e('License Audit Log', 'themisdb-order-request'); ?></a>
                    </p>
                </div>
                <div class="card" style="max-width:none;">
                    <h2><?php _e('Aktiver Konfigurationsstatus', 'themisdb-order-request'); ?></h2>
                    <table class="widefat striped" style="border:none;box-shadow:none;">
                        <tbody>
                            <tr>
                                <td><?php _e('epServer Integration', 'themisdb-order-request'); ?></td>
                                <td><strong><?php echo esc_html($integration_ready ? __('Konfiguriert', 'themisdb-order-request') : __('Unvollstandig', 'themisdb-order-request')); ?></strong></td>
                            </tr>
                            <tr>
                                <td><?php _e('E-Mail Absender', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html($email_sender ? $email_sender : __('Nicht gesetzt', 'themisdb-order-request')); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('PDF Speicherung', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html($pdf_storage === 'filesystem' ? __('Dateisystem', 'themisdb-order-request') : __('Datenbank', 'themisdb-order-request')); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Rechtliche Prufungen', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html($legal_enabled ? __('Aktiv', 'themisdb-order-request') : __('Deaktiviert', 'themisdb-order-request')); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('License API', 'themisdb-order-request'); ?></td>
                                <td><strong><?php echo esc_html($license_api_ready ? __('Bereit', 'themisdb-order-request') : __('Nicht vollstandig konfiguriert', 'themisdb-order-request')); ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Settings Tab Navigation -->
            <?php $this->render_detail_page_tabs($settings_tabs, $active_tab); ?>

            <form method="post" action="options.php">
                <?php settings_fields('themisdb_order_settings'); ?>
                
                <!-- Integration Tab -->
                <?php $this->render_detail_tab_pane('integration', ($active_tab === 'integration')); ?>
                <section id="settings-integration">
                    <h2><?php _e('epServer Integration', 'themisdb-order-request'); ?></h2>
                    <?php
                    $order_page_id = $this->resolve_page_id_from_option('themisdb_order_page_url');
                    $product_page_id = $this->resolve_page_id_from_option('themisdb_product_page_url');
                    $order_page_raw = get_option('themisdb_order_page_url', '');
                    $product_page_raw = get_option('themisdb_product_page_url', '');
                    $order_has_legacy_url = is_string($order_page_raw) && $order_page_raw !== '' && !is_numeric($order_page_raw) && $order_page_id === 0;
                    $product_has_legacy_url = is_string($product_page_raw) && $product_page_raw !== '' && !is_numeric($product_page_raw) && $product_page_id === 0;
                    ?>
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
                        <th><label for="themisdb_order_page_url"><?php _e('Bestellfluss-Seite', 'themisdb-order-request'); ?></label></th>
                        <td>
                            <?php
                            echo wp_dropdown_pages(array(
                                'name' => 'themisdb_order_page_url',
                                'id' => 'themisdb_order_page_url',
                                'selected' => $order_page_id,
                                'show_option_none' => __('- Seite wählen -', 'themisdb-order-request'),
                                'option_none_value' => '',
                                'post_status' => array('publish', 'private', 'draft'),
                                'echo' => 0,
                            ));
                            ?>
                            <p class="description"><?php _e('Native WordPress-Seite mit [themisdb_order_flow]. Wenn leer, nutzt das Plugin eine automatische Erkennung.', 'themisdb-order-request'); ?></p>
                            <?php if ($order_has_legacy_url) : ?>
                                <p class="description" style="color:#8a6d3b;"><?php echo esc_html(sprintf(__('Aktuell ist noch eine Legacy-URL gespeichert: %s', 'themisdb-order-request'), $order_page_raw)); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="themisdb_product_page_url"><?php _e('Produkt-/Konfigurator-Seite', 'themisdb-order-request'); ?></label></th>
                        <td>
                            <?php
                            echo wp_dropdown_pages(array(
                                'name' => 'themisdb_product_page_url',
                                'id' => 'themisdb_product_page_url',
                                'selected' => $product_page_id,
                                'show_option_none' => __('- Seite wählen -', 'themisdb-order-request'),
                                'option_none_value' => '',
                                'post_status' => array('publish', 'private', 'draft'),
                                'echo' => 0,
                            ));
                            ?>
                            <p class="description"><?php _e('Native WordPress-Seite mit [themisdb_product_detail]. Wenn leer, fällt das Plugin auf die Bestellseite zurück.', 'themisdb-order-request'); ?></p>
                            <?php if ($product_has_legacy_url) : ?>
                                <p class="description" style="color:#8a6d3b;"><?php echo esc_html(sprintf(__('Aktuell ist noch eine Legacy-URL gespeichert: %s', 'themisdb-order-request'), $product_page_raw)); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th></th>
                        <td>
                            <a href="<?php echo esc_url($test_connection_url); ?>" class="button">
                                <?php _e('Verbindung testen', 'themisdb-order-request'); ?>
                            </a>
                            <a href="<?php echo esc_url($sync_url); ?>" class="button">
                                <?php _e('Daten synchronisieren', 'themisdb-order-request'); ?>
                            </a>
                        </td>
                    </tr>
                </table>
                </section>
                <?php $this->close_detail_tab_pane(); ?>
                
                <!-- Email Tab -->
                <?php $this->render_detail_tab_pane('email', ($active_tab === 'email')); ?>
                <section id="settings-email">
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
                </section>
                <?php $this->close_detail_tab_pane(); ?>
                
                <!-- PDF Tab -->
                <?php $this->render_detail_tab_pane('pdf', ($active_tab === 'pdf')); ?>
                <section id="settings-pdf">
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
                </section>
                <?php $this->close_detail_tab_pane(); ?>
                
                <!-- Legal Tab -->
                <?php $this->render_detail_tab_pane('legal', ($active_tab === 'legal')); ?>
                <section id="settings-legal">
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
                </section>
                <?php $this->close_detail_tab_pane(); ?>
                
                <!-- License API Tab -->
                <?php $this->render_detail_tab_pane('license', ($active_tab === 'license')); ?>
                <section id="settings-license">
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
                        <th><label for="themisdb_license_default_term_days"><?php _e('Default Renewal Term (days)', 'themisdb-order-request'); ?></label></th>
                        <td>
                            <input type="number" id="themisdb_license_default_term_days" name="themisdb_license_default_term_days"
                                   value="<?php echo esc_attr(get_option('themisdb_license_default_term_days', '365')); ?>"
                                   min="1" max="3650" class="small-text" />
                            <p class="description"><?php _e('Defines how many days are added on auto/manual renewal. Used for one-click renewal links as well.', 'themisdb-order-request'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="themisdb_license_allow_auto_renewal"><?php _e('Allow Auto-Renewal', 'themisdb-order-request'); ?></label></th>
                        <td>
                            <label>
                                <input type="checkbox" id="themisdb_license_allow_auto_renewal" name="themisdb_license_allow_auto_renewal" value="1"
                                       <?php checked(get_option('themisdb_license_allow_auto_renewal', '1'), '1'); ?> />
                                <?php _e('Enable automatic renewal when license usage_data.auto_renew_enabled is true.', 'themisdb-order-request'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="themisdb_affiliate_default_commission_rate"><?php _e('Affiliate Default Commission (%)', 'themisdb-order-request'); ?></label></th>
                        <td>
                            <input type="number" step="0.01" min="0" max="100" id="themisdb_affiliate_default_commission_rate" name="themisdb_affiliate_default_commission_rate"
                                   value="<?php echo esc_attr(get_option('themisdb_affiliate_default_commission_rate', '10')); ?>"
                                   class="small-text" />
                            <p class="description"><?php _e('Standard commission rate used for newly registered affiliates.', 'themisdb-order-request'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="themisdb_affiliate_cookie_days"><?php _e('Affiliate Cookie Lifetime (days)', 'themisdb-order-request'); ?></label></th>
                        <td>
                            <input type="number" min="1" max="365" id="themisdb_affiliate_cookie_days" name="themisdb_affiliate_cookie_days"
                                   value="<?php echo esc_attr(get_option('themisdb_affiliate_cookie_days', '30')); ?>"
                                   class="small-text" />
                            <p class="description"><?php _e('How long referral attribution remains active after a referral link click.', 'themisdb-order-request'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="themisdb_b2b_default_invoice_due_days"><?php _e('B2B Invoice Due (days)', 'themisdb-order-request'); ?></label></th>
                        <td>
                            <input type="number" min="1" max="365" id="themisdb_b2b_default_invoice_due_days" name="themisdb_b2b_default_invoice_due_days"
                                   value="<?php echo esc_attr(get_option('themisdb_b2b_default_invoice_due_days', '30')); ?>"
                                   class="small-text" />
                            <p class="description"><?php _e('Default due date offset for B2B invoice workflows and PO processing.', 'themisdb-order-request'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="themisdb_reporting_marketing_spend_json"><?php _e('Marketing Spend JSON (for CAC)', 'themisdb-order-request'); ?></label></th>
                        <td>
                            <textarea id="themisdb_reporting_marketing_spend_json" name="themisdb_reporting_marketing_spend_json" rows="4" class="large-text code"><?php echo esc_textarea(get_option('themisdb_reporting_marketing_spend_json', '{}')); ?></textarea>
                            <p class="description"><?php _e('Format: {"2026-01": 12000, "2026-02": 9800}. Used by Advanced Reporting to calculate CAC per acquisition month.', 'themisdb-order-request'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="themisdb_support_github_enabled"><?php _e('Support Ticket -> GitHub Sync', 'themisdb-order-request'); ?></label></th>
                        <td>
                            <label>
                                <input type="checkbox" id="themisdb_support_github_enabled" name="themisdb_support_github_enabled" value="1" <?php checked(get_option('themisdb_support_github_enabled', '0'), '1'); ?> />
                                <?php _e('Bei Bedarf GitHub-Issues aus Support-Tickets erzeugen.', 'themisdb-order-request'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="themisdb_support_github_repository"><?php _e('GitHub Repository', 'themisdb-order-request'); ?></label></th>
                        <td>
                            <input type="text" id="themisdb_support_github_repository" name="themisdb_support_github_repository"
                                   value="<?php echo esc_attr(get_option('themisdb_support_github_repository', '')); ?>"
                                   class="regular-text" placeholder="owner/repo" />
                            <p class="description"><?php _e('Format: owner/repo. Beispiel: makr-code/wordpressPlugins', 'themisdb-order-request'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="themisdb_support_github_token"><?php _e('GitHub Token', 'themisdb-order-request'); ?></label></th>
                        <td>
                            <input type="password" id="themisdb_support_github_token" name="themisdb_support_github_token"
                                   value="<?php echo esc_attr(get_option('themisdb_support_github_token', '')); ?>"
                                   class="regular-text" autocomplete="new-password" />
                            <p class="description"><?php _e('Empfohlen: Fine-grained PAT mit Permission Issues: Read and write.', 'themisdb-order-request'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="themisdb_support_github_labels"><?php _e('Standard Labels', 'themisdb-order-request'); ?></label></th>
                        <td>
                            <input type="text" id="themisdb_support_github_labels" name="themisdb_support_github_labels"
                                   value="<?php echo esc_attr(get_option('themisdb_support_github_labels', 'support,themisdb')); ?>"
                                   class="regular-text" />
                            <p class="description"><?php _e('Kommagetrennte Labels, z.B. support,themisdb,customer', 'themisdb-order-request'); ?></p>
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
                </section>
                <?php $this->close_detail_tab_pane(); ?>
                
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
            $this->abort_with_log(
                __('Keine Berechtigung', 'themisdb-order-request'),
                'Admin sync action denied due to missing capability',
                array('required_capability' => 'manage_options'),
                'warning'
            );
        }
        
        $result = ThemisDB_EPServer_API::sync_all();
        
        if ($result['products']) {
            wp_redirect(admin_url('admin.php?page=themisdb-order-settings&sync=success'));
        } else {
            wp_redirect(admin_url('admin.php?page=themisdb-order-settings&sync=error'));
        }
        exit;
    }

    /**
     * Support tickets admin page with optional GitHub issue sync.
     */
    public function support_tickets_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Keine Berechtigung', 'themisdb-order-request'));
        }

        if (!class_exists('ThemisDB_Order_Support_Ticket_Manager')) {
            echo '<div class="wrap"><div class="notice notice-error"><p>' .
                esc_html__('Support Ticket Manager ist nicht verfugbar.', 'themisdb-order-request') .
                '</p></div></div>';
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $post_action = sanitize_text_field(wp_unslash($_POST['action']));

            if ($post_action === 'themisdb_create_support_ticket') {
                check_admin_referer('themisdb_create_support_ticket');

                $result = ThemisDB_Order_Support_Ticket_Manager::create_ticket(array(
                    'subject' => sanitize_text_field(wp_unslash($_POST['subject'] ?? '')),
                    'description' => wp_kses_post(wp_unslash($_POST['description'] ?? '')),
                    'customer_email' => sanitize_email(wp_unslash($_POST['customer_email'] ?? '')),
                    'priority' => sanitize_key(wp_unslash($_POST['priority'] ?? 'normal')),
                    'status' => 'open',
                    'benefit_id' => isset($_POST['benefit_id']) ? intval($_POST['benefit_id']) : 0,
                    'license_id' => isset($_POST['license_id']) ? intval($_POST['license_id']) : 0,
                    'order_id' => isset($_POST['order_id']) ? intval($_POST['order_id']) : 0,
                    'auto_sync_github' => isset($_POST['auto_sync_github']) ? 1 : 0,
                ));

                if (is_wp_error($result)) {
                    $redirect_url = add_query_arg(
                        array(
                            'page' => 'themisdb-support-tickets',
                            'message' => 'ticket_error',
                            'error' => rawurlencode($result->get_error_message()),
                            'error_code' => rawurlencode((string) $result->get_error_code()),
                        ),
                        admin_url('admin.php')
                    );
                } else {
                    $redirect_url = add_query_arg(
                        array('page' => 'themisdb-support-tickets', 'message' => 'ticket_created', 'ticket_id' => intval($result)),
                        admin_url('admin.php')
                    );
                }

                wp_redirect($redirect_url);
                exit;
            }

            if ($post_action === 'themisdb_create_github_issue') {
                check_admin_referer('themisdb_create_github_issue');

                $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
                $result = ThemisDB_Order_Support_Ticket_Manager::create_github_issue_for_ticket($ticket_id);

                if (!empty($result['success'])) {
                    $redirect_url = add_query_arg(
                        array('page' => 'themisdb-support-tickets', 'message' => 'github_created', 'ticket_id' => $ticket_id),
                        admin_url('admin.php')
                    );
                } else {
                    $redirect_url = add_query_arg(
                        array('page' => 'themisdb-support-tickets', 'message' => 'github_error', 'error' => rawurlencode((string) ($result['message'] ?? 'Unknown error'))),
                        admin_url('admin.php')
                    );
                }

                wp_redirect($redirect_url);
                exit;
            }
        }

        $active_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'create';
        $page_tabs = array(
            'create' => __('Ticket erfassen', 'themisdb-order-request'),
            'tickets' => __('Ticket-Ubersicht', 'themisdb-order-request'),
        );
        if (!isset($page_tabs[$active_tab])) {
            $active_tab = 'create';
        }

        $status_filter = isset($_GET['status_tab']) ? sanitize_key(wp_unslash($_GET['status_tab'])) : 'all';
        $status_buttons = array(
            'all' => __('Alle', 'themisdb-order-request'),
            'open' => __('Offen', 'themisdb-order-request'),
            'in_progress' => __('In Bearbeitung', 'themisdb-order-request'),
            'resolved' => __('Gelost', 'themisdb-order-request'),
            'closed' => __('Geschlossen', 'themisdb-order-request'),
        );
        if (!isset($status_buttons[$status_filter])) {
            $status_filter = 'all';
        }

        $tickets = ThemisDB_Order_Support_Ticket_Manager::get_tickets(array(
            'status' => $status_filter === 'all' ? '' : $status_filter,
            'limit' => 200,
            'offset' => 0,
        ));
        $all_tickets = ThemisDB_Order_Support_Ticket_Manager::get_tickets(array(
            'status' => '',
            'limit' => 500,
            'offset' => 0,
        ));
        $open_count = 0;
        $github_linked_count = 0;
        foreach ($all_tickets as $ticket_item) {
            if (in_array($ticket_item['status'], array('open', 'in_progress'), true)) {
                $open_count++;
            }
            if (!empty($ticket_item['github_issue_number'])) {
                $github_linked_count++;
            }
        }
        $github_sync_enabled = get_option('themisdb_support_github_enabled', '0') === '1';

        if (isset($_GET['message']) && $_GET['message'] === 'ticket_created') {
            echo '<div class="notice notice-success"><p>' . esc_html__('Support-Ticket wurde erstellt.', 'themisdb-order-request') . '</p></div>';
        }
        if (isset($_GET['message']) && $_GET['message'] === 'ticket_error') {
            $error_code = sanitize_key(rawurldecode(wp_unslash($_GET['error_code'] ?? '')));
            $error_text = rawurldecode(wp_unslash($_GET['error'] ?? ''));

            if ($error_code === 'support_limit_reached') {
                echo '<div class="notice notice-warning"><p>' .
                    esc_html__('Support-Ticket blockiert: Das aktuelle Support-Limit wurde erreicht.', 'themisdb-order-request') .
                    ' ' . esc_html($error_text) .
                    '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html__('Support-Ticket konnte nicht erstellt werden.', 'themisdb-order-request') . ' ' . esc_html($error_text) . '</p></div>';
            }
        }
        if (isset($_GET['message']) && $_GET['message'] === 'github_created') {
            echo '<div class="notice notice-success"><p>' . esc_html__('GitHub-Issue wurde erstellt.', 'themisdb-order-request') . '</p></div>';
        }
        if (isset($_GET['message']) && $_GET['message'] === 'github_error') {
            echo '<div class="notice notice-error"><p>' . esc_html__('GitHub-Issue konnte nicht erstellt werden.', 'themisdb-order-request') . ' ' . esc_html(rawurldecode(wp_unslash($_GET['error'] ?? ''))) . '</p></div>';
        }

        $priorities = ThemisDB_Order_Support_Ticket_Manager::get_priorities();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Support Tickets', 'themisdb-order-request'); ?></h1>
            <a href="<?php echo esc_url(add_query_arg(array('page' => 'themisdb-support-tickets', 'tab' => 'create'), admin_url('admin.php'))); ?>" class="page-title-action"><?php _e('Neues Ticket', 'themisdb-order-request'); ?></a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-order-settings&tab=license')); ?>" class="page-title-action"><?php _e('GitHub Sync konfigurieren', 'themisdb-order-request'); ?></a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-order-dashboard')); ?>" class="page-title-action"><?php _e('Zum Dashboard', 'themisdb-order-request'); ?></a>
            <hr class="wp-header-end">
            <?php $this->render_module_navigation_tabs('themisdb-support-tickets'); ?>

            <div class="themisdb-admin-modules" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;margin:20px 0;">
                <div class="card" style="max-width:none;">
                    <h2><?php _e('Schnellaktionen', 'themisdb-order-request'); ?></h2>
                    <p><?php _e('Supportfalle erfassen, GitHub-Sync prufen und in die Auftragsdaten springen.', 'themisdb-order-request'); ?></p>
                    <p>
                        <a href="<?php echo esc_url(add_query_arg(array('page' => 'themisdb-support-tickets', 'tab' => 'create'), admin_url('admin.php'))); ?>" class="button button-primary"><?php _e('Ticket erfassen', 'themisdb-order-request'); ?></a>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-order-settings&tab=license')); ?>" class="button"><?php _e('GitHub/License Einstellungen', 'themisdb-order-request'); ?></a>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-orders')); ?>" class="button"><?php _e('Bestellungen offnen', 'themisdb-order-request'); ?></a>
                    </p>
                </div>
                <div class="card" style="max-width:none;">
                    <h2><?php _e('Supportstatus', 'themisdb-order-request'); ?></h2>
                    <table class="widefat striped" style="border:none;box-shadow:none;">
                        <tbody>
                            <tr>
                                <td><?php _e('Tickets gesamt', 'themisdb-order-request'); ?></td>
                                <td><strong><?php echo esc_html(number_format_i18n(count($all_tickets))); ?></strong></td>
                            </tr>
                            <tr>
                                <td><?php _e('Offen oder in Bearbeitung', 'themisdb-order-request'); ?></td>
                                <td><strong><?php echo esc_html(number_format_i18n($open_count)); ?></strong></td>
                            </tr>
                            <tr>
                                <td><?php _e('Mit GitHub verknupft', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html(number_format_i18n($github_linked_count)); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('GitHub-Sync', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html($github_sync_enabled ? __('Aktiv', 'themisdb-order-request') : __('Deaktiviert', 'themisdb-order-request')); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Aktiver Filter', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html($status_buttons[$status_filter]); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php $this->render_detail_page_tabs($page_tabs, $active_tab); ?>

            <?php $this->render_detail_tab_pane('create', ($active_tab === 'create')); ?>
            <div class="card" style="max-width:none; margin-bottom:20px;">
                <h2><?php _e('Neues Support-Ticket', 'themisdb-order-request'); ?></h2>
                <p><?php _e('Erfassen Sie neue Kundenanfragen mit Referenzen auf Lizenzen, Bestellungen oder Benefits.', 'themisdb-order-request'); ?></p>
                <form method="post">
                    <?php wp_nonce_field('themisdb_create_support_ticket'); ?>
                    <input type="hidden" name="action" value="themisdb_create_support_ticket">
                    <table class="form-table">
                        <tr>
                            <th><label for="support_subject"><?php _e('Betreff', 'themisdb-order-request'); ?></label></th>
                            <td><input id="support_subject" name="subject" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="support_customer_email"><?php _e('Kunden-E-Mail', 'themisdb-order-request'); ?></label></th>
                            <td><input id="support_customer_email" name="customer_email" type="email" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="support_priority"><?php _e('Prioritat', 'themisdb-order-request'); ?></label></th>
                            <td>
                                <select id="support_priority" name="priority">
                                    <?php foreach ($priorities as $priority_key => $priority_label): ?>
                                        <option value="<?php echo esc_attr($priority_key); ?>"><?php echo esc_html($priority_label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="support_license_id"><?php _e('License ID', 'themisdb-order-request'); ?></label></th>
                            <td><input id="support_license_id" name="license_id" type="number" min="0" class="small-text"></td>
                        </tr>
                        <tr>
                            <th><label for="support_order_id"><?php _e('Order ID', 'themisdb-order-request'); ?></label></th>
                            <td><input id="support_order_id" name="order_id" type="number" min="0" class="small-text"></td>
                        </tr>
                        <tr>
                            <th><label for="support_benefit_id"><?php _e('Benefit ID', 'themisdb-order-request'); ?></label></th>
                            <td><input id="support_benefit_id" name="benefit_id" type="number" min="0" class="small-text"></td>
                        </tr>
                        <tr>
                            <th><label for="support_description"><?php _e('Beschreibung', 'themisdb-order-request'); ?></label></th>
                            <td><textarea id="support_description" name="description" class="large-text" rows="6" required></textarea></td>
                        </tr>
                        <tr>
                            <th><label for="support_auto_sync_github"><?php _e('GitHub Sync', 'themisdb-order-request'); ?></label></th>
                            <td>
                                <label>
                                    <input id="support_auto_sync_github" name="auto_sync_github" type="checkbox" value="1" <?php checked($github_sync_enabled, true); ?>>
                                    <?php _e('Sofort ein GitHub-Issue erzeugen (wenn Repository/Token konfiguriert sind).', 'themisdb-order-request'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                    <p><button type="submit" class="button button-primary"><?php _e('Ticket erstellen', 'themisdb-order-request'); ?></button></p>
                </form>
            </div>
            <?php $this->close_detail_tab_pane(); ?>

            <?php $this->render_detail_tab_pane('tickets', ($active_tab === 'tickets')); ?>
            <div class="card" style="max-width:none;">
                <h2><?php _e('Tickets', 'themisdb-order-request'); ?></h2>
                <?php
                $this->render_filter_button_bar(
                    'status_tab',
                    $status_buttons,
                    $status_filter,
                    admin_url('admin.php?page=themisdb-support-tickets'),
                    array('tab' => 'tickets')
                );
                ?>
                <table class="wp-list-table widefat striped">
                    <thead>
                        <tr>
                            <th><?php _e('ID', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Betreff', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Prioritat', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Status', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Kunde', 'themisdb-order-request'); ?></th>
                            <th><?php _e('GitHub', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Erstellt', 'themisdb-order-request'); ?></th>
                            <th><?php _e('Aktionen', 'themisdb-order-request'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($tickets)): ?>
                        <tr><td colspan="8"><?php _e('Keine Tickets vorhanden.', 'themisdb-order-request'); ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($tickets as $ticket): ?>
                            <tr>
                                <td>#<?php echo absint($ticket['id']); ?></td>
                                <td>
                                    <strong><?php echo esc_html($ticket['subject']); ?></strong>
                                    <div style="margin-top:4px;color:#666;"><?php echo esc_html(wp_trim_words(wp_strip_all_tags((string) $ticket['description']), 20)); ?></div>
                                </td>
                                <td><?php echo esc_html($ticket['priority']); ?></td>
                                <td><?php echo esc_html($ticket['status']); ?></td>
                                <td><?php echo esc_html($ticket['customer_email']); ?></td>
                                <td>
                                    <?php if (!empty($ticket['github_issue_number']) && !empty($ticket['github_issue_url'])): ?>
                                        <a href="<?php echo esc_url($ticket['github_issue_url']); ?>" target="_blank" rel="noopener noreferrer">#<?php echo absint($ticket['github_issue_number']); ?></a>
                                    <?php elseif (!empty($ticket['github_sync_error'])): ?>
                                        <span style="color:#b32d2e;"><?php _e('Fehler', 'themisdb-order-request'); ?></span>
                                    <?php else: ?>
                                        <span style="color:#666;"><?php _e('Nicht verknupft', 'themisdb-order-request'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($ticket['created_at']); ?></td>
                                <td>
                                    <?php if (empty($ticket['github_issue_number'])): ?>
                                        <form method="post" style="display:inline;">
                                            <?php wp_nonce_field('themisdb_create_github_issue'); ?>
                                            <input type="hidden" name="action" value="themisdb_create_github_issue">
                                            <input type="hidden" name="ticket_id" value="<?php echo absint($ticket['id']); ?>">
                                            <button type="submit" class="button button-small"><?php _e('GitHub-Issue erstellen', 'themisdb-order-request'); ?></button>
                                        </form>
                                    <?php else: ?>
                                        <span>&mdash;</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php $this->close_detail_tab_pane(); ?>
        </div>
        <?php
    }

    // =========================================================================
    // Bank Import Page
    // =========================================================================

    /**
     * Route the bank import page based on action parameter.
     */
    public function bank_import_page() {
        if (!current_user_can('manage_options')) {
            $this->abort_with_log(
                __('Keine Berechtigung', 'themisdb-order-request'),
                'Bank import page denied due to missing capability',
                array('required_capability' => 'manage_options'),
                'warning'
            );
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
        $import_total = count($imports);
        $rows_total = 0;
        $rows_matched = 0;
        $rows_unmatched = 0;

        foreach ($imports as $import_item) {
            $rows_total += (int) $import_item['rows_total'];
            $rows_matched += (int) $import_item['rows_matched'];
            $rows_unmatched += (int) $import_item['rows_unmatched'];
        }
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Bankimport – Zahlungsabgleich', 'themisdb-order-request'); ?></h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-bank-import')); ?>" class="page-title-action"><?php _e('Neue CSV laden', 'themisdb-order-request'); ?></a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-payments')); ?>" class="page-title-action"><?php _e('Zahlungen ansehen', 'themisdb-order-request'); ?></a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-order-dashboard')); ?>" class="page-title-action"><?php _e('Zum Dashboard', 'themisdb-order-request'); ?></a>
            <hr class="wp-header-end">
            <?php $this->render_module_navigation_tabs('themisdb-bank-import'); ?>

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
                <strong><?php _e('Transaktion erfolgreich zugeordnet und verifiziert.', 'themisdb-order-request'); ?></strong>
            </p></div>
            <?php endif; ?>

            <div class="themisdb-admin-modules" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;margin:20px 0;">
                <div class="card" style="max-width:none;">
                    <h2><?php _e('Importstatus', 'themisdb-order-request'); ?></h2>
                    <table class="widefat striped" style="border:none;box-shadow:none;">
                        <tbody>
                            <tr>
                                <td><?php _e('Importe im Verlauf', 'themisdb-order-request'); ?></td>
                                <td><strong><?php echo esc_html(number_format_i18n($import_total)); ?></strong></td>
                            </tr>
                            <tr>
                                <td><?php _e('Verarbeitete Zeilen', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html(number_format_i18n($rows_total)); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Automatisch gematcht', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html(number_format_i18n($rows_matched)); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Noch offen', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html(number_format_i18n($rows_unmatched)); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="card" style="max-width:none;">
                    <h2><?php _e('Schnellaktionen', 'themisdb-order-request'); ?></h2>
                    <p><?php _e('Neue Kontoauszuge hochladen, offene Zahlungen prufen und in die Zahlungsverwaltung wechseln.', 'themisdb-order-request'); ?></p>
                    <p>
                        <a href="#bankimport-upload" class="button button-primary"><?php _e('Zur Upload-Maske', 'themisdb-order-request'); ?></a>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-payments')); ?>" class="button"><?php _e('Offene Zahlungen', 'themisdb-order-request'); ?></a>
                    </p>
                </div>
            </div>

            <!-- Reference guide box -->
            <div class="notice notice-info" style="padding:12px 16px;">
                <h3 style="margin-top:0;">
                    <?php _e('Welche Referenz gehört auf den Überweisungsträger?', 'themisdb-order-request'); ?>
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
            <div id="bankimport-upload" class="card" style="max-width:none; margin:20px 0;">
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
            <h1 class="wp-heading-inline"><?php _e('Bankimport – Vorschau', 'themisdb-order-request'); ?></h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-bank-import')); ?>" class="page-title-action"><?php _e('Zur Upload-Seite', 'themisdb-order-request'); ?></a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-payments')); ?>" class="page-title-action"><?php _e('Zahlungen ansehen', 'themisdb-order-request'); ?></a>
            <hr class="wp-header-end">
            <?php $this->render_module_navigation_tabs('themisdb-bank-import'); ?>

            <div class="themisdb-admin-modules" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:20px;margin:20px 0;">
                <div class="card" style="max-width:none;">
                    <h2><?php _e('Vorschau-Status', 'themisdb-order-request'); ?></h2>
                    <table class="widefat striped" style="border:none;box-shadow:none;">
                        <tbody>
                            <tr>
                                <td><?php _e('Datei', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html($data['filename']); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Gesamtzeilen', 'themisdb-order-request'); ?></td>
                                <td><strong><?php echo esc_html(number_format_i18n((int) $counts['total'])); ?></strong></td>
                            </tr>
                            <tr>
                                <td><?php _e('Gematcht', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html(number_format_i18n((int) $counts['matched'])); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Offen', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html(number_format_i18n((int) $counts['unmatched'])); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="card" style="max-width:none;">
                    <h2><?php _e('Nächste Schritte', 'themisdb-order-request'); ?></h2>
                    <p><?php _e('Prufen Sie Matchings, bestatigen Sie den Import oder brechen Sie zur Korrektur des Dateiformats ab.', 'themisdb-order-request'); ?></p>
                    <p>
                        <a href="#bankimport-confirm" class="button button-primary"><?php _e('Zum Import-Button', 'themisdb-order-request'); ?></a>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-bank-import')); ?>" class="button"><?php _e('Abbrechen', 'themisdb-order-request'); ?></a>
                    </p>
                </div>
            </div>

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
                id="bankimport-confirm"
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
        $counts = $this->bank_import_count_statuses($transactions);
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Bankimport – Detailansicht', 'themisdb-order-request'); ?></h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-bank-import')); ?>" class="page-title-action"><?php _e('Zur Übersicht', 'themisdb-order-request'); ?></a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-payments')); ?>" class="page-title-action"><?php _e('Zahlungen ansehen', 'themisdb-order-request'); ?></a>
            <hr class="wp-header-end">
            <?php $this->render_module_navigation_tabs('themisdb-bank-import'); ?>

            <?php if (isset($_GET['imported'])): ?>
            <div class="notice notice-success">
                <p><?php _e('Import erfolgreich abgeschlossen. Gematchte Zahlungen wurden automatisch verifiziert.', 'themisdb-order-request'); ?></p>
            </div>
            <?php endif; ?>

            <div class="themisdb-admin-modules" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:20px;margin:20px 0;">
                <div class="card" style="max-width:none;">
                    <h2><?php _e('Importsitzung', 'themisdb-order-request'); ?></h2>
                    <table class="widefat striped" style="border:none;box-shadow:none;">
                        <tbody>
                            <tr>
                                <td><?php _e('Gesamt', 'themisdb-order-request'); ?></td>
                                <td><strong><?php echo esc_html(number_format_i18n((int) $counts['total'])); ?></strong></td>
                            </tr>
                            <tr>
                                <td><?php _e('Gematcht', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html(number_format_i18n((int) $counts['matched'])); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Offen', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html(number_format_i18n((int) $counts['unmatched'])); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Duplikate', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html(number_format_i18n((int) $counts['duplicate'])); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="card" style="max-width:none;">
                    <h2><?php _e('Schnellaktionen', 'themisdb-order-request'); ?></h2>
                    <p><?php _e('Offene Buchungen manuell zuordnen oder in die Zahlungsverwaltung wechseln.', 'themisdb-order-request'); ?></p>
                    <p>
                        <a href="#bankimport-transactions" class="button button-primary"><?php _e('Zu den Transaktionen', 'themisdb-order-request'); ?></a>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-payments')); ?>" class="button"><?php _e('Zahlungen offnen', 'themisdb-order-request'); ?></a>
                    </p>
                </div>
            </div>

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
            
            <div id="bankimport-transactions" class="card" style="max-width:none;">
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
            <h1 class="wp-heading-inline"><?php _e('Transaktion manuell zuordnen', 'themisdb-order-request'); ?></h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-bank-import&action=view&import_id=' . absint($tx['import_id']))); ?>" class="page-title-action"><?php _e('Zum Import zurück', 'themisdb-order-request'); ?></a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=themisdb-payments')); ?>" class="page-title-action"><?php _e('Zahlungen ansehen', 'themisdb-order-request'); ?></a>
            <hr class="wp-header-end">
            <?php $this->render_module_navigation_tabs('themisdb-bank-import'); ?>

            <div class="themisdb-admin-modules" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:20px;margin:20px 0;">
                <div class="card" style="max-width:none;">
                    <h2><?php _e('Zuordnungsstatus', 'themisdb-order-request'); ?></h2>
                    <table class="widefat striped" style="border:none;box-shadow:none;">
                        <tbody>
                            <tr>
                                <td><?php _e('Import-ID', 'themisdb-order-request'); ?></td>
                                <td><strong><?php echo esc_html(number_format_i18n((int) $tx['import_id'])); ?></strong></td>
                            </tr>
                            <tr>
                                <td><?php _e('Transaktionsbetrag', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float) $tx['amount'], 2)); ?> <?php echo esc_html($tx['currency']); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Ausstehende Zahlungen', 'themisdb-order-request'); ?></td>
                                <td><?php echo esc_html(number_format_i18n(count($pending_payments))); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="card" style="max-width:none;">
                    <h2><?php _e('Hinweis', 'themisdb-order-request'); ?></h2>
                    <p><?php _e('Wahlen Sie nur eine fachlich passende offene Zahlung aus. Die Zuordnung verifiziert den Zahlungseintrag direkt.', 'themisdb-order-request'); ?></p>
                </div>
            </div>

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
