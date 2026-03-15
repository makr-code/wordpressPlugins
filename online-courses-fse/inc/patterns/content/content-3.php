<?php
/**
 * Pattern
 *
 * @author Themegrill
 * @package online-courses-fse
 * @since 1.0.2
 *
 * slug: content-3
 * title: Content 3
 * categories: OnlineCourseFSE
 * keywords: content-3, call to action, cover
 */

use OnlineCoursesFSE\Assets_Manager;

$online_course_fse_airbnb    = Assets_Manager::get_image_url( 'airbnb.png' );
$online_course_fse_celanese  = Assets_Manager::get_image_url( 'celanese.png' );
$online_course_fse_google    = Assets_Manager::get_image_url( 'google.png' );
$online_course_fse_microsoft = Assets_Manager::get_image_url( 'microsoft.png' );
$online_course_fse_meta      = Assets_Manager::get_image_url( 'meta.png' );
$online_course_fse_elastic   = Assets_Manager::get_image_url( 'elastic.png' );

$our_partners_text = esc_html__( 'OUR PARTNERS', 'online-courses-fse' );

return array(
	'title'      => __( 'Content 3', 'online-courses-fse' ),
	'categories' => array( 'online-courses-fse-content' ),
	'keywords'   => array( 'content-3', 'call to action' ),
	'content'    => '

<!-- wp:group {"metadata":{"name":"Content"},"align":"full","style":{"spacing":{"padding":{"top":"120px","bottom":"120px"}}},"backgroundColor":"elearning-color-4","layout":{"type":"constrained","contentSize":"1320px"}} -->
<div class="wp-block-group alignfull has-elearning-color-4-background-color has-background" style="padding-top:120px;padding-bottom:120px"><!-- wp:columns {"verticalAlignment":"center","style":{"spacing":{"blockGap":{"top":"60px","left":"20%"}}}} -->
<div class="wp-block-columns are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","width":"30%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:30%"><!-- wp:group {"style":{"layout":{"selfStretch":"fit","flexSize":null},"spacing":{"blockGap":"8px","padding":{"bottom":"24px"}}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group" style="padding-bottom:24px"><!-- wp:group {"style":{"spacing":{"padding":{"top":"9px","bottom":"9px","left":"20px","right":"20px"}},"border":{"radius":"20px"},"elements":{"link":{"color":{"text":"var:preset|color|elearning-color-1"}}}},"backgroundColor":"elearning-color-3","textColor":"elearning-color-1","layout":{"type":"constrained","justifyContent":"center"}} -->
<div class="wp-block-group has-elearning-color-1-color has-elearning-color-3-background-color has-text-color has-background has-link-color" style="border-radius:20px;padding-top:9px;padding-right:20px;padding-bottom:9px;padding-left:20px"><!-- wp:heading {"textAlign":"center","level":6,"style":{"elements":{"link":{"color":{"text":"var:preset|color|elearning-color-1"}}}},"textColor":"elearning-color-1"} -->
<h6 class="wp-block-heading has-text-align-center has-elearning-color-1-color has-text-color has-link-color">' . $our_partners_text . '</h6>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:heading {"style":{"elements":{"link":{"color":{"text":"var:preset|color|elearning-color-6"}}}},"textColor":"elearning-color-6"} -->
<h2 class="wp-block-heading has-elearning-color-6-color has-text-color has-link-color">Trusted By Leading Companies</h2>
<!-- /wp:heading --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"50%","style":{"spacing":{"blockGap":"24px"}}} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:50%"><!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"24px"}}}} -->
<div class="wp-block-columns"><!-- wp:column {"verticalAlignment":"center","width":"33.33%","style":{"spacing":{"padding":{"top":"20px","bottom":"20px","left":"29px","right":"29px"}},"border":{"radius":"10px"}},"backgroundColor":"elearning-color-3"} -->
<div class="wp-block-column is-vertically-aligned-center has-elearning-color-3-background-color has-background" style="border-radius:10px;padding-top:20px;padding-right:29px;padding-bottom:20px;padding-left:29px;flex-basis:33.33%"><!-- wp:image {"id":900,"sizeSlug":"full","linkDestination":"none","align":"center"} -->
<figure class="wp-block-image aligncenter size-full"><img src="' . esc_url( $online_course_fse_celanese ) . '" alt="" class="wp-image-900"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"33.33%","style":{"spacing":{"padding":{"top":"20px","bottom":"20px","left":"29px","right":"29px"}},"border":{"radius":"10px"}},"backgroundColor":"elearning-color-3"} -->
<div class="wp-block-column is-vertically-aligned-center has-elearning-color-3-background-color has-background" style="border-radius:10px;padding-top:20px;padding-right:29px;padding-bottom:20px;padding-left:29px;flex-basis:33.33%"><!-- wp:image {"id":901,"sizeSlug":"full","linkDestination":"none","align":"center"} -->
<figure class="wp-block-image aligncenter size-full"><img src="' . esc_url( $online_course_fse_airbnb ) . '" alt="" class="wp-image-901"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"33.33%","style":{"spacing":{"padding":{"top":"20px","bottom":"20px","left":"29px","right":"29px"}},"border":{"radius":"10px"}},"backgroundColor":"elearning-color-3"} -->
<div class="wp-block-column is-vertically-aligned-center has-elearning-color-3-background-color has-background" style="border-radius:10px;padding-top:20px;padding-right:29px;padding-bottom:20px;padding-left:29px;flex-basis:33.33%"><!-- wp:image {"id":902,"sizeSlug":"full","linkDestination":"none","align":"center"} -->
<figure class="wp-block-image aligncenter size-full"><img src="' . esc_url( $online_course_fse_meta ) . '" alt="" class="wp-image-902"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"24px"}}}} -->
<div class="wp-block-columns"><!-- wp:column {"verticalAlignment":"center","width":"33.33%","style":{"spacing":{"padding":{"top":"20px","bottom":"20px","left":"29px","right":"29px"}},"border":{"radius":"10px"}},"backgroundColor":"elearning-color-3"} -->
<div class="wp-block-column is-vertically-aligned-center has-elearning-color-3-background-color has-background" style="border-radius:10px;padding-top:20px;padding-right:29px;padding-bottom:20px;padding-left:29px;flex-basis:33.33%"><!-- wp:image {"id":903,"sizeSlug":"full","linkDestination":"none","align":"center"} -->
<figure class="wp-block-image aligncenter size-full"><img src="' . esc_url( $online_course_fse_elastic ) . '" alt="" class="wp-image-903"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"33.33%","style":{"spacing":{"padding":{"top":"20px","bottom":"20px","left":"29px","right":"29px"}},"border":{"radius":"10px"}},"backgroundColor":"elearning-color-3"} -->
<div class="wp-block-column is-vertically-aligned-center has-elearning-color-3-background-color has-background" style="border-radius:10px;padding-top:20px;padding-right:29px;padding-bottom:20px;padding-left:29px;flex-basis:33.33%"><!-- wp:image {"id":904,"sizeSlug":"full","linkDestination":"none","align":"center"} -->
<figure class="wp-block-image aligncenter size-full"><img src="' . esc_url( $online_course_fse_google ) . '" alt="" class="wp-image-904"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"33.33%","style":{"spacing":{"padding":{"top":"20px","bottom":"20px","left":"29px","right":"29px"}},"border":{"radius":"10px"}},"backgroundColor":"elearning-color-3"} -->
<div class="wp-block-column is-vertically-aligned-center has-elearning-color-3-background-color has-background" style="border-radius:10px;padding-top:20px;padding-right:29px;padding-bottom:20px;padding-left:29px;flex-basis:33.33%"><!-- wp:image {"id":905,"sizeSlug":"full","linkDestination":"none","align":"center"} -->
<figure class="wp-block-image aligncenter size-full"><img src="' . esc_url( $online_course_fse_airbnb ) . '" alt="" class="wp-image-905"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
',
);