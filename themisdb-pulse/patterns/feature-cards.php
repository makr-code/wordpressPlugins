<?php
/**
 * Title: Feature Cards – 3×2 Grid (Azure Style)
 * Slug: themisdb-v3/feature-cards
 * Categories: themisdb-v3, themisdb-v3-landing
 * Keywords: features, cards, capabilities, landing, grid
 * Viewport Width: 1280
 * Description: Six feature cards in a 3-column grid with Azure-style blue top border on hover.
 */
?>
<!-- wp:group {"className":"tv3-feature-section","layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group tv3-feature-section">
	<?php
	$feature_copy = function_exists( 'themisdb_v3_get_frontpage_section_copy' )
		? themisdb_v3_get_frontpage_section_copy(
			'features',
			array(
				'kicker'   => 'Features',
				'title'    => 'Everything you need in one database',
				'subtitle' => 'Designed from the ground up for modern applications that need speed, flexibility, and intelligence.',
			)
		)
		: array(
			'kicker'   => 'Features',
			'title'    => 'Everything you need in one database',
			'subtitle' => 'Designed from the ground up for modern applications that need speed, flexibility, and intelligence.',
		);
	?>

	<!-- wp:paragraph {"align":"center","className":"tv3-feature-section__badge-wrap"} -->
	<p class="has-text-align-center tv3-feature-section__badge-wrap"><span class="tv3-feature-section__badge"><?php echo esc_html( (string) $feature_copy['kicker'] ); ?></span></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":2,"className":"tv3-feature-section__title"} -->
	<h2 class="wp-block-heading tv3-feature-section__title"><?php echo esc_html( (string) $feature_copy['title'] ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"className":"tv3-feature-section__lead"} -->
	<p class="tv3-feature-section__lead"><?php echo esc_html( (string) $feature_copy['subtitle'] ); ?></p>
	<!-- /wp:paragraph -->

	<?php
	if ( function_exists( 'themisdb_v3_render_feature_cards_shortcode' ) ) {
		echo themisdb_v3_render_feature_cards_shortcode(
			array(
				'limit'      => 6,
				'post_types' => 'post,page',
				'category'   => 'features',
				'priority_tag' => 'frontpage-feature',
			)
		);
	}
	?>
</div>
<!-- /wp:group -->
