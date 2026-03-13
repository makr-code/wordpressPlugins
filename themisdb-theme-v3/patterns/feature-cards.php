<?php
/**
 * Title: Feature Cards – 3×2 Grid (Azure Style)
 * Slug: themisdb-v3/feature-cards
 * Categories: themisdb-v3, themisdb-v3-landing
 * Viewport Width: 1280
 * Description: Six feature cards in a 3-column grid with Azure-style blue top border on hover.
 */
?>
<!-- wp:group {"style":{"color":{"background":"#ffffff"},"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group" style="background-color:#ffffff;padding-top:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--20)">

	<!-- wp:html --><div style="text-align:center;margin-bottom:1rem"><span style="display:inline-block;background:#cfe4fc;color:#0078d4;border-radius:9999px;padding:0.25rem 0.875rem;font-size:0.75rem;font-weight:700;letter-spacing:0.06em;text-transform:uppercase">Features</span></div><!-- /wp:html -->

	<!-- wp:heading {"level":2,"textAlign":"center","style":{"typography":{"fontSize":"clamp(1.75rem,3.5vw,2.75rem)","fontWeight":"700","letterSpacing":"-0.03em"},"color":{"text":"#12202f"},"spacing":{"margin":{"bottom":"var:preset|spacing|4"}}}} -->
	<h2 class="wp-block-heading has-text-align-center" style="color:#12202f;font-size:clamp(1.75rem,3.5vw,2.75rem);font-weight:700;letter-spacing:-0.03em;margin-bottom:var(--wp--preset--spacing--4)">Everything you need in one database</h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"textAlign":"center","style":{"typography":{"fontSize":"1.0625rem","lineHeight":"1.65"},"color":{"text":"#6c7f96"},"spacing":{"margin":{"bottom":"var:preset|spacing|12"}}}} -->
	<p class="has-text-align-center" style="color:#6c7f96;font-size:1.0625rem;line-height:1.65;margin-bottom:var(--wp--preset--spacing--12)">Designed from the ground up for modern applications that need speed, flexibility, and intelligence.</p>
	<!-- /wp:paragraph -->

	<!-- wp:html -->
	<div class="tv3-product-grid themis-v3-fade-in" style="display:grid;grid-template-columns:repeat(3,1fr);gap:1.5rem;">

		<a href="/features/multi-model" class="tv3-product-card" style="display:flex;flex-direction:column;gap:0.875rem;padding:1.75rem;border:1px solid #dde3ec;border-radius:12px;text-decoration:none;transition:all 0.25s;background:#fff;position:relative;overflow:hidden;">
			<div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(135deg,#0078d4,#005a9e);transform:scaleX(0);transform-origin:left;transition:transform 0.3s ease;" class="tv3-card-top-border"></div>
			<div style="width:52px;height:52px;background:linear-gradient(135deg,#0078d4,#005a9e);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0;">🗄️</div>
			<div>
				<div style="font-size:1.0625rem;font-weight:700;color:#12202f;margin-bottom:0.375rem;letter-spacing:-0.01em;">Multi-Model Storage</div>
				<div style="font-size:0.875rem;color:#6c7f96;line-height:1.6;">Relational, document, graph, time-series — one engine, one query language, zero compromise on consistency.</div>
			</div>
			<div style="font-size:0.8125rem;color:#0078d4;font-weight:600;margin-top:auto;">Learn more →</div>
		</a>

		<a href="/features/ai-integration" class="tv3-product-card" style="display:flex;flex-direction:column;gap:0.875rem;padding:1.75rem;border:1px solid #dde3ec;border-radius:12px;text-decoration:none;transition:all 0.25s;background:#fff;position:relative;overflow:hidden;">
			<div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(135deg,#003366,#00509e);transform:scaleX(0);transform-origin:left;transition:transform 0.3s ease;" class="tv3-card-top-border"></div>
			<div style="width:52px;height:52px;background:linear-gradient(135deg,#003366,#00509e);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0;">🤖</div>
			<div>
				<div style="font-size:1.0625rem;font-weight:700;color:#12202f;margin-bottom:0.375rem;letter-spacing:-0.01em;">Native AI/LLM Integration</div>
				<div style="font-size:0.875rem;color:#6c7f96;line-height:1.6;">Vector search, semantic queries, and LLM pipelines run natively inside the database engine.</div>
			</div>
			<div style="font-size:0.8125rem;color:#0078d4;font-weight:600;margin-top:auto;">Learn more →</div>
		</a>

		<a href="/benchmarks" class="tv3-product-card" style="display:flex;flex-direction:column;gap:0.875rem;padding:1.75rem;border:1px solid #dde3ec;border-radius:12px;text-decoration:none;transition:all 0.25s;background:#fff;position:relative;overflow:hidden;">
			<div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(135deg,#107c10,#55b056);transform:scaleX(0);transform-origin:left;transition:transform 0.3s ease;" class="tv3-card-top-border"></div>
			<div style="width:52px;height:52px;background:linear-gradient(135deg,#107c10,#55b056);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0;">⚡</div>
			<div>
				<div style="font-size:1.0625rem;font-weight:700;color:#12202f;margin-bottom:0.375rem;letter-spacing:-0.01em;">Extreme Performance</div>
				<div style="font-size:0.875rem;color:#6c7f96;line-height:1.6;">Up to 10× faster than PostgreSQL. Independent benchmarks covering OLTP, OLAP, and vector workloads.</div>
			</div>
			<div style="font-size:0.8125rem;color:#0078d4;font-weight:600;margin-top:auto;">View benchmarks →</div>
		</a>

		<a href="/docker" class="tv3-product-card" style="display:flex;flex-direction:column;gap:0.875rem;padding:1.75rem;border:1px solid #dde3ec;border-radius:12px;text-decoration:none;transition:all 0.25s;background:#fff;position:relative;overflow:hidden;">
			<div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(135deg,#00b7c3,#50e6ff);transform:scaleX(0);transform-origin:left;transition:transform 0.3s ease;" class="tv3-card-top-border"></div>
			<div style="width:52px;height:52px;background:linear-gradient(135deg,#00b7c3,#50e6ff);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0;">🐳</div>
			<div>
				<div style="font-size:1.0625rem;font-weight:700;color:#12202f;margin-bottom:0.375rem;letter-spacing:-0.01em;">Docker Ready</div>
				<div style="font-size:0.875rem;color:#6c7f96;line-height:1.6;">Official images with one-line setup. Deploy on Kubernetes, Docker Compose, or standalone in 60 seconds.</div>
			</div>
			<div style="font-size:0.8125rem;color:#0078d4;font-weight:600;margin-top:auto;">Docker Hub →</div>
		</a>

		<a href="/query-playground" class="tv3-product-card" style="display:flex;flex-direction:column;gap:0.875rem;padding:1.75rem;border:1px solid #dde3ec;border-radius:12px;text-decoration:none;transition:all 0.25s;background:#fff;position:relative;overflow:hidden;">
			<div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(135deg,#ffb900,#e66200);transform:scaleX(0);transform-origin:left;transition:transform 0.3s ease;" class="tv3-card-top-border"></div>
			<div style="width:52px;height:52px;background:linear-gradient(135deg,#ffb900,#e66200);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0;">🧪</div>
			<div>
				<div style="font-size:1.0625rem;font-weight:700;color:#12202f;margin-bottom:0.375rem;letter-spacing:-0.01em;">Interactive Query Playground</div>
				<div style="font-size:0.875rem;color:#6c7f96;line-height:1.6;">Write and execute SQL, JSON, and vector queries in-browser. No installation required.</div>
			</div>
			<div style="font-size:0.8125rem;color:#0078d4;font-weight:600;margin-top:auto;">Try it now →</div>
		</a>

		<a href="/features/analytics" class="tv3-product-card" style="display:flex;flex-direction:column;gap:0.875rem;padding:1.75rem;border:1px solid #dde3ec;border-radius:12px;text-decoration:none;transition:all 0.25s;background:#fff;position:relative;overflow:hidden;">
			<div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(135deg,#d13438,#750b1c);transform:scaleX(0);transform-origin:left;transition:transform 0.3s ease;" class="tv3-card-top-border"></div>
			<div style="width:52px;height:52px;background:linear-gradient(135deg,#d13438,#750b1c);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0;">📊</div>
			<div>
				<div style="font-size:1.0625rem;font-weight:700;color:#12202f;margin-bottom:0.375rem;letter-spacing:-0.01em;">Built-in Analytics</div>
				<div style="font-size:0.875rem;color:#6c7f96;line-height:1.6;">Real-time metrics, performance dashboards, and query analytics — no external monitoring tools needed.</div>
			</div>
			<div style="font-size:0.8125rem;color:#0078d4;font-weight:600;margin-top:auto;">Learn more →</div>
		</a>

	</div>
	<!-- /wp:html -->
</div>
<!-- /wp:group -->
