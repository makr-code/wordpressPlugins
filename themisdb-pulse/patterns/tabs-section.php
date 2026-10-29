<?php
/**
 * Title: Tabs Section – Feature Showcase
 * Slug: themisdb-v3/tabs-section
 * Categories: themisdb-v3, themisdb-v3-landing
 * Keywords: tabs, features, showcase, interaction, capabilities
 * Viewport Width: 1280
 * Description: Feature section using the native WordPress Tabs block.
 */
$get_page_link = static function ( $path, $class, $label ) {
	$page = get_page_by_path( $path, OBJECT, get_post_types( array( 'public' => true ) ) );
	if ( ! $page ) {
		return '';
	}

	return sprintf(
		'<a href="%1$s" class="%2$s">%3$s</a>',
		esc_url( get_permalink( $page ) ),
		esc_attr( $class ),
		esc_html( $label )
	);
};

$multi_model_link = $get_page_link( 'features/multi-model', 'tv3-feature-tabs__cta tv3-feature-tabs__cta--blue', 'Learn about Multi-Model →' );
$ai_integration_link = $get_page_link( 'features/ai-integration', 'tv3-feature-tabs__cta tv3-feature-tabs__cta--navy', 'AI Integration Docs →' );
$benchmarks_link = $get_page_link( 'benchmarks', 'tv3-feature-tabs__cta tv3-feature-tabs__cta--green', 'See Full Benchmarks →' );
$docker_link = $get_page_link( 'docker', 'tv3-feature-tabs__cta tv3-feature-tabs__cta--cyan', 'Docker Documentation →' );
?>
<!-- wp:group {"className":"tv3-tabs-section","layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group tv3-tabs-section">

	<!-- wp:paragraph {"align":"center","className":"tv3-tabs-section__badge-wrap"} -->
	<p class="has-text-align-center tv3-tabs-section__badge-wrap"><span class="tv3-tabs-section__badge">Capabilities</span></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":2,"className":"tv3-tabs-section__title"} -->
	<h2 class="wp-block-heading tv3-tabs-section__title">Built for every use case</h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"className":"tv3-tabs-section__lead"} -->
	<p class="tv3-tabs-section__lead">Explore ThemisDB's core capabilities with interactive tabs.</p>
	<!-- /wp:paragraph -->

	<!-- wp:tabs {"anchor":"tv3-features-tabs","className":"tv3-feature-tabs"} -->
	<div id="tv3-features-tabs" class="wp-block-tabs tv3-feature-tabs">
		<!-- wp:tab-list {"className":"tv3-feature-tabs__nav","ariaLabel":"ThemisDB capabilities"} -->
		<div role="tablist" aria-label="ThemisDB capabilities" class="wp-block-tab-list tv3-feature-tabs__nav">
			<button>🗄️ Multi-Model</button>
			<button>🤖 AI/LLM</button>
			<button>⚡ Performance</button>
			<button>🐳 DevOps</button>
		</div>
		<!-- /wp:tab-list -->

		<!-- wp:tab-panels -->
		<div class="wp-block-tab-panels">
		<!-- wp:tab-panel {"label":"Multi-Model","className":"tv3-feature-tabs__panel"} -->
		<div class="wp-block-tab-panel tv3-feature-tabs__panel">
		<!-- wp:html -->
			<div class="tv3-feature-tabs__layout">
				<div>
					<h3 class="tv3-feature-tabs__h3">One engine for all your data</h3>
					<p class="tv3-feature-tabs__text">Stop juggling multiple databases. ThemisDB handles relational tables, JSON documents, graph relationships, and time-series data — all in one system with ACID guarantees.</p>
					<div class="tv3-feature-tabs__icon-grid">
						<div class="tv3-feature-tabs__icon-item"><span class="tv3-feature-tabs__emoji">📋</span><span class="tv3-feature-tabs__label">Relational</span></div>
						<div class="tv3-feature-tabs__icon-item"><span class="tv3-feature-tabs__emoji">📄</span><span class="tv3-feature-tabs__label">Document</span></div>
						<div class="tv3-feature-tabs__icon-item"><span class="tv3-feature-tabs__emoji">🕸️</span><span class="tv3-feature-tabs__label">Graph</span></div>
						<div class="tv3-feature-tabs__icon-item"><span class="tv3-feature-tabs__emoji">📈</span><span class="tv3-feature-tabs__label">Time-Series</span></div>
					</div>
					<?php echo $multi_model_link; ?>
				</div>
				<div class="tv3-feature-tabs__codebox">
					<div class="tv3-feature-tabs__code-title">Example: Cross-model join</div>
					<div><span class="tv3-code-keyword">SELECT</span> u.name, doc.preferences,</div>
					<div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; g.friend_count, ts.last_active</div>
					<div><span class="tv3-code-keyword">FROM</span>   users u</div>
					<div><span class="tv3-code-keyword">JOIN</span>   user_docs doc <span class="tv3-code-keyword">ON</span> doc.user_id = u.id</div>
					<div><span class="tv3-code-keyword">JOIN</span>   social g <span class="tv3-code-keyword">ON</span> g.node = u.id</div>
					<div><span class="tv3-code-keyword">JOIN</span>   activity ts <span class="tv3-code-keyword">ON</span> ts.user_id = u.id</div>
					<div><span class="tv3-code-keyword">WHERE</span>  u.active = <span class="tv3-code-string">true</span>;</div>
				</div>
			</div>
		<!-- /wp:html -->
		</div>
		<!-- /wp:tab-panel -->

		<!-- wp:tab-panel {"label":"AI/LLM","className":"tv3-feature-tabs__panel"} -->
		<div class="wp-block-tab-panel tv3-feature-tabs__panel">
		<!-- wp:html -->
			<div class="tv3-feature-tabs__layout">
				<div>
					<h3 class="tv3-feature-tabs__h3">AI at the database layer</h3>
					<p class="tv3-feature-tabs__text">Run vector embeddings, semantic search, and LLM inference directly in SQL. No external pipelines, no data movement — AI where your data lives.</p>
					<ul class="tv3-feature-tabs__list">
						<li class="tv3-feature-tabs__list-item"><span class="tv3-feature-tabs__check">✓</span> pgvector-compatible embeddings</li>
						<li class="tv3-feature-tabs__list-item"><span class="tv3-feature-tabs__check">✓</span> ask_ai() SQL function (GPT-4o, Claude, Llama)</li>
						<li class="tv3-feature-tabs__list-item"><span class="tv3-feature-tabs__check">✓</span> Semantic similarity operators</li>
						<li class="tv3-feature-tabs__list-item tv3-feature-tabs__list-item--last"><span class="tv3-feature-tabs__check">✓</span> Real-time streaming via WebSocket</li>
					</ul>
					<?php echo $ai_integration_link; ?>
				</div>
				<div class="tv3-feature-tabs__codebox">
					<div class="tv3-feature-tabs__code-title">Example: LLM-powered query</div>
					<div><span class="tv3-code-keyword">SELECT</span></div>
					<div>&nbsp;&nbsp;title,</div>
					<div>&nbsp;&nbsp;<span class="tv3-code-number">ask_ai</span>(content,</div>
					<div>&nbsp;&nbsp;&nbsp;&nbsp;<span class="tv3-code-string">'Summarize in 2 sentences'</span>,</div>
					<div>&nbsp;&nbsp;&nbsp;&nbsp;model := <span class="tv3-code-string">'gpt-4o'</span>) <span class="tv3-code-keyword">AS</span> summary</div>
					<div><span class="tv3-code-keyword">FROM</span> articles</div>
					<div><span class="tv3-code-keyword">WHERE</span> content <span class="tv3-code-number">&lt;-&gt;</span> embed(<span class="tv3-code-keyword">$1</span>) &lt; <span class="tv3-code-number">0.2</span>;</div>
				</div>
			</div>
		<!-- /wp:html -->
		</div>
		<!-- /wp:tab-panel -->

		<!-- wp:tab-panel {"label":"Performance","className":"tv3-feature-tabs__panel"} -->
		<div class="wp-block-tab-panel tv3-feature-tabs__panel">
		<!-- wp:html -->
			<div class="tv3-feature-tabs__layout">
				<div>
					<h3 class="tv3-feature-tabs__h3">10× faster. Not a marketing claim.</h3>
					<p class="tv3-feature-tabs__text">ThemisDB uses a columnar storage engine, vectorized execution, and adaptive query planning to outperform PostgreSQL on every workload class.</p>
					<div class="tv3-feature-tabs__metrics">
						<div class="tv3-feature-tabs__metric">
							<div class="tv3-feature-tabs__metric-head"><span class="tv3-feature-tabs__metric-label">OLTP (transactions/sec)</span><span class="tv3-feature-tabs__metric-gain">+840%</span></div>
							<div class="tv3-feature-tabs__meter"><div class="tv3-feature-tabs__meter-fill tv3-feature-tabs__meter-fill--90"></div></div>
						</div>
						<div class="tv3-feature-tabs__metric">
							<div class="tv3-feature-tabs__metric-head"><span class="tv3-feature-tabs__metric-label">OLAP (analytical queries)</span><span class="tv3-feature-tabs__metric-gain">+1200%</span></div>
							<div class="tv3-feature-tabs__meter"><div class="tv3-feature-tabs__meter-fill tv3-feature-tabs__meter-fill--95"></div></div>
						</div>
						<div class="tv3-feature-tabs__metric tv3-feature-tabs__metric--last">
							<div class="tv3-feature-tabs__metric-head"><span class="tv3-feature-tabs__metric-label">Vector similarity search</span><span class="tv3-feature-tabs__metric-gain">+2500%</span></div>
							<div class="tv3-feature-tabs__meter"><div class="tv3-feature-tabs__meter-fill tv3-feature-tabs__meter-fill--98"></div></div>
						</div>
					</div>
					<?php echo $benchmarks_link; ?>
				</div>
				<div class="tv3-feature-tabs__benchmark-box">
					<div class="tv3-feature-tabs__benchmark-title">Query Latency (ms) – 1M rows</div>
					<div class="tv3-feature-tabs__benchmark-list">
						<div class="tv3-feature-tabs__benchmark-row">
							<span class="tv3-feature-tabs__benchmark-name">ThemisDB v3</span>
							<div class="tv3-feature-tabs__benchmark-track">
								<div class="tv3-feature-tabs__benchmark-fill tv3-feature-tabs__benchmark-fill--12 tv3-feature-tabs__benchmark-fill--primary">
									<span class="tv3-feature-tabs__benchmark-pill">18ms</span>
								</div>
							</div>
						</div>
						<div class="tv3-feature-tabs__benchmark-row">
							<span class="tv3-feature-tabs__benchmark-name">PostgreSQL 16</span>
							<div class="tv3-feature-tabs__benchmark-track">
								<div class="tv3-feature-tabs__benchmark-fill tv3-feature-tabs__benchmark-fill--78 tv3-feature-tabs__benchmark-fill--secondary">
									<span class="tv3-feature-tabs__benchmark-pill">212ms</span>
								</div>
							</div>
						</div>
						<div class="tv3-feature-tabs__benchmark-row">
							<span class="tv3-feature-tabs__benchmark-name">MongoDB 7</span>
							<div class="tv3-feature-tabs__benchmark-track">
								<div class="tv3-feature-tabs__benchmark-fill tv3-feature-tabs__benchmark-fill--60 tv3-feature-tabs__benchmark-fill--tertiary">
									<span class="tv3-feature-tabs__benchmark-pill">158ms</span>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		<!-- /wp:html -->
		</div>
		<!-- /wp:tab-panel -->

		<!-- wp:tab-panel {"label":"DevOps","className":"tv3-feature-tabs__panel"} -->
		<div class="wp-block-tab-panel tv3-feature-tabs__panel">
		<!-- wp:html -->
			<div class="tv3-feature-tabs__layout">
				<div>
					<h3 class="tv3-feature-tabs__h3">Deploy anywhere in minutes</h3>
					<p class="tv3-feature-tabs__text">Official Docker images, Helm charts, and Kubernetes operators. ThemisDB runs on any infrastructure — from a Raspberry Pi to a 64-core production cluster.</p>
					<ul class="tv3-feature-tabs__list">
						<li class="tv3-feature-tabs__list-item"><span class="tv3-feature-tabs__check">✓</span> Docker Hub official image</li>
						<li class="tv3-feature-tabs__list-item"><span class="tv3-feature-tabs__check">✓</span> Kubernetes operator + Helm chart</li>
						<li class="tv3-feature-tabs__list-item"><span class="tv3-feature-tabs__check">✓</span> Prometheus metrics + Grafana dashboards</li>
						<li class="tv3-feature-tabs__list-item tv3-feature-tabs__list-item--last"><span class="tv3-feature-tabs__check">✓</span> GitHub Actions + CI/CD integration</li>
					</ul>
					<?php echo $docker_link; ?>
				</div>
				<div class="tv3-feature-tabs__codebox">
					<div class="tv3-feature-tabs__code-title">docker-compose.yml</div>
					<div><span class="tv3-code-number">version</span>: <span class="tv3-code-string">'3.9'</span></div>
					<div><span class="tv3-code-number">services</span>:</div>
					<div>&nbsp;&nbsp;<span class="tv3-code-keyword">themisdb</span>:</div>
					<div>&nbsp;&nbsp;&nbsp;&nbsp;<span class="tv3-code-number">image</span>: themisdb/themisdb:v3-latest</div>
					<div>&nbsp;&nbsp;&nbsp;&nbsp;<span class="tv3-code-number">ports</span>: [<span class="tv3-code-string">"5432:5432"</span>]</div>
					<div>&nbsp;&nbsp;&nbsp;&nbsp;<span class="tv3-code-number">environment</span>:</div>
					<div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;THEMISDB_PASSWORD: <span class="tv3-code-string">secret</span></div>
					<div>&nbsp;&nbsp;&nbsp;&nbsp;<span class="tv3-code-number">volumes</span>:</div>
					<div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- <span class="tv3-code-string">themisdb_data:/data</span></div>
				</div>
			</div>
		<!-- /wp:html -->
		</div>
		<!-- /wp:tab-panel -->
		</div>
		<!-- /wp:tab-panels -->
	</div>
	<!-- /wp:tabs -->

</div>
<!-- /wp:group -->
