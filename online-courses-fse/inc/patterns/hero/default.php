<?php
/**
 * Pattern
 *
 * @author Themegrill
 * @package online-courses-fse
 * @since 1.0.2
 *
 * slug: hero
 * title: Hero
 * categories: OnlineCourseFSE
 * keywords: hero, teachers, team
 */

use OnlineCoursesFSE\Assets_Manager;

$online_course_fse_hero_star = Assets_Manager::get_image_url( 'hero-star.png' );
$online_course_fse_hero      = Assets_Manager::get_image_url( 'hero.png' );

$explore_courses_text = esc_html__( 'Explore Courses', 'online-courses-fse' );

return array(
	'title'      => __( 'Hero', 'online-courses-fse' ),
	'categories' => array( 'online-courses-fse-hero' ),
	'keywords'   => array( 'hero', 'teachers', 'team' ),
	'content'    => '

<!-- wp:group {"metadata":{"name":"Hero"},"align":"full","style":{"spacing":{"padding":{"top":"120px","bottom":"120px"}}},"backgroundColor":"background-4","layout":{"type":"constrained","contentSize":"1320px"}} -->
<div class="wp-block-group alignfull has-background-4-background-color has-background" style="padding-top:120px;padding-bottom:120px"><!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"60px","left":"0px"}}}} -->
<div class="wp-block-columns"><!-- wp:column {"verticalAlignment":"center","width":"50%","style":{"spacing":{"blockGap":"40px"}}} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:50%"><!-- wp:group {"style":{"spacing":{"blockGap":"12px"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group"><!-- wp:group {"style":{"border":{"radius":"50px"},"spacing":{"padding":{"top":"8px","bottom":"8px","left":"24px","right":"24px"}}},"backgroundColor":"background-1","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-background-1-background-color has-background" style="border-radius:50px;padding-top:8px;padding-right:24px;padding-bottom:8px;padding-left:24px"><!-- wp:group {"style":{"spacing":{"blockGap":"8px"}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"center"}} -->
<div class="wp-block-group"><!-- wp:heading {"textAlign":"left","level":4} -->
<h4 class="wp-block-heading has-text-align-left">4.8/5</h4>
<!-- /wp:heading -->

<!-- wp:image {"sizeSlug":"full","linkDestination":"none","style":{"spacing":{"margin":{"bottom":"6px"}}}} -->
<figure class="wp-block-image size-full" style="margin-bottom:6px"><img src="' . esc_url( $online_course_fse_hero_star ) . '" alt="" /></figure>
<!-- /wp:image --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"blockGap":"8px"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group"><!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Where Learning Meets Real World <mark style="background-color:rgba(0, 0, 0, 0);color:var(--wp--preset--color--primary)" class="has-inline-color">Growth</mark></h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Quality education designed to equip you with practical skills and industry-ready knowledge through our expertly crafted curriculum.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:buttons {"style":{"spacing":{"blockGap":{"top":"16px","left":"16px"}}}} -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"primary","style":{"elements":{"link":{"color":{"text":"var:preset|color|button-1"}}},"typography":{"fontSize":"17px","textTransform":"capitalize","fontStyle":"normal","fontWeight":"600"}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-primary-background-color has-background has-link-color has-custom-font-size wp-element-button" style="font-size:17px;font-style:normal;font-weight:600;text-transform:capitalize">Explore Courses</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"50%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:50%"><!-- wp:image {"id":952,"sizeSlug":"full","linkDestination":"none","align":"full"} -->
<figure class="wp-block-image alignfull size-full"><img src="' . esc_url( $online_course_fse_hero ) . '" alt="" class="wp-image-952"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
',
);
