<?php
/**
 * ThemisDB Support Portal – Unified Status Resolver (ARCHITECTUR.md §8.5)
 *
 * Aggregiert den Kundenstatus domänenübergreifend:
 *   - Support-Tickets (eigenes Plugin)
 *   - Lizenz (themisdb-order-request, sofern geladen)
 *   - Bestellungen (themisdb-order-request, sofern geladen)
 *   - Build-Jobs (themisdb-order-request, sofern geladen)
 *   - Vertragliche Lifecycle-Anträge (themisdb-order-request, sofern geladen)
 *
 * Wird vom [themisdb_cockpit]-Shortcode genutzt, kann aber auch programmatisch
 * aufgerufen werden:
 *
 *   $summary = ThemisDB_Status_Resolver::for_user($user_id);
 *   $summary = ThemisDB_Status_Resolver::for_context($context);
 *
 * Gibt immer ein vollständiges Array zurück, auch wenn Teilbereiche nicht
 * verfügbar sind (graceful degradation).
 */

if (!defined('ABSPATH')) {
    exit;
}

class ThemisDB_Status_Resolver {

    // ------------------------------------------------------------------
    // Öffentliche API
    // ------------------------------------------------------------------

    /**
     * Gibt den vollständigen aggregierten Status eines Kunden zurück.
     *
     * @param int $user_id  WordPress-User-ID.
     * @return array {
     *     @type array       $license   Lizenzdaten oder leeres Array.
     *     @type array       $tickets   Letzte offene Tickets (max. 5).
     *     @type array       $orders    Letzte Bestellungen (max. 3).
     *     @type array       $builds    Letzte Build-Ergebnisse (max. 3).
     *     @type array       $lifecycle Offene Lifecycle-Anträge.
     *     @type array       $health    Aggregiertes Health-Signal: ['level'=>'ok|warn|crit','reasons'=>[]].
     * }
     */
    public static function for_user($user_id) {
        $user_id  = intval($user_id);
        $license  = self::resolve_license($user_id);
        $tickets  = self::resolve_tickets($user_id, $license);
        $orders   = self::resolve_orders($user_id, $license);
        $builds   = self::resolve_builds($user_id, $license);
        $lifecycle = self::resolve_lifecycle($user_id, $license);
        $health   = self::compute_health($license, $tickets, $builds);

        return compact('license', 'tickets', 'orders', 'builds', 'lifecycle', 'health');
    }

    /**
     * Aggregated status for a normalized customer context.
     *
     * @param array $context
     * @return array
     */
    public static function for_context(array $context) {
        $customer_account_id = isset($context['customer_account_id']) ? (int) $context['customer_account_id'] : 0;
        $user_id = isset($context['user_id']) ? (int) $context['user_id'] : 0;

        if ($customer_account_id > 0) {
            $license = self::resolve_license_for_customer_account($context);
            $tickets = self::resolve_tickets_for_customer_account($customer_account_id, $license);
            $orders = self::resolve_orders_for_customer_account($context, $license);
            $builds = self::resolve_builds_for_customer_account($context, $license);
            $lifecycle = self::resolve_lifecycle_for_customer_account($context, $license);
            $health = self::compute_health($license, $tickets, $builds);

            return compact('license', 'tickets', 'orders', 'builds', 'lifecycle', 'health');
        }

        return self::for_user($user_id);
    }

    // ------------------------------------------------------------------
    // Domain-Resolver
    // ------------------------------------------------------------------

    /**
     * Lizenzstatus für den Benutzer ermitteln.
     *
     * @param int $user_id
     * @return array {
     *     @type int|null    $id
     *     @type string      $license_key   Maskiert (erste 16 Zeichen + …).
     *     @type string      $tier          z. B. 'enterprise'.
     *     @type string      $status        z. B. 'active'.
     *     @type string|null $expires_at    ISO-Datumsstring oder null.
     *     @type bool        $available     True wenn Lizenz gefunden.
     * }
     */
    public static function resolve_license($user_id) {
        $base = array(
            'id'          => null,
            'license_key' => '',
            'tier'        => '',
            'status'      => '',
            'expires_at'  => null,
            'available'   => false,
        );

        // Primär: über themisdb-order-request.
        if (class_exists('ThemisDB_License_Manager')) {
            $license_id = (int) get_user_meta($user_id, 'themisdb_license_id', true);
            if ($license_id > 0) {
                $lic = ThemisDB_License_Manager::get_license($license_id);
                if (is_array($lic)) {
                    return array(
                        'id'          => $license_id,
                        'license_key' => self::mask_key(isset($lic['license_key']) ? (string) $lic['license_key'] : ''),
                        'tier'        => isset($lic['product_edition']) ? (string) $lic['product_edition'] : (isset($lic['tier']) ? (string) $lic['tier'] : ''),
                        'status'      => isset($lic['license_status']) ? (string) $lic['license_status'] : (isset($lic['status']) ? (string) $lic['status'] : ''),
                        'expires_at'  => isset($lic['expiry_date']) ? (string) $lic['expiry_date'] : (isset($lic['expires_at']) ? (string) $lic['expires_at'] : null),
                        'available'   => true,
                    );
                }
            }
        }

        // Fallback: Lizenzschlüssel aus User-Meta (eigenes Session-System).
        $raw_key = get_user_meta($user_id, 'themisdb_support_license_key', true);
        if ($raw_key) {
            $parts    = explode('-', (string) $raw_key);
            $tier_map = array('COM' => 'community', 'ENT' => 'enterprise', 'HYP' => 'hyperscaler', 'RES' => 'reseller');
            $tier     = isset($parts[1], $tier_map[$parts[1]]) ? $tier_map[$parts[1]] : 'standard';

            return array(
                'id'          => null,
                'license_key' => self::mask_key($raw_key),
                'tier'        => $tier,
                'status'      => 'active',
                'expires_at'  => null,
                'available'   => true,
            );
        }

        return $base;
    }

    /**
     * Resolve license for a customer account.
     *
     * @param array $context
     * @return array
     */
    public static function resolve_license_for_customer_account(array $context) {
        $account_id = isset($context['customer_account_id']) ? (int) $context['customer_account_id'] : 0;
        if ($account_id <= 0 || !class_exists('ThemisDB_Support_Customer_Account_Repository')) {
            return self::resolve_license(isset($context['user_id']) ? (int) $context['user_id'] : 0);
        }

        $account = ThemisDB_Support_Customer_Account_Repository::find_by_id($account_id);
        if (!$account) {
            return self::resolve_license(isset($context['user_id']) ? (int) $context['user_id'] : 0);
        }

        $license_id = !empty($account['primary_license_id']) ? (int) $account['primary_license_id'] : 0;
        if ($license_id > 0 && class_exists('ThemisDB_License_Manager')) {
            $lic = ThemisDB_License_Manager::get_license($license_id);
            if (is_array($lic)) {
                return array(
                    'id'          => $license_id,
                    'license_key' => self::mask_key(isset($lic['license_key']) ? (string) $lic['license_key'] : ''),
                    'tier'        => isset($lic['product_edition']) ? (string) $lic['product_edition'] : (isset($lic['tier']) ? (string) $lic['tier'] : ''),
                    'status'      => isset($lic['license_status']) ? (string) $lic['license_status'] : (isset($lic['status']) ? (string) $lic['status'] : ''),
                    'expires_at'  => isset($lic['expiry_date']) ? (string) $lic['expiry_date'] : (isset($lic['expires_at']) ? (string) $lic['expires_at'] : null),
                    'available'   => true,
                );
            }
        }

        return array(
            'id'          => $license_id > 0 ? $license_id : null,
            'license_key' => self::mask_key(isset($account['customer_email']) ? (string) $account['customer_email'] : ''),
            'tier'        => isset($account['support_tier']) ? (string) $account['support_tier'] : '',
            'status'      => isset($account['account_status']) ? (string) $account['account_status'] : 'active',
            'expires_at'  => null,
            'available'   => true,
        );
    }

    /**
     * Offene + kürzlich abgeschlossene Tickets des Benutzers (max. 5).
     *
     * @param int   $user_id
     * @param array $license
     * @return array
     */
    public static function resolve_tickets($user_id, array $license) {
        if (!class_exists('ThemisDB_SupportPortal_Ticket_Manager') && !class_exists('ThemisDB_Ticket_Manager')) {
            return array();
        }

        $manager = class_exists('ThemisDB_SupportPortal_Ticket_Manager')
            ? 'ThemisDB_SupportPortal_Ticket_Manager'
            : 'ThemisDB_Ticket_Manager';

        if (!method_exists($manager, 'get_tickets')) {
            return array();
        }

        $result = $manager::get_tickets(array(
            'user_id' => $user_id,
            'limit'   => 5,
            'orderby' => 'updated_at',
            'order'   => 'DESC',
        ));

        return isset($result['tickets']) ? (array) $result['tickets'] : array();
    }

    public static function resolve_tickets_for_customer_account($customer_account_id, array $license) {
        if (!class_exists('ThemisDB_SupportPortal_Ticket_Manager') && !class_exists('ThemisDB_Ticket_Manager')) {
            return array();
        }

        $manager = class_exists('ThemisDB_SupportPortal_Ticket_Manager')
            ? 'ThemisDB_SupportPortal_Ticket_Manager'
            : 'ThemisDB_Ticket_Manager';

        if (!method_exists($manager, 'get_tickets')) {
            return array();
        }

        $result = $manager::get_tickets(array(
            'customer_account_id' => (int) $customer_account_id,
            'limit'   => 5,
            'orderby' => 'updated_at',
            'order'   => 'DESC',
        ));

        return isset($result['tickets']) ? (array) $result['tickets'] : array();
    }

    /**
     * Bestellungen des Kunden (max. 3) – nur verfügbar wenn Order-Plugin geladen.
     *
     * @param int   $user_id
     * @param array $license
     * @return array
     */
    public static function resolve_orders($user_id, array $license) {
        if (!class_exists('ThemisDB_Order_Manager') || !method_exists('ThemisDB_Order_Manager', 'get_orders')) {
            return array();
        }

        $user    = get_userdata($user_id);
        $email   = $user ? $user->user_email : '';

        if (empty($email)) {
            return array();
        }

        $result = ThemisDB_Order_Manager::get_orders(array(
            'customer_email' => $email,
            'limit'          => 3,
            'orderby'        => 'created_at',
            'order'          => 'DESC',
        ));

        return isset($result['orders']) ? (array) $result['orders'] : (is_array($result) ? $result : array());
    }

    public static function resolve_orders_for_customer_account(array $context, array $license) {
        if (!class_exists('ThemisDB_Order_Manager') || !method_exists('ThemisDB_Order_Manager', 'get_orders')) {
            return array();
        }

        $email = isset($context['customer_email']) ? sanitize_email((string) $context['customer_email']) : '';
        if (empty($email) && !empty($context['user_id'])) {
            $user = get_userdata((int) $context['user_id']);
            $email = $user ? (string) $user->user_email : '';
        }

        if (empty($email)) {
            return array();
        }

        $result = ThemisDB_Order_Manager::get_orders(array(
            'customer_email' => $email,
            'limit'          => 3,
            'orderby'        => 'created_at',
            'order'          => 'DESC',
        ));

        return isset($result['orders']) ? (array) $result['orders'] : (is_array($result) ? $result : array());
    }

    /**
     * Letzte Build-Dispatch-Ergebnisse für die Lizenz des Kunden (max. 3).
     *
     * @param int   $user_id
     * @param array $license
     * @return array
     */
    public static function resolve_builds($user_id, array $license) {
        if (empty($license['id']) || !class_exists('ThemisDB_License_Build_Dispatcher')) {
            return array();
        }

        if (!method_exists('ThemisDB_License_Build_Dispatcher', 'get_dispatch_history')) {
            return array();
        }

        $history = ThemisDB_License_Build_Dispatcher::get_dispatch_history($license['id'], 3);
        return is_array($history) ? $history : array();
    }

    public static function resolve_builds_for_customer_account(array $context, array $license) {
        return self::resolve_builds(isset($context['user_id']) ? (int) $context['user_id'] : 0, $license);
    }

    /**
     * Offene Vertragliche Lifecycle-Anträge (Kündigung / Änderung).
     *
     * @param int   $user_id
     * @param array $license
     * @return array
     */
    public static function resolve_lifecycle($user_id, array $license) {
        if (!class_exists('ThemisDB_Contract_Lifecycle') || !method_exists('ThemisDB_Contract_Lifecycle', 'get_requests_for_user')) {
            return array();
        }

        $reqs = ThemisDB_Contract_Lifecycle::get_requests_for_user($user_id, array('status' => 'pending'));
        return is_array($reqs) ? $reqs : array();
    }

    public static function resolve_lifecycle_for_customer_account(array $context, array $license) {
        return self::resolve_lifecycle(isset($context['user_id']) ? (int) $context['user_id'] : 0, $license);
    }

    /**
     * Berechnet ein aggregiertes Health-Signal aus allen Teilbereichen.
     *
     * @param array $license
     * @param array $tickets
     * @param array $builds
     * @return array { level: 'ok'|'warn'|'crit', reasons: string[] }
     */
    public static function compute_health(array $license, array $tickets, array $builds) {
        $level   = 'ok';
        $reasons = array();

        // Lizenz-Check.
        if (!$license['available']) {
            $level     = 'warn';
            $reasons[] = __('Keine aktive Lizenz gefunden.', 'themisdb-support-portal');
        } elseif (in_array($license['status'], array('expired', 'revoked', 'suspended'), true)) {
            $level     = 'crit';
            $reasons[] = sprintf(__('Lizenz hat Status: %s.', 'themisdb-support-portal'), esc_html($license['status']));
        } elseif ($license['expires_at']) {
            $days_left = (int) floor((strtotime($license['expires_at']) - time()) / DAY_IN_SECONDS);
            if ($days_left < 0) {
                $level     = 'crit';
                $reasons[] = __('Lizenz ist abgelaufen.', 'themisdb-support-portal');
            } elseif ($days_left <= 30) {
                if ($level === 'ok') { $level = 'warn'; }
                $reasons[] = sprintf(
                    /* translators: %d: Anzahl Tage */
                    __('Lizenz läuft in %d Tagen ab.', 'themisdb-support-portal'),
                    $days_left
                );
            }
        }

        // Ticket-Check: kritische offene Tickets.
        $crit_tickets = array_filter($tickets, static function ($t) {
            return in_array(isset($t['priority']) ? $t['priority'] : '', array('urgent', 'high'), true)
                && in_array(isset($t['status']) ? $t['status'] : '', array('open', 'in_progress'), true);
        });
        if (count($crit_tickets) > 0) {
            if ($level === 'ok') { $level = 'warn'; }
            $reasons[] = sprintf(
                /* translators: %d: Anzahl kritischer Tickets */
                __('%d offene Ticket(s) mit hoher Priorität.', 'themisdb-support-portal'),
                count($crit_tickets)
            );
        }

        // Build-Check: letzte Build fehlgeschlagen.
        if (!empty($builds)) {
            $last_build = reset($builds);
            $last_status = isset($last_build['status']) ? $last_build['status'] : '';
            if (in_array($last_status, array('failed', 'error'), true)) {
                if ($level === 'ok') { $level = 'warn'; }
                $reasons[] = __('Letzter Build ist fehlgeschlagen.', 'themisdb-support-portal');
            }
        }

        return array('level' => $level, 'reasons' => $reasons);
    }

    // ------------------------------------------------------------------
    // Hilfsmethoden
    // ------------------------------------------------------------------

    /**
     * Maskiert einen Lizenzschlüssel (erste 16 Zeichen + …).
     *
     * @param string $key
     * @return string
     */
    private static function mask_key($key) {
        if (strlen($key) <= 16) {
            return $key;
        }
        return substr($key, 0, 16) . '…';
    }

    // ------------------------------------------------------------------
    // Label-Helfer (wiederverwendbar in Views)
    // ------------------------------------------------------------------

    /**
     * CSS-Farbe zum Health-Level.
     *
     * @param string $level  'ok'|'warn'|'crit'
     * @return string        Hex-Farbe
     */
    public static function health_color($level) {
        $map = array('ok' => '#27ae60', 'warn' => '#f39c12', 'crit' => '#e74c3c');
        return isset($map[$level]) ? $map[$level] : '#999';
    }

    /**
     * Dashicon-Klasse zum Health-Level.
     *
     * @param string $level
     * @return string
     */
    public static function health_icon($level) {
        $map = array('ok' => 'dashicons-yes-alt', 'warn' => 'dashicons-warning', 'crit' => 'dashicons-dismiss');
        return isset($map[$level]) ? $map[$level] : 'dashicons-info';
    }

    /**
     * Lesbares Label für einen Ticket-Status.
     *
     * @param string $status
     * @return string
     */
    public static function ticket_status_label($status) {
        $labels = array(
            'open'                 => __('Offen', 'themisdb-support-portal'),
            'in_progress'          => __('In Bearbeitung', 'themisdb-support-portal'),
            'waiting_for_customer' => __('Wartet auf Antwort', 'themisdb-support-portal'),
            'resolved'             => __('Gelöst', 'themisdb-support-portal'),
            'closed'               => __('Geschlossen', 'themisdb-support-portal'),
        );
        return isset($labels[$status]) ? $labels[$status] : ucfirst(str_replace('_', ' ', $status));
    }

    /**
     * CSS-Farbe für einen Ticket-Status.
     *
     * @param string $status
     * @return string
     */
    public static function ticket_status_color($status) {
        $map = array(
            'open'                 => '#3498db',
            'in_progress'          => '#f39c12',
            'waiting_for_customer' => '#9b59b6',
            'resolved'             => '#27ae60',
            'closed'               => '#95a5a6',
        );
        return isset($map[$status]) ? $map[$status] : '#666';
    }

    /**
     * Lesbares Label für einen Lizenzstatus.
     *
     * @param string $status
     * @return string
     */
    public static function license_status_label($status) {
        $labels = array(
            'active'    => __('Aktiv', 'themisdb-support-portal'),
            'expired'   => __('Abgelaufen', 'themisdb-support-portal'),
            'revoked'   => __('Widerrufen', 'themisdb-support-portal'),
            'suspended' => __('Gesperrt', 'themisdb-support-portal'),
            'pending'   => __('Ausstehend', 'themisdb-support-portal'),
        );
        return isset($labels[$status]) ? $labels[$status] : ucfirst($status);
    }

    /**
     * Lesbares Label für einen Build-Status.
     *
     * @param string $status
     * @return string
     */
    public static function build_status_label($status) {
        $labels = array(
            'success'    => __('Erfolgreich', 'themisdb-support-portal'),
            'completed'  => __('Abgeschlossen', 'themisdb-support-portal'),
            'failed'     => __('Fehlgeschlagen', 'themisdb-support-portal'),
            'error'      => __('Fehler', 'themisdb-support-portal'),
            'running'    => __('Läuft', 'themisdb-support-portal'),
            'pending'    => __('Ausstehend', 'themisdb-support-portal'),
            'dispatched' => __('Gestartet', 'themisdb-support-portal'),
        );
        return isset($labels[$status]) ? $labels[$status] : ucfirst($status);
    }

    /**
     * CSS-Farbe für einen Build-Status.
     *
     * @param string $status
     * @return string
     */
    public static function build_status_color($status) {
        $map = array(
            'success'    => '#27ae60',
            'completed'  => '#27ae60',
            'failed'     => '#e74c3c',
            'error'      => '#e74c3c',
            'running'    => '#3498db',
            'pending'    => '#f39c12',
            'dispatched' => '#3498db',
        );
        return isset($map[$status]) ? $map[$status] : '#999';
    }
}
