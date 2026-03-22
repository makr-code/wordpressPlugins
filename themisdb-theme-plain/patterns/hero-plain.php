<?php
/**
 * Title: Hero Plain
 * Slug: themisdb-plain/hero-plain
 * Categories: themisdb-plain
 * Description: Schlichter Hero-Bereich im ThemisDB-Farbschema mit zwei Aktionen.
 */
?>
<!-- wp:group {"align":"full","style":{"color":{"gradient":"var:preset|gradient|themisdb-hero"},"spacing":{"padding":{"top":"64px","bottom":"64px","left":"20px","right":"20px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-themisdb-hero-gradient-background has-background" style="background:var(--wp--preset--gradient--themisdb-hero);padding-top:64px;padding-right:20px;padding-bottom:64px;padding-left:20px">
    <!-- wp:group {"align":"wide","layout":{"type":"constrained"}} -->
    <div class="wp-block-group alignwide">
        <!-- wp:paragraph {"style":{"color":{"text":"#d8ecff"},"typography":{"fontSize":"0.9rem"}}} -->
        <p class="has-text-color has-custom-font-size" style="color:#d8ecff;font-size:0.9rem">THEMISDB PLATFORM</p>
        <!-- /wp:paragraph -->

        <!-- wp:heading {"level":1,"style":{"color":{"text":"#ffffff"},"typography":{"fontSize":"2.4rem","lineHeight":"1.15"}}} -->
        <h1 class="wp-block-heading has-text-color has-custom-font-size" style="color:#ffffff;font-size:2.4rem;line-height:1.15">ThemisDB plain, klar und fokussiert</h1>
        <!-- /wp:heading -->

        <!-- wp:paragraph {"style":{"color":{"text":"#e8f4ff"},"typography":{"fontSize":"1.05rem"}}} -->
        <p class="has-text-color has-custom-font-size" style="color:#e8f4ff;font-size:1.05rem">Eine reduzierte Startsektion fuer Produkt, Dokumentation und Support ohne visuelle Unruhe.</p>
        <!-- /wp:paragraph -->

        <!-- wp:buttons -->
        <div class="wp-block-buttons">
            <!-- wp:button {"backgroundColor":"white","textColor":"themisdb-primary"} -->
            <div class="wp-block-button"><a class="wp-block-button__link has-themisdb-primary-color has-white-background-color has-text-color has-background wp-element-button">Jetzt starten</a></div>
            <!-- /wp:button -->

            <!-- wp:button {"className":"is-style-themisdb-outline"} -->
            <div class="wp-block-button is-style-themisdb-outline"><a class="wp-block-button__link wp-element-button">Dokumentation</a></div>
            <!-- /wp:button -->
        </div>
        <!-- /wp:buttons -->
    </div>
    <!-- /wp:group -->
</div>
<!-- /wp:group -->
