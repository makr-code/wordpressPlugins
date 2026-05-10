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

	<!-- wp:paragraph {"align":"center","className":"tv3-docs-grid-kicker-wrap"} -->
	<p class="has-text-align-center tv3-docs-grid-kicker-wrap"><span class="tv3-docs-grid-kicker">Documentation</span></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":2,"textAlign":"center","style":{"typography":{"fontSize":"clamp(1.75rem,3.5vw,2.75rem)","fontWeight":"700","letterSpacing":"-0.03em"},"color":{"text":"#12202f"},"spacing":{"margin":{"bottom":"var:preset|spacing|4"}}}} -->
	<h2 class="wp-block-heading has-text-align-center tv3-docs-grid-title">Explore the Documentation</h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"textAlign":"center","style":{"typography":{"fontSize":"1.0625rem"},"color":{"text":"#6c7f96"},"spacing":{"margin":{"bottom":"var:preset|spacing|12"}}}} -->
	<p class="has-text-align-center tv3-docs-grid-subtitle">Everything you need to build, deploy, and scale with ThemisDB.</p>
	<!-- /wp:paragraph -->

	<!-- wp:group {"className":"tv3-docs-grid-cards","layout":{"type":"default"}} -->
	<div class="wp-block-group tv3-docs-grid-cards">
		<!-- wp:group {"className":"tv3-docs-grid-card","layout":{"type":"constrained"}} -->
		<div class="wp-block-group tv3-docs-grid-card">
			<!-- wp:paragraph {"className":"tv3-docs-grid-icon"} -->
			<p class="tv3-docs-grid-icon">🚀</p>
			<!-- /wp:paragraph -->
			<!-- wp:heading {"level":3,"className":"tv3-docs-grid-card-title"} -->
			<h3 class="wp-block-heading tv3-docs-grid-card-title">Getting Started</h3>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"className":"tv3-docs-grid-card-text"} -->
			<p class="tv3-docs-grid-card-text">Install, configure, and run your first query in 5 minutes.</p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"className":"tv3-docs-grid-card-link"} -->
			<p class="tv3-docs-grid-card-link"><a href="/docs/getting-started">Read guide →</a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"tv3-docs-grid-card","layout":{"type":"constrained"}} -->
		<div class="wp-block-group tv3-docs-grid-card">
			<!-- wp:paragraph {"className":"tv3-docs-grid-icon"} -->
			<p class="tv3-docs-grid-icon">📡</p>
			<!-- /wp:paragraph -->
			<!-- wp:heading {"level":3,"className":"tv3-docs-grid-card-title"} -->
			<h3 class="wp-block-heading tv3-docs-grid-card-title">API Reference</h3>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"className":"tv3-docs-grid-card-text"} -->
			<p class="tv3-docs-grid-card-text">REST, SQL, GraphQL, and WebSocket API documentation.</p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"className":"tv3-docs-grid-card-link"} -->
			<p class="tv3-docs-grid-card-link"><a href="/docs/api">View reference →</a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"tv3-docs-grid-card","layout":{"type":"constrained"}} -->
		<div class="wp-block-group tv3-docs-grid-card">
			<!-- wp:paragraph {"className":"tv3-docs-grid-icon"} -->
			<p class="tv3-docs-grid-icon">🏗️</p>
			<!-- /wp:paragraph -->
			<!-- wp:heading {"level":3,"className":"tv3-docs-grid-card-title"} -->
			<h3 class="wp-block-heading tv3-docs-grid-card-title">Architecture</h3>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"className":"tv3-docs-grid-card-text"} -->
			<p class="tv3-docs-grid-card-text">Deep-dive into the storage engine, query planner, and replication system.</p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"className":"tv3-docs-grid-card-link"} -->
			<p class="tv3-docs-grid-card-link"><a href="/docs/architecture">Explore →</a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"tv3-docs-grid-card","layout":{"type":"constrained"}} -->
		<div class="wp-block-group tv3-docs-grid-card">
			<!-- wp:paragraph {"className":"tv3-docs-grid-icon"} -->
			<p class="tv3-docs-grid-icon">💾</p>
			<!-- /wp:paragraph -->
			<!-- wp:heading {"level":3,"className":"tv3-docs-grid-card-title"} -->
			<h3 class="wp-block-heading tv3-docs-grid-card-title">SQL Guide</h3>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"className":"tv3-docs-grid-card-text"} -->
			<p class="tv3-docs-grid-card-text">Complete SQL reference including vector extensions and AI functions.</p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"className":"tv3-docs-grid-card-link"} -->
			<p class="tv3-docs-grid-card-link"><a href="/docs/sql">Read guide →</a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"tv3-docs-grid-card","layout":{"type":"constrained"}} -->
		<div class="wp-block-group tv3-docs-grid-card">
			<!-- wp:paragraph {"className":"tv3-docs-grid-icon"} -->
			<p class="tv3-docs-grid-icon">🐳</p>
			<!-- /wp:paragraph -->
			<!-- wp:heading {"level":3,"className":"tv3-docs-grid-card-title"} -->
			<h3 class="wp-block-heading tv3-docs-grid-card-title">Docker Guide</h3>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"className":"tv3-docs-grid-card-text"} -->
			<p class="tv3-docs-grid-card-text">Docker Compose, Kubernetes, and production deployment patterns.</p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"className":"tv3-docs-grid-card-link"} -->
			<p class="tv3-docs-grid-card-link"><a href="/docs/docker">Read guide →</a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"tv3-docs-grid-card","layout":{"type":"constrained"}} -->
		<div class="wp-block-group tv3-docs-grid-card">
			<!-- wp:paragraph {"className":"tv3-docs-grid-icon"} -->
			<p class="tv3-docs-grid-icon">📊</p>
			<!-- /wp:paragraph -->
			<!-- wp:heading {"level":3,"className":"tv3-docs-grid-card-title"} -->
			<h3 class="wp-block-heading tv3-docs-grid-card-title">Benchmarks</h3>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"className":"tv3-docs-grid-card-text"} -->
			<p class="tv3-docs-grid-card-text">Independent performance results vs PostgreSQL, MySQL, MongoDB.</p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"className":"tv3-docs-grid-card-link"} -->
			<p class="tv3-docs-grid-card-link"><a href="/benchmarks">View results →</a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"tv3-docs-grid-card","layout":{"type":"constrained"}} -->
		<div class="wp-block-group tv3-docs-grid-card">
			<!-- wp:paragraph {"className":"tv3-docs-grid-icon"} -->
			<p class="tv3-docs-grid-icon">💬</p>
			<!-- /wp:paragraph -->
			<!-- wp:heading {"level":3,"className":"tv3-docs-grid-card-title"} -->
			<h3 class="wp-block-heading tv3-docs-grid-card-title">Community</h3>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"className":"tv3-docs-grid-card-text"} -->
			<p class="tv3-docs-grid-card-text">Forums, Discord, Stack Overflow tag, and community showcase.</p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"className":"tv3-docs-grid-card-link"} -->
			<p class="tv3-docs-grid-card-link"><a href="/community">Join community →</a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"tv3-docs-grid-card","layout":{"type":"constrained"}} -->
		<div class="wp-block-group tv3-docs-grid-card">
			<!-- wp:paragraph {"className":"tv3-docs-grid-icon"} -->
			<p class="tv3-docs-grid-icon">⭐</p>
			<!-- /wp:paragraph -->
			<!-- wp:heading {"level":3,"className":"tv3-docs-grid-card-title"} -->
			<h3 class="wp-block-heading tv3-docs-grid-card-title">GitHub</h3>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"className":"tv3-docs-grid-card-text"} -->
			<p class="tv3-docs-grid-card-text">Source code, issues, discussions, and contribution guides.</p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"className":"tv3-docs-grid-card-link"} -->
			<p class="tv3-docs-grid-card-link"><a href="https://github.com/makr-code/wordpressPlugins">View on GitHub →</a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->

</div>
<!-- /wp:group -->
