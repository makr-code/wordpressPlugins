<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?> data-home-url="<?php echo esc_url( home_url( '/' ) ); ?>">
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'Skip to content', 'themisdb' ); ?></a>

<div id="page" class="site">
    <header id="masthead" class="site-header">
        <div class="header-inner">

            <!-- Site branding: custom logo OR initial box + name + tagline -->
            <div class="site-branding-group">
                <?php if ( has_custom_logo() ) : ?>
                    <?php the_custom_logo(); ?>
                <?php else : ?>
                    <div class="site-logo-initial" aria-hidden="true"><?php echo esc_html( mb_strtoupper( mb_substr( get_bloginfo( 'name' ), 0, 1 ) ) ); ?></div>
                <?php endif; ?>
                <div class="site-branding-text">
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-name-link" rel="home"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></a>
                    <?php
                    $description = get_bloginfo( 'description', 'display' );
                    if ( $description || is_customize_preview() ) :
                    ?>
                    <span class="site-tagline"><?php echo esc_html( $description ); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Desktop navigation (WP menu, primary location) -->
            <nav id="site-navigation" class="main-navigation" aria-label="<?php esc_attr_e( 'Hauptmenü', 'themisdb' ); ?>">
                <?php
                wp_nav_menu( array(
                    'theme_location' => 'primary',
                    'menu_id'        => 'primary-menu',
                    'menu_class'     => 'primary-menu',
                    'container'      => false,
                    'depth'          => 3,
                    'fallback_cb'    => 'themisdb_nav_fallback',
                ) );
                ?>
            </nav>

            <!-- Header action icons -->
            <div class="header-actions">
                <button class="header-icon-btn search-toggle"
                        aria-label="<?php esc_attr_e( 'Suche öffnen', 'themisdb' ); ?>"
                        aria-expanded="false">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </button>
                <button class="header-icon-btn dark-mode-toggle"
                        aria-label="<?php esc_attr_e( 'Dark Mode umschalten', 'themisdb' ); ?>">
                    🌙
                </button>
            </div>

            <!-- Mobile hamburger (toggled by navigation.js) -->
            <button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false" aria-label="<?php esc_attr_e( 'Menü öffnen', 'themisdb' ); ?>">
                <span class="hamburger-bar"></span>
                <span class="hamburger-bar"></span>
                <span class="hamburger-bar"></span>
            </button>

        </div>
    </header>

    <div id="content" class="site-content">
