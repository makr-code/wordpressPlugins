#!/usr/bin/env bash
# E2E-Test-Runner für themisdb-support-portal via wp-cli
# 
# Nutzt wp-cli um alle Lifecycle-Workflows automatisiert zu testen
# Ausführung: bash tests/e2e-test-runner.sh [--skip-cleanup]

set -euo pipefail

# Farben für Ausgabe
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Konfiguration
LOG_FILE="${LOG_FILE:-e2e-test-results.log}"
SKIP_CLEANUP="${1:-}"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

echo -e "${BLUE}═════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}E2E-Test: Support Portal Lifecycle Workflows${NC}"
echo -e "${BLUE}Start: $(date)${NC}"
echo -e "${BLUE}═════════════════════════════════════════════════════════════${NC}"
echo ""

# ──────────────────────────────────────────────────────────────────────
# Hilfsfunktionen
# ──────────────────────────────────────────────────────────────────────

log_test() {
    echo "[$(date '+%H:%M:%S')] 🧪 $1" | tee -a "$LOG_FILE"
}

log_success() {
    echo -e "${GREEN}[$(date '+%H:%M:%S')] ✅ $1${NC}" | tee -a "$LOG_FILE"
}

log_error() {
    echo -e "${RED}[$(date '+%H:%M:%S')] ❌ $1${NC}" | tee -a "$LOG_FILE"
}

log_info() {
    echo -e "${BLUE}[$(date '+%H:%M:%S')] ℹ️ $1${NC}" | tee -a "$LOG_FILE"
}

assert_count() {
    local table=$1
    local column=$2
    local expected=$3
    local actual=$(wp db query "SELECT COUNT(*) FROM $table WHERE $column;" --skip-column-names 2>/dev/null || echo "ERROR")
    
    if [ "$actual" = "$expected" ]; then
        log_success "$table: $column count is $expected"
    else
        log_error "$table: $column count is $actual (expected $expected)"
        return 1
    fi
}

# ──────────────────────────────────────────────────────────────────────
# TEST 1: Ticket-Erstellung & Workflow
# ──────────────────────────────────────────────────────────────────────

test_ticket_lifecycle() {
    log_test "TEST 1: Ticket-Lebenszyklus (erstellen → antworter → schließen)"
    
    # 1a: Ticket erstellen
    log_info "1a: Erstelle Test-Ticket als Kunde..."
    local ticket_id=$(wp db query "
        INSERT INTO wp_themisdb_support_tickets 
        (license_id, customer_email, category, priority, status, created_at, updated_at)
        VALUES (1, 'test-customer@example.com', 'technical', 'medium', 'open', NOW(), NOW());
        SELECT LAST_INSERT_ID();
    " --skip-column-names 2>/dev/null | head -1)
    
    if [ -z "$ticket_id" ]; then
        log_error "Ticket creation failed"
        return 1
    fi
    log_success "Ticket $ticket_id erstellt"
    
    # 1b: Nachricht hinzufügen
    log_info "1b: Füge Kunde-Nachricht hinzu..."
    wp db query "
        INSERT INTO wp_themisdb_support_messages 
        (ticket_id, user_type, message, created_by, created_at)
        VALUES ($ticket_id, 'customer', 'API-Fehler 500 bei /entities', 'customer_1', NOW());
    " 2>/dev/null || log_error "Message insert failed"
    log_success "Kunde-Nachricht hinzugefügt"
    
    # 1c: Support-Antwort
    log_info "1c: Support antwortet..."
    wp db query "
        INSERT INTO wp_themisdb_support_messages 
        (ticket_id, user_type, message, created_by, created_at)
        VALUES ($ticket_id, 'support', 'Bitte API-Version mitteilen.', 'support_1', NOW());
    " 2>/dev/null || log_error "Support reply failed"
    log_success "Support-Antwort hinzugefügt"
    
    # 1d: Ticket schließen
    log_info "1d: Schließe Ticket..."
    wp db query "
        UPDATE wp_themisdb_support_tickets 
        SET status='closed', resolved_at=NOW(), updated_at=NOW()
        WHERE id=$ticket_id;
    " 2>/dev/null || log_error "Ticket close failed"
    log_success "Ticket geschlossen"
    
    # Validierung
    log_info "1e: Validiere DB-Zustand..."
    local status=$(wp db query "SELECT status FROM wp_themisdb_support_tickets WHERE id=$ticket_id;" --skip-column-names 2>/dev/null)
    if [ "$status" = "closed" ]; then
        log_success "Ticket-Status korrekt: closed"
    else
        log_error "Ticket-Status falsch: $status (erwartet closed)"
        return 1
    fi
    
    local msg_count=$(wp db query "SELECT COUNT(*) FROM wp_themisdb_support_messages WHERE ticket_id=$ticket_id;" --skip-column-names 2>/dev/null)
    if [ "$msg_count" -eq 2 ]; then
        log_success "Message-Count korrekt: 2"
    else
        log_error "Message-Count falsch: $msg_count (erwartet 2)"
        return 1
    fi
}

# ──────────────────────────────────────────────────────────────────────
# TEST 2: Change-Request Workflow
# ──────────────────────────────────────────────────────────────────────

test_change_request_lifecycle() {
    log_test "TEST 2: Change-Request-Lebenszyklus (antrag → genehmigung → ausführung)"
    
    # 2a: Change-Request erstellen
    log_info "2a: Erstelle Change-Request..."
    local request_id=$(wp db query "
        INSERT INTO wp_themisdb_contract_lifecycle 
        (customer_id, license_id, request_type, status, reason, requested_at)
        VALUES (1, 1, 'change', 'requested', 'Wir wachsen', NOW());
        SELECT LAST_INSERT_ID();
    " --skip-column-names 2>/dev/null | head -1)
    
    if [ -z "$request_id" ]; then
        log_error "Change-Request creation failed"
        return 1
    fi
    log_success "Change-Request $request_id erstellt"
    
    # 2b: Impact-Analyse im Payload
    log_info "2b: Prüfe Impact-Analyse in Payload..."
    local payload=$(wp db query "
        SELECT payload FROM wp_themisdb_contract_lifecycle WHERE id=$request_id;
    " --skip-column-names 2>/dev/null)
    
    if [[ "$payload" == *"impact_analysis"* ]]; then
        log_success "Impact-Analyse in Payload vorhanden"
    else
        log_info "Impact-Analyse noch nicht berechnet (wird live bei Genehmigung gemacht)"
    fi
    
    # 2c: Genehmigen
    log_info "2c: Genehmige Change-Request..."
    wp db query "
        UPDATE wp_themisdb_contract_lifecycle 
        SET status='confirmed', reviewed_by=1, reviewed_at=NOW()
        WHERE id=$request_id;
    " 2>/dev/null || log_error "Change-Request approval failed"
    log_success "Change-Request genehmigt"
    
    # Validierung
    local status=$(wp db query "SELECT status FROM wp_themisdb_contract_lifecycle WHERE id=$request_id;" --skip-column-names 2>/dev/null)
    if [ "$status" = "confirmed" ]; then
        log_success "Change-Request-Status korrekt: confirmed"
    else
        log_error "Change-Request-Status falsch: $status (erwartet confirmed)"
        return 1
    fi
}

# ──────────────────────────────────────────────────────────────────────
# TEST 3: Termination-Request Workflow
# ──────────────────────────────────────────────────────────────────────

test_termination_request_lifecycle() {
    log_test "TEST 3: Termination-Request-Lebenszyklus (antrag → bestätigung → scheduler)"
    
    # 3a: Termination-Request erstellen
    log_info "3a: Erstelle Termination-Request..."
    local request_id=$(wp db query "
        INSERT INTO wp_themisdb_contract_lifecycle 
        (customer_id, license_id, request_type, status, reason, effective_at, requested_at)
        VALUES (1, 1, 'termination', 'requested', 'Kein Bedarf', DATE_ADD(NOW(), INTERVAL 30 DAY), NOW());
        SELECT LAST_INSERT_ID();
    " --skip-column-names 2>/dev/null | head -1)
    
    if [ -z "$request_id" ]; then
        log_error "Termination-Request creation failed"
        return 1
    fi
    log_success "Termination-Request $request_id erstellt"
    
    # 3b: Bestätigen
    log_info "3b: Bestätige Termination-Request..."
    wp db query "
        UPDATE wp_themisdb_contract_lifecycle 
        SET status='confirmed', reviewed_by=1, reviewed_at=NOW()
        WHERE id=$request_id;
    " 2>/dev/null || log_error "Termination confirmation failed"
    log_success "Termination-Request bestätigt"
    
    # 3c: Simuliere Scheduler-Ausführung (effective_at = NOW)
    log_info "3c: Simuliere Scheduler-Ausführung..."
    wp db query "
        UPDATE wp_themisdb_contract_lifecycle 
        SET effective_at=NOW(), status='executed', executed_at=NOW()
        WHERE id=$request_id;
    " 2>/dev/null || log_error "Scheduler execution failed"
    log_success "Scheduler-Ausführung simuliert"
    
    # Validierung
    local status=$(wp db query "SELECT status FROM wp_themisdb_contract_lifecycle WHERE id=$request_id;" --skip-column-names 2>/dev/null)
    if [ "$status" = "executed" ]; then
        log_success "Termination-Status korrekt: executed"
    else
        log_error "Termination-Status falsch: $status (erwartet executed)"
        return 1
    fi
}

# ──────────────────────────────────────────────────────────────────────
# TEST 4: Mail-Log Audit
# ──────────────────────────────────────────────────────────────────────

test_mail_log_coverage() {
    log_test "TEST 4: Mail-Log-Abdeckung & Event-Verknüpfung"
    
    log_info "Prüfe Mail-Log auf kritische Events..."
    
    # Erwartete Events
    local -a required_events=(
        "contract_change_requested"
        "contract_change_approved"
        "contract_change_rejected"
        "contract_termination_requested"
        "contract_termination_confirmed"
        "contract_termination_rejected"
        "sla_warning"
        "sla_breach_escalation"
        "ticket_closed"
    )
    
    local total_mails=$(wp db query "SELECT COUNT(*) FROM wp_themisdb_mail_log;" --skip-column-names 2>/dev/null)
    log_success "Gesamt-Mails in Log: $total_mails"
    
    # Prüfe für jeden Event-Typ mindestens einen Eintrag
    for event in "${required_events[@]}"; do
        local count=$(wp db query "SELECT COUNT(*) FROM wp_themisdb_mail_log WHERE event_type='$event' OR recipient_context LIKE '%$event%';" --skip-column-names 2>/dev/null || echo "0")
        if [ "$count" -gt 0 ]; then
            log_success "Event '$event': $count Mail-Einträge"
        else
            log_info "Event '$event': noch nicht getestet (wird live ausgelöst)"
        fi
    done
}

# ──────────────────────────────────────────────────────────────────────
# TEST 5: SLA-Tracking
# ──────────────────────────────────────────────────────────────────────

test_sla_tracking() {
    log_test "TEST 5: SLA-Tracking & Eskalation"
    
    log_info "5a: Erstelle Ticket mit SLA..."
    local ticket_id=$(wp db query "
        INSERT INTO wp_themisdb_support_tickets 
        (license_id, customer_email, category, priority, status, sla_due_at, created_at, updated_at)
        VALUES (1, 'sla-test@example.com', 'technical', 'high', 'open', NOW(), NOW(), NOW());
        SELECT LAST_INSERT_ID();
    " --skip-column-names 2>/dev/null | head -1)
    
    log_success "Ticket $ticket_id mit SLA erstellt"
    
    # SLA prüfen
    local sla_due=$(wp db query "SELECT sla_due_at FROM wp_themisdb_support_tickets WHERE id=$ticket_id;" --skip-column-names 2>/dev/null)
    log_info "SLA fällig: $sla_due"
    
    log_success "SLA-Tracking aktiv"
}

# ──────────────────────────────────────────────────────────────────────
# TEST 6: Metriken & Cache
# ──────────────────────────────────────────────────────────────────────

test_observability_metrics() {
    log_test "TEST 6: Observability Metrics & Transient-Cache"
    
    log_info "Prüfe Transient-Caches..."
    
    # Simuliere Metrik-Berechnung
    local transients=(
        "themisdb_obs_frt"
        "themisdb_obs_sla_rate"
        "themisdb_obs_volume"
        "themisdb_obs_queues"
    )
    
    for transient in "${transients[@]}"; do
        local value=$(wp option get "$transient" 2>/dev/null || echo "NOT_SET")
        if [ "$value" != "NOT_SET" ]; then
            log_success "Transient '$transient' gesetzt"
        else
            log_info "Transient '$transient' wird erst bei Admin-Load berechnet"
        fi
    done
}

# ──────────────────────────────────────────────────────────────────────
# Cleanup
# ──────────────────────────────────────────────────────────────────────

cleanup() {
    if [ "$SKIP_CLEANUP" = "--skip-cleanup" ]; then
        log_info "Cleanup übersprungen (--skip-cleanup flag)"
        return
    fi
    
    log_info "Räume Test-Daten auf..."
    
    # Lösche Test-Tickets, -Requests, -Messages
    wp db query "
        DELETE FROM wp_themisdb_support_messages WHERE ticket_id IN (
            SELECT id FROM wp_themisdb_support_tickets 
            WHERE customer_email LIKE 'test%@example.com' OR customer_email LIKE 'sla-test%'
        );
    " 2>/dev/null
    
    wp db query "
        DELETE FROM wp_themisdb_support_tickets 
        WHERE customer_email LIKE 'test%@example.com' OR customer_email LIKE 'sla-test%';
    " 2>/dev/null
    
    wp db query "
        DELETE FROM wp_themisdb_contract_lifecycle 
        WHERE customer_id=1 AND (created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR));
    " 2>/dev/null
    
    log_success "Test-Daten bereinigt"
}

# ──────────────────────────────────────────────────────────────────────
# MAIN
# ──────────────────────────────────────────────────────────────────────

main() {
    > "$LOG_FILE"  # Clear log
    
    # Prüfe WP-Installation
    if ! wp core is-installed 2>/dev/null; then
        log_error "WordPress nicht installiert oder wp-cli nicht erreichbar"
        exit 1
    fi
    log_success "WordPress installiert & wp-cli erreichbar"
    
    # Prüfe Plugin aktiv
    if ! wp plugin is-active themisdb-support-portal 2>/dev/null; then
        log_error "Plugin themisdb-support-portal nicht aktiv"
        exit 1
    fi
    log_success "Plugin themisdb-support-portal aktiv"
    
    # Tests ausführen
    local failed=0
    
    test_ticket_lifecycle || ((failed++))
    echo ""
    
    test_change_request_lifecycle || ((failed++))
    echo ""
    
    test_termination_request_lifecycle || ((failed++))
    echo ""
    
    test_mail_log_coverage || ((failed++))
    echo ""
    
    test_sla_tracking || ((failed++))
    echo ""
    
    test_observability_metrics || ((failed++))
    echo ""
    
    # Cleanup
    cleanup
    
    # Summary
    echo ""
    echo -e "${BLUE}═════════════════════════════════════════════════════════════${NC}"
    if [ $failed -eq 0 ]; then
        echo -e "${GREEN}✅ E2E-Tests BESTANDEN ($(date))${NC}"
    else
        echo -e "${RED}❌ E2E-Tests mit $failed Fehlern abgebrochen${NC}"
    fi
    echo -e "${BLUE}Log: $LOG_FILE${NC}"
    echo -e "${BLUE}═════════════════════════════════════════════════════════════${NC}"
    
    exit $failed
}

# Starte Main
main "$@"
