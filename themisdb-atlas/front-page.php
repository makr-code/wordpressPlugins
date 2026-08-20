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

$latest_section_eyebrow   = get_theme_mod( 'themisdb_home_latest_eyebrow', esc_html__( 'Magazin', 'themisdb' ) );
$latest_section_title     = get_theme_mod( 'themisdb_home_latest_title', esc_html__( 'Neueste Artikel', 'themisdb' ) );
$latest_section_link_text = get_theme_mod( 'themisdb_home_latest_link_label', esc_html__( 'Alle Artikel ansehen', 'themisdb' ) );
$latest_card_cta_label    = get_theme_mod( 'themisdb_home_latest_lead_cta_label', esc_html__( 'Artikel lesen', 'themisdb' ) );
$latest_card_words        = 24;
$slider_variant           = get_theme_mod( 'themisdb_home_slider_variant', 'standard' );

if ( ! in_array( $slider_variant, array( 'standard', 'magazine', 'editorial' ), true ) ) {
    $slider_variant = 'standard';
}

// Keep the section hierarchy distinct when old defaults are still stored.
$latest_eyebrow_normalized = function_exists( 'mb_strtolower' )
    ? mb_strtolower( trim( (string) $latest_section_eyebrow ) )
    : strtolower( trim( (string) $latest_section_eyebrow ) );
$latest_title_normalized = function_exists( 'mb_strtolower' )
    ? mb_strtolower( trim( (string) $latest_section_title ) )
    : strtolower( trim( (string) $latest_section_title ) );

if ( 'aktuell' === $latest_eyebrow_normalized && false !== strpos( $latest_title_normalized, 'neueste artikel' ) ) {
    $latest_section_eyebrow = esc_html__( 'Magazin', 'themisdb' );
}

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
    $front_page_content_raw = '';

    if ( $front_page_has_post ) {
        $intro_full_content = (string) get_post_field( 'post_content', get_the_ID() );
        $front_page_content_raw = $intro_full_content;
        $intro_plain_text   = trim( wp_strip_all_tags( $intro_full_content ) );

        if ( '' !== $intro_plain_text ) {
            $intro_word_count = str_word_count( $intro_plain_text );
            $intro_teaser     = wp_trim_words( $intro_plain_text, $intro_preview_words );
            $show_intro_toggle = $intro_word_count > $intro_preview_words;
        }
    }

    // Resolve latest podcast episode smarttags if the podcast CPT exists.
    $latest_podcast_title        = '';
    $latest_podcast_excerpt      = '';
    $latest_podcast_url          = '';
    $latest_podcast_audio_url    = '';
    $latest_podcast_date         = '';
    $latest_podcast_related_url  = '';
    $latest_podcast_related_title = '';

    if ( post_type_exists( 'pod_episode' ) ) {
        $latest_podcast_query = new WP_Query( array(
            'post_type'           => 'pod_episode',
            'post_status'         => 'publish',
            'posts_per_page'      => 1,
            'ignore_sticky_posts' => true,
            'no_found_rows'       => true,
        ) );

        if ( $latest_podcast_query->have_posts() ) {
            $latest_podcast_post = $latest_podcast_query->posts[0];
            $latest_podcast_id   = (int) $latest_podcast_post->ID;
            $latest_podcast_title = get_the_title( $latest_podcast_id );
            $latest_podcast_excerpt_source = get_the_excerpt( $latest_podcast_id );

            if ( '' === trim( (string) $latest_podcast_excerpt_source ) ) {
                $latest_podcast_excerpt_source = wp_strip_all_tags( (string) get_post_field( 'post_content', $latest_podcast_id ) );
            }

            $latest_podcast_excerpt = wp_trim_words( (string) $latest_podcast_excerpt_source, 34 );
            $latest_podcast_url = get_permalink( $latest_podcast_id );
            $latest_podcast_date = get_the_date( '', $latest_podcast_id );
            $latest_podcast_audio_url = (string) get_post_meta( $latest_podcast_id, 'audio_url', true );

            $related_post_id = (int) get_post_meta( $latest_podcast_id, 'related_post_id', true );
            if ( $related_post_id > 0 ) {
                $latest_podcast_related_url = get_permalink( $related_post_id );
                $latest_podcast_related_title = get_the_title( $related_post_id );
            }
        }

        wp_reset_postdata();
    }

    // Pre-render slider HTML into a buffer so it can be injected via {{slider_output}} smarttag.
    ob_start();
    if ( ! empty( $latest_posts ) ) :
        $slider_show_class = $show_latest_articles ? '' : 'homepage-hidden';
        ?>
        <section class="featured-slider-section homepage-latest-articles homepage-slider-variant-<?php echo esc_attr( $slider_variant ); ?> <?php echo esc_attr( $slider_show_class ); ?>" id="homepage-latest-articles">
            <div class="homepage-section-header homepage-section-header-split">
                <div>
                    <span class="homepage-section-eyebrow" id="homepage-latest-eyebrow" data-default="<?php echo esc_attr( esc_html__( 'Magazin', 'themisdb' ) ); ?>"><?php echo esc_html( $latest_section_eyebrow ); ?></span>
                    <h2 class="homepage-section-title" id="homepage-latest-title" data-default="<?php echo esc_attr( esc_html__( 'Neueste Artikel', 'themisdb' ) ); ?>"><?php echo esc_html( $latest_section_title ); ?></h2>
                </div>
                <?php if ( $has_posts_page ) : ?>
                    <a id="homepage-latest-link" class="homepage-text-link" data-default="<?php echo esc_attr( esc_html__( 'Alle Artikel ansehen', 'themisdb' ) ); ?>" href="<?php echo esc_url( $posts_page_url ); ?>"><?php echo esc_html( $latest_section_link_text ); ?></a>
                <?php endif; ?>
            </div>

            <div class="themisdb-slider-container homepage-slider">
                <div class="themisdb-slider">
                    <?php foreach ( $latest_posts as $post_item ) :
                        $card_excerpt_source = get_the_excerpt( $post_item->ID ) ?: wp_strip_all_tags( get_post_field( 'post_content', $post_item->ID ) );
                        $card_excerpt = wp_trim_words( $card_excerpt_source, $latest_card_words );
                        $slider_content_variant_class = '';
                        if ( 'magazine' === $slider_variant ) {
                            $slider_content_variant_class = 'slider-content-glass';
                        } elseif ( 'editorial' === $slider_variant ) {
                            $slider_content_variant_class = 'slider-content-editorial';
                        }
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
                                <?php
                                if ( 'magazine' === $slider_variant ) {
                                    $post_categories = get_the_category( $post_item->ID );
                                    if ( ! empty( $post_categories ) && isset( $post_categories[0]->name ) ) {
                                        echo '<span class="slider-badge">' . esc_html( $post_categories[0]->name ) . '</span>';
                                    }
                                }
                                ?>
                            </div>
                            <div class="slider-content <?php echo esc_attr( $slider_content_variant_class ); ?>">
                                <h3 class="slider-title">
                                    <a href="<?php echo esc_url( get_permalink( $post_item->ID ) ); ?>"><?php echo esc_html( get_the_title( $post_item->ID ) ); ?></a>
                                </h3>
                                <div class="slider-meta">
                                    <span class="slider-date"><?php echo esc_html( get_the_date( '', $post_item->ID ) ); ?></span>
                                    <?php
                                    $card_category_list = get_the_category_list( ', ', '', $post_item->ID );
                                    if ( $card_category_list ) :
                                        ?>
                                        <span class="slider-category"> &bull; <?php echo wp_kses_post( $card_category_list ); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="slider-excerpt"><?php echo esc_html( $card_excerpt ); ?></div>
                                <a class="slider-readmore" data-default="<?php echo esc_attr( esc_html__( 'Artikel lesen', 'themisdb' ) ); ?>" href="<?php echo esc_url( get_permalink( $post_item->ID ) ); ?>"><?php echo esc_html( $latest_card_cta_label ); ?> &rarr;</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <?php if ( count( $latest_posts ) > 1 ) : ?>
                    <button class="slider-nav slider-prev" aria-label="<?php esc_attr_e( 'Vorheriger Artikel', 'themisdb' ); ?>">&#8249;</button>
                    <button class="slider-nav slider-next" aria-label="<?php esc_attr_e( 'Naechster Artikel', 'themisdb' ); ?>">&#8250;</button>
                    <div class="slider-dots"></div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif;
    $slider_output_html = ob_get_clean();

    // Smarttags for dynamic front page content authored in WordPress.
    $frontpage_smarttags = array(
        '{{site_name}}'                => esc_html( get_bloginfo( 'name' ) ),
        '{{site_description}}'         => esc_html( get_bloginfo( 'description' ) ),
        '{{hero_kicker}}'              => esc_html( $hero_kicker ),
        '{{hero_subtitle}}'            => esc_html( $hero_subtitle ),
        '{{latest_cta_label}}'         => esc_html( $latest_cta_label ),
        '{{blog_cta_label}}'           => esc_html( $blog_cta_label ),
        '{{latest_section_eyebrow}}'   => esc_html( $latest_section_eyebrow ),
        '{{latest_section_title}}'     => esc_html( $latest_section_title ),
        '{{latest_section_link_text}}' => esc_html( $latest_section_link_text ),
        '{{stat_posts}}'               => esc_html( number_format_i18n( (int) $published_posts->publish ) ),
        '{{stat_pages}}'               => esc_html( number_format_i18n( (int) $published_pages->publish ) ),
        '{{stat_categories}}'          => esc_html( number_format_i18n( max( 0, $category_total ) ) ),
        '{{stat_tags}}'                => esc_html( number_format_i18n( max( 0, $tag_total ) ) ),
        '{{url_home}}'                 => esc_url( home_url( '/' ) ),
        '{{url_posts_page}}'           => esc_url( $posts_page_url ),
        '{{url_blog_cta}}'             => esc_url( $hero_blog_cta_url ),
        '{{url_dokumentation}}'        => esc_url( $url_dokumentation ),
        '{{url_downloads}}'            => esc_url( $downloads_url ),
        '{{url_support}}'              => esc_url( $url_support ),
        '{{url_features}}'             => esc_url( $url_features ),
        '{{url_roadmap}}'              => esc_url( $url_roadmap ),
        '{{url_blog}}'                 => esc_url( $url_blog ),
        '{{url_plugins}}'              => esc_url( $url_plugins ),
        '{{url_integrations}}'         => esc_url( $url_integrations ),
        '{{url_performance}}'          => esc_url( $url_performance ),
        '{{url_security}}'             => esc_url( $url_security ),
        '{{url_pricing}}'              => esc_url( $url_pricing ),
        '{{url_community}}'            => esc_url( $url_community ),
        '{{url_releases}}'             => esc_url( $url_releases ),
        '{{podcast_latest_title}}'     => esc_html( $latest_podcast_title ),
        '{{podcast_latest_excerpt}}'   => esc_html( $latest_podcast_excerpt ),
        '{{podcast_latest_url}}'       => esc_url( $latest_podcast_url ),
        '{{podcast_latest_audio_url}}' => esc_url( $latest_podcast_audio_url ),
        '{{podcast_latest_date}}'      => esc_html( $latest_podcast_date ),
        '{{podcast_related_url}}'      => esc_url( $latest_podcast_related_url ),
        '{{podcast_related_title}}'    => esc_html( $latest_podcast_related_title ),
        '{{slider_output}}'            => $slider_output_html,
    );

    // Allow tolerant matching (e.g. {{ hero_kicker }}) and legacy aliases.
    $frontpage_smarttags_loose = array();
    foreach ( $frontpage_smarttags as $tag => $replacement ) {
        $normalized_tag = strtolower( trim( (string) $tag, "{} \t\n\r\0\x0B" ) );
        if ( '' !== $normalized_tag ) {
            $frontpage_smarttags_loose[ $normalized_tag ] = $replacement;
        }
    }

    // Backward-compatible aliases used in older drafts/templates.
    $frontpage_smarttags_loose['hero'] = $frontpage_smarttags['{{site_name}}'];
    $frontpage_smarttags_loose['hero_title'] = $frontpage_smarttags['{{site_name}}'];

    if ( '' !== trim( $front_page_content_raw ) ) {
        // Handle block-editor encoded content (&lt;section&gt;, &#123;&#123;tag&#125;&#125;) before rendering.
        $front_page_content_source = html_entity_decode( (string) $front_page_content_raw, ENT_QUOTES, 'UTF-8' );
        $frontpage_smarttags_expanded = $frontpage_smarttags;

        foreach ( $frontpage_smarttags as $tag => $replacement ) {
            $tag_escaped_numeric = strtr(
                $tag,
                array(
                    '{' => '&#123;',
                    '}' => '&#125;',
                )
            );
            $tag_escaped_hex = strtr(
                $tag,
                array(
                    '{' => '&#x7B;',
                    '}' => '&#x7D;',
                )
            );
            $tag_escaped_hex_lower = strtr(
                $tag,
                array(
                    '{' => '&#x7b;',
                    '}' => '&#x7d;',
                )
            );
            $tag_url_encoded = rawurlencode( $tag );

            $frontpage_smarttags_expanded[ $tag_escaped_numeric ] = $replacement;
            $frontpage_smarttags_expanded[ $tag_escaped_hex ] = $replacement;
            $frontpage_smarttags_expanded[ $tag_escaped_hex_lower ] = $replacement;
            $frontpage_smarttags_expanded[ $tag_url_encoded ] = $replacement;
        }

        $front_page_content_processed = strtr( $front_page_content_source, $frontpage_smarttags_expanded );
        $front_page_content_processed = preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/',
            static function( $matches ) use ( $frontpage_smarttags_loose ) {
                $loose_key = strtolower( (string) $matches[1] );
                if ( isset( $frontpage_smarttags_loose[ $loose_key ] ) ) {
                    return $frontpage_smarttags_loose[ $loose_key ];
                }

                return $matches[0];
            },
            $front_page_content_processed
        );
        ?>
        <section class="homepage-content-block homepage-dynamic-content">
            <?php echo apply_filters( 'the_content', $front_page_content_processed ); ?>
        </section>

        <?php if ( is_active_sidebar( 'frontpage-content' ) ) : ?>
            <section class="homepage-widget-area homepage-widget-area-content" aria-label="<?php esc_attr_e( 'Front Page Content Widgets', 'themisdb' ); ?>">
                <?php dynamic_sidebar( 'frontpage-content' ); ?>
            </section>
        <?php endif; ?>

        <?php
        if ( $front_page_has_post && ( comments_open() || get_comments_number() ) ) :
            comments_template();
        endif;
        ?>
        </main>

        <?php
        get_sidebar();
        get_footer();
        return;
    }
    ?>

        <!-- SLIDER: Latest Articles – Top of Page -->
        <?php if ( ! empty( $latest_posts ) ) : ?>
            <section class="featured-slider-section homepage-latest-articles homepage-slider-variant-<?php echo esc_attr( $slider_variant ); ?> <?php echo $show_latest_articles ? '' : 'homepage-hidden'; ?>" id="homepage-latest-articles">
                <div class="homepage-section-header homepage-section-header-split">
                    <div>
                        <span class="homepage-section-eyebrow" id="homepage-latest-eyebrow" data-default="<?php echo esc_attr( esc_html__( 'Magazin', 'themisdb' ) ); ?>"><?php echo esc_html( $latest_section_eyebrow ); ?></span>
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
                            $slider_content_variant_class = '';

                            if ( 'magazine' === $slider_variant ) {
                                $slider_content_variant_class = 'slider-content-glass';
                            } elseif ( 'editorial' === $slider_variant ) {
                                $slider_content_variant_class = 'slider-content-editorial';
                            }
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
                                    <?php
                                    if ( 'magazine' === $slider_variant ) {
                                        $post_categories = get_the_category( $post_item->ID );

                                        if ( ! empty( $post_categories ) && isset( $post_categories[0]->name ) ) {
                                            echo '<span class="slider-badge">' . esc_html( $post_categories[0]->name ) . '</span>';
                                        }
                                    }
                                    ?>
                                </div>
                                <div class="slider-content <?php echo esc_attr( $slider_content_variant_class ); ?>">
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