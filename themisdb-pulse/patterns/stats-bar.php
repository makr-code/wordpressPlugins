<?php
/**
 * Title: Stats Bar – Animated Counters
 * Slug: themisdb-v3/stats-bar
 * Categories: themisdb-v3, themisdb-v3-landing
 * Keywords: stats, counters, kpi, numbers, metrics
 * Viewport Width: 1280
 * Description: Four animated stat counters (count-up when in viewport) using .themis-v3-counter class.
 */

$docs_term = get_term_by( 'slug', 'documentation', 'category' );
$features_term = get_term_by( 'slug', 'features', 'category' );
$downloads_pages = get_posts(
	array(
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'posts_per_page' => 100,
		'fields'         => 'ids',
		'post_name__in'  => array( 'downloads', 'docker', 'compendium', 'downloads-compendium', 'compendium-download' ),
	)
);
$published_posts = wp_count_posts( 'post' );

$docs_count = ( $docs_term && ! is_wp_error( $docs_term ) ) ? (int) $docs_term->count : 0;
$features_count = ( $features_term && ! is_wp_error( $features_term ) ) ? (int) $features_term->count : 0;
$downloads_count = is_array( $downloads_pages ) ? count( $downloads_pages ) : 0;
$articles_count = ( $published_posts && isset( $published_posts->publish ) ) ? (int) $published_posts->publish : 0;
?>
<!-- wp:group {"className":"tv3-stats-bar","style":{"color":{"background":"#ffffff"},"spacing":{"padding":{"top":"0","bottom":"0"}},"border":{"bottom":{"color":"#dde3ec","width":"1px"},"top":{"color":"#dde3ec","width":"1px"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group tv3-stats-bar tv3-stats-bar-shell">
	<!-- wp:columns {"isStackedOnMobile":false,"style":{"spacing":{"blockGap":{"left":"0"}}}} -->
	<div class="wp-block-columns">

		<!-- wp:column {"style":{"border":{"right":{"color":"#dde3ec","width":"1px"}},"spacing":{"padding":{"top":"var:preset|spacing|8","bottom":"var:preset|spacing|8","left":"var:preset|spacing|6","right":"var:preset|spacing|6"}}}} -->
		<div class="wp-block-column tv3-stats-col tv3-stats-col-divider">
			<!-- wp:themisdb/stats-counter {"target":<?php echo (int) $downloads_count; ?>,"suffix":"","label":"Download Pages"} /-->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"style":{"border":{"right":{"color":"#dde3ec","width":"1px"}},"spacing":{"padding":{"top":"var:preset|spacing|8","bottom":"var:preset|spacing|8","left":"var:preset|spacing|6","right":"var:preset|spacing|6"}}}} -->
		<div class="wp-block-column tv3-stats-col tv3-stats-col-divider">
			<!-- wp:themisdb/stats-counter {"target":<?php echo (int) $features_count; ?>,"suffix":"","label":"Feature Content"} /-->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"style":{"border":{"right":{"color":"#dde3ec","width":"1px"}},"spacing":{"padding":{"top":"var:preset|spacing|8","bottom":"var:preset|spacing|8","left":"var:preset|spacing|6","right":"var:preset|spacing|6"}}}} -->
		<div class="wp-block-column tv3-stats-col tv3-stats-col-divider">
			<!-- wp:themisdb/stats-counter {"target":<?php echo (int) $docs_count; ?>,"suffix":"","label":"Documentation Items"} /-->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|8","bottom":"var:preset|spacing|8","left":"var:preset|spacing|6","right":"var:preset|spacing|6"}}}} -->
		<div class="wp-block-column tv3-stats-col">
			<!-- wp:themisdb/stats-counter {"target":<?php echo (int) $articles_count; ?>,"suffix":"","label":"Published Articles"} /-->
		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
