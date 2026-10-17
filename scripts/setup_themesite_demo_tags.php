<?php
/**
 * Create demo WordPress tag data for semantic front-end selection.
 *
 * Usage:
 *   php C:\Projects\wordpressPlugins\scripts\setup_themesite_demo_tags.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    define( 'WP_USE_THEMES', false );
    require_once 'c:\xampp\htdocs\wordpress\wp-load.php';
}

function themisdb_demo_ensure_term( $slug, $name ) {
    $term = get_term_by( 'slug', $slug, 'post_tag' );
    if ( $term && ! is_wp_error( $term ) ) {
        return (int) $term->term_id;
    }

    $created = wp_insert_term( $name, 'post_tag', array( 'slug' => $slug ) );
    if ( is_wp_error( $created ) ) {
        return 0;
    }

    return (int) $created['term_id'];
}

function themisdb_demo_ensure_post( $title, array $tag_slugs, $post_type = 'post', $content = '' ) {
    $existing = get_page_by_title( $title, OBJECT, $post_type );
    if ( $existing instanceof WP_Post ) {
        wp_set_post_terms( $existing->ID, $tag_slugs, 'post_tag', true );
        return $existing->ID;
    }

    $post_id = wp_insert_post(
        array(
            'post_title'    => $title,
            'post_status'   => 'publish',
            'post_type'     => $post_type,
            'post_content'  => $content ?: 'Demo content for ' . $title . '.',
            'post_excerpt'  => 'Demo excerpt for ' . $title . '.',
        )
    );

    if ( is_wp_error( $post_id ) ) {
        return 0;
    }

    wp_set_post_terms( $post_id, $tag_slugs, 'post_tag', true );
    return $post_id;
}

$selection_tags = array(
    'announcement' => 'Announcement',
    'hero' => 'Hero',
    'feature' => 'Feature',
    'documentation' => 'Documentation',
    'pricing' => 'Pricing',
    'download' => 'Download',
    'edition' => 'Edition',
    'blog' => 'Blog',
    'support' => 'Support',
    'recommended' => 'Recommended',
    'editor-pick' => 'Editor Pick',
    'release' => 'Release',
    'event' => 'Event',
    'conference' => 'Conference',
    'security' => 'Security',
    'benchmark' => 'Benchmark',
    'case-study' => 'Case Study',
    'trial' => 'Trial',
);

$restricted_tags = array(
    'restricted' => 'Restricted',
    'internal' => 'Internal',
    'admin-only' => 'Admin Only',
);

$all_tags = array_merge( $selection_tags, $restricted_tags );
foreach ( $all_tags as $slug => $label ) {
    themisdb_demo_ensure_term( $slug, $label );
}

$demo_items = array(
    array(
        'title' => 'ThemisDB Launch Announcement',
        'type' => 'post',
        'tags' => array( 'announcement', 'release', 'recommended' ),
        'content' => 'This demo announcement highlights a new release and is meant for the announcement bar and release showcases.',
    ),
    array(
        'title' => 'Hero Story: Real-time Database for AI Workloads',
        'type' => 'post',
        'tags' => array( 'hero', 'feature', 'recommended' ),
        'content' => 'This content is selected for the homepage hero section and acts as a product spotlight.',
    ),
    array(
        'title' => 'Feature Highlights for Search and Governance',
        'type' => 'page',
        'tags' => array( 'feature', 'documentation' ),
        'content' => 'A feature page that demonstrates the product and documentation selection logic in the theme.',
    ),
    array(
        'title' => 'Documentation: Getting Started with ThemisDB',
        'type' => 'page',
        'tags' => array( 'documentation', 'recommended' ),
        'content' => 'A sample documentation page. This is used for docs grid card selection.',
    ),
    array(
        'title' => 'Pricing for Teams and Enterprise',
        'type' => 'page',
        'tags' => array( 'pricing', 'edition' ),
        'content' => 'This page simulates a pricing or plan selection item on the front page.',
    ),
    array(
        'title' => 'Downloads: Docker and Binary Packages',
        'type' => 'page',
        'tags' => array( 'download', 'edition' ),
        'content' => 'Sample download package page for the downloads section.',
    ),
    array(
        'title' => 'Community Meetup in San Francisco',
        'type' => 'post',
        'tags' => array( 'event', 'conference', 'recommended' ),
        'content' => 'Example event content for conference announcements and event highlights.',
    ),
    array(
        'title' => 'Security Advisory: Critical Update',
        'type' => 'post',
        'tags' => array( 'security', 'announcement' ),
        'content' => 'A critical security update example used to validate announcement and security filtering.',
    ),
    array(
        'title' => 'Benchmark: Query Performance Under Load',
        'type' => 'post',
        'tags' => array( 'benchmark', 'recommended' ),
        'content' => 'Example benchmark article used for benchmark-grid content queries.',
    ),
    array(
        'title' => 'Customer Story: Enterprise Deployment',
        'type' => 'post',
        'tags' => array( 'case-study', 'recommended' ),
        'content' => 'Demo case study content for the customer story section.',
    ),
    array(
        'title' => 'Trial Setup for New Teams',
        'type' => 'page',
        'tags' => array( 'trial', 'download' ),
        'content' => 'Sample trial content that can be surfaced in a CTA or trial section.',
    ),
    array(
        'title' => 'Support Portal: Common Questions',
        'type' => 'page',
        'tags' => array( 'support', 'documentation' ),
        'content' => 'Support-related content for support portal selection inside the site.',
    ),
    array(
        'title' => 'Internal Planning Notes',
        'type' => 'post',
        'tags' => array( 'restricted', 'internal' ),
        'content' => 'This page is intentionally restricted and should not appear in public selection queries.',
    ),
);

foreach ( $demo_items as $item ) {
    $created = themisdb_demo_ensure_post( $item['title'], $item['tags'], $item['type'], $item['content'] );
    if ( $created ) {
        echo "Created: {$item['title']} ({$item['type']})\n";
    }
}

echo "Demo tag setup complete.\n";
