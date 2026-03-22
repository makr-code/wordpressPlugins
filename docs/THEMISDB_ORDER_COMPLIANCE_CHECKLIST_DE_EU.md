# ThemisDB Order Workflow Compliance Checklist (DE/EU)

Status: Technical checklist for implementation QA. This is not legal advice.
Scope: themisdb-order-request order flow from checkout to delivery/activation.

## 1. Checkout And Button Solution

- [ ] Final checkout CTA uses clear paid-order wording (for DE button solution).
- [ ] Total amount and currency are visible before final submit.
- [ ] Payment method impact is visible (instant vs delayed verification).

## 2. Legal Consent Capture

- [ ] Terms consent is captured and persisted per order.
- [ ] Privacy consent is captured and persisted per order.
- [ ] Withdrawal information acknowledgement is captured for consumers.
- [ ] Explicit waiver for early digital performance is captured separately.
- [ ] Consent version is stored (`legal_acceptance_version`).
- [ ] Consent timestamp is stored (`legal_accepted_at`).
- [ ] Evidence metadata is stored (`legal_accepted_ip`, `legal_accepted_user_agent`).

## 3. Compliance Gating In Workflow

- [ ] Status transitions to `confirmed`/`signed`/`active` are blocked without legal completeness.
- [ ] Regulatory field checks run for compliance-sensitive statuses.
- [ ] Same gating is enforced centrally (not only in admin UI handlers).
- [ ] Order creation with pre-set status cannot bypass compliance checks.

## 4. Digital Performance And Withdrawal Rights

- [ ] For consumer digital orders, activation is deferred if waiver is missing.
- [ ] Payment verification does not auto-activate consumer digital licenses without waiver.
- [ ] Warning/audit logs are created when instant payment exists without waiver.

## 5. WooCommerce Bridge Mapping

- [ ] Woo imported orders do not force legal consents to true.
- [ ] Customer type is inferred from reliable Woo data.
- [ ] Legal consent mapping reads explicit Woo metadata when available.
- [ ] Imported order status stays `pending` if legal evidence is incomplete.

## 6. Order Confirmation And Durable Information

- [ ] Order confirmation email includes legal links (AGB, privacy, withdrawal).
- [ ] Consent version/timestamp are included or traceable.
- [ ] Consumer without waiver receives clear deferred-activation notice.
- [ ] Relevant documents are attached or otherwise provided in durable form.

## 7. Privacy (GDPR) DSAR

- [ ] Data export includes relevant personal order data and legal evidence fields.
- [ ] Data erasure/anonymization does not write to non-existent DB columns.
- [ ] Erasure clears direct identifiers and address fields where required.
- [ ] Erasure updates are logged for auditability.

## 8. Tests And Operational Checks

- [ ] Consumer order without withdrawal acknowledgement cannot become active.
- [ ] Consumer instant-payment order without waiver remains non-active.
- [ ] Business order path remains functional with no consumer-only blocks.
- [ ] Woo import without consent metadata stays pending.
- [ ] GDPR export and erasure jobs run without SQL errors.
- [ ] Logs contain consent snapshot and activation deferral warnings when expected.

## Suggested Test Cases

1. B2C order, no withdrawal acknowledgement -> validation error before submit.
2. B2C order, acknowledgement yes, waiver no, Stripe -> payment verified but activation deferred.
3. B2C order, acknowledgement yes, waiver yes, Stripe -> activation allowed.
4. B2B order with company set -> no consumer withdrawal requirement.
5. Woo import with missing legal meta -> status remains pending.
6. GDPR export for known customer -> includes billing/shipping and legal evidence fields.
7. GDPR erase for known customer -> identifiers/an address fields are anonymized, no SQL column errors.

## References (Current Implementation Areas)

- includes/class-shortcodes.php
- includes/class-order-manager.php
- includes/class-payment-manager.php
- includes/class-woocommerce-bridge.php
- includes/class-email-handler.php
- includes/class-privacy.php
