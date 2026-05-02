<?php
/**
 * ThemisDB Support Portal – Contract Change Engine (ARCHITECTUR.md §8.6)
 *
 * Ergänzt die bestehende ThemisDB_Contract_Lifecycle-Klasse um:
 *   - Automatische Impact-Analyse bei jedem Änderungsantrag
 *   - Speicherung des Impact-Reports im Payload des Antrags
 *   - Hilfsmethoden für die Admin-Review-Ansicht
 *
 * Die eigentliche Genehmigungs-/Ablehnungslogik verbleibt in
 * ThemisDB_Contract_Lifecycle::review_request() (themisdb-order-request).
 * Diese Klasse erweitert nur um die Analyse-Schicht.
 *
 * Events, auf die gehorcht wird:
 *   contract.change.requested → Impact berechnen + in Payload speichern
 *
 * Tier-Hierarchie (aufsteigend):
 *   community < enterprise < hyperscaler < reseller
 */

if (!defined('ABSPATH')) {
    exit;
}

class ThemisDB_Contract_Change_Engine {

    // ------------------------------------------------------------------
    // Tier-Definitionen
    // ------------------------------------------------------------------

    /**
     * Numerischer Rang je Tier (höher = leistungsfähiger / teurer).
     */
    const TIER_RANK = array(
        'community'   => 1,
        'basic'       => 1,
        'standard'    => 2,
        'professional'=> 3,
        'enterprise'  => 4,
        'hyperscaler' => 5,
        'reseller'    => 5,
    );

    /**
     * SLA-Reaktionszeit (h) pro Tier.
     */
    const TIER_SLA_HOURS = array(
        'community'    => 72,
        'basic'        => 48,
        'standard'     => 24,
        'professional' => 8,
        'enterprise'   => 4,
        'hyperscaler'  => 2,
        'reseller'     => 4,
    );

    // ------------------------------------------------------------------
    // Init
    // ------------------------------------------------------------------

    /**
     * WordPress-Hooks registrieren. Aus Plugin-Bootstrap aufrufen.
     */
    public static function init() {
        // Bei neuen Änderungsanträgen automatisch Impact berechnen.
        add_action('contract.change.requested', array(__CLASS__, 'on_change_requested'), 10, 3);

        // Mail-Orchestrator: Bei Genehmigung / Ablehnung Customer-Mail senden.
        add_action('contract.change.approved', array(__CLASS__, 'on_change_approved'), 10, 2);
        add_action('contract.change.rejected', array(__CLASS__, 'on_change_rejected'), 10, 2);
        add_action('contract.change.executed', array(__CLASS__, 'on_change_executed'), 10, 2);
    }

    // ------------------------------------------------------------------
    // Event-Handler
    // ------------------------------------------------------------------

    /**
     * Automatisch Impact berechnen wenn ein Änderungsantrag eingereicht wird.
     *
     * @param int   $request_id
     * @param int   $license_id
     * @param array $change_payload
     */
    public static function on_change_requested($request_id, $license_id, $change_payload) {
        $impact = self::calculate_impact($license_id, $change_payload);
        if ($impact) {
            self::store_impact($request_id, $impact);
        }
    }

    /**
     * Nach Genehmigung: Kundenmail via Mail-Orchestrator.
     *
     * @param int $request_id
     * @param int $license_id
     */
    public static function on_change_approved($request_id, $license_id) {
        if (!class_exists('ThemisDB_Mail_Orchestrator') || !class_exists('ThemisDB_Contract_Lifecycle')) {
            return;
        }

        $request = ThemisDB_Contract_Lifecycle::get_request($request_id);
        if (!$request) {
            return;
        }

        $email = self::get_customer_email($license_id);
        if (!$email) {
            return;
        }

        $impact  = self::get_impact($request_id);
        $summary = $impact ? self::format_impact_plaintext($impact) : '';

        $lines = array(
            sprintf(
                __('Ihr Vertragsänderungsantrag (ID: %d) wurde genehmigt.', 'themisdb-support-portal'),
                $request_id
            ),
            __('Die Änderungen werden zeitnah angewendet und treten nach Ausführung sofort in Kraft.', 'themisdb-support-portal'),
        );

        if ($summary) {
            $lines[] = __('Zusammenfassung der Änderungen:', 'themisdb-support-portal');
            $lines[] = $summary;
        }

        ThemisDB_Mail_Orchestrator::send(
            $email,
            __('[ThemisDB] Ihr Änderungsantrag wurde genehmigt', 'themisdb-support-portal'),
            self::build_mail_body(__('Guten Tag,', 'themisdb-support-portal'), $lines),
            'contract_change_approved',
            array('request_id' => $request_id, 'license_id' => $license_id)
        );
    }

    /**
     * Nach Ablehnung: Kundenmail.
     *
     * @param int $request_id
     * @param int $license_id
     */
    public static function on_change_rejected($request_id, $license_id) {
        if (!class_exists('ThemisDB_Mail_Orchestrator') || !class_exists('ThemisDB_Contract_Lifecycle')) {
            return;
        }

        $request = ThemisDB_Contract_Lifecycle::get_request($request_id);
        if (!$request) {
            return;
        }

        $email = self::get_customer_email($license_id);
        if (!$email) {
            return;
        }

        $review_note = isset($request['review_note']) ? trim((string) $request['review_note']) : '';
        $lines = array(
            sprintf(
                __('Ihr Vertragsänderungsantrag (ID: %d) wurde leider abgelehnt.', 'themisdb-support-portal'),
                $request_id
            ),
        );

        if ($review_note) {
            $lines[] = __('Begründung:', 'themisdb-support-portal') . ' ' . $review_note;
        }

        $lines[] = __('Bei Fragen wenden Sie sich bitte an unseren Support.', 'themisdb-support-portal');

        ThemisDB_Mail_Orchestrator::send(
            $email,
            __('[ThemisDB] Ihr Änderungsantrag wurde abgelehnt', 'themisdb-support-portal'),
            self::build_mail_body(__('Guten Tag,', 'themisdb-support-portal'), $lines),
            'contract_change_rejected',
            array('request_id' => $request_id, 'license_id' => $license_id)
        );
    }

    /**
     * Nach Ausführung: Bestätigungsmail an Kunden.
     *
     * @param int $request_id
     * @param int $license_id
     */
    public static function on_change_executed($request_id, $license_id) {
        if (!class_exists('ThemisDB_Mail_Orchestrator')) {
            return;
        }

        $email = self::get_customer_email($license_id);
        if (!$email) {
            return;
        }

        ThemisDB_Mail_Orchestrator::send(
            $email,
            __('[ThemisDB] Ihre Vertragsänderung wurde angewendet', 'themisdb-support-portal'),
            self::build_mail_body(
                __('Guten Tag,', 'themisdb-support-portal'),
                array(
                    sprintf(
                        __('Ihre Vertragsänderung (ID: %d) wurde erfolgreich angewendet.', 'themisdb-support-portal'),
                        $request_id
                    ),
                    __('Alle Änderungen sind sofort aktiv. Ihr aktualisiertes Lizenz-Cockpit finden Sie in Ihrem Kundenportal.', 'themisdb-support-portal'),
                )
            ),
            'contract_change_executed',
            array('request_id' => $request_id, 'license_id' => $license_id)
        );
    }

    // ------------------------------------------------------------------
    // Impact-Analyse
    // ------------------------------------------------------------------

    /**
     * Impact der beantragten Änderungen berechnen.
     *
     * Vergleicht den aktuellen Lizenzzustand mit dem change_payload und
     * erstellt eine strukturierte Impact-Analyse.
     *
     * @param int   $license_id
     * @param array $change_payload  Schlüssel-Wert-Paare der Änderungen.
     * @return array|null  Strukturiertes Impact-Array oder null wenn kein Zugriff.
     */
    public static function calculate_impact($license_id, $change_payload) {
        if (!class_exists('ThemisDB_License_Manager')) {
            return null;
        }

        $license = ThemisDB_License_Manager::get_license(intval($license_id));
        if (!is_array($license)) {
            return null;
        }

        $change_payload = (array) $change_payload;
        $changes        = array();
        $risk_level     = 'low';  // 'low' | 'medium' | 'high'
        $requires_ops   = false;
        $requires_finance = false;

        // --- Edition / Tier-Wechsel ---
        $old_edition = isset($license['product_edition']) ? (string) $license['product_edition'] : '';
        $new_edition = isset($change_payload['product_edition']) ? (string) $change_payload['product_edition'] : '';

        if ($new_edition !== '' && $new_edition !== $old_edition) {
            $old_rank = self::tier_rank($old_edition);
            $new_rank = self::tier_rank($new_edition);
            $direction = $new_rank > $old_rank ? 'upgrade' : ($new_rank < $old_rank ? 'downgrade' : 'lateral');

            $changes['product_edition'] = array(
                'field'     => 'product_edition',
                'label'     => __('Edition', 'themisdb-support-portal'),
                'old'       => $old_edition,
                'new'       => $new_edition,
                'direction' => $direction,
            );

            $old_sla = self::tier_sla($old_edition);
            $new_sla = self::tier_sla($new_edition);
            if ($old_sla !== $new_sla) {
                $changes['product_edition']['sla_old'] = $old_sla;
                $changes['product_edition']['sla_new'] = $new_sla;
            }

            if ($direction === 'downgrade') {
                $risk_level       = 'high';
                $requires_ops     = true;
                $requires_finance = true;
            } elseif ($direction === 'upgrade') {
                $risk_level     = 'medium';
                $requires_finance = true;
            }
        }

        // --- Kapazitätsänderungen ---
        $capacity_fields = array(
            'max_nodes'      => __('Max. Knoten', 'themisdb-support-portal'),
            'max_cores'      => __('Max. Kerne', 'themisdb-support-portal'),
            'max_storage_gb' => __('Max. Speicher (GB)', 'themisdb-support-portal'),
        );

        foreach ($capacity_fields as $field => $label) {
            if (!array_key_exists($field, $change_payload)) {
                continue;
            }

            $old_val = isset($license[$field]) ? intval($license[$field]) : 0;
            $new_val = intval($change_payload[$field]);

            if ($old_val === $new_val) {
                continue;
            }

            $direction = $new_val > $old_val ? 'increase' : 'decrease';
            $changes[$field] = array(
                'field'     => $field,
                'label'     => $label,
                'old'       => $old_val,
                'new'       => $new_val,
                'direction' => $direction,
            );

            if ($direction === 'decrease') {
                if ($risk_level === 'low') {
                    $risk_level = 'medium';
                }
                $requires_ops = true;
            } elseif ($direction === 'increase') {
                $requires_finance = true;
            }
        }

        // --- Ablaufdatum ---
        if (array_key_exists('expiry_date', $change_payload)) {
            $old_exp = isset($license['expiry_date']) ? (string) $license['expiry_date'] : '';
            $new_exp = sanitize_text_field((string) $change_payload['expiry_date']);

            if ($old_exp !== $new_exp) {
                $old_ts   = $old_exp ? strtotime($old_exp) : 0;
                $new_ts   = $new_exp ? strtotime($new_exp) : 0;
                $direction = $new_ts > $old_ts ? 'extension' : 'shortening';

                $changes['expiry_date'] = array(
                    'field'     => 'expiry_date',
                    'label'     => __('Ablaufdatum', 'themisdb-support-portal'),
                    'old'       => $old_exp ? gmdate('d.m.Y', $old_ts) : __('unbegrenzt', 'themisdb-support-portal'),
                    'new'       => $new_exp ? gmdate('d.m.Y', $new_ts) : __('unbegrenzt', 'themisdb-support-portal'),
                    'direction' => $direction,
                );

                if ($direction === 'shortening') {
                    if ($risk_level === 'low') {
                        $risk_level = 'medium';
                    }
                    $requires_ops = true;
                } else {
                    $requires_finance = true;
                }
            }
        }

        // --- Lizenztyp ---
        if (array_key_exists('license_type', $change_payload)) {
            $old_lt = isset($license['license_type']) ? (string) $license['license_type'] : '';
            $new_lt = sanitize_text_field((string) $change_payload['license_type']);
            if ($old_lt !== $new_lt) {
                $changes['license_type'] = array(
                    'field' => 'license_type',
                    'label' => __('Lizenztyp', 'themisdb-support-portal'),
                    'old'   => $old_lt,
                    'new'   => $new_lt,
                );
                $risk_level   = 'high';
                $requires_ops = true;
            }
        }

        // --- Preisabschätzung ---
        $price_delta = null;
        if (class_exists('ThemisDB_License_Pricing') && method_exists('ThemisDB_License_Pricing', 'get_current_price')) {
            $current_price = ThemisDB_License_Pricing::get_current_price(intval($license_id));
            if (is_array($current_price) && isset($current_price['base_price'])) {
                $price_delta = self::estimate_price_delta($current_price, $changes);
            }
        }

        return array(
            'license_id'       => intval($license_id),
            'change_count'     => count($changes),
            'changes'          => $changes,
            'risk_level'       => $risk_level,
            'requires_ops'     => $requires_ops,
            'requires_finance' => $requires_finance,
            'price_delta'      => $price_delta,
            'analyzed_at'      => current_time('mysql'),
        );
    }

    /**
     * Speichert eine Impact-Analyse im payload-JSON des Lifecycle-Antrags.
     * Hängt `impact_analysis`-Schlüssel an das bestehende JSON an.
     *
     * @param int   $request_id
     * @param array $impact
     * @return bool
     */
    public static function store_impact($request_id, array $impact) {
        if (!class_exists('ThemisDB_Contract_Lifecycle')) {
            return false;
        }

        global $wpdb;

        $request = ThemisDB_Contract_Lifecycle::get_request(intval($request_id));
        if (!$request) {
            return false;
        }

        $payload = array();
        if (!empty($request['payload'])) {
            $decoded = json_decode((string) $request['payload'], true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        $payload['impact_analysis'] = $impact;

        $result = $wpdb->update(
            ThemisDB_Contract_Lifecycle::get_table_name(),
            array('payload' => wp_json_encode($payload)),
            array('id' => intval($request_id)),
            array('%s'),
            array('%d')
        );

        if ($result !== false) {
            ThemisDB_Contract_Lifecycle::add_log(
                intval($request_id),
                'impact_analyzed',
                0,
                sprintf(
                    __('Impact-Analyse abgeschlossen: %d Änderung(en), Risiko: %s.', 'themisdb-support-portal'),
                    $impact['change_count'],
                    $impact['risk_level']
                ),
                array('risk_level' => $impact['risk_level'], 'change_count' => $impact['change_count'])
            );
        }

        return $result !== false;
    }

    /**
     * Impact-Analyse aus dem gespeicherten Payload laden.
     *
     * @param int $request_id
     * @return array|null
     */
    public static function get_impact($request_id) {
        if (!class_exists('ThemisDB_Contract_Lifecycle')) {
            return null;
        }

        $request = ThemisDB_Contract_Lifecycle::get_request(intval($request_id));
        if (!$request || empty($request['payload'])) {
            return null;
        }

        $payload = json_decode((string) $request['payload'], true);
        return isset($payload['impact_analysis']) ? $payload['impact_analysis'] : null;
    }

    /**
     * Holt alle offenen Change-Requests mit Impact-Analyse (für Admin-View).
     *
     * @param array $args  Wird an ThemisDB_Contract_Lifecycle::list_requests() weitergegeben.
     * @return array
     */
    public static function list_change_requests($args = array()) {
        if (!class_exists('ThemisDB_Contract_Lifecycle')) {
            return array();
        }

        $defaults = array(
            'request_type' => ThemisDB_Contract_Lifecycle::TYPE_CHANGE,
            'limit'        => 50,
            'orderby'      => 'created_at',
            'order'        => 'DESC',
        );

        $result = ThemisDB_Contract_Lifecycle::list_requests(array_merge($defaults, $args));
        return is_array($result) ? $result : array();
    }

    // ------------------------------------------------------------------
    // Admin-Aktionen
    // ------------------------------------------------------------------

    /**
     * Änderungsantrag genehmigen (delegiert an Contract_Lifecycle + loggt Impact).
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
     * Änderungsantrag ablehnen.
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

    // ------------------------------------------------------------------
    // Darstellungs-Helfer
    // ------------------------------------------------------------------

    /**
     * Plaintext-Zusammenfassung des Impact für Mails.
     *
     * @param array $impact
     * @return string
     */
    public static function format_impact_plaintext(array $impact) {
        if (empty($impact['changes'])) {
            return __('Keine Felder geändert.', 'themisdb-support-portal');
        }

        $lines = array();
        foreach ($impact['changes'] as $change) {
            $lines[] = sprintf(
                '- %s: %s → %s',
                isset($change['label']) ? $change['label'] : $change['field'],
                isset($change['old']) ? $change['old'] : '—',
                isset($change['new']) ? $change['new'] : '—'
            );
        }

        if (!is_null($impact['price_delta']) && $impact['price_delta'] != 0) {
            $sign    = $impact['price_delta'] > 0 ? '+' : '';
            $lines[] = sprintf('- %s: %s%s EUR/Jahr', __('Preisänderung (geschätzt)', 'themisdb-support-portal'), $sign, number_format_i18n($impact['price_delta'], 2));
        }

        return implode("\n", $lines);
    }

    /**
     * CSS-Farbe je Risikostufe.
     *
     * @param string $risk_level  'low'|'medium'|'high'
     * @return string
     */
    public static function risk_color($risk_level) {
        $map = array('low' => '#27ae60', 'medium' => '#f39c12', 'high' => '#e74c3c');
        return isset($map[$risk_level]) ? $map[$risk_level] : '#999';
    }

    /**
     * Lesbares Label je Risikostufe.
     *
     * @param string $risk_level
     * @return string
     */
    public static function risk_label($risk_level) {
        $labels = array(
            'low'    => __('Gering', 'themisdb-support-portal'),
            'medium' => __('Mittel', 'themisdb-support-portal'),
            'high'   => __('Hoch', 'themisdb-support-portal'),
        );
        return isset($labels[$risk_level]) ? $labels[$risk_level] : ucfirst($risk_level);
    }

    /**
     * Lesbares Label je Status (aus ThemisDB_Contract_Lifecycle).
     *
     * @param string $status
     * @return string
     */
    public static function status_label($status) {
        $labels = array(
            'requested'       => __('Ausstehend', 'themisdb-support-portal'),
            'confirmed'       => __('Genehmigt', 'themisdb-support-portal'),
            'rejected'        => __('Abgelehnt', 'themisdb-support-portal'),
            'executed'        => __('Ausgeführt', 'themisdb-support-portal'),
            'pending_finance' => __('Finance-Review', 'themisdb-support-portal'),
            'pending_ops'     => __('Ops-Review', 'themisdb-support-portal'),
        );
        return isset($labels[$status]) ? $labels[$status] : ucfirst(str_replace('_', ' ', $status));
    }

    /**
     * CSS-Farbe je Status.
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

    /**
     * Lesbares Label für die Richtung einer Änderung.
     *
     * @param string $direction  'upgrade'|'downgrade'|'increase'|'decrease'|'extension'|'shortening'|'lateral'
     * @return string
     */
    public static function direction_label($direction) {
        $labels = array(
            'upgrade'    => '↑ ' . __('Upgrade', 'themisdb-support-portal'),
            'downgrade'  => '↓ ' . __('Downgrade', 'themisdb-support-portal'),
            'increase'   => '↑ ' . __('Erhöhung', 'themisdb-support-portal'),
            'decrease'   => '↓ ' . __('Reduzierung', 'themisdb-support-portal'),
            'extension'  => '↑ ' . __('Verlängerung', 'themisdb-support-portal'),
            'shortening' => '↓ ' . __('Verkürzung', 'themisdb-support-portal'),
            'lateral'    => '→ ' . __('Seitenwechsel', 'themisdb-support-portal'),
        );
        return isset($labels[$direction]) ? $labels[$direction] : $direction;
    }

    // ------------------------------------------------------------------
    // Interne Hilfslogik
    // ------------------------------------------------------------------

    /**
     * Numerischer Tier-Rang.
     *
     * @param string $tier
     * @return int
     */
    private static function tier_rank($tier) {
        $tier = strtolower(trim($tier));
        return isset(self::TIER_RANK[$tier]) ? self::TIER_RANK[$tier] : 0;
    }

    /**
     * SLA-Reaktionszeit für einen Tier (in Stunden).
     *
     * @param string $tier
     * @return int|null
     */
    private static function tier_sla($tier) {
        $tier = strtolower(trim($tier));
        return isset(self::TIER_SLA_HOURS[$tier]) ? self::TIER_SLA_HOURS[$tier] : null;
    }

    /**
     * Grobe Preisabschätzung basierend auf aktueller Preiszeile und erkannten Änderungen.
     * Gibt eine Jahresdelta-Schätzung in EUR zurück, oder null wenn nicht berechenbar.
     *
     * @param array $current_price  Zeile aus wp_themisdb_license_prices.
     * @param array $changes        Aus calculate_impact() produzierte Changes.
     * @return float|null
     */
    private static function estimate_price_delta(array $current_price, array $changes) {
        $base = isset($current_price['base_price']) ? floatval($current_price['base_price']) : null;
        if ($base === null || $base <= 0) {
            return null;
        }

        $delta = 0.0;

        // Edition-Upgrade: grobe Schätzung +20% je Tier-Rang-Stufe.
        if (isset($changes['product_edition'])) {
            $old_rank = self::tier_rank($changes['product_edition']['old']);
            $new_rank = self::tier_rank($changes['product_edition']['new']);
            $diff     = $new_rank - $old_rank;
            $delta   += $base * ($diff * 0.20);
        }

        // Kapazitätsänderung: +5% je verdoppeltem Wert (Näherung).
        foreach (array('max_nodes', 'max_cores', 'max_storage_gb') as $f) {
            if (!isset($changes[$f])) {
                continue;
            }
            $old = max(1, intval($changes[$f]['old']));
            $new = max(1, intval($changes[$f]['new']));
            if ($new !== $old) {
                $ratio  = $new / $old;
                $delta += $base * (($ratio - 1) * 0.05);
            }
        }

        return round($delta, 2);
    }

    /**
     * Kunden-E-Mail-Adresse einer Lizenz auflösen.
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

        // customer_id → WP user e-mail.
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
