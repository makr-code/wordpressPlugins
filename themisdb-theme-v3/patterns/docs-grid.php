<?php
/**
 * Title: Documentation Grid – 8 Resource Tiles
 * Slug: themisdb-v3/docs-grid
 * Categories: themisdb-v3, themisdb-v3-docs
 * Viewport Width: 1280
 * Description: Eight documentation and resource tiles in a responsive grid.
 */
?>
<!-- wp:group {"style":{"color":{"background":"#ffffff"},"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group tv3-docs-grid-section">

	<!-- wp:html --><div class="tv3-docs-grid-kicker-wrap"><span class="tv3-docs-grid-kicker">Documentation</span></div><!-- /wp:html -->

	<!-- wp:heading {"level":2,"textAlign":"center","style":{"typography":{"fontSize":"clamp(1.75rem,3.5vw,2.75rem)","fontWeight":"700","letterSpacing":"-0.03em"},"color":{"text":"#12202f"},"spacing":{"margin":{"bottom":"var:preset|spacing|4"}}}} -->
	<h2 class="wp-block-heading has-text-align-center tv3-docs-grid-title">Explore the Documentation</h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"textAlign":"center","style":{"typography":{"fontSize":"1.0625rem"},"color":{"text":"#6c7f96"},"spacing":{"margin":{"bottom":"var:preset|spacing|12"}}}} -->
	<p class="has-text-align-center tv3-docs-grid-subtitle">Everything you need to build, deploy, and scale with ThemisDB.</p>
	<!-- /wp:paragraph -->

	<!-- wp:html -->
	<div class="tv3-docs-grid-cards">

		<a href="/docs/getting-started" class="tv3-docs-grid-card">
			<div class="tv3-docs-grid-icon">🚀</div>
			<div class="tv3-docs-grid-card-title">Getting Started</div>
			<div class="tv3-docs-grid-card-text">Install, configure, and run your first query in 5 minutes.</div>
			<div class="tv3-docs-grid-card-link">Read guide →</div>
		</a>

		<a href="/docs/api" class="tv3-docs-grid-card">
			<div class="tv3-docs-grid-icon">📡</div>
			<div class="tv3-docs-grid-card-title">API Reference</div>
			<div class="tv3-docs-grid-card-text">REST, SQL, GraphQL, and WebSocket API documentation.</div>
			<div class="tv3-docs-grid-card-link">View reference →</div>
		</a>

		<a href="/docs/architecture" class="tv3-docs-grid-card">
			<div class="tv3-docs-grid-icon">🏗️</div>
			<div class="tv3-docs-grid-card-title">Architecture</div>
			<div class="tv3-docs-grid-card-text">Deep-dive into the storage engine, query planner, and replication system.</div>
			<div class="tv3-docs-grid-card-link">Explore →</div>
		</a>

		<a href="/docs/sql" class="tv3-docs-grid-card">
			<div class="tv3-docs-grid-icon">💾</div>
			<div class="tv3-docs-grid-card-title">SQL Guide</div>
			<div class="tv3-docs-grid-card-text">Complete SQL reference including vector extensions and AI functions.</div>
			<div class="tv3-docs-grid-card-link">Read guide →</div>
		</a>

		<a href="/docs/docker" class="tv3-docs-grid-card">
			<div class="tv3-docs-grid-icon">🐳</div>
			<div class="tv3-docs-grid-card-title">Docker Guide</div>
			<div class="tv3-docs-grid-card-text">Docker Compose, Kubernetes, and production deployment patterns.</div>
			<div class="tv3-docs-grid-card-link">Read guide →</div>
		</a>

		<a href="/benchmarks" class="tv3-docs-grid-card">
			<div class="tv3-docs-grid-icon">📊</div>
			<div class="tv3-docs-grid-card-title">Benchmarks</div>
			<div class="tv3-docs-grid-card-text">Independent performance results vs PostgreSQL, MySQL, MongoDB.</div>
			<div class="tv3-docs-grid-card-link">View results →</div>
		</a>

		<a href="/community" class="tv3-docs-grid-card">
			<div class="tv3-docs-grid-icon">💬</div>
			<div class="tv3-docs-grid-card-title">Community</div>
			<div class="tv3-docs-grid-card-text">Forums, Discord, Stack Overflow tag, and community showcase.</div>
			<div class="tv3-docs-grid-card-link">Join community →</div>
		</a>

		<a href="https://github.com/makr-code/wordpressPlugins" class="tv3-docs-grid-card">
			<div class="tv3-docs-grid-icon">⭐</div>
			<div class="tv3-docs-grid-card-title">GitHub</div>
			<div class="tv3-docs-grid-card-text">Source code, issues, discussions, and contribution guides.</div>
			<div class="tv3-docs-grid-card-link">View on GitHub →</div>
		</a>

	</div>
	<!-- /wp:html -->

</div>
<!-- /wp:group -->
