<?php
/**
 * Title: Feature Cards – 3×2 Grid (Azure Style)
 * Slug: themisdb-v3/feature-cards
 * Categories: themisdb-v3, themisdb-v3-landing
 * Keywords: features, cards, capabilities, landing, grid
 * Viewport Width: 1280
 * Description: Six feature cards in a 3-column grid with Azure-style blue top border on hover.
 */
?>
<!-- wp:group {"className":"tv3-feature-section","layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group tv3-feature-section">
	<?php
	$feature_copy = function_exists( 'themisdb_v3_get_frontpage_section_copy' )
		? themisdb_v3_get_frontpage_section_copy(
			'features',
			array(
				'kicker'   => 'Features',
				'title'    => 'Everything you need in one database',
				'subtitle' => 'Designed from the ground up for modern applications that need speed, flexibility, and intelligence.',
			)
		)
		: array(
			'kicker'   => 'Features',
			'title'    => 'Everything you need in one database',
			'subtitle' => 'Designed from the ground up for modern applications that need speed, flexibility, and intelligence.',
		);
	?>

	<!-- wp:paragraph {"align":"center","className":"tv3-feature-section__badge-wrap"} -->
	<p class="has-text-align-center tv3-feature-section__badge-wrap"><span class="tv3-feature-section__badge"><?php echo esc_html( (string) $feature_copy['kicker'] ); ?></span></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":2,"className":"tv3-feature-section__title"} -->
	<h2 class="wp-block-heading tv3-feature-section__title"><?php echo esc_html( (string) $feature_copy['title'] ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"className":"tv3-feature-section__lead"} -->
	<p class="tv3-feature-section__lead"><?php echo esc_html( (string) $feature_copy['subtitle'] ); ?></p>
	<!-- /wp:paragraph -->

	<div class="wp-block-group tv3-feature-grid themis-v3-fade-in">
		<div class="wp-block-group tv3-feature-card">
			<div class="wp-block-group tv3-feature-card__top tv3-feature-card__top--blue"></div>
			<p class="tv3-feature-card__icon tv3-feature-card__icon--blue">&#128240;</p>
			<div class="wp-block-group tv3-feature-card__body">
				<h3 class="wp-block-heading tv3-feature-card__title">Vector Search &amp; Hybrid Retrieval</h3>
				<p class="tv3-feature-card__desc">Semantic retrieval, hybrid ranking and grounded AI context for modern database workloads.</p>
			</div>
			<p class="tv3-feature-card__cta"><a href="#vector-search-hybrid-retrieval">Mehr erfahren →</a></p>
		</div>
		<div class="wp-block-group tv3-feature-card">
			<div class="wp-block-group tv3-feature-card__top tv3-feature-card__top--navy"></div>
			<p class="tv3-feature-card__icon tv3-feature-card__icon--navy">&#129302;</p>
			<div class="wp-block-group tv3-feature-card__body">
				<h3 class="wp-block-heading tv3-feature-card__title">Graph-native Intelligence</h3>
				<p class="tv3-feature-card__desc">Connected entities and relationship-aware reasoning that keep multi-hop AI workflows explainable.</p>
			</div>
			<p class="tv3-feature-card__cta"><a href="#graph-native-intelligence">Mehr erfahren →</a></p>
		</div>
		<div class="wp-block-group tv3-feature-card">
			<div class="wp-block-group tv3-feature-card__top tv3-feature-card__top--green"></div>
			<p class="tv3-feature-card__icon tv3-feature-card__icon--green">&#9889;</p>
			<div class="wp-block-group tv3-feature-card__body">
				<h3 class="wp-block-heading tv3-feature-card__title">Operational Guardrails &amp; Observability</h3>
				<p class="tv3-feature-card__desc">Production-safe monitoring, recovery paths and audit visibility for AI-critical deployments.</p>
			</div>
			<p class="tv3-feature-card__cta"><a href="#operational-guardrails-observability">Mehr erfahren →</a></p>
		</div>
		<div class="wp-block-group tv3-feature-card">
			<div class="wp-block-group tv3-feature-card__top tv3-feature-card__top--cyan"></div>
			<p class="tv3-feature-card__icon tv3-feature-card__icon--cyan">&#128187;</p>
			<div class="wp-block-group tv3-feature-card__body">
				<h3 class="wp-block-heading tv3-feature-card__title">Hybrid Query Planning</h3>
				<p class="tv3-feature-card__desc">Batches of relational filters, semantic retrieval and cost-aware planning under one execution model.</p>
			</div>
			<p class="tv3-feature-card__cta"><a href="#hybrid-query-planning">Mehr erfahren →</a></p>
		</div>
		<div class="wp-block-group tv3-feature-card">
			<div class="wp-block-group tv3-feature-card__top tv3-feature-card__top--amber"></div>
			<p class="tv3-feature-card__icon tv3-feature-card__icon--amber">&#129513;</p>
			<div class="wp-block-group tv3-feature-card__body">
				<h3 class="wp-block-heading tv3-feature-card__title">Entity-Aware Governance</h3>
				<p class="tv3-feature-card__desc">Protect high-trust workloads with explainable policy checks, lineage and compliance-ready auditing.</p>
			</div>
			<p class="tv3-feature-card__cta"><a href="#entity-aware-governance">Mehr erfahren →</a></p>
		</div>
		<div class="wp-block-group tv3-feature-card">
			<div class="wp-block-group tv3-feature-card__top tv3-feature-card__top--red"></div>
			<p class="tv3-feature-card__icon tv3-feature-card__icon--red">&#128202;</p>
			<div class="wp-block-group tv3-feature-card__body">
				<h3 class="wp-block-heading tv3-feature-card__title">AI-Ready Deployment Paths</h3>
				<p class="tv3-feature-card__desc">Ship production-grade inference and retrieval landscapes without sacrificing control, resilience or speed.</p>
			</div>
			<p class="tv3-feature-card__cta"><a href="#ai-ready-deployment-paths">Mehr erfahren →</a></p>
		</div>
	</div>
</div>
<!-- /wp:group -->
