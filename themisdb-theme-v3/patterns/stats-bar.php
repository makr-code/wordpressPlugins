<?php
/**
 * Title: Stats Bar – Animated Counters
 * Slug: themisdb-v3/stats-bar
 * Categories: themisdb-v3, themisdb-v3-landing
 * Viewport Width: 1280
 * Description: Four animated stat counters (count-up when in viewport) using .themis-v3-counter class.
 */
?>
<!-- wp:group {"className":"tv3-stats-bar","style":{"color":{"background":"#ffffff"},"spacing":{"padding":{"top":"0","bottom":"0"}},"border":{"bottom":{"color":"#dde3ec","width":"1px"},"top":{"color":"#dde3ec","width":"1px"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group tv3-stats-bar" style="background-color:#ffffff;border-top:1px solid #dde3ec;border-bottom:1px solid #dde3ec">
	<!-- wp:columns {"isStackedOnMobile":false,"style":{"spacing":{"blockGap":{"left":"0"}}}} -->
	<div class="wp-block-columns">

		<!-- wp:column {"style":{"border":{"right":{"color":"#dde3ec","width":"1px"}},"spacing":{"padding":{"top":"var:preset|spacing|8","bottom":"var:preset|spacing|8","left":"var:preset|spacing|6","right":"var:preset|spacing|6"}}}} -->
		<div class="wp-block-column" style="padding:var(--wp--preset--spacing--8) var(--wp--preset--spacing--6);border-right:1px solid #dde3ec">
			<!-- wp:html -->
			<div style="text-align:center">
				<div class="themis-v3-counter" data-target="500" data-suffix="K+" data-prefix="" style="font-size:2.25rem;font-weight:800;color:#003366;letter-spacing:-0.04em;line-height:1.1;display:block">500K+</div>
				<div style="font-size:0.8125rem;color:#6c7f96;margin-top:0.375rem;font-weight:500">Downloads</div>
			</div>
			<!-- /wp:html -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"style":{"border":{"right":{"color":"#dde3ec","width":"1px"}},"spacing":{"padding":{"top":"var:preset|spacing|8","bottom":"var:preset|spacing|8","left":"var:preset|spacing|6","right":"var:preset|spacing|6"}}}} -->
		<div class="wp-block-column" style="padding:var(--wp--preset--spacing--8) var(--wp--preset--spacing--6);border-right:1px solid #dde3ec">
			<!-- wp:html -->
			<div style="text-align:center">
				<div class="themis-v3-counter" data-target="50" data-suffix="+" data-prefix="" style="font-size:2.25rem;font-weight:800;color:#003366;letter-spacing:-0.04em;line-height:1.1;display:block">50+</div>
				<div style="font-size:0.8125rem;color:#6c7f96;margin-top:0.375rem;font-weight:500">Integrations</div>
			</div>
			<!-- /wp:html -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"style":{"border":{"right":{"color":"#dde3ec","width":"1px"}},"spacing":{"padding":{"top":"var:preset|spacing|8","bottom":"var:preset|spacing|8","left":"var:preset|spacing|6","right":"var:preset|spacing|6"}}}} -->
		<div class="wp-block-column" style="padding:var(--wp--preset--spacing--8) var(--wp--preset--spacing--6);border-right:1px solid #dde3ec">
			<!-- wp:html -->
			<div style="text-align:center">
				<div class="themis-v3-counter" data-target="10" data-suffix="×" data-prefix="" style="font-size:2.25rem;font-weight:800;color:#003366;letter-spacing:-0.04em;line-height:1.1;display:block">10×</div>
				<div style="font-size:0.8125rem;color:#6c7f96;margin-top:0.375rem;font-weight:500">Faster than PostgreSQL</div>
			</div>
			<!-- /wp:html -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|8","bottom":"var:preset|spacing|8","left":"var:preset|spacing|6","right":"var:preset|spacing|6"}}}} -->
		<div class="wp-block-column" style="padding:var(--wp--preset--spacing--8) var(--wp--preset--spacing--6)">
			<!-- wp:html -->
			<div style="text-align:center">
				<div style="font-size:2.25rem;font-weight:800;color:#003366;letter-spacing:-0.04em;line-height:1.1;display:block">MIT</div>
				<div style="font-size:0.8125rem;color:#6c7f96;margin-top:0.375rem;font-weight:500">Open Source License</div>
			</div>
			<!-- /wp:html -->
		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
