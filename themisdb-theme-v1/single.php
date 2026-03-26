<?php
/**
 * The template for displaying all single posts
 *
 * @package ThemisDB
 */

get_header();

$has_sidebar = is_active_sidebar( 'sidebar-1' );
?>
<?php
// Display breadcrumb navigation above the two-column single-post layout.
themisdb_breadcrumbs();
?>

<div class="single-post-layout<?php echo $has_sidebar ? ' has-active-sidebar' : ''; ?>">
    <main id="primary" class="content-area">
        <?php
        while ( have_posts() ) :
            the_post();

            get_template_part( 'template-parts/content', 'single' );

            // Social share buttons
            themisdb_social_share_buttons();

            the_post_navigation( array(
                'prev_text' => '<span class="nav-subtitle">⬅️ ' . esc_html__( 'Previous:', 'themisdb' ) . '</span> <span class="nav-title">%title</span>',
                'next_text' => '<span class="nav-subtitle">' . esc_html__( 'Next:', 'themisdb' ) . ' ➡️</span> <span class="nav-title">%title</span>',
            ) );

            // If comments are open or we have at least one comment, load up the comment template.
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
