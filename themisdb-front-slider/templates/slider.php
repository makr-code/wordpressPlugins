<?php
/**
 * Template for [themisdb_front_slider] shortcode output.
 *
 * Available variables (from shortcode callback):
 *   $query          – WP_Query with posts
 *   $posts_count    – int
 *   $interval       – int (ms)
 *   $show_excerpt   – bool
 *   $show_date      – bool
 *   $show_category  – bool
 *   $autoplay       – bool
 *   $accent_color   – string (#hex)
 *   $readmore_text  – string
 *   $image_size     – string (thumbnail|medium|large|full)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$slider_id = 'themisdb-fs-' . uniqid();
$labels = isset( $labels ) && is_array( $labels ) ? $labels : array();
$region_label = isset( $labels['region'] ) ? (string) $labels['region'] : '';
$previous_label = isset( $labels['previous'] ) ? (string) $labels['previous'] : '';
$next_label = isset( $labels['next'] ) ? (string) $labels['next'] : '';
$pagination_label = isset( $labels['pagination'] ) ? (string) $labels['pagination'] : '';
$slide_label_format = isset( $labels['slide'] ) ? (string) $labels['slide'] : '';
$readmore_aria_format = isset( $labels['readmore_aria'] ) ? (string) $labels['readmore_aria'] : '';
$has_multiple_slides = $query->post_count > 1;
$is_hero_context = isset( $hero_label ) && '' !== (string) $hero_label;
$respect_reduced_motion = isset( $respect_reduced_motion ) ? (bool) $respect_reduced_motion : true;
?>
<div
    class="themisdb-fs-wrapper la-hero-slider themisdb-fs-preset-<?php echo esc_attr( $layout_preset ); ?><?php echo $is_hero_context ? ' themisdb-fs-context-hero' : ''; ?><?php echo $respect_reduced_motion ? '' : ' themisdb-fs-force-motion'; ?>"
    id="<?php echo esc_attr( $slider_id ); ?>"
    data-interval="<?php echo esc_attr( $interval ); ?>"
    data-autoplay="<?php echo $autoplay ? '1' : '0'; ?>"
    data-respect-reduced-motion="<?php echo $respect_reduced_motion ? '1' : '0'; ?>"
    data-preset="<?php echo esc_attr( $layout_preset ); ?>"
    style="--tfs-accent: <?php echo esc_attr( $accent_color ); ?>;"
    role="region"
    aria-label="<?php echo esc_attr( $region_label ); ?>"
    aria-roledescription="carousel"
>
    <!-- Track -->
    <div class="themisdb-fs-track-outer">
        <div
            class="themisdb-fs-track la-hero-track"
            aria-live="<?php echo $autoplay ? 'off' : 'polite'; ?>"
        >
            <?php
            $slide_index = 0;
            while ( $query->have_posts() ) :
                $query->the_post();
                $post_id      = get_the_ID();
                $raw_title    = wp_strip_all_tags( (string) get_the_title( $post_id ) );
                $display_title = $is_hero_context ? wp_trim_words( $raw_title, 18, '…' ) : $raw_title;
                $categories   = get_the_category();
                $first_cat    = ! empty( $categories ) ? $categories[0] : null;
                $tags         = get_the_tags( $post_id );
                $ribbons      = array();
                if ( ! empty( $categories ) ) {
                    foreach ( $categories as $cat_term ) {
                        if ( ! isset( $cat_term->term_id ) ) {
                            continue;
                        }
                        $ribbons[] = array(
                            'label' => (string) $cat_term->name,
                            'url'   => (string) get_category_link( (int) $cat_term->term_id ),
                        );
                    }
                }
                if ( ! empty( $tags ) && is_array( $tags ) ) {
                    foreach ( $tags as $tag_term ) {
                        if ( ! isset( $tag_term->term_id ) ) {
                            continue;
                        }
                        $ribbons[] = array(
                            'label' => (string) $tag_term->name,
                            'url'   => (string) get_tag_link( (int) $tag_term->term_id ),
                        );
                    }
                }
                $ribbons = array_values( array_unique( $ribbons, SORT_REGULAR ) );
                $ribbons = array_slice( $ribbons, 0, 5 );
                $has_thumb    = has_post_thumbnail();
                $thumb_id     = $has_thumb ? get_post_thumbnail_id( $post_id ) : 0;
                $hero_images  = array();
                if ( $has_thumb ) {
                    $featured_src = wp_get_attachment_image_url( $thumb_id, $image_size );
                    if ( is_string( $featured_src ) && '' !== $featured_src ) {
                        $hero_images[] = $featured_src;
                    }
                }

                $post_content = (string) get_post_field( 'post_content', $post_id );
                if ( '' !== $post_content ) {
                    $content_html = (string) apply_filters( 'the_content', $post_content );
                    if ( preg_match_all( '/<img[^>]+src=["\']([^"\']+)["\']/i', $content_html, $content_matches ) ) {
                        foreach ( $content_matches[1] as $content_src ) {
                            $content_src = trim( (string) $content_src );
                            if ( '' === $content_src ) {
                                continue;
                            }
                            $hero_images[] = $content_src;
                        }
                    }
                }

                $hero_images = array_values(
                    array_unique(
                        array_filter(
                            array_map( 'esc_url_raw', $hero_images )
                        )
                    )
                );
                $hero_images = array_slice( $hero_images, 0, 4 );
                $extra_cta_buttons = $is_hero_context ? themisdb_fs_get_post_cta_buttons( $post_id, 2 ) : array();
                $is_active    = ( 0 === $slide_index );
            ?>
            <div
                class="themisdb-fs-slide la-hero-slide<?php echo $is_active ? ' is-active' : ''; ?>"
                role="group"
                aria-roledescription="slide"
                aria-label="<?php echo esc_attr( sprintf( $slide_label_format, $slide_index + 1, $query->post_count ) ); ?>"
                aria-hidden="<?php echo $is_active ? 'false' : 'true'; ?>"
            >
                <div class="themisdb-fs-slide-inner">

                    <!-- Left column: text content -->
                    <div class="themisdb-fs-slide-content la-slide-content">
                        <?php if ( $show_category && ! empty( $ribbons ) ) : ?>
                        <div class="themisdb-fs-ribbons" role="list" aria-label="Beitrags-Labels">
                            <?php foreach ( $ribbons as $ribbon ) : ?>
                            <a
                                class="themisdb-fs-category themisdb-fs-ribbon"
                                href="<?php echo esc_url( (string) $ribbon['url'] ); ?>"
                                tabindex="<?php echo $is_active ? '0' : '-1'; ?>"
                                role="listitem"
                            >
                                <?php echo esc_html( (string) $ribbon['label'] ); ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <h2 class="themisdb-fs-title la-section-title">
                            <a
                                href="<?php the_permalink(); ?>"
                                tabindex="<?php echo $is_active ? '0' : '-1'; ?>"
                            >
                                <?php echo esc_html( $display_title ); ?>
                            </a>
                        </h2>

                        <?php if ( $show_excerpt ) : ?>
                        <?php
                        $excerpt = wp_strip_all_tags( (string) get_the_excerpt( $post_id ) );
                        if ( $is_hero_context ) {
                            $excerpt = wp_trim_words( $excerpt, 32, '…' );
                        }
                        ?>
                        <?php if ( '' !== $excerpt ) : ?>
                        <p class="themisdb-fs-excerpt">
                            <?php echo esc_html( $excerpt ); ?>
                        </p>
                        <?php endif; ?>
                        <?php endif; ?>

                        <div class="themisdb-fs-meta">
                            <?php if ( $show_date ) : ?>
                            <time
                                class="themisdb-fs-date"
                                datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"
                            >
                                <?php echo esc_html( get_the_date() ); ?>
                            </time>
                            <?php endif; ?>
                            <a
                                class="themisdb-fs-readmore"
                                href="<?php the_permalink(); ?>"
                                tabindex="<?php echo $is_active ? '0' : '-1'; ?>"
                                aria-label="<?php echo esc_attr( sprintf( $readmore_aria_format, $readmore_text, $raw_title ) ); ?>"
                            >
                                <?php echo esc_html( $readmore_text ); ?>
                            </a>

                            <?php if ( ! empty( $extra_cta_buttons ) ) : ?>
                            <?php foreach ( $extra_cta_buttons as $button_index => $cta_button ) : ?>
                            <?php
                                $style = isset( $cta_button['style'] ) ? sanitize_key( (string) $cta_button['style'] ) : 'secondary';
                                $style_class = 'secondary' === $style ? 'themisdb-fs-btn-secondary' : 'themisdb-fs-btn-tertiary';
                            ?>
                            <a
                                class="themisdb-fs-readmore <?php echo esc_attr( $style_class ); ?>"
                                href="<?php echo esc_url( (string) $cta_button['url'] ); ?>"
                                tabindex="<?php echo $is_active ? '0' : '-1'; ?>"
                            >
                                <?php echo esc_html( (string) $cta_button['label'] ); ?>
                            </a>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ( ! empty( $hero_images ) ) : ?>
                    <!-- Right column: featured image in card -->
                    <div class="themisdb-fs-slide-image">
                        <div class="themisdb-fs-image-card" data-image-count="<?php echo esc_attr( count( $hero_images ) ); ?>">
                            <div class="themisdb-fs-image-stack" data-image-count="<?php echo esc_attr( count( $hero_images ) ); ?>">
                                <?php foreach ( $hero_images as $image_index => $image_src ) : ?>
                                <img
                                    class="themisdb-fs-layer-image<?php echo 0 === $image_index ? ' is-layer-active' : ''; ?>"
                                    src="<?php echo esc_url( $image_src ); ?>"
                                    alt="<?php echo esc_attr( $raw_title ); ?>"
                                    loading="<?php echo ( $is_active && 0 === $image_index ) ? 'eager' : 'lazy'; ?>"
                                    fetchpriority="<?php echo ( $is_active && 0 === $image_index ) ? 'high' : 'auto'; ?>"
                                    decoding="async"
                                />
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="themisdb-fs-blob-1" aria-hidden="true"></div>
                        <div class="themisdb-fs-blob-2" aria-hidden="true"></div>
                    </div>
                    <?php endif; ?>

                </div><!-- .themisdb-fs-slide-inner -->
            </div>
            <?php
                $slide_index++;
            endwhile;
            ?>
        </div><!-- .themisdb-fs-track -->
    </div><!-- .themisdb-fs-track-outer -->

    <?php if ( $has_multiple_slides ) : ?>
    <button
        class="themisdb-fs-btn themisdb-fs-prev la-slider-arrow la-slider-arrow-prev"
        aria-label="<?php echo esc_attr( $previous_label ); ?>"
        aria-controls="<?php echo esc_attr( $slider_id ); ?>"
    >
        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <polyline points="15 18 9 12 15 6"></polyline>
        </svg>
    </button>
    <button
        class="themisdb-fs-btn themisdb-fs-next la-slider-arrow la-slider-arrow-next"
        aria-label="<?php echo esc_attr( $next_label ); ?>"
        aria-controls="<?php echo esc_attr( $slider_id ); ?>"
    >
        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <polyline points="9 18 15 12 9 6"></polyline>
        </svg>
    </button>

    <div class="themisdb-fs-dots la-slider-dots" role="tablist" aria-label="<?php echo esc_attr( $pagination_label ); ?>">
        <?php for ( $i = 0; $i < $slide_index; $i++ ) : ?>
        <button
            class="themisdb-fs-dot la-slider-dot<?php echo ( 0 === $i ) ? ' is-active' : ''; ?>"
            role="tab"
            aria-selected="<?php echo ( 0 === $i ) ? 'true' : 'false'; ?>"
            aria-label="<?php echo esc_attr( sprintf( $slide_label_format, $i + 1, $slide_index ) ); ?>"
            data-index="<?php echo esc_attr( $i ); ?>"
        ></button>
        <?php endfor; ?>
    </div>

    <div class="themisdb-fs-timer-bar" aria-hidden="true">
        <div class="themisdb-fs-timer-fill la-hero-progress-bar"></div>
    </div>
    <?php endif; ?>
</div>
