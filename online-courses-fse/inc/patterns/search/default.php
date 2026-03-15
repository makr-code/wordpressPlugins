<?php
/**
 * Pattern
 *
 * @author Themegrill
 * @package online-courses-fse
 * @since 1.0.2
 *
 * slug: search
 * title: Search Page
 * categories: OnlineCourseFSE
 * keywords: search, teachers, team
 */

use OnlineCoursesFSE\Assets_Manager;

$online_course_fse_cta_scaled = Assets_Manager::get_image_url( 'cta.jpg' );

$search_placeholder_text = esc_html__( 'Type here...', 'online-courses-fse' );
$search_button_text      = esc_html__( 'Search', 'online-courses-fse' );
$more_posts_text         = esc_html__( 'More posts', 'online-courses-fse' );
$no_results_text         = esc_html__( 'Sorry, but nothing was found. Please try a search with different keywords.', 'online-courses-fse' );

return array(
	'title'      => __( 'Search Page', 'online-courses-fse' ),
	'categories' => array( 'online-courses-fse-search' ),
	'keywords'   => array( 'search', 'teachers', 'team' ),
	'content'    => '
<!-- wp:group {"tagName":"main","style":{"spacing":{"margin":{"top":"0"},"blockGap":"0"}},"layout":{"type":"constrained"}} -->
<main class="wp-block-group" style="margin-top:0"><!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"100px","bottom":"100px"},"blockGap":"50px"},"background":{"backgroundImage":{"url":"' . esc_url( $online_course_fse_cta_scaled ) . '","id":948,"source":"file","title":"cta"},"backgroundSize":"cover"}},"layout":{"type":"constrained","contentSize":"1320px","justifyContent":"center"}} -->
<div class="wp-block-group alignfull" style="padding-top:100px;padding-bottom:100px"><!-- wp:query-title {"type":"search","textAlign":"center","style":{"typography":{"fontSize":"42px","lineHeight":"1.24","fontStyle":"normal","fontWeight":"800","textTransform":"capitalize"},"elements":{"link":{"color":{"text":"var:preset|color|background-1"}}}},"textColor":"background-1"} /-->

<!-- wp:search {"label":"' . $search_button_text . '","showLabel":false,"placeholder":"' . $search_placeholder_text . '","width":588,"widthUnit":"px","buttonText":"Search","align":"center","backgroundColor":"primary","style":{"typography":{"fontSize":"17px","fontStyle":"normal","fontWeight":"600"},"border":{"radius":"500px"},"elements":{"link":{"color":{"text":"var:preset|color|background-1"}}}},"textColor":"background-1"} /--></div>
<!-- /wp:group -->

<!-- wp:query {"queryId":37,"query":{"perPage":10,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":true,"taxQuery":null,"parents":[]},"align":"full","layout":{"type":"default"}} -->
<div class="wp-block-query alignfull"><!-- wp:post-template {"align":"full","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"30px","bottom":"30px"}}},"backgroundColor":"background-3","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-background-3-background-color has-background" style="padding-top:30px;padding-bottom:30px"><!-- wp:group {"style":{"spacing":{"padding":{"top":"30px","left":"30px","right":"30px","bottom":"60px"},"blockGap":"10px"},"border":{"radius":"16px","width":"1px"}},"backgroundColor":"background-1","borderColor":"accent-6","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-border-color has-accent-6-border-color has-background-1-background-color has-background" style="border-width:1px;border-radius:16px;padding-top:30px;padding-right:30px;padding-bottom:60px;padding-left:30px"><!-- wp:post-featured-image {"isLink":true,"aspectRatio":"3/2","style":{"border":{"radius":"8px"}}} /-->

<!-- wp:group {"style":{"spacing":{"blockGap":"24px"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group"><!-- wp:group {"style":{"spacing":{"blockGap":"6px"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group"><!-- wp:post-date {"isLink":true,"metadata":{"bindings":{"datetime":{"source":"core/post-data","args":{"field":"date"}}}},"textColor":"accent-3","style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}},"elements":{"link":{"color":{"text":"var:preset|color|accent-3"}}}},"fontSize":"small"} /-->

<!-- wp:post-title {"isLink":true,"style":{"typography":{"fontSize":"26px","fontStyle":"normal","fontWeight":"700","lineHeight":"1.46","textTransform":"capitalize"}},"fontFamily":"manrope"} /--></div>
<!-- /wp:group -->

<!-- wp:post-excerpt {"style":{"typography":{"fontSize":"17px","lineHeight":"1.82","fontStyle":"normal","fontWeight":"500"}},"textColor":"accent-3"} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
<!-- /wp:post-template -->

<!-- wp:query-no-results -->
<!-- wp:group {"style":{"spacing":{"padding":{"top":"5px","bottom":"5px"},"margin":{"top":"80px","bottom":"80px"}},"border":{"radius":"16px"}},"backgroundColor":"background-3","layout":{"type":"constrained","contentSize":"1320px"}} -->
<div class="wp-block-group has-background-3-background-color has-background" style="border-radius:16px;margin-top:80px;margin-bottom:80px;padding-top:5px;padding-bottom:5px"><!-- wp:paragraph {"align":"center","textColor":"heading","style":{"elements":{"link":{"color":{"text":"var:preset|color|heading"}}},"typography":{"fontSize":"19px","lineHeight":"1.84","textTransform":"none","fontStyle":"normal","fontWeight":"400"}}} -->
<p class="has-text-align-center has-heading-color has-text-color has-link-color" style="font-size:19px;font-style:normal;font-weight:400;line-height:1.84;text-transform:none">
		' . $no_results_text . '
	</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
<!-- /wp:query-no-results -->

<!-- wp:group {"align":"wide","layout":{"type":"constrained","contentSize":"1320px"}} -->
<div class="wp-block-group alignwide"><!-- wp:query-pagination {"paginationArrow":"arrow","textColor":"accent-3","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"40px","right":"40px"},"margin":{"top":"60px","bottom":"0"}},"border":{"radius":"16px","width":"1px"},"elements":{"link":{"color":{"text":"var:preset|color|heading"},":hover":{"color":{"text":"var:preset|color|heading"}}}}},"backgroundColor":"background-1","borderColor":"accent-6","layout":{"type":"flex","justifyContent":"space-between"}} -->
<!-- wp:query-pagination-previous {"style":{"typography":{"fontSize":"17px","fontStyle":"normal","fontWeight":"700","lineHeight":"1.6","textTransform":"capitalize"}}} /-->

<!-- wp:query-pagination-numbers {"style":{"typography":{"fontSize":"20px","fontStyle":"normal","fontWeight":"700","lineHeight":"1.6","textTransform":"capitalize"}},"fontFamily":"manrope"} /-->

<!-- wp:query-pagination-next {"style":{"typography":{"fontSize":"17px","fontStyle":"normal","fontWeight":"700","lineHeight":"1.6","textTransform":"capitalize"}},"fontFamily":"manrope"} /-->
<!-- /wp:query-pagination --></div>
<!-- /wp:group --></div>
<!-- /wp:query -->

<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"80px","bottom":"80px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide" style="padding-top:80px;padding-bottom:80px"><!-- wp:heading {"align":"wide","style":{"typography":{"textTransform":"uppercase","fontStyle":"normal","fontWeight":"700","letterSpacing":"1.4px"}},"textColor":"heading","fontSize":"small"} -->
<h2 class="wp-block-heading alignwide has-heading-color has-text-color has-small-font-size" style="font-style:normal;font-weight:700;letter-spacing:1.4px;text-transform:uppercase">
			' . $more_posts_text . '
		</h2>
<!-- /wp:heading -->

<!-- wp:query {"queryId":114,"query":{"perPage":4,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false,"taxQuery":null,"parents":[]},"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-query alignwide"><!-- wp:post-template {"align":"full","textColor":"heading","style":{"spacing":{"blockGap":"0"},"typography":{"fontSize":"20px","fontStyle":"normal","fontWeight":"500","lineHeight":"1.6"},"elements":{"link":{"color":{"text":"var:preset|color|heading"}}}},"fontFamily":"manrope","layout":{"type":"default"}} -->
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}},"border":{"bottom":{"color":"var:preset|color|accent-6","width":"1px"},"top":[],"right":[],"left":[]}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center","justifyContent":"space-between"}} -->
<div class="wp-block-group alignfull" style="border-bottom-color:var(--wp--preset--color--accent-6);border-bottom-width:1px;padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30)"><!-- wp:post-title {"level":3,"isLink":true,"fontSize":"large"} /-->

<!-- wp:post-date {"textAlign":"right","isLink":true,"metadata":{"bindings":{"datetime":{"source":"core/post-data","args":{"field":"date"}}}}} /--></div>
<!-- /wp:group -->
<!-- /wp:post-template --></div>
<!-- /wp:query --></div>
<!-- /wp:group --></main>
<!-- /wp:group -->
',
);