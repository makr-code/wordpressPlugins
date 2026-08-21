<?php
/**
 * Title: Documentation Grid – Dynamic Documentation Tiles
 * Slug: themisdb-v3/docs-grid
 * Categories: themisdb-v3, themisdb-v3-docs
 * Keywords: docs, documentation, knowledge-base, grid, resources
 * Viewport Width: 1280
 * Description: Dynamic documentation and resource tiles from ThemisDB documentation pages.
 */
?>
<!-- wp:group {"style":{"color":{"background":"#ffffff"},"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group tv3-docs-grid-section">

	<!-- wp:paragraph {"align":"center","className":"tv3-docs-grid-kicker-wrap"} -->
	<p class="has-text-align-center tv3-docs-grid-kicker-wrap"><span class="tv3-docs-grid-kicker">Documentation</span></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":2,"textAlign":"center","style":{"typography":{"fontSize":"clamp(1.75rem,3.5vw,2.75rem)","fontWeight":"700","letterSpacing":"-0.03em"},"color":{"text":"#12202f"},"spacing":{"margin":{"bottom":"var:preset|spacing|4"}}}} -->
	<h2 class="wp-block-heading has-text-align-center tv3-docs-grid-title">Explore the Documentation</h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"textAlign":"center","style":{"typography":{"fontSize":"1.0625rem"},"color":{"text":"#6c7f96"},"spacing":{"margin":{"bottom":"var:preset|spacing|12"}}}} -->
	<p class="has-text-align-center tv3-docs-grid-subtitle">Everything you need to build, deploy, and scale with ThemisDB.</p>
	<!-- /wp:paragraph -->

	<!-- Dynamic documentation cards rendered from WP pages with documentation tags/roots -->
	<?php
	if ( function_exists( 'themisdb_v3_render_docs_cards_shortcode' ) ) {
		echo themisdb_v3_render_docs_cards_shortcode(
			array(
				'limit'      => 8,
				'post_types' => 'post,page',
				'category'   => 'documentation',
				'priority_tag' => 'frontpage-documentation',
			)
		);
	}
	?>

</div>
<!-- /wp:group -->
