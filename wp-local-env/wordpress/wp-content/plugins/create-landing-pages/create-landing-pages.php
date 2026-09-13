<?php
/**
 * Plugin Name: Landing Pages Creator
 * Plugin URI: #
 * Description: Erstellt Landing Pages für die Hero Local Spotlight Sektion
 * Version: 1.0.0
 * Author: Development Team
 * License: MIT
 * Text Domain: landing-pages-creator
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Create landing pages on plugin activation
 */
function create_hero_landing_pages() {
    $pages = [
        [
            'title' => 'Blog',
            'slug' => 'blog',
            'content' => 'Hier kommen die neuesten Artikel und Blog-Beiträge zum ThemisDB-Projekt.',
            'description' => 'Die aktuellen Blog-Beiträge und News über ThemisDB'
        ],
        [
            'title' => 'Dokumentation',
            'slug' => 'documentation',
            'content' => 'Umfassende Dokumentation für ThemisDB. Erfahren Sie alles, was Sie zum Bauen, Bereitstellen und Skalieren mit ThemisDB benötigen.',
            'description' => 'Offizielle ThemisDB Dokumentation'
        ],
        [
            'title' => 'Features',
            'slug' => 'features',
            'content' => 'Entdecken Sie die Features von ThemisDB. Eine Datenbank mit allen Funktionen, die Sie für moderne Anwendungen brauchen.',
            'description' => 'Features und Capabilities von ThemisDB'
        ],
        [
            'title' => 'Downloads',
            'slug' => 'downloads',
            'content' => 'Laden Sie ThemisDB herunter. Wählen Sie zwischen Docker, Binaries oder dem Compendium-Bundle.',
            'description' => 'Download-Seite für ThemisDB v3'
        ],
        [
            'title' => 'Newsletter',
            'slug' => 'newsletter',
            'content' => 'Abonnieren Sie unseren Newsletter für die neuesten Updates und Ankündigungen zu ThemisDB.',
            'description' => 'Newsletter-Anmeldung für ThemisDB Updates'
        ],
        [
            'title' => 'Docker',
            'slug' => 'docker',
            'content' => 'Docker Hub - Offizielle Docker-Images für ThemisDB. Schnell und einfach zu deployen.',
            'description' => 'Docker-Deployment für ThemisDB'
        ]
    ];

    $created_count = 0;
    $skipped_count = 0;

    foreach ( $pages as $page_data ) {
        // Check if page already exists
        $existing = get_page_by_path( $page_data['slug'] );
        
        if ( $existing ) {
            $skipped_count++;
            continue;
        }

        $page_id = wp_insert_post( [
            'post_title' => $page_data['title'],
            'post_content' => $page_data['content'],
            'post_name' => $page_data['slug'],
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_excerpt' => $page_data['description'],
        ] );

        if ( ! is_wp_error( $page_id ) ) {
            $created_count++;
        }
    }

    // Store the result in an option
    update_option( 'landing_pages_created', [
        'created' => $created_count,
        'skipped' => $skipped_count,
        'timestamp' => current_time( 'mysql' ),
    ] );
}

// Hook to create pages on plugin activation
register_activation_hook( __FILE__, 'create_hero_landing_pages' );

// Admin notice showing status
add_action( 'admin_notices', function() {
    $result = get_option( 'landing_pages_created' );
    
    if ( $result && isset( $result['created'] ) && $result['created'] > 0 ) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>Landing Pages Creator:</strong> <?php echo esc_html( $result['created'] ); ?> Seite(n) erstellt.</p>
        </div>
        <?php
    }
} );
