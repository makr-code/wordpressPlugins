<?php
/**
 * Script to create landing pages for Hero Local Spotlight sections
 * Run: php -r "include 'create-landing-pages.php'; create_hero_landing_pages();"
 * Or place in wp-content/plugins and activate as plugin
 */

// Load WordPress
require_once __DIR__ . '/wp-local-env/wordpress/wp-load.php';

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

    $created = [];
    $skipped = [];

    foreach ( $pages as $page_data ) {
        // Check if page already exists
        $existing = get_page_by_path( $page_data['slug'] );
        
        if ( $existing ) {
            $skipped[] = $page_data['title'];
            echo "⊘ {$page_data['title']} existiert bereits (ID: {$existing->ID})\n";
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

        if ( is_wp_error( $page_id ) ) {
            echo "✗ Fehler beim Erstellen von {$page_data['title']}: {$page_id->get_error_message()}\n";
        } else {
            $created[] = $page_data['title'];
            echo "✓ {$page_data['title']} erstellt (ID: {$page_id}, URL: /{$page_data['slug']}/)\n";
        }
    }

    echo "\n--- Zusammenfassung ---\n";
    echo "Erstellt: " . count( $created ) . " Seiten\n";
    if ( $created ) {
        echo "  - " . implode( "\n  - ", $created ) . "\n";
    }
    
    echo "Übersprungen: " . count( $skipped ) . " Seiten (bereits vorhanden)\n";
    if ( $skipped ) {
        echo "  - " . implode( "\n  - ", $skipped ) . "\n";
    }
}

// Run the function
create_hero_landing_pages();
