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
<div class="wp-block-cover tv3-hero is-light" style="min-height:660px">
	<span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim wp-block-cover__gradient-background has-background-gradient" style="background:linear-gradient(160deg,#001a33 0%,#003366 45%,#00509e 100%)"></span>
	<div class="wp-block-cover__inner-container tv3-hero-inner">
		<!-- wp:spacer {"height":"48px"} --><div style="height:48px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->

		<!-- Animated badge -->
		<!-- wp:html -->
		<div style="text-align:center;margin-bottom:1.5rem;">
			<a href="/docs/changelog" class="tv3-hero-badge themis-v3-slide-up" style="display:inline-flex;align-items:center;gap:0.5rem;background:rgba(0,120,212,0.2);border:1px solid rgba(80,230,255,0.4);border-radius:9999px;padding:0.3rem 1rem 0.3rem 0.4rem;text-decoration:none;font-size:0.8125rem;color:rgba(255,255,255,0.9);font-weight:500;">
				<span style="background:#50e6ff;color:#003366;border-radius:9999px;padding:0.15rem 0.65rem;font-size:0.6875rem;font-weight:800;letter-spacing:0.04em;">NEW</span>
				ThemisDB v3 · Fluent Design + jQuery Animations →
			</a>
		</div>
		<!-- /wp:html -->

		<!-- wp:heading {"level":1,"textAlign":"center","style":{"typography":{"fontSize":"clamp(2.25rem,5.5vw,4rem)","fontWeight":"800","lineHeight":"1.1","letterSpacing":"-0.04em"},"color":{"text":"#ffffff"},"spacing":{"margin":{"bottom":"var:preset|spacing|5"}}}} -->
		<h1 class="wp-block-heading has-text-align-center" style="color:#ffffff;font-size:clamp(2.25rem,5.5vw,4rem);font-weight:800;line-height:1.1;letter-spacing:-0.04em;margin-bottom:var(--wp--preset--spacing--5)">ThemisDB v3 – The Database<br>Built for the AI Era</h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"textAlign":"center","style":{"typography":{"fontSize":"clamp(1rem,2vw,1.3125rem)","lineHeight":"1.65"},"color":{"text":"rgba(255,255,255,0.72)"},"spacing":{"margin":{"bottom":"var:preset|spacing|8"}}}} -->
		<p class="has-text-align-center" style="color:rgba(255,255,255,0.72);font-size:clamp(1rem,2vw,1.3125rem);line-height:1.65;margin-bottom:var(--wp--preset--spacing--8)">Multi-model storage, native AI/LLM integration, and extreme performance — in one open-source database. Now with Azure Fluent Design and enterprise pricing.</p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center","flexWrap":"wrap"},"style":{"spacing":{"blockGap":"var:preset|spacing|4","margin":{"bottom":"var:preset|spacing|8"}}}} -->
		<div class="wp-block-buttons" style="margin-bottom:var(--wp--preset--spacing--8)">
			<!-- wp:button {"style":{"color":{"background":"#0078d4","text":"#ffffff"},"border":{"radius":"8px"},"spacing":{"padding":{"top":"0.875rem","bottom":"0.875rem","left":"2rem","right":"2rem"}}},"fontSize":"md"} -->
			<div class="wp-block-button"><a href="/downloads" class="wp-block-button__link wp-element-button has-md-font-size" style="background-color:#0078d4;color:#ffffff;border-radius:8px;padding:0.875rem 2rem;font-weight:700">⬇ Download Free</a></div>
			<!-- /wp:button -->
			<!-- wp:button {"className":"is-style-outline","style":{"color":{"text":"rgba(255,255,255,0.9)"},"border":{"radius":"8px","color":"rgba(255,255,255,0.35)","width":"1.5px"},"spacing":{"padding":{"top":"0.875rem","bottom":"0.875rem","left":"2rem","right":"2rem"}}},"fontSize":"md"} -->
			<div class="wp-block-button is-style-outline"><a href="/docs/getting-started" class="wp-block-button__link wp-element-button has-md-font-size" style="color:rgba(255,255,255,0.9);border:1.5px solid rgba(255,255,255,0.35);border-radius:8px;padding:0.875rem 2rem">View Documentation →</a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->

		<!-- Docker command snippet -->
		<!-- wp:html -->
		<div style="text-align:center;max-width:560px;margin:0 auto;">
			<div style="display:inline-flex;align-items:center;gap:0.875rem;background:rgba(0,0,0,0.4);border:1px solid rgba(255,255,255,0.12);border-radius:12px;padding:0.875rem 1.5rem;">
				<span style="color:#50e6ff;font-weight:700;font-family:'Cascadia Code',Consolas,monospace">$</span>
				<code style="font-family:'Cascadia Code','SFMono-Regular',Consolas,monospace;font-size:0.875rem;color:#cdd9e5">docker pull themisdb/themisdb:v3-latest</code>
				<button onclick="navigator.clipboard.writeText('docker pull themisdb/themisdb:v3-latest')" style="background:none;border:none;cursor:pointer;color:rgba(255,255,255,0.4);font-size:0.9rem;padding:0;line-height:1;" title="Copy" aria-label="Copy docker command">⎘</button>
			</div>
			<p style="font-size:0.75rem;color:rgba(255,255,255,0.4);margin:0.625rem 0 0">Also available: <a href="/downloads" style="color:rgba(255,255,255,0.55);text-decoration:underline">binary</a> · <a href="https://github.com/makr-code/wordpressPlugins" style="color:rgba(255,255,255,0.55);text-decoration:underline">source</a></p>
		</div>
		<!-- /wp:html -->

		<!-- wp:spacer {"height":"48px"} --><div style="height:48px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->
	</div>
</div>
<!-- /wp:cover -->
