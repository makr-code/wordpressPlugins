<?php
/**
 * Title: Testimonials – 3 Cards
 * Slug: themisdb-v3/testimonial
 * Categories: themisdb-v3, themisdb-v3-landing
 * Viewport Width: 1280
 * Description: Three testimonial cards with avatar, quote, name, and company.
 */
?>
<!-- wp:group {"style":{"color":{"background":"#f5f7fa"},"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group" style="background-color:#f5f7fa;padding-top:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--20)">

	<!-- wp:html --><div style="text-align:center;margin-bottom:1rem"><span style="display:inline-block;background:#cfe4fc;color:#0078d4;border-radius:9999px;padding:0.25rem 0.875rem;font-size:0.75rem;font-weight:700;letter-spacing:0.06em;text-transform:uppercase">Testimonials</span></div><!-- /wp:html -->

	<!-- wp:heading {"level":2,"textAlign":"center","style":{"typography":{"fontSize":"clamp(1.75rem,3.5vw,2.75rem)","fontWeight":"700","letterSpacing":"-0.03em"},"color":{"text":"#12202f"},"spacing":{"margin":{"bottom":"var:preset|spacing|12"}}}} -->
	<h2 class="wp-block-heading has-text-align-center" style="color:#12202f;font-size:clamp(1.75rem,3.5vw,2.75rem);font-weight:700;letter-spacing:-0.03em;margin-bottom:var(--wp--preset--spacing--12)">Trusted by developers worldwide</h2>
	<!-- /wp:heading -->

	<!-- wp:html -->
	<div class="tv3-testimonial-grid themis-v3-fade-in" style="display:grid;grid-template-columns:repeat(3,1fr);gap:1.5rem;">

		<div class="tv3-testimonial-card" style="background:#fff;border:1px solid #dde3ec;border-radius:12px;padding:1.75rem;display:flex;flex-direction:column;">
			<div style="display:flex;gap:0.25rem;margin-bottom:1rem">
				<span style="color:#ffb900;font-size:0.875rem">★★★★★</span>
			</div>
			<blockquote style="font-size:1rem;color:#3b4a5e;line-height:1.7;margin:0 0 1.5rem;font-style:italic;flex:1">"ThemisDB cut our data pipeline complexity by 70%. Running vector search and relational joins in one SQL query is a genuine game-changer for our AI product."</blockquote>
			<div style="display:flex;align-items:center;gap:0.875rem;margin-top:auto">
				<div style="width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,#0078d4,#005a9e);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:1.125rem;flex-shrink:0">S</div>
				<div>
					<div style="font-weight:700;color:#12202f;font-size:0.875rem;line-height:1.3">Sarah Chen</div>
					<div style="font-size:0.8125rem;color:#6c7f96">Lead Engineer, FinTech Startup · San Francisco</div>
				</div>
			</div>
		</div>

		<div class="tv3-testimonial-card" style="background:#fff;border:1px solid #dde3ec;border-radius:12px;padding:1.75rem;display:flex;flex-direction:column;">
			<div style="display:flex;gap:0.25rem;margin-bottom:1rem">
				<span style="color:#ffb900;font-size:0.875rem">★★★★★</span>
			</div>
			<blockquote style="font-size:1rem;color:#3b4a5e;line-height:1.7;margin:0 0 1.5rem;font-style:italic;flex:1">"The 10× performance claim is real. We replaced three separate databases with ThemisDB and our average query latency dropped from 212ms to 18ms."</blockquote>
			<div style="display:flex;align-items:center;gap:0.875rem;margin-top:auto">
				<div style="width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,#107c10,#55b056);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:1.125rem;flex-shrink:0">M</div>
				<div>
					<div style="font-weight:700;color:#12202f;font-size:0.875rem;line-height:1.3">Marcus Weber</div>
					<div style="font-size:0.8125rem;color:#6c7f96">CTO, Enterprise SaaS Platform · Berlin</div>
				</div>
			</div>
		</div>

		<div class="tv3-testimonial-card" style="background:#fff;border:1px solid #dde3ec;border-radius:12px;padding:1.75rem;display:flex;flex-direction:column;">
			<div style="display:flex;gap:0.25rem;margin-bottom:1rem">
				<span style="color:#ffb900;font-size:0.875rem">★★★★★</span>
			</div>
			<blockquote style="font-size:1rem;color:#3b4a5e;line-height:1.7;margin:0 0 1.5rem;font-style:italic;flex:1">"The Docker setup was genuinely one command. Incredible DX. The ask_ai() SQL function alone saved us two weeks of LLM integration work."</blockquote>
			<div style="display:flex;align-items:center;gap:0.875rem;margin-top:auto">
				<div style="width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,#003366,#00509e);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:1.125rem;flex-shrink:0">P</div>
				<div>
					<div style="font-weight:700;color:#12202f;font-size:0.875rem;line-height:1.3">Priya Sharma</div>
					<div style="font-size:0.8125rem;color:#6c7f96">Senior Backend Developer · London</div>
				</div>
			</div>
		</div>

	</div>
	<!-- /wp:html -->

</div>
<!-- /wp:group -->
