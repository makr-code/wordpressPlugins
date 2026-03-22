# ThemisDB Compliance Runtime Playbook (Windows)

Date: 22.03.2026
Scope: Close the 6 yellow items from the compliance audit using a real WordPress runtime.

## 0. Inputs You Need

- WordPress root path (contains wp-config.php)
- Active plugin: themisdb-order-request
- Optional for Woo checks: active woocommerce plugin

Example path used below:
C:/sites/my-wp

## 1. Install and verify WP-CLI (Windows)

Option A (Scoop):

1. Install Scoop (if missing)
2. Run:

powershell
scoop install wp-cli

Option B (manual PHAR):

1. Download wp-cli.phar from https://wp-cli.org/
2. Place in a tools folder and create wp.bat wrapper
3. Ensure wp is in PATH

Verify:

powershell
wp --info

Expected: command returns WP-CLI version and PHP info.

## 2. Preflight checks

powershell
$WpPath = "C:/sites/my-wp"
Test-Path "$WpPath/wp-config.php"
wp --path="$WpPath" core is-installed
wp --path="$WpPath" plugin is-active themisdb-order-request

Expected: True / success for all checks.

## 3. Run official smoke checks

Without Woo checks:

powershell
pwsh -File scripts/themisdb-order-request-e2e-smoke.ps1 -WpPath "$WpPath"

With Woo checks:

powershell
pwsh -File scripts/themisdb-order-request-e2e-smoke.ps1 -WpPath "$WpPath" -CheckWooBridge

Expected: script exits 0 with [OK] lines.

## 4. Close the 6 yellow items

### Yellow 1: Consumer order without withdrawal acknowledgement cannot become active

powershell
wp --path="$WpPath" eval "
$order_id = ThemisDB_Order_Manager::create_order(array(
  'customer_email' => 'runtime-y1@example.com',
  'customer_name' => 'Runtime Y1',
  'customer_type' => 'consumer',
  'billing_name' => 'Runtime Y1',
  'billing_address_line1' => 'Street 1',
  'billing_postal_code' => '10115',
  'billing_city' => 'Berlin',
  'billing_country' => 'DE',
  'shipping_country' => 'DE',
  'product_edition' => 'community',
  'total_amount' => 10,
  'currency' => 'EUR',
  'legal_terms_accepted' => 1,
  'legal_privacy_accepted' => 1,
  'legal_withdrawal_acknowledged' => 0,
  'status' => 'pending'
));
$ok = ThemisDB_Order_Manager::set_order_status($order_id, 'active');
$order = ThemisDB_Order_Manager::get_order($order_id);
echo ($ok === false && ($order['status'] ?? '') !== 'active') ? 'PASS' : 'FAIL';
"

Expected: PASS

### Yellow 2: Consumer instant payment without waiver remains non-active

powershell
wp --path="$WpPath" eval "
$order_id = ThemisDB_Order_Manager::create_order(array(
  'customer_email' => 'runtime-y2@example.com',
  'customer_name' => 'Runtime Y2',
  'customer_type' => 'consumer',
  'billing_name' => 'Runtime Y2',
  'billing_address_line1' => 'Street 2',
  'billing_postal_code' => '10115',
  'billing_city' => 'Berlin',
  'billing_country' => 'DE',
  'shipping_country' => 'DE',
  'product_edition' => 'community',
  'total_amount' => 10,
  'currency' => 'EUR',
  'legal_terms_accepted' => 1,
  'legal_privacy_accepted' => 1,
  'legal_withdrawal_acknowledged' => 1,
  'legal_withdrawal_waiver' => 0,
  'status' => 'pending'
));
$contract_id = ThemisDB_Contract_Manager::create_contract(array(
  'order_id' => intval($order_id),
  'customer_id' => get_current_user_id() ?: 1,
  'contract_type' => 'license',
  'contract_data' => array('source' => 'runtime-y2')
));
$payment_id = ThemisDB_Payment_Manager::create_payment(array(
  'order_id' => intval($order_id),
  'contract_id' => intval($contract_id),
  'amount' => 10,
  'currency' => 'EUR',
  'payment_method' => 'stripe'
));
ThemisDB_Payment_Manager::verify_payment($payment_id);
$order = ThemisDB_Order_Manager::get_order($order_id);
echo (($order['status'] ?? '') === 'confirmed') ? 'PASS' : 'FAIL';
"

Expected: PASS

### Yellow 3: Business path remains functional

powershell
wp --path="$WpPath" eval "
$order_id = ThemisDB_Order_Manager::create_order(array(
  'customer_email' => 'runtime-y3@example.com',
  'customer_name' => 'Runtime Y3',
  'customer_type' => 'business',
  'customer_company' => 'Runtime GmbH',
  'billing_name' => 'Runtime GmbH',
  'billing_address_line1' => 'Street 3',
  'billing_postal_code' => '10115',
  'billing_city' => 'Berlin',
  'billing_country' => 'DE',
  'shipping_country' => 'DE',
  'product_edition' => 'community',
  'total_amount' => 10,
  'currency' => 'EUR',
  'legal_terms_accepted' => 1,
  'legal_privacy_accepted' => 1,
  'status' => 'pending'
));
$ok = ThemisDB_Order_Manager::set_order_status($order_id, 'active');
$order = ThemisDB_Order_Manager::get_order($order_id);
echo ($ok === true && ($order['status'] ?? '') === 'active') ? 'PASS' : 'FAIL';
"

Expected: PASS

### Yellow 4: Woo import without consent metadata stays pending

Prerequisite: Woo active and fixture path available.

powershell
wp --path="$WpPath" plugin is-active woocommerce

Then run fixture (from runbook):

powershell
wp --path="$WpPath" eval-file scripts/create-test-woo-orders.php

Manual validation:

1. Create or identify one Woo order without custom legal consent meta fields.
2. Trigger bridge sync (status/payment event).
3. Check mapped ThemisDB order status in admin list.

Expected: mapped order status is pending (not forced confirmed).

### Yellow 5: GDPR export/erasure runs without SQL errors

Use WP privacy tools in admin:

1. Tools > Export Personal Data for a known test email
2. Tools > Erase Personal Data for same test email

Also check debug log and SQL errors:

powershell
wp --path="$WpPath" eval "global $wpdb; echo empty($wpdb->last_error) ? 'NO_DB_ERROR' : $wpdb->last_error;"

Expected: No SQL errors, export/erase complete.

### Yellow 6: Consent snapshot and deferral warning logs appear

Trigger a consumer instant-payment checkout without waiver, then inspect log sinks used by ThemisDB_Error_Handler.

Expected log messages:

- Checkout legal acceptance snapshot
- Consumer instant payment without withdrawal waiver: activation will be deferred

If WordPress debug log is enabled:

powershell
Get-Content "$WpPath/wp-content/debug.log" -Tail 200

## 5. Update audit status

After all checks PASS, update:

- docs/THEMISDB_ORDER_COMPLIANCE_AUDIT_2026-03-22.md

Set yellow items to green and add run date, environment, tester.

## 6. Quick result template

- Date:
- Environment:
- WP version:
- Woo version (if used):
- themisdb-order-request version:
- Yellow 1: PASS/FAIL
- Yellow 2: PASS/FAIL
- Yellow 3: PASS/FAIL
- Yellow 4: PASS/FAIL
- Yellow 5: PASS/FAIL
- Yellow 6: PASS/FAIL
- Final decision: GO / NO-GO
