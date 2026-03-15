<?php
/**
 * Pattern
 *
 * @author Themegrill
 * @package online-courses-fse
 * @since 1.0.2
 *
 * slug: about-us
 * title: About Us
 * categories: OnlineCourseFSE
 * keywords: about-us, call to action, cover
 */

use OnlineCoursesFSE\Assets_Manager;

$online_course_fse_about_us_1 = Assets_Manager::get_image_url( 'about-us-1.jpg' );
$online_course_fse_about_us_2 = Assets_Manager::get_image_url( 'about-us-2.jpg' );
$online_course_fse_about_us_3 = Assets_Manager::get_image_url( 'about-us-3.jpg' );

$about_us_tag_text    = esc_html__( 'About Us', 'online-courses-fse' );
$about_us_button_text = esc_html__( 'About Us', 'online-courses-fse' );

return array(
	'title'      => __( 'About Us', 'online-courses-fse' ),
	'categories' => array( 'online-courses-fse-about-us' ),
	'keywords'   => array( 'about-us', 'call to action' ),
	'content'    => '

<!-- wp:group {"metadata":{"name":"About Us"},"align":"full","style":{"spacing":{"padding":{"top":"120px","bottom":"120px"},"blockGap":"60px"}},"backgroundColor":"background-1","layout":{"type":"constrained","contentSize":"1320px"}} -->
<div class="wp-block-group alignfull has-background-1-background-color has-background" style="padding-top:120px;padding-bottom:120px">
	<!-- wp:group {"layout":{"type":"constrained","contentSize":"776px"}} -->
	<div class="wp-block-group">
		<!-- wp:group {"style":{"spacing":{"padding":{"bottom":"10px"},"blockGap":"8px"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
		<div class="wp-block-group" style="padding-bottom:10px">
			<!-- wp:group {"style":{"spacing":{"padding":{"top":"9px","bottom":"9px","left":"20px","right":"20px"}},"border":{"radius":"20px"}},"backgroundColor":"background-3","textColor":"accent-2","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-accent-2-color has-background-3-background-color has-text-color has-background" style="border-radius:20px;padding-top:9px;padding-right:20px;padding-bottom:9px;padding-left:20px"><!-- wp:heading {"textAlign":"center","level":6,"style":{"typography":{"textTransform":"uppercase","fontWeight":"700","letterSpacing":"1.4px","fontStyle":"normal"}},"textColor":"primary"} -->
<h6 class="wp-block-heading has-text-align-center has-primary-color has-text-color" style="font-style:normal;font-weight:700;letter-spacing:1.4px;text-transform:uppercase">
				' . $about_us_tag_text . '
				</h6>
<!-- /wp:heading --></div>
<!-- /wp:group -->
			<!-- wp:heading {"className":"has-text-align-center has-text-color has-link-color has-manrope-font-family"} -->
<h2 class="wp-block-heading has-text-align-center has-text-color has-link-color has-manrope-font-family">
							Where Knowledge Meets Innovation
						</h2>
<!-- /wp:heading -->
	
			<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"19px","lineHeight":"1.84"}}} -->
<p class="has-text-align-center has-text-color" style="font-size:19px;line-height:1.84">Discover our most enrolled and top-rated classes. Learn skills that students trust and employers value.</p>
<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"style":{"spacing":{"blockGap":"20px"}},"layout":{"type":"flex","orientation":"vertical"}} -->
	<div class="wp-block-group">
		<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"30px","left":"30px"}}}} -->
		<div class="wp-block-columns">
			<!-- wp:column {"verticalAlignment":"stretch","width":"33.33%","style":{"spacing":{"padding":{"top":"60px","bottom":"60px","left":"40px","right":"40px"}},"border":{"radius":"16px","width":"0px","style":"none"}},"backgroundColor":"background-2","layout":{"type":"default"}} -->
<div class="wp-block-column is-vertically-aligned-stretch has-background-2-background-color has-background" style="border-style:none;border-width:0px;border-radius:16px;padding-top:60px;padding-right:40px;padding-bottom:60px;padding-left:40px;flex-basis:33.33%"><!-- wp:group {"align":"full","style":{"spacing":{"blockGap":"20px"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group alignfull"><!-- wp:group {"style":{"spacing":{"blockGap":"6px"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"left","verticalAlignment":"center"}} -->
<div class="wp-block-group"><!-- wp:heading {"textAlign":"left","level":4,"style":{"typography":{"fontSize":"20px","fontStyle":"normal","fontWeight":"700","lineHeight":"1.6","textTransform":"capitalize"}},"fontFamily":"manrope"} -->
<h4 class="wp-block-heading has-text-align-left has-manrope-font-family" style="font-size:20px;font-style:normal;font-weight:700;line-height:1.6;text-transform:capitalize">
							Our Mission
						</h4>
						<!-- /wp:heading -->

						<!-- wp:paragraph {"align":"left","style":{"elements":{"link":{"color":{"text":"var:preset|color|paragraph"}}},"typography":{"fontSize":"17px","lineHeight":"1.82"}},"textColor":"paragraph"} -->
<p class="has-text-align-left has-paragraph-color has-text-color has-link-color" style="font-size:17px;line-height:1.82">
							I took the Website Development course and got my dream job in 6 months. It really helped me.
						</p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"verticalAlignment":"center","width":"33.33%","style":{"spacing":{"padding":{"right":"0px","left":"0px","top":"0px","bottom":"0px"}},"border":{"radius":"16px"}},"layout":{"type":"default"}} -->
			<div class="wp-block-column is-vertically-aligned-center" style="border-radius:16px;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px;flex-basis:33.33%">
				<!-- wp:image {"id":920,"sizeSlug":"full","linkDestination":"none","align":"full","style":{"border":{"radius":"16px"}}} -->
				<figure class="wp-block-image alignfull size-full has-custom-border"><img src="' . esc_url( $online_course_fse_about_us_1 ) . '" alt="" class="wp-image-920" style="border-radius:16px"/></figure>
				<!-- /wp:image -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"verticalAlignment":"stretch","width":"33.33%","style":{"spacing":{"padding":{"top":"60px","bottom":"60px","left":"40px","right":"40px"}},"border":{"radius":"16px","width":"0px","style":"none"}},"backgroundColor":"background-2","layout":{"type":"default"}} -->
<div class="wp-block-column is-vertically-aligned-stretch has-background-2-background-color has-background" style="border-style:none;border-width:0px;border-radius:16px;padding-top:60px;padding-right:40px;padding-bottom:60px;padding-left:40px;flex-basis:33.33%"><!-- wp:group {"align":"full","style":{"spacing":{"blockGap":"20px"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group alignfull"><!-- wp:group {"style":{"spacing":{"blockGap":"6px"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"left","verticalAlignment":"center"}} -->
<div class="wp-block-group"><!-- wp:heading {"textAlign":"left","level":4,"style":{"typography":{"fontSize":"20px","fontStyle":"normal","fontWeight":"700","lineHeight":"1.6","textTransform":"capitalize"}},"fontFamily":"manrope"} -->
<h4 class="wp-block-heading has-text-align-left has-manrope-font-family" style="font-size:20px;font-style:normal;font-weight:700;line-height:1.6;text-transform:capitalize">
							Our Techniques
						</h4>
						<!-- /wp:heading -->

						<!-- wp:paragraph {"align":"left","style":{"elements":{"link":{"color":{"text":"var:preset|color|paragraph"}}},"typography":{"fontSize":"17px","lineHeight":"1.82"}},"textColor":"paragraph"} -->
<p class="has-text-align-left has-paragraph-color has-text-color has-link-color" style="font-size:17px;line-height:1.82">
							To empower learners worldwide by providing accessible, engaging, and high-quality education that inspires growth, innovation, and lifelong learning
						</p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->

		<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"30px","left":"30px"}}}} -->
		<div class="wp-block-columns">
			<!-- wp:column {"verticalAlignment":"center","width":"33.33%","style":{"spacing":{"padding":{"right":"0px","left":"0px","top":"0px","bottom":"0px"}},"border":{"radius":"16px"}},"layout":{"type":"default"}} -->
			<div class="wp-block-column is-vertically-aligned-center" style="border-radius:16px;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px;flex-basis:33.33%">
				<!-- wp:image {"id":919,"sizeSlug":"full","linkDestination":"none","align":"full","style":{"border":{"radius":"16px"}}} -->
				<figure class="wp-block-image alignfull size-full has-custom-border"><img src="' . esc_url( $online_course_fse_about_us_2 ) . '" alt="" class="wp-image-919" style="border-radius:16px"/></figure>
				<!-- /wp:image -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"verticalAlignment":"stretch","width":"33.33%","style":{"spacing":{"padding":{"top":"60px","bottom":"60px","left":"40px","right":"40px"}},"border":{"radius":"16px","width":"0px","style":"none"}},"backgroundColor":"background-2","layout":{"type":"default"}} -->
<div class="wp-block-column is-vertically-aligned-stretch has-background-2-background-color has-background" style="border-style:none;border-width:0px;border-radius:16px;padding-top:60px;padding-right:40px;padding-bottom:60px;padding-left:40px;flex-basis:33.33%"><!-- wp:group {"align":"full","style":{"spacing":{"blockGap":"20px"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group alignfull"><!-- wp:group {"style":{"spacing":{"blockGap":"6px"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"left","verticalAlignment":"center"}} -->
<div class="wp-block-group"><!-- wp:heading {"textAlign":"left","level":4,"style":{"typography":{"fontSize":"20px","fontStyle":"normal","fontWeight":"700","lineHeight":"1.6","textTransform":"capitalize"}},"fontFamily":"manrope"} -->
<h4 class="wp-block-heading has-text-align-left has-manrope-font-family" style="font-size:20px;font-style:normal;font-weight:700;line-height:1.6;text-transform:capitalize">
							Our Purpose
						</h4>
						<!-- /wp:heading -->

						<!-- wp:paragraph {"align":"left","style":{"elements":{"link":{"color":{"text":"var:preset|color|paragraph"}}},"typography":{"fontSize":"17px","lineHeight":"1.82"}},"textColor":"paragraph"} -->
<p class="has-text-align-left has-paragraph-color has-text-color has-link-color" style="font-size:17px;line-height:1.82">
							We want to inspire people to keep learning and improving. Our purpose is to help everyone find their path and move forward with confidence.
						</p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"verticalAlignment":"center","width":"33.33%","style":{"spacing":{"padding":{"right":"0px","left":"0px","top":"0px","bottom":"0px"}},"border":{"radius":"16px"}},"layout":{"type":"default"}} -->
			<div class="wp-block-column is-vertically-aligned-center" style="border-radius:16px;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px;flex-basis:33.33%">
				<!-- wp:image {"id":921,"sizeSlug":"full","linkDestination":"none","align":"full","style":{"border":{"radius":"16px"}}} -->
				<figure class="wp-block-image alignfull size-full has-custom-border"><img src="' . esc_url( $online_course_fse_about_us_3 ) . '" alt="" class="wp-image-921" style="border-radius:16px"/></figure>
				<!-- /wp:image -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->
	</div>
	<!-- /wp:group -->

	<!-- wp:buttons {"style":{"spacing":{"blockGap":{"top":"16px","left":"16px"}}},"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"primary","style":{"typography":{"fontSize":"17px","textTransform":"capitalize","fontStyle":"normal","fontWeight":"600"}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-primary-background-color has-background has-custom-font-size wp-element-button" style="font-size:17px;font-style:normal;font-weight:600;text-transform:capitalize">
			' . $about_us_button_text . '
			</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
',
);
