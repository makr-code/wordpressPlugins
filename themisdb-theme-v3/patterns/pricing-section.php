<?php
/**
 * Title: Pricing Section – Free vs Enterprise
 * Slug: themisdb-v3/pricing-section
 * Categories: themisdb-v3, themisdb-v3-landing
 * Viewport Width: 1280
 * Description: NEW in v3 – Azure-style pricing cards with Free (Community) and Enterprise edition, feature lists, and CTA buttons.
 */
?>
<!-- wp:group {"style":{"color":{"background":"#f5f7fa"},"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group" style="background-color:#f5f7fa;padding-top:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--20)">

	<!-- wp:html --><div style="text-align:center;margin-bottom:1rem"><span style="display:inline-block;background:#cfe4fc;color:#0078d4;border-radius:9999px;padding:0.25rem 0.875rem;font-size:0.75rem;font-weight:700;letter-spacing:0.06em;text-transform:uppercase">Pricing</span></div><!-- /wp:html -->

	<!-- wp:heading {"level":2,"textAlign":"center","style":{"typography":{"fontSize":"clamp(1.75rem,3.5vw,2.75rem)","fontWeight":"700","letterSpacing":"-0.03em"},"color":{"text":"#12202f"},"spacing":{"margin":{"bottom":"var:preset|spacing|4"}}}} -->
	<h2 class="wp-block-heading has-text-align-center" style="color:#12202f;font-size:clamp(1.75rem,3.5vw,2.75rem);font-weight:700;letter-spacing:-0.03em;margin-bottom:var(--wp--preset--spacing--4)">Start free. Scale with confidence.</h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"textAlign":"center","style":{"typography":{"fontSize":"1.0625rem"},"color":{"text":"#6c7f96"},"spacing":{"margin":{"bottom":"var:preset|spacing|12"}}}} -->
	<p class="has-text-align-center" style="color:#6c7f96;font-size:1.0625rem;margin-bottom:var(--wp--preset--spacing--12)">ThemisDB is open-source and MIT licensed. Enterprise support and SLA available for production.</p>
	<!-- /wp:paragraph -->

	<!-- wp:html -->
	<div class="tv3-pricing-grid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:1.5rem;align-items:start;">

		<!-- Community / Free -->
		<div style="background:#fff;border:1px solid #dde3ec;border-radius:12px;padding:2.5rem;">
			<div style="font-size:0.6875rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#0078d4;margin-bottom:0.875rem">Community</div>
			<div style="font-size:3rem;font-weight:800;color:#003366;letter-spacing:-0.04em;line-height:1;margin-bottom:0.25rem"><sup style="font-size:1.5rem;vertical-align:top;margin-top:0.5rem">$</sup>0</div>
			<div style="font-size:0.875rem;color:#6c7f96;margin-bottom:2rem;line-height:1.6">Free forever · MIT License<br>Self-hosted, no restrictions</div>
			<ul style="list-style:none;padding:0;margin:0 0 2.5rem">
				<li style="font-size:0.875rem;color:#3b4a5e;padding:0.5rem 0;border-bottom:1px solid #eef2f7;display:flex;align-items:center;gap:0.75rem"><span style="color:#107c10;font-weight:700;flex-shrink:0">✓</span> Full multi-model engine (SQL, JSON, Graph, TS)</li>
				<li style="font-size:0.875rem;color:#3b4a5e;padding:0.5rem 0;border-bottom:1px solid #eef2f7;display:flex;align-items:center;gap:0.75rem"><span style="color:#107c10;font-weight:700;flex-shrink:0">✓</span> Native AI/LLM integration</li>
				<li style="font-size:0.875rem;color:#3b4a5e;padding:0.5rem 0;border-bottom:1px solid #eef2f7;display:flex;align-items:center;gap:0.75rem"><span style="color:#107c10;font-weight:700;flex-shrink:0">✓</span> Docker + binary releases</li>
				<li style="font-size:0.875rem;color:#3b4a5e;padding:0.5rem 0;border-bottom:1px solid #eef2f7;display:flex;align-items:center;gap:0.75rem"><span style="color:#107c10;font-weight:700;flex-shrink:0">✓</span> Full documentation + examples</li>
				<li style="font-size:0.875rem;color:#3b4a5e;padding:0.5rem 0;border-bottom:1px solid #eef2f7;display:flex;align-items:center;gap:0.75rem"><span style="color:#107c10;font-weight:700;flex-shrink:0">✓</span> Community support (GitHub)</li>
				<li style="font-size:0.875rem;color:#6c7f96;padding:0.5rem 0;border-bottom:1px solid #eef2f7;display:flex;align-items:center;gap:0.75rem"><span style="color:#bdc8d8;font-weight:700;flex-shrink:0">–</span> Enterprise SLA</li>
				<li style="font-size:0.875rem;color:#6c7f96;padding:0.5rem 0;display:flex;align-items:center;gap:0.75rem"><span style="color:#bdc8d8;font-weight:700;flex-shrink:0">–</span> Priority engineering support</li>
			</ul>
			<a href="/downloads" style="display:block;text-align:center;background:transparent;color:#0078d4;border:1.5px solid #0078d4;border-radius:8px;padding:0.875rem;font-weight:700;font-size:0.9375rem;text-decoration:none;transition:all 0.2s">Download Free</a>
		</div>

		<!-- Enterprise (Featured) -->
		<div style="background:#fff;border:2px solid #0078d4;border-radius:12px;padding:2.5rem;box-shadow:0 4px 24px rgba(0,120,212,0.18);position:relative;">
			<div style="position:absolute;top:1rem;right:1rem;background:#0078d4;color:#fff;font-size:0.6875rem;font-weight:700;letter-spacing:0.05em;text-transform:uppercase;padding:0.2rem 0.75rem;border-radius:9999px">Most Popular</div>
			<div style="font-size:0.6875rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#0078d4;margin-bottom:0.875rem">Enterprise</div>
			<div style="font-size:3rem;font-weight:800;color:#003366;letter-spacing:-0.04em;line-height:1;margin-bottom:0.25rem">Custom</div>
			<div style="font-size:0.875rem;color:#6c7f96;margin-bottom:2rem;line-height:1.6">Tailored pricing<br>Annual or monthly billing</div>
			<ul style="list-style:none;padding:0;margin:0 0 2.5rem">
				<li style="font-size:0.875rem;color:#3b4a5e;padding:0.5rem 0;border-bottom:1px solid #eef2f7;display:flex;align-items:center;gap:0.75rem"><span style="color:#107c10;font-weight:700;flex-shrink:0">✓</span> Everything in Community</li>
				<li style="font-size:0.875rem;color:#3b4a5e;padding:0.5rem 0;border-bottom:1px solid #eef2f7;display:flex;align-items:center;gap:0.75rem"><span style="color:#107c10;font-weight:700;flex-shrink:0">✓</span> 99.99% SLA guarantee</li>
				<li style="font-size:0.875rem;color:#3b4a5e;padding:0.5rem 0;border-bottom:1px solid #eef2f7;display:flex;align-items:center;gap:0.75rem"><span style="color:#107c10;font-weight:700;flex-shrink:0">✓</span> Priority engineering support</li>
				<li style="font-size:0.875rem;color:#3b4a5e;padding:0.5rem 0;border-bottom:1px solid #eef2f7;display:flex;align-items:center;gap:0.75rem"><span style="color:#107c10;font-weight:700;flex-shrink:0">✓</span> Custom integrations &amp; SDKs</li>
				<li style="font-size:0.875rem;color:#3b4a5e;padding:0.5rem 0;border-bottom:1px solid #eef2f7;display:flex;align-items:center;gap:0.75rem"><span style="color:#107c10;font-weight:700;flex-shrink:0">✓</span> Security audit &amp; compliance</li>
				<li style="font-size:0.875rem;color:#3b4a5e;padding:0.5rem 0;border-bottom:1px solid #eef2f7;display:flex;align-items:center;gap:0.75rem"><span style="color:#107c10;font-weight:700;flex-shrink:0">✓</span> Dedicated Slack channel</li>
				<li style="font-size:0.875rem;color:#3b4a5e;padding:0.5rem 0;display:flex;align-items:center;gap:0.75rem"><span style="color:#107c10;font-weight:700;flex-shrink:0">✓</span> On-site training sessions</li>
			</ul>
			<a href="/contact" style="display:block;text-align:center;background:#0078d4;color:#fff;border:2px solid #0078d4;border-radius:8px;padding:0.875rem;font-weight:700;font-size:0.9375rem;text-decoration:none;transition:all 0.2s">Contact Sales →</a>
		</div>

		<!-- Cloud / Managed -->
		<div style="background:#fff;border:1px solid #dde3ec;border-radius:12px;padding:2.5rem;">
			<div style="font-size:0.6875rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#008080;margin-bottom:0.875rem">Cloud (Coming Soon)</div>
			<div style="font-size:3rem;font-weight:800;color:#003366;letter-spacing:-0.04em;line-height:1;margin-bottom:0.25rem">Soon</div>
			<div style="font-size:0.875rem;color:#6c7f96;margin-bottom:2rem;line-height:1.6">Managed cloud service<br>Usage-based pricing</div>
			<ul style="list-style:none;padding:0;margin:0 0 2.5rem">
				<li style="font-size:0.875rem;color:#3b4a5e;padding:0.5rem 0;border-bottom:1px solid #eef2f7;display:flex;align-items:center;gap:0.75rem"><span style="color:#107c10;font-weight:700;flex-shrink:0">✓</span> Zero-ops managed database</li>
				<li style="font-size:0.875rem;color:#3b4a5e;padding:0.5rem 0;border-bottom:1px solid #eef2f7;display:flex;align-items:center;gap:0.75rem"><span style="color:#107c10;font-weight:700;flex-shrink:0">✓</span> Automatic backups &amp; scaling</li>
				<li style="font-size:0.875rem;color:#3b4a5e;padding:0.5rem 0;border-bottom:1px solid #eef2f7;display:flex;align-items:center;gap:0.75rem"><span style="color:#107c10;font-weight:700;flex-shrink:0">✓</span> Multi-region replication</li>
				<li style="font-size:0.875rem;color:#3b4a5e;padding:0.5rem 0;border-bottom:1px solid #eef2f7;display:flex;align-items:center;gap:0.75rem"><span style="color:#107c10;font-weight:700;flex-shrink:0">✓</span> Pay per query / GB</li>
				<li style="font-size:0.875rem;color:#3b4a5e;padding:0.5rem 0;display:flex;align-items:center;gap:0.75rem"><span style="color:#107c10;font-weight:700;flex-shrink:0">✓</span> Azure, AWS, GCP regions</li>
			</ul>
			<a href="/newsletter" style="display:block;text-align:center;background:#f5f7fa;color:#3b4a5e;border:1.5px solid #dde3ec;border-radius:8px;padding:0.875rem;font-weight:700;font-size:0.9375rem;text-decoration:none;transition:all 0.2s">Join Waitlist →</a>
		</div>

	</div>
	<!-- /wp:html -->

</div>
<!-- /wp:group -->
