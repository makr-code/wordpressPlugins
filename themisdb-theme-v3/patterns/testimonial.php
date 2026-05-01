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
<div class="wp-block-group tv3-testimonial-section">

	<!-- wp:html --><div class="tv3-testimonial-kicker-wrap"><span class="tv3-testimonial-kicker">Testimonials</span></div><!-- /wp:html -->

	<!-- wp:heading {"level":2,"textAlign":"center","style":{"typography":{"fontSize":"clamp(1.75rem,3.5vw,2.75rem)","fontWeight":"700","letterSpacing":"-0.03em"},"color":{"text":"#12202f"},"spacing":{"margin":{"bottom":"var:preset|spacing|12"}}}} -->
	<h2 class="wp-block-heading has-text-align-center tv3-testimonial-title">Trusted by developers worldwide</h2>
	<!-- /wp:heading -->

	<!-- wp:html -->
	<div class="tv3-testimonial-grid tv3-testimonial-grid-shell themis-v3-fade-in">

		<div class="tv3-testimonial-card">
			<div class="tv3-testimonial-stars">
				<span class="tv3-testimonial-stars-value">★★★★★</span>
			</div>
			<blockquote class="tv3-testimonial-quote">"ThemisDB cut our data pipeline complexity by 70%. Running vector search and relational joins in one SQL query is a genuine game-changer for our AI product."</blockquote>
			<div class="tv3-testimonial-person">
				<div class="tv3-testimonial-avatar tv3-testimonial-avatar-s">S</div>
				<div>
					<div class="tv3-testimonial-name">Sarah Chen</div>
					<div class="tv3-testimonial-role">Lead Engineer, FinTech Startup · San Francisco</div>
				</div>
			</div>
		</div>

		<div class="tv3-testimonial-card">
			<div class="tv3-testimonial-stars">
				<span class="tv3-testimonial-stars-value">★★★★★</span>
			</div>
			<blockquote class="tv3-testimonial-quote">"The 10× performance claim is real. We replaced three separate databases with ThemisDB and our average query latency dropped from 212ms to 18ms."</blockquote>
			<div class="tv3-testimonial-person">
				<div class="tv3-testimonial-avatar tv3-testimonial-avatar-m">M</div>
				<div>
					<div class="tv3-testimonial-name">Marcus Weber</div>
					<div class="tv3-testimonial-role">CTO, Enterprise SaaS Platform · Berlin</div>
				</div>
			</div>
		</div>

		<div class="tv3-testimonial-card">
			<div class="tv3-testimonial-stars">
				<span class="tv3-testimonial-stars-value">★★★★★</span>
			</div>
			<blockquote class="tv3-testimonial-quote">"The Docker setup was genuinely one command. Incredible DX. The ask_ai() SQL function alone saved us two weeks of LLM integration work."</blockquote>
			<div class="tv3-testimonial-person">
				<div class="tv3-testimonial-avatar tv3-testimonial-avatar-p">P</div>
				<div>
					<div class="tv3-testimonial-name">Priya Sharma</div>
					<div class="tv3-testimonial-role">Senior Backend Developer · London</div>
				</div>
			</div>
		</div>

	</div>
	<!-- /wp:html -->

</div>
<!-- /wp:group -->
