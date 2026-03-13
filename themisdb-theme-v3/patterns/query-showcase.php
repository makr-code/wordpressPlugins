<?php
/**
 * Title: Query Showcase – Tabbed Code Demo
 * Slug: themisdb-v3/query-showcase
 * Categories: themisdb-v3, themisdb-v3-landing
 * Viewport Width: 1280
 * Description: Code demo section with SQL, JSON, and Python tabs powered by jQuery UI Tabs (.themis-v3-tabs class).
 */
?>
<!-- wp:group {"style":{"color":{"background":"#f5f7fa"},"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group" style="background-color:#f5f7fa;padding-top:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--20)">

	<!-- wp:html --><div style="text-align:center;margin-bottom:1rem"><span style="display:inline-block;background:#cfe4fc;color:#0078d4;border-radius:9999px;padding:0.25rem 0.875rem;font-size:0.75rem;font-weight:700;letter-spacing:0.06em;text-transform:uppercase">Query Interface</span></div><!-- /wp:html -->

	<!-- wp:heading {"level":2,"textAlign":"center","style":{"typography":{"fontSize":"clamp(1.75rem,3.5vw,2.75rem)","fontWeight":"700","letterSpacing":"-0.03em"},"color":{"text":"#12202f"},"spacing":{"margin":{"bottom":"var:preset|spacing|4"}}}} -->
	<h2 class="wp-block-heading has-text-align-center" style="color:#12202f;font-size:clamp(1.75rem,3.5vw,2.75rem);font-weight:700;letter-spacing:-0.03em;margin-bottom:var(--wp--preset--spacing--4)">Write once. Query everything.</h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"textAlign":"center","style":{"typography":{"fontSize":"1.0625rem"},"color":{"text":"#6c7f96"},"spacing":{"margin":{"bottom":"var:preset|spacing|10"}}}} -->
	<p class="has-text-align-center" style="color:#6c7f96;font-size:1.0625rem;margin-bottom:var(--wp--preset--spacing--10)">SQL, JSON, or Python — ThemisDB adapts to your preferred query interface. Switch between tabs to see examples.</p>
	<!-- /wp:paragraph -->

	<!-- wp:html -->
	<div id="tv3-showcase-tabs" class="themis-v3-tabs" style="border:1px solid #dde3ec;border-radius:12px;overflow:hidden;max-width:900px;margin:0 auto;background:#fff;">
		<ul style="list-style:none;padding:0;margin:0;display:flex;background:#f5f7fa;border-bottom:1px solid #dde3ec;">
			<li><a href="#tv3-showcase-sql" style="display:block;padding:0.875rem 1.5rem;font-size:0.9rem;font-weight:600;color:#0078d4;text-decoration:none;border-bottom:2px solid #0078d4;">SQL</a></li>
			<li><a href="#tv3-showcase-json" style="display:block;padding:0.875rem 1.5rem;font-size:0.9rem;font-weight:600;color:#6c7f96;text-decoration:none;border-bottom:2px solid transparent;">JSON API</a></li>
			<li><a href="#tv3-showcase-python" style="display:block;padding:0.875rem 1.5rem;font-size:0.9rem;font-weight:600;color:#6c7f96;text-decoration:none;border-bottom:2px solid transparent;">Python SDK</a></li>
		</ul>
		<div id="tv3-showcase-sql" class="themis-v3-tab-panel" style="padding:0;background:#0d1821;">
			<div style="padding:0.625rem 1rem;background:rgba(255,255,255,0.04);border-bottom:1px solid rgba(255,255,255,0.08);display:flex;justify-content:space-between;align-items:center;">
				<span style="font-size:0.75rem;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:0.06em;font-weight:600">SQL</span>
				<button data-copy-selector="#tv3-showcase-sql code" class="tv3-code-copy-btn" style="background:none;border:1px solid rgba(255,255,255,0.2);color:rgba(255,255,255,0.5);font-size:0.75rem;padding:0.2rem 0.6rem;border-radius:4px;cursor:pointer;transition:all 0.2s">Copy</button>
			</div>
			<pre style="margin:0;background:transparent;border:none;border-radius:0;padding:1.75rem;font-family:'Cascadia Code','SFMono-Regular',Consolas,monospace;font-size:0.9rem;color:#cdd9e5;line-height:1.8;overflow-x:auto;"><code style="background:none;color:inherit;padding:0;border:none;"><span style="color:#6c7f96">-- Multi-model query: vector + relational + time-series</span>
<span style="color:#0078d4">SELECT</span>
    u.name,
    e.cosine_distance(u.profile_vec, <span style="color:#0078d4">$1</span>) <span style="color:#0078d4">AS</span> similarity,
    ts.avg_response_ms <span style="color:#0078d4">AS</span> perf
<span style="color:#0078d4">FROM</span>   users u
<span style="color:#0078d4">JOIN</span>   timeseries ts <span style="color:#0078d4">ON</span> ts.user_id = u.id
                    <span style="color:#0078d4">AND</span> ts.bucket = date_trunc(<span style="color:#55b056">'hour'</span>, NOW())
<span style="color:#0078d4">WHERE</span>  e.cosine_distance(u.profile_vec, <span style="color:#0078d4">$1</span>) &lt; <span style="color:#50e6ff">0.2</span>
<span style="color:#0078d4">ORDER BY</span> similarity <span style="color:#0078d4">ASC</span>
<span style="color:#0078d4">LIMIT</span>  <span style="color:#50e6ff">10</span>;</code></pre>
		</div>
		<div id="tv3-showcase-json" class="themis-v3-tab-panel" style="padding:0;background:#0d1821;display:none;">
			<div style="padding:0.625rem 1rem;background:rgba(255,255,255,0.04);border-bottom:1px solid rgba(255,255,255,0.08);display:flex;justify-content:space-between;align-items:center;">
				<span style="font-size:0.75rem;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:0.06em;font-weight:600">JSON API</span>
				<button data-copy-selector="#tv3-showcase-json code" class="tv3-code-copy-btn" style="background:none;border:1px solid rgba(255,255,255,0.2);color:rgba(255,255,255,0.5);font-size:0.75rem;padding:0.2rem 0.6rem;border-radius:4px;cursor:pointer;transition:all 0.2s">Copy</button>
			</div>
			<pre style="margin:0;background:transparent;border:none;border-radius:0;padding:1.75rem;font-family:'Cascadia Code','SFMono-Regular',Consolas,monospace;font-size:0.9rem;color:#cdd9e5;line-height:1.8;overflow-x:auto;"><code style="background:none;color:inherit;padding:0;border:none;"><span style="color:#0078d4">POST</span> /api/v3/multi-query
<span style="color:#6c7f96">Authorization: Bearer &lt;token&gt;</span>
<span style="color:#6c7f96">Content-Type: application/json</span>

{
  <span style="color:#50e6ff">"queries"</span>: [
    {
      <span style="color:#50e6ff">"type"</span>: <span style="color:#55b056">"vector"</span>,
      <span style="color:#50e6ff">"collection"</span>: <span style="color:#55b056">"users"</span>,
      <span style="color:#50e6ff">"embedding"</span>: <span style="color:#55b056">"[0.12, 0.45, ...]"</span>,
      <span style="color:#50e6ff">"threshold"</span>: <span style="color:#50e6ff">0.2</span>
    },
    {
      <span style="color:#50e6ff">"type"</span>: <span style="color:#55b056">"timeseries"</span>,
      <span style="color:#50e6ff">"metric"</span>: <span style="color:#55b056">"avg_response_ms"</span>,
      <span style="color:#50e6ff">"bucket"</span>: <span style="color:#55b056">"1h"</span>
    }
  ],
  <span style="color:#50e6ff">"join"</span>: <span style="color:#55b056">"user_id"</span>,
  <span style="color:#50e6ff">"limit"</span>: <span style="color:#50e6ff">10</span>
}</code></pre>
		</div>
		<div id="tv3-showcase-python" class="themis-v3-tab-panel" style="padding:0;background:#0d1821;display:none;">
			<div style="padding:0.625rem 1rem;background:rgba(255,255,255,0.04);border-bottom:1px solid rgba(255,255,255,0.08);display:flex;justify-content:space-between;align-items:center;">
				<span style="font-size:0.75rem;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:0.06em;font-weight:600">Python SDK</span>
				<button data-copy-selector="#tv3-showcase-python code" class="tv3-code-copy-btn" style="background:none;border:1px solid rgba(255,255,255,0.2);color:rgba(255,255,255,0.5);font-size:0.75rem;padding:0.2rem 0.6rem;border-radius:4px;cursor:pointer;transition:all 0.2s">Copy</button>
			</div>
			<pre style="margin:0;background:transparent;border:none;border-radius:0;padding:1.75rem;font-family:'Cascadia Code','SFMono-Regular',Consolas,monospace;font-size:0.9rem;color:#cdd9e5;line-height:1.8;overflow-x:auto;"><code style="background:none;color:inherit;padding:0;border:none;"><span style="color:#0078d4">import</span> themisdb
<span style="color:#0078d4">from</span> themisdb.ai <span style="color:#0078d4">import</span> embed

db = themisdb.<span style="color:#50e6ff">connect</span>(<span style="color:#55b056">"themisdb://localhost:5432/mydb"</span>)

<span style="color:#6c7f96"># Multi-model query: vector + timeseries join</span>
query_vector = embed(<span style="color:#55b056">"user preference similarity"</span>)

results = db.<span style="color:#50e6ff">multi_query</span>(
    vector_search={<span style="color:#55b056">"collection"</span>: <span style="color:#55b056">"users"</span>, <span style="color:#55b056">"embedding"</span>: query_vector, <span style="color:#55b056">"threshold"</span>: <span style="color:#50e6ff">0.2</span>},
    timeseries_join={<span style="color:#55b056">"metric"</span>: <span style="color:#55b056">"avg_response_ms"</span>, <span style="color:#55b056">"bucket"</span>: <span style="color:#55b056">"1h"</span>},
    join_key=<span style="color:#55b056">"user_id"</span>,
    limit=<span style="color:#50e6ff">10</span>
)

<span style="color:#0078d4">for</span> r <span style="color:#0078d4">in</span> results:
    <span style="color:#0078d4">print</span>(f<span style="color:#55b056">"{r.name}: sim={r.similarity:.3f}, perf={r.avg_response_ms}ms"</span>)</code></pre>
		</div>
	</div>
	<!-- /wp:html -->

</div>
<!-- /wp:group -->
