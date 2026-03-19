<?php
/**
 * The main fallback template file.
 *
 * @package ThemisDB
 */

get_header();
?>

<main id="primary" class="content-area">
    <?php if ( have_posts() ) : ?>

        <div class="posts-container posts-grid">
            <?php
            while ( have_posts() ) :
                the_post();
                get_template_part( 'template-parts/content', get_post_type() );
            endwhile;
            ?>
        </div>

        <?php
        the_posts_pagination( array(
            'mid_size'  => 2,
            'prev_text' => __( '&laquo; Previous', 'themisdb' ),
            'next_text' => __( 'Next &raquo;', 'themisdb' ),
        ) );

    else :

        get_template_part( 'template-parts/content', 'none' );

    endif;
    ?>
</main>

<?php
get_sidebar();
get_footer();
