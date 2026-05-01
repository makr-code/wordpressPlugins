<?php
/**
 * ThemisDB Theme v3 - Minimal Generic Functions
 *
 * @package ThemisDB_V3
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'THEMISDB_V3_VERSION', '3.1.0' );

add_action( 'after_setup_theme', 'themisdb_v3_setup' );
function themisdb_v3_setup() {
    load_theme_textdomain( 'themisdb-v3', get_template_directory() . '/languages' );

    add_theme_support( 'automatic-feed-links' );
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'align-wide' );
    add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
    add_theme_support( 'block-templates' );
    add_theme_support( 'editor-styles' );

    add_theme_support(
        'custom-logo',
        array(
            'height'      => 96,
            'width'       => 320,
            'flex-height' => true,
            'flex-width'  => true,
        )
    );

    add_editor_style( 'style.css' );

    register_nav_menus(
        array(
            'primary' => __( 'Primary Navigation', 'themisdb-v3' ),
            'footer'  => __( 'Footer Navigation', 'themisdb-v3' ),
        )
    );
}

add_action( 'init', 'themisdb_v3_register_shortcodes', 15 );
/**
 * Register frontend shortcodes used by template parts.
 */
function themisdb_v3_register_shortcodes() {
    add_shortcode( 'themisdb_v3_breadcrumbs', 'themisdb_v3_render_breadcrumbs_shortcode' );
    add_shortcode( 'themisdb_v3_read_time', 'themisdb_v3_render_read_time_shortcode' );
    add_shortcode( 'themisdb_v3_modified_date', 'themisdb_v3_render_modified_date_shortcode' );
    add_shortcode( 'themisdb_v3_authors_compact', 'themisdb_v3_render_authors_compact_shortcode' );
    add_shortcode( 'themisdb_v3_author_bio', 'themisdb_v3_render_author_bio_shortcode' );
    add_shortcode( 'themisdb_v3_author_stats', 'themisdb_v3_render_author_stats_shortcode' );
    add_shortcode( 'themisdb_v3_author_focus', 'themisdb_v3_render_author_focus_shortcode' );
}

/**
 * Return normalized author items for a post (Co-Authors Plus compatible).
 *
 * @param int  $post_id     Post ID.
 * @param int  $avatar_size Avatar size in px.
 * @param bool $linked      Whether author names should be linked.
 * @return array<int,array<string,mixed>>
 */
function themisdb_v3_get_post_author_items( int $post_id, int $avatar_size = 40, bool $linked = true ): array {
    $post_id = absint( $post_id );
    if ( $post_id <= 0 ) {
        return array();
    }

    $items = array();

    if ( function_exists( 'get_coauthors' ) ) {
        $coauthors = (array) get_coauthors( $post_id );
        foreach ( $coauthors as $author ) {
            $id    = isset( $author->ID ) ? absint( $author->ID ) : 0;
            $name  = isset( $author->display_name ) ? trim( (string) $author->display_name ) : '';
            $slug  = isset( $author->user_nicename ) ? sanitize_title( (string) $author->user_nicename ) : '';
            $url   = '';
            $bio   = isset( $author->description ) ? trim( (string) $author->description ) : '';
            $avatar = '';

            if ( $linked && '' !== $slug ) {
                $url = get_author_posts_url( $id, $slug );
            }

            if ( function_exists( 'coauthors_get_avatar' ) ) {
                $avatar = (string) coauthors_get_avatar(
                    $author,
                    $avatar_size,
                    '',
                    $name,
                    array( 'class' => 'tv3-card-author-avatar' )
                );
            } elseif ( $id > 0 ) {
                $avatar = get_avatar(
                    $id,
                    $avatar_size,
                    '',
                    $name,
                    array( 'class' => 'tv3-card-author-avatar' )
                );
            }

            if ( '' !== $name ) {
                $items[] = array(
                    'id'     => $id,
                    'name'   => $name,
                    'slug'   => $slug,
                    'url'    => $url,
                    'avatar' => $avatar,
                    'bio'    => $bio,
                );
            }
        }
    }

    if ( ! empty( $items ) ) {
        return $items;
    }

    $author_id = (int) get_post_field( 'post_author', $post_id );
    if ( $author_id <= 0 ) {
        return array();
    }

    $name = trim( (string) get_the_author_meta( 'display_name', $author_id ) );
    if ( '' === $name ) {
        return array();
    }

    $slug = trim( (string) get_the_author_meta( 'user_nicename', $author_id ) );
    $url  = ( $linked && '' !== $slug ) ? get_author_posts_url( $author_id, $slug ) : '';

    return array(
        array(
            'id'     => $author_id,
            'name'   => $name,
            'slug'   => $slug,
            'url'    => $url,
            'avatar' => get_avatar(
                $author_id,
                $avatar_size,
                '',
                $name,
                array( 'class' => 'tv3-card-author-avatar' )
            ),
            'bio'    => trim( (string) get_the_author_meta( 'description', $author_id ) ),
        ),
    );
}

/**
 * Render compact author output with multiple names/avatars.
 *
 * @param array<string,string> $atts Shortcode attributes.
 * @return string
 */
function themisdb_v3_render_authors_compact_shortcode( array $atts = array() ): string {
    $atts = shortcode_atts(
        array(
            'size'         => '40',
            'linked'       => '1',
            'show_avatars' => '1',
        ),
        $atts,
        'themisdb_v3_authors_compact'
    );

    $post_id      = (int) get_the_ID();
    $avatar_size  = max( 24, min( 80, absint( $atts['size'] ) ) );
    $linked       = '0' !== (string) $atts['linked'];
    $show_avatars = '0' !== (string) $atts['show_avatars'];
    $authors      = themisdb_v3_get_post_author_items( $post_id, $avatar_size, $linked );

    if ( empty( $authors ) ) {
        return '';
    }

    $names = array();
    foreach ( $authors as $author ) {
        $name = (string) ( $author['name'] ?? '' );
        if ( '' === $name ) {
            continue;
        }
        $url = (string) ( $author['url'] ?? '' );
        if ( '' !== $url && $linked ) {
            $names[] = '<a href="' . esc_url( $url ) . '">' . esc_html( $name ) . '</a>';
        } else {
            $names[] = esc_html( $name );
        }
    }

    if ( empty( $names ) ) {
        return '';
    }

    $avatars_html = '';
    if ( $show_avatars ) {
        $avatar_nodes = array();
        foreach ( array_slice( $authors, 0, 3 ) as $author ) {
            $avatar = (string) ( $author['avatar'] ?? '' );
            if ( '' === $avatar ) {
                continue;
            }
            $avatar_nodes[] = '<span class="tv3-authors-compact-avatar">' . $avatar . '</span>';
        }
        if ( ! empty( $avatar_nodes ) ) {
            $avatars_html = '<span class="tv3-authors-compact-avatars">' . implode( '', $avatar_nodes ) . '</span>';
        }
    }

    return '<span class="tv3-authors-compact">'
        . $avatars_html
        . '<span class="tv3-authors-compact-names">' . implode( '<span class="tv3-post-meta-sep">·</span>', $names ) . '</span>'
        . '</span>';
}

/**
 * Resolve author ID for author archive and related contexts.
 *
 * @return int
 */
function themisdb_v3_get_context_author_id(): int {
    $author_id = absint( get_query_var( 'author' ) );
    if ( $author_id > 0 ) {
        return $author_id;
    }

    $author_name = sanitize_title( (string) get_query_var( 'author_name' ) );
    if ( '' !== $author_name ) {
        $user = get_user_by( 'slug', $author_name );
        if ( $user instanceof WP_User ) {
            return (int) $user->ID;
        }
    }

    if ( is_singular() ) {
        return (int) get_post_field( 'post_author', get_the_ID() );
    }

    return 0;
}

/**
 * Render compact author bio with fallback from user profile data.
 *
 * @return string
 */
function themisdb_v3_render_author_bio_shortcode(): string {
    if ( is_singular() ) {
        $post_id = (int) get_the_ID();
        $authors = themisdb_v3_get_post_author_items( $post_id, 40, false );
        if ( count( $authors ) > 1 ) {
            $names = array_map(
                static function ( $item ) {
                    return (string) ( $item['name'] ?? '' );
                },
                $authors
            );
            $names = array_values( array_filter( array_map( 'trim', $names ) ) );
            if ( ! empty( $names ) ) {
                return '<p class="tv3-author-fact-text">Dieser Beitrag wurde gemeinsam erstellt von ' . esc_html( implode( ', ', $names ) ) . '.</p>';
            }
        }

        if ( 1 === count( $authors ) && ! empty( $authors[0]['bio'] ) ) {
            $short = wp_trim_words( wp_strip_all_tags( (string) $authors[0]['bio'] ), 34, ' ...' );
            return '<p class="tv3-author-fact-text">' . esc_html( $short ) . '</p>';
        }
    }

    $author_id = themisdb_v3_get_context_author_id();
    if ( $author_id <= 0 ) {
        return '<p class="tv3-author-fact-text">Dieses Autorenprofil wird aktuell aufgebaut.</p>';
    }

    $description = trim( (string) get_the_author_meta( 'description', $author_id ) );
    if ( '' !== $description ) {
        $short = wp_trim_words( wp_strip_all_tags( $description ), 34, ' ...' );
        return '<p class="tv3-author-fact-text">' . esc_html( $short ) . '</p>';
    }

    $display_name = (string) get_the_author_meta( 'display_name', $author_id );
    $userdata     = get_userdata( $author_id );
    $roles        = $userdata instanceof WP_User ? array_values( (array) $userdata->roles ) : array();
    $role_label   = '';
    if ( ! empty( $roles ) ) {
        $role_key = (string) $roles[0];
        $wp_roles = wp_roles();
        if ( isset( $wp_roles->roles[ $role_key ]['name'] ) ) {
            $role_label = (string) $wp_roles->roles[ $role_key ]['name'];
        }
    }

    $fallback = sprintf(
        '%s schreibt regelmaessig Fachbeitraege. Das Profil basiert auf den WordPress-Autoreninformationen%s.',
        $display_name ? $display_name : 'Dieser Autor',
        $role_label ? ' (' . $role_label . ')' : ''
    );

    return '<p class="tv3-author-fact-text">' . esc_html( $fallback ) . '</p>';
}

/**
 * Render author publication counts.
 *
 * @return string
 */
function themisdb_v3_render_author_stats_shortcode(): string {
    $author_id = themisdb_v3_get_context_author_id();
    if ( $author_id <= 0 ) {
        return '<p class="tv3-author-fact-text">Noch keine Publikationsdaten verfuegbar.</p>';
    }

    $post_types = array( 'post', 'page', 'pod_episode' );
    $counts     = array();
    foreach ( $post_types as $type ) {
        $counts[ $type ] = (int) count_user_posts( $author_id, $type, true );
    }

    $total = $counts['post'] + $counts['page'] + $counts['pod_episode'];
    if ( $total <= 0 ) {
        return '<p class="tv3-author-fact-text">Noch keine veroeffentlichten Inhalte.</p>';
    }

    $parts = array();
    if ( $counts['post'] > 0 ) {
        $parts[] = sprintf( _n( '%d Beitrag', '%d Beitraege', $counts['post'], 'themisdb-v3' ), $counts['post'] );
    }
    if ( $counts['page'] > 0 ) {
        $parts[] = sprintf( _n( '%d Seite', '%d Seiten', $counts['page'], 'themisdb-v3' ), $counts['page'] );
    }
    if ( $counts['pod_episode'] > 0 ) {
        $parts[] = sprintf( _n( '%d Episode', '%d Episoden', $counts['pod_episode'], 'themisdb-v3' ), $counts['pod_episode'] );
    }

    return '<p class="tv3-author-fact-text">' . esc_html( implode( ' · ', $parts ) ) . '</p>';
}

/**
 * Render author focus topics based on top categories.
 *
 * @return string
 */
function themisdb_v3_render_author_focus_shortcode(): string {
    $author_id = themisdb_v3_get_context_author_id();
    if ( $author_id <= 0 ) {
        return '<p class="tv3-author-fact-text">Themenschwerpunkte werden laufend ergaenzt.</p>';
    }

    $posts = get_posts(
        array(
            'author'         => $author_id,
            'post_type'      => array( 'post', 'pod_episode' ),
            'post_status'    => 'publish',
            'posts_per_page' => 60,
            'fields'         => 'ids',
            'orderby'        => 'date',
            'order'          => 'DESC',
        )
    );

    if ( empty( $posts ) ) {
        return '<p class="tv3-author-fact-text">Themenschwerpunkte werden laufend ergaenzt.</p>';
    }

    $bucket = array();
    foreach ( $posts as $post_id ) {
        $terms = wp_get_post_terms( (int) $post_id, 'category' );
        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            continue;
        }
        foreach ( $terms as $term ) {
            $name = trim( (string) $term->name );
            if ( '' === $name ) {
                continue;
            }
            if ( ! isset( $bucket[ $name ] ) ) {
                $bucket[ $name ] = 0;
            }
            $bucket[ $name ]++;
        }
    }

    if ( empty( $bucket ) ) {
        return '<p class="tv3-author-fact-text">Themenschwerpunkte werden laufend ergaenzt.</p>';
    }

    arsort( $bucket );
    $top = array_slice( array_keys( $bucket ), 0, 3 );
    return '<p class="tv3-author-fact-text">' . esc_html( implode( ' · ', $top ) ) . '</p>';
}

/**
 * Render the last-modified date — only when it differs from the publish date.
 *
 * Attributes:
 *   format  – PHP date format, default 'j. F Y'
 *   label   – prefix label, default 'Stand:'
 *
 * @param array<string,string> $atts Shortcode attributes.
 * @return string HTML or empty string.
 */
function themisdb_v3_render_modified_date_shortcode( array $atts = array() ): string {
    $atts = shortcode_atts(
        array(
            'format' => 'j. F Y',
            'label'  => 'Stand:',
        ),
        $atts,
        'themisdb_v3_modified_date'
    );

    $post_id       = get_the_ID();
    $published_raw = get_the_date( 'Y-m-d', $post_id );
    $modified_raw  = get_the_modified_date( 'Y-m-d', $post_id );

    if ( ! $modified_raw || $modified_raw === $published_raw ) {
        return '';
    }

    $format        = sanitize_text_field( $atts['format'] );
    $label         = sanitize_text_field( $atts['label'] );
    $modified_disp = get_the_modified_date( $format, $post_id );
    $modified_iso  = get_the_modified_date( 'c', $post_id );

    return sprintf(
        '<span class="tv3-page-meta-sep tv3-page-meta-sep-modified">·</span>'
        . '<span class="tv3-page-meta-modified">'
        . '<span class="tv3-page-meta-label">%s</span>'
        . '<time class="tv3-page-meta-modified-date" datetime="%s">%s</time>'
        . '</span>',
        esc_html( $label ),
        esc_attr( (string) $modified_iso ),
        esc_html( (string) $modified_disp )
    );
}

/**
 * Calculate reading time in minutes for plain text content.
 *
 * @param string $text Source text.
 * @param int    $wpm  Words per minute.
 * @return int
 */
function themisdb_v3_calculate_reading_time_minutes( $text, $wpm = 220 ) {
    $clean_text = trim( wp_strip_all_tags( (string) $text ) );
    if ( '' === $clean_text ) {
        return 1;
    }

    $word_count = (int) str_word_count( $clean_text );
    if ( $word_count <= 0 ) {
        return 1;
    }

    return max( 1, (int) ceil( $word_count / max( 120, (int) $wpm ) ) );
}

/**
 * Resolve reading speed by post type.
 *
 * @param int $post_id Post ID.
 * @return int
 */
function themisdb_v3_get_reading_speed_wpm_for_post( $post_id = 0 ) {
    $post_id   = (int) ( $post_id ?: get_the_ID() );
    $post_type = $post_id > 0 ? (string) get_post_type( $post_id ) : '';

    $defaults = array(
        'post'        => 230,
        'page'        => 210,
        'tutorial'    => 190,
        'screencast'  => 190,
        'pod_episode' => 210,
    );

    $wpm = isset( $defaults[ $post_type ] ) ? (int) $defaults[ $post_type ] : 220;
    return max( 120, $wpm );
}

/**
 * Build a localized reading time label for a post.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function themisdb_v3_get_read_time_label( $post_id = 0 ) {
    $post_id = (int) ( $post_id ?: get_the_ID() );
    if ( $post_id <= 0 ) {
        return __( '1 Min. Lesezeit', 'themisdb-v3' );
    }

    $content = (string) get_post_field( 'post_content', $post_id );
    if ( '' === trim( $content ) ) {
        $content = (string) get_post_field( 'post_excerpt', $post_id );
    }

    $minutes = themisdb_v3_calculate_reading_time_minutes( $content, themisdb_v3_get_reading_speed_wpm_for_post( $post_id ) );
    return sprintf(
        _n( '%d Min. Lesezeit', '%d Min. Lesezeit', $minutes, 'themisdb-v3' ),
        $minutes
    );
}

/**
 * Return reading time in minutes for a post.
 *
 * @param int $post_id Post ID.
 * @return int
 */
function themisdb_v3_get_read_time_minutes( $post_id = 0 ) {
    $post_id = (int) ( $post_id ?: get_the_ID() );
    if ( $post_id <= 0 ) {
        return 1;
    }

    $content = (string) get_post_field( 'post_content', $post_id );
    if ( '' === trim( $content ) ) {
        $content = (string) get_post_field( 'post_excerpt', $post_id );
    }

    return themisdb_v3_calculate_reading_time_minutes( $content, themisdb_v3_get_reading_speed_wpm_for_post( $post_id ) );
}

/**
 * Shortcode renderer for read-time output in block template parts.
 *
 * @param array<string,mixed> $atts Shortcode attributes.
 * @return string
 */
function themisdb_v3_render_read_time_shortcode( $atts = array() ) {
    $atts = shortcode_atts(
        array(
            'post_id' => 0,
        ),
        $atts,
        'themisdb_v3_read_time'
    );

    $post_id = (int) $atts['post_id'];
    $label   = themisdb_v3_get_read_time_label( $post_id );
    return '<span class="tv3-read-time">' . esc_html( $label ) . '</span>';
}

/**
 * Render a context-aware breadcrumb trail.
 *
 * @return string
 */
function themisdb_v3_render_breadcrumbs_shortcode() {
    $items = array(
        array(
            'label' => __( 'Startseite', 'themisdb-v3' ),
            'url'   => home_url( '/' ),
        ),
    );

    $current_label = '';

    if ( is_singular() ) {
        $post = get_queried_object();
        if ( $post instanceof WP_Post ) {
            if ( 'page' === $post->post_type ) {
                $ancestors = array_reverse( get_post_ancestors( $post ) );
                foreach ( $ancestors as $ancestor_id ) {
                    $items[] = array(
                        'label' => get_the_title( $ancestor_id ),
                        'url'   => get_permalink( $ancestor_id ),
                    );
                }
            } else {
                $post_type_object = get_post_type_object( $post->post_type );
                if ( $post_type_object && ! empty( $post_type_object->has_archive ) ) {
                    $archive_link = get_post_type_archive_link( $post->post_type );
                    if ( $archive_link ) {
                        $items[] = array(
                            'label' => (string) $post_type_object->labels->name,
                            'url'   => $archive_link,
                        );
                    }
                }
            }

            $current_label = get_the_title( $post );
        }
    } elseif ( is_category() || is_tag() || is_tax() ) {
        $term = get_queried_object();
        if ( $term instanceof WP_Term ) {
            if ( 'category' !== $term->taxonomy && 'post_tag' !== $term->taxonomy ) {
                $taxonomy = get_taxonomy( $term->taxonomy );
                if ( $taxonomy && ! empty( $taxonomy->labels->name ) ) {
                    $items[] = array(
                        'label' => (string) $taxonomy->labels->name,
                        'url'   => '',
                    );
                }
            }
            $current_label = single_term_title( '', false );
        }
    } elseif ( is_post_type_archive() ) {
        $current_label = post_type_archive_title( '', false );
    } elseif ( is_search() ) {
        $current_label = sprintf( __( 'Suche: %s', 'themisdb-v3' ), get_search_query() );
    } elseif ( is_404() ) {
        $current_label = __( 'Nicht gefunden', 'themisdb-v3' );
    } else {
        $current_label = wp_get_document_title();
    }

    $current_label = wp_strip_all_tags( (string) $current_label );
    if ( '' === trim( $current_label ) ) {
        $current_label = __( 'Aktuelle Seite', 'themisdb-v3' );
    }

    $html = '<nav class="tv3-breadcrumbs" aria-label="' . esc_attr__( 'Breadcrumb-Navigation', 'themisdb-v3' ) . '">';
    $html .= '<ol class="tv3-breadcrumbs-list" itemscope itemtype="https://schema.org/BreadcrumbList">';

    $position = 1;
    foreach ( $items as $item ) {
        $label = isset( $item['label'] ) ? trim( (string) $item['label'] ) : '';
        $url   = isset( $item['url'] ) ? (string) $item['url'] : '';
        if ( '' === $label ) {
            continue;
        }

        $html .= '<li class="tv3-breadcrumbs-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
        if ( '' !== $url ) {
            $html .= '<a href="' . esc_url( $url ) . '" itemprop="item"><span itemprop="name">' . esc_html( $label ) . '</span></a>';
        } else {
            $html .= '<span itemprop="name">' . esc_html( $label ) . '</span>';
        }
        $html .= '<meta itemprop="position" content="' . (int) $position . '" />';
        $html .= '</li>';
        $position++;
    }

    $html .= '<li class="tv3-breadcrumbs-item is-current" aria-current="page" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
    $html .= '<span itemprop="name">' . esc_html( $current_label ) . '</span>';
    $html .= '<meta itemprop="position" content="' . (int) $position . '" />';
    $html .= '</li>';
    $html .= '</ol></nav>';

    return $html;
}

add_action( 'init', 'themisdb_v3_sync_navigation_template_parts', 20 );
/**
 * Keep block navigation refs in header/footer aligned with configured menu locations.
 */
function themisdb_v3_sync_navigation_template_parts() {
    if ( wp_doing_ajax() || wp_is_json_request() ) {
        return;
    }

    $signature      = themisdb_v3_get_navigation_sync_signature();
    $option_name    = 'themisdb_v3_nav_sync_signature';
    $last_signature = (string) get_option( $option_name, '' );

    if ( $signature === $last_signature ) {
        return;
    }

    if ( themisdb_v3_apply_navigation_template_part_sync() ) {
        update_option( $option_name, $signature, false );
    }
}

/**
 * Build a compact signature so sync only runs when menu assignments/content changed.
 *
 * @return string
 */
function themisdb_v3_get_navigation_sync_signature() {
    $locations = (array) get_theme_mod( 'nav_menu_locations', array() );
    $parts     = array( get_stylesheet() );

    foreach ( array( 'primary', 'footer' ) as $location ) {
        $term_id = isset( $locations[ $location ] ) ? (int) $locations[ $location ] : 0;
        $parts[] = $location . ':' . $term_id;

        if ( $term_id <= 0 ) {
            continue;
        }

        $items = wp_get_nav_menu_items( $term_id, array( 'update_post_term_cache' => false ) );
        if ( ! is_array( $items ) ) {
            continue;
        }

        foreach ( $items as $item ) {
            $parts[] = implode(
                ':',
                array(
                    (string) $item->db_id,
                    (string) (int) $item->menu_order,
                    (string) $item->object,
                    (string) $item->object_id,
                    (string) $item->url,
                )
            );
        }
    }

    return md5( implode( '|', $parts ) );
}

/**
 * Sync classic menu locations into block navigation refs used by template parts.
 *
 * @return bool
 */
function themisdb_v3_apply_navigation_template_part_sync() {
    $primary_navigation_id = themisdb_v3_ensure_navigation_post_from_location( 'primary', 'TV3 Primary Navigation' );
    $footer_navigation_id  = themisdb_v3_ensure_navigation_post_from_location( 'footer', 'TV3 Footer Navigation' );

    if ( $primary_navigation_id <= 0 && $footer_navigation_id <= 0 ) {
        return false;
    }

    if ( $primary_navigation_id > 0 ) {
        themisdb_v3_sync_template_part_navigation_ref( 'header', $primary_navigation_id, false );
    }

    if ( $footer_navigation_id > 0 ) {
        themisdb_v3_sync_template_part_navigation_ref( 'footer', $footer_navigation_id, true );
    }

    return true;
}

/**
 * Create or update a wp_navigation post from a classic location menu.
 *
 * @param string $location Location slug.
 * @param string $title    Navigation post title.
 * @return int
 */
function themisdb_v3_ensure_navigation_post_from_location( $location, $title ) {
    if ( ! class_exists( 'WP_Classic_To_Block_Menu_Converter' ) ) {
        $converter_file = ABSPATH . WPINC . '/class-wp-classic-to-block-menu-converter.php';
        if ( file_exists( $converter_file ) ) {
            require_once $converter_file;
        }
    }

    if ( ! class_exists( 'WP_Classic_To_Block_Menu_Converter' ) ) {
        return 0;
    }

    $locations = (array) get_theme_mod( 'nav_menu_locations', array() );
    $term_id   = isset( $locations[ $location ] ) ? (int) $locations[ $location ] : 0;
    if ( $term_id <= 0 ) {
        return 0;
    }

    $menu = wp_get_nav_menu_object( $term_id );
    if ( ! $menu ) {
        return 0;
    }

    $content = WP_Classic_To_Block_Menu_Converter::convert( $menu );
    if ( is_wp_error( $content ) ) {
        return 0;
    }

    $option_name  = 'themisdb_v3_nav_post_id_' . sanitize_key( $location );
    $existing_id  = (int) get_option( $option_name, 0 );
    $existing_nav = $existing_id > 0 ? get_post( $existing_id ) : null;

    if ( ! $existing_nav || 'wp_navigation' !== $existing_nav->post_type ) {
        $found = get_posts(
            array(
                'post_type'      => 'wp_navigation',
                'post_status'    => array( 'publish', 'draft' ),
                'title'          => $title,
                'numberposts'    => 1,
                'fields'         => 'ids',
                'suppress_filters' => false,
            )
        );
        $existing_id = ! empty( $found ) ? (int) $found[0] : 0;
    }

    $postarr = array(
        'post_type'    => 'wp_navigation',
        'post_status'  => 'publish',
        'post_title'   => $title,
        'post_content' => (string) $content,
    );

    if ( $existing_id > 0 ) {
        $postarr['ID'] = $existing_id;
        $result        = wp_update_post( $postarr, true );
    } else {
        $result = wp_insert_post( $postarr, true );
    }

    if ( is_wp_error( $result ) || $result <= 0 ) {
        return 0;
    }

    update_option( $option_name, (int) $result, false );

    return (int) $result;
}

/**
 * Find a theme template part post by slug.
 *
 * @param string $slug Template part slug.
 * @return WP_Post|null
 */
function themisdb_v3_get_theme_template_part_post( $slug ) {
    $query = new WP_Query(
        array(
            'post_type'      => 'wp_template_part',
            'post_status'    => array( 'publish', 'draft' ),
            'name'           => sanitize_title( $slug ),
            'posts_per_page' => 1,
            'tax_query'      => array(
                array(
                    'taxonomy' => 'wp_theme',
                    'field'    => 'name',
                    'terms'    => get_stylesheet(),
                ),
            ),
        )
    );

    return ! empty( $query->posts ) ? $query->posts[0] : null;
}

/**
 * Bind navigation block refs for a template part.
 *
 * @param string $slug         Template part slug.
 * @param int    $ref_id       wp_navigation post ID.
 * @param bool   $apply_to_all Whether to bind all navigation blocks.
 */
function themisdb_v3_sync_template_part_navigation_ref( $slug, $ref_id, $apply_to_all ) {
    $template_part = themisdb_v3_get_theme_template_part_post( $slug );
    if ( ! $template_part || empty( $template_part->post_content ) ) {
        return;
    }

    $blocks      = parse_blocks( $template_part->post_content );
    $bound_count = 0;

    themisdb_v3_bind_navigation_ref_to_blocks( $blocks, (int) $ref_id, (bool) $apply_to_all, $bound_count );

    if ( $bound_count <= 0 ) {
        return;
    }

    $serialized = serialize_blocks( $blocks );
    if ( $serialized === $template_part->post_content ) {
        return;
    }

    wp_update_post(
        array(
            'ID'           => (int) $template_part->ID,
            'post_content' => $serialized,
        )
    );
}

/**
 * Recursively attach a wp_navigation reference to core/navigation blocks.
 *
 * @param array<int,array<string,mixed>> $blocks      Parsed blocks.
 * @param int                            $ref_id      wp_navigation post ID.
 * @param bool                           $apply_to_all Whether to bind all matches.
 * @param int                            $bound_count Number of updated blocks.
 */
function themisdb_v3_bind_navigation_ref_to_blocks( array &$blocks, $ref_id, $apply_to_all, &$bound_count ) {
    foreach ( $blocks as &$block ) {
        if ( isset( $block['blockName'] ) && 'core/navigation' === $block['blockName'] ) {
            if ( $apply_to_all || 0 === $bound_count ) {
                if ( ! isset( $block['attrs'] ) || ! is_array( $block['attrs'] ) ) {
                    $block['attrs'] = array();
                }

                $block['attrs']['ref'] = (int) $ref_id;

                // Ensure frontend uses referenced navigation content consistently.
                $block['innerBlocks']  = array();
                $block['innerHTML']    = '';
                $block['innerContent'] = array();

                $bound_count++;
            }
        }

        if ( ! empty( $block['innerBlocks'] ) ) {
            themisdb_v3_bind_navigation_ref_to_blocks( $block['innerBlocks'], $ref_id, $apply_to_all, $bound_count );
        }
    }
}

add_filter( 'render_block_data', 'themisdb_v3_bind_navigation_ref_for_file_parts', 10, 2 );
/**
 * Ensure file-based navigation blocks resolve to configured menus without DB part overrides.
 *
 * @param array<string,mixed> $parsed_block Parsed block data.
 * @param array<string,mixed> $source_block Source block data.
 * @return array<string,mixed>
 */
function themisdb_v3_bind_navigation_ref_for_file_parts( $parsed_block, $source_block ) {
    if ( empty( $parsed_block['blockName'] ) || 'core/navigation' !== $parsed_block['blockName'] ) {
        return $parsed_block;
    }

    if ( ! empty( $parsed_block['attrs']['ref'] ) ) {
        return $parsed_block;
    }

    $location = isset( $parsed_block['attrs']['__unstableLocation'] ) ? (string) $parsed_block['attrs']['__unstableLocation'] : '';
    if ( ! in_array( $location, array( 'primary', 'footer' ), true ) ) {
        return $parsed_block;
    }

    $ref = themisdb_v3_get_navigation_post_id_for_location( $location );
    if ( $ref <= 0 ) {
        return $parsed_block;
    }

    if ( ! isset( $parsed_block['attrs'] ) || ! is_array( $parsed_block['attrs'] ) ) {
        $parsed_block['attrs'] = array();
    }

    $parsed_block['attrs']['ref'] = (int) $ref;

    return $parsed_block;
}

/**
 * Get an existing navigation post ID for a location without mutating content at render time.
 *
 * @param string $location Location slug.
 * @return int
 */
function themisdb_v3_get_navigation_post_id_for_location( $location ) {
    $option_name = 'themisdb_v3_nav_post_id_' . sanitize_key( $location );
    $post_id     = (int) get_option( $option_name, 0 );

    if ( $post_id > 0 ) {
        $post = get_post( $post_id );
        if ( $post && 'wp_navigation' === $post->post_type ) {
            return $post_id;
        }
    }

    $title = 'primary' === $location ? 'TV3 Primary Navigation' : 'TV3 Footer Navigation';
    $found = get_posts(
        array(
            'post_type'      => 'wp_navigation',
            'post_status'    => array( 'publish', 'draft' ),
            'title'          => $title,
            'numberposts'    => 1,
            'fields'         => 'ids',
            'suppress_filters' => false,
        )
    );

    return ! empty( $found ) ? (int) $found[0] : 0;
}

add_filter( 'render_block', 'themisdb_v3_rewrite_root_relative_links', 20, 2 );
/**
 * Rewrite root-relative links to proper site URLs for subdirectory installs.
 *
 * @param string              $block_content Rendered block HTML.
 * @param array<string,mixed> $block         Parsed block.
 * @return string
 */
function themisdb_v3_rewrite_root_relative_links( $block_content, $block ) {
    if ( is_admin() || '' === $block_content || false === strpos( $block_content, 'href="/' ) ) {
        return $block_content;
    }

    return (string) preg_replace_callback(
        '/href="\/(?!\/)([^"#]*)"/',
        static function ( $matches ) {
            $path = isset( $matches[1] ) ? (string) $matches[1] : '';
            $url  = '' === $path ? home_url( '/' ) : home_url( '/' . ltrim( $path, '/' ) );
            return 'href="' . esc_url( $url ) . '"';
        },
        $block_content
    );
}

add_filter( 'the_content', 'themisdb_v3_ensure_image_alt_text', 20 );
/**
 * Ensure rendered post content images always have a meaningful alt attribute.
 *
 * @param string $content The post content HTML.
 * @return string
 */
function themisdb_v3_ensure_image_alt_text( $content ) {
    $content = (string) $content;
    if ( is_admin() || '' === trim( $content ) || false === stripos( $content, '<img' ) ) {
        return $content;
    }

    $fallback_alt = is_singular() ? wp_strip_all_tags( (string) get_the_title() ) : '';
    if ( '' === trim( $fallback_alt ) ) {
        $fallback_alt = __( 'Artikelbild', 'themisdb-v3' );
    }

    return (string) preg_replace_callback(
        '/<img\b[^>]*>/i',
        static function ( $matches ) use ( $fallback_alt ) {
            $tag = isset( $matches[0] ) ? (string) $matches[0] : '';
            if ( '' === $tag ) {
                return $tag;
            }

            if ( preg_match( '/\balt\s*=\s*(["\'])(.*?)\1/i', $tag, $alt_match ) ) {
                $existing_alt = html_entity_decode( (string) ( $alt_match[2] ?? '' ), ENT_QUOTES, 'UTF-8' );
                if ( '' !== trim( wp_strip_all_tags( $existing_alt ) ) ) {
                    return $tag;
                }

                $replacement_alt = 'alt="' . esc_attr( $fallback_alt ) . '"';
                return (string) preg_replace( '/\balt\s*=\s*(["\']).*?\1/i', $replacement_alt, $tag, 1 );
            }

            $alt_attr = ' alt="' . esc_attr( $fallback_alt ) . '"';
            if ( false !== strpos( $tag, '/>' ) ) {
                return str_replace( '/>', $alt_attr . ' />', $tag );
            }

            return str_replace( '>', $alt_attr . '>', $tag );
        },
        $content
    );
}

add_action( 'wp_enqueue_scripts', 'themisdb_v3_enqueue_assets' );

/**
 * Determine if the reading progress UI should be shown for current request.
 *
 * @return bool
 */
function themisdb_v3_should_show_reading_progress() {
    return is_singular() || is_home() || is_front_page() || is_archive() || is_search();
}

function themisdb_v3_enqueue_assets() {
    $style_version         = file_exists( get_stylesheet_directory() . '/style.css' ) ? (string) filemtime( get_stylesheet_directory() . '/style.css' ) : THEMISDB_V3_VERSION;
    $color_scheme_file     = get_template_directory() . '/assets/css/color-schemes.css';
    $color_scheme_version  = file_exists( $color_scheme_file ) ? (string) filemtime( $color_scheme_file ) : THEMISDB_V3_VERSION;
    $compact_meta_js_file  = get_template_directory() . '/assets/js/compact-meta-layout.js';
    $compact_meta_js_ver   = file_exists( $compact_meta_js_file ) ? (string) filemtime( $compact_meta_js_file ) : THEMISDB_V3_VERSION;
    $kernthese_js_file     = get_template_directory() . '/assets/js/kernthese-highlight.js';
    $kernthese_js_ver      = file_exists( $kernthese_js_file ) ? (string) filemtime( $kernthese_js_file ) : THEMISDB_V3_VERSION;
    $code_highlight_js_file = get_template_directory() . '/assets/js/code-highlight.js';
    $code_highlight_js_ver  = file_exists( $code_highlight_js_file ) ? (string) filemtime( $code_highlight_js_file ) : THEMISDB_V3_VERSION;

    wp_enqueue_style( 'themisdb-v3-style', get_stylesheet_uri(), array(), $style_version );
    wp_enqueue_style(
        'themisdb-v3-color-schemes',
        get_template_directory_uri() . '/assets/css/color-schemes.css',
        array( 'themisdb-v3-style' ),
        $color_scheme_version
    );
    wp_register_script(
        'themisdb-v3-mixed-cards',
        get_template_directory_uri() . '/assets/js/mixed-cards.js',
        array(),
        THEMISDB_V3_VERSION,
        true
    );
    if ( themisdb_v3_should_show_reading_progress() ) {
        $minutes_total = is_singular() ? themisdb_v3_get_read_time_minutes() : 0;

        wp_enqueue_script(
            'themisdb-v3-reading-progress',
            get_template_directory_uri() . '/assets/js/reading-progress.js',
            array(),
            THEMISDB_V3_VERSION,
            true
        );

        wp_localize_script(
            'themisdb-v3-reading-progress',
            'themisdbV3ReadingProgress',
            array(
                'minutesTotal'       => $minutes_total,
                'labelPattern'       => __( '%1$d%% · %2$d Min. uebrig', 'themisdb-v3' ),
                'labelPatternSimple' => __( '%1$d%% gelesen', 'themisdb-v3' ),
            )
        );
    }

    if ( is_singular() ) {
        wp_enqueue_script(
            'themisdb-v3-compact-meta-layout',
            get_template_directory_uri() . '/assets/js/compact-meta-layout.js',
            array(),
            $compact_meta_js_ver,
            true
        );
        wp_enqueue_script(
            'themisdb-v3-kernthese-highlight',
            get_template_directory_uri() . '/assets/js/kernthese-highlight.js',
            array(),
            $kernthese_js_ver,
            true
        );
        wp_enqueue_script(
            'themisdb-v3-code-highlight',
            get_template_directory_uri() . '/assets/js/code-highlight.js',
            array(),
            $code_highlight_js_ver,
            true
        );
    }
}

add_filter( 'body_class', 'themisdb_v3_apply_color_scheme_body_class' );
/**
 * Apply the selected color scheme class from cookie for first paint consistency.
 *
 * @param string[] $classes Existing body classes.
 * @return string[]
 */
function themisdb_v3_apply_color_scheme_body_class( $classes ) {
    $allowed = array(
        'tv3-scheme-modern-slate',
        'tv3-scheme-themis-original',
        'tv3-scheme-ocean-clean',
        'tv3-scheme-charcoal-cyan',
        'tv3-scheme-paper-editorial',
        'tv3-scheme-high-contrast-clean',
    );

    $selected = 'tv3-scheme-modern-slate';
    if ( isset( $_COOKIE['tv3ColorScheme'] ) ) {
        $candidate = sanitize_key( wp_unslash( (string) $_COOKIE['tv3ColorScheme'] ) );
        if ( in_array( $candidate, $allowed, true ) ) {
            $selected = $candidate;
        }
    }

    foreach ( $allowed as $scheme_class ) {
        $index = array_search( $scheme_class, $classes, true );
        if ( false !== $index ) {
            unset( $classes[ $index ] );
        }
    }

    $classes[] = $selected;
    return array_values( $classes );
}

add_action( 'wp_body_open', 'themisdb_v3_render_skip_link', 5 );
/**
 * Render a global skip link for keyboard and assistive technology users.
 */
function themisdb_v3_render_skip_link() {
    echo '<a class="tv3-skip-link" href="#wp--skip-link--target">' . esc_html__( 'Zum Inhalt springen', 'themisdb-v3' ) . '</a>';
}

add_action( 'wp_body_open', 'themisdb_v3_render_reading_progress_bar', 7 );
/**
 * Render the global reading progress bar shell.
 */
function themisdb_v3_render_reading_progress_bar() {
    if ( ! themisdb_v3_should_show_reading_progress() ) {
        return;
    }

    echo '<div class="tv3-reading-progress" aria-hidden="true"><span class="tv3-reading-progress__bar" data-tv3-reading-progress-bar></span><span class="tv3-reading-progress__label" data-tv3-reading-progress-label></span></div>';
}

add_action( 'wp_footer', 'themisdb_v3_print_skip_link_target_fallback', 99 );
/**
 * Ensure a skip-link target exists even when customized templates omit the ID.
 */
function themisdb_v3_print_skip_link_target_fallback() {
    ?>
    <script>
    (function(){
        var main = document.querySelector('main');
        if(!main){
            return;
        }
        if(!main.id){
            main.id = 'wp--skip-link--target';
        }
        if(!main.hasAttribute('tabindex')){
            main.setAttribute('tabindex', '-1');
        }
    })();
    </script>
    <?php
}

add_action( 'init', 'themisdb_v3_register_content_object_post_types', 5 );
/**
 * Register future-ready content object types when they do not already exist.
 */
function themisdb_v3_register_content_object_post_types() {
    $definitions = array(
        'video'      => __( 'Videos', 'themisdb-v3' ),
        'screencast' => __( 'Screencasts', 'themisdb-v3' ),
        'tutorial'   => __( 'Tutorials', 'themisdb-v3' ),
    );

    // Allow regular posts to store and expose audio_url + audio_attachment_id via REST
    // so that imported podcast posts (post type = post, category = podcast) work
    // identically to pod_episode in card listings.
    foreach ( array( 'audio_url', 'audio_attachment_id' ) as $meta_key ) {
        if ( ! registered_meta_key_exists( 'post', $meta_key, 'post' ) ) {
            register_post_meta(
                'post',
                $meta_key,
                array(
                    'type'         => ( 'audio_attachment_id' === $meta_key ) ? 'integer' : 'string',
                    'single'       => true,
                    'show_in_rest' => true,
                )
            );
        }
    }

    foreach ( $definitions as $slug => $label ) {
        if ( post_type_exists( $slug ) ) {
            continue;
        }

        register_post_type(
            $slug,
            array(
                'label'           => $label,
                'public'          => true,
                'show_in_rest'    => true,
                'menu_position'   => 22,
                'has_archive'     => true,
                'rewrite'         => array( 'slug' => $slug ),
                'supports'        => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions', 'custom-fields' ),
                'show_in_nav_menus' => true,
            )
        );
    }
}

/**
 * Get supported post types for mixed content cards.
 *
 * @return array<int,string>
 */
function themisdb_v3_get_supported_content_post_types() {
    $post_types = array( 'post', 'page' );

    $object_candidates = array(
        'pod_episode',
        'video',
        'pod_video',
        'screencast',
        'tutorial',
    );

    foreach ( $object_candidates as $candidate ) {
        if ( post_type_exists( $candidate ) ) {
            $post_types[] = $candidate;
        }
    }

    $post_types = array_values( array_unique( $post_types ) );

    /**
     * Filter supported content types for mixed cards.
     *
     * @param array<int,string> $post_types List of post types.
     */
    return (array) apply_filters( 'themisdb_v3_mixed_post_types', $post_types );
}

/**
 * Get a human-readable label for a content type.
 *
 * @param string $post_type Post type slug.
 * @return string
 */
function themisdb_v3_get_content_type_label( $post_type ) {
    $post_type = (string) $post_type;

    if ( 'pod_episode' === $post_type ) {
        return __( 'Podcast', 'themisdb-v3' );
    }

    if ( 'pod_video' === $post_type ) {
        return __( 'Video', 'themisdb-v3' );
    }

    $object = get_post_type_object( $post_type );
    if ( $object && ! empty( $object->labels->singular_name ) ) {
        return (string) $object->labels->singular_name;
    }

    return ucfirst( str_replace( array( '-', '_' ), ' ', $post_type ) );
}

/**
 * Get supported object-only content types.
 *
 * @return array<int,string>
 */
function themisdb_v3_get_supported_object_post_types() {
    return array_values(
        array_filter(
            themisdb_v3_get_supported_content_post_types(),
            static function ( $type ) {
                return themisdb_v3_is_object_content_type( (string) $type );
            }
        )
    );
}

add_action( 'wp_ajax_themisdb_v3_search_relationship_targets', 'themisdb_v3_ajax_search_relationship_targets' );
/**
 * Search relationship targets by title for the editor metabox.
 */
function themisdb_v3_ajax_search_relationship_targets() {
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
    }

    $nonce = isset( $_GET['_ajax_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_ajax_nonce'] ) ) : '';
    if ( ! wp_verify_nonce( $nonce, 'themisdb_v3_relationship_search' ) ) {
        wp_send_json_error( array( 'message' => 'invalid_nonce' ), 403 );
    }

    $mode = isset( $_GET['mode'] ) ? sanitize_key( wp_unslash( $_GET['mode'] ) ) : 'object';
    $term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';

    if ( strlen( $term ) < 2 ) {
        wp_send_json_success( array() );
    }

    $post_types = 'post' === $mode ? array( 'post', 'page' ) : themisdb_v3_get_supported_object_post_types();
    if ( empty( $post_types ) ) {
        wp_send_json_success( array() );
    }

    $query = new WP_Query(
        array(
            'post_type'           => $post_types,
            'post_status'         => 'publish',
            'posts_per_page'      => 10,
            's'                   => $term,
            'orderby'             => 'date',
            'order'               => 'DESC',
            'ignore_sticky_posts' => true,
        )
    );

    $items = array();
    foreach ( $query->posts as $item ) {
        $items[] = array(
            'id'    => (int) $item->ID,
            'title' => get_the_title( $item->ID ),
            'type'  => (string) $item->post_type,
            'label' => themisdb_v3_get_content_type_label( (string) $item->post_type ),
        );
    }

    wp_send_json_success( $items );
}

add_action( 'init', 'themisdb_v3_register_relationship_meta' );
/**
 * Register generic relationship metadata for content linking.
 */
function themisdb_v3_register_relationship_meta() {
    $types = themisdb_v3_get_supported_content_post_types();

    foreach ( $types as $type ) {
        register_post_meta(
            $type,
            'related_object_ids',
            array(
                'type'              => 'array',
                'single'            => true,
                'show_in_rest'      => array(
                    'schema' => array(
                        'type'  => 'array',
                        'items' => array(
                            'type' => 'integer',
                        ),
                    ),
                ),
                'sanitize_callback' => 'themisdb_v3_sanitize_id_list_meta',
                'auth_callback'     => static function () {
                    return current_user_can( 'edit_posts' );
                },
            )
        );

        register_post_meta(
            $type,
            'related_post_ids',
            array(
                'type'              => 'array',
                'single'            => true,
                'show_in_rest'      => array(
                    'schema' => array(
                        'type'  => 'array',
                        'items' => array(
                            'type' => 'integer',
                        ),
                    ),
                ),
                'sanitize_callback' => 'themisdb_v3_sanitize_id_list_meta',
                'auth_callback'     => static function () {
                    return current_user_can( 'edit_posts' );
                },
            )
        );

        register_post_meta(
            $type,
            'content_object_type',
            array(
                'type'              => 'string',
                'single'            => true,
                'show_in_rest'      => true,
                'sanitize_callback' => 'sanitize_key',
                'auth_callback'     => static function () {
                    return current_user_can( 'edit_posts' );
                },
            )
        );
    }
}

/**
 * Sanitize relationship ID list meta values.
 *
 * @param mixed $value Raw value.
 * @return array<int,int>
 */
function themisdb_v3_sanitize_id_list_meta( $value ) {
    return themisdb_v3_normalize_id_list( $value );
}

add_action( 'add_meta_boxes', 'themisdb_v3_add_relationship_metabox' );
/**
 * Add relationship metabox on supported content types.
 */
function themisdb_v3_add_relationship_metabox() {
    foreach ( themisdb_v3_get_supported_content_post_types() as $post_type ) {
        add_meta_box(
            'themisdb-v3-relationships',
            __( 'Content Relationships', 'themisdb-v3' ),
            'themisdb_v3_render_relationship_metabox',
            $post_type,
            'side',
            'default'
        );
    }
}

/**
 * Get candidate posts for relationship autocomplete fields.
 *
 * @param array<int,string> $post_types Post types.
 * @param int               $limit Max records.
 * @return array<int,array<string,mixed>>
 */
function themisdb_v3_get_relationship_picker_items( array $post_types, $limit = 50 ) {
    $query = new WP_Query(
        array(
            'post_type'           => $post_types,
            'post_status'         => 'publish',
            'posts_per_page'      => max( 1, absint( $limit ) ),
            'orderby'             => 'date',
            'order'               => 'DESC',
            'ignore_sticky_posts' => true,
        )
    );

    $items = array();
    if ( ! $query->have_posts() ) {
        return $items;
    }

    foreach ( $query->posts as $item ) {
        $items[] = array(
            'id'    => (int) $item->ID,
            'title' => get_the_title( $item->ID ),
            'type'  => (string) $item->post_type,
        );
    }

    return $items;
}

/**
 * Render relationship fields in post editor.
 *
 * @param WP_Post $post Post object.
 */
function themisdb_v3_render_relationship_metabox( $post ) {
    wp_nonce_field( 'themisdb_v3_save_relationships', 'themisdb_v3_relationships_nonce' );

    $picker_id_suffix = (string) absint( $post->ID );
    $related_objects = implode( ', ', themisdb_v3_get_related_object_ids( $post->ID ) );
    $related_posts   = implode( ', ', themisdb_v3_get_related_post_ids( $post->ID ) );
    $object_type     = (string) get_post_meta( $post->ID, 'content_object_type', true );

    $object_types    = themisdb_v3_get_supported_object_post_types();

    $object_input_id  = 'themisdb-v3-object-pick-' . $picker_id_suffix;
    $post_input_id    = 'themisdb-v3-post-pick-' . $picker_id_suffix;
    $object_results_id = 'themisdb-v3-object-results-' . $picker_id_suffix;
    $post_results_id   = 'themisdb-v3-post-results-' . $picker_id_suffix;
    $objects_field_id = 'themisdb-v3-related-objects-' . $picker_id_suffix;
    $posts_field_id   = 'themisdb-v3-related-posts-' . $picker_id_suffix;
    $object_type_list = 'themisdb-v3-object-type-list-' . $picker_id_suffix;
    $ajax_nonce       = wp_create_nonce( 'themisdb_v3_relationship_search' );
    ?>
    <p>
        <label for="<?php echo esc_attr( $object_input_id ); ?>"><strong><?php esc_html_e( 'Objekt suchen und ID hinzufuegen', 'themisdb-v3' ); ?></strong></label>
        <input id="<?php echo esc_attr( $object_input_id ); ?>" type="text" style="width:100%;" placeholder="Podcast/Video/Tutorial nach Titel suchen" autocomplete="off" />
        <div id="<?php echo esc_attr( $object_results_id ); ?>" class="themisdb-v3-picker-results"></div>
    </p>
    <p>
        <label for="<?php echo esc_attr( $objects_field_id ); ?>"><strong><?php esc_html_e( 'Related object IDs', 'themisdb-v3' ); ?></strong></label>
        <input id="<?php echo esc_attr( $objects_field_id ); ?>" name="themisdb_v3_related_object_ids" type="text" value="<?php echo esc_attr( $related_objects ); ?>" style="width:100%;" placeholder="8, 9, 10" />
    </p>
    <p>
        <label for="<?php echo esc_attr( $post_input_id ); ?>"><strong><?php esc_html_e( 'Beitrag suchen und ID hinzufuegen', 'themisdb-v3' ); ?></strong></label>
        <input id="<?php echo esc_attr( $post_input_id ); ?>" type="text" style="width:100%;" placeholder="Artikel/Seite nach Titel suchen" autocomplete="off" />
        <div id="<?php echo esc_attr( $post_results_id ); ?>" class="themisdb-v3-picker-results"></div>
    </p>
    <p>
        <label for="<?php echo esc_attr( $posts_field_id ); ?>"><strong><?php esc_html_e( 'Related post IDs', 'themisdb-v3' ); ?></strong></label>
        <input id="<?php echo esc_attr( $posts_field_id ); ?>" name="themisdb_v3_related_post_ids" type="text" value="<?php echo esc_attr( $related_posts ); ?>" style="width:100%;" placeholder="1, 2" />
    </p>
    <p>
        <label for="themisdb-v3-object-type"><strong><?php esc_html_e( 'Object type', 'themisdb-v3' ); ?></strong></label>
        <input id="themisdb-v3-object-type" name="themisdb_v3_content_object_type" type="text" value="<?php echo esc_attr( $object_type ); ?>" style="width:100%;" list="<?php echo esc_attr( $object_type_list ); ?>" placeholder="podcast, video, screencast, tutorial" />
        <datalist id="<?php echo esc_attr( $object_type_list ); ?>">
            <?php foreach ( $object_types as $type ) : ?>
                <option value="<?php echo esc_attr( sanitize_key( $type ) ); ?>"><?php echo esc_html( themisdb_v3_get_content_type_label( $type ) ); ?></option>
            <?php endforeach; ?>
        </datalist>
    </p>
    <p style="margin-bottom:0;color:#50575e;">
        <?php esc_html_e( 'Dynamische Kriterien: Suche basiert auf vorhandenen Content-Typen und vorhandenen Titeln im System.', 'themisdb-v3' ); ?>
    </p>
    <style>
    .themisdb-v3-picker-results { margin-top: 0.35rem; display: grid; gap: 0.3rem; }
    .themisdb-v3-picker-result { width: 100%; text-align: left; border: 1px solid #d0d7de; background: #fff; border-radius: 6px; padding: 0.38rem 0.5rem; cursor: pointer; }
    .themisdb-v3-picker-result strong { display: block; }
    .themisdb-v3-picker-result span { color: #50575e; font-size: 11px; }
    </style>
    <script>
    (function(){
        var ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
        var ajaxNonce = <?php echo wp_json_encode( $ajax_nonce ); ?>;

        function appendId(target, id){
            var raw = (target.value || '').trim();
            var parts = raw ? raw.split(/[\s,;|]+/) : [];
            var map = {};
            parts.forEach(function(part){
                var parsed = parseInt(part, 10);
                if(parsed){
                    map[String(parsed)] = true;
                }
            });
            map[String(id)] = true;

            var merged = Object.keys(map).map(function(key){ return parseInt(key, 10); }).filter(function(v){ return !!v; });
            merged.sort(function(a, b){ return a - b; });
            target.value = merged.join(', ');
        }

        function bindSearch(pickerId, resultsId, targetId, mode){
            var picker = document.getElementById(pickerId);
            var results = document.getElementById(resultsId);
            var target = document.getElementById(targetId);
            var timer = null;

            if(!picker || !results || !target){
                return;
            }

            picker.addEventListener('input', function(){
                var term = (picker.value || '').trim();
                results.innerHTML = '';

                if(timer){
                    clearTimeout(timer);
                }

                if(term.length < 2){
                    return;
                }

                timer = setTimeout(function(){
                    var url = ajaxUrl + '?action=themisdb_v3_search_relationship_targets&_ajax_nonce=' + encodeURIComponent(ajaxNonce) + '&mode=' + encodeURIComponent(mode) + '&term=' + encodeURIComponent(term);
                    fetch(url, { credentials: 'same-origin' })
                        .then(function(response){ return response.json(); })
                        .then(function(payload){
                            results.innerHTML = '';
                            if(!payload || !payload.success || !Array.isArray(payload.data)){
                                return;
                            }

                            payload.data.forEach(function(item){
                                var button = document.createElement('button');
                                button.type = 'button';
                                button.className = 'themisdb-v3-picker-result';
                                button.innerHTML = '<strong>' + item.title + '</strong><span>' + item.label + ' · ID ' + item.id + '</span>';
                                button.addEventListener('click', function(){
                                    appendId(target, parseInt(item.id, 10));
                                    picker.value = '';
                                    results.innerHTML = '';
                                });
                                results.appendChild(button);
                            });
                        });
                }, 180);
            });
        }

        bindSearch('<?php echo esc_js( $object_input_id ); ?>', '<?php echo esc_js( $object_results_id ); ?>', '<?php echo esc_js( $objects_field_id ); ?>', 'object');
        bindSearch('<?php echo esc_js( $post_input_id ); ?>', '<?php echo esc_js( $post_results_id ); ?>', '<?php echo esc_js( $posts_field_id ); ?>', 'post');
    })();
    </script>
    <?php
}

add_action( 'save_post', 'themisdb_v3_save_relationship_metabox' );
/**
 * Persist relationship fields from metabox.
 *
 * @param int $post_id Post ID.
 */
function themisdb_v3_save_relationship_metabox( $post_id ) {
    if ( ! isset( $_POST['themisdb_v3_relationships_nonce'] ) ) {
        return;
    }

    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['themisdb_v3_relationships_nonce'] ) ), 'themisdb_v3_save_relationships' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $post_type = get_post_type( $post_id );
    if ( ! in_array( $post_type, themisdb_v3_get_supported_content_post_types(), true ) ) {
        return;
    }

    $object_ids_raw = isset( $_POST['themisdb_v3_related_object_ids'] ) ? wp_unslash( $_POST['themisdb_v3_related_object_ids'] ) : '';
    $post_ids_raw   = isset( $_POST['themisdb_v3_related_post_ids'] ) ? wp_unslash( $_POST['themisdb_v3_related_post_ids'] ) : '';
    $object_type_raw = isset( $_POST['themisdb_v3_content_object_type'] ) ? wp_unslash( $_POST['themisdb_v3_content_object_type'] ) : '';

    $old_object_ids = themisdb_v3_get_meta_id_list( $post_id, 'related_object_ids' );
    $old_post_ids   = themisdb_v3_get_meta_id_list( $post_id, 'related_post_ids' );

    $object_ids = themisdb_v3_normalize_id_list( $object_ids_raw );
    $post_ids   = themisdb_v3_normalize_id_list( $post_ids_raw );
    $object_type = sanitize_key( (string) $object_type_raw );

    if ( ! empty( $object_ids ) ) {
        update_post_meta( $post_id, 'related_object_ids', $object_ids );
    } else {
        delete_post_meta( $post_id, 'related_object_ids' );
    }

    if ( ! empty( $post_ids ) ) {
        update_post_meta( $post_id, 'related_post_ids', $post_ids );
    } else {
        delete_post_meta( $post_id, 'related_post_ids' );
    }

    if ( '' !== $object_type ) {
        update_post_meta( $post_id, 'content_object_type', $object_type );
    } else {
        delete_post_meta( $post_id, 'content_object_type' );
    }

    themisdb_v3_sync_bidirectional_relationships( $post_id, $old_object_ids, $object_ids, $old_post_ids, $post_ids );
}

add_filter( 'themisdb_persistent_podcast_player_html', 'themisdb_v3_hide_persistent_podcast_player', 10, 2 );
function themisdb_v3_hide_persistent_podcast_player( $html, $payload ) {
    return '';
}

add_filter( 'themisdb_persistent_podcast_player_enqueue_frontend_style', '__return_false' );

add_action( 'wp_enqueue_scripts', 'themisdb_v3_dequeue_persistent_podcast_player_assets', 100 );
function themisdb_v3_dequeue_persistent_podcast_player_assets() {
    wp_dequeue_style( 'ppp-player-style' );
    wp_deregister_style( 'ppp-player-style' );
    wp_dequeue_script( 'ppp-player-script' );
    wp_deregister_script( 'ppp-player-script' );
}

/**
 * Extend the front-page query block to include mixed content types.
 */
add_filter( 'query_loop_block_query_vars', 'themisdb_v3_mixed_frontpage_query', 10, 2 );
function themisdb_v3_mixed_frontpage_query( $query, $block ) {
    if ( ! is_array( $query ) ) {
        return $query;
    }

    $class_name = '';
    if ( is_object( $block ) && isset( $block->parsed_block['attrs']['className'] ) ) {
        $class_name = (string) $block->parsed_block['attrs']['className'];
    } elseif ( is_array( $block ) && isset( $block['attrs']['className'] ) ) {
        $class_name = (string) $block['attrs']['className'];
    }

    if ( false === strpos( $class_name, 'tv3-query-mixed' ) ) {
        return $query;
    }

    $post_types = themisdb_v3_get_supported_content_post_types();

    $query['post_type']      = $post_types;
    $query['post_status']    = 'publish';
    $query['orderby']        = 'date';
    $query['order']          = 'DESC';
    $query['no_found_rows']  = false;
    $query['suppress_filters'] = false;

    return $query;
}

/**
 * Fallback: enforce mixed content on front-page non-main post queries.
 */
add_action( 'pre_get_posts', 'themisdb_v3_mixed_frontpage_query_fallback', 20 );
function themisdb_v3_mixed_frontpage_query_fallback( $query ) {
    if ( is_admin() || ! ( $query instanceof WP_Query ) || $query->is_main_query() ) {
        return;
    }

    if ( ! is_front_page() ) {
        return;
    }

    $configured_type = $query->get( 'post_type' );
    if ( empty( $configured_type ) ) {
        return;
    }

    $is_post_only = ( 'post' === $configured_type ) || ( is_array( $configured_type ) && 1 === count( $configured_type ) && 'post' === reset( $configured_type ) );
    if ( ! $is_post_only ) {
        return;
    }

    $post_types = themisdb_v3_get_supported_content_post_types();

    $query->set( 'post_type', $post_types );
    $query->set( 'post_status', 'publish' );
    $query->set( 'orderby', 'date' );
    $query->set( 'order', 'DESC' );
}

/**
 * Build score and overlay metrics for a card from WordPress data and post meta.
 *
 * @param int $post_id Post ID.
 * @return array<string,mixed>
 */
function themisdb_v3_get_card_metrics( $post_id ) {
    $post_type      = get_post_type( $post_id );
    $featured_meta  = get_post_meta( $post_id, 'featured', true );
    $relevance_meta = absint( get_post_meta( $post_id, 'relevance_score', true ) );
    $priority_meta  = absint( get_post_meta( $post_id, 'priority', true ) );
    $relationship_count = themisdb_v3_get_relationship_count( $post_id );
    $audio_url      = (string) get_post_meta( $post_id, 'audio_url', true );
    $comment_count  = (int) get_comments_number( $post_id );
    $tag_terms      = get_the_terms( $post_id, 'post_tag' );
    $tag_count      = ( ! empty( $tag_terms ) && ! is_wp_error( $tag_terms ) ) ? count( $tag_terms ) : 0;
    $days_since     = max( 0, (int) floor( ( time() - get_post_timestamp( $post_id ) ) / DAY_IN_SECONDS ) );
    $is_featured    = in_array( (string) $featured_meta, array( '1', 'true', 'yes', 'on' ), true ) || is_sticky( $post_id );

    $has_thumbnail  = has_post_thumbnail( $post_id );
    $bookmark_score = $relevance_meta;
    if ( $bookmark_score <= 0 ) {
        // Build only from observable signals; avoid synthetic base values.
        $bookmark_score = min(
            99,
            ( $comment_count * 6 ) +
            ( $tag_count * 4 ) +
            ( $has_thumbnail ? 8 : 0 ) +
            ( $is_featured ? 12 : 0 ) +
            ( ! empty( $audio_url ) ? 10 : 0 ) +
            min( 18, $relationship_count * 4 )
        );
    }

    $star_score = $priority_meta > 0 ? min( 5, max( 1, $priority_meta / 2 ) ) : 0;
    if ( $star_score <= 0 ) {
        $star_score = min(
            5,
            max(
                0,
                2.8 +
                ( $is_featured ? 0.8 : 0 ) +
                min( 0.9, $comment_count / 10 ) +
                min( 0.5, $tag_count / 8 ) +
                ( ! empty( $audio_url ) ? 0.4 : 0 ) +
                ( $days_since <= 14 ? 0.2 : 0 )
            )
        );
    }

    $engagement_evidence = 0;
    $engagement_evidence += $comment_count > 0 ? 1 : 0;
    $engagement_evidence += $tag_count > 0 ? 1 : 0;
    $engagement_evidence += $relationship_count > 0 ? 1 : 0;
    $engagement_evidence += $has_thumbnail ? 1 : 0;
    $engagement_evidence += $is_featured ? 1 : 0;
    $engagement_evidence += ! empty( $audio_url ) ? 1 : 0;
    $engagement_evidence += $priority_meta > 0 ? 1 : 0;
    $engagement_evidence += $relevance_meta > 0 ? 1 : 0;

    if ( $priority_meta <= 0 && $engagement_evidence < 2 ) {
        $star_score = 0;
    }

    $relevance_score = min(
        100,
        max(
            0,
            $bookmark_score +
            ( $star_score * 12 ) +
            ( $is_featured ? 12 : 0 ) +
            ( 'pod_episode' === $post_type ? 6 : 0 ) -
            min( 18, $days_since ) +
            min( 16, $relationship_count * 3 )
        )
    );

    return array(
        'post_type'        => $post_type,
        'tag_terms'        => $tag_terms,
        'tag_count'        => $tag_count,
        'days_since'       => $days_since,
        'is_featured'      => $is_featured,
        'relationship_count' => $relationship_count,
        'bookmark_score'   => $bookmark_score,
        'star_score'       => $star_score,
        'relevance_score'  => $relevance_score,
        'engagement_evidence' => $engagement_evidence,
    );
}

/**
 * Normalize ID list values from array/json/csv/meta formats.
 *
 * @param mixed $value Raw value.
 * @return array<int,int>
 */
function themisdb_v3_normalize_id_list( $value ) {
    $ids = array();

    if ( is_array( $value ) ) {
        foreach ( $value as $item ) {
            $ids = array_merge( $ids, themisdb_v3_normalize_id_list( $item ) );
        }
    } elseif ( is_string( $value ) ) {
        $trimmed = trim( $value );

        if ( '' === $trimmed ) {
            return array();
        }

        if ( '[' === substr( $trimmed, 0, 1 ) ) {
            $decoded = json_decode( $trimmed, true );
            if ( is_array( $decoded ) ) {
                return themisdb_v3_normalize_id_list( $decoded );
            }
        }

        $parts = preg_split( '/[\s,;|]+/', $trimmed );
        if ( is_array( $parts ) ) {
            foreach ( $parts as $part ) {
                $id = absint( $part );
                if ( $id > 0 ) {
                    $ids[] = $id;
                }
            }
        }
    } else {
        $id = absint( $value );
        if ( $id > 0 ) {
            $ids[] = $id;
        }
    }

    return array_values( array_unique( array_filter( $ids ) ) );
}

/**
 * Get clean ID list directly from one meta key.
 *
 * @param int    $post_id Post ID.
 * @param string $meta_key Meta key.
 * @return array<int,int>
 */
function themisdb_v3_get_meta_id_list( $post_id, $meta_key ) {
    $single = get_post_meta( $post_id, $meta_key, true );
    $multi  = get_post_meta( $post_id, $meta_key, false );

    return array_values(
        array_unique(
            array_merge(
                themisdb_v3_normalize_id_list( $single ),
                themisdb_v3_normalize_id_list( $multi )
            )
        )
    );
}

/**
 * Check whether post type is treated as content object (not post/page).
 *
 * @param string $post_type Post type.
 * @return bool
 */
function themisdb_v3_is_object_content_type( $post_type ) {
    return ! in_array( $post_type, array( 'post', 'page' ), true );
}

/**
 * Resolve backlink meta key to keep relationships bidirectional.
 *
 * @param string $source_type Source post type.
 * @param string $target_type Target post type.
 * @return string
 */
function themisdb_v3_get_backlink_meta_key( $source_type, $target_type ) {
    $source_is_object = themisdb_v3_is_object_content_type( (string) $source_type );
    $target_is_object = themisdb_v3_is_object_content_type( (string) $target_type );

    if ( $source_is_object && ! $target_is_object ) {
        return 'related_object_ids';
    }

    if ( ! $source_is_object && $target_is_object ) {
        return 'related_post_ids';
    }

    return $source_is_object ? 'related_object_ids' : 'related_post_ids';
}

/**
 * Synchronize forward relationship changes to targets.
 *
 * @param int              $source_id Source post ID.
 * @param array<int,int>   $old_targets Previous target IDs.
 * @param array<int,int>   $new_targets New target IDs.
 */
function themisdb_v3_sync_relationship_set( $source_id, array $old_targets, array $new_targets ) {
    $source_type = (string) get_post_type( $source_id );
    $all_targets = array_values( array_unique( array_merge( $old_targets, $new_targets ) ) );

    foreach ( $all_targets as $target_id ) {
        $target_id = absint( $target_id );
        if ( $target_id <= 0 || $target_id === (int) $source_id ) {
            continue;
        }

        $target = get_post( $target_id );
        if ( ! $target ) {
            continue;
        }

        $target_type = (string) $target->post_type;
        $meta_key    = themisdb_v3_get_backlink_meta_key( $source_type, $target_type );
        $target_ids  = themisdb_v3_get_meta_id_list( $target_id, $meta_key );
        $should_have = in_array( $target_id, $new_targets, true );

        if ( $should_have && ! in_array( (int) $source_id, $target_ids, true ) ) {
            $target_ids[] = (int) $source_id;
        }

        if ( ! $should_have && in_array( (int) $source_id, $target_ids, true ) ) {
            $target_ids = array_values( array_diff( $target_ids, array( (int) $source_id ) ) );
        }

        if ( ! empty( $target_ids ) ) {
            update_post_meta( $target_id, $meta_key, array_values( array_unique( array_map( 'absint', $target_ids ) ) ) );
        } else {
            delete_post_meta( $target_id, $meta_key );
        }
    }
}

/**
 * Keep related object/post IDs bidirectional after save.
 *
 * @param int            $post_id Post ID.
 * @param array<int,int> $old_object_ids Old related objects.
 * @param array<int,int> $new_object_ids New related objects.
 * @param array<int,int> $old_post_ids Old related posts.
 * @param array<int,int> $new_post_ids New related posts.
 */
function themisdb_v3_sync_bidirectional_relationships( $post_id, array $old_object_ids, array $new_object_ids, array $old_post_ids, array $new_post_ids ) {
    themisdb_v3_sync_relationship_set( $post_id, $old_object_ids, $new_object_ids );
    themisdb_v3_sync_relationship_set( $post_id, $old_post_ids, $new_post_ids );
}

/**
 * Build available card filters from the current query result set.
 *
 * @param array<int,WP_Post> $posts Active mixed feed posts.
 * @return array<string,array<string,mixed>>
 */
function themisdb_v3_get_card_type_filters( array $posts ) {
    $supported_types = themisdb_v3_get_supported_content_post_types();
    $counts          = array();

    foreach ( $posts as $post ) {
        if ( ! $post instanceof WP_Post ) {
            continue;
        }

        $post_type = (string) $post->post_type;
        if ( '' === $post_type || ! post_type_exists( $post_type ) ) {
            continue;
        }

        if ( ! isset( $counts[ $post_type ] ) ) {
            $counts[ $post_type ] = 0;
        }

        $counts[ $post_type ]++;
    }

    $visible_post_types = array_values( array_intersect( $supported_types, array_keys( $counts ) ) );

    $filters = array(
        'all' => array(
            'label' => __( 'Alle', 'themisdb-v3' ),
            'types' => $visible_post_types,
            'count' => array_sum( $counts ),
        ),
    );

    foreach ( $visible_post_types as $post_type ) {
        $filters[ sanitize_key( $post_type ) ] = array(
            'label' => themisdb_v3_get_content_type_label( $post_type ),
            'types' => array( $post_type ),
            'count' => (int) $counts[ $post_type ],
        );
    }

    return $filters;
}

/**
 * Get outbound linked object IDs for a post.
 *
 * @param int $post_id Post ID.
 * @return array<int,int>
 */
function themisdb_v3_get_related_object_ids( $post_id ) {
    $ids = array();

    $raw_single = get_post_meta( $post_id, 'related_object_ids', true );
    $raw_multi  = get_post_meta( $post_id, 'related_object_ids', false );

    $ids = array_merge( $ids, themisdb_v3_normalize_id_list( $raw_single ) );
    $ids = array_merge( $ids, themisdb_v3_normalize_id_list( $raw_multi ) );

    // Backward compatibility for older single-link meta.
    $legacy_related = absint( get_post_meta( $post_id, 'related_post_id', true ) );
    if ( $legacy_related > 0 ) {
        $ids[] = $legacy_related;
    }

    return array_values( array_unique( array_filter( $ids ) ) );
}

/**
 * Get related post IDs stored on content objects.
 *
 * @param int $post_id Post ID.
 * @return array<int,int>
 */
function themisdb_v3_get_related_post_ids( $post_id ) {
    $ids = array();

    $raw_single = get_post_meta( $post_id, 'related_post_ids', true );
    $raw_multi  = get_post_meta( $post_id, 'related_post_ids', false );

    $ids = array_merge( $ids, themisdb_v3_normalize_id_list( $raw_single ) );
    $ids = array_merge( $ids, themisdb_v3_normalize_id_list( $raw_multi ) );

    return array_values( array_unique( array_filter( $ids ) ) );
}

/**
 * Get total relationship links attached to an item.
 *
 * @param int $post_id Post ID.
 * @return int
 */
function themisdb_v3_get_relationship_count( $post_id ) {
    $related = array_merge(
        themisdb_v3_get_related_object_ids( $post_id ),
        themisdb_v3_get_related_post_ids( $post_id )
    );

    return count( array_unique( array_filter( array_map( 'absint', $related ) ) ) );
}

/**
 * Try to extract an audio URL from enclosure meta or post content.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function themisdb_v3_extract_audio_url_from_post( $post_id ) {
    $enclosure = trim( (string) get_post_meta( $post_id, 'enclosure', true ) );
    if ( $enclosure !== '' ) {
        $parts = preg_split( '/\r\n|\r|\n/', $enclosure );
        if ( ! empty( $parts[0] ) ) {
            $candidate = esc_url_raw( trim( (string) $parts[0] ) );
            if ( $candidate !== '' ) {
                return $candidate;
            }
        }
    }

    $post_content = (string) get_post_field( 'post_content', $post_id );
    if ( $post_content === '' ) {
        return '';
    }

    if ( function_exists( 'parse_blocks' ) ) {
        $block_queue = parse_blocks( $post_content );
        while ( ! empty( $block_queue ) ) {
            $block = array_shift( $block_queue );
            if ( ! is_array( $block ) ) {
                continue;
            }

            if ( isset( $block['blockName'] ) && 'core/audio' === $block['blockName'] ) {
                $attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
                if ( ! empty( $attrs['src'] ) ) {
                    $candidate = esc_url_raw( trim( (string) $attrs['src'] ) );
                    if ( $candidate !== '' ) {
                        return $candidate;
                    }
                }

                if ( ! empty( $attrs['id'] ) ) {
                    $attachment_url = wp_get_attachment_url( absint( $attrs['id'] ) );
                    if ( ! empty( $attachment_url ) ) {
                        return (string) $attachment_url;
                    }
                }
            }

            if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
                $block_queue = array_merge( $block_queue, $block['innerBlocks'] );
            }
        }
    }

    if ( preg_match( '/<!--\s+wp:audio\s+\{[^}]*"id"\s*:\s*(\d+)[^}]*\}\s+-->/i', $post_content, $audio_block_id_match ) ) {
        $attachment_url = wp_get_attachment_url( absint( $audio_block_id_match[1] ) );
        if ( ! empty( $attachment_url ) ) {
            return (string) $attachment_url;
        }
    }

    if ( preg_match( '/\[audio[^\]]*(?:ids?|attachment_id)=["\']?(\d+)["\']?[^\]]*\]/i', $post_content, $audio_shortcode_id_match ) ) {
        $attachment_url = wp_get_attachment_url( absint( $audio_shortcode_id_match[1] ) );
        if ( ! empty( $attachment_url ) ) {
            return (string) $attachment_url;
        }
    }

    if ( preg_match( '/\[audio[^\]]*(?:src|mp3|m4a|ogg|wav)=["\']([^"\']+)["\'][^\]]*\]/i', $post_content, $audio_shortcode_match ) ) {
        $candidate = esc_url_raw( trim( (string) $audio_shortcode_match[1] ) );
        if ( $candidate !== '' ) {
            return $candidate;
        }
    }

    if ( preg_match( '/https?:\/\/[^\s"\']+\.(?:mp3|m4a|wav|ogg)(?:\?[^\s"\']*)?/i', $post_content, $audio_file_match ) ) {
        $candidate = esc_url_raw( trim( (string) $audio_file_match[0] ) );
        if ( $candidate !== '' ) {
            return $candidate;
        }
    }

    return '';
}

function themisdb_v3_get_episode_audio_url( $post_id ) {
    $audio_url = (string) get_post_meta( $post_id, 'audio_url', true );
    if ( ! empty( $audio_url ) ) {
        return $audio_url;
    }

    $attachment_id = absint( get_post_meta( $post_id, 'audio_attachment_id', true ) );
    if ( $attachment_id > 0 ) {
        $attachment_url = wp_get_attachment_url( $attachment_id );
        if ( ! empty( $attachment_url ) ) {
            return (string) $attachment_url;
        }
    }

    return themisdb_v3_extract_audio_url_from_post( $post_id );
}

/**
 * Resolve a compact duration label for an episode audio file.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function themisdb_v3_get_episode_duration_label( $post_id ) {
    $attachment_id = absint( get_post_meta( $post_id, 'audio_attachment_id', true ) );
    if ( $attachment_id > 0 ) {
        $metadata = wp_get_attachment_metadata( $attachment_id );
        if ( is_array( $metadata ) && ! empty( $metadata['length_formatted'] ) ) {
            return (string) $metadata['length_formatted'];
        }
    }

    return 'Audio';
}

function themisdb_v3_get_mixed_cards_sort_options() {
    return array(
        'relevance'  => __( 'Relevanz', 'themisdb-v3' ),
        'newest'     => __( 'Neueste zuerst', 'themisdb-v3' ),
        'oldest'     => __( 'Aelteste zuerst', 'themisdb-v3' ),
        'title_asc'  => __( 'Titel A-Z', 'themisdb-v3' ),
        'title_desc' => __( 'Titel Z-A', 'themisdb-v3' ),
    );
}

/**
 * Normalize current mixed-card request state.
 *
 * @param array<string,mixed> $atts Shortcode attributes.
 * @param array<string,mixed> $request Optional request overrides.
 * @return array<string,mixed>
 */
function themisdb_v3_get_mixed_cards_state( array $atts = array(), array $request = array() ) {
    $sort_options = themisdb_v3_get_mixed_cards_sort_options();
    $raw_request  = ! empty( $request ) ? $request : wp_unslash( $_GET );
    $context      = themisdb_v3_get_mixed_cards_context( $raw_request );

    $limit = isset( $raw_request['tv3_limit'] ) ? absint( $raw_request['tv3_limit'] ) : absint( $atts['limit'] ?? 12 );
    $limit = max( 4, min( 24, $limit ) );

    $page = isset( $raw_request['tv3_page'] ) ? absint( $raw_request['tv3_page'] ) : 1;
    $page = max( 1, $page );

    $filter = isset( $raw_request['tv3_object_type'] ) ? sanitize_key( (string) $raw_request['tv3_object_type'] ) : sanitize_key( (string) ( $atts['type'] ?? 'all' ) );
    $filter = '' !== $filter ? $filter : 'all';

    $search = isset( $raw_request['tv3_search'] ) ? sanitize_text_field( (string) $raw_request['tv3_search'] ) : '';
    if ( '' === $search && is_search() ) {
        $search = sanitize_text_field( get_search_query() );
    }
    $sort   = isset( $raw_request['tv3_sort'] ) ? sanitize_key( (string) $raw_request['tv3_sort'] ) : 'newest';

    if ( ! isset( $sort_options[ $sort ] ) ) {
        $sort = 'newest';
    }

    $source_id = isset( $raw_request['tv3_source_id'] ) ? absint( $raw_request['tv3_source_id'] ) : 0;
    if ( $source_id <= 0 ) {
        $source_id = themisdb_v3_get_mixed_cards_source_id();
    }

    return array(
        'limit'  => $limit,
        'page'   => $page,
        'filter' => $filter,
        'search' => $search,
        'sort'   => $sort,
        'source_id' => $source_id,
        'base_path' => $context['base_path'],
        'context_view' => $context['view'],
        'context_taxonomy' => $context['taxonomy'],
        'context_term_id' => $context['term_id'],
        'context_author_id' => $context['author_id'],
        'context_post_type' => $context['post_type'],
        'context_year' => $context['year'],
        'context_monthnum' => $context['monthnum'],
        'context_day' => $context['day'],
    );
}

/**
 * Resolve the active mixed-card query context for page renders and AJAX refreshes.
 *
 * @param array<string,mixed> $request Optional request values.
 * @return array<string,mixed>
 */
function themisdb_v3_get_mixed_cards_context( array $request = array() ) {
    $context = array(
        'base_path' => themisdb_v3_get_mixed_cards_base_path( $request ),
        'view'      => 'default',
        'taxonomy'  => '',
        'term_id'   => 0,
        'author_id' => 0,
        'post_type' => '',
        'year'      => 0,
        'monthnum'  => 0,
        'day'       => 0,
    );

    $requested_view = isset( $request['tv3_context_view'] ) ? sanitize_key( (string) $request['tv3_context_view'] ) : '';
    if ( '' !== $requested_view ) {
        $context['view']      = $requested_view;
        $context['taxonomy']  = isset( $request['tv3_context_taxonomy'] ) ? sanitize_key( (string) $request['tv3_context_taxonomy'] ) : '';
        $context['term_id']   = isset( $request['tv3_context_term_id'] ) ? absint( $request['tv3_context_term_id'] ) : 0;
        $context['author_id'] = isset( $request['tv3_context_author_id'] ) ? absint( $request['tv3_context_author_id'] ) : 0;
        $context['post_type'] = isset( $request['tv3_context_post_type'] ) ? sanitize_key( (string) $request['tv3_context_post_type'] ) : '';
        $context['year']      = isset( $request['tv3_context_year'] ) ? absint( $request['tv3_context_year'] ) : 0;
        $context['monthnum']  = isset( $request['tv3_context_monthnum'] ) ? absint( $request['tv3_context_monthnum'] ) : 0;
        $context['day']       = isset( $request['tv3_context_day'] ) ? absint( $request['tv3_context_day'] ) : 0;
        return $context;
    }

    if ( is_category() || is_tag() || is_tax() ) {
        $term = get_queried_object();
        if ( $term instanceof WP_Term ) {
            $context['view']     = 'taxonomy';
            $context['taxonomy'] = $term->taxonomy;
            $context['term_id']  = (int) $term->term_id;
        }
        return $context;
    }

    if ( is_author() ) {
        $context['view']      = 'author';
        $context['author_id'] = (int) get_queried_object_id();
        return $context;
    }

    if ( is_post_type_archive() ) {
        $post_type = get_query_var( 'post_type' );
        $context['view'] = 'post_type';
        if ( is_array( $post_type ) && ! empty( $post_type ) ) {
            $context['post_type'] = sanitize_key( (string) reset( $post_type ) );
        } elseif ( is_string( $post_type ) ) {
            $context['post_type'] = sanitize_key( $post_type );
        }
        return $context;
    }

    if ( is_date() ) {
        $context['view']     = 'date';
        $context['year']     = (int) get_query_var( 'year' );
        $context['monthnum'] = (int) get_query_var( 'monthnum' );
        $context['day']      = (int) get_query_var( 'day' );
        return $context;
    }

    if ( is_search() ) {
        $context['view'] = 'search';
    }

    return $context;
}

/**
 * Apply current archive/search context to the mixed-card query args.
 *
 * @param array<string,mixed> $query_args Base query arguments.
 * @param array<string,mixed> $state Current mixed-card state.
 * @return array<string,mixed>
 */
function themisdb_v3_apply_mixed_cards_context( array $query_args, array $state = array() ) {
    $context = themisdb_v3_get_mixed_cards_context(
        array(
            'tv3_base_path' => $state['base_path'] ?? '',
            'tv3_context_view' => $state['context_view'] ?? '',
            'tv3_context_taxonomy' => $state['context_taxonomy'] ?? '',
            'tv3_context_term_id' => $state['context_term_id'] ?? 0,
            'tv3_context_author_id' => $state['context_author_id'] ?? 0,
            'tv3_context_post_type' => $state['context_post_type'] ?? '',
            'tv3_context_year' => $state['context_year'] ?? 0,
            'tv3_context_monthnum' => $state['context_monthnum'] ?? 0,
            'tv3_context_day' => $state['context_day'] ?? 0,
        )
    );

    if ( 'taxonomy' === $context['view'] && '' !== $context['taxonomy'] && $context['term_id'] > 0 ) {
        $query_args['tax_query'] = array(
            array(
                'taxonomy' => $context['taxonomy'],
                'field'    => 'term_id',
                'terms'    => array( (int) $context['term_id'] ),
            ),
        );
    }

    if ( 'author' === $context['view'] && $context['author_id'] > 0 ) {
        $query_args['author'] = (int) $context['author_id'];
    }

    if ( 'post_type' === $context['view'] && '' !== $context['post_type'] ) {
        $query_args['post_type'] = $context['post_type'];
    }

    if ( 'date' === $context['view'] && $context['year'] > 0 ) {
        $date_query = array(
            'year' => (int) $context['year'],
        );

        if ( $context['monthnum'] > 0 ) {
            $date_query['monthnum'] = (int) $context['monthnum'];
        }

        if ( $context['day'] > 0 ) {
            $date_query['day'] = (int) $context['day'];
        }

        $query_args['date_query'] = array( $date_query );
    }

    return $query_args;
}

/**
 * Sort mixed card posts according to the active frontend sort setting.
 *
 * @param array<int,WP_Post> $posts Posts to sort.
 * @param string             $sort Sort key.
 * @return array<int,WP_Post>
 */
function themisdb_v3_sort_mixed_card_posts( array $posts, $sort ) {
    if ( 'oldest' === $sort ) {
        usort(
            $posts,
            static function ( $left, $right ) {
                return strcmp( $left->post_date_gmt, $right->post_date_gmt );
            }
        );

        return $posts;
    }

    if ( 'title_asc' === $sort || 'title_desc' === $sort ) {
        usort(
            $posts,
            static function ( $left, $right ) use ( $sort ) {
                $comparison = strcasecmp( get_the_title( $left->ID ), get_the_title( $right->ID ) );
                return 'title_desc' === $sort ? -1 * $comparison : $comparison;
            }
        );

        return $posts;
    }

    if ( 'newest' === $sort ) {
        usort(
            $posts,
            static function ( $left, $right ) {
                return strcmp( $right->post_date_gmt, $left->post_date_gmt );
            }
        );

        return $posts;
    }

    usort(
        $posts,
        static function ( $left, $right ) {
            $left_metrics  = themisdb_v3_get_card_metrics( $left->ID );
            $right_metrics = themisdb_v3_get_card_metrics( $right->ID );

            if ( $left_metrics['relevance_score'] === $right_metrics['relevance_score'] ) {
                return strcmp( $right->post_date_gmt, $left->post_date_gmt );
            }

            return $right_metrics['relevance_score'] <=> $left_metrics['relevance_score'];
        }
    );

    return $posts;
}

/**
 * Resolve the current mixed-card base path without query parameters.
 *
 * @param array<string,mixed> $request Optional request values.
 * @return string
 */
function themisdb_v3_get_mixed_cards_base_path( array $request = array() ) {
    if ( isset( $request['tv3_base_path'] ) ) {
        $base_path = strtok( (string) $request['tv3_base_path'], '?' );
        if ( is_string( $base_path ) && '' !== $base_path ) {
            return '/' . ltrim( $base_path, '/' );
        }
    }

    $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
    $path        = strtok( $request_uri, '?' );

    if ( false === $path || '' === $path ) {
        $path = '/';
    }

    if ( false !== strpos( $path, 'admin-ajax.php' ) ) {
        $referer = wp_get_referer();
        if ( $referer ) {
            $parsed_referer = wp_parse_url( $referer );
            $referer_path   = isset( $parsed_referer['path'] ) ? (string) $parsed_referer['path'] : '';
            if ( '' !== $referer_path ) {
                return '/' . ltrim( $referer_path, '/' );
            }
        }

        return '/';
    }

    return '/' . ltrim( $path, '/' );
}

/**
 * Build current base URL without mixed-card query parameters.
 *
 * @return string
 */
function themisdb_v3_get_mixed_cards_base_url() {
    if ( isset( $_REQUEST['tv3_base_path'] ) ) {
        return home_url( themisdb_v3_get_mixed_cards_base_path( wp_unslash( $_REQUEST ) ) );
    }

    if ( isset( $_REQUEST['tv3_source_id'] ) ) {
        $source_id = absint( wp_unslash( $_REQUEST['tv3_source_id'] ) );
        if ( $source_id > 0 ) {
            $source_url = get_permalink( $source_id );
            if ( ! empty( $source_url ) ) {
                return (string) $source_url;
            }
        }
    }

    return home_url( themisdb_v3_get_mixed_cards_base_path() );
}

/**
 * Resolve the canonical source page ID for mixed cards.
 *
 * @return int
 */
function themisdb_v3_get_mixed_cards_source_id() {
    $front_page_id = (int) get_option( 'page_on_front' );
    if ( is_front_page() && $front_page_id > 0 ) {
        return $front_page_id;
    }

    $posts_page_id = (int) get_option( 'page_for_posts' );
    if ( is_home() && $posts_page_id > 0 ) {
        return $posts_page_id;
    }

    $source_id = get_queried_object_id();

    if ( $source_id > 0 ) {
        return (int) $source_id;
    }

    return 0;
}

/**
 * Build a mixed-card URL for filter/sort/search navigation.
 *
 * @param array<string,mixed> $state Current state.
 * @param array<string,mixed> $overrides Replacement state values.
 * @return string
 */
function themisdb_v3_get_mixed_cards_url( array $state, array $overrides = array() ) {
    $args = array_merge( $state, $overrides );
    $url_args = array(
        'tv3_limit' => (int) $args['limit'],
    );

    if ( ! empty( $args['search'] ) ) {
        $url_args['tv3_search'] = (string) $args['search'];
    }

    if ( ! empty( $args['sort'] ) && 'newest' !== $args['sort'] ) {
        $url_args['tv3_sort'] = (string) $args['sort'];
    }

    if ( ! empty( $args['filter'] ) && 'all' !== $args['filter'] ) {
        $url_args['tv3_object_type'] = (string) $args['filter'];
    }

    if ( ! empty( $args['page'] ) && (int) $args['page'] > 1 ) {
        $url_args['tv3_page'] = (int) $args['page'];
    }

    return add_query_arg( $url_args, themisdb_v3_get_mixed_cards_base_url() );
}

/**
 * Query and prepare the full mixed-card result set.
 *
 * @param array<string,mixed> $state Current frontend state.
 * @return array<string,mixed>
 */
function themisdb_v3_prepare_mixed_cards_data( array $state ) {
    $query_args = array(
        'post_type'           => themisdb_v3_get_supported_content_post_types(),
        'post_status'         => 'publish',
        'posts_per_page'      => -1,
        'orderby'             => 'date',
        'order'               => 'DESC',
        'ignore_sticky_posts' => true,
        's'                   => (string) $state['search'],
        'no_found_rows'       => true,
    );

    $query = new WP_Query( themisdb_v3_apply_mixed_cards_context( $query_args, $state ) );

    $posts   = $query->posts;
    $filters = themisdb_v3_get_card_type_filters( $posts );

    if ( ! isset( $filters[ $state['filter'] ] ) ) {
        $state['filter'] = 'all';
    }

    if ( 'all' !== $state['filter'] ) {
        $allowed_types = $filters[ $state['filter'] ]['types'];
        $posts         = array_values(
            array_filter(
                $posts,
                static function ( $post ) use ( $allowed_types ) {
                    return $post instanceof WP_Post && in_array( (string) $post->post_type, $allowed_types, true );
                }
            )
        );
    }

    $posts       = themisdb_v3_sort_mixed_card_posts( $posts, (string) $state['sort'] );
    $total       = count( $posts );
    $total_pages = max( 1, (int) ceil( $total / (int) $state['limit'] ) );
    $page        = min( (int) $state['page'], $total_pages );
    $offset      = ( $page - 1 ) * (int) $state['limit'];
    $page_posts  = array_slice( $posts, $offset, (int) $state['limit'] );

    return array(
        'state'       => array_merge( $state, array( 'page' => $page ) ),
        'posts'       => $page_posts,
        'filters'     => $filters,
        'total'       => $total,
        'page'        => $page,
        'total_pages' => $total_pages,
        'has_more'    => $page < $total_pages,
    );
}

/**
 * Resolve card attention tone from categories.
 *
 * @param int $post_id Post ID.
 * @return array<string,string>
 */
function themisdb_v3_get_card_attention_tone( $post_id ) {
    $tone_raw = strtolower( trim( (string) get_post_meta( $post_id, 'tv3_attention_tone', true ) ) );
    if ( '' === $tone_raw ) {
        $tone_raw = strtolower( trim( (string) get_post_meta( $post_id, 'attention_tone', true ) ) );
    }

    $term_signals = array();
    $terms        = get_the_terms( $post_id, 'category' );
    if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
        foreach ( $terms as $term ) {
            if ( ! $term instanceof WP_Term ) {
                continue;
            }

            $term_signals[] = strtolower( trim( (string) $term->slug ) );
            $term_signals[] = strtolower( trim( (string) $term->name ) );
        }
    }

    $signal_blob = implode( '|', array_filter( array_merge( array( $tone_raw ), $term_signals ) ) );

    if ( '' === $signal_blob ) {
        return array();
    }

    if ( preg_match( '/critical|critical-note|critical note|urgent|warn|alarm|hinweis/', $signal_blob ) ) {
        return array(
            'label'      => 'Critical Note',
            'badge_class'=> 'tv3-card-badge-critical',
            'item_class' => 'tv3-card-tone-critical',
        );
    }

    if ( preg_match( '/announcement|announcements|annoucement|annoucements|ankuendigung|ankuendigungen|notice/', $signal_blob ) ) {
        return array(
            'label'      => 'Announcement',
            'badge_class'=> 'tv3-card-badge-announcement',
            'item_class' => 'tv3-card-tone-announcement',
        );
    }

    if ( preg_match( '/news|neuigkeit|neuigkeiten|update|updates/', $signal_blob ) ) {
        return array(
            'label'      => 'News',
            'badge_class'=> 'tv3-card-badge-news',
            'item_class' => 'tv3-card-tone-news',
        );
    }

    return array();
}

/**
 * Resolve the sizing thresholds and hero eligibility for the current view context.
 *
 * @param array<string,mixed> $state Current mixed-card state.
 * @return array<string,mixed> Sizing config.
 */
function themisdb_v3_get_card_sizing_config( array $state = array() ) {
    $view = $state['context_view'] ?? '';

    switch ( $view ) {
        case 'search':
            // On search results: no hero, tighter buckets so density stays high.
            return array(
                'allow_hero'   => false,
                'hero_min'     => 100,
                'xl_min'       => 85,
                'lg_min'       => 72,
                'sm_max'       => 35,
                'xs_max'       => 20,
            );

        case 'date':
            // Date archives: no hero, moderate range (browsing by time).
            return array(
                'allow_hero'   => false,
                'hero_min'     => 100,
                'xl_min'       => 82,
                'lg_min'       => 70,
                'sm_max'       => 36,
                'xs_max'       => 22,
            );

        case 'author':
            // Author page: hero allowed but needs stronger signal.
            return array(
                'allow_hero'   => true,
                'hero_min'     => 92,
                'xl_min'       => 80,
                'lg_min'       => 68,
                'sm_max'       => 38,
                'xs_max'       => 24,
            );

        case 'taxonomy':
        case 'post_type':
        default:
            // Curated archives and front page: full range.
            return array(
                'allow_hero'   => true,
                'hero_min'     => 90,
                'xl_min'       => 80,
                'lg_min'       => 68,
                'sm_max'       => 38,
                'xs_max'       => 24,
            );
    }
}

/**
 * Render the mixed-card grid items.
 *
 * @param array<int,WP_Post> $posts Posts to render.
 * @param array<string,mixed> $state Current mixed-card state.
 * @return string
 */
function themisdb_v3_render_mixed_cards_grid( array $posts, array $state = array() ) {
    if ( empty( $posts ) ) {
        return '<div class="tv3-card-empty"><p>Keine Inhalte fuer diese Auswahl gefunden.</p></div>';
    }

    $sizing = themisdb_v3_get_card_sizing_config( $state );

    ob_start();
    ?>
    <ul class="tv3-post-grid wp-block-post-template is-layout-grid">
        <?php
        $card_index    = 0;
        $hero_assigned = false;
        foreach ( $posts as $post ) {
            $post_id           = $post->ID;
            $title             = get_the_title( $post_id );
            $permalink         = get_permalink( $post_id );
            $thumb_html        = get_the_post_thumbnail( $post_id, 'large' );
            $date              = get_the_date( '', $post_id );
            $date_iso          = get_the_date( 'c', $post_id );
            $read_time_label   = themisdb_v3_get_read_time_label( $post_id );
            $author_items      = themisdb_v3_get_post_author_items( $post_id, 48, true );
            $author_names      = array();
            $author_name_links = array();
            $author_faces      = array();
            foreach ( $author_items as $author_item ) {
                $author_name = trim( (string) ( $author_item['name'] ?? '' ) );
                if ( '' !== $author_name ) {
                    $author_names[] = $author_name;

                    $author_url = trim( (string) ( $author_item['url'] ?? '' ) );
                    if ( '' !== $author_url ) {
                        $author_name_links[] = sprintf(
                            '<a href="%1$s" class="tv3-card-author-link">%2$s</a>',
                            esc_url( $author_url ),
                            esc_html( $author_name )
                        );
                    } else {
                        $author_name_links[] = esc_html( $author_name );
                    }
                }

                if ( ! empty( $author_item['avatar'] ) ) {
                    $author_faces[] = sprintf(
                        '<span class="tv3-card-author-face-item">%s</span>',
                        (string) $author_item['avatar']
                    );
                }
            }
            $author = ! empty( $author_name_links ) ? implode( ', ', $author_name_links ) : implode( ', ', $author_names );
            $metrics            = themisdb_v3_get_card_metrics( $post_id );
            $post_type          = $metrics['post_type'];
            $is_podcast_entry  = 'pod_episode' === $post_type || ( 'post' === $post_type && has_category( 'podcast', $post_id ) );
            $episode_audio_url = $is_podcast_entry ? themisdb_v3_get_episode_audio_url( $post_id ) : '';
            $episode_duration  = $is_podcast_entry ? themisdb_v3_get_episode_duration_label( $post_id ) : '';
            $excerpt            = get_the_excerpt( $post_id );
            $is_hero            = false;
            $relevance_size     = 'tv3-card-size-md';
            $tag_terms          = $metrics['tag_terms'];
            $days_since         = $metrics['days_since'];
            $is_featured        = $metrics['is_featured'];
            $badge_label        = 'Article';
            $badge_class        = 'tv3-card-badge-standard';
            $bookmark_score     = $metrics['bookmark_score'];
            $star_score         = $metrics['star_score'];
            $relevance_score    = $metrics['relevance_score'];
            $relationship_count = $metrics['relationship_count'];
            $engagement_evidence = (int) $metrics['engagement_evidence'];
            $attention_tone     = themisdb_v3_get_card_attention_tone( $post_id );

            $author_face = ! empty( $author_faces ) ? implode( '', $author_faces ) : '';

            if ( $sizing['allow_hero'] && ! $hero_assigned && $relevance_score >= $sizing['hero_min'] && $engagement_evidence >= 2 ) {
                $is_hero        = true;
                $hero_assigned  = true;
                $relevance_size = 'tv3-card-hero';
            } elseif ( $relevance_score >= $sizing['xl_min'] ) {
                $relevance_size = 'tv3-card-size-xl';
            } elseif ( $relevance_score >= $sizing['lg_min'] ) {
                $relevance_size = 'tv3-card-size-lg';
            } elseif ( $relevance_score <= $sizing['xs_max'] ) {
                $relevance_size = 'tv3-card-size-xs';
            } elseif ( $relevance_score <= $sizing['sm_max'] ) {
                $relevance_size = 'tv3-card-size-sm';
            }

            if ( $is_hero ) {
                $badge_label = 'Top Score';
                $badge_class = 'tv3-card-badge-hero';
            } elseif ( 'pod_episode' === $post_type || ( 'post' === $post_type && has_category( 'podcast', $post_id ) ) ) {
                $badge_label = 'Podcast';
                $badge_class = 'tv3-card-badge-podcast';
            } elseif ( in_array( $post_type, array( 'video', 'pod_video' ), true ) ) {
                $badge_label = 'Video';
                $badge_class = 'tv3-card-badge-podcast';
            } elseif ( 'screencast' === $post_type ) {
                $badge_label = 'Screencast';
                $badge_class = 'tv3-card-badge-guide';
            } elseif ( 'tutorial' === $post_type ) {
                $badge_label = 'Tutorial';
                $badge_class = 'tv3-card-badge-guide';
            } elseif ( $is_featured ) {
                $badge_label = 'Featured';
                $badge_class = 'tv3-card-badge-featured';
            } elseif ( 'page' === $post_type ) {
                $badge_label = 'Guide';
                $badge_class = 'tv3-card-badge-guide';
            } elseif ( $days_since <= 7 ) {
                $badge_label = 'Fresh';
                $badge_class = 'tv3-card-badge-fresh';
            }

            if ( ! empty( $attention_tone ) ) {
                $badge_label = (string) $attention_tone['label'];
                $badge_class = (string) $attention_tone['badge_class'];
            }

            $item_classes = trim(
                'tv3-card-item ' .
                $relevance_size .
                ( ! empty( $attention_tone['item_class'] ) ? ' ' . (string) $attention_tone['item_class'] : '' ) .
                ' wp-block-post ' .
                implode( ' ', get_post_class( '', $post_id ) )
            );
            ?>
            <li class="<?php echo esc_attr( $item_classes ); ?>">
                <article class="wp-block-group tv3-card">
                    <div class="wp-block-group tv3-card-media">
                        <div class="tv3-card-overlay-top">
                            <div class="tv3-card-badges">
                                <span class="tv3-card-badge <?php echo esc_attr( $badge_class ); ?>"><?php echo esc_html( $badge_label ); ?></span>
                            </div>
                            <div class="tv3-card-signals" aria-label="Card relevance signals">
                                <?php if ( $bookmark_score > 0 ) : ?>
                                    <span class="tv3-card-signal tv3-card-signal-bookmark"><span class="tv3-card-signal-icon" aria-hidden="true"><svg viewBox="0 0 16 16" focusable="false" aria-hidden="true"><path d="M4 2.5h8a1 1 0 0 1 1 1V14l-5-2.8L3 14V3.5a1 1 0 0 1 1-1Z" fill="currentColor"/></svg></span><span><?php echo esc_html( (string) $bookmark_score ); ?></span></span>
                                <?php endif; ?>
                                <?php if ( $relationship_count > 0 ) : ?>
                                    <span class="tv3-card-signal tv3-card-signal-bookmark" title="Linked objects"><span class="tv3-card-signal-icon" aria-hidden="true"><svg viewBox="0 0 16 16" focusable="false" aria-hidden="true"><path d="M6.2 5.1a2.5 2.5 0 0 1 3.53 0l1.17 1.17a2.5 2.5 0 0 1 0 3.53l-1.6 1.6a2.5 2.5 0 0 1-3.53 0l-.35-.34a.8.8 0 1 1 1.13-1.13l.35.35a.9.9 0 0 0 1.27 0l1.6-1.6a.9.9 0 0 0 0-1.27L8.6 6.24a.9.9 0 0 0-1.27 0l-1.1 1.1a.8.8 0 0 1-1.13-1.13l1.1-1.1Z" fill="currentColor"/><path d="M9.8 10.9a2.5 2.5 0 0 1-3.53 0L5.1 9.73a2.5 2.5 0 0 1 0-3.53l1.6-1.6a2.5 2.5 0 0 1 3.53 0l.35.34a.8.8 0 1 1-1.13 1.13l-.35-.35a.9.9 0 0 0-1.27 0l-1.6 1.6a.9.9 0 0 0 0 1.27l1.17 1.17a.9.9 0 0 0 1.27 0l1.1-1.1a.8.8 0 1 1 1.13 1.13l-1.1 1.1Z" fill="currentColor"/></svg></span><span><?php echo esc_html( (string) $relationship_count ); ?></span></span>
                                <?php endif; ?>
                                <?php if ( $star_score > 0 ) : ?>
                                    <span class="tv3-card-signal tv3-card-signal-star"><span class="tv3-card-signal-icon" aria-hidden="true"><svg viewBox="0 0 16 16" focusable="false" aria-hidden="true"><path d="m8 1.6 1.88 3.81 4.2.61-3.04 2.96.72 4.18L8 11.18l-3.76 1.98.72-4.18L1.92 6.02l4.2-.61L8 1.6Z" fill="currentColor"/></svg></span><span><?php echo esc_html( number_format_i18n( $star_score, 1 ) ); ?></span></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ( ! empty( $thumb_html ) ) : ?>
                            <figure class="wp-block-post-featured-image"><a href="<?php echo esc_url( $permalink ); ?>"><?php echo $thumb_html; ?></a></figure>
                        <?php endif; ?>
                        <div class="tv3-card-title-wrap">
                            <div class="tv3-card-article-wrapper">
                                <div class="tv3-card-author-row"><span class="tv3-card-author-face"><?php echo wp_kses_post( $author_face ); ?></span><span class="tv3-card-author-badge"><?php echo wp_kses_post( $author ); ?></span></div>
                                <div class="tv3-card-copy"><h3 class="tv3-card-title wp-block-post-title"><a class="tv3-card-copy-link" href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a></h3><div class="tv3-card-date-line teaser_content"><time datetime="<?php echo esc_attr( $date_iso ); ?>"><?php echo esc_html( $date ); ?></time><span class="tv3-card-meta-sep" aria-hidden="true">·</span><span class="tv3-card-read-time"><?php echo esc_html( $read_time_label ); ?></span></div></div>
                            </div>
                        </div>
                    </div>
                    <div class="wp-block-group tv3-card-body">
                        <?php if ( ! empty( $episode_audio_url ) ) : ?>
                            <div class="tv3-card-audio"><div class="tv3-card-audio-meta"><span class="tv3-card-audio-label">Episode Audio</span><span class="tv3-card-audio-duration"><?php echo esc_html( $episode_duration ); ?></span></div><?php echo wp_audio_shortcode( array( 'src' => esc_url( $episode_audio_url ), 'preload' => 'none' ) ); ?></div>
                        <?php endif; ?>
                        <?php if ( ! empty( $excerpt ) ) : ?>
                            <div class="tv3-card-excerpt wp-block-post-excerpt"><p class="wp-block-post-excerpt__excerpt"><?php echo esc_html( $excerpt ); ?></p><p class="wp-block-post-excerpt__more-text"><a class="wp-block-post-excerpt__more-link" href="<?php echo esc_url( $permalink ); ?>">Weiterlesen</a></p></div>
                        <?php endif; ?>
                        <?php
                        $keyword_terms = array();

                        if ( ! empty( $tag_terms ) && ! is_wp_error( $tag_terms ) ) {
                            foreach ( $tag_terms as $term ) {
                                if ( $term instanceof WP_Term ) {
                                    $keyword_terms[] = $term;
                                }
                            }
                        }

                        $category_terms = get_the_terms( $post_id, 'category' );
                        if ( ! empty( $category_terms ) && ! is_wp_error( $category_terms ) ) {
                            foreach ( $category_terms as $term ) {
                                if ( $term instanceof WP_Term ) {
                                    $keyword_terms[] = $term;
                                }
                            }
                        }

                        if ( ! empty( $keyword_terms ) ) {
                            $deduped = array();
                            foreach ( $keyword_terms as $term ) {
                                $term_key = (string) $term->taxonomy . ':' . (string) $term->term_id;
                                if ( isset( $deduped[ $term_key ] ) ) {
                                    continue;
                                }
                                $deduped[ $term_key ] = $term;
                            }

                            $keyword_terms = array_values( $deduped );
                            usort(
                                $keyword_terms,
                                static function ( WP_Term $left, WP_Term $right ) {
                                    $left_tax_priority  = 'post_tag' === $left->taxonomy ? 0 : 1;
                                    $right_tax_priority = 'post_tag' === $right->taxonomy ? 0 : 1;

                                    if ( $left_tax_priority !== $right_tax_priority ) {
                                        return $left_tax_priority <=> $right_tax_priority;
                                    }

                                    $left_count  = (int) $left->count;
                                    $right_count = (int) $right->count;
                                    if ( $left_count !== $right_count ) {
                                        return $right_count <=> $left_count;
                                    }

                                    return strcasecmp( $left->name, $right->name );
                                }
                            );

                            $keyword_terms = array_slice( $keyword_terms, 0, 4 );
                        }

                        if ( ! empty( $keyword_terms ) ) :
                            ?>
                            <div class="tv3-card-keywords" aria-label="Keywords">
                                <?php foreach ( $keyword_terms as $keyword_term ) : ?>
                                    <?php $keyword_url = add_query_arg( array( 'tv3_search' => (string) $keyword_term->name ), themisdb_v3_get_mixed_cards_base_url() ); ?>
                                    <a class="tv3-card-keyword" href="<?php echo esc_url( $keyword_url ); ?>"><?php echo esc_html( $keyword_term->name ); ?></a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </article>
            </li>
            <?php
            $card_index++;
        }
        ?>
    </ul>
    <?php

    $html = trim( (string) ob_get_clean() );
    return (string) preg_replace( '/>\s+</', '><', $html );
}

/**
 * Render filter, search and sort controls for mixed cards.
 *
 * @param array<string,mixed> $data Prepared query data.
 * @return string
 */
function themisdb_v3_render_mixed_cards_controls( array $data ) {
    $state        = $data['state'];
    $sort_options = themisdb_v3_get_mixed_cards_sort_options();

    ob_start();
    ?>
    <div class="tv3-card-toolbar-row">
        <form class="tv3-card-toolbar" method="get" action="<?php echo esc_url( themisdb_v3_get_mixed_cards_base_url() ); ?>" data-tv3-search-form>
            <input type="hidden" name="tv3_limit" value="<?php echo esc_attr( (string) $state['limit'] ); ?>" />
            <input type="hidden" name="tv3_page" value="1" />
            <input type="hidden" name="tv3_base_path" value="<?php echo esc_attr( (string) ( $state['base_path'] ?? '/' ) ); ?>" />
            <input type="hidden" name="tv3_source_id" value="<?php echo esc_attr( (string) ( $state['source_id'] ?? 0 ) ); ?>" />
            <input type="hidden" name="tv3_context_view" value="<?php echo esc_attr( (string) ( $state['context_view'] ?? 'default' ) ); ?>" />
            <input type="hidden" name="tv3_context_taxonomy" value="<?php echo esc_attr( (string) ( $state['context_taxonomy'] ?? '' ) ); ?>" />
            <input type="hidden" name="tv3_context_term_id" value="<?php echo esc_attr( (string) ( $state['context_term_id'] ?? 0 ) ); ?>" />
            <input type="hidden" name="tv3_context_author_id" value="<?php echo esc_attr( (string) ( $state['context_author_id'] ?? 0 ) ); ?>" />
            <input type="hidden" name="tv3_context_post_type" value="<?php echo esc_attr( (string) ( $state['context_post_type'] ?? '' ) ); ?>" />
            <input type="hidden" name="tv3_context_year" value="<?php echo esc_attr( (string) ( $state['context_year'] ?? 0 ) ); ?>" />
            <input type="hidden" name="tv3_context_monthnum" value="<?php echo esc_attr( (string) ( $state['context_monthnum'] ?? 0 ) ); ?>" />
            <input type="hidden" name="tv3_context_day" value="<?php echo esc_attr( (string) ( $state['context_day'] ?? 0 ) ); ?>" />
            <input type="hidden" name="tv3_object_type" value="<?php echo esc_attr( 'all' === $state['filter'] ? '' : (string) $state['filter'] ); ?>" data-tv3-filter-input />
            <label class="tv3-card-control tv3-card-search"><span class="screen-reader-text"><?php esc_html_e( 'Inhalte durchsuchen', 'themisdb-v3' ); ?></span><input type="search" name="tv3_search" value="<?php echo esc_attr( (string) $state['search'] ); ?>" placeholder="<?php echo esc_attr__( 'Titel oder Inhalt durchsuchen', 'themisdb-v3' ); ?>" data-tv3-search-input /></label>
            <label class="tv3-card-control tv3-card-sort"><span class="screen-reader-text"><?php esc_html_e( 'Sortierung', 'themisdb-v3' ); ?></span><select name="tv3_sort" data-tv3-sort-select><?php foreach ( $sort_options as $sort_key => $sort_label ) : ?><option value="<?php echo esc_attr( $sort_key ); ?>"<?php selected( $state['sort'], $sort_key ); ?>><?php echo esc_html( $sort_label ); ?></option><?php endforeach; ?></select></label>
        </form>
        <?php if ( count( $data['filters'] ) > 1 ) : ?>
        <nav class="tv3-card-filters tv3-card-filters-inline" aria-label="Content filter">
            <?php foreach ( $data['filters'] as $slug => $config ) : ?>
                <?php $active_class = $state['filter'] === $slug ? ' is-active' : ''; ?>
                <a class="tv3-card-filter<?php echo esc_attr( $active_class ); ?>" href="<?php echo esc_url( themisdb_v3_get_mixed_cards_url( $state, array( 'filter' => $slug, 'page' => 1 ) ) ); ?>" data-tv3-filter="<?php echo esc_attr( $slug ); ?>"><span><?php echo esc_html( $config['label'] ); ?></span><span class="tv3-card-filter-count"><?php echo esc_html( (string) ( $config['count'] ?? 0 ) ); ?></span></a>
            <?php endforeach; ?>
        </nav>
        <?php endif; ?>
        <p class="tv3-card-results-count"><?php echo esc_html( sprintf( _n( '%d Inhalt', '%d Inhalte', (int) $data['total'], 'themisdb-v3' ), (int) $data['total'] ) ); ?></p>
    </div>
    <?php

    $html = trim( (string) ob_get_clean() );
    return (string) preg_replace( '/>\s+</', '><', $html );
}

/**
 * Render the progressive load-more control.
 *
 * @param array<string,mixed> $data Prepared query data.
 * @return string
 */
function themisdb_v3_render_mixed_cards_pager( array $data ) {
    if ( empty( $data['has_more'] ) ) {
        return '';
    }

    $next_page = (int) $data['page'] + 1;
    $state     = $data['state'];
    $state['page'] = $next_page;

    return '<div class="tv3-card-loadmore-wrap" data-tv3-loadmore-wrap><span class="tv3-card-loadmore-hint">' . esc_html__( 'Weitere Inhalte werden automatisch geladen.', 'themisdb-v3' ) . '</span><a class="tv3-card-loadmore" href="' . esc_url( themisdb_v3_get_mixed_cards_url( $state ) ) . '" data-tv3-load-more="' . esc_attr( (string) $next_page ) . '">' . esc_html__( 'Jetzt laden', 'themisdb-v3' ) . '</a></div>';
}

/**
 * Render the full mixed-card section with AJAX controls.
 *
 * @param array<string,mixed> $data Prepared query data.
 * @return string
 */
function themisdb_v3_render_mixed_cards_section( array $data ) {
    wp_enqueue_script( 'themisdb-v3-mixed-cards' );

    $section_id    = wp_unique_id( 'tv3-mixed-cards-' );
    $context_class = ' tv3-query-front';
    $context_view  = $data['state']['context_view'] ?? 'default';

    ob_start();
    ?>
    <section id="<?php echo esc_attr( $section_id ); ?>" class="tv3-query tv3-query-mixed<?php echo esc_attr( $context_class ); ?>" data-tv3-mixed-cards data-tv3-context="<?php echo esc_attr( $context_view ); ?>" data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" data-ajax-nonce="<?php echo esc_attr( wp_create_nonce( 'themisdb_v3_mixed_cards' ) ); ?>">
        <div class="tv3-card-controls-region" data-tv3-controls-region><?php echo themisdb_v3_render_mixed_cards_controls( $data ); ?></div>
        <div class="tv3-card-grid-region" data-tv3-grid-region><?php echo themisdb_v3_render_mixed_cards_grid( $data['posts'], $data['state'] ); ?></div>
        <div class="tv3-card-loadmore-region" data-tv3-pager-region><?php echo themisdb_v3_render_mixed_cards_pager( $data ); ?></div>
        <p class="tv3-card-feedback" data-tv3-feedback hidden></p>
    </section>
    <?php

    $html = trim( (string) ob_get_clean() );
    return (string) preg_replace( '/>\s+</', '><', $html );
}

/**
 * AJAX endpoint for mixed-card search, sorting and dynamic loading.
 */
function themisdb_v3_ajax_mixed_cards() {
    check_ajax_referer( 'themisdb_v3_mixed_cards', 'nonce' );

    $state = themisdb_v3_get_mixed_cards_state( array(), wp_unslash( $_POST ) );
    $data  = themisdb_v3_prepare_mixed_cards_data( $state );

    wp_send_json_success(
        array(
            'controlsHtml' => themisdb_v3_render_mixed_cards_controls( $data ),
            'gridHtml'     => themisdb_v3_render_mixed_cards_grid( $data['posts'], $data['state'] ),
            'pagerHtml'    => themisdb_v3_render_mixed_cards_pager( $data ),
            'contextView'  => $data['state']['context_view'] ?? 'default',
            'message'      => sprintf( _n( '%d Inhalt geladen', '%d Inhalte geladen', (int) $data['total'], 'themisdb-v3' ), (int) $data['total'] ),
        )
    );
}
add_action( 'wp_ajax_themisdb_v3_mixed_cards', 'themisdb_v3_ajax_mixed_cards' );
add_action( 'wp_ajax_nopriv_themisdb_v3_mixed_cards', 'themisdb_v3_ajax_mixed_cards' );

/**
 * Render mixed content cards with search, sorting and progressive loading.
 *
 * @param array<string,mixed> $atts Shortcode attributes.
 * @return string
 */
function themisdb_v3_render_mixed_cards_shortcode( $atts ) {
    // Prevent accidental card-section rendering at the end of singular content
    // (post/page), unless explicitly re-enabled via filter.
    if ( is_singular() ) {
        $allow_on_singular = (bool) apply_filters( 'themisdb_v3_enable_mixed_cards_on_singular', false, get_queried_object_id() );
        if ( ! $allow_on_singular ) {
            return '';
        }
    }

    $atts  = shortcode_atts(
        array(
            'limit' => 12,
            'type'  => '',
        ),
        $atts,
        'themisdb_v3_mixed_cards'
    );
    $state = themisdb_v3_get_mixed_cards_state( $atts );
    $data  = themisdb_v3_prepare_mixed_cards_data( $state );

    return themisdb_v3_render_mixed_cards_section( $data );
}
add_shortcode( 'themisdb_v3_mixed_cards', 'themisdb_v3_render_mixed_cards_shortcode' );
