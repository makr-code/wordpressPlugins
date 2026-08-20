<?php
/**
 * Assign frontpage categories and priority tags to existing WordPress pages.
 */

define( 'WP_USE_THEMES', false );
require_once 'c:\xampp\htdocs\wordpress\wp-load.php';
require_once ABSPATH . 'wp-admin/includes/taxonomy.php';

function ensure_term_id( $taxonomy, $name, $slug ) {
    $term = get_term_by( 'slug', $slug, $taxonomy );
    if ( $term && ! is_wp_error( $term ) ) {
        return (int) $term->term_id;
    }

    $result = wp_insert_term( $name, $taxonomy, array( 'slug' => $slug ) );
    if ( is_wp_error( $result ) ) {
        return 0;
    }

    return (int) $result['term_id'];
}

function collect_page_ids_by_tag_slugs( array $tag_slugs ) {
    $tag_slugs = array_values( array_filter( array_map( 'sanitize_title', $tag_slugs ) ) );
    if ( empty( $tag_slugs ) ) {
        return array();
    }

    $posts = get_posts(
        array(
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'tax_query'      => array(
                array(
                    'taxonomy' => 'post_tag',
                    'field'    => 'slug',
                    'terms'    => $tag_slugs,
                ),
            ),
        )
    );

    return array_map( 'absint', $posts );
}

function collect_page_ids_by_paths( array $paths ) {
    $ids = array();
    foreach ( $paths as $path ) {
        $page = get_page_by_path( sanitize_title( $path ) );
        if ( $page instanceof WP_Post ) {
            $ids[] = (int) $page->ID;
        }
    }

    return array_values( array_unique( array_filter( $ids ) ) );
}

$feature_category_id        = ensure_term_id( 'category', 'Features', 'features' );
$documentation_category_id  = ensure_term_id( 'category', 'Documentation', 'documentation' );
$feature_priority_tag_id    = ensure_term_id( 'post_tag', 'Frontpage Feature', 'frontpage-feature' );
$docs_priority_tag_id       = ensure_term_id( 'post_tag', 'Frontpage Documentation', 'frontpage-documentation' );

if ( 0 === $feature_category_id || 0 === $documentation_category_id || 0 === $feature_priority_tag_id || 0 === $docs_priority_tag_id ) {
    echo "Failed to prepare required terms.\n";
    exit( 1 );
}

$feature_page_ids = array_merge(
    collect_page_ids_by_tag_slugs( array( 'feature', 'themisdb-feature' ) ),
    collect_page_ids_by_paths( array( 'features', 'products', 'solutions', 'pricing', 'benchmarks', 'query-playground', 'docker', 'advanced-analytics', 'vector-search', 'real-time-sync' ) )
);
$feature_page_ids = array_values( array_unique( array_filter( $feature_page_ids ) ) );

$documentation_page_ids = array_merge(
    collect_page_ids_by_tag_slugs( array( 'documentation', 'docs', 'themisdb-docs' ) ),
    collect_page_ids_by_paths( array( 'docs', 'tutorials', 'guides', 'api', 'concepts' ) )
);
$documentation_page_ids = array_values( array_unique( array_filter( $documentation_page_ids ) ) );

echo "Feature pages: " . count( $feature_page_ids ) . "\n";
echo "Documentation pages: " . count( $documentation_page_ids ) . "\n";

foreach ( $feature_page_ids as $page_id ) {
    wp_set_post_terms( $page_id, array( $feature_category_id ), 'category', true );
    wp_set_post_terms( $page_id, array( $feature_priority_tag_id ), 'post_tag', true );
    echo "[OK] Feature page updated: {$page_id}\n";
}

foreach ( $documentation_page_ids as $page_id ) {
    wp_set_post_terms( $page_id, array( $documentation_category_id ), 'category', true );
    wp_set_post_terms( $page_id, array( $docs_priority_tag_id ), 'post_tag', true );
    echo "[OK] Documentation page updated: {$page_id}\n";
}

echo "Done.\n";
