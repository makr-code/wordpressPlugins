<?php
/**
 * Pattern
 *
 * @author Themegrill
 * @package online-courses-fse
 * @since 1.0.2
 *
 * slug: single-post
 * title: Single Post
 * categories: OnlineCourseFSE
 * keywords: single-post, teachers, team
 */

use OnlineCoursesFSE\Assets_Manager;

$online_course_fse_cta = Assets_Manager::get_image_url( 'cta-404.jpg' );

$comments_heading_text = esc_html__( 'Comments', 'online-courses-fse' );
$more_posts_text       = esc_html__( 'More posts', 'online-courses-fse' );
$no_posts_text         = esc_html__( 'No Posts were found', 'online-courses-fse' );

return array(
	'title'      => __( 'Single Post', 'online-courses-fse' ),
	'categories' => array( 'online-courses-fse-home' ),
	'keywords'   => array( 'single-post', 'teachers', 'team' ),
	'content'    => '
<!-- wp:cover {"url":"' . esc_url( $online_course_fse_cta ) . '","id":948,"dimRatio":50,"customOverlayColor":"#0c3835","isUserOverlayColor":false,"sizeSlug":"large","style":{"spacing":{"padding":{"top":"100px","bottom":"100px"},"blockGap":"60px"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-cover" style="padding-top:100px;padding-bottom:100px">
	<img class="wp-block-cover__image-background wp-image-948 size-large" alt="" src="' . esc_url( $online_course_fse_cta ) . '" data-object-fit="cover"/>
	<span aria-hidden="true" class="wp-block-cover__background has-background-dim" style="background-color:#0c3835"></span>
	<div class="wp-block-cover__inner-container">
		<!-- wp:group {"style":{"spacing":{"blockGap":"2.25rem"}},"layout":{"type":"constrained"}} -->
		<div class="wp-block-group">
       <!-- wp:post-title {"textAlign":"center","level":2,"style":{"typography":{"fontSize":"56px","lineHeight":"1.5","fontStyle":"normal","fontWeight":"800","textTransform":"capitalize"},"color":{"text":"var(--wp--preset--color--base)"}}} /-->
			<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"center"}} -->
			<div class="wp-block-group">
				<!-- wp:post-terms {"term":"category","style":{"elements":{"link":{"color":{"text":"var:preset|color|background-1"}}},"typography":{"fontSize":"17px","lineHeight":"1.82","textTransform":"capitalize"}},"textColor":"background-1"} /-->

				<!-- wp:post-date {"metadata":{"bindings":{"datetime":{"source":"core/post-data","args":{"field":"date"}}}},"style":{"elements":{"link":{"color":{"text":"var:preset|color|background-1"}}},"typography":{"fontSize":"17px","lineHeight":"1.82","textTransform":"capitalize","fontStyle":"normal","fontWeight":"600"}},"textColor":"background-1"} /-->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->

		<!-- wp:post-featured-image {"aspectRatio":"16/9","align":"wide","style":{"border":{"radius":"16px"}}} /-->
	</div>
</div>
<!-- /wp:cover -->

<!-- wp:group {"tagName":"main","backgroundColor":"background-1","layout":{"type":"constrained"}} -->
<main class="wp-block-group has-background-1-background-color has-background">
	<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"80px","bottom":"80px"},"blockGap":"var:preset|spacing|50"}},"backgroundColor":"background-3","layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignfull has-background-3-background-color has-background" style="padding-top:80px;padding-bottom:80px">
		<!-- wp:post-content {"align":"full","textColor":"accent-3","style":{"elements":{"link":{"color":{"text":"var:preset|color|accent-3"}}},"typography":{"fontSize":"19px","lineHeight":"1.84"}}},"layout":{"type":"constrained"}} /-->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"align":"wide","style":{"spacing":{"margin":{"top":"16px","bottom":"16px"}}},"layout":{"type":"constrained","contentSize":"1320px"}} -->
	<div class="wp-block-group alignwide" style="margin-top:16px;margin-bottom:16px"><!-- wp:group {"tagName":"nav","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"},"ariaLabel":"Post navigation"} -->
	<nav class="wp-block-group" aria-label="Post navigation"><!-- wp:post-navigation-link {"type":"previous","showTitle":true,"arrow":"arrow","style":{"typography":{"fontSize":"17px","fontStyle":"normal","fontWeight":"700","lineHeight":"1.6","textTransform":"capitalize"}},"textColor":"accent-3"} /-->

	<!-- wp:post-navigation-link {"showTitle":true,"arrow":"arrow","style":{"typography":{"fontSize":"17px","fontStyle":"normal","fontWeight":"700","lineHeight":"1.6","textTransform":"capitalize"}},"textColor":"accent-3"} /--></nav>
	<!-- /wp:group --></div>
	<!-- /wp:group -->

	<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"80px","bottom":"80px"},"margin":{"top":"0","bottom":"0"}}},"backgroundColor":"background-3","layout":{"type":"constrained","contentSize":"1320px"}} -->
	<div class="wp-block-group alignfull has-background-3-background-color has-background" style="padding-top:80px;padding-bottom:80px;margin-top:0;margin-bottom:0">
		<!-- wp:comments {"className":"wp-block-comments-query-loop","textColor":"heading","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"blockGap":"var:preset|spacing|50"},"elements":{"link":{"color":{"text":"var:preset|color|heading"}}},"typography":{"fontSize":"17px"}}} -->
		<div class="wp-block-comments wp-block-comments-query-loop has-heading-color has-text-color has-link-color" style="margin-top:0;margin-bottom:0;font-size:17px">
			<!-- wp:comments-title {"level":2,"textColor":"heading","style":{"elements":{"link":{"color":{"text":"var:preset|color|heading"}}},"typography":{"fontSize":"42px","lineHeight":"1.24","fontStyle":"normal","fontWeight":"800","textTransform":"capitalize"},"spacing":{"margin":{"bottom":"40px"}}},"fontFamily":"manrope"} /-->

			<!-- wp:comment-template {"style":{"typography":{"fontSize":"17px","lineHeight":"1.82","fontStyle":"normal","fontWeight":"400"},"spacing":{"blockGap":"var:preset|spacing|50"}}} -->
			<!-- wp:group {"style":{"spacing":{"margin":{"top":"0","bottom":"0"},"blockGap":"var:preset|spacing|40"}}} -->
			<div class="wp-block-group" style="margin-top:0;margin-bottom:0">
				<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"}} -->
				<div class="wp-block-group">
					<!-- wp:avatar {"size":50} /-->

					<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|40"}}} -->
					<div class="wp-block-group">
						<!-- wp:group {"style":{"spacing":{"blockGap":"4px"}},"layout":{"type":"flex","orientation":"vertical"}} -->
						<div class="wp-block-group">
							<!-- wp:comment-author-name {"style":{"typography":{"fontSize":"17px","fontStyle":"normal","fontWeight":"700"}}} /-->

							<!-- wp:comment-date /-->
						</div>
						<!-- /wp:group -->

						<!-- wp:comment-content {"textColor":"accent-3","style":{"spacing":{"padding":{"left":"var:preset|spacing|30","right":"var:preset|spacing|30","top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}},"border":{"radius":"8px"},"elements":{"link":{"color":{"text":"var:preset|color|accent-3"}}},"typography":{"fontStyle":"normal","fontWeight":"400","fontSize":"19px","lineHeight":"1.84"}}},"backgroundColor":"background-1"} /-->

						<!-- wp:group {"className":"is-style-default","style":{"color":{"text":"#006b48"},"elements":{"link":{"color":{"text":"#006b48"},":hover":{"color":{"text":"#064a35"}}}}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
						<div class="wp-block-group is-style-default has-text-color has-link-color" style="color:#006b48">
							<!-- wp:comment-edit-link /-->

							<!-- wp:comment-reply-link /-->
						</div>
						<!-- /wp:group -->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:group -->
			<!-- /wp:comment-template -->

			<!-- wp:comments-pagination {"textColor":"heading","style":{"elements":{"link":{"color":{"text":"var:preset|color|heading"}}},"spacing":{"margin":{"top":"40px"}}},"layout":{"type":"flex","justifyContent":"space-between","verticalAlignment":"center"}} -->
			<!-- wp:comments-pagination-previous {"style":{"typography":{"fontStyle":"normal","fontWeight":"700","fontSize":"19px","textTransform":"capitalize"}},"fontFamily":"manrope"} /-->

			<!-- wp:comments-pagination-next {"style":{"typography":{"fontSize":"19px","fontStyle":"normal","fontWeight":"700","textTransform":"capitalize"}},"fontFamily":"manrope"} /-->
			<!-- /wp:comments-pagination -->

			<!-- wp:post-comments-form {"textColor":"heading","style":{"elements":{"link":{"color":{"text":"var:preset|color|primary"},":hover":{"color":{"text":"var:preset|color|primary"}}}},"typography":{"fontStyle":"normal","fontWeight":"600","fontSize":"42px","textTransform":"capitalize","lineHeight":"1.24"},"spacing":{"margin":{"top":"60px"}}}} /-->
		</div>
		<!-- /wp:comments -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"80px","bottom":"80px"},"blockGap":"var:preset|spacing|50"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide" style="padding-top:80px;padding-bottom:80px">
		<!-- wp:heading {"align":"wide","style":{"typography":{"textTransform":"uppercase","fontStyle":"normal","fontWeight":"700","letterSpacing":"1.4px"},"spacing":{"margin":{"bottom":"30px"}}},"fontSize":"small"} -->
		<h2 class="wp-block-heading alignwide has-small-font-size" style="margin-bottom:30px;font-style:normal;font-weight:700;letter-spacing:1.4px;text-transform:uppercase">
			' . $more_posts_text . '
		</h2>
		<!-- /wp:heading -->

		<!-- wp:query {"queryId":114,"query":{"perPage":4,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false,"taxQuery":null,"parents":[]},"align":"wide","layout":{"type":"default"}} -->
		<div class="wp-block-query alignwide">
			<!-- wp:post-template {"align":"full","textColor":"heading","style":{"spacing":{"blockGap":"0"},"typography":{"fontSize":"20px","fontStyle":"normal","fontWeight":"500","lineHeight":"1.6"},"elements":{"link":{"color":{"text":"var:preset|color|heading"}}}},"fontFamily":"manrope","layout":{"type":"default"}} -->
			<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}},"border":{"bottom":{"color":"#f2f2f2","width":"1px"},"top":[],"right":[],"left":[]}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center","justifyContent":"space-between"}} -->
			<div class="wp-block-group alignfull" style="border-bottom-color:#f2f2f2;border-bottom-width:1px;padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30)">
				<!-- wp:post-title {"level":3,"isLink":true,"fontSize":"large"} /-->

				<!-- wp:post-date {"textAlign":"right","isLink":true,"metadata":{"bindings":{"datetime":{"source":"core/post-data","args":{"field":"date"}}}}} /-->
			</div>
			<!-- /wp:group -->
			<!-- /wp:post-template -->

			<!-- wp:query-no-results -->
			<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"40px","bottom":"40px"},"margin":{"top":"80px","bottom":"80px"}},"border":{"radius":"16px"}},"backgroundColor":"background-3","layout":{"type":"constrained","contentSize":"1320px"}} -->
			<div class="wp-block-group alignwide has-background-3-background-color has-background" style="border-radius:16px;margin-top:80px;margin-bottom:80px;padding-top:40px;padding-bottom:40px">
				<!-- wp:paragraph {"align":"center","textColor":"heading","style":{"elements":{"link":{"color":{"text":"var:preset|color|heading"}}},"typography":{"fontSize":"19px","lineHeight":"1.84","textTransform":"none","fontStyle":"normal","fontWeight":"400"}}} -->
				<p class="has-text-align-center has-heading-color has-text-color has-link-color" style="font-size:19px;font-style:normal;font-weight:400;line-height:1.84;text-transform:none">
					' . $no_posts_text . '
				</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
			<!-- /wp:query-no-results -->
		</div>
		<!-- /wp:query -->
	</div>
	<!-- /wp:group -->
</main>
<!-- /wp:group -->
',
);