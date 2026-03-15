<?php
/**
 * Pattern
 *
 * @author Themegrill
 * @package online-courses-fse
 * @since 1.0.2
 *
 * slug: cta
 * title: CTA
 * categories: OnlineCourseFSE
 * keywords: cta, call to action, cover
 */

use OnlineCoursesFSE\Assets_Manager;

$online_course_fse_cta_scaled = Assets_Manager::get_image_url( 'cta.jpg' );

$start_today_text = esc_html__( 'Start Today!', 'online-courses-fse' );
$get_started_text = esc_html__( 'Get started', 'online-courses-fse' );

return array(
	'title'      => __( 'CTA', 'online-courses-fse' ),
	'categories' => array( 'online-courses-fse-cta' ),
	'keywords'   => array( 'CTA', 'call to action' ),
	'content'    => '

<!-- wp:group {"metadata":{"name":"CTA"},"align":"full","style":{"spacing":{"padding":{"bottom":"120px","right":"var:preset|spacing|30","left":"20px"}}},"backgroundColor":"elearning-color-3","layout":{"type":"constrained","contentSize":"1320px"}} -->
<div class="wp-block-group alignfull has-elearning-color-3-background-color has-background" style="padding-right:var(--wp--preset--spacing--30);padding-bottom:120px;padding-left:20px"><!-- wp:group {"style":{"spacing":{"padding":{"top":"80px","bottom":"80px","left":"50px","right":"50px"},"blockGap":"40px"},"border":{"radius":"16px"},"background":{"backgroundImage":{"url":"' . esc_url( $online_course_fse_cta_scaled ) . '","id":948,"source":"file","title":"cta"},"backgroundSize":"cover","backgroundPosition":"50% 50%"}},"layout":{"type":"constrained","contentSize":"100%","justifyContent":"left"}} -->
<div class="wp-block-group" style="border-radius:16px;padding-top:80px;padding-right:50px;padding-bottom:80px;padding-left:50px"><!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column {"width":"100%"} -->
<div class="wp-block-column" style="flex-basis:100%"><!-- wp:group {"layout":{"type":"constrained","contentSize":"100%","justifyContent":"left"}} -->
<div class="wp-block-group"><!-- wp:group {"style":{"layout":{"selfStretch":"fit","flexSize":null},"spacing":{"blockGap":"16px","padding":{"bottom":"24px"}}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group" style="padding-bottom:24px">

<!-- wp:heading {"style":{"typography":{"fontSize":"42px","fontStyle":"normal","fontWeight":"800","lineHeight":"1.24","textTransform":"capitalize"},"elements":{"link":{"color":{"text":"var:preset|color|button-1"}}}},"textColor":"button-1","fontFamily":"manrope"} -->
<h2 class="wp-block-heading has-button-1-color has-text-color has-link-color has-manrope-font-family" style="font-size:42px;font-style:normal;font-weight:800;line-height:1.24;text-transform:capitalize">' . $start_today_text . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"","style":{"elements":{"link":{"color":{"text":"var:preset|color|button-1"}}}},"textColor":"button-1"} -->
<p class="has-button-1-color has-text-color has-link-color">Join 750+ students who are learning new skills and building better careers.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"210px"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:210px"><!-- wp:group {"layout":{"type":"constrained","contentSize":"100%"}} -->
<div class="wp-block-group"><!-- wp:buttons {"style":{"spacing":{"blockGap":{"top":"16px","left":"16px"}}},"layout":{"type":"flex","verticalAlignment":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"primary","style":{"typography":{"fontSize":"17px","textTransform":"capitalize","fontStyle":"normal","fontWeight":"600"}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-primary-background-color has-background has-custom-font-size wp-element-button" style="font-size:17px;font-style:normal;font-weight:600;text-transform:capitalize">Get started</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
',
);
