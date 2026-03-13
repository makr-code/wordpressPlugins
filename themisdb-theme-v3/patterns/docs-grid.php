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
<div class="wp-block-group" style="background-color:#ffffff;padding-top:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--20)">

	<!-- wp:html --><div style="text-align:center;margin-bottom:1rem"><span style="display:inline-block;background:#cfe4fc;color:#0078d4;border-radius:9999px;padding:0.25rem 0.875rem;font-size:0.75rem;font-weight:700;letter-spacing:0.06em;text-transform:uppercase">Documentation</span></div><!-- /wp:html -->

	<!-- wp:heading {"level":2,"textAlign":"center","style":{"typography":{"fontSize":"clamp(1.75rem,3.5vw,2.75rem)","fontWeight":"700","letterSpacing":"-0.03em"},"color":{"text":"#12202f"},"spacing":{"margin":{"bottom":"var:preset|spacing|4"}}}} -->
	<h2 class="wp-block-heading has-text-align-center" style="color:#12202f;font-size:clamp(1.75rem,3.5vw,2.75rem);font-weight:700;letter-spacing:-0.03em;margin-bottom:var(--wp--preset--spacing--4)">Explore the Documentation</h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"textAlign":"center","style":{"typography":{"fontSize":"1.0625rem"},"color":{"text":"#6c7f96"},"spacing":{"margin":{"bottom":"var:preset|spacing|12"}}}} -->
	<p class="has-text-align-center" style="color:#6c7f96;font-size:1.0625rem;margin-bottom:var(--wp--preset--spacing--12)">Everything you need to build, deploy, and scale with ThemisDB.</p>
	<!-- /wp:paragraph -->

	<!-- wp:html -->
	<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;">

		<a href="/docs/getting-started" style="display:flex;flex-direction:column;gap:0.75rem;padding:1.5rem;border:1px solid #dde3ec;border-radius:12px;text-decoration:none;transition:all 0.25s;background:#fff;">
			<div style="font-size:2rem;">🚀</div>
			<div style="font-weight:700;color:#12202f;font-size:0.9375rem">Getting Started</div>
			<div style="font-size:0.8125rem;color:#6c7f96;line-height:1.55">Install, configure, and run your first query in 5 minutes.</div>
			<div style="font-size:0.8125rem;color:#0078d4;font-weight:600;margin-top:auto">Read guide →</div>
		</a>

		<a href="/docs/api" style="display:flex;flex-direction:column;gap:0.75rem;padding:1.5rem;border:1px solid #dde3ec;border-radius:12px;text-decoration:none;transition:all 0.25s;background:#fff;">
			<div style="font-size:2rem;">📡</div>
			<div style="font-weight:700;color:#12202f;font-size:0.9375rem">API Reference</div>
			<div style="font-size:0.8125rem;color:#6c7f96;line-height:1.55">REST, SQL, GraphQL, and WebSocket API documentation.</div>
			<div style="font-size:0.8125rem;color:#0078d4;font-weight:600;margin-top:auto">View reference →</div>
		</a>

		<a href="/docs/architecture" style="display:flex;flex-direction:column;gap:0.75rem;padding:1.5rem;border:1px solid #dde3ec;border-radius:12px;text-decoration:none;transition:all 0.25s;background:#fff;">
			<div style="font-size:2rem;">🏗️</div>
			<div style="font-weight:700;color:#12202f;font-size:0.9375rem">Architecture</div>
			<div style="font-size:0.8125rem;color:#6c7f96;line-height:1.55">Deep-dive into the storage engine, query planner, and replication system.</div>
			<div style="font-size:0.8125rem;color:#0078d4;font-weight:600;margin-top:auto">Explore →</div>
		</a>

		<a href="/docs/sql" style="display:flex;flex-direction:column;gap:0.75rem;padding:1.5rem;border:1px solid #dde3ec;border-radius:12px;text-decoration:none;transition:all 0.25s;background:#fff;">
			<div style="font-size:2rem;">💾</div>
			<div style="font-weight:700;color:#12202f;font-size:0.9375rem">SQL Guide</div>
			<div style="font-size:0.8125rem;color:#6c7f96;line-height:1.55">Complete SQL reference including vector extensions and AI functions.</div>
			<div style="font-size:0.8125rem;color:#0078d4;font-weight:600;margin-top:auto">Read guide →</div>
		</a>

		<a href="/docs/docker" style="display:flex;flex-direction:column;gap:0.75rem;padding:1.5rem;border:1px solid #dde3ec;border-radius:12px;text-decoration:none;transition:all 0.25s;background:#fff;">
			<div style="font-size:2rem;">🐳</div>
			<div style="font-weight:700;color:#12202f;font-size:0.9375rem">Docker Guide</div>
			<div style="font-size:0.8125rem;color:#6c7f96;line-height:1.55">Docker Compose, Kubernetes, and production deployment patterns.</div>
			<div style="font-size:0.8125rem;color:#0078d4;font-weight:600;margin-top:auto">Read guide →</div>
		</a>

		<a href="/benchmarks" style="display:flex;flex-direction:column;gap:0.75rem;padding:1.5rem;border:1px solid #dde3ec;border-radius:12px;text-decoration:none;transition:all 0.25s;background:#fff;">
			<div style="font-size:2rem;">📊</div>
			<div style="font-weight:700;color:#12202f;font-size:0.9375rem">Benchmarks</div>
			<div style="font-size:0.8125rem;color:#6c7f96;line-height:1.55">Independent performance results vs PostgreSQL, MySQL, MongoDB.</div>
			<div style="font-size:0.8125rem;color:#0078d4;font-weight:600;margin-top:auto">View results →</div>
		</a>

		<a href="/community" style="display:flex;flex-direction:column;gap:0.75rem;padding:1.5rem;border:1px solid #dde3ec;border-radius:12px;text-decoration:none;transition:all 0.25s;background:#fff;">
			<div style="font-size:2rem;">💬</div>
			<div style="font-weight:700;color:#12202f;font-size:0.9375rem">Community</div>
			<div style="font-size:0.8125rem;color:#6c7f96;line-height:1.55">Forums, Discord, Stack Overflow tag, and community showcase.</div>
			<div style="font-size:0.8125rem;color:#0078d4;font-weight:600;margin-top:auto">Join community →</div>
		</a>

		<a href="https://github.com/makr-code/wordpressPlugins" style="display:flex;flex-direction:column;gap:0.75rem;padding:1.5rem;border:1px solid #dde3ec;border-radius:12px;text-decoration:none;transition:all 0.25s;background:#fff;">
			<div style="font-size:2rem;">⭐</div>
			<div style="font-weight:700;color:#12202f;font-size:0.9375rem">GitHub</div>
			<div style="font-size:0.8125rem;color:#6c7f96;line-height:1.55">Source code, issues, discussions, and contribution guides.</div>
			<div style="font-size:0.8125rem;color:#0078d4;font-weight:600;margin-top:auto">View on GitHub →</div>
		</a>

	</div>
	<!-- /wp:html -->

</div>
<!-- /wp:group -->
