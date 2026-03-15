<?php
/**
 * Pattern
 *
 * @author Themegrill
 * @package online-courses-fse
 * @since 1.0.2
 *
 * slug: content-1
 * title: Content 1
 * categories: OnlineCourseFSE
 * keywords: content-1, call to action, cover
 */

use OnlineCoursesFSE\Assets_Manager;

$online_course_fse_content_1 = Assets_Manager::get_image_url( 'content-1.jpg' );
$online_course_fse_content_2 = Assets_Manager::get_image_url( 'content-2.jpg' );
$online_course_fse_content_3 = Assets_Manager::get_image_url( 'content-3.jpg' );

$best_learning_text   = esc_html__( 'Best Learning', 'online-courses-fse' );
$popular_courses_text = esc_html__( 'Popular Courses', 'online-courses-fse' );
$all_courses_text     = esc_html__( 'All Courses', 'online-courses-fse' );
$view_courses_text    = esc_html__( 'View Courses', 'online-courses-fse' );

return array(
	'title'      => __( 'Content 1', 'online-courses-fse' ),
	'categories' => array( 'online-courses-fse-content' ),
	'keywords'   => array( 'content-1', 'call to action' ),
	'content'    => '

<!-- wp:group {"metadata":{"name":"Content"},"align":"full","style":{"spacing":{"padding":{"top":"120px","bottom":"120px"},"blockGap":"60px"}},"backgroundColor":"elearning-color-4","layout":{"type":"constrained","contentSize":"1320px"}} -->
<div class="wp-block-group alignfull has-elearning-color-4-background-color has-background" style="padding-top:120px;padding-bottom:120px"><!-- wp:columns {"style":{"spacing":{"padding":{"top":"0px","bottom":"0px","left":"0px","right":"0px"},"margin":{"top":"0px","bottom":"0px"},"blockGap":{"top":"0px","left":"0px"}}}} -->
<div class="wp-block-columns" style="margin-top:0px;margin-bottom:0px;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px"><!-- wp:column {"verticalAlignment":"center","width":"100%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:100%"><!-- wp:group {"style":{"spacing":{"blockGap":"10px"},"layout":{"selfStretch":"fixed","flexSize":"45%"}},"layout":{"type":"flex","orientation":"horizontal","justifyContent":"space-between","flexWrap":"wrap"}} -->
<div class="wp-block-group"><!-- wp:group {"style":{"layout":{"selfStretch":"fit","flexSize":null},"spacing":{"blockGap":"16px","padding":{"bottom":"24px"}}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group" style="padding-bottom:24px"><!-- wp:group {"style":{"spacing":{"padding":{"top":"9px","bottom":"9px","left":"20px","right":"20px"}},"border":{"radius":"20px"},"elements":{"link":{"color":{"text":"var:preset|color|elearning-color-1"}}}},"backgroundColor":"elearning-color-3","textColor":"elearning-color-1","layout":{"type":"constrained","justifyContent":"center"}} -->
<div class="wp-block-group has-elearning-color-1-color has-elearning-color-3-background-color has-text-color has-background has-link-color" style="border-radius:20px;padding-top:9px;padding-right:20px;padding-bottom:9px;padding-left:20px"><!-- wp:heading {"textAlign":"center","level":6,"style":{"elements":{"link":{"color":{"text":"var:preset|color|elearning-color-1"}}}},"textColor":"elearning-color-1"} -->
<h6 class="wp-block-heading has-text-align-center has-elearning-color-1-color has-text-color has-link-color">' . $best_learning_text . '</h6>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:heading {"style":{"elements":{"link":{"color":{"text":"var:preset|color|elearning-color-6"}}}},"textColor":"elearning-color-6"} -->
<h2 class="wp-block-heading has-elearning-color-6-color has-text-color has-link-color">' . $popular_courses_text . ' <mark style="background-color:rgba(0, 0, 0, 0)" class="has-inline-color has-elearning-color-1-color">Courses</mark></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"elements":{"link":{"color":{"text":"var:preset|color|elearning-color-7"}}},"typography":{"fontSize":"19px"}},"textColor":"elearning-color-7"} -->
<p class="has-elearning-color-7-color has-text-color has-link-color" style="font-size:19px">Check out our most loved courses that help students succeed.&nbsp;</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:buttons {"style":{"spacing":{"blockGap":{"top":"16px","left":"16px"}}},"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"textColor":"background-1","backgroundColor":"primary","style":{"elements":{"link":{"color":{"text":"var:preset|color|background-1"}}},"typography":{"fontSize":"17px","textTransform":"capitalize","fontStyle":"normal","fontWeight":"600"}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-background-1-color has-primary-background-color has-text-color has-background has-link-color has-custom-font-size wp-element-button" style="font-size:17px;font-style:normal;font-weight:600;text-transform:capitalize">' . $all_courses_text . '</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"30px","left":"30px"}}}} -->
<div class="wp-block-columns"><!-- wp:column {"style":{"spacing":{"blockGap":"20px","padding":{"top":"24px","bottom":"24px","left":"24px","right":"24px"}},"border":{"radius":"16px","width":"1px","color":"#E6E6E6"}},"backgroundColor":"white"} -->
<div class="wp-block-column has-border-color has-white-background-color has-background" style="border-color:#E6E6E6;border-width:1px;border-radius:16px;padding-top:24px;padding-right:24px;padding-bottom:24px;padding-left:24px"><!-- wp:image {"id":884,"sizeSlug":"full","linkDestination":"none","align":"full","style":{"border":{"radius":"12px"}}} -->
<figure class="wp-block-image alignfull size-full has-custom-border"><img src="' . esc_url( $online_course_fse_content_1 ) . '" alt="" class="wp-image-884" style="border-radius:12px"/></figure>
<!-- /wp:image -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"0px"}},"border":{"top":{"style":"none","width":"0px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="border-top-style:none;border-top-width:0px;padding-top:0px"><!-- wp:heading {"level":4,"style":{"elements":{"link":{"color":{"text":"var:preset|color|elearning-color-6"}}}},"textColor":"elearning-color-6"} -->
<h4 class="wp-block-heading has-elearning-color-6-color has-text-color has-link-color">Graphic Design</h4>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"20px"}},"border":{"top":{"color":"var:preset|color|elearning-color-9","width":"1px"},"right":[],"bottom":[],"left":[]}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
<div class="wp-block-group" style="border-top-color:var(--wp--preset--color--elearning-color-9);border-top-width:1px;padding-top:20px"><!-- wp:heading {"level":4,"style":{"elements":{"link":{"color":{"text":"var:preset|color|elearning-color-6"}}}},"textColor":"elearning-color-6"} -->
<h4 class="wp-block-heading has-elearning-color-6-color has-text-color has-link-color">$95</h4>
<!-- /wp:heading -->

<!-- wp:buttons {"style":{"spacing":{"blockGap":{"top":"16px","left":"16px"}}},"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"textColor":"background-1","backgroundColor":"primary","style":{"elements":{"link":{"color":{"text":"var:preset|color|background-1"}}},"typography":{"fontSize":"17px","textTransform":"capitalize","fontStyle":"normal","fontWeight":"600"}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-background-1-color has-primary-background-color has-text-color has-background has-link-color has-custom-font-size wp-element-button" style="font-size:17px;font-style:normal;font-weight:600;text-transform:capitalize">' . $view_courses_text . '</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"spacing":{"blockGap":"20px","padding":{"top":"24px","bottom":"24px","left":"24px","right":"24px"}},"border":{"radius":"16px","width":"1px","color":"#E6E6E6"}},"backgroundColor":"white"} -->
<div class="wp-block-column has-border-color has-white-background-color has-background" style="border-color:#E6E6E6;border-width:1px;border-radius:16px;padding-top:24px;padding-right:24px;padding-bottom:24px;padding-left:24px"><!-- wp:image {"id":885,"sizeSlug":"full","linkDestination":"none","align":"full","style":{"border":{"radius":"12px"}}} -->
<figure class="wp-block-image alignfull size-full has-custom-border"><img src="' . esc_url( $online_course_fse_content_2 ) . '" alt="" class="wp-image-885" style="border-radius:12px"/></figure>
<!-- /wp:image -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"0px"}},"border":{"top":{"style":"none","width":"0px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="border-top-style:none;border-top-width:0px;padding-top:0px"><!-- wp:heading {"level":4,"style":{"elements":{"link":{"color":{"text":"var:preset|color|elearning-color-6"}}}},"textColor":"elearning-color-6"} -->
<h4 class="wp-block-heading has-elearning-color-6-color has-text-color has-link-color">Website Development</h4>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"20px"}},"border":{"top":{"color":"var:preset|color|elearning-color-9","width":"1px"},"right":[],"bottom":[],"left":[]}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
<div class="wp-block-group" style="border-top-color:var(--wp--preset--color--elearning-color-9);border-top-width:1px;padding-top:20px"><!-- wp:heading {"level":4,"style":{"elements":{"link":{"color":{"text":"var:preset|color|elearning-color-6"}}}},"textColor":"elearning-color-6"} -->
<h4 class="wp-block-heading has-elearning-color-6-color has-text-color has-link-color">$120</h4>
<!-- /wp:heading -->

<!-- wp:buttons {"style":{"spacing":{"blockGap":{"top":"16px","left":"16px"}}},"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"textColor":"background-1","backgroundColor":"primary","style":{"elements":{"link":{"color":{"text":"var:preset|color|background-1"}}},"typography":{"fontSize":"17px","textTransform":"capitalize","fontStyle":"normal","fontWeight":"600"}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-background-1-color has-primary-background-color has-text-color has-background has-link-color has-custom-font-size wp-element-button" style="font-size:17px;font-style:normal;font-weight:600;text-transform:capitalize">' . $view_courses_text . '</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"spacing":{"blockGap":"20px","padding":{"top":"24px","bottom":"24px","left":"24px","right":"24px"}},"border":{"radius":"16px","width":"1px","color":"#E6E6E6"}},"backgroundColor":"white"} -->
<div class="wp-block-column has-border-color has-white-background-color has-background" style="border-color:#E6E6E6;border-width:1px;border-radius:16px;padding-top:24px;padding-right:24px;padding-bottom:24px;padding-left:24px"><!-- wp:image {"id":886,"sizeSlug":"full","linkDestination":"none","align":"full","style":{"border":{"radius":"12px"}}} -->
<figure class="wp-block-image alignfull size-full has-custom-border"><img src="' . esc_url( $online_course_fse_content_3 ) . '" alt="" class="wp-image-886" style="border-radius:12px"/></figure>
<!-- /wp:image -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"0px"}},"border":{"top":{"style":"none","width":"0px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="border-top-style:none;border-top-width:0px;padding-top:0px"><!-- wp:heading {"level":4,"style":{"elements":{"link":{"color":{"text":"var:preset|color|elearning-color-6"}}}},"textColor":"elearning-color-6"} -->
<h4 class="wp-block-heading has-elearning-color-6-color has-text-color has-link-color">Digital Marketing Basics</h4>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"20px"}},"border":{"top":{"color":"var:preset|color|elearning-color-9","width":"1px"},"right":[],"bottom":[],"left":[]}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
<div class="wp-block-group" style="border-top-color:var(--wp--preset--color--elearning-color-9);border-top-width:1px;padding-top:20px"><!-- wp:heading {"level":4,"style":{"elements":{"link":{"color":{"text":"var:preset|color|elearning-color-6"}}}},"textColor":"elearning-color-6"} -->
<h4 class="wp-block-heading has-elearning-color-6-color has-text-color has-link-color">$89</h4>
<!-- /wp:heading -->

<!-- wp:buttons {"style":{"spacing":{"blockGap":{"top":"16px","left":"16px"}}},"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"textColor":"background-1","backgroundColor":"primary","style":{"elements":{"link":{"color":{"text":"var:preset|color|background-1"}}},"typography":{"fontSize":"17px","textTransform":"capitalize","fontStyle":"normal","fontWeight":"600"}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-background-1-color has-primary-background-color has-text-color has-background has-link-color has-custom-font-size wp-element-button" style="font-size:17px;font-style:normal;font-weight:600;text-transform:capitalize">' . $view_courses_text . '</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
',
);
