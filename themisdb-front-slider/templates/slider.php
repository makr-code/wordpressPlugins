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
?>
<div
    class="themisdb-fs-wrapper la-hero-slider themisdb-fs-preset-<?php echo esc_attr( $layout_preset ); ?>"
    id="<?php echo esc_attr( $slider_id ); ?>"
    data-interval="<?php echo esc_attr( $interval ); ?>"
    data-autoplay="<?php echo $autoplay ? '1' : '0'; ?>"
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
                $categories   = get_the_category();
                $first_cat    = ! empty( $categories ) ? $categories[0] : null;
                $has_thumb    = has_post_thumbnail();
                $thumb_id     = $has_thumb ? get_post_thumbnail_id( $post_id ) : 0;
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
                        <?php if ( $show_category && $first_cat ) : ?>
                        <a
                            class="themisdb-fs-category"
                            href="<?php echo esc_url( get_category_link( $first_cat->term_id ) ); ?>"
                            tabindex="<?php echo $is_active ? '0' : '-1'; ?>"
                        >
                            <?php echo esc_html( $first_cat->name ); ?>
                        </a>
                        <?php endif; ?>

                        <h2 class="themisdb-fs-title la-section-title">
                            <a
                                href="<?php the_permalink(); ?>"
                                tabindex="<?php echo $is_active ? '0' : '-1'; ?>"
                            >
                                <?php the_title(); ?>
                            </a>
                        </h2>

                        <?php if ( $show_excerpt ) : ?>
                        <?php $excerpt = get_the_excerpt(); ?>
                        <?php if ( '' !== $excerpt ) : ?>
                        <p class="themisdb-fs-excerpt">
                            <?php echo wp_kses_post( $excerpt ); ?>
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
                                aria-label="<?php echo esc_attr( sprintf( $readmore_aria_format, $readmore_text, get_the_title() ) ); ?>"
                            >
                                <?php echo esc_html( $readmore_text ); ?>
                            </a>
                        </div>
                    </div>

                    <?php if ( $has_thumb ) : ?>
                    <!-- Right column: featured image in card -->
                    <div class="themisdb-fs-slide-image">
                        <div class="themisdb-fs-image-card">
                            <?php
                            echo wp_get_attachment_image(
                                $thumb_id,
                                $image_size,
                                false,
                                array(
                                    'loading'       => $is_active ? 'eager' : 'lazy',
                                    'fetchpriority' => $is_active ? 'high' : 'auto',
                                    'decoding'      => 'async',
                                )
                            );
                            ?>
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
