<?php
/**
 * ThemisDB – WooCommerce Bridge E2E Fixture Script
 *
 * Creates minimal test data to exercise the full Woo → ThemisDB sync chain:
 *   Products (product / module / training) → Orders → Status transitions
 *   → ThemisDB Order → Contract → License
 *
 * Usage (WP-CLI):
 *   wp eval-file scripts/create-test-woo-orders.php --path=/path/to/wordpress
 *
 * Optional env-var flags (set via WP_CLI::get_runner()->extra_config or define before include):
 *   THEMISDB_FIXTURE_CLEANUP=1   – delete all test data afterwards (dry-run by default: kept)
 *   THEMISDB_FIXTURE_VERBOSE=1   – dump extra debug info
 *
 * All created objects are tagged with meta '_themisdb_fixture = 1' for safe cleanup.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit( 'Run via WP-CLI: wp eval-file ' . __FILE__ . "\n" );
}

if ( ! class_exists( 'WooCommerce' ) ) {
    WP_CLI::error( 'WooCommerce is not active. Activate it first.' );
}

if ( ! class_exists( 'ThemisDB_WooCommerce_Bridge' ) ) {
    WP_CLI::error( 'ThemisDB_WooCommerce_Bridge class not found. Activate the plugin first.' );
}

$verbose  = ! empty( getenv( 'THEMISDB_FIXTURE_VERBOSE' ) );
$cleanup  = ! empty( getenv( 'THEMISDB_FIXTURE_CLEANUP' ) );
$results  = array(
    'products'  => array(),
    'orders'    => array(),
    'assertions' => array(),
    'errors'    => array(),
);

// ──────────────────────────────────────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────────────────────────────────────

function fixture_log( $msg, $verbose_only = false ) {
    global $verbose;
    if ( $verbose_only && ! $verbose ) {
        return;
    }
    WP_CLI::log( $msg );
}

function fixture_assert( $label, $condition, &$results ) {
    if ( $condition ) {
        WP_CLI::log( "  [PASS] $label" );
        $results['assertions'][] = array( 'label' => $label, 'result' => 'pass' );
    } else {
        WP_CLI::warning( "  [FAIL] $label" );
        $results['assertions'][] = array( 'label' => $label, 'result' => 'fail' );
        $results['errors'][]     = $label;
    }
}

// ──────────────────────────────────────────────────────────────────────────────
// Step 1 – Create test products
// ──────────────────────────────────────────────────────────────────────────────

WP_CLI::log( "\n── Step 1: Create test products ───────────────────────────────" );

$product_specs = array(
    array(
        'name'  => '[Fixture] ThemisDB Enterprise License',
        'sku'   => 'FIXTURE-THEMIS-ENT',
        'price' => '2499.00',
        'meta'  => array(
            'themisdb_product_edition' => 'enterprise',
            'themisdb_product_type'    => 'database',
        ),
    ),
    array(
        'name'  => '[Fixture] MOD-GRAPHQUERY Module',
        'sku'   => 'MOD-GRAPHQUERY-FX',
        'price' => '499.00',
        'meta'  => array(
            'themisdb_item_type'       => 'module',
            'themisdb_module_code'     => 'MOD-GRAPHQUERY-FX',
            'themisdb_module_category' => 'query',
        ),
    ),
    array(
        'name'  => '[Fixture] TRAIN-FOUNDATIONS Training',
        'sku'   => 'TRAIN-FOUNDATIONS-FX',
        'price' => '299.00',
        'meta'  => array(
            'themisdb_item_type'              => 'training',
            'themisdb_training_code'          => 'TRAIN-FOUNDATIONS-FX',
            'themisdb_training_type'          => 'online',
            'themisdb_training_duration_hours'=> '8',
        ),
    ),
);

foreach ( $product_specs as $spec ) {
    $product = new WC_Product_Simple();
    $product->set_name( $spec['name'] );
    $product->set_sku( $spec['sku'] );
    $product->set_regular_price( $spec['price'] );
    $product->set_status( 'publish' );
    $product->set_catalog_visibility( 'hidden' );
    $id = $product->save();

    if ( ! $id ) {
        WP_CLI::error( 'Could not create product: ' . $spec['name'] );
    }

    foreach ( $spec['meta'] as $key => $value ) {
        update_post_meta( $id, $key, $value );
    }
    update_post_meta( $id, '_themisdb_fixture', '1' );

    fixture_log( "  Created: {$spec['name']} (WC post_id=$id, SKU={$spec['sku']})" );
    $results['products'][] = array( 'id' => $id, 'sku' => $spec['sku'], 'type' => key( $spec['meta'] ) );

    // Trigger product sync explicitly (simulates woocommerce_update_product hook).
    $bridge = new ThemisDB_WooCommerce_Bridge();
    $bridge->on_product_updated( $id );
}

// Verify product sync results.
WP_CLI::log( "\n── Step 1 Assertions ───────────────────────────────────────────" );
foreach ( $results['products'] as $p ) {
    $product_db_id  = get_post_meta( $p['id'], '_themisdb_product_id',  true );
    $module_db_id   = get_post_meta( $p['id'], '_themisdb_module_id',   true );
    $training_db_id = get_post_meta( $p['id'], '_themisdb_training_id', true );

    $sku = $p['sku'];
    if ( strpos( $sku, 'MOD-' ) === 0 ) {
        fixture_assert( "Product SKU=$sku: _themisdb_module_id set", ! empty( $module_db_id ), $results );
    } elseif ( strpos( $sku, 'TRAIN-' ) === 0 ) {
        fixture_assert( "Product SKU=$sku: _themisdb_training_id set", ! empty( $training_db_id ), $results );
    } else {
        fixture_assert( "Product SKU=$sku: _themisdb_product_id set", ! empty( $product_db_id ), $results );
    }
}

// ──────────────────────────────────────────────────────────────────────────────
// Step 2 – Create test orders and drive status transitions
// ──────────────────────────────────────────────────────────────────────────────

WP_CLI::log( "\n── Step 2: Create test orders ──────────────────────────────────" );

$product_ids = array_column( $results['products'], 'id' );
$main_product_id = $product_ids[0]; // Enterprise license product.

$order_scenarios = array(
    array(
        'label'          => 'Completed order (enterprise)',
        'product_id'     => $main_product_id,
        'payment_method' => 'bacs',
        'statuses'       => array( 'pending', 'processing', 'completed' ),
        'expected_themis_status' => 'confirmed',
        'expect_license' => true,
    ),
    array(
        'label'          => 'Cancelled order',
        'product_id'     => $main_product_id,
        'payment_method' => 'stripe',
        'statuses'       => array( 'pending', 'processing', 'cancelled' ),
        'expected_themis_status' => 'cancelled',
        'expect_license' => false,
    ),
    array(
        'label'          => 'Refunded order',
        'product_id'     => $main_product_id,
        'payment_method' => 'paypal',
        'statuses'       => array( 'pending', 'processing', 'completed', 'refunded' ),
        'expected_themis_status' => 'ended',
        'expect_license' => true, // created on completed, then lifecycle ends on refund.
    ),
    array(
        'label'          => 'COD cheque order (stays pending on processing)',
        'product_id'     => $main_product_id,
        'payment_method' => 'cheque',
        'statuses'       => array( 'pending', 'processing' ),
        'expected_themis_status' => 'pending',
        'expect_license' => false,
    ),
);

$bridge = new ThemisDB_WooCommerce_Bridge();

foreach ( $order_scenarios as $scenario ) {
    $woo_order = wc_create_order();
    if ( is_wp_error( $woo_order ) ) {
        WP_CLI::warning( 'Could not create WC order for scenario: ' . $scenario['label'] );
        $results['errors'][] = 'WC order creation failed: ' . $scenario['label'];
        continue;
    }

    $woo_order->add_product( wc_get_product( $scenario['product_id'] ), 1 );
    $woo_order->set_billing_first_name( 'Fixture' );
    $woo_order->set_billing_last_name( 'Tester' );
    $woo_order->set_billing_email( 'fixture-test@themisdb.invalid' );
    $woo_order->set_billing_company( 'ThemisDB Test GmbH' );
    $woo_order->set_payment_method( $scenario['payment_method'] );
    $woo_order->calculate_totals();
    $woo_order->save();
    $woo_order_id = $woo_order->get_id();
    update_post_meta( $woo_order_id, '_themisdb_fixture', '1' );

    fixture_log( "  Created WC order #{$woo_order_id}: {$scenario['label']}" );

    // Walk through each status change to simulate real checkout flow.
    $previous = 'pending';
    foreach ( $scenario['statuses'] as $status ) {
        if ( $status === 'pending' ) {
            continue; // Initial status already set.
        }
        $woo_order->set_status( $status );
        $woo_order->save();
        // Trigger bridge handler directly (hooks don't fire on programmatic set_status).
        $bridge->on_order_status_changed( $woo_order_id, $previous, $status, $woo_order );
        fixture_log( "    → status: $previous → $status", true );
        $previous = $status;
    }

    $results['orders'][] = array(
        'woo_order_id' => $woo_order_id,
        'label'        => $scenario['label'],
        'scenario'     => $scenario,
    );

    // ── Assertions ────────────────────────────────────────────────────────────
    WP_CLI::log( "\n  Assertions for: {$scenario['label']}" );

    $themis_order_id = intval( get_post_meta( $woo_order_id, '_themisdb_order_id', true ) );

    if ( in_array( 'completed', $scenario['statuses'] ) || in_array( 'processing', $scenario['statuses'] ) ) {
        fixture_assert( "_themisdb_order_id set (woo#{$woo_order_id})", $themis_order_id > 0, $results );

        if ( $themis_order_id > 0 && class_exists( 'ThemisDB_Order_Manager' ) ) {
            $themis_order = ThemisDB_Order_Manager::get_order( $themis_order_id );
            fixture_assert(
                "ThemisDB order status = {$scenario['expected_themis_status']}",
                $themis_order && $themis_order['status'] === $scenario['expected_themis_status'],
                $results
            );
        }
    } else {
        fixture_assert(
            "_themisdb_order_id NOT set for pending-only order",
            $themis_order_id === 0,
            $results
        );
    }

    if ( $scenario['expect_license'] ) {
        $license_id = intval( get_post_meta( $woo_order_id, '_themisdb_license_id', true ) );
        fixture_assert( "_themisdb_license_id set (woo#{$woo_order_id})", $license_id > 0, $results );

        if ( $license_id > 0 && class_exists( 'ThemisDB_License_Manager' ) ) {
            $license = ThemisDB_License_Manager::get_license( $license_id );
            $final_woo_status = end( $scenario['statuses'] );
            if ( $final_woo_status === 'refunded' || $final_woo_status === 'cancelled' ) {
                fixture_assert(
                    "License #{$license_id} ended/cancelled after $final_woo_status",
                    $license && in_array( $license['license_status'], array( 'cancelled', 'ended' ), true ),
                    $results
                );
            } else {
                fixture_assert(
                    "License #{$license_id} is active",
                    $license && $license['license_status'] === 'active',
                    $results
                );
            }
        }
    }
}

// ──────────────────────────────────────────────────────────────────────────────
// Step 3 – Product Trash/Untrash cycle
// ──────────────────────────────────────────────────────────────────────────────

WP_CLI::log( "\n── Step 3: Product trash/untrash lifecycle ─────────────────────" );

foreach ( $results['products'] as $p ) {
    $prod = wc_get_product( $p['id'] );
    if ( ! $prod ) {
        continue;
    }

    wp_trash_post( $p['id'] );
    $bridge->on_product_trashed( $p['id'] );

    global $wpdb;
    $table    = $wpdb->prefix . 'themisdb_products';
    $mod_tbl  = $wpdb->prefix . 'themisdb_modules';
    $trn_tbl  = $wpdb->prefix . 'themisdb_training_modules';

    $db_id = intval( get_post_meta( $p['id'], '_themisdb_product_id', true ) );
    $md_id = intval( get_post_meta( $p['id'], '_themisdb_module_id',  true ) );
    $tr_id = intval( get_post_meta( $p['id'], '_themisdb_training_id', true ) );

    if ( $db_id ) {
        $is_active = intval( $wpdb->get_var( $wpdb->prepare( "SELECT is_active FROM $table WHERE id = %d", $db_id ) ) );
        fixture_assert( "Product {$p['sku']} is_active=0 after trash", $is_active === 0, $results );
    }
    if ( $md_id ) {
        $is_active = intval( $wpdb->get_var( $wpdb->prepare( "SELECT is_active FROM $mod_tbl WHERE id = %d", $md_id ) ) );
        fixture_assert( "Module {$p['sku']} is_active=0 after trash", $is_active === 0, $results );
    }
    if ( $tr_id ) {
        $is_active = intval( $wpdb->get_var( $wpdb->prepare( "SELECT is_active FROM $trn_tbl WHERE id = %d", $tr_id ) ) );
        fixture_assert( "Training {$p['sku']} is_active=0 after trash", $is_active === 0, $results );
    }

    // Restore.
    wp_untrash_post( $p['id'] );
    $bridge->on_product_untrashed( $p['id'] );

    if ( $db_id ) {
        $is_active = intval( $wpdb->get_var( $wpdb->prepare( "SELECT is_active FROM $table WHERE id = %d", $db_id ) ) );
        fixture_assert( "Product {$p['sku']} is_active=1 after untrash", $is_active === 1, $results );
    }
    if ( $md_id ) {
        $is_active = intval( $wpdb->get_var( $wpdb->prepare( "SELECT is_active FROM $mod_tbl WHERE id = %d", $md_id ) ) );
        fixture_assert( "Module {$p['sku']} is_active=1 after untrash", $is_active === 1, $results );
    }
    if ( $tr_id ) {
        $is_active = intval( $wpdb->get_var( $wpdb->prepare( "SELECT is_active FROM $trn_tbl WHERE id = %d", $tr_id ) ) );
        fixture_assert( "Training {$p['sku']} is_active=1 after untrash", $is_active === 1, $results );
    }
}

// ──────────────────────────────────────────────────────────────────────────────
// Step 4 – Optional cleanup
// ──────────────────────────────────────────────────────────────────────────────

if ( $cleanup ) {
    WP_CLI::log( "\n── Step 4: Cleanup fixture data ────────────────────────────────" );

    foreach ( $results['products'] as $p ) {
        wp_delete_post( $p['id'], true );
        fixture_log( "  Deleted product post #{$p['id']}" );
    }

    foreach ( $results['orders'] as $o ) {
        $woo_order = wc_get_order( $o['woo_order_id'] );
        if ( $woo_order ) {
            $woo_order->delete( true );
            fixture_log( "  Deleted WC order #{$o['woo_order_id']}" );
        }
    }
} else {
    WP_CLI::log( "\n── Step 4: Fixture data kept (set THEMISDB_FIXTURE_CLEANUP=1 to delete) ──" );
    WP_CLI::log( "  Products:  " . implode( ', ', array_column( $results['products'], 'id' ) ) );
    WP_CLI::log( "  WC Orders: " . implode( ', ', array_column( $results['orders'],   'woo_order_id' ) ) );
}

// ──────────────────────────────────────────────────────────────────────────────
// Summary
// ──────────────────────────────────────────────────────────────────────────────

WP_CLI::log( "\n══ E2E Fixture Summary ══════════════════════════════════════════" );

$pass  = count( array_filter( $results['assertions'], fn( $a ) => $a['result'] === 'pass' ) );
$fail  = count( $results['errors'] );
$total = count( $results['assertions'] );

WP_CLI::log( "  Assertions: {$pass}/{$total} passed" );

if ( $fail > 0 ) {
    WP_CLI::log( "  Failed:" );
    foreach ( $results['errors'] as $err ) {
        WP_CLI::log( "    - $err" );
    }
    WP_CLI::error( "E2E fixture completed with {$fail} failure(s)." );
} else {
    WP_CLI::success( "All {$total} assertions passed. WooCommerce Bridge Phase 2.1 E2E OK." );
}
