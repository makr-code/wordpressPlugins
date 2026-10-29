<?php
/**
 * Plugin-owned cart/checkout dialog using the existing session order workflow.
 */
if (!defined('ABSPATH')) {
    exit;
}

class ThemisDB_Checkout_Overlay {
    public function __construct() {
        add_shortcode('themisdb_cart_button', array($this, 'button'));
        add_action('wp_enqueue_scripts', array($this, 'assets'), 20);
        add_action('wp_footer', array($this, 'render'));
        add_action('template_redirect', array($this, 'frame'), 5);
    }

    public static function url($view = 'cart') {
        return add_query_arg(array(
            'themisdb_checkout_frame' => '1',
            'view' => $view,
            '_wpnonce' => wp_create_nonce('themisdb_checkout_frame'),
        ), home_url('/'));
    }

    public function assets() {
        wp_enqueue_style('themisdb-checkout-overlay', THEMISDB_ORDER_PLUGIN_URL . 'assets/css/checkout-overlay.css', array(), THEMISDB_ORDER_VERSION);
        if (isset($_GET['themisdb_checkout_frame'])) {
            return;
        }
        wp_enqueue_script('themisdb-checkout-overlay', THEMISDB_ORDER_PLUGIN_URL . 'assets/js/checkout-overlay.js', array(), THEMISDB_ORDER_VERSION, true);
        wp_localize_script('themisdb-checkout-overlay', 'themisdbCheckout', array('url' => self::url()));
    }

    public function button() {
        return '<button type="button" class="themisdb-cart-button" data-themisdb-open-cart aria-haspopup="dialog" aria-controls="themisdb-checkout-dialog" aria-label="' .
            esc_attr__('Warenkorb und Kasse öffnen', 'themisdb-order-request') . '">' .
            '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2 3h3l3 12h11l3-9H6"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg>' .
            '<span class="screen-reader-text">' . esc_html__('Warenkorb', 'themisdb-order-request') . '</span></button>';
    }

    public function render() {
        if (isset($_GET['themisdb_checkout_frame'])) {
            return;
        }
        ?>
        <dialog id="themisdb-checkout-dialog" class="themisdb-checkout-dialog" aria-labelledby="themisdb-checkout-title">
            <div class="themisdb-checkout-bar">
                <h2 id="themisdb-checkout-title"><?php esc_html_e('Warenkorb und Kasse', 'themisdb-order-request'); ?></h2>
                <button type="button" data-themisdb-close-cart aria-label="<?php esc_attr_e('Schließen', 'themisdb-order-request'); ?>">&times;</button>
            </div>
            <iframe title="<?php esc_attr_e('Warenkorb und Bezahlung', 'themisdb-order-request'); ?>"></iframe>
        </dialog>
        <?php
    }

    public function frame() {
        if (!isset($_GET['themisdb_checkout_frame'])) {
            return;
        }
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'themisdb_checkout_frame')) {
            wp_die(esc_html__('Der Warenkorb-Link ist abgelaufen. Bitte die Seite neu laden.', 'themisdb-order-request'), '', array('response' => 403));
        }
        nocache_headers();
        header('X-Frame-Options: SAMEORIGIN');
        if (!session_id()) {
            session_start();
        }
        $shortcodes = new ThemisDB_Order_Shortcodes();
        $selection = isset($_GET['edition']) ? sanitize_key(wp_unslash($_GET['edition'])) : '';
        $flow = '';
        if ($selection !== '') {
            $product = ThemisDB_Order_Manager::get_product_by_edition($selection);
            if (!$product || $product['product_type'] !== 'database') {
                wp_die(esc_html__('Dieses Lizenzprodukt ist nicht verfügbar.', 'themisdb-order-request'), '', array('response' => 400));
            }
            unset($_GET['training'], $_GET['trainings']);
            $prepared = $shortcodes->prepare_license_cart($selection);
            if (is_wp_error($prepared)) {
                error_log('ThemisDB checkout overlay: ' . $prepared->get_error_message());
                wp_die(esc_html($prepared->get_error_message()), '', array('response' => 500));
            }
            unset($_GET['edition'], $_GET['product'], $_GET['modules'], $_GET['module']);
        }
        $order_id = intval($_SESSION['themisdb_order_id'] ?? 0);
        $order = $order_id ? ThemisDB_Order_Manager::get_order($order_id) : null;
        if ($selection !== '' && (!$order || $order['product_edition'] !== $selection)) {
            error_log('ThemisDB checkout overlay: product selection could not be saved.');
            wp_die(esc_html__('Das Lizenzprodukt konnte nicht in den Warenkorb gelegt werden. Bitte erneut versuchen.', 'themisdb-order-request'), '', array('response' => 500));
        }
        $view = isset($_GET['view']) && $_GET['view'] === 'checkout' ? 'checkout' : 'cart';
        if ($order && (!in_array($order['status'], array('draft', 'pending'), true) || $order['product_type'] !== 'database')) {
            $order = null;
            unset($_SESSION['themisdb_order_id']);
        }
        if ($view === 'checkout' && $order && $flow === '') {
            $flow = $shortcodes->express_checkout_shortcode(array());
        }
        if ($view === 'checkout' && $order) {
            $html = $flow;
        } else {
            $html = $shortcodes->shopping_cart_shortcode(array('checkout_url' => self::url('checkout')));
        }
        status_header(200);
        ?><!doctype html>
        <html <?php language_attributes(); ?>>
        <head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width, initial-scale=1"><?php wp_head(); ?></head>
        <body <?php body_class('themisdb-checkout-frame'); ?>>
            <nav class="themisdb-checkout-frame-nav" aria-label="<?php esc_attr_e('Bestellnavigation', 'themisdb-order-request'); ?>">
                <a href="<?php echo esc_url(self::url()); ?>"><?php esc_html_e('Warenkorb', 'themisdb-order-request'); ?></a>
                <a href="<?php echo esc_url(home_url('/products/')); ?>" target="_parent"><?php esc_html_e('Lizenzprodukte auswählen', 'themisdb-order-request'); ?></a>
            </nav>
            <main><?php echo $html; // Plugin-owned shortcode output. ?></main>
            <?php wp_print_footer_scripts(); ?>
            <script>
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') window.parent.postMessage({type: 'themisdb-close-cart'}, window.location.origin);
            });
            document.addEventListener('themisdb-order-step', function(event) {
                if (event.detail.step !== 5 || !event.detail.persisted) return;
                var url = new URL(window.location.href);
                ['edition', 'product', 'modules', 'training', 'trainings', 'checkout'].forEach(function(key) { url.searchParams.delete(key); });
                url.searchParams.set('view', 'checkout');
                window.location.replace(url.href);
            });
            document.addEventListener('themisdb-order-submitted', function() {
                document.querySelectorAll('.themisdb-order-flow .order-step-content, .themisdb-order-flow .order-steps').forEach(function(el) { el.hidden = true; });
            });
            </script>
        </body></html><?php
        exit;
    }
}
