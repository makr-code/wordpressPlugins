<?php
/**
 * Title: Tabs Section – Feature Showcase
 * Slug: themisdb-v3/tabs-section
 * Categories: themisdb-v3, themisdb-v3-landing
 * Viewport Width: 1280
 * Description: NEW in v3 – Tabbed feature section using jQuery UI Tabs (.themis-v3-tab-panel class). Shows Multi-Model, AI, and Performance tabs.
 */
?>
<!-- wp:group {"style":{"color":{"background":"#ffffff"},"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group" style="background-color:#ffffff;padding-top:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--20)">

	<!-- wp:html --><div style="text-align:center;margin-bottom:1rem"><span style="display:inline-block;background:#cfe4fc;color:#0078d4;border-radius:9999px;padding:0.25rem 0.875rem;font-size:0.75rem;font-weight:700;letter-spacing:0.06em;text-transform:uppercase">Capabilities</span></div><!-- /wp:html -->

	<!-- wp:heading {"level":2,"textAlign":"center","style":{"typography":{"fontSize":"clamp(1.75rem,3.5vw,2.75rem)","fontWeight":"700","letterSpacing":"-0.03em"},"color":{"text":"#12202f"},"spacing":{"margin":{"bottom":"var:preset|spacing|4"}}}} -->
	<h2 class="wp-block-heading has-text-align-center" style="color:#12202f;font-size:clamp(1.75rem,3.5vw,2.75rem);font-weight:700;letter-spacing:-0.03em;margin-bottom:var(--wp--preset--spacing--4)">Built for every use case</h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"textAlign":"center","style":{"typography":{"fontSize":"1.0625rem"},"color":{"text":"#6c7f96"},"spacing":{"margin":{"bottom":"var:preset|spacing|10"}}}} -->
	<p class="has-text-align-center" style="color:#6c7f96;font-size:1.0625rem;margin-bottom:var(--wp--preset--spacing--10)">Explore ThemisDB's core capabilities with interactive tabs powered by jQuery UI.</p>
	<!-- /wp:paragraph -->

	<!-- wp:html -->
	<div id="tv3-features-tabs" class="themis-v3-tabs" style="border:1px solid #dde3ec;border-radius:12px;overflow:hidden;background:#fff;">
		<ul style="list-style:none;padding:0;margin:0;display:flex;background:#f5f7fa;border-bottom:1px solid #dde3ec;overflow-x:auto;">
			<li><a href="#tv3-tab-multimodel" style="display:block;padding:1rem 1.5rem;font-size:0.9rem;font-weight:600;color:#0078d4;text-decoration:none;border-bottom:2px solid #0078d4;white-space:nowrap;">🗄️ Multi-Model</a></li>
			<li><a href="#tv3-tab-ai" style="display:block;padding:1rem 1.5rem;font-size:0.9rem;font-weight:600;color:#6c7f96;text-decoration:none;border-bottom:2px solid transparent;white-space:nowrap;">🤖 AI/LLM</a></li>
			<li><a href="#tv3-tab-performance" style="display:block;padding:1rem 1.5rem;font-size:0.9rem;font-weight:600;color:#6c7f96;text-decoration:none;border-bottom:2px solid transparent;white-space:nowrap;">⚡ Performance</a></li>
			<li><a href="#tv3-tab-devops" style="display:block;padding:1rem 1.5rem;font-size:0.9rem;font-weight:600;color:#6c7f96;text-decoration:none;border-bottom:2px solid transparent;white-space:nowrap;">🐳 DevOps</a></li>
		</ul>

		<!-- Multi-Model Tab -->
		<div id="tv3-tab-multimodel" class="themis-v3-tab-panel" style="padding:3rem;">
			<div style="display:grid;grid-template-columns:1fr 1fr;gap:3rem;align-items:center;">
				<div>
					<h3 style="font-size:1.75rem;font-weight:700;color:#12202f;letter-spacing:-0.02em;margin-bottom:1rem">One engine for all your data</h3>
					<p style="font-size:1rem;color:#6c7f96;line-height:1.7;margin-bottom:1.5rem">Stop juggling multiple databases. ThemisDB handles relational tables, JSON documents, graph relationships, and time-series data — all in one system with ACID guarantees.</p>
					<div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-bottom:1.5rem">
						<div style="background:#f5f7fa;border-radius:8px;padding:0.875rem;display:flex;align-items:center;gap:0.5rem"><span style="font-size:1.25rem">📋</span><span style="font-size:0.875rem;font-weight:600;color:#12202f">Relational</span></div>
						<div style="background:#f5f7fa;border-radius:8px;padding:0.875rem;display:flex;align-items:center;gap:0.5rem"><span style="font-size:1.25rem">📄</span><span style="font-size:0.875rem;font-weight:600;color:#12202f">Document</span></div>
						<div style="background:#f5f7fa;border-radius:8px;padding:0.875rem;display:flex;align-items:center;gap:0.5rem"><span style="font-size:1.25rem">🕸️</span><span style="font-size:0.875rem;font-weight:600;color:#12202f">Graph</span></div>
						<div style="background:#f5f7fa;border-radius:8px;padding:0.875rem;display:flex;align-items:center;gap:0.5rem"><span style="font-size:1.25rem">📈</span><span style="font-size:0.875rem;font-weight:600;color:#12202f">Time-Series</span></div>
					</div>
					<a href="/features/multi-model" style="display:inline-block;background:#0078d4;color:#fff;border-radius:8px;padding:0.75rem 1.5rem;font-weight:700;font-size:0.9375rem;text-decoration:none">Learn about Multi-Model →</a>
				</div>
				<div style="background:linear-gradient(135deg,#001a33,#003366);border-radius:12px;padding:1.75rem;font-family:'Cascadia Code',Consolas,monospace;font-size:0.85rem;color:#cdd9e5;line-height:1.9;">
					<div style="color:#6c7f96;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.75rem">Example: Cross-model join</div>
					<div><span style="color:#0078d4">SELECT</span> u.name, doc.preferences,</div>
					<div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; g.friend_count, ts.last_active</div>
					<div><span style="color:#0078d4">FROM</span>   users u</div>
					<div><span style="color:#0078d4">JOIN</span>   user_docs doc <span style="color:#0078d4">ON</span> doc.user_id = u.id</div>
					<div><span style="color:#0078d4">JOIN</span>   social g <span style="color:#0078d4">ON</span> g.node = u.id</div>
					<div><span style="color:#0078d4">JOIN</span>   activity ts <span style="color:#0078d4">ON</span> ts.user_id = u.id</div>
					<div><span style="color:#0078d4">WHERE</span>  u.active = <span style="color:#55b056">true</span>;</div>
				</div>
			</div>
		</div>

		<!-- AI/LLM Tab -->
		<div id="tv3-tab-ai" class="themis-v3-tab-panel" style="padding:3rem;display:none;">
			<div style="display:grid;grid-template-columns:1fr 1fr;gap:3rem;align-items:center;">
				<div>
					<h3 style="font-size:1.75rem;font-weight:700;color:#12202f;letter-spacing:-0.02em;margin-bottom:1rem">AI at the database layer</h3>
					<p style="font-size:1rem;color:#6c7f96;line-height:1.7;margin-bottom:1.5rem">Run vector embeddings, semantic search, and LLM inference directly in SQL. No external pipelines, no data movement — AI where your data lives.</p>
					<ul style="list-style:none;padding:0;margin:0 0 1.5rem">
						<li style="display:flex;align-items:center;gap:0.75rem;padding:0.5rem 0;border-bottom:1px solid #eef2f7;font-size:0.9rem;color:#3b4a5e"><span style="color:#107c10;font-weight:700">✓</span> pgvector-compatible embeddings</li>
						<li style="display:flex;align-items:center;gap:0.75rem;padding:0.5rem 0;border-bottom:1px solid #eef2f7;font-size:0.9rem;color:#3b4a5e"><span style="color:#107c10;font-weight:700">✓</span> ask_ai() SQL function (GPT-4o, Claude, Llama)</li>
						<li style="display:flex;align-items:center;gap:0.75rem;padding:0.5rem 0;border-bottom:1px solid #eef2f7;font-size:0.9rem;color:#3b4a5e"><span style="color:#107c10;font-weight:700">✓</span> Semantic similarity operators</li>
						<li style="display:flex;align-items:center;gap:0.75rem;padding:0.5rem 0;font-size:0.9rem;color:#3b4a5e"><span style="color:#107c10;font-weight:700">✓</span> Real-time streaming via WebSocket</li>
					</ul>
					<a href="/features/ai-integration" style="display:inline-block;background:#003366;color:#fff;border-radius:8px;padding:0.75rem 1.5rem;font-weight:700;font-size:0.9375rem;text-decoration:none">AI Integration Docs →</a>
				</div>
				<div style="background:linear-gradient(135deg,#001a33,#003366);border-radius:12px;padding:1.75rem;font-family:'Cascadia Code',Consolas,monospace;font-size:0.85rem;color:#cdd9e5;line-height:1.9;">
					<div style="color:#6c7f96;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.75rem">Example: LLM-powered query</div>
					<div><span style="color:#0078d4">SELECT</span></div>
					<div>&nbsp;&nbsp;title,</div>
					<div>&nbsp;&nbsp;<span style="color:#50e6ff">ask_ai</span>(content,</div>
					<div>&nbsp;&nbsp;&nbsp;&nbsp;<span style="color:#55b056">'Summarize in 2 sentences'</span>,</div>
					<div>&nbsp;&nbsp;&nbsp;&nbsp;model := <span style="color:#55b056">'gpt-4o'</span>) <span style="color:#0078d4">AS</span> summary</div>
					<div><span style="color:#0078d4">FROM</span> articles</div>
					<div><span style="color:#0078d4">WHERE</span> content <span style="color:#50e6ff">&lt;-&gt;</span> embed(<span style="color:#0078d4">$1</span>) &lt; <span style="color:#50e6ff">0.2</span>;</div>
				</div>
			</div>
		</div>

		<!-- Performance Tab -->
		<div id="tv3-tab-performance" class="themis-v3-tab-panel" style="padding:3rem;display:none;">
			<div style="display:grid;grid-template-columns:1fr 1fr;gap:3rem;align-items:center;">
				<div>
					<h3 style="font-size:1.75rem;font-weight:700;color:#12202f;letter-spacing:-0.02em;margin-bottom:1rem">10× faster. Not a marketing claim.</h3>
					<p style="font-size:1rem;color:#6c7f96;line-height:1.7;margin-bottom:1.5rem">ThemisDB uses a columnar storage engine, vectorized execution, and adaptive query planning to outperform PostgreSQL on every workload class.</p>
					<div style="margin-bottom:1.5rem">
						<div style="margin-bottom:0.75rem">
							<div style="display:flex;justify-content:space-between;margin-bottom:0.25rem"><span style="font-size:0.8125rem;font-weight:600;color:#12202f">OLTP (transactions/sec)</span><span style="font-size:0.8125rem;font-weight:700;color:#107c10">+840%</span></div>
							<div style="background:#eef2f7;border-radius:100px;height:8px;overflow:hidden"><div style="height:100%;background:linear-gradient(135deg,#0078d4,#50e6ff);border-radius:100px;width:90%"></div></div>
						</div>
						<div style="margin-bottom:0.75rem">
							<div style="display:flex;justify-content:space-between;margin-bottom:0.25rem"><span style="font-size:0.8125rem;font-weight:600;color:#12202f">OLAP (analytical queries)</span><span style="font-size:0.8125rem;font-weight:700;color:#107c10">+1200%</span></div>
							<div style="background:#eef2f7;border-radius:100px;height:8px;overflow:hidden"><div style="height:100%;background:linear-gradient(135deg,#0078d4,#50e6ff);border-radius:100px;width:95%"></div></div>
						</div>
						<div>
							<div style="display:flex;justify-content:space-between;margin-bottom:0.25rem"><span style="font-size:0.8125rem;font-weight:600;color:#12202f">Vector similarity search</span><span style="font-size:0.8125rem;font-weight:700;color:#107c10">+2500%</span></div>
							<div style="background:#eef2f7;border-radius:100px;height:8px;overflow:hidden"><div style="height:100%;background:linear-gradient(135deg,#0078d4,#50e6ff);border-radius:100px;width:98%"></div></div>
						</div>
					</div>
					<a href="/benchmarks" style="display:inline-block;background:#107c10;color:#fff;border-radius:8px;padding:0.75rem 1.5rem;font-weight:700;font-size:0.9375rem;text-decoration:none">See Full Benchmarks →</a>
				</div>
				<div style="background:#f5f7fa;border:1px solid #dde3ec;border-radius:12px;padding:2rem">
					<div style="font-size:0.875rem;font-weight:700;color:#12202f;margin-bottom:1.25rem;text-align:center">Query Latency (ms) – 1M rows</div>
					<div style="display:flex;flex-direction:column;gap:0.75rem">
						<div style="display:flex;align-items:center;gap:1rem">
							<span style="font-size:0.8125rem;color:#3b4a5e;width:100px;flex-shrink:0">ThemisDB v3</span>
							<div style="flex:1;background:#eef2f7;border-radius:100px;height:28px;overflow:hidden;position:relative">
								<div style="height:100%;background:linear-gradient(135deg,#0078d4,#50e6ff);border-radius:100px;width:12%;display:flex;align-items:center;justify-content:center">
									<span style="font-size:0.75rem;color:#fff;font-weight:700;white-space:nowrap;padding:0 0.5rem">18ms</span>
								</div>
							</div>
						</div>
						<div style="display:flex;align-items:center;gap:1rem">
							<span style="font-size:0.8125rem;color:#3b4a5e;width:100px;flex-shrink:0">PostgreSQL 16</span>
							<div style="flex:1;background:#eef2f7;border-radius:100px;height:28px;overflow:hidden;position:relative">
								<div style="height:100%;background:linear-gradient(135deg,#6c7f96,#8fa3bc);border-radius:100px;width:78%;display:flex;align-items:center;justify-content:center">
									<span style="font-size:0.75rem;color:#fff;font-weight:700;white-space:nowrap;padding:0 0.5rem">212ms</span>
								</div>
							</div>
						</div>
						<div style="display:flex;align-items:center;gap:1rem">
							<span style="font-size:0.8125rem;color:#3b4a5e;width:100px;flex-shrink:0">MongoDB 7</span>
							<div style="flex:1;background:#eef2f7;border-radius:100px;height:28px;overflow:hidden;position:relative">
								<div style="height:100%;background:linear-gradient(135deg,#8fa3bc,#bdc8d8);border-radius:100px;width:60%;display:flex;align-items:center;justify-content:center">
									<span style="font-size:0.75rem;color:#fff;font-weight:700;white-space:nowrap;padding:0 0.5rem">158ms</span>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- DevOps Tab -->
		<div id="tv3-tab-devops" class="themis-v3-tab-panel" style="padding:3rem;display:none;">
			<div style="display:grid;grid-template-columns:1fr 1fr;gap:3rem;align-items:center;">
				<div>
					<h3 style="font-size:1.75rem;font-weight:700;color:#12202f;letter-spacing:-0.02em;margin-bottom:1rem">Deploy anywhere in minutes</h3>
					<p style="font-size:1rem;color:#6c7f96;line-height:1.7;margin-bottom:1.5rem">Official Docker images, Helm charts, and Kubernetes operators. ThemisDB runs on any infrastructure — from a Raspberry Pi to a 64-core production cluster.</p>
					<ul style="list-style:none;padding:0;margin:0 0 1.5rem">
						<li style="display:flex;align-items:center;gap:0.75rem;padding:0.5rem 0;border-bottom:1px solid #eef2f7;font-size:0.9rem;color:#3b4a5e"><span style="color:#107c10;font-weight:700">✓</span> Docker Hub official image</li>
						<li style="display:flex;align-items:center;gap:0.75rem;padding:0.5rem 0;border-bottom:1px solid #eef2f7;font-size:0.9rem;color:#3b4a5e"><span style="color:#107c10;font-weight:700">✓</span> Kubernetes operator + Helm chart</li>
						<li style="display:flex;align-items:center;gap:0.75rem;padding:0.5rem 0;border-bottom:1px solid #eef2f7;font-size:0.9rem;color:#3b4a5e"><span style="color:#107c10;font-weight:700">✓</span> Prometheus metrics + Grafana dashboards</li>
						<li style="display:flex;align-items:center;gap:0.75rem;padding:0.5rem 0;font-size:0.9rem;color:#3b4a5e"><span style="color:#107c10;font-weight:700">✓</span> GitHub Actions + CI/CD integration</li>
					</ul>
					<a href="/docker" style="display:inline-block;background:#00b7c3;color:#fff;border-radius:8px;padding:0.75rem 1.5rem;font-weight:700;font-size:0.9375rem;text-decoration:none">Docker Documentation →</a>
				</div>
				<div style="background:#0d1821;border-radius:12px;padding:1.75rem;font-family:'Cascadia Code',Consolas,monospace;font-size:0.85rem;color:#cdd9e5;line-height:1.9;">
					<div style="color:#6c7f96;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.75rem">docker-compose.yml</div>
					<div><span style="color:#50e6ff">version</span>: <span style="color:#55b056">'3.9'</span></div>
					<div><span style="color:#50e6ff">services</span>:</div>
					<div>&nbsp;&nbsp;<span style="color:#0078d4">themisdb</span>:</div>
					<div>&nbsp;&nbsp;&nbsp;&nbsp;<span style="color:#50e6ff">image</span>: themisdb/themisdb:v3-latest</div>
					<div>&nbsp;&nbsp;&nbsp;&nbsp;<span style="color:#50e6ff">ports</span>: [<span style="color:#55b056">"5432:5432"</span>]</div>
					<div>&nbsp;&nbsp;&nbsp;&nbsp;<span style="color:#50e6ff">environment</span>:</div>
					<div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;THEMISDB_PASSWORD: <span style="color:#55b056">secret</span></div>
					<div>&nbsp;&nbsp;&nbsp;&nbsp;<span style="color:#50e6ff">volumes</span>:</div>
					<div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#55b056">themisdb_data:/data</span></div>
				</div>
			</div>
		</div>

	</div>
	<!-- /wp:html -->

</div>
<!-- /wp:group -->
