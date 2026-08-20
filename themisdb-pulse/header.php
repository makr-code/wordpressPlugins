<?php
/**
 * Full header used for custom PHP page templates such as the support page.
 */
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<!-- wp:group {"tagName":"header","align":"full","className":"site-header tv3-header-shell tv3-header-azure","layout":{"type":"constrained","contentSize":"1400px"}} -->
<header class="wp-block-group alignfull site-header tv3-header-shell tv3-header-azure">
    <!-- wp:group {"className":"tv3-header-row","layout":{"type":"flex","justifyContent":"space-between","verticalAlignment":"center","flexWrap":"wrap"}} -->
    <div class="wp-block-group tv3-header-row">
        <!-- wp:group {"layout":{"type":"flex","verticalAlignment":"center"},"className":"tv3-header-brand"} -->
        <div class="wp-block-group tv3-header-brand">
            <!-- wp:site-logo {"width":32,"shouldSyncIcon":false} /-->
            <!-- wp:site-title {"level":0,"className":"tv3-brand-title"} /-->
        </div>
        <!-- /wp:group -->

        <!-- wp:group {"className":"tv3-header-split-group tv3-header-split-left","layout":{"type":"flex","justifyContent":"left","flexWrap":"wrap"}} -->
        <div class="wp-block-group tv3-header-split-group tv3-header-split-left">
            <!-- wp:navigation {"overlayMenu":"never","className":"tv3-split-nav tv3-header-split-nav","layout":{"type":"flex","justifyContent":"left","flexWrap":"wrap"},"ariaLabel":"Produktnavigation","__unstableLocation":"primary"} /-->
        </div>
        <!-- /wp:group -->

        <!-- wp:group {"className":"tv3-header-split-group tv3-header-split-right","layout":{"type":"flex","justifyContent":"right","flexWrap":"wrap"}} -->
        <div class="wp-block-group tv3-header-split-group tv3-header-split-right">
            <!-- wp:navigation {"overlayMenu":"never","className":"tv3-split-nav tv3-header-split-nav tv3-header-utility-nav","layout":{"type":"flex","justifyContent":"right","flexWrap":"wrap"},"ariaLabel":"Hilfsnavigation","__unstableLocation":"header_utility"} /-->
        </div>
        <!-- /wp:group -->

        <!-- wp:group {"className":"tv3-header-utility-actions","layout":{"type":"flex","verticalAlignment":"center","justifyContent":"right"}} -->
        <div class="wp-block-group tv3-header-utility-actions">
            <!-- wp:search {"label":"Suche","showLabel":false,"placeholder":"Suchen…","buttonText":"Suchen","buttonUseIcon":true,"className":"tv3-header-search"} /-->
            <!-- wp:button {"className":"tv3-header-login","url":"/login","style":{"border":{"radius":"999px"},"spacing":{"padding":{"left":"1.2rem","right":"1.2rem"}}}} -->
            <div class="wp-block-button tv3-header-login"><a class="wp-block-button__link wp-element-button" href="/login">Anmelden</a></div>
            <!-- /wp:button -->
        </div>
        <!-- /wp:group -->
    </div>
    <!-- /wp:group -->
</header>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"hero-context-nav","tagName":"nav","className":"tv3-hero-context-nav-part"} /-->
