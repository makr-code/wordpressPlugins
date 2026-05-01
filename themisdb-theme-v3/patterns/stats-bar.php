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
<div class="wp-block-group tv3-stats-bar tv3-stats-bar-shell">
	<!-- wp:columns {"isStackedOnMobile":false,"style":{"spacing":{"blockGap":{"left":"0"}}}} -->
	<div class="wp-block-columns">

		<!-- wp:column {"style":{"border":{"right":{"color":"#dde3ec","width":"1px"}},"spacing":{"padding":{"top":"var:preset|spacing|8","bottom":"var:preset|spacing|8","left":"var:preset|spacing|6","right":"var:preset|spacing|6"}}}} -->
		<div class="wp-block-column tv3-stats-col tv3-stats-col-divider">
			<!-- wp:html -->
			<div class="tv3-stats-item">
				<div class="themis-v3-counter tv3-stats-value" data-target="500" data-suffix="K+" data-prefix="">500K+</div>
				<div class="tv3-stats-label">Downloads</div>
			</div>
			<!-- /wp:html -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"style":{"border":{"right":{"color":"#dde3ec","width":"1px"}},"spacing":{"padding":{"top":"var:preset|spacing|8","bottom":"var:preset|spacing|8","left":"var:preset|spacing|6","right":"var:preset|spacing|6"}}}} -->
		<div class="wp-block-column tv3-stats-col tv3-stats-col-divider">
			<!-- wp:html -->
			<div class="tv3-stats-item">
				<div class="themis-v3-counter tv3-stats-value" data-target="50" data-suffix="+" data-prefix="">50+</div>
				<div class="tv3-stats-label">Integrations</div>
			</div>
			<!-- /wp:html -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"style":{"border":{"right":{"color":"#dde3ec","width":"1px"}},"spacing":{"padding":{"top":"var:preset|spacing|8","bottom":"var:preset|spacing|8","left":"var:preset|spacing|6","right":"var:preset|spacing|6"}}}} -->
		<div class="wp-block-column tv3-stats-col tv3-stats-col-divider">
			<!-- wp:html -->
			<div class="tv3-stats-item">
				<div class="themis-v3-counter tv3-stats-value" data-target="10" data-suffix="×" data-prefix="">10×</div>
				<div class="tv3-stats-label">Faster than PostgreSQL</div>
			</div>
			<!-- /wp:html -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|8","bottom":"var:preset|spacing|8","left":"var:preset|spacing|6","right":"var:preset|spacing|6"}}}} -->
		<div class="wp-block-column tv3-stats-col">
			<!-- wp:html -->
			<div class="tv3-stats-item">
				<div class="tv3-stats-value">MIT</div>
				<div class="tv3-stats-label">Open Source License</div>
			</div>
			<!-- /wp:html -->
		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
