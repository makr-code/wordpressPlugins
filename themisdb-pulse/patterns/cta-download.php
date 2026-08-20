<?php
/**
 * Title: Download Options – Docker, Binary, Compendium
 * Slug: themisdb-v3/cta-download
 * Categories: themisdb-v3, themisdb-v3-landing
 * Viewport Width: 1280
 * Description: Three download option cards with icons and CTA buttons.
 */
?>
<!-- wp:group {"style":{"color":{"background":"#f5f7fa"},"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group tv3-download-section">

	<!-- wp:paragraph {"align":"center","className":"tv3-download-kicker-wrap"} -->
	<p class="has-text-align-center tv3-download-kicker-wrap"><span class="tv3-download-kicker">Downloads</span></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":2,"textAlign":"center","style":{"typography":{"fontSize":"clamp(1.75rem,3.5vw,2.75rem)","fontWeight":"700","letterSpacing":"-0.03em"},"color":{"text":"#12202f"},"spacing":{"margin":{"bottom":"var:preset|spacing|4"}}}} -->
	<h2 class="wp-block-heading has-text-align-center tv3-download-title">Get ThemisDB v3</h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"textAlign":"center","style":{"typography":{"fontSize":"1.0625rem"},"color":{"text":"#6c7f96"},"spacing":{"margin":{"bottom":"var:preset|spacing|12"}}}} -->
	<p class="has-text-align-center tv3-download-subtitle">Choose your preferred deployment method. All options are free and MIT licensed.</p>
	<!-- /wp:paragraph -->

	<!-- wp:group {"className":"tv3-download-grid","layout":{"type":"default"}} -->
	<div class="wp-block-group tv3-download-grid">
		<!-- wp:group {"className":"tv3-download-card","layout":{"type":"constrained"}} -->
		<div class="wp-block-group tv3-download-card">
			<!-- wp:paragraph {"className":"tv3-download-icon tv3-download-icon-docker"} -->
			<p class="tv3-download-icon tv3-download-icon-docker">🐳</p>
			<!-- /wp:paragraph -->

			<!-- wp:group {"layout":{"type":"constrained"}} -->
			<div class="wp-block-group">
				<!-- wp:heading {"level":3,"className":"tv3-download-card-title"} -->
				<h3 class="wp-block-heading tv3-download-card-title">Docker Hub</h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"className":"tv3-download-card-text"} -->
				<p class="tv3-download-card-text">Official Docker image. Deploy with a single command in under 60 seconds.</p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph {"className":"tv3-download-code"} -->
				<p class="tv3-download-code"><span class="tv3-download-code-prompt">$</span> docker pull themisdb/themisdb:v3-latest</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->

			<!-- wp:paragraph -->
			<p><a href="/docker" class="tv3-download-link tv3-download-link-primary">Docker Hub →</a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"tv3-download-card tv3-download-card-featured","layout":{"type":"constrained"}} -->
		<div class="wp-block-group tv3-download-card tv3-download-card-featured">
			<!-- wp:paragraph {"className":"tv3-download-pill"} -->
			<p class="tv3-download-pill">Recommended</p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"className":"tv3-download-icon tv3-download-icon-binary"} -->
			<p class="tv3-download-icon tv3-download-icon-binary">📦</p>
			<!-- /wp:paragraph -->

			<!-- wp:group {"layout":{"type":"constrained"}} -->
			<div class="wp-block-group">
				<!-- wp:heading {"level":3,"className":"tv3-download-card-title"} -->
				<h3 class="wp-block-heading tv3-download-card-title">Binary Release</h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"className":"tv3-download-card-text"} -->
				<p class="tv3-download-card-text">Pre-compiled binaries for Linux, macOS, and Windows. No dependencies required.</p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph {"className":"tv3-download-platforms"} -->
				<p class="tv3-download-platforms"><span class="tv3-download-platform">Linux</span><span class="tv3-download-platform">macOS</span><span class="tv3-download-platform">Windows</span></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->

			<!-- wp:paragraph -->
			<p><a href="/downloads" class="tv3-download-link tv3-download-link-primary">⬇ Download v3.0</a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"tv3-download-card","layout":{"type":"constrained"}} -->
		<div class="wp-block-group tv3-download-card">
			<!-- wp:paragraph {"className":"tv3-download-icon tv3-download-icon-compendium"} -->
			<p class="tv3-download-icon tv3-download-icon-compendium">📖</p>
			<!-- /wp:paragraph -->

			<!-- wp:group {"layout":{"type":"constrained"}} -->
			<div class="wp-block-group">
				<!-- wp:heading {"level":3,"className":"tv3-download-card-title"} -->
				<h3 class="wp-block-heading tv3-download-card-title">Compendium Download</h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"className":"tv3-download-card-text"} -->
				<p class="tv3-download-card-text">Full documentation bundle, source code, plugins, and examples in a single archive.</p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph {"className":"tv3-download-meta"} -->
				<p class="tv3-download-meta"><span>📄</span> 85 MB · ZIP archive · MIT License</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->

			<!-- wp:paragraph -->
			<p><a href="/downloads/compendium" class="tv3-download-link tv3-download-link-outline">Get Compendium →</a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->

</div>
<!-- /wp:group -->
