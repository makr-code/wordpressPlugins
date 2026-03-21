# ThemisDB WordPress Plugins - Implementierungs-Roadmap
**Version:** 1.0  
**Datum:** 20. März 2026  
**Status:** 🟠 In Planung | Sistematische Implementierung

---

## 📌 Executive Summary

Das ThemisDB WordPress Plugin-Ökosystem ist zu **90% Feature-Complete**, benötigt aber systematische Implementierung von **3 kritischen Komponenten**:

1. **Support-System Integration** (Order → License → Support Benefits)
2. **Frontend Shop Completion** (WooCommerce Bridge, Payment Gateways)
3. **Security & Compliance Audit** (SQL-Injection, Error Handling)

**Geschätzte Gesamtdauer:** 12-19 Wochen | **Team:** 1-2 Senior Developer

---

## 🎯 Ziele

### Geschäftliche Ziele
- ✅ Automatischer Support-Zugang für alle Besteller (Conversion +15%)
- ✅ Vollständiger Shop-Workflow (Revenue +30%)
- ✅ Production-Ready Sicherheit (Compliance erfüllt)
- ✅ Tier-basierte Support-Level (Upsell Möglichkeit)

### Technische Ziele
- ✅ Zero SQL-Injection Vulnerabilities
- ✅ 95% Error-Handling Coverage
- ✅ Full Audit Trail für Compliance
- ✅ REST API für zukünftige Mobile Apps

---

## 📊 Aktueller Status (vor Roadmap)

| Komponente | Status | Score | Impact |
|------------|--------|-------|--------|
| **Order Management** | ✅ Complete | 100% | Baseline |
| **License System** | ✅ Complete | 100% | Critical |
| **PDF Contracts** | ✅ Complete | 100% | Important |
| **Admin Interface** | ✅ Complete + Tabs | 95% | Important |
| **Support Integration** | ❌ Missing | 0% | CRITICAL |
| **Frontend Shop** | ⚠️ Partial | 50% | HIGH |
| **Payment Gateways** | ⚠️ Bank-Transfer only | 30% | HIGH |
| **Security Audit** | ⚠️ Partial | 60% | MEDIUM |
| **Monitoring** | ❌ Missing | 0% | MEDIUM |

**Baseline:** 90% Feature-Complete, aber Lücken in User-Facing Features

---

## 🔮 Phase Overview

```
PHASE 1: Support Integration (Wochen 1-3)
   └─ Critical Path für Geschäft
   └─ Abhängigkeit: Nichts

PHASE 2: Frontend Shop (Wochen 4-7)
   └─ Revenue Generator
   └─ Abhängigkeit: Phase 1 optional

PHASE 3: Security & Compliance (Wochen 8-9)
   └─ Go-Live Prerequisite
   └─ Abhängigkeit: Phase 1 + 2

PHASE 4: Monitoring & UX (Wochen 10-11)
   └─ Production Stability
   └─ Abhängigkeit: Phase 3

PHASE 5+: Advanced Features (Wochen 12-19)
   └─ Langfristige Differenzierung
   └─ Abhängigkeit: Phase 4
```

---

# 📋 PHASE 1: Support-System Integration

## 🎯 Ziele
- Automatischer Support-Zugang nach Bestellung
- Tier-basierte Support-Limits & SLA (Community/Enterprise/Hyperscaler/Reseller)
- Support-Portal Integration mit Order/License System
- Admin Dashboard für Support-Metrics

## 📦 Komponenten

### 1.1 Database: Support Benefits Table
**Status:** 📝 TODO | **Priority:** 🔴 Critical | **Est. Time:** 0.5 Woche

#### Schema
```sql
CREATE TABLE wp_themisdb_support_benefits (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  license_id BIGINT NOT NULL UNIQUE,
  tier_level VARCHAR(50) NOT NULL, -- community|enterprise|hyperscaler|reseller
  
  -- Limits
  max_open_tickets INT DEFAULT -1, -- -1 = unlimited
  max_tickets_per_month INT DEFAULT -1,
  response_sla_hours INT, -- 24|8|4|2 hours
  priority_can_assign BOOLEAN DEFAULT 0,
  included_hours_per_month INT DEFAULT 0,
  
  -- Status
  benefit_status VARCHAR(50) DEFAULT 'active', -- active|suspended|expired
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  activated_at DATETIME,
  expires_at DATETIME,
  
  -- Tracking
  tickets_used_this_month INT DEFAULT 0,
  hours_used_this_month DECIMAL(10,2) DEFAULT 0,
  last_reset DATETIME,
  
  KEY license_id (license_id),
  KEY tier_level (tier_level),
  KEY benefit_status (benefit_status),
  CONSTRAINT fk_license FOREIGN KEY (license_id) REFERENCES wp_themisdb_licenses(id) ON DELETE CASCADE
);
```

#### Tier Configuration (Default)
```php
$tier_config = array(
  'community' => array(
    'max_open_tickets' => 5,
    'max_tickets_per_month' => 12,
    'response_sla_hours' => 48,
    'priority_can_assign' => false,
    'included_hours_per_month' => 0,
  ),
  'enterprise' => array(
    'max_open_tickets' => -1, // unlimited
    'max_tickets_per_month' => -1,
    'response_sla_hours' => 8,
    'priority_can_assign' => true,
    'included_hours_per_month' => 40,
  ),
  'hyperscaler' => array(
    'max_open_tickets' => -1,
    'max_tickets_per_month' => -1,
    'response_sla_hours' => 4,
    'priority_can_assign' => true,
    'included_hours_per_month' => -1, // unlimited
  ),
  'reseller' => array(
    'max_open_tickets' => -1,
    'max_tickets_per_month' => -1,
    'response_sla_hours' => 2,
    'priority_can_assign' => true,
    'included_hours_per_month' => -1,
  ),
);
```

#### Implementation Tasks
1. Migration Script erstellen für `class-database.php`
2. Support Benefits Manager Klasse: `class-support-benefits-manager.php`
   - `create_for_license($license_id, $tier_level)`
   - `get_by_license($license_id)`
   - `activate($benefit_id)`
   - `suspend($benefit_id, $reason)`
   - `deactivate($benefit_id)`
   - `check_limits($benefit_id, $ticket_type)`
   - `reset_monthly_counts()` (Cron-Job täglich um 00:00)
3. Settings Page: Tier-Konfig editierbar machen

---

### 1.2 License Manager Integration
**Status:** 📝 TODO | **Priority:** 🔴 Critical | **Est. Time:** 1 Woche

#### Hooks in `class-license-manager.php`

**A) `create_license()` - Auto-create Benefits**
```php
public static function create_license($data) {
    // ... existing code ...
    $license_id = $wpdb->insert_id;
    
    // NEW: Auto-create support benefits
    $tier_level = self::get_tier_from_edition($data['product_edition']);
    ThemisDB_Support_Benefits_Manager::create_for_license($license_id, $tier_level);
    
    // Log integration
    error_log("Support Benefits created for License ID: $license_id, Tier: $tier_level");
    
    return $license_id;
}
```

**B) `activate_license()` - Activate Benefits**
```php
public static function activate_license($license_id) {
    // ... existing code ...
    
    // NEW: Activate support benefits
    $benefit_id = ThemisDB_Support_Benefits_Manager::get_benefit_id_by_license($license_id);
    if ($benefit_id) {
        ThemisDB_Support_Benefits_Manager::activate($benefit_id);
    }
    
    return $result;
}
```

**C) `suspend_license()` - Suspend Benefits**
```php
public static function suspend_license($license_id, $reason = '') {
    // ... existing code ...
    
    // NEW: Suspend support benefits
    $benefit_id = ThemisDB_Support_Benefits_Manager::get_benefit_id_by_license($license_id);
    if ($benefit_id) {
        ThemisDB_Support_Benefits_Manager::suspend($benefit_id, $reason ?: 'License suspended');
    }
    
    return true;
}
```

**D) `cancel_license()` - Deactivate Benefits**
```php
public static function cancel_license($license_id, $reason = '', $cancelled_by = 0) {
    // ... existing code ...
    
    // NEW: Deactivate support benefits
    $benefit_id = ThemisDB_Support_Benefits_Manager::get_benefit_id_by_license($license_id);
    if ($benefit_id) {
        ThemisDB_Support_Benefits_Manager::deactivate($benefit_id);
    }
    
    return true;
}
```

#### New Helper Method
```php
private static function get_tier_from_edition($edition) {
    $tier_map = array(
        'community' => 'community',
        'enterprise' => 'enterprise',
        'hyperscaler' => 'hyperscaler',
        'reseller' => 'reseller',
    );
    return isset($tier_map[$edition]) ? $tier_map[$edition] : 'community';
}
```

#### Implementation Tasks
1. `ThemisDB_Support_Benefits_Manager` Klasse erstellen
2. Alle 4 Hooks in `create_license()`, `activate_license()`, `suspend_license()`, `cancel_license()` hinzufügen
3. Testing: Order → License → Support Benefits Kette
4. Edge Cases: Was passiert wenn License gelöscht wird? (Cascade delete)

---

### 1.3 Support Portal Enhancement
**Status:** 📝 TODO | **Priority:** 🔴 Critical | **Est. Time:** 1 Woche

#### A) Ticket Creation Validation
In `class-ticket-manager.php`:

```php
public static function create_ticket($data) {
    // NEW: Check support benefits
    $license_key = $data['license_key'] ?? null;
    $user_id = $data['user_id'] ?? null;
    
    if (!$license_key && !$user_id) {
        return array('success' => false, 'error' => 'License key or user ID required');
    }
    
    // Get support benefits
    $benefit_check = self::check_support_benefit($license_key, $user_id);
    if (!$benefit_check['allowed']) {
        return array(
            'success' => false,
            'error' => $benefit_check['reason'], // "Max 5 open tickets for Community tier"
        );
    }
    
    // ... rest of ticket creation ...
}

private static function check_support_benefit($license_key, $user_id) {
    // Find license
    if ($license_key) {
        $license = ThemisDB_License_Manager::get_license_by_key($license_key);
    } elseif ($user_id) {
        $license_id = get_user_meta($user_id, 'themisdb_license_id', true);
        $license = ThemisDB_License_Manager::get_license($license_id);
    }
    
    if (!$license || $license['license_status'] !== 'active') {
        return array(
            'allowed' => false,
            'reason' => 'License inactive or not found',
        );
    }
    
    // Get support benefits
    $benefit = ThemisDB_Support_Benefits_Manager::get_by_license($license['id']);
    if (!$benefit) {
        return array(
            'allowed' => false,
            'reason' => 'No support benefits tied to this license',
        );
    }
    
    if ($benefit['benefit_status'] !== 'active') {
        return array(
            'allowed' => false,
            'reason' => "Support benefits {$benefit['benefit_status']}",
        );
    }
    
    // Check limits
    $limit_check = ThemisDB_Support_Benefits_Manager::check_limits($benefit['id'], 'create');
    if (!$limit_check['allowed']) {
        return array(
            'allowed' => false,
            'reason' => $limit_check['reason'], // "Max 5 open tickets reached for Community tier"
        );
    }
    
    return array('allowed' => true, 'benefit_id' => $benefit['id']);
}
```

#### B) Support Portal Shortcode Enhancement
In `class-shortcodes.php`:

```php
public function support_portal_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>Please log in to access support</p>';
    }
    
    $user_id = get_current_user_id();
    $license_id = get_user_meta($user_id, 'themisdb_license_id', true);
    
    if (!$license_id) {
        return '<p>No active support license found</p>';
    }
    
    $benefit = ThemisDB_Support_Benefits_Manager::get_by_license($license_id);
    
    ob_start();
    ?>
    <div class="support-portal">
        <!-- NEW: Support Benefit Info Banner -->
        <div class="support-benefit-banner">
            <h3>Your Support Level</h3>
            <p>
                <strong>Tier:</strong> <?php echo ucfirst($benefit['tier_level']); ?><br>
                <strong>Status:</strong> <?php echo ucfirst($benefit['benefit_status']); ?><br>
                <strong>Response Time:</strong> <?php echo $benefit['response_sla_hours']; ?> hours<br>
                <strong>Open Tickets:</strong> 
                <?php 
                echo $benefit['max_open_tickets'] === -1 
                    ? 'Unlimited' 
                    : "{$benefit['tickets_used_this_month']} / {$benefit['max_open_tickets']}"; 
                ?>
            </p>
            <?php if ($benefit['expires_at']): ?>
                <p><em>Supported until: <?php echo date('d.m.Y', strtotime($benefit['expires_at'])); ?></em></p>
            <?php endif; ?>
        </div>
        
        <!-- Create Ticket Form (with limit check UI) -->
        <div class="create-ticket-form">
            <?php if ($benefit['max_open_tickets'] !== -1 && $benefit['tickets_used_this_month'] >= $benefit['max_open_tickets']): ?>
                <div class="alert alert-warning">
                    Maximum open tickets reached for your tier. 
                    <?php if ($benefit['tier_level'] === 'community'): ?>
                        <a href="?upgrade">Upgrade to Enterprise</a> for unlimited support.
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Form to create ticket -->
            <?php endif; ?>
        </div>
        
        <!-- Existing tickets list -->
        <?php // ... rest of portal ...
    }
    ?>
    <?php
    return ob_get_clean();
}
```

#### C) Admin Dashboard Widget
In `class-admin.php`:

```php
// Add Support Benefits widget to order/contract view pages
private function render_support_benefits_widget($license_id) {
    $benefit = ThemisDB_Support_Benefits_Manager::get_by_license($license_id);
    
    if (!$benefit) {
        echo '<div class="card"><p>No support benefits found</p></div>';
        return;
    }
    
    echo '<div class="card" style="background-color:#e8f5e9;">';
    echo '<h3>Support Benefits</h3>';
    echo '<table class="form-table">';
    echo '<tr><th>Tier:</th><td>' . ucfirst($benefit['tier_level']) . '</td></tr>';
    echo '<tr><th>Status:</th><td>' . ucfirst($benefit['benefit_status']) . '</td></tr>';
    echo '<tr><th>Max Open Tickets:</th><td>' . ($benefit['max_open_tickets'] === -1 ? 'Unlimited' : $benefit['max_open_tickets']) . '</td></tr>';
    echo '<tr><th>Response SLA:</th><td>' . $benefit['response_sla_hours'] . ' hours</td></tr>';
    if ($benefit['expires_at']) {
        echo '<tr><th>Expires:</th><td>' . date('d.m.Y', strtotime($benefit['expires_at'])) . '</td></tr>';
    }
    echo '</table>';
    echo '</div>';
}
```

#### Implementation Tasks
1. Ticket creation limits enforcer
2. Support Portal UI enhancements
3. Admin dashboard widget
4. Testing: Ticket creation blocked at limit
5. Testing: Different tiers behave differently

---

### 1.4 Cron Jobs & Maintenance
**Status:** 📝 TODO | **Priority:** 🟡 Important | **Est. Time:** 0.5 Woche

#### Monthly Reset Job
```php
// In themisdb-order-request.php main file
add_action('wp_scheduled_event_themisdb_support_reset', 'themisdb_reset_support_monthly_counts');

function themisdb_reset_support_monthly_counts() {
    global $wpdb;
    
    $table = $wpdb->prefix . 'themisdb_support_benefits';
    
    $wpdb->query("
        UPDATE $table 
        SET tickets_used_this_month = 0, 
            hours_used_this_month = 0,
            last_reset = NOW()
        WHERE benefit_status = 'active'
    ");
    
    error_log('Support benefits monthly counts reset');
}

// Schedule on plugin activation
function themisdb_schedule_support_events() {
    if (!wp_next_scheduled('wp_scheduled_event_themisdb_support_reset')) {
        wp_schedule_event(time(), 'daily', 'wp_scheduled_event_themisdb_support_reset');
    }
}
```

#### Expiry Check Job
```php
add_action('wp_scheduled_event_themisdb_support_expiry_check', 'themisdb_check_support_expiry');

function themisdb_check_support_expiry() {
    global $wpdb;
    
    $table = $wpdb->prefix . 'themisdb_support_benefits';
    
    // Find expired benefits
    $expired = $wpdb->get_results("
        SELECT id, license_id FROM $table 
        WHERE benefit_status = 'active' 
        AND expires_at IS NOT NULL 
        AND expires_at < NOW()
    ");
    
    foreach ($expired as $benefit) {
        ThemisDB_Support_Benefits_Manager::deactivate($benefit->id);
        
        // Send email to customer
        // ...
    }
}
```

---

## 📊 Phase 1 Success Metrics

| Metrik | Target | Überprüfung |
|--------|--------|------------|
| Support Benefits created automatically | 100% | Check DB after order flow |
| Ticket limit enforcement | 100% | Try to create 6th ticket on Community |
| License status sync | 100% | Suspend license → verify benefits suspended |
| Monthly reset funktioniert | 100% | Manual trigger, observe reset |
| Admin UI shows benefits | 100% | View license → see benefit widget |

---

## ⚠️ Phase 1 Risks & Mitigations

| Risk | Impact | Mitigation |
|------|--------|-----------|
| Database migration fails | 🔴 High | Test migration script on staging first |
| Existing licenses ohne Benefits | 🟡 Medium | Migration script für alte Lizenzen |
| Performance impact (monthly reset) | 🟡 Medium | Batch job, nicht per ticket |
| Support limit enforcement missing in edge cases | 🔴 High | Comprehensive test coverage |

---

---

# 📋 PHASE 2: Frontend Shop Completion

## 🎯 Ziele
- Vollständiger Shop-Workflow im Frontend (Checkout nicht nur Administration)
- WooCommerce Integration (optional aber recommended)
- Payment Gateways (Stripe, PayPal, Bank Transfer)
- Express Checkout (schneller Weg für Repeat Customers)

## 📦 Komponenten

### 2.1 WooCommerce Bridge
**Status:** 📝 TODO | **Priority:** 🟠 High | **Est. Time:** 1.5 Wochen

**Ziel:** Besteller können über WooCommerce kaufen, Bestellungen landen in ThemisDB

#### Architecture
```
WooCommerce Order
        ↓
WooCommerce Webhook
        ↓
Themisdb_Order_Request
        ↓
Create Order in themisdb_orders
        ↓
Create License (if applicable)
        ↓
Send Confirmation Email
```

#### Implementation
1. **New Plugin:** `themisdb-woo-bridge.php`
2. **Product Sync:** WooCommerce Products ← → ThemisDB Products
3. **Order Sync:** WooCommerce Orders → ThemisDB Orders
4. **License Generation:** Nach WooCommerce Order Completion
5. **Webhook Handler:** Payment Status Updates

#### Code Structure
```php
// themisdb-woo-bridge.php
class ThemisDB_WooCommerce_Bridge {
    
    public function __construct() {
        // Product sync
        add_action('woocommerce_product_set_stock', array($this, 'sync_product_stock'));
        add_action('woocommerce_product_sync', array($this, 'sync_product_from_woo'));
        
        // Order sync
        add_action('woocommerce_order_status_completed', array($this, 'on_order_completed'));
        add_action('woocommerce_payment_complete', array($this, 'on_payment_complete'));
        
        // License generation
        add_action('woocommerce_thankyou', array($this, 'maybe_generate_license'));
    }
    
    public function on_order_completed($order_id) {
        $woo_order = wc_get_order($order_id);
        
        // Map WooCommerce Order to ThemisDB Order
        $themisdb_order_data = $this->map_woo_order($woo_order);
        
        // Create order in ThemisDB
        $order_id = ThemisDB_Order_Manager::create_order($themisdb_order_data);
        
        // Store mapping
        update_post_meta($woo_order->get_id(), '_themisdb_order_id', $order_id);
    }
    
    public function maybe_generate_license($order_id) {
        $themisdb_order_id = get_post_meta($order_id, '_themisdb_order_id', true);
        if ($themisdb_order_id) {
            $order = ThemisDB_Order_Manager::get_order($themisdb_order_id);
            if ($order['product_type'] === 'license') {
                // License generation logic
            }
        }
    }
}
```

---

### 2.2 Product Detail Page Template
**Status:** 📝 TODO | **Priority:** 🟠 High | **Est. Time:** 1 Woche

**Ziel:** Single Product Page mit Produkt-Info, Module, Pricing, Support-Level

#### Block Editor Template (FSE)
```json
// In theme: /block-templates/single-themisdb-product.html
{
  "version": 2,
  "isGlobalStylesUserThemeJSON": false,
  "body": [
    {
      "blockName": "core/group",
      "attrs": { "tagName": "header", "className": "product-header" },
      "innerBlocks": [
        { "blockName": "core/heading", "attrs": { "level": 1, "content": "Product Name" } },
        { "blockName": "core/paragraph", "attrs": { "content": "Product Description" } }
      ]
    },
    {
      "blockName": "themisdb/product-selector",
      "attrs": { "showModules": true, "showTraining": true }
    },
    {
      "blockName": "themisdb/pricing-calculator"
    },
    {
      "blockName": "themisdb/support-level-selector"
    },
    {
      "blockName": "core/button",
      "attrs": { "text": "Order Now", "url": "/order-flow" }
    }
  ]
}
```

#### Components
1. **Product Showcase Block**
   - High-res product image
   - Title, Description
   - Edition selector (Community/Enterprise/Hyperscaler)

2. **Module Selector**
   - Checkboxes für optionale Module
   - Real-time pricing update
   - Category grouping

3. **Training Selector**
   - Dropdown für Training-Optionen
   - Dauer anzeigen
   - Preis update

4. **Pricing Calculator**
   - Basis-Produktpreis
   - Module-Preise dynamisch
   - Training-Preise dynamisch
   - **Total in Echtzeit aktualisieren**

5. **Support Level Selector**
   - Vergleich-Tabelle (Community vs Enterprise vs Hyperscaler)
   - Response SLA
   - Included hours
   - "Upgrade included" Badge bei höherer Edition

#### JavaScript für Live-Pricing
```js
// In /assets/js/product-selector.js
jQuery(function($) {
    const calculateTotal = () => {
        let total = basePrice;
        
        // Add selected modules
        $('.module-checkbox:checked').each(function() {
            total += parseFloat($(this).data('price'));
        });
        
        // Add training if selected
        if ($('#training-select').val()) {
            total += parseFloat($('#training-select').find(':selected').data('price'));
        }
        
        // Update UI
        $('#total-price').text(total.toFixed(2) + ' €');
        $('#order-button').attr('data-total', total);
    };
    
    $(document).on('change', '.module-checkbox, #training-select', calculateTotal);
});
```

---

### 2.3 Shopping Cart UI
**Status:** 📝 TODO | **Priority:** 🟡 Important | **Est. Time:** 0.5 Wochen

**Ziel:** Visuelle Warenkorb-Übersicht (aktuell nur Order Flow ohne Warenkorb-Icon)

#### Shortcode
```php
[themisdb_shopping_cart]

public function shopping_cart_shortcode() {
    if (!isset($_SESSION['themisdb_order_id'])) {
        return '<p>Your cart is empty</p>';
    }
    
    $order = ThemisDB_Order_Manager::get_order($_SESSION['themisdb_order_id']);
    
    // Render cart table with:
    // - Product
    // - Selected modules
    // - Selected training
    // - Remove buttons
    // - Subtotal, Tax (if applicable), Total
    // - Checkout button
}
```

---

### 2.4 Payment Gateway Integration
**Status:** 📝 TODO | **Priority:** 🔴 Critical | **Est. Time:** 1.5 Wochen

**Aktuell:** Nur Bank-Transfer  
**Ziel:** Stripe + PayPal für instant payments

#### A) Stripe Integration

**Dependencies:** `stripe/stripe-php` via Composer

```php
// class-payment-processor-stripe.php
class ThemisDB_Payment_Processor_Stripe {
    
    public static function create_payment_intent($order_id) {
        $order = ThemisDB_Order_Manager::get_order($order_id);
        
        \Stripe\Stripe::setApiKey(get_option('themisdb_stripe_secret_key'));
        
        $intent = \Stripe\PaymentIntent::create(array(
            'amount' => intval($order['total_amount'] * 100), // Cents
            'currency' => strtolower($order['currency']),
            'metadata' => array(
                'order_id' => $order_id,
                'customer_email' => $order['customer_email'],
            ),
        ));
        
        return $intent->client_secret;
    }
    
    public static function verify_payment($payment_intent_id) {
        \Stripe\Stripe::setApiKey(get_option('themisdb_stripe_secret_key'));
        
        $intent = \Stripe\PaymentIntent::retrieve($payment_intent_id);
        
        if ($intent->status === 'succeeded') {
            return array(
                'success' => true,
                'order_id' => $intent->metadata->order_id,
                'transaction_id' => $intent->id,
            );
        }
        
        return array('success' => false);
    }
}
```

**Frontend Form (Stripe Elements):**
```html
<div id="payment-form">
    <div id="card-element"></div>
    <button id="submit-payment">Pay Now</button>
</div>

<script src="https://js.stripe.com/v3/"></script>
<script>
const stripe = Stripe('<?php echo get_option("themisdb_stripe_public_key"); ?>');
const elements = stripe.elements();
const cardElement = elements.create('card');
cardElement.mount('#card-element');

document.getElementById('submit-payment').addEventListener('click', async () => {
    const { paymentIntent } = await stripe.confirmCardPayment(clientSecret, {
        payment_method: {
            card: cardElement,
            billing_details: { email: customerEmail }
        }
    });
    
    if (paymentIntent.status === 'succeeded') {
        // Redirect to success
    }
});
</script>
```

#### B) PayPal Integration

**Using PayPal JavaScript SDK**

```html
<script src="https://www.paypal.com/sdk/js?client-id=<?php echo get_option('themisdb_paypal_client_id'); ?>"></script>
<div id="paypal-button-container"></div>

<script>
paypal.Buttons({
    createOrder: (data, actions) => {
        return actions.order.create({
            purchase_units: [{
                amount: {
                    value: '<?php echo $order['total_amount']; ?>'
                }
            }]
        });
    },
    onApprove: (data, actions) => {
        return actions.order.capture().then(details => {
            // Verify payment & create license
        });
    }
}).render('#paypal-button-container');
</script>
```

#### C) Webhook Handler
```php
// POST /wp-json/themisdb/v1/payment-webhook
add_action('rest_api_init', function() {
    register_rest_route('themisdb/v1', '/payment-webhook', array(
        'methods' => 'POST',
        'callback' => array('ThemisDB_Payment_Webhook', 'handle_webhook'),
        'permission_callback' => '__return_true',
    ));
});

class ThemisDB_Payment_Webhook {
    public static function handle_webhook() {
        $provider = $_GET['provider'] ?? 'stripe'; // stripe|paypal
        
        if ($provider === 'stripe') {
            self::handle_stripe_webhook();
        } else if ($provider === 'paypal') {
            self::handle_paypal_webhook();
        }
    }
}
```

---

### 2.5 Express Checkout (3-Step)
**Status:** 📝 TODO | **Priority:** 🟡 Important | **Est. Time:** 0.5 Wochen

**Ziel:** Schnellerer Checkout für Repeat Customers (Produkt → Checkout → Done)

#### Flow
```
Step 1: Product Selection (if not preset)
   ↓
Step 2: Checkout (Email + Payment)
   ↓
Step 3: Confirmation
```

#### Implementation
```php
[themisdb_express_checkout product="community" show_modules="false"]

// Skippt Steps 2 (modules) und 3 (training) von normalem 5-Step Flow
```

---

## 📊 Phase 2 Success Metrics

| Metrik | Target | Überprüfung |
|--------|--------|------------|
| WooCommerce Orders imported | 100% | Create order in WooCommerce, check themisdb_orders |
| Product page renders | 100% | Visit /produkte/community |
| Real-time pricing works | 100% | Select modules, price updates |
| Stripe payment accepts | 100% | Use test card, verify payment created |
| PayPal button shows | 100% | Load checkout page |
| Express checkout faster | 30% | Time to checkout: 2 min vs 5 min |

---

---

# 📋 PHASE 3: Security & Compliance

## 🎯 Ziele
- Zero SQL-Injection Vulnerabilities
- 95% Error-Handling Coverage
- Audit Trail für Compliance
- GDPR Compliance (Data Export, Deletion)

## 📦 Komponenten

### 3.1 SQL Injection Audit & Fixes
**Status:** 📝 TODO | **Priority:** 🔴 Critical | **Est. Time:** 1 Woche

#### Process
1. **Scan all 19 plugin files for:**
   ```
   - $wpdb->query() without prepare()
   - $wpdb->get_results() without prepare()
   - Concatenated SQL strings
   - User input in WHERE/SELECT directly
   ```

2. **Fix Template:**
   ```php
   // BEFORE
   $result = $wpdb->query("SELECT * FROM $table WHERE id = " . $_GET['id']);
   
   // AFTER
   $result = $wpdb->get_results(
       $wpdb->prepare("SELECT * FROM $table WHERE id = %d", intval($_GET['id'])),
       ARRAY_A
   );
   ```

3. **Test with Malicious Input:**
   ```
   ?id=1 OR 1=1
   ?id=1' UNION SELECT * FROM wp_users
   ?email=test@example.com'); DROP TABLE orders; --
   ```

#### Audit Checklist
- [ ] class-admin.php - Check all POST/GET handling
- [ ] class-order-manager.php - Check all queries
- [ ] class-contract-manager.php - Check all queries
- [ ] class-license-manager.php - Check all queries
- [ ] class-shortcodes.php - Check all user input
- [ ] All other 14 files - Similar audit

### 3.2 Error Handling & Logging
**Status:** 📝 TODO | **Priority:** 🟡 High | **Est. Time:** 0.5 Wochen

#### Error Logging Framework

```php
// class-error-handler.php
class ThemisDB_Error_Handler {
    
    const LOG_LEVELS = array('info', 'warning', 'error', 'critical');
    
    public static function log($level, $message, $context = array()) {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'user_id' => get_current_user_id(),
            'trace' => wp_debug_backtrace_summary(__FILE__),
        );
        
        // Log to file
        error_log(json_encode($log_entry));
        
        // Log to DB (for admin dashboard)
        if ($level === 'error' || $level === 'critical') {
            self::store_in_db($log_entry);
        }
    }
    
    // Usage:
    // ThemisDB_Error_Handler::log('error', 'Order creation failed', ['order_id' => 123]);
}
```

#### Critical Operations to Monitor
- Order Creation (success/failure)
- License Generation
- Payment Processing
- Contract Generation
- Support Benefits Assignment
- Cron Jobs (reset, expiry check)

---

### 3.3 GDPR Compliance
**Status:** 📝 TODO | **Priority:** 🟡 High | **Est. Time:** 0.5 Wochen

#### Data Export
```php
// Tools → Export Personal Data
add_filter('wp_privacy_personal_data_exporters', function($exporters) {
    $exporters['themisdb-order-data'] = array(
        'callback' => array('ThemisDB_Privacy', 'export_customer_orders'),
        'screen' => 'privacy-settings',
    );
    return $exporters;
});

class ThemisDB_Privacy {
    public static function export_customer_orders($email, $page = 1) {
        $user = get_user_by('email', $email);
        $orders = ThemisDB_Order_Manager::get_customer_orders($user->ID);
        
        $items = array();
        foreach ($orders as $order) {
            $items[] = array(
                'name' => 'Order ' . $order['order_number'],
                'value' => json_encode($order),
            );
        }
        
        return array(
            'data' => $items,
            'done' => true,
        );
    }
}
```

#### Data Deletion
```php
// Tools → Delete Personal Data
// Löscht Orders, Licenses, Contracts für einen Kunden (subject to retention policy)
```

---

## 📊 Phase 3 Success Metrics

| Metrik | Target | Überprüfung |
|--------|--------|-----------|
| SQL Injection vulnerabilities | 0 found | Penetration testing |
| Error logging working | 100% | Trigger error, check error.log |
| GDPR export works | 100% | Request export, download ZIP |
| GDPR delete works | 100% | Request delete, verify data gone |
| All critical operations logged | 100% | Check admin dashboard log |

---

---

# 📋 PHASE 4+: Monitoring, UX, Advanced Features

## PHASE 4: Monitoring & Operations (Wochen 10-11)

### 4.1 Admin Dashboard Widgets
- Order Pipeline: Draft → Pending → Confirmed → (Contract/Shipped)
- Revenue Charts (monthly, by product, by tier)
- Support Metrics (tickets by tier, SLA compliance, avg response time)
- License Metrics (issued, active, expired, cancelled)
- Health Status (last cron run, queue sizes, errors)

### 4.2 Alerts & Notifications
- Payment pending > 7 Tage
- Support SLA breached
- High error rate detected
- Database size warning
- Cron job skipped

---

## PHASE 5: Advanced Features (Wochen 12-19)

### 5.1 Subscription Management
- Auto-Renewal Option
- Renewal Reminders (30d, 7d, 1d before expiry)
- One-Click Renewal
- Upgrade/Downgrade Mid-Term

### 5.2 Affiliate Program
- Referral Links
- Commission Tracking
- Payout Management

### 5.3 B2B Portal
- Department Management
- Bulk User Upload
- Custom Pricing
- PO/Invoice Management

### 5.4 Advanced Reporting
- Cohort Analysis
- LTV & CAC Tracking
- Churn Analysis
- Product Mix Analysis

---

## 🎯 Milestone Overview

```
Week 1-3:    Phase 1 - Support Integration        |████████░░░| 80%
Week 4-7:    Phase 2 - Frontend Shop              |⬜⬜⬜⬜⬜⬜⬜⬜⬜⬜ 0%
Week 8-9:    Phase 3 - Security & Compliance      |⬜⬜⬜⬜⬜⬜⬜⬜⬜⬜ 0%
Week 10-11:  Phase 4 - Monitoring & Ops           |⬜⬜⬜⬜⬜⬜⬜⬜⬜⬜ 0%
Week 12-19:  Phase 5+ - Advanced Features (Backlog)|⬜⬜⬜⬜⬜⬜⬜⬜⬜⬜ 0%
```

---

# 🚀 Getting Started

## Immediate Next Steps

1. **Create Session Tickets:**
   - [ ] Support Benefits DB Migration
   - [ ] License Manager Hooks
   - [ ] Support Portal Validation

2. **Setup Development Environment:**
   ```bash
   # Ensure test site is ready
   wp plugin activate themisdb-order-request
   wp plugin activate themisdb-support-portal
   
   # Create test data
   wp eval-file scripts/create-test-licenses.php
   ```

3. **Begin Phase 1.1 Implementation:**
   ```php
   // 1. Update class-database.php with support_benefits table
   // 2. Create class-support-benefits-manager.php
   // 3. Add hooks to class-license-manager.php
   // 4. Test: Order → License → Benefit creation chain
   ```

---

# 📚 Reference Documentation

- [Support Integration Deep Dive](./docs/SUPPORT_INTEGRATION.md) *(TBD)*
- [WooCommerce Bridge API](./docs/WOO_BRIDGE_API.md) *(TBD)*
- [Payment Gateway Integration](./docs/PAYMENT_GATEWAYS.md) *(TBD)*
- [Security Audit Checklist](./docs/SECURITY_CHECKLIST.md) *(TBD)*

---

**Last Updated:** 20. März 2026  
**Owner:** Development Team  
**Status:** Ready for Implementation
