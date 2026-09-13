<?php
/**
 * Title: Pricing Section – Free vs Enterprise
 * Slug: themisdb-v3/pricing-section
 * Categories: themisdb-v3, themisdb-v3-landing
 * Keywords: pricing, plans, enterprise, community, cta
 * Viewport Width: 1280
 * Description: NEW in v3 – Azure-style pricing cards with Free (Community) and Enterprise edition, feature lists, and CTA buttons.
 */
?>
<!-- wp:group {"className":"tv3-pricing-section","layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group tv3-pricing-section">
	<?php
	$pricing_copy = function_exists( 'themisdb_v3_get_frontpage_section_copy' )
		? themisdb_v3_get_frontpage_section_copy(
			'pricing',
			array(
				'kicker'   => 'Pricing',
				'title'    => 'Start free. Scale with confidence.',
				'subtitle' => 'ThemisDB is open-source and MIT licensed. Enterprise support and SLA available for production.',
			)
		)
		: array(
			'kicker'   => 'Pricing',
			'title'    => 'Start free. Scale with confidence.',
			'subtitle' => 'ThemisDB is open-source and MIT licensed. Enterprise support and SLA available for production.',
		);
	?>

	<!-- wp:paragraph {"align":"center","className":"tv3-pricing-section__badge-wrap"} -->
	<p class="has-text-align-center tv3-pricing-section__badge-wrap"><span class="tv3-pricing-section__badge"><?php echo esc_html( (string) $pricing_copy['kicker'] ); ?></span></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":2,"className":"tv3-pricing-section__title"} -->
	<h2 class="wp-block-heading tv3-pricing-section__title"><?php echo esc_html( (string) $pricing_copy['title'] ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"className":"tv3-pricing-section__lead"} -->
	<p class="tv3-pricing-section__lead"><?php echo esc_html( (string) $pricing_copy['subtitle'] ); ?></p>
	<!-- /wp:paragraph -->

	<?php
	if ( function_exists( 'themisdb_v3_render_pricing_cards_shortcode' ) ) {
		echo themisdb_v3_render_pricing_cards_shortcode(
			array(
				'limit'        => 3,
				'post_types'   => 'page,post',
				'category'     => 'pricing',
				'priority_tag' => 'pricing-featured,most-popular,recommended,frontpage-feature',
				'orderby'      => 'menu_order',
				'order'        => 'ASC',
			)
		);
	}
	?>

</div>
<!-- /wp:group -->
