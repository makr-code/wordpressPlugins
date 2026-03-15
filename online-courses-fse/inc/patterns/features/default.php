<?php
/**
 * Pattern
 *
 * @author Themegrill
 * @package online-courses-fse
 * @since 1.0.2
 *
 * slug: features
 * title: Features
 * categories: OnlineCourseFSE
 * keywords: features, teachers, team
 */

use OnlineCoursesFSE\Assets_Manager;

$online_course_fse_certified_achievements  = Assets_Manager::get_image_url( 'certified-achievements.png' );
$online_course_fse_interactive_lessons     = Assets_Manager::get_image_url( 'interactive-lessons.png' );
$online_course_fse_flexible_learning_paths = Assets_Manager::get_image_url( 'flexible-learning-paths.png' );
$online_course_fse_smart_progress_tracking = Assets_Manager::get_image_url( 'smart-progress-tracking.png' );
$online_course_fse_expert_instructors      = Assets_Manager::get_image_url( 'expert-instructors.png' );
$online_course_fse_advanced_courses        = Assets_Manager::get_image_url( 'advanced-courses.png' );

$our_features_tag_text = esc_html__( 'Our Features', 'online-courses-fse' );

return array(
	'title'      => __( 'Features', 'online-courses-fse' ),
	'categories' => array( 'online-courses-fse-features' ),
	'keywords'   => array( 'features', 'teachers', 'team' ),
	'content'    => '

<!-- wp:group {"metadata":{"name":"Features"},"align":"full","style":{"spacing":{"padding":{"top":"120px","bottom":"120px"},"blockGap":"60px"}},"backgroundColor":"background-1","layout":{"type":"constrained","contentSize":"1320px"}} -->
<div class="wp-block-group alignfull has-background-1-background-color has-background" style="padding-top:120px;padding-bottom:120px"><!-- wp:group {"layout":{"type":"constrained","contentSize":"742px"}} -->
<div class="wp-block-group"><!-- wp:group {"style":{"spacing":{"padding":{"bottom":"10px"},"blockGap":"8px"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
<div class="wp-block-group" style="padding-bottom:10px"><!-- wp:group {"style":{"spacing":{"padding":{"top":"9px","bottom":"9px","left":"20px","right":"20px"}},"border":{"radius":"20px"}},"backgroundColor":"background-3","textColor":"primary","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-primary-color has-background-3-background-color has-text-color has-background" style="border-radius:20px;padding-top:9px;padding-right:20px;padding-bottom:9px;padding-left:20px"><!-- wp:heading {"textAlign":"center","level":6,"style":{"typography":{"textTransform":"uppercase","fontWeight":"700","letterSpacing":"1.4px","fontStyle":"normal"}},"textColor":"primary"} -->
<h6 class="wp-block-heading has-text-align-center has-primary-color has-text-color" style="font-style:normal;font-weight:700;letter-spacing:1.4px;text-transform:uppercase">' . $our_features_tag_text . '</h6>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:heading {"textAlign":"center","style":{"typography":{"textTransform":"capitalize"}}} -->
<h2 class="wp-block-heading has-text-align-center" style="text-transform:capitalize">Elevate Your Learning Experience</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Transform your career with structured learning paths designed by industry professionals.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"blockGap":"0px"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group"><!-- wp:columns {"verticalAlignment":"center","style":{"spacing":{"blockGap":{"top":"0px","left":"0px"}}}} -->
<div class="wp-block-columns are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","style":{"spacing":{"padding":{"right":"50px","left":"50px","top":"44px","bottom":"44px"}},"border":{"radius":"16px"}},"backgroundColor":"background-2","layout":{"type":"default"}} -->
<div class="wp-block-column is-vertically-aligned-center has-background-2-background-color has-background" style="border-radius:16px;padding-top:44px;padding-right:50px;padding-bottom:44px;padding-left:50px"><!-- wp:group {"align":"full","style":{"spacing":{"blockGap":"20px"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group alignfull"><!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:image {"id":876,"sizeSlug":"full","linkDestination":"none","align":"left"} -->
<figure class="wp-block-image alignleft size-full"><img src="' . esc_url( $online_course_fse_advanced_courses ) . '" alt="" class="wp-image-876"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"blockGap":"6px"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"left","verticalAlignment":"center"}} -->
<div class="wp-block-group"><!-- wp:heading {"textAlign":"left","level":4,"style":{"typography":{"textTransform":"capitalize"}}} -->
<h4 class="wp-block-heading has-text-align-left" style="text-transform:capitalize">Learn Anytime</h4>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"left","style":{"typography":{"fontSize":"17px"}}} -->
<p class="has-text-align-left" style="font-size:17px">Study whenever you want. All course videos &amp; materials are available 24/7.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","style":{"spacing":{"padding":{"right":"50px","left":"50px","top":"44px","bottom":"44px"}},"border":{"radius":"16px"}},"backgroundColor":"background-1","layout":{"type":"default"}} -->
<div class="wp-block-column is-vertically-aligned-center has-background-1-background-color has-background" style="border-radius:16px;padding-top:44px;padding-right:50px;padding-bottom:44px;padding-left:50px"><!-- wp:group {"align":"full","style":{"spacing":{"blockGap":"20px"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group alignfull"><!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:image {"id":878,"sizeSlug":"full","linkDestination":"none","align":"left"} -->
<figure class="wp-block-image alignleft size-full"><img src="' . esc_url( $online_course_fse_expert_instructors ) . '" alt="" class="wp-image-878"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"blockGap":"6px"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"left","verticalAlignment":"center"}} -->
<div class="wp-block-group"><!-- wp:heading {"textAlign":"left","level":4,"style":{"typography":{"textTransform":"capitalize"}}} -->
<h4 class="wp-block-heading has-text-align-left" style="text-transform:capitalize">Expert Teachers</h4>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"left","style":{"typography":{"fontSize":"17px"}}} -->
<p class="has-text-align-left" style="font-size:17px">Learn from experienced instructors who know their subjects inside out.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","style":{"spacing":{"padding":{"right":"50px","left":"50px","top":"44px","bottom":"44px"}},"border":{"radius":"16px"}},"backgroundColor":"background-2","layout":{"type":"default"}} -->
<div class="wp-block-column is-vertically-aligned-center has-background-2-background-color has-background" style="border-radius:16px;padding-top:44px;padding-right:50px;padding-bottom:44px;padding-left:50px"><!-- wp:group {"align":"full","style":{"spacing":{"blockGap":"20px"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group alignfull"><!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:image {"id":879,"sizeSlug":"full","linkDestination":"none","align":"left"} -->
<figure class="wp-block-image alignleft size-full"><img src="' . esc_url( $online_course_fse_smart_progress_tracking ) . '" alt="" class="wp-image-879"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"blockGap":"6px"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"left","verticalAlignment":"center"}} -->
<div class="wp-block-group"><!-- wp:heading {"textAlign":"left","level":4,"style":{"typography":{"textTransform":"capitalize"}}} -->
<h4 class="wp-block-heading has-text-align-left" style="text-transform:capitalize">Smart Progress Tracking</h4>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"left","style":{"typography":{"fontSize":"17px"}}} -->
<p class="has-text-align-left" style="font-size:17px">Know what you\'ve finished and what\'s coming next.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:columns {"verticalAlignment":"center","style":{"spacing":{"blockGap":{"top":"0px","left":"0px"}}}} -->
<div class="wp-block-columns are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","style":{"spacing":{"padding":{"right":"50px","left":"50px","top":"44px","bottom":"44px"}},"border":{"radius":"16px"}},"backgroundColor":"background-1","layout":{"type":"default"}} -->
<div class="wp-block-column is-vertically-aligned-center has-background-1-background-color has-background" style="border-radius:16px;padding-top:44px;padding-right:50px;padding-bottom:44px;padding-left:50px"><!-- wp:group {"align":"full","style":{"spacing":{"blockGap":"20px"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group alignfull"><!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:image {"id":880,"sizeSlug":"full","linkDestination":"none","align":"left"} -->
<figure class="wp-block-image alignleft size-full"><img src="' . esc_url( $online_course_fse_flexible_learning_paths ) . '" alt="" class="wp-image-880"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"blockGap":"6px"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"left","verticalAlignment":"center"}} -->
<div class="wp-block-group"><!-- wp:heading {"textAlign":"left","level":4,"style":{"typography":{"textTransform":"capitalize"}}} -->
<h4 class="wp-block-heading has-text-align-left" style="text-transform:capitalize">Flexible Learning Paths</h4>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"left","style":{"typography":{"fontSize":"17px"}}} -->
<p class="has-text-align-left" style="font-size:17px">Study at your own pace and choose your schedule.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","style":{"spacing":{"padding":{"right":"50px","left":"50px","top":"44px","bottom":"44px"}},"border":{"radius":"16px"}},"backgroundColor":"background-2","layout":{"type":"default"}} -->
<div class="wp-block-column is-vertically-aligned-center has-background-2-background-color has-background" style="border-radius:16px;padding-top:44px;padding-right:50px;padding-bottom:44px;padding-left:50px"><!-- wp:group {"align":"full","style":{"spacing":{"blockGap":"20px"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group alignfull"><!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:image {"id":881,"sizeSlug":"full","linkDestination":"none","align":"left"} -->
<figure class="wp-block-image alignleft size-full"><img src="' . esc_url( $online_course_fse_interactive_lessons ) . '" alt="" class="wp-image-881"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"blockGap":"6px"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"left","verticalAlignment":"center"}} -->
<div class="wp-block-group"><!-- wp:heading {"textAlign":"left","level":4,"style":{"typography":{"textTransform":"capitalize"}}} -->
<h4 class="wp-block-heading has-text-align-left" style="text-transform:capitalize">Complete Lessons</h4>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"left","style":{"typography":{"fontSize":"17px"}}} -->
<p class="has-text-align-left" style="font-size:17px">Everything you need to learn is included. Basics to advanced topics.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","style":{"spacing":{"padding":{"right":"50px","left":"50px","top":"44px","bottom":"44px"}},"border":{"radius":"16px"}},"backgroundColor":"background-1","layout":{"type":"default"}} -->
<div class="wp-block-column is-vertically-aligned-center has-background-1-background-color has-background" style="border-radius:16px;padding-top:44px;padding-right:50px;padding-bottom:44px;padding-left:50px"><!-- wp:group {"align":"full","style":{"spacing":{"blockGap":"20px"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group alignfull"><!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:image {"id":882,"sizeSlug":"full","linkDestination":"none","align":"left"} -->
<figure class="wp-block-image alignleft size-full"><img src="' . esc_url( $online_course_fse_certified_achievements ) . '" alt="" class="wp-image-882"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"blockGap":"6px"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"left","verticalAlignment":"center"}} -->
<div class="wp-block-group"><!-- wp:heading {"textAlign":"left","level":4,"style":{"typography":{"textTransform":"capitalize"}}} -->
<h4 class="wp-block-heading has-text-align-left" style="text-transform:capitalize">Get Certified</h4>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"left","style":{"typography":{"fontSize":"17px"}}} -->
<p class="has-text-align-left" style="font-size:17px">Receive a certificate when you complete a course.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
',
);
