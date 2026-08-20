    </div><!-- #content -->

    <footer id="colophon" class="site-footer">
        <?php if ( is_active_sidebar( 'footer-1' ) || is_active_sidebar( 'footer-2' ) || is_active_sidebar( 'footer-3' ) ) : ?>
            <div class="footer-widgets">
                <?php
                for ( $i = 1; $i <= 3; $i++ ) {
                    if ( is_active_sidebar( 'footer-' . $i ) ) {
                        dynamic_sidebar( 'footer-' . $i );
                    }
                }
                ?>
            </div>
        <?php endif; ?>

        <?php
        $footer_tone = get_theme_mod( 'themisdb_footer_tone', 'marketing' );

        if ( ! in_array( $footer_tone, array( 'marketing', 'technical' ), true ) ) {
            $footer_tone = 'marketing';
        }

        $footer_subtitle = 'technical' === $footer_tone
            ? esc_html__( 'Open source WordPress stack for structured plugin releases and operations.', 'themisdb' )
            : esc_html__( 'Open source WordPress tooling for modern data workflows.', 'themisdb' );

        $footer_description = 'technical' === $footer_tone
            ? esc_html__( 'Built around block-native rendering, clear deployment paths and maintainable plugin architecture.', 'themisdb' )
            : esc_html__( 'Block-first, release-focused and built for reliable plugin operations.', 'themisdb' );
        ?>

        <div class="site-info">
            <div class="site-info-grid">
                <div class="site-info-brand">
                    <p class="site-info-title"><?php bloginfo( 'name' ); ?></p>
                    <p class="site-info-subtitle"><?php echo esc_html( $footer_subtitle ); ?></p>
                </div>
                <div class="site-info-meta">
                    <p class="site-info-description"><?php echo esc_html( $footer_description ); ?></p>
                    <p class="site-info-copyright">
                        <?php
                        printf(
                            esc_html__( 'Copyright %1$s %2$s', 'themisdb' ),
                            esc_html( date_i18n( 'Y' ) ),
                            esc_html( get_bloginfo( 'name' ) )
                        );
                        ?>
                    </p>
                </div>
            </div>

            <?php
            if ( has_nav_menu( 'footer' ) ) {
                wp_nav_menu( array(
                    'theme_location' => 'footer',
                    'menu_class'     => 'footer-menu',
                    'depth'          => 1,
                ) );
            }
            ?>

            <p class="site-info-links">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Startseite', 'themisdb' ); ?></a>
                <span aria-hidden="true">|</span>
                <a href="<?php echo esc_url( 'https://github.com/makr-code/wordpressPlugins' ); ?>" rel="noopener noreferrer"><?php esc_html_e( 'GitHub', 'themisdb' ); ?></a>
                <span aria-hidden="true">|</span>
                <a href="<?php echo esc_url( home_url( '/datenschutz' ) ); ?>"><?php esc_html_e( 'Datenschutz', 'themisdb' ); ?></a>
            </p>
        </div>
    </footer>
    
    <!-- Scroll to Top Button -->
    <button id="scroll-to-top" class="scroll-to-top" aria-label="Scroll to top" title="Scroll to top">
        ⬆
    </button>
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
