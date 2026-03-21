<?php
/**
 * Affiliate Program Manager (Phase 5.2)
 *
 * Features:
 * - Referral links
 * - Commission tracking
 * - Payout management
 */

if (!defined('ABSPATH')) {
    exit;
}

class ThemisDB_Affiliate_Program {

    const COOKIE_REFERRAL_CODE = 'themisdb_ref';
    const QUERY_REFERRAL_CODE = 'tdb_ref';

    /**
     * Initialize affiliate hooks.
     */
    public static function init() {
        add_action('init', array(__CLASS__, 'capture_referral_code'), 5);

        // Order lifecycle hooks from frontend and Woo bridge.
        add_action('themisdb_order_submitted', array(__CLASS__, 'track_conversion_for_order'), 10, 2);
        add_action('themisdb_woocommerce_order_synced', array(__CLASS__, 'track_conversion_for_woo_order'), 10, 4);

        // Optional lightweight frontend shortcode for affiliates.
        add_shortcode('themisdb_affiliate_dashboard', array(__CLASS__, 'affiliate_dashboard_shortcode'));
    }

    /**
     * Register or update an affiliate account.
     */
    public static function register_affiliate($user_id = 0, $contact_email = '', $commission_rate = null) {
        global $wpdb;

        $table = $wpdb->prefix . 'themisdb_affiliates';
        $user_id = intval($user_id);
        $email = sanitize_email($contact_email);

        if ($commission_rate === null) {
            $commission_rate = floatval(get_option('themisdb_affiliate_default_commission_rate', 10));
        }

        $existing = null;
        if ($user_id > 0) {
            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table} WHERE user_id = %d LIMIT 1",
                $user_id
            ), ARRAY_A);
        } elseif (!empty($email)) {
            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table} WHERE contact_email = %s LIMIT 1",
                $email
            ), ARRAY_A);
        }

        if ($existing) {
            $update = array(
                'commission_rate' => floatval($commission_rate),
                'status' => 'active',
            );
            if (!empty($email)) {
                $update['contact_email'] = $email;
            }
            $ok = $wpdb->update($table, $update, array('id' => intval($existing['id'])));
            return $ok !== false ? intval($existing['id']) : false;
        }

        $referral_code = self::generate_unique_referral_code();

        $ok = $wpdb->insert($table, array(
            'user_id' => $user_id > 0 ? $user_id : null,
            'referral_code' => $referral_code,
            'contact_email' => !empty($email) ? $email : null,
            'commission_rate' => floatval($commission_rate),
            'status' => 'active',
        ));

        if (!$ok) {
            return false;
        }

        return intval($wpdb->insert_id);
    }

    /**
     * Generate referral link for an affiliate code.
     */
    public static function create_referral_link($referral_code, $target_url = '') {
        $code = sanitize_text_field($referral_code);
        if ($target_url === '') {
            $target_url = get_option('themisdb_order_page_url', home_url('/bestellung'));
        }
        return add_query_arg(array(self::QUERY_REFERRAL_CODE => rawurlencode($code)), $target_url);
    }

    /**
     * Capture referral code from URL and persist in cookie.
     */
    public static function capture_referral_code() {
        if (empty($_GET[self::QUERY_REFERRAL_CODE])) {
            return;
        }

        $code = sanitize_text_field(wp_unslash($_GET[self::QUERY_REFERRAL_CODE]));
        if ($code === '') {
            return;
        }

        $affiliate = self::get_affiliate_by_code($code);
        if (!$affiliate || $affiliate['status'] !== 'active') {
            return;
        }

        $cookie_days = max(1, intval(get_option('themisdb_affiliate_cookie_days', 30)));
        $expire = time() + ($cookie_days * DAY_IN_SECONDS);

        setcookie(self::COOKIE_REFERRAL_CODE, $code, $expire, COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), true);
        $_COOKIE[self::COOKIE_REFERRAL_CODE] = $code;
    }

    /**
     * Track conversion for frontend order flow.
     */
    public static function track_conversion_for_order($order_id, $order = null) {
        $referral_code = self::get_current_referral_code();
        if ($referral_code === '') {
            return;
        }

        self::track_conversion($order_id, $referral_code, 'frontend', $order);
    }

    /**
     * Track conversion for WooCommerce synchronized orders.
     */
    public static function track_conversion_for_woo_order($themis_order_id, $woo_order_id, $woo_order, $mapped_data) {
        $referral_code = '';

        if ($woo_order && method_exists($woo_order, 'get_meta')) {
            $referral_code = sanitize_text_field((string) $woo_order->get_meta('_themisdb_referral_code', true));
        }

        if ($referral_code === '') {
            $referral_code = self::get_current_referral_code();
        }

        if ($referral_code === '') {
            return;
        }

        self::track_conversion($themis_order_id, $referral_code, 'woocommerce');
    }

    /**
     * Insert conversion + commission record if not already tracked.
     */
    public static function track_conversion($order_id, $referral_code, $source = 'frontend', $order = null) {
        global $wpdb;

        if (!class_exists('ThemisDB_Order_Manager')) {
            return false;
        }

        $order_id = intval($order_id);
        if ($order_id <= 0) {
            return false;
        }

        $affiliate = self::get_affiliate_by_code($referral_code);
        if (!$affiliate || $affiliate['status'] !== 'active') {
            return false;
        }

        $table_conv = $wpdb->prefix . 'themisdb_affiliate_conversions';
        $table_comm = $wpdb->prefix . 'themisdb_affiliate_commissions';

        $already = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table_conv} WHERE order_id = %d LIMIT 1",
            $order_id
        ));
        if (!empty($already)) {
            return true;
        }

        if (!$order) {
            $order = ThemisDB_Order_Manager::get_order($order_id);
        }

        if (!$order) {
            return false;
        }

        $order_total = floatval($order['total_amount'] ?? 0);
        $currency = sanitize_text_field($order['currency'] ?? 'EUR');
        $rate = floatval($affiliate['commission_rate']);
        $commission_amount = round($order_total * ($rate / 100), 2);

        $wpdb->query('START TRANSACTION');

        $conv_ok = $wpdb->insert($table_conv, array(
            'affiliate_id' => intval($affiliate['id']),
            'order_id' => $order_id,
            'referral_code' => sanitize_text_field($referral_code),
            'conversion_source' => sanitize_key($source),
            'order_total' => $order_total,
            'currency' => $currency,
            'metadata' => wp_json_encode(array(
                'customer_id' => intval($order['customer_id'] ?? 0),
                'product_edition' => sanitize_key($order['product_edition'] ?? ''),
            )),
        ));

        if (!$conv_ok) {
            $wpdb->query('ROLLBACK');
            return false;
        }

        $conversion_id = intval($wpdb->insert_id);
        $comm_ok = $wpdb->insert($table_comm, array(
            'affiliate_id' => intval($affiliate['id']),
            'conversion_id' => $conversion_id,
            'order_id' => $order_id,
            'commission_rate' => $rate,
            'commission_amount' => $commission_amount,
            'currency' => $currency,
            'status' => 'pending',
        ));

        if (!$comm_ok) {
            $wpdb->query('ROLLBACK');
            return false;
        }

        $wpdb->query('COMMIT');

        if (class_exists('ThemisDB_Error_Handler')) {
            ThemisDB_Error_Handler::log('info', 'Affiliate conversion tracked', array(
                'order_id' => $order_id,
                'affiliate_id' => intval($affiliate['id']),
                'commission_amount' => $commission_amount,
                'currency' => $currency,
                'source' => sanitize_key($source),
            ));
        }

        return true;
    }

    /**
     * Create payout for all pending commissions of an affiliate.
     */
    public static function create_payout_for_pending_commissions($affiliate_id, $notes = '') {
        global $wpdb;

        $affiliate_id = intval($affiliate_id);
        if ($affiliate_id <= 0) {
            return false;
        }

        $table_comm = $wpdb->prefix . 'themisdb_affiliate_commissions';
        $table_pay = $wpdb->prefix . 'themisdb_affiliate_payouts';

        $pending = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_comm} WHERE affiliate_id = %d AND status = 'pending' ORDER BY created_at ASC",
            $affiliate_id
        ), ARRAY_A);

        if (empty($pending)) {
            return false;
        }

        $total = 0.0;
        $currency = 'EUR';
        $ids = array();
        foreach ($pending as $row) {
            $total += floatval($row['commission_amount']);
            $currency = sanitize_text_field($row['currency'] ?? 'EUR');
            $ids[] = intval($row['id']);
        }
        $total = round($total, 2);

        $wpdb->query('START TRANSACTION');

        $pay_ok = $wpdb->insert($table_pay, array(
            'affiliate_id' => $affiliate_id,
            'amount' => $total,
            'currency' => $currency,
            'payout_method' => 'bank_transfer',
            'status' => 'completed',
            'payout_date' => current_time('mysql'),
            'notes' => sanitize_textarea_field($notes),
            'created_by' => get_current_user_id(),
        ));

        if (!$pay_ok) {
            $wpdb->query('ROLLBACK');
            return false;
        }

        $payout_id = intval($wpdb->insert_id);

        $ids_sql = implode(',', array_map('intval', $ids));
        $upd_ok = $wpdb->query("UPDATE {$table_comm} SET status = 'paid', payout_id = {$payout_id}, updated_at = NOW() WHERE id IN ({$ids_sql})");

        if ($upd_ok === false) {
            $wpdb->query('ROLLBACK');
            return false;
        }

        $wpdb->query('COMMIT');

        if (class_exists('ThemisDB_Error_Handler')) {
            ThemisDB_Error_Handler::log('info', 'Affiliate payout created', array(
                'affiliate_id' => $affiliate_id,
                'payout_id' => $payout_id,
                'amount' => $total,
                'currency' => $currency,
                'commission_count' => count($ids),
            ));
        }

        return $payout_id;
    }

    /**
     * Minimal affiliate dashboard shortcode.
     */
    public static function affiliate_dashboard_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('Bitte anmelden, um Affiliate-Daten zu sehen.', 'themisdb-order-request') . '</p>';
        }

        $user = wp_get_current_user();
        $affiliate = self::get_affiliate_by_user($user->ID);
        if (!$affiliate) {
            $affiliate_id = self::register_affiliate($user->ID, $user->user_email);
            if (!$affiliate_id) {
                return '<p>' . esc_html__('Affiliate-Konto konnte nicht erstellt werden.', 'themisdb-order-request') . '</p>';
            }
            $affiliate = self::get_affiliate_by_user($user->ID);
        }

        $ref_link = self::create_referral_link($affiliate['referral_code']);
        $pending_total = self::get_pending_total(intval($affiliate['id']));

        ob_start();
        ?>
        <div class="themisdb-affiliate-dashboard">
            <p><strong><?php esc_html_e('Referral Code:', 'themisdb-order-request'); ?></strong> <?php echo esc_html($affiliate['referral_code']); ?></p>
            <p><strong><?php esc_html_e('Referral Link:', 'themisdb-order-request'); ?></strong><br />
                <input type="text" readonly style="width:100%;" value="<?php echo esc_attr($ref_link); ?>" />
            </p>
            <p><strong><?php esc_html_e('Pending Commissions:', 'themisdb-order-request'); ?></strong> <?php echo esc_html(number_format($pending_total, 2, ',', '.')); ?> EUR</p>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function get_pending_total($affiliate_id) {
        global $wpdb;
        $table_comm = $wpdb->prefix . 'themisdb_affiliate_commissions';
        return floatval($wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(commission_amount),0) FROM {$table_comm} WHERE affiliate_id = %d AND status = 'pending'",
            intval($affiliate_id)
        )));
    }

    private static function get_affiliate_by_code($code) {
        global $wpdb;
        $table = $wpdb->prefix . 'themisdb_affiliates';
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE referral_code = %s LIMIT 1",
            sanitize_text_field($code)
        ), ARRAY_A);
    }

    private static function get_affiliate_by_user($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'themisdb_affiliates';
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE user_id = %d LIMIT 1",
            intval($user_id)
        ), ARRAY_A);
    }

    private static function get_current_referral_code() {
        if (empty($_COOKIE[self::COOKIE_REFERRAL_CODE])) {
            return '';
        }
        return sanitize_text_field(wp_unslash($_COOKIE[self::COOKIE_REFERRAL_CODE]));
    }

    private static function generate_unique_referral_code() {
        global $wpdb;
        $table = $wpdb->prefix . 'themisdb_affiliates';

        for ($i = 0; $i < 10; $i++) {
            $candidate = strtoupper(wp_generate_password(8, false, false));
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE referral_code = %s",
                $candidate
            ));
            if (intval($exists) === 0) {
                return $candidate;
            }
        }

        return strtoupper(substr(md5(uniqid('tdb_aff_', true)), 0, 10));
    }
}
