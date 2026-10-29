<?php
/**
 * Run with: php test-content-layout.php <path-to-wp-load.php>
 */
if ( PHP_SAPI !== 'cli' || empty( $argv[1] ) || ! is_readable( $argv[1] ) ) {
    fwrite( STDERR, "Usage: php test-content-layout.php <path-to-wp-load.php>\n" );
    exit( 1 );
}
require $argv[1];

$checks = 0;
function pulse_layout_check( $condition, $message ) {
    global $checks;
    if ( ! $condition ) {
        fwrite( STDERR, "FAIL: $message\n" );
        exit( 1 );
    }
    ++$checks;
    echo "PASS: $message\n";
}

function pulse_find_blocks( $blocks, $name ) {
    $found = array();
    foreach ( $blocks as $block ) {
        if ( $block['blockName'] === $name ) {
            $found[] = $block;
        }
        $found = array_merge( $found, pulse_find_blocks( $block['innerBlocks'], $name ) );
    }
    return $found;
}

$theme_directory = dirname( __DIR__ );
$config          = json_decode( file_get_contents( $theme_directory . '/theme.json' ), true );
pulse_layout_check( is_array( $config ), 'Theme configuration is valid JSON' );
pulse_layout_check( in_array( 'content-header', array_column( $config['templateParts'], 'name' ), true ), 'Shared content header is registered for the Site Editor' );
pulse_layout_check( realpath( get_stylesheet_directory() ) === realpath( $theme_directory ), 'Pulse is the active theme under test' );

foreach ( array( 'header', 'header-frontpage' ) as $slug ) {
    $header_blocks = parse_blocks( file_get_contents( $theme_directory . '/parts/' . $slug . '.html' ) );
    $groups        = pulse_find_blocks( $header_blocks, 'core/group' );
    $rows          = array_values( array_filter( $groups, function ( $block ) {
        return isset( $block['attrs']['className'] ) && 'tv3-header-row' === $block['attrs']['className'];
    } ) );
    pulse_layout_check( count( $rows ) === 1 && 'nowrap' === $rows[0]['attrs']['layout']['flexWrap'], "$slug keeps a single header row" );
    $navigation = pulse_find_blocks( $header_blocks, 'core/navigation' );
    pulse_layout_check( 'mobile' === $navigation[0]['attrs']['overlayMenu'], "$slug enables the native responsive menu" );
    $search = pulse_find_blocks( $header_blocks, 'core/search' );
    pulse_layout_check( 'button-only' === $search[0]['attrs']['buttonPosition'], "$slug enables the native expandable search" );
}
pulse_layout_check( ! has_action( 'wp_enqueue_scripts', 'themisdb_v3_enqueue_mobile_header_nav_fix' ), 'Obsolete inline rules no longer disable native navigation' );

$contents = array();
foreach ( array( 'page', 'single' ) as $slug ) {
    $blocks = parse_blocks( file_get_contents( $theme_directory . '/templates/' . $slug . '.html' ) );
    $groups = pulse_find_blocks( $blocks, 'core/group' );
    $main   = $groups[0];
    pulse_layout_check( $main['attrs']['tagName'] === 'main' && strpos( $main['attrs']['className'], 'tv3-content-main' ) !== false, "$slug uses the shared main shell" );
    pulse_layout_check( $main['attrs']['layout'] === array( 'type' => 'constrained', 'contentSize' => '800px', 'wideSize' => '1200px' ), "$slug uses native reading and wide dimensions" );

    $parts = pulse_find_blocks( $blocks, 'core/template-part' );
    $slugs = array_column( array_column( $parts, 'attrs' ), 'slug' );
    pulse_layout_check( count( array_keys( $slugs, 'content-header', true ) ) === 1, "$slug uses exactly one shared header" );
    $top_parts = pulse_find_blocks( array_filter( $blocks, function ( $block ) {
        return 'core/template-part' === $block['blockName'];
    } ), 'core/template-part' );
    pulse_layout_check( $top_parts[1]['attrs']['slug'] === 'content-header' && $top_parts[1]['attrs']['align'] === 'full', "$slug places a full-width hero directly after the site header" );
    pulse_layout_check( array_search( 'content-header', $slugs, true ) < array_search( 'breadcrumbs', $slugs, true ), "$slug places breadcrumbs after the header" );
    pulse_layout_check( in_array( 'author-cards', $slugs, true ), "$slug preserves author information" );
    pulse_layout_check( count( pulse_find_blocks( $blocks, 'core/comments' ) ) === 1, "$slug preserves comments" );
    pulse_layout_check( count( pulse_find_blocks( $blocks, 'core/post-navigation-link' ) ) === 2, "$slug preserves previous/next navigation" );
    $contents[ $slug ] = pulse_find_blocks( $blocks, 'core/post-content' );
    pulse_layout_check( count( $contents[ $slug ] ) === 1 && $contents[ $slug ][0]['attrs']['align'] === 'wide', "$slug provides one wide-capable content block" );
}
pulse_layout_check( $contents['page'][0]['attrs'] === $contents['single'][0]['attrs'], 'Pages and posts use identical content layout attributes' );

$header = parse_blocks( file_get_contents( $theme_directory . '/parts/content-header.html' ) );
$titles = pulse_find_blocks( $header, 'core/post-title' );
pulse_layout_check( count( $titles ) === 1 && $titles[0]['attrs']['level'] === 1, 'Shared header supplies exactly one H1' );
pulse_layout_check( count( pulse_find_blocks( $header, 'core/post-featured-image' ) ) === 1, 'Shared header uses the native optional featured image' );
pulse_layout_check( count( pulse_find_blocks( $header, 'core/post-excerpt' ) ) === 1, 'Shared hero includes a native excerpt' );

$samples = get_posts( array( 'post_type' => array( 'post', 'page' ), 'numberposts' => 1 ) );
pulse_layout_check( ! empty( $samples ), 'A real published item is available for rendering' );
$GLOBALS['post'] = $samples[0];
setup_postdata( $samples[0] );
$markup = do_blocks( '<!-- wp:template-part {"slug":"content-header"} /-->' );
pulse_layout_check( substr_count( $markup, '<h1' ) === 1 && strpos( $markup, esc_html( get_the_title() ) ) !== false, 'WordPress resolves and renders the shared header with the current title' );
wp_reset_postdata();
echo "$checks content layout checks passed.\n";
