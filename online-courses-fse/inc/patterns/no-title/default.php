<?php
/**
 * Pattern
 *
 * @author Themegrill
 * @package online-courses-fse
 * @since 1.0.2
 *
 * slug: no-title
 * title: No Title Page
 * categories: OnlineCourseFSE
 * keywords: no-title, teachers, team
 */

use OnlineCoursesFSE\Assets_Manager;

$online_course_fse_cta_scaled = Assets_Manager::get_image_url( 'cta.jpg' );
$title_text                   = esc_html__( 'Title', 'online-courses-fse' );

return array(
	'title'      => __( 'No Title Page', 'online-courses-fse' ),
	'categories' => array( 'online-courses-fse-home' ),
	'keywords'   => array( 'no-title', 'teachers', 'team' ),
	'content'    => '

<!-- wp:group {"tagName":"main","style":{"spacing":{"margin":{"top":"0"}}}} -->
<main class="wp-block-group" style="margin-top:0">
	<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"100px","bottom":"100px"},"blockGap":"8px"},"background":{"backgroundImage":{"url":"' . esc_url( $online_course_fse_cta_scaled ) . '","id":948,"source":"file","title":"cta"},"backgroundSize":"cover"}},"layout":{"type":"constrained","contentSize":"1320px","justifyContent":"center"}} -->
	<div class="wp-block-group alignfull" style="padding-top:100px;padding-bottom:100px">
		<!-- wp:heading {"textAlign":"center","style":{"elements":{"link":{"color":{"text":"var:preset|color|background-1"}}},"typography":{"fontSize":"42px","lineHeight":"1.24","textTransform":"capitalize","fontStyle":"normal","fontWeight":"800"}},"textColor":"background-1","fontFamily":"manrope"} -->
		<h2 class="wp-block-heading has-text-align-center has-background-1-color has-text-color has-link-color has-manrope-font-family" style="font-size:42px;font-style:normal;font-weight:800;line-height:1.24;text-transform:capitalize">
			' . $title_text . '
		</h2>
		<!-- /wp:heading -->
	</div>
	<!-- /wp:group -->
</main>
<!-- /wp:group -->

<!-- wp:group {"tagName":"main","style":{"spacing":{"margin":{"top":"0"},"padding":{"top":"120px","bottom":"120px"}}}} -->
<main class="wp-block-group" style="margin-top:0;padding-top:120px;padding-bottom:120px">
	<!-- wp:post-content {"lock":{"move":false,"remove":true},"textColor":"accent-3","style":{"elements":{"link":{"color":{"text":"var:preset|color|accent-3"}}},"typography":{"fontSize":"19px","lineHeight":"1.84"}}},"layout":{"type":"constrained","contentSize":"1320px"}} /-->
</main>
<!-- /wp:group -->
',
);
