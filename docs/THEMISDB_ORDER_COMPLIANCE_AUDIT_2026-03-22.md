# ThemisDB Order Workflow Compliance Audit (DE/EU)

Date: 22.03.2026
Type: Technical implementation audit (not legal advice)
Scope: themisdb-order-request checkout, order lifecycle, Woo sync, payment activation, email communication, GDPR export/erase.

## Ampel Summary

- Green: 23
- Yellow: 6
- Red: 0

## Runtime Execution Status

- Attempted smoke execution on 22.03.2026 with `scripts/themisdb-order-request-e2e-smoke.ps1` prerequisites.
- Result: blocked in current environment because WP-CLI is not installed (`wp` command not found) and no local `wp-config.php` was found in the workspace.
- Impact: the six operational items remain yellow until executed on a real WordPress runtime.

## Detailed Results

### 1. Checkout And Button Solution

- Green: Final checkout CTA uses clear paid-order wording.
  Evidence: includes/class-shortcodes.php:967
- Green: Total amount and currency are visible before final submit.
  Evidence: includes/class-shortcodes.php:926, includes/class-shortcodes.php:928
- Green: Payment method impact is visible before submit.
  Evidence: includes/class-shortcodes.php:957

### 2. Legal Consent Capture

- Green: Terms consent is captured and persisted.
  Evidence: includes/class-shortcodes.php:739, includes/class-order-manager.php:84
- Green: Privacy consent is captured and persisted.
  Evidence: includes/class-shortcodes.php:749, includes/class-order-manager.php:85
- Green: Withdrawal acknowledgement is captured for consumers.
  Evidence: includes/class-shortcodes.php:759, includes/class-shortcodes.php:1126
- Green: Withdrawal waiver is captured separately.
  Evidence: includes/class-shortcodes.php:769, includes/class-order-manager.php:87
- Green: Consent version is stored.
  Evidence: includes/class-shortcodes.php:1181, includes/class-order-manager.php:88
- Green: Consent timestamp is stored.
  Evidence: includes/class-shortcodes.php:1182, includes/class-order-manager.php:89
- Green: Evidence metadata is stored (IP, user agent).
  Evidence: includes/class-shortcodes.php:1183, includes/class-shortcodes.php:1184, includes/class-order-manager.php:90, includes/class-order-manager.php:91

### 3. Compliance Gating In Workflow

- Green: Status transitions to confirmed/signed/active are blocked without legal completeness.
  Evidence: includes/class-order-manager.php:333, includes/class-order-manager.php:238
- Green: Regulatory checks run for compliance-sensitive transitions.
  Evidence: includes/class-order-manager.php:205, includes/class-order-manager.php:333
- Green: Same gating is enforced centrally.
  Evidence: includes/class-order-manager.php:299, includes/class-order-manager.php:333
- Green: Order creation with pre-set status cannot bypass checks.
  Evidence: includes/class-order-manager.php:109

### 4. Digital Performance And Withdrawal Rights

- Green: Consumer digital activation is deferred if waiver is missing.
  Evidence: includes/class-payment-manager.php:149, includes/class-payment-manager.php:164
- Green: Payment verification does not auto-activate consumer digital licenses without waiver.
  Evidence: includes/class-payment-manager.php:153, includes/class-payment-manager.php:171
- Green: Warning/audit logs exist for instant payment without waiver.
  Evidence: includes/class-shortcodes.php:1341

### 5. WooCommerce Bridge Mapping

- Green: Woo imports do not force legal consents to true.
  Evidence: includes/class-woocommerce-bridge.php:556-561
- Green: Customer type is inferred from Woo data.
  Evidence: includes/class-woocommerce-bridge.php:532, includes/class-woocommerce-bridge.php:609
- Green: Legal consent mapping reads explicit Woo metadata when available.
  Evidence: includes/class-woocommerce-bridge.php:625-629
- Green: Imported order stays pending if legal evidence is incomplete.
  Evidence: includes/class-woocommerce-bridge.php:565

### 6. Order Confirmation And Durable Information

- Green: Order confirmation email includes legal links.
  Evidence: includes/class-email-handler.php:263-265, includes/class-email-handler.php:390
- Green: Consent version/timestamp are included in mail.
  Evidence: includes/class-email-handler.php:397-399
- Green: Consumer without waiver gets deferred-activation notice.
  Evidence: includes/class-email-handler.php:402
- Green: Durable information is provided via PDF attachment in confirmation flow.
  Evidence: includes/class-email-handler.php:87, includes/class-email-handler.php:93, includes/class-email-handler.php:229

### 7. Privacy (GDPR) DSAR

- Green: Export includes personal order and legal evidence fields.
  Evidence: includes/class-privacy.php:88-124
- Green: Erasure no longer writes non-existent order notes column.
  Evidence: includes/class-privacy.php:475-491
- Green: Erasure clears identifiers and address/evidence fields.
  Evidence: includes/class-privacy.php:475-491
- Green: Erasure updates are logged.
  Evidence: includes/class-privacy.php:499

### 8. Tests And Operational Checks

- Yellow: Consumer order without withdrawal acknowledgement cannot become active.
  Status reason: Runtime verification pending (no WP-CLI/WordPress runtime in current environment).
- Yellow: Consumer instant-payment order without waiver remains non-active.
  Status reason: Runtime verification pending (no WP-CLI/WordPress runtime in current environment).
- Yellow: Business path remains functional with no consumer-only blocks.
  Status reason: Requires regression run across B2B checkout and lifecycle on live WordPress runtime.
- Yellow: Woo import without consent metadata stays pending.
  Status reason: Logic exists, not validated with a live Woo order in this audit runtime.
- Yellow: GDPR export and erasure jobs run without SQL errors.
  Status reason: Logic review done; runtime DSAR execution not run due to missing WP runtime.
- Yellow: Logs contain consent snapshot and activation deferral warnings when expected.
  Status reason: Logging code exists; runtime log verification pending on environment with WP-CLI.

## Recommendation

- Use the runtime playbook: `docs/THEMISDB_ORDER_COMPLIANCE_RUNTIME_PLAYBOOK_WINDOWS.md`
- Install WP-CLI and execute `scripts/themisdb-order-request-e2e-smoke.ps1 -WpPath <wordpress_root> -CheckWooBridge`.
- Run one focused E2E matrix across B2C/B2B and Woo import to close all yellow items.
- Keep this document as baseline and update statuses after each release validation.
