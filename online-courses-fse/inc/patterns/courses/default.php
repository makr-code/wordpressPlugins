<?php
/**
 * Pattern
 *
 * @author Themegrill
 * @package online-courses-fse
 * @since 1.0.2
 *
 * slug: courses
 * title: Courses
 * categories: OnlineCourseFSE
 * keywords: courses, call to action, cover
 */

use OnlineCoursesFSE\Assets_Manager;

$online_course_fse_content_1 = Assets_Manager::get_image_url( 'content-1.jpg' );
$online_course_fse_content_2 = Assets_Manager::get_image_url( 'content-2.jpg' );
$online_course_fse_content_3 = Assets_Manager::get_image_url( 'content-3.jpg' );

$best_learning_text   = esc_html__( 'Best Learning', 'online-courses-fse' );
$popular_courses_text = esc_html__( 'Popular Courses', 'online-courses-fse' );
$all_courses_text     = esc_html__( 'View all Courses', 'online-courses-fse' );
$view_course_text     = esc_html__( 'View Course', 'online-courses-fse' );

return array(
	'title'      => __( 'Courses', 'online-courses-fse' ),
	'categories' => array( 'online-courses-fse-courses' ),
	'keywords'   => array( 'courses', 'call to action' ),
	'content'    => '

<!-- wp:group {"metadata":{"name":"Popular Courses"},"align":"full","style":{"spacing":{"padding":{"top":"120px","bottom":"120px","left":"20px","right":"20px"},"blockGap":"60px"}},"backgroundColor":"background-2","layout":{"type":"constrained","contentSize":"1320px"}} -->
<div class="wp-block-group alignfull has-background-2-background-color has-background" style="padding-top:120px;padding-right:20px;padding-bottom:120px;padding-left:20px"><!-- wp:columns {"style":{"spacing":{"padding":{"top":"0px","bottom":"0px","left":"0px","right":"0px"},"margin":{"top":"0px","bottom":"0px"},"blockGap":{"top":"0px","left":"0px"}}}} -->
<div class="wp-block-columns" style="margin-top:0px;margin-bottom:0px;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px"><!-- wp:column {"verticalAlignment":"center","width":"100%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:100%"></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:columns {"style":{"spacing":{"padding":{"top":"0px","bottom":"0px","left":"0px","right":"0px"},"margin":{"top":"0px","bottom":"0px"},"blockGap":{"top":"0px","left":"0px"}}}} -->
<div class="wp-block-columns" style="margin-top:0px;margin-bottom:0px;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px"><!-- wp:column {"verticalAlignment":"center","width":"100%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:100%"><!-- wp:group {"style":{"spacing":{"blockGap":"10px"},"layout":{"selfStretch":"fixed","flexSize":"45%"}},"layout":{"type":"flex","orientation":"horizontal","justifyContent":"space-between","flexWrap":"wrap"}} -->
<div class="wp-block-group"><!-- wp:group {"style":{"layout":{"selfStretch":"fit","flexSize":null},"spacing":{"blockGap":"16px","padding":{"bottom":"24px"}}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group" style="padding-bottom:24px"><!-- wp:group {"style":{"spacing":{"padding":{"top":"9px","bottom":"9px","left":"20px","right":"20px"}},"border":{"radius":"20px"}},"backgroundColor":"background-3","textColor":"accent-2","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-accent-2-color has-background-3-background-color has-text-color has-background" style="border-radius:20px;padding-top:9px;padding-right:20px;padding-bottom:9px;padding-left:20px"><!-- wp:heading {"textAlign":"center","level":6,"style":{"typography":{"textTransform":"uppercase","fontWeight":"700","letterSpacing":"1.4px","fontStyle":"normal"}},"textColor":"primary"} -->
<h6 class="wp-block-heading has-text-align-center has-primary-color has-text-color" style="font-style:normal;font-weight:700;letter-spacing:1.4px;text-transform:uppercase">' . $best_learning_text . '</h6>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:heading {"textAlign":"left","style":{"typography":{"textTransform":"capitalize"}},"textColor":"heading","fontSize":"gigantic","fontFamily":"manrope"} -->
<h2 class="wp-block-heading has-text-align-left has-heading-color has-text-color has-manrope-font-family has-gigantic-font-size" style="text-transform:capitalize">' . $popular_courses_text . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"left","textColor":"accent-3","fontSize":"regular"} -->
<p class="has-text-align-left has-accent-3-color has-text-color has-regular-font-size">Check out our most loved courses that help students succeed.&nbsp;</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"left"}} -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"primary","className":"has-custom-font-size has-button-font-size","fontSize":"button"} -->
<div class="wp-block-button has-custom-font-size has-button-font-size"><a class="wp-block-button__link has-primary-background-color has-background has-button-font-size has-custom-font-size wp-element-button" href="#">' . $all_courses_text . '</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"30px","left":"30px"},"margin":{"top":"60px"}}}} -->
<div class="wp-block-columns" style="margin-top:60px;"><!-- wp:column {"style":{"spacing":{"blockGap":"20px","padding":{"top":"24px","bottom":"24px","left":"24px","right":"24px"}},"border":{"radius":"16px","width":"1px"}},"backgroundColor":"background-1","borderColor":"background-3"} -->
<div class="wp-block-column has-border-color has-background-3-border-color has-background-1-background-color has-background" style="border-width:1px;border-radius:16px;padding-top:24px;padding-right:24px;padding-bottom:24px;padding-left:24px"><!-- wp:image {"id":884,"sizeSlug":"full","linkDestination":"none","align":"full","style":{"border":{"radius":"12px"}}} -->
<figure class="wp-block-image alignfull size-full has-custom-border"><img src="' . esc_url( $online_course_fse_content_1 ) . '" alt="" class="wp-image-884" style="border-radius:12px"/></figure>
<!-- /wp:image -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"0px"}},"border":{"top":{"style":"none","width":"0px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="border-top-style:none;border-top-width:0px;padding-top:0px"><!-- wp:heading {"level":4,"textColor":"heading","fontSize":"extra-large","fontFamily":"manrope"} -->
<h4 class="wp-block-heading has-heading-color has-text-color has-manrope-font-family has-extra-large-font-size">Graphic Design</h4>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"20px"}},"border":{"top":{"color":"#d3d3d3","width":"1px"}}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
<div class="wp-block-group" style="border-top-color:#d3d3d3;border-top-width:1px;padding-top:20px"><!-- wp:heading {"level":4,"style":{"typography":{"letterSpacing":"0.16px"}},"textColor":"heading","fontSize":"extra-large"} -->
<h4 class="wp-block-heading has-heading-color has-text-color has-extra-large-font-size" style="letter-spacing:0.16px">$95</h4>
<!-- /wp:heading -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"left"}} -->
<div class="wp-block-buttons"><!-- wp:button {"textColor":"primary","style":{"border":{"width":"2px","radius":"500px"},"spacing":{"padding":{"left":"22px","right":"22px","top":"12px","bottom":"12px"}},"color":{"background":"#ffffff00"}},"borderColor":"primary","fontSize":"small"} -->
<div class="wp-block-button has-custom-font-size has-small-font-size"><a class="wp-block-button__link has-primary-color has-text-color has-background has-border-color has-primary-border-color wp-element-button" href="#" style="border-width:2px;border-radius:500px;background-color:#ffffff00;padding-top:12px;padding-right:22px;padding-bottom:12px;padding-left:22px">' . $view_course_text . '</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"spacing":{"blockGap":"20px","padding":{"top":"24px","bottom":"24px","left":"24px","right":"24px"}},"border":{"radius":"16px","width":"1px"}},"backgroundColor":"background-1","borderColor":"background-3"} -->
<div class="wp-block-column has-border-color has-background-3-border-color has-background-1-background-color has-background" style="border-width:1px;border-radius:16px;padding-top:24px;padding-right:24px;padding-bottom:24px;padding-left:24px"><!-- wp:image {"id":885,"sizeSlug":"full","linkDestination":"none","align":"full","style":{"border":{"radius":"12px"}}} -->
<figure class="wp-block-image alignfull size-full has-custom-border"><img src="' . esc_url( $online_course_fse_content_2 ) . '" alt="" class="wp-image-885" style="border-radius:12px"/></figure>
<!-- /wp:image -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"0px"}},"border":{"top":{"style":"none","width":"0px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="border-top-style:none;border-top-width:0px;padding-top:0px"><!-- wp:heading {"level":4,"textColor":"heading","fontSize":"extra-large","fontFamily":"manrope"} -->
<h4 class="wp-block-heading has-heading-color has-text-color has-manrope-font-family has-extra-large-font-size">Website Development</h4>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"20px"}},"border":{"top":{"color":"#d3d3d3","width":"1px"}}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
<div class="wp-block-group" style="border-top-color:#d3d3d3;border-top-width:1px;padding-top:20px"><!-- wp:heading {"level":4,"style":{"typography":{"letterSpacing":"0.16px"}},"textColor":"heading","fontSize":"extra-large"} -->
<h4 class="wp-block-heading has-heading-color has-text-color has-extra-large-font-size" style="letter-spacing:0.16px">$120</h4>
<!-- /wp:heading -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"left"}} -->
<div class="wp-block-buttons"><!-- wp:button {"textColor":"primary","style":{"border":{"width":"2px","radius":"500px"},"spacing":{"padding":{"left":"22px","right":"22px","top":"12px","bottom":"12px"}},"color":{"background":"#ffffff00"}},"borderColor":"primary","fontSize":"small"} -->
<div class="wp-block-button has-custom-font-size has-small-font-size"><a class="wp-block-button__link has-primary-color has-text-color has-background has-border-color has-primary-border-color wp-element-button" href="#" style="border-width:2px;border-radius:500px;background-color:#ffffff00;padding-top:12px;padding-right:22px;padding-bottom:12px;padding-left:22px">' . $view_course_text . '</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"spacing":{"blockGap":"20px","padding":{"top":"24px","bottom":"24px","left":"24px","right":"24px"}},"border":{"radius":"16px","width":"1px"}},"backgroundColor":"background-1","borderColor":"background-3"} -->
<div class="wp-block-column has-border-color has-background-3-border-color has-background-1-background-color has-background" style="border-width:1px;border-radius:16px;padding-top:24px;padding-right:24px;padding-bottom:24px;padding-left:24px"><!-- wp:image {"id":886,"sizeSlug":"full","linkDestination":"none","align":"full","style":{"border":{"radius":"12px"}}} -->
<figure class="wp-block-image alignfull size-full has-custom-border"><img src="' . esc_url( $online_course_fse_content_3 ) . '" alt="" class="wp-image-886" style="border-radius:12px"/></figure>
<!-- /wp:image -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"0px"}},"border":{"top":{"style":"none","width":"0px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="border-top-style:none;border-top-width:0px;padding-top:0px"><!-- wp:heading {"level":4,"textColor":"heading","fontSize":"extra-large","fontFamily":"manrope"} -->
<h4 class="wp-block-heading has-heading-color has-text-color has-manrope-font-family has-extra-large-font-size">Digital Marketing Basics</h4>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"20px"}},"border":{"top":{"color":"#d3d3d3","width":"1px"}}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
<div class="wp-block-group" style="border-top-color:#d3d3d3;border-top-width:1px;padding-top:20px"><!-- wp:heading {"level":4,"style":{"typography":{"letterSpacing":"0.16px"}},"textColor":"heading","fontSize":"extra-large"} -->
<h4 class="wp-block-heading has-heading-color has-text-color has-extra-large-font-size" style="letter-spacing:0.16px">$89</h4>
<!-- /wp:heading -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"left"}} -->
<div class="wp-block-buttons"><!-- wp:button {"textColor":"primary","style":{"border":{"width":"2px","radius":"500px"},"spacing":{"padding":{"left":"22px","right":"22px","top":"12px","bottom":"12px"}},"color":{"background":"#ffffff00"}},"borderColor":"primary","fontSize":"small"} -->
<div class="wp-block-button has-custom-font-size has-small-font-size"><a class="wp-block-button__link has-primary-color has-text-color has-background has-border-color has-primary-border-color wp-element-button" href="#" style="border-width:2px;border-radius:500px;background-color:#ffffff00;padding-top:12px;padding-right:22px;padding-bottom:12px;padding-left:22px">' . $view_course_text . '</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
',
);
