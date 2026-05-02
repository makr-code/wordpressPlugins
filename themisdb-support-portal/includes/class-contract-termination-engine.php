<?php
/**
 * ThemisDB Support Portal – Contract Termination Engine (ARCHITECTUR.md §8.7)
 *
 * Ergänzt die bestehende ThemisDB_Contract_Lifecycle-Klasse um:
 *   - Kundenmails bei Eingang, Bestätigung und Ablehnung von Kündigungsanträgen
 *   - Admin-Schnittstelle zur Übersicht und Genehmigung
 *   - Hilfsmethoden für die Admin-Review-Ansicht
 *
 * Die eigentliche Scheduler-Ausführung (stündlicher Cron + Lizenzdeaktivierung
 * + PDF-Mail) liegt vollständig in ThemisDB_Contract_Lifecycle::execute_due_terminations()
 * im Plugin themisdb-order-request.
 *
 * Events, auf die reagiert wird:
 *   contract.termination.requested  → Eingangsbestätigung an Kunden
 *   contract.termination.confirmed  → Bestätigung mit Wirkungsdatum
 *   contract.termination.rejected   → Ablehnungsbenachrichtigung
 *   contract.termination.executed   → (nur Audit-Log; Mail kommt aus Lifecycle)
 */

if (!defined('ABSPATH')) {
    exit;
}

class ThemisDB_Contract_Termination_Engine {

    // ------------------------------------------------------------------
    // Init
    // ------------------------------------------------------------------

    /**
     * WordPress-Hooks registrieren. Aus Plugin-Bootstrap aufrufen.
     */
    public static function init() {
        add_action('contract.termination.requested',       array(__CLASS__, 'on_termination_requested'), 10, 2);
        add_action('contract.termination.confirmed',       array(__CLASS__, 'on_termination_confirmed'), 10, 2);
        add_action('contract.termination.rejected',        array(__CLASS__, 'on_termination_rejected'),  10, 2);
        add_action('contract.termination.ops_rejected',    array(__CLASS__, 'on_termination_rejected'),  10, 2);
        add_action('contract.termination.finance_rejected',array(__CLASS__, 'on_termination_rejected'),  10, 2);
        add_action('contract.termination.executed',        array(__CLASS__, 'on_termination_executed'),  10, 2);
    }

    // ------------------------------------------------------------------
    // Event-Handler
    // ------------------------------------------------------------------

    /**
     * Nach Eingang eines Kündigungsantrags: Eingangsbestätigung an Kunden.
     *
     * @param int $request_id
     * @param int $license_id
     */
    public static function on_termination_requested($request_id, $license_id) {
        if (!class_exists('ThemisDB_Mail_Orchestrator') || !class_exists('ThemisDB_Contract_Lifecycle')) {
            return;
        }

        $request = ThemisDB_Contract_Lifecycle::get_request(intval($request_id));
        if (!$request) {
            return;
        }

        $email = self::get_customer_email(intval($license_id));
        if (!$email) {
            return;
        }

        $effective_date = !empty($request['effective_at'])
            ? mysql2date('d.m.Y', $request['effective_at'])
            : __('wird noch bestimmt', 'themisdb-support-portal');

        ThemisDB_Mail_Orchestrator::send(
            $email,
            __('[ThemisDB] Ihr Kündigungsantrag ist eingegangen', 'themisdb-support-portal'),
            self::build_mail_body(
                __('Guten Tag,', 'themisdb-support-portal'),
                array(
                    sprintf(
                        __('Wir haben Ihren Kündigungsantrag (ID: %d) für Lizenz #%d erhalten.', 'themisdb-support-portal'),
                        intval($request_id),
                        intval($license_id)
                    ),
                    sprintf(
                        __('Gewünschtes Wirkungsdatum: %s', 'themisdb-support-portal'),
                        $effective_date
                    ),
                    __('Ihr Antrag wird von unserem Team geprüft. Sie erhalten nach der Prüfung eine weitere Nachricht.', 'themisdb-support-portal'),
                )
            ),
            'contract_termination_requested',
            array('request_id' => $request_id, 'license_id' => $license_id)
        );
    }

    /**
     * Nach Bestätigung der Kündigung: Bestätigungsmail mit Wirkungsdatum.
     *
     * @param int $request_id
     * @param int $license_id
     */
    public static function on_termination_confirmed($request_id, $license_id) {
        if (!class_exists('ThemisDB_Mail_Orchestrator') || !class_exists('ThemisDB_Contract_Lifecycle')) {
            return;
        }

        $request = ThemisDB_Contract_Lifecycle::get_request(intval($request_id));
        if (!$request) {
            return;
        }

        $email = self::get_customer_email(intval($license_id));
        if (!$email) {
            return;
        }

        $effective_date = !empty($request['effective_at'])
            ? mysql2date('d.m.Y', $request['effective_at'])
            : __('umgehend', 'themisdb-support-portal');

        ThemisDB_Mail_Orchestrator::send(
            $email,
            __('[ThemisDB] Ihre Kündigung wurde bestätigt', 'themisdb-support-portal'),
            self::build_mail_body(
                __('Guten Tag,', 'themisdb-support-portal'),
                array(
                    sprintf(
                        __('Ihre Kündigung (Antrag #%d) wurde bestätigt.', 'themisdb-support-portal'),
                        intval($request_id)
                    ),
                    sprintf(
                        __('Ihr Vertrag und Ihre Lizenzzugänge werden zum %s automatisch beendet.', 'themisdb-support-portal'),
                        $effective_date
                    ),
                    __('Sie erhalten nach der Ausführung eine abschließende Bestätigung. Bei Fragen wenden Sie sich bitte an unseren Support.', 'themisdb-support-portal'),
                )
            ),
            'contract_termination_confirmed',
            array('request_id' => $request_id, 'license_id' => $license_id)
        );
    }

    /**
     * Nach Ablehnung der Kündigung: Ablehnungsbenachrichtigung.
     *
     * @param int $request_id
     * @param int $license_id
     */
    public static function on_termination_rejected($request_id, $license_id) {
        if (!class_exists('ThemisDB_Mail_Orchestrator') || !class_exists('ThemisDB_Contract_Lifecycle')) {
            return;
        }

        $request = ThemisDB_Contract_Lifecycle::get_request(intval($request_id));
        if (!$request) {
            return;
        }

        $email = self::get_customer_email(intval($license_id));
        if (!$email) {
            return;
        }

        $review_note = isset($request['review_note']) ? trim((string) $request['review_note']) : '';
        $lines = array(
            sprintf(
                __('Ihr Kündigungsantrag (ID: %d) wurde leider abgelehnt.', 'themisdb-support-portal'),
                intval($request_id)
            ),
        );
        if ($review_note) {
            $lines[] = __('Begründung:', 'themisdb-support-portal') . ' ' . $review_note;
        }
        $lines[] = __('Ihr Vertrag läuft zu den bestehenden Bedingungen weiter. Bei Fragen wenden Sie sich bitte an unseren Support.', 'themisdb-support-portal');

        ThemisDB_Mail_Orchestrator::send(
            $email,
            __('[ThemisDB] Ihr Kündigungsantrag wurde abgelehnt', 'themisdb-support-portal'),
            self::build_mail_body(__('Guten Tag,', 'themisdb-support-portal'), $lines),
            'contract_termination_rejected',
            array('request_id' => $request_id, 'license_id' => $license_id)
        );
    }

    /**
     * Nach Ausführung: nur Audit-Log-Eintrag (Mail kommt aus ThemisDB_Contract_Lifecycle).
     *
     * @param int $request_id
     * @param int $license_id
     */
    public static function on_termination_executed($request_id, $license_id) {
        if (!class_exists('ThemisDB_Contract_Lifecycle')) {
            return;
        }

        ThemisDB_Contract_Lifecycle::add_log(
            intval($request_id),
            'termination_engine_audit',
            0,
            sprintf(
                __('Kündigung ausgeführt. Lizenz #%d durch Scheduler deaktiviert.', 'themisdb-support-portal'),
                intval($license_id)
            ),
            array('executed_by' => 'scheduler', 'license_id' => $license_id)
        );
    }

    // ------------------------------------------------------------------
    // Datenzugriff
    // ------------------------------------------------------------------

    /**
     * Listet alle Kündigungsanträge.
     *
     * @param array $args
     * @return array
     */
    public static function list_termination_requests($args = array()) {
        if (!class_exists('ThemisDB_Contract_Lifecycle')) {
            return array();
        }

        $defaults = array(
            'request_type' => ThemisDB_Contract_Lifecycle::TYPE_TERMINATION,
            'limit'        => 50,
            'orderby'      => 'created_at',
            'order'        => 'DESC',
        );

        $result = ThemisDB_Contract_Lifecycle::list_requests(array_merge($defaults, $args));
        return is_array($result) ? $result : array();
    }

    /**
     * Kündigungsantrag bestätigen (Genehmigung).
     *
     * @param int    $request_id
     * @param string $review_note
     * @return true|WP_Error
     */
    public static function approve($request_id, $review_note = '') {
        if (!class_exists('ThemisDB_Contract_Lifecycle')) {
            return new WP_Error('missing_class', 'ThemisDB_Contract_Lifecycle nicht verfügbar.');
        }

        return ThemisDB_Contract_Lifecycle::review_request(
            intval($request_id),
            true,
            $review_note,
            get_current_user_id()
        );
    }

    /**
     * Kündigungsantrag ablehnen.
     *
     * @param int    $request_id
     * @param string $review_note
     * @return true|WP_Error
     */
    public static function reject($request_id, $review_note = '') {
        if (!class_exists('ThemisDB_Contract_Lifecycle')) {
            return new WP_Error('missing_class', 'ThemisDB_Contract_Lifecycle nicht verfügbar.');
        }

        return ThemisDB_Contract_Lifecycle::review_request(
            intval($request_id),
            false,
            $review_note,
            get_current_user_id()
        );
    }

    /**
     * Sofortige manuelle Ausführung aller fälligen Kündigungen (Admin-Trigger).
     *
     * @return array{processed:int, executed:int, failed:int}
     */
    public static function run_scheduler_now() {
        if (!class_exists('ThemisDB_Contract_Lifecycle')) {
            return array('processed' => 0, 'executed' => 0, 'failed' => 0);
        }

        return ThemisDB_Contract_Lifecycle::execute_due_terminations();
    }

    // ------------------------------------------------------------------
    // Darstellungs-Helfer
    // ------------------------------------------------------------------

    /**
     * Status-Label (lesbar).
     *
     * @param string $status
     * @return string
     */
    public static function status_label($status) {
        $labels = array(
            'requested'       => __('Ausstehend', 'themisdb-support-portal'),
            'confirmed'       => __('Bestätigt / geplant', 'themisdb-support-portal'),
            'rejected'        => __('Abgelehnt', 'themisdb-support-portal'),
            'executed'        => __('Ausgeführt', 'themisdb-support-portal'),
            'pending_finance' => __('Finance-Review', 'themisdb-support-portal'),
            'pending_ops'     => __('Ops-Review', 'themisdb-support-portal'),
        );
        return isset($labels[$status]) ? $labels[$status] : ucfirst(str_replace('_', ' ', $status));
    }

    /**
     * Status-Farbe.
     *
     * @param string $status
     * @return string
     */
    public static function status_color($status) {
        $map = array(
            'requested'       => '#f39c12',
            'confirmed'       => '#27ae60',
            'rejected'        => '#e74c3c',
            'executed'        => '#3498db',
            'pending_finance' => '#9b59b6',
            'pending_ops'     => '#e67e22',
        );
        return isset($map[$status]) ? $map[$status] : '#999';
    }

    // ------------------------------------------------------------------
    // Interne Helfer
    // ------------------------------------------------------------------

    /**
     * E-Mail-Adresse aus Lizenz → customer_id → WP-User.
     *
     * @param int $license_id
     * @return string
     */
    private static function get_customer_email($license_id) {
        if (!class_exists('ThemisDB_License_Manager')) {
            return '';
        }

        $license = ThemisDB_License_Manager::get_license(intval($license_id));
        if (!is_array($license)) {
            return '';
        }

        if (!empty($license['customer_id'])) {
            $user = get_userdata(intval($license['customer_id']));
            if ($user && $user->user_email) {
                return sanitize_email($user->user_email);
            }
        }

        return '';
    }

    /**
     * Einfacher Plaintext-Mailkörper.
     *
     * @param string   $greeting
     * @param string[] $lines
     * @return string
     */
    private static function build_mail_body($greeting, array $lines) {
        $site_name = get_bloginfo('name');
        $sep       = str_repeat('-', 60);

        $body  = $greeting . "\n\n";
        $body .= implode("\n\n", array_map('wp_strip_all_tags', $lines));
        $body .= "\n\n" . $sep . "\n" . $site_name . "\n" . home_url() . "\n";

        return $body;
    }
}
