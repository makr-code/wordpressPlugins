<?php
/**
 * Pattern: 404 Error Page
 *
 * @author Themegrill
 * @package online-courses-fse
 * @since 1.0.2
 */

use OnlineCoursesFSE\Assets_Manager;

$online_course_fse_cta = Assets_Manager::get_image_url( 'cta-404.jpg' );
$home_url              = esc_url( home_url( '/' ) );

$error_title_text       = esc_html__( '404 - Page Not Found', 'online-courses-fse' );
$error_description_text = esc_html__( 'Unfortunately the page was not found! Head back to the homepage to continue exploring our courses and resources.', 'online-courses-fse' );
$back_home_text         = esc_html__( 'Back to Homepage', 'online-courses-fse' );

return array(
	'title'      => __( '404 Error Page', 'online-courses-fse' ),
	'categories' => array( 'online-courses-fse' ),
	'keywords'   => array( '404', 'error', 'not found' ),
	'content'    => '
<!-- wp:group {"tagName":"main","layout":{"type":"default"}} -->
<main class="wp-block-group">
	<!-- wp:cover {"url":"' . esc_url( $online_course_fse_cta ) . '","id":948,"dimRatio":50,"customOverlayColor":"#0c3835","isUserOverlayColor":false,"sizeSlug":"large","style":{"spacing":{"padding":{"top":"100px","bottom":"100px"}}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-cover" style="padding-top:100px;padding-bottom:100px">
		<img class="wp-block-cover__image-background wp-image-948 size-large" alt="" src="' . esc_url( $online_course_fse_cta ) . '" data-object-fit="cover"/>
		<span aria-hidden="true" class="wp-block-cover__background has-background-dim" style="background-color:#0c3835"></span>
		<div class="wp-block-cover__inner-container">
			<!-- wp:group {"style":{"spacing":{"blockGap":"2.25rem"}},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group">
				<!-- wp:heading {"textAlign":"center","style":{"elements":{"link":{"color":{"text":"var:preset|color|background-1"}}},"typography":{"fontSize":"56px","lineHeight":"1.5","fontStyle":"normal","fontWeight":"800","textTransform":"capitalize"}},"textColor":"background-1"} -->
				<h2 class="wp-block-heading has-text-align-center has-background-1-color has-text-color has-link-color" style="font-size:56px;font-style:normal;font-weight:800;line-height:1.5;text-transform:capitalize">
					' . $error_title_text . '
				</h2>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"19px","lineHeight":"1.84"},"elements":{"link":{"color":{"text":"#fafafa"}}},"color":{"text":"#fafafa"}}} -->
				<p class="has-text-align-center has-text-color has-link-color" style="color:#fafafa;font-size:19px;line-height:1.84">
					' . $error_description_text . '
				</p>
				<!-- /wp:paragraph -->

				<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
				<div class="wp-block-buttons">
					<!-- wp:button {"textAlign":"center","textColor":"background-1","style":{"elements":{"link":{"color":{"text":"var:preset|color|background-1"}}},"typography":{"fontSize":"17px","fontStyle":"normal","fontWeight":"600","textTransform":"capitalize"},"color":{"background":"#118B57"}}} -->
					<div class="wp-block-button">
						<a class="wp-block-button__link has-background-1-color has-text-color has-background has-link-color has-text-align-center has-custom-font-size wp-element-button" href="' . $home_url . '" style="background-color:#118B57;font-size:17px;font-style:normal;font-weight:600;text-transform:capitalize">
							' . $back_home_text . '
						</a>
						
					</div>
					<!-- /wp:button -->
				</div>
				<!-- /wp:buttons -->
			</div>
			<!-- /wp:group -->
		</div>
	</div>
	<!-- /wp:cover -->
</main>
<!-- /wp:group -->
',
);