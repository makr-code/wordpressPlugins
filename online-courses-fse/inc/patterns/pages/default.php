<?php
/**
 * Pattern
 *
 * @author Themegrill
 * @package online-courses-fse
 * @since 1.0.2
 *
 * slug: pages
 * title: Pages
 * categories: OnlineCourseFSE
 * keywords: pages, teachers, team
 */

use OnlineCoursesFSE\Assets_Manager;

$online_course_fse_cta_scaled = Assets_Manager::get_image_url( 'cta.jpg' );

return array(
	'title'      => __( 'Pages', 'online-courses-fse' ),
	'categories' => array( 'online-courses-fse-home' ),
	'keywords'   => array( 'pages', 'teachers', 'team' ),
	'content'    => '

<!-- wp:group {"tagName":"main","style":{"spacing":{"margin":{"top":"0"}}}} -->
<main class="wp-block-group" style="margin-top:0">
	<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"100px","bottom":"100px"},"blockGap":"8px"},"background":{"backgroundImage":{"url":"' . esc_url( $online_course_fse_cta_scaled ) . '","id":948,"source":"file","title":"cta"},"backgroundSize":"cover"}},"layout":{"type":"constrained","contentSize":"1320px","justifyContent":"center"}} -->
	<div class="wp-block-group alignfull" style="padding-top:100px;padding-bottom:100px">
		<!-- wp:post-title {"textAlign":"center","level":1,"style":{"elements":{"link":{"color":{"text":"var:preset|color|background-1"}}},"typography":{"fontSize":"42px","lineHeight":"1.24","textTransform":"capitalize","fontStyle":"normal","fontWeight":"800"}},"textColor":"background-1","fontFamily":"manrope"} /-->
	</div>
	<!-- /wp:group -->
</main>
<!-- /wp:group -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:post-content {"align":"full","textColor":"accent-3","style":{"elements":{"link":{"color":{"text":"var:preset|color|accent-3"}}},"typography":{"fontSize":"19px","lineHeight":"1.84"}}},"layout":{"type":"constrained"}} /-->
</div>
<!-- /wp:group -->
',
);
