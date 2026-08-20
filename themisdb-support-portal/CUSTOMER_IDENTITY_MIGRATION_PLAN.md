# ThemisDB Support Portal - Customer Identity Migration Plan

Status: proposed
Scope: themisdb-support-portal + Integrationspunkte zu themisdb-order-request + themisdb-pulse
Goal: Kundenkonten klar von WordPress-Benutzerverwaltung trennen, ohne sofortige Breaking Changes

## 1. Zielbild

### 1.1 Grundsatz
- Co-Authors Plus (Guest Authors) wird als Kundenprofil-Ebene genutzt.
- WordPress-User bleiben fuer Admin/Support-Agenten erhalten.
- Kunden-Login im Support-Portal laeuft ueber eigene Session-Logik, nicht ueber wp_set_auth_cookie().

### 1.2 Warum dieses Modell
- Trennung von CMS-Administration und Kundenidentitaet.
- Geringeres Risiko, dass Kundenzugaenge unbeabsichtigt WordPress-Capabilities erben.
- Co-Authors-Daten sind fuer Profil/Persona/Ansprechpartner gut geeignet, aber nicht als Rollen-/Rechte-System.

## 2. Ist-Zustand und Hauptkopplungen

### 2.1 Aktuelles Login
- Lizenzdatei-Login erzeugt oder verwendet WordPress-User und setzt Auth-Cookie.
- Referenz: includes/class-license-auth.php

### 2.2 Aktueller Zugriffskontext
- Mehrere Support-Komponenten lesen user_meta ueber wp_get_current_user().
- Referenzen:
  - includes/class-shortcodes.php
  - includes/class-status-resolver.php
  - includes/class-ticket-manager.php

### 2.3 Bestehende Co-Author-Integration
- Co-Authors wird bereits im Theme fuer AI-Profile genutzt.
- Referenzen:
  - ../themisdb-pulse/functions.php
  - ../themisdb-pulse/tools/setup-ai-coauthors.php

## 3. Datenmodell

Neue Tabelle: wp_themisdb_customer_accounts

### 3.1 Felder
- id BIGINT UNSIGNED PK AUTO_INCREMENT
- customer_uuid CHAR(36) NOT NULL UNIQUE
- guest_author_id BIGINT UNSIGNED NULL
- primary_license_id BIGINT UNSIGNED NULL
- customer_email VARCHAR(190) NOT NULL
- customer_name VARCHAR(190) NULL
- customer_company VARCHAR(190) NULL
- support_tier VARCHAR(32) NULL
- account_status VARCHAR(32) NOT NULL DEFAULT 'active'
- last_login_at DATETIME NULL
- created_at DATETIME NOT NULL
- updated_at DATETIME NOT NULL

Indexes:
- UNIQUE(customer_email)
- INDEX(primary_license_id)
- INDEX(guest_author_id)
- INDEX(account_status)

Neue Tabelle: wp_themisdb_customer_sessions

### 3.2 Felder
- id BIGINT UNSIGNED PK AUTO_INCREMENT
- customer_account_id BIGINT UNSIGNED NOT NULL
- session_token_hash CHAR(64) NOT NULL
- session_nonce CHAR(64) NOT NULL
- expires_at DATETIME NOT NULL
- last_seen_at DATETIME NULL
- ip_address VARCHAR(64) NULL
- user_agent VARCHAR(255) NULL
- created_at DATETIME NOT NULL

Indexes:
- UNIQUE(session_token_hash)
- INDEX(customer_account_id)
- INDEX(expires_at)

## 4. Neue Komponenten (Phase 1 Ziel-API)

Neue Dateien im Plugin:
- includes/class-customer-account-repository.php
- includes/class-customer-session-manager.php
- includes/class-customer-context.php
- includes/class-customer-auth.php

### 4.1 class-customer-account-repository.php
- create_or_update_from_license(array $license_payload): array
- find_by_email(string $email): ?array
- find_by_id(int $id): ?array
- attach_guest_author(int $account_id, int $guest_author_id): bool

### 4.2 class-customer-session-manager.php
- create_session(int $customer_account_id): array
- resolve_session_from_request(): ?array
- rotate_session(int $session_id): bool
- destroy_session(): void
- gc_expired_sessions(): int

### 4.3 class-customer-context.php
- current_customer(): ?array
- require_customer_or_null(): ?array
- is_customer_authenticated(): bool

### 4.4 class-customer-auth.php
- authenticate_with_license_file(string $file_content): array
- logout_customer(): void
- map_license_to_customer_account(array $license_data): array

Hinweis:
- Diese Klasse ersetzt schrittweise die kundenbezogene Verantwortung aus class-license-auth.php.

## 5. Refactor-Plan pro Datei

### 5.1 includes/class-license-auth.php
Phase 1:
- Bestehendes Verhalten per Feature Flag beibehalten.
- Neue Option:
  - themisdb_support_customer_auth_mode = wp_user|customer_session
- Bei customer_session:
  - keine Erstellung von WP-Usern
  - kein wp_set_auth_cookie()
  - Delegation an ThemisDB_Support_Customer_Auth

Phase 2:
- get_or_create_user() nur noch Legacy-Pfad.
- current_user_has_license() erweitert um Customer-Session-Check.

### 5.2 includes/class-shortcodes.php
Phase 1:
- Lesepfade kapseln: customer context helper einfuehren.
- user_meta-Zugriffe abstrahieren ueber helper:
  - get_effective_license_key()
  - get_effective_license_id()
  - get_effective_customer_profile()

Phase 2:
- Ticket-Listen, Build-Historie, Contract-Views standardmaessig ueber customer_account_id filtern.

### 5.3 includes/class-status-resolver.php
Phase 1:
- resolve_for_user(int $user_id) unveraendert lassen.
- neue Methode: resolve_for_customer_account(int $account_id).

Phase 2:
- Support-Portal ruft fuer Kunden nur noch resolve_for_customer_account() auf.

### 5.4 includes/class-ticket-manager.php
Phase 1:
- zusaetzliche Felder vorbereiten:
  - customer_account_id nullable
  - created_by_wp_user_id nullable
- create_ticket() so erweitern, dass beide Kontexte moeglich sind.

Phase 2:
- Ownership fuer Kunden primär ueber customer_account_id.

### 5.5 themisdb-support-portal.php
Phase 1:
- Bootstrap neuer Klassen.
- Hook fuer Session-GC, z. B. taeglich via wp_cron.
- Embed-Logik auf doppelte Render-Guards pruefen.

## 6. Co-Authors-Anbindung fuer Kundenprofile

### 6.1 Nutzung
- Guest Author repraesentiert Kundenprofil (Name, Firma, Kontaktanzeige, optional Avatar).
- guest_author_id wird in wp_themisdb_customer_accounts referenziert.

### 6.2 Nicht-Ziele
- Keine Rollen/Rechte aus Co-Authors ableiten.
- Keine Co-Authors-Session als Auth-Ersatz.

### 6.3 Sync-Regeln
- Bei erster erfolgreicher Lizenzauth:
  - Customer-Account erstellen/aktualisieren
  - optional Guest Author erstellen/zuordnen
- Bei Aenderung von Firmenname/E-Mail:
  - Profilfelder synchronisieren (konfigurierbar)

## 7. Feature Flags und Rollout

Optionen:
- themisdb_support_customer_auth_mode
  - wp_user (Default, Legacy)
  - customer_session (neues Modell)
- themisdb_support_customer_profile_sync_enabled
  - 0|1

Rollout:
1. Deploy mit Flags auf Legacy.
2. DB-Migration ausrollen.
3. Staging: customer_session aktivieren.
4. E2E Tests (Login, Ticket, Build, Download, Logout, Session-Expiry).
5. Produktion fuer Teilmenge aktivieren.
6. Vollumstellung.

## 8. Rueckwaertskompatibilitaet

- Admin-Funktionen bleiben bei current_user_can('manage_options').
- Bestehende WP-User-gebundene Kunden koennen uebergangsweise weiterlaufen.
- Mapping von Legacy-WP-User auf customer_account_id wird bei erster Nutzung erstellt.

## 9. Sicherheitsanforderungen

- Session-Token nur gehasht speichern (SHA-256).
- Cookie nur HttpOnly + Secure + SameSite=Lax.
- Lizenzdatei-Upload nur serverseitig validieren.
- Session-Rotation nach Login und bei sensitiven Aktionen.
- Audit-Events fuer login_success, login_failed, session_expired, logout.

## 10. Teststrategie

### 10.1 Unit
- Repository CRUD + Constraints
- Session lifecycle + expiry + rotation
- Auth mapping license -> account

### 10.2 Integration
- Portal login/logout mit customer_session
- Ticket-Erstellung und Ticket-Liste auf account-spezifische Isolation
- Build-Historie und Downloadrechte pro Account

### 10.3 Regression
- Legacy-Modus wp_user bleibt funktionsfaehig
- Admin-Ansichten unveraendert
- Existing support embeds unveraendert

## 11. Offene Punkte

1. Soll ein Kunde mehrere aktive Lizenzen gleichzeitig im selben Account sehen?
2. Soll ein Kunde mehrere Ansprechpartner (Sub-Accounts) erhalten?
3. Duerfen API-Keys pro Lizenz und pro Umgebung (dev/stage/prod) getrennt verwaltet werden?
4. Wie wird das Ticket-Domainmodell vereinheitlicht (Issue vs Incident vs Contract Request)?

## 12. Umsetzungsreihenfolge (empfohlen)

1. DB-Migration + Repository + Session Manager
2. Neuer Auth-Pfad mit Feature Flag
3. Customer Context in Shortcodes/Status Resolver einklinken
4. Ticket Ownership auf customer_account_id erweitern
5. Build/Download/API-Key Flows an Customer Context koppeln
6. Doppelrender-Guard und finale UX-Politur
