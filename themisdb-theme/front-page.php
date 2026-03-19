<?php
/**
 * The template for displaying the static front page.
 *
 * @package ThemisDB
 */

get_header();

$themisdb_link_for_slug = static function( $slug, $fallback = '' ) {
    $normalized_slug = trim( (string) $slug, '/' );

    $slug_aliases = array(
        'dokumentation' => array( 'docs', 'documentation' ),
        'documentation' => array( 'docs', 'dokumentation' ),
        'downloads' => array( 'download' ),
        'blog' => array( 'news' ),
    );

    if ( '' === $normalized_slug ) {
        return home_url( '/' );
    }

    $page = get_page_by_path( $normalized_slug );
    if ( $page instanceof WP_Post ) {
        return get_permalink( $page->ID );
    }

    if ( isset( $slug_aliases[ $normalized_slug ] ) ) {
        foreach ( $slug_aliases[ $normalized_slug ] as $alias_slug ) {
            $alias_page = get_page_by_path( $alias_slug );
            if ( $alias_page instanceof WP_Post ) {
                return get_permalink( $alias_page->ID );
            }
        }
    }

    if ( 'blog' === $normalized_slug ) {
        $posts_page_id = (int) get_option( 'page_for_posts' );
        if ( $posts_page_id > 0 ) {
            return get_permalink( $posts_page_id );
        }

        // If no dedicated posts page is configured, point to the home posts view.
        return home_url( '/' );
    }

    $permalink_structure = (string) get_option( 'permalink_structure', '' );
    if ( false !== strpos( $permalink_structure, 'index.php' ) ) {
        return add_query_arg( 'pagename', $normalized_slug, home_url( '/index.php' ) );
    }

    if ( ! empty( $fallback ) ) {
        return $fallback;
    }

    return home_url( '/' . user_trailingslashit( $normalized_slug ) );
};

$posts_page_id   = (int) get_option( 'page_for_posts' );
$posts_page_url  = $posts_page_id ? get_permalink( $posts_page_id ) : '';
$blog_cta_url_override = get_theme_mod( 'themisdb_home_blog_cta_url', '' );
$hero_blog_cta_url     = ! empty( $blog_cta_url_override ) ? $blog_cta_url_override : $posts_page_url;
$has_posts_page        = ! empty( $hero_blog_cta_url );
$show_latest_articles = (bool) get_theme_mod( 'themisdb_home_show_latest_articles', true );
$latest_query_limit   = 10;

$safe_home_fallback = home_url( '/' );
$url_dokumentation = $themisdb_link_for_slug( 'documentation', $safe_home_fallback );
$url_downloads = $themisdb_link_for_slug( 'downloads', $safe_home_fallback );
$url_support = $themisdb_link_for_slug( 'support', $safe_home_fallback );
$url_features = $themisdb_link_for_slug( 'features', $safe_home_fallback );
$url_roadmap = $themisdb_link_for_slug( 'roadmap', $safe_home_fallback );
$url_blog = $themisdb_link_for_slug( 'blog', $safe_home_fallback );
$url_plugins = $themisdb_link_for_slug( 'plugins', $safe_home_fallback );
$url_integrations = $themisdb_link_for_slug( 'integrations', $safe_home_fallback );
$url_performance = $themisdb_link_for_slug( 'performance', $safe_home_fallback );
$url_security = $themisdb_link_for_slug( 'security', $safe_home_fallback );
$url_pricing = $themisdb_link_for_slug( 'pricing', $safe_home_fallback );
$url_community = $themisdb_link_for_slug( 'community', $safe_home_fallback );
$url_releases = $themisdb_link_for_slug( 'releases', $safe_home_fallback );

$hero_kicker        = get_theme_mod( 'themisdb_home_hero_kicker', esc_html__( 'ThemisDB Startseite', 'themisdb' ) );
$hero_subtitle_mod  = get_theme_mod( 'themisdb_home_hero_subtitle', '' );
$latest_cta_label   = get_theme_mod( 'themisdb_home_latest_cta_label', esc_html__( 'Neueste Artikel', 'themisdb' ) );
$blog_cta_label     = get_theme_mod( 'themisdb_home_blog_cta_label', esc_html__( 'Zum Blog', 'themisdb' ) );

$show_stats         = (bool) get_theme_mod( 'themisdb_home_show_stats', true );
$stat_posts_icon    = get_theme_mod( 'themisdb_home_stat_posts_icon', '📝' );
$stat_posts_label   = get_theme_mod( 'themisdb_home_stat_posts_label', esc_html__( 'Artikel', 'themisdb' ) );
$stat_pages_icon    = get_theme_mod( 'themisdb_home_stat_pages_icon', '📄' );
$stat_pages_label   = get_theme_mod( 'themisdb_home_stat_pages_label', esc_html__( 'Seiten', 'themisdb' ) );
$stat_categories_icon  = get_theme_mod( 'themisdb_home_stat_categories_icon', '🗂️' );
$stat_categories_label = get_theme_mod( 'themisdb_home_stat_categories_label', esc_html__( 'Kategorien', 'themisdb' ) );
$stat_tags_icon     = get_theme_mod( 'themisdb_home_stat_tags_icon', '🏷️' );
$stat_tags_label    = get_theme_mod( 'themisdb_home_stat_tags_label', esc_html__( 'Tags', 'themisdb' ) );

$show_intro_section = (bool) get_theme_mod( 'themisdb_home_show_intro_section', true );
$intro_eyebrow      = get_theme_mod( 'themisdb_home_intro_eyebrow', esc_html__( 'Einleitung', 'themisdb' ) );
$intro_title        = get_theme_mod( 'themisdb_home_intro_title', esc_html__( 'Was diese Seite bietet', 'themisdb' ) );

$latest_section_eyebrow   = get_theme_mod( 'themisdb_home_latest_eyebrow', esc_html__( 'Aktuell', 'themisdb' ) );
$latest_section_title     = get_theme_mod( 'themisdb_home_latest_title', esc_html__( 'Neueste Artikel', 'themisdb' ) );
$latest_section_link_text = get_theme_mod( 'themisdb_home_latest_link_label', esc_html__( 'Alle Artikel ansehen', 'themisdb' ) );
$latest_card_cta_label    = get_theme_mod( 'themisdb_home_latest_lead_cta_label', esc_html__( 'Artikel lesen', 'themisdb' ) );
$latest_card_words        = 24;

$latest_query = new WP_Query( array(
    'posts_per_page'      => $latest_query_limit,
    'post_status'         => 'publish',
    'ignore_sticky_posts' => true,
    'no_found_rows'       => true,
) );

if ( $latest_query->have_posts() ) {
    $latest_posts = $latest_query->posts;
} else {
    $latest_posts = array();
}
wp_reset_postdata();
?>

<main id="primary" class="content-area">
    <?php
    $front_page_has_post = false;
    $hero_title          = get_bloginfo( 'name' );
    $hero_intro          = '';
    $downloads_url       = $url_downloads;

    if ( have_posts() ) {
        the_post();
        $front_page_has_post = true;

        $hero_intro = has_excerpt()
            ? get_the_excerpt()
            : wp_trim_words( wp_strip_all_tags( get_the_content() ), 34 );
    }

    $hero_subtitle = ! empty( $hero_subtitle_mod ) ? $hero_subtitle_mod : $hero_intro;

    $published_posts = wp_count_posts( 'post' );
    $published_pages = wp_count_posts( 'page' );
    $category_count  = wp_count_terms( array( 'taxonomy' => 'category', 'hide_empty' => true ) );
    $tag_count       = wp_count_terms( array( 'taxonomy' => 'post_tag', 'hide_empty' => true ) );
    $category_total  = is_wp_error( $category_count ) ? 0 : (int) $category_count;
    $tag_total       = is_wp_error( $tag_count ) ? 0 : (int) $tag_count;

    $intro_preview_words = 70;
    $intro_teaser        = '';
    $intro_full_content  = '';
    $show_intro_toggle   = false;

    if ( $front_page_has_post ) {
        $intro_full_content = (string) get_post_field( 'post_content', get_the_ID() );
        $intro_plain_text   = trim( wp_strip_all_tags( $intro_full_content ) );

        if ( '' !== $intro_plain_text ) {
            $intro_word_count = str_word_count( $intro_plain_text );
            $intro_teaser     = wp_trim_words( $intro_plain_text, $intro_preview_words );
            $show_intro_toggle = $intro_word_count > $intro_preview_words;
        }
    }
    ?>

        <!-- SLIDER: Latest Articles – Top of Page -->
        <?php if ( ! empty( $latest_posts ) ) : ?>
            <section class="featured-slider-section homepage-latest-articles <?php echo $show_latest_articles ? '' : 'homepage-hidden'; ?>" id="homepage-latest-articles">
                <div class="homepage-section-header homepage-section-header-split">
                    <div>
                        <span class="homepage-section-eyebrow" id="homepage-latest-eyebrow" data-default="<?php echo esc_attr( esc_html__( 'Aktuell', 'themisdb' ) ); ?>"><?php echo esc_html( $latest_section_eyebrow ); ?></span>
                        <h2 class="homepage-section-title" id="homepage-latest-title" data-default="<?php echo esc_attr( esc_html__( 'Neueste Artikel', 'themisdb' ) ); ?>"><?php echo esc_html( $latest_section_title ); ?></h2>
                    </div>
                    <?php if ( $has_posts_page ) : ?>
                        <a id="homepage-latest-link" class="homepage-text-link" data-default="<?php echo esc_attr( esc_html__( 'Alle Artikel ansehen', 'themisdb' ) ); ?>" href="<?php echo esc_url( $posts_page_url ); ?>"><?php echo esc_html( $latest_section_link_text ); ?></a>
                    <?php endif; ?>
                </div>

                <div class="themisdb-slider-container homepage-slider">
                    <div class="themisdb-slider">
                        <?php foreach ( $latest_posts as $post_item ) : ?>
                            <?php
                            $card_excerpt_source = get_the_excerpt( $post_item->ID ) ?: wp_strip_all_tags( get_post_field( 'post_content', $post_item->ID ) );
                            $card_excerpt = wp_trim_words( $card_excerpt_source, $latest_card_words );
                            ?>
                            <article class="slider-item">
                                <div class="slider-image">
                                    <a href="<?php echo esc_url( get_permalink( $post_item->ID ) ); ?>">
                                        <?php
                                        $card_image = get_the_post_thumbnail( $post_item->ID, 'themisdb-featured' );
                                        if ( ! empty( $card_image ) ) {
                                            echo $card_image;
                                        } else {
                                            echo '<span class="homepage-image-fallback" aria-hidden="true"><span>Kein Bild</span></span>';
                                        }
                                        ?>
                                    </a>
                                </div>
                                <div class="slider-content">
                                    <h3 class="slider-title">
                                        <a href="<?php echo esc_url( get_permalink( $post_item->ID ) ); ?>"><?php echo esc_html( get_the_title( $post_item->ID ) ); ?></a>
                                    </h3>
                                    <div class="slider-meta">
                                        <span class="slider-date"><?php echo esc_html( get_the_date( '', $post_item->ID ) ); ?></span>
                                        <?php
                                        $card_category_list = get_the_category_list( ', ', '', $post_item->ID );
                                        if ( $card_category_list ) :
                                            ?>
                                            <span class="slider-category"> • <?php echo wp_kses_post( $card_category_list ); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="slider-excerpt"><?php echo esc_html( $card_excerpt ); ?></div>
                                    <a class="slider-readmore" data-default="<?php echo esc_attr( esc_html__( 'Artikel lesen', 'themisdb' ) ); ?>" href="<?php echo esc_url( get_permalink( $post_item->ID ) ); ?>"><?php echo esc_html( $latest_card_cta_label ); ?> →</a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <?php if ( count( $latest_posts ) > 1 ) : ?>
                        <button class="slider-nav slider-prev" aria-label="<?php esc_attr_e( 'Vorheriger Artikel', 'themisdb' ); ?>">‹</button>
                        <button class="slider-nav slider-next" aria-label="<?php esc_attr_e( 'Naechster Artikel', 'themisdb' ); ?>">›</button>
                        <div class="slider-dots"></div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- MAIN NAVIGATION: 4x3 Card Grid -->
        <section class="homepage-main-navigation">
            <div class="homepage-section-header">
                <span class="homepage-section-eyebrow"><?php esc_html_e( 'Navigation', 'themisdb' ); ?></span>
                <h2 class="homepage-section-title"><?php esc_html_e( 'Direkter Einstieg', 'themisdb' ); ?></h2>
            </div>

            <div class="horizontal-container">
                <div class="main-nav-grid">
                    <a href="<?php echo esc_url( $url_dokumentation ); ?>" class="main-nav-card">
                        <div class="nav-card-icon">📖</div>
                        <h3><?php esc_html_e( 'Dokumentation', 'themisdb' ); ?></h3>
                        <p><?php esc_html_e( 'Umfassende Guides & API-Dokumentation', 'themisdb' ); ?></p>
                    </a>
                    <a href="<?php echo esc_url( $downloads_url ); ?>" class="main-nav-card">
                        <div class="nav-card-icon">⬇️</div>
                        <h3><?php esc_html_e( 'Download', 'themisdb' ); ?></h3>
                        <p><?php esc_html_e( 'ThemisDB & Plugins kostenlos herunterladen', 'themisdb' ); ?></p>
                    </a>
                    <a href="<?php echo esc_url( $url_support ); ?>" class="main-nav-card">
                        <div class="nav-card-icon">❓</div>
                        <h3><?php esc_html_e( 'Support', 'themisdb' ); ?></h3>
                        <p><?php esc_html_e( 'Hilfe & Community Support', 'themisdb' ); ?></p>
                    </a>
                    <a href="<?php echo esc_url( $url_features ); ?>" class="main-nav-card">
                        <div class="nav-card-icon">⚡</div>
                        <h3><?php esc_html_e( 'Features', 'themisdb' ); ?></h3>
                        <p><?php esc_html_e( 'Entdecken Sie alle Funktionen', 'themisdb' ); ?></p>
                    </a>
                    <a href="<?php echo esc_url( $url_roadmap ); ?>" class="main-nav-card">
                        <div class="nav-card-icon">🗺️</div>
                        <h3><?php esc_html_e( 'Roadmap', 'themisdb' ); ?></h3>
                        <p><?php esc_html_e( 'Zukünftige Entwicklung', 'themisdb' ); ?></p>
                    </a>
                    <a href="<?php echo esc_url( $url_blog ); ?>" class="main-nav-card">
                        <div class="nav-card-icon">📝</div>
                        <h3><?php esc_html_e( 'Blog', 'themisdb' ); ?></h3>
                        <p><?php esc_html_e( 'Neuigkeiten & Insights', 'themisdb' ); ?></p>
                    </a>
                    <a href="<?php echo esc_url( $url_plugins ); ?>" class="main-nav-card">
                        <div class="nav-card-icon">🔌</div>
                        <h3><?php esc_html_e( 'Plugins', 'themisdb' ); ?></h3>
                        <p><?php esc_html_e( 'Erweiterungen & Add-ons', 'themisdb' ); ?></p>
                    </a>
                    <a href="<?php echo esc_url( $url_integrations ); ?>" class="main-nav-card">
                        <div class="nav-card-icon">🔗</div>
                        <h3><?php esc_html_e( 'Integrationen', 'themisdb' ); ?></h3>
                        <p><?php esc_html_e( 'Mit anderen Systemen verbinden', 'themisdb' ); ?></p>
                    </a>
                    <a href="<?php echo esc_url( $url_performance ); ?>" class="main-nav-card">
                        <div class="nav-card-icon">🚀</div>
                        <h3><?php esc_html_e( 'Performance', 'themisdb' ); ?></h3>
                        <p><?php esc_html_e( 'Benchmarks & Optimierungen', 'themisdb' ); ?></p>
                    </a>
                    <a href="<?php echo esc_url( $url_security ); ?>" class="main-nav-card">
                        <div class="nav-card-icon">🔒</div>
                        <h3><?php esc_html_e( 'Sicherheit', 'themisdb' ); ?></h3>
                        <p><?php esc_html_e( 'Datenschutz & Compliance', 'themisdb' ); ?></p>
                    </a>
                    <a href="<?php echo esc_url( $url_pricing ); ?>" class="main-nav-card">
                        <div class="nav-card-icon">💰</div>
                        <h3><?php esc_html_e( 'Pricing', 'themisdb' ); ?></h3>
                        <p><?php esc_html_e( 'Pläne & Lizenzoptionen', 'themisdb' ); ?></p>
                    </a>
                    <a href="<?php echo esc_url( $url_community ); ?>" class="main-nav-card">
                        <div class="nav-card-icon">👥</div>
                        <h3><?php esc_html_e( 'Community', 'themisdb' ); ?></h3>
                        <p><?php esc_html_e( 'Treten Sie bei & folgen Sie uns', 'themisdb' ); ?></p>
                    </a>
                </div>
            </div>
        </section>

        <section class="hero homepage-hero">
            <div class="hero-content homepage-hero-content">
                <span class="homepage-kicker" id="homepage-hero-kicker"><?php echo esc_html( $hero_kicker ); ?></span>
                <h1><?php echo esc_html( $hero_title ); ?></h1>
                <?php if ( ! empty( $hero_subtitle ) ) : ?>
                    <p id="homepage-hero-subtitle"><?php echo esc_html( $hero_subtitle ); ?></p>
                <?php endif; ?>
                <div class="homepage-hero-actions">
                    <?php
                    $latest_cta_classes = 'button button-primary';
                    if ( ! $show_latest_articles || empty( $latest_posts ) ) {
                        $latest_cta_classes .= ' homepage-hidden';
                    }
                    ?>
                    <a id="homepage-latest-cta" class="<?php echo esc_attr( $latest_cta_classes ); ?>" href="#homepage-latest-articles"><?php echo esc_html( $latest_cta_label ); ?></a>
                    <?php if ( $has_posts_page ) : ?>
                        <a id="homepage-blog-cta" class="button homepage-secondary-button" data-default-href="<?php echo esc_url( $hero_blog_cta_url ); ?>" href="<?php echo esc_url( $hero_blog_cta_url ); ?>"><?php echo esc_html( $blog_cta_label ); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <?php if ( is_active_sidebar( 'frontpage-hero' ) ) : ?>
            <section class="homepage-widget-area homepage-widget-area-hero" aria-label="<?php esc_attr_e( 'Front Page Hero Widgets', 'themisdb' ); ?>">
                <?php dynamic_sidebar( 'frontpage-hero' ); ?>
            </section>
        <?php endif; ?>

        <section id="homepage-stats-section" class="stats-section homepage-stats <?php echo $show_stats ? '' : 'homepage-hidden'; ?>" aria-label="<?php esc_attr_e( 'Website Kennzahlen', 'themisdb' ); ?>">
            <div class="stat-box">
                <div class="stat-icon" id="homepage-stat-posts-icon" data-default="📝" aria-hidden="true"><?php echo esc_html( $stat_posts_icon ); ?></div>
                <div class="stat-number"><?php echo esc_html( number_format_i18n( (int) $published_posts->publish ) ); ?></div>
                <div class="stat-label" id="homepage-stat-posts-label" data-default="<?php echo esc_attr( esc_html__( 'Artikel', 'themisdb' ) ); ?>"><?php echo esc_html( $stat_posts_label ); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-icon" id="homepage-stat-pages-icon" data-default="📄" aria-hidden="true"><?php echo esc_html( $stat_pages_icon ); ?></div>
                <div class="stat-number"><?php echo esc_html( number_format_i18n( (int) $published_pages->publish ) ); ?></div>
                <div class="stat-label" id="homepage-stat-pages-label" data-default="<?php echo esc_attr( esc_html__( 'Seiten', 'themisdb' ) ); ?>"><?php echo esc_html( $stat_pages_label ); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-icon" id="homepage-stat-categories-icon" data-default="🗂️" aria-hidden="true"><?php echo esc_html( $stat_categories_icon ); ?></div>
                <div class="stat-number"><?php echo esc_html( number_format_i18n( max( 0, $category_total ) ) ); ?></div>
                <div class="stat-label" id="homepage-stat-categories-label" data-default="<?php echo esc_attr( esc_html__( 'Kategorien', 'themisdb' ) ); ?>"><?php echo esc_html( $stat_categories_label ); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-icon" id="homepage-stat-tags-icon" data-default="🏷️" aria-hidden="true"><?php echo esc_html( $stat_tags_icon ); ?></div>
                <div class="stat-number"><?php echo esc_html( number_format_i18n( max( 0, $tag_total ) ) ); ?></div>
                <div class="stat-label" id="homepage-stat-tags-label" data-default="<?php echo esc_attr( esc_html__( 'Tags', 'themisdb' ) ); ?>"><?php echo esc_html( $stat_tags_label ); ?></div>
            </div>
        </section>

        <section id="homepage-intro-section" class="homepage-content-block <?php echo $show_intro_section ? '' : 'homepage-hidden'; ?>">
            <div class="homepage-section-header">
                <span class="homepage-section-eyebrow" id="homepage-intro-eyebrow" data-default="<?php echo esc_attr( esc_html__( 'Einleitung', 'themisdb' ) ); ?>"><?php echo esc_html( $intro_eyebrow ); ?></span>
                <h2 class="homepage-section-title" id="homepage-intro-title" data-default="<?php echo esc_attr( esc_html__( 'Was diese Seite bietet', 'themisdb' ) ); ?>"><?php echo esc_html( $intro_title ); ?></h2>
            </div>
            <?php if ( $front_page_has_post ) : ?>
                <article class="homepage-intro-article">
                    <?php if ( '' !== $intro_teaser ) : ?>
                        <div class="entry-content">
                            <p><?php echo esc_html( $intro_teaser ); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if ( $show_intro_toggle ) : ?>
                        <details class="homepage-intro-details">
                            <summary class="button homepage-intro-toggle"><?php esc_html_e( 'Weiterlesen', 'themisdb' ); ?></summary>
                            <div class="entry-content homepage-intro-full-content">
                                <?php echo apply_filters( 'the_content', $intro_full_content ); ?>
                            </div>
                        </details>
                    <?php endif; ?>
                </article>
            <?php endif; ?>
        </section>

        <?php if ( is_active_sidebar( 'frontpage-content' ) ) : ?>
            <section class="homepage-widget-area homepage-widget-area-content" aria-label="<?php esc_attr_e( 'Front Page Content Widgets', 'themisdb' ); ?>">
                <?php dynamic_sidebar( 'frontpage-content' ); ?>
            </section>
        <?php endif; ?>

        <!-- Features Section -->
        <section class="homepage-features">
            <div class="horizontal-container">
                <div class="section-header">
                    <p class="section-eyebrow"><?php esc_html_e( 'Features', 'themisdb' ); ?></p>
                    <h2 class="section-title"><?php esc_html_e( 'ThemisDB Fähigkeiten', 'themisdb' ); ?></h2>
                    <p class="section-description"><?php esc_html_e( 'Eine hochmoderne Datenbank mit nativer KI-Integration und optimaler Performance.', 'themisdb' ); ?></p>
                </div>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">⚡</div>
                        <h3><?php esc_html_e( 'Höchste Performance', 'themisdb' ); ?></h3>
                        <p><?php esc_html_e( 'Optimiert für schnelle Abfragen und große Datenmengen.', 'themisdb' ); ?></p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">🤖</div>
                        <h3><?php esc_html_e( 'Native KI-Integration', 'themisdb' ); ?></h3>
                        <p><?php esc_html_e( 'Direkte Integration mit führenden LLM-Plattformen.', 'themisdb' ); ?></p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">🔒</div>
                        <h3><?php esc_html_e( 'Sicherheit', 'themisdb' ); ?></h3>
                        <p><?php esc_html_e( 'Enterprise-Grade Sicherheit und Datenschutz.', 'themisdb' ); ?></p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">📊</div>
                        <h3><?php esc_html_e( 'Multi-Modal', 'themisdb' ); ?></h3>
                        <p><?php esc_html_e( 'Unterstützt Vektor-, Tabellen- und Graph-Daten.', 'themisdb' ); ?></p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Getting Started Section -->
        <section class="homepage-getting-started">
            <div class="horizontal-container">
                <div class="section-header">
                    <p class="section-eyebrow"><?php esc_html_e( 'Einstieg', 'themisdb' ); ?></p>
                    <h2 class="section-title"><?php esc_html_e( 'Erste Schritte', 'themisdb' ); ?></h2>
                </div>
                <div class="getting-started-grid">
                    <a href="<?php echo esc_url( $url_dokumentation ); ?>" class="getting-started-card">
                        <div class="gs-icon">📖</div>
                        <h3><?php esc_html_e( 'Dokumentation', 'themisdb' ); ?></h3>
                        <p><?php esc_html_e( 'Umfassende Dokumentation und Tutorials', 'themisdb' ); ?></p>
                        <span class="gs-arrow">→</span>
                    </a>
                    <a href="<?php echo esc_url( $downloads_url ); ?>" class="getting-started-card">
                        <div class="gs-icon">⬇️</div>
                        <h3><?php esc_html_e( 'Download', 'themisdb' ); ?></h3>
                        <p><?php esc_html_e( 'ThemisDB und Plugins herunterladen', 'themisdb' ); ?></p>
                        <span class="gs-arrow">→</span>
                    </a>
                    <a href="<?php echo esc_url( $url_support ); ?>" class="getting-started-card">
                        <div class="gs-icon">❓</div>
                        <h3><?php esc_html_e( 'Support', 'themisdb' ); ?></h3>
                        <p><?php esc_html_e( 'Fragen? Wir helfen Ihnen weiter', 'themisdb' ); ?></p>
                        <span class="gs-arrow">→</span>
                    </a>
                </div>
            </div>
        </section>

        <!-- Latest Releases Section -->
        <section class="homepage-releases">
            <div class="horizontal-container">
                <div class="section-header">
                    <p class="section-eyebrow"><?php esc_html_e( 'News', 'themisdb' ); ?></p>
                    <h2 class="section-title"><?php esc_html_e( 'Neueste Versionen', 'themisdb' ); ?></h2>
                </div>
                <?php
                $releases_query = new WP_Query( array(
                    'posts_per_page' => 3,
                    'post_status'    => 'publish',
                    'category_name'  => 'releases',
                ) );
                if ( $releases_query->have_posts() ) :
                    echo '<div class="releases-list">';
                    while ( $releases_query->have_posts() ) :
                        $releases_query->the_post();
                        ?>
                        <div class="release-item">
                            <span class="release-date"><?php echo esc_html( get_the_date( 'Y-m-d' ) ); ?></span>
                            <h3><a href="<?php echo esc_url( get_permalink() ); ?>"><?php the_title(); ?></a></h3>
                            <p><?php echo wp_trim_words( get_the_excerpt(), 20 ); ?></p>
                        </div>
                        <?php
                    endwhile;
                    echo '</div>';
                    wp_reset_postdata();
                    echo '<a href="' . esc_url( $url_releases ) . '" class="btn btn-secondary">' . esc_html__( 'Alle Versionen ansehen', 'themisdb' ) . '</a>';
                endif;
                ?>
            </div>
        </section>

        <!-- Community Section -->
        <section class="homepage-community">
            <div class="horizontal-container">
                <div class="section-header">
                    <p class="section-eyebrow"><?php esc_html_e( 'Community', 'themisdb' ); ?></p>
                    <h2 class="section-title"><?php esc_html_e( 'Verbinden Sie sich mit uns', 'themisdb' ); ?></h2>
                    <p class="section-description"><?php esc_html_e( 'Treffen Sie die ThemisDB-Community, erfahren Sie von anderen Nutzern und beteiligen Sie sich an der Entwicklung.', 'themisdb' ); ?></p>
                </div>
                <div class="community-grid">
                    <div class="community-card">
                        <h3><?php esc_html_e( 'Mailing Listen', 'themisdb' ); ?></h3>
                        <p><?php esc_html_e( 'Erhalten Sie Updates und diskutieren Sie mit der Community.', 'themisdb' ); ?></p>
                        <a href="#" class="btn btn-outline"><?php esc_html_e( 'Abonnieren', 'themisdb' ); ?></a>
                    </div>
                    <div class="community-card">
                        <h3><?php esc_html_e( 'Events', 'themisdb' ); ?></h3>
                        <p><?php esc_html_e( 'Konferenzen, Webinare und lokale Treffen weltweit.', 'themisdb' ); ?></p>
                        <a href="#" class="btn btn-outline"><?php esc_html_e( 'Events anzeigen', 'themisdb' ); ?></a>
                    </div>
                    <div class="community-card">
                        <h3><?php esc_html_e( 'Mitwirkende', 'themisdb' ); ?></h3>
                        <p><?php esc_html_e( 'Helfen Sie mit, ThemisDB zu verbessern und zu erweitern.', 'themisdb' ); ?></p>
                        <a href="#" class="btn btn-outline"><?php esc_html_e( 'Erfahren Sie mehr', 'themisdb' ); ?></a>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="homepage-cta homepage-cta-dark">
            <div class="horizontal-container">
                <h2><?php esc_html_e( 'Bereit, loszulegen?', 'themisdb' ); ?></h2>
                <p><?php esc_html_e( 'Tauchen Sie in die Leistung von ThemisDB ein.', 'themisdb' ); ?></p>
                <div class="cta-buttons">
                    <a href="<?php echo esc_url( $downloads_url ); ?>" class="btn btn-primary"><?php esc_html_e( 'Download starten', 'themisdb' ); ?></a>
                    <a href="<?php echo esc_url( $url_dokumentation ); ?>" class="btn btn-outline-light"><?php esc_html_e( 'Dokumentation lesen', 'themisdb' ); ?></a>
                </div>
            </div>
        </section>

        <?php
        if ( $front_page_has_post && ( comments_open() || get_comments_number() ) ) :
            comments_template();
        endif;
    ?>
</main>

<?php
get_sidebar();
get_footer();