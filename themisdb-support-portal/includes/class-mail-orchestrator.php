<?php
/**
 * ThemisDB Support Portal – Mail-Orchestrator (ARCHITECTUR.md §8.4)
 *
 * Zentrale Versandstelle für alle Lifecycle-Mails. Registriert Hooks auf
 * Plugin-eigene WP-Actions und Cross-Domain-Actions (Order, Lizenz, Build).
 * Jede versendete Mail wird in wp_themisdb_mail_log protokolliert.
 *
 * Unterstützte Events:
 *   ticket_created          → Eingangsbestätigung an Kunden
 *   ticket_status_changed   → Statusänderung an Kunden
 *   order_created           → Bestelleingang an Kunden + Admin
 *   order_approved          → Auftragsbestätigung an Kunden
 *   order_rejected          → Ablehnung an Kunden
 *   license_activated       → Lizenzzugang an Kunden
 *   build_completed         → Build-Artefakt an Kunden
 *   build_failed            → Build-Fehler an Kunden + Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

class ThemisDB_Mail_Orchestrator {

    // ------------------------------------------------------------------
    // Konstanten
    // ------------------------------------------------------------------

    const LOG_TABLE   = 'themisdb_mail_log';
    const STATUS_SENT = 'sent';
    const STATUS_FAIL = 'failed';

    // Lifecycle-Event-Typen.
    const EVENT_TICKET_CREATED         = 'ticket_created';
    const EVENT_TICKET_STATUS_CHANGED  = 'ticket_status_changed';
    const EVENT_ORDER_CREATED          = 'order_created';
    const EVENT_ORDER_APPROVED         = 'order_approved';
    const EVENT_ORDER_REJECTED         = 'order_rejected';
    const EVENT_LICENSE_ACTIVATED      = 'license_activated';
    const EVENT_BUILD_COMPLETED        = 'build_completed';
    const EVENT_BUILD_FAILED           = 'build_failed';

    // ------------------------------------------------------------------
    // Initialisierung
    // ------------------------------------------------------------------

    /**
     * Hooks registrieren. Wird aus dem Plugin-Bootstrap aufgerufen.
     */
    public static function init() {
        // Support-Portal-Events (gefeuert von ThemisDB_Ticket_Manager).
        add_action(
            'themisdb_support_portal_ticket_created',
            array(__CLASS__, 'on_ticket_created'),
            20,
            3
        );
        add_action(
            'themisdb_support_portal_ticket_status_changed',
            array(__CLASS__, 'on_ticket_status_changed'),
            20,
            4
        );

        // Cross-Domain-Events (gefeuert von themisdb-order-request oder
        // themisdb-github-bridge via do_action).
        add_action('themisdb_order_created',      array(__CLASS__, 'on_order_created'),     20, 1);
        add_action('themisdb_order_approved',     array(__CLASS__, 'on_order_approved'),    20, 1);
        add_action('themisdb_order_rejected',     array(__CLASS__, 'on_order_rejected'),    20, 1);
        add_action('themisdb_license_activated',  array(__CLASS__, 'on_license_activated'), 20, 1);
        add_action('themisdb_build_completed',    array(__CLASS__, 'on_build_completed'),   20, 1);
        add_action('themisdb_build_failed',       array(__CLASS__, 'on_build_failed'),      20, 1);
    }

    // ------------------------------------------------------------------
    // Event-Handler – Support-Portal
    // ------------------------------------------------------------------

    /**
     * Eingangsbestätigung an den Kunden nach Ticket-Erstellung.
     *
     * @param int   $ticket_id
     * @param array $ticket_data  Prepared ticket row.
     * @param array $raw_data     Original input data (may contain extra keys).
     */
    public static function on_ticket_created($ticket_id, $ticket_data = array(), $raw_data = array()) {
        if (empty($ticket_data['customer_email'])) {
            return;
        }

        $number  = isset($ticket_data['ticket_number']) ? $ticket_data['ticket_number'] : '#' . $ticket_id;
        $subject = isset($ticket_data['subject'])       ? $ticket_data['subject']       : '';
        $to      = $ticket_data['customer_email'];
        $name    = isset($ticket_data['customer_name']) ? $ticket_data['customer_name'] : $to;

        $mail_subject = sprintf(
            /* translators: %s: Ticket-Nummer */
            __('[Support] Ihr Ticket %s wurde erfasst', 'themisdb-support-portal'),
            $number
        );

        $body = self::wrap_template(
            sprintf(__('Guten Tag %s,', 'themisdb-support-portal'), esc_html($name)),
            array(
                sprintf(
                    __('Ihr Support-Ticket %s (%s) wurde erfolgreich erfasst.', 'themisdb-support-portal'),
                    $number,
                    $subject
                ),
                __('Unser Team wird sich so bald wie möglich bei Ihnen melden. Die vereinbarten Reaktionszeiten entnehmen Sie bitte Ihrem Servicevertrag.', 'themisdb-support-portal'),
            )
        );

        self::send(
            $to,
            $mail_subject,
            $body,
            self::EVENT_TICKET_CREATED,
            array('ticket_id' => $ticket_id, 'ticket_number' => $number)
        );
    }

    /**
     * Statusänderungs-Mail an den Kunden (z. B. in_progress → resolved).
     *
     * @param int    $ticket_id
     * @param string $old_status
     * @param string $new_status
     * @param array  $ticket     Full ticket row.
     */
    public static function on_ticket_status_changed($ticket_id, $old_status, $new_status, $ticket = array()) {
        // Keine Mail bei internen Statuswechseln ohne Kundenrelevanz.
        $notify_statuses = array('resolved', 'closed', 'waiting_for_customer');
        if (!in_array($new_status, $notify_statuses, true)) {
            return;
        }

        if (empty($ticket['customer_email'])) {
            return;
        }

        $number = isset($ticket['ticket_number']) ? $ticket['ticket_number'] : '#' . $ticket_id;
        $to     = $ticket['customer_email'];
        $name   = isset($ticket['customer_name']) ? $ticket['customer_name'] : $to;

        $status_label = self::status_label($new_status);

        $mail_subject = sprintf(
            /* translators: 1: Ticket-Nummer, 2: neuer Status */
            __('[Support] Ticket %1$s – Status: %2$s', 'themisdb-support-portal'),
            $number,
            $status_label
        );

        $lines = array(
            sprintf(
                __('Der Status Ihres Tickets %s wurde auf "%s" geändert.', 'themisdb-support-portal'),
                $number,
                $status_label
            ),
        );

        if ($new_status === 'waiting_for_customer') {
            $lines[] = __('Bitte antworten Sie auf dieses Ticket, damit wir weiterhelfen können.', 'themisdb-support-portal');
        } elseif ($new_status === 'resolved') {
            $lines[] = __('Falls das Problem weiterhin besteht, antworten Sie bitte auf dieses Ticket.', 'themisdb-support-portal');
        }

        $body = self::wrap_template(
            sprintf(__('Guten Tag %s,', 'themisdb-support-portal'), esc_html($name)),
            $lines
        );

        self::send(
            $to,
            $mail_subject,
            $body,
            self::EVENT_TICKET_STATUS_CHANGED,
            array('ticket_id' => $ticket_id, 'old_status' => $old_status, 'new_status' => $new_status)
        );
    }

    // ------------------------------------------------------------------
    // Event-Handler – Cross-Domain (Order, Lizenz, Build)
    // ------------------------------------------------------------------

    /**
     * Bestelleingang: Mail an Kunden + Admin-Benachrichtigung.
     *
     * @param int|array $order_or_id  Order-ID oder komplettes Order-Array.
     */
    public static function on_order_created($order_or_id) {
        $order = self::resolve_order($order_or_id);
        if (!$order || empty($order['customer_email'])) {
            return;
        }

        $to      = $order['customer_email'];
        $name    = isset($order['customer_name']) ? $order['customer_name'] : $to;
        $order_n = isset($order['order_number'])  ? $order['order_number']  : '#' . (isset($order['id']) ? $order['id'] : '?');

        $mail_subject = sprintf(
            __('[ThemisDB] Ihre Anfrage %s ist eingegangen', 'themisdb-support-portal'),
            $order_n
        );

        $body = self::wrap_template(
            sprintf(__('Guten Tag %s,', 'themisdb-support-portal'), esc_html($name)),
            array(
                sprintf(
                    __('Wir haben Ihre Anfrage %s erhalten und werden sie schnellstmöglich bearbeiten.', 'themisdb-support-portal'),
                    $order_n
                ),
                __('Sie erhalten eine weitere Nachricht, sobald Ihr Angebot fertiggestellt ist.', 'themisdb-support-portal'),
            )
        );

        self::send(
            $to,
            $mail_subject,
            $body,
            self::EVENT_ORDER_CREATED,
            array('order_id' => isset($order['id']) ? $order['id'] : null)
        );

        // Admin informieren.
        $admin_email = get_option('admin_email');
        if ($admin_email) {
            self::send(
                $admin_email,
                sprintf(__('[ThemisDB] Neue Bestellung %s', 'themisdb-support-portal'), $order_n),
                self::wrap_template(
                    __('Neue Bestellung eingegangen:', 'themisdb-support-portal'),
                    array(
                        'Kunde: ' . esc_html($name),
                        'E-Mail: ' . esc_html($to),
                        'Bestell-Nr.: ' . esc_html($order_n),
                    )
                ),
                self::EVENT_ORDER_CREATED,
                array('order_id' => isset($order['id']) ? $order['id'] : null, 'recipient_role' => 'admin')
            );
        }
    }

    /**
     * Bestellung genehmigt: Auftragsbestätigung + nächste Schritte.
     *
     * @param int|array $order_or_id
     */
    public static function on_order_approved($order_or_id) {
        $order = self::resolve_order($order_or_id);
        if (!$order || empty($order['customer_email'])) {
            return;
        }

        $to      = $order['customer_email'];
        $name    = isset($order['customer_name']) ? $order['customer_name'] : $to;
        $order_n = isset($order['order_number'])  ? $order['order_number']  : '#' . (isset($order['id']) ? $order['id'] : '?');

        $body = self::wrap_template(
            sprintf(__('Guten Tag %s,', 'themisdb-support-portal'), esc_html($name)),
            array(
                sprintf(__('Ihre Bestellung %s wurde genehmigt.', 'themisdb-support-portal'), $order_n),
                __('Ihr Vertrag wird vorbereitet und Ihnen in Kürze zur Verfügung gestellt. Die Zahlungsaufforderung erhalten Sie separat.', 'themisdb-support-portal'),
            )
        );

        self::send(
            $to,
            sprintf(__('[ThemisDB] Bestellung %s – Genehmigt', 'themisdb-support-portal'), $order_n),
            $body,
            self::EVENT_ORDER_APPROVED,
            array('order_id' => isset($order['id']) ? $order['id'] : null)
        );
    }

    /**
     * Bestellung abgelehnt: Absage an Kunden.
     *
     * @param int|array $order_or_id
     */
    public static function on_order_rejected($order_or_id) {
        $order = self::resolve_order($order_or_id);
        if (!$order || empty($order['customer_email'])) {
            return;
        }

        $to      = $order['customer_email'];
        $name    = isset($order['customer_name']) ? $order['customer_name'] : $to;
        $order_n = isset($order['order_number'])  ? $order['order_number']  : '#' . (isset($order['id']) ? $order['id'] : '?');

        $body = self::wrap_template(
            sprintf(__('Guten Tag %s,', 'themisdb-support-portal'), esc_html($name)),
            array(
                sprintf(__('Leider können wir Ihre Anfrage %s zu diesem Zeitpunkt nicht annehmen.', 'themisdb-support-portal'), $order_n),
                __('Bei Fragen zu dieser Entscheidung wenden Sie sich bitte an unseren Vertrieb.', 'themisdb-support-portal'),
            )
        );

        self::send(
            $to,
            sprintf(__('[ThemisDB] Anfrage %s – Nicht angenommen', 'themisdb-support-portal'), $order_n),
            $body,
            self::EVENT_ORDER_REJECTED,
            array('order_id' => isset($order['id']) ? $order['id'] : null)
        );
    }

    /**
     * Lizenz aktiviert: Zugangsdaten an Kunden.
     *
     * @param int|array $license_or_id
     */
    public static function on_license_activated($license_or_id) {
        $license = self::resolve_license($license_or_id);
        if (!$license) {
            return;
        }

        $to   = isset($license['customer_email']) ? $license['customer_email'] : '';
        $name = isset($license['customer_name'])  ? $license['customer_name']  : $to;
        $key  = isset($license['license_key'])    ? $license['license_key']    : '—';
        $tier = isset($license['tier'])           ? ucfirst($license['tier'])  : '—';

        if (empty($to)) {
            return;
        }

        $body = self::wrap_template(
            sprintf(__('Guten Tag %s,', 'themisdb-support-portal'), esc_html($name)),
            array(
                __('Ihre ThemisDB-Lizenz wurde aktiviert. Sie können ab sofort auf alle lizenzierten Funktionen zugreifen.', 'themisdb-support-portal'),
                'Lizenzschlüssel: ' . esc_html($key),
                'Tier: ' . esc_html($tier),
                __('Bei Problemen steht Ihnen unser Support-Portal zur Verfügung.', 'themisdb-support-portal'),
            )
        );

        self::send(
            $to,
            __('[ThemisDB] Ihre Lizenz ist aktiv', 'themisdb-support-portal'),
            $body,
            self::EVENT_LICENSE_ACTIVATED,
            array('license_key' => $key, 'tier' => isset($license['tier']) ? $license['tier'] : null)
        );
    }

    /**
     * Build erfolgreich: Artefakt-Link an Kunden.
     *
     * @param int|array $build_or_id
     */
    public static function on_build_completed($build_or_id) {
        $build = is_array($build_or_id) ? $build_or_id : array('id' => intval($build_or_id));

        $to           = isset($build['customer_email']) ? $build['customer_email'] : '';
        $name         = isset($build['customer_name'])  ? $build['customer_name']  : $to;
        $artifact_url = isset($build['artifact_url'])   ? $build['artifact_url']   : '';
        $build_id     = isset($build['id'])             ? $build['id']             : $build_or_id;

        if (empty($to)) {
            return;
        }

        $lines = array(
            __('Ihr Build wurde erfolgreich abgeschlossen. Das Artefakt steht zum Download bereit.', 'themisdb-support-portal'),
        );
        if ($artifact_url) {
            $lines[] = 'Download: ' . esc_url($artifact_url);
        }

        $body = self::wrap_template(
            sprintf(__('Guten Tag %s,', 'themisdb-support-portal'), esc_html($name)),
            $lines
        );

        self::send(
            $to,
            __('[ThemisDB] Ihr Build ist fertig', 'themisdb-support-portal'),
            $body,
            self::EVENT_BUILD_COMPLETED,
            array('build_id' => $build_id, 'artifact_url' => $artifact_url)
        );
    }

    /**
     * Build fehlgeschlagen: Fehlermail an Kunden + Admin.
     *
     * @param int|array $build_or_id
     */
    public static function on_build_failed($build_or_id) {
        $build = is_array($build_or_id) ? $build_or_id : array('id' => intval($build_or_id));

        $to       = isset($build['customer_email']) ? $build['customer_email'] : '';
        $name     = isset($build['customer_name'])  ? $build['customer_name']  : $to;
        $build_id = isset($build['id'])             ? $build['id']             : $build_or_id;
        $error    = isset($build['error'])          ? $build['error']          : '';

        if (!empty($to)) {
            $body = self::wrap_template(
                sprintf(__('Guten Tag %s,', 'themisdb-support-portal'), esc_html($name)),
                array(
                    __('Ihr Build konnte leider nicht abgeschlossen werden. Unser Team wurde informiert und kümmert sich um das Problem.', 'themisdb-support-portal'),
                    __('Sie werden benachrichtigt, sobald der Build erfolgreich wiederholt wurde.', 'themisdb-support-portal'),
                )
            );

            self::send(
                $to,
                __('[ThemisDB] Build fehlgeschlagen – wir kümmern uns', 'themisdb-support-portal'),
                $body,
                self::EVENT_BUILD_FAILED,
                array('build_id' => $build_id)
            );
        }

        // Admin-Alert.
        $admin_email = get_option('admin_email');
        if ($admin_email) {
            $admin_lines = array(
                'Build-ID: ' . esc_html((string) $build_id),
            );
            if ($error) {
                $admin_lines[] = 'Fehler: ' . esc_html($error);
            }
            if (!empty($to)) {
                $admin_lines[] = 'Kunde: ' . esc_html($to);
            }

            self::send(
                $admin_email,
                __('[ThemisDB] Build-Fehler aufgetreten', 'themisdb-support-portal'),
                self::wrap_template(__('Ein Build ist fehlgeschlagen:', 'themisdb-support-portal'), $admin_lines),
                self::EVENT_BUILD_FAILED,
                array('build_id' => $build_id, 'recipient_role' => 'admin')
            );
        }

        // Incident im Log erfassen.
        if (class_exists('ThemisDB_Incident_Log')) {
            ThemisDB_Incident_Log::log(
                ThemisDB_Incident_Log::DOMAIN_BUILD,
                ThemisDB_Incident_Log::SEVERITY_HIGH,
                'Build fehlgeschlagen (ID: ' . intval($build_id) . ')',
                array('build_id' => $build_id, 'error' => $error)
            );
        }
    }

    // ------------------------------------------------------------------
    // Zentraler Versand + Logging
    // ------------------------------------------------------------------

    /**
     * Mail versenden und Ergebnis in wp_themisdb_mail_log protokollieren.
     *
     * @param string $to          Empfänger-E-Mail.
     * @param string $subject     Betreff.
     * @param string $body        Plaintext-Mailkörper.
     * @param string $event_type  Einer der EVENT_*-Konstanten.
     * @param array  $context     Optionale Kontextdaten (werden als JSON gespeichert).
     * @return bool               True wenn wp_mail() erfolgreich.
     */
    public static function send($to, $subject, $body, $event_type, $context = array()) {
        $to      = sanitize_email($to);
        $subject = sanitize_text_field($subject);

        if (empty($to)) {
            return false;
        }

        $headers = array('Content-Type: text/plain; charset=UTF-8');
        $result  = wp_mail($to, $subject, $body, $headers);

        self::log_mail(
            $event_type,
            $to,
            $subject,
            $result ? self::STATUS_SENT : self::STATUS_FAIL,
            $result ? null : 'wp_mail() returned false',
            $context
        );

        return $result;
    }

    // ------------------------------------------------------------------
    // Mail-Log Datenbankoperationen
    // ------------------------------------------------------------------

    /**
     * Eintrag in wp_themisdb_mail_log schreiben.
     *
     * @param string      $event_type
     * @param string      $recipient
     * @param string      $subject
     * @param string      $status      'sent' | 'failed'
     * @param string|null $error_msg
     * @param array       $context
     */
    private static function log_mail($event_type, $recipient, $subject, $status, $error_msg, $context) {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . self::LOG_TABLE,
            array(
                'event_type'   => substr($event_type, 0, 50),
                'recipient'    => substr($recipient, 0, 255),
                'subject'      => substr($subject, 0, 500),
                'status'       => $status,
                'error_msg'    => $error_msg ? substr($error_msg, 0, 500) : null,
                'context_json' => !empty($context) ? wp_json_encode($context) : null,
                'sent_at'      => current_time('mysql'),
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
    }

    /**
     * Log-Einträge abrufen.
     *
     * @param array $args {
     *     @type string $event_type  Filter nach Event-Typ.
     *     @type string $status      Filter nach 'sent' | 'failed'.
     *     @type int    $limit       Max. Zeilen (default 50).
     *     @type int    $offset      Offset für Paginierung (default 0).
     * }
     * @return array
     */
    public static function get_log($args = array()) {
        global $wpdb;

        $table  = $wpdb->prefix . self::LOG_TABLE;
        $limit  = isset($args['limit'])  ? max(1, intval($args['limit']))  : 50;
        $offset = isset($args['offset']) ? max(0, intval($args['offset'])) : 0;

        $where  = array('1=1');
        $values = array();

        if (!empty($args['event_type'])) {
            $where[]  = 'event_type = %s';
            $values[] = $args['event_type'];
        }
        if (!empty($args['status'])) {
            $where[]  = 'status = %s';
            $values[] = $args['status'];
        }

        $sql = "SELECT * FROM `$table` WHERE " . implode(' AND ', $where) . ' ORDER BY sent_at DESC LIMIT %d OFFSET %d';

        $values[] = $limit;
        $values[] = $offset;

        return $wpdb->get_results($wpdb->prepare($sql, $values), ARRAY_A);
    }

    /**
     * Anzahl aller Log-Einträge (optional gefiltert).
     *
     * @param array $args  Dieselben Filter wie get_log().
     * @return int
     */
    public static function count_log($args = array()) {
        global $wpdb;

        $table  = $wpdb->prefix . self::LOG_TABLE;
        $where  = array('1=1');
        $values = array();

        if (!empty($args['event_type'])) {
            $where[]  = 'event_type = %s';
            $values[] = $args['event_type'];
        }
        if (!empty($args['status'])) {
            $where[]  = 'status = %s';
            $values[] = $args['status'];
        }

        $sql = "SELECT COUNT(*) FROM `$table` WHERE " . implode(' AND ', $where);

        return (int) ($values ? $wpdb->get_var($wpdb->prepare($sql, $values)) : $wpdb->get_var($sql));
    }

    /**
     * Ältere Log-Einträge löschen.
     *
     * @param int $days  Einträge älter als $days Tage werden gelöscht (default 90).
     * @return int        Anzahl gelöschter Zeilen.
     */
    public static function purge_log($days = 90) {
        global $wpdb;

        $table = $wpdb->prefix . self::LOG_TABLE;

        return (int) $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM `$table` WHERE sent_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                max(1, intval($days))
            )
        );
    }

    // ------------------------------------------------------------------
    // Hilfsmethoden
    // ------------------------------------------------------------------

    /**
     * Einheitliches Plaintext-Template.
     *
     * @param string   $greeting  Anrede-Zeile.
     * @param string[] $lines     Absätze.
     * @return string
     */
    private static function wrap_template($greeting, array $lines) {
        $site_name = get_bloginfo('name');
        $separator = str_repeat('-', 60);

        $body  = $greeting . "\n\n";
        $body .= implode("\n\n", array_map('wp_strip_all_tags', $lines));
        $body .= "\n\n" . $separator . "\n";
        $body .= $site_name . "\n";
        $body .= home_url() . "\n";

        return $body;
    }

    /**
     * Lesbares Status-Label für Ticket-Status.
     *
     * @param string $status
     * @return string
     */
    private static function status_label($status) {
        $labels = array(
            'open'                 => __('Offen', 'themisdb-support-portal'),
            'in_progress'          => __('In Bearbeitung', 'themisdb-support-portal'),
            'waiting_for_customer' => __('Wartet auf Rückmeldung', 'themisdb-support-portal'),
            'resolved'             => __('Gelöst', 'themisdb-support-portal'),
            'closed'               => __('Geschlossen', 'themisdb-support-portal'),
        );

        return isset($labels[$status]) ? $labels[$status] : ucfirst($status);
    }

    /**
     * Order-Daten auflösen: Akzeptiert sowohl ID als auch Array.
     * Versucht bei einer ID ThemisDB_Order_Manager zu nutzen (falls verfügbar).
     *
     * @param int|array $order_or_id
     * @return array|null
     */
    private static function resolve_order($order_or_id) {
        if (is_array($order_or_id)) {
            return $order_or_id;
        }

        $id = intval($order_or_id);
        if ($id <= 0) {
            return null;
        }

        if (class_exists('ThemisDB_Order_Manager') && method_exists('ThemisDB_Order_Manager', 'get_order')) {
            $order = ThemisDB_Order_Manager::get_order($id);
            return is_array($order) ? $order : null;
        }

        return array('id' => $id); // Minimale Fallback-Daten.
    }

    /**
     * Lizenz-Daten auflösen: Akzeptiert sowohl ID als auch Array.
     *
     * @param int|array $license_or_id
     * @return array|null
     */
    private static function resolve_license($license_or_id) {
        if (is_array($license_or_id)) {
            return $license_or_id;
        }

        $id = intval($license_or_id);
        if ($id <= 0) {
            return null;
        }

        if (class_exists('ThemisDB_License_Manager') && method_exists('ThemisDB_License_Manager', 'get_license')) {
            $license = ThemisDB_License_Manager::get_license($id);
            return is_array($license) ? $license : null;
        }

        return array('id' => $id);
    }

    // ------------------------------------------------------------------
    // Event-Typ-Label (für Admin-Anzeige)
    // ------------------------------------------------------------------

    /**
     * Lesbares Label für einen Event-Typ.
     *
     * @param string $event_type
     * @return string
     */
    public static function event_label($event_type) {
        $labels = array(
            self::EVENT_TICKET_CREATED        => __('Ticket erstellt', 'themisdb-support-portal'),
            self::EVENT_TICKET_STATUS_CHANGED => __('Status geändert', 'themisdb-support-portal'),
            self::EVENT_ORDER_CREATED         => __('Bestellung eingegangen', 'themisdb-support-portal'),
            self::EVENT_ORDER_APPROVED        => __('Bestellung genehmigt', 'themisdb-support-portal'),
            self::EVENT_ORDER_REJECTED        => __('Bestellung abgelehnt', 'themisdb-support-portal'),
            self::EVENT_LICENSE_ACTIVATED     => __('Lizenz aktiviert', 'themisdb-support-portal'),
            self::EVENT_BUILD_COMPLETED       => __('Build abgeschlossen', 'themisdb-support-portal'),
            self::EVENT_BUILD_FAILED          => __('Build fehlgeschlagen', 'themisdb-support-portal'),
        );

        return isset($labels[$event_type]) ? $labels[$event_type] : esc_html($event_type);
    }
}
