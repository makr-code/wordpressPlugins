<?php
/**
 * Pattern
 *
 * @author Themegrill
 * @package online-courses-fse
 * @since 1.0.2
 *
 * slug: pricing
 * title: Pricing
 * categories: OnlineCourseFSE
 * keywords: pricing, teachers, team
 */

use OnlineCoursesFSE\Assets_Manager;

$online_course_fse_tick = Assets_Manager::get_image_url( 'tick.png' );

$pricing_tag_text    = esc_html__( 'Pricing', 'online-courses-fse' );
$simple_pricing_text = esc_html__( 'Simple Pricing', 'online-courses-fse' );
$start_learning_text = esc_html__( 'Start Learning', 'online-courses-fse' );
$get_started_text    = esc_html__( 'Get Started', 'online-courses-fse' );

return array(
	'title'      => __( 'Pricing', 'online-courses-fse' ),
	'categories' => array( 'online-courses-fse-pricing' ),
	'keywords'   => array( 'pricing', 'teachers', 'team' ),
	'content'    => '

		<!-- wp:group {"metadata":{"name":"Pricing"},"align":"full","style":{"spacing":{"padding":{"top":"120px","bottom":"120px","left":"var:preset|spacing|30","right":"var:preset|spacing|30"},"blockGap":"60px"}},"layout":{"type":"constrained","contentSize":"1320px"}} -->
		<div class="wp-block-group alignfull has-background" style="padding-top:120px;padding-right:var(--wp--preset--spacing--30);padding-bottom:120px;padding-left:var(--wp--preset--spacing--30)">
			<!-- wp:group {"style":{"spacing":{"padding":{"bottom":"10px"},"blockGap":"8px"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
			<div class="wp-block-group" style="padding-bottom:10px">
				<!-- wp:group {"style":{"spacing":{"padding":{"top":"9px","bottom":"9px","left":"20px","right":"20px"}},"border":{"radius":"20px"}},"backgroundColor":"background-3","textColor":"accent-2","layout":{"type":"constrained"}} -->
		<div class="wp-block-group has-accent-2-color has-background-3-background-color has-text-color has-background" style="border-radius:20px;padding-top:9px;padding-right:20px;padding-bottom:9px;padding-left:20px"><!-- wp:heading {"textAlign":"center","level":6,"style":{"typography":{"textTransform":"uppercase","fontWeight":"700","letterSpacing":"1.4px","fontStyle":"normal"}},"textColor":"primary"} -->
		<h6 class="wp-block-heading has-text-align-center has-primary-color has-text-color" style="font-style:normal;font-weight:700;letter-spacing:1.4px;text-transform:uppercase">
						' . $pricing_tag_text . '
					</h6>
		<!-- /wp:heading --></div>
		<!-- /wp:group -->

		<!-- wp:heading {"textAlign":"center","style":{"typography":{"fontSize":"42px","fontStyle":"normal","fontWeight":"800","lineHeight":"1.24","textTransform":"capitalize"}},"fontFamily":"manrope"} -->
		<h2 class="wp-block-heading has-text-align-center has-manrope-font-family" style="font-size:42px;font-style:normal;font-weight:800;line-height:1.24;text-transform:capitalize">
			' . $simple_pricing_text . '
		</h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"19px","lineHeight":"1.84"}}} -->
		<p class="has-text-align-center" style="font-size:19px;line-height:1.84">
			Choose a plan that works for you.
		</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:columns {"verticalAlignment":"center","style":{"spacing":{"blockGap":{"top":"30px","left":"30px"},"margin":{"top":"60px"}}}} -->
<div class="wp-block-columns are-vertically-aligned-center" style="margin-top:60px"><!-- wp:column {"verticalAlignment":"center","style":{"spacing":{"padding":{"right":"40px","left":"40px","top":"40px","bottom":"40px"},"blockGap":"32px"},"border":{"radius":"16px","width":"1px"}},"backgroundColor":"background-2","borderColor":"background-3","layout":{"type":"default"}} -->
<div class="wp-block-column is-vertically-aligned-center has-border-color has-background-3-border-color has-background-2-background-color has-background" style="border-width:1px;border-radius:16px;padding-top:40px;padding-right:40px;padding-bottom:40px;padding-left:40px"><!-- wp:group {"style":{"spacing":{"blockGap":"24px"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group"><!-- wp:group {"style":{"spacing":{"blockGap":"6px"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"left","verticalAlignment":"center"}} -->
<div class="wp-block-group"><!-- wp:heading {"textAlign":"left","level":5,"style":{"typography":{"fontSize":"26px","fontStyle":"normal","fontWeight":"700","lineHeight":"1.46"}}} -->
<h5 class="wp-block-heading has-text-align-left" style="font-size:26px;font-style:normal;font-weight:700;line-height:1.46">
						<strong>Single Course</strong>
					</h5>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"align":"left","style":{"typography":{"fontSize":"15px","lineHeight":"1.93"}}} -->
					<p class="has-text-align-left" style="font-size:15px;line-height:1.93">
						For individuals who want to learn at their own pace.
					</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"style":{"spacing":{"blockGap":"6px"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"left","verticalAlignment":"center"}} -->
				<div class="wp-block-group">
					<!-- wp:group {"style":{"spacing":{"blockGap":"10px"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
					<div class="wp-block-group">
						<!-- wp:image {"id":782,"width":"20px","height":"20px","scale":"contain","sizeSlug":"full","linkDestination":"none","style":{"layout":{"selfStretch":"fixed","flexSize":"20px"}}} -->
						<figure class="wp-block-image size-full is-resized"><img src="' . esc_url( $online_course_fse_tick ) . '" alt="" class="wp-image-782" style="object-fit:contain;width:20px;height:20px"/></figure>
						<!-- /wp:image -->

						<!-- wp:paragraph {"align":"left","style":{"typography":{"fontSize":"17px","lineHeight":"1.82"}}} -->
						<p class="has-text-align-left " style="font-size:17px;line-height:1.82">
							Access to all lessons&nbsp;
						</p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->

					<!-- wp:group {"style":{"spacing":{"blockGap":"10px"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
					<div class="wp-block-group">
						<!-- wp:image {"id":782,"width":"20px","height":"20px","scale":"contain","sizeSlug":"full","linkDestination":"none","style":{"layout":{"selfStretch":"fixed","flexSize":"20px"}}} -->
						<figure class="wp-block-image size-full is-resized"><img src="' . esc_url( $online_course_fse_tick ) . '" alt="" class="wp-image-782" style="object-fit:contain;width:20px;height:20px"/></figure>
						<!-- /wp:image -->

						<!-- wp:paragraph {"align":"left","style":{"typography":{"fontSize":"17px","lineHeight":"1.82"}}} -->
						<p class="has-text-align-left" style="font-size:17px;line-height:1.82">
							Get a certificate&nbsp;
						</p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->

					<!-- wp:group {"style":{"spacing":{"blockGap":"10px"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
					<div class="wp-block-group">
						<!-- wp:image {"id":782,"width":"20px","height":"20px","scale":"contain","sizeSlug":"full","linkDestination":"none","style":{"layout":{"selfStretch":"fixed","flexSize":"20px"}}} -->
						<figure class="wp-block-image size-full is-resized"><img src="' . esc_url( $online_course_fse_tick ) . '" alt="" class="wp-image-782" style="object-fit:contain;width:20px;height:20px"/></figure>
						<!-- /wp:image -->

						<!-- wp:paragraph {"align":"left","style":{"typography":{"fontSize":"17px","lineHeight":"1.82"}}} -->
						<p class="has-text-align-left" style="font-size:17px;line-height:1.82">
							Email support for 6 months&nbsp;
						</p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->

					<!-- wp:group {"style":{"spacing":{"blockGap":"10px"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
					<div class="wp-block-group">
						<!-- wp:image {"id":782,"width":"20px","height":"20px","scale":"contain","sizeSlug":"full","linkDestination":"none","style":{"layout":{"selfStretch":"fixed","flexSize":"20px"}}} -->
						<figure class="wp-block-image size-full is-resized"><img src="' . esc_url( $online_course_fse_tick ) . '" alt="" class="wp-image-782" style="object-fit:contain;width:20px;height:20px"/></figure>
						<!-- /wp:image -->

						<!-- wp:paragraph {"align":"left","style":{"typography":{"fontSize":"17px","lineHeight":"1.82"}}} -->
						<p class="has-text-align-left" style="font-size:17px;line-height:1.82">
							Join our student community
						</p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:group -->

			<!-- wp:columns {"style":{"spacing":{"padding":{"top":"33px"},"blockGap":{"top":"24px","left":"0px"}},"border":{"top":{"color":"#e6e6e6","width":"1px"}}}} -->
			<div class="wp-block-columns" style="border-top-color:#e6e6e6;border-top-width:1px;padding-top:33px">
				<!-- wp:column -->
				<div class="wp-block-column">
					<!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
					<div class="wp-block-group">
						<!-- wp:group {"style":{"spacing":{"blockGap":"4px"}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"left"}} -->
						<div class="wp-block-group">
							<!-- wp:heading {"level":4,"style":{"typography":{"fontSize":"28px","fontStyle":"normal","fontWeight":"700"}}} -->
							<h4 class="wp-block-heading" style="font-size:28px;font-style:normal;font-weight:700">
								$100.0
							</h4>
							<!-- /wp:heading -->

							<!-- wp:paragraph {"align":"left","style":{"typography":{"fontSize":"20px","lineHeight":"2"}}} -->
							<p class="has-text-align-left" style="font-size:20px;line-height:2">
								/mo
							</p>
							<!-- /wp:paragraph -->
						</div>
						<!-- /wp:group -->

						<!-- wp:buttons {"style":{"spacing":{"blockGap":{"top":"16px","left":"16px"}}},"layout":{"type":"flex","justifyContent":"left","verticalAlignment":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"primary","style":{"typography":{"fontSize":"17px","textTransform":"capitalize","fontStyle":"normal","fontWeight":"600"}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-primary-background-color has-background has-custom-font-size wp-element-button" style="font-size:17px;font-style:normal;font-weight:600;text-transform:capitalize">
								' . $start_learning_text . '
								</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:column -->
			</div>
			<!-- /wp:columns -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center","style":{"spacing":{"padding":{"right":"40px","left":"40px","top":"40px","bottom":"40px"},"blockGap":"32px"},"border":{"radius":"16px","width":"1px"}},"backgroundColor":"background-2","borderColor":"background-3","layout":{"type":"default"}} -->
<div class="wp-block-column is-vertically-aligned-center has-border-color has-background-3-border-color has-background-2-background-color has-background" style="border-width:1px;border-radius:16px;padding-top:40px;padding-right:40px;padding-bottom:40px;padding-left:40px"><!-- wp:group {"style":{"spacing":{"blockGap":"24px"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group"><!-- wp:group {"style":{"spacing":{"blockGap":"6px"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"left","verticalAlignment":"center"}} -->
<div class="wp-block-group"><!-- wp:heading {"textAlign":"left","level":5,"style":{"typography":{"fontSize":"26px","fontStyle":"normal","fontWeight":"700","lineHeight":"1.46"}}} -->
<h5 class="wp-block-heading has-text-align-left" style="font-size:26px;font-style:normal;font-weight:700;line-height:1.46">
						<strong><strong>Course Bundle</strong></strong>
					</h5>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"align":"left","style":{"typography":{"fontSize":"15px","lineHeight":"1.93"}}} -->
					<p class="has-text-align-left" style="font-size:15px;line-height:1.93">
						For groups and organizations. Everything in single course, plus:&nbsp;
					</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"style":{"spacing":{"blockGap":"6px"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"left","verticalAlignment":"center"}} -->
				<div class="wp-block-group">
					<!-- wp:group {"style":{"spacing":{"blockGap":"10px"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
					<div class="wp-block-group">
						<!-- wp:image {"id":782,"width":"20px","height":"20px","scale":"contain","sizeSlug":"full","linkDestination":"none","style":{"layout":{"selfStretch":"fixed","flexSize":"20px"}}} -->
						<figure class="wp-block-image size-full is-resized"><img src="' . esc_url( $online_course_fse_tick ) . '" alt="" class="wp-image-782" style="object-fit:contain;width:20px;height:20px"/></figure>
						<!-- /wp:image -->

						<!-- wp:paragraph {"align":"left","style":{"typography":{"fontSize":"17px","lineHeight":"1.82"}}} -->
						<p class="has-text-align-left" style="font-size:17px;line-height:1.82">
							Take 5 courses&nbsp;
						</p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->

					<!-- wp:group {"style":{"spacing":{"blockGap":"10px"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
					<div class="wp-block-group">
						<!-- wp:image {"id":782,"width":"20px","height":"20px","scale":"contain","sizeSlug":"full","linkDestination":"none","style":{"layout":{"selfStretch":"fixed","flexSize":"20px"}}} -->
						<figure class="wp-block-image size-full is-resized"><img src="' . esc_url( $online_course_fse_tick ) . '" alt="" class="wp-image-782" style="object-fit:contain;width:20px;height:20px"/></figure>
						<!-- /wp:image -->

						<!-- wp:paragraph {"align":"left","style":{"typography":{"fontSize":"17px","lineHeight":"1.82"}}} -->
						<p class="has-text-align-left" style="font-size:17px;line-height:1.82">
							Extra support from teachers&nbsp;
						</p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->

					<!-- wp:group {"style":{"spacing":{"blockGap":"10px"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
					<div class="wp-block-group">
						<!-- wp:image {"id":782,"width":"20px","height":"20px","scale":"contain","sizeSlug":"full","linkDestination":"none","style":{"layout":{"selfStretch":"fixed","flexSize":"20px"}}} -->
						<figure class="wp-block-image size-full is-resized"><img src="' . esc_url( $online_course_fse_tick ) . '" alt="" class="wp-image-782" style="object-fit:contain;width:20px;height:20px"/></figure>
						<!-- /wp:image -->

						<!-- wp:paragraph {"align":"left","style":{"typography":{"fontSize":"17px","lineHeight":"1.82"}}} -->
						<p class="has-text-align-left" style="font-size:17px;line-height:1.82">
							Career advice session&nbsp;
						</p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->

					<!-- wp:group {"style":{"spacing":{"blockGap":"10px"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
					<div class="wp-block-group">
						<!-- wp:image {"id":782,"width":"20px","height":"20px","scale":"contain","sizeSlug":"full","linkDestination":"none","style":{"layout":{"selfStretch":"fixed","flexSize":"20px"}}} -->
						<figure class="wp-block-image size-full is-resized"><img src="' . esc_url( $online_course_fse_tick ) . '" alt="" class="wp-image-782" style="object-fit:contain;width:20px;height:20px"/></figure>
						<!-- /wp:image -->

						<!-- wp:paragraph {"align":"left","style":{"typography":{"fontSize":"17px","lineHeight":"1.82"}}} -->
						<p class="has-text-align-left" style="font-size:17px;line-height:1.82">
							1 year access
						</p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:group -->

			<!-- wp:columns {"style":{"spacing":{"padding":{"top":"33px"},"blockGap":{"top":"24px","left":"0px"}},"border":{"top":{"color":"#e6e6e6","width":"1px"}}}} -->
			<div class="wp-block-columns" style="border-top-color:#e6e6e6;border-top-width:1px;padding-top:33px">
				<!-- wp:column -->
				<div class="wp-block-column">
					<!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
					<div class="wp-block-group">
						<!-- wp:group {"style":{"spacing":{"blockGap":"4px"}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"left"}} -->
						<div class="wp-block-group">
							<!-- wp:heading {"level":4,"style":{"typography":{"fontSize":"28px","fontStyle":"normal","fontWeight":"700"}}} -->
							<h4 class="wp-block-heading" style="font-size:28px;font-style:normal;font-weight:700">
								$250.0
							</h4>
							<!-- /wp:heading -->

							<!-- wp:paragraph {"align":"left","style":{"typography":{"fontSize":"20px","lineHeight":"2"}}} -->
							<p class="has-text-align-left" style="font-size:20px;line-height:2">
								/mo
							</p>
							<!-- /wp:paragraph -->
						</div>
						<!-- /wp:group -->

						<!-- wp:buttons {"style":{"spacing":{"blockGap":{"top":"16px","left":"16px"}}},"layout":{"type":"flex","justifyContent":"left","verticalAlignment":"center"}} -->
						<div class="wp-block-buttons">
							<!-- wp:button {"backgroundColor":"background-1","textColor":"primary","style":{"typography":{"fontSize":"17px","textTransform":"capitalize","fontStyle":"normal","fontWeight":"600"},"border":{"width":"2px"}},"borderColor":"primary"} -->
							<div class="wp-block-button">
								<a class="wp-block-button__link has-primary-color has-background-1-background-color has-text-color has-background has-border-color has-primary-border-color has-custom-font-size wp-element-button" style="border-width:2px;font-size:17px;font-style:normal;font-weight:600;text-transform:capitalize">
									' . $get_started_text . '
								</a>
							</div>
							<!-- /wp:button -->
						</div>
						<!-- /wp:buttons -->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:column -->
			</div>
			<!-- /wp:columns -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
',
);
