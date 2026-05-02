<?php
/**
 * E2E-Test-Szenario: Support Portal – Komplette Lifecycle-Flows
 * 
 * Beschreibt schrittweise Testszenarien für alle Hauptfunktionen:
 * 1. Ticket-Workflow (öffentlich → Support → Kunde)
 * 2. Change-Request-Workflow (Kunde → Admin-Review → ausgeführt)
 * 3. Termination-Request-Workflow (Kunde → Admin-Review → automatisch ausgeführt)
 * 
 * Ausführung:
 *   - Manuell via WordPress-UI
 *   - Oder: wp-cli-Scripts in tests/ (zukünftig)
 */

// Notizen zur E2E-Test-Vorbereitung
return array(
    'scenarios' => array(

        // ──────────────────────────────────────────────────────────────────────
        // Szenario 1: Ticket erstellen → Support antwortet → Kunde resolved
        // ──────────────────────────────────────────────────────────────────────
        'ticket_lifecycle' => array(
            'title'   => 'Ticket-Lebenszyklus: Erstellen bis Schließen',
            'steps'   => array(
                array(
                    'actor'  => 'Kunde',
                    'action' => 'Meldet sich auf /kundenportal an',
                    'check'  => 'Login-Formular wird angezeigt',
                ),
                array(
                    'actor'  => 'Kunde',
                    'action' => 'Klickt "Neues Ticket"',
                    'check'  => 'Ticket-Erstellungsformular erscheint',
                ),
                array(
                    'actor'  => 'Kunde',
                    'action' => 'Gibt ein: Titel "API-Fehler 500", Beschreibung "Bei POST /api/entities tritt 500 auf"',
                    'check'  => 'Formular validiert Eingabe',
                ),
                array(
                    'actor'  => 'Kunde',
                    'action' => 'Klickt "Senden"',
                    'check'  => 'Ticket erstellt, Bestätigungs-Mail an Kunde, Event `themisdb_support_portal_ticket_created` gefeuert',
                    'assertions' => array(
                        'Event-Hook abgehört von Mail-Orchestrator' => 'Mail versendet',
                        'Status in DB: `open`' => true,
                        'Timestamp: `created_at` gesetzt' => true,
                    ),
                ),
                array(
                    'actor'  => 'Support-Mitarbeiter',
                    'action' => 'Admin öffnet /wp-admin/admin.php?page=themisdb-support-portal',
                    'check'  => 'Ticket-Liste mit Status `open` angezeigt',
                ),
                array(
                    'actor'  => 'Support-Mitarbeiter',
                    'action' => 'Klickt Ticket-ID, ändert Status zu `in_progress`',
                    'check'  => 'Status aktualisiert, Event `themisdb_support_portal_ticket_status_changed` gefeuert',
                ),
                array(
                    'actor'  => 'Support-Mitarbeiter',
                    'action' => 'Antwortet: "Tritt bei uns nicht auf, möglicherweise API-Version. Welche Version nutzen Sie?"',
                    'check'  => 'Nachricht in Ticket eingefügt, Timestamp & Autor korrekt',
                ),
                array(
                    'actor'  => 'Kunde',
                    'action' => 'Sieht auf /kundenportal neue Nachricht im Ticket',
                    'check'  => 'Support-Antwort angezeigt, `created_at` korrekt',
                ),
                array(
                    'actor'  => 'Kunde',
                    'action' => 'Antwortet: "API v3.1.4" und klickt "Status: Gelöst"',
                    'check'  => 'Status auf `resolved`, Nachricht hinzugefügt',
                ),
                array(
                    'actor'  => 'Support-Mitarbeiter',
                    'action' => 'Sieht auf Admin-Seite neuen Status, klickt "Schließen"',
                    'check'  => 'Status = `closed`, Mail an Kunde versendet',
                ),
                array(
                    'actor'  => 'Kunde',
                    'action' => 'Sieht Ticket auf /kundenportal als "Geschlossen"',
                    'check'  => 'Ticket in Liste mit Status-Badge sichtbar',
                ),
            ),
            'expected_db_state' => array(
                'wp_themisdb_support_tickets' => array(
                    'status'                => 'closed',
                    'priority'              => 'medium',
                    'sla_due_at'            => '(24h nach created_at, bei Tier=standard)',
                    'sla_breached_at'       => 'NULL (falls < 24h)',
                    'resolved_at'           => '(NOW())',
                    'updated_at'            => '(NOW())',
                ),
                'wp_themisdb_support_messages' => array(
                    'count'                 => 3,  // Initial + Support + Kunde + Admin-Close = 4
                    'last_is_admin_reply'   => true,
                ),
            ),
            'expected_mails' => array(
                'Bestätigung: Ticket eingegangen' => 1,
                'Benachrichtigung: Status geändert' => 2,
                'Benachrichtigung: Ticket geschlossen' => 1,
            ),
        ),

        // ──────────────────────────────────────────────────────────────────────
        // Szenario 2: Change-Request: Kunde → Antrag → Admin-Review → Ausführung
        // ──────────────────────────────────────────────────────────────────────
        'change_request_lifecycle' => array(
            'title'   => 'Vertragsänderung: Antrag → Genehmigung → Ausführung',
            'steps'   => array(
                array(
                    'actor'  => 'Kunde',
                    'action' => 'Öffnet /vertragsaenderung, klickt "Neue Änderung"',
                    'check'  => 'Change-Request-Formular mit Feldern: Edition, max_nodes, max_cores, max_storage_gb, expiry_date, Begründung',
                ),
                array(
                    'actor'  => 'Kunde',
                    'action' => 'Wählt: Standard → Professional, max_nodes 10→20, Grund: "Wachstum"',
                    'check'  => 'Formular validiert Auswahl',
                ),
                array(
                    'actor'  => 'Kunde',
                    'action' => 'Klickt "Antrag stellen"',
                    'check'  => 'Request erstellt, Event `contract.change.requested` gefeuert',
                    'assertions' => array(
                        'ThemisDB_Contract_Change_Engine::on_change_requested() abgehört' => true,
                        'Impact-Analyse berechnet: upgrade, +10 nodes, etc.' => true,
                        'Impact in Payload gespeichert' => true,
                        'Eingangsbestätigung an Kunde versendet' => 'ARCHITECTUR.md §4 Event #7',
                    ),
                ),
                array(
                    'actor'  => 'Kunde',
                    'action' => 'Sieht auf /vertragsaenderung: "Ausstehend" Badge mit Impact-Zusammenfassung',
                    'check'  => 'Risiko: medium (wegen Upgrade), Finance-Review erforderlich',
                ),
                array(
                    'actor'  => 'Admin',
                    'action' => 'Öffnet /wp-admin/admin.php?page=themisdb-support-change-requests',
                    'check'  => 'Request-Liste mit Status "Ausstehend", Impact-Tabelle sichtbar',
                ),
                array(
                    'actor'  => 'Admin',
                    'action' => 'Klickt "Genehmigen", gibt Notiz: "Approved per pricing" ein',
                    'check'  => 'Status zu "confirmed" oder "pending_finance", Event `contract.change.approved` gefeuert',
                    'assertions' => array(
                        'ThemisDB_Contract_Change_Engine::on_change_approved() abgehört' => true,
                        'Kundenmail versendet: "Ihr Antrag wurde genehmigt"' => true,
                    ),
                ),
                array(
                    'actor'  => 'Kunde',
                    'action' => 'Erhält Mail: "Ihr Änderungsantrag wurde genehmigt. Änderungen sind jetzt aktiv."',
                    'check'  => 'Mail-Log zeigt Event `contract_change_approved`',
                ),
                array(
                    'actor'  => 'Kunde',
                    'action' => 'Öffnet /mein-cockpit, sieht: Edition jetzt "Professional", max_nodes=20',
                    'check'  => 'Lizenz-Informationen aktualisiert (delegiert an ThemisDB_License_Manager)',
                ),
            ),
            'expected_db_state' => array(
                'wp_themisdb_contract_lifecycle' => array(
                    'request_type'          => ThemisDB_Contract_Lifecycle::TYPE_CHANGE,
                    'status'                => 'confirmed',
                    'impact_analysis'       => array(
                        'change_count'      => 2,  // Edition + max_nodes
                        'risk_level'        => 'medium',
                        'price_delta'       => '(positive, wegen Upgrade)',
                    ),
                ),
                'wp_themisdb_mail_log' => array(
                    'new_entries'           => array(
                        'contract_change_requested' => 1,
                        'contract_change_approved'  => 1,
                    ),
                ),
            ),
        ),

        // ──────────────────────────────────────────────────────────────────────
        // Szenario 3: Termination-Request: Antrag → Genehmigung → Scheduler-Ausführung
        // ──────────────────────────────────────────────────────────────────────
        'termination_request_lifecycle' => array(
            'title'   => 'Kündigung: Antrag → Genehmigung → Scheduler-Ausführung',
            'steps'   => array(
                array(
                    'actor'  => 'Kunde',
                    'action' => 'Öffnet /vertragsaenderung, klickt "Kündigung einreichen"',
                    'check'  => 'Kündigungs-Formular mit Feld "Geplantes Enddatum"',
                ),
                array(
                    'actor'  => 'Kunde',
                    'action' => 'Gibt ein: Enddatum "+30 Tage", Grund: "Kein Bedarf mehr"',
                    'check'  => 'Formular validiert Mindestfrist (z. B. 30 Tage)',
                ),
                array(
                    'actor'  => 'Kunde',
                    'action' => 'Klickt "Kündigung einreichen"',
                    'check'  => 'Request erstellt, Event `contract.termination.requested` gefeuert',
                    'assertions' => array(
                        'ThemisDB_Contract_Termination_Engine::on_termination_requested() abgehört' => true,
                        'Eingangsbestätigung an Kunde versendet' => 'Enddatum in Mail',
                    ),
                ),
                array(
                    'actor'  => 'Admin',
                    'action' => 'Öffnet /wp-admin/admin.php?page=themisdb-support-terminations',
                    'check'  => 'Kündigungs-Request-Liste mit Wirkungsdatum angezeigt',
                ),
                array(
                    'actor'  => 'Admin',
                    'action' => 'Klickt "Bestätigen", gibt Notiz ein',
                    'check'  => 'Status zu "confirmed", Event `contract.termination.confirmed` gefeuert',
                    'assertions' => array(
                        'Kundenmail versendet: "Ihre Kündigung wurde bestätigt zum [Datum]"' => true,
                    ),
                ),
                array(
                    'actor'  => 'Scheduler',
                    'action' => 'WP-Cron-Hook `themisdb_contract_lifecycle_execute` (stündlich)',
                    'check'  => 'Sucht alle confirmed-Terminations mit effective_at <= NOW()',
                    'assertions' => array(
                        'ThemisDB_Contract_Lifecycle::execute_due_terminations() aufgerufen' => true,
                        'Lizenz-Status auf "inactive" gesetzt' => 'via ThemisDB_License_Manager::cancel_license()',
                        'Event `contract.termination.executed` gefeuert' => true,
                    ),
                ),
                array(
                    'actor'  => 'Kunde',
                    'action' => 'Erhält Mail: "Ihre Lizenz wurde deaktiviert"',
                    'check'  => 'Mail-Log zeigt `contract_termination_executed` (generiert von Lifecycle)',
                ),
                array(
                    'actor'  => 'Kunde',
                    'action' => 'Öffnet /mein-cockpit',
                    'check'  => 'Lizenz-Status: "Inactive", Support-Zugriff revoked',
                ),
            ),
            'expected_db_state' => array(
                'wp_themisdb_contract_lifecycle' => array(
                    'status'                => 'executed',
                    'executed_at'           => '(NOW())',
                ),
                'wp_themisdb_licenses' => array(
                    'status'                => 'inactive',
                ),
            ),
        ),

        // ──────────────────────────────────────────────────────────────────────
        // Szenario 4: SLA-Eskalation: Ticket geht zur Warnung, dann Breach
        // ──────────────────────────────────────────────────────────────────────
        'sla_escalation_scenario' => array(
            'title'   => 'SLA-Management: Warning → Breach → Management-Alert',
            'steps'   => array(
                array(
                    'actor'  => 'Kunde',
                    'action' => 'Erstellt Ticket (Tier=Standard, SLA=24h)',
                    'check'  => 'sla_due_at = NOW() + 24h',
                ),
                array(
                    'actor'  => 'Scheduler',
                    'action' => 'WP-Cron nach 18h: `themisdb_sla_check` (SLA-Eskalation-Prüfung)',
                    'check'  => 'ThemisDB_SLA_Escalation::check_sla_status() aufgerufen',
                    'assertions' => array(
                        '70% of 24h = ~17h: Warning Mail an Support & Kunde' => true,
                        'Event `sla_warning` gefeuert' => true,
                    ),
                ),
                array(
                    'actor'  => 'Support',
                    'action' => 'Erhält Mail: "Ticket [ID] überschreitet bald SLA (verbleibt 6h)"',
                    'check'  => 'Mail in Mail-Log',
                ),
                array(
                    'actor'  => 'Scheduler',
                    'action' => 'Nach 26h: Nächster Cron-Lauf',
                    'check'  => 'Ticket hat sla_due_at überschritten',
                    'assertions' => array(
                        'sla_breached_at wird gesetzt' => true,
                        'Event `sla_breach` gefeuert' => true,
                        'Eskalations-Mail an Management' => '(Mailbox admin@themisdb.org)',
                    ),
                ),
                array(
                    'actor'  => 'Management',
                    'action' => 'Erhält Alert-Mail: "SLA-Verletzung: Ticket [ID] offen seit 26h"',
                    'check'  => 'Mail-Log zeigt `sla_breach_escalation`',
                ),
                array(
                    'actor'  => 'Support',
                    'action' => 'Klickt auf Ticket im Admin, sieht rotes SLA-Badge "Verletzung"',
                    'check'  => 'CSS-Styling: background=#e74c3c (rot)',
                ),
            ),
            'expected_mails' => array(
                'SLA-Warning (70%)' => 2,  // Support + Kunde
                'SLA-Breach (100%)' => 2,  // Support + Management
            ),
        ),

        // ──────────────────────────────────────────────────────────────────────
        // Szenario 5: Observability-Dashboard: Metriken erfassen & cachen
        // ──────────────────────────────────────────────────────────────────────
        'observability_metrics_scenario' => array(
            'title'   => 'Dashboard-Metriken: Erfassung & 5-Min-Cache',
            'steps'   => array(
                array(
                    'actor'  => 'Admin',
                    'action' => 'Öffnet /wp-admin/admin.php?page=themisdb-support-observability',
                    'check'  => 'Dashboard mit 6 Metrik-Widgets geladen',
                ),
                array(
                    'actor'  => 'System',
                    'action' => 'Beim Laden: ThemisDB_Observability::get_first_response_time() aufgerufen',
                    'check'  => 'Query: AVG(first_reply_timestamp - created_at) der letzten 30 Tage',
                    'assertions' => array(
                        'Transient `themisdb_obs_frt` für 5 Min. gecacht' => true,
                        'Bei erneutem Load innerhalb 5 Min: Cache gelesen' => true,
                    ),
                ),
                array(
                    'actor'  => 'System',
                    'action' => 'Weitere Metriken berechnet: SLA-Breach-Rate, Ticket-Volumen, Queue-Distribution',
                    'check'  => 'Alle Transients in wp_options:',
                    'assertions' => array(
                        'themisdb_obs_sla_rate' => 'z. B. 12.5% (breached/total)',
                        'themisdb_obs_volume' => '{date: count, ...}',
                        'themisdb_obs_queues' => '{queue: open_count, ...}',
                    ),
                ),
                array(
                    'actor'  => 'Admin',
                    'action' => 'Nach Änderung eines Tickets: Cache wird invalidiert via `flush_cache()`',
                    'check'  => 'Alle Transients gelöscht, nächste Admin-Page lädt neu',
                ),
            ),
            'expected_metrics' => array(
                'First Response Time' => 'z. B. 4.2h',
                'SLA Breach Rate' => 'z. B. 8%',
                'Ticket Volume (14d)' => 'z. B. [5, 3, 7, 2, ...]',
                'Queue Distribution' => '{triage: 5, technical: 8, sales: 2}',
                'Incident Summary' => '{critical: 0, high: 1, medium: 2}',
                'Avg Resolution Time' => 'z. B. 18.5h',
            ),
        ),
    ),

    // ──────────────────────────────────────────────────────────────────────
    // Manuelle Prüfliste für Code-Review-Audit
    // ──────────────────────────────────────────────────────────────────────
    'code_review_checklist' => array(
        'Security' => array(
            '[ ] Nonce-Validierung bei allen POST/admin-post Actions' => true,
            '[ ] $wpdb->prepare() bei allen externen SQL-Parametern' => true,
            '[ ] current_user_can("manage_options") bei Admin-Seiten' => true,
            '[ ] esc_html/esc_url/esc_attr bei allen HTML-Ausgaben' => true,
            '[ ] class_exists() vor Cross-Domain-Aufrufen' => true,
        ),
        'Database' => array(
            '[ ] Alle 4 Tabellen (tickets, messages, incident_log, mail_log) erstellt' => true,
            '[ ] DB-Version korrekt (DB_VERSION = "1.0.5")' => true,
            '[ ] dbDelta() wird bei Aktivierung aufgerufen' => true,
            '[ ] drop_tables() bereinigt auch neue Tabellen' => true,
        ),
        'Events' => array(
            '[ ] Alle 11 Event-Flow-Schritte (ARCHITECTUR.md §4) abgedeckt' => true,
            '[ ] Mail-Orchestrator abonniert alle kritischen Events' => true,
            '[ ] Lifecycle-Audit-Logs bei jedem Event-Übergang' => true,
        ),
        'Admin-UI' => array(
            '[ ] 7 Submenu-Seiten: Tickets, Incidents, Observability, Change-Requests, Terminations, Mail-Log, Einstellungen' => true,
            '[ ] Filter, Sorting, Pagination auf Ticket-Liste' => true,
            '[ ] Inline-Approve/Reject für Change/Termination-Requests' => true,
            '[ ] Scheduler-Status mit "Jetzt ausführen"-Button' => true,
        ),
        'Shortcodes' => array(
            '[ ] [themisdb_support_login] mit Lizenz-Auth-Gate' => true,
            '[ ] [themisdb_support_portal] mit Ticket-Management' => true,
            '[ ] [themisdb_lifecycle_portal] mit Change/Termination-Requests' => true,
            '[ ] [themisdb_cockpit] mit Health-Dashboard' => true,
        ),
    ),
);
