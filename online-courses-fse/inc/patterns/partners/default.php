<?php
/**
 * Pattern
 *
 * @author Themegrill
 * @package online-courses-fse
 * @since 1.0.2
 *
 * slug: partners
 * title: Partners
 * categories: OnlineCourseFSE
 * keywords: partners, teachers, team
 */

use OnlineCoursesFSE\Assets_Manager;

$online_course_fse_airbnb    = Assets_Manager::get_image_url( 'airbnb.png' );
$online_course_fse_celanese  = Assets_Manager::get_image_url( 'celanese.png' );
$online_course_fse_google    = Assets_Manager::get_image_url( 'google.png' );
$online_course_fse_microsoft = Assets_Manager::get_image_url( 'microsoft.png' );
$online_course_fse_meta      = Assets_Manager::get_image_url( 'meta.png' );
$online_course_fse_elastic   = Assets_Manager::get_image_url( 'elastic.png' );

$our_partners_tag_text = esc_html__( 'Our Partners', 'online-courses-fse' );

return array(
	'title'      => __( 'Partners', 'online-courses-fse' ),
	'categories' => array( 'online-courses-fse-partners' ),
	'keywords'   => array( 'partners', 'teachers', 'team' ),
	'content'    => '

<!-- wp:group {"metadata":{"name":"Partners"},"align":"full","style":{"spacing":{"padding":{"top":"120px","bottom":"120px"},"blockGap":"60px"}},"backgroundColor":"background-2","layout":{"type":"constrained","contentSize":"1320px"}} -->
<div class="wp-block-group alignfull has-background-2-background-color has-background" style="padding-top:120px;padding-bottom:120px">
	<!-- wp:columns {"verticalAlignment":"center","style":{"spacing":{"blockGap":{"top":"60px","left":"20%"}}}} -->
	<div class="wp-block-columns are-vertically-aligned-center">
		<!-- wp:column {"verticalAlignment":"center","width":"30%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:30%">
			<!-- wp:group {"style":{"layout":{"selfStretch":"fit","flexSize":null},"spacing":{"blockGap":"8px","padding":{"bottom":"24px"}}},"layout":{"type":"flex","orientation":"vertical"}} -->
			<div class="wp-block-group" style="padding-bottom:24px">
				<!-- wp:group {"style":{"spacing":{"padding":{"top":"9px","bottom":"9px","left":"20px","right":"20px"}},"border":{"radius":"20px"}},"backgroundColor":"background-3","textColor":"accent-2","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-accent-2-color has-background-3-background-color has-text-color has-background" style="border-radius:20px;padding-top:9px;padding-right:20px;padding-bottom:9px;padding-left:20px"><!-- wp:heading {"textAlign":"center","level":6,"style":{"typography":{"textTransform":"uppercase","fontWeight":"700","letterSpacing":"1.4px","fontStyle":"normal"}},"textColor":"primary"} -->
<h6 class="wp-block-heading has-text-align-center has-primary-color has-text-color" style="font-style:normal;font-weight:700;letter-spacing:1.4px;text-transform:uppercase">
						' . $our_partners_tag_text . '
					</h6>
<!-- /wp:heading --></div>
<!-- /wp:group -->

				<!-- wp:heading {"textAlign":"left","textColor":"heading"} -->
				<h2 class="wp-block-heading has-text-align-left has-heading-color has-text-color">
					Trusted By Leading Companies
				</h2>
				<!-- /wp:heading -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center","width":"50%","style":{"spacing":{"blockGap":"24px"}}} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:50%">
			<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"24px"}}}} -->
			<div class="wp-block-columns">
				<!-- wp:column {"verticalAlignment":"center","width":"33.33%","style":{"spacing":{"padding":{"top":"20px","bottom":"20px","left":"29px","right":"29px"}},"border":{"radius":"10px","width":"1px"}},"backgroundColor":"button-1","borderColor":"background-3"} -->
				<div class="wp-block-column is-vertically-aligned-center has-border-color has-background-3-border-color has-button-1-background-color has-background" style="border-width:1px;border-radius:10px;padding-top:20px;padding-right:29px;padding-bottom:20px;padding-left:29px;flex-basis:33.33%">
					<!-- wp:image {"sizeSlug":"full","linkDestination":"none","align":"center"} -->
					<figure class="wp-block-image aligncenter size-full"><img src="' . esc_url( $online_course_fse_celanese ) . '" alt=""/></figure>
					<!-- /wp:image -->
				</div>
				<!-- /wp:column -->

				<!-- wp:column {"verticalAlignment":"center","width":"33.33%","style":{"spacing":{"padding":{"top":"20px","bottom":"20px","left":"29px","right":"29px"}},"border":{"radius":"10px","width":"1px"}},"backgroundColor":"button-1","borderColor":"background-3"} -->
				<div class="wp-block-column is-vertically-aligned-center has-border-color has-background-3-border-color has-button-1-background-color has-background" style="border-width:1px;border-radius:10px;padding-top:20px;padding-right:29px;padding-bottom:20px;padding-left:29px;flex-basis:33.33%">
					<!-- wp:image {"sizeSlug":"full","linkDestination":"none","align":"center"} -->
					<figure class="wp-block-image aligncenter size-full"><img src="' . esc_url( $online_course_fse_microsoft ) . '" alt=""/></figure>
					<!-- /wp:image -->
				</div>
				<!-- /wp:column -->

				<!-- wp:column {"verticalAlignment":"center","width":"33.33%","style":{"spacing":{"padding":{"top":"20px","bottom":"20px","left":"29px","right":"29px"}},"border":{"radius":"10px","width":"1px"}},"backgroundColor":"button-1","borderColor":"background-3"} -->
				<div class="wp-block-column is-vertically-aligned-center has-border-color has-background-3-border-color has-button-1-background-color has-background" style="border-width:1px;border-radius:10px;padding-top:20px;padding-right:29px;padding-bottom:20px;padding-left:29px;flex-basis:33.33%">
					<!-- wp:image {"sizeSlug":"full","linkDestination":"none","align":"center"} -->
					<figure class="wp-block-image aligncenter size-full"><img src="' . esc_url( $online_course_fse_meta ) . '" alt=""/></figure>
					<!-- /wp:image -->
				</div>
				<!-- /wp:column -->
			</div>
			<!-- /wp:columns -->

			<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"24px"},"margin":{"top":"24px"}}}} -->
			<div class="wp-block-columns" style="margin-top:24px">
				<!-- wp:column {"verticalAlignment":"center","width":"33.33%","style":{"spacing":{"padding":{"top":"20px","bottom":"20px","left":"29px","right":"29px"}},"border":{"radius":"10px","width":"1px"}},"backgroundColor":"button-1","borderColor":"background-3"} -->
				<div class="wp-block-column is-vertically-aligned-center has-border-color has-background-3-border-color has-button-1-background-color has-background" style="border-width:1px;border-radius:10px;padding-top:20px;padding-right:29px;padding-bottom:20px;padding-left:29px;flex-basis:33.33%">
					<!-- wp:image {"sizeSlug":"full","linkDestination":"none","align":"center"} -->
					<figure class="wp-block-image aligncenter size-full"><img src="' . esc_url( $online_course_fse_elastic ) . '" alt=""/></figure>
					<!-- /wp:image -->
				</div>
				<!-- /wp:column -->

				<!-- wp:column {"verticalAlignment":"center","width":"33.33%","style":{"spacing":{"padding":{"top":"20px","bottom":"20px","left":"29px","right":"29px"}},"border":{"radius":"10px","width":"1px"}},"backgroundColor":"button-1","borderColor":"background-3"} -->
				<div class="wp-block-column is-vertically-aligned-center has-border-color has-background-3-border-color has-button-1-background-color has-background" style="border-width:1px;border-radius:10px;padding-top:20px;padding-right:29px;padding-bottom:20px;padding-left:29px;flex-basis:33.33%">
					<!-- wp:image {"sizeSlug":"full","linkDestination":"none","align":"center"} -->
					<figure class="wp-block-image aligncenter size-full"><img src="' . esc_url( $online_course_fse_google ) . '" alt="" /></figure>
					<!-- /wp:image -->
				</div>
				<!-- /wp:column -->

				<!-- wp:column {"verticalAlignment":"center","width":"33.33%","style":{"spacing":{"padding":{"top":"20px","bottom":"20px","left":"29px","right":"29px"}},"border":{"radius":"10px","width":"1px"}},"backgroundColor":"button-1","borderColor":"background-3"} -->
				<div class="wp-block-column is-vertically-aligned-center has-border-color has-background-3-border-color has-button-1-background-color has-background" style="border-width:1px;border-radius:10px;padding-top:20px;padding-right:29px;padding-bottom:20px;padding-left:29px;flex-basis:33.33%">
					<!-- wp:image {"sizeSlug":"full","linkDestination":"none","align":"center"} -->
					<figure class="wp-block-image aligncenter size-full"><img src="' . esc_url( $online_course_fse_airbnb ) . '" alt=""/></figure>
					<!-- /wp:image -->
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
