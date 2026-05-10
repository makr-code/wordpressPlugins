<?php
/**
 * Title: Tabs Section – Feature Showcase
 * Slug: themisdb-v3/tabs-section
 * Categories: themisdb-v3, themisdb-v3-landing
 * Viewport Width: 1280
 * Description: NEW in v3 – Tabbed feature section using jQuery UI Tabs (.themis-v3-tab-panel class). Shows Multi-Model, AI, and Performance tabs.
 */
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
	<p class="tv3-tabs-section__lead">Explore ThemisDB's core capabilities with interactive tabs powered by jQuery UI.</p>
	<!-- /wp:paragraph -->

	<!-- wp:group {"anchor":"tv3-features-tabs","className":"themis-v3-tabs tv3-feature-tabs","layout":{"type":"constrained"}} -->
	<div id="tv3-features-tabs" class="wp-block-group themis-v3-tabs tv3-feature-tabs">
		<ul class="tv3-feature-tabs__nav">
			<li><a href="#tv3-tab-multimodel" class="tv3-feature-tabs__link tv3-feature-tabs__link--active">🗄️ Multi-Model</a></li>
			<li><a href="#tv3-tab-ai" class="tv3-feature-tabs__link">🤖 AI/LLM</a></li>
			<li><a href="#tv3-tab-performance" class="tv3-feature-tabs__link">⚡ Performance</a></li>
			<li><a href="#tv3-tab-devops" class="tv3-feature-tabs__link">🐳 DevOps</a></li>
		</ul>

		<!-- Multi-Model Tab -->
		<div id="tv3-tab-multimodel" class="themis-v3-tab-panel tv3-feature-tabs__panel">
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
					<a href="/features/multi-model" class="tv3-feature-tabs__cta tv3-feature-tabs__cta--blue">Learn about Multi-Model →</a>
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
		</div>

		<!-- AI/LLM Tab -->
		<div id="tv3-tab-ai" class="themis-v3-tab-panel tv3-feature-tabs__panel tv3-feature-tabs__panel--hidden">
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
					<a href="/features/ai-integration" class="tv3-feature-tabs__cta tv3-feature-tabs__cta--navy">AI Integration Docs →</a>
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
		</div>

		<!-- Performance Tab -->
		<div id="tv3-tab-performance" class="themis-v3-tab-panel tv3-feature-tabs__panel tv3-feature-tabs__panel--hidden">
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
					<a href="/benchmarks" class="tv3-feature-tabs__cta tv3-feature-tabs__cta--green">See Full Benchmarks →</a>
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
		</div>

		<!-- DevOps Tab -->
		<div id="tv3-tab-devops" class="themis-v3-tab-panel tv3-feature-tabs__panel tv3-feature-tabs__panel--hidden">
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
					<a href="/docker" class="tv3-feature-tabs__cta tv3-feature-tabs__cta--cyan">Docker Documentation →</a>
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
		</div>

	</div>
	<!-- /wp:group -->

</div>
<!-- /wp:group -->
