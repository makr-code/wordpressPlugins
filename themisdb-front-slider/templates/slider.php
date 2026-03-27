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
?>
<div
    class="themisdb-fs-wrapper la-hero-slider"
    id="<?php echo esc_attr( $slider_id ); ?>"
    data-interval="<?php echo esc_attr( $interval ); ?>"
    data-autoplay="<?php echo $autoplay ? '1' : '0'; ?>"
    style="--tfs-accent: <?php echo esc_attr( $accent_color ); ?>;"
    role="region"
    aria-label="<?php esc_attr_e( 'Neueste Artikel', 'themisdb-front-slider' ); ?>"
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
                $thumb_url    = $has_thumb ? get_the_post_thumbnail_url( $post_id, $image_size ) : '';
                $is_active    = ( 0 === $slide_index );
            ?>
            <div
                class="themisdb-fs-slide la-hero-slide<?php echo $is_active ? ' is-active' : ''; ?>"
                role="group"
                aria-roledescription="slide"
                aria-label="<?php echo esc_attr( sprintf( '%d / %d', $slide_index + 1, $query->post_count ) ); ?>"
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
                        <p class="themisdb-fs-excerpt">
                            <?php echo wp_kses_post( wp_trim_words( get_the_excerpt(), 25, '…' ) ); ?>
                        </p>
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
                                aria-label="<?php echo esc_attr( sprintf( __( 'Weiterlesen: %s', 'themisdb-front-slider' ), get_the_title() ) ); ?>"
                            >
                                <?php echo esc_html( $readmore_text ); ?>
                            </a>
                        </div>
                    </div>

                    <?php if ( $has_thumb ) : ?>
                    <!-- Right column: featured image in card -->
                    <div class="themisdb-fs-slide-image">
                        <div class="themisdb-fs-image-card">
                            <img
                                src="<?php echo esc_url( $thumb_url ); ?>"
                                alt="<?php echo esc_attr( get_the_title() ); ?>"
                                loading="<?php echo $is_active ? 'eager' : 'lazy'; ?>"
                            >
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

    <!-- Navigation buttons -->
    <button
        class="themisdb-fs-btn themisdb-fs-prev la-slider-arrow la-slider-arrow-prev"
        aria-label="<?php esc_attr_e( 'Vorheriger Artikel', 'themisdb-front-slider' ); ?>"
        aria-controls="<?php echo esc_attr( $slider_id ); ?>"
    >
        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <polyline points="15 18 9 12 15 6"></polyline>
        </svg>
    </button>
    <button
        class="themisdb-fs-btn themisdb-fs-next la-slider-arrow la-slider-arrow-next"
        aria-label="<?php esc_attr_e( 'Nächster Artikel', 'themisdb-front-slider' ); ?>"
        aria-controls="<?php echo esc_attr( $slider_id ); ?>"
    >
        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <polyline points="9 18 15 12 9 6"></polyline>
        </svg>
    </button>

    <!-- Dot indicators -->
    <div class="themisdb-fs-dots la-slider-dots" role="tablist" aria-label="<?php esc_attr_e( 'Slides', 'themisdb-front-slider' ); ?>">
        <?php for ( $i = 0; $i < $slide_index; $i++ ) : ?>
        <button
            class="themisdb-fs-dot la-slider-dot<?php echo ( 0 === $i ) ? ' is-active' : ''; ?>"
            role="tab"
            aria-selected="<?php echo ( 0 === $i ) ? 'true' : 'false'; ?>"
            aria-label="<?php echo esc_attr( sprintf( __( 'Slide %d', 'themisdb-front-slider' ), $i + 1 ) ); ?>"
            data-index="<?php echo esc_attr( $i ); ?>"
        ></button>
        <?php endfor; ?>
    </div>

    <!-- Timer progress bar -->
    <div class="themisdb-fs-timer-bar" aria-hidden="true">
        <div class="themisdb-fs-timer-fill la-hero-progress-bar"></div>
    </div>
</div>
