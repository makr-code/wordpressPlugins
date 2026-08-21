<?php
/**
 * Title: Query Loop – Editorial Cards
 * Slug: themisdb-v3/query-loop-cards
 * Categories: themisdb-v3, themisdb-v3-landing
 * Keywords: query, loop, posts, archive, cards
 * Viewport Width: 1280
 * Description: Native WordPress query loop with editorial card layout, pagination, and no-results state.
 */
?>
<!-- wp:group {"className":"tv3-query-loop-pattern","layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group tv3-query-loop-pattern">
	<!-- wp:query {"queryId":1,"query":{"perPage":12,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"className":"tv3-query-front","layout":{"type":"default"}} -->
	<div class="wp-block-query tv3-query-front">
		<!-- wp:post-template {"className":"tv3-post-grid","layout":{"type":"grid","columnCount":3}} -->
		<!-- wp:group {"className":"tv3-card tv3-card-item","layout":{"type":"constrained"}} -->
		<div class="wp-block-group tv3-card tv3-card-item">
			<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/9","className":"tv3-card-media"} /-->

			<!-- wp:group {"className":"tv3-card-body","layout":{"type":"constrained"}} -->
			<div class="wp-block-group tv3-card-body">
				<!-- wp:post-terms {"term":"category","separator":" · ","className":"tv3-card-meta"} /-->
				<!-- wp:post-title {"level":3,"isLink":true,"className":"tv3-card-title"} /-->
				<!-- wp:post-excerpt {"excerptLength":24,"className":"tv3-card-excerpt"} /-->
				<!-- wp:read-more {"content":"Beitrag anzeigen","className":"tv3-card-link"} /-->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->
		<!-- /wp:post-template -->

		<!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"space-between"},"className":"tv3-query-pagination"} -->
		<!-- wp:query-pagination-previous /-->
		<!-- wp:query-pagination-numbers /-->
		<!-- wp:query-pagination-next /-->
		<!-- /wp:query-pagination -->

		<!-- wp:query-no-results -->
		<!-- wp:paragraph {"className":"tv3-query-no-results"} -->
		<p class="tv3-query-no-results">Keine Beiträge gefunden.</p>
		<!-- /wp:paragraph -->
		<!-- /wp:query-no-results -->
	</div>
	<!-- /wp:query -->
</div>
<!-- /wp:group -->
