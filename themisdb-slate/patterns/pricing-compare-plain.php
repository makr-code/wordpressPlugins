<?php
/**
 * Title: Pricing Comparison Plain
 * Slug: themisdb-plain/pricing-compare-plain
 * Categories: themisdb-plain
 * Description: Schlichte Preisvergleichssektion fuer Community und Enterprise.
 */
?>
<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"28px","bottom":"28px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide" style="padding-top:28px;padding-bottom:28px">
    <!-- wp:heading {"textAlign":"center","level":2} -->
    <h2 class="wp-block-heading has-text-align-center">Editionen im Vergleich</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"align":"center","style":{"color":{"text":"var:preset|color|themisdb-text-muted"}}} -->
    <p class="has-text-align-center has-themisdb-text-muted-color has-text-color">Plain dargestellt, damit Unterschiede schnell sichtbar sind.</p>
    <!-- /wp:paragraph -->

    <!-- wp:columns {"align":"wide"} -->
    <div class="wp-block-columns alignwide">
        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:group {"className":"themisdb-plain-card","style":{"spacing":{"padding":{"top":"22px","right":"22px","bottom":"22px","left":"22px"}},"border":{"radius":"12px"}},"layout":{"type":"constrained"}} -->
            <div class="wp-block-group themisdb-plain-card" style="border-radius:12px;padding-top:22px;padding-right:22px;padding-bottom:22px;padding-left:22px">
                <!-- wp:heading {"level":3} -->
                <h3 class="wp-block-heading">Community</h3>
                <!-- /wp:heading -->

                <!-- wp:paragraph {"style":{"typography":{"fontSize":"1.8rem","fontWeight":"700"},"color":{"text":"var:preset|color|themisdb-primary"}}} -->
                <p class="has-themisdb-primary-color has-text-color" style="font-size:1.8rem;font-weight:700">0 EUR</p>
                <!-- /wp:paragraph -->

                <!-- wp:list -->
                <ul class="wp-block-list">
                    <li>Core-Funktionen</li>
                    <li>Basis-Dokumentation</li>
                    <li>Community Support</li>
                </ul>
                <!-- /wp:list -->

                <!-- wp:button {"className":"is-style-themisdb-outline"} -->
                <div class="wp-block-button is-style-themisdb-outline"><a class="wp-block-button__link wp-element-button">Community starten</a></div>
                <!-- /wp:button -->
            </div>
            <!-- /wp:group -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:group {"className":"themisdb-plain-card","style":{"spacing":{"padding":{"top":"22px","right":"22px","bottom":"22px","left":"22px"}},"border":{"radius":"12px","color":"var:preset|color|themisdb-secondary","width":"1px"}},"layout":{"type":"constrained"}} -->
            <div class="wp-block-group themisdb-plain-card" style="border-color:var(--wp--preset--color--themisdb-secondary);border-width:1px;border-radius:12px;padding-top:22px;padding-right:22px;padding-bottom:22px;padding-left:22px">
                <!-- wp:heading {"level":3} -->
                <h3 class="wp-block-heading">Enterprise</h3>
                <!-- /wp:heading -->

                <!-- wp:paragraph {"style":{"typography":{"fontSize":"1.8rem","fontWeight":"700"},"color":{"text":"var:preset|color|themisdb-secondary"}}} -->
                <p class="has-themisdb-secondary-color has-text-color" style="font-size:1.8rem;font-weight:700">Individuell</p>
                <!-- /wp:paragraph -->

                <!-- wp:list -->
                <ul class="wp-block-list">
                    <li>Erweiterte Module</li>
                    <li>SLA und priorisierter Support</li>
                    <li>Onboarding und Betriebsberatung</li>
                </ul>
                <!-- /wp:list -->

                <!-- wp:button -->
                <div class="wp-block-button"><a class="wp-block-button__link wp-element-button">Enterprise anfragen</a></div>
                <!-- /wp:button -->
            </div>
            <!-- /wp:group -->
        </div>
        <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->
</div>
<!-- /wp:group -->
