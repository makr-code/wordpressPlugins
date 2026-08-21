<?php
/**
 * Title: Query Showcase – Tabbed Code Demo
 * Slug: themisdb-v3/query-showcase
 * Categories: themisdb-v3, themisdb-v3-landing
 * Keywords: query, sql, python, tabs, demo
 * Viewport Width: 1280
 * Description: Code demo section with SQL, JSON, and Python tabs powered by jQuery UI Tabs (.themis-v3-tabs class).
 */
?>
<!-- wp:group {"className":"tv3-showcase-section","layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group tv3-showcase-section">

	<!-- wp:paragraph {"align":"center","className":"tv3-showcase-section__badge-wrap"} -->
	<p class="has-text-align-center tv3-showcase-section__badge-wrap"><span class="tv3-showcase-section__badge">Query Interface</span></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":2,"className":"tv3-showcase-section__title"} -->
	<h2 class="wp-block-heading tv3-showcase-section__title">Write once. Query everything.</h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"className":"tv3-showcase-section__lead"} -->
	<p class="tv3-showcase-section__lead">SQL, JSON, or Python — ThemisDB adapts to your preferred query interface. Switch between tabs to see examples.</p>
	<!-- /wp:paragraph -->

	<!-- wp:group {"anchor":"tv3-showcase-tabs","className":"themis-v3-tabs tv3-showcase-tabs","layout":{"type":"constrained"}} -->
	<div id="tv3-showcase-tabs" class="wp-block-group themis-v3-tabs tv3-showcase-tabs">
		<ul class="tv3-showcase-tabs__nav">
			<li><a href="#tv3-showcase-sql" class="tv3-showcase-tabs__link tv3-showcase-tabs__link--active">SQL</a></li>
			<li><a href="#tv3-showcase-json" class="tv3-showcase-tabs__link">JSON API</a></li>
			<li><a href="#tv3-showcase-python" class="tv3-showcase-tabs__link">Python SDK</a></li>
		</ul>
		<div id="tv3-showcase-sql" class="themis-v3-tab-panel tv3-showcase-tabs__panel">
			<div class="tv3-showcase-tabs__panel-head">
				<span class="tv3-showcase-tabs__panel-label">SQL</span>
				<button data-copy-selector="#tv3-showcase-sql code" class="tv3-code-copy-btn tv3-showcase-tabs__copy">Copy</button>
			</div>
			<pre class="tv3-showcase-tabs__code"><code><span class="tv3-code-comment">-- Multi-model query: vector + relational + time-series</span>
<span class="tv3-code-keyword">SELECT</span>
    u.name,
    e.cosine_distance(u.profile_vec, <span class="tv3-code-keyword">$1</span>) <span class="tv3-code-keyword">AS</span> similarity,
    ts.avg_response_ms <span class="tv3-code-keyword">AS</span> perf
<span class="tv3-code-keyword">FROM</span>   users u
<span class="tv3-code-keyword">JOIN</span>   timeseries ts <span class="tv3-code-keyword">ON</span> ts.user_id = u.id
                    <span class="tv3-code-keyword">AND</span> ts.bucket = date_trunc(<span class="tv3-code-string">'hour'</span>, NOW())
<span class="tv3-code-keyword">WHERE</span>  e.cosine_distance(u.profile_vec, <span class="tv3-code-keyword">$1</span>) &lt; <span class="tv3-code-number">0.2</span>
<span class="tv3-code-keyword">ORDER BY</span> similarity <span class="tv3-code-keyword">ASC</span>
<span class="tv3-code-keyword">LIMIT</span>  <span class="tv3-code-number">10</span>;</code></pre>
		</div>
		<div id="tv3-showcase-json" class="themis-v3-tab-panel tv3-showcase-tabs__panel tv3-showcase-tabs__panel--hidden">
			<div class="tv3-showcase-tabs__panel-head">
				<span class="tv3-showcase-tabs__panel-label">JSON API</span>
				<button data-copy-selector="#tv3-showcase-json code" class="tv3-code-copy-btn tv3-showcase-tabs__copy">Copy</button>
			</div>
			<pre class="tv3-showcase-tabs__code"><code><span class="tv3-code-keyword">POST</span> /api/v3/multi-query
<span class="tv3-code-comment">Authorization: Bearer &lt;token&gt;</span>
<span class="tv3-code-comment">Content-Type: application/json</span>

{
  <span class="tv3-code-number">"queries"</span>: [
    {
      <span class="tv3-code-number">"type"</span>: <span class="tv3-code-string">"vector"</span>,
      <span class="tv3-code-number">"collection"</span>: <span class="tv3-code-string">"users"</span>,
      <span class="tv3-code-number">"embedding"</span>: <span class="tv3-code-string">"[0.12, 0.45, ...]"</span>,
      <span class="tv3-code-number">"threshold"</span>: <span class="tv3-code-number">0.2</span>
    },
    {
      <span class="tv3-code-number">"type"</span>: <span class="tv3-code-string">"timeseries"</span>,
      <span class="tv3-code-number">"metric"</span>: <span class="tv3-code-string">"avg_response_ms"</span>,
      <span class="tv3-code-number">"bucket"</span>: <span class="tv3-code-string">"1h"</span>
    }
  ],
  <span class="tv3-code-number">"join"</span>: <span class="tv3-code-string">"user_id"</span>,
  <span class="tv3-code-number">"limit"</span>: <span class="tv3-code-number">10</span>
}</code></pre>
		</div>
		<div id="tv3-showcase-python" class="themis-v3-tab-panel tv3-showcase-tabs__panel tv3-showcase-tabs__panel--hidden">
			<div class="tv3-showcase-tabs__panel-head">
				<span class="tv3-showcase-tabs__panel-label">Python SDK</span>
				<button data-copy-selector="#tv3-showcase-python code" class="tv3-code-copy-btn tv3-showcase-tabs__copy">Copy</button>
			</div>
			<pre class="tv3-showcase-tabs__code"><code><span class="tv3-code-keyword">import</span> themisdb
<span class="tv3-code-keyword">from</span> themisdb.ai <span class="tv3-code-keyword">import</span> embed

db = themisdb.<span class="tv3-code-number">connect</span>(<span class="tv3-code-string">"themisdb://localhost:5432/mydb"</span>)

<span class="tv3-code-comment"># Multi-model query: vector + timeseries join</span>
query_vector = embed(<span class="tv3-code-string">"user preference similarity"</span>)

results = db.<span class="tv3-code-number">multi_query</span>(
    vector_search={<span class="tv3-code-string">"collection"</span>: <span class="tv3-code-string">"users"</span>, <span class="tv3-code-string">"embedding"</span>: query_vector, <span class="tv3-code-string">"threshold"</span>: <span class="tv3-code-number">0.2</span>},
    timeseries_join={<span class="tv3-code-string">"metric"</span>: <span class="tv3-code-string">"avg_response_ms"</span>, <span class="tv3-code-string">"bucket"</span>: <span class="tv3-code-string">"1h"</span>},
    join_key=<span class="tv3-code-string">"user_id"</span>,
    limit=<span class="tv3-code-number">10</span>
)

<span class="tv3-code-keyword">for</span> r <span class="tv3-code-keyword">in</span> results:
    <span class="tv3-code-keyword">print</span>(f<span class="tv3-code-string">"{r.name}: sim={r.similarity:.3f}, perf={r.avg_response_ms}ms"</span>)</code></pre>
		</div>
	</div>
	<!-- /wp:group -->

</div>
<!-- /wp:group -->
