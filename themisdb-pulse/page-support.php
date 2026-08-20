<?php
/**
 * Template Name: Support Hub
 *
 * Force the /support page to render inside the normal theme page shell so the
 * header, navigation and breadcrumbs remain visible while the support hub stays
 * nested in the page content flow.
 */

get_header();
?>

<main id="wp--skip-link--target" class="site-main tv3-front-main tv3-support-main themisdb-support-template tv3-layout-editorial">
    <div class="themisdb-support-hub-page-shell">
        <?php echo do_shortcode( '[themisdb_v3_breadcrumbs]' ); ?>
        <?php echo do_shortcode( '[themisdb_support_hub]' ); ?>
    </div>
</main>

<?php
get_footer();
