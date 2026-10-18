<?php
/**
 * Front page template for the static homepage in the local WordPress theme.
 * This ensures the homepage renders the shortcode-driven landing page instead of
 * stale legacy content saved on the selected static Home page.
 */

get_header();
?>

<div class="wp-site-blocks">
    <?php echo do_shortcode( '[themisdb_v3_announcement_bar category="announcement" limit="5" link_label="Mehr erfahren →"]' ); ?>

    <section class="wp-block-group tv3-frontpage-hero-shell tv3-hero-slider-shell" style="margin-top:0;">
        <div class="wp-block-group tv3-frontpage-hero-shell tv3-hero-slider-shell">
            <?php echo do_shortcode( '[themisdb_front_slider posts="5" interval="5000" autoplay="yes" excerpt="yes" date="yes" cat_label="yes"]' ); ?>
        </div>
    </section>

    <?php echo do_shortcode( '[themisdb_v3_breadcrumbs]' ); ?>

    <main id="wp--skip-link--target" class="wp-block-group site-main tv3-front-main tv3-layout-editorial tv3-archive-main-shell" style="padding-bottom: 3rem;">
        <div class="wp-block-group tv3-feature-section has-global-padding is-layout-constrained">
            <p class="has-text-align-center tv3-feature-section__badge-wrap"><span class="tv3-feature-section__badge">Features</span></p>
            <h2 class="wp-block-heading tv3-feature-section__title" id="everything-you-need-in-one-database">Everything you need in one database</h2>
            <p class="tv3-feature-section__lead">Designed from the ground up for modern applications that need speed, flexibility, and intelligence.</p>
            <?php echo do_shortcode( '[themisdb_v3_feature_cards limit="6" post_types="post,page" category="feature,hero" priority_tag="feature,hero,recommended,editor-pick" excerpt_words="24"]' ); ?>
        </div>

        <div class="wp-block-group tv3-benchmark-section has-global-padding is-layout-constrained">
            <p class="has-text-align-center tv3-docs-grid-kicker-wrap"><span class="tv3-docs-grid-kicker">Benchmarks</span></p>
            <h2 class="wp-block-heading has-text-align-center tv3-docs-grid-title">Performance-Benchmarks</h2>
            <p class="has-text-align-center tv3-docs-grid-subtitle">Vergleiche der wichtigsten Betriebs- und Analyse-Workloads in einer interaktiven Übersicht.</p>
            <?php if ( class_exists( 'ThemisDB_Benchmark_Visualizer' ) ) : ?>
                <?php echo ThemisDB_Benchmark_Visualizer::get_instance()->render_visualizer( array( 'category' => 'all', 'metric' => 'latency' ) ); ?>
            <?php elseif ( shortcode_exists( 'themisdb_benchmark_visualizer' ) ) : ?>
                <?php echo do_shortcode( '[themisdb_benchmark_visualizer category="all" metric="latency"]' ); ?>
            <?php endif; ?>
        </div>

        <div class="wp-block-group tv3-docs-grid-section has-global-padding is-layout-constrained">
            <p class="has-text-align-center tv3-docs-grid-kicker-wrap"><span class="tv3-docs-grid-kicker">Docs</span></p>
            <h2 class="wp-block-heading has-text-align-center tv3-docs-grid-title">Dokumentation &amp; Leitfaeden</h2>
            <p class="has-text-align-center tv3-docs-grid-subtitle">Schneller Einstieg, Referenzen und praxisnahe Anleitungen fuer den produktiven Einsatz.</p>
            <?php echo do_shortcode( '[themisdb_v3_docs_cards limit="8" post_types="post,page" category="documentation" priority_tag="documentation,recommended,editor-pick" excerpt_words="24"]' ); ?>
        </div>

        <div class="wp-block-group tv3-pricing-section has-global-padding is-layout-constrained">
            <p class="has-text-align-center tv3-pricing-section__badge-wrap"><span class="tv3-pricing-section__badge">Pricing</span></p>
            <h2 class="wp-block-heading tv3-pricing-section__title">Editionen &amp; Preise</h2>
            <p class="tv3-pricing-section__lead">Vergleiche Community-, Cloud- und Enterprise-Optionen fuer dein Einsatzszenario.</p>
            <?php echo do_shortcode( '[themisdb_v3_pricing_cards limit="6" post_types="page,post" category="pricing" priority_tag="pricing,recommended,editor-pick" excerpt_words="18"]' ); ?>
        </div>

        <div class="wp-block-group tv3-download-section has-global-padding is-layout-constrained">
            <p class="has-text-align-center tv3-download-kicker-wrap"><span class="tv3-download-kicker">Downloads</span></p>
            <h2 class="wp-block-heading has-text-align-center tv3-download-title">Pakete &amp; Artefakte</h2>
            <p class="has-text-align-center tv3-download-subtitle">Finde passende Builds, Docker-Images und Begleitpakete fuer Entwicklung und Betrieb.</p>
            <?php echo do_shortcode( '[themisdb_v3_download_cards limit="6" post_types="page" category="download" priority_tag="download,recommended,editor-pick" excerpt_words="18"]' ); ?>
        </div>

        <?php echo do_shortcode( '[themisdb_v3_blog_section limit="9" post_types="post" category="blog" priority_tag="blog,recommended,editor-pick" excerpt_words="24"]' ); ?>
    </main>
</div>

<?php
wp_footer();
?>
</body>
</html>
