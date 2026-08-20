<?php
/**
 * The template for displaying all pages
 *
 * @package ThemisDB
 */

get_header();

$has_sidebar = is_active_sidebar( 'sidebar-1' );
?>

<div class="page-layout<?php echo $has_sidebar ? ' has-active-sidebar' : ''; ?>">
    <main id="primary" class="content-area">
        <?php
        while ( have_posts() ) :
            the_post();

            get_template_part( 'template-parts/content', 'page' );

            if ( comments_open() || get_comments_number() ) :
                comments_template();
            endif;

        endwhile;
        ?>
    </main>

    <?php if ( $has_sidebar ) : ?>
        <?php get_sidebar(); ?>
    <?php endif; ?>
</div>

<?php
get_footer();
