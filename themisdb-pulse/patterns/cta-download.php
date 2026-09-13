<?php
/**
 * Title: Download Options – Docker, Binary, Compendium
 * Slug: themisdb-v3/cta-download
 * Categories: themisdb-v3, themisdb-v3-landing
 * Keywords: download, docker, binary, compendium, cta
 * Viewport Width: 1280
 * Description: Three download option cards with icons and CTA buttons.
 */
?>
<!-- wp:group {"style":{"color":{"background":"#f5f7fa"},"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group tv3-download-section">
	<?php
	$download_copy = function_exists( 'themisdb_v3_get_frontpage_section_copy' )
		? themisdb_v3_get_frontpage_section_copy(
			'downloads',
			array(
				'kicker'   => 'Downloads',
				'title'    => 'Get ThemisDB v3',
				'subtitle' => 'Choose your preferred deployment method. All options are free and MIT licensed.',
			)
		)
		: array(
			'kicker'   => 'Downloads',
			'title'    => 'Get ThemisDB v3',
			'subtitle' => 'Choose your preferred deployment method. All options are free and MIT licensed.',
		);
	?>

	<!-- wp:paragraph {"align":"center","className":"tv3-download-kicker-wrap"} -->
	<p class="has-text-align-center tv3-download-kicker-wrap"><span class="tv3-download-kicker"><?php echo esc_html( (string) $download_copy['kicker'] ); ?></span></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":2,"textAlign":"center","style":{"typography":{"fontSize":"clamp(1.75rem,3.5vw,2.75rem)","fontWeight":"700","letterSpacing":"-0.03em"},"color":{"text":"#12202f"},"spacing":{"margin":{"bottom":"var:preset|spacing|4"}}}} -->
	<h2 class="wp-block-heading has-text-align-center tv3-download-title"><?php echo esc_html( (string) $download_copy['title'] ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"textAlign":"center","style":{"typography":{"fontSize":"1.0625rem"},"color":{"text":"#6c7f96"},"spacing":{"margin":{"bottom":"var:preset|spacing|12"}}}} -->
	<p class="has-text-align-center tv3-download-subtitle"><?php echo esc_html( (string) $download_copy['subtitle'] ); ?></p>
	<!-- /wp:paragraph -->

	<?php
	if ( function_exists( 'themisdb_v3_render_download_cards_shortcode' ) ) {
		echo themisdb_v3_render_download_cards_shortcode(
			array(
				'limit'        => 3,
				'post_types'   => 'page,post',
				'category'     => 'downloads',
				'priority_tag' => 'download,recommended,docker,binary,compendium,frontpage-download',
				'orderby'      => 'menu_order',
				'order'        => 'ASC',
			)
		);
	}
	?>

</div>
<!-- /wp:group -->
