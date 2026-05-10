<?php
/**
 * Title: Hero – Home (Azure/PostgreSQL Style)
 * Slug: themisdb-v3/hero-home
 * Categories: themisdb-v3, themisdb-v3-landing, featured
 * Block Types: core/cover
 * Post Types: wp_template, page
 * Viewport Width: 1280
 * Description: Full-width dark hero with PostgreSQL navy + Azure cyan, animated badge, headline, dual CTAs, and Docker snippet.
 */
?>
<!-- wp:cover {"dimRatio":0,"minHeight":660,"minHeightUnit":"px","isDark":true,"className":"tv3-hero","layout":{"type":"constrained","contentSize":"960px"}} -->
<div class="wp-block-cover tv3-hero tv3-hero-shell is-dark">
	<span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim wp-block-cover__gradient-background has-background-gradient tv3-hero-gradient"></span>
	<div class="wp-block-cover__inner-container tv3-hero-inner">
		<!-- wp:spacer {"height":"48px"} --><div aria-hidden="true" class="wp-block-spacer tv3-spacer-48"></div><!-- /wp:spacer -->

		<!-- Animated badge -->
		<!-- wp:paragraph {"align":"center","className":"tv3-hero-badge-wrap"} -->
		<p class="has-text-align-center tv3-hero-badge-wrap"><a href="/docs/changelog" class="tv3-hero-badge themis-v3-slide-up"><span class="tv3-hero-badge-pill">NEW</span>ThemisDB v3 · Fluent Design + jQuery Animations →</a></p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"level":1,"textAlign":"center","style":{"typography":{"fontSize":"clamp(2.25rem,5.5vw,4rem)","fontWeight":"800","lineHeight":"1.1","letterSpacing":"-0.04em"},"color":{"text":"#ffffff"},"spacing":{"margin":{"bottom":"var:preset|spacing|5"}}}} -->
		<h1 class="wp-block-heading has-text-align-center tv3-hero-title">ThemisDB v3 – The Database<br>Built for the AI Era</h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"textAlign":"center","style":{"typography":{"fontSize":"clamp(1rem,2vw,1.3125rem)","lineHeight":"1.65"},"color":{"text":"rgba(255,255,255,0.72)"},"spacing":{"margin":{"bottom":"var:preset|spacing|8"}}}} -->
		<p class="has-text-align-center tv3-hero-subtitle">Multi-model storage, native AI/LLM integration, and extreme performance — in one open-source database. Now with Azure Fluent Design and enterprise pricing.</p>
		<!-- /wp:paragraph -->

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

		<!-- Docker command snippet -->
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
