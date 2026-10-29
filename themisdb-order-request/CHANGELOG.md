# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed
- License products use `/products/` and a shared cart/checkout dialog rather
  than separate cart/checkout pages. A plugin cart-button shortcode is wired
  into the Pulse header and reused by the Support hub.
- Product configuration excludes merchandise and standalone training offers.
  The configurator keeps its base CSS with any theme. The overlay reuses the
  existing session workflow in a same-origin, nonce-protected, non-cacheable
  frame; payment selection does not imply provider confirmation.
- The native shop is the intended integration. WooCommerce is inspiration
  only; the historical bridge is no longer loaded or initialized automatically.
- Order base styles load independently of theme detection. Shop, configurator,
  cart and Support accept shared `--themisdb-ui-*` palette variables while
  retaining standalone defaults; Pulse supplies those variables.
- Generic order CSS and the loading animation are scoped/namespaced to avoid
  styling unrelated theme or Support elements.

### Fixed
- Woo license entry points now require paid Woo state, an eligible gateway
  mapping, a confirmed compliant linked order and consumer withdrawal waiver.
  Merchandise, cancelled licenses, closed orders and mismatched license links
  cannot bypass these checks through thank-you or direct generation hooks.
- Woo order status persistence failures stop downstream license actions;
  activation/suspension/cancellation failures are logged. Completed callbacks
  do not retry failed generation through a second activation path.
- Woo order/license linkage uses order CRUD metadata and save, rather than
  post-meta APIs, and reports failed saves before emitting sync success.
- Bank imports check transaction and payment-reference persistence before
  verification, mark matches complete only after successful processing, and
  report partial failures as `WP_Error` with import context. Both admin
  callers handle these errors instead of showing a successful import/assignment.
- Bank preview and manual assignments validate positive matching amounts,
  currencies and open payment statuses. Current TDB order references and
  overdue fallback payments are recognized alongside legacy ORD references.
- Persisted import counts track completed matches and saved rows; manual
  assignment refreshes the summary and counts as matched in the admin view.
- Native checkout no longer verifies Stripe/PayPal payments just because the
  customer selected that method; the UI explains the missing provider connection.
- Selection-to-line-item sync uses catalog price, module and training columns,
  retaining selected extras and their prices instead of producing zero-priced
  products and missing extra lines.
- Payment verification guards the previous status and skips duplicate callbacks.
  Failed/conflicting payment writes and blocked order synchronization cannot
  activate licenses. Closed orders cannot be reactivated through this path.
- Payment edits and epServer confirmations use the same verification workflow;
  verified payments cannot be demoted to failed. Payment admin actions report
  processing failures instead of redirecting to a success message.
- Lifecycle reviews now check database persistence before emitting approval or
  rejection events, writing success audit entries or notifying customers.
- Review updates require the expected previous status, preventing a stale
  reviewer from overwriting an already processed request.
- Pending-finance terminations are included in the duplicate-request check.
- Failed license application now propagates a specific error to direct and
  finance reviewers and records `execute_failed`, without emitting approval
  or execution success notifications. The saved approval remains confirmed
  and requires manual inspection; no automatic retry is introduced.
- Malformed stored JSON and nested values in license fields are rejected
  explicitly before writing license data.
- Termination scheduler counts completion only after a successful guarded
  status update. Persistence failures are audited without completion events
  or notifications; subsequent runs can finish requests for cancelled licenses.
- Failed due-request queries return an explicit error and log a technical
  message; the Support admin scheduler trigger displays the error.
- Termination completion mail now cleans up its temporary attachment and
  content-type filter even when mail transport throws. Failed handoff and
  missing documents receive distinct audit entries.
- Termination attachments use exclusively created UUID filenames in the
  WordPress temporary directory instead of second-based upload filenames.
  PDF rendering completes in memory before writing, preventing partial files
  on renderer exceptions; writes and flushes are checked.

### Tests
- Added browser checks for standalone plugin styling, shared Pulse palette
  variants, CSS isolation and existing shop/support mobile layouts. No new
  WordPress pages are created or published by these changes.
- Added isolated Woo lifecycle and import regressions for unpaid/gateway/legal
  restrictions, new/reused licenses, repeated callbacks, refunds, linked
  ownership and failed CRUD/status/license operations without active WooCommerce.
- Added isolated bank-import regressions for write/verification failures,
  partial processing, exact cent tolerance, currency/status mismatches,
  current/legacy references, repeated same-batch candidates, import counts,
  and both admin error paths without processing real bank transactions.
- Added isolated payment, selection/line-item and checkout regressions covering
  all three payment methods for license and merchandise orders, without real
  orders, messages or external provider calls.
- Added isolated review regression tests using in-memory doubles, with no
  WordPress bootstrap, database connection, mail delivery or network access.
- Covered failed and successful license application, unchanged license values,
  metadata exclusion and repeated approval after an application failure.
- Covered scheduler cancellation failures, failed/conflicting status updates,
  recovery, already cancelled/missing licenses, empty queues and query errors.
- Added isolated completion-mail tests for printable HTML, missing PDF output,
  failed handoff and transport exceptions, without sending mail.
- Covered overlapping attachment lifetimes, synthetic PDF output, renderer
  exceptions and unavailable temporary directories.

## [1.1.0] - 2026-03-20

### Added – Deutsches Recht & Compliance
- **Regulatorische Felder**: Vollständige Rechnungsadresse (billing_name, address_line1/2, PLZ, Ort, Land), Lieferadresse, Kundentyp (B2C/B2B), USt-IdNr., Versandart
- **Rechtliche Zustimmungen**: Checkboxen für AGB, Datenschutzerklärung, Widerrufsbelehrung (B2C) und Verzicht auf Widerrufsrecht § 356 Abs. 5 BGB (B2B/digital)
- **Pflichtfeld-Validierung (PHP)**: `validate_order_regulatory_fields()` prüft ISO-Ländercode (`^[A-Z]{2}$`), DE-PLZ (`^\d{5}$`), USt-IdNr.-Format (`^[A-Z]{2}[A-Z0-9]{2,12}$`), B2B-Firmenpflicht
- **Compliance-Vollständigkeitsprüfung**: `is_order_compliance_complete()` – AGB + Datenschutz + Widerrufsbelehrung (B2C)
- **Workflow-Guard**: Statusübergänge zu `confirmed/signed/active` blockiert, solange Compliance unvollständig
- **Strukturierte Feldfehlermeldungen**: `validate_regulatory_fields()` liefert `field_errors`-Map; AJAX-Antworten enthalten feldgenaue Fehler

### Added – Frontend UX
- **Kundentyp-Karten** (`.customer-type-option`): Visuell klickbare B2C/B2B-Auswahl mit Hover-Highlight
- **B2B-Felder dynamisch** (`slideDown/Up`): Firma und USt-IdNr. nur sichtbar wenn „Unternehmer" gewählt
- **Auto-Uppercase** für ISO-Länderfelder: Nicht-Buchstaben entfernt, max. 2 Zeichen, Live-Formatierung
- **Auto-Strip DE-PLZ**: Bei `blur` auf PLZ-Felder werden Nicht-Ziffern entfernt wenn Land = DE
- **Feldmarkierung** (`.themisdb-invalid-field`): Roter Rahmen + Hintergrund für ungültige Felder
- **Feldfehlermeldungen** (`.themisdb-field-error`): Kleiner roter Text unterhalb jedes ungültigen Feldes
- **Step 5 – Compliance-Vorschau**: Zusammenfassung zeigt Kundentyp, USt-IdNr. und Status aller Legal-Zustimmungen mit ✓/✗

### Added – Admin Backend
- **Compliance-Felder in Create/Edit-Formularen**: Rechnungsadresse, Lieferadresse, Kundentyp, USt-IdNr. mit Feldhinweisen
- **Abschnitts-Header** (`.themisdb-form-section-header`): Optische Trennung von Kontakt-, Rechnungs-, Liefer- und Rechtsdaten
- **B2B-Toggle (Admin)**: `syncB2bRow()` in admin.js blendet Firma/USt-IdNr.-Zeilen je nach customer_type ein/aus
- **Auto-Uppercase** für Land-ISO-Felder auch im Admin

### Added – CSS / Styling
- `order-request.css`: Feldvalidierung, Kundentyp-Karten, Compliance-Sections, Adress-Grid (2-spaltig, responsiv), Legal-Checkboxen-Block, Error/Success-Notices
- `admin.css`: Admin-Compliance-Notice, Feldmarkierung, Abschnitts-Header, Workflow-Guard-Hinweis, Feldhinweis

### Added – Dokumentation & Ops
- `docs/THEMISDB_ORDER_REQUEST_E2E_RUNBOOK.md`: 10 E2E-Testszenarien mit Go/No-Go-Kriterien
- `scripts/themisdb-order-request-e2e-smoke.ps1`: Automatisierbarer Smoke-Check per WP-CLI

### Changed
- Step-4-Formular überarbeitet: Semantische Sections, PLZ/Ort/Land in `.address-grid`, Legal-Zustimmungen in `.legal-checkboxes`
- `showErrorMessage` / `showSuccessMessage` nutzen neue CSS-Klassen statt WordPress-`notice`-Klassen
- Widerruf-Text präzisiert auf § 356 Abs. 5 BGB
- Admin-Formulare: Abschnitts-h3 mit `themisdb-form-section-header`-Klasse

---

## [1.0.0] - 2026-01-08

### Added
- Initial release of ThemisDB Order Request & Contract Management Plugin
- Dialog-based order flow with 5 steps (Product → Modules → Training → Customer Data → Summary)
- Complete CRUD operations for orders and contracts
- Automatic contract revision tracking for legal compliance
- PDF generation for contracts and orders
- Email system with automated notifications and logging
- epServer API integration for master data synchronization
- Admin interface for managing orders, contracts, products, and email logs
- Frontend shortcodes: `[themisdb_order_flow]`, `[themisdb_my_orders]`, `[themisdb_my_contracts]`
- Responsive design for mobile and desktop
- Security features: CSRF protection, SQL injection prevention, XSS protection
- GDPR compliance features
- Multi-language support (German primary, extensible)
- Comprehensive documentation and README

### Features
- **Order Management**: Full order lifecycle from creation to completion
- **Contract Management**: Legal-compliant contract handling with revisions
- **PDF Generation**: Professional PDF templates for contracts and orders
- **Email System**: Automated emails with PDF attachments and comprehensive logging
- **epServer Integration**: Real-time product synchronization and customer management
- **Admin Dashboard**: Complete management interface with statistics and reports
- **Frontend Experience**: User-friendly dialog-based ordering process
- **Legal Compliance**: Full audit trail and revision history for all contracts

### Database Schema
- Orders table with customer and product information
- Contracts table with legal data and PDF storage
- Contract revisions table for change tracking
- Products, modules, and training master data tables
- Email log table for delivery tracking

### Security
- WordPress nonce verification for all AJAX requests
- SQL prepared statements to prevent injection attacks
- Input sanitization and output escaping
- User capability checks for admin functions
- Secure PDF storage options (database or filesystem)

## [Unreleased]

### Planned Features
- Multi-currency support
- Automated invoice generation
- Payment gateway integration (Stripe, PayPal)
- Electronic signature support
- Multi-language support (English, Spanish, French)
- Advanced reporting and analytics
- Workflow automation rules
- Mobile app integration
- REST API for third-party integrations
- Bulk operations for orders and contracts
- Export functionality (CSV, Excel)
- Custom contract templates
- Advanced email templates with drag-and-drop editor
