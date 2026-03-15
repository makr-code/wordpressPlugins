<?php
/**
 * Pattern
 *
 * @author Themegrill
 * @package online-courses-fse
 * @since 1.0.2
 *
 * slug: archive
 * title: Archive Page
 * categories: OnlineCourseFSE
 * keywords: archive, teachers, team
 */

use OnlineCoursesFSE\Assets_Manager;

$online_course_fse_cta_scaled = Assets_Manager::get_image_url( 'cta.jpg' );

$no_results_text = esc_html__( 'Sorry, but nothing was found. Please try a search with different keywords.', 'online-courses-fse' );

return array(
	'title'      => __( 'Archive Page', 'online-courses-fse' ),
	'categories' => array( 'online-courses-fse-archive' ),
	'keywords'   => array( 'archive', 'teachers', 'team' ),
	'content'    => '
<!-- wp:group {"tagName":"main","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"constrained","contentSize":"1320px"}} -->
<main class="wp-block-group"><!-- wp:cover {"url":"' . esc_url( $online_course_fse_cta_scaled ) . '","dimRatio":0,"align":"full","style":{"spacing":{"padding":{"top":"80px","bottom":"80px"}}},"layout":{"type":"constrained","contentSize":"1320px"}} -->
<div class="wp-block-cover alignfull" style="padding-top:80px;padding-bottom:80px"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span><img class="wp-block-cover__image-background" alt="" src="' . esc_url( $online_course_fse_cta_scaled ) . '" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:query-title {"type":"archive","textAlign":"center","style":{"typography":{"fontSize":"42px","lineHeight":"1.24","fontStyle":"normal","fontWeight":"800","textTransform":"capitalize"},"color":{"text":"#ffffff"},"elements":{"link":{"color":{"text":"#ffffff"}}}},"fontFamily":"manrope"} /-->

<!-- wp:term-description {"textAlign":"center","style":{"typography":{"fontSize":"19px","lineHeight":"1.84"},"color":{"text":"#ffffff"},"elements":{"link":{"color":{"text":"#ffffff"}}}}} /--></div></div>
<!-- /wp:cover -->

<!-- wp:query {"queryId":37,"query":{"perPage":10,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":true,"taxQuery":null,"parents":[]},"align":"full","layout":{"type":"default"}} -->
<div class="wp-block-query alignfull"><!-- wp:post-template {"align":"full","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"30px","bottom":"30px"}}},"backgroundColor":"background-3","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-background-3-background-color has-background" style="padding-top:30px;padding-bottom:30px"><!-- wp:group {"style":{"spacing":{"padding":{"top":"30px","left":"30px","right":"30px","bottom":"60px"},"blockGap":"10px"},"border":{"radius":"16px","width":"1px"}},"backgroundColor":"background-1","borderColor":"accent-6","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-border-color has-accent-6-border-color has-background-1-background-color has-background" style="border-width:1px;border-radius:16px;padding-top:30px;padding-right:30px;padding-bottom:60px;padding-left:30px"><!-- wp:post-featured-image {"isLink":true,"aspectRatio":"3/2","style":{"border":{"radius":"8px"}}} /-->

<!-- wp:group {"style":{"spacing":{"blockGap":"24px"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group"><!-- wp:group {"style":{"spacing":{"blockGap":"0px"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group"><!-- wp:post-date {"isLink":true,"style":{"spacing":{"margin":{"top":"0","bottom":"0"}}},"textColor":"accent-3","fontSize":"small"} /-->

<!-- wp:post-title {"isLink":true,"style":{"typography":{"fontSize":"26px","fontStyle":"normal","fontWeight":"700","lineHeight":"1.46","textTransform":"capitalize"},"spacing":{"margin":{"top":"0","bottom":"0"}}},"fontFamily":"manrope"} /--></div>
<!-- /wp:group -->

<!-- wp:post-excerpt {"style":{"typography":{"fontSize":"17px","lineHeight":"1.82","fontStyle":"normal","fontWeight":"500"}},"textColor":"accent-3"} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
<!-- /wp:post-template -->

<!-- wp:query-no-results -->
<!-- wp:group {"style":{"spacing":{"padding":{"top":"5px","bottom":"5px"},"margin":{"top":"80px","bottom":"80px"}},"border":{"radius":"16px"}},"backgroundColor":"background-3","layout":{"type":"constrained","contentSize":"1320px"}} -->
<div class="wp-block-group has-background-3-background-color has-background" style="border-radius:16px;margin-top:80px;margin-bottom:80px;padding-top:5px;padding-bottom:5px"><!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"19px","lineHeight":"1.84","textTransform":"none","fontStyle":"normal","fontWeight":"400"}},"textColor":"heading"} -->
<p class="has-text-align-center has-heading-color has-text-color" style="font-size:19px;font-style:normal;font-weight:400;line-height:1.84;text-transform:none">
	' . $no_results_text . '
	</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
<!-- /wp:query-no-results -->

<!-- wp:group {"align":"wide","layout":{"type":"constrained","contentSize":"1320px"}} -->
<div class="wp-block-group alignwide"><!-- wp:query-pagination {"paginationArrow":"arrow","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"40px","right":"40px"},"margin":{"top":"60px","bottom":"60px"}},"border":{"radius":"16px","width":"1px"}},"backgroundColor":"background-1","borderColor":"accent-6","textColor":"accent-3","layout":{"type":"flex","justifyContent":"space-between"}} -->
<!-- wp:query-pagination-previous {"style":{"typography":{"fontSize":"17px","fontStyle":"normal","fontWeight":"700","lineHeight":"1.6","textTransform":"capitalize"}}} /-->

<!-- wp:query-pagination-numbers {"style":{"typography":{"fontSize":"20px","fontStyle":"normal","fontWeight":"700","lineHeight":"1.6","textTransform":"capitalize"}},"fontFamily":"manrope"} /-->

<!-- wp:query-pagination-next {"style":{"typography":{"fontSize":"17px","fontStyle":"normal","fontWeight":"700","lineHeight":"1.6","textTransform":"capitalize"}},"fontFamily":"manrope"} /-->
<!-- /wp:query-pagination --></div>
<!-- /wp:group --></div>
<!-- /wp:query --></main>
<!-- /wp:group -->
',
);