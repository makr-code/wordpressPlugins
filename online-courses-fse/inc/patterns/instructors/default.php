<?php
/**
 * Pattern
 *
 * @author Themegrill
 * @package online-courses-fse
 * @since 1.0.2
 *
 * slug: instructors
 * title: Instructors
 * categories: OnlineCourseFSE
 * keywords: instructors, teachers, team
 */

use OnlineCoursesFSE\Assets_Manager;

$online_course_fse_ins1 = Assets_Manager::get_image_url( 'instructor-1.jpg' );
$online_course_fse_ins2 = Assets_Manager::get_image_url( 'instructor-2.jpg' );
$online_course_fse_ins3 = Assets_Manager::get_image_url( 'instructor-3.jpg' );
$online_course_fse_ins4 = Assets_Manager::get_image_url( 'instructor-4.jpg' );

$instructors_tag_text      = esc_html__( 'Instructors', 'online-courses-fse' );
$meet_our_teachers_text    = esc_html__( 'Meet Our Teachers', 'online-courses-fse' );
$view_all_instructors_text = esc_html__( 'View All Instructors', 'online-courses-fse' );

return array(
	'title'      => __( 'Instructors', 'online-courses-fse' ),
	'categories' => array( 'online-courses-fse-instructors' ),
	'keywords'   => array( 'instructors', 'teachers', 'team' ),
	'content'    => '

<!-- wp:group {"metadata":{"name":"Instructors"},"align":"full","style":{"spacing":{"padding":{"top":"120px","bottom":"120px"},"blockGap":"60px"}},"backgroundColor":"background-2","layout":{"type":"constrained","contentSize":"1320px"}} -->
<div class="wp-block-group alignfull has-background-2-background-color has-background" style="padding-top:120px;padding-bottom:120px"><!-- wp:group {"style":{"spacing":{"margin":{"bottom":"40px"}}},"layout":{"type":"constrained","contentSize":"686px"}} -->
<div class="wp-block-group" style="margin-bottom:40px"><!-- wp:group {"style":{"spacing":{"padding":{"bottom":"10px"},"blockGap":"8px"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
<div class="wp-block-group" style="padding-bottom:10px"><!-- wp:group {"style":{"spacing":{"padding":{"top":"9px","bottom":"9px","left":"20px","right":"20px"}},"border":{"radius":"20px"}},"backgroundColor":"background-3","textColor":"accent-2","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-accent-2-color has-background-3-background-color has-text-color has-background" style="border-radius:20px;padding-top:9px;padding-right:20px;padding-bottom:9px;padding-left:20px"><!-- wp:heading {"textAlign":"center","level":6,"style":{"typography":{"textTransform":"uppercase","fontWeight":"700","letterSpacing":"1.4px","fontStyle":"normal"}},"textColor":"primary"} -->
<h6 class="wp-block-heading has-text-align-center has-primary-color has-text-color" style="font-style:normal;font-weight:700;letter-spacing:1.4px;text-transform:uppercase">' . $instructors_tag_text . '</h6>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:heading {"textAlign":"center","textColor":"heading"} -->
<h2 class="wp-block-heading has-text-align-center has-heading-color has-text-color">' . $meet_our_teachers_text . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","textColor":"accent-3"} -->
<p class="has-text-align-center has-accent-3-color has-text-color">Learn from experienced instructors who love teaching.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"30px","left":"30px"}}}} -->
<div class="wp-block-columns"><!-- wp:column {"style":{"spacing":{"blockGap":"20px","padding":{"top":"24px","bottom":"24px","left":"24px","right":"24px"}},"border":{"radius":"16px","width":"1px"}},"backgroundColor":"background-1","borderColor":"background-3"} -->
<div class="wp-block-column has-border-color has-background-3-border-color has-background-1-background-color has-background" style="border-width:1px;border-radius:16px;padding-top:24px;padding-right:24px;padding-bottom:24px;padding-left:24px"><!-- wp:image {"id":943,"sizeSlug":"full","linkDestination":"none","align":"full","style":{"border":{"radius":"12px"}}} -->
<figure class="wp-block-image alignfull size-full has-custom-border"><img src="' . esc_url( $online_course_fse_ins1 ) . '" alt="" class="wp-image-943" style="border-radius:12px"/></figure>
<!-- /wp:image -->

<!-- wp:group {"style":{"spacing":{"blockGap":"6px"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center","verticalAlignment":"center"}} -->
<div class="wp-block-group"><!-- wp:heading {"textAlign":"center","level":4,"textColor":"heading"} -->
<h4 class="wp-block-heading has-text-align-center has-heading-color has-text-color">Sarah Mitchell</h4>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"15px"}},"textColor":"accent-3"} -->
<p class="has-text-align-center has-accent-3-color has-text-color" style="font-size:15px">Marketing Teacher</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"spacing":{"blockGap":"20px","padding":{"top":"24px","bottom":"24px","left":"24px","right":"24px"}},"border":{"radius":"16px","width":"1px"}},"backgroundColor":"background-1","borderColor":"background-3"} -->
<div class="wp-block-column has-border-color has-background-3-border-color has-background-1-background-color has-background" style="border-width:1px;border-radius:16px;padding-top:24px;padding-right:24px;padding-bottom:24px;padding-left:24px"><!-- wp:image {"id":944,"sizeSlug":"full","linkDestination":"none","align":"full","style":{"border":{"radius":"12px"}}} -->
<figure class="wp-block-image alignfull size-full has-custom-border"><img src="' . esc_url( $online_course_fse_ins2 ) . '" alt="" class="wp-image-944" style="border-radius:12px"/></figure>
<!-- /wp:image -->

<!-- wp:group {"style":{"spacing":{"blockGap":"6px"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center","verticalAlignment":"center"}} -->
<div class="wp-block-group"><!-- wp:heading {"textAlign":"center","level":4,"textColor":"heading"} -->
<h4 class="wp-block-heading has-text-align-center has-heading-color has-text-color">James Chen</h4>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"15px"}},"textColor":"accent-3"} -->
<p class="has-text-align-center has-accent-3-color has-text-color" style="font-size:15px">Technology Teacher</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"spacing":{"blockGap":"20px","padding":{"top":"24px","bottom":"24px","left":"24px","right":"24px"}},"border":{"radius":"16px","width":"1px"}},"backgroundColor":"background-1","borderColor":"background-3"} -->
<div class="wp-block-column has-border-color has-background-3-border-color has-background-1-background-color has-background" style="border-width:1px;border-radius:16px;padding-top:24px;padding-right:24px;padding-bottom:24px;padding-left:24px"><!-- wp:image {"id":945,"sizeSlug":"full","linkDestination":"none","align":"full","style":{"border":{"radius":"12px"}}} -->
<figure class="wp-block-image alignfull size-full has-custom-border"><img src="' . esc_url( $online_course_fse_ins3 ) . '" alt="" class="wp-image-945" style="border-radius:12px"/></figure>
<!-- /wp:image -->

<!-- wp:group {"style":{"spacing":{"blockGap":"6px"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center","verticalAlignment":"center"}} -->
<div class="wp-block-group"><!-- wp:heading {"textAlign":"center","level":4,"textColor":"heading"} -->
<h4 class="wp-block-heading has-text-align-center has-heading-color has-text-color">Emily Rodriguez</h4>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"15px"}},"textColor":"accent-3"} -->
<p class="has-text-align-center has-accent-3-color has-text-color" style="font-size:15px">Design Teacher</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"spacing":{"blockGap":"20px","padding":{"top":"24px","bottom":"24px","left":"24px","right":"24px"}},"border":{"radius":"16px","width":"1px"}},"backgroundColor":"background-1","borderColor":"background-3"} -->
<div class="wp-block-column has-border-color has-background-3-border-color has-background-1-background-color has-background" style="border-width:1px;border-radius:16px;padding-top:24px;padding-right:24px;padding-bottom:24px;padding-left:24px"><!-- wp:image {"id":946,"sizeSlug":"full","linkDestination":"none","align":"full","style":{"border":{"radius":"12px"}}} -->
<figure class="wp-block-image alignfull size-full has-custom-border"><img src="' . esc_url( $online_course_fse_ins4 ) . '" alt="" class="wp-image-946" style="border-radius:12px"/></figure>
<!-- /wp:image -->

<!-- wp:group {"style":{"spacing":{"blockGap":"6px"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center","verticalAlignment":"center"}} -->
<div class="wp-block-group"><!-- wp:heading {"textAlign":"center","level":4,"textColor":"heading"} -->
<h4 class="wp-block-heading has-text-align-center has-heading-color has-text-color">Michael Foster</h4>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"15px"}},"textColor":"accent-3"} -->
<p class="has-text-align-center has-accent-3-color has-text-color" style="font-size:15px">Business Teacher</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:buttons {"style":{"spacing":{"blockGap":{"top":"16px","left":"16px"}}},"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"primary","style":{"typography":{"fontSize":"17px","textTransform":"capitalize","fontStyle":"normal","fontWeight":"600"}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-primary-background-color has-background has-custom-font-size wp-element-button" style="font-size:17px;font-style:normal;font-weight:600;text-transform:capitalize">' . $view_all_instructors_text . '</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->
',
);
