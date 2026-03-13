<?php
/**
 * Title: Download Options – Docker, Binary, Compendium
 * Slug: themisdb-v3/cta-download
 * Categories: themisdb-v3, themisdb-v3-landing
 * Viewport Width: 1280
 * Description: Three download option cards with icons and CTA buttons.
 */
?>
<!-- wp:group {"style":{"color":{"background":"#f5f7fa"},"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group" style="background-color:#f5f7fa;padding-top:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--20)">

	<!-- wp:html --><div style="text-align:center;margin-bottom:1rem"><span style="display:inline-block;background:#cfe4fc;color:#0078d4;border-radius:9999px;padding:0.25rem 0.875rem;font-size:0.75rem;font-weight:700;letter-spacing:0.06em;text-transform:uppercase">Downloads</span></div><!-- /wp:html -->

	<!-- wp:heading {"level":2,"textAlign":"center","style":{"typography":{"fontSize":"clamp(1.75rem,3.5vw,2.75rem)","fontWeight":"700","letterSpacing":"-0.03em"},"color":{"text":"#12202f"},"spacing":{"margin":{"bottom":"var:preset|spacing|4"}}}} -->
	<h2 class="wp-block-heading has-text-align-center" style="color:#12202f;font-size:clamp(1.75rem,3.5vw,2.75rem);font-weight:700;letter-spacing:-0.03em;margin-bottom:var(--wp--preset--spacing--4)">Get ThemisDB v3</h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"textAlign":"center","style":{"typography":{"fontSize":"1.0625rem"},"color":{"text":"#6c7f96"},"spacing":{"margin":{"bottom":"var:preset|spacing|12"}}}} -->
	<p class="has-text-align-center" style="color:#6c7f96;font-size:1.0625rem;margin-bottom:var(--wp--preset--spacing--12)">Choose your preferred deployment method. All options are free and MIT licensed.</p>
	<!-- /wp:paragraph -->

	<!-- wp:html -->
	<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1.5rem;">

		<!-- Docker Card -->
		<div style="background:#fff;border:1px solid #dde3ec;border-radius:12px;padding:2rem;transition:all 0.25s;display:flex;flex-direction:column;gap:1rem;">
			<div style="width:56px;height:56px;background:linear-gradient(135deg,#00b7c3,#50e6ff);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.75rem;">🐳</div>
			<div>
				<div style="font-size:1.125rem;font-weight:700;color:#12202f;margin-bottom:0.375rem">Docker Hub</div>
				<div style="font-size:0.875rem;color:#6c7f96;line-height:1.6;margin-bottom:1.25rem">Official Docker image. Deploy with a single command in under 60 seconds.</div>
				<div style="background:#0d1821;border-radius:8px;padding:0.75rem 1rem;font-family:'Cascadia Code',Consolas,monospace;font-size:0.8125rem;color:#cdd9e5;margin-bottom:1rem;overflow-x:auto;white-space:nowrap">
					<span style="color:#50e6ff">$</span> docker pull themisdb/themisdb:v3-latest
				</div>
			</div>
			<a href="/docker" style="display:block;text-align:center;background:#0078d4;color:#fff;border-radius:8px;padding:0.75rem;font-weight:700;font-size:0.9375rem;text-decoration:none;margin-top:auto;transition:background 0.2s">Docker Hub →</a>
		</div>

		<!-- Binary Release Card -->
		<div style="background:#fff;border:2px solid #0078d4;border-radius:12px;padding:2rem;transition:all 0.25s;display:flex;flex-direction:column;gap:1rem;box-shadow:0 4px 20px rgba(0,120,212,0.15);position:relative">
			<div style="position:absolute;top:1rem;right:1rem;background:#0078d4;color:#fff;font-size:0.6875rem;font-weight:700;letter-spacing:0.05em;padding:0.15rem 0.6rem;border-radius:9999px;text-transform:uppercase">Recommended</div>
			<div style="width:56px;height:56px;background:linear-gradient(135deg,#0078d4,#005a9e);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.75rem;">📦</div>
			<div>
				<div style="font-size:1.125rem;font-weight:700;color:#12202f;margin-bottom:0.375rem">Binary Release</div>
				<div style="font-size:0.875rem;color:#6c7f96;line-height:1.6;margin-bottom:1.25rem">Pre-compiled binaries for Linux, macOS, and Windows. No dependencies required.</div>
				<div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-bottom:1rem">
					<span style="background:#f5f7fa;border:1px solid #dde3ec;border-radius:6px;padding:0.2rem 0.6rem;font-size:0.8125rem;color:#3b4a5e;font-weight:600">Linux</span>
					<span style="background:#f5f7fa;border:1px solid #dde3ec;border-radius:6px;padding:0.2rem 0.6rem;font-size:0.8125rem;color:#3b4a5e;font-weight:600">macOS</span>
					<span style="background:#f5f7fa;border:1px solid #dde3ec;border-radius:6px;padding:0.2rem 0.6rem;font-size:0.8125rem;color:#3b4a5e;font-weight:600">Windows</span>
				</div>
			</div>
			<a href="/downloads" style="display:block;text-align:center;background:#0078d4;color:#fff;border-radius:8px;padding:0.75rem;font-weight:700;font-size:0.9375rem;text-decoration:none;margin-top:auto;transition:background 0.2s">⬇ Download v3.0</a>
		</div>

		<!-- Compendium/Source Card -->
		<div style="background:#fff;border:1px solid #dde3ec;border-radius:12px;padding:2rem;transition:all 0.25s;display:flex;flex-direction:column;gap:1rem;">
			<div style="width:56px;height:56px;background:linear-gradient(135deg,#003366,#00509e);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.75rem;">📖</div>
			<div>
				<div style="font-size:1.125rem;font-weight:700;color:#12202f;margin-bottom:0.375rem">Compendium Download</div>
				<div style="font-size:0.875rem;color:#6c7f96;line-height:1.6;margin-bottom:1.25rem">Full documentation bundle, source code, plugins, and examples in a single archive.</div>
				<div style="display:flex;align-items:center;gap:0.5rem;font-size:0.8125rem;color:#6c7f96;margin-bottom:1rem">
					<span>📄</span> 85 MB · ZIP archive · MIT License
				</div>
			</div>
			<a href="/downloads/compendium" style="display:block;text-align:center;background:transparent;color:#0078d4;border:1.5px solid #0078d4;border-radius:8px;padding:0.75rem;font-weight:700;font-size:0.9375rem;text-decoration:none;margin-top:auto;transition:all 0.2s">Get Compendium →</a>
		</div>

	</div>
	<!-- /wp:html -->

</div>
<!-- /wp:group -->
