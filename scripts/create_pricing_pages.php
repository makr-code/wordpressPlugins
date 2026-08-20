<?php
/**
 * Create pricing pages (Community, Enterprise, Custom) for local WordPress.
 * Run via: C:\xampp\php\php.exe scripts\create_pricing_pages.php
 */

define( 'WP_USE_THEMES', false );
require_once 'C:/xampp/htdocs/wordpress/wp-load.php';

function ensure_term_id( $taxonomy, $name, $slug ) {
    $term = get_term_by( 'slug', $slug, $taxonomy );
    if ( $term && ! is_wp_error( $term ) ) {
        return (int) $term->term_id;
    }

    $result = wp_insert_term( $name, $taxonomy, array( 'slug' => $slug ) );
    if ( is_wp_error( $result ) ) {
        fwrite( STDOUT, "Failed to create term $slug: " . $result->get_error_message() . PHP_EOL );
        return 0;
    }

    return (int) $result['term_id'];
}

$pricing_cat_id = ensure_term_id( 'category', 'Pricing', 'pricing' );
$featured_tag_id = ensure_term_id( 'post_tag', 'Pricing Featured', 'pricing-featured' );
$frontpage_tag_id = ensure_term_id( 'post_tag', 'Frontpage Feature', 'frontpage-feature' );

if ( 0 === $pricing_cat_id ) {
    fwrite( STDOUT, "Could not ensure pricing category. Aborting.\n" );
    exit(1);
}

$pages = array(
    array(
        'post_name'    => 'community',
        'post_title'   => 'Community',
        'post_content' => "Open-source Community Edition. Self-hosted, MIT license.\n\n• Kostenlos\n• Selbst gehostet\n• Community-Support",
        'meta'         => array(
            'tv3_pricing_tier' => 'Community',
            'tv3_price_label'  => '$0',
            'tv3_pricing_cta_label' => 'Download Free',
        ),
        'tags'         => array(),
    ),
    array(
        'post_name'    => 'enterprise',
        'post_title'   => 'Enterprise',
        'post_content' => "Enterprise Edition mit SLA, Support und Integrationen.\n\n• SLA & Support\n• Enterprise-Integrationen\n• Beratung & Onboarding",
        'meta'         => array(
            'tv3_pricing_tier' => 'Enterprise',
            'tv3_price_label'  => 'Custom',
            'tv3_pricing_cta_label' => 'Contact Sales →',
        ),
        'tags'         => array( 'pricing-featured', 'most-popular' ),
    ),
    array(
        'post_name'    => 'custom',
        'post_title'   => 'Custom',
        'post_content' => "Individuelle Angebote und Managed Services.\n\n• Individuelle SLAs\n• Managed Hosting\n• Integration & Engineering",
        'meta'         => array(
            'tv3_pricing_tier' => 'Managed',
            'tv3_price_label'  => 'Contact',
            'tv3_pricing_cta_label' => 'Request Quote',
        ),
        'tags'         => array( 'managed' ),
    ),
);

foreach ( $pages as $p ) {
    $existing = get_page_by_path( $p['post_name'] );
    if ( $existing instanceof WP_Post ) {
        fwrite( STDOUT, "Skipping existing page: {$p['post_name']} (ID={$existing->ID})\n" );
        // Ensure category and tags set
        wp_set_post_terms( $existing->ID, array( $pricing_cat_id ), 'category', true );
        if ( ! empty( $p['tags'] ) ) {
            wp_set_post_terms( $existing->ID, $p['tags'], 'post_tag', true );
        }
        foreach ( $p['meta'] as $k => $v ) {
            update_post_meta( $existing->ID, $k, $v );
        }
        continue;
    }

    $post_arr = array(
        'post_type'    => 'page',
        'post_name'    => $p['post_name'],
        'post_title'   => $p['post_title'],
        'post_content' => $p['post_content'],
        'post_status'  => 'publish',
        'post_author'  => 1,
    );

    $new_id = wp_insert_post( $post_arr );
    if ( is_wp_error( $new_id ) ) {
        fwrite( STDOUT, "Failed to create {$p['post_name']}: " . $new_id->get_error_message() . PHP_EOL );
        continue;
    }

    wp_set_post_terms( $new_id, array( $pricing_cat_id ), 'category', true );
    if ( ! empty( $p['tags'] ) ) {
        wp_set_post_terms( $new_id, $p['tags'], 'post_tag', true );
    }
    foreach ( $p['meta'] as $k => $v ) {
        update_post_meta( $new_id, $k, $v );
    }

    fwrite( STDOUT, "Created pricing page: {$p['post_name']} (ID={$new_id})\n" );
}

fwrite( STDOUT, "Done. Run a browser reload of the frontpage to see cards.\n" );
