<?php
/**
 * Custom Widgets for ThemisDB Theme
 *
 * @package ThemisDB
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Featured Posts Slider Widget
 * Displays featured/sticky posts in a slider format
 */
class ThemisDB_Featured_Slider_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'themisdb_featured_slider',
            esc_html__( 'ThemisDB: Featured Slider', 'themisdb' ),
            array( 
                'description' => esc_html__( 'Display featured posts in a slider to highlight articles', 'themisdb' ),
                'classname' => 'themisdb-featured-slider-widget'
            )
        );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];

        $title = ! empty( $instance['title'] ) ? $instance['title'] : '';
        $title = apply_filters( 'widget_title', $title, $instance, $this->id_base );
        $count = ! empty( $instance['count'] ) ? absint( $instance['count'] ) : 3;

        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }

        // Query for featured posts (sticky posts or posts with a meta key)
        $query_args = array(
            'posts_per_page' => $count,
            'post__in'       => get_option( 'sticky_posts' ),
            'ignore_sticky_posts' => 1,
        );

        // If no sticky posts, get recent posts
        if ( empty( get_option( 'sticky_posts' ) ) ) {
            $query_args = array(
                'posts_per_page' => $count,
                'orderby'        => 'date',
                'order'          => 'DESC',
            );
        }

        $featured_query = new WP_Query( $query_args );

        if ( $featured_query->have_posts() ) :
            ?>
            <div class="themisdb-slider-container">
                <div class="themisdb-slider">
                    <?php while ( $featured_query->have_posts() ) : $featured_query->the_post(); ?>
                        <div class="slider-item">
                            <?php if ( has_post_thumbnail() ) : ?>
                                <div class="slider-image">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_post_thumbnail( 'themisdb-featured' ); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            <div class="slider-content">
                                <h3 class="slider-title">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h3>
                                <div class="slider-meta">
                                    <span class="slider-date"><?php echo esc_html( get_the_date() ); ?></span>
                                </div>
                                <div class="slider-excerpt">
                                    <?php echo wp_trim_words( get_the_excerpt(), 20 ); ?>
                                </div>
                                <a href="<?php the_permalink(); ?>" class="slider-readmore">
                                    <?php esc_html_e( 'Read More', 'themisdb' ); ?> →
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                <?php if ( $featured_query->post_count > 1 ) : ?>
                    <button class="slider-nav slider-prev" aria-label="<?php esc_attr_e( 'Previous slide', 'themisdb' ); ?>">‹</button>
                    <button class="slider-nav slider-next" aria-label="<?php esc_attr_e( 'Next slide', 'themisdb' ); ?>">›</button>
                    <div class="slider-dots"></div>
                <?php endif; ?>
            </div>
            <?php
            wp_reset_postdata();
        endif;

        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'Featured Posts', 'themisdb' );
        $count = ! empty( $instance['count'] ) ? absint( $instance['count'] ) : 3;
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
                <?php esc_html_e( 'Title:', 'themisdb' ); ?>
            </label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" 
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" 
                   value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>">
                <?php esc_html_e( 'Number of posts:', 'themisdb' ); ?>
            </label>
            <input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>" 
                   name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>" type="number" 
                   step="1" min="1" value="<?php echo esc_attr( $count ); ?>" size="3">
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
        $instance['count'] = ( ! empty( $new_instance['count'] ) ) ? absint( $new_instance['count'] ) : 3;
        return $instance;
    }
}

/**
 * Recent Posts with Thumbnails Widget
 * Enhanced recent posts display with featured images
 */
class ThemisDB_Recent_Posts_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'themisdb_recent_posts',
            esc_html__( 'ThemisDB: Recent Posts', 'themisdb' ),
            array( 
                'description' => esc_html__( 'Display recent posts with thumbnails', 'themisdb' ),
                'classname' => 'themisdb-recent-posts-widget'
            )
        );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];

        $title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'Recent Posts', 'themisdb' );
        $title = apply_filters( 'widget_title', $title, $instance, $this->id_base );
        $count = ! empty( $instance['count'] ) ? absint( $instance['count'] ) : 5;
        $show_thumbnails = ! empty( $instance['show_thumbnails'] );

        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }

        $recent_posts = new WP_Query( array(
            'posts_per_page'      => $count,
            'post_status'         => 'publish',
            'ignore_sticky_posts' => true,
        ) );

        if ( $recent_posts->have_posts() ) :
            ?>
            <ul class="themisdb-recent-posts">
                <?php while ( $recent_posts->have_posts() ) : $recent_posts->the_post(); ?>
                    <li class="recent-post-item">
                        <?php if ( $show_thumbnails && has_post_thumbnail() ) : ?>
                            <div class="recent-post-thumbnail">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail( 'themisdb-thumbnail' ); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        <div class="recent-post-content">
                            <h4 class="recent-post-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h4>
                            <span class="recent-post-date"><?php echo esc_html( get_the_date() ); ?></span>
                        </div>
                    </li>
                <?php endwhile; ?>
            </ul>
            <?php
            wp_reset_postdata();
        endif;

        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'Recent Posts', 'themisdb' );
        $count = ! empty( $instance['count'] ) ? absint( $instance['count'] ) : 5;
        $show_thumbnails = ! empty( $instance['show_thumbnails'] );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
                <?php esc_html_e( 'Title:', 'themisdb' ); ?>
            </label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" 
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" 
                   value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>">
                <?php esc_html_e( 'Number of posts:', 'themisdb' ); ?>
            </label>
            <input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>" 
                   name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>" type="number" 
                   step="1" min="1" value="<?php echo esc_attr( $count ); ?>" size="3">
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked( $show_thumbnails ); ?> 
                   id="<?php echo esc_attr( $this->get_field_id( 'show_thumbnails' ) ); ?>" 
                   name="<?php echo esc_attr( $this->get_field_name( 'show_thumbnails' ) ); ?>" />
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_thumbnails' ) ); ?>">
                <?php esc_html_e( 'Display thumbnails', 'themisdb' ); ?>
            </label>
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
        $instance['count'] = ( ! empty( $new_instance['count'] ) ) ? absint( $new_instance['count'] ) : 5;
        $instance['show_thumbnails'] = ( ! empty( $new_instance['show_thumbnails'] ) ) ? 1 : 0;
        return $instance;
    }
}

/**
 * Category Highlights Widget
 * Display posts from specific categories
 */
class ThemisDB_Category_Highlights_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'themisdb_category_highlights',
            esc_html__( 'ThemisDB: Category Highlights', 'themisdb' ),
            array( 
                'description' => esc_html__( 'Highlight posts from a specific category', 'themisdb' ),
                'classname' => 'themisdb-category-highlights-widget'
            )
        );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];

        $title = ! empty( $instance['title'] ) ? $instance['title'] : '';
        $title = apply_filters( 'widget_title', $title, $instance, $this->id_base );
        $category = ! empty( $instance['category'] ) ? absint( $instance['category'] ) : 0;
        $count = ! empty( $instance['count'] ) ? absint( $instance['count'] ) : 3;

        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }

        if ( $category ) {
            $category_posts = new WP_Query( array(
                'cat'            => $category,
                'posts_per_page' => $count,
                'post_status'    => 'publish',
            ) );

            if ( $category_posts->have_posts() ) :
                ?>
                <div class="themisdb-category-highlights">
                    <?php while ( $category_posts->have_posts() ) : $category_posts->the_post(); ?>
                        <article class="category-highlight-item">
                            <?php if ( has_post_thumbnail() ) : ?>
                                <div class="highlight-thumbnail">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_post_thumbnail( 'themisdb-thumbnail' ); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            <div class="highlight-content">
                                <h4 class="highlight-title">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h4>
                                <div class="highlight-excerpt">
                                    <?php echo wp_trim_words( get_the_excerpt(), 15 ); ?>
                                </div>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>
                <?php
                wp_reset_postdata();
            endif;
        } else {
            echo '<p>' . esc_html__( 'Please select a category in widget settings.', 'themisdb' ) . '</p>';
        }

        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : '';
        $category = ! empty( $instance['category'] ) ? absint( $instance['category'] ) : 0;
        $count = ! empty( $instance['count'] ) ? absint( $instance['count'] ) : 3;
        
        $categories = get_categories( array( 'hide_empty' => false ) );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
                <?php esc_html_e( 'Title:', 'themisdb' ); ?>
            </label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" 
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" 
                   value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'category' ) ); ?>">
                <?php esc_html_e( 'Category:', 'themisdb' ); ?>
            </label>
            <select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'category' ) ); ?>" 
                    name="<?php echo esc_attr( $this->get_field_name( 'category' ) ); ?>">
                <option value="0"><?php esc_html_e( 'Select a category', 'themisdb' ); ?></option>
                <?php foreach ( $categories as $cat ) : ?>
                    <option value="<?php echo esc_attr( $cat->term_id ); ?>" <?php selected( $category, $cat->term_id ); ?>>
                        <?php echo esc_html( $cat->name ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>">
                <?php esc_html_e( 'Number of posts:', 'themisdb' ); ?>
            </label>
            <input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>" 
                   name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>" type="number" 
                   step="1" min="1" value="<?php echo esc_attr( $count ); ?>" size="3">
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
        $instance['category'] = ( ! empty( $new_instance['category'] ) ) ? absint( $new_instance['category'] ) : 0;
        $instance['count'] = ( ! empty( $new_instance['count'] ) ) ? absint( $new_instance['count'] ) : 3;
        return $instance;
    }
}

/**
 * Call-to-Action Widget
 * Highlight important content or links
 */
class ThemisDB_CTA_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'themisdb_cta',
            esc_html__( 'ThemisDB: Call to Action', 'themisdb' ),
            array( 
                'description' => esc_html__( 'Display a highlighted call-to-action box', 'themisdb' ),
                'classname' => 'themisdb-cta-widget'
            )
        );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];

        $title = ! empty( $instance['title'] ) ? $instance['title'] : '';
        $content = ! empty( $instance['content'] ) ? $instance['content'] : '';
        $button_text = ! empty( $instance['button_text'] ) ? $instance['button_text'] : '';
        $button_url = ! empty( $instance['button_url'] ) ? $instance['button_url'] : '';
        $style = ! empty( $instance['style'] ) ? $instance['style'] : 'primary';

        ?>
        <div class="themisdb-cta-box cta-style-<?php echo esc_attr( $style ); ?>">
            <?php if ( $title ) : ?>
                <h3 class="cta-title"><?php echo esc_html( $title ); ?></h3>
            <?php endif; ?>
            
            <?php if ( $content ) : ?>
                <div class="cta-content">
                    <?php echo wp_kses_post( wpautop( $content ) ); ?>
                </div>
            <?php endif; ?>
            
            <?php if ( $button_text && $button_url ) : ?>
                <a href="<?php echo esc_url( $button_url ); ?>" class="cta-button">
                    <?php echo esc_html( $button_text ); ?>
                </a>
            <?php endif; ?>
        </div>
        <?php

        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : '';
        $content = ! empty( $instance['content'] ) ? $instance['content'] : '';
        $button_text = ! empty( $instance['button_text'] ) ? $instance['button_text'] : '';
        $button_url = ! empty( $instance['button_url'] ) ? $instance['button_url'] : '';
        $style = ! empty( $instance['style'] ) ? $instance['style'] : 'primary';
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
                <?php esc_html_e( 'Title:', 'themisdb' ); ?>
            </label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" 
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" 
                   value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'content' ) ); ?>">
                <?php esc_html_e( 'Content:', 'themisdb' ); ?>
            </label>
            <textarea class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'content' ) ); ?>" 
                      name="<?php echo esc_attr( $this->get_field_name( 'content' ) ); ?>" rows="4"><?php echo esc_textarea( $content ); ?></textarea>
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'button_text' ) ); ?>">
                <?php esc_html_e( 'Button Text:', 'themisdb' ); ?>
            </label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'button_text' ) ); ?>" 
                   name="<?php echo esc_attr( $this->get_field_name( 'button_text' ) ); ?>" type="text" 
                   value="<?php echo esc_attr( $button_text ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'button_url' ) ); ?>">
                <?php esc_html_e( 'Button URL:', 'themisdb' ); ?>
            </label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'button_url' ) ); ?>" 
                   name="<?php echo esc_attr( $this->get_field_name( 'button_url' ) ); ?>" type="url" 
                   value="<?php echo esc_url( $button_url ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'style' ) ); ?>">
                <?php esc_html_e( 'Style:', 'themisdb' ); ?>
            </label>
            <select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'style' ) ); ?>" 
                    name="<?php echo esc_attr( $this->get_field_name( 'style' ) ); ?>">
                <option value="primary" <?php selected( $style, 'primary' ); ?>><?php esc_html_e( 'Primary', 'themisdb' ); ?></option>
                <option value="secondary" <?php selected( $style, 'secondary' ); ?>><?php esc_html_e( 'Secondary', 'themisdb' ); ?></option>
                <option value="accent" <?php selected( $style, 'accent' ); ?>><?php esc_html_e( 'Accent', 'themisdb' ); ?></option>
                <option value="success" <?php selected( $style, 'success' ); ?>><?php esc_html_e( 'Success', 'themisdb' ); ?></option>
            </select>
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
        $instance['content'] = ( ! empty( $new_instance['content'] ) ) ? wp_kses_post( $new_instance['content'] ) : '';
        $instance['button_text'] = ( ! empty( $new_instance['button_text'] ) ) ? sanitize_text_field( $new_instance['button_text'] ) : '';
        $instance['button_url'] = ( ! empty( $new_instance['button_url'] ) ) ? esc_url_raw( $new_instance['button_url'] ) : '';
        $instance['style'] = ( ! empty( $new_instance['style'] ) ) ? sanitize_text_field( $new_instance['style'] ) : 'primary';
        return $instance;
    }
}

/**
 * About/Info Box Widget
 * Display project information or about section
 */
class ThemisDB_About_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'themisdb_about',
            esc_html__( 'ThemisDB: About Box', 'themisdb' ),
            array( 
                'description' => esc_html__( 'Display project information or about content', 'themisdb' ),
                'classname' => 'themisdb-about-widget'
            )
        );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];

        $title = ! empty( $instance['title'] ) ? $instance['title'] : '';
        $content = ! empty( $instance['content'] ) ? $instance['content'] : '';
        $show_icon = ! empty( $instance['show_icon'] );

        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }

        ?>
        <div class="themisdb-about-box">
            <?php if ( $show_icon ) : ?>
                <div class="about-icon">
                    <span class="icon-database">🗄️</span>
                </div>
            <?php endif; ?>
            <div class="about-content">
                <?php echo wp_kses_post( wpautop( $content ) ); ?>
            </div>
        </div>
        <?php

        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'About ThemisDB', 'themisdb' );
        $content = ! empty( $instance['content'] ) ? $instance['content'] : '';
        $show_icon = ! empty( $instance['show_icon'] );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
                <?php esc_html_e( 'Title:', 'themisdb' ); ?>
            </label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" 
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" 
                   value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'content' ) ); ?>">
                <?php esc_html_e( 'Content:', 'themisdb' ); ?>
            </label>
            <textarea class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'content' ) ); ?>" 
                      name="<?php echo esc_attr( $this->get_field_name( 'content' ) ); ?>" rows="6"><?php echo esc_textarea( $content ); ?></textarea>
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked( $show_icon ); ?> 
                   id="<?php echo esc_attr( $this->get_field_id( 'show_icon' ) ); ?>" 
                   name="<?php echo esc_attr( $this->get_field_name( 'show_icon' ) ); ?>" />
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_icon' ) ); ?>">
                <?php esc_html_e( 'Show icon', 'themisdb' ); ?>
            </label>
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
        $instance['content'] = ( ! empty( $new_instance['content'] ) ) ? wp_kses_post( $new_instance['content'] ) : '';
        $instance['show_icon'] = ( ! empty( $new_instance['show_icon'] ) ) ? 1 : 0;
        return $instance;
    }
}

/**
 * Social Media Links Widget
 * Display social media and community links
 */
class ThemisDB_Social_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'themisdb_social',
            esc_html__( 'ThemisDB: Social Links', 'themisdb' ),
            array( 
                'description' => esc_html__( 'Display social media and community links', 'themisdb' ),
                'classname' => 'themisdb-social-widget'
            )
        );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];

        $title = ! empty( $instance['title'] ) ? $instance['title'] : '';
        $github_url = ! empty( $instance['github_url'] ) ? $instance['github_url'] : '';
        $twitter_url = ! empty( $instance['twitter_url'] ) ? $instance['twitter_url'] : '';
        $discord_url = ! empty( $instance['discord_url'] ) ? $instance['discord_url'] : '';
        $linkedin_url = ! empty( $instance['linkedin_url'] ) ? $instance['linkedin_url'] : '';

        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }

        $has_links = $github_url || $twitter_url || $discord_url || $linkedin_url;

        if ( $has_links ) :
            ?>
            <div class="themisdb-social-links">
                <?php if ( $github_url ) : ?>
                    <a href="<?php echo esc_url( $github_url ); ?>" class="social-link social-github" target="_blank" rel="noopener noreferrer" aria-label="GitHub">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                        </svg>
                        <span>GitHub</span>
                    </a>
                <?php endif; ?>

                <?php if ( $twitter_url ) : ?>
                    <a href="<?php echo esc_url( $twitter_url ); ?>" class="social-link social-twitter" target="_blank" rel="noopener noreferrer" aria-label="Twitter">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"/>
                        </svg>
                        <span>Twitter</span>
                    </a>
                <?php endif; ?>

                <?php if ( $discord_url ) : ?>
                    <a href="<?php echo esc_url( $discord_url ); ?>" class="social-link social-discord" target="_blank" rel="noopener noreferrer" aria-label="Discord">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M20.317 4.37a19.791 19.791 0 00-4.885-1.515.074.074 0 00-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 00-5.487 0 12.64 12.64 0 00-.617-1.25.077.077 0 00-.079-.037A19.736 19.736 0 003.677 4.37a.07.07 0 00-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 00.031.057 19.9 19.9 0 005.993 3.03.078.078 0 00.084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 00-.041-.106 13.107 13.107 0 01-1.872-.892.077.077 0 01-.008-.128 10.2 10.2 0 00.372-.292.074.074 0 01.077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 01.078.01c.12.098.246.198.373.292a.077.077 0 01-.006.127 12.299 12.299 0 01-1.873.892.077.077 0 00-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 00.084.028 19.839 19.839 0 006.002-3.03.077.077 0 00.032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 00-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/>
                        </svg>
                        <span>Discord</span>
                    </a>
                <?php endif; ?>

                <?php if ( $linkedin_url ) : ?>
                    <a href="<?php echo esc_url( $linkedin_url ); ?>" class="social-link social-linkedin" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                        </svg>
                        <span>LinkedIn</span>
                    </a>
                <?php endif; ?>
            </div>
            <?php
        else :
            echo '<p>' . esc_html__( 'Please configure social media URLs in widget settings.', 'themisdb' ) . '</p>';
        endif;

        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'Follow Us', 'themisdb' );
        $github_url = ! empty( $instance['github_url'] ) ? $instance['github_url'] : '';
        $twitter_url = ! empty( $instance['twitter_url'] ) ? $instance['twitter_url'] : '';
        $discord_url = ! empty( $instance['discord_url'] ) ? $instance['discord_url'] : '';
        $linkedin_url = ! empty( $instance['linkedin_url'] ) ? $instance['linkedin_url'] : '';
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
                <?php esc_html_e( 'Title:', 'themisdb' ); ?>
            </label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" 
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" 
                   value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'github_url' ) ); ?>">
                <?php esc_html_e( 'GitHub URL:', 'themisdb' ); ?>
            </label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'github_url' ) ); ?>" 
                   name="<?php echo esc_attr( $this->get_field_name( 'github_url' ) ); ?>" type="url" 
                   value="<?php echo esc_url( $github_url ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'twitter_url' ) ); ?>">
                <?php esc_html_e( 'Twitter URL:', 'themisdb' ); ?>
            </label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'twitter_url' ) ); ?>" 
                   name="<?php echo esc_attr( $this->get_field_name( 'twitter_url' ) ); ?>" type="url" 
                   value="<?php echo esc_url( $twitter_url ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'discord_url' ) ); ?>">
                <?php esc_html_e( 'Discord URL:', 'themisdb' ); ?>
            </label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'discord_url' ) ); ?>" 
                   name="<?php echo esc_attr( $this->get_field_name( 'discord_url' ) ); ?>" type="url" 
                   value="<?php echo esc_url( $discord_url ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'linkedin_url' ) ); ?>">
                <?php esc_html_e( 'LinkedIn URL:', 'themisdb' ); ?>
            </label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'linkedin_url' ) ); ?>" 
                   name="<?php echo esc_attr( $this->get_field_name( 'linkedin_url' ) ); ?>" type="url" 
                   value="<?php echo esc_url( $linkedin_url ); ?>">
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
        $instance['github_url'] = ( ! empty( $new_instance['github_url'] ) ) ? esc_url_raw( $new_instance['github_url'] ) : '';
        $instance['twitter_url'] = ( ! empty( $new_instance['twitter_url'] ) ) ? esc_url_raw( $new_instance['twitter_url'] ) : '';
        $instance['discord_url'] = ( ! empty( $new_instance['discord_url'] ) ) ? esc_url_raw( $new_instance['discord_url'] ) : '';
        $instance['linkedin_url'] = ( ! empty( $new_instance['linkedin_url'] ) ) ? esc_url_raw( $new_instance['linkedin_url'] ) : '';
        return $instance;
    }
}

/**
 * Enhanced Tag Cloud Widget
 * Display tags with custom styling and counts
 */
class ThemisDB_Tag_Cloud_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'themisdb_tag_cloud',
            esc_html__( 'ThemisDB: Tag Cloud', 'themisdb' ),
            array( 
                'description' => esc_html__( 'Display a cloud of your most used tags', 'themisdb' ),
                'classname' => 'themisdb-tag-cloud-widget'
            )
        );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];

        $title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'Tags', 'themisdb' );
        $count = ! empty( $instance['count'] ) ? absint( $instance['count'] ) : 20;
        $show_count = ! empty( $instance['show_count'] );

        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }

        $tags = get_tags( array(
            'orderby' => 'count',
            'order'   => 'DESC',
            'number'  => $count,
        ) );

        if ( $tags ) :
            ?>
            <div class="themisdb-tag-cloud">
                <?php foreach ( $tags as $tag ) : ?>
                    <a href="<?php echo esc_url( get_tag_link( $tag->term_id ) ); ?>" 
                       class="tag-item" 
                       title="<?php echo esc_attr( sprintf( _n( '%s topic', '%s topics', $tag->count, 'themisdb' ), $tag->count ) ); ?>">
                        <?php echo esc_html( $tag->name ); ?>
                        <?php if ( $show_count ) : ?>
                            <span class="tag-count">(<?php echo esc_html( $tag->count ); ?>)</span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php
        else :
            echo '<p>' . esc_html__( 'No tags found.', 'themisdb' ) . '</p>';
        endif;

        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'Tags', 'themisdb' );
        $count = ! empty( $instance['count'] ) ? absint( $instance['count'] ) : 20;
        $show_count = ! empty( $instance['show_count'] );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
                <?php esc_html_e( 'Title:', 'themisdb' ); ?>
            </label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" 
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" 
                   value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>">
                <?php esc_html_e( 'Number of tags:', 'themisdb' ); ?>
            </label>
            <input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>" 
                   name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>" type="number" 
                   step="1" min="1" max="50" value="<?php echo esc_attr( $count ); ?>" size="3">
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked( $show_count ); ?> 
                   id="<?php echo esc_attr( $this->get_field_id( 'show_count' ) ); ?>" 
                   name="<?php echo esc_attr( $this->get_field_name( 'show_count' ) ); ?>" />
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_count' ) ); ?>">
                <?php esc_html_e( 'Show post count', 'themisdb' ); ?>
            </label>
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
        $instance['count'] = ( ! empty( $new_instance['count'] ) ) ? absint( $new_instance['count'] ) : 20;
        $instance['show_count'] = ( ! empty( $new_instance['show_count'] ) ) ? 1 : 0;
        return $instance;
    }
}

/**
 * Documentation/Quick Links Widget
 * Display important documentation links
 */
class ThemisDB_Quick_Links_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'themisdb_quick_links',
            esc_html__( 'ThemisDB: Quick Links', 'themisdb' ),
            array( 
                'description' => esc_html__( 'Display important documentation or quick access links', 'themisdb' ),
                'classname' => 'themisdb-quick-links-widget'
            )
        );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];

        $title = ! empty( $instance['title'] ) ? $instance['title'] : '';
        $links = array();
        
        for ( $i = 1; $i <= 5; $i++ ) {
            $link_title = ! empty( $instance['link_title_' . $i] ) ? $instance['link_title_' . $i] : '';
            $link_url = ! empty( $instance['link_url_' . $i] ) ? $instance['link_url_' . $i] : '';
            
            if ( $link_title && $link_url ) {
                $links[] = array(
                    'title' => $link_title,
                    'url'   => $link_url,
                );
            }
        }

        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }

        if ( ! empty( $links ) ) :
            ?>
            <ul class="themisdb-quick-links">
                <?php foreach ( $links as $link ) : ?>
                    <li class="quick-link-item">
                        <a href="<?php echo esc_url( $link['url'] ); ?>" class="quick-link">
                            <span class="link-icon">📄</span>
                            <span class="link-text"><?php echo esc_html( $link['title'] ); ?></span>
                            <span class="link-arrow">→</span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php
        else :
            echo '<p>' . esc_html__( 'Please configure links in widget settings.', 'themisdb' ) . '</p>';
        endif;

        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'Quick Links', 'themisdb' );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
                <?php esc_html_e( 'Title:', 'themisdb' ); ?>
            </label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" 
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" 
                   value="<?php echo esc_attr( $title ); ?>">
        </p>
        <?php for ( $i = 1; $i <= 5; $i++ ) : 
            $link_title = ! empty( $instance['link_title_' . $i] ) ? $instance['link_title_' . $i] : '';
            $link_url = ! empty( $instance['link_url_' . $i] ) ? $instance['link_url_' . $i] : '';
        ?>
            <p><strong><?php echo sprintf( esc_html__( 'Link %d', 'themisdb' ), $i ); ?></strong></p>
            <p>
                <label for="<?php echo esc_attr( $this->get_field_id( 'link_title_' . $i ) ); ?>">
                    <?php esc_html_e( 'Link Text:', 'themisdb' ); ?>
                </label>
                <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'link_title_' . $i ) ); ?>" 
                       name="<?php echo esc_attr( $this->get_field_name( 'link_title_' . $i ) ); ?>" type="text" 
                       value="<?php echo esc_attr( $link_title ); ?>">
            </p>
            <p>
                <label for="<?php echo esc_attr( $this->get_field_id( 'link_url_' . $i ) ); ?>">
                    <?php esc_html_e( 'Link URL:', 'themisdb' ); ?>
                </label>
                <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'link_url_' . $i ) ); ?>" 
                       name="<?php echo esc_attr( $this->get_field_name( 'link_url_' . $i ) ); ?>" type="url" 
                       value="<?php echo esc_url( $link_url ); ?>">
            </p>
        <?php endfor; ?>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
        
        for ( $i = 1; $i <= 5; $i++ ) {
            $instance['link_title_' . $i] = ( ! empty( $new_instance['link_title_' . $i] ) ) ? sanitize_text_field( $new_instance['link_title_' . $i] ) : '';
            $instance['link_url_' . $i] = ( ! empty( $new_instance['link_url_' . $i] ) ) ? esc_url_raw( $new_instance['link_url_' . $i] ) : '';
        }
        
        return $instance;
    }
}

/**
 * Testimonials/Quotes Carousel Widget
 * Rotating testimonials or quotes display
 */
class ThemisDB_Testimonials_Carousel_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'themisdb_testimonials_carousel',
            esc_html__( 'ThemisDB: Testimonials Carousel', 'themisdb' ),
            array( 
                'description' => esc_html__( 'Display rotating testimonials or quotes in a carousel', 'themisdb' ),
                'classname' => 'themisdb-testimonials-carousel-widget'
            )
        );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];

        $title = ! empty( $instance['title'] ) ? $instance['title'] : '';
        $title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }

        $testimonials = array();
        for ( $i = 1; $i <= 3; $i++ ) {
            $quote = ! empty( $instance['quote_' . $i] ) ? $instance['quote_' . $i] : '';
            $author = ! empty( $instance['author_' . $i] ) ? $instance['author_' . $i] : '';
            $role = ! empty( $instance['role_' . $i] ) ? $instance['role_' . $i] : '';
            
            if ( $quote && $author ) {
                $testimonials[] = array(
                    'quote'  => $quote,
                    'author' => $author,
                    'role'   => $role,
                );
            }
        }

        if ( ! empty( $testimonials ) ) :
            ?>
            <div class="themisdb-testimonials-container">
                <div class="themisdb-testimonials-carousel">
                    <?php foreach ( $testimonials as $index => $testimonial ) : ?>
                        <div class="testimonial-item <?php echo $index === 0 ? 'active' : ''; ?>">
                            <div class="testimonial-quote">
                                <span class="quote-icon">❝</span>
                                <p><?php echo esc_html( $testimonial['quote'] ); ?></p>
                            </div>
                            <div class="testimonial-author">
                                <strong><?php echo esc_html( $testimonial['author'] ); ?></strong>
                                <?php if ( $testimonial['role'] ) : ?>
                                    <span class="author-role"><?php echo esc_html( $testimonial['role'] ); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if ( count( $testimonials ) > 1 ) : ?>
                    <div class="testimonial-nav">
                        <button class="testimonial-prev" aria-label="<?php esc_attr_e( 'Previous testimonial', 'themisdb' ); ?>">‹</button>
                        <button class="testimonial-next" aria-label="<?php esc_attr_e( 'Next testimonial', 'themisdb' ); ?>">›</button>
                    </div>
                    <div class="testimonial-dots"></div>
                <?php endif; ?>
            </div>
            <?php
        else :
            echo '<p>' . esc_html__( 'Please configure testimonials in widget settings.', 'themisdb' ) . '</p>';
        endif;

        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'What People Say', 'themisdb' );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
                <?php esc_html_e( 'Title:', 'themisdb' ); ?>
            </label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" 
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" 
                   value="<?php echo esc_attr( $title ); ?>">
        </p>
        <?php for ( $i = 1; $i <= 3; $i++ ) : 
            $quote = ! empty( $instance['quote_' . $i] ) ? $instance['quote_' . $i] : '';
            $author = ! empty( $instance['author_' . $i] ) ? $instance['author_' . $i] : '';
            $role = ! empty( $instance['role_' . $i] ) ? $instance['role_' . $i] : '';
        ?>
            <p><strong><?php echo sprintf( esc_html__( 'Testimonial %d', 'themisdb' ), $i ); ?></strong></p>
            <p>
                <label for="<?php echo esc_attr( $this->get_field_id( 'quote_' . $i ) ); ?>">
                    <?php esc_html_e( 'Quote:', 'themisdb' ); ?>
                </label>
                <textarea class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'quote_' . $i ) ); ?>" 
                          name="<?php echo esc_attr( $this->get_field_name( 'quote_' . $i ) ); ?>" rows="3"><?php echo esc_textarea( $quote ); ?></textarea>
            </p>
            <p>
                <label for="<?php echo esc_attr( $this->get_field_id( 'author_' . $i ) ); ?>">
                    <?php esc_html_e( 'Author:', 'themisdb' ); ?>
                </label>
                <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'author_' . $i ) ); ?>" 
                       name="<?php echo esc_attr( $this->get_field_name( 'author_' . $i ) ); ?>" type="text" 
                       value="<?php echo esc_attr( $author ); ?>">
            </p>
            <p>
                <label for="<?php echo esc_attr( $this->get_field_id( 'role_' . $i ) ); ?>">
                    <?php esc_html_e( 'Role/Company:', 'themisdb' ); ?>
                </label>
                <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'role_' . $i ) ); ?>" 
                       name="<?php echo esc_attr( $this->get_field_name( 'role_' . $i ) ); ?>" type="text" 
                       value="<?php echo esc_attr( $role ); ?>">
            </p>
        <?php endfor; ?>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
        
        for ( $i = 1; $i <= 3; $i++ ) {
            $instance['quote_' . $i] = ( ! empty( $new_instance['quote_' . $i] ) ) ? sanitize_textarea_field( $new_instance['quote_' . $i] ) : '';
            $instance['author_' . $i] = ( ! empty( $new_instance['author_' . $i] ) ) ? sanitize_text_field( $new_instance['author_' . $i] ) : '';
            $instance['role_' . $i] = ( ! empty( $new_instance['role_' . $i] ) ) ? sanitize_text_field( $new_instance['role_' . $i] ) : '';
        }
        
        return $instance;
    }
}

/**
 * Image Gallery Carousel Widget
 * Rotating images with captions
 */
class ThemisDB_Image_Carousel_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'themisdb_image_carousel',
            esc_html__( 'ThemisDB: Image Carousel', 'themisdb' ),
            array( 
                'description' => esc_html__( 'Display rotating images with captions in a carousel', 'themisdb' ),
                'classname' => 'themisdb-image-carousel-widget'
            )
        );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];

        $title = ! empty( $instance['title'] ) ? $instance['title'] : '';
        $title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }

        $images = array();
        for ( $i = 1; $i <= 5; $i++ ) {
            $image_url = ! empty( $instance['image_url_' . $i] ) ? $instance['image_url_' . $i] : '';
            $caption = ! empty( $instance['caption_' . $i] ) ? $instance['caption_' . $i] : '';
            $link = ! empty( $instance['link_' . $i] ) ? $instance['link_' . $i] : '';
            
            if ( $image_url ) {
                $images[] = array(
                    'url'     => $image_url,
                    'caption' => $caption,
                    'link'    => $link,
                );
            }
        }

        if ( ! empty( $images ) ) :
            ?>
            <div class="themisdb-image-carousel-container">
                <div class="themisdb-image-carousel">
                    <?php foreach ( $images as $index => $image ) : ?>
                        <div class="carousel-image-item <?php echo $index === 0 ? 'active' : ''; ?>">
                            <?php if ( $image['link'] ) : ?>
                                <a href="<?php echo esc_url( $image['link'] ); ?>">
                            <?php endif; ?>
                                <img src="<?php echo esc_url( $image['url'] ); ?>" alt="<?php echo esc_attr( $image['caption'] ); ?>">
                            <?php if ( $image['link'] ) : ?>
                                </a>
                            <?php endif; ?>
                            <?php if ( $image['caption'] ) : ?>
                                <div class="carousel-caption">
                                    <?php echo esc_html( $image['caption'] ); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if ( count( $images ) > 1 ) : ?>
                    <button class="carousel-nav carousel-prev" aria-label="<?php esc_attr_e( 'Previous image', 'themisdb' ); ?>">‹</button>
                    <button class="carousel-nav carousel-next" aria-label="<?php esc_attr_e( 'Next image', 'themisdb' ); ?>">›</button>
                    <div class="carousel-indicators"></div>
                <?php endif; ?>
            </div>
            <?php
        else :
            echo '<p>' . esc_html__( 'Please configure images in widget settings.', 'themisdb' ) . '</p>';
        endif;

        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'Gallery', 'themisdb' );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
                <?php esc_html_e( 'Title:', 'themisdb' ); ?>
            </label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" 
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" 
                   value="<?php echo esc_attr( $title ); ?>">
        </p>
        <?php for ( $i = 1; $i <= 5; $i++ ) : 
            $image_url = ! empty( $instance['image_url_' . $i] ) ? $instance['image_url_' . $i] : '';
            $caption = ! empty( $instance['caption_' . $i] ) ? $instance['caption_' . $i] : '';
            $link = ! empty( $instance['link_' . $i] ) ? $instance['link_' . $i] : '';
        ?>
            <p><strong><?php echo sprintf( esc_html__( 'Image %d', 'themisdb' ), $i ); ?></strong></p>
            <p>
                <label for="<?php echo esc_attr( $this->get_field_id( 'image_url_' . $i ) ); ?>">
                    <?php esc_html_e( 'Image URL:', 'themisdb' ); ?>
                </label>
                <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'image_url_' . $i ) ); ?>" 
                       name="<?php echo esc_attr( $this->get_field_name( 'image_url_' . $i ) ); ?>" type="url" 
                       value="<?php echo esc_url( $image_url ); ?>">
            </p>
            <p>
                <label for="<?php echo esc_attr( $this->get_field_id( 'caption_' . $i ) ); ?>">
                    <?php esc_html_e( 'Caption:', 'themisdb' ); ?>
                </label>
                <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'caption_' . $i ) ); ?>" 
                       name="<?php echo esc_attr( $this->get_field_name( 'caption_' . $i ) ); ?>" type="text" 
                       value="<?php echo esc_attr( $caption ); ?>">
            </p>
            <p>
                <label for="<?php echo esc_attr( $this->get_field_id( 'link_' . $i ) ); ?>">
                    <?php esc_html_e( 'Link URL (optional):', 'themisdb' ); ?>
                </label>
                <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'link_' . $i ) ); ?>" 
                       name="<?php echo esc_attr( $this->get_field_name( 'link_' . $i ) ); ?>" type="url" 
                       value="<?php echo esc_url( $link ); ?>">
            </p>
        <?php endfor; ?>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
        
        for ( $i = 1; $i <= 5; $i++ ) {
            $instance['image_url_' . $i] = ( ! empty( $new_instance['image_url_' . $i] ) ) ? esc_url_raw( $new_instance['image_url_' . $i] ) : '';
            $instance['caption_' . $i] = ( ! empty( $new_instance['caption_' . $i] ) ) ? sanitize_text_field( $new_instance['caption_' . $i] ) : '';
            $instance['link_' . $i] = ( ! empty( $new_instance['link_' . $i] ) ) ? esc_url_raw( $new_instance['link_' . $i] ) : '';
        }
        
        return $instance;
    }
}

/**
 * Timeline/Milestones Carousel Widget
 * Display events or milestones in a sliding timeline
 */
class ThemisDB_Timeline_Carousel_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'themisdb_timeline_carousel',
            esc_html__( 'ThemisDB: Timeline Carousel', 'themisdb' ),
            array( 
                'description' => esc_html__( 'Display timeline events or milestones in a carousel', 'themisdb' ),
                'classname' => 'themisdb-timeline-carousel-widget'
            )
        );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];

        $title = ! empty( $instance['title'] ) ? $instance['title'] : '';
        $title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }

        $events = array();
        for ( $i = 1; $i <= 5; $i++ ) {
            $date = ! empty( $instance['date_' . $i] ) ? $instance['date_' . $i] : '';
            $event_title = ! empty( $instance['event_title_' . $i] ) ? $instance['event_title_' . $i] : '';
            $description = ! empty( $instance['description_' . $i] ) ? $instance['description_' . $i] : '';
            
            if ( $date && $event_title ) {
                $events[] = array(
                    'date'        => $date,
                    'title'       => $event_title,
                    'description' => $description,
                );
            }
        }

        if ( ! empty( $events ) ) :
            ?>
            <div class="themisdb-timeline-container">
                <div class="themisdb-timeline-carousel">
                    <?php foreach ( $events as $index => $event ) : ?>
                        <div class="timeline-item <?php echo $index === 0 ? 'active' : ''; ?>">
                            <div class="timeline-date">
                                <?php echo esc_html( $event['date'] ); ?>
                            </div>
                            <div class="timeline-content">
                                <h4 class="timeline-title"><?php echo esc_html( $event['title'] ); ?></h4>
                                <?php if ( $event['description'] ) : ?>
                                    <p class="timeline-description"><?php echo esc_html( $event['description'] ); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if ( count( $events ) > 1 ) : ?>
                    <div class="timeline-nav">
                        <button class="timeline-prev" aria-label="<?php esc_attr_e( 'Previous event', 'themisdb' ); ?>">‹</button>
                        <button class="timeline-next" aria-label="<?php esc_attr_e( 'Next event', 'themisdb' ); ?>">›</button>
                    </div>
                    <div class="timeline-progress"></div>
                <?php endif; ?>
            </div>
            <?php
        else :
            echo '<p>' . esc_html__( 'Please configure timeline events in widget settings.', 'themisdb' ) . '</p>';
        endif;

        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'Timeline', 'themisdb' );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
                <?php esc_html_e( 'Title:', 'themisdb' ); ?>
            </label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" 
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" 
                   value="<?php echo esc_attr( $title ); ?>">
        </p>
        <?php for ( $i = 1; $i <= 5; $i++ ) : 
            $date = ! empty( $instance['date_' . $i] ) ? $instance['date_' . $i] : '';
            $event_title = ! empty( $instance['event_title_' . $i] ) ? $instance['event_title_' . $i] : '';
            $description = ! empty( $instance['description_' . $i] ) ? $instance['description_' . $i] : '';
        ?>
            <p><strong><?php echo sprintf( esc_html__( 'Event %d', 'themisdb' ), $i ); ?></strong></p>
            <p>
                <label for="<?php echo esc_attr( $this->get_field_id( 'date_' . $i ) ); ?>">
                    <?php esc_html_e( 'Date:', 'themisdb' ); ?>
                </label>
                <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'date_' . $i ) ); ?>" 
                       name="<?php echo esc_attr( $this->get_field_name( 'date_' . $i ) ); ?>" type="text" 
                       value="<?php echo esc_attr( $date ); ?>" placeholder="e.g., 2024 or Jan 2024">
            </p>
            <p>
                <label for="<?php echo esc_attr( $this->get_field_id( 'event_title_' . $i ) ); ?>">
                    <?php esc_html_e( 'Event Title:', 'themisdb' ); ?>
                </label>
                <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'event_title_' . $i ) ); ?>" 
                       name="<?php echo esc_attr( $this->get_field_name( 'event_title_' . $i ) ); ?>" type="text" 
                       value="<?php echo esc_attr( $event_title ); ?>">
            </p>
            <p>
                <label for="<?php echo esc_attr( $this->get_field_id( 'description_' . $i ) ); ?>">
                    <?php esc_html_e( 'Description:', 'themisdb' ); ?>
                </label>
                <textarea class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'description_' . $i ) ); ?>" 
                          name="<?php echo esc_attr( $this->get_field_name( 'description_' . $i ) ); ?>" rows="2"><?php echo esc_textarea( $description ); ?></textarea>
            </p>
        <?php endfor; ?>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
        
        for ( $i = 1; $i <= 5; $i++ ) {
            $instance['date_' . $i] = ( ! empty( $new_instance['date_' . $i] ) ) ? sanitize_text_field( $new_instance['date_' . $i] ) : '';
            $instance['event_title_' . $i] = ( ! empty( $new_instance['event_title_' . $i] ) ) ? sanitize_text_field( $new_instance['event_title_' . $i] ) : '';
            $instance['description_' . $i] = ( ! empty( $new_instance['description_' . $i] ) ) ? sanitize_textarea_field( $new_instance['description_' . $i] ) : '';
        }
        
        return $instance;
    }
}

/**
 * Posts Grid Widget
 * Displays posts in a 2-column responsive card grid.
 */
class ThemisDB_Posts_Grid_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'themisdb_posts_grid',
            esc_html__( 'ThemisDB: Posts Grid', 'themisdb' ),
            array(
                'description' => esc_html__( 'Display posts in a 2-column card grid with optional category/tag filter', 'themisdb' ),
                'classname'   => 'themisdb-posts-grid-widget',
            )
        );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];

        $title        = ! empty( $instance['title'] )        ? $instance['title']            : '';
        $title        = apply_filters( 'widget_title', $title, $instance, $this->id_base );
        $count        = ! empty( $instance['count'] )        ? absint( $instance['count'] )  : 4;
        $category     = ! empty( $instance['category'] )     ? absint( $instance['category'] ) : 0;
        $show_thumb   = ! empty( $instance['show_thumb'] );
        $show_excerpt = ! empty( $instance['show_excerpt'] );
        $show_meta    = ! empty( $instance['show_meta'] );
        $columns      = ! empty( $instance['columns'] )      ? absint( $instance['columns'] ) : 2;

        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }

        $query_args = array(
            'posts_per_page' => $count,
            'post_status'    => 'publish',
            'ignore_sticky_posts' => 1,
        );
        if ( $category ) {
            $query_args['cat'] = $category;
        }

        $q = new WP_Query( $query_args );

        if ( $q->have_posts() ) :
            $col_class = $columns === 3 ? 'themisdb-grid-3col' : 'themisdb-grid-2col';
            ?>
            <div class="themisdb-posts-grid <?php echo esc_attr( $col_class ); ?>">
                <?php while ( $q->have_posts() ) : $q->the_post(); ?>
                    <article class="tpg-card">
                        <?php if ( $show_thumb && has_post_thumbnail() ) : ?>
                            <a class="tpg-thumb" href="<?php the_permalink(); ?>">
                                <?php the_post_thumbnail( 'themisdb-thumbnail' ); ?>
                            </a>
                        <?php endif; ?>
                        <div class="tpg-body">
                            <h4 class="tpg-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h4>
                            <?php if ( $show_meta ) : ?>
                                <div class="tpg-meta">
                                    <span class="tpg-date">📅 <?php echo esc_html( get_the_date() ); ?></span>
                                    <?php $cats = get_the_category(); if ( $cats ) : ?>
                                        <span class="tpg-cat">📁 <?php echo esc_html( $cats[0]->name ); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ( $show_excerpt ) : ?>
                                <p class="tpg-excerpt"><?php echo wp_trim_words( get_the_excerpt(), 15 ); ?></p>
                            <?php endif; ?>
                            <a class="tpg-readmore" href="<?php the_permalink(); ?>">
                                <?php esc_html_e( 'Read more', 'themisdb' ); ?> →
                            </a>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
            <?php
            wp_reset_postdata();
        else :
            echo '<p>' . esc_html__( 'No posts found.', 'themisdb' ) . '</p>';
        endif;

        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title        = ! empty( $instance['title'] )        ? $instance['title']              : '';
        $count        = ! empty( $instance['count'] )        ? absint( $instance['count'] )    : 4;
        $category     = ! empty( $instance['category'] )     ? absint( $instance['category'] ) : 0;
        $columns      = ! empty( $instance['columns'] )      ? absint( $instance['columns'] )  : 2;
        $show_thumb   = isset( $instance['show_thumb'] )     ? (bool) $instance['show_thumb']   : true;
        $show_excerpt = isset( $instance['show_excerpt'] )   ? (bool) $instance['show_excerpt'] : true;
        $show_meta    = isset( $instance['show_meta'] )      ? (bool) $instance['show_meta']    : true;
        $categories   = get_categories( array( 'hide_empty' => false ) );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'themisdb' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text"
                   value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"><?php esc_html_e( 'Number of posts:', 'themisdb' ); ?></label>
            <input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>" type="number"
                   step="1" min="1" value="<?php echo esc_attr( $count ); ?>" size="3">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'columns' ) ); ?>"><?php esc_html_e( 'Columns:', 'themisdb' ); ?></label>
            <select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'columns' ) ); ?>"
                    name="<?php echo esc_attr( $this->get_field_name( 'columns' ) ); ?>">
                <option value="2" <?php selected( $columns, 2 ); ?>><?php esc_html_e( '2 Columns', 'themisdb' ); ?></option>
                <option value="3" <?php selected( $columns, 3 ); ?>><?php esc_html_e( '3 Columns', 'themisdb' ); ?></option>
            </select>
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'category' ) ); ?>"><?php esc_html_e( 'Category:', 'themisdb' ); ?></label>
            <select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'category' ) ); ?>"
                    name="<?php echo esc_attr( $this->get_field_name( 'category' ) ); ?>">
                <option value="0"><?php esc_html_e( 'All categories', 'themisdb' ); ?></option>
                <?php foreach ( $categories as $cat ) : ?>
                    <option value="<?php echo esc_attr( $cat->term_id ); ?>" <?php selected( $category, $cat->term_id ); ?>>
                        <?php echo esc_html( $cat->name ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_thumb' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'show_thumb' ) ); ?>" value="1"
                   <?php checked( $show_thumb ); ?>>
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_thumb' ) ); ?>"><?php esc_html_e( 'Show thumbnail', 'themisdb' ); ?></label>
        </p>
        <p>
            <input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_excerpt' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'show_excerpt' ) ); ?>" value="1"
                   <?php checked( $show_excerpt ); ?>>
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_excerpt' ) ); ?>"><?php esc_html_e( 'Show excerpt', 'themisdb' ); ?></label>
        </p>
        <p>
            <input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_meta' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'show_meta' ) ); ?>" value="1"
                   <?php checked( $show_meta ); ?>>
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_meta' ) ); ?>"><?php esc_html_e( 'Show date & category', 'themisdb' ); ?></label>
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance                 = array();
        $instance['title']        = ( ! empty( $new_instance['title'] ) )        ? sanitize_text_field( $new_instance['title'] )    : '';
        $instance['count']        = ( ! empty( $new_instance['count'] ) )        ? absint( $new_instance['count'] )                  : 4;
        $instance['category']     = ( ! empty( $new_instance['category'] ) )     ? absint( $new_instance['category'] )               : 0;
        $instance['columns']      = ( ! empty( $new_instance['columns'] ) )      ? absint( $new_instance['columns'] )                : 2;
        $instance['show_thumb']   = ! empty( $new_instance['show_thumb'] )   ? 1 : 0;
        $instance['show_excerpt'] = ! empty( $new_instance['show_excerpt'] ) ? 1 : 0;
        $instance['show_meta']    = ! empty( $new_instance['show_meta'] )    ? 1 : 0;
        return $instance;
    }
}

/**
 * Author Card Widget
 * Displays author avatar, bio, post count and social links.
 */
class ThemisDB_Author_Card_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'themisdb_author_card',
            esc_html__( 'ThemisDB: Author Card', 'themisdb' ),
            array(
                'description' => esc_html__( 'Show an author\'s avatar, bio, post count and social links', 'themisdb' ),
                'classname'   => 'themisdb-author-card-widget',
            )
        );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];

        $title          = ! empty( $instance['title'] )       ? $instance['title']              : '';
        $title          = apply_filters( 'widget_title', $title, $instance, $this->id_base );
        $user_id        = ! empty( $instance['user_id'] )     ? absint( $instance['user_id'] )  : 0;
        $twitter_url    = ! empty( $instance['twitter_url'] ) ? $instance['twitter_url']        : '';
        $github_url     = ! empty( $instance['github_url'] )  ? $instance['github_url']         : '';
        $website_url    = ! empty( $instance['website_url'] ) ? $instance['website_url']        : '';

        // Fall back to first administrator if no user selected
        if ( ! $user_id ) {
            $admins  = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
            $user_id = ! empty( $admins ) ? $admins[0]->ID : 0;
        }

        if ( ! $user_id ) {
            echo $args['after_widget'];
            return;
        }

        $user       = get_userdata( $user_id );
        $post_count = count_user_posts( $user_id, 'post' );
        $bio        = get_user_meta( $user_id, 'description', true );

        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }
        ?>
        <div class="themisdb-author-card">
            <div class="author-card-avatar">
                <?php echo get_avatar( $user_id, 80, '', esc_attr( $user->display_name ), array( 'class' => 'author-avatar-img' ) ); ?>
            </div>
            <div class="author-card-info">
                <h4 class="author-card-name"><?php echo esc_html( $user->display_name ); ?></h4>
                <?php if ( $bio ) : ?>
                    <p class="author-card-bio"><?php echo esc_html( wp_trim_words( $bio, 20 ) ); ?></p>
                <?php endif; ?>
                <div class="author-card-meta">
                    <span class="author-post-count"><span aria-hidden="true">✍️</span> <?php echo sprintf( _n( '%s post', '%s posts', $post_count, 'themisdb' ), number_format_i18n( $post_count ) ); ?></span>
                </div>
                <?php if ( $twitter_url || $github_url || $website_url ) : ?>
                    <div class="author-card-links">
                        <?php if ( $twitter_url ) : ?>
                            <a href="<?php echo esc_url( $twitter_url ); ?>" class="author-link author-link-twitter" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Twitter / X', 'themisdb' ); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.745l7.73-8.835L1.254 2.25H8.08l4.253 5.622 5.911-5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                            </a>
                        <?php endif; ?>
                        <?php if ( $github_url ) : ?>
                            <a href="<?php echo esc_url( $github_url ); ?>" class="author-link author-link-github" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'GitHub', 'themisdb' ); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 .5C5.65.5.5 5.65.5 12a11.5 11.5 0 0 0 7.86 10.92c.57.1.78-.25.78-.55v-1.93c-3.19.69-3.86-1.54-3.86-1.54-.52-1.33-1.28-1.68-1.28-1.68-1.04-.71.08-.7.08-.7 1.15.08 1.75 1.18 1.75 1.18 1.02 1.75 2.68 1.24 3.33.95.1-.74.4-1.24.72-1.53-2.55-.29-5.23-1.27-5.23-5.66 0-1.25.45-2.27 1.18-3.07-.12-.29-.51-1.45.11-3.02 0 0 .96-.31 3.15 1.18A10.97 10.97 0 0 1 12 6.84c.97.004 1.95.13 2.86.38 2.18-1.49 3.14-1.18 3.14-1.18.62 1.57.23 2.73.11 3.02.74.8 1.18 1.82 1.18 3.07 0 4.4-2.68 5.37-5.24 5.65.41.36.78 1.06.78 2.13v3.16c0 .31.2.67.79.55A11.5 11.5 0 0 0 23.5 12C23.5 5.65 18.35.5 12 .5z"/></svg>
                            </a>
                        <?php endif; ?>
                        <?php if ( $website_url ) : ?>
                            <a href="<?php echo esc_url( $website_url ); ?>" class="author-link author-link-website" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Website', 'themisdb' ); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <a href="<?php echo esc_url( get_author_posts_url( $user_id ) ); ?>" class="author-card-all-posts">
                    <?php esc_html_e( 'All posts', 'themisdb' ); ?> →
                </a>
            </div>
        </div>
        <?php
        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title       = ! empty( $instance['title'] )       ? $instance['title']              : esc_html__( 'About the Author', 'themisdb' );
        $user_id     = ! empty( $instance['user_id'] )     ? absint( $instance['user_id'] )  : 0;
        $twitter_url = ! empty( $instance['twitter_url'] ) ? $instance['twitter_url']        : '';
        $github_url  = ! empty( $instance['github_url'] )  ? $instance['github_url']         : '';
        $website_url = ! empty( $instance['website_url'] ) ? $instance['website_url']        : '';
        $users       = get_users( array( 'who' => 'authors' ) );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'themisdb' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text"
                   value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'user_id' ) ); ?>"><?php esc_html_e( 'Author:', 'themisdb' ); ?></label>
            <select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'user_id' ) ); ?>"
                    name="<?php echo esc_attr( $this->get_field_name( 'user_id' ) ); ?>">
                <option value="0"><?php esc_html_e( 'Auto (first admin)', 'themisdb' ); ?></option>
                <?php foreach ( $users as $u ) : ?>
                    <option value="<?php echo esc_attr( $u->ID ); ?>" <?php selected( $user_id, $u->ID ); ?>>
                        <?php echo esc_html( $u->display_name ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'twitter_url' ) ); ?>"><?php esc_html_e( 'Twitter/X URL:', 'themisdb' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'twitter_url' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'twitter_url' ) ); ?>" type="url"
                   value="<?php echo esc_attr( $twitter_url ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'github_url' ) ); ?>"><?php esc_html_e( 'GitHub URL:', 'themisdb' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'github_url' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'github_url' ) ); ?>" type="url"
                   value="<?php echo esc_attr( $github_url ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'website_url' ) ); ?>"><?php esc_html_e( 'Website URL:', 'themisdb' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'website_url' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'website_url' ) ); ?>" type="url"
                   value="<?php echo esc_attr( $website_url ); ?>">
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance                = array();
        $instance['title']       = ( ! empty( $new_instance['title'] ) )       ? sanitize_text_field( $new_instance['title'] )    : '';
        $instance['user_id']     = ( ! empty( $new_instance['user_id'] ) )     ? absint( $new_instance['user_id'] )               : 0;
        $instance['twitter_url'] = ( ! empty( $new_instance['twitter_url'] ) ) ? esc_url_raw( $new_instance['twitter_url'] )      : '';
        $instance['github_url']  = ( ! empty( $new_instance['github_url'] ) )  ? esc_url_raw( $new_instance['github_url'] )       : '';
        $instance['website_url'] = ( ! empty( $new_instance['website_url'] ) ) ? esc_url_raw( $new_instance['website_url'] )      : '';
        return $instance;
    }
}

/**
 * Animated Stats Counter Widget
 * Three configurable stat counters with icon, number and label.
 */
class ThemisDB_Stats_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'themisdb_stats',
            esc_html__( 'ThemisDB: Stats Counter', 'themisdb' ),
            array(
                'description' => esc_html__( 'Display up to 3 animated stat counters with icon, number and label', 'themisdb' ),
                'classname'   => 'themisdb-stats-widget',
            )
        );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];

        $title = ! empty( $instance['title'] ) ? $instance['title'] : '';
        $title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }

        $stats = array();
        for ( $i = 1; $i <= 3; $i++ ) {
            $icon   = ! empty( $instance['icon_' . $i] )   ? $instance['icon_' . $i]   : '';
            $number = ! empty( $instance['number_' . $i] ) ? $instance['number_' . $i] : '';
            $suffix = ! empty( $instance['suffix_' . $i] ) ? $instance['suffix_' . $i] : '';
            $label  = ! empty( $instance['label_' . $i] )  ? $instance['label_' . $i]  : '';
            if ( $number !== '' && $label ) {
                $stats[] = compact( 'icon', 'number', 'suffix', 'label' );
            }
        }

        if ( $stats ) :
            ?>
            <div class="themisdb-stats-grid">
                <?php foreach ( $stats as $stat ) : ?>
                    <div class="themisdb-stat-item">
                        <?php if ( $stat['icon'] ) : ?>
                            <span class="stat-icon" aria-hidden="true"><?php echo esc_html( $stat['icon'] ); ?></span>
                        <?php endif; ?>
                        <span class="stat-number" data-target="<?php echo esc_attr( $stat['number'] ); ?>">
                            <?php echo esc_html( $stat['number'] ); ?>
                        </span><?php echo esc_html( $stat['suffix'] ); ?>
                        <span class="stat-label"><?php echo esc_html( $stat['label'] ); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php
        else :
            echo '<p>' . esc_html__( 'Please add at least one stat in widget settings.', 'themisdb' ) . '</p>';
        endif;

        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'Our Numbers', 'themisdb' );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'themisdb' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text"
                   value="<?php echo esc_attr( $title ); ?>">
        </p>
        <?php for ( $i = 1; $i <= 3; $i++ ) :
            $icon   = ! empty( $instance['icon_' . $i] )   ? $instance['icon_' . $i]   : '';
            $number = ! empty( $instance['number_' . $i] ) ? $instance['number_' . $i] : '';
            $suffix = ! empty( $instance['suffix_' . $i] ) ? $instance['suffix_' . $i] : '';
            $label  = ! empty( $instance['label_' . $i] )  ? $instance['label_' . $i]  : '';
            ?>
            <p><strong><?php echo sprintf( esc_html__( 'Stat %d', 'themisdb' ), $i ); ?></strong></p>
            <p>
                <label for="<?php echo esc_attr( $this->get_field_id( 'icon_' . $i ) ); ?>"><?php esc_html_e( 'Icon (emoji):', 'themisdb' ); ?></label>
                <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'icon_' . $i ) ); ?>"
                       name="<?php echo esc_attr( $this->get_field_name( 'icon_' . $i ) ); ?>" type="text"
                       value="<?php echo esc_attr( $icon ); ?>" placeholder="📝">
            </p>
            <p>
                <label for="<?php echo esc_attr( $this->get_field_id( 'number_' . $i ) ); ?>"><?php esc_html_e( 'Number:', 'themisdb' ); ?></label>
                <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'number_' . $i ) ); ?>"
                       name="<?php echo esc_attr( $this->get_field_name( 'number_' . $i ) ); ?>" type="text"
                       value="<?php echo esc_attr( $number ); ?>" placeholder="500">
            </p>
            <p>
                <label for="<?php echo esc_attr( $this->get_field_id( 'suffix_' . $i ) ); ?>"><?php esc_html_e( 'Suffix (e.g. +, k):', 'themisdb' ); ?></label>
                <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'suffix_' . $i ) ); ?>"
                       name="<?php echo esc_attr( $this->get_field_name( 'suffix_' . $i ) ); ?>" type="text"
                       value="<?php echo esc_attr( $suffix ); ?>" placeholder="+">
            </p>
            <p>
                <label for="<?php echo esc_attr( $this->get_field_id( 'label_' . $i ) ); ?>"><?php esc_html_e( 'Label:', 'themisdb' ); ?></label>
                <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'label_' . $i ) ); ?>"
                       name="<?php echo esc_attr( $this->get_field_name( 'label_' . $i ) ); ?>" type="text"
                       value="<?php echo esc_attr( $label ); ?>" placeholder="<?php esc_attr_e( 'Published Posts', 'themisdb' ); ?>">
            </p>
        <?php endfor; ?>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance          = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
        for ( $i = 1; $i <= 3; $i++ ) {
            $instance['icon_' . $i]   = ( ! empty( $new_instance['icon_' . $i] ) )   ? sanitize_text_field( $new_instance['icon_' . $i] )   : '';
            $instance['number_' . $i] = ( ! empty( $new_instance['number_' . $i] ) ) ? sanitize_text_field( $new_instance['number_' . $i] ) : '';
            $instance['suffix_' . $i] = ( ! empty( $new_instance['suffix_' . $i] ) ) ? sanitize_text_field( $new_instance['suffix_' . $i] ) : '';
            $instance['label_' . $i]  = ( ! empty( $new_instance['label_' . $i] ) )  ? sanitize_text_field( $new_instance['label_' . $i] )  : '';
        }
        return $instance;
    }
}

/**
 * Popular Posts Widget
 * Shows most-commented posts ranked by comment count.
 */
class ThemisDB_Popular_Posts_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'themisdb_popular_posts',
            esc_html__( 'ThemisDB: Popular Posts', 'themisdb' ),
            array(
                'description' => esc_html__( 'List the most popular (most-commented) posts with optional thumbnail and rank badge', 'themisdb' ),
                'classname'   => 'themisdb-popular-posts-widget',
            )
        );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];

        $title       = ! empty( $instance['title'] )       ? $instance['title']            : '';
        $title       = apply_filters( 'widget_title', $title, $instance, $this->id_base );
        $count       = ! empty( $instance['count'] )       ? absint( $instance['count'] )  : 5;
        $show_thumb  = ! empty( $instance['show_thumb'] );
        $show_count  = ! empty( $instance['show_count'] );

        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }

        $popular = new WP_Query( array(
            'posts_per_page' => $count,
            'orderby'        => 'comment_count',
            'order'          => 'DESC',
            'post_status'    => 'publish',
            'ignore_sticky_posts' => 1,
        ) );

        if ( $popular->have_posts() ) :
            $rank = 1;
            ?>
            <ol class="themisdb-popular-posts">
                <?php while ( $popular->have_posts() ) : $popular->the_post(); ?>
                    <li class="popular-post-item">
                        <span class="popular-rank"><?php echo esc_html( $rank ); ?></span>
                        <?php if ( $show_thumb && has_post_thumbnail() ) : ?>
                            <a class="popular-thumb" href="<?php the_permalink(); ?>">
                                <?php the_post_thumbnail( array( 60, 60 ) ); ?>
                            </a>
                        <?php endif; ?>
                        <div class="popular-post-info">
                            <a class="popular-post-title" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            <div class="popular-post-meta">
                                <span class="popular-date">📅 <?php echo esc_html( get_the_date() ); ?></span>
                                <?php if ( $show_count ) : ?>
                                    <span class="popular-comments">💬 <?php echo get_comments_number(); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </li>
                    <?php $rank++; ?>
                <?php endwhile; ?>
            </ol>
            <?php
            wp_reset_postdata();
        else :
            echo '<p>' . esc_html__( 'No posts found.', 'themisdb' ) . '</p>';
        endif;

        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title      = ! empty( $instance['title'] )      ? $instance['title']            : esc_html__( 'Popular Posts', 'themisdb' );
        $count      = ! empty( $instance['count'] )      ? absint( $instance['count'] )  : 5;
        $show_thumb = isset( $instance['show_thumb'] )   ? (bool) $instance['show_thumb'] : true;
        $show_count = isset( $instance['show_count'] )   ? (bool) $instance['show_count'] : true;
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'themisdb' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text"
                   value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"><?php esc_html_e( 'Number of posts:', 'themisdb' ); ?></label>
            <input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>" type="number"
                   step="1" min="1" value="<?php echo esc_attr( $count ); ?>" size="3">
        </p>
        <p>
            <input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_thumb' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'show_thumb' ) ); ?>" value="1"
                   <?php checked( $show_thumb ); ?>>
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_thumb' ) ); ?>"><?php esc_html_e( 'Show thumbnail', 'themisdb' ); ?></label>
        </p>
        <p>
            <input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_count' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'show_count' ) ); ?>" value="1"
                   <?php checked( $show_count ); ?>>
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_count' ) ); ?>"><?php esc_html_e( 'Show comment count', 'themisdb' ); ?></label>
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance               = array();
        $instance['title']      = ( ! empty( $new_instance['title'] ) )      ? sanitize_text_field( $new_instance['title'] ) : '';
        $instance['count']      = ( ! empty( $new_instance['count'] ) )      ? absint( $new_instance['count'] )              : 5;
        $instance['show_thumb'] = ! empty( $new_instance['show_thumb'] ) ? 1 : 0;
        $instance['show_count'] = ! empty( $new_instance['show_count'] ) ? 1 : 0;
        return $instance;
    }
}

/**
 * Newsletter Sign-up Widget
 * Simple email opt-in form. Hooks into themisdb_newsletter_submit action.
 */
class ThemisDB_Newsletter_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'themisdb_newsletter',
            esc_html__( 'ThemisDB: Newsletter Sign-up', 'themisdb' ),
            array(
                'description' => esc_html__( 'Display an email newsletter sign-up form', 'themisdb' ),
                'classname'   => 'themisdb-newsletter-widget',
            )
        );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];

        $title        = ! empty( $instance['title'] )        ? $instance['title']        : '';
        $title        = apply_filters( 'widget_title', $title, $instance, $this->id_base );
        $description  = ! empty( $instance['description'] )  ? $instance['description']  : '';
        $button_label = ! empty( $instance['button_label'] ) ? $instance['button_label'] : esc_html__( 'Subscribe', 'themisdb' );
        $placeholder  = ! empty( $instance['placeholder'] )  ? $instance['placeholder']  : esc_html__( 'Your email address', 'themisdb' );
        $action_url   = ! empty( $instance['action_url'] )   ? $instance['action_url']   : '';

        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }

        $form_action = $action_url ? esc_url( $action_url ) : esc_url( admin_url( 'admin-post.php' ) );
        ?>
        <div class="themisdb-newsletter">
            <?php if ( $description ) : ?>
                <p class="newsletter-description"><?php echo esc_html( $description ); ?></p>
            <?php endif; ?>
            <form class="newsletter-form" method="post" action="<?php echo $form_action; ?>">
                <?php if ( ! $action_url ) : ?>
                    <input type="hidden" name="action" value="themisdb_newsletter_subscribe">
                    <?php wp_nonce_field( 'themisdb_newsletter', 'themisdb_newsletter_nonce' ); ?>
                <?php endif; ?>
                <div class="newsletter-input-row">
                    <label for="newsletter-email-<?php echo esc_attr( $this->id ); ?>" class="screen-reader-text">
                        <?php esc_html_e( 'Email address', 'themisdb' ); ?>
                    </label>
                    <input type="email" id="newsletter-email-<?php echo esc_attr( $this->id ); ?>"
                           name="newsletter_email" class="newsletter-email"
                           placeholder="<?php echo esc_attr( $placeholder ); ?>"
                           required aria-required="true">
                    <button type="submit" class="newsletter-submit">
                        <?php echo esc_html( $button_label ); ?>
                    </button>
                </div>
                <p class="newsletter-privacy">
                    <span aria-hidden="true">🔒</span> <?php esc_html_e( 'No spam. Unsubscribe any time.', 'themisdb' ); ?>
                </p>
            </form>
        </div>
        <?php
        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title        = ! empty( $instance['title'] )        ? $instance['title']        : esc_html__( 'Stay Updated', 'themisdb' );
        $description  = ! empty( $instance['description'] )  ? $instance['description']  : '';
        $button_label = ! empty( $instance['button_label'] ) ? $instance['button_label'] : esc_html__( 'Subscribe', 'themisdb' );
        $placeholder  = ! empty( $instance['placeholder'] )  ? $instance['placeholder']  : esc_html__( 'Your email address', 'themisdb' );
        $action_url   = ! empty( $instance['action_url'] )   ? $instance['action_url']   : '';
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'themisdb' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text"
                   value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'description' ) ); ?>"><?php esc_html_e( 'Description:', 'themisdb' ); ?></label>
            <textarea class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'description' ) ); ?>"
                      name="<?php echo esc_attr( $this->get_field_name( 'description' ) ); ?>" rows="3"><?php echo esc_textarea( $description ); ?></textarea>
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'placeholder' ) ); ?>"><?php esc_html_e( 'Input placeholder:', 'themisdb' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'placeholder' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'placeholder' ) ); ?>" type="text"
                   value="<?php echo esc_attr( $placeholder ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'button_label' ) ); ?>"><?php esc_html_e( 'Button label:', 'themisdb' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'button_label' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'button_label' ) ); ?>" type="text"
                   value="<?php echo esc_attr( $button_label ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'action_url' ) ); ?>"><?php esc_html_e( 'Custom form action URL (optional):', 'themisdb' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'action_url' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'action_url' ) ); ?>" type="url"
                   value="<?php echo esc_attr( $action_url ); ?>" placeholder="https://...">
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance                 = array();
        $instance['title']        = ( ! empty( $new_instance['title'] ) )        ? sanitize_text_field( $new_instance['title'] )      : '';
        $instance['description']  = ( ! empty( $new_instance['description'] ) )  ? sanitize_textarea_field( $new_instance['description'] ) : '';
        $instance['button_label'] = ( ! empty( $new_instance['button_label'] ) ) ? sanitize_text_field( $new_instance['button_label'] ) : '';
        $instance['placeholder']  = ( ! empty( $new_instance['placeholder'] ) )  ? sanitize_text_field( $new_instance['placeholder'] )  : '';
        $instance['action_url']   = ( ! empty( $new_instance['action_url'] ) )   ? esc_url_raw( $new_instance['action_url'] )           : '';
        return $instance;
    }
}

/**
 * Video Embed Widget
 * Embeds a YouTube or Vimeo video with optional title and description.
 */
class ThemisDB_Video_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'themisdb_video',
            esc_html__( 'ThemisDB: Video Embed', 'themisdb' ),
            array(
                'description' => esc_html__( 'Embed a YouTube or Vimeo video with optional title and description', 'themisdb' ),
                'classname'   => 'themisdb-video-widget',
            )
        );
    }

    /** Extract a normalised embed URL from a YouTube or Vimeo URL. */
    private function get_embed_url( $raw_url ) {
        $url = trim( $raw_url );

        // YouTube: youtu.be/<id> or ?v=<id> or /embed/<id>
        if ( preg_match( '#(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|v/|shorts/))([a-zA-Z0-9_\-]{11})#', $url, $m ) ) {
            return 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?rel=0';
        }

        // Vimeo: vimeo.com/<id> or player.vimeo.com/video/<id>
        if ( preg_match( '#(?:vimeo\.com/(?:video/)?|player\.vimeo\.com/video/)(\d+)#', $url, $m ) ) {
            return 'https://player.vimeo.com/video/' . $m[1];
        }

        return '';
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];

        $title       = ! empty( $instance['title'] )       ? $instance['title']       : '';
        $title       = apply_filters( 'widget_title', $title, $instance, $this->id_base );
        $video_url   = ! empty( $instance['video_url'] )   ? $instance['video_url']   : '';
        $description = ! empty( $instance['description'] ) ? $instance['description'] : '';
        $aspect      = ! empty( $instance['aspect'] )      ? $instance['aspect']      : '16x9';

        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }

        $embed_url = $this->get_embed_url( $video_url );

        if ( $embed_url ) :
            $ratio_class = ( $aspect === '4x3' ) ? 'video-ratio-4x3' : 'video-ratio-16x9';
            ?>
            <div class="themisdb-video-embed">
                <div class="video-wrapper <?php echo esc_attr( $ratio_class ); ?>">
                    <iframe src="<?php echo esc_url( $embed_url ); ?>"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen
                            loading="lazy"
                            title="<?php echo $title ? esc_attr( $title ) : esc_attr__( 'Embedded video', 'themisdb' ); ?>"></iframe>
                </div>
                <?php if ( $description ) : ?>
                    <p class="video-description"><?php echo esc_html( $description ); ?></p>
                <?php endif; ?>
            </div>
            <?php
        elseif ( $video_url ) :
            echo '<p>' . esc_html__( 'Invalid or unsupported video URL. Please use a YouTube or Vimeo URL.', 'themisdb' ) . '</p>';
        else :
            echo '<p>' . esc_html__( 'Please enter a video URL in widget settings.', 'themisdb' ) . '</p>';
        endif;

        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title       = ! empty( $instance['title'] )       ? $instance['title']       : '';
        $video_url   = ! empty( $instance['video_url'] )   ? $instance['video_url']   : '';
        $description = ! empty( $instance['description'] ) ? $instance['description'] : '';
        $aspect      = ! empty( $instance['aspect'] )      ? $instance['aspect']      : '16x9';
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'themisdb' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text"
                   value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'video_url' ) ); ?>"><?php esc_html_e( 'YouTube or Vimeo URL:', 'themisdb' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'video_url' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'video_url' ) ); ?>" type="url"
                   value="<?php echo esc_attr( $video_url ); ?>" placeholder="https://www.youtube.com/watch?v=...">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'aspect' ) ); ?>"><?php esc_html_e( 'Aspect ratio:', 'themisdb' ); ?></label>
            <select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'aspect' ) ); ?>"
                    name="<?php echo esc_attr( $this->get_field_name( 'aspect' ) ); ?>">
                <option value="16x9" <?php selected( $aspect, '16x9' ); ?>><?php esc_html_e( '16:9 (widescreen)', 'themisdb' ); ?></option>
                <option value="4x3"  <?php selected( $aspect, '4x3' ); ?>><?php esc_html_e( '4:3 (classic)', 'themisdb' ); ?></option>
            </select>
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'description' ) ); ?>"><?php esc_html_e( 'Description (optional):', 'themisdb' ); ?></label>
            <textarea class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'description' ) ); ?>"
                      name="<?php echo esc_attr( $this->get_field_name( 'description' ) ); ?>" rows="3"><?php echo esc_textarea( $description ); ?></textarea>
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance                = array();
        $instance['title']       = ( ! empty( $new_instance['title'] ) )       ? sanitize_text_field( $new_instance['title'] )         : '';
        $instance['video_url']   = ( ! empty( $new_instance['video_url'] ) )   ? esc_url_raw( $new_instance['video_url'] )             : '';
        $instance['description'] = ( ! empty( $new_instance['description'] ) ) ? sanitize_textarea_field( $new_instance['description'] ) : '';
        $instance['aspect']      = ( ! empty( $new_instance['aspect'] ) && in_array( $new_instance['aspect'], array( '16x9', '4x3' ), true ) )
                                   ? $new_instance['aspect'] : '16x9';
        return $instance;
    }
}

/**
 * Tabbed Posts Widget
 * Shows Latest / Popular / Commented tabs in a single widget. Tabs are driven
 * by pure CSS :target fallback plus progressive JS enhancement (no jQuery needed).
 */
class ThemisDB_Tabbed_Posts_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'themisdb_tabbed_posts',
            esc_html__( 'ThemisDB: Tabbed Posts', 'themisdb' ),
            array(
                'description' => esc_html__( 'Show Latest, Popular and Most-Commented posts in one tabbed widget', 'themisdb' ),
                'classname'   => 'themisdb-tabbed-posts-widget',
            )
        );
    }

    /** Run a simple posts query and return WP_Query. */
    private function run_query( $count, $orderby, $meta_key = '' ) {
        $args = array(
            'posts_per_page'      => $count,
            'post_status'         => 'publish',
            'ignore_sticky_posts' => 1,
            'orderby'             => $orderby,
            'order'               => 'DESC',
            'no_found_rows'       => true,
        );
        if ( $meta_key ) {
            $args['meta_key'] = $meta_key;
        }
        return new WP_Query( $args );
    }

    /** Render a single list of posts. */
    private function render_list( $query, $show_thumb, $show_date ) {
        if ( ! $query->have_posts() ) {
            echo '<li class="tpw-empty">' . esc_html__( 'No posts found.', 'themisdb' ) . '</li>';
            return;
        }
        while ( $query->have_posts() ) {
            $query->the_post();
            ?>
            <li class="tpw-item">
                <?php if ( $show_thumb && has_post_thumbnail() ) : ?>
                    <a class="tpw-thumb" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
                        <?php the_post_thumbnail( array( 52, 52 ) ); ?>
                    </a>
                <?php endif; ?>
                <div class="tpw-info">
                    <a class="tpw-title" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    <?php if ( $show_date ) : ?>
                        <span class="tpw-date"><?php echo esc_html( get_the_date() ); ?></span>
                    <?php endif; ?>
                </div>
            </li>
            <?php
        }
        wp_reset_postdata();
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];

        $title      = ! empty( $instance['title'] )      ? apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base ) : '';
        $count      = ! empty( $instance['count'] )      ? absint( $instance['count'] )   : 5;
        $show_thumb = ! empty( $instance['show_thumb'] );
        $show_date  = ! empty( $instance['show_date'] );
        $uid        = 'tpw-' . esc_attr( $this->id );

        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }

        $q_latest    = $this->run_query( $count, 'date' );
        $q_popular   = $this->run_query( $count, 'comment_count' );
        $q_commented = $this->run_query( $count, 'comment_count' );
        ?>
        <div class="themisdb-tabbed-posts" id="<?php echo esc_attr( $uid ); ?>">
            <div class="tpw-tabs" role="tablist">
                <button class="tpw-tab is-active" role="tab"
                        aria-selected="true"  aria-controls="<?php echo esc_attr( $uid ); ?>-latest"
                        id="<?php echo esc_attr( $uid ); ?>-tab-latest">
                    <?php esc_html_e( 'Latest', 'themisdb' ); ?>
                </button>
                <button class="tpw-tab" role="tab"
                        aria-selected="false" aria-controls="<?php echo esc_attr( $uid ); ?>-popular"
                        id="<?php echo esc_attr( $uid ); ?>-tab-popular">
                    <?php esc_html_e( 'Popular', 'themisdb' ); ?>
                </button>
                <button class="tpw-tab" role="tab"
                        aria-selected="false" aria-controls="<?php echo esc_attr( $uid ); ?>-commented"
                        id="<?php echo esc_attr( $uid ); ?>-tab-commented">
                    <?php esc_html_e( 'Commented', 'themisdb' ); ?>
                </button>
            </div>
            <div class="tpw-panels">
                <div class="tpw-panel is-active" id="<?php echo esc_attr( $uid ); ?>-latest"
                     role="tabpanel" aria-labelledby="<?php echo esc_attr( $uid ); ?>-tab-latest">
                    <ul class="tpw-list">
                        <?php $this->render_list( $q_latest,    $show_thumb, $show_date ); ?>
                    </ul>
                </div>
                <div class="tpw-panel" id="<?php echo esc_attr( $uid ); ?>-popular"
                     role="tabpanel" aria-labelledby="<?php echo esc_attr( $uid ); ?>-tab-popular">
                    <ul class="tpw-list">
                        <?php $this->render_list( $q_popular,   $show_thumb, $show_date ); ?>
                    </ul>
                </div>
                <div class="tpw-panel" id="<?php echo esc_attr( $uid ); ?>-commented"
                     role="tabpanel" aria-labelledby="<?php echo esc_attr( $uid ); ?>-tab-commented">
                    <ul class="tpw-list">
                        <?php $this->render_list( $q_commented, $show_thumb, $show_date ); ?>
                    </ul>
                </div>
            </div>
        </div>
        <?php
        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title      = ! empty( $instance['title'] )      ? $instance['title']            : esc_html__( 'Posts', 'themisdb' );
        $count      = ! empty( $instance['count'] )      ? absint( $instance['count'] )  : 5;
        $show_thumb = isset( $instance['show_thumb'] )   ? (bool) $instance['show_thumb'] : true;
        $show_date  = isset( $instance['show_date'] )    ? (bool) $instance['show_date']  : true;
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'themisdb' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text"
                   value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"><?php esc_html_e( 'Posts per tab:', 'themisdb' ); ?></label>
            <input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>" type="number"
                   step="1" min="1" value="<?php echo esc_attr( $count ); ?>" size="3">
        </p>
        <p>
            <input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_thumb' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'show_thumb' ) ); ?>" value="1"
                   <?php checked( $show_thumb ); ?>>
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_thumb' ) ); ?>"><?php esc_html_e( 'Show thumbnail', 'themisdb' ); ?></label>
        </p>
        <p>
            <input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_date' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'show_date' ) ); ?>" value="1"
                   <?php checked( $show_date ); ?>>
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_date' ) ); ?>"><?php esc_html_e( 'Show date', 'themisdb' ); ?></label>
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance               = array();
        $instance['title']      = ( ! empty( $new_instance['title'] ) )      ? sanitize_text_field( $new_instance['title'] ) : '';
        $instance['count']      = ( ! empty( $new_instance['count'] ) )      ? absint( $new_instance['count'] )              : 5;
        $instance['show_thumb'] = ! empty( $new_instance['show_thumb'] ) ? 1 : 0;
        $instance['show_date']  = ! empty( $new_instance['show_date'] )  ? 1 : 0;
        return $instance;
    }
}

/**
 * Recent Comments Widget (styled)
 * Displays recent comments with Gravatar, truncated excerpt and post link.
 */
class ThemisDB_Recent_Comments_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'themisdb_recent_comments',
            esc_html__( 'ThemisDB: Recent Comments', 'themisdb' ),
            array(
                'description' => esc_html__( 'Show recent comments with Gravatar, excerpt and post link', 'themisdb' ),
                'classname'   => 'themisdb-recent-comments-widget',
            )
        );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];

        $title      = ! empty( $instance['title'] )      ? apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base ) : '';
        $count      = ! empty( $instance['count'] )      ? absint( $instance['count'] )  : 5;
        $show_avatar = ! empty( $instance['show_avatar'] );
        $excerpt_len = ! empty( $instance['excerpt_len'] ) ? absint( $instance['excerpt_len'] ) : 12;

        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }

        $comments = get_comments( array(
            'number'  => $count,
            'status'  => 'approve',
            'type'    => 'comment',
            'orderby' => 'comment_date_gmt',
            'order'   => 'DESC',
        ) );

        if ( $comments ) :
            ?>
            <ul class="themisdb-recent-comments">
                <?php foreach ( $comments as $comment ) :
                    $post  = get_post( $comment->comment_post_ID );
                    $clink = esc_url( get_comment_link( $comment ) );
                    $plink = esc_url( get_permalink( $comment->comment_post_ID ) );
                    ?>
                    <li class="trc-item">
                        <?php if ( $show_avatar ) : ?>
                            <div class="trc-avatar">
                                <?php echo get_avatar( $comment, 40, '', esc_attr( $comment->comment_author ), array( 'class' => 'trc-gravatar' ) ); ?>
                            </div>
                        <?php endif; ?>
                        <div class="trc-body">
                            <div class="trc-meta">
                                <strong class="trc-author"><?php echo esc_html( $comment->comment_author ); ?></strong>
                                <?php esc_html_e( 'on', 'themisdb' ); ?>
                                <a class="trc-post-link" href="<?php echo $plink; ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a>
                            </div>
                            <a class="trc-excerpt" href="<?php echo $clink; ?>">
                                <?php echo esc_html( wp_trim_words( $comment->comment_content, $excerpt_len ) ); ?>
                            </a>
                            <time class="trc-date" datetime="<?php echo esc_attr( get_comment_date( 'c', $comment ) ); ?>">
                                <?php echo esc_html( get_comment_date( get_option( 'date_format' ), $comment ) ); ?>
                            </time>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php
        else :
            echo '<p>' . esc_html__( 'No comments yet.', 'themisdb' ) . '</p>';
        endif;

        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title       = ! empty( $instance['title'] )       ? $instance['title']             : esc_html__( 'Recent Comments', 'themisdb' );
        $count       = ! empty( $instance['count'] )       ? absint( $instance['count'] )   : 5;
        $show_avatar = isset( $instance['show_avatar'] )   ? (bool) $instance['show_avatar'] : true;
        $excerpt_len = ! empty( $instance['excerpt_len'] ) ? absint( $instance['excerpt_len'] ) : 12;
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'themisdb' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text"
                   value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"><?php esc_html_e( 'Number of comments:', 'themisdb' ); ?></label>
            <input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>" type="number"
                   step="1" min="1" value="<?php echo esc_attr( $count ); ?>" size="3">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'excerpt_len' ) ); ?>"><?php esc_html_e( 'Excerpt length (words):', 'themisdb' ); ?></label>
            <input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'excerpt_len' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'excerpt_len' ) ); ?>" type="number"
                   step="1" min="1" value="<?php echo esc_attr( $excerpt_len ); ?>" size="3">
        </p>
        <p>
            <input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_avatar' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'show_avatar' ) ); ?>" value="1"
                   <?php checked( $show_avatar ); ?>>
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_avatar' ) ); ?>"><?php esc_html_e( 'Show Gravatar', 'themisdb' ); ?></label>
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance                = array();
        $instance['title']       = ( ! empty( $new_instance['title'] ) )       ? sanitize_text_field( $new_instance['title'] )    : '';
        $instance['count']       = ( ! empty( $new_instance['count'] ) )       ? absint( $new_instance['count'] )                  : 5;
        $instance['excerpt_len'] = ( ! empty( $new_instance['excerpt_len'] ) ) ? absint( $new_instance['excerpt_len'] )            : 12;
        $instance['show_avatar'] = ! empty( $new_instance['show_avatar'] ) ? 1 : 0;
        return $instance;
    }
}

/**
 * Category Grid Widget
 * Visual tile grid of categories with post-count badge.
 */
class ThemisDB_Category_Grid_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'themisdb_category_grid',
            esc_html__( 'ThemisDB: Category Grid', 'themisdb' ),
            array(
                'description' => esc_html__( 'Display categories as a visual tile grid with post-count badge', 'themisdb' ),
                'classname'   => 'themisdb-category-grid-widget',
            )
        );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];

        $title        = ! empty( $instance['title'] )        ? apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base ) : '';
        $limit        = ! empty( $instance['limit'] )        ? absint( $instance['limit'] )        : 8;
        $show_count   = ! empty( $instance['show_count'] );
        $hide_empty   = ! isset( $instance['hide_empty'] )   || ! empty( $instance['hide_empty'] );
        $columns      = ! empty( $instance['columns'] )      ? absint( $instance['columns'] )      : 3;

        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }

        $categories = get_categories( array(
            'number'     => $limit,
            'hide_empty' => $hide_empty,
            'orderby'    => 'count',
            'order'      => 'DESC',
        ) );

        if ( $categories ) :
            $col_class = 'tcg-grid-' . $columns . 'col';
            ?>
            <div class="themisdb-category-grid <?php echo esc_attr( $col_class ); ?>">
                <?php foreach ( $categories as $cat ) :
                    $color_index = ( $cat->term_id % 6 ) + 1;
                    ?>
                    <a class="tcg-tile tcg-color-<?php echo esc_attr( $color_index ); ?>"
                       href="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>">
                        <span class="tcg-name"><?php echo esc_html( $cat->name ); ?></span>
                        <?php if ( $show_count ) : ?>
                            <span class="tcg-count"><?php echo absint( $cat->count ); ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php
        else :
            echo '<p>' . esc_html__( 'No categories found.', 'themisdb' ) . '</p>';
        endif;

        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title      = ! empty( $instance['title'] )      ? $instance['title']            : esc_html__( 'Categories', 'themisdb' );
        $limit      = ! empty( $instance['limit'] )      ? absint( $instance['limit'] )  : 8;
        $columns    = ! empty( $instance['columns'] )    ? absint( $instance['columns'] ) : 3;
        $show_count = isset( $instance['show_count'] )   ? (bool) $instance['show_count'] : true;
        $hide_empty = ! isset( $instance['hide_empty'] ) || ! empty( $instance['hide_empty'] );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'themisdb' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text"
                   value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>"><?php esc_html_e( 'Max categories:', 'themisdb' ); ?></label>
            <input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'limit' ) ); ?>" type="number"
                   step="1" min="1" value="<?php echo esc_attr( $limit ); ?>" size="3">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'columns' ) ); ?>"><?php esc_html_e( 'Columns:', 'themisdb' ); ?></label>
            <select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'columns' ) ); ?>"
                    name="<?php echo esc_attr( $this->get_field_name( 'columns' ) ); ?>">
                <option value="2" <?php selected( $columns, 2 ); ?>><?php esc_html_e( '2', 'themisdb' ); ?></option>
                <option value="3" <?php selected( $columns, 3 ); ?>><?php esc_html_e( '3', 'themisdb' ); ?></option>
                <option value="4" <?php selected( $columns, 4 ); ?>><?php esc_html_e( '4', 'themisdb' ); ?></option>
            </select>
        </p>
        <p>
            <input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_count' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'show_count' ) ); ?>" value="1"
                   <?php checked( $show_count ); ?>>
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_count' ) ); ?>"><?php esc_html_e( 'Show post count badge', 'themisdb' ); ?></label>
        </p>
        <p>
            <input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'hide_empty' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'hide_empty' ) ); ?>" value="1"
                   <?php checked( $hide_empty ); ?>>
            <label for="<?php echo esc_attr( $this->get_field_id( 'hide_empty' ) ); ?>"><?php esc_html_e( 'Hide empty categories', 'themisdb' ); ?></label>
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance               = array();
        $instance['title']      = ( ! empty( $new_instance['title'] ) )   ? sanitize_text_field( $new_instance['title'] ) : '';
        $instance['limit']      = ( ! empty( $new_instance['limit'] ) )   ? absint( $new_instance['limit'] )              : 8;
        $instance['columns']    = ( ! empty( $new_instance['columns'] ) ) ? absint( $new_instance['columns'] )            : 3;
        $instance['show_count'] = ! empty( $new_instance['show_count'] ) ? 1 : 0;
        $instance['hide_empty'] = ! empty( $new_instance['hide_empty'] ) ? 1 : 0;
        return $instance;
    }
}

/**
 * Post Ticker Widget
 * A horizontal scrolling news-ticker strip of post titles.
 * Animation is pure CSS; respects prefers-reduced-motion by pausing the
 * marquee and showing the list statically instead.
 */
class ThemisDB_Post_Ticker_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'themisdb_post_ticker',
            esc_html__( 'ThemisDB: Post Ticker', 'themisdb' ),
            array(
                'description' => esc_html__( 'Horizontal scrolling news-ticker strip of post titles (CSS animation, no JS)', 'themisdb' ),
                'classname'   => 'themisdb-post-ticker-widget',
            )
        );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];

        $title    = ! empty( $instance['title'] )    ? apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base ) : '';
        $label    = ! empty( $instance['label'] )    ? $instance['label']            : esc_html__( 'News', 'themisdb' );
        $count    = ! empty( $instance['count'] )    ? absint( $instance['count'] )  : 8;
        $category = ! empty( $instance['category'] ) ? absint( $instance['category'] ) : 0;
        $speed    = ! empty( $instance['speed'] )    ? absint( $instance['speed'] )  : 30;

        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }

        $query_args = array(
            'posts_per_page'      => $count,
            'post_status'         => 'publish',
            'ignore_sticky_posts' => 1,
            'no_found_rows'       => true,
        );
        if ( $category ) {
            $query_args['cat'] = $category;
        }

        $q = new WP_Query( $query_args );

        if ( ! $q->have_posts() ) {
            echo $args['after_widget'];
            return;
        }

        $items = array();
        while ( $q->have_posts() ) {
            $q->the_post();
            $items[] = '<a class="ticker-link" href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a>';
        }
        wp_reset_postdata();

        // Duplicate items so the CSS infinite-scroll feels seamless
        $strip_html = implode( '<span class="ticker-sep" aria-hidden="true"> ◆ </span>', array_merge( $items, $items ) );
        $duration   = count( $items ) * $speed / 10;
        ?>
        <div class="themisdb-post-ticker" style="--ticker-duration:<?php echo esc_attr( $duration ); ?>s">
            <?php if ( $label ) : ?>
                <span class="ticker-label"><?php echo esc_html( $label ); ?></span>
            <?php endif; ?>
            <div class="ticker-track-wrapper" aria-live="off">
                <div class="ticker-track">
                    <?php echo $strip_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- title/permalink escaped per-item; separator HTML is static and safe by design ?>
                </div>
            </div>
        </div>
        <?php
        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title    = ! empty( $instance['title'] )    ? $instance['title']            : '';
        $label    = ! empty( $instance['label'] )    ? $instance['label']            : esc_html__( 'News', 'themisdb' );
        $count    = ! empty( $instance['count'] )    ? absint( $instance['count'] )  : 8;
        $category = ! empty( $instance['category'] ) ? absint( $instance['category'] ) : 0;
        $speed    = ! empty( $instance['speed'] )    ? absint( $instance['speed'] )  : 30;
        $cats     = get_categories( array( 'hide_empty' => true ) );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Widget title (optional):', 'themisdb' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text"
                   value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'label' ) ); ?>"><?php esc_html_e( 'Ticker label:', 'themisdb' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'label' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'label' ) ); ?>" type="text"
                   value="<?php echo esc_attr( $label ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"><?php esc_html_e( 'Number of posts:', 'themisdb' ); ?></label>
            <input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>" type="number"
                   step="1" min="1" value="<?php echo esc_attr( $count ); ?>" size="3">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'category' ) ); ?>"><?php esc_html_e( 'Category (optional):', 'themisdb' ); ?></label>
            <select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'category' ) ); ?>"
                    name="<?php echo esc_attr( $this->get_field_name( 'category' ) ); ?>">
                <option value="0"><?php esc_html_e( 'All categories', 'themisdb' ); ?></option>
                <?php foreach ( $cats as $cat ) : ?>
                    <option value="<?php echo esc_attr( $cat->term_id ); ?>" <?php selected( $category, $cat->term_id ); ?>>
                        <?php echo esc_html( $cat->name ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'speed' ) ); ?>"><?php esc_html_e( 'Speed (higher = slower, 10–60):', 'themisdb' ); ?></label>
            <input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'speed' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'speed' ) ); ?>" type="number"
                   step="5" min="10" max="60" value="<?php echo esc_attr( $speed ); ?>" size="3">
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance              = array();
        $instance['title']     = ( ! empty( $new_instance['title'] ) )    ? sanitize_text_field( $new_instance['title'] )    : '';
        $instance['label']     = ( ! empty( $new_instance['label'] ) )    ? sanitize_text_field( $new_instance['label'] )    : '';
        $instance['count']     = ( ! empty( $new_instance['count'] ) )    ? absint( $new_instance['count'] )                  : 8;
        $instance['category']  = ( ! empty( $new_instance['category'] ) ) ? absint( $new_instance['category'] )               : 0;
        $raw_speed             = isset( $new_instance['speed'] )          ? absint( $new_instance['speed'] )                  : 30;
        $instance['speed']     = max( 10, min( 60, $raw_speed ) );
        return $instance;
    }
}

/**
 * Related Posts Widget
 * On single post pages shows posts sharing tags/categories.
 * On archive/front pages falls back to a configurable "latest posts" preview.
 */
class ThemisDB_Related_Posts_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'themisdb_related_posts',
            esc_html__( 'ThemisDB: Related Posts', 'themisdb' ),
            array(
                'description' => esc_html__( 'Show posts related to the current single post (by tag or category); falls back to latest posts elsewhere', 'themisdb' ),
                'classname'   => 'themisdb-related-posts-widget',
            )
        );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];

        $title       = ! empty( $instance['title'] )       ? apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base ) : '';
        $count       = ! empty( $instance['count'] )       ? absint( $instance['count'] )   : 4;
        $match_by    = ! empty( $instance['match_by'] )    ? $instance['match_by']           : 'tag';
        $show_thumb  = ! empty( $instance['show_thumb'] );

        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }

        $query_args = array(
            'posts_per_page'      => $count,
            'post_status'         => 'publish',
            'ignore_sticky_posts' => 1,
            'no_found_rows'       => true,
        );

        if ( is_singular( 'post' ) ) {
            global $post;
            $query_args['post__not_in'] = array( $post->ID );

            if ( $match_by === 'tag' ) {
                $tags = wp_get_post_tags( $post->ID, array( 'fields' => 'ids' ) );
                if ( $tags ) {
                    $query_args['tag__in'] = $tags;
                } else {
                    // fall back to category if no tags
                    $cats = wp_get_post_categories( $post->ID );
                    if ( $cats ) {
                        $query_args['category__in'] = $cats;
                    }
                }
            } else {
                $cats = wp_get_post_categories( $post->ID );
                if ( $cats ) {
                    $query_args['category__in'] = $cats;
                }
            }
        }
        // If not on a single post the query_args already pull latest posts

        $q = new WP_Query( $query_args );

        if ( $q->have_posts() ) :
            ?>
            <ul class="themisdb-related-posts">
                <?php while ( $q->have_posts() ) : $q->the_post(); ?>
                    <li class="trp-item">
                        <?php if ( $show_thumb && has_post_thumbnail() ) : ?>
                            <a class="trp-thumb" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
                                <?php the_post_thumbnail( array( 56, 56 ) ); ?>
                            </a>
                        <?php endif; ?>
                        <div class="trp-info">
                            <a class="trp-title" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            <span class="trp-date"><?php echo esc_html( get_the_date() ); ?></span>
                        </div>
                    </li>
                <?php endwhile; ?>
            </ul>
            <?php
            wp_reset_postdata();
        else :
            echo '<p>' . esc_html__( 'No related posts found.', 'themisdb' ) . '</p>';
        endif;

        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title      = ! empty( $instance['title'] )      ? $instance['title']            : esc_html__( 'Related Posts', 'themisdb' );
        $count      = ! empty( $instance['count'] )      ? absint( $instance['count'] )  : 4;
        $match_by   = ! empty( $instance['match_by'] )   ? $instance['match_by']          : 'tag';
        $show_thumb = isset( $instance['show_thumb'] )   ? (bool) $instance['show_thumb'] : true;
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'themisdb' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text"
                   value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"><?php esc_html_e( 'Number of posts:', 'themisdb' ); ?></label>
            <input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>" type="number"
                   step="1" min="1" value="<?php echo esc_attr( $count ); ?>" size="3">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'match_by' ) ); ?>"><?php esc_html_e( 'Match by:', 'themisdb' ); ?></label>
            <select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'match_by' ) ); ?>"
                    name="<?php echo esc_attr( $this->get_field_name( 'match_by' ) ); ?>">
                <option value="tag"      <?php selected( $match_by, 'tag' ); ?>><?php esc_html_e( 'Tags (falls back to category)', 'themisdb' ); ?></option>
                <option value="category" <?php selected( $match_by, 'category' ); ?>><?php esc_html_e( 'Category', 'themisdb' ); ?></option>
            </select>
        </p>
        <p>
            <input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_thumb' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'show_thumb' ) ); ?>" value="1"
                   <?php checked( $show_thumb ); ?>>
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_thumb' ) ); ?>"><?php esc_html_e( 'Show thumbnail', 'themisdb' ); ?></label>
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance               = array();
        $instance['title']      = ( ! empty( $new_instance['title'] ) )    ? sanitize_text_field( $new_instance['title'] )       : '';
        $instance['count']      = ( ! empty( $new_instance['count'] ) )    ? absint( $new_instance['count'] )                     : 4;
        $instance['match_by']   = ( ! empty( $new_instance['match_by'] ) && in_array( $new_instance['match_by'], array( 'tag', 'category' ), true ) )
                                  ? $new_instance['match_by'] : 'tag';
        $instance['show_thumb'] = ! empty( $new_instance['show_thumb'] ) ? 1 : 0;
        return $instance;
    }
}

/**
 * Reading Progress Bar Widget
 * Renders a thin fixed bar at the top of the viewport that fills as the user
 * scrolls through a single post. On other page types the bar is hidden.
 * Uses minimal inline JS (no external file needed) and CSS custom properties.
 * Only one instance needs to be active; multiple instances are harmless.
 */
class ThemisDB_Reading_Progress_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'themisdb_reading_progress',
            esc_html__( 'ThemisDB: Reading Progress Bar', 'themisdb' ),
            array(
                'description' => esc_html__( 'Thin fixed progress bar at the top of the page that fills as you scroll through a post', 'themisdb' ),
                'classname'   => 'themisdb-reading-progress-widget',
            )
        );
    }

    public function widget( $args, $instance ) {
        // The actual bar lives outside the sidebar (fixed positioning) so we
        // output the bar element into <body> via wp_footer, and the widget
        // area just emits nothing visible (height: 0).
        $color     = ! empty( $instance['color'] )     ? $instance['color']     : '#1e6fba';
        $height    = ! empty( $instance['height'] )    ? absint( $instance['height'] ) : 3;
        $only_single = ! isset( $instance['only_single'] ) || ! empty( $instance['only_single'] );

        // Validate colour is a hex value
        if ( ! preg_match( '/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/', $color ) ) {
            $color = '#1e6fba';
        }
        $height = max( 2, min( 8, $height ) );

        // (No element ID needed; bar is identified by its unique class)
        $color_safe   = esc_attr( $color );
        $height_safe  = absint( $height );
        $only_s_int   = $only_single ? 1 : 0;

        // Enqueue once – use a flag stored in a static variable
        static $progress_enqueued = false;
        if ( ! $progress_enqueued ) {
            $progress_enqueued = true;
            add_action( 'wp_footer', static function() use ( $color_safe, $height_safe, $only_s_int ) {
                ?>
                <div id="themisdb-reading-progress" class="themisdb-reading-progress-bar"
                     style="--rpb-color:<?php echo $color_safe; ?>;--rpb-height:<?php echo $height_safe; ?>px"
                     aria-hidden="true" role="presentation"></div>
                <script>
                (function(){
                    var bar = document.getElementById('themisdb-reading-progress');
                    if(!bar) return;
                    var onlySingle = <?php echo $only_s_int; ?>;
                    var isSingle = document.body.classList.contains('single-post') ||
                                   document.body.classList.contains('single');
                    if(onlySingle && !isSingle){ bar.style.display='none'; return; }
                    function update(){
                        var el = document.documentElement;
                        var scrolled = el.scrollTop || document.body.scrollTop;
                        var total    = el.scrollHeight - el.clientHeight;
                        var pct      = total > 0 ? Math.min(100, (scrolled / total) * 100) : 0;
                        bar.style.setProperty('--rpb-progress', pct + '%');
                    }
                    window.addEventListener('scroll', update, {passive:true});
                    update();
                })();
                </script>
                <?php
            }, 20 );
        }

        // Widget area output is intentionally empty – the bar is fixed-position
        echo $args['before_widget'];
        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $color       = ! empty( $instance['color'] )       ? $instance['color']            : '#1e6fba';
        $height      = ! empty( $instance['height'] )      ? absint( $instance['height'] ) : 3;
        $only_single = ! isset( $instance['only_single'] ) || ! empty( $instance['only_single'] );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'color' ) ); ?>"><?php esc_html_e( 'Bar colour:', 'themisdb' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'color' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'color' ) ); ?>" type="color"
                   value="<?php echo esc_attr( $color ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'height' ) ); ?>"><?php esc_html_e( 'Bar height (px, 2–8):', 'themisdb' ); ?></label>
            <input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'height' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'height' ) ); ?>" type="number"
                   step="1" min="2" max="8" value="<?php echo esc_attr( $height ); ?>" size="3">
        </p>
        <p>
            <input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'only_single' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'only_single' ) ); ?>" value="1"
                   <?php checked( $only_single ); ?>>
            <label for="<?php echo esc_attr( $this->get_field_id( 'only_single' ) ); ?>"><?php esc_html_e( 'Show only on single posts', 'themisdb' ); ?></label>
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance                = array();
        $raw_color               = isset( $new_instance['color'] ) ? trim( $new_instance['color'] ) : '#1e6fba';
        $instance['color']       = preg_match( '/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/', $raw_color ) ? $raw_color : '#1e6fba';
        $raw_height              = isset( $new_instance['height'] ) ? absint( $new_instance['height'] ) : 3;
        $instance['height']      = max( 2, min( 8, $raw_height ) );
        $instance['only_single'] = ! empty( $new_instance['only_single'] ) ? 1 : 0;
        return $instance;
    }
}

/**
 * Register all custom widgets
 */
function themisdb_register_widgets() {
    register_widget( 'ThemisDB_Featured_Slider_Widget' );
    register_widget( 'ThemisDB_Recent_Posts_Widget' );
    register_widget( 'ThemisDB_Category_Highlights_Widget' );
    register_widget( 'ThemisDB_CTA_Widget' );
    register_widget( 'ThemisDB_About_Widget' );
    register_widget( 'ThemisDB_Social_Widget' );
    register_widget( 'ThemisDB_Tag_Cloud_Widget' );
    register_widget( 'ThemisDB_Quick_Links_Widget' );
    register_widget( 'ThemisDB_Testimonials_Carousel_Widget' );
    register_widget( 'ThemisDB_Image_Carousel_Widget' );
    register_widget( 'ThemisDB_Timeline_Carousel_Widget' );
    register_widget( 'ThemisDB_Posts_Grid_Widget' );
    register_widget( 'ThemisDB_Author_Card_Widget' );
    register_widget( 'ThemisDB_Stats_Widget' );
    register_widget( 'ThemisDB_Popular_Posts_Widget' );
    register_widget( 'ThemisDB_Newsletter_Widget' );
    register_widget( 'ThemisDB_Video_Widget' );
    register_widget( 'ThemisDB_Tabbed_Posts_Widget' );
    register_widget( 'ThemisDB_Recent_Comments_Widget' );
    register_widget( 'ThemisDB_Category_Grid_Widget' );
    register_widget( 'ThemisDB_Post_Ticker_Widget' );
    register_widget( 'ThemisDB_Related_Posts_Widget' );
    register_widget( 'ThemisDB_Reading_Progress_Widget' );
}
add_action( 'widgets_init', 'themisdb_register_widgets' );
