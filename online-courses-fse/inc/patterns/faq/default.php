<?php
/**
 * Pattern
 *
 * @author Themegrill
 * @package online-courses-fse
 * @since 1.0.2
 *
 * slug: faq
 * title: FAQ
 * categories: OnlineCourseFSE
 * keywords: faq, questions, accordion
 */

$faqs_tag_text         = esc_html__( 'FAQS', 'online-courses-fse' );
$common_questions_text = esc_html__( 'Common Questions', 'online-courses-fse' );

return array(
	'title'      => __( 'FAQs', 'online-courses-fse' ),
	'categories' => array( 'online-courses-fse-faq' ),
	'keywords'   => array( 'FAQ', 'questions', 'accordion' ),
	'content'    => '

<!-- wp:group {"metadata":{"name":"FAQS"},"align":"full","style":{"spacing":{"padding":{"top":"120px","bottom":"120px"},"blockGap":"60px"}},"backgroundColor":"background-1","layout":{"type":"constrained","contentSize":"1320px"}} -->
<div class="wp-block-group alignfull has-background-1-background-color has-background" style="padding-top:120px;padding-bottom:120px"><!-- wp:group {"style":{"spacing":{"blockGap":"60px"}},"layout":{"type":"constrained","contentSize":"686px"}} -->
<div class="wp-block-group"><!-- wp:group {"style":{"spacing":{"padding":{"bottom":"10px"},"blockGap":"8px"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
<div class="wp-block-group" style="padding-bottom:10px"><!-- wp:group {"style":{"spacing":{"padding":{"top":"9px","bottom":"9px","left":"20px","right":"20px"}},"border":{"radius":"20px"}},"backgroundColor":"background-3","textColor":"accent-2","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-accent-2-color has-background-3-background-color has-text-color has-background" style="border-radius:20px;padding-top:9px;padding-right:20px;padding-bottom:9px;padding-left:20px"><!-- wp:heading {"textAlign":"center","level":6,"style":{"typography":{"letterSpacing":"1.4px"}},"textColor":"primary"} -->
<h6 class="wp-block-heading has-text-align-center has-primary-color has-text-color" style="letter-spacing:1.4px">' . $faqs_tag_text . '</h6>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:heading {"textAlign":"center","textColor":"heading"} -->
<h2 class="wp-block-heading has-text-align-center has-heading-color has-text-color">' . $common_questions_text . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","textColor":"accent-3"} -->
<p class="has-text-align-center has-accent-3-color has-text-color">Quick answers to questions you might have.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"blockGap":"60px"}},"layout":{"type":"constrained","contentSize":"1000px"}} -->
<div class="wp-block-group"><!-- wp:accordion {"style":{"spacing":{"blockGap":"28px"}}} -->
<div role="group" class="wp-block-accordion"><!-- wp:accordion-item {"style":{"border":{"radius":"12px"}},"backgroundColor":"background-2","fontSize":"extra-large"} -->
<div class="wp-block-accordion-item has-background-2-background-color has-background has-extra-large-font-size" style="border-radius:12px"><!-- wp:accordion-heading {"level":3,"backgroundColor":"background-2"} -->
<h3 class="wp-block-accordion-heading has-background-2-background-color has-background"><button class="wp-block-accordion-heading__toggle"><span class="wp-block-accordion-heading__toggle-title">01. How do I sign up for a course?</span><span class="wp-block-accordion-heading__toggle-icon" aria-hidden="true">+</span></button></h3>
<!-- /wp:accordion-heading -->

<!-- wp:accordion-panel {"backgroundColor":"background-2"} -->
<div role="region" class="wp-block-accordion-panel has-background-2-background-color has-background"><!-- wp:paragraph {"style":{"typography":{"fontSize":"17px","lineHeight":"1.8"}},"textColor":"accent-3"} -->
<p class="has-accent-3-color has-text-color" style="font-size:17px;line-height:1.8">Click on any course you like, then click "Join Now." Follow the simple steps to create your account and start learning right away.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:accordion-panel --></div>
<!-- /wp:accordion-item -->

<!-- wp:accordion-item {"style":{"border":{"radius":"12px"}},"backgroundColor":"background-2","fontSize":"extra-large"} -->
<div class="wp-block-accordion-item has-background-2-background-color has-background has-extra-large-font-size" style="border-radius:12px"><!-- wp:accordion-heading {"level":3,"backgroundColor":"background-2"} -->
<h3 class="wp-block-accordion-heading has-background-2-background-color has-background"><button class="wp-block-accordion-heading__toggle"><span class="wp-block-accordion-heading__toggle-title">02. How long does a course take?</span><span class="wp-block-accordion-heading__toggle-icon" aria-hidden="true">+</span></button></h3>
<!-- /wp:accordion-heading -->

<!-- wp:accordion-panel {"backgroundColor":"background-2"} -->
<div role="region" class="wp-block-accordion-panel has-background-2-background-color has-background"><!-- wp:paragraph {"style":{"typography":{"fontSize":"17px","lineHeight":"1.8"}},"textColor":"accent-3"} -->
<p class="has-accent-3-color has-text-color" style="font-size:17px;line-height:1.8">Most courses take 4-12 weeks. But you can go at your own speed - faster or slower depending on your schedule.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:accordion-panel --></div>
<!-- /wp:accordion-item -->

<!-- wp:accordion-item {"style":{"border":{"radius":"12px"}},"backgroundColor":"background-2","fontSize":"extra-large"} -->
<div class="wp-block-accordion-item has-background-2-background-color has-background has-extra-large-font-size" style="border-radius:12px"><!-- wp:accordion-heading {"level":3,"backgroundColor":"background-2"} -->
<h3 class="wp-block-accordion-heading has-background-2-background-color has-background"><button class="wp-block-accordion-heading__toggle"><span class="wp-block-accordion-heading__toggle-title">03. Will I get a certificate?</span><span class="wp-block-accordion-heading__toggle-icon" aria-hidden="true">+</span></button></h3>
<!-- /wp:accordion-heading -->

<!-- wp:accordion-panel {"backgroundColor":"background-2"} -->
<div role="region" class="wp-block-accordion-panel has-background-2-background-color has-background"><!-- wp:paragraph {"style":{"typography":{"fontSize":"17px","lineHeight":"1.8"}},"textColor":"accent-3"} -->
<p class="has-accent-3-color has-text-color" style="font-size:17px;line-height:1.8">Yes! When you finish a course, you\'ll get a certificate from Online Academy that you can show to employers.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:accordion-panel --></div>
<!-- /wp:accordion-item -->

<!-- wp:accordion-item {"style":{"border":{"radius":"12px"}},"backgroundColor":"background-2","fontSize":"extra-large"} -->
<div class="wp-block-accordion-item has-background-2-background-color has-background has-extra-large-font-size" style="border-radius:12px"><!-- wp:accordion-heading {"level":3,"backgroundColor":"background-2"} -->
<h3 class="wp-block-accordion-heading has-background-2-background-color has-background"><button class="wp-block-accordion-heading__toggle"><span class="wp-block-accordion-heading__toggle-title">04. What if I don\'t like the course?</span><span class="wp-block-accordion-heading__toggle-icon" aria-hidden="true">+</span></button></h3>
<!-- /wp:accordion-heading -->

<!-- wp:accordion-panel {"backgroundColor":"background-2"} -->
<div role="region" class="wp-block-accordion-panel has-background-2-background-color has-background"><!-- wp:paragraph {"style":{"typography":{"fontSize":"17px","lineHeight":"1.8"}},"textColor":"accent-3"} -->
<p class="has-accent-3-color has-text-color" style="font-size:17px;line-height:1.8">No problem! If you\'re not happy within the first 14 days, we\'ll give you your money back. Just contact us.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:accordion-panel --></div>
<!-- /wp:accordion-item -->

<!-- wp:accordion-item {"style":{"border":{"radius":"12px"}},"backgroundColor":"background-2","fontSize":"extra-large"} -->
<div class="wp-block-accordion-item has-background-2-background-color has-background has-extra-large-font-size" style="border-radius:12px"><!-- wp:accordion-heading {"level":3,"backgroundColor":"background-2"} -->
<h3 class="wp-block-accordion-heading has-background-2-background-color has-background"><button class="wp-block-accordion-heading__toggle"><span class="wp-block-accordion-heading__toggle-title">05. Can I watch lessons again after I finish?</span><span class="wp-block-accordion-heading__toggle-icon" aria-hidden="true">+</span></button></h3>
<!-- /wp:accordion-heading -->

<!-- wp:accordion-panel {"backgroundColor":"background-2"} -->
<div role="region" class="wp-block-accordion-panel has-background-2-background-color has-background"><!-- wp:paragraph {"style":{"typography":{"fontSize":"17px","lineHeight":"1.8"}},"textColor":"accent-3"} -->
<p class="has-accent-3-color has-text-color" style="font-size:17px;line-height:1.8">Yes! Once you join, you can watch all the lessons anytime, forever. Even if new content is added later.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:accordion-panel --></div>
<!-- /wp:accordion-item --></div>
<!-- /wp:accordion --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
',
);
