<?php
/**
 * Title: Hero – Home (Dynamic)
 * Slug: themisdb-v3/hero-home
 * Categories: themisdb-v3, themisdb-v3-landing, featured
 * Keywords: hero, homepage, posts, spotlight, query
 * Block Types: core/cover
 * Post Types: wp_template, page
 * Viewport Width: 1280
 * Description: Full-width dynamic hero driven by the latest published posts and supporting product CTAs.
 */
?>
<!-- wp:cover {"dimRatio":0,"minHeight":660,"minHeightUnit":"px","isDark":true,"className":"tv3-hero tv3-query-hero-shell","layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-cover tv3-hero tv3-hero-shell is-dark tv3-query-hero-shell">
	<span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim wp-block-cover__gradient-background has-background-gradient tv3-hero-gradient"></span>
	<div class="wp-block-cover__inner-container tv3-hero-inner">
		<!-- wp:paragraph {"align":"center","className":"tv3-hero-badge-wrap"} -->
		<p class="has-text-align-center tv3-hero-badge-wrap"><a href="/docs/changelog" class="tv3-hero-badge themis-v3-slide-up"><span class="tv3-hero-badge-pill">NEW</span>Latest stories from the blog →</a></p>
		<!-- /wp:paragraph -->

		<!-- wp:query {"queryId":1,"query":{"perPage":1,"pages":0,"offset":0,"postType":["post","page"],"order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false,"taxQuery":{"post_tag":["hero"]}},"className":"tv3-query-hero","layout":{"type":"default"}} -->
		<div class="wp-block-query tv3-query-hero">
			<!-- wp:post-template {"className":"tv3-query-hero-grid","layout":{"type":"default"}} -->
			<!-- wp:group {"className":"tv3-hero-query-featured","layout":{"type":"constrained"}} -->
			<div class="wp-block-group tv3-hero-query-featured">
				<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/9","className":"tv3-hero-query-image"} /-->
				<!-- wp:post-terms {"term":"category","separator":" · ","className":"tv3-hero-query-meta"} /-->
				<!-- wp:post-title {"level":1,"isLink":true,"className":"tv3-hero-query-title"} /-->
				<!-- wp:post-excerpt {"excerptLength":32,"className":"tv3-hero-query-excerpt"} /-->
			</div>
			<!-- /wp:group -->
			<!-- /wp:post-template -->

			<!-- wp:query-no-results -->
			<!-- wp:group {"className":"tv3-hero-query-fallback","layout":{"type":"constrained","contentSize":"960px"}} -->
			<div class="wp-block-group tv3-hero-query-fallback">
				<!-- wp:heading {"level":1,"textAlign":"center","className":"tv3-hero-fallback-title"} -->
				<h1 class="wp-block-heading has-text-align-center tv3-hero-fallback-title">ThemisDB v3 – Built for the AI Era</h1>
				<!-- /wp:heading -->
				<!-- wp:paragraph {"textAlign":"center","className":"tv3-hero-fallback-copy"} -->
				<p class="has-text-align-center tv3-hero-fallback-copy">No Hero-tagged content is available locally yet, so the front-page keeps a working product hero visible while the content filter is waiting for its tagged post or page.</p>
				<!-- /wp:paragraph -->
				<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center","flexWrap":"wrap"}} -->
				<div class="wp-block-buttons">
					<!-- wp:button -->
					<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/downloads">⬇ Download Free</a></div>
					<!-- /wp:button -->
					<!-- wp:button {"className":"is-style-outline"} -->
					<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/docs/getting-started">View Documentation →</a></div>
					<!-- /wp:button -->
				</div>
				<!-- /wp:buttons -->
			</div>
			<!-- /wp:group -->
			<!-- /wp:query-no-results -->
		</div>
		<!-- /wp:query -->

		<!-- wp:spacer {"height":"32px"} --><div aria-hidden="true" class="wp-block-spacer tv3-spacer-32"></div><!-- /wp:spacer -->

		<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center","flexWrap":"wrap"},"style":{"spacing":{"blockGap":"var:preset|spacing|4","margin":{"bottom":"var:preset|spacing|8"}}}} -->
		<div class="wp-block-buttons tv3-hero-actions">
			<!-- wp:button {"style":{"color":{"background":"#0078d4","text":"#ffffff"},"border":{"radius":"8px"},"spacing":{"padding":{"top":"0.875rem","bottom":"0.875rem","left":"2rem","right":"2rem"}}},"fontSize":"md"} -->
			<div class="wp-block-button"><a href="/downloads" class="wp-block-button__link wp-element-button has-md-font-size tv3-hero-btn-primary">⬇ Download Free</a></div>
			<!-- /wp:button -->
			<!-- wp:button {"className":"is-style-outline","style":{"color":{"text":"rgba(255,255,255,0.9)"},"border":{"radius":"8px","color":"rgba(255,255,255,0.35)","width":"1.5px"},"spacing":{"padding":{"top":"0.875rem","bottom":"0.875rem","left":"2rem","right":"2rem"}}},"fontSize":"md"} -->
			<div class="wp-block-button is-style-outline"><a href="/docs/getting-started" class="wp-block-button__link wp-element-button has-md-font-size tv3-hero-btn-secondary">View Documentation →</a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->

		<!-- wp:group {"className":"tv3-hero-command-wrap","layout":{"type":"constrained"}} -->
		<div class="wp-block-group tv3-hero-command-wrap">
			<div class="tv3-hero-command">
				<span class="tv3-hero-command-prompt">$</span>
				<code class="tv3-hero-command-code">docker pull themisdb/themisdb:v3-latest</code>
				<button data-copy-text="docker pull themisdb/themisdb:v3-latest" class="tv3-code-copy-btn tv3-hero-command-copy" title="Copy" aria-label="Copy docker command">⎘</button>
			</div>
			<p class="tv3-hero-command-links">Also available: <a href="/downloads" class="tv3-hero-command-link">binary</a> · <a href="https://github.com/makr-code/wordpressPlugins" class="tv3-hero-command-link">source</a></p>
		</div>
		<!-- /wp:group -->

		<!-- wp:spacer {"height":"48px"} --><div aria-hidden="true" class="wp-block-spacer tv3-spacer-48"></div><!-- /wp:spacer -->
	</div>
</div>
<!-- /wp:cover -->
