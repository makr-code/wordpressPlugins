# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
