<?php
/**
 * Title: Feature Cards – 3×2 Grid (Azure Style)
 * Slug: themisdb-v3/feature-cards
 * Categories: themisdb-v3, themisdb-v3-landing
 * Viewport Width: 1280
 * Description: Six feature cards in a 3-column grid with Azure-style blue top border on hover.
 */
?>
<!-- wp:group {"className":"tv3-feature-section","layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group tv3-feature-section">

	<!-- wp:paragraph {"align":"center","className":"tv3-feature-section__badge-wrap"} -->
	<p class="has-text-align-center tv3-feature-section__badge-wrap"><span class="tv3-feature-section__badge">Features</span></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":2,"className":"tv3-feature-section__title"} -->
	<h2 class="wp-block-heading tv3-feature-section__title">Everything you need in one database</h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"className":"tv3-feature-section__lead"} -->
	<p class="tv3-feature-section__lead">Designed from the ground up for modern applications that need speed, flexibility, and intelligence.</p>
	<!-- /wp:paragraph -->

	<!-- wp:group {"className":"tv3-product-grid themis-v3-fade-in","layout":{"type":"default"}} -->
	<div class="wp-block-group tv3-product-grid themis-v3-fade-in">
		<!-- wp:group {"className":"tv3-product-card","layout":{"type":"constrained"}} -->
		<div class="wp-block-group tv3-product-card">
			<!-- wp:group {"className":"tv3-card-top-border tv3-card-top-border--blue","layout":{"type":"constrained"}} -->
			<div class="wp-block-group tv3-card-top-border tv3-card-top-border--blue"></div>
			<!-- /wp:group -->
			<!-- wp:paragraph {"className":"tv3-product-card__icon tv3-product-card__icon--blue"} -->
			<p class="tv3-product-card__icon tv3-product-card__icon--blue">🗄️</p>
			<!-- /wp:paragraph -->
			<!-- wp:group {"layout":{"type":"constrained"}} -->
			<div class="wp-block-group">
				<!-- wp:heading {"level":3,"className":"tv3-product-card__title"} -->
				<h3 class="wp-block-heading tv3-product-card__title">Multi-Model Storage</h3>
				<!-- /wp:heading -->
				<!-- wp:paragraph {"className":"tv3-product-card__desc"} -->
				<p class="tv3-product-card__desc">Relational, document, graph, time-series — one engine, one query language, zero compromise on consistency.</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
			<!-- wp:paragraph {"className":"tv3-product-card__cta"} -->
			<p class="tv3-product-card__cta"><a href="/features/multi-model">Learn more →</a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"tv3-product-card","layout":{"type":"constrained"}} -->
		<div class="wp-block-group tv3-product-card">
			<!-- wp:group {"className":"tv3-card-top-border tv3-card-top-border--navy","layout":{"type":"constrained"}} -->
			<div class="wp-block-group tv3-card-top-border tv3-card-top-border--navy"></div>
			<!-- /wp:group -->
			<!-- wp:paragraph {"className":"tv3-product-card__icon tv3-product-card__icon--navy"} -->
			<p class="tv3-product-card__icon tv3-product-card__icon--navy">🤖</p>
			<!-- /wp:paragraph -->
			<!-- wp:group {"layout":{"type":"constrained"}} -->
			<div class="wp-block-group">
				<!-- wp:heading {"level":3,"className":"tv3-product-card__title"} -->
				<h3 class="wp-block-heading tv3-product-card__title">Native AI/LLM Integration</h3>
				<!-- /wp:heading -->
				<!-- wp:paragraph {"className":"tv3-product-card__desc"} -->
				<p class="tv3-product-card__desc">Vector search, semantic queries, and LLM pipelines run natively inside the database engine.</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
			<!-- wp:paragraph {"className":"tv3-product-card__cta"} -->
			<p class="tv3-product-card__cta"><a href="/features/ai-integration">Learn more →</a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"tv3-product-card","layout":{"type":"constrained"}} -->
		<div class="wp-block-group tv3-product-card">
			<!-- wp:group {"className":"tv3-card-top-border tv3-card-top-border--green","layout":{"type":"constrained"}} -->
			<div class="wp-block-group tv3-card-top-border tv3-card-top-border--green"></div>
			<!-- /wp:group -->
			<!-- wp:paragraph {"className":"tv3-product-card__icon tv3-product-card__icon--green"} -->
			<p class="tv3-product-card__icon tv3-product-card__icon--green">⚡</p>
			<!-- /wp:paragraph -->
			<!-- wp:group {"layout":{"type":"constrained"}} -->
			<div class="wp-block-group">
				<!-- wp:heading {"level":3,"className":"tv3-product-card__title"} -->
				<h3 class="wp-block-heading tv3-product-card__title">Extreme Performance</h3>
				<!-- /wp:heading -->
				<!-- wp:paragraph {"className":"tv3-product-card__desc"} -->
				<p class="tv3-product-card__desc">Up to 10× faster than PostgreSQL. Independent benchmarks covering OLTP, OLAP, and vector workloads.</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
			<!-- wp:paragraph {"className":"tv3-product-card__cta"} -->
			<p class="tv3-product-card__cta"><a href="/benchmarks">View benchmarks →</a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"tv3-product-card","layout":{"type":"constrained"}} -->
		<div class="wp-block-group tv3-product-card">
			<!-- wp:group {"className":"tv3-card-top-border tv3-card-top-border--cyan","layout":{"type":"constrained"}} -->
			<div class="wp-block-group tv3-card-top-border tv3-card-top-border--cyan"></div>
			<!-- /wp:group -->
			<!-- wp:paragraph {"className":"tv3-product-card__icon tv3-product-card__icon--cyan"} -->
			<p class="tv3-product-card__icon tv3-product-card__icon--cyan">🐳</p>
			<!-- /wp:paragraph -->
			<!-- wp:group {"layout":{"type":"constrained"}} -->
			<div class="wp-block-group">
				<!-- wp:heading {"level":3,"className":"tv3-product-card__title"} -->
				<h3 class="wp-block-heading tv3-product-card__title">Docker Ready</h3>
				<!-- /wp:heading -->
				<!-- wp:paragraph {"className":"tv3-product-card__desc"} -->
				<p class="tv3-product-card__desc">Official images with one-line setup. Deploy on Kubernetes, Docker Compose, or standalone in 60 seconds.</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
			<!-- wp:paragraph {"className":"tv3-product-card__cta"} -->
			<p class="tv3-product-card__cta"><a href="/docker">Docker Hub →</a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"tv3-product-card","layout":{"type":"constrained"}} -->
		<div class="wp-block-group tv3-product-card">
			<!-- wp:group {"className":"tv3-card-top-border tv3-card-top-border--amber","layout":{"type":"constrained"}} -->
			<div class="wp-block-group tv3-card-top-border tv3-card-top-border--amber"></div>
			<!-- /wp:group -->
			<!-- wp:paragraph {"className":"tv3-product-card__icon tv3-product-card__icon--amber"} -->
			<p class="tv3-product-card__icon tv3-product-card__icon--amber">🧪</p>
			<!-- /wp:paragraph -->
			<!-- wp:group {"layout":{"type":"constrained"}} -->
			<div class="wp-block-group">
				<!-- wp:heading {"level":3,"className":"tv3-product-card__title"} -->
				<h3 class="wp-block-heading tv3-product-card__title">Interactive Query Playground</h3>
				<!-- /wp:heading -->
				<!-- wp:paragraph {"className":"tv3-product-card__desc"} -->
				<p class="tv3-product-card__desc">Write and execute SQL, JSON, and vector queries in-browser. No installation required.</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
			<!-- wp:paragraph {"className":"tv3-product-card__cta"} -->
			<p class="tv3-product-card__cta"><a href="/query-playground">Try it now →</a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"tv3-product-card","layout":{"type":"constrained"}} -->
		<div class="wp-block-group tv3-product-card">
			<!-- wp:group {"className":"tv3-card-top-border tv3-card-top-border--red","layout":{"type":"constrained"}} -->
			<div class="wp-block-group tv3-card-top-border tv3-card-top-border--red"></div>
			<!-- /wp:group -->
			<!-- wp:paragraph {"className":"tv3-product-card__icon tv3-product-card__icon--red"} -->
			<p class="tv3-product-card__icon tv3-product-card__icon--red">📊</p>
			<!-- /wp:paragraph -->
			<!-- wp:group {"layout":{"type":"constrained"}} -->
			<div class="wp-block-group">
				<!-- wp:heading {"level":3,"className":"tv3-product-card__title"} -->
				<h3 class="wp-block-heading tv3-product-card__title">Built-in Analytics</h3>
				<!-- /wp:heading -->
				<!-- wp:paragraph {"className":"tv3-product-card__desc"} -->
				<p class="tv3-product-card__desc">Real-time metrics, performance dashboards, and query analytics — no external monitoring tools needed.</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
			<!-- wp:paragraph {"className":"tv3-product-card__cta"} -->
			<p class="tv3-product-card__cta"><a href="/features/analytics">Learn more →</a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
