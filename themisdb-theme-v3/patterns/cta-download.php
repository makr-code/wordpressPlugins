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

	<!-- wp:html --><div class="tv3-download-kicker-wrap"><span class="tv3-download-kicker">Downloads</span></div><!-- /wp:html -->

	<!-- wp:heading {"level":2,"textAlign":"center","style":{"typography":{"fontSize":"clamp(1.75rem,3.5vw,2.75rem)","fontWeight":"700","letterSpacing":"-0.03em"},"color":{"text":"#12202f"},"spacing":{"margin":{"bottom":"var:preset|spacing|4"}}}} -->
	<h2 class="wp-block-heading has-text-align-center tv3-download-title">Get ThemisDB v3</h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"textAlign":"center","style":{"typography":{"fontSize":"1.0625rem"},"color":{"text":"#6c7f96"},"spacing":{"margin":{"bottom":"var:preset|spacing|12"}}}} -->
	<p class="has-text-align-center tv3-download-subtitle">Choose your preferred deployment method. All options are free and MIT licensed.</p>
	<!-- /wp:paragraph -->

	<!-- wp:html -->
	<div class="tv3-download-grid">

		<!-- Docker Card -->
		<div class="tv3-download-card">
			<div class="tv3-download-icon tv3-download-icon-docker">🐳</div>
			<div>
				<div class="tv3-download-card-title">Docker Hub</div>
				<div class="tv3-download-card-text">Official Docker image. Deploy with a single command in under 60 seconds.</div>
				<div class="tv3-download-code">
					<span class="tv3-download-code-prompt">$</span> docker pull themisdb/themisdb:v3-latest
				</div>
			</div>
			<a href="/docker" class="tv3-download-link tv3-download-link-primary">Docker Hub →</a>
		</div>

		<!-- Binary Release Card -->
		<div class="tv3-download-card tv3-download-card-featured">
			<div class="tv3-download-pill">Recommended</div>
			<div class="tv3-download-icon tv3-download-icon-binary">📦</div>
			<div>
				<div class="tv3-download-card-title">Binary Release</div>
				<div class="tv3-download-card-text">Pre-compiled binaries for Linux, macOS, and Windows. No dependencies required.</div>
				<div class="tv3-download-platforms">
					<span class="tv3-download-platform">Linux</span>
					<span class="tv3-download-platform">macOS</span>
					<span class="tv3-download-platform">Windows</span>
				</div>
			</div>
			<a href="/downloads" class="tv3-download-link tv3-download-link-primary">⬇ Download v3.0</a>
		</div>

		<!-- Compendium/Source Card -->
		<div class="tv3-download-card">
			<div class="tv3-download-icon tv3-download-icon-compendium">📖</div>
			<div>
				<div class="tv3-download-card-title">Compendium Download</div>
				<div class="tv3-download-card-text">Full documentation bundle, source code, plugins, and examples in a single archive.</div>
				<div class="tv3-download-meta">
					<span>📄</span> 85 MB · ZIP archive · MIT License
				</div>
			</div>
			<a href="/downloads/compendium" class="tv3-download-link tv3-download-link-outline">Get Compendium →</a>
		</div>

	</div>
	<!-- /wp:html -->

</div>
<!-- /wp:group -->
