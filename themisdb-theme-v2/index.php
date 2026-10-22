<?php
if ( is_front_page() || is_home() ) :
    if ( is_active_sidebar( 'themisdb-graph-homepage' ) ) :
        ?>
        <div class="themisdb-graph-homepage-widget-area">
            <?php dynamic_sidebar( 'themisdb-graph-homepage' ); ?>
        </div>
        <?php
    endif;
endif;

if ( have_posts() ) :
    while ( have_posts() ) : the_post();
        the_content();
    endwhile;
endif;
